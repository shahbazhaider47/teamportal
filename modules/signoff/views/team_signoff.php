<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$CI =& get_instance();

/* ------------------------------------------------------------------
 *  Context / maps from controller
 * ------------------------------------------------------------------ */
$team_members  = isset($team_members)  && is_array($team_members)  ? $team_members  : [];
$positions_map = isset($positions_map) && is_array($positions_map) ? $positions_map : [];
$submissions   = isset($submissions)   && is_array($submissions)   ? $submissions   : [];
$teamName      = (isset($team_name) && $team_name !== '') ? $team_name : 'My Team';
$table_id      = isset($table_id) ? $table_id : 'teamsignoffTable';

// Pagination vars
$pagination  = isset($pagination)  ? $pagination  : '';
$total_rows  = isset($total_rows)  ? (int)$total_rows  : 0;
$per_page    = isset($per_page)    ? (int)$per_page    : 100;
$page        = isset($page)        ? (int)$page        : 1;
$total_pages = $per_page > 0 ? max(1, (int)ceil($total_rows / $per_page)) : 1;

// Current filter state (from controller — do NOT read $_GET directly)
$currentMonth  = isset($month)          && preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', (string)$month) ? $month : date('Y-m');
$currentStatus = isset($status)         ? strtolower(trim((string)$status))  : '';
$currentUserId = isset($filter_user_id) ? (int)$filter_user_id               : 0;
$currentYear   = isset($filter_year)    ? (int)$filter_year                  : 0;

// Year range for filter dropdown (last 5 years)
$yearNow   = (int)date('Y');
$yearRange = range($yearNow, $yearNow - 4);

// Permissions
$canView    = staff_can('view_global', 'signoff');
$canApprove = staff_can('approve',     'signoff');
$canExport  = staff_can('export',      'general');
$canPrint   = staff_can('print',       'general');
$can_review = staff_can('own_team',    'signoff')
           || staff_can('view_global', 'signoff')
           || staff_can('approve',     'signoff');

// Perf indicators
$perf        = isset($perf_indicators) ? strtolower(trim((string)$perf_indicators)) : 'none';
if ($perf === '') { $perf = 'none'; }
$showTargets = in_array($perf, ['targets', 'both'], true);
$showPoints  = in_array($perf, ['points',  'both'], true);

// Total colspan
$base_cols  = 7; // EMP ID, Name, Designation, Date, Form, Status, Actions
$total_cols = $base_cols + ($showPoints ? 1 : 0) + ($showTargets ? 1 : 0);

// Role-based button visibility
$empRole            = strtolower((string)($CI->session->userdata('user_role') ?? ''));
$showTeamSignoffBtn = in_array($empRole, ['teamlead', 'manager'], true);

// Numeric formatter
$fmtNum = static function ($v) {
    if ($v === null || $v === '' || !is_numeric($v)) { return '—'; }
    $f = (float)$v;
    return fmod($f, 1.0) === 0.0 ? number_format($f, 0) : number_format($f, 2);
};
?>

<div class="container-fluid">

  <!-- =================== PAGE HEADER =================== -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title mb-0">
        <?= isset($page_title) ? html_escape($page_title) : 'My Team Signoff' ?>
        <i class="ti ti-chevron-right"></i>
        <span class="text-muted small"><?= html_escape($teamName) ?></span>
      </h1>
      <?php if ($total_rows > 0): ?>
        <span class="badge bg-light-primary text-primary fw-normal">
          <?= number_format($total_rows) ?> record<?= $total_rows != 1 ? 's' : '' ?>
        </span>
      <?php endif; ?>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">

      <a href="<?= base_url('signoff') ?>" class="btn btn-outline-primary btn-header">
        <i class="ti ti-calendar me-1"></i> My Signoff
      </a>

      <a href="<?= $canView ? site_url('signoff/forms') : 'javascript:void(0);' ?>"
         class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>">
        <i class="ti ti-file-stack me-1"></i> Forms
      </a>

      <?php if ($showTargets): ?>
        <a href="<?= $canView ? site_url('signoff/targets') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>">
          <i class="ti ti-target-arrow me-1"></i> Targets
        </a>
      <?php endif; ?>

      <?php if ($showPoints): ?>
        <a href="<?= $canView ? site_url('signoff/points') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>">
          <i class="ti ti-trophy me-1"></i> Points
        </a>
      <?php endif; ?>

      <div class="btn-divider"></div>

      <?php if ($showTeamSignoffBtn): ?>
        <a href="<?= site_url('signoff/team_signoff') ?>" class="btn btn-header btn-primary active">
          <i class="ti ti-users me-1"></i> Team Signoff
        </a>
      <?php endif; ?>

      <?php if ($canExport): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                title="Export to Excel"
                data-export-filename="team_signoff_export">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>

      <?php if ($canPrint): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                title="Print Table"
                data-print-table-id="<?= html_escape($table_id) ?>">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>

    </div>
  </div>
  <!-- /PAGE HEADER -->

  <div class="card shadow-sm">
    <div class="card-body">

      <!-- =================== SERVER-SIDE FILTER FORM =================== -->
      <form method="GET" action="<?= site_url('signoff/team_signoff') ?>"
            class="bg-light-primary p-3 rounded-3 mb-3">
        <div class="row g-2 align-items-end small">

          <div class="col-6 col-lg-2">
            <label class="form-label small fw-semibold mb-1">Month</label>
            <input type="month" name="month" class="form-control form-control-sm"
                   value="<?= html_escape($currentMonth) ?>">
          </div>

          <div class="col-6 col-lg-1">
            <label class="form-label small fw-semibold mb-1">Year</label>
            <select name="year" class="form-select form-select-sm">
              <option value="">All</option>
              <?php foreach ($yearRange as $yy): ?>
                <option value="<?= (int)$yy ?>" <?= $currentYear === (int)$yy ? 'selected' : '' ?>>
                  <?= (int)$yy ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label small fw-semibold mb-1">Team Member</label>
            <select name="user_id" class="form-select form-select-sm">
              <option value="">All</option>
              <?php foreach ($team_members as $uid => $m): ?>
                <?php $mName = $m['fullname'] ?? trim(($m['firstname'] ?? '') . ' ' . ($m['lastname'] ?? '')); ?>
                <option value="<?= (int)$uid ?>" <?= $currentUserId === (int)$uid ? 'selected' : '' ?>>
                  <?= html_escape($mName) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="submitted" <?= $currentStatus === 'submitted' ? 'selected' : '' ?>>Submitted</option>
              <option value="approved"  <?= $currentStatus === 'approved'  ? 'selected' : '' ?>>Approved</option>
              <option value="rejected"  <?= $currentStatus === 'rejected'  ? 'selected' : '' ?>>Rejected</option>
              <option value="excused"   <?= $currentStatus === 'excused'   ? 'selected' : '' ?>>Excused</option>
            </select>
          </div>

          <div class="col-12 col-lg-3 d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
              <label class="form-label small fw-semibold mb-1 d-none d-lg-block">&nbsp;</label>
              <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="ti ti-filter me-1"></i> Filter
              </button>
            </div>
            <div>
              <label class="form-label small fw-semibold mb-1 d-none d-lg-block">&nbsp;</label>
              <a href="<?= site_url('signoff/team_signoff') ?>"
                 class="btn btn-sm btn-outline-secondary" title="Clear all filters">
                <i class="ti ti-refresh"></i>
              </a>
            </div>
          </div>

        </div>
      </form>
      <!-- /FILTER FORM -->

      <!-- Record count summary -->
      <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
        <span>
          <?php if ($total_rows > 0): ?>
            Showing
            <?= number_format(($page - 1) * $per_page + 1) ?>–<?= number_format(min($page * $per_page, $total_rows)) ?>
            of <?= number_format($total_rows) ?> record<?= $total_rows != 1 ? 's' : '' ?>
          <?php else: ?>
            No records found
          <?php endif; ?>
        </span>
        <?php if ($total_pages > 1): ?>
          <span>Page <?= (int)$page ?> of <?= (int)$total_pages ?></span>
        <?php endif; ?>
      </div>

      <!-- =================== TABLE =================== -->
      <div class="table-responsive">
        <table class="table table-hover table-sm small align-middle mb-0"
               id="<?= html_escape($table_id) ?>">
          <thead class="bg-light-primary">
            <tr>
              <th>EMP ID</th>
              <th>Employee</th>
              <th>Designation</th>
              <th>Date</th>
              <th>Form</th>
              <th>Status</th>
              <?php if ($showPoints): ?>   <th>Points</th>   <?php endif; ?>
              <?php if ($showTargets): ?>  <th>Achieved</th> <?php endif; ?>
              <th class="text-end">Actions</th>
            </tr>
          </thead>

          <tbody>
            <?php if (!empty($submissions)): ?>
              <?php foreach ($submissions as $row): ?>
                <?php
                  $rowId     = (int)($row['id'] ?? 0);
                  $rowStatus = strtolower((string)($row['status'] ?? ''));
                  $statusCls = match($rowStatus) {
                    'approved'  => 'success',
                    'rejected'  => 'danger',
                    'submitted' => 'info',
                    'excused'   => 'warning',
                    default     => 'secondary',
                  };

                  $userId   = (int)($row['user_id'] ?? 0);
                  $member   = $team_members[$userId] ?? [];
                  $fullName = $member['fullname']
                      ?? trim(($member['firstname'] ?? '') . ' ' . ($member['lastname'] ?? ''));
                  if ($fullName === '' && !empty($row['fullname'])) { $fullName = $row['fullname']; }
                  if ($fullName === '') { $fullName = 'User #' . $userId; }

                  $positionId   = (int)($member['emp_title'] ?? $row['emp_title'] ?? 0);
                  $positionName = $positions_map[$positionId] ?? '—';
                  $empId        = $member['emp_id'] ?? ($row['emp_id'] ?? '');

                  $dateISO   = !empty($row['submission_date']) ? date('Y-m-d', strtotime($row['submission_date'])) : '';
                  $formTitle = $row['form_title'] ?? '—';

                  $tpFmt = $fmtNum($row['total_points']     ?? null);
                  $atFmt = $fmtNum($row['achieved_targets'] ?? null);

                  // Modal IDs — must match what modal view files generate exactly
                  $viewModalId = 'signoffSubmissionModal' . $rowId;
                  $editModalId = 'signoffSubmissionEditModal' . $rowId;
                ?>
                <tr>
                  <td><?= function_exists('emp_id_display') ? emp_id_display($empId) : html_escape($empId) ?></td>
                  <td>
                    <?= function_exists('user_profile_image')
                        ? user_profile_image($fullName)
                        : html_escape($fullName) ?>
                  </td>
                  <td><?= html_escape($positionName) ?></td>
                  <td class="text-nowrap"><?= $dateISO ? date('d M Y', strtotime($dateISO)) : '—' ?></td>
                  <td><?= html_escape($formTitle) ?></td>
                  <td>
                    <span class="badge bg-<?= $statusCls ?>">
                      <?= ucfirst($rowStatus ?: '—') ?>
                    </span>
                  </td>
                  <?php if ($showPoints): ?>
                    <td><?= html_escape($tpFmt) ?></td>
                  <?php endif; ?>
                  <?php if ($showTargets): ?>
                    <td><?= html_escape($atFmt) ?></td>
                  <?php endif; ?>
                  <td class="text-end text-nowrap">
                    <div class="btn-group btn-group-sm">

                      <!-- View — matches signoffSubmissionModal{id} -->
                      <a href="#"
                         class="btn btn-outline-secondary"
                         data-bs-toggle="modal"
                         data-bs-target="#<?= $viewModalId ?>"
                         title="View Submission">
                        <i class="ti ti-eye"></i>
                      </a>

                      <!-- Edit — matches signoffSubmissionEditModal{id} -->
                      <?php if ($canApprove || $canView): ?>
                        <a href="#"
                           class="btn btn-outline-primary"
                           data-bs-toggle="modal"
                           data-bs-target="#<?= $editModalId ?>"
                           title="Edit Submission">
                          <i class="ti ti-pencil"></i>
                        </a>
                      <?php endif; ?>

                      <!-- Contextual review actions -->
                      <?php if ($can_review): ?>

                        <?php if ($rowStatus === 'submitted'): ?>
                          <a href="<?= base_url('signoff/review_submission/' . $rowId . '/approved') ?>"
                             class="btn btn-outline-success"
                             onclick="return confirm('Approve this submission?');"
                             title="Approve"><i class="ti ti-check"></i></a>
                          <a href="<?= base_url('signoff/review_submission/' . $rowId . '/rejected') ?>"
                             class="btn btn-outline-danger"
                             onclick="return confirm('Reject this submission?');"
                             title="Reject"><i class="ti ti-x"></i></a>
                          <a href="<?= base_url('signoff/review_submission/' . $rowId . '/excused') ?>"
                             class="btn btn-outline-warning"
                             onclick="return confirm('Mark as Excused?');"
                             title="Excuse"><i class="ti ti-circle-check"></i></a>

                        <?php elseif ($rowStatus === 'approved'): ?>
                          <a href="<?= base_url('signoff/review_submission/' . $rowId . '/rejected') ?>"
                             class="btn btn-outline-danger"
                             onclick="return confirm('Reject this approved submission?');"
                             title="Reject"><i class="ti ti-x"></i></a>
                          <a href="<?= base_url('signoff/review_submission/' . $rowId . '/excused') ?>"
                             class="btn btn-outline-warning"
                             onclick="return confirm('Mark as Excused?');"
                             title="Excuse"><i class="ti ti-circle-check"></i></a>

                        <?php elseif ($rowStatus === 'rejected' || $rowStatus === 'excused'): ?>
                          <a href="<?= base_url('signoff/review_submission/' . $rowId . '/approved') ?>"
                             class="btn btn-outline-success"
                             onclick="return confirm('Approve this submission?');"
                             title="Approve"><i class="ti ti-check"></i></a>
                        <?php endif; ?>

                      <?php endif; ?>

                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="<?= (int)$total_cols ?>" class="text-center text-muted py-5">
                  <i class="ti ti-inbox fs-3 d-block mb-2 opacity-50"></i>
                  No signoff submissions found for your team in this period.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <!-- /TABLE -->

        <?php if ($total_pages > 1 && !empty($pagination)): ?>
          <div class="mt-3 pt-2 border-top">
            
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 small text-muted mb-2">
              <span>
                Showing 
                <?= number_format(($page - 1) * $per_page + 1) ?>
                –
                <?= number_format(min($page * $per_page, $total_rows)) ?>
                of <?= number_format($total_rows) ?> record(s)
              </span>
        
              <span>
                Page <?= (int)$page ?> of <?= (int)$total_pages ?>
              </span>
            </div>
        
            <div class="d-flex justify-content-end">
              <?= $pagination ?>
            </div>
        
          </div>
        <?php endif; ?>

    </div>
  </div>
</div>

<?php
/* ------------------------------------------------------------------
 *  Modals — one pair per visible row
 *  form_fields_json is now joined in the SELECT (f.fields AS form_fields_json)
 *  so NO extra DB query per row needed here.
 *  Modal IDs must match exactly:
 *    View : signoffSubmissionModal{id}       (view_submissions_modal.php)
 *    Edit : signoffSubmissionEditModal{id}   (edit_submission_modal.php)
 * ------------------------------------------------------------------ */
foreach ($submissions as $row):
  // Use joined column first; fall back to empty JSON
  $form_fields_json = $row['form_fields_json'] ?? '[]';

  $CI->load->view('signoff/modals/view_submissions_modal', [
    'row'              => $row,
    'form_fields_json' => $form_fields_json,
    'can_review'       => $can_review,
  ]);

  if ($canApprove || $canView) {
    $CI->load->view('signoff/modals/edit_submission_modal', [
      'row'              => $row,
      'form_fields_json' => $form_fields_json,
    ]);
  }
endforeach;
?>