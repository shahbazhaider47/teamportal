<!DOCTYPE html>
<html lang="en">
<head>
<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('layouts/includes/head', [], true); ?>
<?php echo $CI->load->view('layouts/includes/css', [], true); ?>
</head>
<body>
<div class="app-wrapper <?php echo !empty($hide_sidebar) ? 'no-sidebar' : ''; ?>">
    <?php
    $uri = trim($CI->uri->uri_string(), '/');
    
    $sidebar_view = 'layouts/includes/sidebar'; // your current main sidebar path
    
    if (strpos($uri, 'crm') === 0) {
        $sidebar_view = 'layouts/includes/sidebar_crm';
    } elseif (strpos($uri, 'finance') === 0) {
        $sidebar_view = 'layouts/includes/sidebar_finance';
    }
    
    if (empty($hide_sidebar)) {
        echo $CI->load->view($sidebar_view, [], true);
    }
    
    ?>
    <div class="app-content">
        <?php echo $CI->load->view('layouts/includes/header', [], true); ?>
        <main>
        <?php
            // Render dynamic subview from controller
            if (isset($subview) && isset($view_data)) {
                echo $CI->load->view($subview, $view_data, true);
            } else {
                echo '<div class="alert alert-danger">No content loaded.</div>';
            }
        ?>
        </main>
    </div>

<?php 
$current_uri = uri_string();

if ($CI->session->userdata('is_logged_in') && strpos($current_uri, 'team_chat') === false): 
?>
    <?php $CI->load->view('apps/ai/widget'); ?>
<?php endif; ?>

</div>
<?php echo $CI->load->view('layouts/includes/script', [], true); ?>
<?php echo $CI->load->view('layouts/includes/alerts', [], true); ?>

<!-- Load Application Modal Here -->
<?php echo $CI->load->view('modals/confirm_delete_modal', [], true); ?>
<?php echo $CI->load->view('modals/report_bug_modal', [], true); ?>
<?php echo $CI->load->view('modals/todo_modal', [], true); ?>
<?php echo $CI->load->view('apps/notepad/quick_note_modal', [], true); ?>

<?php hooks()->do_action('app_admin_footer'); ?>


<?php
// Use CI instance instead of $this, and be defensive.
$CI = isset($CI) ? $CI : get_instance();

// Default values
$realtime_user_id = 0;
$pusher_enabled   = 0;
$pusher_key       = '';
$pusher_cluster   = 'mt1';

// Session may not be loaded in every context (e.g. some views, CLI)
if ($CI && isset($CI->session)) {
    $realtime_user_id = (int) ($CI->session->userdata('user_id') ?? 0);
}

// get_setting() may not exist everywhere, so guard it
if (function_exists('get_setting')) {
    // Use the same key as the library: pusher_realtime_notifications
    $pusher_enabled = (int) get_setting('pusher_realtime_notifications', 0);
    $pusher_key     = (string) get_setting('pusher_app_key');
    $pusher_cluster = (string) (get_setting('pusher_cluster') ?: 'mt1');
}
?>

<audio id="notif-sound" preload="auto">
    <source src="<?= base_url('assets/sounds/notification.mp3'); ?>" type="audio/mpeg">
</audio>

<script>
    window.APP_REALTIME = {
        userId: <?= (int) $realtime_user_id; ?>,
        pusherEnabled: <?= (int) $pusher_enabled; ?>,
        pusherKey: "<?= html_escape($pusher_key); ?>",
        pusherCluster: "<?= html_escape($pusher_cluster); ?>"
    };
</script>


<script>
  window.TODO_QUICK_ADD_URL = '<?= site_url('todo/quick_add') ?>';
</script>


</body>
</html>
