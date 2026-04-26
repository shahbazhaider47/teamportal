<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// Expected from parent:
// $row              : submission row (array)
// $form_fields_json : JSON string or array describing the form fields
// $can_review       : bool - if viewer may approve/reject
// Optional (recommended):
// $perf_indicators  : 'points' | 'targets' | 'none' (if absent, we try get_option('signoff_perf_indicators'))
// $points_weights   : ['field_key' => weightNumber, ...]
// $targets_fields   : ['field_key' => targetNumber, ...]  // numbers optional; keys used to mark targeted fields

$row        = is_array($row) ? $row : [];
$can_review = !empty($can_review);

// Detect mode (only one)
// If someone configured "both", we prefer "points" to satisfy "only one".
$mode = 'none';
if (isset($perf_indicators) && is_string($perf_indicators)) {
  $mode = strtolower(trim($perf_indicators));
} elseif (function_exists('get_option')) {
  $mode = strtolower(trim((string) get_option('signoff_perf_indicators')));
}
if ($mode === 'both') { $mode = 'points'; } // enforce "only one"

// Normalize config arrays
$points_weights = isset($points_weights) && is_array($points_weights) ? $points_weights : [];
$targets_fields = isset($targets_fields) && is_array($targets_fields) ? $targets_fields : [];

// Modal id per submission
$modal_id = 'signoffSubmissionModal' . (int)($row['id'] ?? 0);

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

// Flags
$hasAnyRows            = !empty($form_fields) || !empty($row['signoff_attachment']);
$attachmentRowRendered = false;
$status                = strtolower((string)($row['status'] ?? ''));
$submission_date       = !empty($row['submission_date']) ? date('l, d M Y', strtotime($row['submission_date'])) : '—';

// helpers for metrics
$toNumber = static function($v): float {
  if (is_array($v)) {
    $sum = 0.0;
    foreach ($v as $x) { if (is_numeric($x)) $sum += (float)$x; }
    return $sum;
  }
  return is_numeric($v) ? (float)$v : 0.0;
};

$calc_points_total   = 0.0;
$calc_achieved_total = 0.0;

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

<div class="modal fade" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_id ?>Label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="<?= $modal_id ?>Label">Signoff Submission Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">

        <?php if ($hasAnyRows): ?>
          <table class="table small table-bordered mb-0">
            <tbody>
              <?php if (!empty($form_fields)): ?>
                <?php foreach ($form_fields as $f):
                  $key   = $f['name']  ?? '';
                  $label = $f['label'] ?? $key;
                  $type  = strtolower($f['type'] ?? '');
                  $val   = array_key_exists($key, $fields_data) ? $fields_data[$key] : null;

                  // Per-field metric chips
                  $metricHtml = ''; // right-aligned indicator per row
                  if ($mode === 'points' && isset($points_weights[$key])) {
                    $w   = is_numeric($points_weights[$key]) ? (float)$points_weights[$key] : 1.0;
                    $num = $toNumber($val);
                    $pts = $num * $w;
                    $calc_points_total += $pts;
                    $metricHtml = '<span class="badge bg-light-primary ms-2" title="Value × Weight = Points">' .
                                  htmlspecialchars($num, ENT_QUOTES, 'UTF-8') . ' × ' . htmlspecialchars($w, ENT_QUOTES, 'UTF-8') .
                                  ' = <b>' . htmlspecialchars(number_format($pts, 2), ENT_QUOTES, 'UTF-8') . '</b></span>';
                  } elseif ($mode === 'targets' && array_key_exists($key, $targets_fields)) {
                    $num = $toNumber($val);
                    $calc_achieved_total += $num;
                    $metricHtml = '<span class="badge bg-light ms-2" title="Counts toward target">' .
                                  'Achieved: <b>' . htmlspecialchars(number_format($num, 2), ENT_QUOTES, 'UTF-8') .
                                  '</b></span>';
                  }
                ?>
                  <tr>
                    <th style="width:40%"><?= html_escape($label) ?></th>
                    <td class="d-flex justify-content-between align-items-start">
                      <div>
                        <?php if ($type === 'file'): ?>
                          <?php $attachmentRowRendered = true; ?>
                          <?php if (!empty($row['signoff_attachment'])): ?>
                            <a href="<?= base_url(html_escape($row['signoff_attachment'])) ?>" target="_blank">View / Download</a>
                          <?php else: ?>
                            <span class="text-muted">—</span>
                          <?php endif; ?>
                        <?php elseif ($type === 'amount'): ?>
                          <span class="text-muted">$</span><?= nl2br(html_escape((string)$val)) ?>
                        <?php else: ?>
                          <?= ($val !== null && $val !== '') ? nl2br(html_escape((string)$val)) : '<span class="text-muted">—</span>' ?>
                        <?php endif; ?>
                      </div>
                      <div class="text-nowrap"><?= $metricHtml ?></div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>

              <?php if (!$attachmentRowRendered && !empty($row['signoff_attachment'])): ?>
                <tr>
                  <th style="width:40%">Attachment</th>
                  <td>
                    <a href="<?= base_url(html_escape($row['signoff_attachment'])) ?>" target="_blank">View / Download</a>
                  </td>
                </tr>
              <?php endif; ?>

              <?php
                // Summary row (only one mode)
                if ($mode === 'points') {
                  // Prefer stored total_points; fallback to calculated
                  $total = isset($row['total_points']) && $row['total_points'] !== '' ? (float)$row['total_points'] : $calc_points_total;
              ?>
                <tr class="table-light">
                  <th>Total Points</th>
                  <td class="fw-semibold"><?= htmlspecialchars(number_format((float)$total, 2), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php
                } elseif ($mode === 'targets') {
                  $total = isset($row['achieved_targets']) && $row['achieved_targets'] !== '' ? (float)$row['achieved_targets'] : $calc_achieved_total;
              ?>
                <tr class="table-light">
                  <th>Achieved (Targets)</th>
                  <td class="fw-semibold"><?= htmlspecialchars(number_format((float)$total, 2), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="alert alert-warning mb-0">No form data available for this submission.</div>
        <?php endif; ?>

        <div class="mt-3 small text-muted">
          <?= user_profile_image($userId) ?> |
          <b>Status:</b> <?= ucfirst($status ?: '—') ?> |
          <b>Date:</b> <?= $submission_date ?>
        </div>

        <?php
          // Age guard: older than 30 days? hide actions
          $__dateStr  = $row['submission_date'] ?? ($row['created_at'] ?? '');
          $__ts       = $__dateStr ? strtotime($__dateStr) : false;
          $__cutoffTs = strtotime('-30 days');
          $__isOld    = ($__ts && $__ts < $__cutoffTs);
        ?>
        
        <?php if (in_array($status, ['submitted','rejected','approved'], true) && $can_review && ! $__isOld): ?>
          <div class="mt-4 d-flex justify-content-end gap-2">
            <?php if ($status === 'submitted' || $status === 'rejected'): ?>
              <a href="<?= base_url('signoff/review_submission/'.(int)$row['id'].'/approved') ?>"
                 class="btn btn-sm btn-success"
                 onclick="return confirm('Approve this submission?');">
                <i class="ti ti-check me-1"></i> Approve
              </a>
            <?php endif; ?>
            <?php if ($status === 'submitted' || $status === 'approved'): ?>
              <a href="<?= base_url('signoff/review_submission/'.(int)$row['id'].'/rejected') ?>"
                 class="btn btn-sm btn-danger"
                 onclick="return confirm('Reject this submission?');">
                <i class="ti ti-x me-1"></i> Reject
              </a>
            <?php endif; ?>
          </div>
        <?php elseif ($__isOld && $can_review): ?>
          <div class="mt-3 text-end">
            <span class="badge bg-light-primary" title="Actions disabled for items older than 30 days.">
              Review locked (30+ days)
            </span>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
