<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="card-body">
  <div class="mb-4">
    <h5 class="card-title">Attendance Settings</h5>
    <p class="text-muted">Configure how the Attendance module should behave across the application.</p>
  </div>

  <div class="row app-form">
    <!-- Time Settings Section -->
    <div class="col-md-7">
      <div class="border-bottom pb-3 mb-4">
        <h6 class="mb-3"><i class="fas fa-clock me-2"></i>Office Timings</h6>
        
        <!-- Grace Period -->
        <div class="mb-3">
          <label class="form-label" for="attendance_grace_period">Grace Period (minutes)</label>
          <input type="number"
                 class="form-control"
                 id="attendance_grace_period"
                 name="settings[attendance_grace_period]"
                 min="0"
                 max="30"
                 value="<?= e($existing_data['attendance_grace_period'] ?? 5); ?>">
        </div>

        <!-- Office Hours -->
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label" for="office_start_time">Office Start Time</label>
            <input type="time"
                   class="form-control"
                   id="office_start_time"
                   name="settings[office_start_time]"
                   value="<?= e($existing_data['office_start_time'] ?? '09:00'); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="office_end_time">Office End Time</label>
            <input type="time"
                   class="form-control"
                   id="office_end_time"
                   name="settings[office_end_time]"
                   value="<?= e($existing_data['office_end_time'] ?? '17:00'); ?>">
          </div>
        </div>

        <!-- Break Timing -->
        <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label small text-muted"> Break Start Time</label>
              <input type="time"
                     class="form-control"
                     id="break_start_time"
                     name="settings[break_start_time]"
                     value="<?= e($existing_data['break_start_time'] ?? '13:00'); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small text-muted">Break End Time</label>
              <input type="time"
                     class="form-control"
                     id="break_end_time"
                     name="settings[break_end_time]"
                     value="<?= e($existing_data['break_end_time'] ?? '14:00'); ?>">
            </div>
          </div>

        <!-- Working Days -->
        <div class="mb-3">
          <label class="form-label">Working Days</label>
          <div class="d-flex flex-wrap gap-2">
            <?php 
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $selected_days = isset($existing_data['working_days']) ? json_decode($existing_data['working_days'], true) : [0,1,2,3,4]; // Default Mon-Fri
            ?>
            <?php foreach($days as $index => $day): ?>
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="day_<?= $index ?>" 
                       name="settings[working_days][]" 
                       value="<?= $index ?>"
                       <?= in_array($index, $selected_days) ? 'checked' : '' ?>>
                <label class="badge text-light-primary me-2" for="day_<?= $index ?>"><?= substr($day, 0, 3) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Leave Settings Section -->
<div class="col-md-5">
  <div class="border-bottom pb-3 mb-4">
    <h6 class="mb-3">
      <i class="fas fa-calendar-alt me-2"></i>
      Leaves Allocation <small>(Per Year)</small>
    </h6>

    <div class="row">
      <!-- Medical Leaves -->
      <div class="col-md-6 mb-3">
        <label class="form-label" for="medical_leaves">Medical Leaves</label>
        <input type="number"
               class="form-control"
               id="medical_leaves"
               name="settings[medical_leaves]"
               min="0"
               value="<?= e($existing_data['medical_leaves'] ?? 8); ?>">
      </div>

      <!-- Casual Leaves -->
      <div class="col-md-6 mb-3">
        <label class="form-label" for="casual_leaves">Casual Leaves</label>
        <input type="number"
               class="form-control"
               id="casual_leaves"
               name="settings[casual_leaves]"
               min="0"
               value="<?= e($existing_data['casual_leaves'] ?? 10); ?>">
      </div>

      <!-- Public Holidays -->
      <div class="col-md-6 mb-3">
        <label class="form-label" for="holiday_leaves">US Holidays</label>
        <input type="number"
               class="form-control"
               id="holiday_leaves"
               name="settings[holiday_leaves]"
               min="0"
               value="<?= e($existing_data['holiday_leaves'] ?? 12); ?>">
      </div>

      <!-- Leave Carry Forward -->
      <div class="col-md-6 mb-3">
        <label class="form-label" for="leave_carry_forward">Leaves Carry Forward</label><br>
        <input type="number"
               class="form-control"
               id="leave_carry_forward"
               name="settings[leave_carry_forward]"
               min="0"
               value="<?= e($existing_data['leave_carry_forward'] ?? 5); ?>">
      </div>
    </div>
  </div>
</div>

  </div>

<div class="mb-4 border-top pt-3">
    <label class="form-label fw-bold">Leave Types</label>
    <small class="text-muted d-block mb-2">
        Define available leave types for your organization (e.g. Casual, Medical, Marriage).
    </small>
    <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add-leave-type">
        <i class="fas fa-plus"></i> Add Leave Type
    </button>
    <?php
    // Load existing leave types from settings (JSON string or array)
    $leave_types = [];
    if (!empty($existing_data['leave_types'])) {
        if (is_array($existing_data['leave_types'])) {
            $leave_types = $existing_data['leave_types'];
        } else {
            $decoded = json_decode($existing_data['leave_types'], true);
            $leave_types = is_array($decoded) ? $decoded : [];
        }
    }
    ?>
    <div id="leave-types-list">
        <?php foreach ($leave_types as $i => $type): ?>
            <div class="input-group mb-2 leave-type-row">
                <input type="text"
                       class="form-control small"
                       name="settings[leave_types][<?= $i ?>]"
                       value="<?= htmlspecialchars($type) ?>"
                       placeholder="Leave Type Name"
                       required>
                <button type="button" class="btn btn-danger btn-remove-leave-type" tabindex="-1">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

  <!-- Automation Settings -->
  <div class="mt-4">
    <h6 class="mb-3"><i class="fas fa-robot me-2"></i>Automation Settings</h6>

    <!-- Late Arrival Penalty -->
    <div class="mb-3 form-check form-switch">
      <input class="form-check-input" 
             type="checkbox" 
             role="switch" 
             id="late_penalty_enabled" 
             name="settings[late_penalty_enabled]"
             value="1" 
             <?= (isset($existing_data['late_penalty_enabled']) && $existing_data['late_penalty_enabled'] == '1') ? 'checked' : '' ?>>
      <label class="form-check-label" for="late_penalty_enabled">Enable Late Arrival Penalty</label>
      <small class="text-muted d-block">Deduct salary for late arrivals after grace period</small>
    </div>

    <!-- Early Departure Penalty -->
    <div class="mb-3 form-check form-switch">
      <input class="form-check-input" 
             type="checkbox" 
             role="switch" 
             id="early_departure_penalty" 
             name="settings[early_departure_penalty]"
             value="1" 
             <?= (isset($existing_data['early_departure_penalty']) && $existing_data['early_departure_penalty'] == '1') ? 'checked' : '' ?>>
      <label class="form-check-label" for="early_departure_penalty">Enable Early Checkout Penalty</label>
      <small class="text-muted d-block">Deduct salary for early departures before office end time</small>
    </div>

  </div>
</div>

<script>
  // Enable tooltips
  document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Add new leave type row
    document.getElementById('add-leave-type').onclick = function () {
        var list = document.getElementById('leave-types-list');
        var idx = list.querySelectorAll('.leave-type-row').length;
        var html = `
        <div class="input-group mb-2 leave-type-row">
            <input type="text" class="form-control" name="settings[leave_types][${idx}]" placeholder="Leave Type Name" required>
            <button type="button" class="btn btn-danger btn-remove-leave-type" tabindex="-1">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        `;
        list.insertAdjacentHTML('beforeend', html);
    };

    // Remove leave type row (event delegation)
    document.getElementById('leave-types-list').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-leave-type')) {
            e.target.closest('.leave-type-row').remove();
        }
    });
});
</script>