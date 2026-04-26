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
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
    
        <!-- Import -->
        <a href="<?= $canView ? site_url('utilities/import') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Import Data">
           <i class="ti ti-upload me-1"></i> Import
        </a>
        
        <!-- Export -->
        <a href="<?= $canView ? site_url('utilities/export') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
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
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
           title="Database Backup">
           <i class="ti ti-database-export me-1"></i> Backup
        </a>
        
        <div class="btn-divider"></div>
    
        <a href="<?= $canCreate ? site_url('utilities/generate_backup') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canCreate ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
           title="Create Database Backup"
           <?= $canCreate ? '' : 'disabled' ?>>
           <i class="ti ti-cloud-download me-1"></i> Create Backup
        </a>
      
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'databaseTable' ?>">
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

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($backups)): ?>
                <div class="table-responsive">
                    <table class="table small table-hover align-middle" id="databaseTable">
                        <thead class="bg-light-primary">
                            <tr>
                                <th width="50%">Backup File Name</th>
                                <th width="20%">Backup Size (KB)</th>
                                <th width="15%">Date Created</th>
                                <th width="15%" class="text-center">Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td><?= html_escape($b['name']) ?></td>
                                    <td><?= $b['size'] ?></td>
                                    <td><?= $b['date'] ?></td>
                                    <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                    <!-- Download Button -->
                                    <a href="<?= $canCreate ? base_url('backup/' . $b['name']) : 'javascript:void(0);' ?>"
                                       class="btn btn-sm <?= $canCreate ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
                                       <?= $canCreate ? 'download' : 'disabled' ?>>
                                        <i class="ti ti-download"></i> Download
                                    </a>
                                    
                                    <!-- Delete Button -->
                                    <a href="<?= $canDelete ? site_url('utilities/delete_backup/' . urlencode($b['name'])) : 'javascript:void(0);' ?>"
                                       onclick="<?= $canDelete ? "return confirm('Are you sure to delete this backup?')" : 'return false;' ?>"
                                       class="btn btn-sm <?= $canDelete ? 'btn-outline-danger' : 'btn-outline-secondary disabled' ?>"
                                       <?= $canDelete ? '' : 'disabled' ?>>
                                        <i class="ti ti-trash"></i> Delete
                                    </a>
                                    </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="ti ti-inbox fs-1"></i>
                    <p>No backups found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
