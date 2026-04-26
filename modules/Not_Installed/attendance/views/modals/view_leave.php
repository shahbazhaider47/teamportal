<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="viewLeaveModal" tabindex="-1" aria-labelledby="viewLeaveModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="viewLeaveModalLabel">
          <i class="ti ti-eye me-2"></i> View Leave Request
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="leave-detail-body">
        <div class="spinner-border text-primary m-auto d-block" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div class="modal-footer">
        <form id="leave-approval-form" method="post" action="<?= site_url('attendance/process_leave_approval') ?>">
          <input type="hidden" name="leave_id" id="modal_leave_id">
          <input type="hidden" name="action" id="approval_action">

        <?php if (staff_can('approve', 'attendance')): ?>
          <button type="submit" class="btn btn-success btn-header" onclick="setApprovalAction('approved')">
            <i class="ti ti-check"></i> Approve
          </button>
            <button type="submit" class="btn btn-warning btn-header" onclick="setApprovalAction('hold')">
              <i class="ti ti-clock-pause"></i> Hold
            </button>
          <button type="submit" class="btn btn-danger btn-header" onclick="setApprovalAction('rejected')">
            <i class="ti ti-x"></i> Reject
          </button>
        <?php endif; ?>

        </form>
        <button type="button" class="btn btn-light-primary btn-header" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  function setApprovalAction(action) {
    document.getElementById('approval_action').value = action;
  }

  function loadLeaveDetails(leaveId) {
    $('#leave-detail-body').html('<div class="text-center"><div class="spinner-border text-primary"></div></div>');
    $('#modal_leave_id').val(leaveId);

    $.ajax({
      url: '<?= site_url('attendance/view_leave_ajax') ?>/' + leaveId,
      type: 'GET',
      success: function (data) {
        $('#leave-detail-body').html(data);
      },
      error: function () {
        $('#leave-detail-body').html('<div class="alert alert-danger">Failed to load leave details.</div>');
      }
    });
  }
</script>
