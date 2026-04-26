<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

<div class="view-header mb-3">
    <div class="view-icon me-3">
        <i class="ti ti-edit"></i></div>
        <div class="flex-grow-1">
          <div class="view-title"><?= $page_title ?>

          <?php if ($template['is_active']): ?>
            <span class="badge bg-light-success text-success">Active</span>
          <?php else: ?>
            <span class="badge bg-light-danger text-danger">Inactive</span>
          <?php endif; ?>
      
          </div>
        </div>
        
    <div class="ms-auto d-flex gap-2">

      <button type="submit" form="templateMetaForm" class="btn btn-primary btn-header">
        <i class="ti ti-device-floppy me-1"></i> Save Template
      </button>
      
      <div class="btn-divider mt-1"></div> 
      
        <a href="<?= site_url('evaluations/templates') ?>" class="btn btn-header btn-light-primary">
           <i class="ti ti-arrow-back-up"></i>
        </a>
    </div>
</div>

<div class="row g-3">

  <!-- ── Left: template meta + guide ─────────────────────────────── -->
  <div class="col-12 col-xl-4">

    <!-- Template Settings -->
      <div class="solid-card">
        <div class="card-header d-flex align-items-center gap-2 px-1">
          <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:30px;height:30px;background:#e0e7ff;">
            <i class="ti ti-template" style="color:#4f46e5;font-size:15px;"></i>
          </div>
          <span class="fw-semibold small">Template details</span>
        </div>
      <div class="card-body mt-3">
        <form method="post" action="<?= site_url('evaluations/template_edit/' . $template['id']) ?>"
              id="templateMetaForm" class="app-form">

          <!-- Template name -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-uppercase text-muted mb-1">
              Template name <span class="text-danger">*</span>
            </label>
            <input type="text" name="name" class="form-control form-control-sm" required
                   value="<?= e($template['name']) ?>">
          </div>

          <!-- Team picker -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-uppercase text-muted mb-1">
              Team <span class="text-danger">*</span>
            </label>
            <select name="team_id" id="team_select" class="form-select form-select-sm" required>
              <option value="">— Select Team —</option>
              <?php foreach ($teams as $t): ?>
                <option value="<?= (int) $t['id'] ?>"
                        data-dept="<?= e($t['department_name']) ?>"
                        data-lead="<?= e($t['teamlead_name']) ?>"
                        data-mgr="<?= e($t['manager_name']) ?>"
                        <?= (int) $template['team_id'] === (int) $t['id'] ? 'selected' : '' ?>>
                  <?= e($t['team_name']) ?>
                  <?php if ($t['department_name']): ?>
                    &mdash; <?= e($t['department_name']) ?>
                  <?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Team info strip — shown when a team is selected -->
          <div id="teamInfoStrip" class="<?= !empty($template['team_id']) ? '' : 'd-none' ?> mb-3 p-2 rounded-2 border small"
               style="background:var(--color-bg-secondary,#f8fafc);">
            <div class="row g-2">
              <div class="col-12">
                <div class="text-muted x-small">Department</div>
                <div class="fw-semibold" id="strip_dept">
                  <?= e($template['department_name'] ?? '—') ?>
                </div>
              </div>
              <div class="col-12">
                <div class="text-muted x-small">Team Lead</div>
                <div class="fw-semibold" id="strip_lead">
                  <?= e($template['teamlead_name'] ?? '—') ?>
                </div>
              </div>
              <div class="col-12">
                <div class="text-muted x-small">Manager</div>
                <div class="fw-semibold" id="strip_mgr">
                  <?= e($template['manager_name'] ?? '—') ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Review type -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-uppercase text-muted mb-1">Review type</label>
            <select name="review_type" class="form-select form-select-sm">
              <?php foreach (eval_review_types() as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $template['review_type'] === $key ? 'selected' : '' ?>>
                  <?= e($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Description -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-uppercase text-muted mb-1">Description</label>
            <textarea name="description" class="form-control form-control-sm" rows="3"
                      placeholder="Brief description…"><?= e($template['description'] ?? '') ?></textarea>
          </div>

          <hr class="my-2">

          <!-- Active toggle -->
          <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="is_active_chk" <?= $template['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label small" for="is_active_chk">Active — available for use</label>
          </div>

        </form>
      </div>
    </div>

    <!-- Section key reference -->
      <div class="solid-card">
        <div class="card-header d-flex align-items-center gap-2 px-1">
        <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:30px;height:30px;background:#f0f4ff;">
          <i class="ti ti-info-circle" style="color:#6366f1;font-size:15px;"></i>
        </div>
        <span class="fw-semibold small">How sections work</span>
      </div>
      <div class="card-body mt-3 small text-muted">
        <ul class="mb-0">
          <li class="mb-2"><strong class="text-body">Section</strong> — a group of related questions (e.g., Attendance, Work Targets)</li>
          <li class="mb-2"><strong class="text-body">Criteria</strong> — individual questions inside a section</li>
          <li><strong class="text-body">Section keys:</strong>
            <div class="mt-1 d-flex flex-wrap gap-1">
              <?php
              foreach (['attendance','work_targets','perf_metrics','ratings','phone_usage','supervisor','goals','verdict','custom'] as $sk):
              ?>
                <code class="px-2 py-1 rounded"
                      style="background:#f1f5f9;color:#475569;font-size:10px;"><?= $sk ?></code>
              <?php endforeach; ?>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Criteria type reference -->
      <div class="solid-card">
        <div class="card-header d-flex align-items-center gap-2 px-1">
        <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:30px;height:30px;background:#dcfce7;">
          <i class="ti ti-list-details" style="color:#16a34a;font-size:15px;"></i>
        </div>
        <span class="fw-semibold small">Criteria types</span>
      </div>
      <div class="card-body mt-3">
        <?php
        $ctypes_ref = [
            'rating'     => ['Rating (1–5)',     '#eff6ff', '#1d4ed8'],
            'pass_fail'  => ['Pass / Fail',       '#f0fdf4', '#15803d'],
            'target'     => ['Work Target',       '#fff7ed', '#c2410c'],
            'attendance' => ['Attendance',        '#faf5ff', '#7e22ce'],
            'phone'      => ['Phone Usage',       '#fefce8', '#a16207'],
            'text'       => ['Free Text',         '#f8fafc', '#64748b'],
        ];
        foreach ($ctypes_ref as $key => [$lbl, $bg, $col]):
        ?>
          <div class="d-flex align-items-center justify-content-between py-1"
               style="border-bottom:0.5px solid var(--color-border-tertiary,rgba(0,0,0,.08));">
            <code style="font-size:10px;background:#f1f5f9;color:#475569;padding:2px 7px;border-radius:4px;"><?= $key ?></code>
            <span class="badge rounded-pill px-2"
                  style="background:<?= $bg ?>;color:<?= $col ?>;font-size:11px;"><?= $lbl ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- ── Right: sections & criteria manager ───────────────────────── -->
  <div class="col-12 col-xl-8">

      <div class="solid-card">
<div class="card-header d-flex align-items-center justify-content-between px-2 py-2">
    
    <div class="d-flex align-items-center gap-2">
        <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
            style="width:30px;height:30px;background:#e0e7ff;">
            <i class="ti ti-layout-rows" style="color:#4f46e5;font-size:15px;"></i>
        </div>

        <span class="fw-semibold small">Sections &amp; criteria</span>

        <span class="badge bg-light-secondary text-secondary" id="section_count_badge">
            <?= count($sections) ?> section<?= count($sections) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <button type="button" class="btn btn-ssm btn-primary" id="addSectionBtn">
        <i class="ti ti-plus me-1"></i> Add Section
    </button>

</div>

      <div class="card-body p-0" id="sectionsContainer">

        <?php if (!empty($sections)): ?>
          <?php foreach ($sections as $sec): ?>
          <div class="solid-card section-block mt-3" id="sec_block_<?= $sec['id'] ?>"
               style="border-bottom:0.5px solid var(--color-border-tertiary,rgba(0,0,0,.08));">

            <!-- Section header -->
            <div class="d-flex align-items-center gap-2 px-2 py-1"
                 style="background:var(--color-background-secondary,#f8fafc);">
              <div class="flex-grow-1 lh-sm">
                <span class="fw-semibold small"><?= e($sec['section_label']) ?></span>
                <code class="ms-2 px-1 rounded"
                      style="font-size:10px;background:#e2e8f0;color:#475569;"><?= e($sec['section_key']) ?></code>
              </div>
              <span class="badge bg-light-secondary text-secondary" style="font-size:10px;">
                order <?= (int) $sec['sort_order'] ?>
              </span>
              <?php if ($sec['is_active']): ?>
                <span class="badge bg-light-success text-success" style="font-size:10px;">Active</span>
              <?php else: ?>
                <span class="badge bg-light-secondary text-secondary" style="font-size:10px;">Inactive</span>
              <?php endif; ?>
              <button type="button"
                      class="btn btn-ssm btn-outline-primary add-criteria-btn"
                      data-section-id="<?= $sec['id'] ?>"
                      data-section-label="<?= e($sec['section_label']) ?>">
                <i class="ti ti-plus"></i>
              </button>
              <button type="button"
                      class="btn btn-ssm btn-outline-danger delete-section-btn"
                      data-id="<?= $sec['id'] ?>">
                <i class="ti ti-trash"></i>
              </button>
            </div>

            <!-- Criteria table -->
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0"
                     style="font-size:12px;"
                     id="crit_tbl_<?= $sec['id'] ?>">
                <thead class="bg-light-primary small">
                  <tr>
                    <th style="width:4%;padding:6px 12px;">#</th>
                    <th style="width:32%;padding:6px 8px;">Label</th>
                    <th style="padding:6px 8px;">Type</th>
                    <th class="text-center" style="padding:6px 8px;">Tgt/Day</th>
                    <th class="text-center" style="padding:6px 8px;">Tgt/Month</th>
                    <th class="text-center" style="padding:6px 8px;">Order</th>
                    <th style="padding:6px 8px;"></th>
                  </tr>
                </thead>
                <tbody id="crit_body_<?= $sec['id'] ?>">
                  <tr>
                    <td colspan="9" class="text-center text-muted py-3" style="font-size:12px;">
                      <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                      Loading…
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
          <?php endforeach; ?>

        <?php else: ?>
          <div class="text-center text-muted py-5 small" id="noSectionsMsg">
            <i class="ti ti-layout-rows d-block mb-2 opacity-50" style="font-size:32px;"></i>
            No sections yet.<br>
            <span class="x-small">Click <strong>Add Section</strong> to begin building this template.</span>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>
</div>

<!-- ── Add / Edit Section Modal ──────────────────────────────────────── -->
<div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title text-white" id="sectionModalTitle">Add Section</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body app-form">
        <input type="hidden" id="sm_section_id" value="">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Section Label <span class="text-danger">*</span></label>
          <input type="text" id="sm_label" class="form-control"
                 placeholder="e.g., Attendance &amp; Punctuality">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Section Key <span class="text-danger">*</span></label>
          <select id="sm_key" class="form-select">
            <?php
            $section_keys = ['attendance','work_targets','perf_metrics','ratings',
                             'phone_usage','supervisor','goals','verdict','custom'];
            foreach ($section_keys as $sk):
            ?>
              <option value="<?= $sk ?>"><?= ucwords(str_replace('_', ' ', $sk)) ?></option>
            <?php endforeach; ?>
          </select>

          <!-- Key hint — updates on change -->
          <div id="sm_key_hint" class="mt-2 p-2 rounded-2 x-small"
               style="background:var(--color-bg-secondary,#f8fafc);border:1px solid rgba(0,0,0,.07);line-height:1.5;">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Sort Order</label>
          <input type="number" id="sm_order" class="form-control" value="0" min="0">
        </div>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" id="sm_active" checked>
          <label class="form-check-label small" for="sm_active">Active</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm"
                data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="saveSectionBtn">
          <i class="ti ti-check me-1"></i> Save Section
        </button>
      </div>
    </div>
  </div>
</div>


<!-- ── Add / Edit Criteria Modal ─────────────────────────────────────── -->
<div class="modal fade" id="criteriaModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title text-white" id="criteriaModalTitle">Add Criteria</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body app-form">
        <input type="hidden" id="cm_criteria_id" value="">
        <input type="hidden" id="cm_section_id"  value="">

        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label small fw-semibold">Label / Question <span class="text-danger">*</span></label>
            <input type="text" id="cm_label" class="form-control"
                   placeholder="e.g., Job Knowledge">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Criteria Type <span class="text-danger">*</span></label>
            <select id="cm_type" class="form-select">
              <?php
              $ctypes = [
                  'rating'     => 'Rating (1–5)',
                  'pass_fail'  => 'Pass / Fail',
                  'target'     => 'Work Target',
                  'attendance' => 'Attendance',
                  'phone'      => 'Phone Usage',
                  'text'       => 'Free Text',
              ];
              foreach ($ctypes as $k => $l):
              ?>
                <option value="<?= $k ?>"><?= e($l) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label small fw-semibold">Target/Day</label>
            <input type="number" step="0.01" id="cm_target_day" class="form-control"
                   placeholder="—">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Deadline</label>
            <input type="text" id="cm_deadline" class="form-control"
                   placeholder="e.g., 5">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Target/Month</label>
            <input type="number" step="0.01" id="cm_target_month" class="form-control"
                   placeholder="—">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">Sort Order</label>
            <input type="number" id="cm_order" class="form-control" value="0" min="0">
          </div>

          <div class="col-md-8">
            <label class="form-label small fw-semibold">
              Note <small class="text-muted fw-normal">(shown below label)</small>
            </label>
            <input type="text" id="cm_note" class="form-control"
                   placeholder="e.g., For Team Leads only">
          </div>
          <div class="col-md-4 d-flex align-items-end pb-1">
            <div class="form-check form-switch">
              <input type="checkbox" class="form-check-input" id="cm_active" checked>
              <label class="form-check-label small" for="cm_active">Active</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm"
                data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="saveCriteriaBtn">
          <i class="ti ti-check me-1"></i> Save Criteria
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  var TEMPLATE_ID = <?= (int) $template['id'] ?>;
  var BASE        = '<?= site_url() ?>';
  var $secModal   = null;
  var $critModal  = null;

  // ── Team select strip ────────────────────────────────────────────
  document.getElementById('team_select').addEventListener('change', function () {
    var opt   = this.options[this.selectedIndex];
    var strip = document.getElementById('teamInfoStrip');
    if (!this.value) {
      strip.classList.add('d-none');
      return;
    }
    document.getElementById('strip_dept').textContent = opt.dataset.dept || '—';
    document.getElementById('strip_lead').textContent = opt.dataset.lead || '—';
    document.getElementById('strip_mgr').textContent  = opt.dataset.mgr  || '—';
    strip.classList.remove('d-none');
  });

  function getSecModal() {
    if (!$secModal) $secModal = new bootstrap.Modal(document.getElementById('sectionModal'));
    return $secModal;
  }
  function getCritModal() {
    if (!$critModal) $critModal = new bootstrap.Modal(document.getElementById('criteriaModal'));
    return $critModal;
  }

  // ── AJAX POST ────────────────────────────────────────────────────
  function ajax(url, body, cb) {
    fetch(url, {
      method:  'POST',
      headers: {
        'Content-Type':     'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: new URLSearchParams(body || {}).toString(),
    })
    .then(function (r) {
      return r.text().then(function (txt) {
        if (!r.ok) throw new Error('HTTP ' + r.status + ': ' + txt.substring(0, 200));
        var trimmed = txt.trim();
        if (!trimmed) throw new Error('Empty response from server');
        try {
          return JSON.parse(trimmed);
        } catch (e) {
          throw new Error('Invalid JSON: ' + trimmed.substring(0, 200));
        }
      });
    })
    .then(cb)
    .catch(function (err) {
      console.error('AJAX error [' + url + ']:', err.message);
      alert('Request failed: ' + err.message);
    });
  }

  // ── AJAX GET ─────────────────────────────────────────────────────
  function ajaxGet(url, cb) {
    fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(function (r) {
      return r.text().then(function (txt) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        var trimmed = txt.trim();
        if (!trimmed) throw new Error('Empty response');
        return JSON.parse(trimmed);
      });
    })
    .then(cb)
    .catch(function (err) {
      console.error('GET error [' + url + ']:', err.message);
    });
  }

  // ── HTML escape ──────────────────────────────────────────────────
  function esc(s) {
    return String(s || '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Load criteria for one section ────────────────────────────────
  function loadCriteria(sectionId) {
    var tbody = document.getElementById('crit_body_' + sectionId);
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3" style="font-size:12px;">'
      + '<span class="spinner-border spinner-border-sm me-1"></span>Loading…</td></tr>';

    ajaxGet(BASE + 'evaluations/section_criteria_json/' + sectionId, function (data) {
      if (!data || !data.ok) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-2" style="font-size:12px;">'
          + 'Failed to load criteria.</td></tr>';
        return;
      }
      renderRows(tbody, data.criteria || [], sectionId);
    });
  }

  // ── Render criteria rows ─────────────────────────────────────────
  function renderRows(tbody, criteria, sectionId) {
    if (!criteria.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3" style="font-size:12px;">'
        + 'No criteria yet. Click <strong>+ Criteria</strong> to add.</td></tr>';
      return;
    }

    var typeColors = {
      rating:     ['#eff6ff','#1d4ed8'],
      pass_fail:  ['#f0fdf4','#15803d'],
      target:     ['#fff7ed','#c2410c'],
      attendance: ['#faf5ff','#7e22ce'],
      phone:      ['#fefce8','#a16207'],
      text:       ['#f8fafc','#64748b'],
    };

    var html = criteria.map(function (c, i) {
      var tc    = typeColors[c.criteria_type] || ['#f8fafc','#64748b'];
      var badge = '<span class="badge rounded-pill px-2" style="background:' + tc[0]
                + ';color:' + tc[1] + ';font-size:10px;">' + esc(c.criteria_type) + '</span>';

      var critJson = esc(JSON.stringify(c));

      return '<tr style="font-size:12px;">'
        + '<td style="padding:5px 12px;color:#94a3b8;">' + (i + 1) + '</td>'
        + '<td style="padding:5px 8px;">'
            + esc(c.label)
            + (c.note ? '<br><span style="font-size:10px;color:#94a3b8;">' + esc(c.note) + '</span>' : '')
        + '</td>'
        + '<td class="capital" style="padding:5px 8px;">' + badge + '</td>'
        + '<td class="text-center" style="padding:5px 8px;color:#94a3b8;">' + (c.default_target_day   || '—') + '</td>'
        + '<td class="text-center" style="padding:5px 8px;color:#94a3b8;">' + (c.default_target_month || '—') + '</td>'
        + '<td class="text-center" style="padding:5px 8px;">'              + c.sort_order                    + '</td>'
        + '<td class="text-end" style="padding:5px 8px;white-space:nowrap;">'
            + '<button type="button" class="btn btn-ssm btn-outline-warning edit-crit-btn me-1"'
            +   ' data-crit="' + critJson + '" data-sid="' + sectionId + '">'
            +   '<i class="ti ti-pencil"></i></button>'
            + '<button type="button" class="btn btn-ssm btn-outline-danger del-crit-btn"'
            +   ' data-id="' + c.id + '" data-sid="' + sectionId + '">'
            +   '<i class="ti ti-trash"></i></button>'
        + '</td></tr>';
    }).join('');

    tbody.innerHTML = html;
  }

  // ── Build section block HTML ─────────────────────────────────────
  function buildSectionBlock(id, label, key) {
    return '<div class="d-flex align-items-center gap-2 px-3 py-2"'
        +     ' style="background:var(--color-background-secondary,#f8fafc);">'
        +   '<i class="ti ti-grip-vertical text-muted" style="cursor:grab;font-size:14px;"></i>'
        +   '<div class="flex-grow-1 lh-sm">'
        +     '<span class="fw-semibold small">' + esc(label) + '</span>'
        +     '<code class="ms-2 px-1 rounded" style="font-size:10px;background:#e2e8f0;color:#475569;">' + esc(key) + '</code>'
        +   '</div>'
        +   '<span class="badge bg-light-secondary text-secondary" style="font-size:10px;">order 0</span>'
        +   '<span class="badge bg-light-success text-success" style="font-size:10px;">Active</span>'
        +   '<button type="button" class="btn btn-ssm btn-outline-primary add-criteria-btn"'
        +     ' data-section-id="' + id + '" data-section-label="' + esc(label) + '">'
        +     '<i class="ti ti-plus"></i> Criteria'
        +   '</button>'
        +   '<button type="button" class="btn btn-ssm btn-outline-danger delete-section-btn" data-id="' + id + '">'
        +     '<i class="ti ti-trash"></i>'
        +   '</button>'
        + '</div>'
        + '<div class="table-responsive">'
        +   '<table class="table table-sm align-middle mb-0" style="font-size:12px;" id="crit_tbl_' + id + '">'
        +     '<thead style="background:var(--color-background-secondary,#f8fafc);">'
        +       '<tr>'
        +         '<th style="width:4%;padding:6px 12px;">#</th>'
        +         '<th style="width:32%;padding:6px 8px;">Label</th>'
        +         '<th style="padding:6px 8px;">Type</th>'
        +         '<th class="text-center" style="padding:6px 8px;">Tgt/Day</th>'
        +         '<th class="text-center" style="padding:6px 8px;">Tgt/Month</th>'
        +         '<th style="padding:6px 8px;">Note</th>'
        +         '<th class="text-center" style="padding:6px 8px;">Order</th>'
        +         '<th style="padding:6px 8px;"></th>'
        +       '</tr>'
        +     '</thead>'
        +     '<tbody id="crit_body_' + id + '">'
        +       '<tr><td colspan="9" class="text-center text-muted py-3" style="font-size:12px;">'
        +         'No criteria yet. Click <strong>+ Criteria</strong> to add.'
        +       '</td></tr>'
        +     '</tbody>'
        +   '</table>'
        + '</div>';
  }

  function updateSectionBadge() {
    var badge = document.getElementById('section_count_badge');
    var n     = document.querySelectorAll('.section-block').length;
    if (badge) badge.textContent = n + ' section' + (n !== 1 ? 's' : '');
  }

  // ── Init: load criteria for all existing sections ────────────────
  document.querySelectorAll('.section-block').forEach(function (block) {
    var sid = parseInt(block.id.replace('sec_block_', ''), 10);
    if (sid) loadCriteria(sid);
  });

  // ── Add Section button ───────────────────────────────────────────
  document.getElementById('addSectionBtn').addEventListener('click', function () {
    document.getElementById('sm_section_id').value = '';
    document.getElementById('sm_label').value      = '';
    document.getElementById('sm_key').value        = 'attendance';
    document.getElementById('sm_order').value      = document.querySelectorAll('.section-block').length;
    document.getElementById('sm_active').checked   = true;
    document.getElementById('sectionModalTitle').textContent = 'Add Section';
    getSecModal().show();
  });

  // ── Save Section ─────────────────────────────────────────────────
  document.getElementById('saveSectionBtn').addEventListener('click', function () {
    var label = document.getElementById('sm_label').value.trim();
    var key   = document.getElementById('sm_key').value;
    if (!label) { alert('Section label is required.'); return; }
    if (!key)   { alert('Section key is required.');   return; }

    var btn = this;
    btn.disabled    = true;
    btn.textContent = 'Saving…';

    ajax(BASE + 'evaluations/section_store', {
      template_id:   TEMPLATE_ID,
      section_label: label,
      section_key:   key,
      sort_order:    document.getElementById('sm_order').value,
      is_active:     document.getElementById('sm_active').checked ? 1 : 0,
    }, function (data) {
      btn.disabled  = false;
      btn.innerHTML = '<i class="ti ti-check me-1"></i> Save Section';

      if (!data.ok) { alert(data.message || 'Failed to save section.'); return; }

      getSecModal().hide();

      var noMsg = document.getElementById('noSectionsMsg');
      if (noMsg) noMsg.remove();

      var container = document.getElementById('sectionsContainer');
      var div       = document.createElement('div');
      div.className          = 'section-block';
      div.id                 = 'sec_block_' + data.id;
      div.style.borderBottom = '0.5px solid var(--color-border-tertiary,rgba(0,0,0,.08))';
      div.innerHTML          = buildSectionBlock(data.id, label, key);
      container.appendChild(div);

      updateSectionBadge();
    });
  });

  // ── Delete Section (delegated) ───────────────────────────────────
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.delete-section-btn');
    if (!btn) return;
    if (!confirm('Delete this section and all its criteria? This cannot be undone.')) return;

    var id = btn.dataset.id;

    ajax(BASE + 'evaluations/section_delete/' + id, {}, function (data) {
      if (!data.ok) { alert('Failed to delete section.'); return; }

      var block = document.getElementById('sec_block_' + id);
      if (block) block.remove();

      updateSectionBadge();

      if (!document.querySelectorAll('.section-block').length) {
        var container = document.getElementById('sectionsContainer');
        var msg       = document.createElement('div');
        msg.id        = 'noSectionsMsg';
        msg.className = 'text-center text-muted py-5 small';
        msg.innerHTML = '<i class="ti ti-layout-rows d-block mb-2 opacity-50" style="font-size:32px;"></i>'
          + 'No sections yet.<br>'
          + '<span class="x-small">Click <strong>Add Section</strong> to begin building this template.</span>';
        container.appendChild(msg);
      }
    });
  });

  // ── Open Criteria Modal ──────────────────────────────────────────
  function openCriteriaModal(crit, sectionId) {
    document.getElementById('cm_criteria_id').value  = crit ? crit.id                       : '';
    document.getElementById('cm_section_id').value   = sectionId;
    document.getElementById('cm_label').value        = crit ? (crit.label                || '') : '';
    document.getElementById('cm_type').value         = crit ? (crit.criteria_type         || 'rating') : 'rating';
    document.getElementById('cm_target_day').value   = crit ? (crit.default_target_day    || '') : '';
    document.getElementById('cm_deadline').value     = crit ? (crit.default_deadline      || '') : '';
    document.getElementById('cm_target_month').value = crit ? (crit.default_target_month  || '') : '';
    document.getElementById('cm_note').value         = crit ? (crit.note                  || '') : '';
    document.getElementById('cm_order').value        = crit ? (crit.sort_order            || 0)  : 0;
    document.getElementById('cm_active').checked     = crit ? !!parseInt(crit.is_active, 10) : true;
    document.getElementById('criteriaModalTitle').textContent = crit ? 'Edit Criteria' : 'Add Criteria';
    getCritModal().show();
  }

  // ── Add Criteria (delegated) ─────────────────────────────────────
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.add-criteria-btn');
    if (!btn) return;
    openCriteriaModal(null, parseInt(btn.dataset.sectionId, 10));
  });

  // ── Edit Criteria (delegated) ────────────────────────────────────
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.edit-crit-btn');
    if (!btn) return;
    try {
      var raw  = btn.getAttribute('data-crit')
                    .replace(/&amp;/g,'&')
                    .replace(/&lt;/g,'<')
                    .replace(/&gt;/g,'>')
                    .replace(/&quot;/g,'"');
      var crit = JSON.parse(raw);
      openCriteriaModal(crit, parseInt(btn.dataset.sid, 10));
    } catch (err) {
      console.error('Criteria JSON parse error:', err);
      alert('Could not load criteria data. See console.');
    }
  });

  // ── Save Criteria ────────────────────────────────────────────────
  document.getElementById('saveCriteriaBtn').addEventListener('click', function () {
    var label = document.getElementById('cm_label').value.trim();
    var type  = document.getElementById('cm_type').value;
    var sid   = document.getElementById('cm_section_id').value;
    var cid   = document.getElementById('cm_criteria_id').value;

    if (!label) { alert('Label is required.'); return; }
    if (!sid)   { alert('No section selected. Please close and try again.'); return; }

    var btn = this;
    btn.disabled    = true;
    btn.textContent = 'Saving…';

    var url = cid
      ? BASE + 'evaluations/criteria_update/' + cid
      : BASE + 'evaluations/criteria_store';

    ajax(url, {
      section_id:           sid,
      criteria_type:        type,
      label:                label,
      default_target_day:   document.getElementById('cm_target_day').value,
      default_deadline:     document.getElementById('cm_deadline').value.trim(),
      default_target_month: document.getElementById('cm_target_month').value,
      note:                 document.getElementById('cm_note').value.trim(),
      sort_order:           document.getElementById('cm_order').value,
      is_active:            document.getElementById('cm_active').checked ? 1 : 0,
    }, function (data) {
      btn.disabled  = false;
      btn.innerHTML = '<i class="ti ti-check me-1"></i> Save Criteria';
      if (!data.ok) { alert(data.message || 'Failed to save criteria.'); return; }
      getCritModal().hide();
      loadCriteria(parseInt(sid, 10));
    });
  });

  // ── Delete Criteria (delegated) ──────────────────────────────────
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.del-crit-btn');
    if (!btn) return;
    if (!confirm('Delete this criteria? This cannot be undone.')) return;

    ajax(BASE + 'evaluations/criteria_delete/' + btn.dataset.id, {}, function (data) {
      if (!data.ok) { alert('Failed to delete criteria.'); return; }
      loadCriteria(parseInt(btn.dataset.sid, 10));
    });
  });

})();
</script>

<script>
(function () {
  var KEY_INFO = {
    attendance:   { icon: 'ti-calendar-stats', color: '#7e22ce', bg: '#faf5ff', label: 'Attendance & Punctuality',       desc: 'Tracks leaves, absences, late arrivals, extra hours, and SOD/EOD sign-off. Use attendance-type criteria inside this section.' },
    work_targets: { icon: 'ti-target',          color: '#c2410c', bg: '#fff7ed', label: 'Work Targets',                   desc: 'Numeric daily and monthly targets (e.g., charges entered, ERAs posted, AR follow-ups). Use target-type criteria — each has a target/day and target/month.' },
    perf_metrics: { icon: 'ti-checks',          color: '#15803d', bg: '#f0fdf4', label: 'Individual Performance Metrics', desc: 'Behavioural and qualitative metrics assessed as Pass / Fail (e.g., "Works assigned accounts daily without prompting"). Use pass_fail-type criteria.' },
    ratings:      { icon: 'ti-star',            color: '#1d4ed8', bg: '#eff6ff', label: 'Performance Ratings (1–5)',      desc: 'Scored criteria on a 1–5 scale covering soft skills and professional conduct (e.g., Job Knowledge, Communication, Dependability). Use rating-type criteria.' },
    phone_usage:  { icon: 'ti-device-mobile',   color: '#a16207', bg: '#fefce8', label: 'Mobile Phone Usage',            desc: 'Tracks unnecessary phone usage frequency and average usage per day. Use phone-type criteria.' },
    supervisor:   { icon: 'ti-message-2',       color: '#0f766e', bg: '#f0fdfa', label: 'Supervisor Comments',           desc: 'Free-text fields for employee self-comments and supervisor feedback narrative. Use text-type criteria.' },
    goals:        { icon: 'ti-bulb',            color: '#6366f1', bg: '#f0f4ff', label: 'Goals & Development',           desc: 'Development goals and training needs set for the next review period. Use text-type criteria.' },
    verdict:      { icon: 'ti-clipboard-check', color: '#475569', bg: '#f8fafc', label: 'Overall Verdict & Signatures',  desc: 'Final overall rating (Exceeds / Meets / Needs Improvement / Underperforming) and signature fields for supervisor, employee, and HR. Use text-type criteria.' },
    custom:       { icon: 'ti-puzzle',          color: '#64748b', bg: '#f8fafc', label: 'Custom Section',                desc: 'A blank section with no pre-defined structure. Use any criteria type. Suitable for department-specific or one-off requirements.' },
  };

  function updateKeyHint(key) {
    var hint = document.getElementById('sm_key_hint');
    var info = KEY_INFO[key];
    if (!info) { hint.innerHTML = ''; return; }
    hint.style.background   = info.bg;
    hint.style.borderColor  = info.color + '33';
    hint.innerHTML =
      '<span style="color:' + info.color + ';font-weight:600;">'
      + '<i class="ti ' + info.icon + ' me-1"></i>' + info.label
      + '</span>'
      + '<br><span class="text-muted">' + info.desc + '</span>';
  }

  var keySelect = document.getElementById('sm_key');

  // Update on change
  keySelect.addEventListener('change', function () {
    updateKeyHint(this.value);
  });

  // Also update whenever the modal opens (triggered by addSectionBtn listener
  // and openCriteriaModal — hook into the Bootstrap show event)
  document.getElementById('sectionModal').addEventListener('show.bs.modal', function () {
    updateKeyHint(keySelect.value);
  });
})();
</script>