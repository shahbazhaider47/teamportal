<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// $existing_data is passed from your controller
function e_val(array $data, string $key, $default = '') {
    return isset($data[$key]) ? $data[$key] : $default;
}

$test_url = site_url('emails/sent_smtp_test_email');
?>

<div class="bg-light-primary mt-3 small py-2 p-4 rounded-2 mb-3">
    <i class="fas fa-info-circle me-2"></i> This uses the <strong>saved</strong> SMTP configuration. Save any changes below first, then run test email below.
</div>

<div class="app-form">    
<div class="mb-3 row">
  <label class="col-sm-3 col-form-label">Protocol</label>
  <div class="col-sm-9">
    <select name="settings[email_protocol]" class="form-select">
      <?php foreach (['mail','smtp'] as $proto): ?>
        <option value="<?= $proto ?>" <?= e_val($existing_data,'email_protocol') === $proto ? 'selected' : '' ?>>
          <?= ucfirst($proto) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="mb-3 row">
  <label class="col-sm-3 col-form-label">SMTP Host</label>
  <div class="col-sm-9">
    <input type="text" class="form-control" name="settings[smtp_host]" value="<?= e_val($existing_data,'smtp_host') ?>">
  </div>
</div>

<div class="mb-3 row">
  <label class="col-sm-3 col-form-label">SMTP Port</label>
  <div class="col-sm-9">
    <input type="number" class="form-control" name="settings[smtp_port]" value="<?= e_val($existing_data,'smtp_port', 587) ?>">
  </div>
</div>

<div class="mb-3 row">
  <label class="col-sm-3 col-form-label">Encryption</label>
  <div class="col-sm-9">
    <select name="settings[smtp_crypto]" class="form-select">
      <?php foreach (['','tls','ssl'] as $enc): ?>
        <option value="<?= $enc ?>" <?= e_val($existing_data,'smtp_crypto') === $enc ? 'selected' : '' ?>>
          <?= $enc === '' ? 'None' : strtoupper($enc) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="mb-3 row">
  <label class="col-sm-3 col-form-label">SMTP Username</label>
  <div class="col-sm-9">
    <input type="text" class="form-control" name="settings[smtp_user]" value="<?= e_val($existing_data,'smtp_user') ?>">
  </div>
</div>

<div class="mb-3 row">
  <label class="col-sm-3 col-form-label">SMTP Password</label>
  <div class="col-sm-9">
    <input type="password" class="form-control" name="settings[smtp_pass]" placeholder="••••••">
  </div>
  <?php if (e_val($existing_data,'smtp_pass')): ?>
    <div class="offset-sm-3 col-sm-9">
      <small class="text-muted">Password already set. Leave blank to keep.</small>
    </div>
  <?php endif; ?>
</div>

<div class="mb-3 row">
  <label class="col-sm-3 col-form-label">Mail From Address</label>
  <div class="col-sm-9">
    <input type="email" class="form-control" name="settings[from_email]" value="<?= e_val($existing_data,'from_email') ?>">
  </div>
</div>

<div class="mb-3 row">
  <label class="col-sm-3 col-form-label">Mail From Name</label>
  <div class="col-sm-9">
    <input type="text" class="form-control" name="settings[from_name]" value="<?= e_val($existing_data,'from_name') ?>">
  </div>
</div>

<hr class="my-4">
<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="card-title d-flex align-items-center">
      <i class="fas fa-envelope me-2 text-primary"></i>
      Test SMTP Configuration
    </h5>
    <p class="text-muted small mb-4">Verify your email server settings by sending a test message</p>

    <!-- Not a form; we submit a hidden standalone form so we don't conflict with the outer settings form -->
    <div class="row g-3 mb-2">
      <div class="col-md-8">
        <label for="testEmail" class="form-label">Recipient Email <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-at"></i></span>
          <input type="email" id="testEmail" class="form-control" placeholder="recipient@example.com" autocomplete="email">
          <div class="invalid-feedback">Please enter a valid email address</div>
        </div>
      </div>
      <div class="col-md-4 d-flex align-items-end">
         <?php if (staff_can('editsystem', 'general')): ?>  
        <button
          type="button"
          class="btn btn-sm btn-primary w-100"
          id="sendTestBtn"
          data-endpoint="<?= $test_url; ?>"
        >
          <i class="fas fa-paper-plane me-1"></i> Send Test Email
        </button>
                     <?php endif; ?>       
      </div>
    </div>
  </div>
</div>
</div>

<script>
(function () {
  'use strict';

  const btn     = document.getElementById('sendTestBtn');
  const emailEl = document.getElementById('testEmail');

  function isValidEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  }

  btn.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const to = (emailEl.value || '').trim();
    if (!isValidEmail(to)) {
      emailEl.classList.add('is-invalid');
      return;
    }
    emailEl.classList.remove('is-invalid');

    // Build a standalone hidden form appended to <body> (prevents nested form issues)
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = btn.getAttribute('data-endpoint');

    const emailInput = document.createElement('input');
    emailInput.type  = 'hidden';
    emailInput.name  = 'test_email';
    emailInput.value = to;
    form.appendChild(emailInput);

    document.body.appendChild(form);
    form.submit(); // Controller sets set_alert + redirects; alerts render after reload
  });
})();
</script>
