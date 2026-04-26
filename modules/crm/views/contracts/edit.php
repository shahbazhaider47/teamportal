<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <div class="crm-page-header mb-3">
    <div class="crm-page-icon me-3"><i class="ti ti-edit"></i></div>
    <div class="flex-grow-1">
      <div class="crm-page-title"><?= $page_title ?></div>
      <div class="crm-page-sub">
        <?= html_escape($contract['contract_number']) ?> &mdash;
        <?= html_escape($contract['client_name'] ?? '') ?>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2">
      <a href="<?= site_url('crm/contracts/view/' . $contract['id']) ?>" class="btn btn-light-secondary btn-header">
        <i class="ti ti-eye me-1"></i> View
      </a>
      <a href="<?= site_url('crm/contracts') ?>" class="btn btn-light-secondary btn-header">
        <i class="ti ti-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>

  <?= validation_errors('<div class="alert alert-danger py-2 small">', '</div>') ?>

  <form method="post" action="<?= site_url('crm/contracts/edit/' . $contract['id']) ?>">

    <div class="row g-3">

      <!-- ════ LEFT ════ -->
      <div class="col-xl-8 col-lg-7">

        <!-- 01. Identity (read-only fields locked) -->
        <div class="crm-form-card mb-3">
          <div class="crm-form-card-header">
            <i class="ti ti-settings-2"></i><span>Contract Identity</span>
          </div>
          <div class="crm-form-card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="crm-label">Contract Number</label>
                <input type="text" class="form-control crm-input crm-input-readonly"
                       value="<?= html_escape($contract['contract_number']) ?>" readonly>
              </div>
              <div class="col-md-4">
                <label class="crm-label">Client</label>
                <input type="text" class="form-control crm-input crm-input-readonly"
                       value="<?= html_escape($contract['client_name'] ?? '') ?>" readonly>
                <div class="crm-hint">Client cannot be changed after creation</div>
              </div>
              <div class="col-md-3">
                <label class="crm-label">Contract Type</label>
                <input type="text" class="form-control crm-input crm-input-readonly"
                       value="<?= ucfirst(html_escape($contract['contract_type'])) ?>" readonly>
              </div>
              <div class="col-md-2">
                <label class="crm-label">Version</label>
                <input type="text" class="form-control crm-input crm-input-readonly"
                       value="v<?= (int)$contract['contract_version'] ?>" readonly>
              </div>
              <div class="col-md-8">
                <label class="crm-label">Contract Title <span class="crm-req">*</span></label>
                <input type="text" name="title" class="form-control crm-input"
                       value="<?= html_escape(set_value('title', $contract['title'])) ?>"
                       required>
              </div>
              <div class="col-md-4">
                <label class="crm-label">Status <span class="crm-req">*</span></label>
                <select name="status" class="form-select crm-input" required>
                  <?php
                    $statuses = ['draft' => 'Draft', 'pending_signature' => 'Pending Signature',
                                 'active' => 'Active', 'expired' => 'Expired',
                                 'terminated' => 'Terminated', 'cancelled' => 'Cancelled'];
                    $curStatus = set_value('status', $contract['status']);
                    foreach ($statuses as $val => $lbl):
                  ?>
                    <option value="<?= $val ?>" <?= $curStatus === $val ? 'selected' : '' ?>>
                      <?= $lbl ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-12">
                <label class="crm-label">Description / Scope of Work</label>
                <textarea name="description" class="form-control crm-input" rows="3"><?= html_escape(set_value('description', $contract['description'])) ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- 02. Dates & Renewal -->
        <div class="crm-form-card mb-3">
          <div class="crm-form-card-header">
            <i class="ti ti-calendar"></i><span>Dates &amp; Renewal</span>
          </div>
          <div class="crm-form-card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="crm-label">Start Date <span class="crm-req">*</span></label>
                <input type="date" name="start_date" class="form-control crm-input"
                       value="<?= html_escape(set_value('start_date', $contract['start_date'])) ?>" required>
              </div>
              <div class="col-md-3">
                <label class="crm-label">End Date</label>
                <input type="date" name="end_date" class="form-control crm-input"
                       value="<?= html_escape(set_value('end_date', $contract['end_date'])) ?>">
              </div>
              <div class="col-md-3">
                <label class="crm-label">Signed Date</label>
                <input type="date" name="signed_date" class="form-control crm-input"
                       value="<?= html_escape(set_value('signed_date', $contract['signed_date'])) ?>">
              </div>
              <div class="col-md-3">
                <label class="crm-label">Next Review Date</label>
                <input type="date" name="next_review_date" class="form-control crm-input"
                       value="<?= html_escape(set_value('next_review_date', $contract['next_review_date'])) ?>">
              </div>
              <div class="col-md-2">
                <label class="crm-label">Auto Renew</label>
                <select name="auto_renew" class="form-select crm-input">
                  <option value="0" <?= (int)set_value('auto_renew', $contract['auto_renew']) === 0 ? 'selected' : '' ?>>No</option>
                  <option value="1" <?= (int)set_value('auto_renew', $contract['auto_renew']) === 1 ? 'selected' : '' ?>>Yes</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="crm-label">Renewal Term (months)</label>
                <input type="number" name="renewal_term_months" class="form-control crm-input"
                       value="<?= html_escape(set_value('renewal_term_months', $contract['renewal_term_months'] ?? 12)) ?>"
                       min="1">
              </div>
              <div class="col-md-2">
                <label class="crm-label">Renewal Notice (days)</label>
                <input type="number" name="renewal_notice_days" class="form-control crm-input"
                       value="<?= html_escape(set_value('renewal_notice_days', $contract['renewal_notice_days'] ?? 30)) ?>"
                       min="1">
              </div>
              <div class="col-md-3">
                <label class="crm-label">Termination Notice (days)</label>
                <input type="number" name="termination_notice_days" class="form-control crm-input"
                       value="<?= html_escape(set_value('termination_notice_days', $contract['termination_notice_days'] ?? 30)) ?>"
                       min="1">
              </div>
            </div>
          </div>
        </div>

        <!-- 03. Billing Snapshot -->
        <div class="crm-form-card mb-3">
          <div class="crm-form-card-header">
            <i class="ti ti-receipt-2"></i><span>Billing Snapshot</span>
          </div>
          <div class="crm-form-card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="crm-label">Billing Model <span class="crm-req">*</span></label>
                <select name="billing_model" id="billing_model" class="form-select crm-input" required>
                  <?php
                    $bm = set_value('billing_model', $contract['billing_model']);
                    foreach (['percentage' => 'Monthly Percentage', 'flat' => 'Monthly Flat Fee', 'custom' => 'Custom'] as $v => $l):
                  ?>
                    <option value="<?= $v ?>" <?= $bm === $v ? 'selected' : '' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="crm-label" id="rate-label">Rate Value <span class="crm-req">*</span></label>
                <div class="crm-input-icon-wrap">
                  <i class="ti crm-input-icon" id="rate-icon"></i>
                  <input type="number" step="0.0001" name="rate_value" id="rate_value"
                         class="form-control crm-input crm-has-icon"
                         value="<?= html_escape(set_value('rate_value', $contract['rate_value'])) ?>">
                </div>
              </div>
              <div class="col-md-2">
                <label class="crm-label">Currency</label>
                <select name="rate_currency" class="form-select crm-input">
                  <?php foreach (['USD', 'EUR', 'GBP'] as $cur): ?>
                    <option value="<?= $cur ?>"
                      <?= set_value('rate_currency', $contract['rate_currency'] ?? 'USD') === $cur ? 'selected' : '' ?>>
                      <?= $cur ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <label class="crm-label">Invoice Frequency <span class="crm-req">*</span></label>
                <select name="invoice_frequency" class="form-select crm-input" required>
                  <?php
                    $freqs = ['monthly' => 'Monthly', 'weekly' => 'Weekly', 'bi-weekly' => 'Bi-Weekly',
                              'quarterly' => 'Quarterly', 'annual' => 'Annual', 'custom' => 'Custom'];
                    $curFreq = set_value('invoice_frequency', $contract['invoice_frequency']);
                    foreach ($freqs as $v => $l):
                  ?>
                    <option value="<?= $v ?>" <?= $curFreq === $v ? 'selected' : '' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <label class="crm-label">Payment Terms (days)</label>
                <input type="number" name="payment_terms_days" class="form-control crm-input"
                       value="<?= html_escape(set_value('payment_terms_days', $contract['payment_terms_days'] ?? 30)) ?>">
              </div>
              <div class="col-md-3">
                <label class="crm-label">Minimum Monthly Fee</label>
                <div class="crm-input-icon-wrap">
                  <i class="ti ti-currency-dollar crm-input-icon"></i>
                  <input type="number" step="0.01" name="minimum_monthly_fee"
                         class="form-control crm-input crm-has-icon"
                         value="<?= html_escape(set_value('minimum_monthly_fee', $contract['minimum_monthly_fee'])) ?>">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 04. Scope -->
        <div class="crm-form-card mb-3">
          <div class="crm-form-card-header">
            <i class="ti ti-briefcase"></i><span>Services &amp; Scope</span>
          </div>
          <div class="crm-form-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="crm-label">Services Included</label>
                <textarea name="services_included" class="form-control crm-input" rows="3"><?= html_escape(set_value('services_included', $contract['services_included'])) ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="crm-label">Services Excluded</label>
                <textarea name="services_excluded" class="form-control crm-input" rows="3"><?= html_escape(set_value('services_excluded', $contract['services_excluded'])) ?></textarea>
              </div>
              <div class="col-md-4">
                <label class="crm-label">Specialties Covered</label>
                <input type="text" name="specialties_covered" class="form-control crm-input"
                       value="<?= html_escape(set_value('specialties_covered', $contract['specialties_covered'])) ?>">
              </div>
              <div class="col-md-4">
                <label class="crm-label">Locations Covered</label>
                <input type="text" name="locations_covered" class="form-control crm-input"
                       value="<?= html_escape(set_value('locations_covered', $contract['locations_covered'])) ?>">
              </div>
              <div class="col-md-4">
                <label class="crm-label">SLA Terms</label>
                <input type="text" name="sla_terms" class="form-control crm-input"
                       value="<?= html_escape(set_value('sla_terms', $contract['sla_terms'])) ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- 05. Signing -->
        <div class="crm-form-card mb-3">
          <div class="crm-form-card-header">
            <i class="ti ti-signature"></i><span>Signing Details</span>
          </div>
          <div class="crm-form-card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="crm-label">Signed By (Client)</label>
                <input type="text" name="signed_by_client" class="form-control crm-input"
                       value="<?= html_escape(set_value('signed_by_client', $contract['signed_by_client'])) ?>">
              </div>
              <div class="col-md-4">
                <label class="crm-label">Signed By (RCM)</label>
                <input type="text" name="signed_by_rcm" class="form-control crm-input"
                       value="<?= html_escape(set_value('signed_by_rcm', $contract['signed_by_rcm'])) ?>">
              </div>
              <div class="col-md-2">
                <label class="crm-label">Signature Method</label>
                <select name="signature_method" class="form-select crm-input">
                  <option value="">— None —</option>
                  <?php
                    $sigMethods = ['wet' => 'Wet', 'docusign' => 'DocuSign', 'hellosign' => 'HelloSign', 'other' => 'Other'];
                    $curSig = set_value('signature_method', $contract['signature_method']);
                    foreach ($sigMethods as $v => $l):
                  ?>
                    <option value="<?= $v ?>" <?= $curSig === $v ? 'selected' : '' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <label class="crm-label">External Ref</label>
                <input type="text" name="external_ref" class="form-control crm-input"
                       value="<?= html_escape(set_value('external_ref', $contract['external_ref'])) ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- 06. Internal Notes -->
        <div class="crm-form-card mb-3">
          <div class="crm-form-card-header"><i class="ti ti-notes"></i><span>Internal Notes</span></div>
          <div class="crm-form-card-body">
            <textarea name="internal_notes" class="form-control crm-input" rows="4"><?= html_escape(set_value('internal_notes', $contract['internal_notes'])) ?></textarea>
          </div>
        </div>

      </div><!-- /col -->

      <!-- ════ RIGHT ════ -->
      <div class="col-xl-4 col-lg-5">
        <div class="crm-form-card crm-form-actions-card">
          <div class="crm-form-card-body">
            <button type="submit" class="btn btn-primary w-100 mb-2">
              <i class="ti ti-device-floppy me-1"></i> Update Contract
            </button>
            <a href="<?= site_url('crm/contracts/view/' . $contract['id']) ?>"
               class="btn btn-light-secondary w-100 mb-2">
              <i class="ti ti-eye me-1"></i> View Contract
            </a>
            <a href="<?= site_url('crm/contracts') ?>" class="btn btn-light-secondary w-100">
              <i class="ti ti-x me-1"></i> Cancel
            </a>
            <!-- Contract meta -->
            <div class="mt-3 p-3 rounded" style="background:#f8fafc;border:1px solid #e5e7eb;font-size:11.5px;color:#64748b">
              <div class="d-flex justify-content-between mb-1">
                <span>Created</span>
                <span class="fw-semibold text-dark"><?= date('M j, Y', strtotime($contract['created_at'])) ?></span>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span>Last updated</span>
                <span class="fw-semibold text-dark"><?= date('M j, Y g:ia', strtotime($contract['updated_at'])) ?></span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Renewal count</span>
                <span class="fw-semibold text-dark"><?= (int)($contract['renewal_count'] ?? 0) ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /row -->
  </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const billingEl = document.getElementById('billing_model');
  const rateLabel = document.getElementById('rate-label');
  const rateIcon  = document.getElementById('rate-icon');

  function updateBillingLabel() {
    const m = billingEl.value;
    if (m === 'percentage') {
      rateLabel.innerHTML = 'Rate Value (%) <span class="crm-req">*</span>';
      rateIcon.className  = 'ti ti-percentage crm-input-icon';
    } else {
      rateLabel.innerHTML = 'Rate Value ($) <span class="crm-req">*</span>';
      rateIcon.className  = 'ti ti-currency-dollar crm-input-icon';
    }
  }
  billingEl.addEventListener('change', updateBillingLabel);
  updateBillingLabel();
});
</script>