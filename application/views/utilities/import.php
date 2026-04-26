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
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
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
    
    <div class="row no-gutters">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">
                            <i class="fas fa-database mr-2"></i> Import Custom Data
                        </h5>
                        <span class="badge badge-light badge-pill">v2.1.0</span>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($status): ?>
                        <div class="alert alert-<?= $status_type ?> alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas <?= $status_type == 'danger' ? 'fa-exclamation-circle' : ($status_type == 'success' ? 'fa-check-circle' : 'fa-info-circle') ?> mr-2"></i>
                                <div><?= $status ?></div>
                            </div>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" enctype="multipart/form-data" id="importForm" class="needs-validation app-form" novalidate>
                        <div class="row">
                            <div class="col-md-5">
                                <label for="tableSelect" class="font-weight-bold">
                                    <i class="fas fa-table mr-1 mb-3 text-primary"></i> Select Table <small class="form-text text-muted mt-1"> (Choose the destination table for your data)</small>
                                </label>
                                <select name="table" id="tableSelect" class="form-control form-control" required>
                                    <option value="" disabled selected>Select a table...</option>
                                    <?php foreach ($tables as $table): ?>
                                        <option value="<?= html_escape($table) ?>">
                                            <?= human_table_label($table) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a table to import data.
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="importFile" class="font-weight-bold">
                                    <i class="fas fa-file-csv mr-1 mb-3 text-primary"></i> CSV File <small class="form-text text-muted mt-1"> (Max 5MB. Must match table structure exactly)</small>
                                </label>
                                <div class="custom-file">
                                    <input type="file" name="import_file" id="importFile" class="custom-file-input form-control app-form" required accept=".csv, text/csv">
                                    <label class="custom-file-label" for="importFile" data-browse="Browse"></label>
                                </div>
                                <div class="invalid-feedback">
                                    Please select a CSV file to import.
                                </div>
                            </div>
                            
                        <div class="col-md-2 mt-2">
                            <div class=" app-form text-start mt-4">
                                <button type="submit" name="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-cloud-upload-alt"></i> Import Data
                                </button>
                            </div>
                        </div>
                        
                        </div>
                        
                        <div class="card form-group mb-4" id="sampleCsvContainer" style="display:none;">
                            <div class="d-flex align-items-center bg-white p-4 rounded">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 font-weight-bold"><i class="fas fa-file-download fa-1x text-info"></i> Sample Template For: <span id="templateTableName" class="badge bg-light-primary small"></span></h6>
                                    <p class="mb-1 small text-dark text-muted">Download a sample CSV file with the correct structure for your selected table. Change the table to load the related file for the table structre.</p>
                                    <a href="#" id="downloadSampleBtn" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-download mr-1"></i> Download Sample CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                        
                        
                        <div class="app-divider-v light"></div>  
                        
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="alert alert-light-info mb-4">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <strong>Import Guidelines:</strong> 
                                                <ul class="mb-2 pl-3 small">
                                                    <li class="mb-2 mt-3"><i class="ti ti-arrow-badge-right"></i> <b>Select Target Table:</b> Choose the database table where you want to import your data.</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> <b>Download Template:</b> Get the sample CSV to ensure proper formatting.</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> <b>Prepare Your Data:</b> Match your CSV columns exactly to the template.</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> <b>Upload CSV File:</b> Select your prepared CSV file for import.</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> <b>Review & Confirm:</b> Verify your selections before importing.</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> <b>Execute Import:</b> Complete the process and verify results..</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="alert alert-light-danger mb-4">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <strong>Important:</strong> 
                                                <ul class="mb-2 pl-3 small">
                                                    <li class="mb-2 mt-3"><i class="ti ti-arrow-badge-right"></i> Store everything as Y-m-d H:i:s</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> Existing records with matching keys will be updated</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> New records will be inserted</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> Column headers must match exactly</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> Large files may take several minutes to process</li>
                                                    <li class="mb-2"><i class="ti ti-arrow-badge-right"></i> Do not alter sample files or sheet headers.</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <div class="text-center small text-muted mt-4">
                        <i class="fas fa-lock mr-1"></i> Your data will processed securely.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Update file input label with filename
    $('#importFile').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        var label = $(this).next('.custom-file-label');
        
        if (fileName) {
            label.find('span').text(fileName);
            label.addClass('selected');
        } else {
            label.find('span').text('Choose CSV file...');
            label.removeClass('selected');
        }
    });
    
    // Show/hide sample CSV link based on selected table
    $('#tableSelect').on('change', function() {
        var table = $(this).val();
        if (table) {
            var url = '<?= base_url("utilities/download_csv") ?>?table=' + encodeURIComponent(table) + '&sample=1';
            $('#downloadSampleBtn').attr('href', url);
            var tableText = $('#tableSelect option:selected').text();
            $('#templateTableName').text(table ? ' ' + tableText + '' : '');
            $('#sampleCsvContainer').fadeIn(200);
        } else {
            $('#sampleCsvContainer').fadeOut(200);
        }
    });
    
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var form = document.getElementById('importForm');
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    var table = $('#tableSelect').val();
                    var file = $('#importFile').val();
                    
                    if (!table || !file) return;
                    
                    if (!confirm('Are you sure you want to import data into ' + $('#tableSelect option:selected').text() + '?\n\nThis action cannot be undone.')) {
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    }
                    
                    // Show loading state
                    var submitBtn = $(form).find('[type="submit"]');
                    submitBtn.prop('disabled', true);
                    submitBtn.html('<i class="fas fa-circle-notch fa-spin mr-2"></i> Importing...');
                }
                form.classList.add('was-validated');
            }, false);
        }, false);
    })();
});
</script>
