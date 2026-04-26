<?php
// File: application/models/Dashboard_model.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        // Ensure the database library is loaded (belt-and-suspenders)
        if (! isset($this->db) || $this->db === null) {
            $this->load->database();
        }
    }

    /**
     * Generate a stable widget ID from widget name and container.
     *
     * @param string $widget_name   (e.g. 'sample_widget')
     * @param string $container_id  (e.g. 'top-left')
     * @return string  MD5 hash like 'd41d8cd98f00b204e9800998ecf8427e'
     */
    public static function generate_widget_id($widget_name, $container_id)
    {
        return md5("{$container_id}_{$widget_name}");
    }


}
