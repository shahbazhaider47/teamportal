<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Asset_model extends CI_Model
{
    protected $table         = 'assets';
    protected $type_table    = 'asset_types';
    protected $purchase_table = 'asset_purchases';

    public function __construct() {
        parent::__construct();
    }

    /* ============================
     * Assets Section
     * ============================ */

public function get_all_asset() {
    $this->db->select('
        a.*,
        at.name as asset_type,
        u.firstname, u.lastname,
        d.name as department_name
    ');
    $this->db->from($this->table . ' a');
    $this->db->join($this->type_table . ' at', 'a.type_id = at.id', 'left');
    $this->db->join('users u', 'a.employee_id = u.id', 'left');
    $this->db->join('departments d', 'a.department_id = d.id', 'left');
    $this->db->order_by('a.created_at', 'DESC');
    return $this->db->get()->result_array();
}


public function get_assigned_assets() {
    $this->db->select('
        a.*,
        at.name as asset_type,
        u.firstname, u.lastname,
        d.name as department_name
    ');
    $this->db->from($this->table . ' a');
    $this->db->join($this->type_table . ' at', 'a.type_id = at.id', 'left');
    $this->db->join('users u', 'a.employee_id = u.id AND u.is_active = 1', 'left');
    $this->db->join('departments d', 'a.department_id = d.id', 'left');

    // ✅ Only show if assigned to user OR department
    $this->db->group_start()
             ->where('a.employee_id IS NOT NULL')
             ->or_where('a.department_id IS NOT NULL')
             ->group_end();

    $this->db->order_by('a.created_at', 'DESC');
    return $this->db->get()->result_array();
}

public function get_asset($id) {
    $this->db->select('a.*, at.name as asset_type, u.firstname, u.lastname, d.name as department_name');
    $this->db->from($this->table . ' a');
    $this->db->join($this->type_table . ' at', 'a.type_id = at.id', 'left');
    $this->db->join('users u', 'a.employee_id = u.id', 'left');
    $this->db->join('departments d', 'a.department_id = d.id', 'left');
    $this->db->where('a.id', (int)$id);
    return $this->db->get()->row_array();
}


    public function insert_asset($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update_asset($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update($this->table, $data);
    }

    public function delete_asset($id) {
        $this->db->where('id', (int)$id);
        return $this->db->delete($this->table);
    }

    // Asset types
    public function get_asset_types() {
        return $this->db->order_by('name', 'ASC')->get($this->type_table)->result_array();
    }

    public function add_asset_type($name) {
        $this->db->insert($this->type_table, ['name' => $name]);
        return $this->db->insert_id();
    }

public function get_unassigned_assets() {
    $this->db->select('
        a.*,
        at.name as asset_type,
        u.firstname, u.lastname,
        d.name as department_name
    ');
    $this->db->from($this->table . ' a');
    $this->db->join($this->type_table . ' at', 'a.type_id = at.id', 'left');
    $this->db->join('users u', 'a.employee_id = u.id', 'left');
    $this->db->join('departments d', 'a.department_id = d.id', 'left');

    $this->db->group_start()
        // ✅ Rule 1: Show everything except in-use
        ->where('a.status !=', 'in-use')

        // ✅ Rule 2: OR include in-use assets that are not assigned
        ->or_group_start()
            ->where('a.status', 'in-use')
            ->where('a.employee_id IS NULL')
            ->where('a.department_id IS NULL')
        ->group_end()
    ->group_end();

    $this->db->order_by('a.created_at', 'DESC');
    return $this->db->get()->result_array();
}


    public function get_assets_by_user($user_id) {
        $this->db->select('a.*, at.name AS asset_type, u.firstname, u.lastname');
        $this->db->from($this->table . ' a');
        $this->db->join($this->type_table . ' at', 'a.type_id = at.id', 'left');
        $this->db->join('users u', 'a.employee_id = u.id AND u.is_active = 1', 'left');
        $this->db->where('a.employee_id', (int)$user_id);
        $this->db->order_by('a.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    /* ============================
     * Purchases Section
     * ============================ */

    public function get_all_purchases() {
        $this->db->select('
            p.*,
            at.name AS asset_type,
            u1.firstname AS purchaser_firstname, u1.lastname AS purchaser_lastname,
            u2.firstname AS payment_firstname, u2.lastname AS payment_lastname,
            u3.firstname AS created_firstname, u3.lastname AS created_lastname
        ');
        $this->db->from($this->purchase_table . ' p');
        $this->db->join($this->type_table . ' at', 'p.asset_type_id = at.id', 'left');
        $this->db->join('users u1', 'p.purchased_by = u1.id AND u1.is_active = 1', 'left');
        $this->db->join('users u2', 'p.payment_user = u2.id AND u2.is_active = 1', 'left');
        $this->db->join('users u3', 'p.created_by = u3.id AND u3.is_active = 1', 'left');
        $this->db->order_by('p.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_purchase($id) {
        return $this->db->get_where($this->purchase_table, ['id' => (int)$id])->row_array();
    }

    public function insert_purchase($data) {
        $this->db->insert($this->purchase_table, $data);
        return $this->db->insert_id();
    }

    public function update_purchase($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update($this->purchase_table, $data);
    }

    public function delete_purchase($id) {
        $this->db->where('id', (int)$id);
        return $this->db->delete($this->purchase_table);
    }

    /* ============================
     * Utilities
     * ============================ */

    public function generate_serial_no() {
        $this->db->select('serial_no');
        $this->db->from($this->table);
        $this->db->like('serial_no', 'SR-', 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get()->row();

        if ($row && preg_match('/SR-(\d+)/', $row->serial_no, $matches)) {
            $next = intval($matches[1]) + 1;
        } else {
            $next = 1;
        }
        return sprintf("SR-%05d", $next);
    }

    public function get_available_inventory() {
        $this->db->select('a.*, at.name as asset_type');
        $this->db->from($this->table . ' a');
        $this->db->join($this->type_table . ' at', 'a.type_id = at.id', 'left');
        $this->db->where('a.status', 'available');
        $this->db->order_by('a.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function assign_asset($asset_id, $assign_type, $assign_id) {
        $data = [
            'employee_id'   => null,
            'department_id' => null,
            'status'        => 'in-use',
            'updated_at'    => date('Y-m-d H:i:s')
        ];

        if ($assign_type === 'user') {
            $data['employee_id'] = $assign_id;
        } elseif ($assign_type === 'department') {
            $data['department_id'] = $assign_id;
        }

        $this->db->where('id', (int)$asset_id);
        return $this->db->update($this->table, $data);
    }
}
