<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/* --------------------------------------------------------------------------
 * Expected vars (controller should provide):
 *   $task         : array{id,name,description,status,priority,startdate,duedate,addedfrom,assignee_id,followers_json}
 *   $assignee     : array{id, firstname, lastname, profile_image} | null (optional)
 *   $followers    : array<array{id,firstname,lastname,profile_image}> (optional)
 *   $checklist    : array<array{id,description,finished}> (optional)
 *   $attachments  : array<array{id,file_name,file_path,uploaded_by,uploaded_at,uploaded_by_name,uploaded_by_avatar}> (optional)
 *   $activity     : array<array{user_name,user_avatar,activity,description,dateadded}> (optional, recent N)
 *
 * You can render this view from controller action /tasks/modal/{id}
 * and load it via CalendarModuleBridge (extendedProps.modal_url).
 * -------------------------------------------------------------------------- */

$task = is_array($task ?? null) ? $task : [];

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
if (!function_exists('avatar_url_safe')) {
  function avatar_url_safe($profile_image){
    if (function_exists('user_avatar_url')) return user_avatar_url($profile_image);
    if (!$profile_image) return base_url('assets/images/default-avatar.png');
    if (preg_match('#^https?://#i', $profile_image)) return $profile_image;
    return base_url('uploads/users/profile/'.ltrim($profile_image,'/'));
  }
}

$id        = (int)($task['id'] ?? 0);
$name      = trim((string)($task['name'] ?? ''));
$desc      = trim((string)($task['description'] ?? ''));
$status    = strtolower(trim((string)($task['status'] ?? 'not_started')));
$priority  = strtolower(trim((string)($task['priority'] ?? 'normal')));
$startdate = $task['startdate'] ?? null;
$duedate   = $task['duedate'] ?? null;

$assigneeRow = is_array($assignee ?? null) ? $assignee : null;
$assigneeName = $assigneeRow
  ? trim(($assigneeRow['firstname'] ?? '').' '.($assigneeRow['lastname'] ?? ''))
  : '';
$assigneeAvatar = $assigneeRow
  ? avatar_url_safe($assigneeRow['profile_image'] ?? null)
  : base_url('assets/images/default-avatar.png');

$followers = is_array($followers ?? null) ? $followers : [];

$badgeStatus = [
  'not_started' => ['label'=>'Not started','class'=>'bg-secondary'],
  'in_progress' => ['label'=>'In progress','class'=>'bg-info'],
  'on_hold'     => ['label'=>'On hold','class'=>'bg-warning text-dark'],
  'blocked'     => ['label'=>'Blocked','class'=>'bg-danger'],
  'completed'   => ['label'=>'Completed','class'=>'bg-success'],
];
$badge = $badgeStatus[$status] ?? ['label'=>ucfirst($status ?: 'Open'),'class'=>'bg-secondary'];

$prioChip = [
  'low'    => ['label'=>'Low',    'class'=>'chip chip-low'],
  'normal' => ['label'=>'Normal', 'class'=>'chip chip-normal'],
  'high'   => ['label'=>'High',   'class'=>'chip chip-high'],
  'urgent' => ['label'=>'Urgent', 'class'=>'chip chip-urgent'],
];
$chip = $prioChip[$priority] ?? $prioChip['normal'];

$canEdit   = function_exists('staff_can') ? staff_can('edit','tasks')   : false;
$canDelete = function_exists('staff_can') ? staff_can('delete','tasks') : false;
$canAssign = function_exists('staff_can') ? staff_can('assign','tasks') : false;

$fmt = function($date){
  if (!$date) return '—';
  // adjust to your preferred format; use get_setting if available
  return date('Y-m-d', strtotime($date));
};
?>

<style>
  .kv {display:grid; grid-template-columns: 120px 1fr; gap:.25rem .75rem; align-items:center;}
  .chip {display:inline-block; padding:.125rem .5rem; border-radius:999px; font-size:.75rem; line-height:1.25rem;}
  .chip-low{background:#6c757d; color:#fff;}
  .chip-normal{background:#0d6efd; color:#fff;}
  .chip-high{background:#fd7e14; color:#fff;}
  .chip-urgent{background:#dc3545; color:#fff;}
  .pill {border-radius:999px; padding:.125rem .5rem; font-size:.75rem;}
  .meta {background:#f8f9fa; border:1px solid #edf0f3; border-radius:.5rem; padding:.75rem;}
  .avatar {width:32px; height:32px; border-radius:50%; object-fit:cover;}
  .follower {display:inline-flex; align-items:center; gap:.5rem; margin:.125rem .5rem .125rem 0;}
  .list-check {list-style:none; margin:0; padding:0;}
  .list-check li {display:flex; align-items:flex-start; gap:.5rem; padding:.375rem .25rem; border-bottom:1px dashed #eee;}
  .list-check li:last-child{border-bottom:0;}
  .list-activity {list-style:none; margin:0; padding:0;}
  .list-activity li {display:flex; gap:.5rem; padding:.375rem 0; border-bottom:1px dashed #eee;}
  .list-activity li:last-child{border-bottom:0;}
  .file-row {display:flex; align-items:center; justify-content:space-between; padding:.375rem .5rem; border:1px solid #edf0f3; border-radius:.375rem; margin-bottom:.5rem;}
</style>

<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-2">
  <div>
    <div class="text-muted small">Task #<?= (int)$id ?></div>
    <h5 class="mb-1"><?= t_s($name ?: 'Untitled task') ?></h5>
    <div class="d-flex align-items-center gap-2">
      <span class="pill <?= t_s($badge['class']) ?>"><?= t_s($badge['label']) ?></span>
      <span class="<?= t_s($chip['class']) ?>"><?= t_s('Priority: '.$chip['label']) ?></span>
    </div>
  </div>
  <div class="text-end">
    <?php if ($canEdit): ?>
      <a href="<?= site_url('tasks/edit/'.$id) ?>" class="btn btn-sm btn-primary">Edit</a>
    <?php endif; ?>
    <a href="<?= site_url('tasks/view/'.$id) ?>" class="btn btn-sm btn-outline-secondary">Open full</a>
  </div>
</div>

<!-- Meta -->
<div class="meta mb-3">
  <div class="kv">
    <div class="text-muted">Start</div>
    <div><?= t_s($fmt($startdate)) ?></div>

    <div class="text-muted">Due</div>
    <div><?= t_s($fmt($duedate)) ?></div>

    <div class="text-muted">Assignee</div>
    <div class="d-flex align-items-center gap-2">
      <img class="avatar" src="<?= t_s($assigneeAvatar) ?>" alt="">
      <span><?= t_val($assigneeName) ?></span>
      <?php if ($canAssign): ?>
        <a href="<?= site_url('tasks/reassign/'.$id) ?>" class="btn btn-xs btn-outline-primary ms-2">Change</a>
      <?php endif; ?>
    </div>

    <div class="text-muted">Followers</div>
    <div>
      <?php if ($followers): foreach ($followers as $f): 
        $fn = trim(($f['firstname'] ?? '').' '.($f['lastname'] ?? ''));
        $av = avatar_url_safe($f['profile_image'] ?? null);
      ?>
        <span class="follower">
          <img class="avatar" src="<?= t_s($av) ?>" alt="">
          <span class="small"><?= t_val($fn, 'User#'.(int)($f['id'] ?? 0)) ?></span>
        </span>
      <?php endforeach; else: ?>
        <span class="text-muted small">None</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Description -->
<?php if ($desc !== ''): ?>
  <div class="mb-3">
    <div class="fw-semibold mb-1">Description</div>
    <div class="text-muted"><?= nl2br(t_s($desc)) ?></div>
  </div>
<?php endif; ?>

<!-- Checklist -->
<?php if (!empty($checklist)): ?>
  <div class="mb-3">
    <div class="fw-semibold mb-1">Checklist</div>
    <ul class="list-check">
      <?php foreach ($checklist as $item): ?>
        <li>
          <input class="form-check-input mt-1" type="checkbox" disabled <?= !empty($item['finished']) ? 'checked' : '' ?>>
          <div><?= t_s($item['description'] ?? '') ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Attachments -->
<?php if (!empty($attachments)): ?>
  <div class="mb-3">
    <div class="fw-semibold mb-1">Attachments</div>
    <?php foreach ($attachments as $att): ?>
      <div class="file-row">
        <div class="d-flex align-items-center gap-2">
          <i class="ti ti-paperclip"></i>
          <a href="<?= t_s($att['file_path'] ?? '#') ?>" target="_blank"><?= t_s($att['file_name'] ?? 'file') ?></a>
        </div>
        <div class="small text-muted"><?= t_s(date('Y-m-d H:i', strtotime($att['uploaded_at'] ?? 'now'))) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Recent Activity -->
<?php if (!empty($activity)): ?>
  <div class="mb-1">
    <div class="fw-semibold mb-1">Recent activity</div>
    <ul class="list-activity">
      <?php foreach ($activity as $a): ?>
        <li>
          <img class="avatar" src="<?= t_s($a['user_avatar'] ?? base_url('assets/images/default-avatar.png')) ?>" alt="">
          <div>
            <div class="small">
              <b><?= t_s($a['user_name'] ?? 'System') ?></b>
              <span class="text-muted">• <?= t_s($a['activity'] ?? '') ?></span>
            </div>
            <?php if (!empty($a['description'])): ?>
              <div class="text-muted small"><?= t_s($a['description']) ?></div>
            <?php endif; ?>
            <div class="text-muted small"><?= t_s(date('Y-m-d H:i', strtotime($a['dateadded'] ?? 'now'))) ?></div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<hr class="my-3">

<!-- Quick actions: wire these endpoints in your controller -->
<div class="d-flex flex-wrap gap-2">
  <?php if ($status !== 'completed'): ?>
    <a href="<?= site_url('tasks/mark_complete/'.$id) ?>" class="btn btn-success btn-sm">Mark complete</a>
  <?php else: ?>
    <a href="<?= site_url('tasks/reopen/'.$id) ?>" class="btn btn-outline-secondary btn-sm">Reopen</a>
  <?php endif; ?>

  <a href="<?= site_url('tasks/add_follower/'.$id) ?>" class="btn btn-outline-primary btn-sm">Add follower</a>

  <?php if ($canDelete): ?>
    <a href="<?= site_url('tasks/delete/'.$id) ?>" class="btn btn-danger btn-sm"
       onclick="return confirm('Delete this task?');">Delete</a>
  <?php endif; ?>
</div>
