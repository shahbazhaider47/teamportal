<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Points_model extends CI_Model
{
    protected $table = 'signoff_points'; // columns: id, team_id (int; 0 = global), form_id, points_json, created_*, updated_*

    /**
     * Return rows for both team-specific and global forms (no is_active filter).
     * Each row: team_id, team_name, form_id, form_title, form_fields, points_json, points_list[]
     */
    public function get_points()
    {
        // Only rows that HAVE points
        $rows = $this->db->select('
                p.id                AS points_id,
                p.team_id           AS team_id,
                p.form_id           AS form_id,
                p.points_json       AS points_json,
                p.created_at,
                p.updated_at,
                f.title             AS form_title,
                f.fields            AS form_fields,
                f.team_id           AS form_team_id,
                f.position_id       AS position_id,
                tm.name             AS team_name,
                pos.title           AS position_title
            ')
            ->from($this->table.' p')
            ->join('signoff_forms f', 'f.id = p.form_id', 'inner')
            ->join('teams tm', 'tm.id = p.team_id', 'left')                 // p.team_id=0 => Global (no team match)
            ->join('hrm_positions pos', 'pos.id = f.position_id', 'left')   // position scope lives on form
            ->order_by('f.title', 'ASC')
            ->get()->result_array();
    
        // Build points_list (labels) + metrics_count
        foreach ($rows as &$row) {
            $fields_meta = json_decode((string)$row['form_fields'], true) ?: [];
            $points      = is_array($row['points_json']) ? $row['points_json'] : @json_decode((string)$row['points_json'], true);
            if (!is_array($points)) { $points = []; }
    
            // Map field name -> label from form fields
            $labels = [];
            foreach ($fields_meta as $fm) {
                $name  = (string)($fm['name'] ?? '');
                if ($name === '') continue;
                $label = (string)($fm['label'] ?? $name);
                $labels[$name] = $label;
            }
    
            $list = [];
            foreach ($points as $field => $weight) {
                $list[] = [
                    'field'       => (string)$field,
                    'field_label' => $labels[$field] ?? (string)$field,
                    'points'      => (float)$weight,
                ];
            }
    
            $row['points_list']   = $list;
            $row['metrics_count'] = count($points); // Total Metrics = how many fields have assigned points
        }
        unset($row);
    
        return $rows;
    }

    /**
     * (Kept for compatibility)
     */
    public function get_active_form_for_team($team_id)
    {
        return $this->db->get_where('signoff_forms', [
            'team_id'   => (int)$team_id,
            'is_active' => 1
        ])->row_array();
    }

    /**
     * Insert or update a JSON row for (team_id, form_id).
     * Use team_id=0 to represent "Global".
     *
     * $data = [
     *   'team_id' => int (0=global),
     *   'form_id' => int,
     *   'points_json' => array field=>points,
     *   'created_by'=>int, 'created_at'=>datetime,
     *   'updated_by'=>int?, 'updated_at'=>datetime?
     * ]
     */
    public function insert_or_update_json(array $data)
    {
        $exists = $this->db->get_where($this->table, [
            'team_id' => (int)$data['team_id'], // 0 = global
            'form_id' => (int)$data['form_id'],
        ])->row_array();

        $payload = [
            'points_json' => json_encode($data['points_json']),
            'created_by'  => $data['created_by'] ?? null,
            'created_at'  => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        if ($exists) {
            $payload['updated_by'] = $data['updated_by'] ?? ($data['created_by'] ?? null);
            $payload['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
            $this->db->where('id', (int)$exists['id'])->update($this->table, $payload);
            return (int)$exists['id'];
        } else {
            $insert = [
                'team_id' => (int)$data['team_id'],
                'form_id' => (int)$data['form_id'],
            ] + $payload;
            $this->db->insert($this->table, $insert);
            return (int)$this->db->insert_id();
        }
    }



    /**
     * Build a year-month range as DATETIME strings.
     * Returns ['start' => 'YYYY-mm-01 00:00:00', 'end' => 'YYYY-mm-last 23:59:59']
     */
    protected function month_range(string $yyyy_mm): array
    {
        if (!preg_match('/^\d{4}\-\d{2}$/', $yyyy_mm)) {
            $yyyy_mm = date('Y-m');
        }
        $start = $yyyy_mm . '-01 00:00:00';
        $end   = date('Y-m-d 23:59:59', strtotime($yyyy_mm . '-01 +1 month -1 day'));
        return ['start' => $start, 'end' => $end];
    }

    /**
     * Provide last N month options for a filter dropdown.
     */
    public function month_options(int $n = 12): array
    {
        $opts = [];
        for ($i = 0; $i < max(1, $n); $i++) {
            $ts = strtotime(date('Y-m-01') . " -$i months");
            $opts[] = [
                'value' => date('Y-m', $ts),
                'label' => date('F Y', $ts),
            ];
        }
        return $opts;
    }

    /**
     * Fetch the latest points rule for a given form & team.
     * Priority: exact team_id → team_id IS NULL (global) → team_id=0 (global alternative).
     * Returns array(field => weight).
     */
    protected function points_rule_for(int $form_id, ?int $team_id): array
    {
        // Exact team_id first (if provided)
        if ($team_id !== null) {
            $row = $this->db->select('points_json')
                ->from('signoff_points')
                ->where(['form_id' => $form_id, 'team_id' => $team_id])
                ->order_by('updated_at', 'DESC')
                ->limit(1)->get()->row_array();
            if ($row && !empty($row['points_json'])) {
                $pts = is_array($row['points_json']) ? $row['points_json'] : @json_decode($row['points_json'], true);
                if (is_array($pts)) return $pts;
            }
        }

        // Global by NULL
        $rowNull = $this->db->select('points_json')
            ->from('signoff_points')
            ->where('form_id', $form_id)
            ->where('team_id IS NULL', null, false)
            ->order_by('updated_at', 'DESC')
            ->limit(1)->get()->row_array();
        if ($rowNull && !empty($rowNull['points_json'])) {
            $pts = is_array($rowNull['points_json']) ? $rowNull['points_json'] : @json_decode($rowNull['points_json'], true);
            if (is_array($pts)) return $pts;
        }

        // Global by 0 (if you ever stored it that way)
        $rowZero = $this->db->select('points_json')
            ->from('signoff_points')
            ->where(['form_id' => $form_id, 'team_id' => 0])
            ->order_by('updated_at', 'DESC')
            ->limit(1)->get()->row_array();
        if ($rowZero && !empty($rowZero['points_json'])) {
            $pts = is_array($rowZero['points_json']) ? $rowZero['points_json'] : @json_decode($rowZero['points_json'], true);
            if (is_array($pts)) return $pts;
        }

        return [];
    }

    /**
     * Compute points for a single submission row using:
     * - signoff_submissions.total_points when present, otherwise
     * - sum(fields_data[field] * points_json[field]) with boolean coercion when needed.
     *
     * $sub = ['form_id','team_id','fields_data','total_points']
     */
    protected function compute_submission_points(array $sub): float
    {
        if (isset($sub['total_points']) && $sub['total_points'] !== null && $sub['total_points'] !== '' && is_numeric($sub['total_points'])) {
            return (float)$sub['total_points'];
        }

        $answers = [];
        if (!empty($sub['fields_data'])) {
            $answers = is_array($sub['fields_data']) ? $sub['fields_data'] : @json_decode($sub['fields_data'], true);
            if (!is_array($answers)) $answers = [];
        }

        $formId = (int)$sub['form_id'];
        // Prefer the submission's team_id for rule resolution
        $teamId = array_key_exists('team_id', $sub) ? ($sub['team_id'] === null ? null : (int)$sub['team_id']) : null;

        $rule = $this->points_rule_for($formId, $teamId);
        if (!$rule) return 0.0;

        $calc = 0.0;
        foreach ($rule as $field => $weight) {
            $w = (float)$weight;
            if ($w == 0.0) continue;

            $val = $answers[$field] ?? 0;
            if (is_numeric($val)) {
                $calc += ((float)$val) * $w;
            } else {
                $booly = in_array(strtolower((string)$val), ['1','true','yes','on'], true) ? 1.0 : 0.0;
                $calc += $booly * $w;
            }
        }
        return $calc;
    }

    /**
     * Core report for "My Points".
     * - $user_id: required
     * - $month_ym: 'YYYY-MM' (defaults to current month)
     * - $form_id: optional filter (0=all)
     *
     * Returns:
     * [
     *   'rows' => [ {id, form_id, form_title, points, created_at, submission_date}, ... ],
     *   'total_points' => float,
     *   'total_submissions' => int,
     *   'forms_for_user' => [ {id, title}, ... ],
     *   'month_start' => string, 'month_end' => string
     * ]
     */
    public function my_points_report(int $user_id, ?string $month_ym = null, int $form_id = 0): array
    {
        $month_ym = $month_ym ?: date('Y-m');
        $range    = $this->month_range($month_ym);

        // Forms for dropdown (first try chosen month)
        $forms = $this->db->select('f.id, f.title')
            ->from('signoff_submissions s')
            ->join('signoff_forms f', 'f.id = s.form_id', 'inner')
            ->where('s.user_id', $user_id)
            ->where('s.created_at >=', $range['start'])
            ->where('s.created_at <=', $range['end'])
            ->group_by('f.id, f.title')
            ->order_by('f.title', 'ASC')
            ->get()->result_array();

        if (!$forms) {
            // Fallback: any forms ever submitted by the user
            $forms = $this->db->select('f.id, f.title')
                ->from('signoff_submissions s')
                ->join('signoff_forms f', 'f.id = s.form_id', 'inner')
                ->where('s.user_id', $user_id)
                ->group_by('f.id, f.title')
                ->order_by('f.title', 'ASC')
                ->get()->result_array();
        }

        // Submissions for period
        $this->db->select('s.id, s.form_id, s.team_id, s.fields_data, s.total_points, s.submission_date, s.created_at, f.title AS form_title');
        $this->db->from('signoff_submissions s');
        $this->db->join('signoff_forms f', 'f.id = s.form_id', 'left');
        $this->db->where('s.user_id', $user_id);
        $this->db->where('s.created_at >=', $range['start']);
        $this->db->where('s.created_at <=', $range['end']);
        if ($form_id > 0) {
            $this->db->where('s.form_id', $form_id);
        }
        $this->db->order_by('s.created_at', 'DESC');
        $subs = $this->db->get()->result_array();

        $rows  = [];
        $sum   = 0.0;
        $count = 0;

        foreach ($subs as $s) {
            $count++;
            $pts = $this->compute_submission_points($s);
            $sum += $pts;

            $rows[] = [
                'id'              => (int)$s['id'],
                'form_id'         => (int)$s['form_id'],
                'form_title'      => (string)($s['form_title'] ?? 'Form #'.$s['form_id']),
                'points'          => $pts,
                'submission_date' => (string)$s['submission_date'],
                'created_at'      => (string)$s['created_at'],
            ];
        }

        return [
            'rows'               => $rows,
            'total_points'       => $sum,
            'total_submissions'  => $count,
            'forms_for_user'     => $forms,
            'month_start'        => $range['start'],
            'month_end'          => $range['end'],
        ];
    }
    
}