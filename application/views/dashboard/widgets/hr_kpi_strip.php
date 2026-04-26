<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI =& get_instance();
$CI->load->model('User_model');

$kpi = [];
try {
    $kpi = $CI->User_model->get_hr_kpi();
} catch (Exception $e) {
    log_message('error', 'HR KPI widget error: ' . $e->getMessage());
}

$voluntary_turnover   = (float)($kpi['voluntary']   ?? 0);
$involuntary_turnover = (float)($kpi['involuntary'] ?? 0);
$first_year_turnover  = (float)($kpi['first_year']  ?? 0);
$average_age_years    = (float)($kpi['avg_age']      ?? 0);
$average_tenure_years = (float)($kpi['avg_tenure']   ?? 0);
$fte_count            = (int)  ($kpi['fte']          ?? 0);
?>

<style>
.dash-card {
  background: #ffffff;
  border-radius: 10px;
  padding: 10px;
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.05);
  transition: box-shadow 0.25s;
}

.dash-kpi-card{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px}
.dash-kpi-icon{width:40px;height:40px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.dash-kpi-value{font-size:18px;font-weight:700;color:#5b5f67;line-height:1.2}
.dash-kpi-label{font-size:10.5px;color:#5b5f67;text-transform:uppercase;letter-spacing:.04em;margin-top:2px}
.dash-kpi-warn{border-color:#fde68a}

</style>

<div class="dash-card">
    
    <div class="row g-2">

        <div class="col">
          <div class="dash-kpi-card">
            <div class="dash-kpi-icon" style="background:#16a34a18;">
              <i class="ti ti-user-plus"></i>
            </div>
            <div>
              <div class="dash-kpi-value"><?= number_format($voluntary_turnover, 1) ?> <small>%</small></div>
              <div class="dash-kpi-label">Voluntary <br>turnover</div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="dash-kpi-card">
            <div class="dash-kpi-icon" style="background:#ffc78e3d;">
              <i class="ti ti-user-minus"></i>
            </div>
            <div>
              <div class="dash-kpi-value"><?= number_format($involuntary_turnover, 1) ?> <small>%</small></div>
              <div class="dash-kpi-label">Involuntary <br>turnover</div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="dash-kpi-card">
            <div class="dash-kpi-icon" style="background:#0ea5e918;">
              <i class="ti ti-arrows-join"></i>
            </div>
            <div>
              <div class="dash-kpi-value"><?= number_format($first_year_turnover, 1) ?> <small>%</small></div>
              <div class="dash-kpi-label">First-year <br>turnover</div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="dash-kpi-card">
            <div class="dash-kpi-icon" style="background:#6366f118;">
              <i class="ti ti-calendar-stats"></i>
            </div>
            <div>
              <div class="dash-kpi-value"><?= number_format($average_age_years, 1) ?> <small>Years</small></div>
              <div class="dash-kpi-label">Average staff <br>age</div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="dash-kpi-card">
            <div class="dash-kpi-icon" style="background:#5ef3d13d;">
              <i class="ti ti-arrows-split-2"></i>
            </div>
            <div>
              <div class="dash-kpi-value"><?= number_format($average_tenure_years, 1) ?> <small>Years</small></div>
              <div class="dash-kpi-label">Average <br>tenure</div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="dash-kpi-card">
            <div class="dash-kpi-icon" style="background:#ff00eb18;">
              <i class="ti ti-glass-full"></i>
            </div>
            <div>
              <div class="dash-kpi-value"><?= html_escape($fte_count, 1) ?></div>
              <div class="dash-kpi-label">Full Time<br> Employees</div>
            </div>
          </div>
        </div>
    </div>

</div>    