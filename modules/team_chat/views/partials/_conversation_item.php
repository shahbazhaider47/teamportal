<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * _conversation_item.php
 * Variables: $conv, $active_class, $current_user
 */
$conv_id      = (int)$conv['id'];
$type         = $conv['type'];
$unread       = (int)($conv['unread_count'] ?? 0);
$is_muted     = (bool)($conv['is_muted']    ?? false);
$is_archived  = (bool)($conv['is_archived'] ?? false);
$display_name = team_chat_conversation_display_name($conv, $current_user);
$avatar_url   = team_chat_conversation_avatar($conv, defined('TEAM_CHAT_MODULE_URL') ? TEAM_CHAT_MODULE_URL : '');
$preview      = team_chat_message_preview($conv['last_message_body'] ?? '', 55);
$time_label   = !empty($conv['last_message_at']) ? team_chat_time_ago($conv['last_message_at']) : '';
$type_icon    = team_chat_type_icon($type);
?>
<li class="tc-conv-item <?php echo $active_class; ?> <?php echo $is_archived ? 'tc-conv-item--archived' : ''; ?>"
    data-conv-id="<?php echo $conv_id; ?>"
    data-conv-type="<?php echo htmlspecialchars($type, ENT_QUOTES); ?>"
    role="option"
    aria-selected="<?php echo $active_class ? 'true' : 'false'; ?>"
    onclick="TeamChat.openConversation(<?php echo $conv_id; ?>)">

    <?php /* Avatar */ ?>
    <div class="tc-conv-item__avatar">
        <img src="<?php echo $avatar_url; ?>"
             alt="<?php echo htmlspecialchars($display_name, ENT_QUOTES); ?>"
             class="tc-avatar"
             onerror="this.src='data:image/svg+xml;base64,<?php echo base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" rx="20" fill="#6c757d"/><text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" font-family="sans-serif" font-size="15" fill="#fff">?</text></svg>'); ?>'">

        <?php /* Online dot — only for direct conversations */ ?>
        <?php if ($type === 'direct' && !empty($conv['peer']['is_online'])): ?>
        <span class="tc-avatar__status tc-avatar__status--online"
              title="Online"></span>
        <?php endif; ?>
    </div>

    <?php /* Body */ ?>
    <div class="tc-conv-item__body">
        <div class="tc-conv-item__row">
            <span class="tc-conv-item__name">
                <?php if ($type === 'channel'): ?>
                <i class="ti ti-hash tc-conv-item__type-icon"></i>
                <?php elseif ($type === 'group'): ?>
                <i class="ti ti-users tc-conv-item__type-icon"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($display_name, ENT_QUOTES); ?>
            </span>
            <span class="tc-conv-item__time"><?php echo $time_label; ?></span>
        </div>
        <div class="tc-conv-item__row">
            <span class="tc-conv-item__preview <?php echo $unread > 0 ? 'tc-conv-item__preview--unread' : ''; ?>">
                <?php if ($is_muted): ?>
                <i class="ti ti-bell-off tc-muted-icon" title="Muted"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($preview ?: 'No messages yet', ENT_QUOTES); ?>
            </span>
            <?php if ($unread > 0 && !$is_muted): ?>
            <span class="tc-badge" title="<?php echo $unread; ?> unread">
                <?php echo $unread > 99 ? '99+' : $unread; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

</li>