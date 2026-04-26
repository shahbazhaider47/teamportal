<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Invoices extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'form', 'number', 'finance']);
        $this->load->library(['form_validation', 'pagination']);
        $this->load->model('finance/Invoices_model', 'invoices_m');
        $this->load->model('finance/Finance_model',  'finance');
        $this->load->model('crm/Crmclients_model', 'crmclients');
    }

    protected function _render($title, $subview, $data = [])
    {
        add_module_assets('finance', [
            'css' => ['finance.css'],
            'js'  => ['finance.js'],
        ]);
        $this->load->view('layouts/master', [
            'page_title' => $title,
            'subview'    => $subview,
            'view_data'  => $data,
        ]);
    }

    protected function _guard($perm = 'view')
    {
        if (!staff_can($perm, 'finance')) {
            $this->_forbidden();
        }
    }

    protected function _forbidden()
    {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        echo $html;
        exit;
    }

    protected function _json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function _is_ajax()
    {
        return $this->input->is_ajax_request();
    }

    public function index()
    {
        $this->_guard('view');
    
        $per_page   = 20;
        $page       = max(1, (int)$this->input->get('page'));
        $offset     = ($page - 1) * $per_page;
        $total_rows = $this->invoices_m->count_invoices();
    
        $this->pagination->initialize([
            'base_url'   => site_url('finance/invoices'),
            'total_rows' => $total_rows,
            'per_page'   => $per_page,
        ]);
    
        $summary = $this->invoices_m->get_summary();
    
        $this->_render('Invoices', 'finance/invoices/index', [
            'invoices'   => $this->invoices_m->get_invoices($per_page, $offset),
            'summary'    => $summary,
            'clients'    => $this->invoices_m->get_clients_dropdown(),
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $total_rows,
            'page'       => $page,
            'per_page'   => $per_page,
            'offset'     => $offset,
        ]);
    }

    public function view($id = null)
    {
        $this->_guard('view');

        $invoice = $this->invoices_m->get_invoice($id);
        if (!$invoice) {
            show_404();
        }

        $items    = $this->invoices_m->get_invoice_items($id);
        $payments = $this->invoices_m->get_invoice_payments($id);
        $client   = $this->invoices_m->get_client($invoice->client_id);

        $this->_render('Invoice #' . $invoice->invoice_number, 'finance/invoices/view', [
            'invoice'  => $invoice,
            'items'    => $items,
            'payments' => $payments,
            'client'   => $client,
        ]);
    }

public function get_client_info()
{
    if (!$this->_is_ajax()) {
        $this->_forbidden();
    }

    $client_id = (int)$this->input->get('client_id');
    if (!$client_id) {
        $this->_json(['success' => false, 'message' => 'No client ID provided.'], 400);
    }

    $client = $this->crmclients->get_client_for_invoice($client_id);
    if (!$client) {
        $this->_json(['success' => false, 'message' => 'Client not found.'], 404);
    }

    $outstanding = $this->invoices_m->get_client_outstanding_invoices($client_id);

    $this->_json([
        'success'     => true,
        'client'      => $client,
        'outstanding' => $outstanding,
    ]);
}

    public function create()
    {
        $this->_guard('create');

        $clients    = $this->invoices_m->get_clients_dropdown();
        $next_num   = $this->invoices_m->generate_invoice_number();

        $this->_render('New Invoice', 'finance/invoices/invoice_form', [
            'invoice'    => null,
            'items'      => [],
            'clients'    => $clients,
            'next_num'   => $next_num,
            'form_action'=> site_url('finance/invoices/store_invoice'),
            'page_mode'  => 'add',
        ]);
    }

    public function store_invoice()
    {
        $this->_guard('create');

        $this->form_validation->set_rules('client_id',    'Client',         'required|integer');
        $this->form_validation->set_rules('invoice_date', 'Invoice Date',   'required');
        $this->form_validation->set_rules('due_date',     'Due Date',       'permit_empty');
        $this->form_validation->set_rules('currency',     'Currency',       'required|max_length[10]');
        $this->form_validation->set_rules('status',       'Status',         'required|in_list[draft,sent,viewed,partial,paid,overdue,cancelled]');

        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }

        $data = [
            'invoice_number'  => $this->input->post('invoice_number') ?: $this->invoices_m->generate_invoice_number(),
            'client_id'       => (int) $this->input->post('client_id'),
            'contract_id'     => $this->input->post('contract_id')  ?: null,
            'proposal_id'     => $this->input->post('proposal_id')  ?: null,
            'po_number'       => $this->input->post('po_number')    ?: null,
            'invoice_date'    => $this->input->post('invoice_date'),
            'due_date'        => $this->input->post('due_date')     ?: null,
            'currency'        => $this->input->post('currency'),
            'exchange_rate'   => (float) ($this->input->post('exchange_rate') ?: 1),
            'discount_amount' => (float) ($this->input->post('discount_amount') ?: 0),
            'tax_rate'        => (float) ($this->input->post('tax_rate') ?: 0),
            'notes'           => $this->input->post('notes'),
            'terms'           => $this->input->post('terms'),
            'status'          => $this->input->post('status') ?: 'draft',
            'created_by'      => get_staff_user_id(),
        ];

        $items = $this->_parse_line_items();

        if (empty($items)) {
            set_alert('warning', 'Please add at least one line item.');
            $this->create();
            return;
        }

        $invoice_id = $this->invoices_m->create_invoice($data, $items);

        if ($invoice_id) {
            log_activity('Created Invoice #' . $data['invoice_number']);
            set_alert('success', 'Invoice created successfully.');
            redirect(site_url('finance/invoices/view/' . $invoice_id));
        } else {
            set_alert('danger', 'Failed to create invoice. Please try again.');
            $this->create();
        }
    }

    public function edit($id = null)
    {
        $this->_guard('edit');

        $invoice = $this->invoices_m->get_invoice($id);
        if (!$invoice) {
            show_404();
        }

        // Paid/cancelled invoices cannot be edited
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            set_alert('warning', 'This invoice cannot be edited in its current status.');
            redirect(site_url('finance/invoices/view/' . $id));
        }

        $clients    = $this->invoices_m->get_clients_dropdown();
        $items      = $this->invoices_m->get_invoice_items($id);

        $this->_render('Edit Invoice #' . $invoice->invoice_number, 'finance/invoices/invoice_form', [
            'invoice'     => $invoice,
            'items'       => $items,
            'clients'     => $clients,
            'next_num'    => null,
            'form_action' => site_url('finance/invoices/update/' . $id),
            'page_mode'   => 'edit',
        ]);
    }

    public function update($id = null)
    {
        $this->_guard('edit');

        $invoice = $this->invoices_m->get_invoice($id);
        if (!$invoice) {
            show_404();
        }

        $this->form_validation->set_rules('client_id',    'Client',       'required|integer');
        $this->form_validation->set_rules('invoice_date', 'Invoice Date', 'required');
        $this->form_validation->set_rules('currency',     'Currency',     'required|max_length[10]');
        $this->form_validation->set_rules('status',       'Status',       'required|in_list[draft,sent,viewed,partial,paid,overdue,cancelled]');

        if ($this->form_validation->run() === false) {
            $this->edit($id);
            return;
        }

        $data = [
            'client_id'       => (int) $this->input->post('client_id'),
            'contract_id'     => $this->input->post('contract_id')     ?: null,
            'proposal_id'     => $this->input->post('proposal_id')     ?: null,
            'po_number'       => $this->input->post('po_number')       ?: null,
            'invoice_date'    => $this->input->post('invoice_date'),
            'due_date'        => $this->input->post('due_date')        ?: null,
            'currency'        => $this->input->post('currency'),
            'exchange_rate'   => (float) ($this->input->post('exchange_rate') ?: 1),
            'discount_amount' => (float) ($this->input->post('discount_amount') ?: 0),
            'tax_rate'        => (float) ($this->input->post('tax_rate') ?: 0),
            'notes'           => $this->input->post('notes'),
            'terms'           => $this->input->post('terms'),
            'status'          => $this->input->post('status'),
            'updated_by'      => get_staff_user_id(),
        ];

        $items = $this->_parse_line_items();

        if (empty($items)) {
            set_alert('warning', 'Please add at least one line item.');
            $this->edit($id);
            return;
        }

        $updated = $this->invoices_m->update_invoice($id, $data, $items);

        if ($updated) {
            log_activity('Updated Invoice #' . $invoice->invoice_number);
            set_alert('success', 'Invoice updated successfully.');
            redirect(site_url('finance/invoices/view/' . $id));
        } else {
            set_alert('danger', 'Failed to update invoice.');
            $this->edit($id);
        }
    }

    public function delete($id = null)
    {
        $this->_guard('delete');

        if (!$this->input->is_ajax_request()) {
            $this->_forbidden();
        }

        $invoice = $this->invoices_m->get_invoice($id);
        if (!$invoice) {
            $this->_json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        if ($invoice->status === 'paid') {
            $this->_json(['success' => false, 'message' => 'Paid invoices cannot be deleted.']);
        }

        $deleted = $this->invoices_m->delete_invoice($id);

        if ($deleted) {
            log_activity('Deleted Invoice #' . $invoice->invoice_number);
            $this->_json(['success' => true, 'message' => 'Invoice deleted.', 'redirect' => site_url('finance/invoices')]);
        } else {
            $this->_json(['success' => false, 'message' => 'Failed to delete invoice.'], 500);
        }
    }

    public function send($id = null)
    {
        $this->_guard('edit');

        if (!$this->_is_ajax()) {
            $this->_forbidden();
        }

        $invoice = $this->invoices_m->get_invoice($id);
        if (!$invoice) {
            $this->_json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $to      = $this->input->post('email');
        $subject = $this->input->post('subject') ?: 'Invoice #' . $invoice->invoice_number;
        $message = $this->input->post('message');

        if (!valid_email($to)) {
            $this->_json(['success' => false, 'message' => 'Invalid email address.']);
        }

        $sent = $this->invoices_m->send_invoice($id, $to, $subject, $message);

        if ($sent) {
            log_activity('Sent Invoice #' . $invoice->invoice_number . ' to ' . $to);
            $this->_json(['success' => true, 'message' => 'Invoice sent to ' . $to . '.']);
        } else {
            $this->_json(['success' => false, 'message' => 'Failed to send invoice.'], 500);
        }
    }

    public function mark_paid($id = null)
    {
        $this->_guard('edit');

        if (!$this->_is_ajax()) {
            $this->_forbidden();
        }

        $invoice = $this->invoices_m->get_invoice($id);
        if (!$invoice) {
            $this->_json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $updated = $this->invoices_m->mark_as_paid($id);

        if ($updated) {
            log_activity('Marked Invoice #' . $invoice->invoice_number . ' as Paid');
            $this->_json(['success' => true, 'message' => 'Invoice marked as paid.']);
        } else {
            $this->_json(['success' => false, 'message' => 'Failed to update status.'], 500);
        }
    }

    public function record_payment()
    {
        $this->_guard('create');

        if (!$this->_is_ajax()) {
            $this->_forbidden();
        }

        $invoice_id = (int) $this->input->post('invoice_id');
        $invoice    = $this->invoices_m->get_invoice($invoice_id);

        if (!$invoice) {
            $this->_json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $amount = (float) $this->input->post('amount');
        if ($amount <= 0) {
            $this->_json(['success' => false, 'message' => 'Amount must be greater than zero.']);
        }

        if ($amount > $invoice->balance_due) {
            $this->_json(['success' => false, 'message' => 'Amount exceeds outstanding balance of ' . number_format($invoice->balance_due, 2) . '.']);
        }

        $payment = [
            'client_id'       => $invoice->client_id,
            'payment_date'    => $this->input->post('payment_date') ?: date('Y-m-d'),
            'amount'          => $amount,
            'payment_mode'    => $this->input->post('payment_mode') ?: 'other',
            'reference_no'    => $this->input->post('reference_no'),
            'bank_account_id' => $this->input->post('bank_account_id') ?: null,
            'currency'        => $invoice->currency,
            'exchange_rate'   => $invoice->exchange_rate,
            'notes'           => $this->input->post('notes'),
            'status'          => 'completed',
            'created_by'      => get_staff_user_id(),
        ];

        $payment_id = $this->invoices_m->record_payment($invoice_id, $payment);

        if ($payment_id) {
            log_activity('Recorded payment of ' . $invoice->currency . ' ' . number_format($amount, 2) . ' for Invoice #' . $invoice->invoice_number);
            $this->_json([
                'success'     => true,
                'message'     => 'Payment of ' . number_format($amount, 2) . ' recorded successfully.',
                'balance_due' => $this->invoices_m->get_invoice($invoice_id)->balance_due,
            ]);
        } else {
            $this->_json(['success' => false, 'message' => 'Failed to record payment.'], 500);
        }
    }

    public function duplicate($id = null)
    {
        $this->_guard('create');

        $invoice = $this->invoices_m->get_invoice($id);
        if (!$invoice) {
            show_404();
        }

        $new_id = $this->invoices_m->duplicate_invoice($id);

        if ($new_id) {
            $new = $this->invoices_m->get_invoice($new_id);
            log_activity('Duplicated Invoice #' . $invoice->invoice_number . ' → #' . $new->invoice_number);
            set_alert('success', 'Invoice duplicated. You are now editing the copy.');
            redirect(site_url('finance/invoices/edit/' . $new_id));
        } else {
            set_alert('danger', 'Failed to duplicate invoice.');
            redirect(site_url('finance/invoices/view/' . $id));
        }
    }

    public function pdf($id = null)
    {
        $this->_guard('view');

        $invoice = $this->invoices_m->get_invoice($id);
        if (!$invoice) {
            show_404();
        }

        $items  = $this->invoices_m->get_invoice_items($id);
        $client = $this->invoices_m->get_client($invoice->client_id);

        $this->load->library('pdf'); // your PDF library wrapper
        $html = $this->load->view('finance/invoices/pdf_template', [
            'invoice' => $invoice,
            'items'   => $items,
            'client'  => $client,
        ], true);

        $this->pdf->generate($html, 'Invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function get_invoice_data()
    {
        if (!$this->_is_ajax()) {
            $this->_forbidden();
        }

        $id      = (int) $this->input->get('id');
        $invoice = $this->invoices_m->get_invoice($id);

        if (!$invoice) {
            $this->_json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $this->_json(['success' => true, 'invoice' => $invoice]);
    }

    public function update_status()
    {
        $this->_guard('edit');

        if (!$this->_is_ajax()) {
            $this->_forbidden();
        }

        $id     = (int) $this->input->post('id');
        $status = $this->input->post('status');
        $allowed = ['draft', 'sent', 'viewed', 'partial', 'paid', 'overdue', 'cancelled'];

        if (!in_array($status, $allowed)) {
            $this->_json(['success' => false, 'message' => 'Invalid status.']);
        }

        $updated = $this->invoices_m->update_status($id, $status);
        $this->_json(['success' => (bool) $updated, 'message' => $updated ? 'Status updated.' : 'Update failed.']);
    }

    private function _parse_line_items()
    {
        $names      = $this->input->post('item_name');
        $descs      = $this->input->post('item_description');
        $units      = $this->input->post('item_unit');
        $quantities = $this->input->post('item_quantity');
        $prices     = $this->input->post('item_unit_price');
        $disc_types = $this->input->post('item_discount_type');
        $disc_vals  = $this->input->post('item_discount_amount');
        $tax_rates  = $this->input->post('item_tax_rate');

        if (empty($names) || !is_array($names)) {
            return [];
        }

        $items = [];
        foreach ($names as $i => $name) {
            if (trim($name) === '') {
                continue;
            }

            $qty       = (float) ($quantities[$i] ?? 1);
            $price     = (float) ($prices[$i]     ?? 0);
            $disc_type = $disc_types[$i] ?? 'none';
            $disc_val  = (float) ($disc_vals[$i]  ?? 0);
            $tax_rate  = (float) ($tax_rates[$i]  ?? 0);
            $gross      = $qty * $price;
            $disc_amt   = ($disc_type === 'percent')
                            ? $gross * ($disc_val / 100)
                            : ($disc_type === 'fixed' ? $disc_val : 0);
            $line_total = $gross - $disc_amt;
            $tax_amt    = $line_total * ($tax_rate / 100);
            $items[] = [
                'sort_order'      => $i,
                'item_name'       => $name,
                'description'     => $descs[$i]      ?? null,
                'unit'            => $units[$i]       ?? null,
                'quantity'        => $qty,
                'unit_price'      => $price,
                'discount_type'   => $disc_type,
                'discount_amount' => $disc_val,
                'tax_rate'        => $tax_rate,
                'tax_amount'      => round($tax_amt, 2),
                'line_total'      => round($line_total, 2),
            ];
        }

        return $items;
    }
}