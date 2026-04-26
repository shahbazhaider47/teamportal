<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
  .attendance-edit-modal .modal-body { 
    padding: 0.75rem;
    overflow: hidden; /* Prevent double scroll */
  }

  .attendance-edit-modal .table-container {
    max-height: 55vh;
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 8px;
    overflow: auto; /* Enable both horizontal and vertical scrolling */
  }

  .attendance-edit-modal table {
    margin-bottom: 0;
    min-width: 600px; /* Minimum width before horizontal scroll appears */
  }

  .attendance-edit-modal table thead th {
    position: sticky;
    top: 0;
    z-index: 10; /* Higher z-index to stay above content */
    background: #eaf3ff;
    vertical-align: middle;
    box-shadow: 0 2px 3px rgba(0,0,0,0.05);
  }

  .attendance-edit-modal .header-check {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    min-width: 0; /* Allow text overflow */
  }

  .attendance-edit-modal .header-check span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
  }

  .attendance-edit-modal .header-check .form-check {
    margin: 0;
    padding-left: 1.4rem;
    flex-shrink: 0; /* Don't shrink checkbox */
  }

  .attendance-edit-modal .form-control-sm,
  .attendance-edit-modal .form-select-sm {
    font-size: 11px;
    padding: 0.25rem 0.4rem;
    min-width: 100px; /* Prevent inputs from becoming too small */
  }

  .attendance-edit-modal input[type="datetime-local"] {
    min-width: 170px;
  }

  .attendance-edit-modal .logtype-badge {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.3px;
    padding: 0.35rem 0.5rem;
    border-radius: 6px;
    text-transform: uppercase;
    display: inline-block;
    min-width: 105px;
    text-align: center;
  }

  .logtype-auto { background: #eaf3ff; color: #0d6efd; border: 1px solid rgba(13,110,253,.2); }
  .logtype-manual { background: #fff3cd; color: #856404; border: 1px solid rgba(133,100,4,.2); }
  .logtype-correction { background: #f8d7da; color: #842029; border: 1px solid rgba(132,32,41,.2); }

  /* Responsive adjustments */
  @media (max-width: 992px) {
    .attendance-edit-modal .modal-dialog {
      margin: 0.5rem;
      max-width: calc(100% - 1rem);
    }
    
    .attendance-edit-modal table {
      min-width: 550px;
    }
  }

  @media (max-width: 768px) {
    .attendance-edit-modal .modal-body {
      padding: 0.5rem;
    }
    
    .attendance-edit-modal .table-container {
      max-height: 50vh;
    }
    
    .attendance-edit-modal table {
      min-width: 500px;
    }
    
    .attendance-edit-modal .header-check span {
      font-size: 12px;
    }
    
    .attendance-edit-modal input[type="datetime-local"] {
      min-width: 160px;
    }
  }

  /* Hover effects */
  .attendance-edit-modal tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
  }

  /* Focus states */
  .attendance-edit-modal .form-control:focus,
  .attendance-edit-modal .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    z-index: 1;
    position: relative;
  }
</style>

<div class="modal fade attendance-edit-modal" id="EditattendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-primary py-2">
        <h6 class="modal-title text-white mb-0">
          <i class="ti ti-edit me-2"></i> Bulk Edit Attendance Logs
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <?= form_open(base_url('attendance/update_logs'), [
        'method' => 'post',
        'class'  => 'app-form',
        'id'     => 'attendanceEditForm'
      ]) ?>

      <div class="modal-body">
        <input type="hidden" name="user_id" value="<?= (int)($user_id ?? 0) ?>">
        <input type="hidden" name="year" value="<?= (int)($currentYear ?? date('Y')) ?>">
        <input type="hidden" name="month" value="<?= (int)($currentMonth ?? date('n')) ?>">

        <?php if (empty($logs)): ?>
          <div class="alert alert-warning mb-0">
            No logs found for this month.
          </div>
        <?php else: ?>

          <!-- Table container for proper scrolling -->
          <div class="table-container">
            <table class="table small table-sm table-hover align-middle mb-0" id="attendanceEditTable">
              <thead class="bg-light-primary">
                <tr class="text-nowrap">
                  <th style="width:50px; min-width:50px;">#</th>

                  <th style="width:120px;">Attendance Day</th>                  
                  
                  <th style="width:190px; min-width:190px;">
                    Attendance Date & Time
                  </th>

                  <!-- STATUS -->
                  <th style="width:160px; min-width:160px;">
                    <div class="header-check">
                      <span>Status</span>
                      <label class="form-check form-check-sm" title="Apply to all rows">
                        <input class="form-check-input apply-all" type="checkbox" data-col="status">
                      </label>
                    </div>
                  </th>

                  <!-- LOG TYPE -->
                  <th style="width:140px; min-width:140px;">
                    Log Type
                  </th>

                  <!-- APPROVAL -->
                  <th style="width:150px; min-width:150px;">
                    <div class="header-check">
                      <span>Approval</span>
                      <label class="form-check form-check-sm" title="Apply to all rows">
                        <input class="form-check-input apply-all" type="checkbox" data-col="approval_status">
                      </label>
                    </div>
                  </th>
                </tr>
              </thead>

              <tbody>
                <?php foreach (($logs ?? []) as $i => $row): ?>
                  <?php
                    $logId = (int)($row['id'] ?? 0);
                    if (!$logId) continue;

                    $dt = '';
                    if (!empty($row['datetime'])) {
                      $dt = date('Y-m-d\TH:i', strtotime($row['datetime']));
                    }

                    $status   = strtolower(trim((string)($row['status'] ?? 'other')));
                    $approval = strtoupper(trim((string)($row['approval_status'] ?? 'APPROVED')));
                    $logType  = strtoupper(trim((string)($row['log_type'] ?? 'AUTO')));

                    $logTypeClass = 'logtype-auto';
                    if ($logType === 'MANUAL') $logTypeClass = 'logtype-manual';
                    if ($logType === 'CORRECTION') $logTypeClass = 'logtype-correction';
                  ?>
                  <tr data-row="<?= $logId ?>">
                    <td class="text-muted fw-medium"><?= (int)$i + 1 ?></td>

                    <td class="text-muted fw-semibold">
                      <?= !empty($row['datetime']) ? html_escape(date('l', strtotime($row['datetime']))) : '—' ?>
                    </td>

                    <!-- DATETIME -->
                    <td>
                      <input type="datetime-local"
                             name="attendance_logs[<?= $logId ?>][datetime]"
                             value="<?= html_escape($dt) ?>"
                             class="form-control form-control-sm"
                             required>
                      <!-- Hidden originals for audit logic -->
                      <input type="hidden"
                             name="attendance_logs[<?= $logId ?>][original_datetime]"
                             value="<?= html_escape($dt) ?>"
                             class="small">
                      <input type="hidden"
                             name="attendance_logs[<?= $logId ?>][original_status]"
                             value="<?= html_escape($status) ?>">
                      <input type="hidden"
                             name="attendance_logs[<?= $logId ?>][original_log_type]"
                             value="<?= html_escape($logType) ?>">
                    </td>

                    <!-- STATUS -->
                    <td>
                      <select name="attendance_logs[<?= $logId ?>][status]"
                              data-col="status"
                              class="form-select form-select-sm"
                              required>
                        <option value="check_in" <?= $status === 'check_in' ? 'selected' : '' ?>>Check In</option>
                        <option value="check_out" <?= $status === 'check_out' ? 'selected' : '' ?>>Check Out</option>
                        <option value="overtime_in" <?= $status === 'overtime_in' ? 'selected' : '' ?>>Overtime In</option>
                        <option value="overtime_out" <?= $status === 'overtime_out' ? 'selected' : '' ?>>Overtime Out</option>
                        <option value="other" <?= $status === 'other' ? 'selected' : '' ?>>Other</option>
                      </select>
                    </td>

                    <!-- LOG TYPE DISPLAY -->
                    <td>
                      <span class="logtype-badge <?= $logTypeClass ?>">
                        <?= html_escape($logType) ?>
                      </span>
                    </td>

                    <!-- APPROVAL -->
                    <td>
                      <select name="attendance_logs[<?= $logId ?>][approval_status]"
                              data-col="approval_status"
                              class="form-select form-select-sm">
                        <option value="APPROVED" <?= $approval === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                        <option value="PENDING" <?= $approval === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                        <option value="REJECTED" <?= $approval === 'REJECTED' ? 'selected' : '' ?>>REJECTED</option>
                      </select>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        <?php endif; ?>
      </div>

      <div class="modal-footer py-2 border-top">
        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
          Cancel
        </button>
        <?php if (!empty($logs)): ?>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-device-floppy me-2"></i> Update Logs
          </button>
        <?php endif; ?>
      </div>
      <?= form_close() ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('EditattendanceModal');
  
  if (!modal) return;
  
  // Apply column value to all rows
  function applyColumnValue(colKey) {
    const table = document.getElementById('attendanceEditTable');
    if (!table) return;

    const firstInput = table.querySelector('tbody tr:first-child [data-col="' + colKey + '"]');
    if (!firstInput) return;

    const val = firstInput.value;
    const inputs = table.querySelectorAll('tbody [data-col="' + colKey + '"]');
    
    inputs.forEach(function(el) {
      el.value = val;
    });
    
    // Show notification
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.innerHTML = `
      <div class="toast align-items-center text-white bg-success border-0 show" role="alert">
        <div class="d-flex">
          <div class="toast-body">
            <i class="ti ti-check me-2"></i>
            Applied to all ${colKey.replace('_', ' ')} values
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  // Apply-all checkboxes
  const applyAllCheckboxes = modal.querySelectorAll('.apply-all');
  applyAllCheckboxes.forEach(function(cb) {
    cb.addEventListener('change', function() {
      const colKey = this.getAttribute('data-col');
      if (!colKey) return;
      
      if (this.checked) {
        applyColumnValue(colKey);
        // Uncheck after applying
        setTimeout(() => this.checked = false, 100);
      }
    });
  });

  // Auto-update log type to CORRECTION when datetime or status changes
  function setupAuditLogic() {
    const table = document.getElementById('attendanceEditTable');
    if (!table) return;

    table.querySelectorAll('tbody tr').forEach(row => {
      const datetimeInput = row.querySelector('input[type="datetime-local"]');
      const statusSelect = row.querySelector('[data-col="status"]');
      const logTypeBadge = row.querySelector('.logtype-badge');
      
      if (!datetimeInput || !statusSelect || !logTypeBadge) return;
      
      function checkForChanges() {
        const originalDatetime = row.querySelector('input[name*="original_datetime"]').value;
        const originalStatus = row.querySelector('input[name*="original_status"]').value;
        const originalLogType = row.querySelector('input[name*="original_log_type"]').value;
        
        const currentDatetime = datetimeInput.value;
        const currentStatus = statusSelect.value;
        
        // Check if datetime or status changed
        if (currentDatetime !== originalDatetime || currentStatus !== originalStatus) {
          // Update badge to CORRECTION
          logTypeBadge.textContent = 'CORRECTION';
          logTypeBadge.className = 'logtype-badge logtype-correction';
          
          // Update hidden field if it exists (optional, depends on your form structure)
          const logTypeInput = row.querySelector('[name*="log_type"]');
          if (logTypeInput) {
            logTypeInput.value = 'CORRECTION';
          }
        } else {
          // Revert to original
          logTypeBadge.textContent = originalLogType;
          const logTypeClass = originalLogType === 'MANUAL' ? 'logtype-manual' : 
                              originalLogType === 'CORRECTION' ? 'logtype-correction' : 'logtype-auto';
          logTypeBadge.className = 'logtype-badge ' + logTypeClass;
          
          const logTypeInput = row.querySelector('[name*="log_type"]');
          if (logTypeInput) {
            logTypeInput.value = originalLogType;
          }
        }
      }
      
      datetimeInput.addEventListener('change', checkForChanges);
      statusSelect.addEventListener('change', checkForChanges);
    });
  }

  // Initialize when modal is shown
  modal.addEventListener('shown.bs.modal', setupAuditLogic);
});
</script>