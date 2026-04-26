<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ------------------------------------------------------------
 * Install Guard (EARLY BOOT)
 * ------------------------------------------------------------
 * - Blocks app if installer exists after installation
 * - Detects incomplete install
 * - Renders static HTML views safely (no CI loader)
 */

$installDirPath   = realpath(APPPATH . '../install');
$installDirExists = ($installDirPath && is_dir($installDirPath));

$lockFileConfig   = APPPATH . 'config/installed.lock';
$appConfigPath    = APPPATH . 'config/app-config.php';

function render_static_view(string $viewPath, array $data = [])
{
    extract($data, EXTR_SKIP);
    ob_start();
    require $viewPath;
    return ob_get_clean();
}

/*
|--------------------------------------------------------------------------
| SECURITY: Installed BUT installer still exists
|--------------------------------------------------------------------------
*/
if (is_file($lockFileConfig) && $installDirExists) {

    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');

    echo render_static_view(
        APPPATH . 'views/errors/html/installer_present.php',
        [
            'installDirPath' => $installDirPath,
        ]
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Determine install state
|--------------------------------------------------------------------------
*/
$needsInstall = true;
$appMeta     = [];

if (is_file($appConfigPath)) {

    $appMeta = (function ($path) {
        $config = [];
        include $path;
        return $config;
    })($appConfigPath);

    $db = $appMeta['db'] ?? null;

    $dbReady = is_array($db)
        && !empty($db['hostname'])
        && !empty($db['database'])
        && isset($db['username']) && $db['username'] !== '';

    $lockExists = is_file($lockFileConfig);

    $needsInstall = !($dbReady && $lockExists);
}

/*
|--------------------------------------------------------------------------
| NOT INSTALLED → show installer splash
|--------------------------------------------------------------------------
*/
if ($needsInstall) {

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );

    $scheme = $isHttps ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $base   = $base ? $base.'/' : '/';

    $installUrl = $scheme . '://' . $host . $base . 'install/index.php?step=1';

    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');

    echo render_static_view(
        APPPATH . 'views/errors/html/app_not_installed.php',
        [
            'installUrl'       => $installUrl,
            'installDirExists' => $installDirExists,
        ]
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| EXPOSE BASE URL RESOLUTION TO config.php
|--------------------------------------------------------------------------
*/
$GLOBALS['__APP_META'] = $appMeta;
