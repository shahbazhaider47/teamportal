<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="addAllowanceModal" tabindex="-1" aria-labelledby="addAllowanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="addAllowanceModalLabel">
          <i class="ti ti-plus me-1"></i> Add New Allowance
        </h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="addAllowanceForm" action="<?= site_url('users/save_allowance') ?>" method="post" class="app-form">
        <div class="modal-body">
          <div class="row g-3">
            <input type="hidden" name="id" id="allowance_id" value="">

            <!-- Title -->
            <div class="col-md-6">
              <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" id="title" required>
            </div>

            <!-- Amount -->
            <div class="col-md-6">
              <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control" name="amount" id="amount" required>
            </div>

            <!-- Type -->
            <div class="col-md-6">
              <label for="is_percentage" class="form-label">Type</label>
              <select class="form-select" name="is_percentage" id="is_percentage">
                <option value="0">Fixed Amount</option>
                <option value="1">Percentage</option>
              </select>
            </div>

            <!-- Taxable -->
            <div class="col-md-6">
              <label for="is_taxable" class="form-label">Taxable</label>
              <select class="form-select" name="is_taxable" id="is_taxable">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>

            <!-- Percentage fields -->
            <div class="col-md-6 percentage-fields d-none">
              <label for="percentage_of" class="form-label">Percentage Of</label>
              <select class="form-select" name="percentage_of" id="percentage_of">
                <option value="">-- Select --</option>
                <option value="Base Salary">Base Salary</option>
                <option value="Gross Salary">Gross Salary</option>
              </select>
            </div>

            <div class="col-md-6 percentage-fields d-none">
              <label for="max_limit" class="form-label">Max Limit (Optional)</label>
              <input type="number" step="0.01" class="form-control" name="max_limit" id="max_limit" placeholder="Maximum amount limit">
            </div>

            <!-- Applicable To -->
            <div class="col-md-12">
              <label for="applicable_to" class="form-label">Applicable To</label>
              <select class="form-select" name="applicable_to" id="applicable_to">
                <option value="All">All Employees</option>
                <option value="Males">All Males</option>
                <option value="Females">All Females</option>
                <option value="Departments">Specific Departments</option>
                <option value="Positions">Specific Positions</option>
                <option value="Custom">Specific Employees</option>
              </select>
            </div>

            <!-- Custom Employees -->
            <div class="col-md-12 d-none applicable-wrapper" id="custom_eligibility_wrapper">
              <label for="custom_eligibility" class="form-label">Select Employees</label>
              <select class="form-select select2" id="custom_eligibility" name="custom_eligibility[]" multiple>
                <?php foreach ($employees as $emp): ?>
                  <option value="<?= $emp['user_id'] ?>">
                    <?= html_escape($emp['firstname'] . ' ' . $emp['lastname'] . ' (' . $emp['emp_id'] . ')') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Departments -->
            <div class="col-md-12 d-none applicable-wrapper" id="departments_wrapper">
              <label for="departments" class="form-label">Select Departments</label>
              <select class="form-select select2" id="departments" name="applicable_departments_json[]" multiple>
                <?php foreach ($departments as $dep): ?>
                  <option value="<?= $dep['id'] ?>"><?= html_escape($dep['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Positions -->
            <div class="col-md-12 d-none applicable-wrapper" id="positions_wrapper">
              <label for="positions" class="form-label">Select Positions</label>
              <select class="form-select select2" id="positions" name="applicable_positions_json[]" multiple>
                <?php foreach ($positions as $pos): ?>
                  <option value="<?= $pos['id'] ?>"><?= html_escape($pos['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Description -->
            <div class="col-md-12">
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" name="description" id="description" rows="2" placeholder="Optional description of the allowance"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top-0">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm px-4">
            <i class="ti ti-check me-1"></i> Save Allowance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(function () {
  /* ───────── Toggle Helpers ───────── */
  function togglePercentageFields() {
    const show = $('#is_percentage').val() === '1';
    if (show) {
      $('.percentage-fields').removeClass('d-none');
      $('#amount').attr({
        'placeholder': 'Enter percentage (e.g., 10)',
        'min': 0,
        'max': 100
      });
    } else {
      $('.percentage-fields').addClass('d-none');
      $('#percentage_of').val('');
      $('#max_limit').val('');
      $('#amount').removeAttr('min max').attr('placeholder', 'Enter amount');
    }
  }

  function toggleApplicableWrappers() {
    const val = $('#applicable_to').val();
    $('.applicable-wrapper').addClass('d-none');
    if (val === 'Custom') {
      $('#custom_eligibility_wrapper').removeClass('d-none');
    } else if (val === 'Departments') {
      $('#departments_wrapper').removeClass('d-none');
    } else if (val === 'Positions') {
      $('#positions_wrapper').removeClass('d-none');
    }
  }

  /* ───────── Bind change events ───────── */
  $('#is_percentage').on('change', togglePercentageFields);
  $('#applicable_to').on('change', toggleApplicableWrappers);

  /* ───────── Modal open ───────── */
  $('#addAllowanceModal').on('shown.bs.modal', function () {
    togglePercentageFields();
    toggleApplicableWrappers();

    // Safely reinitialize Select2
    if ($.fn.select2) {
      $('.select2').select2('destroy').select2({
        width: '100%',
        placeholder: 'Select options',
        allowClear: true
      });
    }
  });

  /* ───────── Form validation ───────── */
  $('#addAllowanceForm').on('submit', function(e) {
    if ($('#is_percentage').val() === '1' && !$('#percentage_of').val()) {
      e.preventDefault();
      alert('Please select "Percentage Of" field when using percentage type.');
      return false;
    }
  });

  // Run once on page load
  togglePercentageFields();
  toggleApplicableWrappers();

  /* ───────── Edit allowance handler ───────── */
  $(document).on('click', '.edit-allowance', function() {
    const id = $(this).data('id');
    const url = "<?= site_url('users/edit_allowance'); ?>/" + encodeURIComponent(id);

    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'json',
      cache: false
    })
    .done(function(res) {
      if (res.status === 'success') {
        const d = res.data;

        // Basic fields
        $('#allowance_id').val(d.id);
        $('#title').val(d.title);
        $('#amount').val(d.amount);
        $('#is_percentage').val(d.is_percentage).trigger('change');
        $('#is_taxable').val(d.is_taxable);
        $('#percentage_of').val(d.percentage_of);
        $('#max_limit').val(d.max_limit);
        $('#applicable_to').val(d.applicable_to).trigger('change');
        $('#description').val(d.description);

        // Multi-selects (ensure arrays)
        if (Array.isArray(d.applicable_user_ids_json)) {
          $('#custom_eligibility').val(d.applicable_user_ids_json).trigger('change');
        }
        if (Array.isArray(d.applicable_departments_json)) {
          $('#departments').val(d.applicable_departments_json).trigger('change');
        }
        if (Array.isArray(d.applicable_positions_json)) {
          $('#positions').val(d.applicable_positions_json).trigger('change');
        }

        // Adjust wrappers based on loaded values
        togglePercentageFields();
        toggleApplicableWrappers();

        // Switch modal title
        $('#addAllowanceModalLabel').html('<i class="ti ti-edit me-1"></i> Edit Allowance');
        $('#addAllowanceModal').modal('show');
      } else {
        alert(res.message || 'Allowance not found.');
      }
    })
    .fail(function(xhr, status, err) {
      console.error('edit_allowance response:', xhr.responseText);
      alert('Error loading allowance data. Check console for details.');
    });
  });
});
</script>

