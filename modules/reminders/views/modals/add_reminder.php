<!-- Add Reminder Modal -->
<div class="modal fade" id="add_reminder_modal" tabindex="-1" aria-labelledby="addReminderLabel" aria-hidden="true">
    <div class="modal-dialog">
        <?= form_open(base_url('reminders/add'), ['id' => 'add-reminder-form']); ?>
        <div class="modal-content app-form">
            <div class="modal-header">
                <h5 class="modal-title" id="addReminderLabel">Add Reminder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <!-- Title -->
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                </div>

                <!-- Date & Time -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="date" class="form-control basic-date" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="time" class="form-label">Time</label>
                        <input type="time" name="time" id="time" class="form-control date-time-picker" value="09:00">
                    </div>
                </div>

                <!-- Priority -->
                <div class="mb-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select name="priority" id="priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <!-- Recurring Option -->
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring">
                        <label class="form-check-label" for="is_recurring">
                            Recurring Reminder
                        </label>
                    </div>
                    <div id="recurring-options" class="mt-2" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <select name="recurring_frequency" class="form-select">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="number" name="recurring_duration" class="form-control" placeholder="Duration (times)" min="1">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary btn-sm">Save Reminder</button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>