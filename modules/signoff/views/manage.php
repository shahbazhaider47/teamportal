<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <!-- ═══════════════════════════════ PAGE HEADER ═══════════════════════════════ -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title mb-0"><?= html_escape($page_title ?? 'Signoff Submissions') ?></h1>
      <?php if (!empty($total_rows)): ?>
        <span class="badge bg-light-primary text-primary fw-normal">
          <?= number_format((int)$total_rows) ?> record<?= $total_rows != 1 ? 's' : '' ?>
        </span>
      <?php endif; ?>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canView    = staff_can('view_global', 'signoff');
        $canApprove = staff_can('approve', 'signoff');
        $canExport  = staff_can('export', 'general');
        $canPrint   = staff_can('print', 'general');

        $perf = isset($perf_indicators)
            ? strtolower(trim((string)$perf_indicators))
            : (function_exists('get_setting') ? strtolower(trim((string)get_setting('signoff_perf_indicators'))) : 'none');
        if ($perf === '') { $perf = 'none'; }
        $showTargets = in_array($perf, ['targets', 'both'], true);
        $showPoints  = in_array($perf, ['points',  'both'], true);

        $CI =& get_instance();
        $empRole = strtolower((string)($CI->session->userdata('emp_role') ?? $CI->session->userdata('user_role') ?? ''));
        $showTeamSignoffBtn = in_array($empRole, ['teamlead', 'manager'], true);
      ?>

      <a href="<?= base_url('signoff') ?>" class="btn btn-primary btn-header">
        <i class="ti ti-calendar me-1"></i> Signoff
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
        <a href="<?= site_url('signoff/team_signoff') ?>" class="btn btn-header btn-primary">
          <i class="ti ti-users me-1"></i> Team Signoff
        </a>
      <?php endif; ?>

      <?php if ($canExport): ?>
        <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                title="Export to Excel"
                data-export-filename="<?= html_escape($page_title ?? 'signoff-submissions') ?>">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>
      <?php if ($canPrint): ?>
        <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-print-table" title="Print Table">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>
    </div>
  </div>
  <!-- /PAGE HEADER -->

  <div class="card shadow-sm">
    <div class="card-body">

      <?php
        $teams_map_view = [];
        if (!empty($teams) && is_array($teams)) {
          foreach ($teams as $t) {
            if (isset($t['id'])) {
              $teams_map_view[(int)$t['id']] = $t['name'] ?? ('#' . (int)$t['id']);
            }
          }
        }
        $users     = isset($users)     && is_array($users)     ? $users     : [];
        $all_forms = isset($all_forms) && is_array($all_forms) ? $all_forms : [];

        // Restore active filter values from controller
        $fv_from   = $f_from   ?? '';
        $fv_to     = $f_to     ?? '';
        $fv_month  = $f_month  ?? '';
        $fv_year   = (int)($f_year  ?? 0);
        $fv_user   = (int)($f_user  ?? 0);
        $fv_team   = (int)($f_team  ?? 0);
        $fv_form   = (int)($f_form  ?? 0);
        $fv_status = $f_status ?? '';

        $can_review = staff_can('own_team', 'signoff') || staff_can('view_global', 'signoff');

        $year_now  = (int)date('Y');
        $year_list = range($year_now, max(2020, $year_now - 6));
      ?>

      <!-- ═══════════════════ SERVER-SIDE FILTER FORM ═══════════════════ -->
      <form method="GET" action="<?= site_url('signoff') ?>" class="bg-light-primary p-3 rounded-3 mb-3">
        <div class="row g-2 align-items-end small">

          <div class="col-6 col-lg-1">
            <label class="form-label small fw-semibold mb-1">From</label>
            <input type="date" name="from_date" class="form-control form-control-sm"
                   value="<?= html_escape($fv_from) ?>">
          </div>

          <div class="col-6 col-lg-1">
            <label class="form-label small fw-semibold mb-1">To</label>
            <input type="date" name="to_date" class="form-control form-control-sm"
                   value="<?= html_escape($fv_to) ?>">
          </div>

          <div class="col-6 col-lg-1">
            <label class="form-label small fw-semibold mb-1">Month</label>
            <input type="month" name="month" class="form-control form-control-sm"
                   value="<?= html_escape($fv_month) ?>">
          </div>

          <div class="col-6 col-lg-1">
            <label class="form-label small fw-semibold mb-1">Year</label>
            <select name="year" class="form-select form-select-sm">
              <option value="">All</option>
              <?php foreach ($year_list as $yy): ?>
                <option value="<?= (int)$yy ?>" <?= $fv_year === (int)$yy ? 'selected' : '' ?>>
                  <?= (int)$yy ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label small fw-semibold mb-1">Employee</label>
            <select name="user_id" class="form-select form-select-sm">
              <option value="">All</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= $fv_user === (int)$u['id'] ? 'selected' : '' ?>>
                  <?= html_escape(trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''))) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-lg-1">
            <label class="form-label small fw-semibold mb-1">Team</label>
            <select name="team_id" class="form-select form-select-sm">
              <option value="">All</option>
              <?php foreach ($teams_map_view as $tid => $tname): ?>
                <option value="<?= (int)$tid ?>" <?= $fv_team === (int)$tid ? 'selected' : '' ?>>
                  <?= html_escape($tname) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label small fw-semibold mb-1">Form</label>
            <select name="form_id" class="form-select form-select-sm">
              <option value="">All</option>
              <?php foreach ($all_forms as $af): ?>
                <option value="<?= (int)$af['id'] ?>" <?= $fv_form === (int)$af['id'] ? 'selected' : '' ?>>
                  <?= html_escape($af['title'] ?? ('Form #' . $af['id'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-lg-1">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="submitted" <?= $fv_status === 'submitted' ? 'selected' : '' ?>>Submitted</option>
              <option value="approved"  <?= $fv_status === 'approved'  ? 'selected' : '' ?>>Approved</option>
              <option value="rejected"  <?= $fv_status === 'rejected'  ? 'selected' : '' ?>>Rejected</option>
              <option value="excused"   <?= $fv_status === 'excused'   ? 'selected' : '' ?>>Excused</option>
            </select>
          </div>

          <div class="col-12 col-lg-2 d-flex gap-2">
            <label class="form-label small fw-semibold mb-1 d-none d-lg-block">&nbsp;</label>
            <button type="submit" class="btn btn-sm btn-primary flex-fill">
              <i class="ti ti-search me-1"></i> Filter
            </button>
            <a href="<?= site_url('signoff') ?>" class="btn btn-sm btn-white" title="Clear filters">
              <i class="ti ti-refresh"></i>
            </a>
          </div>

        </div>
      </form>
      <!-- /FILTER FORM -->

      <!-- Active filter badges -->
      <?php
        $active_filters = [];
        if ($fv_from  !== '') $active_filters[] = 'From: ' . $fv_from;
        if ($fv_to    !== '') $active_filters[] = 'To: '   . $fv_to;
        if ($fv_month !== '') $active_filters[] = 'Month: ' . $fv_month;
        if ($fv_year  > 0)    $active_filters[] = 'Year: '  . $fv_year;
        if ($fv_user  > 0) {
          foreach ($users as $u) {
            if ((int)$u['id'] === $fv_user) {
              $active_filters[] = 'Employee: ' . trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
              break;
            }
          }
        }
        if ($fv_team > 0 && isset($teams_map_view[$fv_team])) {
          $active_filters[] = 'Team: ' . $teams_map_view[$fv_team];
        }
        if ($fv_form > 0) {
          foreach ($all_forms as $af) {
            if ((int)$af['id'] === $fv_form) { $active_filters[] = 'Form: ' . ($af['title'] ?? ''); break; }
          }
        }
        if ($fv_status !== '') $active_filters[] = 'Status: ' . ucfirst($fv_status);
      ?>
      <?php if (!empty($active_filters)): ?>
        <div class="d-flex flex-wrap gap-1 mb-3 align-items-center">
          <span class="small text-muted me-1">Active filters:</span>
          <?php foreach ($active_filters as $af): ?>
            <span class="badge bg-light-primary text-primary border border-primary-subtle fw-normal">
              <?= html_escape($af) ?>
            </span>
          <?php endforeach; ?>
          <a href="<?= site_url('signoff') ?>"
             class="badge bg-light-danger text-danger border border-danger-subtle fw-normal text-decoration-none ms-1">
            <i class="ti ti-x me-1"></i> Clear all
          </a>
        </div>
      <?php endif; ?>

      <!-- ═══════════════════ TABLE ═══════════════════ -->
      <div class="table-responsive overflow-auto data-table-style app-scroll">
        <table id="<?= html_escape($table_id ?? 'signoffsubmissionsTable') ?>"
               class="table table-hover small table-sm align-middle mb-0">
          <thead class="bg-light-primary">
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Employee</th>
              <th>Team</th>
              <th>Form</th>
              <th>Assigned To</th>
              <th>Points</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($submissions)): ?>
              <tr>
                <td colspan="9" class="text-center text-muted py-5">
                  <i class="ti ti-mood-empty fs-3 d-block mb-2"></i>
                  No submissions found<?= !empty($active_filters) ? ' matching the selected filters.' : '.' ?>
                </td>
              </tr>
            <?php else: ?>
              <?php $row_num = (int)(($page ?? 1) - 1) * (int)($per_page ?? 100) + 1; ?>
              <?php foreach ($submissions as $row): ?>
                <?php
                  $rowStatus = strtolower((string)($row['status'] ?? ''));
                  $statusCls = match($rowStatus) {
                    'approved'  => 'success',
                    'rejected'  => 'danger',
                    'submitted' => 'info',
                    'excused'   => 'warning',
                    default     => 'secondary',
                  };

                  // Form assignment label — use form_team_id joined from query
                  $formTeamId    = (int)($row['form_team_id'] ?? 0);
                  $assnLabelHtml = '<span class="badge bg-secondary">Global</span>';
                  if ($formTeamId > 0) {
                    $tName = $teams_map_view[$formTeamId] ?? ('#' . $formTeamId);
                    $assnLabelHtml = '<span class="badge bg-light-primary"><i class="ti ti-users me-1"></i>'
                                   . html_escape($tName) . '</span>';
                  }

                  $formTitle = $row['form_title'] ?? '—';
                  $userFull  = $row['user_name']
                      ?? trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));

                  $pts = (isset($row['total_points']) && $row['total_points'] !== null)
                      ? number_format((float)$row['total_points'], 2)
                      : '—';

                  $dateDisplay = !empty($row['submission_date'])
                      ? date('d M Y', strtotime($row['submission_date']))
                      : '—';

                  // Modal IDs must match what the modal views generate
                  $viewModalId = 'signoffSubmissionModal' . (int)$row['id'];
                  $editModalId = 'signoffSubmissionEditModal' . (int)$row['id'];
                ?>
                <tr>
                  <td class="text-muted"><?= $row_num++ ?></td>
                  <td class="text-nowrap"><?= html_escape($dateDisplay) ?></td>
                  <td><?= function_exists('user_profile_image') ? user_profile_image($userFull) : html_escape($userFull) ?></td>
                  <td><?= html_escape($row['team_name'] ?? '—') ?></td>
                  <td><?= html_escape($formTitle) ?></td>
                  <td><?= $assnLabelHtml ?></td>
                  <td><?= html_escape($pts) ?></td>
                  <td><span class="badge bg-<?= $statusCls ?>"><?= ucfirst($rowStatus ?: '—') ?></span></td>
                  <td class="text-end text-nowrap">
                    <div class="btn-group btn-group-sm">

                      <!-- View — ID matches signoffSubmissionModal{id} in view_submissions_modal.php -->
                      <a href="#"
                         class="btn btn-outline-secondary"
                         data-bs-toggle="modal"
                         data-bs-target="#<?= $viewModalId ?>"
                         title="View Submission">
                        <i class="ti ti-eye"></i>
                      </a>

                      <!-- Edit — ID matches signoffSubmissionEditModal{id} in edit_submission_modal.php -->
                      <?php if ($canApprove || $canView): ?>
                        <a href="#"
                           class="btn btn-outline-primary"
                           data-bs-toggle="modal"
                           data-bs-target="#<?= $editModalId ?>"
                           title="Edit Submission">
                          <i class="ti ti-pencil"></i>
                        </a>
                      <?php endif; ?>

                      <!-- Review actions (contextual by current status) -->
                      <?php if ($can_review): ?>
                        <?php if ($rowStatus === 'submitted'): ?>
                          <a href="<?= base_url('signoff/review_submission/' . (int)$row['id'] . '/approved') ?>"
                             class="btn btn-outline-success"
                             onclick="return confirm('Approve this submission?');"
                             title="Approve"><i class="ti ti-check"></i></a>
                          <a href="<?= base_url('signoff/review_submission/' . (int)$row['id'] . '/rejected') ?>"
                             class="btn btn-outline-danger"
                             onclick="return confirm('Reject this submission?');"
                             title="Reject"><i class="ti ti-x"></i></a>
                          <a href="<?= base_url('signoff/review_submission/' . (int)$row['id'] . '/excused') ?>"
                             class="btn btn-outline-warning"
                             onclick="return confirm('Mark as Excused?');"
                             title="Excuse"><i class="ti ti-circle-check"></i></a>

                        <?php elseif ($rowStatus === 'approved'): ?>
                          <a href="<?= base_url('signoff/review_submission/' . (int)$row['id'] . '/rejected') ?>"
                             class="btn btn-outline-danger"
                             onclick="return confirm('Reject this approved submission?');"
                             title="Reject"><i class="ti ti-x"></i></a>
                          <a href="<?= base_url('signoff/review_submission/' . (int)$row['id'] . '/excused') ?>"
                             class="btn btn-outline-warning"
                             onclick="return confirm('Mark as Excused?');"
                             title="Excuse"><i class="ti ti-circle-check"></i></a>

                        <?php elseif ($rowStatus === 'rejected' || $rowStatus === 'excused'): ?>
                          <a href="<?= base_url('signoff/review_submission/' . (int)$row['id'] . '/approved') ?>"
                             class="btn btn-outline-success"
                             onclick="return confirm('Approve this submission?');"
                             title="Approve"><i class="ti ti-check"></i></a>
                        <?php endif; ?>
                      <?php endif; ?>

                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <!-- /TABLE -->

        <!-- ═══════════════════ PAGINATION + SUMMARY ═══════════════════ -->
        <?php if ($total_pages > 1 && !empty($pagination)): ?>
          <div class="mt-3 pt-2 border-top">
            
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 small text-muted mb-2">
              <span>
                Showing 
                <?= number_format(($page - 1) * $per_page + 1) ?>
                –
                <?= number_format(min($page * $per_page, $total_rows)) ?>
                of <?= number_format($total_rows) ?> record(s)
                <?php if (!empty($active_filters)) echo ' (filtered)'; ?>
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
        <!-- /PAGINATION -->

    </div>
  </div>
</div>

<?php
// ═══════════════════════════════════════════════════════════════
// Modals — rendered once per visible row only
// form_fields_json is now joined directly in the SELECT so no extra queries needed
// Modal IDs must exactly match what modal view files generate:
//   View: signoffSubmissionModal{id}
//   Edit: signoffSubmissionEditModal{id}
// ═══════════════════════════════════════════════════════════════
$CI =& get_instance();
foreach ($submissions as $row):
  // Use form_fields_json joined from query; fall back to forms map if somehow missing
  $form_fields_json = $row['form_fields_json']
      ?? (isset($forms[$row['form_id']]['fields']) ? $forms[$row['form_id']]['fields'] : '[]');

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