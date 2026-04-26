<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Contracts extends App_Controller
{
    protected int $uid = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Contracts_model', 'contracts');
        $this->load->model('User_model',     'users');

        $this->load->library(['form_validation', 'session']);
        $this->load->library('App_mailer', null, 'app_mailer'); // ← add
        $this->load->helper(['url', 'form']);

        $this->uid = (int) ($this->session->userdata('user_id') ?? 0);
        if (!$this->uid) {
            redirect('authentication/login');
            return;
        }
    }

    /**
     * List all contracts.
     */
    public function index()
    {
        if (!staff_can('view_global','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $contracts = $this->contracts->get_all();

        $staff_list = method_exists($this->users, 'get_active_minimal_list')
            ? $this->users->get_active_minimal_list()
            : [];

        $view_data = [
            'contracts'  => $contracts,
            'staff_list' => $staff_list,
        ];

        $layout_data = [
            'page_title' => 'Staff Contracts',
            'subview'    => 'contracts/manage',
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Show "New Contract" form.
     */
    public function create()
    {
        if (!staff_can('create','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        // 1) Load all staff with required contract-related fields
        $this->db->select('
            id,
            user_role,
            firstname,
            initials,
            lastname,
            fullname,
            emp_id,
            emp_title,
            emp_joining,
            emp_department,
            emp_team,
            employment_type,
            joining_salary,
            current_salary
        ');
        $this->db->from('users');
        $this->db->where('is_active', 1);
        $this->db->order_by('fullname', 'ASC');
        $staff_list = $this->db->get()->result_array();

        // 2) Load positions (for emp_title mapping)
        $positions = [];
        if ($this->db->table_exists('hrm_positions')) {
            $posQuery  = $this->db->get('hrm_positions')->result_array();
            foreach ($posQuery as $p) {
                $positions[$p['id']] = $p['title']; // hrm_positions: id, title
            }
        }

        // 3) Load departments (for emp_department mapping)
        $departments = [];
        if ($this->db->table_exists('departments')) {
            $depQuery = $this->db->get('departments')->result_array();
            foreach ($depQuery as $d) {
                $departments[$d['id']] = $d['name']; // departments: id, name
            }
        }
        
        // 3b) Load teams (for emp_team mapping)
        $teams = [];
        if ($this->db->table_exists('teams')) {
            $teamsQuery = $this->db->get('teams')->result_array();
            foreach ($teamsQuery as $t) {
                $teams[$t['id']] = $t['name']; // teams: id, name
            }
        }

        // 4) Build staff_details for JS
        $staff_details = [];
        foreach ($staff_list as $u) {
            $id = (int)$u['id'];

            $staff_details[$id] = [
                'user_role'        => $u['user_role'] ?? '',
                'firstname'        => $u['firstname'] ?? '',
                'initials'         => $u['initials'] ?? '',
                'lastname'         => $u['lastname'] ?? '',
                'fullname'         => $u['fullname'] ?? trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '')),
                'emp_id'           => $u['emp_id'] ?? '',
                'emp_title'        => !empty($u['emp_title']) && isset($positions[$u['emp_title']])
                                        ? $positions[$u['emp_title']]
                                        : '',
                'emp_joining'      => $u['emp_joining'] ?? '',
                'emp_department'   => !empty($u['emp_department']) && isset($departments[$u['emp_department']])
                                        ? $departments[$u['emp_department']]
                                        : '',
                
                'emp_team'         => !empty($u['emp_team']) && isset($teams[$u['emp_team']])
                                        ? $teams[$u['emp_team']]
                                        : '',
                
                'employment_type'  => $u['employment_type'] ?? '',
                'joining_salary'   => $u['joining_salary'] ?? '',
                'current_salary'   => $u['current_salary'] ?? '',
            ];
        }

        $view_data = [
            'staff_list'    => $staff_list,
            'staff_details' => $staff_details,
        ];

        $layout_data = [
            'page_title' => 'New Staff Contract',
            'subview'    => 'contracts/new_contract',
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Handle create POST.
     */
    public function store()
    {

        $this->form_validation->set_rules('user_id', 'Staff Member', 'required|integer');
        $this->form_validation->set_rules('contract_type', 'Contract Type', 'required|max_length[100]');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');

        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }

        $user_id = (int)$this->input->post('user_id');

        // Handle file upload (optional) — same pattern as save_document()
        $contract_file = null;
        if (!empty($_FILES['contract_file']['name'])) {
            $upload_path = FCPATH . 'uploads/users/contracts/';

            if (!is_dir($upload_path)) {
                @mkdir($upload_path, 0755, true);
            }

            $config = [
                'upload_path'   => $upload_path,
                'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png',
                'max_size'      => 20480, // 20MB
                'encrypt_name'  => true,
            ];

            $this->load->library('upload', $config);
            $this->upload->initialize($config);

            if (!$this->upload->do_upload('contract_file')) {
                $msg = strip_tags($this->upload->display_errors('', ''));
                set_alert('danger', $msg);
                return redirect('contracts/create');
            }

            $upload = $this->upload->data();
            // Store only the file name (like hrm documents)
            $contract_file = $upload['file_name'];
        }

        $is_renewable       = $this->input->post('is_renewable') ? 1 : 0;
        $notice_period_days = (int)$this->input->post('notice_period_days', true);

        $data = [
            'user_id'            => $user_id,
            'contract_type'      => $this->input->post('contract_type', true),
            'version'            => 1,
            'parent_contract_id' => null,
            'start_date'         => $this->input->post('start_date', true),
            'end_date'           => $this->input->post('end_date', true) ?: null,
            'notice_period_days' => $notice_period_days ?: 30,
            'is_renewable'       => $is_renewable,
            'contract_file'      => $contract_file, // file name or null
            'status'             => $this->input->post('status', true) ?: 'draft',
            'internal_notes'     => $this->input->post('internal_notes', true) ?: null,
            'created_by'         => $this->uid,
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        $id = $this->contracts->create($data);

        if ($id) {
            set_alert('success', 'Contract created successfully.');
            redirect('contracts/view/' . $id);
        } else {
            set_alert('danger', 'Failed to create the contract. Please try again.');
            redirect('contracts/create');
        }
    }

    /**
     * View full contract details.
     */
    public function view($id = null)
    {

        if (!staff_can('view_global','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $id       = (int)$id;
        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        $view_data = [
            'contract' => $contract,
        ];

        $layout_data = [
            'page_title' => 'Contract Details',
            'subview'    => 'contracts/view',
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Edit contract form.
     */
    public function edit($id = null)
    {

        if (!staff_can('edit','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $id       = (int)$id;
        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        $staff_list = method_exists($this->users, 'get_active_minimal_list')
            ? $this->users->get_active_minimal_list()
            : [];

        $view_data = [
            'contract'   => $contract,
            'staff_list' => $staff_list,
        ];

        $layout_data = [
            'page_title' => 'Edit Contract',
            'subview'    => 'contracts/edit',
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Handle edit POST.
     */
    public function update($id = null)
    {
        if (!staff_can('edit','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
        $id       = (int)$id;
        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        $this->form_validation->set_rules('contract_type', 'Contract Type', 'required|max_length[100]');
        $this->form_validation->set_rules('start_date', 'Start Date', 'required');

        if ($this->form_validation->run() === false) {
            $this->edit($id);
            return;
        }

        // Handle file upload if new file provided
        $contract_file = $contract['contract_file'] ?? null;
        if (!empty($_FILES['contract_file']['name'])) {
            $upload_path = FCPATH . 'uploads/users/contracts/';

            if (!is_dir($upload_path)) {
                @mkdir($upload_path, 0755, true);
            }

            $config = [
                'upload_path'   => $upload_path,
                'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png',
                'max_size'      => 20480,
                'encrypt_name'  => true,
            ];

            $this->load->library('upload', $config);
            $this->upload->initialize($config);

            if (!$this->upload->do_upload('contract_file')) {
                $msg = strip_tags($this->upload->display_errors('', ''));
                set_alert('danger', $msg);
                return redirect('contracts/edit/' . $id);
            }

            $upload = $this->upload->data();

            // Delete old file if exists
            if (!empty($contract['contract_file'])) {
                $old_path = $upload_path . $contract['contract_file'];
                if (is_file($old_path)) {
                    @unlink($old_path);
                }
            }

            $contract_file = $upload['file_name'];
        }

        $is_renewable       = $this->input->post('is_renewable') ? 1 : 0;
        $notice_period_days = (int)$this->input->post('notice_period_days', true);

        $data = [
            // user_id kept as original to avoid changing ownership easily
            'contract_type'      => $this->input->post('contract_type', true),
            'start_date'         => $this->input->post('start_date', true),
            'end_date'           => $this->input->post('end_date', true) ?: null,
            'notice_period_days' => $notice_period_days ?: $contract['notice_period_days'],
            'is_renewable'       => $is_renewable,
            'contract_file'      => $contract_file, // file name (or unchanged)
            'status'             => $this->input->post('status', true) ?: $contract['status'],
            'sent_at'            => $this->input->post('sent_at', true) ?: null,
            'signed_at'          => $this->input->post('signed_at', true) ?: null,
            'expired_at'         => $this->input->post('expired_at', true) ?: null,
            'renew_at'           => $this->input->post('renew_at', true) ?: null,
            'last_renew_at'      => $this->input->post('last_renew_at', true) ?: $contract['last_renew_at'],
            'sign_method'        => $this->input->post('sign_method', true) ?: $contract['sign_method'],
            'signature_hash'     => $this->input->post('signature_hash', true) ?: null,
            'internal_notes'     => $this->input->post('internal_notes', true) ?: null,
            'updated_by'         => $this->uid,
            'updated_at'         => date('Y-m-d H:i:s'),
        ];

        $this->contracts->update($id, $data);

        set_alert('success', 'Contract updated successfully.');
        redirect('contracts/view/' . $id);
    }

    /**
     * Simple renew action, no extra form.
     */
    public function renew($id = null)
    {

        if (!staff_can('edit','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $id       = (int)$id;
        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        $this->contracts->renew($id, []);
        set_alert('success', 'Contract renewed.');
        redirect('contracts/view/' . $id);
    }

    /**
     * Mark contract as expired.
     */
    public function mark_expired($id = null)
    {
        if (!staff_can('edit','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $id       = (int)$id;
        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        $this->contracts->mark_expired($id);
        set_alert('success', 'Contract marked as expired.');
        redirect('contracts/view/' . $id);
    }

    /**
     * Soft delete.
     */
    public function delete($id = null)
    {
        if (!staff_can('delete','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $id       = (int)$id;
        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        // Optional: remove file as well
        if (!empty($contract['contract_file'])) {
            $upload_path = FCPATH . 'uploads/users/contracts/';
            $file        = $upload_path . $contract['contract_file'];
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->contracts->soft_delete($id, $this->uid);
        set_alert('success', 'Contract deleted.');
        redirect('contracts');
    }


    /**
     * Send contract for signature (status + email + in-app notification).
     */
    public function send_for_sign($id = null)
    {
        if (!staff_can('edit','contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }
    
        $id       = (int)$id;
        $contract = $this->contracts->get($id);
    
        if (!$contract) {
            show_404();
        }
    
        // If already signed, don’t allow sending again
        if (!empty($contract['signed_at']) || (isset($contract['status']) && $contract['status'] === 'signed')) {
            set_alert('warning', 'This contract is already signed and cannot be sent for signature again.');
            redirect('contracts/view/' . $id);
            return;
        }
    
        // Persist "sent" state
        $ok = $this->contracts->send_for_sign($id, [
            'status' => 'sent',
        ]);
    
        if (!$ok) {
            set_alert('danger', 'Failed to update contract status. Please try again.');
            redirect('contracts/view/' . $id);
            return;
        }
    
        // -------------------------------------------------
        // 1) Resolve Job Title from hrm_positions (by id)
        // -------------------------------------------------
        if (!empty($contract['emp_title'])) {
            $posRow = $this->db
                ->select('title')
                ->from('hrm_positions')
                ->where('id', (int)$contract['emp_title'])
                ->get()
                ->row_array();
    
            if ($posRow && !empty($posRow['title'])) {
                // Overwrite with readable title just for this email context
                $contract['emp_title'] = $posRow['title'];
            }
        }
    
        // -------------------------------------------------
        // 2) Build attachment path + public URL
        // -------------------------------------------------
        $attachmentPath = null;
        $attachmentUrl  = null;
    
        if (!empty($contract['contract_file'])) {
            $uploadDir = FCPATH . 'uploads/users/contracts/';
            $fullPath  = $uploadDir . $contract['contract_file'];
    
            if (is_file($fullPath)) {
                $attachmentPath = $fullPath;
                $attachmentUrl  = base_url('uploads/users/contracts/' . $contract['contract_file']);
            }
        }
    
        // -------------------------------------------------
        // 3) Email + In-app notification to employee
        // -------------------------------------------------
        $userId = (int)($contract['user_id'] ?? 0);
        if ($userId > 0) {
            // Fetch user
            $user     = $this->users->get_user_by_id($userId);
            $toEmail  = trim((string)($user['email'] ?? ''));
            $first    = trim((string)($user['firstname'] ?? ''));
            $last     = trim((string)($user['lastname'] ?? ''));
            $fullName = trim((string)($user['fullname'] ?? ($first . ' ' . $last)));
    
            if ($fullName === '') {
                $fullName = 'there';
            }
    
            // Where they will review/sign (can be switched to a dedicated /sign route later)
            $signUrl = base_url('contracts/my_contract/' . $id);
    
            // Brand name
            $brand = function_exists('get_company_name')
                ? (get_company_field('company_name') ?: 'HR Team')
                : 'HR Team';
    
            // --- Email via App_mailer (HTML template + attachment)
            if (function_exists('app_mailer') && $toEmail !== '') {
                $subject = $brand . ' | Employment contract for signature: ' . ($contract['contract_type'] ?? 'Employment Contract');
    
                $mailData = [
                    'to'        => $toEmail,
                    'subject'   => $subject,
                    'view'      => 'emails/users/sign_contract_html',
                    'view_data' => [
                        'recipient_name' => $fullName,
                        'contract'       => $contract,
                        'sign_url'       => $signUrl,
                        'brand'          => $brand,
                        'file_url'       => $attachmentUrl,                  // ← new
                        'file_name'      => $contract['contract_file'] ?? '', // ← new
                    ],
                ];
    
                // Attach the contract file if available
                if ($attachmentPath) {
                    $mailData['attachments'] = [
                        [
                            'attachment' => $attachmentPath,
                            'filename'   => basename($attachmentPath),
                            // 'type'    => mime_content_type($attachmentPath) ?? 'application/octet-stream', // optional
                        ],
                    ];
                }
    
                app_mailer()->send($mailData);
            }
    
            // --- In-app notification
            if (function_exists('notify_user')) {
                $subjectShort = (string)($contract['contract_type'] ?? 'Employment Contract');
                notify_user(
                    $userId,
                    'contracts',
                    'Contract sent for signature',
                    'Your contract "' . $subjectShort . '" has been sent for your review and signature.',
                    $signUrl,
                    ['channels' => ['in_app']]
                );
            }
        }
    
        set_alert('success', 'Contract sent for signature.');
        redirect('contracts/view/' . $id);
    }
    
    /**
     * Let a logged-in user view *their own* contract (for review/signing).
     * Uses the same view file as admin view: contracts/view.php
     */
    public function my_contract($id = null)
    {
        $uid = (int)($this->session->userdata('user_id') ?? 0);
        if (!$uid) {
            redirect('authentication/login');
            return;
        }
    
        $id = (int)$id;
        if (!$id) {
            show_404();
        }
    
        $contract = $this->contracts->get($id);
        if (!$contract) {
            show_404();
        }
    
        // Ownership guard
        if ((int)$contract['user_id'] !== $uid) {
            if (!function_exists('staff_can') || !staff_can('view_global', 'contracts')) {
                show_403();
            }
        }
    
        // 🔐 PASSWORD RE-AUTH CHECK
        $sessionKey = 'contract_verified_' . $id;
        $isVerified = (bool)$this->session->userdata($sessionKey);
    
        if (!$isVerified) {
            // Show password confirmation screen instead of contract
            $layout_data = [
                'page_title' => 'Confirm Identity',
                'subview'    => 'contracts/confirm_password',
                'view_data'  => [
                    'contract_id' => $id,
                ],
            ];
            $this->load->view('layouts/master', $layout_data);
            return;
        }
    
        // ✅ Verified → show contract
        $layout_data = [
            'page_title' => 'My Contract',
            'subview'    => 'contracts/view',
            'view_data'  => [
                'contract'     => $contract,
                'is_self_view' => true,
            ],
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }


    public function verify_contract_password()
    {
        $uid = (int)$this->session->userdata('user_id');
        if (!$uid) {
            redirect('authentication/login');
            return;
        }
    
        $contractId = (int)$this->input->post('contract_id');
        $password   = (string)$this->input->post('password');
    
        if (!$contractId || $password === '') {
            show_error('Invalid request', 400);
        }
    
        // Load user password hash
        $user = $this->db
            ->select('password')
            ->where('id', $uid)
            ->get('users')
            ->row_array();
    
        if (!$user || !password_verify($password, $user['password'])) {
            $this->session->set_flashdata('error', 'Incorrect password. Please try again.');
            redirect('contracts/my_contract/' . $contractId);
            return;
        }
    
        // ✅ Mark this contract as verified in session
        $this->session->set_userdata('contract_verified_' . $contractId, true);
    
        redirect('contracts/my_contract/' . $contractId);
    }

    /**
     * Sign a contract (self-service or HR override).
     * - Updates status => signed
     * - Sets signed_at, signed_by_user_id, sign_method, signature_hash
     */
    public function sign($id = null)
    {
        $uid = (int)($this->session->userdata('user_id') ?? 0);
        if (!$uid) {
            redirect('authentication/login');
            return;
        }

        $id       = (int)$id;
        $contract = $this->contracts->get($id);

        if (!$contract) {
            show_404();
        }

        // Guard: who can sign?
        $isSelf           = ((int)$contract['user_id'] === $uid);
        $canStaffOverride = function_exists('staff_can') && staff_can('edit', 'contracts');

        if (!$isSelf && !$canStaffOverride) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        // If already signed, do not allow again
        if (!empty($contract['signed_at']) || (isset($contract['status']) && $contract['status'] === 'signed')) {
            set_alert('warning', 'This contract is already signed and cannot be signed again.');
            $redirect = $isSelf ? 'contracts/my_contract/' . $id : 'contracts/view/' . $id;
            redirect($redirect);
            return;
        }

        // Basic validation
        $this->form_validation->set_rules('signature_text', 'Signature', 'required|min_length[3]|max_length[255]');

        if ($this->form_validation->run() === false) {
            set_alert('danger', strip_tags(validation_errors()));
            $redirect = $isSelf ? 'contracts/my_contract/' . $id : 'contracts/view/' . $id;
            redirect($redirect);
            return;
        }

        $signatureText = (string)$this->input->post('signature_text', true);
        $signMethod    = (string)($this->input->post('sign_method', true) ?: 'portal');

        $data = [
            'status'            => 'signed',
            'signed_at'         => date('Y-m-d H:i:s'),
            'signed_by_user_id' => $uid,
            'sign_method'       => $signMethod,
            'signature_hash'    => password_hash($signatureText, PASSWORD_BCRYPT),
            'updated_by'        => $uid,
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        $ok = $this->contracts->update($id, $data);

        if ($ok) {
            set_alert('success', 'Contract signed successfully.');
        } else {
            set_alert('danger', 'Failed to sign the contract. Please try again.');
        }

        $redirect = $isSelf ? 'contracts/my_contract/' . $id : 'contracts/view/' . $id;
        redirect($redirect);
    }
    


    /**
     * Bulk renew contracts for active employees who have signed contracts.
     * - Step 1: choose filters + load employees
     * - Step 2: confirm + renew for selected rows
     */
    public function bulk_renew()
    {
        if (!staff_can('edit', 'contracts')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $action = (string)$this->input->post('action', true);

        // Shared filter values (for persistence in the form)
        $filters = [
            'contract_type' => (string)$this->input->post('contract_type', true),
            'start_date'    => (string)$this->input->post('start_date', true),
            'end_date'      => (string)$this->input->post('end_date', true),
            'renew_by'      => (string)$this->input->post('renew_by', true),
            'team_ids'      => (array)$this->input->post('team_ids'),
            'department_ids'=> (array)$this->input->post('department_ids'),
            'position_ids'  => (array)$this->input->post('position_ids'),
            'employee_ids'  => (array)$this->input->post('employee_ids'),
        ];

        $rows = []; // employees + their current contract (if any)

        /* -------------------------------------------------
         * STEP 2: process renewals
         * ------------------------------------------------- */
        if ($action === 'renew') {
            $this->form_validation->set_rules('contract_type', 'Contract Type', 'required|max_length[100]');
            $this->form_validation->set_rules('start_date', 'Start Date', 'required');
            $this->form_validation->set_rules('end_date', 'End Date', 'required');

            $rowsPost = $this->input->post('rows');
            if (!is_array($rowsPost) || empty($rowsPost)) {
                set_alert('danger', 'No employees loaded for renewal.');
                redirect('contracts/bulk_renew');
                return;
            }

            if ($this->form_validation->run() === false) {
                set_alert('danger', strip_tags(validation_errors()));
                redirect('contracts/bulk_renew');
                return;
            }

            $contractType = $filters['contract_type'];
            $startDate    = $filters['start_date'];
            $endDate      = $filters['end_date'];

            $renewedCount = 0;

            foreach ($rowsPost as $row) {
                // Only renew rows explicitly checked
                if (empty($row['renew'])) {
                    continue;
                }

                $contractId   = (int)($row['contract_id'] ?? 0);
                $userId       = (int)($row['user_id'] ?? 0);
                $rowIndex     = (int)($row['row_index'] ?? -1);
                $noticePeriod = (int)($row['notice_period_days'] ?? 30);
                $existingFile = (string)($row['existing_contract_file'] ?? '');

                if ($contractId <= 0 || $userId <= 0) {
                    continue;
                }

                // Load latest contract record
                $contract = $this->contracts->get($contractId);
                if (!$contract) {
                    continue;
                }

                // --- Optional per-row contract file upload ---
                $newFileName = $existingFile;
                $fileField   = 'contract_file_' . $rowIndex;

                if (!empty($_FILES[$fileField]['name'])) {
                    $upload_path = FCPATH . 'uploads/users/contracts/';
                    if (!is_dir($upload_path)) {
                        @mkdir($upload_path, 0755, true);
                    }

                    $config = [
                        'upload_path'   => $upload_path,
                        'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png',
                        'max_size'      => 20480,
                        'encrypt_name'  => true,
                    ];

                    $this->load->library('upload');
                    $this->upload->initialize($config);

                    if ($this->upload->do_upload($fileField)) {
                        $upload     = $this->upload->data();
                        $newFileName = $upload['file_name'];

                        // Delete old file if it existed
                        if (!empty($existingFile)) {
                            $oldPath = $upload_path . $existingFile;
                            if (is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    } else {
                        // On upload error, keep existing file and continue
                        $msg = strip_tags($this->upload->display_errors('', ''));
                        log_message('error', 'Bulk renew contract file upload failed (contract_id='.$contractId.'): '.$msg);
                    }
                }

                // --- Perform renew (sets status=renewed, last_renew_at, end_date, notice_period_days) ---
                $this->contracts->renew($contractId, [
                    'end_date'           => $endDate,
                    'renew_at'           => date('Y-m-d H:i:s'),
                    'notice_period_days' => $noticePeriod,
                ]);

                // --- Update shared fields (type, start_date, file, audit) ---
                $this->contracts->update($contractId, [
                    'contract_type' => $contractType,
                    'start_date'    => $startDate,
                    'contract_file' => $newFileName,
                    'updated_by'    => $this->uid,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

                // Reload with joins for email data (department_name etc.)
                $contractAfter = $this->contracts->get($contractId);
                $userRow       = $this->users->get_user_by_id($userId);

                if ($contractAfter && $userRow) {
                    $this->send_contract_renew_email($contractAfter, $userRow);
                    $renewedCount++;
                }
            }

            if ($renewedCount > 0) {
                set_alert('success', 'Contracts renewed successfully for '.$renewedCount.' employees.');
            } else {
                set_alert('warning', 'No contracts were renewed. Please ensure you have selected employees.');
            }

            redirect('contracts/bulk_renew');
            return;
        }

        /* -------------------------------------------------
         * STEP 1: load employees for renewal
         * ------------------------------------------------- */
        if ($action === 'load') {
            $renewBy = $filters['renew_by'];

            // Base query: active employees with signed contracts (not soft deleted)
            $this->db->select('
                u.id                AS user_id,
                u.fullname,
                u.firstname,
                u.lastname,
                u.emp_id,
                u.emp_title,
                u.emp_department,
                u.emp_team,
                u.current_salary,
                c.id                AS contract_id,
                c.contract_type,
                c.start_date,
                c.end_date,
                c.notice_period_days,
                c.contract_file,
                d.name              AS department_name,
                p.title             AS position_title
            ');
            $this->db->from('staff_contracts c');
            $this->db->join('users u', 'u.id = c.user_id', 'inner');
            $this->db->join('departments d', 'd.id = u.emp_department', 'left');
            $this->db->join('hrm_positions p', 'p.id = u.emp_title', 'left');

            $this->db->where('u.is_active', 1);
            $this->db->where('c.status', 'signed');
            $this->db->where('c.deleted_at IS NULL', null, false);

            // Filter by dimension
            if ($renewBy === 'team' && !empty($filters['team_ids'])) {
                $this->db->where_in('u.emp_team', array_map('intval', $filters['team_ids']));
            } elseif ($renewBy === 'department' && !empty($filters['department_ids'])) {
                $this->db->where_in('u.emp_department', array_map('intval', $filters['department_ids']));
            } elseif ($renewBy === 'position' && !empty($filters['position_ids'])) {
                $this->db->where_in('u.emp_title', array_map('intval', $filters['position_ids']));
            } elseif ($renewBy === 'employees' && !empty($filters['employee_ids'])) {
                $this->db->where_in('u.id', array_map('intval', $filters['employee_ids']));
            }

            $this->db->order_by('u.fullname', 'ASC');
            $rows = $this->db->get()->result_array();
        }

        // Lookup data for filters
        $contract_types = $this->get_contract_types();

        $departments = [];
        if ($this->db->table_exists('departments')) {
            foreach ($this->db->get('departments')->result_array() as $d) {
                $departments[(int)$d['id']] = $d['name'];
            }
        }

        $positions = [];
        if ($this->db->table_exists('hrm_positions')) {
            foreach ($this->db->get('hrm_positions')->result_array() as $p) {
                $positions[(int)$p['id']] = $p['title'];
            }
        }

        $teams = [];
        if ($this->db->table_exists('teams')) {
            foreach ($this->db->get('teams')->result_array() as $t) {
                $teams[(int)$t['id']] = $t['name'];
            }
        }

        // Minimal staff list for "Specific Employees"
        $staff_list = method_exists($this->users, 'get_active_minimal_list')
            ? $this->users->get_active_minimal_list()
            : [];

        $view_data = [
            'contract_types' => $contract_types,
            'departments'    => $departments,
            'positions'      => $positions,
            'teams'          => $teams,
            'staff_list'     => $staff_list,
            'rows'           => $rows,
            'filters'        => $filters,
        ];

        $layout_data = [
            'page_title' => 'Bulk Contract Renewal',
            'subview'    => 'contracts/bulk_renew',
            'view_data'  => $view_data,
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /**
     * Read configured contract types from options as array.
     */
    private function get_contract_types(): array
    {
        $json = function_exists('get_setting') ? (string)get_setting('contract_types', '[]') : '[]';
        $arr  = json_decode($json, true);
        if (!is_array($arr)) {
            return [];
        }
        $arr = array_map(static function ($v) { return trim((string)$v); }, $arr);
        return array_values(array_unique(array_filter($arr, 'strlen')));
    }

/**
 * Send contract renewal email + in-app notification to employee.
 * Used by bulk renew and can be reused for single renew.
 *
 * @param array $contract staff_contracts row (latest state)
 * @param array $user     users row for the contract holder
 */
private function send_contract_renew_email(array $contract, array $user): void
{
    if (!function_exists('app_mailer')) {
        return;
    }

    // 1) Resolve recipient email
    $toEmail = trim((string)($user['email'] ?? ''));
    if ($toEmail === '') {
        return;
    }

    // 2) Brand / basic info
    $brand = function_exists('get_setting')
        ? (get_setting('companyname') ?: 'Our Company')
        : 'Our Company';

    $userFullname = $user['fullname']
        ?? trim(((string)($user['firstname'] ?? '')) . ' ' . ((string)($user['lastname'] ?? '')));

    $empIdDisplay = !empty($user['emp_id'])
        ? emp_id_display($user['emp_id'])
        : '';

    // Job title from emp_title => hrm_positions.title
    $jobTitle = '';
    if (!empty($user['emp_title'])) {
        $jobTitle = resolve_emp_title((int)$user['emp_title']); // your helper that hits hrm_positions
    }

    // Department name
    $departmentName = '';
    if (!empty($user['emp_department'])) {
        $deptRow = $this->db
            ->select('name')
            ->from('departments')
            ->where('id', (int)$user['emp_department'])
            ->get()
            ->row_array();
        if ($deptRow) {
            $departmentName = (string)$deptRow['name'];
        }
    }

    // Team name
    $teamName = '';
    if (!empty($user['emp_team'])) {
        $teamRow = $this->db
            ->select('name')
            ->from('teams')
            ->where('id', (int)$user['emp_team'])
            ->get()
            ->row_array();
        if ($teamRow) {
            $teamName = (string)$teamRow['name'];
        } else {
            // If you store team name directly in users.emp_team, fallback to that
            $teamName = (string)$user['emp_team'];
        }
    }

    // Dates & salary
    $startDate = !empty($contract['start_date']) ? format_date($contract['start_date']) : '';
    $endDate   = !empty($contract['end_date'])   ? format_date($contract['end_date'])   : '';

    $currentSalaryText = '';
    if (!empty($contract['current_salary'])) {
        $currentSalaryText = c_format((float)$contract['current_salary']);
    }

    // 3) Build URL to the employee-facing contract view
    $contractId  = (int)($contract['id'] ?? 0);
    $contractUrl = $contractId > 0
        ? base_url('contracts/my_contract/' . $contractId)
        : base_url('contracts');

    // 4) Prepare data for the HTML template
    $data = [
        'brand'           => $brand,
        'user_fullname'   => $userFullname,
        'emp_id'          => $empIdDisplay,
        'contract_type'   => $contract['contract_type'] ?? 'Employment Contract',
        'job_title'       => $jobTitle,
        'department_name' => $departmentName,
        'team_name'       => $teamName,
        'start_date'      => $startDate,
        'end_date'        => $endDate,
        'current_salary'  => $currentSalaryText,
        'contract_url'    => $contractUrl,
    ];

    // 5) Render your template: application/views/emails/users/contract_renew_html.php
    $html = $this->load->view('emails/users/contract_renew_html', $data, true);

    // 6) Attach the **latest** contract file (if present)
    $attachments = [];
    if (!empty($contract['contract_file'])) {
        $filePath = FCPATH . 'uploads/users/contracts/' . $contract['contract_file'];
        if (is_file($filePath)) {
            $attachments[] = $filePath;
        }
    }

    // 7) Send email via app_mailer with HTML body + attachment
    app_mailer()->send([
        'to'          => $toEmail,
        'subject'     => 'Your employment contract has been renewed',
        'message'     => $html,     // use the rendered HTML template
        'mailtype'    => 'html',
        'attachments' => $attachments,
    ]);

    // 8) In-app notification to employee
    if (function_exists('notify_user') && !empty($user['id'])) {
        notify_user(
            (int)$user['id'],
            'contracts',
            'Contract renewed',
            'Your employment contract has been renewed. Please review the updated contract.',
            $contractUrl,
            ['channels' => ['in_app']]
        );
    }
}

    
}
