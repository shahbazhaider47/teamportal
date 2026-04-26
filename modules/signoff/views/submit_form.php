<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
  /* ── Variables from controller ──────────────────────────────────────────
   *  $form               array   form row
   *  $fields             array   decoded form fields (file fields stripped)
   *  $existing_submission array|null  today's existing submission if any
   *  $today_full         string  e.g. "Friday, April 18, 2025"
   *  $submit_date        string  Y-m-d of the submission date
   *  $has_file_field     bool
   *  $file_field_label   string
   *  $month_stats        array   from Signoff_calendar_model::get_working_day_stats()
   * ─────────────────────────────────────────────────────────────────────── */

  $canView = staff_can('view_own', 'signoff');

  // ── Month stats (safe defaults if not passed) ────────────────────────────
  $ms           = isset($month_stats) && is_array($month_stats) ? $month_stats : [];
  $msLabel      = $ms['month_label']        ?? date('F Y');
  $msWorking    = (int)($ms['working_days']      ?? 0);
  $msWorkingPast= (int)($ms['working_days_past'] ?? 0);
  $msSubmitted  = (int)($ms['submitted']         ?? 0);
  $msMissed     = (int)($ms['missed']            ?? 0);
  $msPending    = (int)($ms['pending']           ?? 0);
  $msExcused    = (int)($ms['excused']           ?? 0);
  $msRate       = (float)($ms['compliance_rate'] ?? 0.0);
  $msUpcoming   = (int)($ms['upcoming']          ?? 0);
  $msBarCls     = $msRate >= 80 ? 'success' : ($msRate >= 50 ? 'warning' : 'danger');

  // ── Is this an update (existing) or a new submission? ───────────────────
  $isUpdate = isset($existing_submission) && !empty($existing_submission);

  // ── Pre-decode existing field values for re-fill ─────────────────────────
  $prevData = [];
  if ($isUpdate && !empty($existing_submission['fields_data'])) {
      $decoded = json_decode($existing_submission['fields_data'], true);
      if (is_array($decoded)) { $prevData = $decoded; }
  }
?>

<div class="container-fluid">

  <!-- ═══════════ PAGE HEADER ═══════════ -->
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm flex-wrap">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title mb-0">
        <?= html_escape($title ?? 'Submit Signoff') ?>
        <i class="ti ti-chevron-right text-muted mx-1" style="font-size:.75rem;"></i>
        <span class="text-muted fw-normal"><?= html_escape($form['title'] ?? '') ?></span>
      </h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="<?= $canView ? site_url('signoff') : 'javascript:void(0);' ?>"
         class="btn btn-header <?= $canView ? 'btn-primary' : 'btn-outline-secondary disabled' ?>">
        <i class="ti ti-calendar me-1"></i> Signoff
      </a>
      <a href="<?= $canView ? site_url('signoff/signoff_history') : 'javascript:void(0);' ?>"
         class="btn btn-header <?= $canView ? 'btn-outline-primary' : 'btn-outline-secondary disabled' ?>">
        <i class="ti ti-history-toggle me-1"></i> History
      </a>
    </div>
  </div>
  <!-- /PAGE HEADER -->

  <div class="row g-4">

    <!-- ═══════════════════════ LEFT: FORM ═══════════════════════ -->
    <div class="col-lg-8">
      <div class="card shadow-sm border-0">

        <!-- Card header -->
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
          <div>
            <h5 class="mb-0 fw-semibold">
              <i class="ti ti-clipboard-text me-2 text-primary"></i>
              <?= html_escape($form['title'] ?? 'Signoff Form') ?>
            </h5>
            <div class="text-muted small mt-1">
              <i class="ti ti-calendar-event me-1"></i>
              Submission date: <strong><?= html_escape($today_full ?? '') ?></strong>
            </div>
          </div>
          <?php if ($isUpdate): ?>
            <span class="badge bg-warning text-dark">
              <i class="ti ti-refresh me-1"></i> Updating Existing
            </span>
          <?php else: ?>
            <span class="badge bg-light-primary text-primary">
              <i class="ti ti-plus me-1"></i> New Submission
            </span>
          <?php endif; ?>
        </div>

        <!-- Status alert -->
        <div class="px-4 pt-3">
          <?php if ($isUpdate): ?>
            <div class="alert bg-warning d-flex align-items-start gap-2 mb-0 py-2">
              <i class="ti ti-alert-triangle mt-1 flex-shrink-0"></i>
              <div>
                <strong>Already submitted for today.</strong>
                Saving will <u>replace</u> all previous data for <?= html_escape($today_full ?? '') ?>.
                <?php if (!empty($existing_submission['status'])): ?>
                  Current status:
                  <span class="badge bg-<?= strtolower($existing_submission['status']) === 'approved' ? 'success' : (strtolower($existing_submission['status']) === 'rejected' ? 'danger' : 'primary') ?>">
                    <?= ucfirst(html_escape($existing_submission['status'])) ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <div class="alert bg-info d-flex align-items-start gap-2 mb-0 py-2">
              <i class="ti ti-clock-hour-4 mt-1 flex-shrink-0"></i>
              <div>
                <strong>Not yet submitted</strong> for today (<?= html_escape($today_full ?? '') ?>).
                Fill in the form below and click Submit.
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Form body -->
        <form action="<?= base_url('signoff/submit/' . (int)$form['id']) ?>"
              method="post"
              class="p-4 app-form"
              autocomplete="off"
              enctype="multipart/form-data">

          <input type="hidden" name="form_id" value="<?= (int)$form['id'] ?>">

          <?php if (!empty($fields) && is_array($fields)): ?>
            <div class="row">
              <?php foreach ($fields as $field):
                $name        = $field['name']        ?? '';
                $label       = $field['label']       ?? $name;
                $type        = strtolower($field['type'] ?? 'text');
                $col         = !empty($field['col'])  ? $field['col'] : 'col-md-6';
                $required    = !empty($field['required']) ? 'required' : '';
                $placeholder = $field['placeholder'] ?? '';
                $options     = $field['options']     ?? [];
                $hint        = $field['hint']        ?? '';

                // Pre-fill: existing submission data takes priority
                $value = $prevData[$name] ?? '';
              ?>
              <div class="<?= html_escape($col) ?> mb-3">
                <label class="form-label fw-semibold small" for="field_<?= md5($name) ?>">
                  <?= html_escape(ucwords(str_replace('_', ' ', $label))) ?>
                  <?php if ($required): ?>
                    <span class="text-danger ms-1">*</span>
                  <?php endif; ?>
                </label>

                <?php switch ($type):
                  case 'textarea': ?>
                    <textarea id="field_<?= md5($name) ?>"
                              name="fields[<?= html_escape($name) ?>]"
                              class="form-control"
                              rows="3"
                              <?= $required ?>
                              placeholder="<?= html_escape($placeholder) ?>"><?= html_escape(is_array($value) ? implode(', ', $value) : (string)$value) ?></textarea>
                    <?php break;

                  case 'select': ?>
                    <select id="field_<?= md5($name) ?>"
                            name="fields[<?= html_escape($name) ?>]"
                            class="form-select"
                            <?= $required ?>>
                      <option value="">— Select —</option>
                      <?php
                        $opts = is_string($options) ? array_map('trim', explode(',', $options)) : (array)$options;
                        foreach ($opts as $opt):
                          $sel = ((string)$value === (string)$opt) ? 'selected' : '';
                      ?>
                        <option value="<?= html_escape($opt) ?>" <?= $sel ?>><?= html_escape($opt) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <?php break;

                  case 'radio': ?>
                    <div class="pt-1">
                      <?php foreach ((array)$options as $opt): ?>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio"
                                 name="fields[<?= html_escape($name) ?>]"
                                 id="field_<?= md5($name . $opt) ?>"
                                 value="<?= html_escape($opt) ?>"
                                 <?= $required ?>
                                 <?= ((string)$value === (string)$opt) ? 'checked' : '' ?>>
                          <label class="form-check-label" for="field_<?= md5($name . $opt) ?>">
                            <?= html_escape($opt) ?>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                    <?php break;

                  case 'checkbox': ?>
                    <div class="pt-1">
                      <?php
                        $checkedArr = is_array($value)
                            ? $value
                            : array_map('trim', explode(',', (string)$value));
                        foreach ((array)$options as $opt):
                      ?>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="checkbox"
                                 name="fields[<?= html_escape($name) ?>][]"
                                 id="field_<?= md5($name . $opt) ?>"
                                 value="<?= html_escape($opt) ?>"
                                 <?= in_array((string)$opt, array_map('strval', $checkedArr), true) ? 'checked' : '' ?>>
                          <label class="form-check-label" for="field_<?= md5($name . $opt) ?>">
                            <?= html_escape($opt) ?>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                    <?php break;

                  case 'amount': ?>
                    <div class="input-group">
                      <span class="input-group-text"><i class="ti ti-currency-dollar"></i></span>
                      <input type="number" step="0.01"
                             id="field_<?= md5($name) ?>"
                             name="fields[<?= html_escape($name) ?>]"
                             class="form-control"
                             <?= $required ?>
                             placeholder="<?= html_escape($placeholder ?: '0.00') ?>"
                             value="<?= html_escape((string)$value) ?>">
                    </div>
                    <?php break;

                  case 'number': ?>
                    <input type="number"
                           id="field_<?= md5($name) ?>"
                           name="fields[<?= html_escape($name) ?>]"
                           class="form-control"
                           <?= $required ?>
                           placeholder="<?= html_escape($placeholder) ?>"
                           value="<?= html_escape((string)$value) ?>">
                    <?php break;

                  case 'email':
                  case 'phone':
                  case 'date':
                  case 'time':
                  case 'color':
                  case 'password':
                    $inputType = ($type === 'phone') ? 'tel' : $type;
                    ?>
                    <input type="<?= $inputType ?>"
                           id="field_<?= md5($name) ?>"
                           name="fields[<?= html_escape($name) ?>]"
                           class="form-control"
                           <?= $required ?>
                           placeholder="<?= html_escape($placeholder) ?>"
                           value="<?= html_escape((string)$value) ?>">
                    <?php break;

                  case 'link': ?>
                    <input type="url"
                           id="field_<?= md5($name) ?>"
                           name="fields[<?= html_escape($name) ?>]"
                           class="form-control"
                           <?= $required ?>
                           placeholder="<?= html_escape($placeholder ?: 'https://') ?>"
                           value="<?= html_escape((string)$value) ?>">
                    <?php break;

                  case 'hidden': ?>
                    <input type="hidden"
                           id="field_<?= md5($name) ?>"
                           name="fields[<?= html_escape($name) ?>]"
                           value="<?= html_escape((string)$value) ?>">
                    <?php break;

                  default: ?>
                    <input type="text"
                           id="field_<?= md5($name) ?>"
                           name="fields[<?= html_escape($name) ?>]"
                           class="form-control"
                           <?= $required ?>
                           placeholder="<?= html_escape($placeholder) ?>"
                           value="<?= html_escape((string)$value) ?>">
                    <?php break;

                endswitch; ?>

                <?php if ($hint !== ''): ?>
                  <div class="form-text text-muted"><?= html_escape($hint) ?></div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div><!-- /fields row -->

          <?php else: ?>
            <div class="alert alert-danger">
              <i class="ti ti-alert-circle me-2"></i>
              No fields are configured for this form. Please contact your administrator.
            </div>
          <?php endif; ?>

          <!-- File attachment (rendered separately, after fields) -->
          <?php if (!empty($has_file_field)): ?>
            <hr class="my-3">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold small" for="signoff_attachment">
                  <i class="ti ti-paperclip me-1 text-muted"></i>
                  <?= html_escape($file_field_label ?? 'Attachment') ?>
                </label>
                <input type="file"
                       class="form-control"
                       id="signoff_attachment"
                       name="signoff_attachment">
                <?php if (!empty($existing_submission['signoff_attachment'])): ?>
                  <div class="mt-2 small">
                    <span class="text-muted">Current file: </span>
                    <a href="<?= base_url(html_escape($existing_submission['signoff_attachment'])) ?>"
                       target="_blank"
                       class="text-primary">
                      <i class="ti ti-external-link me-1"></i>View / Download
                    </a>
                    <span class="text-muted ms-1">(upload a new file to replace)</span>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Form actions -->
          <hr class="my-4">
          <div class="d-flex justify-content-between align-items-center">
            <a href="<?= base_url('signoff') ?>" class="btn btn-outline-secondary btn-sm">
              <i class="ti ti-arrow-left me-1"></i> Back to Signoff
            </a>
            <button type="submit" class="btn btn-primary px-4">
              <i class="ti ti-send me-1"></i>
              <?= $isUpdate ? 'Update Signoff' : 'Submit Signoff' ?>
            </button>
          </div>

        </form>
      </div>
    </div>
    <!-- /LEFT: FORM -->

    <!-- ═══════════════════════ RIGHT: SIDEBAR ═══════════════════════ -->
    <div class="col-lg-4">

      <!-- ── Monthly Summary Card ── -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-semibold">
            <i class="ti ti-chart-bar me-2 text-primary"></i>
            <?= html_escape($msLabel) ?> Summary
          </h6>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush small">

            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <span class="text-muted">
                <i class="ti ti-calendar-stats me-2 text-primary"></i>Working Days
              </span>
              <span class="fw-semibold">
                <?= $msWorking ?>
                <?php if ($msUpcoming > 0): ?>
                  <span class="text-muted fw-normal small">(<?= $msUpcoming ?> upcoming)</span>
                <?php endif; ?>
              </span>
            </li>

            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <span class="text-muted">
                <i class="ti ti-circle-check me-2 text-success"></i>Submitted
              </span>
              <span class="fw-semibold text-success"><?= $msSubmitted ?></span>
            </li>

            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <span class="text-muted">
                <i class="ti ti-clock me-2 text-info"></i>Pending Review
              </span>
              <span class="fw-semibold text-info"><?= $msPending ?></span>
            </li>

            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <span class="text-muted">
                <i class="ti ti-circle-x me-2 text-danger"></i>Missed
              </span>
              <span class="fw-semibold text-danger"><?= $msMissed ?></span>
            </li>

            <?php if ($msExcused > 0): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <span class="text-muted">
                <i class="ti ti-beach me-2 text-warning"></i>Excused / Leave
              </span>
              <span class="fw-semibold text-warning"><?= $msExcused ?></span>
            </li>
            <?php endif; ?>

            <li class="list-group-item py-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">
                  <i class="ti ti-chart-pie me-2 text-<?= $msBarCls ?>"></i>Compliance
                </span>
                <span class="fw-bold text-<?= $msBarCls ?>"><?= $msRate ?>%</span>
              </div>
              <div class="progress" style="height:6px;border-radius:3px;">
                <div class="progress-bar bg-<?= $msBarCls ?>"
                     style="width:<?= min(100, $msRate) ?>%"
                     role="progressbar"
                     aria-valuenow="<?= $msRate ?>"
                     aria-valuemin="0" aria-valuemax="100">
                </div>
              </div>
              <div class="text-muted mt-1" style="font-size:.7rem;">
                Based on <?= $msWorkingPast ?> past working day<?= $msWorkingPast != 1 ? 's' : '' ?>
              </div>
            </li>

          </ul>
        </div>
      </div>

      <!-- ── This Month's Submission Days ── -->
      <?php
        // Pull past days that were submitted or missed (most recent first, capped at 10)
        $msDays    = isset($ms['days']) && is_array($ms['days']) ? $ms['days'] : [];
        $dayRows   = [];
        $actionTypes = ['submitted', 'pending', 'missed', 'excused', 'on_leave'];
        foreach (array_reverse($msDays) as $d) {
            if (in_array($d['type'] ?? '', $actionTypes, true)) {
                $dayRows[] = $d;
                if (count($dayRows) >= 10) { break; }
            }
        }

        $dayTypeMeta = [
            'submitted' => ['success',  'ti-circle-check', 'Submitted'],
            'pending'   => ['info',     'ti-clock',        'Pending'],
            'missed'    => ['danger',   'ti-circle-x',     'Missed'],
            'excused'   => ['warning',  'ti-circle-check', 'Excused'],
            'on_leave'  => ['warning',  'ti-beach',        'On Leave'],
        ];
      ?>
      <?php if (!empty($dayRows)): ?>
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="mb-0 fw-semibold">
            <i class="ti ti-calendar-week me-2 text-primary"></i>
            Recent Days
          </h6>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush small">
            <?php foreach ($dayRows as $d):
              $dt   = $d['type'] ?? 'missed';
              $meta = $dayTypeMeta[$dt] ?? ['secondary', 'ti-circle', ucfirst($dt)];
              $isCurrentDate = ($d['date'] ?? '') === ($submit_date ?? '');
            ?>
            <li class="list-group-item d-flex align-items-center justify-content-between py-2
                        <?= $isCurrentDate ? 'bg-light-primary' : '' ?>">
              <div class="d-flex align-items-center gap-2">
                <i class="ti <?= $meta[1] ?> text-<?= $meta[0] ?>"></i>
                <div>
                  <div class="fw-semibold">
                    <?= date('d M Y', strtotime($d['date'])) ?>
                    <span class="text-muted fw-normal">(<?= $d['dow'] ?>)</span>
                    <?php if ($isCurrentDate): ?>
                      <span class="badge bg-primary ms-1" style="font-size:.6rem;">Today</span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($d['holiday'])): ?>
                    <div class="text-muted" style="font-size:.7rem;">
                      <i class="ti ti-flag me-1"></i><?= html_escape($d['holiday']) ?>
                    </div>
                  <?php elseif (!empty($d['leave'])): ?>
                    <div class="text-muted" style="font-size:.7rem;">
                      <i class="ti ti-beach me-1"></i><?= html_escape($d['leave']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <span class="badge bg-light-<?= $meta[0] ?> text-<?= $meta[0] ?> fw-normal">
                <?= $meta[2] ?>
              </span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="card-footer bg-white border-top py-2 text-center">
          <a href="<?= site_url('signoff/signoff_history') ?>" class="small text-primary text-decoration-none">
            <i class="ti ti-history me-1"></i> View full history
          </a>
        </div>
      </div>
      <?php endif; ?>

    </div>
    <!-- /RIGHT: SIDEBAR -->

  </div><!-- /row -->
</div><!-- /container-fluid -->