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
 * Reads/writes payroll_details and run-level reporting.
 */
class PayrollDetailsModel extends PayrollBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ------- Grid: legacy details list (kept for compatibility) ------- */
    public function get_payroll_details(): array
    {
        $uTbl = $this->tbl($this->tbl_users);
        $pTbl = $this->tbl($this->tbl_details);

        if (!$this->db->table_exists($pTbl)) return [];

        $sql = "
            SELECT
                pd.*,
                u.emp_id,
                u.fullname,
                (
                    COALESCE(pd.basic_salary, 0)
                  + COALESCE(pd.allowances_total, 0)
                  + COALESCE(pd.overtime_amount, 0)
                  + COALESCE(pd.bonus_amount, 0)
                  + COALESCE(pd.commission_amount, 0)
                  + COALESCE(pd.arrears_amount, 0)
                  + COALESCE(pd.other_earnings, 0)
                ) AS total_earnings,
                (
                    COALESCE(pd.deductions_total, 0)
                  + COALESCE(pd.leave_deduction, 0)
                  + COALESCE(pd.tax_amount, 0)
                  + COALESCE(pd.pf_deduction, 0)
                  + COALESCE(pd.loan_total_deduction, 0)
                  + COALESCE(pd.advance_total_deduction, 0)
                ) AS total_deductions
            FROM {$pTbl} pd
            LEFT JOIN {$uTbl} u ON u.id = pd.user_id
            ORDER BY COALESCE(pd.pay_date, pd.period_end) DESC, pd.id DESC
        ";
        return $this->db->query($sql)->result_array();
    }

    /* ------- Run summaries for /payroll (index) ------- */
    public function get_runs_summary(int $limit = 250): array
    {
        $pTbl = $this->tbl($this->tbl_details);
        if (!$this->db->table_exists($pTbl)) return [];

        $sql = "
            SELECT
                pd.run_id,
                pd.pay_period,
                pd.period_start,
                pd.period_end,
                pd.pay_date,
                pd.status_run,
                COUNT(*) AS employees_count,
                SUM(COALESCE(pd.basic_salary,0))        AS sum_basic,
                SUM(COALESCE(pd.allowances_total,0))    AS sum_allowances,
                SUM(COALESCE(pd.overtime_amount,0))     AS sum_overtime,
                SUM(COALESCE(pd.bonus_amount,0))        AS sum_bonus,
                SUM(COALESCE(pd.commission_amount,0))   AS sum_commission,
                SUM(COALESCE(pd.other_earnings,0))      AS sum_other,
                SUM(COALESCE(pd.arrears_amount,0))      AS sum_arrears,
                SUM(COALESCE(pd.gross_pay,0))           AS sum_gross,
                SUM(
                    COALESCE(pd.deductions_total,0)
                  + COALESCE(pd.leave_deduction,0)
                  + COALESCE(pd.tax_amount,0)
                  + COALESCE(pd.pf_deduction,0)
                  + COALESCE(pd.loan_total_deduction,0)
                  + COALESCE(pd.advance_total_deduction,0)
                )                                       AS sum_deductions,
                SUM(COALESCE(pd.net_pay,0))             AS sum_net,
                MIN(pd.created_at)                      AS created_at,
                MAX(pd.updated_at)                      AS updated_at
            FROM {$pTbl} pd
            GROUP BY pd.run_id, pd.pay_period, pd.period_start, pd.period_end, pd.pay_date
            ORDER BY COALESCE(pd.pay_date, pd.period_end) DESC, pd.run_id DESC
            LIMIT {$limit}
        ";
        return $this->db->query($sql)->result_array();
    }

    public function get_run_summary(int $run_id): ?array
    {
        foreach ($this->get_runs_summary(100000) as $r) {
            if ((int)$r['run_id'] === (int)$run_id) return $r;
        }
        return null;
    }

    public function get_run_rows(int $run_id): array
    {
        $uTbl = $this->tbl($this->tbl_users);
        $pTbl = $this->tbl($this->tbl_details);

        return $this->db->select("
                    pd.*,
                    u.emp_id, u.fullname, u.firstname, u.lastname,
                    u.email, u.emp_department, u.emp_team
                ", false)
                ->from($pTbl.' pd')
                ->join($uTbl.' u', 'u.id = pd.user_id', 'left')
                ->where('pd.run_id', (int)$run_id)
                ->order_by('u.fullname', 'ASC')
                ->get()->result_array();
    }

    public function delete_run(int $run_id): bool
    {
        $pTbl = $this->tbl($this->tbl_details);
        $this->db->where('run_id', (int)$run_id)->delete($pTbl);
        return $this->db->affected_rows() > 0;
    }

    /* ------- Payslip ------- */
public function payslip_row(int $id): array
{
    $pdRaw = $this->tbl_details;
    $pdTbl = $this->tbl($pdRaw);
    $uRaw  = $this->tbl_users;
    $uTbl  = $this->tbl($uRaw);
    $lTbl  = $this->db->dbprefix('payroll_loans');

    // ---- loans aggregate (active-ish loans) ----
    $loanAggSql = "
        SELECT user_id,
               COALESCE(SUM(loan_taken),0) AS loan_taken_total,
               COALESCE(SUM(total_paid),0) AS loan_total_paid,
               COALESCE(SUM(balance),0)    AS loan_balance_total
        FROM {$lTbl}
        WHERE status IN ('active','approved','scheduled')
        GROUP BY user_id
    ";

    // ✅ NEW: latest active PF account per user (bring balance + a few IDs)
    $pfAccTbl = $this->db->dbprefix('payroll_pf_accounts');
    $pfLatestSql = "
        SELECT a.user_id,
               a.id                AS pf_account_id,
               a.current_balance   AS pf_current_balance,
               a.uan_number,
               a.pf_member_id,
               a.employee_contribution_rate,
               a.employer_contribution_rate,
               a.wage_base_ceiling
        FROM {$pfAccTbl} a
        INNER JOIN (
            SELECT user_id, MAX(id) AS mid
            FROM {$pfAccTbl}
            WHERE account_status IN ('active','open','enabled')
            GROUP BY user_id
        ) x ON x.user_id = a.user_id AND x.mid = a.id
    ";

    $uFields = $this->columns($uRaw);
    $wantUserCols = [
        'emp_id','firstname','lastname','fullname','emp_joining','email',
        'bank_name','bank_account_number','bank_branch','current_salary','emp_department','emp_title'
    ];
    $userSelectCols = [];
    foreach ($wantUserCols as $c) { if (isset($uFields[$c])) $userSelectCols[] = 'u.`'.$c.'`'; }

    $this->db->from($pdTbl.' pd')
             ->where('pd.id', (int)$id)
             ->select('pd.*', false)
             ->join($uTbl.' u', 'u.id = pd.user_id', 'left')
             ->join("({$loanAggSql}) la", 'la.user_id = pd.user_id', 'left')
             // ✅ NEW: PF join
             ->join("({$pfLatestSql}) pfa", 'pfa.user_id = pd.user_id', 'left');

    if (!empty($userSelectCols)) {
        $this->db->select(implode(', ', $userSelectCols), false);
    }

    // Position (either users text or FK to hrm_positions)
    $userPosIdCols   = ['emp_title','position_id','hrm_position_id','designation_id'];
    $userPosTextCols = ['position','job_title','title','designation'];
    $userPosCol = null;
    foreach (array_merge($userPosIdCols, $userPosTextCols) as $c) {
        if (isset($uFields[$c])) { $userPosCol = $c; break; }
    }

    $posRaw = $this->tbl_positions;
    $posTbl = $this->tbl($posRaw);
    $positionSelected = false;

    if ($userPosCol !== null) {
        if (in_array($userPosCol, $userPosTextCols, true)) {
            $this->db->select('u.`'.$userPosCol.'` AS position_title', false);
            $positionSelected = true;
        } elseif (in_array($userPosCol, $userPosIdCols, true) && $this->db->table_exists($posTbl)) {
            $posFields = $this->db->list_fields($posRaw);
            $titleCandidates = ['title','position_title','name','position_name','designation','job_title'];
            $parts = [];
            foreach ($titleCandidates as $pc) {
                if (in_array($pc, $posFields, true)) $parts[] = 'p.`'.$pc.'`';
            }
            $this->db->join($posTbl.' p', 'p.id = u.`'.$userPosCol.'`', 'left');
            if (!empty($parts)) {
                $this->db->select('COALESCE('.implode(', ', $parts).') AS position_title', false);
                $positionSelected = true;
            }
        }
    }
    if (!$positionSelected) {
        $this->db->select('NULL AS position_title', false);
    }

    // Department
    $deptRaw = $this->tbl_departments;
    $deptTbl = $this->tbl($deptRaw);
    if (isset($uFields['emp_department']) && $this->db->table_exists($deptTbl)) {
        $deptFields = $this->db->list_fields($deptRaw);
        if (in_array('name', $deptFields, true)) {
            $this->db->join($deptTbl.' d', 'd.id = u.emp_department', 'left');
            $this->db->select('d.`name` AS department_name', false);
        } else {
            $this->db->select('NULL AS department_name', false);
        }
    } else {
        $this->db->select('NULL AS department_name', false);
    }

    // << NEW: select the 3 loan totals from the aggregate
    $this->db->select('
        COALESCE(la.loan_taken_total, 0)  AS loan_taken_total,
        COALESCE(la.loan_total_paid, 0)   AS loan_total_paid,
        COALESCE(la.loan_balance_total, 0) AS loan_balance_total
    ', false);

// ✅ NEW: PF projected columns for the view
    $this->db->select('
        COALESCE(pfa.pf_account_id, NULL)            AS pf_account_id,
        COALESCE(pfa.pf_current_balance, 0)          AS pf_current_balance,
        COALESCE(pfa.uan_number, NULL)               AS pf_uan_number,
        COALESCE(pfa.pf_member_id, NULL)             AS pf_member_id,
        COALESCE(pfa.employee_contribution_rate, 0)  AS pf_emp_rate,
        COALESCE(pfa.employer_contribution_rate, 0)  AS pf_empr_rate,
        COALESCE(pfa.wage_base_ceiling, 0)           AS pf_wage_ceiling
    ', false);
    
    
    $row = $this->db->get()->row_array() ?: [];

    if ($row) {
        // keep your existing enrichment
        $row['employee_name'] = trim((string)($row['fullname'] ?? (($row['firstname'] ?? '').' '.($row['lastname'] ?? ''))));
        if ($row['employee_name'] === '') $row['employee_name'] = 'UID:' . (int)($row['user_id'] ?? 0);

        // ensure computed totals exist
        if (!isset($row['gross_pay'])) {
            $row['gross_pay'] =
                (float)($row['basic_salary'] ?? 0) +
                (float)($row['allowances_total'] ?? 0) +
                (float)($row['overtime_amount'] ?? 0) +
                (float)($row['bonus_amount'] ?? 0) +
                (float)($row['commission_amount'] ?? 0) +
                (float)($row['other_earnings'] ?? 0) +
                (float)($row['arrears_amount'] ?? 0);
        }
        if (!isset($row['pf_deduction'])) {
            $row['pf_deduction'] = (float)($row['pf_employee'] ?? 0);
        }
        if (!isset($row['total_deductions'])) {
            $row['total_deductions'] =
                (float)($row['deductions_total'] ?? 0) +
                (float)($row['leave_deduction'] ?? 0) +
                (float)($row['tax_amount'] ?? 0) +
                (float)($row['pf_deduction'] ?? 0) +
                (float)($row['loan_total_deduction'] ?? 0) +
                (float)($row['advance_total_deduction'] ?? 0);
        }
        if (!isset($row['net_pay'])) {
            $row['net_pay'] = max(0, (float)$row['gross_pay'] - (float)$row['total_deductions']);
        }
    }

    return $row;
}


    /* ------- Employee resolution for runs ------- */
    public function find_employees_for_run(string $scope = 'all', array $params = []): array
    {
        $uRaw = $this->tbl_users;
        $uTbl = $this->tbl($uRaw);
        $fields = $this->columns($uRaw);

        $this->db->from($uTbl.' u')->select('u.id');
        $this->where_active_users();

        switch ($scope) {
            case 'department':
                $deptId = (int)($params['department_id'] ?? 0);
                if ($deptId <= 0 || !isset($fields['emp_department'])) return [];
                $this->db->where('u.emp_department', $deptId);
                break;

            case 'team':
                $teamId = (int)($params['team_id'] ?? 0);
                if ($teamId <= 0 || !isset($fields['emp_team'])) return [];
                $this->db->where('u.emp_team', $teamId);
                break;

            case 'selected':
                $ids = array_map('intval', (array)($params['user_ids'] ?? []));
                $ids = array_values(array_filter($ids, fn($v) => $v > 0));
                if (empty($ids)) return [];
                $this->db->where_in('u.id', $ids);
                break;

            case 'all':
            default:
                // no extra filters
                break;
        }

        $this->db->order_by('u.fullname', 'ASC');
        $rows = $this->db->get()->result_array();
        return array_map(static fn($r) => ['id' => (int)$r['id']], $rows);
    }

    /* ------- Convenience aggregate used by Payroll::index view ------- */
    public function runs_index_data(): array
    {
        return [
            'runs'        => $this->get_runs_summary(),
            'departments' => $this->list_departments(),
            'teams'       => $this->list_teams(),
            'users_all'   => $this->list_all_users_for_dropdown(),
        ];
    }


public function latest_payslip_for_user(int $user_id): array
{
    if ($user_id <= 0) return [];

    $pdTbl = $this->tbl($this->tbl_details);
    if (!$this->db->table_exists($pdTbl)) return [];

    // Order by pay_date if set, otherwise period_end; newest first
    $this->db->from($pdTbl.' pd')
             ->where('pd.user_id', (int)$user_id)
             ->order_by('COALESCE(pd.pay_date, pd.period_end)', 'DESC', false)
             ->order_by('pd.id', 'DESC')
             ->limit(1);

    $row = $this->db->get()->row_array() ?: [];
    if (empty($row)) return [];

    // Reuse your existing enrich logic (totals, pf fallback, etc.)
    // If you keep that logic inside payslip_row(), you can re-fetch by id:
    if (!empty($row['id'])) {
        return $this->payslip_row((int)$row['id']);
    }
    return $row;
}


protected function pf_latest_subquery(): string
{
    $pfAccTbl = $this->db->dbprefix('payroll_pf_accounts');
    return "
        SELECT a.user_id,
               a.id              AS pf_account_id,
               a.current_balance AS pf_current_balance
        FROM {$pfAccTbl} a
        INNER JOIN (
            SELECT user_id, MAX(id) AS mid
            FROM {$pfAccTbl}
            WHERE account_status IN ('active','open','enabled')
            GROUP BY user_id
        ) x ON x.user_id = a.user_id AND x.mid = a.id
    ";
}
    
}
