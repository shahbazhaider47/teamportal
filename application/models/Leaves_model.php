<?php defined('BASEPATH') or exit('No direct script access allowed');

class Leaves_model extends CI_Model
{
    protected $table = 'att_leaves';
    protected $pk    = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    /* ============================================================
     | MAIN LISTING (FOR MANAGE LEAVES VIEW)
     * ============================================================ */

    public function get_all_leaves_with_meta(): array
    {
        return $this->db
            ->select("
                l.*,
                u.emp_id,
                COALESCE(u.fullname, CONCAT(u.firstname,' ',u.lastname)) AS emp_name,
                u.emp_department,
                d.name AS department_name,
                lt.name AS leave_type_name
            ")
            ->from("{$this->table} l")
            ->join("users u", "u.id = l.user_id", "left")
            ->join("departments d", "d.id = u.emp_department", "left")
            ->join("leave_types lt", "lt.id = l.leave_type_id", "left")
            ->where("l.deleted_at IS NULL", null, false)
            ->order_by("l.created_at", "DESC")
            ->get()
            ->result_array();
    }

    public function get_user_leaves_with_meta(int $user_id): array
    {
        if ($user_id <= 0) return [];

        return $this->db
            ->select("l.*, lt.name AS leave_type_name")
            ->from("{$this->table} l")
            ->join("leave_types lt", "lt.id = l.leave_type_id", "left")
            ->where("l.user_id", $user_id)
            ->where("l.deleted_at IS NULL", null, false)
            ->order_by("l.start_date", "DESC")
            ->get()
            ->result_array();
    }

    /* ============================================================
     | DROPDOWN DATA (FOR MODALS)
     * ============================================================ */

    public function get_leave_types(): array
    {
        // keep minimal fields for dropdown
        return $this->db
            ->select("id, name")
            ->from("leave_types")
            ->where("deleted_at IS NULL", null, false)
            ->order_by("name", "ASC")
            ->get()
            ->result_array();
    }

    public function get_departments(): array
    {
        return $this->db
            ->select("id, name")
            ->from("departments")
            ->order_by("name", "ASC")
            ->get()
            ->result_array();
    }

    public function get_active_users(): array
    {
        return $this->db
            ->select("id, emp_id, fullname, firstname, lastname")
            ->from("users")
            ->where("is_active", 1)
            ->order_by("fullname", "ASC")
            ->get()
            ->result_array();
    }

    /* ============================================================
     | BASIC CRUD
     * ============================================================ */

    public function get_leave_by_id(int $id): array
    {
        if ($id <= 0) return [];

        return $this->db
            ->where($this->pk, $id)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->table)
            ->row_array() ?? [];
    }

    public function insert_leave(array $data): int
    {
        if (
            empty($data['user_id']) ||
            empty($data['leave_type_id']) ||
            empty($data['start_date']) ||
            empty($data['end_date'])
        ) {
            return 0;
        }

        $data['status'] = strtolower(trim((string)($data['status'] ?? 'pending')));
        if (!in_array($data['status'], ['pending','approved','rejected','cancelled'], true)) {
            $data['status'] = 'pending';
        }

        // total_days auto calc
        if (!isset($data['total_days']) || (float)$data['total_days'] <= 0) {
            $data['total_days'] = $this->calculate_total_days(
                $data['start_date'],
                $data['end_date'],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null
            );
        }

        $data['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    public function update_leave(int $id, array $data): bool
    {
        if ($id <= 0) return false;

        $existing = $this->get_leave_by_id($id);
        if (!$existing) return false;

        // normalize status
        if (isset($data['status'])) {
            $data['status'] = strtolower(trim((string)$data['status']));
            if (!in_array($data['status'], ['pending','approved','rejected','cancelled'], true)) {
                unset($data['status']);
            }
        }

        // recalc total_days if date/time changes
        if (
            isset($data['start_date']) ||
            isset($data['end_date']) ||
            isset($data['start_time']) ||
            isset($data['end_time'])
        ) {
            $start_date = $data['start_date'] ?? $existing['start_date'];
            $end_date   = $data['end_date']   ?? $existing['end_date'];
            $start_time = $data['start_time'] ?? $existing['start_time'];
            $end_time   = $data['end_time']   ?? $existing['end_time'];

            $data['total_days'] = $this->calculate_total_days($start_date, $end_date, $start_time, $end_time);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where($this->pk, $id);
        $this->db->where('deleted_at IS NULL', null, false);

        return (bool)$this->db->update($this->table, $data);
    }

    public function delete_leave(int $id, int $deletedBy = 0): bool
    {
        if ($id <= 0) return false;

        $this->db->where($this->pk, $id);
        $this->db->where('deleted_at IS NULL', null, false);

        return (bool)$this->db->update($this->table, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => $deletedBy ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /* ============================================================
     | STATUS / APPROVAL
     * ============================================================ */

    public function update_leave_status(int $id, string $status, int $approver_id): bool
    {
        $status = strtolower(trim($status));
        if (!in_array($status, ['pending','approved','rejected','cancelled'], true)) {
            return false;
        }

        $update = [
            'status'      => $status,
            'approver_id' => $approver_id ?: null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if ($status === 'approved') {
            $update['approved_at'] = date('Y-m-d H:i:s');
        } else {
            $update['approved_at'] = null;
        }

        $this->db->where($this->pk, $id);
        $this->db->where('deleted_at IS NULL', null, false);

        return (bool)$this->db->update($this->table, $update);
    }

    /* ============================================================
     | STATS (FOR CARDS)
     * ============================================================ */

public function get_leave_stats_summary(): array
{
    // If you want per-user stats, you can filter here later.
    // For now: global summary.

    $rows = $this->db
        ->select('status, COUNT(*) AS total', false)
        ->from('att_leaves')
        ->where('deleted_at IS NULL', null, false)
        ->group_by('status')
        ->get()
        ->result_array();

    $map = [
        'pending'   => 0,
        'approved'  => 0,
        'rejected'  => 0,
        'cancelled' => 0,
    ];

    foreach ($rows as $r) {
        $st = strtolower((string)($r['status'] ?? ''));
        if (isset($map[$st])) {
            $map[$st] = (int)($r['total'] ?? 0);
        }
    }

    $map['total_applied'] = $map['pending'] + $map['approved'] + $map['rejected'] + $map['cancelled'];

    return $map;
}

    /* ============================================================
     | CALCULATIONS
     * ============================================================ */

    /**
     * Calculates leave days excluding weekends.
     * If is_half_day = true => returns 0.5 (no range logic).
     */
    public function calculate_leave_days(string $start_date, string $end_date, bool $is_half_day = false): float
    {
        if ($is_half_day) {
            return 0.5;
        }

        $start = new DateTime($start_date);
        $end   = new DateTime($end_date);

        // inclusive end
        $end->modify('+1 day');

        $days = 0;

        while ($start < $end) {

            // 6=Saturday, 7=Sunday
            $dayNum = (int)$start->format('N');

            if (!in_array($dayNum, [6, 7], true)) {
                $days++;
            }

            $start->modify('+1 day');
        }

        return (float)$days;
    }
    
    public function calculate_total_days(string $start_date, string $end_date, ?string $start_time, ?string $end_time): float
    {
        // Time-based leave (same day)
        if (!empty($start_time) && !empty($end_time) && $start_date === $end_date) {

            $st = strtotime($start_date . ' ' . $start_time);
            $et = strtotime($start_date . ' ' . $end_time);

            if ($st && $et && $et > $st) {
                $mins  = ($et - $st) / 60;
                $hours = $mins / 60;

                // 8 hours = 1 day
                return round(($hours / 8), 2);
            }
        }

        // Full-day range excluding weekends
        $start = new DateTime($start_date);
        $end   = new DateTime($end_date);
        $end->modify('+1 day');

        $days = 0;

        while ($start < $end) {
            $dayNum = (int)$start->format('N'); // 6=Sat,7=Sun
            if (!in_array($dayNum, [6, 7], true)) {
                $days++;
            }
            $start->modify('+1 day');
        }

        return (float)$days;
    }

    /* ============================================================
     | HELPERS (USED IN ATTENDANCE GRID / CALENDAR)
     * ============================================================ */

    public function is_on_leave(int $user_id, string $date): bool
    {
        if ($user_id <= 0 || !$date) return false;

        return $this->db
            ->where('user_id', $user_id)
            ->where('status', 'approved')
            ->where('deleted_at IS NULL', null, false)
            ->where('start_date <=', $date)
            ->where('end_date >=', $date)
            ->count_all_results($this->table) > 0;
    }

    public function get_calendar_events(string $start, string $end, int $user_id): array
    {
        if ($user_id <= 0) return [];

        $start_date = (new DateTime($start))->format('Y-m-d');
        $end_date   = (new DateTime($end))->format('Y-m-d');

        return $this->db
            ->select("
                id,
                start_date,
                end_date,
                start_time,
                end_time,
                total_days,
                status,
                reason,
                leave_type_id
            ")
            ->from($this->table)
            ->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->where('status', 'approved')
            ->where('start_date <=', $end_date)
            ->where('end_date >=', $start_date)
            ->get()
            ->result_array();
    }

    public function get_upcoming_leaves(int $limit = 5): array
    {
        $today = date('Y-m-d');

        $this->db->select("{$this->table}.*, u.firstname, u.lastname, u.emp_department");
        $this->db->from($this->table);
        $this->db->join('users u', "{$this->table}.user_id = u.id", 'left');

        $this->db->where("{$this->table}.deleted_at IS NULL", null, false);
        $this->db->where("{$this->table}.status", 'approved');
        $this->db->where("{$this->table}.start_date >=", $today);

        $this->db->order_by("{$this->table}.start_date", 'ASC');
        $this->db->limit(max(1, (int)$limit));

        $results = $this->db->get()->result_array();

        foreach ($results as &$row) {
            $row['employee_name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            $row['days'] = $row['leave_days'] ?? '-';
        }
        unset($row);

        return $results;
    }

    public function count_on_leave_today(): int
    {
        $today = date('Y-m-d');

        return (int)$this->db
            ->where('status', 'approved')
            ->where('deleted_at IS NULL', null, false)
            ->where('start_date <=', $today)
            ->where('end_date >=', $today)
            ->count_all_results($this->table);
    }
        

/**
 * Leave types with limit columns (for balance strip)
 */
public function get_leave_types_with_limits(): array
{
    return $this->db
        ->select('id, name, type, allowed_annually, allowed_monthly, attachment_required')
        ->from('leave_types')
        ->where('deleted_at IS NULL', null, false)
        ->order_by('name', 'ASC')
        ->get()
        ->result_array();
}

/**
 * Per-user stats (pending/approved/rejected/cancelled + total)
 */
public function get_user_leave_stats(int $user_id): array
{
    if ($user_id <= 0) return [
        'pending'   => 0,
        'approved'  => 0,
        'rejected'  => 0,
        'cancelled' => 0,
        'total_applied' => 0,
    ];

    $rows = $this->db
        ->select('status, COUNT(*) AS total', false)
        ->from($this->table)
        ->where('user_id', $user_id)
        ->where('deleted_at IS NULL', null, false)
        ->group_by('status')
        ->get()
        ->result_array();

    $map = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0];
    foreach ($rows as $r) {
        $st = strtolower((string)($r['status'] ?? ''));
        if (isset($map[$st])) $map[$st] = (int)$r['total'];
    }
    $map['total_applied'] = array_sum($map);
    return $map;
}        
}
