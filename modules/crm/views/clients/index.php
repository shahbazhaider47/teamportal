<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$canCreate    = staff_can('client_create', 'crm');
$table_id     = $table_id ?? 'dataTable';
$canEdit   = staff_can('client_edit', 'crm');
$canDelete = staff_can('client_delete', 'crm');
$canView   = staff_can('client_view', 'crm') || staff_can('view', 'crm');
?>
        
<div class="container-fluid">

<div class="crm-page-header mb-3">

  <!-- Icon + Title row (always visible) -->
  <div class="crm-page-header-main">
    <div class="crm-page-icon">
      <i class="fa-solid fa-users fa-fw"></i>
    </div>
    <div class="crm-page-title-wrap">
      <div class="crm-page-title"><?= $page_title ?></div>
      <div class="crm-page-sub">Manage all clients for the company</div>
    </div>
  </div>

  <!-- Actions row -->
  <div class="crm-page-actions">
    <?php if ($canCreate): ?>
      <a href="<?= site_url('crm/client_add') ?>" class="btn-add-new">
        <i class="ti ti-plus"></i>
        <span>Add Client</span>
      </a>
    <?php endif; ?>

    <div class="btn-divider"></div>

    <?php render_export_buttons(['filename' => $page_title ?? 'export']); ?>
  </div>

</div>
    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                        'exclude_columns' => ['Client Type', 'Billing Model', 'Contract Duration','Contact Info'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="crm-card">

    <div class="row g-2 mb-3">
    
        <div class="col">
          <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#16a34a18;">
              <i class="ti ti-stethoscope"></i>
            </div>
            <div>
              <div class="crm-kpi-value"><?= $kpi['direct_clients'] ?></div>
              <div class="crm-kpi-label">Direct Clients</div>
            </div>
          </div>
        </div>
        
        <div class="col">
          <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#ffc78e3d;">
              <i class="ti ti-building"></i>
            </div>
            <div>
              <div class="crm-kpi-value"><?= $kpi['group_clients'] ?></div>
              <div class="crm-kpi-label">Group Clients</div>
            </div>
          </div>
        </div>
        
        <div class="col">
          <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#0ea5e918;">
              <i class="ti ti-users"></i>
            </div>
            <div>
              <div class="crm-kpi-value"><?= $kpi['total_active'] ?></div>
              <div class="crm-kpi-label">Total Active</div>
            </div>
          </div>
        </div>
        
        <div class="col">
          <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#f59e0b18;">
              <i class="ti ti-user-off"></i>
            </div>
            <div>
              <div class="crm-kpi-value"><?= $kpi['total_inactive'] ?></div>
              <div class="crm-kpi-label">Total In-Active</div>
            </div>
          </div>
        </div>
        
        <div class="col">
          <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#ef444418;">
              <i class="ti ti-user-x"></i>
            </div>
            <div>
              <div class="crm-kpi-value"><?= $kpi['terminated'] ?></div>
              <div class="crm-kpi-label">Terminated</div>
            </div>
          </div>
        </div>
        
        <div class="col">
          <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#6366f118;">
              <i class="ti ti-calendar-event"></i>
            </div>
            <div>
              <div class="crm-kpi-value"><?= $kpi['contract_expiring'] ?></div>
              <div class="crm-kpi-label">Contract Expiring</div>
            </div>
          </div>
        </div>
    
    </div>
    <div class="app-divider-v dashed mb-3"></div>
    
            <div class="table-responsive crm-table">
                <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
                    <thead class="bg-light-primary">
                        <tr>
                            <th>Client Code</th>
                            <th>Practice Name</th>
                            <th>Client Type</th>
                            <th>Contact Person</th>
                            <th>Contact Info</th>
                            <th>Account Manager</th>
                            <th>City / State</th>
                            <th>Billing Model</th>
                            <th>Contract Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                
                    <tbody>
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $i => $row): ?>
                                <?php
                                $isGroup      = (int)($row['is_group'] ?? 0) === 1;
                                $groupName    = trim((string)($row['group_name'] ?? ''));
                                $status       = strtolower(trim((string)($row['client_status'] ?? '')));
                                $isInactive   = $status === 'inactive';
                                $isActive     = $status === 'active';
                                $isOnHold     = $status === 'on-hold';
                                $isTerminated = $status === 'terminated';
                                
                                $amName  = trim((string)($row['primary_contact_name'] ?? ''));
                                $amEmail = trim((string)($row['primary_email'] ?? ''));
                                $amPhone = trim((string)($row['primary_phone'] ?? ''));
                                
                                $start = trim((string)($row['contract_start_date'] ?? ''));
                                $end   = trim((string)($row['contract_end_date'] ?? ''));
                                ?>
                
                                <tr>
                                    <td><?= html_escape($row['client_code'] ?? ''); ?></td>
            
                                    <td class="small">
                                        <div class="fw-semibold mb-1">
                                            <?php if ($canView): ?>
                                                <a href="<?= site_url('crm/client_view/'.$row['id']); ?>"
                                                   class="text-primary"
                                                   target="_blank"
                                                   title="Open Practice Profile"
                                                   title="View Practice">
                                                    <?= html_escape($row['practice_legal_name'] ?? ''); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-dark">
                                                    <?= html_escape($row['practice_legal_name'] ?? ''); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="x-small text-muted">
                                            <i class="ti ti-stethoscope text-info me-1"></i>
                                            <?= html_escape($row['specialty'] ?? ''); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($isGroup): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ti ti-users text-primary" style="font-size:15px;"></i>
                                                <span class="fw-semibold text-dark">Group Client</span>
                                            </div>
                                            <?php if ($groupName !== ''): ?>
                                                <div class="text-muted mt-1" style="font-size:11.5px;"><?= html_escape($groupName); ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ti ti-building text-success" style="font-size:15px;"></i>
                                                <span class="fw-semibold text-dark">Direct Client</span>
                                            </div>
                                            <div class="text-muted mt-1" style="font-size:11.5px;">Company Client</div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="small">
                                        <div class="d-flex align-items-center gap-2">
                                    
                                            <div class="crm-user-avatar">
                                                <i class="ti ti-user"></i>
                                            </div>
                                    
                                            <div>
                                                <div>
                                                    <?= html_escape($amName !== '' ? $amName : '—'); ?>
                                                </div>
                                                <span class="x-small text-muted">
                                                    <?= html_escape($row['primary_contact_title'] ?? ''); ?>
                                                </span>
                                            </div>
                                    
                                        </div>
                                    </td>
                                    
                                    <td class="small">
                                        <?php if ($amEmail !== ''): ?>
                                            <div class="d-flex align-items-center gap-1 text-muted">
                                                <i class="ti ti-mail text-primary" style="font-size:14px;"></i>
                                                <span><?= html_escape($amEmail); ?></span>
                                            </div>
                                        <?php endif; ?>
                
                                        <?php if ($amPhone !== ''): ?>
                                            <div class="d-flex align-items-center gap-1 text-muted mt-1">
                                                <i class="ti ti-phone text-primary" style="font-size:14px;"></i>
                                                <span><?= html_escape($amPhone); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= user_profile_image($row['account_manager'] ?? ''); ?>
                                    </td>
                                    
                                    <td>
                                        <?= html_escape($row['city'] ?? ''); ?>
                                        <?php if (!empty($row['state'])): ?>
                                            <i class="ti ti-dots-vertical"></i>
                                            <?= html_escape($row['state']); ?>
                                            <?= html_escape($row['zip_code']); ?>
                                        <?php endif; ?>
                                    </td>
                
                                    <td class="small">

                                        <?php if (!empty($row['billing_model'])): ?>
                                            <div class="text-muted fw-semibold mt-1">
                                                <?= html_escape(ucfirst($row['billing_model'] ?? 'N/A')); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($row['rate_percent'])): ?>
                                            <div class="text-muted mt-1">
                                                <i class="ti ti-percentage text-primary me-1" style="font-size:13px;"></i>
                                                <?= html_escape($row['rate_percent']); ?>
                                            </div>
                                        <?php endif; ?>
                                    
                                        <?php if (!empty($row['rate_flat'])): ?>
                                            <div class="text-muted mt-1">
                                                <i class="ti ti-currency-dollar text-success me-1" style="font-size:13px;"></i>
                                                <?= html_escape($row['rate_flat']); ?>
                                            </div>
                                        <?php endif; ?>
                                    
                                        <?php if (!empty($row['rate_custom'])): ?>
                                            <div class="text-muted mt-1">
                                                <i class="ti ti-settings text-info me-1" style="font-size:13px;"></i>
                                                <?= html_escape($row['rate_custom']); ?>
                                            </div>
                                        <?php endif; ?>
                                    
                                    </td>
                
                                    <td class="small">
                                        <?php if ($start !== ''): ?>
                                            <div class="d-flex align-items-center gap-1 text-muted">
                                                <i class="ti ti-calendar-event text-success" style="font-size:13px;"></i>
                                                <span><strong>Start:</strong> <?= html_escape($start); ?></span>
                                            </div>
                                        <?php endif; ?>
                
                                        <?php if ($end !== ''): ?>
                                            <div class="d-flex align-items-center gap-1 text-muted mt-1">
                                                <i class="ti ti-calendar-off text-danger" style="font-size:13px;"></i>
                                                <span><strong>End:</strong> <?= html_escape($end); ?></span>
                                            </div>
                                        <?php endif; ?>
                
                                        <?php if ($start === '' && $end === ''): ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                
                                    <td>
                                        <?php if ($isInactive): ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php elseif ($isActive): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php elseif ($isOnHold): ?>
                                            <span class="badge badge-hold">On Hold</span>
                                        <?php elseif ($isTerminated): ?>
                                            <span class="badge badge-terminated">Terminated</span>
                                        <?php else: ?>
                                            <span class="pill pill-na"><?= html_escape(ucwords(str_replace('-', ' ', $status ?: 'unknown'))) ?></span>
                                        <?php endif; ?>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No clients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </div>  
    
</div>
