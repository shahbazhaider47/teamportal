<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$leadId = (int)($lead['id'] ?? 0);

function lead_dt_local($value)
{
    if (empty($value)) {
        return '';
    }

    $ts = strtotime((string)$value);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}

function lead_date_only($value)
{
    if (empty($value)) {
        return '';
    }

    $ts = strtotime((string)$value);
    return $ts ? date('Y-m-d', $ts) : '';
}
?>

<div class="modal fade app-modal" id="leadEditModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <form method="post" action="<?= site_url('crm/leads/update/' . $leadId) ?>" id="leadEditForm">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-primary">
                            <i class="ti ti-edit"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title" id="leadEditModalLabel">Edit Lead</div>
                            <div class="app-modal-subtitle">Update lead information, pipeline details, and practice context</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body modal-body">

                    <input type="hidden" name="id" value="<?= $leadId; ?>">

                    <div class="app-form-section">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label app-form-label-required" for="edit_practice_name">Practice Name</label>
                                    <input type="text" name="practice_name" id="edit_practice_name" class="app-form-control"
                                           value="<?= html_escape($lead['practice_name'] ?? '') ?>" placeholder="e.g. Valley Medical Group" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_lead_source">Lead Source</label>
                                    <div class="app-form-select-wrap">
                                        <select name="lead_source" id="edit_lead_source" class="app-form-control" required>
                                            <?php
                                            $selected = set_value('lead_source', $lead['lead_source'] ?? '');
                                            foreach (lead_source_dropdown() as $value => $label):
                                            ?>
                                                <option value="<?= html_escape($value); ?>"
                                                    <?= ((string)$selected === (string)$value) ? 'selected' : ''; ?>>
                                                    <?= html_escape($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
              
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_practice_type">Practice Type</label>
                                    <div class="app-form-select-wrap">
                                        <select name="practice_type" id="edit_practice_type" class="app-form-control">
                                            <?php foreach (['solo','group','multi-specialty','hospital','clinic','other'] as $s): ?>
                                                <option value="<?= html_escape($s) ?>" <?= (($lead['practice_type'] ?? '') === $s) ? 'selected' : '' ?>>
                                                    <?= html_escape(ucwords(str_replace('-', ' ', $s))) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_specialty">Specialty</label>
                                    <input type="text" name="specialty" id="edit_specialty" class="app-form-control"
                                           value="<?= html_escape($lead['specialty'] ?? '') ?>" placeholder="e.g. Cardiology">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_current_billing_provider">Current Billing Provider</label>
                                    <input type="text" name="current_billing_provider" id="edit_current_billing_provider" class="app-form-control"
                                           value="<?= html_escape($lead['current_billing_provider'] ?? '') ?>" placeholder="Current vendor/provider">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_current_emr_system">Current EMR System</label>
                                    <input type="text" name="current_emr_system" id="edit_current_emr_system" class="app-form-control"
                                           value="<?= html_escape($lead['current_emr_system'] ?? '') ?>" placeholder="e.g. Athena, eClinicalWorks">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_patient_volume_per_month">Patient Volume / Month</label>
                                    <input type="number" name="patient_volume_per_month" id="edit_patient_volume_per_month" class="app-form-control"
                                           value="<?= html_escape($lead['patient_volume_per_month'] ?? '') ?>" placeholder="0">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_monthly_claim_volume">Monthly Claim Volume</label>
                                    <input type="number" name="monthly_claim_volume" id="edit_monthly_claim_volume" class="app-form-control"
                                           value="<?= html_escape($lead['monthly_claim_volume'] ?? '') ?>" placeholder="0">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_monthly_collections">Monthly Collections</label>
                                    <input type="number" step="0.01" name="monthly_collections" id="edit_monthly_collections" class="app-form-control"
                                           value="<?= html_escape($lead['monthly_collections'] ?? '') ?>" placeholder="0.00">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_contact_person">Contact Person</label>
                                    <input type="text" name="contact_person" id="edit_contact_person" class="app-form-control"
                                           value="<?= html_escape($lead['contact_person'] ?? '') ?>" placeholder="Full name">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_contact_email">Contact Email</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-mail"></i></span>
                                        <input type="email" name="contact_email" id="edit_contact_email" class="app-form-control"
                                               value="<?= html_escape($lead['contact_email'] ?? '') ?>" placeholder="email@practice.com">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_contact_phone">Contact Phone</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-phone"></i></span>
                                        <input type="text" name="contact_phone" id="edit_contact_phone" class="app-form-control"
                                               value="<?= html_escape($lead['contact_phone'] ?? '') ?>" placeholder="+1 (000) 000-0000">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_alternate_phone">Alternate Phone</label>
                                    <input type="text" name="alternate_phone" id="edit_alternate_phone" class="app-form-control"
                                           value="<?= html_escape($lead['alternate_phone'] ?? '') ?>" placeholder="Alternate number">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_preferred_contact_method">Preferred Contact Method</label>
                                    <div class="app-form-select-wrap">
                                        <select name="preferred_contact_method" id="edit_preferred_contact_method" class="app-form-control">
                                            <?php foreach (['any' => 'Any', 'email' => 'Email', 'phone' => 'Phone', 'text' => 'Text'] as $k => $v): ?>
                                                <option value="<?= html_escape($k) ?>" <?= (($lead['preferred_contact_method'] ?? '') === $k) ? 'selected' : '' ?>>
                                                    <?= html_escape($v) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_best_time_to_contact">Best Time to Contact</label>
                                    <input type="text" name="best_time_to_contact" id="edit_best_time_to_contact" class="app-form-control"
                                           value="<?= html_escape($lead['best_time_to_contact'] ?? '') ?>" placeholder="Morning / Afternoon / Evening">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_website">Website</label>
                                    <input type="text" name="website" id="edit_website" class="app-form-control"
                                           value="<?= html_escape($lead['website'] ?? '') ?>" placeholder="https://example.com">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_current_billing_method">Current Billing Method</label>
                                    <div class="app-form-select-wrap">
                                        <select name="current_billing_method" id="edit_current_billing_method" class="app-form-control">
                                            <option value="">-- Select --</option>
                                            <?php 
                                            $selectedBillingMethod = set_value('current_billing_method', $lead['current_billing_method'] ?? '');
                                            foreach (['in-house', 'outsourced', 'hybrid', 'none'] as $method): 
                                            ?>
                                                <option value="<?= html_escape($method); ?>"
                                                    <?= ((string)$selectedBillingMethod === (string)$method) ? 'selected' : ''; ?>>
                                                    <?= html_escape(ucwords(str_replace('-', ' ', $method))); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_estimated_monthly_revenue">Estimated Monthly Revenue</label>
                                    <input type="number" step="0.01" name="estimated_monthly_revenue" id="edit_estimated_monthly_revenue" class="app-form-control"
                                           value="<?= html_escape($lead['estimated_monthly_revenue'] ?? '') ?>" placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_estimated_setup_fee">Estimated Setup Fee</label>
                                    <input type="number" step="0.01" name="estimated_setup_fee" id="edit_estimated_setup_fee" class="app-form-control"
                                           value="<?= html_escape($lead['estimated_setup_fee'] ?? '') ?>" placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_estimated_annual_value">Estimated Annual Value</label>
                                    <input type="number" step="0.01" name="estimated_annual_value" id="edit_estimated_annual_value" class="app-form-control"
                                           value="<?= html_escape($lead['estimated_annual_value'] ?? '') ?>" placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_referred_by">Referred By</label>
                                    <div class="app-form-select-wrap">
                                        <select name="referred_by" id="edit_referred_by" class="app-form-control">
                                            <option value="">— Select —</option>
                            
                                            <?php
                                            $selectedReferrer = set_value('referred_by', $lead['referred_by'] ?? '');
                            
                                            $referrers = [
                                                'Existing Client',
                                                'Business Partner',
                                                'Vendor Partner',
                                                'Consultant',
                                                'Industry Contact',
                                                'Employee Referral',
                                                'Conference / Event',
                                                'Online Community',
                                                'LinkedIn Connection',
                                                'Other'
                                            ];
                            
                                            foreach ($referrers as $ref):
                                            ?>
                                                <option value="<?= html_escape($ref); ?>"
                                                    <?= ((string)$selectedReferrer === (string)$ref) ? 'selected' : ''; ?>>
                                                    <?= html_escape($ref); ?>
                                                </option>
                                            <?php endforeach; ?>
                            
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_referral_type">Referral Type</label>
                                    <input type="text" name="referral_type" id="edit_referral_type" class="app-form-control"
                                           value="<?= html_escape($lead['referral_type'] ?? '') ?>" placeholder="e.g. Existing client, Partner">
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <div class="app-form-section">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_address">Street Address</label>
                                    <input type="text" name="address" id="edit_address" class="app-form-control"
                                           value="<?= html_escape($lead['address'] ?? '') ?>" placeholder="Street address">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_city">City</label>
                                    <input type="text" name="city" id="edit_city" class="app-form-control"
                                           value="<?= html_escape($lead['city'] ?? '') ?>" placeholder="City">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_state">State</label>
                                    <input type="text" name="state" id="edit_state" class="app-form-control"
                                           value="<?= html_escape($lead['state'] ?? '') ?>" placeholder="State">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_zip_code">Zip Code</label>
                                    <input type="text" name="zip_code" id="edit_zip_code" class="app-form-control"
                                           value="<?= html_escape($lead['zip_code'] ?? '') ?>" placeholder="00000">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_country">Country</label>
                                    <input type="text" name="country" id="edit_country" class="app-form-control"
                                           value="<?= html_escape($lead['country'] ?? '') ?>" placeholder="Country">
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <div class="app-form-section">
                        <div class="row g-3">

                            <div class="col-md-12">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_internal_notes">Internal Notes</label>
                                    <textarea name="internal_notes" id="edit_internal_notes" class="app-form-control" rows="3"
                                              placeholder="Internal lead notes"><?= html_escape($lead['internal_notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="app-modal-footer">
                    <div class="app-modal-footer-left">
                        <i class="ti ti-info-circle" style="font-size:14px;"></i>
                        All changes will be saved to this lead record.
                    </div>
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-device-floppy"></i>Update Lead
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>