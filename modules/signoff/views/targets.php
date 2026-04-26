<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
  // Ensure variables exist
  $page_title     = $page_title     ?? 'Assigned Signoff Targets';
  $targets        = $targets        ?? [];   // array of scopes from model
  $teams          = $teams          ?? [];
  $forms          = $forms          ?? [];   // optional map id => form row
  $forms_by_team  = $forms_by_team  ?? [];   // optional for "Assign Targets" modal
?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title) ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canView   = staff_can('view_global', 'signoff');
        $canExport = staff_can('export', 'general');
        $canPrint  = staff_can('print', 'general');

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
              
      ?>

      <a href="<?= site_url('signoff') ?>" class="btn btn-outline-primary btn-header" title="Signoff Details">
        <i class="ti ti-calendar me-1"></i> Signoff
      </a>

      <a href="<?= $canView ? site_url('signoff/forms') : 'javascript:void(0);' ?>"
         class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
         title="Signoff Forms">
         <i class="ti ti-file-stack"></i> Forms
      </a>

      <?php if ($showTargets): ?>
        <a href="<?= $canView ? site_url('signoff/targets') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
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

      <button class="btn btn-primary btn-header" data-bs-toggle="modal" data-bs-target="#assignTargetModal">
        <i class="ti ti-plus"></i> Assign Targets
      </button>

      <!-- Search -->
      <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
        <input type="text"
               class="form-control rounded app-form small dynamic-search-input"
               placeholder="Search..."
               aria-label="Search"
               data-table-target="scopetargetsTable">
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

  <div class="card">
    <div class="card-body mt-2">
      <div class="table-responsive">
        <table class="table small align-middle mb-0" id="scopetargetsTable">
          <thead class="bg-light-primary">
            <tr>
              <th style="width:48px;"></th>
              <th>Team</th>
              <th>Form</th>
              <th>Date Range</th>
              <th>Targets</th>
              <th style="width:160px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($targets)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted">No targets assigned.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($targets as $row): ?>
                <?php
                  // Safe reads
                  $row_id         = (int)($row['id'] ?? 0);
                  $team_id_val    = (int)($row['team_id'] ?? 0);
                  $team_name      = trim((string)($row['team_name'] ?? ''));
                  $form_title     = trim((string)($row['form_title'] ?? ''));
                  $start_date     = (string)($row['start_date'] ?? '');
                  $end_date       = (string)($row['end_date'] ?? '');
                  $metrics        = is_array($row['targets_list'] ?? null) ? $row['targets_list'] : [];

                  $collapseId     = 'scopeRow_' . $row_id;

                  $teamLabel      = ($team_id_val === 0) ? 'Global (All Teams)' : ($team_name !== '' ? $team_name : '—');
                  $formLabel      = ($form_title !== '' ? $form_title : '—');
                  $dateRangeLabel = ($start_date && $end_date) ? (html_escape($start_date) . ' → ' . html_escape($end_date)) : '—';

                  // JSON for data attribute (safe-encode)
                  $metrics_json   = json_encode($metrics, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
                ?>
                <tr>
                  <td class="text-center">
                    <button class="btn btn-light btn-sm rounded-circle p-1"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?= $collapseId ?>"
                            aria-expanded="false"
                            aria-controls="<?= $collapseId ?>"
                            title="Show Targets">
                      <i class="ti ti-plus"></i>
                    </button>
                  </td>
                  <td><?= html_escape($teamLabel) ?></td>
                  <td><?= html_escape($formLabel) ?></td>
                  <td><?= $dateRangeLabel ?></td>
                  <td>
                    <?php if (!empty($metrics)): ?>
                      <span class="badge bg-primary"><?= count($metrics) ?> target(s)</span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="btn btn-outline-primary btn-sm btn-edit-scope"
                            data-bs-toggle="modal"
                            data-bs-target="#editScopeTargetsModal"
                            data-row-id="<?= $row_id ?>"
                            data-team-label="<?= html_escape($teamLabel) ?>"
                            data-form-label="<?= html_escape($formLabel) ?>"
                            data-start-date="<?= html_escape($start_date) ?>"
                            data-end-date="<?= html_escape($end_date) ?>"
                            data-metrics='<?= $metrics_json ?>'>
                      <i class="ti ti-edit"></i> Edit Scope
                    </button>
                  </td>
                </tr>

                <!-- Collapsible targets (view-only list) -->
                <tr class="collapse-row">
                  <td colspan="6" class="p-0 border-0">
                    <div class="collapse mt-0" id="<?= $collapseId ?>">
                      <div class="card-body py-2 px-0">
                        <table class="table table-sm table-hover table-bordered mb-0">
                          <thead>
                            <tr>
                              <th>Metric</th>
                              <th class="text-center" style="width:160px;">Target</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($metrics)): ?>
                              <?php foreach ($metrics as $metric): ?>
                                <?php
                                  $label = (string)($metric['field_label'] ?? ($metric['field'] ?? '—'));
                                  $t     = (float)($metric['target_value']   ?? 0);
                                ?>
                                <tr>
                                  <td><?= html_escape($label) ?></td>
                                  <td class="text-center"><?= html_escape($t) ?></td>
                                </tr>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <tr>
                                <td colspan="2" class="text-center text-muted">No numeric/amount targets for this form.</td>
                              </tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
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

<!-- Edit Scope Modal (single, reusable; populated via JS) -->
<div class="modal fade" id="editScopeTargetsModal" tabindex="-1" aria-labelledby="editScopeTargetsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form id="editScopeForm" method="post" action="#">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="editScopeTargetsLabel">
            <i class="ti ti-edit me-2"></i>
            Edit Scope
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" id="editStartDate" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" id="editEndDate" class="form-control" required>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
              <thead>
                <tr>
                  <th>Metric</th>
                  <th style="width:180px;">Target</th>
                </tr>
              </thead>
              <tbody id="editTargetsBody">
                <!-- JS will inject rows here -->
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-device-floppy me-1"></i> Save Changes
          </button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- /Edit Scope Modal -->

<?php
// Assignment modal (handles "Assign Targets" button)
$CI =& get_instance();
$CI->load->view('signoff/modals/targets_modal', [
  'teams'         => $teams,
  'forms'         => $forms,          // optional map, used by modal to show "By Forms"
  'forms_by_team' => $forms_by_team,  // used by modal JS for "By Teams"
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

<script>
(function() {
  // Bootstrap modal element
  const modalEl = document.getElementById('editScopeTargetsModal');
  if (!modalEl) return;

  // When the modal is shown, populate it from the triggering button
  modalEl.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    if (!btn) return;

    // Read data-* from the Edit button
    const rowId      = btn.getAttribute('data-row-id') || '';
    const teamLabel  = btn.getAttribute('data-team-label') || '';
    const formLabel  = btn.getAttribute('data-form-label') || '';
    const startDate  = btn.getAttribute('data-start-date') || '';
    const endDate    = btn.getAttribute('data-end-date') || '';
    const metricsRaw = btn.getAttribute('data-metrics') || '[]';

    let metrics = [];
    try { metrics = JSON.parse(metricsRaw); } catch(e) { metrics = []; }

    // Set form action
    const form = document.getElementById('editScopeForm');
    form.setAttribute('action', '<?= site_url('signoff/targets/update_scope/') ?>' + rowId);

    // Set header title
    const titleEl = document.getElementById('editScopeTargetsLabel');
    titleEl.textContent = 'Edit Scope — ' + (teamLabel || '—') + ' / ' + (formLabel || '—');

    // Fill dates
    document.getElementById('editStartDate').value = startDate || '';
    document.getElementById('editEndDate').value   = endDate   || '';

    // Build targets rows (metric + target input)
    const tbody = document.getElementById('editTargetsBody');
    tbody.innerHTML = '';

    if (Array.isArray(metrics) && metrics.length) {
      metrics.forEach(function(m) {
        const field = (m && (m.field ?? '')) + '';
        const label = (m && (m.field_label ?? field)) + '';
        const t     = parseFloat(m && m.target_value) || 0;

        const tr = document.createElement('tr');

        const tdLabel = document.createElement('td');
        tdLabel.textContent = label || '—';

        const tdTarget = document.createElement('td');
        tdTarget.className = 'text-center';
        tdTarget.innerHTML = '<input type="number" step="0.01" min="0" ' +
                             'name="targets[' + field.replace(/"/g,'&quot;') + ']" ' +
                             'value="' + t + '" class="form-control form-control-sm" required>';

        tr.appendChild(tdLabel);
        tr.appendChild(tdTarget);
        tbody.appendChild(tr);
      });
    } else {
      const tr = document.createElement('tr');
      tr.innerHTML = '<td colspan="2" class="text-center text-muted">No numeric/amount fields in this form.</td>';
      tbody.appendChild(tr);
    }
  });

})();
</script>
