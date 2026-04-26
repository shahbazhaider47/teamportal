<?php defined('BASEPATH') or exit('No direct script access allowed');

class Cronjob_controller extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('CronService');
    }

    // Legacy entry
    public function cleanup_logs()
    {
        $out = $this->cronservice->run('system:cleanup_logs', true);
        if (is_cli()) {
            echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
        } else {
            $this->output->set_content_type('application/json')->set_output(json_encode($out));
        }
    }
}
