<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  const tgtFormsByTeam = <?= json_encode($forms_by_team ?? []) ?>;   // { "global":[...], "3":[...] }
  const tgtFormsByPos  = <?= json_encode($forms_by_pos  ?? []) ?>;   // { "5":[...] }
</script>

<?php
  $defaultStart = date('Y-m-01');
  $defaultEnd   = date('Y-m-t');
?>
<div class="modal fade" id="assignTargetModal" tabindex="-1" aria-labelledby="assignTargetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <form method="post" class="app-form" action="<?= base_url('signoff/targets/assign_target') ?>" id="targetAssignmentForm">

        <div class="modal-header bg-primary">
          <h1 class="modal-title text-white fs-5" id="assignTargetModalLabel">
            <i class="ti ti-target me-2" aria-hidden="true"></i>Assign Targets
          </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- Assigned By -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label for="assignBySelect" class="form-label">Assigned By</label>
              <select name="assigned_by" id="assignBySelect" class="form-select" required>
                <option value="team" selected>By Teams</option>
                <option value="position">By Positions</option>
                <option value="global">Global</option>
              </select>
            </div>

            <!-- Team picker -->
            <div class="col-md-4" id="teamBlock">
              <label for="assignTeamSelect" class="form-label">Select Team</label>
              <select name="team_id" id="assignTeamSelect" class="form-select">
                <option value="">— Select Team —</option>
                <?php foreach ($teams as $team): ?>
                  <option value="<?= (int)$team['id'] ?>"><?= html_escape($team['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Position picker -->
            <div class="col-md-4" id="positionBlock" style="display:none;">
              <label for="assignPositionSelect" class="form-label">Select Position</label>
              <select name="position_id" id="assignPositionSelect" class="form-select">
                <option value="">— Select Position —</option>
                <?php foreach ($positions as $pos): ?>
                  <option value="<?= (int)$pos['id'] ?>"><?= html_escape($pos['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Form picker -->
            <div class="col-md-4" id="formBlock">
              <label for="assignFormSelect" class="form-label">Select Form</label>
              <select id="assignFormSelect" name="form_id" class="form-select" required>
                <option value="">— Select Form —</option>
              </select>
            </div>

            <!-- Date range -->
            <div class="col-md-2">
              <label for="startDateInput" class="form-label">Start Date</label>
              <input type="date" name="start_date" id="startDateInput" class="form-control"
                     value="<?= html_escape($defaultStart) ?>" required>
            </div>
            <div class="col-md-2">
              <label for="endDateInput" class="form-label">End Date</label>
              <input type="date" name="end_date" id="endDateInput" class="form-control"
                     value="<?= html_escape($defaultEnd) ?>" required>
            </div>
          </div>

          <div id="targetsBanner"></div>

          <div id="targetFieldsBox" class="mt-0 mb-0" aria-live="polite">
            <div class="alert alert-info mb-0">Choose assignment type, then select a team, position, or global scope to load metrics.</div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-sm" id="submitTargetBtn">Assign Targets</button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const bySel  = document.getElementById('assignBySelect');
  const teamBlk = document.getElementById('teamBlock');
  const teamSel = document.getElementById('assignTeamSelect');
  const posBlk  = document.getElementById('positionBlock');
  const posSel  = document.getElementById('assignPositionSelect');
  const formSel = document.getElementById('assignFormSelect');
  const box     = document.getElementById('targetFieldsBox');
  const submitBtn = document.getElementById('submitTargetBtn');
  const banner  = document.getElementById('targetsBanner');

  const FORMS_BY_TEAM = <?= json_encode($forms_by_team ?? []) ?>;
  const FORMS_BY_POS  = <?= json_encode($forms_by_pos  ?? []) ?>;

  const escapeHtml = s => String(s ?? '').replace(/[&<>"'`=\/]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;'}[c]));

  const spinner = () => '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

  function resetFormSelect() {
    formSel.innerHTML = '<option value="">— Select Form —</option>';
  }

  function resetBox(msg='Choose an assignment mode, then select scope.') {
    box.innerHTML = '<div class="alert alert-info mb-0">' + escapeHtml(msg) + '</div>';
  }

  function renderMetrics(formObj) {
    if (!formObj || !formObj.fields) {
      resetBox('No metrics found for this form.');
      return;
    }
    let defs;
    try { defs = JSON.parse(formObj.fields); } catch(e) { defs = []; }

    const metrics = defs.filter(f => ['amount','number'].includes((f.type||'').toLowerCase()));
    if (!metrics.length) {
      box.innerHTML = '<div class="alert alert-warning mb-0">No numeric fields in this form.</div>';
      return;
    }

    let html = `
      <div class="table-responsive">
        <table class="table small table-bordered align-middle mb-0">
          <thead class="bg-light-primary">
            <tr>
              <th style="width:40px;"></th>
              <th>Metric</th>
              <th style="width:200px;">Target</th>
            </tr>
          </thead>
          <tbody>
    `;
    metrics.forEach((f,i)=>{
      const name = f.name || 'metric'+i;
      const label= f.label || name;
      html += `
        <tr>
          <td class="text-center">
            <input type="checkbox" class="form-check-input" id="chk_${i}" onclick="(function(cb){var el=document.getElementById('fld_${i}');if(el){el.disabled=!cb.checked;if(!cb.checked){el.value='';}}})(this)">
          </td>
          <td><label for="fld_${i}" class="mb-0">${escapeHtml(label)}</label></td>
          <td><input type="number" step="0.01" min="0" id="fld_${i}" name="targets[${escapeHtml(name)}]" class="form-control" disabled></td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    html += '<small class="text-muted d-block mt-2">Check and fill targets to assign.</small>';
    box.innerHTML = html;
  }

  function populateForms(list) {
    resetFormSelect();
    if (!list || !list.length) {
      resetBox('No forms found for this selection.');
      return;
    }
    list.forEach(f=>{
      const opt=document.createElement('option');
      opt.value=f.id;
      opt.textContent=f.title || ('Form #'+f.id);
      opt.dataset.fields=f.fields||'[]';
      formSel.appendChild(opt);
    });
    if (list.length===1) {
      renderMetrics(list[0]);
      formSel.value=list[0].id;
    } else resetBox('Select a form to load metrics.');
  }

  function listGlobal() { return FORMS_BY_TEAM['global']||[]; }
  function listTeam(tid) { return FORMS_BY_TEAM[tid]||[]; }
  function listPos(pid) { return FORMS_BY_POS[pid]||[]; }

  function toggleMode() {
    const mode=bySel.value;
    banner.innerHTML='';
    resetFormSelect();
    resetBox();

    teamBlk.style.display='none';
    posBlk.style.display='none';

    if (mode==='team') {
      teamBlk.style.display='';
      populateForms(listTeam(teamSel.value));
    } else if (mode==='position') {
      posBlk.style.display='';
      populateForms(listPos(posSel.value));
    } else {
      populateForms(listGlobal());
    }
  }

  bySel.addEventListener('change', toggleMode);

  teamSel.addEventListener('change',()=>{
    if (bySel.value==='team') populateForms(listTeam(teamSel.value));
  });

  posSel.addEventListener('change',()=>{
    if (bySel.value==='position') populateForms(listPos(posSel.value));
  });

  formSel.addEventListener('change',()=>{
    const opt=formSel.options[formSel.selectedIndex];
    if (!opt) return;
    try {
      const f={id:opt.value,title:opt.textContent,fields:opt.dataset.fields};
      renderMetrics(f);
    } catch(e){resetBox();}
  });

  document.getElementById('targetAssignmentForm').addEventListener('submit', e=>{
    const mode=bySel.value;
    const fid=formSel.value;
    if (!fid) { alert('Please select a form.'); e.preventDefault(); return; }
    const checked=box.querySelector('input[type="checkbox"]:checked');
    if (!checked) { alert('Please check at least one metric.'); e.preventDefault(); return; }
    submitBtn.disabled=true;
    submitBtn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
  });

  toggleMode();
});
</script>
