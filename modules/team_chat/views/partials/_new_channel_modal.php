<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
.app-modal .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18), 0 4px 16px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}
.app-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px 16px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    gap: 12px;
}
.app-modal-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.app-modal-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.app-modal-icon-primary  { background: #eff6ff; color: #1d4ed8; }
.app-modal-icon-teal     { background: #f0fdfa; color: #056464; }
.app-modal-icon-success  { background: #f0fdf4; color: #16a34a; }
.app-modal-icon-warning  { background: #fffbeb; color: #d97706; }
.app-modal-icon-danger   { background: #fef2f2; color: #dc2626; }
.app-modal-icon-purple   { background: #f5f3ff; color: #7c3aed; }
.app-modal-icon-slate    { background: #f8fafc; color: #475569;  }

.app-modal-title-wrap {}

.app-modal-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    line-height: 1.3;
    letter-spacing: -0.2px;
}

.app-modal-subtitle {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 1px;
    font-weight: 400;
}

.app-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    cursor: pointer;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
    padding: 0;
    line-height: 1;
}
.app-modal-close:hover { background: #fef2f2; color: #dc2626; }

.app-modal-body {
    padding: 22px 24px;
    background: #ffffff;
}

.app-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.app-modal-footer-left {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    color: #94a3b8;
}

.app-form-section {
    margin-bottom: 20px;
}

.app-form-section:last-child {
    margin-bottom: 0;
}

.app-form-section-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.9px;
    color: #94a3b8;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.app-form-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #f1f5f9;
}

.app-form-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 20px 0;
}

.app-form-label {
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 5px;
    display: block;
    letter-spacing: 0.1px;
}

.app-form-label-required::after {
    content: ' *';
    color: #dc2626;
}

.app-form-hint {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
    line-height: 1.4;
}

.app-form-control {
    display: block;
    width: 100%;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 500;
    color: #0f172a;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    line-height: 1.5;
    font-family: inherit;
    appearance: none;
    -webkit-appearance: none;
}

.app-form-control::placeholder { color: #c0cad8; font-weight: 400; }

.app-form-control:focus {
    border-color: #056464;
    box-shadow: 0 0 0 3px rgba(5, 100, 100, 0.10);
}

.app-form-control:disabled {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
}

.app-form-control.is-invalid {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.08);
}

.app-form-select-wrap {
    position: relative;
}
.app-form-select-wrap::after {
    content: '';
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 0; height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid #94a3b8;
    pointer-events: none;
}
.app-form-select-wrap .app-form-control { padding-right: 32px; cursor: pointer; }

.app-form-input-wrap {
    position: relative;
}
.app-form-input-prefix,
.app-form-input-suffix {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 13px;
    pointer-events: none;
    user-select: none;
}
.app-form-input-prefix {
    left: 0;
    width: 36px;
    border-right: 1.5px solid #f1f5f9;
    height: calc(100% - 4px);
    top: 2px;
    transform: none;
}
.app-form-input-suffix {
    right: 12px;
}
.app-form-input-wrap .app-form-control { padding-left: 44px; }
.app-form-input-wrap.suffix .app-form-control { padding-left: 12px; padding-right: 36px; }

.app-form-group {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.app-form-computed {
    padding: 8px 12px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1.5px solid #f1f5f9;
    font-size: 14px;
    font-weight: 700;
    color: #056464;
    letter-spacing: -0.3px;
}

.app-btn-cancel {
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
    font-family: inherit;
}
.app-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }

.app-btn-submit {
    padding: 7px 20px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    background: #056464;
    color: #ffffff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.15s, box-shadow 0.15s;
    font-family: inherit;
}
.app-btn-submit:hover {
    background: #044848;
    box-shadow: 0 4px 12px rgba(5, 100, 100, 0.25);
}
.app-btn-submit:active { transform: scale(0.98); }
.app-btn-submit i { font-size: 15px; }

.app-btn-submit-danger { background: #dc2626; }
.app-btn-submit-danger:hover { background: #b91c1c; box-shadow: 0 4px 12px rgba(220,38,38,0.25); }


    
</style>
<div class="modal fade app-modal" id="tcNewChannelModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="app-modal-header">
                <div class="app-modal-header-left">
                    <div class="app-modal-icon app-modal-icon-primary">
                        <i class="ti ti-hash"></i>
                    </div>
                    <div class="app-modal-title-wrap">
                        <div class="app-modal-title">New Channel</div>
                        <div class="app-modal-subtitle">Create a new team communication channel</div>
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
                                <label class="app-form-label app-form-label-required">Channel Name</label>
                                <div class="app-form-input-wrap">
                                    <span class="app-form-input-prefix"><i class="ti ti-hash"></i></span>
                                    <input type="text"
                                           id="tcChannelName"
                                           class="app-form-control"
                                           placeholder="e.g. announcements"
                                           maxlength="150"
                                           autocomplete="off">
                                </div>
                                <div class="tc-slug-preview" id="tcChannelSlugPreview" style="font-size:12px;color:#5ebfbf;margin-top:6px;"></div>
                                <div class="invalid-feedback" style="font-size:12px;">Channel name is required.</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="app-form-group">
                                <label class="app-form-label">Description <span class="text-muted small">(optional)</span></label>
                                <textarea id="tcChannelDesc"
                                          class="app-form-control"
                                          rows="2"
                                          placeholder="What is this channel for?"
                                          maxlength="500"></textarea>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="app-form-section">
                    <div class="app-form-section-label">
                        <i class="ti ti-adjustments" style="font-size:12px;color:#5ebfbf;"></i>
                        Scope Settings
                    </div>
                    <div class="row g-3">

                        <div class="col-md-12">
                            <div class="app-form-group">
                                <label class="app-form-label">Scope</label>
                                <div class="tc-channel-scope" style="display:flex;flex-direction:column;gap:10px;">

                                    <div class="app-radio-group">
                                        <label class="app-radio">
                                            <input type="radio" name="tcChannelScope" value="none" checked>
                                            <span class="app-radio-checkmark"></span>
                                            <span class="app-radio-label">No specific scope — I'll add members manually</span>
                                        </label>
                                    </div>

                                    <?php if (!empty($teams)): ?>
                                    <div class="app-radio-group">
                                        <label class="app-radio">
                                            <input type="radio" name="tcChannelScope" value="team">
                                            <span class="app-radio-checkmark"></span>
                                            <span class="app-radio-label">Team</span>
                                        </label>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($departments)): ?>
                                    <div class="app-radio-group">
                                        <label class="app-radio">
                                            <input type="radio" name="tcChannelScope" value="dept">
                                            <span class="app-radio-checkmark"></span>
                                            <span class="app-radio-label">Department</span>
                                        </label>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>

                        <?php if (!empty($teams)): ?>
                        <div class="col-md-12 tc-scope-field d-none" id="tcTeamField">
                            <div class="app-form-group">
                                <label class="app-form-label">Select Team</label>
                                <div class="app-form-select-wrap">
                                    <select id="tcChannelTeam" class="app-form-control">
                                        <option value="">— Choose team —</option>
                                        <?php foreach ($teams as $team): ?>
                                        <option value="<?php echo (int)$team['id']; ?>">
                                            <?php echo htmlspecialchars($team['name'], ENT_QUOTES); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="font-size:12px;color:#475569;margin-top:6px;">All members of this team will be added automatically.</div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($departments)): ?>
                        <div class="col-md-12 tc-scope-field d-none" id="tcDeptField">
                            <div class="app-form-group">
                                <label class="app-form-label">Select Department</label>
                                <div class="app-form-select-wrap">
                                    <select id="tcChannelDept" class="app-form-control">
                                        <option value="">— Choose department —</option>
                                        <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo (int)$dept['id']; ?>">
                                            <?php echo htmlspecialchars($dept['name'], ENT_QUOTES); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="font-size:12px;color:#475569;margin-top:6px;">All members of this department will be added automatically.</div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>

            <div class="app-modal-footer">
                <div class="app-modal-footer-left">
                    <i class="ti ti-info-circle" style="font-size:14px;"></i>
                    Required fields are marked with an asterisk (*).
                </div>
                <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="app-btn-submit" id="tcCreateChannelBtn" disabled>
                    <i class="ti ti-hash"></i> Create Channel
                </button>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    const modal       = document.getElementById('tcNewChannelModal');
    const nameInput   = document.getElementById('tcChannelName');
    const descInput   = document.getElementById('tcChannelDesc');
    const slugPreview = document.getElementById('tcChannelSlugPreview');
    const createBtn   = document.getElementById('tcCreateChannelBtn');
    const teamField   = document.getElementById('tcTeamField');
    const deptField   = document.getElementById('tcDeptField');
    const teamSelect  = document.getElementById('tcChannelTeam');
    const deptSelect  = document.getElementById('tcChannelDept');

    if (!modal) return;

    modal.addEventListener('hidden.bs.modal', _reset);

    function _reset() {
        nameInput.value = '';
        nameInput.classList.remove('is-invalid');
        descInput.value = '';
        slugPreview.textContent = '';
        createBtn.disabled = true;
        const scopeNone = document.querySelector('input[name="tcChannelScope"][value="none"]');
        if (scopeNone) scopeNone.checked = true;
        if (teamField) teamField.classList.add('d-none');
        if (deptField) deptField.classList.add('d-none');
        if (teamSelect) teamSelect.value = '';
        if (deptSelect) deptSelect.value = '';
    }

    nameInput.addEventListener('input', function () {
        const slug = _makeSlug(this.value);
        slugPreview.textContent = slug ? 'Slug: #' + slug : '';
        nameInput.classList.toggle('is-invalid', this.value.length > 0 && !slug);
        _validate();
    });

    document.querySelectorAll('input[name="tcChannelScope"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (teamField) teamField.classList.add('d-none');
            if (deptField) deptField.classList.add('d-none');
            if (this.value === 'team'  && teamField) teamField.classList.remove('d-none');
            if (this.value === 'dept'  && deptField) deptField.classList.remove('d-none');
            _validate();
        });
    });

    function _validate() {
        const nameOk  = nameInput.value.trim().length > 0;
        const scope   = document.querySelector('input[name="tcChannelScope"]:checked')?.value;
        let scopeOk   = true;
        if (scope === 'team' && teamSelect && !teamSelect.value) scopeOk = false;
        if (scope === 'dept' && deptSelect && !deptSelect.value) scopeOk = false;
        createBtn.disabled = !(nameOk && scopeOk);
    }

    if (teamSelect) teamSelect.addEventListener('change', _validate);
    if (deptSelect) deptSelect.addEventListener('change', _validate);

    createBtn.addEventListener('click', function () {
        const name   = nameInput.value.trim();
        const desc   = descInput.value.trim();
        const scope  = document.querySelector('input[name="tcChannelScope"]:checked')?.value;
        const teamId = (scope === 'team'  && teamSelect) ? parseInt(teamSelect.value) || 0 : 0;
        const deptId = (scope === 'dept'  && deptSelect) ? parseInt(deptSelect.value) || 0 : 0;

        if (!name) { nameInput.classList.add('is-invalid'); return; }

        createBtn.disabled = true;
        createBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating…';

        // Assuming TeamChat.createChannel is defined elsewhere
        if (typeof TeamChat !== 'undefined' && TeamChat.createChannel) {
            TeamChat.createChannel(name, desc, teamId, deptId)
                .finally(() => {
                    bootstrap.Modal.getInstance(modal).hide();
                    createBtn.disabled = false;
                    createBtn.innerHTML = '<i class="ti ti-hash"></i> Create Channel';
                });
        } else {
            // Fallback or simulate for demo
            console.log('Channel created:', { name, desc, teamId, deptId });
            setTimeout(() => {
                bootstrap.Modal.getInstance(modal).hide();
                createBtn.disabled = false;
                createBtn.innerHTML = '<i class="ti ti-hash"></i> Create Channel';
            }, 500);
        }
    });

    function _makeSlug(str) {
        return str.toLowerCase().trim()
            .replace(/[^a-z0-9\s\-_]/g, '')
            .replace(/[\s\-_]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
})();
</script>