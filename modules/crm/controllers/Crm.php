<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crm extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['url', 'form', 'crm']);
        $this->load->library('form_validation');
        $this->load->model('crm/Crmclients_model', 'crmclients');
        $this->load->model('Activity_log_model');
        $this->load->model('User_model', 'crmusers');
        $this->load->model('crm/Crmnotes_model', 'crmnotes');
    }

    protected function log_activity(string $action)
    {
        $this->Activity_log_model->add([
            'user_id'    => $this->session->userdata('user_id'),
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /* =========================================================
     * INTERNAL HELPERS
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

    protected function _guard_manage_crm()
    {
        if (!staff_can('view', 'crm')) {
            $this->_crm_forbidden();
        }
    
        if (staff_can('view_global', 'crm')) {
            return;
        }
    
        if (staff_can('view_own', 'crm')) {
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
    
    /* =========================================================
     * DASHBOARD
     * ======================================================= */

    public function index()
    {
        $this->_guard_manage_crm();

        $view = [
            'stats' => [
                'total_invoices' => 0,
                'total_payments' => 0,
                'total_expenses' => 0,
                'total_clients'  => $this->crmclients->count_active_clients(),
                'balance'        => 0.00,
            ],
            'currency' => company_setting('finance_base_currency') ?? 'USD',
        ];

        $this->_render('CRM Overview', 'crm/overview', $view);
    }

    /* =========================================================
     * CLIENTS
     * ======================================================= */

    public function clients()
    {
        $this->_guard_manage_crm();
    
        $view = [
            'clients' => $this->crmclients->get_all_with_group(),
            'kpi'     => $this->crmclients->get_clients_kpi()
        ];
    
        $this->_render('CRM Clients', 'crm/clients/index', $view);
    }

    public function client_view($id)
    {
        $this->_guard_manage_crm();
    
        $id = (int)$id;
        $client = $this->crmclients->get_with_group($id);
        if (!$client) {
            show_404();
        }
    
        $notes = $this->crmnotes->get_by_rel('client', $id);
    
        $this->_render(
            'View Client',
            'crm/clients/view',
            [
                'client' => $client,
                'notes'  => $notes,
            ]
        );
    }

    public function client_add()
    {
        $this->_guard_manage_crm();
    
        if ($this->input->post()) {
            $data = $this->_collect_client_data();
            $this->crmclients->normalize_group_flags($data);
            
            if (empty($data['client_code'])) {
                $data['client_code'] = finance_generate_client_code();
            }
    
            if (!$this->crmclients->is_client_code_unique($data['client_code'])) {
                set_alert('danger', 'Client code already exists. Please try again.');
                redirect('crm/client_add');
                return;
            }
    
            $data['created_by'] = (int)$this->session->userdata('user_id');
            $clientId = $this->crmclients->insert($data);
            
            $this->log_activity(
                'Client created: ID ' . (int)$clientId .
                ', Code ' . ($data['client_code'] ?? '') .
                ', Name ' . ($data['practice_legal_name'] ?? $data['practice_name'] ?? 'Unknown Client')
            );
            
            set_alert('success', 'Client added successfully.');
            redirect('crm/clients');
        }

        $this->_render(
            'Add Client',
            'crm/clients/add',
            [
                'default_currency' => company_setting('finance_base_currency') ?? 'USD',
                'client_groups'    => $this->crmclients->get_groups(true),
                'users'      => $this->crmusers->get_all_users(),
            ]
        );
    }
    
    public function client_edit($id)
    {
        $this->_guard_manage_crm();
    
        $client = $this->crmclients->get($id);
        if (!$client) {
            show_404();
        }
    
        if ($this->input->post()) {
            $data = $this->_collect_client_data();
            unset($data['client_code']);
            $this->crmclients->normalize_group_flags($data);
            $data['updated_by'] = (int)$this->session->userdata('user_id');
            $this->crmclients->update($id, $data);
            
            $clientName = trim((string)($client['practice_legal_name'] ?? $client['practice_name'] ?? 'Unknown Client'));
            $clientCode = trim((string)($client['client_code'] ?? ''));
            
            $this->log_activity(
                'Client updated: ID ' . (int)$id .
                ($clientCode !== '' ? ', Code ' . $clientCode : '') .
                ', Name ' . $clientName
            );
            
            set_alert('success', 'Client updated successfully.');
            redirect('crm/clients');
        }
    
        $this->_render(
            'Edit Client',
            'crm/clients/edit',
            [
                'client'        => $client,
                'client_groups' => $this->crmclients->get_groups(true),
            ]
        );
    }

    public function client_delete($id)
    {
        $this->_guard_manage_crm();
    
        if (!staff_can('client_delete', 'crm')) {
            $this->_crm_forbidden();
        }
    
        $client = $this->crmclients->get((int)$id);
        if (!$client) {
            show_404();
        }
    
        if (!$this->input->post()) {
            show_404();
        }
    
        $this->crmclients->delete((int)$id);
    
        $clientName = trim((string)($client['practice_legal_name'] ?? $client['practice_name'] ?? 'Unknown Client'));
        $clientCode = trim((string)($client['client_code'] ?? ''));
    
        $this->log_activity(
            'Client inactivated: ID ' . (int)$id .
            ($clientCode !== '' ? ', Code ' . $clientCode : '') .
            ', Name ' . $clientName
        );
    
        set_alert('success', 'Client removed successfully.');
        redirect('crm/clients');
    }

    public function groups()
    {
        $this->_guard_manage_crm();
    
        $view = [
            'groups'     => $this->crmclients->get_groups_with_clients_count(false),
            'group_kpi'  => $this->crmclients->get_groups_kpi(),
            'table_id'   => 'crmGroupsTable',
        ];
    
        $this->_render('Client Groups', 'crm/groups/index', $view);
    }

    public function group_store()
    {
        $this->_guard_manage_crm();
    
        if (!staff_can('client_create', 'crm')) {
            $this->_crm_forbidden();
        }
    
        if (!$this->input->post()) {
            show_404();
        }
    
        $data = [
            'group_name'        => trim((string)$this->input->post('group_name', true)),
            'company_name'      => trim((string)$this->input->post('company_name', true)),
            'tax_id'            => trim((string)$this->input->post('tax_id', true)),
            'contact_person'    => trim((string)$this->input->post('contact_person', true)),
            'contact_email'     => trim((string)$this->input->post('contact_email', true)),
            'contact_phone'     => trim((string)$this->input->post('contact_phone', true)),
            'contact_alt_phone' => trim((string)$this->input->post('contact_alt_phone', true)),
            'billing_email'     => trim((string)$this->input->post('billing_email', true)),
            'website'           => trim((string)$this->input->post('website', true)),
            'fax_number'        => trim((string)$this->input->post('fax_number', true)),
            'contract_date'     => $this->input->post('contract_date', true) ?: null,
            'contract_end'      => $this->input->post('contract_end', true) ?: null,
            'auto_renew'        => (int)$this->input->post('auto_renew'),
            'next_renew'        => $this->input->post('next_renew', true) ?: null,
            'last_renew'        => $this->input->post('last_renew', true) ?: null,
            'invoice_mode'      => $this->input->post('invoice_mode', true),
            'payment_terms'     => $this->input->post('payment_terms', true),
            'onboarding_status' => $this->input->post('onboarding_status', true),
            'status'            => 'active',
            'address'           => trim((string)$this->input->post('address', true)),
            'city'              => trim((string)$this->input->post('city', true)),
            'state'             => trim((string)$this->input->post('state', true)),
            'zip_code'          => trim((string)$this->input->post('zip_code', true)),
            'country'           => trim((string)$this->input->post('country', true)),
            'notes'             => trim((string)$this->input->post('notes', true)),
            'created_by'        => (int)$this->session->userdata('user_id'),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
    
        if ($data['group_name'] === '') {
            set_alert('danger', 'Group name is required.');
            redirect('crm/groups');
            return;
        }
    
        if (!empty($_FILES['contract_file']['name'])) {
            $config['upload_path']   = FCPATH . 'uploads/contracts/';
            $config['allowed_types'] = 'pdf|doc|docx';
            $config['encrypt_name']  = true;
    
            $this->load->library('upload', $config);
    
            if ($this->upload->do_upload('contract_file')) {
                $uploadData = $this->upload->data();
                $data['contract_file'] = $uploadData['file_name'];
            } else {
                set_alert('danger', $this->upload->display_errors());
                redirect('crm/groups');
                return;
            }
        }
    
        $groupId = $this->crmclients->insert_group($data);
    
        $this->log_activity(
            'Client group created: ID ' . (int)$groupId .
            ', Name ' . ($data['group_name'] ?? 'Unknown Group')
        );
    
        set_alert('success', 'Group added successfully.');
        redirect('crm/groups');
    }

public function group_view($id)
{
    $this->_guard_manage_crm();

    if (!staff_can('client_view', 'crm') && !staff_can('view', 'crm')) {
        $this->_crm_forbidden();
    }

    $group = $this->crmclients->get_group((int)$id);
    if (!$group) {
        show_404();
    }

    $clients = $this->crmclients->get_clients_by_group((int)$id);

    $this->_render(
        'View Group',
        'crm/groups/view',
        [
            'group'   => $group,
            'clients' => $clients,
        ]
    );
}
public function group_edit_modal($id)
{
    $this->_guard_manage_crm();

    if (!staff_can('client_edit', 'crm')) {
        $this->_crm_forbidden();
    }

    $group = $this->crmclients->get_group((int)$id);
    if (!$group) {
        show_404();
    }

    $this->load->view('crm/groups/modals/group_edit', ['group' => $group]);
}
public function group_update($id)
{
    $this->_guard_manage_crm();

    if (!staff_can('client_edit', 'crm')) {
        $this->_crm_forbidden();
    }

    $group = $this->crmclients->get_group((int)$id);
    if (!$group) {
        show_404();
    }

    if (!$this->input->post()) {
        show_404();
    }

    $data = [
        'group_name'     => trim((string)$this->input->post('group_name', true)),
        'company_name'   => trim((string)$this->input->post('company_name', true)),
        'website'        => trim((string)$this->input->post('website', true)),
        'fax_number'     => trim((string)$this->input->post('fax_number', true)),
        'contract_date'  => trim((string)$this->input->post('contract_date', true)),
        'contact_person' => trim((string)$this->input->post('contact_person', true)),
        'contact_email'  => trim((string)$this->input->post('contact_email', true)),
        'contact_phone'  => trim((string)$this->input->post('contact_phone', true)),
        'status'         => trim((string)$this->input->post('status', true)) ?: 'active',
        'updated_at'     => date('Y-m-d H:i:s'),
    ];

    if ($data['group_name'] === '') {
        set_alert('danger', 'Group name is required.');
        redirect('crm/groups');
        return;
    }

        $this->crmclients->update_group((int)$id, $data);
        
        $this->log_activity(
            'Client group updated: ID ' . (int)$id .
            ', Name ' . ($data['group_name'] ?? $group['group_name'] ?? 'Unknown Group')
        );
        
        set_alert('success', 'Group updated successfully.');
        redirect('crm/groups');
}

    public function group_delete($id)
    {
        $this->_guard_manage_crm();
    
        if (!staff_can('client_delete', 'crm')) {
            $this->_crm_forbidden();
        }
    
        $group = $this->crmclients->get_group((int)$id);
        if (!$group) {
            show_404();
        }
    
        // Must be POST
        if (!$this->input->post()) {
            show_404();
        }
    
        // Block if active clients exist
        if ($this->crmclients->has_active_clients_in_group((int)$id)) {
            set_alert('danger', 'Cannot inactivate this group because it has active clients. Please inactivate all clients under this group first.');
            redirect('crm/groups');
            return;
        }
    
        $this->crmclients->delete_group((int)$id);
        
        $this->log_activity(
            'Client group inactivated: ID ' . (int)$id .
            ', Name ' . ($group['group_name'] ?? 'Unknown Group')
        );
        
        set_alert('success', 'Group inactivated successfully.');
        redirect('crm/groups');
    }


    /* =========================================================
     * CLIENT NOTES
     * ======================================================= */
    
    public function client_add_note($clientId)
    {
        $this->_guard_manage_crm();
    
        if (!staff_can('client_edit', 'crm') && !staff_can('client_create', 'crm')) {
            $this->_crm_forbidden();
        }
    
        $clientId = (int)$clientId;
        $client = $this->crmclients->get($clientId);
        if (!$client) {
            show_404();
        }
    
        if (!$this->input->post()) {
            show_404();
        }
    
        $note = trim((string)$this->input->post('note', false));
        $isInternal = (int)$this->input->post('is_internal', true);
    
        if ($note === '') {
            set_alert('danger', 'Note is required.');
            redirect('crm/client_view/' . $clientId);
            return;
        }
    
        $userId = (int)$this->session->userdata('user_id');
    
        $noteId = $this->crmnotes->insert([
            'rel_type'    => 'client',
            'rel_id'      => $clientId,
            'note'        => $note,
            'is_internal' => $isInternal === 0 ? 0 : 1,
            'user_id'     => $userId,
            'created_by'  => $userId,
            'updated_by'  => $userId,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    
        $clientName = trim((string)($client['practice_legal_name'] ?? $client['practice_name'] ?? 'Unknown Client'));
        $clientCode = trim((string)($client['client_code'] ?? ''));
    
        $this->log_activity(
            'Client note added: Note ID ' . (int)$noteId .
            ', Client ID ' . $clientId .
            ($clientCode !== '' ? ', Code ' . $clientCode : '') .
            ', Name ' . $clientName
        );
    
        set_alert('success', 'Note added successfully.');
        redirect('crm/client_view/' . $clientId);
    }
    
    public function client_update_note($noteId)
    {
        $this->_guard_manage_crm();
    
        if (!staff_can('client_edit', 'crm')) {
            $this->_crm_forbidden();
        }
    
        $noteId = (int)$noteId;
        $noteRow = $this->crmnotes->get($noteId);
        if (!$noteRow || ($noteRow['rel_type'] ?? '') !== 'client') {
            show_404();
        }
    
        $clientId = (int)($noteRow['rel_id'] ?? 0);
        $client = $this->crmclients->get($clientId);
        if (!$client) {
            show_404();
        }
    
        if (!$this->input->post()) {
            show_404();
        }
    
        $note = trim((string)$this->input->post('note', false));
        $isInternal = (int)$this->input->post('is_internal', true);
    
        if ($note === '') {
            set_alert('danger', 'Note is required.');
            redirect('crm/client_view/' . $clientId);
            return;
        }
    
        $ok = $this->crmnotes->update($noteId, [
            'note'        => $note,
            'is_internal' => $isInternal === 0 ? 0 : 1,
            'updated_by'  => (int)$this->session->userdata('user_id'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    
        if ($ok) {
            $clientName = trim((string)($client['practice_legal_name'] ?? $client['practice_name'] ?? 'Unknown Client'));
            $clientCode = trim((string)($client['client_code'] ?? ''));
    
            $this->log_activity(
                'Client note updated: Note ID ' . $noteId .
                ', Client ID ' . $clientId .
                ($clientCode !== '' ? ', Code ' . $clientCode : '') .
                ', Name ' . $clientName
            );
        }
    
        set_alert($ok ? 'success' : 'danger', $ok ? 'Note updated successfully.' : 'Failed to update note.');
        redirect('crm/client_view/' . $clientId);
    }
    
    public function client_delete_note($noteId)
    {
        $this->_guard_manage_crm();
    
        if (!staff_can('client_delete', 'crm') && !staff_can('client_edit', 'crm')) {
            $this->_crm_forbidden();
        }
    
        $noteId = (int)$noteId;
        $noteRow = $this->crmnotes->get($noteId);
        if (!$noteRow || ($noteRow['rel_type'] ?? '') !== 'client') {
            show_404();
        }
    
        $clientId = (int)($noteRow['rel_id'] ?? 0);
        $client = $this->crmclients->get($clientId);
        if (!$client) {
            show_404();
        }
    
        if (!$this->input->post()) {
            show_404();
        }
    
        $ok = $this->crmnotes->delete($noteId);
    
        if ($ok) {
            $clientName = trim((string)($client['practice_legal_name'] ?? $client['practice_name'] ?? 'Unknown Client'));
            $clientCode = trim((string)($client['client_code'] ?? ''));
    
            $this->log_activity(
                'Client note deleted: Note ID ' . $noteId .
                ', Client ID ' . $clientId .
                ($clientCode !== '' ? ', Code ' . $clientCode : '') .
                ', Name ' . $clientName
            );
        }
    
        set_alert($ok ? 'success' : 'danger', $ok ? 'Note deleted successfully.' : 'Failed to delete note.');
        redirect('crm/client_view/' . $clientId);
    }


public function settings()
{
    $this->_guard_manage_crm();

    $this->load->model('Crmsettings_model');
    $this->load->model('crmusers_model', 'crmusers');

    // Handle POST
    if ($this->input->post('settings')) {

        $settings = $this->input->post('settings');

        $this->Crmsettings_model->save($settings);

        set_alert('success', 'CRM settings updated successfully.');

        redirect(site_url('crm/settings'));
    }

    // Fetch settings
    $existing_data = $this->Crmsettings_model->get_all();

    // ✅ Unified user source
    $users = $this->crmusers->get_all_users();

    $view = [
        'existing_data' => $existing_data,
        'users'         => $users,
    ];

    $this->_render('CRM Settings', 'crm/settings/manage', $view);
}

/**
 * =========================================================
 * CLIENT DATA COLLECTOR (FIX MISSING METHOD)
 * =========================================================
 */
protected function _collect_client_data(): array
{
    $fields = [
        // Core
        'client_code',
        'client_group_id',
        'is_group',

        'practice_name',
        'practice_legal_name',
        'practice_type',
        'specialty',

        // Compliance / IDs
        'tax_id',
        'npi_number',

        // Primary Contact
        'primary_contact_name',
        'primary_contact_title',
        'primary_email',
        'primary_phone',

        // Address
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'time_zone',

        // Billing
        'billing_model',
        'rate_percent',
        'rate_flat',
        'rate_custom',

        // Contract
        'contract_start_date',
        'contract_end_date',
        'invoice_frequency',
        'services_included',

        // Volume / Revenue
        'avg_monthly_claims',
        'expected_monthly_collections',

        // Ownership
        'account_manager',

        // Status
        'client_status',
        'is_active',

        // Lifecycle
        'onboarding_date',
        'offboarding_date',
        'termination_reason',

        // Notes
        'internal_notes',
    ];

    $data = [];

    foreach ($fields as $field) {
        $value = $this->input->post($field, true);

        if (is_string($value)) {
            $value = trim($value);
        }

        $data[$field] = ($value === '') ? null : $value;
    }

    // =========================
    // REQUIRED FIELD
    // =========================
    $data['practice_name'] = trim((string)($data['practice_name'] ?? ''));

    // =========================
    // TYPE CASTING
    // =========================

    // Integers
    $intFields = [
        'client_group_id',
        'is_group',
        'account_manager',
        'is_active',
        'avg_monthly_claims',
    ];

    foreach ($intFields as $field) {
        if (isset($data[$field]) && $data[$field] !== null) {
            $data[$field] = is_numeric($data[$field]) ? (int)$data[$field] : null;
        }
    }

    // Decimals
    $decimalFields = [
        'rate_percent',
        'rate_flat',
        'rate_custom',
        'expected_monthly_collections',
    ];

    foreach ($decimalFields as $field) {
        if (isset($data[$field]) && $data[$field] !== null) {
            $data[$field] = is_numeric($data[$field]) ? $data[$field] : null;
        }
    }

    // =========================
    // DEFAULTS (SAFE FALLBACKS)
    // =========================

    if ($data['client_status'] === null) {
        $data['client_status'] = 'active';
    }

    if ($data['is_active'] === null) {
        $data['is_active'] = 1;
    }

    if ($data['is_group'] === null) {
        $data['is_group'] = 0;
    }

    // =========================
    // SYSTEM FIELDS (DO NOT TRUST FORM)
    // =========================

    $userId = (int)$this->session->userdata('user_id');

    if ($userId > 0) {
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
    }

    // Timestamps handled in model normally
    // but safe fallback:
    $now = date('Y-m-d H:i:s');

    if (!isset($data['created_at'])) {
        $data['created_at'] = $now;
    }

    $data['updated_at'] = $now;

    return $data;
}

}