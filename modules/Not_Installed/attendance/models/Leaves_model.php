<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leaves_model extends CI_Model
{
    protected $table = 'user_leaves';
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('date');
    }

    /**
     * Get all leaves for a user (with pagination optional)
     */
    public function get_leaves_by_user($user_id, $limit = null, $offset = null)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('start_date', 'DESC');

        if ($limit) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get($this->table)->result_array();
    }

    /**
     * Insert new leave request
     */
    public function insert_leave(array $data)
    {
        $data['created_at']  = date('Y-m-d H:i:s');
        $data['leave_days']  = $this->calculate_leave_days($data['start_date'], $data['end_date'], $data['is_half_day'] ?? false);

        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update existing leave request
     */
    public function update_leave($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Recalculate leave_days if dates changed
        if (isset($data['start_date']) || isset($data['end_date'])) {
            $existing = $this->get_leave_by_id($id);
            $start    = $data['start_date'] ?? $existing['start_date'];
            $end      = $data['end_date']   ?? $existing['end_date'];
            $is_half  = $data['is_half_day'] ?? false;

            $data['leave_days'] = $this->calculate_leave_days($start, $end, $is_half);
        }

        $this->db->where($this->primaryKey, $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Get a single leave record
     */
    public function get_leave_by_id($id)
    {
        return $this->db->get_where($this->table, [$this->primaryKey => $id])->row_array();
    }

    /**
     * Delete a leave request
     */
    public function delete_leave($id)
    {
        return $this->db->delete($this->table, [$this->primaryKey => $id]);
    }

    /**
     * Calculate leave duration
     */
    public function calculate_leave_days($start_date, $end_date, $is_half_day = false)
    {
        $start = new DateTime($start_date);
        $end   = new DateTime($end_date);
        $end->modify('+1 day');

        $interval = $start->diff($end);
        $days = $interval->days;

        while ($start < $end) {
            if (in_array($start->format('N'), [6, 7])) {
                $days--;
            }
            $start->modify('+1 day');
        }

        return $is_half_day ? 0.5 : $days;
    }

    /**
     * Get monthly leave stats for user
     */
    public function get_monthly_stats($year, $month, $user_id = null)
    {
        $start = "$year-$month-01";
        $end   = date("Y-m-t", strtotime($start));

        $this->db->select("
            SUM(leave_days) as total,
            SUM(CASE WHEN leave_type = 'Medical' THEN leave_days ELSE 0 END) as medical,
            SUM(CASE WHEN leave_type = 'Casual' THEN leave_days ELSE 0 END) as casual,
            SUM(CASE WHEN leave_type = 'Emergency' THEN leave_days ELSE 0 END) as emergency,
            SUM(CASE WHEN leave_type = 'Short Leave' THEN leave_days ELSE 0 END) as short_leave
        ");

        $this->db->where('start_date <=', $end);
        $this->db->where('end_date >=', $start);
        $this->db->where('status', 'approved');

        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        return $this->db->get($this->table)->row_array();
    }

    /**
     * Get all pending leaves assigned to an approver (team lead/admin)
     */
    public function get_pending_leaves_for_approver($approver_id)
    {
        $this->db->where('status', 'pending');
        $this->db->group_start();
        $this->db->where('notified_admin', 0);
        $this->db->or_where('notified_lead', 0);
        $this->db->group_end();
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Approve or reject a leave
     */
    public function update_leave_status($id, $status, $approver_id)
    {
        return $this->db->update($this->table, [
            'status'       => $status,
            'approver_id'  => $approver_id,
            'approved_at'  => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ], [$this->primaryKey => $id]);
    }

    /**
     * Mark a leave as seen by the user (after approval/rejection)
     */
    public function mark_leave_seen_by_user($id)
    {
        return $this->db->update($this->table, ['seen_by_user' => 1], [$this->primaryKey => $id]);
    }

    /**
     * Get unseen leaves approved/rejected that user hasn’t seen
     */
    public function get_unseen_approved_leaves($user_id)
    {
        return $this->db->get_where($this->table, [
            'user_id'       => $user_id,
            'status !='     => 'pending',
            'seen_by_user'  => 0
        ])->result_array();
    }

    /**
     * Get remaining leave balance
     */
    public function get_leave_balance($user_id, $leave_type)
    {
        $allocated = match ($leave_type) {
            'Casual'      => 12,
            'Medical'     => 15,
            'Emergency'   => 5,
            'Short Leave' => 10,
            default       => 0
        };

        $used = $this->db->select_sum('leave_days')
            ->where('user_id', $user_id)
            ->where('leave_type', $leave_type)
            ->where('status', 'approved')
            ->get($this->table)
            ->row()->leave_days;

        return $allocated - ($used ?? 0);
    }


 /**
 * Get all leave records
 *
 * @return array
 */
public function get_all_leaves()
{
    return $this->db->order_by('created_at', 'DESC')
                    ->get($this->table)
                    ->result_array();

}


/**
 * Get leaves for specific users
 *
 * @param array $user_ids
 * @param string|null $status (optional: e.g. 'pending')
 * @return array
 */
    public function get_leaves_by_users(array $user_ids, $status = null)
    {
        $this->db->where_in('user_id', $user_ids);
        
        if ($status !== null) {
            $this->db->where('status', $status);
        }
        
        return $this->db->order_by('created_at', 'DESC')
                        ->get($this->table)
                        ->result_array();
    }
    
    
    
/**
 * Get leave usage summary per type for the current year (not all-time)
 */
public function get_leave_usage_summary($user_id)
{
    $year = date('Y');
    $CI =& get_instance();

    // System limits
    $casual_limit   = (int) get_system_setting('casual_leaves');
    $medical_limit  = (int) get_system_setting('medical_leaves');
    $holiday_limit  = (int) get_system_setting('holiday_leaves');

    // 1. Get all approved user_leaves for the year
    $user_leaves = $CI->db->select('leave_type, start_date, end_date, leave_days')
        ->from('user_leaves')
        ->where('user_id', $user_id)
        ->where('status', 'approved')
        ->where('YEAR(start_date)', $year)
        ->get()
        ->result_array();

    // Build a map of all days covered by approved leave requests (to avoid double-counting with attendance)
    $leave_days_covered = [];
    $leave_totals = [
        'casual'    => 0,
        'medical'   => 0,
        'emergency' => 0,
        'short'     => 0,
        'holiday'   => 0,
    ];

    foreach ($user_leaves as $leave) {
        $type = strtolower($leave['leave_type']);
        // Map to summary keys
        $type_key = match($type) {
            'casual'      => 'casual',
            'medical'     => 'medical',
            'emergency'   => 'emergency',
            'short leave' => 'short',
            'holiday'     => 'holiday',
            default       => null,
        };
        if (!$type_key) continue;

        // Add total days
        $leave_totals[$type_key] += (float) $leave['leave_days'];

        // Mark each individual day (YYYY-MM-DD)
        $start = new DateTime($leave['start_date']);
        $end   = new DateTime($leave['end_date']);
        while ($start <= $end) {
            $leave_days_covered[$start->format('Y-m-d')] = true;
            $start->modify('+1 day');
        }
    }

    // 2. Get attendance for the year, excluding days covered by approved leaves
    $attendance = $CI->db->select('attendance_date, status')
        ->from('attendance')
        ->where('user_id', $user_id)
        ->where('YEAR(attendance_date)', $year)
        ->where_in('status', ['c', 'M', 'S', 'H'])
        ->get()
        ->result_array();

    $attendance_counts = [
        'c' => 0, // Casual
        'M' => 0, // Medical
        'S' => 0, // Short
        'H' => 0, // Holiday (if you track it here)
    ];
    foreach ($attendance as $row) {
        if (!isset($leave_days_covered[$row['attendance_date']])) {
            $attendance_counts[$row['status']]++;
        }
    }

    // Add attendance days (not already covered by formal leave requests)
    $leave_totals['casual']   += $attendance_counts['C'];
    $leave_totals['medical']  += $attendance_counts['M'];
    $leave_totals['short']    += $attendance_counts['S'];
    $leave_totals['holiday']  += $attendance_counts['H']; // only if you want to track
    // Emergency is *not* in attendance (never count)

    return [
        'casual'    => ['used' => $leave_totals['casual'],    'total' => $casual_limit],
        'medical'   => ['used' => $leave_totals['medical'],   'total' => $medical_limit],
        'holiday'   => ['used' => $leave_totals['holiday'],   'total' => $holiday_limit],
        'emergency' => ['used' => $leave_totals['emergency'], 'total' => 0],
        'short'     => ['used' => $leave_totals['short'],     'total' => 0]
    ];
}


public function is_on_leave($user_id, $date)
{
    $this->db->where('user_id', $user_id);
    $this->db->where('status', 'approved');
    $this->db->where('start_date <=', $date);
    $this->db->where('end_date >=', $date);
    return $this->db->count_all_results('user_leaves') > 0;
    
}


public function get_calendar_events($start, $end, $user_id) {
    // Convert ISO8601 or fullcalendar date string to Y-m-d
    $start_date = (new DateTime($start))->format('Y-m-d');
    $end_date = (new DateTime($end))->format('Y-m-d');

    $this->db->select('id, start_date, end_date, leave_type, leave_notes, status');
    $this->db->where('user_id', $user_id);
    // Show any leave overlapping with the calendar window:
    $this->db->where('start_date <=', $end_date);
    $this->db->where('end_date >=', $start_date);
    $this->db->where('status', 'approved');
    $result = $this->db->get($this->table)->result_array();

    //log_message('debug', "Leaves_model::get_calendar_events using start=$start_date end=$end_date SQL: " . $this->db->last_query());
    //log_message('debug', 'Leaves_model::get_calendar_events result: ' . json_encode($result));
    return $result;
}

public function get_upcoming_leaves($limit = 5)
{
    $today = date('Y-m-d');
    $this->db->select("{$this->table}.*, u.firstname, u.lastname, u.emp_department");
    $this->db->from($this->table);
    $this->db->join('users u', "{$this->table}.user_id = u.id", 'left');
    $this->db->where("{$this->table}.status", 'approved');
    $this->db->where("{$this->table}.start_date >=", $today);
    $this->db->order_by("{$this->table}.start_date", 'ASC');
    $this->db->limit($limit);

    $results = $this->db->get()->result_array();

    // Optionally add employee_name and days for your view's convenience
    foreach ($results as &$row) {
        $row['employee_name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        $row['days'] = isset($row['leave_days']) ? $row['leave_days'] : '-';
    }
    unset($row);

    return $results;
}


public function count_on_leave_today()
{
    $today = date('Y-m-d');
    return $this->db
        ->where('status', 'approved')
        ->where('start_date <=', $today)
        ->where('end_date >=', $today)
        ->count_all_results($this->table); // Make sure $this->table = 'user_leaves';
}

   
}
