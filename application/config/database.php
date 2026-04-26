<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database configuration for CodeIgniter, aligned with the installer.
 *
 * Source of truth:
 *   application/config/app-config.php
 *     - $config['db']          : array of DB settings
 *     - $config['app_base_url']: optional base URL (handled in config.php)
 *
 * This file:
 *   - Loads app-config.php in isolation (no global $config pollution)
 *   - Builds $db['default'] for CI's database loader
 *   - Avoids hardcoded credentials
 *   - Falls back to the installer if config is missing/incomplete
 */

$active_group   = 'default';
$query_builder  = TRUE;

/** Helper: load app-config.php safely in an isolated scope */
$__app_cfg_file = APPPATH . 'config/app-config.php';
$__meta = [];
if (is_file($__app_cfg_file)) {
    $__meta = (function ($path) {
        $config = [];
        include $path;   // app-config.php defines $config['db'], etc.
        return $config;
    })($__app_cfg_file);
}

// Extract DB meta (if present)
$__db = $__meta['db'] ?? null;

// Minimal readiness check (we do not connect here)
$__db_ready = is_array($__db)
    && !empty($__db['hostname'])
    && !empty($__db['database'])
    && (isset($__db['username']) && $__db['username'] !== '');

// If not ready, bounce to installer (defensive; config.php already gates earlier)
if (!$__db_ready) {
    // Build installer URL that respects subfolders and proxies
    $is_https = (
        (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
    $scheme     = $is_https ? 'https' : 'http';
    $host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script     = $_SERVER['SCRIPT_NAME'] ?? '';
    $base_path  = rtrim(str_replace('\\','/', dirname($script)), '/');
    $base_path  = $base_path ? $base_path.'/' : '/';
    $installUrl = $scheme . '://' . $host . $base_path . 'install/?step=1';

    // Use a header redirect if possible; otherwise a minimal message
    if (!headers_sent()) {
        header('Location: ' . $installUrl, true, 302);
    }
    exit('Database not configured. Please run the installer: ' . htmlspecialchars($installUrl, ENT_QUOTES, 'UTF-8'));
}

// Map to CI's $db['default']
$db['default'] = [
    'dsn'       => '',
    'hostname'  => (string)$__db['hostname'],
    'username'  => (string)$__db['username'],
    'password'  => (string)($__db['password'] ?? ''),
    'database'  => (string)$__db['database'],
    'dbdriver'  => isset($__db['dbdriver']) ? (string)$__db['dbdriver'] : 'mysqli',
    'dbprefix'  => (string)($__db['dbprefix'] ?? ''),
    'pconnect'  => (bool)  ($__db['pconnect'] ?? FALSE),
    'db_debug'  => defined('ENVIRONMENT') ? (ENVIRONMENT !== 'production') : TRUE,
    'cache_on'  => (bool)  ($__db['cache_on'] ?? FALSE),
    'cachedir'  => (string)($__db['cachedir'] ?? ''),
    // Force safe modern defaults unless explicitly provided
    'char_set'  => (string)($__db['char_set'] ?? 'utf8mb4'),
    'dbcollat'  => (string)($__db['dbcollat'] ?? 'utf8mb4_unicode_ci'),
    'swap_pre'  => (string)($__db['swap_pre'] ?? ''),
    'encrypt'   => (bool)  ($__db['encrypt'] ?? FALSE),
    'compress'  => (bool)  ($__db['compress'] ?? FALSE),
    'stricton'  => (bool)  ($__db['stricton'] ?? FALSE),
    'failover'  => (array) ($__db['failover'] ?? []),
    'save_queries' => (bool)($__db['save_queries'] ?? TRUE),
];

/**
 * NOTE:
 *   - Do NOT put session settings here; keep them in application/config/config.php.
 *   - app-config.php is created by the installer (Step 4).
 *   - installed.lock is enforced in application/config/config.php (early gate).
 */
