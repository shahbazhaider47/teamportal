<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$allTeams     = $all_teams     ?? [];
$top3         = $top_3         ?? [];
$teamOfMonth  = $team_of_month ?? null;
$teamOfYear   = $team_of_year  ?? null;
$year         = (int)($year    ?? date('Y'));
$month        = (int)($month   ?? date('n'));

$months = [
    1=>'January',2=>'February',3=>'March',4=>'April',
    5=>'May',6=>'June',7=>'July',8=>'August',
    9=>'September',10=>'October',11=>'November',12=>'December',
];

$gradeColor = fn(string $g): string => match($g) {
    'A+','A' => 'success',
    'B+','B' => 'primary',
    'C'      => 'warning',
    default  => 'danger',
};

$esc = fn($v) => html_escape($v ?? '');
?>

<div class="container-fluid">

  <!-- ── Header ─────────────────────────────────────────────────── -->
  <div class="d-flex align-items-center justify-content-between
              bg-light-secondary page-header gap-3 px-3 py-2 mb-3 rounded-3">
    <div class="d-flex align-items-center gap-2">
      <i class="ti ti-trophy text-warning fs-5"></i>
      <h1 class="h6 mb-0">Team Rankings</h1>
      <span class="badge bg-light-primary">
        <?= $esc($months[$month] ?? '') ?> <?= $year ?>
      </span>
    </div>
    <form method="get" class="d-flex gap-2 align-items-center">
      <select name="month" class="form-select form-select-sm" style="width:130px">
        <?php foreach ($months as $mn => $ml): ?>
          <option value="<?= $mn ?>" <?= $mn === $month ? 'selected' : '' ?>><?= $ml ?></option>
        <?php endforeach; ?>
      </select>
      <select name="year" class="form-select form-select-sm" style="width:90px">
        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
          <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button class="btn btn-primary btn-sm">
        <i class="ti ti-filter me-1"></i>Filter
      </button>
    </form>
  </div>

  <!-- ── Team of Month + Team of Year ──────────────────────────── -->
  <div class="row g-3 mb-3">

    <!-- Team of the Month -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100"
           style="border-radius:14px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#b45309 0%,#d97706 100%);padding:16px 20px;">
          <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-calendar-star text-white" style="font-size:20px;"></i>
            <span style="font-size:13px;font-weight:700;color:#fff;letter-spacing:.04em;text-transform:uppercase;">
              Team of the Month
            </span>
          </div>
          <div style="font-size:11px;color:rgba(255,255,255,0.7);">
            <?= $esc($months[$month] ?? '') ?> <?= $year ?>
          </div>
        </div>
        <div class="card-body d-flex align-items-center gap-3">
          <?php if ($teamOfMonth): ?>
            <div style="width:54px;height:54px;border-radius:50%;
                        background:linear-gradient(135deg,#b45309,#d97706);
                        display:flex;align-items:center;justify-content:center;
                        font-size:22px;font-weight:900;color:#fff;flex-shrink:0;">
              🏆
            </div>
            <div class="flex-grow-1">
              <div class="fw-bold" style="font-size:17px;">
                <?= $esc($teamOfMonth['team_name']) ?>
              </div>
              <div class="text-muted small"><?= $esc($teamOfMonth['dept'] ?? '—') ?></div>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge bg-<?= $gradeColor($teamOfMonth['grade'] ?? 'D') ?>">
                  <?= $esc($teamOfMonth['grade']) ?>
                </span>
                <span class="small fw-semibold">
                  Score: <?= number_format($teamOfMonth['score'], 1) ?>
                </span>
                <span class="text-muted small">
                  <?= (int)$teamOfMonth['member_count'] ?> members
                </span>
              </div>
            </div>
            <a href="<?= site_url('teams/team_progress/' . (int)$teamOfMonth['team_id']) ?>"
               class="btn btn-outline-warning btn-sm">
              <i class="ti ti-eye"></i>
            </a>
          <?php else: ?>
            <div class="text-muted fst-italic small">No data for this period.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Team of the Year -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100"
           style="border-radius:14px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);padding:16px 20px;">
          <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-award text-white" style="font-size:20px;"></i>
            <span style="font-size:13px;font-weight:700;color:#fff;letter-spacing:.04em;text-transform:uppercase;">
              Team of the Year
            </span>
          </div>
          <div style="font-size:11px;color:rgba(255,255,255,0.7);">
            Full year <?= $year ?>
          </div>
        </div>
        <div class="card-body d-flex align-items-center gap-3">
          <?php if ($teamOfYear): ?>
            <div style="width:54px;height:54px;border-radius:50%;
                        background:linear-gradient(135deg,#1e40af,#3b82f6);
                        display:flex;align-items:center;justify-content:center;
                        font-size:22px;font-weight:900;color:#fff;flex-shrink:0;">
              🥇
            </div>
            <div class="flex-grow-1">
              <div class="fw-bold" style="font-size:17px;">
                <?= $esc($teamOfYear['team_name']) ?>
              </div>
              <div class="text-muted small"><?= $esc($teamOfYear['dept'] ?? '—') ?></div>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge bg-<?= $gradeColor($teamOfYear['grade'] ?? 'D') ?>">
                  <?= $esc($teamOfYear['grade']) ?>
                </span>
                <span class="small fw-semibold">
                  Score: <?= number_format($teamOfYear['score'], 1) ?>
                </span>
                <span class="text-muted small">
                  <?= (int)$teamOfYear['member_count'] ?> members
                </span>
              </div>
            </div>
            <a href="<?= site_url('teams/team_progress/' . (int)$teamOfYear['team_id']) ?>"
               class="btn btn-outline-primary btn-sm">
              <i class="ti ti-eye"></i>
            </a>
          <?php else: ?>
            <div class="text-muted fst-italic small">No data for <?= $year ?>.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Top 3 Podium ───────────────────────────────────────────── -->
  <?php if (!empty($top3)): ?>
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-light-secondary py-2 px-3">
      <h6 class="mb-0 text-primary">
        <i class="ti ti-podium me-2"></i>Top 3 Teams —
        <?= $esc($months[$month] ?? '') ?> <?= $year ?>
      </h6>
    </div>
    <div class="card-body">
      <div class="row g-3 justify-content-center">
        <?php
        $medals = ['🥇', '🥈', '🥉'];
        $podiumH = ['120px', '90px', '70px'];
        foreach ($top3 as $ri => $t):
          $gc = $gradeColor($t['grade']);
        ?>
        <div class="col-md-4">
          <div class="card border-<?= $gc ?> h-100 text-center"
               style="border-width:2px!important;">
            <div class="card-body py-3">
              <div style="font-size:32px;"><?= $medals[$ri] ?? '' ?></div>
              <div class="fw-bold mt-1" style="font-size:16px;">
                <?= $esc($t['team_name']) ?>
              </div>
              <div class="text-muted small mb-2"><?= $esc($t['dept'] ?? '—') ?></div>
              <div class="mb-1">
                <span class="badge bg-<?= $gc ?> fs-6"><?= $esc($t['grade']) ?></span>
              </div>
              <div class="fw-bold" style="font-size:22px;">
                <?= number_format($t['score'], 1) ?>
              </div>
              <div class="text-muted small">
                <?= (int)$t['member_count'] ?> members
              </div>
              <?php if (!empty($t['lead_name'])): ?>
              <div class="text-muted" style="font-size:10px;margin-top:4px;">
                Lead: <?= $esc($t['lead_name']) ?>
              </div>
              <?php endif; ?>
              <a href="<?= site_url('teams/team_progress/' . (int)$t['team_id']) ?>"
                 class="btn btn-outline-<?= $gc ?> btn-sm mt-2 w-100">
                <i class="ti ti-eye me-1"></i>View Progress
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Full Rankings Table ────────────────────────────────────── -->
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-light-secondary py-2 px-3 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 text-primary">
        <i class="ti ti-list-numbers me-2"></i>All Teams Ranked
      </h6>
      <span class="badge bg-light-secondary text-muted"><?= count($allTeams) ?> teams</span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($allTeams)): ?>
        <div class="p-4 text-center text-muted fst-italic">
          No team data available for this period.
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm small align-middle mb-0 table-box-hover">
          <thead class="bg-light-primary">
            <tr>
              <th width="50" class="text-center">Rank</th>
              <th>Team</th>
              <th>Department</th>
              <th>Lead</th>
              <th class="text-center">Members</th>
              <th class="text-center">Score</th>
              <th class="text-center">Grade</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($allTeams as $ri => $t):
            $gc      = $gradeColor($t['grade']);
            $rankNum = $ri + 1;
            $isTop3  = $rankNum <= 3;
            $medal   = ['🥇','🥈','🥉'][$ri] ?? '';
          ?>
          <tr class="<?= $isTop3 ? 'table-light' : '' ?>">
            <td class="text-center fw-bold">
              <?php if ($isTop3): ?>
                <span style="font-size:18px;"><?= $medal ?></span>
              <?php else: ?>
                <span class="text-muted"><?= $rankNum ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= $esc($t['team_name']) ?></div>
            </td>
            <td class="text-muted"><?= $esc($t['dept'] ?? '—') ?></td>
            <td>
              <?php if (!empty($t['lead_name'])): ?>
                <div class="small"><?= $esc($t['lead_name']) ?></div>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-center"><?= (int)$t['member_count'] ?></td>
            <td class="text-center fw-bold text-<?= $gc ?>">
              <?= number_format($t['score'], 1) ?>
            </td>
            <td class="text-center">
              <span class="badge bg-<?= $gc ?>"><?= $esc($t['grade']) ?></span>
            </td>
            <td>
              <a href="<?= site_url('teams/team_progress/' . (int)$t['team_id']) ?>"
                 class="btn btn-outline-primary btn-ssm">
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

</div>