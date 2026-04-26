<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
/* ============================================================
   FIX: Bootstrap scrollable modal support for custom app-modal
   ============================================================ */

.app-modal .modal-dialog.modal-dialog-scrollable {
    height: calc(100% - 1rem);
    margin: 0.5rem auto;
}

@media (min-width: 576px) {
    .app-modal .modal-dialog.modal-dialog-scrollable {
        height: calc(100% - 3.5rem);
        margin: 1.75rem auto;
    }
}

.app-modal .modal-dialog.modal-dialog-scrollable .modal-content {
    max-height: 100%;
    height: auto;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.app-modal .app-modal-body {
    overflow-y: auto;
    overflow-x: hidden;
    flex: 1 1 auto;
    min-height: 0;
}

.app-modal .app-modal-header,
.app-modal .app-modal-footer {
    flex-shrink: 0;
}
</style>
<?php
$leads = is_array($leads ?? null) ? $leads : [];

$proposalStatuses = [
    'draft'          => 'Draft',
    'pending_review' => 'Pending Review',
    'sent'           => 'Sent',
    'viewed'         => 'Viewed',
    'approved'       => 'Approved',
    'declined'       => 'Declined',
    'expired'        => 'Expired',
    'cancelled'      => 'Cancelled',
];

$billingCycles = [
    ''          => '— Select —',
    'weekly'    => 'Weekly',
    'bi-weekly' => 'Bi-Weekly',
    'monthly'   => 'Monthly',
    'quarterly' => 'Quarterly',
    'annual'    => 'Annual',
    'custom'    => 'Custom',
];

$forecastCategories = [
    ''          => '— Select —',
    'commit'    => 'Commit',
    'best_case' => 'Best Case',
    'pipeline'  => 'Pipeline',
    'omitted'   => 'Omitted',
];

$discountTypes = [
    'none'    => 'None',
    'percent' => 'Percent',
    'fixed'   => 'Fixed',
];

$itemTypes = [
    'service'   => 'Service',
    'setup_fee' => 'Setup Fee',
    'addon'     => 'Addon',
    'discount'  => 'Discount',
    'other'     => 'Other',
];
?>

<template id="proposalItemRowTemplate">
    <tr class="js-item-row">
        <td style="min-width:110px;">
            <div class="app-form-select-wrap">
                <select name="item_type[]" class="app-form-control" style="padding:6px 28px 6px 10px;font-size:12px;">
                    <?php foreach ($itemTypes as $value => $label): ?>
                        <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </td>
        <td style="min-width:140px;">
            <input type="text" name="item_name[]" class="app-form-control" style="padding:6px 10px;font-size:12px;" placeholder="Item name">
        </td>
        <td style="min-width:160px;">
            <input type="text" name="item_description[]" class="app-form-control" style="padding:6px 10px;font-size:12px;" placeholder="Description">
        </td>
        <td style="min-width:70px;">
            <input type="number" step="0.01" name="item_quantity[]" class="app-form-control js-item-qty" style="padding:6px 10px;font-size:12px;" value="1" min="0">
        </td>
        <td style="min-width:100px;">
            <input type="number" step="0.01" name="item_unit_price[]" class="app-form-control js-item-price" style="padding:6px 10px;font-size:12px;" value="0" min="0">
        </td>
        <td style="min-width:100px;">
            <div class="app-form-select-wrap">
                <select name="item_discount_type[]" class="app-form-control js-item-disc-type" style="padding:6px 28px 6px 10px;font-size:12px;">
                    <?php foreach ($discountTypes as $value => $label): ?>
                        <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </td>
        <td style="min-width:90px;">
            <input type="number" step="0.01" name="item_discount_value[]" class="app-form-control js-item-disc-val" style="padding:6px 10px;font-size:12px;" value="0" min="0">
        </td>
        <td style="min-width:100px;">
            <input type="number" step="0.01" name="item_discount_amount[]" class="app-form-control js-item-disc-amt" style="padding:6px 10px;font-size:12px;" value="0" readonly tabindex="-1">
        </td>
        <td style="min-width:100px;">
            <input type="number" step="0.01" name="item_line_total[]" class="app-form-control js-item-total" style="padding:6px 10px;font-size:12px;font-weight:700;color:#056464;" value="0" readonly tabindex="-1">
        </td>
        <td style="width:38px;text-align:center;">
            <button type="button" class="js-remove-item-row"
                    style="width:28px;height:28px;border-radius:6px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    </tr>
</template>

<div class="modal fade app-modal" id="addProposalModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="<?= site_url('crm/proposals/store') ?>" class="app-form" id="addProposalForm">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-success">
                            <i class="ti ti-file-plus"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title">Add Proposal</div>
                            <div class="app-modal-subtitle">Create a new proposal and attach it to a lead</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body">

                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-file-description" style="font-size:12px;color:#5ebfbf;"></i>
                            Proposal Details
                        </div>
                        <div class="row g-3">

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_proposal_number">Proposal Number</label>
                                    <input type="text" name="proposal_number" id="add_proposal_number" class="app-form-control" placeholder="Auto-generated if blank">
                                    <div class="app-form-hint">Leave blank to auto-assign.</div>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="app-form-group">
                                    <label class="app-form-label app-form-label-required" for="add_title">Proposal Title</label>
                                    <input type="text" name="title" id="add_title" class="app-form-control" placeholder="e.g. RCM Services Proposal — Valley Medical" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_status">Status</label>
                                    <div class="app-form-select-wrap">
                                        <select name="status" id="add_status" class="app-form-control">
                                            <?php foreach ($proposalStatuses as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_lead_id">Linked Lead</label>
                                    <div class="app-form-select-wrap">
                                        <select name="lead_id" id="add_lead_id" class="app-form-control">
                                            <option value="">— Select Lead —</option>
                                            <?php foreach ($leads as $ld): ?>
                                                <option value="<?= (int)$ld['id'] ?>">
                                                    <?= html_escape(($ld['practice_name'] ?? '') . (!empty($ld['contact_person']) ? ' — ' . $ld['contact_person'] : '')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_summary">Summary</label>
                                    <textarea name="summary" id="add_summary" class="app-form-control" rows="1" placeholder="Brief overview of this proposal…"></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-currency-dollar" style="font-size:12px;color:#5ebfbf;"></i>
                            Pricing &amp; Financials
                        </div>
                        <div class="row g-3">

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_currency">Currency</label>
                                    <input type="text" name="currency" id="add_currency" class="app-form-control" value="USD" maxlength="3" placeholder="USD">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_subtotal">Subtotal</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix">$</span>
                                        <input type="number" step="0.01" name="subtotal" id="add_subtotal" class="app-form-control" value="0" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_discount_type">Discount Type</label>
                                    <div class="app-form-select-wrap">
                                        <select name="discount_type" id="add_discount_type" class="app-form-control">
                                            <?php foreach ($discountTypes as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_discount_value">Discount Value</label>
                                    <input type="number" step="0.01" name="discount_value" id="add_discount_value" class="app-form-control" value="0" min="0">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_tax_rate">Tax Rate (%)</label>
                                    <div class="app-form-input-wrap suffix">
                                        <input type="number" step="0.01" name="tax_rate" id="add_tax_rate" class="app-form-control" value="0" min="0">
                                        <span class="app-form-input-suffix" style="font-weight:700;color:#475569;">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_tax_amount">Tax Amount</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix">$</span>
                                        <input type="number" step="0.01" name="tax_amount" id="add_tax_amount" class="app-form-control" value="0" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Total Value</label>
                                    <div class="app-form-computed" id="add_total_display">$0.00</div>
                                    <div class="app-form-hint">Subtotal − Discount + Tax</div>
                                </div>
                            </div>

                            <input type="hidden" name="total_value" id="add_total_value" value="0">

                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-calendar" style="font-size:12px;color:#5ebfbf;"></i>
                            Terms &amp; Schedule
                        </div>
                        <div class="row g-3">

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_billing_cycle">Billing Cycle</label>
                                    <div class="app-form-select-wrap">
                                        <select name="billing_cycle" id="add_billing_cycle" class="app-form-control">
                                            <?php foreach ($billingCycles as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_payment_terms">Payment Terms</label>
                                    <input type="text" name="payment_terms" id="add_payment_terms" class="app-form-control" placeholder="e.g. Net 30">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_validity_days">Validity (Days)</label>
                                    <div class="app-form-input-wrap suffix">
                                        <input type="number" name="validity_days" id="add_validity_days" class="app-form-control" placeholder="30" min="1">
                                        <span class="app-form-input-suffix" style="font-weight:600;color:#475569;">days</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_forecast_category">Forecast Category</label>
                                    <div class="app-form-select-wrap">
                                        <select name="forecast_category" id="add_forecast_category" class="app-form-control">
                                            <?php foreach ($forecastCategories as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_start_date">Start Date</label>
                                    <input type="date" name="start_date" id="add_start_date" class="app-form-control">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_go_live_date">Go Live Date</label>
                                    <input type="date" name="go_live_date" id="add_go_live_date" class="app-form-control">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_expires_at">Expires At</label>
                                    <input type="date" name="expires_at" id="add_expires_at" class="app-form-control">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section">
                        <div class="app-form-section-label" style="justify-content:space-between;">
                            <span style="display:flex;align-items:center;gap:6px;">
                                <i class="ti ti-list-details" style="font-size:12px;color:#5ebfbf;"></i>
                                Proposal Items
                            </span>
                            <button type="button"
                                    class="js-add-item-row"
                                    data-target="#addProposalItemsBody"
                                    style="padding:3px 12px;font-size:11.5px;font-weight:600;border-radius:6px;border:1.5px solid #056464;background:#f0fdfa;color:#056464;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                <i class="ti ti-plus" style="font-size:13px;"></i> Add Row
                            </button>
                        </div>

                        <div class="table-responsive" style="border:1.5px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-top:4px;">
                            <table class="table mb-0" style="font-size:12.5px;">
                                <thead>
                                    <tr style="background:#f8fafc;">
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Type</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;">Item Name</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;">Description</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Qty</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Unit Price</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Disc. Type</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Disc. Value</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Disc. Amt</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Line Total</th>
                                        <th style="width:38px;border-bottom:1.5px solid #e2e8f0;"></th>
                                    </tr>
                                </thead>
                                <tbody id="addProposalItemsBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="app-form-section-label">
                            <i class="ti ti-note" style="font-size:12px;color:#5ebfbf;"></i>
                            Notes
                        </div>
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_client_notes">Client Notes</label>
                                    <textarea name="client_notes" id="add_client_notes" class="app-form-control" rows="3" placeholder="Notes visible to the client on the proposal…"></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_terms_and_conditions">Terms &amp; Conditions</label>
                                    <textarea name="terms_and_conditions" id="add_terms_and_conditions" class="app-form-control" rows="3" placeholder="Standard terms and conditions…"></textarea>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="add_internal_notes">Internal Notes</label>
                                    <textarea name="internal_notes" id="add_internal_notes" class="app-form-control" rows="2" placeholder="Internal team notes — not visible to client…"></textarea>
                                    <div class="app-form-hint">Visible to your team only.</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="app-modal-footer">
                    <div class="app-modal-footer-left">
                        <i class="ti ti-info-circle" style="font-size:14px;"></i>
                        Required fields are marked with an asterisk (*).
                    </div>
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-device-floppy"></i>Save Proposal
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<div class="modal fade app-modal" id="editProposalModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" id="editProposalForm" action="" class="app-form">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-primary">
                            <i class="ti ti-file-pencil"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title">Edit Proposal</div>
                            <div class="app-modal-subtitle">Update proposal details, pricing, and line items</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body">

                    <div id="editProposalAlert" class="d-none"
                         style="padding:10px 14px;border-radius:8px;background:#fef2f2;border:1.5px solid #fecaca;color:#dc2626;font-size:13px;margin-bottom:16px;">
                    </div>

                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-file-description" style="font-size:12px;color:#5ebfbf;"></i>
                            Proposal Details
                        </div>
                        <div class="row g-3">

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_proposal_number">Proposal Number</label>
                                    <input type="text" name="proposal_number" id="edit_proposal_number" class="app-form-control" readonly style="background:#f8fafc;color:#94a3b8;cursor:not-allowed;">
                                    <div class="app-form-hint">Auto-assigned, read-only.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label app-form-label-required" for="edit_title">Proposal Title</label>
                                    <input type="text" name="title" id="edit_title" class="app-form-control" required placeholder="Proposal title">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_status">Status</label>
                                    <div class="app-form-select-wrap">
                                        <select name="status" id="edit_status" class="app-form-control">
                                            <?php foreach ($proposalStatuses as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_lead_id">Linked Lead</label>
                                    <div class="app-form-select-wrap">
                                        <select name="lead_id" id="edit_lead_id" class="app-form-control">
                                            <option value="">— Select Lead —</option>
                                            <?php foreach ($leads as $ld): ?>
                                                <option value="<?= (int)$ld['id'] ?>">
                                                    <?= html_escape(($ld['practice_name'] ?? '') . (!empty($ld['contact_person']) ? ' — ' . $ld['contact_person'] : '')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_summary">Summary</label>
                                    <textarea name="summary" id="edit_summary" class="app-form-control" rows="1" placeholder="Brief overview…"></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-currency-dollar" style="font-size:12px;color:#5ebfbf;"></i>
                            Pricing &amp; Financials
                        </div>
                        <div class="row g-3">

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_currency">Currency</label>
                                    <input type="text" name="currency" id="edit_currency" class="app-form-control" maxlength="3" placeholder="USD">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_subtotal">Subtotal</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix">$</span>
                                        <input type="number" step="0.01" name="subtotal" id="edit_subtotal" class="app-form-control" value="0" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_discount_type">Discount Type</label>
                                    <div class="app-form-select-wrap">
                                        <select name="discount_type" id="edit_discount_type" class="app-form-control">
                                            <?php foreach ($discountTypes as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_discount_value">Discount Value</label>
                                    <input type="number" step="0.01" name="discount_value" id="edit_discount_value" class="app-form-control" value="0" min="0">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_tax_rate">Tax Rate (%)</label>
                                    <div class="app-form-input-wrap suffix">
                                        <input type="number" step="0.01" name="tax_rate" id="edit_tax_rate" class="app-form-control" value="0" min="0">
                                        <span class="app-form-input-suffix" style="font-weight:700;color:#475569;">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_tax_amount">Tax Amount</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix">$</span>
                                        <input type="number" step="0.01" name="tax_amount" id="edit_tax_amount" class="app-form-control" value="0" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Total Value</label>
                                    <div class="app-form-computed" id="edit_total_display">$0.00</div>
                                    <div class="app-form-hint">Subtotal − Discount + Tax</div>
                                </div>
                            </div>

                            <input type="hidden" name="total_value" id="edit_total_value" value="0">

                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-calendar" style="font-size:12px;color:#5ebfbf;"></i>
                            Terms &amp; Schedule
                        </div>
                        <div class="row g-3">

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_billing_cycle">Billing Cycle</label>
                                    <div class="app-form-select-wrap">
                                        <select name="billing_cycle" id="edit_billing_cycle" class="app-form-control">
                                            <?php foreach ($billingCycles as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_payment_terms">Payment Terms</label>
                                    <input type="text" name="payment_terms" id="edit_payment_terms" class="app-form-control" placeholder="e.g. Net 30">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_validity_days">Validity (Days)</label>
                                    <div class="app-form-input-wrap suffix">
                                        <input type="number" name="validity_days" id="edit_validity_days" class="app-form-control" min="1">
                                        <span class="app-form-input-suffix" style="font-weight:600;color:#475569;">days</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_forecast_category">Forecast Category</label>
                                    <div class="app-form-select-wrap">
                                        <select name="forecast_category" id="edit_forecast_category" class="app-form-control">
                                            <?php foreach ($forecastCategories as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_start_date">Start Date</label>
                                    <input type="date" name="start_date" id="edit_start_date" class="app-form-control">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_go_live_date">Go Live Date</label>
                                    <input type="date" name="go_live_date" id="edit_go_live_date" class="app-form-control">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_expires_at">Expires At</label>
                                    <input type="date" name="expires_at" id="edit_expires_at" class="app-form-control">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section">
                        <div class="app-form-section-label" style="justify-content:space-between;">
                            <span style="display:flex;align-items:center;gap:6px;">
                                <i class="ti ti-list-details" style="font-size:12px;color:#5ebfbf;"></i>
                                Proposal Items
                            </span>
                            <button type="button"
                                    class="js-add-item-row"
                                    data-target="#editProposalItemsBody"
                                    style="padding:3px 12px;font-size:11.5px;font-weight:600;border-radius:6px;border:1.5px solid #056464;background:#f0fdfa;color:#056464;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                <i class="ti ti-plus" style="font-size:13px;"></i> Add Row
                            </button>
                        </div>

                        <div class="table-responsive" style="border:1.5px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-top:4px;">
                            <table class="table mb-0" style="font-size:12.5px;">
                                <thead>
                                    <tr style="background:#f8fafc;">
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Type</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;">Item Name</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;">Description</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Qty</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Unit Price</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Disc. Type</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Disc. Value</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Disc. Amt</th>
                                        <th style="padding:9px 10px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;border-bottom:1.5px solid #e2e8f0;white-space:nowrap;">Line Total</th>
                                        <th style="width:38px;border-bottom:1.5px solid #e2e8f0;"></th>
                                    </tr>
                                </thead>
                                <tbody id="editProposalItemsBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="app-form-divider"></div>

                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="app-form-section-label">
                            <i class="ti ti-note" style="font-size:12px;color:#5ebfbf;"></i>
                            Notes
                        </div>
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_client_notes">Client Notes</label>
                                    <textarea name="client_notes" id="edit_client_notes" class="app-form-control" rows="3" placeholder="Notes visible to the client…"></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_terms_and_conditions">Terms &amp; Conditions</label>
                                    <textarea name="terms_and_conditions" id="edit_terms_and_conditions" class="app-form-control" rows="3" placeholder="Standard terms and conditions…"></textarea>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_internal_notes">Internal Notes</label>
                                    <textarea name="internal_notes" id="edit_internal_notes" class="app-form-control" rows="2" placeholder="Internal team notes — not visible to client…"></textarea>
                                    <div class="app-form-hint">Visible to your team only.</div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="app-modal-footer">
                    <div class="app-modal-footer-left">
                        <i class="ti ti-info-circle" style="font-size:14px;"></i>
                        Changes saved immediately to the proposal record.
                    </div>
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-device-floppy"></i>Update Proposal
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildItemRowHtml(item) {
        item = item || {};

        var itemType = item.item_type || 'service';
        var discountType = item.discount_type || 'none';
        var quantity = item.quantity != null ? item.quantity : 1;
        var unitPrice = item.unit_price != null ? item.unit_price : 0;
        var discountValue = item.discount_value != null ? item.discount_value : 0;
        var discountAmount = item.discount_amount != null ? item.discount_amount : 0;
        var lineTotal = item.line_total != null ? item.line_total : 0;

        return ''
            + '<tr class="js-item-row">'
            + '    <td style="min-width:110px;">'
            + '        <div class="app-form-select-wrap">'
            + '            <select name="item_type[]" class="app-form-control" style="padding:6px 28px 6px 10px;font-size:12px;">'
            + '                <option value="service"'   + (itemType === 'service' ? ' selected' : '')   + '>Service</option>'
            + '                <option value="setup_fee"' + (itemType === 'setup_fee' ? ' selected' : '') + '>Setup Fee</option>'
            + '                <option value="addon"'     + (itemType === 'addon' ? ' selected' : '')     + '>Addon</option>'
            + '                <option value="discount"'  + (itemType === 'discount' ? ' selected' : '')  + '>Discount</option>'
            + '                <option value="other"'     + (itemType === 'other' ? ' selected' : '')     + '>Other</option>'
            + '            </select>'
            + '        </div>'
            + '    </td>'
            + '    <td style="min-width:140px;"><input type="text" name="item_name[]" class="app-form-control" style="padding:6px 10px;font-size:12px;" value="' + escapeHtml(item.item_name || '') + '" placeholder="Item name"></td>'
            + '    <td style="min-width:160px;"><input type="text" name="item_description[]" class="app-form-control" style="padding:6px 10px;font-size:12px;" value="' + escapeHtml(item.description || '') + '" placeholder="Description"></td>'
            + '    <td style="min-width:70px;"><input type="number" step="0.01" name="item_quantity[]" class="app-form-control js-item-qty" style="padding:6px 10px;font-size:12px;" value="' + escapeHtml(quantity) + '" min="0"></td>'
            + '    <td style="min-width:100px;"><input type="number" step="0.01" name="item_unit_price[]" class="app-form-control js-item-price" style="padding:6px 10px;font-size:12px;" value="' + escapeHtml(unitPrice) + '" min="0"></td>'
            + '    <td style="min-width:100px;">'
            + '        <div class="app-form-select-wrap">'
            + '            <select name="item_discount_type[]" class="app-form-control js-item-disc-type" style="padding:6px 28px 6px 10px;font-size:12px;">'
            + '                <option value="none"'    + (discountType === 'none' ? ' selected' : '')    + '>None</option>'
            + '                <option value="percent"' + (discountType === 'percent' ? ' selected' : '') + '>Percent</option>'
            + '                <option value="fixed"'   + (discountType === 'fixed' ? ' selected' : '')   + '>Fixed</option>'
            + '            </select>'
            + '        </div>'
            + '    </td>'
            + '    <td style="min-width:90px;"><input type="number" step="0.01" name="item_discount_value[]" class="app-form-control js-item-disc-val" style="padding:6px 10px;font-size:12px;" value="' + escapeHtml(discountValue) + '" min="0"></td>'
            + '    <td style="min-width:100px;"><input type="number" step="0.01" name="item_discount_amount[]" class="app-form-control js-item-disc-amt" style="padding:6px 10px;font-size:12px;" value="' + escapeHtml(discountAmount) + '" readonly tabindex="-1"></td>'
            + '    <td style="min-width:100px;"><input type="number" step="0.01" name="item_line_total[]" class="app-form-control js-item-total" style="padding:6px 10px;font-size:12px;font-weight:700;color:#056464;" value="' + escapeHtml(lineTotal) + '" readonly tabindex="-1"></td>'
            + '    <td style="width:38px;text-align:center;">'
            + '        <button type="button" class="js-remove-item-row" style="width:28px;height:28px;border-radius:6px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;">'
            + '            <i class="ti ti-trash"></i>'
            + '        </button>'
            + '    </td>'
            + '</tr>';
    }

    function populateRows(tbodyId, count) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        tbody.innerHTML = '';

        for (var i = 0; i < count; i++) {
            tbody.insertAdjacentHTML('beforeend', buildItemRowHtml({}));
        }

        bindRowEvents(tbody);
        var form = tbody.closest('form');
        if (form) recalcModal(form);
    }

    function populateRowsFromItems(tbodyId, items) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;

        tbody.innerHTML = '';
        items = Array.isArray(items) && items.length ? items : [{}];

        items.forEach(function (item) {
            tbody.insertAdjacentHTML('beforeend', buildItemRowHtml(item));
        });

        bindRowEvents(tbody);
        var form = tbody.closest('form');
        if (form) recalcModal(form);
    }

    function calcRowTotal(row) {
        var qty      = parseFloat(row.querySelector('.js-item-qty')?.value) || 0;
        var price    = parseFloat(row.querySelector('.js-item-price')?.value) || 0;
        var discType = row.querySelector('.js-item-disc-type')?.value || 'none';
        var discVal  = parseFloat(row.querySelector('.js-item-disc-val')?.value) || 0;

        var discAmtEl = row.querySelector('.js-item-disc-amt');
        var totalEl   = row.querySelector('.js-item-total');

        var gross   = qty * price;
        var discAmt = 0;

        if (discType === 'percent') {
            discAmt = gross * (discVal / 100);
        } else if (discType === 'fixed') {
            discAmt = Math.min(discVal, gross);
        }

        var lineTotal = Math.max(0, gross - discAmt);

        if (discAmtEl) discAmtEl.value = discAmt.toFixed(2);
        if (totalEl) totalEl.value = lineTotal.toFixed(2);

        return lineTotal;
    }

    function recalcModal(form) {
        if (!form) return;

        var itemsSum = 0;
        form.querySelectorAll('.js-item-row').forEach(function (row) {
            itemsSum += calcRowTotal(row);
        });

        var subtotalEl  = form.querySelector('[name="subtotal"]');
        var discTypeEl  = form.querySelector('[name="discount_type"]');
        var discValEl   = form.querySelector('[name="discount_value"]');
        var taxRateEl   = form.querySelector('[name="tax_rate"]');
        var taxAmtEl    = form.querySelector('[name="tax_amount"]');
        var totalHidden = form.querySelector('[name="total_value"]');
        var currencyEl  = form.querySelector('[name="currency"]');

        if (subtotalEl && itemsSum > 0) {
            subtotalEl.value = itemsSum.toFixed(2);
        }

        var subtotal = parseFloat(subtotalEl?.value) || 0;
        var discType = discTypeEl?.value || 'none';
        var discVal  = parseFloat(discValEl?.value) || 0;
        var taxRate  = parseFloat(taxRateEl?.value) || 0;

        var discAmt = 0;
        if (discType === 'percent') {
            discAmt = subtotal * (discVal / 100);
        } else if (discType === 'fixed') {
            discAmt = Math.min(discVal, subtotal);
        }

        var taxableBase = Math.max(0, subtotal - discAmt);
        var taxAmt      = taxableBase * (taxRate / 100);
        var total       = Math.max(0, taxableBase + taxAmt);

        if (taxAmtEl) taxAmtEl.value = taxAmt.toFixed(2);
        if (totalHidden) totalHidden.value = total.toFixed(2);

        var currency = (currencyEl?.value || 'USD').toUpperCase();
        var symbol = '$';
        if (currency === 'PKR') symbol = 'Rs ';
        else if (currency === 'GBP') symbol = '£';
        else if (currency === 'EUR') symbol = '€';
        else if (currency === 'AED') symbol = 'AED ';
        else if (currency === 'SAR') symbol = 'SAR ';
        else if (currency !== 'USD') symbol = currency + ' ';

        var display = symbol + total.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        var prefix = form.id === 'editProposalForm' ? 'edit' : 'add';
        var displayEl = document.getElementById(prefix + '_total_display');
        if (displayEl) displayEl.textContent = display;
    }

    function onRowInput(e) {
        var row  = e.target.closest('tr');
        var form = e.target.closest('form');
        if (row) calcRowTotal(row);
        if (form) recalcModal(form);
    }

    function bindRowEvents(tbody) {
        tbody.querySelectorAll('.js-item-qty, .js-item-price, .js-item-disc-type, .js-item-disc-val').forEach(function (el) {
            el.removeEventListener('input', onRowInput);
            el.removeEventListener('change', onRowInput);
            el.addEventListener('input', onRowInput);
            el.addEventListener('change', onRowInput);
        });
    }

    function bindHeaderFields(formId) {
        var form = document.getElementById(formId);
        if (!form || form.dataset.bound === '1') return;

        ['subtotal', 'discount_type', 'discount_value', 'tax_rate', 'currency'].forEach(function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el) {
                el.addEventListener('input', function () { recalcModal(form); });
                el.addEventListener('change', function () { recalcModal(form); });
            }
        });

        form.dataset.bound = '1';
    }

    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.js-add-item-row');
        if (addBtn) {
            var targetSelector = addBtn.getAttribute('data-target');
            var tbody = targetSelector ? document.querySelector(targetSelector) : null;
            if (tbody) {
                tbody.insertAdjacentHTML('beforeend', buildItemRowHtml({}));
                bindRowEvents(tbody);
                var form = tbody.closest('form');
                if (form) recalcModal(form);
            }
            return;
        }

        var removeBtn = e.target.closest('.js-remove-item-row');
        if (removeBtn) {
            var row = removeBtn.closest('tr');
            if (row) {
                var tbody = row.closest('tbody');
                row.remove();

                if (tbody && !tbody.querySelector('tr')) {
                    tbody.insertAdjacentHTML('beforeend', buildItemRowHtml({}));
                }

                if (tbody) {
                    bindRowEvents(tbody);
                    var form = tbody.closest('form');
                    if (form) recalcModal(form);
                }
            }
            return;
        }

        var editBtn = e.target.closest('.js-edit-proposal');
        if (!editBtn) return;

        var proposalId = editBtn.getAttribute('data-id');
        if (!proposalId) return;

        var alertBox  = document.getElementById('editProposalAlert');
        var editForm  = document.getElementById('editProposalForm');

        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.innerHTML = '';
        }

        fetch('<?= site_url('crm/proposals/ajax_get/') ?>' + proposalId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Unable to load proposal data.');
            }
            return response.json();
        })
        .then(function (res) {
            if (!res || res.status !== 'ok' || !res.proposal) {
                throw new Error(res && res.message ? res.message : 'Proposal data not found.');
            }

            var p = res.proposal;
            var items = Array.isArray(res.items) ? res.items : [];

            editForm.action = '<?= site_url('crm/proposals/update/') ?>' + p.id;

            [
                'proposal_number','lead_id','status','title','summary','currency',
                'subtotal','discount_type','discount_value','tax_rate','tax_amount',
                'billing_cycle','payment_terms','validity_days','start_date',
                'go_live_date','forecast_category','expires_at','pdf_path',
                'total_value','client_notes','terms_and_conditions','internal_notes'
            ].forEach(function (field) {
                var el = document.getElementById('edit_' + field);
                if (el) {
                    el.value = p[field] != null ? p[field] : '';
                }
            });

            populateRowsFromItems('editProposalItemsBody', items);

            var modalEl = document.getElementById('editProposalModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            recalcModal(editForm);
        })
        .catch(function (error) {
            if (alertBox) {
                alertBox.classList.remove('d-none');
                alertBox.innerHTML = escapeHtml(error.message || 'Failed to load proposal.');
            }

            var modalEl = document.getElementById('editProposalModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    });

    var addModal = document.getElementById('addProposalModal');
    if (addModal) {
        addModal.addEventListener('shown.bs.modal', function () {
            bindHeaderFields('addProposalForm');
            populateRows('addProposalItemsBody', 3);
            recalcModal(document.getElementById('addProposalForm'));
        });
    }

    var editModal = document.getElementById('editProposalModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            bindHeaderFields('editProposalForm');
            var form = document.getElementById('editProposalForm');
            if (form) recalcModal(form);
        });
    }
})();
</script>