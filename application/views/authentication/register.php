<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register | RCM Centric</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
  
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
      max-width: 650px;
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
      font-size: clamp(1rem, 4vw, 1.75rem);
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

    .btn-primary {
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

    .btn-primary:hover {
      background: #0056b3;
      transform: translateY(-2px);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .login-footer {
      text-align: center;
      padding: 1rem;
      font-size: 0.75rem;
      color: #6c757d;
      border-top: 1px solid #dee2e6;
    }

    .input-group-text {
      background-color: #f8f9fa;
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
          <div class="mb-3">
            <img
              src="<?= base_url('uploads/company/' . html_escape($logo_light)); ?>"
              alt="Company Logo"
              style="max-height: 40px; width: auto;"
            />
          </div>
        <?php endif; ?>
      </div>

      <div class="login-body app-form">
        <?= validation_errors('<div class="alert alert-danger">','</div>') ?>
        
        <?php echo form_open('authentication/register', ['class' => 'needs-validation', 'novalidate' => '']); ?>
          <div class="row">

          <div class="col-md-6 mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-at"></i></span>
              <input type="text" id="username" name="username" class="form-control <?= form_error('username') ? 'is-invalid' : '' ?>" placeholder="Choose username" value="<?= set_value('username') ?>" required>
              <?= form_error('username', '<div class="invalid-feedback">', '</div>') ?>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
              <input type="email" id="email" name="email" class="form-control <?= form_error('email') ? 'is-invalid' : '' ?>" placeholder="Enter your email" value="<?= set_value('email') ?>" required>
              <?= form_error('email', '<div class="invalid-feedback">', '</div>') ?>
            </div>
          </div>
          
            <div class="col-md-6 mb-3">
              <label for="firstname" class="form-label">First Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" id="firstname" name="firstname" class="form-control <?= form_error('firstname') ? 'is-invalid' : '' ?>" placeholder="First name" value="<?= set_value('firstname') ?>" required>
                <?= form_error('firstname', '<div class="invalid-feedback">', '</div>') ?>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="lastname" class="form-label">Last Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" id="lastname" name="lastname" class="form-control <?= form_error('lastname') ? 'is-invalid' : '' ?>" placeholder="Last name" value="<?= set_value('lastname') ?>" required>
                <?= form_error('lastname', '<div class="invalid-feedback">', '</div>') ?>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control <?= form_error('password') ? 'is-invalid' : '' ?>" placeholder="Create password" required>
                <?= form_error('password', '<div class="invalid-feedback">', '</div>') ?>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="passconf" class="form-label">Confirm Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" id="passconf" name="passconf" class="form-control <?= form_error('passconf') ? 'is-invalid' : '' ?>" placeholder="Confirm password" required>
                <?= form_error('passconf', '<div class="invalid-feedback">', '</div>') ?>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary mb-3">Register Now</button>
          
          <div class="text-center">
            <p class="mb-0">Already have an account? <a href="<?= site_url('authentication/login') ?>">Sign In</a></p>
          </div>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
  <script>
    (function () {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    })();
  </script>
</body>
</html>