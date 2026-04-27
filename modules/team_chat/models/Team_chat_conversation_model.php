<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team_chat_conversation_model extends App_Model
{
    private $has_is_online;
    private $has_last_seen_at;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('team_chat/team_chat');
        $this->has_is_online = $this->db->field_exists('is_online', 'users');
        $this->has_last_seen_at = $this->db->field_exists('last_seen_at', 'users');
    }

    public function get_user_conversations($user_id)
    {
        $user_id = (int)$user_id;
        $this->db->select('c.*, mem.role, mem.is_muted, mem.last_read_message_id, mem.last_read_at, lm.body AS last_message_body, lm.type AS last_message_type, lm.sender_id AS last_message_sender_id, lm.created_at AS last_message_at', false);
        $this->db->select('(SELECT COUNT(m.id) FROM chat_messages m WHERE m.conversation_id = c.id AND m.id > COALESCE(mem.last_read_message_id,0) AND m.sender_id != ' . $user_id . ' AND m.is_deleted = 0) AS unread_count', false);
        $this->db->from('chat_conversations c');
        $this->db->join('chat_members mem', 'mem.conversation_id = c.id AND mem.user_id = ' . $user_id . ' AND mem.left_at IS NULL', 'inner');
        $this->db->join('chat_messages lm', 'lm.id = c.last_message_id AND lm.is_deleted = 0', 'left');
        $this->db->where('c.is_archived', 0);
        $this->db->order_by('COALESCE(c.last_activity_at, c.created_at)', 'DESC', false);
        $rows = $this->db->get()->result_array();
        foreach ($rows as &$row) {
            $row['unread_count'] = (int)($row['unread_count'] ?? 0);
            $row['peer'] = $row['type'] === 'direct' ? $this->get_direct_peer($row['id'], $user_id) : null;
            $row['display_name'] = team_chat_conversation_display_name($row, $user_id);
            $row['avatar_url'] = team_chat_conversation_avatar($row);
            $row['last_message_preview'] = $this->preview($row['last_message_body'] ?? '');
        }
        unset($row);
        return $rows;
    }

    public function get_conversation($conversation_id, $user_id)
    {
        $conversation_id = (int)$conversation_id;
        $user_id = (int)$user_id;
        $this->db->select('c.*, mem.role, mem.is_muted, mem.last_read_message_id, mem.last_read_at, mem.joined_at', false);
        $this->db->select('(SELECT COUNT(m.id) FROM chat_messages m WHERE m.conversation_id = c.id AND m.id > COALESCE(mem.last_read_message_id,0) AND m.sender_id != ' . $user_id . ' AND m.is_deleted = 0) AS unread_count', false);
        $this->db->from('chat_conversations c');
        $this->db->join('chat_members mem', 'mem.conversation_id = c.id AND mem.user_id = ' . $user_id . ' AND mem.left_at IS NULL', 'left');
        $this->db->where('c.id', $conversation_id);
        $row = $this->db->get()->row_array();
        if (!$row) return null;
        $row['unread_count'] = (int)($row['unread_count'] ?? 0);
        $row['peer'] = $row['type'] === 'direct' ? $this->get_direct_peer($conversation_id, $user_id) : null;
        $row['display_name'] = team_chat_conversation_display_name($row, $user_id);
        $row['avatar_url'] = team_chat_conversation_avatar($row);
        return $row;
    }

    public function is_member($conversation_id, $user_id)
    {
        return (bool)$this->db->where('conversation_id', (int)$conversation_id)->where('user_id', (int)$user_id)->where('left_at IS NULL', null, false)->count_all_results('chat_members');
    }

    public function get_member_role($conversation_id, $user_id)
    {
        $row = $this->db->select('role')->where('conversation_id', (int)$conversation_id)->where('user_id', (int)$user_id)->where('left_at IS NULL', null, false)->get('chat_members')->row_array();
        return $row['role'] ?? null;
    }

    public function get_members($conversation_id)
    {
        $select = 'mem.id AS member_id, mem.user_id, mem.role, mem.is_muted, mem.joined_at, mem.last_read_at, u.id, u.firstname, u.lastname, u.fullname, u.username, u.email, u.profile_image, u.emp_id, u.emp_department, u.emp_team';
        $select .= $this->has_is_online ? ', u.is_online' : ', 0 AS is_online';
        $select .= $this->has_last_seen_at ? ', u.last_seen_at' : ', NULL AS last_seen_at';
        $this->db->select($select, false)->from('chat_members mem')->join('users u', 'u.id = mem.user_id', 'left');
        $this->db->where('mem.conversation_id', (int)$conversation_id)->where('mem.left_at IS NULL', null, false);
        $this->db->order_by("FIELD(mem.role,'owner','admin','member')", '', false)->order_by('u.firstname', 'ASC')->order_by('u.lastname', 'ASC');
        $rows = $this->db->get()->result_array();
        foreach ($rows as &$row) $row = $this->normalize_user($row);
        unset($row);
        return $rows;
    }

    public function get_or_create_direct($user_id, $target_user_id)
    {
        $user_id = (int)$user_id; $target_user_id = (int)$target_user_id;
        $this->db->select('c.id')->from('chat_conversations c');
        $this->db->join('chat_members a', 'a.conversation_id = c.id AND a.user_id = ' . $user_id . ' AND a.left_at IS NULL', 'inner');
        $this->db->join('chat_members b', 'b.conversation_id = c.id AND b.user_id = ' . $target_user_id . ' AND b.left_at IS NULL', 'inner');
        $this->db->where('c.type', 'direct')->where('c.is_archived', 0);
        $this->db->where('(SELECT COUNT(*) FROM chat_members x WHERE x.conversation_id = c.id AND x.left_at IS NULL) = 2', null, false);
        $existing = $this->db->get()->row_array();
        if ($existing) return $this->get_conversation($existing['id'], $user_id);
        $id = $this->create_conversation(['type' => 'direct', 'created_by' => $user_id], [$user_id, $target_user_id]);
        return $id ? $this->get_conversation($id, $user_id) : null;
    }

    public function create_conversation(array $data, array $member_ids, $creator_role = 'owner')
    {
        $now = date('Y-m-d H:i:s');
        $insert = [
            'type' => $data['type'] ?? 'group', 'name' => $data['name'] ?? null, 'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null, 'team_id' => $data['team_id'] ?? null, 'department_id' => $data['department_id'] ?? null,
            'created_by' => $data['created_by'] ?? null, 'is_archived' => 0, 'is_read_only' => $data['is_read_only'] ?? 0,
            'last_activity_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ];
        $this->db->trans_begin();
        $this->db->insert('chat_conversations', $insert);
        $conversation_id = (int)$this->db->insert_id();
        $creator_id = (int)($data['created_by'] ?? 0);
        foreach (array_unique(array_filter(array_map('intval', $member_ids))) as $uid) {
            $this->add_member($conversation_id, $uid, $creator_id, $uid === $creator_id ? $creator_role : 'member');
        }
        if ($this->db->trans_status() === false || !$conversation_id) { $this->db->trans_rollback(); return false; }
        $this->db->trans_commit();
        return $conversation_id;
    }

    public function add_member($conversation_id, $user_id, $added_by = null, $role = 'member')
    {
        $conversation_id = (int)$conversation_id; $user_id = (int)$user_id; $now = date('Y-m-d H:i:s');
        $existing = $this->db->where('conversation_id', $conversation_id)->where('user_id', $user_id)->get('chat_members')->row_array();
        $data = ['role' => in_array($role, ['owner','admin','member'], true) ? $role : 'member', 'added_by' => $added_by, 'joined_at' => $now, 'left_at' => null];
        if ($existing) return $this->db->where('id', $existing['id'])->update('chat_members', $data);
        $data += ['conversation_id' => $conversation_id, 'user_id' => $user_id, 'is_muted' => 0, 'notify_on_mention' => 1];
        return $this->db->insert('chat_members', $data);
    }

    public function remove_member($conversation_id, $user_id)
    {
        return $this->db->where('conversation_id', (int)$conversation_id)->where('user_id', (int)$user_id)->update('chat_members', ['left_at' => date('Y-m-d H:i:s')]);
    }

    public function mark_as_read($conversation_id, $user_id)
    {
        $row = $this->db->select_max('id', 'id')->where('conversation_id', (int)$conversation_id)->where('is_deleted', 0)->get('chat_messages')->row_array();
        $last_id = (int)($row['id'] ?? 0);
        return $this->db->where('conversation_id', (int)$conversation_id)->where('user_id', (int)$user_id)->update('chat_members', ['last_read_message_id' => $last_id, 'last_read_at' => date('Y-m-d H:i:s')]);
    }

    private function get_direct_peer($conversation_id, $user_id)
    {
        $select = 'u.id, u.firstname, u.lastname, u.fullname, u.username, u.email, u.profile_image, u.emp_id';
        $select .= $this->has_is_online ? ', u.is_online' : ', 0 AS is_online';
        $select .= $this->has_last_seen_at ? ', u.last_seen_at' : ', NULL AS last_seen_at';
        $row = $this->db->select($select, false)->from('chat_members mem')->join('users u', 'u.id = mem.user_id', 'inner')->where('mem.conversation_id', (int)$conversation_id)->where('mem.user_id !=', (int)$user_id)->where('mem.left_at IS NULL', null, false)->limit(1)->get()->row_array();
        return $row ? $this->normalize_user($row) : null;
    }

    private function normalize_user(array $row)
    {
        $row['fullname'] = team_chat_user_display_name($row);
        $row['avatar_url'] = team_chat_user_avatar_url($row['profile_image'] ?? '', $row['fullname']);
        $row['is_online'] = !empty($row['is_online']);
        return $row;
    }

    private function preview($body)
    {
        $body = trim(preg_replace('/\s+/', ' ', strip_tags((string)$body)));
        return strlen($body) > 80 ? substr($body, 0, 77) . '...' : $body;
    }
}
