<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$canCreate = !empty($can['create']);
$canEdit   = !empty($can['edit']);
$canDelete = !empty($can['delete']);
$canView   = !empty($can['view']);
$table_id     = $table_id ?? 'dataTable';
?>

<div class="container-fluid">

    <div class="crm-page-header mb-3">
        <div class="crm-page-icon me-3">
            <i class="ti ti-users"></i>
        </div>

        <div class="flex-grow-1">
            <div class="crm-page-title"><?= html_escape($page_title ?? 'Client Groups') ?></div>
            <div class="crm-page-sub">Manage all leads for the company</div>
        </div>

        <div class="ms-auto d-flex gap-2">
          <?php if ($canCreate): ?>
            <button type="button" class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#leadCreateModal">
              <i class="ti ti-plus"></i> New Lead
            </button>
    
            <button type="button" class="btn btn-light-primary btn-header" data-bs-toggle="modal" data-bs-target="#leadImportModal">
              <i class="ti ti-upload"></i> Import
            </button>
          <?php endif; ?>

            <div class="btn-divider mt-1"></div>

            <?php render_export_buttons([
                'filename' => $page_title ?? 'export'
            ]); ?>
        </div>
    </div>
    
    
    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                        'exclude_columns' => ['#','Client Type', 'Account Manager', 'Billing Model', 'Actions', 'Contract Duration'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>


    <div class="crm-card">

<div class="row g-2 mb-3">

    <!-- Total Leads -->
    <div class="col">
        <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#eef2ff;">
                <i class="ti ti-database text-secondary"></i>
            </div>
            <div>
                <div class="crm-kpi-value"><?= (int)($lead_kpi['total_leads'] ?? 0); ?></div>
                <div class="crm-kpi-label">Total Leads</div>
            </div>
        </div>
    </div>

    <!-- New Leads -->
    <div class="col">
        <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#ecfeff;">
                <i class="ti ti-user-plus text-info"></i>
            </div>
            <div>
                <div class="crm-kpi-value"><?= (int)($lead_kpi['new_leads'] ?? 0); ?></div>
                <div class="crm-kpi-label">New Leads</div>
            </div>
        </div>
    </div>

    <!-- Qualified -->
    <div class="col">
        <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#f0fdf4;">
                <i class="ti ti-briefcase text-success"></i>
            </div>
            <div>
                <div class="crm-kpi-value"><?= (int)($lead_kpi['qualified'] ?? 0); ?></div>
                <div class="crm-kpi-label">Qualified</div>
            </div>
        </div>
    </div>

    <!-- Pipeline Value -->
    <div class="col">
        <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#fefce8;">
                <i class="ti ti-chart-bar text-warning"></i>
            </div>
            <div>
                <div class="crm-kpi-value">
                    $<?= number_format((float)($lead_kpi['pipeline_value'] ?? 0), 0); ?>
                </div>
                <div class="crm-kpi-label">Pipeline Value</div>
            </div>
        </div>
    </div>

    <!-- Won -->
    <div class="col">
        <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#ecfdf5;">
                <i class="ti ti-trophy text-success"></i>
            </div>
            <div>
                <div class="crm-kpi-value"><?= (int)($lead_kpi['won'] ?? 0); ?></div>
                <div class="crm-kpi-label">Won Deals</div>
            </div>
        </div>
    </div>

    <!-- Lost -->
    <div class="col">
        <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:#fef2f2;">
                <i class="ti ti-x text-danger"></i>
            </div>
            <div>
                <div class="crm-kpi-value"><?= (int)($lead_kpi['lost'] ?? 0); ?></div>
                <div class="crm-kpi-label">Lost</div>
            </div>
        </div>
    </div>

</div>
    
    <div class="app-divider-v dashed mb-3"></div>
    
              <?php if (empty($leads) || !is_array($leads)): ?>
        
                <div class="p-4 text-center text-muted">
                  <i class="ti ti-info-circle me-1"></i>
                  No leads found.
                </div>
        
              <?php else: ?>
              
            <div class="table-responsive crm-table">
                <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
                    <thead class="bg-light-primary">
                      <tr>
                        <th>Lead Title</th>
                        <th>Contact Person</th>
                        <th>Contact Email</th>
                        <th>Contact Phone</th>
                        <th>Address</th>                        
                        <th>Lead Source </th>
                        <th>Lead Status</th>
                        <th>Assigned To</th>
                        <th>Next Follow Up</th>
                      </tr>
                    </thead>
                
                    <tbody>
        
                      <?php foreach ($leads as $l): ?>
                        <?php
                          $leadId       = (int)($l['id'] ?? 0);
                          $nextFollow   = trim((string)($l['next_followup_date'] ?? ''));
                          $statusMeta = crm_lead_status_meta($l['lead_status'] ?? 'new');
                        ?>
        
                        <tr>
        
                            <td>
                              <div class="fw-semibold">
                            
                                <?php if ($canView): ?>
                                  <a href="<?= site_url('crm/leads/view/' . $leadId); ?>" 
                                     class="text-primary"
                                     target="_blank">
                                    <?= html_escape($l['practice_name'] ?? '—'); ?>
                                    <i class="ti ti-external-link text-primary"></i>
                                  </a>
                                <?php else: ?>
                                  <?= html_escape($l['practice_name'] ?? '—'); ?>
                                <?php endif; ?>
                            
                              </div>
                            </td>
        
                          <td><?= html_escape($l['contact_person'] ?? '—'); ?></td>

                          <td>
                            <i class="ti ti-mail text-primary"></i> <?= html_escape($l['contact_email'] ?? '—'); ?>
                          </td>

                          <td>
                            <i class="ti ti-phone text-primary"></i> <?= html_escape($l['contact_phone'] ?? '—'); ?>
                          </td>
                            
                            <td>
                            <i class="ti ti-map-pin text-danger"></i>
                            <?= html_escape($l['city'] ?? ''); ?>
                                    <?php if (!empty($l['state'])): ?>
                                    <i class="ti ti-dots-vertical"></i>
                                    <?= html_escape($l['state']); ?>
                                    <?= html_escape($l['zip_code']); ?>
                                <?php endif; ?>
                            </td>    
                            
                            
                          <td>
                            <?= render_lead_source($l['lead_source'] ?? null); ?>
                          </td>
                          <td>
                            <span class="badge" style="background: <?= html_escape($statusMeta['bg']); ?>; color: <?= html_escape($statusMeta['color']); ?>;">
                                <i class="ti ti-tag"></i>
                                <?= html_escape($statusMeta['label']); ?>                              
                            </span>
                          </td>
                          
                          <td>
                            <div class="fw-semibold"><?= user_profile_small($l['assigned_to_name'] ?? '—'); ?></div>
                          </td>
        
                          <td>
                            <?php if ($nextFollow !== ''): ?>
                              <div class=""><?= html_escape($nextFollow); ?></div>
                            <?php else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                          </td>
                          
                        </tr>
                      <?php endforeach; ?>
        
                    </tbody>
                </table>

                <?php endif; ?>
      
            </div>
    </div> 

</div>

<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('leads/modals/add_new_lead', [], true); ?>
<?php echo $CI->load->view('leads/modals/import_leads', [], true); ?>