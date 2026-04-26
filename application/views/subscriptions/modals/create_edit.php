<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Create/Edit Subscription modal -->
<div class="modal fade" id="modalSub" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formSub" autocomplete="off" class="app-form">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="modalSubTitle">Add Subscription</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="×"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="subId">

          <div class="row g-4 p-3">
            <div class="col-md-4">
              <label class="form-label">Subscription Title</label>
              <input type="text" name="title" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Vendor / Platform Name</label>
              <input type="text" name="vendor" class="form-control">
            </div>

            <div class="col-md-4">
              <label class="form-label">Account URL</label>
              <input type="url" name="vendor_url" class="form-control" placeholder="https://">
            </div>

            <div class="col-md-3">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select">
                <option value="">—</option>
                <?php foreach (($categories ?? []) as $c): ?>
                  <option value="<?php echo (int)$c['id']; ?>"><?php echo html_escape($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="cancelled">Cancelled</option>
                <option value="expired">Expired</option>
                <option value="trial">Trial</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">2FA Status</label>
              <select name="tfa_status" class="form-select">
                <option value="">—</option>
                <option value="0">Disabled</option>
                <option value="1">Enabled</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">2FA Source</label>
              <select name="tfa_source" class="form-select">
                <option value="">—</option>
                <option value="authenticator">Authenticator</option>
                <option value="mobile">Mobile App</option>
                <option value="sms">SMS</option>
                <option value="email">Email</option>
                <option value="both">Both (SMS & Email)</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Type</label>
              <select name="subscription_type" class="form-select">
                <option value="">—</option>
                <option value="recurring">Recurring</option>
                <option value="one-time">One-time</option>
                <option value="lifetime">Lifetime</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Auto-Renew</label>
              <select name="auto_renew" class="form-select">
                <option value="">—</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Payment Cycle</label>
              <select name="payment_cycle" id="payment_cycle" class="form-select">
                <option value="">—</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="annually">Annually</option>
                <option value="custom">Custom (days)</option>
              </select>
            </div>

            <div class="col-md-3" id="wrapCycleDays" style="display:none;">
              <label class="form-label">Cycle Days</label>
              <input type="number" name="cycle_days" class="form-control" placeholder="e.g., 45">
            </div>

            <div class="col-md-3">
              <label class="form-label">Payment Method</label>
              <select name="payment_method_id" class="form-select">
                <option value="">—</option>
                <option value="1">Credit Card</option>
                <option value="2">Bank Transfer</option>
                <option value="3">PayPal</option>
                <option value="4">Cash</option>
                <option value="5">Cheque</option>
                <option value="6">Other</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Account Email</label>
              <input type="email" name="account_email" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Account Phone</label>
              <input type="text" name="account_phone" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Account Password (plain)</label>
              <input type="text" name="account_password_plain" class="form-control" placeholder="Will be stored as HASH">
            </div>

            <div class="col-md-3">
              <label class="form-label">Assigned To</label>
              <select name="assigned_to" class="form-select">
                <option value="">—</option>
                <?php foreach (($assignees ?? []) as $u): ?>
                  <option value="<?php echo (int)$u['id']; ?>">
                    <?php echo html_escape($u['name'] ?? $u['email'] ?? ('User#'.$u['id'])); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Amount</label>
              <input type="number" step="0.01" name="amount" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Currency</label>
              <?php $base = $base_currency ?? 'USD'; ?>
              <select name="currency" class="form-select">
                <option value="">—</option>
                <?php foreach (['USD','EUR','PKR','INR','GBP','CNY','CAD'] as $c): ?>
                  <option value="<?= $c ?>" <?= $c === $base ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Seats / Users</label>
              <input type="number" name="seats" class="form-control">
            </div>

            <div class="col-md-5">
              <label class="form-label">License Key</label>
              <input type="text" name="license_key" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Next Renewal</label>
              <input type="date" name="next_renewal_date" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Reminder Days</label>
              <input type="number" name="reminder_days_before" class="form-control" value="7">
            </div>

            <div class="col-md-3">
              <label class="form-label">Grace Days</label>
              <input type="number" name="grace_days" class="form-control" value="0">
            </div>

            <div class="col-md-4">
              <label class="form-label">Backup Codes</label>
              <textarea name="backup_codes" rows="2" class="form-control" placeholder="JSON array or newline-separated"></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Subscription Notes</label>
              <textarea name="notes" rows="2" class="form-control" placeholder="Any special subscription details or notes..."></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Meta (JSON-like)</label>
              <textarea name="meta" rows="2" class="form-control" placeholder='{"key":"value"}'></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light-primary btn-sm" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary btn-sm" id="btnSaveSub">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const BASE = '<?= site_url('subscriptions'); ?>';

  async function fetchJSON(url, opts = {}) {
    const resp = await fetch(url, {
      credentials: 'same-origin',
      headers: {'X-Requested-With':'XMLHttpRequest'},
      ...opts
    });
    const text = await resp.text();
    try { return { ok: resp.ok, status: resp.status, data: JSON.parse(text) }; }
    catch(e){ return { ok:false, status: resp.status, data: {status:'error', error:{message:'Unexpected response'}}}; }
  }

  function getModal(){
    const el = document.getElementById('modalSub');
    return (window.bootstrap && window.bootstrap.Modal)
      ? window.bootstrap.Modal.getOrCreateInstance(el)
      : null;
  }

  function toggleCycleDays(){
    const cycle = document.getElementById('payment_cycle');
    const wrap  = document.getElementById('wrapCycleDays');
    if (!cycle || !wrap) return;
    wrap.style.display = (cycle.value === 'custom') ? '' : 'none';
  }

  function bindCycleToggle(){
    const cycle = document.getElementById('payment_cycle');
    if (cycle && !cycle.dataset.bound) {
      cycle.addEventListener('change', toggleCycleDays);
      cycle.dataset.bound = '1';
    }
  }

  function bindFieldsFromRecord(form, r){
    const fields = [
      'title','category_id','status','vendor','vendor_url','account_email','account_phone',
      'tfa_status','tfa_source','subscription_type','payment_cycle','cycle_days',
      'start_date','next_renewal_date','end_date','reminder_days_before','grace_days',
      'auto_renew','amount','currency','seats','license_key','payment_method_id','assigned_to',
      'backup_codes','notes','meta'
    ];
    fields.forEach(k=>{
      if (form[k] !== undefined && form[k] !== null) {
        if (form[k].type === 'checkbox') form[k].checked = !!(parseInt(r[k],10)===1);
        else form[k].value = (r[k] ?? '');
      }
    });
  }

  async function open(id){
    const modalEl = document.getElementById('modalSub');
    const modal   = getModal();
    if (!modalEl || !modal) { alert('Modal library missing'); return; }

    const form     = document.getElementById('formSub');
    const titleEl  = document.getElementById('modalSubTitle');

    // reset
    form.reset();
    document.getElementById('subId').value = '';
    titleEl.textContent = id ? 'Edit Subscription' : 'Add New Subscription';

    bindCycleToggle();
    toggleCycleDays();

    if (id) {
      const res = await fetchJSON(`${BASE}/view/${id}`);
      if (!(res.ok && res.data && res.data.status === 'success')) {
        alert('Failed to load subscription');
        return;
      }
      const r = res.data.data.subscription || res.data.data || res.data.subscription || {};
      document.getElementById('subId').value = r.id || '';
      bindFieldsFromRecord(form, r);
      toggleCycleDays();
    }

    form.onsubmit = async function(e){
      e.preventDefault();
      const fd  = new FormData(form);
      const idv = document.getElementById('subId').value;
      const url = idv ? `${BASE}/update/${idv}` : `${BASE}/store`;
      const res = await fetchJSON(url, {method:'POST', body: fd});
      if (res.ok && res.data && res.data.status === 'success') {
        modal.hide();
        // refresh grid if available on main page
        if (typeof window.loadList === 'function') { try { await window.loadList(); } catch(_){} }
      } else {
        alert((res.data && res.data.error && (res.data.error.message || res.data.error)) || 'Save failed');
      }
    };

    modal.show();
  }

  // expose as global so the main view can call: SubForm.open(id)
  window.SubForm = { open };
})();
</script>
