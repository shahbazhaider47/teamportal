<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CUR = html_escape(get_base_currency_symbol()); ?>

<?php
  $table_id = $table_id ?? 'payrollAdvancesTable';
  $advances = $advances ?? [];

  $fmtDate = function ($d) {
      if (empty($d)) return null;
      $d = trim((string)$d);
      if ($d === '0000-00-00' || $d === '0000-00-00 00:00:00') return null;
      $t = strtotime($d);
      return $t ? date('Y-m-d', $t) : null;
  };
  $statusClass = function ($s) {
      $s = strtolower((string)$s);
      return [
          'requested' => 'badge bg-warning text-dark',
          'approved'  => 'badge bg-primary',
          'scheduled' => 'badge bg-info text-dark',
          'paid'      => 'badge bg-success',
          'canceled'  => 'badge bg-secondary',
      ][$s] ?? 'badge bg-secondary';
  };
?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <h1 class="h6 header-title"><?= e($page_title ?? 'Payroll Advances') ?></h1>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-sm table-bottom-border small align-middle" id="<?= e($table_id) ?>">
        <thead class="bg-light-primary">
          <tr>
            <th>#</th>
            <th>Requested By</th>
            <th>Amount</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Requested On</th>
            <th>Approved</th>
            <th>Approved By</th>
            <th>Notes</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($advances)): foreach ($advances as $row): ?>
            <?php
              $amt  = number_format((float)($row['amount'] ?? 0), 2);
              $paid = number_format((float)($row['paid'] ?? 0), 2);
              $bal  = number_format((float)($row['balance'] ?? 0), 2);
              $st   = (string)($row['status'] ?? 'requested');
              $req  = $fmtDate($row['requested_at'] ?? null);
              $app  = $fmtDate($row['approved_at'] ?? null);

              // Show full name (fallback: emp_id, then UID)
              $userDisplay = trim((string)($row['fullname'] ?? ''));
              if ($userDisplay === '') { $userDisplay = trim((string)($row['emp_id'] ?? '')); }
              if ($userDisplay === '') { $userDisplay = 'UID:' . (int)($row['user_id'] ?? 0); }
            ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= e($row['requester_name'] ?? '—') ?>
              <span class="text-muted small d-block">ID: <?= e($row['emp_id']) ?></span>
              </td>
              <td><?= $CUR . ' ' . $amt ?></td>
              <td><?= $CUR . ' ' . $paid ?></td>
              <td><?= $CUR . ' ' . $bal ?></td>
              <td><span class="<?= $statusClass($st) ?>"><?= e(ucfirst($st)) ?></span></td>
              <td><?= $req ? e($req) : 'N/A' ?></td>
              <td><?= $app ? e($app) : 'N/A' ?></td>
              <td><?= e($row['approved_by_name'] ?? '—') ?></td>
              <td class="text-truncate" style="max-width:220px;" title="<?= e($row['notes'] ?? '') ?>"><?= e($row['notes'] ?? '') ?></td>
            <td class="text-center">
              <div class="btn-group btn-group-sm" role="group" aria-label="Advance actions">
                <button class="btn btn-outline-secondary  btn-edit-advance"
                        data-id="<?= (int)$row['id'] ?>">
                  <i class="ti ti-eye"></i>
                </button>
            
                <a href="<?= site_url('payroll/delete_advance/' . (int)$row['id']) ?>"
                   class="btn btn-outline-secondary  btn-delete-advance"
                   data-id="<?= (int)$row['id'] ?>">
                  <i class="ti ti-trash"></i>
                </a>
              </div>
            </td>

            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="11" class="text-center text-muted py-4">No advances found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Advance Modal -->
<div class="modal fade" id="editAdvanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-top">
    <form method="post"
          action="<?= site_url('payroll/save_advance') ?>"
          class="modal-content app-form"
          id="editAdvanceForm">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-cash me-2"></i> Advance Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="id" id="ad_id">
        <input type="hidden" name="user_id" id="ad_user_id">        
        <div class="row g-3">
        
        <div class="col-md-4">
          <label class="form-label">Requested By</label>
          <input type="text" class="form-control" id="ad_requester_name" readonly>
        </div>

          <div class="col-md-4">
            <label class="form-label">Amount</label>
            <div class="input-group">
              <span class="input-group-text"><?= $CUR ?></span>
              <input type="number" step="0.01" class="form-control" name="amount" id="ad_amount" required>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Paid</label>
            <div class="input-group">
              <span class="input-group-text"><?= $CUR ?></span>
              <input type="number" step="0.01" class="form-control" name="paid" id="ad_paid">
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Balance</label>
            <div class="input-group">
              <span class="input-group-text"><?= $CUR ?></span>
              <input type="number" step="0.01" class="form-control" name="balance" id="ad_balance" readonly>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="ad_status">
              <option value="requested">Requested</option>
              <option value="approved">Approved</option>
              <option value="scheduled">Scheduled</option>
              <option value="paid">Paid</option>
              <option value="canceled">Canceled</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Requested At</label>
            <input type="date" class="form-control" name="requested_at" id="ad_requested_at">
          </div>

          <div class="col-md-4">
            <label class="form-label">Approved At</label>
            <input type="date" class="form-control" name="approved_at" id="ad_approved_at">
          </div>

          <!-- Removed the Approved By field (auto-set in controller) -->

          <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" id="ad_notes" rows="2"></textarea>
          </div>
        </div>
        <div class="alert alert-info small mt-3 mb-0">
          Balance will auto-recalculate as <strong>Amount − Paid</strong>.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="ti ti-device-floppy me-1"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  function toDateOnly(d) {
    if (!d) return '';
    const t = Date.parse(d);
    if (isNaN(t)) return '';
    const dt = new Date(t);
    const m = String(dt.getMonth() + 1).padStart(2, '0');
    const day = String(dt.getDate()).padStart(2, '0');
    return dt.getFullYear() + '-' + m + '-' + day;
  }

  function recalcBalance() {
    const amtEl = document.getElementById('ad_amount');
    const paidEl = document.getElementById('ad_paid');
    const balEl = document.getElementById('ad_balance');
    if (!amtEl || !paidEl || !balEl) return;

    const a = parseFloat(amtEl.value || 0);
    const p = parseFloat(paidEl.value || 0);
    const b = Math.max(0, (a || 0) - (p || 0));
    balEl.value = b.toFixed(2);
  }

  document.getElementById('ad_amount')?.addEventListener('input', recalcBalance);
  document.getElementById('ad_paid')?.addEventListener('input', recalcBalance);

  // Open modal + load row via AJAX
  document.querySelectorAll('.btn-edit-advance').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      if (!id) return;

      fetch("<?= site_url('payroll/get_advance_json/') ?>" + id, { headers: { 'Accept': 'application/json' } })
        .then(function (r) {
          if (!r.ok) throw new Error('Network response was not ok');
          return r.json();
        })
        .then(function (row) {
          const el = function (id) { return document.getElementById(id); };

          if (el('ad_id'))                el('ad_id').value             = row.id || '';
          if (el('ad_user_id'))           el('ad_user_id').value        = row.user_id || '';
          if (el('ad_requester_name'))    el('ad_requester_name').value =
            row.requester_name || row.fullname || (row.emp_id ? row.emp_id : ('UID:' + (row.user_id || '')));

          if (el('ad_amount'))            el('ad_amount').value         = (row.amount ?? 0);
          if (el('ad_paid'))              el('ad_paid').value           = (row.paid ?? 0);
          if (el('ad_balance'))           el('ad_balance').value        = (row.balance ?? 0);
          if (el('ad_status'))            el('ad_status').value         = (row.status || 'requested');
          if (el('ad_requested_at'))      el('ad_requested_at').value   = toDateOnly(row.requested_at || row.created_at || '');
          if (el('ad_approved_at'))       el('ad_approved_at').value    = toDateOnly(row.approved_at || '');
          if (el('ad_notes'))             el('ad_notes').value          = row.notes || '';

          recalcBalance();

          const modalEl = el('editAdvanceModal');
          const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.show();
        })
        .catch(function (err) {
          console.error('Failed to load advance:', err);
        });
    });
  });
})();
</script>

<script>
document.querySelectorAll('.btn-delete-advance').forEach(function(btn){
  btn.addEventListener('click', function(e){
    e.preventDefault();
    const href = this.getAttribute('href');
    const id = this.getAttribute('data-id');
    if (confirm('Delete advance #' + id + '? This cannot be undone.')) {
      window.location.href = href;
    }
  });
});
</script>
