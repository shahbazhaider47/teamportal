<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
  $accounts = $accounts ?? [];
  $table_id = $table_id ?? 'pfAccountsTable';
  $page_title = $page_title ?? 'PF Accounts';
  $canCreate = staff_can('create','payroll');
  $canEdit   = staff_can('edit','payroll');
  $canDelete = staff_can('delete','payroll');
?>
<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title) ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="<?= site_url('payroll') ?>" class="btn btn-light-primary btn-header b-r-4">
        <i class="ti ti-arrow-left"></i> Go Back
      </a>
      <?php if ($canCreate): ?>
      <button type="button" class="btn btn-primary btn-header b-r-4" data-bs-toggle="modal" data-bs-target="#pfAccountModal" onclick="PFACC.clearForm()">
        <i class="ti ti-plus"></i> New PF Account
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-sm table-bottom-border align-middle small" id="<?= html_escape($table_id) ?>">
        <thead class="bg-light-primary">
          <tr>
            <th>#</th>
            <th>Emp ID</th>
            <th>Employee</th>
            <th>UAN</th>
            <th>PF Member ID</th>
            <th class="text-end">Current Balance</th>
            <th>Opened</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($accounts as $a): ?>
            <?php
              $id   = (int)$a['id'];
              $view = site_url('payroll/pf_account/'.$id);
              $del  = site_url('payroll/delete_pf_account/'.$id);
            ?>
            <tr>
              <td><?= $id ?></td>
              <td><?= html_escape($a['emp_id'] ?? '') ?></td>
              <td><?= html_escape($a['fullname'] ?? '') ?></td>
              <td><?= html_escape($a['uan_number'] ?? '') ?></td>
              <td><?= html_escape($a['pf_member_id'] ?? '') ?></td>
              <td class="text-end"><?= number_format((float)($a['current_balance'] ?? 0), 2) ?></td>
              <td><?= html_escape($a['opened_at'] ?? '') ?></td>
              <td><span class="badge bg-light-primary text-primary"><?= html_escape($a['account_status'] ?: 'active') ?></span></td>
              <td class="text-end">
                <a href="<?= $view ?>" class="btn btn-outline-secondary btn-ssm" title="View">
                  <i class="ti ti-eye"></i>
                </a>
                <?php if ($canEdit): ?>
                <button type="button" class="btn btn-outline-secondary btn-ssm"
                        title="Edit" data-bs-toggle="modal" data-bs-target="#pfAccountModal"
                        onclick="PFACC.load(<?= $id ?>)">
                  <i class="ti ti-pencil"></i>
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <a href="<?= $del ?>" class="btn btn-outline-danger btn-ssm"
                   onclick="return confirm('Delete this PF account? This may remove or orphan transactions depending on your DB constraints.');"
                   title="Delete">
                  <i class="ti ti-trash"></i>
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

<!-- Create/Edit PF Account Modal -->
<div class="modal fade" id="pfAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="post" action="<?= site_url('payroll/save_pf_account') ?>" class="modal-content app-form" id="pfAccountForm">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="ti ti-building-bank me-2"></i> PF Account</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="id" id="pfa_id" value="">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">User ID</label>
            <input type="number" name="user_id" id="pfa_user_id" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">UAN Number</label>
            <input type="text" name="uan_number" id="pfa_uan" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">PF Member ID</label>
            <input type="text" name="pf_member_id" id="pfa_member" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Current Balance</label>
            <input type="number" step="0.01" name="current_balance" id="pfa_balance" class="form-control" value="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Emp Contrib %</label>
            <input type="number" step="0.01" name="employee_contribution_rate" id="pfa_emp_rate" class="form-control" value="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Employer Contrib %</label>
            <input type="number" step="0.01" name="employer_contribution_rate" id="pfa_empr_rate" class="form-control" value="0">
          </div>

          <div class="col-md-4">
            <label class="form-label">Wage Base Ceiling</label>
            <input type="number" step="0.01" name="wage_base_ceiling" id="pfa_wage_base" class="form-control" value="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Opened At</label>
            <input type="date" name="opened_at" id="pfa_opened" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Closed At</label>
            <input type="date" name="closed_at" id="pfa_closed" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Nominee Name</label>
            <input type="text" name="nominee_name" id="pfa_nominee" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nominee Relation</label>
            <input type="text" name="nominee_relation" id="pfa_nominee_rel" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nominee Share %</label>
            <input type="number" step="0.01" name="nominee_share_percent" id="pfa_nominee_pct" class="form-control" value="0">
          </div>

          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="account_status" id="pfa_status" class="form-select">
              <option value="active">Active</option>
              <option value="open">Open</option>
              <option value="closed">Closed</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Save</button>
      </div>
    </form>
  </div>
</div>

<script>
const PFACC = {
  clearForm(){
    document.getElementById('pfAccountForm').reset();
    document.getElementById('pfa_id').value = '';
  },
  load(id){
    this.clearForm();
    fetch('<?= site_url('payroll/get_pf_account_json/') ?>' + id)
      .then(r => r.json())
      .then(d => {
        if(!d || !d.id) return;
        document.getElementById('pfa_id').value          = d.id;
        document.getElementById('pfa_user_id').value     = d.user_id || '';
        document.getElementById('pfa_uan').value         = d.uan_number || '';
        document.getElementById('pfa_member').value      = d.pf_member_id || '';
        document.getElementById('pfa_balance').value     = d.current_balance || 0;
        document.getElementById('pfa_emp_rate').value    = d.employee_contribution_rate || 0;
        document.getElementById('pfa_empr_rate').value   = d.employer_contribution_rate || 0;
        document.getElementById('pfa_wage_base').value   = d.wage_base_ceiling || 0;
        document.getElementById('pfa_opened').value      = d.opened_at || '';
        document.getElementById('pfa_closed').value      = d.closed_at || '';
        document.getElementById('pfa_nominee').value     = d.nominee_name || '';
        document.getElementById('pfa_nominee_rel').value = d.nominee_relation || '';
        document.getElementById('pfa_nominee_pct').value = d.nominee_share_percent || 0;
        document.getElementById('pfa_status').value      = (d.account_status || 'active').toLowerCase();
      })
      .catch(()=>{});
  }
};
</script>
