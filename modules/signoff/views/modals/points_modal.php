<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="assignPointsModal" tabindex="-1" aria-labelledby="assignPointsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post" class="app-form" action="<?= base_url('signoff/points/assign_points') ?>" id="pointsAssignmentForm">

        <div class="modal-header bg-primary">
          <h1 class="modal-title text-white fs-5" id="assignPointsModalLabel">
            <i class="ti ti-stars me-2" aria-hidden="true"></i>Assign Points
          </h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- Assigned by -->
          <div class="row g-3 mb-2">
            <div class="col-md-12">
              <label for="assignByPointsSelect" class="form-label">Assigned by</label>
              <select id="assignByPointsSelect" class="form-select">
                <option value="team" selected>Team</option>
                <option value="position">Position</option>
                <option value="global">Global</option>
              </select>
              <small class="text-muted">Pick the scope you want to browse forms by.</small>
            </div>
          </div>

          <!-- Team picker -->
          <div class="row g-3 mb-3" id="pointsTeamBlock">
            <div class="col-md-12">
              <label for="assignPointsTeamSelect" class="form-label">Select Team</label>
              <select id="assignPointsTeamSelect" class="form-select">
                <option value="">— Select Team —</option>
                <?php foreach (($teams ?? []) as $team): ?>
                  <option value="<?= html_escape($team['id']) ?>"><?= html_escape($team['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Position picker -->
          <div class="row g-3 mb-3" id="pointsPositionBlock" style="display:none;">
            <div class="col-md-12">
              <label for="assignPointsPositionSelect" class="form-label">Select Position</label>
              <select id="assignPointsPositionSelect" class="form-select">
                <option value="">— Select Position —</option>
                <?php foreach (($positions ?? []) as $pos): ?>
                  <option value="<?= (int)$pos['id'] ?>"><?= html_escape($pos['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Form picker (only when more than one choice) -->
          <div class="row g-3 mb-3" id="pointsFormPicker" style="display:none;">
            <div class="col-md-12">
              <label for="assignPointsFormSelect" class="form-label">Select Form</label>
              <select id="assignPointsFormSelect" class="form-select"></select>
            </div>
          </div>

          <!-- Always keep chosen ids here -->
          <input type="hidden" name="team_id" id="assignPointsTeamIdHidden" value="">
          <input type="hidden" name="form_id" id="assignPointsFormId" value="">

          <div id="pointsBanner" class="mb-2"></div>

          <div id="pointsFieldsBox" class="mt-0 mb-0" aria-live="polite">
            <small>Select scope above, then choose a team/position to load its forms.</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-sm" id="submitPointsBtn">Assign Points</button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>

      </form>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
  // ===== Data from controller =====
  const formsByTeam     = <?= json_encode($forms_by_team       ?? []) ?>; // { "global": [...], "<teamId>": [...] }
  const formsByPosition = <?= json_encode($forms_by_position   ?? []) ?>; // { "<positionId>": [...] }
  const pointsFlags     = <?= json_encode($points_flags        ?? []) ?>; // { teamIdInt: { formId: true } }
  const subsCounts      = <?= json_encode($subs_counts         ?? []) ?>; // { formId: n }

  // ===== DOM refs =====
  const bySel        = document.getElementById('assignByPointsSelect');
  const teamBlock    = document.getElementById('pointsTeamBlock');
  const teamSelect   = document.getElementById('assignPointsTeamSelect');
  const posBlock     = document.getElementById('pointsPositionBlock');
  const posSelect    = document.getElementById('assignPointsPositionSelect');

  const formPicker   = document.getElementById('pointsFormPicker');
  const formSelect   = document.getElementById('assignPointsFormSelect');
  const formIdInput  = document.getElementById('assignPointsFormId');
  const teamIdHidden = document.getElementById('assignPointsTeamIdHidden');

  const fieldsBox    = document.getElementById('pointsFieldsBox');
  const formEl       = document.getElementById('pointsAssignmentForm');
  const submitBtn    = document.getElementById('submitPointsBtn');
  const bannerEl     = document.getElementById('pointsBanner');

  if (!bySel || !formEl || !fieldsBox || !formIdInput || !teamIdHidden) return;

  const htmlEscape = (str) =>
    String(str || '').replace(/[&<>"'`=\/]/g, s => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#x27;','`':'&#x60;','=':'&#x3D;','/':'&#x2F;'
    }[s]));

  const spinner    = () => '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
  const showBanner = html => { if (bannerEl) bannerEl.innerHTML = html || ''; };
  const clearBanner= () => { if (bannerEl) bannerEl.innerHTML = ''; };

  // ===== Data helpers =====
  function listForTeam(teamIdStr) {
    // Only forms assigned to this team (no global, no positions)
    return Array.isArray(formsByTeam[teamIdStr]) ? formsByTeam[teamIdStr].slice() : [];
  }
  function listForGlobal() {
    // Only truly global forms (team_id NULL && position_id NULL)
    return Array.isArray(formsByTeam['global']) ? formsByTeam['global'].slice() : [];
  }
  function listForPosition(positionIdStr) {
    // Only forms assigned to the selected position
    return Array.isArray(formsByPosition[String(positionIdStr)]) ? formsByPosition[String(positionIdStr)].slice() : [];
  }

  // ===== Rendering =====
  function renderFieldsForForm(formObj) {
    if (!formObj || !formObj.fields) {
      fieldsBox.innerHTML = '<div class="alert alert-warning mb-0">No signoff form or fields to load.</div>';
      submitBtn.disabled = true; return;
    }

    let fields;
    try { fields = JSON.parse(formObj.fields); }
    catch (e) {
      fieldsBox.innerHTML = '<div class="alert alert-danger mb-0">Invalid form fields configuration.</div>';
      submitBtn.disabled = true; return;
    }

    const pointable = (Array.isArray(fields) ? fields : []).filter(f => {
      const t = String(f.type || '').toLowerCase();
      return t === 'number' || t === 'amount';
    });

    if (!pointable.length) {
      fieldsBox.innerHTML = '<div class="alert alert-warning mb-0">No numeric/amount fields available for points assignment.</div>';
      submitBtn.disabled = true; return;
    }

    let html = `
      <div class="table-responsive">
        <table class="table small table-bordered align-middle">
          <thead class="bg-light-primary">
            <tr>
              <th style="width:40px;"></th>
              <th>Metric</th>
              <th style="width:180px;">Points</th>
            </tr>
          </thead>
          <tbody>
    `;

    pointable.forEach((f, i) => {
      const id = (String(f.name || '').trim().replace(/[^a-z0-9]/gi, '_') || 'metric') + '_' + i;
      html += `
        <tr>
          <td class="text-center">
            <input type="checkbox" id="chk_${id}" class="form-check-input"
              onclick="document.getElementById('input_${id}').disabled = !this.checked;">
          </td>
          <td><label for="input_${id}" class="mb-0">${htmlEscape(f.label || f.name)}</label></td>
          <td>
            <input type="number" step="0.01" min="0" id="input_${id}"
                   name="points[${htmlEscape(f.name)}]" class="form-control" disabled required>
          </td>
        </tr>
      `;
    });

    html += `
          </tbody>
        </table>
      </div>
      <small class="text-muted mt-2 d-block">Select metrics to assign points. Unchecked rows will not be saved.</small>`;
    fieldsBox.innerHTML = html;
    submitBtn.disabled = false;
  }

  function populateForms(list) {
    formSelect.innerHTML = '';
    formPicker.style.display = 'none';
    formIdInput.value = '';

    if (!list.length) {
      fieldsBox.innerHTML = '<div class="alert alert-warning mb-0">No signoff forms found for this selection.</div>';
      submitBtn.disabled = true;
      return;
    }

    if (list.length === 1) {
      formIdInput.value = String(list[0].id);
      maybeWarn(list[0].id);
      renderFieldsForForm(list[0]);
      return;
    }

    formPicker.style.display = 'block';
    list.forEach(f => {
      const inactive = String(f.is_active ?? '0') === '0';
      const opt = document.createElement('option');
      opt.value = String(f.id);
      opt.textContent = (f.title || ('Form #' + f.id)) + (inactive ? ' (inactive)' : '');
      formSelect.appendChild(opt);
    });

    // Default select first
    formIdInput.value = String(list[0].id);
    maybeWarn(list[0].id);
    renderFieldsForForm(list[0]);

    formSelect.onchange = function() {
      const selId = this.value;
      const chosen = list.find(x => String(x.id) === String(selId));
      formIdInput.value = String(selId || '');
      maybeWarn(selId);
      renderFieldsForForm(chosen || null);
    };
  }

  function maybeWarn(formId) {
    clearBanner();
    const fid = parseInt(formId, 10);
    // Warning uses the *team* scope (global/position treated as team_id=0)
    const teamIdInt =
      (bySel.value === 'team' && teamSelect && teamSelect.value) ? (parseInt(teamSelect.value, 10) || 0) : 0;

    const hasPts  = !!(pointsFlags[teamIdInt] && pointsFlags[teamIdInt][fid]);
    const hasSubs = (parseInt(subsCounts[fid] || 0, 10) > 0);

    if (hasPts && hasSubs) {
      showBanner(`
        <div class="alert alert-warning py-2 mb-2">
          <i class="ti ti-alert-triangle me-1"></i>
          Points are already assigned for this scope & form, and there are existing submissions.
          Updating points now will not change previously saved totals.
        </div>
      `);
    }
  }

  // ===== Mode switching =====
  function setHiddenTeamForMode() {
    const mode = bySel.value;
    if (mode === 'team') {
      teamIdHidden.value = teamSelect.value || '';
    } else {
      // position/global → team_id='global' (backend treats as 0)
      teamIdHidden.value = 'global';
    }
  }

  function onModeChange() {
    clearBanner();
    fieldsBox.innerHTML = '<small>Select scope above, then choose a team/position to load its forms.</small>';
    submitBtn.disabled = true;
    formIdInput.value = '';
    formSelect.innerHTML = '';
    formPicker.style.display = 'none';

    const mode = bySel.value;
    if (mode === 'team') {
      teamBlock.style.display = '';
      posBlock.style.display  = 'none';
      teamSelect.value = '';
      setHiddenTeamForMode();
    } else if (mode === 'position') {
      teamBlock.style.display = 'none';
      posBlock.style.display  = '';
      posSelect.value = '';
      setHiddenTeamForMode();
    } else { // global
      teamBlock.style.display = 'none';
      posBlock.style.display  = 'none';
      setHiddenTeamForMode();
      fieldsBox.innerHTML = spinner();
      populateForms(listForGlobal());
    }
  }

  // ===== Events =====
  bySel.addEventListener('change', onModeChange);

  if (teamSelect) {
    teamSelect.addEventListener('change', function() {
      setHiddenTeamForMode();
      if (!this.value) { fieldsBox.innerHTML = '<small>Please select a team.</small>'; submitBtn.disabled = true; return; }
      fieldsBox.innerHTML = spinner();
      populateForms(listForTeam(String(this.value)));
    });
  }

  if (posSelect) {
    posSelect.addEventListener('change', function() {
      setHiddenTeamForMode(); // stays 'global'
      if (!this.value) { fieldsBox.innerHTML = '<small>Please select a position.</small>'; submitBtn.disabled = true; return; }
      fieldsBox.innerHTML = spinner();
      populateForms(listForPosition(String(this.value)));
    });
  }

  // Validate on submit
  formEl.addEventListener('submit', function(e) {
    const mode = bySel.value;
    if (mode === 'team' && !teamSelect.value)     { e.preventDefault(); alert('Please select a team.'); return; }
    if (mode === 'position' && !posSelect.value)  { e.preventDefault(); alert('Please select a position.'); return; }
    if (!formIdInput.value)                       { e.preventDefault(); alert('Please select a form.'); return; }

    const checked = fieldsBox.querySelectorAll('input[type="checkbox"]:checked');
    if (checked.length === 0) { e.preventDefault(); alert('Please select at least one metric to assign points.'); return; }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
  });

  // Init
  onModeChange();
});
</script>