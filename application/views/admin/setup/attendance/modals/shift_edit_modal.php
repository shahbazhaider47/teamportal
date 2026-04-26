<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="editWorkShiftModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-top">
        <div class="modal-content">

            <form method="post"
                  action="<?= site_url('admin/setup/attendance/update_shift') ?>"
                  id="editWorkShiftForm" class="app-form">

                <input type="hidden" name="id">
                <input type="hidden" name="weekly_hours">
                <input type="hidden" name="monthly_hours">

                <!-- HEADER -->
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title text-white">
                        <i class="ti ti-clock-edit me-2"></i> Edit Work Shift
                    </h6>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">
                    <div class="card-body">

                        <!-- BASIC DETAILS -->
                        <div class="row g-2">

                            <div class="col-md-6">
                                <label class="form-label small">Shift Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small">Shift Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" maxlength="10"
                                       class="form-control form-control-sm" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small">Shift Type <span class="text-danger">*</span></label>
                                <select name="shift_type" class="form-select form-select-sm" required>
                                    <option value="fixed">Fixed</option>
                                    <option value="regular">Regular</option>
                                    <option value="flexible">Flexible</option>
                                    <option value="off_day">Off Day</option>
                                </select>
                            </div>

                            <!-- OFF DAYS -->
                            <div class="col-md-12">
                                <div class="d-flex align-items-center flex-wrap gap-3">
                                    <label class="form-label small mb-0 fw-semibold">
                                        Off Days:
                                    </label>

                                    <div class="form-selectgroup form-selectgroup-inline small">

                                        <?php
                                        $days = [
                                            'sat' => 'Saturday',
                                            'sun' => 'Sunday',
                                            'mon' => 'Monday',
                                            'tue' => 'Tuesday',
                                            'wed' => 'Wednesday',
                                            'thu' => 'Thursday',
                                            'fri' => 'Friday',
                                        ];
                                        foreach ($days as $key => $label) :
                                        ?>
                                            <label class="select-items">
                                                <input type="checkbox" name="off_days[]" value="<?= $key ?>" class="select-input">
                                                <span class="select-box">
                                                    <span class="selectitem"><?= $label ?></span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>

                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- SHIFT TIMING -->
                            <div class="col-md-4">
                                <label class="form-label small">Shift Start Time <span class="text-danger">*</span></label>
                                <input type="text" name="shift_start_time"
                                       class="form-control form-control-sm timepicker"
                                       id="editStartTime" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">Shift End Time <span class="text-danger">*</span></label>
                                <input type="text" name="shift_end_time"
                                       class="form-control form-control-sm timepicker"
                                       id="editEndTime" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">Shift Duration</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="daily_hours"
                                           class="form-control bg-light-primary"
                                           id="editDailyHours" readonly>
                                    <span class="input-group-text">Hours</span>
                                </div>
                            </div>

                            <!-- BREAK TIMING -->
                            <div class="col-md-4">
                                <label class="form-label small">Break Start Time</label>
                                <input type="text" name="break_start_time"
                                       class="form-control form-control-sm timepicker"
                                       id="editBreakStartTime">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">Break End Time</label>
                                <input type="text" name="break_end_time"
                                       class="form-control form-control-sm timepicker"
                                       id="editBreakEndTime">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">Break Duration</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i class="ti ti-clock"></i>
                                    </span>

                                    <input type="number"
                                           name="break_minutes"
                                           class="form-control bg-light-primary"
                                           id="editBreakMinutes"
                                           readonly>

                                    <span class="input-group-text">Minutes</span>
                                </div>
                            </div>

                        </div>

                        <hr>

                        <!-- RULES -->
                        <div class="row g-2">

                            <div class="col-md-4">
                                <label class="form-label small">Grace Minutes</label>
                                <input type="number" name="grace_minutes"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">Late Check-in</label>
                                <input type="number" name="monthly_late_minutes"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">OT After (min)</label>
                                <input type="number" name="overtime_after_minutes"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">Max OT (min)</label>
                                <input type="number" name="max_overtime_minutes"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">OT Type</label>
                                <select name="overtime_type" class="form-select form-select-sm">
                                    <option value="normal">Normal</option>
                                    <option value="weekend">Weekend</option>
                                    <option value="holiday">Holiday</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small">Min Time Between Punches (min)</label>
                                <input type="number" name="min_time_between_punches"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-6">
                                <div class="form-check mt-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="is_night_shift"
                                           value="1"
                                           id="editIsNightShift">
                                    <label class="form-check-label small" for="editIsNightShift">
                                        Night Shift
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox"
                                           name="is_active" value="1">
                                    <label class="form-check-label small">
                                        Active Shift
                                    </label>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm btn-success" id="editSaveShift">
                        Update Shift
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const modalEl = document.getElementById('editWorkShiftModal');
    const form    = document.getElementById('editWorkShiftForm');

    if (!modalEl || !form) return;

    /* -----------------------------
     * Flatpickr init
     * ----------------------------- */
    const fpOptions = {
        enableTime: true,
        noCalendar: true,
        time_24hr: true,
        dateFormat: "H:i",
        minuteIncrement: 5,
        allowInput: true
    };

    modalEl.querySelectorAll('.timepicker').forEach(el => {
        flatpickr(el, fpOptions);
    });

    /* -----------------------------
     * Helpers
     * ----------------------------- */
    function parseTimeToMinutes(timeStr) {
        if (!timeStr || typeof timeStr !== 'string') return null;
        const parts = timeStr.split(':');
        if (parts.length !== 2) return null;

        const h = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);

        if (isNaN(h) || isNaN(m)) return null;

        return (h * 60) + m;
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

        // Try JSON
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

    function setFlatpickrTime(selector, value) {
        const el = form.querySelector(selector);
        if (!el) return;

        const v = (value || '').trim();
        if (!v) {
            el.value = '';
            return;
        }

        if (el._flatpickr) {
            el._flatpickr.setDate(v, true, 'H:i');
        } else {
            el.value = v;
        }
    }

    function getWorkingDaysCount() {
        const checkedOff = form.querySelectorAll('[name="off_days[]"]:checked').length;
        const workingDays = 7 - checkedOff;
        return Math.max(0, workingDays);
    }

    /* -----------------------------
     * Break Duration
     * ----------------------------- */
    function calculateBreakDuration() {
        const breakStart = form.querySelector('#editBreakStartTime').value;
        const breakEnd   = form.querySelector('#editBreakEndTime').value;

        const breakMinutesEl = form.querySelector('#editBreakMinutes');

        if (!breakStart || !breakEnd) {
            breakMinutesEl.value = '';
            return 0;
        }

        let startMin = parseTimeToMinutes(breakStart);
        let endMin   = parseTimeToMinutes(breakEnd);

        if (startMin === null || endMin === null) {
            breakMinutesEl.value = '';
            return 0;
        }

        // if crosses midnight
        if (endMin <= startMin) {
            endMin += 1440;
        }

        const mins = Math.max(0, endMin - startMin);
        breakMinutesEl.value = mins;

        return mins;
    }

    /* -----------------------------
     * Shift Duration
     * ----------------------------- */
    function calculateShiftDuration() {
        const startTime = form.querySelector('#editStartTime').value;
        const endTime   = form.querySelector('#editEndTime').value;

        const dailyHoursEl = form.querySelector('#editDailyHours');

        if (!startTime || !endTime) {
            dailyHoursEl.value = '';
            form.querySelector('[name="weekly_hours"]').value  = '';
            form.querySelector('[name="monthly_hours"]').value = '';
            return;
        }

        let startMin = parseTimeToMinutes(startTime);
        let endMin   = parseTimeToMinutes(endTime);

        if (startMin === null || endMin === null) {
            dailyHoursEl.value = '';
            return;
        }

        // if crosses midnight
        if (endMin <= startMin) {
            endMin += 1440;
        }

        // Always recalc break first (so shift uses correct break minutes)
        const breakMin = calculateBreakDuration();

        let workedMinutes = Math.max(0, (endMin - startMin) - breakMin);
        let dailyHours = workedMinutes / 60;

        dailyHoursEl.value = dailyHours.toFixed(2);

        const workingDays = getWorkingDaysCount();

        // weekly = dailyHours * workingDays
        const weeklyHours  = dailyHours * workingDays;

        // monthly = dailyHours * (workingDays * 4.33)  (average weeks per month)
        // This is more correct than fixed 22 and works for any off-day pattern.
        const monthlyHours = dailyHours * (workingDays * 4.33);

        form.querySelector('[name="weekly_hours"]').value  = weeklyHours.toFixed(2);
        form.querySelector('[name="monthly_hours"]').value = monthlyHours.toFixed(2);
    }

    /* -----------------------------
     * Events
     * ----------------------------- */
    ['#editStartTime', '#editEndTime', '#editBreakStartTime', '#editBreakEndTime'].forEach(sel => {
        const el = form.querySelector(sel);
        if (el) el.addEventListener('change', () => {
            calculateShiftDuration();
        });
    });

    // Off days change should affect weekly/monthly hours
    form.querySelectorAll('[name="off_days[]"]').forEach(cb => {
        cb.addEventListener('change', calculateShiftDuration);
    });

    /* -----------------------------
     * Populate Form (Main Fix)
     * ----------------------------- */
    window.populateEditForm = function(shiftData) {

        // Basic
        form.querySelector('[name="id"]').value         = shiftData.id || '';
        form.querySelector('[name="name"]').value       = shiftData.name || '';
        form.querySelector('[name="code"]').value       = shiftData.code || '';
        form.querySelector('[name="shift_type"]').value = shiftData.shift_type || 'fixed';

        // Off days
        const offDays = normalizeOffDays(shiftData.off_days);

        form.querySelectorAll('[name="off_days[]"]').forEach(cb => {
            cb.checked = offDays.includes(cb.value);
        });

        // Times
        setFlatpickrTime('#editStartTime', shiftData.shift_start_time || '');
        setFlatpickrTime('#editEndTime', shiftData.shift_end_time || '');
        setFlatpickrTime('#editBreakStartTime', shiftData.break_start_time || '');
        setFlatpickrTime('#editBreakEndTime', shiftData.break_end_time || '');

        // Rules (NO DEFAULTS HERE — load real saved values)
        form.querySelector('[name="grace_minutes"]').value              = (shiftData.grace_minutes ?? '');
        form.querySelector('[name="monthly_late_minutes"]').value       = (shiftData.monthly_late_minutes ?? '');
        form.querySelector('[name="overtime_after_minutes"]').value     = (shiftData.overtime_after_minutes ?? '');
        form.querySelector('[name="max_overtime_minutes"]').value       = (shiftData.max_overtime_minutes ?? '');
        form.querySelector('[name="overtime_type"]').value              = (shiftData.overtime_type || 'normal');
        form.querySelector('[name="min_time_between_punches"]').value   = (shiftData.min_time_between_punches ?? '');

        // Checkboxes
        form.querySelector('[name="is_night_shift"]').checked = String(shiftData.is_night_shift) === '1';
        form.querySelector('[name="is_active"]').checked      = (shiftData.is_active === undefined)
            ? true
            : String(shiftData.is_active) === '1';

        // Recalc after flatpickr applies
        setTimeout(() => {
            calculateShiftDuration();
        }, 80);
    };

    /* -----------------------------
     * Submit handling
     * ----------------------------- */
    form.addEventListener('submit', function(e) {

        // ensure calculations are always updated
        calculateShiftDuration();

        // Required validation
        let valid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value || !field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return;
        }

        // If empty numeric fields, force 0 (so DB doesn’t store null unexpectedly)
        const numericFields = [
            'grace_minutes',
            'monthly_late_minutes',
            'overtime_after_minutes',
            'max_overtime_minutes',
            'min_time_between_punches'
        ];

        numericFields.forEach(name => {
            const el = form.querySelector(`[name="${name}"]`);
            if (el && (el.value === '' || el.value === null)) {
                el.value = 0;
            }
        });

        // loading state
        const submitBtn = modalEl.querySelector('#editSaveShift');
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';
            submitBtn.disabled = true;
        }
    });

    /* -----------------------------
     * Reset on close
     * ----------------------------- */
    modalEl.addEventListener('hidden.bs.modal', function() {

        form.reset();

        // reset calculated outputs
        form.querySelector('#editDailyHours').value = '';
        form.querySelector('#editBreakMinutes').value = '';

        form.querySelector('[name="weekly_hours"]').value  = '';
        form.querySelector('[name="monthly_hours"]').value = '';

        // reset submit button
        const submitBtn = modalEl.querySelector('#editSaveShift');
        if (submitBtn) {
            submitBtn.innerHTML = 'Update Shift';
            submitBtn.disabled = false;
        }

        // remove validation state
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    });

});
</script>

<style>
.card {
    border-radius: 8px;
}
.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
}
.input-group-sm > .form-control {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
.alert-light {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
}
</style>
