<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$leadId = (int)($lead['id'] ?? 0);
?>

<div class="modal fade app-modal" id="editLeadContactModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="post" action="<?= site_url('crm/leads/update_contact_info/' . $leadId) ?>" id="leadEditForm">

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-primary">
                            <i class="ti ti-id-badge"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title">Edit Contact Info</div>
                            <div class="app-modal-subtitle">Edit and update the correct lead contact info</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body modal-body">

                    <input type="hidden" name="id" value="<?= $leadId; ?>">
                    
                    <div class="app-form-section">
                        <div class="app-form-section-label">
                            <i class="ti ti-notes" style="font-size:12px;color:#5ebfbf;"></i>
                            Strategy
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_contact_person">Contact Person</label>
                                    <input type="text" name="contact_person" id="edit_contact_person" class="app-form-control"
                                           value="<?= html_escape($lead['contact_person'] ?? '') ?>" placeholder="Full name">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_contact_email">Contact Email</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-mail"></i></span>
                                        <input type="email" name="contact_email" id="edit_contact_email" class="app-form-control"
                                               value="<?= html_escape($lead['contact_email'] ?? '') ?>" placeholder="email@practice.com">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_contact_phone">Contact Phone</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-phone"></i></span>
                                        <input type="text" name="contact_phone" id="edit_contact_phone" class="app-form-control"
                                               value="<?= html_escape($lead['contact_phone'] ?? '') ?>" placeholder="+1 (000) 000-0000">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_alternate_phone">Alternate Phone</label>
                                    <input type="text" name="alternate_phone" id="edit_alternate_phone" class="app-form-control"
                                           value="<?= html_escape($lead['alternate_phone'] ?? '') ?>" placeholder="Alternate number">
                                </div>
                            </div>

                            <div class="col-md-4">
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

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_best_time_to_contact">Best Time to Contact</label>
                                    <input type="text" name="best_time_to_contact" id="edit_best_time_to_contact" class="app-form-control"
                                           value="<?= html_escape($lead['best_time_to_contact'] ?? '') ?>" placeholder="Morning / Afternoon / Evening">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_website">Website</label>
                                    <input type="text" name="website" id="edit_website" class="app-form-control"
                                           value="<?= html_escape($lead['website'] ?? '') ?>" placeholder="https://example.com">
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_address">Street Address</label>
                                    <input type="text" name="address" id="edit_address" class="app-form-control"
                                           value="<?= html_escape($lead['address'] ?? '') ?>" placeholder="Street address">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_city">City</label>
                                    <input type="text" name="city" id="edit_city" class="app-form-control"
                                           value="<?= html_escape($lead['city'] ?? '') ?>" placeholder="City">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_state">State</label>
                                    <input type="text" name="state" id="edit_state" class="app-form-control"
                                           value="<?= html_escape($lead['state'] ?? '') ?>" placeholder="State">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_zip_code">Zip Code</label>
                                    <input type="text" name="zip_code" id="edit_zip_code" class="app-form-control"
                                           value="<?= html_escape($lead['zip_code'] ?? '') ?>" placeholder="00000">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label" for="edit_country">Country</label>
                                    <input type="text" name="country" id="edit_country" class="app-form-control"
                                           value="<?= html_escape($lead['country'] ?? '') ?>" placeholder="Country">
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
                        <i class="ti ti-device-floppy"></i>Update
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>