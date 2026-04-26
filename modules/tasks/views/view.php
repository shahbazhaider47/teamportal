<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$CI  = get_instance();
$uid = (int)($CI->session->userdata('user_id') ?? 0);

$CI->load->view('tasks/modals/update_description');

/* ---------- tiny safe printers ---------- */
if (!function_exists('t_s')) {
  function t_s($v) { return is_scalar($v) ? html_escape((string)$v) : ''; }
}
if (!function_exists('t_initials')) {
  function t_initials($name) {
    $name = trim((string)$name);
    if ($name === '') return 'U';
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = mb_substr($parts[count($parts)-1] ?? '', 0, 1);
    return mb_strtoupper($first.$last);
  }
}

/* ---------- normalize ---------- */
$task   = is_array($task ?? null) ? $task : [];
$taskId = (int)($task['id'] ?? 0);

/* followers normalized to [int] */
$followers_in = $task['followers'] ?? [];
if (is_string($followers_in)) {
  $decoded  = json_decode($followers_in, true);
  $followers = is_array($decoded) ? $decoded : [];
} elseif (is_array($followers_in)) {
  $followers = $followers_in;
} else {
  $followers = [];
}
$followers = array_values(array_unique(array_filter(array_map('intval', $followers))));
$followersSet = array_flip($followers);

/* ---------- policy flags (read off library loaded by controller) ---------- */
$policy     = property_exists($CI, 'tasks_policy') ? $CI->tasks_policy : (property_exists($CI, 'policy') ? $CI->policy : null);
$canView    = $policy ? $policy->can_view($task, $uid)             : true;
$canEdit    = $policy ? $policy->can_edit($task, $uid)             : false;
$canAssign  = $policy ? $policy->can_assign($task, $uid)           : false;
$canFollow  = $policy ? $policy->can_manage_followers($task, $uid) : false;
$isAssignee = $policy ? $policy->is_assignee($task, $uid)          : ((int)($task['assignee_id'] ?? 0) === $uid);
$isFollower = in_array($uid, $followers, true);

/* commenters: assignee OR follower OR editor */
$canComment = $isAssignee || $isFollower || $canEdit;

/* ---------- settings (same semantics as Support) ---------- */
$orderDesc = function_exists('get_setting') ? (get_setting('tasks_comments_order') === 'descending') : false;

/* ---------- comments/messages ---------- */
$comments = is_array($comments ?? null) ? $comments : [];

/* ---------- labels ---------- */
$statusLabelMap = [
  'not_started' => ['Not Started',  'secondary'],
  'in_progress' => ['In Progress',  'primary'],
  'in_review'   => ['In Review',    'info'],  
  'on_hold'     => ['On Hold',      'warning'],
  'completed'   => ['Completed',    'success'],
  'cancelled'   => ['Cancelled',      'danger'],
];
$priorityLabelMap = [
  'low'     => ['Low',      'low'],
  'normal'  => ['Normal',   'normal'],
  'high'    => ['High',     'high'],  
  'urgent'  => ['Urgent',   'urgent'],
  'no'      => ['N/A',      'no'],
];

list($statusText, $statusClass) = $statusLabelMap[strtolower((string)($task['status'] ?? 'not_started'))] ?? ['—','secondary'];
list($prioText,   $prioClass)   = $priorityLabelMap[strtolower((string)($task['priority'] ?? 'normal'))]  ?? ['—','normal'];

/* ---------- active users fallback (for assign dropdown + follower search) ---------- */
if (!isset($active_users) || !is_array($active_users) || !$active_users) {
  $active_users = $CI->db->select('id, TRIM(CONCAT(COALESCE(firstname,""), " ", COALESCE(lastname,""))) AS fullname, profile_image, is_active', false)
                         ->from('users')->where('is_active', 1)
                         ->order_by('fullname','ASC')->get()->result_array();
}

/* ---------- Build follower candidates for search-add (exclude existing + assignee) ---------- */
$assigneeId = (int)($task['assignee_id'] ?? 0);
$candidates = [];
if (!empty($active_users)) {
  foreach ($active_users as $u) {
    $uidX = (int)($u['id'] ?? 0);
    if ($uidX <= 0) continue;
    if (isset($followersSet[$uidX])) continue;          // already follower
    if ($assigneeId && $uidX === $assigneeId) continue; // skip assignee (as in Support)
    if (isset($u['is_active']) && (int)$u['is_active'] === 0) continue;

    $name = trim(($u['fullname'] ?? '') ?: trim(($u['firstname'] ?? '').' '.($u['lastname'] ?? '')));
    if ($name === '') $name = 'User #'.$uidX;

    $avatar = '';
    if (!empty($u['profile_image'])) {
      $avatar = base_url('uploads/users/profile/'.$u['profile_image']);
    }

    $candidates[] = ['id'=>$uidX, 'name'=>$name, 'avatar'=>$avatar];
  }
}

/* Resolve followers for chips if controller provided richer info */
$followersResolved = is_array($task['followers_resolved'] ?? null) ? $task['followers_resolved'] : [];

/* ---------- File attachment settings (matching Support module) ---------- */
$maxFilesSetting = (int)((function_exists('get_setting') ? get_setting('support_max_attachments') : 10) ?? 10);
if ($maxFilesSetting <= 0) { $maxFilesSetting = 0; } // 0 = disabled
$allowedCsv      = (string)((function_exists('get_setting') ? get_setting('support_allowed_mime_types') : 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip') ?? '');
$allowedExtArr   = array_values(array_filter(array_map('trim', explode(',', $allowedCsv))));
$acceptAttr      = '';
if (!empty($allowedExtArr)) {
  $acceptList = array_map(static function($x) { $x = ltrim(strtolower($x), '.'); return '.' . $x; }, $allowedExtArr);
  $acceptAttr = implode(',', $acceptList);
}
?>

<style>
  .chat-item { display:flex; gap:.5rem; margin-bottom:1rem; }
  .chat-item .avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; }
  .chat-item .avatar-fallback {
    width:36px; height:36px; border-radius:50%;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:12px; background:#f1f3f5; border:1px solid #dee2e6;
  }
  .chat-bubble {
    max-width: 100%;
    border-radius: .75rem;
    box-shadow: var(--bs-box-shadow-sm);
    border: 1px solid var(--bs-border-color);
    background: #f8f9fa;
    padding: .75rem .75rem .6rem;
    width: 100%;
  }
  .chat-meta { font-size:.8rem; }
  .capital { text-transform: capitalize; }
  .follower-chip { display:inline-flex; align-items:center; gap:.4rem; border:1px solid var(--bs-border-color); border-radius:999px; padding:.2rem .5rem; }
  .follower-chip .avatar-mini { width:20px; height:20px; border-radius:50%; object-fit:cover; }
  .list-check .toggle { min-width:34px; }
  .btn-ssm { --bs-btn-padding-y: .15rem; --bs-btn-padding-x: .35rem; --bs-btn-font-size: .75rem; }
  .chat-attachments .attachment-pill {
    border:1px solid var(--bs-border-color);
    border-radius:20px; padding:.15rem .5rem; text-decoration:none;
    display:inline-flex; align-items:center; gap:.35rem;
  }
  .checklist-progress { height: 6px; }
  .checklist-item.completed .checklist-text { text-decoration: line-through; color: var(--bs-secondary); }

  /* Progress bar overlay label */
  .checklist-progress { height: 14px; background: #e9ecef; }
  .checklist-progress .progress-bar { transition: width .2s ease; }
  .progress-label {
    position: absolute; inset: 0; display:flex; align-items:center; justify-content:center;
    font-size: 12px; color: #fff; pointer-events: none;
  }

  /* Round toggle like the screenshot */
  .chk-toggle {
    width: 22px; height: 22px; border-radius: 50%;
    display:inline-flex; align-items:center; justify-content:center;
    border: 1.5px solid var(--bs-secondary);
    background: #fff; color: var(--bs-secondary);
    padding: 0;
  }
  .chk-toggle.is-done {
    background: #28a745; border-color: #28a745; color: #fff;
  }
  .checklist-item .checklist-text { line-height: 1.4; }

.description-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    font-size: 0.85rem;
    line-height: 1.5;
    color: #4b5563;
    min-height: 60px;
  }
  
</style>

<div class="container-fluid tasks-module">

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title m-0">
        Task #<?= (int)($task['id'] ?? 0) ?><i class="ti ti-chevron-right"></i>
      </h1>
      <span class="badge bg-<?= $statusClass ?>"><?= t_s($statusText) ?></span>
      <span class="priority pr-<?= $prioClass ?>"><i class="ti ti-flag-3-filled me-1"></i><?= t_s($prioText) ?></span>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="<?= site_url('tasks?view=list') ?>" class="btn btn-light-primary btn-header">
        <i class="ti ti-arrow-back-up"></i> Back to Tasks
      </a>

    <a href="#"
       class="btn btn-primary btn-header"
       data-bs-toggle="modal"
       data-bs-target="#taskDetailsModalRight_<?= (int)($task['id'] ?? 0) ?>">
      <i class="ti ti-info-circle"></i> Task Details
    </a>

    </div>
  </div>

  <div class="row g-3">
    <!-- LEFT: Description + Discussion + Composer -->
    <div class="col-12 col-lg-6">

      <!-- Quick Status & Assign -->
      <?php if (!empty($taskId)): ?>
        <div class="card mb-3">
          <div class="card-body p-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="small text-muted">Status:</span>
              <form method="post" action="<?= site_url('tasks/status/'.$taskId) ?>" class="d-inline app-form">
                <select name="status" class="form-select form-control-sm small form-select-sm d-inline-block app-form" style="width:150px;" onchange="this.form.submit()">
                  <?php foreach ($statusLabelMap as $key => $pair): ?>
                    <option class="small" value="<?= $key ?>" <?= strtolower((string)($task['status'] ?? '')) === $key ? 'selected' : '' ?>>
                      <?= t_s($pair[0]) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>

              <?php if ($canAssign): ?>
                <form method="post" action="<?= site_url('tasks/assign/'.$taskId) ?>" class="d-inline app-form">
                  <span class="small text-muted">Assign To:</span>
                  <select name="assignee_id" class="form-select form-control-sm small form-select-sm d-inline-block app-form" style="width:200px;" onchange="this.form.submit()">
                    <option class="small" value="">Unassigned</option>
                    <?php foreach ($active_users as $u):
                      $uidX  = (int)($u['id'] ?? 0);
                      $fname = trim($u['fullname'] ?? '');
                      if ($fname === '') $fname = 'User #'.$uidX;
                    ?>
                      <option class="small" value="<?= $uidX ?>" <?= ((int)($task['assignee_id'] ?? 0) === $uidX) ? 'selected' : '' ?>>
                        <?= t_s($fname) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Title + Assignee + Description -->
      <div class="card mb-3">
        <div class="card-header py-2 bg-light-primary">
          <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">
              <span class="text-muted text-primary"><?= t_s($task['name'] ?? ('#'.$taskId)) ?></span>
            </h2>
        
            <!-- Trigger button (place in your header actions) -->
            <?php $canEdit  = staff_can('edit', 'tasks'); ?>
            <button type="button"
                    class="btn <?= $canEdit ? 'btn-light-primary' : 'btn-disabled' ?> btn-ssm text-end"
                    <?= $canEdit ? 'data-bs-toggle="modal" data-bs-target="#modalEditTaskDetails" ' : 'disabled' ?>
                    title="Edit description">
              <i class="ti ti-edit me-1"></i>
            </button>

          </div>
        </div>

        <div class="card-body">
          <?php
            $assigneeId    = (int)($task['assignee_id'] ?? 0);
            $assigneeName  = trim((string)($task['assignee_name'] ?? ''));
            $assigneeImg   = trim((string)($task['assignee_avatar'] ?? ($task['assignee_profile_image'] ?? '')));

            if ($assigneeName === '' && isset($task['assignee_firstname'], $task['assignee_lastname'])) {
              $assigneeName = trim(($task['assignee_firstname'] ?? '') . ' ' . ($task['assignee_lastname'] ?? ''));
            }
            if ($assigneeName === '' && $assigneeId > 0) {
              $assigneeName = 'User #'.$assigneeId;
            }

            // If only a file name is provided, build URL
            if ($assigneeImg !== '' && strpos($assigneeImg, 'http') !== 0 && strpos($assigneeImg, '/') === false) {
              $assigneeImg = base_url('uploads/users/profile/'.$assigneeImg);
            }

            $assigneeIni = t_initials($assigneeName);

            // Description preview (120 chars)
            $descFull  = (string)($task['description'] ?? '');
            $descText  = trim(strip_tags($descFull));
            $hasMore   = mb_strlen($descText) > 200;
            $descShort = $hasMore ? (mb_substr($descText, 0, 200) . '…') : $descText;
          ?>

          <!-- Assignee row -->
          <div class="d-flex align-items-center gap-2 mb-2">
            <?php if ($assigneeImg !== ''): ?>
              <img src="<?= htmlspecialchars($assigneeImg, ENT_QUOTES, 'UTF-8') ?>"
                   alt="<?= htmlspecialchars($assigneeName ?: 'Assignee', ENT_QUOTES, 'UTF-8') ?>"
                   class="rounded-circle object-fit-cover"
                   width="35" height="35"
                   style="border:1px solid var(--bs-border-color)">
            <?php else: ?>
              <span class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center"
                    style="width:35px;height:35px;font-size:12px;">
                <?= htmlspecialchars($assigneeIni, ENT_QUOTES, 'UTF-8') ?>
              </span>
            <?php endif; ?>
            <div class="small">
              <strong class="text-primary"><?= html_escape($assigneeName ?: 'Unassigned') ?></strong>
            </div>
          </div>

          <!-- Description with View more/less -->
          <div class="mb-3">
            <?php if ($descFull === ''): ?>
              <span class="text-muted small">No description added yet.</span>
            <?php else: ?>
              <div class="description-content" id="taskDescWrap">
                <div id="taskDescShort"><?= nl2br(html_escape($descShort)) ?></div>
                <div id="taskDescFull" style="display:none;"><?= $descFull ?></div>
                    
                <?php if ($hasMore): ?>
                  <button type="button"
                          id="taskDescToggleBtn"
                          class="btn btn-link text-primary p-0 btn-ssm mt-2">
                    View more
                  </button>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <script>
        (function () {
          const btn   = document.getElementById('taskDescToggleBtn');
          if (!btn) return;

          const shortEl = document.getElementById('taskDescShort');
          const fullEl  = document.getElementById('taskDescFull');

          btn.addEventListener('click', function () {
            const isShortVisible = shortEl.style.display !== 'none';
            if (isShortVisible) {
              shortEl.style.display = 'none';
              fullEl.style.display  = '';
              btn.textContent       = 'View less';
            } else {
              shortEl.style.display = '';
              fullEl.style.display  = 'none';
              btn.textContent       = 'View more';
            }
          });
        })();
        </script>
      </div>

        <div class="card">
         <div class="card-body">
          <ul class="nav nav-tabs tab-primary bg-primary p-1 small" id="bg" role="tablist">
           <li class="nav-item" role="presentation">
            <button class="nav-link active" id="checklist-tab" data-bs-toggle="tab"
             data-bs-target="#checklist-tab-pane" type="button" role="tab" aria-controls="checklist-tab-pane"
             aria-selected="true"> <i class="ti ti-list-check pe-1 ps-1"></i> Checklist</button>
           </li>
           <li class="nav-item" role="presentation">
            <button class="nav-link" id="attachments-tab" data-bs-toggle="tab"
             data-bs-target="#attachments-tab-pane" type="button" role="tab" aria-controls="attachments-tab-pane"
             aria-selected="false"><i class="ti ti-file-description pe-1 ps-1"></i>Files</button>
           </li>
           <li class="nav-item" role="presentation">
            <button class="nav-link" id="followers-tab" data-bs-toggle="tab"
             data-bs-target="#followers-tab-pane" type="button" role="tab" aria-controls="followers-tab-pane"
             aria-selected="false"><i class="ti ti-users pe-1 ps-1"></i>Members</button>
           </li>
          </ul>

        <div class="tab-content" id="bgContent">
           <div class="tab-pane fade show active" id="checklist-tab-pane" role="tabpanel"
            aria-labelledby="checklist-tab" tabindex="0">    
              <!-- Checklist (HTML provided by controller) -->
              <?= $checklist_html ?>
            </div>

           <div class="tab-pane fade" id="attachments-tab-pane" role="tabpanel"
            aria-labelledby="attachments-tab" tabindex="0">    
          <!-- Attachments -->
          <?php
            $CI->load->view('tasks/partials/attachments', [
              'taskId'          => (int)$taskId,
              'attachments'     => $attachments ?? [],
              'canEdit'         => !empty($canEdit),
              'isAssignee'      => !empty($isAssignee),
              'allowedCsv'      => $allowedCsv ?? 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip',
              'acceptAttr'      => $acceptAttr ?? '',
              'maxFilesSetting' => (int)($maxFilesSetting ?? 10),
            ]);
          ?>
                 </div>
                 

           <div class="tab-pane fade" id="followers-tab-pane" role="tabpanel"
            aria-labelledby="followers-tab" tabindex="0">    

      <?php
        // Followers resolver
        $followers = [];
        if (isset($task['followers'])) {
            $raw = $task['followers'];
            if (is_array($raw)) {
                $followers = array_map('intval', $raw);
            } elseif (is_string($raw)) {
                $s = trim($raw);
                if ($s !== '') {
                    $dec = json_decode($s, true);
                    if (is_array($dec)) {
                        $followers = array_map('intval', $dec);
                    } else {
                        $followers = array_map('intval', preg_split('/\s*,\s*/', $s, -1, PREG_SPLIT_NO_EMPTY));
                    }
                }
            } elseif (is_numeric($raw)) {
                $followers = [ (int)$raw ];
            }
        }
        $followers = array_values(array_unique(array_filter(array_map('intval', $followers))));
        $followersResolved = is_array($task['followers_resolved'] ?? null) ? $task['followers_resolved'] : [];

        if (empty($followersResolved) && !empty($followers)) {
            $activeIndex = [];
            if (isset($active_users) && is_array($active_users)) {
                foreach ($active_users as $u) {
                    $id = (int)($u['id'] ?? 0);
                    if ($id <= 0) continue;
                    $name = trim($u['fullname'] ?? (($u['firstname'] ?? '').' '.($u['lastname'] ?? '')));
                    if ($name === '') $name = 'User #'.$id;

                    $avatar = '';
                    if (!empty($u['profile_image'])) {
                        $avatar = base_url('uploads/users/profile/'.$u['profile_image']);
                    } elseif (function_exists('user_avatar_url')) {
                        $avatar = user_avatar_url($u['profile_image'] ?? null);
                    }
                    $activeIndex[$id] = ['id'=>$id,'name'=>$name,'avatar'=>$avatar];
                }
            }

            $missing = [];
            foreach ($followers as $fid) {
                if (isset($activeIndex[$fid])) {
                    $followersResolved[] = $activeIndex[$fid];
                } else {
                    $missing[] = $fid;
                }
            }

            if (!empty($missing)) {
                $rows = $CI->db->select('id, TRIM(CONCAT(COALESCE(firstname,""), " ", COALESCE(lastname,""))) AS fullname, profile_image', false)
                               ->from('users')
                               ->where_in('id', $missing)
                               ->get()->result_array();
                foreach ($rows as $r) {
                    $id = (int)$r['id'];
                    $name = trim($r['fullname'] ?? '');
                    if ($name === '') $name = 'User #'.$id;

                    $avatar = '';
                    if (!empty($r['profile_image'])) {
                        $avatar = base_url('uploads/users/profile/'.$r['profile_image']);
                    } elseif (function_exists('user_avatar_url')) {
                        $avatar = user_avatar_url($r['profile_image'] ?? null);
                    }

                    $followersResolved[] = ['id'=>$id,'name'=>$name,'avatar'=>$avatar];
                }
            }
        }

        $maxChips = 6;
        $shownFollowers = array_slice($followersResolved, 0, $maxChips);
        $extraFollowers = max(0, count($followersResolved) - count($shownFollowers));
      ?>

      <!-- Followers -->
      <div class="mb-3" id="followersCard" data-task-id="<?= (int)$taskId ?>">
        <div class="card-header py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <strong  class="text-muted">Task Followers</strong>
          <?php if ($canFollow): ?>
            <span class="small text-muted">Add/remove followers</span>
          <?php else: ?>
            <span class="small text-muted">Read-only</span>
          <?php endif; ?>
        </div>

        <div class="card-body app-form">
          <div id="followersChips" class="d-flex align-items-center flex-wrap gap-2 mb-2">
            <?php if (empty($shownFollowers) && empty($followers)): ?>
              <div class="text-muted small">No followers.</div>
            <?php else: ?>
              <?php foreach ($shownFollowers as $w): ?>
                <?php
                  $wId   = (int)($w['id'] ?? 0);
                  $wName = trim((string)($w['name'] ?? 'User'));
                  $wImg  = (string)($w['avatar'] ?? '');
                  $ini   = t_initials($wName);
                ?>
                <span class="d-inline-flex align-items-center border rounded-pill px-2 py-1 gap-2 follower-chip" data-user-id="<?= $wId ?>">
                  <?php if ($wImg): ?>
                    <img src="<?= html_escape($wImg) ?>" alt="<?= html_escape($wName) ?>"
                         class="rounded-circle object-fit-cover" width="20" height="20">
                  <?php else: ?>
                    <span class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;font-size:10px;"><?= html_escape($ini) ?></span>
                  <?php endif; ?>
                  <span class="small text-truncate" style="max-width:120px;" title="<?= html_escape($wName) ?>">
                    <?= t_s($wName) ?>
                  </span>
                  <?php if ($canFollow): ?>
                    <span type="button" class="ms-1 remove-follower" data-user-id="<?= $wId ?>" title="Remove">
                      <i class="ti ti-x"></i>
                    </span>
                  <?php endif; ?>
                </span>
              <?php endforeach; ?>

              <?php if ($extraFollowers > 0): ?>
                <span class="badge text-bg-secondary">+<?= (int)$extraFollowers ?></span>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <?php if ($canFollow): ?>
            <!-- Support-style search + add -->
            <form method="post"
                  action="<?= site_url('tasks/add_follower/' . (int)$taskId) ?>"
                  class="app-form position-relative"
                  id="addFollowerForm">
              <input type="hidden" name="user_id" id="followerUserId">

              <div class="input-group">
                <input type="text" class="form-control" id="followerSearch" placeholder="Type a name to search..." autocomplete="off">
                <button class="btn btn-primary btn-sm" type="submit" id="followerAddBtn" disabled>
                  Add
                </button>
              </div>

              <!-- Results dropdown -->
              <div id="followerResults"
                   class="list-group shadow position-absolute w-100"
                   style="z-index: 1040; max-height: 240px; overflow:auto; display:none; top: 100%; left: 0;"></div>
            </form>
          <?php endif; ?>
        </div>
      </div> 
    </div>
    </div>
 </div>
</div>
</div><!-- /left -->

    <!-- RIGHT: Discussion + Followers -->
    <div class="col-12 col-lg-6">
      
      <?php
        // Discussion partial
     //   $CI->load->view('tasks/partials/taks_members', [  ]);
      ?>
      

      <?php
        // Discussion partial
        $CI->load->view('tasks/partials/discussion', [
          'task'       => $task,
          'taskId'     => (int)($taskId ?? ($task['id'] ?? 0)),
          'comments'   => $comments ?? [],
          'orderDesc'  => $orderDesc ?? (function_exists('get_setting') ? (get_setting('tasks_comments_order') === 'descending') : false),
          'policy'     => $policy ?? (property_exists($CI, 'tasks_policy') ? $CI->tasks_policy : (property_exists($CI, 'policy') ? $CI->policy : null)),
          'uid'        => (int)($uid ?? ($CI->session->userdata('user_id') ?? 0)),
          'assigneeId' => (int)($task['assignee_id'] ?? 0),
          'followers'  => $followers ?? ($task['followers'] ?? []),
        ]);
      ?>
      
    </div><!-- /right -->
  </div><!-- /row -->
</div><!-- /container -->

<?php
  // Details / Recurrence / Relations modal (separate partial)
  $CI->load->view('tasks/partials/details_modal', [
    'task' => $task,
  ]);
?>

<script>
(function() {
  const card     = document.getElementById('followersCard');
  if (!card) return;

  const taskId   = card.getAttribute('data-task-id');
  const chips    = document.getElementById('followersChips');
  const form     = document.getElementById('addFollowerForm');
  const input    = document.getElementById('followerSearch');
  const results  = document.getElementById('followerResults');
  const userId   = document.getElementById('followerUserId');
  const addBtn   = document.getElementById('followerAddBtn');

  const CANDIDATES = <?php echo json_encode($candidates ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

  async function postForm(url, data) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data || {})
      });
      const ct = res.headers.get('content-type') || '';
      if (ct.includes('application/json')) return await res.json();
      return { success: res.ok };
    } catch (e) {
      console.error(e);
      return { success: false, message: e.message || 'Network error' };
    }
  }

  // Remove Follower
  chips?.addEventListener('click', async e => {
    const btn = e.target.closest('.remove-follower');
    if (!btn || !taskId) return;
    const uid = btn.dataset.userId;
    if (!uid) return;

    const resp = await postForm('<?= site_url('tasks/remove_follower/') ?>' + taskId, { user_id: uid });
    if (resp?.success) {
      const chip = chips.querySelector(`.follower-chip[data-user-id="${uid}"]`);
      chip && chip.remove();
    } else {
      alert(resp.message || 'Failed to remove follower');
    }
  });

  // Search + Add Follower
  if (form && input && results && userId && addBtn) {
    function normalize(s){ return (s || '').toString().toLowerCase().trim(); }
    function currentFollowerIds() {
      return Array.from(chips.querySelectorAll('.follower-chip[data-user-id]'))
                  .map(el => Number(el.dataset.userId));
    }

    function search(q) {
      const terms = normalize(q).split(/\s+/).filter(Boolean);
      if (!terms.length) return [];
      const out = [];
      const existing = new Set(currentFollowerIds());
      for (const c of CANDIDATES) {
        const idN = Number(c.id);
        if (existing.has(idN)) continue;
        const hay = normalize(c.name);
        if (terms.every(t => hay.includes(t))) {
          out.push(c);
          if (out.length >= 8) break;
        }
      }
      return out;
    }

    function clearResults() { results.innerHTML = ''; results.style.display = 'none'; }

    input.addEventListener('input', () => {
      userId.value = '';
      addBtn.disabled = true;
      const q = input.value;
      if (normalize(q).length < 2) return clearResults();
      const found = search(q);
      if (!found.length) return clearResults();

      results.innerHTML = found.map(it => {
        const initials = (it.name || 'U').split(' ').map(p=>p[0]||'').join('').toUpperCase().slice(0,2);
        const av = it.avatar
          ? `<img src="${it.avatar}" alt="" width="20" height="20" class="rounded-circle me-2" style="object-fit:cover;">`
          : `<span class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center me-2" style="width:20px;height:20px;font-size:10px;">${initials}</span>`;
        return `<button type="button" class="list-group-item list-group-item-action d-flex align-items-center" data-id="${it.id}" data-name="${it.name}">${av}<span class="text-truncate">${it.name}</span></button>`;
      }).join('');
      results.style.display = 'block';
    });

    results.addEventListener('click', e => {
      const el = e.target.closest('.list-group-item');
      if (!el) return;
      userId.value = el.dataset.id;
      input.value  = el.dataset.name;
      addBtn.disabled = false;
      clearResults();
    });

    document.addEventListener('click', e => {
      if (!(results.contains(e.target) || input.contains(e.target))) clearResults();
    });

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const uid = userId.value;
      if (!uid) { input.focus(); return; }

      const resp = await postForm('<?= site_url('tasks/add_follower/') ?>' + taskId, { user_id: uid });
      if (resp?.success) {
        const name = input.value || ('User #' + uid);
        const initials = (name||'U').split(' ').map(p=>p[0]||'').join('').toUpperCase().slice(0,2);
        const chip = document.createElement('span');
        chip.className = 'd-inline-flex align-items-center border rounded-pill px-2 py-1 gap-2 follower-chip';
        chip.dataset.userId = uid;
        chip.innerHTML = `
          <span class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center" style="width:20px;height:20px;font-size:10px;">${initials}</span>
          <span class="small text-truncate" style="max-width:120px;" title="${name}">${name}</span>
          <span class="ms-1 remove-follower" data-user-id="${uid}" title="Remove"><i class="ti ti-x"></i></span>`;
        chips?.appendChild(chip);

        input.value = '';
        userId.value = '';
        addBtn.disabled = true;
        clearResults();
      } else {
        alert(resp.message || 'Failed to add follower.');
      }
    });
  }
})();
</script>

<script>
(function () {
  const form    = document.getElementById('commentForm');
  const editor  = document.getElementById('editorTaskComment');
  const fldHtml = document.getElementById('commentHtmlField');
  const fldText = document.getElementById('commentPlainField');

  if (!form || !editor || !fldHtml || !fldText) return;

  const tb = form.querySelector('.editor-toolbar');
  if (tb) {
    tb.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-cmd]');
      if (!btn) return;
      e.preventDefault();
      const cmd = btn.getAttribute('data-cmd');
      if (!cmd || cmd === 'foreColor') return;
      document.execCommand(cmd, false, null);
      editor.focus();
    });

    tb.querySelectorAll('[data-rte="fontFamily"], [data-rte="fontSize"]').forEach(sel => {
      sel.addEventListener('change', () => {
        const isFamily = sel.getAttribute('data-rte') === 'fontFamily';
        if (isFamily) document.execCommand('fontName', false, sel.value || 'inherit');
        else document.execCommand('fontSize', false, sel.value || '3');
        editor.focus();
      });
    });

    const linkBtn = tb.querySelector('[data-rte="linkBtn"]');
    if (linkBtn) {
      linkBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const url = prompt('Enter URL (https://...)');
        if (url) document.execCommand('createLink', false, url);
        editor.focus();
      });
    }

    tb.addEventListener('click', (e) => {
      const sw = e.target.closest('.color-btn');
      if (!sw) return;
      e.preventDefault();
      const color = sw.getAttribute('data-color');
      if (color) document.execCommand('foreColor', false, color);
      editor.focus();
    });
  }

  function isEffectivelyEmpty(html) {
    const stripped = (html || '')
      .replace(/<br\s*\/?>/gi, '')
      .replace(/<p>\s*<\/p>/gi, '')
      .replace(/&nbsp;/gi, ' ')
      .replace(/<[^>]*>/g, '')
      .trim();
    const hasMedia = /<(img|audio|video|iframe|embed|object)\b/i.test(html || '');
    return stripped === '' && !hasMedia;
  }

  function showInlineError(msg) {
    let box = form.querySelector('.comment-error-inline');
    if (!box) {
      box = document.createElement('div');
      box.className = 'comment-error-inline alert alert-danger py-1 px-2 small mt-2';
      form.appendChild(box);
    }
    box.textContent = msg;
  }

  form.addEventListener('submit', function (e) {
    const html = (editor.innerHTML || '').trim();
    const text = (editor.textContent || '').replace(/\u00a0/g, ' ').trim();

    if (isEffectivelyEmpty(html)) {
      e.preventDefault();
      showInlineError('Content body is required.');
      editor.focus();
      return;
    }

    fldHtml.value = html;
    fldText.value = text;
  });
})();
</script>
