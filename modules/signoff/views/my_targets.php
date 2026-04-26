<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
  $page_title   = $page_title ?? 'My Targets';
  $defaultStart = date('Y-m-01');
  $defaultEnd   = date('Y-m-t');
  $filterStart  = isset($_GET['start']) && $_GET['start'] ? $_GET['start'] : ($start_date ?? $defaultStart);
  $filterEnd    = isset($_GET['end'])   && $_GET['end']   ? $_GET['end']   : ($end_date ?? $defaultEnd);
  if ($filterEnd < $filterStart) { $tmp = $filterStart; $filterStart = $filterEnd; $filterEnd = $tmp; }

  $scopes   = $scopes ?? [];
  $table_id = $table_id ?? 'my_targetsTable';
?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title) ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canView   = staff_can('view_own', 'signoff');
        $canExport = staff_can('export', 'general');
        $canPrint  = staff_can('print', 'general');
        $canAssign    = staff_can('assign', 'signoff');
        $canApprove   = staff_can('approve', 'signoff');

        $CI =& get_instance();
        // Role-based visibility for "Team Signoff" button
        $empRole = strtolower((string)($CI->session->userdata('emp_role') ?? $CI->session->userdata('user_role') ?? ''));
        $showTeamSignoffBtn = in_array($empRole, ['teamlead', 'manager'], true);        
      ?>

      <a href="<?= $canView ? site_url('signoff') : 'javascript:void(0);' ?>"
         class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
         title="My Signoff">
        <i class="ti ti-calendar me-1"></i> Signoff
      </a>

      <a href="<?= $canView ? site_url('signoff/targets/my_targets') : 'javascript:void(0);' ?>"
         class="btn btn-header btn-primary"
         title="My Targets">
        <i class="ti ti-target-arrow"></i> My Targets
      </a>

      <a href="<?= $canView ? site_url('signoff/signoff_history') : 'javascript:void(0);' ?>"
         class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
         title="Signoff History">
        <i class="ti ti-history-toggle"></i> History
      </a>

      <div class="btn-divider"></div>

      <?php if ($showTeamSignoffBtn): ?>
        <a href="<?= site_url('signoff/team_signoff') ?>"
           class="btn btn-header btn-primary"
           title="My Team Signoff">
          <i class="ti ti-users"></i> Team Signoff
        </a>
      <?php endif; ?>
      
      <!-- Date range filter -->
      <form class="d-flex align-items-center app-form" method="get" action="">
        <input type="date" name="start" class="form-control form-control-sm me-2" value="<?= html_escape($filterStart) ?>" required style="height:34px; min-width:150px;">
        <input type="date" name="end"   class="form-control form-control-sm me-2" value="<?= html_escape($filterEnd) ?>" required style="height:34px; min-width:150px;">
        <button type="submit" class="btn btn-primary btn-header">Filter</button>
      </form>

      <div class="btn-divider"></div>

      <!-- Search -->
      <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
        <input type="text" class="form-control rounded app-form small dynamic-search-input"
               placeholder="Search..."
               aria-label="Search"
               data-table-target="<?= $table_id ?>">
        <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display:none;"></button>
      </div>

      <!-- Export -->
      <?php if ($canExport): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                title="Export to Excel"
                data-export-filename="<?= html_escape($page_title) ?>">
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
    <div class="card-body mt-1">
      <div class="table-responsive">
        <table class="table table-hover small table-bottom-border align-middle mb-0" id="<?= $table_id ?>">
          <thead class="bg-light-primary">
            <tr>
              <th style="width:40px;"></th>
              <th>Team</th>
              <th>Form</th>
              <th>Date Range</th>
              <th class="text-center">Targets</th>
              <th class="text-center" style="width:240px;">Overall Progress</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($scopes)): ?>
              <?php foreach ($scopes as $idx => $s): ?>
                <?php
                  $rowId       = 'scopeRow' . (int)$s['id'] . '_' . $idx;
                  $badge       = (int)($s['targets_count'] ?? 0);
                  $periodLabel = (html_escape($s['start_date']) . ' → ' . html_escape($s['end_date']));

                  // --- Overall progress calc (sum of all metrics in this scope) ---
                  $details        = is_array($s['details'] ?? null) ? $s['details'] : [];
                  $totalTarget    = 0.0;
                  $totalAchieved  = 0.0;
                  foreach ($details as $m) {
                    $totalTarget   += (float)($m['target']   ?? 0);
                    $totalAchieved += (float)($m['achieved'] ?? 0);
                  }
                  $overallPct = $totalTarget > 0 ? round(($totalAchieved / $totalTarget) * 100, 1) : 0.0;
                  if     ($overallPct >= 100) $overallBar = 'success';
                  elseif ($overallPct >= 90)  $overallBar = 'dark';
                  elseif ($overallPct >= 70)  $overallBar = 'primary';
                  elseif ($overallPct >= 50)  $overallBar = 'info';
                  elseif ($overallPct >= 25)  $overallBar = 'warning';
                  elseif ($overallPct > 0)    $overallBar = 'secondary';
                  else                        $overallBar = 'danger';
                ?>
                <tr>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary rounded-circle px-1 py-0 toggle-scope"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?= $rowId ?>"
                            aria-expanded="false"
                            aria-controls="<?= $rowId ?>"
                            title="Expand">
                      <i class="ti ti-plus"></i>
                    </button>
                  </td>
                  <td><?= html_escape($s['team_name'] ?? '—') ?></td>
                  <td><?= html_escape($s['form_title'] ?? '—') ?></td>
                  <td><?= $periodLabel ?></td>
                  <td class="text-center">
                    <span class="badge bg-teal-600 bg-light-primary"><?= $badge ?> Targets</span>
                  </td>
                  <td class="text-center">
                    <div class="d-flex align-items-center gap-2">
                      <div class="flex-grow-1">
                        <div class="progress" style="height:1rem;">
                          <div class="progress-bar bg-<?= $overallBar ?> progress-bar-striped"
                               style="width: <?= min($overallPct, 100) ?>%;">
                            <?= $overallPct ?>%
                          </div>
                        </div>
                      </div>
                      <div class="text-nowrap small">
                        <strong><?= (float)$totalAchieved ?></strong>/<span class="text-muted"><?= (float)$totalTarget ?></span>
                      </div>
                    </div>
                  </td>
                </tr>

                <!-- Collapsible details row -->
                <tr class="collapse" id="<?= $rowId ?>">
                  <td colspan="6" class="p-0">
                    <div class="border-top px-3 py-2">
                      <div class="table-responsive">
                        <table class="table table-sm small mb-0 table-bottom-border">
                          <thead class="bg-light-primary">
                            <tr>
                              <th style="width:40%">Metric</th>
                              <th class="text-center" style="width:15%">Target</th>
                              <th class="text-center" style="width:15%">Achieved</th>
                              <th style="width:30%">Progress</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($details)): ?>
                              <?php foreach ($details as $m): ?>
                                <?php
                                  $pct = (float)($m['progress_pct'] ?? 0);
                                  if     ($pct >= 100) $bar = 'success';
                                  elseif ($pct >= 90)  $bar = 'dark';
                                  elseif ($pct >= 70)  $bar = 'primary';
                                  elseif ($pct >= 50)  $bar = 'info';
                                  elseif ($pct >= 25)  $bar = 'warning';
                                  elseif ($pct > 0)    $bar = 'secondary';
                                  else                 $bar = 'danger';
                                ?>
                                <tr>
                                  <td><?= html_escape($m['label']) ?></td>
                                  <td class="text-center"><?= html_escape($m['target']) ?></td>
                                  <td class="text-center"><?= html_escape($m['achieved']) ?></td>
                                  <td>
                                    <div class="progress" style="height:1rem;">
                                      <div class="progress-bar bg-<?= $bar ?> progress-bar-striped"
                                           style="width: <?= min($pct, 100) ?>%;">
                                        <?= $pct ?>%
                                      </div>
                                    </div>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <tr>
                                <td colspan="4" class="text-center text-muted py-3">No metrics found for this scope.</td>
                              </tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  No targets found for the selected period (<?= html_escape($filterStart) ?> → <?= html_escape($filterEnd) ?>).
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  // toggle + / – icon on expand/collapse
  document.querySelectorAll('.toggle-scope').forEach(function(btn){
    btn.addEventListener('click', function(){
      var icon = this.querySelector('i');
      setTimeout(() => {
        if (this.getAttribute('aria-expanded') === 'true') {
          icon.classList.remove('ti-plus');
          icon.classList.add('ti-minus');
        } else {
          icon.classList.remove('ti-minus');
          icon.classList.add('ti-plus');
        }
      }, 50);
    });
  });
})();
</script>
