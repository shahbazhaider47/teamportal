<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company_info_model extends CI_Model
{
    protected $table = 'company_info'; // PRIMARY TABLE

    /* --------------------------------------------------------------------------
     | COMPANY INFO (existing logic – unchanged)
     |--------------------------------------------------------------------------*/

    public function get_all_values(): array
    {
        $row = $this->db->limit(1)->get($this->table)->row_array();

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
            'office_id'      => null,
        ];
    }

    public function upsert(array $data): bool
    {
        $allowed = [
            'company_name','business_phone','business_email',
            'light_logo','dark_logo','favicon',
            'address','state','city','zip_code','office_id'
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        $exists = $this->db->count_all($this->table) > 0;

        return $exists
            ? $this->db->update($this->table, $payload)
            : $this->db->insert($this->table, $payload);
    }

    /* --------------------------------------------------------------------------
     | COMPANY OFFICES
     |--------------------------------------------------------------------------*/
    
    private function officesTable(): string
    {
        return 'company_offices';
    }
    
    public function get_offices(): array
    {
        return $this->db
            ->where('is_active', 1)
            ->order_by('is_head_office', 'DESC')
            ->order_by('office_name', 'ASC')
            ->get($this->officesTable())
            ->result_array();
    }
    
    public function count_offices(): int
    {
        return (int) $this->db
            ->where('is_active', 1)
            ->count_all_results($this->officesTable());
    }
    
    public function get_head_office(): ?array
    {
        return $this->db
            ->where('is_head_office', 1)
            ->where('is_active', 1)
            ->limit(1)
            ->get($this->officesTable())
            ->row_array() ?: null;
    }
    
    public function insert_office(array $data): bool
    {
        $this->db->trans_start();
    
        if (!empty($data['is_head_office'])) {
            $this->db->update(
                $this->officesTable(),
                ['is_head_office' => 0, 'updated_at' => date('Y-m-d H:i:s')]
            );
        }
    
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
    
        $this->db->insert($this->officesTable(), $data);
        $officeId = $this->db->insert_id();
    
        if ($officeId && !empty($data['is_head_office'])) {
            $this->db->update(
                $this->table,
                ['office_id' => $officeId]
            );
        }
    
        $this->db->trans_complete();
    
        if ($this->db->trans_status() === false) {
            log_message('error', 'Office insert failed: ' . json_encode($data));
            return false;
        }
    
        return true;
    }

}
