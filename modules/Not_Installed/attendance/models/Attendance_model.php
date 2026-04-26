<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Attendance_model extends CI_Model
{
    protected $table = 'attendance';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all attendance records for a given month (optionally for a user)
     */
    public function get_monthly_records($year, $month, $user_ids = null)
    {
        $start = "$year-$month-01";
        $end   = date('Y-m-t', strtotime($start));
    
        $this->db->where('attendance_date >=', $start);
        $this->db->where('attendance_date <=', $end);
    
        if ($user_ids !== null) {
            if (is_array($user_ids)) {
                $this->db->where_in('user_id', $user_ids);
            } else {
                $this->db->where('user_id', $user_ids);
            }
        }
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get attendance status counts for a given month (optionally for a user)
     */
    public function get_monthly_stats($year, $month, $user_id = null)
    {
        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate));

        $this->db->select("
            SUM(CASE WHEN status = 'P' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN status IN ('C', 'M') THEN 1 ELSE 0 END) as `leave`,
            SUM(CASE WHEN status = 'S' THEN 1 ELSE 0 END) as short_leave,
            SUM(CASE WHEN status IN ('H', 'E') THEN 1 ELSE 0 END) as holiday
        ");
        $this->db->where('attendance_date >=', $startDate);
        $this->db->where('attendance_date <=', $endDate);
        if ($user_id !== null) {
            $this->db->where('user_id', $user_id);
        }
        return $this->db->get($this->table)->row_array();
    }

    /**
     * Get a single attendance record for a specific user/date
     */
    public function get_by_user_and_date($user_id, $date)
    {
        return $this->db->get_where($this->table, [
            'user_id' => $user_id,
            'attendance_date' => $date
        ])->row_array();
    }

    /**
     * Insert or update an attendance record (upsert)
     */
    public function upsert($data)
    {
        // $data must contain user_id and attendance_date
        $existing = $this->get_by_user_and_date($data['user_id'], $data['attendance_date']);
        if ($existing) {
            $this->db->where('user_id', $data['user_id']);
            $this->db->where('attendance_date', $data['attendance_date']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->update($this->table, $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert($this->table, $data);
        }
    }

    /**
     * Delete an attendance record for a specific user/date
     */
    public function delete_by_user_and_date($user_id, $date)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('attendance_date', $date);
        return $this->db->delete($this->table);
    }

    
public function get_user_attendance_summary($userId)
{
    $this->db->select('status, COUNT(*) as total');
    $this->db->from('attendance');
    $this->db->where('user_id', $userId);
    $this->db->group_by('status');
    $query = $this->db->get();

    $result = array_fill_keys(['A', 'C', 'S', 'M', 'H', 'E'], 0);
    foreach ($query->result_array() as $row) {
        $status = $row['status'];
        $result[$status] = (int)$row['total'];
    }

    return $result;
}
    



public function get_all_records()
{
    return $this->db->order_by('attendance_date', 'ASC')->get($this->table)->result_array();
}

public function get_records_by_user($user_id)
{
    return $this->db->where('user_id', $user_id)
                    ->order_by('attendance_date', 'ASC')
                    ->get($this->table)
                    ->result_array();
}


public function is_absent($user_id, $date)
{
    $row = $this->db->get_where('attendance', ['user_id' => $user_id, 'attendance_date' => $date])->row_array();
    return $row && ($row['status'] === 'A'); // A = Absent
}


    
}