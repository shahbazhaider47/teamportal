<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
  // View-affecting settings
  $limitToDept  = (function_exists('get_setting') && get_setting('support_staff_limited_to_dept', 'no') === 'yes');
  $publicTpl    = (function_exists('get_setting') ? (string)(get_setting('support_ticket_public_url') ?? '') : '');
  $hasPublicTpl = $publicTpl !== '';
  $colCount     = 10 + ($hasPublicTpl ? 1 : 0); // base columns + optional Link column
?>
<div class="container-fluid support-module">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Watched Tickets') ?></h1>

      <?php if ($limitToDept): ?>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="You can only view tickets belonging to departments you’re assigned to.">
          Dept-limited view
        </span>
      <?php endif; ?>

      <?php if ($hasPublicTpl): ?>
        <span class="badge bg-light text-muted border" title="A public ticket URL is configured; rows show a quick public link icon.">
          Public links enabled
        </span>
      <?php endif; ?>
    </div>
    <div>
      <a href="<?= base_url('support'); ?>" class="btn btn-light-primary btn-header">
        <i class="ti ti-arrow-left"></i> Back to Support
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <p class="text-muted small mb-2">You are the watcher of these tickets created by your colleagues:</p>
      <div class="table-responsive">
        <table class="table table-hover table-border-bottom table-sm small align-middle mb-0" id="supportTable">
          <thead class="bg-light-primary small">
            <tr>
              <th style="white-space:nowrap;">Code</th>
              <th>Subject</th>
              <th>Department</th>
              <th>Requester</th>
              <th>Assignee</th>
              <th>Priority</th>
              <th>Status</th>
              <th style="white-space:nowrap;">Last Activity</th>
              <th>Watchers</th>
              <th></th>
              <?php if ($hasPublicTpl): ?><th class="text-end" style="width:48px;">Link</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tickets)): ?>
              <tr><td colspan="<?= (int)$colCount ?>" class="text-center text-muted py-4">No watched tickets.</td></tr>
            <?php else: ?>

              <?php
              // Tiny helper for initials (single guarded definition)
              if (!function_exists('name_initials')) {
                function name_initials($name) {
                  $name = trim((string)$name);
                  if ($name === '') return 'U';
                  preg_match_all('/\b\p{L}/u', $name, $m);
                  $letters = array_slice($m[0] ?? [], 0, 2);
                  return strtoupper(implode('', $letters) ?: mb_substr($name, 0, 1));
                }
              }
              // Badge maps
              $pMap = ['low'=>'primary','normal'=>'light-primary','high'=>'warning','urgent'=>'danger'];
              $sMap = ['open'=>'primary','in_progress'=>'light-primary','waiting_user'=>'warning','on_hold'=>'danger','resolved'=>'success','closed'=>'secondary'];
              ?>

              <?php foreach ($tickets as $r): ?>
                <?php
                  $tid  = (int)($r['id'] ?? 0);
                  $code = (string)($r['code'] ?? '');
                  // Build public URL if template exists
                  $publicHref = '';
                  if ($hasPublicTpl && $tid > 0) {
                    $publicHref = strtr($publicTpl, [
                      '{ticket_id}' => (string)$tid,
                      '{code}'      => ($code !== '' ? $code : (string)$tid),
                    ]);
                  }
                ?>
                <tr>
                  <td><?= html_escape($code !== '' ? $code : ('#' . $tid)) ?></td>
                  <td><?= html_escape($r['subject'] ?? '') ?></td>
                  <td><?= html_escape($r['department_name'] ?? ('#' . (int)($r['department_id'] ?? 0))) ?></td>

                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <?php if (!empty($r['requester_avatar'])): ?>
                        <img src="<?= html_escape($r['requester_avatar']) ?>"
                             alt="Requester"
                             class="rounded-circle object-fit-cover"
                             width="24" height="24">
                      <?php else: ?>
                        <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center"
                             style="width:24px;height:24px;font-size:11px;">
                          <?= html_escape(name_initials($r['requester_name'] ?? (!empty($r['requester_id']) ? 'User '.$r['requester_id'] : ''))) ?>
                        </div>
                      <?php endif; ?>
                      <span><?= html_escape($r['requester_name'] ?? (!empty($r['requester_id']) ? ('User #' . (int)$r['requester_id']) : '')) ?></span>
                    </div>
                  </td>

                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <?php if (!empty($r['assignee_avatar'])): ?>
                        <img src="<?= html_escape($r['assignee_avatar']) ?>"
                             alt="Assignee"
                             class="rounded-circle object-fit-cover"
                             width="24" height="24">
                      <?php else: ?>
                        <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center"
                             style="width:24px;height:24px;font-size:11px;">
                          <?= html_escape(name_initials($r['assignee_name'] ?? (!empty($r['assignee_id']) ? 'User '.$r['assignee_id'] : ''))) ?>
                        </div>
                      <?php endif; ?>
                      <span><?= html_escape($r['assignee_name'] ?? (!empty($r['assignee_id']) ? ('User #' . (int)$r['assignee_id']) : '-')) ?></span>
                    </div>
                  </td>

                  <?php $p = $r['priority'] ?? 'normal'; ?>
                  <td><span class="badge bg-<?= $pMap[$p] ?? 'secondary' ?>"><?= html_escape($p) ?></span></td>

                  <?php $s = $r['status'] ?? 'open'; ?>
                  <td><span class="badge bg-<?= $sMap[$s] ?? 'secondary' ?>"><?= html_escape(str_replace('_',' ', $s)) ?></span></td>

                  <td><?= html_escape($r['last_activity_at'] ?? '') ?></td>

                  <td>
                    <?php $watchers = $r['watchers_resolved'] ?? []; ?>
                    <?php if (empty($watchers)): ?>
                      <span class="text-muted small">—</span>
                    <?php else: ?>
                      <div class="avatar-stack" style="display:flex;align-items:center;gap:0;">
                        <?php foreach ($watchers as $w):
                              $nm  = $w['name'] ?? '';
                              $av  = $w['avatar'] ?? '';
                              $ini = name_initials($nm);
                        ?>
                          <span class="position-relative" style="display:inline-block;margin-left:-8px;">
                            <?php if ($av): ?>
                              <img src="<?= html_escape($av) ?>"
                                   alt="<?= html_escape($nm) ?>"
                                   class="rounded-circle border border-white"
                                   width="28" height="28"
                                   data-bs-toggle="tooltip" data-bs-title="<?= html_escape($nm) ?>">
                            <?php else: ?>
                              <span class="rounded-circle border border-white bg-light d-inline-flex align-items-center justify-content-center"
                                    style="width:28px;height:28px;font-size:11px;"
                                    data-bs-toggle="tooltip" title="<?= html_escape($nm) ?>">
                                <?= html_escape($ini) ?>
                              </span>
                            <?php endif; ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <td class="text-end">
                    <button type="button" class="btn btn-ssm btn-light-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#ticketLiteModal"
                            data-ticket-id="<?= (int)$r['id'] ?>">
                      <i class="ti ti-eye"></i> View
                    </button>
                  </td>

                  <?php if ($hasPublicTpl): ?>
                    <td class="text-end">
                      <?php if ($publicHref): ?>
                        <a href="<?= html_escape($publicHref) ?>" class="btn btn-ssm btn-light" target="_blank" title="Open public link">
                          <i class="ti ti-external-link"></i>
                        </a>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>

            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Lite Modal -->
<div class="modal fade" id="ticketLiteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-body p-0" id="ticketLiteBody">
        <div class="p-3 text-center text-muted">Loading…</div>
      </div>
    </div>
  </div>
</div>

<style>
  .avatar-stack img, .avatar-stack span[title] { pointer-events: auto; }
  .avatar-stack { overflow: visible; }
  .avatar-stack > * { margin-left: -8px; }
  .tooltip .tooltip-inner {
    font-size: 11px;
    padding: 8px 8px;
    line-height: 1.2;
  }
</style>

<script>
(function () {
  'use strict';

  function initTooltips(root) {
    if (!window.bootstrap) return;
    var scope = root || document;
    var els = scope.querySelectorAll('[data-bs-toggle="tooltip"]');
    els.forEach(function (el) {
      var inst = bootstrap.Tooltip.getInstance(el);
      if (inst) inst.dispose();
      new bootstrap.Tooltip(el, { container: 'body', trigger: 'hover focus' });
    });
  }

  document.addEventListener('DOMContentLoaded', function () { initTooltips(); });

  document.addEventListener('ajaxContentReady', function (e) { initTooltips(e.target || document); });

  document.addEventListener('shown.bs.modal', function (e) {
    if (e.target && e.target.id === 'ticketLiteModal') {
      initTooltips(e.target);
    }
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-ticket-id][data-bs-target="#ticketLiteModal"]');
    if (!btn) return;

    var id = btn.getAttribute('data-ticket-id');
    var body = document.getElementById('ticketLiteBody');
    if (!body) return;

    body.innerHTML = '<div class="p-3 text-center text-muted">Loading…</div>';

    fetch('<?= base_url('support/view_modal_lite/'); ?>' + id, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        body.innerHTML = html;
        initTooltips(body);
      })
      .catch(function () {
        body.innerHTML = '<div class="p-3 text-danger text-center">Failed to load.</div>';
      });
  });
})();
</script>
