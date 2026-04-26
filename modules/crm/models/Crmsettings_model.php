<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crmsettings_model extends CI_Model
{
    protected $table = 'system_settings';

    /**
     * Get all CRM settings as key => value
     */
    public function get_all()
    {
        $this->db->where('group_key', 'crm');
        $rows = $this->db->get($this->table)->result_array();

        $data = [];
        foreach ($rows as $row) {
            $data[$row['key']] = $row['value'];
        }

        return $data;
    }

    /**
     * Save multiple settings (insert/update)
     */
    public function save($settings = [])
    {
        if (empty($settings)) return false;

        foreach ($settings as $key => $value) {

            $exists = $this->db
                ->where('group_key', 'crm')
                ->where('key', $key)
                ->get($this->table)
                ->row();

            $data = [
                'value'      => is_array($value) ? json_encode($value) : $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($exists) {
                $this->db->where('id', $exists->id)->update($this->table, $data);
            } else {
                $data['key']        = $key;
                $data['group_key']  = 'crm';
                $data['created_at'] = date('Y-m-d H:i:s');

                $this->db->insert($this->table, $data);
            }
        }

        return true;
    }
}