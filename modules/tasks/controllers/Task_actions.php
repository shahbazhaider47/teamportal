<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tasks Controller — final aligned with view contracts.
 * - Accepts comment_html/comment/body
 * - Checklist adapters: /tasks/checklist/{toggle|delete|add}/{id}
 * - Attachments adapters: /tasks/attachments/{taskId}/upload and /tasks/attachments/{id}/delete
 */
class Task_actions extends App_Controller
{
    protected int $uid = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('tasks/Tasks_model', 'tasks');
        $this->load->model('User_model',        'users');
        $this->load->model('tasks/Task_comment_replies_model', 'replies'); // <— definitive
        $this->load->library('tasks/Tasks_policy', null, 'policy');

        $this->load->library(['form_validation', 'session']);
        $this->load->helper(['url', 'form', 'file']);

        $this->uid = (int) $this->session->userdata('user_id');
        if (!$this->uid) {
            if ($this->input->is_ajax_request()) {
                return $this->json_error('Not authenticated.', 401);
            }
            redirect('authentication/login');
            exit;
        }
    }


    /**
     * Centralized activity logger.
     * Writes to table: task_activity(id, taskid, user_id, activity, description, dateadded)
     */
    private function log_activity(int $taskId, string $activity, array $payload = [], ?int $userId = null): void
    {
        try {
            $row = [
                'taskid'     => $taskId,
                'user_id'    => $userId ?? $this->uid ?: null, // allow null for system actions
                'activity'   => substr($activity, 0, 64),
                'description'=> json_encode([
                    'payload' => $payload,
                    'meta'    => [
                        'ip'  => $this->input->ip_address(),
                        'ua'  => (string) $this->input->user_agent(),
                        'ts'  => date('Y-m-d H:i:s'),
                    ]
                ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'dateadded'  => date('Y-m-d H:i:s'),
            ];
            $this->db->insert('task_activity', $row);
        } catch (Throwable $e) {
            log_message('error', '[task_activity] log failed: '.$e->getMessage());
            // never break UX for logging
        }
    }
    
    /** Convenience to safely excerpt large bodies for logs */
    private function _excerpt(string $str, int $len = 180): string
    {
        $s = trim(strip_tags($str));
        if (mb_strlen($s) <= $len) return $s;
        return mb_substr($s, 0, $len - 1) . '…';
    }
    
    
}
