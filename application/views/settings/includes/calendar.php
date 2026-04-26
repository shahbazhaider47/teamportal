<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form method="post" autocomplete="off" class="app-form">

<div class="mb-3">
    <label class="form-label">Default Clendar Events</label>

    <small class="text-muted small d-block mb-2">
        Define default draggable events for the calendar sidebar. You can set a name and a color for each event.
    </small>

    <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add-draggable-event">
        <i class="fas fa-plus"></i> Add
    </button>
    
    <?php
    // Load existing draggable events, decode JSON, or set default
    $draggable_events = [];
    if (!empty($existing_data['draggable_events'])) {
        if (is_array($existing_data['draggable_events'])) {
            $draggable_events = $existing_data['draggable_events'];
        } else {
            $decoded = json_decode($existing_data['draggable_events'], true);
            $draggable_events = is_array($decoded) ? $decoded : [];
        }
    }
    
    $color_options = [
        'event-primary'   => 'Primary',
        'event-success'   => 'Success',
        'event-warning'   => 'Warning',
        'event-info'      => 'Info',
        'event-danger'    => 'Danger',
        'event-secondary' => 'Secondary',
        'event-dark'      => 'Dark',
    ];
    ?>
    <div id="draggable-events-list" class="app-form">
        <?php foreach ($draggable_events as $i => $event): ?>
            <div class="input-group mb-2 draggable-event-row">
                <input type="text"
                       class="form-control small"
                       name="settings[draggable_events][<?= $i ?>][title]"
                       value="<?= htmlspecialchars($event['title']) ?>"
                       placeholder="Event Title" required>
                <select class="form-select small"
                        name="settings[draggable_events][<?= $i ?>][class]">
                    <?php foreach ($color_options as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($event['class'] ?? '') === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-danger btn-sm btn-remove-event" tabindex="-1">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

    <hr>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="calendar_dragdrop" name="settings[enable_dragdrop]" value="1" <?= !empty($existing_data['enable_dragdrop']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="calendar_dragdrop">Enable Drag & Drop</label>
    </div>
    
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="calendar_holidays" name="settings[enable_holidays]" value="1" <?= !empty($existing_data['enable_holidays']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="calendar_holidays">Show Public Holidays</label>
    </div>
    
    <div class="form-check mb-3">
      <input type="checkbox" class="form-check-input" id="calendar_announcements"
             name="settings[fetch_announcements]" value="1"
             <?= !empty($existing_data['fetch_announcements']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="calendar_announcements">Fetch Announcements</label>
    </div>
    
    <div class="form-check mb-3">
      <input type="checkbox" class="form-check-input" id="calendar_leaves"
             name="settings[fetch_leaves]" value="1"
             <?= !empty($existing_data['fetch_leaves']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="calendar_leaves">Fetch Leaves</label>
    </div>
    
    <div class="form-check mb-3">
      <input type="checkbox" class="form-check-input" id="calendar_reminders"
             name="settings[fetch_reminders]" value="1"
             <?= !empty($existing_data['fetch_reminders']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="calendar_reminders">Fetch Reminders</label>
    </div>
    
    <div class="form-check mb-3">
      <input type="checkbox" class="form-check-input" id="calendar_signoff"
             name="settings[fetch_signoff]" value="1"
             <?= !empty($existing_data['fetch_signoff']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="calendar_signoff">Fetch SignOff</label>
    </div>
    
    <div class="form-check mb-3">
      <input type="checkbox" class="form-check-input" id="calendar_birthdays"
             name="settings[fetch_birthdays]" value="1"
             <?= !empty($existing_data['fetch_birthdays']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="calendar_birthdays">Fetch Birthdays</label>
    </div>
    
    
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Add new event row
    document.getElementById('add-draggable-event').onclick = function () {
        var list = document.getElementById('draggable-events-list');
        var idx = list.querySelectorAll('.draggable-event-row').length;
        var html = `
        <div class="input-group mb-2 draggable-event-row">
            <input type="text" class="form-control" name="settings[draggable_events][${idx}][title]" placeholder="Event Title" required>
            <select class="form-select" name="settings[draggable_events][${idx}][class]">
                <option value="event-primary">Primary</option>
                <option value="event-success">Success</option>
                <option value="event-warning">Warning</option>
                <option value="event-info">Info</option>
                <option value="event-danger">Danger</option>
                <option value="event-secondary">Secondary</option>
                <option value="event-dark">Dark</option>
            </select>
            <button type="button" class="btn btn-danger btn-remove-event" tabindex="-1">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        `;
        list.insertAdjacentHTML('beforeend', html);
    };

    // Remove event row (event delegation)
    document.getElementById('draggable-events-list').addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-event')) {
            e.target.closest('.draggable-event-row').remove();
        }
    });
});
</script>
