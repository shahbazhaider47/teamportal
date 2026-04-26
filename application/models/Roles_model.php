<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Roles_model
 *
 * Lean role registry (no permissions column).
 * Provides helpers for listing roles and computing assigned user counts.
 */
class Roles_model extends CI_Model
{
    /** @var string */
    protected $table = 'roles';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get list of roles (rows), sorted by role_name ASC.
     * Each row at least contains: role_name
     *
     * @return array<int,array{role_name:string}>
     */
    public function get_all_roles(): array
    {
        return $this->db
            ->select('role_name, description')
            ->from($this->table)
            ->where_not_in('LOWER(role_name)', ['superadmin']) // exclude both roles (case-insensitive)
            ->order_by('role_name', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Get roles with user counts, using users.user_role as the binding.
     * Assumes your users table stores the role name in `user_role`.
     *
     * @return array<int,array{role_name:string,user_count:int}>
     */
    public function get_roles_with_user_counts(): array
    {
        // Subquery (or LEFT JOIN with aggregation) to count users per role
        $sub = $this->db
            ->select('LOWER(COALESCE(user_role, "")) AS rname, COUNT(*) AS cnt', false)
            ->from('users')
            ->group_by('LOWER(COALESCE(user_role, ""))')
            ->get_compiled_select();

        // LEFT JOIN roles with the aggregated counts
        $sql = "
            SELECT r.role_name, r.description,
                   COALESCE(u.cnt, 0) AS user_count
            FROM {$this->db->dbprefix($this->table)} r
            LEFT JOIN ({$sub}) u
              ON LOWER(r.role_name) = u.rname
            ORDER BY r.role_name ASC
        ";

        return $this->db->query($sql)->result_array();
    }

    /**
     * Check if a role exists.
     */
    public function role_exists(string $role_name): bool
    {
        return $this->db
            ->where('role_name', $role_name)
            ->count_all_results($this->table) > 0;
    }

    /**
     * Delete a role (use with caution if users reference it).
     */
    public function delete_role(string $role_name): bool
    {
        return (bool) $this->db
            ->where('role_name', $role_name)
            ->delete($this->table); 
    }

    /**
     * Create a role (simple helper).
     */
    public function create_role(string $role_name, ?string $description = null): bool
    {
        if ($this->role_exists($role_name)) {
            return true; // idempotent
        }
    
        $payload = [
            'role_name'   => $role_name,
            'description' => $description,            // <-- now correctly used
            'created_at'  => date('Y-m-d H:i:s'),
        ];
    
        return (bool) $this->db->insert($this->table, $payload);
    }


    /**
     * Rename a role.
     */
    public function rename_role(string $old_name, string $new_name): bool
    {
        if ($old_name === $new_name) return true;
        if ($this->role_exists($new_name)) return false;

        return (bool) $this->db
            ->where('role_name', $old_name)
            ->update($this->table, [
                'role_name'  => $new_name,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Generic updater for a role row (by role_name)
     * Example payload: ['description' => '...', 'updated_at' => ...]
     */
    public function update_role(string $role_name, array $data): bool
    {
        if (empty($data)) return true;
        return (bool) $this->db
            ->where('role_name', $role_name)
            ->update($this->table, $data);
    }
    


    public function count_users_for_role(string $role_name): int
    {
        return (int) $this->db
            ->from('users')
            ->where('LOWER(user_role)', strtolower($role_name))
            ->count_all_results();
    }

    
}

