<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Team_progress_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_team_progress(
        int    $team_id,
        string $period_start = '',
        string $period_end   = ''
    ): array {
        if ($period_end   === '') { $period_end   = date('Y-m-d'); }
        if ($period_start === '') { $period_start = date('Y-m-01'); }

        $members = $this->get_member_snapshots($team_id, $period_start, $period_end);

        return [
            'team'          => $this->get_team_info($team_id),
            'members'       => $members,
            'attendance'    => $this->get_team_attendance($team_id, $period_start, $period_end),
            'leaves'        => $this->get_team_leaves($team_id, $period_start, $period_end),
            'tasks'         => $this->get_team_tasks($team_id, $period_start, $period_end),
            'evaluations'   => $this->get_team_evaluations($team_id, $period_start, $period_end),
            'signoffs'      => $this->get_team_signoffs($team_id, $period_start, $period_end),
            'top_performer' => $this->_top_performer_from_members($members),
            'score'         => $this->_team_score_from_members($members),
            'period'        => ['start' => $period_start, 'end' => $period_end],
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  1. TEAM INFO
     *  Tables: teams, departments, users (x2), company_offices
     * ═══════════════════════════════════════════════════════════ */

    public function get_team_info(int $team_id): ?array
    {
        return $this->db
            ->select('
                t.id,
                t.name          AS team_name,
                d.name          AS department_name,
                tl.id           AS lead_id,
                tl.firstname    AS lead_firstname,
                tl.lastname     AS lead_lastname,
                tl.emp_id       AS lead_emp_id,
                tl.profile_image AS lead_avatar,
                tl.user_role    AS lead_role,
                mg.id           AS manager_id,
                mg.firstname    AS manager_firstname,
                mg.lastname     AS manager_lastname,
                mg.emp_id       AS manager_emp_id,
                mg.profile_image AS manager_avatar,
                o.office_name
            ')
            ->from('teams t')
            ->join('departments d',     'd.id = t.department_id', 'left')
            ->join('users tl',          'tl.id = t.teamlead_id',  'left')
            ->join('users mg',          'mg.id = t.manager_id',   'left')
            ->join('company_offices o', 'o.id = tl.office_id',    'left')
            ->where('t.id', $team_id)
            ->get()
            ->row_array() ?: null;
    }

    /* ═══════════════════════════════════════════════════════════
     *  2. MEMBER SNAPSHOTS — per-member KPI
     *  Tables: users, hrm_positions, attendance_logs,
     *          att_leaves, tasks, evaluations, signoff_submissions
     * ═══════════════════════════════════════════════════════════ */

    public function get_member_snapshots(
        int    $team_id,
        string $from,
        string $to
    ): array {
        $members = $this->db
            ->select('
                u.id, u.emp_id, u.firstname, u.lastname,
                u.profile_image, u.user_role, u.emp_joining,
                u.is_active, u.emp_title,
                p.title AS position_title
            ')
            ->from('users u')
            ->join('hrm_positions p', 'p.id = u.emp_title', 'left')
            ->where('u.emp_team', $team_id)
            ->where('u.is_active', 1)
            ->order_by('u.firstname', 'ASC')
            ->get()
            ->result_array();

        if (empty($members)) return [];

        $ids    = array_column($members, 'id');
        $inList = implode(',', array_map('intval', $ids));

        // ── Attendance: days present via attendance_logs ──────────
        // attendance_logs: user_id, datetime (DATETIME), status, deleted_at
        $attMap = [];
        $attRows = $this->db->query("
            SELECT user_id,
                   COUNT(DISTINCT DATE(datetime)) AS days_present
            FROM   attendance_logs
            WHERE  user_id IN ({$inList})
              AND  DATE(datetime) BETWEEN ? AND ?
              AND  deleted_at IS NULL
            GROUP  BY user_id
        ", [$from, $to])->result_array();
        foreach ($attRows as $r) {
            $attMap[(int)$r['user_id']] = (int)$r['days_present'];
        }

        // ── Leaves: approved days ─────────────────────────────────
        // att_leaves: user_id, start_date, end_date, total_days, status, deleted_at
        $leaveMap = [];
        $leaveRows = $this->db->query("
            SELECT user_id, SUM(total_days) AS leave_days
            FROM   att_leaves
            WHERE  user_id IN ({$inList})
              AND  status     = 'approved'
              AND  start_date <= ?
              AND  end_date   >= ?
              AND  deleted_at IS NULL
            GROUP  BY user_id
        ", [$to, $from])->result_array();
        foreach ($leaveRows as $r) {
            $leaveMap[(int)$r['user_id']] = (float)$r['leave_days'];
        }

        // ── Tasks: total and completed ────────────────────────────
        // tasks: assignee_id, dateadded (DATETIME), status enum, NO deleted_at
        // completed = status='completed'
        $taskMap = [];
        $taskRows = $this->db->query("
            SELECT assignee_id AS user_id,
                   COUNT(*)    AS total_tasks,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
            FROM   tasks
            WHERE  assignee_id IN ({$inList})
              AND  dateadded BETWEEN ? AND ?
            GROUP  BY assignee_id
        ", [$from . ' 00:00:00', $to . ' 23:59:59'])->result_array();
        foreach ($taskRows as $r) {
            $taskMap[(int)$r['user_id']] = $r;
        }

        // ── Evaluations: latest score per user ────────────────────
        // evaluations: user_id, score_ratings, overall_verdict, review_date, status
        $evalMap = [];
        if (!empty($ids)) {
            $evalRows = $this->db->query("
                SELECT e.user_id, e.score_ratings, e.overall_verdict, e.review_date
                FROM   evaluations e
                INNER JOIN (
                    SELECT user_id, MAX(review_date) AS max_date
                    FROM   evaluations
                    WHERE  user_id IN ({$inList})
                      AND  status IN ('submitted','approved')
                    GROUP  BY user_id
                ) latest
                  ON  latest.user_id  = e.user_id
                  AND latest.max_date = e.review_date
                WHERE e.status IN ('submitted','approved')
            ")->result_array();
            foreach ($evalRows as $r) {
                $evalMap[(int)$r['user_id']] = $r;
            }
        }

        // ── Signoffs: total and approved ─────────────────────────
        // signoff_submissions: user_id, submission_date (DATE), status, created_at
        $signoffMap = [];
        $signoffRows = $this->db->query("
            SELECT user_id,
                   COUNT(*) AS total_signoffs,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_signoffs
            FROM   signoff_submissions
            WHERE  user_id IN ({$inList})
              AND  submission_date BETWEEN ? AND ?
            GROUP  BY user_id
        ", [$from, $to])->result_array();
        foreach ($signoffRows as $r) {
            $signoffMap[(int)$r['user_id']] = $r;
        }

        $workingDays = $this->_count_business_days($from, $to);

        foreach ($members as &$m) {
            $uid = (int)$m['id'];

            $daysPresent = $attMap[$uid]             ?? 0;
            $leaveDays   = $leaveMap[$uid]           ?? 0.0;
            $tk          = $taskMap[$uid]            ?? [];
            $ev          = $evalMap[$uid]            ?? [];
            $sf          = $signoffMap[$uid]         ?? [];

            $attPct = $workingDays > 0
                ? round(($daysPresent + $leaveDays) / $workingDays * 100, 1)
                : 0.0;

            $totalTasks     = (int)($tk['total_tasks']     ?? 0);
            $completedTasks = (int)($tk['completed_tasks'] ?? 0);
            $taskPct        = $totalTasks > 0
                ? round($completedTasks / $totalTasks * 100, 1)
                : null;

            $totalSf    = (int)($sf['total_signoffs']    ?? 0);
            $approvedSf = (int)($sf['approved_signoffs'] ?? 0);
            $sfPct      = $totalSf > 0
                ? round($approvedSf / $totalSf * 100, 1)
                : null;

            $evalScore   = isset($ev['score_ratings'])   ? (float)$ev['score_ratings']  : null;
            $evalVerdict = $ev['overall_verdict']        ?? null;
            $evalDate    = $ev['review_date']            ?? null;

            $m['kpi'] = [
                'working_days'      => $workingDays,
                'days_present'      => $daysPresent,
                'leave_days'        => $leaveDays,
                'att_pct'           => $attPct,
                'total_tasks'       => $totalTasks,
                'completed_tasks'   => $completedTasks,
                'task_pct'          => $taskPct,
                'total_signoffs'    => $totalSf,
                'approved_signoffs' => $approvedSf,
                'signoff_pct'       => $sfPct,
                'eval_score'        => $evalScore,
                'eval_verdict'      => $evalVerdict,
                'eval_date'         => $evalDate,
                'composite_score'   => $this->_compute_member_score(
                    $attPct, $taskPct, $sfPct, $evalScore
                ),
            ];
        }
        unset($m);

        usort($members, function($a, $b) {
            return ($b['kpi']['composite_score'] ?? 0) <=> ($a['kpi']['composite_score'] ?? 0);
        });

        return $members;
    }

    /* ═══════════════════════════════════════════════════════════
     *  3. TEAM ATTENDANCE SUMMARY
     *  Table: attendance_logs — datetime, status, deleted_at
     * ═══════════════════════════════════════════════════════════ */

    public function get_team_attendance(int $team_id, string $from, string $to): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(DISTINCT al.user_id)                                     AS members_logged,
                COUNT(DISTINCT DATE(al.datetime))                              AS active_days,
                SUM(CASE WHEN al.status = 'check_in'  THEN 1 ELSE 0 END)      AS total_checkins,
                SUM(CASE WHEN al.status = 'check_out' THEN 1 ELSE 0 END)      AS total_checkouts,
                COUNT(al.id)                                                   AS total_logs
            FROM attendance_logs al
            JOIN users u ON u.id = al.user_id
            WHERE u.emp_team   = ?
              AND u.is_active  = 1
              AND DATE(al.datetime) BETWEEN ? AND ?
              AND al.deleted_at IS NULL
        ", [$team_id, $from, $to])->row_array();

        return $row ?: [
            'members_logged'  => 0,
            'active_days'     => 0,
            'total_checkins'  => 0,
            'total_checkouts' => 0,
            'total_logs'      => 0,
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  4. TEAM LEAVE SUMMARY
     *  Table: att_leaves — start_date, end_date, total_days, status, deleted_at
     *         leave_types — name
     * ═══════════════════════════════════════════════════════════ */

    public function get_team_leaves(int $team_id, string $from, string $to): array
    {
        $rows = $this->db->query("
            SELECT
                lt.name     AS leave_type,
                al.status,
                COUNT(*)           AS total_count,
                SUM(al.total_days) AS total_days
            FROM att_leaves al
            JOIN users      u  ON u.id  = al.user_id
            JOIN leave_types lt ON lt.id = al.leave_type_id
            WHERE u.emp_team   = ?
              AND u.is_active  = 1
              AND al.start_date <= ?
              AND al.end_date   >= ?
              AND al.deleted_at IS NULL
            GROUP BY lt.name, al.status
            ORDER BY lt.name, al.status
        ", [$team_id, $to, $from])->result_array();

        $summary = [
            'total_requests' => 0,
            'approved_days'  => 0.0,
            'pending'        => 0,
            'by_type'        => [],
        ];

        foreach ($rows as $r) {
            $summary['total_requests'] += (int)$r['total_count'];
            if ($r['status'] === 'approved') {
                $summary['approved_days'] += (float)$r['total_days'];
            }
            if ($r['status'] === 'pending') {
                $summary['pending'] += (int)$r['total_count'];
            }
            $summary['by_type'][] = $r;
        }

        return $summary;
    }

    /* ═══════════════════════════════════════════════════════════
     *  5. TEAM TASK SUMMARY
     *  Table: tasks
     *    dateadded DATETIME — used for period filter
     *    duedate   DATE
     *    status enum: not_started|in_progress|review|completed|on_hold|cancelled
     *    NO deleted_at column
     * ═══════════════════════════════════════════════════════════ */

    public function get_team_tasks(int $team_id, string $from, string $to): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*)  AS total,
                SUM(CASE WHEN t.status = 'completed'   THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN t.status = 'review'      THEN 1 ELSE 0 END) AS in_review,
                SUM(CASE WHEN t.status = 'not_started' THEN 1 ELSE 0 END) AS not_started,
                SUM(CASE WHEN t.status = 'on_hold'     THEN 1 ELSE 0 END) AS on_hold,
                SUM(CASE WHEN t.status = 'cancelled'   THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE
                    WHEN t.duedate < CURDATE()
                     AND t.status NOT IN ('completed','cancelled')
                    THEN 1 ELSE 0
                END) AS past_due
            FROM tasks t
            JOIN users u ON u.id = t.assignee_id
            WHERE u.emp_team  = ?
              AND u.is_active = 1
              AND t.dateadded BETWEEN ? AND ?
        ", [$team_id, $from . ' 00:00:00', $to . ' 23:59:59'])->row_array();

        $total     = (int)($row['total']     ?? 0);
        $completed = (int)($row['completed'] ?? 0);

        return array_merge($row ?? [], [
            'total'          => $total,
            'completed'      => $completed,
            'completion_pct' => $total > 0 ? round($completed / $total * 100, 1) : 0.0,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  6. TEAM EVALUATIONS SUMMARY
     *  Table: evaluations — team_id, user_id, score_ratings,
     *                        overall_verdict, review_date, status
     * ═══════════════════════════════════════════════════════════ */

    public function get_team_evaluations(int $team_id, string $from, string $to): array
    {
        $rows = $this->db->query("
            SELECT
                e.overall_verdict,
                e.score_ratings,
                e.status,
                e.review_date,
                u.firstname,
                u.lastname,
                u.emp_id,
                u.profile_image
            FROM evaluations e
            JOIN users u ON u.id = e.user_id
            WHERE e.team_id    = ?
              AND e.review_date BETWEEN ? AND ?
              AND e.status IN ('submitted','approved')
            ORDER BY e.score_ratings DESC
        ", [$team_id, $from, $to])->result_array();

        $totalScore = 0.0;
        $scored     = 0;
        $verdicts   = [];

        foreach ($rows as $r) {
            if ($r['score_ratings'] !== null) {
                $totalScore += (float)$r['score_ratings'];
                $scored++;
            }
            $v = $r['overall_verdict'] ?? 'unknown';
            $verdicts[$v] = ($verdicts[$v] ?? 0) + 1;
        }

        return [
            'total'     => count($rows),
            'avg_score' => $scored > 0 ? round($totalScore / $scored, 2) : null,
            'verdicts'  => $verdicts,
            'rows'      => $rows,
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  7. TEAM SIGNOFF SUMMARY
     *  Table: signoff_submissions
     *    user_id, team_id, submission_date DATE, status, created_at
     *  Filter by submission_date (DATE column — no time cast needed)
     * ═══════════════════════════════════════════════════════════ */

    public function get_team_signoffs(int $team_id, string $from, string $to): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN ss.status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN ss.status = 'pending'  THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN ss.status = 'rejected' THEN 1 ELSE 0 END) AS rejected
            FROM signoff_submissions ss
            JOIN users u ON u.id = ss.user_id
            WHERE u.emp_team        = ?
              AND u.is_active       = 1
              AND ss.submission_date BETWEEN ? AND ?
        ", [$team_id, $from, $to])->row_array();

        $total    = (int)($row['total']    ?? 0);
        $approved = (int)($row['approved'] ?? 0);

        return array_merge($row ?? [], [
            'total'          => $total,
            'compliance_pct' => $total > 0 ? round($approved / $total * 100, 1) : 0.0,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  8. ALL TEAMS RANKING
     * ═══════════════════════════════════════════════════════════ */

    public function get_all_teams_ranking(string $from, string $to): array
    {
        $teams = $this->db
            ->select('id, name')
            ->from('teams')
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();

        $ranked = [];
        foreach ($teams as $t) {
            $tid     = (int)$t['id'];
            $info    = $this->get_team_info($tid);
            $members = $this->get_member_snapshots($tid, $from, $to);
            $score   = $this->_team_score_from_members($members);

            if ($score['member_count'] === 0) continue;

            $ranked[] = [
                'team_id'      => $tid,
                'team_name'    => $t['name'],
                'dept'         => $info['department_name'] ?? '—',
                'lead_name'    => trim(($info['lead_firstname'] ?? '') . ' ' . ($info['lead_lastname'] ?? '')),
                'lead_avatar'  => $info['lead_avatar']    ?? '',
                'score'        => $score['score'],
                'grade'        => $score['grade'],
                'member_count' => $score['member_count'],
            ];
        }

        usort($ranked, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $ranked;
    }

    /* ═══════════════════════════════════════════════════════════
     *  9. TEAM OF THE MONTH
     * ═══════════════════════════════════════════════════════════ */

    public function get_team_of_month(int $year, int $month): ?array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $all  = $this->get_all_teams_ranking($from, $to);
        return !empty($all) ? $all[0] : null;
    }

    /* ═══════════════════════════════════════════════════════════
     *  10. TEAM OF THE YEAR
     * ═══════════════════════════════════════════════════════════ */

    public function get_team_of_year(int $year): ?array
    {
        $from = "{$year}-01-01";
        $to   = "{$year}-12-31";
        $all  = $this->get_all_teams_ranking($from, $to);
        return !empty($all) ? $all[0] : null;
    }

    /* ═══════════════════════════════════════════════════════════
     *  11. TOP 3 TEAMS
     * ═══════════════════════════════════════════════════════════ */

    public function get_top_teams(string $from, string $to, int $limit = 3): array
    {
        return array_slice($this->get_all_teams_ranking($from, $to), 0, $limit);
    }

    /* ═══════════════════════════════════════════════════════════
     *  PRIVATE HELPERS
     * ═══════════════════════════════════════════════════════════ */

    /**
     * Extract top performer from already-fetched member snapshots.
     * Avoids a second DB round-trip.
     */
    private function _top_performer_from_members(array $members): ?array
    {
        return !empty($members) ? $members[0] : null;
    }

    /**
     * Compute team-level score + grade from already-fetched member snapshots.
     * Avoids a second DB round-trip.
     */
    private function _team_score_from_members(array $members): array
    {
        if (empty($members)) {
            return ['score' => 0.0, 'grade' => 'N/A', 'member_count' => 0];
        }

        $scores = array_filter(
            array_column(array_column($members, 'kpi'), 'composite_score'),
            function($s) { return $s !== null; }
        );

        $avg = count($scores) > 0
            ? round(array_sum($scores) / count($scores), 1)
            : 0.0;

        return [
            'score'        => $avg,
            'grade'        => $this->_score_to_grade($avg),
            'member_count' => count($members),
        ];
    }

    /**
     * Count business days (Mon–Fri) inclusive between two date strings.
     */
    private function _count_business_days(string $from, string $to): int
    {
        $start = strtotime($from);
        $end   = strtotime($to);
        if (!$start || !$end || $end < $start) return 0;

        $count = 0;
        $cur   = $start;
        while ($cur <= $end) {
            if ((int)date('w', $cur) !== 0 && (int)date('w', $cur) !== 6) {
                $count++;
            }
            $cur = strtotime('+1 day', $cur);
        }
        return $count;
    }

    /**
     * Weighted composite score 0–100.
     * Weights: Attendance 30% | Tasks 30% | Signoffs 20% | Eval Rating 20%
     * Re-normalises automatically when components are missing.
     */
    private function _compute_member_score(
        ?float $attPct,
        ?float $taskPct,
        ?float $sfPct,
        ?float $evalScore
    ): ?float {
        $parts   = [];
        $weights = [];

        if ($attPct !== null) {
            $parts[]   = min(100.0, $attPct)  * 0.30;
            $weights[] = 0.30;
        }
        if ($taskPct !== null) {
            $parts[]   = min(100.0, $taskPct) * 0.30;
            $weights[] = 0.30;
        }
        if ($sfPct !== null) {
            $parts[]   = min(100.0, $sfPct)   * 0.20;
            $weights[] = 0.20;
        }
        if ($evalScore !== null) {
            // score_ratings is 1–5, convert to 0–100
            $parts[]   = (min(5.0, $evalScore) / 5.0) * 100.0 * 0.20;
            $weights[] = 0.20;
        }

        if (empty($parts)) return null;

        $totalWeight = array_sum($weights);
        return round(array_sum($parts) / $totalWeight, 1);
    }

    /**
     * Convert numeric score to letter grade.
     */
    private function _score_to_grade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B+';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        return 'D';
    }
}