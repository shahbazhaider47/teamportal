<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 – Page Not Found</title>
    <link rel="icon" type="image/png" href="assets/images/404.png" />

  <!-- Bootstrap CSS -->
  <link 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
    rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" 
    crossorigin="anonymous">
  <!-- Font Awesome -->
  <link 
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
    rel="stylesheet"
    integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
    crossorigin="anonymous">
  <style>
    :root {
      --error-color: #056464;
      --error-bg: #e7f1ff;
    }
    body {
      background-color: #f8f9fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      line-height: 1.6;
    }
    .error-container {
      max-width: 500px;
      width: 100%;
    }
    .error-card {
      background: #fff;
      padding: 2.5rem;
      border-radius: 0.75rem;
      box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.08);
      text-align: center;
      border-top: 4px solid var(--error-color);
    }
    .error-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 80px;
      height: 80px;
      background-color: var(--error-bg);
      border-radius: 50%;
      margin-bottom: 1.5rem;
    }
    .error-icon i {
      font-size: 2.5rem;
      color: var(--error-color);
    }
    .error-card h1 {
      font-size: 5rem;
      margin-bottom: 1rem;
      color: #ff7d7d;
      font-weight: 800;
    }
    
    .error-card h2 {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: #6c757d;
      font-weight: 700;
    }
    
    .error-card p {
      color: #6c757d;
      margin-bottom: 2rem;
      font-size: 1.05rem;
    }
    .btn-home {
      padding: 0.5rem 1.5rem;
      font-weight: 500;
      font-size: 13px;
      transition: all 0.3s ease;
      background: #056464;
      color: white;
      border: none;
    }
    @media (max-width: 576px) {
      .error-card {
        padding: 1.5rem;
      }
      .error-icon {
        width: 60px;
        height: 60px;
      }
      .error-icon i {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="error-container">
    <div class="error-card">
      <div class="error-icon" aria-hidden="true">
        <i class="fas fa-search-minus"></i>
      </div>
      <h1>404</h1>
      <h2>Page Not Found</h2>
        <p>
          Sorry, the page you are looking for doesn’t exist or has been moved.<br>
        </p>
      <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
        <button onclick="history.back()" class="btn btn-light-secondary btn-home d-lg-inline-flex align-items-center">
          <i class="fas fa-arrow-left me-2"></i> Go Back
        </button>        
        <a href="<?= function_exists('site_url') ? site_url() : '/'; ?>" class="btn btn-primary btn-home">
          <i class="fas fa-home me-2"></i> Return to Dashboard
        </a>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" 
    crossorigin="anonymous"></script>
</body>
</html>
