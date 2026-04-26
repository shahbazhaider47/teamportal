<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ═══════════════════════════════════════════════════════
   CONTRACT CREATE — matches proposal pf-section style
   Uses existing crm-form-card classes + new pf-* where needed
   ═══════════════════════════════════════════════════════ */

/* Page header — match pf-page-header */
.crm-page-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 11px 16px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.crm-page-icon {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    background: #f0fdfa;
    color: #056464;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}
.crm-page-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}
.crm-page-sub {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 1px;
}

/* Form card — match pf-section */
.crm-form-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    overflow: hidden;
}

/* Card header — match pf-section-head */
.crm-form-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: #64748b;
}
.crm-form-card-header i {
    font-size: 12px;
    color: #056464;
}
.crm-form-card-header span {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Step number badge — matches pf-step-num */
.crm-step-num {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #056464;
    color: #fff;
    font-size: 9.5px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Card body padding */
.crm-form-card-body {
    padding: 16px;
}

/* Actions card (right sidebar) */
.crm-form-actions-card .crm-form-card-body {
    padding: 14px;
}

/* Label */
.crm-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 4px;
}
.crm-req {
    color: #dc2626;
}

/* Input — match pf-control */
.crm-input {
    width: 100%;
    padding: 6px 10px;
    font-size: 13px;
    color: #0f172a;
    background: #fff;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    appearance: none;
    line-height: 1.5;
}
.crm-input:focus {
    border-color: #056464;
    box-shadow: 0 0 0 3px rgba(5,100,100,.09);
}
.crm-input[readonly],
.crm-input-readonly {
    background: #f8fafc;
    color: #64748b;
    cursor: not-allowed;
}
textarea.crm-input {
    resize: vertical;
}
select.crm-input {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    padding-right: 26px;
    cursor: pointer;
}

/* Icon-wrapped input */
.crm-input-icon-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.crm-input-icon {
    position: absolute;
    left: 9px;
    font-size: 13px;
    color: #94a3b8;
    pointer-events: none;
    z-index: 1;
}
.crm-has-icon {
    padding-left: 28px;
}

/* Footer note */
.crm-form-note {
    font-size: 11.5px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 0 0;
    border-top: 1px solid #e2e8f0;
    margin-top: 4px;
}

/* Action card footer area */
.crm-form-actions-card {
    position: sticky;
    top: 16px;
}
</style>

<div class="container-fluid">

    <!-- ── Page Header ── -->
    <div class="crm-page-header mb-3">
        <div class="crm-page-icon me-3"><i class="ti ti-file-plus"></i></div>
        <div class="flex-grow-1">
            <div class="crm-page-title"><?= $page_title ?></div>
            <div class="crm-page-sub">Complete all required fields to register a new contract</div>
        </div>
        <div class="ms-auto">
            <a href="<?= site_url('crm/contracts') ?>" class="btn btn-light-secondary btn-header">
                <i class="ti ti-arrow-left me-1"></i> Back to Contracts
            </a>
        </div>
    </div>

    <?= validation_errors('<div class="alert alert-danger py-2 small">', '</div>') ?>

    <form method="post" action="<?= site_url('crm/contracts/create') ?>" id="contractCreateForm" class="app-form">

        <div class="row g-3">

            <!-- ════ LEFT COLUMN ════ -->
            <div class="col-md-7">

                <!-- 01. Identity -->
                <div class="crm-form-card mb-3">
                    <div class="crm-form-card-header">
                        <div class="crm-step-num">1</div>
                        <span><i class="ti ti-settings-2"></i>Contract Details</span>
                    </div>
                    <div class="crm-form-card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="crm-label">Contract Number <span class="small text-light">Auto-generated</span></label>
                                <input type="text" name="contract_code"
                                       class="crm-input crm-input-readonly"
                                       value="<?= html_escape($contract_code) ?>" readonly>
                            </div>
                            <div class="col-md-5">
                                <label class="crm-label">Client <span class="crm-req">*</span></label>
                                <select name="client_id" class="crm-input" required>
                                    <option value="">— Select Client —</option>
                                    <?php foreach ($clients as $cl): ?>
                                        <option value="<?= (int)$cl['id'] ?>"
                                            <?= ((int)set_value('client_id', $pre_client_id ?? '') === (int)$cl['id']) ? 'selected' : '' ?>>
                                            <?= html_escape($cl['practice_name']) ?>
                                            (<?= html_escape($cl['client_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="crm-label">Parent Contract ID</label>
                                <input type="number" name="parent_contract_id" class="crm-input"
                                       value="<?= html_escape(set_value('parent_contract_id')) ?>"
                                       placeholder="Leave blank for new originals">
                            </div>
                            
                            <div class="col-md-5">
                                <label class="crm-label">Contract Title <span class="crm-req">*</span></label>
                                <input type="text" name="contract_title" class="crm-input"
                                       value="<?= html_escape(set_value('contract_title')) ?>"
                                       placeholder="e.g. RCM Billing Services Agreement — Sunrise Medical"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label class="crm-label">Contract Type <span class="crm-req">*</span></label>
                                <select name="contract_type" class="crm-input" required>
                                    <option value="service_agreement"  <?= set_value('contract_type') === 'service_agreement'  ? 'selected' : '' ?>>Service Agreement</option>
                                    <option value="nda" <?= set_value('contract_type') === 'nda' ? 'selected' : '' ?>>NDA</option>
                                    <option value="billing_agreement"   <?= set_value('contract_type') === 'billing_agreement'   ? 'selected' : '' ?>>Billing Agreement</option>
                                    <option value="amendment"  <?= set_value('contract_type') === 'amendment'  ? 'selected' : '' ?>>Amendment</option>
                                    <option value="renewal"  <?= set_value('contract_type') === 'renewal'  ? 'selected' : '' ?>>Renewal</option>
                                    <option value="other"  <?= set_value('contract_type') === 'other'  ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="crm-label">Status <span class="crm-req">*</span></label>
                                <select name="status" class="crm-input" required>
                                    <option value="draft"             <?= set_value('status', 'draft') === 'draft'    ? 'selected' : '' ?>>Draft</option>
                                    <option value="sent" <?= set_value('status') === 'sent' ? 'selected' : '' ?>>Sent</option>
                                    <option value="signed"            <?= set_value('status') === 'signed'            ? 'selected' : '' ?>>signed</option>
                                    <option value="active"            <?= set_value('status') === 'active'            ? 'selected' : '' ?>>Active</option>
                                    <option value="expired"            <?= set_value('status') === 'expired'            ? 'selected' : '' ?>>Expired</option>
                                    <option value="terminated"            <?= set_value('status') === 'terminated'            ? 'selected' : '' ?>>Terminated</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="crm-label">Description / Scope of Work</label>
                                <textarea name="description" class="crm-input" rows="3"
                                          placeholder="Brief summary of services covered..."><?= html_escape(set_value('description')) ?></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="crm-label">Contract Document / Attachement</label>
                                <div class="lf-dropzone">
                                    <div class="lf-dropzone-icon">
                                        <i class="ti ti-cloud-upload" style="display:flex;align-items:center;justify-content:center;font-size:20px;"></i>
                                    </div>
                                    <div style="flex:1;">
                                        <div class="lf-dropzone-title">Drag &amp; drop files here</div>
                                        <div class="lf-dropzone-sub">PDF, DOCX, XLSX, PNG, JPG, TXT, ZIP — complete file details in the popup</div>
                                    </div>
                                    <button type="button" class="lf-dropzone-btn">Browse Files</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- 04. Scope -->
                <div class="crm-form-card mb-3">
                    <div class="crm-form-card-header">
                        <div class="crm-step-num">2</div>
                        <span><i class="ti ti-briefcase"></i>Services &amp; Scope</span>
                    </div>
                    <div class="crm-form-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="crm-label">Services Included</label>
                                <textarea name="services_included" class="crm-input" rows="3"
                                          placeholder="e.g. Billing, coding, AR follow-up..."><?= html_escape(set_value('services_included')) ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="crm-label">SLA Terms</label>
                                <input type="text" name="sla_terms" class="crm-input"
                                       value="<?= html_escape(set_value('sla_terms')) ?>"
                                       placeholder="e.g. Claims submitted within 48hrs">
                            </div>

                            <div class="col-md-12">
                                <label class="crm-label">Internal Notes</label>
                                <textarea name="internal_notes" class="crm-input" rows="3"
                                          placeholder="Private notes — not visible to the client..."><?= html_escape(set_value('internal_notes')) ?></textarea>
                            </div>
                            
                        </div>
                    </div>
                </div>
            
            </div><!-- /col-xl-8 -->

            <!-- ════ RIGHT COLUMN ════ -->
            <div class="col-md-5">

                <!-- 02. Dates & Renewal -->
                <div class="crm-form-card mb-3">
                    <div class="crm-form-card-header">
                        <div class="crm-step-num">4</div>
                        <span><i class="ti ti-calendar"></i>Billing &amp; Renewal</span>
                    </div>
                    <div class="crm-form-card-body">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="crm-label">Billing Model <span class="crm-req">*</span></label>
                                <select name="billing_model" id="billing_model" class="crm-input" required>
                                    <option value="percentage" <?= set_value('billing_model', 'percentage') === 'percentage' ? 'selected' : '' ?>>Monthly Percentage</option>
                                    <option value="flat_fee"   <?= set_value('billing_model') === 'flat_fee'                 ? 'selected' : '' ?>>Monthly Flat Fee</option>
                                    <option value="custom"     <?= set_value('billing_model') === 'custom'                   ? 'selected' : '' ?>>Custom</option>
                                </select>
                            </div>
                                <!-- rate_percent — shown when billing_model = percentage -->
                                <div class="col-md-4" id="wrap-rate-percent">
                                    <label class="crm-label">Rate (%) <span class="crm-req">*</span></label>
                                    <div class="crm-input-icon-wrap">
                                        <i class="ti ti-percentage crm-input-icon"></i>
                                        <input type="number" step="0.01" name="rate_percent" id="rate_percent"
                                               class="crm-input crm-has-icon"
                                               value="<?= html_escape(set_value('rate_percent')) ?>"
                                               placeholder="e.g. 7.50">
                                    </div>
                                </div>
                                
                                <!-- rate_flat — shown when billing_model = flat_fee -->
                                <div class="col-md-4" id="wrap-rate-flat" style="display:none;">
                                    <label class="crm-label">Flat Fee ($) <span class="crm-req">*</span></label>
                                    <div class="crm-input-icon-wrap">
                                        <i class="ti ti-currency-dollar crm-input-icon"></i>
                                        <input type="number" step="0.01" name="rate_flat" id="rate_flat"
                                               class="crm-input crm-has-icon"
                                               value="<?= html_escape(set_value('rate_flat')) ?>"
                                               placeholder="0.00">
                                    </div>
                                </div>
                                
                                <!-- custom_rate — shown when billing_model = custom -->
                                <div class="col-md-4" id="wrap-custom-rate" style="display:none;">
                                    <label class="crm-label">Custom Rate <span class="crm-req">*</span></label>
                                    <input type="text" name="custom_rate" id="custom_rate"
                                           class="crm-input"
                                           value="<?= html_escape(set_value('custom_rate')) ?>"
                                           placeholder="e.g. $500 + 5% over $10k">
                                </div>

                            <div class="col-md-4">
                                <label class="crm-label">Invoice Frequency <span class="crm-req">*</span></label>
                                <select name="invoice_frequency" class="crm-input" required>
                                    <option value="monthly"   <?= set_value('invoice_frequency', 'monthly') === 'monthly'   ? 'selected' : '' ?>>Monthly</option>
                                    <option value="weekly"    <?= set_value('invoice_frequency') === 'weekly'                ? 'selected' : '' ?>>Weekly</option>
                                    <option value="bi-weekly" <?= set_value('invoice_frequency') === 'bi-weekly'             ? 'selected' : '' ?>>Bi-Weekly</option>
                                    <option value="quarterly" <?= set_value('invoice_frequency') === 'quarterly'             ? 'selected' : '' ?>>Quarterly</option>
                                    <option value="annual"    <?= set_value('invoice_frequency') === 'annual'                ? 'selected' : '' ?>>Annual</option>
                                    <option value="custom"    <?= set_value('invoice_frequency') === 'custom'                ? 'selected' : '' ?>>Custom</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="crm-label">Payment Terms</label>
                                <input type="number" name="payment_terms_days" class="crm-input"
                                       value="<?= html_escape(set_value('payment_terms_days', '30')) ?>"
                                       placeholder="30">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="crm-label">Start Date <span class="crm-req">*</span></label>
                                <input type="date" name="start_date" class="crm-input"
                                       value="<?= html_escape(set_value('start_date')) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="crm-label">End Date</label>
                                <input type="date" name="end_date" class="crm-input"
                                       value="<?= html_escape(set_value('end_date')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="crm-label">Signed Date</label>
                                <input type="date" name="signed_date" class="crm-input"
                                       value="<?= html_escape(set_value('signed_date')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="crm-label">Next Review Date</label>
                                <input type="date" name="next_review_date" class="crm-input"
                                       value="<?= html_escape(set_value('next_review_date')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="crm-label">Auto Renew</label>
                                <select name="auto_renew" class="crm-input" id="auto_renew">
                                    <option value="0" <?= set_value('auto_renew', '0') === '0' ? 'selected' : '' ?>>No</option>
                                    <option value="1" <?= set_value('auto_renew') === '1'       ? 'selected' : '' ?>>Yes</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="renewal-term-wrap">
                                <label class="crm-label">Renewal Term (months)</label>
                                <input type="number" name="renewal_period" class="crm-input"
                                       value="<?= html_escape(set_value('renewal_period', '12')) ?>"
                                       min="1" placeholder="12">
                            </div>
                            <div class="col-md-4" id="renewal-notice-wrap">
                                <label class="crm-label">Renewal Notice (days)</label>
                                <input type="number" name="notice_period_days" class="crm-input"
                                       value="<?= html_escape(set_value('notice_period_days', '30')) ?>"
                                       min="1" placeholder="30">
                            </div>
                            <div class="col-md-4">
                                <label class="crm-label">Termination Notice (days)</label>
                                <input type="number" name="termination_notice_days" class="crm-input"
                                       value="<?= html_escape(set_value('termination_notice_days', '30')) ?>"
                                       min="1" placeholder="30">
                            </div>
                        </div>
                        
                    </div>
                </div>

                <!-- 05. Signing Details -->
                <div class="crm-form-card mb-3">
                    <div class="crm-form-card-header">
                        <div class="crm-step-num">3</div>
                        <span><i class="ti ti-signature"></i>Signing Details</span>
                    </div>
                    <div class="crm-form-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="crm-label">Signed By (Client)</label>
                                <input type="text" name="signed_by_client" class="crm-input"
                                       value="<?= html_escape(set_value('signed_by_client')) ?>"
                                       placeholder="Client signatory full name">
                            </div>
                            <div class="col-md-4">
                                <label class="crm-label">Signed By (RCM)</label>
                                <input type="text" name="signed_by_rcm" class="crm-input"
                                       value="<?= html_escape(set_value('signed_by_rcm')) ?>"
                                       placeholder="RCM company signatory">
                            </div>
                            <div class="col-md-4">
                                <label class="crm-label">Signature Method</label>
                                <select name="signature_method" class="crm-input">
                                    <option value="">— None —</option>
                                    <option value="wet"       <?= set_value('signature_method') === 'wet'       ? 'selected' : '' ?>>Wet Signature</option>
                                    <option value="docusign"  <?= set_value('signature_method') === 'docusign'  ? 'selected' : '' ?>>DocuSign</option>
                                    <option value="hellosign" <?= set_value('signature_method') === 'hellosign' ? 'selected' : '' ?>>HelloSign</option>
                                    <option value="other"     <?= set_value('signature_method') === 'other'     ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="app-divider-v dashed mt-4"></div>
                            <div class="d-flex gap-3 mt-4">
                                <a href="<?= site_url('crm/contracts') ?>" class="btn btn-light-primary btn-header">
                                    <i class="ti ti-x me-1"></i> Cancel
                                </a>
    
                                <button type="submit" class="btn btn-primary btn-header">
                                    <i class="ti ti-device-floppy me-1"></i> Save Contract
                                </button>
                                
                            </div>
                        
                        </div>
                    </div>
                </div>
                
            </div>

        </div>
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Billing model — show/hide rate fields ── */
    const billingEl = document.getElementById('billing_model');

    function updateBillingLabel() {
        const m           = billingEl.value;
        const wrapPercent = document.getElementById('wrap-rate-percent');
        const wrapFlat    = document.getElementById('wrap-rate-flat');
        const wrapCustom  = document.getElementById('wrap-custom-rate');

        wrapPercent.style.display = 'none';
        wrapFlat.style.display    = 'none';
        wrapCustom.style.display  = 'none';

        document.getElementById('rate_percent').removeAttribute('required');
        document.getElementById('rate_flat').removeAttribute('required');
        document.getElementById('custom_rate').removeAttribute('required');

        if (m === 'percentage') {
            wrapPercent.style.display = '';
            document.getElementById('rate_percent').setAttribute('required', 'required');
        } else if (m === 'flat_fee') {
            wrapFlat.style.display = '';
            document.getElementById('rate_flat').setAttribute('required', 'required');
        } else if (m === 'custom') {
            wrapCustom.style.display = '';
            document.getElementById('custom_rate').setAttribute('required', 'required');
        }
    }

    billingEl.addEventListener('change', updateBillingLabel);
    updateBillingLabel(); // run on page load to reflect default/saved value

    /* ── Auto-renew fields toggle ── */
    const autoRenewEl     = document.getElementById('auto_renew');
    const renewTermWrap   = document.getElementById('renewal-term-wrap');
    const renewNoticeWrap = document.getElementById('renewal-notice-wrap');

    function toggleAutoRenew() {
        const show = autoRenewEl.value === '1';
        renewTermWrap.style.opacity   = show ? '1' : '.45';
        renewNoticeWrap.style.opacity = show ? '1' : '.45';
    }

    autoRenewEl.addEventListener('change', toggleAutoRenew);
    toggleAutoRenew();

});
</script>