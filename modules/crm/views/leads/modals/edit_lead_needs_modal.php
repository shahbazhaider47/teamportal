<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$leadId = (int)($lead['id'] ?? 0);
?>

<div class="modal fade app-modal" id="editLeadNeedsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="post" action="<?= site_url('crm/leads/update_needs/' . $leadId) ?>" id="leadEditForm">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-primary">
                            <i class="ti ti-edit"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title">Edit Requirements</div>
                            <div class="app-modal-subtitle">Update the lead requirements, criteria and their needs</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body modal-body">

                    <input type="hidden" name="id" value="<?= $leadId; ?>">
                    
                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-notes" style="font-size:12px;color:#5ebfbf;"></i>
                            Strategy
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_practice_needs">Practice Needs</label>
                                    <textarea name="practice_needs" id="edit_practice_needs" class="app-form-control" rows="3"
                                              placeholder="Practice needs"><?= html_escape($lead['practice_needs'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_pain_points">Pain Points</label>
                                    <textarea name="pain_points" id="edit_pain_points" class="app-form-control" rows="3"
                                              placeholder="Pain points"><?= html_escape($lead['pain_points'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_decision_criteria">Decision Criteria</label>
                                    <textarea name="decision_criteria" id="edit_decision_criteria" class="app-form-control" rows="3"
                                              placeholder="Decision criteria"><?= html_escape($lead['decision_criteria'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_key_decision_makers">Key Decision Makers</label>
                                    <textarea name="key_decision_makers" id="edit_key_decision_makers" class="app-form-control" rows="3"
                                              placeholder="Key decision makers"><?= html_escape($lead['key_decision_makers'] ?? '') ?></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="app-modal-footer">
                    <div class="app-modal-footer-left">
                        <i class="ti ti-info-circle" style="font-size:14px;"></i>
                        All changes will be saved to this lead record.
                    </div>
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-device-floppy"></i>Update
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>