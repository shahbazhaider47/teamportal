<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payroll extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('PayrollDetailsModel', 'details'); // runs, payslips, listings
        $this->load->model('PayrollRunModel',     'prun');    // compute + upsert run
        $this->load->model('PayrollLoansModel',   'loans');   // loans CRUD
        $this->load->model('PayrollAdvancesModel','adv');     // advances CRUD
        $this->load->model('RunAdminModel', 'runadmin');
        $this->load->model('MonthlyInputsModel',  'minp'); // NEW
        $this->load->model('PayrollPfModel', 'pf'); // NEW
        $this->load->model('PayrollIncrementsModel', 'pi'); // NEW
        $this->load->model('PayrollArrearsModel', 'arrears'); // NEW

        $this->load->helper(['url', 'form']);
        $this->load->library('form_validation');
    }

    /* ───────────────────────── Runs Index ───────────────────────── */

    public function index()
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $view = $this->details->runs_index_data();
        $view['table_id'] = 'payrollRunsTable';

        $layout_data = [
            'page_title' => 'Payroll Details',
            'subview'    => 'payroll/runs',
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    // Run summary for modal
    public function run_json($run_id)
    {
        if (!staff_can('view_global', 'payroll') && !staff_can('view_own', 'payroll')) {
            access_denied('payroll');
        }
        $run_id  = (int)$run_id;
        $summary = $this->details->get_run_summary($run_id);
        $this->output->set_content_type('application/json')->set_output(json_encode($summary ?: []));
    }

    // Run details page (employees in the run)
    public function details($run_id)
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $run_id  = (int)$run_id;
        $summary = $this->details->get_run_summary($run_id);
        if (!$summary) {
            set_alert('danger', 'Run not found.');
            return redirect('payroll');
        }

        $rows = $this->details->get_run_rows($run_id);

        $view = [
            'page_title' => 'Payroll Details #'.$run_id,
            'summary'    => $summary,
            'rows'       => $rows,
            'table_id'   => 'payrollRunUsersTable',
            'payElements'=> $this->minp->pay_elements(), // NEW: supplies the select options
        ];

        $layout_data = [
            'page_title' => 'Payroll Details #'.$run_id,
            'subview'    => 'payroll/details',
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    // NEW: handle form POST
    public function save_monthly_inputs()
    {
        if (!staff_can('edit', 'payroll')) { access_denied('payroll'); }
    
        $run_id = (int)$this->input->post('run_id', true);
        $items  = $this->input->post('items', true) ?: [];
    
        if ($run_id <= 0) {
            set_alert('danger', 'Invalid run id.');
            return redirect('payroll');
        }
    
        $ok = $this->minp->save_and_apply($run_id, $items);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Monthly inputs saved & applied.' : 'Failed to save/apply inputs.');
        return redirect('payroll/details/'.$run_id);
    }
    
    // GET JSON: current run admin controls (for modal prefill)
    public function run_controls_json($run_id)
    {
        if (!staff_can('view_global', 'payroll') && !staff_can('view_own', 'payroll')) {
            access_denied('payroll');
        }
        $run_id = (int)$run_id;
        $row = $this->runadmin->get_run_controls($run_id) ?: [];
        $this->output->set_content_type('application/json')->set_output(json_encode($row));
    }
    
    
    // POST: update run admin controls (status, run status, pay date, lock, payment method, paid_by)
    public function update_run_admin()
    {
        if (!staff_can('edit', 'payroll')) {
            access_denied('payroll');
        }
    
        $run_id = (int)$this->input->post('run_id', true);
        if ($run_id <= 0) {
            set_alert('danger', 'Invalid run id.');
            return redirect('payroll');
        }
    
        $payload = [
            'status'         => $this->input->post('status', true),          // active|inactive
            'status_run'     => $this->input->post('status_run', true),      // draft|processed|posted|paid|void
            'pay_date'       => $this->input->post('pay_date', true),        // Y-m-d
            'is_locked'      => $this->input->post('is_locked', true),       // 0/1
            'payment_method' => $this->input->post('payment_method', true),  // bank|cash|cheque|wallet|other
            'paid_by'        => $this->input->post('paid_by', true),         // staff/user id or null
        ];
    
        $ok = $this->runadmin->update_run_meta($run_id, $payload);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Run settings updated.' : 'No changes or invalid input.');
    
        return redirect('payroll/details/' . $run_id);
    }

    // Create/process a payroll run
    public function run()
    {
        if (!staff_can('create', 'payroll')) {
            access_denied('payroll');
        }

        // Inputs
        $scope        = $this->input->post('scope', true) ?: 'all';
        $params       = [
            'department_id' => (int)$this->input->post('department_id', true),
            'team_id'       => (int)$this->input->post('team_id', true),
            'user_ids'      => $this->input->post('user_ids', true) ?: [],
        ];
        $payroll_type = (string)$this->input->post('payroll_type', true) ?: 'regular';
        $pay_period   = (string)$this->input->post('pay_period', true)   ?: 'monthly'; // monthly|semi-monthly|biweekly|weekly|daily|ad-hoc
        $period_start = (string)$this->input->post('period_start', true);
        $period_end   = (string)$this->input->post('period_end', true);
        $pay_date     = (string)$this->input->post('pay_date', true);
        $rounding     = (string)$this->input->post('rounding', true) ?: 'inherit';     // inherit|none|nearest|down|up
        $notes        = (string)$this->input->post('notes', true);

        // Validation
        if (!$period_start || !$period_end || !$pay_date) {
            set_alert('danger', 'Please select period start, period end, and pay date.');
            return redirect('payroll');
        }
        if (strtotime($period_start) === false || strtotime($period_end) === false || strtotime($pay_date) === false) {
            set_alert('danger', 'Invalid date(s) supplied.');
            return redirect('payroll');
        }
        if (strtotime($period_start) > strtotime($period_end)) {
            set_alert('danger', 'Period start cannot be after period end.');
            return redirect('payroll');
        }

        // Resolve employees from scope
        $employees = $this->details->find_employees_for_run($scope, $params);
        
        // Exclude the system/admin user (ID = 1)
        $employees = array_filter($employees, function ($emp) {
            return (int)($emp['id'] ?? 0) !== 1;
        });
        
        if (empty($employees)) {
            set_alert('warning', 'No active employees matched your selection.');
            return redirect('payroll');
        }

        // Persist run
        $meta   = compact('payroll_type','pay_period','period_start','period_end','pay_date','rounding','notes','scope') + $params;
        $result = $this->prun->run_payroll($meta, $employees);

        if (!$result['ok']) {
            set_alert('danger', 'Failed to create payroll run. Nothing was saved.');
            return redirect('payroll');
        }

        $msg = 'Payroll processed: '.$result['inserted'].' created'
             . ($result['updated'] ? (', '.$result['updated'].' updated') : '')
             . " — {$payroll_type}, {$pay_period} ({$period_start} → {$period_end}), pay date {$pay_date}.";
        set_alert('success', $msg);

        return redirect('payroll');
    }

    // NEW: Delete an entire run (all rows with run_id)
    public function delete_run($run_id)
    {
        if (!staff_can('delete', 'payroll')) {
            access_denied('payroll');
        }
        $run_id = (int)$run_id;
        if ($run_id <= 0) {
            set_alert('danger', 'Invalid run ID.');
            return redirect('payroll');
        }
        $ok = $this->details->delete_run($run_id);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Run deleted.' : 'Failed to delete run.');
        return redirect('payroll');
    }

    /* ───────────────────────── Loans (Admin) ───────────────────────── */

    public function loans()
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $view = $this->loans->loans_data();
        $view['table_id'] = 'payrollLoansTable';

        $layout_data = [
            'page_title' => 'Payroll Loans',
            'subview'    => 'payroll/loans',
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function get_loan_json($id)
    {
        if (!staff_can('view_global', 'payroll') && !staff_can('view_own', 'payroll')) {
            access_denied('payroll');
        }
        $loan = $this->loans->get_loan((int)$id) ?: [];
        $this->output->set_content_type('application/json')->set_output(json_encode($loan));
    }

    public function save_loan()
    {
        if (!staff_can('create', 'payroll') && !staff_can('edit', 'payroll')) {
            access_denied('payroll');
        }

        $id = (int)$this->input->post('id');

        // Normalize + save in model
        $payload = [
            'user_id'            => (int)$this->input->post('user_id', true),
            'loan_taken'         => (float)$this->input->post('loan_taken', true),
            'payback_type'       => (string)$this->input->post('payback_type', true),
            'total_installments' => (int)$this->input->post('total_installments', true),
            'monthly_installment'=> (float)$this->input->post('monthly_installment', true),
            'current_installment'=> (int)$this->input->post('current_installment', true),
            'total_paid'         => (float)$this->input->post('total_paid', true),
            'balance'            => null, // computed inside model
            'start_date'         => $this->input->post('start_date', true) ?: null,
            'end_date'           => $this->input->post('end_date', true) ?: null,
            'status'             => $this->input->post('status', true) ?: 'active',
            'notes'              => (string)$this->input->post('notes', true),
        ];

        $ok = $this->loans->save_loan($payload, $id ?: null);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Loan details saved.' : 'Failed to save loan details.');
        return redirect('payroll/loans');
    }

    public function delete_loan($id)
    {
        if (!staff_can('delete', 'payroll')) {
            access_denied('payroll');
        }
        $ok = $this->loans->delete_loan((int)$id);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Loan details deleted.' : 'Failed to delete loan details.');
        return redirect('payroll/loans');
    }

    /* ───────────────────────── Advances (Admin/Self) ───────────────────────── */

    public function advances()
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        // Determine viewer
        $uid = 0;
        if (function_exists('get_staff_user_id')) {
            $uid = (int) get_staff_user_id();
        }
        if ($uid <= 0) {
            $uid = (int) ($this->session->userdata('user_id') ?: 0);
        }
        if ($uid <= 0) {
            $uid = (int) ($this->session->userdata('staff_user_id') ?: 0);
        }

        // Dataset
        if (staff_can('view_global', 'payroll')) {
            $advances  = $this->adv->advances_all(true);
            $pageTitle = 'Payroll Advances';
        } else {
            $advances  = $this->adv->advances_for_user($uid, true);
            $pageTitle = 'My Advances';
        }

        $view = [
            'table_id'   => 'payrollAdvancesTable',
            'advances'   => $advances,
            'page_title' => $pageTitle,
        ];

        $layout_data = [
            'page_title' => $pageTitle,
            'subview'    => 'payroll/advances',
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    public function get_advance_json($id)
    {
        if (!staff_can('view_global', 'payroll') && !staff_can('view_own', 'payroll')) {
            access_denied('payroll');
        }

        $id  = (int)$id;
        $row = $this->adv->advance($id);
        $this->output->set_content_type('application/json')->set_output(json_encode($row ?: []));
    }

    public function save_advance()
    {
        if (!staff_can('edit', 'payroll') && !staff_can('create', 'payroll')) {
            access_denied('payroll');
        }

        $id            = (int)$this->input->post('id', true);
        $user_id       = (int)$this->input->post('user_id', true);
        $amount        = (float)$this->input->post('amount', true);
        $paid          = (float)$this->input->post('paid', true);
        $status        = (string)$this->input->post('status', true);
        $notes         = (string)$this->input->post('notes', true);
        $requested_at  = $this->input->post('requested_at', true) ?: null;
        $approved_at   = $this->input->post('approved_at', true) ?: null;
        $approved_by   = (int)$this->input->post('approved_by', true) ?: null;

        if ($amount < 0) $amount = 0;
        if ($paid   < 0) $paid   = 0;
        $balance = max(0, $amount - $paid);

        if (in_array($status, ['approved','scheduled','paid'], true) && !$approved_by) {
            $approved_by = function_exists('get_staff_user_id')
                ? (int) get_staff_user_id()
                : (int) ($this->session->userdata('staff_user_id') ?: 0);
        }

        $payload = [
            'user_id'      => $user_id,
            'amount'       => $amount,
            'paid'         => $paid,
            'balance'      => $balance,
            'requested_at' => $requested_at,
            'approved_at'  => $approved_at,
            'approved_by'  => $approved_by ?: null,
            'status'       => $status ?: 'requested',
            'notes'        => $notes,
        ];

        $ok = $this->adv->advance_save($payload, $id ?: null);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Advance saved.' : 'Failed to save advance.');
        return redirect('payroll/advances');
    }

    public function delete_advance($id)
    {
        if (!staff_can('delete', 'payroll')) {
            access_denied('payroll');
        }

        $id = (int)$id;
        if ($id <= 0) {
            set_alert('danger', 'Invalid advance ID.');
            return redirect('payroll/advances');
        }

        $ok = $this->adv->advance_delete($id);
        set_alert($ok ? 'success' : 'danger', $ok ? 'Advance deleted.' : 'Failed to delete advance.');
        return redirect('payroll/advances');
    }

    /* ───────────────────────── Payslip ───────────────────────── */

    public function payslip()
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $id = (int)$this->input->get('id', true);
        if ($id <= 0) {
            set_alert('danger', 'Invalid payslip id.');
            return redirect('payroll');
        }

        $row = $this->details->payslip_row($id);
        if (empty($row)) {
            set_alert('danger', 'Payslip not found.');
            return redirect('payroll');
        }

        // If user can ONLY view own payroll, enforce ownership
        if (!staff_can('view_global', 'payroll') && staff_can('view_own', 'payroll')) {
            $current = function_exists('get_staff_user_id')
                ? (int) get_staff_user_id()
                : (int) ($this->session->userdata('staff_user_id') ?: 0);
            if ($current <= 0 || (int)$row['user_id'] !== $current) {
                access_denied('payroll');
            }
        }

        $view = [
            'page_title' => 'Payslip',
            'pd'         => $row,
        ];

        $layout_data = [
            'page_title' => 'Payslip',
            'subview'    => 'payroll/payslip',
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }


/* ───────────────────────── PF Accounts (Index) ───────────────────────── */

    public function pf_accounts()
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $view = [
            'page_title' => 'PF Accounts',
            'table_id'   => 'pfAccountsTable',
            'accounts'   => $this->pf->accounts_all(), // array of PF accounts + user basics
        ];
    
        $layout_data = [
            'page_title' => 'PF Accounts',
            'subview'    => 'payroll/pf_accounts', // <- your listing view file
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /* Detail page: PF account + transactions */
    public function pf_account($id)
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        $id = (int)$id;
        if ($id <= 0) {
            set_alert('danger', 'Invalid PF account ID.');
            return redirect('payroll/pf_accounts');
        }
    
        $acc = $this->pf->account($id);
        if (!$acc) {
            set_alert('danger', 'PF account not found.');
            return redirect('payroll/pf_accounts');
        }
    
        // if user can only view own payroll, enforce ownership
        if (!staff_can('view_global', 'payroll') && staff_can('view_own', 'payroll')) {
            $current = function_exists('get_staff_user_id')
                ? (int) get_staff_user_id()
                : (int) ($this->session->userdata('staff_user_id') ?: 0);
            if ($current <= 0 || (int)$acc['user_id'] !== $current) {
                access_denied('payroll');
            }
        }
    
        $txns = $this->pf->transactions_for_account($id);
    
        $view = [
            'page_title' => 'PF Account',
            'account'    => $acc,
            'txns'       => $txns,
            'table_id'   => 'pfTxnTable',
        ];
    
        // use a separate detail view (e.g., payroll/pf_account_view.php)
        $layout_data = [
            'page_title' => 'PF Account',
            'subview'    => 'payroll/pf_account_view',
            'view_data'  => $view,
        ];
        $this->load->view('layouts/master', $layout_data);
    }

    /* JSON for edit modal prefill */
    public function get_pf_account_json($id)
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $id  = (int)$id;
        $row = $this->pf->account($id);
        $this->output->set_content_type('application/json')->set_output(json_encode($row ?: []));
    }
    
    /* Create/Update PF account */
    public function save_pf_account()
    {

        if (! staff_can('view_global','payroll')) {
            $html = $this->load
                         ->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        $id = (int)$this->input->post('id', true);
    
        $payload = [
            'user_id'                       => (int)$this->input->post('user_id', true),
            'uan_number'                    => trim((string)$this->input->post('uan_number', true)),
            'pf_member_id'                  => trim((string)$this->input->post('pf_member_id', true)),
            'current_balance'               => (float)$this->input->post('current_balance', true),
            'employee_contribution_rate'    => (float)$this->input->post('employee_contribution_rate', true),
            'employer_contribution_rate'    => (float)$this->input->post('employer_contribution_rate', true),
            'wage_base_ceiling'             => (float)$this->input->post('wage_base_ceiling', true),
            'opened_at'                     => $this->input->post('opened_at', true) ?: null,
            'closed_at'                     => $this->input->post('closed_at', true) ?: null,
            'nominee_name'                  => trim((string)$this->input->post('nominee_name', true)),
            'nominee_relation'              => trim((string)$this->input->post('nominee_relation', true)),
            'nominee_share_percent'         => (float)$this->input->post('nominee_share_percent', true),
            'account_status'                => trim((string)$this->input->post('account_status', true)), // active/open/closed/etc
        ];
    
        $ok = $this->pf->account_save($payload, $id ?: null);
        set_alert($ok ? 'success' : 'danger', $ok ? 'PF account saved.' : 'Failed to save PF account.');
        return redirect('payroll/pf_accounts');
    }

    /* Delete PF account */
    public function delete_pf_account($id)
    {
        if (!staff_can('delete', 'payroll')) { access_denied('payroll'); }
    
        $id = (int)$id;
        if ($id <= 0) {
            set_alert('danger', 'Invalid PF account ID.');
            return redirect('payroll/pf_accounts');
        }
    
        $ok = $this->pf->account_delete($id);
        set_alert($ok ? 'success' : 'danger', $ok ? 'PF account deleted.' : 'Failed to delete PF account.');
        return redirect('payroll/pf_accounts');
    }

    /* Create/Update PF transaction */
    public function save_pf_txn()
    {
        if (!staff_can('edit', 'payroll') && !staff_can('create', 'payroll')) {
            access_denied('payroll');
        }
    
        $id  = (int)$this->input->post('id', true);
        $aid = (int)$this->input->post('pf_account_id', true);
        if ($aid <= 0) {
            set_alert('danger', 'Invalid PF account.');
            return redirect('payroll/pf_accounts');
        }
    
        $payload = [
            'pf_account_id'   => $aid,
            'transaction_type'=> trim((string)$this->input->post('transaction_type', true)), // contribution/withdrawal/adjustment/interest
            'amount'          => (float)$this->input->post('amount', true),
            'employee_share'  => (float)$this->input->post('employee_share', true),
            'employer_share'  => (float)$this->input->post('employer_share', true),
            'interest_rate'   => (float)$this->input->post('interest_rate', true),
            'txn_date'        => $this->input->post('txn_date', true) ?: date('Y-m-d'),
            'financial_year'  => (string)$this->input->post('financial_year', true),
            'reference_id'    => (string)$this->input->post('reference_id', true),
            'reference_module'=> (string)$this->input->post('reference_module', true),
            'status'          => (string)$this->input->post('status', true), // posted/pending/void
            'posted_by'       => function_exists('get_staff_user_id') ? (int)get_staff_user_id() : null,
            'notes'           => (string)$this->input->post('notes', true),
        ];
    
        $ok = $this->pf->txn_save($payload, $id ?: null);
        set_alert($ok ? 'success' : 'danger', $ok ? 'PF transaction saved.' : 'Failed to save PF transaction.');
        return redirect('payroll/pf_account/'.$aid);
    }

    /* Delete PF transaction */
    public function delete_pf_txn($id)
    {
        if (!staff_can('delete', 'payroll')) { access_denied('payroll'); }
    
        $id = (int)$id;
        if ($id <= 0) {
            set_alert('danger', 'Invalid transaction ID.');
            return redirect('payroll/pf_accounts');
        }
    
        // The model returns the affected account id so we can redirect back nicely
        $aid = $this->pf->txn_delete($id);
        set_alert($aid ? 'success' : 'danger', $aid ? 'PF transaction deleted.' : 'Failed to delete transaction.');
        return redirect($aid ? 'payroll/pf_account/'.$aid : 'payroll/pf_accounts');
    }
    




// ───────────────────────── Increments (Index) ─────────────────────────
public function increments()
{
    if (!staff_can('view_global','payroll')) {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html; exit;
    }

    // Use model helpers (handle table/column variability)
    $users       = $this->pi->users_dropdown();
    $departments = $this->pi->departments_dropdown();
    $positions   = $this->pi->positions_dropdown();

    $view = [
        'page_title'  => 'Salary Increments',
        'table_id'    => 'payrollIncrementsTable',
        'increments'  => $this->pi->history_all(),
        'users'       => $users,
        'departments' => $departments,
        'positions'   => $positions,
    ];

    $layout_data = [
        'page_title' => 'Salary Increments',
        'subview'    => 'payroll/increments',
        'view_data'  => $view,
    ];
    $this->load->view('layouts/master', $layout_data);
}



    // GET/POST: preview computed rows for the modal grid
    public function increment_preview()
    {
        if (!staff_can('view_global','payroll') && !staff_can('edit','payroll') && !staff_can('create','payroll')) {
            access_denied('payroll');
        }
    
        $this->load->model('PayrollIncrementsModel', 'inc');
    
        $scope         = (string)$this->input->post('scope', true);
        $user_ids      = $this->input->post('user_ids', true) ?: [];
        $department_id = (int)$this->input->post('department_id', true);
        $position_id   = (int)$this->input->post('position_id', true);
    
        $type          = (string)$this->input->post('increment_type', true) ?: 'amount';
        $value         = (float)$this->input->post('increment_value', true);
    
        // Get targets purely by scope (no value requirement)
        $targets = $this->inc->users_for_scope($scope, (array)$user_ids, $department_id, $position_id);
    
        $rows = [];
        foreach ($targets as $t) {
            $prev = (float)$t['current_salary'];
    
            // Compute ONLY when a positive value exists, otherwise show blanks
            if ($value > 0) {
                $new  = ($type === 'percent')
                      ? max(0, round($prev * (1 + ($value / 100.0)), 2))
                      : max(0, round($prev + $value, 2));
                $raise = $new - $prev;
            } else {
                $new = null;
                $raise = null;
            }
    
            $rows[] = [
                'user_id'        => (int)$t['id'],
                'emp_id'         => (string)($t['emp_id'] ?? ''),
                'name'           => trim(($t['firstname'] ?? '').' '.($t['lastname'] ?? '')),
                'current_salary' => $prev,
                'increment_type' => ($value > 0 ? $type : null),
                'increment'      => ($value > 0 ? $value : null),
                'new_salary'     => $new,
                'raise'          => $raise,
            ];
        }
    
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'rows' => $rows]));
    }





// POST: create increments (all / users[] / department / position)
public function save_increment()
{
    if (!staff_can('create','payroll') && !staff_can('edit','payroll')) {
        access_denied('payroll');
    }
    $this->load->model('PayrollIncrementsModel', 'inc');

    // global meta
    $meta = [
        'increment_date'  => $this->input->post('increment_date', true) ?: date('Y-m-d'),
        'increment_cycle' => (string)$this->input->post('increment_cycle', true) ?: 'annual',
        'remarks'         => (string)$this->input->post('remarks', true),
    ];

    // If items[] are posted, use those (row-specific type/value)
    $items = $this->input->post('items', true);
    if (is_array($items) && !empty($items)) {
        $inserted = $this->inc->apply_items($items, $meta);
        set_alert($inserted > 0 ? 'success' : 'danger',
                  $inserted > 0 ? "Increment(s) saved for {$inserted} employee(s)." : 'Nothing saved. Check values.');
        return redirect('payroll/increments');
    }

    // Fallback: previous behavior (single common type/value for scope)
    $scope           = (string)$this->input->post('scope', true);
    $user_ids        = $this->input->post('user_ids', true) ?: [];
    $department_id   = (int)$this->input->post('department_id', true);
    $position_id     = (int)$this->input->post('position_id', true);

    $payload = [
        'increment_date'  => $meta['increment_date'],
        'increment_type'  => (string)$this->input->post('increment_type', true) ?: 'amount',
        'increment_value' => (float)$this->input->post('increment_value', true),
        'increment_cycle' => $meta['increment_cycle'],
        'remarks'         => $meta['remarks'],
    ];

    // validations -> set alert + redirect
    if (!$scope) {
        set_alert('danger', 'Please select scope');
        return redirect('payroll/increments');
    }
    if ($payload['increment_value'] <= 0) {
        set_alert('danger', 'Enter a positive increment value');
        return redirect('payroll/increments');
    }
    if ($scope === 'users' && empty($user_ids)) {
        set_alert('danger', 'Select at least one employee');
        return redirect('payroll/increments');
    }
    if ($scope === 'department' && $department_id <= 0) {
        set_alert('danger', 'Select a department');
        return redirect('payroll/increments');
    }
    if ($scope === 'position' && $position_id <= 0) {
        set_alert('danger', 'Select a position');
        return redirect('payroll/increments');
    }

    $targets = $this->inc->users_for_scope($scope, (array)$user_ids, $department_id, $position_id);
    if (!$targets) {
        set_alert('danger', 'No matching employees for the selected scope.');
        return redirect('payroll/increments');
    }

    $inserted = $this->inc->apply_bulk($targets, $payload);

    set_alert($inserted > 0 ? 'success' : 'danger',
              $inserted > 0 ? "Increment(s) saved for {$inserted} employee(s)." : 'Failed to save increments.');
    return redirect('payroll/increments');
}





public function delete_increment()
{
    if (!staff_can('delete','payroll')) { access_denied('payroll'); }

    // accept either POST 'id' or URI segment if you want
    $id = (int)$this->input->post('id', true);
    if ($id <= 0) {
        set_alert('danger', 'Invalid increment id.');
        return redirect('payroll/increments');
    }

    $ok = $this->db->where('id', $id)->delete('payroll_increments');

    set_alert($ok ? 'success' : 'danger', $ok ? 'Increment deleted.' : 'Delete failed.');
    return redirect('payroll/increments');
}



// ───────────────────────── Arrears (Index) ─────────────────────────
public function arrears()
{
    if (!staff_can('view_global','payroll')) {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html; exit;
    }

    // Model provides rows + users dropdown
    $view = [
        'page_title' => 'Payroll Arrears',
        'table_id'   => 'payrollArrearsTable',
        'arrears'    => $this->arrears->arrears_all(true),   // with user info
        'users'      => $this->arrears->list_all_users_for_dropdown(),
    ];

    $layout_data = [
        'page_title' => 'Payroll Arrears',
        'subview'    => 'payroll/arrears',   // modules/payroll/views/arrears.php
        'view_data'  => $view,
    ];
    $this->load->view('layouts/master', $layout_data);
}


public function get_arrear_json($id)
{
    if (!staff_can('view_global','payroll')) access_denied('payroll');
    $row = $this->arrears->arrear((int)$id);
    $this->output->set_content_type('application/json')->set_output(json_encode($row ?: []));
}

public function save_arrear()
{
    if (!staff_can('create','payroll') && !staff_can('edit','payroll')) access_denied('payroll');

    $id = (int)$this->input->post('id', true) ?: null;
    $payload = [
        'user_id'        => (int)$this->input->post('user_id', true),
        'arrears_amount' => (float)$this->input->post('arrears_amount', true),
        'reason'         => (string)$this->input->post('reason', true),
        'source'         => (string)$this->input->post('source', true),
        'paid_on'        => $this->input->post('paid_on', true) ?: null,
        'status'         => (string)$this->input->post('status', true) ?: 'pending',
    ];

    $ok = $this->arrears->save($payload, $id);
    set_alert($ok ? 'success' : 'danger', $ok ? 'Arrear saved.' : 'Failed to save arrear.');
    return redirect('payroll/arrears');
}

public function delete_arrear($id)
{
    if (!staff_can('delete','payroll')) access_denied('payroll');
    $ok = $this->arrears->delete((int)$id);
    set_alert($ok ? 'success' : 'danger', $ok ? 'Arrear deleted.' : 'Failed to delete arrear.');
    return redirect('payroll/arrears');
}
    
}