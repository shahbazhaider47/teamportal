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