<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$p       = $progress       ?? [];
$team    = $p['team']      ?? [];
$members = $p['members']   ?? [];
$att     = $p['attendance']  ?? [];
$leaves  = $p['leaves']    ?? [];
$tasks   = $p['tasks']     ?? [];
$evals   = $p['evaluations'] ?? [];
$sof     = $p['signoffs']  ?? [];
$top     = $p['top_performer'] ?? null;
$score   = $p['score']     ?? [];
$period  = $p['period']    ?? [];

$esc  = fn($v) => html_escape($v ?? '');
$num  = fn($v, $d = 0) => number_format((float)($v ?? 0), $d);
$pct  = fn($v) => $v !== null ? $num($v, 1) . '%' : '—';
$dash = fn($v) => $v !== null && $v !== '' ? $v : '—';

$gradeColor = function(string $g): string {
    return match($g) {
        'A+','A' => 'success',
        'B+','B' => 'primary',
        'C'      => 'warning',
        default  => 'danger',
    };
};

$mkAv = function(array $u, int $sz = 36): string {
    $first = trim($u['firstname'] ?? $u['lead_firstname'] ?? '');
    $last  = trim($u['lastname']  ?? $u['lead_lastname']  ?? '');
    $file  = trim($u['profile_image'] ?? $u['lead_avatar'] ?? '');
    $init  = strtoupper(substr($first, 0, 1) . substr($last, 0, 1)) ?: 'U';
    $fs    = max(10, (int)($sz * .36));
    $st    = "width:{$sz}px;height:{$sz}px;font-size:{$fs}px;border-radius:50%;flex-shrink:0;";
    if ($file) {
        $src = base_url('uploads/users/profile/' . $file);
        $fb  = base_url('assets/images/default-avatar.png');
        return '<img src="' . html_escape($src) . '" style="' . $st . 'object-fit:cover;"
                     loading="lazy" onerror="this.onerror=null;this.src=\'' . html_escape($fb) . '\'">';
    }
    return '<span style="' . $st . 'background:#E6F1FB;color:#185FA5;display:inline-flex;
                align-items:center;justify-content:center;font-weight:700;">'
         . html_escape($init) . '</span>';
};

$gradeStr = $score['grade'] ?? 'N/A';
$gradeCol = $gradeColor($gradeStr);
?>

<div class="container-fluid">

  <!-- ── Header ─────────────────────────────────────────────────── -->
  <div class="view-header mb-3">
    <?php
    $backUrl = staff_can('view_global', 'teams')
        ? site_url('teams')
        : (staff_can('view_own', 'teams') ? site_url('teams/my_team') : site_url('dashboard'));
    ?>
    <a href="<?= $backUrl ?>">
      <div class="view-icon me-3"><i class="ti ti-arrow-left"></i></div>
    </a>
    <div class="flex-grow-1">
      <div class="view-title">
        <i class="ti ti-users me-2"></i>
        <?= $esc($team['team_name'] ?? '—') ?>
        <span class="badge bg-<?= $gradeCol ?> ms-2"><?= $esc($gradeStr) ?></span>
      </div>
      <div class="text-muted small">
        <?= $esc($team['department_name'] ?? '—') ?>
        &nbsp;·&nbsp;
        Period: <?= $esc($period['start'] ?? '') ?> → <?= $esc($period['end'] ?? '') ?>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
      <!-- Period filter -->
      <form method="get" class="d-flex gap-2 align-items-center app-form">
        <input type="date" name="from" class="form-control form-control-sm"
               value="<?= $esc($from ?? '') ?>">
        <span class="text-muted small">to</span>
        <input type="date" name="to" class="form-control form-control-sm"
               value="<?= $esc($to ?? '') ?>">
        <button class="btn btn-primary btn-header">
          <i class="ti ti-filter me-1"></i>Apply
        </button>
      </form>

    <div class="btn-divider mt-1"></div>
    
      <a href="<?= site_url('teams/rankings') ?>"
         class="btn btn-outline-primary btn-header">
        <i class="ti ti-trophy me-1"></i>Rankings
      </a>
    </div>
  </div>

  <div class="row g-3">

    <!-- ════════════════════════════════════════════════════════
         LEFT — team info + lead/manager + KPI strip + members
         ════════════════════════════════════════════════════════ -->
    <div class="col-12 col-xl-8">

      <!-- Team Identity Card -->
      <div class="card shadow-sm mb-3 border-0" style="border-radius:14px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#056464 0%,#0a8a8a 100%);padding:20px 24px;">
          <div class="d-flex align-items-center gap-3 flex-wrap">

            <!-- Team score ring -->
            <div style="width:64px;height:64px;border-radius:50%;
                        background:rgba(255,255,255,0.15);
                        border:3px solid rgba(255,255,255,0.4);
                        display:flex;flex-direction:column;
                        align-items:center;justify-content:center;flex-shrink:0;">
              <span style="font-size:18px;font-weight:800;color:#fff;line-height:1;">
                <?= $esc($score['score'] ?? '—') ?>
              </span>
              <span style="font-size:9px;color:rgba(255,255,255,0.7);letter-spacing:.05em;">SCORE</span>
            </div>

            <div class="flex-grow-1">
              <div style="font-size:20px;font-weight:700;color:#fff;">
                <?= $esc($team['team_name'] ?? '—') ?>
              </div>
              <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:2px;">
                <i class="ti ti-building me-1"></i><?= $esc($team['department_name'] ?? '—') ?>
                &nbsp;·&nbsp;
                <i class="ti ti-users me-1"></i><?= (int)($score['member_count'] ?? 0) ?> members
              </div>
            </div>

            <div class="d-flex gap-3 flex-wrap">
              <!-- Team Lead -->
              <?php if (!empty($team['lead_firstname'])): ?>
              <div class="text-center">
                <div class="d-flex justify-content-center mb-1">
                  <?= $mkAv($team, 42) ?>
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,0.9);font-weight:600;">
                  <?= $esc($team['lead_firstname'] . ' ' . $team['lead_lastname']) ?>
                </div>
                <div style="font-size:10px;color:rgba(255,255,255,0.6);">Team Lead</div>
              </div>
              <?php endif; ?>

              <!-- Manager -->
              <?php if (!empty($team['manager_firstname'])): ?>
              <div class="text-center">
                <div class="d-flex justify-content-center mb-1">
                  <?php
                  $mgArr = [
                      'firstname'     => $team['manager_firstname'],
                      'lastname'      => $team['manager_lastname'],
                      'profile_image' => $team['manager_avatar'] ?? '',
                  ];
                  echo $mkAv($mgArr, 42);
                  ?>
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,0.9);font-weight:600;">
                  <?= $esc($team['manager_firstname'] . ' ' . $team['manager_lastname']) ?>
                </div>
                <div style="font-size:10px;color:rgba(255,255,255,0.6);">Manager</div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <!-- /Team Identity Card -->

      <!-- KPI Strip -->
      <div class="row g-2 mb-3">
        <?php
        $kpiStrip = [
            ['Attendance Logs',  $att['total_logs']        ?? 0, 'ti-calendar-check', 'primary'],
            ['Active Members',   $att['members_logged']    ?? 0, 'ti-users',           'success'],
            ['Tasks Total',      $tasks['total']           ?? 0, 'ti-checklist',       'info'],
            ['Tasks Done',       $tasks['completed']       ?? 0, 'ti-circle-check',    'success'],
            ['Completion %',     ($tasks['completion_pct'] ?? 0) . '%', 'ti-chart-bar', 'primary'],
            ['Evals Done',       $evals['total']           ?? 0, 'ti-clipboard-check', 'warning'],
            ['Avg Eval Score',   $evals['avg_score'] !== null ? number_format($evals['avg_score'], 1) . '/5' : '—', 'ti-star', 'warning'],
            ['Signoff Rate',     ($sof['compliance_pct']  ?? 0) . '%', 'ti-check',     'success'],
            ['Leave Days',       number_format($leaves['approved_days'] ?? 0, 1), 'ti-beach', 'secondary'],
            ['Past Due Tasks',   $tasks['past_due']        ?? 0, 'ti-alert-triangle',  'danger'],
        ];
        foreach ($kpiStrip as [$label, $val, $icon, $color]):
        ?>
        <div class="col-6 col-md-3">
          <div class="card shadow-sm border-0 h-100 text-center py-2 px-1">
            <div class="text-<?= $color ?> mb-1">
              <i class="ti <?= $icon ?>" style="font-size:18px;"></i>
            </div>
            <div class="fw-bold small"><?= html_escape((string)$val) ?></div>
            <div class="text-muted" style="font-size:10px;"><?= $label ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- /KPI Strip -->

      <!-- Member Performance Table -->
      <div class="solid-card mb-3">
        <div class="card-header py-1 px-1">
          <h6 class="mb-0 mt-0 text-primary">
            <i class="ti ti-users me-2"></i>Member Performance
          </h6>
          <span class="badge bg-light-primary"><?= count($members) ?> members</span>
        </div>
        <div class="card-body p-0">
          <?php if (empty($members)): ?>
            <div class="p-4 text-center text-muted fst-italic">No active members found.</div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm small align-middle mb-0 table-box-hover">
              <thead class="bg-light-primary">
                <tr class="text-nowrap">
                  <th width="30">#</th>
                  <th>Member</th>
                  <th class="text-center">Att %</th>
                  <th class="text-center">Tasks</th>
                  <th class="text-center">Task %</th>
                  <th class="text-center">Signoff %</th>
                  <th class="text-center">Eval</th>
                  <th class="text-center">Score</th>
                  <th class="text-center">Grade</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($members as $i => $m):
                $kpi      = $m['kpi']  ?? [];
                $attP     = $kpi['att_pct']         ?? null;
                $taskP    = $kpi['task_pct']         ?? null;
                $sfP      = $kpi['signoff_pct']      ?? null;
                $evalS    = $kpi['eval_score']       ?? null;
                $comp     = $kpi['composite_score']  ?? null;
                $verdict  = $kpi['eval_verdict']     ?? null;

                $compCol = match(true) {
                    $comp >= 80 => 'success',
                    $comp >= 60 => 'primary',
                    $comp >= 40 => 'warning',
                    default     => 'danger',
                };

                $isTop = $i === 0;
              ?>
              <tr class="<?= $isTop ? 'table-warning' : '' ?>">
                <td class="text-muted">
                  <?php if ($isTop): ?>
                    <i class="ti ti-trophy text-warning"></i>
                  <?php else: ?>
                    <?= $i + 1 ?>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <?= $mkAv($m, 30) ?>
                    <div>
                      <div class="fw-semibold">
                        <?= $esc($m['firstname'] . ' ' . $m['lastname']) ?>
                      </div>
                      <div class="text-muted" style="font-size:10px;">
                        <?= $esc($m['emp_id'] ?? '') ?>
                        <?php if (!empty($m['position_title'])): ?>
                          · <?= $esc($m['position_title']) ?>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="text-center">
                  <?php if ($attP !== null): ?>
                    <span class="badge bg-<?= $attP >= 80 ? 'success' : ($attP >= 60 ? 'warning' : 'danger') ?>-soft
                                          text-<?= $attP >= 80 ? 'success' : ($attP >= 60 ? 'warning' : 'danger') ?>">
                      <?= $num($attP, 1) ?>%
                    </span>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td class="text-center text-muted small">
                  <?= (int)($kpi['completed_tasks'] ?? 0) ?>/<?= (int)($kpi['total_tasks'] ?? 0) ?>
                </td>
                <td class="text-center">
                  <?php if ($taskP !== null): ?>
                    <span class="badge bg-<?= $taskP >= 80 ? 'success' : ($taskP >= 60 ? 'warning' : 'danger') ?>-soft
                                          text-<?= $taskP >= 80 ? 'success' : ($taskP >= 60 ? 'warning' : 'danger') ?>">
                      <?= $num($taskP, 1) ?>%
                    </span>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($sfP !== null): ?>
                    <span class="badge bg-<?= $sfP >= 80 ? 'success' : ($sfP >= 60 ? 'warning' : 'danger') ?>-soft
                                          text-<?= $sfP >= 80 ? 'success' : ($sfP >= 60 ? 'warning' : 'danger') ?>">
                      <?= $num($sfP, 1) ?>%
                    </span>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($evalS !== null): ?>
                    <span class="fw-semibold"><?= $num($evalS, 1) ?>/5</span>
                    <?php if ($verdict): ?>
                      <div style="font-size:9px;" class="text-muted"><?= $esc($verdict) ?></div>
                    <?php endif; ?>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($comp !== null): ?>
                    <span class="fw-bold text-<?= $compCol ?>"><?= $num($comp, 1) ?></span>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td class="text-center">
                  <?php
                  $g   = $this->Team_progress_model ?? null;
                    $gradeFromComp = match(true) {
                        $comp !== null && $comp >= 90 => 'A+',
                        $comp !== null && $comp >= 80 => 'A',
                        $comp !== null && $comp >= 70 => 'B+',
                        $comp !== null && $comp >= 60 => 'B',
                        $comp !== null && $comp >= 50 => 'C',
                        default                       => 'D',
                    };
                    $gc = $gradeColor($gradeFromComp);
                  ?>
                  <span class="badge bg-<?= $gc ?>"><?= $gradeFromComp ?></span>
                </td>
                <td>
                  <a href="<?= site_url('teams/member_progress/' . (int)$m['id']) ?>"
                     class="btn btn-outline-primary btn-ssm" title="View full progress">
                    <i class="ti ti-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <!-- /Member Table -->

      <!-- Tasks breakdown -->
      <div class="solid-card mb-3">
        <div class="card-header py-1 px-1">
          <h6 class="mb-0 mt-0 text-primary"><i class="ti ti-checklist me-2"></i>Task Breakdown</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php
                $taskItems = [
                    ['Completed',   $tasks['completed']   ?? 0, 'success'],
                    ['In Progress', $tasks['in_progress'] ?? 0, 'primary'],
                    ['In Review',   $tasks['in_review']   ?? 0, 'info'],
                    ['Not Started', $tasks['not_started'] ?? 0, 'secondary'],
                    ['On Hold',     $tasks['on_hold']     ?? 0, 'warning'],
                    ['Past Due',    $tasks['past_due']    ?? 0, 'danger'],
                ];
            foreach ($taskItems as [$label, $val, $col]):
            ?>
            <div class="col-6 col-md-4 col-lg-2-4">
              <div class="text-center">
                <div class="fw-bold text-<?= $col ?>" style="font-size:22px;"><?= (int)$val ?></div>
                <div class="text-muted small"><?= $label ?></div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (($tasks['total'] ?? 0) > 0): ?>
            <div class="col-12">
              <div class="progress" style="height:8px;border-radius:4px;">
                <?php $cp = $tasks['completion_pct'] ?? 0; ?>
                <div class="progress-bar bg-success" style="width:<?= $cp ?>%"
                     title="<?= $num($cp, 1) ?>% complete"></div>
              </div>
              <div class="d-flex justify-content-between mt-1">
                <small class="text-muted">0%</small>
                <small class="text-success fw-semibold"><?= $num($cp, 1) ?>% complete</small>
                <small class="text-muted">100%</small>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Evaluations summary -->
      <?php if ($evals['total'] > 0): ?>
      <div class="solid-card mb-3">
        <div class="card-header py-1 px-1">
          <h6 class="mb-0 mt-0 text-primary"><i class="ti ti-clipboard-check me-2"></i>Evaluations</h6>
        </div>
        <div class="card-body">
          <div class="row g-2 mb-3">
            <div class="col-md-3 text-center">
              <div class="fw-bold" style="font-size:24px;"><?= (int)$evals['total'] ?></div>
              <div class="text-muted small">Total Evals</div>
            </div>
            <div class="col-md-3 text-center">
              <div class="fw-bold text-warning" style="font-size:24px;">
                <?= $evals['avg_score'] !== null ? number_format($evals['avg_score'], 2) : '—' ?>
              </div>
              <div class="text-muted small">Avg Score / 5</div>
            </div>
            <?php foreach ($evals['verdicts'] ?? [] as $v => $cnt): ?>
            <div class="col-md-3 text-center">
              <div class="fw-bold" style="font-size:20px;"><?= (int)$cnt ?></div>
              <div class="text-muted small"><?= $esc($v) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <div class="col-12 col-xl-4">

      <?php if ($top): ?>
      <div class="solid-card mb-3">
        <div class="card-header py-1 px-1 mb-2">
          <h6 class="mb-0 mt-0 text-primary">
            <i class="ti ti-trophy me-2"></i>Top Performer
          </h6>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center gap-3 mb-3">
            <?= $mkAv($top, 52) ?>
            <div>
              <div class="fw-bold">
                <?= $esc($top['firstname'] . ' ' . $top['lastname']) ?>
              </div>
              <div class="text-muted small">
                <?= emp_id_display($top['emp_id'] ?? '') ?>
                <?php if (!empty($top['position_title'])): ?>
                  · <?= $esc($top['position_title']) ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php $tk = $top['kpi'] ?? []; ?>
          <div class="row g-2 text-center">
            <?php
            $topKpis = [
                ['Attendance', ($tk['att_pct']         ?? null) !== null ? $num($tk['att_pct'], 1) . '%' : '—'],
                ['Task %',     ($tk['task_pct']        ?? null) !== null ? $num($tk['task_pct'], 1) . '%' : '—'],
                ['Signoff %',  ($tk['signoff_pct']     ?? null) !== null ? $num($tk['signoff_pct'], 1) . '%' : '—'],
                ['Score',      ($tk['composite_score'] ?? null) !== null ? $num($tk['composite_score'], 1) : '—'],
            ];
            foreach ($topKpis as [$lbl, $val]):
            ?>
            <div class="col-6">
              <div class="bg-light-info rounded py-2 px-1">
                <div class="fw-bold small"><?= $val ?></div>
                <div class="text-muted" style="font-size:10px;"><?= $lbl ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-2 text-center">
            <a href="<?= site_url('teams/member_progress/' . (int)$top['id']) ?>"
               class="btn btn-light-primary btn-header w-100">
              <i class="ti ti-eye me-1"></i>View Full Progress
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Signoff Compliance -->
      <div class="solid-card mb-3">
        <div class="card-header py-1 px-1">
          <h6 class="mb-0 mt-0 text-primary"><i class="ti ti-check me-2"></i>Signoff Compliance</h6>
        </div>
        <div class="card-body text-center">
          <div style="font-size:36px;font-weight:800;"
               class="text-<?= ($sof['compliance_pct'] ?? 0) >= 80 ? 'success' : 'warning' ?>">
            <?= $num($sof['compliance_pct'] ?? 0, 1) ?>%
          </div>
          <div class="text-muted small mb-3">
            <?= (int)($sof['approved'] ?? 0) ?> approved
            of <?= (int)($sof['total'] ?? 0) ?> total
          </div>
          <div class="progress" style="height:10px;">
            <div class="progress-bar bg-<?= ($sof['compliance_pct'] ?? 0) >= 80 ? 'success' : 'warning' ?>"
                 style="width:<?= min(100, (float)($sof['compliance_pct'] ?? 0)) ?>%"></div>
          </div>
          <div class="row g-2 mt-2 text-center">
            <div class="col-4">
              <div class="small fw-semibold"><?= (int)($sof['total']    ?? 0) ?></div>
              <div class="text-muted" style="font-size:10px;">Total</div>
            </div>
            <div class="col-4">
              <div class="small fw-semibold text-success"><?= (int)($sof['approved'] ?? 0) ?></div>
              <div class="text-muted" style="font-size:10px;">Approved</div>
            </div>
            <div class="col-4">
              <div class="small fw-semibold text-warning"><?= (int)($sof['pending']  ?? 0) ?></div>
              <div class="text-muted" style="font-size:10px;">Pending</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Leave Summary -->
      <div class="solid-card mb-3">
        <div class="card-header py-1 px-1">
          <h6 class="mb-0 mt-0 text-primary"><i class="ti ti-beach me-2"></i>Leave Summary</h6>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span class="small text-muted">Total Requests</span>
            <span class="fw-semibold small"><?= (int)($leaves['total_requests'] ?? 0) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="small text-muted">Approved Days</span>
            <span class="fw-semibold small text-success">
              <?= number_format($leaves['approved_days'] ?? 0, 1) ?>
            </span>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span class="small text-muted">Pending Requests</span>
            <span class="fw-semibold small text-warning"><?= (int)($leaves['pending'] ?? 0) ?></span>
          </div>
          <?php if (!empty($leaves['by_type'])): ?>
          <hr class="my-2">
          <div class="small text-muted mb-2 fw-semibold">By Type</div>
          <?php foreach ($leaves['by_type'] as $lt): ?>
            <?php if ($lt['status'] !== 'approved') continue; ?>
            <div class="d-flex justify-content-between mb-1">
              <span class="small"><?= $esc($lt['leave_type']) ?></span>
              <span class="badge bg-light-success text-success small">
                <?= number_format($lt['total_days'], 1) ?> days
              </span>
            </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Team Score Summary -->
      <div class="solid-card mb-3">
        <div class="card-header py-1 px-1">
          <h6 class="mb-0 mt-0 text-primary"><i class="ti ti-chart-bar me-2"></i>Team Score Summary</h6>
        </div>
        <div class="card-body text-center py-4">
          <div style="font-size:52px;font-weight:900;line-height:1;"
               class="text-<?= $gradeCol ?>">
            <?= $esc($gradeStr) ?>
          </div>
          <div style="font-size:20px;font-weight:700;" class="mt-1">
            <?= $num($score['score'] ?? 0, 1) ?> / 100
          </div>
          <div class="text-muted small mt-1">
            Composite score across <?= (int)($score['member_count'] ?? 0) ?> members
          </div>
          <div class="mt-3">
            <div class="progress" style="height:12px;border-radius:6px;">
              <div class="progress-bar bg-<?= $gradeCol ?>"
                   style="width:<?= min(100, (float)($score['score'] ?? 0)) ?>%"></div>
            </div>
          </div>
          <div class="row g-2 mt-3 text-start">
            <div class="col-12">
              <small class="text-muted d-block mb-1">Score legend</small>
              <?php foreach (['A+' => [90, 'success'], 'A' => [80, 'success'], 'B+' => [70, 'primary'],
                              'B'  => [60, 'primary'], 'C' => [50, 'warning'], 'D' => [0, 'danger']]
                             as $g => [$min, $c]): ?>
              <span class="badge bg-<?= $c ?> me-1 mb-1"><?= $g ?> ≥ <?= $min ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
    <!-- /col right -->

  </div>
</div>