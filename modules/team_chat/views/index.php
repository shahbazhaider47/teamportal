<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI =& get_instance();
if (isset($view_data) && is_array($view_data)) extract($view_data);
$cfg = $js_config ?? [];
$user_id = (int)($user_id ?? 0);
$conversations = $conversations ?? [];
$directs = array_values(array_filter($conversations, fn($c) => ($c['type'] ?? '') === 'direct'));
$groups = array_values(array_filter($conversations, fn($c) => ($c['type'] ?? '') === 'group'));
$channels = array_values(array_filter($conversations, fn($c) => ($c['type'] ?? '') === 'channel'));
$active_id = (int)($active_conversation['id'] ?? 0);
?>
<script>
window.TeamChatConfig = <?php echo json_encode([
    'userId' => (int)($cfg['userId'] ?? $user_id),
    'userFullname' => $cfg['userFullname'] ?? '',
    'userAvatar' => $cfg['userAvatar'] ?? '',
    'activeConversationId' => (int)($cfg['activeConversationId'] ?? $active_id),
    'baseUrl' => $cfg['baseUrl'] ?? site_url('team_chat'),
    'uploadUrl' => $cfg['uploadUrl'] ?? site_url('team_chat/upload'),
    'csrfTokenName' => $cfg['csrfTokenName'] ?? $CI->security->get_csrf_token_name(),
    'csrfHash' => $cfg['csrfHash'] ?? $CI->security->get_csrf_hash(),
    'wsToken' => $cfg['wsToken'] ?? team_chat_ws_token($user_id),
    'maxFileSizeMb' => (int)($cfg['maxFileSizeMb'] ?? 10),
    'canCreateChannel' => !empty($cfg['canCreateChannel']),
    'canManageChannel' => !empty($cfg['canManageChannel']),
    'canDeleteAny' => !empty($cfg['canDeleteAny']),
], JSON_UNESCAPED_SLASHES); ?>;
</script>
<div class="tc-shell" id="teamChatShell">
    <?php $CI->load->view('team_chat/partials/_sidebar', compact('directs','groups','channels','active_id','user_id')); ?>
    <main class="tc-pane" id="tcPane">
        <?php if (!empty($active_conversation)): ?>
            <?php $CI->load->view('team_chat/partials/_message_pane', ['conversation'=>$active_conversation,'messages'=>$messages ?? [],'members'=>$members ?? [],'pinned_messages'=>$pinned_messages ?? [],'user_id'=>$user_id]); ?>
        <?php else: ?>
            <?php $CI->load->view('team_chat/partials/_empty_state'); ?>
        <?php endif; ?>
    </main>
    <?php $CI->load->view('team_chat/partials/_thread_panel', ['user_id'=>$user_id]); ?>
</div>
<?php $CI->load->view('team_chat/partials/_new_dm_modal'); ?>
<?php $CI->load->view('team_chat/partials/_new_group_modal'); ?>
<?php if (team_chat_can('create_channel')) $CI->load->view('team_chat/partials/_new_channel_modal', ['teams'=>$teams ?? [], 'departments'=>$departments ?? []]); ?>
<?php $CI->load->view('team_chat/partials/_members_modal', ['members'=>$members ?? [], 'user_id'=>$user_id]); ?>
<?php $CI->load->view('team_chat/partials/_file_preview_modal'); ?>
