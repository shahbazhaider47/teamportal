<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Reminders extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('reminders_model');
        $this->load->model('Notification_model');
        $this->load->helper(['date', 'email']);
        $this->load->library('email');
        $this->load->helper('reminder');
    }

    public function index()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
        }
    
        // Check if user has permission to access reminders (global or own)
        $canViewGlobal = staff_can('view_global', 'reminders');
        $canViewOwn    = staff_can('view_own', 'reminders');
    
        if (!$canViewGlobal && !$canViewOwn) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        $user_id = (int)$this->session->userdata('user_id');
    
        $data['title']       = 'Manage Reminders';
        $data['page_title']  = 'Manage Reminders';
    
        // Fetch reminders based on permission
        if ($canViewGlobal) {
            // Admins / managers — view all reminders
            $reminders = $this->reminders_model->get_all();
        } else {
            // Regular users — only their own reminders
            $reminders = $this->reminders_model->get_all_by_user($user_id);
        }
    
        // Calculate summary counts
        $current_date = date('Y-m-d');
        $data['today_count'] = 0;
        $data['upcoming_count'] = 0;
        $data['past_count'] = 0;
    
        foreach ($reminders as $r) {
            $reminder_date = date('Y-m-d', strtotime($r['date']));
            if ($reminder_date == $current_date) {
                $data['today_count']++;
            } elseif ($reminder_date > $current_date) {
                $data['upcoming_count']++;
            } else {
                $data['past_count']++;
            }
        }
    
        $data['reminders'] = $reminders;
        $data['scripts']   = ['modules/reminders/assets/js/reminders.js'];
    
        $this->load->view('layouts/master', [
            'subview'   => 'manage', // reminders/views/manage.php
            'view_data' => $data,
        ]);
    }

    public function add()
    {
        // ✅ Security check
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
        }
    
        // ✅ Form submission check
        if ($this->input->post()) {
            $user_id = $this->session->userdata('user_id');
    
            // ✅ Input sanitization with XSS filtering
            $title = $this->input->post('title', true);
            $description = $this->input->post('description', true);
            $date = $this->input->post('date', true);
            $time = $this->input->post('time', true) ?: '00:00';
            $priority = $this->input->post('priority', true) ?: 'medium';
            $is_recurring = $this->input->post('is_recurring', true) ? 1 : 0;
            $recurring_frequency = $this->input->post('recurring_frequency', true);
            $recurring_duration = $this->input->post('recurring_duration', true);
    
            // ✅ Basic validation
            if (!$title || !$date) {
                set_alert('danger', 'Title and Date are required.');
                redirect('reminders');
            }
    
            // ✅ Add this right below
            if ($is_recurring && (!is_numeric($recurring_duration) || $recurring_duration < 1)) {
                set_alert('danger', 'Invalid recurring duration.');
                redirect('reminders');
            }

            // ✅ Combine date and time
            $datetime = $date . ' ' . $time . ':00';
    
            // ✅ Main insert array
            $insert = [
                'title'                 => $title,
                'description'           => $description,
                'date'                  => $datetime,
                'priority'              => $priority,
                'is_recurring'          => $is_recurring,
                'recurring_frequency'   => $is_recurring ? $recurring_frequency : null,
                'recurring_duration'    => $is_recurring ? $recurring_duration : null,
                'created_by'            => $user_id,
                'created_at'            => date('Y-m-d H:i:s'),
            ];
    
            // ✅ Insert main reminder
            $reminderId = $this->reminders_model->add($insert);
    
            // ✅ Success check
            if ($reminderId) {
    
                // ✅ Recurring processing (JSON only, no duplicate rows)
                if ($is_recurring) {
                    $recurring_dates = generate_recurring_dates($datetime, $recurring_frequency, $recurring_duration);
                    $this->reminders_model->update($reminderId, [
                        'recurring_dates' => json_encode($recurring_dates),
                    ]);
                }
    
                set_alert('success', 'Reminder created successfully.');
            } else {
                set_alert('danger', 'Failed to create reminder.');
            }
        }
    
        // ✅ Fallback redirect
        redirect('reminders');
    }

public function update()
{
    // ✅ Security check
    if (!$this->session->userdata('is_logged_in')) {
        redirect('authentication/login');
    }

    if ($this->input->post()) {
        // ✅ Input sanitization with XSS filtering
        $id                   = (int) $this->input->post('id', true);
        $title                = $this->input->post('title', true);
        $description          = $this->input->post('description', true);
        $date                 = $this->input->post('date', true);
        $time                 = $this->input->post('time', true) ?: '00:00';
        $priority             = $this->input->post('priority', true) ?: 'medium';
        $is_recurring         = $this->input->post('is_recurring', true) ? 1 : 0;
        $recurring_frequency  = $this->input->post('recurring_frequency', true);
        $recurring_duration   = $this->input->post('recurring_duration', true);

        // ✅ Basic validation
        if ($id <= 0) {
            set_alert('danger', 'Invalid reminder.');
            return redirect('reminders');
        }
        if (!$title || !$date) {
            set_alert('danger', 'Title and Date are required.');
            return redirect('reminders');
        }
        if ($is_recurring && (!is_numeric($recurring_duration) || $recurring_duration < 1)) {
            set_alert('danger', 'Invalid recurring duration.');
            return redirect('reminders');
        }

        // ✅ Combine date and time
        $datetime = $date . ' ' . $time . ':00';

        // ✅ Main update array (mirror add())
        $update = [
            'title'                 => $title,
            'description'           => $description,
            'date'                  => $datetime,
            'priority'              => $priority,
            'is_recurring'          => $is_recurring,
            'recurring_frequency'   => $is_recurring ? $recurring_frequency : null,
            'recurring_duration'    => $is_recurring ? $recurring_duration : null,
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        // ✅ Recurring processing (JSON only, no duplicate rows)
        if ($is_recurring) {
            // Assumes helper function exists like in add(): generate_recurring_dates($startDateTime, $frequency, $duration)
            $recurring_dates = generate_recurring_dates($datetime, $recurring_frequency, (int)$recurring_duration);
            $update['recurring_dates'] = json_encode($recurring_dates);
        } else {
            // Clear any previous recurrence meta if switching from recurring → non-recurring
            $update['recurring_dates'] = null;
        }

        // ✅ Persist
        if ($this->reminders_model->update($id, $update)) {
            set_alert('success', 'Reminder updated successfully.');
        } else {
            set_alert('danger', 'Failed to update reminder.');
        }
    }

    // ✅ Fallback redirect
    redirect('reminders');
}


    public function delete($id)
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
        }

        if ($this->reminders_model->delete($id)) {
            set_alert('success', 'Reminder deleted successfully.');
        } else {
            set_alert('danger', 'Failed to delete reminder.');
        }

        redirect('reminders');
    }

    public function mark_completed($id)
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
        }

        if ($this->reminders_model->update($id, ['is_completed' => 1, 'completed_at' => date('Y-m-d H:i:s')])) {
            set_alert('success', 'Reminder marked as completed.');
        } else {
            set_alert('danger', 'Failed to update reminder status.');
        }

        redirect('reminders');
    }

    private function send_reminder_notification($title, $description, $date, $user_id)
    {
        $message = 'New reminder scheduled:' . $title . ($description ?: 'No description provided.');
        $this->Notification_model->add([
            'user_id'     => $user_id,
            'sender_id'   => $user_id,
            'short_text'  => 'Reminder: ' . $title,
            'full_text'   => $title . '|' . ($description ?: 'No description.'),
        ]);

    }

    private function send_reminder_email($title, $description, $date, $user_id)
    {
        $user = $this->db->get_where('users', ['id' => $user_id])->row();

        if ($user && valid_email($user->email)) {
            $this->email->from(get_option('smtp_email'), get_option('companyname') . ' Reminders');
            $this->email->to($user->email);
            $this->email->subject('New Reminder: ' . $title);

            $body = "Hi {$user->firstname},<br><br>";
            $body .= "You have a new reminder scheduled:<br><br>";
            $body .= "<strong>{$title}</strong><br>";
            $body .= $description ? "<em>{$description}</em><br>" : '';
            $body .= "Scheduled for: <strong>" . _dt($date) . "</strong><br><br>";
            $body .= "<a href='" . site_url('reminders') . "' style='color:#007bff;'>View in Dashboard</a>";

            $this->email->message($body);
            $this->email->send();
        }
    }


    public function report()
    {
        // Any data you want to pass to the view
        $data['title'] = 'Reminders Report';
        $data['report_data'] = $this->reminders_model->get_report_summary(); // Example model function
    
        // This will load: modules/reminders/views/reports/summary.php
        $this->load->view('layouts/master', [
            'subview'   => 'reports/summary',
            'view_data' => $data,
        ]);
    }



public function alert_next()
{
    if (!$this->session->userdata('is_logged_in')) { show_404(); }
    $user_id = (int)$this->session->userdata('user_id');

    // Ask the model for the next due alert (either -30min or -5min) not yet acked
    $p = $this->reminders_model->find_next_alert_for_user($user_id);

    if (!$p) {
        return $this->output->set_status_header(204);
    }

    // Build JSON payload (strings only)
    $out = [
        'id'            => (int)$p['id'],
        'type'          => $p['alert_type'], // 'minus_30' or 'minus_5'
        'occurrence_at' => $p['occurrence_at'],
        'title'         => $p['title'] ?? 'Reminder',
        'description'   => $p['description'] ?? '',
        'when'          => date('M j, Y g:i A', strtotime($p['occurrence_at'])),
        'priority'      => $p['priority'] ?? 'medium',
    ];

    return $this->output->set_content_type('application/json')->set_output(json_encode($out));
}

public function alert_ack()
{
    if (!$this->session->userdata('is_logged_in')) { show_404(); }
    $user_id       = (int)$this->session->userdata('user_id');
    $reminder_id   = (int)$this->input->post('reminder_id', true);
    $occurrence_at = $this->input->post('occurrence_at', true);
    $type          = $this->input->post('type', true); // may be 'minus_30'/'minus_5' or '30'/'5'

    $ok = $this->reminders_model->acknowledge_alert($user_id, $reminder_id, $occurrence_at, $type);

    return $this->output->set_content_type('application/json')->set_output(json_encode(['ok' => (bool)$ok]));
}


}