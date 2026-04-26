<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Hrm_employee_rejoins_model extends CI_Model
{
    protected $table = 'hrm_employee_rejoins';

    public function __construct()
    {
        parent::__construct();
    }

    public function add(array $data): bool
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        return (bool) $this->db->insert($this->table, $data);
    }

    public function get_by_user(int $user_id): array
    {
        return $this->db->where('user_id', $user_id)
                        ->order_by('id', 'DESC')
                        ->get($this->table)
                        ->result_array();
    }
}