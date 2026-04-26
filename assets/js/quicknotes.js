const esc = s => (s ?? '').replace(/[&<>"']/g, m => (
  { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]
));

(function(){
  window.initQuickNoteModal = function initQuickNoteModal(){
    const modal = document.getElementById('qn-notepad-modal');
    if (!modal || modal.dataset.qnBound === '1') return;
    modal.dataset.qnBound = '1';

    const dialog   = modal.querySelector('.qn-modal__dialog');
    const empty    = modal.querySelector('#qn-empty');
    const list     = modal.querySelector('#qn-list');
    const editor   = modal.querySelector('#qn-editor');
    const btnNew   = modal.querySelector('#qn-create');
    const btnSave  = modal.querySelector('#qn-save');
    const btnCancel= modal.querySelector('#qn-cancel');

    const notesContainer  = document.getElementById('qn-notes-container');
    const folderModal     = document.getElementById('qn-folder-modal');

    // Bootstrap-like controls
    const bsBackdropRaw  = (modal.getAttribute('data-bs-backdrop')  || 'true').toLowerCase();  // 'true'|'false'|'static'
    const bsKeyboardRaw  = (modal.getAttribute('data-bs-keyboard')  || 'true').toLowerCase();
    const allowBackdropClose = !(bsBackdropRaw === 'false' || bsBackdropRaw === 'static');
    const allowEscClose      = !(bsKeyboardRaw === 'false');
    
    // Same logic for the secondary modal (fallbacks to primary flags if not set)
    const fBackdropRaw  = (folderModal?.getAttribute('data-bs-backdrop') || bsBackdropRaw).toLowerCase();
    const fKeyboardRaw  = (folderModal?.getAttribute('data-bs-keyboard') || bsKeyboardRaw).toLowerCase();
    const fAllowBackdropClose = !(fBackdropRaw === 'false' || fBackdropRaw === 'static');
    const fAllowEscClose      = !(fKeyboardRaw === 'false');

    let notes = [];
    let folders = [];
    let currentNote = null;
    let activeFolderId = '';
    let activeStatus   = 'active';
    // at top of IIFE (near other lets)
    let notesAbortCtrl = null;
    let foldersAbortCtrl = null;

    // --- FULL NOTE CACHE (merge-friendly) ---
    const cache = new Map(); // id -> richest known note
    
    function upsertCache(n) {
      if (!n || n.id == null) return null;
      const id = String(n.id);
      const prev = cache.get(id) || {};
      const merged = { ...prev, ...n };
      cache.set(id, merged);
      return merged;
    }
    
    function getFromCache(id) {
      return cache.get(String(id)) || null;
    }

    function show(){ modal.setAttribute('aria-hidden','false'); lockPageScroll(); loadFolders(); loadNotes(); }
    function hide(){ modal.setAttribute('aria-hidden','true'); resetEditor(); unlockPageScroll(); }
    window.openQuickNoteModal = show;
    window.closeQuickNoteModal = hide;

let __qnScrollY = 0;
function lockPageScroll(){
  if (document.body.dataset.qnLocked === '1') return;
  __qnScrollY = window.scrollY || document.documentElement.scrollTop || 0;
  document.documentElement.classList.add('qn-locked');
  document.body.dataset.qnLocked = '1';
  document.body.style.position = 'fixed';
  document.body.style.top = `-${__qnScrollY}px`;
  document.body.style.left = '0';
  document.body.style.right = '0';
  document.body.style.width = '100%';
}

function unlockPageScroll(){
  if (document.body.dataset.qnLocked !== '1') return;
  document.documentElement.classList.remove('qn-locked');
  document.body.dataset.qnLocked = '0';
  const y = Math.abs(parseInt(document.body.style.top || '0', 10)) || __qnScrollY;
  document.body.style.position = '';
  document.body.style.top = '';
  document.body.style.left = '';
  document.body.style.right = '';
  document.body.style.width = '';
  window.scrollTo(0, y);
}

    const SAFE_JSON = async (res) => {
      const txt = await res.text();
      try { return JSON.parse(txt); } catch(e){ throw new Error('Non-JSON response'); }
    };

    async function getJSON(url){
      const res = await fetch(url, { credentials:'same-origin', cache:'no-store' });
      const j = await SAFE_JSON(res);
      if (!res.ok || j.ok === false) throw new Error(j.error || `HTTP ${res.status}`);
      return j;
    }
    async function postForm(url, formData){
      const res = await fetch(url, { method:'POST', body: formData, credentials:'same-origin' });
      const j = await SAFE_JSON(res);
      if (!res.ok || j.ok === false) throw new Error(j.error || `HTTP ${res.status}`);
      return j;
    }

    /* Loaders */
    async function loadFolders(){
      try {
        const j = await getJSON('<?= site_url('apps/notepad/folder_list') ?>');
        folders = Array.isArray(j.folders) ? j.folders : [];
      } catch(e){ folders = []; console.warn('folder_list:', e.message); }
      renderFolderOptions();
      updateToolbarButtons();
    }

async function loadNotes({ signal } = {}){
  try {
    const params = new URLSearchParams();
    if (activeStatus)   params.set('status', activeStatus);
    if (activeFolderId) params.set('folder_id', activeFolderId);

    const endpoint = '<?= site_url('apps/notepad/note_list') ?>' + (params.toString() ? ('?' + params.toString()) : '');
    const j = await getJSON(endpoint, { signal });

    if (!Array.isArray(j.notes)) throw new Error('Bad payload: notes is not an array');

    // Merge and precompute timestamps once (avoid Date parse in sort loop)
    notes = j.notes.map(r => {
      const merged = upsertCache({ ...(getFromCache(r.id) || {}), ...r });
      merged.__ts = Date.parse(merged.updated_at || merged.created_at || '') || 0;
      return merged;
    });

    renderNotes(); // this will swap out skeletons

  } catch(e){
    if (e.name === 'AbortError') return; // ignore canceled load
    console.warn('note_list error:', e.message);
    notes = (notes || []).map(n => upsertCache(n));
    renderNotes();
  }
}



document.getElementById('qn-toggle-archived')?.addEventListener('click', async ()=>{
  activeStatus = (activeStatus === 'archived') ? 'active' : 'archived';
  const btn = document.getElementById('qn-toggle-archived');
  if (btn){
    btn.innerHTML = activeStatus === 'archived'
      ? '<i class="ti ti-notes"></i> Show Active'
      : '<i class="ti ti-archive"></i> Show Archived';
  }
  await loadNotes();
});


// utility
async function getJSON(url, { signal } = {}){
  const res = await fetch(url, { credentials:'same-origin', cache:'no-cache', signal });
  const txt = await res.text();
  let j;
  try { j = JSON.parse(txt); } catch { throw new Error('Non-JSON response'); }
  if (!res.ok || j.ok === false) throw new Error(j.error || `HTTP ${res.status}`);
  return j;
}
async function postForm(url, formData, { signal } = {}){
  const res = await fetch(url, { method:'POST', body: formData, credentials:'same-origin', signal });
  const txt = await res.text();
  let j;
  try { j = JSON.parse(txt); } catch { throw new Error('Non-JSON response'); }
  if (!res.ok || j.ok === false) throw new Error(j.error || `HTTP ${res.status}`);
  return j;
}

// small helper: optimistic skeletons while loading
function renderSkeletonNotes(count = 8){
  const c = document.getElementById('qn-notes-container');
  empty.hidden = true; list.hidden = false; editor.hidden = true;
  const frag = document.createDocumentFragment();
  for (let i=0;i<count;i++){
    const d = document.createElement('div');
    d.className = 'qn-note-item qn-skeleton';
    d.innerHTML = `
      <div class="qn-note-content">
        <div class="qn-skel-line qn-skel-title"></div>
        <div class="qn-skel-line"></div>
        <div class="qn-skel-line short"></div>
      </div>
      <div class="qn-note-actions"></div>`;
    frag.appendChild(d);
  }
  c.innerHTML = '';
  c.appendChild(frag);
}


function lockPageScroll(){
  if (document.body.dataset.qnLocked === '1') return;
  __qnScrollY = window.scrollY || document.documentElement.scrollTop || 0;
  document.documentElement.classList.add('qn-locked');
  document.body.dataset.qnLocked = '1';
  document.body.classList.add('qn-fixed');
  document.body.style.top = `-${__qnScrollY}px`;
}
function unlockPageScroll(){
  if (document.body.dataset.qnLocked !== '1') return;
  document.documentElement.classList.remove('qn-locked');
  document.body.dataset.qnLocked = '0';
  document.body.classList.remove('qn-fixed');
  const y = Math.abs(parseInt(document.body.style.top || '0', 10)) || __qnScrollY;
  document.body.style.top = '';
  window.scrollTo(0, y);
}


// load both in parallel on open
function show(){
  modal.setAttribute('aria-hidden','false');
  lockPageScroll();

  // cancel in-flight
  foldersAbortCtrl?.abort(); notesAbortCtrl?.abort();
  foldersAbortCtrl = new AbortController();
  notesAbortCtrl   = new AbortController();

  // show skeletons instantly
  renderSkeletonNotes(6);

  // kick both requests in parallel
  const foldersP = getJSON('<?= site_url('apps/notepad/folder_list') ?>', { signal: foldersAbortCtrl.signal })
    .then(j => { folders = Array.isArray(j.folders) ? j.folders : []; renderFolderOptions(); })
    .catch(()=> { folders = []; });

  const notesP = loadNotes({ signal: notesAbortCtrl.signal });

  Promise.allSettled([foldersP, notesP]).then(()=> updateToolbarButtons());
}


// controls
const VISIBLE_MAX = 60;        // how many DOM rows we keep
const BATCH_SIZE  = 20;        // how many per frame to append

function renderNotes(){
  try {
    const addBtn = document.getElementById('qn-list-add');

    if (!Array.isArray(notes) || notes.length === 0){
      if (activeStatus === 'archived'){
        empty.hidden = true; list.hidden = false; editor.hidden = true;
        addBtn && (addBtn.hidden = true);
        const notesContainer = document.getElementById('qn-notes-container');
        notesContainer.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center py-4">
          <div class="small text-muted mb-2"><i class="ti ti-archive me-1"></i> No archived notes found</div>
        </div>`;
        return;
      }
      empty.hidden = false; list.hidden = true; editor.hidden = true;
      return;
    }

    empty.hidden = true; list.hidden = false; editor.hidden = true;
    addBtn && (addBtn.hidden = (activeStatus === 'archived'));

    const notesContainer = document.getElementById('qn-notes-container');
    notesContainer.innerHTML = '';

    // sort once using precomputed ts
    const sorted = [...notes].sort((a,b)=>{
      if ((a.is_pinned|0) !== (b.is_pinned|0)) return (b.is_pinned|0) - (a.is_pinned|0);
      return (b.__ts || 0) - (a.__ts || 0);
    });

    // virtualization state
    let rendered = 0;
    const totalToRender = Math.min(sorted.length, VISIBLE_MAX);

    const pump = () => {
      const frag = document.createDocumentFragment();
      const end = Math.min(rendered + BATCH_SIZE, totalToRender);
      for (let i=rendered; i<end; i++){
        frag.appendChild(buildNoteRow(sorted[i]));
      }
      notesContainer.appendChild(frag);
      rendered = end;
      if (rendered < totalToRender) requestAnimationFrame(pump);
    };
    requestAnimationFrame(pump);

    // lazy-append more on scroll if list is larger than VISIBLE_MAX
    let inflight = false;
    notesContainer.onscroll = () => {
      if (rendered >= sorted.length || inflight) return;
      const nearBottom = notesContainer.scrollTop + notesContainer.clientHeight >= notesContainer.scrollHeight - 120;
      if (!nearBottom) return;
      inflight = true;
      requestAnimationFrame(() => {
        const frag = document.createDocumentFragment();
        const end = Math.min(rendered + BATCH_SIZE, sorted.length);
        for (let i=rendered; i<end; i++){
          frag.appendChild(buildNoteRow(sorted[i]));
        }
        notesContainer.appendChild(frag);
        rendered = end;
        inflight = false;
      });
    };

  } catch (err) {
    const notesContainer = document.getElementById('qn-notes-container');
    empty.hidden = true; list.hidden = false; editor.hidden = true;
    notesContainer.innerHTML = `<div class="alert alert-danger m-2">Failed to render notes: ${esc(err.message || 'Unknown error')}</div>`;
    console.error('[notepad] renderNotes failed', err);
  }
}

// build a single row (no heavy box-shadows!)
function buildNoteRow(note){
  const row = document.createElement('div');
  row.className = 'qn-note-item' + ((+note.is_pinned) ? ' qn-note-item--pinned' : '');

  // Use border-left instead of inset box-shadow (cheaper to paint)
  const stripe = note.color || note.folder_color || '';
  if (stripe){
    row.style.boxShadow     = 'none';
    row.style.borderLeft    = '8px solid ' + stripe;
    row.style.borderRight   = '8px solid ' + stripe;
    row.style.borderTop     = '3px solid ' + stripe;
    row.style.borderBottom  = '3px solid ' + stripe;    
  }

  const preview = (note.content || '').replace(/<[^>]*>/g,'').slice(0,100);
  const createdChip = `<span class="qn-badge"><i class="ti ti-clock"></i> ${fmtDate(note.updated_at || note.created_at)}</span>`;
  const statBadge = statusBadge(note.status);
  const folderBadge = `<span class="qn-badge">${note.folder_icon ? `<i class="${note.folder_icon}"></i>` : `<i class="ti ti-folder-off"></i>`} ${note.folder_name ? note.folder_name : 'No Folder'}</span>`;
  const pinIcon = (+note.is_pinned) ? '<i class="ti ti-pin" title="Pinned" style="font-size:14px"></i>' : '';
  const isArchived = String(note.status || '').toLowerCase() === 'archived';
  const archiveTitle = isArchived ? 'Restore' : 'Archive';
  const archiveIcon  = isArchived ? 'ti ti-archive-off' : 'ti ti-archive';
  const nextStatus   = isArchived ? 'active' : 'archived';

  row.innerHTML = `
    <div class="qn-note-content">
      <div class="qn-note-title">${pinIcon}${esc(note.title) || '(Untitled)'}${+note.is_favorite ? '<span title="Favorite" class="qn-star">★</span>' : ''}</div>
      <div class="qn-note-preview">${preview}</div>
      <div class="qn-note-meta">${createdChip}${statBadge}${folderBadge}</div>
    </div>
    <div class="qn-note-actions">
      <button class="btn btn-light-secondary icon-btn b-r-4 qn-archive" type="button" title="${archiveTitle}" data-id="${note.id}" data-next="${nextStatus}"><i class="${archiveIcon}"></i></button>
      <button class="btn btn-light-danger icon-btn b-r-4 qn-delete" type="button" title="Delete" data-id="${note.id}"><i class="ti ti-trash"></i></button>
    </div>`;

  row.addEventListener('click', (e)=>{ if (!e.target.closest('.qn-note-actions')) openNote(note.id); });
  row.querySelector('.qn-delete').addEventListener('click', (e)=>{ e.stopPropagation(); deleteNote(note.id); });
  row.querySelector('.qn-archive').addEventListener('click', async (e)=>{
    e.stopPropagation();
    const id   = e.currentTarget.getAttribute('data-id');
    const next = e.currentTarget.getAttribute('data-next') || (isArchived ? 'active' : 'archived');
    await archiveNoteLocalFirst(id, next);
  });

  return row;
}




async function archiveNoteLocalFirst(id, nextStatus){
  const idx = notes.findIndex(n => String(n.id) === String(id));
  if (idx === -1) return;

  // Snapshot to revert on failure (prefer cached full view)
  const prevFull = { ...getFromCache(id), ...notes[idx] };

  // Optimistic local flip
  const flipped = { ...notes[idx], status: nextStatus };
  notes[idx] = upsertCache(flipped);

  // If item no longer belongs in current filter, remove it from visible list only
  if (activeStatus === 'archived' && nextStatus === 'active') notes.splice(idx, 1);
  if (activeStatus === 'active'   && nextStatus === 'archived') notes.splice(idx, 1);

  renderNotes();

  // Server update: prefer dedicated endpoint; fall back to full note_save
  const fd = new FormData(); fd.set('value', nextStatus);

  try {
    let usedExplicit = true;
    try {
      await postForm('<?= site_url('apps/notepad/note_archive/') ?>' + id, fd);
    } catch {
      usedExplicit = false;
    }

    if (!usedExplicit) {
      // Build a FULL payload from cache to avoid wiping fields
      const full = getFromCache(id) || prevFull || { id };
      const fd2 = new FormData();
      fd2.set('id', String(id));
      fd2.set('status', nextStatus);
      fd2.set('title',       full.title ?? '');
      fd2.set('content',     full.content ?? '');
      if (full.folder_id != null)   fd2.set('folder_id', String(full.folder_id));
      fd2.set('is_pinned',   full.is_pinned ? '1' : '0');
      fd2.set('is_favorite', full.is_favorite ? '1' : '0');
      if (full.color != null)       fd2.set('color', String(full.color));
      if (full.sort_order != null)  fd2.set('sort_order', String(full.sort_order));
      if (full.is_locked != null)   fd2.set('is_locked', full.is_locked ? '1' : '0');

      await postForm('<?= site_url('apps/notepad/note_save') ?>', fd2);
    }

    // Cache already holds the correct status; no forced reload needed.

  } catch (err) {
    // Revert on failure
    const rIdx = notes.findIndex(n => String(n.id) === String(id));
    if (rIdx !== -1) notes[rIdx] = upsertCache(prevFull);
    upsertCache(prevFull);
    renderNotes();
    alert(err?.message || 'Unable to update archive state');
  }
}




const dateCache = new Map();
const fmtDate = (iso) => {
  if (!iso) return '—';
  if (dateCache.has(iso)) return dateCache.get(iso);
  const d = new Date(iso);
  const s = isNaN(d) ? '—' : d.toLocaleString(undefined, {
    day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'
  });
  dateCache.set(iso, s);
  return s;
};


const statusBadge = (status) => {
  const s = String(status || '').toLowerCase();
  const map = {
    active:   { cls: 'bg-success-subtle text-success',   label: 'Active'   },
    archived: { cls: 'bg-secondary-subtle text-secondary', label: 'Archived' },
    draft:    { cls: 'bg-warning-subtle text-warning',   label: 'Draft'    }
  };
  const m = map[s] || { cls: 'bg-secondary-subtle text-secondary', label: s || '—' };
  return `<span class="qn-badge ${m.cls}">${m.label}</span>`;
};

// end code here 

function renderFolderOptions(){
  const wrap = document.querySelector('.qn-folders');
  if (!wrap) return; // dropdown not in DOM yet

  const noFolder = wrap.querySelector('.qn-folder-option[data-folder-id=""]');
  wrap.innerHTML = '';
  if (noFolder) wrap.appendChild(noFolder);

  folders.forEach(f=>{
    const el = document.createElement('div');
    el.className = 'qn-folder-option';
    el.dataset.folderId = f.id;
    el.innerHTML = `
      <div class="qn-folder-icon">
        ${f.icon ? `<i class="${f.icon}"></i>` :
        `<i class="ti ti-folder"></i>`}
      </div>
      <span>${esc(f.name)}</span>
    `;
    el.addEventListener('click', ()=>{
      const fld = document.getElementById('note_folder_id');
      if (fld) fld.value = f.id;
      updateToolbarButtons();
      document.getElementById('qn-folder-dd')?.classList.remove('open');
    });
    wrap.appendChild(el);
  });
}


function openFolderModal() {
if (folderModal.getAttribute('aria-hidden') === 'false') return;    
  // close any open dropdowns in the primary
  document.querySelectorAll('.qn-dropdown').forEach(dd=>dd.classList.remove('open'));
  // show folder modal
  folderModal.setAttribute('aria-hidden','false');
  // mark primary as underlay and lock scroll
  modal.classList.add('qn-underlay');
  document.documentElement.style.overflow = 'hidden';
  document.body.style.overflow = 'hidden';
  // focus first input for accessibility
  folderModal.querySelector('#folder_name')?.focus();
}

function closeFolderModal() {
  folderModal.setAttribute('aria-hidden','true');
  modal.classList.remove('qn-underlay');
  document.documentElement.style.overflow = '';
  document.body.style.overflow = '';
}


    function renderFolders(){
      const wrap = document.getElementById('qn-folders-container');
      wrap.innerHTML = '';
      if (!folders.length){
        wrap.innerHTML = '<div class="text-muted" style="padding:8px 12px;">No folders yet</div>';
        return;
      }
      folders.forEach(f=>{
        const item = document.createElement('div');
        item.className = 'qn-folder-row';
        item.innerHTML = `
          <div class="qn-folder-chip" style="border-left:4px solid ${f.color || '#ddd'}">
            <span class="qn-folder-icon">
              ${f.icon ? `<i class="${f.icon}"></i>` :
              `<i class="ti ti-folder"></i>`}
            </span>
            <span class="qn-folder-name">${esc(f.name)}</span>
          </div>
        `;
        item.addEventListener('click', async ()=>{
          activeFolderId = f.id;
          await loadNotes();
        });
        wrap.appendChild(item);
      });
    }

    // Icon picker
// NEW: bind inside the folder modal only
folderModal?.querySelectorAll('#qn-folder-icons .qn-icon-pill').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    folderModal.querySelectorAll('#qn-folder-icons .qn-icon-pill').forEach(b=>b.classList.remove('selected'));
    btn.classList.add('selected');
    folderModal.querySelector('#folder_icon').value = btn.dataset.icon;
  });
});

    /* Editor */
function openNote(id){
  currentNote = notes.find(n => n.id == id) || null;
  if (!currentNote){ resetEditor(); return; }
  currentNote = upsertCache(currentNote); // ensure cache has the richest copy

  document.getElementById('note_id').value          = currentNote.id;
  document.getElementById('note_title').value       = currentNote.title || '';
  document.getElementById('note_content').value     = currentNote.content || '';
  document.getElementById('note_folder_id').value   = currentNote.folder_id || '';
  document.getElementById('note_is_pinned').value   = currentNote.is_pinned ? '1' : '0';
  document.getElementById('note_is_favorite').value = currentNote.is_favorite ? '1' : '0';
  document.getElementById('note_color').value       = currentNote.color || '';
  document.getElementById('note_status').value      = currentNote.status || 'active'; // ensure hidden status reflects current
  updateToolbarButtons();

  empty.hidden = true; list.hidden = true; editor.hidden = false;
  setTimeout(()=>document.getElementById('note_title').focus(), 60);
}


    function resetEditor(){
      currentNote = null;
    
      const ids = ['note_id','note_title','note_content','note_folder_id','note_color'];
      ids.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        // inputs and textarea both have .value
        el.value = '';
      });
    
      const pinEl = document.getElementById('note_is_pinned');
      const favEl = document.getElementById('note_is_favorite');
      if (pinEl) pinEl.value = '0';
      if (favEl) favEl.value = '0';
    
      updateToolbarButtons();
    
      if (!Array.isArray(notes) || notes.length === 0){
        empty.hidden = false; 
        list.hidden  = true; 
        editor.hidden= true;
      } else {
        empty.hidden = true;  
        list.hidden  = false; 
        editor.hidden= true;
      }
    }


function updateToolbarButtons(){
  const isPinned   = document.getElementById('note_is_pinned').value === '1';
  const isFavorite = document.getElementById('note_is_favorite').value === '1';
  const color      = document.getElementById('note_color').value || '#ffffff';
  const folderId   = document.getElementById('note_folder_id').value;

  const pinBtn = document.getElementById('qn-pin-btn');
  const favBtn = document.getElementById('qn-favorite-btn');
  if (pinBtn){ pinBtn.classList.toggle('active', isPinned); pinBtn.querySelector('.qn-ic') && (pinBtn.querySelector('.qn-ic').style.fill = isPinned ? '#056464' : ''); }
  if (favBtn){ favBtn.classList.toggle('active', isFavorite); favBtn.querySelector('.qn-ic') && (favBtn.querySelector('.qn-ic').style.fill = isFavorite ? '#056464' : ''); }

  const folderLabel = document.getElementById('qn-folder-label');
  const folderIconEl = document.getElementById('qn-folder-icon');

  let iconClass = 'ti ti-folder-off';
  let labelText = 'No Folder';

  if (folderId){
    const f = folders.find(x => String(x.id) === String(folderId));
    labelText = f ? (f.name || 'Select Folder') : 'Select Folder';
    iconClass = (f && f.icon && String(f.icon).trim() !== '') ? String(f.icon).trim() : 'ti ti-folder';
  }

  if (folderLabel) folderLabel.textContent = labelText;
  if (folderIconEl) folderIconEl.className = iconClass + ' me-2';

  // reflect selected color in the palette selection UI
  document.querySelectorAll('.qn-color-option').forEach(opt => {
    opt.classList.toggle('selected', (opt.dataset.color || '') === (color || ''));
  });

  // === NEW: apply color theme to the editor and the color button swatch ===
  const editorEl  = document.getElementById('qn-editor');
  const colorBtn  = document.getElementById('qn-color-btn');
  if (editorEl) {
    editorEl.style.setProperty('--qn-note-color', color);
    editorEl.style.setProperty('--qn-note-bg',    color);   // textarea background
    editorEl.style.setProperty('--qn-note-title', color);   // title input background
  }
  if (colorBtn) {
    colorBtn.style.setProperty('--qn-note-color', color);   // swatch in ::after
  }
}


    /* CRUD: Notes */
async function saveNote(){
  const form = document.getElementById('qn-editor');
  const fd = new FormData(form);

  // Optimistically update cache with what user sees in editor
  const optimistic = {
    id:          form.querySelector('#note_id').value,
    title:       form.querySelector('#note_title').value,
    content:     form.querySelector('#note_content').value,
    folder_id:   form.querySelector('#note_folder_id').value,
    is_pinned:   form.querySelector('#note_is_pinned').value === '1' ? 1 : 0,
    is_favorite: form.querySelector('#note_is_favorite').value === '1' ? 1 : 0,
    color:       form.querySelector('#note_color').value,
    status:      form.querySelector('#note_status').value || 'active'
  };
  upsertCache(optimistic);

  try {
    await postForm('<?= site_url('apps/notepad/note_save') ?>', fd);
    await loadNotes();
    resetEditor();
  } catch(err){
    alert(err.message || 'Save failed');
  }
}


    async function deleteNote(id){
      if (!confirm('Delete this note?')) return;
      try {
        await postForm('<?= site_url('apps/notepad/note_delete/') ?>' + id, new FormData());
        notes = notes.filter(n => String(n.id) !== String(id));
        renderNotes();
      } catch(err){ alert(err.message || 'Unable to delete'); }
    }

    /* UI wiring */
modal.addEventListener('click', (e)=>{
  if (folderModal.getAttribute('aria-hidden') === 'false') return;

  const isBackdrop = e.target.classList.contains('qn-modal__backdrop');
  const isCloseBtn = e.target.matches('.qn-modal__header [data-qn-close], .qn-modal__header [data-qn-close] *');

  if (isBackdrop) {
    if (allowBackdropClose) {
      hide();
    } else {
      dialog.animate(
        [{ transform:'scale(1)' }, { transform:'scale(1.02)' }, { transform:'scale(1)' }],
        { duration:150 }
      );
    }
    return;
  }

  if (isCloseBtn) { hide(); }
});


async function archiveNote(id, nextStatus){ // nextStatus: 'archived' or 'active'
  try {
    // Preferred dedicated endpoint (simple, explicit):
    //   POST apps/notepad/note_archive/{id}  with FormData 'value' = 'archived'|'active'
    const fd = new FormData();
    fd.set('value', nextStatus);

    // Try explicit archive endpoint first
    let ok = true, j = null;
    try {
      j = await postForm('<?= site_url('apps/notepad/note_archive/') ?>' + id, fd);
    } catch (e) {
      ok = false;
    }

    // Fallback: use note_save with id + status (if you don’t have note_archive yet)
    if (!ok) {
      const fd2 = new FormData();
      fd2.set('id', String(id));
      fd2.set('status', nextStatus);
      j = await postForm('<?= site_url('apps/notepad/note_save') ?>', fd2);
    }

    await loadNotes();
  } catch (err) {
    alert(err.message || 'Unable to change archive state');
  }
}

    function toEditor(){
        empty.hidden = true; list.hidden = true; editor.hidden = false;
        setTimeout(()=>document.getElementById('note_title').focus(), 60);
    }

    btnNew?.addEventListener('click', toEditor);
    document.getElementById('qn-list-add')?.addEventListener('click', toEditor);

    // menu
    document.querySelector('[data-qn-new]')?.addEventListener('click', toEditor);
    document.querySelector('[data-qn-save]')?.addEventListener('click', saveNote);
    document.querySelector('[data-qn-close]')?.addEventListener('click', hide);
    btnCancel?.addEventListener('click', resetEditor);

    // Only close the one dropdown you clicked
    function toggleDropdown(ddId){
      const dd = document.getElementById(ddId);
      dd.classList.toggle('open');
      document.querySelectorAll('.qn-dropdown').forEach(el=>{ if (el!==dd) el.classList.remove('open'); });
    }
    document.getElementById('qn-menu-btn')?.addEventListener('click', ()=> toggleDropdown('qn-menu'));
    document.getElementById('qn-color-btn')?.addEventListener('click', (e)=>{ e.stopPropagation(); toggleDropdown('qn-color-dd'); });
    document.getElementById('qn-folder-btn')?.addEventListener('click', (e)=>{ e.stopPropagation(); toggleDropdown('qn-folder-dd'); });
    document.addEventListener('click', (e)=>{
      if (!e.target.closest('.qn-dropdown')) document.querySelectorAll('.qn-dropdown').forEach(dd=>dd.classList.remove('open'));
    });

    // toolbar format
// toolbar format (smart wrap + toggle + placeholder)
(function bindInlineFormatting(){
  const ta = document.getElementById('note_content');
  if (!ta) return;

  function applyInline(format){
    const wrap = format === 'bold' ? ['**','**'] : ['*','*'];
    const val = ta.value;
    const start = ta.selectionStart ?? 0;
    const end   = ta.selectionEnd   ?? 0;

    const before = val.slice(0, start);
    const sel    = val.slice(start, end);
    const after  = val.slice(end);

    // If selection exists, toggle if already wrapped, else wrap
    if (sel.length > 0) {
      const alreadyWrapped =
        before.endsWith(wrap[0]) && after.startsWith(wrap[1]);

      if (alreadyWrapped) {
        // Unwrap
        ta.value = before.slice(0, before.length - wrap[0].length)
                 + sel
                 + after.slice(wrap[1].length);
        const newStart = start - wrap[0].length;
        const newEnd   = end   - wrap[0].length;
        ta.setSelectionRange(newStart, newEnd);
      } else {
        // Wrap
        ta.value = before + wrap[0] + sel + wrap[1] + after;
        const newStart = start + wrap[0].length;
        const newEnd   = end   + wrap[0].length;
        ta.setSelectionRange(newStart, newEnd);
      }
      ta.focus();
      return;
    }

    // No selection: insert placeholder and select it
    const placeholder = format === 'bold' ? 'bold text' : 'italic text';
    const insert = wrap[0] + placeholder + wrap[1];
    ta.value = before + insert + after;

    const newStart = before.length + wrap[0].length;
    const newEnd   = newStart + placeholder.length;
    ta.setSelectionRange(newStart, newEnd);
    ta.focus();
  }

    document.querySelectorAll('[data-format]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const format = btn.dataset.format;
        if (format !== 'bold' && format !== 'italic') return;
        applyInline(format);
      });
    });

})();


// Prevent format clicks from being swallowed when dropdowns are open
document.addEventListener('mousedown', (e)=>{
  if (e.target.closest('.qn-dropdown__menu')) e.preventDefault();
}, true);


    // colors
    document.querySelectorAll('.qn-color-option').forEach(opt=>{
      opt.addEventListener('click', ()=>{
        document.getElementById('note_color').value = opt.dataset.color || '';
        updateToolbarButtons();
        document.getElementById('qn-color-dd').classList.remove('open');
      });
    });

    // folder dropdown default (no folder)
    document.querySelector('.qn-folder-option[data-folder-id=""]')?.addEventListener('click', ()=>{
      document.getElementById('note_folder_id').value = '';
      activeFolderId = '';                      // add this line
      updateToolbarButtons();
      document.getElementById('qn-folder-dd').classList.remove('open');
      loadNotes();                              // and this line
    });

    // pin/favorite
    document.getElementById('qn-pin-btn')?.addEventListener('click', async ()=>{
      const field = document.getElementById('note_is_pinned');
      const next  = field.value === '1' ? '0' : '1';
      field.value = next; updateToolbarButtons();
      const id = document.getElementById('note_id').value;
      if (id){
        try { const fd = new FormData(); fd.set('value', next);
          await postForm('<?= site_url('apps/notepad/note_toggle_pin/') ?>' + id, fd);
        } catch(e){ /* keep UI; server failure not critical for UX */ }
      }
    });

    document.getElementById('qn-favorite-btn')?.addEventListener('click', async ()=>{
      const field = document.getElementById('note_is_favorite');
      const next  = field.value === '1' ? '0' : '1';
      field.value = next; updateToolbarButtons();
      const id = document.getElementById('note_id').value;
      if (id){
        try { const fd = new FormData(); fd.set('value', next);
          await postForm('<?= site_url('apps/notepad/note_toggle_favorite/') ?>' + id, fd);
        } catch(e){ /* ignore */ }
      }
    });

    // Create folder modal: open/close with proper z-index and backdrop
document.getElementById('qn-create-folder')?.addEventListener('click', openFolderModal);

/* Delegate close so both the X button and backdrop work reliably */
folderModal.addEventListener('click', (e)=>{
  const isCloseBtn = e.target.matches('[data-qn-close-folder], [data-qn-close-folder] *');
  const isBackdrop = e.target.classList.contains('qn-modal__backdrop');

  if (isCloseBtn) { closeFolderModal(); return; }

  if (isBackdrop) {
    if (fAllowBackdropClose) {
      closeFolderModal();
    } else {
      const dlg = folderModal.querySelector('.qn-modal__dialog');
      dlg?.animate([{ transform:'scale(1)' }, { transform:'scale(1.02)' }, { transform:'scale(1)' }], { duration:150 });
    }
  }
});


/* ESC should close the top-most (folder first) */
document.addEventListener('keydown', (e)=>{
  if (e.key !== 'Escape') return;

  // Folder modal has precedence if open
  if (folderModal.getAttribute('aria-hidden') === 'false') {
    if (fAllowEscClose) closeFolderModal();
    return;
  }

  if (modal.getAttribute('aria-hidden') === 'false') {
    if (allowEscClose) hide();
  }
});



['qn-folder-save'].forEach(id=>{
  const el = document.getElementById(id);
  el?.addEventListener('click', (e)=> e.stopPropagation());
});

    // folder color choose
    folderModal?.querySelectorAll('.qn-color-option').forEach(opt=>{
      opt.addEventListener('click', ()=>{
        folderModal.querySelectorAll('.qn-color-option').forEach(o=>o.classList.remove('selected'));
        opt.classList.add('selected');
        document.getElementById('folder_color').value = opt.dataset.color;
      });
    });

    // Create folder (with icon support captured from main picker)
// Create folder (with icon support captured from main picker)
{
  const btnSaveFolder = document.getElementById('qn-folder-save');

  btnSaveFolder?.addEventListener('click', async ()=>{
    const nameEl   = document.getElementById('folder_name');
    const colorEl  = document.getElementById('folder_color');
    const iconEl   = document.getElementById('folder_icon');   // lives near the icon pills (in folder modal)
    const noteFld  = document.getElementById('note_folder_id'); // hidden field in note editor

    const name  = (nameEl?.value || '').trim();
    const color = colorEl?.value || '';
    const icon  = iconEl?.value || '';

    if (!name){ alert('Folder name required'); nameEl?.focus(); return; }

    // Optimistic lock to prevent double submits
    btnSaveFolder.disabled = true;

    try {
      const fd = new FormData();
      fd.set('name',  name);
      fd.set('color', color);
      fd.set('icon',  icon);

      // Expect { ok:true, id:<newId>, ... } but we guard anyway
      const res = await postForm('<?= site_url('apps/notepad/folder_save') ?>', fd);
      const newId = res && (res.id ?? res.folder_id ?? null);

      // Close the secondary modal via the central utility (restores scroll/z-index states)
      closeFolderModal();

      // Reset inputs in the modal
      if (nameEl)  nameEl.value = '';
      // keep last chosen color/icon as user preference; do not reset colorEl/iconEl

      // Refresh folder data everywhere (dropdown + tab list)
      await loadFolders();

      // If a new id came back, apply it to the current note (if editor is open)
      if (newId && noteFld) {
        noteFld.value = String(newId);
        updateToolbarButtons(); // updates Folder label in the toolbar
      }

      // Refresh “Folders” tab only if it exists AND currently visible
      const foldersList = document.getElementById('qn-folders');
      if (foldersList && foldersList.hidden === false) {
        renderFolders();
      }

    } catch (e) {
      alert(e?.message || 'Unable to create folder');
    } finally {
      btnSaveFolder.disabled = false;
    }
  });
}


    // Save note
    btnSave?.addEventListener('click', saveNote);

    // Slash hook (placeholder)
    document.getElementById('note_content')?.addEventListener('keydown', (e)=>{
      if (e.key === '/' && e.target.selectionStart === e.target.selectionEnd){ /* no-op */ }
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initQuickNoteModal, { once:true });
  } else {
    window.initQuickNoteModal();
  }
})();
