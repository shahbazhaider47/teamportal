<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat_push_service
 *
 * Acts as the bridge between the PHP API controllers and the
 * WebSocket server. After any data-mutating API call (send message,
 * edit, delete, reaction, member change), the API loads this library
 * and calls the appropriate push method.
 *
 * Delivery strategy (in order of preference):
 *   1. WebSocket push — connects to the running WS server via cURL
 *      and sends a signed server_push event for instant delivery.
 *   2. Polling fallback — if WS server is unavailable, writes an
 *      event to the chat_push_queue DB table so polling clients
 *      can pick it up on their next /unread_counts request.
 *
 * The queue table is lightweight and self-cleaning (events older
 * than 60 seconds are purged automatically).
 */
class Team_chat_push_service
{
    /**
     * @var CI_Controller
     */
    private $CI;

    /**
     * WebSocket server internal URL.
     * The WS server listens on a separate port for server_push events.
     * Default: http://127.0.0.1:8091 (internal HTTP port, not the WS port)
     */
    private $ws_internal_url;

    /**
     * Shared secret for signing server_push events.
     */
    private $secret;

    /**
     * cURL timeout in seconds for the WS push attempt.
     */
    private $curl_timeout = 2;

    /**
     * Whether to fall back to DB queue if WS push fails.
     */
    private $use_queue_fallback = true;

    /**
     * Queue event TTL in seconds. Events older than this are discarded.
     */
    private $queue_ttl = 60;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->ws_internal_url = defined('TEAM_CHAT_WS_INTERNAL_URL')
            ? TEAM_CHAT_WS_INTERNAL_URL
            : 'http://127.0.0.1:8091';

        $this->secret = defined('TEAM_CHAT_WS_SECRET')
            ? TEAM_CHAT_WS_SECRET
            : 'changeme_secret';

        // Ensure the push queue table exists (created lazily on first push)
        $this->_ensure_queue_table();
    }

    // =========================================================
    // PUBLIC PUSH METHODS
    // Called by API controller after each mutation
    // =========================================================

    /**
     * Broadcasts a new message to all conversation members.
     *
     * @param array $message         Hydrated message array from the model
     * @param int   $conversation_id
     */
    public function push_new_message(array $message, $conversation_id)
    {
        $this->_push('new_message', $message, (int)$conversation_id);
    }

    /**
     * Broadcasts a message edit.
     *
     * @param array $message         Updated hydrated message
     * @param int   $conversation_id
     */
    public function push_message_edited(array $message, $conversation_id)
    {
        $this->_push('message_edited', $message, (int)$conversation_id);
    }

    /**
     * Broadcasts a message deletion.
     *
     * @param int $message_id
     * @param int $conversation_id
     */
    public function push_message_deleted($message_id, $conversation_id)
    {
        $this->_push('message_deleted', [
            'message_id'      => (int)$message_id,
            'conversation_id' => (int)$conversation_id,
        ], (int)$conversation_id);
    }

    /**
     * Broadcasts a reaction update for a specific message.
     *
     * @param int   $message_id
     * @param int   $conversation_id
     * @param array $reactions        Full grouped reactions array
     */
    public function push_reaction_updated($message_id, $conversation_id, array $reactions)
    {
        $this->_push('reaction_updated', [
            'message_id'      => (int)$message_id,
            'conversation_id' => (int)$conversation_id,
            'reactions'       => $reactions,
        ], (int)$conversation_id);
    }

    /**
     * Broadcasts that a new member joined a conversation.
     *
     * @param array $member          Member row with user details
     * @param int   $conversation_id
     */
    public function push_member_joined(array $member, $conversation_id)
    {
        $this->_push('member_joined', [
            'conversation_id' => (int)$conversation_id,
            'member'          => $member,
        ], (int)$conversation_id);
    }

    /**
     * Broadcasts that a member left or was removed from a conversation.
     *
     * @param int $user_id
     * @param int $conversation_id
     */
    public function push_member_left($user_id, $conversation_id)
    {
        $this->_push('member_left', [
            'user_id'         => (int)$user_id,
            'conversation_id' => (int)$conversation_id,
        ], (int)$conversation_id);
    }

    /**
     * Broadcasts a conversation metadata update (rename, archive, etc.)
     *
     * @param array $conversation    Full conversation array
     */
    public function push_conversation_updated(array $conversation)
    {
        $this->_push('conversation_updated', $conversation, (int)$conversation['id']);
    }

    /**
     * Broadcasts a typing indicator event.
     * This is fire-and-forget — no queue fallback for typing.
     *
     * @param int  $user_id
     * @param int  $conversation_id
     * @param bool $is_typing
     */
    public function push_typing($user_id, $conversation_id, $is_typing)
    {
        $payload = [
            'user_id'         => (int)$user_id,
            'conversation_id' => (int)$conversation_id,
            'is_typing'       => (bool)$is_typing,
        ];

        // Typing is time-sensitive — WS only, no queue fallback
        $this->_push_via_ws('typing', $payload, (int)$conversation_id, $queue_on_fail = false);
    }

    // =========================================================
    // QUEUE POLLING — called by /unread_counts endpoint
    // =========================================================

    /**
     * Returns and clears all pending queue events for a given user.
     * Used when WebSocket is unavailable and client is polling.
     *
     * @param int $user_id
     * @return array
     */
    public function dequeue_for_user($user_id)
    {
        $user_id = (int)$user_id;

        // Get events scoped to this user's conversations
        $this->CI->db->select('q.*');
        $this->CI->db->from('chat_push_queue q');
        $this->CI->db->join('chat_members mem',
            'mem.conversation_id = q.conversation_id
             AND mem.user_id = ' . $user_id . '
             AND mem.left_at IS NULL', 'inner');
        $this->CI->db->where('q.created_at >=',
            date('Y-m-d H:i:s', time() - $this->queue_ttl));
        $this->CI->db->order_by('q.id', 'ASC');

        $events = $this->CI->db->get()->result_array();

        // Decode payloads
        foreach ($events as &$event) {
            $event['payload'] = json_decode($event['payload'], true);
        }
        unset($event);

        return $events;
    }

    /**
     * Purges queue events older than the TTL.
     * Called automatically on each push.
     */
    public function purge_queue()
    {
        $cutoff = date('Y-m-d H:i:s', time() - $this->queue_ttl);
        $this->CI->db->where('created_at <', $cutoff)->delete('chat_push_queue');
    }

    // =========================================================
    // INTERNAL
    // =========================================================

    /**
     * Core push method.
     * Tries WS first; falls back to queue on failure if configured.
     */
    private function _push($event, array $payload, $conversation_id, $queue_on_fail = true)
    {
        $pushed = $this->_push_via_ws($event, $payload, $conversation_id, false);

        if (!$pushed && $queue_on_fail && $this->use_queue_fallback) {
            $this->_enqueue($event, $payload, $conversation_id);
        }

        // Always purge stale queue rows
        $this->purge_queue();
    }

    /**
     * Attempts to push an event to the WebSocket server via cURL HTTP.
     * The WS server exposes a minimal internal HTTP endpoint that accepts
     * signed server_push payloads and broadcasts them to all connected clients.
     *
     * @param string $event
     * @param array  $payload
     * @param int    $conversation_id
     * @param bool   $queue_on_fail   Whether to queue if this call fails
     * @return bool  Whether the push succeeded
     */
    private function _push_via_ws($event, array $payload, $conversation_id, $queue_on_fail = true)
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $body = json_encode([
            'event'           => 'server_push',
            'secret'          => $this->secret,
            'push_event'      => $event,
            'conversation_id' => $conversation_id,
            'payload'         => $payload,
        ]);

        $ch = curl_init($this->ws_internal_url . '/push');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->curl_timeout,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($body),
                'X-TC-Secret: ' . $this->secret,
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error || $http_code !== 200) {
            log_message('debug', '[TeamChat Push] WS push failed event=' . $event
                . ' err=' . $curl_error . ' http=' . $http_code);

            if ($queue_on_fail && $this->use_queue_fallback) {
                $this->_enqueue($event, $payload, $conversation_id);
            }

            return false;
        }

        return true;
    }

    /**
     * Writes an event to the DB queue for polling clients.
     */
    private function _enqueue($event, array $payload, $conversation_id)
    {
        $this->CI->db->insert('chat_push_queue', [
            'event'           => $event,
            'conversation_id' => (int)$conversation_id,
            'payload'         => json_encode($payload),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Generates a signed token for a user.
     * Used by the view to authenticate the WebSocket connection.
     *
     * @param int $user_id
     * @return string
     */
    public function generate_client_token($user_id)
    {
        return hash_hmac('sha256', (string)(int)$user_id, $this->secret);
    }

    /**
     * Creates the chat_push_queue table if it does not exist.
     * This is a lightweight ephemeral table — no migrations needed.
     */
    private function _ensure_queue_table()
    {
        if ($this->CI->db->table_exists('chat_push_queue')) {
            return;
        }

        $this->CI->db->query('
            CREATE TABLE IF NOT EXISTS `chat_push_queue` (
                `id`              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `event`           VARCHAR(50)         NOT NULL,
                `conversation_id` BIGINT(20) UNSIGNED NOT NULL,
                `payload`         MEDIUMTEXT          NOT NULL,
                `created_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_conv_created` (`conversation_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }
}