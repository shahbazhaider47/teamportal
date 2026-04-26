<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (! function_exists('render_dashboard_area')) {

    function render_dashboard_area($container_id, array $widget_list = [], array $visibility_map = [])
    {
        $output = "<div class=\"row g-4\" data-container=\"" . html_escape($container_id) . "\">\n";

        foreach ($widget_list as $widget_id) {
            if (isset($visibility_map[$widget_id]) && $visibility_map[$widget_id] === false) {
                continue;
            }

            $widget_name = '';
            if ($container_id === 'top-left' && $widget_id === Dashboard_model::generate_widget_id('sample_widget', 'top-left')) {
                $widget_name = 'sample_widget';
            }

            if ($widget_name === '') {
                continue;
            }

            $widget_file = FCPATH . 'application/views/dashboard/widgets/' . $widget_name . '.php';

            if (is_file($widget_file)) {
                ob_start();
                include($widget_file);
                $widget_html = ob_get_clean();

                $output .= '<div class="widget mb-3" id="widget-' . html_escape($widget_id) . "\">\n"
                         .   $widget_html
                         .  "\n</div>\n";
            }
        }

        $output .= "</div>\n";
        return $output;
    }
}

if (!function_exists('render_dashboard_widgets')) {
    function render_dashboard_widgets($container)
    {
        $CI = &get_instance();
        $has_widget = false;

        if (function_exists('hooks')) {
            ob_start();
            hooks()->do_action('app_dashboard_widgets', $container);
            $hook_output = ob_get_clean();
            if (trim($hook_output)) {
                echo $hook_output;
                $has_widget = true;
            }
        }

        if (isset($CI->dashboard_widgets) && is_array($CI->dashboard_widgets) && !empty($CI->dashboard_widgets[$container])) {
            foreach ($CI->dashboard_widgets[$container] as $widget_view) {
                $CI->load->view($widget_view);
                $has_widget = true;
            }
        }

    }
}

if (!function_exists('register_dashboard_widget')) {
    function register_dashboard_widget($container, $widget_key, $view_path, $position = null) {
        $CI = &get_instance();
        if (!isset($CI->dashboard_widgets[$container]) || !is_array($CI->dashboard_widgets[$container])) {
            $CI->dashboard_widgets[$container] = [];
        }
        $widgets = $CI->dashboard_widgets[$container];

        if (isset($widgets[$widget_key])) {
            unset($widgets[$widget_key]);
        }

        if ($position === null || $position >= count($widgets) || $position < 0) {
            $widgets[$widget_key] = $view_path;
        } else {
            $widgets = array_slice($widgets, 0, $position, true)
                     + [$widget_key => $view_path]
                     + array_slice($widgets, $position, null, true);
        }

        $CI->dashboard_widgets[$container] = $widgets;
    }
}

if (!function_exists('get_dashboard_calendar_data')) {
    function get_dashboard_calendar_data()
    {
        $CI =& get_instance();
        $CI->load->model(['Calendar_model', 'Settings_model']);
        $user_id = $CI->session->userdata('user_id');

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+30 days'));
        $events = $CI->Calendar_model->get_events($start_date, $end_date, $user_id);

        $calendar_settings = $CI->Settings_model->get_group('calendar');
        $event_colors = [
            'event-primary'   => 'Primary',
            'event-success'   => 'Green',
            'event-warning'   => 'Yellow',
            'event-info'      => 'Cyan',
            'event-danger'    => 'Red',
            'event-secondary' => 'Gray',
            'event-dark'      => 'Dark'
        ];
        $date_format = $calendar_settings['date_format'] ?? 'Y-m-d';
        $time_format = $calendar_settings['time_format'] ?? 'H:i';

        return [
            'events'            => $events,
            'event_colors'      => $event_colors,
            'date_format'       => $date_format,
            'time_format'       => $time_format,
        ];
    }
}
