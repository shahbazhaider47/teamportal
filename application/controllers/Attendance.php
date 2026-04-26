<?php defined('BASEPATH') or exit('No direct script access allowed');

class Attendance extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Leaves_model');
        $this->load->model('Attendance_model');
        $this->load->model('User_model');
        $this->load->model('Teams_model');

        // Ensure the policy class file is included so direct instantiation works
        if (!class_exists('Attendance_policy')) {
            $this->load->library('Attendance_policy', ['user_id' => 0]); // preload file
        }    
    }

    /**
     * Safe factory — returns null if user has no shift/is inactive.
     * Use this everywhere instead of bare `new Attendance_policy(...)`.
     */
    private function _make_policy(int $user_id): ?Attendance_policy
    {
        if ($user_id <= 0) { return null; }
        try {
            // Ensure library file is loaded
            if (!class_exists('Attendance_policy')) {
                require_once APPPATH . 'libraries/Attendance_policy.php';
            }
            return new Attendance_policy(['user_id' => $user_id]);
        } catch (Exception $e) {
            log_message('error', "Attendance_policy failed uid={$user_id}: " . $e->getMessage());
            return null;
        }
    }
    
    public function index()
    {
        $year        = (int)($this->input->get('year') ?: date('Y'));
        $month       = (int)($this->input->get('month') ?: date('n'));
        $currentUser = (int)$this->session->userdata('user_id');
        $todayDay    = (int)date('j');
    
        if (!$currentUser) {
            redirect('authentication/login');
            return;
        }
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        if ($year < 1970 || $year > 2100) {
            $year = (int)date('Y');
        }
        $can_view_global = staff_can('view_global', 'attendance');
        $can_view_team   = staff_can('own_team', 'attendance');
        $can_view_own    = staff_can('view_own', 'attendance');
    
        if (!$can_view_global && !$can_view_team && !$can_view_own) {
            show_error('You do not have permission to view attendance records.', 403);
        }
        $canCreateAttendance = (bool)staff_can('create', 'attendance');
        $users   = [];
        $records = [];
        if ($can_view_global) {
            $users   = $this->User_model->get_all_users();
            $records = $this->Attendance_model->get_monthly_records($year, $month);
        } elseif ($can_view_team) {
            $me     = $this->User_model->get_user_by_id($currentUser);
            $teamId = $me['emp_team'] ?? null;
            if ($teamId) {
                $users    = $this->User_model->get_users_by_team($teamId);
                $user_ids = array_column($users, 'id');
                $records = !empty($user_ids)
                    ? $this->Attendance_model->get_monthly_records($year, $month, $user_ids)
                    : [];
            } else {
                $users   = [];
                $records = [];
            }
        } else {
            $users   = [$this->User_model->get_user_by_id($currentUser)];
            $records = $this->Attendance_model->get_monthly_records($year, $month, $currentUser);
        }
        $users = is_array($users) ? $users : [];
        $sort = strtolower(trim((string)$this->input->get('sort'))) ?: 'emp';
        $empNum = function ($empId) {
            $empId = (string)($empId ?? '');
            $tail  = (strpos($empId, '-') !== false) ? substr($empId, strrpos($empId, '-') + 1) : $empId;
            $num   = preg_replace('/\D+/', '', $tail);
            return (int)$num;
        };
        usort($users, function ($a, $b) use ($sort, $empNum) {
            if ($sort === 'created') {
                $ca = $a['created_at'] ?? '0000-00-00 00:00:00';
                $cb = $b['created_at'] ?? '0000-00-00 00:00:00';
                if ($ca === $cb) {
                    return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
                }
                return strcmp($ca, $cb);
            }
            $ea = $empNum($a['emp_id'] ?? '');
            $eb = $empNum($b['emp_id'] ?? '');
            if ($ea === $eb) {
                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            }
            return $ea <=> $eb;
        });
        $existing = [];
        foreach ($records as $row) {
            if (empty($row['attendance_date'])) continue;
            $day = (int)date('j', strtotime($row['attendance_date']));
            $existing[(int)$row['user_id']][$day] = $row['status'];
        }
        $prevMonth = $month - 1;
        $prevYear  = $year;
        $nextMonth = $month + 1;
        $nextYear  = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        $months = [
            1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
            7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'
        ];
        $monthLabel = ($months[$month] ?? date('M')) . ' ' . $year;
        $prevUrl    = base_url('attendance?year='.$prevYear.'&month='.$prevMonth);
        $nextUrl    = base_url('attendance?year='.$nextYear.'&month='.$nextMonth);
        $currentUrl = base_url('attendance?year='.date('Y').'&month='.date('n'));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $allDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $allDays[$d] = [
                'day'     => $d,
                'dateStr' => $dateStr,
                'dow'     => strtoupper(date('D', strtotime($dateStr))),
            ];
        }
        $cellMeta = [];
        $todayStr = date('Y-m-d');
        foreach ($users as $u) {
            $uid = (int)($u['id'] ?? 0);
            if (!$uid) continue;
            $policy = new Attendance_policy(['user_id' => $uid]);
            foreach ($allDays as $d => $dayInfo) {
                $dateStr = $dayInfo['dateStr'] ?? '';
                if ($dateStr === '') continue;
                $isLocked  = false;
                $reason    = '';
                $display   = '';
                $cellClass = '';
                $boxClass  = '';
                $existingVal = $existing[$uid][$d] ?? '';
                $existingVal = strtoupper(trim((string)$existingVal));
                $state = $policy->manualCellState($dateStr);
                if (!empty($state['is_locked'])) {
                    $isLocked  = true;
                    $reason    = (string)($state['reason'] ?? '');
                    $display   = (string)($state['display'] ?? '—');
                    $cellClass = (string)($state['cell_class'] ?? '');
                    $boxClass  = (string)($state['box_class'] ?? '');
                } else {
                    if ($dateStr < $todayStr) {
                        $isLocked  = true;
                        $reason    = 'Past date locked';
                        $cellClass = 'locked-past';
                        if (in_array($existingVal, ['P','C','M','S','A','NA'], true)) {
                            $display  = $existingVal;
                            $boxClass = 'status-' . $existingVal;
                        } else {
                            $display  = '—';
                            $boxClass = 'attendance-locked-box';
                        }
                    }
                    if ($dateStr > $todayStr) {
                        $isLocked  = true;
                        $reason    = 'Future date locked';
                        $cellClass = 'locked-future';
                       if (in_array($existingVal, ['P','C','M','S','A','NA'], true)) {
                            $display  = $existingVal;
                            $boxClass = 'status-' . $existingVal;
                        } else {
                            $display  = '—';
                            $boxClass = 'attendance-locked-box';
                        }
                    }

if ($dateStr === $todayStr) {

    // lock today if policy says window is closed
    if (!$policy->canEditAttendance($dateStr)) {
        $isLocked  = true;
        $reason    = 'Today locked (shift edit window closed)';
        $cellClass = 'locked-past';

        if (in_array($existingVal, ['P','C','M','S','A','NA'], true)) {
            $display  = $existingVal;
            $boxClass = 'status-' . $existingVal;
        } else {
            $display  = '—';
            $boxClass = 'attendance-locked-box';
        }
    } else {
        $isLocked  = false;
        $reason    = '';
        $display   = '';
        $cellClass = '';
        $boxClass  = '';
    }
}
                }
                $cellMeta[$uid][$d] = [
                    'is_locked'  => (bool)$isLocked,
                    'reason'     => (string)$reason,
                    'display'    => (string)$display,
                    'cell_class' => (string)$cellClass,
                    'box_class'  => (string)$boxClass,
                ];
            }
        }
        
        $this->load->view('layouts/master', [
            'subview' => 'attendance/manage',
            'view_data' => [
                'title'                => 'Attendance Dashboard',
                'page_title'           => 'Attendance Dashboard',
                'users'                => $users,
                'allDays'              => $allDays,
                'existing'             => $existing,
                'cellMeta'             => $cellMeta,
                'currentYear'          => $year,
                'currentMonth'         => $month,
                'todayDay'             => $todayDay,
                'table_id'             => 'attendanceTable',
                'monthLabel'           => $monthLabel,
                'prevUrl'              => $prevUrl,
                'nextUrl'              => $nextUrl,
                'currentUrl'           => $currentUrl,
                'canEditUi'            => false,
                'canCreateAttendance'  => $canCreateAttendance,
            ],
        ]);
    }


    public function save()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
        if (!staff_can('create', 'attendance')) {
            show_error('Unauthorized', 403);
        }
        $posted     = $this->input->post();
        $attendance = $posted['attendance'] ?? [];
        $year  = (int)($posted['year']  ?: date('Y'));
        $month = (int)($posted['month'] ?: date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        if ($year < 1970 || $year > 2100) {
            $year = (int)date('Y');
        }
        if (empty($attendance) || !is_array($attendance)) {
            set_alert('danger', 'No attendance data submitted.');
            redirect("attendance?year=$year&month=$month");
            return;
        }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $this->db->trans_start();
        foreach ($attendance as $user_id => $days) {
            $user_id = (int)$user_id;
            if (!$user_id || !is_array($days)) {
                continue;
            }
            $policy = new Attendance_policy(['user_id' => $user_id]);
            foreach ($days as $day => $status) {
                $day = (int)$day;
                if ($day < 1 || $day > $daysInMonth) {
                    continue;
                }
                $status = strtoupper(trim((string)$status));
                if (!in_array($status, ['P','C','M','S','H','E','A'], true)) {
                    continue;
                }

                $attendance_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                if (!$policy->canEditAttendance($attendance_date)) {
                    continue;
                }
                if ($policy->isPublicHoliday($attendance_date)) {
                    continue;
                }
                if (!$policy->isWorkingDay($attendance_date)) {
                    continue;
                }
                $this->Attendance_model->upsert([
                    'user_id'         => $user_id,
                    'attendance_date' => $attendance_date,
                    'status'          => $status,
                ]);
            }
        }
        $this->db->trans_complete();
        set_alert(
            $this->db->trans_status() ? 'success' : 'danger',
            $this->db->trans_status()
                ? 'Attendance updated successfully.'
                : 'Failed to save attendance.'
        );
        redirect("attendance?year=$year&month=$month");
    }

    public function logs()
    {
        $year        = (int)($this->input->get('year')  ?: date('Y'));
        $month       = (int)($this->input->get('month') ?: date('n'));
        $currentUser = (int)$this->session->userdata('user_id');
    
        if (!$currentUser) {
            redirect('authentication/login');
            return;
        }
    
        if ($month < 1 || $month > 12)      { $month = (int)date('n'); }
        if ($year  < 1970 || $year > 2100)  { $year  = (int)date('Y'); }
    
        $can_view_global = staff_can('view_global', 'attendance');
        $can_view_team   = staff_can('own_team',    'attendance');
        $can_view_own    = staff_can('view_own',    'attendance');
    
        if (!$can_view_global && !$can_view_team && !$can_view_own) {
            show_error('You do not have permission to view attendance logs.', 403);
        }
    
        /* ── Build $user_ids and $users ─────────────────────────── */
        $user_ids = [];
        $users    = [];
    
        if ($can_view_global) {
            $users    = $this->User_model->get_all_users();
            $users    = is_array($users) ? $users : [];
            $user_ids = array_values(array_filter(array_column($users, 'id')));
    
        } elseif ($can_view_team) {
            $me     = $this->User_model->get_user_by_id($currentUser);
            $teamId = is_array($me) ? ($me['emp_team'] ?? null) : null;
    
            if ($teamId) {
                $teamUsers = $this->User_model->get_users_by_team($teamId);
                $users     = is_array($teamUsers) ? $teamUsers : [];
                $user_ids  = array_values(array_filter(array_column($users, 'id')));
            }
    
        } else {
            // view_own — single user, must be an array for model methods
            $me = $this->User_model->get_user_by_id($currentUser);
            if (is_array($me) && !empty($me['id'])) {
                $users    = [$me];
                $user_ids = [(int)$me['id']];
            }
        }
    
        $perPage = 300;
        
        $page = max(1, (int)$this->input->get('page'));
        $offset = ($page - 1) * $perPage;
    
        $totalRows = !empty($user_ids)
            ? (int)$this->Attendance_model->count_monthly_logs($year, $month, $user_ids)
            : 0;
    
        $this->load->library('pagination');
    
        // Use site_url so CI routing works correctly
        // reuse_query_string keeps year/month on every page link
        $config = [
            'base_url' => site_url("attendance/logs"),
            'total_rows'           => $totalRows,
            'per_page'             => $perPage,
            'page_query_string'    => true,
            'query_string_segment' => 'page',
            'use_page_numbers'     => true,
            'reuse_query_string'   => true,
            'full_tag_open'        => '<ul class="pagination pagination-sm mb-0">',
            'full_tag_close'       => '</ul>',
            'first_link'           => 'First',
            'last_link'            => 'Last',
            'first_tag_open'       => '<li class="page-item">',
            'first_tag_close'      => '</li>',
            'last_tag_open'        => '<li class="page-item">',
            'last_tag_close'       => '</li>',
            'next_link'            => 'Next &rsaquo;',
            'next_tag_open'        => '<li class="page-item">',
            'next_tag_close'       => '</li>',
            'prev_link'            => '&lsaquo; Prev',
            'prev_tag_open'        => '<li class="page-item">',
            'prev_tag_close'       => '</li>',
            'cur_tag_open'         => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close'        => '</span></li>',
            'num_tag_open'         => '<li class="page-item">',
            'num_tag_close'        => '</li>',
            'attributes'           => ['class' => 'page-link'],
            'num_links'            => 5,
        ];
        $this->pagination->initialize($config);
    
        /* ── Fetch logs ──────────────────────────────────────────── */
        $logs = !empty($user_ids)
            ? $this->Attendance_model->get_monthly_logs_paginated(
                $year, $month, $user_ids, $perPage, $offset
              )
            : [];
    
        /* ── Month nav ───────────────────────────────────────────── */
        $prevMonth = $month - 1; $prevYear = $year;
        $nextMonth = $month + 1; $nextYear = $year;
        if ($prevMonth < 1)  { $prevMonth = 12; $prevYear--; }
        if ($nextMonth > 12) { $nextMonth = 1;  $nextYear++; }
    
        $months = [
            1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
            7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec',
        ];
    
        $monthLabel = ($months[$month] ?? date('M')) . ' ' . $year;
        $prevUrl    = site_url("attendance/logs?year={$prevYear}&month={$prevMonth}");
        $nextUrl    = site_url("attendance/logs?year={$nextYear}&month={$nextMonth}");
        $currentUrl = site_url('attendance/logs?year=' . date('Y') . '&month=' . date('n'));
    
        /* ── Policy metrics per log row ──────────────────────────── */
        $policyCache    = [];
        $lateMinutes    = [];
        $earlyMinutes   = [];
        $overtimeMinutes= [];
        $overtimeMeta   = [];
    
        foreach ($logs as $i => $row) {
            $uid = (int)($row['user_id'] ?? 0);
    
            if (!$uid) {
                $lateMinutes[$i]     = 0;
                $earlyMinutes[$i]    = 0;
                $overtimeMinutes[$i] = 0;
                $overtimeMeta[$i]    = ['is_exceeded' => false, 'max' => 0];
                continue;
            }
    
            // Example replacement in logs()
            if (!isset($policyCache[$uid])) {
                $policyCache[$uid] = $this->_make_policy($uid); // null if failed
            }
            
            $policy = $policyCache[$uid];
            if (!$policy) {
                $lateMinutes[$i] = $earlyMinutes[$i] = $overtimeMinutes[$i] = 0;
                $overtimeMeta[$i] = ['is_exceeded' => false, 'max' => 0];
                continue;
            }
    
            $policy = $policyCache[$uid];
    
            $lateMinutes[$i]     = $policy->lateMinutesForLog($row);
            $earlyMinutes[$i]    = $policy->earlyCheckoutForLog($row, 30);
            $ot                  = $policy->overtimeForLog($row);
            $overtimeMinutes[$i] = (int)($ot['minutes']     ?? 0);
            $overtimeMeta[$i]    = [
                'is_exceeded' => (bool)($ot['is_exceeded'] ?? false),
                'max'         => (int)($ot['max']          ?? 0),
            ];
        }
    
        /* ── Monthly summary for current user ────────────────────── */
        $totalDays = $totalOffDays = $totalHolidays = $totalWorkDays = 0;
        try {
            $policyMe       = new Attendance_policy(['user_id' => $currentUser]);
            $myMonthSummary = $policyMe->getMonthlyWorkingDaySummary($year, $month);
            $totalDays      = (int)($myMonthSummary['total_days']    ?? 0);
            $totalOffDays   = (int)($myMonthSummary['off_days']      ?? 0);
            $totalHolidays  = (int)($myMonthSummary['holiday_days']  ?? 0);
            $totalWorkDays  = (int)($myMonthSummary['working_days']  ?? 0);
        } catch (Exception $e) {
            log_message('error', 'Attendance_policy summary failed: ' . $e->getMessage());
        }
    
        $this->load->view('layouts/master', [
            'subview'    => 'attendance/logs',
            'view_data'  => [
                'page_title'       => 'Attendance Logs',
                'monthLabel'       => $monthLabel,
                'logs'             => $logs,
                'users'            => $users,
                'lateMinutes'      => $lateMinutes,
                'earlyMinutes'     => $earlyMinutes,
                'overtimeMinutes'  => $overtimeMinutes,
                'overtimeMeta'     => $overtimeMeta,
                'pagination_links' => $this->pagination->create_links(),
                'currentYear'      => $year,
                'currentMonth'     => $month,
                'prevUrl'          => $prevUrl,
                'nextUrl'          => $nextUrl,
                'currentUrl'       => $currentUrl,
                'totalDays'        => $totalDays,
                'totalOffDays'     => $totalOffDays,
                'totalHolidays'    => $totalHolidays,
                'totalWorkDays'    => $totalWorkDays,
                'totalRows'        => $totalRows,
                'perPage'          => $perPage,
                'currentPage'      => $page,
            ],
        ]);
    }

    public function user_logs()
    {
        $currentUser = (int)$this->session->userdata('user_id');
        $userId      = (int)$this->input->get('user_id');
        $year  = (int)($this->input->get('year') ?: date('Y'));
        $month = (int)($this->input->get('month') ?: date('n'));
        if (!$currentUser) {
            redirect('authentication/login');
            return;
        }
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        if ($year < 1970 || $year > 2100) {
            $year = (int)date('Y');
        }
        $canGlobal = staff_can('view_global', 'attendance');
        $canTeam   = staff_can('own_team', 'attendance');
        if ($canGlobal) {
            $staff_list = $this->User_model->get_all_users();
        } elseif ($canTeam) {
            $me     = $this->User_model->get_user_by_id($currentUser);
            $teamId = $me['emp_team'] ?? null;
    
            $staff_list = $teamId
                ? $this->User_model->get_users_by_team($teamId)
                : [];
        } else {
            $staff_list = [$this->User_model->get_user_by_id($currentUser)];
        }
        $prevMonth = $month - 1;
        $prevYear  = $year;
        $nextMonth = $month + 1;
        $nextYear  = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        $months = [
            1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
            7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'
        ];
        $monthLabel = ($months[$month] ?? date('M')) . ' ' . $year;
        $prevUrl    = base_url("attendance/user_logs?user_id={$userId}&year={$prevYear}&month={$prevMonth}");
        $nextUrl    = base_url("attendance/user_logs?user_id={$userId}&year={$nextYear}&month={$nextMonth}");
        $currentUrl = base_url("attendance/user_logs?user_id={$userId}&year=" . date('Y') . "&month=" . date('n'));
        $firstDay = date('Y-m-01', strtotime("$year-$month-01"));
        $lastDay  = date('Y-m-t', strtotime($firstDay));
        if (!$userId) {
            $this->load->view('layouts/master', [
                'subview' => 'attendance/user_logs',
                'view_data' => [
                    'page_title'             => 'Attendance Logs',
                    'user_id'                => null,
                    'logs'                   => [],
                    'staff_list'             => $staff_list,
                    'currentYear'            => $year,
                    'currentMonth'           => $month,
                    'monthLabel'             => $monthLabel,
                    'prevUrl'                => $prevUrl,
                    'nextUrl'                => $nextUrl,
                    'currentUrl'             => $currentUrl,
                    'firstDay'               => $firstDay,
                    'lastDay'                => $lastDay,
                    'lateMinutes'            => [],
                    'totalLateMinutes'       => 0,
                    'earlyMinutes'           => [],
                    'totalEarlyMinutes'      => 0,
                    'totalEarlyCheckouts'    => 0,
                    'overtimeMinutes'        => [],
                    'overtimeMeta'           => [],
                    'totalOvertimeMinutes'   => 0,
                    'totalCheckIns'          => 0,
                    'totalCheckOuts'         => 0,
                    'totalOTDays'            => 0,
                    'shift'                  => [],
                ],
            ]);
            return;
        }
        if (!$canGlobal && !$canTeam && $userId !== $currentUser) {
            show_error('You do not have permission to view this user logs.', 403);
        }
        if ($canTeam && !$canGlobal) {
            $allowedIds = array_column($staff_list, 'id');
            if (!in_array($userId, $allowedIds, true)) {
                show_error('You cannot view logs outside your team.', 403);
            }
        }
        $logs = $this->Attendance_model->get_user_logs_filtered(
            $userId,
            $firstDay,
            $lastDay,
            null,
            null,
            null,
            null,
            0
        );
        $this->load->library('Attendance_policy', ['user_id' => $userId], 'att_policy');
        $lateMinutes = [];
        $totalLateMinutes = 0;
        foreach ($logs as $i => $row) {
            $late = $this->att_policy->lateMinutesForLog($row);
    
            $lateMinutes[$i] = $late;
            $totalLateMinutes += $late;
        }
        $earlyMinutes = [];
        $totalEarlyMinutes = 0;
        $totalEarlyCheckouts = 0;
        foreach ($logs as $i => $row) {
            $early = $this->att_policy->earlyCheckoutForLog($row, 30);
            $earlyMinutes[$i] = $early;
            if ($early > 0) {
                $totalEarlyMinutes += $early;
                $totalEarlyCheckouts++;
            }
        }
        $overtimeMinutes = [];
        $overtimeMeta    = [];
        $totalOvertimeMinutes = 0;
        foreach ($logs as $i => $row) {
            $ot = $this->att_policy->overtimeForLog($row);
            $overtimeMinutes[$i] = (int)($ot['minutes'] ?? 0);
            $overtimeMeta[$i] = [
                'is_exceeded' => (bool)($ot['is_exceeded'] ?? false),
                'max'         => (int)($ot['max'] ?? 0),
            ];
            $totalOvertimeMinutes += (int)($ot['minutes'] ?? 0);
        }
        $totalCheckIns  = 0;
        $totalCheckOuts = 0;
        $otDays = [];
        foreach ($logs as $i => $row) {
            $status = $row['status'] ?? '';
            if ($status === 'check_in') {
                $totalCheckIns++;
            }
            if ($status === 'check_out') {
                $totalCheckOuts++;
            }
            $ot = (int)($overtimeMinutes[$i] ?? 0);
            if ($ot > 0 && !empty($row['datetime'])) {
                $d = date('Y-m-d', strtotime($row['datetime']));
                $otDays[$d] = true;
            }
        }
        $totalOTDays = count($otDays);
        $policyMe = new Attendance_policy(['user_id' => $currentUser]);
        $myMonthSummary = $policyMe->getMonthlyWorkingDaySummary($year, $month);
        $totalDays      = (int)($myMonthSummary['total_days'] ?? 0);
        $totalOffDays   = (int)($myMonthSummary['off_days'] ?? 0);
        $totalHolidays  = (int)($myMonthSummary['holiday_days'] ?? 0);
        $totalWorkDays  = (int)($myMonthSummary['working_days'] ?? 0);
        
        $this->load->view('layouts/master', [
            'subview' => 'attendance/user_logs',
            'view_data' => [
                'page_title'            => 'Attendance Logs',
                'user_id'               => $userId,
                'logs'                  => $logs,
                'staff_list'            => $staff_list,
                'currentYear'           => $year,
                'currentMonth'          => $month,
                'monthLabel'            => $monthLabel,
                'prevUrl'               => $prevUrl,
                'nextUrl'               => $nextUrl,
                'currentUrl'            => $currentUrl,
                'firstDay'              => $firstDay,
                'lastDay'               => $lastDay,
                'lateMinutes'           => $lateMinutes,
                'totalLateMinutes'      => $totalLateMinutes,
                'earlyMinutes'          => $earlyMinutes,
                'totalEarlyMinutes'     => $totalEarlyMinutes,
                'totalEarlyCheckouts'   => $totalEarlyCheckouts,
                'overtimeMinutes'       => $overtimeMinutes,
                'overtimeMeta'          => $overtimeMeta,
                'totalOvertimeMinutes'  => $totalOvertimeMinutes,
                'totalCheckIns'         => $totalCheckIns,
                'totalCheckOuts'        => $totalCheckOuts,
                'totalOTDays'           => $totalOTDays,
                'totalDays'             => $totalDays,
                'totalOffDays'          => $totalOffDays,
                'totalHolidays'         => $totalHolidays,
                'totalWorkDays'         => $totalWorkDays,
                'shift'                 => $this->att_policy->shift(),
            ],
        ]);
    }

    public function my_attendance()
    {
        $currentUser = (int)$this->session->userdata('user_id');
        $year        = (int)($this->input->get('year') ?: date('Y'));
        $month       = (int)($this->input->get('month') ?: date('n'));
        if (!$currentUser) {
            redirect('authentication/login');
            return;
        }
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        if ($year < 1970 || $year > 2100) {
            $year = (int)date('Y');
        }
        $user = $this->User_model->get_user_by_id($currentUser);
        if (empty($user)) {
            show_error('User not found.', 404);
            return;
        }
        $firstDay = date('Y-m-01', strtotime("$year-$month-01"));
        $lastDay  = date('Y-m-t', strtotime($firstDay));
        $logs = $this->Attendance_model->get_user_logs_filtered(
            $currentUser,
            $firstDay,
            $lastDay,
            null,
            null,
            null,
            null,
            0
        );
        $this->load->library('Attendance_policy', ['user_id' => $currentUser], 'att_policy');
        $lateMinutes = [];
        $totalLateMinutes = 0;
        foreach ($logs as $i => $row) {
            $late = $this->att_policy->lateMinutesForLog($row);
            $lateMinutes[$i] = $late;
            $totalLateMinutes += $late;
        }
        $earlyMinutes = [];
        $totalEarlyMinutes = 0;
        $totalEarlyCheckouts = 0;
        foreach ($logs as $i => $row) {
            $early = $this->att_policy->earlyCheckoutForLog($row, 30);
            $earlyMinutes[$i] = $early;
            if ($early > 0) {
                $totalEarlyMinutes += $early;
                $totalEarlyCheckouts++;
            }
        }
        $overtimeMinutes = [];
        $overtimeMeta    = [];
        $totalOvertimeMinutes = 0;
        foreach ($logs as $i => $row) {
            $ot = $this->att_policy->overtimeForLog($row);
            $overtimeMinutes[$i] = (int)($ot['minutes'] ?? 0);
            $overtimeMeta[$i] = [
                'is_exceeded' => (bool)($ot['is_exceeded'] ?? false),
                'max'         => (int)($ot['max'] ?? 0),
            ];
            $totalOvertimeMinutes += (int)($ot['minutes'] ?? 0);
        }
        $totalCheckIns  = 0;
        $totalCheckOuts = 0;
        $otDays = [];
        foreach ($logs as $i => $row) {
            $status = $row['status'] ?? '';
            if ($status === 'check_in') {
                $totalCheckIns++;
            }
            if ($status === 'check_out') {
                $totalCheckOuts++;
            }
            $ot = (int)($overtimeMinutes[$i] ?? 0);
            if ($ot > 0 && !empty($row['datetime'])) {
                $d = date('Y-m-d', strtotime($row['datetime']));
                $otDays[$d] = true;
            }
        }
        $totalOTDays = count($otDays);
        $prevMonth = $month - 1;
        $prevYear  = $year;
        $nextMonth = $month + 1;
        $nextYear  = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        $months = [
            1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
            7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'
        ];
        $monthLabel         = ($months[$month] ?? date('M')) . ' ' . $year;
        $prevUrl            = base_url("attendance/my_attendance?year=$prevYear&month=$prevMonth");
        $nextUrl            = base_url("attendance/my_attendance?year=$nextYear&month=$nextMonth");
        $currentUrl         = base_url("attendance/my_attendance?year=" . date('Y') . "&month=" . date('n'));
        $policyMe           = new Attendance_policy(['user_id' => $currentUser]);
        $myMonthSummary     = $policyMe->getMonthlyWorkingDaySummary($year, $month);
        $totalDays          = (int)($myMonthSummary['total_days'] ?? 0);
        $totalOffDays       = (int)($myMonthSummary['off_days'] ?? 0);
        $totalHolidays      = (int)($myMonthSummary['holiday_days'] ?? 0);
        $totalWorkDays      = (int)($myMonthSummary['working_days'] ?? 0);
    
        $this->load->view('layouts/master', [
            'subview' => 'attendance/my_attendance',
            'view_data' => [
                'page_title'   => 'My Attendance Logs',
                'user'         => $user,
                'user_id'      => $currentUser,
                'logs'         => $logs,
                'lateMinutes'      => $lateMinutes,
                'totalLateMinutes' => $totalLateMinutes,
                'shift'            => $this->att_policy->shift(),
                'earlyMinutes'        => $earlyMinutes,
                'totalEarlyMinutes'   => $totalEarlyMinutes,
                'totalEarlyCheckouts' => $totalEarlyCheckouts,
                'overtimeMinutes'      => $overtimeMinutes,
                'overtimeMeta'         => $overtimeMeta,
                'totalOvertimeMinutes' => $totalOvertimeMinutes,
                'totalCheckIns'  => $totalCheckIns,
                'totalCheckOuts' => $totalCheckOuts,
                'totalOTDays'    => $totalOTDays,
                'currentYear'  => $year,
                'currentMonth' => $month,
                'monthLabel'   => $monthLabel,
                'prevUrl'      => $prevUrl,
                'nextUrl'      => $nextUrl,
                'currentUrl'   => $currentUrl,
                'firstDay'     => $firstDay,
                'lastDay'      => $lastDay,
                'totalDays'     => $totalDays,
                'totalOffDays'  => $totalOffDays,
                'totalHolidays' => $totalHolidays,
                'totalWorkDays' => $totalWorkDays,
            ],
        ]);
    }
    
    public function update_logs()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
        if (!staff_can('create', 'attendance')) {
            show_error('Unauthorized', 403);
        }
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }
        $currentUser    = (int)$this->session->userdata('user_id');
        $userId         = (int)$this->input->post('user_id');
        $year           = (int)$this->input->post('year');
        $month          = (int)$this->input->post('month');
        if ($userId <= 0) {
            set_alert('danger', 'Invalid user selected.');
            redirect('attendance/user_logs');
            return;
        }
        if ($month < 1 || $month > 12) $month = (int)date('n');
        if ($year < 1970 || $year > 2100) $year = (int)date('Y');
        $rows = $this->input->post('attendance_logs');
        if (empty($rows) || !is_array($rows)) {
            set_alert('danger', 'No log data submitted.');
            redirect("attendance/user_logs?user_id={$userId}&year={$year}&month={$month}");
            return;
        }
        $allowedStatus   = ['check_in','check_out','overtime_in','overtime_out','other'];
        $allowedLogType  = ['AUTO','MANUAL','CORRECTION'];
        $allowedApproval = ['APPROVED','PENDING','REJECTED'];
        $updatedCount = 0;
        $this->db->trans_start();
        foreach ($rows as $logId => $data) {
            $logId = (int)$logId;
            if ($logId <= 0 || !is_array($data)) continue;
            $existing = $this->Attendance_model->get_log_by_id($logId);
            if (!$existing) continue;
            if ((int)$existing['user_id'] !== $userId) continue;
            $logMonth = (int)date('n', strtotime($existing['datetime']));
            $logYear  = (int)date('Y', strtotime($existing['datetime']));
            if ($logMonth !== $month || $logYear !== $year) {
                continue;
            }
            $datetime = trim((string)($data['datetime'] ?? ''));
            $status   = strtolower(trim((string)($data['status'] ?? '')));
            $approval = strtoupper(trim((string)($data['approval_status'] ?? 'APPROVED')));
            $origDT   = trim((string)($data['original_datetime'] ?? ''));
            $origStat = strtolower(trim((string)($data['original_status'] ?? '')));
            $origType = strtoupper(trim((string)($data['original_log_type'] ?? 'AUTO')));
            if (!in_array($status, $allowedStatus, true)) continue;
            if (!in_array($approval, $allowedApproval, true)) {
                $approval = 'APPROVED';
            }
            $dtObj = DateTime::createFromFormat('Y-m-d\TH:i', $datetime);
            if (!$dtObj) continue;
            $newDatetime = $dtObj->format('Y-m-d H:i:s');
            $origObj = DateTime::createFromFormat('Y-m-d\TH:i', $origDT);
            $oldDatetime = $origObj ? $origObj->format('Y-m-d H:i:s') : (string)$existing['datetime'];
            $datetimeChanged = ($newDatetime !== $oldDatetime);
            $statusChanged   = ($status !== $origStat);
            $logType = $origType;
            if (!in_array($logType, $allowedLogType, true)) {
                $logType = strtoupper(trim((string)($existing['log_type'] ?? 'AUTO')));
                if (!in_array($logType, $allowedLogType, true)) {
                    $logType = 'AUTO';
                }
            }
            if ($datetimeChanged || $statusChanged) {
                $logType = 'CORRECTION';
            }
            $update = [
                'datetime'        => $newDatetime,
                'status'          => $status,
                'log_type'        => $logType,
                'approval_status' => $approval,
                'updated_by'      => $currentUser,
                'updated_at'      => date('Y-m-d H:i:s'),
            ];
            if ($approval === 'APPROVED') {
                $update['approved_by'] = $currentUser;
                $update['approved_at'] = date('Y-m-d H:i:s');
            } else {
                $update['approved_by'] = null;
                $update['approved_at'] = null;
            }
            $anythingChanged = (
                $newDatetime !== (string)$existing['datetime'] ||
                $status !== (string)$existing['status'] ||
                strtoupper($approval) !== strtoupper((string)$existing['approval_status']) ||
                strtoupper($logType) !== strtoupper((string)$existing['log_type'])
            );
            if (!$anythingChanged) {
                continue;
            }
            $this->db->where('id', $logId);
            $this->db->where('deleted_at IS NULL', null, false);
            $ok = $this->db->update('attendance_logs', $update);
            if ($ok) {
                $updatedCount++;
            }
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            set_alert('danger', 'Failed to update attendance logs.');
        } else {
            set_alert('success', "{$updatedCount} log(s) updated successfully.");
        }
        redirect("attendance/user_logs?user_id={$userId}&year={$year}&month={$month}");
    }


    /**
     * GET /attendance/get_log/{id}
     * Returns a single attendance_log row as JSON for the edit modal.
     */
    public function get_log($log_id = 0)
    {
        if (!$this->session->userdata('is_logged_in')) {
            $this->_json(['error' => 'Unauthenticated'], 401); return;
        }
        if (!staff_can('create', 'attendance')) {
            $this->_json(['error' => 'Permission denied'], 403); return;
        }
    
        $log_id = (int)$log_id;
        if ($log_id <= 0) {
            $this->_json(['error' => 'Invalid log ID'], 400); return;
        }
    
        $row = $this->db
            ->where('id', $log_id)
            ->get('attendance_logs')
            ->row_array();
    
        if (!$row) {
            $this->_json(['error' => 'Record not found'], 404); return;
        }
    
        // Append the user's display name so the modal can show it
        // In get_log() — replace the existing user query
        $user = $this->db
            ->select('id, firstname, lastname, emp_id, user_role, profile_image')
            ->where('id', (int)$row['user_id'])
            ->get('users')
            ->row_array();
        
        $row['user_name']    = $user
            ? trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''))
            : '';
        $row['user_emp_id']  = $user['emp_id']        ?? '';
        $row['user_role']    = $user['user_role']      ?? '';
        $row['user_avatar']  = $user['profile_image'] ?? '';

        // Avatar only — use user_profile_small which renders just the avatar
        $row['user_profile_html'] = function_exists('user_profile_small')
            ? user_profile_small((int)$row['user_id'])
            : '';
        
        // Full name
        $row['user_full_name'] = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        
        // Emp ID with prefix
        $row['user_empid_html'] = function_exists('emp_id_display')
            ? emp_id_display($user['emp_id'] ?? '')
            : html_escape($user['emp_id'] ?? '');
            
        // Add managers list for the approved_by dropdown
        $row['managers'] = $this->db
            ->select('id, firstname, lastname, emp_id')
            ->from('users')
            ->where('is_active', 1)
            ->where('LOWER(user_role)', 'manager')
            ->order_by('firstname', 'ASC')
            ->order_by('lastname',  'ASC')
            ->get()
            ->result_array();
            
        $this->_json($row);
    }
    
    public function update_log($log_id = 0)
    {
        if (!$this->session->userdata('is_logged_in')) {
            $this->_json(['success' => false, 'message' => 'Unauthenticated']); return;
        }
        if (!staff_can('create', 'attendance')) {
            $this->_json(['success' => false, 'message' => 'Permission denied']); return;
        }
        if ($this->input->method() !== 'post') {
            $this->_json(['success' => false, 'message' => 'POST required']); return;
        }
    
        $log_id = (int)$log_id;
        if ($log_id <= 0) {
            set_alert('warning', 'Invalid log ID.');
            $this->_json(['success' => false, 'message' => 'Invalid log ID.']); return;
        }
    
        $existing = $this->db
            ->where('id', $log_id)
            ->get('attendance_logs')
            ->row_array();
    
        if (!$existing) {
            set_alert('warning', 'Attendance log record not found.');
            $this->_json(['success' => false, 'message' => 'Record not found.']); return;
        }
    
        $currentUser = (int)$this->session->userdata('user_id');
    
        /* ── Sanitise & validate ─────────────────────────────────── */
        $allowedStatus   = ['check_in','check_out','overtime_in','overtime_out','other'];
        $allowedLogType  = ['AUTO','MANUAL','CORRECTION'];
        $allowedApproval = ['APPROVED','PENDING','REJECTED'];
    
        $datetime       = trim((string)$this->input->post('datetime',        true));
        $status         = strtolower(trim((string)$this->input->post('status',          true)));
        $logType        = strtoupper(trim((string)$this->input->post('log_type',        true)));
        $deviceId       = trim((string)$this->input->post('device_id',       true));
        $ipAddress      = trim((string)$this->input->post('ip_address',      true));
        $approvalStatus = strtoupper(trim((string)$this->input->post('approval_status', true)));
        $approvedBy     = (int)$this->input->post('approved_by');
        $approvedAtRaw  = trim((string)$this->input->post('approved_at',     true));
    
        if (!in_array($status, $allowedStatus, true)) {
            set_alert('warning', 'Invalid status value submitted.');
            $this->_json(['success' => false, 'message' => 'Invalid status value.']); return;
        }
    
        if (!in_array($logType, $allowedLogType, true)) {
            $logType = 'CORRECTION';
        }
    
        if (!in_array($approvalStatus, $allowedApproval, true)) {
            $approvalStatus = 'APPROVED';
        }
    
        // Parse datetime
        $dtObj = DateTime::createFromFormat('Y-m-d H:i:s', $datetime)
              ?: DateTime::createFromFormat('Y-m-d H:i',   $datetime);
    
        if (!$dtObj) {
            set_alert('warning', 'Invalid datetime format. Expected YYYY-MM-DD HH:MM:SS.');
            $this->_json(['success' => false, 'message' => 'Invalid datetime format.']); return;
        }
        $newDatetime = $dtObj->format('Y-m-d H:i:s');
    
        // Parse approved_at — optional
        $newApprovedAt = null;
        if ($approvedAtRaw !== '') {
            $atObj = DateTime::createFromFormat('Y-m-d H:i:s', $approvedAtRaw)
                  ?: DateTime::createFromFormat('Y-m-d H:i',   $approvedAtRaw);
            if ($atObj) {
                $newApprovedAt = $atObj->format('Y-m-d H:i:s');
            }
        }
    
        // Force CORRECTION if core fields changed
        $changed = (
            $newDatetime !== (string)$existing['datetime']            ||
            $status      !== strtolower((string)$existing['status'])  ||
            $logType     !== strtoupper((string)$existing['log_type'])
        );
        if ($changed && $logType === 'AUTO') {
            $logType = 'CORRECTION';
        }
    
        /* ── Build update payload ────────────────────────────────── */
        $update = [
            'datetime'        => $newDatetime,
            'status'          => $status,
            'log_type'        => $logType,
            'device_id'       => $deviceId  !== '' ? $deviceId  : null,
            'ip_address'      => $ipAddress !== '' ? $ipAddress : null,
            'approval_status' => $approvalStatus,
            'approved_by'     => $approvedBy > 0   ? $approvedBy : null,
            'approved_at'     => $newApprovedAt,
            'updated_by'      => $currentUser,
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
    
        if ($approvalStatus === 'APPROVED' && !$approvedBy) {
            $update['approved_by'] = $currentUser;
            if (!$newApprovedAt) {
                $update['approved_at'] = date('Y-m-d H:i:s');
            }
        }
    
        if ($approvalStatus !== 'APPROVED') {
            $update['approved_by'] = null;
            $update['approved_at'] = null;
        }
    
        /* ── Execute ─────────────────────────────────────────────── */
        $ok = $this->db
            ->where('id', $log_id)
            ->update('attendance_logs', $update);
    
        if (!$ok) {
            set_alert('danger', 'Database error — attendance log #' . $log_id . ' could not be updated.');
            $this->_json(['success' => false, 'message' => 'Database update failed.']); return;
        }
    
        log_message('info', "Attendance log #{$log_id} updated by user #{$currentUser}");
    
        set_alert('success', 'Attendance log #' . $log_id . ' updated successfully.');
    
        $this->_json([
            'success' => true,
            'message' => 'Log #' . $log_id . ' updated successfully.',
            'log_id'  => $log_id,
        ]);
    }
        
    /**
     * Shared JSON response helper.
     * Outputs JSON and stops execution.
     */
    private function _json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }


}