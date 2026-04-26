<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
    <?php
      $canView      = staff_can('view_own', 'signoff');
      $canAssign    = staff_can('assign', 'signoff');
      $canApprove   = staff_can('approve', 'signoff');
      $canExport    = staff_can('export', 'general');
      $canPrint     = staff_can('print', 'general');
    
      // Read perf indicators from controller if provided; otherwise pull from options
      $perf = isset($perf_indicators)
          ? strtolower(trim((string)$perf_indicators))
          : (function_exists('get_option') ? strtolower(trim((string)get_option('signoff_perf_indicators'))) : 'none');
    
      if ($perf === '') { $perf = 'none'; }
    
      $showTargets = in_array($perf, ['targets','both'], true);
      $showPoints  = in_array($perf, ['points','both'],  true);
    
      // Lock-after-submission (prefer controller param; fallback to option)
      $lockAfterSubmit = isset($lock_after_submit)
          ? (bool)$lock_after_submit
          : (function_exists('get_option') ? (get_option('signoff_lock_after_submit') === 'yes') : true);

        $CI =& get_instance();
        // Role-based visibility for "Team Signoff" button
        $empRole = strtolower((string)($CI->session->userdata('emp_role') ?? $CI->session->userdata('user_role') ?? ''));
        $showTeamSignoffBtn = in_array($empRole, ['teamlead', 'manager'], true);          
    ?>
    
<div class="container-fluid">
  
  <div class="view-header mb-3">
    <div class="view-icon me-3"><i class="fa-solid fa-file-text fa-fw"></i></div>
    <div class="flex-grow-1">
      <div class="view-title"><?= $page_title ?></div>
    </div>
    <div class="ms-auto d-flex gap-2">

      <a href="<?= $canView ? site_url('signoff') : 'javascript:void(0);' ?>"
         class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
         title="My Signoff">
         <i class="ti ti-calendar me-1"></i> Signoff
      </a>
    
      <?php if ($showTargets): ?>
        <a href="<?= $canView ? site_url('signoff/targets/my_targets') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="My Targets">
           <i class="ti ti-target-arrow"></i> My Targets
        </a>
      <?php endif; ?>
    
      <?php if ($showPoints): ?>
        <a href="<?= $canView ? site_url('signoff/points/my_points') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="My Points">
           <i class="ti ti-trophy"></i> My Points
        </a>
      <?php endif; ?>
    
      <a href="<?= $canView ? site_url('signoff/signoff_history') : 'javascript:void(0);' ?>"
         class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
         title="Signoff History">
          <i class="ti ti-history-toggle"></i> History
      </a>        
    
    <div class="btn-divider mt-1"></div>        

      <?php if ($showTeamSignoffBtn): ?>
        <a href="<?= site_url('signoff/team_signoff') ?>"
           class="btn btn-header btn-primary"
           title="My Team Signoff">
          <i class="ti ti-users"></i> Team Signoff
        </a>
      <?php endif; ?>
            
    </div>
  </div>
  
  <?php $CI->load->view('signoff/widgets/user_signoff_summary', ['month_stats' => $month_stats ?? []]); ?>

  <div class="row g-4">
    <!-- LEFT COLUMN: Table -->
    <div class="col-md-7">
      <div class="card">
        <div class="d-flex justify-content-between align-items-center p-3">
          <span class="h6">Submitted Signoff</span>
          <a href="<?= base_url('signoff/signoff_history') ?>" class="btn btn-primary btn-header">
            <i class="ti ti-eye me-1"></i> View Full History
          </a>
        </div>
        <div class="card-body">
          <?php if (!empty($history)): ?>
    
            <?php
              // Build a local map form_id => title (fallback if form_title isn’t in $history)
              $__form_titles = [];
              if (isset($forms) && is_array($forms)) {
                foreach ($forms as $f) {
                  $__form_titles[(int)$f['id']] = (string)$f['title'];
                }
              }
            ?>
    
            <div class="table-responsive">
              <table class="table table-hover small align-middle mb-0" id="signoffhistoryTable">
                <thead class="bg-light-primary">
                  <tr>
                    <th width="25%">Signoff Date</th>
                    <th width="45%">Form Title</th>
                    <?php if ($showPoints): ?>
                    <th width="45%">Points</th>
                    <?php endif; ?>
                    <?php if ($showTargets): ?>
                    <th width="45%">Target Achived</th>
                    <?php endif; ?>                    
                    <th class="text-center" width="20%">Status</th>
                  </tr>
                </thead>
                    <tbody>
                    <?php foreach ($history as $row): ?>
                      <?php
                        // Resolve form title safely
                        $__fid   = isset($row['form_id']) ? (int)$row['form_id'] : 0;
                        $__title = isset($row['form_title']) && $row['form_title'] !== ''
                                   ? (string)$row['form_title']
                                   : (isset($__form_titles[$__fid]) ? $__form_titles[$__fid] : '—');
                    
                        // Status badge
                        $status = strtolower($row['status'] ?? '');
                        $status_label = 'secondary';
                        if ($status === 'approved')   $status_label = 'success';
                        elseif ($status === 'rejected') $status_label = 'danger';
                        elseif ($status === 'submitted') $status_label = 'primary';
                    
                        // Formatter for numeric columns: integers without decimals, floats with 2
                        $fmtNum = static function ($v) {
                            if (!is_numeric($v)) return '—';
                            $f = (float)$v;
                            return fmod($f, 1.0) === 0.0 ? number_format($f, 0) : number_format($f, 2);
                        };
                    
                        // Points (from signoff_submissions.total_points)
                        $tpFmt = $fmtNum($row['total_points'] ?? null);
                    
                        // Targets achieved (from signoff_submissions.achieved_targets)
                        $atFmt = $fmtNum($row['achieved_targets'] ?? null);
                      ?>
                      <tr>
                        <td><?= date('Y-M-d', strtotime($row['submission_date'])) ?></td>
                        <td><?= html_escape($__title) ?></td>
                    
                        <?php if (!empty($showPoints)): ?>
                          <td><?= html_escape($tpFmt) ?></td>
                        <?php endif; ?>
                    
                        <?php if (!empty($showTargets)): ?>
                          <td><?= html_escape($atFmt) ?></td>
                        <?php endif; ?>
                    
                        <td class="text-center">
                          <span class="badge bg-<?= $status_label ?>"><?= ucfirst($status ?: '—') ?></span>
                          <?php if ($status === 'excused'): ?>
                            <span class="badge bg-info">Excused</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="p-4 text-center text-muted">
              <i class="ti ti-inbox fs-1 mb-2"></i>
              <div>No signoff history found.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RIGHT COLUMN: History -->
    <div class="col-md-5">

      <?php if (empty($forms)): ?>
        <div class="alert alert-info shadow-sm mb-4">
          <i class="ti ti-info-circle me-2"></i>
          No signoff forms are assigned to you yet. Please check back later.
        </div>
      <?php else: ?>

        <div class="card">
          <div class="d-flex justify-content-between align-items-center p-3">
            <span class="h6 mb-0">Assigned Signoff Forms</span>
          </div>

          <div class="card-body">
            <table class="table table-hover small align-middle mb-0" id="usersignoffTable">
              <thead class="bg-light-primary">
                <tr>
                  <th>Form Title</th>
                  <th class="text-center">Status & Action</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($forms as $form):
                  $sub       = $submissions[$form['id']] ?? null;
                  $submitted = ($sub && $sub['submission_date'] === $today);

                  $hasTeam = !empty($form['team_id']);
                  $hasPos  = !empty($form['position_id']);

                  // Safe lookups (avoid notices if maps are not provided)
                  $teamName = null;
                  if ($hasTeam) {
                      $tid = (int)$form['team_id'];
                      $teamName = (isset($teams) && is_array($teams) && isset($teams[$tid]))
                          ? $teams[$tid]
                          : ('Team #' . $tid);
                  }

                  $posTitle = null;
                  if ($hasPos) {
                      $pid = (int)$form['position_id'];
                      $posTitle = (isset($positions_map) && is_array($positions_map) && isset($positions_map[$pid]))
                          ? $positions_map[$pid]
                          : ('Position #' . $pid);
                  }
              ?>
                <tr>
                  <td class="align-middle">
                    <span class="fw-semibold"><?= html_escape($form['title']) ?></span>
                    <span class="ms-2">
                      <?php if (!$hasTeam && !$hasPos): ?>
                        <span class="badge bg-secondary">
                          <i class="ti ti-users me-1"></i> Global
                        </span>
                      <?php elseif ($hasTeam && !$hasPos): ?>
                        <span class="badge bg-light-primary">
                          <i class="ti ti-users-group me-1"></i> Team: <?= html_escape($teamName) ?>
                        </span>
                      <?php elseif (!$hasTeam && $hasPos): ?>
                        <span class="badge bg-light-primary">
                          <i class="ti ti-id me-1"></i> Position: <?= html_escape($posTitle) ?>
                        </span>
                      <?php else: ?>
                        <span class="badge bg-danger">Misconfigured: team &amp; position set</span>
                      <?php endif; ?>
                    </span>
                  </td>
                    <td class="text-center align-middle">
                      <div class="d-inline-flex align-items-center justify-content-center gap-2">
                        <?php if ($submitted): ?>
                          <span class="pill pill-success">Submitted</span>

                          <a href="<?= base_url('signoff/submit/' . $form['id']) ?>"
                             class="btn btn-outline-primary btn-ssm"
                             title="Submit Signoff"
                             aria-label="Submit signoff for <?= html_escape($form['title']) ?>">
                            <i class="ti ti-edit"></i> Submit
                          </a>
                          
                          <?php if ($lockAfterSubmit): ?>
                            <!-- Locked by setting: updates disabled -->
                            <button type="button"
                                    class="btn btn-outline-secondary btn-ssm disabled"
                                    title="Editing locked by admin setting">
                              <i class="ti ti-lock"></i> Locked
                            </button>
                          <?php else: ?>
                            <!-- Not locked: allow update -->
                            <a href="<?= base_url('signoff/submit/' . $form['id']) ?>"
                               class="btn btn-outline-primary btn-ssm"
                               title="Update today's signoff"
                               aria-label="Update signoff for <?= html_escape($form['title']) ?>">
                              <i class="ti ti-edit"></i> Update
                            </a>
                          <?php endif; ?>

                        <?php else: ?>
                          <span class="pill pill-warning">Not Submitted</span>
                          <a href="<?= base_url('signoff/submit/' . $form['id']) ?>"
                             class="btn btn-outline-primary btn-ssm"
                             title="Submit Signoff"
                             aria-label="Submit signoff for <?= html_escape($form['title']) ?>">
                            <i class="ti ti-edit"></i> Submit
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>