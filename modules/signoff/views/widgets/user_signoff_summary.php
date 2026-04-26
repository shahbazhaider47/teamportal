<?php defined('BASEPATH') OR exit('No direct script access allowed');
$ms = isset($month_stats) && is_array($month_stats) ? $month_stats : [];
$msLabel      = $ms['month_label']        ?? date('F Y');
$msWorking    = (int)($ms['working_days']      ?? 0);  // full month
$msWorkingPast= (int)($ms['working_days_past'] ?? 0);  // past only
$msUpcoming   = (int)($ms['upcoming']          ?? 0);
$msSubmit     = (int)($ms['submitted']         ?? 0);
$msMissed     = (int)($ms['missed']            ?? 0);
$msExcused    = (int)($ms['excused']           ?? 0);
$msOnLeave    = (int)($ms['on_leave']          ?? 0);
$msHolidays   = (int)($ms['holidays']          ?? 0);
$msPending    = (int)($ms['pending']           ?? 0);
$msRate       = (float)($ms['compliance_rate'] ?? 0.0);
$msDays       = isset($ms['days']) && is_array($ms['days']) ? $ms['days'] : [];
$msFrom       = $ms['range']['from'] ?? date('Y-m-01');
$msTo         = $ms['range']['to']   ?? date('Y-m-t');

$today        = date('Y-m-d');

// Compliance bar colour
$msBarCls = $msRate >= 80 ? 'success' : ($msRate >= 50 ? 'warning' : 'danger');

// Day-type display config  [label, colour, icon]
$dayTypeMeta = [
    'submitted' => ['Submitted',  'success',   'ti-circle-check'],
    'pending'   => ['Pending',    'info',      'ti-clock'],
    'missed'    => ['Missed',     'danger',    'ti-circle-x'],
    'excused'   => ['Excused',    'warning',   'ti-circle-check'],
    'on_leave'  => ['On Leave',   'warning',   'ti-beach'],
    'holiday'   => ['Holiday',    'primary',   'ti-flag'],
    'off_day'   => ['Day Off',    'secondary', 'ti-moon'],
    'upcoming'  => ['Upcoming',   'secondary', 'ti-calendar-event'],
    'working'   => ['Working',    'secondary', 'ti-calendar'],
];
?>

<div class="row g-2 mb-3" id="signoff-summary-cards">

<?php
$signoff_metrics = [
    ['Signoff Days', $msWorking, 'ti ti-calendar-stats', '#6366f118', $msLabel],
    ['Submitted', $msSubmit, 'ti ti-circle-check', '#16a34a18', "of $msWorkingPast past days"],
    ['Missed', $msMissed, 'ti ti-circle-x', '#ef444418', 'Past working days'],
    ['Excused', $msExcused, 'ti ti-beach', '#f59e0b18', "$msOnLeave on leave"],
    ['Pending', $msPending, 'ti ti-clock', '#0ea5e918', 'Awaiting review'],
    ['Compliance', $msRate.'%', 'ti ti-chart-pie', '#6366f118', 'Past days only'],
];
?>

<?php foreach ($signoff_metrics as $m): ?>
<div class="col">
    <div class="kpi-card">
        <div class="kpi-icon" style="background:<?= $m[3] ?>;">
            <i class="<?= $m[2] ?>"></i>
        </div>
        <div>
            <div class="kpi-value"><?= $m[1] ?></div>
            <div class="kpi-label"><?= $m[0] ?></div>
            <div class="kpi-subtext"><?= $m[4] ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>

</div>

<?php if (!empty($msDays)): ?>
<div class="solid-card mb-4" id="signoff-day-calendar">

  <!-- Card header: title + legend -->
  <div class="d-flex align-items-start justify-content-between px-3 pt-3 pb-2 flex-wrap gap-2">
    <div>
      <span class="h6 mb-0">
        <i class="ti ti-calendar-month me-1 text-primary"></i>
        <?= html_escape($msLabel) ?> — Day Breakdown
      </span>
      <div class="text-muted mt-1" style="font-size:.75rem;">
        <?= $msWorkingPast ?> past working days &nbsp;·&nbsp;
        <?= $msUpcoming ?> upcoming &nbsp;·&nbsp;
        <?php if ($msHolidays > 0): ?>
          <?= $msHolidays ?> public holiday<?= $msHolidays > 1 ? 's' : '' ?> &nbsp;·&nbsp;
        <?php endif; ?>
        Compliance based on past days only
      </div>
    </div>

    <!-- Legend -->
    <div class="d-flex flex-wrap gap-2 align-items-center" style="font-size:.71rem;">
      <?php
        $legend = [
          ['Submitted',  'success'],
          ['Pending',    'info'],
          ['Missed',     'danger'],
          ['Excused',    'warning'],
          ['Holiday',    'primary'],
          ['Upcoming',   'secondary'],
          ['Day Off',    'light'],
        ];
        foreach ($legend as [$lbl, $cls]):
          $dot = $cls === 'light'
              ? 'background:#e9ecef;border:1px solid #ced4da;'
              : "background:var(--bs-{$cls},#6c757d);";
      ?>
        <span class="d-flex align-items-center gap-1">
          <span class="rounded-circle d-inline-block flex-shrink-0"
                style="width:9px;height:9px;<?= $dot ?>"></span>
          <?= $lbl ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card-body pt-1">

    <!-- Grid header: day-of-week abbreviations -->
    <?php
      // Build a 7-column grid starting from Mon to line up the cells correctly.
      // Find which day of week the first of the month falls on.
      $gridStart = new DateTime($msFrom);
      $gridStart->modify('monday this week'); // roll back to Monday of the week containing day 1
      $gridEnd   = new DateTime($msTo);
      $gridEnd->modify('sunday this week');   // roll forward to Sunday of the week containing last day

      $dowLabels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    ?>

    <!-- Week-day header row -->
    <div class="signoff-calendar-header mb-1">
      <?php foreach ($dowLabels as $dl): ?>
        <div><?= $dl ?></div>
      <?php endforeach; ?>
    </div>

    <!-- Build a lookup: date => day row -->
    <?php
      $dayLookup = [];
      foreach ($msDays as $d) { $dayLookup[$d['date']] = $d; }
    ?>

    <!-- Calendar rows: one row per week -->
    <?php
      $cur = clone $gridStart;
      while ($cur <= $gridEnd):
        $weekStart = clone $cur;
    ?>
      <div class="signoff-calendar-grid mb-1">
        <?php for ($col = 0; $col < 7; $col++):
          $cellDate = $cur->format('Y-m-d');
          $inMonth  = ($cellDate >= $msFrom && $cellDate <= $msTo);
          $day      = $dayLookup[$cellDate] ?? null;
          $isToday  = ($cellDate === $today);

          if (!$inMonth || $day === null):
            // Padding cell (outside month)
        ?>
          <div class="signoff-day-cell empty"></div>
        <?php else:
            $dt   = $day['type'] ?? 'working';
            $meta = $dayTypeMeta[$dt] ?? $dayTypeMeta['working'];
            $isFuture = !empty($day['is_future']);

            // Tooltip text
            $tip = $cellDate . ' (' . $day['dow'] . ') — ' . $meta[0];
            if (!empty($day['holiday'])) { $tip .= ': ' . $day['holiday']; }
            if (!empty($day['leave']))   { $tip .= ' (' . $day['leave'] . ')'; }

            // Cell styling
            $bgCls   = 'bg-light-' . $meta[1];
            $txtCls  = 'text-' . $meta[1];
            $opacity = $isFuture ? 'opacity:0.55;' : '';
            $ring    = $isToday
                ? 'outline:2px solid var(--bs-primary);outline-offset:1px;'
                : '';
        ?>
            <div class="signoff-day-cell text-center rounded <?= $bgCls ?>"
                 style="cursor:default;<?= $opacity . $ring ?>"
                 title="<?= html_escape($tip) ?>"
                 data-bs-toggle="tooltip"
                 data-bs-placement="top">

            <!-- Day number -->
            <div class="fw-bold <?= $txtCls ?>"
                 style="font-size:.8rem;line-height:1.2;">
              <?= (int)substr($cellDate, 8, 2) ?>
              <?php if ($isToday): ?>
                <span style="font-size:.55rem;display:block;line-height:1;"
                      class="text-primary fw-bold">TODAY</span>
              <?php endif; ?>
            </div>

            <div class="<?= $txtCls ?> d-flex align-items-center justify-content-center gap-1 mt-1"
                 style="font-size:.58rem;line-height:1;">
                 
                <i class="ti <?= $meta[2] ?>"></i>
            
                <span class="d-none d-sm-inline"
                      style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= $isFuture && $dt === 'upcoming' ? '—' : $meta[0] ?>
                </span>
            
            </div>

          </div>
        <?php endif; ?>
        <?php $cur->modify('+1 day'); endfor; ?>
      </div>
    <?php endwhile; ?>

    <!-- Footer: supplementary counts + missed alert -->
    <?php if ($msHolidays > 0 || $msOnLeave > 0 || $msMissed > 0): ?>
      <div class="d-flex flex-wrap gap-3 mt-3 pt-2 border-top small text-muted">
        <?php if ($msHolidays > 0): ?>
          <span>
            <i class="ti ti-flag me-1 text-primary"></i>
            <?= $msHolidays ?> Public Holiday<?= $msHolidays > 1 ? 's' : '' ?>
          </span>
        <?php endif; ?>
        <?php if ($msOnLeave > 0): ?>
          <span>
            <i class="ti ti-beach me-1 text-warning"></i>
            <?= $msOnLeave ?> Day<?= $msOnLeave > 1 ? 's' : '' ?> on Approved Leave
          </span>
        <?php endif; ?>
        <?php if ($msMissed > 0): ?>
          <span class="text-danger fw-semibold">
            <i class="ti ti-alert-triangle me-1"></i>
            <?= $msMissed ?> Missed —
            <a href="<?= site_url('signoff/signoff_history') ?>" class="text-danger">
              View History
            </a>
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div><!-- /card-body -->
</div><!-- /CALENDAR -->

<script>
(function () {
  // Activate Bootstrap tooltips on all day cells
  var cells = document.querySelectorAll('#signoff-day-calendar [data-bs-toggle="tooltip"]');
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    cells.forEach(function (el) {
      new bootstrap.Tooltip(el, { trigger: 'hover', boundary: 'window' });
    });
  }
})();
</script>
<?php endif; ?>

<style>
.signoff-calendar-header{
    display:grid;
    grid-template-columns:repeat(7,1fr);
    gap:4px;
}

.signoff-calendar-header div{
    text-align:center;
    font-size:.70rem;
    font-weight:600;
    color:#94a3b8;
}

.signoff-calendar-grid{
    display:grid;
    grid-template-columns:repeat(7,1fr);
    gap:4px;
}

.signoff-day-cell{
    height:68px;
    padding:6px 4px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    border-radius:8px;
}

.signoff-day-cell.empty{
    background:transparent;
}    
</style>