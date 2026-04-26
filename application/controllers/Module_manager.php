<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Module_manager extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('App_modules', null, 'moduleloader');
    }

    /**
     * Display all modules with metadata and activation state
     */
    public function index()
    {
        $data['modules'] = $this->moduleloader->get_all_modules(); // ✅ Merged DB + filesystem
        $data['title']   = 'Manage Modules';

        $this->load->view('layouts/master', [
            'subview'   => 'modules/manage',
            'view_data' => $data,
        ]);
    }

    /**
     * Activate a module by slug
     *
     * @param string $slug
     */
    public function activate($slug)
    {
        if (!$slug || !$this->moduleloader->get($slug)) {
            show_404();
        }

        try {
            $this->moduleloader->activate($slug);
            set_alert('success', ucfirst($slug) . ' module activated successfully.');
        } catch (Throwable $e) {
            log_message('error', 'Module activation failed: ' . $e->getMessage());
            set_alert('danger', 'Failed to activate ' . $slug . ' module.');
        }

        redirect(base_url('module_manager/index'));
    }

    /**
     * Deactivate a module by slug
     *
     * @param string $slug
     */
    public function deactivate($slug)
    {
        if (!$slug || !$this->moduleloader->get($slug)) {
            show_404();
        }

        try {
            $this->moduleloader->deactivate($slug);
            set_alert('success', ucfirst($slug) . ' module deactivated successfully.');
        } catch (Throwable $e) {
            log_message('error', 'Module deactivation failed: ' . $e->getMessage());
            set_alert('danger', 'Failed to deactivate ' . $slug . ' module.');
        }

        redirect(base_url('module_manager/index'));
    }

    /**
     * Delete and uninstall a module
     *
     * @param string $slug
     */
    public function delete($slug)
    {
        if (!$slug || !$this->moduleloader->get($slug)) {
            show_404();
        }

        try {
            $this->moduleloader->uninstall($slug);
            set_alert('success', ucfirst($slug) . ' module deleted successfully.');
        } catch (Throwable $e) {
            log_message('error', 'Module deletion failed: ' . $e->getMessage());
            set_alert('danger', 'Failed to delete ' . $slug . ' module.');
        }

        redirect(base_url('module_manager/index'));
    }
}
