<?php $CI =& get_instance(); ?>

<!-- Header -->
<div class="card-header bg-light-primary mb-2">
    <div class="d-flex align-items-center justify-content-between">
        <h6 class="card-title text-primary mb-0">
            <i class="ti ti-clock me-2" style="font-size:18px;"></i>
            Work Shifts
        </h6>

        <button type="button"
                class="btn btn-primary btn-header"
                data-bs-toggle="modal"
                data-bs-target="#addWorkShiftModal">
            <i class="ti ti-plus"></i> New Shift
        </button>
    </div>
</div>

<!-- Shifts Table -->
<div class="table-responsive">
    <table class="table table-bottom-border table-sm small table-hover align-middle">
        <thead class="bg-light-primary">
            <tr>
                <th>Shift Name</th>
                <th>Type</th>
                <th>Timing</th>
                <th>Break</th>
                <th>Off Days</th>
                <th>Shift Status</th>
                <th>Staff Count</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php if (empty($shifts)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    No work shifts configured yet.
                </td>
            </tr>
        <?php else: ?>

            <?php foreach ($shifts as $s): ?>
                <tr>
                    <td>
                        <strong><?= html_escape($s['name']) ?></strong><br>
                    </td>

                    <td>
                        <span class="badge bg-light-primary">
                            <?= ucfirst(str_replace('_', ' ', $s['shift_type'])) ?>
                        </span>
                    </td>

                    <td>
                        <?= html_escape($s['shift_start_time']) ?> – <?= html_escape($s['shift_end_time']) ?>
                    </td>

                    <td>
                        <?php if (!empty($s['break_start_time']) && !empty($s['break_end_time'])): ?>
                            <?= html_escape($s['break_start_time']) ?> – <?= html_escape($s['break_end_time']) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= !empty($s['off_days'])
                            ? html_escape(strtoupper($s['off_days']))
                            : '<span class="text-muted">—</span>' ?>
                    </td>

                    <td>
                        <?php if (!empty($s['is_active'])): ?>
                            <span class="badge bg-success">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-danger">No</span>
                        <?php endif; ?>
                    </td>

                    <td>
                    <span>89 Employees</span>
                    </td>
                    
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button"
                                    class="btn btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editWorkShiftModal"
                                    data-shift='<?= html_escape(json_encode($s)) ?>'>
                                <i class="ti ti-edit"></i>
                            </button>

                            <?= delete_link([
                                'url'     => 'admin/setup/attendance/delete_shift/' . (int)$s['id'],
                                'icon'  => '<i class="ti ti-trash"></i>',
                                'class' => 'btn btn-outline-danger',
                                'message' => 'This work shift will be permanently deleted. Continue?',
                            ]) ?>
                            
                        </div>
                    </td>
                    
                </tr>
            <?php endforeach; ?>

        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ==========================================================
 | LOAD MODALS (ONCE)
 ========================================================== -->
<?= $CI->load->view('admin/setup/attendance/modals/shift_add_modal', [], true) ?>
<?= $CI->load->view('admin/setup/attendance/modals/shift_edit_modal', [], true) ?>

<!-- ==========================================================
 | EDIT SHIFT POPULATE SCRIPT
 ========================================================== -->
<script>
document.addEventListener('click', function (e) {

    const btn = e.target.closest('[data-shift]');
    if (!btn) return;

    let shift = {};
    try {
        shift = JSON.parse(btn.dataset.shift || '{}');
    } catch (err) {
        console.error('Invalid shift JSON in data-shift:', err);
        return;
    }

    const modal = document.getElementById('editWorkShiftModal');
    if (!modal) return;

    const form = modal.querySelector('#editWorkShiftForm');
    if (!form) return;

    /* -----------------------------
     * Helpers
     * ----------------------------- */
    function setValue(name, value) {
        const el = form.querySelector(`[name="${name}"]`);
        if (!el) return;
        el.value = (value ?? '');
    }

    function setCheckbox(name, value) {
        const el = form.querySelector(`[name="${name}"]`);
        if (!el) return;
        el.checked = (String(value) === '1' || value === 1 || value === true);
    }

    function setSelect(name, value, fallback = '') {
        const el = form.querySelector(`[name="${name}"]`);
        if (!el) return;
        el.value = (value ?? fallback);
    }

    function setFlatpickrTime(name, value) {
        const el = form.querySelector(`[name="${name}"]`);
        if (!el) return;

        const v = (value || '').trim();

        // if empty -> clear
        if (!v) {
            if (el._flatpickr) el._flatpickr.clear();
            el.value = '';
            return;
        }

        // set with flatpickr (prevents UI mismatch)
        if (el._flatpickr) {
            el._flatpickr.setDate(v, true, 'H:i');
        } else {
            el.value = v;
        }
    }

    function normalizeOffDays(input) {
        if (!input) return [];

        // already array
        if (Array.isArray(input)) {
            return input.map(x => String(x).trim().toLowerCase()).filter(Boolean);
        }

        // string
        let str = String(input).trim();
        if (!str) return [];

        // JSON attempt
        if (str.startsWith('[') || str.startsWith('{')) {
            try {
                const parsed = JSON.parse(str);
                if (Array.isArray(parsed)) {
                    return parsed.map(x => String(x).trim().toLowerCase()).filter(Boolean);
                }
            } catch (e) {
                // ignore
            }
        }

        // CSV fallback
        return str.split(',')
            .map(x => x.trim().toLowerCase())
            .filter(Boolean);
    }

    /* -----------------------------
     * Populate ALL fields
     * ----------------------------- */

    // Hidden / base
    setValue('id', shift.id);
    setValue('name', shift.name);
    setValue('code', shift.code);

    setSelect('shift_type', shift.shift_type, 'fixed');

    // Times (flatpickr safe)
    setFlatpickrTime('shift_start_time', shift.shift_start_time);
    setFlatpickrTime('shift_end_time', shift.shift_end_time);
    setFlatpickrTime('break_start_time', shift.break_start_time);
    setFlatpickrTime('break_end_time', shift.break_end_time);

    // Calculated hidden values (will be recalculated anyway)
    setValue('weekly_hours', shift.weekly_hours);
    setValue('monthly_hours', shift.monthly_hours);

    // Rules
    setValue('grace_minutes', shift.grace_minutes);
    setValue('monthly_late_minutes', shift.monthly_late_minutes);
    setValue('overtime_after_minutes', shift.overtime_after_minutes);
    setValue('max_overtime_minutes', shift.max_overtime_minutes);
    setSelect('overtime_type', shift.overtime_type, 'normal');
    setValue('min_time_between_punches', shift.min_time_between_punches);

    // Checkboxes
    setCheckbox('is_active', shift.is_active);
    setCheckbox('is_night_shift', shift.is_night_shift);

    // Off days (checkboxes)
    const offDays = normalizeOffDays(shift.off_days);

    form.querySelectorAll('[name="off_days[]"]').forEach(cb => {
        cb.checked = offDays.includes(cb.value);
    });

    /* -----------------------------
     * Trigger calculations properly
     * (your modal code defines calculateShiftDuration inside DOMContentLoaded)
     * We just trigger input change so it recalculates.
     * ----------------------------- */
    setTimeout(() => {
        // Trigger recalculation by dispatching change events
        ['shift_start_time','shift_end_time','break_start_time','break_end_time'].forEach(name => {
            const el = form.querySelector(`[name="${name}"]`);
            if (el) el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }, 80);

});
</script>

