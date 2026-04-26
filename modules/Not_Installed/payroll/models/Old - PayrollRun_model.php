<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PayrollRun_model extends CI_Model
{
    // Table names (unprefixed)
    protected $tbl_users   = 'users';
    protected $tbl_details = 'payroll_details';

    public function __construct()
    {
        parent::__construct();
    }

    /** Public API: main entry used by controller */
    public function run_payroll(array $meta, array $employees): array
    {
        $pTbl   = $this->db->dbprefix($this->tbl_details);
        $run_id = $this->generate_run_id();
        $now    = date('Y-m-d H:i:s');

        $inserted = 0;
        $updated  = 0;

        $this->db->trans_start();

        foreach ($employees as $emp) {
            $uid = (int)($emp['id'] ?? 0);
            if ($uid <= 0) { continue; }

            // Build a full row snapshot for this user+period
            $row = $this->compute_detail_row($uid, $meta, $run_id, $now);

            // Upsert on (user_id, period_start, period_end)
            $before = $this->find_existing_detail_id($uid, $row['period_start'], $row['period_end']);
            $ok     = $this->upsert_detail($row);

            if ($ok) {
                if ($before) { $updated++; } else { $inserted++; }
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

    /* ---------------- Core helpers ---------------- */

    protected function generate_run_id(): int
    {
        return (int)(date('YmdHis') . sprintf('%02d', random_int(0, 99)));
    }

    protected function apply_rounding(float $v, string $mode): float
    {
        switch ($mode) {
            case 'nearest': return round($v, 0);
            case 'down':    return floor($v);
            case 'up':      return ceil($v);
            case 'none':    return (float)$v;
            case 'inherit':
            default:        return (float)$v; // plug your system setting here later
        }
    }

    protected function user_snapshot(int $user_id): array
    {
        $uTbl = $this->db->dbprefix($this->tbl_users);
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
        $snap   = $this->user_snapshot($user_id);
        $round  = (string)($meta['rounding'] ?? 'inherit');
    
        // --- 90/5/5 policy (CHANGED) ---
        $crnslry     = (float)($snap['base_salary'] ?? 0.0);     // current/joining picked in user_snapshot()
        $house_rent  = round($crnslry * 0.05, 2);                // 5%
        $food_allow  = round($crnslry * 0.05, 2);                // 5%
        $basic       = round($crnslry - ($house_rent + $food_allow), 2); // 90%
        $allow       = round($house_rent + $food_allow, 2);      // total allowances from policy
    
        // If you plan to pass arrears via $meta per run, keep this; otherwise leave as 0.00
        $arrears     = isset($meta['arrears_amount']) ? (float)$meta['arrears_amount'] : 0.00; // (NEW)
    
        // Unchanged scaffolding
        $deduct  = 0.00;
        $ot_amt  = 0.00;
        $bonus   = 0.00;
        $comm    = 0.00;
        $other   = 0.00;
    
        $leave_unpaid_days = 0.00;
        $leave_deduction   = 0.00;
    
        // taxable now includes arrears (CHANGED)
        $taxable_income = max(0,
            $basic + $allow + $ot_amt + $bonus + $comm + $other + $arrears - $leave_deduction
        );
    
        $tax_amount     = 0.00;
    
        // PF snapshot (optional). If unused, keep zeros.
        $pf_wage_base = null;
        $pf_employee  = 0.00;
        $pf_employer  = 0.00;
        $pf_txn_id    = null;
        $pf_deduction = 0.00;
    
        // Loans/Advances deductions (fill from schedulers later)
        $loan_total     = 0.00;
        $advance_total  = 0.00;
    
        // gross now includes arrears (CHANGED)
        $gross = $basic + $allow + $ot_amt + $bonus + $comm + $other + $arrears;
    
        $total_deductions = $deduct + $leave_deduction + $tax_amount + $pf_deduction + $loan_total + $advance_total;
        $net   = max(0, $gross - $total_deductions);
        $net   = $this->apply_rounding($net, $round);
    
        $payslip = 'PS-' . date('Ymd', strtotime($meta['pay_date'])) . '-' . $user_id . '-' . substr((string)$run_id, -4);
    
        return [
            'user_id'               => $user_id,
            'pay_period'            => (string)$meta['pay_period'],
            'period_start'          => $meta['period_start'],
            'period_end'            => $meta['period_end'],
            'pay_date'              => $meta['pay_date'],
            'run_id'                => $run_id,
            'payslip_number'        => $payslip,
    
            // earnings (CHANGED: basic/allow reflect 90/5/5)
            'basic_salary'          => $basic,
            'allowances_total'      => $allow,
            'deductions_total'      => $deduct,
            'overtime_hours'        => 0.00,
            'overtime_amount'       => $ot_amt,
            'bonus_amount'          => $bonus,
            'commission_amount'     => $comm,
            'other_earnings'        => $other,
            'arrears_amount'        => $arrears,  // (NEW) persist arrears
    
            // leaves/tax
            'leave_unpaid_days'     => $leave_unpaid_days,
            'leave_deduction'       => $leave_deduction,
            'taxable_income'        => $taxable_income,
            'tax_amount'            => $tax_amount,
    
            // PF
            'pf_wage_base'          => $pf_wage_base,
            'pf_employee'           => $pf_employee,
            'pf_employer'           => $pf_employer,
            'pf_txn_id'             => $pf_txn_id,
            'pf_deduction'          => $pf_deduction,
    
            // totals (CHANGED: gross includes arrears)
            'gross_pay'             => $gross,
            'employer_cost'         => 0.00,
            'net_pay'               => $net,
    
            // loans/advances
            'loan_total_deduction'     => $loan_total,
            'advance_total_deduction'  => $advance_total,
            'loan_deductions_json'     => null,
            'advance_deductions_json'  => null,
    
            // payment meta
            'payment_method'        => 'bank',
            'payment_ref'           => null,
            'posted_at'             => null,
            'posted_by'             => null,
            'paid_at'               => null,
            'paid_by'               => null,
    
            // status/locks
            'status'                => 'active',
            'status_run'            => 'processed',
            'is_locked'             => 0,
    
            // org
            'cost_center_id'        => null,
            'department_id'         => $snap['department_id'],
            'team_id'               => $snap['team_id'],
    
            'notes'                 => (string)($meta['notes'] ?? null),
    
            'created_at'            => $now,
            'updated_at'            => $now,
        ];
    }


    protected function find_existing_detail_id(int $user_id, string $period_start, string $period_end): ?int
    {
        $pTbl = $this->db->dbprefix($this->tbl_details);
        $row  = $this->db->select('id')
                         ->from($pTbl)
                         ->where('user_id', $user_id)
                         ->where('period_start', $period_start)
                         ->where('period_end', $period_end)
                         ->limit(1)->get()->row_array();
        return $row ? (int)$row['id'] : null;
    }

    protected function upsert_detail(array $row): bool
    {
        $pTbl   = $this->db->dbprefix($this->tbl_details);
        $exists = $this->find_existing_detail_id((int)$row['user_id'], $row['period_start'], $row['period_end']);

        if ($exists) {
            // keep created_at, just bump updated_at
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $exists)->update($pTbl, $row);
            return $this->db->affected_rows() >= 0;
        }

        $this->db->insert($pTbl, $row);
        return $this->db->affected_rows() > 0;
    }
}
