<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$proposal = is_array($proposal ?? null) ? $proposal : [];
$items    = is_array($items ?? null) ? $items : [];
$leads    = is_array($leads ?? null) ? $leads : [];

$proposalStatuses     = proposal_statuses();
$forecastCategories   = forecast_categories();
$billingCycles        = proposal_billing_cycles();
$discountTypes        = proposal_discount_types();
$itemTypes            = proposal_item_types();

$currentStatus  = $proposal['status'] ?? 'draft';
$proposalId     = (int)($proposal['id'] ?? 0);
$proposalNumber = $proposal['proposal_number'] ?? '—';

?>

<style>
/* ── Proposal form shared styles ─────────────────────────────────────────── */
.proposal-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: #94a3b8;
    padding: 10px 16px 8px;
    background: #f8fafc;
    border-bottom: 1px solid #e9ecef;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.proposal-section-title i {
    font-size: 13px;
    color: #056464;
}
.proposal-section-body {
    padding: 16px;
}
.proposal-section {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 16px;
}
.proposal-total-box {
    background: linear-gradient(135deg, #056464 0%, #0a8a8a 100%);
    border-radius: 8px;
    padding: 12px 16px;
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 64px;
}
.proposal-total-box .total-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.8;
    margin-bottom: 2px;
}
.proposal-total-box .total-value {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.5px;
    line-height: 1.1;
}
.proposal-total-box .total-hint {
    font-size: 10px;
    opacity: 0.65;
    margin-top: 2px;
}
.proposal-items-table th {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748b;
    white-space: nowrap;
    padding: 8px 8px;
    border-bottom: 2px solid #e9ecef;
    background: #f8fafc;
}
.proposal-items-table td {
    padding: 5px 6px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.proposal-items-table tbody tr:last-child td {
    border-bottom: none;
}
.proposal-items-table .form-control,
.proposal-items-table .form-select {
    font-size: 12.5px;
    padding: 4px 8px;
    height: 32px;
}
.proposal-items-table .form-select {
    padding-right: 24px;
}
.item-line-total {
    font-weight: 700;
    color: #056464 !important;
    background: #f0fdfa !important;
}
.item-discount-amount {
    background: #f8fafc !important;
    color: #64748b !important;
}
#pf-empty-state {
    padding: 28px;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
    display: none;
}
#pf-empty-state i {
    font-size: 28px;
    display: block;
    margin-bottom: 6px;
    opacity: 0.4;
}
.pf-discount-hint {
    font-size: 10.5px;
    color: #94a3b8;
    margin-top: 3px;
    min-height: 15px;
}
.date-warning {
    font-size: 10.5px;
    color: #dc2626;
    margin-top: 3px;
    display: none;
}
.date-warning i { font-size: 11px; }
.pf-readonly-field {
    background: #f8fafc !important;
    color: #64748b !important;
    cursor: not-allowed;
}
</style>

<div class="container-fluid py-3">

    <!-- ── Page Header ──────────────────────────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <i class="ti ti-file-pencil"></i>
            <h1 class="h6 header-title mb-0">Edit Proposal</h1>
            <span class="badge bg-light-primary"><?= html_escape($proposalNumber) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= site_url('crm/proposals/view/' . $proposalId) ?>" class="btn btn-light-primary btn-header">
                <i class="ti ti-arrow-left me-1"></i>Back to Proposal
            </a>
        </div>
    </div>

    <form method="post" action="<?= site_url('crm/proposals/update/' . $proposalId) ?>"
          id="proposalForm" class="card border-0 shadow-sm">
        <div class="card-body p-3">

            <!-- ══ SECTION 1 — Proposal Details ═══════════════════════════ -->
            <div class="proposal-section">
                <div class="proposal-section-title">
                    <i class="ti ti-file-description"></i> Proposal Details
                </div>
                <div class="proposal-section-body">
                    <div class="row g-3">

                        <div class="col-md-3">
                            <label class="form-label">Proposal Number</label>
                            <input type="text" name="proposal_number" class="form-control pf-readonly-field"
                                   value="<?= html_escape($proposalNumber) ?>" readonly>
                            <div class="form-text">Auto-assigned — read only.</div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Proposal Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   value="<?= html_escape($proposal['title'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <?php $statuses = proposal_statuses(); ?>
                            <select name="status" id="pf_status" class="form-select">
                                <?php foreach ($statuses as $key => $status): ?>
                                    <option value="<?= $key ?>"
                                        <?= ($currentStatus === $key) ? 'selected' : '' ?>>
                                        <?= html_escape($status['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <div id="pf_status_badge" class="badge fs-6 w-100 text-center py-2"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Linked Lead</label>
                            <select name="lead_id" class="form-select">
                                <option value="">— Select Lead —</option>
                                <?php foreach ($leads as $ld): ?>
                                    <option value="<?= (int)$ld['id'] ?>"
                                        <?= ((string)($proposal['lead_id'] ?? '') === (string)$ld['id']) ? 'selected' : '' ?>>
                                        <?= html_escape(($ld['practice_name'] ?? '') . (!empty($ld['contact_person']) ? ' — ' . $ld['contact_person'] : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Summary</label>
                            <textarea name="summary" class="form-control" rows="2"
                                      placeholder="Brief overview of this proposal…"><?= html_escape($proposal['summary'] ?? '') ?></textarea>
                        </div>

                        <!-- Edit-only: tracking timestamps -->
                        <div class="col-md-3">
                            <label class="form-label">Sent At</label>
                            <input type="datetime-local" name="sent_at" class="form-control"
                                   value="<?= html_escape(!empty($proposal['sent_at']) ? date('Y-m-d\TH:i', strtotime($proposal['sent_at'])) : '') ?>">
                            <div class="form-text">When the proposal was sent to the client.</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Approved / Declined At</label>
                            <input type="datetime-local" name="approved_at" class="form-control"
                                   value="<?= html_escape(!empty($proposal['approved_at']) ? date('Y-m-d\TH:i', strtotime($proposal['approved_at'])) : '') ?>">
                            <div class="form-text">When the client responded.</div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ══ SECTION 2 — Pricing & Financials ═══════════════════════ -->
            <div class="proposal-section">
                <div class="proposal-section-title">
                    <i class="ti ti-currency-dollar"></i> Pricing &amp; Financials
                </div>
                <div class="proposal-section-body">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-1">
                            <label class="form-label">Currency</label>
                            <input type="text" name="currency" class="form-control text-center fw-bold"
                                   maxlength="3" value="<?= html_escape($proposal['currency'] ?? 'USD') ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Subtotal</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" name="subtotal" id="pf_subtotal"
                                       class="form-control pf-readonly-field" value="<?= html_escape($proposal['subtotal'] ?? '0.00') ?>"
                                       readonly style="background:#f8fafc;">
                            </div>
                            <div class="form-text">Auto-summed from items.</div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" id="pf_discount_type" class="form-select">
                                <?php foreach ($discountTypes as $v => $l): ?>
                                    <option value="<?= $v ?>"
                                        <?= (($proposal['discount_type'] ?? 'none') === $v) ? 'selected' : '' ?>>
                                        <?= html_escape($l) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Discount Value</label>
                            <input type="number" step="0.01" min="0" name="discount_value" id="pf_discount_value"
                                   class="form-control" value="<?= html_escape($proposal['discount_value'] ?? '0') ?>">
                            <div class="pf-discount-hint" id="pf_discount_hint"></div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tax Rate</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="pf_tax_rate"
                                       class="form-control" value="<?= html_escape($proposal['tax_rate'] ?? '0') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="tax_amount" id="pf_tax_amount"
                                       class="form-control pf-readonly-field" value="<?= html_escape($proposal['tax_amount'] ?? '0.00') ?>"
                                       readonly style="background:#f8fafc;">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="proposal-total-box">
                                <div class="total-label">Total Value</div>
                                <div class="total-value" id="pf_total_display">
                                    $<?= number_format((float)($proposal['total_value'] ?? 0), 2) ?>
                                </div>
                                <div class="total-hint">Subtotal − Discount + Tax</div>
                            </div>
                            <input type="hidden" name="total_value" id="pf_total_value"
                                   value="<?= html_escape($proposal['total_value'] ?? '0') ?>">
                        </div>

                    </div>
                </div>
            </div>

            <!-- ══ SECTION 3 — Terms & Schedule ═══════════════════════════ -->
            <div class="proposal-section">
                <div class="proposal-section-title">
                    <i class="ti ti-calendar-event"></i> Terms &amp; Schedule
                </div>
                <div class="proposal-section-body">
                    <div class="row g-3">

                        <div class="col-md-3">
                            <label class="form-label">Billing Cycle</label>
                            <select name="billing_cycle" class="form-select">
                                <?php foreach ($billingCycles as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($proposal['billing_cycle'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= html_escape($label) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control"
                                   value="<?= html_escape($proposal['payment_terms'] ?? '') ?>"
                                   placeholder="e.g. Net 30">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Validity (Days)</label>
                            <input type="number" min="1" name="validity_days" class="form-control"
                                   value="<?= html_escape($proposal['validity_days'] ?? '') ?>"
                                   placeholder="30">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Forecast Category</label>
                            <select name="forecast_category" class="form-select">
                                <?php foreach ($forecastCategories as $v => $l): ?>
                                    <option value="<?= $v ?>"
                                        <?= (($proposal['forecast_category'] ?? '') === $v) ? 'selected' : '' ?>>
                                        <?= html_escape($l['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="pf_start_date" class="form-control"
                                   value="<?= html_escape($proposal['start_date'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Go Live Date</label>
                            <input type="date" name="go_live_date" id="pf_go_live_date" class="form-control"
                                   value="<?= html_escape($proposal['go_live_date'] ?? '') ?>">
                            <div class="date-warning" id="pf_date_warn">
                                <i class="ti ti-alert-triangle"></i> Go live is before start date.
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Expires At</label>
                            <input type="date" name="expires_at" id="pf_expires_at" class="form-control"
                                   value="<?= html_escape($proposal['expires_at'] ?? '') ?>">
                            <div class="date-warning" id="pf_expire_warn">
                                <i class="ti ti-alert-triangle"></i> Expiry is before start date.
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ══ SECTION 4 — Proposal Items ═════════════════════════════ -->
            <div class="proposal-section">
                <div class="proposal-section-title" style="justify-content:space-between;">
                    <span><i class="ti ti-list-details"></i> Proposal Items</span>
                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" id="addItemRowBtn"
                            style="font-size:12px;font-weight:600;">
                        <i class="ti ti-plus me-1"></i>Add Row
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 proposal-items-table" id="proposalItemsTable">
                        <thead>
                            <tr>
                                <th style="min-width:110px;">Type</th>
                                <th style="min-width:150px;">Item Name</th>
                                <th style="min-width:160px;">Description</th>
                                <th style="min-width:70px;">Qty</th>
                                <th style="min-width:100px;">Unit Price</th>
                                <th style="min-width:110px;">Disc. Type</th>
                                <th style="min-width:90px;">Disc. Value</th>
                                <th style="min-width:90px;">Disc. Amt</th>
                                <th style="min-width:100px;">Line Total</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="proposalItemsBody">
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr class="proposal-item-row">
                                        <td>
                                            <select name="item_type[]" class="form-select form-select-sm item-type">
                                                <?php foreach ($itemTypes as $v => $l): ?>
                                                    <option value="<?= $v ?>" <?= (($item['item_type'] ?? 'service') === $v) ? 'selected' : '' ?>><?= html_escape($l) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="text" name="item_name[]" class="form-control form-control-sm" value="<?= html_escape($item['item_name'] ?? '') ?>" placeholder="Item name"></td>
                                        <td><input type="text" name="item_description[]" class="form-control form-control-sm" value="<?= html_escape($item['description'] ?? $item['item_description'] ?? '') ?>" placeholder="Description"></td>
                                        <td><input type="number" step="0.01" min="0" name="item_quantity[]" class="form-control form-control-sm item-qty" value="<?= html_escape($item['quantity'] ?? $item['item_quantity'] ?? 1) ?>"></td>
                                        <td><input type="number" step="0.01" min="0" name="item_unit_price[]" class="form-control form-control-sm item-price" value="<?= html_escape($item['unit_price'] ?? $item['item_unit_price'] ?? 0) ?>"></td>
                                        <td>
                                            <select name="item_discount_type[]" class="form-select form-select-sm item-discount-type">
                                                <?php foreach ($discountTypes as $v => $l): ?>
                                                    <option value="<?= $v ?>" <?= (($item['discount_type'] ?? $item['item_discount_type'] ?? 'none') === $v) ? 'selected' : '' ?>><?= html_escape($l) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" min="0" name="item_discount_value[]" class="form-control form-control-sm item-discount-value" value="<?= html_escape($item['discount_value'] ?? $item['item_discount_value'] ?? 0) ?>"></td>
                                        <td><input type="number" step="0.01" name="item_discount_amount[]" class="form-control form-control-sm item-discount-amount" value="<?= html_escape($item['discount_amount'] ?? $item['item_discount_amount'] ?? 0) ?>" readonly tabindex="-1"></td>
                                        <td><input type="number" step="0.01" name="item_line_total[]" class="form-control form-control-sm item-line-total" value="<?= html_escape($item['line_total'] ?? $item['item_line_total'] ?? 0) ?>" readonly tabindex="-1"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-item-row p-1" style="line-height:1;">
                                                <i class="ti ti-trash" style="font-size:13px;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- No items from DB — seed one blank row -->
                                <tr class="proposal-item-row">
                                    <td><select name="item_type[]" class="form-select form-select-sm item-type">
                                        <option value="service">Service</option><option value="setup_fee">Setup Fee</option><option value="addon">Addon</option><option value="discount">Discount</option><option value="other">Other</option>
                                    </select></td>
                                    <td><input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="Item name"></td>
                                    <td><input type="text" name="item_description[]" class="form-control form-control-sm" placeholder="Description"></td>
                                    <td><input type="number" step="0.01" min="0" name="item_quantity[]" class="form-control form-control-sm item-qty" value="1"></td>
                                    <td><input type="number" step="0.01" min="0" name="item_unit_price[]" class="form-control form-control-sm item-price" value="0"></td>
                                    <td><select name="item_discount_type[]" class="form-select form-select-sm item-discount-type">
                                        <option value="none">None</option><option value="percent">Percent (%)</option><option value="fixed">Fixed ($)</option>
                                    </select></td>
                                    <td><input type="number" step="0.01" min="0" name="item_discount_value[]" class="form-control form-control-sm item-discount-value" value="0"></td>
                                    <td><input type="number" step="0.01" name="item_discount_amount[]" class="form-control form-control-sm item-discount-amount" value="0" readonly tabindex="-1"></td>
                                    <td><input type="number" step="0.01" name="item_line_total[]" class="form-control form-control-sm item-line-total" value="0" readonly tabindex="-1"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item-row p-1" style="line-height:1;"><i class="ti ti-trash" style="font-size:13px;"></i></button></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div id="pf-empty-state">
                        <i class="ti ti-table-off"></i>
                        No items yet — click <strong>Add Row</strong> to begin.
                    </div>
                </div>
            </div>

            <!-- ══ SECTION 5 — Notes ═══════════════════════════════════════ -->
            <div class="proposal-section" style="margin-bottom:0;">
                <div class="proposal-section-title">
                    <i class="ti ti-note"></i> Notes
                </div>
                <div class="proposal-section-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Client Notes</label>
                            <textarea name="client_notes" class="form-control" rows="4"
                                      placeholder="Visible to the client on the printed proposal…"><?= html_escape($proposal['client_notes'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Terms &amp; Conditions</label>
                            <textarea name="terms_and_conditions" class="form-control" rows="4"
                                      placeholder="Standard terms and conditions…"><?= html_escape($proposal['terms_and_conditions'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">
                                Internal Notes
                                <span class="badge bg-light-secondary text-secondary ms-1" style="font-size:10px;">Team only</span>
                            </label>
                            <textarea name="internal_notes" class="form-control" rows="3"
                                      placeholder="Not visible to client…"><?= html_escape($proposal['internal_notes'] ?? '') ?></textarea>
                        </div>

                    </div>
                </div>
            </div>

        </div><!-- /.card-body -->

        <!-- ── Footer ─────────────────────────────────────────────────── -->
        <div class="card-footer bg-white d-flex align-items-center justify-content-between gap-2">
            <div class="text-muted" style="font-size:12px;">
                <i class="ti ti-info-circle me-1"></i>
                Fields marked <span class="text-danger fw-bold">*</span> are required.
            </div>
            <div class="d-flex gap-2">
                <a href="<?= site_url('crm/proposals/view/' . $proposalId) ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Update Proposal
                </button>
            </div>
        </div>

    </form>
</div>

<script>
(function () {
    'use strict';

    /* ── DOM refs ─────────────────────────────────────────────────────── */
    const tbody          = document.getElementById('proposalItemsBody');
    const addBtn         = document.getElementById('addItemRowBtn');
    const emptyState     = document.getElementById('pf-empty-state');
    const subtotalEl     = document.getElementById('pf_subtotal');
    const discTypeEl     = document.getElementById('pf_discount_type');
    const discValueEl    = document.getElementById('pf_discount_value');
    const discHintEl     = document.getElementById('pf_discount_hint');
    const taxRateEl      = document.getElementById('pf_tax_rate');
    const taxAmountEl    = document.getElementById('pf_tax_amount');
    const totalDisplayEl = document.getElementById('pf_total_display');
    const totalValueEl   = document.getElementById('pf_total_value');
    const startDateEl    = document.getElementById('pf_start_date');
    const goLiveDateEl   = document.getElementById('pf_go_live_date');
    const expiresAtEl    = document.getElementById('pf_expires_at');
    const dateWarnEl     = document.getElementById('pf_date_warn');
    const expireWarnEl   = document.getElementById('pf_expire_warn');

    /* ── Status badge ─────────────────────────────────────────────────── */
    const statusClassMap = {
        draft: 'bg-secondary', pending_review: 'bg-warning text-dark',
        sent: 'bg-info text-dark', viewed: 'bg-primary',
        approved: 'bg-success', declined: 'bg-danger',
        expired: 'bg-dark', cancelled: 'bg-secondary'
    };

    function updateStatusBadge() {
        if (!statusSelect || !statusBadge) return;
        const v     = statusSelect.value;
        const label = statusSelect.options[statusSelect.selectedIndex]?.text || v;
        statusBadge.className  = 'badge fs-6 w-100 text-center py-2 ' + (statusClassMap[v] || 'bg-secondary');
        statusBadge.textContent = label;
    }

    /* ── Discount hint ────────────────────────────────────────────────── */
    function updateDiscountHint() {
        if (!discHintEl || !discTypeEl) return;
        const sub  = parseFloat(subtotalEl?.value  || 0);
        const v    = parseFloat(discValueEl?.value || 0);
        const type = discTypeEl.value;
        if (type === 'none' || v === 0) { discHintEl.textContent = ''; return; }
        if (type === 'percent') {
            discHintEl.textContent = '= $' + (sub * v / 100).toFixed(2) + ' off subtotal';
        } else if (type === 'fixed') {
            discHintEl.textContent = '$' + v.toFixed(2) + ' flat deduction';
        }
    }

    /* ── Date validation ──────────────────────────────────────────────── */
    function validateDates() {
        const start   = startDateEl?.value   || '';
        const goLive  = goLiveDateEl?.value  || '';
        const expires = expiresAtEl?.value   || '';
        if (dateWarnEl)   dateWarnEl.style.display   = (start && goLive  && goLive  < start) ? 'block' : 'none';
        if (expireWarnEl) expireWarnEl.style.display = (start && expires && expires < start) ? 'block' : 'none';
    }

    /* ── Row template ─────────────────────────────────────────────────── */
    function rowTemplate() {
        return `<tr class="proposal-item-row">
            <td><select name="item_type[]" class="form-select form-select-sm item-type">
                <option value="service">Service</option>
                <option value="setup_fee">Setup Fee</option>
                <option value="addon">Addon</option>
                <option value="discount">Discount</option>
                <option value="other">Other</option>
            </select></td>
            <td><input type="text" name="item_name[]" class="form-control form-control-sm" placeholder="Item name"></td>
            <td><input type="text" name="item_description[]" class="form-control form-control-sm" placeholder="Description"></td>
            <td><input type="number" step="0.01" min="0" name="item_quantity[]" class="form-control form-control-sm item-qty" value="1"></td>
            <td><input type="number" step="0.01" min="0" name="item_unit_price[]" class="form-control form-control-sm item-price" value="0"></td>
            <td><select name="item_discount_type[]" class="form-select form-select-sm item-discount-type">
                <option value="none">None</option>
                <option value="percent">Percent (%)</option>
                <option value="fixed">Fixed ($)</option>
            </select></td>
            <td><input type="number" step="0.01" min="0" name="item_discount_value[]" class="form-control form-control-sm item-discount-value" value="0"></td>
            <td><input type="number" step="0.01" name="item_discount_amount[]" class="form-control form-control-sm item-discount-amount" value="0" readonly tabindex="-1"></td>
            <td><input type="number" step="0.01" name="item_line_total[]" class="form-control form-control-sm item-line-total" value="0" readonly tabindex="-1"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item-row p-1" style="line-height:1;"><i class="ti ti-trash" style="font-size:13px;"></i></button></td>
        </tr>`;
    }

    /* ── Per-row calc ─────────────────────────────────────────────────── */
    function calcRow(row) {
        const qty      = parseFloat(row.querySelector('.item-qty')?.value            || 0);
        const price    = parseFloat(row.querySelector('.item-price')?.value          || 0);
        const discType = row.querySelector('.item-discount-type')?.value             || 'none';
        const discVal  = parseFloat(row.querySelector('.item-discount-value')?.value || 0);

        const gross   = qty * price;
        const discAmt = discType === 'percent' ? gross * discVal / 100
                      : discType === 'fixed'   ? Math.min(discVal, gross)
                      : 0;
        const total   = Math.max(0, gross - discAmt);

        row.querySelector('.item-discount-amount').value = discAmt.toFixed(2);
        row.querySelector('.item-line-total').value      = total.toFixed(2);

        return total;
    }

    /* ── Full recalc ──────────────────────────────────────────────────── */
    function recalcAll() {
        let subtotal = 0;
        tbody.querySelectorAll('.proposal-item-row').forEach(r => { subtotal += calcRow(r); });

        subtotalEl.value = subtotal.toFixed(2);

        const discType = discTypeEl.value || 'none';
        const discVal  = parseFloat(discValueEl.value || 0);
        const taxRate  = parseFloat(taxRateEl.value   || 0);
        const discAmt  = discType === 'percent' ? subtotal * discVal / 100
                       : discType === 'fixed'   ? Math.min(discVal, subtotal)
                       : 0;
        const taxBase  = Math.max(0, subtotal - discAmt);
        const taxAmt   = taxBase * taxRate / 100;
        const total    = taxBase + taxAmt;

        taxAmountEl.value  = taxAmt.toFixed(2);
        totalValueEl.value = total.toFixed(2);
        if (totalDisplayEl) {
            totalDisplayEl.textContent = '$' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        }

        updateDiscountHint();
        toggleEmptyState();
    }

    /* ── Empty state toggle ───────────────────────────────────────────── */
    function toggleEmptyState() {
        if (!emptyState) return;
        emptyState.style.display = tbody.querySelectorAll('.proposal-item-row').length > 0 ? 'none' : 'block';
    }

    /* ── Event listeners ─────────────────────────────────────────────── */
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            tbody.insertAdjacentHTML('beforeend', rowTemplate());
            toggleEmptyState();
        });
    }

    document.addEventListener('input', function (e) {
        if (e.target.closest('.proposal-item-row') ||
            e.target === discTypeEl || e.target === discValueEl || e.target === taxRateEl) {
            recalcAll();
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.closest('.proposal-item-row') ||
            e.target === discTypeEl || e.target === discValueEl || e.target === taxRateEl) {
            recalcAll();
        }
        if (e.target === statusSelect) updateStatusBadge();
        if ([startDateEl, goLiveDateEl, expiresAtEl].includes(e.target)) validateDates();
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-item-row');
        if (!btn) return;
        const rows = tbody.querySelectorAll('.proposal-item-row');
        if (rows.length > 1) {
            btn.closest('.proposal-item-row').remove();
        } else {
            /* last row — clear values instead of removing */
            const row = btn.closest('.proposal-item-row');
            row.querySelectorAll('input[type="text"]').forEach(el => el.value = '');
            row.querySelectorAll('input[type="number"]').forEach(el => el.value = '0');
            row.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
        }
        recalcAll();
    });

    /* ── Init ─────────────────────────────────────────────────────────── */
    validateDates();
    recalcAll();

})();
</script>