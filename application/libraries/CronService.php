<?php defined('BASEPATH') or exit('No direct script access allowed');

class CronService
{
    /** @var CI_Controller */
    protected $CI;

    protected $lockFile;
    protected $lockTtl; // seconds

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper(['string', 'url']);
        $this->CI->load->model('System_settings_model', 'sysset');

        $tmp = function_exists('get_temp_dir') ? get_temp_dir() : sys_get_temp_dir();
        $this->lockFile = rtrim($tmp, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'pcrm-cron-lock';
        $this->lockTtl  = (int) $this->CI->sysset->get('cron_lock_ttl', 'cron', 600);
    }

    /* ----------------------------- PUBLIC API ------------------------------ */

    public function run($onlySlug = null, $force = false)
    {
        if (!$this->acquireLock()) {
            return ['ok' => false, 'message' => 'Another cron process is running (lock active).'];
        }

        $started = microtime(true);
        $summary = [
            'ok'        => true,
            'message'   => 'Cron executed',
            'ran'       => [],
            'skipped'   => [],
            'failed'    => [],
            'startedAt' => date('Y-m-d H:i:s'),
        ];

        try {
            $tasks = $this->discoverTasks();
            $this->syncTasksToDB($tasks);
            $this->ensureNextRunAt($tasks);

            $due = $this->getDueTasks($onlySlug, $force);

            foreach ($due as $task) {
                $exec = $this->executeTask($task);
                $summary[$exec['status']][] = [
                    'slug'    => $task['slug'],
                    'message' => $exec['message'] ?? null,
                ];
            }

            // Mark last run (stored in system_settings, group=cron)
            $this->CI->sysset->set('last_cron_run', (string) time(), 'cron');
            if (is_cli()) {
                $this->CI->sysset->set('cron_has_run_from_cli', '1', 'cron');
            }

        } catch (\Throwable $e) {
            $summary['ok']      = false;
            $summary['message'] = 'Cron crashed: ' . $e->getMessage();
        } finally {
            $summary['durationSec'] = round(microtime(true) - $started, 3);
            $this->releaseLock();
        }

        return $summary;
    }

    public function health()
    {
        $last = $this->CI->sysset->get('last_cron_run', 'cron', null);
        $lockAge = null;
        if (file_exists($this->lockFile)) {
            $created = (int) @file_get_contents($this->lockFile);
            if ($created) $lockAge = time() - $created;
        }
        return [
            'lastRunUnix' => $last ?: null,
            'lastRunAt'   => $last ? date('Y-m-d H:i:s', (int)$last) : null,
            'lockExists'  => file_exists($this->lockFile),
            'lockAgeSec'  => $lockAge,
            'lockTtl'     => $this->lockTtl,
            'cliFlag'     => (int) $this->CI->sysset->get('cron_has_run_from_cli', 'cron', 0),
            'dueNow'      => $this->getDueTasks(null, false, true /* peekOnly */),
        ];
    }

    /* ----------------------------- INTERNALS ------------------------------ */

    protected function acquireLock()
    {
        if (!file_exists($this->lockFile)) {
            file_put_contents($this->lockFile, (string) time());
            return true;
        }
        $created = (int) @file_get_contents($this->lockFile);
        if ($created && (time() - $created) > $this->lockTtl) {
            // stale lock
            @unlink($this->lockFile);
            file_put_contents($this->lockFile, (string) time());
            return true;
        }
        return false;
    }

    protected function releaseLock()
    {
        if (file_exists($this->lockFile)) {
            @unlink($this->lockFile);
        }
    }

    /**
     * Core tasks + modules via hooks()->apply_filters('cron_tasks', $tasks)
     */
    protected function discoverTasks()
    {
        $core = [
            [
                'slug'        => 'system:cleanup_logs',
                'description' => 'Purge old activity/login/notification rows',
                'schedule'    => 'daily',
                'source'      => 'core',
                'module_name' => null,
                'callback'    => 'CronCoreTasks/cleanup_logs', // controller/method (HMVC run)
            ],
            [
                'slug'        => 'system:cleanup_cron_history',
                'description' => 'Purge cron_history older than 24 hours (keep failed records)',
                'schedule'    => '30 6 * * *', // runs at 06:30 AM
                'source'      => 'core',
                'module_name' => null,
                'callback'    => 'CronCoreTasks/cleanup_cron_history',
            ],
        ];
    
        if (function_exists('hooks')) {
            $core = hooks()->apply_filters('cron_tasks', $core);
        }
    
        $norm = [];
        foreach ($core as $t) {
            $norm[] = [
                'slug'        => $t['slug'],
                'description' => $t['description'] ?? null,
                'schedule'    => $t['schedule'],
                'source'      => $t['source'] ?? 'module',
                'module_name' => $t['module_name'] ?? null,
                'callback'    => $t['callback'],
                'enabled'     => isset($t['enabled']) ? (int)$t['enabled'] : 1,
            ];
        }
        return $norm;
    }

    protected function syncTasksToDB(array $tasks)
    {
        if (!$this->CI->db->table_exists('cron_tasks')) {
            // No table? Skip syncing silently; cron can still run “force” and callbacks.
            return;
        }
        foreach ($tasks as $t) {
            $row = $this->CI->db->get_where('cron_tasks', ['slug' => $t['slug']])->row_array();
            $data = [
                'description' => $t['description'],
                'schedule'    => $this->normalizeSchedule($t['schedule']),
                'source'      => $t['source'],
                'module_name' => $t['module_name'],
                'callback'    => $t['callback'],
                'enabled'     => $t['enabled'],
                'updated_at'  => date('Y-m-d H:i:s'),
            ];
            if ($row) {
                $this->CI->db->where('id', $row['id'])->update('cron_tasks', $data);
            } else {
                $data['slug']       = $t['slug'];
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->CI->db->insert('cron_tasks', $data);
            }
        }
    }

    protected function getDueTasks($onlySlug = null, $force = false, $peekOnly = false)
    {
        if (!$this->CI->db->table_exists('cron_tasks')) {
            return []; // nothing to run/peek
        }
        if ($onlySlug) {
            $this->CI->db->where('slug', $onlySlug);
        }
        $this->CI->db->where('enabled', 1);
        $rows = $this->CI->db->get('cron_tasks')->result_array();
    
        $due = [];
        foreach ($rows as $r) {
            if ($force) { $due[] = $r; continue; }
    
            $now = time();
            if (!empty($r['next_run_at'])) {
                $dueAt = strtotime($r['next_run_at']);
                if ($dueAt !== false && $dueAt <= $now) {
                    $due[] = $r;
                    continue;
                }
            }
    
            if ($this->isScheduleDue($r['schedule'], $r['last_run_at'])) {
                $due[] = $r;
            }
        }
    
        if ($peekOnly) return array_map(function($t){ return $t['slug']; }, $due);
        return $due;
    }
    
    protected function executeTask(array $task)
    {
        $started = date('Y-m-d H:i:s');
        $status  = 'success';
        $msg     = null;
    
        try {
            $this->invokeCallback($task['callback']);
            $msg = 'Ran OK';
        } catch (\Throwable $e) {
            $status = 'failed';
            $msg    = $e->getMessage();
        }
    
        if ($this->CI->db->table_exists('cron_history')) {
            $this->CI->db->insert('cron_history', [
                'task_slug'  => $task['slug'],
                'started_at' => $started,
                'finished_at'=> date('Y-m-d H:i:s'),
                'status'     => $status,
                'message'    => $msg,
            ]);
        }
    
        if ($this->CI->db->table_exists('cron_tasks')) {
            $this->CI->db->where('id', $task['id'])->update('cron_tasks', [
                'last_run_at' => $started,
                'next_run_at' => $this->computeNextRunAt($task['schedule']),
            ]);
        }
    
        return ['status' => $status === 'success' ? 'ran' : 'failed', 'message' => $msg];
    }

protected function invokeCallback($callback)
{
    // Model callback: "model:module/Model@method" OR "model:Model@method"
    if (strpos($callback, 'model:') === 0) {
        $spec = substr($callback, 6);
        if (strpos($spec, '@') === false) {
            throw new \RuntimeException('Invalid model callback (missing @method): ' . $callback);
        }
        list($modelPath, $method) = explode('@', $spec, 2);

        $alias = 'cron_model_' . md5($modelPath);
        $this->CI->load->model($modelPath, $alias);
        if (!method_exists($this->CI->$alias, $method)) {
            throw new \RuntimeException("Model method not found: {$modelPath}@{$method}");
        }
        return $this->CI->$alias->$method();
    }

    // HMVC controller path: "Controller/method"
    if (strpos($callback, '/') !== false) {
        if (class_exists('Modules') && method_exists('Modules', 'run')) {
            return Modules::run($callback);
        }
        throw new \RuntimeException('HMVC not available. Use a model callback instead.');
    }

    // Static callable: "\Namespace\Class::method"
    if (strpos($callback, '::') !== false) {
        return call_user_func(explode('::', $callback, 2));
    }

    throw new \RuntimeException('Unsupported cron callback format: ' . $callback);
}

    /* -------------------------- Schedule Handling -------------------------- */

    protected function normalizeSchedule($s)
    {
        $map = [
            'every_minute' => '* * * * *',
            'every5'       => '*/5 * * * *',
            'hourly'       => '0 * * * *',
            'daily'        => '0 3 * * *',
            'weekly'       => '0 3 * * 1',
        ];
        return $map[$s] ?? $s;
    }

    protected function isScheduleDue($expression, $lastRunAt)
    {
        $expr = $this->normalizeSchedule($expression);
        $parts = preg_split('/\s+/', trim($expr));
        if (count($parts) !== 5) return false;

        [$m, $h, $dom, $mon, $dow] = $parts;
        $now = getdate();

        return $this->fieldMatches($m,   $now['minutes'])   &&
               $this->fieldMatches($h,   $now['hours'])     &&
               $this->fieldMatches($dom, $now['mday'])      &&
               $this->fieldMatches($mon, $now['mon'])       &&
               $this->fieldMatches($dow, $now['wday']);
    }

    protected function computeNextRunAt($expression)
    {
        $expr = $this->normalizeSchedule($expression);
        $ts   = time() + 60;
        for ($i=0; $i<10000; $i++, $ts+=60) {
            $d = getdate($ts);
            if ($this->fieldMatchesPart($expr, $d)) {
                return date('Y-m-d H:i:s', $ts);
            }
        }
        return null;
    }

    protected function fieldMatchesPart($expr, $d)
    {
        [$m,$h,$dom,$mon,$dow] = preg_split('/\s+/', trim($expr));
        return $this->fieldMatches($m, $d['minutes'])
            && $this->fieldMatches($h, $d['hours'])
            && $this->fieldMatches($dom, $d['mday'])
            && $this->fieldMatches($mon, $d['mon'])
            && $this->fieldMatches($dow, $d['wday']);
    }

    protected function fieldMatches($field, $value)
    {
        // Supports: "*", "*/n", "a,b,c", "a-b", "a-b/n", exact "5"
        if ($field === '*') return true;

        foreach (explode(',', $field) as $part) {
            $part = trim($part);
            $step = 1;

            if (strpos($part, '/') !== false) {
                [$part, $step] = explode('/', $part, 2);
                $step = (int) $step ?: 1;
            }

            if (strpos($part, '-') !== false) {
                [$min, $max] = explode('-', $part, 2);
                $min = (int)$min; $max = (int)$max;
                if ($value >= $min && $value <= $max && (($value - $min) % $step === 0)) {
                    return true;
                }
            } elseif ($part === '*') {
                if ($value % $step === 0) return true;
            } else {
                if ((int)$part === (int)$value) return true;
            }
        }
        return false;
    }


    protected function ensureNextRunAt(array $tasks)
    {
        $now = time();
        foreach ($tasks as $t) {
            // Fetch fresh row (has id)
            $row = $this->CI->db->get_where('cron_tasks', ['slug' => $t['slug']])->row_array();
            if (!$row) continue;
    
            $needsSet = empty($row['next_run_at']);
            $isPast   = false;
            if (!empty($row['next_run_at'])) {
                $isPast = (strtotime($row['next_run_at']) < $now);
            }
    
            // If next_run_at is missing, or stuck in the past (e.g. after time change), recompute
            if ($needsSet || $isPast) {
                $next = $this->computeNextRunAt($row['schedule']);
                $this->CI->db->where('id', $row['id'])->update('cron_tasks', [
                    'next_run_at' => $next,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
    
}
