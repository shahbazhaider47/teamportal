<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Attendance & Leaves
Description: Tracks attendance, working hours, breaks, late arrivals, and leave requests. Supports multi-shift environments, approvals, real-time summaries, and accurate visibility.
Version: 1.6.0
Author: RCM Centric
Author URI: https://rcmcentric.com
Requires at least: 3.3.*
Requires Modules:
Settings Icon: ti ti-clock-edit
Settings Name: Attendance
*/

// ─────────────────────────────────────────────────────────────
// 🔁 Define Constants
// ─────────────────────────────────────────────────────────────
define('ATTENDANCE_MODULE_NAME', 'attendance');

// ─────────────────────────────────────────────────────────────
// 🎨 Register CSS/JS Assets
// ─────────────────────────────────────────────────────────────
add_module_assets(ATTENDANCE_MODULE_NAME, [
    'css' => ['attendance.css'],
    'js'  => ['attendance.js'],
]);

define('ATTENDANCE_MODULE_PATH', module_dir_path(ATTENDANCE_MODULE_NAME));
define('ATTENDANCE_MODULE_URL', module_dir_url(ATTENDANCE_MODULE_NAME));

// ─────────────────────────────────────────────────────────────
// 📦 Register Lifecycle Hooks
// ─────────────────────────────────────────────────────────────
register_activation_hook(ATTENDANCE_MODULE_NAME, 'attendance_module_activate');
register_deactivation_hook(ATTENDANCE_MODULE_NAME, 'attendance_module_deactivate');
register_uninstall_hook(ATTENDANCE_MODULE_NAME, 'attendance_module_uninstall');

// ─────────────────────────────────────────────────────────────
// 🌐 Register Language Files
// ─────────────────────────────────────────────────────────────
$CI = &get_instance();
$lang = $CI->config->item('language') ?? 'english';
if (file_exists(ATTENDANCE_MODULE_PATH . 'language/' . $lang . '/attendance_lang.php')) {
    $CI->lang->load(ATTENDANCE_MODULE_NAME . '/attendance', $lang);
} else {
    $CI->lang->load(ATTENDANCE_MODULE_NAME . '/attendance', 'english');
}
register_language_files(ATTENDANCE_MODULE_NAME, ['attendance']);


// ─────────────────────────────────────────────────────────────
// 🧭 Sidebar Menu Items (Staff + Permission Check)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_sidebar_menu', 'attendance_module_sidebar_menu');
function attendance_module_sidebar_menu($menus)
{
    $CI = &get_instance();

    // ✅ Only add menu if user has permission
    if (
        !staff_can('view_own', 'attendance') &&
        !staff_can('view_global', 'attendance')
    ) {
        return $menus;
    }

    $menus[] = [
        'slug'     => 'attendance',
        'name'     => _l('attendance'),
        'icon'     => 'ti ti-calendar',
        'href'     => base_url('attendance'),
        'position' => 40,
        'children' => [

        ],
    ];

    return $menus;
}


// modules/attendance/attendance.php
hooks()->add_filter('calendar_events', function ($events, $args) {
    $CI =& get_instance();
    if (!isset($CI->Leaves_model)) {
        $CI->load->model('attendance/Leaves_model');
    }
    $leave_events = $CI->Leaves_model->get_calendar_events($args['start'], $args['end'], $args['user_id']);
    $formatted_events = [];
    foreach ($leave_events as $event) {
        $formatted_events[] = [
            'id'        => 'leave_' . $event['id'],
            'title'     => ucfirst($event['leave_type']) . '',
            'start'     => $event['start_date'],
            'end'       => date('Y-m-d', strtotime($event['end_date'].' +1 day')),
            'allDay'    => true,
            'classNames'=> ['event-leave'],
            'extendedProps' => [
                'status'      => $event['status'],
                'description' => $event['leave_notes'] ?? '',
                'type'        => $event['leave_type'],
                'event_type'  => 'leave',              // <==== THIS IS CRITICAL!
                'user'        => isset($event['user_name']) ? $event['user_name'] : '',
                // Add more as needed
            ]
        ];
    }
    return array_merge($events, $formatted_events);
}, 10, 2);


// ─────────────────────────────────────────────────────────────
// 📊 Requests Overview: register "Leave Requests" section
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('requests_sections', 'attendance_register_requests_section', 10, 2);

function attendance_register_requests_section(array $sections, array $args)
{
    if (function_exists('module_is_active') && !module_is_active(ATTENDANCE_MODULE_NAME)) {
        return $sections;
    }

    // Respect Attendance permissions
    if (function_exists('staff_can')) {
        if (!staff_can('view_own', 'attendance') && !staff_can('view_global', 'attendance')) {
            return $sections;
        }
    }

    $CI =& get_instance();

    // You can scope by $args['user_id'] or filters later
    $CI->db->from('user_leaves');
    $total = (int)$CI->db->count_all_results();

    $CI->db->from('user_leaves');
    $CI->db->where('status', 'pending');
    $pending = (int)$CI->db->count_all_results();

    $CI->db->from('user_leaves');
    $CI->db->where('status', 'approved');
    $approved = (int)$CI->db->count_all_results();

    $CI->db->from('user_leaves');
    $CI->db->where('status', 'rejected');
    $rejected = (int)$CI->db->count_all_results();

    $slug = 'leaves'; // THIS is defined in the module, not core

    $sections[$slug] = [
        'slug'        => $slug,
        'label'       => 'Leave Requests',
        //'description' => 'Leave requests submitted by users from attendance.',
        'icon'        => 'ti ti-plane-departure',
        'url'         => site_url('requests/' . $slug),
        'module'      => ATTENDANCE_MODULE_NAME,
        'total'       => $total,
        'pending'     => $pending,
        'approved'    => $approved,
        'rejected'    => $rejected,
    ];

    return $sections;
}


// ─────────────────────────────────────────────────────────────
// 📄 Requests Section View: detailed Leave Requests page
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('requests_section_view', 'attendance_requests_section_view', 10, 2);

function attendance_requests_section_view($html, array $args)
{
    $slug = $args['slug'] ?? '';
    if ($slug !== 'leaves') {
        // Not our section, do nothing
        return $html;
    }

    if (function_exists('module_is_active') && !module_is_active(ATTENDANCE_MODULE_NAME)) {
        return $html;
    }

    // Enforce Attendance permissions here as well
    if (function_exists('staff_can')) {
        if (!staff_can('view_own', 'attendance') && !staff_can('view_global', 'attendance')) {
            return $html; // core will treat empty as 404
        }
    }

    $CI =& get_instance();

    if (!isset($CI->Leaves_model)) {
        $CI->load->model('attendance/Leaves_model');
    }

    $userId  = (int)($args['user_id'] ?? 0);
    $filters = is_array($args['filters'] ?? []) ? $args['filters'] : [];

    // Either a custom model method...
    if (method_exists($CI->Leaves_model, 'get_requests_for_requests_page')) {
        $leaves = $CI->Leaves_model->get_requests_for_requests_page($userId, $filters);
    } else {
        // ...or a direct query fallback
        $CI->db->select('l.*, u.fullname, u.firstname, u.lastname');
        $CI->db->from('user_leaves AS l');
        $CI->db->join('users AS u', 'u.id = l.user_id', 'left');
        $CI->db->order_by('l.created_at', 'DESC');

        $leaves = $CI->db->get()->result_array();
    }

    // THIS is where the module loads its own view file
    return $CI->load->view('attendance/partials/leaves_requests', [
        'leaves'   => $leaves,
        'user_id'  => $userId,
        'section'  => $args['section'] ?? [],
    ], true);
}


// ─────────────────────────────────────────────────────────────
// 🛟 Header "Apps & Shortcuts" — Attendance tile (app-grid style)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_shortcut_icons_raw', function ($items) {
    if (!function_exists('staff_can')) return $items;
    if (!staff_can('view_global', 'attendance') && !staff_can('view_own', 'attendance')) return $items;

    $href  = base_url('attendance');
    $label = 'Attendance';
    $icon  = 'ti ti-clock';

    // Optional badge (e.g., open tickets). Set to null to hide.
    $open = (int) ($GLOBALS['attendance_open_count'] ?? 0);
    $badge = $open > 0 ? (string)$open : null;
    $badge = null;

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
hooks()->add_filter('module_' . ATTENDANCE_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . base_url('settings?group=attendance') . '" target="_blank">Settings</a>';
    return $actions;
});

// ─────────────────────────────────────────────────────────────
// ✅ Activation / Deactivation / Uninstall Functions
// ─────────────────────────────────────────────────────────────
function attendance_module_activate()
{
    $CI = &get_instance();

    try {
        include_once(ATTENDANCE_MODULE_PATH . 'install.php');
    } catch (Exception $e) {
        throw $e;
    }
}

function attendance_module_deactivate()
{
    log_message('debug', '⚙️ Attendance module deactivated.');
}

function attendance_module_uninstall()
{
    $CI = &get_instance();

    try {
        include_once(ATTENDANCE_MODULE_PATH . 'uninstall.php');
    } catch (Exception $e) {
        log_message('error', '❌ Attendance uninstall failed: ' . $e->getMessage());
        throw $e;
    }
}

// ─────────────────────────────────────────────────────────────
// 🔐 Permissions
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('user_permissions', 'attendance_permissions');
function attendance_permissions($permissions)
{
    $permissions['attendance'] = [
        'name'    => _l('permissions_attendance'),
        'actions' => [
            'view_global'   => _l('view_global'),
            'view_own'      => _l('view_own'),
            'own_team'      => _l('own_team'),
            'approve'       => _l('Approve Leaves'),            
            'create'        => _l('create'),
            'edit'          => _l('edit'),
            'delete'        => _l('delete'),
        ],
    ];
    return $permissions;
}


// Tab button
hooks()->add_filter('user_profile_tabs', function ($payload) {
    // $payload is either:
    // - ['tabs'=>[], 'user'=>[...] ]   (new style)
    // - []                            (legacy style)
    if (is_array($payload) && array_key_exists('tabs', $payload)) {
        $payload['tabs'][] = '
          <button class="nav-link" id="attendance-tab"
                  data-bs-toggle="tab" data-bs-target="#attendance"
                  type="button" role="tab" aria-selected="false">
            <i class="ti ti-clock pe-1 ps-1"></i> Attendance
          </button>';
        return $payload;
    }

    // Legacy fallback (payload is just an array of tabs)
    $payload[] = '
      <button class="nav-link" id="attendance-tab"
              data-bs-toggle="tab" data-bs-target="#attendance"
              type="button" role="tab" aria-selected="false">
        <i class="ti ti-clock pe-1 ps-1"></i> Attendance
      </button>';
    return $payload;
});

// Tab content
hooks()->add_filter('user_profile_tab_contents', function ($payload) {
    $CI = &get_instance();

    // New style
    if (is_array($payload) && array_key_exists('contents', $payload)) {
        $user = $payload['user'] ?? null;
        $html = $CI->load->view('attendance/user_tab', ['user' => $user], true);

        $payload['contents'][] = '
          <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
            ' . $html . '
          </div>';
        return $payload;
    }

    // Legacy fallback (payload is just an array of contents)
    $html = $CI->load->view('attendance/user_tab', [], true);
    $payload[] = '
      <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
        ' . $html . '
      </div>';
    return $payload;
});

