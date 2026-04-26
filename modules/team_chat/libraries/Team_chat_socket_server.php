<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat_socket_server
 *
 * WebSocket server library built on Ratchet (PHP).
 * Manages real-time connections, rooms (conversations),
 * typing indicators, presence, and message broadcasting.
 *
 * Entry point: modules/team_chat/websocket/team_chat_server.php
 *
 * Requires:
 *   composer require cboden/ratchet
 *
 * Usage (CLI):
 *   php path/to/team_chat_server.php
 */

// Ratchet interfaces — only loaded when running as WS server
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Team_chat_socket_server implements MessageComponentInterface
{
    /**
     * All open WebSocket connections.
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * Map of connection resource ID → user data
     * [ resourceId => ['user_id' => int, 'conversation_ids' => [], 'conn' => ConnectionInterface] ]
     * @var array
     */
    protected $connections = [];

    /**
     * Map of conversation_id → array of resource IDs
     * Used to broadcast to all members of a conversation efficiently.
     * @var array
     */
    protected $rooms = [];

    /**
     * Typing state per conversation.
     * [ conversation_id => [ user_id => timestamp ] ]
     * @var array
     */
    protected $typing = [];

    /**
     * Shared secret token used to authenticate internal API→WS pushes.
     * Set via TEAM_CHAT_WS_SECRET constant in config.
     * @var string
     */
    protected $secret;

    /**
     * Timestamp of last ping sweep.
     * @var int
     */
    protected $last_ping = 0;

    /**
     * Ping interval in seconds.
     * @var int
     */
    protected $ping_interval = 30;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->secret  = defined('TEAM_CHAT_WS_SECRET') ? TEAM_CHAT_WS_SECRET : 'changeme_secret';
    }

    // =========================================================
    // RATCHET INTERFACE
    // =========================================================

    /**
     * Fires when a new WebSocket connection is opened.
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        $resource_id = $conn->resourceId;

        // Parse query string for auth token: ws://host?token=xxx&user_id=yyy
        $query = [];
        if (isset($conn->httpRequest)) {
            $uri   = $conn->httpRequest->getUri();
            parse_str($uri->getQuery(), $query);
        }

        $user_id = (int)($query['user_id'] ?? 0);
        $token   = $query['token']   ?? '';

        // Validate token against our secret + user_id
        if (!$this->_validate_token($token, $user_id)) {
            $this->_send($conn, ['event' => 'error', 'message' => 'Unauthorized']);
            $conn->close();
            return;
        }

        // Register the connection
        $this->connections[$resource_id] = [
            'user_id'          => $user_id,
            'conversation_ids' => [],
            'conn'             => $conn,
            'connected_at'     => time(),
            'last_pong'        => time(),
        ];

        $this->_log('Client connected: user_id=' . $user_id . ' rid=' . $resource_id);

        // Acknowledge connection
        $this->_send($conn, [
            'event'   => 'connected',
            'user_id' => $user_id,
        ]);
    }

    /**
     * Fires when a message is received from a client.
     * All messages are JSON-encoded objects with an 'event' field.
     */
    public function onMessage(ConnectionInterface $from, $raw)
    {
        $resource_id = $from->resourceId;

        if (!isset($this->connections[$resource_id])) {
            return;
        }

        $data = json_decode($raw, true);

        if (!is_array($data) || empty($data['event'])) {
            $this->_send($from, ['event' => 'error', 'message' => 'Invalid payload']);
            return;
        }

        $event   = $data['event'];
        $user_id = $this->connections[$resource_id]['user_id'];

        switch ($event) {

            // ── Client joins a conversation room ──────────────
            case 'join':
                $conversation_id = (int)($data['conversation_id'] ?? 0);
                if ($conversation_id) {
                    $this->_join_room($resource_id, $conversation_id, $user_id);
                }
                break;

            // ── Client leaves a conversation room ────────────
            case 'leave':
                $conversation_id = (int)($data['conversation_id'] ?? 0);
                if ($conversation_id) {
                    $this->_leave_room($resource_id, $conversation_id, $user_id);
                }
                break;

            // ── Typing indicator ──────────────────────────────
            case 'typing':
                $conversation_id = (int)($data['conversation_id'] ?? 0);
                $is_typing       = (bool)($data['is_typing']       ?? false);
                if ($conversation_id) {
                    $this->_handle_typing($resource_id, $conversation_id, $user_id, $is_typing);
                }
                break;

            // ── Client marks conversation as read ─────────────
            case 'read':
                $conversation_id = (int)($data['conversation_id'] ?? 0);
                if ($conversation_id) {
                    $this->_broadcast_to_room($conversation_id, [
                        'event'           => 'read',
                        'conversation_id' => $conversation_id,
                        'user_id'         => $user_id,
                        'at'              => date('Y-m-d H:i:s'),
                    ], $exclude_resource_id = null);
                }
                break;

            // ── Internal server push (from PHP API via HTTP) ──
            // The API sends a signed message to the WS server
            // to broadcast a new message/event to all clients.
            case 'server_push':
                $push_secret = $data['secret'] ?? '';
                if (!hash_equals($this->secret, $push_secret)) {
                    $this->_send($from, ['event' => 'error', 'message' => 'Forbidden']);
                    break;
                }
                $this->_handle_server_push($data);
                break;

            // ── Pong response from client ─────────────────────
            case 'pong':
                $this->connections[$resource_id]['last_pong'] = time();
                break;

            default:
                $this->_send($from, ['event' => 'error', 'message' => 'Unknown event: ' . $event]);
                break;
        }

        // Periodic ping sweep
        $this->_ping_sweep();
    }

    /**
     * Fires when a connection is closed (client disconnects).
     */
    public function onClose(ConnectionInterface $conn)
    {
        $resource_id = $conn->resourceId;

        if (isset($this->connections[$resource_id])) {
            $user_id = $this->connections[$resource_id]['user_id'];

            // Remove from all rooms
            foreach ($this->connections[$resource_id]['conversation_ids'] as $conv_id) {
                $this->_leave_room($resource_id, $conv_id, $user_id, $broadcast = false);
            }

            // Broadcast presence offline to all rooms this user was in
            $this->_broadcast_presence($user_id, false);

            unset($this->connections[$resource_id]);
        }

        $this->clients->detach($conn);
        $this->_log('Client disconnected: rid=' . $resource_id);
    }

    /**
     * Fires on connection error.
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->_log('Error on rid=' . $conn->resourceId . ': ' . $e->getMessage(), 'error');
        $conn->close();
    }

    // =========================================================
    // PUBLIC — BROADCAST API
    // Called by Team_chat_push_service via HTTP internal request
    // =========================================================

    /**
     * Broadcasts a new message event to all members of a conversation.
     * Called after a message is saved via the API.
     */
    public function broadcast_new_message(array $message, $conversation_id)
    {
        $this->_broadcast_to_room((int)$conversation_id, [
            'event'   => 'new_message',
            'message' => $message,
        ]);
    }

    /**
     * Broadcasts a message edited event.
     */
    public function broadcast_message_edited(array $message, $conversation_id)
    {
        $this->_broadcast_to_room((int)$conversation_id, [
            'event'   => 'message_edited',
            'message' => $message,
        ]);
    }

    /**
     * Broadcasts a message deleted event.
     */
    public function broadcast_message_deleted($message_id, $conversation_id)
    {
        $this->_broadcast_to_room((int)$conversation_id, [
            'event'      => 'message_deleted',
            'message_id' => (int)$message_id,
        ]);
    }

    /**
     * Broadcasts a reaction update to a conversation room.
     */
    public function broadcast_reaction(array $reactions, $message_id, $conversation_id)
    {
        $this->_broadcast_to_room((int)$conversation_id, [
            'event'      => 'reaction_updated',
            'message_id' => (int)$message_id,
            'reactions'  => $reactions,
        ]);
    }

    /**
     * Broadcasts that a new member joined a conversation.
     */
    public function broadcast_member_joined(array $member, $conversation_id)
    {
        $this->_broadcast_to_room((int)$conversation_id, [
            'event'  => 'member_joined',
            'member' => $member,
        ]);
    }

    /**
     * Broadcasts that a member left a conversation.
     */
    public function broadcast_member_left($user_id, $conversation_id)
    {
        $this->_broadcast_to_room((int)$conversation_id, [
            'event'   => 'member_left',
            'user_id' => (int)$user_id,
        ]);
    }

    /**
     * Broadcasts conversation metadata update (rename, archive, etc.)
     */
    public function broadcast_conversation_updated(array $conversation)
    {
        $conversation_id = (int)$conversation['id'];
        $this->_broadcast_to_room($conversation_id, [
            'event'        => 'conversation_updated',
            'conversation' => $conversation,
        ]);
    }

    // =========================================================
    // ROOM MANAGEMENT
    // =========================================================

    /**
     * Subscribes a connection to a conversation room.
     * Broadcasts presence online to other room members.
     */
    private function _join_room($resource_id, $conversation_id, $user_id)
    {
        $conversation_id = (int)$conversation_id;

        // Add to room index
        if (!isset($this->rooms[$conversation_id])) {
            $this->rooms[$conversation_id] = [];
        }

        if (!in_array($resource_id, $this->rooms[$conversation_id])) {
            $this->rooms[$conversation_id][] = $resource_id;
        }

        // Track which rooms this connection belongs to
        if (!in_array($conversation_id, $this->connections[$resource_id]['conversation_ids'])) {
            $this->connections[$resource_id]['conversation_ids'][] = $conversation_id;
        }

        // Notify room of presence
        $this->_broadcast_to_room($conversation_id, [
            'event'           => 'presence',
            'user_id'         => $user_id,
            'online'          => true,
            'conversation_id' => $conversation_id,
        ], $resource_id); // Exclude self

        // Confirm join to the client
        $this->_send($this->connections[$resource_id]['conn'], [
            'event'           => 'joined',
            'conversation_id' => $conversation_id,
        ]);

        $this->_log('User ' . $user_id . ' joined room ' . $conversation_id);
    }

    /**
     * Removes a connection from a conversation room.
     */
    private function _leave_room($resource_id, $conversation_id, $user_id, $broadcast = true)
    {
        $conversation_id = (int)$conversation_id;

        if (isset($this->rooms[$conversation_id])) {
            $this->rooms[$conversation_id] = array_values(
                array_filter($this->rooms[$conversation_id], fn($rid) => $rid !== $resource_id)
            );

            if (empty($this->rooms[$conversation_id])) {
                unset($this->rooms[$conversation_id]);
            }
        }

        // Remove from connection's room list
        if (isset($this->connections[$resource_id])) {
            $this->connections[$resource_id]['conversation_ids'] = array_values(
                array_filter(
                    $this->connections[$resource_id]['conversation_ids'],
                    fn($cid) => $cid !== $conversation_id
                )
            );
        }

        if ($broadcast) {
            $this->_broadcast_to_room($conversation_id, [
                'event'           => 'presence',
                'user_id'         => $user_id,
                'online'          => false,
                'conversation_id' => $conversation_id,
            ]);
        }

        // Clear typing state if any
        $this->_clear_typing($conversation_id, $user_id);
    }

    // =========================================================
    // TYPING INDICATORS
    // =========================================================

    /**
     * Handles a typing event from a client.
     * Broadcasts to all other members of the conversation.
     */
    private function _handle_typing($resource_id, $conversation_id, $user_id, $is_typing)
    {
        $conversation_id = (int)$conversation_id;

        if ($is_typing) {
            if (!isset($this->typing[$conversation_id])) {
                $this->typing[$conversation_id] = [];
            }
            $this->typing[$conversation_id][$user_id] = time();
        } else {
            $this->_clear_typing($conversation_id, $user_id);
        }

        $this->_broadcast_to_room($conversation_id, [
            'event'           => 'typing',
            'user_id'         => $user_id,
            'is_typing'       => $is_typing,
            'conversation_id' => $conversation_id,
        ], $resource_id); // Exclude self
    }

    /**
     * Removes a user from the typing state for a conversation.
     */
    private function _clear_typing($conversation_id, $user_id)
    {
        if (isset($this->typing[$conversation_id][$user_id])) {
            unset($this->typing[$conversation_id][$user_id]);
        }

        if (isset($this->typing[$conversation_id]) && empty($this->typing[$conversation_id])) {
            unset($this->typing[$conversation_id]);
        }
    }

    // =========================================================
    // SERVER PUSH HANDLER
    // =========================================================

    /**
     * Handles authenticated server push events from the PHP API.
     * The API calls the push service which connects to the WS server
     * and sends a signed 'server_push' event.
     */
    private function _handle_server_push(array $data)
    {
        $push_event = $data['push_event'] ?? '';
        $payload    = $data['payload']    ?? [];

        switch ($push_event) {
            case 'new_message':
                $this->_broadcast_to_room(
                    (int)($payload['conversation_id'] ?? 0),
                    ['event' => 'new_message', 'message' => $payload]
                );
                break;

            case 'message_edited':
                $this->_broadcast_to_room(
                    (int)($payload['conversation_id'] ?? 0),
                    ['event' => 'message_edited', 'message' => $payload]
                );
                break;

            case 'message_deleted':
                $this->_broadcast_to_room(
                    (int)($payload['conversation_id'] ?? 0),
                    ['event' => 'message_deleted', 'message_id' => (int)($payload['message_id'] ?? 0)]
                );
                break;

            case 'reaction_updated':
                $this->_broadcast_to_room(
                    (int)($payload['conversation_id'] ?? 0),
                    [
                        'event'      => 'reaction_updated',
                        'message_id' => (int)($payload['message_id'] ?? 0),
                        'reactions'  => $payload['reactions'] ?? [],
                    ]
                );
                break;

            case 'member_joined':
                $this->_broadcast_to_room(
                    (int)($payload['conversation_id'] ?? 0),
                    ['event' => 'member_joined', 'member' => $payload['member'] ?? []]
                );
                break;

            case 'member_left':
                $this->_broadcast_to_room(
                    (int)($payload['conversation_id'] ?? 0),
                    ['event' => 'member_left', 'user_id' => (int)($payload['user_id'] ?? 0)]
                );
                break;

            case 'conversation_updated':
                $this->_broadcast_to_room(
                    (int)($payload['id'] ?? 0),
                    ['event' => 'conversation_updated', 'conversation' => $payload]
                );
                break;

            default:
                $this->_log('Unknown server push event: ' . $push_event, 'warning');
                break;
        }
    }

    // =========================================================
    // PRESENCE
    // =========================================================

    /**
     * Broadcasts a user's online/offline status to all rooms they belong to.
     */
    private function _broadcast_presence($user_id, $online)
    {
        // Find all conversations this user is connected to
        $conversation_ids = [];
        foreach ($this->connections as $rid => $data) {
            if ((int)$data['user_id'] === (int)$user_id) {
                $conversation_ids = array_merge($conversation_ids, $data['conversation_ids']);
            }
        }

        $conversation_ids = array_unique($conversation_ids);

        foreach ($conversation_ids as $conv_id) {
            $this->_broadcast_to_room($conv_id, [
                'event'   => 'presence',
                'user_id' => (int)$user_id,
                'online'  => $online,
            ]);
        }
    }

    // =========================================================
    // INTERNAL HELPERS
    // =========================================================

    /**
     * Broadcasts a payload to all connections in a room.
     *
     * @param int      $conversation_id
     * @param array    $payload
     * @param int|null $exclude_resource_id  Exclude this connection (e.g. sender)
     */
    private function _broadcast_to_room($conversation_id, array $payload, $exclude_resource_id = null)
    {
        $conversation_id = (int)$conversation_id;

        if (empty($this->rooms[$conversation_id])) {
            return;
        }

        $json = json_encode($payload);

        foreach ($this->rooms[$conversation_id] as $resource_id) {
            if ($exclude_resource_id !== null && $resource_id === $exclude_resource_id) {
                continue;
            }

            if (isset($this->connections[$resource_id])) {
                try {
                    $this->connections[$resource_id]['conn']->send($json);
                } catch (\Exception $e) {
                    $this->_log('Send failed rid=' . $resource_id . ': ' . $e->getMessage(), 'error');
                }
            }
        }
    }

    /**
     * Sends a payload to a single connection.
     */
    private function _send(ConnectionInterface $conn, array $payload)
    {
        try {
            $conn->send(json_encode($payload));
        } catch (\Exception $e) {
            $this->_log('Send error: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Validates the client authentication token.
     * Token = HMAC-SHA256 of user_id with the shared secret.
     */
    private function _validate_token($token, $user_id)
    {
        if (!$user_id || empty($token)) {
            return false;
        }

        $expected = hash_hmac('sha256', (string)$user_id, $this->secret);
        return hash_equals($expected, $token);
    }

    /**
     * Periodic ping sweep: sends ping to all clients
     * and closes connections that have not ponged within 2x the interval.
     */
    private function _ping_sweep()
    {
        $now = time();

        if (($now - $this->last_ping) < $this->ping_interval) {
            return;
        }

        $this->last_ping = $now;
        $stale_threshold = $now - ($this->ping_interval * 2);

        foreach ($this->connections as $resource_id => $data) {
            // Close stale connections that never ponged
            if ($data['last_pong'] < $stale_threshold) {
                $this->_log('Closing stale connection rid=' . $resource_id);
                $data['conn']->close();
                continue;
            }

            // Send ping
            $this->_send($data['conn'], ['event' => 'ping']);
        }

        // Also expire old typing states (older than 8 seconds)
        foreach ($this->typing as $conv_id => $typers) {
            foreach ($typers as $uid => $ts) {
                if (($now - $ts) > 8) {
                    unset($this->typing[$conv_id][$uid]);

                    // Broadcast typing stopped
                    $this->_broadcast_to_room($conv_id, [
                        'event'           => 'typing',
                        'user_id'         => $uid,
                        'is_typing'       => false,
                        'conversation_id' => $conv_id,
                    ]);
                }
            }
        }
    }

    /**
     * Logs a message to the PHP error log with a Team Chat prefix.
     */
    private function _log($message, $level = 'debug')
    {
        $prefix = '[TeamChat WS] ';
        switch ($level) {
            case 'error':
                error_log($prefix . 'ERROR: ' . $message);
                break;
            case 'warning':
                error_log($prefix . 'WARN: '  . $message);
                break;
            default:
                if (defined('TEAM_CHAT_WS_DEBUG') && TEAM_CHAT_WS_DEBUG) {
                    error_log($prefix . $message);
                }
                break;
        }
    }

    // =========================================================
    // STATS — for admin/monitoring
    // =========================================================

    /**
     * Returns server stats: connection count, room sizes, typing users.
     */
    public function get_stats()
    {
        $room_sizes = [];
        foreach ($this->rooms as $conv_id => $rids) {
            $room_sizes[$conv_id] = count($rids);
        }

        return [
            'total_connections' => count($this->connections),
            'total_rooms'       => count($this->rooms),
            'room_sizes'        => $room_sizes,
            'typing_states'     => count($this->typing),
            'uptime_seconds'    => time() - (defined('TEAM_CHAT_WS_START') ? TEAM_CHAT_WS_START : time()),
        ];
    }
}