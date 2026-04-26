<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">
  <!-- Page Header - Kept as is per requirement -->
  <div class="view-header mb-3">
    <div class="view-btn me-3">
      <a href="<?= site_url('evaluations') ?>" class="btn btn-sm bg-light-primary icon-btn">
        <i class="ti ti-arrow-left"></i>
      </a>
    </div>
    <div class="flex-grow-1">
      <div class="view-title me-2"><?= $page_title ?> <?= eval_status_badge($eval['status']) ?></div>
    </div>
    <div class="ms-auto d-flex gap-2">
      <?php if ($eval['status'] === 'draft'): ?>
        <form method="post" action="<?= site_url('evaluations/submit/' . $eval['id']) ?>" class="d-inline" onsubmit="return confirm('Submit for approval?')">
          <button type="submit" class="btn btn-primary btn-header"><i class="ti ti-send me-1"></i> Submit</button>
        </form>
      <?php endif; ?>
      <?php if ($can_approve && $eval['status'] === 'submitted'): ?>
        <form method="post" action="<?= site_url('evaluations/approve/' . $eval['id']) ?>" class="d-inline" onsubmit="return confirm('Approve this evaluation?')">
          <button type="submit" class="btn btn-success btn-header"><i class="ti ti-circle-check me-1"></i> Approve</button>
        </form>
        <button type="button" class="btn btn-danger btn-header" id="rejectBtn"><i class="ti ti-circle-x me-1"></i> Reject</button>
      <?php endif; ?>
      <div class="btn-divider mt-1"></div>
      <?php if ($can_edit): ?>
        <a href="<?= site_url('evaluations/edit/' . $eval['id']) ?>" class="btn btn-light-primary btn-header"><i class="ti ti-pencil"></i></a>
      <?php endif; ?>
      <?php if ($can_delete): ?>
        <form method="post" action="<?= site_url('evaluations/delete/' . $eval['id']) ?>" class="d-inline" onsubmit="return confirm('Delete this evaluation? This cannot be undone.')">
          <button type="submit" class="btn btn-light-danger btn-header"><i class="ti ti-trash"></i></button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Rejection Alert -->
  <?php if (!empty($eval['rejection_reason'])): ?>
    <div class="alert-modern alert-modern-warning mb-4" role="alert">
      <div class="alert-modern-icon"><i class="ti ti-alert-triangle"></i></div>
      <div class="alert-modern-content"><strong>Rejected:</strong> <?= e($eval['rejection_reason']) ?></div>
    </div>
  <?php endif; ?>

  <div class="eval-layout">
    <div class="eval-main">
      
      <div class="profile-card-modern mb-4">
        <div class="profile-card-bg"></div>
        <div class="profile-card-content">
          <div class="profile-details">
            <h2 class="profile-name"><?= user_profile($user['id']) ?>  <?= e(trim($eval['firstname'] . ' ' . $eval['lastname'])) ?></h2>
            <div class="profile-badges">
              <span class="profile-badge"><i class="ti ti-id"></i> ID: <?= emp_id_display($eval['emp_id'] ?? '—') ?></span>                
              <span class="profile-badge"><i class="ti ti-briefcase"></i> <?= e($eval['position_name'] ?? '—') ?></span>
              <span class="profile-badge"><i class="ti ti-building"></i> <?= e($eval['team_name']) ?></span>
            </div>
            <div class="profile-meta">
              <div class="meta-item"><span class="meta-label">Reviewer</span><span class="meta-value"><?= e(trim(($eval['reviewer_firstname'] ?? '') . ' ' . ($eval['reviewer_lastname'] ?? ''))) ?></span></div>
              <div class="meta-item"><span class="meta-label">Review Period</span><span class="meta-value"><?= e($eval['review_period'] ?: '—') ?></span></div>
              <div class="meta-item"><span class="meta-label">Review Date</span><span class="meta-value"><?= $eval['review_date'] ? date('d M Y', strtotime($eval['review_date'])) : '—' ?></span></div>
              <div class="meta-item"><span class="meta-label">Review Type</span><span class="meta-value"><?= ucfirst($eval['review_type']) ?></span></div>
              <div class="meta-item"><span class="meta-label">Template</span><span class="meta-value"><?= e($eval['template_name'] ?? '—') ?></span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Attendance Section -->
      <div class="section-modern mb-4">
        <div class="section-header-modern"><i class="ti ti-calendar-time"></i> Attendance & Punctuality</div>
        <div class="section-body-modern">
          <div class="stats-grid-modern">
            <div class="stat-card-modern"><div class="stat-number"><?= e($eval['att_working_days'] ?? '—') ?></div><div class="stat-label">Working Days</div></div>
            <div class="stat-card-modern"><div class="stat-number"><?= e($eval['att_days_present'] ?? '—') ?></div><div class="stat-label">Days Present</div></div>
            <div class="stat-card-modern"><div class="stat-number"><?= e($eval['att_days_absent'] ?? '—') ?></div><div class="stat-label">Days Absent</div></div>
            <div class="stat-card-modern"><div class="stat-number"><?= e($eval['att_late_arrivals'] ?? '—') ?></div><div class="stat-label">Late Arrivals</div></div>
            <div class="stat-card-modern"><div class="stat-number"><?= e($eval['att_extra_hours'] ?? '—') ?></div><div class="stat-label">Extra Hours</div></div>
            <div class="stat-card-modern highlight"><div class="stat-number"><?= $eval['att_pct'] !== null ? number_format($eval['att_pct'], 1) . '%' : '—' ?></div><div class="stat-label">Attendance %</div></div>
          </div>
          <?php
          $att_section = null;
          foreach ($sections as $sec) { if ($sec['section_key'] === 'attendance') { $att_section = $sec; break; } }
          if ($att_section && !empty($att_section['criteria'])):
          ?>
          <div class="criteria-table-modern mt-4">
            <?php foreach ($att_section['criteria'] as $c): ?>
            <div class="criteria-row-modern">
              <div class="criteria-label"><?= e($c['label']) ?></div>
              <div class="criteria-options">
                <?php foreach (eval_attendance_options() as $opt): ?>
                  <span class="criteria-option <?= (isset($responses[(int) $c['id']]) && $responses[(int) $c['id']]['selected_option'] === $opt) ? 'selected' : '' ?>"><?= e($opt) ?></span>
                <?php endforeach; ?>
              </div>
              <div class="criteria-comments"><?= e($responses[(int) $c['id']]['comments'] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Work Targets Section -->
      <?php
      $target_section = null;
      foreach ($sections as $sec) { if ($sec['section_key'] === 'work_targets') { $target_section = $sec; break; } }
      if ($target_section && !empty($target_section['criteria'])):
          $target_groups = [];
          foreach ($target_section['criteria'] as $c) { $target_groups[$c['sub_group'] ?? ''][] = $c; }
      ?>
      <div class="section-modern mb-4">
        <div class="section-header-modern"><i class="ti ti-target-arrow"></i> Work Targets</div>
        <div class="section-body-modern">
          <?php foreach ($target_groups as $group_name => $group_criteria): ?>
            <?php if ($group_name): ?><div class="subgroup-header-modern"><?= e($group_name) ?></div><?php endif; ?>
            <div class="target-grid-modern">
              <?php foreach ($group_criteria as $c):
                $r = $responses[(int) $c['id']] ?? [];
                $ach_pct = isset($r['ach_pct']) ? (float) $r['ach_pct'] * 100 : null;
              ?>
              <div class="target-card-modern">
                <div class="target-title"><?= e($c['label']) ?></div>
                <div class="target-details">
                  <div class="target-detail"><span class="detail-label">Target/Day</span><span class="detail-value"><?= e($r['target_day'] ?? '—') ?></span></div>
                  <div class="target-detail"><span class="detail-label">Deadline</span><span class="detail-value"><?= e($r['deadline'] ?? '—') ?></span></div>
                  <div class="target-detail"><span class="detail-label">Target/Month</span><span class="detail-value"><?= e($r['target_month'] ?? '—') ?></span></div>
                  <div class="target-detail"><span class="detail-label">Actual/Month</span><span class="detail-value"><?= e($r['actual_month'] ?? '—') ?></span></div>
                  <div class="target-detail"><span class="detail-label">Achievement</span><span class="detail-value achievement"><?= eval_achievement_badge($ach_pct) ?></span></div>
                  <div class="target-detail"><span class="detail-label">Pass/Fail</span><span class="detail-value"><?= eval_pass_fail_badge($r['target_pass_fail'] ?? null) ?></span></div>
                  <div class="target-comments"><?= e($r['comments'] ?? '') ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Performance Metrics Section -->
      <?php
      $metrics_section = null;
      foreach ($sections as $sec) { if ($sec['section_key'] === 'perf_metrics') { $metrics_section = $sec; break; } }
      if ($metrics_section && !empty($metrics_section['criteria'])):
          $metric_groups = [];
          foreach ($metrics_section['criteria'] as $c) { $metric_groups[$c['sub_group'] ?? ''][] = $c; }
      ?>
      <div class="section-modern mb-4">
        <div class="section-header-modern"><i class="ti ti-chart-line"></i> Performance Metrics</div>
        <div class="section-body-modern">
          <?php foreach ($metric_groups as $group_name => $group_criteria): ?>
            <?php if ($group_name): ?><div class="subgroup-header-modern"><?= e($group_name) ?></div><?php endif; ?>
            <?php foreach ($group_criteria as $i => $c):
              $r = $responses[(int) $c['id']] ?? [];
            ?>
            <div class="metric-row-modern">
              <div class="metric-number"><?= $i + 1 ?></div>
              <div class="metric-label"><?= e($c['label']) ?></div>
              <div class="metric-result"><?= eval_pass_fail_badge($r['pass_fail'] ?? null) ?></div>
              <div class="metric-comments"><?= e($r['comments'] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
            <div class="metric-summary-modern">
              <span>Pass Count:</span>
              <?php
              $pass_n = 0; $total_n = 0;
              foreach ($group_criteria as $c) {
                  $pf = $responses[(int) $c['id']]['pass_fail'] ?? null;
                  if ($pf === 'pass') $pass_n++;
                  if (in_array($pf, ['pass','fail'], true)) $total_n++;
              }
              ?>
              <strong><?= $pass_n ?> / <?= count($group_criteria) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Ratings Section -->
      <?php
      $ratings_section = null;
      foreach ($sections as $sec) { if ($sec['section_key'] === 'ratings') { $ratings_section = $sec; break; } }
      if ($ratings_section && !empty($ratings_section['criteria'])):
      ?>
      <div class="section-modern mb-4">
        <div class="section-header-modern"><i class="ti ti-star-filled"></i> Performance Ratings</div>
        <div class="section-body-modern">
          <?php foreach ($ratings_section['criteria'] as $c):
            $r = $responses[(int) $c['id']] ?? [];
            $score = isset($r['score']) ? (int) $r['score'] : null;
          ?>
          <div class="rating-row-modern">
            <div class="rating-label"><?= e($c['label']) ?><?php if ($c['note']): ?><small><?= e($c['note']) ?></small><?php endif; ?></div>
            <div class="rating-stars">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="ti ti-star <?= $score && $score >= $s ? 'filled' : '' ?>"></i>
              <?php endfor; ?>
            </div>
            <div class="rating-score"><?= $score ? eval_score_badge((float) $score) : '—' ?></div>
            <div class="rating-comments"><?= e($r['comments'] ?? '') ?></div>
          </div>
          <?php endforeach; ?>
          <?php if ($eval['score_ratings']): ?>
            <div class="rating-total-modern">Overall Rating: <?= eval_score_badge((float) $eval['score_ratings']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Phone Usage Section -->
      <?php
      $phone_section = null;
      foreach ($sections as $sec) { if ($sec['section_key'] === 'phone_usage') { $phone_section = $sec; break; } }
      if ($phone_section && !empty($phone_section['criteria'])):
      ?>
      <div class="section-modern mb-4">
        <div class="section-header-modern"><i class="ti ti-device-mobile"></i> Mobile Phone Usage</div>
        <div class="section-body-modern">
          <?php foreach ($phone_section['criteria'] as $c):
            $r = $responses[(int) $c['id']] ?? [];
          ?>
          <div class="criteria-row-modern">
            <div class="criteria-label"><?= e($c['label']) ?></div>
            <div class="criteria-options">
              <?php foreach (eval_phone_usage_options() as $opt): ?>
                <span class="criteria-option <?= (($r['selected_option'] ?? '') === $opt) ? 'selected' : '' ?>"><?= e($opt) ?></span>
              <?php endforeach; ?>
            </div>
            <div class="criteria-comments"><?= e($r['comments'] ?? '') ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT COLUMN - Sidebar -->
    <div class="eval-sidebar">
      
      <!-- Score Summary Widget -->
      <div class="widget-modern widget-score mb-4">
        <div class="widget-header-modern"><i class="ti ti-chart-pie"></i> Score Summary</div>
        <div class="widget-body-modern">
          <div class="score-item"><span>Attendance Score</span><span><?= eval_score_badge($eval['score_attendance'] ? (float) $eval['score_attendance'] : null) ?></span></div>
          <div class="score-item"><span>Target Achievement</span><span><?= eval_achievement_badge($eval['score_targets'] !== null ? (float) $eval['score_targets'] : null) ?></span></div>
          <div class="score-item"><span>Performance Metrics</span><span><?= $eval['score_perf_metrics'] ? '<span class="badge-modern badge-primary">' . e($eval['score_perf_metrics']) . '</span>' : '—' ?></span></div>
          <div class="score-divider"></div>
          <div class="score-item total"><span>Average Rating</span><span><?= eval_score_badge($eval['score_ratings'] !== null ? (float) $eval['score_ratings'] : null) ?></span></div>
        </div>
      </div>

      <!-- Verdict Widget -->
      <?php if ($eval['overall_verdict']): ?>
      <div class="widget-modern widget-verdict mb-4">
        <div class="widget-header-modern"><i class="ti ti-award"></i> Overall Verdict</div>
        <div class="widget-body-modern text-center">
          <div class="verdict-badge-modern"><?= e($eval['overall_verdict']) ?></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Comments Widget -->
      <?php if ($eval['employee_comments'] || $eval['supervisor_comments']): ?>
      <div class="widget-modern mb-4">
        <div class="widget-header-modern"><i class="ti ti-message-2"></i> Comments</div>
        <div class="widget-body-modern">
          <?php if ($eval['employee_comments']): ?>
            <div class="comment-block"><div class="comment-author">Employee</div><div class="comment-text"><?= nl2br(e($eval['employee_comments'])) ?></div></div>
          <?php endif; ?>
          <?php if ($eval['supervisor_comments']): ?>
            <div class="comment-block"><div class="comment-author">Supervisor</div><div class="comment-text"><?= nl2br(e($eval['supervisor_comments'])) ?></div></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Goals Widget -->
      <?php if (!empty($goals)): ?>
      <div class="widget-modern mb-4">
        <div class="widget-header-modern"><i class="ti ti-flag"></i> Goals & Development</div>
        <div class="widget-body-modern">
          <?php foreach ($goals as $i => $g): ?>
            <div class="goal-item-modern">
              <?php if ($g['goal']): ?><div class="goal-title">Goal <?= $i + 1 ?></div><div class="goal-text"><?= e($g['goal']) ?></div><?php endif; ?>
              <?php if ($g['training_need']): ?><div class="training-need">Training: <?= e($g['training_need']) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Signatures Widget -->
      <?php if ($eval['sig_supervisor'] || $eval['sig_employee'] || $eval['sig_hr']): ?>
      <div class="widget-modern mb-4">
        <div class="widget-header-modern"><i class="ti ti-pen"></i> Signatures</div>
        <div class="widget-body-modern">
          <?php
          $sig_map = [
              'Supervisor' => ['sig_supervisor', 'sig_supervisor_date'],
              'Employee' => ['sig_employee', 'sig_employee_date'],
              'HR / Authorized' => ['sig_hr', 'sig_hr_date'],
          ];
          foreach ($sig_map as $label => [$name, $date]):
              if (empty($eval[$name])) continue;
          ?>
            <div class="signature-row"><span class="sig-label"><?= $label ?>:</span><span class="sig-name"><?= e($eval[$name]) ?></span><?php if (!empty($eval[$date])): ?><span class="sig-date"><?= date('d M Y', strtotime($eval[$date])) ?></span><?php endif; ?></div>
          <?php endforeach; ?>
          <?php if (!empty($eval['approved_by'])): ?>
            <div class="signature-row approved">Approved by: <strong><?= e(trim(($eval['approver_firstname'] ?? '') . ' ' . ($eval['approver_lastname'] ?? ''))) ?></strong><?php if (!empty($eval['approved_at'])): ?> on <?= date('d M Y', strtotime($eval['approved_at'])) ?><?php endif; ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= site_url('evaluations/reject/' . $eval['id']) ?>">
      <div class="modal-content-modern">
        <div class="modal-header-modern">
          <h5><i class="ti ti-circle-x"></i> Reject Evaluation</h5>
          <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body-modern">
          <p class="modal-desc">This evaluation will be returned to draft for revision.</p>
          <label class="form-label-modern">Rejection Reason <span class="required">*</span></label>
          <textarea name="rejection_reason" class="form-control-modern" rows="3" required placeholder="What needs to be corrected?"></textarea>
        </div>
        <div class="modal-footer-modern">
          <button type="button" class="btn-outline-modern" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-danger-modern">Reject</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var btn = document.getElementById('rejectBtn');
  if (btn) {
    btn.addEventListener('click', function () {
      new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });
  }
})();
</script>

<!-- Complete New Styles -->
<style>
/* Modern Reset & Base */
.eval-layout { display: flex; gap: 28px; max-width: 1600px; margin: 0 auto; }
.eval-main { flex: 2; min-width: 0; }
.eval-sidebar { flex: 1; min-width: 280px; }

/* Profile Card Modern */
.profile-card-modern { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 24px; overflow: hidden; position: relative; color: #fff; }
.profile-card-bg { position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%); border-radius: 50%; }
.profile-card-content { position: relative; padding: 28px; display: flex; gap: 24px; flex-wrap: wrap; }
.profile-avatar { width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #3b82f6; }
.profile-details { flex: 1; }
.profile-name { font-size: 20px; font-weight: 600; margin: 5px 0 12px 0; letter-spacing: -0.3px; color:#fff;}
.profile-badges { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
.profile-badge { background: rgba(255,255,255,0.12); padding: 4px 12px; border-radius: 40px; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
.profile-meta { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; background: rgba(0,0,0,0.25); padding: 16px; border-radius: 16px; margin-top: 8px; }
.meta-item { display: flex; flex-direction: column; }
.meta-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.6; margin-bottom: 2px; }
.meta-value { font-size: 14px; font-weight: 500; }

/* Section Modern */
.section-modern { background: #fff; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; border: 1px solid #eef2f6; transition: all 0.2s ease; }
.section-header-modern { padding: 16px 24px; background: #fafbfc; border-bottom: 1px solid #eef2f6; font-weight: 700; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 10px; }
.section-header-modern i { font-size: 20px; color: #3b82f6; }
.section-body-modern { padding: 20px 24px; }

/* Stats Grid */
.stats-grid-modern { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 16px; }
.stat-card-modern { text-align: center; padding: 12px; background: #f8fafc; border-radius: 16px; transition: all 0.2s; }
.stat-card-modern.highlight { background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); }
.stat-number { font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 4px; }
.stat-label { font-size: 12px; color: #64748b; font-weight: 500; }

/* Criteria Row */
.criteria-row-modern { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid #f0f2f5; }
.criteria-row-modern:last-child { border-bottom: none; }
.criteria-label { width: 220px; font-weight: 600; color: #334155; font-size: 14px; }
.criteria-options { display: flex; gap: 12px; flex: 1; }
.criteria-option { font-size: 12px; padding: 4px 12px; background: #f1f5f9; border-radius: 30px; color: #475569; transition: all 0.2s; }
.criteria-option.selected { background: #3b82f6; color: white; }
.criteria-comments { width: 200px; font-size: 13px; color: #64748b; font-style: italic; }

/* Target Grid */
.target-grid-modern { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 16px; }
.target-card-modern { background: #f8fafc; border-radius: 16px; padding: 16px; border: 1px solid #eef2f6; transition: all 0.2s; }
.target-card-modern:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
.target-title { font-weight: 700; margin-bottom: 12px; font-size: 15px; color: #1e293b; }
.target-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; }
.target-detail { display: flex; justify-content: space-between; align-items: baseline; }
.detail-label { color: #64748b; font-size: 11px; }
.detail-value { font-weight: 500; color: #334155; }
.detail-value.achievement span { font-weight: 600; }
.target-comments { grid-column: span 2; margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2e8f0; font-size: 12px; color: #64748b; }

/* Metric Row */
.metric-row-modern { display: flex; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid #f0f2f5; }
.metric-number { width: 32px; height: 32px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #475569; }
.metric-label { flex: 2; font-weight: 500; color: #334155; }
.metric-result { width: 80px; text-align: center; }
.metric-comments { flex: 2; font-size: 13px; color: #64748b; }
.metric-summary-modern { margin-top: 16px; padding-top: 12px; border-top: 1px solid #eef2f6; text-align: right; font-size: 14px; }

/* Rating Row */
.rating-row-modern { display: flex; align-items: center; gap: 20px; padding: 14px 0; border-bottom: 1px solid #f0f2f5; flex-wrap: wrap; }
.rating-label { width: 200px; font-weight: 600; color: #334155; font-size: 14px; }
.rating-label small { display: block; font-size: 11px; font-weight: normal; color: #64748b; margin-top: 2px; }
.rating-stars { display: flex; gap: 4px; }
.rating-stars i { font-size: 18px; color: #cbd5e1; }
.rating-stars i.filled { color: #f59e0b; }
.rating-score { width: 70px; }
.rating-comments { flex: 1; font-size: 13px; color: #64748b; }
.rating-total-modern { margin-top: 16px; text-align: right; font-weight: 700; font-size: 16px; padding-top: 12px; border-top: 2px solid #eef2f6; }

/* Widget Modern */
.widget-modern { background: #fff; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid #eef2f6; overflow: hidden; }
.widget-header-modern { padding: 14px 20px; background: #fafbfc; border-bottom: 1px solid #eef2f6; font-weight: 700; font-size: 14px; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.widget-header-modern i { font-size: 18px; color: #3b82f6; }
.widget-body-modern { padding: 16px 20px; }
.widget-score .score-item { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
.widget-score .score-item.total { font-weight: 700; font-size: 15px; }
.score-divider { height: 1px; background: #eef2f6; margin: 12px 0; }
.verdict-badge-modern { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 10px 24px; border-radius: 60px; font-weight: 700; display: inline-block; font-size: 16px; }
.comment-block { margin-bottom: 16px; }
.comment-block:last-child { margin-bottom: 0; }
.comment-author { font-weight: 700; font-size: 13px; color: #3b82f6; margin-bottom: 6px; }
.comment-text { font-size: 13px; color: #475569; line-height: 1.5; }
.goal-item-modern { margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f2f5; }
.goal-item-modern:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.goal-title { font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px; }
.goal-text { font-size: 13px; color: #475569; margin-bottom: 8px; }
.training-need { font-size: 12px; color: #3b82f6; background: #eff6ff; padding: 4px 10px; border-radius: 20px; display: inline-block; }
.signature-row { display: flex; flex-wrap: wrap; align-items: baseline; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f0f2f5; font-size: 13px; }
.signature-row:last-child { border-bottom: none; }
.sig-label { font-weight: 600; color: #64748b; width: 100px; }
.sig-name { font-weight: 500; color: #1e293b; }
.sig-date { font-size: 11px; color: #94a3b8; }
.signature-row.approved { background: #f0fdf4; margin-top: 8px; padding: 10px; border-radius: 12px; }

/* Subgroup Header */
.subgroup-header-modern { margin: 16px 0 12px 0; padding-left: 12px; border-left: 4px solid #3b82f6; font-weight: 600; font-size: 14px; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }

/* Alert Modern */
.alert-modern { display: flex; align-items: center; gap: 14px; padding: 14px 20px; border-radius: 16px; margin-bottom: 24px; }
.alert-modern-warning { background: #fffbeb; border: 1px solid #fde68a; }
.alert-modern-icon { font-size: 22px; color: #f59e0b; }
.alert-modern-content { font-size: 14px; color: #92400e; }

/* Badge Modern */
.badge-modern { display: inline-block; padding: 4px 12px; border-radius: 40px; font-size: 12px; font-weight: 600; }
.badge-primary { background: #eef2ff; color: #3b82f6; }

/* Modal Modern */
.modal-content-modern { background: #fff; border-radius: 28px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
.modal-header-modern { padding: 20px 24px; background: #fef2f2; display: flex; justify-content: space-between; align-items: center; }
.modal-header-modern h5 { margin: 0; font-size: 18px; font-weight: 700; color: #991b1b; display: flex; align-items: center; gap: 10px; }
.modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #991b1b; }
.modal-body-modern { padding: 24px; }
.modal-desc { font-size: 14px; color: #64748b; margin-bottom: 20px; }
.form-label-modern { font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block; color: #334155; }
.required { color: #ef4444; }
.form-control-modern { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 14px; font-size: 14px; transition: all 0.2s; }
.form-control-modern:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.modal-footer-modern { padding: 16px 24px; background: #fafbfc; display: flex; justify-content: flex-end; gap: 12px; }
.btn-outline-modern { padding: 8px 20px; border-radius: 40px; border: 1px solid #cbd5e1; background: white; font-weight: 500; cursor: pointer; transition: all 0.2s; }
.btn-outline-modern:hover { background: #f8fafc; }
.btn-danger-modern { padding: 8px 24px; border-radius: 40px; background: #dc2626; border: none; color: white; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-danger-modern:hover { background: #b91c1c; }

/* Responsive */
@media (max-width: 992px) {
  .eval-layout { flex-direction: column; }
  .eval-sidebar { flex: auto; }
  .criteria-label, .rating-label { width: 100%; }
  .criteria-row-modern, .rating-row-modern { flex-direction: column; align-items: flex-start; }
  .profile-card-content { flex-direction: column; align-items: center; text-align: center; }
  .profile-meta { grid-template-columns: 1fr; }
  .profile-badges { justify-content: center; }
  .stats-grid-modern { grid-template-columns: repeat(3, 1fr); }
}
</style>