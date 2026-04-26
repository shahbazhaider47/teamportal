<?php defined('BASEPATH') OR exit('No direct script access allowed');

class UserPermissions extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_permissions_model', 'userperms');
        $this->config->load('default_user_permissions', TRUE);
    }

    // POST handler: grants[], denies[], user_id
    public function save()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Method Not Allowed', 405);
        }

        $user_id = (int) $this->input->post('user_id');
        if ($user_id <= 0) show_error('Invalid user', 400);

        $grants = $this->input->post('grants', true) ?: $this->input->post('settings')['grants'] ?? [];
        $denies = $this->input->post('denies', true) ?: $this->input->post('settings')['denies'] ?? [];

        // normalize to canonical "module:action"
        $norm = function ($s) {
            $s = preg_replace('/\s*:\s*/', ':', (string)$s);
            return strtolower(trim($s));
        };
        $g = [];
        foreach ((array)$grants as $x) { $k = $norm($x); if ($k !== '') $g[$k]=true; }
        $d = [];
        foreach ((array)$denies as $x) { $k = $norm($x); if ($k !== '') $d[$k]=true; }

        $ok = $this->userperms->save($user_id, array_keys($g), array_keys($d));

        if ((int)$this->session->userdata('user_id') === $user_id) {
            refresh_current_user_permissions_cache();
        }
        set_alert($ok ? 'success' : 'danger', $ok ? 'User permissions updated.' : 'Failed to update user permissions.');
        redirect('users/profile/'.$user_id);
    }

    // Optional: endpoint to (re)apply defaults to a user with no row
    public function apply_defaults($user_id)
    {
        $user_id = (int)$user_id;
        if ($user_id <= 0) show_error('Invalid user', 400);

        $defaults = $this->config->item('default_user_permissions') ??
                    (include(APPPATH.'config/default_user_permissions.php'));
        $this->userperms->apply_defaults_if_missing($user_id, (array)$defaults);
        redirect('users/profile/'.$user_id);
    }
}
