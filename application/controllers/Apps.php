<?php defined('BASEPATH') or exit('No direct script access allowed');

class Apps extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        // If needed later:
        // $this->guard_logged_in();
    }

    /**
     * Apps hub – just load the main apps view.
     */
    public function index()
    {
        // If you don't need to pass any dynamic data yet:
        $view_data = []; // you can fill later if needed

        $layout_data = [
            'page_title' => 'Apps',
            'subview'    => 'apps/index', // application/views/apps/index.php
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }
}
