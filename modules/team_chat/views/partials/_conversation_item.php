<?php defined('BASEPATH') or exit('No direct script access allowed');
$name = team_chat_conversation_display_name($conv, $user_id ?? 0);
$avatar = team_chat_conversation_avatar($conv);
?>
<li class="tc-conv-item <?php echo $active_class ?? ''; ?>" data-open-conversation="<?php echo (int)$conv['id']; ?>">
    <img class="tc-avatar" src="<?php echo html_escape($avatar); ?>" alt="">
    <div class="tc-conv-body">
        <div class="tc-conv-row"><span class="tc-conv-name"><?php echo html_escape($name); ?></span><span class="tc-conv-time"><?php echo team_chat_time_ago($conv['last_message_at'] ?? $conv['last_activity_at'] ?? ''); ?></span></div>
        <div class="tc-conv-row"><span class="tc-conv-preview"><?php echo html_escape($conv['last_message_preview'] ?? 'No messages yet'); ?></span><?php if (!empty($conv['unread_count'])): ?><span class="tc-badge"><?php echo (int)$conv['unread_count']; ?></span><?php endif; ?></div>
    </div>
</li>
