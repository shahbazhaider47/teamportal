<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
  /** @var array $ticket */
  $ticket       = $ticket ?? [];
  $posts        = $ticket['posts'] ?? [];
  $active_users = $active_users ?? []; // pass from controller for assignee dropdown

  $canAssign    = function_exists('staff_can') ? staff_can('assign','support') : false;
  $canDelete    = function_exists('staff_can') ? staff_can('delete','support') : false;
  $orderDesc    = function_exists('get_setting') ? (get_setting('support_replies_order') === 'descending') : false;

  // >>> CI instance for session/models in the view
  $CI  = get_instance();
  $uid = (int)($CI->session->userdata('user_id') ?? 0);

  // session user roles (for watcher permissions labels only)
  $is_requester = !empty($ticket['requester_id']) && (int)$ticket['requester_id'] === $uid;
  $is_assignee  = isset($is_assignee) ? (bool)$is_assignee : (!empty($ticket['assignee_id']) && (int)$ticket['assignee_id'] === $uid);

  // watcher permissions: requester / assignee / both (for label text)
  $watcherWho   = function_exists('get_setting') ? (get_setting('support_user_added_watchers', 'both') ?: 'both') : 'both';
  $canManageWatchers = ($watcherWho === 'both')
                    || ($watcherWho === 'assignee'  && $is_assignee)
                    || ($watcherWho === 'requester' && $is_requester);

  // staff limited to dept (informational badge)
  $limitedToDept = function_exists('get_setting') && get_setting('support_staff_limited_to_dept', 'no') === 'yes';

  // Max attachments + accept extensions
  $maxFilesSetting = (int)((function_exists('get_setting') ? get_setting('support_max_attachments') : 5) ?? 5);
  if ($maxFilesSetting <= 0) { $maxFilesSetting = 0; } // 0 = disabled
  $allowedCsv      = (string)((function_exists('get_setting') ? get_setting('support_allowed_mime_types') : 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip') ?? '');
  $allowedExtArr   = array_values(array_filter(array_map('trim', explode(',', $allowedCsv))));
  $acceptAttr      = '';
  if (!empty($allowedExtArr)) {
    $acceptList = array_map(static function($x) { $x = ltrim(strtolower($x), '.'); return '.' . $x; }, $allowedExtArr);
    $acceptAttr = implode(',', $acceptList);
  }

  // --- local safe printer to avoid "Array to string conversion"
  if (!function_exists('s')) {
    function s($v, $sep = ', ') {
      if (is_array($v)) { return ''; } // never dump arrays
      if (is_object($v)) { return ''; }
      return html_escape((string)$v);
    }
  }
  if (!function_exists('dn')) {
    function dn($v) {
      if (is_array($v)) {
        $full = trim((string)($v['fullname'] ?? ''));
        if ($full !== '') return $full;
        $fn = trim((string)($v['firstname'] ?? ''));
        $ln = trim((string)($v['lastname']  ?? ''));
        if ($fn !== '' || $ln !== '') return trim($fn.' '.$ln);
        if (!empty($v['name'])) return (string)$v['name'];
        return 'User';
      }
      if (is_object($v)) {
        if (!empty($v->fullname)) return (string)$v->fullname;
        $fn = trim((string)($v->firstname ?? ''));
        $ln = trim((string)($v->lastname  ?? ''));
        if ($fn !== '' || $ln !== '') return trim($fn.' '.$ln);
        if (!empty($v->name)) return (string)$v->name;
        return 'User';
      }
      $s = trim((string)$v);
      return $s === '' ? 'User' : $s;
    }
  }

  // convenience: code/id
  $ticketCode = $ticket['code'] ?? (isset($ticket['id']) ? '#'.$ticket['id'] : '#');

  // Status color mapping
  $statusColors = [
    'open' => 'success',
    'in_progress' => 'primary',
    'waiting_user' => 'warning',
    'on_hold' => 'secondary',
    'resolved' => 'info',
    'closed' => 'success'
  ];
  // Priority color mapping
  $priorityColors = [
    'low' => 'secondary',
    'normal' => 'primary',
    'high' => 'warning',
    'urgent' => 'danger'
  ];
  $currentStatus   = $ticket['status']   ?? 'open';
  $currentPriority = $ticket['priority'] ?? 'normal';

  // Split posts: left side shows only replies (messages); right-side "My Notes" shows only current user's notes
  $posts = is_array($posts) ? $posts : [];
  $messages = [];
  $myNotes  = [];
  foreach ($posts as $p) {
    $type = isset($p['type']) ? strtolower((string)$p['type']) : 'message';
    if ($type === 'note') {
      if ((int)($p['author_id'] ?? 0) === $uid) { $myNotes[] = $p; }
    } else {
      $messages[] = $p;
    }
  }
  if ($orderDesc) {
    $messages = array_reverse($messages);
    $myNotes  = array_reverse($myNotes);
  }
  $msgCount = count($messages);
?>

<style>
  /* Chat-style bubbles inside the timeline */
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
  .chat-attachments .attachment-pill {
    border:1px solid var(--bs-border-color);
    border-radius:20px; padding:.15rem .5rem; text-decoration:none;
    display:inline-flex; align-items:center; gap:.35rem;
  }
</style>

<div class="container-fluid support-module">

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title">
        <a href="<?= base_url('support') ?>"><i class="ti ti-arrow-back-up"></i> <?= $title ?> </a>
        <i class="ti ti-chevron-right"></i>
        <span class="text-muted small"><?= s($ticketCode) ?></span>
      </h1>

      <?php if ($limitedToDept): ?>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="You can only view tickets for departments you’re assigned to.">
          Dept-limited
        </span>
      <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">

      <?php if (is_superadmin() || ($is_assignee && !empty($ticket['id']))): ?>
        <a class="btn btn-danger btn-header" href="<?= base_url('support/delete/'.(int)$ticket['id']) ?>"
           onclick="return confirm('Are you sure you want to delete this ticket? This action cannot be undone.')">
          <i class="ti ti-trash"></i> Delete Ticket
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3">
    <!-- Timeline -->
    <div class="col-12 col-lg-7">

      <?php
        $ticket    = $ticket ?? [];
        $postCount = $msgCount; // only replies counted
      ?>

      <div class="card mb-3">
        <!-- Header -->
        <div class="card-header py-2 bg-light-primary">
          <div class="d-flex justify-content-between align-items-center">
            <h1 class="h6 header-title mb-0 small">
              <span class="text-muted text-primary"><?= s($ticket['subject'] ?? '') ?></span>
            </h1>
            <div class="small text-muted text-primary d-flex align-items-center capital gap-1">
              <i class="ti ti-messages"></i>
              <?= (int)$postCount ?> message<?= $postCount !== 1 ? 's' : '' ?>
            </div>
          </div>
        </div>

        <!-- Body -->
        <div class="card-body">
          <div class="d-flex align-items-center gap-3 flex-wrap">Ticket Conversation
            <?php if ($orderDesc): ?>
              <span class="pill pill-info capital small" title="Replies are shown newest first based on settings.">
                Newest first
              </span>
            <?php else: ?>
              <span class="pill pill-info capital small" title="Replies are shown oldest first based on settings.">
                Oldest first
              </span>
            <?php endif; ?>
          </div>

          <div class="app-divider-v dashed"></div>

          <?php if ($postCount === 0): ?>
            <div class="text-center py-5 text-muted">
              <i class="ti ti-message-off display-4 d-block mb-2"></i>
              <p class="m-0">No ticket messages yet.</p>
            </div>
          <?php else: ?>
            <div class="card-body">
              <div class="timeline">
                <?php foreach ($messages as $p):
                  // Author data
                  $authorName  = !empty($p['author_name']) ? $p['author_name']
                               : (!empty($p['author']) ? $p['author']
                               : ('User #' . (int)($p['author_id'] ?? 0)));
                  $authorImg   = !empty($p['author_avatar']) ? $p['author_avatar']
                               : (!empty($p['author_profile_image']) ? $p['author_profile_image']
                               : (!empty($p['profile_image']) ? $p['profile_image']
                               : (!empty($p['author_image']) ? $p['author_image'] : '')));
                  $createdAt   = $p['created_at'] ?? '';

                  // Initials
                  $initials = '';
                  if (is_string($authorName) && $authorName !== '') {
                    $parts = preg_split('/\s+/', trim($authorName));
                    $first = mb_substr($parts[0] ?? '', 0, 1);
                    $last  = mb_substr($parts[count($parts)-1] ?? '', 0, 1);
                    $initials = mb_strtoupper($first . $last);
                  }
                ?>
                  <div class="timeline-item">
                    <div class="chat-item small">
                      <!-- Avatar -->
                      <div class="chat-avatar">
                        <?php if (!empty($authorImg)): ?>
                          <img src="<?= htmlspecialchars($authorImg, ENT_QUOTES, 'UTF-8') ?>"
                               alt="Avatar" class="avatar">
                        <?php else: ?>
                          <div class="avatar-fallback"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                      </div>

                      <!-- Bubble -->
                      <div class="chat-bubble">
                        <div class="d-flex align-items-center gap-2 chat-meta mb-1">
                          <strong class="me-1"><?= s($authorName) ?></strong>
                          <span class="badge text-bg-primary align-middle">Message</span>
                          <span class="text-muted ms-auto"><?= s($createdAt) ?></span>
                        </div>

                        <div class="chat-body">
                          <?= isset($p['body']) ? $p['body'] : '' /* body sanitized server-side */ ?>
                        </div>

                        <?php if (!empty($p['attachments']) && is_array($p['attachments'])): ?>
                          <div class="chat-attachments mt-2">
                            <div class="small text-muted mb-1">
                              <i class="ti ti-paperclip"></i> Attachments:
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                              <?php foreach ($p['attachments'] as $a):
                                $aPath = is_array($a) ? ($a['path'] ?? '#') : '#';
                                $aName = is_array($a) ? ($a['name'] ?? 'file') : 'file';
                                $aSize = is_array($a) ? ($a['size'] ?? null) : null;
                                $ext   = strtolower(pathinfo((string)$aName, PATHINFO_EXTENSION));
                                $icon  = 'paperclip';
                                if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) $icon = 'photo';
                                elseif (in_array($ext, ['pdf'], true)) $icon = 'file-type-pdf';
                                elseif (in_array($ext, ['doc','docx'], true)) $icon = 'file-type-doc';
                                elseif (in_array($ext, ['xls','xlsx','csv'], true)) $icon = 'file-type-xls';
                              ?>
                                <a class="attachment-pill"
                                   href="<?= htmlspecialchars($aPath, ENT_QUOTES, 'UTF-8') ?>"
                                   target="_blank" rel="noopener">
                                  <i class="ti ti-<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
                                  <span class="attachment-name"><?= s($aName) ?></span>
                                  <?php if (!empty($aSize)): ?>
                                    <span class="attachment-size text-muted">(<?= (int)$aSize ?>)</span>
                                  <?php endif; ?>
                                </a>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        <?php endif; ?>
                      </div><!-- /bubble -->
                    </div><!-- /chat-item -->
                  </div><!-- /timeline-item -->
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

<!-- Reply Composer (rich text; no type dropdown) -->
<?php if ($is_assignee || $is_requester && !empty($ticket['id'])): ?>
<div class="card">
  <div class="card-header">
    <strong>Add Reply</strong>
  </div>
  <div class="card-body">
    <form method="post"
          action="<?= base_url('support/post/'.(int)$ticket['id']) ?>"
          enctype="multipart/form-data"
          id="replyForm"
          class="app-form">

      <!-- Hidden body field that will receive the editor HTML -->
      <input type="hidden" name="body" id="bodyFieldReply">

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Message</label>

          <!-- Rich Text Editor (scoped for Reply) -->
          <div class="rich-text-editor border rounded" id="rteReply">
            <!-- Toolbar -->
            <div class="editor-toolbar d-flex flex-wrap align-items-center gap-1 p-2 border-bottom bg-light-primary small">
              <!-- Font Family -->
              <select class="form-select form-select-sm small" style="width: 180px;" data-rte="fontFamily">
                <option value="">Font</option>
                <option value="Arial, sans-serif">Arial</option>
                <option value="'Helvetica Neue', Helvetica, sans-serif">Helvetica</option>
                <option value="'Times New Roman', Times, serif">Times New Roman</option>
                <option value="'Courier New', Courier, monospace">Courier New</option>
                <option value="Georgia, serif">Georgia</option>
                <option value="Verdana, sans-serif">Verdana</option>
              </select>

              <!-- Font Size -->
              <select class="form-select form-select-sm" style="width: 100px;" data-rte="fontSize">
                <option value="">Size</option>
                <option value="1">Small</option>
                <option value="3" selected>Normal</option>
                <option value="5">Large</option>
                <option value="7">X-Large</option>
              </select>

              <!-- Text Formatting -->
              <div class="btn-group" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="bold" title="Bold">
                  <i class="ti ti-bold"></i>
                </button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="italic" title="Italic">
                  <i class="ti ti-italic"></i>
                </button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="underline" title="Underline">
                  <i class="ti ti-underline"></i>
                </button>
              </div>

              <!-- Text Alignment -->
              <div class="btn-group ms-1" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyLeft" title="Align Left">
                  <i class="ti ti-align-left"></i>
                </button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyCenter" title="Align Center">
                  <i class="ti ti-align-center"></i>
                </button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyRight" title="Align Right">
                  <i class="ti ti-align-right"></i>
                </button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyFull" title="Justify">
                  <i class="ti ti-align-justified"></i>
                </button>
              </div>

              <!-- Lists -->
              <div class="btn-group ms-1" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="insertUnorderedList" title="Bulleted list">
                  <i class="ti ti-list"></i>
                </button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="insertOrderedList" title="Numbered list">
                  <i class="ti ti-list-numbers"></i>
                </button>
              </div>

              <!-- Colors -->
              <div class="btn-group ms-1" role="group">
                <div class="dropdown">
                  <button class="btn btn-ssm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Text Color">
                    <i class="ti ti-palette"></i>
                  </button>
                  <div class="dropdown-menu p-2">
                    <div class="d-flex flex-wrap gap-1" style="width: 150px;">
                      <?php
                        $colors = [
                          '#000000','#434343','#666666','#999999','#b7b7b7','#cccccc','#d9d9d9','#efefef','#f3f3f3','#ffffff',
                          '#980000','#ff0000','#ff9900','#ffff00','#00ff00','#00ffff','#4a86e8','#0000ff','#9900ff','#ff00ff',
                          '#e6b8af','#f4cccc','#fce5cd','#fff2cc','#d9ead3','#d0e0e3','#c9daf8','#cfe2f3','#d9d2e9','#ead1dc'
                        ];
                        foreach ($colors as $c): ?>
                        <button type="button"
                                class="color-btn border rounded"
                                style="width:20px;height:20px;background-color:<?= $c ?>;"
                                data-color="<?= $c ?>"
                                data-cmd="foreColor"
                                title="<?= $c ?>"></button>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Links -->
              <div class="btn-group ms-1" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-rte="linkBtn" title="Insert link">
                  <i class="ti ti-link"></i>
                </button>
              </div>

              <!-- Clear Formatting -->
              <div class="btn-group ms-1" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="removeFormat" title="Clear formatting">
                  <i class="ti ti-eraser"></i>
                </button>
              </div>
            </div>

            <!-- Editable area -->
            <div id="editorReply"
                 class="editor-content form-control border-0"
                 style="min-height:220px;max-height:400px;overflow-y:auto;padding:12px;"
                 contenteditable="true"
                 placeholder="Write your message…">
              <p><br></p>
            </div>
          </div>
        </div>

        <!-- Attachments -->
        <div class="col-12">
          <label class="form-label">Attachments</label>
          <input type="file"
                 name="attachments[]"
                 class="form-control"
                 id="attachmentsReply"
                 multiple
                 <?= $acceptAttr ? 'accept="'.html_escape($acceptAttr).'"' : '' ?>>
          <div class="form-text">
            <?php if ($maxFilesSetting > 0): ?>
              Max files: <?= (int)$maxFilesSetting ?>.
            <?php else: ?>
              File uploads are disabled by settings.
            <?php endif; ?>
            Allowed: <?= html_escape($allowedCsv ?: 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,zip') ?>
          </div>
          <div id="filePreviewReply" class="mt-2"></div>
        </div>

        <div class="col-12 d-flex justify-content-between align-items-center">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="closeOnReply" name="close_on_reply" value="1">
            <label class="form-check-label" for="closeOnReply">Close ticket after replying</label>
          </div>
          <button class="btn btn-primary btn-sm" type="submit" id="replySubmitBtn">
            <i class="ti ti-send"></i> Send Response
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<p class="text-muted small">Only requester and assignee can add reply.</p>
<?php endif; ?>  

    </div><!-- /col-lg-8 -->

    <!-- Meta / Actions / My Notes / Watchers -->
    <div class="col-12 col-lg-5">

<?php
  // Fallbacks if not set (defensive)
  $canAssign = isset($canAssign)
    ? (bool)$canAssign
    : (function_exists('staff_can') ? staff_can('assign', 'support') : true);

  // Quick panel visible if either view_global or assign
  $canSeeQuickPanel = function_exists('staff_can')
    ? (staff_can('view_global', 'support') || staff_can('assign', 'support'))
    : true;
?>

<!-- Quick Status & Assign -->
<?php if ($canSeeQuickPanel && !empty($ticket['id'])): ?>
  <div class="card mb-3">
    <div class="card-body p-2">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="small text-muted px-2">Status:</span>
        <form method="post" action="<?= base_url('support/status/'.(int)$ticket['id']) ?>" class="d-inline app-form">
          <select name="status" class="form-select form-control-sm form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
            <?php
              $statuses = ['open','in_progress','waiting_user','on_hold','resolved','closed'];
              $cur = $ticket['status'] ?? '';
              foreach ($statuses as $s):
            ?>
              <option value="<?= $s ?>" <?= $cur === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </form>

        <?php if ($is_assignee || $canAssign): ?>
          <form method="post" action="<?= base_url('support/assign/'.(int)$ticket['id']) ?>" class="d-inline app-form">
            <span class="small text-muted px-2">Assign To:</span>
            <select name="assignee_id" class="form-select form-select-sm form-control-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
              <option value="">Unassigned</option>
              <?php if (!empty($active_users) && is_array($active_users)): ?>
                <?php foreach ($active_users as $u):
                  if (isset($u['is_active']) && (int)$u['is_active'] === 0) continue;
                  $uidX   = (int)($u['id'] ?? 0);
                  $fname = trim(($u['fullname'] ?? '') ?: trim(($u['firstname'] ?? '').' '.($u['lastname'] ?? '')));
                  if ($fname === '') $fname = 'User #'.$uidX;
                ?>
                  <option value="<?= $uidX ?>" <?= (isset($ticket['assignee_id']) && (int)$ticket['assignee_id'] === $uidX) ? 'selected' : '' ?>>
                    <?= html_escape($fname) ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- My Notes (list first, then reveal-on-click form; only current user's notes) -->
<?php if ($is_assignee || $is_requester && !empty($ticket['id'])): ?>
<div class="card mb-3">
  <div class="card-header py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
    <strong>My Notes</strong>
    <span class="small text-muted">Only you can see these</span>
  </div>

  <div class="card-body">
    <!-- Notes list (no author/name/avatar/badge; just date + body in a small note box) -->
    <?php if (empty($myNotes)): ?>
      <div class="text-muted small mb-2">No notes yet.</div>
    <?php else: ?>
      <div class="d-flex flex-column gap-2 mb-2">
        <?php foreach ($myNotes as $n):
          $createdAt = $n['created_at'] ?? '';
        ?>
          <div class="chat-bubble small">
            <div class="text-muted mb-1 small"><?= s($createdAt) ?></div>
            <div class="small">
              <?= isset($n['body']) ? $n['body'] : '' /* sanitized server-side */ ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

      <!-- Add Note button -->
      <div class="d-flex justify-content-end">
        <button type="button" id="addNoteBtn" class="btn btn-header btn-outline-primary btn-sm">
          <i class="ti ti-notes"></i> Add Note
        </button>
      </div>

      <!-- Reveal-on-click: Add Note form (hidden by default) -->
      <div id="addNoteWrap" class="mt-2" style="display:none;">
        <form method="post" action="<?= base_url('support/post/'.(int)$ticket['id']) ?>" class="app-form mb-0">
          <input type="hidden" name="type" value="note">
          <label class="form-label mb-1">Add a note</label>
          <textarea name="body" class="form-control" rows="3" placeholder="Write a private note..." required></textarea>
          <div class="d-flex justify-content-end gap-2 mt-2">
            <button type="button" id="cancelNoteBtn" class="btn btn-light-primary btn-header">Cancel</button>
            <button class="btn btn-header btn-primary btn-sm" type="submit">
              <i class="ti ti-check"></i> Save Note
            </button>
          </div>
        </form>
      </div>
  </div>
</div>
<?php endif; ?>
    
<!-- Ticket Details (compact) -->
<?php
  // Safe name strings
  $rqName = trim((string)($ticket['requester_name'] ?? (isset($ticket['requester_id']) ? '#'.(int)$ticket['requester_id'] : '')));
  $asName = trim((string)($ticket['assignee_name']  ?? (isset($ticket['assignee_id'])  ? '#'.(int)$ticket['assignee_id']  : '-')));

  // Avatars (controller already enriches *_avatar)
  $rqImg  = !empty($ticket['requester_avatar']) ? (string)$ticket['requester_avatar'] : '';
  $asImg  = !empty($ticket['assignee_avatar'])  ? (string)$ticket['assignee_avatar']  : '';

  // Initials helper
  $mkIni = function($name) {
      $name = trim((string)$name);
      if ($name === '') return 'U';
      $parts = preg_split('/\s+/', $name);
      $first = mb_substr($parts[0] ?? '', 0, 1);
      $last  = mb_substr($parts[count($parts)-1] ?? '', 0, 1);
      return mb_strtoupper($first.$last);
  };

  // Overdue flags (guard if helper does not exist)
  $firstDue = $ticket['first_response_due_at'] ?? null;
  $resDue   = $ticket['resolution_due_at']     ?? null;
  $isOverA  = function_exists('is_overdue') ? is_overdue($firstDue) : false;
  $isOverB  = function_exists('is_overdue') ? is_overdue($resDue)   : false;
?>
<div class="card mb-3">
  <div class="card-header py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
    <div class="d-flex align-items-center gap-2">
      <strong class="me-2">Ticket Details</strong>
    </div>
  </div>

  <div class="card-body small">
    <div class="row g-3 row-cols-1 row-cols-md-2">
      <!-- Ticket Status -->
      <div>
        <div class="text-muted">Status</div>
        <div>
      <span class="small badge bg-<?= $statusColors[$currentStatus] ?? 'secondary' ?>">
        <?= ucfirst(str_replace('_', ' ', $currentStatus)) ?>
      </span>            
        </div>
      </div>

      <!-- Ticket Priority -->
      <div>
        <div class="text-muted">Priority</div>
        <div>
        <span class="small badge bg-<?= $priorityColors[$currentPriority] ?? 'secondary' ?>"><?= ucfirst($currentPriority) ?></span>            
        </div>
      </div>

      <!-- Requester -->
      <div>
        <div class="text-muted">Requester</div>
        <div class="d-flex align-items-center">
          <?php if ($rqImg): ?>
            <img src="<?= html_escape($rqImg) ?>" alt="Requester" class="rounded-circle object-fit-cover me-2" width="24" height="24">
          <?php else: ?>
            <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center me-2" style="width:24px;height:24px;font-size:11px;">
              <?= html_escape($mkIni($rqName)) ?>
            </div>
          <?php endif; ?>
          <span><?= s($rqName) ?></span>
        </div>
      </div>

      <!-- Assignee -->
      <div>
        <div class="text-muted">Assignee</div>
        <div class="d-flex align-items-center">
          <?php if ($asImg): ?>
            <img src="<?= html_escape($asImg) ?>" alt="Assignee" class="rounded-circle object-fit-cover me-2" width="24" height="24">
          <?php else: ?>
            <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center me-2" style="width:24px;height:24px;font-size:11px;">
              <?= html_escape($mkIni($asName)) ?>
            </div>
          <?php endif; ?>
          <span><?= s($asName) ?></span>
        </div>
      </div>
      
      <!-- Ticket Code -->
      <div>
        <div class="text-muted">Ticket Code</div>
        <div><?= s($ticket['code'] ?? '') ?></div>
      </div>
      
      <!-- Department -->
      <div>
        <div class="text-muted">Department</div>
        <div><?= s($ticket['department_name'] ?? (isset($ticket['department_id']) ? '#'.(int)$ticket['department_id'] : '-')) ?></div>
      </div>

      <!-- Priority (duplicate badge for quick scan on small screens) -->
      <div class="d-md-none">
        <div class="text-muted">Priority</div>
        <div><span class="badge bg-<?= $priorityColors[$currentPriority] ?? 'secondary' ?>"><?= ucfirst($currentPriority) ?></span></div>
      </div>

      <!-- First Response Due -->
      <div>
        <div class="text-muted">First Response Due</div>
        <div class="<?= $isOverA ? 'text-danger' : '' ?>">
          <?= s($firstDue ?? '-') ?>
          <?php if ($isOverA): ?><span class="ms-1 badge bg-danger">Overdue</span><?php endif; ?>
        </div>
      </div>

      <!-- Resolution Due -->
      <div>
        <div class="text-muted">Resolution Due</div>
        <div class="<?= $isOverB ? 'text-danger' : '' ?>">
          <?= s($resDue ?? '-') ?>
          <?php if ($isOverB): ?><span class="ms-1 badge bg-danger">Overdue</span><?php endif; ?>
        </div>
      </div>

          <div>
            <div class="text-muted">Created</div>
            <div><?= s($ticket['created_at'] ?? '-') ?></div>
          </div>
          <div>
            <div class="text-muted">Updated</div>
            <div><?= s($ticket['updated_at'] ?? '-') ?></div>
          </div>
          <div>
            <div class="text-muted">Last Activity</div>
            <div><?= s($ticket['last_activity_at'] ?? '-') ?></div>
          </div>
          <div>
            <div class="text-muted">Closed At</div>
            <div><?= s($ticket['closed_at'] ?? '-') ?></div>
          </div>


      <!-- Tags (full width if present) -->
      <?php if (!empty($ticket['tags']) && is_array($ticket['tags'])): ?>
      <div class="col-12 mt-3">
        <div class="text-muted">Tags</div>
        <div class="d-flex flex-wrap gap-1">
          <?php foreach ($ticket['tags'] as $tag): ?>
            <span class="badge bg-light-primary capital"><?= s($tag) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?> 
      
    </div>

<?php
  // Watchers chips (limit display to avoid wrapping)
  $watchers = is_array($ticket['watchers_resolved'] ?? null) ? $ticket['watchers_resolved'] : [];
  $maxChips = 6; // show up to 6, then +N
  $shown    = array_slice($watchers, 0, $maxChips);
  $extra    = max(0, count($watchers) - count($shown));
?>
<?php if (!empty($watchers)): ?>
  <div class="col-12">
    <div class="app-divider-v secondary justify-content-center mt-4 mb-2">
        <span class="badge text-bg-primary">Ticket Watcher</span>
    </div>
    <div class="d-flex align-items-center flex-wrap gap-2">
      <?php foreach ($shown as $w): ?>
        <?php
          $wName = trim((string)($w['name'] ?? 'User'));
          $wImg  = (string)($w['avatar'] ?? '');
        ?>
        <span class="d-inline-flex align-items-center border rounded-pill px-2 py-1 gap-2">
          <?php if ($wImg): ?>
            <img src="<?= html_escape($wImg) ?>" alt="<?= html_escape($wName) ?>"
                 class="rounded-circle object-fit-cover" width="20" height="20">
          <?php else: ?>
            <span class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center"
                  style="width:20px;height:20px;font-size:10px;">
              <?= html_escape($mkIni($wName)) ?>
            </span>
          <?php endif; ?>
          <span class="small text-truncate" style="max-width:120px;" title="<?= html_escape($wName) ?>">
            <?= s($wName) ?>
          </span>
        </span>
      <?php endforeach; ?>

      <?php if ($extra > 0): ?>
        <span class="badge text-bg-secondary">+<?= (int)$extra ?></span>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
  </div><!-- /card-body -->
</div><!-- /card -->

<?php
  // Build candidates for watcher add (exclude requester/assignee/current watchers)
  $watchersRaw = is_array($ticket['watchers'] ?? null) ? $ticket['watchers'] : [];
  $excludeIds = [];
  if (!empty($ticket['requester_id'])) $excludeIds[(int)$ticket['requester_id']] = true;
  if (!empty($ticket['assignee_id']))  $excludeIds[(int)$ticket['assignee_id']]  = true;
  foreach ($watchersRaw as $wid) { $excludeIds[(int)$wid] = true; }

  $candidates = [];
  if (!empty($active_users) && is_array($active_users)) {
    foreach ($active_users as $u) {
      if (isset($u['is_active']) && (int)$u['is_active'] === 0) continue;
      $uidX = (int)($u['id'] ?? 0);
      if ($uidX <= 0 || isset($excludeIds[$uidX])) continue;
      $name = trim(($u['fullname'] ?? '') ?: trim(($u['firstname'] ?? '').' '.($u['lastname'] ?? '')));
      if ($name === '') $name = 'User #'.$uidX;
      $avatar = '';
      if (!empty($u['profile_image'])) {
        $avatar = base_url('uploads/users/profile/'.$u['profile_image']);
      }
      $candidates[] = ['id'=>$uidX, 'name'=>$name, 'avatar'=>$avatar];
    }
  }
?>

<?php if ($canManageWatchers && !empty($active_users)): ?>
  <div class="card mb-3">
    <div class="card-header py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
      <strong>Update Watchers</strong>
      <span class="small text-muted">
        <?php if ($watcherWho === 'assignee'): ?>Only assignee can add watchers
        <?php elseif ($watcherWho === 'requester'): ?>Only requester can add watchers
        <?php else: ?>Requester &amp; assignee can manage<?php endif; ?>
      </span>
    </div>
    <div class="card-body app-form">
      <?php
        $watcherMap  = !empty($watchersRaw) ? $CI->users->get_map_by_ids($watchersRaw) : [];
      ?>
      <ul class="list-unstyled mb-2 small">
        <?php if (empty($watcherMap)): ?>
          <li class="text-muted small">No watchers.</li>
        <?php else: foreach ($watcherMap as $uidW => $u): ?>
          <li class="d-flex align-items-center justify-content-between mb-1">
            <span><?= html_escape($u['name']) ?></span>
            <form method="post" action="<?= base_url('support/remove_watcher/'.(int)$ticket['id']) ?>" class="m-0">
              <input type="hidden" name="user_id" value="<?= (int)$uidW ?>">
              <button class="btn btn-ssm btn-outline-danger">Remove</button>
            </form>
          </li>
        <?php endforeach; endif; ?>
      </ul>
        <!-- Searchable add -->
        <form method="post"
              action="<?= base_url('support/add_watcher/' . (int)$ticket['id']) ?>"
              class="app-form position-relative"
              id="addWatcherForm">
        
          <input type="hidden" name="user_id" id="watcherUserId">
        
          <div class="mb-2 position-relative">
            <label class="form-label mb-1 d-flex align-items-center gap-2">
              <span>Add Watcher</span>
              <i class="ti ti-info-circle text-muted"
                 tabindex="0"
                 data-bs-toggle="tooltip"
                 data-bs-placement="right"
                 title="Only users not already requester, assignee, or watchers can be added."></i>
            </label>
        
            <!-- Input + button inline -->
            <div class="input-group">
              <input type="text"
                     class="form-control"
                     id="watcherSearch"
                     placeholder="Type a name to search..."
                     autocomplete="off">
              <button class="btn btn-primary btn-sm"
                      type="submit"
                      id="watcherAddBtn"
                      disabled>
                Add
              </button>
            </div>
        
            <!-- Results dropdown (positioned under input-group) -->
            <div id="watcherResults"
                 class="list-group shadow position-absolute w-100"
                 style="z-index: 1040; max-height: 240px; overflow:auto; display:none; top: 100%; left: 0;"></div>
          </div>
        </form>
    </div>
  </div>

<?php elseif (!empty($active_users)): ?>
  <!-- Read-only watchers list -->
  <div class="card mb-3">
    <div class="card-header py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
      <strong>Watchers</strong>
      <span class="small text-muted">
        <?php if ($watcherWho === 'assignee'): ?>Only assignee can add watchers
        <?php elseif ($watcherWho === 'requester'): ?>Only requester can add watchers
        <?php else: ?>Requester &amp; assignee can manage<?php endif; ?>
      </span>
    </div>
    <div class="card-body app-form">
      <?php
        $watcherMap  = !empty($watchersRaw) ? $CI->users->get_map_by_ids($watchersRaw) : [];
      ?>
      <ul class="list-unstyled mb-2 small">
        <?php if (empty($watcherMap)): ?>
          <li class="text-muted small">No watchers.</li>
        <?php else: foreach ($watcherMap as $uidW => $u): ?>
          <li class="d-flex align-items-center justify-content-between mb-1">
            <span><?= html_escape($u['name']) ?></span>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

      <!-- Related Tickets -->
      <?php if (!empty($related_tickets) && is_array($related_tickets)): ?>
      <div class="card mb-3">
        <div class="card-header"><strong>Related Tickets</strong></div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php foreach ($related_tickets as $rt): ?>
              <a href="<?= base_url('support/view/'.(int)$rt['id']) ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1"><?= s($rt['subject'] ?? '') ?></h6>
                  <small class="text-muted"><?= s($rt['status'] ?? '') ?></small>
                </div>
                <p class="mb-1 small text-muted"><?= s($rt['code'] ?? '') ?></p>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Activity Log -->
      <?php if (!empty($activity_log) && is_array($activity_log)): ?>
      <div class="card">
        <div class="card-header"><strong>Recent Activity</strong></div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php foreach (array_slice($activity_log, 0, 5) as $activity): ?>
              <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                  <p class="mb-1 small"><?= s($activity['description'] ?? '') ?></p>
                  <small class="text-muted"><?= s($activity['created_at'] ?? '') ?></small>
                </div>
                <small class="text-muted">By <?= s(dn($activity['user_name'] ?? 'System')) ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div><!-- /col-lg-4 -->
  </div><!-- /row -->
</div><!-- /container -->

<script>
(function () {
  const wrap      = document.getElementById('rteReply');
  if (!wrap) return;

  const editor    = document.getElementById('editorReply');
  const bodyField = document.getElementById('bodyFieldReply');
  const form      = document.getElementById('replyForm');

  // Toolbar actions (scoped inside wrap)
  wrap.querySelectorAll('[data-cmd]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const cmd = btn.getAttribute('data-cmd');
      const val = btn.getAttribute('data-color') || null;
      if (cmd === 'foreColor' && val) {
        document.execCommand(cmd, false, val);
      } else {
        document.execCommand(cmd, false, null);
      }
      editor && editor.focus();
    });
  });

  // Font family & size
  const fontFamily = wrap.querySelector('[data-rte="fontFamily"]');
  const fontSize   = wrap.querySelector('[data-rte="fontSize"]');
  const linkBtn    = wrap.querySelector('[data-rte="linkBtn"]');

  fontFamily && fontFamily.addEventListener('change', function () {
    if (this.value) document.execCommand('fontName', false, this.value);
    editor && editor.focus();
  });
  fontSize && fontSize.addEventListener('change', function () {
    if (this.value) document.execCommand('fontSize', false, this.value);
    editor && editor.focus();
  });
  linkBtn && linkBtn.addEventListener('click', () => {
    const url = prompt('Enter URL (https://...)', 'https://');
    if (url) document.execCommand('createLink', false, url);
    editor && editor.focus();
  });

  // Submit: dump HTML into hidden field
  form && form.addEventListener('submit', (e) => {
    const html = (editor && editor.innerHTML.trim()) || '';
    if (!html || html === '<p><br></p>') {
      e.preventDefault();
      alert('Message body is required.');
      return false;
    }
    bodyField && (bodyField.value = html);
  });

  // Attachments preview (IDs are distinct for reply)
  const fileInput   = document.getElementById('attachmentsReply');
  const filePreview = document.getElementById('filePreviewReply');
  const maxFiles    = <?= (int)$maxFilesSetting ?>; // 0 = disabled

  function formatFileSize(bytes){
    if (!bytes) return '0 B';
    const k=1024, sizes=['B','KB','MB','GB'];
    const i=Math.floor(Math.log(bytes)/Math.log(k));
    return (bytes/Math.pow(k,i)).toFixed(2)+' '+sizes[i];
  }

  if (fileInput && filePreview) {
    if (maxFiles === 0) fileInput.disabled = true;
    fileInput.addEventListener('change', function() {
      filePreview.innerHTML = '';
      if (!this.files.length) return;
      const wrap = document.createElement('div');
      wrap.className = 'd-flex flex-column gap-1';
      Array.from(this.files).forEach(f => {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center justify-content-between small';
        const n = document.createElement('span'); n.textContent = f.name;
        const sz = document.createElement('span'); sz.className='text-muted'; sz.textContent = formatFileSize(f.size);
        row.appendChild(n); row.appendChild(sz); wrap.appendChild(row);
      });
      filePreview.appendChild(wrap);
    });

    form && form.addEventListener('submit', function(e){
      if (maxFiles > 0 && fileInput.files.length > maxFiles) {
        e.preventDefault();
        alert('You can upload a maximum of ' + maxFiles + ' file(s) for this ticket.');
      }
      if (maxFiles === 0 && fileInput.files.length > 0) {
        e.preventDefault();
        alert('File uploads are disabled by settings for this module.');
      }
    });
  }
})();
</script>

<script>
  (function () {
    const addBtn = document.getElementById('addNoteBtn');
    const wrap   = document.getElementById('addNoteWrap');
    const cancel = document.getElementById('cancelNoteBtn');

    if (addBtn && wrap) {
      addBtn.addEventListener('click', function () {
        addBtn.style.display = 'none';
        wrap.style.display = 'block';
        const ta = wrap.querySelector('textarea[name="body"]');
        if (ta) ta.focus();
      });
    }
    if (cancel && addBtn && wrap) {
      cancel.addEventListener('click', function () {
        wrap.style.display = 'none';
        addBtn.style.display = 'inline-block';
        const ta = wrap.querySelector('textarea[name="body"]');
        if (ta) ta.value = '';
      });
    }
  })();
</script>

<script>
  (function () {
    // Candidates passed from PHP (already excludes requester/assignee/current watchers)
    const CANDIDATES = <?php echo json_encode($candidates ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    const $input   = document.getElementById('watcherSearch');
    const $results = document.getElementById('watcherResults');
    const $userId  = document.getElementById('watcherUserId');
    const $addBtn  = document.getElementById('watcherAddBtn');
    const $form    = document.getElementById('addWatcherForm');

    function normalize(s){ return (s || '').toString().toLowerCase().trim(); }

    function search(q) {
      const terms = normalize(q).split(/\s+/).filter(Boolean);
      if (terms.length === 0) return [];
      const out = [];
      for (const c of CANDIDATES) {
        const hay = normalize(c.name);
        let ok = true;
        for (const t of terms) { if (!hay.includes(t)) { ok = false; break; } }
        if (ok) { out.push(c); if (out.length >= 8) break; }
      }
      return out;
    }

    function clearResults() {
      $results && ($results.innerHTML = '');
      $results && ($results.style.display = 'none');
    }

    $input && $input.addEventListener('input', function () {
      $userId && ($userId.value = '');
      $addBtn && ($addBtn.disabled = true);
      const q = this.value;
      if (normalize(q).length < 2) { clearResults(); return; }
      const items = search(q);
      if (!$results) return;
      if (!items.length) { clearResults(); return; }
      $results.innerHTML = items.map(it => {
        const av = it.avatar ? `<img src="${it.avatar}" alt="" width="20" height="20" class="rounded-circle me-2" style="object-fit:cover;">` : `<span class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center me-2" style="width:20px;height:20px;font-size:10px;">${(it.name||'U').split(' ').map(p=>p[0]).join('').toUpperCase().slice(0,2)}</span>`;
        return `<button type="button" class="list-group-item list-group-item-action d-flex align-items-center" data-id="${it.id}" data-name="${it.name}">${av}<span class="text-truncate">${it.name}</span></button>`;
      }).join('');
      $results.style.display = 'block';
    });

    $results && $results.addEventListener('click', function (e) {
      const el = e.target.closest('.list-group-item');
      if (!el) return;
      const id   = el.getAttribute('data-id');
      const name = el.getAttribute('data-name');
      if (id && $userId) {
        $userId.value = id;
        if ($input) $input.value  = name;
        if ($addBtn) $addBtn.disabled = false;
        clearResults();
      }
    });

    document.addEventListener('click', function (e) {
      if ($results && !($results.contains(e.target) || ($input && $input.contains(e.target)))) {
        clearResults();
      }
    });

    // Prevent submit without picked id
    $form && $form.addEventListener('submit', function (e) {
      if (!$userId || !$userId.value) {
        e.preventDefault();
        $input && $input.focus();
      }
    });
  })();
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // File preview for attachments + max files enforcement per settings
  const fileInput   = document.getElementById('attachments');
  const filePreview = document.getElementById('filePreview');
  const form        = document.getElementById('replyForm');
  const submitBtn   = document.getElementById('replySubmitBtn');
  const maxFiles    = <?= (int)$maxFilesSetting ?>; // 0 means disabled

  if (fileInput && filePreview) {
    if (maxFiles === 0) {
      fileInput.disabled = true;
    }

    fileInput.addEventListener('change', function() {
      filePreview.innerHTML = '';
      if (this.files.length > 0) {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex flex-column gap-1';
        Array.from(this.files).forEach(f => {
          const row = document.createElement('div');
          row.className = 'd-flex align-items-center justify-content-between small';
          const n = document.createElement('span');
          n.textContent = f.name;
          const sz = document.createElement('span');
          sz.className = 'text-muted';
          sz.textContent = formatFileSize(f.size);
          row.appendChild(n); row.appendChild(sz); wrap.appendChild(row);
        });
        filePreview.appendChild(wrap);
      }
    });
  }

  // Enforce max files on submit
  if (form && fileInput) {
    form.addEventListener('submit', function(e) {
      if (maxFiles > 0 && fileInput.files.length > maxFiles) {
        e.preventDefault();
        alert('You can upload a maximum of ' + maxFiles + ' file(s) for this ticket.');
        return false;
      }
      if (maxFiles === 0 && fileInput.files.length > 0) {
        e.preventDefault();
        alert('File uploads are disabled by settings for this module.');
        return false;
      }
    });
  }

  // Auto-resize textarea
  const textarea = document.getElementById('messageBody');
  if (textarea) {
    textarea.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = (this.scrollHeight) + 'px';
    });
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024, sizes = ['Bytes','KB','MB','GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
});
</script>
<script>
// Bootstrap tooltip init (once per page is fine)
document.addEventListener('DOMContentLoaded', function () {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.forEach(function (el) {
    new bootstrap.Tooltip(el);
  });
});
</script>