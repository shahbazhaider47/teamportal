<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
        <div class="d-flex align-items-center small gap-1">
            <span class="badge bg-primary"><?= count($forms) ?> Forms</span>
        </div>        
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
          $table_id   = $table_id ?? 'feedbackTable';
        ?>

        <div class="btn-divider"></div>
        
        <!-- Add New Contract -->
      <?php if (staff_can('feedback', 'general')): ?>
        <a href="<?= site_url('feedback/create'); ?>" class="btn btn-primary btn-header">
          <i class="ti ti-plus me-1"></i> Create Feedback Form
        </a>
      <?php endif; ?>
      
        <!-- Search and Filters -->
        <a class="btn btn-light-primary icon-btn b-r-4" data-bs-toggle="collapse" href="#showFilter" 
            role="button" aria-expanded="false" aria-controls="showFilter" title="Show Filter">
            <i class="ti ti-search"></i>
        </a> 
    
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

    <!-- Universal table filter (global search + per-column filters) -->
    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('recruitment_table_filter')): ?>
                <?php recruitment_table_filter($table_id, [
                    'exclude_columns' => ['ID #', 'Created At', 'Actions', '', ''],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>

  <!-- Forms Table -->
  <div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
              <!-- Education courses cards start -->
              <div class="col-lg-9">
                    <div class="row">
                      <div class="col-6 col-md-3 education-courses-cards">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                          <div class="flex-shrink-0">
                            <span class="courses-icon bg-primary h-40 w-40 d-flex-center b-r-10">
                              <i class="ti ti-file-text f-s-18"></i>
                            </span>
                          </div>
                            <div class="flex-grow-1 d-flex align-items-center">
                              <p class="f-s-14 f-w-500 text-secondary">
                                Total Forms:
                                <span class="f-s-16 f-w-700"><?= count($forms) ?></span>
                              </p>
                            </div>
                        </div>
                      </div>
                      
                      <div class="col-6 col-md-3 education-courses-cards">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                          <div class="flex-shrink-0">
                            <span class="courses-icon bg-info h-40 w-40 d-flex-center b-r-10">
                              <i class="ti ti-check f-s-18"></i>
                            </span>
                          </div>
                            <div class="flex-grow-1 d-flex align-items-center">
                              <p class="f-s-14 f-w-500 text-secondary">
                                Active Forms:
                                <?php 
                                $active_forms = array_filter($forms, function($form) {
                                    return $form['status'] === 'active';
                                });
                                ?>                                
                                <span class="f-s-16 f-w-700"><?= count($active_forms) ?></span>
                              </p>
                            </div>
                        </div>
                      </div>
                      
                      <div class="col-6 col-md-3 education-courses-cards">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                          <div class="flex-shrink-0">
                            <span class="courses-icon bg-success h-40 w-40 d-flex-center b-r-10">
                              <i class="ti ti-message-circle f-s-18"></i>
                            </span>
                          </div>
                            <div class="flex-grow-1 d-flex align-items-center">
                              <p class="f-s-14 f-w-500 text-secondary">
                                Total Responses:
                                <?php
                                $total_responses = 0;
                                foreach ($forms as $form) {
                                    $CI =& get_instance();
                                    $CI->load->model('Feedback_model', 'feedback_m');
                                    $stats = $CI->feedback_m->get_form_stats($form['id']);
                                    $total_responses += (int)($stats['total'] ?? 0);
                                }
                                ?>
                                <span class="f-s-16 f-w-700"><?= $total_responses ?></span>
                              </p>
                            </div>
                        </div>
                      </div>
                      
                      <div class="col-6 col-md-3 education-courses-cards">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                          <div class="flex-shrink-0">
                            <span class="courses-icon bg-warning h-40 w-40 d-flex-center b-r-10">
                              <i class="ti ti-chart-bar f-s-18"></i>
                            </span>
                          </div>
                            <div class="flex-grow-1 d-flex align-items-center">
                              <p class="f-s-14 f-w-500 text-secondary">
                                Avg. Response Rate:
                                <?php
                                $response_rate = 0;
                                if (count($forms) > 0) {
                                    $response_rate = round(($total_responses / (count($forms) * 10)) * 100, 1); // Simplified calculation
                                }
                                ?>
                                <span class="f-s-16 f-w-700"><?= $response_rate ?>%</span>
                              </p>
                            </div>
                        </div>
                      </div>
                      
                    </div>
                  </div>
              <!-- Education courses cards end -->
              
        </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
          <thead class="bg-light-primary">
            <tr>
              <th>Form Title</th>
              <th>Frequency</th>
              <th>Required All</th>
              <th>Status</th>
              <th>Responses</th>
              <th>Avg Score</th>
              <th>Completion</th>
              <th>Created At</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>

          <tbody>
          <?php if (!empty($forms)): ?>
            <?php foreach ($forms as $form): ?>

              <?php
                // Pull stats inline (lightweight)
                $CI =& get_instance();
                $CI->load->model('Feedback_model', 'feedback_m');
                $stats = $CI->feedback_m->get_form_stats($form['id']);
                
                // Calculate completion rate (simplified)
                $completion_rate = 0;
                if ($form['status'] === 'active') {
                    // This would ideally come from actual user count
                    $completion_rate = min(100, round(($stats['total'] ?? 0) * 20));
                }
              ?>

              <tr>
                <td>
                  <div class="d-flex align-items-start">
                    <div class="flex-grow-1">
                      <strong><?= html_escape($form['title']); ?></strong>
                    </div>
                  </div>
                </td>

                <td>
                  <span class="badge bg-light-primary">
                    <i class="ti ti-calendar me-1"></i>
                    <?= ucfirst($form['frequency']); ?>
                  </span>
                </td>

                <td>
                  <?php if ((int)$form['is_required'] === 1): ?>
                    <span class="badge bg-danger-subtle text-danger">
                      <i class="ti ti-alert-circle me-1"></i>Yes
                    </span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary">
                      <i class="ti ti-circle-check me-1"></i>Optional
                    </span>
                  <?php endif; ?>
                </td>

                <td>
                  <?php
                    $statusClass = [
                      'draft'  => 'warning',
                      'active' => 'success',
                      'inactive' => 'secondary'
                    ][$form['status']] ?? 'secondary';
                  ?>
                  <span class="badge bg-<?= $statusClass; ?>">
                    <?php if ($form['status'] === 'active'): ?>
                      <i class="ti ti-circle-check me-1"></i>
                    <?php elseif ($form['status'] === 'draft'): ?>
                      <i class="ti ti-edit me-1"></i>
                    <?php else: ?>
                      <i class="ti ti-circle-x me-1"></i>
                    <?php endif; ?>
                    <?= ucfirst($form['status']); ?>
                  </span>
                </td>

                <td>
                  <div class="d-flex align-items-center">
                    <div class="me-2">
                      <strong><?= (int)($stats['total'] ?? 0); ?></strong>
                    </div>
                    <?php if ($stats['total'] > 0): ?>
                      <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar bg-success" 
                             style="width: <?= min(100, ($stats['total'] * 10)) ?>%">
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>

                <td>
                  <?php if ($stats['avg_score']): ?>
                    <div class="d-flex align-items-center">
                      <div class="rating-display me-2">
                        <?= number_format($stats['avg_score'], 1) ?>
                      </div>
                      <div class="small text-muted">
                        /5.0
                      </div>
                    </div>
                    <div class="progress mt-1" style="height: 4px;">
                      <div class="progress-bar bg-info" 
                           style="width: <?= ($stats['avg_score'] / 5) * 100 ?>%">
                      </div>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>

                <td>
                  <?php if ($form['status'] === 'active'): ?>
                    <div class="d-flex align-items-center">
                      <div class="completion-rate me-2">
                        <?= $completion_rate ?>%
                      </div>
                      <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar 
                          <?= $completion_rate >= 80 ? 'bg-success' : 
                            ($completion_rate >= 50 ? 'bg-warning' : 'bg-danger') ?>"
                             style="width: <?= $completion_rate ?>%">
                        </div>
                      </div>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">N/A</span>
                  <?php endif; ?>
                </td>

                <td>
                <?= date('M d, Y', strtotime($form['created_at'])) ?>
                </td>
                
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    
                    <?php if ($form['status'] === 'active'): ?>
                      <a href="<?= site_url('feedback/send_to_participants/' . $form['id']); ?>"
                         class="btn btn-outline-secondary"
                         title="Send to Participants">
                        <i class="ti ti-send"></i>
                      </a>
                    <?php endif; ?>

                    <a href="<?= site_url('feedback/view/' . $form['id']); ?>"
                       class="btn btn-outline-secondary"
                       title="View Analytics">
                      <i class="ti ti-chart-bar"></i>
                    </a>

                    <?php if (staff_can('feedback', 'general')): ?>
                      <a href="<?= site_url('feedback/edit/' . $form['id']); ?>"
                         class="btn btn-outline-secondary"
                         title="Edit Form">
                        <i class="ti ti-edit"></i>
                      </a>
                    <?php endif; ?>

                    <?php if (staff_can('feedback', 'general')): ?>
                      <a href="<?= site_url('feedback/export/' . $form['id']); ?>"
                         class="btn btn-outline-secondary"
                         title="Export Responses">
                        <i class="ti ti-download"></i>
                      </a>
                    <?php endif; ?>

                    <!-- Delete Button -->
                    <?php if (staff_can('feedback', 'general')): ?>
                    <?= delete_link([
                    'url' => 'feedback/delete/' . $form['id'],
                    'label' => '',
                    'class' => 'btn btn-outline-secondary',
                    'message' => 'Are you sure you want to delete this feedback form? All submissions of this form will also be deleted.',                                             
                    ]) ?>

                    <?php endif; ?>

                  </div>
                </td>
              </tr>

            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-5">
                <div class="py-4">
                  <i class="ti ti-file-text display-4 text-primary"></i>
                  <h6 class="mt-3 text-muted">No feedback forms created yet.</h6>
                </div>
              </td>
            </tr>
          <?php endif; ?>
          </tbody>

        </table>
      </div>

    </div>
    
    <!-- Table Footer -->
    <div class="card-footer">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">
                    Showing <?= count($forms) ?> form<?= count($forms) !== 1 ? 's' : '' ?>
                </small>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">
                    <i class="ti ti-info-circle me-1"></i>
                    Last updated: <?= date('M d, Y H:i') ?>
                </small>
            </div>
        </div>
    </div>
  </div>

</div>

<script>

// Initialize DataTable if available
<?php if (function_exists('init_table_js')): ?>
    $(document).ready(function() {
        init_table_js();
        
        // Add custom sorting for completion rate
        $.fn.dataTable.ext.type.order['completion-pre'] = function(data) {
            if (data === 'N/A' || data === '-') return 0;
            return parseInt(data.replace('%', ''));
        };
        
        // Initialize the table
        $('#<?= html_escape($table_id); ?>').DataTable({
            columnDefs: [
                { type: 'completion', targets: 7 } // Completion rate column
            ],
            pageLength: 25,
            responsive: true,
            order: [[3, 'desc'], [0, 'asc']], // Sort by status then title
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search forms...",
                lengthMenu: "_MENU_"
            }
        });
    });
<?php endif; ?>

// Quick status update
function updateStatus(formId, status) {
    fetch('<?= site_url("feedback/update_status/") ?>' + formId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating status.');
    });
}
</script>

