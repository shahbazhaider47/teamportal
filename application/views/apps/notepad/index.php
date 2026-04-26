<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Expected from controller via layouts/master:
 * $page_title, $folders, $notes, $active_folder_id, $q, $status
 */
if (!function_exists('e')) { function e($s){ return html_escape((string)$s); } }

/** Helper: find folder name by id for sidebar labels */
$folderNameById = function($fid) use ($folders){
  foreach ($folders as $f) if ((int)$f['id'] === (int)$fid) return $f['name'];
  return '—';
};
?>

<style>
/* --- Professional Notepad Styling --- */

/* 1. Notes List (Sidebar) */
#notesList .list-group-item {
  transition: background-color 0.15s ease-in-out;
  border-radius: 0; /* Make it a clean list */
  border: 0;
  border-bottom: 1px solid #f0f0f0;
  padding: 10px 12px;
}
#notesList .list-group-item:hover {
  background-color: #f8f9fa; /* Light hover */
}
/* This is the new style for the *selected* note */
#notesList .list-group-item.active {
  background-color: #e9f3ff; /* A soft, professional 'selection' blue */
  color: #0058e6;
  font-weight: 600;
  border-left: 3px solid #0058e6; /* Active indicator */
  padding-left: 9px;
}

/* 2. Editor Menu Bar (Toolbar buttons) */
.notepad-menu-bar {
  background: #fdfdfd;
  padding: 4px 8px;
}

.notepad-menu-bar .btn-group .btn {
  font-size: 0.8rem;
  padding: 2px 8px;
  line-height: 1.2;
}


/* 3. Editor Title Input */
.notepad-title-bar {
  background: #fdfdfd;
}
#note_title {
  font-weight: 600;
  font-size: 1.1rem;
  border: 0 !important;
  box-shadow: none !important;
  padding-left: 12px;
  background: transparent; /* Inherit from parent */
}
#note_title:focus {
  background: #fff; /* White background on focus */
}

/* 4. Editor Text Area (The most important part) */
#note_content {
  /* This is a modern, clean font stack for coding/notes */
  font-family: 'Consolas', 'Menlo', 'Monaco', 'Courier New', monospace;
  font-size: 15px; /* Slightly larger for readability */
  line-height: 1.6;
  padding: 12px; /* Give the text room to breathe */
  background: #ffffff;
  
  /* Remove Bootstrap's focus glow for a cleaner look */
  border-color: transparent !important;
  box-shadow: none !important;
}

/* 5. Editor Status Bar */
.notepad-status-bar {
  background: #fdfdfd; /* Match the menu bar */
  color: #555;
  font-size: 0.8rem;
  padding: 4px 12px;
}
.notepad-status-bar .mx-2 {
  color: #ddd; /* Make separators lighter */
}
</style>


<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= e($page_title) ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">

      <div class="btn-divider"></div>

      <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
              title="Export" data-export-filename="<?= e($page_title ?: 'notepad_export') ?>"
              data-export-target="notesExportTable">
        <i class="ti ti-download"></i>
      </button>

      <button type="button" class="btn btn-light-primary icon-btn b-r-4 btn-print-table" title="Print">
        <i class="ti ti-printer"></i>
      </button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="row g-0" style="min-height: calc(100vh - 220px);">
        <div class="col-md-4 border-end">
          <div class="p-2 d-flex align-items-center justify-content-between border-bottom">
            <div class="fw-semibold. text-primary"><i class="ti ti-notes me-1"></i> My Notes</div>
            <div class="btn-group btn-group-sm">
                
              <button class="btn btn-outline-primary" onclick="openFolderModal()" title="New Folder">
                <i class="ti ti-folder-plus"></i>
              </button>

              <button class="btn btn-outline-primary" onclick="ui.newNote()" title="New (Ctrl+N)">
                <i class="ti ti-plus"></i>
              </button>
              
            </div>
          </div>
          <div class="px-2 py-2 border-bottom">
            <select class="form-select form-select-sm mb-2" onchange="ui.filterByFolder(this.value)">
              <option value="">All folders</option>
              <?php foreach ($folders as $f): ?>
                <option value="<?= (int)$f['id'] ?>" <?= (int)$active_folder_id === (int)$f['id'] ? 'selected':'' ?>>
                  <?= e($f['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="input-group input-group-sm">
              <input type="text" id="quickSearch" class="form-control" placeholder="Filter list..." oninput="ui.quickFilter(this.value)">
              <button class="btn btn-outline-secondary" onclick="document.getElementById('quickSearch').value=''; ui.quickFilter('')">
                <i class="ti ti-x"></i>
              </button>
            </div>
          </div>

          <div id="notesList" class="list-group list-group-flush border-0" style="overflow-y:auto; overflow-x:hidden; max-height: calc(100vh - 365px);">
            <?php if (empty($notes)): ?>
              <div class="p-3 text-muted small text-center">
                <?= $q ? 'No matches found.' : 'No notes in this folder.' ?>
              </div>
            <?php endif; ?>
            
            <?php foreach ($notes as $n): ?>
              <button type="button"
                      class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                      data-note='<?= json_encode($n, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>'
                      onclick="ui.loadIntoEditor(this)">
                <span class="text-truncate" style="max-width: 80%;">
                  <?php if (!empty($n['is_pinned'])): ?><i class="ti ti-pin me-1" title="Pinned"></i><?php endif; ?>
                  <?php if (!empty($n['is_favorite'])): ?><i class="ti ti-star me-1" title="Favorite"></i><?php endif; ?>
                  <?= e($n['title'] ?: '(Untitled)') ?>
                </span>
                <span class="badge bg-light text-dark ms-2"><?= e($folderNameById($n['folder_id'] ?? null)) ?></span>
              </button>
            <?php endforeach; ?>
          </div>

          <table class="table table-sm d-none" id="notesExportTable">
            <thead>
              <tr>
                <th>Title</th><th>Status</th><th>Folder</th><th>Pinned</th><th>Favorite</th><th>Locked</th><th>Color</th><th>Sort</th><th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($notes as $n): ?>
                <tr>
                  <td><?= e($n['title'] ?: '(Untitled)') ?></td>
                  <td><?= e($n['status']) ?></td>
                  <td><?= e($folderNameById($n['folder_id'] ?? null)) ?></td>
                  <td><?= (int)$n['is_pinned'] ?></td>
                  <td><?= (int)$n['is_favorite'] ?></td>
                  <td><?= (int)$n['is_locked'] ?></td>
                  <td><?= e($n['color'] ?? '') ?></td>
                  <td><?= (int)$n['sort_order'] ?></td>
                  <td><?= e($n['updated_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

<div class="col-md-8 d-flex flex-column">
  <div class="border-bottom d-flex align-items-center justify-content-between gap-2 notepad-menu-bar p-2">
    <div class="d-flex align-items-center flex-wrap gap-1">

      <!-- File group -->
      <div class="btn-group btn-group-sm me-2">
        <button class="btn btn-outline-secondary" onclick="ui.newNote();return false;">New</button>
        <button class="btn btn-outline-secondary" onclick="ui.save();return false;">Save</button>
        <button class="btn btn-outline-danger"   onclick="ui.deleteNote();return false;">Delete</button>
        <button class="btn btn-outline-secondary" onclick="ui.archive();return false;">Archive</button>
      </div>

    </div>

      <!-- View / Format group -->
      <div class="btn-group btn-group-sm">
        <button class="btn btn-outline-secondary" onclick="ui.togglePin();return false;">Pin</button>
        <button class="btn btn-outline-secondary" onclick="ui.toggleFav();return false;">Favorite</button>        
        <button class="btn btn-outline-secondary" onclick="ui.pickColor();return false;">Color</button>
        <button class="btn btn-outline-secondary" onclick="ui.moveFolder();return false;">Move</button>
      </div>
     
  </div>


          <form id="editorForm" class="flex-grow-1 d-flex flex-column" onsubmit="return false;">
            <input type="hidden" name="id" id="note_id" value="">
            <input type="hidden" name="folder_id" id="note_folder_id" value="">
            <input type="hidden" name="is_pinned" id="note_is_pinned" value="0">
            <input type="hidden" name="is_favorite" id="note_is_favorite" value="0">
            <input type="hidden" name="is_locked" id="note_is_locked" value="0">
            <input type="hidden" name="status" id="note_status" value="active">
            <input type="hidden" name="color" id="note_color" value="">
            <input type="hidden" name="sort_order" id="note_sort_order" value="0">

            <div class="border-bottom notepad-title-bar py-2">
              <input type="text" class="form-control form-control-sm" id="note_title" name="title" placeholder="(Untitled)">
            </div>

            <div class="flex-grow-1" style="position:relative;">
              <textarea id="note_content" name="content"
                        class="form-control border-0 rounded-0"
                        style="resize:none; height:100%; width:100%; position:absolute; inset:0; white-space:pre; overflow:auto;"
                        oninput="ui.refreshStatus()"
                        onkeyup="ui.refreshCaret(event)"
                        onclick="ui.refreshCaret(event)"></textarea>
            </div>

            <div class="border-top d-flex align-items-center justify-content-between notepad-status-bar">
              <div>
                <span id="statusLnCol">Ln 1, Col 1</span>
                <span class="mx-2">|</span>
                <span id="statusLength">Length: 0</span>
              </div>
              <div>
                <span id="statusEol">CRLF</span>
                <span class="mx-2">|</span>
                <span id="statusEnc">UTF-8</span>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="folderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="folderForm" onsubmit="return ui.saveFolder(event)">
        <div class="modal-header">
          <h6 class="modal-title">Folder</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="folder_id">
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" id="folder_name" maxlength="120" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Icon (optional)</label>
            <input type="text" class="form-control" name="icon" id="folder_icon" placeholder="ti ti-notes">
          </div>
          <div class="mb-2">
            <label class="form-label">Color (optional)</label>
            <input type="text" class="form-control" name="color" id="folder_color" placeholder="#a29bfe">
          </div>
          <div class="mb-2">
            <label class="form-label">Sort Order</label>
            <input type="number" class="form-control" name="sort_order" id="folder_sort_order" value="0">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Save</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="findModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form onsubmit="return ui.findNext(event)">
        <div class="modal-header">
          <h6 class="modal-title">Find</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control" id="findQuery" placeholder="Find text">
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="findMatchCase">
            <label class="form-check-label" for="findMatchCase">Match case</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Find Next</button>
          <button type="button" class="btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="replaceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form onsubmit="return ui.replaceNext(event)">
        <div class="modal-header">
          <h6 class="modal-title">Replace</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Find</label>
          <input type="text" class="form-control" id="replaceFind" placeholder="Find text">
          <label class="form-label mt-2">Replace with</label>
          <input type="text" class="form-control" id="replaceWith" placeholder="Replacement">

          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="replaceMatchCase">
            <label class="form-check-label" for="replaceMatchCase">Match case</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" onclick="return ui.replaceNext(event)">Replace</button>
          <button class="btn btn-outline-primary" onclick="return ui.replaceAll(event)">Replace All</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
/* ---------- tiny helpers (no jQuery) ---------- */
const $  = (sel)=>document.querySelector(sel);
const $$ = (sel)=>document.querySelectorAll(sel);

/* ---------- resilient modal shim ---------- */
function makeModalShim(id){
  const el = document.getElementById(id);
  if (!el) return null;

  // If Bootstrap Modal exists, use it. Otherwise, use a CSS class toggle.
  if (window.bootstrap && typeof bootstrap.Modal === 'function') {
    const inst = new bootstrap.Modal(el);
    return {
      show(){ inst.show(); },
      hide(){ inst.hide(); }
    };
  } else {
    // Barebones fallback
    el.addEventListener('click', e=>{
      if (e.target.matches('[data-bs-dismiss="modal"], .btn-close')) { e.preventDefault(); el.classList.remove('show'); el.style.display='none'; }
    });
    return {
      show(){ el.classList.add('show'); el.style.display='block'; },
      hide(){ el.classList.remove('show'); el.style.display='none'; }
    };
  }
}

const Modals = {
  folder:  makeModalShim('folderModal'),
  find:    makeModalShim('findModal'),
  replace: makeModalShim('replaceModal'),
};

/* ---------- safe fetch JSON ---------- */
async function fetchJSON(url, opts){
  try {
    const res = await fetch(url, opts || {});
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch(parseErr){
      // Server may have returned HTML (403/404 or PHP notice). Surface the body.
      return { ok:false, error: 'Non-JSON response', _raw: text };
    }
  } catch (e){
    return { ok:false, error: e && e.message ? e.message : 'Network error' };
  }
}

/* ---------- core UI ---------- */
const ui = {
  sidebarCollapsed: false,
  wrap: false,
  zoom: 1,
  currentNote: null,

  filterByFolder(fid){
    const params = new URLSearchParams(window.location.search);
    if (fid) params.set('folder_id', String(fid)); else params.delete('folder_id');
    const q = params.toString();
    window.location.href = '<?= site_url('apps/notepad') ?>' + (q ? '?' + q : '');
  },

  quickFilter(q){
    const qq = (q || '').toLowerCase();
    $$('#notesList .list-group-item').forEach(li=>{
      const data = li.getAttribute('data-note');
      if (!data) return;
      let n; try { n = JSON.parse(data); } catch { n = {}; }
      const hay = ((n.title||'') + ' ' + (n.content||'')).toLowerCase();
      li.style.display = hay.indexOf(qq) >= 0 ? '' : 'none';
    });
  },

  /* load/editor */
  loadIntoEditor(btn){
    let n; try { n = JSON.parse(btn.getAttribute('data-note') || '{}'); } catch { n = {}; }
    this.currentNote = n;

    // NEW: Handle active state
    $$('#notesList .list-group-item.active').forEach(el => el.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const set = (id,val)=>{ const el=$(id); if (el) el.value = val==null ? '' : val; };
    set('#note_id',           n.id);
    set('#note_title',         n.title);
    set('#note_content',       n.content);
    set('#note_folder_id',     n.folder_id);
    set('#note_is_pinned',     n.is_pinned ? 1 : 0);
    set('#note_is_favorite', n.is_favorite ? 1 : 0);
    set('#note_is_locked',     n.is_locked ? 1 : 0);
    set('#note_status',        n.status || 'active');
    set('#note_color',         n.color);
    set('#note_sort_order',  n.sort_order || 0);

    const badgeStatus = $('#noteStatusBadge');
    if (badgeStatus) badgeStatus.textContent = (n.status||'active').replace(/^\w/, c=>c.toUpperCase());
    const fb = btn.querySelector('.badge');
    const badgeFolder = $('#noteFolderBadge');
    if (badgeFolder) badgeFolder.textContent = fb ? fb.textContent : '—';

    this.refreshStatus();
  },

  newNote(){
    const set = (id,val)=>{ const el=$(id); if (el) el.value = val==null ? '' : val; };
    this.currentNote = null;
    
    // NEW: Deselect all notes in the list
    $$('#notesList .list-group-item.active').forEach(el => el.classList.remove('active'));

    set('#note_id','');
    set('#note_title','');
    set('#note_content','');
    set('#note_folder_id','<?= (int)($active_folder_id ?? 0) ?>');
    set('#note_is_pinned',0);
    set('#note_is_favorite',0);
    set('#note_is_locked',0);
    set('#note_status','active');
    set('#note_color','');
    set('#note_sort_order',0);
    const badgeStatus = $('#noteStatusBadge'); if (badgeStatus) badgeStatus.textContent = 'Active';
    const badgeFolder = $('#noteFolderBadge'); if (badgeFolder) badgeFolder.textContent = '—';
    this.refreshStatus();
    const titleEl = $('#note_title'); if (titleEl) titleEl.focus();
  },

  async save(){
    const form = $('#editorForm'); if (!form) return alert('Editor not found.');
    // disallow saving locked note
    const locked = parseInt($('#note_is_locked')?.value || '0', 10) === 1;
    if (locked) {
      alert('Note is locked. Unlock it from the "Edit" menu to make changes.');
      return;
    }

    const fd = new FormData(form);
    const j = await fetchJSON('<?= site_url('apps/notepad/note_save') ?>', { method:'POST', body: fd });
    if (j.ok) { 
      // Instead of full reload, just redirect to get new list
      const params = new URLSearchParams(window.location.search);
      // Ensure we land in the correct folder
      if (fd.get('folder_id')) params.set('folder_id', fd.get('folder_id'));
      const q = params.toString();
      window.location.href = '<?= site_url('apps/notepad') ?>' + (q ? '?' + q : '');
    }
    else { console.error(j._raw || j.error); alert(j.error || 'Save failed'); }
  },

  async duplicate(){
    const idEl = $('#note_id'); if (!idEl) return;
    const original = idEl.value;
    if (!original) return alert('Save the note first to duplicate it.');
    
    idEl.value = ''; // This flags it as a 'new' note for the save function
    const titleEl = $('#note_title');
    if (titleEl && !titleEl.value.includes('(Copy)')) {
      titleEl.value = titleEl.value + ' (Copy)';
    }
    await this.save();
    // No need to restore original ID, page will reload.
  },

  async deleteNote(){
    const id = $('#note_id')?.value;
    if (!id) return alert('Open a note first.');
    if (!confirm('Are you sure you want to permanently delete this note?')) return;
    const j = await fetchJSON('<?= site_url('apps/notepad/note_delete') ?>/' + encodeURIComponent(id), { method:'POST' });
    if (j.ok) window.location.href = '<?= site_url('apps/notepad') ?>';
    else { console.error(j._raw || j.error); alert(j.error || 'Delete failed'); }
  },

  async archive(){
    const id = $('#note_id')?.value;
    if (!id) return alert('Open a note first.');
    const j = await fetchJSON('<?= site_url('apps/notepad/note_archive') ?>/' + encodeURIComponent(id), { method:'POST' });
    if (j.ok) window.location.href = '<?= site_url('apps/notepad') ?>';
    else { console.error(j._raw || j.error); alert(j.error || 'Archive failed'); }
  },

  async togglePin(){
    const id = $('#note_id')?.value; if (!id) return alert('Open a note first.');
    const current = parseInt($('#note_is_pinned')?.value || '0', 10);
    const fd = new FormData(); fd.append('value', current ? 0 : 1);
    const j = await fetchJSON('<?= site_url('apps/notepad/note_toggle_pin') ?>/' + encodeURIComponent(id), { method:'POST', body: fd });
    if (j.ok) window.location.reload(); else { console.error(j._raw || j.error); alert(j.error || 'Update failed'); }
  },

  async toggleFav(){
    const id = $('#note_id')?.value; if (!id) return alert('Open a note first.');
    const current = parseInt($('#note_is_favorite')?.value || '0', 10);
    const fd = new FormData(); fd.append('value', current ? 0 : 1);
    const j = await fetchJSON('<?= site_url('apps/notepad/note_toggle_favorite') ?>/' + encodeURIComponent(id), { method:'POST', body: fd });
    if (j.ok) window.location.reload(); else { console.error(j._raw || j.error); alert(j.error || 'Update failed'); }
  },

  async toggleLock(){
    const id = $('#note_id')?.value; if (!id) return alert('Open a note first.');
    const current = parseInt($('#note_is_locked')?.value || '0', 10);
    const fd = new FormData(); fd.append('value', current ? 0 : 1);
    const j = await fetchJSON('<?= site_url('apps/notepad/note_lock') ?>/' + encodeURIComponent(id), { method:'POST', body: fd });
    if (j.ok) window.location.reload(); else { console.error(j._raw || j.error); alert(j.error || 'Update failed'); }
  },

  pickColor(){
    const cur = $('#note_color')?.value || '';
    const v = prompt('Enter hex color (e.g., #ffeaa7):', cur);
    if (v !== null) { const c=$('#note_color'); if (c) c.value = v.trim(); }
  },

  moveFolder(){
    const cur = $('#note_folder_id')?.value || '';
    const v = prompt('Move to folder ID (blank for none):', cur);
    if (v !== null) { const f=$('#note_folder_id'); if (f) f.value = (v || '').trim(); }
  },

  properties(){
    const get=(id)=>$(id)?.value || '';
    alert(`Note #${get('#note_id')||'(new)'}
    Status: ${get('#note_status')}
    Pinned: ${get('#note_is_pinned')}
    Favorite: ${get('#note_is_favorite')}
    Locked: ${get('#note_is_locked')}
    Color: ${get('#note_color')}
    Sort: ${get('#note_sort_order')}`);
  },

  /* folder modal */
  openFolderModal(row){
    // ensure modal exists even without bootstrap
    if (!Modals.folder) return alert('Folder modal not available on this page.');
    const form = $('#folderForm');
    if (form) form.reset();
    if (row && typeof row === 'object'){
      $('#folder_id').value       = row.id || '';
      $('#folder_name').value     = row.name || '';
      $('#folder_icon').value     = row.icon || '';
      $('#folder_color').value    = row.color || '';
      $('#folder_sort_order').value= row.sort_order || 0;
    }
    Modals.folder.show();
  },

  async saveFolder(e){
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    const j = await fetchJSON('<?= site_url('apps/notepad/folder_save') ?>', { method:'POST', body: fd });
    if (j.ok) window.location.reload(); else { console.error(j._raw || j.error); alert(j.error || 'Error'); }
    return false;
  },

  /* status line */
  refreshStatus(){
    const ta = $('#note_content'); if (!ta) return;
    const len = (ta.value || '').length;
    const el = $('#statusLength'); if (el) el.textContent = 'Length: ' + len;
  },

  refreshCaret(){
    try {
      const ta = $('#note_content'); if (!ta) return;
      const pos = ta.selectionStart || 0;
      const text = ta.value || '';
      const parts = text.substring(0, pos).split('\n');
      const ln = parts.length;
      const col = parts[parts.length - 1].length + 1;
      const el = $('#statusLnCol'); if (el) el.textContent = `Ln ${ln}, Col ${col}`;
    } catch (e) {
      // ignore errors, e.g. if textarea is not focused
    }
  }
};

/* expose for inline onclicks and external scripts */
window.ui = ui;
window.openFolderModal = (row)=> ui.openFolderModal(row || null);

/* keyboard shortcuts */
document.addEventListener('keydown', function(e){
  const k = (e.key||'').toLowerCase();
  if ((e.ctrlKey || e.metaKey) && k === 's') { e.preventDefault(); ui.save(); }
  if ((e.ctrlKey || e.metaKey) && k === 'n') { e.preventDefault(); ui.newNote(); }
  if ((e.ctrlKey || e.metaKey) && k === 'f') { e.preventDefault(); if (Modals.find) Modals.find.show(); }
  if ((e.ctrlKey || e.metaKey) && k === 'h') { e.preventDefault(); if (Modals.replace) Modals.replace.show(); }
});

/* boot: auto-load first note if none selected */
(function boot(){
  try {
    const currentId = $('#note_id')?.value;
    if (!currentId) {
      const first = document.querySelector('#notesList .list-group-item');
      if (first) {
        first.click(); // This will call loadIntoEditor and set it to active
      } else {
        // No notes, just fire up a new one
        ui.newNote();
      }
    }
    ui.refreshStatus();
  } catch (e) {
    console.error('Boot error:', e);
  }
})();
</script>