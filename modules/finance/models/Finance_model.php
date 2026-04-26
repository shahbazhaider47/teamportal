<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Finance_model extends CI_Model
{
    /* ── Table constants ──────────────────────────────────── */
    const T_BANK_ACCOUNTS      = 'fin_bank_accounts';
    const T_INVOICES           = 'fin_invoices';
    const T_INVOICE_ITEMS      = 'fin_invoice_items';
    const T_CREDIT_NOTES       = 'fin_credit_notes';
    const T_PAYMENTS           = 'fin_payments';
    const T_PAYMENT_ALLOC      = 'fin_payment_allocations';
    const T_EXPENSE_CATS       = 'fin_expense_categories';
    const T_EXPENSES           = 'fin_expenses';
    const T_TRANSACTIONS       = 'fin_transactions';
    const T_BANK_TRANSACTIONS  = 'fin_bank_transactions';
    const T_RECONCILIATIONS    = 'fin_reconciliations';
    const T_SETTINGS           = 'system_settings';

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Get all CRM settings as key => value
     */
    public function get_all()
    {
        $this->db->where('group_key', 'finance');
        $rows = $this->db->get(self::T_SETTINGS)->result_array();
    
        $data = [];
        foreach ($rows as $row) {
            $data[$row['key']] = $row['value'];
        }
    
        return $data;
    }

    /**
     * Save multiple settings (insert/update)
     */
    public function save_settings($settings = [])
    {
        if (empty($settings)) return false;
    
        foreach ($settings as $key => $value) {
    
            $exists = $this->db
                ->where('group_key', 'finance')
                ->where('key', $key)
                ->get(self::T_SETTINGS)
                ->row();
    
            $data = [
                'value'      => is_array($value) ? json_encode($value) : $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
    
            if ($exists) {
                $this->db->where('id', $exists->id)->update(self::T_SETTINGS, $data);
            } else {
                $data['key']        = $key;
                $data['group_key']  = 'finance';
                $data['created_at'] = date('Y-m-d H:i:s');
    
                $this->db->insert(self::T_SETTINGS, $data);
            }
        }
    
        return true;
    }
    
    /* =========================================================
     * DASHBOARD KPIs
     * ======================================================= */

    /**
     * Returns the top-level KPI figures for the dashboard header cards.
     */
    public function get_dashboard_kpis()
    {
        $now        = date('Y-m-d');
        $month_from = date('Y-m-01');
        $month_to   = date('Y-m-t');

        // Total outstanding (balance_due on active invoices)
        $outstanding = $this->db
            ->select('COALESCE(SUM(balance_due), 0) AS val')
            ->from(self::T_INVOICES)
            ->where_in('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->where('deleted_at IS NULL')
            ->get()->row()->val;

        // Revenue collected this month
        $revenue_this_month = $this->db
            ->select('COALESCE(SUM(amount), 0) AS val')
            ->from(self::T_PAYMENTS)
            ->where('status', 'completed')
            ->where('payment_date >=', $month_from)
            ->where('payment_date <=', $month_to)
            ->where('deleted_at IS NULL')
            ->get()->row()->val;

        // Expenses this month
        $expenses_this_month = $this->db
            ->select('COALESCE(SUM(amount + tax_amount), 0) AS val')
            ->from(self::T_EXPENSES)
            ->where_in('status', ['approved', 'reimbursed'])
            ->where('expense_date >=', $month_from)
            ->where('expense_date <=', $month_to)
            ->where('deleted_at IS NULL')
            ->get()->row()->val;

        // Net profit this month
        $net_profit = $revenue_this_month - $expenses_this_month;

        // Overdue invoice count
        $overdue_count = $this->db
            ->from(self::T_INVOICES)
            ->where('status', 'overdue')
            ->where('deleted_at IS NULL')
            ->count_all_results();

        // Total invoiced this month
        $invoiced_this_month = $this->db
            ->select('COALESCE(SUM(total_amount), 0) AS val')
            ->from(self::T_INVOICES)
            ->where('invoice_date >=', $month_from)
            ->where('invoice_date <=', $month_to)
            ->where('deleted_at IS NULL')
            ->get()->row()->val;

        // Total bank balance (active accounts)
        $total_bank_balance = $this->db
            ->select('COALESCE(SUM(current_balance), 0) AS val')
            ->from(self::T_BANK_ACCOUNTS)
            ->where('status', 'active')
            ->where('deleted_at IS NULL')
            ->get()->row()->val;

        return [
            'outstanding'          => (float) $outstanding,
            'revenue_this_month'   => (float) $revenue_this_month,
            'expenses_this_month'  => (float) $expenses_this_month,
            'net_profit'           => (float) $net_profit,
            'overdue_count'        => (int)   $overdue_count,
            'invoiced_this_month'  => (float) $invoiced_this_month,
            'total_bank_balance'   => (float) $total_bank_balance,
        ];
    }

    /* =========================================================
     * INVOICE STATUS BREAKDOWN (for donut chart)
     * ======================================================= */

    public function get_invoice_status_counts()
    {
        $rows = $this->db
            ->select('status, COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS total')
            ->from(self::T_INVOICES)
            ->where('deleted_at IS NULL')
            ->group_by('status')
            ->get()->result_array();

        // Normalise into a keyed array
        $result = [
            'draft'     => ['cnt' => 0, 'total' => 0],
            'sent'      => ['cnt' => 0, 'total' => 0],
            'viewed'    => ['cnt' => 0, 'total' => 0],
            'partial'   => ['cnt' => 0, 'total' => 0],
            'paid'      => ['cnt' => 0, 'total' => 0],
            'overdue'   => ['cnt' => 0, 'total' => 0],
            'cancelled' => ['cnt' => 0, 'total' => 0],
        ];

        foreach ($rows as $r) {
            if (isset($result[$r['status']])) {
                $result[$r['status']] = [
                    'cnt'   => (int)   $r['cnt'],
                    'total' => (float) $r['total'],
                ];
            }
        }

        return $result;
    }

    /* =========================================================
     * MONTHLY REVENUE vs EXPENSES TREND
     * ======================================================= */

    /**
     * Returns $months months of data ending this month.
     * Each item: ['month' => 'Jan 25', 'revenue' => x, 'expenses' => y]
     */
    public function get_monthly_trend($months = 6)
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $from  = date('Y-m-01', strtotime("-{$i} months"));
            $to    = date('Y-m-t',  strtotime("-{$i} months"));
            $label = date('M y',    strtotime($from));

            $revenue = $this->db
                ->select('COALESCE(SUM(amount), 0) AS val')
                ->from(self::T_PAYMENTS)
                ->where('status', 'completed')
                ->where('payment_date >=', $from)
                ->where('payment_date <=', $to)
                ->where('deleted_at IS NULL')
                ->get()->row()->val;

            $expenses = $this->db
                ->select('COALESCE(SUM(amount + tax_amount), 0) AS val')
                ->from(self::T_EXPENSES)
                ->where_in('status', ['approved', 'reimbursed'])
                ->where('expense_date >=', $from)
                ->where('expense_date <=', $to)
                ->where('deleted_at IS NULL')
                ->get()->row()->val;

            $trend[] = [
                'month'    => $label,
                'revenue'  => (float) $revenue,
                'expenses' => (float) $expenses,
            ];
        }

        return $trend;
    }

    /* =========================================================
     * RECENT INVOICES
     * ======================================================= */

    public function get_recent_invoices($limit = 8)
    {
        return $this->db
            ->select('id, invoice_number, client_id, invoice_date, due_date,
                      total_amount, paid_amount, balance_due, status')
            ->from(self::T_INVOICES)
            ->where('deleted_at IS NULL')
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    /* =========================================================
     * RECENT PAYMENTS
     * ======================================================= */

    public function get_recent_payments($limit = 8)
    {
        return $this->db
            ->select('p.id, p.client_id, p.payment_date, p.amount,
                      p.payment_mode, p.reference_no, p.status')
            ->from(self::T_PAYMENTS . ' p')
            ->where('p.deleted_at IS NULL')
            ->where('p.status', 'completed')
            ->order_by('p.created_at', 'DESC')
            ->limit($limit)
            ->get()->result_array();
    }

    /* =========================================================
     * OVERDUE INVOICES
     * ======================================================= */

    public function get_overdue_invoices($limit = 5)
    {
        return $this->db
            ->select('id, invoice_number, client_id, due_date,
                      balance_due, status')
            ->from(self::T_INVOICES)
            ->where('status', 'overdue')
            ->where('deleted_at IS NULL')
            ->order_by('due_date', 'ASC')
            ->limit($limit)
            ->get()->result_array();
    }

    /* =========================================================
     * BANK ACCOUNTS SUMMARY
     * ======================================================= */

    public function get_bank_accounts_summary()
    {
        return $this->db
            ->select('id, account_name, bank_name, account_type,
                      current_balance, status, is_primary, is_default')
            ->from(self::T_BANK_ACCOUNTS)
            ->where('status', 'active')
            ->where('deleted_at IS NULL')
            ->order_by('is_primary', 'DESC')
            ->order_by('account_name', 'ASC')
            ->get()->result_array();
    }

    /* =========================================================
     * EXPENSE BY CATEGORY (current month)
     * ======================================================= */

    public function get_expense_by_category()
    {
        $from = date('Y-m-01');
        $to   = date('Y-m-t');

        return $this->db
            ->select('ec.category_name,
                      COALESCE(SUM(e.amount + e.tax_amount), 0) AS total')
            ->from(self::T_EXPENSES . ' e')
            ->join(self::T_EXPENSE_CATS . ' ec', 'ec.id = e.category_id', 'left')
            ->where_in('e.status', ['approved', 'reimbursed'])
            ->where('e.expense_date >=', $from)
            ->where('e.expense_date <=', $to)
            ->where('e.deleted_at IS NULL')
            ->group_by('e.category_id')
            ->order_by('total', 'DESC')
            ->limit(6)
            ->get()->result_array();
    }

    /* =========================================================
     * UNMATCHED BANK TRANSACTION COUNT
     * ======================================================= */

    public function get_unmatched_transactions_count()
    {
        return $this->db
            ->from(self::T_BANK_TRANSACTIONS)
            ->where('status', 'unmatched')
            ->count_all_results();
    }
}