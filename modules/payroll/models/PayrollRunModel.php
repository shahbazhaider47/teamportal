<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('PayrollBaseModel')) {
    // Load from same directory first (works whether modules are under /modules or /application/modules)
    $base = __DIR__ . '/PayrollBaseModel.php';
    if (file_exists($base)) {
        require_once $base;
    } else {
        // Fallback if someone later moves the module under application/modules
        @require_once APPPATH . 'modules/payroll/models/PayrollBaseModel.php';
    }
}

/**
 * Creates/updates payroll runs by computing and upserting payroll_details rows.
 * Implements the 90/5/5 structure and safe upsert on (user_id, period_start, period_end).
 */
class PayrollRunModel extends PayrollBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ------- Public API ------- */

    public function run_payroll(array $meta, array $employees): array
    {
        $run_id  = $this->generate_run_id();
        $now     = $this->now();
        $inserted = 0;
        $updated  = 0;

        $this->db->trans_start();

        foreach ($employees as $emp) {
            $uid = (int)($emp['id'] ?? 0);
            if ($uid <= 0) continue;
        
            $row = $this->compute_detail_row($uid, $meta, $run_id, $now);
        
            $before = $this->find_existing_detail_id($uid, $row['period_start'], $row['period_end']);
            $up     = $this->upsert_detail($row);
        
            if ($up['ok'] && $up['id']) {
                $detail_id = (int)$up['id'];
                if ($before) { $updated++; } else { $inserted++; }
        
                // (A) Loans: update loan ledgers
                $loanInfo = $this->compute_loan_deductions($uid, $row['period_start'], $row['period_end']);
                if (!empty($loanInfo['items'])) {
                    $this->post_loan_payments($loanInfo['items']);
                }
        
                // (B) PF: post contribution (creates PF account if missing)
                $txnDate = $row['pay_date'] ?: ($row['period_end'] ?: date('Y-m-d'));
                $this->post_pf_contribution(
                    $uid,
                    $detail_id,
                    $txnDate,
                    (float)$row['pf_employee'],
                    (float)$row['pf_employer'],
                    $run_id
                );
            }
        }

        $this->db->trans_complete();
        return [
            'ok'       => $this->db->trans_status(),
            'run_id'   => $run_id,
            'inserted' => $inserted,
            'updated'  => $updated,
        ];
    }

    /* ------- Core helpers ------- */

    protected function generate_run_id(): int
    {
        return (int)(date('YmdHis') . sprintf('%02d', random_int(0, 99)));
    }

    protected function user_snapshot(int $user_id): array
    {
        $uTbl = $this->tbl($this->tbl_users);
        $row  = $this->db->select('id, current_salary, joining_salary, emp_department as department_id, emp_team as team_id')
                         ->from($uTbl)->where('id', $user_id)->get()->row_array() ?: [];

        $base = 0.00;
        if (!empty($row['current_salary']) && (float)$row['current_salary'] > 0) {
            $base = (float)$row['current_salary'];
        } elseif (!empty($row['joining_salary']) && (float)$row['joining_salary'] > 0) {
            $base = (float)$row['joining_salary'];
        }

        return [
            'base_salary'   => $base,
            'department_id' => isset($row['department_id']) ? (int)$row['department_id'] : null,
            'team_id'       => isset($row['team_id']) ? (int)$row['team_id'] : null,
        ];
    }

    protected function compute_detail_row(int $user_id, array $meta, int $run_id, string $now): array
    {
        $snap  = $this->user_snapshot($user_id);
        $round = (string)($meta['rounding'] ?? 'inherit');
    
        // 90/5/5 policy
        $crnslry      = (float)($snap['base_salary'] ?? 0.0);
        $house_rent   = round($crnslry * 0.05, 2);
        $food_allow   = round($crnslry * 0.05, 2);
        $basic        = round($crnslry - ($house_rent + $food_allow), 2);
        $policy_allow = round($house_rent + $food_allow, 2);
    
        $arrears = isset($meta['arrears_amount']) ? (float)$meta['arrears_amount'] : 0.00;
    
        $deduct = 0.00;
        $ot_amt = 0.00;
        $bonus  = 0.00;
        $comm   = 0.00;
        $other  = 0.00;
    
        $leave_unpaid_days = 0.00;
        $leave_deduction   = 0.00;
    
        /* >>> Dynamic allowances based on users.allowances IDs <<< */
        // Gross anchor WITHOUT dynamic allowances (avoid circular % on gross)
        $gross_anchor = $basic + $policy_allow + $ot_amt + $bonus + $comm + $other + $arrears;

        $ctx = $this->user_context($user_id);
        
        // Compute dynamic allowances from users.allowances IDs
        $dyn = $this->calc_user_allowances_from_ids(
            $user_id,
            $crnslry,
            $basic,
            $gross_anchor,
            $ctx // <-- NEW
        );
    
        // Final allowances = policy (if you keep it) + dynamic(IDs)
        $allow = round($policy_allow + (float)$dyn['total'], 2);
    
        // Store breakdown JSON (dynamic only)
        $allowances_breakdown_json = !empty($dyn['breakdown']) ? json_encode($dyn['breakdown']) : null;
    
        // Now continue with totals before deductions
        $gross = $basic + $allow + $ot_amt + $bonus + $comm + $other + $arrears;
    
        /* >>> Loans: compute deduction for this period <<< */
        $loanInfo      = $this->compute_loan_deductions($user_id, $meta['period_start'], $meta['period_end']);
        $loan_total    = round((float)($loanInfo['total'] ?? 0), 2);
        $loan_json     = !empty($loanInfo['items']) ? json_encode($loanInfo['items']) : null;
    
        // Advances placeholder (wire later if you wish)
        $advance_total = 0.00;
        $adv_json      = null;
    
        // Taxes (placeholder)
        $taxable_income = max(0, $basic + $allow + $ot_amt + $bonus + $comm + $other + $arrears - $leave_deduction);
        $tax_amount     = 0.00;
    
        /* >>> Provident Fund (from settings) <<< */
        $pfCfg = $this->pf_config(); // ['enabled'=>bool, 'employee_pct'=>float, 'employer_pct'=>float]
        // Basis for PF: Basic pay (change to $crnslry if you want PF on current salary)
        [$pf_employee, $pf_employer, $pf_deduction, $employer_cost] = $this->compute_pf($basic, $pfCfg);
    
        // Totals / Net
        $total_deductions = $deduct + $leave_deduction + $tax_amount + $pf_deduction + $loan_total + $advance_total;
        $net   = max(0, $gross - $total_deductions);
        $net   = $this->apply_rounding($net, $round);
    
        // Payslip anchor is pay_date if provided; else fall back to period_end or today
        $anchorDate = !empty($meta['pay_date'])
            ? $meta['pay_date']
            : (!empty($meta['period_end']) ? $meta['period_end'] : date('Y-m-d'));
        $payslip = 'PS-' . date('Ymd', strtotime($anchorDate)) . '-' . $user_id . '-' . substr((string)$run_id, -4);
    
        return [
            'user_id'               => $user_id,
            'pay_period'            => (string)$meta['pay_period'],
            'period_start'          => $meta['period_start'],
            'period_end'            => $meta['period_end'],
            'pay_date'              => $meta['pay_date'] ?? null,
            'run_id'                => $run_id,
            'payslip_number'        => $payslip,
    
            // earnings
            'basic_salary'              => round($basic, 2),
            'allowances_total'          => round($allow, 2),
            'allowances_breakdown_json' => $allowances_breakdown_json,
            'deductions_total'          => round($deduct, 2),
            'overtime_hours'            => 0.00,
            'overtime_amount'           => round($ot_amt, 2),
            'bonus_amount'              => round($bonus, 2),
            'commission_amount'         => round($comm, 2),
            'other_earnings'            => round($other, 2),
            'arrears_amount'            => round($arrears, 2),
    
            // leaves/tax
            'leave_unpaid_days'         => round($leave_unpaid_days, 2),
            'leave_deduction'           => round($leave_deduction, 2),
            'taxable_income'            => round($taxable_income, 2),
            'tax_amount'                => round($tax_amount, 2),
    
            // PF
            'pf_wage_base'              => round($basic, 2),   // basis used (for display/audit)
            'pf_employee'               => round($pf_employee, 2),
            'pf_employer'               => round($pf_employer, 2),
            'pf_txn_id'                 => null,
            'pf_deduction'              => round($pf_deduction, 2), // included in deductions
    
            // totals
            'gross_pay'                 => round($gross, 2),
            'employer_cost'             => round($employer_cost, 2), // employer share; does not reduce net
            'net_pay'                   => round($net, 2),
    
            // loan/advance
            'loan_total_deduction'      => round($loan_total, 2),
            'advance_total_deduction'   => round($advance_total, 2),
            'loan_deductions_json'      => $loan_json,
            'advance_deductions_json'   => $adv_json,
    
            // payment meta
            'payment_method'            => null,
            'payment_ref'               => null,
            'posted_at'                 => null,
            'posted_by'                 => null,
            'paid_at'                   => null,
            'paid_by'                   => null,
    
            // status/locks (keep your current casing/enums)
            'status'                    => 'Active',
            'status_run'                => 'Open',
            'is_locked'                 => 0,
    
            // org
            'cost_center_id'            => null,
            'department_id'             => $snap['department_id'],
            'team_id'                   => $snap['team_id'],
    
            'notes'                     => (string)($meta['notes'] ?? null),
    
            'created_at'                => $now,
            'updated_at'                => $now,
        ];
    }



    protected function find_existing_detail_id(int $user_id, string $period_start, string $period_end): ?int
    {
        $pTbl = $this->tbl($this->tbl_details);
        $row  = $this->db->select('id')
                         ->from($pTbl)
                         ->where('user_id', (int)$user_id)
                         ->where('period_start', $period_start)
                         ->where('period_end', $period_end)
                         ->limit(1)->get()->row_array();
        return $row ? (int)$row['id'] : null;
    }

    protected function upsert_detail(array $row): array
    {
        $pTbl   = $this->tbl($this->tbl_details);
        $exists = $this->find_existing_detail_id((int)$row['user_id'], $row['period_start'], $row['period_end']);
    
        if ($exists) {
            $row['updated_at'] = $this->now();
            $this->db->where('id', $exists)->update($pTbl, $row);
            return ['ok' => ($this->db->affected_rows() >= 0), 'id' => $exists];
        }
    
        $this->db->insert($pTbl, $row);
        $id = (int)$this->db->insert_id();
        return ['ok' => ($id > 0), 'id' => ($id > 0 ? $id : null)];
    }


    /**
     * Read user->allowances JSON (["1","2","3"]) and return as int[]
     */
    protected function user_allowance_ids(int $user_id): array
    {
        $uTbl = $this->tbl($this->tbl_users);
        $row  = $this->db->select('allowances')
                         ->from($uTbl)
                         ->where('id', $user_id)
                         ->limit(1)->get()->row_array();
        if (!$row || empty($row['allowances'])) return [];
    
        $ids = json_decode($row['allowances'], true);
        if (!is_array($ids)) return [];
    
        // normalize to unique positive ints
        $out = [];
        foreach ($ids as $v) {
            $n = (int)$v;
            if ($n > 0) $out[$n] = true;
        }
        return array_keys($out);
    }

    /**
     * Fetch allowance rows by id list from hrm_allowances (active + payroll_visible)
     */
    protected function fetch_allowances_by_ids(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
    
        $tbl = $this->db->dbprefix('hrm_allowances');
    
        if (!$this->db->table_exists($tbl)) {
            return [];
        }
    
        return $this->db
            ->from($tbl)
            ->where_in('id', $ids)
            ->where('is_active', 1)
            ->get()
            ->result_array();
    }


    /**
     * Compute a single allowance amount based on config.
     * $basis:
     *  - 'base salary'   → $base_salary
     *  - 'basic pay'     → $basic_after_policy
     *  - 'gross salary'  → $gross_anchor (gross without dynamic allowances to avoid circularity)
     */
    protected function compute_single_allowance(array $a, float $base_salary, float $basic_after_policy, float $gross_anchor): float
    {
        $isPct = (int)($a['is_percentage'] ?? 0) === 1;
        $amt   = (float)($a['amount'] ?? 0);
        if ($amt <= 0) return 0.0;
    
        $basis = strtolower(trim((string)($a['percentage_of'] ?? 'base salary')));
        $baseForPct = $base_salary;
        if ($basis === 'basic pay' || $basis === 'basic') {
            $baseForPct = $basic_after_policy;
        } elseif ($basis === 'gross salary' || $basis === 'gross') {
            $baseForPct = $gross_anchor;
        }
    
        $value = $isPct ? (($baseForPct * $amt) / 100.0) : $amt;
    
        $cap = isset($a['max_limit']) ? (float)$a['max_limit'] : null;
        if ($cap !== null && $cap > 0) $value = min($value, $cap);
    
        return max(0.0, round($value, 2));
    }

    /**
     * Collapse duplicates by title (case-insensitive) and sum
     */
    protected function consolidate_allowances(array $items): array
    {
        $bucket = [];
        foreach ($items as $it) {
            $title = isset($it['title']) ? trim((string)$it['title']) : 'Allowance';
            if ($title === '') $title = 'Allowance';
            $key = mb_strtolower($title, 'UTF-8');
    
            $amt = isset($it['amount']) ? (float)$it['amount'] : 0.0;
            if ($amt <= 0) continue;
    
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['title' => $title, 'amount' => 0.0];
            }
            $bucket[$key]['amount'] += $amt;
        }
        foreach ($bucket as &$b) { $b['amount'] = round($b['amount'], 2); }
        // stable output
        uasort($bucket, fn($a,$b)=>strcasecmp($a['title'],$b['title']));
        return array_values($bucket);
    }

    /**
     * Main: compute dynamic allowances from IDs in users.allowances
     * Returns ['total'=>float, 'breakdown'=>[{'title','amount'},...]]
     */
    protected function calc_user_allowances_from_ids(
        int $user_id,
        float $base_salary,
        float $basic_after_policy,
        float $gross_anchor,
        array $ctx // <-- NEW
    ): array {
        $ids  = $this->user_allowance_ids($user_id);
        $rows = $this->fetch_allowances_by_ids($ids);
        if (empty($rows)) return ['total'=>0.0,'breakdown'=>[]];
    
        $out = [];
        foreach ($rows as $a) {
            // NEW: applicability gate
            if (!$this->is_allowance_applicable($a, $ctx)) {
                continue;
            }
    
            $value = $this->compute_single_allowance($a, $base_salary, $basic_after_policy, $gross_anchor);
            if ($value <= 0) continue;
            $out[] = ['title' => (string)$a['title'], 'amount' => $value];
        }
    
        $out = $this->consolidate_allowances($out);
    
        $sum = 0.0;
        foreach ($out as $ln) $sum += (float)$ln['amount'];
    
        return ['total' => round($sum,2), 'breakdown' => $out];
    }

    /**
     * Fetch user's active loans.
     * We’ll treat loans with status in ('active','approved','scheduled') as collectible.
     */
    protected function fetch_user_loans(int $user_id): array
    {
        $tbl = $this->db->dbprefix('payroll_loans');
        if (!$this->db->table_exists($tbl)) return [];
    
        // tune the statuses to your workflow
        $this->db->from($tbl)->where('user_id', $user_id)
            ->where_in('status', ['active','approved','scheduled']);
        return $this->db->get()->result_array();
    }
    
    /** Months difference (whole months) from start to end (Y-m-d). */
    protected function months_diff(string $start, string $end): int
    {
        $a = date_create($start); $b = date_create($end);
        if (!$a || !$b) return 0;
        $invert = (int)$a > (int)$b ? -1 : 1;
        $y = (int)$b->format('Y') - (int)$a->format('Y');
        $m = (int)$b->format('n') - (int)$a->format('n');
        return $invert * ($y*12 + $m);
    }
    
    /**
     * Decide if a quarterly installment is due in the month of $anchorDate,
     * using $start_date as the schedule anchor. We fire when months_diff % 3 == 0.
     */
    protected function is_quarter_due(string $start_date, string $anchorDate): bool
    {
        $diff = $this->months_diff(date('Y-m-01', strtotime($start_date)),
                                   date('Y-m-01', strtotime($anchorDate)));
        return $diff >= 0 && ($diff % 3) === 0;
    }
    
    /**
     * Compute loan deductions for a user for the given period.
     * Returns ['total'=>float, 'items'=>[['loan_id'=>int,'amount'=>float],...]]
     *
     * Rules:
     *  - Monthly, From Salary  → deduct monthly_installment every run
     *  - Quarterly             → deduct monthly_installment * 3 when a quarter hits
     *  - Custom                → no automatic deduction
     *  - Caps at remaining balance (loan_taken - total_paid)
     *  - Only loans with positive balance
     */
    protected function compute_loan_deductions(int $user_id, string $period_start, string $period_end): array
    {
        $loans = $this->fetch_user_loans($user_id);
        if (empty($loans)) return ['total'=>0.0,'items'=>[]];
    
        $items = []; $total = 0.0;
        $anchor = $period_end ?: $period_start; // charge by end month
    
        foreach ($loans as $L) {
            $loan_id = (int)$L['id'];
            $balance = max(0.0, (float)$L['loan_taken'] - (float)$L['total_paid']);
            if ($balance <= 0) continue;
    
            $type = strtolower(trim((string)$L['payback_type']));
            $per  = max(0.0, (float)$L['monthly_installment']);
    
            $due = 0.0;
            switch ($type) {
                case 'monthly':
                case 'from salary':
                case 'from_salary':
                    $due = $per;
                    break;
    
                case 'quarterly':
                    // Only on quarter boundary months
                    $sd = !empty($L['start_date']) ? $L['start_date'] : $anchor;
                    if ($this->is_quarter_due($sd, $anchor)) {
                        // assume monthly_installment is a monthly figure → 3 months worth
                        $due = $per;
                    }
                    break;
    
                case 'custom':
                default:
                    $due = 0.0; // no auto-deduction
            }
    
            if ($due <= 0) continue;
    
            // never exceed remaining balance
            $due = min($due, $balance);
    
            $items[] = ['loan_id' => $loan_id, 'amount' => round($due, 2)];
            $total  += $due;
        }
    
        return ['total' => round($total, 2), 'items' => $items];
    }
    
    /**
     * After payslip is saved, post loan payments to payroll_loans.
     * Each item is ['loan_id'=>int,'amount'=>float].
     */
    protected function post_loan_payments(array $items): void
    {
        if (empty($items)) return;
        $tbl = $this->db->dbprefix('payroll_loans');
        $now = $this->now();
    
        foreach ($items as $i) {
            $id  = (int)$i['loan_id'];
            $amt = max(0.0, (float)$i['amount']);
            if ($id <= 0 || $amt <= 0) continue;
    
            // atomic: total_paid = total_paid + amt, balance = GREATEST(loan_taken - total_paid - amt, 0)
            $this->db->set('total_paid', "total_paid + {$this->db->escape_str($amt)}", false)
                     ->set('balance',    "GREATEST(loan_taken - total_paid, 0)",      false)
                     ->set('updated_at', $now)
                     ->where('id', $id)
                     ->update($tbl);
        }
    }
    
    
    // Read PF config from Settings
    protected function pf_config(): array
    {
        // Use your app's get_option() helper if available
        $enabled = 'no';
        $empPct  = 0.0;
        $emprPct = 0.0;
    
        if (function_exists('get_option')) {
            $enabled = (string) get_option('payroll_pf_enabled'); // 'yes'|'no'
            $empPct  = (float) get_option('payroll_pf_employee_percentage');
            $emprPct = (float) get_option('payroll_pf_employer_percentage');
        } else {
            // Fallbacks if your app stores settings differently
            $enabled = $this->config->item('payroll_pf_enabled') ?: 'no';
            $empPct  = (float) ($this->config->item('payroll_pf_employee_percentage') ?: 0);
            $emprPct = (float) ($this->config->item('payroll_pf_employer_percentage') ?: 0);
        }
    
        // sanitize
        $empPct  = max(0, min(100, $empPct));
        $emprPct = max(0, min(100, $emprPct));
    
        return [
            'enabled'      => strtolower($enabled) === 'yes',
            'employee_pct' => $empPct,
            'employer_pct' => $emprPct,
        ];
    }
    
    /**
     * Compute PF values based on a PF base (we use Basic pay).
     * Returns [pf_employee, pf_employer, pf_deduction, employer_cost]
     */
    protected function compute_pf(float $pf_base, array $cfg): array
    {
        if (empty($cfg['enabled'])) {
            return [0.00, 0.00, 0.00, 0.00];
        }
    
        $pf_employee = round(($pf_base * $cfg['employee_pct']) / 100.0, 2);
        $pf_employer = round(($pf_base * $cfg['employer_pct']) / 100.0, 2);
    
        // What actually deducts from employee net
        $pf_deduction = $pf_employee;
    
        // Employer cost (doesn't reduce net; increases employer cost line)
        $employer_cost = $pf_employer;
    
        return [$pf_employee, $pf_employer, $pf_deduction, $employer_cost];
    }
    
    
    /** Ensure a PF account exists for the user; create it with current rates if missing. Returns pf_account_id or null. */
    protected function ensure_pf_account(int $user_id): ?int
    {
        $accTbl = $this->db->dbprefix('payroll_pf_accounts');
        if (!$this->db->table_exists($accTbl)) return null;
    
        // Try to find an "open/active" account
        $row = $this->db->from($accTbl)
            ->where('user_id', $user_id)
            ->where_in('account_status', ['active', 'open', 'enabled'])
            ->order_by('id','DESC')->limit(1)->get()->row_array();
    
        if ($row && !empty($row['id'])) return (int)$row['id'];
    
        // Create minimal account using current settings
        $cfg = $this->pf_config();
        $now = $this->now();
        $payload = [
            'user_id'                      => $user_id,
            'uan_number'                   => null,
            'pf_member_id'                 => null,
            'current_balance'              => 0.00,
            'employee_contribution_rate'   => (float)$cfg['employee_pct'],
            'employer_contribution_rate'   => (float)$cfg['employer_pct'],
            'wage_base_ceiling'            => null,
            'opened_at'                    => date('Y-m-d'),
            'closed_at'                    => null,
            'nominee_name'                 => null,
            'nominee_relation'             => null,
            'nominee_share_percent'        => null,
            'account_status'               => 'active',
            'created_at'                   => $now,
            'updated_at'                   => $now,
        ];
        $this->db->insert($accTbl, $payload);
        $id = (int)$this->db->insert_id();
        return $id > 0 ? $id : null;
    }
    
    /** Very simple FY derivation (calendar year). Adapt if you need Apr–Mar, etc. */
    protected function financial_year_from_date(string $ymd): string
    {
        // Example for Apr–Mar style (uncomment to use):
        // $t = strtotime($ymd ?: 'now');
        // $y = (int)date('Y',$t); $m = (int)date('n',$t);
        // $start = ($m >= 4) ? $y : ($y - 1);
        // return $start . '-' . ($start + 1);
    
        $y = (int)date('Y', strtotime($ymd ?: 'now'));
        return (string)$y;
    }
    
    /** Insert PF txn, update account balance, and link payroll_details.pf_txn_id */
    protected function post_pf_contribution(
        int $user_id,
        int $detail_id,
        string $txnDate,
        float $empShare,
        float $emprShare,
        int $run_id
    ): void {
        // Skip when PF is off or nothing to post
        $cfg = $this->pf_config();
        $total = round(max(0.0, $empShare) + max(0.0, $emprShare), 2);
        if (!$cfg['enabled'] || $total <= 0) return;
    
        $acc_id = $this->ensure_pf_account($user_id);
        if (!$acc_id) return;
    
        $txTbl  = $this->db->dbprefix('payroll_pf_transactions');
        $accTbl = $this->db->dbprefix('payroll_pf_accounts');
        $detTbl = $this->tbl($this->tbl_details);
        $now    = $this->now();
    
        $payload = [
            'pf_account_id'   => $acc_id,
            'transaction_type'=> 'contribution',      // convention
            'amount'          => $total,
            'employee_share'  => round($empShare, 2),
            'employer_share'  => round($emprShare, 2),
            'interest_rate'   => null,
            'txn_date'        => $txnDate ?: date('Y-m-d'),
            'financial_year'  => $this->financial_year_from_date($txnDate),
            'reference_id'    => $detail_id,          // link to payslip row
            'reference_module'=> 'payroll_details',
            'status'          => 'posted',
            'posted_by'       => (function_exists('get_staff_user_id') ? (int)get_staff_user_id() : null),
            'notes'           => 'Payroll run #'.$run_id,
            'created_at'      => $now,
        ];
    
        // Insert transaction
        $this->db->insert($txTbl, $payload);
        $txn_id = (int)$this->db->insert_id();
        if ($txn_id <= 0) return;
    
        // Update account balance atomically
        $this->db->set('current_balance', "current_balance + {$this->db->escape_str($total)}", false)
                 ->set('updated_at', $now)
                 ->where('id', $acc_id)
                 ->update($accTbl);
    
        // Link payslip row
        $this->db->where('id', $detail_id)->update($detTbl, ['pf_txn_id' => $txn_id, 'updated_at' => $now]);
    }
    
    
    /** Read basic context for applicability checks. */
    protected function user_context(int $user_id): array
    {
        $uTbl = $this->tbl($this->tbl_users);
    
        // Build a safe, schema-aware select list.
        // We’ll include optional columns only if they exist to avoid SQL errors.
        $baseCols = ['id', 'gender', 'emp_department', 'emp_team', 'emp_title'];
        $optionalCols = ['hrm_position_id', 'position_id', 'designation_id'];
    
        // Detect actual table name CI expects for field_exists/list_fields
        $tableNameForChecks = $uTbl; // $this->tbl() already prefixes
    
        $selectCols = $baseCols;
        foreach ($optionalCols as $col) {
            if ($this->db->field_exists($col, $tableNameForChecks)) {
                $selectCols[] = $col;
            }
        }
    
        // Build the SELECT string safely
        $this->db->select(implode(',', $selectCols), false)
                 ->from($uTbl)
                 ->where('id', $user_id);
    
        $row = $this->db->get()->row_array() ?: [];
    
        // Normalize gender
        $g = strtolower(trim((string)($row['gender'] ?? '')));
        if ($g === 'm' || $g === 'male')        { $g = 'male'; }
        elseif ($g === 'f' || $g === 'female')  { $g = 'female'; }
        else                                    { $g = ''; }
    
        // Normalize a single position id from any known column
        $posId = null;
        foreach (['hrm_position_id', 'position_id', 'designation_id', 'emp_title'] as $pc) {
            if (array_key_exists($pc, $row) && is_numeric($row[$pc]) && (int)$row[$pc] > 0) {
                $posId = (int)$row[$pc];
                break;
            }
        }
    
        return [
            'gender'        => $g,
            'department_id' => isset($row['emp_department']) ? (int)$row['emp_department'] : null,
            'team_id'       => isset($row['emp_team'])       ? (int)$row['emp_team']       : null,
            'position_id'   => $posId,
            'user_id'       => $user_id,
        ];
    }
        
    /** Safe JSON→int[] (accepts ["1","2",3]). */
    protected function parse_id_list($json): array
    {
        if ($json === null || $json === '') return [];
        if (is_array($json)) $arr = $json;
        else {
            $arr = json_decode((string)$json, true);
            if (!is_array($arr)) return [];
        }
        $out = [];
        foreach ($arr as $v) {
            $n = (int)$v;
            if ($n > 0) $out[$n] = true;
        }
        return array_keys($out);
    }
    
    /** Decide if allowance $a applies to user context $ctx. */
    protected function is_allowance_applicable(array $a, array $ctx): bool
    {
        // No field? default to "all"
        $scope = strtolower(trim((string)($a['applicable_to'] ?? 'all')));
    
        switch ($scope) {
            case 'all':
            case '':
                return true;
    
            case 'male':
            case 'males':
            case 'all males':
                return ($ctx['gender'] === 'male');
    
            case 'female':
            case 'females':
            case 'all females':
                return ($ctx['gender'] === 'female');
    
            case 'departments':
                $ids = $this->parse_id_list($a['applicable_departments_json'] ?? null);
                return $ctx['department_id'] ? in_array((int)$ctx['department_id'], $ids, true) : false;
    
            case 'positions':
                $pids = $this->parse_id_list($a['applicable_positions_json'] ?? null);
                return $ctx['position_id'] ? in_array((int)$ctx['position_id'], $pids, true) : false;
    
            case 'custom':
                $uids = $this->parse_id_list($a['applicable_user_ids_json'] ?? null);
                return in_array((int)$ctx['user_id'], $uids, true);
    
            default:
                // Unknown value → safest is to treat as not applicable
                return false;
        }
    }
    
}