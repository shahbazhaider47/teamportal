<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
  // Expected data from controller:
  // $page_title, $month_param, $form_id_param, $forms_for_user, $rows,
  // $total_points, $total_submissions, $month_options, $month_start, $month_end

  $page_title        = $page_title        ?? 'My Signoff Points';
  $month_param       = $month_param       ?? date('Y-m');
  $form_id_param     = (int)($form_id_param ?? 0);
  $forms_for_user    = $forms_for_user    ?? [];
  $rows              = $rows              ?? [];
  $total_points      = (float)($total_points ?? 0);
  $total_submissions = (int)($total_submissions ?? 0);
  $month_options     = $month_options     ?? [];

  // ---- Prep analytics for charts (client-side) ----
  // 1) Daily totals (submission_date => sum(points))
  $byDate = [];
  foreach ($rows as $r) {
      $d = isset($r['submission_date']) ? (string)$r['submission_date'] : date('Y-m-d', strtotime($r['created_at'] ?? 'now'));
      $byDate[$d] = ($byDate[$d] ?? 0) + (float)$r['points'];
  }
  ksort($byDate);

  // 2) Points by form
  $byForm = [];
  foreach ($rows as $r) {
      $ft = (string)($r['form_title'] ?? ('Form #'.(int)$r['form_id']));
      $byForm[$ft] = ($byForm[$ft] ?? 0) + (float)$r['points'];
  }
  arsort($byForm);

  // 3) Derived metrics
  $avg_points = $total_submissions > 0 ? ($total_points / $total_submissions) : 0.0;
  $top_form   = '';
  $top_form_points = 0.0;
  if ($byForm) {
      $tmp = $byForm;
      $firstKey = array_key_first($tmp);
      $top_form = $firstKey;
      $top_form_points = (float)$tmp[$firstKey];
  }

  // Encode for JS safely
  $chartDailyLabels = array_keys($byDate);
  $chartDailyData   = array_values($byDate);
  $chartFormLabels  = array_keys($byForm);
  $chartFormData    = array_values($byForm);

  $JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>
<style>
  /* Light polish for analytics cards & charts */
  .kpi-card .label { font-size: 12px; color: #6c757d; }
  .kpi-card .value { font-size: 24px; font-weight: 600; }
  .chart-card canvas { width: 100% !important; height: 320px !important; }
  .form-small{
      font-size: 12px;
  }
</style>

<div class="container-fluid">
<!-- ===== Header (your exact snippet) ===== -->
<div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <h1 class="h6 header-title"><?= $page_title ?></h1>
  </div>

  <?php
    $canView      = staff_can('view_own', 'signoff');
    $canAssign    = staff_can('assign', 'signoff');
    $canApprove   = staff_can('approve', 'signoff');
    $canExport    = staff_can('export', 'general');
    $canPrint     = staff_can('print', 'general');
  
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

        $CI =& get_instance();
        // Role-based visibility for "Team Signoff" button
        $empRole = strtolower((string)($CI->session->userdata('emp_role') ?? $CI->session->userdata('user_role') ?? ''));
        $showTeamSignoffBtn = in_array($empRole, ['teamlead', 'manager'], true);          
  ?>

  <div class="d-flex align-items-center gap-2 flex-wrap">
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
         class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
         title="My Points">
         <i class="ti ti-trophy"></i> My Points
      </a>
    <?php endif; ?>

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
      
      <!-- Filters -->
        <div class="card-body text-end">
          <form method="get" class="row g-2 align-items-end ap-form">
            <div class="col-md-4">
              <select name="month" class="form-select form-select-sm form-small">
                <?php foreach ($month_options as $opt): ?>
                  <option class="small" value="<?= html_escape($opt['value']) ?>" <?= ($opt['value']===$month_param?'selected':'') ?>>
                    <?= html_escape($opt['label']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
    
            <div class="col-md-5">
              <select name="form_id" class="form-select form-select-sm form-small">
                <option value="0">All Forms</option>
                <?php foreach ($forms_for_user as $f): ?>
                  <option value="<?= (int)$f['id'] ?>" <?= ($form_id_param === (int)$f['id'] ? 'selected' : '') ?>>
                    <?= html_escape($f['title']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
    
            <div class="col-md-3">
              <button type="submit" class="btn btn-primary btn-ssm" title="Apply Filters">
                <i class="ti ti-filter"></i>
              </button>
              <a href="<?= site_url('signoff/points/my_points') ?>" class="btn btn-light-primary btn-ssm" title="Reset Filters"><i class="ti ti-refresh"></i></a>
            </div>
          </form>
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
<!-- ===== /Header ===== -->
    
  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card shadow-sm kpi-card">
        <div class="card-body">
          <div class="label">Total Points (<?= html_escape(date('F Y', strtotime($month_param.'-01'))) ?>)</div>
          <div class="value"><?= number_format($total_points, 2) ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card shadow-sm kpi-card">
        <div class="card-body">
          <div class="label">Submissions</div>
          <div class="value"><?= (int)$total_submissions ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card shadow-sm kpi-card">
        <div class="card-body">
          <div class="label">Avg Points / Submission</div>
          <div class="value"><?= number_format($avg_points, 2) ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card shadow-sm kpi-card">
        <div class="card-body">
          <div class="label">Top Form</div>
          <div class="value" title="<?= html_escape($top_form) ?>">
            <?= $top_form ? html_escape($top_form) : '—' ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="row g-3">
 <div class="col-md-7">     
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bottom-border small align-middle">
          <thead class="bg-light-primary">
            <tr>
              <th style="width: 140px;">Submission Date</th>
              <th>Assigned Form</th>
              <th class="text-end" style="width: 140px;">Points Gained</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="3" class="text-center text-muted py-4">No submissions found for the selected period.</td></tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= html_escape($r['submission_date'] ?? date('Y-m-d', strtotime($r['created_at'] ?? 'now'))) ?></td>
                  <td><?= html_escape($r['form_title']) ?></td>
                  <td class="text-end"><?= number_format((float)$r['points'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if ($rows): ?>
            <tfoot class="table-light">
              <tr>
                <th colspan="2" class="text-end">Total</th>
                <th class="text-end"><?= number_format($total_points, 2) ?></th>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>
  </div>
  
  <!-- Charts -->
<div class="col-md-5">   
    <div class="col-12">
      <div class="card shadow-sm chart-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">Points Trend (Daily)</div>
            <small class="text-muted"><?= html_escape($month_start) ?> → <?= html_escape($month_end) ?></small>
          </div>
          <canvas id="pointsTrend"></canvas>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card shadow-sm chart-card">
        <div class="card-body">
          <div class="fw-semibold mb-2">Points by Form</div>
          <canvas id="pointsByForm"></canvas>
        </div>
      </div>
    </div>
</div>
</div>
</div>

<!-- Chart.js (lightweight, professional defaults) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" integrity="sha384-mZ4W6wT3e9Cxn7Y8n7nX8C3I5Jg6q7C6q5g8mOluqTzqg2D9U0kz2jOeQmT1nq5A" crossorigin="anonymous"></script>
<!-- Robust Chart bootstrap: CDN with local fallback, guards, and no-data UX -->
<script>
(function(){
  // Data embedded by PHP
  const dailyLabels = <?= json_encode($chartDailyLabels ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
  const dailyData   = <?= json_encode($chartDailyData   ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
  const formLabels  = <?= json_encode($chartFormLabels  ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
  const formData    = <?= json_encode($chartFormData    ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;

  // Debug: see what's actually rendered into the page
  console.log('[MyPoints] dailyLabels', dailyLabels);
  console.log('[MyPoints] dailyData', dailyData);
  console.log('[MyPoints] formLabels', formLabels);
  console.log('[MyPoints] formData', formData);

  // Helper: show a friendly empty-state inside a chart card
  function showNoData(canvasId, msg){
    const el = document.getElementById(canvasId);
    if (!el) return;
    const parent = el.closest('.chart-card .card-body') || el.parentElement;
    if (!parent) return;
    const note = document.createElement('div');
    note.className = 'text-muted small text-center py-4';
    note.textContent = msg || 'No data for the selected period.';
    parent.appendChild(note);
    el.style.display = 'none';
  }

  // Load Chart.js with fallback to a local copy
  function loadChartJs(callback){
    if (window.Chart) return callback();
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
    s.onload = callback;
    s.onerror = function(){
      console.warn('[MyPoints] CDN failed, trying local Chart.js');
      var l = document.createElement('script');
      // Put a local copy at this path, or adjust to your assets path
      l.src = '<?= base_url('assets/js/chart.umd.min.js') ?>';
      l.onload = callback;
      l.onerror = function(){
        console.error('[MyPoints] Could not load Chart.js from CDN or local.');
        showNoData('pointsTrend');
        showNoData('pointsByForm');
      };
      document.head.appendChild(l);
    };
    document.head.appendChild(s);
  }

  function initCharts(){
    try {
      // If no data, render helpful empty states
      if (!Array.isArray(dailyLabels) || dailyLabels.length === 0 || !Array.isArray(dailyData) || dailyData.length === 0) {
        showNoData('pointsTrend', 'No daily points recorded in this period.');
      }
      if (!Array.isArray(formLabels) || formLabels.length === 0 || !Array.isArray(formData) || formData.length === 0) {
        showNoData('pointsByForm', 'No form points available in this period.');
      }

      // If both are empty, nothing to chart
      const hasTrend = Array.isArray(dailyLabels) && dailyLabels.length && Array.isArray(dailyData) && dailyData.length;
      const hasForms = Array.isArray(formLabels) && formLabels.length && Array.isArray(formData) && formData.length;
      if (!hasTrend && !hasForms) return;

      const palette = [
        '#2E5BFF', '#00C1D4', '#7C3AED', '#F59E0B', '#10B981',
        '#EF4444', '#3B82F6', '#8B5CF6', '#14B8A6', '#F97316'
      ];

      if (hasTrend) {
        const ctxLine = document.getElementById('pointsTrend');
        new Chart(ctxLine, {
          type: 'line',
          data: {
            labels: dailyLabels,
            datasets: [{
              label: 'Points',
              data: dailyData,
              borderColor: '#2E5BFF',
              backgroundColor: 'rgba(46,91,255,0.12)',
              borderWidth: 2,
              tension: 0.25,
              pointRadius: 3,
              fill: true,
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: { grid: { display: false } },
              y: { beginAtZero: true, ticks: { precision: 0 } }
            },
            plugins: {
              legend: { display: false },
              tooltip: { mode: 'index', intersect: false }
            }
          }
        });
      }

      if (hasForms) {
        const ctxBar = document.getElementById('pointsByForm');
        const maxBars = 10;
        const labels = formLabels.slice(0, maxBars);
        const data   = formData.slice(0, maxBars);
        const colors = labels.map((_, i) => palette[i % palette.length]);

        new Chart(ctxBar, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: 'Points',
              data: data,
              backgroundColor: colors,
              borderColor: colors,
              borderWidth: 1
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: { beginAtZero: true, ticks: { precision: 0 } },
              y: { grid: { display: false } }
            },
            plugins: {
              legend: { display: false },
              tooltip: { mode: 'nearest', intersect: true }
            }
          }
        });
      }
    } catch (e) {
      console.error('[MyPoints] Chart init error:', e);
      showNoData('pointsTrend');
      showNoData('pointsByForm');
    }
  }

  // Ensure DOM is ready, then load Chart.js and initialize
  function onReady(fn){
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  onReady(function(){
    loadChartJs(initCharts);
  });
})();
</script>
