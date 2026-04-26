<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

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
    
        <!-- Utilities -->
        <a href="<?= $canView ? site_url('utilities') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
           title="Import Data">
           <i class="ti ti-keyframes me-1"></i> Utilities
        </a>
        
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
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Database Backup">
           <i class="ti ti-database-export me-1"></i> Backup
        </a>
      
      </div>
    </div>  
    
  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">Tables</div>
              <div class="fs-4 fw-bold"><?= (int)($dbStats['table_count'] ?? 0) ?></div>
            </div>
            <i class="ti ti-database fs-2 text-primary"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">DB Size (MB)</div>
              <div class="fs-4 fw-bold"><?= number_format((float)($dbStats['db_size_mb'] ?? 0), 2) ?></div>
            </div>
            <i class="ti ti-server fs-2 text-success"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">Latest Backup</div>
              <?php if (!empty($latest_backup)): ?>
                <div class="fw-bold"><?= html_escape($latest_backup['name']) ?></div>
                <div class="small text-muted"><?= (float)$latest_backup['size'] ?> KB • <?= html_escape($latest_backup['date']) ?></div>
              <?php else: ?>
                <div class="fw-bold">No backups</div>
                <div class="small text-muted">Create one below</div>
              <?php endif; ?>
            </div>
            <i class="ti ti-archive fs-2 text-warning"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">Environment</div>
              <div class="fw-bold">PHP <?= html_escape($env['php_version']) ?></div>
              <div class="small text-muted">CI <?= html_escape($env['ci_version']) ?> • <?= html_escape($env['db_driver']) ?></div>
            </div>
            <i class="ti ti-settings fs-2 text-secondary"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Primary Utilities -->
  <div class="row g-3">
    <!-- Export -->
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <h6 class="card-title mb-2"><i class="ti ti-upload me-2 text-primary"></i>Export Data</h6>
          <p class="text-muted small mb-3">Export any allowed table to CSV and preview data.</p>
          <a href="<?= site_url('utilities/export') ?>" class="btn btn-primary btn-sm mt-auto">Open Export</a>
        </div>
      </div>
    </div>

    <!-- Import -->
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <h6 class="card-title mb-2"><i class="ti ti-download me-2 text-success"></i>Import Data</h6>
          <p class="text-muted small mb-3">Import CSV into any allowed table with validations.</p>
          <a href="<?= site_url('utilities/import') ?>" class="btn btn-success btn-sm mt-auto">Open Import</a>
        </div>
      </div>
    </div>

    <!-- Reports -->
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <h6 class="card-title mb-2"><i class="ti ti-report-analytics me-2 text-info"></i>Reports</h6>
          <p class="text-muted small mb-3">Browse auto-grouped report categories for all tables.</p>
          <a href="<?= site_url('utilities/reports') ?>" class="btn btn-info btn-sm mt-auto text-white">Open Reports</a>
        </div>
      </div>
    </div>

    <!-- Backups -->
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <h6 class="card-title mb-2"><i class="ti ti-database-export me-2 text-warning"></i>Backups</h6>
          <p class="text-muted small mb-3">Create ZIP backups and manage existing ones.</p>
          <div class="d-flex gap-2 mt-auto">
            <a href="<?= site_url('utilities/backups') ?>" class="btn btn-warning btn-sm text-dark">View Backups</a>
            <a href="<?= site_url('utilities/generate_backup') ?>" class="btn btn-outline-warning btn-sm">Generate</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Power Tools -->
  <div class="row g-3 mt-1">
    <!-- Show CREATE TABLE -->
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="ti ti-code me-2 text-secondary"></i>Show CREATE TABLE</h6>
<form class="row g-2" method="get" action="<?= site_url('utilities/table') ?>">
  <div class="col-md-8">
    <select name="table" class="form-select" required>
      <option value="" selected disabled>Select a table</option>
      <?php foreach ($tables as $t): ?>
        <option value="<?= html_escape($t) ?>"><?= html_escape($t) ?></option>
      <?php endforeach; ?>
    </select>
    <div class="form-text small">Open a full details page with columns, indexes, FKs, triggers, DDL, and backup history.</div>
  </div>
  <div class="col-md-4 d-flex align-items-start">
    <button type="submit" class="btn btn-outline-secondary btn-sm">Open Details</button>
  </div>
</form>

        </div>
      </div>
    </div>

    <!-- Quick CSV Download -->
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="ti ti-file-type-csv me-2 text-success"></i>Quick CSV Download</h6>
          <form class="row g-2" method="get" action="<?= site_url('utilities/download_csv') ?>">
            <div class="col-md-5">
              <select name="table" class="form-select" required>
                <option value="" selected disabled>Select a table</option>
                <?php foreach ($tables as $t): ?>
                  <option value="<?= html_escape($t) ?>"><?= html_escape($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-7 d-flex gap-2">
              <button type="submit" class="btn btn-primary btn-sm">Download Full CSV</button>
              <button type="submit" name="sample" value="1" class="btn btn-outline-primary btn-sm">Download Sample</button>
            </div>
          </form>
          <div class="form-text small mt-2">Sample contains only headers.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Environment Info -->
  <div class="row g-3 mt-1">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="card-title mb-2"><i class="ti ti-info-circle me-2 text-muted"></i>Environment</h6>
          <div class="row">
            <div class="col-md-3"><span class="text-muted small">Base URL</span><div class="fw-semibold"><?= html_escape($env['base_url']) ?></div></div>
            <div class="col-md-3"><span class="text-muted small">PHP</span><div class="fw-semibold"><?= html_escape($env['php_version']) ?></div></div>
            <div class="col-md-3"><span class="text-muted small">CodeIgniter</span><div class="fw-semibold"><?= html_escape($env['ci_version']) ?></div></div>
            <div class="col-md-3"><span class="text-muted small">DB Driver</span><div class="fw-semibold"><?= html_escape($env['db_driver']) ?></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
