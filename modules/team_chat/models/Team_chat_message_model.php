<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat_message_model
 * Handles all message operations: send, fetch, search,
 * threads, soft-delete, and pinned messages.
 */
class Team_chat_message_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // FETCH
    // =========================================================

    /**
     * Returns paginated messages for a conversation.
     * Loads newest-first when no before_id given, oldest-first within set.
     *
     * @param int $conversation_id
     * @param int $user_id         Used to attach reaction/read state
     * @param int $before_id       Load messages older than this ID (pagination)
     * @param int $limit
     */
    public function get_messages($conversation_id, $user_id, $before_id = 0, $limit = 50)
    {
        $conversation_id = (int)$conversation_id;
        $user_id         = (int)$user_id;
        $limit           = min((int)$limit, 100);

        $this->db->select('
            cm.id,
            cm.conversation_id,
            cm.sender_id,
            cm.parent_id,
            cm.thread_reply_count,
            cm.type,
            cm.body,
            cm.metadata,
            cm.is_edited,
            cm.edited_at,
            cm.is_deleted,
            cm.deleted_at,
            cm.created_at,
            cm.updated_at,
            u.fullname       AS sender_name,
            u.firstname      AS sender_firstname,
            u.lastname       AS sender_lastname,
            u.profile_image  AS sender_avatar,
            u.emp_id         AS sender_emp_id
        ', false);

        $this->db->from('chat_messages cm');
        $this->db->join('users u', 'u.id = cm.sender_id', 'left');
        $this->db->where('cm.conversation_id', $conversation_id);
        $this->db->where('cm.parent_id IS NULL', null, false); // Top-level only; threads loaded separately

        if ($before_id) {
            $this->db->where('cm.id <', (int)$before_id);
        }

        $this->db->order_by('cm.id', 'DESC');
        $this->db->limit($limit);

        $messages = $this->db->get()->result_array();

        // Reverse so oldest is first in the returned set
        $messages = array_reverse($messages);

        // Hydrate each message with reactions and attachments
        foreach ($messages as &$msg) {
            $msg = $this->_hydrate($msg, $user_id);
        }
        unset($msg);

        return $messages;
    }

    /**
     * Returns a single message by ID with full hydration.
     */
    public function get_message($message_id, $user_id)
    {
        $this->db->select('
            cm.id,
            cm.conversation_id,
            cm.sender_id,
            cm.parent_id,
            cm.thread_reply_count,
            cm.type,
            cm.body,
            cm.metadata,
            cm.is_edited,
            cm.edited_at,
            cm.is_deleted,
            cm.deleted_at,
            cm.created_at,
            cm.updated_at,
            u.fullname       AS sender_name,
            u.firstname      AS sender_firstname,
            u.lastname       AS sender_lastname,
            u.profile_image  AS sender_avatar,
            u.emp_id         AS sender_emp_id
        ', false);

        $this->db->from('chat_messages cm');
        $this->db->join('users u', 'u.id = cm.sender_id', 'left');
        $this->db->where('cm.id', (int)$message_id);

        $msg = $this->db->get()->row_array();

        if (!$msg) {
            return null;
        }

        return $this->_hydrate($msg, (int)$user_id);
    }

    /**
     * Returns all thread replies for a parent message.
     */
    public function get_thread_replies($parent_id, $user_id)
    {
        $parent_id = (int)$parent_id;
        $user_id   = (int)$user_id;

        $this->db->select('
            cm.id,
            cm.conversation_id,
            cm.sender_id,
            cm.parent_id,
            cm.type,
            cm.body,
            cm.metadata,
            cm.is_edited,
            cm.edited_at,
            cm.is_deleted,
            cm.deleted_at,
            cm.created_at,
            cm.updated_at,
            u.fullname       AS sender_name,
            u.firstname      AS sender_firstname,
            u.lastname       AS sender_lastname,
            u.profile_image  AS sender_avatar,
            u.emp_id         AS sender_emp_id
        ', false);

        $this->db->from('chat_messages cm');
        $this->db->join('users u', 'u.id = cm.sender_id', 'left');
        $this->db->where('cm.parent_id', $parent_id);
        $this->db->order_by('cm.id', 'ASC');

        $replies = $this->db->get()->result_array();

        foreach ($replies as &$msg) {
            $msg = $this->_hydrate($msg, $user_id);
        }
        unset($msg);

        return $replies;
    }

    /**
     * Returns all pinned messages for a conversation.
     */
    public function get_pinned($conversation_id)
    {
        $conversation_id = (int)$conversation_id;

        $this->db->select('
            p.id       AS pin_id,
            p.pinned_by,
            p.pinned_at,
            pu.fullname AS pinned_by_name,
            cm.id,
            cm.conversation_id,
            cm.sender_id,
            cm.type,
            cm.body,
            cm.metadata,
            cm.is_edited,
            cm.is_deleted,
            cm.created_at,
            u.fullname       AS sender_name,
            u.profile_image  AS sender_avatar
        ', false);

        $this->db->from('chat_pins p');
        $this->db->join('chat_messages cm', 'cm.id = p.message_id', 'inner');
        $this->db->join('users u',  'u.id = cm.sender_id', 'left');
        $this->db->join('users pu', 'pu.id = p.pinned_by', 'left');
        $this->db->where('p.conversation_id', $conversation_id);
        $this->db->where('cm.is_deleted', 0);
        $this->db->order_by('p.pinned_at', 'DESC');

        return $this->db->get()->result_array();
    }

    // =========================================================
    // SEARCH
    // =========================================================

    /**
     * Full-text search across messages the user has access to.
     *
     * @param string $query
     * @param int    $user_id
     * @param int    $conversation_id  Optional — restrict to one conversation
     * @param int    $limit
     */
    public function search($query, $user_id, $conversation_id = 0, $limit = 30)
    {
        $user_id         = (int)$user_id;
        $conversation_id = (int)$conversation_id;
        $query           = $this->db->escape_like_str($query);

        $this->db->select('
            cm.id,
            cm.conversation_id,
            cm.sender_id,
            cm.parent_id,
            cm.type,
            cm.body,
            cm.is_edited,
            cm.is_deleted,
            cm.created_at,
            u.fullname      AS sender_name,
            u.profile_image AS sender_avatar,
            c.name          AS conversation_name,
            c.type          AS conversation_type
        ', false);

        $this->db->from('chat_messages cm');
        $this->db->join('chat_members mem',
            'mem.conversation_id = cm.conversation_id
             AND mem.user_id = ' . $user_id . '
             AND mem.left_at IS NULL', 'inner');
        $this->db->join('users u', 'u.id = cm.sender_id', 'left');
        $this->db->join('chat_conversations c', 'c.id = cm.conversation_id', 'left');

        $this->db->where('cm.is_deleted', 0);
        $this->db->like('cm.body', $query);

        if ($conversation_id) {
            $this->db->where('cm.conversation_id', $conversation_id);
        }

        $this->db->order_by('cm.id', 'DESC');
        $this->db->limit($limit);

        $results = $this->db->get()->result_array();

        // Highlight the matched term in body
        foreach ($results as &$row) {
            $row['body_highlighted'] = $this->_highlight($row['body'], $query);
        }
        unset($row);

        return $results;
    }

    // =========================================================
    // WRITE
    // =========================================================

    /**
     * Inserts a new message and updates parent thread_reply_count
     * if this is a thread reply.
     *
     * @param array $data  Keys: conversation_id, sender_id, body, type, parent_id, metadata
     * @return int|false   Inserted message ID or false on failure
     */
    public function send_message(array $data)
    {
        $now = date('Y-m-d H:i:s');

        $insert = [
            'conversation_id'    => (int)$data['conversation_id'],
            'sender_id'          => (int)$data['sender_id'],
            'parent_id'          => isset($data['parent_id']) ? (int)$data['parent_id'] : null,
            'thread_reply_count' => 0,
            'type'               => $data['type'] ?? 'text',
            'body'               => $data['body'] ?? '',
            'metadata'           => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'is_edited'          => 0,
            'is_deleted'         => 0,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $this->db->insert('chat_messages', $insert);
        $message_id = $this->db->insert_id();

        if (!$message_id) {
            return false;
        }

        // If this is a thread reply, increment parent's reply count
        if (!empty($insert['parent_id'])) {
            $this->db->where('id', $insert['parent_id'])
                     ->set('thread_reply_count', 'thread_reply_count + 1', false)
                     ->update('chat_messages');
        }

        return $message_id;
    }

    /**
     * Creates a system-generated message (member joined, left, etc.)
     *
     * @param int    $conversation_id
     * @param int    $sender_id        User who triggered the event
     * @param string $event            e.g. 'member_added', 'member_removed', 'channel_created'
     * @param array  $meta             Extra data stored in metadata JSON
     */
    public function create_system_message($conversation_id, $sender_id, $event, array $meta = [])
    {
        $now = date('Y-m-d H:i:s');

        $meta['event'] = $event;

        // Build a human-readable body for system messages
        $body = $this->_system_message_body($event, $meta);

        $this->db->insert('chat_messages', [
            'conversation_id' => (int)$conversation_id,
            'sender_id'       => (int)$sender_id,
            'parent_id'       => null,
            'type'            => 'system',
            'body'            => $body,
            'metadata'        => json_encode($meta),
            'is_deleted'      => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $message_id = $this->db->insert_id();

        // Update conversation last_activity
        $this->db->where('id', $conversation_id)->update('chat_conversations', [
            'last_message_id'  => $message_id,
            'last_activity_at' => $now,
        ]);

        return $message_id;
    }

    // =========================================================
    // INTERNAL HELPERS
    // =========================================================

    /**
     * Attaches reactions and attachments to a message array.
     * Also parses metadata JSON and formats boolean fields.
     */
    private function _hydrate(array $msg, $user_id)
    {
        $msg['id']                  = (int)$msg['id'];
        $msg['conversation_id']     = (int)$msg['conversation_id'];
        $msg['sender_id']           = (int)$msg['sender_id'];
        $msg['parent_id']           = $msg['parent_id'] ? (int)$msg['parent_id'] : null;
        $msg['thread_reply_count']  = (int)($msg['thread_reply_count'] ?? 0);
        $msg['is_edited']           = (bool)$msg['is_edited'];
        $msg['is_deleted']          = (bool)$msg['is_deleted'];
        $msg['is_mine']             = ((int)$msg['sender_id'] === (int)$user_id);

        // Decode metadata JSON
        if (!empty($msg['metadata']) && is_string($msg['metadata'])) {
            $msg['metadata'] = json_decode($msg['metadata'], true);
        } else {
            $msg['metadata'] = null;
        }

        // If message is soft-deleted, blank the body
        if ($msg['is_deleted']) {
            $msg['body']     = '';
            $msg['metadata'] = null;
        }

        // Reactions grouped by emoji
        $msg['reactions'] = $this->_get_reactions($msg['id'], $user_id);

        // Attachments
        $msg['attachments'] = $this->_get_attachments($msg['id']);

        return $msg;
    }

    /**
     * Returns reactions for a message grouped by emoji,
     * with a flag indicating whether the current user reacted.
     */
    private function _get_reactions($message_id, $user_id)
    {
        $rows = $this->db->select('
            r.emoji,
            COUNT(r.id) AS count,
            MAX(CASE WHEN r.user_id = ' . (int)$user_id . ' THEN 1 ELSE 0 END) AS reacted_by_me,
            GROUP_CONCAT(u.fullname ORDER BY r.created_at ASC SEPARATOR ", ") AS reactor_names
        ', false)
        ->from('chat_reactions r')
        ->join('users u', 'u.id = r.user_id', 'left')
        ->where('r.message_id', (int)$message_id)
        ->group_by('r.emoji')
        ->order_by('r.emoji', 'ASC')
        ->get()
        ->result_array();

        foreach ($rows as &$row) {
            $row['count']         = (int)$row['count'];
            $row['reacted_by_me'] = (bool)$row['reacted_by_me'];
        }
        unset($row);

        return $rows;
    }

    /**
     * Returns attachments for a message.
     */
    private function _get_attachments($message_id)
    {
        return $this->db->select('
            id,
            original_name,
            stored_name,
            file_path,
            mime_type,
            file_size,
            thumbnail_path,
            created_at
        ')
        ->where('message_id', (int)$message_id)
        ->where('is_deleted', 0)
        ->get('chat_attachments')
        ->result_array();
    }

    /**
     * Wraps the matching term in a highlight span for search results.
     */
    private function _highlight($body, $term)
    {
        if (empty($term)) {
            return $body;
        }

        return preg_replace(
            '/(' . preg_quote($term, '/') . ')/iu',
            '<mark>$1</mark>',
            htmlspecialchars($body, ENT_QUOTES)
        );
    }

    /**
     * Builds a human-readable body string for system messages.
     */
    private function _system_message_body($event, array $meta)
    {
        switch ($event) {
            case 'member_added':
                $uid  = $meta['user_id'] ?? 0;
                $user = $this->db->select('fullname')->where('id', $uid)->get('users')->row_array();
                return ($user['fullname'] ?? 'Someone') . ' was added to the conversation.';

            case 'member_removed':
                $uid  = $meta['user_id'] ?? 0;
                $user = $this->db->select('fullname')->where('id', $uid)->get('users')->row_array();
                return ($user['fullname'] ?? 'Someone') . ' was removed from the conversation.';

            case 'member_left':
                $uid  = $meta['user_id'] ?? 0;
                $user = $this->db->select('fullname')->where('id', $uid)->get('users')->row_array();
                return ($user['fullname'] ?? 'Someone') . ' left the conversation.';

            case 'channel_created':
                return 'Channel was created.';

            case 'conversation_renamed':
                $name = $meta['name'] ?? '';
                return 'Conversation renamed to "' . htmlspecialchars($name, ENT_QUOTES) . '".';

            case 'member_role_changed':
                $uid  = $meta['user_id'] ?? 0;
                $role = $meta['role']    ?? '';
                $user = $this->db->select('fullname')->where('id', $uid)->get('users')->row_array();
                return ($user['fullname'] ?? 'Someone') . ' is now ' . ucfirst($role) . '.';

            default:
                return 'Conversation updated.';
        }
    }
}