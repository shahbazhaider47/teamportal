<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hrm_positions_model extends CI_Model
{
    protected $table = 'hrm_positions';

    public function __construct()
    {
        parent::__construct();
    }

    public function get_all_with_stats()
    {
        return $this->db->get($this->table)->result_array();
    }

    public function get_all()
    {
        $this->db->select('p.*, d.name as department_name');
        $this->db->from($this->table . ' p');
        $this->db->join('departments d', 'p.department_id = d.id', 'left');
        return $this->db->order_by('p.created_at', 'DESC')->get()->result_array();
    }

    public function get($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row_array();
    }

    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    public function get_all_positions()
    {
    
    return $this->db->order_by('title', 'asc')->get('hrm_positions')->result_array();
    
        
    }


    public function get_title_by_id($id)
    {
        if (empty($id)) return null;
        $row = $this->db->select('title')->get_where($this->table, ['id' => (int)$id])->row_array();
        return $row['title'] ?? null;
    }


    public function get_all_with_departments(): array
    {
        // Adjust table names if yours differ (e.g., hrm_departments)
        return $this->db
            ->select('p.*, d.name AS department_name')
            ->from('hrm_positions p')
            ->join('departments d', 'd.id = p.department_id', 'left')
            ->order_by('p.title', 'ASC')
            ->get()
            ->result_array();
    }

}
