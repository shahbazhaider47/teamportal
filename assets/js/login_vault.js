/**
 * Login Vault JavaScript
 * Consolidated file for all login vault functionality
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('jQuery is required for Login Vault page');
        return;
    }

    $(function () {

        /* --------------------------------------------------------------
         * PASSWORD TOGGLE FUNCTIONALITY
         * ------------------------------------------------------------ */
        $(document).on('click', '.btn-toggle-password', function () {
            const target = $(this).data('target');
            const $input = $(target);
            const $icon = $(this).find('i');

            if ($input.length && $icon.length) {
                const isPassword = $input.attr('type') === 'password';
                $input.attr('type', isPassword ? 'text' : 'password');
                $icon.toggleClass('ti-eye-off', !isPassword);
                $icon.toggleClass('ti-eye', isPassword);
            }
        });

        /* --------------------------------------------------------------
         * VIEW VAULT MODAL
         * ------------------------------------------------------------ */
        $(document).on('click', '.btn-view-vault', function () {
            let data;
            try {
                data = JSON.parse(this.dataset.vault || '{}');
            } catch (e) {
                console.error('Invalid vault JSON', e);
                return;
            }

            $('#viewVaultModal [data-field]').each(function () {
                const key = $(this).data('field');
                let value = data[key] ?? '—';
                
                if (key === 'is_tfa') {
                    value = data.is_tfa == 1 ? 'Enabled' : 'Disabled';
                }
                
                if ($(this).is('textarea')) {
                    $(this).val(value);
                } else {
                    $(this).text(value);
                }
            });
        });

        /* --------------------------------------------------------------
         * EDIT VAULT MODAL
         * ------------------------------------------------------------ */
        $(document).on('click', '.btn-edit-vault', function () {
            let data;
            try {
                data = JSON.parse(this.dataset.vault || '{}');
            } catch (e) {
                console.error('Invalid vault JSON', e);
                return;
            }

            // Set form action
            $('#editVaultForm').attr(
                'action',
                window.siteUrl + 'login_vault/update/' + data.id
            );

            // Populate form fields
            Object.keys(data).forEach(function (key) {
                const $input = $('#edit-' + key);
                if ($input.length) {
                    $input.val(data[key]);
                }
            });

            // Handle checkbox/select separately
            $('#edit-is_tfa').val(data.is_tfa ?? 0);
        });

        /* --------------------------------------------------------------
         * DELETE VAULT CONFIRMATION
         * ------------------------------------------------------------ */
        $(document).on('click', '.btn-delete-vault', function () {
            const id = $(this).data('id');
            if (!id) return;

            if (confirm('Are you sure you want to delete this vault entry?')) {
                window.location.href = window.siteUrl + 'login_vault/delete/' + id;
            }
        });

        /* --------------------------------------------------------------
         * COPY LOGIN URL TO CLIPBOARD
         * ------------------------------------------------------------ */
        $(document).on('click', '.btn-copy-link', function () {
            const btn = this;
            const link = btn.dataset.link;
            
            if (!link || !navigator.clipboard) {
                alert('Copy not supported in this browser');
                return;
            }

            navigator.clipboard.writeText(link).then(function () {
                const $btn = $(btn);
                $btn.html('<i class="ti ti-check"></i>');
                $btn.removeClass('btn-light-primary').addClass('btn-success');

                setTimeout(function () {
                    $btn.html('<i class="ti ti-copy"></i>');
                    $btn.removeClass('btn-success').addClass('btn-light-primary');
                }, 1500);
            }).catch(function (err) {
                console.error('Copy failed:', err);
                alert('Failed to copy URL');
            });
        });

        /* --------------------------------------------------------------
         * SHARE VAULT MODAL FUNCTIONALITY
         * ------------------------------------------------------------ */
        
        // Set vault ID when modal opens
        $('#shareVaultModal').on('show.bs.modal', function (e) {
            const button = e.relatedTarget;
            const vaultId = button ? $(button).data('id') : null;

            if (vaultId) {
                $('#share-vault-id').val(vaultId);
            }
        });

        // Reset modal when shown
        $('#shareVaultModal').on('shown.bs.modal', function () {
            $('#share_type').val('All');
            resetShareTargets();
        });

        // Reset modal when hidden
        $('#shareVaultModal').on('hidden.bs.modal', function () {
            resetShareTargets();
            $('#share-vault-id').val('');
        });

        // Share type change handler
        $(document).on('change', '#share_type', function () {
            const type = this.value;
            resetShareTargets();

            if (!type || type === 'All') {
                return;
            }

            $('#share-target-wrapper').removeClass('d-none');
            $('#share_ids').html('<option value="">Loading...</option>');

            $.ajax({
                url: window.siteUrl + 'login_vault/get_share_scope_items',
                method: 'GET',
                data: { type: type },
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },

                success: function (items) {
                    if (!Array.isArray(items)) {
                        console.error('Unexpected share scope response', items);
                        $('#share_ids').html('<option value="">Failed to load data</option>');
                        return;
                    }

                    let options = '<option value="">Select ' + type + '</option>';
                    
                    items.forEach(function (item) {
                        if (item && item.id !== undefined && item.name) {
                            options += '<option value="' + item.id + '">' + 
                                     escapeHtml(item.name) + '</option>';
                        }
                    });

                    $('#share_ids').html(options);

                    // Initialize Select2 if available
                    if ($.fn.select2) {
                        $('#share_ids').select2({
                            width: '100%',
                            placeholder: 'Select ' + type,
                            allowClear: true,
                            dropdownParent: $('#shareVaultModal')
                        });
                    }
                },

                error: function (xhr) {
                    console.error('Failed loading share scope:', xhr.status, xhr.responseText);
                    $('#share_ids').html('<option value="">Failed to load data</option>');
                }
            });
        });

        /* --------------------------------------------------------------
         * ADD VAULT FORM VALIDATION
         * ------------------------------------------------------------ */
        $(document).on('submit', '.add-vault-form', function (e) {
            const title = $('#vault-title').val().trim();
            const password = $('#vault-password').val().trim();

            if (!title) {
                e.preventDefault();
                alert('Please enter a title for the vault entry.');
                $('#vault-title').focus();
                return false;
            }

            if (!password) {
                e.preventDefault();
                alert('Please enter a password.');
                $('#vault-password').focus();
                return false;
            }

            return true;
        });

        /* --------------------------------------------------------------
         * EDIT VAULT FORM VALIDATION
         * ------------------------------------------------------------ */
        $(document).on('submit', '.edit-vault-form', function (e) {
            const title = $('#edit-title').val().trim();

            if (!title) {
                e.preventDefault();
                alert('Please enter a title for the vault entry.');
                $('#edit-title').focus();
                return false;
            }

            return true;
        });

        /* --------------------------------------------------------------
         * SHARE VAULT FORM VALIDATION
         * ------------------------------------------------------------ */
        $(document).on('submit', '.share-vault-form', function (e) {
            const vaultId = $('#share-vault-id').val();
            const shareType = $('#share_type').val();

            if (!vaultId || vaultId <= 0) {
                e.preventDefault();
                alert('Invalid vault selection.');
                return false;
            }

            if (!shareType) {
                e.preventDefault();
                alert('Please select a share type.');
                return false;
            }

            return true;
        });

        /* --------------------------------------------------------------
         * HELPER FUNCTIONS
         * ------------------------------------------------------------ */
        function resetShareTargets() {
            const $targets = $('#share_ids');
            
            if ($targets.hasClass('select2-hidden-accessible')) {
                $targets.select2('destroy');
            }
            
            $targets.empty();
            $('#share-target-wrapper').addClass('d-none');
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        /* --------------------------------------------------------------
         * INITIALIZATION
         * ------------------------------------------------------------ */
        // Make sure siteUrl is available
        if (typeof window.siteUrl === 'undefined') {
            window.siteUrl = window.location.origin + '/';
        }

        // Clear add vault modal when hidden
        $('#addVaultModal').on('hidden.bs.modal', function () {
            const form = document.getElementById('vaultForm');
            if (form) {
                form.reset();
            }
            
            // Reset password field to password type
            const pwdInput = $('#vault-password');
            if (pwdInput.length) {
                pwdInput.attr('type', 'password');
                pwdInput.siblings('.btn-toggle-password').find('i')
                    .removeClass('ti-eye')
                    .addClass('ti-eye-off');
            }
        });

        // Clear edit vault modal when hidden
        $('#editVaultModal').on('hidden.bs.modal', function () {
            const form = document.getElementById('editVaultForm');
            if (form) {
                form.reset();
                form.action = '';
            }
            
            // Reset password field to password type
            const pwdInput = $('#edit-password-plain');
            if (pwdInput.length) {
                pwdInput.attr('type', 'password');
                pwdInput.siblings('.btn-toggle-password').find('i')
                    .removeClass('ti-eye')
                    .addClass('ti-eye-off');
            }
        });

    });

})(window.jQuery);