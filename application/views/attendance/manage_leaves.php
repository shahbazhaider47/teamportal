<?php defined('BASEPATH') or exit('No direct script access allowed');
$table_id = 'myAttendanceLogsTable';
$canCreate  = staff_can('create', 'attendance');
?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title mb-2">
        <?= html_escape($page_title ?? 'My Attendance Logs') ?>
        <i class="ti ti-chevron-right"></i>
        <span class="badge bg-light-primary">Sample Badge</span>
      </h1>
    </div>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">
        
      <button
        type="button"
        class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
        <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#addLeaveModal"' : 'disabled' ?>
        title="Add New Leave for User"
      >
        <i class="ti ti-user-plus me-1"></i> Add New Leave
      </button>

      <button
        type="button"
        class="btn <?= $canCreate ? 'btn-primary' : 'btn-disabled' ?> btn-header"
        <?= $canCreate ? 'data-bs-toggle="modal" data-bs-target="#applyLeaveModal"' : 'disabled' ?>
        title="Add New Leave for User"
      >
        <i class="fas fa-plus me-1"></i> Apply for Leave
      </button>
      
      <div class="btn-divider"></div>
        <?php $CI =& get_instance(); ?>
        <?php echo $CI->load->view('attendance/partials/att_admin_menu', [], true); ?>
      <div class="btn-divider"></div>
      
      <?php render_export_buttons(['filename' => $page_title ?? 'export']); ?>
      
    </div>
  </div>

  <!-- Universal table filter (global search + per-column filters) -->
  <div class="collapse multi-collapse" id="showFilter">
    <div class="card mb-3">
      <div class="card-body">
        <?php if (function_exists('app_table_filter')): ?>
          <?php app_table_filter($table_id, [
            'exclude_columns' => [''],
          ]); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

    <div class="row g-3 mb-0">
    <?php echo $CI->load->view('attendance/partials/leaves_stats', [], true); ?>
    </div>

  <!-- Attendance Logs Table -->
  <div class="card mt-0">
    <div class="card-body">

      <?php if (empty($leaves)) : ?>
        <div class="p-4 text-center text-muted fst-italic">
          No record found
        </div>
      <?php else : ?>

        <div class="mt-1 mb-3 text-start">
          <small class="text-muted">
            Showing Total: <strong class="text-primary"><?= count($leaves) ?></strong> Leaves
          </small>
        </div>
        
        <div class="table-responsive">
          <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id) ?>">
            <thead class="bg-light-primary">
              <tr class="text-nowrap">
                <th>EMP ID</th>
                <th>EMP Name</th>
                <th>Department</th>
                <th>Leave Type</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>Total Days / Hours</th>
                <th>Leave Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            
                <?php foreach (($leaves ?? []) as $row): ?>
                  <?php
                    $empId = $row['emp_id'] ?? '—';
                
                    $empName = $row['fullname']
                        ?? trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''))
                        ?? '—';
                
                    $deptName = $row['department_name']
                        ?? $row['emp_department']
                        ?? '—';
                
                    $leaveType = $row['leave_type_name'] ?? '—';
                
                    $from = !empty($row['start_date']) ? format_date($row['start_date'], 'Y-m-d') : '—';
                    $to   = !empty($row['end_date']) ? format_date($row['end_date'], 'Y-m-d') : '—';
                
                    $total = (float)($row['total_days'] ?? 0);
                
                    $status = strtolower(trim((string)($row['status'] ?? 'pending')));
                    $badge = 'secondary';
                    if ($status === 'approved') $badge = 'success';
                    elseif ($status === 'pending') $badge = 'warning';
                    elseif ($status === 'rejected') $badge = 'danger';
                    elseif ($status === 'cancelled') $badge = 'dark';
                  ?>
                
                  <tr>
                    <td class="text-muted"><?= html_escape($empId) ?></td>
                
                    <td><?= html_escape($empName) ?></td>
                
                    <td><?= html_escape($deptName) ?></td>
                
                    <td>
                      <span class="badge bg-light-primary">
                        <?= html_escape($leaveType) ?>
                      </span>
                    </td>
                
                    <td class="text-nowrap"><?= html_escape($from) ?></td>
                    <td class="text-nowrap"><?= html_escape($to) ?></td>
                
                    <td>
                      <?= $total > 0 ? html_escape(number_format($total, 2)) : '—' ?>
                    </td>
                
                    <td>
                      <span class="badge bg-<?= $badge ?>">
                        <?= html_escape(ucfirst($status)) ?>
                      </span>
                    </td>
                
                    <td class="text-nowrap">
                      <a href="#" class="btn btn-sm btn-light-primary">
                        <i class="ti ti-eye"></i>
                      </a>
                
                      <a href="#" class="btn btn-sm btn-light-warning">
                        <i class="ti ti-edit"></i>
                      </a>
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

<?php echo $CI->load->view('attendance/modals/add_leave', [], true); ?>
<?php echo $CI->load->view('attendance/modals/apply_leave', [], true); ?>
