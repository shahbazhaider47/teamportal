<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel-body app-form">
    <span class="fw-semibold text-primary mb-4">Please note:</span>
     Real-time notifications require valid Pusher API credentials (App ID, Key, Secret, and Cluster). 
     If you don’t have these configured, please see the
     <a href="https://pusher.com/docs/clusters" target="_blank" class="small text-muted">Pusher documentation</a>.
    
    <hr>
  <?php echo render_input('settings[pusher_app_id]', 'Pusher App ID', $existing_data['pusher_app_id'] ?? ''); ?>

  <?php echo render_input('settings[pusher_app_key]', 'Pusher App Key', $existing_data['pusher_app_key'] ?? ''); ?>

  <?php echo render_input('settings[pusher_app_secret]', 'Pusher App Secret', $existing_data['pusher_app_secret'] ?? ''); ?>

  <div class="form-group">
    <label for="settings[pusher_cluster]" class="control-label">
      Pusher Cluster
      <small><i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" title="Leave blank to use default Pusher cluster."></i></small>
    </label>
    <input type="text" name="settings[pusher_cluster]" id="settings[pusher_cluster]" class="form-control" value="<?= html_escape($existing_data['pusher_cluster'] ?? '') ?>">
  </div>

  <hr>

  <div class="form-group">
    <label class="control-label d-block">Enable Realtime Notifications</label>
    <div class="form-check form-check-inline">
      <input type="radio" id="pusher_yes" name="settings[pusher_realtime_notifications]" value="1" class="form-check-input" <?= isset($existing_data['pusher_realtime_notifications']) && $existing_data['pusher_realtime_notifications'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="pusher_yes">Yes</label>
    </div>
    <div class="form-check form-check-inline">
      <input type="radio" id="pusher_no" name="settings[pusher_realtime_notifications]" value="0" class="form-check-input" <?= isset($existing_data['pusher_realtime_notifications']) && $existing_data['pusher_realtime_notifications'] == '0' ? 'checked' : '' ?>>
      <label class="form-check-label" for="pusher_no">No</label>
    </div>
  </div>

  <hr>

  <div class="form-group">
    <label class="control-label d-block">Enable Desktop Notifications</label>
    <div class="form-check form-check-inline">
      <input type="radio" id="desktop_yes" name="settings[desktop_notifications]" value="1" class="form-check-input" <?= isset($existing_data['desktop_notifications']) && $existing_data['desktop_notifications'] == '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="desktop_yes">Yes</label>
    </div>
    <div class="form-check form-check-inline">
      <input type="radio" id="desktop_no" name="settings[desktop_notifications]" value="0" class="form-check-input" <?= isset($existing_data['desktop_notifications']) && $existing_data['desktop_notifications'] == '0' ? 'checked' : '' ?>>
      <label class="form-check-label" for="desktop_no">No</label>
    </div>
    <small class="text-muted d-block mt-1">Requires HTTPS and browser support</small>
  </div>

  <hr>

  <?php echo render_input('settings[auto_dismiss_desktop_notifications_after]', 'Auto Dismiss After (Seconds)', $existing_data['auto_dismiss_desktop_notifications_after'] ?? '', 'number'); ?>

</div>
