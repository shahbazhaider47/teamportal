<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Shared by:
 *   evaluations/fill/$template_id/$user_id   (new)
 *   evaluations/edit/$id                      (edit)
 */
$is_edit     = !empty($eval);
$eval_id     = $is_edit ? (int)$eval['id'] : 0;
$form_action = $is_edit
    ? site_url('evaluations/edit/' . $eval_id)
    : site_url('evaluations/fill/' . $template['id'] . '/' . $user['id']);

function _r(array $responses, int $cid, string $field, $default = '')
{
    return isset($responses[$cid][$field]) && $responses[$cid][$field] !== null
        ? $responses[$cid][$field]
        : $default;
}
?>

<div class="container-fluid">

  <!-- ── Header ─────────────────────────────────────────────────── -->
  <div class="view-header mb-3">
    <a href="<?= $is_edit ? site_url('evaluations/view/' . $eval_id) : site_url('evaluations') ?>">
      <div class="view-icon me-3"><i class="ti ti-arrow-left"></i></div>
    </a>
    <div class="flex-grow-1">
      <div class="view-title">
        <?= $page_title ?>
      <?php if ($is_edit): ?>
        <?= eval_status_badge($eval['status']) ?>
      <?php endif; ?>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2">
      <button type="submit" form="evalFillForm" name="action" value="draft"
              class="btn btn-light-primary btn-header">
        <i class="ti ti-device-floppy me-1"></i> Save Draft
      </button>
      <div class="btn-divider mt-1"></div>
      <button type="submit" form="evalFillForm" name="action" value="submit"
              class="btn btn-primary btn-header"
              onclick="return confirm('Submit this evaluation for approval?')">
        <i class="ti ti-send me-1"></i> Submit for Approval
      </button>
    </div>
  </div>

  <form method="post" action="<?= $form_action ?>" id="evalFillForm" class="app-form">
    <div class="row g-3">

      <!-- ════════════════════════════════════════════════════════════
           LEFT COLUMN — all sections
           ════════════════════════════════════════════════════════════ -->
      <div class="col-12 col-xl-8">

        <!-- ── Employee Info Card ────────────────────────────────── -->
        <div class="card shadow-sm mb-3 border-0" style="border-radius:14px;overflow:hidden;">

          <div style="background:linear-gradient(135deg,#056464 0%,#0a8a8a 100%);padding:20px 24px;">
            <div class="d-flex align-items-center gap-3">

              <div style="width:54px;height:54px;border-radius:50%;
                          background:rgba(255,255,255,0.2);
                          display:flex;align-items:center;justify-content:center;
                          font-size:22px;font-weight:700;color:#fff;flex-shrink:0;
                          border:2px solid rgba(255,255,255,0.35);">
                <?= strtoupper(substr($user['firstname'] ?? '?', 0, 1)) ?>
              </div>

              <div class="flex-grow-1 min-w-0">
                <div style="font-size:17px;font-weight:700;color:#fff;line-height:1.2;">
                  <?= e(trim($user['firstname'] . ' ' . $user['lastname'])) ?>
                </div>
                <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:3px;">
                  <?= e($user['emp_title'] ?? 'No Title') ?>
                </div>
              </div>

              <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                <span style="background:rgba(255,255,255,0.18);color:#fff;font-size:11px;
                             font-weight:600;padding:3px 10px;border-radius:20px;
                             border:1px solid rgba(255,255,255,0.3);">
                  <?= e(emp_id_display($user['emp_id'] ?? '')) ?>
                </span>
                <span style="background:rgba(255,255,255,0.18);color:#fff;font-size:11px;
                             font-weight:600;padding:3px 10px;border-radius:20px;
                             border:1px solid rgba(255,255,255,0.3);">
                  <i class="ti ti-users me-1" style="font-size:10px;"></i>
                  <?= e($template['team_name'] ?? '—') ?>
                </span>
              </div>
            </div>

            <div style="border-top:1px solid rgba(255,255,255,0.15);margin:14px 0 12px;"></div>

            <div class="d-flex flex-wrap gap-4" style="font-size:12px;color:rgba(255,255,255,0.8);">
              <span><i class="ti ti-building me-1"></i><?= e($template['department_name'] ?? '—') ?></span>
              <span><i class="ti ti-template me-1"></i><?= e($template['name'] ?? '—') ?></span>
              <span><i class="ti ti-refresh me-1"></i><?= e(ucfirst($template['review_type'])) ?> Review</span>
              <?php if (!empty($template['manager_name'])): ?>
                <span><?= user_profile_image($template['manager_name']) ?></span>
              <?php endif; ?>
              <?php if (!empty($template['teamlead_name'])): ?>
                <span><?= user_profile_image($template['teamlead_name']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="card-body px-4 py-3" style="background:#fff;">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;color:#475569;">
                  Review Period <span class="text-danger">*</span>
                </label>
                <select name="review_period" class="form-select form-select-sm" required>
                  <option value="">— Select Period —</option>
                  <?php
                  $current_period = $is_edit ? ($eval['review_period'] ?? '') : '';
                  foreach (eval_review_periods($template['review_type']) as $period):
                  ?>
                    <option value="<?= e($period) ?>"
                            <?= $current_period === $period ? 'selected' : '' ?>>
                      <?= e($period) ?>
                    </option>
                  <?php endforeach; ?>
                  <?php if ($current_period && !in_array($current_period, eval_review_periods($template['review_type']), true)): ?>
                    <option value="<?= e($current_period) ?>" selected><?= e($current_period) ?></option>
                  <?php endif; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;color:#475569;">
                  Review Date <span class="text-danger">*</span>
                </label>
                <input type="date" name="review_date"
                       class="form-control form-control-sm" required
                       value="<?= e($is_edit ? ($eval['review_date'] ?? date('Y-m-d')) : date('Y-m-d')) ?>">
              </div>
            </div>
          </div>
        </div>
        <!-- /Employee Info Card -->

        <!-- ── Attendance Card (always special — has numeric fields) ── -->
        <div class="solid-card mb-3">
          <div class="card-header py-2 px-2 mb-4">
            <h6 class="mb-0 text-primary">
              <i class="ti ti-calendar me-2"></i>Attendance &amp; Punctuality
            </h6>
          </div>
          <div class="card-body">

            <!-- Numeric attendance fields -->
            <div class="row g-3 mb-3 pb-3 border-bottom">
              <?php
              $att_fields = [
                  ['att_working_days',  'Working Days'],
                  ['att_days_present',  'Days Present'],
                  ['att_days_absent',   'Days Absent'],
                  ['att_late_arrivals', 'Late Arrivals'],
                  ['att_extra_hours',   'Extra Hours', 'step' => '0.5'],
              ];
              foreach ($att_fields as $af):
                $val  = $is_edit ? ($eval[$af[0]] ?? '') : '';
                $step = isset($af['step']) ? 'step="' . $af['step'] . '"' : '';
              ?>
                <div class="col-6 col-md-2">
                  <label class="form-label small"><?= $af[1] ?></label>
                  <input type="number" name="<?= $af[0] ?>"
                         class="form-control form-control-sm text-center"
                         value="<?= e($val) ?>" min="0" <?= $step ?>>
                </div>
              <?php endforeach; ?>
              <div class="col-6 col-md-2">
                <label class="form-label small">Attendance %</label>
                <input type="text" id="att_pct_display"
                       class="form-control form-control-sm text-center bg-light-primary fw-bold"
                       value="<?= ($is_edit && !empty($eval['att_pct']))
                                   ? number_format($eval['att_pct'], 1) . '%' : '' ?>"
                       readonly>
              </div>
            </div>

            <!-- Attendance section criteria (if template has one keyed 'attendance') -->
            <?php
            $att_section = null;
            foreach ($sections as $sec) {
                if ($sec['section_key'] === 'attendance') { $att_section = $sec; break; }
            }
            if ($att_section && !empty($att_section['criteria'])):
            ?>
            <div class="table-responsive">
              <table class="table table-sm small align-middle mb-0">
                <thead class="bg-light-primary">
                  <tr>
                    <th style="width:35%">Criteria</th>
                    <?php foreach (eval_attendance_options() as $opt): ?>
                      <th class="text-center"><?= e($opt) ?></th>
                    <?php endforeach; ?>
                    <th>Comments</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($att_section['criteria'] as $c):
                    $cid = (int)$c['id'];
                  ?>
                  <tr>
                    <td><?= e($c['label']) ?></td>
                    <?php foreach (eval_attendance_options() as $opt): ?>
                      <td class="text-center">
                        <input type="radio"
                               name="responses[<?= $cid ?>][selected_option]"
                               value="<?= e($opt) ?>"
                               class="form-check-input"
                               <?= _r($responses, $cid, 'selected_option') === $opt ? 'checked' : '' ?>>
                      </td>
                    <?php endforeach; ?>
                    <td>
                      <input type="text" class="form-control form-control-sm"
                             name="responses[<?= $cid ?>][comments]"
                             value="<?= e(_r($responses, $cid, 'comments')) ?>"
                             placeholder="Optional">
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <!-- /Attendance Card -->

        <!-- ── All other sections — generic loop ─────────────────── -->
        <?php
        $skip_keys = ['attendance']; // already rendered above

        $sec_icons = [
            'work_targets'     => 'ti ti-target',
            'perf_metrics'     => 'ti ti-checklist',
            'ratings'          => 'ti ti-star',
            'phone_usage'      => 'ti ti-device-mobile',
            'task_completion'  => 'ti ti-checklist',
            'ticket_handling'  => 'ti ti-ticket',
            'technical_skills' => 'ti ti-tools',
            'response_time'    => 'ti ti-clock',
            'communication'    => 'ti ti-messages',
            'equipment_mgmt'   => 'ti ti-device-laptop',
            'initiative'       => 'ti ti-bulb',
        ];

        foreach ($sections as $sec):
            if (in_array($sec['section_key'], $skip_keys, true)) continue;
            if (empty($sec['criteria'])) continue;

            $sec_icon = $sec_icons[$sec['section_key']] ?? 'ti ti-layout-list';

            // Determine which criteria types appear in this section
            $types        = array_unique(array_column($sec['criteria'], 'criteria_type'));
            $has_target   = in_array('target',    $types, true);
            $has_passfail = in_array('pass_fail',  $types, true);
            $has_rating   = in_array('rating',     $types, true);
            $has_text     = in_array('text',       $types, true);
            $has_phone    = in_array('phone',      $types, true);
            $has_att_type = in_array('attendance', $types, true);
        ?>
        <div class="solid-card mb-3">
          <div class="card-header py-2 px-2 mb-4
                      d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary">
              <i class="<?= $sec_icon ?> me-2"></i>
              <?= html_escape($sec['section_label']) ?>
            </h6>
            <?php if ($has_rating): ?>
              <small class="text-light">
                1 = Poor &nbsp;·&nbsp; 2 = Fair &nbsp;·&nbsp;
                3 = Satisfactory &nbsp;·&nbsp; 4 = Good &nbsp;·&nbsp; 5 = Excellent
              </small>
            <?php endif; ?>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm small align-middle mb-0">

                <!-- Column headers -->
                <thead class="bg-light-primary">
                  <tr>
                    <th style="width:35%">Criteria</th>

                    <?php if ($has_target): ?>
                      <th class="text-center">Target / Day</th>
                      <th class="text-center">Deadline</th>
                      <th class="text-center">Target / Month</th>
                      <th class="text-center">Actual / Month</th>
                      <th class="text-center">Ach %</th>
                      <th class="text-center">Pass / Fail</th>
                    <?php endif; ?>

                    <?php if ($has_rating): ?>
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                        <th class="text-center"><?= $s ?></th>
                      <?php endfor; ?>
                      <th class="text-center">Score</th>
                    <?php endif; ?>

                    <?php if ($has_passfail && !$has_target): ?>
                      <th class="text-center">Pass</th>
                      <th class="text-center">Fail</th>
                      <th class="text-center">N/A</th>
                    <?php endif; ?>

                    <?php if ($has_phone || $has_att_type): ?>
                      <?php foreach (eval_phone_usage_options() as $opt): ?>
                        <th class="text-center"><?= html_escape($opt) ?></th>
                      <?php endforeach; ?>
                    <?php endif; ?>

                    <th>Comments</th>
                  </tr>
                </thead>

                <!-- Rows -->
                <tbody>
                <?php foreach ($sec['criteria'] as $i => $c):
                  $cid  = (int)$c['id'];
                  $type = $c['criteria_type'];
                ?>
                  <tr>
                    <td>
                      <?= html_escape($c['label']) ?>
                      <?php if (!empty($c['note'])): ?>
                        <small class="text-muted d-block"><?= html_escape($c['note']) ?></small>
                      <?php endif; ?>
                    </td>

                    <?php if ($type === 'target'): ?>
                      <td>
                        <input type="number" step="0.01" min="0"
                               name="responses[<?= $cid ?>][target_day]"
                               class="form-control form-control-sm text-center"
                               value="<?= e(_r($responses, $cid, 'target_day', $c['default_target_day'] ?? '')) ?>"
                               style="min-width:70px">
                      </td>
                      <td>
                        <input type="text"
                               name="responses[<?= $cid ?>][deadline]"
                               class="form-control form-control-sm text-center"
                               value="<?= e(_r($responses, $cid, 'deadline', $c['default_deadline'] ?? '')) ?>"
                               style="min-width:70px">
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0"
                               name="responses[<?= $cid ?>][target_month]"
                               class="form-control form-control-sm text-center tgt-month"
                               data-cid="<?= $cid ?>"
                               value="<?= e(_r($responses, $cid, 'target_month', $c['default_target_month'] ?? '')) ?>"
                               style="min-width:80px">
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0"
                               name="responses[<?= $cid ?>][actual_month]"
                               class="form-control form-control-sm text-center act-month"
                               data-cid="<?= $cid ?>"
                               value="<?= e(_r($responses, $cid, 'actual_month')) ?>"
                               style="min-width:80px">
                      </td>
                      <td class="text-center">
                        <span id="ach_pct_<?= $cid ?>" class="badge bg-light-secondary text-secondary">
                          <?php $ach = _r($responses, $cid, 'ach_pct');
                          echo $ach !== '' ? number_format((float)$ach * 100, 1) . '%' : '—'; ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <select name="responses[<?= $cid ?>][target_pass_fail]"
                                class="form-select form-select-sm" style="min-width:80px">
                          <option value="">—</option>
                          <option value="pass" <?= _r($responses, $cid, 'target_pass_fail') === 'pass' ? 'selected' : '' ?>>Pass</option>
                          <option value="fail" <?= _r($responses, $cid, 'target_pass_fail') === 'fail' ? 'selected' : '' ?>>Fail</option>
                        </select>
                      </td>

                    <?php elseif ($type === 'rating'): ?>
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                        <td class="text-center">
                          <input type="radio"
                                 name="responses[<?= $cid ?>][score]"
                                 value="<?= $s ?>"
                                 class="form-check-input rating-radio"
                                 data-cid="<?= $cid ?>"
                                 <?= (int)_r($responses, $cid, 'score', 0) === $s ? 'checked' : '' ?>>
                        </td>
                      <?php endfor; ?>
                      <td class="text-center">
                        <span id="score_display_<?= $cid ?>"
                              class="badge <?= _r($responses, $cid, 'score') ? 'bg-light-primary text-primary' : 'bg-light-secondary text-secondary' ?>">
                          <?php $sv = _r($responses, $cid, 'score'); echo $sv ?: '—'; ?>
                        </span>
                      </td>

                    <?php elseif ($type === 'pass_fail'): ?>
                      <?php foreach (['pass', 'fail', 'na'] as $pf): ?>
                        <td class="text-center">
                          <input type="radio"
                                 name="responses[<?= $cid ?>][pass_fail]"
                                 value="<?= $pf ?>"
                                 class="form-check-input"
                                 <?= _r($responses, $cid, 'pass_fail') === $pf ? 'checked' : '' ?>>
                        </td>
                      <?php endforeach; ?>

                    <?php elseif ($type === 'phone' || $type === 'attendance'): ?>
                      <?php foreach (eval_phone_usage_options() as $opt): ?>
                        <td class="text-center">
                          <input type="radio"
                                 name="responses[<?= $cid ?>][selected_option]"
                                 value="<?= html_escape($opt) ?>"
                                 class="form-check-input"
                                 <?= _r($responses, $cid, 'selected_option') === $opt ? 'checked' : '' ?>>
                        </td>
                      <?php endforeach; ?>

                    <?php elseif ($type === 'text'): ?>
                      <?php /* text type: no extra columns, just the comments column below */ ?>

                    <?php endif; ?>

                    <!-- Comments — always last -->
                    <td>
                      <input type="text"
                             class="form-control form-control-sm"
                             name="responses[<?= $cid ?>][comments]"
                             value="<?= e(_r($responses, $cid, 'comments')) ?>"
                             placeholder="Optional">
                    </td>
                  </tr>
                <?php endforeach; // criteria ?>
                </tbody>

                <!-- Footer rows -->
                <?php if ($has_rating || ($has_passfail && !$has_target)): ?>
                <tfoot>
                  <?php if ($has_rating): ?>
                  <tr class="table-light fw-semibold">
                    <td>Average Rating</td>
                    <?php for ($s = 1; $s <= 5; $s++): ?><td></td><?php endfor; ?>
                    <td class="text-center">
                      <span id="avg_rating_score_<?= html_escape($sec['section_key']) ?>"
                            class="badge bg-light-primary text-primary">—</span>
                    </td>
                    <td></td>
                  </tr>
                  <?php endif; ?>
                  <?php if ($has_passfail && !$has_target): ?>
                  <tr class="table-light">
                    <td class="text-end fw-semibold small text-muted">Pass Count</td>
                    <td class="text-center">
                      <span class="badge bg-light-success text-success pf-count-badge"
                            data-seckey="<?= html_escape($sec['section_key']) ?>">—</span>
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                  </tr>
                  <?php endif; ?>
                </tfoot>
                <?php endif; ?>

              </table>
            </div>
          </div>
        </div>
        <?php endforeach; // sections ?>
        <!-- /generic sections loop -->

      </div>
      <!-- /col left -->

      <!-- ════════════════════════════════════════════════════════════
           RIGHT SIDEBAR
           ════════════════════════════════════════════════════════════ -->
      <div class="col-12 col-xl-4">

        <!-- Comments -->
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-light-secondary py-2 px-3">
            <h6 class="mb-0 text-primary">
              <i class="ti ti-message-circle me-2"></i>Comments
            </h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label small fw-semibold">Employee Comments</label>
              <textarea name="employee_comments" class="form-control form-control-sm" rows="3"
                        placeholder="Employee's self-assessment or remarks…"
              ><?= e($is_edit ? ($eval['employee_comments'] ?? '') : '') ?></textarea>
            </div>
            <div>
              <label class="form-label small fw-semibold">Supervisor Comments</label>
              <textarea name="supervisor_comments" class="form-control form-control-sm" rows="4"
                        placeholder="Supervisor's overall remarks…"
              ><?= e($is_edit ? ($eval['supervisor_comments'] ?? '') : '') ?></textarea>
            </div>
          </div>
        </div>

        <!-- Goals & Development -->
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-light-secondary py-2 px-3
                      d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary">
              <i class="ti ti-rocket me-2"></i>Goals &amp; Development
            </h6>
            <button type="button" class="btn btn-ssm btn-outline-primary" id="addGoalBtn">
              <i class="ti ti-plus"></i> Add
            </button>
          </div>
          <div class="card-body p-2" id="goalsContainer">
            <?php
            $goal_rows = !empty($goals) ? $goals : [['goal' => '', 'training_need' => '']];
            foreach ($goal_rows as $gi => $gr):
            ?>
            <div class="goal-row border rounded p-2 mb-2 position-relative">
              <button type="button"
                      class="btn btn-ssm btn-outline-danger remove-goal-btn position-absolute"
                      style="top:6px;right:6px;" tabindex="-1">
                <i class="ti ti-x"></i>
              </button>
              <div class="mb-2">
                <label class="form-label x-small text-muted mb-1">Goal / Action Item</label>
                <input type="text" name="goals[<?= $gi ?>]"
                       class="form-control form-control-sm"
                       value="<?= e($gr['goal'] ?? '') ?>"
                       placeholder="e.g., Reduce AR ageing by 10%">
              </div>
              <div>
                <label class="form-label x-small text-muted mb-1">Training / Development Need</label>
                <input type="text" name="training_needs[<?= $gi ?>]"
                       class="form-control form-control-sm"
                       value="<?= e($gr['training_need'] ?? '') ?>"
                       placeholder="e.g., ERA posting refresher">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Overall Verdict -->
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-light-secondary py-2 px-3">
            <h6 class="mb-0 text-primary">
              <i class="ti ti-award me-2"></i>Overall Verdict
            </h6>
          </div>
          <div class="card-body">
            <select name="overall_verdict" class="form-select form-select-sm mb-2">
              <option value="">— Select Verdict —</option>
              <?php
              $saved_verdict = $is_edit ? ($eval['overall_verdict'] ?? '') : '';
              foreach (eval_verdict_options() as $key => $label):
              ?>
                <option value="<?= e($key) ?>"
                        <?= $saved_verdict === $key ? 'selected' : '' ?>>
                  <?= e($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Signatures -->
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-light-secondary py-2 px-3">
            <h6 class="mb-0 text-primary">
              <i class="ti ti-signature me-2"></i>Signatures
            </h6>
          </div>
          <div class="card-body">
            <?php
            $sig_fields = [
                ['sig_supervisor', 'sig_supervisor_date', 'Supervisor'],
                ['sig_employee',   'sig_employee_date',   'Employee'],
                ['sig_hr',         'sig_hr_date',         'HR / Authorized By'],
            ];
            foreach ($sig_fields as [$name_field, $date_field, $label]):
            ?>
            <div class="mb-3">
              <label class="form-label small fw-semibold"><?= $label ?></label>
              <div class="row g-1">
                <div class="col-7">
                  <input type="text" name="<?= $name_field ?>"
                         class="form-control form-control-sm"
                         value="<?= e($is_edit ? ($eval[$name_field] ?? '') : '') ?>"
                         placeholder="Full name">
                </div>
                <div class="col-5">
                  <input type="date" name="<?= $date_field ?>"
                         class="form-control form-control-sm"
                         value="<?= e($is_edit ? ($eval[$date_field] ?? '') : '') ?>">
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Score Summary -->
        <div class="card shadow-sm mb-3 border-primary">
          <div class="card-header bg-primary text-white py-2 px-3">
            <h6 class="mb-0">
              <i class="ti ti-chart-bar me-2"></i>Score Summary
            </h6>
          </div>
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2 small">
              <span class="text-muted">Attendance Score</span>
              <span id="summary_att" class="fw-semibold">—</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2 small">
              <span class="text-muted">Target Achievement</span>
              <span id="summary_target" class="fw-semibold">—</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2 small">
              <span class="text-muted">Pass / Fail Count</span>
              <span id="summary_metrics" class="fw-semibold">—</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center small">
              <span class="text-muted fw-semibold">Avg. Rating</span>
              <span id="summary_rating" class="fw-bold fs-6 text-primary">—</span>
            </div>
          </div>
        </div>

      </div>
      <!-- /col right -->

    </div><!-- /row -->
  </form>
</div>

<!-- Goal row template -->
<template id="goalRowTemplate">
  <div class="goal-row border rounded p-2 mb-2 position-relative">
    <button type="button"
            class="btn btn-ssm btn-outline-danger remove-goal-btn position-absolute"
            style="top:6px;right:6px;" tabindex="-1">
      <i class="ti ti-x"></i>
    </button>
    <div class="mb-2">
      <label class="form-label x-small text-muted mb-1">Goal / Action Item</label>
      <input type="text" name="goals[__IDX__]" class="form-control form-control-sm"
             placeholder="e.g., Reduce AR ageing by 10%">
    </div>
    <div>
      <label class="form-label x-small text-muted mb-1">Training / Development Need</label>
      <input type="text" name="training_needs[__IDX__]" class="form-control form-control-sm"
             placeholder="e.g., ERA posting refresher">
    </div>
  </div>
</template>

<script>
(function () {

  /* ── Goals ────────────────────────────────────────────────── */
  var $container = document.getElementById('goalsContainer');
  var goalIdx    = <?= count(!empty($goals) ? $goals : [['goal' => '', 'training_need' => '']]) ?>;

  document.getElementById('addGoalBtn').addEventListener('click', function () {
    var tmpl = document.getElementById('goalRowTemplate').innerHTML
                   .replace(/__IDX__/g, goalIdx++);
    var wrap = document.createElement('div');
    wrap.innerHTML = tmpl;
    $container.appendChild(wrap.firstElementChild);
  });

  $container.addEventListener('click', function (e) {
    var btn = e.target.closest('.remove-goal-btn');
    if (!btn) return;
    if ($container.querySelectorAll('.goal-row').length <= 1) return;
    btn.closest('.goal-row').remove();
    $container.querySelectorAll('.goal-row').forEach(function (row, i) {
      row.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/\[\d+\]/, '[' + i + ']');
      });
    });
    goalIdx = $container.querySelectorAll('.goal-row').length;
  });

  /* ── Attendance % ─────────────────────────────────────────── */
  function recalcAtt() {
    var w    = parseFloat(document.querySelector('[name="att_working_days"]').value) || 0;
    var p    = parseFloat(document.querySelector('[name="att_days_present"]').value) || 0;
    var disp = document.getElementById('att_pct_display');
    disp.value = w > 0 ? (p / w * 100).toFixed(1) + '%' : '';
  }
  ['att_working_days', 'att_days_present'].forEach(function (n) {
    var el = document.querySelector('[name="' + n + '"]');
    if (el) el.addEventListener('input', recalcAtt);
  });

  /* ── Target Achievement % ─────────────────────────────────── */
  function recalcAch(cid) {
    var tgtEl = document.querySelector('.tgt-month[data-cid="' + cid + '"]');
    var actEl = document.querySelector('.act-month[data-cid="' + cid + '"]');
    var badge = document.getElementById('ach_pct_' + cid);
    if (!tgtEl || !actEl || !badge) return;
    var tgt = parseFloat(tgtEl.value) || 0;
    var act = parseFloat(actEl.value);
    if (isNaN(act) || tgt === 0) { badge.textContent = '—'; return; }
    var pct = (act / tgt * 100).toFixed(1);
    badge.textContent = pct + '%';
    badge.className   = 'badge ' + (
      pct >= 100 ? 'bg-light-success text-success' :
      pct >= 80  ? 'bg-light-primary text-primary'  :
      pct >= 60  ? 'bg-light-warning text-warning'   :
                   'bg-light-danger text-danger'
    );
    recalcSummaryTargets();
  }
  document.querySelectorAll('.tgt-month, .act-month').forEach(function (el) {
    el.addEventListener('input', function () { recalcAch(this.dataset.cid); });
  });

  /* ── Rating score display + summary ──────────────────────── */
  document.querySelectorAll('.rating-radio').forEach(function (radio) {
    radio.addEventListener('change', function () {
      var disp = document.getElementById('score_display_' + this.dataset.cid);
      if (disp) {
        disp.textContent = this.value;
        disp.className   = 'badge bg-light-primary text-primary';
      }
      recalcAvgRating();
    });
  });

  function recalcAvgRating() {
    // Per-section average spans
    document.querySelectorAll('[id^="avg_rating_score_"]').forEach(function (el) {
      var tfoot  = el.closest('tfoot');
      if (!tfoot) return;
      var tbody  = tfoot.closest('table').querySelector('tbody');
      var radios = tbody.querySelectorAll('.rating-radio:checked');
      if (!radios.length) { el.textContent = '—'; return; }
      var sum = 0;
      radios.forEach(function (r) { sum += parseInt(r.value, 10); });
      el.textContent = (sum / radios.length).toFixed(2);
    });
    // Global summary
    var all = document.querySelectorAll('.rating-radio:checked');
    var se  = document.getElementById('summary_rating');
    if (!se) return;
    if (!all.length) { se.textContent = '—'; return; }
    var total = 0;
    all.forEach(function (r) { total += parseInt(r.value, 10); });
    se.textContent = (total / all.length).toFixed(2) + ' / 5';
  }

  /* ── Pass / Fail count ────────────────────────────────────── */
  function recalcPassCount() {
    var totalPasses = 0;
    var totalItems  = 0;
    document.querySelectorAll('.pf-count-badge').forEach(function (badge) {
      var tfoot  = badge.closest('tfoot');
      if (!tfoot) return;
      var tbody  = tfoot.closest('table').querySelector('tbody');
      var passes = tbody.querySelectorAll('input[name*="[pass_fail]"][value="pass"]:checked').length;
      // Count unique criteria by counting the "pass" radio inputs / 3 options each
      var total  = tbody.querySelectorAll('input[name*="[pass_fail]"][value="pass"]').length;
      badge.textContent = passes + ' / ' + total;
      totalPasses += passes;
      totalItems  += total;
    });
    var se = document.getElementById('summary_metrics');
    if (se) se.textContent = totalItems > 0 ? totalPasses + ' / ' + totalItems : '—';
  }
  document.addEventListener('change', function (e) {
    if (e.target.name && e.target.name.includes('[pass_fail]')) {
      recalcPassCount();
    }
  });

  /* ── Summary: target avg ──────────────────────────────────── */
  function recalcSummaryTargets() {
    var vals = [];
    document.querySelectorAll('[id^="ach_pct_"]').forEach(function (b) {
      var n = parseFloat(b.textContent);
      if (!isNaN(n)) vals.push(n);
    });
    var el = document.getElementById('summary_target');
    if (!el) return;
    el.textContent = vals.length
      ? (vals.reduce(function (a, b) { return a + b; }, 0) / vals.length).toFixed(1) + '%'
      : '—';
  }

  /* ── Init ─────────────────────────────────────────────────── */
  recalcAtt();
  recalcAvgRating();
  recalcPassCount();
  recalcSummaryTargets();

})();
</script>