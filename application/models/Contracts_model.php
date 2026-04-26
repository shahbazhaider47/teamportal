<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Contracts_model extends CI_Model
{
    protected $table = 'staff_contracts';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get list of contracts with optional filters.
     *
     * @param array $filters ['user_id' => int, 'status' => string]
     * @return array
     */
    public function get_all(): array
    {
        $this->db->from($this->table . ' c');
    
        // JOIN users
        $this->db->join('users u', 'u.id = c.user_id', 'left');
    
        // JOIN departments
        $this->db->join('departments d', 'd.id = u.emp_department', 'left');
    
        // Exclude soft-deleted
        $this->db->where('c.deleted_at IS NULL', null, false);
    
        // Select fields
        $this->db->select('
            c.*,
            u.fullname,
            u.emp_id,
            u.emp_title,
            u.emp_department,
            d.name AS department_name
        ');
    
        $this->db->order_by('c.created_at', 'DESC');
    
        return $this->db->get()->result_array();
    }

    /**
     * Get single contract by ID (optionally with user).
     */
    public function get(int $id): ?array
    {
        $this->db->from($this->table . ' c');
    
        // Join user
        $this->db->join('users u', 'u.id = c.user_id', 'left');
    
        // Join departments for readable department name
        $this->db->join('departments d', 'd.id = u.emp_department', 'left');
    
        $this->db->where('c.id', $id);
        $this->db->where('c.deleted_at IS NULL', null, false);
    
        // Select all required fields + department name
        $this->db->select('
            c.*,
            u.fullname,
            u.firstname,
            u.lastname,
            u.emp_id,
            u.emp_title,
            u.emp_department,
            u.emp_joining,
            u.employment_type,
            u.current_salary,
            d.name AS department_name
        ');
    
        $query  = $this->db->get();
        $result = $query->row_array();
    
        return $result ?: null;
    }


    /**
     * Insert new contract.
     */
    public function create(array $data): int
    {
        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    /**
     * Update contract by ID.
     */
    public function update(int $id, array $data): bool
    {
        $this->db->where('id', $id);
        return (bool)$this->db->update($this->table, $data);
    }

    /**
     * Soft delete contract.
     */
    public function soft_delete(int $id, int $deleted_by): bool
    {
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deleted_by,
        ];
        $this->db->where('id', $id);
        return (bool)$this->db->update($this->table, $data);
    }

    /**
     * Mark contract as expired.
     */
    public function mark_expired(int $id): bool
    {
        $data = [
            'status'     => 'expired',
            'expired_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->where('id', $id);
        return (bool)$this->db->update($this->table, $data);
    }

    /**
     * Simple renew logic:
     * - sets status = 'renewed'
     * - bumps last_renew_at
     * - optionally updates end_date / renew_at
     */
    public function renew(int $id, array $payload = []): bool
    {
        $data = [
            'status'        => 'renewed',
            'last_renew_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($payload['end_date'])) {
            $data['end_date'] = $payload['end_date'];
        }

        if (!empty($payload['renew_at'])) {
            $data['renew_at'] = $payload['renew_at'];
        }

        if (!empty($payload['notice_period_days'])) {
            $data['notice_period_days'] = (int)$payload['notice_period_days'];
        }

        $this->db->where('id', $id);
        return (bool)$this->db->update($this->table, $data);
    }


    /**
     * Mark contract as sent for signature.
     *
     * @param int   $id
     * @param array $payload  optional overrides, e.g. ['status' => 'sent']
     * @return bool
     */
    public function send_for_sign(int $id, array $payload = []): bool
    {
        $data = [
            'sent_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($payload['status'])) {
            $data['status'] = $payload['status'];
        }

        $this->db->where('id', $id);
        return (bool)$this->db->update($this->table, $data);
    }
    


    public function get_latest_for_user(int $user_id)
    {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return null;
        }
    
        $row = $this->db
            ->select('id')
            ->from('staff_contracts')
            ->where('user_id', $user_id)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('start_date', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row();
    
        if (!$row || empty($row->id)) {
            return null;
        }
    
        return $this->get((int)$row->id); // reuse your existing get()
    }
    


/**
 * Get all contracts for a specific user (schema-safe).
 *
 * @param int $user_id
 * @return array
 */
public function get_by_user(int $user_id): array
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return [];
    }

    return $this->db
        ->select('
            id,
            user_id,
            contract_type,
            version,
            start_date,
            end_date,
            contract_file,
            status,
            sent_at,
            signed_at,
            expired_at,
            renew_at,
        ')
        ->from($this->table)
        ->where('user_id', $user_id)
        ->where('deleted_at IS NULL', null, false)
        ->order_by('start_date', 'DESC')
        ->order_by('id', 'DESC')
        ->get()
        ->result_array();
}


    
}
