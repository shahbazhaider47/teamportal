<?php defined('BASEPATH') OR exit('No direct script access allowed');

class NotificationHook
{
    public function init()
    {
        $CI =& get_instance();

        // Only if the user is logged in
        if (! $CI->session->userdata('is_logged_in')) {
            return;
        }

        // Load model + helper
        $CI->load->model('Notification_model');
        $CI->load->helper('settings'); // Load this to access get_system_setting()

        $userId = $CI->session->userdata('user_id');

        // 🔄 Get limit from system settings (default to 5 if not found)
        $limit = (int) get_system_setting('notification_dropdown_limit', 5);

        // Get unread data with dynamic limit
        $count = $CI->Notification_model->count_unread($userId);
        $list  = $CI->Notification_model->get_unread($userId, $limit);

        // Make data globally available
        $CI->load->vars([
            'notifUnreadCount' => $count,
            'notifUnreadList'  => $list,
        ]);
    }
}
