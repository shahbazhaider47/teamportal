<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: My Reminders
Description: Allows users to set reminders, follow-ups, deadlines, and recurring tasks alerts. Sends in-app and email notifications to users for accountability and personal productivity.
Version: 2.3.1
Author: RCM Centric
Author URI: https://rcmcentric.com
Requires at least: 4.3.*
Requires Modules:
*/

// ─────────────────────────────────────────────────────────────
// 🔁 Define Constants
// ─────────────────────────────────────────────────────────────
define('REMINDERS_MODULE_NAME', 'reminders');
define('REMINDERS_MODULE_PATH', module_dir_path(REMINDERS_MODULE_NAME));
define('REMINDERS_MODULE_URL', module_dir_url(REMINDERS_MODULE_NAME));

// ─────────────────────────────────────────────────────────────
// 📦 Register Lifecycle Hooks
// ─────────────────────────────────────────────────────────────
register_activation_hook(REMINDERS_MODULE_NAME, 'reminders_module_activate');
register_deactivation_hook(REMINDERS_MODULE_NAME, 'reminders_module_deactivate');
register_uninstall_hook(REMINDERS_MODULE_NAME, 'reminders_module_uninstall');

// ─────────────────────────────────────────────────────────────
// 🌐 Register Language Files
// ─────────────────────────────────────────────────────────────
// Force language file loaded now, for registration-time keys
$CI = &get_instance();
$lang = $CI->config->item('language') ?? 'english';
if (file_exists(REMINDERS_MODULE_PATH . 'language/' . $lang . '/reminders_lang.php')) {
    $CI->lang->load(REMINDERS_MODULE_NAME . '/reminders', $lang);
} else {
    $CI->lang->load(REMINDERS_MODULE_NAME . '/reminders', 'english');
}

// Register for subsequent loads/hooks
register_language_files(REMINDERS_MODULE_NAME, ['reminders']);


hooks()->add_filter('profile_menu_items', function ($items) {
    // Show only for users with access to reminders (global OR own)
    if (
        function_exists('staff_can') &&
        (staff_can('view_global', 'reminders') || staff_can('view_own', 'reminders'))
    ) {
        // Avoid duplicate insert if the filter runs multiple times
        if (!isset($items['my_tasks'])) {
            $items['my_tasks'] = [
                'name'     => 'My Reminders',
                'href'     => base_url('reminders'),
                'icon'     => 'ti ti-checkup-list',
                'position' => 5,
            ];
        }
    }

    return $items;
});


hooks()->add_filter('app_sidebar_menu', 'reminders_module_sidebar_menu');

function reminders_module_sidebar_menu($menus)
{
    // Show only if user has view_global OR view_own on reminders
    if (
        !function_exists('staff_can') ||
        ( !staff_can('view_global', 'reminders') && !staff_can('view_own', 'reminders') )
    ) {
        return $menus;
    }

    // Avoid duplicate insertions (if filter runs multiple times)
    foreach ($menus as $m) {
        if (!empty($m['slug']) && $m['slug'] === 'reminders') {
            return $menus; // already added
        }
    }

    $menus[] = [
        'slug'     => 'reminders',
        'name'     => 'Reminders',
        'icon'     => 'ti ti-calendar',
        'href'     => base_url('reminders'),
        'position' => 10,
        'children' => [],
    ];

    return $menus;
}


// ─────────────────────────────────────────────────────────────
// ⚡ Dashboard Shortcut Icon (matches header app-grid styles)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_shortcut_icons_raw', function ($items) {
    if (!function_exists('staff_can')) return $items;
    if (!staff_can('view_global', 'reminders') && !staff_can('create', 'reminders')) return $items;

    $href  = base_url('reminders');
    $label = 'Reminders';
    $icon  = 'ti ti-alarm';

    // Optional badge (kept hidden as in your current code)
    $dueCount = (int) ($GLOBALS['reminders_due_today'] ?? 0);
    $badge    = $dueCount > 0 ? (string) $dueCount : null;
    $badge    = null;

    $badgeHtml = $badge ? '<span class="app-badge">'.html_escape($badge).'</span>' : '';

    $items[] = '
      <div class="app-cell" data-app-name="support">
        <a href="'.html_escape($href).'"
           class="app-tile"
           title="'.html_escape($label).'">
          <span class="app-icon"><i class="'.html_escape($icon).'"></i></span>'.$badgeHtml.'
          <div class="app-label">'.html_escape($label).'</div>
        </a>
      </div>';

    return $items;
});


// ─────────────────────────────────────────────────────────────
// 🔗 Module Page Action Link (in Modules table/listing)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('module_' . REMINDERS_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . base_url('reminders/settings') . '" target="_blank">Settings</a>';
    return $actions;
});

// ─────────────────────────────────────────────────────────────
// 🎨 Register CSS/JS Assets
// ─────────────────────────────────────────────────────────────
add_module_assets(REMINDERS_MODULE_NAME, [
    'css' => ['css/reminders.css'],
    'js'  => ['js/reminders.js'],
]);

// ─────────────────────────────────────────────────────────────
// ✅ Activation / Deactivation / Uninstall Functions
// ─────────────────────────────────────────────────────────────

function reminders_module_activate()
{
    $CI = &get_instance();

    try {
        include_once(REMINDERS_MODULE_PATH . 'install.php');
    } catch (Exception $e) {
        throw $e;
    }
}

function reminders_module_deactivate()
{
}

function reminders_module_uninstall()
{
    $CI = &get_instance();

    try {
        include_once(REMINDERS_MODULE_PATH . 'uninstall.php');
    } catch (Exception $e) {
        throw $e;
    }
}


// ─────────────────────────────────────────────────────────────
// 🔐 Permissions
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('user_permissions', 'reminders_permissions');
function reminders_permissions($permissions)
{
    $permissions['reminders'] = [
        'name'    =>'Reminders',
        'actions' => [
            'view_global'   => 'View Global',
            'view_own'      => 'View Own',
            'create'        => 'Create',
            'edit'          => 'Edit',
            'delete'        => 'Delete',
        ],
    ];
    return $permissions;
}


// modules/reminders/reminders.php

// Ensure helper is loaded
if (!function_exists('register_dashboard_widget')) {
    require_once APPPATH . 'helpers/dashboard_helper.php';
}

// Register the widget only for users with access:
// - view_global OR view_own on 'reminders'
if (
    function_exists('staff_can') &&
    (staff_can('view_global', 'reminders') || staff_can('view_own', 'reminders'))
) {
    register_dashboard_widget('row3-col-2', 'reminders_widget', 'reminders/widgets/reminders_widget', 0);
}

// ─────────────────────────────────────────────────────────────
// 🧩 Inject Reminder Modals into Master Layout
// ─────────────────────────────────────────────────────────────
hooks()->add_action('app_admin_footer', function () {
    $CI = &get_instance();

    // Hard guards (non-negotiable in enterprise apps)
    if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
        return;
    }

    if (
        !function_exists('staff_can') ||
        (!staff_can('view_global', 'reminders') && !staff_can('view_own', 'reminders'))
    ) {
        return;
    }

    // Load modal view from module
    echo $CI->load->view(
        'reminders/alerts/alert_modal',
        [],
        true
    );
});


// modules/reminders/reminders.php (bootstrap)
hooks()->add_filter('cron_tasks', function ($tasks) {

    /**
     * HOW TO DEFINE A TASK
     *  - slug: unique id (use a namespace like "reminders:*")
     *  - description: short human description
     *  - schedule: a cron expression (minute hour day-of-month month day-of-week)
     *              or one of CronService aliases: every_minute, every5, hourly, daily, weekly
     *  - source/module_name: informational
     *  - callback: HMVC "Controller/method"  (CronService will call Modules::run)
     *              or a static callable "\\Fully\\Qualified\\Class::method"
     *  - enabled: 1 = on, 0 = off
     */

    // 1) In-app/popup alerts (every 5 minutes)
    
    $tasks[] = [
        'slug'        => 'reminders:dispatch_due',
        'description' => 'Send due reminders & missed/past alerts',
        'schedule'    => '0 * * * *', // every hour, at minute 0
        'source'      => 'module',
        'module_name' => 'reminders',
        'callback'    => 'model:reminders/Reminders_model@cron_dispatch_due',
        'enabled'     => 1,
    ];

    // 30-minute email alerts (runs every minute)
    $tasks[] = [
        'slug'        => 'reminders:email_minus_30',
        'description' => 'Email users 30 minutes before reminders are due',
        'schedule'    => '0 * * * *', // every hour, at minute 0
        'source'      => 'module',
        'module_name' => 'reminders',
        // call your mailer model directly (no HMVC controller needed)
        'callback'    => 'model:reminders/Reminders_mailer_model@cron_email_minus_30',
        'enabled'     => 1,
    ];

    return $tasks;
});


// Inject reminders into Calendar events (only title, description, date)
if (!function_exists('reminders_inject_calendar_events')) {
    function reminders_inject_calendar_events(array $events, array $args): array
    {
        $CI =& get_instance();
        $CI->load->model('reminders/Reminders_model');

        $userId   = (int)($args['user_id'] ?? 0);
        $startStr = (string)($args['start']   ?? date('Y-m-d'));
        $endStr   = (string)($args['end']     ?? date('Y-m-d', strtotime('+30 days')));

        // Hide anything older than 24h from "now"
        $nowMinus24 = date('Y-m-d H:i:s', time() - 24 * 3600);

        // Use the stricter/later lower bound
        $from = max($startStr . ' 00:00:00', $nowMinus24);
        $to   = $endStr . ' 23:59:59';

        $rows = $CI->Reminders_model->get_calendar_reminders_basic($userId, $from, $to);

        foreach ($rows as $r) {
            // Map to FullCalendar event
            $events[] = [
                'id'          => 'reminder_' . (int)$r['id'],
                'title'       => (string)$r['title'],
                'start'       => (string)$r['date'],   // expects Y-m-d or Y-m-d H:i:s
                'end'         => null,
                'classNames'  => ['event-info', 'reminder-event'],
                'allDay'      => false,
                'description' => (string)($r['description'] ?? ''),
                'extendedProps' => [
                    'type'        => 'reminder',
                    'reminder_id' => (int)$r['id'],
                    'module'      => 'reminders',
                ],
            ];
        }

        return $events;
    }

    // Register the filter once
    hooks()->add_filter('calendar_events', 'reminders_inject_calendar_events', 10, 2);
}


// ─────────────────────────────────────────────────────────────
// 📊 Inject Reminder Reports Menu (SAFE MERGE)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_reports_menu', function ($menu) {

    if (!isset($menu['reminders'])) {
        $menu['reminders'] = [
            'label'    => 'Reminders',
            'icon'     => 'ti ti-clock',
            'position' => 30,
            'items'    => [],
        ];
    }

    $menu['reminders']['items'][] = [
        'label'    => 'Daily Reminders',
        'href'     => site_url('reports/reminders/daily'),
        'icon'     => 'ti ti-arrow-badge-right',
        'position' => 1,
    ];

    return $menu;
});



hooks()->add_filter('resolve_report', function ($result, $group, $report) {

    if ($group !== 'reminders') {
        return $result;
    }

    if ($report === 'daily') {
        return [
            'title'   => 'Daily Reminders',
            'columns' => ['Title', 'Due Date', 'Status'],
            'rows'    => get_instance()
                ->db
                ->select('title, due_date, status')
                ->from('reminders')
                ->get()
                ->result_array(),
        ];
    }

    return $result;
}, 10, 3);

