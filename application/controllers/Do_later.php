<?php defined('BASEPATH') or exit('No direct script access allowed');

class Do_later extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Do_later_model', 'doLater');
    }

    /**
     * List all do-later tasks
     */
    public function index()
    {
        $data = [
            'page_title' => 'Do Later Tasks',
            'tasks'      => $this->doLater->get_all(),
        ];

        $layout_data = [
            'page_title' => 'Do Later Tasks',
            'subview'    => 'do_later/manage',
            'view_data'  => $data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Create task (POST)
     */
    public function store()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $payload = [
            'type'        => $this->input->post('type', true),
            'reference'   => $this->input->post('reference', true),
            'code'        => $this->input->post('code', false), // RAW
            'status'      => $this->input->post('status', true),
            'priority'    => $this->input->post('priority', true),
        ];

        $this->doLater->insert($payload);

        set_alert('success', 'Task added successfully');
        redirect('do_later');
    }

    /**
     * Update status (AJAX-safe)
     */
    public function update_status($id)
    {
        $status = $this->input->post('status', true);

        if (!$status) {
            echo json_encode(['success' => false]);
            return;
        }

        $this->doLater->update($id, [
            'status' => $status,
            'completed_at' => $status === 'completed' ? date('Y-m-d H:i:s') : null,
        ]);

        set_alert('success', 'Task added successfully');
    }

    /**
     * Update task (POST)
     */
    public function update($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    
        // Optional: basic existence check
        $task = $this->doLater->get($id);
        if (!$task) {
            show_404();
        }
    
        $payload = [
            'type'       => $this->input->post('type', true),
            'reference'  => $this->input->post('reference', true),
            'code'       => $this->input->post('code', false), // RAW (important)
            'status'     => $this->input->post('status', true),
            'priority'   => $this->input->post('priority', true),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    
        // Auto-set completed_at if status is completed
        if ($payload['status'] === 'completed') {
            $payload['completed_at'] = date('Y-m-d H:i:s');
        } else {
            $payload['completed_at'] = null;
        }
    
        $this->doLater->update($id, $payload);
    
        set_alert('success', 'Task updated successfully');
        redirect('do_later');
    }

    /**
     * Delete task
     */
    public function delete($id)
    {
        $this->doLater->delete($id);
        set_alert('success', 'Task deleted');
        redirect('do_later');
    }
}
