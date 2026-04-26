<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
  $account    = $account ?? [];
  $txns       = $txns ?? [];
  $table_id   = $table_id ?? 'pfTxnTable';
  $page_title = $page_title ?? 'PF Account';
  $aid        = (int)($account['id'] ?? 0);
  $CUR        = html_escape(get_base_currency_symbol());
  $canCreate  = staff_can('create','payroll');
  $canEdit    = staff_can('edit','payroll');
  $canDelete  = staff_can('delete','payroll');
?>
<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title) ?> > <span class="text-muted small">#<?= $aid ?> — <?= html_escape($account['fullname'] ?? 'Employee') ?></span></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="<?= site_url('payroll/pf_accounts') ?>" class="btn btn-light-primary btn-header b-r-4">
        <i class="ti ti-arrow-left"></i> Back to Accounts
      </a>
      <?php if ($canCreate): ?>
      <button type="button" class="btn btn-primary btn-header b-r-4" data-bs-toggle="modal" data-bs-target="#pfTxnModal" onclick="PFTXN.clearForm(<?= $aid ?>)">
        <i class="ti ti-plus"></i> Add Transaction
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Account summary card -->
  <div class="card shadow-sm mb-3">
    <div class="card-body small">
      <div class="row g-3">
        <div class="col-md-3"><strong>Emp ID:</strong> <?= html_escape($account['emp_id'] ?? '—') ?></div>
        <div class="col-md-3"><strong>Employee:</strong> <?= html_escape($account['fullname'] ?? '—') ?></div>
        <div class="col-md-3"><strong>UAN:</strong> <?= html_escape($account['uan_number'] ?? '—') ?></div>
        <div class="col-md-3"><strong>PF Member ID:</strong> <?= html_escape($account['pf_member_id'] ?? '—') ?></div>

        <div class="col-md-3"><strong>Emp %:</strong> <?= (float)($account['employee_contribution_rate'] ?? 0) ?>%</div>
        <div class="col-md-3"><strong>Employer %:</strong> <?= (float)($account['employer_contribution_rate'] ?? 0) ?>%</div>
        <div class="col-md-3"><strong>Wage Base Ceiling:</strong> <?= $CUR.' '.number_format((float)($account['wage_base_ceiling'] ?? 0),2) ?></div>
        <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-light-primary text-primary"><?= html_escape($account['account_status'] ?? 'active') ?></span></div>

        <div class="col-md-3"><strong>Opened:</strong> <?= html_escape($account['opened_at'] ?? '—') ?></div>
        <div class="col-md-3"><strong>Closed:</strong> <?= html_escape($account['closed_at'] ?? '—') ?></div>
        <div class="col-md-3"><strong>Current Balance:</strong> <?= $CUR.' '.number_format((float)($account['current_balance'] ?? 0), 2) ?></div>
        <div class="col-md-3"><strong>Email:</strong> <?= html_escape($account['email'] ?? '—') ?></div>
      </div>
    </div>
  </div>

  <!-- Transactions -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h6 class="mb-3">Transactions</h6>
      <table class="table table-sm table-bottom-border align-middle small" id="<?= html_escape($table_id) ?>">
        <thead class="bg-light-primary">
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Type</th>
            <th class="text-end">Amount</th>
            <th class="text-end">Emp Share</th>
            <th class="text-end">Employer Share</th>
            <th>FY</th>
            <th>Ref</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($txns as $t): ?>
            <?php
              $tid = (int)$t['id'];
              $del = site_url('payroll/delete_pf_txn/'.$tid);
            ?>
            <tr>
              <td><?= $tid ?></td>
              <td><?= html_escape($t['txn_date'] ?? '') ?></td>
              <td><?= html_escape($t['transaction_type'] ?? '') ?></td>
              <td class="text-end"><?= number_format((float)($t['amount'] ?? 0), 2) ?></td>
              <td class="text-end"><?= number_format((float)($t['employee_share'] ?? 0), 2) ?></td>
              <td class="text-end"><?= number_format((float)($t['employer_share'] ?? 0), 2) ?></td>
              <td><?= html_escape($t['financial_year'] ?? '') ?></td>
              <td><small><?= html_escape(($t['reference_module'] ?? '').'#'.($t['reference_id'] ?? '')) ?></small></td>
              <td><span class="badge bg-light-primary text-primary"><?= html_escape($t['status'] ?? 'posted') ?></span></td>
              <td class="text-end">
                <?php if ($canEdit): ?>
                <button type="button" class="btn btn-outline-secondary btn-ssm"
                        title="Edit" data-bs-toggle="modal" data-bs-target="#pfTxnModal"
                        onclick='PFTXN.load(<?= json_encode($t,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>
                  <i class="ti ti-pencil"></i>
                </button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <a href="<?= $del ?>" class="btn btn-outline-danger btn-ssm" title="Delete"
                   onclick="return confirm('Delete this transaction?');">
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

<!-- Create/Edit PF Transaction Modal -->
<div class="modal fade" id="pfTxnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="post" action="<?= site_url('payroll/save_pf_txn') ?>" class="modal-content app-form" id="pfTxnForm">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="ti ti-arrows-exchange-2 me-2"></i> PF Transaction</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="id" id="pft_id" value="">
        <input type="hidden" name="pf_account_id" id="pft_account_id" value="<?= $aid ?>">

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="txn_date" id="pft_date" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Type</label>
            <select name="transaction_type" id="pft_type" class="form-select">
              <option value="contribution">Contribution</option>
              <option value="withdrawal">Withdrawal</option>
              <option value="interest">Interest</option>
              <option value="adjustment">Adjustment</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" id="pft_status" class="form-select">
              <option value="posted">Posted</option>
              <option value="pending">Pending</option>
              <option value="void">Void</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Amount</label>
            <input type="number" step="0.01" name="amount" id="pft_amount" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Employee Share</label>
            <input type="number" step="0.01" name="employee_share" id="pft_emp" class="form-control" value="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">Employer Share</label>
            <input type="number" step="0.01" name="employer_share" id="pft_empr" class="form-control" value="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">Interest Rate (%)</label>
            <input type="number" step="0.01" name="interest_rate" id="pft_ir" class="form-control" value="0">
          </div>

          <div class="col-md-4">
            <label class="form-label">Financial Year</label>
            <input type="text" name="financial_year" id="pft_fy" class="form-control" placeholder="e.g. 2025-26">
          </div>
          <div class="col-md-4">
            <label class="form-label">Reference Module</label>
            <input type="text" name="reference_module" id="pft_ref_mod" class="form-control" placeholder="payroll/run">
          </div>
          <div class="col-md-4">
            <label class="form-label">Reference ID</label>
            <input type="text" name="reference_id" id="pft_ref_id" class="form-control" placeholder="e.g. 2025081318194794">
          </div>

          <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" id="pft_notes" class="form-control" rows="2"></textarea>
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
const PFTXN = {
  clearForm(aid){
    const f = document.getElementById('pfTxnForm');
    f.reset();
    document.getElementById('pft_id').value = '';
    document.getElementById('pft_account_id').value = aid || <?= $aid ?>;
    // sensible defaults
    const today = new Date().toISOString().slice(0,10);
    document.getElementById('pft_date').value = today;
    document.getElementById('pft_status').value = 'posted';
    document.getElementById('pft_type').value = 'contribution';
  },
  load(row){
    this.clearForm(row.pf_account_id || <?= $aid ?>);
    // fill
    document.getElementById('pft_id').value        = row.id || '';
    document.getElementById('pft_account_id').value= row.pf_account_id || <?= $aid ?>;
    document.getElementById('pft_date').value      = row.txn_date || '';
    document.getElementById('pft_type').value      = (row.transaction_type || 'contribution').toLowerCase();
    document.getElementById('pft_status').value    = (row.status || 'posted').toLowerCase();
    document.getElementById('pft_amount').value    = row.amount || 0;
    document.getElementById('pft_emp').value       = row.employee_share || 0;
    document.getElementById('pft_empr').value      = row.employer_share || 0;
    document.getElementById('pft_ir').value        = row.interest_rate || 0;
    document.getElementById('pft_fy').value        = row.financial_year || '';
    document.getElementById('pft_ref_mod').value   = row.reference_module || '';
    document.getElementById('pft_ref_id').value    = row.reference_id || '';
    document.getElementById('pft_notes').value     = row.notes || '';
  }
};
</script>
