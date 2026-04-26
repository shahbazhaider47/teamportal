<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal fade" id="addWorkShiftModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-top">
        <div class="modal-content">
            <form method="post" action="<?= site_url('admin/setup/attendance/save_shift') ?>" id="workShiftForm" class="app-form">

                <!-- HEADER -->
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title text-white">
                        <i class="ti ti-clock-plus me-2"></i> Create Work Shift
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">
                    <div class="card-body">
                
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Shift Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" required>
                            </div>
                
                            <div class="col-md-3">
                                <label class="form-label small">Shift Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" maxlength="10" class="form-control form-control-sm" required>
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

                            <!-- Off Days -->
                            <div class="col-md-12">
                                <div class="d-flex align-items-center flex-wrap gap-3">
                                    <label class="form-label small mb-0 fw-semibold">Off Days:</label>
                                    <div class="form-selectgroup form-selectgroup-inline small">
                                        <?php 
                                        $days = ['sat'=>'Saturday','sun'=>'Sunday','mon'=>'Monday','tue'=>'Tuesday','wed'=>'Wednesday','thu'=>'Thursday','fri'=>'Friday'];
                                        foreach($days as $k=>$v): ?>
                                            <label class="select-items">
                                                <input type="checkbox" name="off_days[]" value="<?= $k ?>" class="select-input">
                                                <span class="select-box"><span class="selectitem"><?= $v ?></span></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- SHIFT TIMING -->
                            <div class="col-md-4">
                                <label class="form-label small">Shift Start Time <span class="text-danger">*</span></label>
                                <input type="text" name="shift_start_time" class="form-control form-control-sm timepicker" id="startTime" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Shift End Time <span class="text-danger">*</span></label>
                                <input type="text" name="shift_end_time" class="form-control form-control-sm timepicker" id="endTime" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Shift Duration (HH:MM)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="shiftDuration" class="form-control bg-light-primary" readonly>
                                    <span class="input-group-text">Hours</span>
                                </div>
                            </div>

                            <!-- Break -->
                            <div class="col-md-4">
                                <label class="form-label small">Break Start Time</label>
                                <input type="text" name="break_start_time" class="form-control form-control-sm timepicker" id="breakStartTime">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Break End Time</label>
                                <input type="text" name="break_end_time" class="form-control form-control-sm timepicker" id="breakEndTime">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Break Duration</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" name="break_minutes" class="form-control bg-light-primary" id="breakMinutes" readonly>
                                    <span class="input-group-text">Minutes</span>
                                </div>
                            </div>

                            <!-- Computed Fields -->
                            <div class="col-md-4">
                                <label class="form-label small">Net Daily Hours</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" name="daily_hours" class="form-control bg-light-primary" id="dailyHours" readonly>
                                    <span class="input-group-text">Hours</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Weekly Hours</label>
                                <input type="number" step="0.01" name="weekly_hours" id="weeklyHours" class="form-control bg-light-primary" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Monthly Hours</label>
                                <input type="number" step="0.01" name="monthly_hours" id="monthlyHours" class="form-control bg-light-primary" readonly>
                            </div>
                        </div>

                        <hr>

                        <!-- RULES -->
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Grace Minutes</label>
                                <input type="number" name="grace_minutes" class="form-control form-control-sm" value="5">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Late Check-in</label>
                                <input type="number" name="monthly_late_minutes" class="form-control form-control-sm" value="15">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Early Checkout</label>
                                <input type="number" name="early_checkout_minutes" class="form-control form-control-sm" value="15">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">OT After (min)</label>
                                <input type="number" name="overtime_after_minutes" class="form-control form-control-sm" value="480">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Max OT (min)</label>
                                <input type="number" name="max_overtime_minutes" class="form-control form-control-sm" value="240">
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
                                <input type="number" name="min_time_between_punches" class="form-control form-control-sm" value="30">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_night_shift" value="1" id="isNightShift">
                                    <label class="form-check-label small" for="isNightShift">Night Shift</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label small">Active Shift</label>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success" id="saveShift">Save Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('workShiftForm');

    flatpickr('.timepicker', {
        enableTime: true,
        noCalendar: true,
        time_24hr: true,
        dateFormat: "H:i",
        minuteIncrement: 5,
        allowInput: true
    });

    function parseTimeToMinutes(timeStr) {
        if (!timeStr || !timeStr.includes(':')) return 0;
        const [h, m] = timeStr.split(':').map(Number);
        return h * 60 + m;
    }

    function formatMinutesToHHMM(totalMinutes) {
        const hours = Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        return `${hours}:${mins.toString().padStart(2, '0')}`;
    }

    function calculateDurations() {
        const start = parseTimeToMinutes(document.getElementById('startTime').value);
        const end = parseTimeToMinutes(document.getElementById('endTime').value);
        const breakStart = parseTimeToMinutes(document.getElementById('breakStartTime').value);
        const breakEnd = parseTimeToMinutes(document.getElementById('breakEndTime').value);

        if (!start || !end) return;

        let total = end - start;
        if (total < 0) total += 1440; // overnight

        let breakMin = 0;
        if (breakEnd && breakStart) {
            breakMin = breakEnd - breakStart;
            if (breakMin < 0) breakMin += 1440;
            document.getElementById('breakMinutes').value = breakMin;
        } else {
            document.getElementById('breakMinutes').value = '';
        }

        const netMinutes = total - breakMin;
        document.getElementById('shiftDuration').value = formatMinutesToHHMM(netMinutes);
        const dailyHours = netMinutes / 60;
        document.getElementById('dailyHours').value = dailyHours.toFixed(2);
        document.getElementById('weeklyHours').value = (dailyHours * 5).toFixed(2);
        document.getElementById('monthlyHours').value = (dailyHours * 5 * 4).toFixed(2);
    }

    ['startTime','endTime','breakStartTime','breakEndTime'].forEach(id => {
        document.getElementById(id).addEventListener('change', calculateDurations);
    });

    form.addEventListener('submit', function(e) {
        calculateDurations();
        const required = form.querySelectorAll('[required]');
        let valid = true;
        required.forEach(f => {
            if (!f.value.trim()) {
                f.classList.add('is-invalid');
                valid = false;
            } else f.classList.remove('is-invalid');
        });
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        } else {
            const btn = document.getElementById('saveShift');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
            btn.disabled = true;
        }
    });

    document.getElementById('addWorkShiftModal').addEventListener('hidden.bs.modal', function(){
        form.reset();
        ['shiftDuration','dailyHours','weeklyHours','monthlyHours','breakMinutes'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const btn = document.getElementById('saveShift');
        btn.innerHTML = 'Save Shift';
        btn.disabled = false;
    });
});
</script>

<style>
.card {
    border-radius: 8px;
}
.input-group-sm > .form-control {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>