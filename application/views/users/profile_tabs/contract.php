<div class="tab-pane fade"
     id="contract"
     role="tabpanel"
     aria-labelledby="contract-tab"
     tabindex="0">

  <?php if (!empty($contracts)): ?>
    <div class="card-body table-responsive">
      <table class="table small table-hover align-middle">
        <thead class="bg-light-primary">
          <tr>
            <th>Contract Type</th>
            <th>Period</th>
            <th>Status</th>
            <th>Contract File</th>
            <th>Signed At</th>
            <th>View</th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($contracts as $contract): ?>
            <tr>

              <!-- Contract Type -->
              <td>
                <strong><?= html_escape($contract['contract_type']); ?></strong>
                <?php if (!empty($contract['version'])): ?>
                  <div class="text-muted small">v<?= html_escape($contract['version']); ?></div>
                <?php endif; ?>
              </td>

              <!-- Period -->
              <td>
                <?php if (!empty($contract['start_date'])): ?>
                  <div><?= date('M d, Y', strtotime($contract['start_date'])); ?></div>
                  <?php if (!empty($contract['end_date'])): ?>
                    <div class="text-muted small">
                      to <?= date('M d, Y', strtotime($contract['end_date'])); ?>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">N/A</span>
                <?php endif; ?>
              </td>

              <!-- Status -->
              <td>
                <?php
                  $status = strtolower($contract['status'] ?? '');

                  if ($status === 'expired' || !empty($contract['expired_at'])) {
                    echo '<span class="badge bg-danger">Expired</span>';

                  } elseif ($status === 'sent') {
                    echo '<span class="badge bg-info">Sent</span>';

                  } elseif ($status === 'signed' || !empty($contract['signed_at'])) {
                    echo '<span class="badge bg-success">Signed</span>';

                  } elseif (!empty($contract['end_date'])) {
                    $end   = new DateTime($contract['end_date']);
                    $today = new DateTime();

                    if ($today->diff($end)->days <= 30 && $end >= $today) {
                      echo '<span class="badge bg-warning text-dark">Expiring</span>';
                    } else {
                      echo '<span class="badge bg-success">Active</span>';
                    }

                  } else {
                    echo '<span class="badge bg-secondary">Open</span>';
                  }
                ?>
              </td>

                <td>
                <?php if($contract['contract_file']): ?>
                    <div class="text-muted small mt-1">
                        <?= strtoupper(substr($contract['contract_file'], 1)) ?>
                    </div>
                <?php else: ?>
                    <span class="text-muted">No file</span>
                <?php endif ?>
                </td>

                <td>
                    <?php if($contract['signed_at']): ?>
                        <?= date('M d, Y', strtotime($contract['signed_at'])); ?>
                    <?php else: ?>
                    <span class="text-muted">Pending</span>
                    <?php endif ?>
                </td>
                                
              <!-- Contract File -->
              <td>
                <?php if (!empty($contract['contract_file'])): ?>
                  <a href="<?= site_url('contracts/view/' . (int)$contract['id']); ?>"
                     target="_blank"
                     class="btn btn-ssm btn-light-primary"
                     title="View Contract">
                    <i class="ti ti-external-link"></i> View
                  </a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  <?php else: ?>
    <div class="text-muted py-4 text-center">
      No staff contract found for this employee.
    </div>
  <?php endif; ?>

</div>
