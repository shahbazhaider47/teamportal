<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Teams extends App_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Teams_model');
        $this->load->model('User_model');
        $this->load->model('Activity_log_model'); // ← add this
        $this->load->database();
        $this->load->library('form_validation');
    }
    
    protected function log_activity(string $action): void
    {
        // Guard: only call if model loaded successfully
        if (!isset($this->Activity_log_model) || $this->Activity_log_model === null) {
            log_message('error', 'Teams: Activity_log_model not available. Action: ' . $action);
            return;
        }
    
        $this->Activity_log_model->add([
            'user_id'    => $this->session->userdata('user_id') ?: 0,
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }


    public function index()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); return;
        }
        if (! staff_can('view_global', 'teams')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        if ($this->input->post('add_team')    !== null) { $this->_add_team();    redirect('teams'); return; }
        if ($this->input->post('delete_team') !== null) { $this->_delete_team(); redirect('teams'); return; }
        if ($this->input->post('update_team') !== null) { $this->_update_team(); redirect('teams'); return; }
        if ($this->input->post('add_users')   !== null) { $this->add_users();    return; }
    
        // Teams with all joined data from the new schema
        $teams = $this->Teams_model->get_all_teams();
    
        // Members already in each team + available users (for modals)
        $team_members_by_team    = [];
        $available_users_by_team = [];
    
        foreach ($teams as $t) {
            $tid = (int)$t['id'];
    
            $mem = $this->User_model->get_users_by_team($tid);
            $team_members_by_team[$tid] = array_map(function ($m) {
                return [
                    'id'     => (int)$m['id'],
                    'name'   => trim(($m['firstname'] ?? '') . ' ' . ($m['lastname'] ?? '')),
                    'email'  => $m['email'] ?? '',
                    'role'   => ucfirst($m['user_role'] ?? 'Member'),
                    'avatar' => base_url('uploads/users/profile/' . (($m['profile_image'] ?? '') ?: 'default.png')),
                ];
            }, $mem);
    
            $avail = $this->Teams_model->get_available_users($tid);
            $available_users_by_team[$tid] = array_map(function ($u) use ($tid) {
                return [
                    'id'                => (int)$u['id'],
                    'name'              => trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '')),
                    'email'             => $u['email'] ?? '',
                    'role'              => ucfirst($u['user_role'] ?? 'Member'),
                    'current_team_id'   => (int)($u['emp_team'] ?? 0),
                    'current_team_name' => $u['current_team_name'] ?? null,
                    'in_this_team'      => ((int)($u['emp_team'] ?? 0) === $tid),
                ];
            }, $avail);
        }
    
        $departments = $this->db
            ->select('id, name')
            ->order_by('name', 'ASC')
            ->get('departments')
            ->result_array();
    
        $eligible_leads    = $this->Teams_model->get_eligible_leads();
        $eligible_managers = $this->Teams_model->get_eligible_managers();
    
        $layout_data = [
            'page_title' => 'All Teams',
            'subview'    => 'teams/all_teams',
            'view_data'  => [
                'teams'                     => $teams,
                'team_members_by_team'      => $team_members_by_team,
                'available_users_by_team'   => $available_users_by_team,
                'departments'               => $departments,
                'eligible_leads'            => $eligible_leads,
                'eligible_managers'         => $eligible_managers,
            ],
        ];
        $this->load->view('layouts/master', $layout_data);
    }
    
    protected function _add_team(): void
    {
        $name          = trim($this->input->post('team_name', true));
        $department_id = (int)$this->input->post('department_id');
        $teamlead_id   = (int)$this->input->post('teamlead_id');
        $manager_id    = (int)$this->input->post('manager_id');
    
        if ($name === '' || $department_id <= 0) {
            set_alert('warning', 'Team name and Department are required.');
            return;
        }
    
        // Duplicate name check within same department
        $exists = $this->db
            ->where('department_id', $department_id)
            ->where('LOWER(name)', strtolower($name))
            ->count_all_results('teams') > 0;
    
        if ($exists) {
            set_alert('warning', 'A team with this name already exists in that department.');
            return;
        }
    
        $this->db->trans_start();
    
        $this->db->insert('teams', [
            'name'          => $name,
            'department_id' => $department_id,
            'teamlead_id'   => $teamlead_id > 0 ? $teamlead_id : null,
            'manager_id'    => $manager_id  > 0 ? $manager_id  : null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    
        $teamId = (int)$this->db->insert_id();
    
        if ($teamId <= 0) {
            $this->db->trans_rollback();
            set_alert('warning', 'Could not create team.');
            return;
        }
    
        // Assign lead to this team in users table and clear their teamlead reference
        if ($teamlead_id > 0) {
            $this->db->where('id', $teamlead_id)
                     ->update('users', ['emp_team' => $teamId, 'emp_teamlead' => null]);
    
            // All existing members of this team report to lead
            $this->db->where('emp_team', $teamId)
                     ->where('id !=', $teamlead_id)
                     ->update('users', ['emp_teamlead' => $teamlead_id]);
        }
    
        $this->db->trans_complete();
    
        if ($this->db->trans_status() === false) {
            set_alert('warning', 'Could not finalise team creation.');
            return;
        }
    
        set_alert('success', 'Team "' . html_escape($name) . '" created.');
        $this->log_activity('Created team: ' . $name . ' (ID: ' . $teamId . ')');
    }
    
    protected function _update_team(): void
    {
        $id            = (int)$this->input->post('team_id', true);
        $name          = trim((string)$this->input->post('team_name', true));
        $department_id = (int)$this->input->post('department_id');
        $teamlead_id   = (int)$this->input->post('teamlead_id');
        $manager_id    = (int)$this->input->post('manager_id');
    
        if ($id <= 0 || $name === '' || $department_id <= 0) {
            set_alert('warning', 'Invalid data — ID, name, and department are required.');
            return;
        }
    
        // Uniqueness check
        $exists = $this->db
            ->where('department_id', $department_id)
            ->where('LOWER(name)', strtolower($name))
            ->where('id !=', $id)
            ->count_all_results('teams') > 0;
    
        if ($exists) {
            set_alert('warning', 'Another team with this name already exists in that department.');
            return;
        }
    
        $this->db->trans_start();
    
        // Update the teams row directly — new schema stores IDs here
        $this->db->where('id', $id)->update('teams', [
            'name'          => $name,
            'department_id' => $department_id,
            'teamlead_id'   => $teamlead_id > 0 ? $teamlead_id : null,
            'manager_id'    => $manager_id  > 0 ? $manager_id  : null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    
        // Sync users.emp_team and emp_teamlead for the lead
        if ($teamlead_id > 0) {
            // Lead belongs to this team
            $this->db->where('id', $teamlead_id)
                     ->update('users', ['emp_team' => $id, 'emp_teamlead' => null]);
    
            // All other members of this team report to new lead
            $this->db->where('emp_team', $id)
                     ->where('id !=', $teamlead_id)
                     ->update('users', ['emp_teamlead' => $teamlead_id]);
    
            // Break stale references from other teams pointing to this lead
            $this->db->where('emp_teamlead', $teamlead_id)
                     ->where('emp_team !=', $id)
                     ->update('users', ['emp_teamlead' => null]);
        } else {
            // Clearing the lead — remove teamlead ref from all team members
            $this->db->where('emp_team', $id)
                     ->update('users', ['emp_teamlead' => null]);
        }
    
        $this->db->trans_complete();
    
        if ($this->db->trans_status() === false) {
            set_alert('warning', 'Could not finalise team update.');
            return;
        }
    
        set_alert('success', 'Team "' . html_escape($name) . '" updated.');
        $this->log_activity('Updated team ID ' . $id);
    }


    /**
     * Delete a team
     */
    protected function _delete_team()
    {
        $id = (int)$this->input->post('delete_team', true);

        if ($id <= 0) {
            set_alert('warning', 'Invalid team ID.');
            $this->log_activity('Failed to delete team: invalid ID');
            return;
        }

        // Prevent deleting a team that still has members
        $hasMembers = $this->Teams_model->count_active_members($id) > 0;
        if ($hasMembers) {
            set_alert('warning', 'Cannot delete a team that still has members.');
            return;
        }

        try {
            $ok = $this->db->where('id', $id)->delete('teams');

            if ($ok) {
                set_alert('success', 'Team deleted successfully.');
                $this->log_activity('Deleted team ID ' . $id);
            } else {
                throw new Exception('DB delete failed');
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            set_alert('warning', 'Could not delete team.');
            $this->log_activity('Failed to delete team ID ' . $id);
        }
    }


    /**
     * JSON: members & lead
     * (kept for compatibility if used elsewhere)
     */
    public function view_team_details($id)
    {
        $id = (int)$id;
        $members = $this->User_model->get_users_by_team($id);
        $lead    = $this->Teams_model->get_team_lead($id);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'members' => array_map(function ($m) {
                    return [
                        'name'          => trim(($m['firstname'] ?? '') . ' ' . ($m['lastname'] ?? '')),
                        'profile_image' => base_url('uploads/users/profile/' . (($m['profile_image'] ?? '') ?: 'default.png')),
                        'email'         => $m['email'] ?? '',
                    ];
                }, $members),
                'lead' => $lead ? [
                    'name'          => trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? '')),
                    'profile_image' => base_url('uploads/users/profile/' . (($lead['profile_image'] ?? '') ?: 'default.png')),
                    'email'         => $lead['email'] ?? '',
                ] : null
            ]));
    }

    public function my_team()
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); return;
        }
        
        if (! staff_can('view_own', 'teams')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        $user_id   = (int) $this->session->userdata('user_id');
        $user      = $this->User_model->get_user_by_id($user_id);
        $my_role   = strtolower(trim($user['user_role'] ?? 'employee'));
        $my_weight = teams_role_weight($my_role);
    
        // Position map (id → title string)
        $posMap = [];
        foreach ($this->db->select('id, title')->get('hrm_positions')->result_array() as $p) {
            $posMap[(int)$p['id']] = $p['title'];
        }
    
        // Determine scope and fetch members accordingly
        $teamUsers   = [];
        $teamName    = null;
        $teamLeadName = null;
        $scopeLabel  = teams_scope_label($my_role);
        $viewMode    = 'own'; // 'own' | 'dept' | 'global'
    
        // --- Global view: superadmin, director ---
        if ($my_weight >= TEAMS_ROLE_WEIGHTS['director']) {
            $viewMode  = 'global';
            $teamUsers = $this->Teams_model->get_all_members_global();
    
        // --- Dept view: manager ---
        } elseif ($my_role === 'manager') {
            $viewMode = 'dept';
            $dept_id  = $this->Teams_model->get_user_dept_id($user_id);
            $visible  = teams_visible_roles($my_role); // ['manager','teamlead','employee']
            $teamUsers = $dept_id > 0
                ? $this->Teams_model->get_department_members_scoped($dept_id, $visible)
                : [];
            // dept name for header
            if ($dept_id > 0) {
                $deptRow  = $this->db->select('name')->where('id', $dept_id)->get('departments')->row_array();
                $teamName = $deptRow['name'] ?? null;
            }
    
        // --- Own-team view: teamlead ---
        } elseif ($my_role === 'teamlead') {
            $viewMode = 'own';
            $team_id  = (int)($user['emp_team'] ?? 0);
            $visible  = teams_visible_roles($my_role); // ['teamlead','employee']
    
            if ($team_id > 0) {
                $teamName     = $this->Teams_model->get_team_name($team_id);
                $teamLeadRow  = $this->Teams_model->get_team_lead($team_id);
                $teamLeadName = $teamLeadRow
                    ? trim(($teamLeadRow['firstname'] ?? '') . ' ' . ($teamLeadRow['lastname'] ?? ''))
                    : null;
                $teamUsers = $this->Teams_model->get_team_members_scoped($team_id, $visible);
            }
    
        // --- Employee (and any other included roles): own team only, no leads ---
        } else {
            $viewMode = 'own';
            $team_id  = (int)($user['emp_team'] ?? 0);
            // Employee sees everyone on their team except excluded roles.
            // We pass null so they see all non-excluded teammates (teamlead + fellow employees).
            if ($team_id > 0) {
                $teamName     = $this->Teams_model->get_team_name($team_id);
                $teamLeadRow  = $this->Teams_model->get_team_lead($team_id);
                $teamLeadName = $teamLeadRow
                    ? trim(($teamLeadRow['firstname'] ?? '') . ' ' . ($teamLeadRow['lastname'] ?? ''))
                    : null;
                $teamUsers = $this->Teams_model->get_team_members_scoped($team_id, null);
            }
        }
    
        // Normalise / enrich each row
        foreach ($teamUsers as &$m) {
            $m['first_name'] = $m['firstname'] ?? '';
            $m['last_name']  = $m['lastname']  ?? '';
    
            // Position title resolution
            if (!empty($m['emp_title']) && is_numeric($m['emp_title'])) {
                $m['emp_title'] = $posMap[(int)$m['emp_title']] ?? $m['emp_title'];
            }
    
            // "Reports to" from emp_reporting
            $m['reporting_name'] = !empty($m['emp_reporting'])
                ? $this->User_model->get_full_name((int)$m['emp_reporting'])
                : null;
    
            // Role weight for badge styling in view
            $m['role_weight'] = teams_role_weight($m['user_role'] ?? '');
        }
        unset($m);
    
        // For own-team views: put team lead first
        if ($viewMode === 'own') {
            usort($teamUsers, function ($a, $b) {
                return ($b['role_weight'] ?? 0) <=> ($a['role_weight'] ?? 0);
            });
        }
    
        $layout_data = [
            'page_title' => 'My Team',
            'subview'    => 'teams/my_team',
            'view_data'  => [
                'teamUsers'    => $teamUsers,
                'currentUser'  => $user_id,
                'myRole'       => $my_role,
                'viewMode'     => $viewMode,   // 'own' | 'dept' | 'global'
                'scopeLabel'   => $scopeLabel,
                'has_team'     => !empty($user['emp_team']),
                'teamName'     => $teamName,
                'teamLeadName' => $teamLeadName,
            ]
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }
    
    /**
     * JSON: users available to add to a team (kept for compatibility)
     */
    public function available_users_for_team($team_id)
    {
        $team_id = (int)$team_id;
        $users  = $this->Teams_model->get_available_users($team_id);
        $result = [];
        foreach ($users as $u) {
            $result[] = [
                'id'        => (int)$u['id'],
                'full_name' => trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '')),
                'email'     => $u['email'] ?? '',
                'role'      => ucfirst($u['user_role'] ?? 'Member'),
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /**
     * POST: add users to team (non-AJAX form submit)
     */
    public function add_users()
    {
        $team_id  = (int)$this->input->post('team_id');
        $user_ids = $this->input->post('user_ids'); // array

        if ($team_id && is_array($user_ids) && !empty($user_ids)) {
            $ok = $this->Teams_model->add_users_to_team($team_id, $user_ids);
            if ($ok) {
                set_alert('success', 'Users added to team.');
            } else {
                set_alert('warning', 'Failed to add users to team.');
            }
        } else {
            set_alert('warning', 'Failed - No users were selected for the team.');
        }

        redirect('teams');
    }



    // Team Instructions Guide 

    /**
     * Team Instructions (Timeline)
     * GET  /teams/instructions[/team_id]  -> view timeline (optionally for a specific team)
     * POST /teams/instructions[/team_id]  -> create new guide (title/body/is_pinned, optional team_id)
     *
     * NOTE:
     * - No "who can add" checks (per requirement).
     * - Only uses 'view_global' to drive the form's team selector UX.
     */
    public function instructions($team_id = null)
    {
        if (!$this->session->userdata('is_logged_in')) { redirect('authentication/login'); return; }

        $current_user_id = (int)$this->session->userdata('user_id');
        $canGlobal       = staff_can('view_global', 'teams'); // UX only

        // Resolve context team_id for view
        if ($team_id === null) {
            $team_id = $this->Teams_model->get_user_team_id($current_user_id);
        } else {
            $team_id = (int)$team_id;
            if ($team_id <= 0) {
                $team_id = $this->Teams_model->get_user_team_id($current_user_id);
            }
            // If not global, we still render the page for the given team id,
            // but the form selector will be disabled and POST will force user's team.
        }

        // Handle create (POST)
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->form_validation->set_rules('title',    'Title',       'required|min_length[3]|trim');
            $this->form_validation->set_rules('body',     'Instruction', 'required|min_length[5]|trim');
            $this->form_validation->set_rules('is_pinned','Pinned',      'in_list[0,1]');
            $this->form_validation->set_rules('team_id',  'Team',        'integer');

            if ($this->form_validation->run() === false) {
                set_alert('danger', strip_tags(validation_errors()));
                redirect('teams/instructions/'.$team_id); return;
            }

            // Team target:
            // - If global: take posted team_id when provided, else fall back to current page context.
            // - If NOT global: force to current user's team (dropdown is disabled in the UI).
            $post_team_id = (int)$this->input->post('team_id');
            $target_team  = $canGlobal
                ? ($post_team_id > 0 ? $post_team_id : $team_id)
                : $this->Teams_model->get_user_team_id($current_user_id);

            $title    = (string)$this->input->post('title', true);
            $body     = (string)$this->input->post('body',  true);
            $isPinned = (int)$this->input->post('is_pinned');
            
            // Handle multiple file uploads
            $uploadedFiles = [];
            if (!empty($_FILES['guide_files']['name'][0])) {
                $uploadPath = FCPATH . 'uploads/guides/';
                if (!is_dir($uploadPath)) {
                    @mkdir($uploadPath, 0755, true);
                }
            
                $allowedTypes = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','txt','zip'];
                $maxSize      = 5242880; // 5MB per file
            
                $fileCount = count($_FILES['guide_files']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['guide_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if ($_FILES['guide_files']['size'][$i] > $maxSize) continue;
            
                    $origName = basename($_FILES['guide_files']['name'][$i]);
                    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedTypes, true)) continue;
            
                    $safeName = uniqid('guide_', true) . '.' . $ext;
            
                    if (move_uploaded_file($_FILES['guide_files']['tmp_name'][$i], $uploadPath . $safeName)) {
                        $uploadedFiles[] = [
                            'original_name' => $origName,
                            'path'          => 'uploads/guides/' . $safeName,
                            'ext'           => $ext,
                            'size'          => $_FILES['guide_files']['size'][$i],
                        ];
                    }
                }
            }
            
            $newId = $this->Teams_model->guide_create(
                $target_team, $title, $body, $current_user_id, $isPinned, $uploadedFiles
            );
            
            if ($newId > 0) {
                // Link to the specific team's instructions page
                $linkHref = site_url('teams/instructions/' . (int)$target_team);
            
                // Get unique active member IDs, exclude the author, and sanitize to ints
                $memberIds = $this->Teams_model->get_team_member_ids((int)$target_team);
                if (!is_array($memberIds)) { $memberIds = []; }
                $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
                $memberIds = array_values(array_diff($memberIds, [(int)$current_user_id]));
            
                // Predefined, short notification (no names, no content excerpts)
                $msgTitle = 'Team Instructions Added';
                $msgBody  = 'New instruction has been added to your team, please review and read the instructions carefully.';
            
                foreach ($memberIds as $uid) {
                    // Signature: notify_user($userId, $module, $title, $message, $linkHref)
                    notify_user($uid, 'teams', $msgTitle, $msgBody, $linkHref);
                }
            
                set_alert('success', 'Instruction added and the team has been notified.');
                $this->log_activity("Added team guide #{$newId} for team {$target_team}");
                redirect('teams/instructions/' . (int)$target_team);
                return;
            }

        }

        // Build page data
        $guides        = $this->Teams_model->guide_list_for_team($team_id, 200, 0);
        $teamName      = $this->Teams_model->get_team_name($team_id);
        $teamUsers     = $this->Teams_model->get_team_users_brief($team_id);
        $teamLeadName  = $this->Teams_model->get_team_lead_name_only($team_id);

        // For the form team dropdown (only when view_global)
        $allTeamsBrief = $canGlobal ? $this->Teams_model->get_all_teams_brief() : [];

        $layout_data = [
            'page_title' => 'Team Instructions',
            'subview'    => 'teams/instructions', // your view file
            'view_data'  => [
                'teamId'        => $team_id,
                'teamName'      => $teamName,
                'teamUsers'     => $teamUsers,
                'teamLeadName'  => $teamLeadName,
                'guides'        => $guides,
                'currentUserId' => $current_user_id,

                // UX flags
                'canGlobal'     => $canGlobal,
                'allTeamsBrief' => $allTeamsBrief, // for dropdown if global

                // show the create modal/button to everyone (no add checks at this stage)
                'canCreate'     => true,
            ]
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Pin/Unpin a guide (no permission checks).
     * POST /teams/guide_pin/{id} with is_pinned=0|1
     */
    public function guide_pin($id)
    {
        if (!$this->session->userdata('is_logged_in')) { redirect('authentication/login'); return; }

        $id  = (int)$id;
        $row = $this->Teams_model->guide_get($id);
        if (!$row) { show_404(); return; }

        $isPinned = (int)$this->input->post('is_pinned');
        $ok = $this->Teams_model->guide_toggle_pin($id, $isPinned);

        set_alert($ok ? 'success' : 'warning', $ok ? 'Updated.' : 'Failed to update.');
        redirect('teams/instructions');
    }

    /**
     * Delete a guide (no permission checks).
     * POST /teams/guide_delete/{id}
     */
    public function guide_delete($id)
    {
        if (!$this->session->userdata('is_logged_in')) { redirect('authentication/login'); return; }

        $id  = (int)$id;
        $row = $this->Teams_model->guide_get($id);
        if (!$row) { show_404(); return; }

        $ok = $this->Teams_model->guide_delete($id);
        set_alert($ok ? 'success' : 'warning', $ok ? 'Instruction deleted.' : 'Delete failed.');

        redirect('teams/instructions');
    }


    public function member_progress($user_id = 0)
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); return;
        }
    
        $user_id = (int)$user_id;
        if ($user_id <= 0) { show_404(); return; }
    
        // Permission: viewer must be a team lead, manager, director, superadmin,
        // OR looking at their own profile
        $viewer_id   = (int)$this->session->userdata('user_id');
        $viewer_role = strtolower($this->session->userdata('user_role') ?? '');
    
        $canViewOwn    = ($viewer_id === $user_id);
        $canViewOthers = hierarchy_is_senior($viewer_role, 'employee')
                      || $viewer_role === 'teamlead';
    
        if (! $canViewOwn && ! $canViewOthers) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        $this->load->model('Employee_progress_model');
    
        // Optional date range from GET params — default: current month
        $period_start = $this->input->get('from')
            ? date('Y-m-d', strtotime($this->input->get('from', true)))
            : date('Y-m-01');                          // first day of current month
    
        $period_end = $this->input->get('to')
            ? date('Y-m-d', strtotime($this->input->get('to', true)))
            : date('Y-m-d');                           // today
    
        // Clamp: end cannot be before start
        if ($period_end < $period_start) { $period_end = $period_start; }
    
        // Full progress snapshot
        $progress = $this->Employee_progress_model->get_full_progress(
            $user_id,
            $period_start,
            $period_end
        );
    
        // 404 if user doesn't exist
        if (empty($progress['user'])) { show_404(); return; }
    
        // KPI strip (cheaper separate call — used for headline cards)
        $kpis = $this->Employee_progress_model->get_kpi_strip(
            $user_id,
            $period_start,
            $period_end
        );
    
        $layout_data = [
            'page_title' => 'Employee Progress — ' . html_escape($progress['user']['full_name'] ?? ''),
            'subview'    => 'teams/member_progress',
            'view_data'  => [
                'progress'     => $progress,
                'kpis'         => $kpis,
                'period_start' => $period_start,
                'period_end'   => $period_end,
                'viewer_id'    => $viewer_id,
                'viewer_role'  => $viewer_role,
                'is_own'       => $canViewOwn,
            ],
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }

    public function team_progress($team_id = 0)
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); return;
        }
    
        $team_id = (int)$team_id;
        if ($team_id <= 0) { show_404(); return; }
    
        $currentUser = (int)$this->session->userdata('user_id');
        $canGlobal   = staff_can('view_global', 'teams');
        $canTeam     = staff_can('view_own',    'teams');
    
        if (!$canGlobal && !$canTeam) {
            $html = $this->load->view('errors/html/error_403', [
                'error_message' => 'You do not have permission to view team progress.'
            ], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        if (!$canGlobal && $canTeam) {
            $me = $this->User_model->get_user_by_id($currentUser);
            if ((int)($me['emp_team'] ?? 0) !== $team_id) {
                $html = $this->load->view('errors/html/error_403', [
                    'error_message' => 'You can only view your own team progress.'
                ], true);
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: text/html; charset=UTF-8');
                echo $html; exit;
            }
        }
    
        $this->load->model('Team_progress_model');
    
        $from = $this->input->get('from');
        $to   = $this->input->get('to');
        $from = ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) ? $from : date('Y-m-01');
        $to   = ($to   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   ? $to   : date('Y-m-d');
        if ($from > $to) { $from = date('Y-m-01'); }
    
        $data = $this->Team_progress_model->get_team_progress($team_id, $from, $to);
        if (!$data['team']) { show_404(); return; }
    
        $this->load->view('layouts/master', [
            'subview'   => 'teams/team_progress',
            'view_data' => [
                'page_title' => ($data['team']['team_name'] ?? 'Team') . ' — Progress',
                'progress'   => $data,
                'from'       => $from,
                'to'         => $to,
            ],
        ]);
    }
    
    public function rankings()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); return;
        }
    
        if (!staff_can('view_own', 'teams')) {
            $html = $this->load->view('errors/html/error_403', [
                'error_message' => 'You do not have permission to view team rankings.'
            ], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        $this->load->model('Team_progress_model');
    
        $year  = (int)($this->input->get('year')  ?: date('Y'));
        $month = (int)($this->input->get('month') ?: date('n'));
        if ($month < 1 || $month > 12)    { $month = (int)date('n'); }
        if ($year < 2000 || $year > 2100) { $year  = (int)date('Y'); }
    
        $from_month = sprintf('%04d-%02d-01', $year, $month);
        $to_month   = date('Y-m-t', strtotime($from_month));
        $from_year  = "{$year}-01-01";
        $to_year    = "{$year}-12-31";
    
        $all_teams     = $this->Team_progress_model->get_all_teams_ranking($from_month, $to_month);
        $top_3         = array_slice($all_teams, 0, 3);
        $team_of_month = !empty($all_teams) ? $all_teams[0] : null;
    
        $all_teams_year = $this->Team_progress_model->get_all_teams_ranking($from_year, $to_year);
        $team_of_year   = !empty($all_teams_year) ? $all_teams_year[0] : null;
    
        $this->load->view('layouts/master', [
            'subview'   => 'teams/rankings',
            'view_data' => [
                'page_title'    => 'Team Rankings',
                'all_teams'     => $all_teams,
                'top_3'         => $top_3,
                'team_of_month' => $team_of_month,
                'team_of_year'  => $team_of_year,
                'year'          => $year,
                'month'         => $month,
                'from_month'    => $from_month,
                'to_month'      => $to_month,
                'from_year'     => $from_year,
                'to_year'       => $to_year,
            ],
        ]);
    }

/**
 * POST /teams/guide_edit/{id}
 * Edit an existing guide — title, body, pin, files.
 */
public function guide_edit($id = 0)
{
    if (!$this->session->userdata('is_logged_in')) {
        redirect('authentication/login'); return;
    }

    $id  = (int)$id;
    $row = $this->Teams_model->guide_get($id);
    if (!$row) { show_404(); return; }

    if ($this->input->server('REQUEST_METHOD') !== 'POST') {
        show_404(); return;
    }

    $title    = trim((string)$this->input->post('title',    true));
    $body     = trim((string)$this->input->post('body',     true));
    $isPinned = (int)$this->input->post('is_pinned');

    if ($title === '' || $body === '') {
        set_alert('warning', 'Title and body are required.');
        redirect('teams/instructions/' . (int)$row['team_id']); return;
    }

    // Existing files: JS sends back the remaining ones after user removed any
    $existingJson = $this->input->post('existing_files') ?? '[]';
    $existingFiles = json_decode($existingJson, true) ?: [];

    // Handle new file uploads
    $newFiles = [];
    if (!empty($_FILES['guide_files']['name'][0])) {
        $uploadPath   = FCPATH . 'uploads/guides/';
        if (!is_dir($uploadPath)) { @mkdir($uploadPath, 0755, true); }

        $allowedTypes = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','txt','zip'];
        $maxSize      = 5242880;
        $fileCount    = count($_FILES['guide_files']['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['guide_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['guide_files']['size'][$i]  > $maxSize)        continue;

            $origName = basename($_FILES['guide_files']['name'][$i]);
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes, true)) continue;

            $safeName = uniqid('guide_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['guide_files']['tmp_name'][$i], $uploadPath . $safeName)) {
                $newFiles[] = [
                    'original_name' => $origName,
                    'path'          => 'uploads/guides/' . $safeName,
                    'ext'           => $ext,
                    'size'          => $_FILES['guide_files']['size'][$i],
                ];
            }
        }
    }

    // Merge: remaining existing + newly uploaded
    $allFiles = array_merge($existingFiles, $newFiles);

    $ok = $this->Teams_model->guide_update($id, $title, $body, $isPinned, $allFiles);

    set_alert($ok ? 'success' : 'warning', $ok ? 'Instruction updated.' : 'Update failed.');
    $this->log_activity("Edited team guide #{$id}");
    redirect('teams/instructions/' . (int)$row['team_id']);
}



public function debug_team_chat_files()
{
    $path = FCPATH . 'modules/team_chat/assets';

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    echo '<pre>';
    foreach ($iterator as $file) {
        echo $file->getPathname() . PHP_EOL;
    }
    echo '</pre>';
    exit;
}

}