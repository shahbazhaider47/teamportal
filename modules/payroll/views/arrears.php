<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Payroll Arrears') ?></h1>
    <div class="d-flex align-items-center gap-2">
      <?php if (staff_can('create','payroll')): ?>
      <button class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#arrearModal" onclick="clearArrearForm()">
        <i class="ti ti-plus"></i> Add Arrear
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover small align-middle" id="<?= html_escape($table_id ?? 'payrollArrearsTable') ?>">
          <thead class="bg-light-primary">
            <tr>
              <th>#</th>
              <th>Employee</th>
              <th>Amount</th>
              <th>Reason</th>
              <th>Source</th>
              <th>Status</th>
              <th>Paid On</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if(!empty($arrears)): foreach($arrears as $a): ?>
            <tr>
              <td><?= (int)$a['id'] ?></td>
              <td>
                <?php
                  $name = trim(($a['fullname'] ?? '') ?: (($a['firstname'] ?? '').' '.($a['lastname'] ?? '')));
                  echo html_escape(($a['emp_id'] ? $a['emp_id'].' - ' : '').$name);
                ?>
              </td>
              <td><?= html_escape(get_base_currency_symbol()) . ' ' . number_format((float)$a['arrears_amount'], 2) ?></td>
              <td><?= html_escape($a['reason'] ?? '-') ?></td>
              <td><?= html_escape($a['source'] ?? '-') ?></td>
              <td><span class="badge bg-<?= ($a['status']==='paid'?'success':($a['status']==='cancelled'?'secondary':'warning')) ?>">
                <?= ucfirst(html_escape($a['status'] ?? 'pending')) ?>
              </span></td>
              <td><?= !empty($a['paid_on']) ? date('M d, Y', strtotime($a['paid_on'])) : '-' ?></td>
              <td><?= !empty($a['created_at']) ? date('M d, Y', strtotime($a['created_at'])) : '-' ?></td>
              <td class="d-flex gap-1">
                <button class="btn btn-ssm btn-light-primary" onclick="editArrear(<?= (int)$a['id'] ?>)" title="Edit">
                  <i class="ti ti-edit"></i>
                </button>
                <a class="btn btn-ssm btn-light-danger" href="<?= site_url('payroll/delete_arrear/'.(int)$a['id']) ?>" onclick="return confirm('Delete this arrear?')" title="Delete">
                  <i class="ti ti-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="9" class="text-center py-4">No arrears found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="arrearModal" tabindex="-1" aria-labelledby="arrearModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="app-form" id="arrearForm" method="post" action="<?= site_url('payroll/save_arrear') ?>">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="arrearModalLabel">Add Arrear</h5>
          <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="arrear_id">

          <div class="mb-3">
            <label class="form-label">Employee <span class="text-danger">*</span></label>
            <select name="user_id" id="arrear_user_id" class="form-select" required>
              <option value="" selected disabled>Select Employee</option>
              <?php foreach($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>">
                  <?= html_escape(($u['emp_id'] ? $u['emp_id'].' - ' : '').trim(($u['fullname'] ?? ($u['firstname'].' '.$u['lastname'])))) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control" name="arrears_amount" id="arrears_amount" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Reason</label>
            <input type="text" class="form-control" name="reason" id="arrear_reason" placeholder="Adjustment / correction / etc.">
          </div>

          <div class="mb-3">
            <label class="form-label">Source</label>
            <select class="form-select" name="source" id="arrear_source">
              <option value="" selected disabled>Select source</option>
              <option value="system">System</option>
              <option value="manual">Manual</option>
              <option value="import">Import</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="arrear_status">
              <option value="pending" selected>Pending</option>
              <option value="paid">Paid</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Paid On</label>
            <input type="date" class="form-control" name="paid_on" id="arrear_paid_on">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary btn-sm" type="submit">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function clearArrearForm(){
  const f = document.getElementById('arrearForm');
  f.reset();
  document.getElementById('arrear_id').value = '';
  document.getElementById('arrearModalLabel').innerText = 'Add Arrear';
}

function editArrear(id){
  fetch('<?= site_url('payroll/get_arrear_json/') ?>'+id)
    .then(r => r.json())
    .then(res => {
      if(!res || !res.id){ alert('Failed to load arrear'); return; }
      document.getElementById('arrear_id').value        = res.id;
      document.getElementById('arrear_user_id').value   = res.user_id;
      document.getElementById('arrears_amount').value   = res.arrears_amount ?? '';
      document.getElementById('arrear_reason').value    = res.reason ?? '';
      document.getElementById('arrear_source').value    = res.source ?? '';
      document.getElementById('arrear_status').value    = res.status ?? 'pending';
      document.getElementById('arrear_paid_on').value   = res.paid_on ?? '';
      document.getElementById('arrearModalLabel').innerText = 'Edit Arrear';
      new bootstrap.Modal(document.getElementById('arrearModal')).show();
    })
    .catch(() => alert('Failed to load arrear'));
}
</script>
