<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<aside class="tc-sidebar" id="tcSidebar">
    <div class="tc-sidebar__top">
        <div class="tc-sidebar__title">Team Chat</div>
        <button class="tc-icon-btn" data-bs-toggle="modal" data-bs-target="#tcNewDmModal" title="New direct"><i class="ti ti-edit"></i></button>
        <button class="tc-icon-btn" data-bs-toggle="modal" data-bs-target="#tcNewGroupModal" title="New group"><i class="ti ti-users"></i></button>
        <?php if (team_chat_can('create_channel')): ?><button class="tc-icon-btn" data-bs-toggle="modal" data-bs-target="#tcNewChannelModal" title="New channel"><i class="ti ti-hash"></i></button><?php endif; ?>
    </div>
    <div class="tc-sidebar__search"><input class="tc-input" id="tcSidebarSearch" placeholder="Search conversations" autocomplete="off"></div>
    <?php foreach ([['Direct Messages',$directs],['Groups',$groups],['Channels',$channels]] as $section): ?>
    <section class="tc-section">
        <div class="tc-section__head"><?php echo html_escape($section[0]); ?></div>
        <ul class="tc-conv-list">
            <?php if (empty($section[1])): ?><li class="tc-conv-empty">No conversations</li><?php endif; ?>
            <?php foreach ($section[1] as $conv): $active_class = ((int)$conv['id'] === (int)$active_id) ? 'is-active' : ''; $CI =& get_instance(); $CI->load->view('team_chat/partials/_conversation_item', compact('conv','active_class','user_id')); endforeach; ?>
        </ul>
    </section>
    <?php endforeach; ?>
</aside>
