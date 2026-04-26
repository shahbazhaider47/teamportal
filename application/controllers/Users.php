<!-- Code for the controller file: application/controllers/Users.php -->

<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends App_Controller {

    public function __construct() {
        
        parent::__construct();

        $this->load->model('User_model');
        $this->load->model('Department_model');
        $this->load->model('Hrm_positions_model');
        $this->load->model('Hrm_allowances_model');        
        $this->load->model('Roles_model');
        $this->load->model('Activity_log_model');
                $this->load->model('Employee_transfer_model');
    }

    protected function log_activity(string $action)
    {
        $this->Activity_log_model->add([
            'user_id'    => $this->session->userdata('user_id'),
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function index()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        if (! staff_can('view_global', 'users')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        /* --------------------------------------------------------------------
         | Core datasets required for staff listing
         * ------------------------------------------------------------------ */
        $users          = $this->User_model->get_all_users();
        $teams          = $this->User_model->get_all_teams();
        $teamLeads      = $this->User_model->get_team_leads();
        $managers       = $this->User_model->get_managers();
        $employees      = $this->User_model->get_employees();
        $emp_department = $this->Department_model->get_all();
        $positions      = $this->Hrm_positions_model->get_all();
        $roles_rows     = $this->Roles_model->get_all_roles();
    
        /* --------------------------------------------------------------------
         | Normalize roles to a flat, lower-case array
         * ------------------------------------------------------------------ */
        $roles = [];
        if (is_array($roles_rows)) {
            foreach ($roles_rows as $r) {
                $name = is_array($r)
                    ? ($r['role_name'] ?? '')
                    : (string) $r;
    
                if ($name !== '') {
                    $roles[] = strtolower($name);
                }
            }
            $roles = array_values(array_unique($roles));
        }
    
        /* --------------------------------------------------------------------
         | Stats & dashboard indicators
         * ------------------------------------------------------------------ */
        $new_joiners = $this->User_model->get_recent_joiners(5);
    
        $roleCounts = [
            'admin'    => $this->User_model->count_by_role('admin'),
            'manager'  => $this->User_model->count_by_role('manager'),
            'teamlead' => $this->User_model->count_by_role('teamlead'),
            'employee' => $this->User_model->count_by_role('employee'),
        ];
    
        $counts = [
            'total'        => $this->User_model->count_all_users(),
            'active'       => $this->User_model->count_by_status(1),
            'inactive'     => $this->User_model->count_by_status(0),
            'on_probation' => $this->User_model->count_on_probation(),
        ];
    
        $view_data = [
            'users'           => $users,
            'teams'           => $teams,
            'emp_department'  => $emp_department,
            'teamLeads'       => $teamLeads,
            'managers'        => $managers,
            'employees'       => $employees,
            'roles'           => $roles,
            'positions'       => $positions,
            'role_counts'     => $roleCounts,
            'counts'          => $counts,
            'new_joiners'     => $new_joiners,
        ];
    
        $layout_data = [
            'page_title' => 'Staff List',
            'page_desc'  => 'List of all active staff for this company',            
            'subview'    => 'users/manage',
            'view_data'  => $view_data,
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }

    public function add_new()
    {
        if (! staff_can('view_global', 'users')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $teams          = $this->User_model->get_all_teams();
        $teamLeads      = $this->User_model->get_team_leads();
        $managers       = $this->User_model->get_managers();
        $employees      = $this->User_model->get_employees();
        $emp_department = $this->Department_model->get_all();
        $positions      = $this->Hrm_positions_model->get_all();
        $allowances     = $this->Hrm_allowances_model->get_all_active();
        $roles_rows     = $this->Roles_model->get_all_roles();

        $roles = [];
        if (is_array($roles_rows)) {
            foreach ($roles_rows as $r) {
                $name = is_array($r)
                    ? ($r['role_name'] ?? '')
                    : (string) $r;
    
                if ($name !== '') {
                    $roles[] = strtolower($name);
                }
            }
            $roles = array_values(array_unique($roles));
        }
    
        $employment_types    = get_company_setting_array('employment_types');
        $contract_types      = get_company_setting_array('contract_types');
        $work_location_types = get_company_setting_array('work_location_types');
        $relationship_types  = get_company_setting_array('relationship_types');
        $blood_group_types   = get_company_setting_array('blood_group_types');
        $employee_grades     = get_company_setting_array('employee_grades');
        $qualifications_list = get_company_setting_array('qualifications_list');
        $bank_names          = get_company_setting_array('bank_names');
    
        $view_data = [
            'roles'               => $roles,
            'positions'           => $positions,
            'emp_department'      => $emp_department,
            'teams'               => $teams,
            'teamLeads'           => $teamLeads,
            'managers'            => $managers,
            'employees'           => $employees,
            'allowances'          => $allowances,
            'employment_types'    => $employment_types,
            'contract_types'      => $contract_types,
            'work_location_types' => $work_location_types,
            'relationship_types'  => $relationship_types,
            'blood_group_types'   => $blood_group_types,
            'employee_grades'     => $employee_grades,
            'qualifications_list' => $qualifications_list,
            'bank_names'          => $bank_names,
        ];
    
        $layout_data = [
            'page_title' => 'Add New User',
            'subview'    => 'users/add_new',
            'view_data'  => $view_data,
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }

    public function delete($id = NULL) {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
        if (empty($id) || ! is_numeric($id)) {
            show_404();
            return;
        }
        
        $deletedUser = $this->User_model->get_user_by_id($id);
        $deletedName = $deletedUser ? $deletedUser['firstname'] . ' ' . $deletedUser['lastname'] : 'Unknown User';
    
        if ($this->User_model->delete_user($id, true)) {
            $this->log_activity("Deleted user ID {$id}");
        
            set_alert('success', 'User deleted successfully.');
        } else {
            set_alert('danger', 'Unable to delete user.');
        }
        redirect('users/inactive');
    }

    public function get_user($id) {
        if (! $this->input->is_ajax_request()) {
            show_404();
            return;
        }
        $user = $this->User_model->get_user_by_id($id);
        echo json_encode($user ?: ['error'=>'User not found']);
    }

    /**
     * Build a fully-hydrated profile payload for a given user.
     * Source of truth for both view($id) and profile().
     *
     * @return array [$user, $view_data]
     */
    private function build_user_payload(int $userId): array
    {

        $this->load->model('Hrm_documents_model');
        $this->load->model('Asset_model');
        $this->load->model('Teams_model');
        $this->load->model('Contracts_model', 'contracts');
        $this->load->model('Hrm_employee_exits_model');
        
        // 1) Base user
        $user = $this->User_model->get_user_by_id($userId);
        if (! $user) {
            return [null, []];
        }
    
        // 2) Allowances (map + resolved names)
        $all_allowances = $this->Hrm_allowances_model->get_all_active();
        $allowance_map  = [];
        foreach ($all_allowances as $a) {
            $amount_text = !empty($a['is_percentage'])
                ? rtrim(rtrim((string)$a['amount'], '0'), '.') . '%'
                : number_format((float)$a['amount'], 0);
            $allowance_map[$a['id']] = $a['title']; // . ' (' . $amount_text . ')'; remove // to show the amount next to the allowances names 
        }
    
        $user_allowance_ids = json_decode($user['allowances'] ?? '[]', true);
        if (!is_array($user_allowance_ids)) $user_allowance_ids = [];
    
        $user['allowance_names'] = [];
        foreach ($user_allowance_ids as $aid) {
            if (isset($allowance_map[$aid])) {
                $user['allowance_names'][] = $allowance_map[$aid];
            }
        }
    
        // 3) Documents
        $documents = $this->Hrm_documents_model->get_by_user($userId);
    
        // 4) Position title (robust, single lookup)
        if (!isset($this->Hrm_positions_model)) {
            $this->load->model('Hrm_positions_model');
        }
        $posId = (int)($user['emp_title'] ?? 0);
        
        // If somehow emp_title stores a literal title (legacy data), use it directly.
        // Otherwise resolve by ID from hrm_positions.
        if ($posId > 0) {
            $resolvedTitle = $this->Hrm_positions_model->get_title_by_id($posId);
            $user['position_title'] = $resolvedTitle ?: 'Position N/A';
        } else {
            // fall back: if emp_title is already a string title (legacy), use it; else N/A
            $user['position_title'] = (!empty($user['emp_title']) && !is_numeric($user['emp_title']))
                ? (string)$user['emp_title']
                : 'Position N/A';
        }
    
        // 5) Team + reporting chain (use existing helpers where you have them)
        $teamName      = '—';
        if (!empty($user['emp_team'])) {
            // Prefer Teams_model for encapsulation if available
            $teamName = $this->Teams_model->get_team_name((int)$user['emp_team']) ?? '—';
        }
    
        $teamLeadName  = $this->User_model->get_full_name((int)($user['emp_teamlead'] ?? 0)) ?: '—';
        $managerName   = $this->User_model->get_full_name((int)($user['emp_manager']  ?? 0)) ?: '—';
        $reportingName = $this->User_model->get_full_name((int)($user['emp_reporting']?? 0)) ?: '—';
    
        // 6) Assets
        $assets = $this->Asset_model->get_assets_by_user($userId);
    
        // 7) Exit info
        $this->load->model('Hrm_employee_exits_model');
        $exit = $this->Hrm_employee_exits_model->get_latest_by_user($userId);
    
        // 8) Interviewers dropdown (if needed by view/profile)
        $interviewers = $this->User_model->get_all_for_dropdown();
    
        // 9) Activity logs
        $activity_logs = $this->Activity_log_model->get_by_user($userId);
    
    
        // 10) Contracts
        $this->load->model('Contracts_model');
        $contracts = $this->Contracts_model->get_by_user($userId);
        
        $view_data = [
            'user'           => $user,
            'teamName'       => $teamName,
            'teamLeadName'   => $teamLeadName,
            'managerName'    => $managerName,
            'reportingName'  => $reportingName,
            'documents'      => $documents,
            'contracts'      => $contracts, // ✅ flat array        
            'activity_logs'  => $activity_logs,
            'assets'         => $assets,
            'exit'           => $exit,
            'interviewers'   => $interviewers,
            'contracts'     => $contracts,
        ];
    
        return [$user, $view_data];
    }

    public function view($id = NULL)
    {

        $this->load->model('Asset_model');
        $this->load->model('Contracts_model', 'contracts');
        $this->load->model('Teams_model');
        $this->load->model('Hrm_documents_model');
        
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); return;
        }
        if (!staff_can('view_global', 'users')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
        if (empty($id) || !is_numeric($id)) {
            set_alert('danger', 'Invalid user ID.'); show_404(); return;
        }
    
        [$user, $view_data] = $this->build_user_payload((int)$id);
        if (!$user) {
            set_alert('danger', 'User not found.'); show_404(); return;
        }

        // ✅ System settings → view
        $view_data = array_merge($view_data, [
            'employment_types'    => get_company_setting_array('employment_types'),
            'contract_types'      => get_company_setting_array('contract_types'),
            'work_location_types' => get_company_setting_array('work_location_types'),
            'relationship_types'  => get_company_setting_array('relationship_types'),
            'blood_group_types'   => get_company_setting_array('blood_group_types'),
            'employee_grades'     => get_company_setting_array('employee_grades'),
            'qualifications_list' => get_company_setting_array('qualifications_list'),
            'bank_names'          => get_company_setting_array('bank_names'),
        ]);
        
        // ✅ Supporting datasets for modals
        $view_data['roles']          = $this->Roles_model->get_all_roles();
        $view_data['positions']      = $this->Hrm_positions_model->get_all();
        $view_data['allowances']     = $this->Hrm_allowances_model->get_all_active();
        $view_data['teams']          = $this->User_model->get_all_teams();
        $view_data['teamLeads']      = $this->User_model->get_team_leads();
        $view_data['managers']       = $this->User_model->get_managers();
        $view_data['employees']      = $this->User_model->get_employees();
        $view_data['interviewers']   = $this->User_model->get_interviewers();
        $view_data['emp_department'] = $this->Department_model->get_all();
    
        $layout_data = [
            'page_title' => $user['fullname'],
            'subview'    => 'users/view',
            'view_data'  => $view_data,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function activity()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        $user_id = (int)$this->session->userdata('user_id');
        $since   = date('Y-m-d H:i:s', strtotime('-30 days')); // last 30 days, inclusive
    
        $view_data = [
            'logs'  => $this->Activity_log_model->get_by_user($user_id, $since),
            'title' => 'My Activity (Last 30 Days)',
        ];
    
        $layout_data = [
            'page_title' => 'My Activity',
            'subview'    => 'users/activity',
            'view_data'  => $view_data,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function user_activity($user_id = null)
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }

        if (!staff_can('view_global', 'users')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
        
        if (!$user_id) {
            show_404();
            return;
        }
    
        $user = $this->User_model->get($user_id);
    
        if (!$user) {
            show_error('User not found', 404);
            return;
        }
    
        $view_data = [
            'logs'  => $this->Activity_log_model->get_by_user($user_id),
            'title' => 'Activity: ' . html_escape($user['firstname'] . ' ' . $user['lastname']),
            'user'  => $user,
        ];
    
        $layout_data = [
            'page_title' => 'User Activity',
            'subview'    => 'users/user_activity',
            'view_data'  => $view_data,
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }

    public function manage_users()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }

        // Allow if the user can either view globally OR manage users
        if (! (staff_can('view_global', 'users') || staff_can('manage', 'users'))) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        $roleRows = $this->Roles_model->get_all_roles();
        $allRoleNames = array_values(array_unique(array_map(function ($r) {
            return (string)($r['role_name'] ?? '');
        }, $roleRows)));
    
        $data = [
            'positions'   => $this->Hrm_positions_model->get_all_positions(),
            'departments' => $this->Department_model->get_all_departments(),
    
            // Users & helpers
            'users'       => $this->User_model->get_all_users(false),
            'teams'       => $this->User_model->get_all_teams(),
            'teamLeads'   => $this->User_model->get_team_leads(),
            'managers'    => $this->User_model->get_managers(),
            'admins'      => $this->User_model->get_by_role('admin'),
            'roles'       => $allRoleNames,
        ];
    
        $layout_data = [
            'page_title' => 'User Management',
            'subview'    => 'users/manage_users',
            'view_data'  => $data,
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }
    

    public function manage_salaries()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }

        // Allow if the user can either view globally OR manage users
        if (! (staff_can('view_global', 'users') || staff_can('manage', 'users'))) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        $roleRows = $this->Roles_model->get_all_roles();
        $allRoleNames = array_values(array_unique(array_map(function ($r) {
            return (string)($r['role_name'] ?? '');
        }, $roleRows)));
    
        $data = [
            'positions'   => $this->Hrm_positions_model->get_all_positions(),
            'departments' => $this->Department_model->get_all_departments(),
            'users'       => $this->User_model->get_all_users(true),
            'teams'       => $this->User_model->get_all_teams(),
            'teamLeads'   => $this->User_model->get_team_leads(),
            'managers'    => $this->User_model->get_managers(),
            'admins'      => $this->User_model->get_by_role('admin'),
            'roles'       => $allRoleNames,
        ];
    
        $layout_data = [
            'page_title' => 'Salaries Management',
            'subview'    => 'users/manage_salaries',
            'view_data'  => $data,
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }
    
    public function update_roles()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        $users = $this->input->post('users');
    
        if (!$users || !is_array($users)) {
            log_message('error', 'update_roles: $_POST["users"] empty or not array. Possible max_input_vars truncation?');
            set_alert('danger', 'No user data submitted. If you have many rows, ask admin to increase PHP max_input_vars.');
            redirect('users/manage_users');
            return;
        }
    
        // Ensure needed models
        $this->load->model('User_model');
        $this->load->model('Teams_model');
        $this->load->model('Roles_model');
    
        // Build valid roles list (lowercase)
        $roles_rows  = $this->Roles_model->get_all_roles();
        $valid_roles = [];
        foreach ($roles_rows as $r) {
            $name = is_array($r) ? ($r['role_name'] ?? '') : $r;
            $name = strtolower(trim((string)$name));
            if ($name !== '') $valid_roles[$name] = true;
        }
    
        $this->load->database();
        $this->db->trans_start();
    
        $logEntries = [];
    
        foreach ($users as $id => $row) {
            $id = (int)$id;
    
            $userBefore = $this->User_model->get_user_by_id($id);
            if (!$userBefore) continue;
    
            $beforeRole = strtolower((string)($userBefore['user_role'] ?? ''));
    
            // Normalize inputs
            $submittedRole = strtolower(trim((string)($row['role'] ?? '')));
            $role = isset($valid_roles[$submittedRole]) ? $submittedRole : $beforeRole;
    
            $is_active = !empty($row['active']) ? 1 : 0;
    
            $emp_department = array_key_exists('emp_department', $row)
                ? (($row['emp_department'] === '' || $row['emp_department'] === null) ? null : (int)$row['emp_department'])
                : null;
    
            $emp_team = array_key_exists('emp_team', $row)
                ? (($row['emp_team'] === '' || $row['emp_team'] === null) ? null : (int)$row['emp_team'])
                : null;
    
            $emp_title = array_key_exists('emp_title', $row)
                ? (($row['emp_title'] === '' || $row['emp_title'] === null) ? null : (int)$row['emp_title'])
                : null;
    
            // Reporting fields for the NEW role
            $emp_teamlead = null;
            $emp_manager  = null;
            $emp_reporting = null;
    
            if ($role === 'employee') {
                if (array_key_exists('emp_teamlead', $row)) {
                    $emp_teamlead = ($row['emp_teamlead'] === '' || $row['emp_teamlead'] === null) ? null : (int)$row['emp_teamlead'];
                }
            } elseif ($role === 'teamlead') {
                if (array_key_exists('emp_manager', $row)) {
                    $emp_manager = ($row['emp_manager'] === '' || $row['emp_manager'] === null) ? null : (int)$row['emp_manager'];
                }
            } elseif ($role === 'manager') {
                if (array_key_exists('emp_reporting', $row)) {
                    $emp_reporting = ($row['emp_reporting'] === '' || $row['emp_reporting'] === null) ? null : (int)$row['emp_reporting'];
                }
            }
    
            // Detect change messages
            $changes = [];
            if ($beforeRole !== $role) {
                $changes[] = 'Your role changed from ' . ucfirst($beforeRole ?: 'N/A') . ' to ' . ucfirst($role);
            }
    
            $oldTeamId = (int)($userBefore['emp_team'] ?? 0);
            $newTeamId = (int)($emp_team ?? 0);
            if ($oldTeamId !== $newTeamId) {
                $oldTeam = $oldTeamId ? ($this->Teams_model->get_team_name($oldTeamId) ?: 'None') : 'None';
                $newTeam = $newTeamId ? ($this->Teams_model->get_team_name($newTeamId) ?: 'None') : 'None';
                $changes[] = "Team changed from {$oldTeam} to {$newTeam}";
            }
    
            // Final payload
            $update_data = [
                'user_role'      => $role,
                'emp_department' => $emp_department,
                'emp_title'      => $emp_title,
                'emp_team'       => $emp_team,
                'emp_teamlead'   => $emp_teamlead,
                'emp_manager'    => $emp_manager,
                'emp_reporting'  => $emp_reporting,
                'updated_at'     => date('Y-m-d H:i:s'),
            ];
    
            $this->User_model->update_user($id, $update_data);
    
            $fullName = trim(($userBefore['firstname'] ?? '') . ' ' . ($userBefore['lastname'] ?? ''));
            if ($fullName !== '') $logEntries[] = $fullName;
    
            if (!empty($changes)) {
                notify_user($id, 'users', 'Your role or team changed', implode(' | ', $changes));
            }
        }
    
        $this->db->trans_complete();
    
        if (!empty($logEntries)) {
            $this->log_activity('Updated users roles and teams');
        }
    
        if ($this->db->trans_status()) {
            set_alert('success', 'User roles and teams updated successfully.');
        } else {
            set_alert('danger', 'Something went wrong while saving changes.');
        }
    
        redirect('users/manage_users');
    }
    
    
    public function profile()
    {
        
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        $userId = (int)$this->session->userdata('user_id');
        [$user, $view_data] = $this->build_user_payload($userId);
        if (! $user) {
            set_alert('danger', 'Profile not found.');
            show_error('Profile not found', 404);
            return;
        }
    
        // Keep your extensibility hook
        hooks()->add_filter('current_user_profile_data', function () use ($user) {
            return $user;
        });
    
        // Assuming $user['id'] is the staff user_id
        $contract = $this->contracts->get_latest_for_user((int)$user['id']); // will return null or array
    
        $layout_data = [
            'page_title' => 'My Profile',
            'subview'    => 'users/profile',  // different subview, same rich data
            'view_data'  => $view_data,
            'contract'   => $contract,   // ← add this
            
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function settings()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); // keep this consistent
            return;
        }
    
        $userId        = (int) $this->session->userdata('user_id');
        $data['user']  = $this->User_model->get_user_by_id($userId);
        $data['title'] = 'Account Settings';
    
        // Use standardized helper (company settings JSON array)
        $blood_group_types = function_exists('get_company_setting_array')
            ? get_company_setting_array('blood_group_types')
            : [];
    
        $layout_data = [
            'subview'            => 'users/settings',
            'view_data'          => $data,
            'page_title'         => 'Account Settings',
            'blood_group_types'  => $blood_group_types,
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }

    public function update_settings()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); // was 'login' before; align with app routes
            return;
        }

        $userId  = (int)$this->session->userdata('user_id');
        $oldUser = $this->User_model->get_user_by_id($userId);

        $data = [
            'firstname'         => $this->input->post('firstname', true),
            'lastname'          => $this->input->post('lastname', true),
            'emp_dob'           => $this->input->post('emp_dob', true),
            'fullname'          => $this->input->post('fullname', true),
            'emp_phone'         => $this->input->post('emp_phone', true),
            'email'             => $this->input->post('email', true),
            'blood_group'       => $this->input->post('blood_group', true),
        ];

        // --- Handle profile image upload (optional)
        if (!empty($_FILES['profile_image']['name'])) {

            // Resolve an absolute, writable server path (NOT a URL)
            // Prefer FCPATH if index.php sits at webroot. Fall back to BASEPATH/.. if needed.
            $basePath = defined('FCPATH') ? FCPATH : rtrim(realpath(BASEPATH . '../'), '/') . '/';

            // Ensure trailing slash once
            if (substr($basePath, -1) !== '/') {
                $basePath .= '/';
            }

            $uploadPath = $basePath . 'uploads/users/profile/';

            // Create the directory tree if missing
            if (!is_dir($uploadPath)) {
                if (!@mkdir($uploadPath, 0775, true)) {
                    log_message('error', 'Failed to create upload directory: ' . $uploadPath);
                    set_alert('danger', 'Server cannot create upload directory. Contact admin.');
                    redirect('users/settings');
                    return;
                }
            }

            // Make sure it’s writable by the PHP user
            if (!is_writable($uploadPath)) {
                // Try to chmod; if it still fails, abort with a clear message
                @chmod($uploadPath, 0775);
                if (!is_writable($uploadPath)) {
                    log_message('error', 'Upload directory is not writable: ' . $uploadPath);
                    set_alert('danger', 'Upload folder is not writable. Please contact the administrator.');
                    redirect('users/settings');
                    return;
                }
            }

            // Configure the upload library
            $config = [
                'upload_path'      => $uploadPath,     // absolute server path
                'allowed_types'    => 'jpg|jpeg|png',
                'max_size'         => 2048,            // KB
                'file_ext_tolower' => true,
                'remove_spaces'    => true,
                'detect_mime'      => true,
                'max_filename'     => 160,
                'overwrite'        => false,
                'encrypt_name'     => false,
                'file_name'        => 'user_' . $userId . '_' . time(),
            ];

            $this->load->library('upload');
            $this->upload->initialize($config, true);

            if ($this->upload->do_upload('profile_image')) {
                $uploadData               = $this->upload->data();
                $newFilename              = $uploadData['file_name'];
                $data['profile_image']    = $newFilename;

                // Delete previous image if exists
                if (!empty($oldUser['profile_image'])) {
                    $oldPath = $uploadPath . $oldUser['profile_image'];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                        //log_message('debug', 'Old profile image deleted: ' . $oldUser['profile_image']);
                    }
                }

                //log_message('debug', sprintf('Profile image uploaded (user %d): %s', $userId, $newFilename));
            } else {
                $uploadError = trim($this->upload->display_errors('', ''));
                log_message('error', 'Upload failed for user ' . $userId . ': ' . $uploadError . ' | Path: ' . $uploadPath);
                set_alert('danger', 'Profile image upload failed: ' . $uploadError);
                redirect('users/settings');
                return;
            }
        }

        // --- Persist settings
        if ($this->User_model->update_user($userId, $data)) {
            $this->log_activity("Updated account settings for user ID {$userId}");

            // Actor name for notification
            $actorName = $this->User_model->get_full_name($userId);
            if (!$actorName) {
                $actorName = trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''));
            }
            if (!$actorName) {
                $actorName = $oldUser['fullname'] ?? ('User #' . $userId);
            }

            set_alert('success', 'Your settings have been updated.');
        } else {
            //log_message('error', 'User settings update failed for user ID: ' . $userId);
            set_alert('danger', 'Update failed. Please try again.');
        }

        redirect('users/settings');
    }

    public function remove_profile_photo()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        $userId = (int)$this->session->userdata('user_id');
        $user   = $this->User_model->get_user_by_id($userId);
    
        // Nothing to remove
        if (empty($user) || empty($user['profile_image'])) {
            set_alert('info', 'No profile photo to remove.');
            redirect('users/settings');
            return;
        }
    
        // Resolve upload path (absolute, server-side)
        $basePath = defined('FCPATH') ? FCPATH : rtrim(realpath(BASEPATH . '../'), '/') . '/';
        if (substr($basePath, -1) !== '/') $basePath .= '/';
        $uploadPath = $basePath . 'uploads/users/profile/';
    
        // Delete file if present
        $file = $uploadPath . $user['profile_image'];
        if (is_file($file)) {
            @unlink($file);
            //log_message('debug', "Profile image removed for user {$userId}: {$user['profile_image']}");
        }
    
        // Null out in DB
        $this->User_model->update_user($userId, ['profile_image' => null]);
    
        set_alert('success', 'Profile photo removed.');
        redirect('users/settings');
    }

    public function send_birthday_wish()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        $from_user_id = (int)$this->session->userdata('user_id');
        $to_user_id   = (int)$this->input->post('user_id');
        $message      = trim($this->input->post('message', TRUE));
    
        if (!$to_user_id || $from_user_id === $to_user_id || empty($message)) {
            set_alert('danger', 'Invalid input. Please write your message and try again.');
            redirect('dashboard');
            return;
        }
    
        $wish_key = 'birthday_wished_user_' . $to_user_id;
        if ($this->session->userdata($wish_key)) {
            set_alert('warning', 'You have already sent a birthday wish to this user.');
            redirect('dashboard');
            return;
        }
    
        notify_user(
            $to_user_id,
            'user',
            'Birthday Wish!',
            $message
        );
    
        $this->session->set_userdata($wish_key, true);
    
        set_alert('success', 'Your birthday wish has been sent!');
        redirect('dashboard');
    }

    public function documents()
    {
    
        if (! staff_can('view_global','users')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
            
        $this->load->model('Hrm_documents_model');
        $this->load->model('Company_info_model');
        $this->load->library('pagination');
    
        $company = $this->Company_info_model->get_all_values();
        $company_favicon = !empty($company['favicon']) ? $company['favicon'] : 'default-favicon.png';
    
        $config['base_url'] = site_url('users/documents');
        $config['total_rows'] = $this->Hrm_documents_model->count_all();
        $config['per_page'] = 20;
        $this->pagination->initialize($config);
    
        $page = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 0;
    
        $documents = $this->Hrm_documents_model->get_all($config['per_page'], $page);
        $users = $this->User_model->get_all_users(true);
        $search   = $this->input->get('search');
        $doc_type = $this->input->get('doc_type');
        $user_id  = $this->input->get('user_id');
    
        $layout_data = [
            'page_title' => 'All Documents',
            'subview'    => 'users/documents',
            'view_data'  => [
                'documents'      => $documents,
                'users'          => $users,
                'pagination'     => $this->pagination->create_links(),
                'search'         => $search,
                'doc_type'       => $doc_type,
                'user_id'        => $user_id,
                'company_favicon'=> $company_favicon
            ]
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function save_document()
    {
        $is_ajax  = $this->input->is_ajax_request();
        $doc_scope = $this->input->post('doc_scope'); // employee | company
    
        // --------------------------------------------------
        // Validation
        // --------------------------------------------------
        if ($doc_scope === 'employee') {
            $this->form_validation->set_rules('user_id', 'Employee', 'required');
        }
    
        $this->form_validation->set_rules('title', 'Title', 'required');
        $this->form_validation->set_rules('doc_type', 'Document Type', 'required');
    
        if ($this->form_validation->run() === FALSE) {
            $msg = strip_tags(validation_errors());
    
            if ($is_ajax) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => 'error', 'message' => $msg]));
            }
    
            set_alert('danger', $msg);
            return redirect('users/documents');
        }
    
        // --------------------------------------------------
        // Base Payload
        // --------------------------------------------------
        $id = $this->input->post('id');
    
        $data = [
            'doc_scope'   => $doc_scope, // 🔒 CRITICAL
            'title'       => $this->input->post('title', true),
            'doc_type'    => $this->input->post('doc_type', true),
            'description' => $this->input->post('description', true),
            'expiry_date' => $this->input->post('expiry_date', true),
            'updated_at'  => date('Y-m-d H:i:s'),
            'user_id'     => ($doc_scope === 'employee')
                                ? (int) $this->input->post('user_id')
                                : null, // 🔒 FORCE company documents
        ];
    
        $this->load->model('Hrm_documents_model');
    
        // --------------------------------------------------
        // File Upload (Optional)
        // --------------------------------------------------
        if (!empty($_FILES['file']['name'])) {
    
            $upload_path = FCPATH . 'uploads/hrm/documents/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
    
            $config = [
                'upload_path'   => $upload_path,
                'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png',
                'max_size'      => 5120,
                'encrypt_name'  => true,
            ];
    
            $this->load->library('upload', $config);
            $this->upload->initialize($config);
    
            if (!$this->upload->do_upload('file')) {
                $msg = strip_tags($this->upload->display_errors());
    
                if ($is_ajax) {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['status' => 'error', 'message' => $msg]));
                }
    
                set_alert('danger', $msg);
                return redirect('users/documents');
            }
    
            $upload = $this->upload->data();
    
            // Remove old file on update
            if ($id) {
                $old_doc = $this->Hrm_documents_model->get($id);
                if ($old_doc && !empty($old_doc['file_path'])) {
                    @unlink($upload_path . $old_doc['file_path']);
                }
            }
    
            $data['file_path'] = $upload['file_name'];
        }
    
        // --------------------------------------------------
        // Save
        // --------------------------------------------------
        if ($id) {
            $this->Hrm_documents_model->update($id, $data);
            $msg = 'Document updated successfully.';
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->Hrm_documents_model->insert($data);
            $msg = 'Document added successfully.';
        }
    
        // --------------------------------------------------
        // Response
        // --------------------------------------------------
        if ($is_ajax) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'message' => $msg]));
        }
    
        set_alert('success', $msg);
        redirect('users/documents');
    }

    public function delete_document($id)
    {
        $this->load->model('Hrm_documents_model');
        $this->Hrm_documents_model->delete($id);
        set_alert('success', 'Document deleted.');
        redirect('users/documents');
    }
    
// ───────────────────────── Allowances Section Starts ───────────────────────── //

    public function allowances()
    {

        if (! staff_can('view_global','users')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $this->load->model('Hrm_allowances_model');
        $this->load->model('User_model');
        $this->load->model('Department_model');
        $this->load->model('Hrm_positions_model');
    
        $allowances  = $this->Hrm_allowances_model->get_all();
        $employees   = $this->User_model->get_employees(); // only active employees
        $departments = $this->Department_model->get_all();
        $positions   = $this->Hrm_positions_model->get_all();
    
        $layout_data = [
            'page_title' => 'All Allowances',
            'subview'    => 'users/allowances',
            'view_data'  => [
                'allowances'  => $allowances,
                'employees'   => $employees,
                'departments' => $departments,
                'positions'   => $positions,
            ]
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }
    
    public function save_allowance()
    {
        $this->load->model('Hrm_allowances_model');
    
        $id = $this->input->post('id');
        $employee_ids = $this->input->post('custom_eligibility')
            ?? $this->input->post('applicable_user_ids_json')
            ?? [];
    
        $data = [
            'title'        => $this->input->post('title', true),
            'description'  => $this->input->post('description', true),
            'amount'       => $this->input->post('amount', true),
            'is_percentage'=> $this->input->post('is_percentage') ? 1 : 0,
            'percentage_of'=> $this->input->post('percentage_of', true),
            'max_limit'    => $this->input->post('max_limit', true),
            'applicable_to'=> $this->input->post('applicable_to', true),
            'is_taxable'   => $this->input->post('is_taxable') ? 1 : 0,
    
            'applicable_user_ids_json' => !empty($employee_ids) ? json_encode($employee_ids) : null,
            'applicable_departments_json' => $this->input->post('applicable_departments_json')
                ? json_encode($this->input->post('applicable_departments_json')) : null,
            'applicable_positions_json'   => $this->input->post('applicable_positions_json')
                ? json_encode($this->input->post('applicable_positions_json')) : null,
    
            'updated_at'  => date('Y-m-d H:i:s'),
            'updated_by'  => $this->session->userdata('user_id'),
        ];
    
        if ($id) {
            $success = $this->Hrm_allowances_model->update($id, $data);
            if ($success) {
                set_alert('success', 'Allowance updated successfully.');
            } else {
                set_alert('danger', 'Unable to update allowance.');
            }
        } else {
            $data['created_by'] = $this->session->userdata('user_id');
            $data['created_at'] = date('Y-m-d H:i:s');
            $success = $this->Hrm_allowances_model->insert($data);
            if ($success) {
                set_alert('success', 'Allowance added successfully.');
            } else {
                set_alert('danger', 'Unable to add allowance.');
            }
        }
    
        redirect('users/allowances');
    }
    
    public function edit_allowance($id = null)
    {
        // Optional auth/perm guard — return JSON, don't redirect
        if (!$this->session->userdata('is_logged_in')) {
            return $this->output
                ->set_status_header(401)
                ->set_content_type('application/json','utf-8')
                ->set_output(json_encode(['status'=>'error','message'=>'Not authenticated']));
        }
    
        // Make sure nothing else pollutes the response
        while (ob_get_level() > 0) { @ob_end_clean(); }
        $this->output->enable_profiler(false);
    
        if (!$id || !ctype_digit((string)$id)) {
            return $this->output
                ->set_status_header(400)
                ->set_content_type('application/json','utf-8')
                ->set_output(json_encode(['status'=>'error','message'=>'Invalid allowance ID']));
        }
    
        $this->load->model('Hrm_allowances_model');
        $row = $this->Hrm_allowances_model->get((int)$id);
    
        if (!$row) {
            return $this->output
                ->set_status_header(404)
                ->set_content_type('application/json','utf-8')
                ->set_output(json_encode(['status'=>'error','message'=>'Allowance not found']));
        }
    
        // Decode JSON columns into arrays for the UI
        foreach (['applicable_user_ids_json','applicable_departments_json','applicable_positions_json'] as $f) {
            $row[$f] = !empty($row[$f]) ? (json_decode($row[$f], true) ?: []) : [];
        }
    
        return $this->output
            ->set_status_header(200)
            ->set_content_type('application/json','utf-8')
            ->set_output(json_encode(['status'=>'success','data'=>$row], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    public function delete_allowance($id)
    {
        $is_ajax = $this->input->is_ajax_request();
        $this->load->model('Hrm_allowances_model');
    
        if (empty($id) || !is_numeric($id)) {
            $msg = 'Invalid allowance ID.';
            if ($is_ajax) {
                echo json_encode(['status' => 'error', 'message' => $msg]);
                return;
            }
            set_alert('danger', $msg);
            redirect('users/allowances');
            return;
        }
    
        $deleted = $this->Hrm_allowances_model->delete($id);
        $msg     = $deleted ? 'Allowance deleted successfully.' : 'Failed to delete allowance.';
    
        if ($is_ajax) {
            echo json_encode(['status' => $deleted ? 'success' : 'error', 'message' => $msg]);
            return;
        }
    
        set_alert($deleted ? 'success' : 'danger', $msg);
        redirect('users/allowances');
    }
    
    public function toggle_allowance_status($id, $status)
    {
        $is_ajax = $this->input->is_ajax_request();
        $this->load->model('Hrm_allowances_model');
    
        if (empty($id) || !is_numeric($id)) {
            $msg = 'Invalid allowance ID.';
            if ($is_ajax) {
                echo json_encode(['status' => 'error', 'message' => $msg]);
                return;
            }
            set_alert('danger', $msg);
            redirect('users/allowances');
            return;
        }
    
        $this->Hrm_allowances_model->update($id, [
            'is_active' => (int)$status,
            'updated_at'=> date('Y-m-d H:i:s'),
            'updated_by'=> $this->session->userdata('user_id')
        ]);
    
        $msg = 'Allowance status updated.';
    
        if ($is_ajax) {
            echo json_encode(['status' => 'success', 'message' => $msg]);
            return;
        }
    
        set_alert('success', $msg);
        redirect('users/allowances');
    }

    public function exit_employee()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            show_error('Invalid request method.', 405);
            return;
        }
    
        $this->load->model('Hrm_employee_exits_model');
        $this->load->library('form_validation');
        $exitId = (int) $this->input->post('exit_id');
        $userId = (int) $this->input->post('user_id');
        $this->form_validation->set_rules('user_id', 'User', 'required|integer');
        $this->form_validation->set_rules('exit_type', 'Exit Type', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('exit_date', 'Exit Date', 'required|trim');
        $this->form_validation->set_rules('exit_status', 'Exit Status', 'trim|max_length[20]');
    
        if ($this->form_validation->run() === false) {
            set_alert('danger', validation_errors());
            redirect($this->agent->referrer() ?: site_url('users'));
            return;
        }
    
        $userExists = $this->db->select('id')->from('users')->where('id', $userId)->limit(1)->get()->row_array();
        if (!$userExists) {
            set_alert('danger', 'Invalid employee selected.');
            redirect($this->agent->referrer() ?: site_url('users'));
            return;
        }
    
        $exitDate = $this->input->post('exit_date', true);
        $lastWorking = $this->input->post('last_working_date', true);
    
        if (!empty($lastWorking) && strtotime($lastWorking) > strtotime($exitDate)) {
            set_alert('danger', 'Last working day cannot be after Exit Date.');
            redirect("users/view/{$userId}");
            return;
        }
    
        $exitInterviewBy = $this->input->post('exit_interview_conducted_by', true);
        $exitInterviewBy = ($exitInterviewBy !== '' && $exitInterviewBy !== null) ? (int)$exitInterviewBy : null;
    
        $data = [
            'user_id'                     => $userId,
            'exit_type'                   => $this->input->post('exit_type', true),
            'exit_date'                   => $exitDate,
            'last_working_date'           => $lastWorking ?: null,
            'exit_status'                 => $this->input->post('exit_status', true) ?: 'Pending',
            'reason'                      => $this->input->post('reason', true),
            'remarks'                     => $this->input->post('remarks', true),
            'notice_period_served'        => $this->input->post('notice_period_served') ? 1 : 0,
            'exit_interview_date'         => $this->input->post('exit_interview_date', true) ?: null,
            'exit_interview_conducted_by' => $exitInterviewBy,
            'checklist_completed'         => $this->input->post('checklist_completed') ? 1 : 0,
            'assets_returned'             => $this->input->post('assets_returned') ? 1 : 0,
            'nda_signed'                  => $this->input->post('nda_signed') ? 1 : 0,
            'final_settlement_amount'     => ($this->input->post('final_settlement_amount', true) !== '')
                                              ? (float)$this->input->post('final_settlement_amount', true)
                                              : null,
            'final_settlement_date'       => $this->input->post('final_settlement_date', true) ?: null,
            'updated_at'                  => date('Y-m-d H:i:s'),
        ];
    
        $actorId = (int) $this->session->userdata('user_id');
        $this->db->trans_start();
    
        if ($exitId > 0) {
            $existing = $this->Hrm_employee_exits_model->get_by_id($exitId);
            if (!$existing || (int)$existing['user_id'] !== $userId) {
                $this->db->trans_complete();
                set_alert('danger', 'Invalid exit record.');
                redirect("users/view/{$userId}");
                return;
            }
    
            $this->Hrm_employee_exits_model->update($exitId, $data);
            $msg = "Exit record updated successfully.";
        } else {
            $data['created_by'] = $actorId;
            $data['created_at'] = date('Y-m-d H:i:s');
            $existingExit = $this->Hrm_employee_exits_model->get_latest_by_user($userId);
            if ($existingExit) {
                $this->db->trans_complete();
                set_alert('danger', 'This employee already has an exit record. Please edit the existing record.');
                redirect("users/view/{$userId}");
                return;
            }
    
            $this->Hrm_employee_exits_model->insert($data);
            $msg = "Employee exited and account is in-actived successfully.";
            $this->db->where('id', $userId)->update('users', ['is_active' => 0]);
        }
    
        $this->db->trans_complete();
    
        if ($this->db->trans_status() === false) {
            set_alert('danger', 'Failed to save exit record. Please try again.');
            redirect("users/view/{$userId}");
            return;
        }
    
        set_alert('success', $msg);
        redirect("users/view/{$userId}");
    }

    public function inactive()
    {
    

        if (! staff_can('view_global','users')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $roles = $this->Roles_model->get_all_roles();
        $users = $this->User_model->get_inactive_users_with_exit_info();
    
        $layout_data = [
            'page_title' => 'Inactive Employees',
            'subview'    => 'users/inactive',
            'view_data'  => [
                'users' => $users,
                'roles' => $roles,
            ],
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }


    /**
     * GET  /users/transfer_data/{user_id}  — JSON payload for the modal dropdowns
     * POST /users/transfer/{user_id}       — execute the transfer
     */
    public function transfer($user_id = 0)
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); return;
        }
    
        $user_id = (int)$user_id;
        if ($user_id <= 0) { show_404(); return; }
    
        $this->load->model('Employee_transfer_model');
    
        // ── GET: return JSON data for modal (offices, depts, teams, etc.) ──
        if ($this->input->server('REQUEST_METHOD') === 'GET') {
            $snapshot   = $this->Employee_transfer_model->get_employee_snapshot($user_id);
            if (!$snapshot) {
                $this->output->set_status_header(404)
                             ->set_content_type('application/json')
                             ->set_output(json_encode(['error' => 'Employee not found']));
                return;
            }
    
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'snapshot'    => $snapshot,
                    'offices'     => $this->Employee_transfer_model->get_offices(),
                    'departments' => $this->Employee_transfer_model->get_departments(),
                    'teams'       => $this->Employee_transfer_model->get_teams(),
                    'managers'    => $this->Employee_transfer_model->get_managers(),
                    'team_leads'  => $this->Employee_transfer_model->get_team_leads(),
                    'positions'   => $this->Employee_transfer_model->get_positions(),
                    'history'     => $this->Employee_transfer_model->get_transfer_history($user_id),
                ]));
            return;
        }
    
        // ── POST: execute the transfer ──
        if (! staff_can('manage', 'users')) {
            $this->output->set_status_header(403)
                         ->set_content_type('application/json')
                         ->set_output(json_encode(['success' => false, 'message' => 'Permission denied.']));
            return;
        }
    
        $result = $this->Employee_transfer_model->transfer([
            'user_id'          => $user_id,
            'effective_date'   => $this->input->post('effective_date', true)  ?: date('Y-m-d'),
            'to_office_id'     => (int)$this->input->post('to_office_id'),
            'to_department_id' => (int)$this->input->post('to_department_id'),
            'to_team_id'       => (int)$this->input->post('to_team_id'),
            'to_title_id'      => (int)$this->input->post('to_title_id'),
            'to_manager_id'    => (int)$this->input->post('to_manager_id'),
            'to_teamlead_id'   => (int)$this->input->post('to_teamlead_id'),
            'to_salary'        => (float)$this->input->post('to_salary'),
            'work_location'    => $this->input->post('work_location', true) ?? '',
            'reason'           => $this->input->post('reason',        true) ?? '',
            'remarks'          => $this->input->post('remarks',       true) ?? '',
            'created_by'       => (int)$this->session->userdata('user_id'),
        ]);
    
        if ($result['success']) {
            set_alert('success', $result['message']);
        } else {
            set_alert('warning', $result['message']);
        }
    
        // Respond as JSON (the modal submits via fetch)
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }


}