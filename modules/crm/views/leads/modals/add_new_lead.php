<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade app-modal" id="leadCreateModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <form method="post" action="<?= site_url('crm/leads/store') ?>" class="app-form">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-success">
                            <i class="ti ti-plus"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title">Add New Lead</div>
                            <div class="app-modal-subtitle">Fill in the practice details, contact info, and lead classification</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body" style="max-height:calc(100vh - 220px);overflow-y:auto;">

                    <div class="app-form-section">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label app-form-label-required">Practice Name</label>
                                    <input type="text" name="practice_name" class="app-form-control"
                                           placeholder="e.g. Valley Medical Group" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label app-form-label-required">Lead Source</label>
                                    <div class="app-form-select-wrap">
                                        <select name="lead_source" class="app-form-control" required>
                                            <?php foreach (lead_source_dropdown() as $value => $label): ?>
                                                <option value="<?= html_escape($value) ?>"
                                                    <?= (set_value('lead_source') === $value) ? 'selected' : '' ?>>
                                                    <?= html_escape($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label app-form-label-required">Lead Status</label>
                                    <div class="app-form-select-wrap">
                                    <?php $selected_status = crm_form_value('lead_status', $lead ?? [], 'crm_default_lead_status', 'new'); ?>
                                    
                                    <select name="lead_status" class="app-form-control" required>
                                        <?php foreach (lead_status_dropdown() as $value => $label): ?>
                                            <option value="<?= html_escape($value) ?>"
                                                <?= ($selected_status == $value) ? 'selected' : '' ?>>
                                                <?= html_escape($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Lead Quality</label>
                                    <div class="app-form-select-wrap">
                                        <select name="lead_quality" class="app-form-control">
                                            <option value="hot">🔥 Hot</option>
                                            <option value="warm" selected>☀️ Warm</option>
                                            <option value="cold">❄️ Cold</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Referred By</label>
                                    <div class="app-form-select-wrap">
                                        <select name="referred_by" class="app-form-control">
                                            <option value="">— Select —</option>
                                            <?php foreach ([
                                                'Existing Client','Business Partner','Vendor Partner','Consultant',
                                                'Industry Contact','Employee Referral','Conference / Event',
                                                'Online Community','LinkedIn Connection','Other'
                                            ] as $ref): ?>
                                                <option value="<?= html_escape($ref) ?>"><?= html_escape($ref) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Referral Type</label>
                                    <input type="text" name="referral_type" class="app-form-control"
                                           value="<?= html_escape($lead['referral_type'] ?? '') ?>" placeholder="e.g. Existing client, Partner">
                                </div>
                            </div>
                
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Contact Person</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-user"></i></span>
                                        <input type="text" name="contact_person" class="app-form-control"
                                               placeholder="Jackson Andrew">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Contact Email</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-mail"></i></span>
                                        <input type="email" name="contact_email" class="app-form-control"
                                               placeholder="email@practice.com">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Contact Phone</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-phone"></i></span>
                                        <input type="tel" name="contact_phone" class="app-form-control"
                                               placeholder="(123) 456-7890">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Alternate Phone</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-phone-call"></i></span>
                                        <input type="tel" name="alternate_phone" class="app-form-control"
                                               placeholder="(123) 456-7890">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Preferred Contact Method</label>
                                    <div class="app-form-select-wrap">
                                        <select name="preferred_contact_method" class="app-form-control">
                                            <option value="any">Any</option>
                                            <option value="email">Email</option>
                                            <option value="phone">Phone</option>
                                            <option value="text">Text</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Best Time to Contact</label>
                                    <input type="text" name="best_time_to_contact" class="app-form-control"
                                           placeholder="e.g. 10am – 2pm EST">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Practice Type</label>
                                    <div class="app-form-select-wrap">
                                        <select name="practice_type" class="app-form-control">
                                            <option value="">— Select —</option>
                                            <?php foreach (['solo','group','multi-specialty','hospital','clinic','other'] as $type): ?>
                                                <option value="<?= html_escape($type) ?>"><?= html_escape(ucwords(str_replace('-', ' ', $type))) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Specialty</label>
                                    <input type="text" name="specialty" class="app-form-control"
                                           placeholder="e.g. Cardiology">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Current Billing Provider</label>
                                    <input type="text" name="current_billing_provider" class="app-form-control"
                                           placeholder="Company name">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Current EMR System</label>
                                    <input type="text" name="current_emr_system" class="app-form-control"
                                           placeholder="e.g. Epic, Cerner">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Billing Method</label>
                                    <div class="app-form-select-wrap">
                                        <select name="current_billing_method" class="app-form-control">
                                            <option value="">— Select —</option>
                                            <?php foreach (['in-house','outsourced','hybrid','none'] as $method): ?>
                                                <option value="<?= html_escape($method) ?>"><?= html_escape(ucwords(str_replace('-', ' ', $method))) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Patient Volume / Month</label>
                                    <div class="app-form-input-wrap suffix">
                                        <input type="number" name="patient_volume_per_month"
                                               class="app-form-control" min="0" step="1" placeholder="0">
                                        <span class="app-form-input-suffix" style="font-weight:600;color:#475569;">pts</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Monthly Claim Volume</label>
                                    <div class="app-form-input-wrap suffix">
                                        <input type="number" name="monthly_claim_volume"
                                               class="app-form-control" min="0" step="1" placeholder="0">
                                        <span class="app-form-input-suffix" style="font-weight:600;color:#475569;">claims</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Monthly Collections</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix">$</span>
                                        <input type="number" name="monthly_collections"
                                               class="app-form-control" min="0" step="0.01" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Website</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-world"></i></span>
                                        <input type="url" name="website" class="app-form-control"
                                               placeholder="https://example.com">
                                    </div>
                                </div>
                            </div>
                        

                        </div>
                    </div>
                    
                    <div class="app-form-section">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label">Street Address</label>
                                    <input type="text" name="address" class="app-form-control"
                                           placeholder="Street address">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label">City</label>
                                    <input type="text" name="city" class="app-form-control" placeholder="City">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label">State</label>
                                    <input type="text" name="state" class="app-form-control"
                                           placeholder="CA" maxlength="2">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label">ZIP Code</label>
                                    <input type="text" name="zip_code" class="app-form-control" placeholder="12345">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="app-form-group">
                                    <label class="app-form-label">Country</label>
                                    <input type="text" name="country" class="app-form-control" placeholder="USA">
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="app-form-section-label">
                            <i class="ti ti-note" style="font-size:12px;color:#5ebfbf;"></i>
                            Internal Notes
                        </div>
                        <div class="app-form-group">
                            <textarea name="internal_notes" class="app-form-control" rows="3"
                                      placeholder="Add any additional notes, context, or reminders about this lead…"></textarea>
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
                        <i class="ti ti-device-floppy"></i>Save Lead
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>