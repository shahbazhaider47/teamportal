<?php defined('BASEPATH') or exit('No direct script access allowed');

/** @var array $existing_data */
$S = is_array($existing_data ?? null) ? $existing_data : [];

$token       = $S['cron_auth_token']         ?? '';
$httpEnabled = (int)($S['cron_enable_http']  ?? 0);
$lockTtl     = (int)($S['cron_lock_ttl']     ?? 600);
$retention   = (int)($S['cron_retention_days'] ?? 90);

$baseUrl   = site_url('cron/run');
$healthUrl = site_url('cron/health');
$unlockUrl = site_url('cron/unlock');

// AJAX JSON endpoint we add below in Settings controller
$rotateUrl = site_url('settings/cron/rotate-token');

$cliExample  = '*/1 * * * * php ' . FCPATH . 'index.php cron run >/dev/null 2>&1';
$httpExample = '*/1 * * * * curl -s "' . $baseUrl . '?token=' . html_escape($token) . '"';
?>

<div class="card-body app-form">

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Enable HTTP Runner</label>
      <select name="cron_enable_http" class="form-select" id="cron_enable_http">
        <option value="0" <?= $httpEnabled ? '' : 'selected' ?>>No (Recommended)</option>
        <option value="1" <?= $httpEnabled ? 'selected' : '' ?>>Yes</option>
      </select>
      <small class="text-muted d-block mt-1">
        If enabled, you can trigger cron via a tokenized URL. CLI is still preferred.
      </small>
    </div>

    <div class="col-md-3">
      <label class="form-label">Lock TTL (seconds)</label>
      <input type="number" name="cron_lock_ttl" id="cron_lock_ttl" class="form-control" value="<?= $lockTtl ?>">
      <small class="text-muted d-block mt-1">
        Prevents overlapping runs. Stale locks auto-expire after this TTL.
      </small>
    </div>

    <div class="col-md-3">
      <label class="form-label">Log Retention (days)</label>
      <input type="number" name="cron_retention_days" id="cron_retention_days" class="form-control" value="<?= $retention ?>">
      <small class="text-muted d-block mt-1">
        Used by the built-in cleanup task (activity, notifications, cron history).
      </small>
    </div>
  </div>

  <div class="row g-3 align-items-start mt-1">
    <div class="col-md-12">
      <label class="form-label">HTTP Auth Token</label>
      <div class="input-group">
        <input type="text" id="cron_token" class="form-control" value="<?= html_escape($token) ?>" readonly>
        <button class="btn btn-light-primary btn-sm" id="btn-regen-token" type="button">Regenerate</button>
      </div>
      <small class="text-muted d-block mt-1">
        Share this token only with your scheduler. Rotating will invalidate old URLs.
      </small>
    </div>
  </div>
  
<?php if ($httpEnabled): ?>
  <div class="col-md-12 mt-3">
    <div class="d-flex flex-wrap gap-2 mt-2">
      <a href="#" class="btn btn-outline-primary btn-sm" id="btn-cron-run"
         data-url="<?= $baseUrl . '?token=' . urlencode($token) ?>">Run Manually</a>

      <a href="#" class="btn btn-primary btn-sm" id="btn-cron-health"
         data-url="<?= $healthUrl . '?token=' . urlencode($token) ?>">Check Health</a>

      <a href="#" class="btn btn-outline-danger btn-sm" id="btn-cron-unlock"
         data-url="<?= $unlockUrl . '?token=' . urlencode($token) ?>">Unlock</a>
    </div>
    <small class="text-muted d-block mt-2">
      <strong>Run Manually</strong> triggers all due tasks immediately (HTTP path).<br>
      <strong>Health</strong> shows last run time, lock status, and tasks due now.<br>
      <strong>Unlock</strong> clears a stuck lock so the next run can start.
    </small>
  </div>
<?php else: ?>
  <div class="col-md-12 mt-3">
    <div class="alert alert-warning mt-2 mb-0 py-1 px-2 small">
      HTTP runner is disabled. Enable it above or use the CLI command shown below.
    </div>
  </div>
<?php endif; ?>

    
  <hr class="my-4">

  <h6>How to Schedule</h6>
  <small>CLI (Recommended): </small>
  <pre class="bg-light-primary p-2 border rounded mb-2"><?= $cliExample ?></pre>
  <small>HTTP (Fallback): </small>
  <pre class="bg-light-primary p-2 border rounded"><?= $httpExample ?></pre>

  <p class="text-muted small mb-0">
    CLI runs set the <code>cron_has_run_from_cli</code> flag and do not require a token.
  </p>
</div>

<!-- Modal for AJAX results -->
<div class="modal fade" id="cronResultModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">Cron Result</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="cronResultBody">
        <div class="text-center py-5">
          <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const tokenInput  = document.getElementById('cron_token');
  const enableSel   = document.getElementById('cron_enable_http');
  const ttlInput    = document.getElementById('cron_lock_ttl');
  const keepInput   = document.getElementById('cron_retention_days');

  const btnRun      = document.getElementById('btn-cron-run');
  const btnHealth   = document.getElementById('btn-cron-health');
  const btnUnlock   = document.getElementById('btn-cron-unlock');
  const btnRegen    = document.getElementById('btn-regen-token');

  const modalEl     = document.getElementById('cronResultModal');
  const modalBody   = document.getElementById('cronResultBody');
  let   bsModal     = null;

  function showModal(title, html){
    modalEl.querySelector('.modal-title').textContent = title || 'Cron Result';
    modalBody.innerHTML = html || '';
    if (!bsModal) bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
  }

  function pretty(obj){
    try { return JSON.stringify(obj, null, 2); } catch(e){ return String(obj); }
  }

  async function doJsonGet(url){
    modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
    try {
      const r = await fetch(url, {cache:'no-store'});
      const ct = (r.headers.get('content-type')||'').toLowerCase();
      if (ct.includes('application/json')) {
        const j = await r.json();
        return { ok: r.ok, data: j };
      }
      const t = await r.text();
      return { ok: r.ok, data: t };
    } catch (e) {
      return { ok: false, data: String(e) };
    }
  }

  btnRun?.addEventListener('click', async (e)=>{
    e.preventDefault();
    const url = "<?= $baseUrl ?>?token=" + encodeURIComponent(tokenInput.value || '');
    showModal('Run Manually', '');
    const res = await doJsonGet(url);
    const body = (typeof res.data === 'object')
      ? '<pre class="mb-0 small">'+escapeHtml(pretty(res.data))+'</pre>'
      : '<div class="small">'+escapeHtml(String(res.data))+'</div>';
    showModal('Run Manually', body);
  });

  btnHealth?.addEventListener('click', async (e)=>{
    e.preventDefault();
    const url = "<?= $healthUrl ?>?token=" + encodeURIComponent(tokenInput.value || '');
    showModal('Cron Health', '');
    const res = await doJsonGet(url);
    const body = (typeof res.data === 'object')
      ? '<pre class="mb-0 small">'+escapeHtml(pretty(res.data))+'</pre>'
      : '<div class="small">'+escapeHtml(String(res.data))+'</div>';
    showModal('Cron Health', body);
  });

  btnUnlock?.addEventListener('click', async (e)=>{
    e.preventDefault();
    const url = "<?= $unlockUrl ?>?token=" + encodeURIComponent(tokenInput.value || '');
    showModal('Unlock Cron', '');
    const res = await doJsonGet(url);
    const body = (typeof res.data === 'object')
      ? '<pre class="mb-0 small">'+escapeHtml(pretty(res.data))+'</pre>'
      : '<div class="small">'+escapeHtml(String(res.data))+'</div>';
    showModal('Unlock Cron', body);
  });

  // Regenerate token via AJAX JSON endpoint
  btnRegen?.addEventListener('click', async ()=>{
    // collect current fields so backend can preserve them if needed (optional)
    const payload = new URLSearchParams();
    payload.set('regen', '1');
    payload.set('enable_http', enableSel?.value || '0');
    payload.set('lock_ttl', ttlInput?.value || '600');
    payload.set('retention', keepInput?.value || '90');

    showModal('Regenerate Token', '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>');

    try {
      const r = await fetch("<?= $rotateUrl ?>", {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.toString(),
        cache: 'no-store'
      });
      const ct = (r.headers.get('content-type')||'').toLowerCase();
      if (!ct.includes('application/json')) {
        const t = await r.text();
        showModal('Regenerate Token', '<div class="alert alert-danger">Unexpected response.</div><pre class="small">'+escapeHtml(t)+'</pre>');
        return;
      }
      const j = await r.json();
      if (j && j.ok && j.token) {
        tokenInput.value = j.token;
        showModal('Regenerate Token', '<div class="alert alert-success mb-0">New token generated and saved.</div>');
      } else {
        showModal('Regenerate Token', '<div class="alert alert-danger mb-0">Failed to generate token.</div>');
      }
    } catch (e) {
      showModal('Regenerate Token', '<div class="alert alert-danger mb-0">'+escapeHtml(String(e))+'</div>');
    }
  });

  function escapeHtml(s){
    const d=document.createElement('div'); d.textContent=String(s); return d.innerHTML;
  }
})();
</script>
