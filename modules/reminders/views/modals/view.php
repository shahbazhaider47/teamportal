<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $canViewGlobal = staff_can('view_global', 'reminders'); ?>

<!-- View Reminder Modal -->
<div class="modal fade" id="viewReminderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary">
        <div class="d-flex flex-column">
          <h5 class="modal-title mb-0 text-white" id="vr-title">Reminder Details</h5>
          <small class="opacity-75" id="vr-subtitle">—</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge" id="vr-status-badge">—</span>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      <div class="modal-body">

        <!-- Overview -->
        <div class="card mb-3">
          <div class="card-header fw-semibold">Overview</div>
          <div class="card-body small">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="text-muted d-block">Reminder Title</label>
                <div id="vr-title-val">—</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted d-block">Date & Time</label>
                <div id="vr-date">—</div>
              </div>              
              <div class="col-md-6">
                <label class="text-muted d-block">Priority</label>
                <div><span class="badge text-light-primary capital" id="vr-priority">—</span></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted d-block">Recurring?</label>
                <div id="vr-is-recurring">—</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted d-block">Frequency / Duration</label>
                <div id="vr-recurrence">—</div>
              </div>
              <?php if ($canViewGlobal): ?>
              <div class="col-md-6" id="vr-created-by-wrap">
                <label class="text-muted d-block">Created By</label>
                <div id="vr-created-by">—</div>
              </div>
              <?php endif; ?>
              <div class="col-md-6">
                <label class="text-muted d-block">Completed?</label>
                <div id="vr-completed">—</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted d-block">Completed At</label>
                <div id="vr-completed-at">—</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted d-block">Status</label>
                <div id="vr-status-text">—</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted d-block">Created At</label>
                <div id="vr-created-at">—</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted d-block">Updated At</label>
                <div id="vr-updated-at">—</div>
              </div>
              
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="card mb-3">
          <div class="card-header fw-semibold">Reminder Description</div>
          <div class="card-body small">
            <div class="row g-3">
              <div class="col-12">
                <div id="vr-description" class="text-wrap">—</div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /modal-body -->
    </div>
  </div>
</div>

<script>
(function(){
  // PHP-driven permission flag (used to conditionally show Created By)
  const CAN_VIEW_GLOBAL = <?= $canViewGlobal ? 'true' : 'false' ?>;

  // Helpers
  function esc(s){ const d=document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
  function fmtLong(dtStr){
    if (!dtStr) return '—';
    try {
      const d = new Date(dtStr);
      // Long date/time; adjust if you prefer 24h: hour12:false
      return d.toLocaleString(undefined, {
        weekday:'short', year:'numeric', month:'long', day:'numeric',
        hour:'numeric', minute:'2-digit'
      });
    } catch(e){ return dtStr; }
  }
  function statusFromDate(dtStr){
    if (!dtStr) return {label:'—', class:'bg-secondary'};
    const d = new Date(dtStr);
    const today = new Date(); today.setHours(0,0,0,0);
    const target = new Date(d); target.setHours(0,0,0,0);
    if (target.getTime() === today.getTime())  return {label:'Today',    class:'bg-success'};
    if (target.getTime() >  today.getTime())   return {label:'Upcoming', class:'bg-info'};
    return {label:'Past', class:'bg-danger'};
  }
  function titleCase(s){
    return (s||'')
      .toLowerCase()
      .replace(/\b[a-z]/g, m => m.toUpperCase());
  }

  // Wire up event delegation for all .btn-view-reminder buttons
  document.addEventListener('click', function(ev){
    const btn = ev.target.closest('.btn-view-reminder');
    if (!btn) return;

    // Read data-* attributes from the button
    const d = btn.dataset;
    const id         = d.id || '—';
    const title      = d.title || '—';
    const desc       = d.description || '—';
    const dateRaw    = d.date || '';
    const priority   = d.priority || '—';
    const isRec      = (String(d.is_recurring||'0') === '1' || String(d.is_recurring).toLowerCase()==='true');
    const freq       = d.recurring_frequency || '';
    const dur        = d.recurring_duration || '';
    const createdBy  = d.created_by_name || '';     // only present if you output it on the button
    const completed  = (String(d.is_completed||'0') === '1' || String(d.is_completed).toLowerCase()==='true');
    const completedAt= d.completed_at || '';
    const createdAt  = d.created_at || '';
    const updatedAt  = d.updated_at || '';

    // Compute status from date
    const statusObj  = statusFromDate(dateRaw);

    // Header
    document.getElementById('vr-title').textContent = title || 'Reminder Details';
    document.getElementById('vr-subtitle').textContent = dateRaw ? fmtLong(dateRaw) : '—';

    const badge = document.getElementById('vr-status-badge');
    badge.textContent = statusObj.label;
    badge.className = 'badge ' + statusObj.class;

    // Overview
    document.getElementById('vr-title-val').innerHTML = esc(title);
    document.getElementById('vr-priority').textContent = priority || '—';
    document.getElementById('vr-description').innerHTML = esc(desc);

    // Schedule
    document.getElementById('vr-date').textContent = dateRaw ? fmtLong(dateRaw) : '—';
    document.getElementById('vr-is-recurring').textContent = isRec ? 'Yes' : 'No';
    let recText = '—';
    if (isRec) {
      const f = freq ? titleCase(freq) : '—';
      recText = f + (dur ? ` / ${dur}` : '');
    }
    document.getElementById('vr-recurrence').textContent = recText;

    // Ownership & Status
    if (CAN_VIEW_GLOBAL) {
      const el = document.getElementById('vr-created-by');
      if (el) el.textContent = createdBy ? createdBy : '—';
    }
    document.getElementById('vr-completed').textContent = completed ? 'Yes' : 'No';
    document.getElementById('vr-completed-at').textContent = completedAt ? fmtLong(completedAt) : '—';

    // Metadata
    document.getElementById('vr-status-text').textContent = statusObj.label;
    document.getElementById('vr-created-at').textContent = createdAt ? fmtLong(createdAt) : '—';
    document.getElementById('vr-updated-at').textContent = updatedAt ? fmtLong(updatedAt) : '—';

    // Copy ID
    const copyBtn = document.getElementById('vr-copy-id');
    if (copyBtn) {
      copyBtn.onclick = async () => {
        try { await navigator.clipboard.writeText(String(id)); copyBtn.textContent='Copied'; setTimeout(()=>copyBtn.textContent='Copy ID',1200); }
        catch(_){ /* ignore */ }
      };
    }
  });
})();
</script>