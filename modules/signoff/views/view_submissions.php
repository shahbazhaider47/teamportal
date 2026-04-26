<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <a href="<?= base_url('signoff/forms') ?>" class="btn btn-light-primary btn-header">
          <i class="ti ti-arrow-left me-1"></i>
        </a>
        <h1 class="h6 header-title">
          <?= $title ?> &nbsp;&gt;&nbsp;
          <span class="m-0 fw-normal text-muted"><?= html_escape($form['title'] ?? '—') ?></span>
        </h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canView   = staff_can('view_global', 'signoff');
          $canAdd    = staff_can('create', 'signoff');
          $canExport = staff_can('export', 'general');
          $canPrint  = staff_can('print', 'general');

          // who can approve/reject (exposed to modal)
          $can_review = isset($can_review)
            ? (bool)$can_review
            : (staff_can('approve', 'signoff') || staff_can('review', 'signoff') || staff_can('view_global', 'signoff'));

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
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Points">
           <i class="ti ti-trophy"></i> Points
        </a>
      <?php endif; ?>
        
        <div class="btn-divider"></div>
            
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'viewsubmissionsTable' ?>">
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
    
    <div class="row justify-content-center">
      <div class="col-xl-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-primary py-3">
            <form method="get" class="row g-2 align-items-end mb-0 w-100">
              <div class="col-md-3 col-12">
                <label class="form-label mb-0 fw-semibold small">Month</label>
                <input type="month" name="month"
                       value="<?= html_escape($month ?? date('Y-m')) ?>"
                       class="form-control form-control-sm">
              </div>
              <div class="col-md-3 col-12">
                <label class="form-label mb-0 fw-semibold small">Status</label>
                <select name="status" class="form-select form-select-sm">
                  <option value="">All</option>
                  <option value="submitted" <?= ($status ?? '') === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                  <option value="approved"  <?= ($status ?? '') === 'approved'  ? 'selected' : '' ?>>Approved</option>
                  <option value="rejected"  <?= ($status ?? '') === 'rejected'  ? 'selected' : '' ?>>Rejected</option>
                  <option value="excused"   <?= ($status ?? '') === 'excused'   ? 'selected' : '' ?>>Excused</option>
                </select>
              </div>
              <div class="col-md-4 col-12">
                <label class="form-label mb-0 fw-semibold small">User</label>
                <select name="user_id" class="form-select form-select-sm">
                  <option value="">All</option>
                  <?php foreach ($users as $user): ?>
                    <option value="<?= (int)$user['id'] ?>"
                            <?= (isset($user_id) && (int)$user_id === (int)$user['id']) ? 'selected' : '' ?>>
                      <?= html_escape(($user['firstname'] ?? '').' '.($user['lastname'] ?? '')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-auto d-flex align-items-end gap-2 ms-auto">
                <button type="submit" class="btn btn-sm btn-white px-4">
                  <i class="ti ti-filter me-1"></i> Filter
                </button>
                <a href="<?= base_url('signoff/view_submissions/' . (int)$form['id']) ?>"
                   class="btn btn-sm btn-white px-3" title="Reset">
                  <i class="ti ti-refresh"></i>
                </a>
              </div>
            </form>
          </div>

          <div class="card-body">
            <?php if (!empty($submissions)): ?>
              <div class="table-responsive mt-4">
                <table class="table table-hover small align-middle mb-0" id="viewsubmissionsTable">
                  <thead class="bg-light-primary">
                    <tr>
                      <th>EMP ID</th>
                      <th>Employee Name</th>
                      <th>Designation</th>
                      <th>Team Name</th>
                      <th>Submitted At</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($submissions as $sub): ?>
                    <?php
                      $status_lower = strtolower($sub['status'] ?? '');
                      $badge = 'secondary';
                      if ($status_lower === 'approved')  $badge = 'success';
                      elseif ($status_lower === 'rejected') $badge = 'danger';
                      elseif ($status_lower === 'submitted') $badge = 'primary';

                      $modalId = 'signoffSubmissionModal' . (int)$sub['id'];
                    ?>
                    <tr>
                      <td><?= emp_id_display($sub['emp_id'] ?? '—') ?></td>
                      <td><?= html_escape($sub['user_name'] ?? '—') ?></td>
                      <td><?= html_escape($sub['position_title'] ?? '—') ?></td>                      
                      <td><?= html_escape($sub['team_name'] ?? '—') ?></td>
                      <td><?= ($dt = ($sub['created_at'] ?? $sub['submission_date'] ?? '')) ? (function_exists('format_datetime') ? format_datetime($dt, 'Y-m-d H:i') : ( ($ts=is_numeric($dt)?(int)$dt:strtotime($dt)) ? date('Y-m-d H:i',$ts) : '—' )) : '—' ?></td>
                      <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($status_lower ?: '—') ?></span></td>
                      <td class="text-end">
                        <div class="btn-group btn-group-sm">
                          <!-- View: opens the shared modal -->
                          <a href="#"
                             class="btn btn-outline-info"
                             data-bs-toggle="modal"
                             data-bs-target="#<?= $modalId ?>"
                             title="View Submission">
                            <i class="ti ti-eye"></i> View
                          </a>

                          <?php if ($can_review): ?>
                            <?php if ($status_lower === 'approved'): ?>
                              <a href="<?= base_url('signoff/review_submission/'.(int)$sub['id'].'/rejected') ?>"
                                 class="btn btn-outline-danger"
                                 onclick="return confirm('Reject this submission?');"
                                 title="Reject">
                                <i class="ti ti-x"></i> Reject
                              </a>
                            <?php elseif ($status_lower === 'rejected'): ?>
                              <a href="<?= base_url('signoff/review_submission/'.(int)$sub['id'].'/approved') ?>"
                                 class="btn btn-outline-success"
                                 onclick="return confirm('Approve this submission?');"
                                 title="Approve">
                                <i class="ti ti-check"></i> Approve
                              </a>
                            <?php elseif ($status_lower === 'submitted'): ?>
                              <a href="<?= base_url('signoff/review_submission/'.(int)$sub['id'].'/approved') ?>"
                                 class="btn btn-outline-success"
                                 onclick="return confirm('Approve this submission?');"
                                 title="Approve">
                                <i class="ti ti-check"></i> Approve
                              </a>
                              <a href="<?= base_url('signoff/review_submission/'.(int)$sub['id'].'/rejected') ?>"
                                 class="btn btn-outline-danger"
                                 onclick="return confirm('Reject this submission?');"
                                 title="Reject">
                                <i class="ti ti-x"></i> Reject
                              </a>
                            <?php endif; ?>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="p-4 text-center text-muted">
                <i class="ti ti-inbox fs-1 mb-2"></i>
                <div>No submissions found for this form/date.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
</div>

<?php
// ---------- Render the shared modal for each submission ----------
$CI =& get_instance();
if (!empty($submissions)) {
  foreach ($submissions as $sub) {
    $CI->load->view('signoff/modals/view_submissions_modal', [
      'row'              => $sub,
      'form_fields_json' => $sub['form_fields'] ?? '[]', // labels/types for rendering values
      'can_review'       => (bool)$can_review,           // who can see Approve/Reject in modal
    ]);
  }
}
?>
