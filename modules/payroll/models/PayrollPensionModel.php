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
 * Provident Fund / Pension accounts & transactions.
 * Not yet wired in controllers, but ready for future features.
 */
class PayrollPensionModel extends PayrollBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ------- Accounts ------- */

    public function get_account_for_user(int $user_id): ?array
    {
        $tbl = $this->tbl($this->tbl_pf_accounts);
        if (!$this->db->table_exists($tbl)) return null;

        $row = $this->db->from($tbl)->where('user_id', (int)$user_id)->limit(1)->get()->row_array();
        return $row ?: null;
    }

    public function upsert_account(int $user_id, array $payload): bool
    {
        $tbl = $this->tbl($this->tbl_pf_accounts);
        if (!$this->db->table_exists($tbl)) return false;

        $exists = $this->get_account_for_user($user_id);
        if ($exists) {
            $payload['updated_at'] = $this->now();
            $this->db->where('id', (int)$exists['id'])->update($tbl, $payload);
            return $this->db->affected_rows() >= 0;
        }

        $payload['user_id']    = (int)$user_id;
        $payload['created_at'] = $this->now();
        $payload['updated_at'] = $this->now();
        $this->db->insert($tbl, $payload);
        return $this->db->affected_rows() > 0;
    }

    /* ------- Transactions ------- */

    public function list_transactions(int $pf_account_id): array
    {
        $tbl = $this->tbl($this->tbl_pf_transactions);
        if (!$this->db->table_exists($tbl)) return [];
        return $this->db->from($tbl)
                        ->where('pf_account_id', (int)$pf_account_id)
                        ->order_by('txn_date', 'DESC')
                        ->order_by('id', 'DESC')
                        ->get()->result_array();
    }

    public function add_transaction(int $pf_account_id, array $payload): bool
    {
        $tbl = $this->tbl($this->tbl_pf_transactions);
        if (!$this->db->table_exists($tbl)) return false;

        $payload['pf_account_id'] = (int)$pf_account_id;
        $payload['created_at']    = $this->now();
        $this->db->insert($tbl, $payload);
        return $this->db->affected_rows() > 0;
    }
}
