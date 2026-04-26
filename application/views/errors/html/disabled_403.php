<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>403 – Access Denied</title>

<?php if (!function_exists('get_company_favicon')) { $this->load->helper('company'); }

ob_start(); get_company_favicon(); $_fav = trim(ob_get_clean());
if ($_fav !== '') {
  echo $_fav;
} else {
  $fallback = base_url('assets/images/favicon.png');
  echo '<link rel="icon" href="'.html_escape($fallback).'" type="image/png" />';
}
?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"  crossorigin="anonymous">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"  rel="stylesheet" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">

  <style>
    :root { --error-color:#dc3545; --error-bg:#f8d7da; }
    body { background:#f8f9fa; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; line-height:1.6; }
    .error-container { max-width:500px; width:100%; }
    .error-card { background:#fff; padding:2.5rem; border-radius:.75rem; box-shadow:0 .5rem 1.5rem rgba(0,0,0,.08); text-align:center; border-top:4px solid var(--error-color); }
    .error-icon { display:inline-flex; align-items:center; justify-content:center; width:80px; height:80px; background:var(--error-bg); border-radius:50%; margin-bottom:1.5rem; }
    .error-icon i { font-size:2.5rem; color:var(--error-color); }
    .error-card h1 { font-size:1.75rem; margin-bottom:1rem; color:var(--error-color); font-weight:600; }
    .error-card p { color:#6c757d; margin-bottom:2rem; font-size:1.05rem; }
    .btn-home { padding:.5rem 1.5rem; font-weight:500; transition:all .3s ease; }
    @media (max-width:576px){
      .error-card{ padding:1.5rem; }
      .error-icon{ width:60px; height:60px; }
      .error-icon i{ font-size:2rem; }
    }
  </style>
  
</head>
<body>
  <div class="error-container">
    <div class="error-card">
      <div class="error-icon" aria-hidden="true">
      <i class="fa-solid fa-lock fa-fw"></i>
      </div>
      <h1>Feature Disabled</h1>
      <p>This module / feature is disabled, please contact your administrator.</p>
      <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
        <a href="<?= site_url() ?>" class="btn btn-primary btn-home">
          <i class="fas fa-home me-2"></i> Return to Dashboard
        </a>
        <button onclick="history.back()" class="btn btn-outline-secondary btn-home">
          <i class="fas fa-arrow-left me-2"></i> Go Back
        </button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>