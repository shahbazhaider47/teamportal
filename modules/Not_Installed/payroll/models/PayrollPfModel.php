<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PayrollPfModel extends CI_Model
{
    protected string $tbl_accounts = 'payroll_pf_accounts';
    protected string $tbl_txns     = 'payroll_pf_transactions';
    protected string $tbl_users    = 'users'; // adjust if different

    protected function t($raw) { return $this->db->dbprefix($raw); }
    protected function now()   { return date('Y-m-d H:i:s'); }

    /* List all accounts with user basics */
    public function accounts_all(): array
    {
        $a = $this->t($this->tbl_accounts);
        $u = $this->t($this->tbl_users);

        $this->db->from($a.' a')
                 ->select('a.*, u.fullname, u.emp_id, u.email')
                 ->join($u.' u', 'u.id = a.user_id', 'left')
                 ->order_by('a.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    /* Single account row */
    public function account(int $id): ?array
    {
        $a = $this->t($this->tbl_accounts);
        $u = $this->t($this->tbl_users);

        $row = $this->db->from($a.' a')
                        ->select('a.*, u.fullname, u.emp_id, u.email')
                        ->join($u.' u', 'u.id = a.user_id', 'left')
                        ->where('a.id', $id)->get()->row_array();
        return $row ?: null;
    }

    /* Transactions for account */
    public function transactions_for_account(int $account_id): array
    {
        $t = $this->t($this->tbl_txns);
        return $this->db->from($t)
                        ->where('pf_account_id', $account_id)
                        ->order_by('txn_date', 'DESC')
                        ->order_by('id', 'DESC')
                        ->get()->result_array();
    }

    /* Insert/Update account */
    public function account_save(array $payload, ?int $id = null): bool
    {
        $a = $this->t($this->tbl_accounts);
        $now = $this->now();

        if ($id) {
            $payload['updated_at'] = $now;
            $this->db->where('id', $id)->update($a, $payload);
            return $this->db->affected_rows() >= 0;
        }

        $payload['created_at'] = $now;
        $payload['updated_at'] = $now;
        $this->db->insert($a, $payload);
        return $this->db->affected_rows() > 0;
    }

    public function account_delete(int $id): bool
    {
        $a = $this->t($this->tbl_accounts);
        // (Optional) CASCADE delete transactions or soft-delete as per your policy
        $this->db->where('id', $id)->delete($a);
        return $this->db->affected_rows() > 0;
    }

    /* Insert/Update transaction + keep account.current_balance in sync (optional) */
    public function txn_save(array $payload, ?int $id = null): bool
    {
        $t = $this->t($this->tbl_txns);
        $a = $this->t($this->tbl_accounts);
        $now = $this->now();

        $this->db->trans_start();

        if ($id) {
            $this->db->where('id', $id)->update($t, $payload);
        } else {
            $payload['created_at'] = $now;
            $this->db->insert($t, $payload);
            $id = (int)$this->db->insert_id();
        }

        // OPTIONAL: recompute current_balance as sum(employee_share + employer_share) - withdrawals, etc.
        // Simple example: treat positive amount as contribution, negative as withdrawal/adjustment.
        $aid = (int)$payload['pf_account_id'];
        $sum = $this->db->select('COALESCE(SUM(amount),0) AS s', false)
                        ->from($t)->where('pf_account_id', $aid)->get()->row_array();
        $balance = isset($sum['s']) ? (float)$sum['s'] : 0.0;

        $this->db->where('id', $aid)->update($a, ['current_balance' => $balance, 'updated_at' => $now]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /* Delete transaction and re-sync account balance; returns account id for redirect */
    public function txn_delete(int $id): ?int
    {
        $t = $this->t($this->tbl_txns);
        $a = $this->t($this->tbl_accounts);
        $now = $this->now();

        $row = $this->db->from($t)->where('id', $id)->get()->row_array();
        if (!$row) return null;

        $aid = (int)$row['pf_account_id'];

        $this->db->trans_start();

        $this->db->where('id', $id)->delete($t);

        // re-sync balance
        $sum = $this->db->select('COALESCE(SUM(amount),0) AS s', false)
                        ->from($t)->where('pf_account_id', $aid)->get()->row_array();
        $balance = isset($sum['s']) ? (float)$sum['s'] : 0.0;

        $this->db->where('id', $aid)->update($a, ['current_balance' => $balance, 'updated_at' => $now]);

        $this->db->trans_complete();
        return $this->db->trans_status() ? $aid : null;
    }
}
