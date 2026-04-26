<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CUR = html_escape(get_base_currency_symbol()); ?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <h1 class="h6 header-title"><?= e($page_title ?? 'Salary Increments') ?></h1>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php if (staff_can('create', 'payroll')): ?>
      <button type="button" class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#incrementModal">
        <i class="ti ti-plus me-1"></i> Add Increment
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-3">
      <div class="table-responsive">
        <table class="table table-sm small table-hover table-bottom-border" id="<?= e($table_id ?? 'payrollIncrementsTable') ?>">
          <thead class="bg-light-primary">
            <tr>
              <th>Date</th>
              <th>Emp ID</th>
              <th>Employee Name</th>
              <th>Department</th>
              <th>Raise Type</th>
              <th>Raise Value</th>
              <th>Previous Salary</th>
              <th>Raised Amount</th>
              <th>New Salary</th>
              <th>Increment Cycle</th>
              <th>Status</th>              
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($increments)): ?>
              <?php foreach ($increments as $i): ?>
              <tr>
                <td><?= e(date('M d, Y', strtotime($i['increment_date']))) ?></td>
                <td><?= e($i['emp_id'] ?? '') ?></td>
                <td><?= e(trim(($i['firstname'] ?? '').' '.($i['lastname'] ?? ''))) ?></td>
                <td><?= e($i['department_name'] ?? '') ?></td>
                <td><?= e(ucfirst($i['increment_type'])) ?></td>
                <td>
                  <?php if (($i['increment_type'] ?? '') === 'percent'): ?>
                    <?= e(number_format((float)$i['increment_value'], 2)) ?>%
                  <?php else: ?>
                    <?= $CUR . ' ' . e(number_format((float)$i['increment_value'], 2)) ?>
                  <?php endif; ?>
                </td>
                <td><?= $CUR . ' ' . e(number_format((float)$i['previous_salary'], 2)) ?></td>
                <td><?= $CUR . ' ' . e(number_format((float)$i['raised_amount'], 2)) ?></td>
                <td><?= $CUR . ' ' . e(number_format((float)$i['new_salary'], 2)) ?></td>
                <td><?= e(ucfirst($i['increment_cycle'])) ?></td>
                <td><?= e(ucfirst($i['status'])) ?></td>
                <td>

                <button type="button"
                        class="btn btn-ssm btn-outline-primary view-increment"
                        data-id="<?= (int)$i['id'] ?>"
                        title="View Increment">
                  <i class="ti ti-eye"></i>
                </button>
                
                <?php if (staff_can('edit', 'payroll') && ($i['status'] ?? '') === 'pending'): ?>
                  <form method="post" action="<?= site_url('payroll/approve_increment') ?>" 
                        style="display:inline"
                        onsubmit="return confirm('Approve this increment and update salary?')">
                    <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                    <button type="submit" class="btn btn-ssm btn-outline-success" title="Approve">
                      <i class="ti ti-check"></i>
                    </button>
                  </form>
                <?php endif; ?>

                  <?php if (staff_can('delete', 'payroll')): ?>
                    <button type="button" 
                            class="btn btn-ssm btn-outline-danger delete-increment" 
                            data-id="<?= $i['id'] ?>" 
                            title="Delete Increment">
                      <i class="ti ti-trash"></i>
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="12" class="text-center text-muted py-3">No salary increments found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Increment Modal -->
<div class="modal fade" id="incrementModal" tabindex="-1" aria-labelledby="incrementModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl">
    <form class="app-form" id="increment-form" method="post"
      action="<?= site_url('payroll/save_increment') ?>" novalidate>
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="incrementModalLabel">Add Salary Increment</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- Config row -->
          <div class="row g-3 mb-2">
            <div class="col-md-3">
              <label class="form-label">Scope <span class="text-danger">*</span></label>
              <select class="form-select" name="scope" id="inc_scope" required>
                <option value="" selected disabled>Select Scope</option>
                <option value="all">All Employees</option>
                <option value="users">Specific Employee(s)</option>
                <option value="department">By Department</option>
            <!--    <option value="position">By Position</option> -->
              </select>
              <div class="invalid-feedback">Please select scope</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Effective Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="increment_date" id="inc_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Type <span class="text-danger">*</span></label>
              <select class="form-select" name="increment_type" id="inc_type" required>
                <option value="amount" selected>Fixed Amount</option>
                <option value="percent">Percentage</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Value <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" class="form-control" name="increment_value" id="inc_value" step="0.01" min="0.01">
                <span class="input-group-text" id="inc_value_suffix"><?= $CUR ?></span>
              </div>
              <div class="form-text" id="inc_value_hint">Enter positive value</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Cycle <span class="text-danger">*</span></label>
              <select class="form-select" name="increment_cycle" id="inc_cycle" required>
                <option value="annual" selected>Annual</option>
                <option value="bi-annual">Bi-annual</option>
                <option value="quarterly">Quarterly</option>
                <option value="monthly">Monthly</option>
                <option value="one-time">One-time</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="col-md-9">
              <label class="form-label">Remarks</label>
              <input type="text" class="form-control" name="remarks" id="inc_remarks" placeholder="e.g., Performance increment FY-25" maxlength="255">
            </div>

            <!-- Scope-dependent pickers -->
            <div class="col-12 scope-field scope-users d-none">
              <label class="form-label">Select Employee(s) <span class="text-danger">*</span></label>
              <select class="form-select select2" name="user_ids[]" id="inc_user_ids" multiple data-placeholder="Select employee(s)" required>
                <?php foreach (($users ?? []) as $u): ?>
                  <option value="<?= (int)$u['id'] ?>">
                    <?= e(($u['emp_id'] ? $u['emp_id'].' - ' : '').$u['firstname'].' '.$u['lastname']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 scope-field scope-department d-none">
              <label class="form-label">Department <span class="text-danger">*</span></label>
                <select class="form-select" name="department_id" id="inc_department_id" required>
                  <option value="" selected disabled>Select Department</option>
                  <?php foreach (($departments ?? []) as $d): ?>
                    <option value="<?= (int)$d['id'] ?>"><?= e($d['title']) ?></option>
                  <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 scope-field scope-position d-none">
              <label class="form-label">Position <span class="text-danger">*</span></label>
              <select class="form-select" name="position_id" id="inc_position_id" required>
                <option value="" selected disabled>Select Position</option>
                <?php foreach (($positions ?? []) as $p): ?>
                  <option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Preview grid -->
          <div class="row mt-3">
            <div class="col-12">
                <hr class="mb-3 mt-4">
              <div class="card border">
                <div class="card-body">
                  <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm small align-middle mb-0" id="inc_preview_table">
                        <thead class="sticky-top bg-light-primary p-2">
                          <tr>
                            <th>EMP ID</th>
                            <th>Employee</th>
                            <th class="text-end">Current Salary</th>
                            <th class="text-center">Rais Type</th>
                            <th class="text-end">Raise Value</th>
                            <th class="text-end">Raised Amount</th>                            
                            <th class="text-end">New Salary</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr class="text-muted">
                            <td colspan="7">Select a scope to list employees. Then set Type/Value per user if needed.</td>
                          </tr>
                        </tbody>
                    </table>
                  </div>
                </div>
                <div class="card-footer bg-light-primary">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted" id="inc_preview_count">0 employees selected</span>
                    <span class="small text-muted" id="inc_preview_total">Total increase: <?= $CUR ?> 0.00</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" id="submit-btn">
            Apply Increment
          </button>
        </div>
      </div>
    </form>
  </div>
</div>


<!-- View Increment Modal -->
<div class="modal fade" id="viewIncrementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-trending-up me-2"></i>
          Increment Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0" id="vim-body">
        <!-- Skeleton shown while loading -->
        <div id="vim-skeleton" class="p-4">
          <div class="placeholder-glow">
            <span class="placeholder col-4 mb-3 d-block" style="height:20px;border-radius:4px;"></span>
            <span class="placeholder col-12 mb-2 d-block" style="height:14px;border-radius:4px;"></span>
            <span class="placeholder col-10 mb-2 d-block" style="height:14px;border-radius:4px;"></span>
            <span class="placeholder col-8 mb-4 d-block" style="height:14px;border-radius:4px;"></span>
            <span class="placeholder col-6 mb-3 d-block" style="height:20px;border-radius:4px;"></span>
            <span class="placeholder col-12 mb-2 d-block" style="height:12px;border-radius:4px;"></span>
            <span class="placeholder col-12 mb-2 d-block" style="height:12px;border-radius:4px;"></span>
          </div>
        </div>

        <!-- Loaded content -->
        <div id="vim-content" class="d-none">

          <!-- Employee Header Banner -->
          <div class="px-4 pt-4 pb-3" style="background:#f8f9fc;border-bottom:1px solid #e9ecef;">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                   style="width:52px;height:52px;background:#e8eaf6;font-size:20px;font-weight:600;color:#3949ab;"
                   id="vim-avatar"></div>
              <div class="flex-grow-1">
                <div class="fw-semibold fs-6" id="vim-name"></div>
                <div class="small text-muted" id="vim-meta"></div>
              </div>
              <div class="text-end flex-shrink-0">
                <div class="small text-muted mb-1">Current Salary</div>
                <div class="fw-semibold text-success fs-6" id="vim-current-salary"></div>
              </div>
            </div>
          </div>

          <!-- Increment Detail Cards -->
          <div class="p-4">
            <div class="row g-3 mb-4">
              <div class="col-6 col-md-3">
                <div class="rounded-3 p-3 text-center" style="background:#fff8e1;border:1px solid #ffe082;">
                  <div class="small text-muted mb-1">Previous Salary</div>
                  <div class="fw-semibold" id="vim-prev-salary"></div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="rounded-3 p-3 text-center" style="background:#e8f5e9;border:1px solid #a5d6a7;">
                  <div class="small text-muted mb-1">Raised By</div>
                  <div class="fw-semibold text-success" id="vim-raised"></div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="rounded-3 p-3 text-center" style="background:#e3f2fd;border:1px solid #90caf9;">
                  <div class="small text-muted mb-1">New Salary</div>
                  <div class="fw-semibold text-primary" id="vim-new-salary"></div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="rounded-3 p-3 text-center" style="background:#f3e5f5;border:1px solid #ce93d8;">
                  <div class="small text-muted mb-1">Raise Type</div>
                  <div class="fw-semibold" id="vim-type"></div>
                </div>
              </div>
            </div>

            <!-- Increment Meta -->
            <div class="row g-2 mb-4">
              <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8f9fa;">
                  <i class="ti ti-calendar text-muted" style="font-size:16px;"></i>
                  <div>
                    <div class="small text-muted" style="font-size:11px;">Effective Date</div>
                    <div class="small fw-semibold" id="vim-date"></div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8f9fa;">
                  <i class="ti ti-refresh text-muted" style="font-size:16px;"></i>
                  <div>
                    <div class="small text-muted" style="font-size:11px;">Cycle</div>
                    <div class="small fw-semibold" id="vim-cycle"></div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f8f9fa;">
                  <i class="ti ti-shield-check text-muted" style="font-size:16px;"></i>
                  <div>
                    <div class="small text-muted" style="font-size:11px;">Status</div>
                    <div class="small fw-semibold" id="vim-status-meta"></div>
                  </div>
                </div>
              </div>
              <div class="col-12" id="vim-remarks-row">
                <div class="d-flex align-items-start gap-2 p-2 rounded-2" style="background:#f8f9fa;">
                  <i class="ti ti-notes text-muted mt-1" style="font-size:16px;"></i>
                  <div>
                    <div class="small text-muted" style="font-size:11px;">Remarks</div>
                    <div class="small" id="vim-remarks"></div>
                  </div>
                </div>
              </div>
              <div class="col-12" id="vim-approved-row">
                <div class="d-flex align-items-center gap-2 p-2 rounded-2" style="background:#e8f5e9;">
                  <i class="ti ti-user-check text-success" style="font-size:16px;"></i>
                  <div>
                    <div class="small text-muted" style="font-size:11px;">Approved By</div>
                    <div class="small fw-semibold text-success" id="vim-approved-by"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- History Table -->
            <div id="vim-history-wrap">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="ti ti-history text-muted" style="font-size:16px;"></i>
                <span class="small fw-semibold text-muted text-uppercase" style="letter-spacing:.5px;">Previous Increments</span>
              </div>
              <div class="table-responsive" style="max-height:220px;overflow-y:auto;">
                <table class="table table-sm small table-hover mb-0">
                  <thead class="bg-light-primary sticky-top">
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Value</th>
                      <th class="text-end">Prev Salary</th>
                      <th class="text-end">Raised</th>
                      <th class="text-end">New Salary</th>
                      <th>Cycle</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="vim-history-body"></tbody>
                </table>
              </div>
            </div>

          </div>
        </div><!-- /vim-content -->

        <div id="vim-error" class="d-none p-4 text-center text-danger small">
          <i class="ti ti-alert-circle me-1"></i>
          <span id="vim-error-msg">Failed to load increment data.</span>
        </div>
      </div><!-- /modal-body -->

      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <div id="vim-approve-wrap" class="d-none">
          <form method="post" action="<?= site_url('payroll/approve_increment') ?>" id="vim-approve-form"
                onsubmit="return confirm('Approve this increment and apply salary update?')">
            <input type="hidden" name="id" id="vim-approve-id" value="">
            <?php if (staff_can('edit', 'payroll')): ?>
            <button type="submit" class="btn btn-success btn-sm">
              <i class="ti ti-check me-1"></i> Approve Increment
            </button>
            <?php endif; ?>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  const CUR      = '<?= $CUR ?>';
  const AJAX_URL = '<?= site_url('payroll/increment_json/') ?>';

  function fmt(n) {
    return CUR + ' ' + parseFloat(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function fmtDate(d) {
    if (!d) return '—';
    const dt = new Date(d.replace(' ', 'T'));
    return isNaN(dt) ? d : dt.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function statusBadge(s) {
    const map = {
      pending:  'warning',
      approved: 'success',
      rejected: 'danger',
      cancelled:'secondary',
    };
    const cls = map[s] || 'secondary';
    return `<span class="badge bg-${cls}">${(s||'').charAt(0).toUpperCase()+s.slice(1)}</span>`;
  }

  function initials(name) {
    return (name || '?').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
  }

  function showSkeleton() {
    document.getElementById('vim-skeleton').classList.remove('d-none');
    document.getElementById('vim-content').classList.add('d-none');
    document.getElementById('vim-error').classList.add('d-none');
    document.getElementById('vim-approve-wrap').classList.add('d-none');
  }

  function showError(msg) {
    document.getElementById('vim-skeleton').classList.add('d-none');
    document.getElementById('vim-content').classList.add('d-none');
    document.getElementById('vim-error').classList.remove('d-none');
    document.getElementById('vim-error-msg').textContent = msg || 'Failed to load increment data.';
    document.getElementById('vim-approve-wrap').classList.add('d-none');
  }

  function showContent(inc, history) {
    document.getElementById('vim-skeleton').classList.add('d-none');
    document.getElementById('vim-error').classList.add('d-none');
    document.getElementById('vim-content').classList.remove('d-none');

    const name = ((inc.firstname || '') + ' ' + (inc.lastname || '')).trim();

    // Avatar + header
    document.getElementById('vim-avatar').textContent = initials(name);
    document.getElementById('vim-name').textContent   = name;

    const metaParts = [];
    if (inc.emp_id)          metaParts.push('EMP: ' + inc.emp_id);
    if (inc.department_name) metaParts.push(inc.department_name);
    if (inc.position_title)  metaParts.push(inc.position_title);
    document.getElementById('vim-meta').textContent = metaParts.join(' · ');

    document.getElementById('vim-current-salary').textContent = fmt(inc.current_salary);

    // Cards
    document.getElementById('vim-prev-salary').textContent = fmt(inc.previous_salary);
    document.getElementById('vim-new-salary').textContent  = fmt(inc.new_salary);

    const raisedEl = document.getElementById('vim-raised');
    if (inc.increment_type === 'percent') {
      raisedEl.textContent = parseFloat(inc.increment_value || 0).toFixed(2) + '% (' + fmt(inc.raised_amount) + ')';
    } else {
      raisedEl.textContent = fmt(inc.raised_amount);
    }

    document.getElementById('vim-type').textContent = inc.increment_type === 'percent' ? 'Percentage' : 'Fixed Amount';

    // Meta row
    document.getElementById('vim-date').textContent  = fmtDate(inc.increment_date);
    document.getElementById('vim-cycle').textContent = inc.increment_cycle
      ? inc.increment_cycle.charAt(0).toUpperCase() + inc.increment_cycle.slice(1) : '—';
    document.getElementById('vim-status-meta').innerHTML = statusBadge(inc.status);

    // Remarks
    const remarksRow = document.getElementById('vim-remarks-row');
    if (inc.remarks) {
      remarksRow.classList.remove('d-none');
      document.getElementById('vim-remarks').textContent = inc.remarks;
    } else {
      remarksRow.classList.add('d-none');
    }

    // Approved by
    const approvedRow = document.getElementById('vim-approved-row');
    if (inc.status === 'approved' && inc.approved_by_name) {
      approvedRow.classList.remove('d-none');
      document.getElementById('vim-approved-by').textContent =
        inc.approved_by_name + (inc.approved_at ? ' · ' + fmtDate(inc.approved_at) : '');
    } else {
      approvedRow.classList.add('d-none');
    }

    // History
    const histWrap = document.getElementById('vim-history-wrap');
    const histBody = document.getElementById('vim-history-body');
    histBody.innerHTML = '';

    if (history && history.length) {
      histWrap.classList.remove('d-none');
      history.forEach(function (h) {
        const val = h.increment_type === 'percent'
          ? parseFloat(h.increment_value || 0).toFixed(2) + '%'
          : fmt(h.increment_value);
        histBody.insertAdjacentHTML('beforeend', `
          <tr>
            <td class="text-nowrap">${fmtDate(h.increment_date)}</td>
            <td>${h.increment_type === 'percent' ? 'Percent' : 'Amount'}</td>
            <td>${val}</td>
            <td class="text-end">${fmt(h.previous_salary)}</td>
            <td class="text-end text-success">${fmt(h.raised_amount)}</td>
            <td class="text-end">${fmt(h.new_salary)}</td>
            <td>${h.increment_cycle ? h.increment_cycle.charAt(0).toUpperCase() + h.increment_cycle.slice(1) : '—'}</td>
            <td>${statusBadge(h.status)}</td>
          </tr>`);
      });
    } else {
      histWrap.classList.add('d-none');
    }

    // Approve button
    const approveWrap = document.getElementById('vim-approve-wrap');
    if (inc.status === 'pending') {
      approveWrap.classList.remove('d-none');
      document.getElementById('vim-approve-id').value = inc.id;
    } else {
      approveWrap.classList.add('d-none');
    }
  }

  // Open modal handler
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-increment');
    if (!btn) return;

    const id = parseInt(btn.dataset.id, 10);
    if (!id) return;

    showSkeleton();
    const modal = new bootstrap.Modal(document.getElementById('viewIncrementModal'));
    modal.show();

    fetch(AJAX_URL + id, {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data && data.ok) {
        showContent(data.increment, data.history);
      } else {
        showError(data.message || 'Increment not found.');
      }
    })
    .catch(function () {
      showError('Network error. Please try again.');
    });
  });

})();
</script>

<style>
  .select2-container { z-index: 2000; }
  #inc_preview_table thead th { position: sticky; top: 0; }
  .table-responsive::-webkit-scrollbar { width: 8px; height: 8px; }
  .table-responsive::-webkit-scrollbar-track { background: #f1f1f1; }
  .table-responsive::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
  .table-responsive::-webkit-scrollbar-thumb:hover { background: #555; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

<script>
$(function() {
  const $scope        = $('#inc_scope');
  const $typeGlobal   = $('#inc_type');
  const $valueGlobal  = $('#inc_value');
  const $tableBody    = $('#inc_preview_table tbody');
  const $countInfo    = $('#inc_preview_count');
  const $totalInfo    = $('#inc_preview_total');
  const $btnSubmit    = $('#submit-btn');
  const CUR = '<?= $CUR ?>';

  // Select2
  $('#inc_user_ids').select2({
    width: '100%',
    dropdownParent: $('#incrementModal'),
    placeholder: 'Select employee(s)',
    allowClear: true
  });

  function toggleScopeFields(scopeVal){
    $('.scope-field').addClass('d-none').find('select').prop('required', false);
    if (scopeVal === 'users')        $('.scope-users').removeClass('d-none').find('select').prop('required', true);
    else if (scopeVal === 'department') $('.scope-department').removeClass('d-none').find('select').prop('required', true);
    else if (scopeVal === 'position')   $('.scope-position').removeClass('d-none').find('select').prop('required', true);
  }

  function formatCurrency(n){
    const v = parseFloat(n || 0);
    return CUR + ' ' + v.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
  }
  function calcNew(current, type, value){
    const c = parseFloat(current||0);
    const v = parseFloat(value||0);
    if (!type || !v || v<=0) return null;
    return type==='percent' ? (c + (c*v/100.0)) : (c + v);
  }

  function renderPreview(rows, typeDefault, valueDefault) {
    $tableBody.empty();
    if (!rows || !rows.length) {
      $tableBody.append('<tr class="text-muted"><td colspan="7">No matching employees found for your selection.</td></tr>');
      $countInfo.text('0 employees selected');
      $totalInfo.text('Total increase: ' + formatCurrency(0));
      return;
    }

    let totalIncrease = 0;

    rows.forEach((r, idx) => {
      const curr = parseFloat(r.current_salary) || 0;
      const rowType  = r.increment_type || typeDefault || 'amount';
      const rowValue = r.increment || valueDefault || '';

      const newSal = calcNew(curr, rowType, rowValue);
      const raise  = (newSal !== null) ? (newSal - curr) : null;
      if (raise !== null) totalIncrease += raise;

      const selectType = `
        <select class="form-select form-select-sm inc-row-type" data-uid="${r.user_id}" data-idx="${idx}">
          <option value="amount" ${rowType==='amount'?'selected':''}>Amount</option>
          <option value="percent" ${rowType==='percent'?'selected':''}>Percent</option>
        </select>`;

      const inputValue = `
        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm inc-row-value"
               data-uid="${r.user_id}" data-idx="${idx}" value="${rowValue !== null && rowValue !== undefined ? rowValue : ''}">`;

      const rowHtml = `
        <tr data-uid="${r.user_id}">
          <td>${r.emp_id || 'N/A'}</td>
          <td>${r.name || 'Unknown'}</td>
          <td class="text-end curr-sal" data-val="${curr}">${formatCurrency(curr)}</td>
          <td class="text-end" style="min-width:110px;">${selectType}</td>
          <td class="text-end" style="min-width:110px;">${inputValue}</td>
          <td class="text-end raise ${raise>0?'text-success':''}">${raise !== null ? formatCurrency(raise) : ''}</td>
          <td class="text-end new-sal">${newSal !== null ? formatCurrency(newSal) : ''}</td>
        </tr>`;
      $tableBody.append(rowHtml);
    });

    $countInfo.text(rows.length + ' employee(s) selected');
    $totalInfo.text('Total increase: ' + formatCurrency(totalIncrease));
  }

  function recalcRow($tr){
    const curr   = parseFloat($tr.find('.curr-sal').data('val')) || 0;
    const type   = $tr.find('.inc-row-type').val();
    const val    = parseFloat($tr.find('.inc-row-value').val());
    const newSal = calcNew(curr, type, val);
    const $new   = $tr.find('.new-sal');
    const $raise = $tr.find('.raise');

    if (newSal !== null) {
      const raise = newSal - curr;
      $new.text(formatCurrency(newSal));
      $raise.text(formatCurrency(raise)).toggleClass('text-success', raise>0);
    } else {
      $new.text('');
      $raise.text('').removeClass('text-success');
    }
    recalcTotals();
  }

  function recalcTotals(){
    let total = 0;
    $('#inc_preview_table tbody tr').each(function(){
      const curr = parseFloat($(this).find('.curr-sal').data('val')) || 0;
      const txt  = $(this).find('.new-sal').text().replace(/[^\d.-]/g,'');
      if (txt) {
        const newSal = parseFloat(txt);
        if (!isNaN(newSal)) total += (newSal - curr);
      }
    });
    $('#inc_preview_total').text('Total increase: ' + formatCurrency(total));
  }

  function collectItems(){
    const items = [];
    $('#inc_preview_table tbody tr').each(function(){
      const uid  = parseInt($(this).data('uid'),10) || 0;
      const type = $(this).find('.inc-row-type').val();
      const val  = parseFloat($(this).find('.inc-row-value').val());
      if (uid > 0 && type && val > 0) {
        items.push({ user_id: uid, increment_type: type, increment_value: val });
      }
    });
    return items;
  }

  function refreshPreview(){
    const payload = {
      scope: $('#inc_scope').val(),
      user_ids: $('#inc_user_ids').val(),
      department_id: $('#inc_department_id').val(),
      position_id: $('#inc_position_id').val(),
      increment_type: $typeGlobal.val(),
      increment_value: $valueGlobal.val()
    };

    if (!payload.scope) {
      $tableBody.html('<tr class="text-muted"><td colspan="7">Please select a scope first</td></tr>');
      $countInfo.text('0 employees selected');
      $totalInfo.text('Total increase: ' + formatCurrency(0));
      return;
    }
    if (payload.scope === 'users' && (!payload.user_ids || !payload.user_ids.length)) {
      $tableBody.html('<tr class="text-muted"><td colspan="7">Select at least one employee</td></tr>');
      return;
    }
    if (payload.scope === 'department' && !payload.department_id) {
      $tableBody.html('<tr class="text-muted"><td colspan="7">Select a department</td></tr>');
      return;
    }
    if (payload.scope === 'position' && !payload.position_id) {
      $tableBody.html('<tr class="text-muted"><td colspan="7">Select a position</td></tr>');
      return;
    }

    $tableBody.html('<tr><td colspan="7" class="text-center py-3">Loading…</td></tr>');

    $.ajax({
      url: '<?= site_url('payroll/increment_preview') ?>',
      type: 'POST',
      data: payload,
      dataType: 'json'
    }).done(function(res){
      if (res && res.ok && res.rows) {
        renderPreview(res.rows, payload.increment_type, payload.increment_value);
      } else {
        $tableBody.html('<tr class="text-danger"><td colspan="7">Failed to load preview data</td></tr>');
      }
    }).fail(function(){
      $tableBody.html('<tr class="text-danger"><td colspan="7">Error loading preview</td></tr>');
    });
  }

  // Per-row edits
  $(document).on('change', '.inc-row-type', function(){ recalcRow($(this).closest('tr')); });
  $(document).on('input',  '.inc-row-value', function(){ recalcRow($(this).closest('tr')); });

  // Global type hint/suffix
  $typeGlobal.on('change', function(){
    const t = $(this).val();
    $('#inc_value_suffix').text(t === 'percent' ? '%' : '<?= $CUR ?>');
    $('#inc_value_hint').text(t === 'percent' ? 'Enter percentage (e.g., 5 for 5%)' : 'Enter fixed amount');
    refreshPreview();
  });

  // Scope & filters
  $scope.on('change', function(){ toggleScopeFields($(this).val()); refreshPreview(); });
  $('#inc_user_ids, #inc_department_id, #inc_position_id').on('change', refreshPreview);
  $valueGlobal.on('input', refreshPreview);

  // Submit (NO JS alerts, NO AJAX) – just add items[] then let form post normally
  $('#increment-form').on('submit', function(){
    const items = collectItems();
    // clear any previous hidden items
    $(this).find('input[name^="items["]').remove();
    // add hidden inputs so PHP receives items[]
    items.forEach((it, idx) => {
      $('<input>').attr({type:'hidden', name:`items[${idx}][user_id]`,         value: it.user_id}).appendTo('#increment-form');
      $('<input>').attr({type:'hidden', name:`items[${idx}][increment_type]`,  value: it.increment_type}).appendTo('#increment-form');
      $('<input>').attr({type:'hidden', name:`items[${idx}][increment_value]`, value: it.increment_value}).appendTo('#increment-form');
    });
    $btnSubmit.prop('disabled', true);
    // no preventDefault -> normal POST -> controller sets alert + redirects
  });

  // Delete (NO AJAX). Build a tiny POST form and submit, controller redirects.
  $(document).on('click', '.delete-increment', function(){
    const id = parseInt($(this).data('id'), 10) || 0;
    if (!id) return;
    if (!confirm('Delete this increment record?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= site_url('payroll/delete_increment') ?>';

    const inId = document.createElement('input');
    inId.type  = 'hidden';
    inId.name  = 'id';
    inId.value = id;
    form.appendChild(inId);

    document.body.appendChild(form);
    form.submit();
  });

  // Reset modal UI
  $('#incrementModal').on('show.bs.modal', function(){
    $('#inc_user_ids').val(null).trigger('change');
    $('#inc_department_id, #inc_position_id').val('');
    $('#inc_type').val('amount').trigger('change');
    $('#inc_value').val('');
    toggleScopeFields('');
    $tableBody.html('<tr class="text-muted"><td colspan="7">Please select a scope first</td></tr>');
    $countInfo.text('0 employees selected');
    $totalInfo.text('Total increase: ' + formatCurrency(0));
    $btnSubmit.prop('disabled', false);
  });
});
</script>
