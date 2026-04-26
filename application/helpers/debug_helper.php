<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('dd')) {
    function dd($var, $exit = true)
    {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        if ($exit) {
            exit;
        }
    }
}

if (!function_exists('dlog')) {
    function dlog($var)
    {
        log_message('debug', print_r($var, true));
    }
}
