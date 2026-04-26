<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">
  <!-- Page Header -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title) ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canCreate  = staff_can('create', 'emails');
        $canExport  = staff_can('export', 'general');
        $canPrint   = staff_can('print',  'general');
        $table_id   = isset($table_id) && is_string($table_id) && $table_id !== '' ? $table_id : 'emailsTable';
      ?>

      <div class="btn-divider"></div>
      <!-- Add Email Template -->
      <button
        type="button"
        class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
        <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#addTeamModal"' : 'disabled' ?>
        title="Add New Template"
        aria-label="Add new email template"
      >
        <i class="fas fa-plus me-1" aria-hidden="true"></i> Add New
      </button>

      <!-- Search -->
      <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
        <span class="input-group-text bg-white border-end-0" id="search-icon">
          <i class="fas fa-search text-muted small" aria-hidden="true"></i>
        </span>
        <input
          type="text"
          class="form-control rounded-start-0 app-form small dynamic-search-input border-start-0"
          placeholder="Search templates..."
          aria-label="Search email templates"
          aria-describedby="search-icon"
          data-table-target="<?= html_escape($table_id) ?>"
        >
        <button 
          class="btn btn-outline-secondary dynamic-search-clear" 
          type="button" 
          style="display:none;"
          aria-label="Clear search"
        >
          <i class="fas fa-times" aria-hidden="true"></i>
        </button>
      </div>

      <?php if ($canExport): ?>
        <button 
          type="button"
          class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
          title="Export to Excel"
          aria-label="Export table to Excel"
          data-export-filename="<?= html_escape($page_title ?: 'email-templates') ?>"
          data-export-target="<?= html_escape($table_id) ?>"
        >
          <i class="ti ti-download" aria-hidden="true"></i>
        </button>
      <?php endif; ?>

      <?php if ($canPrint): ?>
        <button 
          type="button"
          class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
          title="Print Table"
          aria-label="Print table"
          data-print-target="<?= html_escape($table_id) ?>"
        >
          <i class="ti ti-printer" aria-hidden="true"></i>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Content -->
  <div id="wrapper">
    <div class="content email-templates">
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-body">

              <?php if (empty($templates_by_type) || !is_array($templates_by_type)): ?>
                <div class="alert alert-info m-0" role="status">
                  <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
                  No email templates found.
                </div>
              <?php else: ?>

                <?php foreach ($templates_by_type as $type => $rows): ?>
                  <?php
                    $typeName = html_escape(ucfirst(str_replace('_', ' ', (string)$type)));
                    $isFirstType = $type === array_key_first($templates_by_type);
                    $hasRows = !empty($rows) && is_array($rows);
                  ?>
                  
                  <section class="email-template-section mb-4" aria-labelledby="heading-<?= html_escape($type) ?>">
                    <h4 
                      id="heading-<?= html_escape($type) ?>" 
                      class="tw-font-semibold email-template-heading d-flex align-items-center justify-content-between mb-3"
                    >
                      <span><?= $typeName ?></span>

                      <?php if (!empty($hasPermissionEdit)): ?>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Template group actions">
                          <a 
                            href="<?= site_url('emails/disable_by_type/' . rawurlencode((string)$type)); ?>" 
                            class="btn btn-outline-secondary"
                            title="Disable all <?= $typeName ?> templates"
                          >
                            <small>Disable all</small>
                          </a>
                          <a 
                            href="<?= site_url('emails/enable_by_type/' . rawurlencode((string)$type)); ?>"
                            class="btn btn-outline-secondary"
                            title="Enable all <?= $typeName ?> templates"
                          >
                            <small>Enable all</small>
                          </a>
                        </div>
                      <?php endif; ?>
                    </h4>

                    <div class="table-responsive">
                      <table 
                        class="table table-bordered table-hover w-100 <?= $isFirstType ? 'is-primary-table' : '' ?>"
                        id="<?= $isFirstType ? html_escape($table_id) : '' ?>"
                        aria-describedby="heading-<?= html_escape($type) ?>"
                      >
                        <thead class="table-light">
                          <tr>
                            <th scope="col">
                              <span class="tw-font-semibold">Template Name</span>
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (!$hasRows): ?>
                            <tr>
                              <td class="text-muted text-center py-4">
                                <em>No templates in this group.</em>
                              </td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($rows as $index => $tpl): ?>
                              <?php
                                $tplId     = (int)($tpl['emailtemplateid'] ?? 0);
                                $tplName   = (string)($tpl['name'] ?? '');
                                $tplSlug   = (string)($tpl['slug'] ?? '');
                                $tplActive = (int)($tpl['active'] ?? 0);
                                $isOff     = $tplActive === 0;
                                $statusText = $tplActive === 1 ? 'Active' : 'Disabled';
                              ?>
                              <tr class="<?= $isOff ? 'table-inactive' : '' ?>">
                                <td>
                                  <div class="d-flex align-items-center justify-content-between">
                                    <div class="template-info">
                                      <a 
                                        href="<?= site_url('emails/email_template/' . $tplId); ?>" 
                                        class="text-decoration-none fw-medium <?= $isOff ? 'text-muted' : 'text-primary' ?>"
                                        title="Edit <?= html_escape($tplName) ?>"
                                      >
                                        <?= html_escape($tplName) ?>
                                        <?php if ($isOff): ?>
                                          <span class="badge bg-secondary ms-2" aria-label="Disabled">Disabled</span>
                                        <?php endif; ?>
                                      </a>
                                      
                                      <?php if (ENVIRONMENT !== 'production' && $tplSlug !== ''): ?>
                                        <div class="text-muted small mt-1">
                                          <code><?= html_escape($tplSlug) ?></code>
                                        </div>
                                      <?php endif; ?>
                                    </div>

                                    <?php if (!empty($hasPermissionEdit)): ?>
                                      <div class="template-actions">
                                        <a 
                                          href="<?= site_url('emails/' . ($tplActive === 1 ? 'disable/' : 'enable/') . $tplId); ?>"
                                          class="btn btn-sm <?= $tplActive === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                          title="<?= $tplActive === 1 ? 'Disable template' : 'Enable template' ?>"
                                          aria-label="<?= $tplActive === 1 ? 'Disable ' . html_escape($tplName) : 'Enable ' . html_escape($tplName) ?>"
                                        >
                                          <small>
                                            <i class="fas fa-power-off me-1" aria-hidden="true"></i>
                                            <?= $tplActive === 1 ? 'Disable' : 'Enable' ?>
                                          </small>
                                        </a>
                                      </div>
                                    <?php endif; ?>
                                  </div>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </section>
                <?php endforeach; ?>

              <?php endif; ?>

            </div><!-- /.card-body -->
          </div><!-- /.card -->
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.table-inactive {
  opacity: 0.7;
  background-color: var(--bs-light);
}
.template-info {
  flex: 1;
}
.template-actions {
  flex-shrink: 0;
}
.email-template-section:last-of-type {
  margin-bottom: 0 !important;
}
</style>