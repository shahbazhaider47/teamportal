<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="editLeaveModal" tabindex="-1" aria-labelledby="editLeaveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="post" action="<?= site_url('attendance/update_leave') ?>" class="app-form" enctype="multipart/form-data">
        <input type="hidden" name="id" id="edit_leave_id">

        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="editLeaveModalLabel">
            <i class="ti ti-edit me-2"></i> Edit Leave Request
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <!-- Leave Type -->
            <div class="col-md-12">
              <label for="edit_leave_type" class="form-label">Leave Type <span class="text-danger">*</span></label>
              <select id="edit_leave_type" name="leave_type" class="form-select" required>
                <option value="" disabled>-- Select Leave Type --</option>
                <?php foreach ($leave_types as $type): ?>
                  <option value="<?= html_escape($type) ?>"><?= html_escape($type) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">Please select a leave type</div>
            </div>

            <!-- Date Range -->
            <div class="col-md-6">
              <label for="edit_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date" id="edit_start_date" name="start_date" class="form-control" required>
              <div class="invalid-feedback">Please select a valid start date</div>
            </div>

            <div class="col-md-6">
              <label for="edit_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="date" id="edit_end_date" name="end_date" class="form-control" required>
              <div class="invalid-feedback">Please select a valid end date</div>
            </div>

            <!-- Existing Attachment Preview -->
            <div class="col-md-12">
              <label class="form-label">Current Attachment</label>
              <div id="edit_current_attachment" class="mb-2 text-muted small">No file uploaded</div>
            </div>

            <!-- Upload New Attachment -->
            <div class="col-md-12">
              <label for="edit_leave_attachment" class="form-label">Update Attachment</label>
              <input type="file" id="edit_leave_attachment" name="leave_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
              <small class="text-muted">Max 2MB (Allowed: JPG, PNG, PDF, DOC)</small>
            </div>

            <!-- Notes -->
            <div class="col-12">
              <label for="edit_leave_notes" class="form-label">Reason / Notes <span class="text-danger">*</span></label>
              <textarea id="edit_leave_notes" name="leave_notes" class="form-control" rows="3" required></textarea>
              <div class="invalid-feedback">Please provide a reason</div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-refresh me-1"></i> Update Leave
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal fade" id="editNotAllowedModal" tabindex="-1" aria-labelledby="editNotAllowedLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-dark" id="editNotAllowedLabel">
          <i class="ti ti-alert-triangle me-2"></i> Edit Not Allowed
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0 text-dark">This leave request has already been processed and cannot be edited.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



<script>
$(document).on('click', '.edit-leave-btn', function () {
  const $btn = $(this);

  $('#edit_leave_id').val($btn.data('id'));
  $('#edit_leave_type').val($btn.data('leave_type'));
  $('#edit_start_date').val($btn.data('start_date'));
  $('#edit_end_date').val($btn.data('end_date'));
  $('#edit_leave_notes').val($btn.data('notes'));

  const attachment = $btn.data('attachment');
  const previewHtml = attachment
    ? `<a href="${base_url}uploads/leaves/${attachment}" target="_blank">${attachment}</a>`
    : 'No file uploaded';
  $('#edit_current_attachment').html(previewHtml);
});

</script>
