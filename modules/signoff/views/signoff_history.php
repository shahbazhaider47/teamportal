<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= $page_title ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canView   = staff_can('view_own', 'signoff');
        $canExport = staff_can('export', 'general');
        $canPrint  = staff_can('print', 'general');
        $canAssign    = staff_can('assign', 'signoff');

          // Read perf indicators from controller if provided; otherwise pull from options
          $perf = isset($perf_indicators)
              ? strtolower(trim((string)$perf_indicators))
              : (function_exists('get_option') ? strtolower(trim((string)get_option('signoff_perf_indicators'))) : 'none');
        
          if ($perf === '') { $perf = 'none'; }
        
          $showTargets = in_array($perf, ['targets','both'], true);
          $showPoints  = in_array($perf, ['points','both'],  true);

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
         class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
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
      
      <!-- Search -->
      <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
        <input type="text" class="form-control rounded app-form small dynamic-search-input"
               placeholder="Search..."
               aria-label="Search"
               data-table-target="<?= $table_id ?? 'signoffhistoryTable' ?>">
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

  <!-- ===================== FILTER BAR (NEW) ===================== -->
  <?php
    // defaults for view state
    $filters = $filters ?? [
      'mode' => 'all', 'year' => 0, 'month' => '', 'start' => '', 'end' => '', 'status' => '', 'years' => []
    ];
  ?>
  <div class="card mb-3">
    <div class="card-body py-2">
      <form class="row g-2 align-items-end" method="get" action="<?= site_url('signoff/signoff_history') ?>">
        <!-- Mode -->
        <div class="col-12 col-md-2">
          <label class="form-label small mb-1">Filter By</label>
          <select name="mode" id="f_mode" class="form-select form-select-sm">
            <option value="all"   <?= $filters['mode']==='all'?'selected':''; ?>>All</option>
            <option value="year"  <?= $filters['mode']==='year'?'selected':''; ?>>Year</option>
            <option value="month" <?= $filters['mode']==='month'?'selected':''; ?>>Month</option>
            <option value="range" <?= $filters['mode']==='range'?'selected':''; ?>>Date Range</option>
          </select>
        </div>

        <!-- Year -->
        <div class="col-6 col-md-2 mode-field mode-year" <?= $filters['mode']==='year'?'':'style="display:none"'; ?>>
          <label class="form-label small mb-1">Year</label>
          <select name="year" id="f_year" class="form-select form-select-sm">
            <option value="">Select year</option>
            <?php foreach (($filters['years'] ?? []) as $y): ?>
              <option value="<?= (int)$y ?>" <?= (int)$filters['year']===(int)$y?'selected':''; ?>><?= (int)$y ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Month -->
        <div class="col-6 col-md-2 mode-field mode-month" <?= $filters['mode']==='month'?'':'style="display:none"'; ?>>
          <label class="form-label small mb-1">Month</label>
          <input type="month" name="month" id="f_month" class="form-control form-control-sm"
                 value="<?= html_escape($filters['month']) ?>" />
        </div>

        <!-- Range -->
        <div class="col-6 col-md-2 mode-field mode-range" <?= $filters['mode']==='range'?'':'style="display:none"'; ?>>
          <label class="form-label small mb-1">Start</label>
          <input type="date" name="start" id="f_start" class="form-control form-control-sm"
                 value="<?= html_escape($filters['start']) ?>" />
        </div>
        <div class="col-6 col-md-2 mode-field mode-range" <?= $filters['mode']==='range'?'':'style="display:none"'; ?>>
          <label class="form-label small mb-1">End</label>
          <input type="date" name="end" id="f_end" class="form-control form-control-sm"
                 value="<?= html_escape($filters['end']) ?>" />
        </div>

        <!-- Status -->
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Status</label>
          <select name="status" id="f_status" class="form-select form-select-sm">
            <option value="">Any</option>
            <option value="submitted" <?= $filters['status']==='submitted'?'selected':''; ?>>Submitted</option>
            <option value="approved"  <?= $filters['status']==='approved'?'selected':''; ?>>Approved</option>
            <option value="rejected"  <?= $filters['status']==='rejected'?'selected':''; ?>>Rejected</option>
            <option value="excused"   <?= $filters['status']==='excused'?'selected':''; ?>>Excused</option>
          </select>
        </div>

        <!-- Actions -->
        <div class="col-6 col-md-2 text-end">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="ti ti-filter me-1"></i> Apply
          </button>
        </div>
        <div class="col-6 col-md-2">
          <a href="<?= site_url('signoff/signoff_history') ?>" class="btn btn-light-primary btn-sm w-100">
            <i class="ti ti-refresh me-1"></i> Reset
          </a>
        </div>
      </form>
    </div>
  </div>
  <!-- =================== /FILTER BAR (NEW) =================== -->

  <div class="row justify-content-center">
    <div class="col-lg-8 col-xl-12">
      <div class="card">
        <div class="card-body">
          <?php if (!empty($history)): ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover small align-middle mb-0" id="signoffhistoryTable">
                <thead class="bg-light-primary">
                  <tr>
                    <th width="12%">Submission Date</th>
                    <th width="28%">Form Title</th>
                    <th width="20%">Team</th>
                    <th width="15%">Status</th>
                    <th width="15%">Points Earned</th>                    
                    <th width="15%" class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($history as $row): ?>
                  <?php
                    $status = strtolower((string)($row['status'] ?? ''));
                    $status_label = 'secondary';
                    if ($status === 'approved')     $status_label = 'success';
                    elseif ($status === 'rejected') $status_label = 'danger';
                    elseif ($status === 'submitted')$status_label = 'primary';

                    $modalId = 'signoffSubmissionModal' . (int)$row['id'];
                  ?>
                  <tr>
                    <td><?= date('Y-M-d', strtotime($row['submission_date'])) ?></td>
                    <td><?= html_escape($row['form_title'] ?? '—') ?></td>
                    <td><?= isset($row['team_name']) ? html_escape($row['team_name']) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                      <span class="badge bg-<?= $status_label ?>">
                        <?= ucfirst($status ?: '—') ?>
                      </span>
                      <?php if (!empty($row['status']) && $row['status'] === 'excused'): ?>
                        <span class="badge bg-info">Excused</span>
                      <?php endif; ?>
                    </td>
                    <td><?= html_escape($row['total_points'] ?? '—') ?></td>
                    <td class="text-end">
                      <a href="#"
                         class="btn btn-outline-primary btn-ssm"
                         data-bs-toggle="modal"
                         data-bs-target="#<?= $modalId ?>"
                         title="View Submission">
                        <i class="ti ti-eye"></i> View
                      </a>
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
  </div>
</div>

<?php
// ---------- Reuse the shared modal for each row ----------
if (!empty($history)) {
  $CI =& get_instance(); // use CI superobject in views
  foreach ($history as $row) {
    $form_fields_json = isset($row['form_fields'])
      ? $row['form_fields']
      : (isset($forms[$row['form_id']]['fields']) ? $forms[$row['form_id']]['fields'] : '[]');

    $CI->load->view('signoff/modals/view_submissions_modal', [
      'row'              => $row,
      'form_fields_json' => $form_fields_json,
      'can_review'       => false,
    ]);
  }
}
?>

<!-- Tiny JS to toggle filter inputs by mode (no external deps) -->
<script>
(function() {
  const modeSel = document.getElementById('f_mode');
  const blocks  = document.querySelectorAll('.mode-field');

  function sync() {
    const val = modeSel ? modeSel.value : 'all';
    blocks.forEach(b => {
      b.style.display = 'none';
    });
    if (val === 'year') {
      document.querySelectorAll('.mode-year').forEach(b => b.style.display = '');
    } else if (val === 'month') {
      document.querySelectorAll('.mode-month').forEach(b => b.style.display = '');
    } else if (val === 'range') {
      document.querySelectorAll('.mode-range').forEach(b => b.style.display = '');
    }
  }

  if (modeSel) {
    modeSel.addEventListener('change', sync);
    sync(); // initialize
  }
})();
</script>
