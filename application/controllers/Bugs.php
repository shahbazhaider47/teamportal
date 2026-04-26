<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Bugs extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('is_logged_in')) {
            // For AJAX, return JSON error instead of redirect
            if ($this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')
                    ->set_status_header(401)
                    ->set_output(json_encode(['status'=>'error','message'=>'Not authenticated.']));
                exit;
            }
            redirect('authentication/login');
        }

        $this->load->library('email');
        $this->load->helper(['url','security']);
        $this->load->model('User_model');
        // Optional: $this->load->model('Company_info_model');
    }

    public function report()
    {
        // Force JSON
        $this->output->set_content_type('application/json');

        // Validate required fields
        $title       = trim((string)$this->input->post('title', true));
        $severity    = trim((string)$this->input->post('severity', true));
        $page_url    = trim((string)$this->input->post('page_url', true));
        $description = trim((string)$this->input->post('description', true));

        if ($title === '' || $severity === '' || $page_url === '' || $description === '') {
            $this->output->set_status_header(422)->set_output(json_encode([
                'status'=>'error','message'=>'Please complete all required fields.'
            ]));
            return;
        }

        $steps           = trim((string)$this->input->post('steps', true));
        $expected_actual = trim((string)$this->input->post('expected_actual', true));
        $user_agent      = trim((string)$this->input->post('user_agent', true));

        // Current user (for email context only; not shown in UI)
        $uid   = (int)$this->session->userdata('user_id');
        $user  = $this->User_model->get_user_by_id($uid);
        $empId = $user['emp_id'] ?? '';
        $name  = trim(($user['firstname'] ?? '').' '.($user['lastname'] ?? ''));

        // Resolve destination email
        $to = null;
        if (function_exists('get_setting')) {
            $to = get_setting('company_email');
        }
        if (!$to && function_exists('get_setting')) {
            $to = get_setting('company_email');
        }
        if (!$to) {
            // Uncomment if using Company_info_model:
            // $company = $this->Company_info_model->get_all_values();
            // $to = $company['email'] ?? null;
        }
        if (!$to) {
            $to = 'support@yourcompany.com'; // fallback
        }

        // Build email
        $subject = '[Bug Report] ' . $title . ' [' . $severity . ']';
        $lines = [
            "Title: {$title}",
            "Severity: {$severity}",
            "Page URL: {$page_url}",
            "",
            "Reported by: {$name} (EMP: {$empId}, ID: {$uid})",
            'When: ' . date('Y-m-d H:i:s'),
            'User Agent: ' . $user_agent,
            "",
            "What happened:",
            $description,
        ];
        if ($steps !== '') {
            $lines[] = "";
            $lines[] = "Steps to Reproduce:";
            $lines[] = $steps;
        }
        if ($expected_actual !== '') {
            $lines[] = "";
            $lines[] = "Expected vs Actual:";
            $lines[] = $expected_actual;
        }
        $message = implode("\n", $lines);

        // Handle optional attachment (no DB save; attach then delete)
        $attachment_path = null;
        if (!empty($_FILES['attachment']['name'])) {
            $tmpDir = FCPATH . 'uploads/_tmp/';
            if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0755, true); }

            $config = [
                'upload_path'   => $tmpDir,
                'allowed_types' => 'png|jpg|jpeg|pdf',
                'max_size'      => 2048,
                'encrypt_name'  => true,
            ];
            $this->load->library('upload');
            $this->upload->initialize($config);

            if ($this->upload->do_upload('attachment')) {
                $data = $this->upload->data();
                $attachment_path = $data['full_path'];
            } else {
                $this->output->set_status_header(400)->set_output(json_encode([
                    'status'=>'error',
                    'message'=>'Attachment upload failed: ' . strip_tags($this->upload->display_errors('', ''))
                ]));
                return;
            }
        }

        // Configure & send
        try {
            // If you already have email config in config/email.php, CI loads it automatically.
            $this->email->from('no-reply@' . parse_url(base_url(), PHP_URL_HOST), 'Bug Reporter');
            $this->email->to($to);
            $this->email->subject($subject);
            $this->email->message($message);

            if ($attachment_path) {
                $this->email->attach($attachment_path);
            }

            if (!$this->email->send(false)) {
                $err = $this->email->print_debugger(['headers']);
                $this->output->set_status_header(500)->set_output(json_encode([
                    'status'=>'error','message'=>'Could not send email.','debug'=>$err
                ]));
                if ($attachment_path && is_file($attachment_path)) @unlink($attachment_path);
                return;
            }

            if ($attachment_path && is_file($attachment_path)) @unlink($attachment_path);

            $this->output->set_status_header(200)->set_output(json_encode([
                'status'=>'success','message'=>'Thanks! Your bug report has been sent.'
            ]));
        } catch (Throwable $e) {
            if ($attachment_path && is_file($attachment_path)) @unlink($attachment_path);
            log_message('error', 'Bug email failed: '.$e->getMessage());
            $this->output->set_status_header(500)->set_output(json_encode([
                'status'=>'error','message'=>'Server error sending bug report.'
            ]));
        }
    }
}
