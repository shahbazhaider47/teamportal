<?php defined('BASEPATH') or exit('No direct script access allowed');
$mine = !empty($msg['is_mine']);
$type = $msg['type'] ?? 'text';
?>
<div class="tc-bubble <?php echo $mine ? 'mine' : ''; ?> <?php echo $type === 'system' ? 'system' : ''; ?>" data-message-id="<?php echo (int)$msg['id']; ?>">
    <?php if (!$mine && $type !== 'system'): ?><img class="tc-avatar tc-avatar--sm" src="<?php echo html_escape($msg['sender_avatar_url'] ?? team_chat_initials_avatar($msg['sender_name'] ?? '?')); ?>" alt=""><?php endif; ?>
    <div class="tc-bubble__body">
        <?php if ($type !== 'system'): ?><div class="tc-bubble__head"><?php echo html_escape($msg['sender_name'] ?? 'User'); ?> · <?php echo team_chat_message_time($msg['created_at'] ?? ''); ?></div><?php endif; ?>
        <div class="tc-bubble__card"><?php echo !empty($msg['is_deleted']) ? 'Message deleted' : team_chat_parse_body($msg['body'] ?? ''); ?>
            <?php if (!empty($msg['attachments'])): ?><div class="tc-attachments"><?php foreach ($msg['attachments'] as $att): ?><div class="tc-file" data-attachment-id="<?php echo (int)$att['id']; ?>" data-file-url="<?php echo html_escape($att['file_url']); ?>" data-mime-type="<?php echo html_escape($att['mime_type']); ?>" data-orig-name="<?php echo html_escape($att['original_name']); ?>"><?php if (team_chat_is_image_mime($att['mime_type'])): ?><img src="<?php echo html_escape($att['file_url']); ?>" alt=""><?php else: ?><i class="<?php echo team_chat_file_icon_class($att['mime_type']); ?>"></i><a href="<?php echo html_escape($att['file_url']); ?>" download><?php echo html_escape($att['original_name']); ?></a><?php endif; ?></div><?php endforeach; ?></div><?php endif; ?>
        </div>
        <?php if (!empty($msg['reactions'])): ?><div class="tc-reactions"><?php foreach ($msg['reactions'] as $reaction): ?><button class="tc-reaction <?php echo !empty($reaction['reacted_by_me']) ? 'mine' : ''; ?>" data-react="<?php echo (int)$msg['id']; ?>" data-emoji="<?php echo html_escape($reaction['emoji']); ?>"><?php echo html_escape($reaction['emoji']); ?> <?php echo (int)$reaction['count']; ?></button><?php endforeach; ?></div><?php endif; ?>
        <?php if ($type !== 'system'): ?><div class="tc-bubble__actions"><button class="tc-action" data-thread="<?php echo (int)$msg['id']; ?>">Reply</button><button class="tc-action" data-react="<?php echo (int)$msg['id']; ?>" data-emoji="👍">👍</button><?php if ($mine): ?><button class="tc-action" data-delete="<?php echo (int)$msg['id']; ?>">Delete</button><?php endif; ?></div><?php endif; ?>
    </div>
</div>
