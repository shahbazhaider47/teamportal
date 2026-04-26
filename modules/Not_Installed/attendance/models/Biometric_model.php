<?php defined('BASEPATH') or exit('No direct script access allowed');

class Biometric_model extends CI_Model
{
    private $devices = 'biometric_devices';
    private $raw     = 'biometric_raw_logs';
    private $map     = 'biometric_user_map';
    private $jobs    = 'biometric_import_jobs';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        // Load your canonical Attendance_model for final writes:
        $this->load->model('attendance/Attendance_model', 'attendance');
    }

    /* ---------------------------
     * Devices
     * --------------------------- */
    public function list_devices(): array
    {
        return $this->db->order_by('id','desc')->get($this->devices)->result_array();
    }

    public function get_device(int $id): ?array
    {
        $row = $this->db->get_where($this->devices, ['id'=>$id])->row_array();
        return $row ?: null;
    }

    public function upsert_device(?int $id, array $data): int
    {
        $payload = [
            'name'        => trim($data['name'] ?? ''),
            'ip_address'  => trim($data['ip_address'] ?? ''),
            'port'        => (int)($data['port'] ?? 4370),
            'comm_key'    => $data['comm_key'] ?? null,
            'device_sn'   => $data['device_sn'] ?? null,
            'timezone'    => $data['timezone'] ?? null,
            'is_active'   => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ];
        if ($id) {
            $this->db->where('id', $id)->update($this->devices, $payload);
            return $id;
        }
        $this->db->insert($this->devices, $payload);
        return (int)$this->db->insert_id();
    }

    public function delete_device(int $id): bool
    {
        return (bool)$this->db->delete($this->devices, ['id'=>$id]);
    }

    /* ---------------------------
     * Mapping
     * --------------------------- */
    public function list_mappings(int $device_id): array
    {
        $sql = "SELECT m.*, u.fullname
                  FROM {$this->map} m
             LEFT JOIN users u ON u.id = m.user_id
                 WHERE m.device_id = ?";
        return $this->db->query($sql, [$device_id])->result_array();
    }

    public function upsert_mapping(int $device_id, string $device_user_id, int $user_id, ?string $user_code = null): void
    {
        $exists = $this->db->get_where($this->map, [
            'device_id' => $device_id,
            'device_user_id' => $device_user_id
        ])->row_array();

        $payload = ['device_id'=>$device_id, 'device_user_id'=>$device_user_id, 'user_id'=>$user_id, 'user_code'=>$user_code];
        if ($exists) {
            $this->db->where('id', $exists['id'])->update($this->map, $payload);
        } else {
            $this->db->insert($this->map, $payload);
        }
    }

    public function delete_mapping(int $id): bool
    {
        return (bool)$this->db->delete($this->map, ['id'=>$id]);
    }

    /* ---------------------------
     * Jobs + Staging
     * --------------------------- */
    public function start_job(int $device_id, ?int $requested_by, ?string $from, ?string $to): int
    {
        $this->db->insert($this->jobs, [
            'device_id'   => $device_id,
            'requested_by'=> $requested_by,
            'status'      => 'running',
            'started_at'  => date('Y-m-d H:i:s'),
            'range_from'  => $from,
            'range_to'    => $to,
        ]);
        return (int)$this->db->insert_id();
    }

    public function finish_job(int $job_id, string $status, int $total, int $inserted, int $skipped, ?string $notes): void
    {
        $this->db->where('id',$job_id)->update($this->jobs, [
            'status'     => $status,
            'ended_at'   => date('Y-m-d H:i:s'),
            'total_pulls'=> $total,
            'inserted'   => $inserted,
            'skipped'    => $skipped,
            'notes'      => $notes
        ]);
    }

    public function stage_logs(int $device_id, array $records, int $duplicateWindowSec = 60): array
    {
        $inserted = 0; $skipped = 0; $total = count($records);

        foreach ($records as $r) {
            $device_user_id = (string)$r['device_user_id'];
            $punch_time     = (string)$r['punch_time'];
            $status_code    = $r['status_code'] ?? null;
            $verified       = $r['verified'] ?? null;
            $work_code      = $r['work_code'] ?? null;
            $punch_type     = $r['punch_type'] ?? null;

            // Soft dedup: if an existing within N seconds, skip
            $sql = "SELECT id FROM {$this->raw}
                     WHERE device_id = ? AND device_user_id = ?
                       AND ABS(TIMESTAMPDIFF(SECOND, punch_time, ?)) <= ?";
            $exists = $this->db->query($sql, [$device_id, $device_user_id, $punch_time, $duplicateWindowSec])->row_array();

            if ($exists) { $skipped++; continue; }

            // Hard dedup (unique key) will also protect exact dupes
            $this->db->insert($this->raw, [
                'device_id'      => $device_id,
                'device_user_id' => $device_user_id,
                'punch_time'     => $punch_time,
                'status_code'    => $status_code,
                'verified'       => $verified,
                'work_code'      => $work_code,
                'punch_type'     => $punch_type,
            ]);
            $inserted++;
        }

        // Touch device last_fetch_at
        $this->db->where('id', $device_id)->update($this->devices, ['last_fetch_at'=>date('Y-m-d H:i:s')]);

        return compact('total','inserted','skipped');
    }

    /* ---------------------------
     * Transform → attendance
     * --------------------------- */
    public function transform_to_attendance(int $device_id, string $fromDate, string $toDate): array
    {
        // 1) fetch staged punches for mapped users in window (date inclusive)
        $sql = "SELECT r.device_user_id, r.punch_time, m.user_id
                  FROM {$this->raw} r
            INNER JOIN {$this->map} m ON m.device_id = r.device_id AND m.device_user_id = r.device_user_id
                 WHERE r.device_id = ?
                   AND r.punch_time BETWEEN ? AND ?
              ORDER BY r.device_user_id, r.punch_time ASC";
        $rows = $this->db->query($sql, [$device_id, $fromDate.' 00:00:00', $toDate.' 23:59:59'])->result_array();

        if (!$rows) return ['days'=>0,'created'=>0,'updated'=>0,'flags'=>0];

        // Group by user + day
        $byDay = [];
        foreach ($rows as $row) {
            $uid = (int)$row['user_id'];
            $day = substr($row['punch_time'], 0, 10);
            $byDay[$uid][$day][] = $row['punch_time'];
        }

        $created = $updated = $flags = $days = 0;

        foreach ($byDay as $uid => $daysData) {
            foreach ($daysData as $day => $times) {
                $days++;
                $in  = min($times);
                $out = max($times);

                // Defaults (fallback to module settings)
                $tz    = get_setting('biometric_timezone', 'Asia/Karachi');
                $shiftStart = get_setting('biometric_default_shift_start', '09:00');
                $shiftEnd   = get_setting('biometric_default_shift_end',   '18:00');
                $graceMin   = (int)get_setting('biometric_grace_minutes', 5);
                $lateAfter  = (int)get_setting('biometric_late_after_minutes', 10);
                $earlyBefore= (int)get_setting('biometric_early_leave_before_minutes', 10);

                // If you have per-user shift logic, resolve here:
                // [$shiftStart, $shiftEnd] = $this->resolve_user_shift($uid, $day) ?: [$shiftStart, $shiftEnd];

                $scheduledIn  = new DateTime("$day $shiftStart", new DateTimeZone($tz));
                $scheduledOut = new DateTime("$day $shiftEnd", new DateTimeZone($tz));
                $actualIn     = new DateTime($in,  new DateTimeZone($tz));
                $actualOut    = new DateTime($out, new DateTimeZone($tz));

                $isDuplicate = (count($times) !== count(array_unique($times)));

                $late = ($actualIn > (clone $scheduledIn)->modify("+{$graceMin} minutes")->modify("+{$lateAfter} minutes"));
                $early= ($actualOut < (clone $scheduledOut)->modify("-{$graceMin} minutes")->modify("+0 minutes"));

                $missedCheckout = ($in === $out); // no second punch that day

                $flagsMask = 0;
                $flagsMask |= $isDuplicate   ? 1 : 0;         // bit 0
                $flagsMask |= $late          ? 2 : 0;         // bit 1
                $flagsMask |= $early         ? 4 : 0;         // bit 2
                $flagsMask |= $missedCheckout? 8 : 0;         // bit 3

                // Upsert into attendance (adjust column names to your schema)
                $exists = $this->attendance->get_by_user_and_date($uid, $day); // you likely have similar
                $payload = [
                    'user_id'       => $uid,
                    'date'          => $day,
                    'check_in'      => $actualIn->format('Y-m-d H:i:s'),
                    'check_out'     => $missedCheckout ? null : $actualOut->format('Y-m-d H:i:s'),
                    'is_late'       => $late ? 1 : 0,
                    'left_early'    => $early ? 1 : 0,
                    'missed_checkout'=> $missedCheckout ? 1 : 0,
                    'flags'         => $flagsMask,
                    'source'        => 'biometric',
                ];

                if ($exists) { $this->attendance->update($exists['id'], $payload); $updated++; }
                else         { $this->attendance->create($payload);                $created++; }
                if ($flagsMask) $flags++;
            }
        }

        return compact('days','created','updated','flags');
    }

    /* Example helper if you later wire per-user shift lookup
    private function resolve_user_shift(int $user_id, string $day): ?array
    {
        // read from hrm_employees or shifts table; return ['09:00','18:00'] or null
        return null;
    }
    */
}
