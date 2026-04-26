<?php defined('BASEPATH') or exit('No direct script access allowed'); 

$table_id  = $table_id ?? 'attendanceLogsTable';

?>

<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title mb-2"><?= html_escape($page_title ?? 'Attendance Logs') ?></h1>
      <?php if (!empty($user_id)): ?>
    <span class="badge bg-white text-muted">
        <?= user_profile_image((int)$user_id) ?>
    </span>
    <?php endif; ?>

    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="get"
                  action="<?= site_url('attendance/user_logs') ?>"
                  class="d-inline-block app-form">
                <div class="input-group"
                     style="width:350px;">
                    <select name="user_id"
                            class="form-select js-searchable-select"
                            required>
                        <option value="">Select Employee…</option>
                        <?php foreach (($staff_list ?? []) as $staff): ?>
                            <?php
                                $id = (int)$staff['id'];
                                if (!$id) continue;
            
                                $name = $staff['full_name']
                                    ?? trim(($staff['firstname'] ?? '').' '.($staff['lastname'] ?? ''))
                                    ?? 'User #'.$id;
                            ?>
                            <option value="<?= $id ?>" <?= ($user_id == $id ? 'selected' : '') ?>>
                                <?= html_escape($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"
                            class="btn btn-primary btn-header" style="font-size:12px;">
                            <i class="ti ti-fingerprint me-2"></i>
                        Load Logs
                    </button>
                </div>
            </form>

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
        <?php echo $CI->load->view('attendance/partials/att_admin_menu', [], true); ?>

      <div class="btn-divider"></div>

      <?php render_export_buttons(['filename' => $page_title ?? 'attendance_logs']); ?>

        <?php if (!empty($user_id)): ?>
        <div class="btn-divider"></div>
            <a class="btn btn-primary icon-btn" href="#" data-bs-toggle="modal" data-bs-target="#EditattendanceModal">
            <i class="ti ti-edit"></i></i>
            </a>
        <?php endif; ?>
      
    </div>
  </div>

  <!-- Universal table filter (global search + per-column filters) -->
    <div class="collapse multi-collapse" id="showFilter">
        <div class="card mb-3">
          <div class="card-body">
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                    'exclude_columns' => ['#',  'Employee', 'Device', 'IP Address'],
                ]);
                ?>
            <?php endif; ?>
          </div>
        </div>
    </div>

<div class="row g-3 mb-0">
    
<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Total Check-ins</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-primary f-w-600"><?= (int)($totalCheckIns ?? 0) ?></h4>
          <p class="m-0 text-muted small"><?= html_escape($monthLabel ?? '') ?></p>
        </div>
      </div>
      <div class="project-card-icon bg-light-primary h-25 w-25 d-flex-center b-r-100">
        <i class="ti ti-login f-s-14 mb-1 text-primary"></i>
      </div>
    </div>
  </div>
</div>

<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Total Check-outs</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-primary f-w-600"><?= (int)($totalCheckOuts ?? 0) ?></h4>
          <p class="m-0 text-muted small"><?= html_escape($monthLabel ?? '') ?></p>
        </div>
      </div>
      <div class="project-card-icon bg-light-primary h-25 w-25 d-flex-center b-r-100">
        <i class="ti ti-logout f-s-14 mb-1 text-primary"></i>
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
        <div class="project-card-icon bg-light-danger h-25 w-25 d-flex-center b-r-100">
          <i class="ti ti-clock f-s-14 mb-1 text-danger"></i>
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
          <div class="project-card-icon bg-light-warning h-25 w-25 d-flex-center b-r-100">
            <i class="ti ti-alert-circle f-s-14 mb-1 text-warning"></i>
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
      <div class="project-card-icon bg-light-success h-25 w-25 d-flex-center b-r-100">
        <i class="ti ti-calendar-stats f-s-14 mb-1 text-success"></i>
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
      <div class="project-card-icon bg-light-success h-25 w-25 d-flex-center b-r-100">
        <i class="ti ti-trending-up f-s-14 mb-1 text-success"></i>
      </div>
    </div>
  </div>
</div>

</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($logs)) : ?>
            <div class="p-4 text-center text-muted fst-italic">
                Select user to load the attendance log
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
                            <th>Employee</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Log Type</th>
                            <th>Device</th>
                            <th>IP Address</th>
                            <th>Approval</th>
                            <th>Late Minutes</th>
                            <th>Early Check Out</th>
                            <th>Overtime Mintes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $index => $row): ?>
                            <tr>
                                <td class="text-muted"><?= (int)$index + 1 ?></td>
                                <td><?= user_profile_small((int)$row['user_id']) ?></td>
                                <td class="text-nowrap">
                                    <?= !empty($row['datetime'])
                                        ? html_escape(date('l', strtotime($row['datetime'])) . ' • ' . format_datetime($row['datetime'], 'Y-m-d H:i'))
                                        : '—'
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        $status = $row['status'] ?? '';
                                        $badge  = 'secondary';
                                        if (in_array($status, ['check_in','overtime_in'], true))  $badge = 'success';
                                        if (in_array($status, ['check_out','overtime_out'], true)) $badge = 'info';
                                        if ($status === 'other') $badge = 'warning';
                                    ?>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= html_escape(str_replace('_',' ', ucfirst($status))) ?>
                                    </span>
                                </td>
                                <td><?= html_escape($row['log_type'] ?? '—') ?></td>
                                <td><?= html_escape($row['device_id'] ?? '—') ?></td>
                                <td class="text-nowrap"><?= html_escape($row['ip_address'] ?? '—') ?></td>
                                <td>
                                    <?php
                                        $approval = strtolower($row['approval_status'] ?? '');
                                        $aBadge   = 'secondary';
                                        if ($approval === 'approved') $aBadge = 'success';
                                        elseif ($approval === 'rejected') $aBadge = 'danger';
                                        elseif ($approval === 'pending')  $aBadge = 'warning';
                                    ?>
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

<?php
echo $CI->load->view('attendance/modals/edit_attendance_logs_modal', [
    'logs'         => $logs ?? [],
    'user_id'      => $user_id ?? null,
    'currentYear'  => $currentYear ?? date('Y'),
    'currentMonth' => $currentMonth ?? date('n'),
], true);
?>
