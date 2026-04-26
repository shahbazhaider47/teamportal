<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Bootstrap DB configuration:
 * - Try app-config.php (written by installer)
 * - Else use environment variables
 * - Else fall back to safe defaults (empty strings)
 *
 * IMPORTANT: No output/echo here. Keep this file BOM-free.
 */

// 1) Optional, written by installer later. Guard it.
$__app_cfg = __DIR__ . '/app-config.php';
if (is_file($__app_cfg)) {
    include_once $__app_cfg;
}

/**
 * Small helper: read from env first, then constant (if installer defined it), else default.
 */
if (!function_exists('__env_or_const')) {
    function __env_or_const(string $envKey, string $constKey, $default = '')
    {
        $v = getenv($envKey);
        if ($v !== false && $v !== '') {
            return $v;
        }
        if (defined($constKey)) {
            return constant($constKey);
        }
        return $default;
    }
}

// Required by CI
$active_group  = 'default';
$query_builder = true;

// Compose the config array using env/const fallback.
$db['default'] = [
    'dsn'      => '',
    'hostname' => __env_or_const('APP_DB_HOSTNAME', 'APP_DB_HOSTNAME', 'localhost'),
    'username' => __env_or_const('APP_DB_USERNAME', 'APP_DB_USERNAME', ''),
    'password' => __env_or_const('APP_DB_PASSWORD', 'APP_DB_PASSWORD', ''),
    'database' => __env_or_const('APP_DB_DATABASE', 'APP_DB_DATABASE', ''),
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => false,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => false,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt'  => false,
    'compress' => false,
    'stricton' => false,
    'failover' => [],
    'save_queries' => true,
    // Optional port/socket support
    'port'     => (int) __env_or_const('APP_DB_PORT', 'APP_DB_PORT', 3306),
    // 'socket' => __env_or_const('APP_DB_SOCKET', 'APP_DB_SOCKET', ''), // if you use a unix socket
];

unset($__app_cfg);
