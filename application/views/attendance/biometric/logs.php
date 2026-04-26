<?php defined('BASEPATH') or exit('No direct script access allowed'); $d = $device; ?>
<div class="container-fluid">
    
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title">Logs — <?= html_escape($d['name']) ?> (<?= html_escape($d['ip_address'].':'.$d['port']) ?>)</h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
        
      <a href="<?= site_url('attendance/biometric/settings') ?>" class="btn btn-outline-primary btn-header">Settings</a>
        
        <div class="btn-divider"></div>

        <a class="btn btn-light-primary btn-header" href="<?= site_url('attendance/biometric') ?>"><i class="ti ti-arrow-left me-2"></i> Go Back</a>
    
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
    
  <div class="row g-3">
    <div class="col-lg-6 app-form">
      <div class="card p-4">
        <h6>Manual Fetch</h6>
            <small class="text-muted d-block">
              Fetch attendance data manually from the device with date range.
            </small>        
        <div class="app-divider-v dotted mb-3"></div>
        <?= form_open(site_url('attendance/biometric/fetch_now/'.$d['id'])) ?>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">From Date</label>
              <input type="date" name="from" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">To Date</label>
              <input type="date" name="to" class="form-control">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm">Fetch Logs</button>
          </div>
        <?= form_close() ?>
      </div>
    </div>

    <div class="col-lg-6 app-form">
      <div class="card p-4">
        <h6>Transform to Attendance</h6>
            <small class="text-muted d-block">
              Flags: duplicates, late arrivals, early leaves, missed checkouts are computed.
            </small>        
        <div class="app-divider-v dotted mb-3"></div>
        <?= form_open(site_url('attendance/biometric/import_to_attendance/'.$d['id'])) ?>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Range From</label>
              <input type="date" name="range_from" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Range To</label>
              <input type="date" name="range_to" class="form-control" value="<?= date('Y-m-t') ?>">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm">Transform</button>
          </div>
        <?= form_close() ?>
      </div>
    </div>
  </div>
</div>
