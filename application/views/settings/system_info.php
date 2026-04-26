<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$CI =& get_instance();
$CI->load->database();

/* -------------------------------------------------------
 | Date / Time
 ------------------------------------------------------- */
$date_format  = get_system_setting('date_format') ?? 'Y-m-d';
$time_format  = get_system_setting('time_format') ?? 'H:i:s';
$timezone     = get_system_setting('default_timezone') ?? date_default_timezone_get();

$current_date = date($date_format);
$current_time = date($time_format);

/* -------------------------------------------------------
 | Database & Sessions
 ------------------------------------------------------- */
$session_count = $CI->db->table_exists('ci_sessions')
    ? $CI->db->count_all('ci_sessions')
    : 'N/A';

$db_version = $CI->db->version();
$db_name    = $CI->db->database ?? 'N/A';

/* -------------------------------------------------------
 | Disk & Filesystem
 ------------------------------------------------------- */
$disk_total = @disk_total_space(FCPATH);
$disk_free  = @disk_free_space(FCPATH);
$disk_usage = ($disk_total && $disk_free)
    ? round((($disk_total - $disk_free) / $disk_total) * 100, 2) . '%'
    : 'N/A';

$uploads_writable = is_writable(FCPATH . 'uploads') ? 'Yes' : 'No';
$cache_writable   = is_writable(APPPATH . 'cache') ? 'Yes' : 'No';
$logs_writable    = is_writable(APPPATH . 'logs') ? 'Yes' : 'No';

/* -------------------------------------------------------
 | Security & Environment
 ------------------------------------------------------- */
$https_enabled   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Yes' : 'No';
$cloudflare     = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? 'Yes' : 'No';
$csrf_enabled   = config_item('csrf_protection') ? 'Yes' : 'No';
$display_errors = ini_get('display_errors') ? 'On' : 'Off';

/* -------------------------------------------------------
 | PHP Extensions (critical)
 ------------------------------------------------------- */
$extensions = [
    'curl', 'openssl', 'mbstring', 'iconv', 'imap',
    'gd', 'zip', 'bcmath', 'intl', 'pdo', 'mysqli'
];

/* -------------------------------------------------------
 | Email / Cron
 ------------------------------------------------------- */
$last_cron = get_setting('last_cron_run') ?: 'Never';
$cron_cli  = get_setting('cron_has_run_from_cli') ? 'Yes' : 'No';

/* -------------------------------------------------------
 | File Permissions
 ------------------------------------------------------- */
$pipe_permissions = file_exists(FCPATH . 'pipe.php')
    ? substr(sprintf('%o', fileperms(FCPATH . 'pipe.php')), -3)
    : 'N/A';
?>

<div class="container-fluid">
    
<div class="card-body">
    <table class="table table-bordered table-hover table-sm table-striped align-middle">
        <tbody>

        <!-- SYSTEM -->
        <tr><th colspan="2" class="bg-light-primary">System</th></tr>
        <tr><th>Operating System</th><td><?= PHP_OS ?></td></tr>
        <tr><th>Environment</th><td><?= ENVIRONMENT ?? 'production' ?></td></tr>
        <tr><th>Base URL</th><td><?= base_url() ?></td></tr>
        <tr><th>Installation Path</th><td><?= FCPATH ?></td></tr>
        <tr><th>Installation Date</th><td><?= get_system_setting('installation_date') ?? 'N/A' ?></td></tr>

        <!-- TIME -->
        <tr><th colspan="2" class="bg-light-primary">Date & Time</th></tr>
        <tr><th>Timezone</th><td><?= $timezone ?></td></tr>
        <tr><th>Current Date</th><td><?= $current_date ?> (<?= $date_format ?>)</td></tr>
        <tr><th>Current Time</th><td><?= $current_time ?> (<?= $time_format ?>)</td></tr>

        <!-- SERVER -->
        <tr><th colspan="2" class="bg-light-primary">Server</th></tr>
        <tr><th>Web Server</th><td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td></tr>
        <tr><th>Server Protocol</th><td><?= $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1' ?></td></tr>
        <tr><th>HTTPS Enabled</th><td><?= $https_enabled ?></td></tr>
        <tr><th>Cloudflare Detected</th><td><?= $cloudflare ?></td></tr>
        <tr><th>Webserver User</th><td><?= get_current_user() ?></td></tr>

        <!-- PHP -->
        <tr><th colspan="2" class="bg-light-primary">PHP</th></tr>
        <tr><th>PHP Version</th><td><?= PHP_VERSION ?></td></tr>
        <tr><th>Memory Limit</th><td><?= ini_get('memory_limit') ?></td></tr>
        <tr><th>Max Execution Time</th><td><?= ini_get('max_execution_time') ?> sec</td></tr>
        <tr><th>Max Input Vars</th><td><?= ini_get('max_input_vars') ?></td></tr>
        <tr><th>Upload Max Filesize</th><td><?= ini_get('upload_max_filesize') ?></td></tr>
        <tr><th>Post Max Size</th><td><?= ini_get('post_max_size') ?></td></tr>
        <tr><th>Display Errors</th><td><?= $display_errors ?></td></tr>

        <tr>
            <th>Required PHP Extensions</th>
            <td>
                <?php foreach ($extensions as $ext): ?>
                    <?= extension_loaded($ext)
                        ? "<span class='text-success'>✔ {$ext}</span>"
                        : "<span class='text-danger'>✖ {$ext}</span>" ?>
                    &nbsp;
                <?php endforeach; ?>
            </td>
        </tr>

        <!-- DATABASE -->
        <tr><th colspan="2" class="bg-light-primary">Database</th></tr>
        <tr><th>Database Name</th><td><?= $db_name ?></td></tr>
        <tr><th>MySQL Version</th><td><?= $db_version ?></td></tr>
        <tr><th>SQL Mode</th><td><?= @$CI->db->query("SELECT @@sql_mode AS mode")->row()->mode ?? 'N/A' ?></td></tr>
        <tr><th>Max Connections</th><td><?= @$CI->db->query("SHOW VARIABLES LIKE 'max_connections'")->row()->Value ?? 'N/A' ?></td></tr>
        <tr><th>Max Allowed Packet</th><td><?= @$CI->db->query("SHOW VARIABLES LIKE 'max_allowed_packet'")->row()->Value ?? 'N/A' ?></td></tr>

        <!-- SESSIONS -->
        <tr><th colspan="2" class="bg-light-primary">Sessions</th></tr>
        <tr>
            <th>Session Table Rows</th>
            <td>
                <?= $session_count ?>
                <?php if (is_numeric($session_count)): ?>
                    <a href="<?= site_url('utilities/clear_sessions') ?>"
                       class="btn btn-sm btn-outline-danger ms-2">
                        Clear
                    </a>
                <?php endif; ?>
            </td>
        </tr>

        <!-- FILESYSTEM -->
        <tr><th colspan="2" class="bg-light-primary">Filesystem</th></tr>
        <tr><th>Disk Usage</th><td><?= $disk_usage ?></td></tr>
        <tr><th>Uploads Writable</th><td><?= $uploads_writable ?></td></tr>
        <tr><th>Cache Writable</th><td><?= $cache_writable ?></td></tr>
        <tr><th>Logs Writable</th><td><?= $logs_writable ?></td></tr>
        <tr><th>Temp Directory</th><td><?= sys_get_temp_dir() ?></td></tr>
        <tr><th>pipe.php Permissions</th><td><?= $pipe_permissions ?></td></tr>

        <!-- SECURITY -->
        <tr><th colspan="2" class="bg-light-primary">Security</th></tr>
        <tr><th>CSRF Protection</th><td><?= $csrf_enabled ?></td></tr>
        <tr><th>allow_url_fopen</th><td><?= ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled' ?></td></tr>
        <tr><th>Suhosin</th><td><?= extension_loaded('suhosin') ? 'Enabled' : 'Not Installed' ?></td></tr>

        <!-- CRON -->
        <tr><th colspan="2" class="bg-light-primary">Cron</th></tr>
        <tr><th>Last Cron Run</th><td><?= $last_cron ?></td></tr>
        <tr><th>Cron Ran via CLI</th><td><?= $cron_cli ?></td></tr>

        <!-- MISC -->
        <tr><th colspan="2" class="bg-light-primary">Miscellaneous</th></tr>
        <tr><th>Allowed Upload Types</th><td><?= str_replace('|', ', ', get_system_setting('allowed_files', 'jpg,jpeg,png,pdf')) ?></td></tr>
        <tr><th>Using custom.css</th><td><?= file_exists(FCPATH . 'assets/css/custom.css') ? 'Yes' : 'No' ?></td></tr>

        </tbody>
    </table>
</div>

</div>