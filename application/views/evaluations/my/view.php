<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

  <!-- HEADER -->
  <div class="view-header mb-3">
    <div class="view-icon me-3"><i class="ti ti-clipboard-check"></i></div>

    <div class="flex-grow-1">
      <div class="view-title">
        <?= e($user['firstname'] . ' ' . $user['lastname']) ?>
      </div>
      <div class="text-muted small">
        <?= e($template['name'] ?? '') ?>
      </div>
    </div>

    <div class="ms-auto">
      <a href="<?= site_url('evaluations/my') ?>" class="btn btn-light btn-sm">
        <i class="ti ti-arrow-left"></i> Back
      </a>
    </div>
  </div>

  <!-- SUMMARY -->
  <div class="row g-3 mb-3">

    <div class="col-md-3">
      <div class="kpi-card">
        <div class="kpi-label">Status</div>
        <div><?= eval_status_badge($eval['status']) ?></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div class="kpi-label">Attendance</div>
        <div><?= eval_score_badge($eval['score_attendance']) ?></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div class="kpi-label">Rating</div>
        <div><?= eval_score_badge($eval['score_ratings']) ?></div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card">
        <div class="kpi-label">Review Date</div>
        <div><?= date('d M Y', strtotime($eval['review_date'])) ?></div>
      </div>
    </div>

  </div>

  <!-- SECTIONS -->
  <?php foreach ($sections as $section): ?>

    <div class="solid-card mb-3">
      <div class="card-header">
        <strong><?= e($section['section_label']) ?></strong>
      </div>

      <div class="card-body">

        <?php if (!empty($section['criteria'])): ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <tbody>

                <?php foreach ($section['criteria'] as $c): 
                  $resp = $responses[$c['id']] ?? null;
                ?>

                  <tr>
                    <td width="40%">
                      <?= e($c['label']) ?>
                    </td>

                    <td>

                      <?php if (!$resp): ?>
                        <span class="text-muted">—</span>

                      <?php else: ?>

                        <?php switch ($c['criteria_type']):

                          case 'rating':
                            echo eval_score_badge($resp['score'] ?? null);
                            break;

                          case 'pass_fail':
                            echo $resp['pass_fail'] === 'pass'
                              ? '<span class="badge bg-success">Pass</span>'
                              : '<span class="badge bg-danger">Fail</span>';
                            break;

                          case 'target':
                            echo '<strong>' . ($resp['actual_month'] ?? '-') . '</strong>';
                            echo ' / ' . ($resp['target_month'] ?? '-');
                            break;

                          case 'attendance':
                          case 'phone':
                            echo e($resp['selected_option'] ?? '—');
                            break;

                          case 'text':
                            echo nl2br(e($resp['comments'] ?? '—'));
                            break;

                          default:
                            echo '—';

                        endswitch; ?>

                      <?php endif; ?>

                    </td>
                  </tr>

                <?php endforeach; ?>

              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>

  <?php endforeach; ?>

  <!-- COMMENTS -->
  <div class="row g-3">

    <div class="col-md-6">
      <div class="solid-card">
        <div class="card-header">Employee Comments</div>
        <div class="card-body">
          <?= nl2br(e($eval['employee_comments'] ?? '—')) ?>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="solid-card">
        <div class="card-header">Supervisor Comments</div>
        <div class="card-body">
          <?= nl2br(e($eval['supervisor_comments'] ?? '—')) ?>
        </div>
      </div>
    </div>

  </div>

</div>