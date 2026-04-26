<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('PayrollBaseModel')) {
    $base = __DIR__ . '/PayrollBaseModel.php';
    if (file_exists($base)) {
        require_once $base;
    } else {
        @require_once APPPATH . 'modules/payroll/models/PayrollBaseModel.php';
    }
}

class PayrollArrearsModel extends PayrollBaseModel
{
    protected $tbl_arrears = 'payroll_arrears'; // raw table name; PayrollBaseModel::tbl() will prefix

    public function __construct()
    {
        parent::__construct();
    }

    /** List all arrears (optionally join user basics) */
    public function arrears_all(bool $with_user = true): array
    {
        $aTbl = $this->tbl($this->tbl_arrears);
        $this->db->from($aTbl.' a')->order_by('a.created_at','DESC');

        if ($with_user) {
            $uTbl = $this->tbl($this->tbl_users);
            $this->db->select('a.*, u.emp_id, u.firstname, u.lastname, u.fullname')
                     ->join($uTbl.' u','u.id = a.user_id','left');
        } else {
            $this->db->select('a.*');
        }

        return $this->db->get()->result_array();
    }


    /** One row */
    public function arrear(int $id): ?array
    {
        $aTbl = $this->tbl($this->tbl_arrears);
        $uTbl = $this->tbl($this->tbl_users);

        $row = $this->db->select('a.*, u.emp_id, u.firstname, u.lastname, u.fullname')
                        ->from($aTbl.' a')
                        ->join($uTbl.' u','u.id = a.user_id','left')
                        ->where('a.id', (int)$id)
                        ->get()->row_array();
        return $row ?: null;
    }

    /** Insert/Update */
    public function save(array $payload, ?int $id = null): bool
    {
        $aTbl = $this->tbl($this->tbl_arrears);
        $now  = $this->now();

        // Normalize
        $payload['arrears_amount'] = isset($payload['arrears_amount']) ? (float)$payload['arrears_amount'] : 0.00;
        $payload['status']         = $payload['status'] ?? 'pending';

        if ($id) {
            $this->db->where('id', (int)$id)->update($aTbl, $payload);
            return $this->db->affected_rows() >= 0;
        }

        $payload['created_at'] = $now;
        $this->db->insert($aTbl, $payload);
        return $this->db->affected_rows() > 0;
    }

    public function delete(int $id): bool
    {
        $aTbl = $this->tbl($this->tbl_arrears);
        $this->db->where('id', (int)$id)->delete($aTbl);
        return $this->db->affected_rows() > 0;
    }

    /** Reuse the base dropdown helper (you already use this elsewhere) */
public function list_all_users_for_dropdown(): array
{
    $uTbl = $this->tbl($this->tbl_users);

    // Build base query
    $this->db->select('id, emp_id, firstname, lastname, fullname')
             ->from($uTbl);

    // Add active filter if the column(s) exist
    $hasIsActive = $this->db->field_exists('is_active', $uTbl);
    $hasStatus   = $this->db->field_exists('status',   $uTbl);

    if ($hasIsActive || $hasStatus) {
        $this->db->group_start();
        if ($hasIsActive) { $this->db->where('is_active', 1); }
        if ($hasStatus)   { $this->db->or_where('status', 1); }
        $this->db->group_end();
    }

    return $this->db->order_by('fullname', 'ASC')->get()->result_array();
}

}
