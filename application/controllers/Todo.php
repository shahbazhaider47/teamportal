<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Todo extends App_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model('Todo_model');
    }

    // Add new to-do (from modal form POST)
    public function add() {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }

        $todo_name = trim($this->input->post('todo_name', true));
        if (!$todo_name) {
            set_alert('danger', 'To-Do name is required.');
            redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
        }

        $user_id = $this->session->userdata('user_id');
        $this->Todo_model->add($user_id, $todo_name);

        set_alert('success', 'To-Do added successfully!');
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard');
    }

    // List all / own To-Dos based on permissions
    public function index()
    {
        // Require login
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
    
        // Permission gates
        $canViewGlobal = function_exists('staff_can') && staff_can('view_global', 'todos');
        $canViewOwn    = function_exists('staff_can') && staff_can('view_own', 'todos');
    
        if (!$canViewGlobal && !$canViewOwn) {
            // Deny if neither permission is granted
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        // Fetch data according to permission
        $user_id = (int) $this->session->userdata('user_id');
        if ($canViewGlobal) {
            // Needs: $this->Todo_model->get_all()
            $todos      = $this->Todo_model->get_all();
            $pageTitle  = 'All To-Dos';
        } else {
            // Needs: $this->Todo_model->get_by_user($user_id)
            $todos      = $this->Todo_model->get_by_user($user_id);
            $pageTitle  = 'My To-Do List';
        }
    
        // View payload
        $data = [
            'todos'       => $todos,
            'page_title'  => $pageTitle,
            'canViewAll'  => $canViewGlobal, // handy for conditional columns/UI in the view
        ];
    
        $this->load->view('layouts/master', [
            'subview'    => 'todo/index',
            'view_data'  => $data,
            'page_title' => $pageTitle,
        ]);
    }


    public function complete($todo_id = null)
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
        $user_id = $this->session->userdata('user_id');
        if ($todo_id && $this->Todo_model->complete($todo_id, $user_id)) {
            set_alert('success', 'To-Do marked as completed!');
        } else {
            set_alert('danger', 'Unable to complete To-Do.');
        }
        redirect('todo');
    }

    public function uncomplete($todo_id = null)
    {
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            return;
        }
        $user_id = $this->session->userdata('user_id');
        if ($todo_id && $this->Todo_model->uncomplete($todo_id, $user_id)) {
            set_alert('success', 'To-Do marked as not completed!');
        } else {
            set_alert('danger', 'Unable to update To-Do.');
        }
        redirect('todo');
    }

// In your Todo.php controller

public function toggle_status() {
    if (! $this->session->userdata('is_logged_in')) {
        $this->output->set_status_header(403)->set_output(json_encode(['error'=>'Not logged in']));
        return;
    }
    $todo_id = $this->input->post('id');
    $status  = $this->input->post('status');
    $user_id = $this->session->userdata('user_id');
    if ($todo_id === null || $status === null) {
        $this->output->set_status_header(400)->set_output(json_encode(['error'=>'Invalid input']));
        return;
    }
    $ok = $this->Todo_model->set_completed($todo_id, $status ? 1 : 0);
    $this->output->set_content_type('application/json')->set_output(json_encode(['success'=>$ok ? 1 : 0]));
}


public function quick_add()
{
    // Only AJAX calls
    if (!$this->input->is_ajax_request()) {
        show_404();
        return;
    }

    // Must be logged in
    if (!$this->session->userdata('is_logged_in')) {
        $this->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => 0,
                'message' => 'Not logged in.',
            ]));
        return;
    }

    $todo_name = trim((string) $this->input->post('todo_name', true));
    if ($todo_name === '') {
        $this->output
            ->set_status_header(422)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => 0,
                'message' => 'To-Do title is required.',
            ]));
        return;
    }

    $user_id    = (int) $this->session->userdata('user_id');
    $rel_type   = trim((string) $this->input->post('rel_type', true));
    $rel_id_raw = $this->input->post('rel_id', true);
    $rel_id     = ($rel_id_raw === '' || $rel_id_raw === null) ? null : (int) $rel_id_raw;

    $insert_id = $this->Todo_model->add(
        $user_id,
        $todo_name,
        $rel_type !== '' ? $rel_type : null,
        $rel_id
    );

    if (!$insert_id) {
        $this->output
            ->set_status_header(500)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => 0,
                'message' => 'Failed to add To-Do.',
            ]));
        return;
    }

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode([
            'success' => 1,
            'message' => 'To-Do added.',
            'todo_id' => (int) $insert_id,
        ]));
}

    
}
