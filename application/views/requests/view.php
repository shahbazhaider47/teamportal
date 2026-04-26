<?php defined('BASEPATH') or exit('No direct script access allowed');

$request = $request ?? [];
$payload = $request['payload'] ?? [];
$attachments = $request['attachments'] ?? [];
?>

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h6">Request <?= html_escape($request['request_no']); ?></h1>

    <a href="<?= site_url('requests'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="ti ti-arrow-left"></i> Back
    </a>
  </div>

  <div class="card mb-3">
    <div class="card-body">

      <h6 class="mb-3">Request Details</h6>

      <table class="table table-sm">
        <tr><th>Type</th><td><?= html_escape($request['type']); ?></td></tr>
        <tr><th>Status</th><td><?= ucfirst($request['status']); ?></td></tr>
        <tr><th>Priority</th><td><?= ucfirst($request['priority']); ?></td></tr>
        <tr><th>Department</th><td><?= get_department_name($request['department_id']); ?></td></tr>
        <tr><th>Submitted At</th><td><?= format_datetime($request['submitted_at']); ?></td></tr>
      </table>

    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">

      <h6 class="mb-3">Payload Data</h6>

      <?php if (!empty($payload)): ?>
        <table class="table table-sm">
          <?php foreach ($payload as $key => $value): ?>
            <tr>
              <th><?= ucwords(str_replace('_',' ',$key)); ?></th>
              <td><?= html_escape(is_array($value) ? json_encode($value) : $value); ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p class="text-muted">No payload data.</p>
      <?php endif; ?>

    </div>
  </div>

  <div class="card">
    <div class="card-body">

      <h6 class="mb-3">Attachments</h6>

      <?php if (!empty($attachments)): ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($attachments as $file): ?>
            <li class="list-group-item">
              <a href="<?= base_url($file['path'] . $file['stored']); ?>" target="_blank">
                <?= html_escape($file['original']); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted">No attachments.</p>
      <?php endif; ?>

    </div>
  </div>

</div>
