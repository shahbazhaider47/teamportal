<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <!-- ── Page Header ── -->
  <div class="crm-page-header mb-3">
    <div class="crm-page-icon me-3">
      <i class="ti ti-user-plus"></i>
    </div>
    <div class="flex-grow-1">
      <div class="crm-page-title"><?= $page_title ?></div>
      <div class="crm-page-sub">Fill in all required fields to register a new client in the system</div>
    </div>
    <div class="ms-auto">
      <a href="<?= site_url('crm/clients'); ?>" class="btn btn-light-primary btn-header">
        <i class="ti ti-arrow-left me-1"></i> Back to Clients
      </a>
    </div>
  </div>

  <form method="post" class="app-form" id="addClientForm">
  <div class="row g-3">
      
    <div class="col-xl-8 col-lg-7">

      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header">
          <i class="ti ti-settings-2"></i>
          <span>System Configuration</span>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">

            <?php $autoClientCode = crm_generate_client_code(); ?>

            <div class="col-md-3">
              <label class="crm-label">Client Code <span class="crm-req">*</span></label>
              <input type="text"
                     name="client_code"
                     class="form-control crm-input crm-input-readonly"
                     value="<?= html_escape($autoClientCode); ?>"
                     readonly>
              <div class="crm-hint">Auto-generated system code</div>
            </div>

            <div class="col-md-3">
              <label class="crm-label">Client Type <span class="crm-req">*</span></label>
              <select name="is_group" id="is_group" class="form-select crm-input" required>
                <option value="0" selected>Direct Client</option>
                <option value="1">Group / Third-Party</option>
              </select>
            </div>

            <div class="col-md-6 d-none" id="client-group-wrapper">
              <label class="crm-label">Group / Third-Party <span class="crm-req">*</span></label>
              <select name="client_group_id" id="client_group_id" class="form-select crm-input">
                <option value="">-- Select Partner Company --</option>
                <?php if (!empty($client_groups)): ?>
                  <?php foreach ($client_groups as $group): ?>
                    <option value="<?= (int)$group['id']; ?>">
                      <?= html_escape($group['group_name']); ?>
                      (<?= html_escape($group['company_name']); ?>)
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

          </div>
        </div>
      </div>

    <div class="row g-3">
    <div class="col-md-8">
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header">
          <i class="ti ti-building-hospital"></i>
          <span>Practice Information</span>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="crm-label">Practice Name <span class="crm-req">*</span></label>
              <input type="text" name="practice_name" class="form-control crm-input" placeholder="e.g. Sunrise Medical Group" required>
            </div>

            <div class="col-md-6">
              <label class="crm-label">Legal Business Name <span class="crm-req">*</span></label>
              <input type="text" name="practice_legal_name" class="form-control crm-input" placeholder="Registered legal entity name" required>
            </div>

            <div class="col-md-4">
              <label class="crm-label">Practice Type <span class="crm-req">*</span></label>
              <select name="practice_type" class="form-select crm-input" required>
                <?php
                  $selected = set_value('practice_type');
                  foreach (practice_types_dropdown() as $value => $label):
                ?>
                  <option value="<?= html_escape($value); ?>" <?= ($selected === $value) ? 'selected' : ''; ?>>
                    <?= html_escape($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="crm-label">Specialty <span class="crm-req">*</span></label>
              <input type="text" name="specialty" class="form-control crm-input" placeholder="e.g. Cardiology" required>
            </div>

            <div class="col-md-4">
              <label class="crm-label">Tax ID</label>
              <input type="text" name="tax_id" class="form-control crm-input" placeholder="XX-XXXXXXX">
            </div>

            <div class="col-md-4">
              <label class="crm-label">NPI Number</label>
              <input type="text" name="npi_number" class="form-control crm-input" placeholder="10-digit NPI">
            </div>
        
            <div class="col-md-4">
              <label class="crm-label">Avg Monthly Claims</label>
              <div class="crm-input-icon-wrap">
                <i class="ti ti-file-invoice crm-input-icon"></i>
                <input type="number" name="avg_monthly_claims" class="form-control crm-input crm-has-icon" placeholder="0">
              </div>
            </div>

            <div class="col-md-4">
              <label class="crm-label">Expected Monthly Collections</label>
              <div class="crm-input-icon-wrap">
                <i class="ti ti-currency-dollar crm-input-icon"></i>
                <input type="number" step="0.01" name="expected_monthly_collections" class="form-control crm-input crm-has-icon" placeholder="0.00">
              </div>
            </div>

            <div class="col-md-6">
              <label class="crm-label">Business Address <span class="crm-req">*</span></label>
              <input type="text" name="address" class="form-control crm-input" placeholder="Street address, suite, floor..." required>
            </div>

            <div class="col-md-3">
              <label class="crm-label">City <span class="crm-req">*</span></label>
              <input type="text" name="city" class="form-control crm-input" required>
            </div>

            <div class="col-md-3">
              <label class="crm-label">State <span class="crm-req">*</span></label>
              <input type="text" name="state" class="form-control crm-input" required>
            </div>

            <div class="col-md-4">
              <label class="crm-label">Zip Code <span class="crm-req">*</span></label>
              <input type="text" name="zip_code" class="form-control crm-input" required>
            </div>

            <div class="col-md-4">
              <label class="crm-label">Country</label>
              <input type="text" name="country" class="form-control crm-input" value="USA">
            </div>

            <div class="col-md-4">
              <label class="crm-label">Time Zone</label>
              <select name="time_zone" class="form-select crm-input">
                <option value="">Select</option>
                <option value="EST">EST — Eastern</option>
                <option value="CST">CST — Central</option>
                <option value="MST">MST — Mountain</option>
                <option value="PST">PST — Pacific</option>
              </select>
            </div>
            
          </div>
        </div>
      </div>
    </div> 

    <div class="col-md-4">
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header">
          <i class="ti ti-address-book"></i>
          <span>Contact Person</span>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">

            <div class="col-md-12">
              <label class="crm-label">Contact Full Name <span class="crm-req">*</span></label>
              <input type="text" name="primary_contact_name" class="form-control crm-input" placeholder="Contact person's full name" required>
            </div>
            
            <div class="col-md-12">
              <label class="crm-label">Contact Title <span class="crm-req">*</span></label>
              <select name="primary_contact_title" class="form-select crm-input" required>
                <?php
                  $selected = set_value('primary_contact_title');
                  foreach (contact_titles_dropdown() as $value => $label):
                ?>
                  <option value="<?= html_escape($value); ?>" <?= ($selected === $value) ? 'selected' : ''; ?>>
                    <?= html_escape($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="col-md-12">
              <label class="crm-label">Contact Email <span class="crm-req">*</span></label>
              <div class="crm-input-icon-wrap">
                <i class="ti ti-mail crm-input-icon"></i>
                <input type="email" name="primary_email" class="form-control crm-input crm-has-icon" placeholder="email@practice.com" required>
              </div>
            </div>

            <div class="col-md-12">
              <label class="crm-label">Contact Phone <span class="crm-req">*</span></label>
              <div class="crm-input-icon-wrap">
                <i class="ti ti-phone crm-input-icon"></i>
                <input type="text" name="primary_phone" class="form-control crm-input crm-has-icon" placeholder="+1 (000) 000-0000" required>
              </div>
            </div>

            <div class="col-12">
                <div class="app-form-group">
                    <label class="app-form-label app-form-label-required" for="account_manager">Account Manager</label>
                    <select name="account_manager" id="account_manager"
                            class="app-form-control js-assign-select" required>
                        <option value="">Select staff member</option>
                        <?php foreach (($users ?? []) as $user): ?>
                            <?php
                                $avatarSrc = user_avatar_url(
                                    !empty($user['profile_image']) ? $user['profile_image'] : (int)$user['id']
                                );
                            ?>
                            <option value="<?= (int)$user['id'] ?>"
                                    data-avatar="<?= html_escape($avatarSrc) ?>"
                                <?= ((int)($client['account_manager'] ?? 0) === (int)$user['id']) ? 'selected' : '' ?>>
                                <?= html_escape($user['fullname'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
          </div>
        </div>
      </div>
    </div> 

</div>    


    </div><!-- /col-xl-8 -->


    <!-- ══════════════════════════════════════
         RIGHT COLUMN — account mgmt & actions
    ═══════════════════════════════════════ -->
    <div class="col-xl-4 col-lg-5">

      <!-- ── 06. Contract Details ── -->
      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header">
          <i class="ti ti-file-text"></i>
          <span>Contract Details</span>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="crm-label">Billing Model <span class="crm-req">*</span></label>
              <select name="billing_model" id="billing_model" class="form-select crm-input" required>
                <option value="percentage">Monthly Percentage</option>
                <option value="flat">Monthly Flat</option>
                <option value="custom">Custom</option>
              </select>
            </div>

            <div class="col-md-6" id="billing-percent-wrapper">
              <label class="crm-label">Percentage Rate %</label>
              <div class="crm-input-icon-wrap">
                <i class="ti ti-percentage crm-input-icon"></i>
                <input type="number" step="0.01" name="rate_percent" id="rate_percent" class="form-control crm-input crm-has-icon" placeholder="0.00">
              </div>
            </div>

            <div class="col-md-6 d-none" id="billing-flat-wrapper">
              <label class="crm-label">Monthly Flat Fee</label>
              <div class="crm-input-icon-wrap">
                <i class="ti ti-currency-dollar crm-input-icon"></i>
                <input type="number" step="0.01" name="rate_flat" id="rate_flat" class="form-control crm-input crm-has-icon" placeholder="0.00">
              </div>
            </div>

            <div class="col-md-6 d-none" id="billing-custom-wrapper">
              <label class="crm-label">Custom Rate</label>
              <div class="crm-input-icon-wrap">
                <i class="ti ti-currency-dollar crm-input-icon"></i>
                <input type="number" step="0.01" name="rate_custom" id="rate_custom" class="form-control crm-input crm-has-icon" placeholder="0.00">
              </div>
            </div>

            <div class="col-md-6">
              <label class="crm-label">Invoice Frequency <span class="crm-req">*</span></label>
              <select name="invoice_frequency" id="invoice_frequency" class="form-select crm-input" required>
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
                <option value="bi-weekly">Bi-Weekly</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label class="crm-label">Contract Start Date</label>
              <input type="date" name="contract_start_date" class="form-control crm-input">
            </div>

            <div class="col-md-6">
              <label class="crm-label">Contract End Date</label>
              <input type="date" name="contract_end_date" class="form-control crm-input">
            </div>

            <div class="col-md-6">
              <label class="crm-label">Onboarding Date</label>
              <input type="date" name="onboarding_date" class="form-control crm-input">
            </div>
            
          </div>
        </div>
      </div>

      <div class="crm-form-card mb-3">
        <div class="crm-form-card-header">
          <i class="ti ti-briefcase"></i>
          <span>Services &amp; Notes</span>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">

            <div class="col-md-6">
              <label class="crm-label">Services Included</label>
              <textarea name="services_included" class="form-control crm-input" rows="3"
                        placeholder="List services: billing, coding, AR follow-up..."></textarea>
            </div>

            <div class="col-md-6">
              <label class="crm-label">Internal Notes</label>
                <textarea name="internal_notes" class="form-control crm-input" rows="3"
                          placeholder="Internal-only notes about this client. Not visible to the client."></textarea>
            </div>
            

          </div>
        </div>
      </div>
      

      <!-- ── Form Actions ── -->
      <div class="crm-form-card crm-form-actions-card">
        <div class="crm-form-card-body">
          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="ti ti-device-floppy me-1"></i> Save Client
          </button>
          <a href="<?= site_url('crm/clients'); ?>" class="btn btn-light-secondary w-100">
            <i class="ti ti-x me-1"></i> Cancel
          </a>
          <div class="crm-form-note mt-2">
            <i class="ti ti-lock-filled"></i>
            Fields marked <span class="crm-req">*</span> are required.
            <code>created_by</code> and timestamps are set automatically.
          </div>
        </div>
      </div>

    </div><!-- /col-xl-4 -->

  </div><!-- /row -->
  </form>

</div><!-- /container-fluid -->


<style>
/* =====================================================
   CRM Add Client Form — Scoped Styles
   Prefix: crm-form- / crm-input / crm-label
   ===================================================== */

/* ── Form card ── */
.crm-form-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.crm-form-card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  letter-spacing: .01em;
}

.crm-form-card-header i {
  font-size: 15px;
  color: #056464;
}

.crm-form-card-body {
  padding: 18px 16px;
}

/* ── Labels ── */
.crm-label {
  display: block;
  font-size: 11.5px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 5px;
  letter-spacing: .01em;
}

.crm-req {
  color: #dc2626;
  margin-left: 1px;
}

.crm-hint {
  font-size: 10.5px;
  color: #94a3b8;
  margin-top: 4px;
  line-height: 1.4;
}

/* ── Inputs ── */
.crm-input {
  font-size: 13px;
  font-weight: 400;
  color: #0f172a;
  border: 1.5px solid #e2e8f0;
  border-radius: 7px;
  padding: 7px 11px;
  transition: border-color .15s, box-shadow .15s;
  background: #ffffff;
}

.crm-input:focus {
  border-color: #056464;
  box-shadow: 0 0 0 3px rgba(5,100,100,.10);
  outline: none;
}

.crm-input::placeholder {
  color: #c0cad8;
  font-weight: 400;
}

.crm-input-readonly {
  background: #f8fafc !important;
  color: #64748b !important;
  cursor: default;
}

/* ── Input with icon ── */
.crm-input-icon-wrap {
  position: relative;
}

.crm-input-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
  color: #94a3b8;
  pointer-events: none;
  z-index: 2;
}

.crm-has-icon {
  padding-left: 32px;
}

/* ── Checklist (right sidebar) ── */
.crm-checklist {
  list-style: none;
  padding: 0;
  margin: 0;
}

.crm-checklist-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  font-size: 12px;
  color: #475569;
  border-bottom: 1px solid #f1f5f9;
}

.crm-checklist-item:last-child {
  border-bottom: none;
}

.crm-check-icon {
  font-size: 15px;
  flex-shrink: 0;
  color: #cbd5e1;
}

.crm-check-icon.crm-check-done {
  color: #16a34a;
}

.crm-checklist-item span:nth-child(2) {
  flex: 1;
}

.crm-check-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 600;
  padding: 1px 7px;
  border-radius: 999px;
  background: #fef2f2;
  color: #dc2626;
}

.crm-check-badge-auto {
  background: #f0fdf4;
  color: #16a34a;
}

.crm-check-badge-opt {
  background: #f8fafc;
  color: #94a3b8;
}

/* ── Actions card ── */
.crm-form-actions-card {
  position: sticky;
  top: 16px;
}

.crm-form-note {
  font-size: 10.5px;
  color: #94a3b8;
  line-height: 1.5;
  text-align: center;
}

.crm-form-note i {
  font-size: 11px;
  vertical-align: middle;
  margin-right: 2px;
}

.crm-form-note code {
  font-size: 10px;
  background: #f1f5f9;
  color: #475569;
  padding: 0 4px;
  border-radius: 3px;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .crm-form-card-body { padding: 14px 12px; }
  .crm-form-actions-card { position: static; }
}
</style>


<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ── Group toggle ── */
  const isGroupSelect  = document.getElementById('is_group');
  const groupWrapper   = document.getElementById('client-group-wrapper');
  const groupSelect    = document.getElementById('client_group_id');

  function toggleGroupFields() {
    const isGroup = isGroupSelect.value === '1';
    groupWrapper.classList.toggle('d-none', !isGroup);
    if (isGroup) {
      groupSelect.setAttribute('required', 'required');
    } else {
      groupSelect.removeAttribute('required');
      groupSelect.value = '';
    }
  }
  isGroupSelect.addEventListener('change', toggleGroupFields);
  toggleGroupFields();


  /* ── Billing model toggle ── */
  const billingModel   = document.getElementById('billing_model');
  const percentWrap    = document.getElementById('billing-percent-wrapper');
  const flatWrap       = document.getElementById('billing-flat-wrapper');
  const customWrap     = document.getElementById('billing-custom-wrapper');
  const percentInp     = document.getElementById('rate_percent');
  const flatInp        = document.getElementById('rate_flat');
  const customInp      = document.getElementById('rate_custom');

  function toggleBillingFields() {
    const model = billingModel.value;
    const all   = [[percentWrap, percentInp], [flatWrap, flatInp], [customWrap, customInp]];

    all.forEach(([wrap, inp]) => {
      wrap.classList.add('d-none');
      inp.required = false;
      inp.value    = '';
    });

    const map = { percentage: [percentWrap, percentInp], flat: [flatWrap, flatInp], custom: [customWrap, customInp] };
    if (map[model]) {
      map[model][0].classList.remove('d-none');
      map[model][1].required = true;
    }
  }
  billingModel.addEventListener('change', toggleBillingFields);
  toggleBillingFields();


  /* ── Live checklist ── */
  const watchFields = [
    'practice_name','practice_type','specialty',
    'primary_contact_name','primary_email','address',
    'contract_start_date','internal_notes'
  ];

  function updateChecklist() {
    watchFields.forEach(function(name) {
      const input = document.querySelector('[name="' + name + '"]');
      const item  = document.querySelector('.crm-checklist-item[data-field="' + name + '"]');
      if (!input || !item) return;
      const filled = input.value.trim() !== '';
      const icon   = item.querySelector('.crm-check-icon');
      icon.classList.toggle('ti-circle-check', filled);
      icon.classList.toggle('ti-circle', !filled);
      icon.classList.toggle('crm-check-done', filled);
    });
  }

  watchFields.forEach(function(name) {
    const el = document.querySelector('[name="' + name + '"]');
    if (el) el.addEventListener('input', updateChecklist);
  });

  updateChecklist();

});
</script>