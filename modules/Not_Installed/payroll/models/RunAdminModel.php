<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * RunAdminModel
 *
 * Admin controls for a payroll run (bulk update across payroll_details by run_id).
 */
class RunAdminModel extends CI_Model
{
    protected $tbl_details = 'payroll_details';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Read the current run controls (from the first row of the run).
     * Returns null if run_id not found.
     */
    public function get_run_controls(int $run_id): ?array
    {
        $tbl = $this->db->dbprefix($this->tbl_details);

        $row = $this->db->select('
                    run_id,
                    MIN(period_start) AS period_start,
                    MAX(period_end)   AS period_end,
                    pay_period,
                    pay_date,
                    status        AS payroll_status,
                    status_run,
                    is_locked,
                    payment_method,
                    paid_by,
                    MIN(created_at) AS created_at,
                    MAX(updated_at) AS updated_at
                ', false)
                ->from($tbl)
                ->where('run_id', (int)$run_id)
                ->limit(1)
                ->get()->row_array();

        return $row ?: null;
    }

    /**
     * Bulk update admin controls for a run.
     *
     * @param int   $run_id
     * @param array $input  Keys allowed:
     *   - status:        'active'|'inactive'
     *   - status_run:    'draft'|'processed'|'posted'|'paid'|'void'
     *   - pay_date:      'Y-m-d'
     *   - is_locked:     truthy/0|1
     *   - payment_method:'bank'|'cash'|'cheque'|'wallet'|'other'
     *   - paid_by:       int|null
     *
     * Auto-behavior:
     *   - If status_run set to 'posted' and posted_at is NULL → sets posted_at=NOW()
     *   - If status_run set to 'paid'   and paid_at   is NULL → sets paid_at=NOW()
     *
     * @return bool true on success (even if no rows changed), false if nothing to update / invalid run.
     */
    public function update_run_meta(int $run_id, array $input): bool
    {
        $run_id = (int)$run_id;
        if ($run_id <= 0) return false;

        $allowedStatus     = ['active','inactive'];
        $allowedRunStatus  = ['draft','processed','posted','paid','void'];
        $allowedPayMethods = ['bank','cash','cheque','wallet','other'];

        $update = [];

        // status (payroll_status)
        if (isset($input['status'])) {
            $v = strtolower(trim((string)$input['status']));
            if (in_array($v, $allowedStatus, true)) {
                $update['status'] = $v;
            }
        }

        // status_run
        $setPostedAt = false;
        $setPaidAt   = false;
        if (isset($input['status_run'])) {
            $v = strtolower(trim((string)$input['status_run']));
            if (in_array($v, $allowedRunStatus, true)) {
                $update['status_run'] = $v;
                if ($v === 'posted') $setPostedAt = true;
                if ($v === 'paid')   $setPaidAt   = true;
            }
        }

        // pay_date
        if (isset($input['pay_date'])) {
            $d = trim((string)$input['pay_date']);
            $ts = strtotime($d);
            if ($ts !== false) {
                $update['pay_date'] = date('Y-m-d', $ts);
            }
        }

        // is_locked
        if (array_key_exists('is_locked', $input)) {
            $update['is_locked'] = (int) (!!$input['is_locked']);
        }

        // payment_method
        if (isset($input['payment_method'])) {
            $pm = strtolower(trim((string)$input['payment_method']));
            if (in_array($pm, $allowedPayMethods, true)) {
                $update['payment_method'] = $pm;
            }
        }

        // paid_by
        if (array_key_exists('paid_by', $input)) {
            $pb = $input['paid_by'];
            if ($pb === '' || $pb === null) {
                $update['paid_by'] = null;
            } else {
                $update['paid_by'] = (int)$pb ?: null;
            }
        }

        if (empty($update) && !$setPostedAt && !$setPaidAt) {
            // nothing to do
            return false;
        }

        $tbl = $this->db->dbprefix($this->tbl_details);

        // Build update
        $this->db->where('run_id', $run_id);

        // Stamp updated_at always
        $update['updated_at'] = date('Y-m-d H:i:s');

        // Conditional stamps only if transitioning and current columns are NULL
        // We do it with SQL CASE to avoid overwriting existing stamps.
        if ($setPostedAt) {
            // posted_at = IFNULL(posted_at, NOW())
            $this->db->set('posted_at', 'IFNULL(posted_at, NOW())', false);
        }
        if ($setPaidAt) {
            // paid_at = IFNULL(paid_at, NOW())
            $this->db->set('paid_at', 'IFNULL(paid_at, NOW())', false);
        }

        $ok = $this->db->update($tbl, $update);
        if (!$ok) return false;

        // Even if 0 rows changed (values identical), treat as success
        return true;
    }
}
