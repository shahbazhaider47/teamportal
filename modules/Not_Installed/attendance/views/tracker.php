<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
        
        <a href="<?= site_url('attendance') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-clock"></i> Attendance
        </a>
        <a href="<?= site_url('attendance/leaves') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-clipboard-list"></i> Leaves
        </a>
        <a href="<?= site_url('attendance/calendar') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-calendar-event"></i> Calendar
        </a>
        <a href="<?= site_url('attendance/tracker') ?>"
           class="btn btn-primary btn-header">
            <i class="ti ti-map-pin"></i> Tracker
        </a>
        
        <div class="btn-divider"></div>

        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'trackerTable' ?>">
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
    
  <?php
    // Normalize and safely cast all leave data
    $casual     = $leave_balances['casual']    ?? ['used' => 0, 'total' => 0];
    $medical    = $leave_balances['medical']   ?? ['used' => 0, 'total' => 0];
    $holiday    = $leave_balances['holiday']   ?? ['used' => 0, 'total' => 0];
    $emergency  = $leave_balances['emergency'] ?? ['used' => 0, 'total' => 0];
    $short      = $leave_balances['short']     ?? ['used' => 0, 'total' => 0];

    $casual_used     = (int)($casual['used'] ?? 0);
    $casual_total    = (int)($casual['total'] ?? 0);
    $medical_used    = (int)($medical['used'] ?? 0);
    $medical_total   = (int)($medical['total'] ?? 0);
    $holiday_used    = (int)($holiday['used'] ?? 0);
    $holiday_total   = (int)($holiday['total'] ?? 0);
    $emergency_used  = (int)($emergency['used'] ?? 0);
    $short_used      = (int)($short['used'] ?? 0);
    $short_converted = floor($short_used / 2);

    $adjusted_casual_used = $casual_used + $emergency_used + $short_converted;
    $adjusted_casual_pct  = $casual_total > 0 ? ($adjusted_casual_used / $casual_total * 100) : 0;
  ?>

  <div class="row g-3 mb-4">
    <!-- Adjusted Casual -->
    <div class="col-md-3">
      <div class="card border-start border-success shadow-sm">
        <div class="card-body">
          <h6 class="card-title">Casual (Adjusted)</h6>
          <p class="mb-1 text-muted small">
            Used: <?= $adjusted_casual_used ?> / <?= $casual_total ?> <small>( C:<?= $casual_used ?> + E:<?= $emergency_used ?> + S:<?= $short_used ?> ➜ <?= $short_converted ?> )</small>
          </p>
          <div class="progress" style="height: 6px;">
            <div class="progress-bar bg-success" role="progressbar"
                 style="width: <?= min(100, max(2, $adjusted_casual_pct)) ?>%;"
                 aria-valuenow="<?= $adjusted_casual_used ?>" aria-valuemin="0" aria-valuemax="<?= $casual_total ?>"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Medical and Holiday -->
    <?php foreach ([
      'medical' => ['used' => $medical_used, 'total' => $medical_total],
      'holiday' => ['used' => $holiday_used, 'total' => $holiday_total]
    ] as $type => $data): ?>
      <?php
        $percent = $data['total'] > 0 ? ($data['used'] / $data['total'] * 100) : 0;
      ?>
      <div class="col-md-3">
        <div class="card border-start border-primary shadow-sm">
          <div class="card-body">
            <h6 class="card-title text-capitalize"><?= ucfirst($type) ?></h6>
            <p class="mb-1 text-muted small">Used: <?= $data['used'] ?> / <?= $data['total'] ?></p>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-primary" role="progressbar"
                   style="width: <?= min(100, max(2, $percent)) ?>%;"
                   aria-valuenow="<?= $data['used'] ?>" aria-valuemin="0" aria-valuemax="<?= $data['total'] ?>"></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Short -->
    <div class="col-md-3">
      <div class="card border-start border-warning shadow-sm">
        <div class="card-body">
          <h6 class="card-title">Short</h6>
          <p class="mb-1 text-muted small">Used: <?= $short_used ?> (½ counts as 1 Casual)</p>
          <div class="progress" style="height: 6px;">
            <div class="progress-bar bg-warning" role="progressbar"
                 style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Leave Summary Table -->
  <div class="card">
    <div class="card-header text-light-primary d-flex justify-content-between align-items-center">
      <span class="fw-bold">Leave Summary</span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered text-center" id="trackerTable">
          <thead class="table-light">
            <tr>
              <th>Leave Type</th>
              <th>Allowed</th>
              <th>Taken</th>
              <th>Balance</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Casual (Adjusted)</td>
              <td><?= $casual_total ?></td>
              <td>
                <?= $adjusted_casual_used ?>
                <small class="text-muted d-block">(C:<?= $casual_used ?>, E:<?= $emergency_used ?>, S:<?= $short_used ?>)</small>
              </td>
              <td>
                <?php $bal = $casual_total - $adjusted_casual_used; ?>
                <?= $bal ?>
                <?php if ($bal < 0): ?>
                  <span class="badge bg-danger ms-1">Overused</span>
                <?php endif; ?>
              </td>
            </tr>

            <tr>
              <td>Medical</td>
              <td><?= $medical_total ?></td>
              <td><?= $medical_used ?></td>
              <td>
                <?php $bal = $medical_total - $medical_used; ?>
                <?= $bal ?>
                <?php if ($bal < 0): ?>
                  <span class="badge bg-danger ms-1">Overused</span>
                <?php endif; ?>
              </td>
            </tr>

            <tr>
              <td>Holiday</td>
              <td><?= $holiday_total ?></td>
              <td><?= $holiday_used ?></td>
              <td>
                <?php $bal = $holiday_total - $holiday_used; ?>
                <?= $bal ?>
                <?php if ($bal < 0): ?>
                  <span class="badge bg-danger ms-1">Overused</span>
                <?php endif; ?>
              </td>
            </tr>

            <tr>
              <td>Short Leave</td>
              <td>-</td>
              <td><?= $short_used ?></td>
              <td>Used as ½ Casual Leave</td>
            </tr>

            <tr>
              <td>Emergency</td>
              <td>-</td>
              <td><?= $emergency_used ?></td>
              <td>Deducted from Casual</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
