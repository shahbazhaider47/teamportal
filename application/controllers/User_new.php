<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_new extends App_Controller {

    public function __construct() {
        
        parent::__construct();

        $this->load->model('User_model');
        $this->load->model('Teams_model');
        $this->load->model('Department_model');
        $this->load->model('Hrm_positions_model');
        $this->load->model('Hrm_allowances_model');        
        $this->load->model('Roles_model');
        $this->load->model('Activity_log_model');
        $this->load->library(['form_validation', 'upload']);
    }

    protected function log_activity(string $action)
    {
        $this->Activity_log_model->add([
            'user_id'    => $this->session->userdata('user_id'),
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

public function add()
{
    if (! $this->session->userdata('is_logged_in')) {
        redirect('authentication/login');
        return;
    }
    
    if (! staff_can('view_global', 'users')) {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    // Log attempt to add user
    log_message('info', 'User add attempt started by user ID: ' . $this->session->userdata('user_id'));

    // MINIMAL REQUIRED VALIDATIONS ONLY
    $this->form_validation->set_rules('username',  'Username',   'trim|required');
    $this->form_validation->set_rules('email',     'Email',      'trim|required|valid_email');
    $this->form_validation->set_rules('password',  'Password',   'trim|required');
    $this->form_validation->set_rules('user_role', 'User Role',  'trim|required');
    $this->form_validation->set_rules('emp_id', 'Employee ID', 'required');

    if ($this->form_validation->run() === FALSE) {
        $validationErrors = validation_errors();
        $validationMessage = strip_tags($validationErrors);
        
        // Log validation failure
        log_message('error', 'User add validation failed: ' . $validationMessage);
        
        if ($this->input->is_ajax_request()) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(422)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $validationMessage
                ]));
        }

        $layout_data = [
            'page_title' => 'Add New User',
            'subview'    => 'users/add',
            'view_data'  => [],
        ];
        $this->load->view('layouts/master', $layout_data);
        return;
    }

    $posted_username = trim((string)$this->input->post('username', TRUE));
    $posted_email    = strtolower(trim((string)$this->input->post('email', TRUE)));
    $posted_emp_id   = trim((string)$this->input->post('emp_id', TRUE));

    // Log user data being processed
    log_message('info', 'Processing user add - Username: ' . $posted_username . ', Email: ' . $posted_email . ', EMP ID: ' . $posted_emp_id);

    // Check for duplicate username
    if (method_exists($this->User_model, 'exists_username')) {
        if ($this->User_model->exists_username($posted_username, 0)) {
            $msg = 'Username is already taken. Please choose a different username.';
            log_message('error', 'Duplicate username: ' . $posted_username);
            
            if ($this->input->is_ajax_request()) {
                return $this->output->set_content_type('application/json')
                    ->set_status_header(422)
                    ->set_output(json_encode(['success' => false, 'message' => $msg]));
            }
            set_alert('warning', $msg);
            redirect('users');
            return;
        }
    }

    // Check for duplicate email
    if (method_exists($this->User_model, 'email_exists_for_other_user')) {
        if ($this->User_model->email_exists_for_other_user($posted_email, 0)) {
            $msg = 'Email is already in use by another user.';
            log_message('error', 'Duplicate email: ' . $posted_email);
            
            if ($this->input->is_ajax_request()) {
                return $this->output->set_content_type('application/json')
                    ->set_status_header(422)
                    ->set_output(json_encode(['success' => false, 'message' => $msg]));
            }
            set_alert('warning', $msg);
            redirect('users');
            return;
        }
    }

    // Check for duplicate employee ID
    if ($posted_emp_id !== '' && method_exists($this->User_model, 'exists_emp_id')) {
        if ($this->User_model->exists_emp_id($posted_emp_id, 0)) {
            $msg = 'Employee ID is already assigned to another user. Please provide a unique Employee ID.';
            log_message('error', 'Duplicate employee ID: ' . $posted_emp_id);
            
            if ($this->input->is_ajax_request()) {
                return $this->output->set_content_type('application/json')
                    ->set_status_header(422)
                    ->set_output(json_encode(['success' => false, 'message' => $msg]));
            }
            set_alert('warning', $msg);
            redirect('users');
            return;
        }
    }

    // Helper functions for data cleaning
    $nullIfEmpty = function($v){ return ($v === '' || $v === null) ? null : $v; };
    $intOrNull   = function($v) use ($nullIfEmpty) { $v = $nullIfEmpty($v); return ($v === null) ? null : (int)$v; };
    $dateOrNull  = function($v) use ($nullIfEmpty) {
        $v = $nullIfEmpty($v);
        if ($v === null) return null;
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    };
    $moneyOrNull = function($v) use ($nullIfEmpty) {
        $v = $nullIfEmpty($v);
        if ($v === null) return null;
        $num = preg_replace('/[^\d.-]/', '', (string)$v);
        return ($num === '' ? null : (float)$num);
    };

    // Handle profile image upload
    $profileFile = null;
    if (!empty($_FILES['profile_image']['name'])) {
        log_message('info', 'Processing profile image upload');
        
        $config = [
            'upload_path'   => FCPATH . 'uploads/users/profile/',
            'allowed_types' => 'jpg|jpeg|png|gif|webp',
            'encrypt_name'  => true,
            'max_size'      => 2048,
        ];
        
        if (!is_dir($config['upload_path'])) {
            @mkdir($config['upload_path'], 0755, true);
        }
        
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('profile_image')) {
            $uploadError = $this->upload->display_errors('', '');
            log_message('error', 'Profile image upload failed: ' . $uploadError);
            
            if ($this->input->is_ajax_request()) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode(['success' => false, 'message' => $uploadError]));
            }
            set_alert('danger', $uploadError);
            redirect('users');
            return;
        }
        
        $profileFile = $this->upload->data('file_name');
        log_message('info', 'Profile image uploaded: ' . $profileFile);
    }

    // Process allowances
    $allowance_ids  = $this->input->post('allowance_ids');
    $allowancesJson = null;
    if (is_array($allowance_ids)) {
        $allowance_ids  = array_values(array_filter($allowance_ids, fn($x) => $x !== '' && $x !== null));
        $allowancesJson = json_encode($allowance_ids, JSON_UNESCAPED_UNICODE);
        log_message('debug', 'Allowances selected: ' . count($allowance_ids));
    }

    // Determine employee title/position
    $posted_emp_title = $this->input->post('emp_title', TRUE);
    $posted_position  = $this->input->post('position_id', TRUE);
    
    $emp_title_id =
        $posted_emp_title !== '' ? (int)$posted_emp_title
      : ($posted_position !== '' ? (int)$posted_position : null);

    $role      = strtolower($this->input->post('user_role', TRUE) ?: 'employee');
    $is_active = $this->input->post('is_active', TRUE);
    $is_active = ($is_active === null) ? 1 : (int)$is_active;

    // Prepare payload - MUST MATCH MODEL FIELDS
    $payload = [
        'username'                          => $posted_username,
        'firstname'                         => $this->input->post('firstname', TRUE),
        'initials'                          => $this->input->post('initials', TRUE),        
        'lastname'                          => $this->input->post('lastname', TRUE),
        'fullname'                          => $this->input->post('fullname', TRUE) ?: trim(($this->input->post('firstname', TRUE) ?? '').' '.($this->input->post('lastname', TRUE) ?? '')),
        'email'                             => $posted_email,
        'password'                          => $this->input->post('password', TRUE),
        'password_token'                    => null,
        'token_expires_at'                  => null,
        'is_active'                         => $is_active,
        'last_login_at'                     => null,
        'gender'                            => $nullIfEmpty(strtolower($this->input->post('gender', TRUE))),
        'emp_dob'                           => $dateOrNull($this->input->post('emp_dob', TRUE)),
        'emp_phone'                         => $nullIfEmpty($this->input->post('emp_phone', TRUE)),
        'profile_image'                     => $profileFile,
        'emp_id'                            => ($posted_emp_id !== '') ? $posted_emp_id : null,
        'emp_title'                         => $emp_title_id,
        'emp_joining'                       => $dateOrNull($this->input->post('emp_joining', TRUE)),
        'emp_department'                    => $nullIfEmpty($this->input->post('emp_department', TRUE)),
        'emp_team'                          => $intOrNull($this->input->post('emp_team', TRUE)),
        'emp_teamlead'                      => $intOrNull($this->input->post('emp_teamlead', TRUE)),
        'emp_manager'                       => $intOrNull($this->input->post('emp_manager', TRUE)),
        'emp_reporting'                     => $intOrNull($this->input->post('emp_reporting', TRUE)),
        'employment_type'                   => $nullIfEmpty($this->input->post('employment_type', TRUE)),
        'contract_type'                     => $nullIfEmpty($this->input->post('contract_type', TRUE)),
        'pay_period'                        => $nullIfEmpty($this->input->post('pay_period', TRUE)),
        'work_location'                     => $nullIfEmpty($this->input->post('work_location', TRUE)),
        'office_id'                         => $nullIfEmpty($this->input->post('office_id', TRUE)),        
        'work_shift'                        => $nullIfEmpty($this->input->post('work_shift', TRUE)), // CHANGED TO MATCH MODEL
        'probation_end_date'                => $dateOrNull($this->input->post('probation_end_date', TRUE)),
        'confirmation_date'                 => $this->input->post('is_confirmed_employee') ? $dateOrNull($this->input->post('confirmation_date', TRUE)) : null,
        'nic_expiry'                        => $dateOrNull($this->input->post('nic_expiry', TRUE)),
        'pay_method'                        => $nullIfEmpty($this->input->post('pay_method', TRUE)), // ADDED - Missing in old version
        'allow_payroll'                     => (int) ($this->input->post('allow_payroll', TRUE) ?: 0), // ADDED - Missing in old version
        'eobi_no'                           => $nullIfEmpty($this->input->post('eobi_no', TRUE)), // ADDED - Missing in old version
        'ntn_no'                            => $nullIfEmpty($this->input->post('ntn_no', TRUE)), // ADDED - Missing in old version
        'emp_grade'                         => $nullIfEmpty($this->input->post('emp_grade', TRUE)),
        'joining_salary'                    => $moneyOrNull($this->input->post('joining_salary', TRUE)),
        'current_salary'                    => $moneyOrNull($this->input->post('current_salary', TRUE)),
        'allowances'                        => $allowancesJson,
        'country'                           => $nullIfEmpty($this->input->post('country', TRUE)),
        'state'                             => $nullIfEmpty($this->input->post('state', TRUE)),
        'city'                              => $nullIfEmpty($this->input->post('city', TRUE)),
        'marital_status'                    => $nullIfEmpty($this->input->post('marital_status', TRUE)),
        'address'                           => $nullIfEmpty($this->input->post('address', TRUE)),
        'current_address'                   => $nullIfEmpty($this->input->post('current_address', TRUE)),
        'national_id'                       => $nullIfEmpty($this->input->post('national_id', TRUE)),
        'passport_no'                       => $nullIfEmpty($this->input->post('passport_no', TRUE)),
        'nationality'                       => $nullIfEmpty($this->input->post('nationality', TRUE)),
        'tax_number'                        => $nullIfEmpty($this->input->post('tax_number', TRUE)),
        'insurance_policy_no'               => $nullIfEmpty($this->input->post('insurance_policy_no', TRUE)),
        'bank_account_number'               => $nullIfEmpty($this->input->post('bank_account_number', TRUE)),
        'bank_name'                         => $nullIfEmpty($this->input->post('bank_name', TRUE)),
        'bank_branch'                       => $nullIfEmpty($this->input->post('bank_branch', TRUE)),
        'bank_code'                         => $nullIfEmpty($this->input->post('bank_code', TRUE)),
        'emergency_contact_name'            => $nullIfEmpty($this->input->post('emergency_contact_name', TRUE)),
        'emergency_contact_phone'           => $nullIfEmpty($this->input->post('emergency_contact_phone', TRUE)),
        'emergency_contact_relationship'    => $nullIfEmpty($this->input->post('emergency_contact_relationship', TRUE)),
        'father_name'                       => $nullIfEmpty($this->input->post('father_name', TRUE)),
        'mother_name'                       => $nullIfEmpty($this->input->post('mother_name', TRUE)),
        'blood_group'                       => $nullIfEmpty($this->input->post('blood_group', TRUE)),
        'qualification'                     => $nullIfEmpty($this->input->post('qualification', TRUE)),
        'religion'                          => $nullIfEmpty($this->input->post('religion', TRUE)),
        'notes'                             => $nullIfEmpty($this->input->post('notes', TRUE)),
        'user_role'                         => $role,
        'dashboard_layout'                  => $nullIfEmpty($this->input->post('dashboard_layout', TRUE)),
    ];

    // Log payload details (excluding password for security)
    $logPayload = $payload;
    unset($logPayload['password']);
    log_message('debug', 'User payload prepared: ' . print_r($logPayload, true));

    try {
        $result = $this->User_model->set_user($payload);
        $ok = is_bool($result) ? $result : ((int)$result > 0);

        if ($ok) {
            $userId = is_int($result) ? $result : 'unknown';
            $successMessage = 'New user added successfully! User ID: ' . $userId . ', Email: ' . $payload['email'];
            
            // Log successful creation
            log_message('info', $successMessage);
            $this->log_activity('Created user: ' . $payload['email'] . ' (ID: ' . $userId . ')');
            
            if ($this->input->is_ajax_request()) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => true,
                        'message' => 'New user added successfully!',
                        'user_id' => $userId
                    ]));
            }
            set_alert('success', 'New user added successfully!');
            redirect('users');
        } else {
            $errorMessage = 'Failed to add user to database. User details: ' . $payload['email'];
            log_message('error', $errorMessage);
            
            if ($this->input->is_ajax_request()) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(500)
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Database error: Failed to add user. Please try again.'
                    ]));
            }
            set_alert('danger', 'Database error: Failed to add user. Please try again.');
            redirect('users');
        }
    } catch (Exception $e) {
        $exceptionMessage = 'Exception while adding user: ' . $e->getMessage();
        log_message('error', $exceptionMessage);
        log_message('debug', 'Exception trace: ' . $e->getTraceAsString());
        
        if ($this->input->is_ajax_request()) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'System error: ' . $e->getMessage()
                ]));
        }
        set_alert('danger', 'System error: ' . $e->getMessage());
        redirect('users');
    }
}

    public function get_dropdown_data() {
        if (!$this->session->userdata('is_logged_in')) {
            show_json_error('Access denied');
            return;
        }

        $data = [
            'positions' => $this->Hrm_positions_model->get_all(),
            'departments' => $this->Department_model->get_all(),
            'teams' => $this->User_model->get_all_teams(),
            'teamLeads' => $this->User_model->get_team_leads(),
            'managers' => $this->User_model->get_managers(),
            'employees' => $this->User_model->get_employees(),
            'allowances' => $this->Hrm_allowances_model->get_all_active(),
            'roles' => $this->Roles_model->get_all_roles(),
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    private function build_add_user_view_data(): array
    {
        if (!function_exists('read_option_array')) {
            function read_option_array($key) {
                $json = function_exists('get_option') ? (string) get_option($key, '[]') : '[]';
                $arr  = json_decode($json, true);
                if (!is_array($arr)) return [];
                $arr = array_map(function($v){ return trim((string)$v); }, $arr);
                return array_values(array_unique(array_filter($arr, 'strlen')));
            }
        }
    
        $roles_rows = $this->Roles_model->get_all_roles();
        $roles = [];
        if (is_array($roles_rows)) {
            foreach ($roles_rows as $r) {
                $name = is_array($r) ? ($r['role_name'] ?? '') : (string)$r;
                if ($name !== '') $roles[] = strtolower($name);
            }
            $roles = array_values(array_unique($roles));
        }
    
        $positions      = method_exists($this->Hrm_positions_model, 'get_all_active')
                        ? $this->Hrm_positions_model->get_all_active()
                        : $this->Hrm_positions_model->get_all();
    
        $emp_department = $this->Department_model->get_all();
    
        $teams          = method_exists($this->Teams_model, 'get_all')
                        ? $this->Teams_model->get_all()
                        : [];
    
        $allowances     = method_exists($this->Hrm_allowances_model, 'get_all_active')
                        ? $this->Hrm_allowances_model->get_all_active()
                        : [];
    
        $teamLeads      = method_exists($this->User_model, 'get_team_leads') ? $this->User_model->get_team_leads() : [];
        $managers       = method_exists($this->User_model, 'get_managers')   ? $this->User_model->get_managers()   : [];
        $employees      = method_exists($this->User_model, 'get_employees')  ? $this->User_model->get_employees()  : [];
    
        $employment_types    = read_option_array('employment_types');
        $contract_types      = read_option_array('contract_types');
        $shift_types         = read_option_array('shift_types');
        $work_location_types = read_option_array('work_location_types');
        $pay_period_types    = read_option_array('pay_period_types');
        $gender_types        = read_option_array('gender_types');
        $marital_statuses    = read_option_array('marital_statuses');
        $relationship_types  = read_option_array('relationship_types');
        $blood_group_types   = read_option_array('blood_group_types');
    
        return [
            'roles'                => $roles,
            'positions'            => $positions,
            'emp_department'       => $emp_department,
            'teams'                => $teams,
            'teamLeads'            => $teamLeads,
            'managers'             => $managers,
            'employees'            => $employees,
            'allowances'           => $allowances,
            'employment_types'     => $employment_types,
            'contract_types'       => $contract_types,
            'shift_types'          => $shift_types,
            'work_location_types'  => $work_location_types,
            'pay_period_types'     => $pay_period_types,
            'gender_types'         => $gender_types,
            'marital_statuses'     => $marital_statuses,
            'relationship_types'   => $relationship_types,
            'blood_group_types'    => $blood_group_types,
        ];
    }

}