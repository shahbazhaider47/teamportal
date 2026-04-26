<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
if (!function_exists('t_s')) {
  function t_s($v) { return is_scalar($v) ? html_escape((string)$v) : ''; }
}

// Progress numbers from controller summary
$total    = (int)($check_summary['total'] ?? 0);
$done     = (int)($check_summary['done'] ?? 0);
$percent  = $total > 0 ? round(($done / $total) * 100) : 0;
$percent2 = $total > 0 ? number_format(($done / $total) * 100, 2) : '0.00';

// Fallback: load active users if controller didn't pass $active_users
if (!isset($active_users) || !is_array($active_users) || !$active_users) {
  $CI = &get_instance();
  $active_users = $CI->db
    ->select('id, TRIM(CONCAT(COALESCE(firstname,""), " ", COALESCE(lastname,""))) AS fullname', false)
    ->from('users')->where('is_active', 1)
    ->order_by('fullname','ASC')->get()->result_array();
}
?>
<style>
  /* ------ Checklist scoped styles ------ */
  .checklist-card .checklist-progress { height: 14px; background: #e9ecef; }
  .checklist-card .checklist-progress .progress-bar { transition: width .2s ease; }
  .checklist-card .progress-label {
    position: absolute; inset: 0; display:flex; align-items:center; justify-content:center;
    font-size: 12px; color: #fff; pointer-events: none;
  }
  .checklist-card .chk-toggle {
    width: 22px; height: 22px; border-radius: 50%;
    display:inline-flex; align-items:center; justify-content:center;
    border: 1.5px solid var(--bs-secondary);
    background: #fff; color: var(--bs-secondary);
    padding: 0;
  }
  .checklist-card .chk-toggle.is-done { background: #28a745; border-color: #28a745; color: #fff; }
  .checklist-card .checklist-item .checklist-text { line-height: 1.4; }
  .checklist-card .checklist-item.completed .checklist-text { text-decoration: line-through; color: var(--bs-secondary) !important; }
</style>

<!-- ===== Checklist Card ===== -->
<div class="mb-3 checklist-card" id="checklistCard" data-task-id="<?= (int)$taskId ?>">
  <div class="card-header py-2 d-flex align-items-center justify-content-between">
    <strong class="text-muted">Checklist Items</strong>
    <button type="button" class="btn btn-sm btn-outline-primary" id="chkAddQuick" aria-expanded="false" aria-controls="checkAddForm">
      <i class="ti ti-plus"></i>
    </button>
  </div>

  <!-- Progress bar with centered label -->
  <div class="px-3 pt-2">
    <div class="progress checklist-progress position-relative" aria-label="Checklist progress" aria-valuemin="0" aria-valuemax="100">
      <div class="progress-bar bg-secondary" role="progressbar"
           style="width: <?= $percent ?>%;"
           aria-valuenow="<?= $percent ?>"></div>
      <div class="progress-label" id="chkProgressLabel"><?= $percent2 ?>%</div>
    </div>
  </div>

  <div class="card-body pt-2">
    <div class="d-flex justify-content-between small text-muted mb-2">
      <span id="chkPctText"><?= $percent2 ?>%</span>
      <span><span id="chkDone"><?= (int)$done ?></span> of <span id="chkTotal"><?= (int)$total ?></span></span>
    </div>

    <?php $hasItems = !empty($checklist) && is_array($checklist); ?>
    <?php if ($hasItems): ?>

<?php
// Build a quick index from $active_users so we can resolve profile pics for assignees.
$activeIndex = [];
if (isset($active_users) && is_array($active_users)) {
  foreach ($active_users as $u) {
    $id = (int)($u['id'] ?? 0);
    if ($id <= 0) continue;

    $name = trim($u['fullname'] ?? (($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '')));
    if ($name === '') $name = 'User #'.$id;

    $avatar = '';
    if (!empty($u['profile_image'])) {
      $avatar = base_url('uploads/users/profile/'.$u['profile_image']);
    } elseif (function_exists('user_avatar_url')) {
      $avatar = user_avatar_url($u['profile_image'] ?? null);
    }

    $activeIndex[$id] = ['name' => $name, 'avatar' => $avatar];
  }
}
?>


      
      <ul class="list-group list-group-flush list-check" id="checklistList">
        <?php foreach ($checklist as $item):
          $iid        = (int)$item['id'];
          $finished   = (int)($item['finished'] ?? 0) === 1;
          $assignedId = isset($item['assigned']) ? (int)$item['assigned'] : null;
          $assignedNm = isset($item['assigned_name']) ? trim((string)$item['assigned_name']) : '';
          $assignedUi = $assignedNm !== '' ? $assignedNm : ($assignedId ? ('#'.$assignedId) : '—');
          $itemClass  = $finished ? 'completed' : '';
          $listOrder  = isset($item['list_order']) ? (int)$item['list_order'] : null;
        ?>
          <li class="list-group-item checklist-item <?= $itemClass ?>"
              data-check-id="<?= $iid ?>"
              <?php if ($listOrder !== null): ?>data-order="<?= (int)$listOrder ?>"<?php endif; ?>
              data-finished="<?= $finished ? '1' : '0' ?>">
            <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
              <label class="chk-toggle-row toggle flex-grow-1 mb-0" data-check-id="<?= $iid ?>">
                <input
                  type="checkbox"
                  class="chk-toggle-input"
                  <?= $finished ? 'checked' : '' ?>
                  aria-label="Toggle checklist item <?= (int)$iid ?>"
                >
                <span class="chk-box" aria-hidden="true">
                  <i class="ti ti-check"></i>
                </span>
                <span
                  class="checklist-text small text-truncate">
                  <?= t_s($item['description'] ?? '') ?>
                </span>
              </label>
            </div>
<div class="d-flex align-items-center gap-1">
  <?php
// Resolve full name (never show raw ID)
$assigneeName = $assignedNm !== ''
  ? $assignedNm
  : (($assignedId && isset($activeIndex[$assignedId]['name'])) ? $activeIndex[$assignedId]['name'] : '');

$assigneeTitle = $assigneeName !== '' ? $assigneeName : 'Not assigned';

// Prefer avatar if available
$assigneeAvatar = '';
if (!empty($item['assigned_avatar'])) {
  $assigneeAvatar = (string)$item['assigned_avatar'];
} elseif ($assignedId && isset($activeIndex[$assignedId]['avatar'])) {
  $assigneeAvatar = $activeIndex[$assignedId]['avatar'];
} elseif ($assignedId && function_exists('user_avatar_url')) {
  $assigneeAvatar = user_avatar_url(null);
}

  ?>

<?php if ($assignedId): ?>
  <span class="d-inline-flex align-items-center me-3"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        aria-label="<?= t_s($assigneeTitle) ?>">
    <?= user_profile($assignedId, [
        'class' => 'rounded-circle object-fit-cover',
        'style' => 'width:22px;height:22px;object-fit:cover;',
        // title/alt will be set to full name by helper, no visible text
    ]) ?>
  </span>
  <?php else: ?>
    <span class="d-inline-flex align-items-center text-muted me-3"
          title="Not assigned"
          data-bs-toggle="tooltip"
          data-bs-placement="top"
          aria-label="Not assigned">
      <i class="ti ti-user-off" aria-hidden="true"></i>
    </span>
  <?php endif; ?>

		 <div class="btn-group btn-group-sm" role="group"
		 aria-label="Small button group">
		     
  <button type="button"
          class="btn btn-ssm btn-light-danger delete"
          data-check-id="<?= $iid ?>"
          title="Delete">
    <i class="ti ti-trash"></i>
  </button>

    <?php
    render_todo_ai_button($item['description'] ?? ('Task #' . $taskId), [
        'rel_type'  => 'task',
        'rel_id'    => (int) $taskId,
    ]);
    ?>

</div>


</div>

            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="text-muted small text-center py-3" id="checklistEmpty">No checklist items yet.</div>
      <ul class="list-group list-group-flush list-check" id="checklistList" hidden></ul>
    <?php endif; ?>

    <!-- Quick-add form (always available; permissions removed) -->
<form id="checkAddForm" class="mt-3 d-none" autocomplete="off">
  <div class="row g-2 align-items-center">
    <div class="col-md-6">
      <input type="text"
             name="description"
             class="form-control small form-control-sm"
             placeholder="Checklist Item…"
             required
             maxlength="500">
      <input type="hidden" name="taskid" value="<?= (int)$taskId ?>">
      <input type="hidden" name="finished" value="0">
      <input type="hidden" name="list_order" value="">
    </div>

    <div class="col-md-3">
      <select name="assigned" id="chkAssigned"
              class="form-select form-select-sm"
              aria-label="Assign to">
        <option value="">— Assign to —</option>
        <?php foreach ($active_users as $u): ?>
          <?php
            $uidX  = (int)($u['id'] ?? 0);
            $fname = trim($u['fullname'] ?? '');
            if ($fname === '') { $fname = 'User #'.$uidX; }
          ?>
          <option value="<?= $uidX ?>"><?= t_s($fname) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3 d-grid">
      <button class="btn btn-primary btn-sm" type="submit">
        <i class="ti ti-plus"></i> Add
      </button>
    </div>
  </div>
</form>


  </div>
</div>

<script>
/* Single-shot initializer with hard de-duplication per taskId */
window.initChecklistPanel = function initChecklistPanel(root) {
  const scope = root && root.querySelector ? root : document;

  const cards = Array.from(scope.querySelectorAll('#checklistCard, .checklist-card'));
  if (!cards.length) return;

  const card = cards[cards.length - 1];
  const taskId = Number(card.getAttribute('data-task-id') || 0);

  // remove any earlier cards for same task to avoid duplicated views
  const dupes = Array.from(document.querySelectorAll('.checklist-card[data-task-id="' + taskId + '"]'))
    .filter(n => n !== card);
  dupes.forEach(n => n.remove());

  if (card.dataset.bound === '1') return;
  card.dataset.bound = '1';

  const list        = card.querySelector('#checklistList');
  const emptyBlock  = card.querySelector('#checklistEmpty');
  const lblDone     = card.querySelector('#chkDone');
  const lblTotal    = card.querySelector('#chkTotal');
  const progressBar = card.querySelector('.checklist-progress .progress-bar');
  const pctLabel    = card.querySelector('#chkProgressLabel');
  const pctText     = card.querySelector('#chkPctText');
  const addBtn      = card.querySelector('#chkAddQuick');
  const addForm     = card.querySelector('#checkAddForm');

  function esc(s){return (s||'').toString().replace(/[&<>"]/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m]));}

  async function postForm(url, data) {
    const opt = { method:'POST', headers:{ 'X-Requested-With':'XMLHttpRequest' } };
    if (data instanceof URLSearchParams) { opt.headers['Content-Type']='application/x-www-form-urlencoded'; opt.body=data; }
    else if (data instanceof FormData) { opt.body=data; }
    else { opt.headers['Content-Type']='application/x-www-form-urlencoded'; opt.body=new URLSearchParams(data||{}); }
    try {
      const res = await fetch(url, opt);
      const ct  = res.headers.get('content-type') || '';
      if (ct.includes('application/json')) {
        const json = await res.json();
        return (typeof json.success !== 'undefined') ? json : { success: res.ok, data: json };
      }
      return { success: res.ok };
    } catch(e) { return { success:false, message:e.message || 'Network error' }; }
  }

  function recalc() {
    if (!list) return;
    const items = list.querySelectorAll('.checklist-item');
    const total = items.length;
    let done = 0; items.forEach(li => { if (li.classList.contains('completed')) done++; });

    if (lblDone)  lblDone.textContent  = String(done);
    if (lblTotal) lblTotal.textContent = String(total);

    const p  = total > 0 ? Math.round((done/total)*100) : 0;
    const p2 = total > 0 ? ((done/total)*100).toFixed(2) : '0.00';

    if (progressBar) { progressBar.style.width = p + '%'; progressBar.setAttribute('aria-valuenow', String(p)); }
    if (pctLabel) pctLabel.textContent = p2 + '%';
    if (pctText)  pctText.textContent  = p2 + '%';

    if (emptyBlock) emptyBlock.hidden = total !== 0;
    if (list) list.hidden = total === 0;
  }

  function setRowDone(li, done) {
    const text = li?.querySelector('.checklist-text');
    const cb   = li?.querySelector('.chk-toggle-input');
    if (li)   li.classList.toggle('completed', !!done);
    if (cb)   cb.checked = !!done;
    if (text) {
      text.classList.toggle('text-decoration-line-through', !!done);
      text.classList.toggle('text-muted', !!done);
    }
  }

  function initials(name) {
    return (name || 'U').trim().split(/\s+/).map(p=>p[0]||'').join('').toUpperCase().slice(0,2);
  }

  function buildAssigneeHtml(assignedId, assignedName, assignedAvatar) {
    if (assignedId) {
      if (assignedAvatar) {
        return `
          <span class="d-inline-flex align-items-center"
                title="${esc(assignedName || ('#'+assignedId))}"
                data-bs-toggle="tooltip" data-bs-placement="top"
                aria-label="${esc(assignedName || ('#'+assignedId))}">
            <img src="${esc(assignedAvatar)}" alt="${esc(assignedName || ('#'+assignedId))}"
                 class="rounded-circle object-fit-cover" width="22" height="22">
          </span>`;
      }
      return `
        <span class="d-inline-flex align-items-center"
              title="${esc(assignedName || ('#'+assignedId))}"
              data-bs-toggle="tooltip" data-bs-placement="top"
              aria-label="${esc(assignedName || ('#'+assignedId))}">
          <span class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center"
                style="width:22px;height:22px;font-size:10px;">${esc(initials(assignedName || 'U'))}</span>
        </span>`;
    }
    return `
      <span class="d-inline-flex align-items-center text-muted"
            title="Not assigned" data-bs-toggle="tooltip" data-bs-placement="top"
            aria-label="Not assigned">
        <i class="ti ti-user-off" aria-hidden="true"></i>
      </span>`;
  }

  // --- TOGGLE (click on label area)
  list && list.addEventListener('click', async (e) => {
    const tglArea = e.target.closest('.chk-toggle-row'); // the label wrapper
    const delBtn  = e.target.closest('.delete');
    const dupBtn  = e.target.closest('.duplicate');

    if (tglArea) {
      const id = tglArea.getAttribute('data-check-id'); if (!id) return;
      const li = tglArea.closest('.checklist-item');
      const wasDone = li?.classList.contains('completed');
      const willBeDone = !wasDone;

      // optimistic UI
      setRowDone(li, willBeDone);
      recalc();

      const resp = await postForm('<?= site_url('tasks/checklist/toggle/') ?>' + id, new URLSearchParams({ __req: 1 }));
      if (!resp || resp.success === false) {
        // rollback
        setRowDone(li, wasDone);
        recalc();
        alert((resp && resp.message) || 'Toggle failed.');
      }
      return;
    }

    if (delBtn) {
      const id = delBtn.getAttribute('data-check-id'); if (!id) return;
      if (!confirm('Delete this checklist item?')) return;

      delBtn.disabled = true;
      const li = delBtn.closest('.checklist-item');
      const placeholder = document.createElement('li');
      if (li) { placeholder.className = li.className; placeholder.innerHTML = '<div class="text-muted small">Removing…</div>'; li.replaceWith(placeholder); }

      const resp = await postForm('<?= site_url('tasks/checklist/delete/') ?>' + id, new URLSearchParams({ __req: 1 }));
      if (resp && resp.success !== false) { placeholder.remove(); recalc(); }
      else { if (placeholder && li) placeholder.replaceWith(li); alert((resp && resp.message) || 'Delete failed.'); delBtn.disabled = false; }
      return;
    }

    if (dupBtn) { alert('Duplicate action not wired yet.'); return; }
  });

  // --- TOGGLE (keyboard / direct checkbox change)
  list && list.addEventListener('change', async (e) => {
    const cb = e.target.closest('.chk-toggle-input');
    if (!cb) return;

    const label = cb.closest('.chk-toggle-row');
    const id    = label?.getAttribute('data-check-id');
    const li    = cb.closest('.checklist-item');
    if (!id || !li) return;

    const willBeDone = !!cb.checked;
    const wasDone    = li.classList.contains('completed');

    // optimistic
    setRowDone(li, willBeDone);
    recalc();

    const resp = await postForm('<?= site_url('tasks/checklist/toggle/') ?>' + id, new URLSearchParams({ __req: 1 }));
    if (!resp || resp.success === false) {
      // rollback
      setRowDone(li, wasDone);
      recalc();
      alert((resp && resp.message) || 'Toggle failed.');
    }
  });

  // show/hide quick add & submit
  if (addBtn && addForm) {
    addBtn.addEventListener('click', () => {
      const isOpen = !addForm.classList.toggle('d-none');
      addBtn.setAttribute('aria-expanded', String(isOpen));
      const inp = addForm.querySelector('input[name="description"]');
      if (isOpen && inp) inp.focus();
    });

    addForm.querySelector('input[name="description"]')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); (addForm.requestSubmit?.() || addForm.submit()); }
    });

    addForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn        = addForm.querySelector('button[type="submit"]');
      const inp        = addForm.querySelector('input[name="description"]');
      const orderInput = addForm.querySelector('input[name="list_order"]');
      const taskInput  = addForm.querySelector('input[name="taskid"]');
      const sel        = addForm.querySelector('#chkAssigned');

      const desc = (inp?.value || '').trim(); if (!desc) { inp?.focus(); return; }
      if (taskInput) taskInput.value = String(taskId || 0);

      // best-effort list_order
      let order = 999;
      const last = list?.querySelector('.checklist-item:last-of-type');
      if (last) {
        const cur = Number(last.getAttribute('data-order') || '0');
        order = Number.isFinite(cur) && cur > 0 ? cur + 1 : 999;
      }
      if (orderInput) orderInput.value = String(order);

      btn && (btn.disabled = true);

      // Build FormData explicitly; force-append assigned as integer (or empty string)
      const fd = new FormData(addForm);
      // Remove any accidental duplicate keys, then append normalized value
      fd.delete('assigned');
      const rawAssigned = sel ? sel.value : '';
      const assignedInt = rawAssigned && !isNaN(rawAssigned) ? String(parseInt(rawAssigned, 10)) : '';
      fd.append('assigned', assignedInt);

      const res = await fetch('<?= site_url('tasks/checklist/add/') ?>' + taskId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      });

      let json = { success: res.ok };
      try {
        const ct = res.headers.get('content-type') || '';
        if (ct.includes('application/json')) json = await res.json();
      } catch(_) {}

      if (json && json.success !== false) {
        const payload = json.data || {};
        const nested  = (payload && payload.data) ? payload.data : payload;

        const newId   = nested.id || json.id || '';
        const descOut = nested.description || desc;

        // Prefer server echo (what got saved). Fallback to our selected option.
        const assignedId     = (nested.assigned != null ? String(nested.assigned) : '') || assignedInt;
        const assignedName   = (nested.assigned_name || '').trim() ||
                               (sel && sel.selectedIndex >= 0 ? (sel.options[sel.selectedIndex].text || '').trim() : '');
        const assignedAvatar = (nested.assigned_avatar || '').trim();

        if (emptyBlock) emptyBlock.hidden = true;
        if (list) list.hidden = false;

        const li = document.createElement('li');
        li.className = 'list-group-item checklist-item';
        li.setAttribute('data-check-id', String(newId));
        li.setAttribute('data-finished', '0');
        if (typeof nested.list_order !== 'undefined') li.setAttribute('data-order', String(nested.list_order));
        else if (order) li.setAttribute('data-order', String(order));

        li.innerHTML = `
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
              <label class="chk-toggle-row toggle flex-grow-1 mb-0" data-check-id="${String(newId)}">
                <input type="checkbox" class="chk-toggle-input" aria-label="Toggle checklist item ${String(newId)}">
                <span class="chk-box" aria-hidden="true">
                  <i class="ti ti-check"></i>
                </span>
                <span class="checklist-text small text-truncate" title="${esc(descOut)}">${esc(descOut)}</span>
              </label>
            </div>
            <div class="d-flex align-items-center gap-2 ms-2">
              ${buildAssigneeHtml(assignedId, assignedName, assignedAvatar)}
              <button type="button" class="btn btn-ssm btn-light-danger delete" data-check-id="${String(newId)}" title="Delete">
                <i class="ti ti-trash"></i>
              </button>
            </div>
          </div>`;

        list && list.appendChild(li);

        // enable tooltip for the new node
        if (window.bootstrap) {
          [].slice.call(li.querySelectorAll('[data-bs-toggle="tooltip"]'))
            .forEach(el => new bootstrap.Tooltip(el));
        }

        addForm.reset();
        addForm.classList.add('d-none');
        addBtn?.setAttribute('aria-expanded', 'false');
        recalc();
      } else {
        alert((json && json.message) || 'Add failed.');
      }

      btn && (btn.disabled = false);
    });
  }

  // keyboard a11y: space/enter on the label group toggles as click
  list && list.addEventListener('keydown', (e) => {
    const label = e.target.closest('.chk-toggle-row');
    if (!label) return;
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); label.click(); }
  });

  recalc();
};

// Init once on server-rendered page load
window.initChecklistPanel(document);
// If you AJAX-replace this partial later, call window.initChecklistPanel(mountEl) right after injection.
</script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap) {
      const triggers = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      triggers.forEach(el => new bootstrap.Tooltip(el));
    }
  });
</script>

