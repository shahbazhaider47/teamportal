<?php
defined('BASEPATH') or exit('No direct script access allowed');

class My extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Repoint to new models
        $this->load->model('PayrollLoansModel',   'loans');
        $this->load->model('PayrollAdvancesModel','adv');
        $this->load->model('Settings_model');
        $this->load->helper(['url','form']);
        $this->load->model('PayrollDetailsModel', 'details');
    }

    private function current_user_id(): int
    {
        if (function_exists('get_staff_user_id')) {
            $id = (int) get_staff_user_id();
            if ($id > 0) return $id;
        }
        $id = (int) ($this->session->userdata('staff_user_id') ?: 0);
        if ($id > 0) return $id;

        $id = (int) ($this->session->userdata('user_id') ?: 0);
        return $id > 0 ? $id : 0;
    }

public function index()
{
    if (!staff_can('view_own', 'payroll')) {
        access_denied('dashboard');
    }

    // Resolve current user
    $uid = $this->current_user_id();
    if ($uid <= 0) {
        set_alert('danger', 'Invalid session.');
        return redirect('/');
    }

    // ---- Pull last 12 payslips (newest first) ----
    $pdTbl = $this->db->dbprefix('payroll_details');
    if (!$this->db->table_exists($pdTbl)) {
        set_alert('warning', 'Payroll not initialized yet.');
        return redirect('/');
    }

    // We’ll grab raw rows, then compute labels/series in PHP
    $rows = $this->db->from($pdTbl . ' pd')
        ->where('pd.user_id', (int)$uid)
        ->order_by('COALESCE(pd.pay_date, pd.period_end)', 'DESC', false)
        ->order_by('pd.id', 'DESC')
        ->limit(12)
        ->get()->result_array();

    // Build series (reverse so chart shows oldest -> newest)
    $labels       = [];
    $grossSeries  = [];
    $dedSeries    = [];
    $netSeries    = [];

    $rows = array_reverse($rows);
    foreach ($rows as $r) {
        $anchor = !empty($r['pay_date']) ? $r['pay_date'] : ($r['period_end'] ?? null);
        $labels[] = $anchor ? date('M Y', strtotime($anchor)) : '—';

        // Ensure gross/total_deductions/net are computed even if not stored
        $gross = isset($r['gross_pay']) ? (float)$r['gross_pay'] : (
            (float)($r['basic_salary'] ?? 0)
          + (float)($r['allowances_total'] ?? 0)
          + (float)($r['overtime_amount'] ?? 0)
          + (float)($r['bonus_amount'] ?? 0)
          + (float)($r['commission_amount'] ?? 0)
          + (float)($r['other_earnings'] ?? 0)
          + (float)($r['arrears_amount'] ?? 0)
        );

        $pf_ded = isset($r['pf_deduction']) ? (float)$r['pf_deduction'] : (float)($r['pf_employee'] ?? 0);
        $deductions = isset($r['total_deductions']) ? (float)$r['total_deductions'] : (
              (float)($r['deductions_total'] ?? 0)
            + (float)($r['leave_deduction'] ?? 0)
            + (float)($r['tax_amount'] ?? 0)
            + $pf_ded
            + (float)($r['loan_total_deduction'] ?? 0)
            + (float)($r['advance_total_deduction'] ?? 0)
        );

        $net = isset($r['net_pay']) ? (float)$r['net_pay'] : max(0, $gross - $deductions);

        $grossSeries[] = round($gross, 2);
        $dedSeries[]   = round($deductions, 2);
        $netSeries[]   = round($net, 2);
    }

    // ---- Loan & Advance snapshots (optional) ----
    $loanAgg = $this->db->select('
            COALESCE(SUM(loan_taken),0)  AS loan_taken_total,
            COALESCE(SUM(total_paid),0)  AS loan_total_paid,
            COALESCE(SUM(balance),0)     AS loan_balance_total
        ', false)
        ->from($this->db->dbprefix('payroll_loans'))
        ->where('user_id', (int)$uid)
        ->where_in('status', ['active','approved','scheduled','requested']) // tweak if needed
        ->get()->row_array() ?: [];

    $advAgg = $this->db->select('
            COALESCE(SUM(amount),0) AS advance_total,
            COALESCE(SUM(paid),0)   AS advance_paid,
            COALESCE(SUM(balance),0)AS advance_balance
        ', false)
        ->from($this->db->dbprefix('payroll_advances'))
        ->where('user_id', (int)$uid)
        ->where_in('status', ['requested','approved','scheduled','paid']) // tweak if needed
        ->get()->row_array() ?: [];

    // Pass data to view; encode chart arrays once in PHP
    $this->load->view('layouts/master', [
        'page_title' => 'My Payroll',
        'subview'    => 'payroll/my/index',
        'view_data'  => [
            'page_title' => 'My Payroll Dashboard',
            'chart_labels_json'  => json_encode($labels),
            'chart_gross_json'   => json_encode($grossSeries),
            'chart_deduct_json'  => json_encode($dedSeries),
            'chart_net_json'     => json_encode($netSeries),
            'loanAgg'            => $loanAgg,
            'advAgg'             => $advAgg,
        ],
    ]);
}

    /* ───────────────────────── My Loans ───────────────────────── */

    public function my_loans()
    {
        $user_id = $this->current_user_id();

        // Settings (group = 'payroll')
        $S            = $this->Settings_model->get_group('payroll') ?: [];
        $allowRequest = ($S['payroll_allow_loan'] ?? 'no') === 'yes';
        $eligibility  = $S['payroll_loan_eligibility'] ?? 'on_joining';   // on_joining|after_probation|after_6_months|after_1_year
        $limitCode    = $S['payroll_loan_limit']       ?? 'half_salary';  // half_salary|full_salary|two_salaries|any_amount

        // Basics + eligibility
        $basics               = $this->loans->user_basics($user_id);
        [$eligible, $eligMsg] = $this->loans->meets_loan_eligibility($user_id, $eligibility);
        $baseSalary           = (float)($basics['base_salary'] ?? 0.0);

        log_message(
            'debug',
            'payroll/my_loans: user='.$user_id.
            ' emp_joining='.($basics['emp_joining'] ?? 'null').
            ' base_salary='.number_format($baseSalary, 2).
            ' rule='.$eligibility.
            ' eligible='.($eligible ? '1':'0').
            ' msg='.$eligMsg
        );

        // Cap (null = unlimited)
        $maxAmount = null;
        if ($baseSalary > 0) {
            switch ($limitCode) {
                case 'half_salary':  $maxAmount = $baseSalary * 0.5; break;
                case 'full_salary':  $maxAmount = $baseSalary * 1.0; break;
                case 'two_salaries': $maxAmount = $baseSalary * 2.0; break;
                case 'any_amount':   $maxAmount = null; break;
                default:             $maxAmount = $baseSalary * 0.5; break;
            }
        }

        $view = [
            'page_title'      => 'My Loans',
            'table_id'        => 'myLoansTable',
            'loans'           => $this->loans->loans_for_user($user_id),
            'can_request'     => ($allowRequest && $eligible),
            'eligibility_msg' => $allowRequest ? $eligMsg : 'Loan requests are disabled by the system.',
            'max_amount'      => $maxAmount,
            'limit_code'      => $limitCode,
            'base_salary'     => $baseSalary,
        ];

        $layout_data = [
            'page_title' => 'My Loans',
            'subview'    => 'payroll/my/my_loans',
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function request_loan_submit()
    {
        $user_id = $this->current_user_id();

        $S    = $this->Settings_model->get_group('payroll') ?: [];
        $rule = $S['payroll_loan_eligibility'] ?? 'on_joining';

        if (($S['payroll_allow_loan'] ?? 'no') !== 'yes') {
            set_alert('danger', 'Loan requests are disabled.');
            return redirect('payroll/my/my_loans');
        }

        [$eligible, $eligMsg] = $this->loans->meets_loan_eligibility($user_id, $rule);
        if (!$eligible) {
            set_alert('danger', $eligMsg ?: 'You are not yet eligible to request a loan.');
            return redirect('payroll/my/my_loans');
        }

        // Inputs
        $loan_taken          = (float)$this->input->post('loan_taken', true);
        $total_installments  = (int)$this->input->post('total_installments', true);
        $monthly_installment = (float)$this->input->post('monthly_installment', true);
        $payback_type        = (string)$this->input->post('payback_type', true);

        $allowed = ['monthly','quarterly','from_salary','custom'];
        if (!in_array($payback_type, $allowed, true)) {
            $payback_type = 'monthly';
        }
        if ($loan_taken <= 0) {
            set_alert('danger', 'Please enter a valid loan amount.');
            return redirect('payroll/my/my_loans');
        }

        // Cap enforcement
        $basics     = $this->loans->user_basics($user_id);
        $baseSalary = (float)($basics['base_salary'] ?? 0.0);
        $limitCode  = $S['payroll_loan_limit'] ?? 'half_salary';

        $maxAmount = null;
        if ($baseSalary > 0) {
            switch ($limitCode) {
                case 'half_salary':  $maxAmount = $baseSalary * 0.5; break;
                case 'full_salary':  $maxAmount = $baseSalary * 1.0; break;
                case 'two_salaries': $maxAmount = $baseSalary * 2.0; break;
                case 'any_amount':   $maxAmount = null; break;
                default:             $maxAmount = $baseSalary * 0.5; break;
            }
        }

        if ($maxAmount !== null && $loan_taken > $maxAmount) {
            set_alert('warning', 'Requested amount exceeds your current limit (max ' . number_format($maxAmount, 2) . ').');
            return redirect('payroll/my/my_loans');
        }

        // Optional auto-calc
        if (in_array($payback_type, ['monthly','quarterly'], true)
            && $monthly_installment <= 0
            && $loan_taken > 0
            && $total_installments > 0) {
            $monthly_installment = $loan_taken / $total_installments;
            if ($payback_type === 'quarterly') {
                $monthly_installment /= 3;
            }
        }

        $payload = [
            'user_id'            => (int)$user_id,
            'loan_taken'         => $loan_taken,
            'payback_type'       => $payback_type,
            'total_installments' => max(0, $total_installments),
            'monthly_installment'=> max(0, $monthly_installment),
            'current_installment'=> 0,
            'total_paid'         => 0,
            'balance'            => $loan_taken,
            'start_date'         => null,
            'end_date'           => null,
            'status'             => 'requested',
            'notes'              => (string)$this->input->post('notes', true),
        ];

        $ok = $this->loans->save_loan($payload, null);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Loan request submitted.' : 'Failed to submit loan request.');
        return redirect('payroll/my/my_loans');
    }

    /* ───────────────────────── My Advances ───────────────────────── */

    public function my_advances()
    {
        $user_id = $this->current_user_id();
        if ($user_id <= 0) {
            access_denied('payroll');
            return;
        }

        $view = [
            'page_title'           => 'My Advances',
            'table_id'             => 'myAdvancesTable',
            'advances'             => $this->adv->advances_for_user($user_id, true),
            'can_request_advance'  => true,
        ];

        $layout_data = [
            'page_title' => 'My Advances',
            'subview'    => 'payroll/my/my_advances',
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function request_advance_submit()
    {
        // Require a logged-in staff user (fallbacks)
        if (function_exists('is_staff_logged_in') && !is_staff_logged_in()) {
            if (!$this->session->userdata('user_id') && !$this->session->userdata('staff_user_id')) {
                access_denied('payroll');
                return;
            }
        }

        // Resolve uid
        $user_id = 0;
        if (function_exists('get_staff_user_id')) {
            $user_id = (int) get_staff_user_id();
        }
        if ($user_id <= 0) {
            $user_id = (int) ($this->session->userdata('user_id') ?: 0);
        }
        if ($user_id <= 0) {
            $user_id = (int) ($this->session->userdata('staff_user_id') ?: 0);
        }
        if ($user_id <= 0) {
            set_alert('danger', 'Session error: unable to identify your user.');
            return redirect('payroll/my/my_advances');
        }

        // Settings toggle
        $S = $this->Settings_model->get_group('payroll') ?: [];
        $allowAdvance = ($S['payroll_allow_advance_salary'] ?? 'no') === 'yes';
        if (!$allowAdvance) {
            set_alert('danger', 'Advance requests are disabled.');
            return redirect('payroll/my/my_advances');
        }

        // Inputs
        $amount = (float) $this->input->post('amount', true);
        $notes  = (string) $this->input->post('notes', true);

        if ($amount <= 0) {
            set_alert('danger', 'Please enter a valid amount.');
            return redirect('payroll/my/my_advances');
        }

        // Save
        $ok = $this->adv->save_advance_by_user($user_id, $amount, $notes);

        log_message('debug', 'request_advance_submit uid='.$user_id.' amount='.$amount.' ok=' . ($ok ? '1' : '0'));

        set_alert($ok ? 'success' : 'danger', $ok ? 'Advance request submitted.' : 'Failed to submit advance request.');
        return redirect('payroll/my/my_advances');
    }


public function pay_slip()
{
    if (!staff_can('view_own', 'payroll')) {
        access_denied('dashboard');
    }

    // Current user (try multiple fallbacks)
    $current_user_id = 0;
    if (function_exists('get_staff_user_id')) {
        $current_user_id = (int) get_staff_user_id();
    }
    if ($current_user_id <= 0) {
        $current_user_id = (int) ($this->session->userdata('staff_user_id') ?: 0);
    }
    if ($current_user_id <= 0) {
        $current_user_id = (int) ($this->session->userdata('user_id') ?: 0);
    }

    // Optional payslip id from querystring
    $id = (int) $this->input->get('id', true);

    if ($id > 0) {
        // Explicit payslip id flow
        $row = $this->details->payslip_row($id);
        if (empty($row)) {
            set_alert('danger', 'Payslip not found.');
            // redirect to auto-resolve view, not a dead loop
            return redirect('payroll/my/pay_slip');
        }

        // Enforce ownership if user only has view_own
        if (!staff_can('view_own', 'payroll') ) {
            if ($current_user_id <= 0 || (int)$row['user_id'] !== $current_user_id) {
                access_denied('payroll/my');
            }
        }
    } else {
        // Auto-resolve latest/active payslip for current user
        if ($current_user_id <= 0) {
            set_alert('danger', 'Invalid session.');
            return redirect('payroll/my'); // avoid looping to same URL
        }
    
        $row = $this->details->latest_payslip_for_user($current_user_id);
        if (empty($row)) {
            set_alert('warning', 'Payslip is not available for this month, please check back later.');
            return redirect('payroll/my'); // take them to main payroll page
        }
    }

    $this->load->view('layouts/master', [
        'page_title' => 'Payslip',
        'subview'    => 'payroll/my/pay_slip', // <- keep filename as pay_slip.php
        'view_data'  => [
            'page_title' => 'Payslip',
            'pd'         => $row,
        ],
    ]);
}

  
}