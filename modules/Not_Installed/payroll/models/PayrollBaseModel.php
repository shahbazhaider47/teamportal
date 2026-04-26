<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Base model for shared helpers, table names, and safe joins.
 * All payroll models extend this.
 */
class PayrollBaseModel extends CI_Model
{
    // Unprefixed table names (match your schema)
    protected $tbl_users           = 'users';
    protected $tbl_details         = 'payroll_details';
    protected $tbl_loans           = 'payroll_loans';
    protected $tbl_advances        = 'payroll_advances';
    protected $tbl_positions       = 'hrm_positions';
    protected $tbl_departments     = 'departments';
    protected $tbl_teams           = 'teams';
    protected $tbl_pf_accounts     = 'payroll_pf_accounts';
    protected $tbl_pf_transactions = 'payroll_pf_transactions';

    public function __construct()
    {
        parent::__construct();
    }

    /** Prefixed table name */
    protected function tbl(string $raw): string
    {
        return $this->db->dbprefix($raw);
    }

    /** Current timestamp string */
    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** Safe list of columns for a table (unprefixed name) */
    protected function columns(string $raw): array
    {
        return array_flip($this->db->list_fields($raw));
    }

    /** Add "active user" filter if such a column exists */
    protected function where_active_users(): void
    {
        $uCols = $this->columns($this->tbl_users);
        if (isset($uCols['is_active'])) {
            $this->db->where('u.is_active', 1);
        } elseif (isset($uCols['active'])) {
            $this->db->where('u.active', 1);
        } elseif (isset($uCols['status'])) {
            $this->db->where('u.status', 'active');
        }
    }

    /* ---------- Common picklists ---------- */

    public function list_all_users_for_dropdown(): array
    {
        $uTbl = $this->tbl($this->tbl_users);
        return $this->db->select('id, emp_id, firstname, lastname, fullname, is_active')
                        ->from($uTbl)
                        ->order_by('firstname,lastname', 'ASC')
                        ->get()->result_array();
    }

    public function list_departments(): array
    {
        $raw = $this->tbl_departments;
        $tbl = $this->tbl($raw);
        if (!$this->db->table_exists($tbl)) return [];
        return $this->db->get($raw)->result_array();
    }

    public function list_teams(): array
    {
        $raw = $this->tbl_teams;
        $tbl = $this->tbl($raw);
        if (!$this->db->table_exists($tbl)) return [];
        return $this->db->get($raw)->result_array();
    }

    /* ---------- User payroll basics ---------- */

    /** Return normalized joining date and base salary (current > joining) */
    public function user_basics(int $user_id): array
    {
        $uTbl = $this->tbl($this->tbl_users);

        $row = $this->db->select('id, emp_joining, current_salary, joining_salary')
                        ->from($uTbl)->where('id', $user_id)
                        ->get()->row_array() ?: [];

        $empJoining = (!empty($row['emp_joining']) && $row['emp_joining'] !== '0000-00-00')
            ? date('Y-m-d', strtotime($row['emp_joining'])) : null;

        $base = 0.0;
        if (!empty($row['current_salary']) && (float)$row['current_salary'] > 0) {
            $base = (float)$row['current_salary'];
        } elseif (!empty($row['joining_salary']) && (float)$row['joining_salary'] > 0) {
            $base = (float)$row['joining_salary'];
        }

        return ['emp_joining' => $empJoining, 'base_salary' => $base];
    }

    /** Loan eligibility evaluation */
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
        if ($base_salary <= 0) return ($limitCode === 'any_amount') ? null : null;

        switch ($limitCode) {
            case 'half_salary':  return $base_salary * 0.5;
            case 'full_salary':  return $base_salary * 1.0;
            case 'two_salaries': return $base_salary * 2.0;
            case 'any_amount':   return null;
            default:             return $base_salary * 0.5;
        }
    }

    /* ---------- Shared math helpers ---------- */

    protected function apply_rounding(float $v, string $mode): float
    {
        switch ($mode) {
            case 'nearest': return round($v, 0);
            case 'down':    return floor($v);
            case 'up':      return ceil($v);
            case 'none':    return (float)$v;
            case 'inherit':
            default:        return (float)$v; // hook a system setting here later
        }
    }
}
