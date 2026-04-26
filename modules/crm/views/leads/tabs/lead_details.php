<div class="tab-pane fade show active" id="leaddetails" role="tabpanel" aria-labelledby="leaddetails-tab" tabindex="0">    
    <div class="section-card mb-3">

        <!-- Basic Information Section -->
        <div class="detail-grid">
            <div class="detail-cell">
                <div class="dc-label">Lead UUID</div>
                <div class="dc-value mono" style="font-size: 12px;"><?= html_escape($lead['lead_uuid'] ?? '—') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Practice Type</div>
                <div class="dc-value capital"><?= html_escape(ucwords(str_replace(['_', '-'], ' ', $lead['practice_type'] ?? '—'))) ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Specialty</div>
                <div class="dc-value capital"><?= html_escape($lead['specialty'] ?? '—') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Patient Volume</div>
                <div class="dc-value"><?= number_format((int)($lead['patient_volume_per_month'] ?? 0)) ?>/Month</div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Current Billing Provider</div>
                <div class="dc-value"><?= html_escape($lead['current_billing_provider'] ?? '—') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Current EMR System</div>
                <div class="dc-value"><?= html_escape($lead['current_emr_system'] ?? '—') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Monthly Claim Volume</div>
                <div class="dc-value"><?= number_format((int)($lead['monthly_claim_volume'] ?? 0)) ?>/month</div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Current Billing Method</div>
                <div class="dc-value capital"><?= html_escape($lead['current_billing_method'] ?? '—') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Monthly Collections</div>
                <div class="dc-value">$<?= number_format((float)($lead['monthly_collections'] ?? 0), 2) ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Import Batch ID</div>
                <div class="dc-value mono"><?= html_escape($lead['import_batch_id'] ?? '—') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Import Source File</div>
                <div class="dc-value"><?= html_escape($lead['import_source_file'] ?? '—') ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Import Date</div>
                <div class="dc-value"><?= !empty($lead['import_date']) ? date('M d, Y', strtotime($lead['import_date'])) : '—' ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Preferred Contact</div>
                <div class="dc-value capital"><?= html_escape($lead['preferred_contact_method']) ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Best Time To Contact</div>
                <div class="dc-value"><?= html_escape($lead['best_time_to_contact']) ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Referred By</div>
                <div class="dc-value"><?= html_escape($lead['referred_by']) ?></div>
            </div>

            <div class="detail-cell">
                <div class="dc-label">Referral Type</div>
                <div class="dc-value capital"><?= html_escape($lead['referral_type']) ?></div>
            </div>
            
        </div>
        
        <!-- Audit Trail Section -->
        <div class="detail-grid">
            <div class="detail-cell">
                <div class="dc-label">Created At</div>
                <div class="dc-value"><?= !empty($lead['created_at']) ? date('M d, Y H:i', strtotime($lead['created_at'])) : '—' ?>
                <i class="ti ti-dots-vertical"></i>
                <?= user_profile_small($lead['created_by_name'] ?? '—') ?>
                </div>
            </div>

            <?php if (!empty($lead['updated_at'])): ?>
            <div class="detail-cell">
                <div class="dc-label">Last Updated</div>
                <div class="dc-value"><?= date('M d, Y H:i', strtotime($lead['updated_at'])) ?>
                <i class="ti ti-dots-vertical"></i>
                <?= user_profile_small($lead['updated_by_name'] ?? '—') ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($lead['verified_by']) || !empty($lead['verified_date'])): ?>
            <div class="detail-cell">
                <div class="dc-label">Verified At</div>
                <div class="dc-value"><?= date('M d, Y H:i', strtotime($lead['verified_date'])) ?>
                <i class="ti ti-dots-vertical"></i>
                <?= user_profile_small($lead['verified_by_name'] ?? '—') ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($lead['is_deleted']) && $lead['is_deleted'] == 1): ?>
            <div class="detail-cell">
                <div class="dc-label">Deleted At</div>
                <div class="dc-value"><div class="dc-value"><?= date('M d, Y H:i', strtotime($lead['deleted_at'])) ?>
                <i class="ti ti-dots-vertical"></i>
                <?= user_profile_small($lead['deleted_by'] ?? '—') ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

</div>