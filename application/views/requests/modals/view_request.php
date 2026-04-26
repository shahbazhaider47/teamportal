<?php defined('BASEPATH') or exit('No direct script access allowed');

$request     = $request ?? [];
$payload     = $request['payload'] ?? [];
$attachments = $request['attachments'] ?? [];
?>

<!-- HEADER -->
<div class="modal-header bg-primary">
  <h5 class="modal-title text-white">
    Request <?= html_escape($request['request_no']); ?>
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<!-- BODY -->
<div class="modal-body">

<!-- Meta Summary -->
<div class="row g-3 mb-4">

  <!-- LEFT COLUMN -->
  <div class="col-md-4">
    <div class="p-3 border rounded-3 h-100">

      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted small fw-semibold">Status</span>
        <span class="badge bg-light-primary text-uppercase">
          <?= html_escape($request['status']); ?>
        </span>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted small fw-semibold">Priority</span>
        <span class="badge bg-<?= priority_class($request['priority']); ?> capital">
          <?= html_escape($request['priority']); ?>
        </span>
      </div>

      <div class="d-flex align-items-center justify-content-between">
        <span class="text-muted small fw-semibold">Department</span>
        <span class="text-muted small">
          <?= html_escape(get_department_name($request['department_id'])); ?>
        </span>
      </div>

    </div>
  </div>

  <!-- RIGHT COLUMN -->
  <div class="col-md-4">
    <div class="p-3 border rounded-3 h-100">

      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted small fw-semibold">Submitted At</span>
        <span class="text-muted small">
          <?= format_datetime($request['submitted_at']); ?>
        </span>
      </div>

      <div class="d-flex align-items-center justify-content-between">
        <span class="text-muted small fw-semibold">Requested By</span>
        <span>
          <?= user_profile_image($request['requested_by']); ?>
        </span>
      </div>

    </div>
  </div>


  <!-- Attachments -->
  <div class="col-md-4">
    <div class="p-3 border rounded-3 h-100">

      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted small fw-semibold">Attachments:</span>
        <span class="text-muted small">
        <?php if (!empty($attachments)): ?>
            <?php foreach ($attachments as $file): ?>
                <a href="<?= base_url($file['path'] . $file['stored']); ?>" target="_blank">
                <?= html_escape($file['original']); ?>
                </a>
            <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted">No attachments.</p>
        <?php endif; ?>
        </span>
        
      
      </div>

    </div>
  </div>
  
</div>


  <div class="app-divider-v dotted"></div>

  <!-- Payload -->
  <h6 class="mb-2">Request Details</h6>

  <?php if (!empty($payload)): ?>
    <table class="table table-sm table-bordered">
      <?php foreach ($payload as $key => $value): ?>
        <tr>
          <th style="width:35%">
            <?= ucwords(str_replace('_',' ', $key)); ?>
          </th>
          <td>
            <?= html_escape(is_array($value) ? json_encode($value) : $value); ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="text-muted">No request data.</p>
  <?php endif; ?>

</div>

<!-- FOOTER -->
<div class="modal-footer justify-content-between">

  <!-- Approval buttons (future use) -->
  <div>
    <button class="btn btn-success btn-sm">
      <i class="ti ti-check"></i> Approve
    </button>
    <button class="btn btn-danger btn-sm">
      <i class="ti ti-x"></i> Reject
    </button>
  </div>

  <button type="button"
          class="btn btn-secondary btn-sm"
          data-bs-dismiss="modal">
    Close
  </button>

</div>



