<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Files extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['url', 'form', 'crm', 'download']);
        $this->load->library(['form_validation', 'upload']);
        $this->load->model('crm/Crm_files_model', 'crmfiles');
        $this->load->model('crm/Crm_activity_model', 'crmactivity');
        $this->load->model('crm/Crmleads_model', 'crmleads');
    }

    /* =========================================================
     * INTERNAL HELPERS
     * ======================================================= */

    protected function _guard_manage_crm()
    {
        if (
            staff_can('view', 'crm') ||
            staff_can('view_global', 'crm') ||
            staff_can('view_own', 'crm')
        ) {
            return;
        }

        $this->_crm_forbidden();
    }

    protected function _crm_forbidden()
    {
        $html = $this->load->view('errors/html/error_403', [], true);
        header('HTTP/1.1 403 Forbidden');
        echo $html;
        exit;
    }

    protected function _can_create(): bool
    {
        return staff_can('lead_create', 'crm') || staff_can('client_create', 'crm');
    }

    protected function _can_edit(): bool
    {
        return staff_can('lead_edit', 'crm') || staff_can('client_edit', 'crm');
    }

    protected function _can_delete(): bool
    {
        return staff_can('lead_delete', 'crm') || staff_can('client_delete', 'crm');
    }

    protected function _can_view(): bool
    {
        return staff_can('lead_view', 'crm') || staff_can('client_view', 'crm') || staff_can('view', 'crm');
    }

    protected function _require_login(): void
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            exit;
        }
    }

    protected function _user_id(): int
    {
        return (int)$this->session->userdata('user_id');
    }

    protected function _log_activity(string $action, int $relId, string $description, array $metadata = [], string $relType = 'lead'): void
    {
        $userId = $this->_user_id();

        $this->crmactivity->log([
            'user_id'     => $userId > 0 ? $userId : null,
            'rel_type'    => $relType,
            'rel_id'      => $relId > 0 ? $relId : null,
            'action'      => $action,
            'description' => $description,
            'metadata'    => $metadata,
            'ip_address'  => $this->input->ip_address(),
        ]);
    }

    protected function _relation_label(string $relatedType, int $relatedId): string
    {
        $relatedType = strtolower(trim($relatedType));

        if ($relatedType === 'lead') {
            $lead = $this->crmleads->get($relatedId);
            if ($lead) {
                $name  = trim((string)($lead['practice_name'] ?? 'Unknown Lead'));
                $email = trim((string)($lead['contact_email'] ?? ''));
                return $email !== '' ? ($name . ' (' . $email . ')') : $name;
            }
        }

        return ucfirst($relatedType) . ' #' . $relatedId;
    }

    protected function _validate_relation_or_404(string $relatedType, int $relatedId): void
    {
        if (
            !$this->crmfiles->is_allowed_related_type($relatedType) ||
            !$this->crmfiles->relation_exists($relatedType, $relatedId)
        ) {
            show_404();
        }
    }

    protected function _safe_file_name(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9\-\._]/', '_', $name);
        $name = preg_replace('/_+/', '_', (string)$name);
        return trim((string)$name, '_');
    }

    /* =========================================================
     * UPLOAD MULTIPLE FILES
     * ======================================================= */

    public function upload()
    {
        $this->_guard_manage_crm();

        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }

        if (strtoupper($this->input->method()) !== 'POST') {
            show_404();
        }

        $this->_require_login();

        $relatedType = trim((string)$this->input->post('related_type', true));
        $relatedId   = (int)$this->input->post('related_id', true);
        $title       = trim((string)$this->input->post('title', true));
        $description = trim((string)$this->input->post('description', true));
        $isPublic    = (int)$this->input->post('is_public', true) === 1 ? 1 : 0;

        $this->_validate_relation_or_404($relatedType, $relatedId);

        if (empty($_FILES['files']['name']) || !is_array($_FILES['files']['name'])) {
            set_alert('danger', 'Please select at least one file.');
            redirect('crm/' . $relatedType . 's/view/' . $relatedId);
            return;
        }

        $uploadPath = $this->crmfiles->build_storage_path($relatedType, $relatedId);
        if (!$this->crmfiles->ensure_directory($uploadPath)) {
            set_alert('danger', 'Failed to create upload directory.');
            redirect('crm/' . $relatedType . 's/view/' . $relatedId);
            return;
        }

        $filesCount = count($_FILES['files']['name']);
        $insertRows = [];
        $errors     = [];
        $success    = 0;

        for ($i = 0; $i < $filesCount; $i++) {
            if (empty($_FILES['files']['name'][$i])) {
                continue;
            }

            $_FILES['single_file']['name']     = $_FILES['files']['name'][$i];
            $_FILES['single_file']['type']     = $_FILES['files']['type'][$i];
            $_FILES['single_file']['tmp_name'] = $_FILES['files']['tmp_name'][$i];
            $_FILES['single_file']['error']    = $_FILES['files']['error'][$i];
            $_FILES['single_file']['size']     = $_FILES['files']['size'][$i];

            $originalName = $_FILES['single_file']['name'];
            $extension    = pathinfo($originalName, PATHINFO_EXTENSION);
            $baseName     = pathinfo($originalName, PATHINFO_FILENAME);
            $safeBase     = $this->_safe_file_name($baseName);
            $finalName    = $safeBase . '_' . date('YmdHis') . '_' . mt_rand(100, 999);

            if ($extension !== '') {
                $finalName .= '.' . strtolower($extension);
            }

            $config = [
                'upload_path'   => $uploadPath,
                'allowed_types' => 'jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx|csv|txt|zip',
                'max_size'      => 10240,
                'file_name'     => $finalName,
                'overwrite'     => false,
            ];

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('single_file')) {
                $errors[] = $originalName . ': ' . strip_tags((string)$this->upload->display_errors('', ''));
                continue;
            }

            $uploaded = $this->upload->data();

            $insertRows[] = [
                'related_type' => $relatedType,
                'related_id'   => $relatedId,
                'file_name'    => $originalName,
                'file_path'    => $this->crmfiles->build_db_path($relatedType, $relatedId, $uploaded['file_name']),
                'title'        => $title !== '' ? $title : null,
                'description'  => $description !== '' ? $description : null,
                'is_public'    => $isPublic,
                'created_by'   => $this->_user_id(),
                'updated_by'   => $this->_user_id(),
            ];

            $success++;
        }

        if (!empty($insertRows)) {
            $this->crmfiles->insert_batch($insertRows);

            $this->_log_activity(
                'files_uploaded',
                $relatedId,
                $success . ' file(s) uploaded for ' . $this->_relation_label($relatedType, $relatedId),
                [
                    'related_type' => $relatedType,
                    'uploaded'     => $success,
                    'files'        => array_column($insertRows, 'file_name'),
                ],
                $relatedType
            );
        }

        if ($success > 0 && empty($errors)) {
            set_alert('success', $success . ' file(s) uploaded successfully.');
        } elseif ($success > 0 && !empty($errors)) {
            set_alert('warning', $success . ' file(s) uploaded, but some failed: ' . implode(' | ', array_slice($errors, 0, 3)));
        } else {
            set_alert('danger', 'Upload failed: ' . implode(' | ', array_slice($errors, 0, 3)));
        }

        redirect('crm/' . $relatedType . 's/view/' . $relatedId);
    }

    /* =========================================================
     * DOWNLOAD FILE
     * ======================================================= */

    public function download($id)
    {
        $this->_guard_manage_crm();

        if (!$this->_can_view()) {
            $this->_crm_forbidden();
        }

        $id   = (int)$id;
        $file = $this->crmfiles->get($id);

        if (!$file) {
            show_404();
        }

        $fullPath = FCPATH . ltrim((string)$file['file_path'], '/');
        if (!is_file($fullPath)) {
            show_404();
        }

        force_download($fullPath, null);
    }

    /* =========================================================
     * DELETE FILE
     * ======================================================= */

    public function delete($id)
    {
        $this->_guard_manage_crm();

        if (!$this->_can_delete()) {
            $this->_crm_forbidden();
        }

        if (strtoupper($this->input->method()) !== 'POST') {
            show_404();
        }

        $id   = (int)$id;
        $file = $this->crmfiles->get($id);

        if (!$file) {
            show_404();
        }

        $this->_require_login();

        $relatedType = trim((string)$file['related_type']);
        $relatedId   = (int)$file['related_id'];
        $fullPath    = FCPATH . ltrim((string)$file['file_path'], '/');

        $ok = $this->crmfiles->delete($id);

        if ($ok) {
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }

            $this->_log_activity(
                'file_deleted',
                $relatedId,
                'File deleted from ' . $this->_relation_label($relatedType, $relatedId),
                [
                    'file_id'      => $id,
                    'file_name'    => $file['file_name'] ?? null,
                    'related_type' => $relatedType,
                ],
                $relatedType
            );
        }

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'File deleted successfully.' : 'Failed to delete file.'
        );

        redirect('crm/' . $relatedType . 's/view/' . $relatedId);
    }

    /* =========================================================
     * UPDATE FILE META
     * ======================================================= */

    public function update($id)
    {
        $this->_guard_manage_crm();

        if (!$this->_can_edit()) {
            $this->_crm_forbidden();
        }

        if (strtoupper($this->input->method()) !== 'POST') {
            show_404();
        }

        $id   = (int)$id;
        $file = $this->crmfiles->get($id);

        if (!$file) {
            show_404();
        }

        $this->_require_login();

        $title       = trim((string)$this->input->post('title', true));
        $description = trim((string)$this->input->post('description', true));
        $isPublic    = (int)$this->input->post('is_public', true) === 1 ? 1 : 0;

        $ok = $this->crmfiles->update($id, [
            'title'       => $title !== '' ? $title : null,
            'description' => $description !== '' ? $description : null,
            'is_public'   => $isPublic,
            'updated_by'  => $this->_user_id(),
        ]);

        if ($ok) {
            $this->_log_activity(
                'file_updated',
                (int)$file['related_id'],
                'File details updated for ' . $this->_relation_label($file['related_type'], (int)$file['related_id']),
                [
                    'file_id'   => $id,
                    'file_name' => $file['file_name'] ?? null,
                ],
                $file['related_type']
            );
        }

        set_alert(
            $ok ? 'success' : 'danger',
            $ok ? 'File updated successfully.' : 'Failed to update file.'
        );

        redirect('crm/' . $file['related_type'] . 's/view/' . (int)$file['related_id']);
    }
}