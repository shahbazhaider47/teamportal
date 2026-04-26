<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'third_party/MX/Controller.php';

class App_Controller extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');

        // CLI-safe: skip web-only concerns
        if (is_cli()) {
            return;
        }
        $this->load->helper(['url', 'html', 'hierarchy']);
        $this->config->load('hierarchy');
        $this->load->library('ai/Todo_ai_helper', [], 'todo_ai');

        // Set default timezone from settings (if present)
        if (function_exists('get_setting')) {
            $timezone = get_setting('default_timezone');
            if (!empty($timezone)) {
                date_default_timezone_set($timezone);
            }
        }

        // ------------------------------------
        // Global view variables
        // ------------------------------------
        $year = date('Y');

        $this->config->load('app', true);
        $ver = (string) $this->config->item('app_version', 'app');

        $companyName = 'Company Name';
        if (function_exists('get_setting')) {
            $maybeName = get_setting('company_name');
            if (!empty($maybeName)) {
                $companyName = $maybeName;
            }
        }

        $this->load->vars([
            'year'         => $year,
            'app_version'  => $ver ?: 'dev',
            'company_name' => $companyName,
        ]);

        // ------------------------------------
        // AUTH SYSTEM (FINAL CLEAN VERSION)
        // ------------------------------------
        $controller = strtolower($this->router->fetch_class());
        $method     = strtolower($this->router->fetch_method());

        // Auth controller allowed methods
        $public_auth_methods = [
            'login',
            'register',
            'social_login',
            'forgot_password',
            'reset_password',
            'logout',
        ];

        // 👇 PUBLIC CONTROLLERS (NO LOGIN REQUIRED)
        $public_controllers = [
            'public_leads',   // ✅ your embedded form
        ];

        // Determine public access
        $is_public = (
            ($controller === 'authentication' && in_array($method, $public_auth_methods, true))
            || in_array($controller, $public_controllers, true)
        );

        // 🔒 Enforce login ONLY if not public
        if (!$is_public && !$this->session->userdata('is_logged_in')) {
            $this->session->set_userdata('redirect_url', current_url());
            redirect('authentication/login', 'refresh');
            exit;
        }

        // ------------------------------------
        // Active user enforcement
        // ------------------------------------
        if ($this->session->userdata('is_logged_in')) {

            $userId = (int) $this->session->userdata('user_id');

            if ($userId > 0 && $this->db->table_exists('users')) {

                $user = $this->db
                    ->select('id, is_active')
                    ->from('users')
                    ->where('id', $userId)
                    ->limit(1)
                    ->get()
                    ->row_array();

                if (empty($user) || (int)$user['is_active'] !== 1) {

                    $this->session->sess_destroy();

                    $this->session->set_flashdata(
                        'error',
                        'Your account has been deactivated. Please contact the administrator.'
                    );

                    redirect('authentication/login', 'refresh');
                    exit;
                }
            }
        }
    }
    

    protected function _get_user_scope(int $user_id = 0): array
    {
        if ($user_id === 0) {
            $user_id = (int)$this->session->userdata('user_id');
        }
        $user = $this->User_model->get_user_by_id($user_id);
        $role = strtolower($user['user_role'] ?? 'employee');
    
        return [
            'user_id'    => $user_id,
            'role'       => $role,
            'weight'     => hierarchy_weight($role),
            'scope'      => hierarchy_scope($role),
            'team_id'    => (int)($user['emp_team']       ?? 0),
            'dept_id'    => (int)($user['emp_department']  ?? 0),
            'can_view'   => hierarchy_visible_roles($role),
            'can_manage' => hierarchy_manageable_roles($role),
        ];
    }

}