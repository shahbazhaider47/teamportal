<div class="tab-pane fade show active" id="clientdetails" role="tabpanel" aria-labelledby="clientdetails-tab" tabindex="0">    
    <div class="section-card row mb-3">
        <div class="col-md-3">

      <div class="audit-section" style="margin-bottom: 16px;">
            <div class="audit-section-header">
                <span class="text-primary"><i class="ti ti-device-landline-phone"></i>Primary Contact</span>
                    <button class="btn-icon">
                    <i class="ti ti-pencil"></i>
                    </button>
            </div>
        <div>

          <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-user"></i></div>
                <span class="audit-label">Contact Name <br>
                <span class="text-muted capital"><?= html_escape($client['primary_contact_name'] ?? '') ?></span>
                </span>
          </div>

          <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-address-book"></i></div>
                <span class="audit-label">Contact Title <br>
                <span class="text-muted capital"><?= html_escape($client['primary_contact_title'] ?? '') ?></span>
                </span>
          </div>

          <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-mail"></i></div>
                <span class="audit-label">Contact Email <br>
                <span class="text-muted capital"><?= html_escape($client['primary_email'] ?? '') ?></span>
                </span>
          </div>

          <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-phone"></i></div>
                <span class="audit-label">Contact Phone <br>
                <span class="text-muted capital"><?= html_escape($client['primary_phone'] ?? '') ?></span>
                </span>
          </div>

          <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-map-pin"></i></div>
                <span class="audit-label">Business Address <br>
                <span class="text-muted capital"><?= html_escape($client['address'] ?? '') ?>
                <?= html_escape(trim($client['city'] . ', ' . $client['state'] . ' ' . $client['zip_code'] . ' ' . $client['country'], '')) ?>
                </span>
                </span>
          </div>
          
        </div>
      </div>
      
      <div class="audit-section" style="margin-bottom: 16px;">
        <div>

          <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-plus"></i></div>
                <span class="audit-label">Created At <br>
                <span class="text-muted capital"><?= html_escape($client['created_at'] ?? '') ?>
                <i class="ti ti-dots-vertical"></i>
                <?= user_profile_small($client['created_by_name'] ?? '') ?>
                </span>
                </span>
          </div>

          <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-stats"></i></div>
                <span class="audit-label">Last Updated <br>
                <span class="text-muted capital"><?= html_escape($client['updated_at'] ?? '') ?>
                <?php if (!empty($client['updated_by_name'])): ?>
                <i class="ti ti-dots-vertical"></i>
                <?= user_profile_small($client['updated_by_name'] ?? '') ?>
                <?php endif; ?>
                </span>
                </span>
          </div>
          
        </div>
      </div>
        
        </div>

        <div class="col-md-9">
        
        <div class="detail-grid">

            <div class="detail-cell">
                <div class="dc-label">Client Code</div>
                <div class="dc-value mono"><?= html_escape($client['client_code'] ?? '_') ?></div>
            </div>


            <div class="detail-cell">
                <div class="dc-label">Is Group Client</div>
                <div class="dc-value">
                    <?php if (!empty($client['is_group'])): ?> Yes
                        <span class="badge bg-light-primary"><?= html_escape($client['group_name'] ?? '_') ?></span>
                    <?php else: ?>
                        No <span class="badge bg-primary">Direct Client</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Practice Name</div>
                <div class="dc-value"><?= html_escape($client['practice_name'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Practice Legal Name</div>
                <div class="dc-value"><?= html_escape($client['practice_legal_name'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Practice Type</div>
                <div class="dc-value"><?= html_escape($client['practice_type'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Specialty</div>
                <div class="dc-value"><?= html_escape($client['specialty'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Tax ID</div>
                <div class="dc-value mono"><?= html_escape($client['tax_id'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">NPI Number</div>
                <div class="dc-value mono"><?= html_escape($client['npi_number'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Time Zone</div>
                <div class="dc-value"><?= html_escape($client['time_zone'] ?? '_') ?></div>
            </div>

            <?php
            $model = strtolower($client['billing_model'] ?? '_');
            $rate  = '_';
            
            if ($model === 'percentage') {
                $rate = !empty($client['rate_percent']) ? $client['rate_percent'].'%' : '_';
            }
            elseif ($model === 'flat') {
                $rate = !empty($client['rate_flat']) ? '$'.$client['rate_flat'] : '_';
            }
            elseif ($model === 'custom') {
                $rate = $client['rate_custom'] ?? '_';
            }
            ?>
            
            <div class="detail-cell">
                <div class="dc-label">Billing Model</div>
                <div class="dc-value">
                    <?= html_escape(ucfirst($model)) ?>
                    <?php if ($rate): ?>
                        <span class="text-muted">(<?= html_escape($rate) ?>)</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Contract Duration</div>
                <div class="dc-value">
                <i class="ti ti-calendar-event text-success" style="font-size:13px;"></i>
                <?= !empty($client['contract_start_date']) ? date('M d, Y', strtotime($client['contract_start_date'])) : '_' ?>
                <i class="ti ti-dots-vertical"></i>
                <i class="ti ti-calendar-off text-danger" style="font-size:13px;"></i>
                <?= !empty($client['contract_end_date']) ? date('M d, Y', strtotime($client['contract_end_date'])) : '_' ?>
                </div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Invoice Frequency</div>
                <div class="dc-value capital">
                <i class="ti ti-calendar text-warning me-1" style="font-size:13px;"></i>
                <?= html_escape($client['invoice_frequency'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Services Included</div>
                <div class="dc-value"><?= html_escape($client['services_included'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Avg Monthly Claims</div>
                <div class="dc-value"><?= html_escape($client['avg_monthly_claims'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Expected Monthly Collections</div>
                <div class="dc-value">$<?= html_escape($client['expected_monthly_collections'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Account Manager</div>
                <div class="dc-value"><?= html_escape($client['account_manager'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Onboarding Date</div>
                <div class="dc-value"><?= !empty($client['onboarding_date']) ? date('M d, Y', strtotime($client['onboarding_date'])) : '_' ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Offboarding Date</div>
                <div class="dc-value"><?= !empty($client['offboarding_date']) ? date('M d, Y', strtotime($client['offboarding_date'])) : '_' ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Termination Reason</div>
                <div class="dc-value"><?= html_escape($client['termination_reason'] ?? '_') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Internal Notes</div>
                <div class="dc-value" style="font-size: 12px;"><?= html_escape($client['internal_notes'] ?? '_') ?></div>
            </div>


        </div>
                </div>
        
            
    </div>
</div>  