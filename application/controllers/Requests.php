<?php defined('BASEPATH') or exit('No direct script access allowed');

class Requests extends App_Controller
{
    protected int $uid = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Requests_model', 'requests');
        $this->load->helper(['security']);
        $this->load->library(['session', 'upload']);

        $this->uid = (int) ($this->session->userdata('user_id') ?? 0);

        if (!$this->uid) {
            redirect('auth/login');
        }
    }

    public function index()
    {

        if (! staff_can('manage_requests','general')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        
        $types = get_request_types();
        $sections = [];

        foreach ($types as $slug => $meta) {
            $stats = $this->requests->get_request_stats($this->uid, [
                'type' => $slug
            ]);

            $sections[$slug] = [
                'slug'        => $slug,
                'label'       => $meta['label'],
                'description' => $meta['description'] ?? '',
                'icon'        => $meta['icon'] ?? 'ti ti-list',
                'url'         => site_url('requests/type/' . $slug),
                'total'       => $stats['total'],
                'pending'     => $stats['pending'],
                'approved'    => $stats['approved'],
                'rejected'    => $stats['rejected'],
                'other'       => $stats['other'],
            ];
        }

        $layout_data = [
            'page_title' => 'Requests Overview',
            'subview'    => 'requests/index',
            'view_data'  => [
                'sections' => $sections,
                'stats'    => $this->requests->get_request_stats($this->uid),
            ],
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    public function type($slug = null)
    {
        $type = get_request_type((string) $slug);
        if (!$type) {
            show_404();
            return;
        }

        $rows = $this->requests->get_by_user_and_type($this->uid, $slug);

        $layout_data = [
            'page_title' => $type['label'],
            'subview'    => 'requests/type',
            'view_data'  => [
                'slug'    => $slug,
                'section' => $type,
                'rows'    => $rows,
            ],
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    public function new_request()
    {
        $layout_data = [
            'page_title' => 'New Request',
            'subview'    => 'requests/new_request',
            'view_data'  => [
                'request_types' => get_request_types(),
            ],
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    public function load_form($slug = '')
    {
        $type = get_request_type($slug);
        if (!$type || empty($type['form_view'])) {
            show_404();
            return;
        }

        $this->load->view($type['form_view']);
    }

    public function load_existing($slug = '')
    {
        if (!is_valid_request_type($slug)) {
            show_404();
            return;
        }
    
        $rows = $this->requests->get_existing_for_selector($this->uid, $slug);
    
        if (!$rows) {
            echo '<div class="text-muted fst-italic py-2">No existing records found.</div>';
            return;
        }
    
        echo '
        <div class="table-responsive">
          <table class="table table-sm small table-hover table-bottom-border align-middle mb-0">
            <thead class="bg-light-primary">
              <tr>
                <th>#</th>
                <th>Request No</th>
                <th class="text-end">Status</th>
              </tr>
            </thead>
            <tbody>
        ';
    
        $i = 1;
        foreach ($rows as $r) {
    
            // Status → badge mapping (safe default)
            $status = strtolower($r['status']);
            switch ($status) {
                case 'approved':
                case 'active':
                    $badge = 'success';
                    break;
                case 'pending':
                    $badge = 'warning';
                    break;
                case 'rejected':
                case 'cancelled':
                    $badge = 'danger';
                    break;
                default:
                    $badge = 'secondary';
            }
    
            echo sprintf(
                '<tr>
                    <td class="text-muted">%d</td>
                    <td class="fw-semibold">%s</td>
                    <td class="text-end">
                      <span class="badge bg-%s-subtle text-%s fw-semibold">
                        %s
                      </span>
                    </td>
                </tr>',
                $i++,
                html_escape($r['request_no']),
                $badge,
                $badge,
                html_escape(ucfirst($r['status']))
            );
        }
    
        echo '
            </tbody>
          </table>
        </div>';
    }

    public function store()
    {
        if ($this->input->method(true) !== 'POST') {
            show_404();
        }
    
        $type = $this->input->post('request_type', true);
    
        if (!is_valid_request_type($type)) {
            set_alert('danger', 'Invalid request type.');
            redirect('requests/new_request');
        }
    
        $method = 'store_' . $type;
    
        if (!method_exists($this, $method)) {
            show_error("Handler for request type '{$type}' not implemented.", 500);
        }
    
        return $this->{$method}();
    }

    private function handle_attachments(): array
    {
        $attachments = [];
    
        if (empty($_FILES['attachments']['name'][0])) {
            return $attachments;
        }
    
        $path = FCPATH . 'uploads/requests/' . date('Y/m') . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    
        foreach ($_FILES['attachments']['name'] as $i => $name) {
    
            $_FILES['file'] = [
                'name'     => $_FILES['attachments']['name'][$i],
                'type'     => $_FILES['attachments']['type'][$i],
                'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                'error'    => $_FILES['attachments']['error'][$i],
                'size'     => $_FILES['attachments']['size'][$i],
            ];
    
            $this->upload->initialize([
                'upload_path'   => $path,
                'allowed_types' => '*',
                'encrypt_name'  => true,
            ]);
    
            if ($this->upload->do_upload('file')) {
                $f = $this->upload->data();
    
                $attachments[] = [
                    'original' => $f['client_name'],
                    'stored'   => $f['file_name'],
                    'path'     => str_replace(FCPATH, '', $path),
                    'mime'     => $f['file_type'],
                    'size'     => $f['file_size'],
                ];
            }
        }
    
        return $attachments;
    }
    
    private function store_inventory_request()
    {
        $payload = $this->input->post('payload', true) ?? [];
    
        if (empty($payload['required_quantity']) || empty($payload['cost_per_item'])) {
            set_alert('danger', 'Quantity and cost are required.');
            redirect('requests/new_request');
        }
    
        // Server-authoritative calculation
        $qty  = (float) $payload['required_quantity'];
        $cost = (float) $payload['cost_per_item'];
    
        $payload['total_amount'] = round($qty * $cost, 2);
    
        $request_no = $this->requests->generate_request_no();
    
        $this->requests->create([
            'request_no'    => $request_no,
            'type'          => 'inventory_request',
            'requested_by'  => $this->uid,
            'department_id' => (int) $this->input->post('department_id'),
            'priority'      => $this->input->post('priority') ?? 'normal',
            'payload'       => $payload,
            'attachments'   => $this->handle_attachments(),
        ]);
    
        set_alert('success', "Inventory Request {$request_no} submitted.");
        redirect('requests/new');
    }

    private function store_leave_request()
    {
        $payload = $this->input->post('payload', true) ?? [];
    
        if (empty($payload['leave_type_id']) || empty($payload['start_date']) || empty($payload['end_date'])) {
            set_alert('danger', 'Leave type and dates are required.');
            redirect('requests/new_request');
        }
    
        if (strtotime($payload['end_date']) < strtotime($payload['start_date'])) {
            set_alert('danger', 'End date cannot be before start date.');
            redirect('requests/new_request');
        }
    
        $request_no = $this->requests->generate_request_no();
    
        $this->requests->create([
            'request_no'    => $request_no,
            'type'          => 'leave_request',
            'requested_by'  => $this->uid,
            'department_id' => (int)$this->input->post('department_id'),
            'priority'      => $this->input->post('priority') ?? 'normal',
            'payload'       => $payload,
            'attachments'   => $this->handle_attachments(),
        ]);
    
        set_alert('success', "Leave Request {$request_no} submitted.");
        redirect('requests/new');
    }

    public function view_ajax($id = null)
    {
        $id = (int) $id;
        if (!$id) {
            show_404();
        }
    
        $request = $this->requests->get_by_id($id);
        if (!$request) {
            show_404();
        }
    
        if (
            (int)$request['requested_by'] !== $this->uid &&
            !staff_can('manage_requests', 'general')
        ) {
            show_403();
        }
    
        $this->load->view('requests/modals/view_request', [
            'request' => $request,
        ]);
    }
    
    public function delete($id = null)
    {
        $id = (int) $id;
        if (!$id) {
            show_404();
        }
    
        if (!staff_can('manage_requests', 'general')) {
            show_403();
        }
    
        $request = $this->requests->get_by_id($id);
        if (!$request) {
            show_404();
        }
    
        $this->requests->delete($id);
    
        set_alert('success', 'Request deleted successfully.');
        redirect('requests');
    }

public function approve_leave($id = null)
{
    $id = (int)$id;
    if (!$id) {
        show_404();
    }

    if (!staff_can('manage_requests', 'general')) {
        show_403();
    }

    $request = $this->requests->get_by_id($id);
    if (!$request || $request['type'] !== 'leave_request') {
        show_404();
    }

    // Update status → APPROVED
    $this->requests->update_status($id, 'approved', $this->uid);

    // Notify requester ONLY (decision stage)
    $this->load->helper('attendance_notification');

    notify_leave_approver(
        (int)$request['requested_by'],
        [
            'request_no'   => $request['request_no'],
            'start_date'   => $request['payload']['start_date'] ?? null,
            'end_date'     => $request['payload']['end_date'] ?? null,
            'leave_type'   => $request['payload']['leave_type_id'] ?? null,
            'requested_by'=> $request['requested_by'],
        ],
        'approved'
    );

    set_alert('success', 'Leave request approved successfully.');
    redirect('requests/type/leave_request');
}

public function reject_leave($id = null)
{
    $id = (int)$id;
    if (!$id) {
        show_404();
    }

    if (!staff_can('manage_requests', 'general')) {
        show_403();
    }

    $request = $this->requests->get_by_id($id);
    if (!$request || $request['type'] !== 'leave_request') {
        show_404();
    }

    $reason = trim((string)$this->input->post('reason', true));

    // Update status → REJECTED
    $this->requests->update_status(
        $id,
        'rejected',
        $this->uid,
        ['reason' => $reason]
    );

    // Notify requester ONLY (decision stage)
    $this->load->helper('attendance_notification');

    notify_leave_approver(
        (int)$request['requested_by'],
        [
            'request_no'   => $request['request_no'],
            'start_date'   => $request['payload']['start_date'] ?? null,
            'end_date'     => $request['payload']['end_date'] ?? null,
            'leave_type'   => $request['payload']['leave_type_id'] ?? null,
            'requested_by'=> $request['requested_by'],
            'reason'       => $reason,
        ],
        'rejected'
    );

    set_alert('success', 'Leave request rejected successfully.');
    redirect('requests/type/leave_request');
}


}
