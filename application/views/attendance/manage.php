<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $CI =& get_instance(); 
$canView    = staff_can('view_global', 'attendance');
?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Attendance') ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if ($canView): ?>
        <?php echo $CI->load->view('attendance/partials/att_admin_menu', [], true); ?>
        <?php else: ?>
        <?php echo $CI->load->view('attendance/partials/att_user_menu', [], true); ?>
        <?php endif; ?>           
      <div class="btn-divider"></div>
      <?php render_export_buttons(['filename' => $page_title ?? 'export']); ?>
    </div>
  </div>

  <!-- ===================== FILTER ===================== -->
  <div class="collapse multi-collapse" id="showFilter">
    <div class="card">
      <div class="card-body">
        <?php if (function_exists('app_table_filter')): ?>
          <?php app_table_filter($table_id ?? 'attendanceTable', ['exclude_columns' => ['day']]); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ===================== TOP CONTROLS ===================== -->
  <div class="d-flex align-items-center justify-content-between mt-5 mb-2" style="font-size:13px;">
    <div class="d-flex align-items-center" style="gap:6px;">

      <a href="<?= html_escape($prevUrl ?? '#') ?>"
         class="btn btn-light-primary btn-xs px-1 py-0"
         title="Previous Month"
         style="font-size:13px; min-width:28px; height:28px; display:flex; align-items:center; justify-content:center;">
        <i class="fas fa-chevron-left" style="font-size:13px;"></i>
      </a>

      <a href="<?= html_escape($currentUrl ?? '#') ?>"
         class="btn btn-primary btn-xs ms-2 px-1 py-0"
         title="Go to Current Month"
         style="font-size:12px; min-width:38px; height:28px; display:flex; align-items:center;">
        <i class="fas fa-calendar-day me-2" style="font-size:12px; margin-right:2px;"></i> Current
      </a>

      <a href="<?= html_escape($nextUrl ?? '#') ?>"
         class="btn btn-light-primary btn-xs px-1 py-0"
         title="Next Month"
         style="font-size:12px; min-width:28px; height:28px; display:flex; align-items:center; justify-content:center;">
        <i class="fas fa-chevron-right" style="font-size:12px;"></i>
      </a>
      
      <select id="bulkStatusSelect"
              class="form-select dropdown-toggle"
              style="font-size:12px; width:auto;" required>
        <option value="" disabled selected>Select Status</option>
        <option value="P">P = Present</option>
        <option value="C">C = Casual Leave</option>
        <option value="M">M = Medical Leave</option>
        <option value="S">S = Short Leave</option>
        <option value="A">A = Absent</option>
      </select>

      <button type="button"
              id="applyBulkBtn"
              class="btn btn-header btn-light-primary"
              <?= !empty($canCreateAttendance) ? '' : 'disabled title="You do not have permission to create attendance."' ?>>
        Apply Bulk
      </button>

    </div>

    <div class="attendance-scroll-arrows d-flex align-items-center mb-1" style="gap:6px;">
      <button type="button"
              id="scrollLeft"
              class="btn btn-light-primary btn-xs px-1 py-0"
              title="Scroll left"
              style="font-size:12px; min-width:24px; height:24px; display:flex; align-items:center; justify-content:center;">
        <i class="fas fa-chevron-left" style="font-size:12px;"></i>
      </button>

      <span class="text-muted ms-2" style="font-size:10px;">Scroll Table</span>

      <button type="button"
              id="scrollRight"
              class="btn btn-light-primary btn-xs px-1 py-0"
              title="Scroll right"
              style="font-size:12px; min-width:24px; height:24px; display:flex; align-items:center; justify-content:center;">
        <i class="fas fa-chevron-right" style="font-size:12px;"></i>
      </button>
    </div>
  </div>

  <!-- ===================== TABLE FORM ===================== -->
  <?= form_open(base_url('attendance/save'), ['method' => 'post', 'class' => 'overflow-auto']) ?>
    <input type="hidden" name="year"  value="<?= (int)($currentYear ?? 0) ?>">
    <input type="hidden" name="month" value="<?= (int)($currentMonth ?? 0) ?>">

    <div class="card">
      <div class="card-body">

        <p class="text-muted mb-2 small">
          <?= htmlspecialchars(date('l, F j, Y'), ENT_QUOTES) ?> |
          <span class="text-muted mb-1" style="font-size:10px;">
            P = Present | C = Casual Leave | M = Medical Leave | S = Short Leave | A = Absent | FH = Federal Holiday | LH = Local Holiday | RH = Religious Holiday
          </span>
        </p>

        <div class="table-responsive app-scroll" id="attendanceTableScroll">
          <table class="table table-sm table-bordered text-center table-hover attendance-grid align-middle" id="attendanceTable">
            <thead class="table-light">
              <tr>
                <th style="text-align:left; vertical-align:middle; font-size:12px; padding-left:10px; min-width:90px;">Emp ID</th>
                <th style="text-align:left; vertical-align:middle; font-size:12px; padding-left:10px; min-width:190px;">Employee Name</th>

                <?php foreach (($allDays ?? []) as $d => $dayInfo): ?>
                  <?php
                    // DOW already provided by controller as: MON, TUE, ...
                    $isWeekend = in_array(($dayInfo['dow'] ?? ''), ['SAT', 'SUN'], true);
                  ?>
                  <th class="<?= $isWeekend ? 'weekend' : '' ?>">
                    <?= sprintf('%02d', (int)($dayInfo['day'] ?? $d)) ?><br>
                    <?= html_escape($dayInfo['dow'] ?? '') ?>
                  </th>
                <?php endforeach; ?>

              </tr>
            </thead>

            <tbody>
              <?php foreach (($users ?? []) as $u): ?>
                <?php $uid = (int)($u['id'] ?? 0); ?>
                <tr>
                  <td class="text-muted" style="text-align:left; vertical-align:middle; font-size:10px; padding-left:10px;">
                    <?= emp_id_display($u['emp_id'] ?? '') ?>
                  </td>
            
                  <td class="text-muted" style="text-align:left; vertical-align:middle; font-size:10px; padding-left:10px;">
                    <?= html_escape(trim(($u['firstname'] ?? '').' '.($u['lastname'] ?? ''))) ?>
                  </td>
            
                  <?php foreach (($allDays ?? []) as $d => $dayInfo): ?>
                    <?php
                      $val = $existing[$uid][$d] ?? '';
            
                      $isToday   = ((int)($todayDay ?? 0) === (int)$d);
                      $isWeekend = in_array(($dayInfo['dow'] ?? ''), ['SAT', 'SUN'], true);
            
                      $inputName = "attendance[$uid][$d]";
                      $inputVal  = html_escape($val);
                    ?>
            
                    <?php
                      $meta = $cellMeta[$uid][$d] ?? [];
                    
                      $isLocked  = (bool)($meta['is_locked'] ?? false);
                      $reason    = (string)($meta['reason'] ?? '');
                      $display   = (string)($meta['display'] ?? '—');
                      $cellClass = (string)($meta['cell_class'] ?? '');
                      $boxClass  = (string)($meta['box_class'] ?? '');
                    ?>
                    
                    <td class="<?= $isWeekend ? 'weekend' : '' ?> <?= $cellClass ?>">
                    
                      <?php if ($isLocked): ?>
                    
                        <?php
                          $isStatusBox = (strpos($boxClass, 'status-') === 0);
                        ?>
                        
                        <div class="<?= $isStatusBox ? 'attendance-status-box' : 'attendance-locked-box' ?> <?= html_escape($boxClass) ?>"
                             title="<?= html_escape($reason) ?>">
                          <?= html_escape($display) ?>
                        </div>
                    
                        <input type="hidden"
                               name="<?= $inputName ?>"
                               value="<?= $inputVal ?>">
                    
                      <?php else: ?>
                    
                        <input type="text"
                               name="<?= $inputName ?>"
                               value="<?= $inputVal ?>"
                               maxlength="1"
                               class="form-control attendance-input <?= $isToday ? 'today-input' : '' ?>"
                               style="width:1.5rem; text-align:center; font-size:12px; margin:auto;">
                    
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

    <?php if (!empty($canCreateAttendance)): ?>
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

