<?php defined('BASEPATH') or exit('No direct script access allowed');

class Attendance_leaves extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Leaves_model');
        $this->load->model('User_model');
        $this->load->model('Teams_model');
        $this->load->helper(['url', 'form']);
        $this->load->library(['session', 'form_validation']);
    }

    public function manage_leaves()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        $currentRole  = strtolower((string)($this->session->userdata('user_role') ?? ''));
        $isSuperAdmin = ($currentRole === 'superadmin');
    
        if (!$isSuperAdmin && !staff_can('view_own', 'attendance') && !staff_can('view_global', 'attendance')) {
            show_error('Unauthorized', 403);
        }
    
        $canCreate     = $isSuperAdmin || staff_can('create', 'attendance');
        $canApply      = $isSuperAdmin || staff_can('apply', 'attendance');
        $canViewGlobal = $isSuperAdmin || staff_can('view_global', 'attendance');
        $canOwnTeam    = $isSuperAdmin || staff_can('own_team', 'attendance');
    
        $layout_data = [
            'page_title' => 'Manage Leaves',
            'subview'    => 'attendance/manage_leaves',
            'view_data'  => [
                'page_title'       => 'Manage Leaves',
                'table_id'         => 'leavesTable',
                'leaves'           => $this->Leaves_model->get_all_leaves_with_meta(),
                'leave_types'      => $this->Leaves_model->get_leave_types(),
                'users'            => $this->Leaves_model->get_active_users(),
                'departments'      => $this->Leaves_model->get_departments(),
                'stats'            => $this->Leaves_model->get_leave_stats_summary(),
                // permission flags consumed by the modal partial
                'can_create'       => $canCreate,
                'can_apply'        => $canApply,
                'can_view_global'  => $canViewGlobal,
                'can_own_team'     => $canOwnTeam,
                'is_super_admin'   => $isSuperAdmin,
            ],
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }

    public function ajax_check_leave_policy()
    {
        if ($this->input->method() !== 'post') show_404();
        $leaveTypeId = (int)$this->input->post('leave_type_id');
        $fromDate    = $this->input->post('from_date', true);
        $toDate      = $this->input->post('to_date', true);
        $userId = (int)$this->input->post('user_id');
        if ($userId <= 0) {
            $userId = (int)$this->session->userdata('user_id');
        }
        $requestedQty = (float)$this->input->post('requested_qty');
        $this->load->library('Attendance_policy', ['user_id' => $userId], 'policy');
        $result = $this->policy->evaluateLeaveApplication(
            $leaveTypeId,
            $fromDate,
            $toDate,
            [
                'requested_qty' => $requestedQty,
                'mode'          => $this->input->post('mode', true),
                'start_time'    => $this->input->post('start_time', true),
                'end_time'      => $this->input->post('end_time', true),
            ]
        );
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }
    
    public function create()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }
    
        $currentUser   = (int)$this->session->userdata('user_id');
        $currentRole   = strtolower((string)($this->session->userdata('user_role') ?? ''));
        $isSuperAdmin  = ($currentRole === 'superadmin');
    
        // ── Resolve permissions ───────────────────────────────────────────────────
        $canCreate     = $isSuperAdmin || staff_can('create', 'attendance');
        $canApply      = $isSuperAdmin || staff_can('apply', 'attendance');
        $canViewGlobal = $isSuperAdmin || staff_can('view_global', 'attendance');
        $canOwnTeam    = $isSuperAdmin || staff_can('own_team', 'attendance');
    
        if (!$canCreate && !$canApply) {
            show_error('Unauthorized', 403);
            return;
        }
    
        $payload       = $this->input->post('payload');
        if (!is_array($payload)) $payload = [];
    
        $target_user_id = (int)($payload['user_id'] ?? 0);
    
        // ── Determine who the leave is for ───────────────────────────────────────
        // canCreate + canViewGlobal = can create for any user
        // canCreate + canOwnTeam   = can create only for own team members
        // canApply only            = can only apply for self
    
        $applyingForSelf = ($target_user_id === $currentUser || $target_user_id === 0);
    
        if (!$applyingForSelf) {
            // Someone other than self: need create permission
            if (!$canCreate) {
                set_alert('danger', 'You do not have permission to create leave for other users.');
                redirect('attendance/all_leaves');
                return;
            }
    
            // own_team restriction: verify target is in manager's team
            if (!$canViewGlobal && $canOwnTeam) {
                $isTeamMember = $this->_is_team_member($currentUser, $target_user_id);
                if (!$isTeamMember) {
                    set_alert('danger', 'You can only create leave for members of your team.');
                    redirect('attendance/all_leaves');
                    return;
                }
            }
        } else {
            // Applying for self: need apply permission (create also suffices)
            if (!$canApply && !$canCreate) {
                set_alert('danger', 'You do not have permission to apply for leave.');
                redirect('attendance/all_leaves');
                return;
            }
            $target_user_id = $currentUser; // normalize
        }
    
        // ── Parse fields ─────────────────────────────────────────────────────────
        $leave_type_id = (int)($payload['leave_type_id'] ?? 0);
        $start_date    = trim((string)($payload['start_date'] ?? ''));
        $end_date      = trim((string)($payload['end_date'] ?? ''));
        $start_time    = trim((string)($payload['start_time'] ?? ''));
        $end_time      = trim((string)($payload['end_time'] ?? ''));
        $reason        = trim((string)($payload['reason'] ?? ''));
    
        // Status: only admins (create+view_global) or superadmin can pre-set status
        // Self-applicants always start at pending
        $requestedStatus = strtolower(trim((string)($payload['status'] ?? 'pending')));
        if (!in_array($requestedStatus, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $requestedStatus = 'pending';
        }
    
        if ($applyingForSelf && !$canCreate) {
            // Pure applicants cannot set status — always pending
            $status = 'pending';
        } else {
            $status = $requestedStatus;
        }
    
        // ── Policy evaluation ─────────────────────────────────────────────────────
        // Note: policy already bypasses everything for superadmin users
        $this->load->library('Attendance_policy', ['user_id' => $target_user_id], 'policy');
    
        $requestedQty = isset($payload['total_days']) ? (float)$payload['total_days'] : 0;
    
        $policyResult = $this->policy->evaluateLeaveApplication(
            $leave_type_id,
            $start_date,
            $end_date,
            [
                'requested_qty' => $requestedQty,
                'mode'          => (string)($payload['mode'] ?? 'full'),
                'start_time'    => $start_time,
                'end_time'      => $end_time,
            ]
        );
    
        if (!empty($policyResult['blocked'])) {
            $msg = !empty($policyResult['errors'])
                ? implode('<br>', array_map('html_escape', $policyResult['errors']))
                : 'Leave request blocked by policy.';
            set_alert('danger', $msg);
            redirect('attendance/all_leaves');
            return;
        }
    
        // ── Attachment ────────────────────────────────────────────────────────────
        $attachmentRequired = !empty($policyResult['requires_attachment']);
        if ($attachmentRequired && empty($_FILES['attachment']['name'])) {
            set_alert('danger', 'Attachment is required for this leave type.');
            redirect('attendance/all_leaves');
            return;
        }
    
        $attachment_path = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadPath = FCPATH . 'uploads/leaves/';
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0755, true);
            }
            $this->load->library('upload', [
                'upload_path'   => $uploadPath,
                'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
                'max_size'      => 2048,
                'encrypt_name'  => true,
            ]);
            if (!$this->upload->do_upload('attachment')) {
                set_alert('danger', $this->upload->display_errors('', ''));
                redirect('attendance/all_leaves');
                return;
            }
            $up = $this->upload->data();
            $attachment_path = 'uploads/leaves/' . $up['file_name'];
        }
    
        // ── Insert ────────────────────────────────────────────────────────────────
        $insert = [
            'leave_type_id'   => $leave_type_id,
            'user_id'         => $target_user_id,
            'start_date'      => $start_date,
            'end_date'        => $end_date,
            'start_time'      => $start_time ?: null,
            'end_time'        => $end_time ?: null,
            'status'          => $status,
            'reason'          => $reason,
            'attachment_path' => $attachment_path,
            'created_by'      => $currentUser,
        ];
    
        $id = $this->Leaves_model->insert_leave($insert);
    
        if ($id > 0) {
            // If a manager/admin pre-approved, record the approver
            if ($status === 'approved') {
                $this->Leaves_model->update_leave_status($id, 'approved', $currentUser);
            }
            set_alert('success', 'Leave request created successfully.');
        } else {
            set_alert('danger', 'Failed to create leave request.');
        }
    
        redirect('attendance/all_leaves');
    }
    
    /**
     * Check if $targetUserId is in any team where $managerId is the manager.
     * Adjust the query to match your teams table schema.
     */
    private function _is_team_member(int $managerId, int $targetUserId): bool
    {
        if ($managerId <= 0 || $targetUserId <= 0) return false;
    
        // Get team IDs where current user is manager
        $teams = $this->db
            ->select('id')
            ->from('teams')
            ->where('manager_id', $managerId)
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->result_array();
    
        if (empty($teams)) return false;
    
        $teamIds = array_column($teams, 'id');
    
        // Check if target user belongs to any of those teams
        $count = $this->db
            ->from('users')
            ->where('id', $targetUserId)
            ->where('is_active', 1)
            ->where_in('team_id', $teamIds) // adjust column name to match your schema
            ->count_all_results();
    
        return $count > 0;
    }


public function my_leaves()
{
    if (!$this->session->userdata('is_logged_in')) {
        redirect('authentication/login');
        return;
    }

    $currentRole  = strtolower((string)($this->session->userdata('user_role') ?? ''));
    $isSuperAdmin = ($currentRole === 'superadmin');
    $canApply     = $isSuperAdmin || staff_can('apply', 'attendance');
    $canViewOwn   = $isSuperAdmin || staff_can('view_own', 'attendance');

    if (!$canViewOwn && !$canApply) {
        show_error('Unauthorized', 403);
        return;
    }

    $userId = (int)$this->session->userdata('user_id');

    // Leave type balances — annual & monthly usage per type
    $leave_types     = $this->Leaves_model->get_leave_types_with_limits();
    $balances        = $this->_build_leave_balances($userId, $leave_types);

    $layout_data = [
        'page_title' => 'My Leaves',
        'subview'    => 'attendance/my_leaves',
        'view_data'  => [
            'page_title'      => 'My Leaves',
            'leaves'          => $this->Leaves_model->get_user_leaves_with_meta($userId),
            'stats'           => $this->Leaves_model->get_user_leave_stats($userId),
            'leave_types'     => $leave_types,
            'balances'        => $balances,
            // Permission flags for the modal partial
            'can_apply'       => $canApply,
            'can_create'      => false,   // apply-only context — no user selector
            'can_view_global' => false,
            'can_own_team'    => false,
            'is_super_admin'  => $isSuperAdmin,
            'users'           => [],      // modal needs this key, empty = hidden selector
        ],
    ];

    $this->load->view('layouts/master', $layout_data);
}

// ── AJAX: calendar events for current user ────────────────────────────────────
public function ajax_my_leave_events()
{
    if (!$this->session->userdata('is_logged_in')) {
        $this->output->set_status_header(401);
        return;
    }

    $userId = (int)$this->session->userdata('user_id');
    $start  = $this->input->get('start', true) ?: date('Y-m-01');
    $end    = $this->input->get('end', true)   ?: date('Y-m-t');

    $rows = $this->Leaves_model->get_calendar_events($start, $end, $userId);

    // Map status → FullCalendar color
    $colorMap = [
        'approved'  => '#2fb344',
        'pending'   => '#f59f00',
        'rejected'  => '#d63939',
        'cancelled' => '#868e96',
    ];

    $events = [];
    foreach ($rows as $r) {
        $status = strtolower((string)($r['status'] ?? 'pending'));
        $events[] = [
            'id'              => $r['id'],
            'title'           => $r['leave_type_name'] ?? 'Leave',
            'start'           => $r['start_date'],
            'end'             => date('Y-m-d', strtotime($r['end_date'] . ' +1 day')), // FC end is exclusive
            'backgroundColor' => $colorMap[$status] ?? '#868e96',
            'borderColor'     => $colorMap[$status] ?? '#868e96',
            'extendedProps'   => [
                'status'     => $status,
                'total_days' => $r['total_days'],
                'reason'     => $r['reason'] ?? '',
            ],
        ];
    }

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($events));
}

// ── Private: build balance array ──────────────────────────────────────────────
private function _build_leave_balances(int $userId, array $leaveTypes): array
{
    if ($userId <= 0 || empty($leaveTypes)) return [];

    $year  = (int)date('Y');
    $month = (int)date('n');

    $annualWindow  = [
        'start' => date('Y-01-01'),
        'end'   => date('Y-12-31'),
    ];
    $monthlyWindow = [
        'start' => date('Y-m-01'),
        'end'   => date('Y-m-t'),
    ];

    $allowedStatuses = ['approved', 'pending'];

    // Pull all usage in one query grouped by leave_type_id
    $annualRows = $this->db
        ->select('leave_type_id, SUM(total_days) AS used', false)
        ->from('att_leaves')
        ->where('user_id', $userId)
        ->where('deleted_at IS NULL', null, false)
        ->where('start_date <=', $annualWindow['end'])
        ->where('end_date >=',  $annualWindow['start'])
        ->where_in('status', $allowedStatuses)
        ->group_by('leave_type_id')
        ->get()->result_array();

    $monthlyRows = $this->db
        ->select('leave_type_id, SUM(total_days) AS used', false)
        ->from('att_leaves')
        ->where('user_id', $userId)
        ->where('deleted_at IS NULL', null, false)
        ->where('start_date <=', $monthlyWindow['end'])
        ->where('end_date >=',  $monthlyWindow['start'])
        ->where_in('status', $allowedStatuses)
        ->group_by('leave_type_id')
        ->get()->result_array();

    $annualUsed  = array_column($annualRows,  'used', 'leave_type_id');
    $monthlyUsed = array_column($monthlyRows, 'used', 'leave_type_id');

    $balances = [];
    foreach ($leaveTypes as $lt) {
        $ltId           = (int)$lt['id'];
        $annualAllowed  = is_numeric($lt['allowed_annually']  ?? null) ? (float)$lt['allowed_annually']  : null;
        $monthlyAllowed = is_numeric($lt['allowed_monthly']   ?? null) ? (float)$lt['allowed_monthly']   : null;
        $aUsed          = round((float)($annualUsed[$ltId]  ?? 0), 2);
        $mUsed          = round((float)($monthlyUsed[$ltId] ?? 0), 2);

        $balances[$ltId] = [
            'id'               => $ltId,
            'name'             => $lt['name'],
            'type'             => $lt['type'] ?? 'Paid',
            'annual_allowed'   => $annualAllowed,
            'monthly_allowed'  => $monthlyAllowed,
            'annual_used'      => $aUsed,
            'monthly_used'     => $mUsed,
            'annual_remaining' => $annualAllowed !== null ? max(0, $annualAllowed - $aUsed) : null,
            'monthly_remaining'=> $monthlyAllowed !== null ? max(0, $monthlyAllowed - $mUsed) : null,
        ];
    }

    return $balances;
}


public function ajax_cancel_leave()
{
    if ($this->input->method() !== 'post') {
        show_404();
        return;
    }

    if (!$this->session->userdata('is_logged_in')) {
        $this->output->set_status_header(401);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['success' => false, 'message' => 'Unauthenticated']));
        return;
    }

    $currentUser  = (int)$this->session->userdata('user_id');
    $currentRole  = strtolower((string)($this->session->userdata('user_role') ?? ''));
    $isSuperAdmin = ($currentRole === 'superadmin');
    $canApply     = $isSuperAdmin || staff_can('apply', 'attendance');

    if (!$canApply) {
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
        return;
    }

    $leaveId = (int)$this->input->post('leave_id');
    if ($leaveId <= 0) {
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['success' => false, 'message' => 'Invalid leave ID']));
        return;
    }

    // Load the leave — must belong to current user (superadmin can cancel any)
    $leave = $this->Leaves_model->get_leave_by_id($leaveId);

    if (!$leave) {
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['success' => false, 'message' => 'Leave not found']));
        return;
    }

    if (!$isSuperAdmin && (int)$leave['user_id'] !== $currentUser) {
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['success' => false, 'message' => 'You can only cancel your own leaves']));
        return;
    }

    // Only pending leaves can be self-cancelled
    if (!$isSuperAdmin && strtolower($leave['status']) !== 'pending') {
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['success' => false, 'message' => 'Only pending leaves can be cancelled']));
        return;
    }

    $ok = $this->Leaves_model->update_leave_status($leaveId, 'cancelled', $currentUser);

    $this->output->set_content_type('application/json')
                 ->set_output(json_encode([
                     'success' => $ok,
                     'message' => $ok ? 'Leave cancelled.' : 'Failed to cancel leave.',
                 ]));
}


public function ajax_user_leave_history()
{
    if (!$this->session->userdata('is_logged_in')) {
        $this->output->set_status_header(401);
        return;
    }

    $currentUser  = (int)$this->session->userdata('user_id');
    $currentRole  = strtolower((string)($this->session->userdata('user_role') ?? ''));
    $isSuperAdmin = ($currentRole === 'superadmin');

    $requestedUid = (int)$this->input->get('user_id');
    if ($requestedUid <= 0) $requestedUid = $currentUser;

    // Non-admins can only fetch their own history
    $canCreate     = $isSuperAdmin || staff_can('create', 'attendance');
    $canViewGlobal = $isSuperAdmin || staff_can('view_global', 'attendance');

    if (!$isSuperAdmin && !$canCreate && !$canViewGlobal) {
        // Regular user — can only see own
        $requestedUid = $currentUser;
    }

    // Fetch current year's leaves for the user
    $yearStart = date('Y-01-01');
    $yearEnd   = date('Y-12-31');

    $leaves = $this->db
        ->select("
            l.id,
            l.start_date,
            l.end_date,
            l.total_days,
            l.status,
            l.reason,
            lt.name AS leave_type_name
        ")
        ->from('att_leaves l')
        ->join('leave_types lt', 'lt.id = l.leave_type_id', 'left')
        ->where('l.user_id', $requestedUid)
        ->where('l.deleted_at IS NULL', null, false)
        ->where('l.start_date >=', $yearStart)
        ->where('l.start_date <=', $yearEnd)
        ->order_by('l.start_date', 'DESC')
        ->limit(10)
        ->get()
        ->result_array();

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'success' => true,
            'leaves'  => $leaves,
        ]));
}
}