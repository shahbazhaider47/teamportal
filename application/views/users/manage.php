<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$canCreate    = staff_can('create', 'users');
$canExport    = staff_can('export', 'general');
$canPrint     = staff_can('print', 'general');
$canViewUser  = staff_can('view_global', 'users');
$table_id     = $table_id ?? 'dataTable';
?>
        
<div class="container-fluid">

  <div class="view-header mb-3">
    <div class="view-icon me-3"><i class="fa-solid fa-users fa-fw"></i></div>
    <div class="flex-grow-1">
      <div class="view-title"><?= $page_title ?></div>
      <div class="view-sub"><?= $page_desc ?></div>
    </div>
    <div class="ms-auto d-flex gap-2">

        <!-- In-Active Staff -->
        <a href="<?= site_url('users/inactive') ?>"
           id="btn-inactive-users"
           class="btn btn-outline-primary btn-header"
           title="View In-Active Staff">
            <i class="fas fa-user-slash me-1"></i> In-Active Staff
        </a>
        
    
    <div class="btn-divider mt-1"></div>        

        <a href="<?= site_url('users/add_new') ?>"
           target="_blank"
           rel="noopener noreferrer"
           class="btn btn-primary btn-header"
           title="Add new user to system">
            <i class="fas fa-user-plus me-1"></i> Add New
        </a>
        
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
                    'exclude_columns' => ['EMP ID', 'Current Salary', 'Date of Joining'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
  <div class="card">
    <div class="card-body">

        <div class="row g-2 mb-3">
        
            <?php
            $card_metrics = [
                ['Total Staff',       $counts['total'] ?? 0,         'ti ti-users',        '#6366f118'],
                ['Active',            $counts['active'] ?? 0,        'ti ti-user-check',   '#16a34a18'],
                ['In-Active',         $counts['inactive'] ?? 0,      'ti ti-user-off',     '#ef444418'],
                ['On Probation',      $counts['on_probation'] ?? 0,  'ti ti-clock',        '#0ea5e918'],
                ['Contracts Expired', $counts['contracts_expired'] ?? 0, 'ti ti-file',     '#f59e0b18'],
            ];
            ?>
        
            <?php foreach ($card_metrics as $m): ?>
            <div class="col">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:<?= $m[3] ?>;">
                        <i class="<?= $m[2] ?>"></i>
                    </div>
                    <div>
                        <div class="kpi-value"><?= $m[1] ?></div>
                        <div class="kpi-label"><?= $m[0] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        
        </div>
        
        <div class="app-divider-v dashed mb-3"></div>

    <div class="table-responsive">    
    <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
          <thead class="bg-light-primary">
            <tr>
              <th class="text-center" width="60">EMP ID</th>
              <th width="250">Full Name</th>
              <th>Date of Joining</th>
              <th>Office Location</th>
              <th>Designation</th>
              <th>Department</th>
              <th>Team Name</th>
              <th>Reports To</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <?php if ((int)$u['id'] === 1) continue; // Skip super admin ?>
              <tr>

                <td class="text-center">
                <span class="text-muted align-self-center small"><?= emp_id_display($u['emp_id'] ?? '-') ?></span>
                  <?php $role = $u['user_role']; ?>
                  <span class="badge badge-role-<?= html_escape($role) ?>">
                    <?= ucfirst(html_escape($role)) ?>
                  </span>
                </td>
                <?php 
                  $isActiveClass = empty($u['is_active']) ? 'text-danger ' : 'text-primary';
                  $fullName      = html_escape($u['firstname'].' '.$u['lastname']);
                ?>
                <td>
                  <a href="<?= $canViewUser ? site_url("users/view/{$u['id']}") : '#' ?>" 
                     <?= $canViewUser ? '' : 'class="disabled" aria-disabled="true" tabindex="-1"' ?>
                     target="_blank" style="text-decoration: none; color: inherit; display: block;">
                    <div class="d-flex align-items-center">
                    <img
                      src="<?= user_avatar_url($u['profile_image'] ?? null) ?>"
                      class="rounded-circle me-2"
                      width="32" height="32"
                      alt="<?= html_escape(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '')) ?>">
                      <div>
                        <strong class="text-primary">
                          <?= html_escape($u['firstname'] . ' ' . $u['lastname']) ?>
                          <?php if (!empty($u['gender'])): ?>
                            <?php if (strtolower($u['gender']) === 'male'): ?>
                              <i class="ti ti-gender-male text-primary ms-1" title="Male"></i>
                            <?php elseif (strtolower($u['gender']) === 'female'): ?>
                              <i class="ti ti-gender-female text-danger ms-1" title="Female"></i>
                            <?php else: ?>
                              <i class="ti ti-gender-bigender text-muted ms-1" title="Other"></i>
                            <?php endif; ?>
                          <?php endif; ?>
                        </strong>
                        <div class="text-muted small"><?= html_escape($u['email']) ?></div>
                      </div>
                    </div>
                  </a>
                </td>
                <td>
                  <?= !empty($u['emp_joining']) 
                        ? date('M d, Y', strtotime($u['emp_joining'])) 
                        : '-' ?>
                </td>
                <td><?= get_company_office_name($u['office_id'] ?? '-') ?></td>
                <td><?= html_escape($u['position_title'] ?? '-') ?></td>
                <td><?= get_emp_department($u['department_name'] ?? 'N/A'); ?></td>
                <td><?= html_escape($u['team_name'] ?? '-'); ?></td>
                <td>
                  <?php
                    $reportingName = '-';
                
                    switch (strtolower($u['user_role'])) {
                        case 'employee':
                            $reportingName = $u['teamlead_name'] ?? '-';
                            break;
                        case 'teamlead':
                            $reportingName = $u['manager_name'] ?? '-';
                            break;
                        case 'manager':
                            $reportingName = $u['reporting_name'] ?? '-';
                            break;
                    }
                
                    echo $reportingName !== '-' 
                        ? html_escape($reportingName)
                        : '<span class="text-muted">—</span>';
                  ?>
                </td>
                <td style="display: none;"><?= html_escape($u['username']); ?></td>
                <td style="display: none;"><?= html_escape($u['email']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>  