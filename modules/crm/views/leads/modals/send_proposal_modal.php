<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    
.app-proposal-item{
    display:flex;
    gap:10px;
    padding:12px;
    border:1px solid #e5e7eb;
    border-radius:6px;
    margin-bottom:8px;
    cursor:pointer;
}
.app-proposal-item:hover{
    background:#f8fafc;
}
.app-proposal-title{
    font-weight:600;
}
.app-proposal-meta{
    font-size:12px;
    color:#64748b;
}
.app-empty-state{
    text-align:center;
    padding:30px 10px;
}
.app-empty-state i{
    font-size:32px;
    color:#94a3b8;
}
.app-empty-title{
    font-weight:600;
    margin-top:10px;
}
.app-empty-subtitle{
    color:#64748b;
    font-size:13px;
}    
</style>
<?php 
$leadId = (int)($lead['id'] ?? 0);
$proposals = $proposals ?? []; // pass from controller
?>

<div class="modal fade app-modal" id="sendPoposalModal" tabindex="-1"
     aria-labelledby="sendPoposalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form class="app-form"
                  action="<?= site_url('crm/proposals/send_to_lead/' . $leadId) ?>"
                  method="post">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-teal">
                            <i class="ti ti-file-invoice"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title" id="sendPoposalModalLabel">
                                Send Proposal
                            </div>
                            <div class="app-modal-subtitle">
                                Select and send proposal to this lead
                            </div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body">

                    <div class="app-form-section">

                        <?php if (!empty($proposals)): ?>

                            <div class="app-form-group">
                                <label class="app-form-label">
                                    Select Proposal
                                </label>

                                <div class="app-proposal-list">

                                    <?php foreach ($proposals as $proposal): ?>
                                        <label class="app-proposal-item">
                                            <input type="radio"
                                                   name="proposal_id"
                                                   value="<?= $proposal['id'] ?>"
                                                   required>

                                            <div class="app-proposal-content">
                                                <div class="app-proposal-title">
                                                    <?= html_escape($proposal['title']) ?>
                                                </div>

                                                <div class="app-proposal-meta">
                                                    Amount: $<?= number_format($proposal['total'] ?? 0, 2) ?>
                                                    •
                                                    Created: <?= date('M d, Y', strtotime($proposal['created_at'])) ?>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>

                                </div>
                            </div>

                        <?php else: ?>

                            <div class="app-empty-state">
                                <i class="ti ti-file-x"></i>
                                <div class="app-empty-title">
                                    No Proposal Created Yet
                                </div>
                                <div class="app-empty-subtitle">
                                    Create a proposal before sending to this lead
                                </div>

                                <a href="<?= site_url('crm/proposals/create?lead_id=' . $leadId) ?>"
                                   class="app-btn-submit mt-2">
                                    <i class="ti ti-plus"></i>
                                    Create Proposal
                                </a>
                            </div>

                        <?php endif; ?>

                    </div>

                    <?php if (!empty($proposals)): ?>

                    <div class="app-form-section">
                        <div class="app-form-group">
                            <label class="app-form-label">
                                Message (Optional)
                            </label>
                            <textarea name="message"
                                      class="app-form-control"
                                      rows="4"
                                      placeholder="Add message to include with proposal"></textarea>
                        </div>
                    </div>

                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="app-form-group">
                            <label class="app-form-checkbox">
                                <input type="checkbox" name="send_email" value="1" checked>
                                Send proposal via email to lead
                            </label>
                        </div>
                    </div>

                    <?php endif; ?>

                </div>

                <div class="app-modal-footer">
                    <div class="app-modal-footer-left">
                        <i class="ti ti-info-circle"></i>
                        Proposal will be logged in lead activity.
                    </div>

                    <button type="button"
                            class="app-btn-cancel"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <?php if (!empty($proposals)): ?>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-send"></i>
                        Send Proposal
                    </button>
                    <?php endif; ?>

                </div>

            </form>
        </div>
    </div>
</div>