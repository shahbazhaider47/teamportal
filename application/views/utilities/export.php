<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canView    = staff_can('view_global', 'utilities');
          $canCreate  = staff_can('create', 'utilities');
          $canDelete  = staff_can('delete', 'utilities');          
        ?>
    
        <!-- Import -->
        <a href="<?= $canView ? site_url('utilities/import') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Import Data">
           <i class="ti ti-upload me-1"></i> Import
        </a>
        
        <!-- Export -->
        <a href="<?= $canView ? site_url('utilities/export') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
           title="Export Data">
           <i class="ti ti-download me-1"></i> Export
        </a>
        
        <!-- Reports -->
        <a href="<?= $canView ? site_url('utilities/reports') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Reports">
           <i class="ti ti-chart-pie me-1"></i> Reports
        </a>
        
        <!-- Backups -->
        <a href="<?= $canView ? site_url('utilities/backups') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Database Backup">
           <i class="ti ti-database-export me-1"></i> Backup
        </a>

      </div>
    </div>
    
    <div class="card">
    <div class="card-body">        
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                <form method="get" action="<?= base_url('utilities/export') ?>" class="row gx-2 gy-2 align-items-end app-form">
                    <div class="col-md-10 col-12">
                        <label for="table" class="form-label">Select Table to Export</label>
                        <select name="table" id="table" class="form-control" required>
                            <option value="">Select Table</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?= html_escape($table) ?>"
                                    <?= (isset($selected_table) && $selected_table === $table) ? 'selected' : '' ?>>
                                    <?= human_table_label($table) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="ti ti-eye me-1"></i> Show Data
                        </button>
                    </div>
                </form>
                    
                    <?php if ($selected_table): ?>
                        <hr>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>
                                Table: <strong><?= human_table_label($selected_table) ?></strong>
                                <small class="text-muted">(<?= html_escape($selected_table) ?>)</small>
                            </h5>
                            <div>
                                <a href="<?= base_url('utilities/download_csv?table=' . urlencode($selected_table)) ?>" 
                                   class="btn btn-light-primary btn-sm">
                                    <i class="fa fa-download"></i> Export
                                </a>
                            </div>
                        </div>
                        
                        <?php if (!empty($columns)): ?>
                            <div class="alert alert-info small">
                                <strong>Columns:</strong> <?= implode(', ', $columns) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($rows)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <?php foreach ($columns as $col): ?>
                                                <th><?= html_escape($col) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($rows, 0, 100) as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $val): ?>
                                                    <td><?= html_escape(strlen($val) > 50 ? substr($val, 0, 50) . '...' : $val) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (count($rows) > 100): ?>
                                <div class="alert alert-warning">
                                    Displaying first 100 rows of <?= count($rows) ?> total rows.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No data found in this table.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>