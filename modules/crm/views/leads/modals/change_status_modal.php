<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$leadId        = (int)($lead['id'] ?? 0);
$currentStatus = (string)($lead['lead_status'] ?? 'new');
$statuses = [
    'new'             => 'New',
    'contacted'       => 'Contacted',
    'qualified'       => 'Qualified',
    'proposal_sent'   => 'Proposal Sent',
    'negotiation'     => 'Negotiation',
    'demo_scheduled'  => 'Demo Scheduled',
    'demo_completed'  => 'Demo Completed',
    'contract_sent'   => 'Contract Sent',
    'contract_signed' => 'Contract Signed',
    'lost'            => 'Lost',
    'disqualified'    => 'Disqualified',
];
?>

<div class="modal fade app-modal" id="changeStatusModal" tabindex="-1"
     aria-labelledby="changeStatusModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= site_url('crm/leads/change_status/' . $leadId) ?>" method="post">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-warning">
                            <i class="ti ti-arrows-exchange"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title" id="changeStatusModalLabel">Change Lead Status</div>
                            <div class="app-modal-subtitle">Update pipeline status, and lead quality</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body">

                    <div class="app-form-section">

                        <div class="app-form-group">
                            <label class="app-form-label app-form-label-required" for="leadStatusSelect">Lead Status</label>
                            <div class="app-form-select-wrap">
                                <select name="lead_status" id="leadStatusSelect" class="app-form-control" required>
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $currentStatus === $value ? 'selected' : '' ?>>
                                            <?= html_escape($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="lossReasonWrap" style="display:none;margin-top:14px;">
                            <div class="app-form-group">
                                <label class="app-form-label app-form-label-required" for="loss_reason">Loss Reason</label>
                                <textarea name="loss_reason" id="loss_reason"
                                          class="app-form-control" rows="3"
                                          placeholder="Describe why this lead was lost or disqualified…"><?= html_escape($lead['loss_reason'] ?? '') ?></textarea>
                                <div class="app-form-hint">Required when status is Lost or Disqualified.</div>
                            </div>
                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section">
                        <div class="row g-3">

                            <div class="col-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="cs_lead_quality">Lead Quality</label>
                                    <div class="app-form-select-wrap">
                                        <select name="lead_quality" id="cs_lead_quality" class="app-form-control">
                                            <option value="hot"  <?= (($lead['lead_quality'] ?? '') === 'hot')  ? 'selected' : '' ?>>🔥 Hot</option>
                                            <option value="warm" <?= (($lead['lead_quality'] ?? '') === 'warm') ? 'selected' : '' ?>>☀️ Warm</option>
                                            <option value="cold" <?= (($lead['lead_quality'] ?? '') === 'cold') ? 'selected' : '' ?>>❄️ Cold</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                </div>

                <div class="app-modal-footer">
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-device-floppy"></i>Update Status
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var select = document.getElementById('leadStatusSelect');
    var wrap   = document.getElementById('lossReasonWrap');
    var ta     = document.getElementById('loss_reason');
    if (!select || !wrap) return;

    function toggle() {
        var v = select.value;
        var show = (v === 'lost' || v === 'disqualified');
        wrap.style.display = show ? 'block' : 'none';
        if (ta) ta.required = show;
    }

    select.addEventListener('change', toggle);
    toggle(); // run on load to handle pre-selected value
})();
</script>