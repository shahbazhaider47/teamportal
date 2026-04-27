<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * _message_bubble.php
 * Variables: $msg (hydrated message array), $user_id (current user ID)
 *
 * Called both from _message_pane (initial load) and
 * injected dynamically by team_chat_messages.js via JS template.
 * Keep this partial in sync with the JS render function.
 */

$msg_id          = (int)$msg['id'];
$sender_id       = (int)$msg['sender_id'];
$is_mine         = ((int)$user_id === $sender_id);
$is_deleted      = (bool)$msg['is_deleted'];
$is_edited       = (bool)$msg['is_edited'];
$type            = $msg['type'] ?? 'text';
$body            = $msg['body'] ?? '';
$parent_id       = $msg['parent_id'] ?? null;
$reply_count     = (int)($msg['thread_reply_count'] ?? 0);
$reactions       = $msg['reactions']   ?? [];
$attachments     = $msg['attachments'] ?? [];
$sender_name     = htmlspecialchars($msg['sender_name']   ?? 'Unknown', ENT_QUOTES);
$sender_avatar   = $msg['sender_avatar'] ?? '';
$created_at      = $msg['created_at'] ?? '';
$time_display    = team_chat_message_time($created_at);
$time_full       = date('D, d M Y H:i', strtotime($created_at));
$bubble_class    = $is_mine ? 'tc-bubble--mine' : 'tc-bubble--theirs';
$system_class    = $type === 'system' ? 'tc-bubble--system' : '';

// Avatar URL
$avatar_src = team_chat_user_avatar_url($sender_avatar, $msg['sender_name'] ?? '?');
?>

<?php /* ── System message — minimal centered display ─────── */ ?>
<?php if ($type === 'system'): ?>
<div class="tc-bubble tc-bubble--system"
     data-msg-id="<?php echo $msg_id; ?>"
     data-type="system">
    <span class="tc-system-text">
        <i class="ti ti-info-circle"></i>
        <?php echo htmlspecialchars($body, ENT_QUOTES); ?>
    </span>
</div>

<?php /* ── Regular / file / image message ─────────────────── */ ?>
<?php else: ?>
<div class="tc-bubble <?php echo $bubble_class; ?>"
     data-msg-id="<?php echo $msg_id; ?>"
     data-sender-id="<?php echo $sender_id; ?>"
     data-conversation-id="<?php echo (int)($msg['conversation_id'] ?? 0); ?>"
     data-parent-id="<?php echo (int)$parent_id; ?>"
     data-type="<?php echo htmlspecialchars($type, ENT_QUOTES); ?>"
     data-created="<?php echo htmlspecialchars($created_at, ENT_QUOTES); ?>">

    <?php /* Avatar (shown only for others' messages) */ ?>
    <?php if (!$is_mine): ?>
    <div class="tc-bubble__avatar">
        <img src="<?php echo $avatar_src; ?>"
             alt="<?php echo $sender_name; ?>"
             class="tc-avatar tc-avatar--sm"
             onerror="this.src='<?php echo team_chat_initials_avatar($msg['sender_name'] ?? '?'); ?>'">
    </div>
    <?php endif; ?>

    <?php /* Message body */ ?>
    <div class="tc-bubble__wrap">

        <?php /* Sender name + timestamp header (others only) */ ?>
        <?php if (!$is_mine): ?>
        <div class="tc-bubble__header">
            <span class="tc-bubble__sender"><?php echo $sender_name; ?></span>
            <time class="tc-bubble__time" datetime="<?php echo $created_at; ?>" title="<?php echo $time_full; ?>">
                <?php echo $time_display; ?>
            </time>
        </div>
        <?php endif; ?>

        <?php /* Main content */ ?>
        <div class="tc-bubble__content <?php echo $is_deleted ? 'tc-bubble__content--deleted' : ''; ?>">

            <?php if ($is_deleted): ?>
                <span class="tc-deleted-text">
                    <i class="ti ti-ban"></i>
                    This message was deleted
                </span>

            <?php elseif ($type === 'image'): ?>
                <?php foreach ($attachments as $att):
                    if (!team_chat_is_image_mime($att['mime_type'])) continue; ?>
                <div class="tc-attachment tc-attachment--image"
                     data-attachment-id="<?php echo (int)$att['id']; ?>"
                     onclick="TeamChat.openFilePreview(<?php echo (int)$att['id']; ?>)">
                    <img src="<?php echo htmlspecialchars($att['thumbnail_url'] ?? $att['file_url'], ENT_QUOTES); ?>"
                         alt="<?php echo htmlspecialchars($att['original_name'], ENT_QUOTES); ?>"
                         class="tc-img-thumb"
                         loading="lazy">
                </div>
                <?php endforeach; ?>
                <?php if (!empty($body)): ?>
                <div class="tc-bubble__text"><?php echo team_chat_parse_body($body); ?></div>
                <?php endif; ?>

            <?php elseif ($type === 'file'): ?>
                <?php foreach ($attachments as $att): ?>
                <div class="tc-attachment tc-attachment--file"
                     data-attachment-id="<?php echo (int)$att['id']; ?>">
                    <div class="tc-file-card">
                        <span class="tc-file-card__icon">
                            <i class="<?php echo team_chat_file_icon_class($att['mime_type']); ?>"></i>
                        </span>
                        <div class="tc-file-card__info">
                            <span class="tc-file-card__name"
                                  title="<?php echo htmlspecialchars($att['original_name'], ENT_QUOTES); ?>">
                                <?php echo htmlspecialchars($att['original_name'], ENT_QUOTES); ?>
                            </span>
                            <span class="tc-file-card__size">
                                <?php echo team_chat_format_file_size($att['file_size']); ?>
                            </span>
                        </div>
                        <a href="<?php echo htmlspecialchars($att['file_url'], ENT_QUOTES); ?>"
                           class="tc-file-card__dl"
                           download="<?php echo htmlspecialchars($att['original_name'], ENT_QUOTES); ?>"
                           title="Download">
                            <i class="ti ti-download"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="tc-bubble__text"><?php echo team_chat_parse_body($body); ?></div>
            <?php endif; ?>

        </div>
        <?php /* end .tc-bubble__content */ ?>

        <?php /* Edited label + timestamp for own messages */ ?>
        <div class="tc-bubble__footer">
            <?php if ($is_edited && !$is_deleted): ?>
            <span class="tc-edited-label" title="Edited <?php echo $time_full; ?>">edited</span>
            <?php endif; ?>
            <?php if ($is_mine): ?>
            <time class="tc-bubble__time" datetime="<?php echo $created_at; ?>" title="<?php echo $time_full; ?>">
                <?php echo $time_display; ?>
            </time>
            <?php endif; ?>
        </div>

        <?php /* Reactions bar */ ?>
        <?php if (!empty($reactions) && !$is_deleted): ?>
        <div class="tc-reactions" data-msg-id="<?php echo $msg_id; ?>">
            <?php foreach ($reactions as $reaction): ?>
            <button class="tc-reaction-pill <?php echo $reaction['reacted_by_me'] ? 'tc-reaction-pill--mine' : ''; ?>"
                    data-msg-id="<?php echo $msg_id; ?>"
                    data-emoji="<?php echo htmlspecialchars($reaction['emoji'], ENT_QUOTES); ?>"
                    title="<?php echo htmlspecialchars($reaction['reactor_names'] ?? '', ENT_QUOTES); ?>">
                <span class="tc-reaction-pill__emoji"><?php echo htmlspecialchars($reaction['emoji'], ENT_QUOTES); ?></span>
                <span class="tc-reaction-pill__count"><?php echo (int)$reaction['count']; ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php /* Thread reply count */ ?>
        <?php if (!$parent_id && $reply_count > 0 && !$is_deleted): ?>
        <div class="tc-thread-link" onclick="TeamChat.openThread(<?php echo $msg_id; ?>)">
            <i class="ti ti-message-reply"></i>
            <?php echo $reply_count; ?> <?php echo $reply_count === 1 ? 'reply' : 'replies'; ?>
        </div>
        <?php endif; ?>

    </div>
    <?php /* end .tc-bubble__wrap */ ?>

    <?php /* Hover action toolbar */ ?>
    <?php if (!$is_deleted): ?>
    <div class="tc-bubble__actions" data-msg-id="<?php echo $msg_id; ?>">

        <?php /* Emoji react */ ?>
        <button class="tc-bubble__action-btn tc-emoji-trigger"
                data-msg-id="<?php echo $msg_id; ?>"
                title="React">
            <i class="ti ti-mood-smile"></i>
        </button>

        <?php /* Reply in thread */ ?>
        <?php if (!$parent_id): ?>
        <button class="tc-bubble__action-btn"
                onclick="TeamChat.openThread(<?php echo $msg_id; ?>)"
                title="Reply in thread">
            <i class="ti ti-message-reply"></i>
        </button>
        <?php endif; ?>

        <?php /* Edit — own messages only */ ?>
        <?php if ($is_mine): ?>
        <button class="tc-bubble__action-btn"
                onclick="TeamChat.startEdit(<?php echo $msg_id; ?>)"
                title="Edit">
            <i class="ti ti-pencil"></i>
        </button>
        <?php endif; ?>

        <?php /* Delete — own or permission */ ?>
        <?php if ($is_mine || team_chat_can('delete_message')): ?>
        <button class="tc-bubble__action-btn tc-bubble__action-btn--danger"
                onclick="TeamChat.deleteMessage(<?php echo $msg_id; ?>)"
                title="Delete">
            <i class="ti ti-trash"></i>
        </button>
        <?php endif; ?>

        <?php /* Pin — admins/owners only (JS checks role) */ ?>
        <button class="tc-bubble__action-btn tc-pin-btn"
                data-msg-id="<?php echo $msg_id; ?>"
                title="Pin message"
                style="display:none">
            <i class="ti ti-pin"></i>
        </button>

    </div>
    <?php endif; ?>

</div>
<?php /* end .tc-bubble */ ?>
<?php endif; ?>