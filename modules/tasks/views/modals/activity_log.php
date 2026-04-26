<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Tiny safe printers (won’t collide if already defined elsewhere)
if (!function_exists('t_s')) {
  function t_s($v){ return is_scalar($v) ? html_escape((string)$v) : ''; }
}
if (!function_exists('t_initials')) {
  function t_initials($name){
    $name = trim((string)$name);
    if ($name === '') return 'U';
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = mb_substr($parts[count($parts)-1] ?? '', 0, 1);
    return mb_strtoupper($first.$last);
  }
}

// Expect: $taskId (int), $activities (array)
$taskId     = (int)($taskId ?? 0);
$activities = is_array($activities ?? null) ? $activities : [];

// Normalize rows just in case
$norm = function(array $r){
  $out = [];
  $out['id']          = (int)($r['id'] ?? 0);
  $out['user_id']     = (int)($r['user_id'] ?? 0);
  $out['user_name']   = trim((string)($r['user_name'] ?? $r['author_name'] ?? ''));
  $out['user_avatar'] = trim((string)($r['user_avatar'] ?? ''));
  $out['activity']    = trim((string)($r['activity'] ?? 'updated'));
  $out['description'] = trim((string)($r['description'] ?? ''));
  $out['dateadded']   = trim((string)($r['dateadded'] ?? ''));
  return $out;
};

// Optional: map activity → label/icon
$labelFor = function(string $act){
  $a = strtolower($act);
  switch ($a) {
    case 'created':         return ['Created', 'ti ti-plus'];
    case 'updated':         return ['Updated', 'ti ti-edit'];
    case 'status_changed':  return ['Status Changed', 'ti ti-arrows-sort'];
    case 'comment_added':   return ['Commented', 'ti ti-message'];
    case 'attachment_added':return ['Attachment', 'ti ti-paperclip'];
    case 'checklist_done':  return ['Checklist', 'ti ti-checks'];
    default:                return [ucfirst($a), 'ti ti-activity'];
  }
};

// Group by date (YYYY-MM-DD)
$grouped = [];
foreach ($activities as $row) {
  $r = $norm($row);
  $key = $r['dateadded'] ? substr($r['dateadded'], 0, 10) : 'Unknown';
  $grouped[$key][] = $r;
}
krsort($grouped); // latest day first
?>

<div class="modal fade" id="activityModal" tabindex="-1" aria-labelledby="activityModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="activityModalLabel">
          <i class="ti ti-activity me-1"></i> Activity Log
          <?php if ($taskId): ?>
            <span class="text-muted fw-normal">#<?= (int)$taskId ?></span>
          <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <?php if (empty($activities)): ?>
          <div class="text-center text-muted py-5">
            <i class="ti ti-history" style="font-size:32px;"></i>
            <div class="mt-2">No activity recorded yet.</div>
          </div>
        <?php else: ?>

          <?php foreach ($grouped as $ymd => $rows): ?>
            <div class="mb-3">
              <div class="small text-uppercase text-muted mb-2">
                <i class="ti ti-calendar-event"></i>
                <?= t_s($ymd) ?>
              </div>

              <div class="list-group">
                <?php foreach ($rows as $r): ?>
                  <?php
                    [$label, $icon] = $labelFor($r['activity']);
                    $name   = $r['user_name'] !== '' ? $r['user_name'] : ($r['user_id'] ? ('User #'.$r['user_id']) : 'System');
                    $avatar = $r['user_avatar'];
                  ?>
                  <div class="list-group-item d-flex align-items-start gap-3">
                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                      <?php if ($avatar !== ''): ?>
                        <img src="<?= t_s($avatar) ?>" alt="" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                      <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;">
                          <span class="small text-muted"><?= t_s(t_initials($name)) ?></span>
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- Body -->
                    <div class="flex-grow-1">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="fw-semibold">
                          <i class="<?= t_s($icon) ?> me-1"></i><?= t_s($label) ?>
                          <span class="text-muted">by <?= t_s($name) ?></span>
                        </div>
                        <?php if ($r['dateadded']): ?>
                          <div class="small text-muted"><?= t_s($r['dateadded']) ?></div>
                        <?php endif; ?>
                      </div>

                      <?php if ($r['description'] !== ''): ?>
                        <div class="mt-1 small">
                          <?= nl2br(t_s($r['description'])) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>

        <?php endif; ?>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
          <i class="ti ti-x"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>
