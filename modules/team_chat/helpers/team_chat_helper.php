<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('team_chat_can')) {
    function team_chat_can($action)
    {
        return function_exists('staff_can') ? (bool) staff_can($action, 'team_chat') : true;
    }
}

if (!function_exists('team_chat_user_display_name')) {
    function team_chat_user_display_name(array $user)
    {
        $name = trim((string)($user['fullname'] ?? ''));
        if ($name === '') {
            $name = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        }
        if ($name === '') {
            $name = $user['username'] ?? $user['email'] ?? ('User #' . (int)($user['id'] ?? 0));
        }
        return $name;
    }
}

if (!function_exists('team_chat_user_avatar_url')) {
    function team_chat_user_avatar_url($profile_image, $fallback_name = '')
    {
        $profile_image = trim((string)$profile_image);
        if ($profile_image !== '') {
            if (preg_match('#^https?://#i', $profile_image) || strpos($profile_image, 'data:') === 0) {
                return $profile_image;
            }
            return base_url('uploads/users/profile/' . ltrim($profile_image, '/'));
        }
        return team_chat_initials_avatar($fallback_name ?: '?');
    }
}

if (!function_exists('team_chat_initials_avatar')) {
    function team_chat_initials_avatar($name)
    {
        $name = trim((string)$name);
        $initials = team_chat_initials($name ?: '?');
        $palette = ['#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626', '#7c3aed', '#db2777'];
        $color = $palette[abs(crc32($name ?: '?')) % count($palette)];
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" rx="20" fill="' . $color . '"/><text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" font-weight="600" fill="#fff">' . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') . '</text></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

if (!function_exists('team_chat_initials')) {
    function team_chat_initials($name)
    {
        $words = preg_split('/\s+/', trim((string)$name));
        if (!$words || $words[0] === '') {
            return '?';
        }
        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }
        return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
    }
}

if (!function_exists('team_chat_make_slug')) {
    function team_chat_make_slug($name)
    {
        $slug = strtolower(trim((string)$name));
        $slug = preg_replace('/[^a-z0-9\s\-_]/', '', $slug);
        $slug = preg_replace('/[\s\-_]+/', '-', $slug);
        return trim($slug, '-') ?: 'channel';
    }
}

if (!function_exists('team_chat_parse_body')) {
    function team_chat_parse_body($body)
    {
        $body = htmlspecialchars((string)$body, ENT_QUOTES, 'UTF-8');
        $body = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener noreferrer" class="tc-link">$1</a>', $body);
        $body = preg_replace('/@([a-zA-Z0-9._-]+)/', '<span class="tc-mention">@$1</span>', $body);
        $body = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $body);
        $body = preg_replace('/`([^`]+)`/', '<code class="tc-code-inline">$1</code>', $body);
        return nl2br($body);
    }
}

if (!function_exists('team_chat_time_ago')) {
    function team_chat_time_ago($datetime)
    {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return '';
        }
        $ts = strtotime($datetime);
        if (!$ts) return '';
        $diff = time() - $ts;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date(date('Y') === date('Y', $ts) ? 'M j' : 'M j, Y', $ts);
    }
}

if (!function_exists('team_chat_message_time')) {
    function team_chat_message_time($datetime)
    {
        $ts = strtotime((string)$datetime);
        if (!$ts) return '';
        return date('Y-m-d') === date('Y-m-d', $ts) ? date('H:i', $ts) : date('M j, H:i', $ts);
    }
}

if (!function_exists('team_chat_date_divider')) {
    function team_chat_date_divider($datetime)
    {
        $ts = strtotime((string)$datetime);
        if (!$ts) return '';
        if (date('Y-m-d') === date('Y-m-d', $ts)) return 'Today';
        if (date('Y-m-d', strtotime('-1 day')) === date('Y-m-d', $ts)) return 'Yesterday';
        return date(date('Y') === date('Y', $ts) ? 'F j' : 'F j, Y', $ts);
    }
}

if (!function_exists('team_chat_conversation_display_name')) {
    function team_chat_conversation_display_name(array $conversation, $current_user_id = 0)
    {
        if (($conversation['type'] ?? '') === 'direct') {
            return !empty($conversation['peer']) ? team_chat_user_display_name($conversation['peer']) : 'Direct Message';
        }
        return trim((string)($conversation['name'] ?? '')) ?: 'Unnamed Conversation';
    }
}

if (!function_exists('team_chat_conversation_avatar')) {
    function team_chat_conversation_avatar(array $conversation)
    {
        if (($conversation['type'] ?? '') === 'direct') {
            $peer = $conversation['peer'] ?? [];
            return team_chat_user_avatar_url($peer['profile_image'] ?? '', team_chat_user_display_name($peer));
        }
        return !empty($conversation['avatar']) ? base_url('uploads/team_chat/' . ltrim($conversation['avatar'], '/')) : team_chat_initials_avatar($conversation['name'] ?? 'Chat');
    }
}

if (!function_exists('team_chat_format_file_size')) {
    function team_chat_format_file_size($bytes)
    {
        $bytes = (int)$bytes;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}

if (!function_exists('team_chat_file_icon_class')) {
    function team_chat_file_icon_class($mime)
    {
        if (strpos((string)$mime, 'image/') === 0) return 'ti ti-photo';
        if ($mime === 'application/pdf') return 'ti ti-file-type-pdf';
        if (strpos((string)$mime, 'video/') === 0) return 'ti ti-video';
        if (strpos((string)$mime, 'audio/') === 0) return 'ti ti-music';
        if (strpos((string)$mime, 'zip') !== false) return 'ti ti-file-zip';
        return 'ti ti-file';
    }
}

if (!function_exists('team_chat_is_image_mime')) {
    function team_chat_is_image_mime($mime)
    {
        return in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'], true);
    }
}

if (!function_exists('team_chat_ws_token')) {
    function team_chat_ws_token($user_id)
    {
        $secret = defined('TEAM_CHAT_WS_SECRET') ? TEAM_CHAT_WS_SECRET : (defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'team_chat_secret');
        return hash_hmac('sha256', (string)(int)$user_id, $secret);
    }
}
