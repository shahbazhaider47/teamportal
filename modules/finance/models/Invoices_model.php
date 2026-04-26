<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Invoices Model
 * Module  : finance
 * Path    : modules/finance/models/Invoices_model.php
 *
 * Covers  : fin_invoices, fin_invoice_items, fin_payments,
 *           fin_payment_allocations, fin_transactions
 */
class Invoices_model extends CI_Model
{
    const T_INVOICES     = 'fin_invoices';
    const T_ITEMS        = 'fin_invoice_items';
    const T_PAYMENTS     = 'fin_payments';
    const T_ALLOCATIONS  = 'fin_payment_allocations';
    const T_TRANSACTIONS = 'fin_transactions';
    const T_CLIENTS      = 'crm_clients';

    public function __construct()
    {
        parent::__construct();
    }

    /* =========================================================
     * INVOICES – READ
     * ======================================================= */

    public function get_invoices($limit = 20, $offset = 0)
    {
        return $this->db
            ->select('i.*, c.practice_legal_name AS client_name')
            ->from(self::T_INVOICES . ' i')
            ->join(self::T_CLIENTS . ' c', 'c.id = i.client_id', 'left')
            ->where('i.deleted_at IS NULL')
            ->order_by('i.created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->result();
    }

    public function count_invoices()
    {
        return $this->db
            ->from(self::T_INVOICES . ' i')
            ->where('i.deleted_at IS NULL')
            ->count_all_results();
    }

    public function get_invoice($id)
    {
        return $this->db
            ->select('i.*, c.practice_name AS client_name')
            ->from(self::T_INVOICES . ' i')
            ->join(self::T_CLIENTS . ' c', 'c.id = i.client_id', 'left')
            ->where('i.id', (int) $id)
            ->where('i.deleted_at IS NULL')
            ->get()
            ->row();
    }

    /* =========================================================
     * INVOICES – WRITE
     * ======================================================= */

    public function create_invoice(array $data, array $items)
    {
        $this->db->trans_begin();

        $totals = $this->_calculate_totals(
            $items,
            $data['discount_amount'] ?? 0,
            $data['tax_rate'] ?? 0
        );

        $data = array_merge($data, $totals);

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = get_staff_user_id();
        $data['status']     = $data['status'] ?? 'draft';

        $this->db->insert(self::T_INVOICES, $data);
        $invoice_id = $this->db->insert_id();

        if (!$invoice_id) {
            $this->db->trans_rollback();
            return false;
        }

        if (!$this->_save_items($invoice_id, $items, false)) {
            $this->db->trans_rollback();
            return false;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return $invoice_id;
    }

    public function update_invoice($id, array $data, array $items)
    {
        $this->db->trans_begin();

        $totals = $this->_calculate_totals(
            $items,
            $data['discount_amount'] ?? 0,
            $data['tax_rate'] ?? 0
        );

        $data = array_merge($data, $totals);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', (int) $id)
                 ->update(self::T_INVOICES, $data);

        $this->db->where('invoice_id', (int) $id)
                 ->delete(self::T_ITEMS);

        if (!$this->_save_items($id, $items, false)) {
            $this->db->trans_rollback();
            return false;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;
    }

    public function delete_invoice($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->where('deleted_at IS NULL')
            ->update(self::T_INVOICES, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => get_staff_user_id(),
            ]);
    }

    public function update_status($id, $status)
    {
        return $this->db
            ->where('id', (int) $id)
            ->where('deleted_at IS NULL')
            ->update(self::T_INVOICES, [
                'status'     => $status,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => get_staff_user_id(),
            ]);
    }

    public function mark_as_paid($id)
    {
        $invoice = $this->get_invoice($id);

        if (!$invoice) {
            return false;
        }

        return $this->db
            ->where('id', (int) $id)
            ->update(self::T_INVOICES, [
                'status'      => 'paid',
                'paid_amount' => $invoice->total_amount,
                'balance_due' => 0.00,
                'updated_at'  => date('Y-m-d H:i:s'),
                'updated_by'  => get_staff_user_id(),
            ]);
    }

    public function duplicate_invoice($id)
    {
        $invoice = $this->get_invoice($id);

        if (!$invoice) {
            return false;
        }

        $items = $this->get_invoice_items($id);

        $new_data = (array) $invoice;

        unset(
            $new_data['id'],
            $new_data['client_name']
        );

        $new_data['invoice_number'] = $this->generate_invoice_number();
        $new_data['invoice_date']   = date('Y-m-d');
        $new_data['status']         = 'draft';
        $new_data['paid_amount']    = 0;
        $new_data['balance_due']    = $invoice->total_amount;
        $new_data['created_by']     = get_staff_user_id();
        $new_data['created_at']     = date('Y-m-d H:i:s');
        $new_data['updated_at']     = null;

        $item_rows = array_map(function ($item) {
            $row = (array) $item;
            unset($row['id']);
            return $row;
        }, $items);

        return $this->create_invoice($new_data, $item_rows);
    }

    public function send_invoice($id, $to, $subject, $message)
    {
        $this->load->library('email');

        $this->email->clear();
        $this->email->from(
            company_setting('company_email'),
            company_setting('company_name')
        );

        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message);

        if (!$this->email->send()) {
            log_message(
                'error',
                'Invoice email failed: ' . $this->email->print_debugger()
            );
            return false;
        }

        $this->db
            ->where('id', (int) $id)
            ->update(self::T_INVOICES, [
                'status'  => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
            ]);

        return true;
    }

    /* =========================================================
     * LINE ITEMS
     * ======================================================= */

    public function get_invoice_items($invoice_id)
    {
        return $this->db
            ->where('invoice_id', (int) $invoice_id)
            ->order_by('sort_order', 'ASC')
            ->get(self::T_ITEMS)
            ->result();
    }

    /* =========================================================
     * PAYMENTS
     * ======================================================= */

    public function record_payment($invoice_id, array $payment_data)
    {
        $this->db->trans_begin();

        $this->db->insert(self::T_PAYMENTS, $payment_data);
        $payment_id = $this->db->insert_id();

        if (!$payment_id) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->insert(self::T_ALLOCATIONS, [
            'payment_id'       => $payment_id,
            'invoice_id'       => (int) $invoice_id,
            'allocated_amount' => $payment_data['amount'],
            'created_by'       => $payment_data['created_by'],
            'allocated_at'     => date('Y-m-d H:i:s'),
        ]);

        $invoice = $this->get_invoice($invoice_id);

        $paid_amount = $invoice->paid_amount + $payment_data['amount'];
        $balance_due = max(0, $invoice->total_amount - $paid_amount);

        $status = ($balance_due <= 0) ? 'paid' : 'partial';

        $this->db->where('id', (int) $invoice_id)
                 ->update(self::T_INVOICES, [
                     'paid_amount' => $paid_amount,
                     'balance_due' => $balance_due,
                     'status'      => $status,
                     'updated_at'  => date('Y-m-d H:i:s'),
                 ]);

        $this->db->insert(self::T_TRANSACTIONS, [
            'transaction_type' => 'income',
            'direction'        => 'credit',
            'bank_account_id'  => $payment_data['bank_account_id'],
            'invoice_id'       => (int) $invoice_id,
            'payment_id'       => $payment_id,
            'currency'         => $payment_data['currency'] ?? 'USD',
            'exchange_rate'    => $payment_data['exchange_rate'] ?? 1,
            'amount'           => $payment_data['amount'],
            'transaction_date' => $payment_data['payment_date'],
            'reference_no'     => $payment_data['reference_no'],
            'created_by'       => $payment_data['created_by'],
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return $payment_id;
    }

    public function get_invoice_payments($invoice_id)
    {
        return $this->db
            ->select('p.*, pa.allocated_amount')
            ->from(self::T_PAYMENTS . ' p')
            ->join(self::T_ALLOCATIONS . ' pa', 'pa.payment_id = p.id')
            ->where('pa.invoice_id', (int) $invoice_id)
            ->where('p.deleted_at IS NULL')
            ->order_by('p.payment_date', 'DESC')
            ->get()
            ->result();
    }

    /* =========================================================
     * CLIENTS
     * ======================================================= */

/**
 * Get single client by ID
 */
public function get_client($client_id)
{
    return $this->db
        ->select('id, practice_name, primary_email, primary_phone, address')
        ->from(self::T_CLIENTS)
        ->where('id', (int) $client_id)
        ->limit(1)
        ->get()
        ->row();
}

    public function get_clients_dropdown()
    {
        return $this->db
            ->select('id, practice_name AS name')
            ->where('is_active', 1)
            ->order_by('practice_name', 'ASC')
            ->get(self::T_CLIENTS)
            ->result();
    }

    public function generate_invoice_number()
    {
        $prefix = company_setting('invoice_prefix') ?: 'INV';
        $year   = date('Y');

        $last = $this->db
            ->select('invoice_number')
            ->like('invoice_number', $prefix . '-' . $year, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get(self::T_INVOICES)
            ->row();

        $seq = 1;

        if ($last) {
            $parts = explode('-', $last->invoice_number);
            $seq   = ((int) end($parts)) + 1;
        }

        return $prefix . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /* =========================================================
     * HELPERS
     * ======================================================= */

    private function _save_items($invoice_id, array $items, $delete_first = true)
    {
        if ($delete_first) {
            $this->db->where('invoice_id', (int) $invoice_id)
                     ->delete(self::T_ITEMS);
        }

        foreach ($items as $item) {
            $item['invoice_id'] = (int) $invoice_id;

            $this->db->insert(self::T_ITEMS, $item);

            if ($this->db->affected_rows() === 0) {
                return false;
            }
        }

        return true;
    }

    private function _calculate_totals(array $items, $discount = 0, $tax_rate = 0)
    {
        $subtotal = 0;
        $items_tax = 0;

        foreach ($items as $item) {
            $subtotal += (float) $item['line_total'];
            $items_tax += (float) ($item['tax_amount'] ?? 0);
        }

        $after_discount = $subtotal - (float) $discount;
        $invoice_tax = $after_discount * ((float) $tax_rate / 100);
        $total_tax = $items_tax + $invoice_tax;
        $total = $after_discount + $invoice_tax;

        return [
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => round($discount, 2),
            'tax_amount'      => round($total_tax, 2),
            'total_amount'    => round($total, 2),
            'balance_due'     => round($total, 2),
            'paid_amount'     => 0.00,
        ];
    }

    
public function get_summary()
{
    $today = date('Y-m-d');
    $in30  = date('Y-m-d', strtotime('+30 days'));

    // ── Status counts ─────────────────────────────────────
    $rows = $this->db
        ->select('status, COUNT(*) AS cnt')
        ->where('deleted_at IS NULL')
        ->group_by('status')
        ->get(self::T_INVOICES)
        ->result();

    $status_counts = [
        'all'       => 0,
        'draft'     => 0,
        'sent'      => 0,
        'viewed'    => 0,
        'partial'   => 0,
        'paid'      => 0,
        'overdue'   => 0,
        'cancelled' => 0,
    ];

    foreach ($rows as $r) {
        if (array_key_exists($r->status, $status_counts)) {
            $status_counts[$r->status] = (int)$r->cnt;
        }
        $status_counts['all'] += (int)$r->cnt;
    }

    // Overdue count (due_date passed, still unpaid)
    $status_counts['overdue'] = (int)$this->db
        ->where('deleted_at IS NULL')
        ->where('due_date <', $today)
        ->where('status !=', 'paid')
        ->where('status !=', 'cancelled')
        ->count_all_results(self::T_INVOICES);

    // ── Payment totals ────────────────────────────────────
    $outstanding = $this->db
        ->select_sum('balance_due')
        ->where('deleted_at IS NULL')
        ->where('status !=', 'paid')
        ->where('status !=', 'cancelled')
        ->get(self::T_INVOICES)
        ->row();

    $due_today = $this->db
        ->select_sum('balance_due')
        ->where('deleted_at IS NULL')
        ->where('due_date', $today)
        ->where('status !=', 'paid')
        ->where('status !=', 'cancelled')
        ->get(self::T_INVOICES)
        ->row();

    $due_30 = $this->db
        ->select_sum('balance_due')
        ->where('deleted_at IS NULL')
        ->where('due_date >=', $today)
        ->where('due_date <=', $in30)
        ->where('status !=', 'paid')
        ->where('status !=', 'cancelled')
        ->get(self::T_INVOICES)
        ->row();

    $overdue = $this->db
        ->select_sum('balance_due')
        ->where('deleted_at IS NULL')
        ->where('due_date <', $today)
        ->where('status !=', 'paid')
        ->where('status !=', 'cancelled')
        ->get(self::T_INVOICES)
        ->row();

    $avg = $this->db
        ->select('AVG(DATEDIFF(paid_at, invoice_date)) AS avg_days')
        ->where('deleted_at IS NULL')
        ->where('status', 'paid')
        ->where('paid_at IS NOT NULL')
        ->where('invoice_date IS NOT NULL')
        ->get(self::T_INVOICES)
        ->row();

    return [
        // status card counts  → used as $summary['counts'][$key]
        'counts' => $status_counts,

        // payment strip totals → used as $summary['totals'][...]
        'totals' => [
            'total_outstanding' => (float)($outstanding->balance_due ?? 0),
            'due_today'         => (float)($due_today->balance_due   ?? 0),
            'due_30_days'       => (float)($due_30->balance_due      ?? 0),
            'total_overdue'     => (float)($overdue->balance_due     ?? 0),
            'avg_days_to_pay'   => (int)round((float)($avg->avg_days ?? 0)),
        ],
    ];
}


/**
 * Get unpaid / partially paid invoices for a client
 * Used in the invoice form outstanding balance panel
 */
public function get_client_outstanding_invoices($client_id)
{
    $rows = $this->db
        ->select('id, invoice_number, invoice_date, due_date, total_amount, paid_amount, balance_due, status')
        ->from(self::T_INVOICES)
        ->where('client_id', (int)$client_id)
        ->where('deleted_at IS NULL')
        ->where_in('status', ['sent', 'viewed', 'partial', 'overdue'])
        ->order_by('due_date', 'ASC')
        ->get()
        ->result();

    $total_outstanding = 0;
    foreach ($rows as $r) {
        $total_outstanding += (float)$r->balance_due;
    }

    return [
        'invoices'          => $rows,
        'total_outstanding' => $total_outstanding,
    ];
}

}
