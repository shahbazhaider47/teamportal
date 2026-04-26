<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Department_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ===== Basic CRUD ===== */

    public function get_all()
    {
        return $this->db->order_by('name', 'ASC')
                        ->get('departments')
                        ->result_array();
    }

    /**
     * Departments + staff count and HOD info (optional).
     * - staff_count = COUNT(users.id) where users.emp_department = departments.id
     * - hod_name / hod_profile_image joined from users (if hod is set)
     */
    public function get_all_with_stats()
    {
        return $this->db->select([
                            'd.*',
                            'COUNT(u.id) AS staff_count',
                            "CONCAT(hod_u.firstname, ' ', hod_u.lastname) AS hod_name",
                            'hod_u.profile_image AS hod_profile_image'
                        ])
                        ->from('departments d')
                        ->join('users u', 'u.emp_department = d.id', 'left')     // one-to-many
                        ->join('users hod_u', 'hod_u.id = d.hod', 'left')        // HOD user (optional)
                        ->group_by('d.id')
                        ->order_by('d.name', 'ASC')
                        ->get()
                        ->result_array();
    }

    public function get($id)
    {
        return $this->db->get_where('departments', ['id' => (int)$id])->row_array();
    }

    public function insert(array $data): int
    {
        $this->db->insert('departments', $data);
    
        $insertId = (int) $this->db->insert_id();
    
        return $insertId;
    }

    public function update($id, $data)
    {
        return $this->db->where('id', (int)$id)
                        ->update('departments', $data);
    }

    public function delete($id)
    {
        // If your controller checks has_staff() before delete, this can remain as-is.
        return $this->db->where('id', (int)$id)
                        ->delete('departments');
    }

    /* ===== Staff lookups (one-to-many via users.emp_department) ===== */

    public function get_users($department_id)
    {
        return $this->db->select('id, firstname, lastname, email, profile_image, user_role')
                        ->from('users')
                        ->where('emp_department', (int)$department_id)
                        ->order_by('firstname', 'ASC')
                        ->get()
                        ->result_array();
    }

    // Assign a user to a department (one department per user)
    public function assign_staff($department_id, $user_id)
    {
        return $this->db->where('id', (int)$user_id)
                        ->update('users', ['emp_department' => (int)$department_id]);
    }

    // Remove a user from a department (clear FK only if matches)
    public function remove_staff($department_id, $user_id)
    {
        return $this->db->where('id', (int)$user_id)
                        ->where('emp_department', (int)$department_id)
                        ->update('users', ['emp_department' => null]);
    }

    // Is this user currently assigned to this department?
    public function is_staff_assigned($department_id, $user_id)
    {
        return $this->db->where('id', (int)$user_id)
                        ->where('emp_department', (int)$department_id)
                        ->count_all_results('users') > 0;
    }

    // Does this department have any staff?
    public function has_staff($department_id)
    {
        return $this->db->where('emp_department', (int)$department_id)
                        ->count_all_results('users') > 0;
    }

    // Return the department(s) for a given user (here it’s at most one)
    public function get_staff_departments($user_id)
    {
        return $this->db->select('d.id, d.name')
                        ->from('departments d')
                        ->join('users u', 'u.emp_department = d.id', 'inner')
                        ->where('u.id', (int)$user_id)
                        ->get()
                        ->result_array();
    }

    public function get_all_departments()
    {
        return $this->db->get('departments')->result_array();
    }
}
