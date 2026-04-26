<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Evaluations extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('evaluations/Evaluations_model', 'evals');
        $this->load->model('User_model', 'eval_users');
        $this->load->model('Activity_log_model');
        $this->load->model('Department_model', 'departments');
        $this->load->helper(['url', 'form', 'evaluations']);
        $this->load->library('form_validation');
    }

    /* =========================================================
     * INTERNAL HELPERS
     * ======================================================= */

    protected function _render(string $title, string $subview, array $data = []): void
    {
        add_module_assets('evaluations', [
            'css' => ['evaluations.css'],
            'js'  => ['evaluations.js'],
        ]);

        $this->load->view('layouts/master', [
            'page_title' => $title,
            'subview'    => $subview,
            'view_data'  => $data,
        ]);
    }

    protected function _json($data, int $status = 200): void
    {
        if (ob_get_level()) ob_clean();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    protected function _guard(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            if (staff_can($permission, 'evaluations')) {
                return;
            }
        }
        $this->_forbidden();
    }

    protected function _forbidden(): void
    {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        echo $html;
        exit;
    }

    protected function _current_user_id(): int
    {
        if (function_exists('get_staff_user_id')) {
            $id = (int) get_staff_user_id();
            if ($id > 0) return $id;
        }
        return (int) $this->session->userdata('staff_user_id') ?: (int) $this->session->userdata('user_id');
    }

    protected function _log(string $action): void
    {
        $this->Activity_log_model->add([
            'user_id'    => $this->_current_user_id(),
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /* =========================================================
     * INDEX — EVALUATIONS LIST
     * ======================================================= */

    public function index()
    {
        $this->_guard('view_global');

        $view = [
            'page_title'  => 'Employee Evaluations',
            'table_id'    => 'evaluationsTable',
            'evaluations' => $this->evals->get_all(),
            'kpi'         => $this->evals->get_kpi(),
            'templates'   => $this->evals->get_active_templates(),
            'users'       => $this->eval_users->get_all_users(),
            'departments' => $this->departments->get_all(),
        ];

        $this->_render('Employee Evaluations', 'evaluations/index', $view);
    }

    /* =========================================================
     * TEMPLATES — CRUD
     * ======================================================= */

    public function templates()
    {
        $this->_guard('view_global');

        $view = [
            'page_title' => 'Evaluation Templates',
            'table_id'   => 'templatesTable',
            'templates'  => $this->evals->get_all_templates(),
        ];

        $this->_render('Evaluation Templates', 'evaluations/templates/index', $view);
    }

    public function template_create()
    {
        $this->_guard('create');
    
        if ($this->input->post()) {
            $data = $this->_collect_template_data();
    
            if (empty($data['name'])) {
                set_alert('danger', 'Template name is required.');
                redirect('evaluations/template_create');
                return;
            }
    
            if (empty($data['team_id'])) {
                set_alert('danger', 'Please select a team.');
                redirect('evaluations/template_create');
                return;
            }
    
            $id = $this->evals->insert_template($data);
            $this->_log('Evaluation template created: ID ' . $id . ', Name: ' . $data['name']);
            set_alert('success', 'Template created successfully.');
            redirect('evaluations/template_edit/' . $id);
        }
    
        $this->_render('Create Template', 'evaluations/templates/create', [
            'page_title' => 'Create Evaluation Template',
            'teams'      => $this->evals->get_all_teams(),
        ]);
    }

    public function template_edit($id)
    {
        $this->_guard('edit');

        $id       = (int) $id;
        $template = $this->evals->get_template($id);
        if (!$template) {
            show_404();
        }

        if ($this->input->post()) {
            $data = $this->_collect_template_data();
            $this->evals->update_template($id, $data);
            $this->_log('Evaluation template updated: ID ' . $id . ', Name: ' . $data['name']);
            set_alert('success', 'Template updated.');
            redirect('evaluations/template_edit/' . $id);
        }

        $view = [
            'page_title' => 'Edit Template: ' . $template['name'],
            'template'   => $template,
            'sections'   => $this->evals->get_sections($id),
            'teams'      => $this->evals->get_all_teams(),
        ];

        $this->_render('Edit Template', 'evaluations/templates/edit', $view);
    }

    public function template_delete($id)
    {
        $this->_guard('delete');

        $id       = (int) $id;
        $template = $this->evals->get_template($id);
        if (!$template) {
            show_404();
        }

        if ($this->evals->template_has_evaluations($id)) {
            set_alert('danger', 'Cannot delete a template that has evaluations linked to it. Deactivate it instead.');
            redirect('evaluations/templates');
            return;
        }

        $this->evals->delete_template($id);
        $this->_log('Evaluation template deleted: ID ' . $id . ', Name: ' . $template['name']);
        set_alert('success', 'Template deleted.');
        redirect('evaluations/templates');
    }

    public function template_toggle($id)
    {
        $this->_guard('edit');
        $id       = (int) $id;
        $template = $this->evals->get_template($id);
        if (!$template) {
            show_404();
        }
        $newStatus = (int) $template['is_active'] === 1 ? 0 : 1;
        $this->evals->update_template($id, ['is_active' => $newStatus]);
        $this->_log('Evaluation template ' . ($newStatus ? 'activated' : 'deactivated') . ': ID ' . $id);
        set_alert('success', 'Template ' . ($newStatus ? 'activated' : 'deactivated') . '.');
        redirect('evaluations/templates');
    }

    /* =========================================================
     * SECTIONS — AJAX CRUD (used inside template_edit page)
     * ======================================================= */

    public function section_store()
    {
        $this->_guard('create');

        $template_id = (int) $this->input->post('template_id', true);
        $data = [
            'template_id'   => $template_id,
            'section_key'   => trim((string) $this->input->post('section_key', true)),
            'section_label' => trim((string) $this->input->post('section_label', true)),
            'sort_order'    => (int) $this->input->post('sort_order', true),
            'is_active'     => 1,
        ];

        if (!$data['section_label'] || !$data['section_key']) {
            $this->_json(['ok' => false, 'message' => 'Section key and label are required.'], 422);
            return;
        }

        $id = $this->evals->insert_section($data);
        $this->_json(['ok' => true, 'id' => $id]);
    }

    public function section_update($id)
    {
        $this->_guard('edit');

        $id = (int) $id;
        $data = [
            'section_label' => trim((string) $this->input->post('section_label', true)),
            'sort_order'    => (int) $this->input->post('sort_order', true),
            'is_active'     => (int) $this->input->post('is_active', true),
        ];

        $this->evals->update_section($id, $data);
        $this->_json(['ok' => true]);
    }

    public function section_delete($id)
    {
        $this->_guard('delete');

        $id = (int) $id;
        $this->evals->delete_section($id);
        $this->_json(['ok' => true]);
    }

    /* =========================================================
     * CRITERIA — AJAX CRUD
     * ======================================================= */

    public function criteria_store()
    {
        $this->_guard('create');

        $data = [
            'section_id'           => (int) $this->input->post('section_id', true),
            'criteria_type'        => $this->input->post('criteria_type', true),
            'label'                => trim((string) $this->input->post('label', true)),
            'sub_group'            => trim((string) $this->input->post('sub_group', true)) ?: null,
            'default_target_day'   => $this->input->post('default_target_day', true) ?: null,
            'default_target_month' => $this->input->post('default_target_month', true) ?: null,
            'default_deadline'     => trim((string) $this->input->post('default_deadline', true)) ?: null,
            'note'                 => trim((string) $this->input->post('note', true)) ?: null,
            'sort_order'           => (int) $this->input->post('sort_order', true),
            'is_active'            => 1,
        ];

        $allowed_types = ['rating', 'pass_fail', 'target', 'attendance', 'phone', 'text'];
        if (!in_array($data['criteria_type'], $allowed_types, true)) {
            $this->_json(['ok' => false, 'message' => 'Invalid criteria type.'], 422);
            return;
        }

        $id = $this->evals->insert_criteria($data);
        $this->_json(['ok' => true, 'id' => $id]);
    }

    public function criteria_update($id)
    {
        $this->_guard('edit');

        $id = (int) $id;
        $data = [
            'label'                => trim((string) $this->input->post('label', true)),
            'sub_group'            => trim((string) $this->input->post('sub_group', true)) ?: null,
            'default_target_day'   => $this->input->post('default_target_day', true) ?: null,
            'default_target_month' => $this->input->post('default_target_month', true) ?: null,
            'default_deadline'     => trim((string) $this->input->post('default_deadline', true)) ?: null,
            'note'                 => trim((string) $this->input->post('note', true)) ?: null,
            'sort_order'           => (int) $this->input->post('sort_order', true),
            'is_active'            => (int) $this->input->post('is_active', true),
        ];

        $this->evals->update_criteria($id, $data);
        $this->_json(['ok' => true]);
    }

    public function criteria_delete($id)
    {
        $this->_guard('delete');

        $id = (int) $id;
        $this->evals->delete_criteria($id);
        $this->_json(['ok' => true]);
    }

    /* =========================================================
     * EVALUATIONS — CREATE / FILL
     * ======================================================= */

    public function create()
    {
        $this->_guard('create');

        // Step 1 POST: select employee + template → redirect to fill form
        if ($this->input->post('step') === 'select') {
            $user_id     = (int) $this->input->post('user_id', true);
            $template_id = (int) $this->input->post('template_id', true);

            if ($user_id <= 0 || $template_id <= 0) {
                set_alert('danger', 'Please select an employee and a template.');
                redirect('evaluations/create');
                return;
            }

            redirect('evaluations/fill/' . $template_id . '/' . $user_id);
            return;
        }

        $this->_render('Start Evaluation', 'evaluations/create', [
            'page_title' => 'Start New Evaluation',
            'templates'  => $this->evals->get_active_templates(),
            'users'      => $this->eval_users->get_all_users(),
            'departments' => $this->departments->get_all(),
        ]);
    }

    public function user_team($user_id)
    {
        $this->_guard('view_global');
    
        $user_id = (int) $user_id;
    
        $row = $this->db
            ->select('tm.id AS team_id, tm.name AS team_name, d.name AS department_name')
            ->from('users AS u')
            ->join('teams AS tm', 'tm.id = u.emp_team', 'left')
            ->join('departments AS d', 'd.id = tm.department_id', 'left')
            ->where('u.id', $user_id)
            ->get()
            ->row_array();
    
        $this->_json(['ok' => true, 'team' => $row ?: null]);
    }
    
    public function fill($template_id, $user_id)
    {
        $this->_guard('create');

        $template_id = (int) $template_id;
        $user_id     = (int) $user_id;

        $template = $this->evals->get_template($template_id);
        $user     = $this->eval_users->get_user_by_id($user_id);

        if (!$template || !$user) {
            show_404();
        }

        $sections = $this->evals->get_sections_with_criteria($template_id);

        if ($this->input->post()) {
            $eval_id = $this->_save_evaluation(0, $template_id, $user_id, $template, $user);
            $this->_log('Evaluation created: ID ' . $eval_id . ' for User ID ' . $user_id . ' Template: ' . $template['name']);
            set_alert('success', 'Evaluation saved successfully.');
            redirect('evaluations/view/' . $eval_id);
            return;
        }

        $view = [
            'page_title'  => '' . ($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''),
            'template'    => $template,
            'user'        => $user,
            'sections'    => $sections,
            'eval'        => null,
            'responses'   => [],
            'goals'       => [],
            'reviewer_id' => $this->_current_user_id(),
        ];

        $this->_render('Fill Evaluation', 'evaluations/fill', $view);
    }

    public function edit($id)
    {
        $this->_guard('edit');

        $id   = (int) $id;
        $eval = $this->evals->get($id);

        if (!$eval) {
            show_404();
        }

        if (in_array($eval['status'], ['approved'], true) && !staff_can('manage', 'evaluations')) {
            set_alert('danger', 'Approved evaluations cannot be edited.');
            redirect('evaluations/view/' . $id);
            return;
        }

        $template = $this->evals->get_template((int) $eval['template_id']);
        $user = $this->eval_users->get_user_by_id((int) $eval['user_id']);
        $sections = $this->evals->get_sections_with_criteria((int) $eval['template_id']);

        if ($this->input->post()) {
            $this->_save_evaluation($id, (int) $eval['template_id'], (int) $eval['user_id'], $template, $user);
            $this->_log('Evaluation updated: ID ' . $id);
            set_alert('success', 'Evaluation updated.');
            redirect('evaluations/view/' . $id);
            return;
        }

        $view = [
            'page_title'  => 'Edit Evaluation',
            'template'    => $template,
            'user'        => $user,
            'sections'    => $sections,
            'eval'        => $eval,
            'responses'   => $this->evals->get_responses_keyed($id),
            'goals'       => $this->evals->get_goals($id),
            'reviewer_id' => $this->_current_user_id(),
        ];

        $this->_render('Edit Evaluation', 'evaluations/fill', $view);
    }

    /* =========================================================
     * EVALUATIONS — VIEW
     * ======================================================= */

    public function view($id)
    {
        $this->_guard('view_global');

        $id   = (int) $id;
        $eval = $this->evals->get($id);

        if (!$eval) {
            show_404();
        }

        $template  = $this->evals->get_template((int) $eval['template_id']);
        $user     = $this->eval_users->get_user_by_id((int) $eval['user_id']);
        $reviewer = $this->eval_users->get_user_by_id((int) $eval['reviewer_id']);
        $sections  = $this->evals->get_sections_with_criteria((int) $eval['template_id']);
        $responses = $this->evals->get_responses_keyed($id);
        $goals     = $this->evals->get_goals($id);

        $view = [
            'page_title' => 'Evaluation for ' . ($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''),
            'eval'       => $eval,
            'template'   => $template,
            'user'       => $user,
            'reviewer'   => $reviewer,
            'sections'   => $sections,
            'responses'  => $responses,
            'goals'      => $goals,
            'can_approve'=> staff_can('approve', 'evaluations'),
            'can_edit'   => staff_can('edit', 'evaluations') && $eval['status'] !== 'approved',
            'can_delete' => staff_can('delete', 'evaluations'),
        ];

        $this->_render('View Evaluation', 'evaluations/view', $view);
    }

    /* =========================================================
     * EVALUATIONS — STATUS TRANSITIONS
     * ======================================================= */

    public function submit($id)
    {
        $this->_guard('create');

        if (!$this->input->post()) {
            show_404();
        }

        $id   = (int) $id;
        $eval = $this->evals->get($id);
        if (!$eval) {
            show_404();
        }

        if ($eval['status'] !== 'draft') {
            set_alert('danger', 'Only draft evaluations can be submitted.');
            redirect('evaluations/view/' . $id);
            return;
        }

        $this->evals->update($id, ['status' => 'submitted']);
        $this->_log('Evaluation submitted: ID ' . $id);
        set_alert('success', 'Evaluation submitted for approval.');
        redirect('evaluations/view/' . $id);
    }

    public function approve($id)
    {
        $this->_guard('approve');

        if (!$this->input->post()) {
            show_404();
        }

        $id   = (int) $id;
        $eval = $this->evals->get($id);
        if (!$eval) {
            show_404();
        }

        $this->evals->update($id, [
            'status'      => 'approved',
            'approved_by' => $this->_current_user_id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_log('Evaluation approved: ID ' . $id);
        set_alert('success', 'Evaluation approved.');
        redirect('evaluations/view/' . $id);
    }

    public function reject($id)
    {
        $this->_guard('approve');

        if (!$this->input->post()) {
            show_404();
        }

        $id     = (int) $id;
        $eval   = $this->evals->get($id);
        if (!$eval) {
            show_404();
        }

        $reason = trim((string) $this->input->post('rejection_reason', true));

        $this->evals->update($id, [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $this->_log('Evaluation rejected: ID ' . $id . ($reason ? ' — Reason: ' . $reason : ''));
        set_alert('warning', 'Evaluation rejected and returned for revision.');
        redirect('evaluations/view/' . $id);
    }

    /* =========================================================
     * EVALUATIONS — DELETE
     * ======================================================= */

    public function delete($id)
    {
        $this->_guard('delete');

        if (!$this->input->post()) {
            show_404();
        }

        $id   = (int) $id;
        $eval = $this->evals->get($id);
        if (!$eval) {
            show_404();
        }

        $this->evals->delete($id);
        $this->_log('Evaluation deleted: ID ' . $id . ', User ID: ' . $eval['user_id']);
        set_alert('success', 'Evaluation deleted.');
        redirect('evaluations');
    }

    /* =========================================================
     * EMPLOYEE HISTORY (AJAX / inline)
     * ======================================================= */

    public function employee_history($user_id)
    {
        $this->_guard('view_global');

        $user_id = (int) $user_id;
        $history = $this->evals->get_by_user_filtered($user_id);

        $this->_json(['ok' => true, 'history' => $history]);
    }

    public function get_eval_json($id)
    {
        $this->_guard('view_global');

        $id   = (int) $id;
        $eval = $this->evals->get($id);

        if (!$eval) {
            $this->_json(['ok' => false, 'message' => 'Not found.'], 404);
            return;
        }

        $eval['responses'] = $this->evals->get_responses_keyed($id);
        $eval['goals']     = $this->evals->get_goals($id);

        $this->_json(['ok' => true, 'eval' => $eval]);
    }

    /* =========================================================
     * TEMPLATE JSON (for dynamic form building)
     * ======================================================= */

    public function template_json($id)
    {
        $this->_guard('view_global');

        $id       = (int) $id;
        $template = $this->evals->get_template($id);
        if (!$template) {
            $this->_json(['ok' => false, 'message' => 'Template not found.'], 404);
            return;
        }

        $template['sections'] = $this->evals->get_sections_with_criteria($id);
        $this->_json(['ok' => true, 'template' => $template]);
    }

    /* =========================================================
     * SAVE EVALUATION (internal shared logic)
     * ======================================================= */

    protected function _save_evaluation(int $eval_id, int $template_id, int $user_id, array $template, array $user): int
    {
        $uid = $this->_current_user_id();
        $now = date('Y-m-d H:i:s');

        // Attendance raw
        $att_working = (int) $this->input->post('att_working_days', true);
        $att_present = (int) $this->input->post('att_days_present', true);
        $att_absent  = (int) $this->input->post('att_days_absent', true);
        $att_late    = (int) $this->input->post('att_late_arrivals', true);
        $att_extra   = (float) $this->input->post('att_extra_hours', true);
        $att_pct     = $att_working > 0 ? round($att_present / $att_working * 100, 2) : 0;

        // Goals
        $goals_raw        = $this->input->post('goals', true) ?: [];
        $training_raw     = $this->input->post('training_needs', true) ?: [];

        // Main eval payload
        $eval_data = [
            'template_id'          => $template_id,
            'user_id'              => $user_id,
            'reviewer_id'          => $uid,
            'review_type'          => $template['review_type'],
            'review_period'        => trim((string) $this->input->post('review_period', true)),
            'review_date'          => $this->input->post('review_date', true) ?: date('Y-m-d'),
            'att_working_days'     => $att_working,
            'att_days_present'     => $att_present,
            'att_days_absent'      => $att_absent,
            'att_late_arrivals'    => $att_late,
            'att_extra_hours'      => $att_extra,
            'att_pct'              => $att_pct,
            'overall_verdict'      => trim((string) $this->input->post('overall_verdict', true)),
            'employee_comments'    => trim((string) $this->input->post('employee_comments', false)),
            'supervisor_comments'  => trim((string) $this->input->post('supervisor_comments', false)),
            'sig_supervisor'       => trim((string) $this->input->post('sig_supervisor', true)),
            'sig_supervisor_date'  => $this->input->post('sig_supervisor_date', true) ?: null,
            'sig_employee'         => trim((string) $this->input->post('sig_employee', true)),
            'sig_employee_date'    => $this->input->post('sig_employee_date', true) ?: null,
            'sig_hr'               => trim((string) $this->input->post('sig_hr', true)),
            'sig_hr_date'          => $this->input->post('sig_hr_date', true) ?: null,
            'status'               => $this->input->post('action', true) === 'submit' ? 'submitted' : 'draft',
            'updated_at'           => $now,
        ];

        if ($eval_id === 0) {
            $eval_data['created_at'] = $now;
            $eval_id = $this->evals->insert($eval_data);
        } else {
            $this->evals->update($eval_id, $eval_data);
            $this->evals->delete_responses($eval_id);
            $this->evals->delete_goals($eval_id);
        }

        // Save responses
        $responses_post = $this->input->post('responses', true) ?: [];
        $response_rows  = [];

        foreach ($responses_post as $criteria_id => $resp) {
            $row = [
                'evaluation_id' => $eval_id,
                'criteria_id'   => (int) $criteria_id,
            ];

            $row['score']            = isset($resp['score'])            ? (int) $resp['score']            : null;
            $row['pass_fail']        = isset($resp['pass_fail'])        ? $resp['pass_fail']               : null;
            $row['target_day']       = isset($resp['target_day'])       ? (float) $resp['target_day']      : null;
            $row['deadline']         = isset($resp['deadline'])         ? $resp['deadline']                : null;
            $row['target_month']     = isset($resp['target_month'])     ? (float) $resp['target_month']    : null;
            $row['actual_month']     = isset($resp['actual_month'])     ? (float) $resp['actual_month']    : null;
            $row['selected_option']  = isset($resp['selected_option'])  ? $resp['selected_option']         : null;
            $row['comments']         = isset($resp['comments'])         ? trim($resp['comments'])          : null;

            // Auto-compute ach_pct for target rows
            if ($row['target_month'] > 0 && $row['actual_month'] !== null) {
                $row['ach_pct'] = round($row['actual_month'] / $row['target_month'], 4);
            } else {
                $row['ach_pct'] = null;
            }

            $response_rows[] = $row;
        }

        if (!empty($response_rows)) {
            $this->evals->insert_responses_batch($eval_id, $response_rows);
        }

        // Save goals
        $goal_rows = [];
        foreach ($goals_raw as $i => $goal_text) {
            $goal_text      = trim((string) $goal_text);
            $training_text  = trim((string) ($training_raw[$i] ?? ''));
            if ($goal_text !== '' || $training_text !== '') {
                $goal_rows[] = [
                    'evaluation_id' => $eval_id,
                    'goal'          => $goal_text ?: null,
                    'training_need' => $training_text ?: null,
                    'sort_order'    => $i,
                ];
            }
        }

        if (!empty($goal_rows)) {
            $this->evals->insert_goals_batch($eval_id, $goal_rows);
        }

        // Recompute and store aggregate scores
        $this->evals->recalculate_scores($eval_id);

        return $eval_id;
    }

    /* =========================================================
     * COLLECT TEMPLATE DATA FROM POST
     * ======================================================= */

    protected function _collect_template_data(): array
    {
        return [
            'name'        => trim((string) $this->input->post('name', true)),
            'team_id'     => (int) $this->input->post('team_id', true) ?: null,
            'review_type' => $this->input->post('review_type', true) ?: 'monthly',
            'description' => trim((string) $this->input->post('description', true)) ?: null,
            'is_active'   => (int) $this->input->post('is_active', true),
            'created_by'  => $this->_current_user_id(),
        ];
    }

    public function section_criteria_json($section_id)
    {
        $this->_guard('view_global');
    
        $section_id = (int) $section_id;
        $criteria   = $this->evals->get_criteria($section_id);
    
        $this->_json(['ok' => true, 'criteria' => $criteria]);
    }    


    /* =========================================================
     * VIEW OWN - STAFF ONLY
     * ======================================================= */
     
    public function my()
    {
        $user_id = $this->_current_user_id();
    
        $type = $this->input->get('type');
        $last_eval = $this->evals->get_last_evaluation($user_id);
    
        $evaluations = $this->evals->get_by_user_filtered($user_id, $type);
        $kpi         = $this->evals->get_kpi_by_user($user_id);
    
        $view = [
            'page_title'  => 'My Evaluations',
            'table_id'    => 'myEvaluationsTable',
            'evaluations' => $evaluations,
            'kpi'         => $kpi,
            'last_eval'   => $last_eval,
        ];
    
        $this->_render('My Evaluations', 'evaluations/my/index', $view);
    }

    public function my_view($id)
    {
        $id   = (int) $id;
        $eval = $this->evals->get($id);
    
        if (!$eval || $eval['user_id'] != $this->_current_user_id()) {
            show_404(); // or forbidden
        }
    
        // reuse existing logic
        $template  = $this->evals->get_template((int) $eval['template_id']);
        $user      = $this->eval_users->get_user_by_id((int) $eval['user_id']);
        $reviewer  = $this->eval_users->get_user_by_id((int) $eval['reviewer_id']);
        $sections  = $this->evals->get_sections_with_criteria((int) $eval['template_id']);
        $responses = $this->evals->get_responses_keyed($id);
        $goals     = $this->evals->get_goals($id);
    
        $view = [
            'page_title' => 'My Evaluation',
            'eval'       => $eval,
            'template'   => $template,
            'user'       => $user,
            'reviewer'   => $reviewer,
            'sections'   => $sections,
            'responses'  => $responses,
            'goals'      => $goals,
            'readonly'   => true, // 🔥 important flag
        ];
    
        $this->_render('My Evaluation', 'evaluations/view', $view);
    }
}