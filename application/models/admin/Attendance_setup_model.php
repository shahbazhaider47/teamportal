<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance_setup_model extends CI_Model
{
    /* ==========================================================
     | TABLES
     ========================================================== */
    protected string $shiftsTable   = 'work_shifts';
    protected string $holidaysTable = 'public_holidays';
    protected string $leaveTypesTable = 'leave_types';
    protected $companySettings = 'company_settings';    
    
    /* ==========================================================
     | WORK SHIFTS
     ========================================================== */

    /**
     * Get all work shifts
     */
    public function get_shifts(): array
    {
        return $this->db
            ->from($this->shiftsTable)
            ->order_by('is_active DESC, name ASC')
            ->get()
            ->result_array();
    }

    public function insert_shift(array $data): bool
    {
        return $this->db->insert($this->shiftsTable, $data);
    }

    public function update_shift(int $id, array $data): bool
    {
        return $this->db
            ->where('id', $id)
            ->update($this->shiftsTable, $data);
    }

    public function delete_shift(int $id): bool
    {
        return $this->db
            ->where('id', $id)
            ->delete($this->shiftsTable);
    }

    /* ==========================================================
     | PUBLIC HOLIDAYS
     ========================================================== */
    public function get_public_holidays(): array
    {
        return $this->db
            ->from($this->holidaysTable)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('from_date DESC, name ASC')
            ->get()
            ->result_array();
    }
    
    public function get_public_holiday(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
    
        return $this->db
            ->from($this->holidaysTable)
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array() ?: null;
    }
    
    public function insert_public_holiday(array $data): bool
    {
        return $this->db->insert($this->holidaysTable, $data);
    }
    
    public function update_public_holiday(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }
    
        return $this->db
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->holidaysTable, $data);
    }
    
    public function archive_public_holiday(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
    
        return $this->db
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->holidaysTable, [
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
    }
    
    public function holiday_is_used(int $holidayId): bool
    {
        // Example: attendance
        $attendanceCount = $this->db
            ->where('holiday_id', $holidayId)
            ->count_all_results('attendance_logs');
    
        if ($attendanceCount > 0) {
            return true;
        }
    
        // Example: payroll
        $payrollCount = $this->db
            ->where('reference_type', 'holiday')
            ->where('reference_id', $holidayId)
            ->count_all_results('payroll_items');
    
        return $payrollCount > 0;
    }

    /* ==========================================================
     | LEAVE TYPES
     ========================================================== */
    
    /**
     * Get all leave types (active only)
     */
    public function get_leave_types(): array
    {
        return $this->db
            ->from($this->leaveTypesTable)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('name ASC')
            ->get()
            ->result_array();
    }
    
    public function get_leave_type(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
    
        return $this->db
            ->from($this->leaveTypesTable)
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array() ?: null;
    }
    
    public function insert_leave_type(array $data): bool
    {
        return $this->db->insert($this->leaveTypesTable, $data);
    }
    
    public function update_leave_type(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }
    
        return $this->db
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->leaveTypesTable, $data);
    }
    
    public function archive_leave_type(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
    
        return $this->db
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->leaveTypesTable, [
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
    }


    /**
     * Insert or update a company setting
     */
/**
 * Insert or update a company setting
 */
public function save_company_setting(string $key, ?string $value): bool
{
    if ($key === '') {
        return false;
    }

    $exists = $this->db
        ->where('key', $key)
        ->count_all_results($this->companySettings) > 0;

    $data = [
        'key' => $key,
        'value' => $value,
        'updated_at'  => date('Y-m-d H:i:s'),
    ];

    if ($exists) {
        return $this->db
            ->where('key', $key)
            ->update($this->companySettings, $data);
    }

    $data['created_at'] = date('Y-m-d H:i:s');

    return $this->db->insert($this->companySettings, $data);
}

    
}
