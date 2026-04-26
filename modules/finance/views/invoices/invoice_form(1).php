<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>

/* ── Client panel ─────────────────────────────────── */
.inv-client-panel {
    background: #f8fafc;
    border: 1px solid #94a3b8;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 16px;
    transition: border-color .15s;
}
.inv-client-panel.loaded { border-color: #94a3b8; }

.inv-client-select-wrap {
    padding: 14px 16px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
}
.inv-client-select-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.inv-client-placeholder {
    padding: 28px 16px;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
}
.inv-client-placeholder i { font-size: 28px; margin-bottom: 6px; display: block; }

/* Client info */
.inv-client-info { padding: 14px 16px; display: none; }
.inv-client-info.show { display: block; }

/* Client header */
.inv-client-hd {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
}
.inv-client-name {
    font-size: 15px;
    font-weight: 800;
    color: #0d1b2a;
    line-height: 1.3;
    margin-bottom: 3px;
}
.inv-client-code {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    color: #056464;
}

/* Detail grid */
.inv-client-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
    margin-bottom: 12px;
}
@media (max-width: 768px) { .inv-client-detail-grid { grid-template-columns: 1fr 1fr; } }

.inv-client-detail-lbl {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .7px;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 2px;
}
.inv-client-detail-val {
    font-size: 12.5px;
    font-weight: 500;
    color: #334155;
    line-height: 1.55;
}

/* Outstanding invoices */
.inv-outstanding-wrap { margin-top: 12px; }
.inv-outstanding-title {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.inv-outstanding-title i { color: #d97706; font-size: 13px; }
.inv-outstanding-badge {
    display: none;
    align-items: center;
    gap: 5px;
    padding: 2px 9px;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    color: #92400e;
}
.inv-outstanding-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.inv-outstanding-table thead tr { background: #f1f5f9; }
.inv-outstanding-table thead th {
    padding: 7px 10px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: uppercase;
    color: #94a3b8;
    border-bottom: 1px solid #e2e8f0;
}
.inv-outstanding-table tbody td {
    padding: 4px 10px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-size: 10px;
    font-weight: 400;
}
.inv-outstanding-table tbody tr:last-child td { border-bottom: none; }
.inv-outstanding-total-row td {
    padding: 8px 10px;
    font-weight: 700;
    color: #0d1b2a;
    border-top: 2px solid #e2e8f0;
    background: #f8fafc;
}
.inv-no-outstanding {
    padding: 10px;
    text-align: center;
    font-size: 12px;
    color: #16a34a;
    background: #f0fdf4;
    border-radius: 7px;
    border: 1px solid #bbf7d0;
}
/* Loading */
.inv-client-loading {
    padding: 20px;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
    display: none;
}
.inv-client-loading.show { display: block; }

/* ── Proposal-style section wrapper ───────────────── */
.inv-section {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(13,27,42,.05);
}
.inv-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.inv-section-head-left { display: flex; align-items: center; gap: 9px; }

.inv-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #0d1b2a;
    display: flex;
    align-items: center;
    gap: 7px;
}
.inv-section-title i { color: #056464; font-size: 14px; }
.inv-section-body { padding: 16px; }

/* ── Totals panel (inside line items section) ─────── */
.inv-totals-panel {
    display: flex;
    justify-content: flex-end;
    padding: 14px 16px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}
.inv-totals-summary { width: 300px; }
.inv-total-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 13px;
}
.inv-tl-label { color: #64748b; font-weight: 500; }
.inv-tl-value { font-weight: 600; color: #0d1b2a; }
.inv-tl-disc .inv-tl-value { color: #d97706; }

.inv-grand-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 13px;
    background: linear-gradient(135deg, #056464, #0a8a7a);
    border-radius: 8px;
    margin-top: 10px;
}
.inv-gt-label { font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,.75); }
.inv-gt-value { font-size: 19px; font-weight: 800; color: #ffffff; letter-spacing: -.4px; }

.inv-tax-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 6px;
}
.inv-tax-row label { font-size: 12px; font-weight: 600; color: #64748b; white-space: nowrap; }
.inv-tax-row input {
    width: 90px;
    padding: 5px 8px;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12.5px;
    color: #0d1b2a;
    font-family: inherit;
    outline: none;
    transition: border-color .12s;
    text-align: right;
}
.inv-tax-row input:focus { border-color: #056464; }
.inv-tax-pct { font-size: 11.5px; font-weight: 600; color: #94a3b8; }

/* ── Line items table ─────────────────────────────── */
.inv-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}
.inv-items-table thead tr { background: #f8fafc; }
.inv-items-table thead th {
    padding: 9px 8px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: uppercase;
    color: #94a3b8;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.inv-items-table tbody td { padding: 5px 5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.inv-items-table tbody tr:last-child td { border-bottom: none; }
.inv-row-num {
    width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    background: #f1f5f9;
    border-radius: 50%;
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
}
.inv-cell {
    width: 100%;
    padding: 6px 7px;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12.5px;
    color: #0d1b2a;
    background: #ffffff;
    font-family: inherit;
    outline: none;
    transition: border-color .12s;
    box-sizing: border-box;
}
.inv-cell:focus { border-color: #056464; }
.inv-cell[readonly] { background: #f8fafc; color: #64748b; cursor: default; }
.inv-cell-total { background: #f0fdf4; font-weight: 700; color: #15803d; text-align: right; }
.inv-cell-sm { font-size: 11.5px; margin-top: 3px; }

.inv-items-empty {
    padding: 30px;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
    display: none;
}
.inv-items-empty i { font-size: 26px; display: block; margin-bottom: 6px; }

/* ── Summary card (right column) ─────────────────── */
.inv-summary-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(13,27,42,.05);
    position: sticky;
    top: 72px;
}
.inv-summary-head {
    padding: 11px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12.5px;
    font-weight: 700;
    color: #0d1b2a;
    display: flex;
    align-items: center;
    gap: 7px;
}
.inv-summary-head i { color: #056464; }
.inv-summary-body { padding: 14px 16px; }
.inv-sum-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 5px 0;
    font-size: 12.5px;
    border-bottom: 1px solid #f1f5f9;
}
.inv-sum-row:last-of-type { border-bottom: none; }
.inv-sum-lbl { color: #64748b; }
.inv-sum-val { font-weight: 600; color: #0d1b2a; }
.inv-sum-total-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 13px;
    background: linear-gradient(135deg, #056464, #0a8a7a);
    border-radius: 8px;
    margin-top: 10px;
}
.inv-sum-total-lbl { font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,.75); }
.inv-sum-total-val { font-size: 18px; font-weight: 800; color: #ffffff; }

/* ── Footer bar ───────────────────────────────────── */
.inv-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-top: 16px;
    gap: 10px;
    flex-wrap: wrap;
}
.inv-footer-info { font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
</style>

<div class="container-fluid">

    <!-- ── Page Header ──────────────────────────────── -->
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
                    ? 'Select a client then fill in the invoice details below'
                    : 'Update invoice information and line items' ?>
            </div>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="<?= site_url('finance/invoices') ?>" class="btn btn-light-primary btn-header">
                <i class="ti ti-arrow-back-up me-1"></i> Back to Invoices
            </a>
        </div>
    </div>

    <?php if (validation_errors()): ?>
        <div class="alert alert-danger mb-3">
            <?= validation_errors('<p class="mb-1">', '</p>') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $form_action ?>" id="invoiceForm" autocomplete="off" class="app-form">
    <div class="row g-4">

        <!-- ══════════════════════════════════════════
             LEFT COLUMN
        ══════════════════════════════════════════ -->
        <div class="col-lg-8">

            <!-- ══ SECTION 1 — Client ══════════════ -->
            <div class="inv-section">
                <div class="inv-section-head">
                    <div class="inv-section-head-left">
                        <div class="inv-section-title">
                            <i class="ti ti-building"></i> Client
                        </div>
                    </div>
                </div>

                <div style="padding:14px 16px;">

                    <!-- Client panel -->
                    <div class="inv-client-panel" id="clientPanel">

                        <div class="inv-client-select-wrap">
                            <div class="inv-client-select-label">
                                <i class="ti ti-building"></i>
                                Select Client <span style="color:#dc2626;">*</span>
                            </div>
                            <select name="client_id" id="clientSelect"
                                    class="form-select" required style="font-size:13.5px;">
                                <option value="">— Search and select a client —</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= (int)$c->id ?>"
                                            <?= (isset($invoice) && $invoice->client_id == $c->id) ? 'selected' : '' ?>>
                                        <?= html_escape($c->name) ?>
                                        <?php if (!empty($c->client_code)): ?>
                                            (<?= html_escape($c->client_code) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Loading -->
                        <div class="inv-client-loading" id="clientLoading">
                            <i class="ti ti-loader ti-spin me-1"></i> Loading client information…
                        </div>

                        <!-- Placeholder -->
                        <div class="inv-client-placeholder" id="clientPlaceholder">
                            <i class="ti ti-user-search"></i>
                            Select a client above to load their profile and outstanding invoices
                        </div>

                        <!-- Client info -->
                        <div class="inv-client-info" id="clientInfo">

                            <div class="inv-client-hd">
                                <div>
                                    <div class="inv-client-name" id="ciName">—</div>
                                    <span class="x-small text-light" id="ciCode" style="display:none;"></span>
                                </div>
                                <a href="#" id="ciViewLink" target="_blank"
                                   class="btn btn-light-primary btn-header" style="white-space:nowrap;flex-shrink:0;">
                                    <i class="ti ti-external-link me-1"></i>View Client
                                </a>
                            </div>

                            <div class="inv-client-detail-grid">
                                <div>
                                    <div class="inv-client-detail-lbl">Billing Address</div>
                                    <div class="inv-client-detail-val" id="ciAddress" style="line-height:1.6;">—</div>
                                </div>
                                <div>
                                    <div class="inv-client-detail-lbl">Contact Person</div>
                                    <div class="inv-client-detail-val" id="ciContact">—</div>
                                    <div class="inv-client-detail-val" id="ciEmail" style="margin-top:2px;font-size:12px;color:#64748b;"></div>
                                    <div class="inv-client-detail-val" id="ciPhone" style="margin-top:1px;font-size:12px;color:#64748b;"></div>
                                </div>
                                <div>
                                    <div class="inv-client-detail-lbl">Billing Model</div>
                                    <div class="inv-client-detail-val capital" id="ciBilling">—</div>
                                </div>
                            </div>

                            <div class="inv-outstanding-wrap" id="outstandingWrap">
                                <div class="inv-outstanding-title">
                                    <i class="ti ti-alert-circle"></i>
                                    Balance:
                                    <span class="inv-outstanding-badge" id="outstandingBadge">
                                        <i class="ti ti-currency-dollar" style="font-size:11px;"></i>
                                        <span id="outstandingTotal">$0.00</span>
                                    </span>
                                </div>
                                <div id="outstandingTableWrap"></div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- ══ SECTION 2 — Line Items ══════════ -->
            <div class="inv-section" id="lineItemsSection" style="display:none;">
                <div class="inv-section-head">
                    <div class="inv-section-head-left">
                        <div class="inv-section-title">
                            <i class="ti ti-list-details"></i> Line Items
                        </div>
                    </div>
                    <button type="button" id="btnAddItem" class="btn btn-light-primary btn-header">
                        <i class="ti ti-plus" style="font-size:12px;"></i> Add Row
                    </button>
                </div>

                <div style="overflow-x:auto;border-bottom:1px solid #e2e8f0;">
                    <table class="inv-items-table" id="lineItemsTable">
                        <thead>
                            <tr>
                                <th style="width:30px;">#</th>
                                <th style="min-width:200px;">Item &amp; Description</th>
                                <th style="min-width:65px;">Unit</th>
                                <th style="min-width:65px;">Qty</th>
                                <th style="min-width:100px;">Unit Price</th>
                                <th style="min-width:120px;">Discount</th>
                                <th style="min-width:68px;">Tax %</th>
                                <th style="min-width:100px;text-align:right;">Total</th>
                                <th style="width:32px;"></th>
                            </tr>
                        </thead>
                        <tbody id="lineItemsBody">
                            <?php if (!empty($items)):
                                foreach ($items as $idx => $item):
                                    include('_line_item_row.php');
                                endforeach;
                            else:
                                $idx = 0; $item = null;
                                include('_line_item_row.php');
                            endif; ?>
                        </tbody>
                    </table>
                    <div id="lineItemsEmpty" class="inv-items-empty">
                        <i class="ti ti-table-off"></i>
                        No line items yet — click <strong>Add Row</strong> to begin.
                    </div>
                </div>

                <!-- Totals -->
                <div class="inv-totals-panel">
                    <div class="inv-totals-summary">

                        <div class="inv-tax-row">
                            <label>Invoice Discount (flat)</label>
                            <div style="display:flex;align-items:center;gap:4px;">
                                <input type="number" id="invoiceDiscount" name="discount_amount"
                                       step="0.01" min="0"
                                       value="<?= html_escape($invoice->discount_amount ?? 0) ?>"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div class="inv-tax-row">
                            <label>Tax Rate</label>
                            <div style="display:flex;align-items:center;gap:4px;">
                                <input type="number" id="invoiceTaxRate" name="tax_rate"
                                       step="0.01" min="0" max="100"
                                       value="<?= html_escape($invoice->tax_rate ?? 0) ?>"
                                       placeholder="0">
                                <span class="inv-tax-pct">%</span>
                            </div>
                        </div>

                        <hr style="border-color:#e2e8f0;margin:8px 0;">

                        <div class="inv-total-line">
                            <span class="inv-tl-label">Subtotal</span>
                            <span class="inv-tl-value" id="dispSubtotal">0.00</span>
                        </div>

                        <div class="inv-total-line inv-tl-disc" id="discRow" style="display:none;">
                            <span class="inv-tl-label">Discount</span>
                            <span class="inv-tl-value" id="dispDiscount">−0.00</span>
                        </div>

                        <div class="inv-total-line" id="taxRow" style="display:none;">
                            <span class="inv-tl-label" id="taxRowLabel">Tax (0%)</span>
                            <span class="inv-tl-value" id="dispTax">0.00</span>
                        </div>

                        <div class="inv-grand-total">
                            <span class="inv-gt-label">Total Due</span>
                            <span class="inv-gt-value" id="dispTotal">0.00</span>
                        </div>

                        <!-- Hidden computed fields for controller -->
                        <input type="hidden" id="hidSubtotal"  name="subtotal_calc"    value="">
                        <input type="hidden" id="hidTaxAmount" name="tax_amount_calc"  value="">
                        <input type="hidden" id="hidTotal"     name="total_calc"       value="">

                    </div>
                </div>
            </div>
            
        </div>

        <!-- ══════════════════════════════════════════
             RIGHT COLUMN — Invoice Details + Summary
        ══════════════════════════════════════════ -->
        <div class="col-lg-4">

            <!-- Invoice Details -->
            <div class="inv-section" id="invoiceDetailsSection" style="display:none;">
                <div class="inv-section-head">
                    <div class="inv-section-head-left">
                        <div class="inv-section-title" style="font-size:12.5px;">
                            <i class="ti ti-file-description"></i> Invoice Details
                        </div>
                    </div>
                </div>
                <div class="inv-section-body">
                    <div class="row g-3">
                        
                        <?php $autoInvoiceNumber = generate_invoice_number(); ?>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">
                                Invoice Number <span class="text-danger">*</span>
                            </label>
                        
                            <input type="text"
                                   name="invoice_number"
                                   class="form-control crm-input crm-input-readonly"
                                   value="<?= $page_mode === 'add'
                                       ? html_escape($autoInvoiceNumber)
                                       : html_escape($invoice->invoice_number ?? '') ?>"
                                   readonly>
                        </div>
            
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Status</label>
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
                            <label class="form-label small fw-semibold">
                                Invoice Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="invoice_date" class="form-control" required
                                   value="<?= html_escape($invoice->invoice_date ?? date('Y-m-d')) ?>">
                        </div>

                        <div class="col-6">
                            <label class="form-label small fw-semibold">Due Date</label>
                            <input type="date" name="due_date" class="form-control"
                                   value="<?= html_escape($invoice->due_date ?? '') ?>">
                        </div>

                        <div class="col-6">
                            <label class="form-label small fw-semibold">PO Number</label>
                            <input type="text" name="po_number" class="form-control"
                                   value="<?= html_escape($invoice->po_number ?? '') ?>"
                                   placeholder="Client PO reference">
                        </div>
                        
                    </div>
                </div>
            </div>

            <!-- ══ SECTION 3 — Notes & Terms ═══════ -->
            <div class="inv-section" id="notesSection" style="display:none;">
                <div class="inv-section-head">
                    <div class="inv-section-head-left">
                        <div class="inv-section-title">
                            <i class="ti ti-notes"></i> Notes &amp; Terms
                        </div>
                    </div>
                </div>
                <div class="inv-section-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Invoice Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Notes visible on the invoice…"><?= html_escape($invoice->notes ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payment Terms</label>
                            <textarea name="terms" class="form-control" rows="3"
                                      placeholder="e.g. Net 30, due on receipt…"><?= html_escape($invoice->terms ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Live Summary Card -->
            <div class="inv-summary-card" id="summaryCard" style="display:none;">
                <div class="inv-summary-head">
                    <i class="ti ti-calculator"></i> Invoice Summary
                </div>
                <div class="inv-summary-body">
                    <div class="inv-sum-row">
                        <span class="inv-sum-lbl">Subtotal</span>
                        <span class="inv-sum-val" id="sidSubtotal">0.00</span>
                    </div>
                    <div class="inv-sum-row" id="sidDiscRow" style="display:none;">
                        <span class="inv-sum-lbl">Discount</span>
                        <span class="inv-sum-val" style="color:#d97706;" id="sidDiscount">−0.00</span>
                    </div>
                    <div class="inv-sum-row" id="sidTaxRow" style="display:none;">
                        <span class="inv-sum-lbl" id="sidTaxLabel">Tax</span>
                        <span class="inv-sum-val" id="sidTax">0.00</span>
                    </div>
                    <div class="inv-sum-total-wrap">
                        <span class="inv-sum-total-lbl">Total Due</span>
                        <span class="inv-sum-total-val" id="sidTotal">0.00</span>
                    </div>
                </div>
                
                <!-- ══ Footer ═══════════════════════════ -->
                <div class="inv-footer" id="invoiceFooter" style="display:none;">
                    <div class="inv-footer-info">
                        <i class="ti ti-info-circle"></i>
                        Fields marked <span style="color:#dc2626;font-weight:700;">*</span> are required.
                    </div>
                    
                    <div class="d-flex gap-2 align-items-center">
                        <a href="<?= site_url('finance/invoices') ?>" class="btn btn-outline-secondary btn-header">
                            Cancel
                        </a>
                        <?php if ($page_mode === 'add'): ?>
                            <button type="submit" name="save_action" value="save_send"
                                    class="btn btn-outline-primary btn-header">
                                <i class="ti ti-send me-1"></i> Create &amp; Send
                            </button>
                        <?php endif; ?>
                        <button type="submit" name="save_action" value="save" class="btn btn-primary btn-header">
                            <i class="ti ti-device-floppy me-1"></i>
                            <?= $page_mode === 'add' ? 'Create Invoice' : 'Save Changes' ?>
                        </button>
                    </div>
                </div>
            
            </div>

        </div>

    </div>
    </form>

</div>

<template id="lineItemRowTemplate">
    <?php $idx = '__IDX__'; $item = null; include('_line_item_row.php') ?>
</template>

<script>
(function () {
    'use strict';

    /* ── Helpers ──────────────────────────────────────────── */
    const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const $   = id => document.getElementById(id);

    /* ── Refs ─────────────────────────────────────────────── */
    const clientSelect      = $('clientSelect');
    const clientPanel       = $('clientPanel');
    const clientLoading     = $('clientLoading');
    const clientPlaceholder = $('clientPlaceholder');
    const clientInfo        = $('clientInfo');
    const tbody             = $('lineItemsBody');
    const addBtn            = $('btnAddItem');
    const emptyState        = $('lineItemsEmpty');
    const discountInput     = $('invoiceDiscount');
    const taxRateInput      = $('invoiceTaxRate');

    const revealSections = [
        'invoiceDetailsSection',
        'lineItemsSection',
        'notesSection',
        'summaryCard',
        'invoiceFooter',
    ];

    function showSections() {
        revealSections.forEach(id => { const el = $(id); if (el) el.style.display = 'block'; });
    }
    function hideSections() {
        revealSections.forEach(id => { const el = $(id); if (el) el.style.display = 'none'; });
    }

    /* ══════════════════════════════════════════════════════
       CALCULATIONS
    ══════════════════════════════════════════════════════ */

    function calcRow(tr) {
        const qty   = parseFloat(tr.querySelector('[data-role="qty"]')?.value   || 0);
        const price = parseFloat(tr.querySelector('[data-role="price"]')?.value || 0);
        const gross = qty * price;

        const dtype = tr.querySelector('[data-role="disc-type"]')?.value || 'none';
        const dval  = parseFloat(tr.querySelector('[data-role="disc-val"]')?.value || 0);
        let   damt  = 0;

        if (dtype === 'percent') {
            damt = gross * (dval / 100);
        } else if (dtype === 'fixed') {
            damt = Math.min(dval, gross);
        }

        const dAmtEl = tr.querySelector('[data-role="disc-amt"]');
        if (dAmtEl) dAmtEl.value = damt.toFixed(2);

        const lineTotal = Math.max(0, gross - damt);
        const ltEl = tr.querySelector('[data-role="line-total"]');
        if (ltEl) ltEl.value = lineTotal.toFixed(2);

        return lineTotal;
    }

    function recalcAll() {
        let sub = 0;
        tbody.querySelectorAll('tr.inv-item-row').forEach(tr => { sub += calcRow(tr); });

        const discFlat  = Math.max(0, parseFloat(discountInput?.value || 0));
        const taxRate   = Math.max(0, parseFloat(taxRateInput?.value  || 0));
        const afterDisc = Math.max(0, sub - discFlat);
        const taxAmt    = afterDisc * (taxRate / 100);
        const total     = afterDisc + taxAmt;

        /* Line items panel */
        if ($('dispSubtotal')) $('dispSubtotal').textContent = fmt(sub);

        const discRow = $('discRow');
        if (discRow) {
            discRow.style.display = discFlat > 0 ? 'flex' : 'none';
            if ($('dispDiscount')) $('dispDiscount').textContent = '−' + fmt(discFlat);
        }
        const taxRow = $('taxRow');
        if (taxRow) {
            taxRow.style.display = taxRate > 0 ? 'flex' : 'none';
            if ($('taxRowLabel')) $('taxRowLabel').textContent = 'Tax (' + taxRate + '%)';
            if ($('dispTax'))     $('dispTax').textContent     = fmt(taxAmt);
        }
        if ($('dispTotal')) $('dispTotal').textContent = fmt(total);

        /* Right-column summary */
        if ($('sidSubtotal')) $('sidSubtotal').textContent = fmt(sub);

        const sidDiscRow = $('sidDiscRow');
        if (sidDiscRow) {
            sidDiscRow.style.display = discFlat > 0 ? 'flex' : 'none';
            if ($('sidDiscount')) $('sidDiscount').textContent = '−' + fmt(discFlat);
        }
        const sidTaxRow = $('sidTaxRow');
        if (sidTaxRow) {
            sidTaxRow.style.display = taxRate > 0 ? 'flex' : 'none';
            if ($('sidTaxLabel')) $('sidTaxLabel').textContent = 'Tax (' + taxRate + '%)';
            if ($('sidTax'))      $('sidTax').textContent      = fmt(taxAmt);
        }
        if ($('sidTotal')) $('sidTotal').textContent = fmt(total);

        /* Hidden server fields */
        if ($('hidSubtotal'))  $('hidSubtotal').value  = sub.toFixed(2);
        if ($('hidTaxAmount')) $('hidTaxAmount').value = taxAmt.toFixed(2);
        if ($('hidTotal'))     $('hidTotal').value     = total.toFixed(2);

        toggleEmptyState();
    }

    function toggleEmptyState() {
        const count = tbody.querySelectorAll('tr.inv-item-row').length;
        if (emptyState) emptyState.style.display = count > 0 ? 'none' : 'block';
    }

    function refreshNums() {
        tbody.querySelectorAll('tr.inv-item-row').forEach((tr, i) => {
            const b = tr.querySelector('.inv-row-num');
            if (b) b.textContent = i + 1;
        });
    }

    /* ── New row HTML ──────────────────────────────────── */
    function makeRow(n) {
        return `<tr class="inv-item-row">
            <td><div class="inv-row-num">${n}</div></td>
            <td>
                <input type="text" name="item_name[]" class="inv-cell"
                       placeholder="Item name" style="margin-bottom:3px;">
                <input type="text" name="item_description[]" class="inv-cell inv-cell-sm"
                       placeholder="Description (optional)">
            </td>
            <td><input type="text" name="item_unit[]" class="inv-cell" placeholder="hrs"></td>
            <td><input type="number" step="0.01" min="0" name="item_quantity[]"
                       class="inv-cell" data-role="qty" value="1"></td>
            <td><input type="number" step="0.01" min="0" name="item_unit_price[]"
                       class="inv-cell" data-role="price" value="0.00"></td>
            <td>
                <select name="item_discount_type[]" class="inv-cell" data-role="disc-type"
                        style="margin-bottom:3px;">
                    <option value="none">None</option>
                    <option value="percent">% Percent</option>
                    <option value="fixed">$ Fixed</option>
                </select>
                <input type="number" step="0.01" min="0" name="item_discount_amount[]"
                       class="inv-cell inv-cell-sm" data-role="disc-val"
                       value="0" placeholder="Value">
                <input type="hidden" name="item_discount_calculated[]" data-role="disc-amt" value="0.00">
            </td>
            <td><input type="number" step="0.01" min="0" max="100" name="item_tax_rate[]"
                       class="inv-cell" value="0"></td>
            <td style="text-align:right;">
                <input type="number" step="0.01" name="item_line_total[]"
                       class="inv-cell inv-cell-total" data-role="line-total"
                       value="0.00" readonly tabindex="-1">
            </td>
            <td>
                <button type="button" class="btn btn-light-danger icon-btn inv-remove-row">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>`;
    }

    /* ── Add row ───────────────────────────────────────── */
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            const n = tbody.querySelectorAll('tr.inv-item-row').length + 1;
            tbody.insertAdjacentHTML('beforeend', makeRow(n));
            toggleEmptyState();
            const rows = tbody.querySelectorAll('tr.inv-item-row');
            rows[rows.length - 1]?.querySelector('input[name="item_name[]"]')?.focus();
        });
    }

    /* ── Remove row ────────────────────────────────────── */
    document.addEventListener('click', e => {
        const btn = e.target.closest('.inv-remove-row');
        if (!btn) return;
        const rows = tbody.querySelectorAll('tr.inv-item-row');
        if (rows.length > 1) {
            btn.closest('tr.inv-item-row').remove();
            refreshNums();
        } else {
            const tr = btn.closest('tr.inv-item-row');
            tr.querySelectorAll('input[type="text"],input[type="number"]').forEach(el => {
                const role = el.getAttribute('data-role');
                el.value = role === 'qty' ? '1' : '0';
            });
            tr.querySelectorAll('select').forEach(el => { el.selectedIndex = 0; });
        }
        recalcAll();
    });

    /* ── Live recalc ───────────────────────────────────── */
    document.addEventListener('input', e => {
        if (e.target.closest('tr.inv-item-row') ||
            e.target === discountInput ||
            e.target === taxRateInput) {
            recalcAll();
        }
    });
    document.addEventListener('change', e => {
        if (e.target.closest('tr.inv-item-row') ||
            e.target === discountInput ||
            e.target === taxRateInput) {
            recalcAll();
        }
    });

    /* ══════════════════════════════════════════════════════
       CLIENT PANEL
    ══════════════════════════════════════════════════════ */

    function renderClientInfo(client, outstanding) {

        $('ciName').textContent = client.practice_legal_name || client.practice_name || '—';

        const codeEl = $('ciCode');
        if (client.client_code) {
            codeEl.textContent   = '#' + client.client_code;
            codeEl.style.display = 'inline-block';
        } else {
            codeEl.style.display = 'none';
        }

        $('ciViewLink').href = '<?= site_url('crm/clients/view') ?>/' + client.id;

        $('ciContact').textContent = client.primary_contact_name || '—';
        $('ciEmail').textContent   = client.primary_email        || '';
        $('ciPhone').textContent   = client.primary_phone        || '';

        // Address: street on line 1, city/state/zip/country on line 2
        const addrParts = [
            client.address || '',
            [client.city, client.state, client.zip_code, client.country].filter(Boolean).join(', '),
        ].filter(Boolean);
        $('ciAddress').innerHTML = addrParts.length ? addrParts.join('<br>') : '—';

        // Billing model
        let billingText = client.billing_model || '—';
        
        if (client.billing_model === 'percent' && client.rate_percent) {
            billingText = 'Percent (' + client.rate_percent + '%)';
        } 
        else if (client.billing_model === 'flat' && client.rate_flat) {
            billingText = 'Flat ($' + parseFloat(client.rate_flat).toFixed(2) + ')';
        }
        
        $('ciBilling').textContent = billingText;

        renderOutstanding(outstanding);

        clientInfo.classList.add('show');
        clientPanel.classList.add('loaded');
    }

    function renderOutstanding(outstanding) {
        const wrap    = $('outstandingTableWrap');
        const badge   = $('outstandingBadge');
        const totalEl = $('outstandingTotal');
        const invoices = outstanding.invoices || [];

        if (invoices.length === 0) {
            badge.style.display = 'none';
            wrap.innerHTML = '<div class="inv-no-outstanding">' +
                '<i class="ti ti-circle-check me-1"></i>No outstanding invoices — account is current.' +
                '</div>';
            return;
        }

        badge.style.display = 'inline-flex';
        const totalFmt = '$' + parseFloat(outstanding.total_outstanding)
            .toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        totalEl.textContent = totalFmt;

        const pillClass = {
            sent:    'text-success',
            viewed:  'text-info',
            partial: 'text-warning',
            overdue: 'text-danger',
        };

        const rows = invoices.map(inv => {
            const cls = pillClass[inv.status] || '';
            const bal = parseFloat(inv.balance_due  || 0).toFixed(2);
            const amt = parseFloat(inv.total_amount || 0).toFixed(2);
            return `<tr>
                <td><a href="<?= site_url('finance/invoices/view') ?>/${inv.id}"
                        target="_blank" style="color:#056464;font-weight:600;">
                    ${inv.invoice_number}</a></td>
                <td>${inv.invoice_date || '—'}</td>
                <td>${inv.due_date    || '—'}</td>
                <td class="text-center">$${amt}</td>
                <td class="text-center text-light-danger">$${bal}</td>
                <td class="text-center capital"><span class="${cls}">${inv.status}</span></td>
            </tr>`;
        }).join('');

        wrap.innerHTML = `<table class="inv-outstanding-table">
            <thead><tr>
                <th>Invoice #</th><th>Date</th><th>Due</th>
                <th class="text-center">Amount</th>
                <th class="text-center">Balance</th>
                <th class="text-center">Status</th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
    }

    function loadClient(clientId) {
        if (!clientId) {
            clientInfo.classList.remove('show');
            clientPanel.classList.remove('loaded');
            clientPlaceholder.style.display = 'block';
            clientLoading.classList.remove('show');
            hideSections();
            return;
        }

        clientPlaceholder.style.display = 'none';
        clientInfo.classList.remove('show');
        clientLoading.classList.add('show');

        fetch('<?= site_url('finance/invoices/get_client_info') ?>?client_id=' + clientId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            clientLoading.classList.remove('show');
            if (data.success) {
                renderClientInfo(data.client, data.outstanding);
                showSections();
                recalcAll();
            } else {
                clientPlaceholder.style.display = 'block';
                clientPanel.classList.remove('loaded');
            }
        })
        .catch(() => {
            clientLoading.classList.remove('show');
            clientPlaceholder.style.display = 'block';
        });
    }

    clientSelect.addEventListener('change', function () {
        loadClient(this.value);
    });

    // Edit mode — preload on page open
    const preselected = clientSelect.value;
    if (preselected) {
        loadClient(preselected);
    } else {
        recalcAll();
    }

    toggleEmptyState();

})();
</script>