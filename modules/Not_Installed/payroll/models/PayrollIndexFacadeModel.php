<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('PayrollBaseModel')) {
    // Load from same directory first (works whether modules are under /modules or /application/modules)
    $base = __DIR__ . '/PayrollBaseModel.php';
    if (file_exists($base)) {
        require_once $base;
    } else {
        // Fallback if someone later moves the module under application/modules
        @require_once APPPATH . 'modules/payroll/models/PayrollBaseModel.php';
    }
}

/**
 * Optional facade to keep controller calls simple during transition.
 * Not required if you plan to call underlying models directly.
 */
class PayrollIndexFacadeModel extends PayrollBaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('payroll/PayrollDetailsModel', 'details');
    }

    public function runs_index_data(): array
    {
        return $this->details->runs_index_data();
    }
}
