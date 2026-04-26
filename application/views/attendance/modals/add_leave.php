<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI  =& get_instance();
$uid = (int)($CI->session->userdata('user_id') ?? 0);
$currentUserName = '';
if ($uid > 0) {
    $currentUserRow = $CI->db
        ->select('fullname, firstname, lastname, emp_id')
        ->where('id', $uid)
        ->get('users')
        ->row_array();
    if ($currentUserRow) {
        $currentUserName = $currentUserRow['fullname']
            ?? trim(($currentUserRow['firstname'] ?? '') . ' ' . ($currentUserRow['lastname'] ?? ''));
        if (!empty($currentUserRow['emp_id'])) {
            $currentUserName = $currentUserRow['emp_id'] . ' – ' . $currentUserName;
        }
    }
}

$isSuperAdmin     = !empty($is_super_admin);
$canCreate        = !empty($can_create);
$canApply         = !empty($can_apply);
$canViewGlobal    = !empty($can_view_global);
$canOwnTeam       = !empty($can_own_team);
$showUserSelect   = $canCreate || $isSuperAdmin;
$showStatusSelect = $canCreate || $isSuperAdmin;
?>

<div class="modal fade" id="addLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">

      <!-- Header -->
      <div class="modal-header bg-primary py-2">
        <h6 class="modal-title text-white mb-0">
          <i class="ti ti-calendar-plus me-2"></i>
          <?= $showUserSelect ? 'Add Leave' : 'Apply for Leave' ?>
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Policy status bar — replaces Bootstrap alert boxes -->
      <div class="leave-policy-bar px-3 pt-3 pb-0" id="policyBarWrap">
        <div id="policyBar"
             class="d-flex align-items-center gap-2 px-3 py-2 rounded"
             style="font-size:.82rem;background:#e8f0fe;color:#1a56db;min-height:38px;transition:background .2s,color .2s">
          <i class="ti ti-info-circle flex-shrink-0" id="policyBarIcon" style="font-size:1rem"></i>
          <span id="policyBarText">Fill in all required fields to validate leave policy.</span>
          <span id="policyBarSpinner" class="spinner-border spinner-border-sm ms-auto d-none"
                style="width:.85rem;height:.85rem;border-width:2px"></span>
        </div>
      </div>

      <form method="post"
            action="<?= site_url('attendance_leaves/create') ?>"
            class="app-form"
            enctype="multipart/form-data"
            id="leaveForm">

        <input type="hidden" name="payload[mode]" id="leave_mode_hidden" value="full">

        <div class="modal-body pt-3">
          <div class="row g-3">

            <!-- ── Employee field ────────────────────────────────────────── -->
            <?php if ($showUserSelect): ?>
              <div class="col-md-6">
                <label class="form-label">Employee <span class="text-danger">*</span></label>
                <select name="payload[user_id]"
                        class="form-select form-select-sm"
                        required
                        id="leave_user_id">
                  <option value="">Select employee</option>
                  <?php foreach (($users ?? []) as $u): ?>
                    <?php
                      $uName = $u['fullname']
                          ?? trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
                    ?>
                    <option value="<?= (int)$u['id'] ?>"
                            <?= ((int)$u['id'] === $uid) ? 'selected' : '' ?>>
                      <?= html_escape(($u['emp_id'] ?? '') . ' – ' . $uName) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if ($canOwnTeam && !$canViewGlobal && !$isSuperAdmin): ?>
                  <small class="text-muted">Only your team members are listed.</small>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="col-md-6">
                <label class="form-label">Employee</label>
                <div class="form-control form-control-sm bg-light-primary d-flex align-items-center gap-2"
                     style="cursor:default;color:#495057">
                  <span><?= user_profile($currentUserName) ?> <?= html_escape($currentUserName) ?></span>
                </div>
                <input type="hidden" name="payload[user_id]" value="<?= (int)$uid ?>">
              </div>
            <?php endif; ?>

            <!-- ── Leave Type ─────────────────────────────────────────────── -->
            <div class="col-md-6">
              <label class="form-label">Leave Type <span class="text-danger">*</span></label>
              <select name="payload[leave_type_id]"
                      class="form-select form-select-sm"
                      required
                      id="leave_type_id">
                <option value="">Select leave type</option>
                <?php foreach (($leave_types ?? []) as $lt): ?>
                  <option value="<?= (int)$lt['id'] ?>">
                    <?= html_escape($lt['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- ── Leave Mode ─────────────────────────────────────────────── -->
            <div class="col-md-4">
              <label class="form-label d-block">Leave Mode</label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="mode_radio"
                       id="mode_full" value="full" checked>
                <label class="btn btn-outline-primary btn-sm" for="mode_full">
                  <i class="ti ti-calendar me-1"></i>Full
                </label>
                <input type="radio" class="btn-check" name="mode_radio"
                       id="mode_time" value="time">
                <label class="btn btn-outline-primary btn-sm" for="mode_time">
                  <i class="ti ti-clock me-1"></i>Short
                </label>
              </div>
            </div>

            <!-- ── Start Date ─────────────────────────────────────────────── -->
            <div class="col-md-4">
              <label class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date"
                     class="form-control form-control-sm"
                     name="payload[start_date]"
                     id="leave_start_date"
                     required
                     min="<?= date('Y-m-d') ?>">
            </div>

            <!-- ── End Date ───────────────────────────────────────────────── -->
            <div class="col-md-4">
              <label class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="date"
                     class="form-control form-control-sm"
                     name="payload[end_date]"
                     id="leave_end_date"
                     required
                     min="<?= date('Y-m-d') ?>">
            </div>

            <!-- ── Time fields (Hours mode only) ─────────────────────────── -->
            <div class="col-md-6 d-none" id="time_start_wrap">
              <label class="form-label">Start Time <span class="text-danger">*</span></label>
              <input type="time"
                     class="form-control form-control-sm"
                     name="payload[start_time]"
                     id="leave_start_time">
            </div>

            <div class="col-md-6 d-none" id="time_end_wrap">
              <label class="form-label">End Time <span class="text-danger">*</span></label>
              <input type="time"
                     class="form-control form-control-sm"
                     name="payload[end_time]"
                     id="leave_end_time">
            </div>

            <!-- ── Total Days ─────────────────────────────────────────────── -->
            <div class="col-md-4">
              <label class="form-label">Total Days</label>
              <input type="text"
                     class="form-control form-control-sm bg-light"
                     name="payload[total_days]"
                     id="leave_total_days"
                     readonly
                     placeholder="Auto-calculated">
              <small class="text-muted">Excludes weekends.</small>
            </div>

            <!-- ── Status (admins only) ───────────────────────────────────── -->
            <?php if ($showStatusSelect): ?>
              <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="payload[status]" class="form-select form-select-sm">
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                  <option value="cancelled">Cancelled</option>
                </select>
                <small class="text-muted">Admins can pre-approve.</small>
              </div>
            <?php else: ?>
              <input type="hidden" name="payload[status]" value="pending">
            <?php endif; ?>

            <!-- ── Attachment ─────────────────────────────────────────────── -->
            <div class="col-md-<?= $showStatusSelect ? '4' : '8' ?>">
              <label class="form-label">
                Attachment
                <span id="attachmentRequiredBadge" class="text-danger d-none">*</span>
              </label>
              <input type="file"
                     class="form-control form-control-sm"
                     name="attachment"
                     id="leave_attachment"
                     accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
              <small class="text-muted" id="attachmentHint">
                Optional unless required by leave type.
              </small>
            </div>

            <!-- ── Reason ─────────────────────────────────────────────────── -->
            <div class="col-12">
              <label class="form-label">Reason <span class="text-danger">*</span></label>
              <textarea name="payload[reason]"
                        class="form-control form-control-sm"
                        rows="3"
                        required
                        placeholder="Briefly describe the reason for your leave..."></textarea>
            </div>

            <!-- ── Leave History panel ────────────────────────────────────── -->
            <div class="col-12" id="leaveHistoryWrap" style="display:none">
              <div class="border rounded" style="border-color:#dee2e6 !important">

                <!-- Header row -->
                <div class="d-flex align-items-center justify-content-between px-3 py-2
                            border-bottom" style="background:#f8f9fa;border-radius:6px 6px 0 0">
                  <span class="fw-semibold" style="font-size:.82rem;color:#495057">
                    <i class="ti ti-history me-1 text-muted"></i>
                    Recent Leave History
                    <span id="leaveHistoryUserLabel" class="text-muted fw-normal"></span>
                  </span>
                  <span id="leaveHistoryBadge" class="badge bg-blue-lt text-blue"
                        style="font-size:.7rem"></span>
                </div>

                <!-- Body: loading / empty / table -->
                <div id="leaveHistoryBody" class="px-0 py-0">
                  <div id="leaveHistoryLoading"
                       class="text-center text-muted py-3"
                       style="font-size:.82rem;display:none">
                    <span class="spinner-border spinner-border-sm me-1"
                          style="width:.8rem;height:.8rem;border-width:2px"></span>
                    Loading history...
                  </div>
                  <div id="leaveHistoryEmpty"
                       class="text-center text-muted py-3"
                       style="font-size:.82rem;display:none">
                    <i class="ti ti-calendar-off d-block mb-1"
                       style="font-size:1.4rem;opacity:.35"></i>
                    No leave records found for this year.
                  </div>
                  <div id="leaveHistoryTableWrap" style="display:none">
                    <table class="table table-sm mb-0" style="font-size:.79rem">
                      <thead class="table-light">
                        <tr>
                          <th class="ps-3">Type</th>
                          <th>From</th>
                          <th>To</th>
                          <th class="text-center">Days</th>
                          <th class="text-center">Status</th>
                        </tr>
                      </thead>
                      <tbody id="leaveHistoryTbody"></tbody>
                    </table>
                  </div>
                </div>

              </div>
            </div>

          </div><!-- /row -->
        </div><!-- /modal-body -->

        <div class="modal-footer py-2">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary btn-sm" id="leaveSubmitBtn">
            <i class="ti ti-send me-1"></i>
            <?= $showUserSelect ? 'Submit Leave' : 'Apply Leave' ?>
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  /* ─────────────────────────────────────────────────────────────────────────
   | DOM REFS
   * ───────────────────────────────────────────────────────────────────────── */
  const startDateEl    = document.getElementById('leave_start_date');
  const endDateEl      = document.getElementById('leave_end_date');
  const startTimeWrap  = document.getElementById('time_start_wrap');
  const endTimeWrap    = document.getElementById('time_end_wrap');
  const startTimeEl    = document.getElementById('leave_start_time');
  const endTimeEl      = document.getElementById('leave_end_time');
  const totalDaysEl    = document.getElementById('leave_total_days');
  const leaveTypeEl    = document.getElementById('leave_type_id');
  const userSelectEl   = document.getElementById('leave_user_id');   // null in apply-only mode
  const submitBtn      = document.getElementById('leaveSubmitBtn');
  const modeHidden     = document.getElementById('leave_mode_hidden');
  const attachmentEl   = document.getElementById('leave_attachment');
  const attachBadge    = document.getElementById('attachmentRequiredBadge');
  const attachHint     = document.getElementById('attachmentHint');

  // Policy bar refs
  const policyBar      = document.getElementById('policyBar');
  const policyBarIcon  = document.getElementById('policyBarIcon');
  const policyBarText  = document.getElementById('policyBarText');
  const policyBarSpinner = document.getElementById('policyBarSpinner');

  // Leave history refs
  const historyWrap    = document.getElementById('leaveHistoryWrap');
  const historyLoading = document.getElementById('leaveHistoryLoading');
  const historyEmpty   = document.getElementById('leaveHistoryEmpty');
  const historyTable   = document.getElementById('leaveHistoryTableWrap');
  const historyTbody   = document.getElementById('leaveHistoryTbody');
  const historyBadge   = document.getElementById('leaveHistoryBadge');
  const historyLabel   = document.getElementById('leaveHistoryUserLabel');

  // Fixed self uid from PHP (apply-only mode)
  const selfUid = <?= (int)$uid ?>;

  /* ─────────────────────────────────────────────────────────────────────────
   | POLICY BAR  (replaces Bootstrap alert boxes)
   |
   | States: info | loading | success | warning | danger | field-error
   * ───────────────────────────────────────────────────────────────────────── */
  const barTheme = {
    info        : { bg: '#e8f0fe', color: '#1a56db', icon: 'ti-info-circle' },
    loading     : { bg: '#f0f4ff', color: '#3b5bdb', icon: 'ti-loader'      },
    success     : { bg: '#d3f9d8', color: '#2b7a36', icon: 'ti-circle-check'},
    warning     : { bg: '#fff3cd', color: '#8a6d00', icon: 'ti-alert-triangle'},
    danger      : { bg: '#ffe3e3', color: '#c92a2a', icon: 'ti-alert-circle' },
    'field-error': { bg: '#fff0f0', color: '#c92a2a', icon: 'ti-alert-circle'},
  };

  function setPolicyBar(state, message, showSpinner) {
    const t = barTheme[state] || barTheme.info;
    policyBar.style.background = t.bg;
    policyBar.style.color      = t.color;
    policyBarIcon.className    = 'ti ' + t.icon + ' flex-shrink-0';
    policyBarIcon.style.color  = t.color;
    policyBarText.innerHTML    = message;
    policyBarSpinner.classList.toggle('d-none', !showSpinner);
  }

  // Kept as global so the inline onclick in PHP templates can call it
  window.hidePolicyAlert    = function () { /* no-op — bar is always visible */ };
  window.hideImmediateAlert = function () { /* no-op */ };

  /* ─────────────────────────────────────────────────────────────────────────
   | HELPERS
   * ───────────────────────────────────────────────────────────────────────── */
  function getMode() {
    const el = document.querySelector('input[name="mode_radio"]:checked');
    return el ? el.value : 'full';
  }

  function getSelectedUserId() {
    if (userSelectEl) return parseInt(userSelectEl.value || '0', 10);
    return selfUid;
  }

  function setSubmitEnabled(enabled) {
    submitBtn.disabled = !enabled;
    submitBtn.innerHTML = enabled
      ? '<i class="ti ti-send me-1"></i> <?= $showUserSelect ? 'Submit Leave' : 'Apply Leave' ?>'
      : '<i class="ti ti-loader me-1"></i> Validating...';
  }

  function setAttachmentRequired(required) {
    if (!attachmentEl) return;
    if (required) {
      attachmentEl.setAttribute('required', 'required');
      attachBadge.classList.remove('d-none');
      attachHint.textContent = 'Required for this leave type.';
    } else {
      attachmentEl.removeAttribute('required');
      attachBadge.classList.add('d-none');
      attachHint.textContent = 'Optional unless required by leave type.';
    }
  }

  function calcBusinessDays(s, e) {
    if (!s || !e) return 0;
    const start = new Date(s), end = new Date(e);
    if (end < start) return 0;
    let count = 0, cur = new Date(start);
    while (cur <= end) {
      const d = cur.getDay();
      if (d !== 0 && d !== 6) count++;
      cur.setDate(cur.getDate() + 1);
    }
    return count;
  }

  function calcTotal() {
    const mode = getMode();
    modeHidden.value = mode;

    if (mode === 'time') {
      if (!startDateEl.value || !startTimeEl.value || !endTimeEl.value) {
        totalDaysEl.value = '';
        return;
      }
      endDateEl.value = startDateEl.value;

      if (endTimeEl.value <= startTimeEl.value) {
        totalDaysEl.value = '';
        setPolicyBar('field-error', 'End time must be after start time.');
        setSubmitEnabled(false);
        return;
      }

      const st    = new Date('2000-01-01T' + startTimeEl.value);
      const et    = new Date('2000-01-01T' + endTimeEl.value);
      const hours = (et - st) / 1000 / 60 / 60;
      totalDaysEl.value = (hours / 8).toFixed(2);
      return;
    }

    // Full day
    if (!startDateEl.value || !endDateEl.value) {
      totalDaysEl.value = '';
      return;
    }
    if (endDateEl.value < startDateEl.value) {
      totalDaysEl.value = '';
      setPolicyBar('field-error', 'End date cannot be before start date.');
      setSubmitEnabled(false);
      return;
    }

    const days = calcBusinessDays(startDateEl.value, endDateEl.value);
    totalDaysEl.value = days > 0 ? days.toFixed(2) : '';
  }

  function toggleTimeFields() {
    const mode = getMode();
    modeHidden.value = mode;

    if (mode === 'time') {
      startTimeWrap.classList.remove('d-none');
      endTimeWrap.classList.remove('d-none');
      startTimeEl.setAttribute('required', 'required');
      endTimeEl.setAttribute('required', 'required');
      if (startDateEl.value) endDateEl.value = startDateEl.value;
      endDateEl.setAttribute('readonly', 'readonly');
      endDateEl.style.cssText = 'pointer-events:none;background:#f8f9fa';
      setPolicyBar('info', 'Hours mode: select the same day, end time must be after start time.');
    } else {
      startTimeWrap.classList.add('d-none');
      endTimeWrap.classList.add('d-none');
      startTimeEl.removeAttribute('required');
      endTimeEl.removeAttribute('required');
      endDateEl.removeAttribute('readonly');
      endDateEl.style.cssText = '';
    }

    calcTotal();
    checkPolicyDebounced();
  }

  /* ─────────────────────────────────────────────────────────────────────────
   | IMMEDIATE FIELD VALIDATION
   * ───────────────────────────────────────────────────────────────────────── */
  function validateImmediate() {
    const today = new Date().toISOString().split('T')[0];

    if (startDateEl.value && startDateEl.value < today) {
      setPolicyBar('field-error', 'Start date cannot be in the past.');
      return false;
    }
    if (endDateEl.value && endDateEl.value < today) {
      setPolicyBar('field-error', 'End date cannot be in the past.');
      return false;
    }
    if (startDateEl.value && endDateEl.value && endDateEl.value < startDateEl.value) {
      setPolicyBar('field-error', 'End date cannot be before start date.');
      return false;
    }
    return true;
  }

  /* ─────────────────────────────────────────────────────────────────────────
   | POLICY CHECK  (debounced AJAX)
   * ───────────────────────────────────────────────────────────────────────── */
  let policyTimer    = null;
  let lastPayloadKey = '';
  let inFlight       = false;

  function checkPolicyDebounced() {
    if (!validateImmediate()) {
      setSubmitEnabled(false);
      return;
    }
    if (policyTimer) clearTimeout(policyTimer);
    policyTimer = setTimeout(checkPolicy, 380);
  }

  async function checkPolicy() {

    // ── Guard: required fields ──────────────────────────────────────────────
    if (!leaveTypeEl || !leaveTypeEl.value) {
      setPolicyBar('info', 'Select a leave type to validate policy.');
      setSubmitEnabled(false);
      return;
    }
    if (userSelectEl && !userSelectEl.value) {
      setPolicyBar('info', 'Select an employee to validate leave policy.');
      setSubmitEnabled(false);
      return;
    }
    if (!startDateEl.value || !endDateEl.value) {
      setPolicyBar('info', 'Select start and end dates to validate leave policy.');
      setSubmitEnabled(false);
      return;
    }

    const mode = getMode();

    if (mode === 'time') {
      if (!startTimeEl.value || !endTimeEl.value) {
        setPolicyBar('info', 'Select start and end times to validate leave policy.');
        setSubmitEnabled(false);
        return;
      }
      if (endTimeEl.value <= startTimeEl.value) {
        setPolicyBar('field-error', 'End time must be greater than start time.');
        setSubmitEnabled(false);
        return;
      }
    }

    const requestedQty = totalDaysEl.value ? parseFloat(totalDaysEl.value) : 0;
    if (!requestedQty || requestedQty <= 0) {
      setPolicyBar('field-error', 'Leave duration is invalid — check your dates or times.');
      setSubmitEnabled(false);
      return;
    }

    // ── Dedupe ──────────────────────────────────────────────────────────────
    const uid       = getSelectedUserId();
    const payloadKey = [
      leaveTypeEl.value,
      startDateEl.value,
      endDateEl.value,
      mode,
      requestedQty,
      startTimeEl.value  || '',
      endTimeEl.value    || '',
      uid,
    ].join('|');

    if (payloadKey === lastPayloadKey && !inFlight) return;
    lastPayloadKey = payloadKey;
    if (inFlight) return;
    inFlight = true;

    // ── Loading state ───────────────────────────────────────────────────────
    setPolicyBar('loading', 'Validating leave policy…', true);
    setSubmitEnabled(false);

    // ── Build FormData ──────────────────────────────────────────────────────
    const fd = new FormData();
    fd.append('leave_type_id', leaveTypeEl.value);
    fd.append('from_date',     startDateEl.value);
    fd.append('to_date',       endDateEl.value);
    fd.append('requested_qty', requestedQty);
    fd.append('mode',          mode);
    fd.append('user_id',       uid);

    if (mode === 'time') {
      fd.append('start_time', startTimeEl.value);
      fd.append('end_time',   endTimeEl.value);
    }

    // ── Fetch ───────────────────────────────────────────────────────────────
    try {
      const res  = await fetch('<?= site_url('attendance_leaves/ajax_check_leave_policy') ?>', {
        method : 'POST',
        body   : fd,
      });
      const data = await res.json();

      setAttachmentRequired(!!data.requires_attachment);

      if (data.blocked) {
        const errs = (data.errors || ['Leave request blocked by policy.']).join(' · ');
        setPolicyBar('danger', '<strong>Blocked:</strong> ' + errs);
        setSubmitEnabled(false);
        inFlight = false;
        return;
      }

      if (data.warnings && data.warnings.length) {
        const warns = data.warnings.join(' · ');
        setPolicyBar('warning', '<strong>Note:</strong> ' + warns);
        setSubmitEnabled(true);
        inFlight = false;
        return;
      }

      <?php if ($isSuperAdmin): ?>
      setPolicyBar('success', '<strong>✓</strong> Policy bypassed — you are a superadmin.');
      <?php else: ?>
      setPolicyBar('success', '<strong>✓ Policy OK.</strong> You can submit this leave request.');
      <?php endif; ?>
      setSubmitEnabled(true);

    } catch (err) {
      setPolicyBar('warning', 'Could not reach policy server — you may proceed, but check your connection.');
      setSubmitEnabled(true);
    }

    inFlight = false;
  }

  /* ─────────────────────────────────────────────────────────────────────────
   | LEAVE HISTORY (fetched per-user before applying)
   * ───────────────────────────────────────────────────────────────────────── */
  let historyLastUid = null;

  const statusMeta = {
    approved  : { cls: 'success',   label: 'Approved'   },
    pending   : { cls: 'warning',   label: 'Pending'    },
    rejected  : { cls: 'danger',    label: 'Rejected'   },
    cancelled : { cls: 'secondary', label: 'Cancelled'  },
  };

  function fmtDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  async function fetchLeaveHistory(userId) {
    if (!userId || userId <= 0) {
      historyWrap.style.display = 'none';
      return;
    }

    // Skip re-fetch if same user
    if (userId === historyLastUid) {
      historyWrap.style.display = '';
      return;
    }
    historyLastUid = userId;

    // Show panel, loading state
    historyWrap.style.display    = '';
    historyLoading.style.display = '';
    historyEmpty.style.display   = 'none';
    historyTable.style.display   = 'none';
    historyBadge.textContent     = '';
    historyTbody.innerHTML       = '';

    // Update label
    if (userSelectEl) {
      const opt = userSelectEl.options[userSelectEl.selectedIndex];
      historyLabel.textContent = opt && opt.text ? ' — ' + opt.text : '';
    } else {
      historyLabel.textContent = '';
    }

    try {
      const res  = await fetch(
        '<?= site_url('attendance_leaves/ajax_user_leave_history') ?>?user_id=' + userId,
        { method: 'GET' }
      );
      const data = await res.json();

      historyLoading.style.display = 'none';

      if (!data.success || !data.leaves || data.leaves.length === 0) {
        historyEmpty.style.display = '';
        historyBadge.textContent   = '0 records';
        return;
      }

      historyBadge.textContent = data.leaves.length + ' record' + (data.leaves.length !== 1 ? 's' : '');

      data.leaves.forEach(function (lv) {
        const st  = (lv.status || 'pending').toLowerCase();
        const sm  = statusMeta[st] || { cls: 'secondary', label: st };
        const isSameDay = lv.start_date === lv.end_date;

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="ps-3 py-1">
            <span class="fw-semibold">${escHtml(lv.leave_type_name || '—')}</span>
          </td>
          <td class="py-1">${fmtDate(lv.start_date)}</td>
          <td class="py-1">${isSameDay ? '<span class="text-muted">—</span>' : fmtDate(lv.end_date)}</td>
          <td class="text-center py-1">
            <span class="fw-semibold">${parseFloat(lv.total_days || 0).toFixed(1)}</span>
          </td>
          <td class="text-center py-1">
            <span class="badge bg-${sm.cls}-lt text-${sm.cls} fw-semibold"
                  style="font-size:.68rem;letter-spacing:.03em">
              ${sm.label}
            </span>
          </td>`;
        historyTbody.appendChild(tr);
      });

      historyTable.style.display = '';

    } catch (err) {
      historyLoading.style.display = 'none';
      historyEmpty.style.display   = '';
      historyBadge.textContent     = 'Failed to load';
    }
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ─────────────────────────────────────────────────────────────────────────
   | EVENTS
   * ───────────────────────────────────────────────────────────────────────── */
  // Mode toggle
  document.querySelectorAll('input[name="mode_radio"]').forEach(function (r) {
    r.addEventListener('change', toggleTimeFields);
  });

  // Date / time field changes
  [startDateEl, endDateEl, startTimeEl, endTimeEl].forEach(function (el) {
    if (!el) return;
    el.addEventListener('change', function () {
      calcTotal();
      checkPolicyDebounced();
    });
  });

  // Start date: enforce same-day in time mode
  startDateEl.addEventListener('change', function () {
    if (getMode() === 'time') endDateEl.value = startDateEl.value;
    calcTotal();
    checkPolicyDebounced();
  });

  // Leave type change
  if (leaveTypeEl) {
    leaveTypeEl.addEventListener('change', function () {
      calcTotal();
      checkPolicyDebounced();
    });
  }

  // User select change (admin mode) — re-fetch history + re-run policy
  if (userSelectEl) {
    userSelectEl.addEventListener('change', function () {
      const uid = parseInt(this.value || '0', 10);
      historyLastUid = null;   // force re-fetch
      fetchLeaveHistory(uid);
      calcTotal();
      checkPolicyDebounced();
    });
  }

  // Form submit guard
  document.getElementById('leaveForm').addEventListener('submit', function (e) {
    if (submitBtn.disabled) {
      e.preventDefault();
      setPolicyBar('warning', 'Please wait for policy validation to complete.');
      return false;
    }
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ti ti-loader me-1"></i> Submitting…';
  });

  // Modal open: load history for the resolved user
  const modalEl = document.getElementById('addLeaveModal');
  if (modalEl) {
    modalEl.addEventListener('shown.bs.modal', function () {
      const uid = getSelectedUserId();
      fetchLeaveHistory(uid);
      // Reset dedupe so policy re-evaluates fresh each open
      lastPayloadKey = '';
      toggleTimeFields();
      calcTotal();
      checkPolicyDebounced();
    });

    // Reset form state on close
    modalEl.addEventListener('hidden.bs.modal', function () {
      historyLastUid = null;
      historyWrap.style.display = 'none';
      lastPayloadKey = '';
      inFlight = false;
      setSubmitEnabled(true);
      setPolicyBar('info', 'Fill in all required fields to validate leave policy.');
      setAttachmentRequired(false);
    });
  }

  /* ─────────────────────────────────────────────────────────────────────────
   | INIT
   * ───────────────────────────────────────────────────────────────────────── */
  toggleTimeFields();
  calcTotal();
  checkPolicyDebounced();

})();
</script>