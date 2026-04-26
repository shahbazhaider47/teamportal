<?php defined('BASEPATH') or exit('No direct script access allowed'); 

$loans = is_array($loans ?? null) ? $loans : [];
?>

<?php if (empty($loans)): ?>
  <div class="p-4 text-center text-muted border rounded">
    <i class="ti ti-inbox mb-2" style="font-size: 2rem;"></i>
    <p class="mb-0">No payroll loan requests found.</p>
  </div>
<?php else: ?>

  <div class="table-responsive">
    <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="requestsTable">
      <thead class="bg-light-primary">
        <tr>
          <th>Employee</th>
          <th>Loan Taken</th>
          <th>Payback Type</th>
          <th>Installments</th>
          <th>Monthly Installment</th>
          <th>Total Paid</th>
          <th>Balance</th>
          <th>Period</th>
          <th>Status</th>
          <th>Requested At</th>
          <th>Notes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($loans as $l): ?>
          <?php
            $name = $l['fullname']
              ?? trim(($l['firstname'] ?? '') . ' ' . ($l['lastname'] ?? ''));

            $loan_taken         = (float)($l['loan_taken']          ?? 0);
            $payback_type       = (string)($l['payback_type']       ?? '');
            $total_installments = (int)($l['total_installments']    ?? 0);
            $monthly_install    = (float)($l['monthly_installment'] ?? 0);
            $current_install    = (int)($l['current_installment']   ?? 0);
            $total_paid         = (float)($l['total_paid']          ?? 0);
            $balance            = (float)($l['balance']             ?? 0);
            $start_date         = $l['start_date']  ?? null;
            $end_date           = $l['end_date']    ?? null;
            $status             = $l['status']      ?? 'requested';
            $created_at         = $l['created_at']  ?? null;
            $notes              = trim((string)($l['notes'] ?? ''));

            $statusClass = 'badge bg-secondary-subtle text-muted';
            if ($status === 'requested') $statusClass = 'badge bg-warning-subtle text-warning';
            if ($status === 'approved')  $statusClass = 'badge bg-success-subtle text-success';
            if ($status === 'rejected')  $statusClass = 'badge bg-danger-subtle text-danger';
            if ($status === 'running')   $statusClass = 'badge bg-primary-subtle text-primary';
            if ($status === 'closed')    $statusClass = 'badge bg-success-subtle text-success';
          ?>
          <tr>
            <td><?= html_escape($name ?: 'Unknown'); ?></td>

            <td><span class="fw-semibold"><?= c_format($loan_taken); ?></span></td>

            <td><?= html_escape(ucfirst($payback_type)); ?></td>

            <td>
              <?php if ($total_installments > 0): ?>
                <span class="small">
                  <?= (int)$current_install; ?> / <?= (int)$total_installments; ?>
                </span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>

            <td>
              <?= $monthly_install > 0 ? c_format($monthly_install) : '<span class="text-muted small">—</span>'; ?>
            </td>

            <td>
              <?= $total_paid > 0 ? c_format($total_paid) : '<span class="text-muted small">—</span>'; ?>
            </td>

            <td>
              <?= $balance != 0.0 ? c_format($balance) : '<span class="text-muted small">—</span>'; ?>
            </td>

            <td>
              <?php if ($start_date || $end_date): ?>
                <span class="small">
                  <?= $start_date ? html_escape(format_date($start_date)) : '—'; ?>
                  &raquo;
                  <?= $end_date ? html_escape(format_date($end_date)) : '—'; ?>
                </span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>

            <td>
              <span class="<?= $statusClass; ?>">
                <?= html_escape(ucfirst($status)); ?>
              </span>
            </td>

            <td>
              <?php if (!empty($created_at)): ?>
                <span class="small"><?= format_datetime($created_at); ?></span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if ($notes !== ''): ?>
                <span class="small text-truncate d-inline-block" style="max-width: 220px;"
                      title="<?= html_escape($notes); ?>">
                  <?= html_escape($notes); ?>
                </span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>

            <td>
              <div class="btn-group btn-group-sm" role="group">
                <a href="<?= site_url('payroll/loans/view/' . (int)$l['id']); ?>"
                   class="btn btn-light-primary"
                   title="View">
                  <i class="ti ti-eye"></i>
                </a>
                <!-- Future: add approve/reject/cancel actions from Payroll logic -->
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>
