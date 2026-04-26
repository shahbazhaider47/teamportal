<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="addLeaveModal" tabindex="-1" role="dialog" aria-labelledby="addLeaveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="addLeaveForm" method="post" action="<?= base_url('attendance/leaves/add') ?>" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addLeaveModalLabel">
            <i class="ti ti-calendar-plus me-1"></i> Add Leave
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            
            <!-- Employee -->
            <div class="col-md-6">
              <label for="emp_id" class="form-label">Employee</label>
              <select class="form-select" name="emp_id" id="emp_id" required>
                <option value="">Select Employee</option>
                <?php foreach (get_all_staff() as $staff): ?>
                  <option value="<?= $staff['id'] ?>"><?= html_escape($staff['firstname'] . ' ' . $staff['lastname']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Leave Type -->
            <div class="col-md-6">
              <label for="leave_type" class="form-label">Leave Type</label>
              <select class="form-select" name="leave_type" id="leave_type" required>
                <option value="">Select Leave Type</option>
                <option value="Medical">Medical</option>
                <option value="Casual">Casual</option>
                <option value="Emergency">Emergency</option>
                <option value="Short">Short</option>
              </select>
            </div>

            <!-- Start Date -->
            <div class="col-md-6">
              <label for="start_date" class="form-label">Start Date</label>
              <input type="date" class="form-control" name="start_date" id="start_date" required>
            </div>

            <!-- End Date -->
            <div class="col-md-6">
              <label for="end_date" class="form-label">End Date</label>
              <input type="date" class="form-control" name="end_date" id="end_date" required>
            </div>

            <!-- Leave Days -->
            <div class="col-md-6">
              <label for="leave_days" class="form-label">Number of Days</label>
              <input type="number" class="form-control" name="leave_days" id="leave_days" min="0.5" step="0.5" required>
            </div>

            <!-- Attachment -->
            <div class="col-md-6">
              <label for="leave_attachment" class="form-label">Attachment (optional)</label>
              <input type="file" class="form-control" name="leave_attachment" id="leave_attachment" accept=".jpg,.png,.pdf,.doc,.docx">
            </div>

            <!-- Notes -->
            <div class="col-12">
              <label for="leave_notes" class="form-label">Leave Notes</label>
              <textarea class="form-control" name="leave_notes" id="leave_notes" rows="3" placeholder="Optional notes..."></textarea>
            </div>
          
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> Submit Leave
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancel
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
