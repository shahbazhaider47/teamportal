<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retrieve all settings data for a given $group_key.
     *
     * @param string $group_key
     * @return array
     */
    public function get_group($group_key)
    {
        switch ($group_key) {
            case 'general':
                $this->load->model('Company_info_model');
                return $this->Company_info_model->get_all_values();

            case 'permissions':
                $this->load->model('Role_permissions_model');
                $roles = $this->Role_permissions_model->get_all_roles();
                $ret = [];
                foreach ($roles as $role) {
                    $ret[$role] = $this->Role_permissions_model->get_permissions_by_role($role);
                }
                return $ret;

            default:
                // For system, other, pusher, and module groups
                $this->load->model('System_settings_model');
                return $this->System_settings_model->get_all($group_key);
        }
    }

    /**
     * Save settings data for a given $group_key.
     *
     * @param string $group_key
     * @param array $data
     * @return bool
     */
    public function save_group($group_key, array $data)
    {
        switch ($group_key) {
            case 'general':
                $this->load->model('Company_info_model');
                return $this->Company_info_model->upsert($data);

            case 'permissions':
                $this->load->model('Role_permissions_model');
                $ok = true;
                if (isset($data['permissions']) && is_array($data['permissions'])) {
                    foreach ($data['permissions'] as $role => $perms) {
                        if (! $this->Role_permissions_model->save_permissions($role, $perms)) {
                            $ok = false;
                        }
                    }
                }
                return $ok;

            default:
                // For system, other, pusher, or any module settings
                $this->load->model('System_settings_model');
                return $this->System_settings_model->upsert($data, $group_key);
        }
    }

    /**
     * Optional: Get a single setting value from a specific group.
     *
     * @param string $group_key
     * @param string $option_key
     * @return mixed|null
     */
    public function get_setting($group_key, $option_key)
    {
        $this->load->model('System_settings_model');
        return $this->System_settings_model->get($option_key, $group_key);
    }
}
