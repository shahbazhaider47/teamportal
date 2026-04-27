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