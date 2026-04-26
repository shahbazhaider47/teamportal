<?php defined('BASEPATH') or exit('No direct script access allowed'); 

$purchases = is_array($purchases ?? null) ? $purchases : [];
?>

<?php if (empty($purchases)): ?>
  <div class="p-4 text-center text-muted border rounded">
    <i class="ti ti-inbox mb-2" style="font-size: 2rem;"></i>
    <p class="mb-0">No asset purchase requests found.</p>
  </div>
<?php else: ?>

  <div class="table-responsive">
    <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="requestsTable">
      <thead class="bg-light-primary">
        <tr>
          <th>Requested By</th>
          <th>Title</th>
          <th>Asset Type</th>
          <th>Required Qty</th>
          <th>Cost / Item</th>
          <th>Total Amount</th>
          <th>Status</th>
          <th>Date Required</th>
          <th>Requested At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($purchases as $p): ?>
          <?php
            $name = $p['fullname']
              ?? trim(($p['firstname'] ?? '') . ' ' . ($p['lastname'] ?? ''));

            $title            = trim((string)($p['purchase_title'] ?? ''));
            $description      = trim((string)($p['description'] ?? ''));
            $assetTypeId      = $p['asset_type_id'] ?? null;
            $requiredQty      = (int)($p['required_quantity'] ?? 0);
            $costPerItem      = (float)($p['cost_per_item'] ?? 0);
            $totalAmount      = (float)($p['total_amount'] ?? 0);
            $status           = $p['purchase_status'] ?? 'requested';
            $dateRequired     = $p['date_required'] ?? null;
            $createdAt        = $p['created_at'] ?? null;
            $purchaseSource   = trim((string)($p['purchase_source'] ?? ''));
            $paymentMethod    = trim((string)($p['payment_method'] ?? ''));

            // You can later join asset_types to show proper name; for now show ID.
            $assetTypeLabel = $assetTypeId ? ('#' . (int)$assetTypeId) : '—';

            $statusClass = 'badge bg-secondary-subtle text-muted';
            if ($status === 'requested') $statusClass = 'badge bg-warning-subtle text-warning';
            if ($status === 'approved')  $statusClass = 'badge bg-success-subtle text-success';
            if ($status === 'rejected')  $statusClass = 'badge bg-danger-subtle text-danger';
            if ($status === 'purchased') $statusClass = 'badge bg-primary-subtle text-primary';
          ?>
          <tr>
            <td><?= user_profile_image($name ?: 'Unknown'); ?></td>

            <td>
              <div class="fw-semibold">
                <?= html_escape($title ?: 'Untitled Request'); ?>
              </div>
              <?php if ($description !== ''): ?>
                <div class="small text-muted text-truncate" style="max-width: 260px;"
                     title="<?= html_escape($description); ?>">
                  <?= html_escape($description); ?>
                </div>
              <?php endif; ?>
            </td>

            <td><?= html_escape($assetTypeLabel); ?></td>

            <td><?= $requiredQty > 0 ? (int)$requiredQty : '<span class="text-muted small">—</span>'; ?></td>

            <td>
              <?= $costPerItem > 0 ? c_format($costPerItem) : '<span class="text-muted small">—</span>'; ?>
            </td>

            <td>
              <?= $totalAmount > 0 ? c_format($totalAmount) : '<span class="text-muted small">—</span>'; ?>
            </td>

            <td>
              <span class="<?= $statusClass; ?>">
                <?= html_escape(ucfirst($status)); ?>
              </span>
            </td>

            <td>
              <?php if (!empty($dateRequired)): ?>
                <span class="small"><?= format_date($dateRequired); ?></span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if (!empty($createdAt)): ?>
                <span class="small"><?= format_datetime($createdAt); ?></span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>

            <td>
              <div class="btn-group btn-group-sm" role="group">
                <a href="<?= site_url('assets/purchases/view/' . (int)$p['id']); ?>"
                   class="btn btn-light-primary"
                   title="View">
                  <i class="ti ti-eye"></i>
                </a>
                <!-- Future: add approve/reject/mark purchased actions as per Assets logic -->
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>
