<?php
// File: application/controllers/Dashboard.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('dashboard');
        $this->load->model('Dashboard_model');
        $this->load->helper('text');
    }

    /**
     * Main dashboard page.
     */
    public function index()
    {
        // 1) Check login
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        // 2) User context
        $user_id = $this->session->userdata('user_id');
        $role    = strtolower($this->session->userdata('user_role'));
    
        // 3) Deferred activity log from login
        $action = $this->session->flashdata('log_action');
        if ($action) {
            $this->load->model('Activity_log_model');
            $this->Activity_log_model->add([
                'user_id'    => $user_id,
                'action'     => $action,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    
        // Register the widget for 'middle-col-1'
        if (function_exists('register_dashboard_widget')) {
        register_dashboard_widget('row3-col-1', 'calendar_widget', 'dashboard/widgets/calendar_widget', 0);
        register_dashboard_widget('row3-col-2', 'announcements_widget', 'dashboard/widgets/announcements_widget', 0);
        register_dashboard_widget('row3-col-2', 'birthdays_widget', 'dashboard/widgets/birthdays_widget');
        register_dashboard_widget('row3-col-2', 'todo_widget', 'dashboard/widgets/todo_widget');
        register_dashboard_widget('top-col-4', 'team_widget', 'dashboard/widgets/team_widget');
        register_dashboard_widget('row3-col-1', 'team_widget', 'dashboard/widgets/my_open_tickets');
        
        // Widgets for admin user
        // Admin top row widgets
        register_dashboard_widget('admin-top-col-1', 'headcount_widget', 'dashboard/widgets/headcount_widget');
        //register_dashboard_widget('admin-top-col-2', 'Empty', 'dashboard/widgets/Empty');
        register_dashboard_widget('admin-top-col-0', 'hr_kpi_strip', 'dashboard/widgets/hr_kpi_strip');
        //register_dashboard_widget('admin-top-col-4', 'Empty', 'dashboard/widgets/Empty');
        
        register_dashboard_widget('admin-row3-col-1', 'calendar_widget', 'dashboard/widgets/calendar_widget', 0);
        register_dashboard_widget('admin-row3-col-2', 'announcements_widget', 'dashboard/widgets/announcements_widget', 0);
        register_dashboard_widget('admin-row3-col-2', 'birthdays_widget', 'dashboard/widgets/birthdays_widget');
        register_dashboard_widget('admin-row3-col-2', 'todo_widget', 'dashboard/widgets/todo_widget');
    
        }
    
        // 4) Load the dashboard helper for widget functions
        $this->load->helper('dashboard_helper');
    
        // 5) Let modules/widgets register themselves (do this BEFORE loading the view)
        hooks()->do_action('dashboard_widgets_init');
    
        // 6) Load all the data needed by widgets or page
        $this->load->model('Announcement_model');
        $announcements = $this->Announcement_model->get_visible_announcements($user_id, $role);
    
        $this->load->model('User_model');
        $upcoming_birthdays = $this->User_model->get_upcoming_birthdays(14);
    
        $this->load->model('Todo_model');
        $todos = $this->Todo_model->get_visible_todos($user_id);
    
        // 7) Prepare view data (shared)
        $view_data = [
            'user_id'            => $user_id,
            'widget_order'       => [], // If you are using ordering, fill here
            'widget_visibility'  => [], // If you are using visibility, fill here
            'announcements'      => $announcements,
            'todos'              => $todos,
            'upcoming_birthdays' => $upcoming_birthdays,
        ];
    
        // 8) Add dashboard-specific assets
        add_style('assets/vendor/apexcharts/apexcharts.css');
        add_script('assets/vendor/apexcharts/apexcharts.min.js');
    
        // 9) Decide which dashboard view to render
        $subview    = ($role === 'superadmin') ? 'dashboard/superadmin_dashboard' : 'dashboard/index';
        $page_title = ($role === 'superadmin') ? 'Dashboard' : 'My Dashboard';
    
        // 10) Render the dashboard in the master layout
        $layout_data = [
            'page_title' => $page_title,
            'subview'    => $subview,
            'view_data'  => $view_data,
            'styles'     => get_styles(),
            'scripts'    => get_scripts(),
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }


    /**
     * AJAX: Save a container’s new widget order.
     * Expects JSON POST body { container_id: "...", widget_ids: ["md5hash1","md5hash2",...] }.
     */
    public function save_order()
    {
        if (! $this->input->is_ajax_request()) {
            show_error('No direct script access allowed');
        }
        $user_id = (int) $this->session->userdata('user_id');
        $payload = json_decode($this->input->raw_input_stream, true);
        if (empty($payload['container_id']) || ! is_array($payload['widget_ids'])) {
            $this->output
                 ->set_status_header(400)
                 ->set_output(json_encode(['error' => 'Invalid payload']));
            return;
        }

        $container_id = $payload['container_id'];
        $widget_ids   = array_map('html_escape', $payload['widget_ids']);
        $this->Dashboard_model->save_container_order($user_id, $container_id, $widget_ids);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true]));
    }

    /**
     * AJAX: Save updated visibility for one or more widgets.
     * Expects JSON POST body { visibility: { "md5hash1":0 or 1, ... } }.
     */
    public function save_visibility()
    {
        if (! $this->input->is_ajax_request()) {
            show_error('No direct script access allowed');
        }
        $user_id = (int) $this->session->userdata('user_id');
        $payload = json_decode($this->input->raw_input_stream, true);
        if (empty($payload['visibility']) || ! is_array($payload['visibility'])) {
            $this->output
                 ->set_status_header(400)
                 ->set_output(json_encode(['error' => 'Invalid payload']));
            return;
        }

        $visibility_map = [];
        foreach ($payload['visibility'] as $wid => $flag) {
            $visibility_map[$wid] = (bool) $flag;
        }
        $this->Dashboard_model->save_visibility_map($user_id, $visibility_map);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true]));
    }

    


    public function calendar_widget_data()
    {
        // Permissions: Only for logged-in users
        if (!$this->session->userdata('is_logged_in')) {
            show_error('Unauthorized', 401);
        }
    
        $this->load->model(['Calendar_model', 'Settings_model']);
        $user_id = $this->session->userdata('user_id');
    
        // Get events for the next 30 days for THIS user
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+30 days'));
        $events = $this->Calendar_model->get_events($start_date, $end_date, $user_id);
    
        // Calendar settings (optionally from system_settings table)
        $calendar_settings = $this->Settings_model->get_group('calendar');
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
    
        // Default draggable events if present
        $default_draggable = [];
        if (!empty($calendar_settings['draggable_events'])) {
            $decoded = json_decode($calendar_settings['draggable_events'], true);
            if (is_array($decoded)) {
                $default_draggable = $decoded;
            }
        }
    
        // Prepare as array
        $data = [
            'events'            => $events,
            'event_colors'      => $event_colors,
            'default_draggable' => $default_draggable,
            'date_format'       => $date_format,
            'time_format'       => $time_format,
        ];
    
        // Return as view variables
        return $data;
    }
    
}
