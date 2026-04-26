<!DOCTYPE html>
<html lang="en">
<head>
  <?php $CI =& get_instance(); ?>
<?php
$stylePath = 'assets/css/crmstyle.css';
$styleVersion = file_exists(FCPATH . $stylePath) ? filemtime(FCPATH . $stylePath) : time();
?>
<link rel="stylesheet" href="<?= base_url($stylePath . '?v=' . $styleVersion) ?>">

</head>
<body>

<div class="app-shell">

  <?php echo $CI->load->view('layouts/theme/sidebar', [], true); ?>

  <!-- ============================================================
       MAIN COLUMN  (topbar → page content → footer)
  ============================================================ -->
  <div class="app-main" id="appMain">

    <!-- ── Topbar ──────────────────────────────────────────── -->
    <!--
      Partial: layouts/includes/topbar
      Contains the full .topbar-inner block:
        hamburger · search · notifications panel · profile dropdown
    -->
    <header class="app-topbar">
      <?php echo $CI->load->view('layouts/theme/topbar', [], true); ?>
    </header>

    <!-- ── Page Content ────────────────────────────────────── -->
    <div class="app-content">

      <!-- Page Header (breadcrumb + title + actions) -->
      <!--
        Partial: layouts/includes/header
        Contains the .page-header block.
        Controllers pass $page_title, $page_subtitle, $breadcrumbs[]
        into view data so each page can customise the header.
      -->
      <?php echo $CI->load->view('layouts/includes/header', [], true); ?>

      <!-- Dynamic Sub-view -->
      <main>
        <?php
          if (isset($subview) && isset($view_data)) {
              echo $CI->load->view($subview, $view_data, true);
          } else {
              echo '<div class="alert alert-danger">No content loaded.</div>';
          }
        ?>
      </main>

    </div><!-- /app-content -->

    <!-- ── Footer ──────────────────────────────────────────── -->
    <!--
      Partial: layouts/includes/footer
      Contains the minimal .app-footer block.
    -->
    <?php echo $CI->load->view('layouts/includes/footer', [], true); ?>

  </div><!-- /app-main -->

</div><!-- /app-shell -->

<!-- Mobile sidebar overlay -->
<div class="mobile-overlay" id="mobileOverlay" style="display:none;"></div>

<!-- Scroll-to-top trigger (kept for backwards compatibility) -->
<div class="go-top">
  <span class="progress-value">
    <i class="ti ti-arrow-up"></i>
  </span>
</div>

<!-- ============================================================
     SCRIPTS & MODALS
============================================================ -->
<?php echo $CI->load->view('layouts/includes/script', [], true); ?>
<?php echo $CI->load->view('layouts/includes/alerts', [], true); ?>

<!-- Application Modals -->
<?php echo $CI->load->view('modals/confirm_delete_modal', [], true); ?>
<?php echo $CI->load->view('modals/report_bug_modal', [], true); ?>
<?php echo $CI->load->view('modals/todo_modal', [], true); ?>
<?php echo $CI->load->view('apps/notepad/quick_note_modal', [], true); ?>

<?php hooks()->do_action('app_admin_footer'); ?>

<!-- ── Realtime / Pusher config ──────────────────────────── -->
<?php
  $CI = isset($CI) ? $CI : get_instance();

  $realtime_user_id = 0;
  $pusher_enabled   = 0;
  $pusher_key       = '';
  $pusher_cluster   = 'mt1';

  if ($CI && isset($CI->session)) {
      $realtime_user_id = (int) ($CI->session->userdata('user_id') ?? 0);
  }

  if (function_exists('get_setting')) {
      $pusher_enabled = (int)    get_setting('pusher_realtime_notifications', 0);
      $pusher_key     = (string) get_setting('pusher_app_key');
      $pusher_cluster = (string) (get_setting('pusher_cluster') ?: 'mt1');
  }
?>

<audio id="notif-sound" preload="auto">
  <source src="<?= base_url('assets/sounds/notification.mp3'); ?>" type="audio/mpeg">
</audio>
<script src="<?= base_url('assets/js/maincrm.js') ?>"></script>

<script>
  window.APP_REALTIME = {
    userId:        <?= (int)    $realtime_user_id ?>,
    pusherEnabled: <?= (int)    $pusher_enabled ?>,
    pusherKey:     "<?= html_escape($pusher_key) ?>",
    pusherCluster: "<?= html_escape($pusher_cluster) ?>"
  };
  window.TODO_QUICK_ADD_URL = '<?= site_url('todo/quick_add') ?>';
</script>

</body>
</html>