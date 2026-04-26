<?php

spl_autoload_register(function ($class) {
    // Normalize backslashes
    $class = ltrim($class, '\\');

    // PSR-4 base directories
    $prefixes = [
        'Psr\\Http\\Client\\' => __DIR__ . '/psr/http-client/src/',
        'Psr\\Http\\Message\\' => __DIR__ . '/psr/http-message/src/',
        'Psr\\Log\\'           => __DIR__ . '/psr/log/',
        'GuzzleHttp\\'         => __DIR__ . '/guzzlehttp/guzzle/src/',
        'GuzzleHttp\\Promise\\'=> __DIR__ . '/guzzlehttp/promises/src/',
        'GuzzleHttp\\Psr7\\'   => __DIR__ . '/guzzlehttp/psr7/src/',
        'Pusher\\'             => __DIR__ . '/pusher/pusher-php-server/src/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
            return true;
        }
    }

    return false;
});
