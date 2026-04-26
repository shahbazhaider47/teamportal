<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends CI_Model
{
    protected $table = 'notifications';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Insert a new notification.
     *
     * @param  array $data  [
     *     'user_id'     => int,             // required
     *     'feature_key' => string,          // required, e.g. 'support'
     *     'short_text'  => string,          // required (title)
     *     'full_text'   => string,          // required (body, plain text)
     *     'action_url'  => string|null,     // optional absolute URL
     *     'email_sent'  => 0|1              // optional, will be set automatically when emailing
     * ]
     * @return int Inserted notification ID (0 if fully disabled)
     */
    public function insert(array $data): int
    {
        // Read requested channels from caller; preserve current default
        $channels = $data['_channels'] ?? ['in_app', 'email'];
        unset($data['_channels']);
    
        $settingsInApp  = get_setting('notifications_in_app_enabled', '1') === '1';
        $settingsEmail  = get_setting('notifications_email_enabled', '1') === '1';
    
        // Caller intent ∧ system setting
        $inAppEnabled = $settingsInApp && in_array('in_app', $channels, true);
        $emailEnabled = $settingsEmail && in_array('email', $channels, true);
    
        if (!$inAppEnabled && !$emailEnabled) return 0;
    
        $row = [
            'user_id'     => (int)($data['user_id'] ?? 0),
            'feature_key' => (string)($data['feature_key'] ?? 'general'),
            'short_text'  => (string)($data['short_text'] ?? ''),
            'full_text'   => (string)($data['full_text'] ?? ''),
            'action_url'  => isset($data['action_url']) ? (string)$data['action_url'] : null,
            'is_read'     => 0,
            'email_sent'  => 0,
            'sender_id'   => $this->session->userdata('user_id') ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
        ];
    
        $notifId = 0;
    
        if ($inAppEnabled) {
        $this->db->insert($this->table, $row);
        $notifId = (int)$this->db->insert_id();

        if ($notifId > 0) {
            // Build payload for realtime
            $payload = [
                'id'          => $notifId,
                'user_id'     => $row['user_id'],
                'title'       => $row['short_text'],
                'description' => $row['full_text'],
                'link'        => $row['action_url'],
                'icon'        => '', // or derive from feature_key if you want
                'date'        => $row['created_at'],
                'is_read'     => $row['is_read'],
            ];

            $CI =& get_instance();
            $CI->load->library('Realtime_notifications');
            $CI->realtime_notifications->push($payload);
        }
    }
    
        if ($emailEnabled) {
            $sent = $this->_send_email_notification($row);
            if ($sent && $notifId) {
                $this->db->where('id', $notifId)->update($this->table, ['email_sent' => 1]);
            }
        }
    
        return $notifId;
    }
    
    /**
     * Fetch unread notifications for a user (newest first).
     */
    public function get_unread(int $userId, int $limit = 10): array
    {
        return $this->db
            ->from($this->table)
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Count unread notifications for a user.
     */
    public function count_unread(int $userId): int
    {
        return (int)$this->db
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count_all_results($this->table);
    }

    /**
     * Mark one or many notifications as read.
     */
    public function mark_read($ids): bool
    {
        if (empty($ids)) return false;

        if (is_array($ids)) {
            return $this->db->where_in('id', $ids)->update($this->table, ['is_read' => 1]) !== false;
        }
        return $this->db->where('id', (int)$ids)->update($this->table, ['is_read' => 1]) !== false;
    }

    /**
     * Fetch all notifications for a user with optional filters.
     */
    public function get_all(int $userId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->db->from($this->table)->where('user_id', $userId);

        if (isset($filters['is_read'])) {
            $qb->where('is_read', (int)$filters['is_read']);
        }
        if (!empty($filters['feature_key'])) {
            $qb->where('feature_key', $filters['feature_key']);
        }
        if (!empty($filters['date_from'])) {
            $qb->where('created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $qb->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        return $qb->order_by('created_at', 'DESC')
                  ->limit($limit, $offset)
                  ->get()
                  ->result_array();
    }

    /**
     * Send email notification (plain text). Returns bool.
     */
    private function _send_email_notification(array $data): bool
    {
        $this->load->library('email');
        $this->load->model('User_model');

        $user = $this->User_model->get_user_by_id((int)$data['user_id']);
        if (!$user || empty($user['email'])) {
            return false;
        }

        $this->email->from('admin@linqer.me', 'Notification System');
        $this->email->to($user['email']);
        $this->email->subject($data['short_text']);
        $body = $data['full_text'];
        if (!empty($data['action_url'])) {
            $body .= "\n\nOpen: " . $data['action_url'];
        }
        $this->email->message($body);

        return (bool)@$this->email->send(); // suppress noisy errors
    }

    public function clear_all_for_user($user_id)
    {
        $this->db->where('user_id', (int)$user_id)->delete($this->table);
    }


    /**
     * Convenience method used by other modules (e.g., reminders).
     *
     * $data = [
     *   'user_id'    => int,
     *   'sender_id'  => int|null,     // optional
     *   'short_text' => string,
     *   'full_text'  => string,
     *   'feature_key'=> string|null,  // default 'reminders'
     *   'action_url' => string|null,  // optional
     *   '_channels'  => ['in_app','email']|[...] // optional, same as insert()
     * ]
     *
     * Returns: bool success
     */
    public function add($data): bool
    {
        // Normalize to the signature expected by insert()
        $payload = [
            'user_id'     => (int)($data['user_id'] ?? 0),
            'feature_key' => $data['feature_key'] ?? 'reminders',
            'short_text'  => (string)($data['short_text'] ?? ''),
            'full_text'   => (string)($data['full_text'] ?? ''),
            'action_url'  => isset($data['action_url']) ? (string)$data['action_url'] : null,
        ];

        // Pass through channels if provided (e.g. ['in_app'] or ['email'])
        if (isset($data['_channels'])) {
            $payload['_channels'] = $data['_channels'];
        }

        // Let the main insert() handle:
        // - in_app vs email logic
        // - DB insert
        // - email sending
        // - realtime Pusher push (your new logic)
        $id = $this->insert($payload);

        // For callers expecting "true/false", convert id to bool
        return $id > 0;
    }



}
