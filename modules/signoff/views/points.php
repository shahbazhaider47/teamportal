<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canView   = staff_can('view_global', 'signoff');
          $canExport = staff_can('export', 'general');
          $canPrint  = staff_can('print', 'general');
          
          // Read perf indicators from controller if provided; otherwise pull from options
          $perf = isset($perf_indicators)
              ? strtolower(trim((string)$perf_indicators))
              : (function_exists('get_setting') ? strtolower(trim((string)get_setting('signoff_perf_indicators'))) : 'none');
        
          if ($perf === '') { $perf = 'none'; }
        
          $showTargets = in_array($perf, ['targets','both'], true);
          $showPoints  = in_array($perf, ['points','both'],  true);
        
          // Lock-after-submission (prefer controller param; fallback to option)
          $lockAfterSubmit = isset($lock_after_submit)
              ? (bool)$lock_after_submit
              : (function_exists('get_setting') ? (get_setting('signoff_lock_after_submit') === 'yes') : true);
              
        ?>

        <a href="<?= base_url('signoff') ?>" class="btn btn-outline-primary btn-header" title="Signoff Details">
            <i class="ti ti-calendar me-1"></i> Signoff
        </a>

        <a href="<?= $canView ? site_url('signoff/forms') : 'javascript:void(0);' ?>" 
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>" 
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
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
           title="Points">
           <i class="ti ti-trophy"></i> Points
        </a>
      <?php endif; ?>
      
        
        <div class="btn-divider"></div>

        <button class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#assignPointsModal">
          <i class="ti ti-plus"></i> Assign Points
        </button> 
            
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'pointstable' ?>">
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

    <div class="card">
        <div class="card-body mt-2">
            <div class="table-responsive">
                <table class="table small align-middle mb-0" id="pointstable">
                    <thead class="bg-light-primary">
                      <tr>
                        <th>Form Title</th>
                        <th>Scope</th>
                        <th class="text-center">Total Metrics</th>
                        <th class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($points_rows)): ?>
                      <tr>
                        <td colspan="4" class="text-center text-muted">No assigned points found.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($points_rows as $row): ?>
                        <?php
                          $formId   = (int)$row['form_id'];
                          $teamId   = (int)$row['team_id']; // 0 = Global
                          $title    = (string)($row['form_title'] ?? '—');
                          $metrics  = (int)($row['metrics_count'] ?? 0);
                    
                          // Resolve scope label priority:
                          // If the form has a position_id -> "Position: {position_title}"
                          // else if teamId > 0 -> "Team: {team_name}"
                          // else -> "Global"
                          $scope = 'Global';
                          if (!empty($row['position_id'])) {
                              $scope = 'Position: ' . html_escape($row['position_title'] ?? ('#'.$row['position_id']));
                          } elseif ($teamId > 0) {
                              $scope = 'Team: ' . html_escape($row['team_name'] ?? ('#'.$teamId));
                          }
                          
                          // Modal IDs per (team, form)
                          $rowKey   = $teamId . '_' . $formId;
                          $modalId  = 'editTeamPointsModal_' . $rowKey;
                          $labelId  = 'editPointsLabel_' . $rowKey;
                    
                          // For "Edit" modal we need the list of fields with current weights
                          $metricList = $row['points_list'] ?? [];
                          $teamHidden = ($teamId === 0) ? 'global' : (string)$teamId;
                        ?>
                        <tr>
                          <td><?= html_escape($title) ?></td>
                          <td><?= $scope ?></td>
                          <td class="text-center">
                            <span class="badge bg-primary"><?= $metrics ?></span>
                          </td>
                          <td class="text-end">
                            <!-- Edit -->
                            <button class="btn btn-outline-primary btn-ssm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#<?= $modalId ?>">
                              <i class="ti ti-edit"></i> Edit
                            </button>
                    
                            <!-- Delete assigned points (not the form) -->
                            <form method="post"
                                  action="<?= base_url('signoff/points/delete_points') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete assigned points for this form/scope only? This will not delete the form.');">
                              <input type="hidden" name="team_id" value="<?= html_escape($teamHidden) ?>">
                              <input type="hidden" name="form_id" value="<?= $formId ?>">
                              <button type="submit" class="btn btn-outline-danger btn-ssm">
                                <i class="ti ti-trash"></i> Delete
                              </button>
                            </form>
                    
                            <!-- Edit Points Modal -->
                            <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $labelId ?>" aria-hidden="true">
                              <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                  <form method="post" action="<?= base_url('signoff/points/update_points') ?>">
                                    <div class="modal-header bg-primary">
                                      <h5 class="modal-title text-white" id="<?= $labelId ?>">
                                        <i class="ti ti-edit me-2"></i>
                                        Edit Points — <?= html_escape($scope) ?> / <?= html_escape($title) ?>
                                      </h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                      <input type="hidden" name="team_id" value="<?= html_escape($teamHidden) ?>">
                                      <input type="hidden" name="form_id" value="<?= $formId ?>">
                    
                                      <table class="table table-sm table-bordered mb-0">
                                        <thead class="bg-light-primary">
                                          <tr>
                                            <th class="text-start">Metrics</th>
                                            <th style="width:180px;" class="text-center">Points</th>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          <?php if (!empty($metricList)): ?>
                                            <?php foreach ($metricList as $metric): ?>
                                              <tr>
                                                <td class="text-start align-middle"><?= html_escape($metric['field_label']) ?></td>
                                                <td class="text-center align-middle">
                                                  <input type="number" step="0.01" min="0"
                                                         name="points[<?= html_escape($metric['field']) ?>]"
                                                         value="<?= html_escape($metric['points']) ?>"
                                                         class="form-control form-control-sm" required>
                                                </td>
                                              </tr>
                                            <?php endforeach; ?>
                                          <?php else: ?>
                                            <tr>
                                              <td colspan="2" class="text-center text-muted">No metrics are currently assigned points.</td>
                                            </tr>
                                          <?php endif; ?>
                                        </tbody>
                                      </table>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">Cancel</button>                                      
                                      <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ti ti-device-floppy me-1"></i> Save Changes
                                      </button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                            <!-- /Edit Points Modal -->
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

<?php
$CI =& get_instance();
$CI->load->view('signoff/modals/points_modal', [
    'teams'              => $teams,
    'positions'          => $positions,          // ✅ now passed
    'forms_by_team'      => $forms_by_team,
    'forms_by_position'  => $forms_by_position,  // ✅ now passed
    'points_flags'       => $points_flags,       // ✅ now passed
    'subs_counts'        => $subs_counts,        // ✅ now passed
]);
?>

<style>
.btn.rounded-circle {
    width: 25px;
    height: 25px;
    display: flex;
    color: white;
    background-color: #056464;
    justify-content: center;
    align-items: center;
    font-size: 1.1em;
}
</style>
