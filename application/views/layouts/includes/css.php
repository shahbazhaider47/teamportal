<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
if (!function_exists('asset_css')) {
    function asset_css($path, $version = true)
    {
        $isExternal = preg_match('#^(https?:)?//#i', $path);

        if ($isExternal) {
            return $path;
        }

        $fullPath = FCPATH . ltrim($path, '/');
        $url      = base_url($path);

        if ($version && file_exists($fullPath)) {
            $url .= '?v=' . filemtime($fullPath);
        }

        return $url;
    }
}
?>

<!-- Animation css -->
<link rel="stylesheet" href="<?= asset_css('assets/vendor/animation/animate.min.css') ?>">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= asset_css('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap', false) ?>">

<!-- Font Awesome -->
<link rel="stylesheet" href="<?= asset_css('assets/vendor/fontawesome/css/all.css') ?>">

<!-- Alerts -->
<link rel="stylesheet" href="<?= asset_css('assets/css/alerts.css') ?>">

<!-- Quick Notes -->
<link rel="stylesheet" href="<?= asset_css('assets/css/quicknotes.css') ?>">

<!-- Tabler icons -->
<link rel="stylesheet" href="<?= asset_css('assets/vendor/tabler-icons/tabler-icons.css') ?>">

<!-- Bootstrap -->
<link rel="stylesheet" href="<?= asset_css('assets/vendor/bootstrap/bootstrap.min.css') ?>">

<!-- Select2 -->
<link rel="stylesheet" href="<?= asset_css('assets/vendor/select/select2.min.css') ?>">

<!-- Flatpickr -->
<link rel="stylesheet" href="<?= asset_css('assets/vendor/datepikar/flatpickr.min.css') ?>">

<!-- Toastify -->
<link rel="stylesheet" href="<?= asset_css('assets/vendor/notifications/toastify.min.css') ?>">

<!-- Dynamic plugin/page styles -->
<?php if (!empty($styles) && is_array($styles)): ?>
    <?php foreach ($styles as $href): ?>
        <link rel="stylesheet" href="<?= asset_css($href) ?>">
    <?php endforeach; ?>
<?php endif; ?>

<!-- Module CSS -->
<?php if (function_exists('app_styles')) { app_styles(); } ?>

<!-- Main Stylesheets -->
<link rel="stylesheet" href="<?= asset_css('assets/css/global.css') ?>">
<link rel="stylesheet" href="<?= asset_css('assets/css/responsive.css') ?>">
<link rel="stylesheet" href="<?= asset_css('assets/css/custom.css') ?>">
<link rel="stylesheet" href="<?= asset_css('assets/css/tailwind.css') ?>">
<link rel="stylesheet" href="<?= asset_css('assets/css/buttons.css') ?>">