<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$proposal = is_array($proposal ?? null) ? $proposal : [];
$items    = is_array($items ?? null) ? $items : [];
$leads    = is_array($leads ?? null) ? $leads : [];
$currentStatus = $proposal['status'] ?? 'draft';
$proposalStatuses   = proposal_statuses();
$billingCycles      = proposal_billing_cycles();
$forecastCategories = forecast_categories();
$discountTypes      = proposal_discount_types();
$itemTypes          = proposal_item_types();
$discountScope = $proposal['discount_scope'] ?? 'total';
?>

<style>
/* ═══════════════════════════════════════════════════════
   PROPOSAL CREATE — Professional Invoice Layout
   Brand teal: #056464
   ═══════════════════════════════════════════════════════ */
:root{
    --brand:#056464;
    --brand-mid:#0a8a8a;
    --brand-light:#f0fdfa;
    --brand-pale:#e6f7f7;
    --ink:#0f172a;
    --ink-mid:#334155;
    --ink-muted:#64748b;
    --ink-faint:#94a3b8;
    --border:#e2e8f0;
    --border-dark:#cbd5e1;
    --bg:#f8fafc;
    --surface:#fff;
    --red:#dc2626;
    --amber:#d97706;
    --r4:4px;
    --r6:6px;
    --r8:8px;
    --r12:12px;
    --shadow-xs:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
    --shadow-sm:0 2px 8px rgba(0,0,0,.08),0 1px 3px rgba(0,0,0,.05);
}

/* Page header */
.pf-page-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;
    padding:11px 16px;background:var(--surface);border:1px solid var(--border);
    border-radius:var(--r12);margin-bottom:16px;box-shadow:var(--shadow-xs);}
.pf-page-icon{width:36px;height:36px;border-radius:var(--r6);background:var(--brand-light);color:var(--brand);
    display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
.pf-page-title{font-size:15px;font-weight:700;color:var(--ink);}
.pf-page-sub{font-size:11.5px;color:var(--ink-muted);margin-top:1px;}

/* Section */
.pf-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--r12);
    box-shadow:var(--shadow-xs);margin-bottom:12px;overflow:hidden;}
.pf-section-head{display:flex;align-items:center;justify-content:space-between;gap:8px;
    padding:9px 16px;background:var(--bg);border-bottom:1px solid var(--border);}
.pf-section-head-left{display:flex;align-items:center;gap:7px;}
.pf-step-num{width:20px;height:20px;border-radius:50%;background:var(--brand);color:#fff;
    font-size:9.5px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.pf-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink-muted);}
.pf-section-title i{font-size:12px;color:var(--brand);margin-right:3px;}
.pf-section-body{padding:16px;}

/* Labels & controls */
.app-form-label-req::after{content:' *';color:var(--red);}
.pf-control{width:100%;padding:6px 10px;font-size:13px;color:var(--ink);background:var(--surface);
    border:1.5px solid var(--border-dark);border-radius:var(--r6);outline:none;
    transition:border-color .15s,box-shadow .15s;appearance:none;line-height:1.5;}
.pf-control:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(5,100,100,.09);}
.pf-control[readonly]{background:var(--bg);color:var(--ink-muted);cursor:not-allowed;}
textarea.pf-control{resize:vertical;}
select.pf-control{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center;padding-right:26px;}
.pf-input-sfx{position:relative;display:flex;align-items:center;}
.pf-input-sfx .pf-sfx{position:absolute;right:9px;font-size:12px;font-weight:700;color:var(--ink-muted);pointer-events:none;}
.pf-input-sfx .pf-control{padding-right:26px;}

/* ── Discount scope toggle ────────────────────────────── */
.pf-disc-scope{display:flex;align-items:center;gap:6px;padding:7px 14px;
    background:var(--bg);border-bottom:1px solid var(--border);}
.pf-disc-scope-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
    color:var(--ink-muted);margin-right:4px;white-space:nowrap;}
.pf-scope-btn{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;font-size:11.5px;
    font-weight:600;border-radius:20px;border:1.5px solid var(--border-dark);background:var(--surface);
    color:var(--ink-muted);cursor:pointer;transition:all .15s;line-height:1.5;}
.pf-scope-btn.active{background:var(--brand);color:#fff;border-color:var(--brand);}
.pf-scope-btn i{font-size:12px;}

/* ── Items table ──────────────────────────────────────── */
.pf-items-table{width:100%;border-collapse:collapse;font-size:12.5px;}
.pf-items-table thead tr{background:#f1f5f9;border-bottom:2px solid var(--border-dark);}
.pf-items-table thead th{padding:8px 8px;font-size:9.5px;font-weight:800;text-transform:uppercase;
    letter-spacing:.65px;color:var(--ink-faint);white-space:nowrap;border:none;}
.pf-items-table thead th:first-child{padding-left:14px;}
.pf-items-table thead th:last-child{padding-right:12px;}
.pf-item-row td{padding:4px 5px;vertical-align:middle;border-bottom:1px solid #f1f5f9;background:var(--surface);}
.pf-item-row:last-child td{border-bottom:none;}
.pf-item-row:hover td{background:#fdfdfe;}
.pf-item-row td:first-child{padding-left:10px;}
.pf-item-row td:last-child{padding-right:10px;}
.pf-row-num{width:20px;height:20px;border-radius:50%;background:var(--bg);border:1px solid var(--border);
    font-size:9.5px;font-weight:700;color:var(--ink-faint);display:flex;align-items:center;justify-content:center;}

/* Cell controls */
.pf-cell{width:100%;padding:4px 7px;font-size:12.5px;color:var(--ink);background:transparent;
    border:1.5px solid transparent;border-radius:var(--r4);outline:none;transition:border-color .13s,background .13s;
    appearance:none;line-height:1.4;}
.pf-cell:hover{border-color:var(--border-dark);}
.pf-cell:focus{border-color:var(--brand);background:var(--surface);box-shadow:0 0 0 2px rgba(5,100,100,.08);}
select.pf-cell{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24'%3E%3Cpath fill='%2394a3b8' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 5px center;padding-right:20px;}
.pf-cell-computed{background:var(--bg)!important;color:var(--ink-muted)!important;cursor:not-allowed;border-color:transparent!important;}

/* Empty state */
.pf-empty-state{padding:34px;text-align:center;color:var(--ink-faint);font-size:13px;display:none;border-top:1px solid var(--border);}
.pf-empty-state i{font-size:30px;display:block;margin-bottom:7px;opacity:.3;}

/* ── Invoice totals panel ────────────────────────────── */
.pf-totals-panel{display:flex;align-items:stretch;border-top:2px solid var(--border);background:var(--bg);}
.pf-totals-controls{flex:1;padding:16px 18px;border-right:1px solid var(--border);}
.pf-totals-controls .row{margin:0;}
.pf-totals-summary{width:290px;flex-shrink:0;padding:16px 18px;display:flex;flex-direction:column;gap:0;}

.pf-total-line{display:flex;align-items:center;justify-content:space-between;padding:5px 0;font-size:12.5px;}
.pf-total-line+.pf-total-line{border-top:1px dashed var(--border);}
.pf-tl-label{color:var(--ink-muted);font-size:12px;}
.pf-tl-value{font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums;}
.pf-tl-disc .pf-tl-value{color:var(--amber);}
.pf-tl-tax  .pf-tl-value{color:var(--ink-muted);}

.pf-grand-total{margin-top:10px;padding:12px 14px;border-radius:var(--r8);
    background:linear-gradient(135deg,var(--brand) 0%,var(--brand-mid) 100%);
    color:#fff;display:flex;align-items:center;justify-content:space-between;}
.pf-gt-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;opacity:.82;}
.pf-gt-value{font-size:24px;font-weight:800;letter-spacing:-.5px;line-height:1;}

/* Tax rate inline field in summary */
.pf-tax-input-row{display:flex;align-items:center;justify-content:space-between;padding:5px 0;}
.pf-tax-input-row .pf-tl-label{color:var(--ink-muted);font-size:12px;white-space:nowrap;margin-right:8px;}
.pf-tax-field-wrap{display:flex;align-items:center;gap:4px;width:100px;flex-shrink:0;}
.pf-tax-field-wrap input{width:100%;padding:3px 22px 3px 7px;font-size:12px;font-weight:600;
    color:var(--ink);background:var(--surface);border:1.5px solid var(--border-dark);
    border-radius:var(--r4);outline:none;text-align:right;transition:border-color .13s;}
.pf-tax-field-wrap input:focus{border-color:var(--brand);box-shadow:0 0 0 2px rgba(5,100,100,.08);}
.pf-tax-field-wrap .pf-tax-pct{font-size:11px;font-weight:700;color:var(--ink-muted);margin-left:-20px;pointer-events:none;z-index:1;}

/* Date warning */
.pf-date-warn{font-size:10.5px;color:var(--red);margin-top:3px;display:none;}
/* Disc hint */
.pf-disc-hint{font-size:10.5px;color:var(--brand-mid);min-height:14px;}

/* Footer */
.pf-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;
    padding:12px 16px;background:var(--surface);border:1px solid var(--border);
    border-radius:var(--r12);margin-top:12px;box-shadow:var(--shadow-xs);}
.pf-footer-info{font-size:12px;color:var(--ink-muted);display:flex;align-items:center;gap:5px;}
.pf-btn-cancel{display:inline-flex;align-items:center;gap:5px;padding:8px 16px;font-size:13px;font-weight:600;
    color:var(--ink-muted);background:var(--surface);border:1.5px solid var(--border-dark);
    border-radius:var(--r6);cursor:pointer;text-decoration:none;transition:all .13s;}
.pf-btn-cancel:hover{background:var(--bg);color:var(--ink);}
.pf-btn-submit{display:inline-flex;align-items:center;gap:6px;padding:8px 22px;font-size:13px;font-weight:700;
    color:#fff;background:var(--brand);border:1.5px solid var(--brand);border-radius:var(--r6);
    cursor:pointer;transition:all .13s;}
.pf-btn-submit:hover{background:#044f4f;box-shadow:0 4px 14px rgba(5,100,100,.28);}
</style>

<div class="container-fluid">

    <!-- ══ Page Header ══════════════════════════════════════════════════ -->
    <div class="pf-page-header">
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="pf-page-icon"><i class="ti ti-file-plus"></i></div>
            <div>
                <div class="pf-page-title">Create Proposal <span id="pf_status_badge" class="badge"></span></div>
                <div class="pf-page-sub">Fill in the details below to generate a new client proposal</div>
            </div>
        </div>
        <a href="<?= site_url('crm/proposals') ?>" class="btn btn-light-primary btn-header">
            <i class="ti ti-arrow-left me-1"></i>Back to Proposals
        </a>
    </div>

    <form method="post" action="<?= site_url('crm/proposals/store') ?>" id="proposalForm" class="app-form">

        <!-- ══ SECTION 1 — Proposal Details ═════════════════════════════ -->
        <div class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-head-left">
                    <div class="pf-step-num">1</div>
                    <div class="pf-section-title"><i class="ti ti-file-description"></i>Proposal Details</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="row g-3">

                    <div class="col-md-2">
                        <label class="app-form-label">Proposal No. <span class="text-light small">Auto-assign</span></label>
                        <input type="text" name="proposal_number" class="pf-control"
                               value="<?= html_escape($proposal['proposal_number'] ?? '') ?>"
                               placeholder="Auto-generated">
                    </div>

                    <div class="col-md-4">
                        <label class="app-form-label app-form-label-req">Proposal Title</label>
                        <input type="text" name="title" class="pf-control"
                               value="<?= html_escape($proposal['title'] ?? '') ?>"
                               placeholder="e.g. RCM Services Proposal — Valley Medical" required>
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Linked Lead</label>
                        <select name="lead_id" class="pf-control">
                            <option value="">— Select Lead —</option>
                            <?php foreach ($leads as $ld): ?>
                                <option value="<?= (int)$ld['id'] ?>"
                                    <?= ((string)($proposal['lead_id'] ?? '') === (string)$ld['id']) ? 'selected' : '' ?>>
                                    <?= html_escape(($ld['practice_name'] ?? '') . (!empty($ld['contact_person']) ? ' — ' . $ld['contact_person'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Status</label>
                        <select name="status" id="pf_status" class="pf-control">
                            <?php foreach ($proposalStatuses as $v => $l): ?>
                                <option value="<?= $v ?>"
                                    <?= ($currentStatus === $v) ? 'selected' : '' ?>>
                                    <?= html_escape($l['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Billing Cycle</label>
                        <select name="billing_cycle" class="pf-control">
                            <?php foreach ($billingCycles as $v => $l): ?>
                                <option value="<?= $v ?>" <?= (($proposal['billing_cycle'] ?? '') === $v) ? 'selected' : '' ?>><?= html_escape($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Payment Terms</label>
                        <input type="text" name="payment_terms" class="pf-control"
                               value="<?= html_escape($proposal['payment_terms'] ?? '') ?>"
                               placeholder="e.g. Net 30">
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Validity (Days)</label>
                        <input type="number" min="1" name="validity_days" class="pf-control"
                               value="<?= html_escape($proposal['validity_days'] ?? '') ?>"
                               placeholder="30">
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Forecast Category</label>
                        <select name="forecast_category" class="pf-control">
                            <?php foreach ($forecastCategories as $v => $l): ?>
                                <option value="<?= $v ?>" <?= (($proposal['forecast_category'] ?? '') === $v) ? 'selected' : '' ?>>
                                <?= html_escape($l['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Start Date</label>
                        <input type="date" name="start_date" id="pf_start_date" class="pf-control"
                               value="<?= html_escape($proposal['start_date'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Go Live Date</label>
                        <input type="date" name="go_live_date" id="pf_go_live_date" class="pf-control"
                               value="<?= html_escape($proposal['go_live_date'] ?? '') ?>">
                        <div class="pf-date-warn" id="pf_date_warn"><i class="ti ti-alert-triangle"></i> Before start date.</div>
                    </div>

                    <div class="col-md-2">
                        <label class="app-form-label">Expires At</label>
                        <input type="date" name="expires_at" id="pf_expires_at" class="pf-control"
                               value="<?= html_escape($proposal['expires_at'] ?? '') ?>">
                        <div class="pf-date-warn" id="pf_expire_warn"><i class="ti ti-alert-triangle"></i> Before start date.</div>
                    </div>

                    <div class="col-md-12">
                        <label class="app-form-label">Summary</label>
                        <textarea name="summary" class="pf-control" rows="2"
                                  placeholder="Brief overview of this proposal…"><?= html_escape($proposal['summary'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- ══ SECTION 2 — Line Items + Invoice Totals ═══════════════════ -->
        <div class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-head-left">
                    <div class="pf-step-num">2</div>
                    <div class="pf-section-title"><i class="ti ti-list-details"></i>Line Items</div>
                </div>
                <button type="button" id="pf_add_row" class="btn btn-light-primary btn-header">
                    <i class="ti ti-plus" style="font-size:12px;"></i> Add Row
                </button>
            </div>

            <!-- ── Discount scope toggle ─────────────────────────────── -->
            <div class="pf-disc-scope">
                <span class="pf-disc-scope-label"><i class="ti ti-discount" style="font-size:12px;margin-right:3px;"></i>Apply Discount:</span>
                <button type="button" class="pf-scope-btn <?= $discountScope === 'line' ? 'active' : '' ?>"
                        id="scope_line" data-scope="line">
                    <i class="ti ti-list-check"></i> Per Line Item
                </button>
                <button type="button" class="pf-scope-btn <?= $discountScope === 'total' ? 'active' : '' ?>"
                        id="scope_total" data-scope="total">
                    <i class="ti ti-receipt"></i> On Total
                </button>
                <input type="hidden" name="discount_scope" id="pf_discount_scope" value="<?= html_escape($discountScope) ?>">
                <span id="pf_scope_desc" style="font-size:11px;color:var(--ink-faint);margin-left:8px;"></span>
            </div>

            <!-- Table -->
            <div style="overflow-x:auto;border-bottom:1px solid var(--border);">
                <table class="pf-items-table" id="pf_items_table">
                    <thead>
                        <tr>
                            <th style="width:32px;">#</th>
                            <th style="min-width:108px;">Type</th>
                            <th style="min-width:155px;">Item Name</th>
                            <th style="min-width:165px;">Description</th>
                            <th style="min-width:65px;">Qty</th>
                            <th style="min-width:100px;">Unit Price</th>
                            <th class="col-disc-type" style="min-width:108px;">Disc. Type</th>
                            <th class="col-disc-val"  style="min-width:85px;">Disc. Value</th>
                            <th class="col-disc-amt"  style="min-width:85px;">Disc. Amt</th>
                            <th style="min-width:100px;">Line Total</th>
                            <th style="width:34px;"></th>
                        </tr>
                    </thead>
                    <tbody id="pf_items_body">
                        <?php if (!empty($items)):
                            foreach ($items as $i => $item): ?>
                            <tr class="pf-item-row">
                                <td><div class="pf-row-num"><?= $i + 1 ?></div></td>
                                <td><select name="item_type[]" class="pf-cell" style="min-width:98px;">
                                    <?php foreach ($itemTypes as $v => $l): ?><option value="<?= $v ?>" <?= (($item['item_type'] ?? 'service') === $v) ? 'selected' : '' ?>><?= html_escape($l) ?></option><?php endforeach; ?>
                                </select></td>
                                <td><input type="text" name="item_name[]" class="pf-cell" value="<?= html_escape($item['item_name'] ?? '') ?>" placeholder="Item name"></td>
                                <td><input type="text" name="item_description[]" class="pf-cell" value="<?= html_escape($item['description'] ?? $item['item_description'] ?? '') ?>" placeholder="Optional"></td>
                                <td><input type="number" step="0.01" min="0" name="item_quantity[]" class="pf-cell pf-item-qty" value="<?= html_escape($item['quantity'] ?? $item['item_quantity'] ?? 1) ?>"></td>
                                <td><input type="number" step="0.01" min="0" name="item_unit_price[]" class="pf-cell pf-item-price" value="<?= html_escape($item['unit_price'] ?? $item['item_unit_price'] ?? 0) ?>"></td>
                                <td class="col-disc-type"><select name="item_discount_type[]" class="pf-cell pf-item-disc-type" style="min-width:98px;">
                                    <?php foreach ($discountTypes as $v => $l): ?><option value="<?= $v ?>" <?= (($item['discount_type'] ?? $item['item_discount_type'] ?? 'none') === $v) ? 'selected' : '' ?>><?= html_escape($l) ?></option><?php endforeach; ?>
                                </select></td>
                                <td class="col-disc-val"><input type="number" step="0.01" min="0" name="item_discount_value[]" class="pf-cell pf-item-disc-val" value="<?= html_escape($item['discount_value'] ?? $item['item_discount_value'] ?? 0) ?>"></td>
                                <td class="col-disc-amt"><input type="number" step="0.01" name="item_discount_amount[]" class="pf-cell pf-cell-computed pf-item-disc-amt" value="<?= html_escape($item['discount_amount'] ?? $item['item_discount_amount'] ?? 0) ?>" readonly tabindex="-1"></td>
                                <td><input type="number" step="0.01" name="item_line_total[]" class="pf-cell bg-light-primary pf-item-line-total" value="<?= html_escape($item['line_total'] ?? $item['item_line_total'] ?? 0) ?>" readonly tabindex="-1"></td>
                                <td><button type="button" class="btn btn-light-danger icon-btn pf-remove-row"><i class="ti ti-trash"></i></button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <div id="pf_empty_state" class="pf-empty-state">
                    <i class="ti ti-table-off"></i>
                    No line items yet — click <strong>Add Row</strong> to begin.
                </div>
            </div>

            <!-- ── Invoice Totals panel ───────────────────────────────── -->
            <div class="pf-totals-panel">

                <div class="pf-totals-controls">

                </div>

                <!-- Right: totals summary -->
                <div class="pf-totals-summary">

                    <div id="pf_total_disc_controls">
                        <div class="pf-tax-input-row">
                            <span class="pf-tl-label">Discount Type</span>
                            <div class="pf-tax-field-wrap small">
                                <select name="discount_type" id="pf_discount_type" class="form-select form-select-sm py-1">
                                    <?php foreach ($discountTypes as $v => $l): ?>
                                        <option value="<?= $v ?>" <?= (($proposal['discount_type'] ?? 'none') === $v) ? 'selected' : '' ?>><?= html_escape($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>    
                        </div>
                        
                        <div class="pf-tax-input-row">
                            <span class="pf-tl-label">Discount Value</span>
                            <div class="pf-tax-field-wrap">
                                <input type="number" step="0.01" min="0" max="100"
                                       name="discount_value" id="pf_discount_value"
                                       value="<?= html_escape($proposal['discount_value'] ?? '0') ?>"
                                       placeholder="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="pf-tax-input-row">
                        <span class="pf-tl-label">Tax Rate</span>
                        <div class="pf-tax-field-wrap">
                            <input type="number" step="0.01" min="0" max="100"
                                   name="tax_rate" id="pf_tax_rate"
                                   value="<?= html_escape($proposal['tax_rate'] ?? '0') ?>"
                                   placeholder="0">
                            <span class="pf-tax-pct">%</span>
                        </div>
                    </div>

                    <div class="pf-total-line">
                        <span class="pf-tl-label">Subtotal</span>
                        <span class="pf-tl-value" id="pf_subtotal_disp">$0.00</span>
                    </div>

                    <div class="pf-total-line pf-tl-disc" id="pf_disc_row" style="display:none;">
                        <span class="pf-tl-label" id="pf_disc_label">Discount</span>
                        <span class="pf-tl-value" id="pf_disc_disp">−$0.00</span>
                    </div>

                    <div class="pf-total-line pf-tl-tax" id="pf_tax_row" style="display:none;">
                        <span class="pf-tl-label" id="pf_tax_label">Tax (0%)</span>
                        <span class="pf-tl-value" id="pf_tax_disp">+$0.00</span>
                    </div>

                    <div class="pf-grand-total">
                        <div class="pf-gt-label">Total Due</div>
                        <span class="pf-gt-value" id="pf_total_disp">0.00</span>
                    </div>

                    <!-- Hidden fields posted to server -->
                    <input type="hidden" name="subtotal"        id="pf_subtotal"        value="<?= html_escape($proposal['subtotal']        ?? '0') ?>">
                    <input type="hidden" name="tax_amount"      id="pf_tax_amount"      value="<?= html_escape($proposal['tax_amount']      ?? '0') ?>">
                    <input type="hidden" name="total_value"     id="pf_total_value"     value="<?= html_escape($proposal['total_value']     ?? '0') ?>">
                    <input type="hidden" name="discount_amount" id="pf_discount_amount" value="<?= html_escape($proposal['discount_amount'] ?? '0') ?>">

                </div>
            </div>
        </div>

        <!-- ══ SECTION 3 — Notes ══════════════════════════════════════════ -->
        <div class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-head-left">
                    <div class="pf-step-num">3</div>
                    <div class="pf-section-title"><i class="ti ti-note"></i>Notes</div>
                </div>
            </div>
            <div class="pf-section-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="app-form-label">Client Notes</label>
                        <textarea name="client_notes" class="pf-control" rows="3"
                                  placeholder="Visible to the client on the printed proposal…"><?= html_escape($proposal['client_notes'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="app-form-label">Terms &amp; Conditions</label>
                        <textarea name="terms_and_conditions" class="pf-control" rows="3"
                                  placeholder="Standard terms and conditions…"><?= html_escape(
                            !empty($proposal['terms_and_conditions'])
                                ? $proposal['terms_and_conditions']
                                : crm_setting('crm_default_terms_and_conditions', '')
                        ) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="app-form-label" style="display:flex;align-items:center;gap:7px;">
                            Internal Notes
                            <span style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                  background:var(--bg);border:1px solid var(--border);color:var(--ink-faint);
                                  padding:1px 6px;border-radius:4px;">Team only</span>
                        </label>
                        <textarea name="internal_notes" class="pf-control" rows="3"
                                  placeholder="Not visible to client…"><?= html_escape($proposal['internal_notes'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- ══ Footer ════════════════════════════════════════════════════ -->
        <div class="pf-footer">
            <div class="pf-footer-info">
                <i class="ti ti-info-circle" style="font-size:14px;"></i>
                Fields marked <span style="color:var(--red);font-weight:700;">*</span> are required before saving.
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <a href="<?= site_url('crm/proposals') ?>" class="pf-btn-cancel">Cancel</a>
                <button type="submit" class="pf-btn-submit">
                    <i class="ti ti-device-floppy"></i> Save Proposal
                </button>
            </div>
        </div>

    </form>
</div>

<script>
(function () {
    'use strict';

    /* ── Refs ──────────────────────────────────────────────────────────── */
    const tbody             = document.getElementById('pf_items_body');
    const addBtn            = document.getElementById('pf_add_row');
    const emptyState        = document.getElementById('pf_empty_state');
    const discTypeEl        = document.getElementById('pf_discount_type');
    const discValueEl       = document.getElementById('pf_discount_value');
    const discHintEl        = document.getElementById('pf_disc_hint');
    const discRowEl         = document.getElementById('pf_disc_row');
    const discLabelEl       = document.getElementById('pf_disc_label');
    const discDispEl        = document.getElementById('pf_disc_disp');
    const taxRateEl         = document.getElementById('pf_tax_rate');
    const taxRowEl          = document.getElementById('pf_tax_row');
    const taxLabelEl        = document.getElementById('pf_tax_label');
    const taxDispEl         = document.getElementById('pf_tax_disp');
    const subHidden         = document.getElementById('pf_subtotal');
    const discHidden        = document.getElementById('pf_discount_amount');
    const taxHidden         = document.getElementById('pf_tax_amount');
    const totalHidden       = document.getElementById('pf_total_value');
    const subDispEl         = document.getElementById('pf_subtotal_disp');
    const totalDispEl       = document.getElementById('pf_total_disp');
    const statusSel         = document.getElementById('pf_status');
    const statusBadge       = document.getElementById('pf_status_badge');
    const startDateEl       = document.getElementById('pf_start_date');
    const goLiveDateEl      = document.getElementById('pf_go_live_date');
    const expiresAtEl       = document.getElementById('pf_expires_at');
    const dateWarnEl        = document.getElementById('pf_date_warn');
    const expireWarnEl      = document.getElementById('pf_expire_warn');
    const scopeInput        = document.getElementById('pf_discount_scope');
    const totalDiscControls = document.getElementById('pf_total_disc_controls');
    const scopeDesc         = document.getElementById('pf_scope_desc');

    /* ── Helpers ──────────────────────────────────────────────────────── */
    const fmt = n => n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

    /* ── Status badge ─────────────────────────────────────────────────── */
    const STATUS_STYLES = {
        draft:          {bg:'#f1f5f9',color:'#475569'},
        pending_review: {bg:'#fef9c3',color:'#92400e'},
        sent:           {bg:'#dbeafe',color:'#1e40af'},
        viewed:         {bg:'#ede9fe',color:'#5b21b6'},
        approved:       {bg:'#d1fae5',color:'#065f46'},
        declined:       {bg:'#fee2e2',color:'#991b1b'},
        expired:        {bg:'#fce7f3',color:'#9d174d'},
        cancelled:      {bg:'#f1f5f9',color:'#94a3b8'},
    };
    function updateStatusBadge() {
        if (!statusSel || !statusBadge) return;
        const v   = statusSel.value;
        const s   = STATUS_STYLES[v] || STATUS_STYLES.draft;
        const lbl = statusSel.options[statusSel.selectedIndex]?.text || v;
        statusBadge.style.cssText = `background:${s.bg};color:${s.color};display:inline-flex;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;vertical-align:middle;margin-left:6px;`;
        statusBadge.textContent   = lbl;
    }

    /* ── Date validation ──────────────────────────────────────────────── */
    function validateDates() {
        const s = startDateEl?.value || '', g = goLiveDateEl?.value || '', e = expiresAtEl?.value || '';
        if (dateWarnEl)   dateWarnEl.style.display   = (s && g && g < s) ? 'block' : 'none';
        if (expireWarnEl) expireWarnEl.style.display = (s && e && e < s) ? 'block' : 'none';
    }

    /* ── Row number refresh ───────────────────────────────────────────── */
    function refreshNums() {
        tbody.querySelectorAll('.pf-item-row').forEach((tr, i) => {
            const b = tr.querySelector('.pf-row-num');
            if (b) b.textContent = i + 1;
        });
    }

    /* ── Discount scope ───────────────────────────────────────────────── */
    /* Columns to toggle: th and td with class col-disc-type / col-disc-val / col-disc-amt */
    function applyScope(scope) {
        const isLine  = scope === 'line';
        const isTotal = scope === 'total';

        /* Toggle scope buttons */
        document.querySelectorAll('.pf-scope-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.scope === scope);
        });

        /* Update hidden input */
        if (scopeInput) scopeInput.value = scope;

        /* Update description */
        if (scopeDesc) {
            scopeDesc.textContent = isLine
                ? 'Discount applied per line — total-level discount hidden.'
                : 'Single discount applied to the overall total.';
        }

        /* Show/hide total-level discount controls */
        if (totalDiscControls) {
            totalDiscControls.style.display = isLine ? 'none' : '';
        }

        /* Show/hide per-row discount columns (th + td) */
        const discCols = document.querySelectorAll('.col-disc-type, .col-disc-val, .col-disc-amt');
        discCols.forEach(el => { el.style.display = isLine ? '' : 'none'; });

        /* When switching to "on total", zero out all row-level discounts so they don't affect calc */
        if (isTotal) {
            tbody.querySelectorAll('.pf-item-row').forEach(tr => {
                const dt = tr.querySelector('.pf-item-disc-type');
                const dv = tr.querySelector('.pf-item-disc-val');
                const da = tr.querySelector('.pf-item-disc-amt');
                if (dt) dt.value = 'none';
                if (dv) dv.value = '0';
                if (da) da.value = '0.00';
            });
        }

        /* When switching to "per line", zero out total-level discount */
        if (isLine) {
            if (discTypeEl)  discTypeEl.value  = 'none';
            if (discValueEl) discValueEl.value = '0';
        }

        recalcAll();
    }

    /* ── New row HTML ─────────────────────────────────────────────────── */
    function makeRow(n) {
        const scope      = scopeInput ? scopeInput.value : 'total';
        const discHidden = scope === 'total' ? 'display:none;' : '';
        return `<tr class="pf-item-row">
            <td><div class="pf-row-num">${n}</div></td>
            <td><select name="item_type[]" class="pf-cell" style="min-width:98px;">
                <option value="service">Service</option><option value="setup_fee">Setup Fee</option>
                <option value="addon">Addon</option><option value="other">Other</option>
            </select></td>
            <td><input type="text" name="item_name[]" class="pf-cell" placeholder="Item name"></td>
            <td><input type="text" name="item_description[]" class="pf-cell" placeholder="Optional"></td>
            <td><input type="number" step="0.01" min="0" name="item_quantity[]" class="pf-cell pf-item-qty" value="1"></td>
            <td><input type="number" step="0.01" min="0" name="item_unit_price[]" class="pf-cell pf-item-price" value="0.00"></td>
            <td class="col-disc-type" style="${discHidden}"><select name="item_discount_type[]" class="pf-cell pf-item-disc-type" style="min-width:98px;">
                <option value="none">None</option><option value="percent">Percent (%)</option><option value="fixed">Fixed ($)</option>
            </select></td>
            <td class="col-disc-val" style="${discHidden}"><input type="number" step="0.01" min="0" name="item_discount_value[]" class="pf-cell pf-item-disc-val" value="0"></td>
            <td class="col-disc-amt" style="${discHidden}"><input type="number" step="0.01" name="item_discount_amount[]" class="pf-cell pf-cell-computed pf-item-disc-amt" value="0.00" readonly tabindex="-1"></td>
            <td><input type="number" step="0.01" name="item_line_total[]" class="pf-cell bg-light-primary pf-item-line-total" value="0.00" readonly tabindex="-1"></td>
            <td><button type="button" class="btn btn-light-danger icon-btn pf-remove-row"><i class="ti ti-trash"></i></button></td>
        </tr>`;
    }

    /* ── Per-row calc ─────────────────────────────────────────────────── */
    function calcRow(tr) {
        const qty   = parseFloat(tr.querySelector('.pf-item-qty')?.value      || 0);
        const price = parseFloat(tr.querySelector('.pf-item-price')?.value    || 0);
        const scope = scopeInput ? scopeInput.value : 'total';
        let dAmt    = 0;

        if (scope === 'line') {
            const dtype = tr.querySelector('.pf-item-disc-type')?.value || 'none';
            const dval  = parseFloat(tr.querySelector('.pf-item-disc-val')?.value || 0);
            const gross = qty * price;
            dAmt = dtype === 'percent' ? gross * dval / 100
                 : dtype === 'fixed'   ? Math.min(dval, gross) : 0;
            const da = tr.querySelector('.pf-item-disc-amt');
            if (da) da.value = dAmt.toFixed(2);
        }

        const gross     = qty * price;
        const lineTotal = Math.max(0, gross - dAmt);
        const lEl       = tr.querySelector('.pf-item-line-total');
        if (lEl) lEl.value = lineTotal.toFixed(2);
        return lineTotal;
    }

    /* ── Full recalc ──────────────────────────────────────────────────── */
    function recalcAll() {
        let sub = 0;
        tbody.querySelectorAll('.pf-item-row').forEach(tr => { sub += calcRow(tr); });

        const scope   = scopeInput ? scopeInput.value : 'total';
        const taxRate = parseFloat(taxRateEl?.value || 0);
        let   dAmt    = 0;

        if (scope === 'total') {
            const dtype = discTypeEl?.value  || 'none';
            const dval  = parseFloat(discValueEl?.value || 0);
            dAmt = dtype === 'percent' ? sub * dval / 100
                 : dtype === 'fixed'   ? Math.min(dval, sub) : 0;

            /* Update discount hint */
            if (discHintEl) {
                discHintEl.textContent = (dtype === 'none' || dval === 0) ? ''
                    : dtype === 'percent' ? `= $${fmt(dAmt)} off`
                    : `$${fmt(dval)} flat`;
            }
        } else {
            /* In line-item mode the row discounts are baked into line totals already;
               sub already reflects post-discount line totals from calcRow */
            if (discHintEl) discHintEl.textContent = '';
        }

        const taxBase = Math.max(0, sub - dAmt);
        const taxAmt  = taxBase * taxRate / 100;
        const total   = taxBase + taxAmt;

        /* Hidden form fields */
        if (subHidden)   subHidden.value   = sub.toFixed(2);
        if (discHidden)  discHidden.value  = dAmt.toFixed(2);
        if (taxHidden)   taxHidden.value   = taxAmt.toFixed(2);
        if (totalHidden) totalHidden.value = total.toFixed(2);

        /* Display */
        if (subDispEl) subDispEl.textContent = '$' + fmt(sub);

        if (discRowEl) {
            if (dAmt > 0) {
                discRowEl.style.display = 'flex';
                const dtype = discTypeEl?.value || 'none';
                const dval  = parseFloat(discValueEl?.value || 0);
                if (discLabelEl) discLabelEl.textContent = scope === 'line' ? 'Line Discounts'
                    : dtype === 'percent' ? `Discount (${dval}%)` : 'Discount (fixed)';
                if (discDispEl) discDispEl.textContent = '−$' + fmt(dAmt);
            } else { discRowEl.style.display = 'none'; }
        }

        if (taxRowEl) {
            if (taxRate > 0) {
                taxRowEl.style.display = 'flex';
                if (taxLabelEl) taxLabelEl.textContent = `Tax (${taxRate}%)`;
                if (taxDispEl)  taxDispEl.textContent  = '+$' + fmt(taxAmt);
            } else { taxRowEl.style.display = 'none'; }
        }

        if (totalDispEl) totalDispEl.textContent = fmt(total);

        toggleEmptyState();
    }

    /* ── Empty state ──────────────────────────────────────────────────── */
    function toggleEmptyState() {
        if (emptyState) emptyState.style.display = tbody.querySelectorAll('.pf-item-row').length > 0 ? 'none' : 'block';
    }

    /* ── Add row ──────────────────────────────────────────────────────── */
    if (addBtn) addBtn.addEventListener('click', () => {
        const n = tbody.querySelectorAll('.pf-item-row').length + 1;
        tbody.insertAdjacentHTML('beforeend', makeRow(n));
        toggleEmptyState();
        const rows = tbody.querySelectorAll('.pf-item-row');
        rows[rows.length - 1]?.querySelector('input[name="item_name[]"]')?.focus();
    });

    /* ── Remove row ───────────────────────────────────────────────────── */
    document.addEventListener('click', e => {
        /* Scope buttons */
        const scopeBtn = e.target.closest('.pf-scope-btn');
        if (scopeBtn) { applyScope(scopeBtn.dataset.scope); return; }

        /* Remove row */
        const btn = e.target.closest('.pf-remove-row');
        if (!btn) return;
        const rows = tbody.querySelectorAll('.pf-item-row');
        if (rows.length > 1) { btn.closest('.pf-item-row').remove(); refreshNums(); }
        else {
            const tr = btn.closest('.pf-item-row');
            tr.querySelectorAll('input[type="text"]').forEach(el => el.value = '');
            tr.querySelectorAll('input[type="number"]').forEach(el => el.value = '0');
            tr.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
        }
        recalcAll();
    });

    /* ── Live events ──────────────────────────────────────────────────── */
    document.addEventListener('input', e => {
        if (e.target.closest('.pf-item-row') ||
            e.target === discTypeEl || e.target === discValueEl || e.target === taxRateEl) {
            recalcAll();
        }
    });
    document.addEventListener('change', e => {
        if (e.target.closest('.pf-item-row') ||
            e.target === discTypeEl || e.target === discValueEl || e.target === taxRateEl) {
            recalcAll();
        }
        if (e.target === statusSel) updateStatusBadge();
        if ([startDateEl, goLiveDateEl, expiresAtEl].includes(e.target)) validateDates();
    });

    /* ── Init ─────────────────────────────────────────────────────────── */
    updateStatusBadge();
    validateDates();

    /* Apply saved/default scope without zeroing out saved row data */
    const initScope = scopeInput ? scopeInput.value : 'total';
    /* Just toggle UI, don't reset values */
    document.querySelectorAll('.pf-scope-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.scope === initScope);
    });
    if (totalDiscControls) totalDiscControls.style.display = initScope === 'line' ? 'none' : '';
    document.querySelectorAll('.col-disc-type, .col-disc-val, .col-disc-amt')
        .forEach(el => { el.style.display = initScope === 'line' ? '' : 'none'; });
    if (scopeDesc) {
        scopeDesc.textContent = initScope === 'line'
            ? 'Discount applied per line — total-level discount hidden.'
            : 'Single discount applied to the overall total.';
    }

    recalcAll();

    if (tbody.querySelectorAll('.pf-item-row').length === 0) {
        tbody.insertAdjacentHTML('beforeend', makeRow(1));
        toggleEmptyState();
    }

})();
</script>