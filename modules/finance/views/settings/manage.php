<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<style>
/* ===== Section Card ===== */
.fs-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 18px;
    height: 100%;
}

.fs-section-hd {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 5px 10px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.fs-section-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e6f4f1;
    border-radius: 7px;
    color: #056464;
}

.fs-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #0d1b2a;
}

.fs-section-body {
    padding: 10px 20px;
}

/* ===== Field ===== */
.fs-field {
    display: flex;
    gap: 14px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.fs-field:last-child {
    border-bottom: none;
}

.fs-field-lbl {
    width: 220px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
}

.fs-field-hint {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 400;
}

.fs-field-ctrl {
    flex: 1;
}

.fs-field-ctrl { flex: 1; min-width: 0; }
.fs-field-top { align-items: flex-start; }
.fs-field-top .fs-field-lbl { padding-top: 6px; }

/* ===== Inputs ===== */
.fs-input,
.fs-select,
.fs-textarea {
    width: 100%;
    padding: 7px 10px;
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    font-size: 13px;
}

.fs-input:focus,
.fs-select:focus,
.fs-textarea:focus {
    border-color: #056464;
    outline: none;
}

/* ===== Toggle ===== */
.fs-toggle-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 11px 0;
    border-bottom: 1px solid #f1f5f9;
}

.fs-toggle-row:last-child {
    border-bottom: none;
}

.fs-toggle-lbl {
    font-size: 13px;
    font-weight: 600;
}

.fs-toggle-desc {
    font-size: 11.5px;
    color: #94a3b8;
}

/* ===== Switch ===== */
.fs-switch {
    position: relative;
    width: 40px;
    height: 22px;
}

.fs-switch input {
    display: none;
}

.fs-switch-track {
    position: absolute;
    inset: 0;
    background: #cbd5e1;
    border-radius: 20px;
}

.fs-switch-track:after {
    content: '';
    position: absolute;
    left: 3px;
    top: 3px;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    transition: .2s;
}

.fs-switch input:checked + .fs-switch-track {
    background: #056464;
}

.fs-switch input:checked + .fs-switch-track:after {
    transform: translateX(18px);
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .fs-field {
        flex-direction: column;
    }

    .fs-field-lbl {
        width: 100%;
    }
}

/* ── Checkbox grid ───────────────────────────────────────── */
.fs-cb-grid { 
    grid-template-columns: 1fr 1fr; 
}

@media (max-width: 768px) {
    .fs-cb-grid {
        grid-template-columns: 1fr;
    }
}

.fs-num-unit {
    padding: 0 11px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 0 7px 7px 0;
    font-size: 11.5px;
    font-weight: 600;
    color: #94a3b8;
    white-space: nowrap;
    display: flex;
    align-items: center;
}

/* number + unit */
.fs-num-wrap { display: inline-flex; align-items: stretch; }
.fs-num-wrap .fs-input { border-radius: 7px 0 0 7px; border-right: none; }
.fs-num-unit {
    padding: 0 11px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 0 7px 7px 0;
    font-size: 11.5px;
    font-weight: 600;
    color: #94a3b8;
    white-space: nowrap;
    display: flex;
    align-items: center;
}

.fs-cb-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    cursor: pointer;
    background: #ffffff;
    transition: border-color .12s, background .12s;
}
.fs-cb-item:hover { border-color: #056464; background: #e6f4f1; }
.fs-cb-item input { accent-color: #056464; width: 14px; height: 14px; flex-shrink: 0; cursor: pointer; }
.fs-cb-item label { font-size: 12.5px; font-weight: 500; color: #475569; cursor: pointer; display: flex; align-items: center; gap: 5px; margin: 0; }
.fs-cb-item label i { font-size: 13px; color: #94a3b8; }

/* ── Radio pills ─────────────────────────────────────────── */
.fs-radio-wrap { display: flex; gap: 7px; flex-wrap: wrap; }
.fs-radio-wrap input[type=radio] { display: none; }
.fs-radio-wrap label {
    padding: 6px 13px;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all .12s;
    background: #ffffff;
    margin: 0;
}
.fs-radio-wrap input[type=radio]:checked + label {
    border-color: #056464;
    background: #e6f4f1;
    color: #056464;
    font-weight: 600;
}

/* ── Collection stages grid ──────────────────────────────── */
.fs-stages { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 4px; }
.fs-stage-lbl { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; margin-bottom: 5px; }

</style>

<div class="container-fluid">
<?= form_open(site_url('finance/settings'), ['method' => 'post', 'id' => 'financeSettingsForm']) ?>

    <div class="fin-page-header mb-3 d-flex align-items-center justify-content-between">
        <div class="fin-page-icon me-3">
            <i class="ti ti-users"></i>
        </div>

        <div class="flex-grow-1">
            <div class="fin-page-title">
                <?= html_escape($page_title ?? 'All Invoices') ?>
            </div>

            <div class="fin-page-sub">
                Configure invoices, payments, expenses, taxes, accounting, and automation.
            </div>
        </div>

        <div class="ms-auto d-flex gap-2">
    
            <button type="submit" class="btn btn-primary btn-header">
                <i class="ti ti-device-floppy"></i> Save Settings
            </button>

        </div>
    </div>
    

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="fs-section">
            <div class="fs-section-hd">
                <div class="fs-section-icon"><i class="ti ti-file-invoice"></i></div>
                <div>
                    <div class="fs-section-title">Invoice &amp; Billing</div>
                </div>
            </div>
            <div class="fs-section-body">

                <div class="fs-field">
                    <div class="fs-field-lbl">Invoice # Prefix
                        <div class="fs-field-hint">Prepended to every invoice number</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <input type="text" class="fs-input fs-input-md"
                               name="settings[finance_invoice_prefix]"
                               value="<?= e($existing_data['finance_invoice_prefix'] ?? 'INV-') ?>">
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Next Invoice Number
                        <div class="fs-field-hint">Sequence number for the next invoice created</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <input type="number" class="fs-input fs-input-md"
                               name="settings[finance_invoice_start_num]" min="1"
                               value="<?= e($existing_data['finance_invoice_start_num'] ?? 1000) ?>">
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Invoice Template
                        <div class="fs-field-hint">Default PDF / print layout</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $sel_tpl = $existing_data['finance_invoice_template'] ?? 'modern'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_invoice_template]">
                            <?php foreach (finance_invoice_templates() as $tpl): ?>
                                <option value="<?= html_escape($tpl['key']) ?>"
                                    <?= $sel_tpl === $tpl['key'] ? 'selected' : '' ?>>
                                    <?= html_escape($tpl['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Payment Terms
                        <div class="fs-field-hint">Pre-selected on new invoices</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $terms = $existing_data['finance_payment_terms'] ?? 'net_15'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_payment_terms]">
                            <option value="net_0"  <?= $terms === 'net_0'  ? 'selected' : '' ?>>Net 0 — Due on receipt</option>
                            <option value="net_7"  <?= $terms === 'net_7'  ? 'selected' : '' ?>>Net 7</option>
                            <option value="net_15" <?= $terms === 'net_15' ? 'selected' : '' ?>>Net 15</option>
                            <option value="net_30" <?= $terms === 'net_30' ? 'selected' : '' ?>>Net 30</option>
                            <option value="net_45" <?= $terms === 'net_45' ? 'selected' : '' ?>>Net 45</option>
                            <option value="net_60" <?= $terms === 'net_60' ? 'selected' : '' ?>>Net 60</option>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Due Date
                        <div class="fs-field-hint">Days from invoice date until due</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <div class="fs-num-wrap">
                            <input type="number" class="fs-input fs-input-sm"
                                   name="settings[finance_invoice_due_days]" min="0"
                                   value="<?= e($existing_data['finance_invoice_due_days'] ?? 30) ?>">
                            <span class="fs-num-unit">days</span>
                        </div>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Discount Type
                        <div class="fs-field-hint">Pre-selected discount method on new invoices</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $disc = $existing_data['finance_default_discount_type'] ?? 'none'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_default_discount_type]">
                            <option value="none"    <?= $disc === 'none'    ? 'selected' : '' ?>>No Discount</option>
                            <option value="percent" <?= $disc === 'percent' ? 'selected' : '' ?>>Percentage (%)</option>
                            <option value="fixed"   <?= $disc === 'fixed'   ? 'selected' : '' ?>>Fixed Amount</option>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">PO Number Field
                        <div class="fs-field-hint">Controls the Purchase Order field on invoices</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $po = $existing_data['finance_po_number_required'] ?? 'optional'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_po_number_required]">
                            <option value="hidden"   <?= $po === 'hidden'   ? 'selected' : '' ?>>Hidden</option>
                            <option value="optional" <?= $po === 'optional' ? 'selected' : '' ?>>Optional</option>
                            <option value="required" <?= $po === 'required' ? 'selected' : '' ?>>Required</option>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Bank Account
                        <div class="fs-field-hint">Pre-selected when recording payments</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php
                        $sel_bank = $existing_data['finance_default_bank_account'] ?? '';
                        $accounts = get_bank_accounts_dropdown(['blank_text' => '— Select —']);
                        ?>
                        <select class="fs-select fs-input-md" name="settings[finance_default_bank_account]">
                            <?php foreach ($accounts as $aid => $alabel): ?>
                                <option value="<?= (int)$aid ?>"
                                    <?= ((string)$sel_bank === (string)$aid) ? 'selected' : '' ?>>
                                    <?= html_escape($alabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Reminder Frequency
                        <div class="fs-field-hint">Days between automated payment reminders</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <div class="fs-num-wrap">
                            <input type="number" class="fs-input fs-input-sm"
                                   name="settings[finance_reminder_frequency]" min="1"
                                   value="<?= e($existing_data['finance_reminder_frequency'] ?? 7) ?>">
                            <span class="fs-num-unit">days</span>
                        </div>
                    </div>
                </div>

                <div class="fs-field fs-field-top">
                    <div class="fs-field-lbl">Default Terms &amp; Conditions
                        <div class="fs-field-hint">Pre-filled on every new invoice</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <textarea class="fs-textarea" rows="4"
                                  name="settings[finance_invoice_terms]"
                                  placeholder="Enter default invoice terms…"><?= e($existing_data['finance_invoice_terms'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="fs-sub">
                    <div class="fs-sub-title"><i class="ti ti-bolt"></i> Invoice Automation</div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Auto-mark Invoice as Paid</span>
                            <span class="fs-toggle-desc">Set status to <code>paid</code> automatically when full payment is recorded.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_auto_mark_paid]" value="1"
                                   <?= !empty($existing_data['finance_auto_mark_paid']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Auto-mark Invoices as Overdue</span>
                            <span class="fs-toggle-desc">Change status to <code>overdue</code> when <code>due_date</code> passes and invoice is unpaid.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_auto_overdue_status]" value="1"
                                   <?= !empty($existing_data['finance_auto_overdue_status']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Auto-send New Invoices</span>
                            <span class="fs-toggle-desc">Automatically email invoices to clients upon creation.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_auto_send_invoices]" value="1"
                                   <?= !empty($existing_data['finance_auto_send_invoices']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Auto-send Payment Reminders</span>
                            <span class="fs-toggle-desc">Send automated reminders for overdue invoices per the reminder frequency above.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_auto_reminders]" value="1"
                                   <?= !empty($existing_data['finance_auto_reminders']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Track Invoice Views</span>
                            <span class="fs-toggle-desc">Record <code>viewed_at</code> timestamp when client opens the invoice link.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_track_invoice_views]" value="1"
                                   <?= !empty($existing_data['finance_track_invoice_views']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Allow Partial Payments</span>
                            <span class="fs-toggle-desc">Allow clients to pay less than full balance, setting status to <code>partial</code>.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_allow_partial_payments]" value="1"
                                   <?= !empty($existing_data['finance_allow_partial_payments']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                </div>

            </div>
        </div>
    </div>    

    <div class="col-md-6 mb-3">
        <div class="fs-section">
            <div class="fs-section-hd">
                <div class="fs-section-icon"><i class="ti ti-currency-dollar"></i></div>
                <div>
                    <div class="fs-section-title">Payments &amp; Taxes</div>
                </div>
            </div>
            <div class="fs-section-body">

                <?php
                $sel_bc     = $existing_data['finance_base_currency'] ?? 'USD';
                $currencies = finance_currency_dropdown($sel_bc);
                ?>

                <div class="fs-field">
                    <div class="fs-field-lbl">Base Currency
                        <div class="fs-field-hint">Used for all invoices and reports
                            <span class="fs-cur-preview" id="financeCurrencyPreviewInvoice"><?= invc_format(1234.56, $sel_bc) ?></span>
                        </div>
                    </div>
                    <div class="fs-field-ctrl">
                        <select class="fs-select fs-input-md" name="settings[finance_base_currency]"
                                id="financeBaseCurrencyInvoice">
                            <?php foreach ($currencies as $code => $label): ?>
                                <option value="<?= $code ?>" <?= $sel_bc === $code ? 'selected' : '' ?>>
                                    <?= html_escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Tax Name / Label
                        <div class="fs-field-hint">Displayed on invoices e.g. VAT, GST, Sales Tax</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <input type="text" class="fs-input fs-input-md"
                               name="settings[finance_tax_label]"
                               placeholder="e.g. VAT, GST, Sales Tax"
                               value="<?= e($existing_data['finance_tax_label'] ?? 'Tax') ?>">
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Tax Calculation
                        <div class="fs-field-hint">Whether tax is added on top or included in price</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $tax_inc = $existing_data['finance_tax_inclusive'] ?? ''; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_tax_inclusive]">
                            <option value="none"      <?= ($tax_inc === '' || $tax_inc === 'none') ? 'selected' : '' ?>>None</option>
                            <option value="exclusive" <?= ((string)$tax_inc === '0' || $tax_inc === 'exclusive') ? 'selected' : '' ?>>Exclusive — added on top</option>
                            <option value="inclusive" <?= ((string)$tax_inc === '1' || $tax_inc === 'inclusive') ? 'selected' : '' ?>>Inclusive — already in price</option>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Tax Rate
                        <div class="fs-field-hint">Applied to new invoices unless overridden per line</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <div class="fs-num-wrap">
                            <input type="number" class="fs-input fs-input-sm"
                                   step="0.01" min="0" max="100"
                                   name="settings[finance_default_tax]"
                                   value="<?= e($existing_data['finance_default_tax'] ?? 0) ?>">
                            <span class="fs-num-unit">%</span>
                        </div>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Late Payment Fee
                        <div class="fs-field-hint">Percentage added to overdue invoice balance</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <div class="fs-num-wrap">
                            <input type="number" class="fs-input fs-input-sm"
                                   step="0.01" min="0" max="100"
                                   name="settings[finance_late_fee_percent]"
                                   value="<?= e($existing_data['finance_late_fee_percent'] ?? 0) ?>">
                            <span class="fs-num-unit">%</span>
                        </div>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Payment Mode
                        <div class="fs-field-hint">Pre-selected when recording a payment</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $mode = $existing_data['finance_default_payment_mode'] ?? 'ach'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_default_payment_mode]">
                            <option value="cash"           <?= $mode === 'cash'           ? 'selected' : '' ?>>Cash</option>
                            <option value="check"          <?= $mode === 'check'          ? 'selected' : '' ?>>Check</option>
                            <option value="ach"            <?= $mode === 'ach'            ? 'selected' : '' ?>>ACH Transfer</option>
                            <option value="wire"           <?= $mode === 'wire'           ? 'selected' : '' ?>>Wire Transfer</option>
                            <option value="credit_card"    <?= $mode === 'credit_card'    ? 'selected' : '' ?>>Credit Card</option>
                            <option value="digital_wallet" <?= $mode === 'digital_wallet' ? 'selected' : '' ?>>Digital Wallet</option>
                            <option value="other"          <?= $mode === 'other'          ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Payment Currency
                        <div class="fs-field-hint">Currency pre-selected on the payment form</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $sel_pc = $existing_data['finance_default_payment_currency'] ?? 'USD'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_default_payment_currency]">
                            <?php foreach (finance_currency_dropdown($sel_pc) as $code => $label): ?>
                                <option value="<?= $code ?>" <?= $sel_pc === $code ? 'selected' : '' ?>>
                                    <?= html_escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Exchange Rate
                        <div class="fs-field-hint">Used when payment currency differs from base</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <input type="number" class="fs-input fs-input-md"
                               step="0.000001" min="0"
                               name="settings[finance_default_exchange_rate]"
                               value="<?= e($existing_data['finance_default_exchange_rate'] ?? 1) ?>">
                    </div>
                </div>

                <div class="fs-sub">
                    <div class="fs-sub-title"><i class="ti ti-credit-card"></i> Accepted Payment Methods</div>
                    <?php
                    $pm_enabled = [];
                    if (!empty($existing_data['finance_payment_methods_enabled'])) {
                        $pm_enabled = is_array($existing_data['finance_payment_methods_enabled'])
                            ? $existing_data['finance_payment_methods_enabled']
                            : json_decode($existing_data['finance_payment_methods_enabled'], true);
                        $pm_enabled = is_array($pm_enabled) ? $pm_enabled : [];
                    }
                    $all_methods = finance_payment_methods();
                    ?>
                    <div class="fs-cb-grid">
                        <?php foreach ($all_methods as $key => $method): ?>
                            <div class="fs-cb-item">
                                <input type="checkbox"
                                       id="method_<?= html_escape($key) ?>"
                                       name="settings[finance_payment_methods_enabled][]"
                                       value="<?= html_escape($key) ?>"
                                       <?= in_array($key, $pm_enabled, true) ? 'checked' : '' ?>>
                                <label for="method_<?= html_escape($key) ?>">
                                    <?php if (!empty($method['icon'])): ?>
                                        <i class="<?= html_escape($method['icon']) ?>"></i>
                                    <?php endif; ?>
                                    <?= html_escape($method['label']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="fs-sub">
                    <div class="fs-sub-title"><i class="ti ti-bolt"></i> Payment Automation</div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Require Reference No. on Payments</span>
                            <span class="fs-toggle-desc">Makes <code>fin_payments.reference_no</code> mandatory when recording a payment.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_require_payment_reference]" value="1"
                                   <?= !empty($existing_data['finance_require_payment_reference']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Auto-allocate Payments to Oldest Invoice</span>
                            <span class="fs-toggle-desc">Automatically populate <code>fin_payment_allocations</code> from the oldest unpaid invoice first.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_auto_allocate_payments]" value="1"
                                   <?= !empty($existing_data['finance_auto_allocate_payments']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="fs-section">
            <div class="fs-section-hd">
                <div class="fs-section-icon"><i class="ti ti-wallet"></i></div>
                <div>
                    <div class="fs-section-title">Expenses &amp; Budgeting</div>
                </div>
            </div>
            <div class="fs-section-body">

                <?php
                $sel_ec  = $existing_data['finance_expense_currency'] ?? 'USD';
                $exp_cur = finance_currency_dropdown($sel_ec);
                ?>

                <div class="fs-field">
                    <div class="fs-field-lbl">Expense Base Currency
                        <div class="fs-field-hint">Default currency for expense entries
                            <span class="fs-cur-preview" id="financeCurrencyPreviewExpense"><?= expc_format(1234.56, $sel_ec) ?></span>
                        </div>
                    </div>
                    <div class="fs-field-ctrl">
                        <select class="fs-select fs-input-md" name="settings[finance_expense_currency]"
                                id="financeBaseCurrencyExpense">
                            <?php foreach ($exp_cur as $code => $label): ?>
                                <option value="<?= $code ?>" <?= $sel_ec === $code ? 'selected' : '' ?>>
                                    <?= html_escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Auto-approval Limit
                        <div class="fs-field-hint">Expenses below this amount are auto-approved</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <input type="number" class="fs-input fs-input-md"
                               step="0.01" min="0"
                               name="settings[finance_expense_auto_approve_limit]"
                               value="<?= e($existing_data['finance_expense_auto_approve_limit'] ?? 100) ?>">
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Budget Alert Threshold
                        <div class="fs-field-hint">Alert when this % of budget is consumed</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <div class="fs-num-wrap">
                            <input type="number" class="fs-input fs-input-sm"
                                   step="1" min="0" max="100"
                                   name="settings[finance_budget_alert_threshold]"
                                   value="<?= e($existing_data['finance_budget_alert_threshold'] ?? 90) ?>">
                            <span class="fs-num-unit">%</span>
                        </div>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Expense Category
                        <div class="fs-field-hint">Pre-selected when adding a new expense</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $sel_cat = $existing_data['finance_default_expense_category'] ?? ''; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_default_expense_category]">
                            <option value="">— None —</option>
                            <?php if (!empty($expense_categories)):
                                foreach ($expense_categories as $cat): ?>
                                    <option value="<?= (int)$cat->id ?>"
                                            <?= (string)$sel_cat === (string)$cat->id ? 'selected' : '' ?>>
                                        <?= html_escape($cat->category_name) ?>
                                    </option>
                                <?php endforeach;
                            endif; ?>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Billable Expense Default
                        <div class="fs-field-hint">Default billable flag on new expenses</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $bill = $existing_data['finance_expense_billable_default'] ?? '0'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_expense_billable_default]">
                            <option value="0" <?= $bill === '0' ? 'selected' : '' ?>>Not Billable</option>
                            <option value="1" <?= $bill === '1' ? 'selected' : '' ?>>Billable to Client</option>
                        </select>
                    </div>
                </div>

                <div class="fs-sub">
                    <div class="fs-sub-title"><i class="ti ti-shield-check"></i> Approval &amp; Compliance</div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Require Expense Approval</span>
                            <span class="fs-toggle-desc">Expenses must be approved before affecting financial reports.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_require_expense_approval]" value="1"
                                   <?= !empty($existing_data['finance_require_expense_approval']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Require Receipts for Expenses</span>
                            <span class="fs-toggle-desc">Receipt upload is mandatory when submitting an expense.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_require_receipts]" value="1"
                                   <?= !empty($existing_data['finance_require_receipts']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Auto-match Receipts</span>
                            <span class="fs-toggle-desc">Automatically match uploaded receipts to expense entries.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_auto_match_receipts]" value="1"
                                   <?= !empty($existing_data['finance_auto_match_receipts']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Notify on Expense Approval / Rejection</span>
                            <span class="fs-toggle-desc">Send notification when <code>fin_expenses.status</code> changes to <code>approved</code> or <code>rejected</code>.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_notify_on_expense_approval]" value="1"
                                   <?= !empty($existing_data['finance_notify_on_expense_approval']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Allow Expense Reimbursement</span>
                            <span class="fs-toggle-desc">Enable the <code>reimbursed</code> status flow in <code>fin_expenses</code>.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_allow_expense_reimbursement]" value="1"
                                   <?= !empty($existing_data['finance_allow_expense_reimbursement']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                    <div class="fs-toggle-row">
                        <div class="fs-toggle-info">
                            <span class="fs-toggle-lbl">Auto-close Accounting Periods</span>
                            <span class="fs-toggle-desc">Automatically close accounting periods at month-end.</span>
                        </div>
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_auto_close_books]" value="1"
                                   <?= !empty($existing_data['finance_auto_close_books']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="fs-section">
            <div class="fs-section-hd">
                <div class="fs-section-icon"><i class="ti ti-chart-bar"></i></div>
                <div>
                    <div class="fs-section-title">Accounting &amp; Reports</div>
                </div>
            </div>
            <div class="fs-section-body">

                <div class="fs-field">
                    <div class="fs-field-lbl">Chart of Accounts Template
                        <div class="fs-field-hint">Starting structure for your chart of accounts</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $sel_coa = $existing_data['finance_coa_template'] ?? 'standard'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_coa_template]">
                            <?php foreach (finance_coa_templates() as $tpl): ?>
                                <option value="<?= html_escape($tpl['key']) ?>"
                                    <?= $sel_coa === $tpl['key'] ? 'selected' : '' ?>>
                                    <?= html_escape($tpl['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Accounting Method
                        <div class="fs-field-hint">Determines when income and expenses are recognised</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $acc_method = $existing_data['finance_accounting_method'] ?? 'accrual'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_accounting_method]">
                            <option value="accrual" <?= $acc_method === 'accrual' ? 'selected' : '' ?>>Accrual Basis</option>
                            <option value="cash"    <?= $acc_method === 'cash'    ? 'selected' : '' ?>>Cash Basis</option>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Financial Year Start Month
                        <div class="fs-field-hint">First month of your fiscal year</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php
                        $months = ['1'=>'January','2'=>'February','3'=>'March','4'=>'April',
                                   '5'=>'May','6'=>'June','7'=>'July','8'=>'August',
                                   '9'=>'September','10'=>'October','11'=>'November','12'=>'December'];
                        $sel_month = $existing_data['finance_fy_start_month'] ?? '1';
                        ?>
                        <select class="fs-select fs-input-md" name="settings[finance_fy_start_month]">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $sel_month == $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Reconciliation Frequency
                        <div class="fs-field-hint">How often bank reconciliations should run</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $freq = $existing_data['finance_reconciliation_frequency'] ?? 'monthly'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_reconciliation_frequency]">
                            <option value="daily"     <?= $freq === 'daily'     ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly"    <?= $freq === 'weekly'    ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly"   <?= $freq === 'monthly'   ? 'selected' : '' ?>>Monthly</option>
                            <option value="quarterly" <?= $freq === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Auto-reconcile Transactions
                        <div class="fs-field-hint">Automatically match bank transactions with invoices / payments</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <label class="fs-switch">
                            <input type="checkbox" name="settings[finance_auto_reconcile]" value="1"
                                   <?= !empty($existing_data['finance_auto_reconcile']) ? 'checked' : '' ?>>
                            <span class="fs-switch-track"></span>
                        </label>
                    </div>
                </div>

                <div class="fs-field fs-field-top">
                    <div class="fs-field-lbl">Default Report Period
                        <div class="fs-field-hint">Pre-selected when opening financial reports</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $report_period = $existing_data['finance_default_report_period'] ?? 'monthly'; ?>
                        <div class="fs-radio-wrap">
                            <?php foreach (['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly'] as $val => $lbl): ?>
                                <input type="radio" id="period_<?= $val ?>"
                                       name="settings[finance_default_report_period]"
                                       value="<?= $val ?>"
                                       <?= $report_period === $val ? 'checked' : '' ?>>
                                <label for="period_<?= $val ?>"><?= $lbl ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="fs-field fs-field-top">
                    <div class="fs-field-lbl">Export Report Formats
                        <div class="fs-field-hint">Available formats when exporting reports</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php
                        $report_formats = [];
                        if (!empty($existing_data['finance_report_formats'])) {
                            $report_formats = is_array($existing_data['finance_report_formats'])
                                ? $existing_data['finance_report_formats']
                                : json_decode($existing_data['finance_report_formats'], true);
                            $report_formats = is_array($report_formats) ? $report_formats : [];
                        }
                        ?>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <?php foreach (['pdf'=>'PDF','excel'=>'Excel','csv'=>'CSV'] as $fmt => $flbl): ?>
                                <div class="fs-cb-item" style="min-width:auto;">
                                    <input type="checkbox" id="format_<?= $fmt ?>"
                                           name="settings[finance_report_formats][]"
                                           value="<?= $fmt ?>"
                                           <?= in_array($fmt, $report_formats) ? 'checked' : '' ?>>
                                    <label for="format_<?= $fmt ?>"><?= $flbl ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="fs-section">
            <div class="fs-section-hd">
                <div class="fs-section-icon"><i class="ti ti-building-bank"></i></div>
                <div>
                    <div class="fs-section-title">Bank &amp; Reconciliation</div>
                </div>
            </div>
            <div class="fs-section-body">

                <div class="fs-toggle-row">
                    <div class="fs-toggle-info">
                        <span class="fs-toggle-lbl">Allow CSV Bank Statement Import</span>
                        <span class="fs-toggle-desc">Enables <code>imported_via = csv</code> in <code>fin_bank_transactions</code>.</span>
                    </div>
                    <label class="fs-switch">
                        <input type="checkbox" name="settings[finance_allow_csv_import]" value="1"
                               <?= !empty($existing_data['finance_allow_csv_import']) ? 'checked' : '' ?>>
                        <span class="fs-switch-track"></span>
                    </label>
                </div>

                <div class="fs-toggle-row">
                    <div class="fs-toggle-info">
                        <span class="fs-toggle-lbl">Notify on Unmatched Bank Transactions</span>
                        <span class="fs-toggle-desc">Alert when <code>fin_bank_transactions.status</code> remains <code>unmatched</code> after import.</span>
                    </div>
                    <label class="fs-switch">
                        <input type="checkbox" name="settings[finance_notify_unmatched_transactions]" value="1"
                               <?= !empty($existing_data['finance_notify_unmatched_transactions']) ? 'checked' : '' ?>>
                        <span class="fs-switch-track"></span>
                    </label>
                </div>

                <div class="fs-toggle-row">
                    <div class="fs-toggle-info">
                        <span class="fs-toggle-lbl">Lock Reconciled Transactions</span>
                        <span class="fs-toggle-desc">Prevent edits to transactions where <code>fin_transactions.reconciled = 1</code>.</span>
                    </div>
                    <label class="fs-switch">
                        <input type="checkbox" name="settings[finance_lock_reconciled_transactions]" value="1"
                               <?= !empty($existing_data['finance_lock_reconciled_transactions']) ? 'checked' : '' ?>>
                        <span class="fs-switch-track"></span>
                    </label>
                </div>

                <div class="fs-toggle-row">
                    <div class="fs-toggle-info">
                        <span class="fs-toggle-lbl">Notify on Low Bank Account Balance</span>
                        <span class="fs-toggle-desc">Alert when <code>fin_bank_accounts.current_balance</code> drops below the threshold set below.</span>
                    </div>
                    <label class="fs-switch">
                        <input type="checkbox" name="settings[finance_notify_low_balance]" value="1"
                               <?= !empty($existing_data['finance_notify_low_balance']) ? 'checked' : '' ?>>
                        <span class="fs-switch-track"></span>
                    </label>
                </div>

                <div class="fs-field" style="margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;">
                    <div class="fs-field-lbl">Low Balance Threshold
                        <div class="fs-field-hint">Alert fires when balance drops below this amount</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <input type="number" class="fs-input fs-input-md"
                               step="0.01" min="0"
                               name="settings[finance_low_balance_threshold]"
                               value="<?= e($existing_data['finance_low_balance_threshold'] ?? 500) ?>">
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="fs-section">
            <div class="fs-section-hd">
                <div class="fs-section-icon"><i class="ti ti-receipt-2"></i></div>
                <div>
                    <div class="fs-section-title">Credit &amp; Collections</div>
                </div>
            </div>
            <div class="fs-section-body">

                <div class="fs-field">
                    <div class="fs-field-lbl">Credit Limit Policy
                        <div class="fs-field-hint">How client credit limits are enforced</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <?php $policy = $existing_data['finance_credit_limit_policy'] ?? 'none'; ?>
                        <select class="fs-select fs-input-md" name="settings[finance_credit_limit_policy]">
                            <option value="none"    <?= $policy === 'none'    ? 'selected' : '' ?>>No Credit Limits</option>
                            <option value="fixed"   <?= $policy === 'fixed'   ? 'selected' : '' ?>>Fixed Credit Limit</option>
                            <option value="dynamic" <?= $policy === 'dynamic' ? 'selected' : '' ?>>Dynamic — Based on Payment History</option>
                        </select>
                    </div>
                </div>

                <div class="fs-field">
                    <div class="fs-field-lbl">Default Credit Period
                        <div class="fs-field-hint">Days before a client account is considered overdue</div>
                    </div>
                    <div class="fs-field-ctrl">
                        <div class="fs-num-wrap">
                            <input type="number" class="fs-input fs-input-sm"
                                   min="0" max="365"
                                   name="settings[finance_credit_days]"
                                   value="<?= e($existing_data['finance_credit_days'] ?? 30) ?>">
                            <span class="fs-num-unit">days</span>
                        </div>
                    </div>
                </div>

                <div class="fs-sub">
                    <div class="fs-sub-title"><i class="ti ti-flag"></i> Collection Escalation Stages</div>
                    <p style="font-size:11.5px;color:#94a3b8;margin:0 0 12px;">Days after due date before each escalation action fires.</p>
                    <div class="fs-stages">
                        <div>
                            <div class="fs-stage-lbl">1st Reminder</div>
                            <div class="fs-num-wrap">
                                <input type="number" class="fs-input" style="width:85px;"
                                       name="settings[finance_collection_stage1]" min="0"
                                       value="<?= e($existing_data['finance_collection_stage1'] ?? 7) ?>">
                                <span class="fs-num-unit">days</span>
                            </div>
                        </div>
                        <div>
                            <div class="fs-stage-lbl">2nd Reminder</div>
                            <div class="fs-num-wrap">
                                <input type="number" class="fs-input" style="width:85px;"
                                       name="settings[finance_collection_stage2]" min="0"
                                       value="<?= e($existing_data['finance_collection_stage2'] ?? 14) ?>">
                                <span class="fs-num-unit">days</span>
                            </div>
                        </div>
                        <div>
                            <div class="fs-stage-lbl">Final Notice</div>
                            <div class="fs-num-wrap">
                                <input type="number" class="fs-input" style="width:85px;"
                                       name="settings[finance_collection_stage3]" min="0"
                                       value="<?= e($existing_data['finance_collection_stage3'] ?? 30) ?>">
                                <span class="fs-num-unit">days</span>
                            </div>
                        </div>
                        <div>
                            <div class="fs-stage-lbl">Send to Collections</div>
                            <div class="fs-num-wrap">
                                <input type="number" class="fs-input" style="width:85px;"
                                       name="settings[finance_collection_stage4]" min="0"
                                       value="<?= e($existing_data['finance_collection_stage4'] ?? 45) ?>">
                                <span class="fs-num-unit">days</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="fs-section">
            <div class="fs-section-hd">
                <div class="fs-section-icon"><i class="ti ti-receipt-refund"></i></div>
                <div>
                    <div class="fs-section-title">Credit Notes</div>
                </div>
            </div>
            <div class="fs-section-body">

                <div class="fs-toggle-row">
                    <div class="fs-toggle-info">
                        <span class="fs-toggle-lbl">Enable Credit Notes</span>
                        <span class="fs-toggle-desc">Allow issuing credit notes against invoices via <code>fin_credit_notes</code>.</span>
                    </div>
                    <label class="fs-switch">
                        <input type="checkbox" name="settings[finance_allow_credit_notes]" value="1"
                               <?= !empty($existing_data['finance_allow_credit_notes']) ? 'checked' : '' ?>>
                        <span class="fs-switch-track"></span>
                    </label>
                </div>

                <div class="fs-toggle-row">
                    <div class="fs-toggle-info">
                        <span class="fs-toggle-lbl">Auto-apply Credit Notes to Next Invoice</span>
                        <span class="fs-toggle-desc">Automatically apply an issued credit note to the client's next open invoice balance.</span>
                    </div>
                    <label class="fs-switch">
                        <input type="checkbox" name="settings[finance_auto_apply_credit_notes]" value="1"
                               <?= !empty($existing_data['finance_auto_apply_credit_notes']) ? 'checked' : '' ?>>
                        <span class="fs-switch-track"></span>
                    </label>
                </div>

            </div>
        </div>
    </div>
</div>      
<?= form_close() ?>
</div>

<style>

/* ── Layout ─────────────────────────────────────────────── */
.fs-wrap { width: 100%; }
.fs-clearfix::after { content:''; display:table; clear:both; }

.fs-clearfix {
    display: flex;
    align-items: flex-start;
    gap: 24px;
}

.fs-sidebar {
    width: 260px;
    float: left;
    flex: 0 0 260px;
    position: sticky;
    top: 72px;
    align-self: flex-start;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(13,27,42,.06);
    margin-right: 24px;
}
.fs-main { margin-left: 0px; flex: 1;} /* width + spacing */

@media (max-width: 960px) {
    .fs-clearfix {
        flex-direction: column;
    }

    .fs-sidebar {
        display: none;
    }
}

@media (max-width: 768px) {
    .fs-field {
        flex-direction: column;
        align-items: flex-start;
    }

    .fs-field-lbl {
        width: 100%;
        min-width: 100%;
        margin-bottom: 6px;
    }

    .fs-field-ctrl {
        width: 100%;
    }
}

/* ── Sidebar nav ────────────────────────────────────────── */
.fs-sidebar-hd {
    padding: 12px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #94a3b8;
}
.fs-nav { padding: 6px; }
.fs-nav-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 11px;
    border-radius: 7px;
    font-size: 12.5px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: background .12s, color .12s;
    text-decoration: none;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    margin-bottom: 1px;
    box-sizing: border-box;
}
.fs-nav-item i { font-size: 14px; color: #94a3b8; flex-shrink: 0; }
.fs-nav-item:hover { background: #f1f5f9; color: #0d1b2a; }
.fs-nav-item:hover i { color: #056464; }
.fs-nav-item.active { background: #e6f4f1; color: #056464; font-weight: 600; }
.fs-nav-item.active i { color: #056464; }
.fs-nav-divider { height: 1px; background: #e2e8f0; margin: 5px 0; }

/* ── Section card ────────────────────────────────────────── */
.fs-section {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 18px;
    box-shadow: 0 1px 3px rgba(13,27,42,.05);
    scroll-margin-top: 72px;
}
.fs-section-hd {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.fs-section-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e6f4f1;
    border-radius: 7px;
    color: #056464;
    font-size: 15px;
}
.fs-section-title { font-size: 13px; font-weight: 700; color: #0d1b2a; }
.fs-section-sub { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.fs-section-body { padding: 18px 20px; }

/* ── Field row ───────────────────────────────────────────── */
.fs-field {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    padding: 11px 0;
    border-bottom: 1px solid #f1f5f9;
}
.fs-field:last-child { border-bottom: none; padding-bottom: 0; }
.fs-field:first-child { padding-top: 0; }
.fs-field-lbl {
    width: 240px;
    min-width: 200px;
    max-width: 260px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    line-height: 1.4;
}
.fs-field-hint {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 400;
    margin-top: 2px;
    line-height: 1.4;
}
.fs-field-ctrl { flex: 1; min-width: 0; }
.fs-field-top { align-items: flex-start; }
.fs-field-top .fs-field-lbl { padding-top: 6px; }

/* ── Inputs ──────────────────────────────────────────────── */
.fs-input,
.fs-select {
    padding: 8px 11px;
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    font-size: 13px;
    color: #0d1b2a;
    background: #ffffff;
    font-family: inherit;
    transition: border-color .13s;
    outline: none;
    appearance: auto;
    box-sizing: border-box;
}
.fs-input:focus, .fs-select:focus { border-color: #056464; }
.fs-input-sm  { width: 110px; }
.fs-input-md  { width: 220px; }
.fs-input-w100 { width: 100%; }

.fs-textarea {
    width: 100%;
    padding: 8px 11px;
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    font-size: 13px;
    color: #0d1b2a;
    font-family: inherit;
    resize: vertical;
    outline: none;
    transition: border-color .13s;
    background: #ffffff;
    box-sizing: border-box;
}
.fs-textarea:focus { border-color: #056464; }

/* number + unit */
.fs-num-wrap { display: inline-flex; align-items: stretch; }
.fs-num-wrap .fs-input { border-radius: 7px 0 0 7px; border-right: none; }
.fs-num-unit {
    padding: 0 11px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 0 7px 7px 0;
    font-size: 11.5px;
    font-weight: 600;
    color: #94a3b8;
    white-space: nowrap;
    display: flex;
    align-items: center;
}

/* currency preview badge */
.fs-cur-preview {
    display: inline-block;
    padding: 1px 7px;
    background: #e6f4f1;
    border: 1px solid #c8e8e3;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 600;
    color: #056464;
    margin-left: 5px;
}

/* ── Toggle row ──────────────────────────────────────────── */
.fs-toggle-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.fs-toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
.fs-toggle-row:first-child { padding-top: 0; }
.fs-toggle-info { flex: 1; }
.fs-toggle-lbl { font-size: 13px; font-weight: 600; color: #0d1b2a; display: block; margin-bottom: 2px; }
.fs-toggle-desc { font-size: 11.5px; color: #94a3b8; line-height: 1.5; }
.fs-toggle-desc code {
    font-size: 10.5px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 1px 5px;
    border-radius: 4px;
    color: #056464;
    font-family: monospace;
}

/* custom switch */
.fs-switch { position: relative; display: inline-block; width: 42px; height: 23px; flex-shrink: 0; margin-top: 2px; }
.fs-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
.fs-switch-track {
    position: absolute; inset: 0;
    background: #cbd5e1;
    border-radius: 23px;
    cursor: pointer;
    transition: background .18s;
}
.fs-switch-track::after {
    content: '';
    position: absolute;
    left: 3px; top: 3px;
    width: 17px; height: 17px;
    border-radius: 50%;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,.18);
    transition: transform .18s;
}
.fs-switch input:checked ~ .fs-switch-track { background: #056464; }
.fs-switch input:checked ~ .fs-switch-track::after { transform: translateX(19px); }

/* ── Sub-card ────────────────────────────────────────────── */
.fs-sub {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px 16px;
    margin-top: 14px;
}
.fs-sub-title {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .9px;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.fs-sub-title i { color: #056464; font-size: 12px; }

/* ── Checkbox grid ───────────────────────────────────────── */
.fs-cb-grid { 
    grid-template-columns: 1fr 1fr; 
}

@media (max-width: 768px) {
    .fs-cb-grid {
        grid-template-columns: 1fr;
    }
}
.fs-cb-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    cursor: pointer;
    background: #ffffff;
    transition: border-color .12s, background .12s;
}
.fs-cb-item:hover { border-color: #056464; background: #e6f4f1; }
.fs-cb-item input { accent-color: #056464; width: 14px; height: 14px; flex-shrink: 0; cursor: pointer; }
.fs-cb-item label { font-size: 12.5px; font-weight: 500; color: #475569; cursor: pointer; display: flex; align-items: center; gap: 5px; margin: 0; }
.fs-cb-item label i { font-size: 13px; color: #94a3b8; }

/* ── Radio pills ─────────────────────────────────────────── */
.fs-radio-wrap { display: flex; gap: 7px; flex-wrap: wrap; }
.fs-radio-wrap input[type=radio] { display: none; }
.fs-radio-wrap label {
    padding: 6px 13px;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all .12s;
    background: #ffffff;
    margin: 0;
}
.fs-radio-wrap input[type=radio]:checked + label {
    border-color: #056464;
    background: #e6f4f1;
    color: #056464;
    font-weight: 600;
}

/* ── Collection stages grid ──────────────────────────────── */
.fs-stages { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 4px; }
.fs-stage-lbl { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; margin-bottom: 5px; }

</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Currency preview
    function bindCurrencyPreview(selectId, previewId, formatter) {
        var sel  = document.getElementById(selectId);
        var prev = document.getElementById(previewId);
        if (!sel || !prev) return;
        sel.addEventListener('change', function () {
            try {
                prev.textContent = typeof formatter === 'function'
                    ? formatter(1234.56, this.value)
                    : this.value + ' 1,234.56';
            } catch(e) {
                prev.textContent = this.value + ' 1,234.56';
            }
        });
    }

    bindCurrencyPreview('financeBaseCurrencyInvoice', 'financeCurrencyPreviewInvoice', window.invc_format || null);
    bindCurrencyPreview('financeBaseCurrencyExpense', 'financeCurrencyPreviewExpense', window.expc_format || null);

    // Active nav on scroll
    var sections = document.querySelectorAll('.fs-section[id]');
    var navItems = document.querySelectorAll('.fs-nav-item');

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                navItems.forEach(function(n) { n.classList.remove('active'); });
                var active = document.querySelector('.fs-nav-item[href="#' + entry.target.id + '"]');
                if (active) active.classList.add('active');
            }
        });
    }, { rootMargin: '-10% 0px -80% 0px' });

    sections.forEach(function(s) { observer.observe(s); });
});

function fsSroll(e, id, el) {
    e.preventDefault();
    document.querySelectorAll('.fs-nav-item').forEach(function(n) { n.classList.remove('active'); });
    el.classList.add('active');
    var t = document.getElementById(id);
    if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>