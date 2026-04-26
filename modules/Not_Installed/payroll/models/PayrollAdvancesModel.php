<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('PayrollBaseModel')) {
    // Load from same directory first (works whether modules are under /modules or /application/modules)
    $base = __DIR__ . '/PayrollBaseModel.php';
    if (file_exists($base)) {
        require_once $base;
    } else {
        // Fallback if someone later moves the module under application/modules
        @require_once APPPATH . 'modules/payroll/models/PayrollBaseModel.php';
    }
}
/**
 * Salary advances: admin and self-service flows.
 */
class PayrollAdvancesModel extends PayrollBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ------- Lists ------- */

    public function advances_all(bool $with_user = true): array
    {
        $aTbl = $this->tbl($this->tbl_advances);
        $this->db->from($aTbl.' pa')
                 ->order_by('pa.requested_at','DESC')
                 ->order_by('pa.id','DESC');

        if ($with_user) {
            $uTbl = $this->tbl($this->tbl_users);
            $this->db->select("
                pa.*,
                u.fullname AS requester_name, u.emp_id,
                ap.fullname AS approved_by_name
            ", false);
            $this->db->join($uTbl.' u',  'u.id  = pa.user_id',     'left');
            $this->db->join($uTbl.' ap', 'ap.id = pa.approved_by', 'left');
        } else {
            $this->db->select('pa.*');
        }

        return $this->db->get()->result_array();
    }

    public function advances_for_user(int $user_id, bool $with_user = false): array
    {
        $aTbl = $this->tbl($this->tbl_advances);
        $this->db->from($aTbl.' pa')
                 ->where('pa.user_id', (int)$user_id)
                 ->order_by('pa.requested_at','DESC')
                 ->order_by('pa.id','DESC');

        if ($with_user) {
            $uTbl = $this->tbl($this->tbl_users);
            $this->db->select("
                pa.id, pa.user_id, pa.amount, pa.paid, pa.balance,
                pa.requested_at, pa.approved_at, pa.approved_by, pa.notes, pa.status,
                u.fullname AS requester_name,
                ap.fullname AS approved_by_name
            ", false);
            $this->db->join($uTbl.' u',  'u.id  = pa.user_id',     'left');
            $this->db->join($uTbl.' ap', 'ap.id = pa.approved_by', 'left');
        } else {
            $this->db->select("
                pa.id, pa.user_id, pa.amount, pa.paid, pa.balance,
                pa.requested_at, pa.approved_at, pa.approved_by, pa.notes, pa.status
            ", false);
        }

        return $this->db->get()->result_array();
    }

    /* ------- Single ------- */

    public function advance(int $id, bool $with_user = true): array
    {
        $aTbl = $this->tbl($this->tbl_advances);
        $uTbl = $this->tbl($this->tbl_users);

        $this->db->from($aTbl.' pa')->where('pa.id', (int)$id);

        if ($with_user) {
            $this->db->select("
                pa.*,
                u.fullname AS requester_name, u.emp_id AS requester_emp_id,
                ap.fullname AS approved_by_name
            ", false)
            ->join($uTbl.' u',  'u.id  = pa.user_id',     'left')
            ->join($uTbl.' ap', 'ap.id = pa.approved_by', 'left');
        } else {
            $this->db->select('pa.*');
        }

        return $this->db->get()->row_array() ?: [];
    }

    /* ------- Save/Delete ------- */

    public function advance_save(array $data, ?int $id = null): bool
    {
        $aTbl = $this->tbl($this->tbl_advances);
        if ($id) {
            $this->db->where('id', (int)$id)->update($aTbl, $data);
            return $this->db->affected_rows() >= 0;
        }
        return $this->db->insert($aTbl, $data);
    }

    public function save_advance_by_user(int $user_id, float $amount, string $notes = ''): bool
    {
        if ($user_id <= 0 || $amount <= 0) return false;

        $uTbl = $this->tbl($this->tbl_users);
        $exists = $this->db->select('id')->from($uTbl)->where('id', $user_id)->limit(1)->get()->row_array();
        if (!$exists) return false;

        $row = [
            'user_id'      => $user_id,
            'amount'       => round($amount, 2),
            'paid'         => 0,
            'balance'      => round($amount, 2),
            'requested_at' => $this->now(),
            'approved_at'  => null,
            'approved_by'  => null,
            'status'       => 'requested',
            'notes'        => (string)$notes,
        ];

        $aTbl = $this->tbl($this->tbl_advances);
        return $this->db->insert($aTbl, $row);
    }

    public function advance_delete(int $id): bool
    {
        $aTbl = $this->tbl($this->tbl_advances);
        $this->db->where('id', (int)$id)->delete($aTbl);
        return $this->db->affected_rows() > 0;
    }
}
