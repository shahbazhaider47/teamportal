<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
  .subscriptions-module .loading-overlay {
    position: absolute; inset: 0; display: none; align-items: center; justify-content: center;
    background: rgba(255,255,255,.6); z-index: 2;
  }
  .subscriptions-module .loading-overlay.show { display: flex; }
  .subscriptions-module .empty-row td { text-align:center; color:#6c757d; padding:2.5rem 1rem; }
  .badge-outline { border:1px solid currentColor; background: transparent; }

  #modalView .table tbody tr:hover { background-color: transparent; }
  #modalView code { word-break: break-all; }
</style>

<div class="container-fluid subscriptions-module position-relative">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= $page_title ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
<?php
$canEdit   = function_exists('staff_can') && staff_can('edit',   'subscriptions');
$canAdd    = function_exists('staff_can') && staff_can('create', 'subscriptions');
$canExport = function_exists('staff_can') && staff_can('export', 'subscriptions');
$canDelete = function_exists('staff_can') && staff_can('delete', 'subscriptions');
?>

      <a href="<?= base_url('subscriptions') ?>" class="btn btn-primary btn-header" title="Manage Subscriptions">
        <i class="ti ti-receipt-2 me-1"></i> Subscriptions
      </a>

      <div class="btn-divider"></div>

      <button type="button"
              id="btnAddSub"
              title="Add Subscription"
              class="btn btn-header <?= $canAdd ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
              <?= $canAdd ? '' : 'disabled' ?>>
        <i class="ti ti-plus"></i> Add New Subscription
      </button>

      <!-- Global Search -->
      <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
        <input type="text" class="form-control rounded app-form small dynamic-search-input"
               placeholder="Search..."
               aria-label="Search"
               data-table-target="<?= $table_id ?? 'subsTable' ?>">
        <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
      </div>

      <?php if ($canExport): ?>
        <button type="button"
                id="btnExport"
                class="btn btn-light-primary icon-btn b-r-4"
                title="Export CSV">
          <i class="ti ti-download"></i>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="loading-overlay" id="loader">
      <div class="spinner-border" role="status"><span class="visually-hidden">Loading…</span></div>
    </div>

    <div class="card-body table-responsive">
      <table class="table small table-sm table-bottom-border" id="subsTable">
        <thead class="bg-light-primary">
          <tr>
            <th class="sortable" data-field="title">Subscription Title <span class="sort-ind">↕</span></th>
            <th class="sortable" data-field="vendor">Vendor / Platform <span class="sort-ind">↕</span></th>
            <th class="sortable" data-field="category_name">Category <span class="sort-ind">↕</span></th>
            <th class="sortable" data-field="next_renewal_date">Next Renewal <span class="sort-ind">↕</span></th>
            <th class="text-end sortable" data-field="amount">Amount <span class="sort-ind">↕</span></th>
            <th class="sortable" data-field="status">Status <span class="sort-ind">↕</span></th>
            <th class="sortable" data-field="assigned_name">Assigned <span class="sort-ind">↕</span></th>
            <th style="width: 168px;">Actions</th>
          </tr>
        </thead>
        <tbody><!-- rows injected by JS --></tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <div id="subsSummary" class="text-muted small">—</div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-light btn-sm" id="btnPrev" disabled>&laquo;</button>
        <span class="mx-2" id="pageInfo">Page 1</span>
        <button class="btn btn-light btn-sm" id="btnNext" disabled>&raquo;</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Make sure these exist before modal scripts run
  window.BASE = '<?= site_url('subscriptions'); ?>';

  window.fmt = function(n){ try { return (n===null||n===undefined||n==='') ? '' : parseFloat(n).toFixed(2); } catch(e){ return n || ''; } };
  window.esc = function(s){ const d=document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
  window.statusClass = function(s){
    switch((s||'').toLowerCase()){
      case 'active': return 'success';
      case 'trial': return 'info';
      case 'expired': return 'danger';
      case 'cancelled': return 'secondary';
      case 'paused': return 'warning';
      default: return 'dark';
    }
  };
  window.fetchJSON = async function(url, opts = {}) {
    const resp = await fetch(url, { credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'}, ...opts });
    const text = await resp.text();
    try { return { ok: resp.ok, status: resp.status, data: JSON.parse(text) }; }
    catch(e){ return { ok:false, status: resp.status, data: {status:'error', error:{message:'Unexpected response'}}}; }
  };
</script>

<?php
// ------- Include modals (HTML + their own JS) -------
$CI =& get_instance();

echo $CI->load->view('subscriptions/modals/create_edit', [
  'categories'    => $categories ?? [],
  'base_currency' => $base_currency ?? 'USD',
  'assignees'     => $assignees ?? [],
], true);

echo $CI->load->view('subscriptions/modals/payments', [
  'base_currency' => $base_currency ?? 'USD',
], true);

echo $CI->load->view('subscriptions/modals/view', [], true);
?>

<script>
(function(){
  // --------- Globals used by modals & this page ----------
  window.BASE = '<?= site_url('subscriptions'); ?>';

  // Helpers (shared by modals)
  window.fmt = function(n){ try { return (n===null||n===undefined||n==='') ? '' : parseFloat(n).toFixed(2); } catch(e){ return n || ''; } };
  window.esc = function(s){ const d=document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
  window.statusClass = function(s){
    switch((s||'').toLowerCase()){
      case 'active': return 'success';
      case 'trial': return 'info';
      case 'expired': return 'danger';
      case 'cancelled': return 'secondary';
      case 'paused': return 'warning';
      default: return 'dark';
    }
  };
  window.fetchJSON = async function(url, opts = {}) {
    const resp = await fetch(url, { credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'}, ...opts });
    const text = await resp.text();
    try { return { ok: resp.ok, status: resp.status, data: JSON.parse(text) }; }
    catch(e){ return { ok:false, status: resp.status, data: {status:'error', error:{message:'Unexpected response'}}}; }
  };

  // --------- Local state for the grid ----------
  const loader   = document.getElementById('loader');
  const tblBody  = document.querySelector('#subsTable tbody');
  const pageInfo = document.getElementById('pageInfo');
  const summary  = document.getElementById('subsSummary');

  const CAN_DELETE = <?= $canDelete ? 'true' : 'false' ?>;
  const CAN_EDIT   = <?= $canEdit   ? 'true' : 'false' ?>;
  const CAN_ADD    = <?= $canAdd    ? 'true' : 'false' ?>;

  let limit  = 25;
  let offset = 0;
  let total  = 0;
  let order  = 'next_renewal_date';
  let dir    = 'ASC';

  function qs() {
    const params = new URLSearchParams();
    params.set('limit',  limit);
    params.set('offset', offset);
    params.set('order',  order);
    params.set('dir',    dir);
    return params.toString();
  }

  function setLoading(on){ loader.classList.toggle('show', !!on); }

function showEmpty(msg='No data found') {
  tblBody.innerHTML = `<tr class="empty-row"><td colspan="8">${esc(msg)}</td></tr>`;
}
function showError(msg='Failed to load data') {
  tblBody.innerHTML = `<tr class="empty-row"><td colspan="8"><span class="text-danger">${esc(msg)}</span></td></tr>`;
}

  function dueClass(dateStr){
    const today = new Date();
    const d = new Date(dateStr);
    const diff = (d - today) / 86400000;
    if (isNaN(diff)) return 'secondary';
    if (diff < 0) return 'danger';
    if (diff <= 7) return 'warning';
    return 'light-primary';
  }
  function cap1(s){
    s = (s ?? '').toString();
    return s ? s.charAt(0).toUpperCase() + s.slice(1).toLowerCase() : '';
  }

  // --------- Main grid loader (exposed so modals can refresh) ----------
  async function loadList(){
    setLoading(true);
    const url = window.BASE + '/list_json?' + qs();
    const res = await window.fetchJSON(url);
    setLoading(false);

    if (!res.ok || !res.data || res.data.status !== 'success') {
      const err = (res.data && res.data.error && (res.data.error.message || res.data.error)) || ('HTTP ' + (res.status||'')); showError(err); return;
    }

    const payload = res.data.data || {};
    const items   = payload.items || [];
    total = payload.total || 0;

    if (!Array.isArray(items) || items.length === 0) {
      showEmpty('No subscriptions found.');
    } else {
      tblBody.innerHTML = items.map(r => {
        const due = r.next_renewal_date
          ? `<span class="badge bg-${dueClass(r.next_renewal_date)}">${esc(r.next_renewal_date)}</span>`
          : '';
        const status = `<span class="badge bg-${statusClass(r.status)}">${esc(cap1(r.status))}</span>`;
        return `<tr data-id="${r.id}">
          <td><a href="#" class="link-view" data-id="${r.id}">${esc(r.title || '')}</a></td>
          <td>${esc(r.vendor || '')}</td>
          <td>${esc(r.category_name || '')}</td>
          <td>${due}</td>
          <td class="text-end">${fmt(r.amount)} ${esc(r.currency || '')}</td>
          <td>${status}</td>
          <td>${esc(r.assigned_name || '')}</td>
<td>
  <div class="btn-group btn-group-sm">
    <button class="btn btn-light-primary link-view" data-id="${r.id}" title="View">
      <i class="ti ti-eye"></i>
    </button>
    ${CAN_EDIT
      ? `<button class="btn btn-light-primary link-edit" data-id="${r.id}" title="Edit">
           <i class="ti ti-edit"></i>
         </button>`
      : ``}
    ${CAN_EDIT
      ? `<button class="btn btn-light-primary link-payments" data-id="${r.id}" title="Payments">
           <i class="ti ti-credit-card"></i>
         </button>`
      : ``}
    ${CAN_DELETE
      ? `<button class="btn btn-danger link-delete" data-id="${r.id}" title="Delete">
           <i class="ti ti-trash"></i>
         </button>`
      : ``}
  </div>
</td>

        </tr>`;
      }).join('');
    }

    const page  = Math.floor(offset / limit) + 1;
    const pages = Math.max(1, Math.ceil(total / limit));
    pageInfo.textContent = `${page} of ${pages}`;
    summary.textContent  = `${total} subscriptions`;

    document.getElementById('btnPrev').disabled = (offset <= 0);
    document.getElementById('btnNext').disabled = (offset + limit >= total);
  }
  window.loadList = loadList; // allow modals to refresh the table

  // Sorting
  document.querySelectorAll('#subsTable thead th.sortable').forEach(th=>{
    th.addEventListener('click', ()=>{
      const field = th.getAttribute('data-field');
      if (!field) return;
      if (order === field) { dir = (dir === 'ASC') ? 'DESC' : 'ASC'; } else { order = field; dir = 'ASC'; }
      offset = 0; loadList();
    });
  });

  // Paging
  document.getElementById('btnPrev').addEventListener('click', function(){
    offset = Math.max(0, offset - limit); loadList();
  });
  document.getElementById('btnNext').addEventListener('click', function(){
    if (offset + limit < total) { offset += limit; loadList(); }
  });

  // Toolbar actions
  const btnAdd = document.getElementById('btnAddSub');
  if (btnAdd) {
    btnAdd.addEventListener('click', function(){
      if (!<?= $canAdd ? 'true' : 'false' ?>) return;
      // open the create modal from create_edit.php
      if (window.SubForm && typeof window.SubForm.open === 'function') {
        window.SubForm.open(null);
      }
    });
  }

  const btnExport = document.getElementById('btnExport');
  if (btnExport) btnExport.addEventListener('click', function(e){
    e.preventDefault();
    const url = window.BASE + '/export';
    window.location = url;
  });

  // Row actions (event delegation) — use modal APIs from their files
  document.querySelector('#subsTable tbody').addEventListener('click', async function(e){
    const a = e.target.closest('button, a'); if (!a) return;
    const id = a.dataset.id;

    if (a.classList.contains('link-edit')) {
      e.preventDefault();
      if (!<?= $canEdit ? 'true' : 'false' ?>) return;
      if (window.SubForm && typeof window.SubForm.open === 'function') {
        window.SubForm.open(id);
      }
    } else if (a.classList.contains('link-delete')) {
      e.preventDefault();
      if (!<?= $canDelete ? 'true' : 'false' ?>) return;
      if (!confirm('Delete this subscription?')) return;
      const res = await window.fetchJSON(`${window.BASE}/delete/${id}`, {method:'POST'});
      if (res.ok && res.data && res.data.status === 'success') loadList();
      else alert((res.data && res.data.error && (res.data.error.message || res.data.error)) || 'Delete failed');
    } else if (a.classList.contains('link-payments')) {
      e.preventDefault();
      if (window.SubPayments && typeof window.SubPayments.open === 'function') {
        window.SubPayments.open(id);
      }
    } else if (a.classList.contains('link-view')) {
      e.preventDefault();
      if (window.SubView && typeof window.SubView.open === 'function') {
        window.SubView.open(id);
      }
    }
  });

  // Initial load
  loadList();
})();
</script>
