/**
 * Team Chat bundled JavaScript.
 * Source sections were consolidated from assets/js/modules/*.js and modal partial scripts.
 */


/* ===== modules/team_chat_socket.js ===== */

/**
 * team_chat_socket.js
 * WebSocket client with auto-reconnect, event dispatch,
 * and polling fallback when WS is unavailable.
 */
var TC_Socket = (function () {
    'use strict';

    var _ws            = null;
    var _url           = '';
    var _userId        = 0;
    var _token         = '';
    var _connected     = false;
    var _reconnectTimer= null;
    var _reconnectDelay= 2000;
    var _maxDelay      = 30000;
    var _pingTimer     = null;
    var _handlers      = {};  // { event: [callbacks] }
    var _pendingJoins  = [];  // conversation IDs to join after connect
    var _usePolling    = false;

    /* ── Public API ──────────────────────────────────────── */
    function init(socketUrl, userId, token) {
        _userId = userId;
        _token  = token;

        if (!socketUrl || !window.WebSocket) {
            console.warn('[TC_Socket] WebSocket unavailable. Using polling fallback.');
            _usePolling = true;
            return;
        }

        _url = socketUrl + '?user_id=' + userId + '&token=' + token;
        _connect();
    }

    function on(event, callback) {
        if (!_handlers[event]) _handlers[event] = [];
        _handlers[event].push(callback);
    }

    function off(event, callback) {
        if (!_handlers[event]) return;
        _handlers[event] = _handlers[event].filter(function (fn) { return fn !== callback; });
    }

    function emit(event, data) {
        if (!_connected || !_ws) return false;
        try {
            _ws.send(JSON.stringify(Object.assign({ event: event }, data)));
            return true;
        } catch (e) {
            console.error('[TC_Socket] send error:', e);
            return false;
        }
    }

    function joinRoom(conversationId) {
        if (!_connected) {
            if (_pendingJoins.indexOf(conversationId) === -1) {
                _pendingJoins.push(conversationId);
            }
            return;
        }
        emit('join', { conversation_id: conversationId });
    }

    function leaveRoom(conversationId) {
        emit('leave', { conversation_id: conversationId });
    }

    function sendTyping(conversationId, isTyping) {
        emit('typing', { conversation_id: conversationId, is_typing: isTyping });
    }

    function sendRead(conversationId) {
        emit('read', { conversation_id: conversationId });
    }

    function isConnected() { return _connected; }
    function isPolling()   { return _usePolling; }

    /* ── Private ─────────────────────────────────────────── */
    function _connect() {
        try {
            _ws = new WebSocket(_url);

            _ws.onopen = function () {
                _connected     = true;
                _reconnectDelay= 2000;
                console.log('[TC_Socket] Connected.');
                _dispatch('connected', {});

                // Join pending rooms
                _pendingJoins.forEach(function (cid) { emit('join', { conversation_id: cid }); });
                _pendingJoins = [];

                // Ping keepalive
                _pingTimer = setInterval(function () {
                    emit('ping', {});
                }, 25000);
            };

            _ws.onmessage = function (e) {
                var data;
                try { data = JSON.parse(e.data); } catch (_) { return; }
                if (data && data.event) {
                    _dispatch(data.event, data);
                }
            };

            _ws.onclose = function () {
                _onDisconnect();
            };

            _ws.onerror = function (err) {
                console.warn('[TC_Socket] Error — falling back to polling.', err);
                _usePolling = true;
                _ws && _ws.close();
            };

        } catch (e) {
            console.warn('[TC_Socket] Connect failed:', e);
            _usePolling = true;
        }
    }

    function _onDisconnect() {
        _connected = false;
        clearInterval(_pingTimer);
        _dispatch('disconnected', {});

        // Exponential back-off reconnect
        _reconnectTimer = setTimeout(function () {
            _reconnectDelay = Math.min(_reconnectDelay * 1.5, _maxDelay);
            console.log('[TC_Socket] Reconnecting in', _reconnectDelay, 'ms');
            _connect();
        }, _reconnectDelay);
    }

    function _dispatch(event, data) {
        var handlers = _handlers[event] || [];
        handlers.forEach(function (fn) {
            try { fn(data); } catch (e) { console.error('[TC_Socket] Handler error:', e); }
        });

        // Also dispatch a generic '*' wildcard handler
        (_handlers['*'] || []).forEach(function (fn) {
            try { fn(event, data); } catch (e) {}
        });
    }

    /* ── Expose ──────────────────────────────────────────── */
    return { init: init, on: on, off: off, emit: emit, joinRoom: joinRoom, leaveRoom: leaveRoom, sendTyping: sendTyping, sendRead: sendRead, isConnected: isConnected, isPolling: isPolling };
})();



/* ===== modules/team_chat_conversations.js ===== */

/**
 * team_chat_conversations.js
 * Manages the sidebar conversation list: opening, creating,
 * filtering, unread badges, and real-time sidebar updates.
 */
var TC_Conversations = (function () {
    'use strict';

    var _activeConvId = 0;
    var _base         = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';

    function init() {
        _activeConvId = window.TeamChatConfig ? window.TeamChatConfig.activeConversationId : 0;

        // Socket events
        TC_Socket.on('new_message',          _onNewMessage);
        TC_Socket.on('conversation_updated', _onConversationUpdated);
        TC_Socket.on('member_joined',        _refreshAfterMemberChange);
        TC_Socket.on('member_left',          _refreshAfterMemberChange);

        // Join active conversation room
        if (_activeConvId) {
            TC_Socket.joinRoom(_activeConvId);
        }
    }

    /* ── Open a conversation ─────────────────────────────── */
    function open(convId) {
        convId = parseInt(convId);
        if (!convId) return;

        // Navigate — full page for now; can be AJAX later
        window.location.href = _base.replace('/team_chat', '') + '/team_chat/conversation/' + convId;
    }

    /* ── Create conversations ────────────────────────────── */
    function createDirect(targetUserId) {
        return _post('/conversation/create_direct', { target_user_id: targetUserId })
            .then(function (res) {
                if (res.success && res.data) {
                    open(res.data.id);
                } else {
                    _alert(res.message || 'Could not start conversation.');
                }
                return res;
            });
    }

    function createGroup(name, memberIds) {
        return _post('/conversation/create_group', { name: name, 'member_ids[]': memberIds })
            .then(function (res) {
                if (res.success && res.data) {
                    open(res.data.id);
                } else {
                    _alert(res.message || 'Could not create group.');
                }
                return res;
            });
    }

    function createChannel(name, desc, teamId, deptId) {
        return _post('/conversation/create_channel', {
            name:          name,
            description:   desc,
            team_id:       teamId || 0,
            department_id: deptId || 0,
        }).then(function (res) {
            if (res.success && res.data) {
                open(res.data.id);
            } else {
                _alert(res.message || 'Could not create channel.');
            }
            return res;
        });
    }

    /* ── Update ──────────────────────────────────────────── */
    function archive(convId) {
        return _post('/conversation/archive/' + convId, {})
            .then(function (res) {
                if (res.success) { window.location.href = _base.replace('/team_chat', '') + '/team_chat'; }
                else { _alert(res.message || 'Could not archive.'); }
            });
    }

    function rename(name) {
        if (!_activeConvId) return;
        return _post('/conversation/update/' + _activeConvId, { name: name })
            .then(function (res) {
                if (res.success) {
                    var el = document.getElementById('tcPaneName');
                    if (el) el.textContent = name;
                    _updateSidebarItem(_activeConvId, { name: name });
                } else {
                    _alert(res.message || 'Could not rename.');
                }
            });
    }

    function mute(convId, shouldMute) {
        return _post('/members/mute', { conversation_id: convId, mute: shouldMute ? 1 : 0 })
            .then(function (res) {
                if (res.success) {
                    var btn  = document.getElementById('tcMuteBtn');
                    var icon = btn ? btn.querySelector('i') : null;
                    if (btn)  btn.dataset.muted  = shouldMute ? '1' : '0';
                    if (btn)  btn.title           = shouldMute ? 'Unmute' : 'Mute';
                    if (icon) { icon.className = shouldMute ? 'ti ti-bell-off' : 'ti ti-bell'; }
                }
            });
    }

    /* ── Sidebar filter ──────────────────────────────────── */
    function filter(query) {
        var items = document.querySelectorAll('.tc-conv-item');
        items.forEach(function (item) {
            var name = (item.querySelector('.tc-conv-item__name')?.textContent || '').toLowerCase();
            var preview = (item.querySelector('.tc-conv-item__preview')?.textContent || '').toLowerCase();
            var match   = !query || name.includes(query) || preview.includes(query);
            item.style.display = match ? '' : 'none';
        });
    }

    /* ── Mark active ─────────────────────────────────────── */
    function markActive(convId) {
        document.querySelectorAll('.tc-conv-item').forEach(function (el) {
            el.classList.toggle('is-active', parseInt(el.dataset.convId) === parseInt(convId));
        });
    }

    /* ── Unread badge update ─────────────────────────────── */
    function updateUnread(convId, count) {
        var item  = document.querySelector('.tc-conv-item[data-conv-id="' + convId + '"]');
        if (!item) return;

        var badge = item.querySelector('.tc-badge');

        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'tc-badge';
                item.querySelector('.tc-conv-item__row:last-child')?.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
            item.querySelector('.tc-conv-item__preview')?.classList.add('tc-conv-item__preview--unread');
        } else {
            if (badge) badge.remove();
            item.querySelector('.tc-conv-item__preview')?.classList.remove('tc-conv-item__preview--unread');
        }
    }

    /* ── Socket handlers ─────────────────────────────────── */
    function _onNewMessage(data) {
        var msg    = data.message || {};
        var convId = parseInt(msg.conversation_id || data.conversation_id);
        if (!convId) return;

        // Update last message preview in sidebar
        _updateSidebarItem(convId, {
            preview: msg.body || '',
            time:    msg.created_at || '',
        });

        // Bump unread if not in this conversation
        if (convId !== _activeConvId) {
            var item  = document.querySelector('.tc-conv-item[data-conv-id="' + convId + '"]');
            var badge = item ? item.querySelector('.tc-badge') : null;
            var count = badge ? (parseInt(badge.textContent) || 0) + 1 : 1;
            updateUnread(convId, count);
        }

        // Move conversation to top of its section
        _bumpToTop(convId);
    }

    function _onConversationUpdated(data) {
        var conv = data.conversation || {};
        if (conv.id) {
            _updateSidebarItem(conv.id, { name: conv.name });
        }
    }

    function _refreshAfterMemberChange() {
        // Minimal: just reload if we were removed
    }

    /* ── DOM helpers ─────────────────────────────────────── */
    function _updateSidebarItem(convId, fields) {
        var item = document.querySelector('.tc-conv-item[data-conv-id="' + convId + '"]');
        if (!item) return;

        if (fields.name !== undefined) {
            var nameEl = item.querySelector('.tc-conv-item__name');
            if (nameEl) {
                // Preserve type icon
                var icon = nameEl.querySelector('i');
                nameEl.textContent = fields.name;
                if (icon) nameEl.insertBefore(icon, nameEl.firstChild);
            }
        }

        if (fields.preview !== undefined) {
            var previewEl = item.querySelector('.tc-conv-item__preview');
            if (previewEl) previewEl.textContent = _truncate(fields.preview, 55);
        }

        if (fields.time !== undefined) {
            var timeEl = item.querySelector('.tc-conv-item__time');
            if (timeEl) timeEl.textContent = _timeAgo(fields.time);
        }
    }

    function _bumpToTop(convId) {
        var item = document.querySelector('.tc-conv-item[data-conv-id="' + convId + '"]');
        if (!item) return;
        var list = item.parentElement;
        if (list && list.firstChild !== item) {
            list.insertBefore(item, list.firstChild);
        }
    }

    /* ── Utils ───────────────────────────────────────────── */
    function _post(endpoint, body) {
        var formData = new FormData();
        Object.keys(body).forEach(function (k) {
            var val = body[k];
            if (Array.isArray(val)) {
                val.forEach(function (v) { formData.append(k, v); });
            } else {
                formData.append(k, val);
            }
        });
        formData.append(window.TeamChatConfig.csrfTokenName, window.TeamChatConfig.csrfHash);

        return fetch(_base + endpoint, {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    formData,
        }).then(function (r) { return r.json(); });
    }

    function _alert(msg) { alert(msg); }

    function _truncate(str, len) {
        str = String(str || '').replace(/<[^>]+>/g, '').trim();
        return str.length > len ? str.substring(0, len) + '…' : str;
    }

    function _timeAgo(datetime) {
        if (!datetime) return '';
        var diff = Math.floor((Date.now() - new Date(datetime).getTime()) / 1000);
        if (diff < 60)    return 'just now';
        if (diff < 3600)  return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return Math.floor(diff / 86400) + 'd';
    }

    return { init: init, open: open, createDirect: createDirect, createGroup: createGroup, createChannel: createChannel, archive: archive, rename: rename, mute: mute, filter: filter, markActive: markActive, updateUnread: updateUnread };
})();



/* ===== modules/team_chat_messages.js ===== */

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



/* ===== modules/team_chat_threads.js ===== */

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



/* ===== modules/team_chat_input.js ===== */

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



/* ===== modules/team_chat_upload.js ===== */

/**
 * team_chat_upload.js
 * File upload: file input, drag-drop, progress chips, attachment linking.
 */
var TC_Upload = (function () {
    'use strict';

    var _base    = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _pending = [];  // [{ file, attachmentId, status }]
    var _convId  = 0;
    var MAX_MB   = window.TeamChatConfig ? window.TeamChatConfig.maxFileSizeMb : 10;
    var MAX_BYTES= MAX_MB * 1048576;

    var ALLOWED_TYPES = [
        'image/jpeg','image/png','image/gif','image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain','text/csv',
        'application/zip','application/x-rar-compressed',
        'video/mp4','video/webm',
        'audio/mpeg','audio/ogg','audio/wav',
    ];

    function init() {
        var composer = document.getElementById('tcComposer');
        if (composer) {
            _convId = parseInt(composer.dataset.convId) || 0;
        }

        var fileInput = document.getElementById('tcFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                handleFiles(this.files);
                this.value = ''; // Reset so same file can be re-selected
            });
        }

        // Thread file input
        var threadFileInput = document.getElementById('tcThreadFileInput');
        if (threadFileInput) {
            threadFileInput.addEventListener('change', function () {
                handleFiles(this.files);
                this.value = '';
            });
        }
    }

    /* ── Handle file list ────────────────────────────────── */
    function handleFiles(files) {
        if (!files || !files.length) return;

        Array.from(files).forEach(function (file) {
            // Validate size
            if (file.size > MAX_BYTES) {
                alert(file.name + ' exceeds the maximum file size of ' + MAX_MB + ' MB.');
                return;
            }

            // Validate type
            if (!ALLOWED_TYPES.includes(file.type)) {
                alert(file.name + ': file type not allowed.');
                return;
            }

            var pending = { file: file, attachmentId: null, status: 'pending', uid: _uid() };
            _pending.push(pending);
            _addChip(pending);
        });

        if (_pending.length) _showPreview();
    }

    /* ── Upload all pending to server ────────────────────── */
    function uploadAll(convId) {
        convId = convId || _convId;
        var promises = _pending
            .filter(function (p) { return p.status === 'pending'; })
            .map(function (p) { return _uploadOne(p, convId); });

        return Promise.all(promises).then(function (results) {
            return results.filter(Boolean);
        });
    }

    function _uploadOne(pending, convId) {
        pending.status = 'uploading';
        _updateChip(pending.uid, 'uploading', 0);

        var fd = new FormData();
        fd.append('file', pending.file);
        fd.append('conversation_id', convId);
        fd.append(window.TeamChatConfig.csrfTokenName, window.TeamChatConfig.csrfHash);

        return new Promise(function (resolve) {
            var xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    var pct = Math.round((e.loaded / e.total) * 100);
                    _updateChip(pending.uid, 'uploading', pct);
                }
            });

            xhr.addEventListener('load', function () {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.data) {
                        pending.status      = 'done';
                        pending.attachmentId= res.data.id;
                        _updateChip(pending.uid, 'done', 100);
                        resolve(res.data.id);
                    } else {
                        pending.status = 'error';
                        _updateChip(pending.uid, 'error', 0);
                        alert('Upload failed: ' + (res.message || 'Unknown error'));
                        resolve(null);
                    }
                } catch (e) {
                    pending.status = 'error';
                    _updateChip(pending.uid, 'error', 0);
                    resolve(null);
                }
            });

            xhr.addEventListener('error', function () {
                pending.status = 'error';
                _updateChip(pending.uid, 'error', 0);
                resolve(null);
            });

            xhr.open('POST', _base + '/upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(fd);
        });
    }

    /* ── Attach uploaded file to a message ───────────────── */
    function attachToMessage(attachmentId, messageId) {
        var fd = new FormData();
        fd.append('attachment_id', attachmentId);
        fd.append('message_id', messageId);
        fd.append(window.TeamChatConfig.csrfTokenName, window.TeamChatConfig.csrfHash);

        return fetch(_base + '/upload/attach', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    fd,
        }).then(function (r) { return r.json(); });
    }

    /* ── Get pending attachment IDs ──────────────────────── */
    function getPending() {
        return _pending.filter(function (p) { return p.status === 'pending' || p.status === 'done'; });
    }

    /* ── Clear preview ───────────────────────────────────── */
    function clearPreview() {
        _pending = [];
        var previewEl = document.getElementById('tcUploadPreview');
        var chipsEl   = document.getElementById('tcUploadChips');
        if (chipsEl)   chipsEl.innerHTML = '';
        if (previewEl) previewEl.classList.add('d-none');
    }

    /* ── Drop zone ───────────────────────────────────────── */
    function showDropZone() {
        var dz = document.getElementById('tcDropZone');
        if (dz) dz.classList.remove('d-none');
    }

    function hideDropZone() {
        var dz = document.getElementById('tcDropZone');
        if (dz) dz.classList.add('d-none');
    }

    /* ── DOM helpers ─────────────────────────────────────── */
    function _showPreview() {
        var previewEl = document.getElementById('tcUploadPreview');
        if (previewEl) previewEl.classList.remove('d-none');
    }

    function _addChip(pending) {
        var chipsEl = document.getElementById('tcUploadChips');
        if (!chipsEl) return;

        var chip = document.createElement('div');
        chip.className        = 'tc-upload-chip';
        chip.dataset.uid      = pending.uid;
        chip.innerHTML = '<i class="ti ti-file"></i>'
            + '<span class="tc-upload-chip__name" title="' + _esc(pending.file.name) + '">' + _esc(pending.file.name) + '</span>'
            + '<button type="button" class="tc-upload-chip__remove" title="Remove"><i class="ti ti-x"></i></button>';

        chip.querySelector('.tc-upload-chip__remove').addEventListener('click', function () {
            _removeChip(pending.uid);
        });

        chipsEl.appendChild(chip);
    }

    function _updateChip(uid, status, pct) {
        var chip = document.querySelector('.tc-upload-chip[data-uid="' + uid + '"]');
        if (!chip) return;

        var icon = chip.querySelector('i');
        if (status === 'uploading') {
            if (icon) icon.className = 'ti ti-loader-2';
            chip.title = pct + '%';
        } else if (status === 'done') {
            if (icon) icon.className = 'ti ti-check';
        } else if (status === 'error') {
            if (icon) icon.className = 'ti ti-alert-triangle';
            chip.style.borderColor = '#ef4444';
        }
    }

    function _removeChip(uid) {
        _pending = _pending.filter(function (p) { return p.uid !== uid; });
        var chip = document.querySelector('.tc-upload-chip[data-uid="' + uid + '"]');
        if (chip) chip.remove();
        if (!_pending.length) clearPreview();
    }

    function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function _uid()  { return Math.random().toString(36).substring(2, 10); }

    return { init: init, handleFiles: handleFiles, uploadAll: uploadAll, attachToMessage: attachToMessage, getPending: getPending, clearPreview: clearPreview, showDropZone: showDropZone, hideDropZone: hideDropZone };
})();



/* ===== modules/team_chat_mentions.js ===== */

/**
 * team_chat_mentions.js
 * @mention autocomplete in both main composer and thread composer.
 */
var TC_Mentions = (function () {
    'use strict';

    var _base        = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _searchTimer = null;
    var _activeIndex = -1;
    var _results     = [];

    function init() {
        _bindComposer('tcComposerInput',     'tcMentionDropdown',  'tcMentionList');
        _bindComposer('tcThreadInput',       'tcThreadMentionDropdown', 'tcThreadMentionList');
    }

    function _bindComposer(inputId, dropdownId, listId) {
        var input    = document.getElementById(inputId);
        var dropdown = document.getElementById(dropdownId);
        var list     = document.getElementById(listId);

        if (!input || !dropdown || !list) return;

        input.addEventListener('input', function () {
            var text  = _getTextBeforeCaret(input);
            var match = text.match(/@([a-zA-Z0-9._-]*)$/);

            if (!match) {
                _close(dropdown);
                return;
            }

            var query = match[1];
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(function () {
                _search(query, list, dropdown, input);
            }, 200);
        });

        input.addEventListener('keydown', function (e) {
            if (dropdown.classList.contains('d-none')) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                _activeIndex = Math.min(_activeIndex + 1, _results.length - 1);
                _highlightItem(list);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                _activeIndex = Math.max(_activeIndex - 1, 0);
                _highlightItem(list);
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                if (_activeIndex >= 0 && _results[_activeIndex]) {
                    e.preventDefault();
                    _insert(input, dropdown, _results[_activeIndex].username || _results[_activeIndex].fullname);
                }
            } else if (e.key === 'Escape') {
                _close(dropdown);
            }
        });

        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target) && e.target !== input) {
                _close(dropdown);
            }
        });
    }

    function _search(query, listEl, dropdown, input) {
        var url = _base + '/users/search?q=' + encodeURIComponent(query);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success || !res.data || !res.data.length) {
                    _close(dropdown);
                    return;
                }

                _results     = res.data;
                _activeIndex = 0;

                listEl.innerHTML = _results.map(function (u, i) {
                    var avatar = u.avatar_url || '';
                    return '<div class="tc-mention-item' + (i===0?' is-active':'') + '" data-index="' + i + '" data-username="' + _esc(u.username || u.fullname) + '">'
                        + (avatar ? '<img class="tc-avatar tc-avatar--xs" src="' + avatar + '" alt="">' : '<span class="tc-avatar tc-avatar--xs" style="background:#4f46e5;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;">' + _esc((u.fullname||'?')[0]) + '</span>')
                        + '<div><div class="tc-mention-item__name">' + _esc(u.fullname) + '</div>'
                        + '<div class="tc-mention-item__meta">' + _esc(u.emp_id || '') + '</div></div></div>';
                }).join('');

                // Click handlers
                listEl.querySelectorAll('.tc-mention-item').forEach(function (el) {
                    el.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        _insert(input, dropdown, this.dataset.username);
                    });
                    el.addEventListener('mouseover', function () {
                        _activeIndex = parseInt(this.dataset.index);
                        _highlightItem(listEl);
                    });
                });

                dropdown.classList.remove('d-none');
            })
            .catch(function () { _close(dropdown); });
    }

    function _insert(input, dropdown, username) {
        // Replace the @partial typed so far with @username + space
        _replacePartialMention(input, username);
        _close(dropdown);
        input.focus();
    }

    function _replacePartialMention(input, username) {
        // Get current content and caret position
        var sel  = window.getSelection();
        if (!sel.rangeCount) return;

        var range = sel.getRangeAt(0);
        var node  = range.startContainer;
        var text  = node.textContent || '';
        var offset= range.startOffset;

        // Find the @ that started this mention
        var before  = text.substring(0, offset);
        var atIndex = before.lastIndexOf('@');
        if (atIndex === -1) return;

        // Replace text in the text node
        var newText = text.substring(0, atIndex) + '@' + username + '\u00A0' + text.substring(offset);
        node.textContent = newText;

        // Move caret after inserted text
        var newOffset = atIndex + username.length + 2;
        var newRange  = document.createRange();
        newRange.setStart(node, Math.min(newOffset, node.textContent.length));
        newRange.collapse(true);
        sel.removeAllRanges();
        sel.addRange(newRange);
    }

    function _close(dropdown) {
        if (dropdown) dropdown.classList.add('d-none');
        _results     = [];
        _activeIndex = -1;
    }

    function _highlightItem(listEl) {
        listEl.querySelectorAll('.tc-mention-item').forEach(function (el, i) {
            el.classList.toggle('is-active', i === _activeIndex);
        });
    }

    function _getTextBeforeCaret(el) {
        var sel = window.getSelection();
        if (!sel.rangeCount) return '';
        var range  = sel.getRangeAt(0).cloneRange();
        range.selectNodeContents(el);
        range.setEnd(sel.getRangeAt(0).endContainer, sel.getRangeAt(0).endOffset);
        return range.toString();
    }

    function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    return { init: init };
})();



/* ===== modules/team_chat_reactions.js ===== */

/**
 * team_chat_reactions.js
 * Emoji picker, reaction toggle, and reaction bar rendering.
 */
var TC_Reactions = (function () {
    'use strict';

    var _base    = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _picker  = null;
    var _targetMsgId = 0;

    /* Common emoji set grouped by category */
    var EMOJI_CATEGORIES = [
        { icon: '😀', label: 'Smileys', emojis: ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🥸','🤩','🥳'] },
        { icon: '👍', label: 'Gestures', emojis: ['👍','👎','👌','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝️','👋','🤚','🖐️','✋','🖖','💪','🤜','🤛','👊','✊','🙌','👐','🤲','🤝','🙏','💅','🤳'] },
        { icon: '❤️', label: 'Hearts', emojis: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❤️‍🔥','❤️‍🩹','💕','💞','💓','💗','💖','💘','💝','💟','♥️','🫀'] },
        { icon: '🎉', label: 'Celebration', emojis: ['🎉','🎊','🎈','🎀','🎁','🎂','🎆','🎇','🧨','🎏','🎐','🎑','🎃','🎄','🎋','🎍','🎎','🎠','🎡','🎢','🎪','🤹','🎭','🎬','🎤','🎧','🎼','🎹','🎸','🎺'] },
        { icon: '🚀', label: 'Objects', emojis: ['🚀','✅','❌','⚡','🔥','💡','💰','📌','📎','🔑','🔒','🔓','📱','💻','🖥️','🖨️','⌨️','🖱️','💾','📀','📷','📸','📹','🎥','📡','☎️','📞','📺','📻','⏰','⏱️'] },
        { icon: '😸', label: 'Animals', emojis: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐔','🐧','🐦','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝'] },
    ];

    function init() {
        _picker = document.getElementById('tcEmojiPicker');
        if (_picker) _buildPicker();

        // Emoji trigger on message hover
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.tc-emoji-trigger');
            if (trigger) {
                e.stopPropagation();
                var msgId = parseInt(trigger.dataset.msgId);
                _openPicker(msgId, trigger);
                return;
            }

            // Composer emoji button
            var composerBtn = e.target.closest('#tcEmojiPickerBtn');
            if (composerBtn) {
                e.stopPropagation();
                _openPicker(0, composerBtn, true);
                return;
            }

            // Close picker on outside click
            if (_picker && !_picker.classList.contains('d-none') && !_picker.contains(e.target)) {
                _picker.classList.add('d-none');
            }
        });

        // Reaction pill click (toggle)
        document.addEventListener('click', function (e) {
            var pill = e.target.closest('.tc-reaction-pill');
            if (!pill) return;
            var msgId = parseInt(pill.dataset.msgId);
            var emoji = pill.dataset.emoji;
            if (msgId && emoji) toggle(msgId, emoji);
        });
    }

    /* ── Toggle reaction ─────────────────────────────────── */
    function toggle(msgId, emoji) {
        var fd = new FormData();
        fd.append('message_id', msgId);
        fd.append('emoji', emoji);
        fd.append(window.TeamChatConfig.csrfTokenName, window.TeamChatConfig.csrfHash);

        fetch(_base + '/reactions/toggle', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                renderForMessage(msgId, res.data.reactions);
            }
        });
    }

    /* ── Render reactions for a message ──────────────────── */
    function renderForMessage(msgId, reactions) {
        var bubble = document.querySelector('.tc-bubble[data-msg-id="' + msgId + '"]');
        if (!bubble) return;

        var existing = bubble.querySelector('.tc-reactions');
        if (existing) existing.remove();

        if (!reactions || !reactions.length) return;

        var html = '<div class="tc-reactions" data-msg-id="' + msgId + '">'
            + reactions.map(function (r) {
                var mine = r.reacted_by_me ? 'tc-reaction-pill--mine' : '';
                return '<button class="tc-reaction-pill ' + mine + '" data-msg-id="' + msgId + '" data-emoji="' + _esc(r.emoji) + '" title="' + _esc(r.reactor_names || '') + '">'
                    + '<span class="tc-reaction-pill__emoji">' + _esc(r.emoji) + '</span>'
                    + '<span class="tc-reaction-pill__count">' + r.count + '</span></button>';
            }).join('')
            + '</div>';

        var wrap = bubble.querySelector('.tc-bubble__wrap');
        var thread = bubble.querySelector('.tc-thread-link');
        if (thread) {
            wrap.insertBefore(_htmlToEl(html), thread);
        } else {
            wrap.insertAdjacentHTML('beforeend', html);
        }
    }

    /* ── Build picker HTML ───────────────────────────────── */
    function _buildPicker() {
        if (!_picker) return;

        // Categories
        var catEl = document.getElementById('tcEmojiCategories');
        if (catEl) {
            catEl.innerHTML = EMOJI_CATEGORIES.map(function (cat, i) {
                return '<button class="tc-emoji-cat-btn' + (i===0?' is-active':'') + '" title="' + cat.label + '" data-cat="' + i + '">' + cat.icon + '</button>';
            }).join('');

            catEl.querySelectorAll('.tc-emoji-cat-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    catEl.querySelectorAll('.tc-emoji-cat-btn').forEach(function(b){b.classList.remove('is-active');});
                    this.classList.add('is-active');
                    _renderGrid(parseInt(this.dataset.cat));
                });
            });
        }

        _renderGrid(0);

        // Search
        var searchEl = document.getElementById('tcEmojiSearch');
        if (searchEl) {
            searchEl.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase();
                if (!q) { _renderGrid(0); return; }
                var all = [];
                EMOJI_CATEGORIES.forEach(function (cat) {
                    cat.emojis.forEach(function (e) { all.push(e); });
                });
                _renderGridEmojis(all.filter(function (e) { return e.includes(q); }));
            });
        }
    }

    function _renderGrid(catIndex) {
        var cat = EMOJI_CATEGORIES[catIndex];
        if (!cat) return;
        _renderGridEmojis(cat.emojis);
    }

    function _renderGridEmojis(emojis) {
        var grid = document.getElementById('tcEmojiGrid');
        if (!grid) return;

        grid.innerHTML = emojis.map(function (e) {
            return '<button class="tc-emoji-btn" data-emoji="' + _esc(e) + '">' + e + '</button>';
        }).join('');

        grid.querySelectorAll('.tc-emoji-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var emoji = this.dataset.emoji;
                if (_targetMsgId) {
                    toggle(_targetMsgId, emoji);
                } else {
                    // Insert into composer
                    var input = document.getElementById('tcComposerInput');
                    if (input) {
                        input.focus();
                        document.execCommand('insertText', false, emoji);
                    }
                }
                if (_picker) _picker.classList.add('d-none');
            });
        });
    }

    function _openPicker(msgId, triggerEl, isComposer) {
        if (!_picker) return;
        _targetMsgId = isComposer ? 0 : msgId;

        // Position near trigger
        var rect  = triggerEl.getBoundingClientRect();
        var shell = document.getElementById('teamChatShell');
        var sRect = shell ? shell.getBoundingClientRect() : { top: 0, left: 0 };

        _picker.style.bottom  = 'auto';
        _picker.style.top     = (rect.bottom - sRect.top + 4) + 'px';
        _picker.style.right   = 'auto';
        _picker.style.left    = Math.min(rect.left - sRect.left, window.innerWidth - 340) + 'px';

        _picker.classList.toggle('d-none');
    }

    function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function _htmlToEl(html) { var d = document.createElement('div'); d.innerHTML = html; return d.firstElementChild; }

    return { init: init, toggle: toggle, renderForMessage: renderForMessage };
})();



/* ===== modules/team_chat_search.js ===== */

/**
 * team_chat_search.js
 * Full-text message search within a conversation.
 */
var TC_Search = (function () {
    'use strict';

    var _base      = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _timer     = null;
    var _convId    = 0;

    function init() {
        var listEl = document.getElementById('tcMessageList');
        _convId    = listEl ? (parseInt(listEl.dataset.convId) || 0) : 0;

        var searchInput = document.getElementById('tcMessageSearchInput');
        if (!searchInput) return;

        searchInput.addEventListener('input', function () {
            clearTimeout(_timer);
            var q = this.value.trim();

            if (!q) {
                _clearResults();
                return;
            }

            _timer = setTimeout(function () { _search(q); }, 300);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                _clearResults();
                this.value = '';
            }
        });
    }

    function _search(query) {
        var resultsEl = document.getElementById('tcMessageSearchResults');
        var listEl    = document.getElementById('tcMessageSearchList');

        if (!resultsEl || !listEl) return;

        listEl.innerHTML = '<div class="p-3 text-muted text-center"><div class="tc-spinner" style="width:20px;height:20px;margin:auto"></div></div>';
        resultsEl.classList.remove('d-none');

        var url = _base + '/messages/search?q=' + encodeURIComponent(query) + (_convId ? '&conversation_id=' + _convId : '');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success || !res.data || !res.data.length) {
                    listEl.innerHTML = '<div class="p-3 text-muted text-center">No results found for <strong>' + _esc(query) + '</strong></div>';
                    return;
                }

                listEl.innerHTML = res.data.map(function (msg) {
                    var time = _msgTime(msg.created_at || '');
                    var body = msg.body_highlighted || _esc(msg.body || '');
                    return '<div class="tc-search-result-item" data-msg-id="' + msg.id + '" onclick="TC_Messages.scrollTo(' + msg.id + '); TC_Search.clear();">'
                        + '<div class="tc-search-result-item__meta">'
                        + '<span class="tc-search-result-item__sender">' + _esc(msg.sender_name || '') + '</span>'
                        + '<span class="tc-search-result-item__time">' + time + '</span>'
                        + '</div>'
                        + '<div class="tc-search-result-item__body">' + body + '</div>'
                        + '</div>';
                }).join('');
            })
            .catch(function () {
                listEl.innerHTML = '<div class="p-3 text-muted text-center">Search failed. Please try again.</div>';
            });
    }

    function _clearResults() {
        var resultsEl = document.getElementById('tcMessageSearchResults');
        if (resultsEl) resultsEl.classList.add('d-none');
    }

    function clear() {
        _clearResults();
        var searchInput = document.getElementById('tcMessageSearchInput');
        if (searchInput) searchInput.value = '';
    }

    function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function _msgTime(dt) {
        if (!dt) return '';
        var d = new Date(dt);
        return d.toLocaleDateString() + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
    }

    return { init: init, clear: clear };
})();



/* ===== modules/team_chat_members.js ===== */

/**
 * team_chat_members.js
 * Members modal: refresh, add, remove, role change, leave.
 */
var TC_Members = (function () {
    'use strict';

    var _base   = window.TeamChatConfig ? window.TeamChatConfig.baseUrl   : '';
    var _userId = window.TeamChatConfig ? window.TeamChatConfig.userId     : 0;
    var _convId = 0;

    function init() {
        var listEl = document.getElementById('tcMessageList');
        _convId    = listEl ? (parseInt(listEl.dataset.convId) || 0) : 0;

        TC_Socket.on('member_joined', _onMemberJoined);
        TC_Socket.on('member_left',   _onMemberLeft);
        TC_Socket.on('presence',      _onPresence);
    }

    /* ── Refresh member list in modal ────────────────────── */
    function refresh() {
        if (!_convId) return;

        _get('/members/' + _convId)
            .then(function (res) {
                if (!res.success) return;
                var members = res.data || [];
                _renderList(members);
                _showAdminControls(members);
            });
    }

    /* ── Add member ──────────────────────────────────────── */
    function add(userId) {
        return _post('/members/add', {
            conversation_id: _convId,
            'user_ids[]':    [userId],
        }).then(function (res) {
            if (res.success) { refresh(); }
            else { alert(res.message || 'Could not add member.'); }
            return res;
        });
    }

    /* ── Remove member ───────────────────────────────────── */
    function remove(userId) {
        return _post('/members/remove', {
            conversation_id: _convId,
            user_id:         userId,
        }).then(function (res) {
            if (res.success) { refresh(); }
            else { alert(res.message || 'Could not remove member.'); }
            return res;
        });
    }

    /* ── Update role ─────────────────────────────────────── */
    function updateRole(userId, role) {
        return _post('/members/update_role', {
            conversation_id: _convId,
            user_id:         userId,
            role:            role,
        }).then(function (res) {
            if (res.success) { refresh(); }
            else { alert(res.message || 'Could not update role.'); }
            return res;
        });
    }

    /* ── Leave conversation ──────────────────────────────── */
    function leave() {
        return _post('/members/remove', {
            conversation_id: _convId,
            user_id:         _userId,
        }).then(function (res) {
            if (res.success) {
                window.location.href = _base.replace('/team_chat','') + '/team_chat';
            } else {
                alert(res.message || 'Could not leave conversation.');
            }
            return res;
        });
    }

    /* ── Socket handlers ─────────────────────────────────── */
    function _onMemberJoined(data) {
        var convId = parseInt(data.conversation_id || (data.member && data.member.conversation_id));
        if (convId !== _convId) return;
        refresh();
    }

    function _onMemberLeft(data) {
        var convId = parseInt(data.conversation_id);
        if (convId !== _convId) return;

        // If it was us, redirect
        if (parseInt(data.user_id) === parseInt(_userId)) {
            window.location.href = _base.replace('/team_chat','') + '/team_chat';
            return;
        }

        refresh();
    }

    function _onPresence(data) {
        var uid    = parseInt(data.user_id);
        var online = data.online;
        var convId = parseInt(data.conversation_id || 0);

        if (convId && convId !== _convId) return;

        // Update online dot in members modal
        var rows = document.querySelectorAll('.tc-member-row[data-user-id="' + uid + '"]');
        rows.forEach(function (row) {
            var statusDot = row.querySelector('.tc-avatar__status');
            if (statusDot) {
                statusDot.classList.toggle('tc-avatar__status--online', !!online);
                statusDot.classList.toggle('tc-avatar__status--offline', !online);
            }
        });

        // Update online dot in pane header (for direct conversations)
        var headerStatus = document.querySelector('.tc-pane__header-avatar .tc-avatar__status');
        if (headerStatus) {
            var convType = document.getElementById('tcMessageList')?.dataset.convType;
            if (convType === 'direct') {
                headerStatus.classList.toggle('tc-avatar__status--online', !!online);
            }
        }

        // Update status label
        var metaEl = document.getElementById('tcPaneMeta');
        if (metaEl) {
            var statusLabel = metaEl.querySelector('.tc-status-label');
            if (statusLabel) {
                statusLabel.textContent = online ? 'Online' : 'Offline';
            }
            var statusDot2 = metaEl.querySelector('.tc-status-dot');
            if (statusDot2) {
                statusDot2.className = 'tc-status-dot ' + (online ? 'tc-status--online' : 'tc-status--offline');
            }
        }
    }

    /* ── Render member list ──────────────────────────────── */
    function _renderList(members) {
        var listEl  = document.getElementById('tcMembersList');
        var countEl = document.getElementById('tcMembersCount');

        if (countEl) countEl.textContent = members.length;
        if (!listEl) return;

        listEl.innerHTML = members.map(function (m) {
            var isMe       = parseInt(m.user_id) === parseInt(_userId);
            var roleBadge  = { owner: 'bg-primary', admin: 'bg-info text-dark', member: 'bg-secondary' };
            var badge      = roleBadge[m.role] || 'bg-secondary';
            var onlineCls  = m.is_online ? 'tc-avatar__status--online' : 'tc-avatar__status--offline';
            var avatar     = m.avatar_url || '';

            return '<div class="tc-member-row" data-user-id="' + m.user_id + '" data-role="' + _esc(m.role) + '">'
                + '<div class="tc-member-row__avatar">'
                + (avatar ? '<img class="tc-avatar tc-avatar--sm" src="' + avatar + '" alt="">' : '<span class="tc-avatar tc-avatar--sm" style="background:#4f46e5;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;">' + _esc((m.fullname||'?')[0]) + '</span>')
                + '<span class="tc-avatar__status ' + onlineCls + '"></span>'
                + '</div>'
                + '<div class="tc-member-row__info">'
                + '<div class="tc-member-row__name">' + _esc(m.fullname) + (isMe ? ' <span class="text-muted small">(you)</span>' : '') + '</div>'
                + '<div class="tc-member-row__meta">' + _esc(m.emp_id || '') + '</div>'
                + '</div>'
                + '<div class="tc-member-row__role"><span class="badge ' + badge + '">' + _esc(m.role) + '</span></div>'
                + '<div class="tc-member-row__actions tc-member-actions d-none" data-user-id="' + m.user_id + '">'
                + (!isMe ? '<button class="btn btn-sm btn-outline-danger tc-remove-member" data-uid="' + m.user_id + '" data-name="' + _esc(m.fullname) + '" title="Remove"><i class="ti ti-user-minus"></i></button>' : '')
                + (isMe  ? '<button class="btn btn-sm btn-outline-warning tc-leave-conv" title="Leave"><i class="ti ti-door-exit me-1"></i>Leave</button>' : '')
                + '</div>'
                + '</div>';
        }).join('');
    }

    /* ── Show admin actions ──────────────────────────────── */
    function _showAdminControls(members) {
        var me = members.find(function (m) { return parseInt(m.user_id) === parseInt(_userId); });
        if (!me) return;

        var isAdmin = me.role === 'owner' || me.role === 'admin';

        // Show "Add Members" button
        var addToggle = document.getElementById('tcToggleAddMember');
        if (addToggle) addToggle.classList.toggle('d-none', !isAdmin);

        // Show action columns
        if (isAdmin) {
            document.querySelectorAll('.tc-member-actions').forEach(function (el) {
                el.classList.remove('d-none');
            });
        }
    }

    /* ── Utils ───────────────────────────────────────────── */
    function _get(endpoint) {
        return fetch(_base + endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); });
    }

    function _post(endpoint, body) {
        var fd = new FormData();
        Object.keys(body).forEach(function (k) {
            var val = body[k];
            if (Array.isArray(val)) { val.forEach(function (v) { fd.append(k, v); }); }
            else { fd.append(k, val); }
        });
        fd.append(window.TeamChatConfig.csrfTokenName, window.TeamChatConfig.csrfHash);
        return fetch(_base + endpoint, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(function (r) { return r.json(); });
    }

    function _esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    return { init: init, refresh: refresh, add: add, remove: remove, updateRole: updateRole, leave: leave };
})();



/* ===== modules/team_chat_notifications.js ===== */

/**
 * team_chat_notifications.js
 * In-app notification badge updates, sound, and polling fallback.
 */
var TC_Notifications = (function () {
    'use strict';

    var _base        = window.TeamChatConfig ? window.TeamChatConfig.baseUrl : '';
    var _userId      = window.TeamChatConfig ? window.TeamChatConfig.userId  : 0;
    var _pollTimer   = null;
    var _pollInterval= 15000; // 15 seconds polling fallback
    var _audio       = null;
    var _convId      = 0;

    function init() {
        var listEl = document.getElementById('tcMessageList');
        _convId = listEl ? (parseInt(listEl.dataset.convId) || 0) : 0;

        // Prepare notification sound
        _audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAA' +
            'EAAQAAgD4AAACAPAABACAAZGF0YUoGAAAA'); // minimal silent placeholder
        _audio.volume = 0.3;

        // Socket events
        TC_Socket.on('new_message', _onNewMessage);

        // Page visibility — refresh unread on tab focus
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) _fetchUnread();
        });
    }

    /* ── Start polling (fallback) ────────────────────────── */
    function startPolling() {
        if (TC_Socket.isConnected()) return; // WS handles it

        _pollTimer = setInterval(function () {
            _fetchUnread();
            if (TC_Socket.isPolling()) {
                _fetchQueuedEvents();
            }
        }, _pollInterval);
    }

    /* ── Fetch unread counts ─────────────────────────────── */
    function _fetchUnread() {
        if (!_userId) return;

        fetch(_base + '/unread', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) return;

            var counts = res.data.counts || {};
            var total  = res.data.total  || 0;

            // Update per-conversation unread in sidebar
            Object.keys(counts).forEach(function (cid) {
                TC_Conversations.updateUnread(parseInt(cid), counts[cid]);
            });

            // Update sidebar title badge
            _updateGlobalBadge(total);
        })
        .catch(function () {});
    }

    /* ── Fetch queued push events (polling fallback) ─────── */
    function _fetchQueuedEvents() {
        fetch(_base + '/unread', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) return;
            // Queue handling is server-side in Team_chat_push_service
            // Here we just re-trigger message list reload if needed
        })
        .catch(function () {});
    }

    /* ── Socket: new message ─────────────────────────────── */
    function _onNewMessage(data) {
        var msg    = data.message || {};
        var convId = parseInt(msg.conversation_id || data.conversation_id);
        var isMe   = parseInt(msg.sender_id) === parseInt(_userId);

        if (isMe) return; // Don't notify for own messages

        // Play sound if not in this conversation or tab is hidden
        if (convId !== _convId || document.hidden) {
            _playSound();
        }

        // Browser notification if page hidden
        if (document.hidden) {
            _browserNotify(msg);
        }
    }

    /* ── Global badge ────────────────────────────────────── */
    function _updateGlobalBadge(count) {
        // Update sidebar menu badge if present
        var badge = document.querySelector('.sidebar-menu .tc-badge, .nav-link[href*="team_chat"] .badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        }

        // Update page title
        var baseTitle = document.title.replace(/^\(\d+\+?\) /, '');
        document.title = count > 0 ? '(' + (count > 99 ? '99+' : count) + ') ' + baseTitle : baseTitle;
    }

    /* ── Sound ───────────────────────────────────────────── */
    function _playSound() {
        if (!_audio) return;
        _audio.currentTime = 0;
        _audio.play().catch(function () {}); // Ignore autoplay policy errors
    }

    /* ── Browser notification ────────────────────────────── */
    function _browserNotify(msg) {
        if (!('Notification' in window)) return;

        if (Notification.permission === 'default') {
            Notification.requestPermission();
            return;
        }

        if (Notification.permission !== 'granted') return;

        var title   = msg.sender_name || 'New message';
        var body    = (msg.body || '').replace(/<[^>]+>/g,'').substring(0, 80);
        var baseUrl = _base.replace(/\/team_chat(?:\/api)?$/, '');

        var notif = new Notification(title, {
            body:  body,
            icon:  baseUrl + '/modules/team_chat/assets/images/icon-192.png',
            tag:   'tc-msg-' + msg.id,
        });

        notif.onclick = function () {
            window.focus();
            if (msg.conversation_id) {
                TC_Conversations.open(msg.conversation_id);
            }
            notif.close();
        };

        setTimeout(function () { notif.close(); }, 5000);
    }

    return { init: init, startPolling: startPolling };
})();



/* ===== team_chat_init.js ===== */

/**
 * team_chat_init.js
 * Bootstrap entry point. Initialises all modules and
 * exposes the global TeamChat API used by views and partials.
 * Loaded last in the asset stack so all modules are available.
 */
(function (window, document) {
    'use strict';

    // Bail if config is missing (module not active on this page)
    if (!window.TeamChatConfig) return;

    const Cfg = window.TeamChatConfig;

    /* ─── Wait for DOM ─────────────────────────────────────── */
    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {

        const shell = document.getElementById('teamChatShell');
        if (!shell) return; // Chat page not loaded

        /* ─── Initialise modules in dependency order ─────── */
        TC_Socket.init(Cfg.socketUrl, Cfg.userId, Cfg.wsToken);
        TC_Conversations.init();
        TC_Messages.init();
        TC_Threads.init();
        TC_Input.init();
        TC_Upload.init();
        TC_Mentions.init();
        TC_Reactions.init();
        TC_Search.init();
        TC_Members.init();
        TC_Notifications.init();

        /* ─── Open active conversation if set ───────────── */
        if (Cfg.activeConversationId) {
            TC_Messages.scrollToBottom();
            TC_Conversations.markActive(Cfg.activeConversationId);
        }

        /* ─── Sidebar mobile toggle ──────────────────────── */
        const sidebar      = document.getElementById('tcSidebar');
        const backBtn      = document.getElementById('tcBackToSidebar');
        const threadPanel  = document.getElementById('tcThreadPanel');

        if (backBtn && sidebar) {
            backBtn.addEventListener('click', function () {
                sidebar.classList.remove('tc-sidebar--hidden');
                if (threadPanel) threadPanel.classList.remove('tc-thread-panel--open');
            });
        }

        /* ─── Global drag-drop on pane ───────────────────── */
        const pane = document.getElementById('tcPane');
        if (pane) {
            pane.addEventListener('dragover', function (e) {
                e.preventDefault();
                TC_Upload.showDropZone();
            });

            pane.addEventListener('dragleave', function (e) {
                if (!pane.contains(e.relatedTarget)) {
                    TC_Upload.hideDropZone();
                }
            });

            pane.addEventListener('drop', function (e) {
                e.preventDefault();
                TC_Upload.hideDropZone();
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    TC_Upload.handleFiles(e.dataTransfer.files);
                }
            });
        }

        /* ─── Sidebar search filter ─────────────────────── */
        const sidebarSearch = document.getElementById('tcSidebarSearch');
        const clearBtn      = document.getElementById('tcSidebarSearchClear');

        if (sidebarSearch) {
            sidebarSearch.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                TC_Conversations.filter(q);
                if (clearBtn) clearBtn.classList.toggle('d-none', !q);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                sidebarSearch.value = '';
                TC_Conversations.filter('');
                this.classList.add('d-none');
                sidebarSearch.focus();
            });
        }

        /* ─── Pins bar toggle ───────────────────────────── */
        const pinsToggle = document.getElementById('tcTogglePins');
        const pinsBar    = document.getElementById('tcPinsBar');
        const closePins  = document.getElementById('tcClosePinsBar');

        if (pinsToggle && pinsBar) {
            pinsToggle.addEventListener('click', function () {
                pinsBar.classList.toggle('d-none');
            });
        }

        if (closePins && pinsBar) {
            closePins.addEventListener('click', function () {
                pinsBar.classList.add('d-none');
            });
        }

        /* ─── Message search toggle ─────────────────────── */
        const searchBtn       = document.getElementById('tcSearchMessages');
        const searchBar       = document.getElementById('tcMessageSearchBar');
        const closeSearchBtn  = document.getElementById('tcCloseMessageSearch');

        if (searchBtn && searchBar) {
            searchBtn.addEventListener('click', function () {
                searchBar.classList.toggle('d-none');
                if (!searchBar.classList.contains('d-none')) {
                    document.getElementById('tcMessageSearchInput')?.focus();
                }
            });
        }

        if (closeSearchBtn && searchBar) {
            closeSearchBtn.addEventListener('click', function () {
                searchBar.classList.add('d-none');
                document.getElementById('tcMessageSearchResults')?.classList.add('d-none');
            });
        }

        /* ─── Mute toggle ───────────────────────────────── */
        const muteBtn = document.getElementById('tcMuteBtn');
        if (muteBtn) {
            muteBtn.addEventListener('click', function () {
                const convId = parseInt(this.dataset.convId);
                const muted  = this.dataset.muted === '1';
                TeamChat.muteConversation(convId, !muted);
            });
        }

        /* ─── Archive conversation ──────────────────────── */
        const archiveBtn = document.getElementById('tcArchiveConv');
        if (archiveBtn) {
            archiveBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const convId = parseInt(this.dataset.convId);
                if (confirm('Archive this conversation? It will be read-only and hidden from the sidebar.')) {
                    TeamChat.archiveConversation(convId);
                }
            });
        }

        /* ─── Rename conversation ───────────────────────── */
        const renameBtn = document.getElementById('tcRenameConv');
        if (renameBtn) {
            renameBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const currentName = document.getElementById('tcPaneName')?.textContent?.trim();
                const newName     = prompt('Rename conversation:', currentName);
                if (newName && newName.trim() && newName.trim() !== currentName) {
                    TeamChat.renameConversation(newName.trim());
                }
            });
        }

        /* ─── Unread polling (fallback when WS unavailable) */
        TC_Notifications.startPolling();

    }); // end ready()

    /* =========================================================
       GLOBAL TeamChat API
       Called by partials, modals, and PHP-rendered event handlers
    ========================================================= */
    window.TeamChat = {

        /* Conversations */
        openConversation: function (convId) {
            TC_Conversations.open(convId);
        },

        createDirect: function (targetUserId) {
            return TC_Conversations.createDirect(targetUserId);
        },

        createGroup: function (name, memberIds) {
            return TC_Conversations.createGroup(name, memberIds);
        },

        createChannel: function (name, desc, teamId, deptId) {
            return TC_Conversations.createChannel(name, desc, teamId, deptId);
        },

        archiveConversation: function (convId) {
            return TC_Conversations.archive(convId);
        },

        renameConversation: function (name) {
            return TC_Conversations.rename(name);
        },

        muteConversation: function (convId, mute) {
            return TC_Conversations.mute(convId, mute);
        },

        /* Messages */
        sendMessage: function (body, parentId) {
            return TC_Messages.send(body, parentId);
        },

        startEdit: function (msgId) {
            TC_Input.startEdit(msgId);
        },

        deleteMessage: function (msgId) {
            if (confirm('Delete this message?')) {
                TC_Messages.remove(msgId);
            }
        },

        scrollToMessage: function (msgId) {
            TC_Messages.scrollTo(msgId);
        },

        /* Threads */
        openThread: function (parentMsgId) {
            TC_Threads.open(parentMsgId);
        },

        /* Reactions */
        toggleReaction: function (msgId, emoji) {
            TC_Reactions.toggle(msgId, emoji);
        },

        /* Pins */
        pinMessage: function (msgId, convId) {
            TC_Messages.pin(msgId, convId);
        },

        unpinMessage: function (msgId, convId) {
            TC_Messages.unpin(msgId, convId);
        },

        /* Members */
        refreshMembersModal: function () {
            TC_Members.refresh();
        },

        addMember: function (userId) {
            return TC_Members.add(userId);
        },

        removeMember: function (userId) {
            TC_Members.remove(userId);
        },

        updateMemberRole: function (userId, role) {
            TC_Members.updateRole(userId, role);
        },

        leaveConversation: function () {
            TC_Members.leave();
        },

        /* Files */
        openFilePreview: function (attachmentId) {
            // Delegated to _file_preview_modal.php inline script
        },

        openFilePreviewData: function (data) {
            // Delegated to _file_preview_modal.php inline script
        },
    };

})(window, document);




/* ===== modal handlers ===== */
(function (window, document) {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    function esc(str) {
        var d = document.createElement('div');
        d.textContent = String(str || '');
        return d.innerHTML;
    }

    function initials(name) {
        if (!name) return '?';
        var words = String(name).trim().split(/\s+/);
        if (words.length >= 2) return (words[0][0] + words[words.length - 1][0]).toUpperCase();
        return String(name).substring(0, 2).toUpperCase();
    }

    function roleLabel(role) {
        var map = { admin: 'Admin', manager: 'Manager', teamlead: 'Team Lead', employee: 'Employee' };
        role = String(role || '').toLowerCase();
        return map[role] || (role ? role.charAt(0).toUpperCase() + role.slice(1) : '');
    }

    function apiBase() {
        return window.TeamChatConfig && window.TeamChatConfig.baseUrl ? window.TeamChatConfig.baseUrl : '';
    }

    function getJson(url) {
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); });
    }

    function makeSlug(str) {
        return String(str || '').toLowerCase().trim()
            .replace(/[^a-z0-9\s\-_]/g, '')
            .replace(/[\s\-_]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function initDmModal() {
        var modal = document.getElementById('tcNewDmModal');
        if (!modal) return;
        var selectedUserId = null;
        var searchTimer = null;
        var searchInput = document.getElementById('tcDmUserSearch');
        var resultsEl = document.getElementById('tcDmUserResults');
        var selectedEl = document.getElementById('tcDmSelectedUser');
        var selectedName = document.getElementById('tcDmSelectedName');
        var selectedMeta = document.getElementById('tcDmSelectedMeta');
        var selectedAvtr = document.getElementById('tcDmSelectedAvatar');
        var clearBtn = document.getElementById('tcDmClearSelected');
        var startBtn = document.getElementById('tcStartDmBtn');
        if (!searchInput || !resultsEl || !selectedEl || !startBtn) return;

        modal.addEventListener('hidden.bs.modal', function () {
            selectedUserId = null;
            searchInput.value = '';
            resultsEl.innerHTML = '';
            resultsEl.style.display = 'none';
            selectedEl.classList.add('d-none');
            startBtn.disabled = true;
        });
        modal.addEventListener('shown.bs.modal', function () { searchInput.focus(); });
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            var q = this.value.trim();
            if (q.length < 1) { resultsEl.innerHTML = ''; resultsEl.style.display = 'none'; return; }
            searchTimer = setTimeout(function () { searchUsers(q); }, 250);
        });

        function searchUsers(q) {
            var base = apiBase();
            if (!base) return;
            getJson(base + '/users/search?q=' + encodeURIComponent(q)).then(function (res) {
                if (!res.success || !res.data || !res.data.length) {
                    resultsEl.innerHTML = '<div class="tc-user-result tc-user-result--empty"><i class="ti ti-users-off me-1"></i> No users found</div>';
                    resultsEl.style.display = '';
                    return;
                }
                resultsEl.innerHTML = res.data.map(function (u) {
                    var avatarHtml = u.avatar_url
                        ? '<img src="' + esc(u.avatar_url) + '" alt="" onerror="this.style.display=\'none\'">'
                        : '<span class="tc-dm-initials">' + initials(u.fullname) + '</span>';
                    return '<div class="tc-user-result" data-user-id="' + u.id + '" data-name="' + esc(u.fullname) + '" data-emp-id="' + esc(u.emp_id || '') + '" data-role="' + esc(u.user_role || '') + '" data-avatar-url="' + esc(u.avatar_url || '') + '">'
                        + '<div class="tc-dm-avatar">' + avatarHtml + '</div>'
                        + '<div class="tc-dm-info"><div class="tc-user-result__name">' + esc(u.fullname) + '</div>'
                        + '<div class="tc-user-result__meta">' + (u.emp_id ? esc(u.emp_id) + ' · ' : '') + esc(roleLabel(u.user_role)) + '</div></div></div>';
                }).join('');
                resultsEl.style.display = '';
                resultsEl.querySelectorAll('.tc-user-result[data-user-id]').forEach(function (el) {
                    el.addEventListener('click', function () {
                        selectedUserId = parseInt(this.dataset.userId, 10);
                        searchInput.value = '';
                        resultsEl.innerHTML = '';
                        resultsEl.style.display = 'none';
                        selectedName.textContent = this.dataset.name || '';
                        selectedMeta.textContent = (this.dataset.empId ? this.dataset.empId + ' · ' : '') + roleLabel(this.dataset.role);
                        if (this.dataset.avatarUrl) { selectedAvtr.src = this.dataset.avatarUrl; selectedAvtr.style.display = ''; }
                        else { selectedAvtr.style.display = 'none'; }
                        selectedEl.classList.remove('d-none');
                        startBtn.disabled = false;
                    });
                });
            }).catch(function () {
                resultsEl.innerHTML = '<div class="tc-user-result tc-user-result--empty">Search failed. Please try again.</div>';
                resultsEl.style.display = '';
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                selectedUserId = null;
                selectedEl.classList.add('d-none');
                startBtn.disabled = true;
                searchInput.focus();
            });
        }
        startBtn.addEventListener('click', function () {
            if (!selectedUserId) return;
            startBtn.disabled = true;
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Opening...';
            if (window.TeamChat && typeof window.TeamChat.createDirect === 'function') {
                window.TeamChat.createDirect(selectedUserId).finally(function () {
                    var inst = bootstrap.Modal.getInstance(modal);
                    if (inst) inst.hide();
                    startBtn.disabled = false;
                    startBtn.innerHTML = '<i class="ti ti-send"></i> Start Conversation';
                });
            }
        });
    }

    function initGroupModal() {
        var modal = document.getElementById('tcNewGroupModal');
        if (!modal) return;
        var nameInput = document.getElementById('tcGroupName');
        var searchInput = document.getElementById('tcGroupUserSearch');
        var resultsEl = document.getElementById('tcGroupUserResults');
        var chipsEl = document.getElementById('tcGroupMemberChips');
        var hintEl = document.getElementById('tcGroupMemberHint');
        var countEl = document.getElementById('tcGroupMemberCount');
        var createBtn = document.getElementById('tcCreateGroupBtn');
        if (!nameInput || !searchInput || !resultsEl || !chipsEl || !createBtn) return;
        var selectedMembers = {};
        var searchTimer = null;
        modal.addEventListener('hidden.bs.modal', reset);
        nameInput.addEventListener('input', validate);
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            var q = this.value.trim();
            if (q.length < 1) { resultsEl.innerHTML = ''; return; }
            searchTimer = setTimeout(function () { searchUsers(q); }, 250);
        });
        function reset() {
            selectedMembers = {};
            nameInput.value = '';
            nameInput.classList.remove('is-invalid');
            searchInput.value = '';
            resultsEl.innerHTML = '';
            renderChips();
            validate();
        }
        function searchUsers(q) {
            var base = apiBase();
            if (!base) return;
            getJson(base + '/users/search?q=' + encodeURIComponent(q)).then(function (res) {
                if (!res.success || !res.data || !res.data.length) {
                    resultsEl.innerHTML = '<div class="tc-user-result tc-user-result--empty">No users found</div>';
                    return;
                }
                resultsEl.innerHTML = res.data.map(function (u) {
                    var already = selectedMembers[u.id] ? 'tc-user-result--selected' : '';
                    return '<div class="tc-user-result ' + already + '" data-user-id="' + u.id + '" data-name="' + esc(u.fullname) + '" data-emp-id="' + esc(u.emp_id || '') + '" data-avatar-url="' + esc(u.avatar_url || '') + '">'
                        + '<img class="tc-avatar--sm" src="' + esc(u.avatar_url || '') + '" alt="" onerror="this.style.display=\'none\'">'
                        + '<div><div class="tc-user-result__name">' + esc(u.fullname) + '</div><div class="tc-user-result__meta">' + esc(u.emp_id || '') + '</div></div>'
                        + (selectedMembers[u.id] ? '<i class="ti ti-check ms-auto" style="color:#10b981;font-size:18px;"></i>' : '') + '</div>';
                }).join('');
                resultsEl.querySelectorAll('.tc-user-result[data-user-id]').forEach(function (el) {
                    el.addEventListener('click', function () {
                        var uid = parseInt(this.dataset.userId, 10);
                        if (selectedMembers[uid]) delete selectedMembers[uid];
                        else selectedMembers[uid] = { id: uid, fullname: this.dataset.name, emp_id: this.dataset.empId, avatar_url: this.dataset.avatarUrl };
                        renderChips();
                        validate();
                        if (searchInput.value.trim()) searchUsers(searchInput.value.trim());
                        else resultsEl.innerHTML = '';
                    });
                });
            }).catch(function () { resultsEl.innerHTML = '<div class="tc-user-result tc-user-result--empty">Error searching users</div>'; });
        }
        function renderChips() {
            var keys = Object.keys(selectedMembers);
            if (!keys.length) { chipsEl.innerHTML = ''; if (hintEl) hintEl.style.display = ''; }
            else {
                if (hintEl) hintEl.style.display = 'none';
                chipsEl.innerHTML = keys.map(function (uid) { return '<span class="tc-member-chip" data-uid="' + uid + '">' + esc(selectedMembers[uid].fullname) + '<button type="button" class="tc-member-chip__remove" data-uid="' + uid + '"><i class="ti ti-x"></i></button></span>'; }).join('');
            }
            if (countEl) countEl.textContent = keys.length + ' member' + (keys.length !== 1 ? 's' : '') + ' selected';
            chipsEl.querySelectorAll('.tc-member-chip__remove').forEach(function (btn) {
                btn.addEventListener('click', function (e) { e.stopPropagation(); delete selectedMembers[this.dataset.uid]; renderChips(); validate(); if (searchInput.value.trim()) searchUsers(searchInput.value.trim()); });
            });
        }
        function validate() {
            var nameOk = nameInput.value.trim().length > 0;
            var membersOk = Object.keys(selectedMembers).length > 0;
            nameInput.classList.toggle('is-invalid', nameInput.value.length > 0 && !nameOk);
            createBtn.disabled = !(nameOk && membersOk);
        }
        createBtn.addEventListener('click', function () {
            var name = nameInput.value.trim();
            var userIds = Object.keys(selectedMembers).map(Number);
            if (!name || !userIds.length) return;
            createBtn.disabled = true;
            createBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
            window.TeamChat.createGroup(name, userIds).finally(function () {
                var inst = bootstrap.Modal.getInstance(modal);
                if (inst) inst.hide();
                createBtn.disabled = false;
                createBtn.innerHTML = '<i class="ti ti-users"></i> Create Group';
            });
        });
    }

    function initMembersModal() {
        var modal = document.getElementById('tcMembersModal');
        if (!modal) return;
        var addSection = document.getElementById('tcMembersAddSection');
        var addToggleBtn = document.getElementById('tcToggleAddMember');
        var searchInput = document.getElementById('tcAddMemberSearch');
        var resultsEl = document.getElementById('tcAddMemberResults');
        var searchTimer = null;
        modal.addEventListener('show.bs.modal', function () { if (window.TeamChat) window.TeamChat.refreshMembersModal(); });
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                var q = this.value.trim();
                if (q.length < 1) { resultsEl.innerHTML = ''; return; }
                searchTimer = setTimeout(function () { searchUsers(q); }, 250);
            });
        }
        if (addToggleBtn) {
            addToggleBtn.addEventListener('click', function () {
                var isOpen = !addSection.classList.contains('d-none');
                addSection.classList.toggle('d-none', isOpen);
                addToggleBtn.innerHTML = isOpen ? '<i class="ti ti-user-plus me-1"></i> Add Members' : '<i class="ti ti-x me-1"></i> Cancel';
                if (!isOpen && searchInput) searchInput.focus();
            });
        }
        function searchUsers(q) {
            var base = apiBase();
            if (!base) return;
            getJson(base + '/users/search?q=' + encodeURIComponent(q)).then(function (res) {
                if (!res.success || !res.data || !res.data.length) { resultsEl.innerHTML = '<div class="tc-user-result tc-user-result--empty">No users found</div>'; return; }
                resultsEl.innerHTML = res.data.map(function (u) {
                    return '<div class="tc-user-result" data-user-id="' + u.id + '" data-name="' + esc(u.fullname) + '">'
                        + '<img class="tc-avatar tc-avatar--sm" src="' + esc(u.avatar_url || '') + '" alt="" onerror="this.style.display=\'none\'">'
                        + '<div><div class="tc-user-result__name">' + esc(u.fullname) + '</div><div class="tc-user-result__meta">' + esc(u.emp_id || '') + '</div></div>'
                        + '<button class="btn btn-sm btn-primary ms-auto tc-add-single-member" data-uid="' + u.id + '"><i class="ti ti-plus"></i></button></div>';
                }).join('');
                resultsEl.querySelectorAll('.tc-add-single-member').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var uid = parseInt(this.dataset.uid, 10);
                        this.disabled = true;
                        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                        window.TeamChat.addMember(uid).then(function () { btn.innerHTML = '<i class="ti ti-check"></i>'; btn.classList.replace('btn-primary', 'btn-success'); })
                            .catch(function () { btn.disabled = false; btn.innerHTML = '<i class="ti ti-plus"></i>'; });
                    });
                });
            });
        }
        document.addEventListener('click', function (e) {
            var roleBtn = e.target.closest('.tc-role-change');
            if (roleBtn) { e.preventDefault(); window.TeamChat.updateMemberRole(parseInt(roleBtn.dataset.uid, 10), roleBtn.dataset.role); return; }
            var removeBtn = e.target.closest('.tc-remove-member');
            if (removeBtn && confirm('Remove ' + removeBtn.dataset.name + ' from this conversation?')) { window.TeamChat.removeMember(parseInt(removeBtn.dataset.uid, 10)); return; }
            var leaveBtn = e.target.closest('.tc-leave-conv');
            if (leaveBtn && confirm('Are you sure you want to leave this conversation?')) { window.TeamChat.leaveConversation(); }
        });
    }

    function initChannelModal() {
        var modal = document.getElementById('tcNewChannelModal');
        if (!modal) return;
        var nameInput = document.getElementById('tcChannelName');
        var descInput = document.getElementById('tcChannelDesc');
        var slugPreview = document.getElementById('tcChannelSlugPreview');
        var createBtn = document.getElementById('tcCreateChannelBtn');
        var teamField = document.getElementById('tcTeamField');
        var deptField = document.getElementById('tcDeptField');
        var teamSelect = document.getElementById('tcChannelTeam');
        var deptSelect = document.getElementById('tcChannelDept');
        if (!nameInput || !descInput || !createBtn) return;
        modal.addEventListener('hidden.bs.modal', reset);
        nameInput.addEventListener('input', function () {
            var slug = makeSlug(this.value);
            if (slugPreview) slugPreview.textContent = slug ? 'Slug: #' + slug : '';
            nameInput.classList.toggle('is-invalid', this.value.length > 0 && !slug);
            validate();
        });
        document.querySelectorAll('input[name="tcChannelScope"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (teamField) teamField.classList.add('d-none');
                if (deptField) deptField.classList.add('d-none');
                if (this.value === 'team' && teamField) teamField.classList.remove('d-none');
                if (this.value === 'dept' && deptField) deptField.classList.remove('d-none');
                validate();
            });
        });
        if (teamSelect) teamSelect.addEventListener('change', validate);
        if (deptSelect) deptSelect.addEventListener('change', validate);
        createBtn.addEventListener('click', function () {
            var name = nameInput.value.trim();
            var desc = descInput.value.trim();
            var scopeEl = document.querySelector('input[name="tcChannelScope"]:checked');
            var scope = scopeEl ? scopeEl.value : 'none';
            var teamId = scope === 'team' && teamSelect ? parseInt(teamSelect.value, 10) || 0 : 0;
            var deptId = scope === 'dept' && deptSelect ? parseInt(deptSelect.value, 10) || 0 : 0;
            if (!name) { nameInput.classList.add('is-invalid'); return; }
            createBtn.disabled = true;
            createBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
            window.TeamChat.createChannel(name, desc, teamId, deptId).finally(function () {
                var inst = bootstrap.Modal.getInstance(modal);
                if (inst) inst.hide();
                createBtn.disabled = false;
                createBtn.innerHTML = '<i class="ti ti-hash"></i> Create Channel';
            });
        });
        function reset() {
            nameInput.value = '';
            nameInput.classList.remove('is-invalid');
            descInput.value = '';
            if (slugPreview) slugPreview.textContent = '';
            createBtn.disabled = true;
            var scopeNone = document.querySelector('input[name="tcChannelScope"][value="none"]');
            if (scopeNone) scopeNone.checked = true;
            if (teamField) teamField.classList.add('d-none');
            if (deptField) deptField.classList.add('d-none');
            if (teamSelect) teamSelect.value = '';
            if (deptSelect) deptSelect.value = '';
        }
        function validate() {
            var nameOk = nameInput.value.trim().length > 0;
            var scopeEl = document.querySelector('input[name="tcChannelScope"]:checked');
            var scope = scopeEl ? scopeEl.value : 'none';
            var scopeOk = true;
            if (scope === 'team' && teamSelect && !teamSelect.value) scopeOk = false;
            if (scope === 'dept' && deptSelect && !deptSelect.value) scopeOk = false;
            createBtn.disabled = !(nameOk && scopeOk);
        }
    }

    function initFilePreview() {
        var modal = document.getElementById('tcFilePreviewModal');
        if (!modal) return;
        var loading = document.getElementById('tcPreviewLoading');
        var imageWrap = document.getElementById('tcPreviewImageWrap');
        var pdfWrap = document.getElementById('tcPreviewPdfWrap');
        var videoWrap = document.getElementById('tcPreviewVideoWrap');
        var audioWrap = document.getElementById('tcPreviewAudioWrap');
        var genericWrap = document.getElementById('tcPreviewGeneric');
        var previewImg = document.getElementById('tcPreviewImage');
        var previewIframe = document.getElementById('tcPreviewPdf');
        var previewVideo = document.getElementById('tcPreviewVideo');
        var previewAudio = document.getElementById('tcPreviewAudio');
        var dlBtn = document.getElementById('tcPreviewDownloadBtn');
        var openBtn = document.getElementById('tcPreviewOpenBtn');
        var filenameEl = document.getElementById('tcPreviewFilename');
        var filesizeEl = document.getElementById('tcPreviewFileSize');
        var uploaderEl = document.getElementById('tcPreviewUploaderInfo');
        var genericIcon = document.getElementById('tcPreviewGenericIcon');
        var genericName = document.getElementById('tcPreviewGenericName');
        var genericDl = document.getElementById('tcPreviewGenericDownload');
        if (!window.TeamChat) window.TeamChat = {};
        window.TeamChat.openFilePreview = function (attachmentId) {
            resetPreview();
            new bootstrap.Modal(modal).show();
            var attEl = document.querySelector('[data-attachment-id="' + attachmentId + '"]');
            if (!attEl) { showGeneric('Unknown file', '#', 'ti ti-file'); return; }
            renderPreview(attEl.dataset.fileUrl || (attEl.querySelector('a') ? attEl.querySelector('a').href : '#'), attEl.dataset.thumbUrl || '', attEl.dataset.origName || (attEl.querySelector('.tc-file-card__name') ? attEl.querySelector('.tc-file-card__name').textContent.trim() : 'File'), attEl.dataset.mimeType || '', attEl.dataset.fileSize || '', '');
        };
        window.TeamChat.openFilePreviewData = function (data) {
            resetPreview();
            new bootstrap.Modal(modal).show();
            renderPreview(data.file_url, data.thumbnail_url || '', data.original_name, data.mime_type, data.file_size_kb ? data.file_size_kb + ' KB' : '', data.uploader_name ? 'Shared by ' + data.uploader_name : '');
        };
        function renderPreview(fileUrl, thumbUrl, origName, mimeType, fileSize, uploaderInfo) {
            loading.classList.add('d-none');
            filenameEl.textContent = origName;
            filesizeEl.textContent = fileSize || '';
            uploaderEl.textContent = uploaderInfo || '';
            dlBtn.href = fileUrl; dlBtn.download = origName; openBtn.href = fileUrl;
            var imageTypes = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
            var videoTypes = ['video/mp4','video/webm','video/ogg'];
            var audioTypes = ['audio/mpeg','audio/ogg','audio/wav','audio/webm'];
            if (imageTypes.includes(mimeType)) { previewImg.src = thumbUrl || fileUrl; previewImg.onclick = function () { this.src = fileUrl; }; imageWrap.classList.remove('d-none'); }
            else if (mimeType === 'application/pdf') { previewIframe.src = fileUrl; pdfWrap.classList.remove('d-none'); }
            else if (videoTypes.includes(mimeType)) { previewVideo.querySelector('source').src = fileUrl; previewVideo.querySelector('source').type = mimeType; previewVideo.load(); videoWrap.classList.remove('d-none'); }
            else if (audioTypes.includes(mimeType)) { previewAudio.querySelector('source').src = fileUrl; previewAudio.querySelector('source').type = mimeType; previewAudio.load(); audioWrap.classList.remove('d-none'); }
            else showGeneric(origName, fileUrl, ({ 'application/pdf': 'ti ti-file-type-pdf', 'text/plain': 'ti ti-file-text', 'text/csv': 'ti ti-file-type-csv', 'application/zip': 'ti ti-file-zip' })[mimeType] || 'ti ti-file');
        }
        function showGeneric(name, url, iconClass) { genericIcon.className = iconClass + ' tc-file-preview-modal__generic-icon'; genericName.textContent = name; genericDl.href = url; genericDl.download = name; genericWrap.classList.remove('d-none'); }
        function resetPreview() {
            [imageWrap, pdfWrap, videoWrap, audioWrap, genericWrap].forEach(function (el) { if (el) el.classList.add('d-none'); });
            loading.classList.remove('d-none');
            if (previewImg) previewImg.src = '';
            if (previewIframe) previewIframe.src = '';
            if (previewVideo) previewVideo.pause();
            if (previewAudio) previewAudio.pause();
            filenameEl.textContent = 'Loading...'; filesizeEl.textContent = ''; uploaderEl.textContent = ''; dlBtn.href = '#'; openBtn.href = '#'; loading.classList.add('d-none');
        }
        modal.addEventListener('hidden.bs.modal', function () { if (previewVideo) previewVideo.pause(); if (previewAudio) previewAudio.pause(); if (previewIframe) previewIframe.src = ''; });
    }

    ready(function () {
        initDmModal();
        initGroupModal();
        initMembersModal();
        initChannelModal();
        initFilePreview();
    });
})(window, document);
