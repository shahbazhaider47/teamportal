<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Signoff_calendar_model
 *
 * Resolves which days in a date range are genuine "required signoff days"
 * for a specific user, by layering:
 *
 *   1. User work-shift off-days  (work_shifts.off_days)
 *   2. Company-wide working days (company_settings.att_working_days)
 *   3. Public holidays           (public_holidays — filtered by user context)
 *   4. Approved leaves           (att_leaves — only approved, not deleted)
 *
 * Usage:
 *   $this->load->model('signoff/Signoff_calendar_model');
 *   $result = $this->Signoff_calendar_model->get_working_day_stats(
 *       $user_id, $from_date, $to_date, $submitted_dates_map
 *   );
 */
class Signoff_calendar_model extends CI_Model
{
    // ── Day-name to ISO weekday number (1=Mon … 7=Sun) ─────────────────────
    private const DAY_MAP = [
        'mon' => 1, 'monday'    => 1,
        'tue' => 2, 'tuesday'   => 2,
        'wed' => 3, 'wednesday' => 3,
        'thu' => 4, 'thursday'  => 4,
        'fri' => 5, 'friday'    => 5,
        'sat' => 6, 'saturday'  => 6,
        'sun' => 7, 'sunday'    => 7,
    ];

    // ────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Main entry point.
     *
     * Returns an array with per-day breakdown and summary counters.
     *
     * @param  int    $user_id
     * @param  string $from        Y-m-d  (inclusive)
     * @param  string $to          Y-m-d  (inclusive, capped at today internally)
     * @param  array  $submitted   date => status  e.g. ['2025-04-01' => 'approved']
     * @param  string $timezone    PHP timezone identifier, e.g. 'Asia/Karachi'. Empty = server default.
     * @return array {
     *   days[]          => per-day detail rows
     *   working_days    => int   (required signoff days in range)
     *   submitted       => int   (approved or pending, excused)
     *   missed          => int
     *   excused         => int   (leave-excused OR signoff status 'excused')
     *   on_leave        => int   (approved leave days that overlap)
     *   holidays        => int   (public holiday days that overlap)
     *   pending         => int   (status = 'submitted', awaiting review)
     *   compliance_rate => float (0–100)
     *   month_label     => string
     * }
     */
    public function get_working_day_stats(
        int    $user_id,
        string $from,
        string $to,
        array  $submitted = [],
        string $timezone  = ''
    ): array {
        $tz    = new DateTimeZone($timezone !== '' ? $timezone : date_default_timezone_get());
        $today = (new DateTime('now', $tz))->format('Y-m-d');

        // ── Fetch context for the FULL month range (not capped at today) ──────
        // Holidays and leaves are fetched for the full $from→$to window so that
        // upcoming public holidays and pre-approved leave are visible in the calendar.
        $offDays       = $this->get_user_off_days($user_id);
        $companyOff    = $this->get_company_off_days();
        $holidays      = $this->get_holidays_for_user($user_id, $from, $to);
        $leaveDates    = $this->get_approved_leave_dates($user_id, $from, $to);
        $allOffDayNums = array_unique(array_merge($offDays, $companyOff));

        // ── Counters split into past (≤ today) and future (> today) ──────────
        // working_days_total  = all working days in the full month
        // working_days_past   = working days up to and including today
        //                       (used for compliance rate and missed count)
        // submitted/missed/excused/pending only count PAST days
        $days              = [];
        $workingDaysTotal  = 0;   // full-month working day count (for the card)
        $workingDaysPast   = 0;   // past working days only (for compliance denominator)
        $cntSubmitted      = 0;
        $cntMissed         = 0;
        $cntExcused        = 0;
        $cntOnLeave        = 0;
        $cntHolidaysTotal  = 0;
        $cntPending        = 0;
        $cntUpcoming       = 0;   // future working days (no action yet required)

        $cursor = new DateTime($from, $tz);
        $endDt  = new DateTime($to,   $tz);

        while ($cursor <= $endDt) {
            $date      = $cursor->format('Y-m-d');
            $dowNum    = (int)$cursor->format('N'); // 1=Mon … 7=Sun
            $isFuture  = ($date > $today);
            $isToday   = ($date === $today);

            $isOffDay  = in_array($dowNum, $allOffDayNums, true);
            $isHoliday = isset($holidays[$date]);
            $isOnLeave = isset($leaveDates[$date]);
            $signoffSt = $submitted[$date] ?? null;

            // ── Classify ──────────────────────────────────────────────────────
            if ($isOffDay || $isHoliday) {
                $dayType = $isHoliday ? 'holiday' : 'off_day';
                if ($isHoliday) { $cntHolidaysTotal++; }

            } elseif ($isFuture && !$isToday) {
                // Future working day — show as 'upcoming', never as 'missed'
                $dayType = $isOnLeave ? 'on_leave' : 'upcoming';
                $workingDaysTotal++;
                $cntUpcoming++;

            } elseif ($isOnLeave) {
                // Working day (past or today) with approved leave → excused
                $dayType = 'on_leave';
                $workingDaysTotal++;
                $workingDaysPast++;
                $cntOnLeave++;
                $cntExcused++;

            } else {
                // Regular working day, past or today
                $workingDaysTotal++;
                $workingDaysPast++;

                if ($signoffSt === null) {
                    $dayType = 'missed';
                    $cntMissed++;
                } elseif ($signoffSt === 'excused') {
                    $dayType = 'excused';
                    $cntExcused++;
                } elseif ($signoffSt === 'rejected') {
                    $dayType = 'missed';
                    $cntMissed++;
                } elseif ($signoffSt === 'submitted') {
                    $dayType = 'pending';
                    $cntSubmitted++;
                    $cntPending++;
                } else {
                    $dayType = 'submitted'; // approved
                    $cntSubmitted++;
                }
            }

            $days[] = [
                'date'       => $date,
                'dow'        => $cursor->format('D'),
                'dow_num'    => $dowNum,
                'is_future'  => $isFuture,
                'is_today'   => $isToday,
                'type'       => $dayType,
                'status'     => $signoffSt,
                'is_holiday' => $isHoliday,
                'holiday'    => $holidays[$date]   ?? null,
                'is_leave'   => $isOnLeave,
                'leave'      => $leaveDates[$date] ?? null,
            ];

            $cursor->modify('+1 day');
        }

        // Compliance = past (submitted + excused) / past working days
        // Future days do NOT affect the rate yet.
        $complianceRate = $workingDaysPast > 0
            ? round(($cntSubmitted + $cntExcused) / $workingDaysPast * 100, 1)
            : 0.0;

        return [
            'days'               => $days,
            // Card stats — working_days shows the FULL month total
            'working_days'       => $workingDaysTotal,
            'working_days_past'  => $workingDaysPast,
            'upcoming'           => $cntUpcoming,
            'submitted'          => $cntSubmitted,
            'missed'             => $cntMissed,
            'excused'            => $cntExcused,
            'on_leave'           => $cntOnLeave,
            'holidays'           => $cntHolidaysTotal,
            'pending'            => $cntPending,
            'compliance_rate'    => $complianceRate,
            'month_label'        => (new DateTime($from, $tz))->format('F Y'),
            'range'              => ['from' => $from, 'to' => $to],
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // 1) WORK SHIFT — user's off days
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Returns an array of ISO weekday numbers (1=Mon…7=Sun) that are
     * off-days for the user's assigned work shift.
     *
     * Reads  work_shifts.off_days  which stores a CSV like "sat,sun" or "fri,sat".
     * Falls back to [6,7] (Sat+Sun) when no shift is configured.
     */
    public function get_user_off_days(int $user_id): array
    {
        // Get the user's shift ID
        $row = $this->db
            ->select('work_shift')
            ->from('users')
            ->where('id', $user_id)
            ->get()->row_array();

        $shiftId = (int)($row['work_shift'] ?? 0);

        if ($shiftId > 0) {
            $shift = $this->db
                ->select('off_days, shift_type')
                ->from('work_shifts')
                ->where('id', $shiftId)
                ->where('is_active', 1)
                ->get()->row_array();

            if ($shift) {
                // 'off_day' type shift means the whole shift is a rest shift
                if (($shift['shift_type'] ?? '') === 'off_day') {
                    return [1, 2, 3, 4, 5, 6, 7]; // every day off
                }

                $offDaysCsv = trim((string)($shift['off_days'] ?? ''));
                if ($offDaysCsv !== '') {
                    return $this->parse_off_days_csv($offDaysCsv);
                }
            }
        }

        // Fallback: try company_settings
        return $this->get_company_off_days();
    }

    // ────────────────────────────────────────────────────────────────────────
    // 2) COMPANY SETTINGS — att_working_days
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Reads company_settings.att_working_days and returns the OFF-day weekday
     * numbers (complement of working days).
     *
     * The value can be:
     *   CSV of working day names  e.g. "mon,tue,wed,thu,fri"
     *   JSON array                e.g. ["mon","tue","wed","thu","fri"]
     *   Number                    e.g. "5" meaning 5-day work week Mon–Fri
     *
     * Returns array of ISO off-day numbers. Default: [6, 7] (Sat+Sun).
     */
    public function get_company_off_days(): array
    {
        $row = $this->db
            ->select('value')
            ->from('company_settings')
            ->where('key', 'att_working_days')
            ->limit(1)
            ->get()->row_array();

        if (!$row || $row['value'] === null || $row['value'] === '') {
            return [6, 7]; // default Sat+Sun off
        }

        $raw = trim((string)$row['value']);

        // JSON array?
        if ($raw[0] === '[' || $raw[0] === '{') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->working_names_to_off_days($decoded);
            }
        }

        // Pure integer (number of working days per week starting Monday)?
        if (is_numeric($raw)) {
            $n = (int)$raw;
            // e.g. 5 means Mon–Fri work, Sat+Sun off
            $workingNums = range(1, min($n, 7));
            return array_values(array_diff(range(1, 7), $workingNums));
        }

        // CSV of day names
        $parts = array_map('trim', explode(',', $raw));
        return $this->working_names_to_off_days($parts);
    }

    // ────────────────────────────────────────────────────────────────────────
    // 3) PUBLIC HOLIDAYS — filtered for this user
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Returns a map of  date => holiday_name  for all public holidays that
     * apply to the given user within the date range.
     *
     * A holiday applies to a user when any of the following are true:
     *   - holidays.locations  is NULL/empty  OR  contains user's office_id
     *   - holidays.departments is NULL/empty OR  contains user's emp_department
     *   - holidays.positions  is NULL/empty  OR  contains user's emp_title
     *   - holidays.employees  is NULL/empty  OR  contains the user's id
     *   (all four conditions are OR'd — if ANY scope is global, it applies)
     *
     * Soft-deleted holidays (deleted_at IS NOT NULL) are excluded.
     */
    public function get_holidays_for_user(int $user_id, string $from, string $to): array
    {
        // Load user context
        $user = $this->db
            ->select('office_id, emp_department, emp_title')
            ->from('users')
            ->where('id', $user_id)
            ->get()->row_array();

        $officeId   = (int)($user['office_id']       ?? 0);
        $deptId     = (int)($user['emp_department']   ?? 0);
        $posId      = (int)($user['emp_title']        ?? 0);

        // Fetch all holidays overlapping the range (not deleted)
        $rows = $this->db
            ->select('name, from_date, to_date, locations, departments, positions, employees')
            ->from('public_holidays')
            ->where('from_date <=', $to)
            ->where('to_date >=',   $from)
            ->where('deleted_at IS NULL', null, false)
            ->get()->result_array();

        $result = [];

        foreach ($rows as $h) {
            if (!$this->holiday_applies_to_user($h, $user_id, $officeId, $deptId, $posId)) {
                continue;
            }

            // Expand the holiday range into individual dates within our window
            $hFrom = max($h['from_date'], $from);
            $hTo   = min($h['to_date'],   $to);

            $cur = new DateTime($hFrom);
            $end = new DateTime($hTo);
            while ($cur <= $end) {
                $result[$cur->format('Y-m-d')] = (string)$h['name'];
                $cur->modify('+1 day');
            }
        }

        return $result;
    }

    // ────────────────────────────────────────────────────────────────────────
    // 4) APPROVED LEAVES — att_leaves
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Returns a map of  date => leave_type_name  for all approved, non-deleted
     * leaves that overlap the given range for the user.
     */
    public function get_approved_leave_dates(int $user_id, string $from, string $to): array
    {
        $rows = $this->db
            ->select('al.start_date, al.end_date, lt.name AS leave_name')
            ->from('att_leaves al')
            ->join('leave_types lt', 'lt.id = al.leave_type_id', 'left')
            ->where('al.user_id', $user_id)
            ->where('al.status', 'approved')
            ->where('al.start_date <=', $to)
            ->where('al.end_date >=',   $from)
            ->where('al.deleted_at IS NULL', null, false)
            ->where('lt.deleted_at IS NULL', null, false)
            ->get()->result_array();

        $result = [];

        foreach ($rows as $leave) {
            $lFrom = max($leave['start_date'], $from);
            $lTo   = min($leave['end_date'],   $to);
            $name  = (string)($leave['leave_name'] ?? 'Leave');

            $cur = new DateTime($lFrom);
            $end = new DateTime($lTo);
            while ($cur <= $end) {
                $result[$cur->format('Y-m-d')] = $name;
                $cur->modify('+1 day');
            }
        }

        return $result;
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Parse a CSV like "sat,sun" or "fri,saturday" into ISO weekday numbers.
     */
    private function parse_off_days_csv(string $csv): array
    {
        $parts = array_map('trim', explode(',', strtolower($csv)));
        $nums  = [];
        foreach ($parts as $p) {
            if ($p !== '' && isset(self::DAY_MAP[$p])) {
                $nums[] = self::DAY_MAP[$p];
            }
        }
        return array_unique($nums) ?: [6, 7];
    }

    /**
     * Given an array of working day names, return the complementary off-day numbers.
     * e.g. ['mon','tue','wed','thu','fri'] → [6, 7]
     */
    private function working_names_to_off_days(array $names): array
    {
        $workingNums = [];
        foreach ($names as $n) {
            $key = strtolower(trim((string)$n));
            if (isset(self::DAY_MAP[$key])) {
                $workingNums[] = self::DAY_MAP[$key];
            }
        }

        if (empty($workingNums)) {
            return [6, 7]; // safe default
        }

        return array_values(array_diff(range(1, 7), array_unique($workingNums)));
    }

    /**
     * Check whether a holiday row applies to the given user.
     *
     * Scoping logic:
     *   A scope column (locations / departments / positions / employees) is
     *   "global" when it is NULL or an empty JSON array [].
     *   The holiday applies if ALL non-empty scopes include the user's value.
     *   (i.e. if locations lists specific offices, the user must be in one of them)
     */
    private function holiday_applies_to_user(
        array $h,
        int $userId,
        int $officeId,
        int $deptId,
        int $posId
    ): bool {
        $checks = [
            ['col' => 'locations',   'val' => $officeId],
            ['col' => 'departments', 'val' => $deptId],
            ['col' => 'positions',   'val' => $posId],
            ['col' => 'employees',   'val' => $userId],
        ];

        foreach ($checks as $check) {
            $raw = trim((string)($h[$check['col']] ?? ''));
            if ($raw === '' || $raw === '[]' || $raw === 'null') {
                continue; // global scope — applies to everyone
            }
            $ids = json_decode($raw, true);
            if (!is_array($ids) || empty($ids)) {
                continue; // treat as global
            }
            // If scope is specified but user's value is 0 or not in list — skip holiday
            if ($check['val'] <= 0 || !in_array($check['val'], array_map('intval', $ids), true)) {
                return false;
            }
        }

        return true;
    }
}