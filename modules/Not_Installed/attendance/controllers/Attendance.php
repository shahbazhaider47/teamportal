<?php
// File: modules/attendance/controllers/Attendance.php

defined('BASEPATH') or exit('No direct script access allowed');

class Attendance extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Load required models
        $this->load->model('attendance/Leaves_model');
        $this->load->model('attendance/Attendance_model');
        $this->load->helper('attendance/attendance'); // Adjust path if needed


    }

    public function index()
    {
        // Get from URL (?year=2024&month=6), fallback to today if missing
        $year  = (int)($this->input->get('year') ?: date('Y'));
        $month = (int)($this->input->get('month') ?: date('m'));
    
        $todayDay    = (int)date('j');
        $currentUser = (int)$this->session->userdata('user_id');
    
        // Permission checks
        $can_view_global = staff_can('view_global', 'attendance');
        $can_view_team   = staff_can('own_team', 'attendance');
        $can_view_own    = staff_can('view_own', 'attendance');
    
        if (!$can_view_global && !$can_view_team && !$can_view_own) {
            show_error('You do not have permission to view attendance records.', 403, 'Permission Denied');
        }
    
        // Build calendar days for this month/year
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $allDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $isWeekend = in_array(date('w', strtotime($dateStr)), [0, 6]);
            $allDays[] = [
                'day'       => $d,
                'dateStr'   => $dateStr,
                'isWeekend' => $isWeekend,
            ];
        }
    
        // Load users and records per permission
        $this->load->model('User_model');
        $this->load->model('Teams_model');
    
        if ($can_view_global) {
            // See all users
            $users   = $this->User_model->get_all_users();
            $records = $this->Attendance_model->get_monthly_records($year, $month);
        } elseif ($can_view_team) {
            // Get the current user's team
            $currentUserInfo = $this->User_model->get_user_by_id($currentUser);
            $currentTeamId   = $currentUserInfo['emp_team'] ?? null;
            $users    = $currentTeamId ? $this->User_model->get_users_by_team($currentTeamId) : [];
            $user_ids = array_column($users, 'id');
            $records  = !empty($user_ids)
                ? $this->Attendance_model->get_monthly_records($year, $month, $user_ids)
                : [];
        } else {
            // Only self
            $users   = [$this->User_model->get_user_by_id($currentUser)];
            $records = $this->Attendance_model->get_monthly_records($year, $month, $currentUser);
        }
    
        // Build status grid
        $existing = [];
        foreach ($records as $r) {
            $day = (int)date('j', strtotime($r['attendance_date']));
            $existing[$r['user_id']][$day] = $r['status'];
        }
    
        $data = [
            'title'        => 'Attendance Dashboard',
            'page_title'   => 'Attendance Dashboard',
            'allDays'      => $allDays,
            'users'        => $users,
            'existing'     => $existing,
            'todayDay'     => $todayDay,
            'currentUser'  => $currentUser,
            'currentYear'  => $year,
            'currentMonth' => $month,
        ];
    
        $this->load->view('layouts/master', [
            'subview'   => 'manage',
            'view_data' => $data,
        ]);
    }

    protected function get_attendance_summary($year, $month)
    {
        // Get total working days in the month (excluding weekends)
        $totalWorkingDays = $this->calculate_working_days($year, $month);

        // Get attendance statistics
        $stats = $this->Attendance_model->get_monthly_stats($year, $month);

        return array(
            'totalWorkingDays' => $totalWorkingDays,
            'presentCount' => isset($stats['present']) ? $stats['present'] : 0,
            'absentCount' => isset($stats['absent']) ? $stats['absent'] : 0,
            'leaveCount' => isset($stats['leave']) ? $stats['leave'] : 0,
            'shortLeaveCount' => isset($stats['short_leave']) ? $stats['short_leave'] : 0,
            'holidayCount' => isset($stats['holiday']) ? $stats['holiday'] : 0,
        );
    }

    protected function calculate_working_days($year, $month)
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $workingDays = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayOfWeek = date('w', mktime(0, 0, 0, $month, $day, $year));
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
    

    /**
    * Add or update an attendance record (AJAX)
    * POST: user_id, attendance_date, status
    */
    public function save()
    {
        if (!$this->session->userdata('is_logged_in')) { redirect('authentication/login'); return; }
        if (!staff_can('create','attendance')) { show_error('Unauthorized', 403, 'Permission Denied'); }
    
        $posted       = $this->input->post();
        $attendance   = $posted['attendance'] ?? null;
    
        // ← read month context from POST (added via hidden inputs in the view)
        $year         = (int)($posted['year']  ?? 0);
        $month        = (int)($posted['month'] ?? 0);
    
        // Fallback only if missing (shouldn’t happen once we add hidden inputs)
        if ($year <= 0)  { $year  = (int)date('Y'); }
        if ($month <= 0) { $month = (int)date('n'); }
    
        if (!$attendance || !is_array($attendance)) {
            set_alert('danger', 'No attendance data submitted.');
            redirect('attendance?year='.$year.'&month='.$month);
            return;
        }
        
        // Clamp / validate
        if ($month < 1 || $month > 12) { $month = (int)date('n'); }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
        // Persist
        $this->db->trans_start();
    
        foreach ($attendance as $user_id => $days) {
            $user_id = (int)$user_id;
            if (!is_array($days)) { continue; }
    
            foreach ($days as $day => $status) {
                $day = (int)$day;
                if ($day < 1 || $day > $daysInMonth) { continue; }
    
                $attendance_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    
                // Skip weekends—matches your view behavior
                $dow = (int)date('w', strtotime($attendance_date)); // 0 Sun, 6 Sat
                if ($dow === 0 || $dow === 6) { continue; }
    
                $status = strtoupper(trim((string)$status));
                if ($status === '') { continue; } // nothing changed
    
                // Accept only the known one-letter codes you display
                if (!in_array($status, ['P','L','M','S','H','E','A'], true)) { continue; }
    
                $this->Attendance_model->upsert([
                    'user_id'         => $user_id,
                    'attendance_date' => $attendance_date,
                    'status'          => $status,
                ]);
            }
        }
    
        $this->db->trans_complete();
    
        if (!$this->db->trans_status()) {
            set_alert('danger', 'Failed to save attendance.');
        } else {
            set_alert('success', 'Attendance updated successfully.');
        }
    
        // ← return to the same month the user was editing
        redirect('attendance?year='.$year.'&month='.$month);
    }



    /**
     * Delete attendance record (AJAX)
     * POST: user_id, attendance_date
     */
public function delete_leave()
{
    $leave_id = (int)$this->input->post('id');
    $current_user = (int)$this->session->userdata('user_id');

    if (!$leave_id) {
        set_alert('danger', 'Invalid leave ID.');
        redirect('attendance/leaves');
    }

    $this->load->model('attendance/Leaves_model');
    $leave = $this->Leaves_model->get_leave_by_id($leave_id);

    if (!$leave) {
        set_alert('danger', 'Leave not found.');
        redirect('attendance/leaves');
    }

    $this->Leaves_model->delete_leave($leave_id);
    set_alert('success', 'Leave deleted successfully.');
    redirect('attendance/leaves');
}


/**
 * Show logged-in user's own attendance for current month
 */
public function my()
{
    $currentUser = (int)$this->session->userdata('user_id');
    $currentYear  = date('Y');
    $currentMonth = date('m');

    $records = $this->Attendance_model->get_monthly_records($currentYear, $currentMonth, $currentUser);

    $data = [
        'title'       => 'My Attendance',
        'page_title'  => 'My Attendance',
        'records'     => $records,
    ];

    $this->load->view('layouts/master', [
        'subview'   => 'my_attendance',
        'view_data' => $data,
    ]);
}


public function export_csv($year = null, $month = null)
{
    $year  = $year ?: date('Y');
    $month = $month ?: date('m');
    $records = $this->Attendance_model->get_monthly_records($year, $month);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_'.$year.'_'.$month.'.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['User ID', 'Date', 'Status', 'Created At', 'Updated At']);

    foreach ($records as $r) {
        fputcsv($output, [
            $r['user_id'],
            $r['attendance_date'],
            $r['status'],
            $r['created_at'],
            $r['updated_at']
        ]);
    }
    fclose($output);
    exit;
}


protected function log_attendance_action($action, $user_id, $date, $status)
{
    $current_user_id = (int)$this->session->userdata('user_id');
    log_message('info', "Attendance $action: by $current_user_id for $user_id on $date [status:$status]");
}


// Leaves Management Systme

    /**
     * Leave Management
     */

public function leaves()
{
    $this->load->helper('form');
    $this->load->library('form_validation');
    $this->load->model('User_model');
    $this->load->model('Teams_model');

    $currentUser = (int)$this->session->userdata('user_id');
    $currentUserInfo = $this->User_model->get_user_by_id($currentUser);
    $role = $currentUserInfo['user_role'] ?? '';
    $isAdmin = ($role === 'admin');

    $this->load->model('attendance/Leaves_model');
    $leave_balances = $this->Leaves_model->get_leave_usage_summary($currentUser);

    // Determine whose leaves to show based on role
    if ($isAdmin) {
        $leaves = $this->Leaves_model->get_all_leaves();
    } else {
        $teamId = $currentUserInfo['emp_team'] ?? null;
        $teamLead = $teamId ? $this->Teams_model->get_team_lead($teamId) : null;
        $isTeamLead = $teamLead && $teamLead['id'] == $currentUser;
        if ($isTeamLead) {
            $teamMembers = $this->User_model->get_users_by_team($teamId);
            $memberIds = array_column($teamMembers, 'id');
            $leaves = $this->Leaves_model->get_leaves_by_users($memberIds, 'pending');
        } else {
            $leaves = $this->Leaves_model->get_leaves_by_user($currentUser);
        }
    }

    // Disabled dates logic (same)
    $disabled_dates = [];
    foreach ($leaves as $leave) {
        $current = new DateTime($leave['start_date']);
        $end = new DateTime($leave['end_date']);
        while ($current <= $end) {
            $disabled_dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
    }
    $disabled_dates = array_unique($disabled_dates);

    // ✅ Fetch leave_types from system settings
    $leave_types_setting = get_system_setting('leave_types');
    if (is_string($leave_types_setting)) {
        $leave_types = json_decode($leave_types_setting, true);
        $leave_types = is_array($leave_types) ? $leave_types : [];
    } elseif (is_array($leave_types_setting)) {
        $leave_types = $leave_types_setting;
    } else {
        $leave_types = [];
    }

    $this->load->view('layouts/master', [
        'subview'   => 'attendance/leaves',
        'view_data' => [
            'title'          => 'Leaves Management',
            'page_title'     => 'Leaves Management',
            'leaves'         => $leaves,
            'leave_types'    => $leave_types,
            'leave_balances' => $leave_balances,
            'disabled_dates' => $disabled_dates,
            'breadcrumbs'    => [
                ['title' => 'Dashboard', 'url' => site_url('dashboard')],
                ['title' => 'Leave Management', 'active' => true]
            ]
        ]
    ]);
}



    /**
     * Submit Leave Request
     */
    public function submit_leave_request()
    {
        $this->load->library('form_validation');
    
        $this->form_validation->set_rules('leave_type', 'Leave Type', 'required|trim');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required|trim');
        $this->form_validation->set_rules('end_date', 'End Date', 'required|trim');
        $this->form_validation->set_rules('leave_notes', 'Reason', 'required|trim');
    
        if ($this->form_validation->run() === false) {
            set_alert('danger', validation_errors());
            redirect('attendance/leaves');
        }
    
        $start_date = $this->input->post('start_date', true);
        $end_date   = $this->input->post('end_date', true);
    
        if (!strtotime($start_date) || !strtotime($end_date)) {
            set_alert('danger', 'Start and End dates must be valid.');
            redirect('attendance/leaves');
        }
    
        if (strtotime($end_date) < strtotime($start_date)) {
            set_alert('danger', 'End date must be after start date.');
            redirect('attendance/leaves');
        }
    
        $user_id = (int)$this->session->userdata('user_id');
    
        $leaveData = [
            'user_id'     => $user_id,
            'leave_type'  => $this->input->post('leave_type', true),
            'start_date'  => $start_date,
            'end_date'    => $end_date,
            'leave_notes' => $this->input->post('leave_notes', true),
            'status'      => 'pending',
        ];
    
        // Handle file upload if exists
        if (!empty($_FILES['leave_attachment']['name'])) {
            $rawUploadPath = FCPATH . 'uploads/attendance/';
            $uploadPath = realpath($rawUploadPath);
    
            // If realpath fails, fallback to raw path and ensure it exists
            if (!$uploadPath) {
                if (!is_dir($rawUploadPath)) {
                    mkdir($rawUploadPath, 0755, true);
                }
                $uploadPath = realpath($rawUploadPath);
            }
    
            if (!$uploadPath || !is_dir($uploadPath)) {
                log_message('error', 'Invalid upload path: ' . $rawUploadPath);
                set_alert('danger', 'Upload folder does not exist or is not writable.');
                redirect('attendance/leaves');
            }
    
            $config = [
                'upload_path'   => $uploadPath,
                'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
                'max_size'      => 2048,
                'encrypt_name'  => true,
            ];
    
            $this->load->library('upload');
            $this->upload->initialize($config);
    
            if ($this->upload->do_upload('leave_attachment')) {
                $uploadData = $this->upload->data();
                $leaveData['leave_attachment'] = $uploadData['file_name'];
                log_message('debug', 'Leave attachment uploaded: ' . $uploadData['file_name']);
            } else {
                $uploadError = $this->upload->display_errors('', '');
                log_message('error', 'Upload failed: ' . $uploadError);
                set_alert('danger', 'Attachment upload failed: ' . $uploadError);
                redirect('attendance/leaves');
            }
        }
    
        $leave_id = $this->Leaves_model->insert_leave($leaveData);
    
        if ($leave_id) {
            $this->notify_approvers($user_id, $leave_id);
            set_alert('success', 'Leave request submitted successfully!');
        } else {
            log_message('error', 'Failed to insert leave record for user: ' . $user_id);
            set_alert('danger', 'Failed to submit leave request.');
        }
    
        redirect('attendance/leaves');
    }

    /**
     * Notify admin and team lead about new leave request
     */
    private function notify_approvers($user_id, $leave_id)
    {
        $this->load->model('User_model');
        $this->load->model('Teams_model');
    
        $user = $this->User_model->get_user_by_id($user_id);
    
        if (!$user) return;
    
        $team_id = $user['emp_team'] ?? null;
    
        // Get team lead if exists
        $teamlead = $team_id ? $this->Teams_model->get_team_lead($team_id) : null;
    
        // Get all admins
        $admins = $this->User_model->get_users_by_role('admin');
    
        // Push notifications to DB or queue (you can enhance this)
        if ($teamlead) {
            // Send to team lead (if not user himself)
            if ($teamlead['id'] != $user_id) {
notify_user(
    $teamlead['id'],
    'attendance',
    'New leave request',
    staff_full_name() . ' has submitted a leave request.',
    'attendance/leaves/approve/' . $leave_id
);

    
                // Update notified_lead = 1
                $this->Leaves_model->update_leave($leave_id, ['notified_lead' => 1]);
            }
        }
    
        foreach ($admins as $admin) {
            if ($admin['id'] == $user_id) continue;
    
notify_user(
    $admin['id'],
    'attendance',
    'New leave request',
    staff_full_name() . ' has submitted a leave request.',
    'attendance/leaves/approve/' . $leave_id
);

        }
    
        // Update notified_admin = 1
        $this->Leaves_model->update_leave($leave_id, ['notified_admin' => 1]);
    }

public function view_leave_ajax($id)
{
    $leave = $this->Leaves_model->get_leave_by_id($id);
    if (!$leave) {
        echo '<div class="alert alert-danger">Leave record not found.</div>';
        return;
    }

    $this->load->view('attendance/ajax/view_leave_detail', ['leave' => $leave]);
}



public function process_leave_approval()
{
    $leave_id    = (int)$this->input->post('leave_id');
    $action      = $this->input->post('action');
    $approver_id = (int)$this->session->userdata('user_id');

    if (!in_array($action, ['approved', 'hold', 'rejected'])) {
        set_alert('danger', 'Invalid approval action.');
        redirect('attendance/leaves');
    }

    $leave = $this->Leaves_model->get_leave_by_id($leave_id);

    if (!$leave) {
        set_alert('warning', 'Leave not found.');
        redirect('attendance/leaves');
    }

//    if ($leave['status'] === 'approved') {
//        set_alert('warning', 'This leave has already been approved and cannot be modified.');
//        redirect('attendance/leaves');
//    }

    $this->Leaves_model->update_leave_status($leave_id, $action, $approver_id);

    // Optional: notify user
    notify_user(
        $leave['user_id'],
        'attendance',
        'Your leave request was ' . ucfirst($action),
        'Your leave request from ' . _d($leave['start_date']) . ' to ' . _d($leave['end_date']) . ' has been ' . ucfirst($action) . '.'
    );

    set_alert('success', 'Leave has been ' . $action . '.');
    redirect('attendance/leaves');
}


public function update_leave()
{
    $this->load->library('form_validation');
    $this->load->model('Leaves_model');
    $data['leave_balances'] = $this->Leaves_model->get_leave_usage_summary($this->session->userdata('user_id'));

    $this->form_validation->set_rules('leave_type', 'Leave Type', 'required|trim');
    $this->form_validation->set_rules('start_date', 'Start Date', 'required|trim');
    $this->form_validation->set_rules('end_date', 'End Date', 'required|trim');
    $this->form_validation->set_rules('leave_notes', 'Reason', 'required|trim');

    if ($this->form_validation->run() === false) {
        set_alert('danger', validation_errors());
        redirect('attendance/leaves');
    }

    $id = (int)$this->input->post('id');
    $data = [
        'leave_type'  => $this->input->post('leave_type', true),
        'start_date'  => $this->input->post('start_date', true),
        'end_date'    => $this->input->post('end_date', true),
        'leave_notes' => $this->input->post('leave_notes', true),
    ];

    // Handle file upload if exists
    if (!empty($_FILES['leave_attachment']['name'])) {
        $uploadPath = FCPATH . 'uploads/attendance/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $config = [
            'upload_path'   => $uploadPath,
            'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
            'max_size'      => 2048,
            'encrypt_name'  => true,
        ];

        $this->load->library('upload', $config);

        if ($this->upload->do_upload('leave_attachment')) {
            $uploadData = $this->upload->data();
            $data['leave_attachment'] = $uploadData['file_name'];
        } else {
            set_alert('danger', 'File upload failed: ' . strip_tags($this->upload->display_errors()));
            redirect('attendance/leaves');
        }
    }

    $updated = $this->Leaves_model->update_leave($id, $data);

    if ($updated) {
        set_alert('success', 'Leave updated successfully.');
    } else {
        set_alert('danger', 'Failed to update leave.');
    }

    redirect('attendance/leaves');
}


    // Attendance Tracker 
    
    public function tracker()
    {
        $this->load->model('Leaves_model');
        $this->load->model('Settings_model');
    
        $user_id = (int)$this->session->userdata('user_id');
    
        // ✅ Fetch leave usage
        $usage = $this->Leaves_model->get_leave_usage_summary($user_id);
    
        // ✅ Get default allowed values from system settings
        $default_medical  = (int)get_system_setting('medical_leaves') ?? 0;
        $default_casual   = (int)get_system_setting('casual_leaves') ?? 0;
        $default_holiday  = (int)get_system_setting('holiday_leaves') ?? 0;
    
$leave_balances = $this->Leaves_model->get_leave_usage_summary($user_id);
    
        // ✅ You can also fetch all leave entries if needed
        $leaves = $this->Leaves_model->get_leaves_by_user($user_id);
    
        // ✅ Load the view
        $this->load->view('layouts/master', [
            'subview'   => 'attendance/tracker',
            'view_data' => [
                'title'          => 'My Leaves Tracker',
                'page_title'     => 'My Leaves Tracker',
                'leave_balances' => $leave_balances,
                'leaves'         => $leaves,
            ]
        ]);
    }



// full calendar 

public function calendar()
{
    $this->load->model('Leaves_model');
    $this->load->model('User_model');
    $this->load->model('Attendance_model');

    $currentUser = (int)$this->session->userdata('user_id');
    $currentUserInfo = $this->User_model->get_user_by_id($currentUser);
    $role = $currentUserInfo['user_role'] ?? '';

    if ($role === 'admin') {
        $leaves = $this->Leaves_model->get_all_leaves();
        $users = $this->User_model->get_all_users();
        $userMap = [];
        foreach ($users as $u) $userMap[$u['id']] = $u;
        $attendanceRecords = $this->Attendance_model->get_all_records(); // New method, see below
    } else {
        $leaves = $this->Leaves_model->get_leaves_by_user($currentUser);
        $userMap = [$currentUser => $currentUserInfo];
        $attendanceRecords = $this->Attendance_model->get_records_by_user($currentUser);
    }

    // Prepare leave events
    $events = [];
    foreach ($leaves as $leave) {
        $u = $userMap[$leave['user_id']] ?? ['firstname'=>'','lastname'=>''];
        $events[] = [
            'id'    => 'leave_' . $leave['id'],
            'title' => ($role === 'admin' ? ($u['firstname'] . ' ' . $u['lastname'] . ' - ') : '') .
                        ucfirst($leave['leave_type']) . ' (' . ucfirst($leave['status']) . ')',
            'start' => $leave['start_date'],
            'end'   => date('Y-m-d', strtotime($leave['end_date'].' +1 day')),
            'color' => match(strtolower($leave['leave_type'])) {
                'casual'      => '#28a745',
                'medical'     => '#007bff',
                'emergency'   => '#dc3545',
                'short leave' => '#ffc107',
                'holiday'     => '#6c757d',
                default       => '#888'
            },
            'extendedProps' => [
                'user'   => $u['firstname'] . ' ' . $u['lastname'],
                'notes'  => $leave['leave_notes'],
                'status' => $leave['status'],
                'type'   => $leave['leave_type'],
                'event_type' => 'leave'
            ]
        ];
    }

    // Prepare attendance events (only single-day events)
    foreach ($attendanceRecords as $a) {

    $s = $a['status'];
    if ($s == 'P') continue; // Skip "Present" records
    
        $u = $userMap[$a['user_id']] ?? ['firstname'=>'','lastname'=>''];
        $statusMap = [
            'A' => ['label' => 'Absent',   'color' => '#e03131'],
            'L' => ['label' => 'Casual Leave', 'color' => '#82c91e'],
            'M' => ['label' => 'Medical Leave', 'color' => '#4263eb'],
            'S' => ['label' => 'Short Leave', 'color' => '#fab005'],
            'H' => ['label' => 'Holiday', 'color' => '#868e96'],
            'E' => ['label' => 'Emergency', 'color' => '#e8590c'],
        ];
        $s = $a['status'];
        $statusData = $statusMap[$s] ?? ['label'=>'Unknown','color'=>'#adb5bd'];

        $events[] = [
            'id'    => 'att_' . $a['user_id'] . '_' . $a['attendance_date'],
            'title' => ($role === 'admin' ? ($u['firstname'] . ' ' . $u['lastname'] . ' - ') : '') .
                        $statusData['label'],
            'start' => $a['attendance_date'],
            'end'   => $a['attendance_date'],
            'color' => $statusData['color'],
            'allDay' => true,
            'extendedProps' => [
                'user'   => $u['firstname'] . ' ' . $u['lastname'],
                'status' => $statusData['label'],
                'event_type' => 'attendance'
            ]
        ];
    }

    $this->load->view('layouts/master', [
        'subview' => 'attendance/calendar',
        'view_data' => [
            'page_title'     => 'Leaves Calendar',
            'events'         => $events,
            'is_admin'       => $role === 'admin'
        ]
    ]);
}

}