<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * HMVC controller invoked by CronService via:
 *   Modules::run('reminders/Reminders_cron/dispatch_due')
 *   Modules::run('reminders/Reminders_cron/email_minus_30')
 *
 * Keep this controller lean: no auth/session; only cron-safe work.
 */
class Reminders_cron extends CI_Controller  // keep CI_Controller
{
    /** Minute windows for dispatch_due (in-app / alerts) */
    private $windowPastMin  = 5;
    private $windowAheadMin = 0;

    /** File-cache idempotency TTL (seconds) to avoid duplicate deliveries within a short time */
    private $idempotencyTtl = 600;

    public function __construct()
    {
        parent::__construct();

        // Models
        $this->load->model('reminders/Reminders_model', 'rem');
        $this->load->model('reminders/Reminders_mailer_model', 'mailer'); // <-- for -30 emails

        // Cache for idempotency
        $this->load->driver('cache', ['adapter' => 'file']);

        // Helpers/utilities
        $this->load->helper('date');
    }

    /**
     * Dispatch due occurrences (within a minute window) to your in-app delivery flow.
     * This is what you already had — left intact.
     *
     * Cron task suggestion (every 5 minutes):
     *   slug: reminders:dispatch_due
     *   callback: reminders/Reminders_cron/dispatch_due
     *   schedule: * /5 * * * *
     */
    public function dispatch_due()
    {
        $now   = time();
        $from  = date('Y-m-d H:i:00', $now - $this->windowPastMin  * 60);
        $until = date('Y-m-d H:i:00', $now + $this->windowAheadMin * 60);

        $candidates     = $this->rem->load_candidates($from, $until);
        $dueOccurrences = $this->filter_due_occurrences($candidates, $from, $until);

        foreach ($dueOccurrences as $occ) {
            // idempotency key: reminder id + occurrence time
            $key = 'reminders_sent_' . $occ['id'] . '_' . md5($occ['occurrence_at']);
            if ($this->cache->file->get($key)) {
                continue; // already dispatched very recently
            }

            // Your delivery mechanism (e.g., in-app notification)
            $this->rem->send_occurrence($occ);

            // Remember for a short time to avoid duplicates on overlapping runs
            $this->cache->file->save($key, 1, $this->idempotencyTtl);
        }

        if (is_cli()) {
            echo "reminders:dispatch_due OK\n";
        } else {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['ok'=>true]));
        }
    }

    /**
     * NEW: Email users 30 minutes before a reminder is due (plain email).
     * Uses Reminders_mailer_model::cron_email_minus_30 which:
     *   - scans upcoming/near-past occurrences
     *   - computes each occurrence's (-30 min) alertAt
     *   - checks reminder_alerts(alert_type='30', delivered_at) for idempotency
     *   - sends a plain email using system_settings (group=email)
     *
     * Cron task suggestion (every minute):
     *   slug: reminders:email_minus_30
     *   callback: reminders/Reminders_cron/email_minus_30
     *   schedule: * * * * *
     */
    public function email_minus_30()
    {
        $this->mailer->cron_email_minus_30();

        if (is_cli()) {
            echo "reminders:email_minus_30 OK\n";
        } else {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['ok'=>true]));
        }
    }

    /**
     * Minute-level filter for both one-time and recurring reminders — unchanged from your version.
     * Returns array items shaped for send_occurrence():
     *   id, title, description, priority, occurrence_at, user_id
     */
    private function filter_due_occurrences(array $rows, string $from, string $until): array
    {
        $due    = [];
        $fromTs = strtotime($from);
        $toTs   = strtotime($until);

        foreach ($rows as $r) {
            // 1) One-time
            if (empty($r['is_recurring']) || (int)$r['is_recurring'] === 0) {
                $ts = strtotime($r['date']);
                if ($ts !== false && $ts >= $fromTs && $ts <= $toTs) {
                    $due[] = [
                        'id'            => (int)$r['id'],
                        'title'         => $r['title'] ?? 'Reminder',
                        'description'   => $r['description'] ?? '',
                        'priority'      => $r['priority'] ?? 'medium',
                        'occurrence_at' => date('Y-m-d H:i:00', $ts),
                        'user_id'       => (int)$r['created_by'],
                    ];
                }
                continue;
            }

            // 2) Recurring (JSON list of datetimes)
            $list = [];
            if (!empty($r['recurring_dates'])) {
                $j = is_array($r['recurring_dates']) ? $r['recurring_dates'] : @json_decode($r['recurring_dates'], true);
                if (is_array($j)) $list = $j;
            }

            foreach ($list as $when) {
                $ts = strtotime($when);
                if ($ts !== false && $ts >= $fromTs && $ts <= $toTs) {
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
}
