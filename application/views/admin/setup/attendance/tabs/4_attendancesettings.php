<div class="card border-0 shadow-sm">
    <div class="card-header bg-light-primary mb-3">
        <h6 class="card-title text-primary mb-0">
            <i class="ti ti-settings me-2"></i>
            Attendance Settings
        </h6>
        <span class="text-muted small">
            These rules control how leave and attendance are enforced system-wide.
        </span>
    </div>
    
    <div class="card-body">
        <form class="app-ajax-form app-form"
              method="post"
              action="<?= site_url('admin/setup/attendance/save_attendance_settings') ?>">

            <div class="row">
                <!-- Allow Leaves -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Allow Staff to Apply Leaves</span>
                        <select name="att_allow_to_apply_leaves" class="form-select">
                            <option value="1" <?= ($settings['att_allow_to_apply_leaves'] ?? '1') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= ($settings['att_allow_to_apply_leaves'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>

                <!-- Monday Leave -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Allow Monday Leaves</span>
                        <select name="att_allow_monday_leave" class="form-select">
                            <option value="1" <?= ($settings['att_allow_monday_leave'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= ($settings['att_allow_monday_leave'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>

                <!-- Friday Leave -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Allow Friday Leaves</span>
                        <select name="att_allow_friday_leave" class="form-select">
                            <option value="1" <?= ($settings['att_allow_friday_leave'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= ($settings['att_allow_friday_leave'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>

                <!-- Bridge Holiday -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Allow Leave Before/After Holidays</span>
                        <select name="att_allow_bridge_holiday_leave" class="form-select">
                            <option value="1" <?= ($settings['att_allow_bridge_holiday_leave'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= ($settings['att_allow_bridge_holiday_leave'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>

                <!-- Sandwich Rule -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Enable Sandwich Rule</span>
                        <select name="att_enable_sandwich_rule" class="form-select">
                            <option value="1" <?= ($settings['att_enable_sandwich_rule'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= ($settings['att_enable_sandwich_rule'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>

                <!-- Sandwich Deduction -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Sandwich Rule Deduction (Days)</span>
                        <input type="number"
                               min="0"
                               class="form-control"
                               name="att_sandwich_deduction_days"
                               value="<?= (int)($settings['att_sandwich_deduction_days'] ?? 1) ?>">
                    </div>
                </div>

                <!-- Max Consecutive Leave Days -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Max Consecutive Leave Days</span>
                        <input type="number"
                               min="0"
                               class="form-control"
                               name="att_max_consecutive_leave_days"
                               value="<?= (int)($settings['att_max_consecutive_leave_days'] ?? 0) ?>">
                    </div>
                </div>

                <!-- Working Days -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Total Working Days</span>
                        <input type="number"
                               min="0"
                               class="form-control"
                               name="att_working_days"
                               value="<?= (int)($settings['att_working_days'] ?? 0) ?>">
                    </div>
                </div>
                
                <!-- Leave Approver -->
                <div class="col-md-3 mb-3">
                    <div class="input-group">
                        <span class="input-group-text group-sm">Leave Approver</span>
                        <select name="att_leave_approver" id="att_leave_approver" class="form-select">
                            <option value="auto" <?= ($settings['att_leave_approver'] ?? '') === 'auto' ? 'selected' : '' ?>>Auto Approval</option>
                            <option value="director" <?= ($settings['att_leave_approver'] ?? '') === 'director' ? 'selected' : '' ?>>By Directors</option>
                            <option value="hr" <?= ($settings['att_leave_approver'] ?? '') === 'hr' ? 'selected' : '' ?>>By HR Department</option>
                            <option value="manager" <?= ($settings['att_leave_approver'] ?? '') === 'manager' ? 'selected' : '' ?>>By Managers</option>
                            <option value="teamlead" <?= ($settings['att_leave_approver'] ?? '') === 'teamlead' ? 'selected' : '' ?>>By Teamleads</option>
                        </select>
                    </div>
                </div>
                
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    Save Attendance Settings
                </button>
            </div>

        </form>
    </div>
</div>