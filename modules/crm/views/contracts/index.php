<?php defined('BASEPATH') or exit('No direct script access allowed'); 
$table_id     = $table_id ?? 'dataTable';
?>

<div class="container-fluid">

  <div class="crm-page-header mb-3">
    <div class="crm-page-icon me-3"><i class="ti ti-file-text"></i></div>
    <div class="flex-grow-1">
      <div class="crm-page-title"><?= $page_title ?></div>
      <div class="crm-page-sub">Manage all client contracts and service agreements</div>
    </div>
    <div class="ms-auto d-flex gap-2">
      <?php if (!empty($can['create'])): ?>
        <a href="<?= site_url('crm/contracts/create') ?>" class="btn btn-primary btn-header">
          <i class="ti ti-plus me-1"></i> New Contract
        </a>
    
    <div class="btn-divider mt-1"></div>        
    
      <?php endif; ?>
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
                        'exclude_columns' => ['#'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    

  <div class="crm-card">
    
      <?php
        $kpiMap = [
          'active'            => ['label' => 'Active',           'icon' => 'ti-circle-check',  'color' => '#16a34a'],
          'draft'             => ['label' => 'Draft',            'icon' => 'ti-pencil',         'color' => '#64748b'],
          'pending_signature' => ['label' => 'Pending Sign',     'icon' => 'ti-clock',          'color' => '#d97706'],
          'expired'           => ['label' => 'Expired',          'icon' => 'ti-clock-cancel',   'color' => '#dc2626'],
          'terminated'        => ['label' => 'Terminated',       'icon' => 'ti-ban',            'color' => '#b91c1c'],
        ];
      ?>
      <div class="row g-2 mb-3">
        <?php foreach ($kpiMap as $key => $info): ?>
        <div class="col">
          <div class="crm-kpi-card">
            <div class="crm-kpi-icon" style="background:<?= $info['color'] ?>18;color:<?= $info['color'] ?>">
              <i class="ti <?= $info['icon'] ?>"></i>
            </div>
            <div>
              <div class="crm-kpi-value"><?= (int)($kpi[$key] ?? 0) ?></div>
              <div class="crm-kpi-label"><?= $info['label'] ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
    
        <div class="col">
          <div class="crm-kpi-card crm-kpi-warn">
            <div class="crm-kpi-icon" style="background:#fef3c7;color:#d97706">
              <i class="ti ti-alert-triangle"></i>
            </div>
            <div>
              <div class="crm-kpi-value"><?= count($expiring) ?></div>
              <div class="crm-kpi-label">Expiring &lt;60 Days</div>
            </div>
          </div>
        </div>
      </div>
      
      <?php if (!empty($expiring)): ?>
      <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3 small">
        <i class="ti ti-alert-triangle fs-5"></i>
        <span>
          <strong><?= count($expiring) ?> contract<?= count($expiring) > 1 ? 's' : '' ?></strong>
          expiring within 60 days:
          <?php foreach ($expiring as $i => $ex): ?>
            <a href="<?= site_url('crm/contracts/view/' . $ex['id']) ?>" class="fw-semibold">
              <?= html_escape($ex['contract_number']) ?>
            </a><?php echo ($i < count($expiring) - 1) ? ', ' : ''; ?>
          <?php endforeach; ?>
        </span>
      </div>
      <?php endif; ?>
      <div class="app-divider-v dashed mb-3"></div>
  
<div class="table-responsive crm-table">
    <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
        <thead class="bg-light-primary">
            <tr>
              <th style="width:40px">#</th>
              <th>Contract Tittle</th>
              <th>Client</th>
              <th>Contract Type</th>
              <th>Status</th>
              <th>Billing Model</th>
              <th>Start Date</th>
              <th>End Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($contracts)): ?>
              <?php foreach ($contracts as $c): ?>
              <tr>
                <td class="text-muted small"><?= (int)$c['id'] ?></td>
                <td>
                  <a href="<?= site_url('crm/contracts/view/' . $c['id']) ?>" class="fw-semibold text-primary">
                    <?= html_escape($c['contract_title']) ?>
                  </a>
                  <div class="small text-light"><?= html_escape($c['contract_code']) ?></div>
                </td>
                <td>
                  <span><?= html_escape($c['client_name'] ?? '—') ?></span>
                  <?php if (!empty($c['client_code'])): ?>
                    <div class="small text-light"><?= html_escape($c['client_code']) ?></div>
                  <?php endif; ?>
                </td>
                
                <td>
                    <span class="capital">
                    <?= ucfirst(str_replace('_', ' ', $c['contract_type'])) ?>
                    </span>
                </td>
                
                <td><?= contract_status_badge($c['status']) ?></td>
                
                <td>
                <span><?= ucfirst(str_replace('_', ' ', $c['billing_model'])) ?></span>
                <?php if ($c['billing_model'] === 'percentage' && !empty($c['rate_percent'])): ?>
                  <div class="small text-muted">
                    <?= number_format($c['rate_percent'], 2) ?>%
                  </div>
                <?php elseif ($c['billing_model'] === 'flat_fee' && !empty($c['rate_flat'])): ?>
                  <div class="small text-muted">
                    $<?= number_format($c['rate_flat'], 2) ?>
                  </div>
                <?php elseif ($c['billing_model'] === 'custom' && !empty($c['custom_rate'])): ?>
                  <div class="small text-muted">
                    <?= html_escape($c['custom_rate']) ?>
                  </div>
                <?php endif; ?>
                </td>
                
                <td class="small"><?= $c['start_date'] ? date('M j, Y', strtotime($c['start_date'])) : '—' ?></td>
                <td class="small">
                  <?php if (!empty($c['end_date'])): ?>
                    <?php
                      $daysLeft = (int)ceil((strtotime($c['end_date']) - time()) / 86400);
                      $expClass = ($daysLeft <= 30 && $c['status'] === 'active') ? 'text-danger fw-semibold' : '';
                    ?>
                    <span class="<?= $expClass ?>"><?= date('M j, Y', strtotime($c['end_date'])) ?></span>
                    <?php if ($daysLeft <= 60 && $daysLeft > 0 && $c['status'] === 'active'): ?>
                      <div class="small text-warning"><?= $daysLeft ?> days left</div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">Open-ended</span>
                  <?php endif; ?>
                </td>
                
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                  <i class="ti ti-file-off d-block mb-2" style="font-size:2rem;opacity:.4"></i>
                  No contracts found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>