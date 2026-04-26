<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Developers Guide — single-page documentation hub.
 * Loads one self-contained view without the master layout.
 */
class Developers extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->library(['session']);
    }

    public function index()
    {
        // Optional permission check (comment out if public)
        if (function_exists('staff_can')) {
            if (!staff_can('view_global', 'developers') && !staff_can('view_own', 'developers')) {
                $this->render_403_and_exit(); return;
            }
        }

        // No master layout — render single-file docs view
        $this->load->view('developers/index');
    }
}
