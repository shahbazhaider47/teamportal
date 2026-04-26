<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * team_chat_helper
 *
 * Shared utility functions for the Team Chat module.
 * Loaded by both controllers via:
 *   $this->load->helper('team_chat/team_chat');
 */

// ─────────────────────────────────────────────────────────────
// SLUG & NAMING
// ─────────────────────────────────────────────────────────────

if (!function_exists('team_chat_make_slug')) {
    /**
     * Converts a channel name to a URL-safe slug.
     * Example: "HR Announcements" → "hr-announcements"
     *
     * @param  string $name
     * @return string
     */
    function team_chat_make_slug($name)
    {
        $slug = mb_strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\s\-_]/u', '', $slug);
        $slug = preg_replace('/[\s\-_]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug ?: 'channel';
    }
}

if (!function_exists('team_chat_conversation_display_name')) {
    /**
     * Returns the display name for a conversation.
     * For direct conversations returns the peer's full name.
     * For channels/groups returns the conversation name.
     *
     * @param  array $conversation  Conversation row (must include 'peer' key for directs)
     * @param  int   $current_user_id
     * @return string
     */
    function team_chat_conversation_display_name(array $conversation, $current_user_id = 0)
    {
        if ($conversation['type'] === 'direct') {
            $peer = $conversation['peer'] ?? [];
            return !empty($peer['fullname']) ? $peer['fullname'] : 'Unknown User';
        }

        return !empty($conversation['name']) ? $conversation['name'] : 'Unnamed Conversation';
    }
}

if (!function_exists('team_chat_conversation_avatar')) {
    /**
     * Returns the avatar URL for a conversation.
     * For direct conversations returns the peer's profile image.
     * For channels/groups returns the conversation avatar or a fallback initials URL.
     *
     * @param  array  $conversation
     * @param  string $module_url     TEAM_CHAT_MODULE_URL constant value
     * @return string
     */
    function team_chat_conversation_avatar(array $conversation, $module_url = '')
    {
        if ($conversation['type'] === 'direct') {
            $peer = $conversation['peer'] ?? [];

            if (!empty($peer['profile_image'])) {
                return base_url('uploads/staff_profile_images/' . $peer['profile_image']);
            }

            return team_chat_initials_avatar(
                $peer['fullname'] ?? '?',
                $module_url
            );
        }

        if (!empty($conversation['avatar'])) {
            return base_url('uploads/team_chat/' . $conversation['avatar']);
        }

        return team_chat_initials_avatar(
            $conversation['name'] ?? '?',
            $module_url
        );
    }
}

if (!function_exists('team_chat_initials_avatar')) {
    /**
     * Generates a data URI SVG avatar using the first two initials of a name.
     * Used as a fallback when no profile image is set.
     *
     * @param  string $name
     * @param  string $module_url   Unused — kept for signature consistency
     * @return string               A base_url path or a data URI SVG
     */
    function team_chat_initials_avatar($name, $module_url = '')
    {
        $initials = team_chat_initials($name);
        $color    = team_chat_avatar_color($name);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40">'
             . '<rect width="40" height="40" rx="20" fill="' . $color . '"/>'
             . '<text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" '
             . 'font-family="sans-serif" font-size="15" font-weight="500" fill="#fff">'
             . htmlspecialchars($initials, ENT_QUOTES)
             . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

// ─────────────────────────────────────────────────────────────
// INITIALS & COLORS
// ─────────────────────────────────────────────────────────────

if (!function_exists('team_chat_initials')) {
    /**
     * Extracts up to two initials from a full name.
     * "Ali Hassan" → "AH", "Omar" → "O", "HR Team" → "HT"
     *
     * @param  string $name
     * @return string
     */
    function team_chat_initials($name)
    {
        $name  = trim($name);
        $words = preg_split('/\s+/', $name);

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1));
    }
}

if (!function_exists('team_chat_avatar_color')) {
    /**
     * Returns a deterministic hex color for a name.
     * Same name always produces the same color — no DB needed.
     *
     * @param  string $name
     * @return string  Hex color e.g. "#4f46e5"
     */
    function team_chat_avatar_color($name)
    {
        $palette = [
            '#4f46e5', // indigo
            '#0891b2', // cyan
            '#059669', // emerald
            '#d97706', // amber
            '#dc2626', // red
            '#7c3aed', // violet
            '#db2777', // pink
            '#ea580c', // orange
            '#16a34a', // green
            '#0284c7', // sky
        ];

        $index = abs(crc32($name)) % count($palette);
        return $palette[$index];
    }
}

// ─────────────────────────────────────────────────────────────
// TIME FORMATTING
// ─────────────────────────────────────────────────────────────

if (!function_exists('team_chat_time_ago')) {
    /**
     * Returns a human-friendly relative time string.
     * "just now", "2 min ago", "yesterday", "Apr 18", "18/04/2024"
     *
     * @param  string $datetime  MySQL datetime string
     * @return string
     */
    function team_chat_time_ago($datetime)
    {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
            return '';
        }

        $timestamp = strtotime($datetime);
        $now       = time();
        $diff      = $now - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }

        if ($diff < 3600) {
            $mins = (int)floor($diff / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 86400) {
            $hours = (int)floor($diff / 3600);
            return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 172800) { // 2 days
            return 'yesterday';
        }

        if ($diff < 604800) { // 7 days
            $days = (int)floor($diff / 86400);
            return $days . ' days ago';
        }

        // Same year → show day/month
        if (date('Y', $timestamp) === date('Y')) {
            return date('M j', $timestamp);
        }

        return date('d/m/Y', $timestamp);
    }
}

if (!function_exists('team_chat_message_time')) {
    /**
     * Returns time string suitable for displaying on a message bubble.
     * Shows time (HH:MM) for today, full date + time otherwise.
     *
     * @param  string $datetime
     * @return string
     */
    function team_chat_message_time($datetime)
    {
        if (empty($datetime)) {
            return '';
        }

        $timestamp = strtotime($datetime);

        if (date('Y-m-d') === date('Y-m-d', $timestamp)) {
            return date('H:i', $timestamp);
        }

        if (date('Y') === date('Y', $timestamp)) {
            return date('M j, H:i', $timestamp);
        }

        return date('M j Y, H:i', $timestamp);
    }
}

if (!function_exists('team_chat_date_divider')) {
    /**
     * Returns a date label for a message group divider.
     * "Today", "Yesterday", "Monday", "Apr 18", "18/04/2024"
     *
     * @param  string $datetime
     * @return string
     */
    function team_chat_date_divider($datetime)
    {
        $timestamp = strtotime($datetime);
        $today     = strtotime('today');
        $yesterday = strtotime('yesterday');

        if ($timestamp >= $today) {
            return 'Today';
        }

        if ($timestamp >= $yesterday) {
            return 'Yesterday';
        }

        // Within the last 7 days — show day name
        if ((time() - $timestamp) < 604800) {
            return date('l', $timestamp); // Monday, Tuesday...
        }

        // Same year
        if (date('Y', $timestamp) === date('Y')) {
            return date('F j', $timestamp); // April 18
        }

        return date('F j, Y', $timestamp);
    }
}

// ─────────────────────────────────────────────────────────────
// BODY PARSING & SANITIZATION
// ─────────────────────────────────────────────────────────────

if (!function_exists('team_chat_parse_body')) {
    /**
     * Parses a raw message body for display.
     * Converts @mentions to highlighted spans,
     * converts URLs to clickable links,
     * escapes HTML, and preserves newlines.
     *
     * @param  string $body
     * @return string  Safe HTML string
     */
    function team_chat_parse_body($body)
    {
        if (empty($body)) {
            return '';
        }

        // Escape HTML first — do NOT trust stored content
        $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');

        // Restore newlines as <br>
        $body = nl2br($body);

        // Linkify URLs (http/https)
        $body = preg_replace(
            '/(https?:\/\/[^\s<>"\']+)/i',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="tc-link">$1</a>',
            $body
        );

        // Highlight @mentions
        $body = preg_replace(
            '/@([a-zA-Z0-9._-]+)/',
            '<span class="tc-mention">@$1</span>',
            $body
        );

        // Bold: **text**
        $body = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $body);

        // Italic: _text_
        $body = preg_replace('/_(.+?)_/s', '<em>$1</em>', $body);

        // Inline code: `code`
        $body = preg_replace('/`([^`]+)`/', '<code class="tc-code-inline">$1</code>', $body);

        return $body;
    }
}

if (!function_exists('team_chat_sanitize_input')) {
    /**
     * Sanitizes raw user input before storing.
     * Strips all HTML tags, trims whitespace, limits length.
     *
     * @param  string $input
     * @param  int    $max_length  0 = no limit
     * @return string
     */
    function team_chat_sanitize_input($input, $max_length = 10000)
    {
        $input = strip_tags(trim($input));

        if ($max_length > 0 && mb_strlen($input) > $max_length) {
            $input = mb_substr($input, 0, $max_length);
        }

        return $input;
    }
}

if (!function_exists('team_chat_message_preview')) {
    /**
     * Returns a short plain-text preview of a message body.
     * Strips HTML, removes @, trims, and truncates.
     *
     * @param  string $body
     * @param  int    $length
     * @return string
     */
    function team_chat_message_preview($body, $length = 80)
    {
        $body = strip_tags($body);
        $body = preg_replace('/@\w+/', '', $body);
        $body = preg_replace('/\s+/', ' ', trim($body));

        return mb_strlen($body) > $length
            ? mb_substr($body, 0, $length) . '…'
            : $body;
    }
}

// ─────────────────────────────────────────────────────────────
// FILE HELPERS
// ─────────────────────────────────────────────────────────────

if (!function_exists('team_chat_file_icon_class')) {
    /**
     * Returns a Tabler icon class for a given MIME type.
     *
     * @param  string $mime_type
     * @return string  e.g. "ti ti-file-type-pdf"
     */
    function team_chat_file_icon_class($mime_type)
    {
        $map = [
            'image/jpeg'       => 'ti ti-photo',
            'image/png'        => 'ti ti-photo',
            'image/gif'        => 'ti ti-gif',
            'image/webp'       => 'ti ti-photo',
            'image/svg+xml'    => 'ti ti-vector',
            'application/pdf'  => 'ti ti-file-type-pdf',
            'application/msword'                                                        => 'ti ti-file-type-doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'ti ti-file-type-docx',
            'application/vnd.ms-excel'                                                  => 'ti ti-file-type-xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'ti ti-file-type-xlsx',
            'application/vnd.ms-powerpoint'                                             => 'ti ti-file-type-ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'ti ti-presentation',
            'text/plain'       => 'ti ti-file-text',
            'text/csv'         => 'ti ti-file-type-csv',
            'application/zip'  => 'ti ti-file-zip',
            'application/x-rar-compressed' => 'ti ti-file-zip',
            'application/x-7z-compressed'  => 'ti ti-file-zip',
            'video/mp4'        => 'ti ti-video',
            'video/webm'       => 'ti ti-video',
            'audio/mpeg'       => 'ti ti-music',
            'audio/ogg'        => 'ti ti-music',
            'audio/wav'        => 'ti ti-music',
        ];

        return $map[$mime_type] ?? 'ti ti-file';
    }
}

if (!function_exists('team_chat_format_file_size')) {
    /**
     * Formats a byte count into a human-readable string.
     * 1024 → "1 KB", 1048576 → "1 MB"
     *
     * @param  int $bytes
     * @return string
     */
    function team_chat_format_file_size($bytes)
    {
        $bytes = (int)$bytes;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }
}

if (!function_exists('team_chat_is_image_mime')) {
    /**
     * Returns true if the MIME type is a renderable image.
     *
     * @param  string $mime_type
     * @return bool
     */
    function team_chat_is_image_mime($mime_type)
    {
        return in_array($mime_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ]);
    }
}

// ─────────────────────────────────────────────────────────────
// PERMISSION SHORTCUTS
// ─────────────────────────────────────────────────────────────

if (!function_exists('team_chat_can')) {
    /**
     * Shortcut permission check for chat-specific actions.
     * Wraps the global staff_can() function.
     *
     * @param  string $action  'access'|'create_channel'|'manage_channel'|'delete_message'|'view_all'
     * @return bool
     */
    function team_chat_can($action)
    {
        return (bool)staff_can($action, 'team_chat');
    }
}

// ─────────────────────────────────────────────────────────────
// WS TOKEN
// ─────────────────────────────────────────────────────────────

if (!function_exists('team_chat_ws_token')) {
    /**
     * Generates a signed WebSocket authentication token for a user.
     * Token = HMAC-SHA256(user_id, TEAM_CHAT_WS_SECRET)
     * Matches the validation in Team_chat_socket_server.
     *
     * @param  int $user_id
     * @return string
     */
    function team_chat_ws_token($user_id)
    {
        $secret = defined('TEAM_CHAT_WS_SECRET') ? TEAM_CHAT_WS_SECRET : 'changeme_secret';
        return hash_hmac('sha256', (string)(int)$user_id, $secret);
    }
}

// ─────────────────────────────────────────────────────────────
// CONVERSATION TYPE HELPERS
// ─────────────────────────────────────────────────────────────

if (!function_exists('team_chat_type_label')) {
    /**
     * Returns a human-readable label for a conversation type.
     *
     * @param  string $type  'direct'|'group'|'channel'
     * @return string
     */
    function team_chat_type_label($type)
    {
        $map = [
            'direct'  => 'Direct Message',
            'group'   => 'Group',
            'channel' => 'Channel',
        ];

        return $map[$type] ?? ucfirst($type);
    }
}

if (!function_exists('team_chat_type_icon')) {
    /**
     * Returns a Tabler icon class for a conversation type.
     *
     * @param  string $type
     * @return string
     */
    function team_chat_type_icon($type)
    {
        $map = [
            'direct'  => 'ti ti-user',
            'group'   => 'ti ti-users',
            'channel' => 'ti ti-hash',
        ];

        return $map[$type] ?? 'ti ti-message';
    }
}

if (!function_exists('team_chat_online_status')) {
    /**
     * Returns an online status label and CSS class for a user.
     *
     * @param  bool   $is_online
     * @param  string $last_seen_at
     * @return array  ['label' => string, 'class' => string]
     */
    function team_chat_online_status($is_online, $last_seen_at = null)
    {
        if ($is_online) {
            return ['label' => 'Online', 'class' => 'tc-status--online'];
        }

        if ($last_seen_at) {
            $diff = time() - strtotime($last_seen_at);

            if ($diff < 300) { // 5 minutes
                return ['label' => 'Away', 'class' => 'tc-status--away'];
            }

            return [
                'label' => 'Last seen ' . team_chat_time_ago($last_seen_at),
                'class' => 'tc-status--offline',
            ];
        }

        return ['label' => 'Offline', 'class' => 'tc-status--offline'];
    }
}