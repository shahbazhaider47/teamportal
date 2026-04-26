<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payroll_model extends CI_Model
{
    protected $tbl_users        = 'users';
    protected $tbl_details      = 'payroll_details';
    protected $tbl_loans        = 'payroll_loans';
    protected $tbl_advances     = 'payroll_advances';
    protected $tbl_positions    = 'hrm_positions';    

    public function __construct()
    {
        parent::__construct();
    }

    /* ==============================
     * PUBLIC FACADE: controller API
     * ============================== */

    /** For Payroll::index() */
    public function index_data(): array
    {
        return [
            'rows'        => $this->get_payroll_details(),     // existing logic retained
            'departments' => $this->list_departments(),        // private helper below
            'teams'       => $this->list_teams(),              // private helper below
            'users_all'   => $this->list_all_users_for_dropdown(),
        ];
    }

    /** For Payroll::run() */
    public function run_employees(string $scope = 'all', array $params = []): array
    {
        // reuse your working method
        return $this->find_employees_for_run($scope, $params);
    }

// Generate a run id (no separate runs table needed)
public function generate_run_id(): int
{
    // yyyymmddHHMMSS + random 2 digits to reduce collision
    return (int)(date('YmdHis') . sprintf('%02d', random_int(0, 99)));
}

// Snapshot minimal user payroll inputs (extend as needed)
private function user_snapshot(int $user_id): array
{
    $uTbl = $this->db->dbprefix($this->tbl_users);
    $row = $this->db->select('id, current_salary, joining_salary, emp_department, emp_team')
                    ->from($uTbl)->where('id', $user_id)->get()->row_array() ?: [];

    $base = 0.00;
    if (!empty($row['current_salary']) && (float)$row['current_salary'] > 0) {
        $base = (float)$row['current_salary'];
    } elseif (!empty($row['joining_salary']) && (float)$row['joining_salary'] > 0) {
        $base = (float)$row['joining_salary'];
    }

    return [
        'base_salary'   => $base,
        'department_id' => isset($row['emp_department']) ? (int)$row['emp_department'] : null,
        'team_id'       => isset($row['emp_team']) ? (int)$row['emp_team'] : null,
    ];
}

// Rounding helper
private function apply_rounding(float $v, string $mode): float
{
    switch ($mode) {
        case 'nearest': return round($v, 0);
        case 'down':    return floor($v);
        case 'up':      return ceil($v);
        case 'none':    return (float)$v;
        case 'inherit':
        default:
            // If you add a system setting later, apply it here; for now behave like 'none'
            return (float)$v;
    }
}

// Insert or update same user+period (unique key uq_pd_user_period)
private function upsert_detail(array $row): bool
{
    $pTbl = $this->db->dbprefix($this->tbl_details);

    $exists = $this->db->select('id')
        ->from($pTbl)
        ->where('user_id', (int)$row['user_id'])
        ->where('period_start', $row['period_start'])
        ->where('period_end', $row['period_end'])
        ->limit(1)->get()->row_array();

    if ($exists) {
        $this->db->where('id', (int)$exists['id'])->update($pTbl, $row);
        return $this->db->affected_rows() >= 0;
    }

    $this->db->insert($pTbl, $row);
    return $this->db->affected_rows() > 0;
}

    /** For Payroll::loans() */
    public function loans_data(): array
    {
        return [
            'loans'     => $this->get_loans(),
            'users_all' => $this->list_all_users_for_dropdown(),
        ];
    }

    /** For get_loan_json() */
    public function loan(int $id): ?array
    {
        return $this->get_loan($id);
    }

    /** For save_loan() */
    public function loan_save(array $payload, ?int $id = null): bool
    {
        // keep your normalization pipeline
        return $this->save_loan($payload, $id);
    }

    /** For delete_loan() */
    public function loan_delete(int $id): bool
    {
        return $this->delete_loan($id);
    }

    /* =======================================================
     * BELOW: Your existing methods (kept) + minor adjustments
     * ======================================================= */

    /** Existing: details grid (unchanged) */
public function get_payroll_details(): array
{
    $uTbl = $this->db->dbprefix($this->tbl_users);
    $pTbl = $this->db->dbprefix($this->tbl_details); // 'payroll_details'

    if (!$this->db->table_exists($pTbl)) {
        return [];
    }

    $sql = "
        SELECT
            pd.*,
            u.emp_id,
            u.fullname,

            /* Derived convenience fields */
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

    /* ===== Loans (existing behavior kept) ===== */

    public function get_loans(): array
    {
        $lTbl = $this->db->dbprefix($this->tbl_loans);
        $uTbl = $this->db->dbprefix($this->tbl_users);

        return $this->db->select("pl.*, u.emp_id, u.firstname, u.lastname, u.fullname")
                        ->from($lTbl . ' pl')
                        ->join($uTbl . ' u', 'u.id = pl.user_id', 'left')
                        ->order_by('pl.created_at', 'DESC')
                        ->get()->result_array();
    }

    public function get_loan(int $id): ?array
    {
        $lTbl = $this->db->dbprefix($this->tbl_loans);
        $uTbl = $this->db->dbprefix($this->tbl_users);

        $row = $this->db->select("pl.*, u.emp_id, u.firstname, u.lastname, u.fullname")
                        ->from($lTbl . ' pl')
                        ->join($uTbl . ' u', 'u.id = pl.user_id', 'left')
                        ->where('pl.id', $id)
                        ->get()->row_array();

        return $row ?: null;
    }

    public function save_loan(array $payload, ?int $id = null): bool
    {
        // (Your normalization logic preserved exactly as you wrote it)
        $loan_taken         = isset($payload['loan_taken'])         ? (float)$payload['loan_taken']         : 0.0;
        $total_installments = isset($payload['total_installments']) ? (int)$payload['total_installments']   : 0;
        $monthly_installment= isset($payload['monthly_installment'])? (float)$payload['monthly_installment']: 0.0;
        $total_paid         = isset($payload['total_paid'])         ? (float)$payload['total_paid']         : 0.0;
        $current_installment= isset($payload['current_installment'])? (int)$payload['current_installment']  : 0;

        if ($monthly_installment <= 0 && $loan_taken > 0 && $total_installments > 0) {
            $monthly_installment = $loan_taken / $total_installments;
        }
        if ($total_paid > 0 && $monthly_installment > 0) {
            $current_installment = (int) floor($total_paid / $monthly_installment);
        }
        $balance = $loan_taken - $total_paid;

        $payload['loan_taken']          = $loan_taken;
        $payload['total_installments']  = $total_installments;
        $payload['monthly_installment'] = $monthly_installment;
        $payload['total_paid']          = $total_paid;
        $payload['current_installment'] = $current_installment;
        $payload['balance']             = $balance;

        $payload['payback_type'] = $payload['payback_type'] ?? 'monthly';
        $payload['status']       = $payload['status']       ?? 'active';

        return $this->upsert_loan($payload, $id);
    }

    public function upsert_loan(array $payload, ?int $id = null): bool
    {
        $now = date('Y-m-d H:i:s');

        if (!empty($id)) {
            $payload['updated_at'] = $now;
            $this->db->where('id', (int)$id)->update($this->tbl_loans, $payload);
            return $this->db->affected_rows() >= 0;
        }

        $payload['created_at'] = $now;
        $payload['updated_at'] = $now;
        $this->db->insert($this->tbl_loans, $payload);
        return $this->db->affected_rows() > 0;
    }

    public function delete_loan(int $id): bool
    {
        $this->db->where('id', $id)->delete($this->tbl_loans);
        return $this->db->affected_rows() > 0;
    }

    /* ==========================
     * PRIVATE/INTERNAL HELPERS
     * ========================== */

    private function list_all_users_for_dropdown(): array
    {
        $uTbl = $this->db->dbprefix($this->tbl_users);
        return $this->db->select('id, emp_id, firstname, lastname, fullname, is_active')
                        ->from($uTbl)
                        ->order_by('firstname,lastname', 'ASC')
                        ->get()->result_array();
    }

    private function list_departments(): array
    {
        $tbl = $this->db->dbprefix('departments');
        if (!$this->db->table_exists($tbl)) return [];
        return $this->db->get('departments')->result_array();
    }

    private function list_teams(): array
    {
        $tbl = $this->db->dbprefix('teams');
        if (!$this->db->table_exists($tbl)) return [];
        return $this->db->get('teams')->result_array();
    }

    /* ==========================
     * BACKWARD-COMPAT ALIASES
     * ========================== */
    public function get_departments(): array { return $this->list_departments(); }
    public function get_teams(): array { return $this->list_teams(); }
    public function get_active_users_min(): array { return $this->list_all_users_for_dropdown(); }


// loans methos 

public function loans_for_user(int $user_id, bool $with_user = false): array
{
    $lTbl = $this->db->dbprefix($this->tbl_loans);
    $this->db->from($lTbl . ' pl')
             ->where('pl.user_id', $user_id)
             ->order_by('pl.created_at', 'DESC');

    if ($with_user) {
        $uTbl = $this->db->dbprefix($this->tbl_users);
        $this->db->select('pl.*, u.emp_id, u.firstname, u.lastname, u.fullname')
                 ->join($uTbl . ' u', 'u.id = pl.user_id', 'left');
    } else {
        $this->db->select('pl.*');
    }

    return $this->db->get()->result_array();
}


public function user_basics(int $user_id): array
{
    $uTbl = $this->db->dbprefix($this->tbl_users);

    $row = $this->db->select('id, emp_joining, current_salary, joining_salary')
        ->from($uTbl)
        ->where('id', $user_id)
        ->get()->row_array() ?: [];

    // Normalize to Y-m-d and treat 0000-00-00 as null
    $empJoining = (!empty($row['emp_joining']) && $row['emp_joining'] !== '0000-00-00')
        ? date('Y-m-d', strtotime($row['emp_joining']))
        : null;

    $base = 0.0;
    if (!empty($row['current_salary']) && (float)$row['current_salary'] > 0) {
        $base = (float)$row['current_salary'];
    } elseif (!empty($row['joining_salary']) && (float)$row['joining_salary'] > 0) {
        $base = (float)$row['joining_salary'];
    }

    return [
        'emp_joining' => $empJoining,
        'base_salary' => $base,
    ];
}

public function meets_loan_eligibility(int $user_id, string $rule): array
{
    $b = $this->user_basics($user_id);
    $join = $b['emp_joining'] ?? null;

    if (empty($join)) {
        return [true, 'Your joining date is not on file.'];
    }

    $joinDate = DateTime::createFromFormat('Y-m-d', $join);
    if (!$joinDate) {
        return [false, 'Invalid joining date format.'];
    }

    $need = clone $joinDate;
    switch ($rule) {
        case 'on_joining': break;
        case 'after_probation': $need->modify('+3 months'); break;
        case 'after_6_months':  $need->modify('+6 months'); break;
        case 'after_1_year':    $need->modify('+12 months'); break;
    }

    $ok  = (new DateTime()) >= $need;
    $msg = $ok ? 'Eligible.' : 'Not eligible until ' . $need->format('Y-m-d') . '.';
    return [$ok, $msg];
}


public function max_loan_allowed(float $base_salary, string $limitCode): ?float
{
    if ($base_salary <= 0) {
        return ($limitCode === 'any_amount') ? null : null;
    }

    switch ($limitCode) {
        case 'half_salary':  return $base_salary * 0.5;
        case 'full_salary':  return $base_salary * 1.0;
        case 'two_salaries': return $base_salary * 2.0;
        case 'any_amount':   return null; // unlimited
        default:             return $base_salary * 0.5;
    }
}


/**
 * Get salary advances for a specific user.
 */
public function advances_all(bool $with_user = true): array
{
    $aTbl = $this->db->dbprefix($this->tbl_advances);
    $this->db->from($aTbl.' pa')
             ->order_by('pa.requested_at','DESC')
             ->order_by('pa.id','DESC');

    if ($with_user) {
        $uTbl = $this->db->dbprefix($this->tbl_users);
        $this->db->select("
            pa.*,
            u.fullname AS requester_name, u.emp_id,
            ap.fullname AS approved_by_name
        ", false);
        $this->db->join($uTbl.' u',  'u.id  = pa.user_id',    'left');
        $this->db->join($uTbl.' ap', 'ap.id = pa.approved_by', 'left');
    } else {
        $this->db->select('pa.*');
    }

    return $this->db->get()->result_array();
}

public function advances_for_user(int $user_id, bool $with_user = false): array
{
    $aTbl = $this->db->dbprefix($this->tbl_advances);
    $this->db->from($aTbl.' pa')
             ->where('pa.user_id', $user_id)
             ->order_by('pa.requested_at','DESC')
             ->order_by('pa.id','DESC');

    if ($with_user) {
        $uTbl = $this->db->dbprefix($this->tbl_users);
        $this->db->select("
            pa.id, pa.user_id, pa.amount, pa.paid, pa.balance,
            pa.requested_at, pa.approved_at, pa.approved_by, pa.notes, pa.status,
            u.fullname AS requester_name,
            ap.fullname AS approved_by_name
        ", false);
        $this->db->join($uTbl.' u',  'u.id  = pa.user_id',    'left');
        $this->db->join($uTbl.' ap', 'ap.id = pa.approved_by', 'left');
    } else {
        $this->db->select("
            pa.id, pa.user_id, pa.amount, pa.paid, pa.balance,
            pa.requested_at, pa.approved_at, pa.approved_by, pa.notes, pa.status
        ", false);
    }

    return $this->db->get()->result_array();
}


// Payroll_model.php
public function advance(int $id, bool $with_user = true): array
{
    $aTbl = $this->db->dbprefix($this->tbl_advances);
    $uTbl = $this->db->dbprefix($this->tbl_users);

    $this->db->from($aTbl.' pa')
             ->where('pa.id', (int)$id);

    if ($with_user) {
        $this->db->select("
            pa.*,
            u.fullname      AS requester_name,
            u.emp_id        AS requester_emp_id,
            ap.fullname     AS approved_by_name
        ", false)
        ->join($uTbl.' u',  'u.id  = pa.user_id',    'left')
        ->join($uTbl.' ap', 'ap.id = pa.approved_by','left');
    } else {
        $this->db->select('pa.*');
    }

    return $this->db->get()->row_array() ?: [];
}


public function advance_save(array $data, ?int $id = null): bool
{
    $tbl = $this->db->dbprefix($this->tbl_advances);
    if ($id) {
        $this->db->where('id', (int)$id)->update($tbl, $data);
        return $this->db->affected_rows() >= 0;
    }
    return $this->db->insert($tbl, $data);
}


public function save_advance_by_user(int $user_id, float $amount, string $notes = ''): bool
{
    if ($user_id <= 0 || $amount <= 0) {
        return false;
    }

    // Optional: ensure the user exists (prevents UID:0 or orphan rows)
    $uTbl = $this->db->dbprefix($this->tbl_users);
    $exists = $this->db->select('id')->from($uTbl)->where('id', $user_id)->limit(1)->get()->row_array();
    if (!$exists) {
        return false;
    }

    $row = [
        'user_id'      => $user_id,
        'amount'       => round($amount, 2),
        'paid'         => 0,
        'balance'      => round($amount, 2),
        'requested_at' => date('Y-m-d H:i:s'),
        'approved_at'  => null,
        'approved_by'  => null,
        'status'       => 'requested',
        'notes'        => (string)$notes,
    ];

    $aTbl = $this->db->dbprefix($this->tbl_advances);
    return $this->db->insert($aTbl, $row);
}

public function advance_delete(int $id): bool
{
    $tbl = $this->db->dbprefix($this->tbl_advances);
    $this->db->where('id', $id)->delete($tbl);
    return $this->db->affected_rows() > 0;
}


public function payslip_row(int $id): array
{
    $pdTbl = $this->db->dbprefix($this->tbl_details); // payroll_details
    $uRaw  = $this->tbl_users;                         // 'users' (unprefixed)
    $uTbl  = $this->db->dbprefix($uRaw);               // prefixed users

    // Build a safe list of user columns that actually exist
    $uFields = array_flip($this->db->list_fields($uRaw));
    $wantUserCols = [
        'emp_id','firstname','lastname','fullname','emp_joining','email',
        'bank_name','bank_account_number','bank_branch','current_salary','emp_department'
    ];
    $userSelectCols = [];
    foreach ($wantUserCols as $c) {
        if (isset($uFields[$c])) $userSelectCols[] = 'u.`'.$c.'`';
    }

    // Detect which users column holds position info
    $userPosIdCols   = ['emp_title','position_id','hrm_position_id','designation_id']; // numeric FK → hrm_positions.id
    $userPosTextCols = ['position','job_title','title','designation'];                 // text title stored on users
    $userPosCol = null;
    foreach (array_merge($userPosIdCols, $userPosTextCols) as $c) {
        if (isset($uFields[$c])) { $userPosCol = $c; break; }
    }

    // Base query
    $this->db->from($pdTbl.' pd')
             ->where('pd.id', (int)$id)
             ->select('pd.*', false)
             ->join($uTbl.' u', 'u.id = pd.user_id', 'left');

    if (!empty($userSelectCols)) {
        $this->db->select(implode(', ', $userSelectCols), false);
    }

    /* -------- Position (hrm_positions) -------- */
    $positionSelected = false;
    $posRaw = $this->tbl_positions ?: 'hrm_positions';
    $posTbl = $this->db->dbprefix($posRaw);

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

    /* -------- Department (departments) -------- */
    $deptSelected = false;
    $deptRaw = 'departments';
    $deptTbl = $this->db->dbprefix($deptRaw);

    if (isset($uFields['emp_department']) && $this->db->table_exists($deptTbl)) {
        $deptFields = $this->db->list_fields($deptRaw);
        // Use the same naming you showed in get_all_users(): d.name AS department_name
        if (in_array('name', $deptFields, true)) {
            $this->db->join($deptTbl.' d', 'd.id = u.emp_department', 'left');
            $this->db->select('d.`name` AS department_name', false);
            $deptSelected = true;
        }
    }
    if (!$deptSelected) {
        $this->db->select('NULL AS department_name', false);
    }

    $row = $this->db->get()->row_array() ?: [];

    // Fallbacks/derived values for the payslip view
    if ($row) {
        $row['employee_name'] = trim((string)($row['fullname'] ?? (($row['firstname'] ?? '').' '.($row['lastname'] ?? ''))));
        if ($row['employee_name'] === '') $row['employee_name'] = 'UID:' . (int)($row['user_id'] ?? 0);

        // Prefer computed designation; avoid showing numeric FK
        $row['designation'] = trim((string)($row['position_title'] ?? ''));
        if ($row['designation'] === '') {
            $row['designation'] = is_numeric($row['emp_title'] ?? null) ? '' : (string)($row['emp_title'] ?? '');
        }

        if (!isset($row['gross_pay'])) {
            $row['gross_pay'] =
                (float)($row['basic_salary'] ?? 0) +
                (float)($row['allowances_total'] ?? 0) +
                (float)($row['overtime_amount'] ?? 0) +
                (float)($row['bonus_amount'] ?? 0) +
                (float)($row['commission_amount'] ?? 0) +
                (float)($row['other_earnings'] ?? 0);
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


// ─────────────────────────────────────────────────────────────
// RUNS INDEX DATA (for Payroll::index)
// ─────────────────────────────────────────────────────────────
public function runs_index_data(): array
{
    return [
        'runs'        => $this->get_runs_summary(),
        'departments' => $this->list_departments(),
        'teams'       => $this->list_teams(),
        'users_all'   => $this->list_all_users_for_dropdown(),
    ];
}

/**
 * Aggregate one row per run_id with core metadata & KPIs.
 */
public function get_runs_summary(int $limit = 250): array
{
    $pTbl = $this->db->dbprefix($this->tbl_details);

    if (!$this->db->table_exists($pTbl)) {
        return [];
    }

    $sql = "
        SELECT
            pd.run_id,
            pd.pay_period,
            pd.period_start,
            pd.period_end,
            pd.pay_date,
            pd.status_run,
            COUNT(*)              AS employees_count,
            SUM(COALESCE(pd.basic_salary,0))        AS sum_basic,
            SUM(COALESCE(pd.allowances_total,0))    AS sum_allowances,
            SUM(COALESCE(pd.overtime_amount,0))     AS sum_overtime,
            SUM(COALESCE(pd.bonus_amount,0))        AS sum_bonus,
            SUM(COALESCE(pd.commission_amount,0))   AS sum_commission,
            SUM(COALESCE(pd.other_earnings,0))      AS sum_other,
            /* include arrears if available */
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

/** One run’s high-level summary (for modal) */
public function get_run_summary(int $run_id): ?array
{
    $row = null;
    foreach ($this->get_runs_summary(100000) as $r) {
        if ((int)$r['run_id'] === (int)$run_id) { $row = $r; break; }
    }
    return $row ?: null;
}

/** Full rows (users) for a run, with user join (for the run details page) */
public function get_run_rows(int $run_id): array
{
    $uTbl = $this->db->dbprefix($this->tbl_users);
    $pTbl = $this->db->dbprefix($this->tbl_details);

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

/** Delete an entire run (all rows for run_id) */
public function delete_run(int $run_id): bool
{
    $tbl = $this->db->dbprefix($this->tbl_details);
    $this->db->where('run_id', (int)$run_id)->delete($tbl);
    return $this->db->affected_rows() > 0;
}


/**
 * Resolve employees to include in a run based on $scope and $params.
 * Returns array of ['id' => <user_id>] as expected by PayrollRun_model::run_payroll().
 *
 * $scope: all | department | team | selected
 * $params: ['department_id' => int, 'team_id' => int, 'user_ids' => int[]]
 */
public function find_employees_for_run(string $scope = 'all', array $params = []): array
{
    $uRaw = $this->tbl_users;                  // unprefixed table name e.g. 'users'
    $uTbl = $this->db->dbprefix($uRaw);        // prefixed table
    $fields = array_flip($this->db->list_fields($uRaw));

    $this->db->from($uTbl . ' u')->select('u.id');

    // Prefer to include only active staff if such a column exists
    if (isset($fields['is_active'])) {
        $this->db->where('u.is_active', 1);
    } elseif (isset($fields['active'])) {
        $this->db->where('u.active', 1);
    } elseif (isset($fields['status'])) {
        $this->db->where('u.status', 'active');
    }

    switch ($scope) {
        case 'department':
            $deptId = (int)($params['department_id'] ?? 0);
            if ($deptId <= 0 || !isset($fields['emp_department'])) {
                return []; // invalid input or column not present
            }
            $this->db->where('u.emp_department', $deptId);
            break;

        case 'team':
            $teamId = (int)($params['team_id'] ?? 0);
            if ($teamId <= 0 || !isset($fields['emp_team'])) {
                return [];
            }
            $this->db->where('u.emp_team', $teamId);
            break;

        case 'selected':
            $ids = array_map('intval', (array)($params['user_ids'] ?? []));
            $ids = array_values(array_filter($ids, fn($v) => $v > 0));
            if (empty($ids)) {
                return [];
            }
            $this->db->where_in('u.id', $ids);
            break;

        case 'all':
        default:
            // no extra filters
            break;
    }

    $this->db->order_by('u.fullname', 'ASC');
    $rows = $this->db->get()->result_array();

    // Map to the shape expected by run_payroll(): [['id'=>1], ['id'=>2], ...]
    return array_map(static fn($r) => ['id' => (int)$r['id']], $rows);
}

}
