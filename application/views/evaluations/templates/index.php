<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

<div class="view-header mb-3">
    <div class="view-icon me-3"><i class="ti ti-layers-linked"></i></div>
        <div class="flex-grow-1">
          <div class="view-title"><?= $page_title ?></div>
        </div>
        
    <div class="ms-auto d-flex gap-2">

          <a href="<?= site_url('evaluations/template_create') ?>" class="btn btn-primary btn-header">
            <i class="ti ti-plus me-1"></i> New Template
          </a>
          
        <div class="btn-divider mt-1"></div>        

        <?php render_export_buttons([
            'filename' => $page_title ?? 'export'
        ]); ?>    

          <a href="<?= site_url('evaluations') ?>" class="btn btn-header btn-light-primary">
            <i class="ti ti-arrow-back-up"></i>
          </a>  
          
    </div>
</div>

    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                    'exclude_columns' => ['Description', 'Actions'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
  <div class="solid-card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm small table-hover table-bottom-border align-middle mb-0"
               id="<?= html_escape($table_id); ?>">
          <thead class="bg-light-primary">
            <tr>
              <th>Template Name</th>
              <th>Department</th>
              <th>Review Type</th>
              <th>Description</th>
              <th>Created By</th>
              <th>Created At</th>
              <th class="text-center">Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($templates)): ?>
              <?php foreach ($templates as $t): ?>
              <tr class="<?= !$t['is_active'] ? 'opacity-50' : '' ?>">
                
                <td>
                  <div class="fw-semibold">
                    <?= !empty($t['name']) ? e($t['name']) : '<span class="text-muted">—</span>' ?>
                  </div>
                
                  <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                    <span>
                      <i class="ti ti-sitemap text-primary"></i> <?= !empty($t['team_name']) ? e($t['team_name']) : '—' ?>
                    </span>
                
                    <i class="ti ti-dots-vertical"></i>
                
                    <span>
                      <?= !empty($t['teamlead_name']) ? user_profile_small($t['teamlead_name']) : '—' ?>
                    </span>
                  </div>
                </td>
                
                <td>
                  <div class="fw-semibold">
                    <?= !empty($t['department_name']) ? e($t['department_name']) : '<span class="text-muted">—</span>' ?>
                  </div>
                
                  <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                    <span>
                      <?= !empty($t['manager_name']) ? user_profile_small($t['manager_name']) : '—' ?>
                    </span>
                  </div>
                </td>

                <td><span class="badge bg-light-info text-info"><?= ucfirst($t['review_type']) ?></span></td>

                <td class="text-muted" style="max-width:200px;">
                  <span class="text-truncate d-inline-block" style="max-width:180px;"
                        title="<?= e($t['description'] ?? '') ?>">
                    <?= e($t['description'] ?: '—') ?>
                  </span>
                </td>
                
                <td><?= user_profile_small($t['created_by'] ?: '—') ?></td>

                <td class="text-nowrap">
                  <?= $t['created_at'] ? date('d M Y', strtotime($t['created_at'])) : '—' ?>
                </td>
                
                <td class="text-center">
                  <?php if ($t['is_active']): ?>
                    <span class="badge bg-light-success text-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-light-secondary text-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                  <!-- Edit -->
                  <a href="<?= site_url('evaluations/template_edit/' . $t['id']) ?>"
                     class="btn btn-ssm btn-outline-warning"
                     title="Edit template & sections">
                    <i class="ti ti-pencil"></i>
                  </a>

                  <!-- Toggle active/inactive -->
                  <form method="post" action="<?= site_url('evaluations/template_toggle/' . $t['id']) ?>"
                        class="d-inline"
                        onsubmit="return confirm('<?= $t['is_active'] ? 'Deactivate' : 'Activate' ?> this template?')">
                    <button type="submit"
                            class="btn btn-ssm <?= $t['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>"
                            title="<?= $t['is_active'] ? 'Deactivate' : 'Activate' ?>">
                      <i class="ti <?= $t['is_active'] ? 'ti-toggle-right' : 'ti-toggle-left' ?>"></i>
                    </button>
                  </form>

                  <!-- Delete -->
                  <form method="post" action="<?= site_url('evaluations/template_delete/' . $t['id']) ?>"
                        class="d-inline"
                        onsubmit="return confirm('Delete this template? This cannot be undone if it has no linked evaluations.')">
                    <button type="submit" class="btn btn-ssm btn-outline-danger" title="Delete">
                      <i class="ti ti-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="ti ti-template fs-3 d-block mb-2 opacity-50"></i>
                  No templates found.
                  <a href="<?= site_url('evaluations/template_create') ?>" class="d-block mt-1 small">
                    Create your first template
                  </a>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if (!empty($templates)): ?>
      <div class="card-footer bg-transparent py-2 px-3">
        <small class="text-muted"><?= count($templates) ?> template(s)</small>
      </div>
    <?php endif; ?>
  </div>
</div>