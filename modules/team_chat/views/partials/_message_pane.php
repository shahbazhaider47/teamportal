<?php defined('BASEPATH') or exit('No direct script access allowed');
$display = team_chat_conversation_display_name($conversation, $user_id);
$avatar = team_chat_conversation_avatar($conversation);
?>
<header class="tc-pane__head">
    <img class="tc-avatar" src="<?php echo html_escape($avatar); ?>" alt="">
    <div><div class="tc-pane__title"><?php echo html_escape($display); ?></div><div class="tc-pane__meta"><?php echo count($members ?? []); ?> members</div></div>
    <div class="tc-pane__actions"><button class="tc-icon-btn" id="tcSearchMessages"><i class="ti ti-search"></i></button><?php if (($conversation['type'] ?? '') !== 'direct'): ?><button class="tc-icon-btn" data-bs-toggle="modal" data-bs-target="#tcMembersModal"><i class="ti ti-users"></i></button><?php endif; ?></div>
</header>
<div class="tc-messages" id="tcMessageList" data-conv-id="<?php echo (int)$conversation['id']; ?>">
<?php $last_date = null; foreach ($messages as $msg): $date = date('Y-m-d', strtotime($msg['created_at'])); if ($date !== $last_date): $last_date = $date; ?><div class="tc-date"><?php echo team_chat_date_divider($date); ?></div><?php endif; ?><?php $CI =& get_instance(); $CI->load->view('team_chat/partials/_message_bubble', ['msg'=>$msg,'user_id'=>$user_id]); ?><?php endforeach; ?>
<?php if (empty($messages)): ?><div class="tc-empty"><p>No messages yet. Say hello!</p></div><?php endif; ?>
</div>
<div id="tcUploadChips"></div>
<footer class="tc-composer">
    <label class="tc-icon-btn"><i class="ti ti-paperclip"></i><input id="tcFileInput" type="file" class="d-none" multiple></label>
    <div class="tc-composer__input" id="tcComposerInput" contenteditable="true" data-placeholder="Message <?php echo html_escape($display); ?>"></div>
    <button class="tc-send" id="tcSendBtn"><i class="ti ti-send"></i></button>
</footer>
