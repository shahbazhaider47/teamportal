<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
        <div class="d-flex align-items-center small gap-1">
        </div>        
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
          $table_id   = $table_id ?? 'contractsTable';
        ?>

        <!-- Renew All Contracts -->
      <?php if (staff_can('create', 'contracts')): ?>
        <a href="<?= site_url('contracts/bulk_renew'); ?>" class="btn btn-light-primary btn-header">
          <i class="ti ti-plus me-1"></i> Renew All Contracts
        </a>
      <?php endif; ?>

        <div class="btn-divider"></div>
        
        <!-- Add New Contract -->
      <?php if (staff_can('create', 'contracts')): ?>
        <a href="<?= site_url('contracts/create'); ?>" class="btn btn-primary btn-header">
          <i class="ti ti-plus me-1"></i> New Contract
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
    
    
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
          <thead class="bg-light-primary">
            <tr>
              <th>ID #</th>
              <th>Employee Name</th>
              <th>Designation</th>
              <th>Department</th>
              <th>Contract Type</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Renewable</th>
              <th>Status</th>              
              <th>Created At</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($contracts)): ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">No contracts found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($contracts as $row): ?>
              <tr>
                <td><?= (int)$row['id']; ?></td>
                

                    <td>
                      <div class="d-flex align-items-start gap-2">
                        <?= user_profile((int)$row['user_id']) ?>
                        <div class="d-flex flex-column lh-sm">
                          <span class="fw-medium">
                            <?= e($row['fullname']) ?>
                          </span>
                          <small class="text-muted">
                            <?= emp_id_display($row['emp_id']) ?>
                          </small>
                        </div>
                      </div>
                    </td> 
                    
                <td><?= resolve_emp_title($row['emp_title']); ?></td>
                <td><?= html_escape($row['department_name']); ?></td>
                <td><?= html_escape($row['contract_type']); ?></td>
                <td><?= format_date ($row['start_date']) ?: '—'; ?></td>
                <td><?= format_date ($row['end_date']) ?: '—'; ?></td>
                <td><?= !empty($row['is_renewable']) ? 'Yes' : 'No'; ?></td>
                <td>
                  <span class="badge bg-light-primary">
                    <?= ucfirst(html_escape($row['status'])); ?>
                  </span>
                </td>                
                <td><?= format_datetime ($row['created_at']) ?: '—'; ?></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= site_url('contracts/view/' . (int)$row['id']); ?>" class="btn btn-light-primary">
                      <i class="ti ti-eye"></i>
                    </a>
                    <?php if (staff_can('edit', 'contracts')): ?>
                      <a href="<?= site_url('contracts/edit/' . (int)$row['id']); ?>" class="btn btn-light-primary">
                        <i class="ti ti-edit"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
