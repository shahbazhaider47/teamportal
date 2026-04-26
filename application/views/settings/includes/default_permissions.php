<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Ensure defaults exist
$default_user_grants = isset($default_user_grants) && is_array($default_user_grants) ? $default_user_grants : [];

// Build $all_permissions from core + modules if controller didn't pass it
if (!isset($all_permissions) || !is_array($all_permissions) || !$all_permissions) {
  $coreModules        = include APPPATH . 'config/core_permissions.php';
  $modulePermissions  = hooks()->apply_filters('user_permissions', []);
  $modules            = array_merge($coreModules, $modulePermissions);

  $all_permissions = [];
  foreach ($modules as $moduleKey => $actions) {
    $actionArr = (is_array($actions) && isset($actions['actions'])) ? $actions['actions'] : $actions;
    if (is_array($actionArr)) {
      foreach ($actionArr as $actionKey => $label) {
        $all_permissions[] = strtolower($moduleKey . ':' . $actionKey);
      }
    }
  }
  $all_permissions = array_values(array_unique($all_permissions, SORT_STRING));
  sort($all_permissions);
}

// Popular chips (optional; safe default)
$popular_permissions = isset($popular_permissions) && is_array($popular_permissions) ? $popular_permissions : [
  'users:view_own','teams:view_own','todos:view_own','todos:create',
  'reminders:view_own','attendance:view_own','support:view_own'
];

// Humanizer
if (!function_exists('humanize_perm')) {
  function humanize_perm($perm): string {
    $perm = (string)$perm;
    $parts  = explode(':', $perm, 2);
    $module = $parts[0] ?? '';
    $action = $parts[1] ?? '';

    $moduleLabel = ucwords(str_replace(['_', '-', '.'], ' ', $module));
    $actionLabel = ucwords(str_replace(['_', '-', '.'], ' ', $action));

    return trim($moduleLabel . ': Can ' . $actionLabel);
  }
}
?>

<style>
/* Layout */
.perm-defaults .panel { border:1px solid #e9ecef; border-radius:10px; }
.perm-defaults .panel-hd { padding:.6rem .9rem; background:#f7f9fb; border-bottom:1px solid #e9ecef; border-radius:10px 10px 0 0; }
.perm-defaults .panel-bd { padding:.75rem .9rem; }

/* Badge list areas */
.badge-area {
  min-height: 210px;
  max-height: 420px;
  overflow:auto;
  border:1px dashed #cfd8e3;
  border-radius:10px;
  padding:.6rem;
  background:#fff;
}

/* Badges */
.perm-badge {
  display:inline-flex;
  align-items:center;
  gap:.4rem;
  font-size:12px;
  background:#eef2ff;
  color:#1e293b;
  border:1px solid #c7d2fe;
  padding:.25rem .5rem;
  border-radius:999px;
  margin:.2rem;
  user-select:none;
}
.perm-badge[data-zone="source"] { cursor:grab; }
.perm-badge .x {
  display:inline-block; width:16px; height:16px; line-height:14px; text-align:center;
  border-radius:999px; cursor:pointer; font-weight:700; font-size:11px;
  background:#fff; color:#ef4444; border:1px solid #fecaca;
}
.perm-badge.grant { background:#ecfdf5; border-color:#a7f3d0; }

/* Drop highlights */
.badge-area.drop-ok { background:#f0fdf4; border-color:#86efac; }

/* Search/select */
#permSearch { font-size: 13px; }

/* Little help text */
.small-muted { font-size:12px; color:#6b7280; }

/* Buttons row */
.btn-row { display:flex; gap:.5rem; flex-wrap:wrap; }

/* Chips row */
.chips-row .chip {
  display:inline-block; margin:.15rem; font-size:12px;
}
</style>

<div class="container-fluid perm-defaults">
    
    <div class="card-body p-0">
      <form method="post" action="<?= site_url('settings/save_default_permissions') ?>" class="app-form" id="defaultPermsForm">

        <div class="row g-3">
          <!-- LEFT: All permissions (source) -->
          <div class="col-lg-6">
            <div class="panel">
              <div class="panel-hd d-flex align-items-center justify-content-between">
                <div>
                  <strong>All Permissions</strong>
                  <div class="small-muted">Drag any badge below into Grants, or use the buttons.</div>
                </div>
              </div>
              <div class="panel-bd">
                <div class="btn-row mb-2">
                  <select class="form-select form-select-sm" id="permQuickSelect">
                    <option class="app-form small" value="">— Select a permission —</option>
                    <?php foreach ($all_permissions as $p): ?>
                      <option value="<?= html_escape($p) ?>"><?= html_escape(humanize_perm($p)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" class="btn btn-header btn-primary" id="quickAddGrant">
                    <i class="ti ti-plus"></i> Add to Grants
                  </button>
                </div>

                <?php if (!empty($popular_permissions)): ?>
                  <div class="chips-row mb-2">
                    <span class="small-muted d-block mb-1">Popular:</span>
                    <?php foreach ($popular_permissions as $pp): ?>
                      <button type="button" class="btn btn-light-primary btn-xs chip add-popular" data-perm="<?= html_escape($pp) ?>">
                        <?= html_escape(humanize_perm($pp)) ?>
                      </button>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <div id="sourceArea"  aria-label="All permissions (drag source)">

                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT: Grants (target) -->
          <div class="col-lg-6">
            <div class="row g-3">
              <div class="col-12">
                <div class="panel">
                  <div class="panel-hd">
                    <strong>Default Grants</strong>
                    <div class="small-muted">Drop here to grant by default.</div>
                  </div>
                  <div class="panel-bd">
                    <div id="grantsArea" class="badge-area" aria-label="Default grants (drop target)">
                      <?php foreach ($default_user_grants as $perm): ?>
                        <span class="perm-badge grant" data-perm="<?= html_escape($perm) ?>" draggable="false" data-zone="grants">
                          <?= html_escape(humanize_perm($perm)) ?>
                          <span class="x" title="Remove" aria-label="Remove">×</span>
                        </span>
                      <?php endforeach; ?>
                    </div>
                    <div class="btn-row mt-2">
                      <button type="button" class="btn btn-outline-secondary btn-sm" id="clearGrants">Clear</button>
                      <button type="button" class="btn btn-outline-secondary btn-sm" id="sortGrants">Sort & dedupe</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden inputs we submit (kept in sync from badges) -->
        <input type="hidden" name="default_user_grants" id="grantsInput">
      </form>

    </div>
  </div>

<script>
(function(){
  const sourceArea   = document.getElementById('sourceArea');
  const grantsArea   = document.getElementById('grantsArea');
  const permSearch   = document.getElementById('permSearch');
  const quickSelect  = document.getElementById('permQuickSelect');
  const quickAddG    = document.getElementById('quickAddGrant');
  const form         = document.getElementById('defaultPermsForm');
  const grantsInput  = document.getElementById('grantsInput');

  // Helpers
  function titleCase(s){
    return String(s).replace(/[_\-.]+/g, ' ')
                    .replace(/\w\S*/g, t => t.charAt(0).toUpperCase() + t.slice(1));
  }
  function humanize(perm){
    const p = String(perm || '');
    const parts = p.split(':', 2);
    const moduleLabel = titleCase(parts[0] || '');
    const actionLabel = titleCase(parts[1] || '');
    return (moduleLabel ? moduleLabel + ': ' : '') + 'Can ' + actionLabel;
  }
  function cssEscape(s){ return String(s).replace(/(["\\])/g,'\\$1'); }
  function hasBadge(area, perm){ return !!area.querySelector('[data-perm="'+cssEscape(perm)+'"]'); }
  function listPerms(area){ return Array.from(area.querySelectorAll('.perm-badge')).map(b=>b.getAttribute('data-perm')); }

  function makeBadge(perm, zone) {
    const span = document.createElement('span');
    span.className = 'perm-badge' + (zone==='grants' ? ' grant' : '');
    span.setAttribute('data-perm', perm);
    span.setAttribute('data-zone', zone || 'source');
    span.draggable = zone === 'source';

    span.appendChild(document.createTextNode(humanize(perm)));

    if (zone !== 'source') {
      const x = document.createElement('span');
      x.className = 'x'; x.textContent = '×'; x.title = 'Remove';
      x.addEventListener('click', () => { span.remove(); });
      span.appendChild(document.createTextNode(' '));
      span.appendChild(x);
    }

    span.addEventListener('dragstart', (ev) => {
      ev.dataTransfer.setData('text/plain', perm);
    });
    return span;
  }

  function addToArea(area, perm, zone) {
    if (!hasBadge(area, perm)) {
      area.appendChild(makeBadge(perm, zone));
    }
    const srcBadge = sourceArea.querySelector('[data-perm="'+cssEscape(perm)+'"]');
    if (srcBadge) srcBadge.remove();
  }

  function returnToSource(perm) {
    if (!hasBadge(sourceArea, perm)) {
      sourceArea.appendChild(makeBadge(perm, 'source'));
    }
  }

  function sortArea(area) {
    const perms = listPerms(area).sort((a,b)=> a.localeCompare(b));
    area.innerHTML = '';
    perms.forEach(p => area.appendChild(makeBadge(p, 'grants')));
  }

  // Drag & drop
  function enableDrop(area, highlightClass) {
    area.addEventListener('dragover', (ev) => { ev.preventDefault(); area.classList.add(highlightClass); });
    area.addEventListener('dragleave', () => area.classList.remove(highlightClass));
    area.addEventListener('drop', (ev) => {
      ev.preventDefault();
      area.classList.remove(highlightClass);
      const perm = (ev.dataTransfer.getData('text/plain') || '').trim();
      if (!perm) return;
      if (area === grantsArea) {
        addToArea(grantsArea, perm, 'grants');
      } else if (area === sourceArea) {
        const g = grantsArea.querySelector('[data-perm="'+cssEscape(perm)+'"]');
        if (g) g.remove();
        returnToSource(perm);
      }
    });
  }
  enableDrop(grantsArea, 'drop-ok');
  enableDrop(sourceArea, 'drop-ok');

  // Search filter on source
  permSearch?.addEventListener('input', () => {
    const q = (permSearch.value || '').toLowerCase();
    Array.from(sourceArea.querySelectorAll('.perm-badge')).forEach(b=>{
      const v = (b.getAttribute('data-perm') || '').toLowerCase();
      b.style.display = (!q || v.includes(q)) ? '' : 'none';
    });
  });

  // Quick select add
  function quickAdd() {
    const val = (quickSelect.value || '').trim();
    if (!val) return;
    addToArea(grantsArea, val, 'grants');
  }
  quickAddG?.addEventListener('click', quickAdd);

  // Popular chips default to Grants
  document.querySelectorAll('.add-popular').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const perm = btn.getAttribute('data-perm');
      if (!perm) return;
      addToArea(grantsArea, perm, 'grants');
    });
  });

  // Clear / sort
  document.getElementById('clearGrants')?.addEventListener('click', ()=>{ grantsArea.innerHTML = ''; });
  document.getElementById('sortGrants')?.addEventListener('click', ()=> sortArea(grantsArea));

  // Remove via [x] returns to source
  function delegateRemove(area) {
    area.addEventListener('click', (e)=>{
      const x = e.target.closest('.x');
      if (!x) return;
      const badge = x.closest('.perm-badge');
      const perm  = badge?.getAttribute('data-perm');
      if (!perm) return;
      badge.remove();
      if (!hasBadge(sourceArea, perm)) {
        sourceArea.appendChild(makeBadge(perm, 'source'));
      }
    });
  }
  delegateRemove(grantsArea);

  // Keep hidden inputs in sync on submit
  form.addEventListener('submit', ()=>{
    const g = new Set(listPerms(grantsArea));
    grantsInput.value = Array.from(g).join("\n");
  });
})();
</script>