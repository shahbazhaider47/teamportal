<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $CI =& get_instance(); ?>
<?php
$leadId = (int)($lead['id'] ?? 0);
$isDeleted = ((int)($lead['is_deleted'] ?? 0) === 1);
$practiceName = trim($lead['practice_name'] ?? '');
$words = preg_split('/\s+/', $practiceName);
$initials = strtoupper(substr($words[0] ?? 'L', 0, 1));
if (isset($words[1])) {
    $initials .= strtoupper(substr($words[1], 0, 1));
}

$isVerified = (int)($lead['data_verified'] ?? 0) === 1;
$totalFiles   = count($files);
$isStale = crm_is_lead_stale($lead['last_contact_date'] ?? null);
?>

<div class="container-fluid">    

  <div class="crm-page-header mb-3">
    <div class="crm-page-icon me-3">
        <a href="<?= site_url('crm/leads') ?>" class="fs-semibold" title="Go back to clients list">
            <i class="ti ti-arrow-back-up me-2"></i>
        </a>
    </div>
    <div class="flex-grow-1">
      <div class="crm-page-title"><?= html_escape($practiceName ?: 'Unnamed Lead') ?></div>
      <div class="crm-page-sub">
          View all details related to this lead (Lead ID: <span class="idtext">#<?= $leadId ?></span> )
      </div>
    </div>
    
    <div class="ms-auto d-flex gap-2">
    
            <?php if (!$isDeleted && !empty($can['edit'])): ?>
                <button type="button" class="btn btn-light-primary btn-header" data-bs-toggle="modal" data-bs-target="#leadEditModal">
                    <i class="ti ti-edit"></i> Edit Lead
                </button>
            <?php endif; ?>
            
        <div class="btn-divider mt-1"></div>        

            <div class="dropdown">
                <button class="btn btn-light-primary btn-header dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                </button>
        
                <div class="dropdown-menu dropdown-menu-end">
                    <?php if (!$isDeleted && !empty($can['edit'])): ?>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#assignLeadModal">
                            <i class="ti ti-user-plus"></i> Assign Lead
                        </button>
        
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                            <i class="ti ti-arrows-exchange"></i> Change Status
                        </button>
        
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#updateForecastModal">
                            <i class="ti ti-chart-line"></i> Update Forecast
                        </button>

                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                            <i class="ti ti-mail"></i> Send Email
                        </button>

                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#sendPoposalModal">
                            <i class="ti ti-file-certificate"></i> Send Proposal
                        </button>
                        
                        <div class="dropdown-divider"></div>
                    <?php endif; ?>

                    <?php if ($isVerified): ?>
                        <form action="<?= site_url('crm/leads/unverify/' . $leadId) ?>" method="post" class="d-inline"
                              onsubmit="return confirm('Are you sure you want to mark this lead as unverified?');">
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="ti ti-shield-x text-danger"></i> Mark Unverify
                            </button>
                        </form>
                    <?php else: ?>
                        <form action="<?= site_url('crm/leads/verify/' . $leadId) ?>" method="post" class="d-inline"
                              onsubmit="return confirm('Mark this lead as verified?');">
                            <button type="submit" class="dropdown-item text-success">
                                <i class="ti ti-shield-check text-success"></i> Mark Verified
                            </button>
                        </form>
                    <?php endif; ?>
                
                    <?php if ($isDeleted): ?>
                        <?php if (!empty($can['delete'])): ?>
                            <form action="<?= site_url('crm/leads/restore/' . $leadId) ?>" method="post" class="m-0">
                                <button type="submit" class="dropdown-item text-success">
                                    <i class="ti ti-restore text-success"></i> Restore Lead
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!empty($can['delete'])): ?>
                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteLeadModal">
                                <i class="ti ti-trash text-danger"></i> Delete Lead
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            
    </div>

  
    <!-- Lead Hero Section -->
      <div class="crm-card">
        <div class="hero-inner">
            <div class="avatar"><?= html_escape($initials ?: 'L') ?></div>

            <div class="hero-info">
                <h2 class="hero-name">
                    <?= html_escape($practiceName ?: 'Unnamed Practice') ?>
                    <?php if ($isVerified): ?>
                        <span class="badge badge-active">Verified</span>
                    <?php else: ?>
                        <span class="badge badge-inactive">Unverified</span>
                    <?php endif; ?>
                </h2>

                <div class="badge-row">
                    <?php $statusMeta = crm_lead_status_meta($lead['lead_status'] ?? 'new'); ?>
                
                    <span
                        class="badge cursor-pointer"
                        style="background: <?= html_escape($statusMeta['bg']); ?>; color: <?= html_escape($statusMeta['color']); ?>;"
                        <?php if (!$isDeleted && !empty($can['edit'])): ?>
                            data-bs-toggle="modal"
                            data-bs-target="#changeStatusModal"
                            role="button"
                            data-bs-toggle="modal" data-bs-target="#changeStatusModal"
                            tabindex="0"
                        <?php endif; ?>
                    >
                        <i class="ti ti-tag"></i>
                        <?= html_escape($statusMeta['label']); ?>
                        <i class="ti ti-edit text-dark"></i>
                    </span>

                    <span class="badge bg-light-info capital">
                        <i class="ti ti-flame"></i>
                        <?= html_escape($lead['lead_quality'] ?? 'Cold') ?>
                    </span>

                    <span class="badge badge-pill">
                        <i class="ti ti-chart-line"></i>
                        <span class="text-light">Score:</span> <?= crm_calculate_lead_score($lead) ?>
                    </span>

                    <?php if (!empty($lead['practice_type'])): ?>
                    <span class="badge badge-pill">
                        <i class="ti ti-building-warehouse"></i>
                        <?= html_escape(ucwords(str_replace('-', ' ', (string)$lead['practice_type']))); ?>
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($lead['assigned_to_name'])): ?>
                        <span
                            class="badge badge-pill cursor-pointer"
                            <?php if (!$isDeleted && !empty($can['edit'])): ?>
                                data-bs-toggle="modal"
                                data-bs-target="#assignLeadModal"
                                role="button"
                                data-bs-toggle="modal" data-bs-target="#assignLeadModal"
                                tabindex="0"
                            <?php endif; ?>
                        >
                            <?= user_profile_small($lead['assigned_to_name']) ?>
                            <i class="ti ti-user-plus mb-1 text-success"></i>
                        </span>
                    <?php endif; ?>
                        
                </div>
            </div>
        </div>

        <!-- KPI Strip -->
        <div class="kpi-strip">
            <?php if (!empty($lead['monthly_collections'])): ?>
            <div class="kpi">
                <span class="kpi-label"><i class="ti ti-home-dollar text-success me-2 fs-6"></i> Monthly Collections</span>
                <span class="kpi-value text-success">$<?= number_format((float)$lead['monthly_collections'], 2) ?></span>
                <span class="kpi-sub">Estimated Revenue</span>
            </div>
            <?php endif; ?>

            <?php if (!empty($lead['patient_volume_per_month'])): ?>
            <div class="kpi">
                <span class="kpi-label"><i class="ti ti-stethoscope text-info me-2 fs-6"></i> Patient Volume</span>
                <span class="kpi-value text-info"><?= number_format((int)$lead['patient_volume_per_month']) ?></span>
                <span class="kpi-sub">Per Month</span>
            </div>
            <?php endif; ?>

            <?php if (!empty($lead['monthly_claim_volume'])): ?>
            <div class="kpi">
                <span class="kpi-label"><i class="ti ti-files text-danger me-2 fs-6"></i> Monthly Claims</span>
                <span class="kpi-value text-danger"><?= number_format((int)$lead['monthly_claim_volume']) ?></span>
                <span class="kpi-sub">Claim Volume</span>
            </div>
            <?php endif; ?>

            <?php if (!empty($lead['last_contact_date'])): ?>
            <div class="kpi">
                <span class="kpi-label"><i class="ti ti-phone-outgoing text-warning me-2 fs-6"></i> Last Contact</span>
                <span class="kpi-value text-warning"><?= date('M d, Y', strtotime($lead['last_contact_date'])) ?></span>
                <?php if ($isStale): ?>
                    <span class="kpi-sub">
                        <i class="ti ti-alert-triangle"></i> 
                        <?= crm_lead_stale_days($lead['last_contact_date']); ?> Days - Stale
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>


            <?php if (!empty($lead['lead_source'])): ?>
            <div class="kpi">
                <span class="kpi-label"><i class="ti ti-layers-linked text-primary me-2 fs-6"></i> Source</span>
                <span class="kpi-value text-primary"><?= render_lead_source($lead['lead_source'] ?? null); ?></span>
                <span class="kpi-sub">Lead Origin</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Body -->
        <div class="body mt-3">
            <div class="sidebar">
                <!-- Contact Info Card -->
                <div class="audit-section">
                    <div class="audit-section-header">
                        <span><i class="ti ti-id-badge"></i>Contact Info</span>
                        <?php if (!$isDeleted && !empty($can['edit'])): ?>
                        <button type="button" class="btn-icon bg-light-primary" data-bs-toggle="modal" data-bs-target="#editLeadContactModal">
                            <i class="ti ti-pencil-plus"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (!empty($lead['contact_person'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-users"></i></div>
                            <span class="audit-label">Contact<br>
                            <span class="text-muted capital"><?= html_escape($lead['contact_person']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
            
                        <?php if (!empty($lead['contact_email'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-mail"></i></div>
                            <span class="audit-label">Email<br>
                            <span class="text-muted capital"><?= html_escape($lead['contact_email']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
            
                        <?php if (!empty($lead['contact_phone'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-phone"></i></div>
                            <span class="audit-label">Phone<br>
                            <span class="text-muted"><?= html_escape($lead['contact_phone']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
            
                        <?php if (!empty($lead['alternate_phone'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-device-landline-phone"></i></div>
                            <span class="audit-label">Alternate<br>
                            <span class="text-muted"><?= html_escape($lead['alternate_phone']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
            
                        <?php if (!empty($lead['website'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-file-phone"></i></div>
                            <span class="audit-label">Preferred Contact Method<br>
                            <span class="text-muted capital"><?= html_escape($lead['preferred_contact_method']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($lead['website'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-clock"></i></div>
                            <span class="audit-label">Best Time to Contact<br>
                            <span class="text-muted capital"><?= html_escape($lead['best_time_to_contact']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($lead['website'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-world"></i></div>
                            <span class="audit-label">Website<br>
                            <span class="text-muted capital"><?= html_escape($lead['website']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($lead['address']) || !empty($lead['city'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-map-pin"></i></div>
                            <span class="audit-label">Address<br>
                            <span class="text-muted capital">
                                <?php if (!empty($lead['address'])): ?>
                                    <?= html_escape($lead['address']) ?><br>
                                <?php endif; ?>
                                <?= html_escape(trim($lead['city'] . ', ' . $lead['state'] . ' ' . $lead['zip_code'], ', ')) ?>
                                <?php if (!empty($lead['country'])): ?>
                                    <br><?= html_escape($lead['country']) ?>
                                <?php endif; ?>
                            </span>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            
                <!-- Assignment Card -->
                <div class="audit-section">
                    <div class="audit-section-header">
                        <span><i class="ti ti-users"></i>Assignment</span>
                        <?php if (!$isDeleted && !empty($can['edit'])): ?>
                        <button type="button" class="btn-icon bg-light-primary" data-bs-toggle="modal" data-bs-target="#assignLeadModal">
                            <i class="ti ti-pencil-plus"></i>
                        </button>
                        <?php endif; ?>
                    
                    </div>
                    <div>
                        <?php if (!empty($lead['assigned_to_name'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-user-circle"></i></div>
                            <span class="audit-label">Assigned To<br>
                            <span class="text-muted capital"><?= html_escape($lead['assigned_to_name']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($lead['assigned_by_name'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-user-check"></i></div>
                            <span class="audit-label">Assigned By<br>
                            <span class="text-muted capital"><?= html_escape($lead['assigned_by_name']) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
            
                        <?php if (!empty($lead['assigned_at'])): ?>
                        <div class="audit-row">
                            <div class="audit-icon"><i class="ti ti-calendar-time"></i></div>
                            <span class="audit-label">Assigned At<br>
                            <span class="text-muted capital"><?= date('M d, Y', strtotime($lead['assigned_at'])) ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            
            
                <!-- Internal Notes -->
                <?php if (!empty($lead['internal_notes'])): ?>
                <div class="audit-section">
                    <div class="audit-section-header">
                        <span><i class="ti ti-note"></i>Internal Notes</span>
                    </div>
                    <div class="internal-notes">
                        <?= nl2br(html_escape($lead['internal_notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>
        
            </div>
        
                <!-- Main Content Area -->
                <div class="main">
                    <?php $CI =& get_instance(); ?>
                
                    <div class="crm-profile-wrap mt-4">
                        <ul class="crm-tab-nav" id="leadTabs" role="tablist">
                
                            <li class="crm-tab-item" role="presentation">
                                <button class="crm-tab-btn active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-pane"
                                        type="button" role="tab" aria-controls="details-pane" aria-selected="true">
                                    <i class="ti ti-info-circle"></i>
                                    Lead Details
                                </button>
                            </li>
                
                            <li class="crm-tab-item" role="presentation">
                                <button class="crm-tab-btn" id="needs-tab" data-bs-toggle="tab" data-bs-target="#needs-pane"
                                        type="button" role="tab" aria-controls="needs-pane" aria-selected="false">
                                    <i class="ti ti-checklist"></i>
                                    Needs &amp; Criteria
                                </button>
                            </li>
                
                            <li class="crm-tab-item" role="presentation">
                                <button class="crm-tab-btn" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-pane"
                                        type="button" role="tab" aria-controls="activity-pane" aria-selected="false">
                                    <i class="ti ti-activity"></i>
                                    Activity
                                </button>
                            </li>
                
                            <li class="crm-tab-item" role="presentation">
                                <button class="crm-tab-btn" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-pane"
                                        type="button" role="tab" aria-controls="tasks-pane" aria-selected="false">
                                    <i class="ti ti-checkbox"></i>
                                    Tasks
                                    <span class="crm-tab-badge">12</span>
                                </button>
                            </li>
                
                            <li class="crm-tab-item" role="presentation">
                                <button class="crm-tab-btn" id="proposals-tab" data-bs-toggle="tab" data-bs-target="#proposals-pane"
                                        type="button" role="tab" aria-controls="proposals-pane" aria-selected="false">
                                    <i class="ti ti-file-invoice"></i>
                                    Proposals
                                    <span class="crm-tab-badge"><?= count($proposals ?? []) ?></span>
                                </button>
                            </li>
                
                            <li class="crm-tab-item" role="presentation">
                                <button class="crm-tab-btn" id="files-tab" data-bs-toggle="tab" data-bs-target="#files-pane"
                                        type="button" role="tab" aria-controls="files-pane" aria-selected="false">
                                    <i class="fa-solid fa-paperclip"></i>
                                    Files
                                    <span class="crm-tab-badge"><?= (int)($totalFiles ?? 0) ?></span>
                                </button>
                            </li>
                
                        </ul>
                
                        <div class="tab-content crm-tab-content" id="leadTabsContent">
                
                            <div class="tab-pane fade show active" id="details-pane" role="tabpanel"
                                 aria-labelledby="details-tab" tabindex="0">
                                <?= $CI->load->view('leads/tabs/lead_details', ['lead' => $lead], true); ?>
                            </div>
                
                            <div class="tab-pane fade" id="needs-pane" role="tabpanel"
                                 aria-labelledby="needs-tab" tabindex="0">
                                <?= $CI->load->view('leads/tabs/lead_needs', ['lead' => $lead], true); ?>
                            </div>
                
                            <div class="tab-pane fade" id="activity-pane" role="tabpanel"
                                 aria-labelledby="activity-tab" tabindex="0">
                                <?= $CI->load->view('leads/tabs/lead_activity', [
                                    'lead'       => $lead,
                                    'activities' => $activities ?? []
                                ], true); ?>
                            </div>
                
                            <div class="tab-pane fade" id="tasks-pane" role="tabpanel"
                                 aria-labelledby="tasks-tab" tabindex="0">
                                <?= $CI->load->view('leads/tabs/lead_tasks', ['lead' => $lead], true); ?>
                            </div>
                
                            <div class="tab-pane fade" id="proposals-pane" role="tabpanel"
                                 aria-labelledby="proposals-tab" tabindex="0">
                                <?= $CI->load->view('leads/tabs/lead_proposals', [
                                    'lead'      => $lead,
                                    'proposals' => $proposals ?? [],
                                    'can'       => $can ?? [],
                                ], true); ?>
                            </div>
                
                            <div class="tab-pane fade" id="files-pane" role="tabpanel"
                                 aria-labelledby="files-tab" tabindex="0">
                                <?= $CI->load->view('leads/tabs/lead_files', [
                                    'lead'  => $lead,
                                    'files' => $files ?? [],
                                    'can'   => $can ?? [],
                                ], true); ?>
                            </div>
                
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>


<?= $CI->load->view('leads/modals/assign_lead_modal', ['lead' => $lead], true); ?>
<?= $CI->load->view('leads/modals/change_status_modal', ['lead' => $lead], true); ?>
<?= $CI->load->view('leads/modals/update_forecast_modal', ['lead' => $lead], true); ?>
<?= $CI->load->view('leads/modals/delete_lead_modal', ['lead' => $lead], true); ?>
<?= $CI->load->view('leads/modals/edit_lead_modal', ['lead' => $lead], true); ?>
<?= $CI->load->view('leads/modals/edit_lead_needs_modal', ['lead' => $lead], true); ?>
<?= $CI->load->view('leads/modals/edit_lead_contact_modal', ['lead' => $lead], true); ?>
<?= $CI->load->view('leads/modals/upload_files_modal', ['lead' => $lead,], true); ?>
<?= $CI->load->view('leads/modals/send_email_modal', ['lead' => $lead], true); ?>
<?= $CI->load->view('leads/modals/send_proposal_modal', ['lead' => $lead], true); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusSelect = document.getElementById('leadStatusSelect');
    const lossReasonWrap = document.getElementById('lossReasonWrap');

    function toggleLossReason() {
        if (!statusSelect || !lossReasonWrap) return;

        const val = statusSelect.value;
        lossReasonWrap.style.display = (val === 'lost' || val === 'disqualified') ? 'block' : 'none';
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', toggleLossReason);
        toggleLossReason();
    }
});
</script>