<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$logs            = is_array($logs            ?? null) ? $logs            : [];
$lateMinutes     = is_array($lateMinutes     ?? null) ? $lateMinutes     : [];
$earlyMinutes    = is_array($earlyMinutes    ?? null) ? $earlyMinutes    : [];
$overtimeMinutes = is_array($overtimeMinutes ?? null) ? $overtimeMinutes : [];
$overtimeMeta    = is_array($overtimeMeta    ?? null) ? $overtimeMeta    : [];
$table_id        = 'attendanceLogsTable';
$totalRows       = (int)($totalRows   ?? 0);
$perPage         = (int)($perPage     ?? 300);
$currentPage     = (int)($currentPage ?? 1);
$totalPages      = $perPage > 0 ? (int)ceil($totalRows / $perPage) : 1;
?>

<div class="container-fluid">

  <!-- ================= HEADER ================= -->

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title mb-2"><?= html_escape($page_title ?? 'Attendance Logs') ?></h1>
      <span class="badge bg-light-primary">
        <strong>Month: </strong><?= html_escape($monthLabel ?? '') ?>
      </span>

      <?php if ($totalRows > 0): ?>
        <span class="badge bg-light-secondary text-muted">
          <?= number_format($totalRows) ?> Records · Page <?= $currentPage ?> of <?= $totalPages ?>
        </span>
      <?php endif; ?>
      
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">

      <a href="<?= html_escape($prevUrl ?? '#') ?>" class="btn btn-header btn-light-primary" title="Previous Month">
        <i class="ti ti-chevron-left"></i>
      </a>

      <a href="<?= html_escape($currentUrl ?? base_url('attendance/logs')) ?>" class="btn btn-header btn-primary">
        Current
      </a>

      <a href="<?= html_escape($nextUrl ?? '#') ?>" class="btn btn-header btn-light-primary" title="Next Month">
        <i class="ti ti-chevron-right"></i>
      </a>

        <div class="btn-divider"></div>
        
        <?php $CI =& get_instance(); ?>
        <?php echo $CI->load->view('attendance/partials/att_admin_menu', [], true); ?>

      <div class="btn-divider"></div>
      
      <?php
      if (function_exists('render_export_buttons')) {
          render_export_buttons(['filename' => $page_title ?? 'attendance_logs']);
      }
      ?>
    </div>
  </div>

  <!-- Universal table filter (global search + per-column filters) -->
  <div class="collapse multi-collapse" id="showFilter">
    <div class="card">
      <div class="card-body">
        <?php if (function_exists('app_table_filter')): ?>
          <?php app_table_filter($table_id, [
              'exclude_columns' => ['#', 'Date & Time', 'Device', 'IP Address', 'Created By', 'Created At'],
          ]); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
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
                <th>Fullname</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Approval</th>
                <th>Late Minutes</th>
                <th>Early Outs</th>
                <th>Overtime</th>
                <th>Log Type</th>
                <th>Created By</th>
                <th>Created At</th>
                <th width="60" class="text-center">Edit</th>
              </tr>
            </thead>

            <tbody>
            <?php foreach ($logs as $index => $row): ?>
              <?php
                $status = $row['status'] ?? '';

                // Status badge mapping
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

                // Metrics (index based, from controller)
                $late  = (int)($lateMinutes[$index] ?? 0);
                $early = (int)($earlyMinutes[$index] ?? 0);

                $ot  = (int)($overtimeMinutes[$index] ?? 0);
                $otx = $overtimeMeta[$index] ?? ['is_exceeded' => false, 'max' => 0];

                $isExceeded = !empty($otx['is_exceeded']);
                $maxOT      = (int)($otx['max'] ?? 0);
              ?>
              <tr>
                
                <td class="text-muted">
                  <?= (($currentPage - 1) * $perPage) + $index + 1 ?>
                </td>

                <td>
                  <a href="<?= site_url(
                        'attendance/user_logs?user_id='.(int)$row['user_id']
                        .'&year='.(int)($currentYear ?? date('Y'))
                        .'&month='.(int)($currentMonth ?? date('n'))
                    ) ?>" target="_blank">
                    <?= user_profile_small((int)$row['user_id']) ?>
                  </a>
                </td>

                <td class="text-nowrap">
                    <?= !empty($row['datetime'])
                        ? html_escape(date('l', strtotime($row['datetime'])) . ' • ' . format_datetime($row['datetime'], 'Y-m-d H:i'))
                        : '—'
                    ?>
                </td>

                <td>
                  <span class="badge bg-<?= $badge ?>">
                    <?= html_escape(ucwords(str_replace('_', ' ', $status ?: 'N/A'))) ?>
                  </span>
                </td>

                <td>
                  <span class="badge bg-<?= $aBadge ?>">
                    <?= html_escape(ucfirst($approval ?: 'N/A')) ?>
                  </span>
                </td>

                <!-- Late Minutes -->
                <td class="<?= $late > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                  <?= $late > 0 ? $late : '—' ?>
                </td>

                <!-- Early Outs -->
                <td class="<?= $early > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>">
                  <?= $early > 0 ? $early : '—' ?>
                </td>

                <!-- Overtime -->
                <td class="<?= $ot > 0 ? ($isExceeded ? 'text-danger fw-semibold' : 'text-success fw-semibold') : 'text-muted' ?>">
                  <?php if ($ot <= 0): ?>
                    —
                  <?php else: ?>
                    <?php if ($isExceeded && $maxOT > 0): ?>
                      OT <?= (int)$ot ?> Exceeded
                    <?php else: ?>
                      <?= (int)$ot ?>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>

                <td class="text-nowrap text-muted">
                  <?= html_escape($row['log_type'] ?? '—') ?>
                </td>
                
                <!-- Created By -->
                <td>
                  <?php
                    $cb = (string)($row['created_by'] ?? '');
                    if ($cb !== '' && ctype_digit($cb)) {
                      echo user_profile_small((int)$cb);
                    } else {
                      echo '<span class="badge bg-light-secondary">'.html_escape($cb ?: '—').'</span>';
                    }
                  ?>
                </td>
                
                <!-- Created At -->
                <td class="text-nowrap text-muted">
                  <?= html_escape($row['created_at'] ?? '—') ?>
                </td>

                <td class="text-center">
                  <button type="button"
                          class="btn btn-light-primary btn-ssm"
                          title="Edit this log"
                          onclick="openLogEdit(<?= (int)$row['id'] ?>)">
                    <i class="ti ti-edit"></i>
                  </button>
                </td>

              </tr>
            <?php endforeach; ?>
            </tbody>

          </table>
        </div>

            <div class="mt-3 pt-2 border-top">
            
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 small text-muted mb-2">
                
                <span>
                  Showing 
                  <?= number_format(($currentPage - 1) * $perPage + 1) ?>
                  –
                  <?= number_format(min($currentPage * $perPage, $totalRows)) ?>
                  of <?= number_format($totalRows) ?> record(s)
                </span>
            
                <span>
                  Page <?= (int)$currentPage ?> of <?= (int)$totalPages ?>
                </span>
            
              </div>
            
              <div class="d-flex justify-content-end">
                <?= $pagination_links ?>
              </div>
            
            </div>


      <?php endif; ?>

    </div>
  </div>

</div>


<?php
$CI =& get_instance();
echo $CI->load->view('attendance/modals/edit_single_log', [
    'logs'         => $logs ?? [],
    'user_id'      => $user_id ?? null,
    'currentYear'  => $currentYear ?? date('Y'),
    'currentMonth' => $currentMonth ?? date('n'),
], true);
?>