<?php defined('BASEPATH') or exit('No direct script access allowed');

class Reminders_mailer_model extends CI_Model
{
    
    /**
     * Send a simple plain-text email using settings from system_settings (group 'email')
     */
    protected function send_plain(string $to, string $subject, string $body): bool
    {
        $this->load->model('System_settings_model', 'sysset');
        $S = $this->sysset->get_all('email');

        // Build CI email config from settings
        $protocol    = strtolower(trim($S['email_protocol'] ?? 'smtp'));
        $smtp_host   = $S['smtp_host']   ?? '';
        $smtp_port   = (int)($S['smtp_port'] ?? 587);
        $smtp_user   = $S['smtp_user']   ?? '';
        $smtp_pass   = $S['smtp_pass']   ?? '';
        $smtp_crypto = strtolower(trim($S['smtp_crypto'] ?? 'tls')); // '', 'tls', 'ssl'

        // normalize known combos
        if ($protocol === 'smtp') {
            if ($smtp_port === 465) $smtp_crypto = 'ssl';
            if ($smtp_port === 587) $smtp_crypto = 'tls';
        }

        $cfg = [
            'protocol' => $protocol,
            'mailtype' => 'text',
            'charset'  => 'utf-8',
            'wordwrap' => true,
            'newline'  => "\r\n",
            'crlf'     => "\r\n",
        ];
        if ($protocol === 'smtp') {
            $cfg['smtp_host'] = $smtp_host;
            $cfg['smtp_port'] = $smtp_port;
            $cfg['smtp_user'] = $smtp_user;
            $cfg['smtp_pass'] = $smtp_pass;
            if ($smtp_crypto === 'tls' || $smtp_crypto === 'ssl') {
                $cfg['smtp_crypto'] = $smtp_crypto;
            }
        }

        $this->load->library('email');
        $this->email->initialize($cfg);
        // Optional: Reply-To same as from
        $this->email->reply_to($from_email, $from_name);

        // From info
        $from_email = trim($S['from_email'] ?? '');
        $from_name  = trim($S['from_name']  ?? '');
        if ($from_email === '') {
            $host = parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
            $from_email = 'no-reply@' . $host;
        }
        if ($from_name === '') {
            $from_name = get_option('company_name') ?: 'System';
        }

        $this->email->from($from_email, $from_name);
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($body);

        $ok = $this->email->send(false);
        if (!$ok && method_exists($this->email, 'print_debugger')) {
            log_message('error', 'Reminders_mailer_model: send_plain failed: ' . $this->email->print_debugger(['headers']));
        }
        return $ok;
    }

    /**
     * Send the 30-min reminder email (plain) for one occurrence (if not sent already).
     * Uses reminder_alerts(alert_type='30') + delivered_at as idempotent marker.
     */
    public function maybe_send_30min_email(int $userId, int $reminderId, string $occurrenceAt, array $remRow): bool
    {
        // Check if already delivered for this occurrence
        $alert = $this->db->where([
                    'user_id'       => $userId,
                    'reminder_id'   => $reminderId,
                    'occurrence_at' => $occurrenceAt,
                    'alert_type'    => '30',
                ])->get('reminder_alerts')->row_array();

        if ($alert && !empty($alert['delivered_at'])) {
            return false; // already sent
        }

        // Resolve recipient email
        $user = $this->db->select('email, firstname, lastname')
                         ->from('users')->where('id', $userId)->get()->row_array();
        if (!$user || empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            log_message('debug', "[reminders] skip email: no valid email for user_id={$userId}");
            return false;
        }

        $who   = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        $when  = date('M j, Y g:i A', strtotime($occurrenceAt));
        $title = trim($remRow['title'] ?? 'Reminder');
        $desc  = trim($remRow['description'] ?? '');

        $subject = 'Auto Reminder: ' . $title;

        $lines = [];
        $lines[] = ($who !== '' ? "Hi {$who}," : "Hello,");
        $lines[] = "";
        $lines[] = "This is a quick reminder that you have an upcoming item due in ~30 minutes:";
        $lines[] = "";
        $lines[] = "Title: {$title}";
        if ($desc !== '') $lines[] = "Notes: {$desc}";
        $lines[] = "When:  {$when}";
        $lines[] = "";
        $lines[] = "— Sent automatically by " . (get_option('company_name') ?: 'the system');

        $ok = $this->send_plain($user['email'], $subject, implode("\n", $lines));
        if ($ok) {
            // Upsert delivered_at marker
            if ($alert) {
                $this->db->where('id', $alert['id'])->update('reminder_alerts', [
                    'delivered_at' => date('Y-m-d H:i:00'),
                ]);
            } else {
                $this->db->insert('reminder_alerts', [
                    'user_id'        => $userId,
                    'reminder_id'    => $reminderId,
                    'occurrence_at'  => $occurrenceAt,
                    'alert_type'     => '30',
                    'delivered_at'   => date('Y-m-d H:i:00'),
                    'acknowledged_at'=> null,
                    'created_at'     => date('Y-m-d H:i:00'),
                ]);
            }
        }
        return $ok;
    }

    /**
     * Cron: scan reminders and send -30 emails for occurrences that are “now” (±1min)
     * Schedule this task every minute.
     */
    public function cron_email_minus_30(): void
    {
        $now   = time();
        $from  = date('Y-m-d H:i:00', $now - 60);  // previous minute
        $until = date('Y-m-d H:i:00', $now);       // current minute

        // Scan a few hours around now to avoid full-table sweeps
        $windowHours = 6;
        $scanFrom = date('Y-m-d H:i:00', $now - $windowHours * 3600);
        $scanTo   = date('Y-m-d H:i:00', $now + $windowHours * 3600);

        // Use Reminders_model helpers to fetch candidates
        $this->load->model('reminders/Reminders_model', 'rem');
        $candidates = $this->rem->load_candidates($scanFrom, $scanTo);

        foreach ($candidates as $r) {
            $remId   = (int)($r['id'] ?? 0);
            $ownerId = (int)($r['created_by'] ?? 0);
            if ($ownerId <= 0 || $remId <= 0) continue;

            // One-time
            if (empty($r['is_recurring'])) {
                $occTs = strtotime($r['date']);
                if ($occTs === false) continue;
                $alertAt = date('Y-m-d H:i:00', $occTs - 30*60);
                if ($alertAt >= $from && $alertAt <= $until) {
                    $this->maybe_send_30min_email($ownerId, $remId, date('Y-m-d H:i:00', $occTs), $r);
                }
                continue;
            }

            // Recurring list
            $list = [];
            if (!empty($r['recurring_dates'])) {
                $decoded = is_array($r['recurring_dates']) ? $r['recurring_dates'] : @json_decode($r['recurring_dates'], true);
                if (is_array($decoded)) $list = $decoded;
            }
            foreach ($list as $when) {
                $ts = strtotime($when);
                if ($ts === false) continue;
                $alertAt = date('Y-m-d H:i:00', $ts - 30*60);
                if ($alertAt >= $from && $alertAt <= $until) {
                    $clone = $r; $clone['date'] = date('Y-m-d H:i:00', $ts);
                    $this->maybe_send_30min_email($ownerId, $remId, $clone['date'], $clone);
                }
            }
        }
    }
}
