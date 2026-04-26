<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crmclients_model extends CI_Model
{
    /* =========================================================
     * TABLE DEFINITIONS
     * ======================================================= */

    protected $clients_table = 'crm_clients';
    protected $groups_table  = 'crm_client_groups';

    protected $pk = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    /* =========================================================
     * CLIENT CRUD
     * ======================================================= */

    public function get_all($onlyActive = true)
    {
        if ($onlyActive) {
            $this->db->where('is_active', 1);
        }
    
        return $this->db
            ->order_by('practice_name ASC')
            ->get($this->clients_table)
            ->result_array();
    }

public function get_all_with_group($onlyActive = true)
{
    if ($onlyActive) {
        $this->db->where('c.is_active', 1);
    }

    return $this->db
        ->select('c.*, g.group_name, g.company_name, g.contact_person, g.contact_email, g.contact_phone')
        ->from($this->clients_table . ' AS c')
        ->join($this->groups_table . ' AS g', 'g.id = c.client_group_id', 'left')
        ->order_by('c.practice_name ASC')
        ->get()
        ->result_array();
}

    public function get($id)
    {
        return $this->db
            ->where($this->pk, (int)$id)
            ->get($this->clients_table)
            ->row_array();
    }

    public function get_by_code($clientCode)
    {
        return $this->db
            ->where('client_code', $clientCode)
            ->get($this->clients_table)
            ->row_array();
    }

    public function insert(array $data)
    {
        $this->normalize_group_flags($data);

        $data['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->clients_table, $data);
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $this->normalize_group_flags($data);

        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db
            ->where($this->pk, (int)$id)
            ->update($this->clients_table, $data);
    }

    public function delete($id)
    {

        return $this->db
            ->where($this->pk, (int)$id)
            ->update($this->clients_table, [
                'is_active'     => 0,
                'client_status' => 'inactive',
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
    }

    /* =========================================================
     * CLIENT BUSINESS HELPERS
     * ======================================================= */

    public function get_active_clients()
    {
        return $this->db
            ->where('client_status', 'active')
            ->where('is_active', 1)
            ->order_by('practice_name')
            ->get($this->clients_table)
            ->result_array();
    }

    public function count_active_clients()
    {
        return $this->db
            ->where('is_active', 1)
            ->count_all_results($this->clients_table);
    }

    public function count_group_clients()
    {
        return $this->db
            ->where('is_group', 1)
            ->where('is_active', 1)
            ->count_all_results($this->clients_table);
    }

    public function get_clients_for_billing()
    {
        return $this->db
            ->where('client_status', 'active')
            ->where('is_active', 1)
            ->get($this->clients_table)
            ->result_array();
    }

    public function is_client_code_unique($clientCode, $excludeId = null)
    {
        $this->db->where('client_code', $clientCode);

        if ($excludeId) {
            $this->db->where($this->pk . ' !=', (int)$excludeId);
        }

        return $this->db->count_all_results($this->clients_table) === 0;
    }

    /* =========================================================
     * CLIENT GROUPS (THIRD PARTIES)
     * ======================================================= */

    public function get_groups($onlyActive = true)
    {
        if ($onlyActive) {
            $this->db->where('status', 'active');
        }

        return $this->db
            ->order_by('group_name ASC')
            ->get($this->groups_table)
            ->result_array();
    }

public function get_group($id)
{
    return $this->db
        ->select('g.*, 
                  uc.fullname AS created_by_name,
                  ua.fullname AS deleted_by_name,                  
                  uu.fullname AS updated_by_name')
        ->from($this->groups_table . ' g')
        ->join('users uc', 'uc.id = g.created_by',  'left')
        ->join('users ua', 'ua.id = g.deleted_by',  'left')        
        ->join('users uu', 'uu.id = g.updated_by',  'left')
        ->where('g.' . $this->pk, (int)$id)
        ->get()
        ->row_array();
}

    public function insert_group(array $data)
    {
        $this->db->insert($this->groups_table, $data);
        return $this->db->insert_id();
    }

    public function update_group($id, array $data)
    {
        return $this->db
            ->where($this->pk, (int)$id)
            ->update($this->groups_table, $data);
    }

    public function delete_group($id)
    {
        // Soft disable group
        return $this->db
            ->where($this->pk, (int)$id)
            ->update($this->groups_table, ['status' => 'inactive']);
    }

    /* =========================================================
     * INTERNAL UTILITIES
     * ======================================================= */

    /**
     * Ensures is_group + client_group_id consistency
     */
    public function normalize_group_flags(array &$data): void
    {
        if (!empty($data['client_group_id'])) {
            $data['is_group'] = 1;
        } else {
            $data['is_group'] = 0;
            $data['client_group_id'] = null;
        }
    }

public function has_active_clients_in_group(int $groupId): bool
{
    return $this->db
        ->where('client_group_id', $groupId)
        ->where('is_active', 1)
        ->count_all_results($this->clients_table) > 0;
}

public function get_groups_with_clients_count(bool $onlyActiveGroups = false): array
{
    if ($onlyActiveGroups) {
        $this->db->where('g.status', 'active');
    }

    return $this->db
        ->select('
            g.*,
            COUNT(c.id) AS clients_total,
            SUM(CASE WHEN c.is_active = 1 THEN 1 ELSE 0 END) AS clients_active
        ')
        ->from($this->groups_table . ' g')
        ->join($this->clients_table . ' c', 'c.client_group_id = g.id', 'left')
        ->group_by('g.id')
        ->order_by('g.group_name ASC')
        ->get()
        ->result_array();
}


public function get_groups_kpi(): array
{
    $rows = $this->db
        ->select("
            g.id,
            g.status,
            COUNT(c.id) AS clients_total
        ")
        ->from($this->groups_table . ' g')
        ->join($this->clients_table . ' c', 'c.client_group_id = g.id', 'left')
        ->group_by('g.id')
        ->get()
        ->result_array();

    $kpi = [
        'total_groups'      => 0,
        'active_groups'     => 0,
        'inactive_groups'   => 0,
        'hold_groups'       => 0,
        'terminated_groups' => 0,
        'archived_groups'   => 0,
        'total_clients'     => 0,
    ];

    foreach ($rows as $row) {
        $status = strtolower(trim((string)($row['status'] ?? 'inactive')));

        $kpi['total_groups']++;
        $kpi['total_clients'] += (int)($row['clients_total'] ?? 0);

        if ($status === 'active') {
            $kpi['active_groups']++;
        } elseif ($status === 'inactive') {
            $kpi['inactive_groups']++;
        } elseif (in_array($status, ['hold', 'on-hold', 'on hold'], true)) {
            $kpi['hold_groups']++;
        } elseif ($status === 'terminated') {
            $kpi['terminated_groups']++;
        } elseif ($status === 'archived') {
            $kpi['archived_groups']++;
        }
    }

    return $kpi;
}

public function get_clients_by_group(int $groupId): array
{
    return $this->db
        ->where('client_group_id', $groupId)
        ->order_by('practice_name ASC')
        ->get($this->clients_table)
        ->result_array();
}

public function get_with_group($id): ?array
{
    $row = $this->db
        ->select("
            c.*,
            g.group_name, g.company_name, g.contact_person, g.contact_email, g.contact_phone, g.status AS group_status,
            creator.fullname AS created_by_name,
            updater.fullname AS updated_by_name
        ")
        ->from($this->clients_table . ' c')
        ->join($this->groups_table . ' g', 'g.id = c.client_group_id', 'left')
        ->join('users AS creator', 'creator.id = c.created_by', 'left')
        ->join('users AS updater', 'updater.id = c.updated_by', 'left')
        ->where('c.' . $this->pk, (int)$id)
        ->limit(1)
        ->get()
        ->row_array();

    return $row ?: null;
}


public function get_clients_kpi()
{
    $kpi = [];

    $kpi['direct_clients'] = $this->db
        ->where('is_group', 0)
        ->where('is_active', 1)
        ->count_all_results('crm_clients');

    $kpi['group_clients'] = $this->db
        ->where('is_group', 1)
        ->where('is_active', 1)
        ->count_all_results('crm_clients');

    $kpi['total_active'] = $this->db
        ->where('is_active', 1)
        ->count_all_results('crm_clients');

    $kpi['total_inactive'] = $this->db
        ->where('is_active', 0)
        ->count_all_results('crm_clients');

    $kpi['terminated'] = $this->db
        ->where('client_status', 'terminated')
        ->count_all_results('crm_clients');

    $kpi['contract_expiring'] = $this->db
        ->where('contract_end_date >=', date('Y-m-d'))
        ->where('contract_end_date <=', date('Y-m-d', strtotime('+30 days')))
        ->where('is_active', 1)
        ->count_all_results('crm_clients');

    return $kpi;
}


/**
 * Get a single client with fields needed for the invoice form panel
 */
public function get_client_for_invoice($id)
{
    return $this->db
        ->select('
            id,
            client_code,
            practice_name,
            practice_legal_name,
            practice_type,
            specialty,
            primary_contact_name,
            primary_email,
            primary_phone,
            address,
            city,
            state,
            zip_code,
            country,
            billing_model,
            rate_percent,
            rate_flat,
            account_manager,
            client_status,
            is_active
        ')
        ->from('crm_clients')
        ->where('id', (int)$id)
        ->where('is_active', 1)
        ->limit(1)
        ->get()
        ->row();
}

/**
 * Dropdown list for invoice form client selector
 */
public function get_clients_dropdown()
{
    return $this->db
        ->select('id, practice_name AS name, client_code, primary_email, client_status')
        ->from('crm_clients')
        ->where('is_active', 1)
        ->where('client_status !=', 'offboarded')
        ->order_by('practice_name', 'ASC')
        ->get()
        ->result();
}

}
