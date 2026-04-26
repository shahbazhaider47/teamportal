<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat_conversation_model
 * Handles all conversation and membership operations.
 */
class Team_chat_conversation_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // CONVERSATIONS — READ
    // =========================================================

    /**
     * Returns all conversations the user belongs to,
     * ordered by last activity. Includes unread count per conversation.
     */
    public function get_user_conversations($user_id)
    {
        $user_id = (int)$user_id;

        $this->db->select('
            c.id,
            c.type,
            c.name,
            c.slug,
            c.description,
            c.avatar,
            c.team_id,
            c.department_id,
            c.is_archived,
            c.is_read_only,
            c.last_message_id,
            c.last_activity_at,
            c.created_by,
            mem.role,
            mem.is_muted,
            mem.last_read_message_id,
            mem.last_read_at,
            (
                SELECT COUNT(cm2.id)
                FROM chat_messages cm2
                WHERE cm2.conversation_id = c.id
                  AND cm2.id > COALESCE(mem.last_read_message_id, 0)
                  AND cm2.sender_id != ' . $user_id . '
                  AND cm2.is_deleted = 0
            ) AS unread_count,
            lm.body        AS last_message_body,
            lm.type        AS last_message_type,
            lm.sender_id   AS last_message_sender_id,
            lm.created_at  AS last_message_at,
            lu.fullname    AS last_message_sender_name
        ', false);

        $this->db->from('chat_conversations c');
        $this->db->join('chat_members mem',
            'mem.conversation_id = c.id AND mem.user_id = ' . $user_id, 'inner');
        $this->db->join('chat_messages lm',
            'lm.id = c.last_message_id AND lm.is_deleted = 0', 'left');
        $this->db->join('users lu',
            'lu.id = lm.sender_id', 'left');
        $this->db->where('mem.left_at IS NULL', null, false);
        $this->db->order_by('c.last_activity_at', 'DESC');

        $conversations = $this->db->get()->result_array();

        // Attach peer info for direct conversations
        foreach ($conversations as &$conv) {
            $conv['unread_count'] = (int)$conv['unread_count'];

            if ($conv['type'] === 'direct') {
                $conv['peer'] = $this->_get_direct_peer($conv['id'], $user_id);
            } else {
                $conv['peer'] = null;
            }

            // Truncate last message preview
            if (!empty($conv['last_message_body'])) {
                $conv['last_message_preview'] = $this->_preview($conv['last_message_body']);
            } else {
                $conv['last_message_preview'] = '';
            }
        }
        unset($conv);

        return $conversations;
    }

    /**
     * Returns a single conversation with full detail.
     */
    public function get_conversation($conversation_id, $user_id)
    {
        $conversation_id = (int)$conversation_id;
        $user_id         = (int)$user_id;

        $this->db->select('
            c.*,
            mem.role,
            mem.is_muted,
            mem.last_read_message_id,
            mem.joined_at,
            (
                SELECT COUNT(cm2.id)
                FROM chat_messages cm2
                WHERE cm2.conversation_id = c.id
                  AND cm2.id > COALESCE(mem.last_read_message_id, 0)
                  AND cm2.sender_id != ' . $user_id . '
                  AND cm2.is_deleted = 0
            ) AS unread_count,
            creator.fullname AS created_by_name
        ', false);

        $this->db->from('chat_conversations c');
        $this->db->join('chat_members mem',
            'mem.conversation_id = c.id AND mem.user_id = ' . $user_id, 'left');
        $this->db->join('users creator',
            'creator.id = c.created_by', 'left');
        $this->db->where('c.id', $conversation_id);

        $conv = $this->db->get()->row_array();

        if (!$conv) {
            return null;
        }

        $conv['unread_count'] = (int)$conv['unread_count'];

        if ($conv['type'] === 'direct') {
            $conv['peer'] = $this->_get_direct_peer($conversation_id, $user_id);
        } else {
            $conv['peer'] = null;
        }

        // Attach team/department labels if applicable
        if ($conv['team_id']) {
            $team = $this->db->select('id, name')->where('id', $conv['team_id'])->get('teams')->row_array();
            $conv['team'] = $team;
        }

        if ($conv['department_id']) {
            $dept = $this->db->select('id, name')->where('id', $conv['department_id'])->get('departments')->row_array();
            $conv['department'] = $dept;
        }

        return $conv;
    }

    /**
     * Checks if a user is an active member of a conversation.
     */
    public function is_member($conversation_id, $user_id)
    {
        return (bool)$this->db
            ->where('conversation_id', (int)$conversation_id)
            ->where('user_id', (int)$user_id)
            ->where('left_at IS NULL', null, false)
            ->count_all_results('chat_members');
    }

    /**
     * Returns the member's role in a conversation.
     * Returns null if not a member.
     */
    public function get_member_role($conversation_id, $user_id)
    {
        $row = $this->db->select('role')
                        ->where('conversation_id', (int)$conversation_id)
                        ->where('user_id', (int)$user_id)
                        ->where('left_at IS NULL', null, false)
                        ->get('chat_members')
                        ->row_array();

        return $row ? $row['role'] : null;
    }

    /**
     * Returns all members of a conversation with user details.
     */
    public function get_members($conversation_id)
    {
        $this->db->select('
            mem.id AS member_id,
            mem.user_id,
            mem.role,
            mem.is_muted,
            mem.joined_at,
            mem.last_read_at,
            u.fullname,
            u.firstname,
            u.lastname,
            u.profile_image,
            u.emp_id,
            u.emp_department,
            u.emp_team,
            u.is_online,
            u.last_seen_at
        ');
        $this->db->from('chat_members mem');
        $this->db->join('users u', 'u.id = mem.user_id', 'left');
        $this->db->where('mem.conversation_id', (int)$conversation_id);
        $this->db->where('mem.left_at IS NULL', null, false);
        $this->db->order_by('mem.role', 'ASC');
        $this->db->order_by('u.fullname', 'ASC');

        return $this->db->get()->result_array();
    }

    // =========================================================
    // CONVERSATIONS — WRITE
    // =========================================================

    /**
     * Finds an existing direct conversation between two users
     * or creates one if it doesn't exist.
     */
    public function get_or_create_direct($user_id, $target_user_id)
    {
        $user_id        = (int)$user_id;
        $target_user_id = (int)$target_user_id;

        // Find existing direct conversation between exactly these two users
        $this->db->select('c.id');
        $this->db->from('chat_conversations c');
        $this->db->join('chat_members m1',
            'm1.conversation_id = c.id AND m1.user_id = ' . $user_id . ' AND m1.left_at IS NULL', 'inner');
        $this->db->join('chat_members m2',
            'm2.conversation_id = c.id AND m2.user_id = ' . $target_user_id . ' AND m2.left_at IS NULL', 'inner');
        $this->db->where('c.type', 'direct');
        $this->db->where('c.is_archived', 0);

        // Ensure exactly 2 members
        $this->db->where('(SELECT COUNT(*) FROM chat_members mx 
            WHERE mx.conversation_id = c.id AND mx.left_at IS NULL) = 2', null, false);

        $existing = $this->db->get()->row_array();

        if ($existing) {
            return $this->get_conversation($existing['id'], $user_id);
        }

        // Create new direct conversation
        $conversation_id = $this->create_conversation(
            ['type' => 'direct', 'created_by' => $user_id],
            [$user_id, $target_user_id]
        );

        return $this->get_conversation($conversation_id, $user_id);
    }

    /**
     * Creates a new conversation and adds members.
     * Returns the new conversation ID.
     *
     * @param array  $data       Conversation fields
     * @param array  $member_ids User IDs to add
     * @param string $creator_role Role for the creating user (default: owner)
     */
    public function create_conversation(array $data, array $member_ids = [], $creator_role = 'owner')
    {
        $now = date('Y-m-d H:i:s');

        $insert = [
            'type'             => $data['type']          ?? 'group',
            'name'             => $data['name']          ?? null,
            'slug'             => $data['slug']          ?? null,
            'description'      => $data['description']   ?? null,
            'team_id'          => $data['team_id']       ?? null,
            'department_id'    => $data['department_id'] ?? null,
            'avatar'           => $data['avatar']        ?? null,
            'created_by'       => $data['created_by']    ?? null,
            'is_archived'      => 0,
            'is_read_only'     => $data['is_read_only']  ?? 0,
            'last_activity_at' => $now,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        $this->db->insert('chat_conversations', $insert);
        $conversation_id = $this->db->insert_id();

        if (!$conversation_id) {
            return false;
        }

        // Add members
        $creator_id = (int)($data['created_by'] ?? 0);

        foreach ($member_ids as $uid) {
            $uid  = (int)$uid;
            $role = ($uid === $creator_id) ? $creator_role : 'member';
            $this->add_member($conversation_id, $uid, $creator_id, $role);
        }

        return $conversation_id;
    }

    // =========================================================
    // MEMBERS — WRITE
    // =========================================================

    /**
     * Adds a single member to a conversation.
     * If already a member (left), reinstates them.
     */
    public function add_member($conversation_id, $user_id, $added_by = null, $role = 'member')
    {
        $conversation_id = (int)$conversation_id;
        $user_id         = (int)$user_id;
        $now             = date('Y-m-d H:i:s');

        // Check if a left record exists — reinstate rather than duplicate
        $existing = $this->db->where('conversation_id', $conversation_id)
                             ->where('user_id', $user_id)
                             ->get('chat_members')
                             ->row_array();

        if ($existing) {
            $this->db->where('conversation_id', $conversation_id)
                     ->where('user_id', $user_id)
                     ->update('chat_members', [
                         'left_at'   => null,
                         'role'      => $role,
                         'joined_at' => $now,
                         'added_by'  => $added_by,
                     ]);
        } else {
            $this->db->insert('chat_members', [
                'conversation_id' => $conversation_id,
                'user_id'         => $user_id,
                'role'            => $role,
                'is_muted'        => 0,
                'notify_on_mention' => 1,
                'added_by'        => $added_by,
                'joined_at'       => $now,
                'left_at'         => null,
            ]);
        }

        return $this->db->affected_rows() > 0;
    }

    /**
     * Soft-removes a member from a conversation by setting left_at.
     */
    public function remove_member($conversation_id, $user_id)
    {
        $this->db->where('conversation_id', (int)$conversation_id)
                 ->where('user_id', (int)$user_id)
                 ->update('chat_members', ['left_at' => date('Y-m-d H:i:s')]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Updates last_read_message_id for a user in a conversation
     * to the latest message in that conversation.
     */
    public function mark_as_read($conversation_id, $user_id)
    {
        $conversation_id = (int)$conversation_id;
        $user_id         = (int)$user_id;

        // Get the latest non-deleted message ID
        $latest = $this->db->select_max('id', 'max_id')
                           ->where('conversation_id', $conversation_id)
                           ->where('is_deleted', 0)
                           ->get('chat_messages')
                           ->row_array();

        if (!$latest || !$latest['max_id']) {
            return false;
        }

        $this->db->where('conversation_id', $conversation_id)
                 ->where('user_id', $user_id)
                 ->update('chat_members', [
                     'last_read_message_id' => (int)$latest['max_id'],
                     'last_read_at'         => date('Y-m-d H:i:s'),
                 ]);

        return true;
    }

    // =========================================================
    // INTERNAL HELPERS
    // =========================================================

    /**
     * Returns the other user in a direct conversation.
     */
    private function _get_direct_peer($conversation_id, $user_id)
    {
        $this->db->select('
            u.id,
            u.fullname,
            u.firstname,
            u.lastname,
            u.profile_image,
            u.emp_id,
            u.is_online,
            u.last_seen_at
        ');
        $this->db->from('chat_members mem');
        $this->db->join('users u', 'u.id = mem.user_id', 'inner');
        $this->db->where('mem.conversation_id', (int)$conversation_id);
        $this->db->where('mem.user_id !=', (int)$user_id);
        $this->db->where('mem.left_at IS NULL', null, false);
        $this->db->limit(1);

        return $this->db->get()->row_array();
    }

    /**
     * Returns a short preview of a message body, stripping HTML and mentions.
     */
    private function _preview($body, $length = 80)
    {
        $body = strip_tags($body);
        $body = preg_replace('/@\w+/', '', $body);
        $body = trim($body);

        return mb_strlen($body) > $length
            ? mb_substr($body, 0, $length) . '…'
            : $body;
    }
}