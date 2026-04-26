<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity_log_model extends CI_Model
{
    protected $table = 'activity_log';

    /**
     * Add a new log entry.
     *
     * @param array $data Must include: user_id, action, ip_address, user_agent, created_at
     * @return bool
     */
    public function add(array $data): bool
    {
        // Basic validation
        if (empty($data['user_id']) || empty($data['action'])) {
            return false;
        }

        // Optional defaults
        $data['ip_address']  = $data['ip_address']  ?? $this->input->ip_address();
        $data['user_agent']  = $data['user_agent']  ?? substr($this->input->user_agent(), 0, 255);
        $data['created_at']  = $data['created_at']  ?? date('Y-m-d H:i:s');

        return (bool) $this->db->insert($this->table, $data);
    }

    /**
     * Get all logs for a specific user.
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
public function get_by_user(int $user_id, string $since = null)
{
    $this->db->from('activity_log'); // adjust table name if different
    $this->db->where('user_id', $user_id);
    if ($since) {
        // Replace 'created_at' with your actual timestamp column (e.g., 'logged_at')
        $this->db->where('created_at >=', $since);
    }
    $this->db->order_by('created_at', 'DESC');
    return $this->db->get()->result_array();
}


    /**
     * Fetch all logs (admin/reporting use).
     *
     * @param int $limit
     * @return array
     */
    public function get_all(int $limit = 500): array
    {
        return $this->db
                    ->order_by('created_at', 'DESC')
                    ->limit($limit)
                    ->get($this->table)
                    ->result_array();
    }

    /**
     * Filter logs between two dates.
     *
     * @param string $start YYYY-MM-DD
     * @param string $end   YYYY-MM-DD
     * @return array
     */
    public function filter_by_date(string $start, string $end): array
    {
        return $this->db
                    ->where('DATE(created_at) >=', $start)
                    ->where('DATE(created_at) <=', $end)
                    ->order_by('created_at', 'DESC')
                    ->get($this->table)
                    ->result_array();
    }

    /**
     * Delete logs older than X days (maintenance).
     *
     * @param int $days
     * @return int Rows affected
     */
    public function delete_older_than(int $days): int
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $this->db->where('created_at <', $threshold);
        $this->db->delete($this->table);

        return $this->db->affected_rows();
    }
}
