<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-labelledby="applyLeaveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="post" action="<?= site_url('attendance/submit_leave_request') ?>" class="app-form"  enctype="multipart/form-data">

        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="applyLeaveModalLabel">
            <i class="ti ti-calendar-plus me-2"></i> Apply for Leave
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
<div id="leaveWarning" class="alert alert-warning d-none">
  <strong>Note:</strong> You have exhausted your balance for this leave type.
  This request will be treated as <strong>unpaid leave</strong>.
</div>
              
            <!-- Leave Type -->
            <div class="col-md-12">
              <label for="leave_type" class="form-label">Leave Type <span class="text-danger">*</span></label>
              <select id="leave_type" name="leave_type" class="form-select" required>
                <option value="" disabled selected>-- Select Leave Type --</option>
                <?php foreach ($leave_types as $type): ?>
                  <option value="<?= html_escape($type) ?>"><?= html_escape($type) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">Please select a leave type</div>
            </div>

            <!-- Date Range -->
            <div class="col-md-6">
              <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="text" id="start_date" name="start_date" class="form-control basic-date" placeholder="YYYY-MM-DD" min="<?= date('Y-m-d') ?>" required>
              <div class="invalid-feedback">Please select a valid start date</div>
            </div>

            <div class="col-md-6">
              <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="text" id="end_date" name="end_date" class="form-control basic-date" placeholder="YYYY-MM-DD" min="<?= date('Y-m-d') ?>" required>
              <div class="invalid-feedback">Please select a valid end date</div>
            </div>

            <!-- Attachment -->
            <div class="col-md-12">
              <label for="leave_attachment" class="form-label">Attachment / Proof</label>
              <input type="file" id="leave_attachment" name="leave_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
              <small class="text-muted">Max file size: 2MB (Allowed: JPG, PNG, PDF, DOC)</small>
            </div>

            <!-- Reason -->
            <div class="col-12">
              <label for="leave_notes" class="form-label">Reason / Notes <span class="text-danger">*</span></label>
              <textarea id="leave_notes" name="leave_notes" class="form-control" rows="3" placeholder="Please provide a detailed reason for your leave..." required></textarea>
              <div class="invalid-feedback">Please provide a reason for your leave</div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-send me-1"></i> Submit Request
          </button>
        </div>
      </form>
    </div>
  </div>
<script>
  const leaveBalances = <?= json_encode($leave_balances) ?>;
</script>
</div>


<script>
  function calculateLeaveDays(start, end) {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const msPerDay = 1000 * 60 * 60 * 24;
    return Math.floor((endDate - startDate) / msPerDay) + 1;
  }

  function checkLeaveBalance() {
    const leaveType = document.getElementById('leave_type').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    const warningBox = document.getElementById('leaveWarning');

    if (!leaveType || !startDate || !endDate) {
      warningBox.classList.add('d-none');
      return;
    }

    const days = calculateLeaveDays(startDate, endDate);
    const typeKey = leaveType.toLowerCase().replace(/\s+/g, '_'); // e.g., Short Leave => short_leave

    if (leaveBalances[typeKey]) {
      const used = parseInt(leaveBalances[typeKey].used || 0);
      const total = parseInt(leaveBalances[typeKey].total || 0);
      const remaining = total - used;

      if (remaining <= 0 || days > remaining) {
        warningBox.classList.remove('d-none');
      } else {
        warningBox.classList.add('d-none');
      }
    } else {
      // Unknown leave type, assume unpaid
      warningBox.classList.remove('d-none');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('leave_type').addEventListener('change', checkLeaveBalance);
    document.getElementById('start_date').addEventListener('change', checkLeaveBalance);
    document.getElementById('end_date').addEventListener('change', checkLeaveBalance);
  });
</script>

<script>
const disabledDates = <?= json_encode($disabled_dates ?? []) ?>;
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Helper to disable weekends and disabledDates from PHP
  function isWeekend(date) {
    return (date.getDay() === 0 || date.getDay() === 6); // Sunday=0, Saturday=6
  }

  function isDisabled(date) {
    const ymd = date.toISOString().slice(0, 10);
    return disabledDates.includes(ymd);
  }

  function disableFn(date) {
    return isWeekend(date) || isDisabled(date);
  }

  flatpickr(".basic-date", {
    dateFormat: "Y-m-d",
    minDate: "today",
    disable: [disableFn],
    allowInput: true
  });
});
</script>
