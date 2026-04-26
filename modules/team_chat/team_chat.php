<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Team Chat
Description: Real-time internal messaging for your team. Supports direct messages, group conversations, and team channels with file sharing, emoji reactions, threading, and @mentions.
Version: 1.0.0
Author: RCM Centric
Author URI: https://rcmcentric.com
Requires at least: 3.3.*
Requires Modules:
Settings Icon: ti ti-message-circle
Settings Name: Team Chat
*/

// ─────────────────────────────────────────────────────────────
// 🔁 Define Constants
// ─────────────────────────────────────────────────────────────
define('TEAM_CHAT_MODULE_NAME', 'team_chat');
define('TEAM_CHAT_MODULE_PATH', module_dir_path(TEAM_CHAT_MODULE_NAME));
define('TEAM_CHAT_MODULE_URL',  module_dir_url(TEAM_CHAT_MODULE_NAME));

// ─────────────────────────────────────────────────────────────
// 📦 Register Lifecycle Hooks
// ─────────────────────────────────────────────────────────────
register_activation_hook(TEAM_CHAT_MODULE_NAME,   'team_chat_module_activate');
register_deactivation_hook(TEAM_CHAT_MODULE_NAME, 'team_chat_module_deactivate');
register_uninstall_hook(TEAM_CHAT_MODULE_NAME,    'team_chat_module_uninstall');

// ─────────────────────────────────────────────────────────────
// 🌐 Register Language Files
// ─────────────────────────────────────────────────────────────
$CI   = &get_instance();
$lang = $CI->config->item('language') ?? 'english';

if (file_exists(TEAM_CHAT_MODULE_PATH . 'language/' . $lang . '/team_chat_lang.php')) {
    $CI->lang->load(TEAM_CHAT_MODULE_NAME . '/team_chat', $lang);
} else {
    $CI->lang->load(TEAM_CHAT_MODULE_NAME . '/team_chat', 'english');
}

register_language_files(TEAM_CHAT_MODULE_NAME, ['team_chat']);


// ─────────────────────────────────────────────────────────────
// 🧭 Sidebar Menu Items
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_sidebar_menu', 'team_chat_module_sidebar_menu');

function team_chat_module_sidebar_menu($menus)
{
    if (staff_can('access', 'team_chat')) {
        $menus[] = [
            'slug'     => 'team_chat',
            'name'     => 'Team Chat',
            'icon'     => 'ti ti-message-circle',
            'href'     => site_url('team_chat'),
            'position' => 5,
        ];
    }

    return $menus;
}

// ─────────────────────────────────────────────────────────────
// 🔗 Module Page Action Link
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('module_' . TEAM_CHAT_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . site_url('team_chat/settings') . '">Settings</a>';
    return $actions;
});

// ─────────────────────────────────────────────────────────────
// ✅ Activation / Deactivation / Uninstall
// ─────────────────────────────────────────────────────────────
function team_chat_module_activate()
{
    $CI = &get_instance();

    try {
        include_once(TEAM_CHAT_MODULE_PATH . 'install.php');
    } catch (Exception $e) {
        throw $e;
    }
}

function team_chat_module_deactivate()
{
    log_message('debug', '⚙️ Team Chat module deactivated.');
}

function team_chat_module_uninstall()
{
    $CI = &get_instance();

    try {
        include_once(TEAM_CHAT_MODULE_PATH . 'uninstall.php');
    } catch (Exception $e) {
        log_message('error', '❌ Team Chat uninstall failed: ' . $e->getMessage());
        throw $e;
    }
}

// ─────────────────────────────────────────────────────────────
// 🔐 Permissions
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('user_permissions', 'team_chat_permissions');

function team_chat_permissions($permissions)
{
    $permissions['team_chat'] = [
        'name'    => 'Team Chat',
        'actions' => [
            'access'         => 'Access Chat',
            'create_channel' => 'Create Channels',
            'manage_channel' => 'Manage Channels',
            'delete_message' => 'Delete Any Message',
            'view_all'       => 'View All Conversations',
        ],
    ];

    return $permissions;
}