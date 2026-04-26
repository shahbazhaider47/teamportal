<?php defined('BASEPATH') or exit('No direct script access allowed');

class Do_later_model extends CI_Model
{
    protected $table = 'do_later_tasks';

    public function get_all()
    {
        return $this->db
            ->order_by('priority', 'DESC')
            ->order_by('created_at', 'DESC')
            ->get($this->table)
            ->result_array();
    }

    public function insert(array $data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, array $data)
    {
        return $this->db
            ->where('id', (int)$id)
            ->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db
            ->where('id', (int)$id)
            ->delete($this->table);
    }

    public function get($id)
    {
        return $this->db
            ->where('id', (int)$id)
            ->get($this->table)
            ->row_array();
    }
}
