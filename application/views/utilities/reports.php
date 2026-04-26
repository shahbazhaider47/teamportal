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
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
           title="Reports">
           <i class="ti ti-chart-pie me-1"></i> Reports
        </a>
        
        <!-- Backups -->
        <a href="<?= $canView ? site_url('utilities/backups') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Database Backup">
           <i class="ti ti-database-export me-1"></i> Backup
        </a>
        
        <div class="btn-divider"></div>
      
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'reportsTable' ?>">
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
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="reportsTable">
                    <thead class="bg-light-primary">
                        <tr>
                            <th>#</th>
                            <th>Report Name</th>
                            <th>Table</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($report_categories as $category => $reports):
                            foreach ($reports as $report):
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <span class="fw-bold"><?= html_escape($report['name']) ?></span>
                                <div class="small text-muted"><?= html_escape($category) ?></div>
                            </td>
                            <td><?= html_escape($report['table']) ?></td>
                            <td>
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm open-report-modal"
                                    data-table="<?= html_escape($report['table']) ?>"
                                    data-report="<?= html_escape($report['slug']) ?>"
                                    data-report-name="<?= html_escape($report['name']) ?>"
                                >
                                    <i class="ti ti-filter"></i> Generate
                                </button>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Report Filter Modal -->
<div class="modal fade" id="reportFilterModal" tabindex="-1" role="dialog" aria-labelledby="reportFilterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="reportFilterForm">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="reportFilterModalLabel"><i class="ti ti-filter"></i> Filter Report</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="reportModalBody">
          <!-- Dynamic filter fields will be loaded here by JS -->
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Generate Report</button>
        </div>
      </div>
    </form>
  </div>
</div>


<!-- Scripts, order matters! -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<script>
$(document).on('click', '.open-report-modal', function () {
    var button = $(this);
    var table = button.data('table');
    var report = button.data('report');
    var reportName = button.data('report-name');
    $('#reportFilterModalLabel').text('Filter: ' + reportName);

    // Show loading state
    $('#reportModalBody').html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading filters...</div>');

    // Open the modal (Bootstrap 4/5 compatible)
    $('#reportFilterModal').modal('show');

    // Simulate loading filter fields based on table/report (replace with AJAX if needed)
    setTimeout(function() {
        var fieldsHtml = '';
        if(table === 'users') {
            fieldsHtml = 
                <div class="form-group">
                    <label>User Status</label>
                    <select name="status" class="form-control">
                        <option value="">Any</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Signup Date Range</label>
                    <input type="date" name="from_date" class="form-control mb-1">
                    <input type="date" name="to_date" class="form-control">
                </div>
            ;
        } else if(table === 'attendance') {
            fieldsHtml = 
                <div class="form-group">
                    <label>Month</label>
                    <input type="month" name="month" class="form-control">
                </div>
                <div class="form-group">
                    <label>Employee</label>
                    <input type="text" name="employee" class="form-control" placeholder="Employee Name">
                </div>
            ;
        } else {
            fieldsHtml = '<div class="alert alert-info">No filter fields defined for this report.</div>';
        }
        $('#reportModalBody').html(fieldsHtml);
    }, 350);
});

</script>