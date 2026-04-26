<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="card-body">
  <div class="mb-4">
    <p class="text-muted mb-2">
      Enable Google reCAPTCHA to protect your authentication forms from bots and automated abuse.
    </p>

    <div class="small text-muted">
      <ol class="mb-1 ps-3">
        <li>Open the
          <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer" class="text-primary">
            Google reCAPTCHA Admin Console
          </a>.
        </li>
        <li>Create a new site (or select an existing one) and choose the appropriate reCAPTCHA version.</li>
        <li>Add your domain(s) in the reCAPTCHA settings and save.</li>
        <li>Copy the <strong>Site Key</strong> and <strong>Secret Key</strong> from Google and paste them below.</li>
      </ol>
    </div>
  </div>

<?php
// Normalize settings
$S = [];
if (isset($existing) && is_array($existing)) {
    $S = $existing;
} elseif (isset($existing_data) && is_array($existing_data)) {
    $S = $existing_data;
}

// Safe escape helper
if (!function_exists('e')) {
    function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
}

// Fields
$enabled        = $S['recaptcha_enabled']         ?? 'no';          // yes|no
$siteKey        = $S['recaptcha_site_key']        ?? '';
$secretKey      = $S['recaptcha_secret_key']      ?? '';
$version        = $S['recaptcha_version']         ?? 'v2_checkbox'; // v2_checkbox|v2_invisible|v3
$scoreThreshold = $S['recaptcha_score_threshold'] ?? '0.5';         // for v3

$onLogin        = $S['recaptcha_on_login']        ?? 'yes';
$onSetPassword  = $S['recaptcha_on_set_password'] ?? 'yes';
$onForgot       = $S['recaptcha_on_forgot']       ?? 'no';

// Tooltip helper (used only where needed)
if (!function_exists('rc_tt')) {
    function rc_tt($label, $tip) {
        return $label . ' <i class="ti ti-question-circle text-primary align-middle"
                data-bs-toggle="tooltip" title="' . e($tip) . '"></i>';
    }
}
?>

<div class="settings-section mb-4 app-form">

  <!-- Enable / Disable ReCAPTCHA -->
  <div class="row">
    <div class="col-md-3 mb-3">
      <label class="form-label">
        <?= rc_tt('Enable reCAPTCHA', 'Turn protection on or off for all configured forms.') ?>
      </label>
      <select name="settings[recaptcha_enabled]" class="form-select" id="recaptcha-enable-toggle">
        <option value="yes" <?= $enabled === 'yes' ? 'selected' : '' ?>>Enabled</option>
        <option value="no"  <?= $enabled === 'no'  ? 'selected' : '' ?>>Disabled</option>
      </select>
    </div>
  </div>

  <!-- Hidden wrapper (only shows when enabled) -->
  <div id="recaptcha-settings-fields" style="<?= $enabled === 'yes' ? '' : 'display:none;' ?>">

    <div class="row mt-2">

      <!-- Version -->
      <div class="col-md-3 mb-3">
        <label class="form-label">
          <?= rc_tt('Version', 'Must match the version you selected in the Google reCAPTCHA console.') ?>
        </label>
        <select name="settings[recaptcha_version]" class="form-select">
          <option value="v2_checkbox"  <?= $version === 'v2_checkbox'  ? 'selected' : '' ?>>v2 – Checkbox</option>
          <option value="v2_invisible" <?= $version === 'v2_invisible' ? 'selected' : '' ?>>v2 – Invisible</option>
          <option value="v3"          <?= $version === 'v3'           ? 'selected' : '' ?>>v3 – Score Based</option>
        </select>
      </div>

      <!-- v3 Score threshold -->
      <div class="col-md-3 mb-3">
        <label class="form-label">
          <?= rc_tt('v3 Score Threshold', 'Only used for reCAPTCHA v3. Typical values range from 0.3 to 0.7.') ?>
        </label>
        <input type="number" min="0" max="1" step="0.1"
               name="settings[recaptcha_score_threshold]"
               class="form-control"
               value="<?= e($scoreThreshold) ?>">
      </div>

      <!-- Site Key -->
      <div class="col-md-6 mb-3">
        <label class="form-label">Site Key</label>
        <input type="text"
               name="settings[recaptcha_site_key]"
               class="form-control"
               value="<?= e($siteKey) ?>"
               placeholder="Paste your Site Key from Google">
      </div>

      <!-- Secret Key -->
      <div class="col-md-6 mb-3">
        <label class="form-label">Secret Key</label>
        <input type="text"
               name="settings[recaptcha_secret_key]"
               class="form-control"
               value="<?= e($secretKey) ?>"
               placeholder="Paste your Secret Key from Google">
      </div>

    </div>

    <!-- Enforcement -->
    <div class="row mt-2">
      <div class="col-md-3 mb-3">
        <label class="form-label">
          <?= rc_tt('Login Form', 'Show reCAPTCHA on the standard login form.') ?>
        </label>
        <select name="settings[recaptcha_on_login]" class="form-select">
          <option value="yes" <?= $onLogin === 'yes' ? 'selected' : '' ?>>Enabled</option>
          <option value="no"  <?= $onLogin === 'no'  ? 'selected' : '' ?>>Disabled</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Set Password</label>
        <select name="settings[recaptcha_on_set_password]" class="form-select">
          <option value="yes" <?= $onSetPassword === 'yes' ? 'selected' : '' ?>>Enabled</option>
          <option value="no"  <?= $onSetPassword === 'no'  ? 'selected' : '' ?>>Disabled</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Forgot Password</label>
        <select name="settings[recaptcha_on_forgot]" class="form-select">
          <option value="yes" <?= $onForgot === 'yes' ? 'selected' : '' ?>>Enabled</option>
          <option value="no"  <?= $onForgot === 'no'  ? 'selected' : '' ?>>Disabled</option>
        </select>
      </div>
    </div>

  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bootstrap tooltips (only where used)
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // Show/hide recaptcha fields dynamically
    const toggle = document.getElementById('recaptcha-enable-toggle');
    const fields = document.getElementById('recaptcha-settings-fields');

    if (toggle && fields) {
        toggle.addEventListener('change', function () {
            if (this.value === 'yes') {
                fields.style.display = '';
            } else {
                fields.style.display = 'none';
            }
        });
    }
});
</script>
