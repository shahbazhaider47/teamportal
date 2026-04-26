<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $company = company_info();
    
    // Accept the most likely key, fallback to others
    if (isset($page_title) && $page_title) {
        $pageTitle = html_escape($page_title);
    } elseif (isset($title) && $title) {
        $pageTitle = html_escape($title);
    } elseif (isset($view_data['page_title']) && $view_data['page_title']) {
        $pageTitle = html_escape($view_data['page_title']);
    } elseif (isset($view_data['title']) && $view_data['title']) {
        $pageTitle = html_escape($view_data['title']);
    } else {
        $pageTitle = 'Dashboard';
    }

    $companyName = isset($company['company_name']) ? html_escape($company['company_name']) : 'My Company';
    $favicon = !empty($company['favicon']) ? $company['favicon'] : 'default-favicon.svg';
?>

<!-- Meta Tags -->
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="description"
      content="<?= $companyName ?> offers a secure, modular, and scalable admin interface to manage your business operations with efficiency and control.">
<meta name="keywords"
      content="<?= strtolower($companyName) ?> admin, business dashboard, enterprise portal, management system, web application backend">
<meta name="author" content="<?= $companyName ?>">

<!-- Favicon -->
<link rel="icon" href="<?= base_url("uploads/company/" . $favicon); ?>" type="image/x-icon">
<link rel="shortcut icon" href="<?= base_url("uploads/company/" . $favicon); ?>" type="image/x-icon">

<!-- Title -->
<title><?= $pageTitle ?> | <?= $companyName ?></title>
