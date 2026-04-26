<?php defined('BASEPATH') or exit('No direct script access allowed');

class Login_vault extends App_Controller
{
    protected int $uid         = 0;
    protected bool $canGlobal  = false;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Login_vault_model', 'vaults');
        $this->load->model('Activity_log_model');
        $this->load->library(['form_validation', 'session']);

        $this->uid       = (int)  ($this->session->userdata('user_id')    ?? 0);
        $this->canGlobal = (bool)  staff_can('view_global', 'vault');

        if (!$this->session->userdata('is_logged_in') || !$this->uid) {
            redirect('authentication/login');
            return;
        }
    }

    /* =========================================================
     * HELPERS
     * ======================================================= */

    protected function log_activity(string $action): void
    {
        $this->Activity_log_model->add([
            'user_id'    => $this->uid,
            'action'     => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Resolve a vault by ID respecting ownership / global-view permission.
     * Returns null when the record does not exist or the user has no access.
     */
    protected function resolve_vault(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->canGlobal
            ? $this->vaults->get_by_id_any_owner($id)
            : $this->vaults->get_by_id($id, $this->uid);
    }

    /* =========================================================
     * INDEX
     * ======================================================= */

    public function index(): void
    {
        if (!staff_can('view_own', 'vault') && !$this->canGlobal) {
            $html = $this->load->view('errors/html/error_403', [], true);
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        $vaults = $this->vaults->get_list_for_user($this->uid, $this->canGlobal);

        // Resolve owner names for the view modal (owner_user_id -> full name)
        $userNames = [];
        foreach ($vaults as &$row) {
            $ownerId = (int)($row['owner_user_id'] ?? 0);
            if ($ownerId && !isset($userNames[$ownerId])) {
                $u = $this->db->select('firstname, lastname')
                              ->where('id', $ownerId)
                              ->get('users')->row_array();
                $userNames[$ownerId] = $u
                    ? trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''))
                    : 'Unknown';
            }
            $row['owner_name'] = $userNames[$ownerId] ?? '—';
        }
        unset($row);

        $layout_data = [
            'page_title' => 'Logins Vault',
            'subview'    => 'login_vault/index',
            'view_data'  => [
                'vaults'       => $vaults,
                'type_options' => get_vault_types(),
            ],
        ];

        $this->load->view('layouts/master', $layout_data);
    }

    /* =========================================================
     * STORE
     * ======================================================= */

    public function store(): void
    {
        if (!$this->input->post()) {
            redirect('login_vault');
            return;
        }

        if (!staff_can('create', 'vault')) {
            set_alert('danger', 'You do not have permission to create vault entries.');
            redirect('login_vault');
            return;
        }

        // Validation rules
        $this->form_validation->set_rules('title',         'Title',       'required|trim|max_length[191]');
        $this->form_validation->set_rules('type',          'Type',        'trim|max_length[50]');
        $this->form_validation->set_rules('username',      'Username',    'trim|max_length[191]');
        $this->form_validation->set_rules('login_email',   'Login Email', 'trim|max_length[191]');
        $this->form_validation->set_rules('login_phone',   'Login Phone', 'trim|max_length[50]');
        $this->form_validation->set_rules('login_pin',     'PIN',         'trim|max_length[20]');
        $this->form_validation->set_rules('password_plain','Password',    'required|trim|max_length[255]');
        $this->form_validation->set_rules('permissions',   'Permissions', 'required|in_list[private,read,write]');
        $this->form_validation->set_rules('description',   'Description', 'trim');

        // login_url: optional, but if provided must be a valid URL
        $loginUrl = trim((string)$this->input->post('login_url', false));
        if ($loginUrl !== '' && !filter_var($loginUrl, FILTER_VALIDATE_URL)) {
            set_alert('danger', 'Please enter a valid Login URL (example: https://portal.example.com).');
            redirect('login_vault');
            return;
        }

        if ($this->form_validation->run() === false) {
            set_alert('danger', strip_tags(validation_errors()));
            redirect('login_vault');
            return;
        }

        $data = [
            'owner_user_id'      => $this->uid,
            'title'              => trim($this->input->post('title',         true)),
            'description'        =>      $this->input->post('description',   true),
            'type'               =>     ($this->input->post('type',          true) ?: 'website'),
            'login_url'          => $loginUrl ?: null,
            'username'           => trim($this->input->post('username',      true)) ?: null,
            'login_email'        => trim($this->input->post('login_email',   true)) ?: null,
            'login_phone'        => trim($this->input->post('login_phone',   true)) ?: null,
            'login_pin'          => trim($this->input->post('login_pin',     true)) ?: null,
            'password_encrypted' =>      $this->input->post('password_plain', false), // stored as-is
            'is_tfa'             =>      $this->input->post('is_tfa')   ? 1 : 0,
            'tfa_secret'         =>      $this->input->post('tfa_secret', false) ?: null,
            'permissions'        =>      $this->input->post('permissions', true),
            'created_by'         => $this->uid,
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        try {
            $id = $this->vaults->create($data);

            if ($id) {
                $this->log_activity("Created vault entry ID {$id}: " . $data['title']);
                set_alert('success', 'Vault entry created successfully.');
            } else {
                set_alert('danger', 'Failed to create vault entry.');
            }
        } catch (Throwable $e) {
            log_message('error', 'Login Vault Store Error: ' . $e->getMessage());
            set_alert('danger', 'Unexpected error while saving vault entry.');
        }

        redirect('login_vault');
    }

    /* =========================================================
     * UPDATE
     * ======================================================= */

    public function update(int $id): void
    {
        if (!staff_can('edit', 'vault')) {
            set_alert('danger', 'Access denied.');
            redirect('login_vault');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            set_alert('warning', 'Invalid vault ID.');
            redirect('login_vault');
            return;
        }

        $vault = $this->resolve_vault($id);
        if (!$vault) {
            set_alert('warning', 'Vault entry not found or access denied.');
            redirect('login_vault');
            return;
        }

        // Validate login_url if provided
        $loginUrl = trim((string)$this->input->post('login_url', false));
        if ($loginUrl !== '' && !filter_var($loginUrl, FILTER_VALIDATE_URL)) {
            set_alert('danger', 'Please enter a valid Login URL.');
            redirect('login_vault');
            return;
        }

        $data = [
            'title'       => trim($this->input->post('title',       true)),
            'description' =>      $this->input->post('description', true),
            'type'        =>     ($this->input->post('type',        true) ?: 'website'),
            'login_url'   => $loginUrl ?: null,
            'username'    => trim($this->input->post('username',    true)) ?: null,
            'login_email' => trim($this->input->post('login_email', true)) ?: null,
            'login_phone' => trim($this->input->post('login_phone', true)) ?: null,
            'login_pin'   => trim($this->input->post('login_pin',   true)) ?: null,
            'is_tfa'      =>      $this->input->post('is_tfa')   ? 1 : 0,
            'tfa_secret'  =>      $this->input->post('tfa_secret', false) ?: null,
            'permissions' =>      $this->input->post('permissions', true),
            'updated_at'  => date('Y-m-d H:i:s'),
            'updated_by'  => $this->uid,
        ];

        // Update password only when a new one is submitted
        $plainPassword = trim((string)$this->input->post('password_plain', false));
        if ($plainPassword !== '') {
            $data['password_encrypted'] = $plainPassword;
        }

        // Validate permissions value
        if (!in_array($data['permissions'], ['private', 'read', 'write'], true)) {
            $data['permissions'] = 'private';
        }

        $this->vaults->update($id, $this->uid, $data, $this->canGlobal);

        $this->log_activity("Updated vault entry ID {$id}");
        set_alert('success', 'Vault entry updated successfully.');
        redirect('login_vault');
    }

    /* =========================================================
     * DELETE
     * ======================================================= */

    public function delete(int $id): void
    {
        if (!staff_can('delete', 'vault')) {
            set_alert('danger', 'Access denied.');
            redirect('login_vault');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            set_alert('warning', 'Invalid vault entry.');
            redirect('login_vault');
            return;
        }

        $vault = $this->resolve_vault($id);
        if (!$vault) {
            set_alert('warning', 'Vault entry not found or access denied.');
            redirect('login_vault');
            return;
        }

        $deleted = $this->vaults->soft_delete($id, $this->uid, $this->canGlobal);

        if ($deleted) {
            // Revoke all shares when deleting
            $this->vaults->revoke_all_shares($id);
            $this->log_activity("Deleted vault entry ID {$id}");
            set_alert('success', 'Vault entry deleted.');
        } else {
            set_alert('danger', 'Vault entry could not be deleted.');
        }

        redirect('login_vault');
    }

    /* =========================================================
     * REVEAL PASSWORD (AJAX)
     * ======================================================= */

    public function reveal_password(int $id): void
    {
        if (!staff_can('view_own', 'vault') && !$this->canGlobal) {
            $this->_json_error('Access denied', 403);
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->_json_error('Invalid vault ID', 400);
            return;
        }

        $vault = $this->resolve_vault($id);

        if (!$vault || empty($vault['password_encrypted'])) {
            $this->_json_error('Password not found', 404);
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['password' => $vault['password_encrypted']]));
    }

    /* =========================================================
     * SHARE — SUBMIT FORM
     * ======================================================= */

    public function share(): void
    {
        if (!$this->input->is_ajax_request() && !$this->input->post()) {
            redirect('login_vault');
            return;
        }

        $vaultId   = (int)  $this->input->post('vault_id');
        $shareType = trim((string) $this->input->post('share_type', true));
        $shareIds  = (array) $this->input->post('share_ids');
        $perms     = trim((string) $this->input->post('permissions', true));

        // --- Validate vault_id ---
        if ($vaultId <= 0) {
            set_alert('danger', 'Invalid vault selected.');
            redirect('login_vault');
            return;
        }

        // --- Validate share_type ---
        $allowedTypes = ['Departments', 'Teams', 'Positions', 'Staff'];
        if (!in_array($shareType, $allowedTypes, true)) {
            set_alert('danger', 'Invalid share scope selected.');
            redirect('login_vault');
            return;
        }

        // --- Validate share_ids ---
        $shareIds = array_values(array_filter(array_map('intval', $shareIds), fn($i) => $i > 0));
        if (empty($shareIds)) {
            set_alert('danger', 'Please select at least one target to share with.');
            redirect('login_vault');
            return;
        }

        // --- Validate permissions ---
        if (!in_array($perms, ['view', 'edit', 'delete'], true)) {
            $perms = 'view';
        }

        // --- Confirm vault access ---
        $vault = $this->resolve_vault($vaultId);
        if (!$vault) {
            set_alert('danger', 'Vault not found or access denied.');
            redirect('login_vault');
            return;
        }

        $ok = $this->vaults->add_share([
            'vault_id'    => $vaultId,
            'share_type'  => $shareType,
            'share_ids'   => $shareIds,
            'permissions' => $perms,
            'created_by'  => $this->uid,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        if ($ok) {
            $this->log_activity("Shared vault ID {$vaultId} with {$shareType} [" . implode(',', $shareIds) . "]");
            set_alert('success', 'Vault shared successfully.');
        } else {
            set_alert('danger', 'Failed to share vault. Please try again.');
        }

        redirect('login_vault');
    }

    /* =========================================================
     * GET SHARE SCOPE ITEMS (AJAX — populates share modal dropdown)
     * ======================================================= */

    public function get_share_scope_items(): void
    {
        if (!$this->session->userdata('is_logged_in')) {
            $this->_json_error('Not authenticated', 401);
            return;
        }

        $type  = trim((string) $this->input->get('type', true));
        $items = [];

        switch ($type) {

            case 'Departments':
                $rows = $this->db
                    ->select('id, name')
                    ->where('deleted_at IS NULL', null, false) // adjust if your departments have soft-delete
                    ->order_by('name', 'ASC')
                    ->get('departments')
                    ->result_array();
                foreach ($rows as $row) {
                    $items[] = ['id' => (int)$row['id'], 'name' => (string)$row['name']];
                }
                break;

            case 'Teams':
                $rows = $this->db
                    ->select('id, name')
                    ->order_by('name', 'ASC')
                    ->get('teams')
                    ->result_array();
                foreach ($rows as $row) {
                    $items[] = ['id' => (int)$row['id'], 'name' => (string)$row['name']];
                }
                break;

            case 'Positions':
                $rows = $this->db
                    ->select('id, title')
                    ->order_by('title', 'ASC')
                    ->get('hrm_positions')
                    ->result_array();
                foreach ($rows as $row) {
                    $items[] = ['id' => (int)$row['id'], 'name' => (string)$row['title']];
                }
                break;

            case 'Staff':
                $rows = $this->db
                    ->select('id, firstname, lastname, email')
                    ->from('users')
                    ->where('is_active', 1)
                    ->order_by('firstname', 'ASC')
                    ->order_by('lastname',  'ASC')
                    ->get()
                    ->result_array();
                foreach ($rows as $row) {
                    $name = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
                    if ($name === '') {
                        $name = 'User #' . (int)$row['id'];
                    }
                    $items[] = ['id' => (int)$row['id'], 'name' => $name];
                }
                break;

            default:
                // Unknown type — return empty array (not an error)
                break;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($items));
    }

    /* =========================================================
     * INTERNAL HELPERS
     * ======================================================= */

    private function _json_error(string $message, int $status = 400): void
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode(['error' => $message]));
    }
}