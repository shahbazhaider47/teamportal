<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canView      = staff_can('view_global', 'signoff');

          // Read perf indicators from controller if provided; otherwise pull from options
          $perf = isset($perf_indicators)
              ? strtolower(trim((string)$perf_indicators))
              : (function_exists('get_setting') ? strtolower(trim((string)get_setting('signoff_perf_indicators'))) : 'none');
        
          if ($perf === '') { $perf = 'none'; }
        
          $showTargets = in_array($perf, ['targets','both'], true);
          $showPoints  = in_array($perf, ['points','both'],  true);
        
          // Lock-after-submission (prefer controller param; fallback to option)
          $lockAfterSubmit = isset($lock_after_submit)
              ? (bool)$lock_after_submit
              : (function_exists('get_setting') ? (get_setting('signoff_lock_after_submit') === 'yes') : true);
              
        ?>

        <a href="<?= base_url('signoff') ?>" class="btn btn-outline-primary btn-header" title="Signoff Details">
            <i class="ti ti-calendar me-1"></i> Signoff
        </a>
        
        <a href="<?= $canView ? site_url('signoff/forms') : 'javascript:void(0);' ?>" 
           class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>" 
           title="Signoff Forms">
           <i class="ti ti-file-stack"></i> Forms
        </a>
        
      <?php if ($showTargets): ?>
        <a href="<?= $canView ? site_url('signoff/targets') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Targets">
           <i class="ti ti-target-arrow"></i> Targets
        </a>
      <?php endif; ?>
    
      <?php if ($showPoints): ?>
        <a href="<?= $canView ? site_url('signoff/points') : 'javascript:void(0);' ?>"
           class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>"
           title="Points">
           <i class="ti ti-trophy"></i> Points
        </a>
      <?php endif; ?>
        
      </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-12">
            <div class="card shadow-sm ">
                <div class="card-header bg-primary">
                    <h5 class="mb-0 text-white"><i class="ti ti-edit me-2"></i> Edit Signoff Form</h5>
                </div>
                <form action="<?= base_url('signoff/update_form/' . $form['id']) ?>" method="post" class="p-4 app-form">
                    <div class="row">
                      <div class="col-md-4 mb-3">
                          <label for="title" class="form-label">Form Title <span class="text-danger">*</span></label>
                          <input type="text" id="title" name="title" class="form-control" required maxlength="128"
                                 value="<?= html_escape($form['title']) ?>">
                      </div>
                    
                      <div class="col-md-4 mb-3">
                          <label class="form-label">Assigned To</label>
                          <small class="text-muted"> (Assign this form to a Team (or Global), or to a specific Position.)</small>
                          <?php
                            $isPosition = !empty($form['position_id']);     // preselect Positions if position_id present
                            $isTeam     = !$isPosition;                      // otherwise Teams/Global
                          ?>
                          <div class="btn-group" role="group" aria-label="Assigned To">
                            <input type="radio" class="btn-check" name="assigned_to" id="assn_teams" value="teams" <?= $isTeam ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="assn_teams">Teams</label>
                    
                            <input type="radio" class="btn-check" name="assigned_to" id="assn_positions" value="positions" <?= $isPosition ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="assn_positions">Positions</label>
                          </div>
                      </div>
                    
                      <!-- Teams picker -->
                      <div class="col-md-4 mb-3 assn-block assn-teams" <?= $isTeam ? '' : 'style="display:none;"' ?>>
                          <label for="team_id" class="form-label">Team</label>
                          <small class="text-muted">(Leave blank to allow all teams — Global Form)</small>
                          <select id="team_id" name="team_id" class="form-select">
                              <option value="">All Teams (Global Form)</option>
                              <?php foreach ($teams as $id => $name): ?>
                                  <option value="<?= $id ?>" <?= (!empty($form['team_id']) && (int)$form['team_id'] === (int)$id) ? 'selected' : '' ?>>
                                      <?= html_escape($name) ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                    
                      <!-- Positions picker -->
                      <div class="col-md-4 mb-3 assn-block assn-positions" <?= $isPosition ? '' : 'style="display:none;"' ?>>
                          <label for="position_id" class="form-label">Position <span class="text-danger">*</span></label>
                          <select id="position_id" name="position_id" class="form-select" <?= $isPosition ? 'required' : '' ?>>
                              <option value="">Select Position</option>
                              <?php foreach (($positions ?? []) as $pos): ?>
                                  <option value="<?= (int)$pos['id'] ?>" <?= (!empty($form['position_id']) && (int)$form['position_id'] === (int)$pos['id']) ? 'selected' : '' ?>>
                                      <?= html_escape($pos['title']) ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                    </div>

                    <!-- FIELD BUILDER START -->
                    <div class="mb-3">
                        <label class="form-label">Build Form Fields</label>
                        <div id="field-builder-list"></div>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <input type="text" id="fb-label" class="form-control" placeholder="Field Label">
                            </div>
                            <div class="col-md-2">
                                <select id="fb-type" class="form-select">
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="number">Number</option>
                                    <option value="amount">Amount</option>
                                    <option value="link">Link</option>
                                    <option value="date">Date</option>
                                    <option value="time">Time</option>
                                    <option value="file">File Upload</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" id="fb-name" class="form-control" placeholder="Field Name (opt)">
                            </div>
                            <div class="col-md-2">
                                <select id="fb-col" class="form-select">
                                    <option value="">Col</option>
                                    <?php for ($i=3; $i<=12; $i+=1): ?>
                                        <option value="col-md-<?= $i ?>">col-md-<?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex justify-content-center align-items-center">
                                <input type="checkbox" id="fb-required" class="form-check-input" title="Required">
                            </div>
                            <div class="col-md-1 d-flex justify-content-center align-items-center">
                                <button type="button" id="fb-add" class="btn btn-outline-primary btn-sm"><i class="ti ti-plus"></i></button>
                            </div>
                        </div>
                        <small class="text-muted">Add/edit fields visually above, or tweak the textarea below for advanced changes.</small>
                    </div>
                    <!-- FIELD BUILDER END -->
                    <div class="mb-3">
                        <label class="form-label">Example Fields</label>
                        <div class="d-flex flex-wrap gap-2 mb-2" id="example-fields">
                            <?php
                            $examples = [
                                'Eligibilities', 'Demo Entries', 'Submit Claims', 'Review Claims',
                                'Work on Rejections', 'Fix EOB/ERA Denials', 'Payments to Post',
                                'Claims Follow-Ups', 'Office Queries', 'Appeal to Send',
                                'Review Medical Records', 'Make Insurance Calls', 'Other Work'
                            ];
                            foreach ($examples as $field): ?>
                                <button type="button" class="btn btn-light-primary btn-sm example-field-btn" data-field="<?= htmlspecialchars($field) ?>">
                                    <?= htmlspecialchars($field) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Click to add preset fields, or type your own below.</small>
                    </div>
                    <div class="mb-3">
                        <label for="fields" class="form-label">Form Fields <span class="text-danger">*</span></label>
                        <textarea id="fields" name="fields" class="form-control" rows="5" required><?php
                            // Try to show as pretty JSON
                            $fields = $form['fields'];
                            if (is_string($fields)) {
                                $tryJson = json_decode($fields, true);
                                if (is_array($tryJson)) {
                                    echo htmlspecialchars(json_encode($tryJson, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                                } else {
                                    echo htmlspecialchars($fields);
                                }
                            } else {
                                echo htmlspecialchars(json_encode($fields, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                            }
                        ?></textarea>
                        <small class="text-muted">
                            <ul class="mb-1 mt-2 ps-3">
                                <li>Edit as comma-separated list <b>or</b> advanced JSON (col, type, required, etc).</li>
                                <li><b>Comma Example:</b> <code>Task Completed, Issues Faced, Next Plan</code></li>
                                <li><b>JSON Example:</b>
                                    <pre class="bg-light-primary p-2 mt-1 mb-0" style="font-size:0.96em;">
[
    {"name": "task_completed", "label": "Task Completed", "type": "text", "col": "col-md-6", "required": true},
    {"name": "issues_faced", "label": "Issues Faced", "type": "textarea"}
]
                                    </pre>
                                </li>
                            </ul>
                        </small>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                            <?= $form['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="<?= base_url('signoff') ?>" class="btn btn-secondary btn-sm me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ti ti-device-floppy me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    // HTML Escape Helper (for safe value injection)
    function htmlEscape(str) {
        return String(str || '').replace(/[&<>"'`=\/]/g, function(s) {
            return {
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                '`': '&#x60;', '=': '&#x3D;', '/': '&#x2F;'
            }[s];
        });
    }

    // Example field buttons
    var fieldsBox = document.getElementById('fields');
    document.querySelectorAll('.example-field-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var val = btn.getAttribute('data-field');
            var current = fieldsBox.value.split(',').map(function(x){return x.trim();});
            if (current.includes(val)) return;
            if (fieldsBox.value.trim() !== '' && !fieldsBox.value.trim().endsWith(',')) {
                fieldsBox.value += ', ';
            }
            fieldsBox.value += val;
            fieldsBox.focus();
        });
    });

    // FIELD BUILDER FUNCTIONALITY
    const fbList = document.getElementById('field-builder-list');
    let fbFields = [];
    // Initialize from textarea (if JSON)
    try {
        const arr = JSON.parse(fieldsBox.value);
        if (Array.isArray(arr)) fbFields = arr;
    } catch(e) {}

    // Render fields
    function renderBuilderFields() {
        fbList.innerHTML = '';
        if (!fbFields.length) return;

        fbFields.forEach((f, i) => {
            fbList.innerHTML += `
                <div class="row g-2 mb-2 align-items-center border-bottom pb-2 builder-field-row" data-index="${i}">
                    <div class="col-md-3">
                        <input type="text" class="form-control field-label" value="${htmlEscape(f.label)}" />
                    </div>
                    <div class="col-md-2">
                        <select class="form-select field-type">
                            <option value="text" ${f.type === "text" ? "selected" : ""}>Text</option>
                            <option value="textarea" ${f.type === "textarea" ? "selected" : ""}>Textarea</option>
                            <option value="number" ${f.type === "number" ? "selected" : ""}>Number</option>
                            <option value="amount" ${f.type === "amount" ? "selected" : ""}>Amount</option>
                            <option value="link" ${f.type === "link" ? "selected" : ""}>Link</option>
                            <option value="date" ${f.type === "date" ? "selected" : ""}>Date</option>
                            <option value="time" ${f.type === "time" ? "selected" : ""}>Time</option>
                            <option value="file" ${f.type === "file" ? "selected" : ""}>File Upload</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control field-name" value="${htmlEscape(f.name)}" />
                    </div>
                    <div class="col-md-2">
                        <select class="form-select field-col">
                            <option value="">Col</option>
                            ${[...Array(10).keys()].map(k => {
                                const colVal = "col-md-" + (k+3);
                                return `<option value="${colVal}" ${f.col === colVal ? "selected" : ""}>${colVal}</option>`;
                            }).join('')}
                        </select>
                    </div>
                    <div class="col-md-1 text-center">
                        <input type="checkbox" class="form-check-input field-required" ${f.required ? "checked" : ""} />
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-danger btn-sm btn-delete-field" title="Delete field">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        updateFieldsTextarea();
    }

    fbList.addEventListener('input', function(e) {
        const row = e.target.closest('.builder-field-row');
        if (!row) return;
        const index = parseInt(row.getAttribute('data-index'), 10);
        if (isNaN(index)) return;

        // Update the field object live
        fbFields[index].label = row.querySelector('.field-label').value;
        fbFields[index].type = row.querySelector('.field-type').value;
        fbFields[index].name = row.querySelector('.field-name').value;
        fbFields[index].col = row.querySelector('.field-col').value;
        fbFields[index].required = row.querySelector('.field-required').checked;

        updateFieldsTextarea();
    });

    // Delegate delete button clicks (works for dynamically rendered fields)
    fbList.addEventListener('click', function(e) {
        if (e.target.closest('.btn-delete-field')) {
            const row = e.target.closest('.builder-field-row');
            const index = parseInt(row.getAttribute('data-index'), 10);
            if (!isNaN(index)) {
                fbFields.splice(index, 1);
                renderBuilderFields();
            }
        }
    });

    function updateFieldsTextarea() {
        if (fbFields.length > 0) {
            fieldsBox.value = JSON.stringify(fbFields, null, 2);
        }
    }

    document.getElementById('fb-add').onclick = function(){
        const label = document.getElementById('fb-label').value.trim();
        const type = document.getElementById('fb-type').value;
        const col  = document.getElementById('fb-col').value;
        const name = document.getElementById('fb-name').value.trim() || label.toLowerCase().replace(/\s+/g,'_');
        const required = document.getElementById('fb-required').checked;
        if (!label) return;
        fbFields.push({ name, label, type, col, required });
        document.getElementById('fb-label').value = '';
        document.getElementById('fb-name').value = '';
        document.getElementById('fb-col').value = '';
        document.getElementById('fb-required').checked = false;
        renderBuilderFields();
    };

    // Allow manual textarea editing too (reset field builder if they type custom data)
    fieldsBox.addEventListener('input', function(){
        try {
            const arr = JSON.parse(fieldsBox.value);
            if (Array.isArray(arr)) {
                fbFields = arr;
                renderBuilderFields();
            }
        } catch(e) {
            // Ignore, user may be typing CSV or malformed JSON
        }
    });

    // Initial render if JSON present
    renderBuilderFields();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const byTeams = document.getElementById('assn_teams');
  const byPos   = document.getElementById('assn_positions');
  const blkTeams= document.querySelector('.assn-teams');
  const blkPos  = document.querySelector('.assn-positions');
  const teamSel = document.getElementById('team_id');
  const posSel  = document.getElementById('position_id');

  function syncAssn() {
    const teamsOn = byTeams.checked;
    blkTeams.style.display = teamsOn ? '' : 'none';
    blkPos.style.display   = teamsOn ? 'none' : '';
    if (teamsOn) {
      if (posSel) { posSel.value = ''; posSel.removeAttribute('required'); }
    } else {
      if (teamSel) teamSel.value = '';
      if (posSel) posSel.setAttribute('required', 'required'); // client-side hint
    }
  }
  [byTeams, byPos].forEach(r => r.addEventListener('change', syncAssn));
  syncAssn();
});
</script>