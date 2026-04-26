<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Quick Notepad Modal (fast load, inline banners, no alerts) -->
<div id="qn-notepad-modal" class="qn-modal" aria-hidden="true">
  <div class="qn-modal__backdrop" data-qn-close></div>

  <div class="qn-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="qn-notepad-title">
    <!-- Header -->
    <div class="qn-modal__header">
      <button class="qn-icon-btn qn-back" type="button" id="qn-back" title="Back" aria-label="Back" hidden>
        <svg viewBox="0 0 24 24" class="qn-ic"><path d="M15.5 19.1 9.4 12l6.1-7.1-1.5-1.3L6 12l8 8.4z"/></svg>
      </button>
      <div id="qn-notepad-title" class="qn-title">Notepad</div>

      <div class="qn-actions">
        <button class="qn-icon-btn" type="button" title="Search notes" id="qn-search-toggle" aria-label="Search notes">
          <svg viewBox="0 0 24 24" class="qn-ic"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.71.71l.27.28v.79L20 20.49 20.49 20l-4.99-4.99zM10 15a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/></svg>
        </button>

        <div class="qn-dropdown">
          <button class="qn-icon-btn" type="button" aria-haspopup="true" aria-expanded="false" id="qn-menu-btn" title="More options">
            <svg viewBox="0 0 24 24" class="qn-ic"><circle cx="6" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="18" cy="12" r="1.6"/></svg>
          </button>
          <div class="qn-dropdown__menu" role="menu" aria-labelledby="qn-menu-btn">
            <button class="qn-menu-item" type="button" data-qn-new>New note</button>
            <button class="qn-menu-item" type="button" data-qn-save>Save</button>
            <button class="qn-menu-item" type="button" data-qn-archive>Archive</button>
            <button class="qn-menu-item" type="button" data-qn-delete>Delete</button>
            <hr class="qn-hr">
            <button class="qn-menu-item" type="button" data-qn-close>Close</button>
          </div>
        </div>

        <button class="qn-icon-btn" type="button" title="Close" aria-label="Close" data-qn-close>
          <svg viewBox="0 0 24 24" class="qn-ic"><path d="M18.3 5.7 12 12l6.3 6.3-1.3 1.3L10.7 13.3 4.4 19.6 3.1 18.3 9.4 12 3.1 5.7 4.4 4.4l6.3 6.3 6.3-6.3z"/></svg>
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="qn-search" id="qn-search-bar" hidden>
      <div class="qn-search__wrap">
        <input type="text" class="qn-search__input" placeholder="Search" id="qn-search-input" autocomplete="off">
        <button class="qn-icon-btn" type="button" aria-label="Close search" id="qn-search-close">
          <svg viewBox="0 0 24 24" class="qn-ic"><path d="M18.3 5.7 12 12l6.3 6.3-1.3 1.3L10.7 13.3 4.4 19.6 3.1 18.3 9.4 12 3.1 5.7 4.4 4.4l6.3 6.3 6.3-6.3z"/></svg>
        </button>
      </div>
    </div>

    <!-- Inline banner area (no JS alerts) -->
    <div class="qn-banner" id="qn-banner" hidden></div>

    <!-- Body -->
    <div class="qn-modal__body">
      <!-- Left: list with skeleton -->
      <aside class="qn-list-pane" id="qn-list-pane" hidden>
        <div class="qn-list-header">
          <div class="qn-list-title">Notes</div>
          <button class="qn-icon-btn" type="button" id="qn-add-from-list" title="New note">
            <svg viewBox="0 0 24 24" class="qn-ic"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"/></svg>
          </button>
        </div>
        <div class="qn-list" id="qn-notes-list"></div>
        <div class="qn-skel-list" id="qn-skel-list" hidden>
          <div class="qn-skel-li"></div><div class="qn-skel-li"></div><div class="qn-skel-li"></div>
        </div>
      </aside>

      <!-- Empty state -->
      <div class="qn-empty" id="qn-empty">
        <div class="qn-empty__art" aria-hidden="true">
          <svg viewBox="0 0 120 120" class="qn-art">
            <rect x="28" y="22" width="64" height="86" rx="8" fill="#EEF2F7"/>
            <rect x="42" y="16" width="36" height="14" rx="6" fill="#D9DEE7"/>
            <rect x="40" y="38" width="40" height="6" rx="3" fill="#C5CCD8"/>
            <rect x="40" y="52" width="28" height="6" rx="3" fill="#C5CCD8"/>
            <circle cx="62" cy="72" r="9" fill="#AEB7C6"/>
            <path d="M62 66v12M56 72h12" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="qn-empty__title">Create personal notes</div>
        <p class="qn-empty__desc">Capture your thoughts or ideas and access them anywhere in ClickUp!</p>
        <button class="qn-btn qn-btn--primary" type="button" id="qn-create">Create a note</button>
      </div>

      <!-- Right: editor -->
      <section class="qn-editor-pane" id="qn-editor-pane" hidden>
        <form id="qn-editor" class="qn-editor" onsubmit="return false;">
          <!-- Schema columns -->
          <input type="hidden" name="id"          id="note_id">
          <input type="hidden" name="user_id"     id="note_user_id" value="">
          <input type="hidden" name="updated_by"  id="note_updated_by" value="">
          <input type="hidden" name="is_pinned"   id="note_is_pinned" value="0">
          <input type="hidden" name="is_favorite" id="note_is_favorite" value="0">
          <input type="hidden" name="is_locked"   id="note_is_locked" value="0">
          <input type="hidden" name="status"      id="note_status" value="active">
          <input type="hidden" name="color"       id="note_color" value="">
          <input type="hidden" name="sort_order"  id="note_sort_order" value="0">

          <div class="qn-editor-toolbar">
            <div class="qn-chipset">
              <button type="button" class="qn-chip" id="cmd-pin"      title="Pin">📌</button>
              <button type="button" class="qn-chip" id="cmd-fav"      title="Favorite">⭐</button>
              <button type="button" class="qn-chip" id="cmd-lock"     title="Lock">🔒</button>
              <button type="button" class="qn-chip" id="cmd-color"    title="Color">🎨</button>
              <button type="button" class="qn-chip" id="cmd-sort"     title="Sort order">↕</button>
            </div>
            <div class="qn-folder-ctl">
              <label class="qn-label">Folder</label>
              <select id="note_folder_id" name="folder_id" class="qn-select"></select>
              <button type="button" class="qn-btn qn-btn--ghost qn-btn-sm" id="folder-create">New</button>
            </div>
          </div>

          <input class="qn-input" name="title" id="note_title" placeholder="(Untitled)">
          <div class="qn-help">Write or type <b>/</b> for commands (checklist, color, pin, favorite, move to folder, etc.).</div>
          <textarea class="qn-textarea" name="content" id="note_content" placeholder="Start typing..."></textarea>

          <!-- Checklist -->
          <div class="qn-checklist">
            <div class="qn-checklist__hdr">
              <div class="qn-list-title">Checklist</div>
              <div class="qn-flex-gap">
                <input type="text" id="cl-new-body" class="qn-input qn-input-sm" placeholder="New checklist item">
                <button type="button" class="qn-btn qn-btn--ghost qn-btn-sm" id="cl-add">Add</button>
              </div>
            </div>
            <div id="cl-items"></div>
            <div id="cl-skel" class="qn-skel-cl" hidden></div>
          </div>

          <div class="qn-editor__actions">
            <button class="qn-btn qn-btn--primary" type="button" id="qn-save">Save</button>
            <button class="qn-btn qn-btn--ghost"   type="button" id="qn-archive">Archive</button>
            <button class="qn-btn qn-btn--ghost"   type="button" id="qn-delete">Delete</button>
            <button class="qn-btn qn-btn--ghost"   type="button" data-qn-close>Close</button>
          </div>
        </form>
      </section>
    </div>
  </div>
</div>

<?php
$qn_notes   = isset($notes)   && is_array($notes)   ? array_values($notes)   : [];
$qn_folders = isset($folders) && is_array($folders) ? array_values($folders) : [];
?>
<script>
window.QN_BOOT_NOTES   = <?= json_encode($qn_notes, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
window.QN_BOOT_FOLDERS = <?= json_encode($qn_folders, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
</script>

<style>
#qn-notepad-modal{
  --qn-header:#FFE6A7; --qn-btn:#F5C96A; --qn-btn-h:#EDBB4E;
  --qn-border:#E7EBF1; --qn-text:#2a2f3a; --qn-muted:#6b7280; --qn-bg:#fff;
}
.qn-modal{position:fixed; inset:0; display:none; z-index:1060}
.qn-modal[aria-hidden="false"]{display:block}
.qn-modal__backdrop{position:absolute; inset:0; background:rgba(26,32,44,.35)}
.qn-modal__dialog{position:relative; margin:24px auto; width:720px; max-width:96%; background:#fff; border-radius:12px; box-shadow:0 12px 40px rgba(0,0,0,.20); overflow:hidden}
.qn-modal__header{display:flex; align-items:center; gap:6px; justify-content:space-between; padding:8px 10px; background:var(--qn-header); color:var(--qn-text)}
.qn-title{font-size:14px; font-weight:600}
.qn-actions{display:flex; align-items:center; gap:6px}
.qn-icon-btn{border:0; background:transparent; padding:6px; border-radius:6px; cursor:pointer}
.qn-icon-btn:hover{background:rgba(0,0,0,.06)}
.qn-ic{width:18px; height:18px; fill:currentColor}
.qn-back{margin-right:4px}
.qn-search{padding:6px 10px; background:var(--qn-header); border-top:1px solid rgba(0,0,0,.05)}
.qn-search__wrap{display:flex; align-items:center; gap:6px}
.qn-search__input{flex:1; border:0; border-radius:8px; padding:8px 10px}
.qn-search__input:focus{outline:2px solid #e9b94d}
.qn-banner{display:none; margin:8px 10px 0; border-radius:8px; padding:8px 10px; font-size:13px}
.qn-banner.show{display:block}
.qn-banner.ok{background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0}
.qn-banner.err{background:#fef2f2; color:#991b1b; border:1px solid #fecaca}

.qn-modal__body{display:flex; min-height:520px}
.qn-list-pane{width:260px; border-right:1px solid var(--qn-border)}
.qn-editor-pane{flex:1}
.qn-list-header{display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-bottom:1px solid var(--qn-border)}
.qn-list-title{font-weight:600; font-size:13px}
.qn-list{overflow:auto; max-height:calc(520px - 44px); padding:6px}
.qn-li{display:flex; align-items:center; justify-content:space-between; gap:6px; padding:8px 8px; border-radius:8px; cursor:pointer; border:1px solid transparent}
.qn-li:hover{background:#f6f8fb}
.qn-li.active{background:#eef5ff; border-color:#d6e6ff}
.qn-li h6{margin:0; font-size:13px; font-weight:600; color:var(--qn-text)}
.qn-li p{margin:0; font-size:12px; color:var(--qn-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:70%}
.qn-li-badges{display:flex; gap:4px}
.qn-badge{font-size:10px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; padding:2px 6px}
.qn-empty{text-align:center; margin:auto; padding:24px}
.qn-empty__art{margin-bottom:12px}
.qn-art{width:128px; height:128px}
.qn-empty__title{font-size:18px; font-weight:700; color:var(--qn-text); margin-top:6px}
.qn-empty__desc{font-size:13px; color:var(--qn-muted); margin:6px auto 14px; max-width:260px}
.qn-btn{border:0; border-radius:8px; font-size:13px; padding:8px 14px; cursor:pointer}
.qn-btn--primary{background:var(--qn-btn); color:#3a2f06}
.qn-btn--primary:hover{background:var(--qn-btn-h)}
.qn-btn--ghost{background:transparent; color:var(--qn-text)}
.qn-btn--ghost:hover{background:#f3f4f6}
.qn-btn-sm{padding:6px 10px; font-size:12px}
.qn-editor{display:flex; flex-direction:column; gap:10px; padding:12px}
.qn-editor-toolbar{display:flex; align-items:center; gap:8px; justify-content:space-between}
.qn-chipset{display:flex; gap:6px}
.qn-chip{border:1px solid var(--qn-border); background:#fff; border-radius:8px; padding:6px 8px; cursor:pointer}
.qn-chip.active{background:#fff6cf; border-color:#e5cf7a}
.qn-folder-ctl{display:flex; align-items:center; gap:6px}
.qn-label{font-size:12px; color:var(--qn-muted)}
.qn-select{border:1px solid var(--qn-border); border-radius:6px; padding:6px 8px; font-size:12px}
.qn-input,.qn-textarea{width:100%; border:1px solid var(--qn-border); border-radius:8px; padding:10px 12px; font-size:14px}
.qn-input:focus,.qn-textarea:focus{outline:2px solid #e9b94d; border-color:transparent}
.qn-textarea{min-height:220px; resize:vertical}
.qn-help{font-size:12px; color:var(--qn-muted); margin:-6px 0 2px}
.qn-checklist{border-top:1px dashed var(--qn-border); padding-top:8px}
.qn-checklist__hdr{display:flex; align-items:center; justify-content:space-between; margin-bottom:6px}
.qn-flex-gap{display:flex; gap:6px; align-items:center}
.qn-input-sm{padding:6px 8px; font-size:12px}
.qn-cl{display:flex; align-items:center; gap:8px; padding:6px 0; border-bottom:1px dashed #f0f2f6}
.qn-cl input[type="checkbox"]{width:16px; height:16px}
.qn-cl input[type="text"]{flex:1; border:1px solid transparent; border-radius:6px; padding:6px 8px; font-size:13px}
.qn-cl input[type="text"]:focus{border-color:var(--qn-border)}
.qn-cl .qn-del{border:0; background:transparent; color:#b91c1c; cursor:pointer}
/* skeletons */
.qn-skel-list .qn-skel-li{height:46px; border-radius:8px; background:linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 37%,#f3f4f6 63%); background-size:400% 100%; animation:qn-shimmer 1.2s infinite}
.qn-skel-cl{height:28px; border-radius:6px; background:linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 37%,#f3f4f6 63%); background-size:400% 100%; animation:qn-shimmer 1.2s infinite}
@keyframes qn-shimmer{0%{background-position:100% 0}100%{background-position:-100% 0}}
</style>

<script>
(function(){
  const modal = document.getElementById('qn-notepad-modal'); if (!modal) return;

  /* elements */
  const titleEl   = document.getElementById('qn-notepad-title');
  const backBtn   = document.getElementById('qn-back');
  const listPane  = document.getElementById('qn-list-pane');
  const listEl    = document.getElementById('qn-notes-list');
  const listSkel  = document.getElementById('qn-skel-list');
  const emptyEl   = document.getElementById('qn-empty');
  const paneEd    = document.getElementById('qn-editor-pane');
  const form      = document.getElementById('qn-editor');
  const banner    = document.getElementById('qn-banner');
  const menuBtn   = document.getElementById('qn-menu-btn');
  const dropdown  = menuBtn.parentElement;
  const fldSel    = document.getElementById('note_folder_id');

  const btnCreate = document.getElementById('qn-create');
  const btnAdd    = document.getElementById('qn-add-from-list');
  const btnSave   = document.getElementById('qn-save');
  const btnArc    = document.getElementById('qn-archive');
  const btnDel    = document.getElementById('qn-delete');

  const clAddBtn  = document.getElementById('cl-add');
  const clNew     = document.getElementById('cl-new-body');
  const clWrap    = document.getElementById('cl-items');
  const clSkel    = document.getElementById('cl-skel');

  const btnPin  = document.getElementById('cmd-pin');
  const btnFav  = document.getElementById('cmd-fav');
  const btnLock = document.getElementById('cmd-lock');
  const btnColor= document.getElementById('cmd-color');
  const btnSort = document.getElementById('cmd-sort');

  const searchBar    = document.getElementById('qn-search-bar');
  const searchToggle = document.getElementById('qn-search-toggle');
  const searchInput  = document.getElementById('qn-search-input');
  const searchClose  = document.getElementById('qn-search-close');

  /* state */
  let NOTES   = Array.isArray(window.QN_BOOT_NOTES)   ? window.QN_BOOT_NOTES   : [];
  let FOLDERS = Array.isArray(window.QN_BOOT_FOLDERS) ? window.QN_BOOT_FOLDERS : [];
  let activeId = null;

  /* utils */
  const escapeHtml = s => (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m]));
  const stripHtml  = s => (s||'').replace(/<[^>]*>/g,'');
  const fd         = o => { const f=new FormData(); Object.entries(o||{}).forEach(([k,v])=>f.append(k,v)); return f; };

  function showBanner(type, msg){
    banner.className = 'qn-banner show ' + (type==='ok'?'ok':'err');
    banner.textContent = msg;
    clearTimeout(showBanner._t);
    showBanner._t = setTimeout(()=>{ banner.className='qn-banner'; banner.textContent=''; }, 2500);
  }

  async function api(url, method='GET', body=null){
    const opt = { method };
    if (body) opt.body = body;
    const res = await fetch(url, opt);
    const txt = await res.text(); let j; try{ j = JSON.parse(txt); }catch{ j={ok:false,error:'Non-JSON',_raw:txt}; }
    return j;
  }

  async function bootstrap(){
    // render immediately from injected data
    renderFolders();
    if (NOTES.length){ setListMode(); renderList(); openNote(NOTES[0].id); return; }

    // fetch if empty
    setLoadingList(true);
    const j = await api('<?= site_url('apps/notepad/note_list') ?>','GET');
    setLoadingList(false);
    if (j && Array.isArray(j.notes)) {
      NOTES = j.notes;
      if (NOTES.length){ setListMode(); renderList(); openNote(NOTES[0].id); }
      else setEmptyMode();
    } else {
      // try legacy /list
      setLoadingList(true);
      const j2 = await api('<?= site_url('apps/notepad/list') ?>','GET');
      setLoadingList(false);
      if (j2 && Array.isArray(j2.notes)) { NOTES=j2.notes; setListMode(); renderList(); openNote(NOTES[0].id); }
      else setEmptyMode();
    }

    if (!FOLDERS.length){
      try{ const fl = await api('<?= site_url('apps/notepad/folders_list') ?>','GET'); if (fl && Array.isArray(fl.folders)) FOLDERS = fl.folders; }catch{}
      renderFolders();
    }
  }

  function setLoadingList(v){ listSkel.hidden = !v; listEl.hidden = v; }
  function setListMode(){ listPane.hidden=false; paneEd.hidden=false; emptyEl.hidden=true; backBtn.hidden=true; titleEl.textContent='Notepad'; }
  function setEmptyMode(){ listPane.hidden=true; paneEd.hidden=true; emptyEl.hidden=false; backBtn.hidden=true; titleEl.textContent='Notepad'; }
  function setSingleEditor(){ listPane.hidden=true; paneEd.hidden=false; emptyEl.hidden=true; backBtn.hidden=false; }

  function renderFolders(){
    fldSel.innerHTML = '<option value="">None</option>' + (FOLDERS||[]).map(f=>`<option value="${f.id||''}">${escapeHtml(f.name||'')}</option>`).join('');
  }

  function renderList(filter=''){
    const q=(filter||'').toLowerCase();
    listEl.innerHTML='';
    (NOTES||[]).forEach(n=>{
      const hay = `${n.title||''} ${stripHtml(n.content||'')}`.toLowerCase();
      if (q && !hay.includes(q)) return;
      const li=document.createElement('div'); li.className='qn-li'+(String(n.id)===String(activeId)?' active':''); li.dataset.id=n.id||'';
      const updated = (n.updated_at||n.created_at||'').split(' ')[0];
      li.innerHTML = `
        <div>
          <h6>${escapeHtml(n.title||'(Untitled)')}</h6>
          <p>${escapeHtml((stripHtml(n.content||'').slice(0,60))+( (n.content||'').length>60?'…':'' ))}</p>
        </div>
        <div class="qn-li-badges">
          ${n.is_pinned?'<span class="qn-badge">Pin</span>':''}
          ${n.is_favorite?'<span class="qn-badge">Fav</span>':''}
          ${n.status && n.status!=='active'?`<span class="qn-badge">${escapeHtml(n.status)}</span>`:''}
          ${updated?`<span class="qn-badge">${escapeHtml(updated)}</span>`:''}
        </div>`;
      li.addEventListener('click', ()=>openNote(n.id));
      listEl.appendChild(li);
    });
  }

  function setFormVal(id,v){ const el=document.getElementById('note_'+id) || document.getElementById(id); if (el) el.value=v; }
  function syncChips(){
    btnPin.classList.toggle('active',  parseInt(document.getElementById('note_is_pinned').value,10)===1);
    btnFav.classList.toggle('active',  parseInt(document.getElementById('note_is_favorite').value,10)===1);
    btnLock.classList.toggle('active', parseInt(document.getElementById('note_is_locked').value,10)===1);
  }

  function openNew(){
    activeId=''; form.reset();
    setFormVal('id',''); setFormVal('title',''); setFormVal('content',''); setFormVal('is_pinned','0'); setFormVal('is_favorite','0'); setFormVal('is_locked','0');
    setFormVal('status','active'); setFormVal('color',''); setFormVal('sort_order','0'); fldSel.value='';
    syncChips(); setSingleEditor();
    titleEl.textContent = new Date().toLocaleDateString(undefined,{year:'numeric',month:'long',day:'numeric'});
    clWrap.innerHTML=''; // no checklist yet
  }

  function openNote(id){
    const n=(NOTES||[]).find(x=>String(x.id)===String(id)); if(!n){ openNew(); return; }
    activeId=n.id; setListMode();
    setFormVal('id',n.id||''); setFormVal('title',n.title||''); setFormVal('content',stripHtml(n.content||''));
    setFormVal('is_pinned',n.is_pinned?1:0); setFormVal('is_favorite',n.is_favorite?1:0); setFormVal('is_locked',n.is_locked?1:0);
    setFormVal('status',n.status||'active'); setFormVal('color',n.color||''); setFormVal('sort_order',n.sort_order||0);
    fldSel.value = n.folder_id || '';
    document.querySelectorAll('.qn-li').forEach(x=>x.classList.toggle('active', String(x.dataset.id)===String(n.id)));
    const d = (n.updated_at||n.created_at) ? new Date((n.updated_at||n.created_at).replace(' ','T')) : new Date();
    titleEl.textContent = d.toLocaleDateString(undefined,{year:'numeric',month:'long',day:'numeric'});
    syncChips();
    loadChecklistFor(n.id);
  }

  async function loadChecklistFor(noteId){
    clSkel.hidden=false; clWrap.innerHTML='';
    const j = await api('<?= site_url('apps/notepad/checklist_list') ?>/'+noteId,'GET');
    clSkel.hidden=true;
    const items = (j && Array.isArray(j.items)) ? j.items : [];
    clWrap.innerHTML='';
    items.forEach(it=>addChecklistRow(it.id, it.body, it.is_done));
  }

  function addChecklistRow(id, body, is_done){
    const row=document.createElement('div'); row.className='qn-cl';
    row.innerHTML = `
      <input type="checkbox" ${parseInt(is_done,10)===1?'checked':''}>
      <input type="text" value="${escapeHtml(body||'')}" placeholder="Checklist item">
      <button class="qn-del" title="Delete">✖</button>`;
    const cb=row.querySelector('input[type="checkbox"]');
    const txt=row.querySelector('input[type="text"]');
    const del=row.querySelector('.qn-del');

    cb.addEventListener('change', async ()=>{
      await api('<?= site_url('apps/notepad/checklist_toggle') ?>/'+id, 'POST', fd({is_done:cb.checked?1:0}));
    });
    txt.addEventListener('blur', async ()=>{
      await api('<?= site_url('apps/notepad/checklist_toggle') ?>/'+id, 'POST', fd({body:txt.value}));
    });
    del.addEventListener('click', async ()=>{
      const r = await api('<?= site_url('apps/notepad/checklist_delete') ?>/'+id, 'POST');
      if (r && r.ok){ row.remove(); showBanner('ok','Checklist item removed'); } else showBanner('err', r.error||'Delete failed');
    });

    clWrap.appendChild(row);
  }

  async function saveNote(){
    if (parseInt(document.getElementById('note_is_locked').value,10)===1) { showBanner('err','Note is locked'); return; }
    const data=new FormData(form);
    const j=await api('<?= site_url('apps/notepad/note_save') ?>','POST',data);
    if (j && j.ok){
      showBanner('ok','Saved');
      // refresh list quickly
      try{ const jl=await api('<?= site_url('apps/notepad/note_list') ?>','GET'); if (jl && Array.isArray(jl.notes)) NOTES=jl.notes; }catch{}
      renderList(searchInput.value);
      openNote(j.id || document.getElementById('note_id').value);
    }else{
      showBanner('err', j && j.error ? j.error : 'Save failed');
      console.error(j);
    }
  }

  async function archiveNote(){
    const id=document.getElementById('note_id').value; if(!id) return;
    const j=await api('<?= site_url('apps/notepad/note_archive') ?>/'+id,'POST');
    if (j && j.ok){ showBanner('ok','Archived'); await reloadNotes(); } else showBanner('err', j.error||'Archive failed');
  }

  async function deleteNote(){
    const id=document.getElementById('note_id').value; if(!id) return;
    const j=await api('<?= site_url('apps/notepad/note_delete') ?>/'+id,'POST');
    if (j && j.ok){ showBanner('ok','Deleted'); await reloadNotes(); } else showBanner('err', j.error||'Delete failed');
  }

  async function reloadNotes(){
    try{ const jl=await api('<?= site_url('apps/notepad/note_list') ?>','GET'); if (jl && Array.isArray(jl.notes)) NOTES=jl.notes; }catch{}
    activeId=null;
    if (NOTES.length){ setListMode(); renderList(searchInput.value); openNote(NOTES[0].id); }
    else setEmptyMode();
  }

  /* toolbar chips */
  btnPin.addEventListener('click', async ()=>{
    const id=document.getElementById('note_id').value; if(!id) return;
    const cur=parseInt(document.getElementById('note_is_pinned').value,10)?1:0;
    const j=await api('<?= site_url('apps/notepad/note_toggle_pin') ?>/'+id,'POST', fd({value:cur?0:1}));
    if (j && j.ok){ document.getElementById('note_is_pinned').value=cur?0:1; syncChips(); showBanner('ok','Pin updated'); await reloadNotes(); }
    else showBanner('err', j.error||'Update failed');
  });
  btnFav.addEventListener('click', async ()=>{
    const id=document.getElementById('note_id').value; if(!id) return;
    const cur=parseInt(document.getElementById('note_is_favorite').value,10)?1:0;
    const j=await api('<?= site_url('apps/notepad/note_toggle_favorite') ?>/'+id,'POST', fd({value:cur?0:1}));
    if (j && j.ok){ document.getElementById('note_is_favorite').value=cur?0:1; syncChips(); showBanner('ok','Favorite updated'); await reloadNotes(); }
    else showBanner('err', j.error||'Update failed');
  });
  btnLock.addEventListener('click', async ()=>{
    const id=document.getElementById('note_id').value; if(!id) return;
    const cur=parseInt(document.getElementById('note_is_locked').value,10)?1:0;
    const j=await api('<?= site_url('apps/notepad/note_lock') ?>/'+id,'POST', fd({value:cur?0:1}));
    if (j && j.ok){ document.getElementById('note_is_locked').value=cur?0:1; syncChips(); showBanner('ok','Lock updated'); }
    else showBanner('err', j.error||'Update failed');
  });
  btnColor.addEventListener('click', ()=>{
    const cur=document.getElementById('note_color').value||'';
    const v=prompt('Enter hex color (e.g., #ffeaa7):', cur);
    if (v!==null) document.getElementById('note_color').value=v.trim();
  });
  btnSort.addEventListener('click', ()=>{
    const cur=document.getElementById('note_sort_order').value||'0';
    const v=prompt('Sort order (integer):', cur);
    if (v!==null) document.getElementById('note_sort_order').value=(v||'0').replace(/[^\d\-]/g,'');
  });

  /* folder create */
  document.getElementById('folder-create').addEventListener('click', async ()=>{
    const name=prompt('Folder name'); if(!name) return;
    const icon=prompt('Icon (optional, e.g., ti ti-notes)')||'';
    const color=prompt('Color (optional, e.g., #a29bfe)')||'';
    const sort=prompt('Sort order (integer)','0')||'0';
    const j=await api('<?= site_url('apps/notepad/folder_save') ?>','POST', fd({name,icon,color,sort_order:sort}));
    if (j && j.ok){
      try{ const fl=await api('<?= site_url('apps/notepad/folders_list') ?>','GET'); if (fl && Array.isArray(fl.folders)) FOLDERS=fl.folders; }catch{}
      renderFolders(); if (j.id) fldSel.value=j.id;
      showBanner('ok','Folder created');
    } else showBanner('err', j.error||'Folder create failed');
  });

  /* checklist add */
  clAddBtn.addEventListener('click', async ()=>{
    const body=clNew.value.trim(); if(!body) return;
    const id=document.getElementById('note_id').value; if(!id){ showBanner('err','Save note first'); return; }
    const j=await api('<?= site_url('apps/notepad/checklist_add') ?>/'+id,'POST', fd({body,position:Date.now()}));
    if (j && j.ok){ clNew.value=''; await loadChecklistFor(id); showBanner('ok','Checklist added'); }
    else showBanner('err', j.error||'Add failed');
  });

  /* menu + header buttons */
  menuBtn.addEventListener('click', ()=>dropdown.classList.toggle('open'));
  document.addEventListener('click', e=>{ if(!dropdown.contains(e.target)) dropdown.classList.remove('open'); });
  document.querySelector('[data-qn-new]').addEventListener('click', openNew);
  document.querySelector('[data-qn-save]').addEventListener('click', saveNote);
  document.querySelector('[data-qn-close]').addEventListener('click', ()=>modal.setAttribute('aria-hidden','true'));
  document.querySelector('[data-qn-archive]').addEventListener('click', archiveNote);
  document.querySelector('[data-qn-delete]').addEventListener('click', deleteNote);

  btnCreate.addEventListener('click', openNew);
  btnAdd.addEventListener('click', openNew);
  btnSave.addEventListener('click', saveNote);
  btnArc.addEventListener('click', archiveNote);
  btnDel.addEventListener('click', deleteNote);

  /* search */
  document.getElementById('qn-search-toggle').addEventListener('click', ()=>{
    searchBar.hidden = !searchBar.hidden; if (!searchBar.hidden) setTimeout(()=>searchInput.focus(),30);
  });
  searchClose.addEventListener('click', ()=>{ searchBar.hidden=true; renderList(''); searchInput.value=''; });
  let searchDeb; searchInput.addEventListener('input', ()=>{
    clearTimeout(searchDeb); searchDeb=setTimeout(()=>renderList(searchInput.value), 120);
  });

  /* back & close */
  backBtn.addEventListener('click', ()=>{ if (NOTES.length){ setListMode(); renderList(searchInput.value); } else setEmptyMode(); });
  modal.addEventListener('click', e=>{
    if (e.target.matches('[data-qn-close], [data-qn-close] *') || e.target.classList.contains('qn-modal__backdrop')) modal.setAttribute('aria-hidden','true');
  });
  document.addEventListener('keydown', e=>{ if(e.key==='Escape' && modal.getAttribute('aria-hidden')==='false') modal.setAttribute('aria-hidden','true'); });

  /* public API */
  window.openQuickNoteModal = function(){ modal.setAttribute('aria-hidden','false'); bootstrap(); };
  window.closeQuickNoteModal = function(){ modal.setAttribute('aria-hidden','true'); };

  // initial render (deferred until opened)
})();
</script>
