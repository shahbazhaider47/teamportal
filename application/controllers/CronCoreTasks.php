<?php defined('BASEPATH') or exit('No direct script access allowed');

class CronCoreTasks extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('System_settings_model', 'sysset');
        $this->load->helper('date');
    }

    /**
     * Task: system:cleanup_logs (scheduled daily at 03:00)
     */
    public function cleanup_logs()
    {
        // General retention (default 90 days)
        $days   = (int) (get_setting('cron_retention_days') ?: 90);
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $tables = [
            ['table' => 'activity_log',   'col' => 'created_at'],
            ['table' => 'login_attempts', 'col' => 'attempt_time'],
            ['table' => 'notifications',  'col' => 'created_at'],
            // NOTE: cron_history handled by its own policy below
        ];

        $deletedTotals = [];

        foreach ($tables as $t) {
            if ($this->db->table_exists($t['table'])) {
                $this->db->where($t['col'].' <', $cutoff)->delete($t['table']);
                $deletedTotals[$t['table']] = $this->db->affected_rows();
            }
        }

        // Dedicated retention for cron_history (default 7 days)
        $histDays = (int) (get_setting('cron_history_retention_days') ?: 7);
        if ($this->db->table_exists('cron_history')) {
            $historyCutoff = date('Y-m-d H:i:s', strtotime("-{$histDays} days"));
            $this->db->where('started_at <', $historyCutoff)->delete('cron_history');
            $deletedTotals['cron_history'] = $this->db->affected_rows();
        }

        // Optional summary email
        if (get_setting('email_log_cron_summary')) {
            // Pull sender/recipient from settings (fallbacks included)
            $fromEmail = get_setting('from_email') ?: 'no-reply@' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost');
            $fromName  = get_setting('from_name') ?: 'System Cron';
            $toEmail   = get_setting('cron_summary_recipient') ?: get_setting('from_email');

            if ($toEmail) {
                $this->load->library('email');
                $this->email->from($fromEmail, $fromName);
                $this->email->to($toEmail);
                $this->email->subject('Daily Log Cleanup Report');

                $lines = ["Cutoff (general): {$cutoff}", "Cron history retention: {$histDays} days"];
                foreach ($deletedTotals as $table => $n) {
                    $lines[] = sprintf('%s: %d deleted', $table, (int)$n);
                }
                $this->email->message(nl2br(implode("\n", $lines)));
                $this->email->send(); // no @ — surface errors to logs
            }
        }

        // Optional: log summary
        log_message('info', '[cron] cleanup_logs done: ' . json_encode($deletedTotals));
    }

    public function cleanup_cron_history()
    {
        $CI =& get_instance();
        $db = $CI->db;
    
        if (!$db->table_exists('cron_history')) {
            return; // Nothing to do
        }
    
        // Compute cutoff: now - 24 hours
        $cutoff = date('Y-m-d H:i:s', time() - 86400);
    
        // Delete ONLY non-failed history older than 24h
        // Keep "failed" rows for diagnostics.
        $db->where('status !=', 'failed');
        $db->where('started_at <', $cutoff);
        $db->delete('cron_history');
    }
 
     /**
     * Task: system:cleanup_sessions
     * Cleans up expired CI file-based sessions
     * Recommended schedule: every 30–60 minutes
     */
    public function cleanup_sessions()
    {
        // Session config (keep in sync with config.php)
        $sessionPath = APPPATH . 'ci_sessions';
        $expiration  = (int) ($this->config->item('sess_expiration') ?: 7200);
    
        if (!is_dir($sessionPath) || !is_readable($sessionPath)) {
            log_message('error', '[cron] cleanup_sessions: session path not accessible');
            return;
        }
    
        $now       = time();
        $deleted   = 0;
        $skipped   = 0;
    
        foreach (glob($sessionPath . '/*') as $file) {
            if (!is_file($file)) {
                continue;
            }
    
            // File last modified time
            $mtime = filemtime($file);
    
            // Skip active sessions
            if (($now - $mtime) < $expiration) {
                $skipped++;
                continue;
            }
    
            if (@unlink($file)) {
                $deleted++;
            }
        }
    
        log_message(
            'info',
            sprintf(
                '[cron] cleanup_sessions done: %d deleted, %d active kept',
                $deleted,
                $skipped
            )
        );
    }
   
}