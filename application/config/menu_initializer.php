<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Register core menu items (e.g., dashboard, settings)
register_core_menu_items();

// Safely trigger additional menu registrations via hooks
if (function_exists('hooks') && hooks() && method_exists(hooks(), 'do_action')) {
    hooks()->do_action('app_menu');
}
