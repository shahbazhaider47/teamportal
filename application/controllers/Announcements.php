<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Announcements extends App_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Department_model');
        $this->load->model('Announcement_model'); // ← add this
        $this->load->model('User_model');         // used by send_notifications if you keep it
        $this->load->model('Notification_model'); // ← add this
    }

    protected function log_activity(string $action)
    {
        $this->Activity_log_model->add([
            'user_id'    => $this->session->userdata('user_id') ?: 0,
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    public function index()
    {

        // Allow if the user has EITHER view_global OR view_own on 'announcements'.
        // Deny only if they have NEITHER.
        if (
            !staff_can('view_global', 'announcements') &&
            !staff_can('view_own', 'announcements')
        ) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $user_id = $this->session->userdata('user_id');
        $role = strtolower($this->session->userdata('user_role'));

        $announcements = $this->Announcement_model->get_visible_announcements($user_id, $role);
        $popup_announcements = $this->Announcement_model->get_popup_announcements($user_id, $role);

        $layout_data = [
            'page_title' => 'Announcements',
            'subview'    => 'announcements/manage',
            'view_data'  => [
                'announcements'       => $announcements,
                'popup_announcements' => $popup_announcements,
                'categories'          => $this->Announcement_model->get_categories(),
                'departments'         => $this->Department_model->get_all(),
                'user_id'             => $user_id,
                'user_role'           => $role
            ]
        ];

        $this->load->view('layouts/master', $layout_data);
    }


    public function create()
    {
        // Permissions check (optional but recommended)
        if (!staff_can('create', 'announcements')) { show_error('Forbidden', 403); }
    
        $data = [
            'title'        => $this->input->post('title', true),
            'message'      => $this->input->post('message', true),
            'sent_to'      => $this->input->post('sent_to', true),
            'priority'     => $this->input->post('priority', true),
            'category_id'  => $this->input->post('category_id'),
            'is_published' => $this->input->post('is_published') ? 1 : 0,
            'created_by'   => $this->session->userdata('user_id'),
            'created_at'   => date('Y-m-d H:i:s'),
        ];
    
        // Handle file upload (use same types as edit)
        if (!empty($_FILES['attachment']['name'])) {
            $uploadPath = FCPATH . 'uploads/announcements/';
            if (!is_dir($uploadPath)) { @mkdir($uploadPath, 0755, true); }
    
            $config = [
                'upload_path'   => $uploadPath,
                'allowed_types' => 'jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|ppt|pptx|zip',
                'max_size'      => 2048,
                'encrypt_name'  => true,
            ];
            $this->load->library('upload', $config);
    
            if ($this->upload->do_upload('attachment')) {
                $uploadedData        = $this->upload->data();
                $data['attachment']  = $uploadedData['file_name']; // ← correct column
            } else {
                set_alert('danger', strip_tags($this->upload->display_errors()));
                redirect('announcements'); return;
            }
        }
    
        $announcement_id = $this->Announcement_model->save_announcement($data); // ← single call
        if ($announcement_id) {
            $this->log_activity('Created announcement: "' . $data['title'] . '" (ID: ' . $announcement_id . ')');
            // $this->send_notifications($announcement_id); // ← comment out if not emailing now
            
            if ((int)$data['is_published'] === 1) {
                $this->send_notifications($announcement_id);
            }
            
            set_alert('success', 'Announcement created successfully.');
        } else {
            set_alert('danger', 'Failed to create announcement.');
        }
        redirect('announcements');
    }

    
    public function edit($id)
    {
        if (!staff_can('edit', 'announcements')) { show_error('Forbidden', 403); }
    
        $announcement = $this->Announcement_model->get_announcement((int)$id);
        if (!$announcement) { show_404(); }
    
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            // Normalize & validate inputs
            $allowedSentTo   = ['all','employee','teamlead','manager'];
            $allowedPriority = ['low','medium','high','critical'];
    
            $rawSentTo   = strtolower((string)$this->input->post('sent_to', true));
            $rawPriority = strtolower((string)$this->input->post('priority', true));
    
            $data = [
                'id'           => (int)$id,
                'title'        => (string)$this->input->post('title', true),
                'message'      => (string)$this->input->post('message', true),
                'sent_to'      => in_array($rawSentTo, $allowedSentTo, true) ? $rawSentTo : 'all',
                'priority'     => in_array($rawPriority, $allowedPriority, true) ? $rawPriority : 'medium',
                'category_id'  => (int)$this->input->post('category_id'),
                'is_published' => (int)$this->input->post('is_published') === 1 ? 1 : 0,
            ];
    
            // Was draft before this edit?
            $wasDraft = (int)$announcement['is_published'] === 0;
    
            // Handle attachment upload (optional)
            if (!empty($_FILES['attachment']['name'])) {
                $uploadPath = FCPATH . 'uploads/announcements/';
                if (!is_dir($uploadPath)) { @mkdir($uploadPath, 0755, true); }
    
                $config = [
                    'upload_path'   => $uploadPath,
                    'allowed_types' => 'jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|ppt|pptx|zip',
                    'encrypt_name'  => true,
                    'max_size'      => 2048,
                    'overwrite'     => true,
                ];
                $this->load->library('upload', $config);
    
                if ($this->upload->do_upload('attachment')) {
                    $uploadData         = $this->upload->data();
                    $data['attachment'] = $uploadData['file_name'];
    
                    // remove old file
                    if (!empty($announcement['attachment'])) {
                        $oldPath = $uploadPath . $announcement['attachment'];
                        if (is_file($oldPath)) { @unlink($oldPath); }
                    }
                } else {
                    set_alert('danger', strip_tags($this->upload->display_errors()));
                    redirect('announcements'); return;
                }
            }
    
            $saved = $this->Announcement_model->save_announcement($data);
    
            if ($saved) {
                // If it was a draft before and is now published → send notifications once
                $becamePublished = $wasDraft && ((int)$data['is_published'] === 1);
                if ($becamePublished) {
                    $this->send_notifications((int)$id);
                }
    
                $this->log_activity('Updated announcement: "' . $data['title'] . '" (ID: ' . (int)$id . ')');
                set_alert('success', 'Announcement updated successfully.');
            } else {
                set_alert('danger', 'Failed to update announcement.');
            }
            redirect('announcements'); return;
        }
    
        if ($this->input->is_ajax_request()) {
            $this->load->view('announcements/edit_ajax', ['announcement' => $announcement]);
            return;
        }
    
        set_alert('danger', 'Invalid access method.');
        redirect('announcements');
    }

    
    public function delete($id)
    {
        if (!staff_can('delete', 'announcements')) { show_error('Forbidden', 403); }
    
        $announcement = $this->db->get_where('announcements', ['id' => (int)$id])->row_array();
        $title = $announcement['title'] ?? 'Unknown';
    
        // remove attachment file (optional)
        if (!empty($announcement['attachment'])) {
            $path = FCPATH . 'uploads/announcements/' . $announcement['attachment'];
            if (is_file($path)) { @unlink($path); }
        }
    
        // delete announcement + dismissals
        $this->db->delete('announcement_dismissals', ['announcement_id' => (int)$id]);
        $this->db->delete('announcements', ['id' => (int)$id]);
    
        $this->log_activity('Deleted announcement: "' . $title . '" (ID: ' . (int)$id . ')');
        set_alert('success', 'Announcement deleted successfully');
        redirect('announcements');
    }
    
    
    public function dismiss()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') { show_404(); }
    
        $announcement_id = (int)$this->input->post('announcement_id');
        $user_id = (int)$this->session->userdata('user_id');
        if (!$announcement_id || !$user_id) { show_404(); }
    
        $exists = $this->db->where([
            'announcement_id' => $announcement_id,
            'user_id'         => $user_id
        ])->get('announcement_dismissals')->row();
    
        if (!$exists) {
            $this->db->insert('announcement_dismissals', [
                'announcement_id' => $announcement_id,
                'user_id'         => $user_id,
                'dismissed_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }
    
private function send_notifications($announcement_id)
{
    // Fetch announcement + creator
    $announcement = $this->db
        ->select('a.*, CONCAT(u.firstname, " ", u.lastname) AS sender_name, u.email AS sender_email')
        ->from('announcements a')
        ->join('users u', 'u.id = a.created_by', 'left')
        ->where('a.id', (int)$announcement_id)
        ->get()
        ->row_array();

    if (!$announcement) { return; }
    if ((int)$announcement['is_published'] !== 1) { return; }

    // Build recipients
    if ($announcement['sent_to'] === 'all') {
        $recipients = $this->db
            ->select('id, email, firstname, lastname, user_role, is_active')
            ->from('users')
            ->where('is_active', 1)
            ->get()->result_array();
    } else {
        $role = strtolower(trim($announcement['sent_to']));
        $recipients = $this->db
            ->select('id, email, firstname, lastname, user_role, is_active')
            ->from('users')
            ->where('is_active', 1)
            ->where('LOWER(user_role) =', $role)
            ->get()->result_array();
    }

    if (empty($recipients)) { return; }

    // Common parts
    $title         = (string)$announcement['title'];
    $message       = (string)$announcement['message'];
    $link          = site_url('announcements');
    $email_subject = 'New Announcement: ' . $title;

    $brand = function_exists('get_setting') ? (get_setting('companyname') ?: 'System') : 'System';

    foreach ($recipients as $r) {
        if (empty($r['email']) || (int)$r['is_active'] !== 1) { continue; }

        $full_name = trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? ''));

        // Render HTML template via App_mailer (which loads SMTP from system_settings)
        $brand = app_mailer()->brand_name(); // resolves to system_settings.from_name or get_company_name()
        $link  = site_url('announcements');
        
        app_mailer()->send([
          'to'        => $r['email'],
          'subject'   => 'New Announcement: ' . $title,
          'view'      => 'emails/templates/announcement',
          'view_data' => [
              'recipient_name' => $full_name ?: 'there',
              'title'          => $title,
              'message'        => $message,
              'cta_url'        => $link,
              'brand'          => $brand,
              'brand_url'      => base_url(),
          ],
        ]);
    }
}

   
    public function view_ajax($id)
    {
        $data = $this->Announcement_model->get_announcement($id);
        if (!$data) {
            show_404();
        }
    
        $this->load->view('announcements/view_ajax', ['announcement' => $data]);
    }
    
    
    
    public function widget()
    {
        $user_id = $this->session->userdata('user_id');
        $role    = strtolower($this->session->userdata('user_role'));
    
        $this->load->model('Announcement_model');
        $data['announcements'] = $this->Announcement_model->get_visible_announcements($user_id, $role);
    
        $this->load->view('dashboard/widgets/announcements_widget', $data);
    }
    
    
    public function popup_latest()
    {
        // Must be logged in
        $user_id = (int)$this->session->userdata('user_id');
        if (!$user_id) { $this->output->set_status_header(401); return; }
    
        $role = strtolower((string)$this->session->userdata('user_role'));
    
        // Get the first (highest priority, latest) popup entry the user hasn't dismissed
        $list = $this->Announcement_model->get_popup_announcements($user_id, $role);
    
        if (empty($list)) {
            // Nothing to display
            $this->output
                 ->set_status_header(204)
                 ->set_content_type('application/json')
                 ->set_output('');
            return;
        }
    
        // Take the first one
        $a = $list[0];
    
        // Render the same body used by your view-only modal
        $html = $this->load->view('announcements/view_ajax', ['announcement' => $a], true);
    
        $payload = [
            'id'    => (int)$a['id'],
            'title' => 'New Announcement', // static heading requested
            'html'  => $html,
        ];
    
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($payload));
    }
 
}