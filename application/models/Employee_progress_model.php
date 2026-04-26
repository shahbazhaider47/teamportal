<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Employee_progress_model
 *
 * Aggregates ALL progress-related data for a single employee
 * from across the full application schema.
 *
 * Usage:
 *   $this->load->model('Employee_progress_model');
 *   $data = $this->Employee_progress_model->get_full_progress($user_id);
 *
 * Returns a keyed array — each section is independently usable
 * so controllers can pass only what their view needs.
 */
class Employee_progress_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ═══════════════════════════════════════════════════════════
     *  MASTER METHOD — returns everything
     * ═══════════════════════════════════════════════════════════ */

    /**
     * Build and return the full progress snapshot for one employee.
     *
     * @param  int    $user_id
     * @param  string $period_start  e.g. '2026-01-01' (defaults to 30 days ago)
     * @param  string $period_end    e.g. '2026-04-21' (defaults to today)
     * @return array
     */
    public function get_full_progress(
        int    $user_id,
        string $period_start = '',
        string $period_end   = ''
    ): array {
        if ($period_end   === '') { $period_end   = date('Y-m-d'); }
        if ($period_start === '') { $period_start = date('Y-m-d', strtotime('-30 days')); }

        return [
            'user'             => $this->get_user_profile($user_id),
            'attendance'       => $this->get_attendance_summary($user_id, $period_start, $period_end),
            'attendance_logs'  => $this->get_attendance_logs($user_id, $period_start, $period_end),
            'leaves'           => $this->get_leave_summary($user_id, $period_start, $period_end),
            'signoffs'         => $this->get_signoff_summary($user_id, $period_start, $period_end),
            'evaluations'      => $this->get_evaluation_summary($user_id),
            'latest_evaluation'=> $this->get_latest_evaluation_detail($user_id),
            'tasks'            => $this->get_task_summary($user_id, $period_start, $period_end),
            'movements'        => $this->get_movement_history($user_id),
            'increments'       => $this->get_increment_history($user_id),
            'contract'         => $this->get_active_contract($user_id),
            'assets'           => $this->get_assigned_assets($user_id),
            'loans'            => $this->get_loan_summary($user_id),
            'advances'         => $this->get_advance_summary($user_id),
            'payroll_recent'   => $this->get_recent_payroll($user_id, 3),
            'documents'        => $this->get_document_list($user_id),
            'exit_info'        => $this->get_exit_info($user_id),
            'tickets'          => $this->get_ticket_summary($user_id, $period_start, $period_end),
            'requests'         => $this->get_request_summary($user_id, $period_start, $period_end),
            'period'           => [
                'start' => $period_start,
                'end'   => $period_end,
            ],
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  1. USER PROFILE
     * ═══════════════════════════════════════════════════════════ */

public function get_user_profile(int $user_id): ?array
{
    $row = $this->db
        ->select('
            u.id, u.emp_id, u.firstname, u.lastname, u.fullname,
            u.email, u.emp_phone, u.profile_image, u.user_role,
            u.emp_joining, u.employment_type, u.contract_type,
            u.emp_dob, u.gender, u.current_salary, u.pay_period,
            u.is_active, u.last_login_at, u.last_seen_at,
            u.probation_end_date, u.confirmation_date,
            u.national_id, u.blood_group, u.nationality,
            p.title   AS position_title,
            d.name    AS department_name,
            t.name    AS team_name,
            tl.firstname AS lead_firstname,
            tl.lastname  AS lead_lastname,
            mg.firstname AS manager_firstname,
            mg.lastname  AS manager_lastname,
            o.office_name
        ')
        ->from('users u')
        ->join('hrm_positions p',   'p.id = u.emp_title',      'left')
        ->join('departments d',     'd.id = u.emp_department',  'left')
        ->join('teams t',           't.id = u.emp_team',        'left')
        ->join('users tl',          'tl.id = u.emp_teamlead',   'left')
        ->join('users mg',          'mg.id = u.emp_manager',    'left')
        ->join('company_offices o', 'o.id = u.office_id',       'left')
        ->where('u.id', $user_id)
        ->get()   // ← no table name here — from() already set it
        ->row_array();

    if (!$row) { return null; }

    $row['full_name']    = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
    $row['lead_name']    = trim(($row['lead_firstname'] ?? '') . ' ' . ($row['lead_lastname'] ?? ''));
    $row['manager_name'] = trim(($row['manager_firstname'] ?? '') . ' ' . ($row['manager_lastname'] ?? ''));
    $row['tenure_days']  = $row['emp_joining']
        ? (int)((strtotime(date('Y-m-d')) - strtotime($row['emp_joining'])) / 86400)
        : null;

    return $row;
}

    /* ═══════════════════════════════════════════════════════════
     *  2. ATTENDANCE SUMMARY
     * ═══════════════════════════════════════════════════════════ */

    public function get_attendance_summary(
        int    $user_id,
        string $from,
        string $to
    ): array {
        $rows = $this->db
            ->select('status, COUNT(*) AS cnt')
            ->from('attendance')
            ->where('user_id', $user_id)
            ->where('attendance_date >=', $from)
            ->where('attendance_date <=', $to)
            ->group_by('status')
            ->get()
            ->result_array();

        $summary = ['P' => 0, 'A' => 0, 'L' => 0, 'H' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $key = strtoupper($r['status'] ?? '');
            $summary[$key] = (int)$r['cnt'];
            $summary['total'] += (int)$r['cnt'];
        }

        // Attendance percentage
        $workable = $summary['P'] + $summary['A'];
        $summary['attendance_pct'] = $workable > 0
            ? round(($summary['P'] / $workable) * 100, 1)
            : null;

        // Late arrivals from attendance_logs
        $lateRow = $this->db->query("
            SELECT COUNT(DISTINCT DATE(datetime)) AS cnt
              FROM attendance_logs
             WHERE user_id = ?
               AND status  = 'check_in'
               AND DATE(datetime) BETWEEN ? AND ?
               AND deleted_at IS NULL
               AND TIME(datetime) > (
                   SELECT shift_start_time
                     FROM work_shifts ws
                    WHERE ws.id = (
                        SELECT work_shift FROM users WHERE id = ?
                    )
                    LIMIT 1
               )
        ", [$user_id, $from, $to, $user_id])->row_array();

        $summary['late_arrivals'] = (int)($lateRow['cnt'] ?? 0);

        // Average check-in time
        $avgRow = $this->db->query("
            SELECT TIME_FORMAT(SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(datetime)))), '%H:%i') AS avg_time
              FROM attendance_logs
             WHERE user_id   = ?
               AND status    = 'check_in'
               AND DATE(datetime) BETWEEN ? AND ?
               AND deleted_at IS NULL
        ", [$user_id, $from, $to])->row_array();

        $summary['avg_checkin_time'] = $avgRow['avg_time'] ?? null;

        return $summary;
    }

    /* ═══════════════════════════════════════════════════════════
     *  3. ATTENDANCE LOGS (recent 30 entries)
     * ═══════════════════════════════════════════════════════════ */

    public function get_attendance_logs(
        int    $user_id,
        string $from,
        string $to,
        int    $limit = 30
    ): array {
        return $this->db
            ->select('id, datetime, status, log_type, approval_status, ip_address, created_at')
            ->from('attendance_logs')
            ->where('user_id', $user_id)
            ->where('DATE(datetime) >=', $from)
            ->where('DATE(datetime) <=', $to)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('datetime', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /* ═══════════════════════════════════════════════════════════
     *  4. LEAVE SUMMARY
     * ═══════════════════════════════════════════════════════════ */

    public function get_leave_summary(
        int    $user_id,
        string $from,
        string $to
    ): array {
        $rows = $this->db
            ->select('
                al.id, al.start_date, al.end_date, al.total_days,
                al.status, al.reason, al.created_at,
                lt.name AS leave_type, lt.type AS paid_type, lt.color
            ')
            ->from('att_leaves al')
            ->join('leave_types lt', 'lt.id = al.leave_type_id', 'left')
            ->where('al.user_id', $user_id)
            ->where('al.deleted_at IS NULL', null, false)
            ->where('al.start_date >=', $from)
            ->where('al.start_date <=', $to)
            ->order_by('al.start_date', 'DESC')
            ->get()
            ->result_array();

        // Aggregate by status
        $totals = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'days_taken' => 0.0];
        foreach ($rows as $r) {
            $s = $r['status'] ?? 'pending';
            $totals[$s] = ($totals[$s] ?? 0) + 1;
            if ($s === 'approved') {
                $totals['days_taken'] += (float)($r['total_days'] ?? 0);
            }
        }

        return [
            'list'   => $rows,
            'totals' => $totals,
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  5. SIGNOFF / DAILY PERFORMANCE SUBMISSIONS
     * ═══════════════════════════════════════════════════════════ */

    public function get_signoff_summary(
        int    $user_id,
        string $from,
        string $to
    ): array {
        $rows = $this->db
            ->select('
                ss.id, ss.submission_date, ss.total_points,
                ss.achieved_targets, ss.status, ss.reviewed_at,
                ss.created_at,
                sf.title AS form_title,
                rv.firstname AS reviewer_firstname,
                rv.lastname  AS reviewer_lastname
            ')
            ->from('signoff_submissions ss')
            ->join('signoff_forms sf', 'sf.id = ss.form_id',       'left')
            ->join('users rv',         'rv.id = ss.reviewed_by',   'left')
            ->where('ss.user_id', $user_id)
            ->where('ss.submission_date >=', $from)
            ->where('ss.submission_date <=', $to)
            ->order_by('ss.submission_date', 'DESC')
            ->get()
            ->result_array();

        // Aggregate stats
        $totalPoints    = 0.0;
        $totalTargets   = 0.0;
        $reviewedCount  = 0;
        foreach ($rows as &$r) {
            $totalPoints  += (float)($r['total_points']      ?? 0);
            $totalTargets += (float)($r['achieved_targets']  ?? 0);
            if (!empty($r['reviewed_at'])) { $reviewedCount++; }
            $r['reviewer_name'] = trim(
                ($r['reviewer_firstname'] ?? '') . ' ' . ($r['reviewer_lastname'] ?? '')
            );
        }
        unset($r);

        $count = count($rows);
        return [
            'list'             => $rows,
            'total_submissions'=> $count,
            'avg_points'       => $count > 0 ? round($totalPoints  / $count, 2) : 0,
            'avg_targets'      => $count > 0 ? round($totalTargets / $count, 2) : 0,
            'reviewed_count'   => $reviewedCount,
            'pending_review'   => $count - $reviewedCount,
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  6. EVALUATION SUMMARY (all evaluations)
     * ═══════════════════════════════════════════════════════════ */

    public function get_evaluation_summary(int $user_id): array
    {
        $rows = $this->db
            ->select('
                e.id, e.review_type, e.review_period, e.review_date,
                e.att_pct, e.score_attendance, e.score_targets,
                e.score_ratings, e.overall_verdict, e.status,
                e.approved_at, e.created_at,
                et.name   AS template_name,
                rv.firstname AS reviewer_firstname,
                rv.lastname  AS reviewer_lastname
            ')
            ->from('evaluations e')
            ->join('eval_templates et', 'et.id = e.template_id',   'left')
            ->join('users rv',          'rv.id = e.reviewer_id',   'left')
            ->where('e.user_id', $user_id)
            ->order_by('e.review_date', 'DESC')
            ->get()
            ->result_array();

        foreach ($rows as &$r) {
            $r['reviewer_name'] = trim(
                ($r['reviewer_firstname'] ?? '') . ' ' . ($r['reviewer_lastname'] ?? '')
            );
        }
        unset($r);

        $approved = array_filter($rows, fn($r) => $r['status'] === 'approved');
        $ratings  = array_column(array_values($approved), 'score_ratings');
        $ratings  = array_filter($ratings, fn($v) => $v !== null && $v !== '');

        return [
            'list'         => $rows,
            'total'        => count($rows),
            'approved'     => count($approved),
            'avg_rating'   => count($ratings) > 0
                ? round(array_sum($ratings) / count($ratings), 2)
                : null,
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  7. LATEST EVALUATION — with section scores and responses
     * ═══════════════════════════════════════════════════════════ */

    public function get_latest_evaluation_detail(int $user_id): ?array
    {
        $eval = $this->db
            ->select('
                e.*,
                et.name AS template_name,
                rv.firstname AS reviewer_firstname,
                rv.lastname  AS reviewer_lastname
            ')
            ->from('evaluations e')
            ->join('eval_templates et', 'et.id = e.template_id', 'left')
            ->join('users rv',          'rv.id = e.reviewer_id', 'left')
            ->where('e.user_id', $user_id)
            ->order_by('e.review_date', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$eval) { return null; }

        $eval['reviewer_name'] = trim(
            ($eval['reviewer_firstname'] ?? '') . ' ' . ($eval['reviewer_lastname'] ?? '')
        );

        // Sections with their criteria responses
        $sections = $this->db
            ->select('es.id, es.section_key, es.section_label, es.sort_order')
            ->from('eval_sections es')
            ->where('es.template_id', $eval['template_id'])
            ->where('es.is_active', 1)
            ->order_by('es.sort_order', 'ASC')
            ->get()
            ->result_array();

        foreach ($sections as &$sec) {
            $sec['criteria'] = $this->db
                ->select('
                    ec.id, ec.label, ec.criteria_type, ec.sort_order,
                    er.score, er.pass_fail, er.target_day, er.target_month,
                    er.actual_month, er.ach_pct, er.target_pass_fail,
                    er.selected_option, er.comments
                ')
                ->from('eval_criteria ec')
                ->join('eval_responses er',
                    'er.criteria_id = ec.id AND er.evaluation_id = ' . (int)$eval['id'],
                    'left')
                ->where('ec.section_id', $sec['id'])
                ->where('ec.is_active', 1)
                ->order_by('ec.sort_order', 'ASC')
                ->get()
                ->result_array();
        }
        unset($sec);

        $eval['sections'] = $sections;

        // Goals
        $eval['goals'] = $this->db
            ->select('goal, training_need, sort_order')
            ->from('eval_goals')
            ->where('evaluation_id', $eval['id'])
            ->order_by('sort_order', 'ASC')
            ->get()
            ->result_array();

        return $eval;
    }

    /* ═══════════════════════════════════════════════════════════
     *  8. TASK SUMMARY
     * ═══════════════════════════════════════════════════════════ */

    public function get_task_summary(
        int    $user_id,
        string $from,
        string $to
    ): array {
        $rows = $this->db
            ->select('
                id, name, priority, status, startdate, duedate,
                datefinished, dateadded, recurring
            ')
            ->from('tasks')
            ->where('assignee_id', $user_id)
            ->where('dateadded >=', $from . ' 00:00:00')
            ->where('dateadded <=', $to   . ' 23:59:59')
            ->order_by('duedate', 'DESC')
            ->get()
            ->result_array();

        $byStatus = [];
        $overdue  = 0;
        $today    = date('Y-m-d');

        foreach ($rows as $r) {
            $s = $r['status'] ?? 'not_started';
            $byStatus[$s] = ($byStatus[$s] ?? 0) + 1;
            if ($r['duedate'] && $r['duedate'] < $today
                && !in_array($s, ['completed', 'cancelled'])) {
                $overdue++;
            }
        }

        return [
            'list'        => $rows,
            'total'       => count($rows),
            'by_status'   => $byStatus,
            'overdue'     => $overdue,
            'completed'   => $byStatus['completed'] ?? 0,
            'completion_pct' => count($rows) > 0
                ? round((($byStatus['completed'] ?? 0) / count($rows)) * 100, 1)
                : null,
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  9. EMPLOYEE MOVEMENTS (promotions, transfers, etc.)
     * ═══════════════════════════════════════════════════════════ */

    public function get_movement_history(int $user_id): array
    {
        $rows = $this->db
            ->select('
                em.id, em.movement_type, em.effective_date, em.reason,
                em.remarks, em.created_at,
                ft.title  AS from_title,
                tt.title  AS to_title,
                fd.name   AS from_department,
                td.name   AS to_department,
                fteam.name AS from_team,
                tteam.name AS to_team,
                cb.firstname AS created_by_firstname,
                cb.lastname  AS created_by_lastname
            ')
            ->from('employee_movements em')
            ->join('hrm_positions ft',  'ft.id = em.from_title_id',      'left')
            ->join('hrm_positions tt',  'tt.id = em.to_title_id',        'left')
            ->join('departments fd',    'fd.id = em.from_department_id', 'left')
            ->join('departments td',    'td.id = em.to_department_id',   'left')
            ->join('teams fteam',       'fteam.id = em.from_team_id',    'left')
            ->join('teams tteam',       'tteam.id = em.to_team_id',      'left')
            ->join('users cb',          'cb.id = em.created_by',         'left')
            ->where('em.user_id', $user_id)
            ->order_by('em.effective_date', 'DESC')
            ->get()
            ->result_array();

        foreach ($rows as &$r) {
            $r['created_by_name'] = trim(
                ($r['created_by_firstname'] ?? '') . ' ' . ($r['created_by_lastname'] ?? '')
            );
        }
        unset($r);

        return $rows;
    }

    /* ═══════════════════════════════════════════════════════════
     *  10. INCREMENT HISTORY
     * ═══════════════════════════════════════════════════════════ */

    public function get_increment_history(int $user_id): array
    {
        return $this->db
            ->select('
                id, increment_date, increment_type, increment_value,
                previous_salary, raised_amount, new_salary,
                increment_cycle, remarks, status, approved_at, created_at
            ')
            ->from('payroll_increments')
            ->where('user_id', $user_id)
            ->order_by('increment_date', 'DESC')
            ->get()
            ->result_array();
    }

    /* ═══════════════════════════════════════════════════════════
     *  11. ACTIVE CONTRACT
     * ═══════════════════════════════════════════════════════════ */

    public function get_active_contract(int $user_id): ?array
    {
        return $this->db
            ->select('
                id, contract_type, version, start_date, end_date,
                notice_period_days, is_renewable, status,
                sent_at, signed_at, expired_at, internal_notes
            ')
            ->from('staff_contracts')
            ->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get()
            ->row_array() ?: null;
    }

    /* ═══════════════════════════════════════════════════════════
     *  12. ASSIGNED ASSETS
     * ═══════════════════════════════════════════════════════════ */

    public function get_assigned_assets(int $user_id): array
    {
        return $this->db
            ->select('
                a.id, a.name, a.serial_no, a.status, a.purchase_date,
                a.guarantee_date, a.price, a.description,
                at.name AS asset_type
            ')
            ->from('assets a')
            ->join('asset_types at', 'at.id = a.type_id', 'left')
            ->where('a.employee_id', $user_id)
            ->order_by('a.name', 'ASC')
            ->get()
            ->result_array();
    }

    /* ═══════════════════════════════════════════════════════════
     *  13. LOAN SUMMARY
     * ═══════════════════════════════════════════════════════════ */

    public function get_loan_summary(int $user_id): array
    {
        $rows = $this->db
            ->select('
                id, loan_taken, payback_type, total_installments,
                monthly_installment, current_installment, total_paid,
                balance, start_date, end_date, status, notes, created_at
            ')
            ->from('payroll_loans')
            ->where('user_id', $user_id)
            ->order_by('created_at', 'DESC')
            ->get()
            ->result_array();

        $totalBalance = array_sum(array_column($rows, 'balance'));
        $active = array_filter($rows, fn($r) => $r['status'] === 'active');

        return [
            'list'          => $rows,
            'total_balance' => (float)$totalBalance,
            'active_count'  => count($active),
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  14. ADVANCE SUMMARY
     * ═══════════════════════════════════════════════════════════ */

    public function get_advance_summary(int $user_id): array
    {
        $rows = $this->db
            ->select('id, amount, paid, balance, status, requested_at, approved_at, notes')
            ->from('payroll_advances')
            ->where('user_id', $user_id)
            ->order_by('requested_at', 'DESC')
            ->get()
            ->result_array();

        return [
            'list'          => $rows,
            'total_balance' => array_sum(array_column($rows, 'balance')),
            'pending'       => count(array_filter($rows, fn($r) => $r['status'] === 'requested')),
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  15. RECENT PAYROLL (last N payslips)
     * ═══════════════════════════════════════════════════════════ */

    public function get_recent_payroll(int $user_id, int $limit = 3): array
    {
        return $this->db
            ->select('
                id, payslip_number, pay_period, period_start, period_end,
                pay_date, basic_salary, allowances_total, deductions_total,
                overtime_amount, bonus_amount, gross_pay, net_pay,
                status, status_run, is_locked, created_at
            ')
            ->from('payroll_details')
            ->where('user_id', $user_id)
            ->where("status_run IN ('Posted','Paid')", null, false)
            ->order_by('period_end', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /* ═══════════════════════════════════════════════════════════
     *  16. DOCUMENTS
     * ═══════════════════════════════════════════════════════════ */

    public function get_document_list(int $user_id): array
    {
        return $this->db
            ->select('id, title, doc_type, file_path, expiry_date, description, created_at')
            ->from('hrm_documents')
            ->where('user_id', $user_id)
            ->where("doc_scope = 'employee'", null, false)
            ->order_by('created_at', 'DESC')
            ->get()
            ->result_array();
    }

    /* ═══════════════════════════════════════════════════════════
     *  17. EXIT INFO (if applicable)
     * ═══════════════════════════════════════════════════════════ */

    public function get_exit_info(int $user_id): ?array
    {
        return $this->db
            ->select('
                id, exit_type, exit_date, last_working_date, exit_status,
                reason, remarks, notice_period_served, exit_interview_date,
                checklist_completed, assets_returned, final_settlement_amount,
                final_settlement_date, created_at
            ')
            ->from('hrm_employee_exits')
            ->where('user_id', $user_id)
            ->limit(1)
            ->get()
            ->row_array() ?: null;
    }

    /* ═══════════════════════════════════════════════════════════
     *  18. SUPPORT TICKETS (assigned to user)
     * ═══════════════════════════════════════════════════════════ */

    public function get_ticket_summary(
        int    $user_id,
        string $from,
        string $to
    ): array {
        $rows = $this->db
            ->select('
                st.id, st.code, st.subject, st.status, st.priority,
                st.created_at, st.resolved_at,
                d.name AS department_name
            ')
            ->from('support_tickets st')
            ->join('departments d', 'd.id = st.department_id', 'left')
            ->where('st.assignee_id', $user_id)
            ->where('st.created_at >=', $from . ' 00:00:00')
            ->where('st.created_at <=', $to   . ' 23:59:59')
            ->order_by('st.created_at', 'DESC')
            ->get()
            ->result_array();

        $byStatus = [];
        foreach ($rows as $r) {
            $s = $r['status'] ?? 'open';
            $byStatus[$s] = ($byStatus[$s] ?? 0) + 1;
        }

        return [
            'list'      => $rows,
            'total'     => count($rows),
            'by_status' => $byStatus,
            'resolved'  => $byStatus['resolved'] ?? 0 + ($byStatus['closed'] ?? 0),
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  19. REQUESTS submitted by user
     * ═══════════════════════════════════════════════════════════ */

    public function get_request_summary(
        int    $user_id,
        string $from,
        string $to
    ): array {
        $rows = $this->db
            ->select('id, request_no, type, status, priority, submitted_at, approved_at, completed_at')
            ->from('requests')
            ->where('requested_by', $user_id)
            ->where('submitted_at >=', $from . ' 00:00:00')
            ->where('submitted_at <=', $to   . ' 23:59:59')
            ->order_by('submitted_at', 'DESC')
            ->get()
            ->result_array();

        $byStatus = [];
        foreach ($rows as $r) {
            $s = $r['status'] ?? 'pending';
            $byStatus[$s] = ($byStatus[$s] ?? 0) + 1;
        }

        return [
            'list'      => $rows,
            'total'     => count($rows),
            'by_status' => $byStatus,
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  UTILITY — quick KPI strip for dashboard widgets
     *  Returns a flat array of the most important headline numbers
     * ═══════════════════════════════════════════════════════════ */

    /**
     * Returns a lean set of KPIs suitable for a summary card / widget.
     * Much cheaper than get_full_progress().
     */
    public function get_kpi_strip(int $user_id, string $from = '', string $to = ''): array
    {
        if ($to   === '') { $to   = date('Y-m-d'); }
        if ($from === '') { $from = date('Y-m-d', strtotime('-30 days')); }

        // Attendance %
        $attRow = $this->db->query("
            SELECT
                SUM(status = 'P') AS present,
                SUM(status = 'A') AS absent
            FROM attendance
            WHERE user_id = ?
              AND attendance_date BETWEEN ? AND ?
        ", [$user_id, $from, $to])->row_array();
        $present = (int)($attRow['present'] ?? 0);
        $absent  = (int)($attRow['absent']  ?? 0);
        $attPct  = ($present + $absent) > 0
            ? round($present / ($present + $absent) * 100, 1) : null;

        // Tasks
        $taskRow = $this->db->query("
            SELECT
                COUNT(*)                              AS total,
                SUM(status = 'completed')             AS completed,
                SUM(status NOT IN ('completed','cancelled') AND duedate < CURDATE()) AS overdue
            FROM tasks
            WHERE assignee_id = ?
              AND dateadded BETWEEN ? AND ?
        ", [$user_id, $from . ' 00:00:00', $to . ' 23:59:59'])->row_array();

        // Latest evaluation rating
        $evalRow = $this->db
            ->select('score_ratings, review_date, status')
            ->from('evaluations')
            ->where('user_id', $user_id)
            ->where('status', 'approved')
            ->order_by('review_date', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        // Leave days taken
        $leaveRow = $this->db->query("
            SELECT COALESCE(SUM(total_days), 0) AS days
            FROM att_leaves
            WHERE user_id = ?
              AND status   = 'approved'
              AND start_date BETWEEN ? AND ?
              AND deleted_at IS NULL
        ", [$user_id, $from, $to])->row_array();

        // Active loans balance
        $loanRow = $this->db->query("
            SELECT COALESCE(SUM(balance), 0) AS total
            FROM payroll_loans
            WHERE user_id = ? AND status = 'active'
        ", [$user_id])->row_array();

        // Signoff avg points
        $signoffRow = $this->db->query("
            SELECT ROUND(AVG(total_points), 2) AS avg_pts,
                   COUNT(*) AS count
            FROM signoff_submissions
            WHERE user_id = ?
              AND submission_date BETWEEN ? AND ?
        ", [$user_id, $from, $to])->row_array();

        return [
            'attendance_pct'     => $attPct,
            'present_days'       => $present,
            'absent_days'        => $absent,
            'tasks_total'        => (int)($taskRow['total']     ?? 0),
            'tasks_completed'    => (int)($taskRow['completed'] ?? 0),
            'tasks_overdue'      => (int)($taskRow['overdue']   ?? 0),
            'latest_eval_rating' => isset($evalRow['score_ratings']) ? (float)$evalRow['score_ratings'] : null,
            'latest_eval_date'   => $evalRow['review_date'] ?? null,
            'leave_days_taken'   => (float)($leaveRow['days'] ?? 0),
            'loan_balance'       => (float)($loanRow['total'] ?? 0),
            'signoff_avg_pts'    => isset($signoffRow['avg_pts']) ? (float)$signoffRow['avg_pts'] : null,
            'signoff_count'      => (int)($signoffRow['count'] ?? 0),
            'period_start'       => $from,
            'period_end'         => $to,
        ];
    }
}