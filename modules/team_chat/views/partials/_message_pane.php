<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * _message_pane.php
 * Variables: $conversation, $messages, $members, $pinned_messages, $user_id
 */
$conv_id       = (int)$conversation['id'];
$conv_type     = $conversation['type'];
$display_name  = team_chat_conversation_display_name($conversation, $user_id);
$avatar_url    = team_chat_conversation_avatar($conversation, defined('TEAM_CHAT_MODULE_URL') ? TEAM_CHAT_MODULE_URL : '');
$is_read_only  = (bool)($conversation['is_read_only']  ?? false);
$is_archived   = (bool)($conversation['is_archived']   ?? false);
$member_count  = count($members);
$user_role     = $conversation['role'] ?? 'member';
$can_manage    = in_array($user_role, ['owner', 'admin']) || team_chat_can('manage_channel');

// Group messages by date for dividers
$grouped = [];
foreach ($messages as $msg) {
    $date_key = date('Y-m-d', strtotime($msg['created_at']));
    $grouped[$date_key][] = $msg;
}
?>

<?php /* ── Pane Header ────────────────────────────────────── */ ?>
<div class="tc-pane__header" id="tcPaneHeader">

    <?php /* Mobile: back to sidebar */ ?>
    <button class="tc-icon-btn tc-pane__back d-lg-none" id="tcBackToSidebar" title="Back">
        <i class="ti ti-chevron-left"></i>
    </button>

    <?php /* Avatar */ ?>
    <div class="tc-pane__header-avatar">
        <img src="<?php echo $avatar_url; ?>"
             alt="<?php echo htmlspecialchars($display_name, ENT_QUOTES); ?>"
             class="tc-avatar">
        <?php if ($conv_type === 'direct' && !empty($conversation['peer']['is_online'])): ?>
        <span class="tc-avatar__status tc-avatar__status--online"></span>
        <?php endif; ?>
    </div>

    <?php /* Title & meta */ ?>
    <div class="tc-pane__header-info">
        <div class="tc-pane__header-name">
            <?php if ($conv_type === 'channel'): ?>
            <i class="ti ti-hash"></i>
            <?php endif; ?>
            <span id="tcPaneName"><?php echo htmlspecialchars($display_name, ENT_QUOTES); ?></span>
        </div>
        <div class="tc-pane__header-meta" id="tcPaneMeta">
            <?php if ($conv_type === 'direct'): ?>
                <?php
                $peer   = $conversation['peer'] ?? [];
                $status = team_chat_online_status($peer['is_online'] ?? false, $peer['last_seen_at'] ?? null);
                ?>
                <span class="tc-status-dot <?php echo $status['class']; ?>"></span>
                <span class="tc-status-label"><?php echo $status['label']; ?></span>
            <?php else: ?>
                <span><?php echo $member_count; ?> members</span>
            <?php endif; ?>

            <?php if ($is_archived): ?>
            <span class="badge bg-secondary ms-1">Archived</span>
            <?php endif; ?>
            <?php if ($is_read_only): ?>
            <span class="badge bg-warning ms-1">Read-only</span>
            <?php endif; ?>
        </div>
    </div>

    <?php /* Header action buttons */ ?>
    <div class="tc-pane__header-actions">

        <?php /* Pinned messages toggle */ ?>
        <?php if (!empty($pinned_messages)): ?>
        <button class="tc-icon-btn tc-pane__header-btn"
                id="tcTogglePins"
                title="Pinned messages (<?php echo count($pinned_messages); ?>)">
            <i class="ti ti-pin"></i>
            <span class="tc-icon-btn__badge"><?php echo count($pinned_messages); ?></span>
        </button>
        <?php endif; ?>

        <?php /* Search messages */ ?>
        <button class="tc-icon-btn tc-pane__header-btn"
                id="tcSearchMessages"
                title="Search in conversation">
            <i class="ti ti-search"></i>
        </button>

        <?php /* Members — only for group/channel */ ?>
        <?php if ($conv_type !== 'direct'): ?>
        <button class="tc-icon-btn tc-pane__header-btn"
                data-bs-toggle="modal"
                data-bs-target="#tcMembersModal"
                title="Members">
            <i class="ti ti-users"></i>
        </button>
        <?php endif; ?>

        <?php /* Mute toggle */ ?>
        <button class="tc-icon-btn tc-pane__header-btn"
                id="tcMuteBtn"
                data-conv-id="<?php echo $conv_id; ?>"
                data-muted="<?php echo ($conversation['is_muted'] ?? 0) ? '1' : '0'; ?>"
                title="<?php echo ($conversation['is_muted'] ?? 0) ? 'Unmute' : 'Mute'; ?>">
            <i class="ti <?php echo ($conversation['is_muted'] ?? 0) ? 'ti-bell-off' : 'ti-bell'; ?>"></i>
        </button>

        <?php /* More options (admin/owner) */ ?>
        <?php if ($can_manage && !$is_archived): ?>
        <div class="dropdown">
            <button class="tc-icon-btn tc-pane__header-btn"
                    data-bs-toggle="dropdown"
                    title="More options">
                <i class="ti ti-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if ($conv_type !== 'direct'): ?>
                <li>
                    <a class="dropdown-item" href="#" id="tcRenameConv">
                        <i class="ti ti-pencil me-2"></i> Rename
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item" href="#"
                       data-bs-toggle="modal" data-bs-target="#tcMembersModal">
                        <i class="ti ti-users me-2"></i> Manage Members
                    </a>
                </li>
                <?php if ($conv_type === 'channel' || $conv_type === 'group'): ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-warning" href="#" id="tcArchiveConv"
                       data-conv-id="<?php echo $conv_id; ?>">
                        <i class="ti ti-archive me-2"></i> Archive
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

    </div>

</div>
<?php /* end .tc-pane__header */ ?>

<?php /* ── Pinned Messages Bar (collapsible) ──────────────── */ ?>
<?php if (!empty($pinned_messages)): ?>
<div class="tc-pins-bar d-none" id="tcPinsBar">
    <div class="tc-pins-bar__header">
        <i class="ti ti-pin"></i>
        <strong><?php echo count($pinned_messages); ?> pinned</strong>
        <button class="tc-icon-btn ms-auto" id="tcClosePinsBar">
            <i class="ti ti-x"></i>
        </button>
    </div>
    <div class="tc-pins-bar__list">
        <?php foreach ($pinned_messages as $pin): ?>
        <div class="tc-pin-item"
             onclick="TeamChat.scrollToMessage(<?php echo (int)$pin['id']; ?>)">
            <span class="tc-pin-item__sender">
                <?php echo htmlspecialchars($pin['sender_name'] ?? '', ENT_QUOTES); ?>
            </span>
            <span class="tc-pin-item__preview">
                <?php echo htmlspecialchars(team_chat_message_preview($pin['body'] ?? '', 60), ENT_QUOTES); ?>
            </span>
            <?php if ($can_manage): ?>
            <button class="tc-icon-btn tc-pin-item__unpin"
                    onclick="event.stopPropagation(); TeamChat.unpinMessage(<?php echo (int)$pin['id']; ?>, <?php echo $conv_id; ?>)"
                    title="Unpin">
                <i class="ti ti-pinned-off"></i>
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php /* ── Message Search Bar (hidden by default) ─────────── */ ?>
<div class="tc-search-bar d-none" id="tcMessageSearchBar">
    <div class="tc-search-wrap">
        <i class="ti ti-search tc-search-icon"></i>
        <input type="text"
               id="tcMessageSearchInput"
               class="tc-search-input"
               placeholder="Search messages…"
               autocomplete="off">
    </div>
    <button class="tc-icon-btn" id="tcCloseMessageSearch" title="Close search">
        <i class="ti ti-x"></i>
    </button>
</div>

<?php /* ── Message Search Results Overlay ───────────────────*/ ?>
<div class="tc-search-results d-none" id="tcMessageSearchResults">
    <div class="tc-search-results__list" id="tcMessageSearchList"></div>
</div>

<?php /* ── Message List ────────────────────────────────────── */ ?>
<div class="tc-pane__messages" id="tcMessageList"
     data-conv-id="<?php echo $conv_id; ?>"
     data-conv-type="<?php echo htmlspecialchars($conv_type, ENT_QUOTES); ?>">

    <?php /* Load more trigger */ ?>
    <div class="tc-load-more" id="tcLoadMore" data-conv-id="<?php echo $conv_id; ?>">
        <button class="btn btn-sm btn-outline-secondary" id="tcLoadMoreBtn">
            <i class="ti ti-arrow-up me-1"></i> Load earlier messages
        </button>
    </div>

    <?php if (empty($messages)): ?>
    <div class="tc-no-messages" id="tcNoMessages">
        <i class="ti ti-message-off"></i>
        <p>No messages yet. Say hello!</p>
    </div>
    <?php else: ?>

        <?php
        $prev_date = null;
        foreach ($grouped as $date_key => $day_messages):
            $divider_label = team_chat_date_divider($date_key . ' 00:00:00');
        ?>

        <?php /* Date divider */ ?>
        <div class="tc-date-divider" data-date="<?php echo $date_key; ?>">
            <span><?php echo $divider_label; ?></span>
        </div>

        <?php foreach ($day_messages as $msg): ?>
        <?php $CI =& get_instance();
        $CI->load->view('team_chat/partials/_message_bubble', [
            'msg'     => $msg,
            'user_id' => $user_id,
        ]); ?>
        <?php endforeach; ?>

        <?php endforeach; ?>

    <?php endif; ?>

</div>
<?php /* end .tc-pane__messages */ ?>

<?php /* ── Typing Indicator ─────────────────────────────────── */ ?>
<div class="tc-typing-indicator d-none" id="tcTypingIndicator">
    <span class="tc-typing-dots">
        <span></span><span></span><span></span>
    </span>
    <span class="tc-typing-text" id="tcTypingText"></span>
</div>

<?php /* ── Composer ────────────────────────────────────────── */ ?>
<?php if ($is_archived): ?>
<div class="tc-composer tc-composer--disabled">
    <span class="tc-composer__archived-notice">
        <i class="ti ti-archive me-1"></i>
        This conversation is archived and cannot receive new messages.
    </span>
</div>

<?php elseif ($is_read_only && !$can_manage): ?>
<div class="tc-composer tc-composer--disabled">
    <span class="tc-composer__archived-notice">
        <i class="ti ti-lock me-1"></i>
        This channel is read-only.
    </span>
</div>

<?php else: ?>
<div class="tc-composer" id="tcComposer" data-conv-id="<?php echo $conv_id; ?>">

    <?php /* Edit mode banner — shown when editing a message */ ?>
    <div class="tc-composer__edit-banner d-none" id="tcEditBanner">
        <i class="ti ti-pencil"></i>
        <span>Editing message</span>
        <button class="tc-icon-btn ms-auto" id="tcCancelEdit" title="Cancel edit">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <?php /* File/image drop zone visual */ ?>
    <div class="tc-drop-zone d-none" id="tcDropZone">
        <i class="ti ti-upload"></i>
        <span>Drop files to upload</span>
    </div>

    <?php /* Upload preview chips */ ?>
    <div class="tc-upload-preview d-none" id="tcUploadPreview">
        <div class="tc-upload-chips" id="tcUploadChips"></div>
    </div>

    <?php /* Toolbar */ ?>
    <div class="tc-composer__toolbar">
        <label class="tc-icon-btn tc-composer__tool" title="Attach file" for="tcFileInput">
            <i class="ti ti-paperclip"></i>
            <input type="file"
                   id="tcFileInput"
                   multiple
                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z,.mp4,.webm,.mp3,.wav"
                   class="d-none">
        </label>
        <button class="tc-icon-btn tc-composer__tool" id="tcEmojiPickerBtn" title="Emoji">
            <i class="ti ti-mood-smile"></i>
        </button>
    </div>

    <?php /* Textarea */ ?>
    <div class="tc-composer__input-wrap">
        <div class="tc-composer__input"
             id="tcComposerInput"
             contenteditable="true"
             role="textbox"
             aria-multiline="true"
             aria-label="Message input"
             data-placeholder="Message <?php echo $conv_type === 'channel' ? '#' . htmlspecialchars(strtolower($conversation['name'] ?? ''), ENT_QUOTES) : htmlspecialchars($display_name, ENT_QUOTES); ?>…">
        </div>

        <?php /* @mention autocomplete dropdown */ ?>
        <div class="tc-mention-dropdown d-none" id="tcMentionDropdown" role="listbox">
            <div class="tc-mention-list" id="tcMentionList"></div>
        </div>
    </div>

    <?php /* Send button */ ?>
    <button class="tc-composer__send" id="tcSendBtn"
            data-conv-id="<?php echo $conv_id; ?>"
            title="Send (Enter)">
        <i class="ti ti-send"></i>
    </button>

</div>
<?php /* end .tc-composer */ ?>
<?php endif; ?>

<?php /* ── Emoji Picker (floating, managed by JS) ─────────── */ ?>
<div class="tc-emoji-picker d-none" id="tcEmojiPicker" role="dialog" aria-label="Emoji picker">
    <div class="tc-emoji-picker__search">
        <input type="text" id="tcEmojiSearch" placeholder="Search emoji…" autocomplete="off">
    </div>
    <div class="tc-emoji-picker__categories" id="tcEmojiCategories"></div>
    <div class="tc-emoji-picker__grid" id="tcEmojiGrid"></div>
</div>