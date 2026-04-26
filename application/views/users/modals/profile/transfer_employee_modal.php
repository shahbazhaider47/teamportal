<!-- Transfer Employee Modal -->
<div class="modal fade" id="transferEmployeeModal" tabindex="-1"
     aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-top">
    <div class="modal-content app-form">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-arrows-exchange me-2"></i>
          Transfer Employee — <span id="trfEmpName" class="text-white-50"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Loading state -->
      <div id="trfLoading" class="modal-body text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-3 text-muted small">Loading employee data…</p>
      </div>

      <!-- Main form (hidden until loaded) -->
      <div id="trfFormWrap" style="display:none">
        <div class="modal-body">

          <!-- Current snapshot strip -->
          <div class="alert alert-light border small mb-3 py-2" id="trfSnapshot">
            <div class="row g-2" id="trfSnapshotRow"></div>
          </div>

          <div class="row g-3">

            <!-- LEFT: destination fields -->
            <div class="col-lg-7">
              <div class="row g-3">

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">
                    Destination Office <span class="text-danger">*</span>
                  </label>
                  <select id="trfOffice" name="to_office_id" class="form-select form-select-sm">
                    <option value="">— Same as current —</option>
                  </select>
                  <div class="form-text" id="trfOfficeHint"></div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Work Location (override)</label>
                  <input type="text" id="trfWorkLocation" name="work_location"
                         class="form-control form-control-sm"
                         placeholder="e.g. Floor 2, Block B">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Department</label>
                  <select id="trfDept" name="to_department_id" class="form-select form-select-sm">
                    <option value="">— Same as current —</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Team</label>
                  <select id="trfTeam" name="to_team_id" class="form-select form-select-sm">
                    <option value="">— Same as current —</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">New Position / Title</label>
                  <select id="trfTitle" name="to_title_id" class="form-select form-select-sm">
                    <option value="">— No change —</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Reports To (Manager)</label>
                  <select id="trfManager" name="to_manager_id" class="form-select form-select-sm">
                    <option value="">— No change —</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Team Lead</label>
                  <select id="trfLead" name="to_teamlead_id" class="form-select form-select-sm">
                    <option value="">— No change —</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">New Salary (leave blank = no change)</label>
                  <input type="number" id="trfSalary" name="to_salary"
                         class="form-control form-control-sm" min="0" step="0.01"
                         placeholder="0.00">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">
                    Effective Date <span class="text-danger">*</span>
                  </label>
                  <input type="date" id="trfDate" name="effective_date"
                         class="form-control form-control-sm"
                         value="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small">Reason</label>
                  <input type="text" id="trfReason" name="reason"
                         class="form-control form-control-sm"
                         placeholder="e.g. Office relocation, reorganisation">
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold small">Internal Remarks</label>
                  <textarea id="trfRemarks" name="remarks"
                            class="form-control form-control-sm" rows="2"
                            placeholder="Optional notes visible to HR only"></textarea>
                </div>

              </div>
            </div>

            <!-- RIGHT: transfer history -->
            <div class="col-lg-5">
              <div class="fw-semibold small text-muted mb-2">
                <i class="ti ti-history me-1"></i>Transfer History
              </div>
              <div id="trfHistory" class="border rounded p-2"
                   style="max-height:420px;overflow-y:auto;font-size:12px">
                <p class="text-muted text-center py-3 mb-0">No history yet.</p>
              </div>
            </div>

          </div><!-- /row -->
        </div><!-- /modal-body -->

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm"
                  data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm px-4"
                  id="trfSubmitBtn" onclick="submitTransfer()">
            <i class="ti ti-arrows-exchange me-1"></i>Execute Transfer
          </button>
        </div>
      </div>

    </div><!-- /.modal-content -->
  </div>
</div>


<script>
let _trfUserId = 0;
let _trfData   = {};

function transferEmployee(userId, name, currentTitle) {
    _trfUserId = userId;
    document.getElementById('trfEmpName').textContent = name;

    // Reset form
    document.getElementById('trfLoading').style.display  = '';
    document.getElementById('trfFormWrap').style.display = 'none';
    document.getElementById('trfSnapshotRow').innerHTML  = '';
    document.getElementById('trfHistory').innerHTML      =
        '<p class="text-muted text-center py-3 mb-0">Loading…</p>';

    // Open modal
    const modal = new bootstrap.Modal(document.getElementById('transferEmployeeModal'));
    modal.show();

    // Fetch data
    fetch(baseUrl + 'users/transfer/' + userId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        _trfData = data;
        populateTransferModal(data);
    })
    .catch(() => {
        document.getElementById('trfLoading').innerHTML =
            '<p class="text-danger text-center py-4">Failed to load data. Please try again.</p>';
    });
}

function populateTransferModal(data) {
    const snap = data.snapshot || {};

    // Snapshot strip
    const snapFields = [
        ['Office',     snap.office_name     || '—'],
        ['City',       snap.office_city     || '—'],
        ['Dept',       snap.department_name || '—'],
        ['Team',       snap.team_name       || '—'],
        ['Position',   snap.position_title  || '—'],
        ['Salary',     snap.current_salary  ? Number(snap.current_salary).toLocaleString() : '—'],
    ];
    document.getElementById('trfSnapshotRow').innerHTML = snapFields.map(([k,v]) =>
        `<div class="col-6 col-md-4 col-lg-2">
           <div class="text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.04em">${k}</div>
           <div class="fw-semibold small">${esc(v)}</div>
         </div>`
    ).join('');

    // Populate selects
    populateSelect('trfOffice',   data.offices,     'id', 'office_name', snap.office_id,
        o => `[${o.office_code}] ${o.office_name}${o.city ? ', '+o.city : ''}${o.country ? ' · '+o.country : ''}`);

    populateSelect('trfDept',     data.departments, 'id', 'name',        snap.emp_department);
    populateSelect('trfTeam',     data.teams,       'id', 'name',        snap.emp_team,
        t => `${t.name}${t.department_name ? ' ('+t.department_name+')' : ''}`);
    populateSelect('trfTitle',    data.positions,   'id', 'title',       snap.emp_title,
        p => `[${p.code}] ${p.title}`);
    populateSelect('trfManager',  data.managers,    'id', null,          snap.emp_manager,
        u => `${u.firstname} ${u.lastname}${u.emp_id ? ' · '+u.emp_id : ''}`);
    populateSelect('trfLead',     data.team_leads,  'id', null,          snap.emp_teamlead,
        u => `${u.firstname} ${u.lastname}${u.emp_id ? ' · '+u.emp_id : ''}`);

    // Office hint (timezone / currency)
    document.getElementById('trfOffice').addEventListener('change', function() {
        const sel = (data.offices || []).find(o => o.id == this.value);
        document.getElementById('trfOfficeHint').textContent = sel
            ? `${sel.timezone} · ${sel.currency}`
            : '';
        // Filter teams by dept if dept is also selected — optional enhancement
    });

    // Dept change → filter teams
    document.getElementById('trfDept').addEventListener('change', function() {
        const deptId = parseInt(this.value) || 0;
        const filtered = deptId > 0
            ? (data.teams || []).filter(t => t.department_id == deptId)
            : (data.teams || []);
        populateSelect('trfTeam', filtered, 'id', 'name', snap.emp_team,
            t => `${t.name}${t.department_name ? ' ('+t.department_name+')' : ''}`);
    });

    // Render history
    renderTransferHistory(data.history || []);

    document.getElementById('trfLoading').style.display  = 'none';
    document.getElementById('trfFormWrap').style.display = '';
}

function populateSelect(elId, items, valKey, labelKey, currentVal, labelFn) {
    const el = document.getElementById(elId);
    const existing = el.options[0].outerHTML; // keep "no change" option
    el.innerHTML = existing;
    (items || []).forEach(item => {
        const opt = document.createElement('option');
        opt.value = item[valKey];
        opt.textContent = labelFn ? labelFn(item) : item[labelKey];
        if (currentVal && String(item[valKey]) === String(currentVal)) {
            opt.selected = true;
        }
        el.appendChild(opt);
    });
}

function renderTransferHistory(history) {
    const el = document.getElementById('trfHistory');
    if (!history.length) {
        el.innerHTML = '<p class="text-muted text-center py-3 mb-0">No transfer history.</p>';
        return;
    }

    const typeColors = {
        transfer:          'bg-primary',
        location_change:   'bg-info',
        department_change: 'bg-warning',
        team_change:       'bg-success',
    };

    el.innerHTML = history.map(h => {
        const badgeCls = typeColors[h.movement_type] || 'bg-secondary';
        const lines = [];
        if (h.from_department !== h.to_department && h.to_department)
            lines.push(`Dept: ${h.from_department||'—'} → <strong>${h.to_department}</strong>`);
        if (h.from_team !== h.to_team && h.to_team)
            lines.push(`Team: ${h.from_team||'—'} → <strong>${h.to_team}</strong>`);
        if (h.from_title !== h.to_title && h.to_title)
            lines.push(`Title: ${h.from_title||'—'} → <strong>${h.to_title}</strong>`);
        if (h.from_salary !== h.to_salary && h.to_salary)
            lines.push(`Salary: ${Number(h.from_salary||0).toLocaleString()} → <strong>${Number(h.to_salary).toLocaleString()}</strong>`);
        if (h.reason)
            lines.push(`<em class="text-muted">Reason: ${esc(h.reason)}</em>`);

        const doneBy = (h.done_by_first||'') + ' ' + (h.done_by_last||'');

        return `<div class="border-bottom pb-2 mb-2">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge ${badgeCls} text-white" style="font-size:9px">
              ${h.movement_type.replace('_',' ')}
            </span>
            <span class="text-muted">${formatDate(h.effective_date)}</span>
            ${doneBy.trim() ? `<span class="text-muted ms-auto" style="font-size:10px">by ${esc(doneBy.trim())}</span>` : ''}
          </div>
          ${lines.length ? '<div class="small">'+lines.join('<br>')+'</div>' : '<div class="text-muted small">General transfer recorded.</div>'}
        </div>`;
    }).join('');
}

function submitTransfer() {
    const btn = document.getElementById('trfSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing…';

    const fields = ['to_office_id','work_location','to_department_id','to_team_id',
                    'to_title_id','to_manager_id','to_teamlead_id','to_salary',
                    'effective_date','reason','remarks'];

    const elIds = {
        to_office_id:     'trfOffice',
        work_location:    'trfWorkLocation',
        to_department_id: 'trfDept',
        to_team_id:       'trfTeam',
        to_title_id:      'trfTitle',
        to_manager_id:    'trfManager',
        to_teamlead_id:   'trfLead',
        to_salary:        'trfSalary',
        effective_date:   'trfDate',
        reason:           'trfReason',
        remarks:          'trfRemarks',
    };

    const body = new URLSearchParams();
    fields.forEach(f => {
        const val = document.getElementById(elIds[f])?.value || '';
        body.append(f, val);
    });

    fetch(baseUrl + 'users/transfer/' + _trfUserId, {
        method:  'POST',
        headers: {
            'Content-Type':     'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token':     document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: body.toString(),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            bootstrap.Modal.getInstance(
                document.getElementById('transferEmployeeModal')
            ).hide();
            // Show the CI alert system alert or reload
            if (typeof showAlert === 'function') {
                showAlert('success', res.message);
            }
            setTimeout(() => location.reload(), 800);
        } else {
            alert(res.message || 'Transfer failed.');
        }
    })
    .catch(() => alert('Network error. Please try again.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-arrows-exchange me-1"></i>Execute Transfer';
    });
}

/* helpers */
function esc(s) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s||'')));
    return d.innerHTML;
}
function formatDate(s) {
    if (!s) return '—';
    const d = new Date(s);
    return d.toLocaleDateString('en-US', {year:'numeric',month:'short',day:'numeric'});
}    
</script>