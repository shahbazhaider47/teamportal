<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('admin/Company_setup_model', 'setup');
        $this->load->model('Department_model');
        $this->load->model('User_model');
        $this->load->model('Hrm_positions_model');
        $this->load->model('Activity_log_model');
        $this->load->model('Roles_model', 'roles');
        
        if (! $this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            exit;
        }

        if (! staff_can('manage', 'company')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            echo $html;
            exit;
        }
    }

    protected function log_activity(string $action): void
    {
        $this->Activity_log_model->add([
            'user_id'    => (int)($this->session->userdata('user_id') ?? 0),
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Company Setup – Main Entry Point
     * -------------------------------------
     * - Loads all required data once
     * - Views decide how/where to render
     * - Modals are INCLUDED at view level
     */
    public function index()
    {
        $this->handle_form_submissions();
        
        // Base departments with stats
        $departments = $this->setup->get_dept_with_stats();
        $positions   = $this->Hrm_positions_model->get_all_with_departments();
    
        // Attach users + HOD
        foreach ($departments as &$dept) {
    
            $dept['users'] = $this->Department_model->get_users($dept['id']);
    
            $dept['hod_user'] = !empty($dept['hod'])
                ? $this->User_model->get_user_by_id($dept['hod'])
                : null;
        }
        unset($dept);
    
        // (Optional) Fallback mapping if you can’t change the model:
        if (!empty($positions) && (empty($positions[0]['department_name']) && isset($departments[0]['name']))) {
            $deptMap = [];
            foreach ($departments as $d) {
                $deptMap[(int)$d['id']] = (string)$d['name'];
            }
            foreach ($positions as &$p) {
                $p['department_name'] = $deptMap[(int)($p['department_id'] ?? 0)] ?? null;
            }
            unset($p);
        }
        
        $rolesPayload = $this->build_roles_overview();
        
        $view_data = array_merge([
            'company'     => $this->setup->get_company(),
            'offices'     => $this->setup->get_offices(),
            'departments' => $departments,
            'positions'   => $positions,
            'users'       => $this->User_model->get_all_users(),
            'org_chart'   => $this->setup->get_org_chart(),
            'settings'    => $this->setup->get_company_settings(),
        ], $rolesPayload);
    
        $layout_data = [
            'page_title' => 'Company Setup',
            'subview'    => 'admin/setup/company/index',
            'view_data'  => $view_data,
        ];
    
        $this->load->view('layouts/master', $layout_data);
    }

// --------------------------------------------------------------------------------------------------
// Methods for Tab 1: Organizational Chart
// --------------------------------------------------------------------------------------------------


// --------------------------------------------------------------------------------------------------
// Methods for Tab 2: Company Info
// --------------------------------------------------------------------------------------------------
        
    /**
     * Save Company Profile
     * --------------------
     * Flat POST fields (NOT company_info[])
     * Compatible with Company_model::save()
     * Handles logo upload safely
     */
    public function save_company()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        
        $data = [
            'company_name'   => trim($this->input->post('company_name', true)),
            'business_email' => trim($this->input->post('business_email', true)),
            'business_phone' => trim($this->input->post('business_phone', true)),
            'address'        => trim($this->input->post('address', true)),
            'state'          => trim($this->input->post('state', true)),
            'city'           => trim($this->input->post('city', true)),
            'zip_code'       => trim($this->input->post('zip_code', true)),
            'office_id'      => $this->input->post('office_id') ?: null,
            'company_type'   => trim($this->input->post('company_type', true)),
            'ntn_no'         => trim($this->input->post('ntn_no', true)),
            'website'        => trim($this->input->post('website', true)),
        ];
    
        if ($data['company_name'] === '') {
            set_alert('danger', 'Company name is required.');
            redirect(site_url('admin/setup/company#company'));
            return;
        }
    
        $uploadPath = FCPATH . 'uploads/company/';
    
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
    
        $config = [
            'upload_path'   => $uploadPath,
            'allowed_types' => 'png|jpg|jpeg|gif|ico|svg|webp',
            'encrypt_name'  => true,
            'max_size'      => 2048,
        ];
    
        $this->load->library('upload');

        foreach (['light_logo', 'dark_logo', 'favicon'] as $field) {
        
            if (!empty($_FILES[$field]['name'])) {
        
                $this->upload->initialize($config);
        
                if (!$this->upload->do_upload($field)) {
                    set_alert(
                        'danger',
                        ucfirst(str_replace('_', ' ', $field)) .
                        ' upload failed: ' .
                        strip_tags($this->upload->display_errors('', ''))
                    );
                    redirect(site_url('admin/setup/company#company'));
                    return;
                }
        
                $file = $this->upload->data('file_name');
        
                $this->setup->update_company_logo($field, $file);
            }
        }
    
        if (!$this->setup->save_company($data)) {
            set_alert('danger', 'Failed to save company profile.');
            redirect(site_url('admin/setup/company#company'));
            return;
        }
    
        set_alert('success', 'Company profile updated successfully.');
        redirect(site_url('admin/setup/company#company'));
    }

    public function remove_company_logo($type = null)
    {
        if (!in_array($type, ['light', 'dark'], true)) {
            show_404();
        }
    
        $column  = ($type === 'light') ? 'light_logo' : 'dark_logo';
        $company = $this->setup->get_company();
    
        if (!empty($company[$column])) {
    
            $filePath = FCPATH . 'uploads/company/' . $company[$column];
    
            if (file_exists($filePath)) {
                unlink($filePath);
            }
    
            $this->setup->clear_company_logo($column);
        }
    
        set_alert('success', ucfirst($type) . ' logo removed successfully.');
        redirect(site_url('admin/setup/company#company'));
    }

    public function remove_favicon()
    {
        $company = $this->setup->get_company();
    
        if (!empty($company['favicon'])) {
    
            $filePath = FCPATH . 'uploads/company/' . $company['favicon'];
    
            if (file_exists($filePath)) {
                unlink($filePath);
            }
    
            $this->setup->clear_company_logo('favicon');
        }
    
        set_alert('success', 'Favicon removed successfully.');
        redirect(site_url('admin/setup/company#company'));
    }

// --------------------------------------------------------------------------------------------------
// Methods for Tab 3: Company Offices / Locations
// --------------------------------------------------------------------------------------------------

    public function get_office($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
    
        $id = (int) $id;
        if ($id <= 0) {
            show_404();
        }
    
        $office = $this->setup->get_office($id);
    
        if (!$office) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode([]));
            exit;
        }
    
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($office));
    
        exit;
    }

    /**
     * Save Company Office
     * -------------------
     * Handles company_offices table
     */
    public function save_office()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    
        $data = [
            'office_code'    => trim($this->input->post('office_code', true)),
            'office_name'    => trim($this->input->post('office_name', true)),
            'address_line_1' => trim($this->input->post('address_line_1', true)),
            'address_line_2' => trim($this->input->post('address_line_2', true)),
            'city'           => trim($this->input->post('city', true)),
            'state'          => trim($this->input->post('state', true)),
            'postal_code'    => trim($this->input->post('postal_code', true)),
            'country'        => trim($this->input->post('country', true)),
            'phone'          => trim($this->input->post('phone', true)),
            'email'          => trim($this->input->post('email', true)),
            'timezone'       => trim($this->input->post('timezone', true)),
            'currency'       => trim($this->input->post('currency', true)),
            'is_head_office' => $this->input->post('is_head_office') ? 1 : 0,
            'is_active'      => $this->input->post('is_active') ? 1 : 0,
        ];
    
        $required = [
            'office_code', 'office_name', 'address_line_1',
            'city', 'state', 'postal_code', 'country',
            'phone', 'timezone', 'currency',
        ];
    
        foreach ($required as $field) {
            if ($data[$field] === '') {
                set_alert('danger', ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                redirect(site_url('admin/setup/company#offices'));
                return;
            }
        }
    
        $ok = $this->setup->save_office($data);
        
        if (!$ok) {
            set_alert('danger', 'Office code already exists.');
            redirect(site_url('admin/setup/company#offices'));
            return;
        }
        
        set_alert('success', 'Office saved successfully.');
        redirect(site_url('admin/setup/company#offices'));
    }

    public function update_office()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    
        $officeId = (int)$this->input->post('office_id');
    
        if (!$officeId) {
            set_alert('danger', 'Invalid office.');
            redirect(site_url('admin/setup/company#offices'));
            return;
        }
    
        $data = [
            'office_code'    => trim($this->input->post('office_code', true)),
            'office_name'    => trim($this->input->post('office_name', true)),
            'address_line_1' => trim($this->input->post('address_line_1', true)),
            'address_line_2' => trim($this->input->post('address_line_2', true)),
            'city'           => trim($this->input->post('city', true)),
            'state'          => trim($this->input->post('state', true)),
            'postal_code'    => trim($this->input->post('postal_code', true)),
            'country'        => trim($this->input->post('country', true)),
            'phone'          => trim($this->input->post('phone', true)),
            'email'          => trim($this->input->post('email', true)),
            'timezone'       => trim($this->input->post('timezone', true)),
            'currency'       => trim($this->input->post('currency', true)),
            'is_head_office' => $this->input->post('is_head_office') ? 1 : 0,
            'is_active'      => $this->input->post('is_active') ? 1 : 0,
        ];
    
        $ok = $this->setup->save_office($data, $officeId);
        
        if (!$ok) {
            set_alert('danger', 'Office code already exists.');
            redirect(site_url('admin/setup/company#offices'));
            return;
        }
        
        set_alert('success', 'Office updated successfully.');
        redirect(site_url('admin/setup/company#offices'));
    }
 
     /**
     * Delete Company Office
     * ---------------------
     * - Hard delete (no soft delete yet)
     * - Prevents deleting Head Office
     * - Prevents deleting office in use (company.office_id)
     */
    public function delete_office($id = null)
    {
        $id = (int) $id;
        if ($id <= 0) {
            show_404();
        }
    
        $office = $this->setup->get_office($id);
        if (!$office) {
            set_alert('danger', 'Office not found.');
            redirect(site_url('admin/setup/company#offices'));
            return;
        }
    
        if (!empty($office['is_head_office'])) {
            set_alert('danger', 'Head Office cannot be deleted.');
            redirect(site_url('admin/setup/company#offices'));
            return;
        }
    
        $company = $this->setup->get_company();
        if (!empty($company['office_id']) && (int)$company['office_id'] === $id) {
            set_alert('danger', 'This office is currently set as the main company office.');
            redirect(site_url('admin/setup/company#offices'));
            return;
        }
    
        $deleted = $this->setup->delete_office($id);
    
        if (!$deleted) {
            set_alert('danger', 'Unable to delete office. It may be in use.');
            redirect(site_url('admin/setup/company#offices'));
            return;
        }
    
        set_alert('success', 'Office deleted successfully.');
        redirect(site_url('admin/setup/company#offices'));
    }

// --------------------------------------------------------------------------------------------------
// Methods for Tab 4: Departments
// --------------------------------------------------------------------------------------------------

    protected function handle_form_submissions()
    {
        if ($this->input->post('add_department')) {
            $this->_add_department();
            redirect('admin/setup/company#departments');
            return;
        }

        if ($this->input->post('update_department')) {
            $this->_update_department();
            redirect('admin/setup/company#departments');
            return;
        }
    }
    
    protected function _add_department()
    {
        $this->form_validation->set_rules('name', 'Department Name', 'required|max_length[100]|is_unique[departments.name]');
        $this->form_validation->set_rules('hod', 'Head of Department', 'required');
        $this->form_validation->set_rules('description', 'Description', 'max_length[500]');

        if ($this->form_validation->run() === false) {
            set_alert('danger', validation_errors());
            return;
        }

        $data = [
            'name' => $this->input->post('name', true),
            'hod'  => $this->input->post('hod', true),            
            'description' => $this->input->post('description', true),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $insert = $this->Department_model->insert($data);

        if ($insert) {
            set_alert('success', 'Department added successfully.');
            $this->log_activity('New Department Added [ID: ' . $insert . ', Name: ' . $data['name'] . ']');
        } else {
            set_alert('danger', 'Failed to add department.');
        }
    }

    protected function _update_department()
    {
        $id = (int)$this->input->post('id');
        $original = $this->Department_model->get($id);

        if (!$original) {
            set_alert('danger', 'Department not found.');
            return;
        }

        $this->form_validation->set_rules('id', 'Department ID', 'required|integer');
        $this->form_validation->set_rules('name', 'Department Name', 'required|max_length[100]');
        $this->form_validation->set_rules('hod', 'Head of Department', 'required');        
        $this->form_validation->set_rules('description', 'Description', 'max_length[500]');

        if ($original['name'] != $this->input->post('name')) {
            $this->form_validation->set_rules('name', 'Department Name', 'is_unique[departments.name]');
        }

        if ($this->form_validation->run() === false) {
            set_alert('danger', validation_errors());
            return;
        }

        $data = [
            'name' => $this->input->post('name', true),
            'hod'  => $this->input->post('hod', true),            
            'description' => $this->input->post('description', true),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $update = $this->Department_model->update($id, $data);

        if ($update) {
            set_alert('success', 'Department updated successfully.');
            $this->log_activity('Department Updated [ID: ' . $id . ', Name: ' . $data['name'] . ']');
        } else {
            set_alert('danger', 'Failed to update department.');
        }
    }

    /**
     * Delete department (URL-based)
     * Used by delete_link() helper
     */
    public function delete_department($id)
    {
        // Permission check (same spirit as index)
        if (!staff_can('delete', 'company')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }
    
        $id = (int) $id;
    
        if ($id <= 0) {
            set_alert('danger', 'Invalid department ID.');
            redirect('admin/setup/company#departments');
            return;
        }
    
        $department = $this->Department_model->get($id);
    
        if (!$department) {
            set_alert('danger', 'Department not found.');
            redirect('admin/setup/company#departments');
            return;
        }
    
        // Business rule: cannot delete if staff assigned
        if ($this->Department_model->has_staff($id)) {
            set_alert('danger', 'Cannot delete department with assigned staff members.');
            redirect('admin/setup/company#departments');
            return;
        }
    
        $delete = $this->Department_model->delete($id);
    
        if ($delete) {
            set_alert('success', 'Department deleted successfully.');
            $this->log_activity(
                'Department Deleted [ID: ' . $id . ', Name: ' . $department['name'] . ']'
            );
        } else {
            set_alert('danger', 'Failed to delete department.');
        }
    
        redirect('admin/setup/company#departments');
    }
    
// --------------------------------------------------------------------------------------------------
// Methods for Tab 5:  APositions / Designations
// --------------------------------------------------------------------------------------------------

    public function get_position($id = null)
    {
        while (ob_get_level() > 0) { @ob_end_clean(); }
        $this->output->enable_profiler(false);
        $this->output->set_content_type('application/json');
    
        if ($id === null || !ctype_digit((string)$id)) {
            $this->output->set_status_header(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            return;
        }
    
        $this->load->model('Hrm_positions_model');
    
        $row = $this->Hrm_positions_model->get((int)$id);
    
        if (!$row) {
            $this->output->set_status_header(404);
            echo json_encode(['status' => 'error', 'message' => 'Not found']);
            return;
        }
    
        if (is_object($row)) $row = (array)$row;
    
        $this->output->set_status_header(200);
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    public function delete_position($id)
    {
        if (empty($id) || !ctype_digit((string)$id)) {
            set_alert('danger', 'Invalid position id.');
            redirect('admin/setup/company#positions');
            return;
        }
    
        $this->Hrm_positions_model->delete((int)$id);
        set_alert('success', 'Position deleted.');
        redirect('admin/setup/company#positions');
    }
    
    public function save_position()
    {
        $this->form_validation->set_rules('title', 'Position Title', 'required|trim');
        $this->form_validation->set_rules('code',  'Position Code',  'required|trim');
    
        if ($this->form_validation->run() === FALSE) {
            set_alert('danger', strip_tags(validation_errors()));
            redirect('admin/setup/company#positions');
            return;
        }
    
        $id            = $this->input->post('id');
        $title         = $this->input->post('title', true);
        $code          = $this->input->post('code', true);
        $description   = $this->input->post('description', true);
        $department_id = $this->input->post('department_id');
        $min_salary    = $this->input->post('min_salary', true);
        $max_salary    = $this->input->post('max_salary', true);
        $status        = $this->input->post('status');
    
        $payload = [
            'title'         => $title,
            'code'          => $code,
            'description'   => $description !== '' ? $description : null,
            'department_id' => ($department_id === '' ? null : (int)$department_id),
            'min_salary'    => ($min_salary === '' ? null : (float)$min_salary),
            'max_salary'    => ($max_salary === '' ? null : (float)$max_salary),
            'status'        => ($status ? 1 : 0),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
    
        if ($id) {
            $ok = $this->Hrm_positions_model->update((int)$id, $payload);
            set_alert($ok ? 'success' : 'danger', $ok ? 'Position updated successfully.' : 'Failed to update position.');
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $ok = $this->Hrm_positions_model->insert($payload);
            set_alert($ok ? 'success' : 'danger', $ok ? 'Position added successfully.' : 'Failed to add position.');
        }
    
        redirect('admin/setup/company#positions');
    }

// --------------------------------------------------------------------------------------------------
// Methods for Tab 6: Staff Roles
// --------------------------------------------------------------------------------------------------
    /**
     * Build roles overview payload
     * ----------------------------
     * Shared structure with /roles page
     * Safe to reuse inside Company Setup
     */
    private function build_roles_overview(): array
    {
        $roles_with_counts = $this->roles->get_roles_with_user_counts();
    
        $roleKeys = [];
        foreach ($roles_with_counts as $r) {
            $key = strtolower((string)($r['role_name'] ?? ''));
            if ($key !== '') {
                $roleKeys[$key] = true;
            }
        }
        $roleKeys = array_keys($roleKeys);
    
        $avatarsByRole = [];
    
        if (!empty($roleKeys)) {
            $in = implode(',', array_map([$this->db, 'escape'], $roleKeys));
    
            $users = $this->db
                ->select('id, user_role, firstname, lastname, fullname, profile_image, is_active')
                ->from('users')
                ->where("LOWER(user_role) IN ($in)", null, false)
                ->order_by('LOWER(user_role) ASC, id DESC')
                ->get()
                ->result_array();
    
            $defaultAvatar = base_url('assets/images/default.png');
    
            foreach ($users as $u) {
                $role = strtolower((string)($u['user_role'] ?? ''));
                if ($role === '') {
                    continue;
                }
    
                $name = trim($u['fullname'] ?? '');
                if ($name === '') {
                    $name = trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
                }
                if ($name === '') {
                    $name = 'User #' . (int)$u['id'];
                }
    
                $avatar = $defaultAvatar;
                if (!empty($u['profile_image'])) {
                    $fs = FCPATH . 'uploads/users/profile/' . $u['profile_image'];
                    if (is_file($fs)) {
                        $avatar = base_url('uploads/users/profile/' . $u['profile_image']);
                    }
                }
    
                $avatarsByRole[$role][] = [
                    'id'     => (int)$u['id'],
                    'name'   => $name,
                    'avatar' => $avatar,
                    'active' => (int)($u['is_active'] ?? 0),
                ];
            }
        }
    
        return [
            'roles'               => $roles_with_counts,
            'avatarsByRole'       => $avatarsByRole,
            'max_role_avatars'    => 5,
        ];
    }

    /**
     * POST /roles/create
     * Body: role_name, description (<=100 chars)
     */
    public function create_role()
    {
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); return; }
        if (! $this->session->userdata('is_logged_in')) { show_error('Forbidden', 403); }
        if (! staff_can('manage', 'company')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden'); header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }

        $role_name = strtolower(trim(str_replace(' ', '_', (string)$this->input->post('role_name', true))));
        $role_name = preg_replace('/[^a-z_]/', '', $role_name ?? '');
        if ($role_name === '') {
            set_alert('danger', 'Role name is required.');
            redirect('admin/setup/company#staffroles'); return;
        }

        $desc = (string)$this->input->post('description', true);
        $desc = trim(strip_tags($desc));
        if (mb_strlen($desc) > 100) { $desc = mb_substr($desc, 0, 100); }

        if ($this->roles->role_exists($role_name)) {
            set_alert('warning', 'Role already exists.');
            redirect('admin/setup/company#staffroles'); return;
        }

        $ok = $this->roles->create_role($role_name, $desc ?: null);
        if ($ok) {
            $this->log_activity("Created role: {$role_name}");
            set_alert('success', 'New role added successfully.');
        } else {
            set_alert('danger', 'Failed to add role.');
        }
        redirect('admin/setup/company#staffroles');
    }

    /**
     * POST /roles/edit
     * Body:
     *  - original_name (required)
     *  - role_name (optional new name)
     *  - description (optional, <=100 chars)
     */
    public function edit_role()
    {
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); return; }
        if (! $this->session->userdata('is_logged_in')) { show_error('Forbidden', 403); }
        if (! staff_can('manage', 'company')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden'); header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }

        $original = (string)$this->input->post('original_name', true);
        if ($original === '') {
            set_alert('danger', 'Original role name is required.');
            redirect('admin/setup/company#staffroles'); return;
        }

        if (strtolower($original) === 'superadmin') {
            set_alert('danger', 'The “superadmin” role cannot be edited.');
            redirect('admin/setup/company#staffroles'); return;
        }

        $new_name = (string)$this->input->post('role_name', true);
        $new_name = strtolower(trim(str_replace(' ', '_', $new_name)));
        $new_name = preg_replace('/[^a-z_]/', '', $new_name ?? '');
        if ($new_name === '') { $new_name = $original; }

        if (strtolower($new_name) === 'superadmin') {
            set_alert('danger', 'You cannot rename a role to “superadmin”.');
            redirect('admin/setup/company#staffroles'); return;
        }

        $desc = (string)$this->input->post('description', true);
        $desc = trim(strip_tags($desc));
        if (mb_strlen($desc) > 100) { $desc = mb_substr($desc, 0, 100); }

        if ($new_name !== $original && $this->roles->role_exists($new_name)) {
            set_alert('danger', 'A role with the new name already exists.');
            redirect('admin/setup/company#staffroles'); return;
        }

        $renamed = true;
        if ($new_name !== $original) {
            $renamed = $this->roles->rename_role($original, $new_name);
            if (! $renamed) {
                set_alert('danger', 'Failed to rename role.');
                redirect('admin/setup/company#staffroles'); return;
            }
        }

        $updated = $this->roles->update_role($new_name, [
            'description' => ($desc !== '' ? $desc : null),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        if ($updated) {
            $this->log_activity("Updated role: {$original} → {$new_name}");
            set_alert('success', 'Role updated successfully.');
        } else {
            if ($renamed) {
                set_alert('success', 'Role renamed successfully.');
            } else {
                set_alert('danger', 'No changes were saved.');
            }
        }

        redirect('admin/setup/company#staffroles');
    }

    /**
     * POST /roles/delete
     * Body: role_name
     */
    public function delete_role()
    {
        if ($this->input->method() !== 'post') { show_error('Method Not Allowed', 405); return; }
        if (! $this->session->userdata('is_logged_in')) { show_error('Forbidden', 403); }
        if (! staff_can('manage', 'company')) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden'); header('Content-Type: text/html; charset=UTF-8');
            echo $html; exit;
        }

        $role_name = (string)$this->input->post('role_name', true);
        if ($role_name === '') {
            set_alert('danger', 'Role name is missing.');
            redirect('admin/setup/company#staffroles'); return;
        }

        // 🔒 Hard rule: superadmin cannot be deleted
        if (strtolower($role_name) === 'superadmin') {
            set_alert('danger', 'The “superadmin” role cannot be deleted.');
            redirect('admin/setup/company#staffroles'); return;
        }

        // 🔒 Don’t delete a role if any user currently has it
        $assignedCount = (int) $this->db
            ->from('users')
            ->where('LOWER(user_role)', strtolower($role_name))
            ->count_all_results();

        if ($assignedCount > 0) {
            set_alert('danger', 'This role has assigned users and cannot be deleted.');
            redirect('admin/setup/company#staffroles'); return;
        }

        $deleted = $this->roles->delete_role($role_name);
        if ($deleted) {
            $this->log_activity("Deleted role: {$role_name}");
            set_alert('success', "Role '{$role_name}' deleted.");
        } else {
            set_alert('danger', "Failed to delete role '{$role_name}'.");
        }
        redirect('admin/setup/company#staffroles');
    }    

// --------------------------------------------------------------------------------------------------
// Methods for Tab 7: Default Variable Types
// --------------------------------------------------------------------------------------------------

    /**
     * Save  Default Variable Types (JSON-based key/value)
     * -------------------------------------------
     * - Saves multiple keys in one request
     * - Each setting stored as JSON string
     * - Uses company_settings table
     */
    public function save_variable_types()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    
        if (!staff_can('manage', 'company')) {
            show_error('Forbidden', 403);
        }
    
        $settings = $this->input->post('settings');
        if (!is_array($settings)) {
            set_alert('warning', 'No settings received.');
            redirect('admin/setup/company#variablestypes');
            return;
        }
    
        $saved = 0;
    
        foreach ($settings as $key => $json) {
            $key = trim((string)$key);
    
            if ($key === '') {
                continue;
            }
    
            // Validate JSON (do NOT auto-fix silently)
            $decoded = json_decode((string)$json, true);
            if (!is_array($decoded)) {
                log_message('error', 'Invalid JSON for company setting: ' . $key);
                continue;
            }
    
            $ok = $this->setup->save_company_setting($key, json_encode($decoded, JSON_UNESCAPED_UNICODE));
            if ($ok) {
                $saved++;
            }
        }
    
        if ($saved > 0) {
            $this->log_activity("Updated company default variable types ({$saved} keys)");
            set_alert('success', 'Company settings updated successfully.');
        } else {
            set_alert('warning', 'No settings were saved.');
        }
    
        redirect('admin/setup/company#variablestypes');
    }

// --------------------------------------------------------------------------------------------------
// Methods for Tab 8: Default Company Setup Settings
// --------------------------------------------------------------------------------------------------
    /**
     * Save Company Settings (System-Level)
     * -----------------------------------
     * - Stores scalar key/value settings
     * - Uses company_settings table
     * - Enforced centrally by backend rules
     */
public function save_company_settings()
{
    if ($this->input->method() !== 'post') {
        show_404();
    }

    if (!staff_can('manage', 'company')) {
        show_error('Forbidden', 403);
    }

    $allowedSettings = [
        'cnic_required',
        'ntn_required',
        'tax_number_required',
        'blood_group_required',
        'father_name_required',
        'mother_name_required',
        'min_hiring_age',
        'default_employment_type',
        'probation_duration_months',
        'default_employee_grade',
        'iban_min_digits',
        'iban_max_digits',
        'default_salary_pay_method',
    ];

    $post  = $this->input->post();
    $saved = 0;

    foreach ($allowedSettings as $key) {

        if (!array_key_exists($key, $post)) {
            continue;
        }

        $raw = $post[$key];

        switch ($key) {

            // Boolean toggles
            case 'cnic_required':
            case 'ntn_required':
            case 'tax_number_required':
            case 'blood_group_required':
            case 'father_name_required':
            case 'mother_name_required':
                $value = in_array((string)$raw, ['1', 'true', 'yes'], true) ? '1' : '0';
                break;

            // Integers
            case 'min_hiring_age':
            case 'probation_duration_months':
            case 'iban_min_digits':
            case 'iban_max_digits':
                $value = (string)(int)$raw;
                break;

            // Strings / enums
            default:
                $value = trim((string)$raw);
                break;
        }

        if ($this->setup->save_company_setting($key, $value)) {
            $saved++;
        }
    }

    set_alert(
        $saved ? 'success' : 'warning',
        $saved ? 'Company settings updated successfully.' : 'No settings were changed.'
    );

    $this->log_activity("Updated company system settings ({$saved} keys)");
    redirect('admin/setup/company#companysettings');
}


// --------------------------------------------------------------------------------------------------
// Methods for Tab 9: 
// --------------------------------------------------------------------------------------------------


// --------------------------------------------------------------------------------------------------
// Methods for Tab 10: 
// --------------------------------------------------------------------------------------------------


}