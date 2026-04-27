<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team_chat_attachment_model extends App_Model
{
    private $allowed = [
        'image/jpeg' => 'image', 'image/png' => 'image', 'image/gif' => 'image', 'image/webp' => 'image', 'image/svg+xml' => 'image',
        'application/pdf' => 'document', 'text/plain' => 'document', 'text/csv' => 'document',
        'application/msword' => 'document', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
        'application/vnd.ms-excel' => 'document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'document',
        'application/zip' => 'archive', 'application/x-rar-compressed' => 'archive', 'application/x-7z-compressed' => 'archive',
        'video/mp4' => 'video', 'video/webm' => 'video', 'audio/mpeg' => 'audio', 'audio/ogg' => 'audio', 'audio/wav' => 'audio',
    ];
    private $max_size = 10485760;
    private $base_path;
    private $base_url;

    public function __construct()
    {
        parent::__construct();
        $this->base_path = FCPATH . 'uploads/team_chat/';
        $this->base_url = base_url('uploads/team_chat/');
        if (!is_dir($this->base_path)) @mkdir($this->base_path, 0755, true);
    }

    public function get($id)
    {
        $row = $this->db->where('id', (int)$id)->where('is_deleted', 0)->get('chat_attachments')->row_array();
        return $row ? $this->append_urls($row) : null;
    }

    public function get_for_message($message_id)
    {
        $rows = $this->db->where('message_id', (int)$message_id)->where('is_deleted', 0)->order_by('id', 'ASC')->get('chat_attachments')->result_array();
        return array_map([$this, 'append_urls'], $rows);
    }

    public function upload($conversation_id, $uploader_id, $message_id = null)
    {
        if (empty($_FILES['file']['tmp_name'])) return ['success' => false, 'error' => 'No file received'];
        $file = $_FILES['file'];
        if ((int)$file['size'] > $this->max_size) return ['success' => false, 'error' => 'File is larger than 10 MB'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($this->allowed[$mime])) return ['success' => false, 'error' => 'File type is not allowed'];
        $dir = $this->base_path . (int)$conversation_id . '/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $original = basename($file['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $stored = uniqid('tc_', true) . ($ext ? '.' . $ext : '');
        if (!move_uploaded_file($file['tmp_name'], $dir . $stored)) return ['success' => false, 'error' => 'Could not save file'];
        $this->db->insert('chat_attachments', [
            'message_id' => $message_id ?: null, 'conversation_id' => (int)$conversation_id, 'uploader_id' => (int)$uploader_id,
            'original_name' => $original, 'stored_name' => $stored, 'file_path' => (int)$conversation_id . '/' . $stored,
            'mime_type' => $mime, 'file_size' => (int)$file['size'], 'thumbnail_path' => null, 'is_deleted' => 0, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int)$this->db->insert_id();
        return $id ? ['success' => true, 'attachment' => $this->get($id)] : ['success' => false, 'error' => 'Database error'];
    }

    public function attach_to_message($attachment_id, $message_id)
    {
        return $this->db->where('id', (int)$attachment_id)->where('message_id IS NULL', null, false)->update('chat_attachments', ['message_id' => (int)$message_id]);
    }

    public function soft_delete($attachment_id)
    {
        return $this->db->where('id', (int)$attachment_id)->update('chat_attachments', ['is_deleted' => 1]);
    }

    public function append_urls(array $row)
    {
        $row['file_url'] = $this->base_url . $row['file_path'];
        $row['thumbnail_url'] = !empty($row['thumbnail_path']) ? $this->base_url . $row['thumbnail_path'] : null;
        $row['category'] = $this->allowed[$row['mime_type']] ?? 'other';
        $row['file_size_kb'] = round(((int)$row['file_size']) / 1024, 1);
        return $row;
    }
}
