<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$val = function ($v, $fallback = '—') {
    $v = is_string($v) ? trim($v) : $v;
    return ($v !== '' && $v !== null) ? $v : $fallback;
};

?>

<div class="row g-3">

    <!-- Identity & Contact -->
    <div class="col-lg-4">
        <div class="audit-section h-100">
            <div class="audit-section-header">
                <span><i class="ti ti-building"></i> Identity &amp; Contact</span>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-users"></i></div>
                <div><div class="audit-label">Group Name</div>
                <div class="audit-value"><?= html_escape($groupName) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-building"></i></div>
                <div><div class="audit-label">Company Name</div>
                <div class="audit-value"><?= html_escape($companyName) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-id-badge"></i></div>
                <div><div class="audit-label">Tax ID</div>
                <div class="audit-value"><?= html_escape($group['tax_id']) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-user"></i></div>
                <div><div class="audit-label">Contact Person</div>
                <div class="audit-value"><?= html_escape($val($group['contact_person'] ?? null)) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-mail"></i></div>
                <div><div class="audit-label">Contact Email</div>
                <div class="audit-value">
                    <?php if (!empty($group['contact_email'])): ?>
                        <a href="mailto:<?= html_escape($group['contact_email']) ?>" style="color:#056464;">
                            <?= html_escape($group['contact_email']) ?>
                        </a>
                    <?php else: ?>—<?php endif; ?>
                </div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-phone"></i></div>
                <div><div class="audit-label">Contact Phone</div>
                <div class="audit-value"><?= html_escape($val($group['contact_phone'] ?? null)) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-phone-call"></i></div>
                <div><div class="audit-label">Alternate Phone</div>
                <div class="audit-value"><?= html_escape($group['contact_alt_phone']) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-device-landline-phone"></i></div>
                <div><div class="audit-label">Fax</div>
                <div class="audit-value"><?= html_escape($group['fax_number']) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-world"></i></div>
                <div><div class="audit-label">Website</div>
                <div class="audit-value">
                    <a href="<?= prep_url($group['website']) ?>" target="_blank" style="color:#056464;">
                        <?= html_escape($group['website']) ?>
                    </a>
                </div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-map-pin"></i></div>
                <div><div class="audit-label">Address</div>
                <div class="audit-value"><?= html_escape($fullAddress) ?></div></div>
            </div>
        </div>
    </div>

    <!-- Contract & Billing -->
    <div class="col-lg-4">
        <div class="audit-section h-100">
            <div class="audit-section-header">
                <span><i class="ti ti-file-description"></i> Contract &amp; Billing</span>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-event"></i></div>
                <div><div class="audit-label">Contract Start</div>
                <div class="audit-value"><?= html_escape(crm_date($group['contract_date'] ?? null)) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-off"></i></div>
                <div><div class="audit-label">Contract End</div>
                <div class="audit-value">
                    <?php
                    if (!empty($group['contract_end'])) {
                        $exp  = new DateTime($group['contract_end']);
                        $now  = new DateTime();
                        $diff = (int)$now->diff($exp)->days;
                        $past = $exp < $now;
                        if ($past) {
                            echo '<span class="audit-value-danger">' . crm_date($group['contract_end']) . ' (expired)</span>';
                        } elseif ($diff <= 30) {
                            echo '<span class="kpi-value-warning" style="font-size:12.5px;">' . crm_date($group['contract_end']) . ' (' . $diff . 'd left)</span>';
                        } else {
                            echo '<span class="audit-value-success">' . crm_date($group['contract_end']) . '</span>';
                        }
                    } else { echo '—'; }
                    ?>
                </div></div>
            </div>
            
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-refresh"></i></div>
                <div><div class="audit-label">Auto Renew</div>
                <div class="audit-value">
                    <?= !empty($group['auto_renew'])
                        ? '<span class="audit-value-success">Yes</span>'
                        : '<span class="text-muted">No</span>' ?>
                </div></div>
            </div>
            
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-up"></i></div>
                <div><div class="audit-label">Next Renewal</div>
                <div class="audit-value">
                    <?= !empty($group['next_renew']) ? html_escape(crm_date($group['next_renew'])) : '—' ?>
                </div></div>
            </div>
            
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-check"></i></div>
                <div><div class="audit-label">Last Renewal</div>
                <div class="audit-value">
                    <?= !empty($group['last_renew']) ? html_escape(crm_date($group['last_renew'])) : '—' ?>
                </div></div>
            </div>
            
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-paperclip"></i></div>
                <div><div class="audit-label">Contract File</div>
                <div class="audit-value">
                    <a href="<?= html_escape($group['contract_file']) ?>" target="_blank" style="color:#056464;">
                        <i class="ti ti-download me-1"></i>Download
                    </a>
                </div></div>
            </div>
            
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-file-invoice"></i></div>
                <div><div class="audit-label">Invoice Mode</div>
                <div class="audit-value"><?= html_escape(ucfirst($val($group['invoice_mode'] ?? null))) ?></div></div>
            </div>
            
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-credit-card"></i></div>
                <div><div class="audit-label">Payment Terms</div>
                <div class="audit-value"><?= html_escape($val($group['payment_terms'] ?? null)) ?></div></div>
            </div>
            
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-mail-forward"></i></div>
                <div><div class="audit-label">Billing Email</div>
                <div class="audit-value">
                    <a href="mailto:<?= html_escape($group['billing_email']) ?>" style="color:#056464;">
                        <?= html_escape($group['billing_email']) ?>
                    </a>
                </div></div>
            </div>

        </div>
    </div>

    <!-- Operational -->
    <div class="col-lg-4">
        <div class="audit-section">
            <div class="audit-section-header">
                <span><i class="ti ti-settings"></i> Operational</span>
            </div>

            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-info-circle"></i></div>
                <div><div class="audit-label">Status Reason</div>
                <div class="audit-value audit-value-danger"><?= html_escape($group['status_reason']) ?></div></div>
            </div>
            
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-user-x"></i></div>
                <div><div class="audit-label">Churned At</div>
                <div class="audit-value audit-value-danger"><?= html_escape(crm_date($group['churned_at'], true)) ?></div></div>
            </div>

            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-notes"></i></div>
                <div><div class="audit-label">Internal Notes</div>
                <div class="audit-value" style="white-space:pre-wrap;"><?= html_escape($group['notes']) ?></div></div>
            </div>
        </div>

        <div class="audit-section">
            <div class="audit-section-header">
                <span><i class="ti ti-clock-record"></i> Audit Trail</span>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-user"></i></div>
                <div><div class="audit-label">Created By</div>
                <div class="audit-value"><?= user_profile_small($val($group['created_by_name'] ?? null)) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-calendar-plus"></i></div>
                <div><div class="audit-label">Created At</div>
                <div class="audit-value"><?= html_escape(crm_date($group['created_at'] ?? null, true)) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-edit"></i></div>
                <div><div class="audit-label">Last Updated</div>
                <div class="audit-value"><?= html_escape(crm_date($group['updated_at'] ?? null, true)) ?></div></div>
            </div>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-user-edit"></i></div>
                <div><div class="audit-label">Updated By</div>
                <div class="audit-value"><?= user_profile_small($group['updated_by_name']) ?></div></div>
            </div>

            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-trash" style="color:#dc2626;"></i></div>
                <div><div class="audit-label">Deleted At</div>
                <div class="audit-value audit-value-danger"><?= html_escape(crm_date($group['deleted_at'], true)) ?></div></div>
            </div>

            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-user" style="color:#dc2626;"></i></div>
                <div><div class="audit-label">Deleted By</div>
                <div class="audit-value"><?= user_profile_small($group['deleted_by_name']) ?></div></div>
            </div>

        </div>
        
    </div>

</div>