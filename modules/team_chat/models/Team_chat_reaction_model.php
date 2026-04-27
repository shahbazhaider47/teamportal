<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat_reaction_model
 * Handles emoji reactions: toggle add/remove, fetch grouped by emoji.
 */
class Team_chat_reaction_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // READ
    // =========================================================

    /**
     * Returns all reactions for a message grouped by emoji.
     * Includes count, reactor names, and whether the current user reacted.
     *
     * @param int $message_id
     * @param int $user_id     Current user — used to flag reacted_by_me
     */
    public function get_for_message($message_id, $user_id)
    {
        $message_id = (int)$message_id;
        $user_id    = (int)$user_id;

        $rows = $this->db->select('
            r.emoji,
            COUNT(r.id) AS count,
            MAX(CASE WHEN r.user_id = ' . $user_id . ' THEN 1 ELSE 0 END) AS reacted_by_me,
            GROUP_CONCAT(u.fullname ORDER BY r.created_at ASC SEPARATOR ", ") AS reactor_names
        ', false)
        ->from('chat_reactions r')
        ->join('users u', 'u.id = r.user_id', 'left')
        ->where('r.message_id', $message_id)
        ->group_by('r.emoji')
        ->order_by('MIN(r.created_at)', 'ASC')
        ->get()
        ->result_array();

        foreach ($rows as &$row) {
            $row['message_id']    = $message_id;
            $row['count']         = (int)$row['count'];
            $row['reacted_by_me'] = (bool)$row['reacted_by_me'];
        }
        unset($row);

        return $rows;
    }

    /**
     * Returns the individual users who reacted with a specific emoji on a message.
     * Used in tooltip/popover: "Ali Hassan, Sara Khan and 3 others".
     *
     * @param int    $message_id
     * @param string $emoji
     */
    public function get_reactors($message_id, $emoji)
    {
        return $this->db->select('
            u.id,
            u.fullname,
            u.profile_image,
            r.created_at AS reacted_at
        ')
        ->from('chat_reactions r')
        ->join('users u', 'u.id = r.user_id', 'left')
        ->where('r.message_id', (int)$message_id)
        ->where('r.emoji', $emoji)
        ->order_by('r.created_at', 'ASC')
        ->get()
        ->result_array();
    }

    /**
     * Checks if a specific user has reacted with a given emoji on a message.
     */
    public function has_reacted($message_id, $user_id, $emoji)
    {
        return (bool)$this->db
            ->where('message_id', (int)$message_id)
            ->where('user_id', (int)$user_id)
            ->where('emoji', $emoji)
            ->count_all_results('chat_reactions');
    }

    // =========================================================
    // WRITE
    // =========================================================

    /**
     * Toggles a reaction: adds if not present, removes if already exists.
     *
     * @param int    $message_id
     * @param int    $user_id
     * @param string $emoji
     * @return string  'added' | 'removed'
     */
    public function toggle($message_id, $user_id, $emoji)
    {
        $message_id = (int)$message_id;
        $user_id    = (int)$user_id;
        $emoji      = trim($emoji);

        if ($this->has_reacted($message_id, $user_id, $emoji)) {
            $this->remove($message_id, $user_id, $emoji);
            return 'removed';
        }

        $this->add($message_id, $user_id, $emoji);
        return 'added';
    }

    /**
     * Adds a reaction. Silently skips if duplicate.
     */
    public function add($message_id, $user_id, $emoji)
    {
        // Use INSERT IGNORE to safely handle race conditions
        $this->db->query('
            INSERT IGNORE INTO chat_reactions (message_id, user_id, emoji, created_at)
            VALUES (?, ?, ?, ?)
        ', [(int)$message_id, (int)$user_id, $emoji, date('Y-m-d H:i:s')]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Removes a specific reaction.
     */
    public function remove($message_id, $user_id, $emoji)
    {
        $this->db->where('message_id', (int)$message_id)
                 ->where('user_id', (int)$user_id)
                 ->where('emoji', $emoji)
                 ->delete('chat_reactions');

        return $this->db->affected_rows() > 0;
    }

    /**
     * Removes all reactions on a message (e.g. when message is deleted).
     */
    public function remove_all_for_message($message_id)
    {
        $this->db->where('message_id', (int)$message_id)->delete('chat_reactions');
        return $this->db->affected_rows();
    }

    /**
     * Returns a summary count of all reactions across a conversation.
     * Useful for stats/admin views.
     *
     * @param int $conversation_id
     */
    public function get_conversation_reaction_stats($conversation_id)
    {
        return $this->db->select('
            r.emoji,
            COUNT(r.id) AS total
        ', false)
        ->from('chat_reactions r')
        ->join('chat_messages cm', 'cm.id = r.message_id', 'inner')
        ->where('cm.conversation_id', (int)$conversation_id)
        ->where('cm.is_deleted', 0)
        ->group_by('r.emoji')
        ->order_by('total', 'DESC')
        ->get()
        ->result_array();
    }
}