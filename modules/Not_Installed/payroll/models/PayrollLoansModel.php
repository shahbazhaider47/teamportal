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
 * Loans domain: CRUD, user limits, and admin lists.
 */
class PayrollLoansModel extends PayrollBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ------- Lists ------- */

    public function get_loans(): array
    {
        $lTbl = $this->tbl($this->tbl_loans);
        $uTbl = $this->tbl($this->tbl_users);

        return $this->db->select("pl.*, u.emp_id, u.firstname, u.lastname, u.fullname")
                        ->from($lTbl.' pl')
                        ->join($uTbl.' u', 'u.id = pl.user_id', 'left')
                        ->order_by('pl.created_at', 'DESC')
                        ->get()->result_array();
    }

    public function loans_for_user(int $user_id, bool $with_user = false): array
    {
        $lTbl = $this->tbl($this->tbl_loans);
        $this->db->from($lTbl.' pl')->where('pl.user_id', $user_id)
                 ->order_by('pl.created_at', 'DESC');

        if ($with_user) {
            $uTbl = $this->tbl($this->tbl_users);
            $this->db->select('pl.*, u.emp_id, u.firstname, u.lastname, u.fullname')
                     ->join($uTbl.' u', 'u.id = pl.user_id', 'left');
        } else {
            $this->db->select('pl.*');
        }

        return $this->db->get()->result_array();
    }

    /* ------- Single ------- */

    public function get_loan(int $id): ?array
    {
        $lTbl = $this->tbl($this->tbl_loans);
        $uTbl = $this->tbl($this->tbl_users);

        $row = $this->db->select("pl.*, u.emp_id, u.firstname, u.lastname, u.fullname")
                        ->from($lTbl.' pl')
                        ->join($uTbl.' u', 'u.id = pl.user_id', 'left')
                        ->where('pl.id', (int)$id)
                        ->get()->row_array();

        return $row ?: null;
    }

    /* ------- Save/Delete ------- */

    public function save_loan(array $payload, ?int $id = null): bool
    {
        // Normalize numeric fields
        $loan_taken          = isset($payload['loan_taken']) ? (float)$payload['loan_taken'] : 0.0;
        $total_installments  = isset($payload['total_installments']) ? (int)$payload['total_installments'] : 0;
        $monthly_installment = isset($payload['monthly_installment']) ? (float)$payload['monthly_installment'] : 0.0;
        $total_paid          = isset($payload['total_paid']) ? (float)$payload['total_paid'] : 0.0;
        $current_installment = isset($payload['current_installment']) ? (int)$payload['current_installment'] : 0;

        if ($monthly_installment <= 0 && $loan_taken > 0 && $total_installments > 0) {
            $monthly_installment = $loan_taken / $total_installments;
        }
        if ($total_paid > 0 && $monthly_installment > 0) {
            $current_installment = (int) floor($total_paid / $monthly_installment);
        }
        $balance = $loan_taken - $total_paid;

        $payload['loan_taken']          = $loan_taken;
        $payload['total_installments']  = $total_installments;
        $payload['monthly_installment'] = $monthly_installment;
        $payload['total_paid']          = $total_paid;
        $payload['current_installment'] = $current_installment;
        $payload['balance']             = $balance;

        $payload['payback_type'] = $payload['payback_type'] ?? 'monthly';
        $payload['status']       = $payload['status'] ?? 'active';

        return $this->upsert_loan($payload, $id);
    }

    public function upsert_loan(array $payload, ?int $id = null): bool
    {
        $lTbl = $this->tbl($this->tbl_loans);
        $now  = $this->now();

        if (!empty($id)) {
            $payload['updated_at'] = $now;
            $this->db->where('id', (int)$id)->update($lTbl, $payload);
            return $this->db->affected_rows() >= 0;
        }

        $payload['created_at'] = $now;
        $payload['updated_at'] = $now;
        $this->db->insert($lTbl, $payload);
        return $this->db->affected_rows() > 0;
    }

    public function delete_loan(int $id): bool
    {
        $lTbl = $this->tbl($this->tbl_loans);
        $this->db->where('id', (int)$id)->delete($lTbl);
        return $this->db->affected_rows() > 0;
    }

    /* ------- Facade helpers for controller wiring later ------- */

    // Aggregated payload for loans page
    public function loans_data(): array
    {
        return [
            'loans'     => $this->get_loans(),
            'users_all' => $this->list_all_users_for_dropdown(),
        ];
    }
}
