<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Finance extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'form', 'finance']);
        $this->load->library('form_validation');
        $this->load->model('finance/Finance_model', 'finance');
    }

    protected function _render($title, $subview, $data = [])
    {
        add_module_assets('finance', [
            'css' => ['finance.css'],
            'js'  => ['finance.js'],
        ]);

        $this->load->view('layouts/master', [
            'page_title' => $title,
            'subview'    => $subview,
            'view_data'  => $data,
        ]);
    }

    protected function _guard()
    {
        if (!staff_can('view', 'finance')) {
            $this->_forbidden();
        }
    }

    protected function _forbidden()
    {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        echo $html;
        exit;
    }
    
    public function index()
    {
        $this->_guard();

        $currency = function_exists('company_setting')
            ? (company_setting('finance_base_currency') ?: 'USD')
            : 'USD';
        $kpis = $this->finance->get_dashboard_kpis();
        $invoice_status = $this->finance->get_invoice_status_counts();
        $monthly_trend = $this->finance->get_monthly_trend(6);
        $recent_invoices = $this->finance->get_recent_invoices(8);
        $recent_payments = $this->finance->get_recent_payments(8);
        $overdue_invoices = $this->finance->get_overdue_invoices(5);
        $bank_accounts = $this->finance->get_bank_accounts_summary();
        $expense_categories = $this->finance->get_expense_by_category();
        $unmatched_count = $this->finance->get_unmatched_transactions_count();

        $view = [
            'currency'           => $currency,
            'kpis'               => $kpis,
            'invoice_status'     => $invoice_status,
            'monthly_trend'      => $monthly_trend,
            'recent_invoices'    => $recent_invoices,
            'recent_payments'    => $recent_payments,
            'overdue_invoices'   => $overdue_invoices,
            'bank_accounts'      => $bank_accounts,
            'expense_categories' => $expense_categories,
            'unmatched_count'    => $unmatched_count,
        ];

        $this->_render('Finance Dashboard', 'finance/overview', $view);
    }

    public function settings()
    {
        $this->_guard();
    
        if ($this->input->post('settings')) {
            $settings = $this->input->post('settings');
            $this->finance->save_settings($settings);
            set_alert('success', 'Finance settings updated successfully.');
            redirect(site_url('finance/settings'));
        }
    
        $existing_data = $this->finance->get_all();
        $view = [
            'existing_data' => $existing_data,
        ];
        $this->_render('Finance Settings', 'finance/settings/manage', $view);
    }  
}