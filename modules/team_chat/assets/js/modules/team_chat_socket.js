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