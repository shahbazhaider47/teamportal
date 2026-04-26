<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Profile_editor extends App_Controller {

    public function __construct() {
        parent::__construct();
        
        $this->load->model('User_model');
        $this->load->model('Teams_model');
        $this->load->model('Department_model');
        $this->load->model('Hrm_positions_model');
        $this->load->model('Hrm_allowances_model');
        $this->load->model('Roles_model');
        $this->load->model('Hrm_employee_rejoins_model');
        $this->load->library('form_validation');
    }

    /**
     * Edit Personal Profile Section
     */
    public function edit_personal($user_id = null)
    {
        if (!$this->session->userdata('is_logged_in') || !staff_can('edit', 'users')) {
            show_error('Access denied', 403);
            return;
        }
    
        if (!$user_id || !is_numeric($user_id)) {
            set_alert('warning', 'Invalid user ID.');
            redirect('users');
            return;
        }
    
        $user = $this->User_model->get_user_by_id((int)$user_id);
        if (!$user) {
            set_alert('warning', 'User not found.');
            redirect('users');
            return;
        }
    
        // --- Validation (base rules)
        $this->form_validation->set_rules('firstname', 'First Name', 'trim|required');
        $this->form_validation->set_rules('lastname', 'Last Name', 'trim|required');
    
        // normalize email lower-case; still validate as email
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
    
        // username required in payload (we will lock it server-side for non-superadmins)
        $this->form_validation->set_rules('username', 'Username', 'trim|required');
    
        // emp_id required (change if your business logic allows it to be optional)
        $this->form_validation->set_rules('emp_id', 'Employee ID', 'trim|required');
    
        if ($this->form_validation->run() === FALSE) {
            set_alert('warning', strip_tags(validation_errors()));
            redirect("users/view/{$user_id}");
            return;
        }
    
        // --- Normalize inputs
        $posted_username = trim((string) $this->input->post('username', TRUE));
        $posted_email    = strtolower(trim((string) $this->input->post('email', TRUE)));
        $posted_emp_id   = trim((string) $this->input->post('emp_id', TRUE));
    
        // --- Permission guardrail: non-superadmins cannot change username/emp_id
        $is_superadmin = function_exists('is_superadmin') ? is_superadmin() : false;
        if (!$is_superadmin) {
            $posted_username = $user['username']; // lock to existing value
            $posted_emp_id   = $user['emp_id'];   // lock to existing value
        }
    
        // --- Uniqueness checks (exclude current user id)
        // Short-circuit on the first conflict to give clear feedback.
        if ($this->User_model->exists_username($posted_username, (int)$user_id)) {
            set_alert('warning', 'Username is already taken by another user.');
            redirect("users/view/{$user_id}");
            return;
        }
    
        if ($this->User_model->email_exists_for_other_user($posted_email, (int)$user_id)) {
            set_alert('warning', 'Email is already in use by another user.');
            redirect("users/view/{$user_id}");
            return;
        }
    
        if (!empty($posted_emp_id) && $this->User_model->exists_emp_id($posted_emp_id, (int)$user_id)) {
            set_alert('warning', 'Employee ID is already assigned to another user, please add unique employee ID.');
            redirect("users/view/{$user_id}");
            return;
        }
    
        // --- Handle profile image upload
        $profileFile = $user['profile_image'];
        if (!empty($_FILES['profile_image']['name'])) {
            $uploadPath = FCPATH . 'uploads/users/profile/';
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0755, true);
            }
    
            $config = [
                'upload_path'   => $uploadPath,
                'allowed_types' => 'jpg|jpeg|png|gif',
                'max_size'      => 2048,
                'encrypt_name'  => true,
            ];
    
            $this->load->library('upload', $config);
            if ($this->upload->do_upload('profile_image')) {
                $uploadData  = $this->upload->data();
                $newFilename = $uploadData['file_name'];
    
                // Delete old file
                if (!empty($profileFile) && file_exists($uploadPath . $profileFile)) {
                    @unlink($uploadPath . $profileFile);
                }
                $profileFile = $newFilename;
            } else {
                set_alert('warning', $this->upload->display_errors());
                redirect("users/view/{$user_id}");
                return;
            }
        }
    
        // Remove photo if requested
        if ($this->input->post('remove_photo') === '1') {
            $old = $profileFile;
            if (!empty($old) && file_exists(FCPATH . 'uploads/users/profile/' . $old)) {
                @unlink(FCPATH . 'uploads/users/profile/' . $old);
            }
            $profileFile = null;
        }
    
        // --- Build update payload
        $update_data = [
            'username'        => $posted_username,
            'user_role' => $this->input->post('user_role', TRUE) ?: 'employee',            
            'firstname'       => $this->input->post('firstname', TRUE),
            'initials'       => $this->input->post('initials', TRUE),            
            'lastname'        => $this->input->post('lastname', TRUE),
            'fullname'        => $this->input->post('fullname', TRUE) ?: trim($this->input->post('firstname', TRUE) . ' ' . $this->input->post('lastname', TRUE)),
            'email'           => $posted_email,
            'emp_id'          => $posted_emp_id ?: null,
            'emp_phone'       => $this->input->post('emp_phone', TRUE) ?: null,
            'gender'          => $this->input->post('gender', TRUE) ?: null,
            'emp_dob'         => $this->input->post('emp_dob', TRUE) ? date('Y-m-d', strtotime($this->input->post('emp_dob', TRUE))) : null,
            'marital_status'  => $this->input->post('marital_status', TRUE) ?: null,
            'current_address' => $this->input->post('current_address', TRUE) ?: null,
            'address'         => $this->input->post('address', TRUE) ?: null,
            'city'            => $this->input->post('city', TRUE) ?: null,
            'state'           => $this->input->post('state', TRUE) ?: null,
            'country'         => $this->input->post('country', TRUE) ?: null,
            'national_id'     => $this->input->post('national_id', TRUE) ?: null,
            'passport_no'     => $this->input->post('passport_no', TRUE) ?: null,
            'nationality'     => $this->input->post('nationality', TRUE) ?: null,
            'profile_image'   => $profileFile,
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
    
        if ($this->User_model->update_user((int)$user_id, $update_data)) {
            set_alert('success', 'Personal profile updated successfully.');
        } else {
            set_alert('warning', 'Failed to update personal profile.');
        }
    
        redirect("users/view/{$user_id}");
    }

    /**
     * Edit Employee Address Information Section
     */
    public function edit_address($user_id = null) {
        if (!$this->session->userdata('is_logged_in') || !staff_can('edit', 'users')) {
            show_error('Access denied', 403);
            return;
        }

        if (!$user_id || !is_numeric($user_id)) {
            set_alert('warning', 'Invalid user ID.');
            redirect('users');
            return;
        }

        $user = $this->User_model->get_user_by_id((int)$user_id);
        if (!$user) {
            set_alert('warning', 'User not found.');
            redirect('users');
            return;
        }

        $update_data = [
            'current_address' => $this->input->post('current_address', TRUE) ?: null,
            'address'         => $this->input->post('address', TRUE) ?: null,
            'city'            => $this->input->post('city', TRUE) ?: null,
            'state'           => $this->input->post('state', TRUE) ?: null,
            'country'         => $this->input->post('country', TRUE) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->User_model->update_user((int)$user_id, $update_data)) {
            set_alert('success', 'Address updated successfully.');
        } else {
            set_alert('warning', 'Failed to update address.');
        }

        redirect("users/view/{$user_id}");
    }
    
    /**
     * Edit Official Information Section
     */
    public function edit_official($user_id = null) {
        if (!$this->session->userdata('is_logged_in') || !staff_can('edit', 'users')) {
            show_error('Access denied', 403);
            return;
        }

        if (!$user_id || !is_numeric($user_id)) {
            set_alert('warning', 'Invalid user ID.');
            redirect('users');
            return;
        }

        $user = $this->User_model->get_user_by_id((int)$user_id);
        if (!$user) {
            set_alert('warning', 'User not found.');
            redirect('users');
            return;
        }

        $this->form_validation->set_rules('emp_title', 'Position', 'trim');
        $this->form_validation->set_rules('emp_department', 'Department', 'trim');
        $this->form_validation->set_rules('emp_team', 'Team', 'trim');

        if ($this->form_validation->run() === FALSE) {
            set_alert('warning', strip_tags(validation_errors()));
            redirect("users/view/{$user_id}");
            return;
        }
        
        // In Profile_editor controller - update the edit_official method
        $update_data = [
            'emp_title' => $this->input->post('emp_title', TRUE) ? (int)$this->input->post('emp_title', TRUE) : null,
            'employment_type' => $this->input->post('employment_type', TRUE) ?: null,
            'contract_type' => $this->input->post('contract_type', TRUE) ?: null,
            'work_shift' => $this->input->post('work_shift', TRUE) ?: null,
            'work_location' => $this->input->post('work_location', TRUE) ?: null,
            'office_id' => $this->input->post('office_id', TRUE) ?: null,
            'emp_joining' => $this->input->post('emp_joining', TRUE) ? date('Y-m-d', strtotime($this->input->post('emp_joining', TRUE))) : null,
            'confirmation_date' => $this->input->post('confirmation_date', TRUE) ? date('Y-m-d', strtotime($this->input->post('confirmation_date', TRUE))) : null,
            'probation_end_date' => $this->input->post('probation_end_date', TRUE) ? date('Y-m-d', strtotime($this->input->post('probation_end_date', TRUE))) : null,
            'emp_department' => $this->input->post('emp_department', TRUE) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->User_model->update_user((int)$user_id, $update_data)) {
            set_alert('success', 'Official information updated successfully.');
        } else {
            set_alert('warning', 'Failed to update official information.');
        }

        redirect("users/view/{$user_id}");
    }

    /**
     * Edit Salary Information Section
     */
    public function edit_salary($user_id = null) {
        if (!$this->session->userdata('is_logged_in') || !staff_can('edit', 'users')) {
            show_error('Access denied', 403);
            return;
        }

        if (!$user_id || !is_numeric($user_id)) {
            set_alert('warning', 'Invalid user ID.');
            redirect('users');
            return;
        }

        $user = $this->User_model->get_user_by_id((int)$user_id);
        if (!$user) {
            set_alert('warning', 'User not found.');
            redirect('users');
            return;
        }

        $update_data = [
            'pay_period' => $this->input->post('pay_period', TRUE) ?: null,
            'joining_salary' => $this->input->post('joining_salary', TRUE) ? (float)$this->input->post('joining_salary', TRUE) : null,
            'current_salary' => $this->input->post('current_salary', TRUE) ? (float)$this->input->post('current_salary', TRUE) : null,
            'last_increment_date' => $this->input->post('last_increment_date', TRUE) ? date('Y-m-d', strtotime($this->input->post('last_increment_date', TRUE))) : null,
            'tax_number' => $this->input->post('tax_number', TRUE) ?: null,
            'insurance_policy_no' => $this->input->post('insurance_policy_no', TRUE) ?: null,
            'bank_name' => $this->input->post('bank_name', TRUE) ?: null,
            'bank_branch' => $this->input->post('bank_branch', TRUE) ?: null,
            'bank_account_number' => $this->input->post('bank_account_number', TRUE) ?: null,
            'bank_code' => $this->input->post('bank_code', TRUE) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Handle allowances
        $allowance_ids = $this->input->post('allowance_ids');
        if (is_array($allowance_ids)) {
            $allowance_ids = array_values(array_filter($allowance_ids, fn($x) => $x !== '' && $x !== null));
            $update_data['allowances'] = json_encode($allowance_ids, JSON_UNESCAPED_UNICODE);
        }

        if ($this->User_model->update_user((int)$user_id, $update_data)) {
            set_alert('success', 'Salary information updated successfully.');
        } else {
            set_alert('warning', 'Failed to update salary information.');
        }

        redirect("users/view/{$user_id}");
    }

    /**
     * Edit Emergency Information Section
     */
    public function edit_emergency($user_id = null) {
        if (!$this->session->userdata('is_logged_in') || !staff_can('edit', 'users')) {
            show_error('Access denied', 403);
            return;
        }

        if (!$user_id || !is_numeric($user_id)) {
            set_alert('warning', 'Invalid user ID.');
            redirect('users');
            return;
        }

        $user = $this->User_model->get_user_by_id((int)$user_id);
        if (!$user) {
            set_alert('warning', 'User not found.');
            redirect('users');
            return;
        }

        $update_data = [
            'blood_group' => $this->input->post('blood_group', TRUE) ?: null,
            'father_name' => $this->input->post('father_name', TRUE) ?: null,
            'mother_name' => $this->input->post('mother_name', TRUE) ?: null,
            'emergency_contact_name' => $this->input->post('emergency_contact_name', TRUE) ?: null,
            'emergency_contact_phone' => $this->input->post('emergency_contact_phone', TRUE) ?: null,
            'emergency_contact_relationship' => $this->input->post('emergency_contact_relationship', TRUE) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->User_model->update_user((int)$user_id, $update_data)) {
            set_alert('success', 'Emergency information updated successfully.');
        } else {
            set_alert('warning', 'Failed to update emergency information.');
        }

        redirect("users/view/{$user_id}");
    }

    /**
     * Edit Employee Team Section
     */
    public function edit_team($user_id = null)
    {
        if (!staff_can('edit', 'users')) {
            show_error('Access denied', 403);
        }
    
        $user = $this->User_model->get_user_by_id((int)$user_id);
        if (!$user) {
            set_alert('warning', 'User not found.');
            redirect('users');
        }
    
        $role = strtolower($user['user_role'] ?? 'employee');
    
        $emp_team      = $this->input->post('emp_team', true) ?: null;
        $emp_teamlead  = $this->input->post('emp_teamlead', true) ?: null;
        $emp_manager   = $this->input->post('emp_manager', true) ?: null;
        $emp_reporting = $this->input->post('emp_reporting', true) ?: null;
    
        // Normalize
        $emp_team      = $emp_team !== null ? (int)$emp_team : null;
        $emp_teamlead  = $emp_teamlead !== null ? (int)$emp_teamlead : null;
        $emp_manager   = $emp_manager !== null ? (int)$emp_manager : null;
        $emp_reporting = $emp_reporting !== null ? (int)$emp_reporting : null;
    
        $validate_role = function ($targetId, $requiredRole) use ($user_id) {
            if (!$targetId) return true;
            if ($targetId == $user_id) return false;
    
            $u = $this->User_model->get_user_by_id($targetId);
            return $u && strtolower($u['user_role']) === $requiredRole;
        };
    
        if ($role === 'employee') {
            if (!$validate_role($emp_teamlead, 'teamlead')) {
                set_alert('warning', 'Invalid Team Lead selected.');
                redirect("users/view/{$user_id}");
            }
            $emp_manager = $emp_reporting = null;
    
        } elseif ($role === 'teamlead') {
            if (!$validate_role($emp_manager, 'manager')) {
                set_alert('warning', 'Invalid Manager selected.');
                redirect("users/view/{$user_id}");
            }
            $emp_teamlead = $emp_reporting = null;
    
        } elseif ($role === 'manager') {
            if (!$validate_role($emp_reporting, 'director')) {
                set_alert('warning', 'Invalid Director selected.');
                redirect("users/view/{$user_id}");
            }
            $emp_teamlead = $emp_manager = null;
    
        } else {
            // Director or other roles
            $emp_teamlead = $emp_manager = $emp_reporting = null;
        }
    
        $this->User_model->update_user($user_id, [
            'emp_team'      => $emp_team,
            'emp_teamlead'  => $emp_teamlead,
            'emp_manager'   => $emp_manager,
            'emp_reporting' => $emp_reporting,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    
        set_alert('success', 'Team information updated successfully.');
        redirect("users/view/{$user_id}");
    }
    
    
        /**
         * Get dropdown data for all sections (used in modals)
         */
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
    
    public function change_password($user_id = null)
    {
        // Auth + permission guard (admin-style)
        if (!$this->session->userdata('is_logged_in') || !staff_can('edit', 'users')) {
            show_error('Access denied', 403);
            return;
        }
    
        if (!$user_id || !is_numeric($user_id)) {
            set_alert('warning', 'Invalid user ID.');
            redirect('users');
            return;
        }
        $user_id = (int) $user_id;
    
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_404();
            return;
        }
    
        // Validate inputs
        $this->form_validation->set_rules('new_password',     'New Password',     'trim|required|min_length[8]');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|required|matches[new_password]');
    
        if ($this->form_validation->run() === FALSE) {
            set_alert('danger', strip_tags(validation_errors()) ?: 'Validation failed.');
            redirect("users/view/{$user_id}");
            return;
        }
    
        // Fetch target user
        $user = $this->User_model->get_user_by_id($user_id);
        if (!$user) {
            set_alert('warning', 'User not found.');
            redirect('users');
            return;
        }
    
        // Hash & update
        $new  = (string) $this->input->post('new_password', true);
        $hash = password_hash($new, PASSWORD_DEFAULT);
    
        $data = [
            'password'         => $hash,
            'password_token'   => null,
            'token_expires_at' => null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
    
        $ok = false;
        if (method_exists($this->User_model, 'update_user')) {
            $ok = $this->User_model->update_user($user_id, $data);
        } elseif (method_exists($this->User_model, 'update')) {
            $ok = $this->User_model->update($user_id, $data);
        } else {
            $ok = $this->db->where('id', $user_id)->update('users', $data);
        }
    
        if (!$ok) {
            set_alert('danger', 'Unable to change password.');
            redirect("users/view/{$user_id}");
            return;
        }
    
        // Log + notify (house style)
        if (method_exists($this, 'log_activity')) {
            $this->log_activity("Changed password for user ID {$user_id}");
        }
        if (function_exists('notify_user')) {
            $when  = date('M j, Y H:i');
            $actor = function_exists('staff_full_name') ? staff_full_name() : 'System';
            notify_user(1, 'users', 'Password changed', "{$actor} changed the password for {$user['firstname']} {$user['lastname']} on {$when}.");
            notify_user($user_id, 'users', 'Your password was changed', "Your account password was changed on {$when}.");
        }
    
        // --- Force logout the target user (no DB schema changes)
        $currentUserId = (int) ($this->session->userdata('user_id') ?: 0);
        if ($currentUserId === $user_id) {
            // Self-change: kill own session immediately
            set_alert('success', 'Password changed. Please sign in again.');
            $this->session->sess_destroy();
            redirect('authentication/login');
            return;
        } else {
            // Admin changed someone else: best-effort kill their active sessions
            $this->force_logout_user($user_id);
            set_alert('success', 'Password changed successfully. The user has been logged out.');
            redirect("users/view/{$user_id}");
            return;
        }
    }
    
    /**
     * Best-effort: invalidate all active sessions for a given user without DB schema changes.
     * Supports:
     * - sess_driver = 'database' → deletes rows by user_id (if column exists) or by matching serialized data blob.
     * - sess_driver = 'files'    → scans session files under sess_save_path and unlinks those containing this user_id.
     * Other drivers fall back to no-op (cannot reliably enumerate sessions).
     */
    private function force_logout_user(int $targetUserId): void
    {
        $driver = (string) $this->config->item('sess_driver');
        $save   = (string) $this->config->item('sess_save_path');
    
        // Database driver
        if ($driver === 'database' && !empty($save)) {
            $table = $save;
    
            // A) If a dedicated user_id column exists, simple delete
            if ($this->db->field_exists('user_id', $table)) {
                // Delete all sessions with that user_id
                $this->db->where('user_id', $targetUserId)->delete($table);
                return;
            }
    
            // B) Otherwise, match inside the serialized/JSON data column (usually 'data')
            if ($this->db->field_exists('data', $table)) {
                // Common encodings/patterns
                $patterns = [
                    'user_id|i:' . $targetUserId,       // PHP serialize int
                    '"user_id";i:' . $targetUserId,     // serialized w/ quotes
                    '"user_id":' . $targetUserId,       // JSON-like
                    's:7:"user_id";i:' . $targetUserId, // serialized with length prefix
                ];
                $this->db->group_start();
                foreach ($patterns as $idx => $p) {
                    if ($idx === 0) $this->db->like('data', $p, 'both', false);
                    else            $this->db->or_like('data', $p, 'both', false);
                }
                $this->db->group_end();
                $this->db->delete($table);
                return;
            }
            // If we can’t identify a column, we stop silently.
            return;
        }
    
        // Files driver
        if ($driver === 'files' && !empty($save) && is_dir($save)) {
            $files = @glob(rtrim($save, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ci_session*');
            if ($files) {
                foreach ($files as $file) {
                    // Read a small chunk should be enough; if not, read all
                    $data = @file_get_contents($file);
                    if ($data === false) continue;
                    // Look for common markers of user_id in serialized session payload
                    if (strpos($data, 'user_id|i:' . $targetUserId) !== false
                     || strpos($data, '"user_id";i:' . $targetUserId) !== false
                     || strpos($data, '"user_id":' . $targetUserId) !== false
                     || strpos($data, 's:7:"user_id";i:' . $targetUserId) !== false) {
                        @unlink($file);
                    }
                }
            }
            return;
        }
    }
    
    public function reactivate($user_id = null)
    {
        if (!$this->session->userdata('is_logged_in') || !staff_can('edit', 'users')) {
            show_error('Access denied', 403);
            return;
        }
    
        if (!$user_id || !is_numeric($user_id)) {
            set_alert('warning', 'Invalid user ID.');
            redirect('users');
            return;
        }
        $user_id = (int)$user_id;
    
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_404();
            return;
        }
    
        $user = $this->User_model->get_user_by_id($user_id);
        if (!$user) {
            set_alert('warning', 'User not found.');
            redirect('users');
            return;
        }
    
        // Guardrail: only reactivate if currently inactive
        if ((int)($user['is_active'] ?? 0) === 1) {
            set_alert('warning', 'User is already active.');
            redirect("users/view/{$user_id}");
            return;
        }
    
        // Validation
        $this->form_validation->set_rules('rejoin_date',  'Rejoin Date', 'trim|required');
        $this->form_validation->set_rules('rejoin_reson', 'Rejoin Reason', 'trim|required');
    
        if ($this->form_validation->run() === FALSE) {
            set_alert('warning', strip_tags(validation_errors()) ?: 'Validation failed.');
            redirect("users/view/{$user_id}");
            return;
        }
    
        // Normalize
        $rejoin_date  = $this->input->post('rejoin_date', true);
        $rejoin_date  = $rejoin_date ? date('Y-m-d', strtotime($rejoin_date)) : null;
    
        $rejoin_reson = trim((string)$this->input->post('rejoin_reson', true));
        $custom_reson = trim((string)$this->input->post('rejoin_reson_custom', true));
    
        // If UI sends "Other", require custom
        if (strtolower($rejoin_reson) === 'other') {
            if ($custom_reson === '') {
                set_alert('warning', 'Please provide a custom rejoin reason.');
                redirect("users/view/{$user_id}");
                return;
            }
            $rejoin_reson = $custom_reson;
        }
    
        $actor_id = (int)($this->session->userdata('user_id') ?: 0);
    
        // 1) Update users + delete exit record in ONE transaction
        $ok = $this->User_model->reactivate_user($user_id, [
            'is_rejoined'  => 1,
            'rejoin_date'  => $rejoin_date,
            'rejoin_reson' => $rejoin_reson,
            'updated_by'   => $actor_id,
        ]);
    
        if (!$ok) {
            set_alert('danger', 'Failed to reactivate user. Database transaction rolled back.');
            redirect("users/view/{$user_id}");
            return;
        }
    
        // 2) Insert history row (separate table)
        $this->Hrm_employee_rejoins_model->add([
            'user_id'       => $user_id,
            'status'        => 1,
            'is_rejoined'   => 1,
            'rejoin_date'   => $rejoin_date,
            'rejoin_reson'  => $rejoin_reson,
            'updated_by'    => $actor_id,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    
        set_alert('success', 'User reactivated successfully. Exit record removed and rejoin logged.');
        redirect("users/view/{$user_id}");
    }

}