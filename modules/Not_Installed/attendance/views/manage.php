<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Standardize: always use $currentMonth and $currentYear everywhere below
$currentYear  = isset($currentYear)  ? (int)$currentYear  : (isset($year) ? (int)$year : (int)date('Y'));
$currentMonth = isset($currentMonth) ? (int)$currentMonth : (isset($month) ? (int)$month : (int)date('m'));

// For backward compatibility if old code still uses $year/$month
$year  = $currentYear;
$month = $currentMonth;

// Calculate previous/next for navigation
$prevMonth = $currentMonth - 1;
$prevYear  = $currentYear;
$nextMonth = $currentMonth + 1;
$nextYear  = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
$months = [
  1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
  7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'
];
?>

<?php
// ---- Lightweight client-side (view-only) sorting ----
// Accept ?sort=created to sort by oldest account first; default = EMP ID ascending
$sort = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'emp';

// Normalize EMP ID (e.g., "RCM-00123" -> 123; "123" -> 123; anything else -> 0)
$empNum = function ($empId) {
    $empId = (string)($empId ?? '');
    // If contains a dash, use the last segment; else use the whole string
    $tail  = (strpos($empId, '-') !== false) ? substr($empId, strrpos($empId, '-') + 1) : $empId;
    // Keep only digits and cast
    $num   = preg_replace('/\D+/', '', $tail);
    return (int)$num;
};

// Stable-ish sort: tie-break with `id`
usort($users, function ($a, $b) use ($sort, $empNum) {
    if ($sort === 'created') {
        $ca = $a['created_at'] ?? '0000-00-00 00:00:00';
        $cb = $b['created_at'] ?? '0000-00-00 00:00:00';
        if ($ca === $cb) {
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        }
        return strcmp($ca, $cb); // ASC = oldest first
    }

    // Default: EMP ID ascending (small -> large)
    $ea = $empNum($a['emp_id'] ?? '');
    $eb = $empNum($b['emp_id'] ?? '');
    if ($ea === $eb) {
        return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
    }
    return $ea <=> $eb;
});
?>


<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport           = staff_can('export', 'general');
          $canPrint            = staff_can('print', 'general');
        ?>

        <a href="<?= site_url('attendance') ?>"
           class="btn btn-primary btn-header">
            <i class="ti ti-clock"></i> Attendance
        </a>
        <a href="<?= site_url('attendance/leaves') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-clipboard-list"></i> Leaves
        </a>
        <a href="<?= site_url('attendance/calendar') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-calendar-event"></i> Calendar
        </a>
        <a href="<?= site_url('attendance/tracker') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-map-pin"></i> Tracker
        </a>

        <a href="<?= site_url('attendance/biometric') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-fingerprint"></i> Biometric
        </a>
        
        <div class="btn-divider"></div>
        
        <!-- Search -->
        <div class="input-group small app-form dynamic-search-container" style="width: 200px;">
          <input type="text" class="form-control rounded app-form small dynamic-search-input" 
                 placeholder="Search..." 
                 aria-label="Search"
                 data-table-target="<?= $table_id ?? 'attendanceTable' ?>">
          <button class="btn btn-outline-secondary dynamic-search-clear" type="button" style="display: none;"></button>
        </div>
     
        <!-- Export -->
        <?php if ($canExport): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                  title="Export to Excel"
                  data-export-filename="<?= $page_title ?? 'export' ?>">
            <i class="ti ti-download"></i>
          </button>
        <?php endif; ?>
    
        <!-- Print -->
        <?php if ($canPrint): ?>
          <button type="button"
                  class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
                  title="Print Table">
            <i class="ti ti-printer"></i>
          </button>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="d-flex align-items-center justify-content-between mt-5 mb-2" style="font-size:13px;">
      <!-- Left: Month Navigation -->
      <div class="d-flex align-items-center" style="gap:6px;">
        <a href="<?= base_url('attendance?year='.$prevYear.'&month='.$prevMonth) ?>"
           class="btn btn-light-primary btn-xs px-1 py-0"
           title="Previous Month"
           style="font-size:13px; min-width:28px; height:28px; display:flex; align-items:center; justify-content:center;">
          <i class="fas fa-chevron-left" style="font-size:13px;"></i>
        </a>
        <span style="font-weight:600; font-size:13px; padding:0 4px;">
          <?= $months[(int)$month] . ' ' . $year ?>
        </span>
        <a href="<?= base_url('attendance?year='.$nextYear.'&month='.$nextMonth) ?>"
           class="btn btn-light-primary btn-xs px-1 py-0"
           title="Next Month"
           style="font-size:12px; min-width:28px; height:28px; display:flex; align-items:center; justify-content:center;">
          <i class="fas fa-chevron-right" style="font-size:12px;"></i>
        </a>
        <a href="<?= base_url('attendance?year='.date('Y').'&month='.date('m')) ?>"
           class="btn btn-primary btn-xs ms-2 px-2 py-0"
           title="Go to Current Month"
           style="font-size:12px; min-width:38px; height:28px; display:flex; align-items:center;"> 
          <i class="fas fa-calendar-day me-2" style="font-size:12px; margin-right:2px;"></i> Current
        </a>
            <?php if (staff_can('view_global', 'attendance')): ?>
              <button type="button" id="editAttendanceBtn" class="btn btn-warning btn-header">
                <i class="fas fa-edit me-1"></i> Edit
              </button>
              <button type="button" id="cancelEditBtn" class="btn btn-secondary btn-header" style="display:none;">
                <i class="fas fa-times me-1"></i> Cancel
              </button>
            <?php endif; ?>              
            <select id="bulkStatusSelect" class="form-select btn-sm btn btn-light-primary dropdown-toggle" style="font-size:12px; width: auto;">
              <option value="P">P = Present</option>
              <option value="C">C = Casual Leave</option>
              <option value="M">M = Medical Leave</option>
              <option value="S">S = Short Leave</option>
              <option value="H">H = US Holiday</option>
              <option value="E">E = Eid Holiday</option>
              <option value="A">A = Absent</option>
            </select>
            <button type="button" id="applyBulkBtn" class="btn btn-header btn-light-primary"
              <?= staff_can('create', 'attendance') ? '' : 'disabled' ?>
              <?= staff_can('create', 'attendance') ? '' : 'title="You do not have permission to create attendance."' ?>>
              Apply Bulk
            </button>
      </div>
    
      <!-- Right: Scroll Arrows -->
      <div class="attendance-scroll-arrows d-flex align-items-center mb-1" style="gap:6px;">
        <button type="button" id="scrollLeft"
                class="btn btn-light-primary btn-xs px-1 py-0"
                title="Scroll left"
                style="font-size:12px; min-width:24px; height:24px; display:flex; align-items:center; justify-content:center;">
          <i class="fas fa-chevron-left" style="font-size:12px;"></i>
        </button>
        <span class="text-muted ms-2" style="font-size:10px;">Scroll Table</span>
        <button type="button" id="scrollRight"
                class="btn btn-light-primary btn-xs px-1 py-0"
                title="Scroll right"
                style="font-size:12px; min-width:24px; height:24px; display:flex; align-items:center; justify-content:center;">
          <i class="fas fa-chevron-right" style="font-size:12px;"></i>
        </button>
      </div>
    </div>
          
    <?= form_open(base_url('attendance/save'), ['method' => 'post', 'class' => 'overflow-auto']) ?>
        <input type="hidden" name="year"  value="<?= (int)$currentYear ?>">
        <input type="hidden" name="month" value="<?= (int)$currentMonth ?>">
      <div class="card">
      <div class="card-body">
                  <!-- Legend/Explanation Line (always below toolbar, above table) -->
        <p class="text-muted mb-2 small"> <?= htmlspecialchars(date('l, F j, Y'), ENT_QUOTES) ?> | <span class="text-muted mb-1" style="font-size: 10px;">P = Present | C = Casual Leave | M = Medical Leave | S = Short Leave | H = US Holiday | E = Eid Holiday | A = Absent</span></p>
      <div class="table-responsive app-scroll" id="attendanceTableScroll">
      <table class="table table-sm table-bordered text-center table-hover attendance-grid align-middle" id="attendanceTable">
      <thead class="table-light">
        <tr>
          <th style="text-align: left; vertical-align: middle; font-size: 12px; padding-left: 10px; min-width: 90px;">Emp ID</th>
          <th style="text-align: left; vertical-align: middle; font-size: 12px; padding-left: 10px; min-width: 190px;">Employee Name</th>
          <?php foreach ($allDays as $info): ?>
            <th class="<?= $info['isWeekend'] ? 'weekend' : '' ?>">
              <?= sprintf('%02d', $info['day']) ?><br>
              <?= strtoupper(date('D', strtotime($info['dateStr']))) ?>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="text-muted" style="text-align: left; vertical-align: middle; font-size: 10px; padding-left: 10px"> <?= emp_id_display($u['emp_id']) ?> </td>
            <td class="text-muted" style="text-align: left; vertical-align: middle; font-size: 10px; padding-left: 10px;"> <?= html_escape($u['firstname'] . ' ' . $u['lastname']) ?> </td>
            <?php foreach ($allDays as $info):
              $d      = $info['day'];
              $val    = $existing[$u['id']][$d] ?? '';
              $isToday = (
                (int)$d === (int)date('j') &&
                (int)$month === (int)date('n') &&
                (int)$year === (int)date('Y')
            );
            ?>
            <td class="<?= $info['isWeekend'] ? 'weekend' : '' ?>">
              <?php
                $input_name = "attendance[{$u['id']}][{$d}]";
                $input_val  = html_escape($val);
                $isWeekend  = $info['isWeekend'];
              ?>
              <?php if ($isWeekend): ?>
                <div class="attendance-status-box status-NA attendance-box" style="opacity: 0.7;">
                </div>
                <?php elseif ($isToday): ?>
                  <input type="text"
                         name="<?= $input_name ?>"
                         value="<?= $input_val ?>"
                         maxlength="1"
                         class="form-control app-form today-input attendance-input"
                         style="width:1.5rem; text-align:center; font-size:12px; margin:auto"
                         <?= staff_can('create', 'attendance') ? '' : 'disabled title="You do not have permission to mark today\'s attendance."' ?>>
              <?php else: ?>
                <div class="attendance-status-box status-<?= $input_val !== '' ? $input_val : 'NA' ?> attendance-box">
                  <?= $input_val ?>
                </div>
                <input type="text"
                       name="<?= $input_name ?>"
                       value="<?= $input_val ?>"
                       maxlength="1"
                       class="form-control app-form attendance-input"
                       style="width:1.5rem; text-align:center; font-size:12px; margin:auto; display:none;">
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    </div>
    </div>

            <?php if (staff_can('create', 'attendance')): ?>
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-save me-1"></i> Save Attendance
              </button>
            <?php else: ?>
              <span title="You do not have permission to create attendance.">
                <button type="button"
                  class="btn btn-outline-secondary btn-sm d-lg-inline-flex align-items-center disabled"
                  disabled
                  tabindex="-1">
                  Save Attendance <i class="ti ti-alert-triangle ms-3"></i>
                </button>
              </span>
            <?php endif; ?>
            
  <?= form_close() ?>
</div>