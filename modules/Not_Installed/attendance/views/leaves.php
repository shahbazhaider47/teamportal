<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
  /* Hide all .leave-actions by default */
  .leave-actions {
    opacity: 0;
    visibility: hidden;
    transform: translateY(15px);
    transition: all 0.2s ease;
    pointer-events: none;
  }

  /* Show leave-actions when the entire row is hovered */
  tr:hover .leave-actions {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    pointer-events: auto;
  }
  
  /* Smooth transition for the buttons */
  .leave-actions .btn {
    transition: transform 0.1s ease, background-color 0.2s ease;
  }
  
</style>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport           = staff_can('export', 'general');
          $canPrint            = staff_can('print', 'general');
        ?>

        <a href="<?= site_url('attendance') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-clock"></i> Attendance
        </a>
        <a href="<?= site_url('attendance/leaves') ?>"
           class="btn btn-primary btn-header">
            <i class="ti ti-clipboard-list"></i> Leaves
        </a>
        <a href="<?= site_url('attendance/calendar') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-calendar-event"></i> Calendar
        </a>
        <a href="<?= site_url('attendance/tracker') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-map-pin"></i> Tracker
        </a>
        
        <div class="btn-divider"></div>

        <button class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
          <i class="ti ti-plus"></i> Apply Leave
        </button>
        
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'leavesTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
     
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>

<p class="text-muted p-2"><strong>Please Note: </strong><small>2 Short leaves = 1 casual leave and Emergency leaves will be deducted from allowed casual leaves.</small></p>  
  <div class="card">
      <div class="list-table-header app-scroll">
    <?php if (!empty($leave_balances)): ?>
      <div class="mb-3 d-flex flex-wrap gap-3">
        <span class="badge text-bg-primary">Annual Leaves: 14</span> 
        <span class="badge text-light-primary">US Holidays: 8</span> 
        <span class="badge text-light-info">Casual Leaves: <?= $leave_balances['casual']['used'] ?>/<?= $leave_balances['casual']['total'] ?></span>
        <span class="badge text-light-success">Medical Leaves: <?= $leave_balances['medical']['used'] ?>/<?= $leave_balances['medical']['total'] ?></span>
        <span class="badge text-light-secondary">Short Leaves: <?= $leave_balances['short']['used'] ?></span>
      </div>
    <?php endif; ?>
        <table class="table small table-bottom-border align-middle mb-2" id="leavesTable">
          <thead class="bg-light-primary">
            <tr>
              <th width="5%">ID</th>
              <th width="18%">Leave Type</th>
              <th width="18%">From</th>
              <th width="18%">To</th>
              <th width="8%">Days</th>
              <th width="8%">Status</th>
              <th width="25%">Reason</th>
            </tr>
          </thead>

          <tbody class="small">
            <?php if (!empty($leaves)): ?>
              <?php foreach ($leaves as $index => $leave): ?>
                <tr>
                  <td><?= $index + 1 ?></td>
                  <td class="hover-action-cell">
                    <?= html_escape(ucfirst($leave['leave_type'])) ?>
          
                    <div class="leave-actions d-flex small gap-2 mt-2">
                      <!-- View -->
                      <button type="button"
                              class="bg-transparent border-0 p-0 text-secondary"
                              onclick="loadLeaveDetails(<?= $leave['id'] ?>)"
                              data-bs-toggle="modal"
                              data-bs-target="#viewLeaveModal"
                              title="View Leave">
                        <i class="fas fa-eye"></i> View
                      </button>
                          <span class="small text-muted align-middle">|</span>                       
                      <!-- Edit -->
                      <?php if (staff_can('view_own', 'attendance')): ?>
                    <button type="button"
                            class="bg-transparent border-0 p-0 text-primary edit-leave-btn"
                            data-bs-toggle="modal"
                            data-id="<?= $leave['id'] ?>"
                            data-leave_type="<?= $leave['leave_type'] ?>"
                            data-start_date="<?= $leave['start_date'] ?>"
                            data-end_date="<?= $leave['end_date'] ?>"
                            data-notes="<?= html_escape($leave['leave_notes']) ?>"
                            data-attachment="<?= $leave['leave_attachment'] ?? '' ?>"
                            data-status="<?= $leave['status'] ?>"
                            title="Edit Leave">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                      <?php endif; ?>
                      <span class="small text-muted align-middle">|</span>
                      <!-- Delete -->
                      <?php if (staff_can('view_own', 'attendance')): ?>
                        <form action="<?= site_url('attendance/delete_leave') ?>"
                              method="post"
                              onsubmit="return confirm('Are you sure you want to delete this leave request?');"
                              style="display:inline;">
                          <input type="hidden" name="id" value="<?= $leave['id'] ?>">
                          <button type="submit"
                                  class="bg-transparent border-0 p-0 text-danger"
                                  title="Delete Leave">
                            <i class="fas fa-trash"></i> Delete
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td><?= format_date($leave['start_date'], 'd M Y') ?></td>
                  <td><?= format_date($leave['end_date'], 'd M Y') ?></td>
                  <td><?= calculate_leave_days($leave['start_date'], $leave['end_date']) ?></td>
                  <td>
                    <?php $status = $leave['status'] ?? 'pending'; ?>
                    <span class="badge bg-<?= get_leave_status_badge($status) ?>">
                      <?= ucfirst($status) ?>
                    </span>
                  </td>
                  <td><?= nl2br(html_escape($leave['leave_notes'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
  </div>
</div>
<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('attendance/modals/apply_leave', [], true); ?>
<?php echo $CI->load->view('attendance/modals/edit_leave', [], true); ?>
<?php echo $CI->load->view('attendance/modals/view_leave', [], true); ?>



<script>
  const base_url = "<?= base_url() ?>";

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-leave-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const status = this.getAttribute('data-status');

        if (status !== 'pending') {
          const warningModal = new bootstrap.Modal(document.getElementById('editNotAllowedModal'));
          warningModal.show();
          return;
        }

        // Populate edit modal
        document.getElementById('edit_leave_id').value = this.getAttribute('data-id');
        document.getElementById('edit_leave_type').value = this.getAttribute('data-leave_type');
        document.getElementById('edit_start_date').value = this.getAttribute('data-start_date');
        document.getElementById('edit_end_date').value = this.getAttribute('data-end_date');
        document.getElementById('edit_leave_notes').value = this.getAttribute('data-notes');

        const attachment = this.getAttribute('data-attachment');
        document.getElementById('edit_current_attachment').innerHTML = attachment
          ? `<a href="${base_url}uploads/attendance/${attachment}" target="_blank">${attachment}</a>`
          : 'No file uploaded';

        // Show edit modal only if status is pending
        const editModal = new bootstrap.Modal(document.getElementById('editLeaveModal'));
        editModal.show();
      });
    });
  });
</script>

