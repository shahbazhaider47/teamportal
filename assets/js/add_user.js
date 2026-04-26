/**
 * Add New User - Form Handling & Validation
 * External JavaScript file for add_user form
 */

(function($) {
    'use strict';

    // Configuration
    const CONFIG = {
        currencySymbol: window.currencySymbol || '$',
        probationMonths: parseInt(document.querySelector('#add-user-form')?.dataset.probationMonths || '0', 10),
        minHiringAge: parseInt(window.minHiringAge || '0', 10)
    };

    // Role scope mapping
    const ROLE_SCOPE_MAP = {
        employee:   { show: ['teamlead'] },
        teamlead:   { show: ['manager'] },
        manager:    { show: ['reporting'] },
        admin:      { show: ['reporting'] },
        officeboy:  { show: ['reporting'] },
        sweeper:    { show: ['reporting'] },
        other:      { show: ['reporting'] },
        director:   { show: [] },
        superadmin: { show: [] }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        initForm();
        initEventHandlers();
        initDateLimits();
        initSelect2();
        initProfileImage();
    });

    /**
     * Initialize form components
     */
    function initForm() {
        // Set default joining date if empty
        const joiningInput = $('#emp_joining');
        if (joiningInput && !joiningInput.val()) {
            joiningInput.val(new Date().toISOString().split('T')[0]);
        }

        // Initialize role scope
        applyRoleScope($('#user_role').val());

        // Initialize confirmation date toggle
        toggleConfirmationDate();

        // Initialize salary range from position
        updateSalaryRange();

        // Set DOB max date based on min hiring age
        if (CONFIG.minHiringAge > 0) {
            setDobMaxDate();
        }
    }

    /**
     * Initialize event handlers
     */
    function initEventHandlers() {
        // Position change - update salary range and emp_title
        $('#position_id').on('change', handlePositionChange);

        // Profile image handling
        $('#profile_image').on('change', handleProfileImageChange);
        $('#removeProfileBtn').on('click', removeProfilePhoto);

        // Auto-generate full name
        $('#firstname, #initials, #lastname').on('input', generateFullName);

        // Role change - update reporting scope
        $('#user_role').on('change', function() {
            applyRoleScope($(this).val());
        });

        // Joining date change - calculate probation end
        $('#emp_joining').on('change input', calculateProbationEnd);

        // Confirmed employee checkbox
        $('#is_confirmed_employee').on('change', toggleConfirmationDate);

        // Tab navigation
        $('.next-tab').on('click', handleNextTab);
        $('.prev-tab').on('click', handlePrevTab);

        // Remove validation errors on input
        $(document).on('input change', 'input, select, textarea', function() {
            $(this).removeClass('is-invalid');
        });

        // Numeric-only phone input
        $(document).on('input', '.phone-numeric-only', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        // Form submission
        $('#add-user-form').on('submit', handleFormSubmit);
    }

    /**
     * Initialize date limits
     */
    function initDateLimits() {
        // Set DOB max date if min hiring age is configured
        if (CONFIG.minHiringAge > 0) {
            const dobInput = document.getElementById('emp_dob');
            if (dobInput) {
                const today = new Date();
                const maxDate = new Date(
                    today.getFullYear() - CONFIG.minHiringAge,
                    today.getMonth(),
                    today.getDate()
                );
                dobInput.max = maxDate.toISOString().split('T')[0];
            }
        }
    }

    /**
     * Initialize Select2 for allowances
     */
    function initSelect2() {
        if ($.fn.select2 && $('.select-allowances').length) {
            $('.select-allowances').select2({
                width: '100%',
                placeholder: 'Select Allowances',
                allowClear: true,
                dropdownParent: $(document.body)
            });
        }
    }

    /**
     * Initialize profile image handling
     */
    function initProfileImage() {
        $('#profileImagePreview').attr('src', function() {
            return $(this).attr('src') || base_url('assets/images/default.png');
        });
    }

    /**
     * Handle position change
     */
    function handlePositionChange() {
        const $option = $(this).find(':selected');
        const positionId = $option.val() ? parseInt($option.val(), 10) : null;
        const minSalary = parseFloat($option.data('min-salary')) || 0;
        const maxSalary = parseFloat($option.data('max-salary')) || 0;

        // Update salary range display
        if (positionId && (minSalary > 0 || maxSalary > 0)) {
            $('.salary-range-container').removeClass('d-none');
            $('#min_salary_display').val(formatCurrency(minSalary));
            $('#max_salary_display').val(formatCurrency(maxSalary));
        } else {
            $('.salary-range-container').addClass('d-none');
            $('#min_salary_display, #max_salary_display').val('');
        }

        // Update emp_title hidden field
        $('#emp_title').val(positionId || '');
    }

    /**
     * Handle profile image change
     */
    function handleProfileImageChange(e) {
        const file = e.target.files && e.target.files[0];
        if (!file) {
            $('#selectedFileName').hide();
            return;
        }

        // Validate file type and size
        const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        const maxSize = 2 * 1024 * 1024; // 2MB

        if (!validTypes.includes(file.type) || file.size > maxSize) {
            showToast('error', 'Only JPG/PNG/WEBP/GIF images up to 2MB are allowed.');
            $(this).val('');
            return;
        }

        // Display preview
        const previewUrl = URL.createObjectURL(file);
        $('#profileImagePreview').attr('src', previewUrl);
        $('#selectedFileName').text(file.name).show();
        $('#removeProfileBtn').show();
        $('#remove_profile_photo').val('0');
    }

    /**
     * Remove profile photo
     */
    function removeProfilePhoto() {
        const defaultImage = base_url('assets/images/default.png');
        $('#profileImagePreview').attr('src', defaultImage);
        $('#profile_image').val('');
        $('#remove_profile_photo').val('1');
        $('#removeProfileBtn, #selectedFileName').hide();
    }

    /**
     * Generate full name from parts
     */
    function generateFullName() {
        const first = $('#firstname').val() || '';
        const middle = $('#initials').val() || '';
        const last = $('#lastname').val() || '';
        
        const parts = [first, middle, last]
            .map(part => part.trim())
            .filter(part => part.length > 0);
        
        $('#fullname').val(parts.join(' '));
    }

    /**
     * Apply role-based reporting scope
     */
    function applyRoleScope(role) {
        role = (role || '').toLowerCase();
        
        // Hide all scope fields
        $('.field-teamlead, .field-manager, .field-reporting')
            .addClass('d-none')
            .find('select').val('');
        
        // Show fields based on role
        if (ROLE_SCOPE_MAP[role]) {
            const showFields = ROLE_SCOPE_MAP[role].show || [];
            
            if (showFields.includes('teamlead')) {
                $('.field-teamlead').removeClass('d-none');
            }
            if (showFields.includes('manager')) {
                $('.field-manager').removeClass('d-none');
            }
            if (showFields.includes('reporting')) {
                $('.field-reporting').removeClass('d-none');
            }
        }
    }

    /**
     * Calculate probation end date
     */
    function calculateProbationEnd() {
        const joiningDate = $('#emp_joining').val();
        if (!joiningDate || CONFIG.probationMonths <= 0) {
            $('#probation_end_date').val('');
            return;
        }

        const endDate = addMonths(joiningDate, CONFIG.probationMonths);
        if (endDate) {
            $('#probation_end_date').val(formatDate(endDate));
        }
    }

    /**
     * Toggle confirmation date field
     */
    function toggleConfirmationDate() {
        const isConfirmed = $('#is_confirmed_employee').is(':checked');
        const $wrapper = $('#confirmationDateWrapper');
        const $confirmationInput = $('#confirmation_date');
        const $probationInput = $('#probation_end_date');

        if (isConfirmed) {
            $wrapper.removeClass('d-none');
            $confirmationInput.prop('required', true);
            $probationInput.prop('required', false);
        } else {
            $wrapper.addClass('d-none');
            $confirmationInput.prop('required', false).val('');
            $probationInput.prop('required', true);
        }
    }

    /**
     * Handle next tab button click
     */
    function handleNextTab() {
        const $button = $(this);
        const $currentPane = $button.closest('.tab-pane');
        
        if (!validatePane($currentPane)) {
            showToast('error', 'Please fill all required fields in this tab.');
            return;
        }
        
        const targetTabId = $button.data('target');
        showTab(targetTabId);
        scrollToTop();
    }

    /**
     * Handle previous tab button click
     */
    function handlePrevTab() {
        const targetTabId = $(this).data('target');
        showTab(targetTabId);
        scrollToTop();
    }

    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        if (!validateAll()) {
            showToast('error', 'Please fill all required fields.');
            return;
        }
        
        // Show loading state
        const $submitBtn = $('#add-user-save');
        const $spinner = $submitBtn.find('.spinner-border');
        const $btnText = $submitBtn.find('.btn-text');
        
        $spinner.removeClass('d-none');
        $btnText.text('Saving...');
        $submitBtn.prop('disabled', true);
        
        // Submit form via AJAX
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message || 'User added successfully!');
                    setTimeout(() => {
                        window.location.href = base_url('users');
                    }, 1500);
                } else {
                    showToast('error', response.message || 'Failed to add user.');
                    $spinner.addClass('d-none');
                    $btnText.text('Save User');
                    $submitBtn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                let message = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast('error', message);
                $spinner.addClass('d-none');
                $btnText.text('Save User');
                $submitBtn.prop('disabled', false);
            }
        });
    }

    /**
     * Validate a specific tab pane - FIXED VERSION
     * Only checks visible required fields
     */
    function validatePane($pane) {
        let isValid = true;
        
        // Only check visible required fields
        $pane.find('[required]:visible').each(function() {
            const $field = $(this);
            let value = ($field.val() || '').toString().trim();
            
            // Special handling for select elements
            if ($field.is('select')) {
                value = $field.find('option:selected').val() || '';
            }
            
            if (!value) {
                $field.addClass('is-invalid');
                if (isValid) {
                    goToFieldTab($field);
                    try {
                        $field[0].focus({ preventScroll: true });
                    } catch (e) {
                        $field.focus();
                    }
                }
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Validate all form fields - FIXED VERSION
     * Only checks visible required fields
     */
    function validateAll() {
        let isValid = true;
        
        $('.tab-pane').each(function() {
            $(this).find('[required]:visible').each(function() {
                const $field = $(this);
                let value = ($field.val() || '').toString().trim();
                
                // Special handling for select elements
                if ($field.is('select')) {
                    value = $field.find('option:selected').val() || '';
                }
                
                if (!value) {
                    $field.addClass('is-invalid');
                    if (isValid) {
                        goToFieldTab($field);
                    }
                    isValid = false;
                }
            });
        });
        
        return isValid;
    }

    /**
     * Go to the tab containing a specific field
     */
    function goToFieldTab($field) {
        const $pane = $field.closest('.tab-pane');
        if (!$pane.length) return;
        
        const paneId = $pane.attr('id');
        const $tabButton = $(`button[data-bs-target="#${paneId}"]`);
        
        if ($tabButton.length) {
            const tab = new bootstrap.Tab($tabButton[0]);
            tab.show();
        }
    }

    /**
     * Show a specific tab
     */
    function showTab(buttonSelector) {
        const $button = $(buttonSelector);
        if ($button.length) {
            const tab = new bootstrap.Tab($button[0]);
            tab.show();
        }
    }

    /**
     * Scroll to top of page
     */
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Set DOB max date based on minimum hiring age
     */
    function setDobMaxDate() {
        const dobInput = document.getElementById('emp_dob');
        if (!dobInput) return;
        
        const today = new Date();
        const maxDate = new Date(
            today.getFullYear() - CONFIG.minHiringAge,
            today.getMonth(),
            today.getDate()
        );
        
        dobInput.max = maxDate.toISOString().split('T')[0];
    }

    /**
     * Update salary range display
     */
    function updateSalaryRange() {
        const $position = $('#position_id');
        if ($position.length && $position.val()) {
            handlePositionChange.call($position[0]);
        }
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        if (amount === null || amount === undefined || amount === '' || isNaN(amount)) {
            return '';
        }
        
        const formatted = parseFloat(amount).toFixed(2)
            .replace(/\d(?=(\d{3})+\.)/g, '$&,');
        
        return CONFIG.currencySymbol + ' ' + formatted;
    }

    /**
     * Add months to a date
     */
    function addMonths(dateString, months) {
        const date = new Date(dateString);
        if (isNaN(date)) return null;
        
        const day = date.getDate();
        date.setMonth(date.getMonth() + months);
        
        // Handle month rollover
        if (date.getDate() < day) {
            date.setDate(0);
        }
        
        return date;
    }

    /**
     * Format date to YYYY-MM-DD
     */
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        if (window.toastr) {
            toastr[type](message);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }

    /**
     * Get base URL
     */
    function base_url(path = '') {
        return window.base_url ? window.base_url + path : '/' + path;
    }

    // Make functions available globally if needed
    window.addUserForm = {
        removeProfilePhoto: removeProfilePhoto,
        validateForm: validateAll,
        calculateProbationEnd: calculateProbationEnd
    };

})(jQuery);


function validatePane($pane) {
    let isValid = true;
    
    console.log('Validating pane:', $pane.attr('id'));
    
    $pane.find('[required]:visible').each(function() {
        const $field = $(this);
        const fieldName = $field.attr('name') || $field.attr('id');
        let value = ($field.val() || '').toString().trim();
        
        if ($field.is('select')) {
            value = $field.find('option:selected').val() || '';
        }
        
        console.log(`Field: ${fieldName}, Value: "${value}", Visible: ${$field.is(':visible')}`);
        
        if (!value) {
            console.log(`❌ Field ${fieldName} is empty!`);
            $field.addClass('is-invalid');
            if (isValid) {
                goToFieldTab($field);
                try {
                    $field[0].focus({ preventScroll: true });
                } catch (e) {
                    $field.focus();
                }
            }
            isValid = false;
        }
    });
    
    console.log('Validation result:', isValid ? 'PASS' : 'FAIL');
    return isValid;
}