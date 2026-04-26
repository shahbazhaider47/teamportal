<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
if (!isset($user_id) && isset($view_data) && is_array($view_data)) {
    extract($view_data);
}
$cfg      = $js_config ?? [];
$user_id  = $user_id   ?? 0;
$directs  = array_filter($conversations ?? [], fn($c) => $c['type'] === 'direct');
$groups   = array_filter($conversations ?? [], fn($c) => $c['type'] === 'group');
$channels = array_filter($conversations ?? [], fn($c) => $c['type'] === 'channel');
$active_id = isset($active_conversation['id']) ? (int)$active_conversation['id'] : 0;
?>
<script>
window.TeamChatConfig = {
    userId:               <?php echo (int)($cfg['userId']               ?? 0); ?>,
    userFullname:         <?php echo json_encode($cfg['userFullname']   ?? ''); ?>,
    userAvatar:           <?php echo json_encode($cfg['userAvatar']     ?? ''); ?>,
    activeConversationId: <?php echo (int)($cfg['activeConversationId'] ?? 0); ?>,
    baseUrl:              <?php echo json_encode($cfg['baseUrl']         ?? ''); ?>,
    uploadUrl:            <?php echo json_encode($cfg['uploadUrl']       ?? ''); ?>,
    socketUrl:            <?php echo json_encode($cfg['socketUrl']       ?? ''); ?>,
    moduleUrl:            <?php echo json_encode($cfg['moduleUrl']       ?? ''); ?>,
    csrfTokenName:        <?php echo json_encode($cfg['csrfTokenName']   ?? ''); ?>,
    csrfHash:             <?php echo json_encode($cfg['csrfHash']        ?? ''); ?>,
    wsToken:              <?php echo json_encode(team_chat_ws_token((int)($cfg['userId'] ?? 0))); ?>,
    maxFileSizeMb:        <?php echo (int)($cfg['maxFileSizeMb']         ?? 10); ?>,
    canCreateChannel:     <?php echo !empty($cfg['canCreateChannel']) ? 'true' : 'false'; ?>,
    canManageChannel:     <?php echo !empty($cfg['canManageChannel']) ? 'true' : 'false'; ?>,
    canDeleteAny:         <?php echo !empty($cfg['canDeleteAny'])     ? 'true' : 'false'; ?>,
    canViewAll:           <?php echo !empty($cfg['canViewAll'])       ? 'true' : 'false'; ?>,
};
</script>

<div class="tc-shell main-top" id="teamChatShell">

    <aside class="tc-sidebar" id="tcSidebar">

        <div class="tc-sidebar__header">
            <span class="tc-sidebar__title">Conversations</span>
            <div class="tc-sidebar__header-actions">
                <button class="tc-icon-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#tcNewDmModal"
                        title="New Direct Message">
                    <i class="ti ti-edit"></i>
                </button>
                <button class="tc-icon-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#tcNewGroupModal"
                        title="New Group">
                    <i class="ti ti-users"></i>
                </button>
            </div>
        </div>

        <div class="tc-sidebar__search">
            <div class="tc-search-wrap">
                <i class="ti ti-search tc-search-icon"></i>
                <input type="text"
                       id="tcSidebarSearch"
                       class="tc-search-input"
                       placeholder="Search conversations…"
                       autocomplete="off">
                <button class="tc-search-clear d-none" id="tcSidebarSearchClear">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        </div>

        <div class="tc-sidebar__body" id="tcSidebarBody">

            <div class="tc-sidebar__section" id="tcSectionDMs">
                <div class="tc-sidebar__section-header">
                    <span>Direct Messages</span>
                    <button class="tc-icon-btn tc-sidebar__section-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#tcNewDmModal"
                            title="New DM">
                        <i class="ti ti-plus"></i>
                    </button>
                </div>
                <ul class="tc-conv-list" id="tcDmList" role="listbox">
                    <?php foreach ($directs as $conv): ?>
                    <?php $CI->load->view('team_chat/partials/_conversation_item', [
                        'conv'         => $conv,
                        'active_class' => ($active_id && (int)$conv['id'] === $active_id) ? 'is-active' : '',
                        'current_user' => $user_id,
                    ]); ?>
                    <?php endforeach; ?>
                    <?php if (empty($directs)): ?>
                    <li class="tc-conv-list__empty">No direct messages yet</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="tc-sidebar__section" id="tcSectionGroups">
                <div class="tc-sidebar__section-header">
                    <span>Groups</span>
                    <button class="tc-icon-btn tc-sidebar__section-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#tcNewGroupModal"
                            title="New Group">
                        <i class="ti ti-plus"></i>
                    </button>
                </div>
                <ul class="tc-conv-list" id="tcGroupList" role="listbox">
                    <?php foreach ($groups as $conv): ?>
                    <?php $CI->load->view('team_chat/partials/_conversation_item', [
                        'conv'         => $conv,
                        'active_class' => ($active_id && (int)$conv['id'] === $active_id) ? 'is-active' : '',
                        'current_user' => $user_id,
                    ]); ?>
                    <?php endforeach; ?>
                    <?php if (empty($groups)): ?>
                    <li class="tc-conv-list__empty">No groups yet</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="tc-sidebar__section" id="tcSectionChannels">
                <div class="tc-sidebar__section-header">
                    <span>Channels</span>
                    <?php if (team_chat_can('create_channel')): ?>
                    <button class="tc-icon-btn tc-sidebar__section-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#tcNewChannelModal"
                            title="New Channel">
                        <i class="ti ti-plus"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <ul class="tc-conv-list" id="tcChannelList" role="listbox">
                    <?php foreach ($channels as $conv): ?>
                    <?php $CI->load->view('team_chat/partials/_conversation_item', [
                        'conv'         => $conv,
                        'active_class' => ($active_id && (int)$conv['id'] === $active_id) ? 'is-active' : '',
                        'current_user' => $user_id,
                    ]); ?>
                    <?php endforeach; ?>
                    <?php if (empty($channels)): ?>
                    <li class="tc-conv-list__empty">No channels yet</li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

    </aside>

    <main class="tc-pane" id="tcPane">
        <?php if (!empty($active_conversation)): ?>
            <?php $CI->load->view('team_chat/partials/_message_pane', [
                'conversation'    => $active_conversation,
                'messages'        => $messages        ?? [],
                'members'         => $members         ?? [],
                'pinned_messages' => $pinned_messages ?? [],
                'user_id'         => $user_id,
            ]); ?>
        <?php else: ?>
            <?php $CI->load->view('team_chat/partials/_empty_state'); ?>
        <?php endif; ?>
    </main>

    <aside class="tc-thread-panel d-none" id="tcThreadPanel">
        <?php $CI->load->view('team_chat/partials/_thread_panel', [
            'user_id' => $user_id,
        ]); ?>
    </aside>

</div>

<?php $CI->load->view('team_chat/partials/_new_dm_modal', ['user_id' => $user_id,]); ?>
<?php $CI->load->view('team_chat/partials/_new_group_modal', ['user_id' => $user_id,]); ?>
<?php if (team_chat_can('create_channel')): ?>
<?php $CI->load->view('team_chat/partials/_new_channel_modal', ['teams' => $teams ?? [],'departments' => $departments ?? [],]); ?>
<?php endif; ?>
<?php $CI->load->view('team_chat/partials/_members_modal', ['user_id' => $user_id,'members' => $members ?? [],]); ?>
<?php $CI->load->view('team_chat/partials/_file_preview_modal'); ?>