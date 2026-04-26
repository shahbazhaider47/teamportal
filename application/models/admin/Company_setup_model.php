<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company_setup_model extends CI_Model
{
    /* ==========================================================
     | TABLES
     ========================================================== */
    protected string $companyTable      = 'company_info';
    protected string $officeTable       = 'company_offices';
    protected string $companySettings   = 'company_settings';

    /* ==========================================================
     | COMPANY METHODS
     ========================================================== */

    /**
     * Get company profile (single row system-wide)
     */
    public function get_company(): array
    {
        $row = $this->db
            ->limit(1)
            ->get($this->companyTable)
            ->row_array();

        return $row ?: [
            'company_name'   => '',
            'business_phone' => '',
            'business_email' => '',
            'light_logo'     => '',
            'dark_logo'      => '',
            'favicon'        => '',
            'address'        => '',
            'state'          => '',
            'city'           => '',
            'zip_code'       => '',
            'company_type'   => '',
            'ntn_no'         => '',
            'website'        => '',
            'office_id'      => null,
        ];
    }

    /**
     * Save company profile (insert/update)
     */
    public function save_company(array $data): bool
    {
        $allowed = [
            'company_name',
            'business_phone',
            'business_email',
            'address',
            'state',
            'city',
            'zip_code',
            'office_id',
            'company_type',
            'ntn_no',
            'website',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));
        $payload['updated_at'] = date('Y-m-d H:i:s');

        if ($this->db->count_all($this->companyTable) > 0) {
            return $this->db->update($this->companyTable, $payload);
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($this->companyTable, $payload);
    }

    /**
     * Update a single company logo field
     */
    public function update_company_logo(string $column, string $file): bool
    {
        if (!in_array($column, ['light_logo', 'dark_logo', 'favicon'], true)) {
            return false;
        }

        return $this->db->update(
            $this->companyTable,
            [$column => $file]
        );
    }

    /**
     * Clear a company logo field
     */
    public function clear_company_logo(string $column): bool
    {
        if (!in_array($column, ['light_logo', 'dark_logo', 'favicon'], true)) {
            return false;
        }

        return $this->db->update(
            $this->companyTable,
            [$column => '']
        );
    }

    /* ==========================================================
     | OFFICE METHODS
     ========================================================== */

    /**
     * Get all offices
     */
    public function get_offices(): array
    {
        return $this->db
            ->order_by('is_head_office', 'DESC')
            ->order_by('office_name', 'ASC')
            ->get($this->officeTable)
            ->result_array();
    }

    /**
     * Get single office
     */
    public function get_office(int $id): ?array
    {
        $row = $this->db
            ->select('id as office_id, office_code, office_name, country, address_line_1, address_line_2, city, state, postal_code, phone, email, timezone, currency, is_head_office, is_active')
            ->where('id', $id)
            ->get($this->officeTable)
            ->row_array();
    
        return $row ?: null;
    }

    /**
     * Check if office code already exists
     */
    public function office_code_exists(string $code, ?int $ignoreId = null): bool
    {
        $this->db->where('office_code', $code);
    
        if ($ignoreId) {
            $this->db->where('id !=', $ignoreId);
        }
    
        return $this->db->count_all_results($this->officeTable) > 0;
    }
    
    /**
     * Save office (insert/update)
     * --------------------------------------------------
     * - Prevents duplicate office_code
     * - Ensures ONLY one head office
     * - Syncs company_info.office_id automatically
     */
    public function save_office(array $data, ?int $id = null): bool
    {
        // --------------------------------------------------
        // Validate unique office code
        // --------------------------------------------------
        if (!empty($data['office_code']) && $this->office_code_exists($data['office_code'], $id)) {
            return false; // controller shows alert
        }
    
        // --------------------------------------------------
        // Allowed fields
        // --------------------------------------------------
        $allowed = [
            'office_code',
            'office_name',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'country',
            'phone',
            'email',
            'timezone',
            'currency',
            'is_head_office',
            'is_active',
        ];
    
        $payload = array_intersect_key($data, array_flip($allowed));
        $payload['updated_at'] = date('Y-m-d H:i:s');
    
        $this->db->trans_start();
    
        // --------------------------------------------------
        // Enforce single head office
        // --------------------------------------------------
        if (!empty($payload['is_head_office'])) {
            $this->db->update($this->officeTable, ['is_head_office' => 0]);
        }
    
        // --------------------------------------------------
        // Insert or Update
        // --------------------------------------------------
        if ($id) {
            $this->db
                ->where('id', $id)
                ->update($this->officeTable, $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert($this->officeTable, $payload);
            $id = $this->db->insert_id();
        }
    
        // --------------------------------------------------
        // Sync head office to company_info
        // --------------------------------------------------
        if (!empty($payload['is_head_office'])) {
            $this->db->update(
                $this->companyTable,
                ['office_id' => $id]
            );
        }
    
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === false) {
            log_message('error', 'Failed to save office: '.json_encode($payload));
            return false;
        }
        
        return true;
    }


    public function get_all_departments()
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
    public function get_dept_with_stats()
    {
        return $this->db->select([
                'd.*',
                'COUNT(DISTINCT u.id) AS staff_count',
                "CONCAT(hod_u.firstname, ' ', hod_u.lastname) AS hod_name",
                'hod_u.profile_image AS hod_profile_image'
            ])
            ->from('departments d')
            ->join('users u', 'u.emp_department = d.id', 'left')
            ->join('users hod_u', 'hod_u.id = d.hod', 'left')
            ->group_by('d.id')
            ->order_by('d.name', 'ASC')
            ->get()
            ->result_array();
    }
     
    
    public function delete_office(int $id): bool
    {
        return $this->db
            ->where('id', $id)
            ->delete('company_offices');
    }


/**
 * Build organizational chart hierarchy
 * -----------------------------------
 * Returns a nested tree:
 * [
 *   id, name, title, department, avatar, children[]
 * ]
 */
public function get_org_chart(): array
{
    // Fetch all active users
    $users = $this->db
        ->select([
            'u.id',
            'u.firstname',
            'u.lastname',
            'u.fullname',
            'u.emp_title',
            'u.emp_department',
            'u.emp_manager',
            'u.profile_image',
            'd.name AS department_name'
        ])
        ->from('users u')
        ->join('departments d', 'd.id = u.emp_department', 'left')
        ->where('u.is_active', 1)
        ->order_by('u.emp_manager ASC')
        ->order_by('u.id ASC')
        ->get()
        ->result_array();

    if (empty($users)) {
        return [];
    }

    // Normalize users into nodes
    $nodes = [];
    foreach ($users as $u) {
        $name = trim($u['fullname']);
        if ($name === '') {
            $name = trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
        }

        $nodes[$u['id']] = [
            'id'         => (int)$u['id'],
            'name'       => $name ?: 'User #' . $u['id'],
            'title'      => $u['emp_title'] ?: '—',
            'department' => $u['department_name'] ?: '—',
            'avatar'     => $this->_resolve_avatar($u['profile_image']),
            'manager_id' => $u['emp_manager'] ? (int)$u['emp_manager'] : null,
            'children'   => [],
        ];
    }

    // Build tree
    $tree = [];
    foreach ($nodes as $id => &$node) {
        if ($node['manager_id'] && isset($nodes[$node['manager_id']])) {
            $nodes[$node['manager_id']]['children'][] = &$node;
        } else {
            // Top-level node (CEO / Director / Head)
            $tree[] = &$node;
        }
    }
    unset($node);

    return $tree;
}
    
    /**
     * Resolve profile image safely
     */
    private function _resolve_avatar(?string $image): string
    {
        $default = base_url('assets/images/default.png');
    
        if (!$image) {
            return $default;
        }
    
        $path = FCPATH . 'uploads/users/profile/' . $image;
        if (is_file($path)) {
            return base_url('uploads/users/profile/' . $image);
        }
    
        return $default;
    }
     
    
    /**
     * Insert or update a company setting
     */
    public function save_company_setting(string $key, ?string $value): bool
    {
        if ($key === '') {
            return false;
        }
    
        $exists = $this->db
            ->where('key', $key)
            ->count_all_results($this->companySettings) > 0;
    
        $data = [
            'key'        => $key,
            'value'      => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    
        if ($exists) {
            return $this->db
                ->where('key', $key)
                ->update($this->companySettings, $data);
        }
    
        $data['created_at'] = date('Y-m-d H:i:s');
    
        return $this->db->insert($this->companySettings, $data);
    }

    /**
     * Get all company settings as key => value
     * ---------------------------------------
     * Used by Company Settings form
     */
    public function get_company_settings(): array
    {
        $rows = $this->db
            ->select('key, value')
            ->from('company_settings')
            ->get()
            ->result_array();
    
        $settings = [];
    
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
    
        return $settings;
    }

}