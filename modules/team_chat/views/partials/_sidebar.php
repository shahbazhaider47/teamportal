<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * _sidebar.php
 * Standalone sidebar partial — used when re-rendering the sidebar
 * independently (e.g. after a conversation is created via AJAX).
 * The main sidebar HTML lives in views/index.php.
 * This partial mirrors it for standalone partial reloads.
 *
 * Variables: $conversations (array), $user_id (int), $active_conversation_id (int|null)
 */
$active_id = (int)($active_conversation_id ?? 0);
$directs   = array_filter($conversations ?? [], fn($c) => $c['type'] === 'direct');
$groups    = array_filter($conversations ?? [], fn($c) => $c['type'] === 'group');
$channels  = array_filter($conversations ?? [], fn($c) => $c['type'] === 'channel');
?>

<?php /* ── Header ─────────────────────────────────────────── */ ?>
<div class="tc-sidebar__header">
    <span class="tc-sidebar__title">
        <i class="ti ti-message-circle"></i>
        Team Chat
    </span>
    <div class="tc-sidebar__header-actions">
        <?php if (team_chat_can('create_channel')): ?>
        <button class="tc-icon-btn" title="New Channel"
                data-bs-toggle="modal" data-bs-target="#tcNewChannelModal">
            <i class="ti ti-hash"></i>
        </button>
        <?php endif; ?>
        <button class="tc-icon-btn" title="New Group"
                data-bs-toggle="modal" data-bs-target="#tcNewGroupModal">
            <i class="ti ti-users"></i>
        </button>
        <button class="tc-icon-btn" title="New Direct Message"
                data-bs-toggle="modal" data-bs-target="#tcNewDmModal">
            <i class="ti ti-edit"></i>
        </button>
    </div>
</div>

<?php /* ── Search ─────────────────────────────────────────── */ ?>
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

<?php /* ── Conversation Lists ──────────────────────────────── */ ?>
<div class="tc-sidebar__body" id="tcSidebarBody">

    <?php /* Direct Messages */ ?>
    <div class="tc-sidebar__section" id="tcSectionDMs">
        <div class="tc-sidebar__section-header">
            <span>Direct Messages</span>
            <button class="tc-icon-btn tc-sidebar__section-btn"
                    data-bs-toggle="modal" data-bs-target="#tcNewDmModal"
                    title="New DM">
                <i class="ti ti-plus"></i>
            </button>
        </div>
        <ul class="tc-conv-list" id="tcDmList" role="listbox">
            <?php foreach ($directs as $conv):
                $active_class = ($active_id && (int)$conv['id'] === $active_id) ? 'is-active' : '';
                $CI =& get_instance();
            ?>
            <?php $CI->load->view('team_chat/partials/_conversation_item', [
                'conv'         => $conv,
                'active_class' => $active_class,
                'current_user' => $user_id,
            ]); ?>
            <?php endforeach; ?>
            <?php if (empty($directs)): ?>
            <li class="tc-conv-list__empty">No direct messages yet</li>
            <?php endif; ?>
        </ul>
    </div>

    <?php /* Groups */ ?>
    <div class="tc-sidebar__section" id="tcSectionGroups">
        <div class="tc-sidebar__section-header">
            <span>Groups</span>
            <button class="tc-icon-btn tc-sidebar__section-btn"
                    data-bs-toggle="modal" data-bs-target="#tcNewGroupModal"
                    title="New Group">
                <i class="ti ti-plus"></i>
            </button>
        </div>
        <ul class="tc-conv-list" id="tcGroupList" role="listbox">
            <?php foreach ($groups as $conv):
                $active_class = ($active_id && (int)$conv['id'] === $active_id) ? 'is-active' : '';
            ?>
            <?php $CI->load->view('team_chat/partials/_conversation_item', [
                'conv'         => $conv,
                'active_class' => $active_class,
                'current_user' => $user_id,
            ]); ?>
            <?php endforeach; ?>
            <?php if (empty($groups)): ?>
            <li class="tc-conv-list__empty">No groups yet</li>
            <?php endif; ?>
        </ul>
    </div>

    <?php /* Channels */ ?>
    <div class="tc-sidebar__section" id="tcSectionChannels">
        <div class="tc-sidebar__section-header">
            <span>Channels</span>
            <?php if (team_chat_can('create_channel')): ?>
            <button class="tc-icon-btn tc-sidebar__section-btn"
                    data-bs-toggle="modal" data-bs-target="#tcNewChannelModal"
                    title="New Channel">
                <i class="ti ti-plus"></i>
            </button>
            <?php endif; ?>
        </div>
        <ul class="tc-conv-list" id="tcChannelList" role="listbox">
            <?php foreach ($channels as $conv):
                $active_class = ($active_id && (int)$conv['id'] === $active_id) ? 'is-active' : '';
            ?>
            <?php $CI->load->view('team_chat/partials/_conversation_item', [
                'conv'         => $conv,
                'active_class' => $active_class,
                'current_user' => $user_id,
            ]); ?>
            <?php endforeach; ?>
            <?php if (empty($channels)): ?>
            <li class="tc-conv-list__empty">No channels yet</li>
            <?php endif; ?>
        </ul>
    </div>

</div>
<?php /* end .tc-sidebar__body */ ?>