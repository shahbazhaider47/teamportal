<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
  $pd  = $pd ?? [];
  $CUR = html_escape(get_base_currency_symbol());

  // helpers
  $e = function($v){ return html_escape((string)$v); };
  $fmtDate = function($d){
      if (empty($d)) return null;
      $t = strtotime($d);
      return $t ? date('F d, Y', $t) : null;
  };
  $fmtDateYmd = function($d){
      if (empty($d)) return null;
      $t = strtotime($d);
      return $t ? date('Y-m-d', $t) : null;
  };
  $m = function($v) use ($CUR){ return $CUR.' '.number_format((float)$v, 2); };

  // employee display
  $employee_name = trim((string)($pd['employee_name'] ?? $pd['fullname'] ?? ''));
  if ($employee_name === '') { $employee_name = 'UID:'.(int)($pd['user_id'] ?? 0); }
  $emp_code      = (string)($pd['emp_id'] ?? '');

  // payroll dates
  $pstart   = $fmtDate($pd['period_start'] ?? null);
  $pend     = $fmtDate($pd['period_end']   ?? null);
  $paydate  = $fmtDate($pd['pay_date']     ?? null) ?: '—';

    $ps = $pd['period_start'] ?? null;
    $pe = $pd['period_end']   ?? null;
    
    if ($ps && $pe && strtotime($ps) && strtotime($pe)) {
        // Same-month period → "August, 2025"; cross-month → "August, 2025 – September, 2025"
        $sm = date('Ym', strtotime($ps));
        $em = date('Ym', strtotime($pe));
        $salary_month = ($sm === $em)
            ? date('F, Y', strtotime($ps))
            : date('F, Y', strtotime($ps)) . ' – ' . date('F, Y', strtotime($pe));
    } elseif (!empty($pd['pay_date']) && strtotime($pd['pay_date'])) {
        // Fallback to pay_date if period is missing
        $salary_month = date('F, Y', strtotime($pd['pay_date']));
    } else {
        // Final fallback: current month
        $salary_month = date('F, Y');
    }

// earnings, deductions, totals (fall back if missing)
$crnslry = (float)($pd['current_salary'] ?? 0);

// --- New policy breakdown ---
// 90% Basic, 5% House Rent, 5% Food Allowance (all based on current_salary)
$house_rent      = round($crnslry * 0.05, 2);
$food_allowance  = round($crnslry * 0.05, 2);
$basic           = round($crnslry - ($house_rent + $food_allowance), 2); // = 90%

// If you have other allowance items, add them here (keep at 0 if none)
$other_allow     = 0.0; // or: (float)($pd['allowances_total'] ?? 0);

// Compose total allowances per policy
$allow = round($house_rent + $food_allowance + $other_allow, 2);

// Keep your existing ancillary earnings
$ot_amt = (float)($pd['overtime_amount']   ?? 0);
$bonus  = (float)($pd['bonus_amount']      ?? 0);
$comm   = (float)($pd['commission_amount'] ?? 0);
$otherE = (float)($pd['other_earnings']    ?? 0);

// Gross pay: allow override if provided, else compute
$computed_gross = $basic + $allow + $ot_amt + $bonus + $comm + $otherE;
$gross = isset($pd['gross_pay']) && is_numeric($pd['gross_pay'])
    ? (float)$pd['gross_pay']
    : $computed_gross;

// (Optional) expose the derived numbers for downstream views/templates
$pd['basic_salary']     = $basic;
$pd['house_rent']       = $house_rent;
$pd['food_allowance']   = $food_allowance;
$pd['allowances_total'] = $allow;


  $leave_deduc = (float)($pd['leave_deduction'] ?? 0);
  $tax         = (float)($pd['tax_amount']      ?? 0);
  $pf_emp      = (float)($pd['pf_employee']     ?? 0);
  $pf_ded      = isset($pd['pf_deduction']) ? (float)$pd['pf_deduction'] : $pf_emp;
  $loan_ded    = (float)($pd['loan_total_deduction']    ?? 0);
  $adv_ded     = (float)($pd['advance_total_deduction'] ?? 0);
  $std_ded     = (float)($pd['deductions_total']        ?? 0);
  $sec_ded     = (float)($pd['security_deduction']      ?? 0);
  $late_mins   = (float)($pd['late_minutes']            ?? 0);
  $late_amt    = (float)($pd['late_amount']             ?? 0);
  $unpaid_days = (float)($pd['leave_unpaid_days']       ?? 0);

  $total_deductions = isset($pd['total_deductions']) ? (float)$pd['total_deductions']
                     : ($std_ded + $leave_deduc + $tax + $pf_ded + $loan_ded + $adv_ded + $sec_ded + $late_amt);

  $net = isset($pd['net_pay']) ? (float)$pd['net_pay'] : max(0, $gross - $total_deductions);

  // PF employer + totals
  $pf_empr  = (float)($pd['pf_employer'] ?? 0);
  $pf_total = $pf_emp + $pf_empr;
// AFTER: prefer ledger balance, fallback to this month’s total if missing
$pf_total = isset($pd['pf_current_balance'])
    ? (float)$pd['pf_current_balance']
    : ($pf_emp + $pf_empr);
  // extra identity bits (optional from users table)
  $designation = $pd['designation'] ?? '';
  $department  = $pd['department_name'] ?? ($pd['department'] ?? '');
  $email       = $pd['work_email']  ?? ($pd['email'] ?? '');
  $join_date   = $fmtDate($pd['emp_joining'] ?? null);

  // bank display (optional)
  $bank_line_1 = trim(($pd['bank_name'] ?? '').($pd['bank_branch'] ? ' — '.$pd['bank_branch'] : ''));
  $bank_ac     = $pd['bank_account_number'] ?? '';
  $bank_disp   = $bank_line_1 !== '' ? $bank_line_1 : ($bank_ac !== '' ? $bank_ac : 'Cash');

  $payslip_no  = !empty($pd['payslip_number']) ? $pd['payslip_number'] : ('PS-'.(int)($pd['id'] ?? 0));

    
    $dynAllow = [];
    if (!empty($pd['allowances_breakdown_json'])) {
      $tmp = json_decode($pd['allowances_breakdown_json'], true);
      if (is_array($tmp)) $dynAllow = $tmp;
    }

?>

<style>
/* Page + print */

.payslip-wrap{
    width: auto;
}
.payslip { background: #fff; }
.ps-titlebar {
  background:#056464; color:white; text-transform:uppercase; letter-spacing:.5px;
  font-weight:700; text-align:center; padding:.4rem 0.8rem; margin-bottom: 20px; border-radius:.3rem;
}
.section-head {
  font-weight:700; text-transform:uppercase;
  padding:.35rem .6rem; margin:.75rem 0 .5rem; border: 1px; border-radius:.25rem;
  font-size:.8rem; letter-spacing:.3px;
}
.ps-row { display:flex; gap:20px; }
.ps-col { flex:1; min-width:0; }
.kv { display:flex; align-items:justify; gap:.75rem; margin:.2rem 0; }
.kv .k { width:56%; font-weight:500; font-size:12px;}
.kv .v {
  flex:1; display:flex; align-items:center; justify-content:space-between;
  border-bottom: 1px solid #cfcfcf; min-height:20px; padding:0 .25rem; font-size:11px;
}
.kv .v span { font-weight:400; }
.tot-row { display:flex; justify-content:space-between; font-weight:700; margin-top:.80rem; padding: 4px; border-radius: 5px; color: #056464;}
.tot-row .label { font-size:13px; }
.tot-row .val { font-size:13px; }
.table-box { border:1px solid #e5e5e5; border-radius:.35rem; padding:.6rem .75rem; }
.mid-gap { margin-top:.8rem; }
.notice {
  font-size:12px; color:#333; padding:.6rem .75rem; border-top:1px solid #ddd;
}
.sig-line {
  display:flex; align-items:center; justify-content:space-between; gap:2rem; margin-top:1rem; margin-bottom:1rem; padding: 10px;
}
.sig {
  flex:0 0 46%; border-bottom:1px solid #222; height:35px;
}
.meta-pair { display:flex; gap:2rem; margin-top: 1rem; margin-bottom: 1rem; }
.meta-pair .meta { flex:1; }

@media print {
  .page-header, .btn, .icon-btn { display:none !important; }
  body { background:#fff; }
  .payslip-wrap { max-width:none; }
}

</style>

<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?> > <span class="text-muted small"><?= $e($employee_name) ?> (<?= $emp_code ? $e($emp_code) : '—' ?>)</span></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canPrint   = staff_can('print', 'general');
        ?>
        
        <div class="btn-divider"></div>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn"
                  title="Print Table"
                  onclick="window.print()">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>
<div class="row">
<div class="col-md-9">
<div class="payslip-wrap">
  <div class="card payslip mt-3">
    <div class="card-body">
        <div class="ps-titlebar">Personal Information</div>        
      <!-- Personal info two columns -->
      <div class="ps-row">
        <div class="ps-col">
          <div class="kv"><div class="k">EMP ID</div><div class="v"><span><?= $emp_code ? $e($emp_code) : '—' ?></span></div></div>            
          <div class="kv"><div class="k">Employee Name</div><div class="v"><span><?= $e($employee_name) ?></span></div></div>
          <div class="kv"><div class="k">Designation</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
          <div class="kv"><div class="k">Department</div><div class="v"><span><?= $e($department ?: '—') ?></span></div></div>
          <div class="kv"><div class="k">Work Email</div><div class="v"><span><?= $e($email ?: '—') ?></span></div></div>
        </div>
        
        <div class="ps-col">
          <div class="kv"><div class="k">Date of Joining</div><div class="v"><span><?= $join_date ? $e($join_date) : '—' ?></span></div></div>            
          <div class="kv"><div class="k">Salary Month</div><div class="v"><span><?= $e($salary_month) ?></span></div></div>
          <div class="kv"><div class="k">Payment Date</div><div class="v"><span><?= $e($paydate) ?></span></div></div>
          <div class="kv"><div class="k">Bank Account Number</div><div class="v"><span><?= $e($bank_ac) ?></span></div></div>
          <div class="kv"><div class="k">Payslip ID / No</div><div class="v"><span><?= $e($payslip_no) ?></span></div></div>            
        </div>
      </div>

      <!-- Pay & Deductions -->
      <div class="ps-row mid-gap">
        <div class="ps-col">
          <div class="section-head bg-light-primary">Pay &amp; Allowances</div>
          <div class="table-box">
            <div class="kv"><div class="k">Basic Salary</div><div class="v"><span><?= $m($basic) ?></span></div></div>
            <div class="kv"><div class="k">House Rent</div><div class="v"><span><?= $m($pd['house_rent'] ?? 0) ?></span></div></div>
            <div class="kv"><div class="k">Food</div><div class="v"><span><?= $m($pd['food_allowance'] ?? 0) ?></span></div></div>
            <?php if (!empty($dynAllow)): ?>
              <?php foreach ($dynAllow as $al): ?>
                <div class="kv">
                  <div class="k"><?= html_escape($al['title']) ?></div>
                  <div class="v"><span><?= $CUR.' '.number_format((float)$al['amount'], 2) ?></span></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            <div class="kv"><div class="k">Overtime</div><div class="v"><span><?= $m($ot_amt) ?> </span><span><?= $e((string)($pd['overtime_hours'] ?? 0)) ?></span></div></div>            
            <div class="kv"><div class="k">Arrears / Previous</div><div class="v"><span><?= $m($pd['arrears_amount'] ?? 0) ?></span></div></div>
            <div class="tot-row"><div class="label">Sub Total:</div><div class="val"><?= $m($gross) ?></div></div>
          </div>
        </div>

        <div class="ps-col">
          <div class="section-head bg-light-primary">Deductions</div>
          <div class="table-box">
            <div class="kv"><div class="k">Leaves Deductions</div><div class="v"><span><?= $m($leave_deduc) ?></span><span><small class="text-danger"><?= $e((string)$unpaid_days) ?> - Non Approved</small></span></div></div>
            <div class="kv"><div class="k">PF Deduction</div><div class="v"><span><?= $m($pf_ded) ?></span></div></div>
            <div class="kv"><div class="k">No Pay Amount</div><div class="v"><span><?= $m($leave_deduc) ?></span></div></div>
            <div class="kv"><div class="k">TAX Deduction</div><div class="v"><span><?= $m($tax) ?></span></div></div>
            <div class="kv"><div class="k">Advance Deduction</div><div class="v"><span><?= $m($adv_ded) ?></span></div></div>
            <div class="kv"><div class="k">Late Minutes</div><div class="v"><span><?= $m($late_amt) ?> </span><span><small class="text-danger"><?= $e((string)$late_mins) ?> - Monthly</small></span></div></div>
            <div class="kv"><div class="k">Loan Installment</div><div class="v"><span><?= $m($loan_ded) ?></span></div></div>
            <div class="kv"><div class="k">Security Deductions</div><div class="v"><span><?= $m($sec_ded) ?></span></div></div>

            <div class="tot-row"><div class="label">Sub Total:</div><div class="val"><?= $m($total_deductions) ?></div></div>
          </div>
        </div>
      </div>

      <!-- PF + Loan boxes -->
      <div class="ps-row mid-gap">
        <div class="ps-col">
        <div class="section-head bg-light-primary">PF Details</div>
        <div class="table-box">
          <div class="kv"><div class="k">EMP Contribution</div><div class="v"><span><?= $m($pf_emp) ?></span></div></div>
          <div class="kv"><div class="k">Company Contribution</div><div class="v"><span><?= $m($pf_empr) ?></span></div></div>
          <div class="kv"><div class="k">Total PF Deductions</div><div class="v"><span><?= $m($pf_ded) ?></span></div></div>
          <div class="tot-row"><div class="label">Total PF Amount:</div><div class="val"><?= $m($pf_total) ?></div></div>
        </div>
        </div>

        <div class="ps-col">
          <div class="section-head bg-light-primary">Loan Details</div>
          <div class="table-box">
            <div class="kv"><div class="k">Total Loan Taken</div><div class="v"><span><?= $m($pd['loan_taken_total'] ?? 0) ?></span></div></div>
            <div class="kv"><div class="k">Current Installment</div><div class="v"><span><?= $m($loan_ded) ?></span></div></div>
            <div class="kv"><div class="k">Total Paid</div><div class="v"><span><?= $m($pd['loan_total_paid'] ?? 0) ?></span></div></div>
            <div class="tot-row"><div class="label">Balance:</div><div class="val"><?= $m(max(0, (float)($pd['loan_balance_total'] ?? 0))) ?></div></div>
          </div>
        </div>
      </div>

      <!-- Income tax + Net payable -->
      <div class="ps-row mid-gap">
        <div class="ps-col">
          <div class="section-head bg-light-primary">Income Tax Details</div>
          <div class="table-box">
            <div class="kv"><div class="k">Current Tax Amount</div><div class="v"><span><?= $m($tax) ?></span></div></div>
            <div class="kv"><div class="k">Total Tax Paid</div><div class="v"><span><?= $m($pd['tax_paid_ytd'] ?? 0) ?></span></div></div>
            <div class="tot-row"><div class="label">Sub Total:</div><div class="val"><?= $m($pd['tax_paid_ytd'] ?? 0) ?></div></div>
          </div>
        </div>

        <div class="ps-col">
          <div class="section-head bg-light-primary">Net Payable</div>
          <div class="table-box">
            <div class="kv"><div class="k">Gross Salary</div><div class="v"><span><?= $m($gross) ?></span></div></div>
            <div class="kv"><div class="k">Total Deductions</div><div class="v"><span><?= $m($total_deductions) ?></span></div></div>
            <div class="tot-row" style="font-size:1.15rem"><div class="label">Net Salary</div><div class="val"><?= $m($net) ?></div></div>
          </div>
        </div>
      </div>

      <!-- Footer meta + signature -->
      <div class="sig-line">
        <div class="meta text-primary"><strong>HR / Accounts Sign & Stamp</strong><div class="sig"></div></div>          
        <div class="meta text-primary"><strong>EMP Signature</strong><div class="sig"></div></div>
      </div>

      <div class="notice mt-3 small text-primary">
        <strong>NOTICE:</strong> The information contained in this slip is confidential and intended only for the personal and confidential use of the designated recipient(s) only.
      </div>
    </div>
  </div>
  </div>
  </div>
  
        <div class="col-md-3">
            <div class="payslip-wrap">
              <div class="card payslip mt-3">
                <div class="card-body">
                <div class="ps-titlebar bg-info">Employee Record / Previous</div>        
                  <div class="ps-row">
                    <div class="ps-col">
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Employment Status</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Probation Status</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Probation End Date</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Confirmation Date</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Last Increment Date</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>            
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Year of Service</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Notice Period</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Last Promotion Date</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Next Review Date</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Annual Leave Balance</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                      <div class="kv"><div class="k"><i class="ti ti-circle-filled fs-8 small me-2 text-light"></i> Last Leave Taken</div><div class="v"><span><?= $e($designation ?: '—') ?></span></div></div>
                    </div>
                  </div>
                </div>  
              </div>  
            </div>  
        </div>  
    </div>  
</div>