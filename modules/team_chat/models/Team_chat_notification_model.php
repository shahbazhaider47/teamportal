<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team_chat_notification_model extends App_Model
{
    const FEATURE_MENTION = 'chat_mention';
    const FEATURE_MESSAGE = 'chat_message';
    const FEATURE_ADDED = 'chat_member_added';

    public function process_mentions($body, $message_id, $conversation_id, $sender_id)
    {
        if (!$this->db->table_exists('notifications')) return;
        $mentions = $this->extract_mentions($body);
        if (!$mentions) return;
        $users = $this->resolve_mentions($mentions, $conversation_id, $sender_id);
        foreach ($users as $user) {
            $this->insert_notification((int)$user['id'], $sender_id, self::FEATURE_MENTION, 'You were mentioned in Team Chat', site_url('team_chat/conversation/' . (int)$conversation_id . '?msg=' . (int)$message_id));
        }
    }

    public function notify_member_added($conversation_id, $user_id, $added_by)
    {
        if ($this->db->table_exists('notifications')) {
            $this->insert_notification($user_id, $added_by, self::FEATURE_ADDED, 'You were added to a Team Chat conversation', site_url('team_chat/conversation/' . (int)$conversation_id));
        }
    }

    private function extract_mentions($body)
    {
        preg_match_all('/@([a-zA-Z0-9._-]+)/', (string)$body, $matches);
        return array_values(array_unique(array_map('strtolower', $matches[1] ?? [])));
    }

    private function resolve_mentions(array $mentions, $conversation_id, $sender_id)
    {
        if (!$mentions) return [];
        $this->db->select('u.id, u.username')->from('users u')->join('chat_members mem', 'mem.user_id = u.id AND mem.conversation_id = ' . (int)$conversation_id . ' AND mem.left_at IS NULL', 'inner');
        $this->db->where('u.is_active', 1)->where('u.id !=', (int)$sender_id)->where_in('LOWER(u.username)', $mentions, false);
        return $this->db->get()->result_array();
    }

    private function insert_notification($user_id, $sender_id, $feature, $text, $url)
    {
        $data = ['user_id'=>(int)$user_id, 'sender_id'=>(int)$sender_id, 'feature_key'=>$feature, 'short_text'=>$text, 'full_text'=>$text, 'action_url'=>$url, 'is_read'=>0, 'created_at'=>date('Y-m-d H:i:s')];
        if ($this->db->field_exists('email_sent', 'notifications')) $data['email_sent'] = 0;
        $this->db->insert('notifications', $data);
    }
}
