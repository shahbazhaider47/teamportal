<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * _new_group_modal.php
 * Variables: $user_id
 */
?>

<div class="modal fade app-modal" id="tcNewGroupModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="app-modal-header">
                <div class="app-modal-header-left">
                    <div class="app-modal-icon app-modal-icon-warning">
                        <i class="ti ti-users"></i>
                    </div>
                    <div class="app-modal-title-wrap">
                        <div class="app-modal-title">New Group Conversation</div>
                        <div class="app-modal-subtitle">Create a group chat with multiple team members</div>
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
                                <label class="app-form-label app-form-label-required">Group Name</label>
                                <div class="app-form-input-wrap">
                                    <span class="app-form-input-prefix"><i class="ti ti-hash"></i></span>
                                    <input type="text"
                                           id="tcGroupName"
                                           class="app-form-control"
                                           placeholder="e.g. Project Alpha Team"
                                           maxlength="150"
                                           autocomplete="off">
                                </div>
                                <div class="invalid-feedback" style="font-size:12px;margin-top:6px;">Group name is required.</div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="app-form-section">
                    <div class="app-form-section-label">
                        <i class="ti ti-user-plus" style="font-size:12px;color:#5ebfbf;"></i>
                        Add Members
                    </div>
                    <div class="row g-3">
                        
                        <div class="col-md-12">
                            <div class="app-form-group">
                                <div class="tc-user-search-wrap" style="position:relative;">
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-search"></i></span>
                                        <input type="text"
                                               id="tcGroupUserSearch"
                                               class="app-form-control"
                                               placeholder="Search by name or employee ID…"
                                               autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <?php /* Search results */ ?>
                <div class="tc-user-results" id="tcGroupUserResults" style="margin-bottom:20px;"></div>

                <?php /* Selected members chips */ ?>
                <div class="tc-selected-members" id="tcGroupSelectedMembers" style="background:#f8fafc;border-radius:12px;padding:16px;">
                    <div class="tc-member-chips" id="tcGroupMemberChips" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;"></div>
                    <p class="tc-selected-members__hint" id="tcGroupMemberHint" style="font-size:12px;color:#94a3b8;margin:0;">
                        No members selected yet. Search and add members above.
                    </p>
                </div>

            </div>

            <div class="app-modal-footer">
                <div class="app-modal-footer-left">
                    <i class="ti ti-info-circle" style="font-size:14px;"></i>
                    <span id="tcGroupMemberCount" style="color:#5ebfbf;font-weight:500;">0 members selected</span>
                </div>
                <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="app-btn-submit" id="tcCreateGroupBtn" disabled>
                    <i class="ti ti-users"></i> Create Group
                </button>
            </div>

        </div>
    </div>
</div>

<style>
/* Additional styles for group chat components to match the app design */
.tc-user-results {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    max-height: 250px;
    overflow-y: auto;
}

.tc-user-result {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f1f5f9;
}

.tc-user-result:last-child {
    border-bottom: none;
}

.tc-user-result:hover {
    background: #f8fafc;
}

.tc-user-result--selected {
    background: #f0fdf4;
}

.tc-user-result--empty {
    justify-content: center;
    color: #94a3b8;
    font-size: 14px;
    cursor: default;
}

.tc-user-result--empty:hover {
    background: #ffffff;
}

.tc-user-result img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    background: #e2e8f0;
}

.tc-user-result__name {
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
    margin-bottom: 2px;
}

.tc-user-result__meta {
    font-size: 12px;
    color: #5ebfbf;
}

/* Member chips styling */
.tc-member-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #e2e8f0;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
}

.tc-member-chip__remove {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    margin: 0;
    display: inline-flex;
    align-items: center;
    font-size: 14px;
    transition: color 0.2s ease;
}

.tc-member-chip__remove:hover {
    color: #ef4444;
}

/* Avatar styles */
.tc-avatar--sm {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}
</style>

<script>
(function () {
    const modal       = document.getElementById('tcNewGroupModal');
    const nameInput   = document.getElementById('tcGroupName');
    const searchInput = document.getElementById('tcGroupUserSearch');
    const resultsEl   = document.getElementById('tcGroupUserResults');
    const chipsEl     = document.getElementById('tcGroupMemberChips');
    const hintEl      = document.getElementById('tcGroupMemberHint');
    const countEl     = document.getElementById('tcGroupMemberCount');
    const createBtn   = document.getElementById('tcCreateGroupBtn');

    if (!modal) return;

    let selectedMembers = {}; // { userId: { id, fullname, profile_image, emp_id } }
    let searchTimer = null;

    modal.addEventListener('hidden.bs.modal', _reset);

    function _reset() {
        selectedMembers = {};
        nameInput.value = '';
        nameInput.classList.remove('is-invalid');
        searchInput.value = '';
        resultsEl.innerHTML = '';
        _renderChips();
        _validate();
    }

    nameInput.addEventListener('input', _validate);

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 1) { 
            resultsEl.innerHTML = ''; 
            return; 
        }
        searchTimer = setTimeout(() => _searchUsers(q), 250);
    });

    function _searchUsers(q) {
        const baseUrl = window.TeamChatConfig?.baseUrl || '';
        fetch(baseUrl + '/users/search?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data.length) {
                resultsEl.innerHTML = '<div class="tc-user-result tc-user-result--empty">No users found</div>';
                return;
            }
            resultsEl.innerHTML = res.data.map(u => {
                const already = selectedMembers[u.id] ? 'tc-user-result--selected' : '';
                return `<div class="tc-user-result ${already}" data-user-id="${u.id}"
                             data-name="${_esc(u.fullname)}" 
                             data-emp-id="${_esc(u.emp_id || '')}"
                             data-avatar="${_esc(u.profile_image || '')}">
                    <img class="tc-avatar--sm"
                         src="${u.profile_image ? (baseUrl.replace('/team_chat','') + '/uploads/staff_profile_images/' + u.profile_image) : ''}"
                         alt="" 
                         onerror="this.style.display='none'">
                    <div>
                        <div class="tc-user-result__name">${_esc(u.fullname)}</div>
                        <div class="tc-user-result__meta">${_esc(u.emp_id || '')}</div>
                    </div>
                    ${selectedMembers[u.id] ? '<i class="ti ti-check ms-auto" style="color:#10b981;font-size:18px;"></i>' : ''}
                </div>`;
            }).join('');

            resultsEl.querySelectorAll('.tc-user-result[data-user-id]').forEach(el => {
                el.addEventListener('click', function () {
                    const uid = parseInt(this.dataset.userId);
                    if (selectedMembers[uid]) {
                        delete selectedMembers[uid];
                    } else {
                        selectedMembers[uid] = { 
                            id: uid, 
                            fullname: this.dataset.name, 
                            emp_id: this.dataset.empId,
                            profile_image: this.dataset.avatar 
                        };
                    }
                    _renderChips();
                    _validate();
                    // Re-search to update checkmarks
                    if (searchInput.value.trim()) {
                        _searchUsers(searchInput.value.trim());
                    } else {
                        resultsEl.innerHTML = '';
                    }
                });
            });
        })
        .catch(() => {
            resultsEl.innerHTML = '<div class="tc-user-result tc-user-result--empty">Error searching users</div>';
        });
    }

    function _renderChips() {
        const keys = Object.keys(selectedMembers);
        
        if (keys.length === 0) {
            chipsEl.innerHTML = '';
            if (hintEl) hintEl.style.display = '';
        } else {
            if (hintEl) hintEl.style.display = 'none';
            chipsEl.innerHTML = keys.map(uid => {
                const m = selectedMembers[uid];
                return `<span class="tc-member-chip" data-uid="${uid}">
                    ${_esc(m.fullname)}
                    <button type="button" class="tc-member-chip__remove" data-uid="${uid}">
                        <i class="ti ti-x"></i>
                    </button>
                </span>`;
            }).join('');
        }

        const memberCount = keys.length;
        if (countEl) {
            countEl.textContent = memberCount + ' member' + (memberCount !== 1 ? 's' : '') + ' selected';
        }

        chipsEl.querySelectorAll('.tc-member-chip__remove').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                delete selectedMembers[this.dataset.uid];
                _renderChips();
                _validate();
                // Refresh search results if there's an active search
                if (searchInput.value.trim()) {
                    _searchUsers(searchInput.value.trim());
                }
            });
        });
    }

    function _validate() {
        const nameOk    = nameInput.value.trim().length > 0;
        const membersOk = Object.keys(selectedMembers).length > 0;
        
        if (nameInput.value.length > 0 && !nameOk) {
            nameInput.classList.add('is-invalid');
        } else {
            nameInput.classList.remove('is-invalid');
        }
        
        createBtn.disabled = !(nameOk && membersOk);
    }

    if (createBtn) {
        createBtn.addEventListener('click', function () {
            const name    = nameInput.value.trim();
            const userIds = Object.keys(selectedMembers).map(Number);
            if (!name || !userIds.length) return;

            createBtn.disabled = true;
            createBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating…';

            if (typeof TeamChat !== 'undefined' && TeamChat.createGroup) {
                TeamChat.createGroup(name, userIds)
                    .finally(() => {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) modalInstance.hide();
                        createBtn.disabled = false;
                        createBtn.innerHTML = '<i class="ti ti-users"></i> Create Group';
                    });
            } else {
                // Fallback for demo/testing
                console.log('Creating group:', { name, userIds });
                setTimeout(() => {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) modalInstance.hide();
                    createBtn.disabled = false;
                    createBtn.innerHTML = '<i class="ti ti-users"></i> Create Group';
                }, 500);
            }
        });
    }

    function _esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = String(str);
        return d.innerHTML;
    }
})();
</script>