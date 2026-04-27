<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
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
