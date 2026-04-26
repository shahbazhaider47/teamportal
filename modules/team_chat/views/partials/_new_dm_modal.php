<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade app-modal" id="tcNewDmModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="app-modal-header">
                <div class="app-modal-header-left">
                    <div class="app-modal-icon app-modal-icon-info">
                        <i class="ti ti-edit"></i>
                    </div>
                    <div class="app-modal-title-wrap">
                        <div class="app-modal-title">New Direct Message</div>
                        <div class="app-modal-subtitle">Start a private conversation with a team member</div>
                    </div>
                </div>
                <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <div class="app-modal-body" style="max-height:calc(100vh - 220px);overflow-y:auto;">

                <div class="app-form-section">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="app-form-group">
                                <label class="app-form-label app-form-label-required">To</label>
                                <div class="tc-user-search-wrap">
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-search"></i></span>
                                        <input type="text"
                                               id="tcDmUserSearch"
                                               class="app-form-control"
                                               placeholder="Search by name or employee ID…"
                                               autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tc-user-results" id="tcDmUserResults"></div>

                <div class="tc-selected-user d-none" id="tcDmSelectedUser">
                    <div class="tc-selected-user__info">
                        <img class="tc-avatar" id="tcDmSelectedAvatar" src="" alt=""
                             style="width:40px;height:40px;border-radius:50%;object-fit:cover;background:#e2e8f0;">
                        <div>
                            <div class="tc-selected-user__name" id="tcDmSelectedName"></div>
                            <div class="tc-selected-user__meta" id="tcDmSelectedMeta"></div>
                        </div>
                    </div>
                    <button type="button" class="tc-icon-btn" id="tcDmClearSelected" title="Clear">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

            </div>

            <div class="app-modal-footer">
                <div class="app-modal-footer-left">
                    <i class="ti ti-info-circle" style="font-size:14px;"></i>
                    Search for a user by name or employee ID.
                </div>
                <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="app-btn-submit" id="tcStartDmBtn" disabled>
                    <i class="ti ti-send"></i> Start Conversation
                </button>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    let selectedUserId = null;
    let searchTimer    = null;

    const modal        = document.getElementById('tcNewDmModal');
    const searchInput  = document.getElementById('tcDmUserSearch');
    const resultsEl    = document.getElementById('tcDmUserResults');
    const selectedEl   = document.getElementById('tcDmSelectedUser');
    const selectedName = document.getElementById('tcDmSelectedName');
    const selectedMeta = document.getElementById('tcDmSelectedMeta');
    const selectedAvtr = document.getElementById('tcDmSelectedAvatar');
    const clearBtn     = document.getElementById('tcDmClearSelected');
    const startBtn     = document.getElementById('tcStartDmBtn');

    if (!modal) return;

    // ── Reset on modal close ─────────────────────────────────
    modal.addEventListener('hidden.bs.modal', function () {
        selectedUserId        = null;
        searchInput.value     = '';
        resultsEl.innerHTML   = '';
        resultsEl.style.display = 'none';
        selectedEl.classList.add('d-none');
        startBtn.disabled     = true;
    });

    modal.addEventListener('shown.bs.modal', function () {
        searchInput.focus();
    });

    // ── Live search ──────────────────────────────────────────
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();

        if (q.length < 1) {
            resultsEl.innerHTML    = '';
            resultsEl.style.display = 'none';
            return;
        }

        searchTimer = setTimeout(function () { _searchUsers(q); }, 250);
    });

    // ── Search function ──────────────────────────────────────
    function _searchUsers(q) {
// Wait for TeamChatConfig — it may not be defined yet if scripts load async
const baseUrl = (window.TeamChatConfig && window.TeamChatConfig.baseUrl)
    ? window.TeamChatConfig.baseUrl
    : '<?php echo site_url('team_chat'); ?>';

if (!baseUrl) {
    console.error('[TeamChat] baseUrl could not be determined.');
    return;
}

        fetch(baseUrl + '/users/search?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success || !res.data || !res.data.length) {
                resultsEl.innerHTML     = '<div class="tc-user-result tc-user-result--empty"><i class="ti ti-users-off me-1"></i> No users found</div>';
                resultsEl.style.display = '';
                return;
            }

            resultsEl.innerHTML = res.data.map(function (u) {
                // avatar_url is already built by the API controller
                const avatarHtml = u.avatar_url
                    ? '<img src="' + _esc(u.avatar_url) + '" alt="" onerror="this.style.display=\'none\'">'
                    : '<span class="tc-dm-initials">' + _initials(u.fullname) + '</span>';

                return '<div class="tc-user-result" '
                    + 'data-user-id="'   + u.id           + '" '
                    + 'data-name="'      + _esc(u.fullname)    + '" '
                    + 'data-emp-id="'    + _esc(u.emp_id || '') + '" '
                    + 'data-role="'      + _esc(u.user_role || '') + '" '
                    + 'data-avatar-url="'+ _esc(u.avatar_url || '') + '">'
                    + '<div class="tc-dm-avatar">' + avatarHtml + '</div>'
                    + '<div class="tc-dm-info">'
                    +   '<div class="tc-user-result__name">' + _esc(u.fullname) + '</div>'
                    +   '<div class="tc-user-result__meta">'
                    +     (u.emp_id ? _esc(u.emp_id) + ' · ' : '')
                    +     _esc(_roleLabel(u.user_role))
                    +   '</div>'
                    + '</div>'
                    + '</div>';
            }).join('');

            resultsEl.style.display = '';

            // Bind click on each result
            resultsEl.querySelectorAll('.tc-user-result[data-user-id]').forEach(function (el) {
                el.addEventListener('click', function () {
                    _selectUser(
                        parseInt(this.dataset.userId),
                        this.dataset.name,
                        this.dataset.empId,
                        this.dataset.role,
                        this.dataset.avatarUrl
                    );
                });
            });
        })
        .catch(function (err) {
            console.error('[TeamChat DM search] Error:', err);
            resultsEl.innerHTML     = '<div class="tc-user-result tc-user-result--empty">Search failed. Please try again.</div>';
            resultsEl.style.display = '';
        });
    }

    // ── Select a user ────────────────────────────────────────
    function _selectUser(userId, name, empId, role, avatarUrl) {
        selectedUserId = userId;

        // Clear search
        searchInput.value       = '';
        resultsEl.innerHTML     = '';
        resultsEl.style.display = 'none';

        // Populate selected preview
        selectedName.textContent = name;
        selectedMeta.textContent = (empId ? empId + ' · ' : '') + _roleLabel(role);

        if (avatarUrl) {
            selectedAvtr.src         = avatarUrl;
            selectedAvtr.style.display = '';
        } else {
            selectedAvtr.style.display = 'none';
        }

        selectedEl.classList.remove('d-none');
        startBtn.disabled = false;
    }

    // ── Clear selection ──────────────────────────────────────
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            selectedUserId = null;
            selectedEl.classList.add('d-none');
            startBtn.disabled = true;
            searchInput.focus();
        });
    }

    // ── Start conversation ───────────────────────────────────
    if (startBtn) {
        startBtn.addEventListener('click', function () {
            if (!selectedUserId) return;

            startBtn.disabled  = true;
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Opening…';

            if (typeof TeamChat !== 'undefined' && typeof TeamChat.createDirect === 'function') {
                TeamChat.createDirect(selectedUserId)
                    .finally(function () {
                        const inst = bootstrap.Modal.getInstance(modal);
                        if (inst) inst.hide();
                        startBtn.disabled  = false;
                        startBtn.innerHTML = '<i class="ti ti-send"></i> Start Conversation';
                    });
            } else {
                console.warn('[TeamChat] TeamChat.createDirect not available.');
                startBtn.disabled  = false;
                startBtn.innerHTML = '<i class="ti ti-send"></i> Start Conversation';
            }
        });
    }

    // ── Helpers ──────────────────────────────────────────────
    function _esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = String(str);
        return d.innerHTML;
    }

    function _initials(name) {
        if (!name) return '?';
        const words = name.trim().split(/\s+/);
        if (words.length >= 2) return (words[0][0] + words[words.length - 1][0]).toUpperCase();
        return name.substring(0, 2).toUpperCase();
    }

    function _roleLabel(role) {
        const map = {
            admin:    'Admin',
            manager:  'Manager',
            teamlead: 'Team Lead',
            employee: 'Employee',
        };
        return map[(role || '').toLowerCase()] || (role ? role.charAt(0).toUpperCase() + role.slice(1) : '');
    }

})();
</script>

<style>
.tc-user-results {
    display: none;
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    max-height: 260px;
    overflow-y: auto;
    margin-bottom: 12px;
}

.tc-user-result {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    cursor: pointer;
    transition: background 0.15s ease;
    border-bottom: 1px solid #f1f5f9;
}

.tc-user-result:last-child  { border-bottom: none; }
.tc-user-result:hover       { background: #f8fafc; }

.tc-user-result--empty {
    justify-content: center;
    color: #94a3b8;
    font-size: 13.5px;
    cursor: default;
    padding: 16px;
}

.tc-user-result--empty:hover { background: transparent; }

.tc-dm-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tc-dm-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tc-dm-initials {
    font-size: 13px;
    font-weight: 600;
    color: #4f46e5;
    background: #eef0fd;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.tc-dm-info { flex: 1; min-width: 0; }

.tc-user-result__name {
    font-weight: 600;
    color: #1e293b;
    font-size: 13.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tc-user-result__meta {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 1px;
}

.tc-selected-user {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 4px;
}

.tc-selected-user__info { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
.tc-selected-user__name { font-weight: 600; color: #1e293b; font-size: 13.5px; }
.tc-selected-user__meta { font-size: 11.5px; color: #64748b; margin-top: 1px; }
</style>