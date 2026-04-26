<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CUR = html_escape(get_base_currency_symbol()); ?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= e($page_title ?? 'Payroll') ?></h1>
    </div>

<?php
  $canExport    = staff_can('export', 'general');
  $canPrint     = staff_can('print', 'general');
?>
    <div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="<?= site_url('payroll') ?>" class="btn btn-light-primary btn-header b-r-4 ">
      <i class="ti ti-arrow-left"></i> Go Back
    </a> 

    <!-- Monthly Inputs button -->
    <button type="button"
            class="btn btn-secondary btn-header b-r-4"
            data-bs-toggle="modal"
            data-bs-target="#monthlyInputsModal"
            data-run-id="<?= (int)($summary['run_id'] ?? 0) ?>">
      <i class="ti ti-list-details me-1"></i> Monthly Inputs
    </button>
    
      <!-- Run Settings button -->
    <button type="button"
            class="btn btn-primary btn-header b-r-4"
            data-bs-toggle="modal"
            data-bs-target="#runAdminModal"
            data-run-id="<?= (int)($summary['run_id'] ?? 0) ?>">
      <i class="ti ti-adjustments-horizontal me-1"></i> Run Settings
    </button>
    
    
      <div class="btn-divider"></div>
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'hrmusersTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
      <?php if ($canExport): ?>
      <button type="button"
              class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
              title="Export to Excel"
              data-export-filename="<?= e($page_title ?? 'payroll_export') ?>">
        <i class="ti ti-download"></i>
      </button>
      <?php endif; ?>

      <?php if ($canPrint): ?>
      <button type="button"
              class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
              title="Print Table">
        <i class="ti ti-printer"></i>
      </button>
      <?php endif; ?>
    </div>
  </div>
  
    <div class="card shadow-sm mb-3 bg-light-primary">
      <div class="card-body small">
        <strong>Payroll Period:</strong> <?= e($summary['period_start'].' → '.$summary['period_end']) ?> &nbsp;|&nbsp;
        <strong>Pay Date:</strong> <?= e($summary['pay_date']) ?> &nbsp;|&nbsp;
        <strong>Employees Count:</strong> <?= (int)$summary['employees_count'] ?> &nbsp;|&nbsp;
        <strong>Gross Payroll:</strong> <?= $CUR .  number_format((float)$summary['sum_gross'], 2) ?> &nbsp;|&nbsp;
        <strong>Total Deductions:</strong> <?= $CUR .  number_format((float)$summary['sum_deductions'], 2) ?> &nbsp;|&nbsp;
        <strong>Net Payroll:</strong> <?= $CUR .  number_format((float)$summary['sum_net'], 2) ?>
      </div>
    </div>


  <div class="card shadow-sm">
    <div class="card-body">
    <table class="table table-sm table-bottom-border small align-middle" id="<?= e($table_id) ?>">
      <thead class="bg-light-primary">
        <tr>
          <th>Emp ID</th>
          <th>Emp Name</th>
          <th>Basic Salary</th>
          <th>Allowances</th>
          <th>OT Amount</th>
          <th>Bonus</th>
          <th>Leaves</th>
          <th>Other</th>
          <th>Arrears</th>
          <th>Gross</th>
          <th>Deductions</th>
          <th>Net</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $pd): ?>
          <?php
            // Row identifiers
            $id     = (int)($pd['id'] ?? 0); // payroll_details.id
            $runId  = isset($summary['run_id']) ? (int)$summary['run_id'] : (int)($pd['run_id'] ?? 0);
    
            // Action URLs (use existing controller methods)
            $urlPayslip = site_url('payroll/payslip?id=' . $id);
            $urlBank    = site_url('payroll/export_bank?run_id=' . $runId); // make sure this route exists
          ?>
          <tr>
            <td><?= e($pd['emp_id']) ?></td>
            <td><?= e($pd['fullname']) ?></td>
            <td><?=  $CUR .  number_format((float)$pd['basic_salary'], 2) ?></td>
            <td><?=  $CUR .  number_format((float)$pd['allowances_total'], 2) ?></td>
            <td><?=  $CUR .  number_format((float)$pd['overtime_amount'], 2) ?></td>
            <td><?=  $CUR .  number_format((float)$pd['bonus_amount'], 2) ?></td>
            <td><?=  $CUR .  number_format((float)$pd['leave_deduction'], 2) ?></td>
            <td><?=  $CUR .  number_format((float)$pd['other_earnings'], 2) ?></td>
            <td><?=  $CUR .  number_format((float)($pd['arrears_amount'] ?? 0), 2) ?></td>
            <td><?=  $CUR .  number_format((float)$pd['gross_pay'], 2) ?></td>
            <td><?=  $CUR .  number_format((float)(
                  (float)($pd['deductions_total'] ?? 0)
                + (float)($pd['leave_deduction'] ?? 0)
                + (float)($pd['tax_amount'] ?? 0)
                + (float)($pd['pf_deduction'] ?? 0)
                + (float)($pd['loan_total_deduction'] ?? 0)
                + (float)($pd['advance_total_deduction'] ?? 0)
              ), 2) ?></td>
            <td><?=  $CUR .  number_format((float)$pd['net_pay'], 2) ?></td>
            <td class="text-end">
              <a class="btn btn-outline-secondary btn-ssm" href="<?= e($urlPayslip) ?>" target="_blank" title="Payslip">
                <i class="ti ti-file-text"></i>
              </a>
    
              <?php if ($runId > 0): ?>
                <a class="btn btn-outline-secondary btn-ssm" href="<?= e($urlBank) ?>" title="Export Bank File">
                  <i class="ti ti-building-bank"></i>
                </a>
              <?php else: ?>
                <a class="btn btn-outline-secondary btn-ssm disabled" href="#" role="button" aria-disabled="true" tabindex="-1" title="No Run ID">
                  <i class="ti ti-building-bank"></i>
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    </div>
  </div>
</div>


<!-- Run Settings Modal -->
<div class="modal fade" id="runAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="post"
          action="<?= site_url('payroll/update_run_admin') ?>"
          class="modal-content app-form"
          id="runAdminForm">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="ti ti-adjustments-horizontal me-2"></i> Run Settings</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="run_id" id="ra_run_id" value="<?= (int)($summary['run_id'] ?? 0) ?>">

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Payroll Status</label>
            <select name="status" id="ra_status" class="form-select">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Run Status</label>
            <select name="status_run" id="ra_status_run" class="form-select">
              <option value="open">Open</option>
              <option value="processed">Processed</option>
              <option value="posted">Posted</option>
              <option value="paid">Paid</option>
              <option value="void">Void</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Pay Date</label>
            <input type="date" name="pay_date" id="ra_pay_date" class="form-control" value="">
          </div>

          <div class="col-md-4">
            <label class="form-label">Is Locked</label>
            <select name="is_locked" id="ra_is_locked" class="form-select">
              <option value="0">No</option>
              <option value="1">Yes</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" id="ra_payment_method" class="form-select">
              <option value="bank">Bank Transfer</option>
              <option value="cash">Cash Payment</option>
              <option value="cheque">Cheque</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Paid By (User ID)</label>
            <input type="number" name="paid_by" id="ra_paid_by" class="form-control" placeholder="e.g., 12">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="ti ti-device-floppy me-1"></i> Update Run
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Monthly Inputs Modal -->
<div class="modal fade" id="monthlyInputsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <form method="post"
          action="<?= site_url('payroll/save_monthly_inputs') ?>"
          class="modal-content app-form"
          id="monthlyInputsForm">

      <input type="hidden" name="run_id" value="<?= (int)($summary['run_id'] ?? 0) ?>">

      <div class="modal-header bg-secondary">
        <h5 class="modal-title text-white">
          <i class="ti ti-list-details me-2"></i> Monthly Inputs (Run #<?= (int)($summary['run_id'] ?? 0) ?>)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="small text-muted">Add one-off allowances/deductions for this run. Saving will update payroll details immediately.</div>
          <button type="button" class="btn btn-outline-secondary btn-ssm" id="miAddRow">
            <i class="ti ti-plus"></i> Add Row
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle" id="miTable">
            <thead class="bg-light-primary">
              <tr>
                <th style="width: 220px;">Employee</th>
                <th style="width: 240px;">Pay Element</th>
                <th style="width: 140px;">Pay Date</th>
                <th style="width: 140px;">Amount</th>
                <th>Remarks</th>
                <th style="width: 60px;">Remove</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td>
                    <input type="hidden" name="items[uid][]" value="<?= (int)$r['user_id'] ?>">
                    <div class="small fw-semibold"><?= e($r['fullname'] ?? ('UID:'.$r['user_id'])) ?></div>
                    <div class="small text-muted"><?= e($r['emp_id'] ?? '') ?></div>
                  </td>
                  <td>
                    <select name="items[element_key][]" class="form-select form-select-sm mi-element">
                      <option value="">— Select —</option>
                      <?php foreach (($payElements ?? []) as $el): ?>
                        <option value="<?= e($el['key']) ?>"
                                data-type="<?= e($el['type']) ?>">
                          <?= e($el['label']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td><input type="date" name="items[pay_date][]" class="form-control form-control-sm" /></td>
                  <td><input type="number" step="0.01" min="0" name="items[amount][]" class="form-control form-control-sm" value="0"></td>
                  <td><input type="text" name="items[remarks][]" class="form-control form-control-sm" placeholder="Optional"></td>
                  <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-ssm mi-remove"><i class="ti ti-trash"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-secondary">
          <i class="ti ti-device-floppy me-1"></i> Save Monthly Inputs
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const tbl = document.getElementById('miTable');
  if (!tbl) return;

  // add row from a blank template (uses first row as template)
  const addBtn = document.getElementById('miAddRow');
  addBtn?.addEventListener('click', () => {
    const tbody = tbl.querySelector('tbody');
    const t = tbody.querySelector('tr');
    if (!t) return;
    const clone = t.cloneNode(true);

    // reset values
    clone.querySelectorAll('input').forEach(i => {
      if (i.name.endsWith('[uid][]')) return; // keep same user id if you duplicate, or adjust logic as you like
      i.value = i.type === 'number' ? '0' : '';
    });
    const sel = clone.querySelector('select.mi-element');
    if (sel) sel.value = '';

    tbody.appendChild(clone);
  });

  // remove row
  tbl.addEventListener('click', (ev) => {
    const btn = ev.target.closest('.mi-remove');
    if (!btn) return;
    const tr = btn.closest('tr');
    if (!tr) return;
    tr.remove();
  });
})();
</script>

<script>
(function(){
  const modal = document.getElementById('runAdminModal');
  if (!modal) return;

  modal.addEventListener('show.bs.modal', function (ev) {
    const button = ev.relatedTarget;
    const runId  = button?.getAttribute('data-run-id') || document.getElementById('ra_run_id')?.value;
    if (!runId) return;

    // write the hidden input
    document.getElementById('ra_run_id').value = runId;

    // reset quick defaults
    document.getElementById('ra_status').value          = 'active';
    document.getElementById('ra_status_run').value      = 'processed';
    document.getElementById('ra_pay_date').value        = '';
    document.getElementById('ra_is_locked').value       = '0';
    document.getElementById('ra_payment_method').value  = 'bank';
    document.getElementById('ra_paid_by').value         = '';

    // fetch current controls
    fetch('<?= site_url('payroll/run_controls_json/') ?>' + runId)
      .then(r => r.json())
      .then(d => {
        if (!d || !d.run_id) return;

        // d.payroll_status may be null if not in summary; fallback to 'active'
        const status      = (d.payroll_status || 'active').toLowerCase();
        const status_run  = (d.status_run || 'processed').toLowerCase();
        const pay_date    = d.pay_date || '';
        const is_locked   = String(d.is_locked ?? '0');
        const pay_method  = (d.payment_method || 'bank').toLowerCase();
        const paid_by     = d.paid_by ?? '';

        // assign
        document.getElementById('ra_status').value          = ['active','inactive'].includes(status) ? status : 'active';
        document.getElementById('ra_status_run').value      = ['draft','processed','posted','paid','void'].includes(status_run) ? status_run : 'processed';
        document.getElementById('ra_pay_date').value        = pay_date;
        document.getElementById('ra_is_locked').value       = (is_locked === '1' ? '1' : '0');
        document.getElementById('ra_payment_method').value  = ['bank','cash','cheque','wallet','other'].includes(pay_method) ? pay_method : 'bank';
        document.getElementById('ra_paid_by').value         = paid_by;
      })
      .catch(()=>{/* no-op */});
  });
})();
</script>
