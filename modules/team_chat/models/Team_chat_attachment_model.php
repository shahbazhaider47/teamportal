<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team_chat_attachment_model
 * Handles file upload, retrieval, soft-delete,
 * thumbnail generation for images, and storage management.
 */
class Team_chat_attachment_model extends App_Model
{
    /**
     * Allowed MIME types and their categories.
     */
    private $allowed_mime_types = [
        // Images
        'image/jpeg'      => 'image',
        'image/png'       => 'image',
        'image/gif'       => 'image',
        'image/webp'      => 'image',
        'image/svg+xml'   => 'image',
        // Documents
        'application/pdf'                                                          => 'document',
        'application/msword'                                                       => 'document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'  => 'document',
        'application/vnd.ms-excel'                                                 => 'document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'        => 'document',
        'application/vnd.ms-powerpoint'                                            => 'document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'=> 'document',
        'text/plain'      => 'document',
        'text/csv'        => 'document',
        // Archives
        'application/zip'              => 'archive',
        'application/x-rar-compressed' => 'archive',
        'application/x-7z-compressed'  => 'archive',
        // Video
        'video/mp4'       => 'video',
        'video/webm'      => 'video',
        'video/ogg'       => 'video',
        // Audio
        'audio/mpeg'      => 'audio',
        'audio/ogg'       => 'audio',
        'audio/wav'       => 'audio',
        'audio/webm'      => 'audio',
    ];

    private $max_file_size    = 10485760; // 10 MB in bytes
    private $upload_base_path = '';       // Set in constructor
    private $upload_base_url  = '';

    public function __construct()
    {
        parent::__construct();

        // Set upload paths relative to the module's assets folder
        $this->upload_base_path = FCPATH . 'uploads/team_chat/';
        $this->upload_base_url  = base_url('uploads/team_chat/');

        // Ensure base upload directory exists
        if (!is_dir($this->upload_base_path)) {
            mkdir($this->upload_base_path, 0755, true);
        }
    }

    // =========================================================
    // READ
    // =========================================================

    /**
     * Returns a single attachment by ID.
     */
    public function get($attachment_id)
    {
        $row = $this->db->where('id', (int)$attachment_id)
                        ->where('is_deleted', 0)
                        ->get('chat_attachments')
                        ->row_array();

        if (!$row) {
            return null;
        }

        return $this->_append_urls($row);
    }

    /**
     * Returns all attachments for a message.
     */
    public function get_for_message($message_id)
    {
        $rows = $this->db->where('message_id', (int)$message_id)
                         ->where('is_deleted', 0)
                         ->order_by('id', 'ASC')
                         ->get('chat_attachments')
                         ->result_array();

        return array_map([$this, '_append_urls'], $rows);
    }

    /**
     * Returns all attachments for a conversation (media gallery).
     *
     * @param int    $conversation_id
     * @param string $category         'image'|'document'|'video'|'audio'|'archive'|'' (all)
     * @param int    $limit
     * @param int    $offset
     */
    public function get_for_conversation($conversation_id, $category = '', $limit = 50, $offset = 0)
    {
        $this->db->select('
            a.*,
            cm.sender_id,
            cm.created_at AS message_date,
            u.fullname AS uploader_name,
            u.profile_image AS uploader_avatar
        ');
        $this->db->from('chat_attachments a');
        $this->db->join('chat_messages cm', 'cm.id = a.message_id', 'inner');
        $this->db->join('users u', 'u.id = a.uploader_id', 'left');
        $this->db->where('a.conversation_id', (int)$conversation_id);
        $this->db->where('a.is_deleted', 0);
        $this->db->where('cm.is_deleted', 0);

        if ($category) {
            // Filter by MIME category
            $mimes_in_category = array_keys(array_filter(
                $this->allowed_mime_types,
                function ($cat) use ($category) { return $cat === $category; }
            ));
            if (!empty($mimes_in_category)) {
                $this->db->where_in('a.mime_type', $mimes_in_category);
            }
        }

        $this->db->order_by('a.id', 'DESC');
        $this->db->limit($limit, $offset);

        $rows = $this->db->get()->result_array();
        return array_map([$this, '_append_urls'], $rows);
    }

    // =========================================================
    // UPLOAD
    // =========================================================

    /**
     * Processes a file upload from $_FILES['file'].
     * Creates the conversation subdirectory, validates, stores,
     * generates thumbnail for images, and inserts DB record.
     *
     * @param int $conversation_id
     * @param int $uploader_id
     * @param int $message_id        Optional — attached after message is created
     * @return array { success, attachment|error }
     */
    public function upload($conversation_id, $uploader_id, $message_id = null)
    {
        $conversation_id = (int)$conversation_id;
        $uploader_id     = (int)$uploader_id;

        if (empty($_FILES['file']['tmp_name'])) {
            return ['success' => false, 'error' => 'No file received'];
        }

        $file      = $_FILES['file'];
        $tmp_path  = $file['tmp_name'];
        $orig_name = basename($file['name']);
        $file_size = $file['size'];

        // ── Validate size ──────────────────────────────────────
        if ($file_size > $this->max_file_size) {
            return [
                'success' => false,
                'error'   => 'File exceeds maximum size of ' . ($this->max_file_size / 1048576) . ' MB',
            ];
        }

        // ── Validate MIME (server-side, not trusting client) ──
        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($tmp_path);

        if (!array_key_exists($mime_type, $this->allowed_mime_types)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }

        $category = $this->allowed_mime_types[$mime_type];

        // ── Build storage path ─────────────────────────────────
        $conv_dir = $this->upload_base_path . $conversation_id . '/';
        if (!is_dir($conv_dir)) {
            mkdir($conv_dir, 0755, true);
        }

        $extension   = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        $stored_name = uniqid('tc_', true) . '.' . $extension;
        $dest_path   = $conv_dir . $stored_name;

        if (!move_uploaded_file($tmp_path, $dest_path)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }

        // ── Thumbnail for images ───────────────────────────────
        $thumbnail_path = null;
        if ($category === 'image') {
            $thumbnail_path = $this->_generate_thumbnail($dest_path, $conv_dir, $stored_name, $mime_type);
        }

        // ── DB record ─────────────────────────────────────────
        $db_path   = $conversation_id . '/' . $stored_name;
        $thumb_db  = $thumbnail_path
            ? $conversation_id . '/thumbs/' . basename($thumbnail_path)
            : null;

        $this->db->insert('chat_attachments', [
            'message_id'      => $message_id,
            'conversation_id' => $conversation_id,
            'uploader_id'     => $uploader_id,
            'original_name'   => $orig_name,
            'stored_name'     => $stored_name,
            'file_path'       => $db_path,
            'mime_type'       => $mime_type,
            'file_size'       => $file_size,
            'thumbnail_path'  => $thumb_db,
            'is_deleted'      => 0,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $attachment_id = $this->db->insert_id();

        if (!$attachment_id) {
            @unlink($dest_path);
            return ['success' => false, 'error' => 'Database error'];
        }

        return [
            'success'    => true,
            'attachment' => $this->_append_urls($this->get($attachment_id)),
        ];
    }

    /**
     * Attaches a previously uploaded attachment to a message.
     * Called after the message is created when upload happened first.
     */
    public function attach_to_message($attachment_id, $message_id)
    {
        $this->db->where('id', (int)$attachment_id)
                 ->update('chat_attachments', ['message_id' => (int)$message_id]);

        return $this->db->affected_rows() > 0;
    }

    // =========================================================
    // DELETE
    // =========================================================

    /**
     * Soft-deletes an attachment record.
     * Does NOT remove the file from disk (for audit trail).
     */
    public function soft_delete($attachment_id)
    {
        $this->db->where('id', (int)$attachment_id)
                 ->update('chat_attachments', ['is_deleted' => 1]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Hard-deletes an attachment record and removes the file from disk.
     * Used by the uninstall routine only.
     */
    public function hard_delete($attachment_id)
    {
        $row = $this->db->where('id', (int)$attachment_id)
                        ->get('chat_attachments')
                        ->row_array();

        if (!$row) {
            return false;
        }

        $full_path = $this->upload_base_path . $row['file_path'];
        if (is_file($full_path)) {
            @unlink($full_path);
        }

        if ($row['thumbnail_path']) {
            $thumb_full = $this->upload_base_path . $row['thumbnail_path'];
            if (is_file($thumb_full)) {
                @unlink($thumb_full);
            }
        }

        $this->db->where('id', $attachment_id)->delete('chat_attachments');
        return true;
    }

    /**
     * Deletes all attachment files for a conversation from disk.
     * Used during module uninstall.
     */
    public function delete_conversation_files($conversation_id)
    {
        $conv_dir = $this->upload_base_path . (int)$conversation_id . '/';

        if (is_dir($conv_dir)) {
            $this->_rrmdir($conv_dir);
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Appends full URL fields to an attachment record.
     */
    private function _append_urls(array $row)
    {
        $row['file_url']      = $this->upload_base_url . $row['file_path'];
        $row['thumbnail_url'] = $row['thumbnail_path']
            ? $this->upload_base_url . $row['thumbnail_path']
            : null;
        $row['category']      = $this->allowed_mime_types[$row['mime_type']] ?? 'other';
        $row['file_size_kb']  = round($row['file_size'] / 1024, 1);
        $row['file_size_mb']  = round($row['file_size'] / 1048576, 2);

        return $row;
    }

    /**
     * Generates a thumbnail for an uploaded image using GD.
     * Returns the full disk path to the thumbnail, or null on failure.
     */
    private function _generate_thumbnail($source_path, $conv_dir, $stored_name, $mime_type)
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $thumb_width  = 320;
        $thumb_height = 240;

        try {
            switch ($mime_type) {
                case 'image/jpeg':
                    $src = @imagecreatefromjpeg($source_path);
                    break;
                case 'image/png':
                    $src = @imagecreatefrompng($source_path);
                    break;
                case 'image/gif':
                    $src = @imagecreatefromgif($source_path);
                    break;
                case 'image/webp':
                    $src = function_exists('imagecreatefromwebp')
                        ? @imagecreatefromwebp($source_path)
                        : null;
                    break;
                default:
                    return null;
            }

            if (!$src) {
                return null;
            }

            $orig_w = imagesx($src);
            $orig_h = imagesy($src);

            // Calculate proportional thumbnail dimensions
            $ratio    = min($thumb_width / $orig_w, $thumb_height / $orig_h);
            $new_w    = (int)round($orig_w * $ratio);
            $new_h    = (int)round($orig_h * $ratio);

            $thumb = imagecreatetruecolor($new_w, $new_h);

            // Preserve PNG transparency
            if ($mime_type === 'image/png') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefilledrectangle($thumb, 0, 0, $new_w, $new_h, $transparent);
            }

            imagecopyresampled($thumb, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

            // Store thumbnail in a thumbs/ subfolder
            $thumb_dir = $conv_dir . 'thumbs/';
            if (!is_dir($thumb_dir)) {
                mkdir($thumb_dir, 0755, true);
            }

            $thumb_name = 'thumb_' . $stored_name;
            $thumb_path = $thumb_dir . $thumb_name;

            switch ($mime_type) {
                case 'image/jpeg':
                    imagejpeg($thumb, $thumb_path, 85);
                    break;
                case 'image/png':
                    imagepng($thumb, $thumb_path, 7);
                    break;
                case 'image/gif':
                    imagegif($thumb, $thumb_path);
                    break;
                case 'image/webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($thumb, $thumb_path, 85);
                    }
                    break;
            }

            imagedestroy($src);
            imagedestroy($thumb);

            return $thumb_path;

        } catch (Exception $e) {
            log_message('error', 'Team Chat thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Recursively removes a directory and all its contents.
     */
    private function _rrmdir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->_rrmdir($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}