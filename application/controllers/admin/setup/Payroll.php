<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Payroll-specific setup model
        $this->load->model('admin/Payroll_setup_model', 'setup');
        $this->load->model('Activity_log_model');

        // Auth guard
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            exit;
        }

        // Permission guard
        if (! staff_can('manage', 'company')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            echo $html;
            exit;
        }
    }

    /* ==========================================================
     | Utility: Activity Logger
     ========================================================== */
    protected function log_activity(string $action): void
    {
        $this->Activity_log_model->add([
            'user_id'    => (int) ($this->session->userdata('user_id') ?? 0),
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /* ==========================================================
     | Payroll Settings – Main Entry Point
     | URL: /admin/setup/payroll
     |
     | - All payroll configuration loads here
     | - Tabs are handled in the view using hash-based routing
     | - Each tab will later map to its own service/model methods
     ========================================================== */
    public function index()
    {
        $view_data = [
            // Placeholders for future payroll config
            // 'salary_cycles'   => $this->setup->get_salary_cycles(),
            // 'allowances'      => $this->setup->get_allowances(),
            // 'deductions'      => $this->setup->get_deductions(),
            // 'tax_rules'       => $this->setup->get_tax_rules(),
            // 'pf_settings'     => $this->setup->get_pf_settings(),
            // 'final_settlement'=> $this->setup->get_final_settlement_rules(),
        ];

        $layout_data = [
            'page_title' => 'Payroll Settings',
            'subview'    => 'admin/setup/payroll/index',
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /* ==========================================================
     | TAB 1: Salary Cycles
     | Hash: #salarycycles
     ========================================================== */

    /* ==========================================================
     | TAB 2: Allowances
     | Hash: #allowances
     ========================================================== */

    /* ==========================================================
     | TAB 3: Deductions
     | Hash: #deductions
     ========================================================== */

    /* ==========================================================
     | TAB 4: Tax Rules
     | Hash: #taxrules
     ========================================================== */

    /* ==========================================================
     | TAB 5: Payroll Calculations
     | Hash: #calculations
     ========================================================== */

    /* ==========================================================
     | TAB 6: Final Settlement
     | Hash: #finalsettlement
     ========================================================== */
}
