<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Minimal PSR-4 autoloader for Pusher + Guzzle + PSR packages.
 * Keep folder names and case EXACTLY as below.
 */
spl_autoload_register(function ($class) {
    // Map of namespace prefixes to base directories
    static $map = [
        'Pusher\\'               => APPPATH . 'vendor/pusher/pusher-php-server/src/',
        'Psr\\Log\\'             => APPPATH . 'vendor/psr/log/src/',
        'Psr\\Http\\Message\\'   => APPPATH . 'vendor/psr/http-message/src/',
        'Psr\\Http\\Client\\'    => APPPATH . 'vendor/psr/http-client/src/',
        'GuzzleHttp\\'           => APPPATH . 'vendor/guzzlehttp/guzzle/src/',
        'GuzzleHttp\\Promise\\'  => APPPATH . 'vendor/guzzlehttp/promises/src/',
        'GuzzleHttp\\Psr7\\'     => APPPATH . 'vendor/guzzlehttp/psr7/src/',
        'ralouphie\\getallheaders\\' => APPPATH . 'vendor/ralouphie/getallheaders/src/',
    ];

    foreach ($map as $prefix => $baseDir) {
        if (strpos($class, $prefix) === 0) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
});
