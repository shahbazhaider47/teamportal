/**
 * team_chat_input.js
 * Manages the main composer: send on Enter, edit mode,
 * typing indicator emit, character limit.
 */
var TC_Input = (function () {
    'use strict';

    var _base        = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _convId      = 0;
    var _editMsgId   = null;
    var _typingTimer = null;
    var _isTyping    = false;
    var MAX_LEN      = 10000;

    function init() {
        var composer = document.getElementById('tcComposer');
        if (!composer) return;

        _convId = parseInt(composer.dataset.convId) || 0;

        var input   = document.getElementById('tcComposerInput');
        var sendBtn = document.getElementById('tcSendBtn');

        if (!input || !sendBtn) return;

        /* ── Key events ─────────────────────────────────── */
        input.addEventListener('keydown', function (e) {
            // Enter to send, Shift+Enter for newline
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                _submit();
            }

            // Escape cancels edit
            if (e.key === 'Escape' && _editMsgId) {
                cancelEdit();
            }
        });

        /* ── Input events ───────────────────────────────── */
        input.addEventListener('input', function () {
            _handleTyping();
            // Enforce max length
            var text = input.textContent || '';
            if (text.length > MAX_LEN) {
                input.textContent = text.substring(0, MAX_LEN);
                _moveCursorToEnd(input);
            }
        });

        /* ── Send button ─────────────────────────────────── */
        sendBtn.addEventListener('click', _submit);

        /* ── Cancel edit ─────────────────────────────────── */
        var cancelEditBtn = document.getElementById('tcCancelEdit');
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', cancelEdit);
        }
    }

    /* ── Submit ──────────────────────────────────────────── */
    function _submit() {
        var input = document.getElementById('tcComposerInput');
        if (!input) return;

        var body = (input.textContent || '').trim();
        if (!body) return;

        if (_editMsgId) {
            // Edit mode
            TC_Messages.edit(_editMsgId, body)
                .then(function (res) {
                    if (res && res.success) {
                        cancelEdit();
                        input.textContent = '';
                    }
                });
        } else {
            // New message — upload pending files first if any
            var pendingFiles = TC_Upload.getPending();

            if (pendingFiles.length) {
                TC_Upload.uploadAll(_convId)
                    .then(function (attachmentIds) {
                        TC_Messages.send(body, null, attachmentIds);
                    });
            } else {
                TC_Messages.send(body);
            }

            input.textContent = '';
            TC_Upload.clearPreview();
        }

        // Stop typing indicator
        _stopTyping();
    }

    /* ── Edit mode ───────────────────────────────────────── */
    function startEdit(msgId) {
        _editMsgId = msgId;

        var bubble  = document.querySelector('.tc-bubble[data-msg-id="' + msgId + '"]');
        var textEl  = bubble ? bubble.querySelector('.tc-bubble__text') : null;
        var current = textEl ? (textEl.textContent || '') : '';

        var input  = document.getElementById('tcComposerInput');
        var banner = document.getElementById('tcEditBanner');

        if (input) {
            input.textContent = current;
            input.focus();
            _moveCursorToEnd(input);
        }

        if (banner) banner.classList.remove('d-none');
    }

    function cancelEdit() {
        _editMsgId = null;
        var input  = document.getElementById('tcComposerInput');
        var banner = document.getElementById('tcEditBanner');
        if (input)  input.textContent = '';
        if (banner) banner.classList.add('d-none');
    }

    /* ── Typing indicator ────────────────────────────────── */
    function _handleTyping() {
        if (!_isTyping) {
            _isTyping = true;
            TC_Socket.sendTyping(_convId, true);
        }

        clearTimeout(_typingTimer);
        _typingTimer = setTimeout(_stopTyping, 3000);
    }

    function _stopTyping() {
        if (_isTyping) {
            _isTyping = false;
            TC_Socket.sendTyping(_convId, false);
        }
        clearTimeout(_typingTimer);
    }

    /* ── Utils ───────────────────────────────────────────── */
    function _moveCursorToEnd(el) {
        var range = document.createRange();
        var sel   = window.getSelection();
        range.selectNodeContents(el);
        range.collapse(false);
        sel.removeAllRanges();
        sel.addRange(range);
    }

    function getBody() {
        var input = document.getElementById('tcComposerInput');
        return input ? (input.textContent || '').trim() : '';
    }

    function setBody(text) {
        var input = document.getElementById('tcComposerInput');
        if (input) input.textContent = text;
    }

    function clear() {
        var input = document.getElementById('tcComposerInput');
        if (input) input.textContent = '';
    }

    return { init: init, startEdit: startEdit, cancelEdit: cancelEdit, getBody: getBody, setBody: setBody, clear: clear };
})();