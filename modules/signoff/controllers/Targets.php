<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Targets extends App_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('signoff/Targets_model');
        $this->load->model('signoff/Signoff_forms_model');
        $this->load->model('signoff/Signoff_submissions_model'); // NEW
         $this->load->model('Hrm_positions_model');
        $this->load->model('Teams_model');
        $this->load->model('User_model'); // NEW
        $this->load->helper(['url', 'date']);
        $this->hydrate_signoff_settings();
    }


    /** Load signoff settings used by this controller (targets-only) */
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
    
        // Normalize to lowercase; tolerate stray whitespace and capitalization.
        $ind = strtolower(trim((string) $get('signoff_perf_indicators', 'none')));
    
        // Harden against unexpected values; coerce to the supported tri-state.
        if (!in_array($ind, ['points', 'targets', 'none'], true)) {
            $ind = 'none';
        }
    
        $this->S = ['indicators' => $ind];
    }
    
    /** Gate: allow this controller only when setting == 'targets' */
    protected function assert_targets_enabled_or_404(): void
    {

        if (($this->S['indicators'] ?? 'none') !== 'targets') {
            $html = $this->load->view('errors/html/disabled_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
    }

    /**
     * List targets (scopes are team+form+date range)
     * - Prepares data for the Assign Targets modal:
     *   teams, all active forms, and forms grouped by team.
     * - Accepts optional filters (?team_id, ?form_id, ?start, ?end)
     */
    public function index()
    {
        
        $this->assert_targets_enabled_or_404();
        
        if (!staff_can('view_global', 'signoff')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }

        // Filters (optional)
        $filter_team_id = $this->input->get('team_id', true);
        $filter_form_id = $this->input->get('form_id', true);
        $filter_start   = $this->input->get('start', true);
        $filter_end     = $this->input->get('end', true);

        // Default to current month range if not provided
        $firstDay = date('Y-m-01');
        $lastDay  = date('Y-m-t');
        $start_date = $this->_valid_date_or_default($filter_start, $firstDay);
        $end_date   = $this->_valid_date_or_default($filter_end, $lastDay);

        // Teams (for "Assigned By: Teams" flow)
        $teams = [];
        foreach ($this->Teams_model->get_all_teams() as $t) {
            $teams[] = ['id' => (int)$t['id'], 'name' => (string)$t['name']];
        }
        
        // NEW: positions list for the modal
        $positions = $this->Hrm_positions_model->get_all_positions(); // [ ['id'=>..,'title'=>..], ... ]
        
        // Preload submissions count per form (you already do this)
        $this->load->model('signoff/Signoff_submissions_model', 'subs');
        $counts_all_map = method_exists($this->subs, 'counts_all_time')
            ? (array)$this->subs->counts_all_time()
            : [];
        
        // Helper: has targets row?
        $hasTargetsForForm = function(int $formId): bool {
            $ci = get_instance();
            $row = $ci->db->select('id')->from('signoff_targets')
                          ->where('form_id', $formId)->limit(1)->get()->row_array();
            return !empty($row);
        };
        
        // Build maps
        $forms         = [];         // id => row
        $forms_by_team = [];         // 'global' or team_id => [rows...]
        $forms_by_pos  = [];         // position_id => [rows...]   <-- NEW
        
        foreach ($this->Signoff_forms_model->get_all_forms() as $f) {
            $fid       = (int)$f['id'];
            $teamId    = (int)($f['team_id'] ?? 0);     // 0 = global/none
            $posId     = (int)($f['position_id'] ?? 0); // 0 = none
            $isActive  = (int)($f['is_active'] ?? 0);
        
            $row = [
                'id'             => $fid,
                'title'          => (string)$f['title'],
                'team_id'        => $teamId,                        // 0 if global/position
                'position_id'    => $posId,                         // 0 if global/team
                'fields'         => (string)($f['fields'] ?? '[]'),
                'is_active'      => $isActive,
                'has_targets'    => $hasTargetsForForm($fid),
                'has_submissions'=> (int)($counts_all_map[$fid] ?? 0) > 0,
            ];
            $forms[$fid] = $row;
        
            if ($posId > 0 && $teamId === 0) {
                // Position-assigned form (no team)
                if (!isset($forms_by_pos[$posId])) $forms_by_pos[$posId] = [];
                $forms_by_pos[$posId][] = $row;
            } else {
                // Global (teamId=0 & posId=0) or Team-assigned (teamId>0, posId=0)
                $bucket = $teamId ?: 'global';
                if (!isset($forms_by_team[$bucket])) $forms_by_team[$bucket] = [];
                $forms_by_team[$bucket][] = $row;
            }
        }

        // Fetch existing target scopes to display (aggregated)
        $scopes = $this->Targets_model->get_scoped_targets([
            'team_id'    => $filter_team_id !== '' ? $filter_team_id : null,
            'form_id'    => $filter_form_id !== '' ? $filter_form_id : null,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ]);

        $this->load->view('layouts/master', [
          'subview'   => 'signoff/targets',
          'view_data' => [
            'title'           => 'Signoff Targets',
            'page_title'      => 'Assigned Signoff Targets',
            'teams'           => $teams,
            'positions'       => $positions,        // <-- NEW
            'forms'           => $forms,
            'forms_by_team'   => $forms_by_team,
            'forms_by_pos'    => $forms_by_pos,     // <-- NEW
            'targets'         => $scopes,
            'start_date'      => $start_date,
            'end_date'        => $end_date,
          ]
        ]);
    }

    /**
     * Assign or update a target SCOPE (no per-user writes):
     * POST fields:
     * - assigned_by: 'teams' | 'forms'  (REQUIRED)
     * - team_id: int (REQUIRED if assigned_by='teams')
     * - form_id: int (REQUIRED if assigned_by='forms'; ignored for 'teams' — we resolve automatically)
     * - start_date: YYYY-MM-DD (REQUIRED)
     * - end_date:   YYYY-MM-DD (REQUIRED)
     * - targets[field] = numeric value (REQUIRED; only number/amount fields allowed)
     */
public function assign_target()
{
    $this->assert_targets_enabled_or_404();
    
    if ($this->input->server('REQUEST_METHOD') !== 'POST') { show_404(); }
    if (!staff_can('view_global', 'signoff')) { show_error('Unauthorized', 403); }

    // Read raw inputs
    $assigned_by_raw = $this->input->post('assigned_by', true); // 'team'|'form' or 'teams'|'forms'
    $team_id_in      = $this->input->post('team_id', true);
    $form_id_in      = (int)$this->input->post('form_id');
    $start_date      = $this->input->post('start_date', true);
    $end_date        = $this->input->post('end_date', true);
    $targets         = $this->input->post('targets'); // array
    $user_id         = (int)$this->session->userdata('user_id');

    // --- Normalize assigned_by & infer if missing ---
    $assigned_by = strtolower(trim((string)$assigned_by_raw));
    if ($assigned_by === 'teams') { $assigned_by = 'team'; }
    if ($assigned_by === 'forms') { $assigned_by = 'form'; }
    if ($assigned_by !== 'team' && $assigned_by !== 'form') {
        if (!empty($form_id_in))         { $assigned_by = 'form'; }
        elseif (!empty($team_id_in))     { $assigned_by = 'team'; }
    }
    if (!in_array($assigned_by, ['team','form'], true)) {
        set_alert('danger', 'Please select a valid "Assigned By" option (Teams or Forms).');
        redirect('signoff/targets');
    }

    // --- Dates validation (fallback to regex if helper is missing) ---
    $is_valid_date = function($d) {
        if (method_exists($this, '_is_valid_date')) { return $this->_is_valid_date($d); }
        return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
    };
    if (!$start_date || !$end_date || !$is_valid_date($start_date) || !$is_valid_date($end_date) || $end_date < $start_date) {
        set_alert('danger', 'Please provide a valid Start/End date range.');
        redirect('signoff/targets');
    }

    if (!is_array($targets) || empty($targets)) {
        set_alert('danger', 'Please provide at least one target metric.');
        redirect('signoff/targets');
    }

    // --- Resolve (team_id, form) according to mode ---
    $resolved_team_id = 0;   // 0 == global
    $resolved_form    = null;
    $form_id          = 0;

    if ($assigned_by === 'team') {
        $resolved_team_id = (int)$team_id_in;
        if ($resolved_team_id <= 0) {
            set_alert('danger', 'Please select a Team.');
            redirect('signoff/targets');
        }

        if ($form_id_in > 0) {
            // Verify provided form belongs to this team and is active
            $f = $this->Signoff_forms_model->get_form($form_id_in);
            if (!$f || empty($f['is_active']) || (int)$f['team_id'] !== $resolved_team_id) {
                set_alert('danger', 'Selected form is not active or not assigned to the selected team.');
                redirect('signoff/targets');
            }
            $resolved_form = $f;
            $form_id       = (int)$f['id'];
        } else {
            // Find exactly one active form for this team
            $team_forms = [];
            foreach ($this->Signoff_forms_model->get_all_forms() as $f) {
                if (!empty($f['is_active']) && (int)$f['team_id'] === $resolved_team_id) {
                    $team_forms[] = $f;
                }
            }
            if (count($team_forms) === 0) {
                set_alert('danger', 'No active signoff form is assigned to the selected team.');
                redirect('signoff/targets');
            }
            if (count($team_forms) > 1) {
                set_alert('danger', 'Multiple forms are assigned to this team. Please pick one using "Assigned By: Forms".');
                redirect('signoff/targets');
            }
            $resolved_form = $team_forms[0];
            $form_id       = (int)$resolved_form['id'];
        }
    } else {
        // assigned_by === 'form'
        $form_id = $form_id_in;
        if ($form_id <= 0) {
            set_alert('danger', 'Please select a Form.');
            redirect('signoff/targets');
        }
        $resolved_form = $this->Signoff_forms_model->get_form($form_id);
        if (!$resolved_form || empty($resolved_form['is_active'])) {
            set_alert('danger', 'Selected form is not available.');
            redirect('signoff/targets');
        }
        // team scope is dictated by the form (0 for global)
        $resolved_team_id = !empty($resolved_form['team_id']) ? (int)$resolved_form['team_id'] : 0;
    }

    // --- Server-side whitelist of numeric/amount fields ---
    $fields_def = json_decode((string)$resolved_form['fields'], true);
    $numeric_whitelist = [];
    if (is_array($fields_def)) {
        foreach ($fields_def as $f) {
            $type = strtolower((string)($f['type'] ?? ''));
            if (($type === 'number' || $type === 'amount') && !empty($f['name'])) {
                $numeric_whitelist[(string)$f['name']] = true;
            }
        }
    }
    if (empty($numeric_whitelist)) {
        set_alert('danger', 'The selected form has no numeric/amount fields to target.');
        redirect('signoff/targets');
    }

    // --- Clean/sanitize incoming targets against whitelist ---
    $clean = [];
    foreach ($targets as $field => $val) {
        if (!isset($numeric_whitelist[$field])) { continue; }
        if ($val === '' || $val === null)      { continue; }
        $clean[$field] = (float)$val;
    }
    if (empty($clean)) {
        set_alert('danger', 'No valid numeric targets were provided.');
        redirect('signoff/targets');
    }

    // --- Persist scope (team_id + form_id + date range) ---
    $payload = [
        'team_id'      => $resolved_team_id,     // 0 = global
        'form_id'      => (int)$form_id,
        'start_date'   => $start_date,
        'end_date'     => $end_date,
        'targets_json' => $clean,
        'created_by'   => $user_id,
        'created_at'   => date('Y-m-d H:i:s'),
        'updated_by'   => $user_id,
        'updated_at'   => date('Y-m-d H:i:s'),
    ];

    // Upsert by composite scope (team_id + form_id + start_date + end_date)
    $this->Targets_model->insert_or_update_scope($payload);

    set_alert('success', 'Targets assigned successfully.');
    redirect('signoff/targets');
}


/**
 * Update an existing scope row (edit targets and/or date range)
 * POST: id, start_date, end_date, targets[field]=value
 */
public function update_scope($id = null)
{
    $this->assert_targets_enabled_or_404();
    
    if (!$id || $this->input->server('REQUEST_METHOD') !== 'POST') { show_404(); }
    if (!staff_can('view_global', 'signoff')) { show_error('Unauthorized', 403); }

    $start_date = $this->input->post('start_date', true);
    $end_date   = $this->input->post('end_date', true);
    $targets    = $this->input->post('targets'); // array
    $user_id    = (int)$this->session->userdata('user_id');

    // Validate dates (fallback if helper missing)
    $is_valid_date = function($d) {
        if (method_exists($this, '_is_valid_date')) { return $this->_is_valid_date($d); }
        return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
    };
    if (!$start_date || !$end_date || !$is_valid_date($start_date) || !$is_valid_date($end_date) || $end_date < $start_date) {
        set_alert('danger', 'Please provide a valid Start/End date range.');
        redirect('signoff/targets');
    }

    // Load existing scope to determine form & whitelist
    $scope = $this->Targets_model->get_scope($id);
    if (!$scope) {
        set_alert('danger', 'Target scope not found.');
        redirect('signoff/targets');
    }

    $form = $this->Signoff_forms_model->get_form((int)$scope['form_id']);
    if (!$form || empty($form['is_active'])) {
        set_alert('danger', 'Form not found or inactive for this scope.');
        redirect('signoff/targets');
    }

    // Whitelist numeric/amount fields
    $fields_def = json_decode((string)$form['fields'], true);
    $numeric_whitelist = [];
    if (is_array($fields_def)) {
        foreach ($fields_def as $f) {
            $type = strtolower((string)($f['type'] ?? ''));
            if (($type === 'number' || $type === 'amount') && !empty($f['name'])) {
                $numeric_whitelist[(string)$f['name']] = true;
            }
        }
    }

    $clean = [];
    if (is_array($targets)) {
        foreach ($targets as $field => $val) {
            if (!isset($numeric_whitelist[$field])) { continue; }
            if ($val === '' || $val === null)      { continue; }
            $clean[$field] = (float)$val;
        }
    }
    if (empty($clean)) {
        set_alert('danger', 'No valid numeric targets were provided.');
        redirect('signoff/targets');
    }

    $ok = $this->Targets_model->update_scope($id, [
        'start_date'   => $start_date,
        'end_date'     => $end_date,
        'targets_json' => $clean,
        'updated_by'   => $user_id,
        'updated_at'   => date('Y-m-d H:i:s'),
    ]);

    set_alert($ok ? 'success' : 'danger', $ok ? 'Targets updated.' : 'Failed to update targets.');
    redirect('signoff/targets');
}


    /**
     * Delete a scope row
     */
    public function delete_scope($id = null)
    {
        if (!$id) { show_404(); }
        if (!staff_can('view_global', 'signoff')) { show_error('Unauthorized', 403); }

        $ok = $this->Targets_model->delete_scope($id);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Targets deleted.' : 'Failed to delete targets.');
        redirect('signoff/targets');
    }

    /* ===================== *
     *  Internal helpers     *
     * ===================== */

    private function _is_valid_date($s)
    {
        // Accepts 'YYYY-MM-DD'
        if (!is_string($s) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return false;
        [$y,$m,$d] = explode('-', $s);
        return checkdate((int)$m, (int)$d, (int)$y);
    }

    private function _valid_date_or_default($maybe, $default)
    {
        return $this->_is_valid_date($maybe) ? $maybe : $default;
    }
    

    public function my_targets()
    {
        $this->assert_targets_enabled_or_404();
        
        $user_id = (int)$this->session->userdata('user_id');
        if (!$user_id) { show_error('Unauthorized', 403); }
    
        // Accept date filters instead of month/year
        $start_date = $this->input->get('start') ?: date('Y-m-01');
        $end_date   = $this->input->get('end')   ?: date('Y-m-t');
        if ($end_date < $start_date) { [$start_date, $end_date] = [$end_date, $start_date]; }
    
        // Current user + scope context
        $me          = $this->User_model->get_user_by_id($user_id);
        $team_id     = (int)($me['emp_team']  ?? 0);
        $position_id = (int)($me['emp_title'] ?? 0);
    
        // Forms the user is allowed to submit (team or position or global)
        $assigned_forms = $this->Signoff_forms_model->get_forms_for_user($team_id ?: null, $position_id ?: null);
        $forms_map      = [];      // form_id => ['title'=>..., 'fields'=>json]
        $field_labels   = [];      // form_id => [field_name => label]
        $allowed_form_ids = [];
    
        foreach ((array)$assigned_forms as $f) {
            $fid = (int)$f['id'];
            $allowed_form_ids[] = $fid;
            $forms_map[$fid] = [
                'title'  => (string)($f['title'] ?? 'Untitled'),
                'fields' => (string)($f['fields'] ?? '[]'),
            ];
            // Build field label map for detail table
            $labels = [];
            $defs = json_decode((string)$f['fields'], true);
            if (is_array($defs)) {
                foreach ($defs as $def) {
                    $name  = isset($def['name'])  ? (string)$def['name']  : '';
                    $label = isset($def['label']) ? (string)$def['label'] : $name;
                    if ($name !== '') { $labels[$name] = $label; }
                }
            }
            $field_labels[$fid] = $labels;
        }
    
        // Early exit if nothing is assigned
        if (empty($allowed_form_ids)) {
            $this->load->view('layouts/master', [
                'subview' => 'signoff/my_targets',
                'view_data' => [
                    'title'       => 'Assigned Targets',
                    'page_title'  => 'My Targets',
                    'scopes'      => [],                  // NOTE: changed key for the view
                    'start_date'  => $start_date,
                    'end_date'    => $end_date,
                    'table_id'    => 'my_targetsTable',
                ]
            ]);
            return;
        }
    
        // Get team names once
        $teams_map = [];
        foreach ($this->Teams_model->get_all_teams() as $t) {
            $teams_map[(int)$t['id']] = (string)$t['name'];
        }
    
        // Scopes that apply to the user: Global (team_id=0) + their team scopes
        $scopes_global = $this->Targets_model->get_scoped_targets([
            'team_id'    => 0,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ]);
        $scopes_team = $team_id > 0
            ? $this->Targets_model->get_scoped_targets([
                'team_id'    => $team_id,
                'start_date' => $start_date,
                'end_date'   => $end_date,
            ])
            : [];
    
        // Merge and keep only scopes whose form_id is allowed
        $scopes_all = array_values(array_filter(array_merge((array)$scopes_global, (array)$scopes_team), function($row) use ($allowed_form_ids) {
            $fid = (int)($row['form_id'] ?? 0);
            return $fid > 0 && in_array($fid, $allowed_form_ids, true);
        }));
    
        // Pull user's submissions once
        $user_history = $this->Signoff_submissions_model->get_user_history($user_id);
    
        // Helpers
        $overlaps = static function($aStart, $aEnd, $bStart, $bEnd) {
            if (!$aStart || !$aEnd || !$bStart || !$bEnd) return true;
            return !($aEnd < $bStart || $bEnd < $aStart);
        };
    
        // Build scope rows: one row per scope (as in your screenshot)
        $scope_rows = [];
        foreach ($scopes_all as $scope) {
            $scope_team_id = (int)($scope['team_id'] ?? 0);
            $fid           = (int)($scope['form_id'] ?? 0);
            $scope_start   = (string)($scope['start_date'] ?? '');
            $scope_end     = (string)($scope['end_date']   ?? '');
            if ($fid <= 0) { continue; }
    
            // Decode targets_json → array(field => target_value)
            $targets_json = $scope['targets_json'] ?? [];
            if (is_string($targets_json)) {
                $decoded = json_decode($targets_json, true);
                if (is_array($decoded)) { $targets_json = $decoded; }
            }
            if (!is_array($targets_json) || empty($targets_json)) { continue; }
    
            // Effective window = overlap(filter_window, scope_window)
            $win_start = max($start_date, $scope_start);
            $win_end   = min($end_date, $scope_end);
            if ($win_end < $win_start) { continue; }
    
            // Achieved per field for this user, within the effective window
            $achieved_by_field = [];
            foreach ((array)$user_history as $sub) {
                if ((int)($sub['form_id'] ?? 0) !== $fid) continue;
                $sub_date = (string)($sub['submission_date'] ?? '');
                if ($sub_date === '' || !$overlaps($win_start, $win_end, $sub_date, $sub_date)) continue;
    
                $fd_raw = $sub['fields_data'] ?? '';
                $fd = [];
                if (is_string($fd_raw) && $fd_raw !== '') {
                    $tmp = json_decode($fd_raw, true);
                    if (is_array($tmp)) { $fd = $tmp; }
                }
    
                foreach ($targets_json as $field => $_targetVal) {
                    if (!array_key_exists($field, $fd)) continue;
                    $val = $fd[$field];
    
                    // normalize numeric
                    if (is_array($val)) {
                        $sum = 0.0;
                        foreach ($val as $vx) { if (is_numeric($vx)) { $sum += (float)$vx; } }
                        $val = $sum;
                    }
                    if (!is_numeric($val)) continue;
    
                    if (!isset($achieved_by_field[$field])) $achieved_by_field[$field] = 0.0;
                    $achieved_by_field[$field] += (float)$val;
                }
            }
    
            // Build detail metrics (for collapsed section)
            $labels = $field_labels[$fid] ?? [];
            $details = [];
            foreach ($targets_json as $field => $targetVal) {
                $t   = (float)$targetVal;
                $a   = (float)($achieved_by_field[$field] ?? 0.0);
                $pct = $t > 0 ? round(($a / $t) * 100, 1) : 0.0;
                $details[] = [
                    'field'        => $field,
                    'label'        => $labels[$field] ?? $field,
                    'target'       => $t,
                    'achieved'     => $a,
                    'progress_pct' => $pct,
                ];
            }
    
            // Scope row (top-level)
            $scope_rows[] = [
                'id'            => (int)($scope['id'] ?? 0),
                'team_id'       => $scope_team_id,
                'team_name'     => $scope_team_id === 0 ? 'Global (All Teams)' : ($teams_map[$scope_team_id] ?? ('Team #'.$scope_team_id)),
                'form_id'       => $fid,
                'form_title'    => $forms_map[$fid]['title'] ?? '—',
                'start_date'    => $scope_start,
                'end_date'      => $scope_end,
                'targets_count' => count($details),
                'details'       => $details, // for expansion
            ];
        }
    
        // Sort: newest scopes first, then by form title
        usort($scope_rows, static function($a, $b) {
            $da = (string)($a['start_date'] ?? '');
            $db = (string)($b['start_date'] ?? '');
            if ($da !== $db) return strcmp($db, $da);
            return strcmp((string)$a['form_title'], (string)$b['form_title']);
        });
    
        $this->load->view('layouts/master', [
            'subview' => 'signoff/my_targets',
            'view_data' => [
                'title'       => 'Assigned Targets',
                'page_title'  => 'My Targets',
                'scopes'      => $scope_rows,    // <— NOTE: view expects "scopes"
                'start_date'  => $start_date,
                'end_date'    => $end_date,
                'table_id'    => 'my_targetsTable',
            ]
        ]);
    }


}
