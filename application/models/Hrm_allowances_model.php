<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hrm_allowances_model extends CI_Model
{
    protected $table = 'hrm_allowances';
    protected $employee_allowances_table = 'hrm_employee_allowances';

    /* ==============================
     * GETTERS
     * ============================== */

    public function get_all($active_only = false)
    {
        $this->db->where('deleted_at', null);
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        return $this->db->order_by('created_at', 'DESC')
                        ->get($this->table)
                        ->result_array();
    }

    public function get_all_active()
    {
        return $this->db->where('is_active', 1)
                        ->where('deleted_at', null)
                        ->get($this->table)
                        ->result_array();
    }

    public function get($id)
    {
        return $this->db->get_where(
            $this->table,
            ['id' => $id, 'deleted_at' => null]
        )->row_array();
    }

    /* ==============================
     * INSERT / UPDATE / DELETE
     * ============================== */

    public function insert($data)
    {
        // Extract employee_ids before insert
        $employee_ids = $data['employee_ids'] ?? null;
        unset($data['employee_ids']);

        $this->db->insert($this->table, $data);
        $allowance_id = $this->db->insert_id();

        if (!empty($employee_ids)) {
            $this->assign_employees($allowance_id, $employee_ids);
        }

        return $allowance_id;
    }

    public function update($id, $data)
    {
        // Extract employee_ids before update
        $employee_ids = $data['employee_ids'] ?? null;
        unset($data['employee_ids']);

        $this->db->where('id', $id)->update($this->table, $data);

        if (is_array($employee_ids)) {
            $this->update_employee_assignments($id, $employee_ids);
        }

        return $this->db->affected_rows();
    }

    public function delete($id)
    {
        return $this->update($id, [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /* ==============================
     * EMPLOYEE ASSIGNMENTS
     * ============================== */

    public function assign_employees($allowance_id, $employee_ids)
    {
        $batch = [];
        foreach ($employee_ids as $employee_id) {
            $batch[] = [
                'allowance_id' => $allowance_id,
                'employee_id'  => $employee_id,
                'created_at'   => date('Y-m-d H:i:s')
            ];
        }
        return !empty($batch)
            ? $this->db->insert_batch($this->employee_allowances_table, $batch)
            : true;
    }

    public function update_employee_assignments($allowance_id, $employee_ids)
    {
        $this->db->where('allowance_id', $allowance_id)
                 ->delete($this->employee_allowances_table);

        if (!empty($employee_ids)) {
            return $this->assign_employees($allowance_id, $employee_ids);
        }
        return true;
    }

    public function get_employees_for_allowance($allowance_id)
    {
        $rows = $this->db->select('employee_id')
                         ->from($this->employee_allowances_table)
                         ->where('allowance_id', $allowance_id)
                         ->get()
                         ->result_array();
        return array_column($rows, 'employee_id');
    }

    public function get_employee_allowances($employee_id)
    {
        $this->db->select('a.*')
                 ->from($this->table . ' a')
                 ->join($this->employee_allowances_table . ' ea', 'a.id = ea.allowance_id')
                 ->where('a.deleted_at', null)
                 ->where('a.is_active', 1)
                 ->where('ea.employee_id', $employee_id);

        return $this->db->get()->result_array();
    }
}
