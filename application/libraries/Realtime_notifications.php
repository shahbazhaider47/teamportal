<?php defined('BASEPATH') or exit('No direct script access allowed');

use Pusher\Pusher;

class Realtime_notifications
{
    /** @var CI_Controller */
    protected $CI;

    /** @var Pusher|null */
    protected $pusher = null;

    public function __construct()
    {
        $this->CI =& get_instance();

        // Adjust these to your actual setting keys in system_settings
        if (!function_exists('get_system_setting')) {
            // If helper is not loaded, bail out quietly
            return;
        }
        
        $enabled = (int) get_system_setting('pusher_realtime_notifications', 0);
        $app_id  = get_system_setting('pusher_app_id', '');
        $key     = get_system_setting('pusher_app_key', '');
        $secret  = get_system_setting('pusher_app_secret', '');
        $cluster = get_system_setting('pusher_cluster', 'mt1'); // default cluster if not set

        if (
            $enabled
            && !empty($app_id)
            && !empty($key)
            && !empty($secret)
        ) {
            require_once APPPATH . 'third_party/pusher_autoload.php';

            $options = [
                'cluster' => $cluster,
                'useTLS'  => true,
            ];

            $this->pusher = new Pusher($key, $secret, $app_id, $options);
                } else {
            log_message('debug', 'Realtime_notifications: Pusher not initialised (enabled=' . $enabled . ', app_id=' . $app_id . ', key=' . $key . ')');
        }
    }

    /**
     * Broadcast a freshly created notification to the target user.
     *
     * @param array $notificationRow The row as stored in DB (id, user_id, description, url, etc.)
     */
    public function push(array $notificationRow): void
    {
        // Debug log to confirm invocation
        log_message(
            'debug',
            'Realtime_notifications: pushing to user-' . ($notificationRow['user_id'] ?? 0) .
            ' event notification.created payload: ' . json_encode($notificationRow)
        );
    
        if (!$this->pusher) {
            log_message('debug', 'Realtime_notifications: Pusher client not initialised, skipping trigger.');
            return;
        }
    
        $userId = (int)($notificationRow['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }
    
        $channel = 'user-' . $userId;
        $event   = 'notification.created';
    
        $payload = [
            'id'          => (int)$notificationRow['id'],
            'title'       => $notificationRow['title']       ?? '',
            'description' => $notificationRow['description'] ?? '',
            'link'        => $notificationRow['link']        ?? '',
            'icon'        => $notificationRow['icon']        ?? '',
            'created_at'  => $notificationRow['date']        ?? date('Y-m-d H:i:s'),
            'is_read'     => (int)($notificationRow['is_read'] ?? 0),
        ];
    
        try {
            $this->pusher->trigger($channel, $event, $payload);
        } catch (\Exception $e) {
            log_message('error', 'Realtime_notifications push failed: ' . $e->getMessage());
        }
    }
}
