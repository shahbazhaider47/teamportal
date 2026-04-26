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