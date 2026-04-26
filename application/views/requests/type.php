<?php defined('BASEPATH') or exit('No direct script access allowed');

$rows = $rows ?? [];
?>

<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
        <div class="d-flex align-items-center small gap-1">
          <span class="badge bg-light-primary border">Total: </span>
          <span class="badge bg-warning border">Pending: </span>
        </div>        
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $table_id     = $table_id ?? 'dataTable';
        ?>

        <a href="<?= site_url('requests') ?>"
           class="btn btn-outline-primary btn-header"
           title="Go back to Requests Overview">
            <i class="fas fa-arrow-left me-1"></i> Back to Requests
        </a>
        
        <div class="btn-divider"></div>
        
            <!-- Filter & Export Buttons-->
            <?php render_export_buttons([
                'filename' => $page_title ?? 'export'
            ]); ?>
            
      </div>
    </div>

    <!-- Universal table filter (global search + per-column filters) -->
    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                    'exclude_columns' => [''],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
  <div class="card shadow-sm border-0">
    <div class="card-body">

      <?php if (empty($rows)): ?>
        <div class="p-4 text-center text-muted">
          <i class="ti ti-inbox mb-2" style="font-size:2rem"></i>
          <p class="mb-0">No requests found.</p>
        </div>
      <?php else: ?>

        <div class="table-responsive">
        <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
            <thead class="bg-light-primary">
              <tr>
                <th>Request #</th>
                <th>Requested By</th>
                <th>Department</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Priority</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>

              <?php foreach ($rows as $row): ?>
                <?php
                  // Decode attachments safely
                  $attachments = [];
                  if (!empty($row['attachments'])) {
                      $decoded = json_decode($row['attachments'], true);
                      if (is_array($decoded)) {
                          $attachments = $decoded;
                      }
                  }
                ?>

                <tr>
                    
                  <td><?= html_escape($row['request_no']); ?></td>
                  <td><?= user_profile_image($row['requested_by']); ?></td>
                  <td>
                      <?= get_department_name($row['department_id']); ?>
                  </td>

                  <td>
                    <span class="badge bg-light-primary">
                      <?= ucfirst($row['status']); ?>
                    </span>
                  </td>

                  <td><?= format_datetime($row['submitted_at']); ?></td>

                    <td>
                        <span class="badge bg-<?= priority_class($row['priority']); ?>">
                            <?= ucfirst(html_escape($row['priority'])); ?>
                        </span>
                    </td>

                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                    
                        <!-- VIEW (MODAL) -->
                        <button type="button"
                                class="btn btn-outline-secondary"
                                data-id="<?= (int)$row['id']; ?>"
                                onclick="openRequestModal(this)"
                                title="View Request">
                          <i class="ti ti-eye"></i>
                        </button>
                    
                        <!-- DELETE -->
                        <a href="<?= site_url('requests/delete/' . (int)$row['id']); ?>"
                           class="btn btn-outline-danger"
                           onclick="return confirm('Delete this request?');"
                           title="Delete Request">
                          <i class="ti ti-trash"></i>
                        </a>
                    
                      </div>
                    </td>
            
                </tr>

              <?php endforeach; ?>

            </tbody>
          </table>
        </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Request View Modal (Global Shell) -->
<div class="modal fade" id="requestViewModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-body text-center text-muted p-5">
        Loading request…
      </div>
    </div>
  </div>
</div>

<script>
function openRequestModal(btn) {
  const id = btn.getAttribute('data-id');
  const modalEl = document.getElementById('requestViewModal');

  // Reset modal content
  modalEl.querySelector('.modal-content').innerHTML =
    '<div class="modal-body text-center text-muted p-5">Loading request…</div>';

  fetch('<?= site_url('requests/view_ajax'); ?>/' + id)
    .then(res => res.text())
    .then(html => {
      modalEl.querySelector('.modal-content').innerHTML = html;

      const modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
        backdrop: 'static',
        keyboard: true
      });

      modal.show();
    });
}
</script>