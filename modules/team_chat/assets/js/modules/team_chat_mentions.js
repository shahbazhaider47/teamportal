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