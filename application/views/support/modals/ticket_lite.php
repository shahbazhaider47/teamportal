<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
if (!function_exists('name_initials')) {
  function name_initials($name) {
    $name = trim((string)$name);
    if ($name === '') return 'U';
    preg_match_all('/\b\p{L}/u', $name, $m);
    $letters = array_slice($m[0] ?? [], 0, 2);
    return strtoupper(implode('', $letters) ?: mb_substr($name, 0, 1));
  }
}

$priority = strtolower($ticket['priority'] ?? 'normal');
$status   = strtolower($ticket['status']   ?? 'open');
$prioMap  = ['low'=>'primary','normal'=>'light-primary','high'=>'warning','urgent'=>'danger'];
$statMap  = ['open'=>'primary','in_progress'=>'light-primary','waiting_user'=>'warning','on_hold'=>'danger','resolved'=>'success','closed'=>'secondary'];

$reqName = $ticket['requester_name'] ?? (isset($ticket['requester_id']) ? ('User #'.(int)$ticket['requester_id']) : null);
$reqAv   = $ticket['requester_avatar'] ?? null;
$asgName = $ticket['assignee_name'] ?? (isset($ticket['assignee_id']) ? ('User #'.(int)$ticket['assignee_id']) : '-');
$asgAv   = $ticket['assignee_avatar'] ?? null;

$watchers = is_array($ticket['watchers_resolved'] ?? null) ? $ticket['watchers_resolved'] : [];
?>

<style>
.ticket-lite { --chip-b: var(--bs-border-color); }
.ticket-lite .chip{display:inline-flex;align-items:center;gap:.4rem;border:1px solid var(--chip-b);padding:.25rem .5rem;border-radius:999px;font-size:.75rem;line-height:1;background:#fff}
.ticket-lite .chip img{width:20px;height:20px;border-radius:50%;object-fit:cover}
.ticket-lite .initial{width:20px;height:20px;border-radius:50%;background:#f8f9fa;border:1px solid var(--chip-b);display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;color:#6c757d}
.ticket-lite .meta{color:#6c757d}
.ticket-lite .divider{border-color:var(--bs-border-color);opacity:.6}
.ticket-lite .post{border:1px solid var(--bs-border-color);border-radius:.5rem;padding:.5rem .6rem;background:#fff}
.ticket-lite .post+.post{margin-top:.5rem}
.ticket-lite .author{display:flex;align-items:center;gap:.5rem;color:#6c757d;font-size:.8rem}
.ticket-lite .author img{width:20px;height:20px;border-radius:50%;object-fit:cover}
.ticket-lite .avatar-stack{display:flex;align-items:center}
.ticket-lite .avatar-stack > *{margin-left:-8px}
.ticket-lite .avatar-stack .avatar{width:26px;height:26px;border-radius:50%;object-fit:cover;border:2px solid #fff;box-shadow:0 0 0 1px var(--bs-border-color)}
.ticket-lite .avatar-stack .avatar-fb{width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;background:#f8f9fa;border:1px solid var(--bs-border-color);box-shadow:0 0 0 1px var(--bs-border-color)}
.ticket-lite .pill{border:1px dashed var(--bs-border-color);border-radius:999px;padding:.1rem .45rem;font-size:.7rem}
</style>

<div class="p-3 ticket-lite">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <div class="small meta mb-1">
      <strong>Ticket Code: </strong>
      <?= html_escape($ticket['code'] ?? ('#'.(int)$ticket['id'])) ?> 
      <i class="ti ti-dots-vertical"></i> 
      <strong> Ticket Title: </strong>
      <?= html_escape($ticket['subject'] ?? 'Ticket') ?>
      </div>
      <div class="d-flex flex-wrap align-items-center gap-2 meta mt-2">
        <?php if (!empty($ticket['department_name'])): ?>
          <span class="chip"><i class="ti ti-building me-1"></i><?= html_escape($ticket['department_name']) ?></span>
        <?php endif; ?>
        <span class="badge bg-<?= $prioMap[$priority] ?? 'secondary' ?> text-uppercase" style="font-size:.5rem;"><?= html_escape($priority) ?></span>
        <span class="badge bg-<?= $statMap[$status] ?? 'secondary' ?> text-uppercase" style="font-size:.5rem;"><?= html_escape(str_replace('_',' ', $status)) ?></span>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
        <?php if ($reqName): ?>
          <span class="chip" title="Requester">
            <?php if (!empty($reqAv)): ?> 
              <img src="<?= html_escape($reqAv) ?>" alt="">
            <?php else: ?>
              <span class="initial">Requester: <?= html_escape(name_initials($reqName)) ?></span>
            <?php endif; ?>
            <span><?= html_escape($reqName) ?></span>
          </span>
        <?php endif; ?>

<?php
  // Normalize from either local vars or $ticket (fallbacks are safe)
  $asgId   = isset($asgId) ? (int)$asgId : (int)($ticket['assignee_id']  ?? 0);
  $asgName = trim((string)($asgName ?? ($ticket['assignee_name'] ?? '')));
  $asgAv   = (string)($asgAv   ?? ($ticket['assignee_avatar'] ?? ''));

  // Consider "no assignee" when:
  // - ID is 0, OR
  // - name is empty, OR
  // - name looks like a placeholder "User #0" / "User #<empty>"
  $looksPlaceholder = (stripos($asgName, 'user #') === 0);
  $hasAssignee = ($asgId > 0) && ($asgName !== '') && !$looksPlaceholder;
?>
<span class="chip" title="Assignee">
  <?php if ($hasAssignee): ?>
    <?php if ($asgAv !== ''): ?>
      <img src="<?= html_escape($asgAv) ?>" alt="">
    <?php else: ?>
      <span class="initial"><?= html_escape(name_initials($asgName)) ?></span>
    <?php endif; ?>
    <span><?= html_escape($asgName) ?></span>
  <?php else: ?>
    <i class="ti ti-user me-1"></i>
    <span class="text-muted small">Not Assigned Yet</span>
  <?php endif; ?>
</span>

        <?php if (!empty($watchers)): ?>
          <span class="pill">Watchers</span>
          <span class="avatar-stack ms-1">
            <?php
              $maxShow = 6;
              $extra   = max(0, count($watchers) - $maxShow);
              foreach (array_slice($watchers, 0, $maxShow) as $w):
                $wn = trim((string)($w['name'] ?? 'User'));
                $wa = (string)($w['avatar'] ?? '');
            ?>
              <?php if ($wa): ?>
                <img src="<?= html_escape($wa) ?>" class="avatar" alt="<?= html_escape($wn) ?>"
                     data-bs-toggle="tooltip" data-bs-placement="top" title="<?= html_escape($wn) ?>">
              <?php else: ?>
                <span class="avatar-fb" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= html_escape($wn) ?>">
                  <?= html_escape(name_initials($wn)) ?>
                </span>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($extra > 0): ?>
              <span class="avatar-fb" data-bs-toggle="tooltip" data-bs-placement="top" title="+<?= (int)$extra ?> more">
                +<?= (int)$extra ?>
              </span>
            <?php endif; ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <a class="btn btn-header btn-light-primary" href="<?= base_url('support/view/'.(int)$ticket['id']) ?>" target="_blank">
      <i class="ti ti-external-link"></i> View Full Ticket
    </a>
  </div>

  <hr class="my-2 divider">

  <div class="small meta mb-2 capital">
    <i class="ti ti-calendar-time"></i>
    Created: <?= html_escape($ticket['created_at'] ?? '-') ?>
    <i class="ti ti-dots-vertical"></i> 
    <i class="ti ti-activity"></i>
    Last activity: <?= html_escape($ticket['last_activity_at'] ?? '-') ?>
    <?php if (!empty($ticket['first_response_due_at'])): ?>
      <i class="ti ti-dots-vertical"></i> 
      <i class="ti ti-clock"></i>
      First response due: <?= html_escape($ticket['first_response_due_at']) ?>
    <?php endif; ?>
    <?php if (!empty($ticket['resolution_due_at'])): ?>
      <i class="ti ti-dots-vertical"></i> 
      <i class="ti ti-flag-check"></i>
      Resolution due: <?= html_escape($ticket['resolution_due_at']) ?>
    <?php endif; ?>
  </div>
<div class="app-divider-v dashed"></div>
  <?php if (!empty($ticket['tags']) && is_array($ticket['tags'])): ?>
    <div class="mb-3 mt-3"><small class="text-muted">Tags: </small>
      <?php foreach ($ticket['tags'] as $tag): ?> 
        <span class="badge bg-light-primary capital me-1 mb-1"><?= html_escape($tag) ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h6 class="mb-2 text-muted small">Recent Activity:</h6>

  <?php if (empty($ticket['posts'])): ?>
    <div class="text-muted small">No recent public messages.</div>
  <?php else: foreach ($ticket['posts'] as $p): ?>
    <div class="post">
      <div class="d-flex justify-content-between">
        <div class="author">
          <?php if (!empty($p['author_avatar'])): ?>
            <img src="<?= html_escape($p['author_avatar']) ?>" alt="">
          <?php else: ?>
            <span class="initial"><?= html_escape(name_initials($p['author_name'] ?? 'U')) ?></span>
          <?php endif; ?>
          <strong class="text-body"><?= html_escape($p['author_name'] ?? 'User #'.(int)($p['author_id'] ?? 0)) ?></strong>
        </div>
        <div class="small meta"><?= html_escape($p['created_at'] ?? '') ?></div>
      </div>
      <div class="mt-2 small">
        <?= isset($p['body']) ? $p['body'] : '<span class="text-muted small">(no content)</span>' /* body already sanitized server-side */ ?>
      </div>
      <?php if (!empty($p['attachments']) && is_array($p['attachments'])): ?>
        <div class="mt-2 small">
          <i class="ti ti-paperclip"></i> Attachments:
          <?php foreach ($p['attachments'] as $a):
            $aPath = is_array($a) ? ($a['path'] ?? '#') : '#';
            $aName = is_array($a) ? ($a['name'] ?? 'file') : 'file';
          ?>
            <a href="<?= html_escape($aPath) ?>" target="_blank" rel="noopener" class="ms-2 text-decoration-none">
              <?= html_escape($aName) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>
</div>

<script>
// Single tooltip init for this modal content
(function initLiteTooltips(root){
  if (!window.bootstrap) return;
  (root || document).querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){
    var t = bootstrap.Tooltip.getInstance(el);
    if (t) t.dispose();
    new bootstrap.Tooltip(el, {container:'body', trigger:'hover focus'});
  });
})(document.currentScript ? document.currentScript.closest('.ticket-lite') || document : document);
</script>
