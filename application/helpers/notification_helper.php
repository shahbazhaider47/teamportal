<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('notify_user')) {
    function notify_user($user_id, $feature_key, $short_text, $full_text, $action_url = null, array $opts = [])
    {
        $CI =& get_instance();
        $CI->load->model('Notification_model', 'notifs');
        $channels = $opts['channels'] ?? ['in_app', 'email'];
        return $CI->notifs->insert([
            'user_id'     => (int)$user_id,
            'feature_key' => (string)$feature_key,
            'short_text'  => (string)$short_text,
            'full_text'   => (string)$full_text,
            'action_url'  => $action_url ? (string)$action_url : null,
            '_channels'   => $channels,
        ]);
    }
}

if (!function_exists('show_alert')) {
    function show_alert(): string
    {
        $CI =& get_instance();
        $alert = $CI->session->flashdata('alert');

        if (!$alert || !isset($alert['type'], $alert['message'])) {
            return '';
        }

        $type    = htmlspecialchars($alert['type'], ENT_QUOTES);
        $message = htmlspecialchars($alert['message'], ENT_QUOTES);

        return <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show shadow position-fixed top-0 end-0 m-4" role="alert" style="min-width: 300px; z-index: 1055;">
            {$message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 3000);
        </script>
HTML;
    }
}

