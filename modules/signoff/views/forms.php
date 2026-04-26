<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canView      = staff_can('view_global', 'signoff');
          $canAdd       = staff_can('create', 'signoff');          
          $canExport    = staff_can('export', 'general');
          $canPrint     = staff_can('print', 'general');

          // Read perf indicators from controller if provided; otherwise pull from options
          $perf = isset($perf_indicators)
              ? strtolower(trim((string)$perf_indicators))
              : (function_exists('get_setting') ? strtolower(trim((string)get_setting('signoff_perf_indicators'))) : 'none');
        
          if ($perf === '') { $perf = 'none'; }
        
          $showTargets = in_array($perf, ['targets','both'], true);
          $showPoints  = in_array($perf, ['points','both'],  true);
          
                  // From controller (added below)
          $has_points_by_form  = $has_points_by_form  ?? [];
          $has_targets_by_form = $has_targets_by_form ?? [];
          
          // Lock-after-submission (prefer controller param; fallback to option)
          $lockAfterSubmit = isset($lock_after_submit)
              ? (bool)$lock_after_submit
              : (function_exists('get_setting') ? (get_setting('signoff_lock_after_submit') === 'yes') : true);          
        ?>

        <a href="<?= base_url('signoff') ?>" class="btn btn-outline-primary btn-header" title="Signoff Details">
            <i class="ti ti-calendar me-1"></i> Signoff
        </a>
        
        <a href="<?= $canView ? site_url('signoff/forms') : 'javascript:void(0);' ?>" 
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>" 
           title="Signoff Forms">
           <i class="ti ti-file-stack"></i> Forms
        </a>
        
          <?php if ($showTargets): ?>
            <a href="<?= $canView ? site_url('signoff/targets') : 'javascript:void(0);' ?>"
               class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
               title="Targets">
               <i class="ti ti-target-arrow"></i> Targets
            </a>
          <?php endif; ?>
        
          <?php if ($showPoints): ?>
            <a href="<?= $canView ? site_url('signoff/points') : 'javascript:void(0);' ?>"
               class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
               title="Points">
               <i class="ti ti-trophy"></i> Points
            </a>
          <?php endif; ?>
        
        <div class="btn-divider"></div>

            <a href="<?= base_url('signoff/add_new_form') ?>" class="btn btn-primary btn-header" title="Create a new signoff form">
                <i class="ti ti-plus"></i> New Signoff Form
            </a>
            
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'signoffformsTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
        
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>
    
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
                <table class="table table-hover table-sm small align-middle mb-0" id="signoffformsTable">
                  <thead class="bg-light-primary">
                    <tr>
                      <th scope="col" class="fw-semibold">Form Title</th>
                      <th scope="col" class="fw-semibold">Assigned To</th>
                      <th scope="col" class="fw-semibold text-center" title="From the 1st of this month to today">
                        Submissions (This Month)
                      </th>
                      <th scope="col" class="fw-semibold text-center" title="All submissions to date">
                        Submissions (All-Time)
                      </th>
                        <th scope="col" class="fw-semibold">Status</th>
                      <th scope="col" class="fw-semibold text-end" width="20%">Actions</th>
                    </tr>
                  </thead>
                
                  <tbody>
                    <?php if (empty($forms)): ?>
                      <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                          <i class="ti ti-info-circle text-secondary fs-3 mb-2"></i><br>
                          No signoff forms found. <br>
                          <span class="small">Click <b>New Signoff Form</b> to get started.</span>
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($forms as $form): ?>
                        <?php
                          $fid = isset($form['id']) ? (int)$form['id'] : 0;
                          $m   = isset($counts_this_month[$fid]) ? (int)$counts_this_month[$fid] : 0;
                          $all = isset($counts_all_time[$fid])   ? (int)$counts_all_time[$fid]   : 0;
                
                          $hasTeam = !empty($form['team_id']);
                          $hasPos  = !empty($form['position_id']);
                
                          // Defensive lookups
                          $teamName = $hasTeam ? ($teams[$form['team_id']] ?? ('#' . (int)$form['team_id'])) : null;
                          $posTitle = $hasPos  ? ($positions_map[$form['position_id']] ?? ('#' . (int)$form['position_id'])) : null;
                        ?>
                        <tr>
<td>
  <div class="fw-semibold"><?= html_escape($form['title']) ?></div>

  <?php
    $needsTargets = (strtolower($perf) === 'targets');
    $needsPoints  = (strtolower($perf) === 'points');

    $hasTargets = !empty($has_targets_by_form[$fid]);
    $hasPoints  = !empty($has_points_by_form[$fid]);

    $notReadyTargets = $needsTargets && !$hasTargets;
    $notReadyPoints  = $needsPoints  && !$hasPoints;
  ?>

  <?php if ($notReadyTargets || $notReadyPoints): ?>
    <div class="small text-danger mt-1">
      <i class="ti ti-alert-triangle me-1"></i>
      This form can’t be used until you assign
      <?= $notReadyTargets ? '<b>Targets</b>' : '<b>Points</b>' ?>
      or change the performance indicator (Settings) to
      <?= $notReadyTargets ? 'Points or None' : 'Targets or None' ?>.
    </div>
  <?php endif; ?>
</td>
                          
                
                          <td>
                            <?php
                              if (!$hasTeam && !$hasPos) {
                                  echo '<span class="badge bg-secondary">Global (All Teams)</span>';
                              } elseif ($hasTeam && !$hasPos) {
                                  echo '<span class="badge bg-light-primary">Team: ' . html_escape($teamName) . '</span>';
                              } elseif (!$hasTeam && $hasPos) {
                                  echo '<span class="badge bg-light-primary">Position: ' . html_escape($posTitle) . '</span>';
                              } else {
                                  echo '<span class="badge bg-danger-subtle text-danger">Team &amp; Position</span>';
                              }
                            ?>
                          </td>
                
                          <!-- NEW: This Month -->
                          <td class="text-center">
                            <span class="badge bg-light-primary"><?= $m ?></span>
                          </td>
                
                          <!-- NEW: All Time -->
                          <td class="text-center">
                            <span class="badge bg-light"><?= $all ?></span>
                          </td>
                
                          <td>
                            <?php if (!empty($form['is_active'])): ?>
                              <span class="badge bg-success">Active</span>
                            <?php else: ?>
                              <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                          </td>
                          
                            <td class="text-end">
                              <!-- hidden toggle form so the toggle button can stay inside the btn-group -->
                              <form id="toggleForm<?= (int)$fid ?>" action="<?= site_url('signoff/toggle_form_status/'.$fid) ?>" method="post" class="d-none"></form>
                            
                              <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                            
                                <!-- View -->
                                <a href="<?= base_url('signoff/view_submissions/' . (int)$fid) ?>"
                                   class="btn btn-outline-secondary" title="View Submissions">
                                  <i class="ti ti-eye"></i>
                                </a>

                                <form id="cloneForm<?= (int)$fid ?>" method="post" action="<?= base_url('signoff/clone_form/' . (int)$form['id']) ?>" class="d-inline"></form>
                                  <button type="submit"
                                          form="cloneForm<?= (int)$fid ?>"
                                          class="btn btn-outline-secondary<?= $canView ? '' : ' disabled' ?>"
                                          <?= $canView ? '' : 'disabled' ?>
                                          title="Clone this form"
                                          onclick="return confirm('Clone “<?= html_escape($form['title']) ?>”? A copy will be created as inactive so you can assign it to another scope.');">
                                    <i class="ti ti-copy me-1"></i>
                                  </button>
                            
                                <!-- Edit -->
                                <a href="<?= base_url('signoff/edit_form/' . (int)$fid) ?>"
                                   class="btn btn-outline-secondary" title="Edit Form">
                                  <i class="ti ti-edit"></i>
                                </a>
                            
                                <!-- Delete -->
                                <button type="button"
                                        class="btn btn-outline-secondary"
                                        title="Delete Form"
                                        onclick="if(confirm('Are you sure you want to delete this form? This action cannot be undone.')){ window.location.href='<?= base_url('signoff/delete_form/' . (int)$fid) ?>'; }">
                                  <i class="ti ti-trash"></i>
                                </button>
                            
                                <?php
                                  $perfMode  = strtolower($perf ?? 'none');
                                  $canEnable = true;
                                  if ($perfMode === 'targets') { $canEnable = !empty($hasTargets); }
                                  elseif ($perfMode === 'points') { $canEnable = !empty($hasPoints); }
                                  $isActive = !empty($form['is_active']);
                                ?>
                            
                                <!-- Toggle (play/pause) — SAME STYLE as the others -->
                                <?php if ($isActive): ?>
                                  <button type="submit"
                                          form="toggleForm<?= (int)$fid ?>"
                                          class="btn btn-outline-secondary"
                                          title="Deactivate Form">
                                    <i class="ti ti-player-pause"></i>
                                  </button>
                                <?php else: ?>
                                  <button type="submit"
                                          form="toggleForm<?= (int)$fid ?>"
                                          class="btn btn-outline-secondary<?= $canEnable ? '' : ' disabled' ?>"
                                          <?= $canEnable ? '' : 'disabled' ?>
                                          title="<?= $canEnable ? 'Activate Form' : 'Assign required settings first' ?>">
                                    <i class="ti ti-player-play"></i>
                                  </button>
                                <?php endif; ?>
                            
                              </div>
                            </td>

                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
