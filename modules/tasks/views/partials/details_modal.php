<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Task Details Modal – Pro (ClickUp/Monday-like)
 * Expects:
 *   $task       (array)
 *   $activities (array, optional)
 *   $share_url  (string, optional) – canonical URL to this task detail page
 */

if (!function_exists('t_s')) {
  function t_s($v){ return is_scalar($v) ? html_escape((string)$v) : ''; }
}
if (!function_exists('t_val')) {
  function t_val($v,$fallback='—'){
    if ($v === null) return $fallback;
    $s = is_scalar($v) ? (string)$v : (is_array($v) ? json_encode($v) : (string)$v);
    $s = trim($s);
    return $s === '' ? $fallback : html_escape($s);
  }
}
if (!function_exists('t_boolbadge')) {
  function t_boolbadge($v){
    $isTrue = (string)$v === '1' || $v === 1 || $v === true || $v === 'yes' || $v === 'true';
    return $isTrue ? '<span class="badge rounded-pill bg-success-subtle text-success border-0">Yes</span>'
                   : '<span class="badge rounded-pill bg-secondary-subtle text-secondary border-0">No</span>';
  }
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
if (!function_exists('t_avatar_url')) {
  function t_avatar_url($img){
    $img = trim((string)$img);
    if ($img === '') return '';
    if (strpos($img,'http://') === 0 || strpos($img,'https://') === 0) return $img;
    if (strpos($img,'/') !== false) return $img;
    return base_url('uploads/users/profile/'.$img);
  }
}

/* ---------- Normalize task ---------- */
$task         = is_array($task ?? null) ? $task : [];
$taskId       = (int)($task['id'] ?? 0);
$modalId      = 'taskDetailsModalRight_' . $taskId;
$title        = t_s($task['name'] ?? 'Untitled Task');
$description  = (string)($task['description'] ?? '');
$shareUrl     = isset($share_url) && is_string($share_url) && $share_url !== '' ? $share_url : site_url('tasks/view/'.$taskId);

/* --- Owner (creator) --- */
$ownerId   = (int)($task['addedfrom'] ?? 0);
$ownerName = trim((string)($task['addedfrom_name'] ?? ''));
if ($ownerName === '' && ($task['addedfrom_firstname'] ?? '') !== '' ) {
  $ownerName = trim(($task['addedfrom_firstname'] ?? '').' '.($task['addedfrom_lastname'] ?? ''));
}
if ($ownerName === '' && $ownerId) $ownerName = 'User #'.$ownerId;
$ownerImg  = t_avatar_url($task['addedfrom_avatar'] ?? ($task['addedfrom_profile_image'] ?? ''));
$ownerIni  = t_initials($ownerName);

/* --- Assignee --- */
$assigneeId   = (int)($task['assignee_id'] ?? 0);
$assigneeName = trim((string)($task['assignee_name'] ?? ''));
if ($assigneeName === '' && ($task['assignee_firstname'] ?? '') !== '' ) {
  $assigneeName = trim(($task['assignee_firstname'] ?? '').' '.($task['assignee_lastname'] ?? ''));
}
if ($assigneeName === '' && $assigneeId) $assigneeName = 'User #'.$assigneeId;
$assigneeImg  = t_avatar_url($task['assignee_avatar'] ?? ($task['assignee_profile_image'] ?? ''));
$assigneeIni  = t_initials($assigneeName);

/* --- Status & Priority --- */
$status       = t_val($task['status'] ?? '');
$priority     = (int)($task['priority'] ?? 0);
$priorityMap  = [
  1 => ['label' => 'Low',     'class' => 'bg-info-subtle text-info'],
  2 => ['label' => 'Medium',  'class' => 'bg-warning-subtle text-warning'],
  3 => ['label' => 'High',    'class' => 'bg-danger-subtle text-danger'],
  4 => ['label' => 'Urgent',  'class' => 'bg-danger text-white']
];
$priorityInfo = $priorityMap[$priority] ?? ['label' => 'Normal', 'class' => 'bg-secondary-subtle text-secondary'];

/* --- Dates --- */
$created   = format_datetime($task['dateadded']       ?? '');
$startdate = format_datetime($task['startdate']       ?? '');
$duedate   = format_datetime($task['duedate']         ?? '');
$finished  = format_datetime($task['datefinished']    ?? '');

/* --- Recurrence --- */
$isRecurring = (int)($task['recurring'] ?? 0) === 1;

/* --- Activities --- */
$activities  = is_array($activities ?? null) ? $activities : [];
$normAct = function(array $r){
  return [
    'id'          => (int)($r['id'] ?? 0),
    'user_id'     => (int)($r['user_id'] ?? 0),
    'user_name'   => trim((string)($r['user_name'] ?? $r['author_name'] ?? '')),
    'user_avatar' => trim((string)($r['user_avatar'] ?? '')),
    'activity'    => trim((string)($r['activity'] ?? 'updated')),
    'description' => trim((string)($r['description'] ?? '')),
    'dateadded'   => trim((string)($r['dateadded'] ?? '')),
  ];
};
$labelFor = function(string $act){
  $a = strtolower($act);
  switch ($a) {
    case 'created':          return ['Created', 'ti ti-plus'];
    case 'updated':          return ['Updated', 'ti ti-edit'];
    case 'status_changed':   return ['Status Changed', 'ti ti-arrows-sort'];
    case 'comment_added':    return ['Commented', 'ti ti-message'];
    case 'attachment_added': return ['Attachment', 'ti ti-paperclip'];
    case 'checklist_done':   return ['Checklist', 'ti ti-checks'];
    default:                 return [ucfirst($a), 'ti ti-activity'];
  }
};
$grouped = [];
foreach ($activities as $row) {
  $r = $normAct($row);
  $key = $r['dateadded'] ? substr($r['dateadded'], 0, 10) : 'Unknown';
  $grouped[$key][] = $r;
}
krsort($grouped);
?>

<?php
if (!function_exists('t_activity_render')) {
  function t_activity_render(string $act, string $desc_json): string {
    $data = json_decode($desc_json, true);
    if (!is_array($data)) {
      // Legacy/non-JSON rows: show as-is but escaped
      return '<div class="text-muted">'.nl2br(html_escape($desc_json)).'</div>';
    }
    $p = $data['payload'] ?? [];
    $safe = fn($x) => html_escape((string)$x);

    switch (strtolower($act)) {
      case 'task_created':
      case 'created':
        return '<div>Task created'
             . (isset($p['title']) ? ': <strong>'.$safe($p['title']).'</strong>' : '')
             . (isset($p['status']) ? ' · Status: <span class="badge bg-primary-subtle text-primary">'.$safe($p['status']).'</span>' : '')
             . (isset($p['priority']) ? ' · Priority: '.$safe($p['priority']) : '')
             . '</div>';

      case 'assignee_set':
      case 'assignee_changed':
        $from = $p['from'] ?? null; $to = $p['to'] ?? null;
        $from = $from === null ? 'Unassigned' : ('#'.(int)$from);
        $to   = $to   === null ? 'Unassigned' : ('#'.(int)$to);
        return '<div>Assignee changed: <strong>'.$safe($from).'</strong> → <strong>'.$safe($to).'</strong></div>';

      case 'status_changed':
        $from = $p['from'] ?? ''; $to = $p['to'] ?? '';
        $line = 'Status: <span class="badge bg-secondary-subtle text-secondary">'.$safe($from).'</span> → '
              . '<span class="badge bg-primary-subtle text-primary">'.$safe($to).'</span>';
        if (isset($p['checklist_summary']['done'], $p['checklist_summary']['total'])) {
          $cs = $p['checklist_summary'];
          $line .= ' · Checklist '.$safe($cs['done']).'/'.$safe($cs['total']);
          if (isset($cs['percent'])) $line .= ' ('.$safe($cs['percent']).'%)';
        }
        return '<div>'.$line.'</div>';

      case 'comment_added':
        $ex = $p['excerpt'] ?? '';
        $cid = isset($p['comment_id']) ? ' (comment #'.(int)$p['comment_id'].')' : '';
        return '<div>Comment'.$cid.': “'.$safe($ex).'”</div>';

      case 'comment_reply_added':
        $ex = $p['excerpt'] ?? '';
        $cid = isset($p['comment_id']) ? 'comment #'.(int)$p['comment_id'] : null;
        $rid = isset($p['reply_id'])   ? 'reply #'.(int)$p['reply_id']   : null;
        $meta = array_filter([$cid, $rid]);
        $metaStr = $meta ? ' ('.implode(', ', array_map($safe, $meta)).')' : '';
        return '<div>Reply'.$metaStr.': “'.$safe($ex).'”</div>';

      case 'attachment_uploaded':
      case 'attachment_added':
        $fn = $p['file_name'] ?? '';
        $fp = $p['file_path'] ?? '';
        if ($fp !== '') {
          $href = base_url(ltrim($fp,'/'));
          return '<div>Attachment: <a href="'.$safe($href).'" target="_blank" rel="noopener">'.$safe($fn ?: $fp).'</a></div>';
        }
        return '<div>Attachment: '.$safe($fn ?: 'file').'</div>';

      case 'attachment_deleted':
        return '<div>Attachment removed: '.$safe($p['file_name'] ?? ($p['file_path'] ?? 'file')).'</div>';

      case 'checklist_item_added':
        $who = isset($p['assigned']) ? ' (assigned #'.(int)$p['assigned'].')' : '';
        return '<div>Checklist item added'.$who.': “'.$safe($p['description'] ?? '').'”</div>';

      case 'checklist_item_toggled':
        $state = ((int)($p['finished'] ?? 0)) === 1 ? 'completed' : 'reopened';
        return '<div>Checklist item '.$safe($state).' (ID '.$safe($p['item_id'] ?? '').')</div>';

      case 'checklist_item_deleted':
        return '<div>Checklist item deleted: “'.$safe($p['desc'] ?? '').'”</div>';

      case 'followers_set':
        $count = is_array($p['followers'] ?? null) ? count($p['followers']) : 0;
        return '<div>Followers updated · '.$safe($count).' user(s)</div>';

      case 'follower_added':
        return '<div>Follower added: #'.$safe($p['user_id'] ?? '').'</div>';

      case 'follower_removed':
        return '<div>Follower removed: #'.$safe($p['user_id'] ?? '').'</div>';

      case 'kanban_move':
        $to = $p['to_status'] ?? '';
        $order = $p['order'] ?? null;
        return '<div>Moved to <strong>'.$safe($to).'</strong>'.($order ? ' · Order '.$safe($order) : '').'</div>';

      case 'task_deleted':
        return '<div>Task deleted</div>';

      default:
        // Fallback: pretty-print payload keys if present
        $payload = isset($data['payload']) ? $data['payload'] : [];
        if ($payload) {
          $pairs = [];
          foreach ($payload as $k=>$v) {
            if (is_array($v)) continue;
            $pairs[] = '<span class="text-muted">'.$safe($k).'</span>: '.$safe($v);
          }
          return '<div>'.implode(' · ', $pairs).'</div>';
        }
        return '<div class="text-muted">'.nl2br(html_escape($desc_json)).'</div>';
    }
  }
}

?>

<style>
  /* --- Modal shell --- */
  #<?= $modalId ?> .modal-dialog {
    position: fixed; right: 1rem; top: 1rem; margin: 0;
    width: 760px; max-width: 96vw;
    max-height: calc(100vh - 2rem);
    border-radius: 12px; overflow: hidden;
    box-shadow: 0 18px 56px rgba(16,24,40,.18);
  }
  #<?= $modalId ?> .modal-content { border-radius: 12px; border: 1px solid #e7ebf0; overflow: hidden; }

  /* --- Header: sticky + actions --- */
  #<?= $modalId ?> .modal-header {
    position: sticky; top: 0; z-index: 3;
    background: #fff; border-bottom: 1px solid #eef1f5;
    padding: 1rem 1.25rem;
  }
  #<?= $modalId ?> .title-wrap { display:flex; gap: .75rem; align-items: flex-start; }
  #<?= $modalId ?> .task-title { font-size: 1.25rem; font-weight: 700; color: #101828; margin: 0; line-height: 1.25; }
  #<?= $modalId ?> .task-id    { color: #667085; font-size: .85rem; }
  #<?= $modalId ?> .header-actions { margin-left:auto; display:flex; gap:.5rem; align-items:center; }

  #<?= $modalId ?> .btn-ghost {
    border: 1px solid #e7ebf0; background: #fff; color:#344054;
    padding:.45rem .7rem; border-radius:8px; font-weight:600; font-size:.9rem;
  }
  #<?= $modalId ?> .btn-ghost:hover { background:#f8fafc; }

  #<?= $modalId ?> .btn-primary-strong {
    background:#155EEF; border:1px solid #155EEF; color:#fff;
    padding:.45rem .8rem; border-radius:8px; font-weight:700; font-size:.92rem;
  }
  #<?= $modalId ?> .btn-primary-strong:hover { background:#0b4bd9; border-color:#0b4bd9; }

  /* --- Tabs --- */
  #<?= $modalId ?> .tabs-wrap { background:#fff; border-bottom:1px solid #eef1f5; }
  #<?= $modalId ?> .nav-tabs { border:0; padding:0 1rem; gap: .25rem; }
  #<?= $modalId ?> .nav-tabs .nav-link {
    border:0; border-bottom:2px solid transparent; color:#475467; font-weight:600; padding:.8rem .75rem;
  }
  #<?= $modalId ?> .nav-tabs .nav-link.active {
    color:#155EEF; border-bottom-color:#155EEF; background: transparent;
  }

  /* --- Body sections --- */
  #<?= $modalId ?> .modal-body { padding: 0; background:#f6f7fb; }
  #<?= $modalId ?> .pane { padding: 1.25rem; }

  /* --- Cards & blocks --- */
  #<?= $modalId ?> .card {
    background:#fff; border:1px solid #eef1f5; border-radius:12px;
    box-shadow: 0 1px 2px rgba(16,24,40,.04);
  }
  #<?= $modalId ?> .card + .card { margin-top: 1rem; }
  #<?= $modalId ?> .card-section { padding: 1rem 1rem; }
  #<?= $modalId ?> .card-header {
    padding: .9rem 1rem; border-bottom:1px solid #f0f2f7; display:flex; align-items:center; gap:.5rem;
    background:#fff; border-top-left-radius:12px; border-top-right-radius:12px;
  }
  #<?= $modalId ?> .card-title { font-size:.95rem; font-weight:700; color:#344054; margin:0; }

  /* --- People --- */
  #<?= $modalId ?> .people { display:flex; gap:1.25rem; flex-wrap:wrap; }
  #<?= $modalId ?> .person { display:flex; gap:.75rem; align-items:center; }
  #<?= $modalId ?> .avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #fff; box-shadow:0 1px 3px rgba(0,0,0,.08); }
  #<?= $modalId ?> .avatar-fallback {
    width:40px; height:40px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
    background:#e9eef5; border:2px solid #fff; font-size:13px; font-weight:700; color:#344054;
  }
  #<?= $modalId ?> .person-role { font-size:.78rem; color:#667085; margin:0; }
  #<?= $modalId ?> .person-name { font-size:.95rem; font-weight:700; color:#101828; margin:0; }

  /* --- Key/Value grid --- */
  #<?= $modalId ?> .kv { display:grid; grid-template-columns: 1fr 1fr; gap:.5rem 1rem; }
  #<?= $modalId ?> .kv .k { color:#667085; font-size:.9rem; }
  #<?= $modalId ?> .kv .v { text-align:right; font-size:.9rem; font-weight:600; color:#1f2937; }

  /* --- Badges --- */
  #<?= $modalId ?> .pill { padding:.35rem .7rem; border-radius:999px; font-size:.8rem; font-weight:600; }

  /* --- Description --- */
  #<?= $modalId ?> .desc {
    background:#f8fafc; border:1px solid #eef1f5; border-radius:10px; padding: .9rem; color:#4b5563; line-height:1.55;
    min-height:56px;
  }

  /* --- Activity list --- */
  #<?= $modalId ?> .activity-day { margin-bottom:1rem; }
  #<?= $modalId ?> .activity-day h6 { color:#667085; font-size:.78rem; letter-spacing:.02em; text-transform:uppercase; margin:0 0 .4rem; }
  #<?= $modalId ?> .activity-item { display:flex; gap:.75rem; align-items:flex-start; padding:.7rem .85rem; border-bottom:1px solid #f5f6fa; }
  #<?= $modalId ?> .activity-item:last-child { border-bottom:0; }
  #<?= $modalId ?> .activity-meta { display:flex; justify-content:space-between; gap:.75rem; }
  #<?= $modalId ?> .activity-title { font-weight:700; color:#101828; }
  #<?= $modalId ?> .activity-by { color:#667085; font-weight:500; margin-left:.25rem; }
  #<?= $modalId ?> .activity-time { color:#98a2b3; font-size:.82rem; white-space:nowrap; }

  /* --- Footer --- */
  #<?= $modalId ?> .modal-footer { background:#fff; border-top:1px solid #eef1f5; padding: .8rem 1.25rem; }

  /* --- Responsive --- */
  @media (max-width: 768px) {
    #<?= $modalId ?> .kv { grid-template-columns: 1fr; }
    #<?= $modalId ?> .kv .v { text-align:left; }
  }
</style>

<div class="modal fade"
     id="<?= $modalId ?>"
     tabindex="-1"
     aria-labelledby="<?= $modalId ?>Label"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">

      <!-- Sticky header -->
      <div class="modal-header">
        <div class="title-wrap">
          <div>
            <h3 class="task-title"><?= $title ?></h3>
            <div class="task-id">Task #<?= (int)$taskId ?></div>
          </div>
        </div>

        <div class="header-actions">
          <!-- Copy link -->
          <button type="button" class="btn-ghost" id="<?= $modalId ?>__copyLink" title="Copy link">
            <i class="ti ti-link"></i>
          </button>
          <!-- Edit (hook into your route if needed) -->
          <a href="<?= site_url('tasks/edit/'.(int)$taskId) ?>" class="btn-ghost">
            <i class="ti ti-pencil"></i> Edit
          </a>
          <!-- Primary action example -->
          <a href="<?= site_url('tasks/status/'.(int)$taskId) ?>" class="btn-primary-strong">
            <i class="ti ti-checks"></i> Update Status
          </a>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs-wrap">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="<?= $modalId ?>-tab-overview" data-bs-toggle="tab"
                    data-bs-target="#<?= $modalId ?>-pane-overview" type="button" role="tab"
                    aria-controls="<?= $modalId ?>-pane-overview" aria-selected="true">
              <i class="ti ti-layout-grid me-1"></i> Overview
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="<?= $modalId ?>-tab-activity" data-bs-toggle="tab"
                    data-bs-target="#<?= $modalId ?>-pane-activity" type="button" role="tab"
                    aria-controls="<?= $modalId ?>-pane-activity" aria-selected="false">
              <i class="ti ti-history me-1"></i> Activity
            </button>
          </li>
        </ul>
      </div>

      <div class="modal-body">
        <div class="tab-content">
          <!-- OVERVIEW -->
          <div class="tab-pane fade show active" id="<?= $modalId ?>-pane-overview" role="tabpanel" aria-labelledby="<?= $modalId ?>-tab-overview">
            <div class="pane">
              <!-- People -->
              <div class="card">
                <div class="card-header">
                  <i class="ti ti-users"></i>
                  <h5 class="card-title">People</h5>
                </div>
                <div class="card-section">
                  <div class="people">
                    <div class="person">
                      <?php if ($ownerImg !== ''): ?>
                        <img class="avatar" src="<?= t_s($ownerImg) ?>" alt="Creator">
                      <?php else: ?>
                        <span class="avatar-fallback"><?= t_s($ownerIni) ?></span>
                      <?php endif; ?>
                      <div>
                        <div class="person-role">Created by</div>
                        <div class="person-name"><?= t_s($ownerName ?: '—') ?></div>
                      </div>
                    </div>

                    <div class="person">
                      <?php if ($assigneeImg !== ''): ?>
                        <img class="avatar" src="<?= t_s($assigneeImg) ?>" alt="Assignee">
                      <?php else: ?>
                        <span class="avatar-fallback"><?= t_s($assigneeIni) ?></span>
                      <?php endif; ?>
                      <div>
                        <div class="person-role">Assigned to</div>
                        <div class="person-name"><?= t_s($assigneeName ?: 'Unassigned') ?></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Description -->
              <div class="card">
                <div class="card-header">
                  <i class="ti ti-align-left"></i>
                  <h5 class="card-title">Description</h5>
                </div>
                <div class="card-section">
                  <div class="desc">
                    <?= t_val($description, 'No description provided') ?>
                  </div>
                </div>
              </div>

              <!-- Properties -->
              <div class="card">
                <div class="card-header">
                  <i class="ti ti-info-circle"></i>
                  <h5 class="card-title">Properties</h5>
                </div>
                <div class="card-section">
                  <div class="kv">
                    <div class="k">Status</div>
                    <div class="v"><span class="pill bg-primary-subtle text-primary"><?= $status ?: 'Not Set' ?></span></div>

                    <div class="k">Priority</div>
                    <div class="v"><span class="pill <?= $priorityInfo['class'] ?>"><?= $priorityInfo['label'] ?></span></div>

                    <div class="k">Visible to Team</div>
                    <div class="v"><?= t_boolbadge($task['visible_to_team'] ?? 0) ?></div>

                    <div class="k">Deadline Notified</div>
                    <div class="v"><?= t_boolbadge($task['deadline_notified'] ?? 0) ?></div>
                  </div>
                </div>
              </div>

              <!-- Dates -->
              <div class="card">
                <div class="card-header">
                  <i class="ti ti-calendar"></i>
                  <h5 class="card-title">Dates</h5>
                </div>
                <div class="card-section">
                  <div class="kv">
                    <div class="k">Created</div>
                    <div class="v"><?= $created ?></div>

                    <div class="k">Start Date</div>
                    <div class="v"><?= $startdate ?></div>

                    <div class="k">Due Date</div>
                    <div class="v"><?= $duedate ?></div>

                    <div class="k">Finished</div>
                    <div class="v"><?= $finished ?></div>
                  </div>
                </div>
              </div>

              <!-- Recurrence -->
              <div class="card">
                <div class="card-header">
                  <i class="ti ti-arrows-shuffle"></i>
                  <h5 class="card-title">Recurrence</h5>
                </div>
                <div class="card-section">
                  <div class="kv">
                    <div class="k">Recurring</div>
                    <div class="v"><?= t_boolbadge($task['recurring'] ?? 0) ?></div>

                    <?php if ($isRecurring): ?>
                      <div class="k">Type</div>
                      <div class="v"><?= t_val($task['recurring_type'] ?? '—') ?></div>

                      <div class="k">Repeat Every</div>
                      <div class="v"><?= t_val($task['repeat_every'] ?? '—') ?></div>

                      <div class="k">Cycles</div>
                      <div class="v">
                        <?= t_val($task['cycles'] ?? '—') ?> / <?= t_val($task['total_cycles'] ?? '—') ?>
                      </div>

                      <div class="k">Last Recurring</div>
                      <div class="v"><?= format_datetime($task['last_recurring_date'] ?? '') ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Relations -->
              <div class="card">
                <div class="card-header">
                  <i class="ti ti-link"></i>
                  <h5 class="card-title">Relations</h5>
                </div>
                <div class="card-section">
                  <div class="kv">
                    <div class="k">Related To</div>
                    <div class="v"><?= t_val($task['rel_type'] ?? '—') ?></div>

                    <div class="k">Related ID</div>
                    <div class="v"><?= isset($task['rel_id']) ? '#'.(int)$task['rel_id'] : '—' ?></div>

                    <div class="k">Milestone</div>
                    <div class="v"><?= t_val($task['milestone'] ?? '—') ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ACTIVITY -->
          <div class="tab-pane fade" id="<?= $modalId ?>-pane-activity" role="tabpanel" aria-labelledby="<?= $modalId ?>-tab-activity">
            <div class="pane">
              <?php if (empty($activities)): ?>
                <div class="card">
                  <div class="card-section text-center text-muted py-5">
                    <i class="ti ti-history" style="font-size:32px;"></i>
                    <div class="mt-2">No activity recorded yet.</div>
                  </div>
                </div>
              <?php else: ?>
                <?php foreach ($grouped as $ymd => $rows): ?>
                  <div class="card activity-day">
                    <div class="card-header">
                      <i class="ti ti-calendar-event"></i>
                      <h5 class="card-title mb-0"><?= format_date($ymd) ?></h5>
                    </div>
                    <div class="card-section">
                      <?php foreach ($rows as $r): ?>
                        <?php
                          [$label,$icon] = $labelFor($r['activity']);
                          $name   = $r['user_name'] !== '' ? $r['user_name'] : ($r['user_id'] ? ('User #'.$r['user_id']) : 'System');
                          $avatar = t_avatar_url($r['user_avatar']);
                        ?>
                        <div class="activity-item">
                          <div class="flex-shrink-0">
                            <?php if ($avatar !== ''): ?>
                              <img src="<?= t_s($avatar) ?>" alt="" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                            <?php else: ?>
                              <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                <span class="small text-muted"><?= t_s(t_initials($name)) ?></span>
                              </div>
                            <?php endif; ?>
                          </div>
                          <div class="flex-grow-1 w-100">
                            <div class="activity-meta">
                              <div class="activity-title">
                                <span class="activity-by"><?= t_s($name) ?></span>
                                <i class="<?= t_s($icon) ?> me-1"></i> <span class="small text-muted text-primary"><?= t_s($label) ?></span>                                
                              </div>
                                <?php if ($r['dateadded']): ?>
                                  <div class="activity-time"><?= format_datetime($r['dateadded']) ?></div>
                                <?php endif; ?>
                                                            </div>
                                                            <?php if ($r['description'] !== ''): ?>
                                <div class="mt-1 small text-body">
                                  <?= t_activity_render($r['activity'], $r['description']) ?>
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
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-ghost" data-bs-dismiss="modal" id="<?= $modalId ?>__closeBtn">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const modalEl = document.getElementById('<?= $modalId ?>');
  if (!modalEl) return;

  // Bootstrap modal: static/hardened
  bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false, focus: true });

  // Copy link action
  const copyBtn = document.getElementById('<?= $modalId ?>__copyLink');
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText('<?= t_s($shareUrl) ?>');
        copyBtn.innerHTML = '<i class="ti ti-check"></i>';
        setTimeout(() => copyBtn.innerHTML = '<i class="ti ti-link"></i>', 1200);
      } catch (e) {
        alert('Copy failed. URL: <?= t_s($shareUrl) ?>');
      }
    });
  }

  // Focus first active tab on show for accessibility
  modalEl.addEventListener('shown.bs.modal', () => {
    const activeTab = modalEl.querySelector('.nav-tabs .nav-link.active');
    if (activeTab) activeTab.focus({ preventScroll: true });
  });
})();
</script>
