<?php defined('BASEPATH') or exit('No direct script access allowed');
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

// ---- Normalize inputs ----
$CI      = get_instance();
$task    = is_array($task ?? null) ? $task : [];
$taskId  = (int)($taskId ?? ($task['id'] ?? 0));
$uid     = (int)($uid ?? ($CI->session->userdata('user_id') ?? 0));
$policy  = $policy ?? (property_exists($CI, 'tasks_policy') ? $CI->tasks_policy : (property_exists($CI, 'policy') ? $CI->policy : null));

$assigneeId = (int)($assigneeId ?? ($task['assignee_id'] ?? 0));

// followers normalized to [int]
$followers_in = $followers ?? ($task['followers'] ?? []);
if (is_string($followers_in)) {
  $tmp = json_decode($followers_in, true);
  $followers = is_array($tmp) ? $tmp : array_map('intval', preg_split('/\s*,\s*/', $followers_in, -1, PREG_SPLIT_NO_EMPTY));
} elseif (is_array($followers_in)) {
  $followers = array_map('intval', $followers_in);
} else {
  $followers = [];
}
$followers  = array_values(array_unique(array_filter($followers)));
$isFollower = in_array($uid, $followers, true);
$isAssignee = ($assigneeId > 0 && $assigneeId === $uid);

$canEdit    = $policy ? $policy->can_edit($task, $uid)             : false;
$canComment = $isAssignee || $isFollower || $canEdit;

$orderDesc  = (bool)($orderDesc ?? (function_exists('get_setting') ? (get_setting('tasks_comments_order') === 'descending') : false));

$messages   = is_array($comments ?? null) ? $comments : [];
$msgCount   = count($messages);
?>

<style>
.comment-content {
    font-size: 0.75rem;
    line-height: 1.4;
    color: #4b5563;
    min-height: 30px;
  }    
</style>
<div class="card mb-3">
  <div class="card-header py-2 bg-light-primary">
    <div class="d-flex justify-content-between align-items-center">
      <h2 class="h6 header-title mb-0 small">
        <span class="text-muted text-primary">Task Discussion</span>
      </h2>
      <div class="small text-muted text-primary d-flex align-items-center gap-2">
        <i class="ti ti-messages"></i>
        <?= (int)$msgCount ?> comment<?= $msgCount !== 1 ? 's' : '' ?>
        <span class="pill pill-info capital small ms-2">
          <?= $orderDesc ? 'Newest first' : 'Oldest first' ?>
        </span>
      </div>
    </div>
  </div>

  <div class="card-body">
    <?php if ($msgCount === 0): ?>
      <div class="text-center py-5 text-muted">
        <i class="ti ti-message-off display-4 d-block mb-2"></i>
        <p class="m-0">No comments yet.</p>
      </div>
    <?php else: ?>
      <div class="timeline mt-2">
        <?php foreach ($messages as $c):
          $authorName = trim($c['author_name'] ?? ('User #'.(int)($c['user_id'] ?? 0)));
          $authorImg  = (string)($c['author_avatar'] ?? '');
          $createdAt  = (string)($c['dateadded'] ?? '');
          $initials   = t_initials($authorName);
          $bodyHtml   = !empty($c['comment_html'])
                        ? $c['comment_html']   // assumed sanitized server-side
                        : nl2br(html_escape((string)($c['comment'] ?? '')));

          $commentUserId       = (int)($c['user_id'] ?? 0);
          $isCommenterAssignee = ($assigneeId && $commentUserId === $assigneeId);
          $isCommenterFollower = in_array($commentUserId, $followers, true);

          $roleBadgeLabel = $isCommenterAssignee ? 'Task Owner' : ($isCommenterFollower ? 'Task Follower' : 'Comment');
          $roleBadgeClass = $isCommenterAssignee ? 'primary'   : ($isCommenterFollower ? 'secondary'     : 'primary');
        ?>
          <div class="timeline-item">
            <div class="chat-item small">
              <div class="chat-bubble">
                <div class="d-flex align-items-center gap-2 chat-meta mb-3">
                  <?php if ($authorImg !== ''): ?>
                    <img src="<?= htmlspecialchars($authorImg, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="avatar">
                  <?php else: ?>
                    <div class="avatar-fallback"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                  <?php endif; ?>

                  <strong class="me-1"><?= t_s($authorName) ?></strong>
                  <span class="badge bg-light-<?= $roleBadgeClass ?> align-middle"><?= $roleBadgeLabel ?></span>
                  <span class="text-muted ms-auto small"><?= t_s($createdAt) ?></span>

                  <?php if ($canEdit || $isAssignee || ($commentUserId === $uid)): ?>
                    <form method="post"
                          action="<?= site_url('tasks/delete_comment/'.(int)($c['id'] ?? 0)) ?>"
                          onsubmit="return confirm('Delete this comment?');">
                      <button class="btn btn-light-primary btn-ssm" type="submit" title="Edit placeholder (coming soon)">
                        <i class="ti ti-edit"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  
                </div>

                <div class="chat-body comment-content"><?= $bodyHtml ?></div>
                  <div class="d-flex align-items-center gap-2 chat-meta mt-3">
                  <?php if ($canEdit || $isAssignee || ($commentUserId === $uid)): ?>
                    <form method="post"
                          action="<?= site_url('tasks/delete_comment/'.(int)($c['id'] ?? 0)) ?>"
                          onsubmit="return confirm('Delete this comment?');">
                      <button class="btn btn-white btn-ssm" type="submit" title="Like">
                        <i>👍</i>
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if ($canEdit || $isAssignee || ($commentUserId === $uid)): ?>
                    <form method="post"
                          action="<?= site_url('tasks/delete_comment/'.(int)($c['id'] ?? 0)) ?>"
                          onsubmit="return confirm('Delete this comment?');">
                      <button class="btn btn-white btn-ssm" type="submit" title="Reply">
                        <i class="ti ti-arrow-back-up"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($canComment && $taskId): ?>
  <!-- Composer -->
  <div class="card">
    <div class="card-header"><strong>Add Comment</strong></div>
    <div class="card-body">
      <form method="post"
            action="<?= site_url('tasks/post_comment/'.$taskId) ?>"
            enctype="multipart/form-data"
            id="commentForm"
            class="app-form">
        <input type="hidden" name="comment_html" id="commentHtmlField">
        <input type="hidden" name="comment" id="commentPlainField">

        <div class="mb-3">
          <div class="rich-text-editor border rounded" id="rteTaskComment">
            <div class="editor-toolbar d-flex flex-wrap align-items-center gap-1 p-2 border-bottom bg-light-primary small">
              <select class="form-select form-select-sm" style="width: 180px;" data-rte="fontFamily">
                <option value="">Font</option>
                <option value="Arial, sans-serif">Arial</option>
                <option value="'Helvetica Neue', Helvetica, sans-serif">Helvetica</option>
                <option value="'Times New Roman', Times, serif">Times New Roman</option>
                <option value="'Courier New', Courier, monospace">Courier New</option>
                <option value="Georgia, serif">Georgia</option>
                <option value="Verdana, sans-serif">Verdana</option>
              </select>
              <select class="form-select form-select-sm" style="width: 100px;" data-rte="fontSize">
                <option value="">Size</option>
                <option value="1">Small</option>
                <option value="3" selected>Normal</option>
                <option value="5">Large</option>
                <option value="7">X-Large</option>
              </select>
              <div class="btn-group" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="bold"><i class="ti ti-bold"></i></button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="italic"><i class="ti ti-italic"></i></button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="underline"><i class="ti ti-underline"></i></button>
              </div>
              <div class="btn-group ms-1" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyLeft"><i class="ti ti-align-left"></i></button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyCenter"><i class="ti ti-align-center"></i></button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyRight"><i class="ti ti-align-right"></i></button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="justifyFull"><i class="ti ti-align-justified"></i></button>
              </div>
              <div class="btn-group ms-1" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="insertUnorderedList"><i class="ti ti-list"></i></button>
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="insertOrderedList"><i class="ti ti-list-numbers"></i></button>
              </div>
              <div class="btn-group ms-1" role="group">
                <div class="dropdown">
                  <button class="btn btn-ssm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="ti ti-palette"></i></button>
                  <div class="dropdown-menu p-2">
                    <div class="d-flex flex-wrap gap-1" style="width: 150px;">
                      <?php
                        $colors = ['#000000','#434343','#666666','#999999','#b7b7b7','#cccccc','#d9d9d9','#efefef','#f3f3f3','#ffffff','#980000','#ff0000','#ff9900','#ffff00','#00ff00','#00ffff','#4a86e8','#0000ff','#9900ff','#ff00ff','#e6b8af','#f4cccc','#fce5cd','#fff2cc','#d9ead3','#d0e0e3','#c9daf8','#cfe2f3','#d9d2e9','#ead1dc'];
                        foreach ($colors as $c): ?>
                          <button type="button" class="color-btn border rounded" style="width:20px;height:20px;background-color:<?= $c ?>;" data-color="<?= $c ?>" data-cmd="foreColor" title="<?= $c ?>"></button>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="btn-group ms-1" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-rte="linkBtn"><i class="ti ti-link"></i></button>
              </div>
              <div class="btn-group ms-1" role="group">
                <button class="btn btn-ssm btn-outline-secondary" type="button" data-cmd="removeFormat"><i class="ti ti-eraser"></i></button>
              </div>
            </div>
            <div id="editorTaskComment"
                 class="editor-content form-control border-0"
                 style="min-height:180px;max-height:380px;overflow-y:auto;padding:12px;"
                 contenteditable="true"
                 placeholder="Write your comment…">
              <p><br></p>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button class="btn btn-primary btn-sm" type="submit" id="commentSubmitBtn">
            <i class="ti ti-message-plus"></i> Post Comment
          </button>
          <?php if ($isFollower && !$isAssignee && !$canEdit): ?>
            <span class="text-muted small ms-2 align-self-center">You're a follower — you can comment, but can't edit this task.</span>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <script>
  (function () {
    const form    = document.getElementById('commentForm');
    const editor  = document.getElementById('editorTaskComment');
    const fldHtml = document.getElementById('commentHtmlField');
    const fldText = document.getElementById('commentPlainField');

    if (!form || !editor || !fldHtml || !fldText) return;

    // Toolbar actions
    const tb = form.querySelector('.editor-toolbar');
    if (tb) {
      tb.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cmd]');
        if (!btn) return;
        e.preventDefault();
        const cmd = btn.getAttribute('data-cmd');
        if (!cmd || cmd === 'foreColor') return; // color handled below
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
<?php else: ?>
  <p class="text-muted small">You don't have permission to comment on this task.</p>
<?php endif; ?>
