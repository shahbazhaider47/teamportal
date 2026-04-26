<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

<div class="view-header mb-3">
    <div class="view-icon me-3"><i class="ti ti-forms"></i></div>
        <div class="flex-grow-1">
          <div class="view-title"><?= $page_title ?></div>
        </div>
        
    <div class="ms-auto d-flex gap-2">
        <a href="<?= site_url('evaluations/templates') ?>" class="btn btn-header btn-light-primary">
           <i class="ti ti-arrow-back-up"></i>
        </a>
    </div>
</div>

  <div class="row g-4 align-items-start">

    <!-- ── Left: form ─────────────────────────────────────────────── -->
    <div class="col-12 col-xl-7">
      <div class="solid-card">
        <div class="card-header d-flex align-items-center gap-2 px-1">
          <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:30px;height:30px;background:#e0e7ff;">
            <i class="ti ti-template" style="color:#4f46e5;font-size:15px;"></i>
          </div>
          <span class="fw-semibold small">Template details</span>
        </div>

        <div class="card-body mt-3">
          <form method="post" action="<?= site_url('evaluations/template_create') ?>" class="app-form">

            <!-- Template name -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-uppercase text-muted ls-wide mb-1">
                Template name <span class="text-danger">*</span>
              </label>
              <input type="text" name="name" class="form-control" required
                     placeholder="e.g., AR Monthly Evaluation"
                     value="<?= set_value('name') ?>">
            </div>

            <!-- Team picker -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-uppercase text-muted ls-wide mb-1">
                Team <span class="text-danger">*</span>
              </label>
              <select name="team_id" id="team_select" class="form-select" required>
                <option value="">— Select Team —</option>
                <?php foreach ($teams as $t): ?>
                  <option value="<?= (int) $t['id'] ?>"
                          data-dept="<?= e($t['department_name']) ?>"
                          data-lead="<?= e($t['teamlead_name']) ?>"
                          data-mgr="<?= e($t['manager_name']) ?>">
                    <?= e($t['team_name']) ?>
                    <?php if ($t['department_name']): ?>
                      &mdash; <?= e($t['department_name']) ?>
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <!-- Team info strip — shown after selection -->
            <div id="teamInfoStrip" class="d-none mb-3 p-2 rounded-2 border small"
                 style="background:var(--color-bg-secondary,#f8fafc);">
              <div class="row g-2">
                <div class="col-4">
                  <div class="text-muted x-small">Department</div>
                  <div class="fw-semibold" id="strip_dept">—</div>
                </div>
                <div class="col-4">
                  <div class="text-muted x-small">Team Lead</div>
                  <div class="fw-semibold" id="strip_lead">—</div>
                </div>
                <div class="col-4">
                  <div class="text-muted x-small">Manager</div>
                  <div class="fw-semibold" id="strip_mgr">—</div>
                </div>
              </div>
            </div>
            
            <!-- Review type -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-uppercase text-muted ls-wide mb-1">
                Review type <span class="text-danger">*</span>
              </label>
              <select name="review_type" class="form-select" required>
                <?php foreach (eval_review_types() as $key => $label): ?>
                  <option value="<?= e($key) ?>" <?= set_select('review_type', $key, $key === 'monthly') ?>>
                    <?= e($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-uppercase text-muted ls-wide mb-1">
                Description
              </label>
              <textarea name="description" class="form-control" rows="3"
                        placeholder="Brief description of this template's purpose…"><?= set_value('description') ?></textarea>
            </div>

            <hr class="my-3">

            <!-- Active toggle -->
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="form-check form-switch mb-0">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active"
                       value="1" id="is_active_chk" checked>
                <label class="form-check-label small" for="is_active_chk">
                  Active — available for use in evaluations
                </label>
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary flex-grow-1">
                <i class="ti ti-check me-1"></i> Create &amp; Add Sections
              </button>
              <a href="<?= site_url('evaluations/templates') ?>" class="btn btn-light-primary">
                Cancel
              </a>
            </div>

          </form>
        </div>
      </div>
    </div>

    <!-- ── Right: info panels ──────────────────────────────────────── -->
    <div class="col-12 col-xl-5 d-flex flex-column gap-3">

      <!-- How it works -->
      <div class="solid-card">
        <div class="card-header d-flex align-items-center gap-2 px-1">
          <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:30px;height:30px;background:#dcfce7;">
            <i class="ti ti-circle-check" style="color:#16a34a;font-size:15px;"></i>
          </div>
          <span class="fw-semibold small">How it works</span>
        </div>
        <div class="card-body mt-3">
          <ol class="list-unstyled mb-0 d-flex flex-column gap-3">
            <?php
            $steps = [
                ['Create the template',  'Set name, team, and review type on this form.'],
                ['Add sections',         'On the next page, add sections — attendance, targets, ratings, etc.'],
                ['Add criteria',         'Inside each section, add individual questions, metrics, and targets.'],
                ['Use in evaluations',   'Select this template when starting a new employee evaluation.'],
            ];
            foreach ($steps as $i => [$title, $desc]):
            ?>
              <li class="d-flex align-items-start gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-semibold"
                     style="width:24px;height:24px;background:#6366f1;color:#fff;font-size:11px;margin-top:1px;">
                  <?= $i + 1 ?>
                </div>
                <div>
                  <div class="small fw-semibold"><?= $title ?></div>
                  <div class="x-small text-muted mt-1"><?= $desc ?></div>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        </div>

        <div class="card-header d-flex align-items-center gap-2 px-1 mt-2">
          <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:30px;height:30px;background:#f0f4ff;">
            <i class="ti ti-list-details" style="color:#6366f1;font-size:15px;"></i>
          </div>
          <span class="fw-semibold small">Section key reference</span>
        </div>

          <?php
          $section_keys = [
              'attendance'   => 'Attendance & Punctuality',
              'work_targets' => 'Work Targets',
              'perf_metrics' => 'Individual Performance Metrics',
              'ratings'      => 'Performance Ratings (1–5)',
              'phone_usage'  => 'Mobile Phone Usage',
              'supervisor'   => 'Supervisor Comments',
              'goals'        => 'Goals & Development',
              'verdict'      => 'Overall Verdict & Signatures',
          ];
          ?>
          <div class="d-flex flex-column gap-1 mt-2">
            <?php foreach ($section_keys as $key => $label): ?>
              <div class="d-flex align-items-center gap-2 py-1"
                   style="border-bottom:0.5px solid var(--color-border-tertiary,rgba(0,0,0,.08));">
                <code class="x-small px-2 py-1 rounded"
                      style="background:#f1f5f9;color:#475569;font-size:10px;"><?= $key ?></code>
                <span class="x-small text-muted"><?= $label ?></span>
              </div>
            <?php endforeach; ?>
          </div>
          
      </div>

    </div>
  </div>
</div>

<script>
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
</script>