<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Request Information</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:          #f4f6f9;
    --surface:     #ffffff;
    --border:      #e2e8f0;
    --border-focus:#3b82f6;
    --text-primary:#0f172a;
    --text-muted:  #64748b;
    --text-hint:   #94a3b8;
    --accent:      #2563eb;
    --accent-dark: #1d4ed8;
    --accent-soft: #eff6ff;
    --success:     #16a34a;
    --success-soft:#f0fdf4;
    --label-size:  13px;
    --radius:      8px;
    --shadow:      0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.06);
    --shadow-lg:   0 8px 40px rgba(0,0,0,.10);
    --transition:  .18s ease;
  }

  html, body {
    min-height: 100%;
    font-family: 'DM Sans', sans-serif;
    color: var(--text-primary);
    -webkit-font-smoothing: antialiased;
  }

  body {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 30px 0px 0px 0px;
  }

  /* ── Card ── */
  .lf-card {
    width: 100%;
    max-width: 800px;
    background: var(--surface);
    border-radius: 15px;
    overflow: hidden;
    animation: fadeUp .4s ease both;
  }

  @keyframes fadeUp {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
  }

  /* ── Body ── */
  .lf-body {
    padding: 28px 32px;
  }


  .lf-section + .lf-section {
    margin-top: 16px;
  }

  /* ── Grid ── */
  .lf-row {
    display: grid;
    gap: 14px;
    margin-bottom: 14px;
  }

  .lf-row:last-child { margin-bottom: 0; }

  .lf-row.cols-1 { grid-template-columns: 1fr; }
  .lf-row.cols-2 { grid-template-columns: 1fr 1fr; }
  .lf-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }

  /* ── Form group ── */
  .lf-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .lf-label {
    font-size: var(--label-size);
    font-weight: 500;
    color: var(--text-primary);
    line-height: 1;
  }

  .lf-label .req {
    color: #ef4444;
    margin-left: 2px;
  }

  /* ── Controls ── */
  .lf-control {
    height: 38px;
    width: 100%;
    padding: 0 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: #fff;
    font-family: inherit;
    font-size: 14px;
    color: var(--text-primary);
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
    appearance: none;
    -webkit-appearance: none;
  }

  .lf-control::placeholder { color: var(--text-hint); }

  .lf-control:focus {
    border-color: var(--border-focus);
    box-shadow: 0 0 0 3px rgba(59,130,246,.12);
    background: #fff;
  }

  .lf-control:hover:not(:focus) { border-color: #cbd5e1; }

  /* input with prefix icon */
  .lf-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
  }

  .lf-input-wrap .lf-prefix {
    position: absolute;
    left: 11px;
    display: flex;
    align-items: center;
    pointer-events: none;
  }

  .lf-input-wrap .lf-prefix svg {
    width: 15px;
    height: 15px;
    stroke: var(--text-hint);
    stroke-width: 1.75;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .lf-input-wrap .lf-control { padding-left: 34px; }

  /* input with suffix text */
  .lf-input-wrap.has-suffix .lf-control { padding-right: 52px; }

  .lf-suffix {
    position: absolute;
    right: 11px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-hint);
    pointer-events: none;
  }

  /* select arrow */
  .lf-select-wrap {
    position: relative;
  }

  .lf-select-wrap::after {
    content: '';
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 0; height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid var(--text-hint);
    pointer-events: none;
  }

  .lf-select-wrap .lf-control { padding-right: 30px; cursor: pointer; }

  /* textarea */
  .lf-textarea {
    height: auto;
    min-height: 90px;
    padding: 10px 12px;
    resize: vertical;
    line-height: 1.5;
  }

  /* ── Honeypot ── */
  .lf-honey {
    position: absolute;
    left: -9999px;
    visibility: hidden;
    pointer-events: none;
    tabindex: -1;
  }

  /* ── Footer ── */
  .lf-footer {
    padding: 20px 32px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-top: 1px solid var(--border);
    margin-top: 8px;
  }

  /* ── Submit button ── */
  .lf-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 24px;
    height: 40px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition), box-shadow var(--transition), transform var(--transition);
    white-space: nowrap;
    flex-shrink: 0;
  }

  .lf-btn svg {
    width: 16px; height: 16px;
    stroke: #fff;
    stroke-width: 2;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .lf-btn:hover  { background: var(--accent-dark); box-shadow: 0 4px 14px rgba(37,99,235,.35); }
  .lf-btn:active { transform: translateY(1px); }
  .lf-btn:disabled { background: #93c5fd; cursor: default; box-shadow: none; transform: none; }

  /* ── Alert messages ── */
  .lf-alert {
    display: none;
    padding: 12px 16px;
    border-radius: var(--radius);
    font-size: 13.5px;
    font-weight: 500;
    margin: 0 32px 16px;
    align-items: center;
    gap: 10px;
  }

  .lf-alert.show { display: flex; }
  .lf-alert svg { width:16px; height:16px; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }

  .lf-alert.success { background: var(--success-soft); color: var(--success); border: 1px solid #bbf7d0; }
  .lf-alert.success svg { stroke: var(--success); }
  .lf-alert.error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
  .lf-alert.error svg { stroke: #b91c1c; }

  /* ── Responsive ── */
  @media (max-width: 540px) {
    body { padding: 0; }
    .lf-card { border-radius: 0; box-shadow: none; min-height: 100vh; }
    .lf-body, .lf-footer { padding-left: 20px; padding-right: 20px; }
    .lf-alert { margin-left: 20px; margin-right: 20px; }
    .lf-row.cols-2, .lf-row.cols-3 { grid-template-columns: 1fr; }
    .lf-footer { flex-direction: column; align-items: stretch; }
    .lf-btn { justify-content: center; }
  }
</style>
</head>
<body>

<div class="lf-card">

  <!-- Alert -->
  <div class="lf-alert" id="lf-alert" role="alert">
    <svg id="lf-alert-icon" viewBox="0 0 24 24"></svg>
    <span id="lf-alert-text"></span>
  </div>

  <!-- Form -->
  <form id="lf-form" novalidate>

    <!-- Honeypot -->
    <div class="lf-honey" aria-hidden="true">
      <input type="text" name="website_url" tabindex="-1" autocomplete="off">
    </div>

    <div class="lf-body">

      <!-- Practice Info -->
      <div class="lf-section">

        <div class="lf-row cols-1">
          <div class="lf-group">
            <label class="lf-label" for="lf_practice_name">Practice Name <span class="req">*</span></label>
            <input class="lf-control" type="text" id="lf_practice_name" name="practice_name"
                   placeholder="e.g. Valley Medical Group" required autocomplete="organization">
          </div>
        </div>

        <div class="lf-row cols-2">
          <div class="lf-group">
            <label class="lf-label" for="lf_practice_type">Practice Type</label>
            <div class="lf-select-wrap">
              <select class="lf-control" id="lf_practice_type" name="practice_type">
                <option value="">— Select —</option>
                <option value="solo">Solo Practice</option>
                <option value="group">Group Practice</option>
                <option value="multi-specialty">Multi-Specialty</option>
                <option value="hospital">Hospital</option>
                <option value="clinic">Clinic</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>
          <div class="lf-group">
            <label class="lf-label" for="lf_specialty">Specialty</label>
            <input class="lf-control" type="text" id="lf_specialty" name="specialty"
                   placeholder="e.g. Cardiology, Family Medicine">
          </div>
        </div>

        <div class="lf-row cols-1">
          <div class="lf-group">
            <label class="lf-label" for="lf_monthly_collections">Monthly Collections</label>
            <div class="lf-input-wrap has-suffix">
              <span class="lf-prefix" style="font-size:14px;font-weight:600;color:#94a3b8;">$</span>
              <input class="lf-control" type="number" id="lf_monthly_collections" name="monthly_collections"
                     min="0" step="0.01" placeholder="0.00" style="padding-left:24px;">
            </div>
          </div>
        </div>

        <div class="lf-row cols-2">
          <div class="lf-group">
            <label class="lf-label" for="lf_contact_person">Contact Person</label>
            <div class="lf-input-wrap">
              <span class="lf-prefix">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
              </span>
              <input class="lf-control" type="text" id="lf_contact_person" name="contact_person"
                     placeholder="Full name" autocomplete="name">
            </div>
          </div>
          <div class="lf-group">
            <label class="lf-label" for="lf_contact_phone">Phone Number</label>
            <div class="lf-input-wrap">
              <span class="lf-prefix">
                <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.07 11.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3 .84h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16z"/></svg>
              </span>
              <input class="lf-control" type="tel" id="lf_contact_phone" name="contact_phone"
                     placeholder="(123) 456-7890" autocomplete="tel">
            </div>
          </div>
        </div>

        <div class="lf-row cols-1">
          <div class="lf-group">
            <label class="lf-label" for="lf_contact_email">Email Address</label>
            <div class="lf-input-wrap">
              <span class="lf-prefix">
                <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
              </span>
              <input class="lf-control" type="email" id="lf_contact_email" name="contact_email"
                     placeholder="email@practice.com" autocomplete="email">
            </div>
          </div>
        </div>
        
      </div>


      <!-- Notes -->
      <div class="lf-section">
        <div class="lf-group">
          <label class="lf-label" for="lf_internal_notes">Tell us about your needs</label>
          <textarea class="lf-control lf-textarea" id="lf_internal_notes" name="internal_notes"
                    placeholder="Describe what you're looking for, current challenges, or any questions you have…"></textarea>
        </div>
      </div>

    </div><!-- /lf-body -->

    <!-- Hidden system fields -->
    <input type="hidden" name="lead_source" value="Website Inquiry">
    <input type="hidden" name="form_token"  value=TOKEN2323098DHHSOIEUWE097802384>

    <!-- Footer -->
    <div class="lf-footer">
      <button type="submit" class="lf-btn" id="lf-submit">
        <svg viewBox="0 0 24 24"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9l20-7z"/></svg>
        Submit Form
      </button>
    </div>

  </form>
</div>

<script>
(function () {
  'use strict';

  // ─────────────────────────────────────────────
  // ELEMENTS (safe guards)
  // ─────────────────────────────────────────────
  var form    = document.getElementById('lf-form');
  var btn     = document.getElementById('lf-submit');
  var alertEl = document.getElementById('lf-alert');
  var alertTx = document.getElementById('lf-alert-text');
  var alertIc = document.getElementById('lf-alert-icon');

  if (!form || !btn) {
    console.error('Lead form initialization failed: missing elements.');
    return;
  }

  // ─────────────────────────────────────────────
  // CONFIG (future-proof endpoint)
  // ─────────────────────────────────────────────
  var ACTION_URL = window.location.origin + '/lead-submit';

  var ICON_SUCCESS = '<polyline points="20 6 9 17 4 12"/>';
  var ICON_ERROR   = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';

  // Prevent duplicate submissions
  var isSubmitting = false;

  // ─────────────────────────────────────────────
  // SUBMIT HANDLER
  // ─────────────────────────────────────────────
  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (isSubmitting) return;

    var name = (form.querySelector('[name="practice_name"]')?.value || '').trim();

    if (!name) {
      showAlert('Please enter your practice name.', 'error');
      form.querySelector('[name="practice_name"]')?.focus();
      return;
    }

    // Honeypot check (anti-spam)
    var honeypot = form.querySelector('[name="website_url"]');
    if (honeypot && honeypot.value) {
      console.warn('Spam detected.');
      return;
    }

    isSubmitting = true;
    setLoading(true);
    hideAlert();

    // Use FormData (CI compatible)
    var formData = new FormData(form);

    fetch(ACTION_URL, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin' // safe default
    })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('Server error: ' + response.status);
      }
      return response.json();
    })
    .then(function (data) {
      if (data && data.status === 'success') {
        form.reset();
        showAlert(
          data.message || 'Thank you! We will be in touch shortly.',
          'success'
        );
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        showAlert(
          (data && data.message) || 'Submission failed. Please try again.',
          'error'
        );
      }
    })
    .catch(function (err) {
      console.error('Lead form error:', err);
      showAlert(
        'Network or server issue. Please try again shortly.',
        'error'
      );
    })
    .finally(function () {
      isSubmitting = false;
      setLoading(false);
    });
  });

  // ─────────────────────────────────────────────
  // UI HELPERS
  // ─────────────────────────────────────────────
  function setLoading(on) {
    btn.disabled = on;

    var label = btn.querySelector('span');
    if (label) {
      label.textContent = on ? 'Sending…' : 'Submit Form';
    } else {
      btn.lastChild.textContent = on ? ' Sending…' : ' Submit Form';
    }
  }

  function showAlert(message, type) {
    if (!alertEl) return;

    alertTx.textContent = message;
    alertIc.innerHTML   = type === 'success' ? ICON_SUCCESS : ICON_ERROR;
    alertEl.className   = 'lf-alert show ' + type;
  }

  function hideAlert() {
    if (alertEl) {
      alertEl.className = 'lf-alert';
    }
  }

})();
</script>

</body>
</html>