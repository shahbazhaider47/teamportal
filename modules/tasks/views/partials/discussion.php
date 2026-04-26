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

/* ---------- Normalize inputs ---------- */
$CI      = get_instance();
$task    = is_array($task ?? null) ? $task : [];
$taskId  = (int)($taskId ?? ($task['id'] ?? 0));
$uid     = (int)($uid ?? ($CI->session->userdata('user_id') ?? 0));
$policy  = $policy ?? (property_exists($CI, 'tasks_policy') ? $CI->tasks_policy : (property_exists($CI, 'policy') ? $CI->policy : null));

$assigneeId = (int)($assigneeId ?? ($task['assignee_id'] ?? 0));

/* followers normalized to [int] */
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

$canEdit    = $policy ? $policy->can_edit($task, $uid) : false;
$canComment = $isAssignee || $isFollower || $canEdit;

$orderDesc  = (bool)($orderDesc ?? (function_exists('get_setting') ? (get_setting('tasks_comments_order') === 'descending') : false));

$messages   = is_array($comments ?? null) ? $comments : [];
$msgCount   = count($messages);

/* replies_by_comment: map[comment_id] => [rows...] (preloaded by controller) */
$repliesMap = is_array($replies_by_comment ?? null) ? $replies_by_comment : [];

/* ---------- Tiny avatar for replies (inside bubble) ---------- */
if (!function_exists('tiny_avatar_in_bubble')) {
  function tiny_avatar_in_bubble(string $name, ?string $url): string {
    $name = trim($name);
    $initials = t_initials($name);
    $url = trim((string)$url);
    if ($url !== '') {
      return '<img src="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'" alt="" class="reply-avatar-in">';
    }
    return '<span class="reply-fallback-in">'.htmlspecialchars($initials,ENT_QUOTES,'UTF-8').'</span>';
  }
}
?>

<style>
  /* Comment body */
  .comment-content{font-size:.78rem;line-height:1.45;color:#374151;min-height:30px}

  /* Reply bubble block */
  .reply-item{margin:6px 0}
  .reply-bubble{
    display:flex;align-items:flex-start;gap:10px;width:100%;
    background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:8px 10px;
  }
  .reply-avatar-in{
    width:22px;height:22px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;flex:0 0 22px;background:#f8fafc;display:inline-block;
  }
  .reply-fallback-in{
    width:22px;height:22px;border-radius:50%;border:1px solid #e5e7eb;background:#f8fafc;
    display:inline-flex;align-items:center;justify-content:center;font-size:10px;flex:0 0 22px;
  }
  .reply-body{flex:1 1 auto}
  .reply-text{font-size:.78rem;line-height:1.4;color:#374151;word-break:break-word}
  .reply-meta{font-size:.65rem;line-height:1.1;color:#9ca3af;margin-top:4px}

  /* Small avatar for comment author (existing style) */
  .avatar{width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb}
  .avatar-fallback{width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#f3f4f6;border:1px solid #e5e7eb;font-weight:600;font-size:.72rem}
</style>

<div class="card mb-3">
  <div class="card-header py-2 bg-light-primary">
    <div class="d-flex justify-content-between align-items-center">
      <h2 class="h6 header-title mb-0 small"><span class="text-muted text-primary">Task Discussion</span></h2>
      <div class="small text-muted text-primary d-flex align-items-center gap-2">
        <i class="ti ti-messages"></i>
        <?= (int)$msgCount ?> comment<?= $msgCount !== 1 ? 's' : '' ?>
        <span class="pill pill-info capital small ms-2"><?= $orderDesc ? 'Newest first' : 'Oldest first' ?></span>
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
          $cid        = (int)($c['id'] ?? 0);
          $authorName = trim($c['author_name'] ?? ('User #'.(int)($c['user_id'] ?? 0)));
          $authorImg  = (string)($c['author_avatar'] ?? '');
          $createdAt  = (string)($c['dateadded'] ?? '');
          $initials   = t_initials($authorName);
          $bodyHtml   = !empty($c['comment_html']) ? $c['comment_html'] : nl2br(html_escape((string)($c['comment'] ?? '')));

          $commentUserId       = (int)($c['user_id'] ?? 0);
          $isCommenterAssignee = ($assigneeId && $commentUserId === $assigneeId);
          $isCommenterFollower = in_array($commentUserId, $followers, true);

          $roleBadgeLabel = $isCommenterAssignee ? 'Assignee' : ($isCommenterFollower ? 'Task Follower' : 'Team Member');
          $roleBadgeClass = $isCommenterAssignee ? 'primary' : ($isCommenterFollower ? 'info' : 'secondary');

          /* Prefer preloaded replies; fall back to replies_count from SQL */
          $replies      = isset($repliesMap[$cid]) && is_array($repliesMap[$cid]) ? $repliesMap[$cid] : [];
          $preCount     = isset($c['replies_count']) ? (int)$c['replies_count'] : null;
          $repliesCount = count($replies);
          $uiCount      = $repliesCount > 0 ? $repliesCount : (int)($preCount ?? 0);

          /* Autoload if DB says there are replies and we didn’t preload them */
          $autoload = ($repliesCount === 0 && $uiCount > 0) ? '1' : ($repliesCount > 0 ? '1' : '0');
        ?>
          <div class="timeline-item">
            <div class="chat-item small">
              <div class="chat-bubble">
                <div class="d-flex align-items-center gap-2 chat-meta mb-2">
                  <?php if ($authorImg !== ''): ?>
                    <img src="<?= htmlspecialchars($authorImg, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" style="width:28px;height:28px;font-size:11px;" class="avatar">
                  <?php else: ?>
                    <div class="avatar-fallback"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                  <?php endif; ?>
                <div class="small">
                  <strong class="text-primary"><?= t_s($authorName) ?></strong>
                </div>                  
                  <span class="badge bg-light-<?= $roleBadgeClass ?> align-middle"><?= $roleBadgeLabel ?></span>
                  <span class="text-muted ms-auto small"><?= format_datetime($createdAt) ?></span>

                <?php
                  $isOwnComment   = ((int)($c['user_id'] ?? 0) === (int)$uid);
                  $canglobal  = staff_can('view_global', 'tasks');  
                ?>
                <?php if ($isOwnComment || $canglobal): ?>
                  <form method="post"
                        action="<?= site_url('tasks/delete_comment/'.$cid) ?>"
                        onsubmit="return confirm('Delete this comment?');">
                    <button class="btn btn-light-primary btn-ssm" type="submit" title="Delete comment">
                      <i class="ti ti-trash"></i>
                    </button>
                  </form>
                <?php endif; ?>

                <?php
                render_todo_ai_button($c['comment'] ?? ('Task #' . $taskId), [
                    'rel_type'  => 'task',
                    'rel_id'    => (int) $taskId,
                ]);
                ?>

                </div>

                <div class="chat-body comment-content"><?= $bodyHtml ?></div>

                <!-- Replies (collapsible) -->
                <div class="collapse" 
                     id="replies-collapse-<?= $cid ?>" 
                     data-comment-id="<?= $cid ?>" 
                     data-task-id="<?= (int)$taskId ?>">
                
                  <div class="mt-2 ms-4"
                       id="replies-wrap-<?= $cid ?>"
                       data-loaded="<?= $repliesCount>0 ? '1' : '0' ?>"
                       data-autoload="<?= $autoload ?>"
                       style="<?= $uiCount>0 ? '' : 'display:none;' ?>">
                
                    <?php if ($repliesCount>0): ?>
                      <div class="replies-list" id="replies-list-<?= $cid ?>">
                        <?php foreach ($replies as $r):
                          $rName = trim($r['author_name'] ?? ('User #'.(int)($r['user_id'] ?? 0)));
                          $rImg  = (string)($r['author_avatar'] ?? '');
                          $rAt   = (string)($r['dateadded'] ?? '');
                          $rTxt  = (string)($r['reply'] ?? '');
                        ?>
                          <div class="reply-item">
                            <div class="reply-bubble">
                              <?= tiny_avatar_in_bubble($rName, $rImg) ?>
                              <div class="reply-body">
                                <div class="reply-text"><?= nl2br(t_s($rTxt)) ?></div>
                                <?php if ($rAt): ?><div class="reply-meta"><?= format_datetime($rAt) ?></div><?php endif; ?>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <div class="replies-list" id="replies-list-<?= $cid ?>"></div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <!-- Inline reply form (kept outside collapse so it's always visible when button toggled) -->
                <div class="mt-2 ms-4" id="reply-form-<?= $cid ?>" style="display:none;">
                  <form class="reply-inline-form app-form" data-task-id="<?= (int)$taskId ?>" data-comment-id="<?= $cid ?>">
                    <div class="input-group input-group-sm">
                      <input type="text" class="form-control" name="reply" rows="2" placeholder="Add a new reply..." maxlength="2000">
                      <!-- <button class="btn btn-primary btn-ssm" type="submit">Add Reply</button> -->
                    </div>
                    <p class="small text-muted text-end p-1">Enter to Send</p>
                  </form>
                </div>
                
                <!-- Row actions -->
                <div class="d-flex align-items-center gap-2 chat-meta mt-3">
                  <button class="btn btn-white btn-ssm" type="button" title="Like" disabled>
                    <i>👍</i>
                  </button>
                
                  <!-- Reply toggle with live count -->
                  <button class="btn btn-white btn-ssm btn-reply-toggle <?= $uiCount ? '' : 'collapsed' ?>"
                          type="button"
                          data-bs-toggle="collapse"
                          data-bs-target="#replies-collapse-<?= $cid ?>"
                          aria-expanded="<?= $uiCount ? 'true' : 'false' ?>"
                          aria-controls="replies-collapse-<?= $cid ?>"
                          data-comment-id="<?= $cid ?>"
                          data-task-id="<?= (int)$taskId ?>"
                          title="Reply">
                    <i class="ti ti-arrow-back-up"></i> Replies
                    <span class="ms-1 badge bg-light-secondary" id="btn-replies-count-<?= $cid ?>"><?= (int)$uiCount ?></span>
                  </button>
                </div>

           
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
 <?php if ($canComment && $taskId): ?>
  <!-- Composer -->
<!-- Composer (plain textarea only) -->

  <div class="card-header"><strong>Add Comment</strong></div>
  <div class="card-body">
    <form method="post"
          action="<?= site_url('tasks/post_comment/'.$taskId) ?>"
          id="commentForm"
          class="app-form">

      <!-- what the server expects -->
      <input type="hidden" name="comment_html" id="commentHtmlField" value="">
      <!-- we will also post comment as plain text via the textarea name -->
      
      <div class="mb-3">
        <textarea id="commentBody"
                  name="comment"
                  class="form-control"
                  placeholder="Write your comment…"
                  rows="4"
                  maxlength="5000"
                  required></textarea>
        <div id="commentErr" class="invalid-feedback">Please enter a comment.</div>
      </div>

      <div class="d-flex justify-content-end align-items-center gap-2">
        <?php if ($isFollower && !$isAssignee && !$canEdit): ?>
          <span class="text-muted small me-auto">You're a follower — you can comment, but can't edit this task.</span>
        <?php endif; ?>
        <button class="btn btn-primary btn-sm" type="submit" id="commentSubmitBtn">
          <i class="ti ti-message-plus"></i> Post Comment
        </button>
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
    if (!form) return;
    e.preventDefault();

    const tb = form.querySelector('.editor-toolbar');
    if (tb) {
      tb.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cmd]'); if (!btn) return;
        e.preventDefault(); const cmd = btn.getAttribute('data-cmd');
        if (!cmd || cmd === 'foreColor') return;
        document.execCommand(cmd, false, null); editor.focus();
      });

      tb.querySelectorAll('[data-rte="fontFamily"], [data-rte="fontSize"]').forEach(sel => {
        sel.addEventListener('change', () => {
          const isFamily = sel.getAttribute('data-rte') === 'fontFamily';
          if (isFamily) document.execCommand('fontName', false, sel.value || 'inherit');
          else document.execCommand('fontSize', false, sel.value || '3'); editor.focus();
        });
      });

      const linkBtn = tb.querySelector('[data-rte="linkBtn"]');
      if (linkBtn) linkBtn.addEventListener('click', (e) => {
        e.preventDefault(); const url = prompt('Enter URL (https://...)');
        if (url) document.execCommand('createLink', false, url); editor.focus();
      });

      tb.addEventListener('click', (e) => {
        const sw = e.target.closest('.color-btn'); if (!sw) return;
        e.preventDefault(); const color = sw.getAttribute('data-color');
        if (color) document.execCommand('foreColor', false, color); editor.focus();
      });
    }

    function isEffectivelyEmpty(html) {
      const stripped = (html || '').replace(/<br\s*\/?>/gi,'').replace(/<p>\\s*<\\/p>/gi,'')
        .replace(/&nbsp;/gi,' ').replace(/<[^>]*>/g,'').trim();
      const hasMedia = /<(img|audio|video|iframe|embed|object)\\b/i.test(html||'');
      return stripped === '' && !hasMedia;
    }
    function showInlineError(msg) {
      let box = form.querySelector('.comment-error-inline');
      if (!box) { box = document.createElement('div'); box.className='comment-error-inline alert alert-danger py-1 px-2 small mt-2'; form.appendChild(box); }
      box.textContent = msg;
    }
    form.addEventListener('submit', function (e) {
      const html = (editor.innerHTML || '').trim();
      const text = (editor.textContent || '').replace(/\\u00a0/g,' ').trim();
      if (isEffectivelyEmpty(html)) { e.preventDefault(); showInlineError('Content body is required.'); editor.focus(); return; }
      fldHtml.value = html; fldText.value = text;
    });
  })();
  </script>
<?php else: ?>
  <p class="text-muted small">You don't have permission to comment on this task.</p>
<?php endif; ?>

<script>
(function () {
  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

  async function getJson(url){
    try{
      const res = await fetch(url, { headers:{ 'X-Requested-With':'XMLHttpRequest' }});
      const ct = (res.headers.get('content-type')||'').toLowerCase();
      if (ct.includes('application/json')) return await res.json();
      // Non-JSON: treat 2xx as soft-success with no rows
      if (res.ok) return { success:true, rows:[] };
      return { success:false };
    }catch(e){ return { success:false }; }
  }

  async function postForm(url, data){
    data = data || {};
    try{
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
      });
      const ct = (res.headers.get('content-type')||'').toLowerCase();
      if (ct.includes('application/json')) {
        const j = await res.json();
        // If backend returns row inside {row: {...}}, keep as-is
        return j;
      }
      // Fallback: treat any 2xx as success to avoid false negatives
      return { success: res.ok };
    }catch(e){
      return { success:false };
    }
  }

  function initials(name){
    name = (name||'').trim(); if (!name) return 'U';
    const parts = name.split(/\\s+/);
    return ((parts[0]?.[0]||'') + (parts[parts.length-1]?.[0]||'')).toUpperCase();
  }
  function avatarInBubbleHtml(name,url){
    url=(url||'').trim();
    if(url){ return `<img src="${escapeHtml(url)}" alt="" class="reply-avatar-in">`; }
    return `<span class="reply-fallback-in">${escapeHtml(initials(name))}</span>`;
  }

  // Renderer for AJAX-loaded reply rows (bubble with avatar inside; no name; tiny date)
  function renderReplyRow(r){
    const name = (r.author_name || ('User #'+(r.user_id||''))).trim();
    const text = escapeHtml(r.reply || '').replace(/\\n/g,'<br>');
    const when = r.dateadded ? `<div class="reply-meta">${escapeHtml(r.dateadded)}</div>` : '';
    const av   = avatarInBubbleHtml(name, (r.author_avatar || '').trim());
    return `
      <div class="reply-item">
        <div class="reply-bubble">
          ${av}
          <div class="reply-body">
            <div class="reply-text">${text}</div>
            ${when}
          </div>
        </div>
      </div>
    `;
  }
  window.renderReplyRow = renderReplyRow;

  async function ensureRepliesLoaded(taskId, commentId){
    const wrap       = document.getElementById('replies-wrap-'+commentId);
    const list       = document.getElementById('replies-list-'+commentId);
    const btnBadge   = document.getElementById('btn-replies-count-'+commentId);
    if (!wrap || !list) return;

    // If already loaded, just show container
    if (wrap.getAttribute('data-loaded') === '1') {
      wrap.style.display = list.children.length > 0 ? '' : 'none';
      return;
    }

    const url  = '<?= site_url('tasks/comment') ?>/' + commentId + '/replies/' + taskId;
    const json = await getJson(url);

    if (json && json.success && Array.isArray(json.rows) && json.rows.length > 0) {
      list.innerHTML = json.rows.map(renderReplyRow).join('');
      wrap.setAttribute('data-loaded','1');
      wrap.style.display = '';
      if (btnBadge) btnBadge.textContent = String(json.rows.length);
    } else {
      // Keep it hidden if nothing
      wrap.style.display = 'none';
      // Don't set data-loaded so we can re-attempt if needed
    }
  }

  function toggleReplyUI(taskId, commentId) {
    const wrap     = document.getElementById('replies-wrap-'+commentId);
    const formWrap = document.getElementById('reply-form-'+commentId);
    if (!wrap || !formWrap) return;

    const willShow = formWrap.style.display === 'none';

    // Always load/show replies when opening the UI
    if (willShow) {
      formWrap.style.display = '';
      ensureRepliesLoaded(taskId, commentId);
      const input = formWrap.querySelector('input[name="reply"]'); if (input) input.focus();
    } else {
      // Hide only the form; keep replies as-is
      formWrap.style.display = 'none';
    }
  }

  // Autoload replies where the server signaled there are some but none were pre-rendered
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[id^="replies-wrap-"][data-autoload="1"]').forEach(wrap => {
      const commentId = wrap.id.replace('replies-wrap-','');
      const btn = document.querySelector('.btn-reply-toggle[data-comment-id="'+commentId+'"]');
      const taskId = btn ? btn.getAttribute('data-task-id') : '<?= (int)$taskId ?>';
      if (commentId && taskId) ensureRepliesLoaded(taskId, commentId);
    });
  });

  document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-reply-toggle'); if (!btn) return;
    const commentId = btn.getAttribute('data-comment-id');
    const taskId    = btn.getAttribute('data-task-id');
    if (!commentId || !taskId) return;
    toggleReplyUI(taskId, commentId);
  });

  document.addEventListener('click', function(e){
    const cancelBtn = e.target.closest('button[data-action="cancel-reply"]'); if (!cancelBtn) return;
    const form = cancelBtn.closest('.reply-inline-form'); if (!form) return;
    const commentId = form.getAttribute('data-comment-id');
    const formWrap  = document.getElementById('reply-form-'+commentId);
    if (formWrap) formWrap.style.display='none';
  });

  document.addEventListener('submit', async function(e){
    const form = e.target.closest('.reply-inline-form'); if (!form) return;
    e.preventDefault();

    const taskId    = form.getAttribute('data-task-id');
    const commentId = form.getAttribute('data-comment-id');
    const input     = form.querySelector('input[name="reply"]');
    const val       = (input && input.value || '').trim();
    if (!taskId || !commentId) return;
    if (!val) { if (input) input.focus(); return; }

    const url  = '<?= site_url('tasks/comment') ?>/' + commentId + '/replies/add/' + taskId;
    const resp = await postForm(url, { reply: val });

    // Treat non-JSON 2xx as success; resp.success may be undefined in that case => coerce
    const ok = !!(resp && (resp.success === undefined ? true : resp.success));
    if (ok) {
      const list     = document.getElementById('replies-list-'+commentId);
      const wrap     = document.getElementById('replies-wrap-'+commentId);
      const btnBadge = document.getElementById('btn-replies-count-'+commentId);

      if (list && wrap) {
        // show container and consider it loaded now
        wrap.style.display = '';
        if (wrap.getAttribute('data-loaded') !== '1') {
          wrap.setAttribute('data-loaded','1');
        }

        const rowHtml = renderReplyRow(resp.row || {
          user_id: <?= (int)$uid ?>,
          reply: val,
          dateadded: (new Date()).toISOString().slice(0,19).replace('T',' '),
          author_name: 'You',
          author_avatar: ''
        });
        list.insertAdjacentHTML('beforeend', rowHtml);

        // Increment live count
        if (btnBadge) {
          const n = parseInt(String(btnBadge.textContent || '0'), 10);
          btnBadge.textContent = String(isNaN(n) ? 1 : n + 1);
        }
      }

      if (input) input.value = '';
      const formWrap = document.getElementById('reply-form-'+commentId);
      if (formWrap) formWrap.style.display='none';
    } else {
      alert((resp && resp.message) ? resp.message : 'Failed to add reply.');
    }
  });
})();
</script>