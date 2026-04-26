<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Tasks Management
Description: Internal task management system with assignments, followers, comments, checklists, attachments, timelines, and recurring task automation.
Version: 1.6.0
Author: RCM Centric
Author URI: https://rcmcentric.com
Requires at least: 4.3.*
Settings Icon: ti ti-list-check
Settings Name: Tasks Management
*/

// ─────────────────────────────────────────────────────────────
// 🔁 Define Constants
// ─────────────────────────────────────────────────────────────
define('TASKS_MODULE_NAME', 'tasks');
define('TASKS_MODULE_PATH', module_dir_path(TASKS_MODULE_NAME));
define('TASKS_MODULE_URL', module_dir_url(TASKS_MODULE_NAME));

// ─────────────────────────────────────────────────────────────
// 📦 Register Lifecycle Hooks
// ─────────────────────────────────────────────────────────────
register_activation_hook(TASKS_MODULE_NAME, 'tasks_module_activate');
register_deactivation_hook(TASKS_MODULE_NAME, 'tasks_module_deactivate');
register_uninstall_hook(TASKS_MODULE_NAME, 'tasks_module_uninstall');

// ─────────────────────────────────────────────────────────────
// 🌐 Register Language Files
// ─────────────────────────────────────────────────────────────
$CI   = &get_instance();
$lang = $CI->config->item('language') ?? 'english';

if (file_exists(TASKS_MODULE_PATH . 'language/' . $lang . '/tasks_lang.php')) {
    $CI->lang->load(TASKS_MODULE_NAME . '/tasks', $lang);
} else {
    $CI->lang->load(TASKS_MODULE_NAME . '/tasks', 'english');
}
register_language_files(TASKS_MODULE_NAME, ['tasks']);

// ─────────────────────────────────────────────────────────────
// 🎨 Register CSS/JS Assets
// ─────────────────────────────────────────────────────────────
add_module_assets(TASKS_MODULE_NAME, [
    'css' => ['css/tasks.css'],
    'js'  => ['js/tasks.js'],
]);

// ─────────────────────────────────────────────────────────────
// 🔐 Permissions
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('user_permissions', 'tasks_permissions');
function tasks_permissions($permissions)
{
    $permissions['tasks'] = [
        'name'    => 'Tasks',
        'actions' => [
            'view_global' => 'View Global',
            'view_own'    => 'View Own',
            'create'      => 'Create',
            'edit'        => 'Edit',
            'delete'      => 'Delete',
            'assign'      => 'Assign',
        ],
    ];
    return $permissions;
}

// ─────────────────────────────────────────────────────────────
// 🧭 Sidebar Menu (visible if user can view global or own)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_sidebar_menu', 'tasks_module_sidebar_menu');

function tasks_module_sidebar_menu($menus)
{
    if (!function_exists('staff_can')) {
        return $menus;
    }

    if (!staff_can('view_global', 'tasks') && !staff_can('view_own', 'tasks')) {
        return $menus;
    }

    // Prevent duplicate sidebar entries
    foreach ($menus as $m) {
        if (!empty($m['slug']) && $m['slug'] === 'tasks') {
            return $menus;
        }
    }

    $menus[] = [
        'slug'     => 'tasks',
        'name'     => 'Tasks',
        'icon'     => 'ti ti-list-check',
        'href'     => base_url('tasks'),
        'position' => 40,
        'children' => [],
    ];

    return $menus;
}


// ─────────────────────────────────────────────────────────────
// ⚡ Dashboard Shortcut Icon (matches header app-grid styles)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_shortcut_icons_raw', function ($items) {
    // Respect perms if available, but don't fatal if helper missing
    if (function_exists('staff_can')) {
        if (!staff_can('view_global', 'tasks') && !staff_can('view_own', 'tasks')) {
            return $items;
        }
    }

    $href  = base_url('tasks');
    $label = 'Tasks';
    $icon  = 'ti ti-list-check';
    $badge = null; // e.g. unread/open count. Set to string/number to show.

    $badgeHtml = $badge ? '<span class="app-badge">'.html_escape($badge).'</span>' : '';

    $items[] = '
      <div class="app-cell" data-app-name="tasks">
        <a href="'.html_escape($href).'" class="app-tile" title="'.html_escape($label).'">
          <span class="app-icon"><i class="'.html_escape($icon).'"></i></span>'.$badgeHtml.'
          <div class="app-label">'.html_escape($label).'</div>
        </a>
      </div>';

    return $items;
});


// ─────────────────────────────────────────────────────────────
// 🔗 Module page action links (Modules list)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('module_' . TASKS_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . base_url('tasks/settings') . '" target="_blank">Settings</a>';
    return $actions;
});

// ─────────────────────────────────────────────────────────────
// ⏰ Cron: Recurring Tasks Generator
// (Executes daily to auto-create next task cycles)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('cron_tasks', function ($tasks) {
    $tasks[] = [
        'slug'        => 'tasks:recurring',
        'description' => 'Generate recurring tasks automatically',
        'schedule'    => '0 2 * * *', // every day at 02:00
        'source'      => 'module',
        'module_name' => TASKS_MODULE_NAME,
        'callback'    => 'model:tasks/Tasks_model@cron_generate_recurring',
        'enabled'     => 1,
    ];
    return $tasks;
});


// ─────────────────────────────────────────────────────────────
// 📅 Calendar: single-day pins on start date (fallback: due)
// • Title prefixed with "Task: "
// • Colored by priority (low/normal/high/urgent)
// • Hover via extendedProps: status + due_date
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('calendar_events', function ($events, $args) {
    $CI =& get_instance();
    if (!isset($CI->Tasks_model)) {
        $CI->load->model('tasks/Tasks_model', 'Tasks_model');
    }

    $user_id = (int)($args['user_id'] ?? ($CI->session->userdata('user_id') ?? 0));
    $start   = $args['start'] ?? null;
    $end     = $args['end']   ?? null;
    if (!$start || !$end) return $events;

    // Priority -> color map (override via get_setting if you have it)
    $get = function($k,$d){ return function_exists('get_setting') ? (get_setting($k,$d) ?: $d) : $d; };
    $prioColor = [
        'low'    => $get('tasks_color_low',    '#6c757d'), // gray
        'normal' => $get('tasks_color_normal', '#0d6efd'), // blue
        'high'   => $get('tasks_color_high',   '#fd7e14'), // orange
        'urgent' => $get('tasks_color_urgent', '#dc3545'), // red
    ];
    $textColor = $get('tasks_calendar_text_color', '#ffffff'); // readable on solid bg

    $rows = $CI->Tasks_model->get_calendar_events($start, $end, $user_id);

    $out = [];
    foreach ($rows as $t) {
        $id       = (int)($t['id'] ?? 0);
        $rawTitle = trim((string)($t['title'] ?? 'Task #'.$id));
        $title    = '<b>Task:</b> ' . ($rawTitle !== '' ? $rawTitle : ('#'.$id));

        // Pin only on start; fallback to due
        $pinDate  = $t['start_date'] ?? $t['end_date'] ?? null;
        if (!$pinDate) continue;

        $status   = strtolower((string)($t['status'] ?? 'not_started'));
        $priority = strtolower((string)($t['priority'] ?? 'normal'));
        $bg       = $prioColor[$priority] ?? $prioColor['normal'];

        $out[] = [
            'id'             => 'task_'.$id,
            'title'          => $title,
            'start'          => $pinDate,
            'allDay'         => true,
            'url'            => base_url('tasks/view/'.$id),
            'classNames'     => ['event-task','status-'.$status,'prio-'.$priority],

            // Colorization (FullCalendar reads these)
            'backgroundColor'=> $bg,
            'borderColor'    => $bg,
            'textColor'      => $textColor,

            'extendedProps'  => [
                'event_type' => 'task',
                'status'     => $t['status'] ?? null,
                'due_date'   => $t['end_date'] ?? null, // model maps duedate -> end_date
            ],
        ];
    }

    return array_merge($events, $out);
}, 10, 2);

// ─────────────────────────────────────────────────────────────
// 🧩 Dashboard Widget (My Tasks Summary)
// ─────────────────────────────────────────────────────────────
if (!function_exists('register_dashboard_widget')) {
    @require_once APPPATH . 'helpers/dashboard_helper.php';
}
if (
    function_exists('register_dashboard_widget') &&
    function_exists('staff_can') &&
    (staff_can('view_global', 'tasks') || staff_can('view_own', 'tasks'))
) {
    register_dashboard_widget('row3-col-1', 'tasks_my_summary', 'tasks/widgets/my_summary', 0);
}

// ─────────────────────────────────────────────────────────────
// ✅ Activation / Deactivation / Uninstall Functions
// ─────────────────────────────────────────────────────────────
function tasks_module_activate()
{
    $CI = &get_instance();
    try {
        include_once(TASKS_MODULE_PATH . 'install.php');
    } catch (Exception $e) {
        throw $e;
    }
}

function tasks_module_deactivate()
{
    // No special behavior needed
}

function tasks_module_uninstall()
{
    $CI = &get_instance();
    try {
        include_once(TASKS_MODULE_PATH . 'uninstall.php');
    } catch (Exception $e) {
        throw $e;
    }
}
