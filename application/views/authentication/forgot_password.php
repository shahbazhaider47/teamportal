<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password | RCM Centric</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">

  <?php
    // reCAPTCHA script include (only if enabled and key present)
    $recaptchaEnabled = !empty($recaptcha_enabled);
    $recaptchaVersion = $recaptcha_version ?? 'v2_checkbox';
    $recaptchaSiteKey = $recaptcha_site_key ?? '';

    if ($recaptchaEnabled && $recaptchaSiteKey):
      if ($recaptchaVersion === 'v3'):
  ?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?= html_escape($recaptchaSiteKey) ?>"></script>
  <?php else: ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php
      endif;
    endif;
  ?>

  <?php
    $CI =& get_instance();
    $CI->load->model('Company_info_model');
    $company = $CI->Company_info_model->get_all_values();
    $favicon = isset($company['favicon']) ? $company['favicon'] : '';
    if (! empty($favicon)): ?>
      <link
        rel="icon"
        type="image/png"
        href="<?= base_url('uploads/company/' . html_escape($favicon)); ?>"
      />
    <?php endif; ?>

  <style>
    body {
      font-family: "Poppins", "Helvetica Neue", Helvetica, Arial, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
    }

    .login-wrapper {
      max-width: 400px;
      width: 100%;
      padding: 1rem;
    }

    .login-card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .login-header {
      text-align: center;
      padding: 2rem 1.5rem;
      background: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
    }

    .login-header h2 {
      font-size: clamp(1.5rem, 4vw, 1.75rem);
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 0.5rem;
    }

    .login-header p {
      font-size: 0.875rem;
      color: #6c757d;
      margin: 0;
    }

    .login-body {
      padding: 1.5rem;
    }

    .form-label {
      font-size: 0.875rem;
      font-weight: 500;
      color: #495057;
      margin-bottom: 0.25rem;
    }

    .form-control {
      font-size: 0.875rem;
      padding: 0.75rem;
      border-radius: 8px;
      border: 1px solid #ced4da;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus {
      border-color: #007bff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
      outline: none;
    }

    .form-control.is-invalid {
      border-color: #dc3545;
    }

    .invalid-feedback {
      font-size: 0.75rem;
      color: #dc3545;
    }

    .forgot-link {
      font-size: 0.875rem;
      color: #007bff;
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .forgot-link:hover {
      color: #0056b3;
      text-decoration: underline;
    }

    .btn-reset {
      width: 100%;
      padding: 0.75rem;
      font-size: 0.875rem;
      font-weight: 500;
      background: #007bff;
      border: none;
      border-radius: 8px;
      color: #ffffff;
      transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .btn-reset:hover {
      background: #0056b3;
      transform: translateY(-2px);
    }

    .btn-reset:active {
      transform: translateY(0);
    }

    .btn-back {
      width: 100%;
      padding: 0.75rem;
      font-size: 0.875rem;
      font-weight: 500;
      background: transparent;
      border: 1px solid #007bff;
      border-radius: 8px;
      color: #007bff;
      transition: all 0.2s ease;
      margin-top: 0.5rem;
    }

    .btn-back:hover {
      background: #f8f9fa;
      color: #0056b3;
      border-color: #0056b3;
    }

    .login-footer {
      text-align: center;
      padding: 1rem;
      font-size: 0.75rem;
      color: #6c757d;
      border-top: 1px solid #dee2e6;
    }

    @media (max-width: 576px) {
      .login-wrapper {
        padding: 0.5rem;
      }

      .login-card {
        border-radius: 8px;
      }

      .login-header {
        padding: 1.5rem 1rem;
      }

      .login-body {
        padding: 1rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">

      <div class="login-header text-center">
        <?php
        $CI =& get_instance();
        $CI->load->model('Company_info_model');
        $company = $CI->Company_info_model->get_all_values();
        $logo_light = isset($company['light_logo']) ? $company['light_logo'] : '';
        if (! empty($logo_light)) : ?>
          <div class="mb-0">
            <img
              src="<?= base_url('uploads/company/' . html_escape($logo_light)); ?>"
              alt="Company Logo"
              style="max-height: 40px; width: auto;"
            />
          </div>
        <?php endif; ?>
      </div>

      <div class="login-body">

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?= validation_errors('<div class="alert alert-danger">','</div>') ?>

        <?php echo form_open('authentication/forgot_password', ['class' => 'needs-validation', 'novalidate' => '']); ?>

          <div class="mb-3">
            <p class="text-muted text-center small">Enter your details to reset your password</p>
            <label for="emp_id" class="form-label">Employee ID</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-id-card"></i></span>
              <input type="text" id="emp_id" name="emp_id" class="form-control <?= form_error('emp_id') ? 'is-invalid' : '' ?>" placeholder="Enter your employee ID" value="<?= set_value('emp_id') ?>" required>
              <?= form_error('emp_id', '<div class="invalid-feedback">', '</div>') ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
              <input type="email" id="email" name="email" class="form-control <?= form_error('email') ? 'is-invalid' : '' ?>" placeholder="Enter your email" value="<?= set_value('email') ?>" required>
              <?= form_error('email', '<div class="invalid-feedback">', '</div>') ?>
            </div>
          </div>

          <?php if (!empty($recaptcha_enabled) && !empty($recaptcha_site_key)): ?>
            <?php if (($recaptcha_version ?? 'v2_checkbox') === 'v3'): ?>
              <!-- reCAPTCHA v3: hidden field, token set via JS -->
              <input type="hidden" name="g-recaptcha-response" id="recaptcha-token">
              <?php if (!empty($recaptcha_error)): ?>
                <div class="invalid-feedback d-block small mb-2">
                  <?= html_escape($recaptcha_error) ?>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <!-- reCAPTCHA v2: visible widget -->
              <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="<?= html_escape($recaptcha_site_key) ?>"></div>
                <?php if (!empty($recaptcha_error)): ?>
                  <div class="invalid-feedback d-block small">
                    <?= html_escape($recaptcha_error) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>

          <button type="submit" class="btn-reset">Reset Password</button>
          <a href="<?php echo site_url('authentication/login'); ?>" class="btn btn-back">Back to Login</a>

        <?php echo form_close(); ?>
      </div>

      <?php
      $CI =& get_instance();
      $CI->load->model('Company_info_model');
      $company = $CI->Company_info_model->get_all_values();
      $company_name = isset($company['company_name']) && $company['company_name'] !== ''
                      ? $company['company_name']
                      : 'Your Company';
      ?>
      <div class="login-footer">
        <p>
          &copy; <?= date('Y') ?> <?= html_escape($company_name) ?>. <br> All Rights Reserved.
        </p>
      </div>
    </div>
  </div>

<?php $CI =& get_instance(); $CI->load->view('layouts/includes/alerts'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
<script>
  (function () {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');

    const recaptchaEnabled = <?= !empty($recaptcha_enabled) ? 'true' : 'false' ?>;
    const recaptchaVersion = '<?= isset($recaptcha_version) ? addslashes($recaptcha_version) : 'v2_checkbox' ?>';
    const recaptchaSiteKey = '<?= isset($recaptcha_site_key) ? addslashes($recaptcha_site_key) : '' ?>';

    Array.from(forms).forEach(form => {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
          form.classList.add('was-validated');
          return;
        }

        // If reCAPTCHA v3 is enabled, fetch token before final submit
        if (recaptchaEnabled && recaptchaVersion === 'v3' && recaptchaSiteKey) {
          event.preventDefault();

          if (typeof grecaptcha !== 'undefined') {
            grecaptcha.ready(function () {
              grecaptcha.execute(recaptchaSiteKey, {action: 'forgot_password'}).then(function (token) {
                var input = document.getElementById('recaptcha-token');
                if (input) {
                  input.value = token;
                }
                form.submit();
              });
            });
          } else {
            alert('Security check could not be loaded. Please refresh the page and try again.');
          }
        } else {
          form.classList.add('was-validated');
        }
      }, false);
    });
  })();
</script>
</body>
</html>
