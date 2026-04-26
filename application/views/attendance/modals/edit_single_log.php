<!-- ── Edit Attendance Log Modal ─────────────────────────── -->
<div class="modal fade" id="editLogModal" tabindex="-1"
     aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-top">
    <div class="modal-content app-form">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-edit me-2"></i>
          Edit Attendance Log
          <span class="text-white-50 small ms-2" id="elmLogId"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white"
                data-bs-dismiss="modal"></button>
      </div>

      <!-- Loading state -->
      <div id="elmLoading" class="modal-body text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted small">Loading record…</p>
      </div>

      <!-- Form (hidden until loaded) -->
      <div id="elmFormWrap" style="display:none">
        <div class="modal-body">

        <div class="d-flex align-items-center gap-3 p-3 mb-3 rounded-3 bg-light-primary border">
          <div id="elmUserAvatar"></div>
          <div>
            <div class="fw-semibold small" id="elmUserFullName"></div>
            <div class="text-muted small capital" id="elmUserMeta"></div>
          </div>
          <div class="ms-auto text-end">
            <div class="text-muted" style="font-size:10px;letter-spacing:.04em">Log ID</div>
            <div class="fw-semibold small" id="elmLogIdStrip"></div>
          </div>
        </div>

          <!-- ── Editable fields ──────────────────────────── -->
          <div class="row g-3">

            <div class="col-md-4">
              <label class="form-label fw-semibold small">
                Date &amp; Time <span class="text-danger">*</span>
              </label>
              <input type="datetime-local" id="elmDatetime"
                     class="form-control form-control-sm" step="1">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">
                Status <span class="text-danger">*</span>
              </label>
              <select id="elmStatus" class="form-select form-select-sm">
                <option value="check_in">Check In</option>
                <option value="check_out">Check Out</option>
                <option value="overtime_in">Overtime In</option>
                <option value="overtime_out">Overtime Out</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Log Type</label>
              <select id="elmLogType" class="form-select form-select-sm">
                <option value="AUTO">AUTO</option>
                <option value="MANUAL">MANUAL</option>
                <option value="CORRECTION">CORRECTION</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Device ID</label>
              <input type="text" id="elmDeviceId"
                     class="form-control form-control-sm"
                     placeholder="Optional">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">IP Address</label>
              <input type="text" id="elmIpAddress"
                     class="form-control form-control-sm"
                     placeholder="e.g. 192.168.1.1">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Approval Status</label>
              <select id="elmApprovalStatus" class="form-select form-select-sm">
                <option value="APPROVED">Approved</option>
                <option value="PENDING">Pending</option>
                <option value="REJECTED">Rejected</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Approved By</label>
              <select id="elmApprovedBy" class="form-select form-select-sm">
                <option value="">— None —</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold small">Approved At</label>
              <input type="datetime-local" id="elmApprovedAt"
                     class="form-control form-control-sm" step="1">
            </div>

          </div><!-- /row -->
        </div><!-- /modal-body -->

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm"
                  data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm px-4"
                  id="elmSaveBtn" onclick="saveLogEdit()">
            <i class="ti ti-device-floppy me-1"></i> Save Changes
          </button>
        </div>
      </div><!-- /elmFormWrap -->

    </div>
  </div>
</div>

<script>
var baseUrl = '<?= base_url() ?>';
</script>

<script>
let _elmLogId = 0;

function openLogEdit(logId) {
    _elmLogId = logId;

    document.getElementById('elmLoading').style.display  = '';
    document.getElementById('elmFormWrap').style.display = 'none';
    document.getElementById('elmLogId').textContent      = '#' + logId;
    document.getElementById('elmLogIdStrip').textContent = '#' + logId;
    document.getElementById('elmSaveBtn').disabled       = false;

    const modal = new bootstrap.Modal(document.getElementById('editLogModal'));
    modal.show();

    fetch(baseUrl + 'attendance/get_log/' + logId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (!data || data.error) {
            document.getElementById('elmLoading').innerHTML =
                '<p class="text-danger text-center py-4 small">' +
                '<i class="ti ti-alert-circle d-block mb-2 fs-4"></i>' +
                (data.error || 'Record not found.') + '</p>';
            return;
        }
        populateLogModal(data);
    })
    .catch(err => {
        document.getElementById('elmLoading').innerHTML =
            '<p class="text-danger text-center py-4 small">' +
            '<i class="ti ti-alert-circle d-block mb-2 fs-4"></i>' +
            'Failed to load record: ' + err.message + '</p>';
    });
}

function populateLogModal(d) {
    const toLocal = v => v ? v.trim().replace(' ', 'T').substring(0, 19) : '';


// Build avatar manually so we control what goes where
const imgFile  = d.user_avatar || '';
const name     = d.user_full_name || 'Unknown';
const initials = name.split(' ').map(w => w[0] || '').join('').substring(0, 2).toUpperCase() || 'U';
const fb       = baseUrl + 'assets/images/default-avatar.png';

document.getElementById('elmUserAvatar').innerHTML =
    imgFile
    ? '<img src="' + baseUrl + 'uploads/users/profile/' + imgFile + '" '
      + 'width="40" height="40" '
      + 'style="border-radius:50%;object-fit:cover;flex-shrink:0;" '
      + 'onerror="this.onerror=null;this.src=\'' + fb + '\'">'
    : '<span style="width:40px;height:40px;border-radius:50%;background:#fff;color:#185FA5;'
      + 'display:inline-flex;align-items:center;justify-content:center;'
      + 'font-weight:700;font-size:13px;flex-shrink:0;">' + initials + '</span>';

document.getElementById('elmUserFullName').textContent = name;
document.getElementById('elmUserMeta').innerHTML =
    (d.user_empid_html || '') +
    (d.user_role ? ' <span class="mx-1 text-muted">·</span> ' + d.user_role : '');
            
    /* ── Editable fields ────────────────────────────────── */
    document.getElementById('elmDatetime').value     = toLocal(d.datetime);
    document.getElementById('elmApprovedAt').value   = toLocal(d.approved_at);
    document.getElementById('elmDeviceId').value     = d.device_id   ?? '';
    document.getElementById('elmIpAddress').value    = d.ip_address  ?? '';
    // Populate approved_by dropdown with managers
    const apByEl = document.getElementById('elmApprovedBy');
    apByEl.innerHTML = '<option value="">— None —</option>';
    (d.managers || []).forEach(function(m) {
        const opt  = document.createElement('option');
        opt.value  = m.id;
        opt.textContent = (m.emp_id ? '[' + m.emp_id + '] ' : '')
                        + m.firstname + ' ' + m.lastname;
        if (String(m.id) === String(d.approved_by)) {
            opt.selected = true;
        }
        apByEl.appendChild(opt);
    });

    setSelectVal('elmStatus',         d.status          ?? 'check_in');
    setSelectVal('elmLogType',        d.log_type        ?? 'AUTO');
    setSelectVal('elmApprovalStatus', d.approval_status ?? 'APPROVED');

    document.getElementById('elmLoading').style.display  = 'none';
    document.getElementById('elmFormWrap').style.display = '';
}

function setSelectVal(elId, val) {
    const el = document.getElementById(elId);
    for (let i = 0; i < el.options.length; i++) {
        if (el.options[i].value === String(val)) {
            el.selectedIndex = i;
            return;
        }
    }
}

function saveLogEdit() {
    const btn = document.getElementById('elmSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

    const datetimeRaw   = document.getElementById('elmDatetime').value;
    const approvedAtRaw = document.getElementById('elmApprovedAt').value;

    const payload = new URLSearchParams({
        datetime:        datetimeRaw.replace('T', ' '),
        status:          document.getElementById('elmStatus').value,
        log_type:        document.getElementById('elmLogType').value,
        device_id:       document.getElementById('elmDeviceId').value,
        ip_address:      document.getElementById('elmIpAddress').value,
        approval_status: document.getElementById('elmApprovalStatus').value,
        approved_by:     document.getElementById('elmApprovedBy').value,
        approved_at:     approvedAtRaw ? approvedAtRaw.replace('T', ' ') : '',
    });

    fetch(baseUrl + 'attendance/update_log/' + _elmLogId, {
        method:  'POST',
        headers: {
            'Content-Type':     'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: payload.toString(),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(
                document.getElementById('editLogModal')
            ).hide();
            setTimeout(() => location.reload(), 400);
        } else {
            alert(res.message || 'Save failed. Please check the values and try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Save Changes';
        }
    })
    .catch(() => {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i> Save Changes';
    });
}
</script>