<!-- Edit Reminder Modal -->
<div class="modal fade" id="edit_reminder_modal" tabindex="-1" aria-labelledby="editReminderLabel" aria-hidden="true">
    <div class="modal-dialog">
        <?= form_open(base_url('reminders/update'), ['id' => 'edit-reminder-form']); ?>
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-content app-form">
            <div class="modal-header">
                <h5 class="modal-title" id="editReminderLabel">Edit Reminder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Title -->
                <div class="mb-3">
                    <label for="edit_title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label for="edit_description" class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>

                <!-- Date and Time -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="edit_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="edit_date" class="form-control basic-date" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_time" class="form-label">Time</label>
                        <input type="time" name="time" id="edit_time" class="form-control " value="09:00">
                    </div>
                </div>

                <!-- Priority -->
                <div class="mb-3">
                    <label for="edit_priority" class="form-label">Priority</label>
                    <select name="priority" id="edit_priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <!-- Recurring Reminder -->
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_is_recurring" name="is_recurring">
                        <label class="form-check-label" for="edit_is_recurring">
                            Recurring Reminder
                        </label>
                    </div>

                    <div id="edit_recurring_options" class="mt-3" style="display: none;">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="edit_recurring_frequency" class="form-label">Frequency</label>
                                <select name="recurring_frequency" id="edit_recurring_frequency" class="form-select">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_recurring_duration" class="form-label">Duration (times)</label>
                                <input type="number" name="recurring_duration" id="edit_recurring_duration" class="form-control" min="1" placeholder="e.g. 5">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary btn-sm">Update Reminder</button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
