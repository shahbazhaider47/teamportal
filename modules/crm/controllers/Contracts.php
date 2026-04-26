<?php defined('BASEPATH') or exit('No direct script access allowed');

class Contracts extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['url', 'form', 'crm']);
        $this->load->library(['form_validation']);

        $this->load->model('crm/Contracts_model',      'contracts');
        $this->load->model('crm/Crm_activity_model',   'crmactivity');
        $this->load->model('crm/Crm_files_model',      'crmfiles');
        $this->load->model('crm/Crmclients_model',            'crm');
    }

    /* =========================================================
     * INTERNAL HELPERS
     * ======================================================= */

    protected function _render(string $title, string $subview, array $data = []): void
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

    protected function _crm_forbidden(): void
    {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        echo $html;
        exit;
    }

    /* ── Permission guards ── */

    protected function _guard_view(): void
    {
        if (
            !staff_can('view', 'crm') &&
            !staff_can('view_global', 'crm') &&
            !staff_can('view_own', 'crm')
        ) {
            $this->_crm_forbidden();
        }
    }

    protected function _can_create(): bool
    {
        return staff_can('contract_create', 'crm') || staff_can('client_create', 'crm');
    }

    protected function _can_edit(): bool
    {
        return staff_can('contract_edit', 'crm') || staff_can('client_edit', 'crm');
    }

    protected function _can_delete(): bool
    {
        return staff_can('contract_delete', 'crm') || staff_can('client_delete', 'crm');
    }

    protected function _can_view(): bool
    {
        return staff_can('contract_view', 'crm')
            || staff_can('client_view', 'crm')
            || staff_can('view', 'crm')
            || staff_can('view_global', 'crm');
    }

    /** Convenience: build $can[] array once and pass to every view. */
    protected function _permissions(): array
    {
        return [
            'create' => $this->_can_create(),
            'edit'   => $this->_can_edit(),
            'delete' => $this->_can_delete(),
            'view'   => $this->_can_view(),
        ];
    }

    protected function _log(string $action, int $relId, string $description, array $meta = []): void
    {
        $this->crmactivity->log([
            'user_id'     => $this->_user_id(),
            'rel_type'    => 'contract',
            'rel_id'      => $relId,
            'action'      => $action,
            'description' => $description,
            'metadata'    => $meta,
            'ip_address'  => $this->input->ip_address(),
        ]);
    }

    /* =========================================================
     * CONTRACT LIST
     * ======================================================= */

    public function index(): void
    {
        $this->_require_login();
        $this->_guard_view();

        if (!$this->_can_view()) {
            $this->_crm_forbidden();
        }
        
        $this->_render('Contracts', 'crm/contracts/index', [
            'page_title'   => 'Contracts',
            'contracts'    => $this->contracts->get_all(),
            'kpi'          => $this->contracts->count_by_status(),
            'expiring'     => $this->contracts->get_expiring(60),
            'can'          => $this->_permissions(),
        ]);
    }

    /* =========================================================
     * CREATE CONTRACT
     * ======================================================= */

    public function create(): void
    {
        $this->_require_login();
        $this->_guard_view();

        if (!$this->_can_create()) {
            $this->_crm_forbidden();
        }

        /* Pre-fill client_id from query string (called from client profile) */
        $preClientId = (int)$this->input->get('client_id');

        if ($this->input->post()) {

            $this->_set_create_rules();

            if ($this->form_validation->run() === false) {
                /* Re-render with errors */
                $this->_render_create_form([
                    'clients'      => $this->crm->get_all_with_group(),
                    'pre_client_id'=> $preClientId,
                    'contract_code' => $this->contracts->generate_contract_code(),
                ]);
                return;
            }

            $data                    = $this->_collect();
            $data['contract_code'] = $this->contracts->generate_contract_code();
            $data['created_by']      = $this->_user_id();
            $data['updated_by']      = $this->_user_id();

            $id = $this->contracts->insert($data);

            if ($id) {
                $this->_log('created', $id, 'Contract created: ' . ($data['title'] ?? $data['contract_code']));
                set_alert('success', 'Contract <strong>' . html_escape($data['contract_code']) . '</strong> created successfully.');
                redirect('crm/contracts/view/' . $id);
                return;
            }

            set_alert('danger', 'Failed to save the contract. Please try again.');
        }

        $this->_render_create_form([
            'clients'         => $this->crm->get_all_with_group(),
            'pre_client_id'   => $preClientId,
            'contract_code' => $this->contracts->generate_contract_code(),
        ]);
    }

    /* =========================================================
     * VIEW CONTRACT
     * ======================================================= */

    public function view(int $id): void
    {
        $this->_require_login();
        $this->_guard_view();

        if (!$this->_can_view()) {
            $this->_crm_forbidden();
        }

        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        $this->_render('Contract: ' . html_escape($contract['contract_code']), 'crm/contracts/view', [
            'page_title' => 'Contract: ' . html_escape($contract['contract_code']),
            'contract'   => $contract,
            'files'      => $this->crmfiles->get_by_relation('contract', $id),
            'activities' => $this->crmactivity->get_by_relation('contract', $id),
            'can'        => $this->_permissions(),
        ]);
    }

    /* =========================================================
     * EDIT CONTRACT
     * ======================================================= */

    public function edit(int $id): void
    {
        $this->_require_login();
        $this->_guard_view();

        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }

        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        /* Block editing of terminated / cancelled contracts */
        if (in_array($contract['status'], ['terminated', 'cancelled'])) {
            set_alert('warning', 'Terminated or cancelled contracts cannot be edited. Create an amendment instead.');
            redirect('crm/contracts/view/' . $id);
            return;
        }

        if ($this->input->post()) {

            $this->_set_edit_rules();

            if ($this->form_validation->run() === false) {
                $this->_render_edit_form($contract, $id);
                return;
            }

            $data               = $this->_collect();
            $data['updated_by'] = $this->_user_id();

            $ok = $this->contracts->update($id, $data);

            if ($ok) {
                $this->_log('updated', $id, 'Contract updated: ' . html_escape($contract['contract_code']));
                set_alert('success', 'Contract updated successfully.');
                redirect('crm/contracts/view/' . $id);
                return;
            }

            set_alert('danger', 'Failed to update contract. Please try again.');
        }

        $this->_render_edit_form($contract, $id);
    }

    /* =========================================================
     * TERMINATE CONTRACT  (POST-only)
     * ======================================================= */

    public function terminate(int $id): void
    {
        $this->_require_login();

        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        if ($contract['status'] === 'terminated') {
            set_alert('warning', 'Contract is already terminated.');
            redirect('crm/contracts/view/' . $id);
            return;
        }

        $terminatedDate = $this->input->post('terminated_date', true);
        $reason         = trim($this->input->post('termination_reason', true) ?? '');
        $initiatedBy    = $this->input->post('termination_initiated_by', true);

        if (empty($terminatedDate)) {
            set_alert('danger', 'Termination date is required.');
            redirect('crm/contracts/view/' . $id);
            return;
        }

        $data = [
            'status'                     => 'terminated',
            'terminated_date'            => $terminatedDate,
            'termination_reason'         => $reason ?: null,
            'termination_initiated_by'   => in_array($initiatedBy, ['client', 'rcm', 'mutual']) ? $initiatedBy : 'mutual',
            'updated_by'                 => $this->_user_id(),
        ];

        $ok = $this->contracts->update($id, $data);

        if ($ok) {
            $this->_log('terminated', $id,
                'Contract terminated: ' . html_escape($contract['contract_code']),
                ['reason' => $reason, 'initiated_by' => $data['termination_initiated_by']]
            );
        }

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'Contract terminated successfully.' : 'Failed to terminate contract.'
        );

        redirect('crm/contracts/view/' . $id);
    }

    /* =========================================================
     * RENEW CONTRACT  (POST-only)
     * Creates a new contract row linked to the original.
     * ======================================================= */

    public function renew(int $id): void
    {
        $this->_require_login();

        if (!$this->_can_create()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        if ($contract['status'] !== 'active') {
            set_alert('warning', 'Only active contracts can be renewed.');
            redirect('crm/contracts/view/' . $id);
            return;
        }

        /* Determine version number */
        $versions       = $this->contracts->get_versions($id);
        $nextVersion    = count($versions) + 1;
        $newStartDate   = $this->input->post('new_start_date', true) ?: $contract['end_date'];
        $termMonths     = (int)($contract['renewal_term_months'] ?? 12);
        $newEndDate     = date('Y-m-d', strtotime($newStartDate . ' +' . $termMonths . ' months'));

        $newData = [
            'client_id'             => $contract['client_id'],
            'contract_version'      => $nextVersion,
            'contract_type'         => 'renewal',
            'contract_code'         => $this->contracts->generate_contract_code(),
            'title'                 => $contract['title'] . ' — Renewal v' . $nextVersion,
            'description'           => $contract['description'],
            'start_date'            => $newStartDate,
            'end_date'              => $newEndDate,
            'auto_renew'            => $contract['auto_renew'],
            'renewal_notice_days'   => $contract['renewal_notice_days'],
            'renewal_term_months'   => $contract['renewal_term_months'],
            'termination_notice_days' => $contract['termination_notice_days'],
            'billing_model'         => $contract['billing_model'],
            'rate_value'            => $contract['rate_value'],
            'rate_currency'         => $contract['rate_currency'],
            'invoice_frequency'     => $contract['invoice_frequency'],
            'payment_terms_days'    => $contract['payment_terms_days'],
            'minimum_monthly_fee'   => $contract['minimum_monthly_fee'],
            'services_included'     => $contract['services_included'],
            'services_excluded'     => $contract['services_excluded'],
            'sla_terms'             => $contract['sla_terms'],
            'status'                => 'draft',
            'created_by'            => $this->_user_id(),
            'updated_by'            => $this->_user_id(),
        ];

        $newId = $this->contracts->insert($newData);

        if ($newId) {
            /* Update renewal tracking on original */
            $this->contracts->update($id, [
                'last_renewed_date' => date('Y-m-d'),
                'renewal_count'     => (int)($contract['renewal_count'] ?? 0) + 1,
                'updated_by'        => $this->_user_id(),
            ]);

            $this->_log('renewed', $id,
                'Contract renewed — new contract: ' . $newData['contract_code'],
                ['new_contract_id' => $newId]
            );

            set_alert('success', 'Renewal contract <strong>' . html_escape($newData['contract_code']) . '</strong> created. Review and activate it.');
            redirect('crm/contracts/edit/' . $newId);
            return;
        }

        set_alert('danger', 'Failed to create renewal contract.');
        redirect('crm/contracts/view/' . $id);
    }

    /* =========================================================
     * ACTIVATE CONTRACT  (POST-only)
     * ======================================================= */

    public function activate(int $id): void
    {
        $this->_require_login();

        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        if (!in_array($contract['status'], ['draft', 'pending_signature'])) {
            set_alert('warning', 'Only draft or pending contracts can be activated.');
            redirect('crm/contracts/view/' . $id);
            return;
        }

        $signedDate = $this->input->post('signed_date', true) ?: date('Y-m-d');

        $ok = $this->contracts->update($id, [
            'status'      => 'active',
            'signed_date' => $signedDate,
            'updated_by'  => $this->_user_id(),
        ]);

        if ($ok) {
            $this->_log('activated', $id, 'Contract activated: ' . html_escape($contract['contract_code']));
        }

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'Contract is now active.' : 'Failed to activate contract.'
        );

        redirect('crm/contracts/view/' . $id);
    }

    /* =========================================================
     * DELETE (SOFT)  (POST-only)
     * ======================================================= */

    public function delete(int $id): void
    {
        $this->_require_login();

        if (!$this->_can_delete()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        /* Never hard-delete active contracts */
        if ($contract['status'] === 'active') {
            set_alert('danger', 'Active contracts cannot be deleted. Terminate the contract first.');
            redirect('crm/contracts/view/' . $id);
            return;
        }

        $ok = $this->contracts->soft_delete($id, $this->_user_id());

        if ($ok) {
            $this->_log('deleted', $id, 'Contract soft-deleted: ' . html_escape($contract['contract_code']));
        }

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'Contract deleted.' : 'Failed to delete contract.'
        );

        redirect('crm/contracts');
    }

    /* =========================================================
     * RESTORE  (POST-only)
     * ======================================================= */

    public function restore(int $id): void
    {
        $this->_require_login();

        if (!$this->_can_delete()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        /* Fetch including deleted */
        $contract = $this->db
            ->where('id', $id)
            ->get('crm_contracts')
            ->row_array();

        if (!$contract) {
            show_404();
        }

        $ok = $this->contracts->restore($id);

        if ($ok) {
            $this->_log('restored', $id, 'Contract restored: ' . html_escape($contract['contract_code']));
        }

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'Contract restored successfully.' : 'Failed to restore contract.'
        );

        redirect('crm/contracts');
    }

    /* =========================================================
     * AJAX: QUICK STATUS UPDATE  (POST-only, returns JSON)
     * ======================================================= */

    public function ajax_status(int $id): void
    {
        $this->_require_login();

        if (!$this->_can_edit() || !$this->input->is_ajax_request()) {
            $this->output->set_status_header(403)
                         ->set_content_type('application/json')
                         ->set_output(json_encode(['success' => false, 'message' => 'Forbidden']));
            return;
        }

        $status   = $this->input->post('status', true);
        $allowed  = ['draft', 'pending_signature', 'active', 'expired', 'terminated', 'cancelled'];

        if (!in_array($status, $allowed)) {
            $this->output->set_status_header(422)
                         ->set_content_type('application/json')
                         ->set_output(json_encode(['success' => false, 'message' => 'Invalid status']));
            return;
        }

        $contract = $this->contracts->get($id);

        if (!$contract) {
            $this->output->set_status_header(404)
                         ->set_content_type('application/json')
                         ->set_output(json_encode(['success' => false, 'message' => 'Not found']));
            return;
        }

        $ok = $this->contracts->update($id, [
            'status'     => $status,
            'updated_by' => $this->_user_id(),
        ]);

        if ($ok) {
            $this->_log('status_changed', $id,
                'Status changed from ' . $contract['status'] . ' to ' . $status
            );
        }

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode([
                         'success' => $ok,
                         'message' => $ok ? 'Status updated.' : 'Failed to update status.',
                     ]));
    }

    /* =========================================================
     * PRIVATE: RENDER HELPERS
     * ======================================================= */

    private function _render_create_form(array $extra = []): void
    {
        $this->_render('Create Contract', 'crm/contracts/create', array_merge([
            'page_title' => 'New Contract',
            'can'        => $this->_permissions(),
        ], $extra));
    }

    private function _render_edit_form(array $contract, int $id): void
    {
        $this->_render(
            'Edit Contract: ' . html_escape($contract['contract_code']),
            'crm/contracts/edit',
            [
                'page_title' => 'Edit Contract',
                'contract'   => $contract,
                'clients'    => $this->crm->get_all_with_group(),
                'can'        => $this->_permissions(),
            ]
        );
    }

    /* =========================================================
     * PRIVATE: VALIDATION RULES
     * ======================================================= */

    private function _set_create_rules(): void
    {
        $this->form_validation->set_rules('client_id',         'Client',           'required|integer');
        $this->form_validation->set_rules('title',             'Contract Title',   'required|max_length[200]');
        $this->form_validation->set_rules('contract_type',     'Contract Type',    'required');
        $this->form_validation->set_rules('status',            'Status',           'required');
        $this->form_validation->set_rules('start_date',        'Start Date',       'required|valid_date');
        $this->form_validation->set_rules('billing_model',     'Billing Model',    'required');
        $this->form_validation->set_rules('invoice_frequency', 'Invoice Frequency','required');
    }

    private function _set_edit_rules(): void
    {
        $this->form_validation->set_rules('title',             'Contract Title',   'required|max_length[200]');
        $this->form_validation->set_rules('status',            'Status',           'required');
        $this->form_validation->set_rules('start_date',        'Start Date',       'required|valid_date');
        $this->form_validation->set_rules('billing_model',     'Billing Model',    'required');
        $this->form_validation->set_rules('invoice_frequency', 'Invoice Frequency','required');
    }

    /* =========================================================
     * PRIVATE: POST DATA COLLECTOR
     * ======================================================= */

    private function _collect(): array
    {
        $fields = [
            /* identity */
            'client_id', 'contract_version', 'contract_type',
            /* terms */
            'title', 'description', 'start_date', 'end_date',
            'auto_renew', 'renewal_notice_days', 'renewal_term_months', 'termination_notice_days',
            /* billing */
            'billing_model', 'rate_value', 'rate_currency',
            'invoice_frequency', 'payment_terms_days', 'minimum_monthly_fee',
            /* scope */
            'services_included', 'services_excluded', 'specialties_covered',
            'locations_covered', 'sla_terms',
            /* signing */
            'status', 'signed_date', 'signed_by_client', 'signed_by_rcm',
            'signature_method', 'external_ref',
            /* renewal/termination */
            'next_review_date',
            /* misc */
            'internal_notes',
        ];

        $data = [];
        foreach ($fields as $field) {
            $value = $this->input->post($field, true);
            if (is_string($value)) {
                $value = trim($value);
            }
            $data[$field] = ($value === '' || $value === null) ? null : $value;
        }

        /* Sanitise types */
        $data['client_id']               = $data['client_id']               ? (int)$data['client_id']               : null;
        $data['contract_version']        = $data['contract_version']        ? (int)$data['contract_version']        : 1;
        $data['auto_renew']              = $data['auto_renew']              ? (int)(bool)$data['auto_renew']        : 0;
        $data['renewal_notice_days']     = $data['renewal_notice_days']     ? (int)$data['renewal_notice_days']     : null;
        $data['renewal_term_months']     = $data['renewal_term_months']     ? (int)$data['renewal_term_months']     : null;
        $data['termination_notice_days'] = $data['termination_notice_days'] ? (int)$data['termination_notice_days'] : null;
        $data['payment_terms_days']      = $data['payment_terms_days']      ? (int)$data['payment_terms_days']      : null;
        $data['rate_value']              = $data['rate_value']              ? (float)$data['rate_value']            : null;
        $data['minimum_monthly_fee']     = $data['minimum_monthly_fee']     ? (float)$data['minimum_monthly_fee']   : null;

        return $data;
    }
}