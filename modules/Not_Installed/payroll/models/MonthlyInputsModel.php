<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('PayrollBaseModel')) {
    $base = __DIR__ . '/PayrollBaseModel.php';
    if (file_exists($base)) require_once $base;
}

class MonthlyInputsModel extends PayrollBaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ------------ UI elements list (Allowances + Deductions) ------------ */
    public function pay_elements(): array
    {
        $out = [];
    
        // (A) Allowances from hrm_allowances (active only)
        $aTbl = $this->db->dbprefix('hrm_allowances');
        if ($this->db->table_exists($aTbl)) {
            $rows = $this->db->from($aTbl)
                ->where('is_active', 1)
                ->order_by('title', 'ASC')
                ->get()
                ->result_array();
    
            foreach ($rows as $r) {
                $out[] = [
                    'key'   => 'A:' . $r['id'],     // A:<allowance_id>
                    'type'  => 'A',
                    'label' => $r['title'] . ' (A)',
                ];
            }
        }
    
        // (D) Common deductions
        $deductions = [
            ['key' => 'D:tax',  'label' => 'Income Tax (D)'],
            ['key' => 'D:loan', 'label' => 'Loan (D)'],
            ['key' => 'D:late', 'label' => 'Late & Early Out Deduction (D)'],
            ['key' => 'D:eobi', 'label' => 'EOBI (D)'],
        ];
    
        foreach ($deductions as $d) {
            $out[] = [
                'key'   => $d['key'],
                'type'  => 'D',
                'label' => $d['label'],
            ];
        }
    
        return $out;
    }


    /* ------------ Save + apply (write into payroll_details only) ------------ */
    public function save_and_apply(int $run_id, array $items): bool
    {
        $run_id = (int)$run_id;
        if ($run_id <= 0) return false;

        // Normalize incoming POST arrays
        $uids  = (array)($items['uid'] ?? []);
        $keys  = (array)($items['element_key'] ?? []);
        $amts  = (array)($items['amount'] ?? []);
        $dates = (array)($items['pay_date'] ?? []);   // not used for storage, retained if you later want audit
        $rems  = (array)($items['remarks'] ?? []);    // not used for storage, retained if you later want audit

        // Aggregate only D:* (deduction) items per user and per code
        // Structure: $agg[$user_id][$code] = sum
        $agg = [];
        $n = max(count($uids), count($keys), count($amts));
        for ($i=0; $i<$n; $i++) {
            $uid = (int)($uids[$i] ?? 0);
            $key = trim((string)($keys[$i] ?? ''));
            $amt = (float)($amts[$i] ?? 0);

            if ($uid <= 0 || $key === '' || $amt == 0.0) continue;

            // Only deductions
            if (strtoupper(substr($key, 0, 1)) !== 'D') continue;

            // Extract code after "D:"
            $parts = explode(':', $key, 2);
            $code  = strtolower(trim($parts[1] ?? 'misc'));

            if (!isset($agg[$uid])) $agg[$uid] = [];
            if (!isset($agg[$uid][$code])) $agg[$uid][$code] = 0.0;
            $agg[$uid][$code] += $amt;
        }

        if (empty($agg)) {
            // Nothing to write
            return true;
        }

        $pdTbl = $this->tbl($this->tbl_details);
        if (!$this->db->table_exists($pdTbl)) return false;

        $this->db->trans_start();

        // Human-friendly labels for JSON
        $label = [
            'tax'  => 'Income Tax',
            'loan' => 'Loan Deduction',
            'late' => 'Late & Early Out',
            'eobi' => 'EOBI',
        ];

        foreach ($agg as $user_id => $byCode) {
            // Fetch the payroll_details row for (run_id, user_id)
            $pd = $this->db->from($pdTbl)
                ->where('run_id', $run_id)
                ->where('user_id', (int)$user_id)
                ->limit(1)->get()->row_array();

            if (!$pd) continue;

            // Load existing JSON → array of items [{code, title, amount}]
            $existing = [];
            if (!empty($pd['monthly_input_deductions_json'])) {
                $tmp = json_decode($pd['monthly_input_deductions_json'], true);
                if (is_array($tmp)) $existing = $tmp;
            }

            // Collapse existing to map by code for easy merging
            $byCodeExisting = [];
            foreach ($existing as $it) {
                $c = strtolower((string)($it['code'] ?? ''));
                $t = (string)($it['title'] ?? '');
                $a = (float)($it['amount'] ?? 0);
                if ($c === '') continue;
                if (!isset($byCodeExisting[$c])) $byCodeExisting[$c] = ['code'=>$c, 'title'=>$t ?: ucfirst($c), 'amount'=>0.0];
                $byCodeExisting[$c]['amount'] += $a;
            }

            // Merge new amounts
            $newSum = 0.0;
            foreach ($byCode as $code => $sum) {
                $title = $label[$code] ?? ucfirst($code) . ' Deduction';
                if (!isset($byCodeExisting[$code])) {
                    $byCodeExisting[$code] = ['code'=>$code, 'title'=>$title, 'amount'=>0.0];
                }
                $byCodeExisting[$code]['amount'] = round($byCodeExisting[$code]['amount'] + (float)$sum, 2);
                $newSum += (float)$sum;
            }

            // Re-expand to a stable array sorted by title
            $jsonItems = array_values($byCodeExisting);
            usort($jsonItems, function($a,$b){
                return strcasecmp((string)$a['title'], (string)$b['title']);
            });

            // Update totals
            $allowances_total = (float)($pd['allowances_total'] ?? 0);
            $deductions_total = (float)($pd['deductions_total'] ?? 0) + round($newSum, 2);

            $basic   = (float)($pd['basic_salary'] ?? 0);
            $ot      = (float)($pd['overtime_amount'] ?? 0);
            $bonus   = (float)($pd['bonus_amount'] ?? 0);
            $comm    = (float)($pd['commission_amount'] ?? 0);
            $other   = (float)($pd['other_earnings'] ?? 0);
            $arrears = (float)($pd['arrears_amount'] ?? 0);

            $gross = round($basic + $allowances_total + $ot + $bonus + $comm + $other + $arrears, 2);

            $leave  = (float)($pd['leave_deduction'] ?? 0);
            $tax    = (float)($pd['tax_amount'] ?? 0);
            $pf     = (float)($pd['pf_deduction'] ?? 0);
            $loan   = (float)($pd['loan_total_deduction'] ?? 0);
            $adv    = (float)($pd['advance_total_deduction'] ?? 0);

            $total_deductions = round($deductions_total + $leave + $tax + $pf + $loan + $adv, 2);
            $net = max(0, round($gross - $total_deductions, 2));

            // Persist back to payroll_details (only this row)
            $this->db->where('id', (int)$pd['id'])->update($pdTbl, [
                'monthly_input_deductions_json' => json_encode($jsonItems),
                'deductions_total'              => $deductions_total,
                'gross_pay'                     => $gross,
                'net_pay'                       => $net,
                'updated_at'                    => $this->now(),
            ]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
