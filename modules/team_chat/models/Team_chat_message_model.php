<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team_chat_message_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('team_chat/team_chat');
        $this->load->model(['team_chat/Team_chat_reaction_model','team_chat/Team_chat_attachment_model']);
    }

    public function get_messages($conversation_id, $user_id, $before_id = 0, $limit = 50)
    {
        $limit = min(max((int)$limit, 1), 100);
        $this->base_query()->where('cm.conversation_id', (int)$conversation_id)->where('cm.parent_id IS NULL', null, false);
        if ($before_id) $this->db->where('cm.id <', (int)$before_id);
        $rows = $this->db->order_by('cm.id', 'DESC')->limit($limit)->get()->result_array();
        $rows = array_reverse($rows);
        foreach ($rows as &$row) $row = $this->hydrate($row, $user_id);
        return $rows;
    }

    public function get_message($message_id, $user_id)
    {
        $row = $this->base_query()->where('cm.id', (int)$message_id)->get()->row_array();
        return $row ? $this->hydrate($row, $user_id) : null;
    }

    public function get_thread_replies($parent_id, $user_id)
    {
        $rows = $this->base_query()->where('cm.parent_id', (int)$parent_id)->order_by('cm.id', 'ASC')->get()->result_array();
        foreach ($rows as &$row) $row = $this->hydrate($row, $user_id);
        return $rows;
    }

    public function get_pinned($conversation_id, $user_id = 0)
    {
        $rows = $this->base_query()->select('p.id AS pin_id, p.pinned_by, p.pinned_at, pu.fullname AS pinned_by_name', false)
            ->join('chat_pins p', 'p.message_id = cm.id AND p.conversation_id = cm.conversation_id', 'inner')
            ->join('users pu', 'pu.id = p.pinned_by', 'left')
            ->where('p.conversation_id', (int)$conversation_id)->where('cm.is_deleted', 0)->order_by('p.pinned_at', 'DESC')->get()->result_array();
        foreach ($rows as &$row) $row = $this->hydrate($row, $user_id);
        return $rows;
    }

    public function search($query, $user_id, $conversation_id = 0, $limit = 30)
    {
        $query = trim((string)$query);
        if ($query === '') return [];
        $this->base_query()->join('chat_members mem', 'mem.conversation_id = cm.conversation_id AND mem.user_id = ' . (int)$user_id . ' AND mem.left_at IS NULL', 'inner')
            ->where('cm.is_deleted', 0)->like('cm.body', $query)->limit(min((int)$limit, 100))->order_by('cm.id', 'DESC');
        if ($conversation_id) $this->db->where('cm.conversation_id', (int)$conversation_id);
        $rows = $this->db->get()->result_array();
        foreach ($rows as &$row) {
            $row = $this->hydrate($row, $user_id);
            $row['body_highlighted'] = preg_replace('/(' . preg_quote($query, '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($row['body'], ENT_QUOTES, 'UTF-8'));
        }
        return $rows;
    }

    public function send_message(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $insert = [
            'conversation_id' => (int)$data['conversation_id'], 'sender_id' => (int)$data['sender_id'], 'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'thread_reply_count' => 0, 'type' => $data['type'] ?? 'text', 'body' => $data['body'] ?? '', 'metadata' => !empty($data['metadata']) ? json_encode($data['metadata']) : null,
            'is_edited' => 0, 'is_deleted' => 0, 'created_at' => $now, 'updated_at' => $now,
        ];
        $this->db->insert('chat_messages', $insert);
        $id = (int)$this->db->insert_id();
        if ($id && $insert['parent_id']) {
            $this->db->where('id', $insert['parent_id'])->set('thread_reply_count', 'thread_reply_count + 1', false)->update('chat_messages');
        }
        if ($id) {
            $this->db->where('id', $insert['conversation_id'])->update('chat_conversations', ['last_message_id' => $id, 'last_activity_at' => $now, 'updated_at' => $now]);
        }
        return $id ?: false;
    }

    public function create_system_message($conversation_id, $actor_id, $event, array $meta = [])
    {
        $meta['event'] = $event;
        return $this->send_message(['conversation_id' => $conversation_id, 'sender_id' => $actor_id, 'type' => 'system', 'body' => $this->system_body($event, $meta), 'metadata' => $meta]);
    }

    private function base_query()
    {
        $this->db->select('cm.id, cm.conversation_id, cm.sender_id, cm.parent_id, cm.thread_reply_count, cm.type, cm.body, cm.metadata, cm.is_edited, cm.edited_at, cm.is_deleted, cm.deleted_at, cm.created_at, cm.updated_at, u.firstname AS sender_firstname, u.lastname AS sender_lastname, u.fullname AS sender_fullname, u.username AS sender_username, u.email AS sender_email, u.profile_image AS sender_avatar', false);
        $this->db->from('chat_messages cm')->join('users u', 'u.id = cm.sender_id', 'left');
        return $this->db;
    }

    private function hydrate(array $row, $user_id)
    {
        $sender = ['id'=>$row['sender_id'] ?? 0, 'firstname'=>$row['sender_firstname'] ?? '', 'lastname'=>$row['sender_lastname'] ?? '', 'fullname'=>$row['sender_fullname'] ?? '', 'username'=>$row['sender_username'] ?? '', 'email'=>$row['sender_email'] ?? '', 'profile_image'=>$row['sender_avatar'] ?? ''];
        $row['id'] = (int)$row['id']; $row['conversation_id'] = (int)$row['conversation_id']; $row['sender_id'] = (int)$row['sender_id'];
        $row['parent_id'] = $row['parent_id'] ? (int)$row['parent_id'] : null; $row['thread_reply_count'] = (int)($row['thread_reply_count'] ?? 0);
        $row['is_edited'] = (bool)$row['is_edited']; $row['is_deleted'] = (bool)$row['is_deleted']; $row['is_mine'] = (int)$row['sender_id'] === (int)$user_id;
        $row['sender_name'] = team_chat_user_display_name($sender); $row['sender_avatar_url'] = team_chat_user_avatar_url($row['sender_avatar'] ?? '', $row['sender_name']);
        $row['metadata'] = !empty($row['metadata']) ? json_decode($row['metadata'], true) : null;
        if ($row['is_deleted']) { $row['body'] = ''; $row['metadata'] = null; }
        $row['reactions'] = $this->Team_chat_reaction_model->get_for_message($row['id'], $user_id);
        $row['attachments'] = $this->Team_chat_attachment_model->get_for_message($row['id']);
        return $row;
    }

    private function system_body($event, array $meta)
    {
        switch ($event) {
            case 'member_added': return 'A member was added to the conversation.';
            case 'member_removed': return 'A member was removed from the conversation.';
            case 'member_left': return 'A member left the conversation.';
            case 'channel_created': return 'Channel was created.';
            default: return 'Conversation updated.';
        }
    }
}
