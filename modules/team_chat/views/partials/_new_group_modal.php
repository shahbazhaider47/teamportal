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
