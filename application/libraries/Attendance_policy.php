<?php defined('BASEPATH') or exit('No direct script access allowed');

class Attendance_policy
{
    protected $CI;
    protected array $user = [];
    protected array $shift = [];
    protected array $office = [];
    protected array $department = [];
    protected array $position = [];
    protected array $company_settings = [];
    protected array $system_settings = [];
    protected bool $isSuperAdmin = false;

    public function __construct(array $params = [])
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        if (empty($params['user_id'])) {
            throw new Exception('Attendance_policy_new requires user_id');
        }
        $this->bootstrapContext((int)$params['user_id']);
        $this->loadSettings();
    }

    
    protected function bootstrapContext(int $userId): void
    {
        $this->user = $this->CI->db
            ->where('id', $userId)
            ->where('is_active', 1)
            ->get('users')
            ->row_array();
    
        if (!$this->user) {
            throw new Exception('User not found or inactive');
        }
    
        // Superadmin bypass flag — set early so all downstream checks can short-circuit
        $this->isSuperAdmin = strtolower(trim((string)($this->user['user_role'] ?? ''))) === 'superadmin';

        $this->shift = [];
        if (!empty($this->user['work_shift'])) {
            $this->shift = $this->CI->db
                ->select('
                    id, name, code, shift_type, shift_start_time, shift_end_time,
                    break_start_time, break_end_time, break_minutes,
                    grace_minutes, monthly_late_minutes,
                    overtime_after_minutes, max_overtime_minutes, overtime_type,
                    weekly_hours, monthly_hours, min_time_between_punches,
                    off_days, is_night_shift, is_active
                ')
                ->where('id', (int)$this->user['work_shift'])
                ->where('is_active', 1)
                ->get('work_shifts')
                ->row_array() ?? [];
        }

        $this->office = [];
        if (!empty($this->user['office_id'])) {
            $this->office = $this->CI->db
                ->where('id', (int)$this->user['office_id'])
                ->where('is_active', 1)
                ->get('company_offices')
                ->row_array() ?? [];
        }

        $this->department = [];
        if (!empty($this->user['emp_department'])) {
            $this->department = $this->CI->db
                ->where('id', (int)$this->user['emp_department'])
                ->get('departments')
                ->row_array() ?? [];
        }

        $this->position = [];
        if (!empty($this->user['emp_title'])) {
            $this->position = $this->CI->db
                ->where('id', (int)$this->user['emp_title'])
                ->where('status', 1)
                ->get('hrm_positions')
                ->row_array() ?? [];
        }
    }

    protected function loadSettings(): void
    {
        $this->company_settings = [];
        $this->system_settings  = [];

        $settings = $this->CI->db->get('company_settings')->result_array();
        foreach ($settings as $setting) {
            if (!isset($setting['key'])) continue;
            $this->company_settings[$setting['key']] = $setting['value'] ?? null;
        }

        $sys_settings = $this->CI->db->get('system_settings')->result_array();
        foreach ($sys_settings as $setting) {
            if (!isset($setting['key'])) continue;
            $this->system_settings[$setting['key']] = $setting['value'] ?? null;
        }
    }

    public function user(): array
    {
        return $this->user;
    }

    public function shift(): array
    {
        return $this->shift;
    }

    public function office(): array
    {
        return $this->office;
    }

    public function department(): array
    {
        return $this->department;
    }

    public function position(): array
    {
        return $this->position;
    }

    public function companySettings(): array
    {
        return $this->company_settings;
    }

    public function systemSettings(): array
    {
        return $this->system_settings;
    }

    public function companySetting(string $key, $default = null)
    {
        return array_key_exists($key, $this->company_settings)
            ? $this->company_settings[$key]
            : $default;
    }

    public function systemSetting(string $key, $default = null)
    {
        return array_key_exists($key, $this->system_settings)
            ? $this->system_settings[$key]
            : $default;
    }

    public function hasShift(): bool
    {
        return !empty($this->shift) && !empty($this->shift['id']);
    }

    public function hasOffice(): bool
    {
        return !empty($this->office) && !empty($this->office['id']);
    }

    public function hasDepartment(): bool
    {
        return !empty($this->department) && !empty($this->department['id']);
    }

    public function hasPosition(): bool
    {
        return !empty($this->position) && !empty($this->position['id']);
    }

    public function exportContext(): array
    {
        return [
            'user'             => $this->user,
            'shift'            => $this->shift,
            'office'           => $this->office,
            'department'       => $this->department,
            'position'         => $this->position,
            'company_settings' => $this->company_settings,
            'system_settings'  => $this->system_settings,
        ];
    }

    public function lateMinutesForLog(array $logRow): int
    {
        if (empty($logRow['status']) || $logRow['status'] !== 'check_in') {
            return 0;
        }
    
        if (empty($logRow['datetime'])) {
            return 0;
        }
    
        if (empty($this->shift) || empty($this->shift['shift_start_time'])) {
            return 0;
        }
    
        $shiftStart = trim((string)$this->shift['shift_start_time']); // e.g. 09:00:00 or 09:00
        if ($shiftStart === '') {
            return 0;
        }
    
        $logDate = date('Y-m-d', strtotime($logRow['datetime']));
    
        if (strlen($shiftStart) === 5) {
            $shiftStart .= ':00';
        }
    
        $shiftStartDT = strtotime($logDate . ' ' . $shiftStart);
        $checkInDT    = strtotime($logRow['datetime']);
    
        if (!$shiftStartDT || !$checkInDT) {
            return 0;
        }
    
        $graceMinutes = (int)($this->shift['grace_minutes'] ?? 0);
        $graceSeconds = $graceMinutes * 60;
        
        // Within grace period = not late
        if ($checkInDT <= ($shiftStartDT + $graceSeconds)) {
            return 0;
        }
        
        $diffSeconds = $checkInDT - $shiftStartDT;
        $lateMinutes = (int)floor($diffSeconds / 60);
        
        // Subtract grace so late minutes start counting after grace ends
        return max(0, $lateMinutes - $graceMinutes);
    }
    
    public function earlyCheckoutForLog(array $logRow, int $windowMinutes = 30): int
    {
        if (empty($logRow['status']) || $logRow['status'] !== 'check_out') {
            return 0;
        }
    
        if (empty($logRow['datetime'])) {
            return 0;
        }
    
        if (empty($this->shift) || empty($this->shift['shift_end_time'])) {
            return 0;
        }
    
        $shiftEnd = trim((string)$this->shift['shift_end_time']); // e.g. 17:00:00 or 17:00
        if ($shiftEnd === '') {
            return 0;
        }
    
        if (strlen($shiftEnd) === 5) {
            $shiftEnd .= ':00';
        }
    
        $logDate = date('Y-m-d', strtotime($logRow['datetime']));
    
        $shiftEndDT = strtotime($logDate . ' ' . $shiftEnd);
        $checkOutDT = strtotime($logRow['datetime']);
    
        if (!$shiftEndDT || !$checkOutDT) {
            return 0;
        }
    
        if ($checkOutDT >= $shiftEndDT) {
            return 0;
        }
    
        $diffSeconds  = $shiftEndDT - $checkOutDT;
        $earlyMinutes = (int)floor($diffSeconds / 60);
        
        // Only flag as early if outside the allowed grace window
        $windowMinutes = max(0, (int)$windowMinutes);
        if ($earlyMinutes <= $windowMinutes) {
            return 0;
        }
        
        return max(0, $earlyMinutes);
    }
    
    public function overtimeForLog(array $logRow): array
    {
        $status = $logRow['status'] ?? '';
    
        if (!in_array($status, ['check_out', 'overtime_out'], true)) {
            return ['minutes' => 0, 'is_exceeded' => false, 'max' => 0];
        }
    
        if (empty($logRow['datetime'])) {
            return ['minutes' => 0, 'is_exceeded' => false, 'max' => 0];
        }
    
        if (empty($this->shift) || empty($this->shift['shift_end_time'])) {
            return ['minutes' => 0, 'is_exceeded' => false, 'max' => 0];
        }
    
        $shiftEnd = trim((string)$this->shift['shift_end_time']);
        if ($shiftEnd === '') {
            return ['minutes' => 0, 'is_exceeded' => false, 'max' => 0];
        }
    
        if (strlen($shiftEnd) === 5) {
            $shiftEnd .= ':00';
        }
    
        $afterMinutes = (int)($this->shift['overtime_after_minutes'] ?? 0);
        $maxMinutes   = (int)($this->shift['max_overtime_minutes'] ?? 0);
    
        $logDate = date('Y-m-d', strtotime($logRow['datetime']));
    
        $shiftEndDT = strtotime($logDate . ' ' . $shiftEnd);
        $logDT      = strtotime($logRow['datetime']);
    
        if (!$shiftEndDT || !$logDT) {
            return ['minutes' => 0, 'is_exceeded' => false, 'max' => $maxMinutes];
        }
    
        $otThresholdDT = $shiftEndDT + ($afterMinutes * 60);
        
        // Must stay past the threshold before OT is counted at all
        if ($logDT <= $otThresholdDT) {
            return ['minutes' => 0, 'is_exceeded' => false, 'max' => $maxMinutes];
        }
        
        // OT = total time after shift end (not after threshold)
        $diffSeconds = $logDT - $shiftEndDT;
        $minutes     = (int)floor($diffSeconds / 60);
        $minutes = max(0, $minutes);
    
        $isExceeded = false;
        if ($maxMinutes > 0 && $minutes > $maxMinutes) {
            $isExceeded = true;
        }
    
        return [
            'minutes'     => $minutes,
            'is_exceeded' => $isExceeded,
            'max'         => $maxMinutes,
        ];
    }
    
    public function manualCellState(string $dateStr): array
    {
        $dateStr = preg_replace('/[^0-9\-]/', '', $dateStr);
    
        if ($dateStr === '' || strlen($dateStr) !== 10) {
            return [
                'is_locked'  => false,
                'reason'     => 'none',
                'display'    => '',
                'cell_class' => '',
                'box_class'  => '',
            ];
        }
    
        $joining = trim((string)($this->user['emp_joining'] ?? ''));
    
        if ($joining !== '' && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $joining)) {
            if ($dateStr < $joining) {
                return [
                    'is_locked'  => true,
                    'reason'     => 'before_joining',
                    'display'    => 'DOJ',
                    'cell_class' => 'locked-joining',
                    'box_class'  => 'attendance-locked-box',
                ];
            }
        }
    
        $offDaysRaw = (string)($this->shift['off_days'] ?? '');
        $offDaysRaw = strtolower(trim($offDaysRaw));
    
        if ($offDaysRaw !== '') {
    
            $offDays = [];
    
            if ($offDaysRaw[0] === '[') {
                $decoded = json_decode($offDaysRaw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $v) {
                        $v = strtolower(trim((string)$v));
                        if ($v !== '') $offDays[] = $v;
                    }
                }
            } else {
                $offDaysRaw = str_replace(['|', ';'], ',', $offDaysRaw);
                $parts = preg_split('/[\s,]+/', $offDaysRaw);
                foreach ($parts as $p) {
                    $p = strtolower(trim((string)$p));
                    if ($p !== '') $offDays[] = $p;
                }
            }
    
            $offDays = array_unique($offDays);
    
            $dow = strtolower(date('D', strtotime($dateStr)));
    
            if (in_array($dow, $offDays, true)) {
                return [
                    'is_locked'  => true,
                    'reason'     => 'off_day',
                    'display'    => 'OFF',
                    'cell_class' => 'locked-offday',
                    'box_class'  => 'attendance-locked-box',
                ];
            }
        }
    
        return [
            'is_locked'  => false,
            'reason'     => 'none',
            'display'    => '',
            'cell_class' => '',
            'box_class'  => '',
        ];
    }
    
    public function getPublicHolidaysForMonth(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));
    
        $rows = $this->CI->db
            ->where('deleted_at IS NULL', null, false)
            ->where('from_date <=', $end)
            ->where('to_date >=', $start)
            ->get('public_holidays')
            ->result_array();
    
        if (!$rows) return [];
    
        $userId   = (int)($this->user['id'] ?? 0);
        $locId    = (int)($this->user['office_id'] ?? 0);
        $deptId   = (int)($this->user['emp_department'] ?? 0);
        $posId    = (int)($this->user['emp_title'] ?? 0);
    
        $filtered = [];
    
        foreach ($rows as $h) {
    
            if (!$this->holidayAppliesToUser($h, $userId, $locId, $deptId, $posId)) {
                continue;
            }
    
            $filtered[] = $h;
        }
    
        return $filtered;
    }
    
    
    protected function holidayAppliesToUser(array $holiday, int $userId, int $locId, int $deptId, int $posId): bool
    {
        if (!$this->filterFieldMatches($holiday['locations'] ?? '', $locId)) {
            return false;
        }
    
        if (!$this->filterFieldMatches($holiday['departments'] ?? '', $deptId)) {
            return false;
        }
    
        if (!$this->filterFieldMatches($holiday['positions'] ?? '', $posId)) {
            return false;
        }
    
        if (!$this->filterFieldMatches($holiday['employees'] ?? '', $userId)) {
            return false;
        }
    
        return true;
    }
    
    protected function filterFieldMatches($raw, int $id): bool
    {
        $raw = trim((string)($raw ?? ''));
    
        if ($raw === '') {
            return true;
        }
    
        if ($id <= 0) {
            return false;
        }
    
        $list = [];
    
        if (strlen($raw) > 0 && $raw[0] === '[') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $v) {
                    $v = (int)$v;
                    if ($v > 0) $list[] = $v;
                }
            }
        } else {
            $raw = str_replace(['|', ';'], ',', $raw);
            $parts = preg_split('/[\s,]+/', $raw);
            foreach ($parts as $p) {
                $p = (int)trim((string)$p);
                if ($p > 0) $list[] = $p;
            }
        }
    
        $list = array_unique($list);
    
        return in_array($id, $list, true);
    }
    
    public function getMonthlyWorkingDaySummary(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));
    
        $totalDays = (int)date('t', strtotime($start));
    
        $joining = trim((string)($this->user['emp_joining'] ?? ''));
        $joining = preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $joining) ? $joining : '';
    
        $offDays = $this->getShiftOffDaysList();
    
        $holidays = $this->getPublicHolidaysForMonth($year, $month);
    
        $holidayDates = [];
        foreach ($holidays as $h) {
    
            $from = preg_replace('/[^0-9\-]/', '', (string)($h['from_date'] ?? ''));
            $to   = preg_replace('/[^0-9\-]/', '', (string)($h['to_date'] ?? ''));
    
            if ($from === '' || $to === '') continue;
    
            if ($from < $start) $from = $start;
            if ($to > $end)     $to   = $end;
    
            $cur = strtotime($from);
            $endTs = strtotime($to);
    
            while ($cur && $cur <= $endTs) {
                $holidayDates[] = date('Y-m-d', $cur);
                $cur = strtotime('+1 day', $cur);
            }
        }
    
        $holidayDates = array_values(array_unique($holidayDates));
        
        $offdayDates   = [];
        $eligibleDays  = 0;
        $offDaysCount  = 0;
        $holidayCount  = 0;
        $workingDays   = 0;
    
        for ($d = 1; $d <= $totalDays; $d++) {
    
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
    
            if ($joining !== '' && $dateStr < $joining) {
                continue;
            }
    
            $eligibleDays++;
    
            $dow = strtolower(date('D', strtotime($dateStr)));
    
            $isOffday = (!empty($offDays) && in_array($dow, $offDays, true));
            $isHoliday = in_array($dateStr, $holidayDates, true);
    
            if ($isOffday) {
                $offDaysCount++;
                $offdayDates[] = $dateStr;
                // If it's also a holiday on an off-day, don't double count
            } elseif ($isHoliday) {
                // Only count as holiday if it falls on a working day
                $holidayCount++;
            }
            
            if (!$isOffday && !$isHoliday) {
                $workingDays++;
            }
        }
    
        return [
            'total_days'     => $totalDays,
            'eligible_days'  => $eligibleDays,
            'off_days'       => $offDaysCount,
            'holiday_days'   => $holidayCount,
            'working_days'   => $workingDays,
            'holiday_dates'  => $holidayDates,
            'offday_dates'   => $offdayDates,
        ];
    }
    
    protected function getShiftOffDaysList(): array
    {
        $offDaysRaw = (string)($this->shift['off_days'] ?? '');
        $offDaysRaw = strtolower(trim($offDaysRaw));
    
        if ($offDaysRaw === '') return [];
    
        $offDays = [];
    
        if ($offDaysRaw[0] === '[') {
            $decoded = json_decode($offDaysRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $v) {
                    $v = strtolower(trim((string)$v));
                    if ($v !== '') $offDays[] = $v;
                }
            }
        } else {
            $offDaysRaw = str_replace(['|', ';'], ',', $offDaysRaw);
            $parts = preg_split('/[\s,]+/', $offDaysRaw);
            foreach ($parts as $p) {
                $p = strtolower(trim((string)$p));
                if ($p !== '') $offDays[] = $p;
            }
        }
    
        return array_values(array_unique($offDays));
    }


    // Policies for Leaves 
    /**
     * Fetch a leave type by id (active + not deleted)
     */
    public function getLeaveTypeById(int $leaveTypeId): array
    {
        if ($leaveTypeId <= 0) return [];
    
        return $this->CI->db
            ->from('leave_types')
            ->where('id', $leaveTypeId)
            ->where('deleted_at IS NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array() ?? [];
    }
    
    /**
     * SINGLE METHOD you will call from the modal (AJAX or server side)
     *
     * Returns:
     * [
     *   'ok' => bool,                     // can apply or not
     *   'blocked' => bool,                // hard restriction
     *   'errors' => string[],             // hard-block reasons
     *   'warnings' => string[],           // show but may still allow (your choice)
     *   'requires_attachment' => bool,    // enforce attachment field required
     *   'approver' => array|null,         // approver user row
     *   'meta' => array                   // computed details (limits/usage/window)
     * ]
     */
    public function evaluateLeaveApplication(
        int $leaveTypeId,
        string $fromDate,
        string $toDate,
        array $extra = []
    ): array {
        $fromDate = preg_replace('/[^0-9\-]/', '', $fromDate);
        $toDate   = preg_replace('/[^0-9\-]/', '', $toDate);
    
        $resp = [
            'ok'                   => true,
            'blocked'              => false,
            'errors'               => [],
            'warnings'             => [],
            'requires_attachment'  => false,
            'approver'             => null,
            'meta'                 => [],
        ];
    
        // ── Date sanity ──────────────────────────────────────────────────────────
        if (
            !preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $fromDate) ||
            !preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $toDate)
        ) {
            $resp['ok'] = $resp['blocked'] = false; // already false, just explicit
            $resp['blocked'] = true;
            $resp['errors'][] = 'Invalid date range.';
            return $resp;
        }
    
        if ($toDate < $fromDate) {
            $resp['ok']       = false;
            $resp['blocked']  = true;
            $resp['errors'][] = 'To date cannot be earlier than From date.';
            return $resp;
        }
    
        // ── SUPERADMIN: bypass all policy checks ─────────────────────────────────
        if ($this->isSuperAdmin) {
            $lt = $this->getLeaveTypeById($leaveTypeId);
            $resp['requires_attachment']       = $lt ? ((int)($lt['attachment_required'] ?? 0) === 1) : false;
            $resp['meta']['leave_type']        = $lt ?: [];
            $resp['meta']['superadmin_bypass'] = true;
            return $resp;
        }
    
        // ── Company settings ─────────────────────────────────────────────────────
        $allowApplyLeaves   = (int)$this->companySetting('att_allow_to_apply_leaves', 1);
        $allowMondayLeave   = (int)$this->companySetting('att_allow_monday_leave', 1);
        $allowFridayLeave   = (int)$this->companySetting('att_allow_friday_leave', 1);
        $allowBridgeHoliday = (int)$this->companySetting('att_allow_bridge_holiday_leave', 1);
        $sandwichEnabled    = (int)$this->companySetting('att_enable_sandwich_rule', 0);
        $sandwichDeductDays = (int)$this->companySetting('att_sandwich_deduction_days', 0);
        $maxConsecutiveDays = (int)$this->companySetting('att_max_consecutive_leave_days', 0);
        $approverId         = (int)$this->companySetting('att_leave_approver', 0);
    
        if ($allowApplyLeaves !== 1) {
            $resp['ok']       = false;
            $resp['blocked']  = true;
            $resp['errors'][] = 'Leave applications are currently disabled by company policy.';
        }
    
        // ── Leave type ───────────────────────────────────────────────────────────
        $lt = $this->getLeaveTypeById($leaveTypeId);
        if (!$lt) {
            $resp['ok']       = false;
            $resp['blocked']  = true;
            $resp['errors'][] = 'Selected leave type not found or inactive.';
            return $resp;
        }
    
        $resp['requires_attachment'] = ((int)($lt['attachment_required'] ?? 0) === 1);
    
        if (strtolower((string)($lt['type'] ?? 'Paid')) === 'unpaid') {
            $resp['warnings'][] = 'This is an unpaid leave. Salary deduction may apply.';
        }
    
        // ── Approver ─────────────────────────────────────────────────────────────
        if ($approverId > 0) {
            $resp['approver'] = $this->CI->db
                ->select('id, fullname, email, user_role, is_active')
                ->from('users')
                ->where('id', $approverId)
                ->where('is_active', 1)
                ->limit(1)
                ->get()
                ->row_array() ?: null;
    
            if (!$resp['approver']) {
                $resp['warnings'][] = 'Leave approver is not configured correctly.';
            }
        } else {
            $resp['warnings'][] = 'Leave approver is not configured (att_leave_approver).';
        }
    
        // ── Eligibility filters ──────────────────────────────────────────────────
        $userGender     = strtolower((string)($this->user['gender'] ?? ''));
        $userLocId      = (int)($this->user['office_id'] ?? 0);
        $userDeptId     = (int)($this->user['emp_department'] ?? 0);
        $userPosId      = (int)($this->user['emp_title'] ?? 0);
        $userId         = (int)($this->user['id'] ?? 0);
        $userRole       = strtolower((string)($this->user['user_role'] ?? ''));
        $employmentType = strtolower((string)($this->user['employment_type'] ?? ($this->user['emp_employment_type'] ?? '')));
    
        if (!$this->jsonStringListAllows($lt['applies_to_genders'] ?? null, $userGender)) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'You are not eligible for this leave type (gender restriction).';
        }
        if (!$this->jsonStringListAllows($lt['applies_to_roles'] ?? null, $userRole)) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'You are not eligible for this leave type (role restriction).';
        }
        if (!$this->jsonStringListAllows($lt['employment_types'] ?? null, $employmentType)) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'You are not eligible for this leave type (employment type restriction).';
        }
        if (!$this->jsonIdListAllows($lt['applies_to_locations'] ?? null, $userLocId)) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'You are not eligible for this leave type (location restriction).';
        }
        if (!$this->jsonIdListAllows($lt['applies_to_departments'] ?? null, $userDeptId)) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'You are not eligible for this leave type (department restriction).';
        }
        if (!$this->jsonIdListAllows($lt['applies_to_positions'] ?? null, $userPosId)) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'You are not eligible for this leave type (position restriction).';
        }
        if (!$this->jsonIdListAllows($lt['applies_to_employees'] ?? null, $userId)) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'You are not eligible for this leave type (employee restriction).';
        }
    
        if ($resp['blocked']) {
            $resp['meta']['leave_type'] = $lt;
            return $resp;
        }
    
        // ── Date-based company rules ─────────────────────────────────────────────
        $daysRequested = $this->countBusinessDaysInclusive($fromDate, $toDate);
    
        if ($maxConsecutiveDays > 0 && $daysRequested > $maxConsecutiveDays) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'Maximum consecutive leave days exceeded (Limit: ' . $maxConsecutiveDays . ').';
        }
    
        $fromDow = strtolower(date('D', strtotime($fromDate)));
        $toDow   = strtolower(date('D', strtotime($toDate)));
    
        if ($allowMondayLeave !== 1 && ($fromDow === 'mon' || $toDow === 'mon')) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'Monday leaves are not allowed by company policy.';
        }
        if ($allowFridayLeave !== 1 && ($fromDow === 'fri' || $toDow === 'fri')) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'Friday leaves are not allowed by company policy.';
        }
        if ($allowBridgeHoliday !== 1 && $this->isBridgeHolidayLeave($fromDate, $toDate)) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'Bridge holiday leave is not allowed.';
        }
    
        if ($sandwichEnabled === 1) {
            $resp['warnings'][] = 'Sandwich rule is enabled. Leave deduction may include weekends/off-days between.';
            $resp['meta']['sandwich_deduction_days_setting'] = $sandwichDeductDays;
        }
    
        // ── Limits ───────────────────────────────────────────────────────────────
        $annualAllowed  = $this->toFloatOrNull($lt['allowed_annually'] ?? null);
        $monthlyAllowed = $this->toFloatOrNull($lt['allowed_monthly'] ?? null);
        $basedOn        = strtolower((string)($lt['based_on'] ?? 'calendar days'));
    
        $annualWindow  = $this->getAnnualWindow($fromDate, $basedOn);
        $monthlyWindow = [
            'start' => date('Y-m-01', strtotime($fromDate)),
            'end'   => date('Y-m-t',  strtotime($fromDate)),
        ];
    
        $usage = $this->getLeaveUsageFromAttLeaves($userId, $leaveTypeId, $annualWindow, $monthlyWindow);
    
        $requestedQty = isset($extra['requested_qty'])
            ? (float)$extra['requested_qty']
            : (float)$daysRequested;
    
        if ($annualAllowed !== null && ($usage['annual_used'] + $requestedQty) > $annualAllowed) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'Annual limit exceeded. Allowed: ' . $annualAllowed . ', Used: ' . $usage['annual_used'] . '.';
        }
        if ($monthlyAllowed !== null && ($usage['monthly_used'] + $requestedQty) > $monthlyAllowed) {
            $resp['ok'] = false; $resp['blocked'] = true;
            $resp['errors'][] = 'Monthly limit exceeded. Allowed: ' . $monthlyAllowed . ', Used: ' . $usage['monthly_used'] . '.';
        }
    
        // ── Meta ─────────────────────────────────────────────────────────────────
        $resp['meta'] = array_merge($resp['meta'], [
            'leave_type'      => $lt,
            'requested_qty'   => $requestedQty,
            'requested_days'  => $daysRequested,
            'annual_allowed'  => $annualAllowed,
            'monthly_allowed' => $monthlyAllowed,
            'annual_used'     => $usage['annual_used'],
            'monthly_used'    => $usage['monthly_used'],
            'annual_window'   => $annualWindow,
            'monthly_window'  => $monthlyWindow,
        ]);
    
        if (!empty($resp['errors'])) {
            $resp['ok']      = false;
            $resp['blocked'] = true;
        }
    
        return $resp;
    }
/* =========================================================
 * Helpers used by evaluateLeaveApplication()
 * ========================================================= */

protected function countBusinessDaysInclusive(string $from, string $to): float
{
    $s = strtotime($from);
    $e = strtotime($to);
    if (!$s || !$e) return 0;

    if ($e < $s) return 0;

    $count = 0;
    $cur = $s;

    $offDays = $this->getShiftOffDaysList(); // e.g. ['fri','sat'] or ['sat','sun']
    
    while ($cur <= $e) {
        $dow = strtolower(date('D', $cur)); // 'mon','tue',...
        if (empty($offDays) || !in_array($dow, $offDays, true)) {
            $count++;
        }
        $cur = strtotime('+1 day', $cur);
    }

    return (float)$count;
}


protected function toFloatOrNull($val): ?float
{
    if ($val === null || $val === '') return null;
    if (!is_numeric($val)) return null;
    return (float)$val;
}

protected function countCalendarDaysInclusive(string $from, string $to): int
{
    $s = strtotime($from);
    $e = strtotime($to);
    if (!$s || !$e) return 0;
    $diff = (int)floor(($e - $s) / 86400);
    return max(1, $diff + 1);
}

/**
 * If JSON list is null/empty => allow all
 * If JSON has values => must match one of them
 */
protected function jsonStringListAllows($rawJson, string $needle): bool
{
    $needle = strtolower(trim($needle));

    // If leave type doesn't define restriction, allow all.
    if ($rawJson === null || trim((string)$rawJson) === '') return true;

    $arr = json_decode((string)$rawJson, true);
    if (!is_array($arr) || empty($arr)) return true;

    // If user value missing, deny when restricted
    if ($needle === '') return false;

    $arr = array_map(function ($v) {
        return strtolower(trim((string)$v));
    }, $arr);

    $arr = array_values(array_unique(array_filter($arr, 'strlen')));

    return in_array($needle, $arr, true);
}

/**
 * If JSON list is null/empty => allow all
 * If JSON has ids => must contain id
 */
protected function jsonIdListAllows($rawJson, int $id): bool
{
    // If leave type doesn't define restriction, allow all.
    if ($rawJson === null || trim((string)$rawJson) === '') return true;

    $arr = json_decode((string)$rawJson, true);
    if (!is_array($arr) || empty($arr)) return true;

    if ($id <= 0) return false;

    $ids = [];
    foreach ($arr as $v) {
        $v = (int)$v;
        if ($v > 0) $ids[] = $v;
    }

    $ids = array_values(array_unique($ids));
    return in_array($id, $ids, true);
}

/**
 * Bridge Holiday Rule:
 * Block leave that touches the day AFTER a holiday or day BEFORE a holiday.
 */
protected function isBridgeHolidayLeave(string $fromDate, string $toDate): bool
{
    // We need holidays for the span months (from month, to month)
    $fromY = (int)date('Y', strtotime($fromDate));
    $fromM = (int)date('n', strtotime($fromDate));
    $toY   = (int)date('Y', strtotime($toDate));
    $toM   = (int)date('n', strtotime($toDate));

    $holidayDates = [];

    $months = [[$fromY, $fromM]];
    if ($fromY !== $toY || $fromM !== $toM) {
        $months[] = [$toY, $toM];
    }

    foreach ($months as [$y, $m]) {
        $hs = $this->getPublicHolidaysForMonth($y, $m);
        foreach ($hs as $h) {
            $f = preg_replace('/[^0-9\-]/', '', (string)($h['from_date'] ?? ''));
            $t = preg_replace('/[^0-9\-]/', '', (string)($h['to_date'] ?? ''));
            if (!$f || !$t) continue;

            $cur = strtotime($f);
            $end = strtotime($t);
            while ($cur && $cur <= $end) {
                $holidayDates[date('Y-m-d', $cur)] = true;
                $cur = strtotime('+1 day', $cur);
            }
        }
    }

    if (empty($holidayDates)) return false;

    $dayAfterHoliday = date('Y-m-d', strtotime($fromDate . ' -1 day')); // if yesterday was holiday => leave starts after holiday
    $dayBeforeHoliday = date('Y-m-d', strtotime($toDate . ' +1 day'));  // if tomorrow is holiday => leave ends before holiday

    if (isset($holidayDates[$dayAfterHoliday])) return true;
    if (isset($holidayDates[$dayBeforeHoliday])) return true;

    return false;
}

/**
 * Annual window based on leave_types.based_on
 * - Calendar Days => Jan 1 to Dec 31 of the fromDate year
 * - Joining Date  => anniversary window based on user emp_joining
 */
protected function getAnnualWindow(string $fromDate, string $basedOnLower): array
{
    $y = (int)date('Y', strtotime($fromDate));

    if ($basedOnLower === 'joining date') {
        $joining = trim((string)($this->user['emp_joining'] ?? ''));
        if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $joining)) {

            $joinMd = date('m-d', strtotime($joining));
            $start = $y . '-' . $joinMd;

            // If the calculated start is after fromDate, go to previous year window
            if ($start > $fromDate) {
                $start = ($y - 1) . '-' . $joinMd;
            }

            $end = date('Y-m-d', strtotime($start . ' +1 year -1 day'));

            return ['start' => $start, 'end' => $end];
        }
        // fallback to calendar year
    }

    return [
        'start' => sprintf('%04d-01-01', $y),
        'end'   => sprintf('%04d-12-31', $y),
    ];
}

/**
 * Usage from att_leaves (annual + monthly)
 * NOTE: adjust column names if your schema differs.
 */
protected function getLeaveUsageFromAttLeaves(
    int $userId,
    int $leaveTypeId,
    array $annualWindow,
    array $monthlyWindow
): array
{
    $annualUsed  = 0.0;
    $monthlyUsed = 0.0;

    // Count approved + pending (so user cannot exceed limits by applying multiple pending)
    $allowedStatuses = ['approved', 'pending'];

    // ANNUAL USAGE (sum total_days)
    $r1 = $this->CI->db
        ->select('SUM(total_days) AS used', false)
        ->from('att_leaves')
        ->where('deleted_at IS NULL', null, false)
        ->where('user_id', $userId)
        ->where('leave_type_id', $leaveTypeId)
        ->where('start_date <=', $annualWindow['end'])
        ->where('end_date >=', $annualWindow['start'])
        ->where_in('status', $allowedStatuses)
        ->get()
        ->row_array();

    $annualUsed = (float)($r1['used'] ?? 0);

    // MONTHLY USAGE (sum total_days)
    $r2 = $this->CI->db
        ->select('SUM(total_days) AS used', false)
        ->from('att_leaves')
        ->where('deleted_at IS NULL', null, false)
        ->where('user_id', $userId)
        ->where('leave_type_id', $leaveTypeId)
        ->where('start_date <=', $monthlyWindow['end'])
        ->where('end_date >=', $monthlyWindow['start'])
        ->where_in('status', $allowedStatuses)
        ->get()
        ->row_array();

    $monthlyUsed = (float)($r2['used'] ?? 0);

    return [
        'annual_used'  => round($annualUsed, 2),
        'monthly_used' => round($monthlyUsed, 2),
    ];
}




    /* =========================================================
     * Attendance Manual Edit Permission (Grid Editing)
     * ========================================================= */

    /**
     * Resolve timezone for this user (office > system setting > fallback)
     */
    protected function resolveTimezone(): DateTimeZone
    {
        $tz = '';

        // office timezone (recommended)
        if (!empty($this->office['timezone'])) {
            $tz = (string)$this->office['timezone'];
        }

        // fallback to system settings key (adjust key if your system uses a different one)
        if ($tz === '') {
            $tz = (string)$this->systemSetting('timezone', '');
        }

        // safe fallback
        if ($tz === '') {
            $tz = 'Asia/Karachi';
        }

        try {
            return new DateTimeZone($tz);
        } catch (Exception $e) {
            return new DateTimeZone('Asia/Karachi');
        }
    }

    /**
     * Get "now" in the user's timezone (office/system)
     */
    protected function nowTz(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->resolveTimezone());
    }

    /**
     * Build shift end datetime for a given date (handles night shifts)
     */
    protected function shiftEndDateTime(string $dateStr): ?DateTimeImmutable
    {
        $dateStr = preg_replace('/[^0-9\-]/', '', $dateStr);
        if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $dateStr)) {
            return null;
        }

        if (empty($this->shift) || empty($this->shift['shift_end_time'])) {
            return null;
        }

        $tz = $this->resolveTimezone();

        $start = trim((string)($this->shift['shift_start_time'] ?? ''));
        $end   = trim((string)$this->shift['shift_end_time']);

        if ($end === '') {
            return null;
        }

        // normalize HH:MM to HH:MM:SS
        if (strlen($start) === 5) $start .= ':00';
        if (strlen($end) === 5)   $end   .= ':00';

        // Night shift detection:
        // If end time is "earlier" than start time, end is next day.
        $endDate = $dateStr;

        if ($start !== '' && strcmp($end, $start) < 0) {
            $endDate = (new DateTimeImmutable($dateStr, $tz))->modify('+1 day')->format('Y-m-d');
        }

        try {
            return new DateTimeImmutable($endDate . ' ' . $end, $tz);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Can user edit manual attendance cell for a date?
     * Policy: ONLY allow editing for "today" until 1 hour after shift end.
     */
    public function canEditAttendance(string $dateStr): bool
    {
        $dateStr = preg_replace('/[^0-9\-]/', '', $dateStr);
        if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $dateStr)) {
            return false;
        }

        // Apply DOJ rule: before joining => never editable
        $joining = trim((string)($this->user['emp_joining'] ?? ''));
        if ($joining !== '' && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $joining)) {
            if ($dateStr < $joining) return false;
        }

        $now   = $this->nowTz();
        $today = $now->format('Y-m-d');

        // Only today is editable
        if ($dateStr !== $today) {
            return false;
        }

        // If shift end not configured, keep today editable (don’t break ops)
        $shiftEnd = $this->shiftEndDateTime($dateStr);
        if (!$shiftEnd) {
            return true;
        }

        // 1 hour grace after shift end
        $cutoff = $shiftEnd->modify('+1 hour');

        return $now <= $cutoff;
    }

    /**
     * Is date a public holiday for this user?
     */
    public function isPublicHoliday(string $dateStr): bool
    {
        $dateStr = preg_replace('/[^0-9\-]/', '', $dateStr);
        if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $dateStr)) {
            return false;
        }

        $y = (int)date('Y', strtotime($dateStr));
        $m = (int)date('n', strtotime($dateStr));

        $holidays = $this->getPublicHolidaysForMonth($y, $m);
        if (empty($holidays)) return false;

        foreach ($holidays as $h) {
            $from = preg_replace('/[^0-9\-]/', '', (string)($h['from_date'] ?? ''));
            $to   = preg_replace('/[^0-9\-]/', '', (string)($h['to_date'] ?? ''));
            if ($from === '' || $to === '') continue;

            if ($dateStr >= $from && $dateStr <= $to) {
                // getPublicHolidaysForMonth() is already user-filtered via holidayAppliesToUser()
                return true;
            }
        }

        return false;
    }

    /**
     * Is date a working day for this user?
     * Working day = not off-day, not holiday, not before joining.
     */
    public function isWorkingDay(string $dateStr): bool
    {
        $dateStr = preg_replace('/[^0-9\-]/', '', $dateStr);
        if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $dateStr)) {
            return false;
        }

        // DOJ lock
        $joining = trim((string)($this->user['emp_joining'] ?? ''));
        if ($joining !== '' && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $joining)) {
            if ($dateStr < $joining) return false;
        }

        // Off day?
        $offDays = $this->getShiftOffDaysList();
        $dow = strtolower(date('D', strtotime($dateStr)));
        if (!empty($offDays) && in_array($dow, $offDays, true)) {
            return false;
        }

        // Holiday?
        if ($this->isPublicHoliday($dateStr)) {
            return false;
        }

        return true;
    }
    
    // ADD public accessor
    public function isSuperAdmin(): bool
    {
        return $this->isSuperAdmin;
    }
}