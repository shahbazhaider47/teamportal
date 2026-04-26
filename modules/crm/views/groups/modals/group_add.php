<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade app-modal" id="clientGroupCreateModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <?= form_open(site_url('crm/group_store'), ['id' => 'crm-group-add-form', 'class' => 'app-form']); ?>

                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-success">
                            <i class="ti ti-building"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title">Add Client Group</div>
                            <div class="app-modal-subtitle">Register a new partner or third-party company that onboards clients</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="app-modal-body" style="max-height:calc(100vh - 220px);overflow-y:auto;">

                    <!-- ── Identity ───────────────────────────────────── -->
                    <div class="app-form-section">
                        <div class="row g-3">

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label app-form-label-required">Group Name</label>
                                    <input type="text" name="group_name" class="app-form-control"
                                           placeholder="e.g. Sunrise Billing Partners" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label app-form-label-required">Company Name</label>
                                    <input type="text" name="company_name" class="app-form-control"
                                           placeholder="Legal / registered company name" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Tax ID</label>
                                    <input type="text" name="tax_id" class="app-form-control"
                                           placeholder="EIN, VAT, NTN or equivalent">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Contact Person</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-user"></i></span>
                                        <input type="text" name="contact_person" class="app-form-control"
                                               placeholder="Full name">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Contact Email</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-mail"></i></span>
                                        <input type="email" name="contact_email" class="app-form-control"
                                               placeholder="email@company.com">
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
                                        <input type="tel" name="contact_alt_phone" class="app-form-control"
                                               placeholder="(123) 456-7890">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Fax Number</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-device-fax"></i></span>
                                        <input type="tel" name="fax_number" class="app-form-control"
                                               placeholder="(123) 456-7890">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Billing Email</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-mail-forward"></i></span>
                                        <input type="email" name="billing_email" class="app-form-control"
                                               placeholder="invoices@company.com">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="app-form-group">
                                    <label class="app-form-label">Website</label>
                                    <div class="app-form-input-wrap">
                                        <span class="app-form-input-prefix"><i class="ti ti-world"></i></span>
                                        <input type="url" name="website" class="app-form-control"
                                               placeholder="https://example.com">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Contract Start Date</label>
                                    <input type="date" name="contract_date" class="app-form-control">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Contract End Date</label>
                                    <input type="date" name="contract_end" class="app-form-control">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Auto Renew</label>
                                    <div class="app-form-select-wrap">
                                        <select name="auto_renew" class="app-form-control">
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Next Renewal Date</label>
                                    <input type="date" name="next_renew" class="app-form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Last Renewal Date</label>
                                    <input type="date" name="last_renew" class="app-form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Contract File</label>
                                    <input type="file" name="contract_file" class="app-form-control"
                                           accept=".pdf,.doc,.docx">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Invoice Mode</label>
                                    <div class="app-form-select-wrap">
                                        <select name="invoice_mode" class="app-form-control">
                                            <option value="single">Single — One invoice for all clients</option>
                                            <option value="separate">Separate — Per client invoice</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Payment Terms</label>
                                    <div class="app-form-select-wrap">
                                        <select name="payment_terms" class="app-form-control">
                                            <option value="">— Select —</option>
                                            <?php foreach ([
                                                'Due on Receipt', 'Net 15', 'Net 30',
                                                'Net 45', 'Net 60', 'Custom'
                                            ] as $term): ?>
                                                <option value="<?= html_escape($term) ?>"><?= html_escape($term) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Onboarding Status</label>
                                    <div class="app-form-select-wrap">
                                        <select name="onboarding_status" class="app-form-control">
                                            <option value="pending" selected>Pending</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="on_hold">On Hold</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>


                    <!-- ── Business Address ────────────────────────────────── -->
                    <div class="app-form-section">
                        <div class="row g-3">

                            <div class="col-md-12">
                                <div class="app-form-group">
                                    <label class="app-form-label">Business Address</label>
                                    <input type="text" name="address" class="app-form-control"
                                           placeholder="Street address">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">City</label>
                                    <input type="text" name="city" class="app-form-control" placeholder="City">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">State</label>
                                    <input type="text" name="state" class="app-form-control"
                                           placeholder="CA" maxlength="100">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Zip Code</label>
                                    <input type="text" name="zip_code" class="app-form-control"
                                           placeholder="12345">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="app-form-group">
                                    <label class="app-form-label">Country</label>
                                    <input type="text" name="country" class="app-form-control"
                                           placeholder="USA" value="US">
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <!-- ── Internal Notes ─────────────────────────────── -->
                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="app-form-section-label">
                            <i class="ti ti-note" style="font-size:12px;color:#5ebfbf;"></i>
                            Internal Notes
                        </div>
                        <div class="app-form-group">
                            <textarea name="notes" class="app-form-control" rows="3"
                                      placeholder="Add any internal notes, context, or reminders about this group…"></textarea>
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
                        <i class="ti ti-device-floppy"></i> Save Group
                    </button>
                </div>

            <?= form_close(); ?>
        </div>
    </div>
</div>