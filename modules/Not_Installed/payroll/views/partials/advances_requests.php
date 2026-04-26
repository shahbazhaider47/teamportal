<?php defined('BASEPATH') or exit('No direct script access allowed'); 

$advances = is_array($advances ?? null) ? $advances : [];
?>


<?php if (empty($advances)): ?>
  <div class="p-4 text-center text-muted border rounded">
    <i class="ti ti-inbox mb-2" style="font-size: 2rem;"></i>
    <p class="mb-0">No payroll advance requests found.</p>
  </div>
<?php else: ?>

  <div class="table-responsive">
    <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="requestsTable">
      <thead class="bg-light-primary">
        <tr>
          <th>Employee</th>
          <th>Amount</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Status</th>
          <th>Requested At</th>
          <th>Notes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($advances as $a): ?>
          <?php
            $name = $a['fullname']
              ?? trim(($a['firstname'] ?? '') . ' ' . ($a['lastname'] ?? ''));

            $amount      = (float)($a['amount']  ?? 0);
            $paid        = (float)($a['paid']    ?? 0);
            $balance     = (float)($a['balance'] ?? 0);
            $status      = $a['status'] ?? 'pending';
            $requestedAt = $a['requested_at'] ?? null;
            $notes       = trim((string)($a['notes'] ?? ''));

            $statusClass = 'badge bg-secondary-subtle text-muted';
            if ($status === 'pending')  $statusClass = 'badge bg-warning-subtle text-warning';
            if ($status === 'approved') $statusClass = 'badge bg-success-subtle text-success';
            if ($status === 'rejected') $statusClass = 'badge bg-danger-subtle text-danger';
            if ($status === 'paid')     $statusClass = 'badge bg-primary-subtle text-primary';
          ?>
          <tr>
            <td><?= html_escape($name ?: 'Unknown'); ?></td>

            <td>
              <span class="fw-semibold"><?= c_format($amount); ?></span>
            </td>

            <td>
              <?= $paid > 0 ? c_format($paid) : '<span class="text-muted small">—</span>'; ?>
            </td>

            <td>
              <?= $balance !== 0.0 ? c_format($balance) : '<span class="text-muted small">—</span>'; ?>
            </td>

            <td>
              <span class="<?= $statusClass; ?>">
                <?= html_escape(ucfirst($status)); ?>
              </span>
            </td>

            <td>
              <?php if (!empty($requestedAt)): ?>
                <span class="small"><?= format_datetime($requestedAt); ?></span>
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
                <a href="<?= site_url('payroll/advances/view/' . (int)$a['id']); ?>"
                   class="btn btn-light-primary"
                   title="View">
                  <i class="ti ti-eye"></i>
                </a>
                <!-- Future: add approve/reject buttons driven by Payroll logic -->
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>
