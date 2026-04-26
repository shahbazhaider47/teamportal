<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hrm_employee_exits_model extends CI_Model
{
    protected $table = 'hrm_employee_exits';

    public function insert($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    public function get_latest_by_user($user_id)
    {
        return $this->db->where('user_id', (int)$user_id)
            ->order_by('exit_date', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', (int)$id)->limit(1)->get($this->table)->row_array();
    }
    
    
    public function get_by_user($user_id)
    {
        // If multiple rows can exist, this should be deterministic:
        return $this->db->where('user_id', (int)$user_id)
            ->order_by('exit_date', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row_array();
    }
    
    public function exists_for_user($user_id): bool
    {
        return (bool) $this->db->select('id')
            ->where('user_id', (int)$user_id)
            ->limit(1)
            ->get($this->table)
            ->row_array();
    }
}
