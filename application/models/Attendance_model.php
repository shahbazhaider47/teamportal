<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Attendance_model extends CI_Model
{
    protected $table = 'attendance';

    public function __construct()
    {
        parent::__construct();
    }

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

    public function get_by_user_and_date($user_id, $date)
    {
        return $this->db->get_where($this->table, [
            'user_id' => $user_id,
            'attendance_date' => $date
        ])->row_array();
    }

    public function upsert($data)
    {
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


/* =============================================================
 | ATTENDANCE LOGS
 * ============================================================= */

public function get_monthly_logs($year, $month, $user_ids = null)
{
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $end   = date('Y-m-t 23:59:59', strtotime($start));

    $this->db->from('attendance_logs');
    $this->db->where('datetime >=', $start);
    $this->db->where('datetime <=', $end);
    $this->db->where('deleted_at IS NULL', null, false);

    if ($user_ids !== null) {
        if (is_array($user_ids)) {
            $this->db->where_in('user_id', $user_ids);
        } else {
            $this->db->where('user_id', (int)$user_ids);
        }
    }

    $this->db->order_by('datetime', 'ASC');

    return $this->db->get()->result_array();
}

public function get_logs_by_user($user_id, $limit = 100)
{
    return $this->db
        ->from('attendance_logs')
        ->where('user_id', (int)$user_id)
        ->where('deleted_at IS NULL', null, false)
        ->order_by('datetime', 'DESC')
        ->limit((int)$limit)
        ->get()
        ->result_array();
}

public function get_log_by_id($id)
{
    return $this->db
        ->from('attendance_logs')
        ->where('id', (int)$id)
        ->where('deleted_at IS NULL', null, false)
        ->get()
        ->row_array();
}

public function count_monthly_logs($year, $month, $user_ids)
{
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $end   = date('Y-m-t 23:59:59', strtotime($start));

    $this->db->from('attendance_logs');
    $this->db->where('datetime >=', $start);
    $this->db->where('datetime <=', $end);
    $this->db->where('deleted_at IS NULL', null, false);

    if (is_array($user_ids)) {
        $this->db->where_in('user_id', $user_ids);
    } else {
        $this->db->where('user_id', (int)$user_ids);
    }

    return (int)$this->db->count_all_results();
}

/* =============================================================
 | PAGINATED ATTENDANCE LOGS (MONTHLY)
 * ============================================================= */

public function get_monthly_logs_paginated($year, $month, $user_ids, $limit = 100, $offset = 0)
{
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $end   = date('Y-m-t 23:59:59', strtotime($start));

    $this->db->from('attendance_logs');
    $this->db->where('datetime >=', $start);
    $this->db->where('datetime <=', $end);
    $this->db->where('deleted_at IS NULL', null, false);

    if (!empty($user_ids)) {
        if (is_array($user_ids)) {
            $this->db->where_in('user_id', $user_ids);
        } else {
            $this->db->where('user_id', (int)$user_ids);
        }
    }

    $this->db->order_by('datetime', 'ASC');
    $this->db->limit((int)$limit, (int)$offset);

    return $this->db->get()->result_array();
}


public function get_user_logs_filtered(
    $userId, $from, $to, $status, $logType, $approval, $limit, $offset
) {
    $this->db->from('attendance_logs');
    $this->db->where('user_id', (int)$userId);
    $this->db->where('deleted_at IS NULL', null, false);

    if ($from) $this->db->where('datetime >=', $from.' 00:00:00');
    if ($to)   $this->db->where('datetime <=', $to.' 23:59:59');
    if ($status)  $this->db->where('status', $status);
    if ($logType) $this->db->where('log_type', strtoupper($logType));
    if ($approval) $this->db->where('approval_status', $approval);

    $this->db->order_by('datetime', 'ASC');
    $this->db->limit($limit, $offset);

    return $this->db->get()->result_array();
}

public function count_user_logs_filtered(
    int $userId,
    ?string $fromDate = null,
    ?string $toDate = null,
    ?string $status = null,
    ?string $logType = null,
    ?string $approval = null
): int {
    $this->db->from('attendance_logs');
    $this->db->where('user_id', $userId);
    $this->db->where('deleted_at IS NULL', null, false);

    if (!empty($fromDate)) {
        $this->db->where('datetime >=', $fromDate . ' 00:00:00');
    }

    if (!empty($toDate)) {
        $this->db->where('datetime <=', $toDate . ' 23:59:59');
    }

    if (!empty($status)) {
        $this->db->where('status', $status);
    }

    if (!empty($logType)) {
        $this->db->where('log_type', strtoupper($logType));
    }

    if (!empty($approval)) {
        $this->db->where('approval_status', strtoupper($approval));
    }

    return (int)$this->db->count_all_results();
}
 

    /**
     * In Attendance_model, add this method for better querying:
     */
public function get_monthly_logs_with_shift_date($year, $month, $user_ids = null, $limit = null, $offset = null)
{
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $end   = date('Y-m-t 23:59:59', strtotime($start));

    $this->db->select("
        a.*,
        CASE 
            WHEN ws.is_night_shift = 1
                 AND TIME(a.datetime) < TIME(ws.start_time)
            THEN DATE(DATE_SUB(a.datetime, INTERVAL 1 DAY))
            ELSE DATE(a.datetime)
        END AS shift_date
    ");
    $this->db->from('attendance_logs a');
    $this->db->join('users u', 'u.id = a.user_id', 'left');
    $this->db->join('work_shifts ws', 'ws.id = u.work_shift', 'left');

    $this->db->where('a.datetime >=', $start);
    $this->db->where('a.datetime <=', $end);
    $this->db->where('a.deleted_at IS NULL', null, false);

    if (!empty($user_ids)) {
        if (is_array($user_ids)) {
            $this->db->where_in('a.user_id', $user_ids);
        } else {
            $this->db->where('a.user_id', (int)$user_ids);
        }
    }

    $this->db->order_by('a.datetime', 'DESC');

    if ($limit !== null) {
        $this->db->limit((int)$limit, (int)$offset);
    }

    return $this->db->get()->result_array();
}


public function get_user_logs_filtered_with_shift_date(
    int $userId,
    ?string $fromDate,
    ?string $toDate,
    ?string $status,
    ?string $logType,
    ?string $approval,
    int $limit,
    int $offset
) {
    $this->db->select("
        a.*,
        CASE 
            WHEN ws.is_night_shift = 1
                 AND TIME(a.datetime) < TIME(ws.start_time)
            THEN DATE(DATE_SUB(a.datetime, INTERVAL 1 DAY))
            ELSE DATE(a.datetime)
        END AS shift_date
    ");
    $this->db->from('attendance_logs a');
    $this->db->join('users u', 'u.id = a.user_id', 'left');
    $this->db->join('work_shifts ws', 'ws.id = u.work_shift', 'left');

    $this->db->where('a.user_id', $userId);
    $this->db->where('a.deleted_at IS NULL', null, false);

    if ($fromDate) $this->db->where('a.datetime >=', $fromDate.' 00:00:00');
    if ($toDate)   $this->db->where('a.datetime <=', $toDate.' 23:59:59');

    if ($status)   $this->db->where('a.status', $status);
    if ($logType)  $this->db->where('a.log_type', strtoupper($logType));
    if ($approval) $this->db->where('a.approval_status', strtoupper($approval));

    $this->db->order_by('a.datetime', 'DESC');
    $this->db->limit($limit, $offset);

    return $this->db->get()->result_array();
}

 
 
public function update_attendance_log(int $id, array $data): bool
{
    $this->db->where('id', $id);
    $this->db->where('deleted_at IS NULL', null, false);
    return (bool)$this->db->update('attendance_logs', $data);
}
   
}