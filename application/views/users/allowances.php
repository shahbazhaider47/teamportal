<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= $page_title ?></h1>
    </div>
  
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canExport  = staff_can('export', 'general');
        $canPrint   = staff_can('print', 'general');
      ?>

      <button class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#addAllowanceModal" id="addAllowanceBtn">
        <i class="ti ti-plus me-1"></i> Add Allowance
      </button>
      
      <div class="btn-divider"></div>

      <!-- Search -->
      <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
        <input type="text" class="form-control rounded app-form small dynamic-search-input" 
               placeholder="Search..." 
               aria-label="Search"
               data-table-target="<?= $table_id ?? 'allowancesTable' ?>">
        <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
      </div>
      
      <?php if ($canExport): ?>
        <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                title="Export to Excel"
                data-export-filename="<?= $page_title ?? 'export' ?>">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>
  
      <?php if ($canPrint): ?>
        <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                title="Print Table">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-hover small align-middle mb-0" id="allowancesTable">
        <thead class="bg-light-primary">
          <tr>
            <th>Title</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Applicable To</th>
            <th>Taxable</th>
            <th>Status</th>
            <th width="120">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allowances as $row): ?>
            <tr data-id="<?= $row['id'] ?>">
              <td><?= html_escape($row['title']) ?></td>
              <td><?= $row['is_percentage'] ? $row['amount'] . '%' : html_escape(get_base_currency_symbol()) . number_format($row['amount'], 2) ?></td>
              <td><?= $row['is_percentage'] ? 'Percentage of ' . $row['percentage_of'] : 'Fixed' ?></td>
              <td><?= html_escape($row['applicable_to']) ?></td>
              <td><?= $row['is_taxable'] ? 'Yes' : 'No' ?></td>
              <td>
                <span class="badge bg-<?= $row['is_active'] ? 'success' : 'secondary' ?>">
                  <?= $row['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td>
                <div class="btn-group" role="group">
                  <button type="button" class="btn btn-sm btn-light-primary icon-btn edit-allowance" data-id="<?= $row['id'] ?>" title="Edit">
                    <i class="ti ti-edit"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-light-warning icon-btn toggle-status" data-id="<?= $row['id'] ?>" data-status="<?= $row['is_active'] ?>" title="<?= $row['is_active'] ? 'Deactivate' : 'Activate' ?>">
                    <i class="ti ti-<?= $row['is_active'] ? 'player-pause' : 'player-play' ?>"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-light-danger icon-btn delete-allowance" data-id="<?= $row['id'] ?>" title="Delete">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($allowances)): ?>
            <tr><td colspan="7" class="text-center">No allowances defined.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $CI = &get_instance(); $CI->load->view('users/modals/add_allowance_modal'); ?>


<script>
// Wait for document to be fully ready
document.addEventListener('DOMContentLoaded', function() {
    const site_url = "<?= site_url(); ?>";
    
    // Check if jQuery is available
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        // Load jQuery dynamically if not available
        loadjQuery();
    } else {
        initializeAllowanceScripts();
    }
    
    function loadjQuery() {
        const script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        script.integrity = 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=';
        script.crossOrigin = 'anonymous';
        script.onload = function() {
            // Now load jQuery Migrate for compatibility
            const migrateScript = document.createElement('script');
            migrateScript.src = 'https://code.jquery.com/jquery-migrate-3.4.0.min.js';
            migrateScript.onload = initializeAllowanceScripts;
            document.head.appendChild(migrateScript);
        };
        document.head.appendChild(script);
    }
    
    function initializeAllowanceScripts() {
        // Use jQuery noConflict if needed
        const $ = jQuery.noConflict();
        
        console.log('jQuery loaded, version:', $.fn.jquery);
        
        // Initialize Select2 if available
        initializeSelect2($);
        
        // Bind all events
        bindEvents($);
        
        // Initialize field states
        initializeFieldStates($);
    }
    
    function initializeSelect2($) {
        if (typeof $.fn.select2 === 'function') {
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select options',
                allowClear: true,
                dropdownParent: $('#addAllowanceModal')
            });
            console.log('Select2 initialized');
        } else {
            console.warn('Select2 not available, using native select');
            // Fallback: make multiple selects work without Select2
            $('select[multiple]').addClass('form-control');
        }
    }
    
    function bindEvents($) {
        // Add Allowance Button
        $(document).on('click', '#addAllowanceBtn', function () {
            $('#addAllowanceModalLabel').html('<i class="ti ti-plus me-1"></i> Add New Allowance');
            $('#addAllowanceForm').trigger('reset');
            $('#addAllowanceForm input[name=id]').val('');
            
            // Reset Select2 fields
            resetSelect2Fields($);
            
            // Reset modal state
            togglePercentageFields($);
            toggleApplicableWrappers($);
        });

        // Edit Allowance
        $(document).on('click', '.edit-allowance', function () {
            const id = $(this).data('id');
            handleEditAllowance($, id, $(this));
        });

        // Toggle Status
        $(document).on('click', '.toggle-status', function () {
            const id = $(this).data('id');
            const currentStatus = $(this).data('status');
            handleToggleStatus($, id, currentStatus, $(this));
        });

        // Delete Allowance
        $(document).on('click', '.delete-allowance', function () {
            const id = $(this).data('id');
            handleDeleteAllowance($, id, $(this));
        });

        // Form Submission
        $('#addAllowanceForm').on('submit', function (e) {
            e.preventDefault();
            handleFormSubmit($, $(this));
        });

        // Field change events
        $('#is_percentage').on('change', function() {
            togglePercentageFields($);
        });
        
        $('#applicable_to').on('change', function() {
            toggleApplicableWrappers($);
        });
    }
    
    function initializeFieldStates($) {
        togglePercentageFields($);
        toggleApplicableWrappers($);
    }
    
    function resetSelect2Fields($) {
        $('#custom_eligibility').val(null).trigger('change');
        $('#departments').val(null).trigger('change');
        $('#positions').val(null).trigger('change');
    }
    
    function togglePercentageFields($) {
        const show = $('#is_percentage').val() === '1';
        if (show) {
            $('.percentage-fields').removeClass('d-none');
            $('#amount').attr('placeholder', 'Enter percentage (e.g., 10)');
        } else {
            $('.percentage-fields').addClass('d-none');
            $('#percentage_of').val('');
            $('#max_limit').val('');
            $('#amount').attr('placeholder', 'Enter amount');
        }
    }

    function toggleApplicableWrappers($) {
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
    
    function handleEditAllowance($, id, button) {
        // Show loading state
        const originalHtml = button.html();
        button.html('<i class="ti ti-loader"></i>').prop('disabled', true);

        $.ajax({
            url: site_url + 'users/edit_allowance/' + id,
            type: 'GET',
            dataType: 'json',
            success: function (resp) {
                button.html(originalHtml).prop('disabled', false);
                
                if (resp.status === 'success') {
                    const d = resp.data;
                    populateEditForm($, d);
                    $('#addAllowanceModal').modal('show');
                } else {
                    alert(resp.message || 'Unable to fetch allowance data');
                }
            },
            error: function (xhr, status, error) {
                button.html(originalHtml).prop('disabled', false);
                alert('Error loading allowance data: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    }
    
    function populateEditForm($, data) {
        $('#addAllowanceModalLabel').html('<i class="ti ti-edit me-1"></i> Edit Allowance');
        $('#addAllowanceForm input[name=id]').val(data.id);
        $('#addAllowanceForm input[name=title]').val(data.title);
        $('#addAllowanceForm input[name=amount]').val(data.amount);
        $('#addAllowanceForm select[name=is_percentage]').val(data.is_percentage);
        $('#addAllowanceForm select[name=percentage_of]').val(data.percentage_of);
        $('#addAllowanceForm input[name=max_limit]').val(data.max_limit);
        $('#addAllowanceForm select[name=applicable_to]').val(data.applicable_to);
        $('#addAllowanceForm select[name=is_taxable]').val(data.is_taxable);
        $('#addAllowanceForm textarea[name=description]').val(data.description);

        // Handle applicable fields
        handleApplicableData($, data);

        // Trigger change events
        $('#is_percentage').trigger('change');
        $('#applicable_to').trigger('change');
    }
    
    function handleApplicableData($, data) {
        // Reset all first
        $('#custom_eligibility').val(null).trigger('change');
        $('#departments').val(null).trigger('change');
        $('#positions').val(null).trigger('change');
        
        if (data.applicable_to === 'Custom' && data.applicable_user_ids_json) {
            try {
                const userIds = JSON.parse(data.applicable_user_ids_json);
                $('#custom_eligibility').val(userIds).trigger('change');
            } catch (e) {
                console.error('Error parsing user IDs:', e);
            }
        } else if (data.applicable_to === 'Departments' && data.applicable_departments_json) {
            try {
                const deptIds = JSON.parse(data.applicable_departments_json);
                $('#departments').val(deptIds).trigger('change');
            } catch (e) {
                console.error('Error parsing department IDs:', e);
            }
        } else if (data.applicable_to === 'Positions' && data.applicable_positions_json) {
            try {
                const positionIds = JSON.parse(data.applicable_positions_json);
                $('#positions').val(positionIds).trigger('change');
            } catch (e) {
                console.error('Error parsing position IDs:', e);
            }
        }
    }
    
    function handleToggleStatus($, id, currentStatus, button) {
        const newStatus = currentStatus ? 0 : 1;
        const action = newStatus ? 'activate' : 'deactivate';
        
        if (confirm(`Are you sure you want to ${action} this allowance?`)) {
            const originalHtml = button.html();
            button.html('<i class="ti ti-loader"></i>').prop('disabled', true);

            $.ajax({
                url: site_url + 'users/toggle_allowance_status/' + id + '/' + newStatus,
                type: 'GET',
                success: function (response) {
                    window.location.reload();
                },
                error: function (xhr, status, error) {
                    button.html(originalHtml).prop('disabled', false);
                    alert('Error updating allowance status: ' + error);
                }
            });
        }
    }
    
    function handleDeleteAllowance($, id, button) {
        if (confirm('Are you sure you want to delete this allowance? This action cannot be undone.')) {
            const originalHtml = button.html();
            button.html('<i class="ti ti-loader"></i>').prop('disabled', true);

            $.ajax({
                url: site_url + 'users/delete_allowance/' + id,
                type: 'GET',
                success: function (response) {
                    window.location.reload();
                },
                error: function (xhr, status, error) {
                    button.html(originalHtml).prop('disabled', false);
                    alert('Error deleting allowance: ' + error);
                }
            });
        }
    }
    
    function handleFormSubmit($, form) {
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="ti ti-loader me-1"></i> Saving...').prop('disabled', true);

        // Prepare form data
        const formData = new FormData(form[0]);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#addAllowanceModal').modal('hide');
                    // Use CodeIgniter's flashdata alert system
                    window.location.reload();
                } else {
                    alert(response.message || 'Error saving allowance');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function (xhr, status, error) {
                alert('Error saving allowance: ' + error);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }
});
</script>