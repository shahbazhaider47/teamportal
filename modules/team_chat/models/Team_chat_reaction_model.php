<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team_chat_reaction_model extends App_Model
{
    public function get_for_message($message_id, $user_id)
    {
        $rows = $this->db->select('r.emoji, COUNT(r.id) AS count, MAX(CASE WHEN r.user_id = ' . (int)$user_id . ' THEN 1 ELSE 0 END) AS reacted_by_me, GROUP_CONCAT(COALESCE(NULLIF(u.fullname,""), TRIM(CONCAT(COALESCE(u.firstname,""), " ", COALESCE(u.lastname,""))), u.username) ORDER BY r.created_at SEPARATOR ", ") AS reactor_names', false)
            ->from('chat_reactions r')->join('users u', 'u.id = r.user_id', 'left')->where('r.message_id', (int)$message_id)->group_by('r.emoji')->order_by('MIN(r.created_at)', 'ASC')->get()->result_array();
        foreach ($rows as &$row) { $row['count'] = (int)$row['count']; $row['reacted_by_me'] = (bool)$row['reacted_by_me']; }
        return $rows;
    }

    public function toggle($message_id, $user_id, $emoji)
    {
        $emoji = $this->clean_emoji($emoji);
        if ($emoji === '') return 'invalid';
        $exists = $this->db->where('message_id', (int)$message_id)->where('user_id', (int)$user_id)->where('emoji', $emoji)->count_all_results('chat_reactions');
        if ($exists) { $this->db->where('message_id', (int)$message_id)->where('user_id', (int)$user_id)->where('emoji', $emoji)->delete('chat_reactions'); return 'removed'; }
        $this->db->insert('chat_reactions', ['message_id' => (int)$message_id, 'user_id' => (int)$user_id, 'emoji' => $emoji, 'created_at' => date('Y-m-d H:i:s')]);
        return 'added';
    }

    public function remove_all_for_message($message_id)
    {
        return $this->db->where('message_id', (int)$message_id)->delete('chat_reactions');
    }

    private function clean_emoji($emoji)
    {
        $emoji = trim((string)$emoji);
        return mb_strlen($emoji) <= 10 ? $emoji : '';
    }
}
