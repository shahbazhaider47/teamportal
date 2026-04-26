<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Leave Request Form Fields -->
<div class="card-body app-form">

  <div class="app-divider-v primary mb-4">
    <span class="badge text-bg-primary py-2">
      <i class="ti ti-calendar-time me-1"></i> New Leave Request
    </span>
  </div>

  <!-- JSON: leave_type -->
  <div class="row">

<div class="col-md-12 mb-3">
  <label for="leave_type_id" class="form-label">
    Leave Type <span class="text-danger">*</span>
  </label>

  <select id="leave_type_id"
          name="payload[leave_type_id]"
          class="form-select"
          required>

    <option value="">Select leave type</option>

    <?php foreach (get_leave_types() as $lt): ?>
      <option value="<?= (int) $lt['id'] ?>"
              data-code="<?= html_escape($lt['code']) ?>"
              data-unit="<?= html_escape($lt['unit']) ?>"
              data-attachment="<?= (int) $lt['attachment_required'] ?>">
        <?= html_escape($lt['name']) ?>
      </option>
    <?php endforeach; ?>

  </select>

  <div class="invalid-feedback">
    Please select a leave type
  </div>
</div>

            
    <!-- JSON: start_date -->
    <div class="col-md-4 mb-3">
      <label class="form-label">
        Start Date <span class="text-danger">*</span>
      </label>
      <input type="date"
             name="payload[start_date]"
             class="form-control basic-date"
             required>
    </div>

    <!-- JSON: end_date -->
    <div class="col-md-4 mb-3">
      <label class="form-label">
        End Date <span class="text-danger">*</span>
      </label>
      <input type="date"
             name="payload[end_date]"
             class="form-control basic-date"
             required>
    </div>
  </div>

  <!-- JSON: duration -->
  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label">
        Duration <span class="text-danger">*</span>
      </label>
      <select name="payload[duration]" class="form-select" required>
        <option value="full_day">Full Day</option>
        <option value="half_day">Half Day</option>
        <option value="multiple_days">Multiple Days</option>
      </select>
    </div>

    <!-- HEADER: Priority -->
    <div class="col-md-4 mb-3">
      <label class="form-label">Priority</label>
      <select name="priority" class="form-select">
        <option value="normal">Normal</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>

    <!-- HEADER: Department -->
    <div class="col-md-4 mb-3">
      <label class="form-label">Department</label>
      <select name="department_id" class="form-select">
        <?= department_dropdown_options(); ?>
      </select>
    </div>
  </div>

  <!-- JSON: reason -->
  <div class="mb-3">
    <label class="form-label">
      Reason <span class="text-danger">*</span>
    </label>
    <textarea name="payload[reason]"
              class="form-control"
              rows="3"
              maxlength="1000"
              placeholder="Brief reason for leave request"
              required></textarea>
  </div>

  <!-- JSON: contact_during_leave -->
  <div class="mb-3">
    <label class="form-label">Contact During Leave</label>
    <input type="text"
           name="payload[contact_during_leave]"
           class="form-control"
           maxlength="150"
           placeholder="Optional contact number or person">
  </div>

  <!-- ATTACHMENTS -->
<div class="mb-3" id="leaveAttachmentWrapper">
  <label class="form-label">
    Attachments
    <span class="small text-muted" id="leaveAttachmentHint">(Optional)</span>
    <span class="text-danger d-none" id="leaveAttachmentRequired">*</span>
  </label>

  <input type="file"
         name="attachments[]"
         id="leave_attachments"
         class="form-control"
         multiple>

  <small class="text-muted" id="leaveAttachmentHelp">
    Medical certificate or supporting documents.
  </small>
</div>


</div>

<script>
(function () {

  /**
   * Sync attachment required/optional state
   */
  function syncLeaveAttachment(select) {
    if (!select) return;

    const opt = select.options[select.selectedIndex];
    if (!opt) return;

    const isRequired    = opt.dataset.attachment === '1';
    const attachments  = document.getElementById('leave_attachments');
    const requiredMark = document.getElementById('leaveAttachmentRequired');
    const hint         = document.getElementById('leaveAttachmentHint');

    if (!attachments) return;

    if (isRequired) {
      attachments.required = true;
      requiredMark && requiredMark.classList.remove('d-none');
      hint && (hint.textContent = '(Required)');
    } else {
      attachments.required = false;
      requiredMark && requiredMark.classList.add('d-none');
      hint && (hint.textContent = '(Optional)');
    }
  }

  /**
   * Handle changes (AJAX-safe via delegation)
   */
  document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'leave_type_id') {
      syncLeaveAttachment(e.target);
    }
  });

  /**
   * Handle initial render (when form is injected)
   */
  document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('leave_type_id');
    if (select) {
      syncLeaveAttachment(select);
    }
  });

})();
</script>
