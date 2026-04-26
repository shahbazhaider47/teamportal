<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Signoff_submissions_model extends CI_Model
{
    protected $table = 'signoff_submissions';

    /**
     * Get a submission for a given form, user, and (optional) date
     * @param int $form_id
     * @param int $user_id
     * @param string|null $date (Y-m-d)
     * @return array|null
     */
    public function get_by_form_and_user($form_id, $user_id, $date = null)
    {
        $this->db->where('form_id', (int)$form_id);
        $this->db->where('user_id', (int)$user_id);
        if (!empty($date)) {
            $this->db->where('submission_date', $date);
        }
        return $this->db->order_by('id', 'DESC')->get($this->table)->row_array();
    }

    /**
     * Get all submissions for a form on a specific date (with user/team meta)
     * @param int $form_id
     * @param string $date (Y-m-d)
     * @return array
     */
    public function get_by_form($form_id, $date)
    {
        $this->db->select('s.*, u.firstname, u.lastname, t.name as team_name, profile_image')
            ->from('signoff_submissions s')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('teams t', 't.id = s.team_id', 'left')
            ->where('s.form_id', (int)$form_id)
            ->where('s.submission_date', $date);

        $results = $this->db->get()->result_array();

        // Attach user_name for convenience
        foreach ($results as &$row) {
            $row['user_name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        }
        unset($row);

        return $results;
    }

    /**
     * Insert a new signoff submission
     * @param array $data
     * @return int Insert ID
     */
    public function insert_submission($data)
    {
        $data = $this->normalize_submission_data($data);
        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    /**
     * Update a submission by ID
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_submission($id, $data)
    {
        $data = $this->normalize_submission_data($data);
        $this->db->where('id', (int)$id)->update($this->table, $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get a user's submission history (with form meta)
     * @param int $user_id
     * @return array
     */
    public function get_user_history($user_id)
    {
        $this->db->select('ss.*, sf.title as form_title, sf.fields as form_fields, t.name as team_name');
        $this->db->from('signoff_submissions ss');
        $this->db->join('signoff_forms sf', 'sf.id = ss.form_id', 'left');
        $this->db->join('teams t', 't.id = ss.team_id', 'left');
        $this->db->where('ss.user_id', (int)$user_id);
        $this->db->order_by('ss.submission_date', 'desc');
        return $this->db->get()->result_array();
    }

    /**
     * Get a single submission by its ID
     * @param int $submission_id
     * @return array|null
     */
    public function get_submission($submission_id)
    {
        return $this->db
            ->get_where($this->table, ['id' => (int)$submission_id])
            ->row_array();
    }

    /**
     * Check if a user has any submission on a date
     * @param int $user_id
     * @param string $date (Y-m-d)
     * @return bool
     */
    public function has_submission($user_id, $date)
    {
        $this->db->where('user_id', (int)$user_id);
        $this->db->where('submission_date', $date);
        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Basic compliance metrics (attendance-free):
     * signed_off = days with a submission,
     * missed     = working days without a submission,
     * excused    = 0 (not tracked without attendance),
     * compliance_rate = signed_off / total.
     * @param int $user_id
     * @param string $from (Y-m-d)
     * @param string $to   (Y-m-d)
     * @return array
     */
    public function get_compliance_metrics($user_id, $from, $to)
    {
        $dates      = get_working_days($from, $to); // Helper: array of Y-m-d excluding weekends
        $signed_off = 0;
        $missed     = 0;

        foreach ($dates as $date) {
            if ($this->has_submission($user_id, $date)) {
                $signed_off++;
            } else {
                $missed++;
            }
        }

        $total = count($dates);
        return [
            'signed_off'      => $signed_off,
            'excused'         => 0, // attendance removed
            'missed'          => $missed,
            'total'           => $total,
            'compliance_rate' => $total > 0 ? round(($signed_off) / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * List all submissions with joined meta for admin/manager views
     * @return array
     */
    public function get_all_submissions_with_meta()
    {
        $this->db->select('ss.*, sf.title as form_title, u.firstname, u.lastname, t.name as team_name');
        $this->db->from('signoff_submissions ss');
        $this->db->join('signoff_forms sf', 'ss.form_id = sf.id', 'left');
        $this->db->join('users u', 'ss.user_id = u.id', 'left');
        $this->db->join('teams t', 'ss.team_id = t.id', 'left');
        $this->db->order_by('ss.submission_date', 'DESC');
        return $this->db->get()->result_array();
    }

    /**
     * Paginated + filtered submissions for admin/manager manage view.
     *
     * Filters (all optional):
     *   from_date   YYYY-MM-DD
     *   to_date     YYYY-MM-DD
     *   month       YYYY-MM   (overridden by from/to if both set)
     *   year        YYYY      (overridden by from/to if both set)
     *   user_id     int
     *   team_id     int
     *   form_id     int
     *   status      submitted|approved|rejected|excused
     *
     * @param  array  $filters
     * @param  int    $limit
     * @param  int    $offset
     * @return array
     */
    public function get_paginated_submissions(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $this->_apply_manage_filters($filters);
        $this->db
            ->select('ss.*, sf.title AS form_title, sf.fields AS form_fields_json, sf.team_id AS form_team_id, u.firstname, u.lastname, u.emp_id, t.name AS team_name')
            ->order_by('ss.submission_date', 'DESC')
            ->order_by('ss.id', 'DESC')
            ->limit($limit, $offset);

        $results = $this->db->get()->result_array();

        foreach ($results as &$row) {
            $row['user_name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        }
        unset($row);

        return $results;
    }

    /**
     * Count total rows matching the same filters as get_paginated_submissions().
     *
     * @param  array $filters
     * @return int
     */
    public function count_paginated_submissions(array $filters = []): int
    {
        $this->_apply_manage_filters($filters);
        return (int) $this->db->count_all_results();
    }

    /**
     * Internal: build the shared WHERE clauses for the manage/paginated queries.
     */
    private function _apply_manage_filters(array $filters): void
    {
        $this->db
            ->from('signoff_submissions ss')
            ->join('signoff_forms sf', 'sf.id = ss.form_id', 'left')
            ->join('users u',          'u.id  = ss.user_id', 'left')
            ->join('teams t',          't.id  = ss.team_id', 'left');

        // --- Date range ---
        $from = trim((string)($filters['from_date'] ?? ''));
        $to   = trim((string)($filters['to_date']   ?? ''));

        if ($from === '' && $to === '') {
            // Try month (YYYY-MM)
            $month = trim((string)($filters['month'] ?? ''));
            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                $from = $month . '-01';
                $to   = date('Y-m-t', strtotime($from));
            } else {
                // Try year (YYYY)
                $year = (int)($filters['year'] ?? 0);
                if ($year >= 2000 && $year <= ((int)date('Y') + 1)) {
                    $from = $year . '-01-01';
                    $to   = $year . '-12-31';
                }
            }
        }

        if ($from !== '') { $this->db->where('ss.submission_date >=', $from); }
        if ($to   !== '') { $this->db->where('ss.submission_date <=', $to);   }

        // --- Retention cutoff (passed in as filter) ---
        $cutoff = trim((string)($filters['retention_cutoff'] ?? ''));
        if ($cutoff !== '') {
            $this->db->where('ss.submission_date >=', $cutoff);
        }

        // --- Optional scalar filters ---
        $status  = strtolower(trim((string)($filters['status']  ?? '')));
        $user_id = (int)($filters['user_id'] ?? 0);
        $team_id = (int)($filters['team_id'] ?? 0);
        $form_id = (int)($filters['form_id'] ?? 0);

        $valid_statuses = ['submitted', 'approved', 'rejected', 'excused'];
        if ($status !== '' && in_array($status, $valid_statuses, true)) {
            $this->db->where('ss.status', $status);
        }
        if ($user_id > 0) { $this->db->where('ss.user_id', $user_id); }
        if ($team_id > 0) { $this->db->where('ss.team_id', $team_id); }
        if ($form_id > 0) { $this->db->where('ss.form_id', $form_id); }
    }

    /**
     * Filtered submissions for a form over a date range, optional status/user filters
     * @param int $form_id
     * @param string $start_date
     * @param string $end_date
     * @param string $status
     * @param string|int $user_id   hrm_positions
     * @return array
     */
    public function get_filtered_submissions($form_id, $start_date, $end_date, $status = '', $user_id = '')
    {
        $this->db->select('
                s.*,
                u.firstname,
                u.lastname,
                u.emp_id,
                u.emp_title AS position_id,
                p.title     AS position_title,
                t.name      AS team_name
            ')
            ->from('signoff_submissions s')
            ->join('users u',         'u.id = s.user_id',    'left')
            ->join('hrm_positions p', 'p.id = u.emp_title',  'left')   // <-- NEW: join for position title
            ->join('teams t',         't.id = s.team_id',    'left')
            ->where('s.form_id', (int)$form_id)
            ->where('s.submission_date >=', $start_date)
            ->where('s.submission_date <=', $end_date);
    
        if (!empty($status)) {
            $this->db->where('s.status', strtolower(trim($status)));
        }
        if (!empty($user_id)) {
            $this->db->where('s.user_id', (int)$user_id);
        }
    
        $this->db->order_by('s.submission_date', 'DESC');
        $results = $this->db->get()->result_array();
    
        foreach ($results as &$row) {
            // Friendly user name
            $row['user_name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
    
            // Optional: pretty EMP ID if your helper exists; keeps raw emp_id intact
            if (!empty($row['emp_id'])) {
                $row['emp_id_display'] = function_exists('emp_id_display') ? emp_id_display($row['emp_id']) : $row['emp_id'];
            } else {
                $row['emp_id_display'] = '—';
            }
    
            // Optional: fallback when position has been deleted or unset
            if (empty($row['position_title']) && !empty($row['position_id'])) {
                $row['position_title'] = '#'.$row['position_id']; // visible anomaly cue
            }
        }
        unset($row);
    
        return $results;
    }


    /**
     * Count all signoff submissions (any status)
     * @return int
     */
    public function count_all()
    {
        return $this->db->from($this->table)->count_all_results();
    }

    /**
     * Count signoff submissions filtered by status (lowercase normalized)
     * @param string $status
     * @return int
     */
    public function count_by_status($status)
    {
        $status = strtolower(trim($status));
        return $this->db
            ->from($this->table)
            ->where('status', $status)
            ->count_all_results();
    }

    /**
     * Count valid submissions (excluding any non-standard statuses)
     * @return int
     */
    public function count_valid_submissions()
    {
        return $this->db
            ->from($this->table)
            ->where_in('status', ['submitted', 'approved', 'rejected'])
            ->count_all_results();
    }

    /**
     * Count user submissions within an optional date range and optional status
     * @param int $user_id
     * @param string|null $status
     * @param string|null $start_date
     * @param string|null $end_date
     * @return int
     */
    public function count_user_submissions($user_id, $status = null, $start_date = null, $end_date = null)
    {
        $this->db->from($this->table)->where('user_id', (int)$user_id);

        if (!empty($status)) {
            $this->db->where('status', strtolower(trim($status)));
        }
        if (!empty($start_date)) {
            $this->db->where('submission_date >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('submission_date <=', $end_date);
        }

        return $this->db->count_all_results();
    }

    /**
     * Summarize "payment_posted" from fields_data for current vs previous month (approved only)
     * @param int $user_id
     * @return array{current: float, previous: float, difference: float}
     */
    public function get_payment_posted_summary($user_id)
    {
        $current_month_start  = date('Y-m-01');
        $current_month_end    = date('Y-m-t');
        $previous_month_start = date('Y-m-01', strtotime('-1 month'));
        $previous_month_end   = date('Y-m-t', strtotime('-1 month'));

        $sum = function ($start, $end) use ($user_id) {
            $results = $this->db->select('fields_data')
                ->from($this->table)
                ->where('user_id', (int)$user_id)
                ->where('status', 'approved')
                ->where('submission_date >=', $start)
                ->where('submission_date <=', $end)
                ->get()->result_array();

            $total = 0.0;
            foreach ($results as $row) {
                $data = json_decode($row['fields_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) { continue; }
                $total += isset($data['payment_posted']) ? (float)$data['payment_posted'] : 0.0;
            }
            return $total;
        };

        $sum_current  = $sum($current_month_start, $current_month_end);
        $sum_previous = $sum($previous_month_start, $previous_month_end);

        return [
            'current'    => $sum_current,
            'previous'   => $sum_previous,
            'difference' => $sum_current - $sum_previous,
        ];
    }

    /**
     * Summarize "claims_submitted" from fields_data for current vs previous month (approved only)
     * @param int $user_id
     * @return array{current: float, previous: float, difference: float}
     */
    public function get_claims_submitted_summary($user_id)
    {
        $current_month_start  = date('Y-m-01');
        $current_month_end    = date('Y-m-t');
        $previous_month_start = date('Y-m-01', strtotime('-1 month'));
        $previous_month_end   = date('Y-m-t', strtotime('-1 month'));

        $sum_field = function ($start, $end, $field) use ($user_id) {
            $results = $this->db->select('fields_data')
                ->from($this->table)
                ->where('user_id', (int)$user_id)
                ->where('status', 'approved')
                ->where('submission_date >=', $start)
                ->where('submission_date <=', $end)
                ->get()->result_array();

            $total = 0.0;
            foreach ($results as $row) {
                $data = json_decode($row['fields_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) { continue; }
                if (isset($data[$field])) {
                    $total += (float)$data[$field];
                }
            }
            return $total;
        };

        $sum_current  = $sum_field($current_month_start,  $current_month_end,  'claims_submitted');
        $sum_previous = $sum_field($previous_month_start, $previous_month_end, 'claims_submitted');

        return [
            'current'    => $sum_current,
            'previous'   => $sum_previous,
            'difference' => $sum_current - $sum_previous,
        ];
    }

    /**
     * Ensure fields_data is a non-null JSON string and normalize status to lowercase
     * @param array $data
     * @return array
     */
    private function normalize_submission_data(array $data): array
    {
        // Guarantee a non-null JSON string for fields_data
        if (!isset($data['fields_data']) || $data['fields_data'] === null || $data['fields_data'] === '') {
            $data['fields_data'] = json_encode(new stdClass()); // "{}"
        } elseif (is_array($data['fields_data'])) {
            $data['fields_data'] = json_encode($data['fields_data'], JSON_UNESCAPED_UNICODE);
        } elseif (!is_string($data['fields_data'])) {
            // Fallback for scalars/objects
            $data['fields_data'] = json_encode($data['fields_data'], JSON_UNESCAPED_UNICODE);
        }

        // Normalize status to lowercase if present
        if (isset($data['status']) && is_string($data['status'])) {
            $s = strtolower(trim($data['status']));
            $data['status'] = in_array($s, ['submitted', 'approved', 'rejected', 'excused'], true)
                ? $s
                : 'submitted';
        }

        return $data;
    }


    /**
     * Return [form_id => total_count] for ALL time.
     * Uses GROUP BY for a single pass over the table.
     */
    public function counts_all_time(): array
    {
        $rows = $this->db
            ->select('form_id, COUNT(*) AS total', false)
            ->from($this->table)
            ->group_by('form_id')
            ->get()->result_array();

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['form_id']] = (int)$r['total'];
        }
        return $out;
    }

    /**
     * Return [form_id => total_count] since $start (inclusive) until $end (exclusive).
     * $start/$end must be 'Y-m-d H:i:s'.
     * Change $dateCol to your actual timestamp column if different.
     */
    public function counts_between(string $start, string $end): array
    {
        $dateCol = 'created_at'; // TODO: change to 'submitted_at' if that’s your column

        $rows = $this->db
            ->select('form_id, COUNT(*) AS total', false)
            ->from($this->table)
            ->where("$dateCol >=", $start)
            ->where("$dateCol <",  $end)
            ->group_by('form_id')
            ->get()->result_array();

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['form_id']] = (int)$r['total'];
        }
        return $out;
    }    



    // NEW: Monthly performance summary for points/targets widget
    public function get_perf_summary(int $user_id, string $indicator): array
    {
        $user_id   = (int) $user_id;
        $indicator = strtolower(trim($indicator));
    
        // Default empty structure
        $empty = [
            'current'    => 0.0,
            'previous'   => 0.0,
            'difference' => 0.0,
            // Only used for targets mode:
            'assigned'   => 0.0,
            'achieved'   => 0.0,
            'completion' => 0.0,
        ];
    
        if ($user_id <= 0 || !in_array($indicator, ['points', 'targets'], true)) {
            return $empty;
        }
    
        $monthStart     = date('Y-m-01');
        $monthEnd       = date('Y-m-t');
        $prevMonthStart = date('Y-m-01', strtotime('-1 month'));
        $prevMonthEnd   = date('Y-m-t', strtotime('-1 month'));
    
        // We usually do not want rejected signoffs in performance metrics
        $statusAllowed = ['submitted', 'approved', 'excused'];
    
        /* ============================================================
         * MODE 1: POINTS  (uses signoff_submissions.total_points)
         * ============================================================ */
        if ($indicator === 'points') {
            $col = 'total_points';
    
            // Current month
            $this->db->select_sum($col, 'sum_val');
            $this->db->from('signoff_submissions');
            $this->db->where('user_id', $user_id);
            $this->db->where('submission_date >=', $monthStart);
            $this->db->where('submission_date <=', $monthEnd);
            $this->db->where_in('status', $statusAllowed);
            $rowCurr  = $this->db->get()->row_array();
            $current  = (float) ($rowCurr['sum_val'] ?? 0);
    
            // Previous month
            $this->db->select_sum($col, 'sum_val');
            $this->db->from('signoff_submissions');
            $this->db->where('user_id', $user_id);
            $this->db->where('submission_date >=', $prevMonthStart);
            $this->db->where('submission_date <=', $prevMonthEnd);
            $this->db->where_in('status', $statusAllowed);
            $rowPrev  = $this->db->get()->row_array();
            $previous = (float) ($rowPrev['sum_val'] ?? 0);
    
            return [
                'current'    => $current,
                'previous'   => $previous,
                'difference' => $current - $previous,
                'assigned'   => 0.0,
                'achieved'   => 0.0,
                'completion' => 0.0,
            ];
        }
    
        /* ============================================================
         * MODE 2: TARGETS
         * - assigned: sum of targets_json values in scopes that apply to the user this month
         * - achieved: sum of achieved_targets for this user this month
         * ============================================================ */
    
        // 1) Achieved (from signoff_submissions.achieved_targets)
        $this->db->select_sum('achieved_targets', 'sum_val');
        $this->db->from('signoff_submissions');
        $this->db->where('user_id', $user_id);
        $this->db->where('submission_date >=', $monthStart);
        $this->db->where('submission_date <=', $monthEnd);
        $this->db->where_in('status', $statusAllowed);
        $rowAch   = $this->db->get()->row_array();
        $achieved = (float) ($rowAch['sum_val'] ?? 0);
    
        // 2) Assigned (team/global scopes from signoff_targets)
        $CI = get_instance();
        $CI->load->model('signoff/Targets_model');
        $CI->load->model('User_model');
    
        $user    = $CI->User_model->get_user_by_id($user_id);
        $team_id = (int) ($user['emp_team'] ?? 0);
    
        // Global scopes for this month
        $scopes_global = $CI->Targets_model->get_scoped_targets([
            'team_id'    => 0,
            'start_date' => $monthStart,
            'end_date'   => $monthEnd,
        ]);
    
        // Team-specific scopes for this month
        $scopes_team = $team_id > 0 ? $CI->Targets_model->get_scoped_targets([
            'team_id'    => $team_id,
            'start_date' => $monthStart,
            'end_date'   => $monthEnd,
        ]) : [];
    
        $scopes   = array_merge((array) $scopes_global, (array) $scopes_team);
        $assigned = 0.0;
    
        foreach ($scopes as $scope) {
            $targetsJson = $scope['targets_json'] ?? [];
            if (is_string($targetsJson) && $targetsJson !== '') {
                $tmp = json_decode($targetsJson, true);
                if (is_array($tmp)) {
                    $targetsJson = $tmp;
                }
            }
            if (!is_array($targetsJson)) {
                continue;
            }
    
            foreach ($targetsJson as $field => $val) {
                if (is_numeric($val)) {
                    $assigned += (float) $val;
                }
            }
        }
    
        $completion = $assigned > 0 ? ($achieved / $assigned) * 100 : 0.0;
    
        return [
            // For targets mode, we use "current" as "achieved this month"
            'current'    => $achieved,
            'previous'   => 0.0,
            'difference' => 0.0,
            'assigned'   => $assigned,
            'achieved'   => $achieved,
            'completion' => $completion,
        ];
    }


    
}