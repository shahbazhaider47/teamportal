<?php defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('CronService');
        $this->load->helper(['url']);
        $this->load->model('System_settings_model', 'sysset');
    }

    /**
     * Run all due tasks (or a single task by slug).
     * CLI-only by default. HTTP requires ?token=... and cron_enable_http=1.
     *
     * Usage:
     *  php index.php cron run
     *  php index.php cron run system:cleanup_logs
     *  GET /cron/run?token=XYZ
     *  GET /cron/run/system:cleanup_logs?token=XYZ&force=1
     */
    public function run($slug = null)
    {
        if (!$this->authorized()) {
            show_error('Unauthorized', 401);
            return;
        }

        $force = (bool) $this->input->get('force');
        $out   = $this->cronservice->run($slug, $force);

        $this->respond($out);
    }

    /**
     * Light-weight health endpoint
     */
    public function health()
    {
        if (!$this->authorized()) { show_error('Unauthorized', 401); return; }
        $out = $this->cronservice->health();
        $this->respond($out);
    }

    /**
     * Backward-compat shim if something still calls old cleanup directly.
     */
    public function cleanup()
    {
        if (!$this->authorized()) { show_error('Unauthorized', 401); return; }
        $out = $this->cronservice->run('system:cleanup_logs', true);
        $this->respond($out);
    }

    /* ---------------------------- internals ----------------------------- */

    protected function authorized()
    {
        if (is_cli()) return true;

        $enabled = (int) $this->sysset->get('cron_enable_http', 'cron', 0);
        if (!$enabled) return false;

        $token = $this->input->get('token');
        $want  = (string) $this->sysset->get('cron_auth_token', 'cron', '');
        return $token && $want && hash_equals($want, (string)$token);
    }

    protected function respond($payload)
    {
        // If CLI, echo; else JSON
        if (is_cli()) {
            echo json_encode($payload, JSON_PRETTY_PRINT) . PHP_EOL;
        } else {
            $this->output->set_content_type('application/json')->set_output(json_encode($payload));
        }
    }

    public function unlock()
    {
        // Only allow from CLI or logged-in admin (or same token as run/health)
        $isOk = false;
        if (is_cli()) {
            $isOk = true;
        } else {
            // allow via token if HTTP runner is enabled
            if ($this->authorized()) $isOk = true;
            // or your own admin check here if you prefer
            if (function_exists('is_admin') && is_admin()) $isOk = true;
        }
        if (!$isOk) { show_error('Unauthorized', 401); return; }
    
        // crude unlock: re-create service and call a small internal helper
        $ref = new ReflectionClass($this->cronservice);
        $prop = $ref->getProperty('lockFile');
        $prop->setAccessible(true);
        $lockFile = $prop->getValue($this->cronservice);
        $ok = true;
        if (file_exists($lockFile)) {
            $ok = @unlink($lockFile);
        }
        $this->respond(['ok' => (bool)$ok, 'message' => $ok ? 'Lock cleared' : 'Nothing to clear']);
    }

}
