<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="card-header bg-light-primary mb-2">
    <div class="d-flex align-items-center justify-content-between">
        <h6 class="card-title text-primary mb-0">
            <i class="ti ti-calendar-event me-2"></i>
            Public Holidays
        </h6>

        <button class="btn btn-primary btn-header"
                data-bs-toggle="modal"
                data-bs-target="#addHolidayModal">
            <i class="ti ti-plus"></i> New Holiday
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bottom-border table-sm small table-hover align-middle">
        <thead class="bg-light-primary">
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Date</th>
                <th>Locations</th>
                <th>Departments</th>
                <th>Positions</th>
                <th>Employees</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($holidays)): ?>
        
            <?php foreach ($holidays as $h): ?>
                <tr>
                    <td class="fw-semibold">
                    <?= html_escape($h['name'] ?? '') ?>
                    </td>

                    <td> 
                    <span class="badge bg-light-primary"> <?= html_escape($h['category'] ?? '') ?> </span> 
                    </td>

                    <td> 
                    <?= date('d M Y', strtotime($h['from_date'])) ?> → <?= date('d M Y', strtotime($h['to_date'])) ?> 
                    </td>

                    <td>
                    <?= render_holidays_scope_list( $h['locations'] ?? null, $offices ?? [], 'office_name' ) ?>
                    </td>
                    
                    <td>
                    <?= render_holidays_scope_list( $h['departments'] ?? null, $departments ?? [], 'name' ) ?>
                    </td>
                    
                    <td>
                    <?= render_holidays_scope_list( $h['positions'] ?? null, $positions ?? [], 'title' ) ?>
                    </td>
                    
                    <td>
                    <?= render_holidays_scope_list( $h['employees'] ?? null, $employees ?? [], 'full_name' ) ?>
                    </td>
                    
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-edit-holiday"
                                data-bs-toggle="modal"
                                data-bs-target="#editHolidayModal"
                            
                                data-id="<?= (int)$h['id'] ?>"
                                data-name="<?= html_escape($h['name']) ?>"
                                data-category="<?= html_escape($h['category']) ?>"
                                data-from="<?= $h['from_date'] ?>"
                                data-to="<?= $h['to_date'] ?>"
                                data-locations='<?= $h['locations'] ?>'
                                data-departments='<?= $h['departments'] ?>'
                                data-positions='<?= $h['positions'] ?>'
                                data-employees='<?= $h['employees'] ?>'
                            >
                                <i class="ti ti-edit"></i>
                            </button>
                            
                            <?= delete_link([
                                'url'   => 'admin/setup/attendance/delete_holiday/' . (int) ($h['id'] ?? 0),
                                'icon'  => '<i class="ti ti-trash"></i>',
                                'class' => 'btn btn-outline-danger',
                            ]) ?>
                        </div>
                    </td>
                    
                </tr>
            <?php endforeach; ?>

        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    No public holidays added yet.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>


<?php $CI =& get_instance();
echo $CI->load->view( 'admin/setup/attendance/modals/holiday_add_modal', true ); 
echo $CI->load->view( 'admin/setup/attendance/modals/holiday_edit_modal', true ); 
?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('editHolidayModal');

    modal.addEventListener('show.bs.modal', function (event) {

        const btn = event.relatedTarget;

        // Basic fields
        document.getElementById('edit_holiday_id').value = btn.dataset.id || '';
        document.getElementById('edit_name').value       = btn.dataset.name || '';
        document.getElementById('edit_category').value   = btn.dataset.category || '';
        document.getElementById('edit_from_date').value  = btn.dataset.from || '';
        document.getElementById('edit_to_date').value    = btn.dataset.to || '';

        // Helper to safely assign select values
        function setSelectValue(id, value) {
            const el = document.getElementById(id);
            if (!el) return;
            el.value = value ?? '';
        }

        // JSON → single select (your design)
        setSelectValue('edit_locations',   parseJsonFirst(btn.dataset.locations));
        setSelectValue('edit_departments', parseJsonFirst(btn.dataset.departments));
        setSelectValue('edit_positions',   parseJsonFirst(btn.dataset.positions));
        setSelectValue('edit_employees',   parseJsonFirst(btn.dataset.employees));
    });

    function parseJsonFirst(json) {
        if (!json) return '';
        try {
            const arr = JSON.parse(json);
            return Array.isArray(arr) && arr.length ? arr[0] : '';
        } catch {
            return '';
        }
    }
});
</script>
