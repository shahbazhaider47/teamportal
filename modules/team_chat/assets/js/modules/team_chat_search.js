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