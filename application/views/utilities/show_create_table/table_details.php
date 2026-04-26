<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

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
        
        <!-- Utilities -->
        <a href="<?= $canView ? site_url('utilities') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
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
        
        <div class="btn-divider"></div>
        
        <a href="<?= $canView ? site_url('utilities/download_create_sql/'.$table) : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>"
           title="Database Backup">
           <i class="ti ti-download me-1"></i>Download Create
        </a>
        
      </div>
    </div> 

<div class="card">
    <div class="card-body">
  <!-- Quick stats -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Engine</div>
          <div class="fs-6 fw-semibold"><?= html_escape($status['Engine'] ?? 'n/a') ?></div>
          <div class="text-muted small mt-2">Row Format</div>
          <div class="fw-semibold"><?= html_escape($status['Row_format'] ?? 'n/a') ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Rows</div>
          <div class="fs-6 fw-semibold"><?= number_format((float)($status['Rows'] ?? 0)) ?></div>
          <div class="text-muted small mt-2">Avg Row Len</div>
          <div class="fw-semibold"><?= number_format((float)($status['Avg_row_length'] ?? 0)) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Data Size</div>
          <div class="fs-6 fw-semibold">
            <?= number_format(((float)($status['Data_length'] ?? 0))/1024/1024, 2) ?> MB
          </div>
          <div class="text-muted small mt-2">Index Size</div>
          <div class="fw-semibold">
            <?= number_format(((float)($status['Index_length'] ?? 0))/1024/1024, 2) ?> MB
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Collation</div>
          <div class="fs-6 fw-semibold"><?= html_escape($status['Collation'] ?? 'n/a') ?></div>
          <div class="text-muted small mt-2">Created / Updated</div>
          <div class="fw-semibold">
            <?= html_escape($status['Create_time'] ?? 'n/a') ?> /
            <?= html_escape($status['Update_time'] ?? 'n/a') ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs: Columns / Indexes / FKs / Triggers / DDL / History -->
  <ul class="nav nav-tabs app-tabs-primary mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-columns" type="button">Columns</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-indexes" type="button">Indexes</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fks" type="button">Foreign Keys</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-triggers" type="button">Triggers</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ddl" type="button">CREATE TABLE</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history" type="button">History (Backups)</button></li>
  </ul>

  <div class="tab-content">
    <!-- Columns -->
    <div class="tab-pane fade show active" id="tab-columns">
      <div class="card shadow-sm">
        <div class="card-body table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="bg-light-primary">
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Type</th>
                <th>Null</th>
                <th>Default</th>
                <th>Extra</th>
                <th>Key</th>
                <th>Charset/Collation</th>
                <th>Comment</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($columns as $col): ?>
              <tr>
                <td><?= (int)$col['ORDINAL_POSITION'] ?></td>
                <td class="fw-semibold"><?= html_escape($col['COLUMN_NAME']) ?></td>
                <td><code><?= html_escape($col['COLUMN_TYPE']) ?></code></td>
                <td><?= html_escape($col['IS_NULLABLE']) ?></td>
                <td><code><?= html_escape($col['COLUMN_DEFAULT']) ?></code></td>
                <td><?= html_escape($col['EXTRA']) ?></td>
                <td><?= html_escape($col['COLUMN_KEY']) ?></td>
                <td>
                  <?php
                    $cc = [];
                    if (!empty($col['CHARACTER_SET_NAME'])) $cc[] = $col['CHARACTER_SET_NAME'];
                    if (!empty($col['COLLATION_NAME']))     $cc[] = $col['COLLATION_NAME'];
                    echo html_escape(implode(' / ', $cc));
                  ?>
                </td>
                <td><?= html_escape($col['COLUMN_COMMENT']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Indexes -->
    <div class="tab-pane fade" id="tab-indexes">
      <div class="card shadow-sm">
        <div class="card-body table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="bg-light-primary">
              <tr>
                <th>Key Name</th>
                <th>Non-Unique</th>
                <th>Seq</th>
                <th>Column</th>
                <th>Type</th>
                <th>Cardinality</th>
                <th>Collation</th>
                <th>Null</th>
                <th>Comment</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($indexes as $ix): ?>
              <tr>
                <td class="fw-semibold"><?= html_escape($ix['Key_name']) ?></td>
                <td><?= (int)$ix['Non_unique'] ?></td>
                <td><?= (int)$ix['Seq_in_index'] ?></td>
                <td><?= html_escape($ix['Column_name']) ?></td>
                <td><?= html_escape($ix['Index_type']) ?></td>
                <td><?= html_escape($ix['Cardinality']) ?></td>
                <td><?= html_escape($ix['Collation']) ?></td>
                <td><?= html_escape($ix['Null']) ?></td>
                <td><?= html_escape($ix['Comment']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Foreign Keys -->
    <div class="tab-pane fade" id="tab-fks">
      <div class="card shadow-sm">
        <div class="card-body table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="bg-light-primary">
              <tr>
                <th>Constraint</th>
                <th>Column</th>
                <th>References</th>
                <th>On Update</th>
                <th>On Delete</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($foreignKeys)): ?>
              <tr><td colspan="5" class="text-muted small">No foreign keys found.</td></tr>
            <?php else: foreach ($foreignKeys as $fk): ?>
              <tr>
                <td class="fw-semibold"><?= html_escape($fk['CONSTRAINT_NAME']) ?></td>
                <td><?= html_escape($fk['COLUMN_NAME']) ?></td>
                <td>
                  <?= html_escape($fk['REFERENCED_TABLE_NAME']) ?>(
                  <?= html_escape($fk['REFERENCED_COLUMN_NAME']) ?>)
                </td>
                <td><?= html_escape($fk['UPDATE_RULE']) ?></td>
                <td><?= html_escape($fk['DELETE_RULE']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Triggers -->
    <div class="tab-pane fade" id="tab-triggers">
      <div class="card shadow-sm">
        <div class="card-body table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="bg-light-primary">
              <tr>
                <th>Name</th>
                <th>Timing</th>
                <th>Event</th>
                <th>Statement</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($triggers)): ?>
              <tr><td colspan="4" class="text-muted small">No triggers found.</td></tr>
            <?php else: foreach ($triggers as $tg): ?>
              <tr>
                <td class="fw-semibold"><?= html_escape($tg['Trigger']) ?></td>
                <td><?= html_escape($tg['Timing']) ?></td>
                <td><?= html_escape($tg['Event']) ?></td>
                <td><code class="small d-block" style="white-space:pre-wrap"><?= html_escape($tg['Statement']) ?></code></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- CREATE TABLE (DDL) -->
    <div class="tab-pane fade" id="tab-ddl">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Current DDL</h6>
            <a class="btn btn-outline-primary btn-sm" href="<?= site_url('utilities/download_create_sql/'.$table) ?>">
              <i class="ti ti-download me-1"></i>Download .sql
            </a>
          </div>
          <pre class="bg-light-primary p-3 rounded border" style="white-space:pre-wrap; font-size:12px;"><code><?= html_escape($createSql) ?>;</code></pre>
          <?php if (!empty($status['Comment'])): ?>
            <div class="text-muted small mt-2">Comment: <?= html_escape($status['Comment']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- History (from backups) -->
    <div class="tab-pane fade" id="tab-history">
      <div class="card shadow-sm">
        <div class="card-body">
          <?php if (empty($history)): ?>
            <div class="text-muted">No CREATE TABLE snapshots found in recent backups.</div>
          <?php else: ?>
            <p class="text-muted small mb-3">Snapshots extracted from your latest backup ZIPs. This approximates schema change history without additional databases.</p>
            <?php foreach ($history as $h): ?>
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <strong><?= html_escape($h['backup']) ?></strong>
                    <span class="text-muted small ms-2"><?= html_escape($h['date']) ?></span>
                  </div>
                </div>
                <pre class="bg-light-primary p-3 rounded border mt-2" style="font-size:12px;"><code><?= html_escape($h['snippet']) ?></code></pre>
              </div>
              <hr>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>
</div>
</div>