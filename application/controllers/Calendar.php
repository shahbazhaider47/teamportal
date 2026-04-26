<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calendar extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Events_model', 'Calendar_model', 'Settings_model']);
        $this->load->library('form_validation');
    }

    public function index()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }

        $birthday_upcoming = $this->Calendar_model->get_birthdays_in_range(
            date('Y-m-d'),
            date('Y-m-d', strtotime('+15 days'))
        );
    
        // 1. Get calendar settings from settings model
        $calendar_settings = $this->Settings_model->get_group('calendar');
        $default_draggable = [];
        if (!empty($calendar_settings['draggable_events'])) {
            $decoded = json_decode($calendar_settings['draggable_events'], true);
            if (is_array($decoded)) {
                $default_draggable = $decoded;
            }
        }
    
        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d', strtotime('+30 days'));
    
        // 2. Get core events for "upcoming events" in sidebar
        $events = $this->Calendar_model->get_events($start_date, $end_date, $this->session->userdata('user_id'));
    
        // 3. Add public holidays for US and Pakistan (auto-fetch)
        $year = date('Y');
        $us_holidays = get_public_holidays('US', $year);
        $pk_holidays = get_public_holidays('PK', $year);
        error_log('PK Holidays: ' . print_r($pk_holidays, true));
        
        // Merge holidays into events array
        foreach ($us_holidays as $date => $title) {
            if ($date >= $start_date && $date <= $end_date) {
                $events[] = [
                    'id'          => 'holiday_us_' . $date,
                    'title'       => $title,
                    'start'       => $date,
                    'end'         => $date,
                    'classNames'  => ['event-us'],
                    'allDay'      => true,
                    'description' => $title . ' (US)',
                    'extendedProps' => [
                        'is_public_holiday' => true,
                        'country' => 'US'
                    ]
                ];
            }
        }
        foreach ($pk_holidays as $date => $title) {
            if ($date >= $start_date && $date <= $end_date) {
                $events[] = [
                    'id'          => 'holiday_pk_' . $date,
                    'title'       => $title,
                    'start'       => $date,
                    'end'         => $date,
                    'classNames'  => ['event-pk'],
                    'allDay'      => true,
                    'description' => $title . ' (PK)',
                    'extendedProps' => [
                        'is_public_holiday' => true,
                        'country' => 'PK'
                    ]
                ];
            }
        }
    
        // 4. Prepare data for view
        $layout_data = [
            'page_title' => 'Calendar',
            'subview'    => 'calendar/index',
            'view_data'  => [
                'page_title'        => 'Calendar',
                'events'            => $events,
                'event_colors'      => $this->get_event_colors(),
                'default_draggable' => $default_draggable,
                'date_format'       => $system_settings['date_format'] ?? 'Y-m-d',
                'time_format'       => $system_settings['time_format'] ?? 'H:i',
                'birthday_upcoming' => $birthday_upcoming,
            ],
            
            'scripts' => [
                'assets/vendor/full-calendar/global.js?v=' . @filemtime(FCPATH . 'assets/vendor/full-calendar/global.js'),
                'assets/js/calendar.js?v=' . @filemtime(FCPATH . 'assets/js/calendar.js'),
            ]

        ];
    
        $this->load->view('layouts/master', $layout_data);
    }



public function get_events()
{
    header('Content-Type: application/json');

    $start = $this->input->get('start') ?: date('Y-m-d');
    $end   = $this->input->get('end') ?: date('Y-m-d', strtotime('+30 days'));
    $user_id = $this->session->userdata('user_id');
    $core_events = $this->Calendar_model->get_events($start, $end, $user_id);

    // Format core events for the calendar
    $formatted = [];
    foreach ($core_events as $event) {
        $formatted[] = $this->format_calendar_event($event);
    }

    // ---------- AUTO PUBLIC HOLIDAYS BLOCK ----------
    $year = date('Y', strtotime($start));
    $us_holidays = get_public_holidays('US', $year);
    $pk_holidays = get_public_holidays('PK', $year);
    error_log('PK Holidays: ' . print_r($pk_holidays, true));

    $holiday_events = [];
    foreach ($us_holidays as $date => $title) {
        if ($date >= $start && $date <= $end) {
            $holiday_events[] = [
                'id' => 'holiday_us_' . $date,
                'title' => $title,
                'start' => $date,
                'end' => $date,
                'classNames' => ['event-us'],
                'allDay' => true,
                'description' => $title . ' (US)',
                'extendedProps' => [
                    'is_public_holiday' => true,
                    'country' => 'US'
                ]
            ];
        }
    }
    foreach ($pk_holidays as $date => $title) {
        if ($date >= $start && $date <= $end) {
            $holiday_events[] = [
                'id' => 'holiday_pk_' . $date,
                'title' => $title,
                'start' => $date,
                'end' => $date,
                'classNames' => ['event-pk'],
                'allDay' => true,
                'description' => $title . ' (PK)',
                'extendedProps' => [
                    'is_public_holiday' => true,
                    'country' => 'PK'
                ]
            ];
        }
    }

    // Merge holidays with regular events
    $formatted = array_merge($formatted, $holiday_events);
    // ---------- END HOLIDAY BLOCK ----------
    // ---------- USER-BASED PUBLIC HOLIDAYS ----------
    $user_holidays = get_user_public_holidays($user_id, $start, $end);
    $formatted = array_merge($formatted, $user_holidays);
    // ---------- END USER HOLIDAYS ----------
    // ---------- BIRTHDAYS (READ-ONLY) ----------
    $birthday_start = date('Y-m-d', strtotime('-5 days'));
    $birthday_end   = date('Y-m-d', strtotime('+15 days'));
    
    $birthdays = $this->Calendar_model->get_birthdays_in_range(
        $birthday_start,
        $birthday_end
    );
    
    foreach ($birthdays as $b) {
        $formatted[] = [
            'id'    => 'birthday_' . $b['id'],
            'title' => '🎂 ' . $b['name'] . '\'s Birthday',
            'start' => $b['birthday'],
            'end'   => $b['birthday'],
            'allDay' => true,
            'classNames' => ['event-birthday'],
            'extendedProps' => [
                'type'        => 'birthday',
                'readOnly'    => true,
                'is_birthday' => true
            ]
        ];
    }
    // ---------- END BIRTHDAYS ----------

    // Modular events: allow modules to inject additional events
    $args = [
        'start'   => $start,
        'end'     => $end,
        'user_id' => $user_id,
    ];

    $all_events = hooks()->apply_filters('calendar_events', $formatted, $args);
    echo json_encode($all_events);
    exit;
}



    public function add_event()
    {

        if (!staff_can('add', 'calendar')) {
            echo json_encode(['status'=>'error','message'=>'Permission denied']);
            return;
        }        
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->session->userdata('is_logged_in')) {
            set_alert('danger', 'Unauthorized access');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
    
        $this->form_validation->set_rules('title', 'Title', 'required|max_length[100]');
        $this->form_validation->set_rules('start', 'Start Date', 'required');
    
        if ($this->form_validation->run() === FALSE) {
            set_alert('danger', strip_tags(validation_errors()));
            echo json_encode(['status' => 'error', 'message' => strip_tags(validation_errors())]);
            return;
        }
    
        // Prepare event data
        $data = [
            'title'       => $this->input->post('title', true),
            'description' => $this->input->post('description', true),
            'start'       => $this->input->post('start', true),
            'end'         => $this->input->post('end', true) ?: null,
            'className'   => $this->input->post('className', true) ?: 'event-primary',
            'created_by'  => $this->session->userdata('user_id'),
            'is_private'  => $this->input->post('is_private') ? 1 : 0,
            'created_at'  => date('Y-m-d H:i:s')
        ];
    
        $event_id = $this->Events_model->add_event($data);
    
        if ($event_id) {
            $event = $this->Events_model->get_event($event_id);
            set_alert('success', 'Event added successfully');
            echo json_encode([
                'status' => 'success',
                'message' => 'Event added successfully',
                'event'  => $this->format_calendar_event($event)
            ]);
        } else {
            set_alert('danger', 'Failed to add event');
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add event'
            ]);
        }
    }

    public function update_event()
    {

        if (!staff_can('edit', 'calendar')) {
            echo json_encode(['status'=>'error','message'=>'Permission denied']);
            return;
        }
        
        if (!$this->input->is_ajax_request()) show_404();
    
        if (!$this->session->userdata('is_logged_in')) {
            set_alert('danger', 'Unauthorized access');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
    
        $id = $this->input->post('id');
        if (!$id) {
            set_alert('danger', 'Invalid event');
            echo json_encode(['status' => 'error', 'message' => 'Invalid event']);
            return;
        }
    
        $event = $this->Events_model->get_event($id);
        if (!$event || ($event['created_by'] != $this->session->userdata('user_id') && !staff_can('edit', 'calendar'))) {
            set_alert('danger', 'Unauthorized to edit this event');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized to edit this event']);
            return;
        }
    
        $data = [
            'title'       => $this->input->post('title', true),
            'description' => $this->input->post('description', true),
            'start'       => $this->input->post('start', true),
            'end'         => $this->input->post('end', true) ?: null,
            'className'   => $this->input->post('className', true) ?: 'event-primary',
            'is_private'  => $this->input->post('is_private') ? 1 : 0
        ];
    
        $result = $this->Events_model->update_event($id, $data);
    
        if ($result) {
            set_alert('success', 'Event updated successfully');
            echo json_encode([
                'status'  => 'success',
                'message' => 'Event updated successfully'
            ]);
        } else {
            set_alert('danger', 'Failed to update event');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to update event'
            ]);
        }
    }
    
    public function delete_event()
    {

        if (!staff_can('delete', 'calendar')) {
            echo json_encode(['status'=>'error','message'=>'Permission denied']);
            return;
        }
        
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->session->userdata('is_logged_in')) {
            set_alert('danger', 'Unauthorized access');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
    
        $id = $this->input->post('id');
        if (!$id) {
            set_alert('danger', 'Invalid event');
            echo json_encode(['status' => 'error', 'message' => 'Invalid event']);
            return;
        }
    
        $result = $this->Events_model->delete_event($id);
        if ($result) {
            set_alert('success', 'Event deleted successfully');
            echo json_encode([
                'status'  => 'success',
                'message' => 'Event deleted successfully'
            ]);
        } else {
            set_alert('danger', 'Failed to delete event');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to delete event'
            ]);
        }
    }

    private function format_calendar_event($event)
    {
        // If it's already a module event with proper format, return as is
        if (isset($event['id'], $event['title'], $event['start'])) {
            // Handle both core and module events
            return [
                'id'          => $event['id'],
                'title'       => $event['title'],
                'start'       => $event['start'],
                'end'         => $event['end'] ?? null,
                'classNames'  => [$event['className'] ?? 'event-primary'], // Always send as array!
                'allDay'      => $event['allDay'] ?? false,
                'description' => $event['description'] ?? ($event['extendedProps']['description'] ?? ''),
                'extendedProps' => [
                    'created_by' => $event['created_by'] ?? null,
                    'is_private' => isset($event['is_private']) ? (bool)$event['is_private'] : false,
                ],
            ];
        }
        return [];
    }

    private function get_event_colors()
    {
        return [
            'event-primary'   => 'Primary',
            'event-success'   => 'Green',
            'event-warning'   => 'Yellow',
            'event-info'      => 'Cyan',
            'event-danger'    => 'Red',
            'event-secondary' => 'Gray',
            'event-dark'      => 'Dark'
        ];
    }
}
