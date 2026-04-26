<?php defined('BASEPATH') or exit('No direct script access allowed');

/** @var array $leaves */
/** @var int   $user_id */

$leaves = is_array($leaves ?? null) ? $leaves : [];
?>

<?php if (empty($leaves)): ?>
  <div class="p-4 text-center text-muted border rounded">
    <i class="ti ti-inbox mb-2" style="font-size: 2rem;"></i>
    <p class="mb-0">No leave requests found.</p>
  </div>
<?php else: ?>

  <div class="table-responsive">
    <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="requestsTable">
      <thead class="bg-light-primary">
        <tr>
          <th>Employee</th>
          <th>Leave Type</th>
          <th>Dates</th>
          <th>Days</th>
          <th>Status</th>
          <th>Requested At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leaves as $l): ?>
          <?php
            $name = $l['fullname']
              ?? trim(($l['firstname'] ?? '') . ' ' . ($l['lastname'] ?? ''));

            $start    = $l['start_date'] ?? '';
            $end      = $l['end_date'] ?? '';
            $days     = (int)($l['leave_days'] ?? 0);
            $status   = $l['status'] ?? 'pending';
            $type     = $l['leave_type'] ?? '';
            $created  = $l['created_at'] ?? null;

            $statusClass = 'badge bg-secondary-subtle text-muted';
            if ($status === 'pending')  $statusClass = 'badge bg-warning-subtle text-warning';
            if ($status === 'approved') $statusClass = 'badge bg-success-subtle text-success';
            if ($status === 'rejected') $statusClass = 'badge bg-danger-subtle text-danger';
          ?>
          <tr>
            <td><?= user_profile_image($name ?: 'Unknown'); ?></td>
            <td><?= html_escape(ucfirst($type)); ?></td>
            <td>
              <?php if ($start || $end): ?>
                <span class="small">
                  <?= format_date($start); ?> <i class="ti ti-chevron-right"></i> <?= format_date($end); ?>
                </span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            
            <td>
              <?php if ($days > 0): ?>
                <?= (int)$days; ?>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            
            <td><span class="<?= $statusClass; ?>"><?= html_escape(ucfirst($status)); ?></span></td>
            <td>
              <?php if (!empty($created)): ?>
                <span class="small"><?= format_datetime($created); ?></span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="btn-group btn-group-sm" role="group">
                

                      <button type="button"
                              class="btn btn-light-primary"
                              onclick="loadLeaveDetails(<?= $l['id'] ?>)"
                              data-bs-toggle="modal"
                              data-bs-target="#viewLeaveModal"
                              title="View Leave">
                        <i class="fas fa-eye"></i> View
                      </button>
                      
                <!-- you can add approve/reject buttons here tied to Attendance logic -->
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>


<?php $CI =& get_instance(); ?>
<?php echo $CI->load->view('attendance/modals/view_leave', [], true); ?>
