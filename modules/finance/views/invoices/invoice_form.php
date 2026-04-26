<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
/* ================================================================
   invoice-form.css
   Flat rules only — no :root, no var(), no CSS custom properties.
   Exact hex color codes throughout.
   ================================================================ */

/* ── Section card ─────────────────────────────────────────────── */
.ifsec {
    background    : #ffffff;
    border        : 1px solid #e2e8f0;
    border-radius : 10px;
    overflow      : hidden;
    margin-bottom : 16px;
}

.ifsec-head {
    display         : flex;
    align-items     : center;
    justify-content : space-between;
    padding         : 11px 16px;
    background      : #f8fafc;
    border-bottom   : 1px solid #e2e8f0;
    gap             : 10px;
    flex-wrap       : wrap;
}

.ifsec-head-right {
    display     : flex;
    align-items : center;
    gap         : 10px;
    flex-wrap   : wrap;
}

.ifsec-title {
    display     : flex;
    align-items : center;
    gap         : 7px;
    font-size   : 13px;
    font-weight : 700;
    color       : #0d1b2a;
}

.ifsec-title i {
    color     : #056464;
    font-size : 14px;
}

.ifsec-body {
    padding : 16px;
}

/* Sticky summary on right column */
.if-summary-sticky {
    position : sticky;
    top      : 72px;
}

/* ── Form labels ──────────────────────────────────────────────── */
.if-lbl {
    display       : block;
    font-size     : 12px;
    font-weight   : 600;
    color         : #475569;
    margin-bottom : 5px;
}

.if-readonly-input {
    background : #f8fafc;
    color      : #475569;
    cursor     : default;
}

/* ── Client panel ─────────────────────────────────────────────── */
.ifcp {
    background    : #f8fafc;
    border        : 1px solid #e2e8f0;
    border-radius : 8px;
    overflow      : hidden;
    transition    : border-color .15s;
}

.ifcp.loaded {
    border-color : #056464;
}

.ifcp-select-wrap {
    padding       : 14px 16px;
    background    : #ffffff;
    border-bottom : 1px solid #e2e8f0;
}

.ifcp-select-lbl {
    display        : flex;
    align-items    : center;
    gap            : 5px;
    font-size      : 10.5px;
    font-weight    : 700;
    letter-spacing : .8px;
    text-transform : uppercase;
    color          : #94a3b8;
    margin-bottom  : 6px;
}

.ifcp-req {
    color       : #dc2626;
    font-weight : 700;
}

.ifcp-loading {
    padding    : 20px;
    text-align : center;
    color      : #94a3b8;
    font-size  : 13px;
    display    : none;
}

.ifcp-loading.show {
    display : block;
}

.ifcp-placeholder {
    padding    : 28px 16px;
    text-align : center;
    color      : #94a3b8;
    font-size  : 13px;
}

.ifcp-placeholder i {
    font-size     : 28px;
    display       : block;
    margin-bottom : 8px;
}

.ifcp-info {
    padding : 14px 16px;
    display : none;
}

.ifcp-info.show {
    display : block;
}

.ifcp-info-hd {
    display         : flex;
    align-items     : flex-start;
    justify-content : space-between;
    gap             : 10px;
    margin-bottom   : 12px;
}

.ifcp-name {
    font-size     : 15px;
    font-weight   : 800;
    color         : #0d1b2a;
    line-height   : 1.3;
    margin-bottom : 3px;
}

.ifcp-code {
    font-size   : 10px;
    font-weight : 600;
    color       : #056464;
    display     : none;
}

.ifcp-grid {
    display               : grid;
    grid-template-columns : 1fr 1fr 1fr;
    gap                   : 10px;
    margin-bottom         : 12px;
}

.ifcp-lbl {
    font-size      : 10px;
    font-weight    : 700;
    letter-spacing : .7px;
    text-transform : uppercase;
    color          : #94a3b8;
    margin-bottom  : 2px;
}

.ifcp-val {
    font-size   : 12.5px;
    font-weight : 500;
    color       : #334155;
    line-height : 1.55;
}

.ifcp-val-sm {
    font-size : 12px;
    color     : #64748b;
}

/* Outstanding invoices */
.ifcp-outstanding {
    margin-top : 12px;
}

.ifcp-outstanding-title {
    display        : flex;
    align-items    : center;
    gap            : 6px;
    font-size      : 10.5px;
    font-weight    : 700;
    letter-spacing : .8px;
    text-transform : uppercase;
    color          : #94a3b8;
    margin-bottom  : 8px;
}

.ifcp-outstanding-title i {
    color     : #d97706;
    font-size : 13px;
}

.ifcp-outstanding-badge {
    display       : inline-flex;
    align-items   : center;
    gap           : 4px;
    padding       : 2px 9px;
    background    : #fef3c7;
    border        : 1px solid #fde68a;
    border-radius : 20px;
    font-size     : 11px;
    font-weight   : 700;
    color         : #92400e;
}

/* Outstanding table */
.if-out-table {
    width           : 100%;
    border-collapse : collapse;
    font-size       : 11px;
}

.if-out-table thead tr {
    background : #f1f5f9;
}

.if-out-table thead th {
    padding        : 6px 8px;
    font-size      : 10px;
    font-weight    : 700;
    letter-spacing : .5px;
    text-transform : uppercase;
    color          : #94a3b8;
    border-bottom  : 1px solid #e2e8f0;
    white-space    : nowrap;
}

.if-out-table tbody td {
    padding       : 5px 8px;
    border-bottom : 1px solid #f1f5f9;
    color         : #334155;
    font-size     : 11px;
}

.if-out-table tbody tr:last-child td {
    border-bottom : none;
}

.if-out-table .if-out-link {
    color       : #056464;
    font-weight : 600;
    text-decoration : none;
}

.if-out-table .if-out-link:hover {
    text-decoration : underline;
}

.if-out-table .if-out-bal {
    color       : #dc2626;
    font-weight : 700;
}

.if-no-outstanding {
    padding       : 10px;
    text-align    : center;
    font-size     : 12px;
    color         : #16a34a;
    background    : #f0fdf4;
    border-radius : 7px;
    border        : 1px solid #bbf7d0;
}

/* ── Discount toggle switch ────────────────────────────────────── */
.if-disc-toggle {
    display     : flex;
    align-items : center;
    gap         : 8px;
}

.if-disc-toggle-lbl {
    font-size   : 12px;
    font-weight : 600;
    color       : #475569;
    white-space : nowrap;
}

.if-switch {
    position : relative;
    display  : inline-block;
    width    : 40px;
    height   : 22px;
}

.if-switch input {
    opacity  : 0;
    width    : 0;
    height   : 0;
    position : absolute;
}

.if-switch-track {
    position      : absolute;
    inset         : 0;
    background    : #cbd5e1;
    border-radius : 22px;
    cursor        : pointer;
    transition    : background .18s;
}

.if-switch-track::after {
    content       : '';
    position      : absolute;
    left          : 3px;
    top           : 3px;
    width         : 16px;
    height        : 16px;
    border-radius : 50%;
    background    : #ffffff;
    box-shadow    : 0 1px 3px rgba(0,0,0,.18);
    transition    : transform .18s;
}

.if-switch input:checked ~ .if-switch-track {
    background : #056464;
}

.if-switch input:checked ~ .if-switch-track::after {
    transform : translateX(18px);
}

/* ── Line items table ──────────────────────────────────────────── */
.if-table-wrap {
    overflow-x    : auto;
    border-bottom : 1px solid #e2e8f0;
}

.if-table {
    width           : 100%;
    border-collapse : collapse;
    font-size       : 12.5px;
    min-width       : 600px;
}

.if-table thead tr {
    background : #f8fafc;
}

.if-table thead th {
    padding        : 9px 7px;
    font-size      : 10px;
    font-weight    : 700;
    letter-spacing : .5px;
    text-transform : uppercase;
    color          : #94a3b8;
    border-bottom  : 1px solid #e2e8f0;
    white-space    : nowrap;
}

.if-table tbody td {
    padding        : 5px 4px;
    border-bottom  : 1px solid #f1f5f9;
    vertical-align : top;
}

.if-table tbody tr:last-child td {
    border-bottom : none;
}

.if-col-num   { width: 28px; }
.if-col-unit  { width: 70px; }
.if-col-qty   { width: 70px; }
.if-col-price { width: 110px; }
.if-col-disc  { width: 130px; }
.if-col-tax   { width: 70px; }
.if-col-total { width: 100px; }
.if-col-del   { width: 36px; }

/* Row number bubble */
.if-row-num {
    width           : 22px;
    height          : 22px;
    display         : flex;
    align-items     : center;
    justify-content : center;
    background      : #f1f5f9;
    border-radius   : 50%;
    font-size       : 10px;
    font-weight     : 700;
    color           : #94a3b8;
    margin-top      : 7px;
}

/* Cell inputs */
.if-cell {
    width         : 100%;
    padding       : 6px 7px;
    border        : 1.5px solid #e2e8f0;
    border-radius : 6px;
    font-size     : 12.5px;
    color         : #0d1b2a;
    background    : #ffffff;
    font-family   : inherit;
    outline       : none;
    transition    : border-color .12s;
    box-sizing    : border-box;
}

.if-cell:focus {
    border-color : #056464;
}

.if-cell-desc {
    font-size  : 11.5px;
    margin-top : 3px;
    color      : #64748b;
}

.if-cell-sm {
    font-size : 11.5px;
}

.if-cell-total {
    background   : #f0fdf4;
    color        : #15803d;
    font-weight  : 700;
    text-align   : right;
    cursor       : default;
}

/* Delete button */
.if-del-btn {
    background    : none;
    border        : 1px solid #e2e8f0;
    border-radius : 6px;
    padding       : 5px 7px;
    cursor        : pointer;
    color         : #dc2626;
    font-size     : 13px;
    transition    : background .12s, border-color .12s;
    margin-top    : 4px;
}

.if-del-btn:hover {
    background   : #fce8e8;
    border-color : #f5a9a9;
}

/* ── Totals panel ──────────────────────────────────────────────── */
.if-totals {
    display         : flex;
    justify-content : flex-end;
    padding         : 14px 16px;
    border-top      : 1px solid #e2e8f0;
    background      : #f8fafc;
}

.if-totals-inner {
    width : 310px;
}

.if-totals-row {
    display         : flex;
    align-items     : center;
    justify-content : space-between;
    gap             : 8px;
    padding         : 4px 0;
    font-size       : 13px;
}

.if-totals-lbl {
    color       : #64748b;
    font-weight : 500;
    white-space : nowrap;
}

.if-totals-val {
    font-weight : 600;
    color       : #0d1b2a;
}

.if-val-disc {
    color : #d97706;
}

.if-totals-divider {
    border-top : 1px solid #e2e8f0;
    margin     : 6px 0;
}

.if-whole-disc {
    margin-bottom : 4px;
}

.if-whole-tax {
    margin-bottom : 4px;
}

/* Mini select and input inside totals panel */
.if-mini-select {
    padding       : 4px 8px;
    border        : 1.5px solid #e2e8f0;
    border-radius : 6px;
    font-size     : 12px;
    color         : #0d1b2a;
    background    : #ffffff;
    font-family   : inherit;
    outline       : none;
    cursor        : pointer;
    transition    : border-color .12s;
}

.if-mini-select:focus {
    border-color : #056464;
}

.if-mini-input {
    width         : 90px;
    padding       : 4px 8px;
    border        : 1.5px solid #e2e8f0;
    border-radius : 6px;
    font-size     : 12px;
    color         : #0d1b2a;
    font-family   : inherit;
    text-align    : right;
    outline       : none;
    transition    : border-color .12s;
}

.if-mini-input:focus {
    border-color : #056464;
}

.if-pct {
    font-size   : 11.5px;
    font-weight : 600;
    color       : #94a3b8;
}

/* Grand total bar */
.if-grand-total {
    display         : flex;
    align-items     : center;
    justify-content : space-between;
    padding         : 11px 13px;
    background      : #056464;
    border-radius   : 8px;
    margin-top      : 10px;
}

.if-gt-lbl {
    font-size      : 10.5px;
    font-weight    : 700;
    letter-spacing : 1px;
    text-transform : uppercase;
    color          : rgba(255,255,255,.75);
}

.if-gt-val {
    font-size   : 19px;
    font-weight : 800;
    color       : #ffffff;
}

/* ── Right-column summary card ─────────────────────────────────── */
.if-sum-row {
    display         : flex;
    align-items     : center;
    justify-content : space-between;
    padding         : 5px 0;
    font-size       : 12.5px;
    border-bottom   : 1px solid #f1f5f9;
}

.if-sum-row:last-of-type {
    border-bottom : none;
}

.if-sum-lbl {
    color : #64748b;
}

.if-sum-val {
    font-weight : 600;
    color       : #0d1b2a;
}

.if-sum-grand {
    display         : flex;
    align-items     : center;
    justify-content : space-between;
    padding         : 12px 13px;
    background      : #056464;
    border-radius   : 8px;
    margin-top      : 10px;
}

.if-sum-grand-lbl {
    font-size      : 10.5px;
    font-weight    : 700;
    letter-spacing : 1px;
    text-transform : uppercase;
    color          : rgba(255,255,255,.75);
}

.if-sum-grand-val {
    font-size   : 18px;
    font-weight : 800;
    color       : #ffffff;
}

/* ── Footer / submit bar ───────────────────────────────────────── */
.if-footer {
    display         : flex;
    align-items     : center;
    justify-content : space-between;
    padding         : 13px 16px;
    background      : #ffffff;
    border          : 1px solid #e2e8f0;
    border-radius   : 10px;
    margin-top      : 0;
    gap             : 10px;
    flex-wrap       : wrap;
}

.if-footer-hint {
    font-size   : 12px;
    color       : #94a3b8;
    display     : flex;
    align-items : center;
    gap         : 5px;
}

/* ── Responsive ────────────────────────────────────────────────── */
@media (max-width: 991px) {
    .if-summary-sticky {
        position : static;
    }
}

@media (max-width: 768px) {
    .ifcp-grid {
        grid-template-columns : 1fr 1fr;
    }

    .ifsec-head {
        flex-direction : column;
        align-items    : flex-start;
    }

    .ifsec-head-right {
        width : 100%;
    }

    .if-footer {
        flex-direction : column;
        align-items    : flex-start;
    }
}    
</style>
<div class="container-fluid">

    <!-- ── Page Header ──────────────────────────────────────────── -->
    <div class="fin-page-header mb-3">
        <div class="fin-page-icon me-3">
            <i class="ti ti-file-invoice"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fin-page-title">
                <?= $page_mode === 'add'
                    ? 'New Invoice'
                    : 'Edit Invoice #' . html_escape($invoice->invoice_number ?? '') ?>
            </div>
            <div class="fin-page-sub">
                <?= $page_mode === 'add'
                    ? 'Select a client, add line items, then save or send.'
                    : 'Update invoice details and line items below.' ?>
            </div>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="<?= site_url('finance/invoices') ?>" class="btn btn-light-primary btn-header">
                <i class="ti ti-arrow-back-up me-1"></i> Back
            </a>
        </div>
    </div>

    <?php if (validation_errors()): ?>
        <div class="alert alert-danger mb-3">
            <?= validation_errors('<p class="mb-1">', '</p>') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $form_action ?>" id="invoiceForm" autocomplete="off" class="app-form">
    <div class="row g-3">

        <!-- ══ LEFT COLUMN ══════════════════════════════════════════ -->
        <div class="col-lg-8">

            <!-- ── Section: Client ──────────────────────────────── -->
            <div class="ifsec">
                <div class="ifsec-head">
                    <div class="ifsec-title"><i class="ti ti-building"></i> Client</div>
                </div>
                <div class="ifsec-body">

                    <div class="ifcp" id="clientPanel">

                        <div class="ifcp-select-wrap">
                            <label class="ifcp-select-lbl">
                                <i class="ti ti-building"></i>
                                Select Client <span class="ifcp-req">*</span>
                            </label>
                            <select name="client_id" id="clientSelect" class="form-select" required>
                                <option value="">— Search and select a client —</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= (int)$c->id ?>"
                                            <?= (isset($invoice) && $invoice->client_id == $c->id) ? 'selected' : '' ?>>
                                        <?= html_escape($c->name) ?>
                                        <?php if (!empty($c->client_code)): ?>(<?= html_escape($c->client_code) ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="ifcp-loading" id="clientLoading">
                            <i class="ti ti-loader ti-spin me-1"></i> Loading…
                        </div>

                        <div class="ifcp-placeholder" id="clientPlaceholder">
                            <i class="ti ti-user-search"></i>
                            Select a client above to see their details and outstanding balance
                        </div>

                        <div class="ifcp-info" id="clientInfo">

                            <div class="ifcp-info-hd">
                                <div>
                                    <div class="ifcp-name" id="ciName">—</div>
                                    <span class="ifcp-code" id="ciCode"></span>
                                </div>
                                <a href="#" id="ciViewLink" target="_blank" class="btn btn-light-primary btn-header">
                                    <i class="ti ti-external-link me-1"></i>View Client
                                </a>
                            </div>

                            <div class="ifcp-grid">
                                <div>
                                    <div class="ifcp-lbl">Billing Address</div>
                                    <div class="ifcp-val" id="ciAddress">—</div>
                                </div>
                                <div>
                                    <div class="ifcp-lbl">Contact</div>
                                    <div class="ifcp-val" id="ciContact">—</div>
                                    <div class="ifcp-val ifcp-val-sm" id="ciEmail"></div>
                                    <div class="ifcp-val ifcp-val-sm" id="ciPhone"></div>
                                </div>
                                <div>
                                    <div class="ifcp-lbl">Billing Model</div>
                                    <div class="ifcp-val" id="ciBilling">—</div>
                                </div>
                            </div>

                            <!-- Outstanding invoices -->
                            <div class="ifcp-outstanding">
                                <div class="ifcp-outstanding-title">
                                    <i class="ti ti-alert-circle"></i>
                                    Outstanding Balance:
                                    <span class="ifcp-outstanding-badge d-none" id="outstandingBadge">
                                        <span id="outstandingTotal"></span>
                                    </span>
                                </div>
                                <div id="outstandingTableWrap"></div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- ── Section: Line Items ───────────────────────────── -->
            <div class="ifsec ifreveal" id="lineItemsSection" style="display:none;">
                <div class="ifsec-head">
                    <div class="ifsec-title"><i class="ti ti-list-details"></i> Line Items</div>
                    <div class="ifsec-head-right">

                        <!-- Discount toggle -->
                        <div class="if-disc-toggle" id="discToggleWrap">
                            <span class="if-disc-toggle-lbl">Discount per line</span>
                            <label class="if-switch">
                                <input type="checkbox" id="discToggle" name="discount_per_line" value="1"
                                       <?= !empty($invoice->discount_per_line) ? 'checked' : '' ?>>
                                <span class="if-switch-track"></span>
                            </label>
                        </div>

                        <button type="button" id="btnAddItem" class="btn btn-light-primary btn-header">
                            <i class="ti ti-plus"></i> Add Row
                        </button>
                    </div>
                </div>

                <div class="if-table-wrap">
                    <table class="if-table" id="lineItemsTable">
                        <thead id="lineItemsHead">
                            <tr>
                                <th class="if-col-num">#</th>
                                <th class="if-col-item">Item &amp; Description</th>
                                <th class="if-col-unit">Unit</th>
                                <th class="if-col-qty">Qty</th>
                                <th class="if-col-price">Unit Price</th>
                                <th class="if-col-disc if-perline-col" style="display:none;">Discount</th>
                                <th class="if-col-tax if-perline-col" style="display:none;">Tax %</th>
                                <th class="if-col-total">Total</th>
                                <th class="if-col-del"></th>
                            </tr>
                        </thead>
                        <tbody id="lineItemsBody">
                            <?php if (!empty($items)):
                                foreach ($items as $idx => $row):
                                    include('_line_item_row.php');
                                endforeach;
                            else:
                                $idx = 0; $row = null;
                                include('_line_item_row.php');
                            endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals panel -->
                <div class="if-totals">
                    <div class="if-totals-inner">

                        <!-- Whole-invoice discount (shown when per-line is OFF) -->
                        <div class="if-whole-disc" id="wholeDiscWrap">
                            <div class="if-totals-row">
                                <span class="if-totals-lbl">Discount Type</span>
                                <select id="wholeDiscType" name="whole_discount_type" class="if-mini-select">
                                    <option value="none"    <?= ($invoice->whole_discount_type ?? 'none') === 'none'    ? 'selected' : '' ?>>None</option>
                                    <option value="percent" <?= ($invoice->whole_discount_type ?? 'none') === 'percent' ? 'selected' : '' ?>>Percent (%)</option>
                                    <option value="fixed"   <?= ($invoice->whole_discount_type ?? 'none') === 'fixed'   ? 'selected' : '' ?>>Fixed amount</option>
                                </select>
                            </div>
                            <div class="if-totals-row" id="wholeDiscValRow" style="display:none;">
                                <span class="if-totals-lbl">Discount Value</span>
                                <input type="number" id="wholeDiscVal" name="whole_discount_value"
                                       class="if-mini-input" step="0.01" min="0"
                                       value="<?= html_escape($invoice->whole_discount_value ?? 0) ?>">
                            </div>
                        </div>

                        <!-- Whole-invoice tax (shown when per-line is OFF) -->
                        <div class="if-whole-tax" id="wholeTaxWrap">
                            <div class="if-totals-row">
                                <span class="if-totals-lbl">Tax Rate</span>
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <input type="number" id="wholeTaxRate" name="tax_rate"
                                           class="if-mini-input" step="0.01" min="0" max="100"
                                           value="<?= html_escape($invoice->tax_rate ?? 0) ?>">
                                    <span class="if-pct">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="if-totals-divider"></div>

                        <div class="if-totals-row">
                            <span class="if-totals-lbl">Subtotal</span>
                            <span class="if-totals-val" id="dispSubtotal">0.00</span>
                        </div>
                        <div class="if-totals-row" id="dispDiscRow" style="display:none;">
                            <span class="if-totals-lbl">Discount</span>
                            <span class="if-totals-val if-val-disc" id="dispDiscount">−0.00</span>
                        </div>
                        <div class="if-totals-row" id="dispTaxRow" style="display:none;">
                            <span class="if-totals-lbl" id="dispTaxLabel">Tax</span>
                            <span class="if-totals-val" id="dispTax">0.00</span>
                        </div>

                        <div class="if-grand-total">
                            <span class="if-gt-lbl">Total Due</span>
                            <span class="if-gt-val" id="dispTotal">0.00</span>
                        </div>

                        <!-- Hidden fields sent to controller — exact column names from fin_invoices -->
                        <input type="hidden" name="subtotal"        id="hidSubtotal"   value="">
                        <input type="hidden" name="discount_amount" id="hidDiscount"   value="">
                        <input type="hidden" name="tax_amount"      id="hidTaxAmount"  value="">
                        <input type="hidden" name="total_amount"    id="hidTotal"      value="">
                        <input type="hidden" name="balance_due"     id="hidBalanceDue" value="">

                    </div>
                </div>
            </div>

        </div><!-- /col-lg-8 -->


        <!-- ══ RIGHT COLUMN ═════════════════════════════════════════ -->
        <div class="col-lg-4">

            <!-- Invoice Details -->
            <div class="ifsec ifreveal" id="invoiceDetailsSection" style="display:none;">
                <div class="ifsec-head">
                    <div class="ifsec-title"><i class="ti ti-file-description"></i> Invoice Details</div>
                </div>
                <div class="ifsec-body">
                    <div class="row g-2">

                        <div class="col-12">
                            <label class="if-lbl">Invoice Subject</label>
                            <input type="text" name="subject" class="form-control"
                                   value="<?= html_escape($invoice->po_number ?? '') ?>"
                                   placeholder="Enter invoice subject / title">
                        </div>
                        
                        <?php $autoNum = generate_invoice_number(); ?>
                        <div class="col-6">
                            <label class="if-lbl">Invoice Number <span class="ifcp-req">*</span></label>
                            <input type="text" name="invoice_number" class="form-control if-readonly-input"
                                   value="<?= $page_mode === 'add' ? html_escape($autoNum) : html_escape($invoice->invoice_number ?? '') ?>"
                                   readonly>
                        </div>

                        <div class="col-6">
                            <label class="if-lbl">Status</label>
                            <select name="status" class="form-select">
                                <?php
                                $statuses   = ['draft','sent','viewed','partial','paid','overdue','cancelled'];
                                $cur_status = $invoice->status ?? 'draft';
                                foreach ($statuses as $s): ?>
                                    <option value="<?= $s ?>" <?= $cur_status === $s ? 'selected' : '' ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="if-lbl">Invoice Date <span class="ifcp-req">*</span></label>
                            <input type="date" name="invoice_date" class="form-control" required
                                   value="<?= html_escape($invoice->invoice_date ?? date('Y-m-d')) ?>">
                        </div>

                        <div class="col-6">
                            <label class="if-lbl">Due Date</label>
                            <input type="date" name="due_date" class="form-control"
                                   value="<?= html_escape($invoice->due_date ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="if-lbl">PO Number</label>
                            <input type="text" name="po_number" class="form-control"
                                   value="<?= html_escape($invoice->po_number ?? '') ?>"
                                   placeholder="Client purchase order reference">
                        </div>

                    </div>
                </div>
            </div>

            <!-- Notes & Terms -->
            <div class="ifsec ifreveal" id="notesSection" style="display:none;">
                <div class="ifsec-head">
                    <div class="ifsec-title"><i class="ti ti-notes"></i> Notes &amp; Terms</div>
                </div>
                <div class="ifsec-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="if-lbl">Invoice Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Notes visible on the invoice…"><?= html_escape($invoice->notes ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="if-lbl">Payment Terms</label>
                            <textarea name="terms" class="form-control" rows="3"
                                      placeholder="e.g. Net 30, due on receipt…"><?= html_escape($invoice->terms ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="ifsec if-summary-sticky ifreveal" id="summaryCard" style="display:none;">
                <div class="ifsec-head">
                    <div class="ifsec-title"><i class="ti ti-calculator"></i> Summary</div>
                </div>
                <div class="ifsec-body">
                    <div class="if-sum-row">
                        <span class="if-sum-lbl">Subtotal</span>
                        <span class="if-sum-val" id="sidSubtotal">0.00</span>
                    </div>
                    <div class="if-sum-row" id="sidDiscRow" style="display:none;">
                        <span class="if-sum-lbl">Discount</span>
                        <span class="if-sum-val if-val-disc" id="sidDiscount">−0.00</span>
                    </div>
                    <div class="if-sum-row" id="sidTaxRow" style="display:none;">
                        <span class="if-sum-lbl" id="sidTaxLabel">Tax</span>
                        <span class="if-sum-val" id="sidTax">0.00</span>
                    </div>
                    <div class="if-sum-grand">
                        <span class="if-sum-grand-lbl">Total Due</span>
                        <span class="if-sum-grand-val" id="sidTotal">0.00</span>
                    </div>
                </div>
            </div>

            <!-- Footer / Submit -->
            <div class="if-footer ifreveal" id="invoiceFooter" style="display:none;">
                <span class="if-footer-hint">
                    <i class="ti ti-info-circle"></i>
                    Fields marked <span class="ifcp-req">*</span> are required.
                </span>
                <div class="d-flex gap-2">
                    <a href="<?= site_url('finance/invoices') ?>" class="btn btn-outline-secondary btn-header">
                        Cancel
                    </a>
                    <?php if ($page_mode === 'add'): ?>
                        <button type="submit" name="save_action" value="save_send"
                                class="btn btn-outline-primary btn-header">
                            <i class="ti ti-send me-1"></i> Create &amp; Send
                        </button>
                    <?php endif; ?>
                    <button type="submit" name="save_action" value="save"
                            class="btn btn-primary btn-header">
                        <i class="ti ti-device-floppy me-1"></i>
                        <?= $page_mode === 'add' ? 'Create Invoice' : 'Save Changes' ?>
                    </button>
                </div>
            </div>

        </div><!-- /col-lg-4 -->

    </div><!-- /row -->
    </form>

</div><!-- /container-fluid -->

<script>
/**
 * invoice-form.js
 * Handles: client AJAX, discount-mode toggle, line item add/remove, calculations
 * No jQuery dependency. Plain ES5-compatible vanilla JS.
 */

(function () {
    'use strict';

    /* ── Helpers ──────────────────────────────────────────────────── */
    function ge(id) { return document.getElementById(id); }
    function show(el, d) { if (el) el.style.display = (d || 'block'); }
    function hide(el)    { if (el) el.style.display = 'none'; }
    function fmt(n)      { return parseFloat(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function num(v)      { return Math.max(0, parseFloat(v) || 0); }

    /* ── Refs ─────────────────────────────────────────────────────── */
    var clientSelect      = ge('clientSelect');
    var clientPanel       = ge('clientPanel');
    var clientLoading     = ge('clientLoading');
    var clientPlaceholder = ge('clientPlaceholder');
    var clientInfo        = ge('clientInfo');
    var tbody             = ge('lineItemsBody');
    var addBtn            = ge('btnAddItem');
    var discToggle        = ge('discToggle');
    var wholeDiscWrap     = ge('wholeDiscWrap');
    var wholeTaxWrap      = ge('wholeTaxWrap');
    var wholeDiscType     = ge('wholeDiscType');
    var wholeDiscVal      = ge('wholeDiscVal');
    var wholeDiscValRow   = ge('wholeDiscValRow');
    var wholeTaxRate      = ge('wholeTaxRate');

    /* Sections to reveal after client is selected */
    var REVEAL_IDS = [
        'lineItemsSection',
        'invoiceDetailsSection',
        'notesSection',
        'summaryCard',
        'invoiceFooter'
    ];

    function showSections() {
        REVEAL_IDS.forEach(function(id) {
            var el = ge(id);
            if (!el) return;
            el.style.display = (id === 'invoiceFooter') ? 'flex' : 'block';
        });
    }

    function hideSections() {
        REVEAL_IDS.forEach(function(id) { hide(ge(id)); });
    }

    /* ── Discount mode ────────────────────────────────────────────── */
    /* perLine = true  → show disc/tax columns on each row, hide whole-invoice inputs  */
    /* perLine = false → hide row columns, show whole-invoice disc/tax above subtotal  */

    var perLine = discToggle ? discToggle.checked : false;

    function applyDiscountMode() {
        perLine = discToggle ? discToggle.checked : false;

        /* Column headers */
        var perLineCols = document.querySelectorAll('.if-perline-col');
        perLineCols.forEach(function(el) {
            el.style.display = perLine ? '' : 'none';
        });

        /* Row cells */
        var rowCells = document.querySelectorAll('.if-row-perline');
        rowCells.forEach(function(el) {
            el.style.display = perLine ? '' : 'none';
        });

        /* Whole-invoice panels */
        if (wholeDiscWrap) wholeDiscWrap.style.display = perLine ? 'none' : 'block';
        if (wholeTaxWrap)  wholeTaxWrap.style.display  = perLine ? 'none' : 'block';

        recalcAll();
    }

    if (discToggle) {
        discToggle.addEventListener('change', applyDiscountMode);
    }

    /* Show/hide whole-discount value input based on type select */
    if (wholeDiscType) {
        wholeDiscType.addEventListener('change', function() {
            if (wholeDiscValRow) {
                wholeDiscValRow.style.display = (this.value !== 'none') ? 'flex' : 'none';
            }
            recalcAll();
        });

        /* Init on load */
        if (wholeDiscValRow) {
            wholeDiscValRow.style.display = (wholeDiscType.value !== 'none') ? 'flex' : 'none';
        }
    }

    /* ── Calculations ─────────────────────────────────────────────── */

    function calcRow(tr) {
        var qty   = num(tr.querySelector('[data-role="qty"]')   ? tr.querySelector('[data-role="qty"]').value   : 0);
        var price = num(tr.querySelector('[data-role="price"]') ? tr.querySelector('[data-role="price"]').value : 0);
        var gross = qty * price;

        var lineTotal = gross;
        var lineTax   = 0;

        if (perLine) {
            var dtypeEl = tr.querySelector('[data-role="disc-type"]');
            var dvalEl  = tr.querySelector('[data-role="disc-val"]');
            var taxEl   = tr.querySelector('[data-role="tax-rate"]');
            var dAmtEl  = tr.querySelector('[data-role="disc-amt"]');

            var dtype = dtypeEl ? dtypeEl.value : 'none';
            var dval  = num(dvalEl ? dvalEl.value : 0);
            var tax   = num(taxEl  ? taxEl.value  : 0);

            var damt = 0;
            if (dtype === 'percent') {
                damt = gross * (dval / 100);
            } else if (dtype === 'fixed') {
                damt = Math.min(dval, gross);
            }

            if (dAmtEl) dAmtEl.value = damt.toFixed(2);

            lineTotal = Math.max(0, gross - damt);
            lineTax   = lineTotal * (tax / 100);
        }

        var ltEl = tr.querySelector('[data-role="line-total"]');
        if (ltEl) ltEl.value = lineTotal.toFixed(2);

        return { lineTotal: lineTotal, lineTax: lineTax };
    }

    function recalcAll() {
        var subtotal = 0;
        var itemsTax = 0;

        if (tbody) {
            var rows = tbody.querySelectorAll('tr.if-item-row');
            rows.forEach(function(tr) {
                var r = calcRow(tr);
                subtotal += r.lineTotal;
                itemsTax += r.lineTax;
            });
        }

        /* Discount applied to whole invoice */
        var discAmt  = 0;
        var taxAmt   = 0;
        var taxRate  = 0;
        var afterDisc = subtotal;

        if (!perLine) {
            var dtype = wholeDiscType ? wholeDiscType.value : 'none';
            var dval  = num(wholeDiscVal  ? wholeDiscVal.value  : 0);
            taxRate   = num(wholeTaxRate  ? wholeTaxRate.value  : 0);

            if (dtype === 'percent') {
                discAmt = subtotal * (dval / 100);
            } else if (dtype === 'fixed') {
                discAmt = Math.min(dval, subtotal);
            }

            afterDisc = Math.max(0, subtotal - discAmt);
            taxAmt    = afterDisc * (taxRate / 100);
        } else {
            taxAmt = itemsTax;
        }

        var total = afterDisc + taxAmt;

        /* ── Update inline totals panel ── */
        setText('dispSubtotal', fmt(subtotal));

        var discRow = ge('dispDiscRow');
        if (discRow) {
            discRow.style.display = discAmt > 0 ? 'flex' : 'none';
            setText('dispDiscount', '−' + fmt(discAmt));
        }

        var taxRow = ge('dispTaxRow');
        if (taxRow) {
            var hasTax = taxAmt > 0;
            taxRow.style.display = hasTax ? 'flex' : 'none';
            if (hasTax) {
                setText('dispTaxLabel', perLine ? 'Tax (per line)' : 'Tax (' + taxRate + '%)');
                setText('dispTax', fmt(taxAmt));
            }
        }

        setText('dispTotal', fmt(total));

        /* ── Update right-column summary ── */
        setText('sidSubtotal', fmt(subtotal));

        var sidDiscRow = ge('sidDiscRow');
        if (sidDiscRow) {
            sidDiscRow.style.display = discAmt > 0 ? 'flex' : 'none';
            setText('sidDiscount', '−' + fmt(discAmt));
        }

        var sidTaxRow = ge('sidTaxRow');
        if (sidTaxRow) {
            sidTaxRow.style.display = taxAmt > 0 ? 'flex' : 'none';
            if (taxAmt > 0) {
                setText('sidTaxLabel', perLine ? 'Tax (per line)' : 'Tax (' + taxRate + '%)');
                setText('sidTax', fmt(taxAmt));
            }
        }

        setText('sidTotal', fmt(total));

        /* ── Hidden fields posted to server (exact fin_invoices column names) ── */
        setVal('hidSubtotal',   subtotal.toFixed(2));
        setVal('hidDiscount',   discAmt.toFixed(2));
        setVal('hidTaxAmount',  taxAmt.toFixed(2));
        setVal('hidTotal',      total.toFixed(2));
        setVal('hidBalanceDue', total.toFixed(2));   /* starts equal to total, payments reduce it server-side */
    }

    function setText(id, v) { var e = ge(id); if (e) e.textContent = v; }
    function setVal(id, v)  { var e = ge(id); if (e) e.value = v; }

    /* ── Row counter ──────────────────────────────────────────────── */
    function refreshRowNums() {
        if (!tbody) return;
        var rows = tbody.querySelectorAll('tr.if-item-row');
        rows.forEach(function(tr, i) {
            var b = tr.querySelector('.if-row-num');
            if (b) b.textContent = i + 1;
            /* update sort_order hidden field if present */
            var so = tr.querySelector('[data-role="sort-order"]');
            if (so) so.value = i;
        });
    }

    /* ── Build a new row ──────────────────────────────────────────── */
    function makeRow(n) {
        /* We build the row as a real TR element so there are zero string-parsing issues */
        var tr = document.createElement('tr');
        tr.className = 'if-item-row';

        /* # */
        var tdNum = document.createElement('td');
        var numDiv = document.createElement('div');
        numDiv.className = 'if-row-num';
        numDiv.textContent = n;
        tdNum.appendChild(numDiv);
        tr.appendChild(tdNum);

        /* Item + Description */
        var tdItem = document.createElement('td');
        tdItem.innerHTML =
            '<input type="text" name="item_name[]" class="if-cell" placeholder="Item name" style="margin-bottom:3px;">' +
            '<input type="text" name="item_description[]" class="if-cell if-cell-desc" placeholder="Description (optional)">';
        tr.appendChild(tdItem);

        /* Unit */
        var tdUnit = document.createElement('td');
        tdUnit.innerHTML = '<input type="text" name="item_unit[]" class="if-cell if-cell-sm" placeholder="hrs">';
        tr.appendChild(tdUnit);

        /* Qty */
        var tdQty = document.createElement('td');
        tdQty.innerHTML = '<input type="number" name="item_quantity[]" class="if-cell" data-role="qty" step="0.01" min="0" value="1">';
        tr.appendChild(tdQty);

        /* Unit Price */
        var tdPrice = document.createElement('td');
        tdPrice.innerHTML = '<input type="number" name="item_unit_price[]" class="if-cell" data-role="price" step="0.01" min="0" value="0.00">';
        tr.appendChild(tdPrice);

        /* Discount (per-line column) */
        var tdDisc = document.createElement('td');
        tdDisc.className = 'if-row-perline';
        tdDisc.style.display = perLine ? '' : 'none';
        tdDisc.innerHTML =
            '<select name="item_discount_type[]" class="if-cell if-cell-sm" data-role="disc-type" style="margin-bottom:3px;">' +
                '<option value="none">None</option>' +
                '<option value="percent">% Percent</option>' +
                '<option value="fixed">$ Fixed</option>' +
            '</select>' +
            '<input type="number" name="item_discount_amount[]" class="if-cell if-cell-sm" data-role="disc-val" step="0.01" min="0" value="0" placeholder="Value">' +
            '<input type="hidden" name="item_discount_calculated[]" data-role="disc-amt" value="0.00">';
        tr.appendChild(tdDisc);

        /* Tax % (per-line column) */
        var tdTax = document.createElement('td');
        tdTax.className = 'if-row-perline';
        tdTax.style.display = perLine ? '' : 'none';
        tdTax.innerHTML = '<input type="number" name="item_tax_rate[]" class="if-cell if-cell-sm" data-role="tax-rate" step="0.01" min="0" max="100" value="0">';
        tr.appendChild(tdTax);

        /* Line Total */
        var tdTotal = document.createElement('td');
        tdTotal.innerHTML = '<input type="number" name="item_line_total[]" class="if-cell if-cell-total" data-role="line-total" step="0.01" value="0.00" readonly tabindex="-1">';
        tr.appendChild(tdTotal);

        /* Delete */
        var tdDel = document.createElement('td');
        tdDel.innerHTML = '<button type="button" class="if-del-btn" title="Remove row"><i class="ti ti-trash"></i></button>';
        tr.appendChild(tdDel);

        /* Hidden sort_order — maps to fin_invoice_items.sort_order */
        var soInput = document.createElement('input');
        soInput.type = 'hidden';
        soInput.name = 'item_sort_order[]';
        soInput.setAttribute('data-role', 'sort-order');
        soInput.value = n - 1;
        tr.appendChild(soInput);

        return tr;
    }

    /* ── Add Row ──────────────────────────────────────────────────── */
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            if (!tbody) return;
            var n = tbody.querySelectorAll('tr.if-item-row').length + 1;
            var newRow = makeRow(n);
            tbody.appendChild(newRow);
            /* Focus the item name input in the new row */
            var nameInput = newRow.querySelector('input[name="item_name[]"]');
            if (nameInput) nameInput.focus();
            recalcAll();
        });
    }

    /* ── Remove Row ───────────────────────────────────────────────── */
    /*
     * Using event delegation on the tbody so it works for both
     * PHP-rendered rows (which may not have the button class yet)
     * and JS-added rows. This is the fix for the remove not working.
     */
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.if-del-btn');
        if (!btn || !tbody) return;

        var tr = btn.closest('tr.if-item-row');
        if (!tr) return;

        var rows = tbody.querySelectorAll('tr.if-item-row');

        if (rows.length <= 1) {
            /* Last row: clear it instead of removing */
            tr.querySelectorAll('input[type="text"]').forEach(function(inp) { inp.value = ''; });
            tr.querySelectorAll('input[type="number"]').forEach(function(inp) {
                inp.value = inp.getAttribute('data-role') === 'qty' ? '1' : '0';
            });
            tr.querySelectorAll('select').forEach(function(sel) { sel.selectedIndex = 0; });
        } else {
            tr.parentNode.removeChild(tr);
            refreshRowNums();
        }

        recalcAll();
    });

    /* ── Live recalc ──────────────────────────────────────────────── */
    document.addEventListener('input', function(e) {
        var inRow = e.target.closest('tr.if-item-row');
        if (inRow ||
            e.target === wholeDiscVal ||
            e.target === wholeTaxRate) {
            recalcAll();
        }
    });

    document.addEventListener('change', function(e) {
        var inRow = e.target.closest('tr.if-item-row');
        if (inRow ||
            e.target === wholeDiscType ||
            e.target === wholeTaxRate) {
            recalcAll();
        }
    });

    /* ══════════════════════════════════════════════════════════════
       CLIENT PANEL AJAX
    ══════════════════════════════════════════════════════════════ */

    function showPlaceholder(msg) {
        if (!clientPlaceholder) return;
        if (msg) clientPlaceholder.innerHTML = msg;
        show(clientPlaceholder);
    }

    function renderClientInfo(client, outstanding) {
        var nameEl = ge('ciName');
        if (nameEl) nameEl.textContent =
            client.practice_legal_name || client.practice_name || client.company || '—';

        var codeEl = ge('ciCode');
        if (codeEl) {
            if (client.client_code) {
                codeEl.textContent = '#' + client.client_code;
                codeEl.style.display = 'inline';
            } else {
                codeEl.style.display = 'none';
            }
        }

        var viewLink = ge('ciViewLink');
        if (viewLink) viewLink.href = '<?= site_url('crm/clients/view') ?>/' + client.id;
        
        var contactEl = ge('ciContact');
        var emailEl   = ge('ciEmail');
        var phoneEl   = ge('ciPhone');
        if (contactEl) contactEl.textContent = client.primary_contact_name || '—';
        if (emailEl)   emailEl.textContent   = client.primary_email        || '';
        if (phoneEl)   phoneEl.textContent   = client.primary_phone        || '';

        var addrEl = ge('ciAddress');
        if (addrEl) {
            var parts = [
                client.address || '',
                [client.city, client.state, client.zip_code, client.country]
                    .filter(Boolean).join(', ')
            ].filter(Boolean);
            addrEl.innerHTML = parts.length ? parts.join('<br>') : '—';
        }

        var billEl = ge('ciBilling');
        if (billEl) {
            var bText = client.billing_model || '—';
            if (client.billing_model === 'percent' && client.rate_percent) {
                bText = 'Percent (' + client.rate_percent + '%)';
            } else if (client.billing_model === 'flat' && client.rate_flat) {
                bText = 'Flat ($' + parseFloat(client.rate_flat).toFixed(2) + ')';
            }
            billEl.textContent = bText;
        }

        renderOutstanding(outstanding);

        if (clientInfo)  clientInfo.classList.add('show');
        if (clientPanel) clientPanel.classList.add('loaded');
    }

    function renderOutstanding(outstanding) {
        var wrap    = ge('outstandingTableWrap');
        var badge   = ge('outstandingBadge');
        var totalEl = ge('outstandingTotal');
        if (!wrap) return;

        var invoices = (outstanding && Array.isArray(outstanding.invoices))
            ? outstanding.invoices : [];

        if (invoices.length === 0) {
            if (badge) badge.classList.add('d-none');
            wrap.innerHTML =
                '<div class="if-no-outstanding">' +
                '<i class="ti ti-circle-check me-1"></i>' +
                'No outstanding invoices — account is current.' +
                '</div>';
            return;
        }

        if (badge) badge.classList.remove('d-none');
        var total = parseFloat(outstanding.total_outstanding) || 0;
        if (totalEl) totalEl.textContent = '$' + fmt(total);

        var statusClass = {
            sent   : 'text-primary',
            viewed : 'text-info',
            partial: 'text-warning',
            overdue: 'text-danger'
        };

        var rows = invoices.map(function(inv) {
            var cls = statusClass[inv.status] || '';
            var bal = fmt(parseFloat(inv.balance_due)  || 0);
            var amt = fmt(parseFloat(inv.total_amount) || 0);
            return '<tr>' +
                '<td><a href="<?= site_url('finance/invoices/view') ?>/' + inv.id + '" target="_blank" class="if-out-link">' + inv.invoice_number + '</a></td>' +
                '<td>' + (inv.invoice_date || '—') + '</td>' +
                '<td>' + (inv.due_date    || '—') + '</td>' +
                '<td style="text-align:right;">$' + amt + '</td>' +
                '<td class="if-out-bal" style="text-align:right;">$' + bal + '</td>' +
                '<td><span class="' + cls + '">' + inv.status + '</span></td>' +
            '</tr>';
        }).join('');

        wrap.innerHTML =
            '<table class="if-out-table">' +
            '<thead><tr>' +
                '<th>Invoice #</th><th>Date</th><th>Due</th>' +
                '<th style="text-align:right;">Amount</th>' +
                '<th style="text-align:right;">Balance</th>' +
                '<th>Status</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table>';
    }

    function loadClient(clientId) {
        if (!clientId) {
            if (clientInfo)  clientInfo.classList.remove('show');
            if (clientPanel) clientPanel.classList.remove('loaded');
            if (clientLoading) clientLoading.classList.remove('show');
            showPlaceholder(null);
            hideSections();
            return;
        }

        hide(clientPlaceholder);
        if (clientInfo)    clientInfo.classList.remove('show');
        if (clientLoading) clientLoading.classList.add('show');

var url = '<?= site_url('finance/invoices/get_client_info') ?>?client_id=' + encodeURIComponent(clientId);

        fetch(url, {
            method     : 'GET',
            credentials: 'same-origin',
            headers    : {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            if (clientLoading) clientLoading.classList.remove('show');

            if (data.success) {
                renderClientInfo(data.client, data.outstanding);
                showSections();
                applyDiscountMode();
                recalcAll();
            } else {
                if (clientPanel) clientPanel.classList.remove('loaded');
                showPlaceholder(
                    '<i class="ti ti-alert-circle" style="font-size:22px;display:block;margin-bottom:6px;color:#d97706;"></i>' +
                    (data.message || 'Could not load client information.')
                );
            }
        })
        .catch(function(err) {
            if (clientLoading) clientLoading.classList.remove('show');
            if (clientPanel)   clientPanel.classList.remove('loaded');
            showPlaceholder(
                '<i class="ti ti-wifi-off" style="font-size:22px;display:block;margin-bottom:6px;color:#dc2626;"></i>' +
                'Failed to load client. Please refresh and try again.<br>' +
                '<small style="opacity:.6;">' + err.message + '</small>'
            );
            console.error('[InvoiceForm] loadClient:', err);
        });
    }

    if (clientSelect) {
        clientSelect.addEventListener('change', function() {
            loadClient(this.value);
        });
    }

    /* ── Init on page load ────────────────────────────────────────── */
    var preselected = clientSelect ? clientSelect.value : '';
    if (preselected) {
        /* Edit mode or validation-failed re-render: load client immediately */
        loadClient(preselected);
    } else {
        recalcAll();
    }

    /* Init discount mode from checkbox state (handles edit mode) */
    applyDiscountMode();

})();    
</script>