<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CUR = html_escape(get_base_currency_symbol()); ?>

<?php
  // from controller
  $table_id  = $table_id  ?? 'myAdvancesTable';
  $advances  = $advances  ?? [];
  $can_request_advance = isset($can_request_advance) ? (bool)$can_request_advance : true;

  // Safe date formatter (treat empty/zero as missing)
  $fmtDate = function ($d) {
      if (empty($d)) return null;
      $d = trim((string)$d);
      if ($d === '0000-00-00' || $d === '0000-00-00 00:00:00') return null;
      $t = strtotime($d);
      return $t ? date('Y-m-d', $t) : null;
  };

  // Status → badge class
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
    <h1 class="h6 header-title"><?= e($page_title ?? 'My Advances') ?></h1>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button"
              class="btn btn-primary btn-header"
              data-bs-toggle="modal"
              data-bs-target="#requestAdvanceModal"
              id="btnRequestAdvance"
              <?= $can_request_advance ? '' : 'disabled aria-disabled="true"' ?>>
        <i class="ti ti-cash me-1"></i> Request Advance
      </button>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
    <p class="text-muted small mb-4">Submit a request for a quick cash advance or advance salary — subject to management approval.</p>
      <table class="table table-sm table-bottom-border small align-middle" id="<?= e($table_id) ?>">
        <thead class="bg-light-primary">
          <tr>
            <th>#</th>
            <th>Amount</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Requested On</th>
            <th>Approved On</th>
            <th>Approved By</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($advances)): ?>
            <?php foreach ($advances as $row): ?>
              <?php
                $amt   = number_format((float)($row['amount'] ?? 0), 2);
                $paid  = number_format((float)($row['paid'] ?? 0), 2);
                $bal   = number_format((float)($row['balance'] ?? 0), 2);
                $st    = (string)($row['status'] ?? 'requested');
                $reqOn = $fmtDate($row['requested_at'] ?? null);
                $appOn = $fmtDate($row['approved_at'] ?? null);
                $appBy = $row['approved_by'] ?? null; // id; show as-is unless you join for name
                $notes = trim((string)($row['notes'] ?? ''));
              ?>
              <tr>
                <td><?= (int)($row['id'] ?? 0) ?></td>
                <td><?= $CUR . ' ' . $amt ?></td>
                <td><?= $CUR . ' ' . $paid ?></td>
                <td><?= $CUR . ' ' . $bal ?></td>
                <td><span class="<?= $statusClass($st) ?>"><?= e(ucfirst($st)) ?></span></td>
                <td><?= $reqOn ? e($reqOn) : 'N/A' ?></td>
                <td><?= $appOn ? e($appOn) : 'N/A' ?></td>
                <td><?= $appBy ? e($appBy) : 'N/A' ?></td>
                <td><?= $notes !== '' ? e($notes) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">No advances yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Request Advance Modal -->
<div class="modal fade" id="requestAdvanceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-md modal-dialog-top">
    <form method="post"
          action="<?= site_url('payroll/my/request_advance_submit') ?>"
          class="modal-content app-form"
          id="requestAdvanceForm">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-cash me-2"></i> Request an Advance
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <?php if (!$can_request_advance): ?>
          <div class="alert alert-warning d-flex align-items-start small mb-3">
            <i class="ti ti-alert-triangle me-2 mt-1"></i>
            <div>
              Advance/Loan requests are currently <strong>disabled</strong> by company settings.
              Please contact HR or Payroll if you believe this is an error.
            </div>
          </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><?= $CUR ?></span>
              <input type="number"
                     class="form-control"
                     name="amount"
                     id="ad_amount"
                     min="0"
                     step="0.01"
                     <?= $can_request_advance ? '' : 'disabled' ?>
                     required>
            </div>
          </div>

          <div class="col-md-12">
            <label class="form-label">Reason / Notes <span class="text-danger">*</span></label>
            <textarea name="notes"
                      id="ad_notes"
                      rows="2"
                      class="form-control"
                      <?= $can_request_advance ? '' : 'disabled' ?>
                      required></textarea>
          </div>
        </div>

        <div class="alert alert-light-primary small mt-3 mb-0">
          Your request will be submitted to management for approval.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary btn-sm" <?= $can_request_advance ? '' : 'disabled' ?>>
          <i class="ti ti-send me-1"></i> Submit Request
        </button>
      </div>
    </form>
  </div>
</div>

