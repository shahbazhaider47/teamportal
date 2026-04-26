<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $CUR = html_escape(get_base_currency_symbol()); ?>

<!-- Optional launcher button (put this where your header shortcuts render) -->
<button type="button"
        class="btn btn-primary btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#requestLoanModal">
  <i class="ti ti-cash me-1"></i> Request Loan
</button>

<!-- Request Loan Modal -->
<div class="modal fade" id="requestLoanModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-top">
    <form method="post"
          action="<?= site_url('payroll/request-loan') ?>"
          class="modal-content app-form"
          id="requestLoanForm">
      <?php // CSRF (only if enabled in your CI config)
      if (function_exists('get_instance')) {
          $CI =& get_instance();
          if (!empty($CI->security)) {
              echo '<input type="hidden" name="'.$CI->security->get_csrf_token_name().'" value="'.$CI->security->get_csrf_hash().'">';
          }
      } ?>

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-cash me-2"></i> Request a Loan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Loan Amount <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><?= $CUR ?></span>
              <input type="number" class="form-control" name="loan_taken" id="rq_loan_taken" min="0" step="0.01" required>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Payback Type <span class="text-danger">*</span></label>
            <select name="payback_type" id="rq_payback_type" class="form-select" required>
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="from_salary">From Salary</option>
              <option value="custom">Custom</option>
            </select>
          </div>

          <div class="col-md-4 rq-plan-fields">
            <label class="form-label">Total Installments <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="total_installments" id="rq_total_installments" min="0">
          </div>

          <div class="col-md-4 rq-plan-fields">
            <label class="form-label">Monthly Installment</label>
            <div class="input-group">
              <span class="input-group-text"><?= $CUR ?></span>
              <input type="number" class="form-control" name="monthly_installment" id="rq_monthly_installment" step="0.01">
            </div>
          </div>

          <div class="col-md-8">
            <label class="form-label">Notes (optional)</label>
            <textarea name="notes" id="rq_notes" rows="2" class="form-control" placeholder="Why you need this loan, preferred payback, etc."></textarea>
          </div>

          <!-- Hidden: these will be computed or defaulted server-side -->
          <input type="hidden" name="total_paid" value="0">
          <input type="hidden" name="current_installment" value="0">
          <input type="hidden" name="balance" value="0">
          <input type="hidden" name="start_date" value="">
          <input type="hidden" name="end_date" value="">
        </div>

        <div class="alert alert-info small mt-3 mb-0">
          Your request will be submitted as <strong>requested</strong> and routed for approval. Payroll will review and respond.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="ti ti-send me-1"></i> Submit Request
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  function valNum(id){ const el=document.getElementById(id); return el? (parseFloat(el.value)||0):0; }
  function setVal(id,v){ const el=document.getElementById(id); if(el) el.value=v; }

  function togglePlanFields(){
    const type = document.getElementById('rq_payback_type')?.value || 'monthly';
    const show = (type === 'monthly' || type === 'quarterly');
    document.querySelectorAll('.rq-plan-fields').forEach(el => el.classList.toggle('d-none', !show));

    // Ensure inputs are enabled/disabled for posting cleanliness
    document.getElementById('rq_total_installments')?.toggleAttribute('disabled', !show);
    document.getElementById('rq_monthly_installment')?.toggleAttribute('disabled', !show);

    // Recalc if visible
    if (show) recalcMonthly();
  }

  function recalcMonthly(){
    const type  = document.getElementById('rq_payback_type')?.value || 'monthly';
    const loan  = valNum('rq_loan_taken');
    const total = Math.max(0, parseInt(document.getElementById('rq_total_installments')?.value || 0, 10) || 0);
    if (total <= 0) return;

    let monthly = loan / total;
    if (type === 'quarterly') monthly = (loan / total) / 3;
    setVal('rq_monthly_installment', monthly.toFixed(2));
  }

  ['rq_payback_type','rq_loan_taken','rq_total_installments'].forEach(id=>{
    document.getElementById(id)?.addEventListener('input', function(){
      togglePlanFields(); recalcMonthly();
    });
    document.getElementById(id)?.addEventListener('change', function(){
      togglePlanFields(); recalcMonthly();
    });
  });

  // Init on load
  document.addEventListener('DOMContentLoaded', function(){
    togglePlanFields();
  });
})();
</script>
