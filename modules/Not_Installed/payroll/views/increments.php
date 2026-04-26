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
