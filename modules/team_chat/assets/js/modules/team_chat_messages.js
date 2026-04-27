/**
 * team_chat_messages.js
 * Handles message list rendering, real-time updates,
 * load-more pagination, edit, delete, pin/unpin.
 */
var TC_Messages = (function () {
    'use strict';

    var _base      = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _convId    = 0;
    var _userId    = window.TeamChatConfig ? window.TeamChatConfig.userId : 0;
    var _oldestId  = 0;  // Lowest message ID currently rendered — for pagination
    var _loading   = false;
    var _noMore    = false;

    function init() {
        var listEl = document.getElementById('tcMessageList');
        if (!listEl) return;

        _convId = parseInt(listEl.dataset.convId) || 0;
        if (!_convId) return;

        // Determine oldest rendered message
        var bubbles = listEl.querySelectorAll('.tc-bubble[data-msg-id]');
        if (bubbles.length) {
            _oldestId = parseInt(bubbles[0].dataset.msgId) || 0;
        }

        // Socket events
        TC_Socket.on('new_message',      _onNewMessage);
        TC_Socket.on('message_edited',   _onMessageEdited);
        TC_Socket.on('message_deleted',  _onMessageDeleted);
        TC_Socket.on('reaction_updated', _onReactionUpdated);

        // Load-more button
        var loadMoreBtn = document.getElementById('tcLoadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', loadMore);
        }

        scrollToBottom();
    }

    /* ── Load more (pagination) ───────────────────────────── */
    function loadMore() {
        if (_loading || _noMore || !_convId) return;
        _loading = true;

        var btn = document.getElementById('tcLoadMoreBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="tc-spinner" style="width:16px;height:16px;display:inline-block"></span>'; }

        _get('/messages/' + _convId + '?before_id=' + _oldestId + '&limit=50')
            .then(function (res) {
                if (!res.success) return;
                var msgs = res.data || [];
                if (!msgs.length) {
                    _noMore = true;
                    var lm = document.getElementById('tcLoadMore');
                    if (lm) lm.style.display = 'none';
                    return;
                }

                var listEl = document.getElementById('tcMessageList');
                var loadEl = document.getElementById('tcLoadMore');
                var firstBubble = listEl.querySelector('.tc-bubble');

                msgs.forEach(function (msg) {
                    var el = _buildBubble(msg);
                    if (firstBubble) {
                        listEl.insertBefore(el, loadEl ? loadEl.nextSibling : firstBubble);
                    } else {
                        listEl.appendChild(el);
                    }
                    if (!_oldestId || msg.id < _oldestId) _oldestId = msg.id;
                });

                if (msgs.length < 50) {
                    _noMore = true;
                    if (loadEl) loadEl.style.display = 'none';
                }
            })
            .finally(function () {
                _loading = false;
                var btn2 = document.getElementById('tcLoadMoreBtn');
                if (btn2) { btn2.disabled = false; btn2.innerHTML = '<i class="ti ti-arrow-up me-1"></i> Load earlier messages'; }
            });
    }

    /* ── Send ─────────────────────────────────────────────── */
    function send(body, parentId, attachmentIds) {
        if (!body || !_convId) return Promise.reject('Invalid');

        var data = { conversation_id: _convId, body: body };
        if (parentId) data.parent_id = parentId;
        if (attachmentIds && attachmentIds.length) data['attachment_ids[]'] = attachmentIds;

        return _post('/messages/send', data)
            .then(function (res) {
                if (res.success && res.data) {
                    if (!parentId) {
                        _appendMessage(res.data);
                        scrollToBottom();
                    }
                } else {
                    alert(res.message || 'Could not send message.');
                }
                return res;
            });
    }

    /* ── Edit ─────────────────────────────────────────────── */
    function edit(msgId, newBody) {
        return _post('/messages/edit/' + msgId, { body: newBody })
            .then(function (res) {
                if (res.success && res.data) {
                    _updateBubble(res.data);
                } else {
                    alert(res.message || 'Could not edit message.');
                }
                return res;
            });
    }

    /* ── Delete ───────────────────────────────────────────── */
    function remove(msgId) {
        return _post('/messages/delete/' + msgId, {})
            .then(function (res) {
                if (res.success) {
                    _markDeleted(msgId);
                } else {
                    alert(res.message || 'Could not delete message.');
                }
                return res;
            });
    }

    /* ── Pin / Unpin ─────────────────────────────────────── */
    function pin(msgId, convId) {
        return _post('/pins/add', { message_id: msgId, conversation_id: convId || _convId })
            .then(function (res) {
                if (!res.success) alert(res.message || 'Could not pin message.');
                return res;
            });
    }

    function unpin(msgId, convId) {
        return _post('/pins/remove', { message_id: msgId, conversation_id: convId || _convId })
            .then(function (res) {
                if (!res.success) alert(res.message || 'Could not unpin message.');
                return res;
            });
    }

    /* ── Scroll helpers ──────────────────────────────────── */
    function scrollToBottom() {
        var el = document.getElementById('tcMessageList');
        if (el) { el.scrollTop = el.scrollHeight; }
    }

    function scrollTo(msgId) {
        var el = document.querySelector('.tc-bubble[data-msg-id="' + msgId + '"]');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('tc-bubble--highlight');
            setTimeout(function () { el.classList.remove('tc-bubble--highlight'); }, 2000);
        }
    }

    /* ── Socket handlers ─────────────────────────────────── */
    function _onNewMessage(data) {
        var msg = data.message || {};
        if (parseInt(msg.conversation_id) !== _convId) return;
        if (parseInt(msg.parent_id)) return; // Thread replies handled by TC_Threads

        _appendMessage(msg);

        // Auto-scroll if near bottom
        var list = document.getElementById('tcMessageList');
        if (list) {
            var nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 120;
            if (nearBottom) scrollToBottom();
        }

        // Mark as read
        _post('/messages/mark_read', { conversation_id: _convId });
        TC_Socket.sendRead(_convId);
        TC_Conversations.updateUnread(_convId, 0);
    }

    function _onMessageEdited(data) {
        var msg = data.message || {};
        if (msg.id) _updateBubble(msg);
    }

    function _onMessageDeleted(data) {
        if (data.message_id) _markDeleted(data.message_id);
    }

    function _onReactionUpdated(data) {
        TC_Reactions.renderForMessage(data.message_id, data.reactions);
    }

    /* ── DOM manipulation ────────────────────────────────── */
    function _appendMessage(msg) {
        var list = document.getElementById('tcMessageList');
        if (!list) return;

        // Hide "no messages" notice
        var noMsg = document.getElementById('tcNoMessages');
        if (noMsg) noMsg.style.display = 'none';

        // Date divider if needed
        var msgDate   = msg.created_at ? msg.created_at.substring(0, 10) : '';
        var lastDiv   = list.querySelector('.tc-date-divider:last-of-type');
        var lastDate  = lastDiv ? lastDiv.dataset.date : '';

        if (msgDate && msgDate !== lastDate) {
            var divEl = document.createElement('div');
            divEl.className       = 'tc-date-divider';
            divEl.dataset.date    = msgDate;
            divEl.innerHTML       = '<span>' + _dateDividerLabel(msgDate) + '</span>';
            list.appendChild(divEl);
        }

        list.appendChild(_buildBubble(msg));
    }

    function _buildBubble(msg) {
        var div  = document.createElement('div');
        div.innerHTML = _bubbleHTML(msg);
        return div.firstElementChild;
    }

    function _updateBubble(msg) {
        var bubble = document.querySelector('.tc-bubble[data-msg-id="' + msg.id + '"]');
        if (!bubble) return;

        var contentEl = bubble.querySelector('.tc-bubble__content');
        var textEl    = bubble.querySelector('.tc-bubble__text');
        var editedEl  = bubble.querySelector('.tc-edited-label');

        if (contentEl && msg.is_deleted) {
            contentEl.classList.add('tc-bubble__content--deleted');
            contentEl.innerHTML = '<span class="tc-deleted-text"><i class="ti ti-ban"></i> This message was deleted</span>';
        } else if (textEl && msg.body !== undefined) {
            textEl.innerHTML = _parseBody(msg.body);
        }

        if (msg.is_edited && !msg.is_deleted) {
            if (!editedEl) {
                var footer = bubble.querySelector('.tc-bubble__footer');
                if (footer) {
                    var el = document.createElement('span');
                    el.className   = 'tc-edited-label';
                    el.textContent = 'edited';
                    footer.insertBefore(el, footer.firstChild);
                }
            }
        }

        // Update thread count
        if (msg.thread_reply_count !== undefined) {
            var threadLink = bubble.querySelector('.tc-thread-link');
            if (msg.thread_reply_count > 0) {
                if (!threadLink) {
                    threadLink = document.createElement('div');
                    threadLink.className  = 'tc-thread-link';
                    threadLink.onclick    = function () { TC_Threads.open(msg.id); };
                    bubble.querySelector('.tc-bubble__wrap')?.appendChild(threadLink);
                }
                threadLink.innerHTML = '<i class="ti ti-message-reply"></i> ' + msg.thread_reply_count + ' ' + (msg.thread_reply_count === 1 ? 'reply' : 'replies');
            }
        }
    }

    function _markDeleted(msgId) {
        var bubble    = document.querySelector('.tc-bubble[data-msg-id="' + msgId + '"]');
        if (!bubble)  return;
        var contentEl = bubble.querySelector('.tc-bubble__content');
        if (contentEl) {
            contentEl.classList.add('tc-bubble__content--deleted');
            contentEl.innerHTML = '<span class="tc-deleted-text"><i class="ti ti-ban"></i> This message was deleted</span>';
        }
        var actions = bubble.querySelector('.tc-bubble__actions');
        if (actions) actions.remove();
        var reactions = bubble.querySelector('.tc-reactions');
        if (reactions) reactions.remove();
    }

    /* ── HTML builder ─────────────────────────────────────── */
    function _bubbleHTML(msg) {
        var isMe      = parseInt(msg.sender_id) === parseInt(_userId);
        var isDeleted = msg.is_deleted;
        var isSystem  = msg.type === 'system';
        var isMine    = isMe ? 'tc-bubble--mine' : 'tc-bubble--theirs';
        var time      = _msgTime(msg.created_at || '');
        var avatar    = msg.sender_avatar_url || (msg.sender_avatar
            ? '/uploads/users/profile/' + msg.sender_avatar
            : _initialsAvatar(msg.sender_name || '?'));

        if (isSystem) {
            return '<div class="tc-bubble tc-bubble--system" data-msg-id="' + msg.id + '" data-type="system">'
                 + '<span class="tc-system-text"><i class="ti ti-info-circle"></i>' + _esc(msg.body) + '</span></div>';
        }

        var avatarHtml = !isMe
            ? '<div class="tc-bubble__avatar"><img src="' + avatar + '" alt="" class="tc-avatar tc-avatar--sm"></div>'
            : '';

        var headerHtml = !isMe
            ? '<div class="tc-bubble__header"><span class="tc-bubble__sender">' + _esc(msg.sender_name) + '</span>'
            + '<time class="tc-bubble__time">' + time + '</time></div>'
            : '';

        var bodyHtml = isDeleted
            ? '<span class="tc-deleted-text"><i class="ti ti-ban"></i> This message was deleted</span>'
            : _parseBody(msg.body || '');

        var footerTime = isMe
            ? '<time class="tc-bubble__time">' + time + '</time>'
            : '';

        var editedLabel = msg.is_edited && !isDeleted ? '<span class="tc-edited-label">edited</span>' : '';

        var reactionsHtml = '';
        if (msg.reactions && msg.reactions.length && !isDeleted) {
            reactionsHtml = '<div class="tc-reactions" data-msg-id="' + msg.id + '">'
                + msg.reactions.map(function (r) {
                    var mine = r.reacted_by_me ? 'tc-reaction-pill--mine' : '';
                    return '<button class="tc-reaction-pill ' + mine + '" data-msg-id="' + msg.id + '" data-emoji="' + _esc(r.emoji) + '"'
                        + ' title="' + _esc(r.reactor_names || '') + '">'
                        + '<span class="tc-reaction-pill__emoji">' + _esc(r.emoji) + '</span>'
                        + '<span class="tc-reaction-pill__count">' + r.count + '</span></button>';
                }).join('') + '</div>';
        }

        var threadHtml = '';
        if (!msg.parent_id && msg.thread_reply_count > 0 && !isDeleted) {
            threadHtml = '<div class="tc-thread-link" onclick="TC_Threads.open(' + msg.id + ')">'
                + '<i class="ti ti-message-reply"></i> ' + msg.thread_reply_count + ' '
                + (msg.thread_reply_count === 1 ? 'reply' : 'replies') + '</div>';
        }

        return '<div class="tc-bubble ' + isMine + '" data-msg-id="' + msg.id + '" data-sender-id="' + msg.sender_id + '" data-type="' + msg.type + '">'
            + avatarHtml
            + '<div class="tc-bubble__wrap">'
            + headerHtml
            + '<div class="tc-bubble__content' + (isDeleted ? ' tc-bubble__content--deleted' : '') + '">'
            + '<div class="tc-bubble__text">' + bodyHtml + '</div>'
            + '</div>'
            + '<div class="tc-bubble__footer">' + editedLabel + footerTime + '</div>'
            + reactionsHtml + threadHtml
            + '</div></div>';
    }

    /* ── Utils ───────────────────────────────────────────── */
    function _parseBody(body) {
        body = body.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        body = body.replace(/(https?:\/\/[^\s<>"']+)/gi, '<a href="$1" target="_blank" rel="noopener noreferrer" class="tc-link">$1</a>');
        body = body.replace(/@([a-zA-Z0-9._-]+)/g, '<span class="tc-mention">@$1</span>');
        body = body.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        body = body.replace(/_(.+?)_/g, '<em>$1</em>');
        body = body.replace(/`([^`]+)`/g, '<code class="tc-code-inline">$1</code>');
        body = body.replace(/\n/g, '<br>');
        return body;
    }

    function _esc(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function _initialsAvatar(name) {
        var words = name.trim().split(/\s+/);
        var initials = words.length > 1
            ? (words[0][0] + words[words.length-1][0]).toUpperCase()
            : name.substring(0,2).toUpperCase();
        var colors = ['#4f46e5','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#db2777'];
        var color  = colors[Math.abs(name.split('').reduce(function(a,c){return a+c.charCodeAt(0);},0)) % colors.length];
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"><rect width="30" height="30" rx="15" fill="' + color + '"/><text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" font-family="sans-serif" font-size="12" fill="#fff">' + initials + '</text></svg>';
        return 'data:image/svg+xml;base64,' + btoa(svg);
    }

    function _msgTime(datetime) {
        if (!datetime) return '';
        var d    = new Date(datetime);
        var now  = new Date();
        var isToday = d.toDateString() === now.toDateString();
        var h = String(d.getHours()).padStart(2,'0');
        var m = String(d.getMinutes()).padStart(2,'0');
        if (isToday) return h + ':' + m;
        return d.toLocaleDateString() + ' ' + h + ':' + m;
    }

    function _dateDividerLabel(dateStr) {
        var d     = new Date(dateStr + 'T00:00:00');
        var today = new Date(); today.setHours(0,0,0,0);
        var diff  = today - d;
        if (diff === 0)      return 'Today';
        if (diff === 86400000) return 'Yesterday';
        return d.toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' });
    }

    function _post(endpoint, body) {
        var fd = new FormData();
        Object.keys(body).forEach(function (k) { fd.append(k, body[k]); });
        fd.append(window.TeamChatConfig.csrfTokenName, window.TeamChatConfig.csrfHash);
        return fetch(_base + endpoint, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(function (r) { return r.json(); });
    }

    function _get(endpoint) {
        return fetch(_base + endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); });
    }

    return { init: init, send: send, edit: edit, remove: remove, pin: pin, unpin: unpin, loadMore: loadMore, scrollToBottom: scrollToBottom, scrollTo: scrollTo };
})();