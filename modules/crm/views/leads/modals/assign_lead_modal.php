<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $leadId = (int)($lead['id'] ?? 0); ?>
<div class="modal fade app-modal" id="assignLeadModal" tabindex="-1"
     aria-labelledby="assignLeadModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= site_url('crm/leads/assign/' . $leadId) ?>" method="post" class="app-form">
                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-teal">
                            <i class="ti ti-user-plus"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title" id="assignLeadModalLabel">Assign Lead</div>
                            <div class="app-modal-subtitle">Assign this lead to a staff member</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="app-modal-body">
                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="col-12">
                            <div class="app-form-group">
                                <label class="app-form-label app-form-label-required" for="assign_assigned_to">Assigned To</label>
                                <select name="assigned_to" id="assign_assigned_to"
                                        class="app-form-control js-assign-select" required>
                                    <option value="">Select staff member</option>
                                    <?php foreach (($users ?? []) as $user): ?>
                                        <?php
                                            // Derive the avatar URL the same way user_profile_image() does,
                                            // so we can embed it as a data attribute for Select2 templating.
                                            $avatarSrc = user_avatar_url(
                                                !empty($user['profile_image']) ? $user['profile_image'] : (int)$user['id']
                                            );
                                        ?>
                                        <option value="<?= (int)$user['id'] ?>"
                                                data-avatar="<?= html_escape($avatarSrc) ?>"
                                            <?= ((int)($lead['assigned_to'] ?? 0) === (int)$user['id']) ? 'selected' : '' ?>>
                                            <?= html_escape($user['fullname'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="app-form-hint">The staff member responsible for following up on this lead.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="app-modal-footer">
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-user-check"></i>Save &amp; Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('assignLeadModal');
    if (!modalEl) return;

    /**
     * Builds a jQuery element for a Select2 option/result.
     * Shows the avatar thumbnail + the user's display name side by side.
     */
    function formatUserOption(option) {
        if (!option.id) {
            // Placeholder — render plain text
            return option.text;
        }

        var avatar = $(option.element).data('avatar');

        if (!avatar) {
            // No avatar available — fall back to a simple initials circle
            var initials = (option.text || '?').trim().charAt(0).toUpperCase();
            return $(
                '<span style="display:inline-flex;align-items:center;gap:8px;">' +
                    '<span style="' +
                        'display:inline-flex;align-items:center;justify-content:center;' +
                        'width:24px;height:24px;border-radius:50%;' +
                        'background:var(--app-color-teal,#20c997);' +
                        'color:#fff;font-size:11px;font-weight:600;flex-shrink:0;' +
                    '">' + initials + '</span>' +
                    '<span>' + $('<span>').text(option.text).html() + '</span>' +
                '</span>'
            );
        }

        return $(
            '<span style="display:inline-flex;align-items:center;gap:8px;">' +
                '<img src="' + avatar + '" ' +
                     'alt="" ' +
                     'style="width:24px;height:24px;border-radius:50%;object-fit:cover;flex-shrink:0;" />' +
                '<span>' + $('<span>').text(option.text).html() + '</span>' +
            '</span>'
        );
    }

    function initSelect2($modal) {
        $modal.find('.js-assign-select').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });

        $modal.find('.js-assign-select').select2({
            width          : '100%',
            dropdownParent : $modal,
            templateResult : formatUserOption,   // avatar in the open dropdown list
            templateSelection: formatUserOption, // avatar in the closed selection box
        });
    }

    modalEl.addEventListener('shown.bs.modal', function () {
        initSelect2($(this));
    });

    modalEl.addEventListener('hide.bs.modal', function () {
        $(this).find('.js-assign-select').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
    });
})();
</script>