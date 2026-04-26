<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Reminders_model extends CI_Model
{
    protected $table = 'reminders';


    
    public function get_all()
    {
        // Admin / Global view — includes user name of reminder creator
        return $this->db
            ->select('r.*, CONCAT(u.firstname, " ", u.lastname) AS created_by_name')
            ->from('reminders AS r')
            ->join('users AS u', 'u.id = r.created_by', 'left')
            ->order_by('r.date', 'DESC')
            ->get()
            ->result_array();
    }
    
    public function get_all_by_user($user_id)
    {
        // Personal view — reminders created by this user
        return $this->db
            ->select('r.*, CONCAT(u.firstname, " ", u.lastname) AS created_by_name')
            ->from('reminders AS r')
            ->join('users AS u', 'u.id = r.created_by', 'left')
            ->where('r.created_by', $user_id)
            ->order_by('r.date', 'DESC')
            ->get()
            ->result_array();
    }


    public function add($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function get_todays_reminders()
    {
        $user_id = $this->session->userdata('user_id');
        $today = date('Y-m-d');
        
        return $this->db
            ->where('created_by', $user_id)
            ->where('DATE(date)', $today)
            ->where('is_completed', 0)
            ->order_by('date', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function get_upcoming_reminders($limit = 5)
    {
        $user_id = $this->session->userdata('user_id');
        $today = date('Y-m-d');
        
        return $this->db
            ->where('created_by', $user_id)
            ->where('DATE(date) >', $today)
            ->where('is_completed', 0)
            ->order_by('date', 'ASC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();
    }

    public function mark_as_completed($id)
    {
        return $this->db
            ->where('id', $id)
            ->update($this->table, [
                'is_completed' => 1,
                'completed_at' => date('Y-m-d H:i:s')
            ]);
    }


public function get_report_summary()
{
    // Example: Count reminders by priority for reporting.
    $this->db->select('priority, COUNT(*) as count');
    $this->db->group_by('priority');
    $query = $this->db->get('reminders');

    return $query->result_array();
}
 

// Get reminders for dashboard widget (latest N reminders for the user)
public function get_dashboard_reminders($user_id = null, $limit = 5)
{
    if ($user_id === null) {
        $user_id = $this->session->userdata('user_id');
    }
    return $this->db
        ->where('created_by', $user_id)
        ->order_by('date', 'ASC')
        ->limit($limit)
        ->get($this->table)
        ->result_array();
}


// ─────────────────────────────────────────────────────────────
// Cron helpers (no schema change)
// ─────────────────────────────────────────────────────────────
public function load_candidates(string $from, string $until): array
{
    $base = $this->db->select('id,title,description,date,priority,is_recurring,recurring_dates,created_by')
                     ->from($this->table)
                     ->where('date >=', $from)
                     ->where('date <=', $until)
                     ->get()->result_array();

    $rec  = $this->db->select('id,title,description,date,priority,is_recurring,recurring_dates,created_by')
                     ->from($this->table)
                     ->where('is_recurring', 1)
                     ->where('recurring_dates IS NOT NULL', null, false)
                     ->get()->result_array();

    $byId = [];
    foreach (array_merge($base, $rec) as $r) { $byId[$r['id']] = $r; }
    return array_values($byId);
}

public function send_occurrence(array $occ): bool
{
    log_message('info', sprintf(
        '[reminders] dispatch id=%d user_id=%d at=%s title="%s"',
        $occ['id'], $occ['user_id'], $occ['occurrence_at'], $occ['title'] ?? ''
    ));

    // Optional in-app notification (uses your existing model if present)
    if (class_exists('Notification_model')) {
        try {
            $CI = &get_instance();
            $CI->load->model('Notification_model');
            $CI->Notification_model->add([
                'user_id'    => (int)$occ['user_id'],
                'sender_id'  => (int)$occ['user_id'],
                'short_text' => 'Reminder: ' . ($occ['title'] ?? 'Reminder'),
                'full_text'  => ($occ['title'] ?? 'Reminder') . '|' . ($occ['description'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Reminders notify failed: ' . $e->getMessage());
        }
    }
    return true;
}

// ─────────────────────────────────────────────────────────────
// UI Alert flow (align with install.php -> reminder_alerts)
// ─────────────────────────────────────────────────────────────

private function mapAlertTypeToEnum($type)
{
    // Accept both 'minus_30'/'minus_5' and '30'/'5' and normalize to '30'/'5'
    $t = strtolower((string)$type);
    if ($t === 'minus_30' || $t === '30') return '30';
    if ($t === 'minus_5'  || $t === '5')  return '5';
    return null; // unknown -> ignore
}

public function find_next_alert_for_user($user_id)
{
    // We will show an alert if:
    // - The alert time (occurrence_at minus 30 or 5 minutes) is <= now
    // - AND the reminder occurrence is not older than 24h
    // - AND the alert is NOT acknowledged yet
    //
    // We do NOT suppress by delivered_at anymore (so it can re-show on reloads)
    // Only acknowledged_at blocks it.

    $now      = new DateTime('now');
    $nowTs    = $now->getTimestamp();
    $cutoffTs = $nowTs - 24 * 3600; // 24 hours look-back for the occurrence itself

    // Fetch a reasonable batch for this user (non-completed). Extend for recurring if needed.
    $rem = $this->db->where('created_by', $user_id)
                    ->where('is_completed', 0)
                    ->order_by('date', 'ASC')
                    ->limit(50)
                    ->get('reminders')->result_array();

    $best = null;            // the “next” alert to show
    $bestAlertAtTs = null;   // timestamp of that alert’s alert-time (occurrence minus X minutes)

    foreach ($rem as $r) {
        if (empty($r['date'])) { continue; }

        $occ      = new DateTime($r['date']);
        $occTs    = $occ->getTimestamp();

        // Skip occurrences older than the 24h cutoff
        if ($occTs < $cutoffTs) { continue; }

        // Two alert times per occurrence: -30 and -5 minutes
        $candidates = [
            ['typeUi' => 'minus_30', 'dbType' => '30', 'alertAtTs' => $occTs - 30 * 60],
            ['typeUi' => 'minus_5',  'dbType' => '5',  'alertAtTs' => $occTs -  5 * 60],
        ];

        foreach ($candidates as $c) {
            // We only care about alerts that should have already fired (<= now)
            if ($c['alertAtTs'] > $nowTs) { continue; }

            // Check if already acknowledged
            $row = $this->db->where([
                        'user_id'       => (int)$user_id,
                        'reminder_id'   => (int)$r['id'],
                        'occurrence_at' => date('Y-m-d H:i:00', $occTs),
                        'alert_type'    => $c['dbType'],    // ENUM '30' or '5'
                    ])
                    ->get('reminder_alerts')->row_array();

            if ($row && !empty($row['acknowledged_at'])) {
                // User has dismissed this alert in the past — skip it forever
                continue;
            }

            // This candidate needs to be surfaced. Prefer the *most recent* alertAt up to now.
            if ($best === null || $c['alertAtTs'] > $bestAlertAtTs) {
                $best = [
                    'id'            => (int)$r['id'],
                    'title'         => $r['title'] ?? 'Reminder',
                    'description'   => $r['description'] ?? '',
                    'priority'      => $r['priority'] ?? 'medium',
                    'occurrence_at' => date('Y-m-d H:i:00', $occTs),
                    'typeUi'        => $c['typeUi'],
                    'dbType'        => $c['dbType'],
                    'alertAtTs'     => $c['alertAtTs'],
                ];
                $bestAlertAtTs = $c['alertAtTs'];
            }
        }
    }

    if ($best === null) {
        return null;
    }

    // Upsert a tracking row and stamp delivered_at (does NOT suppress future shows).
    $row = $this->db->where([
                'user_id'       => (int)$user_id,
                'reminder_id'   => (int)$best['id'],
                'occurrence_at' => $best['occurrence_at'],
                'alert_type'    => $best['dbType'],
            ])->get('reminder_alerts')->row_array();

    if ($row) {
        $this->db->where('id', $row['id'])->update('reminder_alerts', [
            'delivered_at' => date('Y-m-d H:i:00'),
        ]);
    } else {
        $this->db->insert('reminder_alerts', [
            'user_id'       => (int)$user_id,
            'reminder_id'   => (int)$best['id'],
            'occurrence_at' => $best['occurrence_at'],
            'alert_type'    => $best['dbType'],
            'delivered_at'  => date('Y-m-d H:i:00'),
            'created_at'    => date('Y-m-d H:i:00'),
        ]);
    }

    // Return UI payload. Keep 'type' compatible with modal JS.
    return [
        'id'            => $best['id'],
        'title'         => $best['title'],
        'description'   => $best['description'],
        'priority'      => $best['priority'],
        'occurrence_at' => $best['occurrence_at'],
        'alert_type'    => $best['typeUi'], // 'minus_30' or 'minus_5'
    ];
}


public function acknowledge_alert($user_id, $reminder_id, $occurrence_at, $type)
{
    $dbType = $this->mapAlertTypeToEnum($type);
    if ($dbType === null) return false;

    // Upsert to set acknowledged_at (if record not there yet, create it)
    $row = $this->db->where([
                'user_id'       => (int)$user_id,
                'reminder_id'   => (int)$reminder_id,
                'occurrence_at' => date('Y-m-d H:i:00', strtotime($occurrence_at)),
                'alert_type'    => $dbType,
           ])->get('reminder_alerts')->row_array();

    if ($row) {
        return $this->db->where('id', $row['id'])->update('reminder_alerts', [
            'acknowledged_at' => date('Y-m-d H:i:00'),
        ]);
    }

    return $this->db->insert('reminder_alerts', [
        'user_id'         => (int)$user_id,
        'reminder_id'     => (int)$reminder_id,
        'occurrence_at'   => date('Y-m-d H:i:00', strtotime($occurrence_at)),
        'alert_type'      => $dbType,
        'delivered_at'    => null,
        'acknowledged_at' => date('Y-m-d H:i:00'),
        'created_at'      => date('Y-m-d H:i:00'),
    ]);
}


// =========================================================
// CRON ENTRYPOINT (called by CronService via model callback)
// =========================================================
public function cron_dispatch_due(): void
{
    // Small, safe window: send reminders due in the last 5 minutes up to "now"
    $windowPastMin  = 5;   // how far back to include (missed, slight delays)
    $windowAheadMin = 0;   // how far ahead to include (usually 0 for "now")

    $now   = time();
    $from  = date('Y-m-d H:i:00', $now - $windowPastMin  * 60);
    $until = date('Y-m-d H:i:00', $now + $windowAheadMin * 60);

    // 1) Load candidates efficiently (your existing helper)
    $candidates = $this->load_candidates($from, $until);

    // 2) Filter by minute window for one-time and recurring JSON
    $dueOccurrences = $this->filter_due_occurrences_for_cron($candidates, $from, $until);

    // 3) Dispatch each due occurrence (idempotency is optional; handled in sender if needed)
    foreach ($dueOccurrences as $occ) {
        // If you want idempotency, you can add a small cache check here (file cache)
        // but you already handle downstream safely; this keeps things DB-free.
        $this->send_occurrence($occ);
    }
}

/**
 * Minute-level filter for both one-time and recurring reminders.
 * Reuses your shape from send_occurrence(): id, title, description, priority, occurrence_at, user_id
 */
private function filter_due_occurrences_for_cron(array $rows, string $from, string $until): array
{
    $due = [];
    $fromTs  = strtotime($from);
    $untilTs = strtotime($until);

    foreach ($rows as $r) {
        // 1) Non-recurring: use main 'date'
        if (empty($r['is_recurring'])) {
            $occTs = strtotime($r['date']);
            if ($occTs !== false && $occTs >= $fromTs && $occTs <= $untilTs) {
                $due[] = [
                    'id'            => (int)$r['id'],
                    'title'         => $r['title'] ?? 'Reminder',
                    'description'   => $r['description'] ?? '',
                    'priority'      => $r['priority'] ?? 'medium',
                    'occurrence_at' => date('Y-m-d H:i:00', $occTs),
                    'user_id'       => (int)$r['created_by'],
                ];
            }
            continue;
        }

        // 2) Recurring: filter JSON array of datetimes
        $list = [];
        if (!empty($r['recurring_dates'])) {
            $decoded = is_array($r['recurring_dates'])
                ? $r['recurring_dates']
                : @json_decode($r['recurring_dates'], true);
            if (is_array($decoded)) $list = $decoded;
        }

        foreach ($list as $when) {
            $ts = strtotime($when);
            if ($ts !== false && $ts >= $fromTs && $ts <= $untilTs) {
                $due[] = [
                    'id'            => (int)$r['id'],
                    'title'         => $r['title'] ?? 'Reminder',
                    'description'   => $r['description'] ?? '',
                    'priority'      => $r['priority'] ?? 'medium',
                    'occurrence_at' => date('Y-m-d H:i:00', $ts),
                    'user_id'       => (int)$r['created_by'],
                ];
            }
        }
    }
    return $due;
}



    /**
     * Return reminders for a user between datetime bounds.
     * Uses only title, description, date.
     */
    public function get_calendar_reminders_basic(int $user_id, string $from, string $to): array
    {
        // Expecting 'date' to be DATETIME (or DATE—works either way)
        $this->db->select('id, title, description, date');
        $this->db->from('reminders');
        $this->db->where('created_by', $user_id);
        // If you want to skip completed reminders, keep this; otherwise remove.
        $this->db->group_start()
                 ->where('is_completed', 0)
                 ->or_where('is_completed IS NULL', null, false)
                 ->group_end();
        $this->db->where('date >=', $from);
        $this->db->where('date <=', $to);
        $this->db->order_by('date', 'ASC');

        return $this->db->get()->result_array();
    }

}