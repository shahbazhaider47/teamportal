<!-- jQuery -->
<script src="<?= base_url('assets/js/jquery-3.6.3.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/simplebar/simplebar.js') ?>"></script>
<script src="<?= base_url('assets/vendor/prism/prism.min.js') ?>"></script>

    <!-- select2 -->
    <script src="<?= base_url('assets/vendor/select/select2.min.js') ?>"></script>

    <!--js-->
    <script src="<?= base_url('assets/js/select.js') ?>"></script>
<!-- Dynamic Scripts (from $scripts array, for e.g. per-page or 3rd-party) -->
<?php if (!empty($scripts) && is_array($scripts)): ?>
    <?php foreach ($scripts as $src): ?>
        <script src="<?= base_url($src) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Main App Scripts -->

<!-- Main Stylesheets (with versioning) -->
<?php
$scriptePath = 'assets/js/script.js';
$scriptVersion = file_exists(FCPATH . $scriptePath) ? filemtime(FCPATH . $scriptePath) : time();
?>
<script src="<?= base_url($scriptePath . '?v=' . $scriptVersion) ?>"></script>

<?php
$scriptePath = 'assets/js/app.js';
$scriptVersion = file_exists(FCPATH . $scriptePath) ? filemtime(FCPATH . $scriptePath) : time();
?>
<script src="<?= base_url($scriptePath . '?v=' . $scriptVersion) ?>"></script>

<script src="<?= base_url('assets/js/printing.js') ?>"></script>

<script src="<?= base_url('assets/vendor/datatable/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datepikar/flatpickr.js') ?>"></script>
<script src="<?= base_url('assets/js/date_picker.js') ?>"></script>
<script src="<?= base_url('assets/vendor/notifications/toastify-js.js') ?>"></script>
<script src="<?= base_url('assets/vendor/sweetalert/sweetalert.js') ?>"></script>
<script src="<?= base_url('assets/js/sweet_alert.js') ?>"></script>
<script src="<?= base_url('assets/vendor/slick/slick.min.js') ?>"></script>
<!-- Rich Text Editor Tiny MCE -->
<script src="<?= base_url('assets/plugins/tinymce/tinymce.min.js'); ?>"></script>


<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script src="<?= base_url('assets/js/notifications.js'); ?>"></script>
<script src="<?= base_url('assets/js/quicknotes.js'); ?>"></script>

<!-- Define base_url for JS! -->
<script>
    var base_url = "<?= base_url() ?>";
</script>


<!-- Module and Custom JS (asset pipeline, modules_helper.php-driven) -->
<?php if (function_exists('app_scripts')) { app_scripts(); } ?>
