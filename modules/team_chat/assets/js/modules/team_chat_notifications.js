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
        var baseUrl = _base.replace('/team_chat_api', '');

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