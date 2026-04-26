<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Signoff extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('signoff/Signoff_forms_model');
        $this->load->model('signoff/Signoff_submissions_model');
        $this->load->model('Teams_model');
        $this->load->model('User_model');
        $this->load->helper('signoff/signoff');
        $this->load->model('Hrm_positions_model');
        $this->load->model('signoff/Targets_model');
        $this->load->model('signoff/Points_model');
        $this->load->model('signoff/Signoff_calendar_model');

        // --- Signoff settings (loaded once into $this->S) ---
        $this->hydrate_signoff_settings();
    }

    /**
     * Utility: Check if current user has one of the given roles
     */
    protected function current_user_has_role(array $roles): bool
    {
        $user_id = (int) $this->session->userdata('user_id');
        if (!$user_id) { return false; }

        $user = $this->User_model->get_user_by_id($user_id);
        if (!$user || empty($user['user_role'])) { return false; }

        $role   = strtolower(trim($user['user_role']));
        $needle = array_map(static function ($r) { return strtolower(trim($r)); }, $roles);
        return in_array($role, $needle, true);
    }

    /**
     * Utility: Check if the current user is an admin (backward-compatible wrapper)
     */
    protected function is_current_user_admin(): bool
    {
        return $this->current_user_has_role(['superadmin', 'admin']);
    }


    /**
     * Utility: Coerce a value to float (arrays are summed).
     * Replaces the duplicated $toNumber closure in submit() and update_submission().
     */
    private function to_number($v): float
    {
        if (is_array($v)) {
            $sum = 0.0;
            foreach ($v as $x) { if (is_numeric($x)) { $sum += (float)$x; } }
            return $sum;
        }
        return is_numeric($v) ? (float)$v : 0.0;
    }

    /**
     * Utility: Send a 403 Forbidden response and exit.
     * Replaces the repeated 4-line block used throughout this controller.
     */
    protected function forbidden(): void
    {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    /**
     * Utility: Record an action in the activity_log table.
     */
    protected function log_activity(string $action): void
    {
        $this->load->model('Activity_log_model');
        $this->Activity_log_model->add([
            'user_id'    => (int)$this->session->userdata('user_id'),
            'action'     => $action,
            'created_at' => $this->tz_now(),
        ]);
    }

    /**
     * Load all signoff-related settings into $this->S (one place, strongly typed).
     */
    protected function hydrate_signoff_settings(): void
    {
        $parseIdList = function ($raw): array {
            if ($raw === null) return [];
            // Normalize input
            $val = trim((string)$raw);
            if ($val === '') return [];
    
            // Try JSON first: e.g., "[18, 19]" or '["18","19"]'
            if ($val[0] === '[' || $val[0] === '{') {
                $decoded = json_decode($val, true);
                if (is_array($decoded)) {
                    // For objects like {"ids":[1,2]}, flatten best-effort
                    if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
                        $decoded = array_values($decoded);
                    }
                    $flat = [];
                    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($decoded));
                    foreach ($it as $v) { $flat[] = $v; }
                    return array_values(array_filter(array_map('intval', $flat)));
                }
            }
    
            // Fallback: CSV / newline / mixed separators
            // Convert any non-digit to a comma, then split
            $val = preg_replace('/[^0-9,]+/', ',', $val);
            $parts = array_map('trim', explode(',', $val));
            $ints  = array_map('intval', array_filter($parts, static fn($p) => $p !== '' && preg_match('/^\d+$/', $p)));
            return array_values(array_unique($ints));
        };
    
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
    
        // Read and validate the timezone setting
        $rawTz   = (string) $get('signoff_default_timezone', '');
        $validTz = '';
        if ($rawTz !== '') {
            try {
                new DateTimeZone($rawTz); // throws if invalid identifier
                $validTz = $rawTz;
            } catch (Exception $e) {
                $validTz = ''; // silently fall back to server default
            }
        }

        $this->S = [
            'enabled'              => ($get('enable_signoff_submissions', 'no') === 'yes'),
            'period'               => (string) $get('signoff_default_period', 'monthly'),
            'indicators'           => (string) $get('signoff_perf_indicators', 'none'),
            'allow_backdated'      => ($get('signoff_allow_backdated', 'no') === 'yes'),
            'auto_approve'         => ($get('signoff_auto_approve', 'yes') === 'yes'),
            'reviewer_user_id'     => (int) $get('signoff_reviewer_user_id', '0'),
            'assign_by_user_id'    => (int) $get('signoff_assign_by_user_id', '0'),
            'lock_after_submit'    => ($get('signoff_lock_after_submit', 'yes') === 'yes'),
            'retention_years'      => max(0, (int) $get('signoff_retention_years', '3')),
            'exclude_position_ids' => $parseIdList($get('signoff_exclude_position_ids', '')),
            // Timezone: validated PHP timezone string, or '' to use server default
            'timezone'             => $validTz,
        ];
    }

    
    // =========================================================================
    // Timezone-aware date helpers
    // All signoff date/time generation goes through these — never raw date().
    // =========================================================================

    /**
     * Returns a DateTimeZone for the configured signoff timezone.
     * Falls back to the PHP server default when none is set.
     */
    protected function signoff_timezone(): DateTimeZone
    {
        $tz = $this->S['timezone'] ?? '';
        return new DateTimeZone($tz !== '' ? $tz : date_default_timezone_get());
    }

    /**
     * Like PHP date() but always in the signoff timezone.
     *
     *   $this->tz_date('Y-m-d')             → today in signoff TZ
     *   $this->tz_date('Y-m-d', $unixTs)    → format a Unix timestamp
     *   $this->tz_date('Y-m-d', '2025-04-01') → format a date string
     */
    protected function tz_date(string $format, $when = null): string
    {
        $tz = $this->signoff_timezone();
        if ($when === null) {
            $dt = new DateTime('now', $tz);
        } elseif (is_int($when)) {
            $dt = new DateTime('@' . $when);
            $dt->setTimezone($tz);
        } else {
            $dt = new DateTime((string)$when, $tz);
        }
        return $dt->format($format);
    }

    /**
     * Returns the current datetime string in the signoff timezone.
     * Use for created_at / updated_at / reviewed_at timestamps.
     */
    protected function tz_now(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->tz_date($format);
    }

    // =========================================================================

    protected function assert_module_enabled_or_403(): void
    {
        if (!$this->S['enabled'] && !$this->is_current_user_admin()) {
            $html = $this->load->view('errors/html/disabled_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    }

    
    /** Is the given user excluded by position settings? */
    protected function is_user_position_excluded(array $user): bool
    {
        if (empty($this->S['exclude_position_ids'])) return false;
        $posId = (int) ($user['emp_title'] ?? 0);
        return in_array($posId, $this->S['exclude_position_ids'], true);
    }
    
    /** Cutoff date from retention policy (or null for no cutoff). */
    protected function retention_cutoff_date(): ?string
    {
        $y = (int) $this->S['retention_years'];
        if ($y <= 0) return null;
        return $this->tz_date('Y-m-d', strtotime('-' . $y . ' years'));
    }
    
    /**
     * Main dashboard: Show forms (admin/manager vs user/teamlead), honoring settings
     */
    public function index()
    {

        // ---------------------------------------
        // Shared assets for Signoff dashboards
        // ---------------------------------------
        $datatable_styles = [
            'assets/vendor/datatable/jquery.dataTables.min.css',
        ];
        
        $datatable_scripts = [
            'assets/vendor/datatable/jquery-3.5.1.js',
            'assets/vendor/datatable/jquery.dataTables.min.js',
            'assets/js/data_table.js',
        ];
        
        // 1) Module gate
        $this->assert_module_enabled_or_403();
    
        // 2) Current user + exclusion gate
        $user_id = (int) $this->session->userdata('user_id');
        $user    = $this->User_model->get_user_by_id($user_id);
    
        if ($this->is_user_position_excluded($user) && !$this->is_current_user_admin()) {
            set_alert('warning', "You're not allowed to use this feature.");
            redirect('dashboard');
        }
    
        // 3) Period label for UI (Daily / Weekly / Monthly)
        $periodLabel = ucfirst((string)($this->S['period'] ?? 'daily'));
    
        // 4) Admin / Manager branch — server-side paginated + filtered
        if ($this->current_user_has_role(['superadmin', 'admin', 'manager', 'director'])) {

            $per_page = 100;
            $page_num = max(1, (int)($this->input->get('page') ?: 1));
            $offset   = ($page_num - 1) * $per_page;

            // --- Read & validate GET filters ---
            $f_from   = trim((string)$this->input->get('from_date'));
            $f_to     = trim((string)$this->input->get('to_date'));
            $f_month  = trim((string)$this->input->get('month'));
            $f_year   = (int)$this->input->get('year');
            $f_user   = (int)$this->input->get('user_id');
            $f_team   = (int)$this->input->get('team_id');
            $f_form   = (int)$this->input->get('form_id');
            $f_status = strtolower(trim((string)$this->input->get('status')));

            // Sanitise
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_from))            { $f_from  = ''; }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_to))              { $f_to    = ''; }
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $f_month))       { $f_month = ''; }
            if ($f_year < 2000 || $f_year > (int)$this->tz_date('Y') + 1)  { $f_year  = 0; }
            if (!in_array($f_status, ['submitted','approved','rejected','excused'], true)) { $f_status = ''; }

            $filters = [
                'from_date'        => $f_from,
                'to_date'          => $f_to,
                'month'            => $f_month,
                'year'             => $f_year ?: '',
                'user_id'          => $f_user,
                'team_id'          => $f_team,
                'form_id'          => $f_form,
                'status'           => $f_status,
                'retention_cutoff' => $this->retention_cutoff_date() ?? '',
            ];

            // --- Paginated fetch ---
            $total_rows  = $this->Signoff_submissions_model->count_paginated_submissions($filters);
            $submissions = $this->Signoff_submissions_model->get_paginated_submissions($filters, $per_page, $offset);

            // --- Forms map for modal field rendering ---
            $forms_map = [];
            foreach ($this->Signoff_forms_model->get_all_forms() as $f) {
                $forms_map[$f['id']] = $f;
            }

            // Attach form_fields JSON to each row (needed by modals)
            foreach ($submissions as &$row) {
                $fid = $row['form_id'] ?? null;
                $row['form_fields'] = isset($forms_map[$fid]['fields']) ? $forms_map[$fid]['fields'] : '[]';
            }
            unset($row);

            // --- Aux data for filter dropdowns ---
            $teams = $this->Teams_model->get_all_teams();
            $users = $this->User_model->get_all_users();
            $all_forms = $this->Signoff_forms_model->get_all_forms();

            // Positions map (id => title)
            $positions_map = [];
            foreach ($this->Hrm_positions_model->get_all_positions() as $p) {
                $positions_map[$p['id']] = $p['title'];
            }

            // --- Build pagination HTML ---
            $total_pages = $per_page > 0 ? (int)ceil($total_rows / $per_page) : 1;
            $total_pages = max(1, $total_pages);

            $filter_qs = http_build_query(array_filter([
                'from_date' => $f_from,
                'to_date'   => $f_to,
                'month'     => $f_month,
                'year'      => $f_year  ?: '',
                'user_id'   => $f_user  ?: '',
                'team_id'   => $f_team  ?: '',
                'form_id'   => $f_form  ?: '',
                'status'    => $f_status,
            ]));

            $pagination_html = '';
            if ($total_pages > 1) {
                $base        = site_url('signoff');
                $link_radius = 2;
                $link_start  = max(1, $page_num - $link_radius);
                $link_end    = min($total_pages, $page_num + $link_radius);
                $mkurl = function (int $p) use ($base, $filter_qs): string {
                    $qs = $filter_qs !== '' ? $filter_qs . '&page=' . $p : 'page=' . $p;
                    return $base . '?' . $qs;
                };
                $html  = '<ul class="pagination pagination-sm mb-0">';
                if ($page_num > 1) {
                    $html .= '<li class="page-item"><a class="page-link" href="' . $mkurl(1) . '">&laquo;</a></li>';
                    $html .= '<li class="page-item"><a class="page-link" href="' . $mkurl($page_num - 1) . '">&lsaquo; Prev</a></li>';
                }
                if ($link_start > 2) {
                    $html .= '<li class="page-item disabled"><a class="page-link" href="#">&hellip;</a></li>';
                }
                for ($p = $link_start; $p <= $link_end; $p++) {
                    if ($p === $page_num) {
                        $html .= '<li class="page-item active"><a class="page-link" href="#">' . $p . '</a></li>';
                    } else {
                        $html .= '<li class="page-item"><a class="page-link" href="' . $mkurl($p) . '">' . $p . '</a></li>';
                    }
                }
                if ($link_end < $total_pages - 1) {
                    $html .= '<li class="page-item disabled"><a class="page-link" href="#">&hellip;</a></li>';
                }
                if ($page_num < $total_pages) {
                    $html .= '<li class="page-item"><a class="page-link" href="' . $mkurl($page_num + 1) . '">Next &rsaquo;</a></li>';
                    $html .= '<li class="page-item"><a class="page-link" href="' . $mkurl($total_pages) . '">&raquo;</a></li>';
                }
                $html .= '</ul>';
                $pagination_html = $html;
            }

            $this->load->view('layouts/master', [
                'subview' => 'signoff/manage',
                'styles'  => $datatable_styles,
                'scripts' => $datatable_scripts,
                'view_data' => [
                    'title'          => 'Team Signoff',
                    'page_title'     => 'Teams Signoff Submissions',
                    'submissions'    => $submissions,
                    'forms'          => $forms_map,
                    'all_forms'      => $all_forms,
                    'teams'          => $teams,
                    'users'          => $users,
                    'positions_map'  => $positions_map,
                    'total_rows'     => $total_rows,
                    'total_pages'    => $total_pages,
                    'per_page'       => $per_page,
                    'page'           => $page_num,
                    'pagination'     => $pagination_html,
                    'table_id'       => 'signoffsubmissionsTable',
                    // Active filter values (to restore UI state)
                    'f_from'         => $f_from,
                    'f_to'           => $f_to,
                    'f_month'        => $f_month,
                    'f_year'         => $f_year,
                    'f_user'         => $f_user,
                    'f_team'         => $f_team,
                    'f_form'         => $f_form,
                    'f_status'       => $f_status,
                ],
            ]);
            return;
        }
    
        // 5) User / Team Lead branch
        $team_id     = $user['emp_team']  ?? null;
        $position_id = $user['emp_title'] ?? null;
    
        // Forms the user can submit & user history
        $forms   = $this->Signoff_forms_model->get_forms_for_user($team_id, $position_id);
        $history = $this->Signoff_submissions_model->get_user_history($user_id);
    
        // Apply retention to history (if configured)
        if ($cutoff = $this->retention_cutoff_date()) {
            $history = array_values(array_filter($history, static function ($r) use ($cutoff) {
                $d = (string)($r['submission_date'] ?? '');
                return ($d === '' || $d >= $cutoff);
            }));
        }
    
        // Status for "today" — use signoff timezone
        $today       = $this->tz_date('Y-m-d');
        $submissions = [];
        foreach ($forms as $form) {
            $submissions[$form['id']] = $this->Signoff_submissions_model
                ->get_by_form_and_user($form['id'], $user_id, $today);
        }
    
        // Positions map for view rendering
        $positions_map = [];
        foreach ($this->Hrm_positions_model->get_all_positions() as $p) {
            $positions_map[$p['id']] = $p['title'];
        }
    
        // Teams map id => name (for showing team name chips in view)
        $teams = [];
        foreach ($this->Teams_model->get_all_teams() as $t) {
            $teams[(int)$t['id']] = (string)$t['name'];
        }
    
        // ===== Monthly progress (Targets vs Achieved) per FORM for this USER =====
        $monthStart = $this->tz_date('Y-m-01');
        $monthEnd   = $this->tz_date('Y-m-t');
    
        // Allowed form ids & titles
        $allowed_form_ids = [];
        $forms_map_titles = [];
        foreach ((array)$forms as $f) {
            $fid = (int)$f['id'];
            $allowed_form_ids[]     = $fid;
            $forms_map_titles[$fid] = (string)$f['title'];
        }
    
        // Scopes applicable to this user (global + their team) intersecting with this month
        $scopes_global = $this->Targets_model->get_scoped_targets([
            'team_id'    => 0,
            'start_date' => $monthStart,
            'end_date'   => $monthEnd,
        ]);
    
        $team_id_int = (int)($user['emp_team'] ?? 0);
        $scopes_team = $team_id_int > 0 ? $this->Targets_model->get_scoped_targets([
            'team_id'    => $team_id_int,
            'start_date' => $monthStart,
            'end_date'   => $monthEnd,
        ]) : [];
    
        $scopes = array_values(array_filter(
            array_merge((array)$scopes_global, (array)$scopes_team),
            function($row) use ($allowed_form_ids) {
                $fid = (int)($row['form_id'] ?? 0);
                return $fid > 0 && in_array($fid, $allowed_form_ids, true);
            }
        ));
    
        // Collect target fields & totals per form
        $target_fields_by_form = []; // fid => set[field] = true
        $target_totals_by_form = []; // fid => float
        foreach ($scopes as $scope) {
            $fid = (int)$scope['form_id'];
            $tj  = $scope['targets_json'] ?? [];
            if (is_string($tj)) { $tmp = json_decode($tj, true); if (is_array($tmp)) { $tj = $tmp; } }
            if (!is_array($tj) || empty($tj)) continue;
    
            if (!isset($target_fields_by_form[$fid])) $target_fields_by_form[$fid] = [];
            if (!isset($target_totals_by_form[$fid])) $target_totals_by_form[$fid] = 0.0;
    
            foreach ($tj as $field => $tval) {
                $target_fields_by_form[$fid][$field] = true;
                if (is_numeric($tval)) { $target_totals_by_form[$fid] += (float)$tval; }
            }
        }
    
        // Sum achieved this month for this user, per form, only for targeted fields
        $achieved_totals_by_form = []; // fid => float
        foreach ((array)$history as $h) {
            $fid = (int)($h['form_id'] ?? 0);
            if (!isset($target_fields_by_form[$fid])) continue; // form has no targets this month
            $d = (string)($h['submission_date'] ?? '');
            if ($d < $monthStart || $d > $monthEnd) continue;
    
            $fd_raw = $h['fields_data'] ?? '';
            $fd = [];
            if (is_string($fd_raw) && $fd_raw !== '') {
                $tmp = json_decode($fd_raw, true);
                if (is_array($tmp)) { $fd = $tmp; }
            }
    
            foreach ($target_fields_by_form[$fid] as $field => $_true) {
                if (!array_key_exists($field, $fd)) continue;
                $val = $fd[$field];
    
                // normalize arrays of numbers
                if (is_array($val)) {
                    $sum = 0.0;
                    foreach ($val as $vx) { if (is_numeric($vx)) $sum += (float)$vx; }
                    $val = $sum;
                }
                if (!is_numeric($val)) continue;
    
                if (!isset($achieved_totals_by_form[$fid])) $achieved_totals_by_form[$fid] = 0.0;
                $achieved_totals_by_form[$fid] += (float)$val;
            }
        }
    
        // Build chart payload (labels, targets, achieved)
        $chart_labels   = [];
        $chart_targets  = [];
        $chart_achieved = [];
        foreach ($target_totals_by_form as $fid => $tSum) {
            $chart_labels[]   = $forms_map_titles[$fid] ?? ('Form #'.$fid);
            $chart_targets[]  = (float)$tSum;
            $chart_achieved[] = (float)($achieved_totals_by_form[$fid] ?? 0.0);
        }
        $progress_chart = [
            'labels'   => $chart_labels,
            'targets'  => $chart_targets,
            'achieved' => $chart_achieved,
            'range'    => ['start' => $monthStart, 'end' => $monthEnd],
        ];
    
        // ── Monthly Compliance Stats via Signoff_calendar_model ─────────────────
        // Build a date => best-status map from history for the current month only.
        // Priority: approved > submitted > excused > rejected
        $statusPriority      = ['approved' => 4, 'submitted' => 3, 'excused' => 2, 'rejected' => 1];
        $monthSubmittedDates = [];

        foreach ($history as $h) {
            $d  = (string)($h['submission_date'] ?? '');
            if ($d < $monthStart || $d > $monthEnd) { continue; }
            $st  = strtolower((string)($h['status'] ?? 'submitted'));
            $pri = $statusPriority[$st] ?? 0;
            if (!isset($monthSubmittedDates[$d]) || $pri > ($statusPriority[$monthSubmittedDates[$d]] ?? 0)) {
                $monthSubmittedDates[$d] = $st;
            }
        }

        // Delegate working-day calculation to the calendar model which checks:
        //   user work-shift off_days, company att_working_days,
        //   public holidays, and approved leaves.
        $monthStats = $this->Signoff_calendar_model->get_working_day_stats(
            $user_id,
            $monthStart,
            $monthEnd,
            $monthSubmittedDates,
            $this->S['timezone'] // pass validated timezone string to calendar model
        );
        // ─────────────────────────────────────────────────────────────────────────

        // Render user dashboard
        $this->load->view('layouts/master', [
            'subview'   => 'signoff/user_signoff',

            // 👇 ASSETS
            'styles'  => $datatable_styles,
            'scripts' => $datatable_scripts,
                
            'view_data' => [
                'title'             => $periodLabel . ' Signoff',
                'page_title'        => $periodLabel . ' Signoff',
                'forms'             => $forms,
                'positions_map'     => $positions_map,
                'submissions'       => $submissions,
                'today'             => $today,
                'history'           => $history,
                'perf_indicators'   => $this->S['indicators'],
                'teams'             => $teams,
                'progress_chart'    => $progress_chart,
                // Monthly compliance stats for the summary cards widget
                // month_stats includes: working_days, submitted, missed, excused,
                //   on_leave, holidays, pending, compliance_rate, month_label,
                //   range, days[] (per-day detail for calendar rendering)
                'month_stats'       => $monthStats,
            ],
        ]);
    }

    public function forms()
    {
        if (! staff_can('view_global','signoff')) {
            $this->forbidden();
        }
    
        $forms      = $this->Signoff_forms_model->get_all_forms();
        $teams      = $this->Teams_model->get_all_teams();
        $teams_map  = [];
        foreach ($teams as $t) { $teams_map[$t['id']] = $t['name']; }
        
        // positions map (id => title)
        $positions_map = [];
        foreach ($this->Hrm_positions_model->get_all_positions() as $p) {
            $positions_map[$p['id']] = $p['title'];
        }
        
        // NEW: load submissions model and compute counters
        $this->load->model('signoff/Signoff_submissions_model', 'subs');
        
        // This month window (inclusive start, exclusive end)
        $monthStart = $this->tz_date('Y-m-01 00:00:00');
        $nextMonth  = $this->tz_date('Y-m-01 00:00:00', strtotime('+1 month'));
        
        $counts_all_time   = $this->subs->counts_all_time();                    // [form_id => n]
        $counts_this_month = $this->subs->counts_between($monthStart, $nextMonth); // [form_id => n]

        // Current performance indicator
        $perf = strtolower(trim((string)($this->S['indicators'] ?? 'none')));
        
        // Lookups: any points assigned per form?
        $has_points_by_form = [];
        $this->db->select('form_id')->from('signoff_points')->group_by('form_id');
        foreach ($this->db->get()->result_array() as $r) {
            $has_points_by_form[(int)$r['form_id']] = true;
        }
        
        // Lookups: any targets assigned per form?
        $has_targets_by_form = [];
        $this->db->select('form_id')->from('signoff_targets')->group_by('form_id');
        foreach ($this->db->get()->result_array() as $r) {
            $has_targets_by_form[(int)$r['form_id']] = true;
        }
        
        $this->load->view('layouts/master', [
            'subview' => 'signoff/forms',
            'view_data' => [
                'title'              => 'Signoff Forms',
                'forms'              => $forms,
                'teams'              => $teams_map,
                'positions_map'      => $positions_map,
                'page_title'         => 'Manage Signoff Forms',
                // pass counters to the view
                'counts_all_time'    => $counts_all_time,
                'counts_this_month'  => $counts_this_month,
                'perf_indicators'    => $perf,
                'has_points_by_form' => $has_points_by_form,
                'has_targets_by_form'=> $has_targets_by_form,                
            ]
        ]);

    }


    /**
     * Admin: Add New Form UI
     */
    public function add_new_form()
    {
        if (! staff_can('view_global','signoff')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        if (!$this->is_current_user_admin()) { show_error('Unauthorized', 403); }
        $teams = $this->Teams_model->get_all_teams();
        $positions = $this->Hrm_positions_model->get_all_positions();

        $this->load->view('layouts/master', [
            'subview' => 'signoff/add_new_form',
            'view_data' => [
                'title'      => 'Create New Form',
                'teams'      => $teams,
                'positions'  => $positions,  // NEW
                'page_title' => 'Forms > New Form',
            ]
        ]);
    }

    /**
     * Admin: Create New Form (POST)
     */
    public function create_form()
    {
        if (!$this->is_current_user_admin()) { show_error('Unauthorized', 403); }
        if ($this->input->server('REQUEST_METHOD') !== 'POST') { show_404(); }
    
        $user_id = $this->session->userdata('user_id');
    
        $this->load->library('form_validation');
        $this->form_validation->set_rules('title', 'Form Title', 'required|max_length[128]');
        $this->form_validation->set_rules('fields', 'Form Fields', 'required');
    
        // Read assignment selector
        $assigned_to = $this->input->post('assigned_to', true); // 'teams' | 'positions'
        if (!in_array($assigned_to, ['teams','positions'], true)) {
            set_alert('danger', 'Please choose a valid "Assigned To" option.');
            redirect('signoff/add_new_form');
        }
    
        if ($assigned_to === 'teams') {
            // Optional (blank => global)
            // Optional team_id: validate only if present (blank means Global)
            $team_id_post = $this->input->post('team_id', true);
            if ($assigned_to === 'teams' && $team_id_post !== '' && $team_id_post !== null) {
                $this->form_validation->set_rules(
                    'team_id',
                    'Team',
                    'integer|greater_than[0]',
                    ['integer' => 'The Team selection is invalid.',
                     'greater_than' => 'Please choose a valid Team.']
                );
            }

        } else {
            // Required
            $this->form_validation->set_rules('position_id', 'Position', 'required|integer');
        }
    
        if ($this->form_validation->run() === FALSE) {
            set_alert('danger', validation_errors());
            redirect('signoff/add_new_form');
        }
    
        $fields = parse_signoff_fields($this->input->post('fields', true));
        if (!is_array($fields)) { $fields = []; }
    
        // Enforce mutual exclusivity at write time
        $team_id     = null;
        $position_id = null;
    
        if ($assigned_to === 'teams') {
            $team_id = $this->input->post('team_id', true) ?: null; // null => global
            $position_id = null;
        } else {
            $position_id = (int)$this->input->post('position_id', true);
            $team_id = null;
        }
    
        $form_data = [
            'title'       => $this->input->post('title', true),
            'team_id'     => $team_id ?: null,
            'position_id' => $position_id ?: null,
            'fields'      => json_encode($fields),
            'is_active'   => 0, // ALWAYS create inactive
            'created_by'  => $user_id,
            'created_at'  => $this->tz_now(),
        ];
    
        $id = $this->Signoff_forms_model->insert_form($form_data);
        if ($id) {
            $this->log_activity('Created signoff form #' . $id . ' — "' . $form_data['title'] . '"');
        }
        set_alert($id ? 'success' : 'danger', $id ? 'Signoff form created successfully.' : 'Error creating signoff form.');
        redirect('signoff/forms');
    }


    /**
     * Admin: Edit Form (UI)
     */
    public function edit_form($id = null)
    {
        if (!$this->is_current_user_admin()) { show_error('Unauthorized', 403); }
        if (!$id) { show_404(); }
    
        $form = $this->Signoff_forms_model->get_form($id);
        if (!$form) {
            set_alert('danger', 'Signoff form not found.');
            redirect('signoff');
        }
    
        $teams     = [];
        foreach ($this->Teams_model->get_all_teams() as $t) { $teams[$t['id']] = $t['name']; }
        $positions = $this->Hrm_positions_model->get_all_positions();
    
        $this->load->view('layouts/master', [
            'subview'   => 'signoff/edit_form', // update this view similarly to add_new_form
            'view_data' => [
                'form'       => $form,
                'teams'      => $teams,
                'positions'  => $positions,
                'title'      => 'Edit Signoff Form',
            ]
        ]);
    }


    /**
     * Admin: Update Form (POST)
     */
    public function update_form($id = null)
    {
        if (!$this->is_current_user_admin()) { show_error('Unauthorized', 403); }
        if (!$id || $this->input->server('REQUEST_METHOD') !== 'POST') { show_404(); }
    
        $this->load->library('form_validation');
        $this->form_validation->set_rules('title', 'Form Title', 'required|max_length[128]');
        $this->form_validation->set_rules('fields', 'Form Fields', 'required');
    
        $assigned_to = $this->input->post('assigned_to', true); // 'teams'|'positions'
        if (!in_array($assigned_to, ['teams','positions'], true)) {
            set_alert('danger', 'Please choose a valid "Assigned To" option.');
            redirect('signoff/edit_form/' . $id);
        }
        if ($assigned_to === 'positions') {
            $this->form_validation->set_rules('position_id', 'Position', 'required|integer');
        }
    
        if ($this->form_validation->run() === FALSE) {
            set_alert('danger', validation_errors());
            redirect('signoff/edit_form/' . $id);
        }
    
        $fields = parse_signoff_fields($this->input->post('fields', true));
        if (!is_array($fields)) { $fields = []; }
    
        $team_id     = null;
        $position_id = null;
        if ($assigned_to === 'teams') {
            $team_id = $this->input->post('team_id', true) ?: null;
        } else {
            $position_id = (int)$this->input->post('position_id', true);
        }
    
        $data = [
            'title'       => $this->input->post('title', true),
            'team_id'     => $team_id ?: null,
            'position_id' => $position_id ?: null,
            'fields'      => json_encode($fields),
            'is_active'   => $this->input->post('is_active') ? 1 : 0,
            'updated_at'  => $this->tz_now(),
        ];
    
        $ok = $this->Signoff_forms_model->update_form($id, $data);
        if ($ok) {
            $this->log_activity('Updated signoff form #' . (int)$id . ' — "' . $data['title'] . '"');
        }
        set_alert($ok ? 'success' : 'danger', $ok ? 'Signoff form updated.' : 'Failed to update.');
        redirect('signoff/forms');
    }



    /** Activate/Deactivate a form (guarded; activation checks readiness under current indicator) */
    public function toggle_form_status($id = null)
    {
        if (!$this->is_current_user_admin()) { show_error('Unauthorized', 403); }
        if (!$id) { show_404(); }
    
        $form = $this->Signoff_forms_model->get_form((int)$id);
        if (!$form) { show_404(); }
    
        $current = (int)($form['is_active'] ?? 0);
        $target  = $current ? 0 : 1;
    
        if ($target === 1) {
            // Enabling: enforce readiness under current Performance Indicator
            $perf = strtolower(trim((string)($this->S['indicators'] ?? 'none')));
    
            if ($perf === 'targets') {
                $has = $this->db->select('id')->from('signoff_targets')
                    ->where('form_id', (int)$id)->limit(1)->get()->row_array();
                if (!$has) {
                    set_alert('danger', 'Cannot activate: assign Targets to this form or change the Performance Indicator to Points or None.');
                    redirect('signoff/forms');
                }
            } elseif ($perf === 'points') {
                $has = $this->db->select('id')->from('signoff_points')
                    ->where('form_id', (int)$id)->limit(1)->get()->row_array();
                if (!$has) {
                    set_alert('danger', 'Cannot activate: assign Points to this form or change the Performance Indicator to Targets or None.');
                    redirect('signoff/forms');
                }
            }
            // 'none' ⇒ no gating
        }
    
        $ok = $this->Signoff_forms_model->update_form((int)$id, [
            'is_active'  => $target,
            'updated_at' => $this->tz_now(),
        ]);
    
        if ($ok) {
            $this->log_activity(($target ? 'Activated' : 'Deactivated') . ' signoff form #' . (int)$id . ' — "' . $form['title'] . '"');
        }
        set_alert($ok ? 'success' : 'danger', $ok
            ? ($target ? 'Form activated.' : 'Form deactivated.')
            : 'Failed to update form status.');
    
        redirect('signoff/forms');
    }


    public function clone_form($id = null)
    {
        if (!$this->is_current_user_admin()) { show_error('Unauthorized', 403); }
        if (!$id || $this->input->server('REQUEST_METHOD') !== 'POST') { show_404(); }
    
        $src = $this->Signoff_forms_model->get_form((int)$id);
        if (!$src) {
            set_alert('danger', 'Source form not found.');
            redirect('signoff/forms');
        }
    
        // Create a safe default title; keep fields; reset scope; keep inactive.
        $newTitle = trim((string)$src['title']) !== '' ? ($src['title'] . ' (Copy)') : 'Untitled (Copy)';
    
        $data = [
            'title'       => $newTitle,
            'team_id'     => null,                       // reset scope so you can re-assign
            'position_id' => null,                       // reset scope so you can re-assign
            'fields'      => (string)($src['fields'] ?? '[]'),
            'is_active'   => 0,                          // copies are inactive by default
            'created_by'  => (int)$this->session->userdata('user_id'),
            'created_at'  => $this->tz_now(),
        ];
    
        $newId = $this->Signoff_forms_model->insert_form($data);
    
        if ($newId) {
            $this->log_activity('Cloned signoff form #' . (int)$id . ' — new form #' . $newId . ' "' . $newTitle . '"');
            set_alert('success', 'Form cloned. You can now assign the new form to a different scope.');
        } else {
            set_alert('danger', 'Failed to clone form.');
        }
        redirect('signoff/forms');
    }

    /**
     * Admin: Delete Form
     */
    public function delete_form($id = null)
    {
        if (!$this->is_current_user_admin()) { show_error('Unauthorized', 403); }
        if (!$id) { show_404(); }

        $ok = $this->Signoff_forms_model->delete_form($id);
        if ($ok) {
            $this->log_activity('Deleted signoff form #' . (int)$id);
        }
        set_alert($ok ? 'success' : 'danger', $ok ? 'Signoff form deleted.' : 'Failed to delete.');
        redirect('signoff/forms');
    }

    
    /**
     * USER: Submit a signoff (respects allow_backdated, lock_after_submit, auto_approve)
     */
    public function submit($form_id = null)
    {
        // Gate: module enabled
        $this->assert_module_enabled_or_403();
    
        $user_id = (int)$this->session->userdata('user_id');
        $user    = $this->User_model->get_user_by_id($user_id);
    
        if (!$form_id) { show_404(); }
    
        // Block excluded positions (non-admins)
        if ($this->is_user_position_excluded($user) && !$this->is_current_user_admin()) {
            set_alert('warning', "You're not allowed to use this feature.");
            redirect('dashboard');
        }
    
        $form = $this->Signoff_forms_model->get_form($form_id);
        if (!$form || !(int)$form['is_active']) {
            set_alert('danger', 'Form is not available.');
            redirect('signoff');
        }
    
        // Only allowed if user in assigned team (or global) or assigned position
        $allowed = false;
    
        // Global
        if (empty($form['team_id']) && empty($form['position_id'])) {
            $allowed = true;
        }
    
        // Team-based
        if (!$allowed && !empty($form['team_id'])) {
            $allowed = ((int)$user['emp_team'] === (int)$form['team_id']);
        }
    
        // Position-based
        if (!$allowed && !empty($form['position_id'])) {
            $allowed = ((int)$user['emp_title'] === (int)$form['position_id']);
        }
    
        if (!$allowed && !$this->is_current_user_admin()) {
            set_alert('warning', 'You are not allowed to submit this signoff form.');
            redirect('signoff');
        }
    
        // --- Submission date (supports allow_backdated) ---
        // We keep UI the same (no date picker). If a field named 'submission_date' is posted, validate it.
        $today          = $this->tz_date('Y-m-d');
        $postedDateRaw  = $this->input->post('submission_date', true); // optional
        $submit_date    = $today;
    
        if ($this->input->server('REQUEST_METHOD') === 'POST' && $postedDateRaw) {
            $d = $this->tz_date('Y-m-d', $postedDateRaw);
            if ($d !== $today && !$this->S['allow_backdated']) {
                set_alert('danger', 'Backdated submissions are disabled by admin.');
                redirect('signoff/submit/' . (int)$form_id);
            }
            $submit_date = $d;
        }
    
        $submit_date_full = $this->tz_date('l, F d, Y', $submit_date);
    
        // Check for existing submission for the chosen date
        $existing_submission = $this->Signoff_submissions_model
            ->get_by_form_and_user($form_id, $user_id, $submit_date);
    
        // Honor Lock After Submission
        if ($existing_submission && $this->S['lock_after_submit']) {
            set_alert('warning', 'A submission already exists for ' . $this->tz_date('M j, Y', $submit_date) . ' and editing is locked.');
            redirect('signoff');
        }
    
        // -------------------- POST: save / update --------------------
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            // 1) Decode fields with legacy CSV fallback
            $rawFields = (string)$form['fields'];
            $fields = json_decode($rawFields, true);
            if (!is_array($fields)) {
                $fields = parse_signoff_fields($rawFields);
            }
    
            // 2) File-field discovery (first file field drives the single uploader)
            $fileFieldNames    = [];
            $fileFieldLabel    = 'Attachment';
            $fileFieldRequired = false;
            foreach ($fields as $f) {
                if (strtolower($f['type'] ?? '') === 'file' && !empty($f['name'])) {
                    $fileFieldNames[] = (string)$f['name'];
                    if (!empty($f['label']))    { $fileFieldLabel    = $f['label']; }
                    if (!empty($f['required'])) { $fileFieldRequired = true; }
                    break;
                }
            }
    
            // 3) Build fields_data from POST (do NOT cast arrays to string)
            $postedFields = $this->input->post('fields');
            $postedFields = is_array($postedFields) ? $postedFields : [];
    
            $submission_data = [];
            foreach ($fields as $field) {
                $key = $field['name'] ?? null;
                if (!$key) { continue; }
                if (in_array($key, $fileFieldNames, true)) { continue; } // skip file input; handled below
    
                if (array_key_exists($key, $postedFields)) {
                    $val = $postedFields[$key];
                    if (is_array($val)) {
                        $val = array_values(array_filter($val, static function($v) { return $v !== '' && $v !== null; }));
                    } elseif (!is_string($val)) {
                        $val = (string)$val;
                    }
                    $submission_data[$key] = $val;
                } else {
                    $submission_data[$key] = '';
                }
            }
    
            // 4) File upload into dedicated column signoff_attachment
            $attachment_path = !empty($existing_submission['signoff_attachment'])
                ? $existing_submission['signoff_attachment']
                : null;
    
            // Remap file input if it came via fields[<file_field_name>] (legacy support)
            if (empty($_FILES['signoff_attachment']['name']) && !empty($fileFieldNames)) {
                $ff = $fileFieldNames[0];
                if (!empty($_FILES['fields']['name'][$ff])) {
                    $_FILES['signoff_attachment'] = [
                        'name'     => $_FILES['fields']['name'][$ff],
                        'type'     => $_FILES['fields']['type'][$ff],
                        'tmp_name' => $_FILES['fields']['tmp_name'][$ff],
                        'error'    => $_FILES['fields']['error'][$ff],
                        'size'     => $_FILES['fields']['size'][$ff],
                    ];
                }
            }
    
            if (!empty($_FILES['signoff_attachment']['name'])) {
                $upload_path_fs  = FCPATH . 'uploads/signoff_attachments/';
                $upload_path_rel = 'uploads/signoff_attachments/';
    
                if (!is_dir($upload_path_fs)) { @mkdir($upload_path_fs, 0775, true); }
    
                $config = [
                    'upload_path'            => $upload_path_fs,
                    'allowed_types'          => 'pdf|jpg|jpeg|png|doc|docx|xls|xlsx|txt',
                    'max_size'               => 10240, // 10 MB
                    'encrypt_name'           => true,
                    'remove_spaces'          => true,
                    'file_ext_tolower'       => true,
                    'detect_mime'            => true,
                    'overwrite'              => false,
                    'max_filename_increment' => 100,
                ];
                $this->load->library('upload', $config);
                $this->upload->initialize($config, true);
    
                if (!$this->upload->do_upload('signoff_attachment')) {
                    $error = $this->upload->display_errors('', '');
                    set_alert('danger', 'Attachment upload failed: ' . $error);
                    if ($fileFieldRequired && empty($attachment_path)) {
                        redirect('signoff/submit/' . (int)$form_id);
                    }
                } else {
                    $dataUpload   = $this->upload->data();
                    $new_file_rel = $upload_path_rel . $dataUpload['file_name'];
    
                    // Remove old file if replacing
                    if (!empty($attachment_path)) {
                        $oldAbs = FCPATH . $attachment_path;
                        if (is_file($oldAbs)) { @unlink($oldAbs); }
                    }
                    $attachment_path = $new_file_rel;
                }
            } else {
                if ($fileFieldRequired && empty($attachment_path)) {
                    set_alert('danger', $fileFieldLabel . ' is required.');
                    redirect('signoff/submit/' . (int)$form_id);
                }
            }

        // Start points calcualtion and submit in the table // 
        // ================== PERFORMANCE INDICATORS ==================
        $indicators = strtolower(trim((string)($this->S['indicators'] ?? 'none')));
        
        $totalPoints = null;     // set only when indicators includes "points"
        $achieved    = null;     // set only when indicators includes "targets"
        $userTeamId  = (int)($user['emp_team'] ?? 0);
        
        // -------- Points: sum(field_contribution * weight) --------
        // Scoring rules per field type:
        //   Numeric fields (number, amount): contribution = numeric value entered
        //   Non-numeric fields (text, textarea, select, etc.):
        //     contribution = 1.0 if the field has any non-empty value, else 0
        //     (presence-based scoring — the weight IS the flat award)
        // A submission where ALL weighted fields are empty gets null, not 0.0.
        if ($indicators === 'points' || $indicators === 'both') {
            $rowPts = $this->db->select('points_json')
                ->from('signoff_points')
                ->where('form_id', (int)$form_id)
                ->where_in('team_id', [$userTeamId, 0])
                ->order_by('team_id', 'DESC')
                ->order_by('updated_at', 'DESC')
                ->limit(1)
                ->get()->row_array();

            $weights = [];
            if (!empty($rowPts['points_json'])) {
                $decoded = json_decode($rowPts['points_json'], true);
                if (is_array($decoded)) { $weights = $decoded; }
            }

            // Build a field-type lookup from the form schema for scoring mode detection
            $fieldTypeLookup = [];
            foreach ($fields as $f) {
                $fn = $f['name'] ?? '';
                if ($fn !== '') {
                    $fieldTypeLookup[$fn] = strtolower($f['type'] ?? 'text');
                }
            }
            $numericTypes = ['number', 'amount'];

            $sumPoints        = 0.0;
            $anyWeightedField = false; // track whether at least one weighted field existed

            foreach ($weights as $field => $weight) {
                if (!array_key_exists($field, $submission_data)) { continue; }

                $anyWeightedField = true;
                $raw = $submission_data[$field];
                $w   = is_numeric($weight) ? (float)$weight : 1.0;

                $fieldType = $fieldTypeLookup[$field] ?? 'text';

                if (in_array($fieldType, $numericTypes, true)) {
                    // Numeric field: points = entered_value * weight
                    // Empty/zero entry = 0 points (no ghost points)
                    $num = $this->to_number($raw);
                    $sumPoints += ($num * $w);
                } else {
                    // Non-numeric field: presence-based — award weight if field is non-empty
                    $isEmpty = ($raw === '' || $raw === null || $raw === [] ||
                                (is_array($raw) && count(array_filter($raw, static fn($x) => $x !== '' && $x !== null)) === 0));
                    if (!$isEmpty) {
                        $sumPoints += $w;
                    }
                    // Empty non-numeric field = 0 contribution (no ghost points from weight alone)
                }
            }

            // Only store points when at least one weighted field was part of this form.
            // A truly empty submission (no weighted fields filled) saves null, not 0.
            $totalPoints = $anyWeightedField ? $sumPoints : null;
        }

        // -------- Targets: sum(field_value) for targeted numeric fields only --------
        // Non-numeric fields do not contribute to achieved targets.
        // Empty submissions produce null (not 0.0) for achieved_targets.
        if ($indicators === 'targets' || $indicators === 'both') {
            $rowTgt = $this->db->select('targets_json')
                ->from('signoff_targets')
                ->where('form_id', (int)$form_id)
                ->where('start_date <=', $submit_date)
                ->where('end_date >=',   $submit_date)
                ->where_in('team_id', [$userTeamId, 0])
                ->order_by('team_id', 'DESC')
                ->order_by('updated_at', 'DESC')
                ->limit(1)
                ->get()->row_array();

            $targets = [];
            if (!empty($rowTgt['targets_json'])) {
                $decoded = json_decode($rowTgt['targets_json'], true);
                if (is_array($decoded)) { $targets = $decoded; }
            }

            $sumAchieved      = 0.0;
            $anyTargetedField = false;

            foreach ($targets as $field => $targetValue) {
                if (!array_key_exists($field, $submission_data)) { continue; }
                $anyTargetedField = true;
                $sumAchieved += $this->to_number($submission_data[$field]);
            }

            // null when no targeted fields found (no targets configured for this date range)
            $achieved = $anyTargetedField ? $sumAchieved : null;
        }
        // ================== End Performance Indicators ==================



            // 5) Persist (respect auto_approve)
            $payload = [
                'form_id'            => (int)$form_id,
                'user_id'            => (int)$user_id,
                'team_id'            => $user['emp_team'] ?? null,
                'submission_date'    => $submit_date,
                'fields_data'        => json_encode($submission_data, JSON_UNESCAPED_UNICODE),
                'status'             => 'submitted', // may be overwritten below
                'signoff_attachment' => $attachment_path,
            ];
    
            if ($this->S['auto_approve']) {
                $payload['status']      = 'approved';
                // Use designated reviewer if set; otherwise fall back to the submitting user;
                // never write 0 which would be an invalid FK reference.
                $designatedReviewer = (int)($this->S['reviewer_user_id'] ?? 0);
                $payload['reviewed_by'] = $designatedReviewer > 0 ? $designatedReviewer : (int)$user_id;
                $payload['reviewed_at'] = $this->tz_now();
            }

            // Attach metrics if computed ---- start points saving in db 
            if ($totalPoints !== null) {
                $payload['total_points'] = $totalPoints;           // DECIMAL/DOUBLE column in signoff_submissions
            }
            if ($achieved !== null) {
                $payload['achieved_targets'] = $achieved;          // DECIMAL/DOUBLE column in signoff_submissions
            }
            // end points saving in db 
            
            if ($existing_submission) {
                // If we got here, lock is OFF; allow update
                $payload['updated_at'] = $this->tz_now();
                $this->Signoff_submissions_model->update_submission($existing_submission['id'], $payload);
            } else {
                $payload['created_at'] = $this->tz_now();
                $this->Signoff_submissions_model->insert_submission($payload);
            }
    
            $this->log_activity('Submitted signoff form #' . (int)$form_id .
                ' for date ' . $submit_date .
                ($this->S['auto_approve'] ? ' (auto-approved)' : ' (pending approval)'));

            set_alert('success', $this->S['auto_approve']
                ? 'Signoff submitted and auto-approved.'
                : 'Signoff submitted and pending approval.');
    
            redirect('signoff');
        }
        // ------------------ /POST ------------------
    
        // ------------------ GET: render form ------------------
        // Decode with legacy CSV fallback
        $rawFields = (string)$form['fields'];
        $fields = json_decode($rawFields, true);
        if (!is_array($fields)) {
            $fields = parse_signoff_fields($rawFields);
        }
    
        // Find if a file field exists, capture its label, then filter it out for the dynamic renderer
        $has_file_field   = false;
        $file_field_label = 'Attachment';
        foreach ($fields as $f) {
            if (strtolower($f['type'] ?? '') === 'file') {
                $has_file_field = true;
                if (!empty($f['label'])) { $file_field_label = $f['label']; }
                break;
            }
        }
        $fields = array_values(array_filter($fields, function ($f) {
            return strtolower($f['type'] ?? '') !== 'file';
        }));
    
        // Monthly stats for the submit form sidebar (submitted/missed counts)
        $__monthStart = $this->tz_date('Y-m-01');
        $__monthEnd   = $this->tz_date('Y-m-t');

        // Build date=>status map from user's history for this month
        $__history = $this->Signoff_submissions_model->get_user_history($user_id);
        $__statusPriority = ['approved' => 4, 'submitted' => 3, 'excused' => 2, 'rejected' => 1];
        $__submittedDates = [];
        foreach ($__history as $__h) {
            $__d = (string)($__h['submission_date'] ?? '');
            if ($__d < $__monthStart || $__d > $__monthEnd) { continue; }
            $__st  = strtolower((string)($__h['status'] ?? 'submitted'));
            $__pri = $__statusPriority[$__st] ?? 0;
            if (!isset($__submittedDates[$__d]) || $__pri > ($__statusPriority[$__submittedDates[$__d]] ?? 0)) {
                $__submittedDates[$__d] = $__st;
            }
        }
        $__monthStats = $this->Signoff_calendar_model->get_working_day_stats(
            $user_id, $__monthStart, $__monthEnd, $__submittedDates, $this->S['timezone']
        );

        $this->load->view('layouts/master', [
            'subview' => 'signoff/submit_form',
            'view_data' => [
                'form'                => $form,
                'fields'              => $fields,
                'existing_submission' => $existing_submission,
                'today_full'          => $submit_date_full,
                'title'               => 'Submit Signoff',
                'has_file_field'      => $has_file_field,
                'file_field_label'    => $file_field_label,
                'month_stats'         => $__monthStats,
                'submit_date'         => $submit_date,
            ]
        ]);
    }



    /**
     * USER: View own submission history with filters (year/month/range/status)
     */
    public function signoff_history()
    {
        // Module gate
        $this->assert_module_enabled_or_403();
    
        $user_id = (int)$this->session->userdata('user_id');
        $user    = $this->User_model->get_user_by_id($user_id);
    
        // Exclude positions (non-admins)
        if ($this->is_user_position_excluded($user) && !$this->is_current_user_admin()) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        // ---------- Read filters from GET ----------
        // mode: all|year|month|range
        $mode   = strtolower(trim((string)$this->input->get('mode'))) ?: 'all';
    
        // year: 2023, 2024, ...
        $year   = (int)$this->input->get('year');
        if ($year < 2000 || $year > (int)$this->tz_date('Y') + 1) { $year = 0; }
    
        // month: YYYY-MM (e.g., 2025-09)
        $month  = trim((string)$this->input->get('month'));
        $month  = preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $month) ? $month : '';
    
        // range: start, end (YYYY-MM-DD)
        $start  = trim((string)$this->input->get('start'));
        $end    = trim((string)$this->input->get('end'));
        $start  = preg_match('/^\d{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[12]\d|3[01])$/', $start) ? $start : '';
        $end    = preg_match('/^\d{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[12]\d|3[01])$/', $end) ? $end : '';
    
        $status = strtolower(trim((string)$this->input->get('status')));
        $status = in_array($status, ['submitted','approved','rejected','excused'], true) ? $status : '';

        // Date window — defaults to no window; switch below overrides per mode
        $start_date = '';
        $end_date   = '';

        switch ($mode) {
            case 'year':
                if ($year > 0) {
                    $start_date = sprintf('%04d-01-01', $year);
                    $end_date   = sprintf('%04d-12-31', $year);
                }
                break;
    
            case 'month':
                if ($month !== '') {
                    $start_date = $month . '-01';
                    $end_date   = $this->tz_date('Y-m-t', strtotime($start_date));
                }
                break;
    
            case 'range':
                if ($start !== '' && $end !== '') {
                    // normalize start <= end
                    if ($start > $end) { [$start, $end] = [$end, $start]; }
                    $start_date = $start;
                    $end_date   = $end;
                }
                break;
    
            case 'all':
            default:
                // no window
                break;
        }
    
        // ---------- Fetch + retention filter ----------
        $history = $this->Signoff_submissions_model->get_user_history($user_id);
    
        // Retention filter (as before)
        if ($cutoff = $this->retention_cutoff_date()) {
            $history = array_values(array_filter($history, static function ($r) use ($cutoff) {
                $d = (string)($r['submission_date'] ?? '');
                return ($d === '' || $d >= $cutoff);
            }));
        }
    
        // ---------- Apply user filters in-memory (safe, non-breaking) ----------
        if ($start_date || $end_date || $status) {
            $history = array_values(array_filter($history, static function ($r) use ($start_date, $end_date, $status) {
                $d = (string)($r['submission_date'] ?? '');
                if ($d !== '' && $start_date && $d < $start_date) return false;
                if ($d !== '' && $end_date   && $d > $end_date)   return false;
                if ($status !== '' && strtolower((string)($r['status'] ?? '')) !== $status) return false;
                return true;
            }));
        }
    
        // Sort newest first (stable UX)
        usort($history, static function ($a, $b) {
            $da = (string)($a['submission_date'] ?? '');
            $db = (string)($b['submission_date'] ?? '');
            if ($da === $db) return 0;
            return ($da < $db) ? 1 : -1; // DESC
        });
    
        // Build years dropdown (last 7 years by default)
        $years = [];
        $yNow  = (int)$this->tz_date('Y');
        for ($y = $yNow; $y >= $yNow - 6; $y--) { $years[] = $y; }
    
        // Pass filters to the view to keep state in the UI
        $filters = [
            'mode'   => $mode,
            'year'   => $year,
            'month'  => $month,
            'start'  => $start,
            'end'    => $end,
            'status' => $status,
            'years'  => $years,
        ];
    
        $this->load->view('layouts/master', [
            'subview' => 'signoff/signoff_history',
            'view_data' => [
                'title'      => 'Signoff History',
                'history'    => $history,
                'page_title' => 'Signoff History',
                'filters'    => $filters,
                'table_id'   => 'signoffhistoryTable',
            ]
        ]);
    }



    /**
     * ADMIN: View all submissions for a form (optionally by date)
     */
    public function view_submissions($form_id)
    {
        // Module gate
        $this->assert_module_enabled_or_403();
    
        if (! staff_can('own_team','signoff')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        // Existing filters...
        $month   = $this->input->get('month') ?: $this->tz_date('Y-m');
        $status  = $this->input->get('status');
        $user_id = $this->input->get('user_id');
        $form    = $this->Signoff_forms_model->get_form($form_id);
    
        $start_date = $month . '-01';
        $end_date   = $this->tz_date('Y-m-t', strtotime($start_date));
    
        $submissions = $this->Signoff_submissions_model
            ->get_filtered_submissions($form_id, $start_date, $end_date, $status, $user_id);
    
        // Retention filter
        if ($cutoff = $this->retention_cutoff_date()) {
            $submissions = array_values(array_filter($submissions, static function ($r) use ($cutoff) {
                $d = (string)($r['submission_date'] ?? '');
                return ($d === '' || $d >= $cutoff);
            }));
        }
    
        foreach ($submissions as &$sub) {
            $sub['form_fields'] = $form && !empty($form['fields']) ? $form['fields'] : '[]';
        }
        unset($sub);
    
        $users = $this->User_model->get_all_users();
    
        $this->load->view('layouts/master', [
            'subview' => 'signoff/view_submissions',
            'view_data' => [
                'title'        => 'Signoff Submissions',
                'form'         => $form,
                'submissions'  => $submissions,
                'month'        => $month,
                'status'       => $status,
                'user_id'      => $user_id,
                'users'        => $users
            ]
        ]);
    }



    /**
     * ADMIN/MANAGER: Approve or Reject a user's submission
     */
    public function review_submission($submission_id, $action)
    {
        // Module gate
        $this->assert_module_enabled_or_403();
    
        $action  = strtolower((string)$action);
        $allowed = ['approved', 'rejected', 'excused'];
        if (!in_array($action, $allowed, true)) { show_404(); }
    
        $submission_id = (int)$submission_id;
        $submission    = $this->Signoff_submissions_model->get_submission($submission_id);
        if (!$submission) { show_404(); }
    
        $current_uid = (int)$this->session->userdata('user_id');
    
        // -------- PERMISSIONS (admin / reviewer / can_approve) --------
        $canApprovePerm = staff_can('approve', 'signoff');
        
        if ($this->S['auto_approve']) {
            // With auto-approve ON, restrict manual overrides to:
            // - admins OR
            // - users who have explicit approve permission
            if (!$this->is_current_user_admin() && !$canApprovePerm) {
                $this->forbidden();
            }
        } else {
            // Auto-approve OFF: allow
            // - admin OR
            // - designated reviewer OR
            // - users with approve permission
            $reviewerId = (int)($this->S['reviewer_user_id'] ?? 0);
            $isReviewer = ($reviewerId > 0 && $current_uid === $reviewerId);
        
            if (
                !$this->is_current_user_admin()
                && !$isReviewer
                && !$canApprovePerm
            ) {
                $this->forbidden();
            }
        }
    
        // ---- STATUS-ONLY UPDATE (do not touch fields_data or any other column) ----
        $this->db->trans_start();
        $this->db->where('id', $submission_id)
                 ->set('status', $action)
                 ->set('reviewed_by', $current_uid)
                 ->set('reviewed_at', $this->tz_now())
                 ->update('signoff_submissions');
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
    
        if ($ok) {
            notify_user(
                (int)$submission['user_id'],
                'signoff',
                'Signoff ' . ucfirst($action),
                'Your signoff submitted on ' .
                $this->tz_date('M j, Y', $submission['submission_date']) .
                ' was ' . ucfirst($action) . '.'
            );

            // Activity log
            $this->load->model('Activity_log_model');
            $this->Activity_log_model->add([
                'user_id'    => $current_uid,
                'action'     => 'Signoff submission #' . $submission_id . ' marked as ' . ucfirst($action) .
                                ' (form_id: ' . (int)$submission['form_id'] .
                                ', user_id: ' . (int)$submission['user_id'] . ')',
                'created_at' => $this->tz_now(),
            ]);
        }
    
        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode([
                             'ok'            => (bool)$ok,
                             'message'       => $ok ? 'Submission ' . ucfirst($action) . '.' : 'Failed.',
                             'new_status'    => $action,
                             'submission_id' => $submission_id,
                         ]));
            return;
        }
    
        set_alert($ok ? 'success' : 'danger', $ok ? 'Submission ' . ucfirst($action) . '.' : 'Failed.');
        $ref = $this->input->server('HTTP_REFERER');
        redirect(!empty($ref) ? $ref : ('signoff/view_submissions/' . (int)$submission['form_id']));
    }



    /**
     * ADMIN/MANAGER: Edit a submitted signoff (fields + attachment).
     * - Recalculates points/targets
     * - Logs who edited and what changed
     * - Notifies the owner user with old vs new summary
     */
    public function update_submission($submission_id = null)
    {
        // Module gate
        $this->assert_module_enabled_or_403();

        $submission_id = (int) $submission_id;
        if (!$submission_id || $this->input->server('REQUEST_METHOD') !== 'POST') {
            show_404();
        }

        // Load existing submission
        $submission = $this->Signoff_submissions_model->get_submission($submission_id);
        if (!$submission) {
            show_404();
        }

        $current_uid = (int) $this->session->userdata('user_id');

        // -------- PERMISSIONS (admin / reviewer / can_approve) --------
        $canApprovePerm = staff_can('approve', 'signoff');
        
        if ($this->S['auto_approve']) {
            // With auto-approve ON, restrict edits to admins OR explicit approvers
            if (!$this->is_current_user_admin() && !$canApprovePerm) {
                $this->forbidden();
            }
        } else {
            // Auto-approve OFF: allow admin OR designated reviewer OR explicit approvers
            $reviewerId = (int)($this->S['reviewer_user_id'] ?? 0);
            $isReviewer = ($reviewerId > 0 && $current_uid === $reviewerId);
        
            if (
                !$this->is_current_user_admin()
                && !$isReviewer
                && !$canApprovePerm
            ) {
                $this->forbidden();
            }
        }

        // -------- GUARD: 30-day retention / edit lock (same as view modal) --------
        $__dateStr  = $submission['submission_date'] ?? ($submission['created_at'] ?? '');
        $__ts       = $__dateStr ? strtotime($__dateStr) : false;
        $__cutoffTs = (new DateTime('now', $this->signoff_timezone()))->modify('-30 days')->getTimestamp();
        $__isOld    = ($__ts && $__ts < $__cutoffTs);

        if ($__isOld) {
            set_alert('warning', 'Editing is locked for submissions older than 30 days.');
            $ref = $this->input->server('HTTP_REFERER');
            redirect(!empty($ref) ? $ref : 'signoff');
        }

        // -------- Load form definition & user (for metrics) --------
        $form_id  = (int) $submission['form_id'];
        $form     = $this->Signoff_forms_model->get_form($form_id);
        $user     = $this->User_model->get_user_by_id((int)$submission['user_id']);

        // Guard: form may have been deleted after submission was created
        if (!$form) {
            set_alert('danger', 'The form linked to this submission no longer exists and cannot be edited.');
            $ref = $this->input->server('HTTP_REFERER');
            redirect(!empty($ref) ? $ref : 'signoff');
        }

        // Guard: submitting user may have been deleted
        if (!$user) {
            set_alert('danger', 'The user linked to this submission no longer exists and cannot be edited.');
            $ref = $this->input->server('HTTP_REFERER');
            redirect(!empty($ref) ? $ref : 'signoff');
        }

        $team_id  = (int)($user['emp_team'] ?? 0);
        $sub_date = $submission['submission_date'] ?: $this->tz_date('Y-m-d');

        // Decode fields schema (like in submit())
        $rawFields = (string)($form['fields'] ?? '[]');
        $fields    = json_decode($rawFields, true);
        if (!is_array($fields)) {
            $fields = parse_signoff_fields($rawFields);
        }

        // Decode old fields_data for diff
        $old_fields_data = [];
        if (!empty($submission['fields_data'])) {
            $tmp = json_decode((string)$submission['fields_data'], true);
            if (is_array($tmp)) { $old_fields_data = $tmp; }
        }
        $old_attachment = $submission['signoff_attachment'] ?? null;
        $old_points     = isset($submission['total_points'])       ? (float)$submission['total_points']       : null;
        $old_achieved   = isset($submission['achieved_targets'])   ? (float)$submission['achieved_targets']   : null;

        // -------- Build updated fields_data from POST --------
        $postedFields = $this->input->post('fields');
        $postedFields = is_array($postedFields) ? $postedFields : [];

        $updated_fields_data = [];
        foreach ($fields as $f) {
            $key = $f['name'] ?? null;
            if (!$key) { continue; }

            // File fields are handled separately, same as submit()
            if (strtolower($f['type'] ?? '') === 'file') {
                continue;
            }

            if (array_key_exists($key, $postedFields)) {
                $val = $postedFields[$key];
                if (is_array($val)) {
                    $val = array_values(array_filter($val, static function($v) {
                        return $v !== '' && $v !== null;
                    }));
                } elseif (!is_string($val)) {
                    $val = (string)$val;
                }
                $updated_fields_data[$key] = $val;
            } else {
                $updated_fields_data[$key] = '';
            }
        }

        // -------- Handle attachment (same pattern as submit()) --------
        $attachment_path = $old_attachment;

        if (!empty($_FILES['signoff_attachment']['name'])) {
            $upload_path_fs  = FCPATH . 'uploads/signoff_attachments/';
            $upload_path_rel = 'uploads/signoff_attachments/';

            if (!is_dir($upload_path_fs)) {
                @mkdir($upload_path_fs, 0775, true);
            }

            $config = [
                'upload_path'            => $upload_path_fs,
                'allowed_types'          => 'pdf|jpg|jpeg|png|doc|docx|xls|xlsx|txt',
                'max_size'               => 10240, // 10 MB
                'encrypt_name'           => true,
                'remove_spaces'          => true,
                'file_ext_tolower'       => true,
                'detect_mime'            => true,
                'overwrite'              => false,
                'max_filename_increment' => 100,
            ];
            $this->load->library('upload', $config);
            $this->upload->initialize($config, true);

            if (!$this->upload->do_upload('signoff_attachment')) {
                $error = $this->upload->display_errors('', '');
                set_alert('danger', 'Attachment upload failed: ' . $error);
                $ref = $this->input->server('HTTP_REFERER');
                redirect(!empty($ref) ? $ref : 'signoff');
            } else {
                $dataUpload   = $this->upload->data();
                $new_file_rel = $upload_path_rel . $dataUpload['file_name'];

                // Remove old file if replacing
                if (!empty($attachment_path)) {
                    $oldAbs = FCPATH . $attachment_path;
                    if (is_file($oldAbs)) { @unlink($oldAbs); }
                }
                $attachment_path = $new_file_rel;
            }
        }

        // ================== PERFORMANCE INDICATORS (same logic as submit) ==================
        $indicators = strtolower(trim((string)($this->S['indicators'] ?? 'none')));

        $totalPoints = null;
        $achieved    = null;

        // -------- Points (same dual-mode logic as submit()) --------
        if ($indicators === 'points' || $indicators === 'both') {
            $rowPts = $this->db->select('points_json')
                ->from('signoff_points')
                ->where('form_id', $form_id)
                ->where_in('team_id', [$team_id, 0])
                ->order_by('team_id', 'DESC')
                ->order_by('updated_at', 'DESC')
                ->limit(1)
                ->get()->row_array();

            $weights = [];
            if (!empty($rowPts['points_json'])) {
                $decoded = json_decode($rowPts['points_json'], true);
                if (is_array($decoded)) { $weights = $decoded; }
            }

            $fieldTypeLookup = [];
            foreach ($fields as $f) {
                $fn = $f['name'] ?? '';
                if ($fn !== '') { $fieldTypeLookup[$fn] = strtolower($f['type'] ?? 'text'); }
            }
            $numericTypes = ['number', 'amount'];

            $sumPoints        = 0.0;
            $anyWeightedField = false;

            foreach ($weights as $field => $weight) {
                if (!array_key_exists($field, $updated_fields_data)) { continue; }
                $anyWeightedField = true;
                $raw = $updated_fields_data[$field];
                $w   = is_numeric($weight) ? (float)$weight : 1.0;
                $fieldType = $fieldTypeLookup[$field] ?? 'text';

                if (in_array($fieldType, $numericTypes, true)) {
                    $sumPoints += ($this->to_number($raw) * $w);
                } else {
                    $isEmpty = ($raw === '' || $raw === null || $raw === [] ||
                                (is_array($raw) && count(array_filter($raw, static fn($x) => $x !== '' && $x !== null)) === 0));
                    if (!$isEmpty) { $sumPoints += $w; }
                }
            }
            $totalPoints = $anyWeightedField ? $sumPoints : null;
        }

        // -------- Targets (same null-safe logic as submit()) --------
        if ($indicators === 'targets' || $indicators === 'both') {
            $rowTgt = $this->db->select('targets_json')
                ->from('signoff_targets')
                ->where('form_id', $form_id)
                ->where('start_date <=', $sub_date)
                ->where('end_date >=',   $sub_date)
                ->where_in('team_id', [$team_id, 0])
                ->order_by('team_id', 'DESC')
                ->order_by('updated_at', 'DESC')
                ->limit(1)
                ->get()->row_array();

            $targets = [];
            if (!empty($rowTgt['targets_json'])) {
                $decoded = json_decode($rowTgt['targets_json'], true);
                if (is_array($decoded)) { $targets = $decoded; }
            }

            $sumAchieved      = 0.0;
            $anyTargetedField = false;

            foreach ($targets as $field => $targetValue) {
                if (!array_key_exists($field, $updated_fields_data)) { continue; }
                $anyTargetedField = true;
                $sumAchieved += $this->to_number($updated_fields_data[$field]);
            }
            $achieved = $anyTargetedField ? $sumAchieved : null;
        }
        // ================== END PERFORMANCE INDICATORS ==================

        // -------- Build payload for update --------
        $payload = [
            'fields_data'        => json_encode($updated_fields_data, JSON_UNESCAPED_UNICODE),
            'signoff_attachment' => $attachment_path,
            'updated_at'         => $this->tz_now(),
        ];

        if ($totalPoints !== null) {
            $payload['total_points'] = $totalPoints;
        }
        if ($achieved !== null) {
            $payload['achieved_targets'] = $achieved;
        }

        // -------- Detect changes (old vs new) for logging & notification --------
        $changes = [];

        // Per-field changes
        $form_fields = [];
        $tmp_ff = json_decode((string)($form['fields'] ?? '[]'), true);
        if (is_array($tmp_ff)) { $form_fields = $tmp_ff; }

        foreach ($form_fields as $f) {
            $k = $f['name']  ?? '';
            if (!$k) { continue; }
            $label = $f['label'] ?? $k;

            $old = $old_fields_data[$k] ?? '';
            $new = $updated_fields_data[$k] ?? '';

            if (is_array($old)) { $old = implode(', ', $old); }
            if (is_array($new)) { $new = implode(', ', $new); }

            $oldStr = (string)$old;
            $newStr = (string)$new;

            if ($oldStr !== $newStr) {
                $changes[$label] = [
                    'old' => $oldStr,
                    'new' => $newStr,
                ];
            }
        }

        // Attachment change
        if ($attachment_path !== $old_attachment) {
            $changes['Attachment'] = [
                'old' => $old_attachment ? basename($old_attachment) : 'None',
                'new' => $attachment_path ? basename($attachment_path) : 'None',
            ];
        }

        // Metrics changes
        if ($totalPoints !== null && $old_points !== null && $totalPoints != $old_points) {
            $changes['Total Points'] = [
                'old' => number_format($old_points, 2),
                'new' => number_format($totalPoints, 2),
            ];
        }
        if ($achieved !== null && $old_achieved !== null && $achieved != $old_achieved) {
            $changes['Achieved Targets'] = [
                'old' => number_format($old_achieved, 2),
                'new' => number_format($achieved, 2),
            ];
        }

        // If literally nothing changed, don't touch DB / notifications
        if (empty($changes)) {
            set_alert('info', 'No changes detected in this signoff submission.');
            $ref = $this->input->server('HTTP_REFERER');
            redirect(!empty($ref) ? $ref : 'signoff');
        }

        // -------- Persist update --------
        $ok = $this->Signoff_submissions_model->update_submission($submission_id, $payload);

        if ($ok) {
            // -------- Activity Log --------
            $this->load->model('Activity_log_model');
            $editor = $this->User_model->get_user_by_id($current_uid);
            $editorName = trim(
                ($editor['firstname'] ?? '') . ' ' . ($editor['lastname'] ?? '')
            );
            if ($editorName === '') {
                $editorName = 'User #' . $current_uid;
            }

            $this->Activity_log_model->add([
                'user_id'    => $current_uid,
                'action'     => 'Edited signoff submission #' . $submission_id .
                                ' for user #' . (int)$submission['user_id'] .
                                ' by ' . $editorName .
                                '. Changes: ' . json_encode($changes, JSON_UNESCAPED_UNICODE),
                'created_at' => $this->tz_now(),
            ]);

            // -------- User Notification with old vs new summary --------
            // Build a concise summary string
            $summaryParts = [];
            foreach ($changes as $label => $pair) {
                $summaryParts[] = $label . ': "' . $pair['old'] . '" → "' . $pair['new'] . '"';
            }
            $summaryText = implode('; ', $summaryParts);
            if (strlen($summaryText) > 400) {
                $summaryText = substr($summaryText, 0, 400) . '...';
            }

            notify_user(
                (int)$submission['user_id'],
                'signoff',
                'Signoff updated',
                'Your signoff submitted on ' .
                $this->tz_date('M j, Y', $submission['submission_date']) .
                ' was edited by ' . $editorName . '. Changes: ' . $summaryText
            );
        }

        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok'            => (bool)$ok,
                    'message'       => $ok ? 'Submission updated.' : 'Failed to update submission.',
                    'submission_id' => $submission_id,
                ]));
            return;
        }

        set_alert($ok ? 'success' : 'danger',
            $ok ? 'Signoff submission updated.' : 'Failed to update submission.'
        );

        $ref = $this->input->server('HTTP_REFERER');
        redirect(!empty($ref) ? $ref : 'signoff');
    }

/**
 * TEAMLEAD: View signoff submissions for my team only.
 * - Visible to role "teamlead" (or superadmin via override)
 * - Shows submissions for users whose emp_team = current user's emp_team
 * - Default filter: current month, with optional GET params:
 *   ?month=YYYY-MM &status=submitted|approved|rejected|excused
 *   &user_id=N &year=YYYY &page=N
 */
public function team_signoff()
{
    // ---------------------------------------------------------------
    // 1) Module gate
    // ---------------------------------------------------------------
    $this->assert_module_enabled_or_403();

    $current_uid = (int)$this->session->userdata('user_id');
    $user        = $this->User_model->get_user_by_id($current_uid);

    if (!$user) {
        redirect('authentication/login');
        return;
    }

    // ---------------------------------------------------------------
    // 2) Position exclusion gate (non-admins only)
    // ---------------------------------------------------------------
    if ($this->is_user_position_excluded($user) && !$this->is_current_user_admin()) {
        $this->forbidden();
    }

    // ---------------------------------------------------------------
    // 3) Role gate: teamlead and manager (superadmin/admin bypass via is_current_user_admin)
    // ---------------------------------------------------------------
    if (!$this->current_user_has_role(['teamlead', 'manager', 'director']) && !$this->is_current_user_admin()) {
        $this->forbidden();
    }

    // ---------------------------------------------------------------
    // 4) Resolve team
    // ---------------------------------------------------------------
    $team_id = (int)($user['emp_team'] ?? 0);
    if ($team_id <= 0) {
        set_alert('warning', 'You are not assigned to any team, so there is no team signoff to show.');
        redirect('dashboard');
        return;
    }

    $this->load->model('Teams_model');
    $team_name = (string)($this->Teams_model->get_team_name($team_id) ?? '');

    // ---------------------------------------------------------------
    // 5) Read and validate all GET filters
    // ---------------------------------------------------------------

    // Month (YYYY-MM)
    $month = trim((string)($this->input->get('month') ?: ''));
    if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $month)) {
        $month = $this->tz_date('Y-m');
    }

    // Status
    $status = strtolower(trim((string)$this->input->get('status')));
    if (!in_array($status, ['submitted', 'approved', 'rejected', 'excused'], true)) {
        $status = '';
    }

    // User ID filter (validated against member_ids later)
    $filter_user_id = max(0, (int)$this->input->get('user_id'));

    // Year filter (overrides month window when set)
    $filter_year = (int)$this->input->get('year');
    if ($filter_year < 2000 || $filter_year > ((int)$this->tz_date('Y') + 1)) {
        $filter_year = 0;
    }

    // Page number — read as plain GET param, not URI segment
    $per_page = 80; // TODO: restore to 100 after confirming pagination works
    $page_num = max(1, (int)($this->input->get('page') ?: 1));
    $offset   = ($page_num - 1) * $per_page;

    // Resolve date window (year overrides month)
    if ($filter_year > 0) {
        $start_date = $filter_year . '-01-01';
        $end_date   = $filter_year . '-12-31';
    } else {
        $start_date = $month . '-01';
        $end_date   = $this->tz_date('Y-m-t', strtotime($start_date));
    }

    // ---------------------------------------------------------------
    // 6) Fetch active team members
    // ---------------------------------------------------------------
    $team_members = $this->db
        ->select('id, fullname, emp_id, emp_title, emp_team, user_role')
        ->from('users')
        ->where('is_active', 1)
        ->where('emp_team', $team_id)
        ->get()
        ->result_array();

    // Build id => row map for the view
    $team_members_by_id = [];
    foreach ($team_members as $m) {
        $team_members_by_id[(int)$m['id']] = $m;
    }

    // Shared view_data — safe defaults used by both early-return and normal render
    $shared_view_data = [
        'title'           => 'My Team Signoff',
        'page_title'      => 'My Team Signoff',
        'team_id'         => $team_id,
        'team_name'       => $team_name,
        'month'           => $month,
        'status'          => $status,
        'table_id'        => 'teamsignoffTable',
        'perf_indicators' => $this->S['indicators'],
        'filter_user_id'  => $filter_user_id,
        'filter_year'     => $filter_year,
        'pagination'      => '',
        'total_rows'      => 0,
        'per_page'        => $per_page,
        'page'            => $page_num,
    ];

    if (empty($team_members)) {
        $this->load->view('layouts/master', [
            'subview'   => 'signoff/team_signoff',
            'view_data' => array_merge($shared_view_data, [
                'submissions'   => [],
                'team_members'  => [],
                'positions_map' => [],
            ]),
        ]);
        return;
    }

    $member_ids = array_map(static function ($m) {
        return (int)$m['id'];
    }, $team_members);

    // Validate filter_user_id belongs to this team (prevent cross-team data leak)
    if ($filter_user_id > 0 && !in_array($filter_user_id, $member_ids, true)) {
        $filter_user_id = 0;
    }

    // ---------------------------------------------------------------
    // 7) Count total rows for pagination (identical filters, no LIMIT)
    // ---------------------------------------------------------------
$this->db
    ->from('signoff_submissions AS ss')
    ->where_in('ss.user_id', $member_ids)
    ->where('ss.submission_date >=', $start_date)
    ->where('ss.submission_date <=', $end_date);

if ($cutoff = $this->retention_cutoff_date()) {
    $this->db->where('ss.submission_date >=', $cutoff);
}
    if ($status !== '') {
        $this->db->where('ss.status', $status);
    }
    if ($filter_user_id > 0) {
        $this->db->where('ss.user_id', $filter_user_id);
    }

    $total_rows = $this->db->count_all_results();

    // ---------------------------------------------------------------
    // 8) Fetch paginated submissions
    // ---------------------------------------------------------------
    $this->db
        ->select('ss.*, u.fullname, u.emp_id, u.emp_title, u.emp_team, f.title AS form_title, f.fields AS form_fields_json, f.team_id AS form_team_id')
        ->from('signoff_submissions AS ss')
        ->join('users AS u',         'u.id = ss.user_id', 'left')
        ->join('signoff_forms AS f', 'f.id = ss.form_id', 'left')
        ->where_in('ss.user_id', $member_ids)
->where('ss.submission_date >=', $start_date)
->where('ss.submission_date <=', $end_date);

if ($cutoff = $this->retention_cutoff_date()) {
    $this->db->where('ss.submission_date >=', $cutoff);
}

    if ($status !== '') {
        $this->db->where('ss.status', $status);
    }
    if ($filter_user_id > 0) {
        $this->db->where('ss.user_id', $filter_user_id);
    }

    $this->db
        ->order_by('ss.submission_date', 'DESC')
        ->order_by('u.fullname', 'ASC')
        ->limit($per_page, $offset);

    $submissions = $this->db->get()->result_array();


    // ---------------------------------------------------------------
    // 10) Build pagination HTML
    //
    //     IMPORTANT: We do NOT use CI's pagination library here because
    //     it mangles query-string URLs when use_page_numbers=true by
    //     inserting a leading slash (page=/2). Instead we build the
    //     links manually — this is simpler, fully Bootstrap 5 compatible,
    //     and gives us complete control over the URL structure.
    // ---------------------------------------------------------------
    $total_pages = $per_page > 0 ? (int)ceil($total_rows / $per_page) : 1;
    $total_pages = max(1, $total_pages);

    // Build the filter query string (without page) for pagination links
    $filter_params = array_filter([
        'month'   => $month,
        'status'  => $status,
        'user_id' => $filter_user_id > 0 ? (string)$filter_user_id : '',
        'year'    => $filter_year    > 0 ? (string)$filter_year    : '',
    ]);
    // page will be appended per-link below
    $filter_qs = http_build_query($filter_params);

    $pagination_html = '';

    if ($total_pages > 1) {
        $base = site_url('signoff/team_signoff');

        // Show at most 5 page number links centred around current page
        $link_radius = 2;
        $link_start  = max(1, $page_num - $link_radius);
        $link_end    = min($total_pages, $page_num + $link_radius);

        $mkurl = function (int $p) use ($base, $filter_qs): string {
            $qs = $filter_qs !== '' ? $filter_qs . '&page=' . $p : 'page=' . $p;
            return $base . '?' . $qs;
        };

        $html  = '<ul class="pagination pagination-sm mb-0">';

        // « First
        if ($page_num > 1) {
            $html .= '<li class="page-item">'
                   . '<a class="page-link" href="' . $mkurl(1) . '" title="First">&laquo;</a>'
                   . '</li>';
        }

        // ‹ Prev
        if ($page_num > 1) {
            $html .= '<li class="page-item">'
                   . '<a class="page-link" href="' . $mkurl($page_num - 1) . '">&lsaquo; Prev</a>'
                   . '</li>';
        }

        // Ellipsis before
        if ($link_start > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">&hellip;</a></li>';
        }

        // Numbered links
        for ($p = $link_start; $p <= $link_end; $p++) {
            if ($p === $page_num) {
                $html .= '<li class="page-item active">'
                       . '<a class="page-link" href="#">' . $p . '</a>'
                       . '</li>';
            } else {
                $html .= '<li class="page-item">'
                       . '<a class="page-link" href="' . $mkurl($p) . '">' . $p . '</a>'
                       . '</li>';
            }
        }

        // Ellipsis after
        if ($link_end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">&hellip;</a></li>';
        }

        // Next ›
        if ($page_num < $total_pages) {
            $html .= '<li class="page-item">'
                   . '<a class="page-link" href="' . $mkurl($page_num + 1) . '">Next &rsaquo;</a>'
                   . '</li>';
        }

        // Last »
        if ($page_num < $total_pages) {
            $html .= '<li class="page-item">'
                   . '<a class="page-link" href="' . $mkurl($total_pages) . '" title="Last">&raquo;</a>'
                   . '</li>';
        }

        $html .= '</ul>';

        $pagination_html = $html;
    }

    // ---------------------------------------------------------------
    // 11) Positions map (id => title) for designation column
    // ---------------------------------------------------------------
    $positions_map = [];
    foreach ($this->Hrm_positions_model->get_all_positions() as $p) {
        $positions_map[(int)$p['id']] = $p['title'];
    }

    // ---------------------------------------------------------------
    // 12) Render view
    // ---------------------------------------------------------------
    $this->load->view('layouts/master', [
        'subview'   => 'signoff/team_signoff',
        'view_data' => array_merge($shared_view_data, [
            'submissions'    => $submissions,
            'team_members'   => $team_members_by_id,
            'positions_map'  => $positions_map,
            'filter_user_id' => $filter_user_id,
            'filter_year'    => $filter_year,
            'pagination'     => $pagination_html,
            'total_rows'     => $total_rows,
            'total_pages'    => $total_pages,
            'per_page'       => $per_page,
            'page'           => $page_num,
        ]),
    ]);
}


}