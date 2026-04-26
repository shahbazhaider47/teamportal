<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_id = 'myAttendanceLogsTable';
?>

<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title mb-2">
        <?= html_escape($page_title ?? 'My Attendance Logs') ?>
        <i class="ti ti-chevron-right"></i>
        <span class="small text-muted"><?= html_escape($monthLabel ?? '') ?></span>
      </h1>

      <?php if (!empty($user)): ?>
        <span class="badge bg-white text-muted">
          <?= user_profile_image((int)($user['id'] ?? 0)) ?>
        </span>
      <?php endif; ?>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">

      <!-- Month Navigation -->
      <div class="d-flex align-items-center gap-2">
        <a href="<?= html_escape($prevUrl ?? '#') ?>" class="btn btn-header btn-outline-primary">
          <i class="ti ti-chevron-left"></i>
        </a>

        <a href="<?= html_escape($currentUrl ?? '#') ?>" class="btn btn-header btn-primary">
          <i class="ti ti-calendar me-2"></i> This Month
        </a>

        <a href="<?= html_escape($nextUrl ?? '#') ?>" class="btn btn-header btn-outline-primary">
          <i class="ti ti-chevron-right"></i>
        </a>
      </div>
      
      <div class="btn-divider"></div>
      
        <?php $CI =& get_instance(); ?>
        <?php echo $CI->load->view('attendance/partials/att_user_menu', [], true); ?>

        
      <div class="btn-divider"></div>

      <?php
        $exportFile = 'my_attendance_' . ($currentYear ?? date('Y')) . '_' . str_pad((string)($currentMonth ?? date('n')), 2, '0', STR_PAD_LEFT);
        if (function_exists('render_export_buttons')) {
            render_export_buttons(['filename' => $exportFile]);
        }
      ?>

    </div>
  </div>

  <!-- Universal table filter (global search + per-column filters) -->
  <div class="collapse multi-collapse" id="showFilter">
    <div class="card mb-3">
      <div class="card-body">
        <?php if (function_exists('app_table_filter')): ?>
          <?php app_table_filter($table_id, [
            'exclude_columns' => ['#'],
          ]); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

<div class="row g-3 mb-0">
    
<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Total Check-Ins</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-primary f-w-600"><?= (int)($totalCheckIns ?? 0) ?></h4>
          <p class="m-0 text-muted small"><?= html_escape($monthLabel ?? '') ?></p>
        </div>
      </div>
      <div class="project-card-icon bg-light-primary h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-login f-s-16 mb-1 text-primary"></i>
      </div>
    </div>
  </div>
</div>

<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Total Check-Outs</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-primary f-w-600"><?= (int)($totalCheckOuts ?? 0) ?></h4>
          <p class="m-0 text-muted small"><?= html_escape($monthLabel ?? '') ?></p>
        </div>
      </div>
      <div class="project-card-icon bg-light-primary h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-logout f-s-16 mb-1 text-primary"></i>
      </div>
    </div>
  </div>
</div>

    
  <div class="col-md-3 col-lg-2">
    <div class="card project-cards">
      <div class="card-body d-flex justify-content-between">
        <div>
          <h6 class="small">Late Minutes</h6>
          <div class="d-flex align-items-center gap-2 mt-2">
            <h4 class="f-w-600 <?= ($totalLateMinutes ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
              <?= ($totalLateMinutes ?? 0) > 0 ? (int)$totalLateMinutes : '—' ?>
            </h4>
            <p class="m-0 text-muted small"><?= html_escape($monthLabel ?? '') ?></p>
          </div>
        </div>
        <div class="project-card-icon bg-light-danger h-35 w-35 d-flex-center b-r-100">
          <i class="ti ti-clock f-s-16 mb-1 text-danger"></i>
        </div>
      </div>
    </div>
  </div>

    <div class="col-md-3 col-lg-2">
      <div class="card project-cards">
        <div class="card-body d-flex justify-content-between">
          <div>
            <h6 class="small">Early Checkouts</h6>
            <div class="d-flex align-items-center gap-2 mt-2">
              <h4 class="f-w-600 <?= ($totalEarlyCheckouts ?? 0) > 0 ? 'text-warning' : 'text-muted' ?>">
                <?= (int)($totalEarlyCheckouts ?? 0) ?>
              </h4>
              <p class="m-0 text-muted small"><?= html_escape($monthLabel ?? '') ?>
              <i class="ti ti-dots-vertical"></i>
              <span class="badge bg-light-danger">
                <?= ($totalEarlyMinutes ?? 0) > 0 ? (int)$totalEarlyMinutes : '—' ?> Minutes
              </span>
              </p>
            </div>
          </div>
          <div class="project-card-icon bg-light-warning h-35 w-35 d-flex-center b-r-100">
            <i class="ti ti-alert-circle f-s-16 mb-1 text-warning"></i>
          </div>
        </div>
      </div>
    </div>

<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">OT Days</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="f-w-600 <?= ($totalOTDays ?? 0) > 0 ? 'text-success' : 'text-muted' ?>">
            <?= (int)($totalOTDays ?? 0) ?>
          </h4>
          <p class="m-0 text-muted small"><?= html_escape($monthLabel ?? '') ?></p>
        </div>
      </div>
      <div class="project-card-icon bg-light-success h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-calendar-stats f-s-16 mb-1 text-success"></i>
      </div>
    </div>
  </div>
</div>


<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Overtime Minutes</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="f-w-600 <?= ($totalOvertimeMinutes ?? 0) > 0 ? 'text-success' : 'text-muted' ?>">
            <?= ($totalOvertimeMinutes ?? 0) > 0 ? (int)$totalOvertimeMinutes : '—' ?>
          </h4>
          <p class="m-0 text-muted small"><?= html_escape($monthLabel ?? '') ?></p>
        </div>
      </div>
      <div class="project-card-icon bg-light-success h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-trending-up f-s-16 mb-1 text-success"></i>
      </div>
    </div>
  </div>
</div>

  
</div>

  <!-- Attendance Logs Table -->
  <div class="card mt-0">
    <div class="card-body">

      <?php if (empty($logs)) : ?>
        <div class="p-4 text-center text-muted fst-italic">
          No attendance logs found for <?= html_escape($monthLabel ?? '') ?>.
        </div>
      <?php else : ?>

        <div class="mt-1 mb-3 text-start">
          <small class="text-muted">
            Total <strong class="text-primary"><?= count($logs) ?></strong> Attendance Records for <?= $monthLabel ?>
            <i class="ti ti-dots-vertical"></i>
            <span class="badge bg-light-success">
            Working Days: 
            <strong><?= ($totalWorkDays ?? 0) > 0 ? (int)$totalWorkDays : '0' ?></strong>
            </span>

            <i class="ti ti-dots-vertical"></i>
            <span class="badge bg-light-primary">
            Holidays: 
            <strong><?= ($totalHolidays ?? 0) > 0 ? (int)$totalHolidays : '0' ?></strong>
            </span>

            <i class="ti ti-dots-vertical"></i>
            <span class="badge bg-light-warning">
            Off Days: 
            <strong><?= ($totalOffDays ?? 0) > 0 ? (int)$totalOffDays : '0' ?></strong>
            </span>

            <i class="ti ti-dots-vertical"></i>
            <span class="badge bg-light-info">
            Month Days: 
            <strong><?= (int)($totalDays ?? 0) ?></strong>
            </span>
            
          </small>
        </div>
        
        <div class="table-responsive">
          <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id) ?>">
            <thead class="bg-light-primary">
              <tr class="text-nowrap">
                <th>#</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Approval</th>
                <th>Late Minutes</th>
                <th>Early Minutes</th>
                <th>OT Minutes</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $index => $row): ?>
                <?php
                  $dt = $row['datetime'] ?? '';
                  $status = $row['status'] ?? '';

                  // Status badge mapping (simple + safe)
                  $badge = 'secondary';
                  if (in_array($status, ['check_in','overtime_in'], true)) {
                      $badge = 'success';
                  } elseif (in_array($status, ['check_out','overtime_out'], true)) {
                      $badge = 'info';
                  } elseif ($status === 'other') {
                      $badge = 'warning';
                  }

                  // Approval badge mapping
                  $approval = strtolower($row['approval_status'] ?? '');
                  $aBadge   = 'secondary';
                  if ($approval === 'approved') $aBadge = 'success';
                  elseif ($approval === 'rejected') $aBadge = 'danger';
                  elseif ($approval === 'pending')  $aBadge = 'warning';

                  // Optional columns (avoid undefined index warnings)
                  $device = $row['device_name'] ?? ($row['device'] ?? '—');
                  $ip     = $row['ip_address'] ?? ($row['ip'] ?? '—');
                ?>
                <tr>
                  <td class="text-muted"><?= (int)$index + 1 ?></td>

                    <td class="text-nowrap">
                        <?= !empty($row['datetime'])
                            ? html_escape(date('l', strtotime($row['datetime'])) . ' • ' . format_datetime($row['datetime'], 'Y-m-d H:i'))
                            : '—'
                        ?>
                    </td>

                  <td>
                    <span class="badge bg-<?= $badge ?>">
                      <?= html_escape(ucwords(str_replace('_',' ', $status ?: 'N/A'))) ?>
                    </span>
                  </td>

                  <td>
                    <span class="badge bg-<?= $aBadge ?>">
                      <?= html_escape(ucfirst($approval ?: 'N/A')) ?>
                    </span>
                  </td>

                    <?php
                      $late = (int)($lateMinutes[$index] ?? 0);
                    ?>
                    <td class="<?= $late > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                      <?= $late > 0 ? $late : '—' ?>
                    </td>
                    
                    <?php $early = (int)($earlyMinutes[$index] ?? 0); ?>
                    
                    <td class="<?= $early > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>">
                      <?= $early > 0 ? $early : '—' ?>
                    </td>
                    
                    <?php
                      $ot  = (int)($overtimeMinutes[$index] ?? 0);
                      $otx = $overtimeMeta[$index] ?? ['is_exceeded' => false, 'max' => 0];
                    
                      $isExceeded = !empty($otx['is_exceeded']);
                      $maxOT      = (int)($otx['max'] ?? 0);
                    ?>
                    
                    <td class="<?= $ot > 0 ? ($isExceeded ? 'text-danger fw-semibold' : 'text-success fw-semibold') : 'text-muted' ?>">
                      <?php if ($ot <= 0): ?>
                        —
                      <?php else: ?>
                        <?php if ($isExceeded && $maxOT > 0): ?>
                          OT <?= (int)$ot ?> 
                          <span class="small">
                          (Exceeded)     
                          </span>
                        <?php else: ?>
                          <?= (int)$ot ?>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php endif; ?>
    </div>
  </div>

</div>

<?php if (!empty($logs)): ?>
<script>
$(document).ready(function() {

  if ($.fn.DataTable) {
    $('#<?= $table_id ?>').DataTable({
      pageLength: 50,
      order: [[1, 'desc']],
      dom: '<"top"f>rt<"bottom"lip><"clear">',
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search logs..."
      }
    });
  }

  // Month keyboard navigation
  $(document).keydown(function(e) {
    if (e.target.tagName.toLowerCase() !== 'input' &&
        e.target.tagName.toLowerCase() !== 'textarea') {

      if (e.keyCode === 37) window.location.href = '<?= $prevUrl ?>';     // left
      if (e.keyCode === 39) window.location.href = '<?= $nextUrl ?>';     // right
      if (e.keyCode === 36) window.location.href = '<?= $currentUrl ?>';  // home
    }
  });

});
</script>
<?php endif; ?>
