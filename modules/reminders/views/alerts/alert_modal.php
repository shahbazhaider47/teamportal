<!-- Global Reminder Alert Modal (single file) -->
<div class="modal fade" id="autoReminderModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white"><i class="ti ti-clock"></i> Reminder</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="autoReminderBody">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Dismiss</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  if (window.NO_GLOBAL_REMINDER_ALERT) return; // allow pages to opt out

  const MODAL = document.getElementById('autoReminderModal');
  const BODY  = document.getElementById('autoReminderBody');
  const FETCH_URL = "<?= site_url('reminders/alert_next') ?>";
  const ACK_URL   = "<?= site_url('reminders/alert_ack') ?>";

  let lastShownKey = null;

  function esc(s){ const d=document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }

  function renderBody(payload){
    const when   = esc(payload.when || '');
    const title  = esc(payload.title || 'Reminder');
    const desc   = esc(payload.description || '');
    const pr     = String(payload.priority || 'medium').toLowerCase();
    const badge  = {low:'text-bg-success',medium:'text-bg-primary',high:'text-bg-warning',critical:'text-bg-danger'}[pr] || 'text-bg-secondary';

    return `
      <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start justify-content-between">
          <h6 class="mb-0">${title}</h6>
          <span class="badge ${badge}">${pr.charAt(0).toUpperCase()+pr.slice(1)}</span>
        </div>
        <hr>
        ${desc ? `<p class="mb-4">${desc.replace(/\n/g,'<br>')}</p>` : ``}
        ${when ? `<div class="small text-muted"><i class="ti ti-clock"></i> Scheduled For: <strong>${when}</strong></div>` : ``}
      </div>`;
  }

  function show(payload){
    // guard against the same reminder occurrence showing twice
    const key = `${payload.id}|${payload.type}|${payload.occurrence_at}`;
    if (lastShownKey === key) return;
    lastShownKey = key;

    BODY.innerHTML = renderBody(payload);
    const bsModal = new bootstrap.Modal(MODAL);
    bsModal.show();

    // ACK when closed (records that the user saw/dismissed it)
    MODAL.addEventListener('hidden.bs.modal', function onHide(){
      const form = new URLSearchParams();
      form.set('reminder_id', payload.id);
      form.set('occurrence_at', payload.occurrence_at);
      form.set('type', payload.type);
      fetch(ACK_URL, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString() });
      MODAL.removeEventListener('hidden.bs.modal', onHide);
    }, { once:true });
  }

  async function poll(){
    try {
      const r = await fetch(FETCH_URL, { cache:'no-store' });
      if (r.status === 204) return;       // nothing to show now
      if (!r.ok) return;                  // API error, ignore this tick
      const data = await r.json();        // expected fields: id, type, occurrence_at, title, description, when, priority
      if (data && data.id) show(data);
    } catch(e) {}
  }

  document.addEventListener('DOMContentLoaded', function(){
    setTimeout(poll, 2000);         // initial nudge
    setInterval(poll, 60*1000);     // then every minute
  });

  // cleanup any stray backdrops if this is the only modal closing
  MODAL.addEventListener('hidden.bs.modal', () => {
    if (!document.querySelector('.modal.show')) {
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('padding-right');
    }
  });
})();
</script>
