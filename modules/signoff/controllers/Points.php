<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Points extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('signoff/Points_model');
        $this->load->model('signoff/Signoff_forms_model');
        $this->load->model('signoff/Signoff_submissions_model'); // NEW
         $this->load->model('Hrm_positions_model');
        $this->load->model('Teams_model');
        $this->load->model('User_model'); // NEW
        $this->load->helper(['url', 'date']);
        $this->hydrate_signoff_settings();
    }


    /** Load the signoff indicators setting once */
    protected function hydrate_signoff_settings(): void
    {
        $get = function (string $key, $default = null) {
            if (function_exists('get_setting')) {
                $val = get_setting($key);
                if ($val !== null && $val !== '') return $val;
            }
            if (isset($this->db)) {
                $row = $this->db->get_where('system_settings', [
                    'group_key' => 'signoff',
                    'key'       => $key
                ])->row_array();
                if ($row && array_key_exists('value', $row) && $row['value'] !== '') {
                    return $row['value'];
                }
            }
            return $default;
        };
    
        $ind = strtolower(trim((string) $get('signoff_perf_indicators', 'none')));
        if (!in_array($ind, ['points', 'targets', 'none'], true)) {
            $ind = 'none';
        }
        $this->S = ['indicators' => $ind];
    }
    
    /** Gate: allow this controller only when setting == 'points' */
    protected function assert_points_enabled_or_404(): void
    {

        if (($this->S['indicators'] ?? 'none') !== 'points') {
            $html = $this->load->view('errors/html/disabled_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }        
    }

    /**
     * Display points table (by team/form) and load modal data
     */
    public function index()
    {
        $this->assert_points_enabled_or_404();
    
        if (!staff_can('view_global','signoff')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
    // Teams (prepend Global)
        $teams = [];
        $teams[] = ['id' => 'global', 'name' => 'Global (All Teams)'];
        foreach ($this->Teams_model->get_all_teams() as $t) {
            $teams[] = ['id' => (string)$t['id'], 'name' => (string)$t['name']];
        }
    
        // ---- NEW: Positions for the modal ----
        $positions = $this->Hrm_positions_model->get_all_positions(); // array of [id,title]
    
        // ---- Forms by team (global + team-only; exclude position-assigned) ----
        $forms_by_team = []; // 'global' or "<teamId>" => [ {id,title,fields,is_active}, ... ]
        // ---- NEW: Forms by position (position-only; exclude team/global) ----
        $forms_by_position = []; // "<positionId>" => [ {id,title,fields,is_active}, ... ]
    
        foreach ($this->Signoff_forms_model->get_all_forms() as $f) {
            $isGlobal    = empty($f['team_id']) && empty($f['position_id']);
            $isTeamOnly  = !empty($f['team_id']) && empty($f['position_id']);
            $isPosOnly   = empty($f['team_id']) && !empty($f['position_id']);
    
            if ($isGlobal || $isTeamOnly) {
                $bucket = $isGlobal ? 'global' : (string)$f['team_id'];
                if (!isset($forms_by_team[$bucket])) $forms_by_team[$bucket] = [];
                $forms_by_team[$bucket][] = [
                    'id'        => (int)$f['id'],
                    'title'     => (string)$f['title'],
                    'fields'    => (string)($f['fields'] ?? '[]'),
                    'is_active' => (int)($f['is_active'] ?? 0),
                ];
            } elseif ($isPosOnly) {
                $bucket = (string)$f['position_id'];
                if (!isset($forms_by_position[$bucket])) $forms_by_position[$bucket] = [];
                $forms_by_position[$bucket][] = [
                    'id'        => (int)$f['id'],
                    'title'     => (string)$f['title'],
                    'fields'    => (string)($f['fields'] ?? '[]'),
                    'is_active' => (int)($f['is_active'] ?? 0),
                ];
            }
        }
    
        // Existing points rows -> warnings
        $points_flags = [];
        $ptsRows = $this->db->select('team_id, form_id')->from('signoff_points')->get()->result_array();
        foreach ($ptsRows as $r) {
            $tid = (int)($r['team_id'] ?? 0);
            $fid = (int)($r['form_id'] ?? 0);
            if ($fid <= 0) continue;
            if (!isset($points_flags[$tid])) $points_flags[$tid] = [];
            $points_flags[$tid][$fid] = true;
        }
    
        // Submissions counts -> warnings
        $subs_counts = [];
        $subsRows = $this->db->select('form_id, COUNT(*) AS n')
                             ->from('signoff_submissions')
                             ->group_by('form_id')->get()->result_array();
        foreach ($subsRows as $r) {
            $subs_counts[(int)$r['form_id']] = (int)$r['n'];
        }
    
        // Existing rows table
        $rows = $this->Points_model->get_points();
    
        $this->load->view('layouts/master', [
            'subview' => 'signoff/points',
            'view_data' => [
                'title'               => 'Signoff Points',
                'page_title'          => 'Assigned Signoff Points',
                'points_rows'         => $rows,
                'teams'               => $teams,
                'positions'           => $positions,          // <-- NEW
                'forms_by_team'       => $forms_by_team,
                'forms_by_position'   => $forms_by_position,  // <-- NEW
                'points_flags'        => $points_flags,
                'subs_counts'         => $subs_counts,
            ]
        ]);
    }

    /**
     * Assign (create/update) points for a team/form
     * POST: team_id ('global' or numeric), form_id (int), points[field]=value
     */

    // POST: team_id (optional: 'global' or numeric), form_id (REQUIRED), points[field]=value (REQUIRED)
    public function assign_points()
    {
        $this->assert_points_enabled_or_404();
        
        if ($this->input->server('REQUEST_METHOD') !== 'POST') show_404();
    
        $team_raw   = $this->input->post('team_id');   // may be null / '' / 'global' / '3'
        $form_id    = (int)$this->input->post('form_id');
        $points     = $this->input->post('points');    // array [field => value]
        $created_by = (int)$this->session->userdata('user_id');
    
        // Only require form_id + points
        if (!$form_id || !is_array($points)) {
            set_alert('warning', 'Missing form or invalid points data.');
            redirect('signoff/points');
        }
    
        // Default to Global when team is missing
        $team_id = ($team_raw === null || $team_raw === '' || $team_raw === 'global') ? 0 : (int)$team_raw;
    
        $form = $this->Signoff_forms_model->get_form($form_id);
        if (!$form) {
            set_alert('danger', 'Selected form was not found.');
            redirect('signoff/points');
        }
    
        // Clean numeric values
        $clean = [];
        foreach ($points as $field => $val) {
            if ($val === '' || $val === null) continue;
            $clean[$field] = (float)$val;
        }
        if (empty($clean)) {
            set_alert('warning', 'No points provided.');
            redirect('signoff/points');
        }
    
        $data = [
            'team_id'     => $team_id,       // 0 = Global
            'form_id'     => $form_id,
            'points_json' => $clean,
            'created_by'  => $created_by,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_by'  => $created_by,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        $this->Points_model->insert_or_update_json($data);
    
        set_alert('success', 'Points assigned/updated successfully.');
        redirect('signoff/points');
    }
    
        /**
         * Update points (same payload as assign)
         * Can be called as:
         *  - POST with team_id (or 'global') + form_id + points[], or
         *  - /points/update_points/{team_id} with form_id in POST (team_id '0' means global)
         */
    public function update_points($team_id = null)
    {
        $this->assert_points_enabled_or_404();
        
        if ($this->input->server('REQUEST_METHOD') !== 'POST') show_404();
    
        // Prefer POST team_id; fall back to URL param; default to Global
        $team_raw = $this->input->post('team_id');
        if ($team_raw === null && $team_id !== null) {
            $team_raw = (string)$team_id;
        }
    
        $form_id = (int)$this->input->post('form_id');
        $points  = $this->input->post('points');
    
        if (!$form_id || !is_array($points)) {
            set_alert('warning', 'Invalid form submission.');
            redirect('signoff/points');
        }
    
        $team_id_norm = ($team_raw === null || $team_raw === '' || $team_raw === 'global') ? 0 : (int)$team_raw;
    
        // Only require active form
        $form = $this->Signoff_forms_model->get_form($form_id);
        if (!$form) {
            set_alert('danger', 'Selected form was not found.');
            redirect('signoff/points');
        }
            
        $clean = [];
        foreach ($points as $field => $val) {
            if ($val === '' || $val === null) continue;
            $clean[$field] = (float)$val;
        }
        if (empty($clean)) {
            set_alert('warning', 'No points provided.');
            redirect('signoff/points');
        }
    
        $data = [
            'team_id'     => $team_id_norm,  // 0 = Global
            'form_id'     => $form_id,
            'points_json' => $clean,
            'created_by'  => (int)$this->session->userdata('user_id'),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_by'  => (int)$this->session->userdata('user_id'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        $this->Points_model->insert_or_update_json($data);
    
        set_alert('success', 'Points updated.');
        redirect('signoff/points');
    }


    public function delete_points()
    {
        $this->assert_points_enabled_or_404();
        if ($this->input->server('REQUEST_METHOD') !== 'POST') show_404();
    
        $form_id = (int)$this->input->post('form_id');
        $team_raw = $this->input->post('team_id'); // 'global' or numeric or '' (treat as global)
        $team_id = ($team_raw === null || $team_raw === '' || $team_raw === 'global') ? 0 : (int)$team_raw;
    
        if ($form_id <= 0) {
            set_alert('warning', 'Invalid form.');
            redirect('signoff/points');
        }
    
        // Ensure row exists before delete (optional but nice UX)
        $exists = $this->db->get_where('signoff_points', [
            'form_id' => $form_id,
            'team_id' => $team_id
        ])->row_array();
    
        if (!$exists) {
            set_alert('info', 'No assigned points found for this scope.');
            redirect('signoff/points');
        }
    
        $this->db->where('id', (int)$exists['id'])->delete('signoff_points');
        set_alert('success', 'Assigned points deleted for this form/scope.');
        redirect('signoff/points');
    }
    
    public function my_points()
    {
        $this->assert_points_enabled_or_404();
    
        // Gate: allow viewing own points (tweak to your RBAC)
        if (!function_exists('staff_can') || !staff_can('view_own', 'signoff')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        $uid        = (int)$this->session->userdata('user_id');
        $monthParam = trim((string)$this->input->get('month'));  // 'YYYY-MM'
        $formId     = (int)$this->input->get('form_id');         // optional
    
        $report = $this->Points_model->my_points_report($uid, $monthParam ?: null, $formId);
    
        $this->load->view('layouts/master', [
            'subview'   => 'signoff/my_points',
            'view_data' => [
                'title'             => 'My Signoff Points',
                'page_title'        => 'My Signoff Points',
                'month_param'       => $monthParam ?: date('Y-m'),
                'form_id_param'     => $formId,
                'rows'              => $report['rows'],
                'total_points'      => $report['total_points'],
                'total_submissions' => $report['total_submissions'],
                'forms_for_user'    => $report['forms_for_user'],
                'month_start'       => $report['month_start'],
                'month_end'         => $report['month_end'],
                'month_options'     => $this->Points_model->month_options(12),
            ],
        ]);
    }


}
