<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('load_system_settings_into_config')) {
    function load_system_settings_into_config()
    {
        $CI =& get_instance();

        if (!is_object($CI)) {
            return;
        }

        // Ensure database is available
        if (!isset($CI->db)) {
            $CI->load->database();
        }

        $settings = [];

        // You can later add back caching here manually if needed
        $query = $CI->db->get('system_settings');
        foreach ($query->result() as $row) {
            $settings[$row->key] = $row->value;
        }

        foreach ($settings as $key => $value) {
            $CI->config->set_item($key, $value);
        }

        if (!empty($settings['default_timezone'])) {
            date_default_timezone_set($settings['default_timezone']);
        }
    }
}
