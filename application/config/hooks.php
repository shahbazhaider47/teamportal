<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// ✅ Ensure hooks are initialized
require_once(APPPATH . 'libraries/AppHooks.php');
global $hooks;
$hooks = new AppHooks(); // ✅ This must be declared early

/**
 * ────────────────────────────────────────────────────────────────
 * Global `hooks()` accessor
 * Ensures safe return of hooks instance or null
 * @since 2.3.0
 * ────────────────────────────────────────────────────────────────
 */
function hooks()
{
    global $hooks;
    return isset($hooks) && is_object($hooks) ? $hooks : null;
}


/**
 * ────────────────────────────────────────────────────────────────
 * PRE-SYSTEM HOOKS (EARLIEST STAGE)
 * Used to bootstrap app-wide components like security, autoloaders,
 * and module registration before CI's core system initializes.
 * ────────────────────────────────────────────────────────────────
 */
$hook['pre_system'][] = [
    'class'    => 'EnhanceSecurity',
    'function' => 'protect',
    'filename' => 'EnhanceSecurity.php',
    'filepath' => 'hooks',
    'params'   => [],
];

$hook['pre_system'][] = [
    'class'    => 'App_Autoloader',
    'function' => 'register',
    'filename' => 'App_Autoloader.php',
    'filepath' => 'hooks',
    'params'   => [],
];

$hook['pre_system'][] = [
    'class'    => 'InitModules',
    'function' => 'handle',
    'filename' => 'InitModules.php',
    'filepath' => 'hooks',
    'params'   => [],
];


/**
 * ────────────────────────────────────────────────────────────────
 * PRE-CONTROLLER HOOKS (Before controller instance is created)
 * ────────────────────────────────────────────────────────────────
 */

// Load system settings from DB into config array (e.g. timezone, date format)
$hook['pre_controller'][] = [
    'class'    => '',
    'function' => 'load_system_settings_into_config',
    'filename' => 'system_settings_loader.php',
    'filepath' => 'hooks',
];

// Global initialization utility (optional)
$hook['pre_controller_constructor'][] = [
    'class'    => '',
    'function' => '_app_init',
    'filename' => 'InitHook.php',
    'filepath' => 'hooks',
];


/**
 * ────────────────────────────────────────────────────────────────
 * POST-CONTROLLER-CONSTRUCTOR HOOKS (after controller object created)
 * Safe point to load config, permissions, and UI components
 * ────────────────────────────────────────────────────────────────
 */

// Dynamically load sidebar menu items
if (!function_exists('load_menu_initializer')) {
    function load_menu_initializer()
    {
        require_once APPPATH . 'helpers/menu_helper.php';
        $CI = &get_instance();
        $CI->load->helper('url');
        require_once APPPATH . 'config/menu_initializer.php';
    }
}
$hook['post_controller_constructor'][] = [
    'class'    => '',
    'function' => 'load_menu_initializer',
    'filename' => '',
    'filepath' => '',
];

// Load company settings from database (after controller is available)
$hook['post_controller_constructor'][] = [
    'class'    => 'CompanySettingsLoader',
    'function' => 'initialize',
    'filename' => 'CompanySettingsLoader.php',
    'filepath' => 'hooks',
    'params'   => [],
];

// Role-based permission system
$hook['post_controller_constructor'][] = [
    'class'    => 'PermissionHook',
    'function' => 'check',
    'filename' => 'PermissionHook.php',
    'filepath' => 'hooks'
];

// Notifications (in-app or real-time)
$hook['post_controller_constructor'][] = [
    'class'    => 'NotificationHook',
    'function' => 'init',
    'filename' => 'NotificationHook.php',
    'filepath' => 'hooks'
];


/**
 * ────────────────────────────────────────────────────────────────
 * CUSTOM HOOK EXTENSIONS
 * Load `my_hooks.php` if available (user-defined)
 * ────────────────────────────────────────────────────────────────
 */
if (file_exists(APPPATH . 'config/my_hooks.php')) {
    include_once APPPATH . 'config/my_hooks.php';
}




// ─────────────────────────────────────────────────────────────
// 🛟 Header "Apps & Shortcuts" — Support tile (app-grid style)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_shortcut_icons_raw', function ($items) {
    if (!function_exists('staff_can')) return $items;
    if (!staff_can('view_global', 'support') && !staff_can('view_own', 'support')) return $items;

    $href  = base_url('support');
    $label = 'Support';
    $icon  = 'ti ti-headset';

    // Optional badge (e.g., open tickets). Set to null to hide.
    $open = (int) ($GLOBALS['support_open_count'] ?? 0);
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
// ⏰ Cron: Auto-close resolved tickets after N days
// (You will implement the model method later. Registered here.)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('cron_tasks', function ($tasks) {
    $tasks[] = [
        'slug'        => 'support:auto_close',
        'description' => 'Auto-close resolved tickets after configured days',
        'schedule'    => '0 3 * * *', // daily at 03:00
        'source'      => 'module',
        'module_name' => SUPPORT_MODULE_NAME,
        'callback'    => 'model:support/Support_tickets_model@cron_auto_close',
        'enabled'     => 1,
    ];
    return $tasks;
});