<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
  /* Modal-specific styles */
  #modalView .table tbody tr:hover { background-color: transparent; }
  #modalView code { word-break: break-all; }
</style>

<!-- View-only modal (opened by clicking Title) -->
<div class="modal fade" id="modalView" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary align-items-center">
        <div class="d-flex flex-column">
          <h5 class="modal-title mb-0 text-white">Subscription Details</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span id="viewStatusBadge" class="badge"></span>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="×"></button>
        </div>
      </div>

      <div class="modal-body">
        <!-- KPI header -->
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <div class="card h-100">
              <div class="card-body py-3">
                <div class="small text-muted">Next Renewal</div>
                <div id="kpiNextRenewal" class="fs-6 fw-semibold">—</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card h-100">
              <div class="card-body py-3">
                <div class="small text-muted">Amount</div>
                <div id="kpiAmount" class="fs-6 fw-semibold">—</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card h-100">
              <div class="card-body py-3">
                <div class="small text-muted">Payment Cycle</div>
                <div id="kpiCycle" class="fs-6 fw-semibold">—</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card h-100">
              <div class="card-body py-3">
                <div class="small text-muted">Auto-Renew</div>
                <div id="kpiAutoRenew" class="fs-6 fw-semibold">—</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Overview / Account -->
        <div class="card mb-3">
          <div class="card-header fw-semibold">Overview</div>
          <div class="card-body">
            <div class="row g-3 small" id="viewBasic"><!-- filled by JS --></div>
          </div>
        </div>

        <!-- Security -->
        <div class="card mb-3">
          <div class="card-header fw-semibold">Security</div>
          <div class="card-body small">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="text-muted d-block">2FA Status</label>
                <div id="sec2fa">—</div>
              </div>
              <div class="col-md-8">
                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                  <div class="flex-fill">
                    <label class="text-muted d-block">Account Password (HASH)</label>
                    <code id="secHash" class="d-block text-wrap">—</code>
                  </div>
                  <div class="d-flex gap-2">
                    <button class="btn btn-ssm btn-light-primary" id="btnCopyHash" type="button" title="Copy hash">Copy</button>
                    <button class="btn btn-ssm btn-light-primary" id="btnToggleHash" type="button" title="Truncate / Full">Expand</button>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <label class="text-muted d-block">Backup Codes</label>
                <pre id="secBackup" class="bg-light-primary p-2 mb-0" style="max-height: 140px; overflow:auto;">—</pre>
              </div>
            </div>
          </div>
        </div>

        <!-- Lifecycle / Dates -->
        <div class="card mb-3">
          <div class="card-header fw-semibold">Lifecycle</div>
          <div class="card-body small">
            <div class="row g-3">
              <div class="col-md-3"><label class="text-muted d-block">Start Date</label><div id="lifeStart">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">End Date</label><div id="lifeEnd">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">Reminder (days)</label><div id="lifeReminder">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">Grace (days)</label><div id="lifeGrace">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">Last Payment</label><div id="lifeLastPay">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">Created</label><div id="lifeCreated">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">Updated</label><div id="lifeUpdated">—</div></div>
            </div>
          </div>
        </div>

        <!-- Billing / Ownership -->
        <div class="card mb-3">
          <div class="card-header fw-semibold">Billing & Ownership</div>
          <div class="card-body small">
            <div class="row g-3">
              <div class="col-md-3"><label class="text-muted d-block">Payment Method</label><div id="billMethod">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">Assigned To</label><div id="billAssigned">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">Seats</label><div id="billSeats">—</div></div>
              <div class="col-md-3"><label class="text-muted d-block">License Key</label><div id="billLicense">—</div></div>
            </div>
          </div>
        </div>

        <!-- Notes / Meta -->
        <div class="row">
          <div class="col-lg-12">
            <div class="card mb-3">
              <div class="card-header fw-semibold">Notes</div>
              <div class="card-body small">
                <div id="notesBox" class="text-wrap">—</div>
              </div>
            </div>
          </div>
          <div class="col-lg-12">
            <div class="card mb-3">
              <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                <span>Meta</span>
                <button class="btn btn-ssm btn-light-primary" id="btnCopyMeta" type="button" title="Copy meta JSON">Copy</button>
              </div>
              <div class="card-body small">
                <pre id="metaBox" class="bg-light-primary p-2 mb-0" style="max-height: 240px; overflow:auto;">—</pre>
              </div>
            </div>
          </div>
        </div>

        <!-- Payments -->
        <div class="card">
          <div class="card-header fw-semibold">Payments</div>
          <div class="card-body table-responsive">
            <table class="table table-sm mb-0" id="tblViewPayments">
              <thead>
                <tr>
                  <th>Date</th>
                  <th class="text-end">Amount</th>
                  <th>Currency</th>
                  <th>Method</th>
                  <th>Txn</th>
                  <th>Notes</th>
                  <th>Receipt</th>
                </tr>
              </thead>
              <tbody><!-- filled by JS --></tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
(function(){
  /* === Self-contained helpers used by this modal only === */
  const BASE = '<?= site_url('subscriptions'); ?>';
  const FILEBASE = '<?= rtrim(base_url(), '/'); ?>/'; // e.g., https://example.com/

  function fmt(n){ try { return (n===null||n===undefined||n==='') ? '' : parseFloat(n).toFixed(2); } catch(e){ return n || ''; } }
  function esc(s){ const d=document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
  async function fetchJSON(url, opts = {}) {
    const resp = await fetch(url, { credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'}, ...opts });
    const text = await resp.text();
    try { return { ok: resp.ok, status: resp.status, data: JSON.parse(text) }; }
    catch(e){ return { ok:false, status: resp.status, data: {status:'error', error:{message:'Unexpected response'}}}; }
  }
  function statusClass(s){
    switch((s||'').toLowerCase()){
      case 'active': return 'success';
      case 'trial': return 'info';
      case 'expired': return 'danger';
      case 'cancelled': return 'secondary';
      case 'paused': return 'warning';
      default: return 'dark';
    }
  }

  function isImagePath(p){
    return /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(p || '');
  }
  function fileUrl(rel){
    if (!rel) return '';
    return FILEBASE + String(rel).replace(/^\/+/, '');
  }
  function fileName(rel){
    const r = String(rel || '');
    const p = r.split('/');
    return p[p.length - 1] || r;
  }

function formatLongDate(d){
  if (!d) return '—';
  const date = new Date(d);
  if (isNaN(date)) return d; // fallback if not valid ISO date
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

function titleCase(str){
  return (str || '').toString().toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
}

  /* === Public entry point used by the main grid === */
  async function openViewOnly(id){
    const res = await fetchJSON(`${BASE}/view/${id}`);
    if (!(res.ok && res.data && res.data.status === 'success')) { alert('Failed to load'); return; }

    const d    = res.data.data || {};
    const s    = d.subscription || {};
    const pays = d.payments || [];

    // Helpers bound to this function
    const el     = id => document.getElementById(id);
    const setTxt = (id, v) => { const e = el(id); if (e) e.textContent = (v ?? '—'); };
    const money  = (v,c) => (v==null||v==='') ? '—' : `${fmt(v)} ${c||''}`.trim();
    const cap    = (x) => (x||'').toString().replace(/\b\w/g, m => m.toUpperCase());
    const trunc  = (h) => (h && h.length>24) ? (h.slice(0,12)+'…'+h.slice(-10)) : (h || '—');

    /* ---------- Header & KPIs ---------- */
    const badge = el('viewStatusBadge');
    if (badge) { badge.className = `badge bg-${statusClass(s.status||'')}`; badge.textContent = cap(s.status||'—'); }
    setTxt('kpiNextRenewal', formatLongDate(s.next_renewal_date));    
    setTxt('kpiAmount', money(s.amount, s.currency));
    setTxt(
  'kpiCycle',
  titleCase(s.payment_cycle || '—') +
    ((String(s.payment_cycle || '').toLowerCase() === 'custom' && s.cycle_days)
      ? ` (${s.cycle_days} days)` : '')
);
    setTxt('kpiAutoRenew', (parseInt(s.auto_renew,10)===1 ? 'Yes' : 'No'));

    /* ---------- Overview ---------- */
    const viewBasic = el('viewBasic');
    if (viewBasic) {
      viewBasic.innerHTML = `
        <div class="col-md-3"><label class="text-muted d-block">Subscription Title</label><div>${esc(s.title||'')}</div></div>
        <div class="col-md-3"><label class="text-muted d-block">Category</label><div>${esc(s.category_name||'')}</div></div>
        <div class="col-md-3"><label class="text-muted d-block">Assigned To</label><div>${esc(s.assigned_name||'')}</div></div>
        <div class="col-md-3"><label class="text-muted d-block">Payment Method</label><div>${esc(s.payment_method_id ?? '') || '—'}</div></div>

        <div class="col-md-3"><label class="text-muted d-block">Vendor / Platform</label><div>${esc(s.vendor||'')}</div></div>
        <div class="col-md-3"><label class="text-muted d-block">Vendor URL</label>
          <div>${s.vendor_url ? `<a href="${esc(s.vendor_url)}" target="_blank" rel="noopener">${esc(s.vendor_url)}</a>` : '—'}</div>
        </div>
        <div class="col-md-3"><label class="text-muted d-block">Account Email</label><div>${esc(s.account_email||'')}</div></div>
        <div class="col-md-3"><label class="text-muted d-block">Account Phone</label><div>${esc(s.account_phone||'')}</div></div>

        <div class="col-md-3"><label class="text-muted d-block">Type</label><div>${esc(titleCase(s.subscription_type||'—'))}</div></div>
        <div class="col-md-3"><label class="text-muted d-block">Payment Cycle</label><div>${esc(titleCase(s.payment_cycle||''))}</div></div>
        <div class="col-md-3"><label class="text-muted d-block">Seats/Licenses</label><div>${esc(s.seats ?? '') || '—'}</div></div>
        <div class="col-md-3"><label class="text-muted d-block">License Key</label><div>${esc(s.license_key||'—')}</div></div>

        <div class="col-md-6">
          <label class="text-muted d-block">Password</label>
          <div class="input-group input-group-sm mt-1" style="max-width:420px;">
            <input type="password" class="form-control" id="viewPassword" value="••••••••••" disabled>
            <button class="btn btn-outline-primary" id="btnReveal">Reveal</button>
            <button class="btn btn-outline-secondary" id="btnCopy" disabled>Copy</button>
          </div>
          <div class="form-text text-muted">Visible on demand for authorized users.</div>
        </div>`;
    }

    // Reveal / Copy handlers
    (function wirePassword(){
      const btnReveal = el('btnReveal');
      const btnCopy   = el('btnCopy');
      const inp       = el('viewPassword');
      const masked    = '•'.repeat(10);
      if (!btnReveal || !btnCopy || !inp) return;

      btnReveal.addEventListener('click', async (e)=>{
        e.preventDefault();
        if (btnReveal.dataset.state === 'shown') {
          inp.value = masked; inp.type = 'password';
          btnCopy.disabled = true;
          btnReveal.textContent = 'Reveal'; btnReveal.dataset.state = 'hidden';
          return;
        }
        btnReveal.disabled = true; btnReveal.textContent = 'Revealing...';
        try {
          const r = await fetchJSON(`${BASE}/password_plain/${s.id}`);
          if (r.ok && r.data && r.data.status === 'success') {
            inp.value = r.data.data.password || '';
            inp.type  = 'text';
            btnCopy.disabled = !inp.value;
            btnReveal.textContent = 'Hide'; btnReveal.dataset.state = 'shown';
          } else {
            alert((r.data && r.data.error && (r.data.error.message || r.data.error)) || 'Failed to reveal password');
            btnReveal.textContent = 'Reveal';
          }
        } catch {
          alert('Network error'); btnReveal.textContent = 'Reveal';
        } finally {
          btnReveal.disabled = false;
        }
      });

      btnCopy.addEventListener('click', async ()=>{
        try { await navigator.clipboard.writeText(inp.value); btnCopy.textContent = 'Copied'; setTimeout(()=>btnCopy.textContent='Copy',1200); }
        catch(e){ inp.select(); document.execCommand('copy'); }
      });
    })();

    /* ---------- Security ---------- */
    setTxt('sec2fa', (parseInt(s.tfa_status,10)===1 ? 'Enabled' : 'Disabled') + (titleCase(s.tfa_source ? ` (${s.tfa_source})` : '')));
    (function wireHash(){
      const hashEl = el('secHash');
      const btnCopyHash = el('btnCopyHash');
      const btnToggle   = el('btnToggleHash');
      const full = s.account_password || '—';
      if (hashEl) hashEl.textContent = trunc(full);
      if (btnCopyHash) btnCopyHash.onclick = () => { if (full && full!=='—') navigator.clipboard.writeText(full); };
      if (btnToggle && hashEl) {
        let expanded = false;
        btnToggle.onclick = () => { expanded = !expanded; hashEl.textContent = expanded ? (full || '—') : trunc(full); };
      }
    })();
    setTxt('secBackup', (s.backup_codes && String(s.backup_codes).trim() !== '') ? s.backup_codes : '—');

    /* ---------- Lifecycle ---------- */
    setTxt('lifeStart',   s.start_date || '—');
    setTxt('lifeEnd',     s.end_date || '—');
    setTxt('lifeReminder', (s.reminder_days_before ?? '—'));
    setTxt('lifeGrace',    (s.grace_days ?? '—'));
    setTxt('lifeLastPay',  s.last_payment_date || '—');
    setTxt('lifeCreated',  s.created_at || '—');
    setTxt('lifeUpdated',  s.updated_at || '—');

    /* ---------- Billing / Ownership ---------- */
    setTxt('billMethod',   (s.payment_method_id ?? '—'));
    setTxt('billAssigned', s.assigned_name || (s.assigned_to ?? '—'));
    setTxt('billSeats',    (s.seats ?? '—'));
    setTxt('billLicense',  s.license_key || '—');

    /* ---------- Notes / Meta ---------- */
    setTxt('notesBox', (s.notes && String(s.notes).trim() !== '') ? s.notes : '—');
    (function wireMeta(){
      const metaEl = el('metaBox');
      const btnCopy = el('btnCopyMeta');
      if (!metaEl) return;
      let metaRaw = (s.meta && String(s.meta).trim() !== '') ? s.meta : '—';
      try { if (s.meta) metaRaw = JSON.stringify(JSON.parse(s.meta), null, 2); } catch(_) { /* leave raw */ }
      metaEl.textContent = metaRaw;
      if (btnCopy) btnCopy.onclick = ()=>{ const txt = metaEl.textContent || ''; if (txt && txt!=='—') navigator.clipboard.writeText(txt); };
    })();

    /* ---------- Payments ---------- */
    const vp = document.querySelector('#tblViewPayments tbody');
    if (vp) {
      vp.innerHTML = pays.length ? pays.map(r=>{
        const hasFile = !!r.receipt_file;
        const href    = hasFile ? fileUrl(r.receipt_file) : '';
        const link    = hasFile
          ? `<a href="${href}" target="_blank" rel="noopener" class="btn btn-sm btn-light-primary">View</a>`
          : '—';
        return `
          <tr>
            <td>${esc(r.payment_date||'')}</td>
            <td class="text-end">${fmt(r.amount)}</td>
            <td>${esc(r.currency||'')}</td>
            <td>${esc(r.method||'')}</td>
            <td>${esc(r.transaction_id||'')}</td>
            <td>${esc(r.notes||'')}</td>
            <td>${link}</td>
          </tr>`;
      }).join('')
      : `<tr><td colspan="7" class="text-muted text-center py-3">No payments.</td></tr>`;
    }

    /* ---------- Show modal ---------- */
    const vm = el('modalView');
    const m  = (window.bootstrap && window.bootstrap.Modal) ? window.bootstrap.Modal.getOrCreateInstance(vm) : null;
    m && m.show();
  }

  // Expose to main view
  window.SubView = { open: openViewOnly };
})();
</script>
