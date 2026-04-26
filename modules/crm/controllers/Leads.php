<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leads extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['url', 'form', 'crm']);
        $this->load->library(['form_validation']);
        $this->load->model('crm/Crmleads_model', 'crmleads');
        $this->load->model('crm/Crm_activity_model', 'crmactivity');
        $this->load->model('crm/Crm_files_model', 'crmfiles');
        $this->load->model('crm/Crmproposals_model', 'crmproposals');
        $this->load->model('User_model', 'crmusers');
    }

    /* =========================================================
     * INTERNAL HELPERS (same pattern as Crm controller)
     * ======================================================= */

    protected function _render($title, $subview, $data = [])
    {
        add_module_assets('crm', [
            'css' => ['crm.css'],
            'js'  => ['crm.js'],
        ]);
    
        $this->load->view('layouts/master', [
            'page_title' => $title,
            'subview'    => $subview,
            'view_data'  => $data,
        ]);
    }

    protected function _log_activity(string $action, int $relId, string $description, array $metadata = [], string $relType = 'lead'): void
    {
        $userId = (int)$this->session->userdata('user_id');
    
        $this->crmactivity->log([
            'user_id'     => $userId > 0 ? $userId : null,
            'rel_type'    => $relType,
            'rel_id'      => $relId > 0 ? $relId : null,
            'action'      => $action,
            'description' => $description,
            'metadata'    => $metadata,
            'ip_address'  => $this->input->ip_address(),
        ]);
    }
    
    protected function _lead_label(array $lead): string
    {
        $name  = trim((string)($lead['practice_name'] ?? 'Unknown Lead'));
        $email = trim((string)($lead['contact_email'] ?? ''));
    
        if ($email !== '') {
            return $name . ' (' . $email . ')';
        }
    
        return $name;
    }
    
    protected function _guard_manage_crm()
    {
        if (
            staff_can('view', 'crm') ||
            staff_can('view_global', 'crm') ||
            staff_can('view_own', 'crm')
        ) {
            return;
        }
    
        $this->_crm_forbidden();
    }

    protected function _crm_forbidden()
    {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        echo $html;
        exit;
    }

    /**
     * Leads permissions: keep compatible with your current setup.
     * If you later add lead_* permissions, they will work too.
     */
    protected function _can_create(): bool
    {
        return staff_can('lead_create', 'crm') || staff_can('client_create', 'crm');
    }

    protected function _can_edit(): bool
    {
        return staff_can('lead_edit', 'crm') || staff_can('client_edit', 'crm');
    }

    protected function _can_delete(): bool
    {
        return staff_can('lead_delete', 'crm') || staff_can('client_delete', 'crm');
    }

    protected function _can_view(): bool
    {
        return staff_can('lead_view', 'crm') || staff_can('client_view', 'crm') || staff_can('view', 'crm');
    }


    protected function _require_login(): void
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            exit;
        }
    }
    
    protected function _user_id(): int
    {
        return (int)$this->session->userdata('user_id');
    }
    /* =========================================================
     * MAIN LIST
     * ======================================================= */

public function index()
{
    $this->_guard_manage_crm();

    if (!$this->_can_view()) {
        $this->_crm_forbidden();
    }

    $filters = [
        'q'               => (string)$this->input->get('q', true),
        'lead_status'     => (string)$this->input->get('lead_status', true),
        'lead_quality'    => (string)$this->input->get('lead_quality', true),
        'assigned_to'     => (int)$this->input->get('assigned_to', true),
        'exclude_deleted' => true,
    ];

    // =========================
    // MAIN DATA
    // =========================
    $leads  = $this->crmleads->get_all_with_meta($filters);
    $counts = $this->crmleads->count_by_status(true);

    // =========================
    // KPI DATA (NEW)
    // =========================
    $lead_kpi = [
        'total_leads' => array_sum($counts),

        'new_leads'   => $this->crmleads->count_this_month(),

        'qualified'   => (int)($counts['qualified'] ?? 0),

        'won'         => (int)($counts['contract_signed'] ?? 0),

        'lost'        => (int)($counts['lost'] ?? 0),

        'pipeline_value' => $this->crmleads->get_pipeline_value(),
    ];

    // =========================
    // RENDER
    // =========================
    $this->_render('CRM Leads', 'crm/leads/index', [
        'leads'    => $leads,
        'counts'   => $counts,
        'lead_kpi' => $lead_kpi, // 🔥 NEW
        'filters'  => $filters,
        'can'      => [
            'create' => $this->_can_create(),
            'edit'   => $this->_can_edit(),
            'delete' => $this->_can_delete(),
            'view'   => $this->_can_view(),
        ],
    ]);
}

    /* =========================================================
     * CREATE (modal form POST)
     * ======================================================= */

    public function store()
    {
        $this->_guard_manage_crm();
        if (!$this->_can_create()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $data = $this->_collect_lead_data_from_post();
        if ($data['practice_name'] === '') {
            set_alert('danger', 'Practice name is required.');
            redirect('crm/leads');
            return;
        }

        // Set defaults/system fields
        $this->_require_login();
        $actorId = $this->_user_id();
        
        $data['created_by'] = $actorId;
        $data['updated_by'] = $actorId;

        // UUID
        if (empty($data['lead_uuid'])) {
            $data['lead_uuid'] = $this->crmleads->generate_uuid_v4();
        }

        // Ensure UUID unique (rare collision, but safe)
        if (!$this->crmleads->is_uuid_unique($data['lead_uuid'])) {
            $data['lead_uuid'] = $this->crmleads->generate_uuid_v4();
        }

        $newId = $this->crmleads->insert($data);
        
        if ($newId > 0) {
            $this->_log_activity(
                'created',
                $newId,
                'Lead created: ' . $this->_lead_label($data),
                [
                    'practice_name' => $data['practice_name'] ?? null,
                    'contact_person'=> $data['contact_person'] ?? null,
                    'contact_email' => $data['contact_email'] ?? null,
                    'lead_status'   => $data['lead_status'] ?? null,
                    'lead_source'   => $data['lead_source'] ?? null,                    
                ]
            );
        
            set_alert('success', 'Lead created successfully.');
        } else {
            set_alert('danger', 'Failed to create lead.');
        }

        redirect('crm/leads');
    }

    /* =========================================================
     * VIEW (page)
     * ======================================================= */
    public function view($id)
    {
        $this->_guard_manage_crm();
    
        if (!$this->_can_view()) {
            $this->_crm_forbidden();
        }
    
        $id   = (int)$id;
        $lead = $this->crmleads->get_with_meta($id);
    
        if (!$lead || (int)($lead['is_deleted'] ?? 0) === 1) {
            show_404();
        }
    
        $proposals = $this->crmproposals->get_all_with_meta([
            'lead_id'         => $id,
            'exclude_deleted' => true,
        ]);
    
        $this->_render('View Lead', 'crm/leads/view_lead', [
            'lead'       => $lead,
            'activities' => $this->crmactivity->get_by_relation('lead', $id, 100),
            'files'      => $this->crmfiles->get_by_relation('lead', $id),
            'proposals'  => $proposals,
            'users'      => $this->crmusers->get_all_users(),
            'can'        => [
                'create' => $this->_can_create(),
                'edit'   => $this->_can_edit(),
                'delete' => $this->_can_delete(),
                'view'   => $this->_can_view(),
            ],
        ]);
    }

    /* =========================================================
     * EDIT (modal fetch JSON + POST update)
     * ======================================================= */

    public function ajax_get($id)
    {
        $this->_guard_manage_crm();
    
        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }
    
        $id   = (int)$id;
        $lead = $this->crmleads->get_with_meta($id);
    
        if (!$lead || (int)($lead['is_deleted'] ?? 0) === 1) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode([
                    'status'  => 'error',
                    'message' => 'Lead not found',
                ]));
            return;
        }
    
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'ok',
                'lead'   => $lead,
            ]));
    }

    public function update($id)
    {
        $this->_guard_manage_crm();
        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }
    
        $id = (int)$id;
        $oldLead = $this->crmleads->get_with_meta($id);
    
        if (!$oldLead || (int)($oldLead['is_deleted'] ?? 0) === 1) {
            show_404();
        }
    
        if (!$this->input->post()) {
            show_404();
        }
    
        $data = $this->_collect_lead_data_from_post();
        if ($data['practice_name'] === '') {
            set_alert('danger', 'Practice name is required.');
            redirect('crm/leads');
            return;
        }
    
        unset($data['lead_uuid']);
    
        $this->_require_login();
        $data['updated_by'] = $this->_user_id();
    
        $ok = $this->crmleads->update($id, $data);
    
        if ($ok) {
            $this->_log_activity(
                'updated',
                $id,
                'Lead updated: ' . $this->_lead_label($oldLead),
                [
                    'old_status'      => $oldLead['lead_status'] ?? null,
                    'new_status'      => $data['lead_status'] ?? ($oldLead['lead_status'] ?? null),
                    'old_assigned_to' => $oldLead['assigned_to'] ?? null,
                    'new_assigned_to' => $data['assigned_to'] ?? ($oldLead['assigned_to'] ?? null),
                ]
            );
        }
    
        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'Lead updated successfully.' : 'Failed to update lead.'
        );
    
        redirect('crm/leads/view/' . $id);
    }

public function update_needs($id)
{
    $this->_guard_manage_crm();

    if (!$this->_can_edit()) {
        $this->_crm_forbidden();
    }

    $id = (int)$id;
    $oldLead = $this->crmleads->get_with_meta($id);

    if (!$oldLead || (int)($oldLead['is_deleted'] ?? 0) === 1) {
        show_404();
    }

    if (!$this->input->post()) {
        show_404();
    }

    $this->_require_login();

    $data = [
        'practice_needs'      => trim((string)$this->input->post('practice_needs', true)),
        'pain_points'         => trim((string)$this->input->post('pain_points', true)),
        'decision_criteria'   => trim((string)$this->input->post('decision_criteria', true)),
        'key_decision_makers' => trim((string)$this->input->post('key_decision_makers', true)),
        'updated_by'          => $this->_user_id(),
    ];

    foreach (['practice_needs', 'pain_points', 'decision_criteria', 'key_decision_makers'] as $field) {
        if ($data[$field] === '') {
            $data[$field] = null;
        }
    }

    $ok = $this->crmleads->update($id, $data);

    if ($ok) {
        $changedFields = [];

        if (($oldLead['practice_needs'] ?? null) !== $data['practice_needs']) {
            $changedFields[] = 'practice_needs';
        }

        if (($oldLead['pain_points'] ?? null) !== $data['pain_points']) {
            $changedFields[] = 'pain_points';
        }

        if (($oldLead['decision_criteria'] ?? null) !== $data['decision_criteria']) {
            $changedFields[] = 'decision_criteria';
        }

        if (($oldLead['key_decision_makers'] ?? null) !== $data['key_decision_makers']) {
            $changedFields[] = 'key_decision_makers';
        }

        $this->_log_activity(
            'needs_updated',
            $id,
            'Lead needs and criteria updated: ' . $this->_lead_label($oldLead),
            [
                'changed_fields' => $changedFields,
            ]
        );
    }

    set_alert(
        $ok ? 'success' : 'danger',
        $ok ? 'Lead requirements updated successfully.' : 'Failed to update lead requirements.'
    );

    redirect('crm/leads/view/' . $id);
}


public function update_contact_info($id)
{
    $this->_guard_manage_crm();

    if (!$this->_can_edit()) {
        $this->_crm_forbidden();
    }

    $id = (int)$id;
    $oldLead = $this->crmleads->get_with_meta($id);

    if (!$oldLead || (int)($oldLead['is_deleted'] ?? 0) === 1) {
        show_404();
    }

    if (!$this->input->post()) {
        show_404();
    }

    $this->_require_login();

    $data = [
        'contact_person'            => trim((string)$this->input->post('contact_person', true)),
        'contact_email'             => trim((string)$this->input->post('contact_email', true)),
        'contact_phone'             => trim((string)$this->input->post('contact_phone', true)),
        'alternate_phone'           => trim((string)$this->input->post('alternate_phone', true)),
        'preferred_contact_method'  => trim((string)$this->input->post('preferred_contact_method', true)),
        'best_time_to_contact'      => trim((string)$this->input->post('best_time_to_contact', true)),
        'website'                   => trim((string)$this->input->post('website', true)),
        'address'                   => trim((string)$this->input->post('address', true)),
        'city'                      => trim((string)$this->input->post('city', true)),
        'state'                     => trim((string)$this->input->post('state', true)),
        'zip_code'                  => trim((string)$this->input->post('zip_code', true)),
        'country'                   => trim((string)$this->input->post('country', true)),
        'updated_by'                => $this->_user_id(),
    ];

    foreach ([
        'contact_person',
        'contact_email',
        'contact_phone',
        'alternate_phone',
        'preferred_contact_method',
        'best_time_to_contact',
        'website',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
    ] as $field) {
        if ($data[$field] === '') {
            $data[$field] = null;
        }
    }

    $ok = $this->crmleads->update($id, $data);

    if ($ok) {
        $changedFields = [];

        if (($oldLead['contact_person'] ?? null) !== $data['contact_person']) {
            $changedFields[] = 'contact_person';
        }

        if (($oldLead['contact_email'] ?? null) !== $data['contact_email']) {
            $changedFields[] = 'contact_email';
        }

        if (($oldLead['contact_phone'] ?? null) !== $data['contact_phone']) {
            $changedFields[] = 'contact_phone';
        }

        if (($oldLead['alternate_phone'] ?? null) !== $data['alternate_phone']) {
            $changedFields[] = 'alternate_phone';
        }

        if (($oldLead['preferred_contact_method'] ?? null) !== $data['preferred_contact_method']) {
            $changedFields[] = 'preferred_contact_method';
        }

        if (($oldLead['best_time_to_contact'] ?? null) !== $data['best_time_to_contact']) {
            $changedFields[] = 'best_time_to_contact';
        }

        if (($oldLead['website'] ?? null) !== $data['website']) {
            $changedFields[] = 'website';
        }

        if (($oldLead['address'] ?? null) !== $data['address']) {
            $changedFields[] = 'address';
        }

        if (($oldLead['city'] ?? null) !== $data['city']) {
            $changedFields[] = 'city';
        }

        if (($oldLead['state'] ?? null) !== $data['state']) {
            $changedFields[] = 'state';
        }

        if (($oldLead['zip_code'] ?? null) !== $data['zip_code']) {
            $changedFields[] = 'zip_code';
        }

        if (($oldLead['country'] ?? null) !== $data['country']) {
            $changedFields[] = 'country';
        }

        $this->_log_activity(
            'contact_info_updated',
            $id,
            'Lead contact information updated: ' . $this->_lead_label($oldLead),
            [
                'changed_fields' => $changedFields,
            ]
        );
    }

    set_alert(
        $ok ? 'success' : 'danger',
        $ok ? 'Lead contact information updated successfully.' : 'Failed to update lead contact information.'
    );

    redirect('crm/leads/view/' . $id);
}

    /* =========================================================
     * DELETE (soft delete)
     * ======================================================= */

    public function delete($id)
    {
        $this->_guard_manage_crm();
        if (!$this->_can_delete()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $id = (int)$id;
        $lead = $this->crmleads->get($id);
        if (!$lead) {
            show_404();
        }

        $this->_require_login();
        $ok = $this->crmleads->soft_delete($id, $this->_user_id());
        
        if ($ok) {
            $this->_log_activity(
                'deleted',
                $id,
                'Lead deleted: ' . $this->_lead_label($lead),
                [
                    'lead_status' => $lead['lead_status'] ?? null,
                    'deleted_by'  => $this->_user_id(),
                ]
            );
        }
        
        set_alert($ok ? 'success' : 'danger', $ok ? 'Lead deleted successfully.' : 'Failed to delete lead.');
        redirect('crm/leads');
    }

    /* =========================================================
     * IMPORT (CSV)
     * ======================================================= */

    public function import()
    {
        $this->_guard_manage_crm();
        if (!$this->_can_create()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        if (empty($_FILES['csv_file']['name'])) {
            set_alert('danger', 'Please select a CSV file.');
            redirect('crm/leads');
            return;
        }

        $config = [
            'upload_path'   => FCPATH . 'uploads/crm/',
            'allowed_types' => 'csv',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        ];

        if (!is_dir($config['upload_path'])) {
            @mkdir($config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('csv_file')) {
            set_alert('danger', strip_tags((string)$this->upload->display_errors()));
            redirect('crm/leads');
            return;
        }

        $fileData = $this->upload->data();
        $path     = $fileData['full_path'];

        $batchId  = 'LEADS-' . date('YmdHis') . '-' . mt_rand(100, 999);

        $result = $this->crmleads->import_from_csv($path, [
            'import_batch_id'    => $batchId,
            'import_source_file' => $fileData['file_name'],
            'actor_id'           => (int)get_staff_user_id(),
        ]);

        $this->_log_activity(
            'imported',
            0,
            'Lead import executed',
            [
                'batch_id'  => $batchId,
                'inserted'  => $result['inserted'] ?? 0,
                'updated'   => $result['updated'] ?? 0,
                'skipped'   => $result['skipped'] ?? 0,
                'file_name' => $fileData['file_name'] ?? null,
            ],
            'lead_import'
        );

        if (!empty($result['errors'])) {
            set_alert('danger', 'Import completed with errors: ' . implode(' | ', array_slice($result['errors'], 0, 3)));
        } else {
            set_alert('success', "Import completed. Inserted: {$result['inserted']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}.");
        }

        redirect('crm/leads');
    }

    /* =========================================================
     * DATA COLLECTOR
     * ======================================================= */
    protected function _collect_lead_data_from_post(): array
    {
        $fields = [
            'practice_name',
            'contact_person',
            'contact_email',
            'contact_phone',
            'alternate_phone',
            'website',
            'practice_type',
            'specialty',
            'patient_volume_per_month',
            'current_billing_provider',
            'current_emr_system',
            'monthly_claim_volume',
            'current_billing_method',
            'monthly_collections',
            'estimated_monthly_revenue',
            'estimated_setup_fee',
            'estimated_annual_value',
            'forecast_probability',
            'forecast_category',
            'address',
            'city',
            'state',
            'zip_code',
            'country',
            'lead_status',
            'lead_quality',
            'lead_source',
            'assigned_to',
            'initial_contact_date',
            'last_contact_date',
            'next_followup_date',
            'demo_date',
            'proposal_date',
            'expected_close_date',
            'actual_close_date',
            'loss_reason',
            'practice_needs',
            'pain_points',
            'decision_criteria',
            'key_decision_makers',
            'internal_notes',
            'preferred_contact_method',
            'best_time_to_contact',
            'referred_by',
            'referral_type',
        ];
    
        $data = [];
    
        foreach ($fields as $field) {
            $value = $this->input->post($field, true);
    
            if (is_string($value)) {
                $value = trim($value);
            }
    
            if ($value === '') {
                $value = null;
            }
    
            $data[$field] = $value;
        }
    
        $data['practice_name'] = trim((string)($data['practice_name'] ?? ''));
    
        $intFields = [
            'patient_volume_per_month',
            'monthly_claim_volume',
            'assigned_to',
            'forecast_probability',
        ];
    
        foreach ($intFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = is_numeric($data[$field]) ? (int)$data[$field] : null;
            }
        }
    
        $decimalFields = [
            'monthly_collections',
            'estimated_monthly_revenue',
            'estimated_setup_fee',
            'estimated_annual_value',
        ];
    
        foreach ($decimalFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = is_numeric($data[$field]) ? $data[$field] : null;
            }
        }
    
        return $data;
    }

    public function forecast()
    {
        $this->_guard_manage_crm();
    
        if (!$this->_can_view()) {
            $this->_crm_forbidden();
        }
    
        $filters = [
            'date_from'         => trim((string)$this->input->get('date_from', true)),
            'date_to'           => trim((string)$this->input->get('date_to', true)),
            'assigned_to'       => (int)$this->input->get('assigned_to', true),
            'forecast_category' => trim((string)$this->input->get('forecast_category', true)),
            'exclude_deleted'   => true,
        ];
    
        $this->_render('Sales Forecast', 'crm/leads/forecast', [
            'summary'           => $this->crmleads->get_forecast_summary($filters),
            'forecast_by_stage' => $this->crmleads->get_forecast_by_stage($filters),
            'forecast_by_owner' => $this->crmleads->get_forecast_by_owner($filters),
            'forecast_leads'    => $this->crmleads->get_forecast_leads($filters),
            'filters'           => $filters,
        ]);
    }


public function assign($id)
{
    $this->_guard_manage_crm();

    if (!$this->_can_edit()) {
        $this->_crm_forbidden();
    }

    if (!$this->input->post()) {
        show_404();
    }

    $id   = (int)$id;
    $lead = $this->crmleads->get($id);

    if (!$lead || (int)($lead['is_deleted'] ?? 0) === 1) {
        show_404();
    }

    $this->_require_login();

    $oldAssignedTo   = $lead['assigned_to'] ?? null;
    $assignedTo = (int)$this->input->post('assigned_to', true);

    if ($assignedTo < 0) {
        set_alert('danger', 'Invalid assignee selected.');
        redirect('crm/leads/view/' . $id);
        return;
    }

    $ok = $this->crmleads->assign(
        $id,
        $assignedTo,
        $this->_user_id(),
    );

    if ($ok) {
        $this->_log_activity(
            'assigned',
            $id,
            'Lead assignment updated: ' . $this->_lead_label($lead),
            [
                'old_assigned_to'   => $oldAssignedTo,
                'new_assigned_to'   => $assignedTo ?: null,
            ]
        );
    }

    set_alert(
        $ok ? 'success' : 'danger',
        $ok ? 'Lead assignment updated successfully.' : 'Failed to update lead assignment.'
    );

    redirect('crm/leads/view/' . $id);
}

// =====================================================================
// CONTROLLER METHOD — crm/leads/change_status/$id
// =====================================================================

public function change_status($id)
{
    $this->_guard_manage_crm();
    if (!$this->_can_edit()) {
        $this->_crm_forbidden();
    }
    if (!$this->input->post()) {
        show_404();
    }

    $id   = (int)$id;
    $lead = $this->crmleads->get($id);
    if (!$lead || (int)($lead['is_deleted'] ?? 0) === 1) {
        show_404();
    }

    $this->_require_login();

    $oldStatus = trim((string)($lead['lead_status'] ?? ''));

    // ── Validate status ──────────────────────────────────────────────
    $status = trim((string)$this->input->post('lead_status', true));
    $allowedStatuses = [
        'new', 'contacted', 'qualified', 'proposal_sent', 'negotiation',
        'demo_scheduled', 'demo_completed', 'contract_sent',
        'contract_signed', 'lost', 'disqualified',
    ];
    if (!in_array($status, $allowedStatuses, true)) {
        set_alert('danger', 'Invalid lead status selected.');
        redirect('crm/leads/view/' . $id);
        return;
    }

    // ── Validate loss reason when required ───────────────────────────
    $lossReason = trim((string)$this->input->post('loss_reason', true));
    if (in_array($status, ['lost', 'disqualified'], true) && $lossReason === '') {
        set_alert('danger', 'Please provide a loss reason when marking a lead as Lost or Disqualified.');
        redirect('crm/leads/view/' . $id);
        return;
    }

    // ── Collect additional fields from the modal ─────────────────────
    $allowedQualities = ['hot', 'warm', 'cold'];
    $leadQuality      = trim((string)$this->input->post('lead_quality', true));
    $leadQuality      = in_array($leadQuality, $allowedQualities, true) ? $leadQuality : null;

    $forecastProbability = $this->input->post('forecast_probability', true);
    $forecastProbability = ($forecastProbability !== '' && $forecastProbability !== null)
        ? max(0, min(100, (int)$forecastProbability))
        : null;

    $allowedCategories    = ['commit', 'best_case', 'pipeline', 'omitted', ''];
    $forecastCategory     = trim((string)$this->input->post('forecast_category', true));
    $forecastCategory     = in_array($forecastCategory, $allowedCategories, true) ? $forecastCategory : null;

    // ── Build extra data array to pass to model ──────────────────────
    $extra = [];

    if ($leadQuality !== null) {
        $extra['lead_quality'] = $leadQuality;
    }
    if ($forecastProbability !== null) {
        $extra['forecast_probability'] = $forecastProbability;
    }
    if ($forecastCategory !== null) {
        $extra['forecast_category'] = $forecastCategory !== '' ? $forecastCategory : null;
    }
    if (in_array($status, ['lost', 'disqualified'], true) && $lossReason !== '') {
        $extra['loss_reason'] = $lossReason;
    }

    // ── Call model ───────────────────────────────────────────────────
    $ok = $this->crmleads->change_status($id, $status, $this->_user_id(), $extra);

    // ── Log activity ─────────────────────────────────────────────────
    if ($ok) {
        $meta = [
            'old_status' => $oldStatus,
            'new_status' => $status,
        ];

        if ($leadQuality !== null)       $meta['lead_quality']        = $leadQuality;
        if ($forecastProbability !== null) $meta['forecast_probability'] = $forecastProbability;
        if (!empty($forecastCategory))   $meta['forecast_category']   = $forecastCategory;
        if (!empty($lossReason))         $meta['loss_reason']          = $lossReason;

        $this->_log_activity(
            'status_changed',
            $id,
            'Lead status changed from "' . $oldStatus . '" to "' . $status . '" for ' . $this->_lead_label($lead),
            $meta
        );
    }

    set_alert(
        $ok ? 'success' : 'danger',
        $ok ? 'Lead status updated successfully.' : 'Failed to update lead status.'
    );
    redirect('crm/leads/view/' . $id);
}

public function verify($id)
{
    $this->_guard_manage_crm();

    if (!$this->_can_edit()) {
        $this->_crm_forbidden();
    }

    if (strtoupper($this->input->method()) !== 'POST') {
        show_404();
    }

    $id   = (int)$id;
    $lead = $this->crmleads->get($id);

    if (!$lead || (int)($lead['is_deleted'] ?? 0) === 1) {
        show_404();
    }

    $this->_require_login();

    $ok = $this->crmleads->mark_verified($id, $this->_user_id());

    if ($ok) {
        $this->_log_activity(
            'verified',
            $id,
            'Lead marked as verified: ' . $this->_lead_label($lead),
            [
                'verified_by' => $this->_user_id(),
            ]
        );
    }
    
    set_alert(
        $ok ? 'success' : 'danger',
        $ok ? 'Lead marked as verified successfully.' : 'Failed to verify lead.'
    );

    redirect('crm/leads/view/' . $id);
}

public function unverify($id)
{
    $this->_guard_manage_crm();

    if (!$this->_can_edit()) {
        $this->_crm_forbidden();
    }

    if (strtoupper($this->input->method()) !== 'POST') {
        show_404();
    }

    $id   = (int)$id;
    $lead = $this->crmleads->get($id);

    if (!$lead || (int)($lead['is_deleted'] ?? 0) === 1) {
        show_404();
    }

    $this->_require_login();

    $ok = $this->crmleads->mark_unverified($id, $this->_user_id());

    if ($ok) {
        $this->_log_activity(
            'unverified',
            $id,
            'Lead marked as unverified: ' . $this->_lead_label($lead),
            [
                'updated_by' => $this->_user_id(),
            ]
        );
    }

    set_alert(
        $ok ? 'success' : 'danger',
        $ok ? 'Lead marked as unverified successfully.' : 'Failed to update verification.'
    );

    redirect('crm/leads/view/' . $id);
}

public function restore($id)
{
    $this->_guard_manage_crm();

    if (!$this->_can_delete()) {
        $this->_crm_forbidden();
    }

    if (!$this->input->post()) {
        show_404();
    }

    $id   = (int)$id;
    $lead = $this->crmleads->get($id);

    if (!$lead) {
        show_404();
    }

    $this->_require_login();

    $ok = $this->crmleads->restore($id);

    if ($ok) {
        $this->crmleads->update($id, [
            'updated_by' => $this->_user_id(),
        ]);
    
        $this->_log_activity(
            'restored',
            $id,
            'Lead restored: ' . $this->_lead_label($lead),
            [
                'lead_status' => $lead['lead_status'] ?? null,
            ]
        );
    }

    set_alert(
        $ok ? 'success' : 'danger',
        $ok ? 'Lead restored successfully.' : 'Failed to restore lead.'
    );

    redirect('crm/leads/view/' . $id);
}

public function update_forecast($id)
{
    $this->_guard_manage_crm();

    if (!$this->_can_edit()) {
        $this->_crm_forbidden();
    }

    if (!$this->input->post()) {
        show_404();
    }

    $id   = (int)$id;
    $lead = $this->crmleads->get($id);

    if (!$lead || (int)($lead['is_deleted'] ?? 0) === 1) {
        show_404();
    }

    $this->_require_login();

    $oldLead = $this->crmleads->get($id);
    
    if (!$oldLead || (int)($oldLead['is_deleted'] ?? 0) === 1) {
        show_404();
    }    
    
    $data = [
        'estimated_monthly_revenue' => $this->input->post('estimated_monthly_revenue', true),
        'estimated_setup_fee'       => $this->input->post('estimated_setup_fee', true),
        'estimated_annual_value'    => $this->input->post('estimated_annual_value', true),
        'forecast_probability'      => $this->input->post('forecast_probability', true),
        'forecast_category'         => $this->input->post('forecast_category', true),
        'expected_close_date'       => $this->input->post('expected_close_date', true),
        'updated_by'                => $this->_user_id(),
    ];

    foreach (['estimated_monthly_revenue', 'estimated_setup_fee', 'estimated_annual_value'] as $field) {
        $data[$field] = ($data[$field] !== null && $data[$field] !== '' && is_numeric($data[$field]))
            ? $data[$field]
            : null;
    }

    $data['forecast_probability'] = ($data['forecast_probability'] !== null && $data['forecast_probability'] !== '' && is_numeric($data['forecast_probability']))
        ? (int)$data['forecast_probability']
        : null;

    $allowedCategories = ['commit', 'best_case', 'pipeline', 'omitted'];
    $category = trim((string)($data['forecast_category'] ?? ''));

    if ($category !== '' && !in_array($category, $allowedCategories, true)) {
        set_alert('danger', 'Invalid forecast category selected.');
        redirect('crm/leads/view/' . $id);
        return;
    }

    $data['forecast_category'] = $category !== '' ? $category : null;

    $expectedClose = trim((string)($data['expected_close_date'] ?? ''));
    $data['expected_close_date'] = $expectedClose !== '' ? $expectedClose : null;

    $ok = $this->crmleads->update_forecast_fields($id, $data);
    
    if ($ok) {
        $this->_log_activity(
            'forecast_updated',
            $id,
            'Lead forecast updated: ' . $this->_lead_label($lead),
            [
                'old_estimated_monthly_revenue' => $oldLead['estimated_monthly_revenue'] ?? null,
                'new_estimated_monthly_revenue' => $data['estimated_monthly_revenue'] ?? null,
                'old_forecast_probability'      => $oldLead['forecast_probability'] ?? null,
                'new_forecast_probability'      => $data['forecast_probability'] ?? null,
                'old_forecast_category'         => $oldLead['forecast_category'] ?? null,
                'new_forecast_category'         => $data['forecast_category'] ?? null,
                'old_expected_close_date'       => $oldLead['expected_close_date'] ?? null,
                'new_expected_close_date'       => $data['expected_close_date'] ?? null,
            ]
        );
    }

    set_alert(
        $ok ? 'success' : 'danger',
        $ok ? 'Forecast updated successfully.' : 'Failed to update forecast.'
    );

    redirect('crm/leads/view/' . $id);
}



}