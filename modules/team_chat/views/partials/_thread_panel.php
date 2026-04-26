<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * _thread_panel.php
 * Variables: $user_id
 * Thread content is fully loaded and rendered by JS (team_chat_threads.js).
 * This file provides the static shell that JS populates.
 */
?>

<div class="tc-thread-panel__header" id="tcThreadHeader">
    <div class="tc-thread-panel__title">
        <i class="ti ti-message-reply"></i>
        <span>Thread</span>
    </div>
    <button class="tc-icon-btn" id="tcCloseThread" title="Close thread">
        <i class="ti ti-x"></i>
    </button>
</div>

<?php /* ── Parent message (top of thread) ──────────────────── */ ?>
<div class="tc-thread-panel__parent" id="tcThreadParent">
    <?php /* Populated by JS: renders the parent message bubble */ ?>
    <div class="tc-thread-loading">
        <div class="tc-spinner"></div>
    </div>
</div>

<div class="tc-thread-panel__divider">
    <span id="tcThreadReplyCount">Replies</span>
</div>

<?php /* ── Thread replies list ───────────────────────────────── */ ?>
<div class="tc-thread-panel__replies" id="tcThreadReplies">
    <?php /* Populated by JS */ ?>
</div>

<?php /* ── Thread composer ────────────────────────────────────── */ ?>
<div class="tc-thread-composer" id="tcThreadComposer"
     data-parent-id=""
     data-conv-id="">

    <div class="tc-composer__input-wrap">
        <div class="tc-composer__input"
             id="tcThreadInput"
             contenteditable="true"
             role="textbox"
             aria-multiline="true"
             aria-label="Reply in thread"
             data-placeholder="Reply in thread…">
        </div>

        <?php /* @mention dropdown inside thread */ ?>
        <div class="tc-mention-dropdown d-none" id="tcThreadMentionDropdown" role="listbox">
            <div class="tc-mention-list" id="tcThreadMentionList"></div>
        </div>
    </div>

    <div class="tc-thread-composer__footer">
        <label class="tc-icon-btn" title="Attach file" for="tcThreadFileInput">
            <i class="ti ti-paperclip"></i>
            <input type="file"
                   id="tcThreadFileInput"
                   multiple
                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip"
                   class="d-none">
        </label>

        <button class="tc-composer__send" id="tcThreadSendBtn" title="Send reply (Enter)">
            <i class="ti ti-send"></i>
        </button>
    </div>

    <?php /* Thread upload preview */ ?>
    <div class="tc-upload-preview d-none" id="tcThreadUploadPreview">
        <div class="tc-upload-chips" id="tcThreadUploadChips"></div>
    </div>

</div>