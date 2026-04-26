<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

<div class="view-header mb-3">
    <div class="view-icon me-3"><i class="ti ti-clipboard-check"></i></div>
        <div class="flex-grow-1">
          <div class="view-title"><?= $page_title ?></div>
        </div>
        
    <div class="ms-auto d-flex gap-2">

      <?php if (staff_can('create', 'evaluations')): ?>
        <a href="#" 
           class="btn btn-primary btn-header"
           data-bs-toggle="modal" 
           data-bs-target="#newEvalModal">
          <i class="ti ti-clipboard-plus me-1"></i> New Evaluation
        </a>
      <?php endif; ?>

      <?php if (staff_can('view_gloabl', 'evaluations')): ?>
        <a href="<?= site_url('evaluations/templates') ?>" class="btn btn-light-primary btn-header">
          <i class="ti ti-template me-1"></i> Templates
        </a>
      <?php endif; ?>
      
        <div class="btn-divider mt-1"></div>        

        <?php render_export_buttons([
            'filename' => $page_title ?? 'export'
        ]); ?>    
          
    </div>
</div>

    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                        'exclude_columns' => ['Actions'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>

  <!-- ── Evaluations Table ───────────────────────────────────────── -->
  <div class="solid-card">
    <div class="card-body">

    <!-- ── KPI Strip ───────────────────────────────────────────────── -->
    <div class="row g-2 mb-3">
        <?php
        $kpis = [
            ['Total',      $kpi['total'] ?? 0,                                   'ti ti-clipboard-list', '#6366f118'],
            ['Draft',      $kpi['draft'] ?? 0,                                   'ti ti-pencil',         '#94a3b818'],
            ['Submitted',  $kpi['submitted'] ?? 0,                               'ti ti-send',           '#0ea5e918'],
            ['Approved',   $kpi['approved'] ?? 0,                                'ti ti-circle-check',   '#16a34a18'],
            ['Rejected',   $kpi['rejected'] ?? 0,                                'ti ti-circle-x',       '#ef444418'],
            ['Avg Rating', number_format(($kpi['avg_rating'] ?? 0), 1) . ' / 5', 'ti ti-star',           '#f59e0b18'],
        ];
        ?>
        <?php foreach ($kpis as $m): ?>
        <div class="col">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:<?= $m[3] ?>;">
                    <i class="<?= $m[2] ?>"></i>
                </div>
                <div>
                    <div class="kpi-value"><?= $m[1] ?></div>
                    <div class="kpi-label"><?= $m[0] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
      <div class="table-responsive">
        <table class="table table-sm small table-hover table-bottom-border align-middle mb-0"
               id="<?= html_escape($table_id); ?>">
          <thead class="bg-light-primary">
            <tr>
              <th width="18%">Employee</th>
              <th>Department</th>
              <th>Manager</th>
              <th>Period</th>
              <th class="text-center">Attendance</th>
              <th class="text-center">Rating</th>
              <th class="text-center">Status</th>
              <th>Review Date</th>
              <th>Reviewed By</th>              
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($evaluations)): ?>
              <?php foreach ($evaluations as $ev): ?>
              <tr>
                <!-- Employee -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <?= user_profile($ev) ?>
                    <div class="lh-sm">
                      <div class="fw-medium">
                        <?= e(trim(($ev['firstname'] ?? '') . ' ' . ($ev['lastname'] ?? ''))) ?>
                      </div>
                      <small class="text-muted">
                        <?= emp_id_display($ev['emp_id'] ?? '') ?>
                      </small>
                    </div>
                  </div>
                </td>

                <!-- Department -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="lh-sm">
                      <div class="fw-medium">
                        <?= e($ev['department_name'] ?? '-') ?>
                      </div>
                      <small class="text-muted">
                        <?= e($ev['team_name'] ?? '-') ?>
                      </small>
                    </div>
                  </div>
                </td>

                <td>
                    <div class="lh-sm">
                      <div class="fw-medium">
                        <?= user_profile_small($ev['mg.firstname'] ?? '-') ?>
                      </div>
                    </div>
                </td>
                
                <!-- Period -->
                <td>
                  <?= e($ev['review_period'] ?: '—') ?>
                  <div class="x-small text-muted"><?= ucfirst($ev['review_type']) ?></div>
                </td>
                
                <!-- Attendance Score -->
                <td class="text-center">
                  <?php if ($ev['score_attendance']): ?>
                    <?= eval_score_badge((float) $ev['score_attendance']) ?>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>

                <!-- Avg Rating -->
                <td class="text-center">
                  <?= eval_score_badge($ev['score_ratings'] !== null ? (float) $ev['score_ratings'] : null) ?>
                </td>

                <!-- Status -->
                <td class="text-center">
                  <?= eval_status_badge($ev['status']) ?>
                </td>

                <!-- Review Date -->
                <td class="text-nowrap">
                  <?= $ev['review_date'] ? date('d M Y', strtotime($ev['review_date'])) : '—' ?>
                </td>

                <!-- Reviewer -->
                <td><?= user_profile_small(trim(($ev['reviewer_firstname'] ?? '') . ' ' . ($ev['reviewer_lastname'] ?? ''))) ?></td>
                
                <!-- Actions -->
                <td class="text-end text-nowrap">
                  <!-- View -->
                  <a href="<?= site_url('evaluations/view/' . $ev['id']) ?>"
                     class="btn btn-ssm btn-outline-primary"
                     title="View Evaluation">
                    <i class="ti ti-eye"></i>
                  </a>

                  <!-- Edit (draft/rejected only) -->
                  <?php if (staff_can('edit', 'evaluations') && in_array($ev['status'], ['draft', 'rejected'])): ?>
                    <a href="<?= site_url('evaluations/edit/' . $ev['id']) ?>"
                       class="btn btn-ssm btn-outline-warning"
                       title="Edit Evaluation">
                      <i class="ti ti-pencil"></i>
                    </a>
                  <?php endif; ?>

                  <!-- Submit -->
                  <?php if (staff_can('edit', 'evaluations') && $ev['status'] === 'draft'): ?>
                    <form method="post" action="<?= site_url('evaluations/submit/' . $ev['id']) ?>"
                          class="d-inline"
                          onsubmit="return confirm('Submit this evaluation for approval?')">
                      <button type="submit" class="btn btn-ssm btn-outline-info" title="Submit">
                        <i class="ti ti-send"></i>
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Approve -->
                  <?php if (staff_can('approve', 'evaluations') && $ev['status'] === 'submitted'): ?>
                    <form method="post" action="<?= site_url('evaluations/approve/' . $ev['id']) ?>"
                          class="d-inline"
                          onsubmit="return confirm('Approve this evaluation?')">
                      <button type="submit" class="btn btn-ssm btn-outline-success" title="Approve">
                        <i class="ti ti-circle-check"></i>
                      </button>
                    </form>
                    <button type="button"
                            class="btn btn-ssm btn-outline-danger reject-eval-btn"
                            data-id="<?= (int) $ev['id'] ?>"
                            title="Reject">
                      <i class="ti ti-circle-x"></i>
                    </button>
                  <?php endif; ?>

                  <!-- Delete -->
                  <?php if (staff_can('delete', 'evaluations')): ?>
                    <form method="post" action="<?= site_url('evaluations/delete/' . $ev['id']) ?>"
                          class="d-inline"
                          onsubmit="return confirm('Delete this evaluation? This cannot be undone.')">
                      <button type="submit" class="btn btn-ssm btn-outline-danger" title="Delete">
                        <i class="ti ti-trash"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="12" class="text-center text-muted py-4">
                  <i class="ti ti-clipboard-off fs-3 d-block mb-2 opacity-50"></i>
                  No evaluations found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if (!empty($evaluations)): ?>
      <div class="card-footer bg-transparent py-2 px-3">
        <small class="text-muted"><?= count($evaluations) ?> evaluation(s) shown</small>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── Reject Modal ─────────────────────────────────────────────── -->
<div class="modal fade" id="rejectEvalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" id="rejectEvalForm" action="" class="app-form">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white py-2">
          <h5 class="modal-title"><i class="ti ti-circle-x me-2"></i>Reject Evaluation</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-3">
            This evaluation will be returned to draft status for revision.
          </p>
          <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
          <textarea name="rejection_reason" class="form-control" rows="3" required
                    placeholder="Explain what needs to be corrected…"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="ti ti-circle-x me-1"></i>Reject
          </button>
        </div>
      </div>
    </form>
  </div>
</div>


<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('evaluations/modals/create_new_evaluation', [], true); ?>
<script>
document.addEventListener('click', function (e) {
  var btn = e.target.closest('.reject-eval-btn');
  if (!btn) return;
  var id   = parseInt(btn.dataset.id, 10);
  var form = document.getElementById('rejectEvalForm');
  form.action = '<?= site_url('evaluations/reject/') ?>' + id;
  form.querySelector('textarea').value = '';
  new bootstrap.Modal(document.getElementById('rejectEvalModal')).show();
});
</script>

