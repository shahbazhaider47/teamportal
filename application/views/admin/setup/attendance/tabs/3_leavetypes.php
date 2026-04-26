<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Header -->
<div class="card-header bg-light-primary mb-2">
  <div class="d-flex align-items-center justify-content-between">
    <div class="justify-content-between gap-2">
        <h6 class="card-title text-primary mb-0">
            <i class="ti ti-clipboard-list me-2" style="font-size:18px;"></i>
            Leave Types
        </h6>
        <span class="text-muted small"><strong class="text-primary">Please Note: </strong>
        Leave types currently assigned to employees must not be edited. <br>Any changes may override attendance policies and result in incorrect leave calculations.</span>
    </div>
    
        <button class="btn btn-primary btn-header"
                data-bs-toggle="modal"
                data-bs-target="#addLeaveTypeModal">
            <i class="ti ti-plus"></i> New Leave Type
        </button>
          
  </div>
</div>

<div class="table-responsive">
    <table class="table table-bottom-border table-sm small table-hover align-middle">
        <thead class="bg-light-primary">
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Type</th>
                <th>Unit</th>
                <th>Limit</th>
                <th>Eligibility</th>
                <th>Applies To</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>

        <tbody>
        <?php if (!empty($leave_types)): ?>
            <?php foreach ($leave_types as $lt): ?>

                <?php
                $employmentTypes = json_decode($lt['employment_types'] ?? '[]', true);
                $genderTypes     = json_decode($lt['applies_to_genders'] ?? '[]', true);
                ?>

                <tr>
                    <td class="fw-semibold">
                        <span class="badge me-1"
                              style="background:<?= html_escape($lt['color'] ?? '#6c757d') ?>;">
                        </span>
                        <?= html_escape($lt['name']) ?>
                    </td>

                    <td>
                        <span class="badge bg-light-secondary">
                            <?= html_escape($lt['code']) ?>
                        </span>
                    </td>

                    <td>
                        <span class="badge bg-light-primary">
                            <?= html_escape($lt['type']) ?>
                        </span>
                    </td>

                    <td><?= html_escape($lt['unit']) ?></td>

                    <td>
                        <?= $lt['limit'] !== null
                            ? html_escape($lt['limit']) . ' ' . html_escape($lt['unit'])
                            : '<span class="text-muted">—</span>' ?>
                    </td>

                    <td>
                        <?= !empty($employmentTypes)
                            ? '<span class="text-muted">' . implode(', ', $employmentTypes) . '</span>'
                            : '<span class="text-muted">All</span>' ?>
                    </td>

                    <td>
                        <?= !empty($genderTypes)
                            ? '<span class="text-muted">' . implode(', ', $genderTypes) . '</span>'
                            : '<span class="text-muted">All</span>' ?>
                    </td>

                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editLeaveTypeModal"

                                data-id="<?= (int) $lt['id'] ?>"
                                data-name="<?= html_escape($lt['name']) ?>"
                                data-code="<?= html_escape($lt['code']) ?>"
                                data-color="<?= html_escape($lt['color']) ?>"
                                data-type="<?= html_escape($lt['type']) ?>"
                                data-unit="<?= html_escape($lt['unit']) ?>"
                                data-limit="<?= html_escape($lt['limit']) ?>"
                                data-description="<?= html_escape($lt['description']) ?>"
                                data-attachment_required="<?= (int) $lt['attachment_required'] ?>"
                                data-based_on="<?= html_escape($lt['based_on']) ?>"
                                data-allowed_annually="<?= html_escape($lt['allowed_annually']) ?>"
                                data-allowed_monthly="<?= html_escape($lt['allowed_monthly']) ?>"

                                data-employment_types="<?= html_escape($lt['employment_types']) ?>"
                                data-applies_to_genders="<?= html_escape($lt['applies_to_genders']) ?>"
                                data-applies_to_locations="<?= html_escape($lt['applies_to_locations']) ?>"
                                data-applies_to_departments="<?= html_escape($lt['applies_to_departments']) ?>"
                                data-applies_to_positions="<?= html_escape($lt['applies_to_positions']) ?>"
                                data-applies_to_employees="<?= html_escape($lt['applies_to_employees']) ?>"
                                data-applies_to_roles="<?= html_escape($lt['applies_to_roles']) ?>"
                            >
                                <i class="ti ti-edit"></i>
                            </button>

                            <?= delete_link([
                                'url'   => 'admin/setup/attendance/delete_leave_type/' . (int) $lt['id'],
                                'icon'  => '<i class="ti ti-trash"></i>',
                                'class' => 'btn btn-outline-danger',
                            ]) ?>
                        </div>
                    </td>
                </tr>

            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    No leave types defined yet.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$CI =& get_instance();
echo $CI->load->view('admin/setup/attendance/modals/leavetype_add_modal', true);
echo $CI->load->view('admin/setup/attendance/modals/leavetype_edit_modal', true);
?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('editLeaveTypeModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;

        const setVal = (id, val) => {
            const el = modal.querySelector('#' + id);
            if (el) el.value = val ?? '';
        };

        const setMulti = (id, json) => {
            const el = modal.querySelector('#' + id);
            if (!select) return;

            let values = [];
            try {
                values = json ? JSON.parse(json) : [];
            } catch (e) {
                console.error('Invalid JSON for', id, json);
            }

            Array.from(select.options).forEach(o => o.selected = false);
            values.forEach(v => {
                const opt = Array.from(select.options).find(o => o.value == v);
                if (opt) opt.selected = true;
            });

            const wrapper = select.closest('.multi-select-wrapper');
            if (wrapper && typeof window.setMultiSelectValues === 'function') {
                window.setMultiSelectValues(wrapper, values);
            }
        };

        /* BASIC */
        setVal('edit_leave_type_id', btn.dataset.id);
        setVal('edit_name', btn.dataset.name);
        setVal('edit_code', btn.dataset.code);
        setVal('edit_color', btn.dataset.color);
        setVal('edit_type', btn.dataset.type);
        setVal('edit_unit', btn.dataset.unit);
        setVal('edit_limit', btn.dataset.limit);
        setVal('edit_based_on', btn.dataset.based_on);
        setVal('edit_allowed_annually', btn.dataset.allowed_annually);
        setVal('edit_allowed_monthly', btn.dataset.allowed_monthly);
        setVal('edit_description', btn.dataset.description);

        const attach = document.getElementById('edit_attachment_required');
        if (attach) attach.value = btn.dataset.attachment_required ?? '0';

        /* MULTI */
        setMulti('edit_applies_to_genders', btn.dataset.applies_to_genders);
        setMulti('edit_applies_to_locations', btn.dataset.applies_to_locations);
        setMulti('edit_applies_to_departments', btn.dataset.applies_to_departments);
        setMulti('edit_applies_to_positions', btn.dataset.applies_to_positions);
        setMulti('edit_applies_to_employees', btn.dataset.applies_to_employees);
        setMulti('edit_applies_to_roles', btn.dataset.applies_to_roles);
        setMulti('edit_employment_types', btn.dataset.employment_types);
    });

});
</script>
