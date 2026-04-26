<?php defined('BASEPATH') or exit('No direct script access allowed');

class Reports extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Reports_model', 'reports');
    }

    /**
     * Reports landing page
     */
    public function index()
    {

        if (! staff_can('view_global','reports')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $layout_data = [
            'page_title' => 'Generate Reports',
            'subview'    => 'reports/index',
            'view_data'  => [],
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Universal Report Viewer
     * URL: /reports/{group}/{report}/report_view
     */
    public function report_view($group = null, $report = null)
    {
        if (!$group || !$report) {
            show_404();
        }

        if (! staff_can('view_global','reports')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        // 🔹 Ask model to resolve the report
        $reportData = $this->reports->resolve_report($group, $report);

        if (!$reportData) {
            show_404();
        }

        $layout_data = [
            'page_title' => $reportData['title'],
            'subview'    => 'reports/report_view',
            'view_data'  => $reportData,
        ];

        $this->load->view('layouts/master', $layout_data);
    }
}
