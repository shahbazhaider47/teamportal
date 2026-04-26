<style>
.app-btn-cancel {
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
    font-family: inherit;
}
.app-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }

.app-btn-submit {
    padding: 7px 20px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    background: #056464;
    color: #ffffff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.15s, box-shadow 0.15s;
    font-family: inherit;
}
.app-btn-submit:hover {
    background: #044848;
    box-shadow: 0 4px 12px rgba(5, 100, 100, 0.25);
}
.app-btn-submit:active { transform: scale(0.98); }
.app-btn-submit i { font-size: 15px; }

.app-btn-submit-danger { background: #dc2626; }
.app-btn-submit-danger:hover { background: #b91c1c; box-shadow: 0 4px 12px rgba(220,38,38,0.25); }


.app-modal .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 24px 64px rgba(15, 23, 42, 0.18), 0 4px 16px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}
.app-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px 16px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    gap: 12px;
}
.app-modal-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.app-modal-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.app-modal-icon-primary  { background: #eff6ff; color: #1d4ed8; }
.app-modal-icon-teal     { background: #f0fdfa; color: #056464; }
.app-modal-icon-success  { background: #f0fdf4; color: #16a34a; }
.app-modal-icon-warning  { background: #fffbeb; color: #d97706; }
.app-modal-icon-danger   { background: #fef2f2; color: #dc2626; }
.app-modal-icon-purple   { background: #f5f3ff; color: #7c3aed; }
.app-modal-icon-slate    { background: #f8fafc; color: #475569;  }

.app-modal-title-wrap {}

.app-modal-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    line-height: 1.3;
    letter-spacing: -0.2px;
}

.app-modal-subtitle {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 1px;
    font-weight: 400;
}

.app-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    cursor: pointer;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
    padding: 0;
    line-height: 1;
}
.app-modal-close:hover { background: #fef2f2; color: #dc2626; }

.app-modal-body {
    padding: 22px 24px;
    background: #ffffff;
}

.app-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.app-modal-footer-left {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    color: #94a3b8;
}
 
.app-form-hint {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
    line-height: 1.4;
}    
</style>
<div class="modal fade app-modal" id="newEvalModal" tabindex="-1"
     aria-labelledby="newEvalModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="post" action="<?= site_url('evaluations/create') ?>" id="newEvalModalForm" class="app-form">
                <input type="hidden" name="step" value="select">

                <!-- Modal Header -->
                <div class="app-modal-header">
                    <div class="app-modal-header-left">
                        <div class="app-modal-icon app-modal-icon-teal">
                            <i class="ti ti-clipboard-plus"></i>
                        </div>
                        <div class="app-modal-title-wrap">
                            <div class="app-modal-title" id="newEvalModalLabel">New Evaluation</div>
                            <div class="app-modal-subtitle">Create a new employee evaluation</div>
                        </div>
                    </div>
                    <button type="button" class="app-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="app-modal-body">

                    <div class="app-form-section" style="margin-bottom:0;">
                        <div class="col-12">

                            <!-- Employee -->
                            <div class="app-form-group">
                                <label class="app-form-label app-form-label-required">
                                    Employee
                                </label>
                                <select name="user_id"
                                        id="modal_eval_user_id"
                                        class="app-form-control js-searchable-select"
                                        required
                                        data-placeholder="Search employee…">
                                    <option value="">Select employee</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?= (int) $u['id'] ?>">
                                            <?= e(($u['emp_id'] ? $u['emp_id'] . ' — ' : '') . $u['firstname'] . ' ' . $u['lastname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="app-form-hint">Choose the employee you want to evaluate.</div>
                            </div>

<!-- Template -->
<div class="app-form-group mt-3">
    <label class="app-form-label form-label-required">
        Evaluation Template
    </label>
    <select name="template_id"
            id="modal_eval_template_id"
            class="form-select"
            required>
        <option value="">Select template</option>
        <?php
        $modal_grouped = [];
        foreach ($templates as $t) {
            $group_key = !empty($t['team_name']) ? $t['team_name'] : 'General';
            $modal_grouped[$group_key][] = $t;
        }
        ksort($modal_grouped);
        foreach ($modal_grouped as $team_name => $items):
        ?>
            <optgroup label="<?= e($team_name) ?>">
                <?php foreach ($items as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"
                            data-team-id="<?= (int) ($t['team_id'] ?? 0) ?>"
                            data-team="<?= e($t['team_name'] ?? '') ?>"
                            data-dept="<?= e($t['department_name'] ?? '') ?>"
                            data-type="<?= e($t['review_type']) ?>">
                        <?= e($t['name']) ?> (<?= ucfirst($t['review_type']) ?>)
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>
    <div id="modal_tpl_hint" class="app-form-hint">Select an employee first to load their templates.</div>
</div>

                            <!-- Template info preview -->
                            <div id="modalTplInfoCard" class="d-none mt-2 mb-1 px-3 py-2 rounded-2"
                                 style="background:#f0fdfa;border:1px solid #99f6e4;">
                                <div class="fw-semibold text-dark mb-1" id="modalTplInfoName" style="font-size:13px;"></div>
                                <div class="d-flex flex-wrap gap-3" style="font-size:11.5px;color:#475569;">
                                    <span><span class="text-muted">Team:</span> <span id="modalTplInfoTeam" class="fw-medium"></span></span>
                                    <span><span class="text-muted">Dept:</span> <span id="modalTplInfoDept" class="fw-medium"></span></span>
                                    <span><span class="text-muted">Type:</span> <span id="modalTplInfoType" class="fw-medium"></span></span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Employee history -->
                    <div id="modalEmpHistoryWrap" class="d-none border-top mt-3">
                        <div class="solid-card mt-3 p-2">
                            <div class="px-1 py-2">
                                <span class="small fw-semibold">
                                    <i class="ti ti-history me-1 text-muted"></i>Previous Evaluations
                                </span>
                            </div>
    
                            <div class="table-responsive" style="max-height:180px;overflow-y:auto;">
                                <table class="table table-sm small mb-0">
                                    <thead class="bg-light-primary">
                                        <tr>
                                            <th>Template Name</th>
                                            <th>Period</th>
                                            <th>Date</th>
                                            <th class="text-center">Rating</th>
                                            <th class="text-center">Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalEmpHistoryBody">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-2">
                                                Loading…
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="app-modal-footer">
                    <button type="button" class="app-btn-cancel" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="app-btn-submit">
                        <i class="ti ti-arrow-right"></i>
                        Continue to Evaluation Form
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
(function () {
  var $tplSel   = document.getElementById('modal_eval_template_id');
  var $userSel  = document.getElementById('modal_eval_user_id');
  var $infoCard = document.getElementById('modalTplInfoCard');
  var $histWrap = document.getElementById('modalEmpHistoryWrap');
  var $histBody = document.getElementById('modalEmpHistoryBody');
  var $tplHint  = document.getElementById('modal_tpl_hint');
  var BASE      = '<?= site_url() ?>';

  if (!$tplSel) return;

  // ── Template info preview ──────────────────────────────────────
  $tplSel.addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    if (!opt.value) { $infoCard.classList.add('d-none'); return; }

    // Strip the "(Monthly)" suffix to show clean template name
    var fullText = opt.text;
    var nameOnly = fullText.replace(/\s*\(\w+\)\s*$/, '').trim();

    document.getElementById('modalTplInfoName').textContent = nameOnly;
    document.getElementById('modalTplInfoTeam').textContent = opt.dataset.team || '—';
    document.getElementById('modalTplInfoDept').textContent = opt.dataset.dept || '—';
    document.getElementById('modalTplInfoType').textContent = opt.dataset.type
      ? opt.dataset.type.charAt(0).toUpperCase() + opt.dataset.type.slice(1) : '—';
    $infoCard.classList.remove('d-none');
  });

  // ── Filter templates by team_id ────────────────────────────────
function filterTemplatesByTeamId(teamId) {
    $tplSel.value = '';
    $infoCard.classList.add('d-none');

    var allOptions = $tplSel.querySelectorAll('option[data-team-id]');
    var allGroups  = $tplSel.querySelectorAll('optgroup');

    if (!teamId) {
      allOptions.forEach(function (opt) { opt.disabled = false; opt.hidden = false; });
      allGroups.forEach(function (grp)  { grp.hidden = false; });
      if ($tplHint) $tplHint.textContent = 'Select which evaluation template to use.';
      return;
    }

    var teamIdStr  = String(teamId);
    var matchCount = 0;
    var lastMatch  = null;

    allOptions.forEach(function (opt) {
      // Use getAttribute to avoid any camelCase conversion ambiguity
      var optTeamId = opt.getAttribute('data-team-id');
      var match     = optTeamId === teamIdStr;
      opt.disabled  = !match;
      opt.hidden    = !match;
      if (match) { matchCount++; lastMatch = opt; }
    });

    allGroups.forEach(function (grp) {
      var hasVisible = Array.from(grp.querySelectorAll('option')).some(function (o) {
        return !o.hidden;
      });
      grp.hidden = !hasVisible;
    });

    if ($tplHint) {
      $tplHint.textContent = matchCount === 0
        ? 'No templates found for this employee\'s team.'
        : matchCount + ' template' + (matchCount > 1 ? 's' : '') + ' available for their team.';
    }

    if (matchCount === 1 && lastMatch) {
      $tplSel.value = lastMatch.value;
      $tplSel.dispatchEvent(new Event('change'));
    }
}
  // ── Employee select ────────────────────────────────────────────
  $userSel.addEventListener('change', function () {
    var uid = this.value;

    if (!uid) {
      filterTemplatesByTeamId(null);
      $histWrap.classList.add('d-none');
      return;
    }

    // Fire both requests in parallel
    var teamPromise = fetch(BASE + 'evaluations/user_team/' + uid, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); });

    var histPromise = fetch(BASE + 'evaluations/employee_history/' + uid, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); });

    // Apply team filter using team_id (not name)
    teamPromise.then(function (data) {
      if (data.ok && data.team && data.team.team_id) {
        filterTemplatesByTeamId(data.team.team_id);
      } else {
        filterTemplatesByTeamId(null); // no team assigned — show all
      }
    }).catch(function () {
      filterTemplatesByTeamId(null);
    });

    // Load history
    $histWrap.classList.remove('d-none');
    $histBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">Loading…</td></tr>';

    histPromise.then(function (data) {
      if (!data.ok || !data.history.length) {
        $histBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">No previous evaluations.</td></tr>';
        return;
      }
      var statusColor = { draft: 'secondary', submitted: 'warning', approved: 'success', rejected: 'danger' };
      $histBody.innerHTML = data.history.map(function (h) {
        var sc  = h.score_ratings ? parseFloat(h.score_ratings).toFixed(1) : '—';
        var st  = h.status || 'draft';
        var col = statusColor[st] || 'secondary';
        return '<tr>'
          + '<td>' + (h.template_name || '—') + '</td>'
          + '<td>' + (h.review_period || '—') + '</td>'
          + '<td>' + (h.review_date ? h.review_date.substring(0, 10) : '—') + '</td>'
          + '<td class="text-center"><span class="badge bg-light-primary text-primary">' + sc + '</span></td>'
          + '<td class="text-center"><span class="badge bg-light-' + col + ' text-' + col + '">'
            + st.charAt(0).toUpperCase() + st.slice(1) + '</span></td>'
          + '<td class="text-end"><a href="' + BASE + 'evaluations/view/' + h.id
            + '" class="btn btn-ssm btn-outline-primary" target="_blank"><i class="ti ti-eye"></i></a></td>'
          + '</tr>';
      }).join('');
    }).catch(function () {
      $histBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-2">Failed to load.</td></tr>';
    });
  });

  // ── Reset on modal close ───────────────────────────────────────
  document.getElementById('newEvalModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newEvalModalForm').reset();
    filterTemplatesByTeamId(null);
    $infoCard.classList.add('d-none');
    $histWrap.classList.add('d-none');
    $histBody.innerHTML = '';
    if ($tplHint) $tplHint.textContent = 'Select an employee first to load their templates.';
  });
})();
</script>