<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
  // Pull settings that affect this view
  $limitToDept   = (function_exists('get_setting') && get_setting('support_staff_limited_to_dept', 'no') === 'yes');
  $canCreate     = (function_exists('staff_can') && staff_can('create', 'support'));
  $canExport     = (function_exists('staff_can') && staff_can('export', 'general'));
  $canPrint      = (function_exists('staff_can') && staff_can('print', 'general'));
?>

<div class="container-fluid support-module">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Support Tickets') ?></h1>

      <?php if ($limitToDept): ?>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="You can only view tickets belonging to departments you’re assigned to.">
          Dept-limited view
        </span>
      <?php endif; ?>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">

      <a href="<?= base_url('support/watching'); ?>" class="btn btn-primary btn-header">
        <i class="ti ti-ticket"></i> Watching Tickets
      </a>

      <div class="btn-divider"></div>

      <?php if ($canCreate): ?>
        <a href="<?= base_url('support/create'); ?>" class="btn btn-outline-primary btn-header" title="Open a new support ticket">
          <i class="ti ti-plus"></i> New Ticket
        </a>
      <?php endif; ?>

      <!-- Global Search -->
      <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
        <input type="text" class="form-control rounded app-form small dynamic-search-input"
               placeholder="Search..."
               aria-label="Search"
               data-table-target="<?= $table_id ?? 'supportTable' ?>">
        <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
      </div>

      <!-- Export -->
      <?php if ($canExport): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                title="Export to Excel"
                data-export-filename="<?= html_escape($page_title ?? 'export') ?>">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>

      <!-- Print -->
      <?php if ($canPrint): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                title="Print Table">
          <i class="ti ti-printer"></i>
        </button>
      <?php endif; ?>

    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body">
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
            </tr>
          </thead>
          <tbody id="ticketsRows">
            <?php if (empty($tickets)): ?>
              <tr><td colspan="8" class="text-center text-muted py-4">No support tickets found.</td></tr>
            <?php else: foreach ($tickets as $r): ?>
              <?php
                // Helper: name initials
                if (!function_exists('name_initials')) {
                  function name_initials($name) {
                    $name = trim((string)$name);
                    if ($name === '') return 'U';
                    preg_match_all('/\b\p{L}/u', $name, $m);
                    $letters = array_slice($m[0] ?? [], 0, 2);
                    return strtoupper(implode('', $letters) ?: mb_substr($name, 0, 1));
                  }
                }
                $tid   = (int)($r['id'] ?? 0);
                $code  = (string)($r['code'] ?? '');
                $subj  = (string)($r['subject'] ?? '');
                $deptN = $r['department_name'] ?? ('#' . (int)($r['department_id'] ?? 0));
              ?>
              <tr>
                <td>
                  <a href="<?= base_url('support/view/' . $tid); ?>" class="text-decoration-none">
                    <?= html_escape($code !== '' ? $code : ('#' . $tid)) ?>
                  </a>
                </td>
                <td><?= html_escape($subj) ?></td>
                <td><?= html_escape($deptN) ?></td>

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

                <td>
                  <?php
                    $p = $r['priority'] ?? 'normal';
                    $pMap = ['low'=>'primary','normal'=>'light-primary','high'=>'warning','urgent'=>'danger'];
                  ?>
                  <span class="capital badge bg-<?= $pMap[$p] ?? 'secondary' ?>"><?= html_escape($p) ?></span>
                </td>
                <td>
                  <?php
                    $s = $r['status'] ?? 'open';
                    $sMap = ['open'=>'primary','in_progress'=>'light-primary','waiting_user'=>'warning','on_hold'=>'danger','resolved'=>'success','closed'=>'secondary'];
                  ?>
                  <span class="capital badge bg-<?= $sMap[$s] ?? 'secondary' ?>"><?= html_escape(str_replace('_',' ',$s)) ?></span>
                </td>
                <td><?= html_escape($r['last_activity_at'] ?? '') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<script>
// Utilities
const el     = (sel) => document.querySelector(sel);
const rowsEl = el('#ticketsRows');
const loader = el('#ticketsLoading');

// Status & Priority badges (JS side, used by AJAX renderer)
function badgeForStatus(s) {
  const map = {
    open: 'primary',
    in_progress: 'light-primary',
    waiting_user: 'warning',
    on_hold: 'danger',
    resolved: 'success',
    closed: 'secondary'
  };
  const key = (s || 'open');
  return `<span class="badge bg-${map[key] || 'secondary'} capital">${key.replace('_',' ')}</span>`;
}

function badgeForPriority(p) {
  const map = { low: 'primary', normal: 'light-primary', high: 'warning', urgent: 'danger' };
  const key = (p || 'normal');
  return `<span class="badge bg-${map[key] || 'secondary'} capital">${key}</span>`;
}

function escapeHtml(str) {
  return (str || '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
  }[m]));
}

async function fetchTickets() {
  if (!loader) return;
  loader.classList.remove('d-none');

  // Using site_url ensures correct base even if app isn't at domain root.
  const url = new URL('<?= site_url('support'); ?>', window.location.origin);
  url.search = new URLSearchParams({ limit: 50, offset: 0 }).toString();

  try {
    const res  = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const json = await res.json();
    renderRows(json.data || []);
  } catch (e) {
    if (rowsEl) rowsEl.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Failed to load support tickets.</td></tr>`;
  } finally {
    loader.classList.add('d-none');
  }
}

function renderRows(items) {
  if (!rowsEl) return;
  if (!items.length) {
    rowsEl.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No support tickets found.</td></tr>`;
    return;
  }
  rowsEl.innerHTML = items.map(r => {
    const codeText = (r.code && r.code.length) ? r.code : ('#' + r.id);

    return `
      <tr>
        <td><a href="<?= base_url('support/view'); ?>/${r.id}" class="text-decoration-none">${escapeHtml(codeText)}</a></td>
        <td>${escapeHtml(r.subject || '')}</td>
        <td>${escapeHtml(r.department_name || String(r.department_id || ''))}</td>
        <td>${escapeHtml(r.requester_name || (r.requester_id ? ('User #' + r.requester_id) : ''))}</td>
        <td>${escapeHtml(r.assignee_name || (r.assignee_id ? ('User #' + r.assignee_id) : '-'))}</td>
        <td>${badgeForPriority(r.priority)}</td>
        <td>${badgeForStatus(r.status)}</td>
        <td>${escapeHtml(r.last_activity_at ?? '')}</td>
      </tr>
    `;
  }).join('');
}

// Initial load (AJAX refresh; server-side rows already rendered for first paint)
fetchTickets();
</script>
