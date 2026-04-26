<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>


<div class="card-body">
  <div class="mb-4">
    <h5 class="card-title">Payroll Settings</h5>
    <p class="text-muted">Configure payroll rules for loans, taxes, working days, increments, and overtime.</p>
  </div>

<?php
// Normalize the incoming settings array to $S.
// The settings/all.php template passes "existing_data" to module views.
$S = [];
if (isset($existing) && is_array($existing)) {
  $S = $existing;
} elseif (isset($existing_data) && is_array($existing_data)) {
  $S = $existing_data;
}

// Now use $S[...] everywhere below
$allowLoan              = $S['payroll_allow_loan']            ?? '';
$loanTaxable            = $S['payroll_loan_taxable']          ?? '';
$loanEligibility        = $S['payroll_loan_eligibility']      ?? '';
$loanLimit              = $S['payroll_loan_limit']            ?? '';
$workingDays            = $S['payroll_total_working_days']    ?? '';
$salaryTax              = $S['payroll_salary_tax']            ?? 'disabled';
$salaryTaxType          = $S['payroll_salary_tax_type']       ?? 'percentage';
$salaryTaxPercent       = $S['payroll_salary_tax_percent']    ?? '';
$salaryTaxFixed         = $S['payroll_salary_tax_fixed']      ?? '';
$increments             = $S['payroll_company_increments']    ?? '';
$overtimeRate           = $S['payroll_overtime_rate']         ?? '';
$allowAdvance           = $S['payroll_allow_advance_salary']  ?? '';
$minServiceInc          = $S['payroll_min_service_for_increment'] ?? '';
$pfEnabled              = $S['payroll_pf_enabled']            ?? '';
$defaultPaymentMethod   = $S['payroll_default_payment_method'] ?? '';
$payslipAccess          = $S['payroll_payslip_access']         ?? '';
$payslipTemplate        = $S['payroll_payslip_template']       ?? '';
$bonusCalculation       = $S['payroll_bonus_calculation']      ?? '';
$leaveEncashment        = $S['payroll_leave_encashment']       ?? '';
$leave_threshold        = $S['payroll_leave_threshold']        ?? '';
$pfEmployeePercentage   = $S['payroll_pf_employee_percentage']      ?? '';
$pfEmployerPercentage   = $S['payroll_pf_employer_percentage']       ?? '';

?>


  <!-- Basic Settings Section -->
  <div class="settings-section mb-4">
    <h6 class="section-title">Basic Settings</h6>
    <div class="row app-form">
      <div class="col-md-6 mb-3">
        <label class="form-label">Total Working Days <small class="badge bg-light-primary p-0 px-2">Per Month</small></label>
        <input type="number" min="1" max="31" name="settings[payroll_total_working_days]" class="form-control" value="<?= e($workingDays) ?>" placeholder="Default 26">
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Overtime Rate <small class="badge bg-light-primary p-0 px-2">Per Hour</small></label>
        <select name="settings[payroll_overtime_rate]" class="form-select">
          <option value="0"     <?= $overtimeRate === '0'     ? 'selected' : '' ?>>None / Disabled</option>
          <option value="1.0"   <?= $overtimeRate === '1.0'   ? 'selected' : '' ?>>1.0x (Regular Pay)</option>
          <option value="1.5"   <?= $overtimeRate === '1.5'   ? 'selected' : '' ?>>1.5x (Time and a Half)</option>
          <option value="2.0"   <?= $overtimeRate === '2.0'   ? 'selected' : '' ?>>2.0x (Double Time)</option>
          <option value="3.0"   <?= $overtimeRate === '3.0'   ? 'selected' : '' ?>>3.0x (Tripple Time)</option>
        </select>        
      </div>
    </div>
  </div>

  <!-- Salary Increment Section -->
  <div class="settings-section mb-4">
    <h6 class="section-title">Salary Increment Settings</h6>
    <div class="row app-form">
      <div class="col-md-6 mb-3">
        <label class="form-label">Increment Frequency</label>
        <select name="settings[payroll_company_increments]" class="form-select">
          <option value="monthly"        <?= $increments === 'monthly'        ? 'selected' : '' ?>>Monthly</option>
          <option value="quarterly"      <?= $increments === 'quarterly'      ? 'selected' : '' ?>>Quarterly</option>
          <option value="bi_annual"      <?= $increments === 'bi_annual'      ? 'selected' : '' ?>>Bi-Annual</option>
          <option value="annual"         <?= $increments === 'annual'         ? 'selected' : '' ?>>Annually</option>
          <option value="not_applicable" <?= $increments === 'not_applicable' ? 'selected' : '' ?>>Not Applicable</option>
        </select>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Minimum Service Period for Increment</label>
        <select name="settings[payroll_min_service_for_increment]" class="form-select">
          <option value="none" <?= $minServiceInc === 'none' ? 'selected' : '' ?>>None</option>
          <option value="3m"   <?= $minServiceInc === '3m'   ? 'selected' : '' ?>>3 Months</option>
          <option value="6m"   <?= $minServiceInc === '6m'   ? 'selected' : '' ?>>6 Months</option>
          <option value="12m"  <?= $minServiceInc === '12m'  ? 'selected' : '' ?>>12 Months</option>
          <option value="18m"  <?= $minServiceInc === '18m'  ? 'selected' : '' ?>>18 Months</option>
          <option value="24m"  <?= $minServiceInc === '24m'  ? 'selected' : '' ?>>24 Months</option>
        </select>
      </div>
    </div>
  </div>

<!-- Loan Settings Section -->
<div class="settings-section mb-4">
  <h6 class="section-title">Loan Settings</h6>
  <div class="row app-form">
    <div class="col-md-4 mb-3">
      <label class="form-label">Allow Loan Request</label>
      <select name="settings[payroll_allow_loan]" class="form-select" id="allowLoanSelect">
        <option value="yes" <?= $allowLoan === 'yes' ? 'selected' : '' ?>>Yes</option>
        <option value="no"  <?= $allowLoan === 'no'  ? 'selected' : '' ?>>No</option>
      </select>
    </div>

    <div class="col-md-4 mb-3 loan-field" style="<?= $allowLoan === 'no' ? 'display: none;' : '' ?>">
      <label class="form-label">Loan Taxable</label>
      <select name="settings[payroll_loan_taxable]" class="form-select">
        <option value="yes" <?= $loanTaxable === 'yes' ? 'selected' : '' ?>>Yes</option>
        <option value="no"  <?= $loanTaxable === 'no'  ? 'selected' : '' ?>>No</option>
      </select>
    </div>

    <div class="col-md-4 mb-3 loan-field" style="<?= $allowLoan === 'no' ? 'display: none;' : '' ?>">
      <label class="form-label">Loan Eligibility</label>
      <select name="settings[payroll_loan_eligibility]" class="form-select">
        <option value="on_joining"      <?= $loanEligibility === 'on_joining'     ? 'selected' : '' ?>>On Joining</option>
        <option value="after_probation" <?= $loanEligibility === 'after_probation'? 'selected' : '' ?>>After Probation</option>
        <option value="after_6_months"  <?= $loanEligibility === 'after_6_months' ? 'selected' : '' ?>>After 6 Months</option>
        <option value="after_1_year"    <?= $loanEligibility === 'after_1_year'   ? 'selected' : '' ?>>After 1 Year</option>
      </select>
    </div>

    <div class="col-md-4 mb-3 loan-field" style="<?= $allowLoan === 'no' ? 'display: none;' : '' ?>">
      <label class="form-label">Loan Limit</label>
      <select name="settings[payroll_loan_limit]" class="form-select">
        <option value="half_salary" <?= $loanLimit === 'half_salary' ? 'selected' : '' ?>>Half Salary</option>
        <option value="full_salary" <?= $loanLimit === 'full_salary' ? 'selected' : '' ?>>Full Salary</option>
        <option value="two_salaries"<?= $loanLimit === 'two_salaries'? 'selected' : '' ?>>Maximum 2 Salaries</option>
        <option value="any_amount"  <?= $loanLimit === 'any_amount'  ? 'selected' : '' ?>>Any Amount</option>
      </select>
    </div>

    <div class="col-md-4 mb-3">
      <label class="form-label">Allow Advance Salary Requests</label>
      <select name="settings[payroll_allow_advance_salary]" class="form-select">
        <option value="yes" <?= $allowAdvance === 'yes' ? 'selected' : '' ?>>Yes</option>
        <option value="no"  <?= $allowAdvance === 'no'  ? 'selected' : '' ?>>No</option>
      </select>
    </div>
  </div>
</div>

  <!-- Tax Settings Section -->
  <div class="settings-section mb-4">
    <h6 class="section-title">Tax Settings</h6>
    <div class="row app-form">
      <div class="col-12">
        <div class="row g-3 align-items-end" id="salary_tax_row">
          <div class="col-md-4">
            <label class="form-label">Salary Tax</label>
            <select id="salary_tax_select" name="settings[payroll_salary_tax]" class="form-select">
              <option value="enabled"  <?= $salaryTax === 'enabled'  ? 'selected' : '' ?>>Enabled</option>
              <option value="disabled" <?= $salaryTax === 'disabled' ? 'selected' : '' ?>>Disabled</option>
            </select>
          </div>
        
          <div class="col-md-4" id="salary_tax_type_wrap">
            <label class="form-label">Salary Tax Type</label>
            <select id="salary_tax_type" name="settings[payroll_salary_tax_type]" class="form-select">
              <option value="percentage" <?= $salaryTaxType === 'percentage' ? 'selected' : '' ?>>Percentage</option>
              <option value="fixed"      <?= $salaryTaxType === 'fixed'      ? 'selected' : '' ?>>Fixed</option>
            </select>
          </div>
        
          <div class="col-md-4" id="salary_tax_percent_wrap">
            <label class="form-label">Percentage (%)</label>
            <input type="number" step="0.01" name="settings[payroll_salary_tax_percent]" class="form-control"
                   value="<?= e($salaryTaxPercent) ?>">
          </div>
        
          <div class="col-md-4" id="salary_tax_fixed_wrap">
            <label class="form-label">Fixed Amount</label>
            <input type="number" step="0.01" name="settings[payroll_salary_tax_fixed]" class="form-control"
                   value="<?= e($salaryTaxFixed) ?>">
          </div>
        </div>
      </div>
    </div>
  </div>

    <!-- Benefits Section -->
    <div class="settings-section mb-4">
      <h6 class="section-title">Employee Benefits</h6>
      <div class="row app-form align-items-end">
        <!-- Provident Fund -->
        <div class="col-md-4 mb-3">
          <label class="form-label">Provident Fund</label>
          <select name="settings[payroll_pf_enabled]" id="payroll_pf_enabled" class="form-select">
            <option value="yes" <?= $pfEnabled === 'yes' ? 'selected' : '' ?>>Enabled</option>
            <option value="no"  <?= $pfEnabled === 'no'  ? 'selected' : ''  ?>>Disabled</option>
          </select>
        </div>
    
        <!-- Employee Contribution -->
        <div class="col-md-4 mb-3 pf-extra-fields" style="display: none;">
          <label class="form-label">Employee Contribution (%)</label>
          <input type="number" step="0.01" min="0" max="100"
                 name="settings[payroll_pf_employee_percentage]"
                 value="<?= $pfEmployeePercentage ?? '' ?>"
                 class="form-control" placeholder="e.g. 5">
        </div>
    
        <!-- Employer Contribution -->
        <div class="col-md-4 mb-3 pf-extra-fields" style="display: none;">
          <label class="form-label">Employer Contribution (%)</label>
          <input type="number" step="0.01" min="0" max="100"
                 name="settings[payroll_pf_employer_percentage]"
                 value="<?= $pfEmployerPercentage ?? '' ?>"
                 class="form-control" placeholder="e.g. 10">
        </div>
      </div>
    </div>


  <hr class="mt-4">

  <!-- Additional Recommended Settings -->
  <div class="settings-section">
    <h6 class="section-title">Additional Settings</h6>
    <div class="row app-form">
      <div class="col-md-4 mb-3">
        <label class="form-label">Default Payment Method</label>
        <select name="settings[payroll_default_payment_method]" class="form-select">
          <option value="bank_transfer">Bank Transfer</option>
          <option value="cheque">Cheque</option>
          <option value="cash">Cash</option>
          <option value="digital_wallet">Digital Wallet</option>
        </select>
      </div>
      
      <div class="col-md-4 mb-3">
        <label class="form-label">Payslip Access for Employees</label>
        <select name="settings[payroll_payslip_access]" class="form-select">
          <option value="enabled">Enabled</option>
          <option value="disabled">Disabled</option>
        </select>
      </div>
      
      <div class="col-md-4 mb-3">
        <label class="form-label">Default Payslip Template</label>
        <select name="settings[payroll_payslip_template]" class="form-select">
          <option value="template1">Template 1</option>
          <option value="template2">Template 2</option>
          <option value="template3">Template 3</option>
        </select>
      </div>
      
      <div class="col-md-4 mb-3">
        <label class="form-label">Bonus Calculation Method</label>
        <select name="settings[payroll_bonus_calculation]" class="form-select">
          <option value="fixed_amount">Fixed Amount</option>
          <option value="percentage">Percentage of Basic</option>
          <option value="profit_based">Profit Based</option>
        </select>
      </div>
      
      <div class="col-md-4 mb-3">
        <label class="form-label">Leave Encashment Policy</label>
        <select name="settings[payroll_leave_encashment]" class="form-select">
          <option value="allowed">Allowed</option>
          <option value="not_allowed">Not Allowed</option>
          <option value="partial">Partial Encashment</option>
        </select>
      </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">Leave Encashment Threshold</label>
            <input type="number" step="1" name="settings[payroll_leave_threshold]" class="form-control"
                   value="<?= e($leave_threshold) ?>">
          </div>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const pfSelect = document.getElementById('payroll_pf_enabled');
  const extraFields = document.querySelectorAll('.pf-extra-fields');

  function togglePFFields() {
    extraFields.forEach(field => {
      field.style.display = (pfSelect.value === 'yes') ? 'block' : 'none';
    });
  }

  pfSelect.addEventListener('change', togglePFFields);
  togglePFFields(); // Run on load
});
</script>



<script>
document.getElementById('allowLoanSelect').addEventListener('change', function() {
  const loanFields = document.querySelectorAll('.loan-field');
  if (this.value === 'no') {
    loanFields.forEach(field => field.style.display = 'none');
  } else {
    loanFields.forEach(field => field.style.display = 'block');
  }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const taxSelect   = document.getElementById('salary_tax_select');
  const typeSelect  = document.getElementById('salary_tax_type');
  const typeWrap    = document.getElementById('salary_tax_type_wrap');
  const percentWrap = document.getElementById('salary_tax_percent_wrap');
  const fixedWrap   = document.getElementById('salary_tax_fixed_wrap');

  function refreshSalaryTaxUI() {
    const enabled = taxSelect?.value === 'enabled';
    typeWrap?.classList.toggle('d-none', !enabled);
    percentWrap?.classList.toggle('d-none', !enabled || typeSelect?.value !== 'percentage');
    fixedWrap?.classList.toggle('d-none', !enabled || typeSelect?.value !== 'fixed');
  }

  taxSelect?.addEventListener('change', refreshSalaryTaxUI);
  typeSelect?.addEventListener('change', refreshSalaryTaxUI);
  refreshSalaryTaxUI(); // initial
});
</script>