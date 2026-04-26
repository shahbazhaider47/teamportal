<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CUR = html_escape(get_base_currency_symbol()); ?>

<?php
  // from controller
  $can_request     = $can_request ?? false;
  $eligibility_msg = $eligibility_msg ?? '';
  $max_amount      = isset($max_amount) ? $max_amount : null;
  $limit_code      = $limit_code ?? 'half_salary';
  $limit_label = [
    'half_salary'  => 'Maximum amount half salary',
    'full_salary'  => 'Maximum amount 1 salary',
    'two_salaries' => 'Maximum amount 2 salaries)',
    'any_amount'   => 'Any Amount',
  ][$limit_code] ?? ucfirst(str_replace('_',' ', $limit_code));
?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <h1 class="h6 header-title"><?= e($page_title ?? 'My Loans') ?></h1>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button"
              class="btn btn-primary btn-header"
              data-bs-toggle="modal"
              data-bs-target="#requestLoanModal"
              id="btnRequestLoan"
              <?= $can_request ? '' : 'disabled aria-disabled="true"' ?>>
        <i class="ti ti-cash me-1"></i> Request Loan
      </button>
    </div>
  </div>

  <?php if (!$can_request && !empty($eligibility_msg)): ?>
    <div class="alert alert-warning small">
      <i class="ti ti-info-circle me-1"></i><?= e($eligibility_msg) ?>
    </div>
  <?php endif; ?>

<div class="card">
  <div class="card-body table-responsive">
  <p class="text-muted small mb-4">Submit a loan request with repayment via installments — subject to management approval.</p>      
    <table class="table table-sm table-bottom-border small align-middle" id="<?= e($table_id ?? 'myLoansTable') ?>">
      <thead class="bg-light-primary">
        <tr>
          <th>Loan Taken</th>
          <th>Payback Type</th>
          <th>Total Inst.</th>
          <th>Monthly Inst.</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Status</th>
          <th>Duration</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($loans)): foreach ($loans as $row): ?>
        <tr>
          <td><?= $CUR . ' ' . number_format((float)$row['loan_taken'], 2) ?></td>
          <td><?= e(ucfirst(str_replace('_',' ', (string)$row['payback_type']))) ?></td>
          <td><?= (int)$row['total_installments'] ?></td>
          <td><?= $CUR . ' ' . number_format((float)$row['monthly_installment'], 2) ?></td>
          <td><?= $CUR . ' ' . number_format((float)$row['total_paid'], 2) ?></td>
          <td><?= $CUR . ' ' . number_format((float)$row['balance'], 2) ?></td>
          <td>
            <?php
              $s = (string)($row['status'] ?? '');
              $badge = 'bg-secondary';
              if ($s === 'requested') $badge = 'bg-warning text-dark';
              elseif ($s === 'active') $badge = 'bg-success';
              elseif ($s === 'paid') $badge = 'bg-primary';
              elseif ($s === 'defaulted') $badge = 'bg-danger';
            ?>
            <span class="badge <?= $badge ?>"><?= e(ucfirst($s)) ?></span>
          </td>
            <?php
            $fmtDateRaw = function ($d) {
                if (empty($d)) return null;
                $d = trim((string)$d);
                if ($d === '0000-00-00' || $d === '0000-00-00 00:00:00') return null;
                $t = strtotime($d);
                return $t ? date('Y-m-d', $t) : null;
            };
            
            $start = $fmtDateRaw($row['start_date'] ?? null);
            $end   = $fmtDateRaw($row['end_date'] ?? null);
            ?>
            <td>
              <?php if (!$start && !$end): ?>
                N/A
              <?php elseif ($start && $end): ?>
                <?= e($start) ?> <strong>To:</strong> <?= e($end) ?>
              <?php else: ?>
                <?= e($start ?: $end) ?>
              <?php endif; ?>
            </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="10" class="text-center text-muted py-4">No loans yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<!-- Request Loan Modal -->
<div class="modal fade" id="requestLoanModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-top">
    <form method="post"
          action="<?= site_url('payroll/my/request_loan_submit') ?>"
          class="modal-content app-form"
          id="requestLoanForm">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-cash me-2"></i> Request a Loan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Loan Amount <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><?= $CUR ?></span>
<input type="number"
       class="form-control"
       name="loan_taken"
       id="rq_loan_taken"
       min="0"
       step="0.01"
       <?= ($max_amount !== null && $max_amount > 0)
            ? 'max="'.e(number_format((float)$max_amount, 2, '.', '')).'"'
            : '' ?>
       required>
            </div>
            <small class="text-muted">
              <?= e($limit_label) ?>
              <?php if ($max_amount !== null && $max_amount > 0): ?>
                (Max <?= $CUR . ' ' . number_format((float)$max_amount, 2) ?>)
              <?php endif; ?>
            </small>
          </div>

          <div class="col-md-6">
            <label class="form-label">Payback Type <span class="text-danger">*</span></label>
            <select name="payback_type" id="rq_payback_type" class="form-select" required>
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="from_salary">From Salary</option>
              <option value="custom">Custom</option>
            </select>
          </div>

          <div class="col-md-6 rq-plan-fields">
            <label class="form-label">Total Installments</label>
            <input type="number" class="form-control" name="total_installments" id="rq_total_installments" min="0">
          </div>

          <div class="col-md-6 rq-plan-fields">
            <label class="form-label">Monthly Installment</label>
            <div class="input-group">
              <span class="input-group-text"><?= $CUR ?></span>
              <input type="number" class="form-control" name="monthly_installment" id="rq_monthly_installment" step="0.01">
            </div>
          </div>

          <div class="col-md-12">
            <label class="form-label">Notes / Reason <span class="text-danger">*</span></label>
            <textarea name="notes" id="rq_notes" rows="2" class="form-control" required></textarea>
          </div>

          <!-- hidden defaults ... -->
        </div>
        <div class="alert alert-info small mt-3 mb-0">
          Your request will be submitted as <strong>requested</strong> and sent for approval.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" <?= $can_request ? '' : 'disabled' ?>>
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
    const ti = document.getElementById('rq_total_installments');
    const mi = document.getElementById('rq_monthly_installment');
    if (ti) ti.disabled = !show;
    if (mi) mi.disabled = !show;
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

  // Re-check max each time; do nothing if max <= 0 or not set
  const loanInput = document.getElementById('rq_loan_taken');
  function clampMax(){
    if (!loanInput) return;
    const maxAttr = loanInput.getAttribute('max');
    if (!maxAttr) return;
    const max = parseFloat(maxAttr);
    if (!isFinite(max) || max <= 0) return; // <-- don't clamp on 0.00
    const v = parseFloat(loanInput.value || 0);
    if (v > max) loanInput.value = max.toFixed(2);
  }

  ['rq_payback_type','rq_loan_taken','rq_total_installments'].forEach(id=>{
    document.getElementById(id)?.addEventListener('input', function(){
      togglePlanFields(); recalcMonthly(); clampMax();
    });
    document.getElementById(id)?.addEventListener('change', function(){
      togglePlanFields(); recalcMonthly(); clampMax();
    });
  });

  document.addEventListener('DOMContentLoaded', togglePlanFields);
})();
</script>

