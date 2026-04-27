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
