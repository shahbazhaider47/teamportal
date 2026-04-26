<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CUR = html_escape(get_base_currency_symbol()); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
      
      <?php if (staff_can('create', 'payroll')): ?>
      <button type="button" class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#loanModal" id="btnAddLoan">
        <i class="ti ti-plus me-1"></i> Add Loan
      </button>
      <?php endif; ?>
      
        <div class="btn-divider"></div>
        
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'positionsTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
        
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-sm table-bottom-border small align-middle" id="<?= e($table_id ?? 'payrollLoansTable') ?>">
        <thead class="bg-light-primary">
          <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Loan Taken</th>
            <th>Payback Type</th>
            <th>Total Installments</th>
            <th>Monthly Inst.</th>
            <th>Total Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Duration</th>
            <th class="text-center" width="140">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($loans)): foreach ($loans as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td>
                <?= e(trim($row['fullname'] ?: ($row['firstname'].' '.$row['lastname']))) ?>
                <?php if (!empty($row['emp_id'])): ?>
                  <span class="text-muted small d-block">ID: <?= e($row['emp_id']) ?></span>
                <?php endif; ?>
              </td>
              <td><?= $CUR . ' ' . number_format((float)$row['loan_taken'], 2) ?></td>
              <td><?= ucfirst(str_replace('_',' ', (string)$row['payback_type'])) ?></td>
              <td><?= (int)$row['total_installments'] ?></td>
              <td><?= $CUR . ' ' . number_format((float)$row['monthly_installment'], 2) ?></td>
              <td><?= $CUR . ' ' . number_format((float)$row['total_paid'], 2) ?></td>
              <td><?= $CUR . ' ' . number_format((float)$row['balance'], 2) ?></td>
              <td>
                <span class="badge 
                  <?= $row['status']==='active'?'bg-success':($row['status']==='paid'?'bg-primary':($row['status']==='defaulted'?'bg-danger':'bg-secondary')) ?>">
                  <?= e(ucfirst($row['status'])) ?>
                </span>
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
                <?= e($start) ?> <strong class="text-primary">To:</strong> <?= e($end) ?>
              <?php else: ?>
                <?= e($start ?: $end) ?>
              <?php endif; ?>
            </td>
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary btn-edit-loan" data-id="<?= (int)$row['id'] ?>" title="Edit">
                    <i class="ti ti-eye"></i>
                </button>
                  <?php if (staff_can('delete', 'payroll')): ?>
                  <a href="<?= site_url('payroll/delete_loan/' . (int)$row['id']) ?>"
                     class="btn btn-outline-secondary"
                     onclick="return confirm('Delete this loan?');"
                     title="Delete">
                    <i class="ti ti-trash"></i>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="13" class="text-center text-muted py-4">No loans found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit Loan Modal -->
<div class="modal fade" id="loanModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-top">
    <form method="post" action="<?= site_url('payroll/save_loan') ?>" class="modal-content app-form" id="loanForm">
      <input type="hidden" name="id" id="loan_id" value="">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="loanModalTitle">
          <i class="ti ti-cash me-2"></i> Add Loan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Employee <span class="text-danger">*</span></label>
            <select name="user_id" id="loan_user_id" class="form-select" required>
              <option value="">Select Employee</option>
              <?php if (!empty($users_all)): foreach ($users_all as $u): ?>
                <option value="<?= (int)$u['id'] ?>">
                  <?= e(($u['emp_id'] ? ($u['emp_id'].' - ') : '') . ($u['fullname'] ?: ($u['firstname'].' '.$u['lastname']))) ?>
                </option>
              <?php endforeach; endif; ?>
            </select>
          </div>

        <div class="col-md-4">
          <label class="form-label">Loan Taken <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><?= html_escape(get_base_currency_symbol()) ?></span>
            <input type="number"
                   class="form-control"
                   name="loan_taken"
                   id="loan_taken"
                   step="0.01"
                   min="0"
                   required>
          </div>
        </div>
        
        <div class="col-md-4">
          <label class="form-label">Payback Type <span class="text-danger">*</span></label>
          <select name="payback_type" id="payback_type" class="form-select" required>
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="from_salary">From Salary</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        
        
        <div class="col-md-4 loan-plan-fields" id="wrap_total_installments">
          <label class="form-label">Total Installments <span class="text-danger">*</span></label>
          <input type="number" name="total_installments" id="total_installments" class="form-control" min="0" required>
        </div>
        
        <div class="col-md-4 loan-plan-fields" id="wrap_monthly_installment">
          <label class="form-label">Monthly Installment</label>
          <div class="input-group">
            <span class="input-group-text"><?= $CUR ?></span>
            <input type="number" step="0.01" name="monthly_installment" id="monthly_installment" class="form-control">
          </div>
        </div>
        
        <div class="col-md-4" id="wrap_current_installment">
          <label class="form-label">Current Installment</label>
          <input type="number" name="current_installment" id="current_installment" class="form-control" min="0" value="0">
        </div>

        <div class="col-md-4">
          <label class="form-label">Paid Amount</label>
          <div class="input-group">
            <span class="input-group-text"><?= $CUR ?></span>
            <input type="number" step="0.01" name="total_paid" id="total_paid" class="form-control" value="0">
          </div>
        </div>
        
        <div class="col-md-4">
          <label class="form-label">Loan Balance</label>
          <div class="input-group">
            <span class="input-group-text"><?= $CUR ?></span>
            <input type="number" step="0.01" name="balance" id="balance" class="form-control" value="0">
          </div>
        </div>

          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
              <option value="active">Active</option>
              <option value="paid">Paid</option>
              <option value="defaulted">Defaulted</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control basic-date" placeholder="YYYY-MM-DD">
          </div>

          <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control basic-date" placeholder="YYYY-MM-DD">
          </div>

          <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Optional"></textarea>
          </div>

          <div class="col-12">
            <div class="alert alert-info small mb-0">
              Tip: Balance is usually <em>Loan Taken - Total Paid</em>. You can enter manually or let payroll runs update it automatically later.
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="ti ti-device-floppy me-1"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Payroll Loans JS initialized');
    
    function showModal() {
        document.getElementById('loanModal').style.display = 'block';
        document.body.classList.add('modal-open');
        document.getElementById('loanModal').classList.add('show');
        document.getElementById('loanModalBackdrop').style.display = 'block';
    }

    function hideModal() {
        document.getElementById('loanModal').style.display = 'none';
        document.body.classList.remove('modal-open');
        document.getElementById('loanModal').classList.remove('show');
        document.getElementById('loanModalBackdrop').style.display = 'none';
    }

    const loanForm = document.getElementById('loanForm');
    const modalTitle = document.getElementById('loanModalTitle');

    function valNum(id) { 
        const el = document.getElementById(id);
        if (!el) return 0;
        const val = parseFloat(el.value);
        return isNaN(val) ? 0 : val;
    }
    
    function setVal(id, v) { 
        const el = document.getElementById(id);
        if (el) el.value = v;
    }
    
    function setDisabled(id, disabled) { 
        const el = document.getElementById(id);
        if (el) el.disabled = disabled;
    }

    // Calculation functions (same as before)
    function recalcMonthlyInstallment() {
        const type = document.getElementById('payback_type')?.value || 'monthly';
        const loan = valNum('loan_taken');
        const total = Math.max(0, parseInt(document.getElementById('total_installments')?.value || 0, 10) || 0);

        if (total <= 0) return;

        let monthly = 0;
        if (type === 'monthly') {
            monthly = loan / total;
        } else if (type === 'quarterly') {
            monthly = (loan / total) / 3;
        }
        
        if (monthly > 0) {
            setVal('monthly_installment', monthly.toFixed(2));
        }
    }

    function recalcCurrentInstallment() {
        const total = Math.max(0, parseInt(document.getElementById('total_installments')?.value || 0, 10) || 0);
        const monthly = Math.max(0.000001, valNum('monthly_installment'));
        const paid = valNum('total_paid');
        let currentInst = Math.floor(paid / monthly);
        
        if (!isFinite(currentInst)) currentInst = 0;
        if (total > 0) currentInst = Math.min(currentInst, total);
        
        setVal('current_installment', currentInst);
    }

    function recalcBalance() {
        const loan = valNum('loan_taken');
        const paid = valNum('total_paid');
        setVal('balance', (loan - paid).toFixed(2));
    }

    function recalcAll() {
        recalcMonthlyInstallment();
        recalcCurrentInstallment();
        recalcBalance();
    }

    // Toggle fields based on payback type
function togglePlanFields() {
    const type = document.getElementById('payback_type')?.value || 'monthly';
    const showPlan = (type === 'monthly' || type === 'quarterly');

    // Toggle visibility of plan fields
    document.querySelectorAll('.loan-plan-fields').forEach(el => {
        el.classList.toggle('d-none', !showPlan);
    });

    // Always keep payback_type enabled so it posts to PHP
    const paybackSelect = document.getElementById('payback_type');
    if (paybackSelect) {
        paybackSelect.disabled = false;
    }

    // Enable/disable only the fields that depend on payback type
    setDisabled('total_installments', !showPlan);
    setDisabled('monthly_installment', !showPlan);

    if (showPlan) {
        recalcMonthlyInstallment();
    }
}


    // Setup event listeners
    function setupEventListeners() {
        // Close button
        document.querySelector('#loanModal .btn-close')?.addEventListener('click', hideModal);
        
        // Cancel button
        document.querySelector('#loanModal .btn-outline-secondary')?.addEventListener('click', hideModal);

        // Calculation triggers
        ['loan_taken', 'total_installments'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', recalcAll);
                el.addEventListener('change', recalcAll);
            }
        });

        ['monthly_installment', 'total_paid'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function() {
                    recalcCurrentInstallment();
                    recalcBalance();
                });
                el.addEventListener('change', function() {
                    recalcCurrentInstallment();
                    recalcBalance();
                });
            }
        });

        // Payback type change
        const paybackType = document.getElementById('payback_type');
        if (paybackType) {
            paybackType.addEventListener('change', function() {
                togglePlanFields();
                recalcBalance();
            });
        }

        // Add loan button
        const addBtn = document.getElementById('btnAddLoan');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                if (loanForm) {
                    loanForm.reset();
                    setVal('loan_id', '');
                    if (modalTitle) {
                        modalTitle.innerHTML = '<i class="ti ti-cash me-2"></i> Add Loan';
                    }
                }
                setTimeout(function() {
                    togglePlanFields();
                    recalcAll();
                    showModal();
                }, 50);
            });
        }

        // Edit loan buttons
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-edit-loan');
            if (!btn) return;

            const loanId = btn.getAttribute('data-id');
            if (!loanId) return;

            fetch(`<?= site_url('payroll/get_loan_json/') ?>${loanId}`)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    if (!data || !data.id) {
                        throw new Error('Invalid loan data');
                    }

                    // Fill form fields
                    setVal('loan_id', data.id);
                    setVal('loan_user_id', data.user_id);
                    setVal('loan_taken', data.loan_taken);
                    setVal('payback_type', data.payback_type);
                    setVal('total_installments', data.total_installments);
                    setVal('monthly_installment', data.monthly_installment);
                    setVal('current_installment', data.current_installment);
                    setVal('total_paid', data.total_paid);
                    setVal('balance', data.balance);
                    setVal('status', data.status);
                    setVal('start_date', data.start_date || '');
                    setVal('end_date', data.end_date || '');
                    setVal('notes', data.notes || '');

                    // Update modal title
                    if (modalTitle) {
                        modalTitle.innerHTML = '<i class="ti ti-edit me-2"></i> Edit Loan';
                    }

                    // Recalculate and show
                    setTimeout(function() {
                        togglePlanFields();
                        recalcAll();
                        showModal();
                    }, 50);
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error loading loan details. Please try again.');
                });
        });
    }

    // Initialize
    setupEventListeners();
    togglePlanFields();
    recalcAll();
});
</script>