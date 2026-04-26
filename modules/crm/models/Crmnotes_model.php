<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crmnotes_model extends CI_Model
{
    protected $table = 'crm_notes';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($id)
    {
        return $this->db
            ->where('id', (int)$id)
            ->get($this->table)
            ->row_array();
    }

    public function get_by_rel($relType, $relId)
    {
        return $this->db
            ->select('n.*, u.firstname, u.lastname, u.fullname')
            ->from($this->table . ' n')
            ->join('users u', 'u.id = n.user_id', 'left')
            ->where('n.rel_type', trim((string)$relType))
            ->where('n.rel_id', (int)$relId)
            ->order_by('n.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function insert($data)
    {
        $insert = [
            'rel_type'   => trim((string)($data['rel_type'] ?? '')),
            'rel_id'     => (int)($data['rel_id'] ?? 0),
            'note'       => trim((string)($data['note'] ?? '')),
            'is_internal'=> isset($data['is_internal']) ? (int)$data['is_internal'] : 1,
            'user_id'    => isset($data['user_id']) ? (int)$data['user_id'] : null,
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null,
            'updated_by' => isset($data['updated_by']) ? (int)$data['updated_by'] : null,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
        ];

        $this->db->insert($this->table, $insert);
        return (int)$this->db->insert_id();
    }

    public function update($id, $data)
    {
        $update = [
            'note'        => trim((string)($data['note'] ?? '')),
            'is_internal' => isset($data['is_internal']) ? (int)$data['is_internal'] : 1,
            'updated_by'  => isset($data['updated_by']) ? (int)$data['updated_by'] : null,
            'updated_at'  => $data['updated_at'] ?? date('Y-m-d H:i:s'),
        ];

        return $this->db
            ->where('id', (int)$id)
            ->update($this->table, $update);
    }

    public function delete($id)
    {
        return $this->db
            ->where('id', (int)$id)
            ->delete($this->table);
    }

    public function belongs_to($id, $relType, $relId)
    {
        return $this->db
            ->where('id', (int)$id)
            ->where('rel_type', trim((string)$relType))
            ->where('rel_id', (int)$relId)
            ->count_all_results($this->table) > 0;
    }

    public function count_by_rel($relType, $relId)
    {
        return (int)$this->db
            ->where('rel_type', trim((string)$relType))
            ->where('rel_id', (int)$relId)
            ->count_all_results($this->table);
    }
}