<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $CI =& get_instance(); ?>
<?php
$clientId = (int)($client['id'] ?? 201);
$practiceName = trim($client['practice_name']);
$words = preg_split('/\s+/', $practiceName);
$initials = strtoupper(substr($words[0] ?? 'C', 0, 1));
if (isset($words[1])) {
    $initials .= strtoupper(substr($words[1], 0, 1));
}

$status       = strtolower(trim((string)($client['client_status'] ?? 'inactive')));
$recordActive = (int)($client['is_active'] ?? 1) === 1;

$isInactive   = $status === 'inactive';
$isActive     = $status === 'active';
$isOnHold     = $status === 'on-hold';
$isTerminated = $status === 'terminated';
$isGroup      = !empty($client['is_group']);
?>
<div class="container-fluid">
    
  <div class="crm-page-header mb-3">
    <div class="crm-page-icon me-3">
        <a href="<?= site_url('crm/clients') ?>" class="fs-semibold" title="Go back to clients list">
            <i class="ti ti-arrow-back-up me-2"></i>
        </a>
    </div>
    <div class="flex-grow-1">
      <div class="crm-page-title"><?= html_escape($client['practice_name'])?></div>
      <div class="crm-page-sub">This Client was onboarded by 
      <?php if ($isGroup): ?>
      <span class="fw-semibold"><?= html_escape($client['group_name'] ?? '_') ?></span>
      <?php else: ?>
      <span class="fw-semibold"><?= get_company_name() ?></span>
      <?php endif; ?>
      </div>
    </div>
    
    <div class="ms-auto d-flex gap-2">
    
        <a href="<?= site_url('crm/client_edit/' . (int)$clientId) ?>" class="btn btn-light-primary btn-header">
            <i class="ti ti-edit"></i>
            <span>Edit</span>
        </a>

        <div class="btn-divider mt-1"></div>        

        <div class="dropdown">
            <button class="btn btn-light-primary btn-header dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-dots-vertical"></i>
            </button>

            <div class="dropdown-menu dropdown-menu-end app-page-header__menu">

                <a class="dropdown-item" href="<?= site_url('crm/reports/client/' . (int)$clientId) ?>">
                    <i class="ti ti-report-analytics"></i> Generate Report
                </a>

                <a class="dropdown-item" href="<?= site_url('crm/client_email/' . (int)$clientId) ?>">
                    <i class="ti ti-mail"></i> Send Email
                </a>

                <a class="dropdown-item" href="<?= site_url('crm/client_export_pdf/' . (int)$clientId) ?>">
                    <i class="ti ti-download"></i> Export PDF
                </a>

                <div class="dropdown-divider"></div>

                <button type="button"
                        class="dropdown-item text-danger"
                        onclick="deleteClient(<?= (int)$clientId ?>)">
                    <i class="ti ti-trash"></i> Delete Client
                </button>
            </div>
        </div>
            
    </div>
  </div>
  
  <!-- Hero -->
  <div class="crm-card">
    <div class="hero-inner">
      <div class="avatar"><?= html_escape($initials) ?></div>

      <div class="hero-info">
        <h2 class="hero-name">
          <?= html_escape($client['practice_name'] ?? $client['practice_legal_name'] ?? 'Client') ?>

            <?php if (!$recordActive): ?>
              <span class="badge badge-archived">Archived</span>
            <?php elseif ($isActive): ?>
              <span class="badge badge-active">Active</span>
            <?php elseif ($isOnHold): ?>
              <span class="badge badge-hold">On Hold</span>
            <?php elseif ($isTerminated): ?>
              <span class="badge badge-terminated">Terminated</span>
            <?php elseif ($isInactive): ?>
              <span class="badge badge-inactive">Inactive</span>
            <?php else: ?>
              <span class="pill pill-secondary"><?= html_escape(ucwords(str_replace('-', ' ', $status))) ?></span>
            <?php endif; ?>
                                        
        </h2>

        <div class="badge-row">
          <span class="badge badge-code"><?= html_escape($client['client_code']) ?></span>

          <?php if ($isGroup): ?>
            <span class="badge badge-type">
              <i class="ti ti-route" style="font-size:11px"></i> Group Client
            </span>
          <?php else: ?>
            <span class="badge badge-type">
              <i class="ti ti-route-off" style="font-size:11px"></i> Direct Client
            </span>
          <?php endif; ?>

          <?php if ($isGroup && !empty($client['group_name']) && !empty($client['client_group_id'])): ?>
          <span class="badge badge-pill">
            <i class="ti ti-users text-info"></i>
            <?= html_escape($client['group_name'] ?? '_') ?>            
            <div class="kpi-sub">
              <a href="<?= site_url('crm/group_view/' . (int)$client['client_group_id']) ?>"
                 target="_blank">
                <i class="ti ti-external-link text-primary"></i>
              </a>
            </div>
          </span>
          <?php endif; ?>
          
          <span class="badge badge-pill">
            <i class="ti ti-map-pin" style="color:#dc2626"></i>
            <?= html_escape($client['city'] . ', ' . $client['state']) ?>
          </span>
        </div>
      </div>

    </div>

        <!-- KPI Strip -->
        <div class="kpi-strip">
        
          <div class="kpi">
            <span class="kpi-label">Total Invoices</span>
            <span class="kpi-value">154</span>
            <span class="kpi-sub">All time</span>
          </div>
        
          <div class="kpi">
            <span class="kpi-label">Total Billed</span>
            <span class="kpi-value">$185,540</span>
            <span class="kpi-sub">Lifetime revenue</span>
          </div>
        
          <div class="kpi">
            <span class="kpi-label">Total Paid</span>
            <span class="kpi-value kpi-value-success">$156,154</span>
            <span class="kpi-sub">Collected</span>
          </div>
        
          <div class="kpi">
            <span class="kpi-label">Outstanding</span>
            <span class="kpi-value kpi-value-warning">$29,386</span>
            <span class="kpi-sub">Pending payments</span>
          </div>
        
          <div class="kpi">
            <span class="kpi-label">Avg Monthly Collections</span>
            <span class="kpi-value">$13,845</span>
            <span class="kpi-sub">Estimated</span>
          </div>
        
          <div class="kpi">
            <span class="kpi-label">Locations</span>
            <span class="kpi-value">5</span>
            <span class="kpi-sub">Practice sites</span>
          </div>
        
        </div>
    
    
        <div class="col-md-12">
            <div class="crm-profile-wrap mt-4">
                <ul class="crm-tab-nav" id="clientProfileTabs" role="tablist">
        
                    <li class="crm-tab-item" role="presentation">
                        <button class="crm-tab-btn active"
                                id="client-details-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#client-details-pane"
                                type="button"
                                role="tab"
                                aria-controls="client-details-pane"
                                aria-selected="true">
                            <i class="ti ti-list-details"></i>
                            Client Details
                        </button>
                    </li>
        
                    <li class="crm-tab-item" role="presentation">
                        <button class="crm-tab-btn"
                                id="client-invoices-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#client-invoices-pane"
                                type="button"
                                role="tab"
                                aria-controls="client-invoices-pane"
                                aria-selected="false">
                            <i class="ti ti-file-invoice"></i>
                            Invoices
                            <span class="crm-tab-badge">3</span>
                        </button>
                    </li>
        
                    <li class="crm-tab-item" role="presentation">
                        <button class="crm-tab-btn"
                                id="client-notes-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#client-notes-pane"
                                type="button"
                                role="tab"
                                aria-controls="client-notes-pane"
                                aria-selected="false">
                            <i class="ti ti-notes"></i>
                            Notes
                            <span class="crm-tab-badge"><?= isset($notes) && is_array($notes) ? count($notes) : 0 ?></span>
                        </button>
                    </li>
        
                </ul>
        
                <div class="tab-content crm-tab-content" id="clientProfileTabsContent">
        
                    <div class="tab-pane fade show active"
                         id="client-details-pane"
                         role="tabpanel"
                         aria-labelledby="client-details-tab"
                         tabindex="0">
                        <?= $CI->load->view('clients/tabs/client_details', ['client' => $client], true); ?>
                    </div>
        
                    <div class="tab-pane fade"
                         id="client-invoices-pane"
                         role="tabpanel"
                         aria-labelledby="client-invoices-tab"
                         tabindex="0">
                    </div>
        
                    <div class="tab-pane fade"
                         id="client-notes-pane"
                         role="tabpanel"
                         aria-labelledby="client-notes-tab"
                         tabindex="0">
                        <?= $CI->load->view('clients/tabs/client_notes', [
                            'client' => $client,
                            'notes'  => $notes ?? [],
                        ], true); ?>
                    </div>
        
                </div>
            </div>
        </div>
    
  </div>
  
</div>


<style>

 
</style>