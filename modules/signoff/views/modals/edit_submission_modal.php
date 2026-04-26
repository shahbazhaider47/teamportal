<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// Expected from parent:
// $row              : submission row (array)
// $form_fields_json : JSON string or array describing the form fields
// (same as your view modal)

$row = is_array($row) ? $row : [];

// Modal id per submission
$modal_id = 'signoffSubmissionEditModal' . (int)($row['id'] ?? 0);

// Decode submission field values
$fields_data = [];
if (!empty($row['fields_data'])) {
    $tmp = json_decode((string)$row['fields_data'], true);
    if (is_array($tmp)) { $fields_data = $tmp; }
}

// Decode form fields schema (labels, types, etc.)
$form_fields = [];
if (isset($form_fields_json)) {
    if (is_array($form_fields_json)) {
        $form_fields = $form_fields_json;
    } else {
        $tmp = json_decode((string)$form_fields_json, true);
        if (is_array($tmp)) { $form_fields = $tmp; }
    }
}

$hasAnyRows      = !empty($form_fields) || !empty($row['signoff_attachment']);
$status          = strtolower((string)($row['status'] ?? ''));
$submission_date = !empty($row['submission_date'])
    ? date('l, d M Y', strtotime($row['submission_date']))
    : '—';

// Age guard: older than 30 days? hide actions (same logic as view modal)
$__dateStr  = $row['submission_date'] ?? ($row['created_at'] ?? '');
$__ts       = $__dateStr ? strtotime($__dateStr) : false;
$__cutoffTs = strtotime('-30 days');
$__isOld    = ($__ts && $__ts < $__cutoffTs);

// ------------------------------------------------------------------
// Basic identity: user id + FIRSTNAME + LASTNAME only
// ------------------------------------------------------------------
$userId = (int)($row['user_id'] ?? 0);

$first = trim($row['firstname'] ?? '');
$last  = trim($row['lastname'] ?? '');
$fullName = trim($first . ' ' . $last);

if ($fullName === '' && $userId > 0) {
    $fullName = 'User #' . $userId;
}

?>

<div class="modal fade" id="<?= $modal_id ?>" tabindex="-1"
     aria-labelledby="<?= $modal_id ?>Label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content border-0 shadow"
          method="post"
          action="<?= base_url('signoff/update_submission/' . (int)$row['id']) ?>"
          enctype="multipart/form-data">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="<?= $modal_id ?>Label">
          Edit Signoff Submission
        </h5>
        <button type="button" class="btn-close btn-close-white"
                data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <?php if ($__isOld): ?>
          <div class="alert alert-info small">
            This submission is older than 30 days. Editing may be restricted by policy.
          </div>
        <?php endif; ?>

        <?php if ($hasAnyRows): ?>
          <table class="table small table-bordered mb-0 align-middle">
            <tbody>
              <?php if (!empty($form_fields)): ?>
                <?php foreach ($form_fields as $f):
                  $key   = $f['name']  ?? '';
                  if (!$key) { continue; }

                  $label = $f['label'] ?? $key;
                  $type  = strtolower($f['type'] ?? '');
                  $val   = array_key_exists($key, $fields_data) ? $fields_data[$key] : '';

                  // Flatten arrays to simple value/string for display/edit
                  if (is_array($val)) {
                      $val = implode(', ', array_filter($val, static function($v) {
                          return $v !== '' && $v !== null;
                      }));
                  }
                ?>
                  <tr>
                    <th style="width:40%"><?= html_escape($label) ?></th>
                    <td>
                      <?php if ($type === 'file'): ?>
                        <?php if (!empty($row['signoff_attachment'])): ?>
                          <div class="mb-1">
                            <a href="<?= base_url(html_escape($row['signoff_attachment'])) ?>"
                               target="_blank">
                              Current: View / Download
                            </a>
                          </div>
                        <?php endif; ?>
                        <input type="file"
                               name="signoff_attachment"
                               class="form-control form-control-sm">
                        <small class="text-muted">
                          Leave blank to keep existing attachment.
                        </small>

                      <?php elseif (in_array($type, ['textarea', 'multiline'], true)): ?>
                        <textarea name="fields[<?= html_escape($key) ?>]"
                                  class="form-control form-control-sm"
                                  rows="2"><?= html_escape((string)$val) ?></textarea>

                      <?php elseif (in_array($type, ['amount', 'number'], true)): ?>
                        <input type="number"
                               step="0.01"
                               class="form-control form-control-sm"
                               name="fields[<?= html_escape($key) ?>]"
                               value="<?= html_escape((string)$val) ?>">

                      <?php else: ?>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="fields[<?= html_escape($key) ?>]"
                               value="<?= html_escape((string)$val) ?>">
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>

              <?php if (empty($form_fields) && !empty($row['signoff_attachment'])): ?>
                <tr>
                  <th style="width:40%">Attachment</th>
                  <td>
                    <a href="<?= base_url(html_escape($row['signoff_attachment'])) ?>"
                       target="_blank">View / Download</a>
                    <div class="mt-2">
                      <input type="file"
                             name="signoff_attachment"
                             class="form-control form-control-sm">
                      <small class="text-muted">
                        Upload a new file to replace the current attachment.
                      </small>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="alert alert-warning mb-0">
            No form data available for this submission.
          </div>
        <?php endif; ?>

        <div class="mt-3 small text-muted">
          <?= user_profile_image($userId) ?> |
          <b>Status:</b> <?= ucfirst($status ?: '—') ?> |
          <b>Date:</b> <?= $submission_date ?>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button"
                class="btn btn-sm btn-light-primary"
                data-bs-dismiss="modal">
          Cancel
        </button>
        <?php if (! $__isOld): ?>
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Save Changes
          </button>
        <?php else: ?>
          <button type="submit" class="btn btn-sm btn-primary" disabled>
            Editing Locked (30+ days)
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
