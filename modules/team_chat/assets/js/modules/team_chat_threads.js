/**
 * team_chat_threads.js
 * Thread panel: open, load replies, send reply, real-time updates.
 */
var TC_Threads = (function () {
    'use strict';

    var _base      = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _userId    = window.TeamChatConfig ? window.TeamChatConfig.userId  : 0;
    var _parentId  = 0;
    var _convId    = 0;

    function init() {
        TC_Socket.on('new_message', _onNewMessage);

        var closeBtn = document.getElementById('tcCloseThread');
        if (closeBtn) closeBtn.addEventListener('click', close);

        var sendBtn = document.getElementById('tcThreadSendBtn');
        if (sendBtn) sendBtn.addEventListener('click', _sendReply);

        var threadInput = document.getElementById('tcThreadInput');
        if (threadInput) {
            threadInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    _sendReply();
                }
            });
        }
    }

    /* ── Open thread panel ───────────────────────────────── */
    function open(parentMsgId) {
        _parentId = parseInt(parentMsgId);

        var listEl = document.getElementById('tcMessageList');
        _convId = listEl ? (parseInt(listEl.dataset.convId) || 0) : 0;

        var panel = document.getElementById('tcThreadPanel');
        if (!panel) return;

        panel.classList.remove('d-none');
        panel.classList.add('tc-thread-panel--open');

        // Set composer data
        var composer = document.getElementById('tcThreadComposer');
        if (composer) {
            composer.dataset.parentId = _parentId;
            composer.dataset.convId   = _convId;
        }

        _load();

        // Mobile: show panel over pane
        if (window.innerWidth < 992) {
            document.getElementById('tcSidebar')?.classList.add('tc-sidebar--hidden');
        }
    }

    /* ── Close thread panel ──────────────────────────────── */
    function close() {
        _parentId = 0;
        var panel = document.getElementById('tcThreadPanel');
        if (panel) {
            panel.classList.add('d-none');
            panel.classList.remove('tc-thread-panel--open');
        }
        var repliesEl = document.getElementById('tcThreadReplies');
        var parentEl  = document.getElementById('tcThreadParent');
        if (repliesEl) repliesEl.innerHTML = '';
        if (parentEl)  parentEl.innerHTML  = '<div class="tc-thread-loading"><div class="tc-spinner"></div></div>';
    }

    /* ── Load thread ─────────────────────────────────────── */
    function _load() {
        var repliesEl = document.getElementById('tcThreadReplies');
        var parentEl  = document.getElementById('tcThreadParent');
        var countEl   = document.getElementById('tcThreadReplyCount');

        if (repliesEl) repliesEl.innerHTML = '<div class="tc-thread-loading"><div class="tc-spinner"></div></div>';

        fetch(_base + '/messages/thread/' + _parentId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) { if (repliesEl) repliesEl.innerHTML = '<p class="text-muted p-3">Could not load thread.</p>'; return; }

            var data    = res.data || {};
            var parent  = data.parent  || {};
            var replies = data.replies || [];

            // Render parent
            if (parentEl) parentEl.innerHTML = _bubbleHTML(parent, true);

            // Render replies
            if (repliesEl) {
                repliesEl.innerHTML = '';
                replies.forEach(function (msg) {
                    repliesEl.insertAdjacentHTML('beforeend', _bubbleHTML(msg, false));
                });
            }

            // Count
            if (countEl) {
                var n = replies.length;
                countEl.textContent = n + ' ' + (n === 1 ? 'Reply' : 'Replies');
            }

            // Scroll replies to bottom
            if (repliesEl) repliesEl.scrollTop = repliesEl.scrollHeight;
        })
        .catch(function () {
            if (repliesEl) repliesEl.innerHTML = '<p class="text-muted p-3">Error loading thread.</p>';
        });
    }

    /* ── Send reply ──────────────────────────────────────── */
    function _sendReply() {
        var input = document.getElementById('tcThreadInput');
        if (!input) return;

        var body = (input.textContent || '').trim();
        if (!body || !_parentId || !_convId) return;

        input.textContent = '';

        TC_Messages.send(body, _parentId)
            .then(function (res) {
                if (res && res.success && res.data) {
                    _appendReply(res.data);
                }
            });
    }

    /* ── Append reply (real-time or after send) ──────────── */
    function _appendReply(msg) {
        var repliesEl = document.getElementById('tcThreadReplies');
        if (!repliesEl) return;

        repliesEl.insertAdjacentHTML('beforeend', _bubbleHTML(msg, false));
        repliesEl.scrollTop = repliesEl.scrollHeight;

        // Update reply count
        var countEl = document.getElementById('tcThreadReplyCount');
        if (countEl) {
            var current = parseInt(countEl.textContent) || 0;
            var n = current + 1;
            countEl.textContent = n + ' ' + (n === 1 ? 'Reply' : 'Replies');
        }

        // Update thread link in main message list
        var threadLink = document.querySelector('.tc-bubble[data-msg-id="' + _parentId + '"] .tc-thread-link');
        if (threadLink) {
            var count = parseInt(threadLink.textContent) || 0;
            count++;
            threadLink.innerHTML = '<i class="ti ti-message-reply"></i> ' + count + ' ' + (count === 1 ? 'reply' : 'replies');
        }
    }

    /* ── Socket handler ──────────────────────────────────── */
    function _onNewMessage(data) {
        var msg = data.message || {};
        if (!_parentId) return;
        if (parseInt(msg.parent_id) !== _parentId) return;
        if (parseInt(msg.sender_id) === parseInt(_userId)) return; // Already appended optimistically
        _appendReply(msg);
    }

    /* ── HTML builder ─────────────────────────────────────── */
    function _bubbleHTML(msg, isParent) {
        var isMe  = parseInt(msg.sender_id) === parseInt(_userId);
        var isMine = isMe ? 'tc-bubble--mine' : 'tc-bubble--theirs';
        var avatar = msg.sender_avatar_url || (msg.sender_avatar ? '/uploads/users/profile/' + msg.sender_avatar : _initialsAvatar(msg.sender_name || '?'));
        var time   = _msgTime(msg.created_at || '');
        var body   = _parseBody(msg.body || '');

        var avatarHtml = !isMe
            ? '<div class="tc-bubble__avatar"><img src="' + avatar + '" alt="" class="tc-avatar tc-avatar--sm"></div>'
            : '';

        var headerHtml = !isMe
            ? '<div class="tc-bubble__header"><span class="tc-bubble__sender">' + _esc(msg.sender_name) + '</span><time class="tc-bubble__time">' + time + '</time></div>'
            : '';

        var footerTime = isMe ? '<div class="tc-bubble__footer"><time class="tc-bubble__time">' + time + '</time></div>' : '';

        return '<div class="tc-bubble ' + isMine + '" data-msg-id="' + msg.id + '">'
            + avatarHtml
            + '<div class="tc-bubble__wrap">'
            + headerHtml
            + '<div class="tc-bubble__content"><div class="tc-bubble__text">' + body + '</div></div>'
            + footerTime
            + '</div></div>';
    }

    function _parseBody(body) {
        body = body.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        body = body.replace(/(https?:\/\/[^\s]+)/gi,'<a href="$1" target="_blank" class="tc-link">$1</a>');
        body = body.replace(/@([a-zA-Z0-9._-]+)/g,'<span class="tc-mention">@$1</span>');
        body = body.replace(/\n/g,'<br>');
        return body;
    }

    function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function _msgTime(dt) {
        if (!dt) return '';
        var d = new Date(dt);
        return String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
    }

    function _initialsAvatar(name) {
        var w = name.trim().split(/\s+/);
        var i = w.length > 1 ? (w[0][0]+w[w.length-1][0]).toUpperCase() : name.substring(0,2).toUpperCase();
        var colors = ['#4f46e5','#0891b2','#059669','#d97706','#dc2626'];
        var c = colors[Math.abs(name.split('').reduce(function(a,ch){return a+ch.charCodeAt(0);},0)) % colors.length];
        return 'data:image/svg+xml;base64,' + btoa('<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"><rect width="30" height="30" rx="15" fill="'+c+'"/><text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" font-family="sans-serif" font-size="12" fill="#fff">'+i+'</text></svg>');
    }

    return { init: init, open: open, close: close };
})();