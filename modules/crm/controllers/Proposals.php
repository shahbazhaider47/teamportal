<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Proposals extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['url', 'form', 'crm']);
        $this->load->library(['form_validation']);
        $this->load->model('crm/Crmproposals_model', 'crmproposals');
        $this->load->model('crm/Crmleads_model', 'crmleads');
    }

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

    protected function _crm_forbidden()
    {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        echo $html;
        exit;
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

    protected function _can_create(): bool
    {
        return staff_can('proposal_create', 'crm') || staff_can('client_create', 'crm');
    }

    protected function _can_edit(): bool
    {
        return staff_can('proposal_edit', 'crm') || staff_can('client_edit', 'crm');
    }

    protected function _can_delete(): bool
    {
        return staff_can('proposal_delete', 'crm') || staff_can('client_delete', 'crm');
    }

    protected function _can_view(): bool
    {
        return staff_can('proposal_view', 'crm') || staff_can('client_view', 'crm') || staff_can('view', 'crm');
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

    protected function _lead_options(): array
    {
        return $this->db
            ->select('id, practice_name, contact_person')
            ->from('crm_leads')
            ->where('is_deleted', 0)
            ->order_by('practice_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function index()
    {
        $this->_guard_manage_crm();

        if (!$this->_can_view()) {
            $this->_crm_forbidden();
        }

        $filters = [
            'q'                 => (string)$this->input->get('q', true),
            'status'            => (string)$this->input->get('status', true),
            'lead_id'           => (int)$this->input->get('lead_id', true),
            'forecast_category' => (string)$this->input->get('forecast_category', true),
            'exclude_deleted'   => true,
        ];

        $proposals = $this->crmproposals->get_all_with_meta($filters);
        $counts    = $this->crmproposals->count_by_status(true);

        $this->_render('Proposals', 'crm/proposals/index', [
            'proposals' => $proposals,
            'counts'    => $counts,
            'filters'   => $filters,
            'leads'     => $this->_lead_options(),
            'can'       => [
                'create' => $this->_can_create(),
                'edit'   => $this->_can_edit(),
                'delete' => $this->_can_delete(),
                'view'   => $this->_can_view(),
            ],
        ]);
    }

    public function create()
    {
        $this->_guard_manage_crm();

        if (!$this->_can_create()) {
            $this->_crm_forbidden();
        }

        $leadId = (int)$this->input->get('lead_id', true);

        $proposal = [
            'proposal_number'     => '',
            'lead_id'             => $leadId > 0 ? $leadId : '',
            'title'               => '',
            'summary'             => '',
            'terms_and_conditions'=> '',
            'subtotal'            => 0,
            'discount_type'       => 'none',
            'discount_value'      => 0,
            'discount_amount'     => 0,
            'tax_rate'            => 0,
            'tax_amount'          => 0,
            'total_value'         => 0,
            'billing_cycle'       => '',
            'payment_terms'       => '',
            'validity_days'       => '',
            'start_date'          => '',
            'go_live_date'        => '',
            'status'              => 'draft',
            'forecast_category'   => '',
            'pdf_path'            => '',
            'internal_notes'      => '',
            'client_notes'        => '',
            'expires_at'          => '',
        ];

        $items = [
            [
                'item_type'       => 'service',
                'item_name'       => '',
                'description'     => '',
                'quantity'        => 1,
                'unit_price'      => 0,
                'discount_type'   => 'none',
                'discount_value'  => 0,
                'discount_amount' => 0,
                'line_total'      => 0,
            ],
            [
                'item_type'       => 'service',
                'item_name'       => '',
                'description'     => '',
                'quantity'        => 1,
                'unit_price'      => 0,
                'discount_type'   => 'none',
                'discount_value'  => 0,
                'discount_amount' => 0,
                'line_total'      => 0,
            ],
            [
                'item_type'       => 'service',
                'item_name'       => '',
                'description'     => '',
                'quantity'        => 1,
                'unit_price'      => 0,
                'discount_type'   => 'none',
                'discount_value'  => 0,
                'discount_amount' => 0,
                'line_total'      => 0,
            ],
        ];

        $this->_render('Create Proposal', 'crm/proposals/create', [
            'proposal' => $proposal,
            'items'    => $items,
            'leads'    => $this->_lead_options(),
            'can'      => [
                'create' => $this->_can_create(),
                'edit'   => $this->_can_edit(),
                'delete' => $this->_can_delete(),
                'view'   => $this->_can_view(),
            ],
        ]);
    }

    public function edit($id)
    {
        $this->_guard_manage_crm();

        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }

        $id = (int)$id;

        $proposal = $this->crmproposals->get_with_meta($id);
        if (!$proposal || !empty($proposal['deleted_at'])) {
            show_404();
        }

        $items = $this->crmproposals->get_items($id);
        if (empty($items)) {
            $items = [[
                'item_type'       => 'service',
                'item_name'       => '',
                'description'     => '',
                'quantity'        => 1,
                'unit_price'      => 0,
                'discount_type'   => 'none',
                'discount_value'  => 0,
                'discount_amount' => 0,
                'line_total'      => 0,
            ]];
        }

        $this->_render('Edit Proposal', 'crm/proposals/edit', [
            'proposal' => $proposal,
            'items'    => $items,
            'leads'    => $this->_lead_options(),
            'can'      => [
                'create' => $this->_can_create(),
                'edit'   => $this->_can_edit(),
                'delete' => $this->_can_delete(),
                'view'   => $this->_can_view(),
            ],
        ]);
    }

    public function view($id)
    {
        $this->_guard_manage_crm();

        if (!$this->_can_view()) {
            $this->_crm_forbidden();
        }

        $id = (int)$id;

        $proposal = $this->crmproposals->get_with_meta($id);
        if (!$proposal || !empty($proposal['deleted_at'])) {
            show_404();
        }

        $items = $this->crmproposals->get_items($id);

        $this->_render('View Proposal', 'crm/proposals/view_proposal', [
            'proposal' => $proposal,
            'items'    => $items,
            'can'      => [
                'create' => $this->_can_create(),
                'edit'   => $this->_can_edit(),
                'delete' => $this->_can_delete(),
                'view'   => $this->_can_view(),
            ],
        ]);
    }

    public function store()
    {
        $this->_guard_manage_crm();

        if (!$this->_can_create()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $this->_require_login();
        $actorId = $this->_user_id();

        $data  = $this->_collect_proposal_data_from_post();
        $items = $this->_collect_items_from_post();

        if ($data['title'] === '') {
            set_alert('danger', 'Proposal title is required.');
            redirect('crm/proposals/create');
            return;
        }

        if ($data['proposal_number'] === '') {
            $data['proposal_number'] = $this->crmproposals->generate_proposal_number();
        }

        if (!$this->crmproposals->is_number_unique($data['proposal_number'])) {
            $data['proposal_number'] = $this->crmproposals->generate_proposal_number();
        }

        $data['created_by'] = $actorId;
        $data['updated_by'] = $actorId;

        $this->db->trans_begin();

        $proposalId = $this->crmproposals->insert($data);

        if ($proposalId > 0) {
            $this->crmproposals->replace_items($proposalId, $items);
        }

        if ($this->db->trans_status() === false || $proposalId <= 0) {
            $this->db->trans_rollback();
            set_alert('danger', 'Failed to create proposal.');
            redirect('crm/proposals/create');
            return;
        }

        $this->db->trans_commit();

        set_alert('success', 'Proposal created successfully.');
        redirect('crm/proposals/view/' . $proposalId);
    }

    public function update($id)
    {
        $this->_guard_manage_crm();

        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }

        $id = (int)$id;
        $proposal = $this->crmproposals->get($id);

        if (!$proposal || !empty($proposal['deleted_at'])) {
            show_404();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $this->_require_login();

        $data  = $this->_collect_proposal_data_from_post();
        $items = $this->_collect_items_from_post();

        if ($data['title'] === '') {
            set_alert('danger', 'Proposal title is required.');
            redirect('crm/proposals/edit/' . $id);
            return;
        }

        unset($data['proposal_number']);
        unset($data['public_token']);

        $data['updated_by'] = $this->_user_id();

        $this->db->trans_begin();

        $ok1 = $this->crmproposals->update($id, $data);
        $ok2 = $this->crmproposals->replace_items($id, $items);

        if ($this->db->trans_status() === false || !$ok1 || !$ok2) {
            $this->db->trans_rollback();
            set_alert('danger', 'Failed to update proposal.');
            redirect('crm/proposals/edit/' . $id);
            return;
        }

        $this->db->trans_commit();

        set_alert('success', 'Proposal updated successfully.');
        redirect('crm/proposals/view/' . $id);
    }

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
        $proposal = $this->crmproposals->get($id);

        if (!$proposal) {
            show_404();
        }

        $this->_require_login();

        $ok = $this->crmproposals->soft_delete($id, $this->_user_id());

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'Proposal deleted successfully.' : 'Failed to delete proposal.'
        );

        redirect('crm/proposals');
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

        $id = (int)$id;
        $proposal = $this->crmproposals->get($id);

        if (!$proposal) {
            show_404();
        }

        $this->_require_login();

        $ok = $this->crmproposals->restore($id, $this->_user_id());

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'Proposal restored successfully.' : 'Failed to restore proposal.'
        );

        redirect('crm/proposals/view/' . $id);
    }

    public function change_status($id)
    {
        $this->_guard_manage_crm();

        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $id = (int)$id;
        $proposal = $this->crmproposals->get($id);

        if (!$proposal || !empty($proposal['deleted_at'])) {
            show_404();
        }

        $this->_require_login();

        $status = trim((string)$this->input->post('status', true));
        $declineReason = trim((string)$this->input->post('decline_reason', true));

        $allowedStatuses = [
            'draft',
            'pending_review',
            'sent',
            'viewed',
            'approved',
            'declined',
            'expired',
            'cancelled',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            set_alert('danger', 'Invalid proposal status selected.');
            redirect('crm/proposals/view/' . $id);
            return;
        }

        $ok = $this->crmproposals->change_status($id, $status, $this->_user_id(), $declineReason !== '' ? $declineReason : null);

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'Proposal status updated successfully.' : 'Failed to update proposal status.'
        );

        redirect('crm/proposals/view/' . $id);
    }

protected function _collect_proposal_data_from_post(): array
{
    $fields = [
        'proposal_number',
        'lead_id',
        'title',
        'summary',
        'terms_and_conditions',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_value',
        'billing_cycle',
        'payment_terms',
        'validity_days',
        'start_date',
        'go_live_date',
        'status',
        'forecast_category',
        'pdf_path',
        'internal_notes',
        'client_notes',
        'expires_at',
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

    $data['title']           = trim((string)($data['title'] ?? ''));
    $data['proposal_number'] = trim((string)($data['proposal_number'] ?? ''));

    foreach (['lead_id', 'validity_days'] as $field) {
        $value = $data[$field] ?? null;
        $data[$field] = ($value !== null && is_numeric($value)) ? (int)$value : null;
    }

    foreach (['subtotal', 'discount_value', 'discount_amount', 'tax_rate', 'tax_amount', 'total_value'] as $field) {
        $value = $data[$field] ?? 0;
        $data[$field] = is_numeric($value) ? (float)$value : 0.00;
    }

    $allowedDiscountTypes = ['none', 'percent', 'fixed'];
    if (!in_array((string)($data['discount_type'] ?? 'none'), $allowedDiscountTypes, true)) {
        $data['discount_type'] = 'none';
    }

    $allowedBillingCycles = ['weekly', 'bi-weekly', 'monthly', 'quarterly', 'annual', 'custom'];
    if ($data['billing_cycle'] !== null && !in_array((string)$data['billing_cycle'], $allowedBillingCycles, true)) {
        $data['billing_cycle'] = null;
    }

    $allowedStatuses = ['draft', 'pending_review', 'sent', 'viewed', 'approved', 'declined', 'expired', 'cancelled'];
    if (!in_array((string)($data['status'] ?? 'draft'), $allowedStatuses, true)) {
        $data['status'] = 'draft';
    }

    $allowedForecastCategories = ['commit', 'best_case', 'pipeline', 'omitted'];
    if ($data['forecast_category'] !== null && !in_array((string)$data['forecast_category'], $allowedForecastCategories, true)) {
        $data['forecast_category'] = null;
    }

    return $data;
}

    protected function _collect_items_from_post(): array
    {
        $itemTypes       = (array)$this->input->post('item_type');
        $itemNames       = (array)$this->input->post('item_name');
        $descriptions    = (array)$this->input->post('item_description');
        $quantities      = (array)$this->input->post('item_quantity');
        $unitPrices      = (array)$this->input->post('item_unit_price');
        $discountTypes   = (array)$this->input->post('item_discount_type');
        $discountValues  = (array)$this->input->post('item_discount_value');
        $discountAmounts = (array)$this->input->post('item_discount_amount');
        $lineTotals      = (array)$this->input->post('item_line_total');

        $count = max(
            count($itemNames),
            count($itemTypes),
            count($descriptions),
            count($quantities),
            count($unitPrices),
            count($discountTypes),
            count($discountValues),
            count($discountAmounts),
            count($lineTotals)
        );

        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $itemName = trim((string)($itemNames[$i] ?? ''));
            if ($itemName === '') {
                continue;
            }

            $itemType = trim((string)($itemTypes[$i] ?? 'service'));
            if (!in_array($itemType, ['service', 'setup_fee', 'addon', 'discount', 'other'], true)) {
                $itemType = 'service';
            }

            $discountType = trim((string)($discountTypes[$i] ?? 'none'));
            if (!in_array($discountType, ['none', 'percent', 'fixed'], true)) {
                $discountType = 'none';
            }

            $items[] = [
                'item_type'       => $itemType,
                'item_name'       => $itemName,
                'description'     => trim((string)($descriptions[$i] ?? '')) ?: null,
                'quantity'        => isset($quantities[$i]) && is_numeric($quantities[$i]) ? $quantities[$i] : 1,
                'unit_price'      => isset($unitPrices[$i]) && is_numeric($unitPrices[$i]) ? $unitPrices[$i] : 0,
                'discount_type'   => $discountType,
                'discount_value'  => isset($discountValues[$i]) && is_numeric($discountValues[$i]) ? $discountValues[$i] : 0,
                'discount_amount' => isset($discountAmounts[$i]) && is_numeric($discountAmounts[$i]) ? $discountAmounts[$i] : 0,
                'line_total'      => isset($lineTotals[$i]) && is_numeric($lineTotals[$i]) ? $lineTotals[$i] : 0,
            ];
        }

        return $items;
    }
}