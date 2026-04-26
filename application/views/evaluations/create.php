<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-2">
      <a href="<?= site_url('evaluations') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left"></i>
      </a>
      <h1 class="h6 header-title mb-0"><?= e($page_title) ?></h1>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">

      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white py-2 px-3">
          <h6 class="mb-0"><i class="ti ti-clipboard-plus me-2"></i>Select Employee &amp; Template</h6>
        </div>
        <div class="card-body p-4">

          <form method="post" action="<?= site_url('evaluations/create') ?>" class="app-form" id="startEvalForm">
            <input type="hidden" name="step" value="select">

            <!-- Employee -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Employee <span class="text-danger">*</span>
              </label>
              <select name="user_id" id="eval_user_id"
                      class="form-select js-searchable-select" required
                      data-placeholder="Search employee…">
                <option value="">— Select Employee —</option>
                <?php foreach ($users as $u): ?>
                  <option value="<?= (int) $u['id'] ?>">
                    <?= e(($u['emp_id'] ? $u['emp_id'] . ' — ' : '') . $u['firstname'] . ' ' . $u['lastname']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Select the employee you want to evaluate.</div>
            </div>

            <!-- Template picker — grouped by team -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Evaluation Template <span class="text-danger">*</span>
              </label>
              <select name="template_id" id="eval_template_id"
                      class="form-select" required>
                <option value="">— Select Template —</option>
                <?php
                $grouped = [];
                foreach ($templates as $t) {
                    $group_key = !empty($t['team_name']) ? $t['team_name'] : 'General';
                    $grouped[$group_key][] = $t;
                }
                ksort($grouped);
                foreach ($grouped as $team_name => $items):
                ?>
                  <optgroup label="<?= e($team_name) ?>">
                    <?php foreach ($items as $t): ?>
                      <option value="<?= (int) $t['id'] ?>"
                              data-team="<?= e($t['team_name'] ?? '') ?>"
                              data-dept="<?= e($t['department_name'] ?? '') ?>"
                              data-type="<?= e($t['review_type']) ?>">
                        <?= e($t['name']) ?>
                        (<?= ucfirst($t['review_type']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Template info card — shown after selection -->
            <div id="templateInfoCard" class="alert alert-info small d-none mb-3 p-2">
              <strong id="tplInfoName"></strong><br>
              <span class="text-muted">Team: </span><span id="tplInfoTeam"></span> &nbsp;|&nbsp;
              <span class="text-muted">Dept: </span><span id="tplInfoDept"></span> &nbsp;|&nbsp;
              <span class="text-muted">Type: </span><span id="tplInfoType"></span>
            </div>

            <div class="d-grid mt-4">
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-arrow-right me-1"></i> Continue to Evaluation Form
              </button>
            </div>
          </form>

        </div>
      </div>

      <!-- Recent evaluations for the selected employee (loaded via AJAX) -->
      <div id="empHistoryWrap" class="d-none mt-3">
        <div class="card shadow-sm border-0">
          <div class="card-header py-2 px-3 bg-light-secondary">
            <span class="small fw-semibold">
              <i class="ti ti-history me-1 text-muted"></i>
              Previous Evaluations for this Employee
            </span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm small mb-0">
                <thead class="bg-light-primary">
                  <tr>
                    <th>Template</th>
                    <th>Period</th>
                    <th>Date</th>
                    <th class="text-center">Rating</th>
                    <th class="text-center">Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="empHistoryBody">
                  <tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  var $tplSel   = document.getElementById('eval_template_id');
  var $userSel  = document.getElementById('eval_user_id');
  var $infoCard = document.getElementById('templateInfoCard');
  var $histWrap = document.getElementById('empHistoryWrap');
  var $histBody = document.getElementById('empHistoryBody');
  var BASE      = '<?= site_url() ?>';

  // Template info preview
  $tplSel.addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    if (!opt.value) { $infoCard.classList.add('d-none'); return; }
    document.getElementById('tplInfoName').textContent = opt.text;
    document.getElementById('tplInfoTeam').textContent = opt.dataset.team || '—';
    document.getElementById('tplInfoDept').textContent = opt.dataset.dept || '—';
    document.getElementById('tplInfoType').textContent = opt.dataset.type
      ? opt.dataset.type.charAt(0).toUpperCase() + opt.dataset.type.slice(1)
      : '—';
    $infoCard.classList.remove('d-none');
  });

  // Employee history
  $userSel.addEventListener('change', function () {
    var uid = this.value;
    if (!uid) { $histWrap.classList.add('d-none'); return; }

    $histWrap.classList.remove('d-none');
    $histBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr>';

    fetch(BASE + 'evaluations/employee_history/' + uid, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.ok || !data.history.length) {
        $histBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No previous evaluations.</td></tr>';
        return;
      }
      var statusColor = { draft: 'secondary', submitted: 'warning', approved: 'success', rejected: 'danger' };
      var rows = data.history.map(function (h) {
        var sc  = h.score_ratings ? parseFloat(h.score_ratings).toFixed(1) : '—';
        var st  = h.status || 'draft';
        var col = statusColor[st] || 'secondary';
        return '<tr>'
          + '<td>' + (h.template_name || '—') + '</td>'
          + '<td>' + (h.review_period || '—') + '</td>'
          + '<td>' + (h.review_date ? h.review_date.substring(0, 10) : '—') + '</td>'
          + '<td class="text-center"><span class="badge bg-light-primary text-primary">' + sc + '</span></td>'
          + '<td class="text-center"><span class="badge bg-light-' + col + ' text-' + col + '">' + st.charAt(0).toUpperCase() + st.slice(1) + '</span></td>'
          + '<td class="text-end"><a href="' + BASE + 'evaluations/view/' + h.id + '" class="btn btn-ssm btn-outline-primary"><i class="ti ti-eye"></i></a></td>'
          + '</tr>';
      }).join('');
      $histBody.innerHTML = rows;
    })
    .catch(function () {
      $histBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-2">Failed to load history.</td></tr>';
    });
  });
})();
</script>