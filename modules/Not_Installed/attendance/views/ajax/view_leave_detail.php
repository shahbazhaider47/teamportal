<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="border rounded-3 p-3 bg-light-subtle mb-3">
  <!-- Top summary line -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <div class="text-muted text-uppercase small fw-semibold mb-1">Leave Type</div>
      <div class="fw-semibold text-primary">
        <i class="ti ti-plane-departure me-1 text-primary"></i>
        <?= ucfirst($leave['leave_type']) ?>
      </div>
    </div>

    <div class="text-end">
      <div class="text-muted text-uppercase small fw-semibold mb-1">Status</div>
      <span class="badge bg-<?= get_leave_status_badge($leave['status']) ?>">
        <?= ucfirst($leave['status']) ?>
      </span>
    </div>
  </div>

  <!-- Details grid -->
  <div class="row g-3 small">

    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <span class="text-muted">Requested By:</span>
        <span class="fw-semibold">
          <?= user_profile_image($leave['user_id']) ?>
        </span>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <span class="text-muted">Start Date:</span>
        <span class="fw-semibold"><?= format_date($leave['start_date']) ?></span>
      </div>
    </div>

    <div class="col-md-6">
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <span class="text-muted">End Date:</span>
        <span class="fw-semibold"><?= format_date($leave['end_date']) ?></span>
      </div>
    </div>

    <div class="col-md-6">
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <span class="text-muted">Date Requested:</span>
        <span class="fw-semibold"><?= format_datetime($leave['created_at']) ?></span>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
        <span class="text-muted">Leave Days:</span>
        <span class="fw-semibold"><?= (int)$leave['leave_days'] ?></span>
      </div>
    </div>
    
  </div>

  <!-- Notes -->
  <div class="mt-3">
    <div class="text-muted text-uppercase small fw-semibold mb-1">Reason / Notes:</div>
    <div class="p-2 rounded bg-white border small" style="min-height: 48px;">
      <?= nl2br(html_escape($leave['leave_notes'])) ?>
    </div>
  </div>

  <!-- Attachment -->
  <?php if (!empty($leave['leave_attachment'])): ?>
    <div class="mt-3">
      <div class="text-muted text-uppercase small fw-semibold mb-1">Attachment</div>
      <a href="<?= base_url('uploads/attendance/' . $leave['leave_attachment']) ?>"
         target="_blank"
         class="btn btn-light-primary btn-header">
        <i class="ti ti-file me-1"></i> View
      </a>
    </div>
  <?php endif; ?>
</div>


