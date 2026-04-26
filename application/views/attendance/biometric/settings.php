<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">
  <div class="bg-light-secondary page-header px-3 py-2 mb-3 rounded-3 shadow-sm d-flex align-items-center justify-content-between">
    <h1 class="h6 m-0">Biometric Settings</h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('attendance/biometric') ?>">Back</a>
  </div>

  <?= form_open() ?>
  <div class="card p-3">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Enabled</label>
        <select class="form-select" name="biometric_enabled">
          <?php $v=get_setting('biometric_enabled','no'); ?>
          <option value="no"  <?= $v==='no'?'selected':'' ?>>No</option>
          <option value="yes" <?= $v==='yes'?'selected':'' ?>>Yes</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Default Device ID</label>
        <input class="form-control" name="biometric_default_device_id" value="<?= html_escape(get_setting('biometric_default_device_id','')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Duplicate Window (sec)</label>
        <input class="form-control" type="number" name="biometric_duplicate_window_seconds" value="<?= (int)get_setting('biometric_duplicate_window_seconds',60) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Grace Minutes</label>
        <input class="form-control" type="number" name="biometric_grace_minutes" value="<?= (int)get_setting('biometric_grace_minutes',5) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Late after (min)</label>
        <input class="form-control" type="number" name="biometric_late_after_minutes" value="<?= (int)get_setting('biometric_late_after_minutes',10) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Early leave before (min)</label>
        <input class="form-control" type="number" name="biometric_early_leave_before_minutes" value="<?= (int)get_setting('biometric_early_leave_before_minutes',10) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Default Shift Start</label>
        <input class="form-control" name="biometric_default_shift_start" value="<?= html_escape(get_setting('biometric_default_shift_start','09:00')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Default Shift End</label>
        <input class="form-control" name="biometric_default_shift_end" value="<?= html_escape(get_setting('biometric_default_shift_end','18:00')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Timezone</label>
        <input class="form-control" name="biometric_timezone" value="<?= html_escape(get_setting('biometric_timezone','Asia/Karachi')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Cron Token</label>
        <input class="form-control" name="biometric_cron_token" value="<?= html_escape(get_setting('biometric_cron_token','')) ?>">
        <small class="text-muted">Call: <code><?= site_url('attendance/biometric/run_scheduled?token=' . rawurlencode(get_setting('biometric_cron_token','SET_ME'))) ?></code></small>
      </div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary">Save Settings</button>
    </div>
  </div>
  <?= form_close() ?>
</div>
