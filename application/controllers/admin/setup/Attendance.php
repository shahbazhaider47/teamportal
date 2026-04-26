<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Attendance-specific setup model
        $this->load->model('admin/Attendance_setup_model', 'setup');
        $this->load->model('Activity_log_model');
        
        $this->load->model('admin/Company_setup_model', 'csetup');
        $this->load->model('Department_model');
        $this->load->model('User_model');
        $this->load->model('Hrm_positions_model');   
        $this->load->model('Roles_model', 'roles');

        // Auth guard
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            exit;
        }

        // Permission guard
        if (! staff_can('manage', 'company')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            echo $html;
            exit;
        }
    }

    /* ==========================================================
     | Utility: Activity Logger
     ========================================================== */
    protected function log_activity(string $action): void
    {
        $this->Activity_log_model->add([
            'user_id'    => (int) ($this->session->userdata('user_id') ?? 0),
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /* ==========================================================
     | Attendance Settings – Main Entry Point
     | URL: /admin/setup/attendance
     |
     | - All attendance configuration loads here
     | - Tabs are handled in the view using hash-based routing
     | - Each tab will later map to its own service/model methods
     ========================================================== */
    public function index()
    {
        // Base departments with stats
        $departments = $this->csetup->get_dept_with_stats();
        $positions   = $this->Hrm_positions_model->get_all();
        $roles       = $this->roles->get_all_roles();
        $settings = $this->csetup->get_company_settings();
    
        $view_data = [
            /* ------------------------------
             | TAB 1: Shifts
             ------------------------------ */
            'shifts'        => $this->setup->get_shifts(),
    
            /* ------------------------------
             | TAB 2: Public Holidays
             ------------------------------ */
            'holidays'      => $this->setup->get_public_holidays(),
    
            /* ------------------------------
             | TAB 3: Leave Types
             ------------------------------ */
            'leave_types'   => $this->setup->get_leave_types(),
    
            /* ------------------------------
             | Shared Lookups
             ------------------------------ */
            'offices'       => $this->csetup->get_offices(),
            'departments'   => $departments,
            'positions'     => $positions,
            'roles'         => $roles,
            'employees'     => $this->User_model->get_all_users(),
            'settings'      => $settings,
        ];
    
        $layout_data = [
            'page_title' => 'Attendance Setting',
            'subview'    => 'admin/setup/attendance/index',
            'view_data'  => $view_data,
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }

    /* ==========================================================
     | TAB 1: Shifts
     | Hash: #shifts
     ========================================================== */

public function save_shift()
{
    if ($this->input->method() !== 'post') show_404();

    /* ---------------- BASIC INPUT ---------------- */

    $offDays = $this->input->post('off_days');
    $start   = $this->input->post('shift_start_time');
    $end     = $this->input->post('shift_end_time');
    $break   = (int)$this->input->post('break_minutes');
    $isNight = $this->input->post('is_night_shift') ? true : false;

    /* ---------------- VALIDATE TIME FORMAT ---------------- */

    if (
        !$start || !$end ||
        !preg_match('/^\d{2}:\d{2}$/', $start) ||
        !preg_match('/^\d{2}:\d{2}$/', $end)
    ) {
        set_alert('warning', 'Invalid shift time provided.');
        redirect('admin/setup/attendance#shifts');
    }

    /* ---------------- CALCULATE DAILY MINUTES ---------------- */

    [$sh, $sm] = array_map('intval', explode(':', $start));
    [$eh, $em] = array_map('intval', explode(':', $end));

    $startMin = ($sh * 60) + $sm;
    $endMin   = ($eh * 60) + $em;

    // Night shift handling
    if ($isNight || $endMin <= $startMin) {
        $endMin += 1440;
    }

    $dailyMinutes = max(0, $endMin - $startMin);

    // Subtract break minutes
    $dailyMinutes = max(0, $dailyMinutes - $break);

    $dailyHours = round($dailyMinutes / 60, 2);

    /* ---------------- STANDARD WORKING DAYS ---------------- */

    $weeklyHours  = round($dailyHours * 5, 2);
    $monthlyHours = round($dailyHours * 22, 2);

    /* ---------------- DATA ARRAY ---------------- */

    $data = [
        'name'                     => $this->input->post('name', true),
        'code'                     => $this->input->post('code', true),
        'shift_type'               => $this->input->post('shift_type'),

        'shift_start_time'         => $start,
        'shift_end_time'           => $end,
        'break_start_time'         => $this->input->post('break_start_time'),
        'break_end_time'           => $this->input->post('break_end_time'),
        'break_minutes'            => $break,

        'grace_minutes'            => (int)$this->input->post('grace_minutes'),
        'monthly_late_minutes'     => (int)$this->input->post('monthly_late_minutes'),
        'overtime_after_minutes'   => (int)$this->input->post('overtime_after_minutes'),
        'max_overtime_minutes'     => (int)$this->input->post('max_overtime_minutes'),
        'overtime_type'            => $this->input->post('overtime_type'),

        'weekly_hours'             => $weeklyHours,
        'monthly_hours'            => $monthlyHours,

        'min_time_between_punches' => (int)$this->input->post('min_time_between_punches'),
        'off_days'                 => $offDays ? json_encode($offDays) : null,

        'is_night_shift'           => $isNight ? 1 : 0,
        'is_active'                => $this->input->post('is_active') ? 1 : 0,

        'created_at'               => date('Y-m-d H:i:s'),
    ];

    /* ---------------- SAVE ---------------- */

    if ($this->setup->insert_shift($data)) {
        set_alert('success', 'Work shift created successfully.');
        $this->log_activity('Created Work Shift: ' . $data['name']);
    } else {
        set_alert('warning', 'Failed to create work shift.');
    }

    redirect('admin/setup/attendance#shifts');
}


public function update_shift()
{
    if ($this->input->method() !== 'post') show_404();

    $id = (int)$this->input->post('id');
    if ($id <= 0) {
        set_alert('warning', 'Invalid shift ID.');
        redirect('admin/setup/attendance#shifts');
    }

    /* ---------------- BASIC INPUT ---------------- */

    $offDays = $this->input->post('off_days');
    $start   = $this->input->post('shift_start_time');
    $end     = $this->input->post('shift_end_time');
    $break   = (int)$this->input->post('break_minutes');
    $isNight = $this->input->post('is_night_shift') ? true : false;

    /* ---------------- VALIDATE TIME FORMAT ---------------- */

    if (
        !$start || !$end ||
        !preg_match('/^\d{2}:\d{2}$/', $start) ||
        !preg_match('/^\d{2}:\d{2}$/', $end)
    ) {
        set_alert('warning', 'Invalid shift time provided.');
        redirect('admin/setup/attendance#shifts');
    }

    /* ---------------- CALCULATE DAILY MINUTES ---------------- */

    [$sh, $sm] = array_map('intval', explode(':', $start));
    [$eh, $em] = array_map('intval', explode(':', $end));

    $startMin = ($sh * 60) + $sm;
    $endMin   = ($eh * 60) + $em;

    if ($isNight || $endMin <= $startMin) {
        $endMin += 1440;
    }

    $dailyMinutes = max(0, $endMin - $startMin);
    $dailyMinutes = max(0, $dailyMinutes - $break);

    $dailyHours = round($dailyMinutes / 60, 2);

    /* ---------------- STANDARD WORKING DAYS ---------------- */

    $weeklyHours  = round($dailyHours * 5, 2);
    $monthlyHours = round($dailyHours * 22, 2);

    /* ---------------- DATA ARRAY ---------------- */

    $data = [
        'name'                     => $this->input->post('name', true),
        'code'                     => $this->input->post('code', true),
        'shift_type'               => $this->input->post('shift_type'),

        'shift_start_time'         => $start,
        'shift_end_time'           => $end,
        'break_start_time'         => $this->input->post('break_start_time'),
        'break_end_time'           => $this->input->post('break_end_time'),
        'break_minutes'            => $break,

        'grace_minutes'            => (int)$this->input->post('grace_minutes'),
        'monthly_late_minutes'     => (int)$this->input->post('monthly_late_minutes'),
        'overtime_after_minutes'   => (int)$this->input->post('overtime_after_minutes'),
        'max_overtime_minutes'     => (int)$this->input->post('max_overtime_minutes'),
        'overtime_type'            => $this->input->post('overtime_type'),

        'weekly_hours'             => $weeklyHours,
        'monthly_hours'            => $monthlyHours,

        'min_time_between_punches' => (int)$this->input->post('min_time_between_punches'),
        'off_days'                 => $offDays ? json_encode($offDays) : null,

        'is_night_shift'           => $isNight ? 1 : 0,
        'is_active'                => $this->input->post('is_active') ? 1 : 0,

        'updated_at'               => date('Y-m-d H:i:s'),
    ];

    /* ---------------- UPDATE ---------------- */

    if ($this->setup->update_shift($id, $data)) {
        set_alert('success', 'Work shift updated successfully.');
        $this->log_activity('Updated Work Shift ID: ' . $id);
    } else {
        set_alert('warning', 'Failed to update work shift.');
    }

    redirect('admin/setup/attendance#shifts');
}


    
    public function delete_shift($id)
    {
        $id = (int) $id;
    
        if ($id <= 0) {
            set_alert('warning', 'Invalid shift ID.');
            redirect('admin/setup/attendance#shifts');
        }
    
        if ($this->setup->delete_shift($id)) {
            set_alert('success', 'Work shift deleted successfully.');
            $this->log_activity('Deleted Work Shift ID: ' . $id);
        } else {
            set_alert('warning', 'Failed to delete work shift.');
        }
    
        redirect('admin/setup/attendance#shifts');
    }

    /* ==========================================================
     | TAB 2: Public Holidays
     | Hash: #holidays
     ========================================================== */
    
    public function save_holiday()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    
        $name     = $this->input->post('name', true);
        $category = $this->input->post('category');
        $fromDate = $this->input->post('from_date');
        $toDate   = $this->input->post('to_date');
    
        // Normalize dates
        $fromTs = strtotime($fromDate);
        $toTs   = strtotime($toDate);
        $today  = strtotime(date('Y-m-d')); // midnight today
    
        /* ------------------------------------------
         | VALIDATIONS
         ------------------------------------------ */
    
        // Date range validation
        if ($fromTs > $toTs) {
            set_alert('warning', 'From date cannot be greater than To date.');
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        // ❗ Past date validation (future-only)
        //if ($fromTs < $today || $toTs < $today) {
        //    set_alert(
        //        'warning',
        //        'Public holidays must be created for future dates only. Past dates are not allowed.'
        //    );
        //    redirect('admin/setup/attendance#holidays');
        //    return;
        //}
    
        // Category validation
        if (!in_array($category, ['Local', 'Federal', 'Religion'], true)) {
            set_alert('warning', 'Invalid holiday category.');
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        /* ------------------------------------------
         | NORMALIZER (single or array → JSON)
         ------------------------------------------ */
        $normalize = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }
    
            if (!is_array($value)) {
                $value = [$value];
            }
    
            return json_encode(array_map('intval', $value));
        };
    
        /* ------------------------------------------
         | DATA PAYLOAD
         ------------------------------------------ */
        $data = [
            'name'        => $name,
            'category'    => $category,
            'from_date'   => $fromDate,
            'to_date'     => $toDate,
            'locations'   => $normalize($this->input->post('locations')),
            'departments' => $normalize($this->input->post('departments')),
            'positions'   => $normalize($this->input->post('positions')),
            'employees'   => $normalize($this->input->post('employees')),
            'created_at'  => date('Y-m-d H:i:s'),
        ];
    
        /* ------------------------------------------
         | SAVE
         ------------------------------------------ */
        if ($this->setup->insert_public_holiday($data)) {
            set_alert('success', 'Public holiday added successfully.');
            $this->log_activity('Created Public Holiday: ' . $name);
        } else {
            set_alert('warning', 'Failed to add public holiday.');
        }
    
        redirect('admin/setup/attendance#holidays');
    }
    
    public function update_holiday()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    
        $id       = (int) $this->input->post('id');
        $name     = $this->input->post('name', true);
        $category = $this->input->post('category');
        $fromDate = $this->input->post('from_date');
        $toDate   = $this->input->post('to_date');
    
        if ($id <= 0) {
            set_alert('warning', 'Invalid holiday ID.');
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        // Fetch existing record (CRITICAL)
        $existing = $this->setup->get_public_holiday($id);
    
        if (!$existing) {
            set_alert('warning', 'Holiday record not found.');
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        // Normalize timestamps
        $newFromTs = strtotime($fromDate);
        $newToTs   = strtotime($toDate);
        $oldFromTs = strtotime($existing['from_date']);
        $todayTs  = strtotime(date('Y-m-d'));
    
        /* ------------------------------------------
         | VALIDATIONS
         ------------------------------------------ */
    
        // Date order check
        if ($newFromTs > $newToTs) {
            set_alert('warning', 'From date cannot be greater than To date.');
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        // Category validation
        if (!in_array($category, ['Local', 'Federal', 'Religion'], true)) {
            set_alert('warning', 'Invalid holiday category.');
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        /*
         | Past-date rule:
         | - If original holiday was in the past
         |   → allow update ONLY if dates are unchanged
         | - If original holiday was future/ongoing
         |   → new dates must not be in the past
         */
        if ($oldFromTs < $todayTs) {
            // Holiday already in past → dates must NOT change
            if ($newFromTs !== $oldFromTs || $newToTs !== strtotime($existing['to_date'])) {
                set_alert(
                    'warning',
                    'Past holidays cannot have their dates modified.'
                );
                redirect('admin/setup/attendance#holidays');
                return;
            }
        } else {
            // Holiday was future → new dates must not be in the past
            if ($newFromTs < $todayTs || $newToTs < $todayTs) {
                set_alert(
                    'warning',
                    'You cannot move a holiday to past dates.'
                );
                redirect('admin/setup/attendance#holidays');
                return;
            }
        }
    
        /* ------------------------------------------
         | NORMALIZER
         ------------------------------------------ */
        $normalize = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }
    
            if (!is_array($value)) {
                $value = [$value];
            }
    
            return json_encode(array_map('intval', $value));
        };
    
        /* ------------------------------------------
         | UPDATE DATA
         ------------------------------------------ */
        $data = [
            'name'        => $name,
            'category'    => $category,
            'from_date'   => $fromDate,
            'to_date'     => $toDate,
            'locations'   => $normalize($this->input->post('locations')),
            'departments' => $normalize($this->input->post('departments')),
            'positions'   => $normalize($this->input->post('positions')),
            'employees'   => $normalize($this->input->post('employees')),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
    
        if ($this->setup->update_public_holiday($id, $data)) {
            set_alert('success', 'Public holiday updated successfully.');
            $this->log_activity('Updated Public Holiday ID: ' . $id);
        } else {
            set_alert('warning', 'Failed to update public holiday.');
        }
    
        redirect('admin/setup/attendance#holidays');
    }
    
    public function delete_holiday($id)
    {
        $id = (int) $id;
    
        if ($id <= 0) {
            set_alert('warning', 'Invalid holiday ID.');
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        $holiday = $this->setup->get_public_holiday($id);
    
        if (!$holiday) {
            set_alert('warning', 'Holiday record not found.');
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        // 1️⃣ Check if holiday is already in the past
        if (strtotime($holiday['from_date']) < strtotime(date('Y-m-d'))) {
            set_alert(
                'warning',
                'Past holidays cannot be deleted. You may archive it instead.'
            );
            redirect('admin/setup/attendance#holidays');
            return;
        }
    
        // 2️⃣ Check if holiday is used in payroll / attendance - Need to re-use
        //if ($this->setup->holiday_is_used($id)) {
        //    set_alert(
        //        'warning',
        //        'This holiday has been used in payroll or attendance and cannot be deleted.'
        //    );
        //    redirect('admin/setup/attendance#holidays');
        //    return;
        //}
    
        // 3️⃣ Soft delete (archive)
        if ($this->setup->archive_public_holiday($id)) {
            set_alert('success', 'Public holiday archived successfully.');
            $this->log_activity('Archived Public Holiday ID: ' . $id);
        } else {
            set_alert('warning', 'Failed to archive public holiday.');
        }
    
        redirect('admin/setup/attendance#holidays');
    }


    /* ==========================================================
     | TAB 3: Leave Types
     | Hash: #leavetypes
     ========================================================== */
    public function save_leave_type()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    
        $normalize_ids = function ($value) {
            if (empty($value)) {
                return null;
            }
    
            if (!is_array($value)) {
                $value = [$value];
            }
    
            return json_encode(array_values(array_map('intval', $value)));
        };
    
        $normalize_strings = function ($value) {
            if (empty($value)) {
                return null;
            }
    
            if (!is_array($value)) {
                $value = [$value];
            }
    
            return json_encode(array_values(array_map('trim', $value)));
        };
    
        $data = [
            'name'                => $this->input->post('name', true),
            'code'                => strtoupper($this->input->post('code', true)),
            'color'               => $this->input->post('color'),
            'type'                => $this->input->post('type'),
            'limit'               => $this->input->post('limit'),
            'unit'                => $this->input->post('unit'),
            'description'         => $this->input->post('description', true),
            'based_on'            => $this->input->post('based_on'),
    
            'attachment_required' => (int) $this->input->post('attachment_required'),
    
            // STRING JSON
            'employment_types'    => $normalize_strings($this->input->post('employment_types')),
            'applies_to_genders'  => $normalize_strings($this->input->post('applies_to_genders')),
            'applies_to_roles'    => $normalize_strings($this->input->post('applies_to_roles')),
    
            // ID JSON
            'applies_to_locations'   => $normalize_ids($this->input->post('applies_to_locations')),
            'applies_to_departments' => $normalize_ids($this->input->post('applies_to_departments')),
            'applies_to_positions'   => $normalize_ids($this->input->post('applies_to_positions')),
            'applies_to_employees'   => $normalize_ids($this->input->post('applies_to_employees')),
    
            'allowed_annually'    => $this->input->post('allowed_annually'),
            'allowed_monthly'     => $this->input->post('allowed_monthly'),
            'created_at'          => date('Y-m-d H:i:s'),
        ];
    
        if ($this->setup->insert_leave_type($data)) {
            set_alert('success', 'Leave type created successfully.');
            $this->log_activity('Created Leave Type: ' . $data['name']);
        } else {
            set_alert('warning', 'Failed to create leave type.');
        }
    
        redirect('admin/setup/attendance#leavetypes');
    }
        
    public function update_leave_type()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    
        $id = (int) $this->input->post('id');
        if ($id <= 0) {
            set_alert('warning', 'Invalid leave type ID.');
            redirect('admin/setup/attendance#leavetypes');
            return;
        }
    
        $normalize_ids = function ($value) {
            if (empty($value)) {
                return null;
            }
    
            if (!is_array($value)) {
                $value = [$value];
            }
    
            return json_encode(array_values(array_map('intval', $value)));
        };
    
        $normalize_strings = function ($value) {
            if (empty($value)) {
                return null;
            }
    
            if (!is_array($value)) {
                $value = [$value];
            }
    
            return json_encode(array_values(array_map('trim', $value)));
        };
    
        $data = [
            'name'                => $this->input->post('name', true),
            'code'                => strtoupper($this->input->post('code', true)),
            'color'               => $this->input->post('color'),
            'type'                => $this->input->post('type'),
            'limit'               => $this->input->post('limit'),
            'unit'                => $this->input->post('unit'),
            'description'         => $this->input->post('description', true),
            'based_on'            => $this->input->post('based_on'),
    
            'attachment_required' => (int) $this->input->post('attachment_required'),
    
            // STRING JSON
            'employment_types'    => $normalize_strings($this->input->post('employment_types')),
            'applies_to_genders'  => $normalize_strings($this->input->post('applies_to_genders')),
            'applies_to_roles'    => $normalize_strings($this->input->post('applies_to_roles')),
    
            // ID JSON
            'applies_to_locations'   => $normalize_ids($this->input->post('applies_to_locations')),
            'applies_to_departments' => $normalize_ids($this->input->post('applies_to_departments')),
            'applies_to_positions'   => $normalize_ids($this->input->post('applies_to_positions')),
            'applies_to_employees'   => $normalize_ids($this->input->post('applies_to_employees')),
    
            'allowed_annually'    => $this->input->post('allowed_annually'),
            'allowed_monthly'     => $this->input->post('allowed_monthly'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ];
    
        if ($this->setup->update_leave_type($id, $data)) {
            set_alert('success', 'Leave type updated successfully.');
            $this->log_activity('Updated Leave Type ID: ' . $id);
        } else {
            set_alert('warning', 'Failed to update leave type.');
        }
    
        redirect('admin/setup/attendance#leavetypes');
    }

    public function delete_leave_type($id)
    {
        $id = (int) $id;
    
        if ($id <= 0) {
            set_alert('warning', 'Invalid leave type ID.');
            redirect('admin/setup/attendance#leavetypes');
            return;
        }
    
        if ($this->setup->archive_leave_type($id)) {
            set_alert('success', 'Leave type archived successfully.');
            $this->log_activity('Archived Leave Type ID: ' . $id);
        } else {
            set_alert('warning', 'Failed to archive leave type.');
        }
    
        redirect('admin/setup/attendance#leavetypes');
    }


    /* ==========================================================
     | TAB 4: Overtime Policy
     | Hash: #overtime
     ========================================================== */
public function save_attendance_settings()
{
    if ($this->input->method() !== 'post') {
        show_404();
    }

    if (!staff_can('manage', 'company')) {
        show_error('Forbidden', 403);
    }

    $allowedSettings = [
        'att_allow_to_apply_leaves'         => 'bool',
        'att_allow_monday_leave'            => 'bool',
        'att_allow_friday_leave'            => 'bool',
        'att_allow_bridge_holiday_leave'    => 'bool',
        'att_enable_sandwich_rule'          => 'bool',
        'att_sandwich_deduction_days'       => 'int',
        'att_max_consecutive_leave_days'    => 'int',
        'att_working_days'                  => 'int',        
        'att_leave_approver'                => 'string',
    ];

    $post  = $this->input->post();
    $saved = 0;

    foreach ($allowedSettings as $key => $type) {

        if (!array_key_exists($key, $post)) {
            continue;
        }

        $raw = $post[$key];

        switch ($type) {
            case 'bool':
                $value = in_array((string)$raw, ['1', 'true', 'yes'], true) ? '1' : '0';
                break;

            case 'int':
                $value = (string) max(0, (int) $raw);
                break;

            case 'string':
                $value = (string) $raw; // Add string handling
                break;

            default:
                continue 2;
        }

        if ($this->setup->save_company_setting($key, $value)) {
            $saved++;
        }
    }

    set_alert(
        $saved ? 'success' : 'warning',
        $saved
            ? 'Attendance settings updated successfully.'
            : 'No attendance settings were changed.'
    );

    $this->log_activity("Updated attendance settings ({$saved} keys)");
    redirect('admin/setup/attendance#attendancesettings');
}

    /* ==========================================================
     | TAB 5: Attendance Status
     | Hash: #attendance
     ========================================================== */

    /* ==========================================================
     | TAB 6: Time Rules
     | Hash: #timerule
     ========================================================== */

    /* ==========================================================
     | TAB 7: Leave Adjustments
     | Hash: #leavesadjustment
     ========================================================== */
}
