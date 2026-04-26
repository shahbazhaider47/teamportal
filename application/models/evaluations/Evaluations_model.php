<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Evaluations_model extends CI_Model
{
    const T_TEMPLATES  = 'eval_templates';
    const T_SECTIONS   = 'eval_sections';
    const T_CRITERIA   = 'eval_criteria';
    const T_EVALS      = 'evaluations';
    const T_RESPONSES  = 'eval_responses';
    const T_GOALS      = 'eval_goals';

    /* =========================================================
     * TEMPLATES
     * ======================================================= */

    public function get_template(int $id): ?array
    {
        $row = $this->db
            ->select(self::T_TEMPLATES . '.*,
                      tm.id   AS team_id,
                      tm.name AS team_name,
                      d.id    AS department_id,
                      d.name  AS department_name,
                      CONCAT(tl.firstname, " ", tl.lastname) AS teamlead_name,
                      CONCAT(mg.firstname, " ", mg.lastname) AS manager_name')
            ->from(self::T_TEMPLATES)
            ->join('teams AS tm',      'tm.id = ' . self::T_TEMPLATES . '.team_id', 'left')
            ->join('departments AS d', 'd.id = tm.department_id',                   'left')
            ->join('users AS tl',      'tl.id = tm.teamlead_id',                    'left')
            ->join('users AS mg',      'mg.id = tm.manager_id',                     'left')
            ->where(self::T_TEMPLATES . '.id', $id)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    public function get_active_templates(): array
    {
        return $this->db
            ->select(self::T_TEMPLATES . '.*,
                      tm.id   AS team_id,
                      tm.name AS team_name,
                      d.id    AS department_id,
                      d.name  AS department_name')
            ->from(self::T_TEMPLATES)
            ->join('teams AS tm',      'tm.id = ' . self::T_TEMPLATES . '.team_id', 'left')
            ->join('departments AS d', 'd.id = tm.department_id',                   'left')
            ->where(self::T_TEMPLATES . '.is_active', 1)
            ->order_by('d.name',                        'ASC')
            ->order_by('tm.name',                       'ASC')
            ->order_by(self::T_TEMPLATES . '.review_type', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_all_teams(): array
    {
        return $this->db
            ->select('tm.id,
                      tm.name AS team_name,
                      d.id    AS department_id,
                      d.name  AS department_name,
                      CONCAT(tl.firstname, " ", tl.lastname) AS teamlead_name,
                      tl.profile_image AS teamlead_image,
                      CONCAT(mg.firstname, " ", mg.lastname) AS manager_name,
                      mg.profile_image AS manager_image')
            ->from('teams AS tm')
            ->join('departments AS d', 'd.id = tm.department_id', 'left')
            ->join('users AS tl',      'tl.id = tm.teamlead_id',  'left')
            ->join('users AS mg',      'mg.id = tm.manager_id',   'left')
            ->order_by('d.name',  'ASC')
            ->order_by('tm.name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_all_templates(): array
    {
        return $this->db
            ->select(self::T_TEMPLATES . '.*,
                      u.firstname AS created_by_firstname,
                      u.lastname  AS created_by_lastname,
                      tm.id   AS team_id,
                      tm.name AS team_name,
                      d.id    AS department_id,
                      d.name  AS department_name,
                      CONCAT(tl.firstname, " ", tl.lastname) AS teamlead_name,
                      CONCAT(mg.firstname, " ", mg.lastname) AS manager_name')
            ->from(self::T_TEMPLATES)
            ->join('users AS u',      'u.id = '  . self::T_TEMPLATES . '.created_by', 'left')
            ->join('teams AS tm',     'tm.id = ' . self::T_TEMPLATES . '.team_id',    'left')
            ->join('departments AS d','d.id = tm.department_id',                       'left')
            ->join('users AS tl',     'tl.id = tm.teamlead_id',                        'left')
            ->join('users AS mg',     'mg.id = tm.manager_id',                         'left')
            ->order_by(self::T_TEMPLATES . '.name', 'ASC')
            ->get()
            ->result_array();
    }

    public function insert_template(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert(self::T_TEMPLATES, $data);
        return (int) $this->db->insert_id();
    }

    public function update_template(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update(self::T_TEMPLATES, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_template(int $id): bool
    {
        $this->db->where('id', $id)->delete(self::T_TEMPLATES);
        return $this->db->affected_rows() > 0;
    }

    public function template_has_evaluations(int $template_id): bool
    {
        return $this->db
            ->where('template_id', $template_id)
            ->count_all_results(self::T_EVALS) > 0;
    }

    /* =========================================================
     * SECTIONS
     * ======================================================= */

    public function get_sections(int $template_id): array
    {
        return $this->db
            ->where('template_id', $template_id)
            ->order_by('sort_order', 'ASC')
            ->get(self::T_SECTIONS)
            ->result_array();
    }

    public function get_sections_with_criteria(int $template_id): array
    {
        $sections = $this->db
            ->where('template_id', $template_id)
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->get(self::T_SECTIONS)
            ->result_array();

        foreach ($sections as &$section) {
            $section['criteria'] = $this->db
                ->where('section_id', $section['id'])
                ->where('is_active', 1)
                ->order_by('sort_order', 'ASC')
                ->get(self::T_CRITERIA)
                ->result_array();
        }
        unset($section);

        return $sections;
    }

    public function insert_section(array $data): int
    {
        $this->db->insert(self::T_SECTIONS, $data);
        return (int) $this->db->insert_id();
    }

    public function update_section(int $id, array $data): bool
    {
        $this->db->where('id', $id)->update(self::T_SECTIONS, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_section(int $id): bool
    {
        $criteria_ids = array_column(
            $this->db->select('id')->where('section_id', $id)->get(self::T_CRITERIA)->result_array(),
            'id'
        );
        if ($criteria_ids) {
            $this->db->where_in('criteria_id', $criteria_ids)->delete(self::T_RESPONSES);
            $this->db->where_in('id',          $criteria_ids)->delete(self::T_CRITERIA);
        }
        $this->db->where('id', $id)->delete(self::T_SECTIONS);
        return true;
    }

    /* =========================================================
     * CRITERIA
     * ======================================================= */

    public function get_criteria(int $section_id): array
    {
        return $this->db
            ->where('section_id', $section_id)
            ->order_by('sort_order', 'ASC')
            ->get(self::T_CRITERIA)
            ->result_array();
    }

    public function insert_criteria(array $data): int
    {
        $this->db->insert(self::T_CRITERIA, $data);
        return (int) $this->db->insert_id();
    }

    public function update_criteria(int $id, array $data): bool
    {
        $this->db->where('id', $id)->update(self::T_CRITERIA, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_criteria(int $id): bool
    {
        $this->db->where('criteria_id', $id)->delete(self::T_RESPONSES);
        $this->db->where('id',          $id)->delete(self::T_CRITERIA);
        return true;
    }

    /* =========================================================
     * EVALUATIONS
     * ======================================================= */

public function get(int $id): ?array
{
    $row = $this->db
        ->select(self::T_EVALS . '.*,
                  u.firstname, u.lastname, u.emp_id, u.emp_title,
                  pos.title AS position_name,
                  t.name  AS template_name,
                  tm.id   AS team_id,
                  tm.name AS team_name,
                  d.id    AS department_id,
                  d.name  AS department_name,
                  CONCAT(tl.firstname, " ", tl.lastname) AS teamlead_name,
                  CONCAT(mg.firstname, " ", mg.lastname) AS manager_name,
                  rev.firstname  AS reviewer_firstname,
                  rev.lastname   AS reviewer_lastname,
                  appr.firstname AS approver_firstname,
                  appr.lastname  AS approver_lastname')
        ->from(self::T_EVALS)
        ->join('users AS u',           'u.id = '    . self::T_EVALS . '.user_id',     'left')
        ->join('hrm_positions AS pos', 'pos.id = u.emp_title',                         'left')
        ->join('users AS rev',         'rev.id = '  . self::T_EVALS . '.reviewer_id', 'left')
        ->join('users AS appr',        'appr.id = ' . self::T_EVALS . '.approved_by', 'left')
        ->join(self::T_TEMPLATES . ' AS t', 't.id = ' . self::T_EVALS . '.template_id', 'left')
        ->join('teams AS tm',          'tm.id = t.team_id',       'left')
        ->join('departments AS d',     'd.id = tm.department_id', 'left')
        ->join('users AS tl',          'tl.id = tm.teamlead_id',  'left')
        ->join('users AS mg',          'mg.id = tm.manager_id',   'left')
        ->where(self::T_EVALS . '.id', $id)
        ->get()
        ->row_array();

    return $row ?: null;
}

public function get_all(): array
{
    return $this->db
        ->select(self::T_EVALS . '.*,
                  u.firstname, u.profile_image, u.lastname, u.emp_id, u.emp_title,
                  pos.title AS position_name,
                  t.name  AS template_name,
                  tm.id   AS team_id,
                  tm.name AS team_name,
                  d.id    AS department_id,
                  d.name  AS department_name,
                  CONCAT(tl.firstname, " ", tl.lastname) AS teamlead_name,
                  CONCAT(mg.firstname, " ", mg.lastname) AS manager_name,
                  rev.firstname AS reviewer_firstname,
                  rev.lastname  AS reviewer_lastname')
        ->from(self::T_EVALS)
        ->join('users AS u',           'u.id = '   . self::T_EVALS . '.user_id',     'left')
        ->join('hrm_positions AS pos', 'pos.id = u.emp_title',                        'left')
        ->join('users AS rev',         'rev.id = ' . self::T_EVALS . '.reviewer_id', 'left')
        ->join(self::T_TEMPLATES . ' AS t', 't.id = ' . self::T_EVALS . '.template_id', 'left')
        ->join('teams AS tm',          'tm.id = t.team_id',       'left')
        ->join('departments AS d',     'd.id = tm.department_id', 'left')
        ->join('users AS tl',          'tl.id = tm.teamlead_id',  'left')
        ->join('users AS mg',          'mg.id = tm.manager_id',   'left')
        ->order_by(self::T_EVALS . '.review_date', 'DESC')
        ->order_by(self::T_EVALS . '.id',          'DESC')
        ->get()
        ->result_array();
}

public function get_by_user_filtered(int $user_id, $type = null): array
{
    $this->db
        ->select(self::T_EVALS . '.*,
                  t.name  AS template_name,
                  tm.id   AS team_id,
                  tm.name AS team_name,
                  d.name  AS department_name,
                  pos.title AS position_name,
                  rev.firstname AS reviewer_firstname,
                  rev.lastname  AS reviewer_lastname')
        ->from(self::T_EVALS)
        ->join(self::T_TEMPLATES . ' AS t', 't.id = ' . self::T_EVALS . '.template_id', 'left')
        ->join('teams AS tm',          'tm.id = t.team_id',                              'left')
        ->join('departments AS d',     'd.id = tm.department_id',                        'left')
        ->join('users AS rev',         'rev.id = ' . self::T_EVALS . '.reviewer_id',     'left')
        ->join('users AS u',           'u.id = '   . self::T_EVALS . '.user_id',         'left')
        ->join('hrm_positions AS pos', 'pos.id = u.emp_title',                            'left')
        ->where(self::T_EVALS . '.user_id', $user_id);

    if ($type === 'monthly') {
        $this->db->where(self::T_EVALS . '.review_type', 'monthly');
    } elseif ($type === 'annual') {
        $this->db->where(self::T_EVALS . '.review_type', 'annual');
    }

    return $this->db
        ->order_by(self::T_EVALS . '.review_date', 'DESC')
        ->get()
        ->result_array();
}

    public function insert(array $data): int
    {
        $this->db->insert(self::T_EVALS, $data);
        return (int) $this->db->insert_id();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update(self::T_EVALS, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete(int $id): bool
    {
        $this->db->trans_start();
        $this->delete_responses($id);
        $this->delete_goals($id);
        $this->db->where('id', $id)->delete(self::T_EVALS);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /* =========================================================
     * RESPONSES
     * ======================================================= */

    public function get_responses(int $eval_id): array
    {
        return $this->db
            ->select(self::T_RESPONSES . '.*, c.label, c.criteria_type, c.note')
            ->from(self::T_RESPONSES)
            ->join(self::T_CRITERIA . ' AS c', 'c.id = ' . self::T_RESPONSES . '.criteria_id', 'left')
            ->where(self::T_RESPONSES . '.evaluation_id', $eval_id)
            ->get()
            ->result_array();
    }

    public function get_responses_keyed(int $eval_id): array
    {
        $rows  = $this->get_responses($eval_id);
        $keyed = [];
        foreach ($rows as $r) {
            $keyed[(int) $r['criteria_id']] = $r;
        }
        return $keyed;
    }

    public function insert_responses_batch(int $eval_id, array $rows): bool
    {
        if (empty($rows)) return true;
        $this->db->insert_batch(self::T_RESPONSES, $rows);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_responses(int $eval_id): bool
    {
        $this->db->where('evaluation_id', $eval_id)->delete(self::T_RESPONSES);
        return true;
    }

    /* =========================================================
     * GOALS
     * ======================================================= */

    public function get_goals(int $eval_id): array
    {
        return $this->db
            ->where('evaluation_id', $eval_id)
            ->order_by('sort_order', 'ASC')
            ->get(self::T_GOALS)
            ->result_array();
    }

    public function insert_goals_batch(int $eval_id, array $rows): bool
    {
        if (empty($rows)) return true;
        $this->db->insert_batch(self::T_GOALS, $rows);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_goals(int $eval_id): bool
    {
        $this->db->where('evaluation_id', $eval_id)->delete(self::T_GOALS);
        return true;
    }

    /* =========================================================
     * KPI / SCORES
     * ======================================================= */

    public function get_kpi(): array
    {
        $base = self::T_EVALS;

        $total     = $this->db->count_all($base);
        $drafts    = $this->db->where('status', 'draft')->count_all_results($base);
        $submitted = $this->db->where('status', 'submitted')->count_all_results($base);
        $approved  = $this->db->where('status', 'approved')->count_all_results($base);
        $rejected  = $this->db->where('status', 'rejected')->count_all_results($base);

        $avg = $this->db
            ->select_avg('score_ratings', 'avg_rating')
            ->where('score_ratings IS NOT NULL')
            ->get($base)
            ->row_array();

        return [
            'total'      => (int) $total,
            'draft'      => (int) $drafts,
            'submitted'  => (int) $submitted,
            'approved'   => (int) $approved,
            'rejected'   => (int) $rejected,
            'avg_rating' => round((float) ($avg['avg_rating'] ?? 0), 2),
        ];
    }

    public function get_kpi_by_user(int $user_id): array
    {
        $base = self::T_EVALS;

        $this->db->where('user_id', $user_id);
        $total = $this->db->count_all_results($base);

        $this->db->where(['user_id' => $user_id, 'status' => 'draft']);
        $drafts = $this->db->count_all_results($base);

        $this->db->where(['user_id' => $user_id, 'status' => 'submitted']);
        $submitted = $this->db->count_all_results($base);

        $this->db->where(['user_id' => $user_id, 'status' => 'approved']);
        $approved = $this->db->count_all_results($base);

        $this->db->where(['user_id' => $user_id, 'status' => 'rejected']);
        $rejected = $this->db->count_all_results($base);

        $avg = $this->db
            ->select_avg('score_ratings', 'avg_rating')
            ->where('user_id', $user_id)
            ->where('score_ratings IS NOT NULL')
            ->get($base)
            ->row_array();

        return [
            'total'      => (int) $total,
            'draft'      => (int) $drafts,
            'submitted'  => (int) $submitted,
            'approved'   => (int) $approved,
            'rejected'   => (int) $rejected,
            'avg_rating' => round((float) ($avg['avg_rating'] ?? 0), 2),
        ];
    }

    public function get_last_evaluation(int $user_id): ?array
    {
        $row = $this->db
            ->where('user_id', $user_id)
            ->where('score_ratings IS NOT NULL')
            ->order_by('review_date', 'DESC')
            ->limit(1)
            ->get(self::T_EVALS)
            ->row_array();

        return $row ?: null;
    }

    public function recalculate_scores(int $eval_id): void
    {
        $responses     = $this->get_responses($eval_id);
        $rating_scores = [];
        $target_achs   = [];
        $pass_count    = 0;
        $fail_count    = 0;
        $att_scores    = [];

        foreach ($responses as $r) {
            switch ($r['criteria_type']) {
                case 'rating':
                    if ($r['score'] !== null) $rating_scores[] = (float) $r['score'];
                    break;
                case 'target':
                    if ($r['ach_pct'] !== null) $target_achs[] = (float) $r['ach_pct'];
                    break;
                case 'pass_fail':
                    if ($r['pass_fail'] === 'pass') $pass_count++;
                    if ($r['pass_fail'] === 'fail')  $fail_count++;
                    break;
                case 'attendance':
                    $att_map = ['Poor' => 1, 'Fair' => 2, 'Satisfactory' => 3, 'Good' => 4, 'Excellent' => 5];
                    if (isset($att_map[$r['selected_option']])) {
                        $att_scores[] = $att_map[$r['selected_option']];
                    }
                    break;
            }
        }

        $total_pf = $pass_count + $fail_count;

        $this->db->where('id', $eval_id)->update(self::T_EVALS, [
            'score_ratings'      => !empty($rating_scores) ? round(array_sum($rating_scores) / count($rating_scores), 2) : null,
            'score_targets'      => !empty($target_achs)   ? round(array_sum($target_achs)   / count($target_achs) * 100, 2) : null,
            'score_attendance'   => !empty($att_scores)    ? round(array_sum($att_scores)    / count($att_scores), 2) : null,
            'score_perf_metrics' => $total_pf > 0          ? $pass_count . ' / ' . $total_pf : null,
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
    }

    /* =========================================================
     * SEED DATA
     * ======================================================= */

    public function seed_default_templates(): void
    {
        if ($this->db->count_all(self::T_TEMPLATES) > 0) return;

        $created_by  = 1;
        $departments = [
            'AR'                   => 'AR',
            'Charges'              => 'Charges',
            'Payments'             => 'Payments',
            'Denials & Rejections' => 'Denials & Rejections',
        ];

        foreach ($departments as $dept_key => $dept_label) {
            $template_id = $this->insert_template([
                'name'        => 'RCM ' . $dept_label . ' — Monthly Evaluation',
                'review_type' => 'monthly',
                'description' => 'Standard monthly evaluation form for the ' . $dept_label . ' department.',
                'is_active'   => 1,
                'created_by'  => $created_by,
            ]);
            $this->_seed_sections($template_id, $dept_key);
        }
    }

    private function _seed_sections(int $template_id, string $dept): void
    {
        $sections_meta = [
            ['key' => 'attendance',   'label' => 'Attendance & Punctuality',         'order' => 1],
            ['key' => 'work_targets', 'label' => 'Work Targets',                     'order' => 2],
            ['key' => 'perf_metrics', 'label' => 'Individual Performance Metrics',   'order' => 3],
            ['key' => 'ratings',      'label' => 'Performance Ratings',              'order' => 4],
            ['key' => 'phone_usage',  'label' => 'Mobile Phone Usage',               'order' => 5],
            ['key' => 'supervisor',   'label' => 'Supervisor Evaluation & Comments', 'order' => 6],
            ['key' => 'goals',        'label' => 'Goals & Development',              'order' => 7],
            ['key' => 'verdict',      'label' => 'Overall Verdict & Signatures',     'order' => 8],
        ];

        foreach ($sections_meta as $sm) {
            $section_id = $this->insert_section([
                'template_id'   => $template_id,
                'section_key'   => $sm['key'],
                'section_label' => $sm['label'],
                'sort_order'    => $sm['order'],
                'is_active'     => 1,
            ]);
            $this->_seed_criteria($section_id, $sm['key'], $dept);
        }
    }

    private function _seed_criteria(int $section_id, string $section_key, string $dept): void
    {
        $criteria = [];

        switch ($section_key) {
            case 'attendance':
                $criteria = [
                    ['label' => 'Leaves / Absences',      'criteria_type' => 'attendance', 'sort_order' => 1],
                    ['label' => 'Late Hours',              'criteria_type' => 'attendance', 'sort_order' => 2],
                    ['label' => 'Extra / Overtime Hours',  'criteria_type' => 'attendance', 'sort_order' => 3],
                    ['label' => 'SOD / EOD Sign-off',      'criteria_type' => 'attendance', 'sort_order' => 4],
                ];
                break;
            case 'work_targets':
                $criteria = $this->_targets_for_dept($dept);
                break;
            case 'perf_metrics':
                $criteria = $this->_perf_metrics_for_dept($dept);
                break;
            case 'ratings':
                $labels = [
                    'Job Knowledge', 'Work Quality', 'Innovative Thinking',
                    'Professional Behaviour', 'Productivity', 'Communication',
                    'Dependability', 'Problem-Solving', 'Responsibility',
                    'HIPAA / Compliance', 'Adaptability', 'Team Management',
                ];
                foreach ($labels as $i => $lbl) {
                    $criteria[] = [
                        'label'         => $lbl,
                        'criteria_type' => 'rating',
                        'sort_order'    => $i + 1,
                        'note'          => $lbl === 'Team Management' ? 'For Team Leads only' : null,
                    ];
                }
                break;
            case 'phone_usage':
                $criteria = [
                    ['label' => 'Unnecessary Usage',   'criteria_type' => 'phone', 'sort_order' => 1],
                    ['label' => 'Average Usage / Day', 'criteria_type' => 'phone', 'sort_order' => 2],
                ];
                break;
            case 'supervisor':
                $criteria = [
                    ['label' => 'Employee Comments',   'criteria_type' => 'text', 'sort_order' => 1],
                    ['label' => 'Supervisor Comments', 'criteria_type' => 'text', 'sort_order' => 2],
                ];
                break;
            case 'goals':
                $criteria = [['label' => 'Goals & Development', 'criteria_type' => 'text', 'sort_order' => 1]];
                break;
            case 'verdict':
                $criteria = [['label' => 'Overall Verdict', 'criteria_type' => 'text', 'sort_order' => 1]];
                break;
        }

        foreach ($criteria as $c) {
            $this->insert_criteria(array_merge([
                'section_id'           => $section_id,
                'criteria_type'        => 'rating',
                'label'                => '',
                'default_target_day'   => null,
                'default_target_month' => null,
                'default_deadline'     => null,
                'note'                 => null,
                'sort_order'           => 0,
                'is_active'            => 1,
            ], $c));
        }
    }

    private function _targets_for_dept(string $dept): array
    {
        switch ($dept) {
            case 'AR':
                return [
                    ['label' => 'AR Follow-up / day',         'criteria_type' => 'target', 'default_target_day' => 5,  'default_deadline' => '5',  'default_target_month' => 200, 'sort_order' => 1],
                    ['label' => 'Accounts Worked / Month',    'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 2],
                    ['label' => 'Collection Rate Target (%)', 'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 3],
                    ['label' => 'Reporting (Weekly/Monthly)', 'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 4],
                ];
            case 'Charges':
                return [
                    ['label' => 'Charge Entry Volume',        'criteria_type' => 'target', 'default_target_day' => 25, 'default_deadline' => '25', 'default_target_month' => 300, 'sort_order' => 1],
                    ['label' => 'Benefits Verification',      'criteria_type' => 'target', 'default_target_day' => 25, 'default_deadline' => '25', 'default_target_month' => null, 'sort_order' => 2],
                    ['label' => 'Enrollment / Credentialing', 'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 3],
                    ['label' => 'Reporting (Weekly/Monthly)', 'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 4],
                ];
            case 'Payments':
                return [
                    ['label' => 'Payment Posting (ERAs/day)', 'criteria_type' => 'target', 'default_target_day' => 8,  'default_deadline' => '10', 'default_target_month' => 220, 'sort_order' => 1],
                    ['label' => 'Unapplied Cash Resolution',  'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 2],
                    ['label' => 'Daily Reconciliation',       'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 3],
                    ['label' => 'Reporting (Weekly/Monthly)', 'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 4],
                ];
            case 'Denials & Rejections':
                return [
                    ['label' => 'Denial Follow-ups / day',    'criteria_type' => 'target', 'default_target_day' => 5,  'default_deadline' => '5',  'default_target_month' => 100, 'sort_order' => 1],
                    ['label' => 'Appeal Letters Written',     'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 2],
                    ['label' => 'Denial Resolution Rate',     'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 3],
                    ['label' => 'Reporting (Weekly/Monthly)', 'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 4],
                    ['label' => 'Claims Reviewed / day',      'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 5],
                    ['label' => 'Resubmission TAT (days)',    'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 6],
                    ['label' => 'Eligibility Verifications',  'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 7],
                    ['label' => 'Reporting (Weekly/Monthly)', 'criteria_type' => 'target', 'default_target_day' => null,'default_deadline' => null,'default_target_month' => null, 'sort_order' => 8],
                ];
        }
        return [];
    }

    private function _perf_metrics_for_dept(string $dept): array
    {
        switch ($dept) {
            case 'AR':
                return [
                    ['label' => 'Works assigned AR accounts daily — no idle accounts in queue', 'criteria_type' => 'pass_fail', 'sort_order' => 1],
                    ['label' => 'Prioritises high-value and ageing accounts appropriately',     'criteria_type' => 'pass_fail', 'sort_order' => 2],
                    ['label' => 'Notes are clear, timestamped, and reflect action taken',       'criteria_type' => 'pass_fail', 'sort_order' => 3],
                    ['label' => 'Follows up with payers / patients without repeated prompting', 'criteria_type' => 'pass_fail', 'sort_order' => 4],
                    ['label' => 'Escalates stuck accounts to supervisor with context',          'criteria_type' => 'pass_fail', 'sort_order' => 5],
                ];
            case 'Charges':
                return [
                    ['label' => 'Enters charges accurately without missing fields or incorrect codes', 'criteria_type' => 'pass_fail', 'sort_order' => 1],
                    ['label' => 'Reviews documentation before posting — no unsupported charges',       'criteria_type' => 'pass_fail', 'sort_order' => 2],
                    ['label' => 'Meets daily charge entry TAT without follow-up',                      'criteria_type' => 'pass_fail', 'sort_order' => 3],
                    ['label' => 'Avoids duplicate or incorrect charge submissions',                    'criteria_type' => 'pass_fail', 'sort_order' => 4],
                    ['label' => 'Flags issues proactively instead of skipping',                        'criteria_type' => 'pass_fail', 'sort_order' => 5],
                ];
            case 'Payments':
                return [
                    ['label' => 'Posts payments accurately — ERA/EOB applied with no variances', 'criteria_type' => 'pass_fail', 'sort_order' => 1],
                    ['label' => 'Resolves unapplied cash without reminders',                     'criteria_type' => 'pass_fail', 'sort_order' => 2],
                    ['label' => 'Applies contractual adjustments correctly',                     'criteria_type' => 'pass_fail', 'sort_order' => 3],
                    ['label' => 'Completes daily batch balancing on time',                       'criteria_type' => 'pass_fail', 'sort_order' => 4],
                    ['label' => 'Flags discrepancies proactively',                               'criteria_type' => 'pass_fail', 'sort_order' => 5],
                ];
            case 'Denials & Rejections':
                return [
                    ['label' => 'Works assigned denials on time without backlogs',  'criteria_type' => 'pass_fail', 'sort_order' => 1],
                    ['label' => 'Identifies correct denial reason before action',   'criteria_type' => 'pass_fail', 'sort_order' => 2],
                    ['label' => 'Writes strong appeal letters independently',       'criteria_type' => 'pass_fail', 'sort_order' => 3],
                    ['label' => 'Documents actions accurately in system',           'criteria_type' => 'pass_fail', 'sort_order' => 4],
                    ['label' => 'Escalates complex cases appropriately',            'criteria_type' => 'pass_fail', 'sort_order' => 5],
                    ['label' => 'Reviews rejected claims within TAT',               'criteria_type' => 'pass_fail', 'sort_order' => 6],
                    ['label' => 'Identifies root cause before resubmitting',        'criteria_type' => 'pass_fail', 'sort_order' => 7],
                    ['label' => 'Prevents repeat rejections via verification',      'criteria_type' => 'pass_fail', 'sort_order' => 8],
                    ['label' => 'Maintains accurate rejection logs',                'criteria_type' => 'pass_fail', 'sort_order' => 9],
                    ['label' => 'Shows reduction in error-based rejections',        'criteria_type' => 'pass_fail', 'sort_order' => 10],
                ];
        }
        return [];
    }
}