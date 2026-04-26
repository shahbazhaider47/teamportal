<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI   =& get_instance();
$user = (isset($user) && is_array($user)) ? $user : [];

$userId = isset($user['id']) ? (int)$user['id'] : 0;
if ($userId === 0) {
    echo '<div class="alert alert-warning">User context missing. Unable to render attendance report.</div>';
    return;
}

// Resolve year (GET param `yr` or current)
$requestedYear = (int) $CI->input->get('yr');
$currentYear   = $requestedYear > 2000 ? $requestedYear : (int) date('Y');

// Safe full name fallback chain
$fullNameRaw = $user['fullname']
    ?? trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
if ($fullNameRaw === '') {
    $fullNameRaw = $user['username'] ?? ($user['emp_id'] ?? '');
}
$userFullName = $fullNameRaw !== '' ? e($fullNameRaw) : '—';

// Status map (label + color + tooltip)
$statusConfig = [
    'P' => ['label' => 'Total Presents', 'color' => 'success',   'tooltip' => 'Working days'],
    'L' => ['label' => 'Casual Leaves',  'color' => 'warning',   'tooltip' => 'Casual Leaves'],
    'M' => ['label' => 'Medical Leaves', 'color' => 'danger',    'tooltip' => 'Sick days with documentation'],
    'S' => ['label' => 'Short Leaves',   'color' => 'info',      'tooltip' => 'Partial day absences'],
    'A' => ['label' => 'Absents',        'color' => 'dark',      'tooltip' => 'Unexcused absence'],
    'H' => ['label' => 'US Holidays',    'color' => 'primary',   'tooltip' => 'Company recognized holidays'],
    'E' => ['label' => 'Eid Holidays',   'color' => 'secondary', 'tooltip' => 'Religious holidays'],
];

// Seed matrix: status x 1..12 months = 0
$matrix = [];
foreach (array_keys($statusConfig) as $code) {
    $matrix[$code] = array_fill(1, 12, 0);
}

// Single grouped query (fast)
$sql = "
    SELECT status, MONTH(attendance_date) AS m, COUNT(*) AS c
    FROM attendance
    WHERE user_id = ?
      AND YEAR(attendance_date) = ?
      AND status IN (" . implode(',', array_map(fn($s)=>$CI->db->escape($s), array_keys($statusConfig))) . ")
    GROUP BY status, m
";
$q = $CI->db->query($sql, [$userId, $currentYear]);
foreach ($q->result_array() as $row) {
    $s = $row['status'];
    $m = (int)$row['m'];
    $c = (int)$row['c'];
    if (isset($matrix[$s][$m])) {
        $matrix[$s][$m] = $c;
    }
}

// Yearly totals by status + compute quick KPIs
$yearlyTotals = array_fill_keys(array_keys($statusConfig), 0);
$monthlyTotals = array_fill(1, 12, 0);

foreach ($matrix as $s => $months) {
    $yearlyTotals[$s] = array_sum($months);
    foreach ($months as $m => $c) $monthlyTotals[$m] += $c;
}

$totalWorking = $yearlyTotals['P'] ?? 0;
$totalLeaves  = ($yearlyTotals['L'] ?? 0) + ($yearlyTotals['M'] ?? 0) + ($yearlyTotals['S'] ?? 0);
$totalAbsents = $yearlyTotals['A'] ?? 0;
$totalDays    = array_sum($monthlyTotals);
$attendanceRate = $totalDays > 0 ? round(($totalWorking / $totalDays) * 100) : 0;

// Helper: build base URL with yr param swapped
$baseUrl = current_url();
$query   = $CI->input->get();
unset($query['yr']);
$makeYearUrl = function($yr) use ($baseUrl, $query) {
    $q = $query;
    $q['yr'] = $yr;
    return $baseUrl . (empty($q) ? '' : '?' . http_build_query($q));
};
?>

<div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab" tabindex="0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <h6 class="mb-0">Annual Attendance Report</h6>
                    <div class="small text-muted">
                    </div>
                </div>
                <div class="vr d-none d-md-block"></div>
                <?php
                // Keep these two vars straight:
                $selectedYear = (int) $currentYear;   // whatever you've resolved from ?yr=... or default
                $nowYear      = (int) date('Y');      // actual calendar year
                
                // Build a symmetric window: last 3 years up to next 1 year,
                // but ensure it also spans the selected year (even if it's outside the window).
                $start = min($selectedYear, $nowYear) - 3;
                $end   = max($selectedYear, $nowYear) + 1;
                
                // Guard rails
                $start = max(2000, $start);
                $end   = max($start, $end);
                ?>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Year</span>
                    <select class="form-select form-select-sm w-auto" onchange="if(this.value){window.location=this.value;}">
                        <?php for ($yr = $end; $yr >= $start; $yr--): ?>
                            <option value="<?= e($makeYearUrl($yr)) ?>" <?= $yr === $selectedYear ? 'selected' : '' ?>>
                                <?= $yr ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="pill pill-success">
                Present: <?= $totalWorking ?>
                </span>
                <span class="pill pill-warning">
                Leaves: <?= $totalLeaves ?>
                </span>
                <span class="pill pill-danger">
                Absents: <?= $totalAbsents ?>
                </span>
                <span class="pill pill-info">
                Rate: <?= $attendanceRate ?>%
                </span>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-sm small table-hover align-middle table-bottom-border app-attendance-table">
                <thead class="bg-light-primary">
                    <tr>
                        <th class="text-start ps-3">Status</th>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <th class="text-center"><?= date('M', mktime(0,0,0,$m,1)) ?></th>
                        <?php endfor; ?>
                        <th class="text-center fw-bold">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($statusConfig as $code => $cfg): ?>
                    <?php $rowTotal = array_sum($matrix[$code]); ?>
                    <tr>
                        <td class="ps-3">
                            <span class="badge bg-<?= $cfg['color'] ?> bg-opacity-10 text-<?= $cfg['color'] ?> me-2">
                                <?= $code ?>
                            </span>
                            <?= e($cfg['label']) ?>
                        </td>
                        <?php for ($m = 1; $m <= 12; $m++): $c = (int)$matrix[$code][$m]; ?>
                            <td class="text-center <?= $c ? 'fw-semibold' : 'text-muted' ?>">
                                <?= $c ?: '-' ?>
                            </td>
                        <?php endfor; ?>
                        <td class="text-center fw-semibold bg-light-primary">
                            <?= $rowTotal ?: '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
