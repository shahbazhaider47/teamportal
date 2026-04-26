<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PermissionHook
 *
 * Runs after every controller constructor to enforce
 * your permissions_map without sprinkling checks in each method.
 */
class PermissionHook
{
    public function check()
    {
        $CI =& get_instance();
        $ctrl   = $CI->router->fetch_class();
        $method = $CI->router->fetch_method();
        $CI->config->load('permissions', TRUE);
        $map = $CI->config->item('permissions_map', 'permissions');
        if (isset($map[$ctrl][$method])) {
            $action = $map[$ctrl][$method];
            $module = strtolower($ctrl);
            if (! staff_can($action, $module)) {
                show_error('Access Denied', 403);
                exit;
            }
        }
    }
}
