<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Team Signoff
Description: Captures daily activity summaries, KPIs, and performance notes. Provides supervisors a quick view into progress, gaps, and operational alignment across teams.
Version: 1.2.0
Author: RCM Centric
Author URI: https://rcmcentric.com
Requires at least: 3.7.*
Requires Modules:
Settings Icon: ti ti-file-report
Settings Name: Team Signoff
*/

// ─────────────────────────────────────────────────────────────
// 🔁 Define Constants
// ─────────────────────────────────────────────────────────────
define('SIGNOFF_MODULE_NAME', 'signoff');

// ─────────────────────────────────────────────────────────────
// 🎨 Register CSS/JS Assets
// ─────────────────────────────────────────────────────────────
add_module_assets(SIGNOFF_MODULE_NAME, [
    'css' => ['signoff.css'],
    'js'  => ['signoff.js'],
]);

define('SIGNOFF_MODULE_PATH', module_dir_path(SIGNOFF_MODULE_NAME));
define('SIGNOFF_MODULE_URL', module_dir_url(SIGNOFF_MODULE_NAME));

// ─────────────────────────────────────────────────────────────
// 📦 Register Lifecycle Hooks
// ─────────────────────────────────────────────────────────────
register_activation_hook(SIGNOFF_MODULE_NAME, 'signoff_module_activate');
register_deactivation_hook(SIGNOFF_MODULE_NAME, 'signoff_module_deactivate');
register_uninstall_hook(SIGNOFF_MODULE_NAME, 'signoff_module_uninstall');

// ─────────────────────────────────────────────────────────────
// 🌐 Register Language Files
// ─────────────────────────────────────────────────────────────
$CI = &get_instance();
$lang = $CI->config->item('language') ?? 'english';
if (file_exists(SIGNOFF_MODULE_PATH . 'language/' . $lang . '/signoff_lang.php')) {
    $CI->lang->load(SIGNOFF_MODULE_NAME . '/signoff', $lang);
} else {
    $CI->lang->load(SIGNOFF_MODULE_NAME . '/signoff', 'english');
}
register_language_files(SIGNOFF_MODULE_NAME, ['signoff']);


// ─────────────────────────────────────────────────────────────
// 🧭 Sidebar Menu Items (Staff + Permission Check)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_sidebar_menu', 'signoff_module_sidebar_menu');
function signoff_module_sidebar_menu($menus)
{
    $CI = &get_instance();

    // ✅ Only add menu if user has permission
    if (
        !staff_can('view_own', 'signoff') &&
        !staff_can('view_global', 'signoff')
    ) {
        return $menus;
    }

    $menus[] = [
        'slug'     => 'signoff',
        'name'     => _l('signoff'),
        'icon'     => 'ti ti-calendar',
        'href'     => base_url('signoff'),
        'position' => 50,
        'children' => [
        ],
    ];

    return $menus;
}



// ─────────────────────────────────────────────────────────────
// 🛟 Header "Apps & Shortcuts" — Signoff tile (app-grid style)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_shortcut_icons_raw', function ($items) {
    if (!function_exists('staff_can')) return $items;
    if (!staff_can('view_global', 'signoff') && !staff_can('view_own', 'signoff')) return $items;

    $href  = base_url('signoff');
    $label = 'Signoff';
    $icon  = 'ti ti-calendar';

    // Optional badge (e.g., open tickets). Set to null to hide.
    $open = (int) ($GLOBALS['signoff_open_count'] ?? 0);
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
hooks()->add_filter('module_' . SIGNOFF_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . base_url('signoff/settings') . '" target="_blank">Settings</a>';
    return $actions;
});

// ─────────────────────────────────────────────────────────────
// ✅ Activation / Deactivation / Uninstall Functions
// ─────────────────────────────────────────────────────────────
function signoff_module_activate()
{
    $CI = &get_instance();

    try {
        include_once(SIGNOFF_MODULE_PATH . 'install.php');
    } catch (Exception $e) {
        throw $e;
    }
}

function signoff_module_deactivate()
{
    log_message('debug', '⚙️ Signoff module deactivated.');
}

function signoff_module_uninstall()
{
    $CI = &get_instance();

    try {
        include_once(SIGNOFF_MODULE_PATH . 'uninstall.php');
    } catch (Exception $e) {
        log_message('error', '❌ Signoff module uninstall failed: ' . $e->getMessage());
        throw $e;
    }
}

// ─────────────────────────────────────────────────────────────
// 🔐 Permissions
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('user_permissions', 'signoff_permissions');
function signoff_permissions($permissions)
{
    $permissions['signoff'] = [
        'name'    => _l('permissions_signoff'),
        'actions' => [
            'view_global'   => 'View Global',
            'view_own'      => 'View Own',
            'own_team'      => 'Own Team',
            'assign'        => 'Assign',
            'approve'       => 'Approve',            
            'create'        => 'Create',
            'edit'          => 'Edit',
            'delete'        => 'Delete',
        ],
    ];
    return $permissions;
}



if (!function_exists('register_dashboard_widget')) {
    require_once APPPATH . 'helpers/dashboard_helper.php';
}
register_dashboard_widget('top-col-1', 'signoff_widget', 'signoff/widgets/signoff_widget', 0);
register_dashboard_widget('top-col-2', 'signoff_widget', 'signoff/widgets/progress_widget', 0);
register_dashboard_widget('top-col-3', 'signoff_widget', 'signoff/widgets/claims_widget', 0);