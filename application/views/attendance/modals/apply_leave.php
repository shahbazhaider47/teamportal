<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-labelledby="applyLeaveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">

      <form method="post"
            action="<?= site_url('attendance/submit_leave_request') ?>"
            class="app-form"
            enctype="multipart/form-data"
            id="leaveForm">

        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="applyLeaveModalLabel">
            <i class="ti ti-calendar-plus me-2"></i> Apply for Leave
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- leave_type_id -->
            <div class="col-md-4">
              <label class="form-label">Leave Type <span class="text-danger">*</span></label>
              <select name="payload[leave_type_id]" class="form-select" required>
                <option value="">Select</option>
                <?php foreach (($leave_types ?? []) as $lt): ?>
                  <option value="<?= (int)$lt['id'] ?>">
                    <?= html_escape($lt['name'] ?? '') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- start_date -->
            <div class="col-md-4">
              <label class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date"
                     name="payload[start_date]"
                     class="form-control future-date"
                     required>
            </div>

            <!-- end_date -->
            <div class="col-md-4">
              <label class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="date"
                     name="payload[end_date]"
                     class="form-control future-date"
                     required>
            </div>

            <!-- start_time -->
            <div class="col-md-4">
              <label class="form-label">Start Time</label>
              <input type="time"
                     name="payload[start_time]"
                     class="form-control">
            </div>

            <!-- end_time -->
            <div class="col-md-4">
              <label class="form-label">End Time</label>
              <input type="time"
                     name="payload[end_time]"
                     class="form-control">
            </div>

            <!-- total_hours -->
            <div class="col-md-4">
              <label class="form-label">Total Hours <span class="text-danger">*</span></label>
              <input type="number"
                     step="0.01"
                     name="payload[total_hours]"
                     class="form-control"
                     value="0.00"
                     required>
            </div>

            <!-- total_days -->
            <div class="col-md-4">
              <label class="form-label">Total Days <span class="text-danger">*</span></label>
              <input type="number"
                     step="0.01"
                     name="payload[total_days]"
                     class="form-control"
                     value="0.00"
                     required>
            </div>

            <!-- reason -->
            <div class="col-md-4">
              <label class="form-label">Reason</label>
              <textarea name="payload[reason]" class="form-control" rows="3"></textarea>
            </div>

            <!-- attachment_path -->
            <div class="col-md-4">
              <label class="form-label">Attachment</label>
              <input type="file"
                     name="attachment_path"
                     class="form-control"
                     accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
              <small class="text-muted">This will save the file and store its path in attachment_path.</small>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i> Close
          </button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-device-floppy me-1"></i> Submit
          </button>
        </div>

      </form>

    </div>
  </div>
</div>
