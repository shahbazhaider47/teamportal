<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Payments modal -->
<div class="modal fade" id="modalPayments" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formPayment" autocomplete="off" enctype="multipart/form-data" class="app-form">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white">Subscription Payments</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="×"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="paySubId">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Payment Date</label>
              <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Amount</label>
              <input type="number" step="0.01" name="amount" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Currency</label>
              <input type="text" name="currency" class="form-control" value="<?= html_escape($base_currency ?? 'USD'); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Method</label>
              <input type="text" name="method" class="form-control" placeholder="Card, Bank, PayPal...">
            </div>
            <div class="col-md-6">
              <label class="form-label">Transaction #</label>
              <input type="text" name="transaction_id" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Receipt</label>
              <input type="file" name="receipt" class="form-control">
            </div>
            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <input type="text" name="notes" class="form-control">
            </div>

            <div class="col-md-12 text-end">
              <button type="submit" class="btn btn-primary btn-sm">
                <span class="btn-text">Add Payment</span>
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              </button>
            </div>
          </div>

          <hr class="my-3">

          <div class="table-responsive">
            <table class="table table-sm table-striped" id="tblPayments">
              <thead>
                <tr>
                  <th>Date</th>
                  <th class="text-end">Amount</th>
                  <th>Currency</th>
                  <th>Method</th>
                  <th>Txn</th>
                  <th>Notes</th>
                  <th style="width:90px;">Actions</th>
                </tr>
              </thead>
              <tbody><!-- rows --></tbody>
            </table>
          </div>
        </div>
      </form>
        <div class="modal-footer">
          <button class="btn btn-light-primary btn-sm" type="button" data-bs-dismiss="modal">Close</button>
        </div>      
    </div>
  </div>
</div>

<script>
(function () {
  const BASE = '<?= site_url('subscriptions'); ?>';

  function fmt(n){ try { return (n===null||n===undefined||n==='') ? '' : parseFloat(n).toFixed(2); } catch(e){ return n || ''; } }
  function esc(s){ const d=document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
  async function fetchJSON(url, opts = {}) {
    const resp = await fetch(url, { credentials: 'same-origin', headers: {'X-Requested-With':'XMLHttpRequest'}, ...opts });
    const text = await resp.text();
    try { return { ok: resp.ok, status: resp.status, data: JSON.parse(text) }; }
    catch(e){ return { ok:false, status: resp.status, data: {status:'error', error:{message:'Unexpected response'}}}; }
  }

  function getModal(){ 
    return (window.bootstrap && window.bootstrap.Modal) 
      ? window.bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPayments')) 
      : null;
  }

  async function loadPayments(id){
    const tBody = document.querySelector('#tblPayments tbody');
    tBody.innerHTML = `<tr><td colspan="7" class="text-muted text-center py-3">Loading…</td></tr>`;
    const res = await fetchJSON(`${BASE}/payments/${id}`);
    if (!(res.ok && res.data && res.data.status === 'success')) {
      tBody.innerHTML = `<tr><td colspan="7" class="text-danger text-center py-3">Failed to load</td></tr>`;
      return;
    }
    const rows = (res.data.data && res.data.data.payments) ? res.data.data.payments : (res.data.payments || []);
    if (!rows.length) {
      tBody.innerHTML = `<tr><td colspan="7" class="text-muted text-center py-3">No payments yet.</td></tr>`;
      return;
    }
    tBody.innerHTML = rows.map(r => `
      <tr data-id="${r.id}">
        <td>${esc(r.payment_date || '')}</td>
        <td class="text-end">${fmt(r.amount)}</td>
        <td>${esc(r.currency || '')}</td>
        <td>${esc(r.method || '')}</td>
        <td>${esc(r.transaction_id || '')}</td>
        <td>${esc(r.notes || '')}</td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-danger btnDelPay" data-id="${r.id}" title="Delete"><i class="ti ti-trash"></i></button>
          </div>
        </td>
      </tr>
    `).join('');

    tBody.querySelectorAll('.btnDelPay').forEach(b=>{
      b.addEventListener('click', async function(){
        if (!confirm('Delete this payment?')) return;
        const pid = this.dataset.id;
        const r = await fetchJSON(`${BASE}/delete_payment/${pid}`, {method:'POST'});
        if (r.ok && r.data && r.data.status === 'success') {
          await loadPayments(id);
          // optional: refresh main list if function exists
          if (typeof window.loadList === 'function') { try { await window.loadList(); } catch(_){} }
        } else {
          alert((r.data && r.data.error && (r.data.error.message || r.data.error)) || 'Delete failed');
        }
      });
    });
  }

  async function wireForm(id){
    const form = document.getElementById('formPayment');
    form.reset();
    document.getElementById('paySubId').value = id;

    form.onsubmit = async function(e){
      e.preventDefault();
      const btn = form.querySelector('button[type="submit"]');
      const spn = btn.querySelector('.spinner-border');
      const txt = btn.querySelector('.btn-text');
      btn.disabled = true; spn.classList.remove('d-none'); txt.classList.add('d-none');

      const fd = new FormData(form); // includes 'receipt' file
      const res = await fetchJSON(`${BASE}/add_payment/${id}`, {method:'POST', body: fd});

      btn.disabled = false; spn.classList.add('d-none'); txt.classList.remove('d-none');

      if (res.ok && res.data && res.data.status === 'success') {
        form.reset(); document.getElementById('paySubId').value = id;
        await loadPayments(id);
        // optional: refresh main list if function exists
        if (typeof window.loadList === 'function') { try { await window.loadList(); } catch(_){} }
      } else {
        alert((res.data && res.data.error && (res.data.error.message || res.data.error)) || 'Failed to add payment');
      }
    };
  }

  async function open(id){
    await wireForm(id);
    await loadPayments(id);
    const modal = getModal();
    modal && modal.show();
  }

  // Expose for main view to call: SubPayments.open(id)
  window.SubPayments = { open };
})();
</script>
