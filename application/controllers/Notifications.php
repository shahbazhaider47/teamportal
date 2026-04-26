<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Require login (hard stop)
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login');
            exit;
        }

        $this->load->model('Notification_model');
        $this->load->model('User_model');
    }

    /**
     * Full-screen notifications center.
     * URL: /notifications
     */
    public function index()
    {
        $userId = (int)$this->session->userdata('user_id');

        // Collect filters from GET
        $rawFilter = (string)$this->input->get('filter', true);   // 'unread' or ''
        $feature   = (string)$this->input->get('type', true);     // feature_key
        $dateFrom  = (string)$this->input->get('date_from', true);
        $dateTo    = (string)$this->input->get('date_to', true);

        // Build filters for query
        $filters = [];
        if ($rawFilter === 'unread') $filters['is_read'] = 0;
        if ($feature !== '')         $filters['feature_key'] = $feature;
        if ($dateFrom !== '')        $filters['date_from'] = $dateFrom;
        if ($dateTo !== '')          $filters['date_to']   = $dateTo;

        // Optional single mark_read via GET param (guard ownership)
        $markReadId = (int)$this->input->get('mark_read');
        if ($markReadId > 0) {
            $exists = $this->db->where('id', $markReadId)->where('user_id', $userId)->get('notifications')->row();
            if ($exists && (int)$exists->is_read === 0) {
                $this->Notification_model->mark_read($markReadId);
            }
        }

        // Fetch list with sender info (LEFT JOIN)
        $qb = $this->db->select('n.*, s.firstname AS sender_first, s.lastname AS sender_last, s.profile_image AS sender_image')
                       ->from('notifications n')
                       ->join('users s', 'n.sender_id = s.id', 'left')
                       ->where('n.user_id', $userId);

        if (array_key_exists('is_read', $filters)) {
            $qb->where('n.is_read', (int)$filters['is_read']);
        }
        if (!empty($filters['feature_key'])) {
            $qb->where('n.feature_key', $filters['feature_key']);
        }
        if (!empty($filters['date_from'])) {
            $qb->where('n.created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $qb->where('n.created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        $data['notifications'] = $qb->order_by('n.created_at', 'DESC')->get()->result_array();
        $data['filters'] = [
            'filter'     => $rawFilter,
            'type'       => $feature,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
        ];

        $this->load->view('layouts/master', [
            'page_title' => 'All Notifications',
            'subview'    => 'notifications/index',
            'view_data'  => $data,
        ]);
    }

    /**
     * Mark notifications as read.
     * POST (no id): mark all unread for current user.
     * GET  (with id): mark a single id after ownership check.
     */
    public function mark_read($id = null)
    {
        $userId = (int)$this->session->userdata('user_id');

        if (strtoupper($this->input->method()) === 'POST') {
            // Mark all unread for this user
            $ids = $this->db->select('id')
                            ->from('notifications')
                            ->where('user_id', $userId)
                            ->where('is_read', 0)
                            ->get()->result_array();
            $idList = array_column($ids, 'id');
            if (!empty($idList)) {
                $this->Notification_model->mark_read($idList);
            }
        } else {
            $nid = (int)$id;
            if ($nid > 0) {
                $exists = $this->db->where('id', $nid)->where('user_id', $userId)->get('notifications')->row();
                if ($exists) {
                    $this->Notification_model->mark_read($nid);
                }
            }
        }

        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['success' => true]));
            return;
        }

        redirect('notifications');
    }

    /**
     * View a single notification (and mark read).
     * URL: /notifications/view/{id}
     */
    public function view($id = null)
    {
        $nid = (int)$id;
        if ($nid <= 0) { show_404(); return; }

        $userId = (int)$this->session->userdata('user_id');

        $notif = $this->db->where('id', $nid)
                          ->where('user_id', $userId)
                          ->get('notifications')
                          ->row_array();

        if (!$notif) { show_404(); return; }

        if ((int)$notif['is_read'] === 0) {
            $this->Notification_model->mark_read($nid);
            $notif['is_read'] = 1;
        }

        $this->load->view('layouts/master', [
            'page_title' => 'Notification Details',
            'subview'    => 'notifications/view',
            'view_data'  => ['notification' => $notif],
        ]);
    }

    /**
     * AJAX: unread count + list for the bell.
     * URL: /notifications/unread
     */
    public function unread()
    {
        $userId = (int)$this->session->userdata('user_id');

        // Dropdown limit with safe fallback
        $limit = 5;
        if (function_exists('get_system_setting')) {
            $limStr = get_system_setting('notification_dropdown_limit', 5);
            $limit = (int)$limStr > 0 ? (int)$limStr : 5;
        }

        $count = $this->Notification_model->count_unread($userId);
        $list  = $this->Notification_model->get_unread($userId, $limit);

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode([
                         'count' => (int)$count,
                         'list'  => $list,
                     ]));
    }

    /**
     * Delete all notifications for the current user.
     */
    public function clear_all()
    {
        if (!$this->session->userdata('is_logged_in')) {
            redirect('authentication/login'); exit;
        }

        $userId = (int)$this->session->userdata('user_id');
        $this->Notification_model->clear_all_for_user($userId);

        set_alert('success', 'All notifications cleared');
        redirect('notifications');
    }

    /**
     * AJAX: mark one notification as read (ownership-guarded).
     */
    public function mark_as_read_ajax()
    {
        if (!$this->input->is_ajax_request()) { show_404(); return; }

        $id = (int)$this->input->post('id');
        $userId = (int)$this->session->userdata('user_id');

        $this->output->set_content_type('application/json');

        if ($id <= 0 || $userId <= 0) {
            $this->output->set_output(json_encode(['status' => 'error', 'message' => 'Invalid request.']));
            return;
        }

        $exists = $this->db->where('id', $id)->where('user_id', $userId)->get('notifications')->row();
        if (!$exists) {
            $this->output->set_output(json_encode(['status' => 'error', 'message' => 'Notification not found.']));
            return;
        }

        $this->Notification_model->mark_read($id);
        $this->output->set_output(json_encode(['status' => 'success', 'id' => $id]));
    }
}
