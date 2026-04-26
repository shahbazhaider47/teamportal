<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat_notification_model
 * Handles @mention parsing and notification dispatch,
 * using the existing notifications table in the HRM.
 */
class Team_chat_notification_model extends App_Model
{
    /**
     * Feature key used in the notifications table.
     * Matches the feature_key column convention in the existing system.
     */
    const FEATURE_KEY_MENTION  = 'chat_mention';
    const FEATURE_KEY_MESSAGE  = 'chat_message';
    const FEATURE_KEY_ADDED    = 'chat_member_added';

    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================
    // MENTION PROCESSING
    // =========================================================

    /**
     * Parses @mentions in a message body and fires a notification
     * for each mentioned user who is a member of the conversation.
     *
     * @param string $body             Raw message body
     * @param int    $message_id
     * @param int    $conversation_id
     * @param int    $sender_id        The user who sent the message
     */
    public function process_mentions($body, $message_id, $conversation_id, $sender_id)
    {
        $mentioned_usernames = $this->_extract_mentions($body);

        if (empty($mentioned_usernames)) {
            return;
        }

        // Resolve usernames to user IDs (only active users who are members)
        $mentioned_users = $this->_resolve_usernames(
            $mentioned_usernames,
            $conversation_id,
            $sender_id
        );

        if (empty($mentioned_users)) {
            return;
        }

        // Get sender info for the notification text
        $sender = $this->db->select('fullname, profile_image')
                           ->where('id', (int)$sender_id)
                           ->get('users')
                           ->row_array();

        $sender_name = $sender['fullname'] ?? 'Someone';

        // Get conversation info
        $conv = $this->db->select('id, type, name')
                         ->where('id', (int)$conversation_id)
                         ->get('chat_conversations')
                         ->row_array();

        $conv_label = $this->_conversation_label($conv);

        // Build notification texts
        $short_text = $sender_name . ' mentioned you in ' . $conv_label;
        $full_text  = $sender_name . ' mentioned you in ' . $conv_label . ': "' . $this->_preview($body) . '"';
        $action_url = site_url('team_chat/conversation/' . $conversation_id . '?msg=' . $message_id);

        foreach ($mentioned_users as $user) {
            $uid = (int)$user['id'];

            // Don't notify the sender themselves
            if ($uid === (int)$sender_id) {
                continue;
            }

            // Check user has not muted this conversation
            if ($this->_is_muted($conversation_id, $uid)) {
                continue;
            }

            $this->_insert_notification(
                $uid,
                $sender_id,
                self::FEATURE_KEY_MENTION,
                $short_text,
                $full_text,
                $action_url
            );
        }
    }

    /**
     * Sends a "new message" notification to all conversation members
     * who have not read up to this message and have not muted the conversation.
     * Used for direct messages or when polling is the delivery mechanism.
     *
     * @param int $message_id
     * @param int $conversation_id
     * @param int $sender_id
     */
    public function notify_new_message($message_id, $conversation_id, $sender_id)
    {
        $sender = $this->db->select('fullname')
                           ->where('id', (int)$sender_id)
                           ->get('users')
                           ->row_array();

        $sender_name = $sender['fullname'] ?? 'Someone';

        $conv = $this->db->select('id, type, name')
                         ->where('id', (int)$conversation_id)
                         ->get('chat_conversations')
                         ->row_array();

        $conv_label = $this->_conversation_label($conv);
        $short_text = 'New message from ' . $sender_name . ' in ' . $conv_label;
        $full_text  = $short_text;
        $action_url = site_url('team_chat/conversation/' . $conversation_id);

        // Get all active non-muted members except sender
        $members = $this->db->select('user_id')
                            ->where('conversation_id', (int)$conversation_id)
                            ->where('user_id !=', (int)$sender_id)
                            ->where('is_muted', 0)
                            ->where('left_at IS NULL', null, false)
                            ->get('chat_members')
                            ->result_array();

        foreach ($members as $member) {
            $this->_insert_notification(
                (int)$member['user_id'],
                $sender_id,
                self::FEATURE_KEY_MESSAGE,
                $short_text,
                $full_text,
                $action_url
            );
        }
    }

    /**
     * Notifies a user that they were added to a conversation.
     *
     * @param int $conversation_id
     * @param int $added_user_id    The user being added
     * @param int $added_by_id      The user doing the adding
     */
    public function notify_member_added($conversation_id, $added_user_id, $added_by_id)
    {
        $adder = $this->db->select('fullname')
                          ->where('id', (int)$added_by_id)
                          ->get('users')
                          ->row_array();

        $adder_name = $adder['fullname'] ?? 'Someone';

        $conv = $this->db->select('id, type, name')
                         ->where('id', (int)$conversation_id)
                         ->get('chat_conversations')
                         ->row_array();

        $conv_label = $this->_conversation_label($conv);
        $short_text = $adder_name . ' added you to ' . $conv_label;
        $full_text  = $short_text;
        $action_url = site_url('team_chat/conversation/' . $conversation_id);

        $this->_insert_notification(
            (int)$added_user_id,
            $added_by_id,
            self::FEATURE_KEY_ADDED,
            $short_text,
            $full_text,
            $action_url
        );
    }

    // =========================================================
    // READ — Unread Notification Counts
    // =========================================================

    /**
     * Returns the count of unread chat-related notifications for a user.
     *
     * @param int $user_id
     */
    public function get_unread_count($user_id)
    {
        return (int)$this->db
            ->where('user_id', (int)$user_id)
            ->where('is_read', 0)
            ->where_in('feature_key', [
                self::FEATURE_KEY_MENTION,
                self::FEATURE_KEY_MESSAGE,
                self::FEATURE_KEY_ADDED,
            ])
            ->count_all_results('notifications');
    }

    /**
     * Returns recent chat notifications for a user.
     *
     * @param int $user_id
     * @param int $limit
     */
    public function get_recent($user_id, $limit = 20)
    {
        return $this->db->select('
            n.*,
            u.fullname  AS sender_name,
            u.profile_image AS sender_avatar
        ')
        ->from('notifications n')
        ->join('users u', 'u.id = n.sender_id', 'left')
        ->where('n.user_id', (int)$user_id)
        ->where_in('n.feature_key', [
            self::FEATURE_KEY_MENTION,
            self::FEATURE_KEY_MESSAGE,
            self::FEATURE_KEY_ADDED,
        ])
        ->order_by('n.created_at', 'DESC')
        ->limit($limit)
        ->get()
        ->result_array();
    }

    /**
     * Marks all chat notifications for a user as read.
     *
     * @param int $user_id
     */
    public function mark_all_read($user_id)
    {
        $this->db->where('user_id', (int)$user_id)
                 ->where_in('feature_key', [
                     self::FEATURE_KEY_MENTION,
                     self::FEATURE_KEY_MESSAGE,
                     self::FEATURE_KEY_ADDED,
                 ])
                 ->update('notifications', ['is_read' => 1]);
    }

    /**
     * Marks a single notification as read.
     *
     * @param int $notification_id
     * @param int $user_id          Safety check — must own the notification
     */
    public function mark_read($notification_id, $user_id)
    {
        $this->db->where('id', (int)$notification_id)
                 ->where('user_id', (int)$user_id)
                 ->update('notifications', ['is_read' => 1]);
    }

    // =========================================================
    // INTERNAL HELPERS
    // =========================================================

    /**
     * Extracts @mention usernames from a message body.
     * Supports @username and @first.last formats.
     *
     * @param string $body
     * @return array  Array of lowercase usernames without the @ symbol
     */
    private function _extract_mentions($body)
    {
        // Match @word, @word.word, @word_word (no spaces in username)
        preg_match_all('/@([a-zA-Z0-9._-]+)/', $body, $matches);

        if (empty($matches[1])) {
            return [];
        }

        return array_unique(array_map('strtolower', $matches[1]));
    }

    /**
     * Resolves an array of usernames to their user records.
     * Only returns users who are active members of the conversation
     * (excluding the sender to prevent self-notification).
     *
     * @param array $usernames
     * @param int   $conversation_id
     * @param int   $sender_id
     * @return array
     */
    private function _resolve_usernames(array $usernames, $conversation_id, $sender_id)
    {
        if (empty($usernames)) {
            return [];
        }

        // Lowercase all for comparison
        $usernames = array_map('strtolower', $usernames);

        $this->db->select('u.id, u.username, u.fullname');
        $this->db->from('users u');
        $this->db->join('chat_members mem',
            'mem.user_id = u.id
             AND mem.conversation_id = ' . (int)$conversation_id . '
             AND mem.left_at IS NULL', 'inner');
        $this->db->where('u.is_active', 1);
        $this->db->where('u.id !=', (int)$sender_id);

        // Build OR conditions for each username
        $this->db->group_start();
        foreach ($usernames as $i => $uname) {
            $method = $i === 0 ? 'where' : 'or_where';
            $this->db->$method('LOWER(u.username)', $uname);
        }
        $this->db->group_end();

        return $this->db->get()->result_array();
    }

    /**
     * Returns a human-readable conversation label.
     */
    private function _conversation_label(array $conv)
    {
        if ($conv['type'] === 'direct') {
            return 'a direct message';
        }

        if ($conv['type'] === 'channel') {
            return '#' . ($conv['name'] ?? 'channel');
        }

        return '"' . ($conv['name'] ?? 'a group') . '"';
    }

    /**
     * Checks if a user has muted a conversation.
     */
    private function _is_muted($conversation_id, $user_id)
    {
        $row = $this->db->select('is_muted')
                        ->where('conversation_id', (int)$conversation_id)
                        ->where('user_id', (int)$user_id)
                        ->get('chat_members')
                        ->row_array();

        return $row ? (bool)$row['is_muted'] : false;
    }

    /**
     * Inserts a notification into the existing notifications table.
     * Follows the same schema as all other modules in the HRM.
     */
    private function _insert_notification($user_id, $sender_id, $feature_key, $short_text, $full_text, $action_url)
    {
        $this->db->insert('notifications', [
            'user_id'     => (int)$user_id,
            'sender_id'   => (int)$sender_id,
            'feature_key' => $feature_key,
            'short_text'  => mb_substr($short_text, 0, 191),
            'full_text'   => $full_text,
            'action_url'  => $action_url,
            'is_read'     => 0,
            'email_sent'  => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Returns a short preview of a message body for notification text.
     */
    private function _preview($body, $length = 60)
    {
        $body = strip_tags($body);
        $body = preg_replace('/@\w+/', '', $body);
        $body = trim($body);

        return mb_strlen($body) > $length
            ? mb_substr($body, 0, $length) . '…'
            : $body;
    }
}