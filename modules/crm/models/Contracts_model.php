<?php defined('BASEPATH') or exit('No direct script access allowed');

class Contracts_model extends App_Model
{
    protected $table = 'crm_contracts';

    /* =========================================================
     * READ
     * ======================================================= */

    /**
     * All non-deleted contracts with client name joined.
     */
    public function get_all(): array
    {
        $this->db->select('c.*, cl.practice_name AS client_name, cl.client_code')
                 ->from($this->table . ' c')
                 ->join('crm_clients cl', 'cl.id = c.client_id', 'left')
                 ->where('c.deleted_at IS NULL', null, false)
                 ->order_by('c.created_at', 'DESC');
    
        return $this->db->get()->result_array();
    }

    /**
     * Single contract with client name.
     */
    public function get(int $id): ?array
    {
        $row = $this->db
            ->select('c.*, cl.practice_name AS client_name, cl.client_code, cl.primary_contact_name, cl.primary_email')
            ->from($this->table . ' c')
            ->join('crm_clients cl', 'cl.id = c.client_id', 'left')
            ->where('c.id', $id)
            ->where('c.deleted_at IS NULL', null, false)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    /**
     * All versions (amendments/renewals) of a contract chain.
     */
    public function get_versions(int $parentId): array
    {
        return $this->db
            ->where('parent_contract_id', $parentId)
            ->or_where('id', $parentId)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('contract_version', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    /**
     * Contracts expiring within $days days (for alerts/dashboard).
     */
    public function get_expiring(int $days = 60): array
    {
        return $this->db
            ->select('c.*, cl.practice_name AS client_name')
            ->from($this->table . ' c')
            ->join('crm_clients cl', 'cl.id = c.client_id', 'left')
            ->where('c.status', 'active')
            ->where('c.end_date IS NOT NULL', null, false)
            ->where('c.end_date >=', date('Y-m-d'))
            ->where('c.end_date <=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('c.deleted_at IS NULL', null, false)
            ->order_by('c.end_date', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Contracts for a specific client (used in client profile tabs).
     */
    public function get_by_client(int $clientId): array
    {
        return $this->db
            ->where('client_id', $clientId)
            ->where('deleted_at IS NULL', null, false)
            ->order_by('created_at', 'DESC')
            ->get($this->table)
            ->result_array();
    }

    /**
     * Count contracts by status (for dashboard KPIs).
     */
    public function count_by_status(): array
    {
        $rows = $this->db
            ->select('status, COUNT(*) as total')
            ->where('deleted_at IS NULL', null, false)
            ->group_by('status')
            ->get($this->table)
            ->result_array();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int)$row['total'];
        }
        return $counts;
    }

    /* =========================================================
     * WRITE
     * ======================================================= */

    public function insert(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $id);
        return (bool)$this->db->update($this->table, $data);
    }

    /**
     * Soft delete — keeps record for audit trail.
     */
    public function soft_delete(int $id, int $userId): bool
    {
        $this->db->where('id', $id);
        return (bool)$this->db->update($this->table, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $userId,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);
    }

    /**
     * Restore a soft-deleted contract.
     */
    public function restore(int $id): bool
    {
        $this->db->where('id', $id);
        return (bool)$this->db->update($this->table, [
            'deleted_at' => null,
            'deleted_by' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if a contract_code already exists (used in controller validation).
     */
    public function number_exists(string $number, int $excludeId = 0): bool
    {
        $this->db->where('contract_code', $number)
                 ->where('deleted_at IS NULL', null, false);

        if ($excludeId > 0) {
            $this->db->where('id !=', $excludeId);
        }

        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Generate a unique contract number: CNT-YYYY-XXXX
     */
    public function generate_contract_code(): string
    {
        $year  = date('Y');
        $count = $this->db
            ->like('contract_code', 'CNT-' . $year . '-', 'after')
            ->count_all_results($this->table);

        $seq = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return 'CNT-' . $year . '-' . $seq;
    }
}