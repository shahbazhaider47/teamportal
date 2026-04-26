<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
  if (!function_exists('e')) { function e($s){ return html_escape($s); } }
  $CUR       = function_exists('get_base_currency_symbol') ? html_escape(get_base_currency_symbol()) : '';
  $table_id  = $table_id ?? 'payrollDetailsTable';
  $rows      = $rows ?? [];

  $fmtDate = function($d){
    if (empty($d)) return null;
    $d = trim((string)$d);
    if ($d === '0000-00-00' || $d === '0000-00-00 00:00:00') return null;
    $ts = strtotime($d);
    return $ts ? date('Y-m-d', $ts) : null;
  };

  $money = function($n) use ($CUR){
    $v = number_format((float)$n, 2);
    return ($CUR ? $CUR.' ' : '') . $v;
  };

  $runBadge = function($s){
    $s = strtolower((string)$s);
    $map = [
      'draft'     => 'badge bg-secondary',
      'processed' => 'badge bg-info text-dark',
      'posted'    => 'badge bg-primary',
      'paid'      => 'badge bg-success',
      'void'      => 'badge bg-danger',
    ];
    return $map[$s] ?? 'badge bg-secondary';
  };

  $canExport    = staff_can('export', 'general');
  $canPrint     = staff_can('print', 'general');
  $canRun       = staff_can('create', 'payroll');
  $canView      = staff_can('view_global', 'payroll') || staff_can('view_own', 'payroll');
  $canEditState = staff_can('edit', 'payroll');
?>
<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= e($page_title ?? 'Payroll') ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php if ($canRun): ?>
      <button type="button"
              class="btn btn-primary btn-header"
              data-bs-toggle="modal"
              data-bs-target="#runPayrollModal">
        <i class="ti ti-report-money me-1"></i> Run New Payroll
      </button>
      <?php endif; ?>

      <div class="btn-divider"></div>

      <?php if ($canExport): ?>
      <button type="button"
              class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
              title="Export to Excel"
              data-export-filename="<?= e($page_title ?? 'payroll_export') ?>">
        <i class="ti ti-download"></i>
      </button>
      <?php endif; ?>

      <?php if ($canPrint): ?>
      <button type="button"
              class="btn btn-light-primary icon-btn b-r-4 btn-print-table"
              title="Print Table">
        <i class="ti ti-printer"></i>
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-sm table-bottom-border small align-middle" id="<?= e($table_id) ?>">
        <thead class="bg-light-primary">
          <tr>
            <th>Employee</th>
            <th>Pay Period</th>
            <th>Period</th>
            <th>Pay Date</th>
            <th>Gross</th>
            <th>Deductions</th
            <th>Net Pay</th>
            <th>Status</th>
            <th style="width:1%"></th>
          </tr>
        </thead>
            <tbody>
              <?php if (!empty($rows)): foreach ($rows as $r): ?>
                <?php
                  $id   = (int)($r['id'] ?? 0);
            
                  $name = trim((string)($r['fullname'] ?? ''));
                  $code = trim((string)($r['emp_id'] ?? ''));
                  if ($name === '') { $name = 'UID:' . (int)($r['user_id'] ?? 0); }
            
                  $pp     = (string)($r['pay_period'] ?? 'monthly');
                  $pStart = $fmtDate($r['period_start'] ?? null);
                  $pEnd   = $fmtDate($r['period_end'] ?? null);
                  $pDate  = $fmtDate($r['pay_date'] ?? null);
            
                  $gross = isset($r['gross_pay']) ? (float)$r['gross_pay'] : (
                             (float)($r['basic_salary'] ?? 0)
                           + (float)($r['allowances_total'] ?? 0)
                           + (float)($r['overtime_amount'] ?? 0)
                           + (float)($r['bonus_amount'] ?? 0)
                           + (float)($r['commission_amount'] ?? 0)
                           + (float)($r['other_earnings'] ?? 0)
                          );
            
                  $ded = isset($r['total_deductions']) ? (float)$r['total_deductions'] : (
                            (float)($r['deductions_total'] ?? 0)
                          + (float)($r['leave_deduction'] ?? 0)
                          + (float)($r['tax_amount'] ?? 0)
                          + (float)($r['pf_deduction'] ?? 0)
                          + (float)($r['loan_total_deduction'] ?? 0)
                          + (float)($r['advance_total_deduction'] ?? 0)
                         );
            
                  $net = isset($r['net_pay']) ? (float)$r['net_pay'] : max(0, $gross - $ded);
            
                  $runStatus  = (string)($r['status_run'] ?? 'draft');
                  $badgeCls   = $runBadge($runStatus);
                  $periodLabel = ($pStart && $pEnd) ? ($pStart . ' → ' . $pEnd) : 'N/A';
                  $urlPreview = site_url('payroll/preview?id=' . $id);
                  $urlPayslip = site_url('payroll/payslip?id=' . $id);
                  $urlBank    = site_url('payroll/export_bank?run_id=' . (int)($r['run_id'] ?? 0));
                ?>
                <tr>
                  <td>
                    <?= e($name ?: '—') ?>
                    <?php if ($code !== ''): ?>
                      <div class="text-muted small"><?= e($code) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= e(ucfirst(str_replace('-', ' ', $pp))) ?></td>
                  <td><?= e($periodLabel) ?></td>
                  <td><?= $pDate ? e($pDate) : 'N/A' ?></td>
                  <td><?= $money($gross) ?></td>
                  <td><?= $money($ded) ?></td>
                  <td><strong><?= $money($net) ?></strong></td>
                  <td><span class="<?= $badgeCls ?>"><?= e(ucfirst($runStatus)) ?></span></td>
                  <td class="text-end">
                    <?php if ($canView): ?>
                      <a class="btn btn-outline-secondary icon-btn" href="<?= $urlPreview ?>" title="Preview">
                        <i class="ti ti-eye"></i>
                      </a>
                      <a class="btn btn-outline-secondary icon-btn" href="<?= $urlPayslip ?>" title="Payslip">
                        <i class="ti ti-file-text"></i>
                      </a>
                    <?php endif; ?>
                    <?php if ($canExport): ?>
                      <a class="btn btn-outline-secondary icon-btn <?= empty($r['run_id']) ? 'disabled' : '' ?>"
                         href="<?= $urlBank ?>" title="Export Bank File">
                        <i class="ti ti-building-bank"></i>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No payroll data found.</td></tr>
              <?php endif; ?>
            </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Run Payroll Modal -->
<div class="modal fade" id="runPayrollModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-top">
    <form method="post" action="<?= site_url('payroll/run') ?>" class="modal-content app-form" id="runPayrollForm">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
          <i class="ti ti-report-money me-2"></i> Run New Payroll
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Payroll Type</label>
            <select class="form-select" name="payroll_type" required>
              <option value="regular" selected>Regular</option>
              <option value="off_cycle">Off-Cycle</option>
              <option value="bonus">Bonus</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Pay Period</label>
            <select class="form-select" name="pay_period" id="rp_pay_period" required>
              <option value="monthly" selected>Monthly</option>
              <option value="semi-monthly">Semi-Monthly</option>
              <option value="biweekly">Bi-Weekly</option>
              <option value="weekly">Weekly</option>
              <option value="daily">Daily</option>
              <option value="ad-hoc">Ad-hoc</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Period Start</label>
            <input type="date" class="form-control" name="period_start" id="rp_period_start" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Period End</label>
            <input type="date" class="form-control" name="period_end" id="rp_period_end" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Pay Date</label>
            <input type="date" class="form-control" name="pay_date" id="rp_pay_date" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Include</label>
            <select class="form-select" name="scope" id="rp_scope" required>
              <option value="all" selected>All Active Employees</option>
              <option value="department">By Department</option>
              <option value="team">By Team</option>
              <option value="selected">Selected Employees</option>
            </select>
          </div>
          <div class="col-md-4 rp_scope_field rp_scope_department d-none">
            <label class="form-label">Department</label>
            <select class="form-select" name="department_id">
              <option value="">Select</option>
              <?php if (!empty($departments ?? [])): foreach ($departments as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>
          <div class="col-md-4 rp_scope_field rp_scope_team d-none">
            <label class="form-label">Team</label>
            <select class="form-select" name="team_id">
              <option value="">Select</option>
              <?php if (!empty($teams ?? [])): foreach ($teams as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>
          <div class="col-12 rp_scope_field rp_scope_selected d-none">
            <label class="form-label">Employees</label>
            <select class="form-select" name="user_ids[]" multiple size="6">
              <?php if (!empty($users_all ?? [])): foreach ($users_all as $u): ?>
                <option value="<?= (int)$u['id'] ?>">
                  <?= e(($u['emp_id'] ? ($u['emp_id'].' - ') : '').($u['firstname'].' '.$u['lastname'])) ?>
                </option>
              <?php endforeach; endif; ?>
            </select>
            <div class="text-muted small mt-1">Hold Ctrl/⌘ to select multiple.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Rounding</label>
            <select class="form-select" name="rounding">
              <option value="inherit" selected>Use System Setting</option>
              <option value="none">None</option>
              <option value="nearest">Nearest</option>
              <option value="down">Down</option>
              <option value="up">Up</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Notes (optional)</label>
            <textarea class="form-control" name="notes" rows="3" placeholder="Any remarks for this run..."></textarea>
          </div>
          <div class="col-12">
            <div class="alert alert-info small mb-0">
              This will create a new payroll run for the selected period and employees.
              No final posting to accounting is done at this step.
            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="ti ti-player-play me-1"></i> Run Payroll
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  function pad(n){ return n < 10 ? '0'+n : n; }
  function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
  function monthStart(d){ return new Date(d.getFullYear(), d.getMonth(), 1); }
  function monthEnd(d){ return new Date(d.getFullYear(), d.getMonth()+1, 0); }

  function setDatesForPeriod(period){
    const now = new Date();
    let start = null, end = null, pay = null;

    switch(period){
      case 'weekly': {
        const day = now.getDay();
        const diffToMon = (day === 0 ? 6 : day - 1);
        start = new Date(now); start.setDate(now.getDate() - diffToMon);
        end   = new Date(start); end.setDate(start.getDate() + 6);
        break;
      }
      case 'biweekly': {
        const d2 = now.getDay();
        const diffSun = (d2 === 0 ? 0 : 7 - d2);
        end   = new Date(now); end.setDate(now.getDate() + diffSun);
        start = new Date(end); start.setDate(end.getDate() - 13);
        break;
      }
      case 'semi-monthly': {
        const ms = monthStart(now);
        const me = monthEnd(now);
        if (now.getDate() <= 15){
          start = ms;
          end   = new Date(now.getFullYear(), now.getMonth(), 15);
        } else {
          start = new Date(now.getFullYear(), now.getMonth(), 16);
          end   = me;
        }
        break;
      }
      case 'daily': {
        start = new Date(now);
        end   = new Date(now);
        break;
      }
      case 'ad-hoc': {
        start = null; end = null;
        break;
      }
      case 'monthly':
      default: {
        start = monthStart(now);
        end   = monthEnd(now);
        break;
      }
    }

    if (start && end) {
      document.getElementById('rp_period_start').value = ymd(start);
      document.getElementById('rp_period_end').value   = ymd(end);
      const payDate = new Date(end); payDate.setDate(end.getDate() + 3);
      document.getElementById('rp_pay_date').value     = ymd(payDate);
    } else {
      document.getElementById('rp_period_start').value = '';
      document.getElementById('rp_period_end').value   = '';
      document.getElementById('rp_pay_date').value     = '';
    }
  }

  function refreshScope(){
    const v = document.getElementById('rp_scope').value;
    document.querySelectorAll('.rp_scope_field').forEach(el => el.classList.add('d-none'));
    if (v === 'department') document.querySelector('.rp_scope_department')?.classList.remove('d-none');
    if (v === 'team')       document.querySelector('.rp_scope_team')?.classList.remove('d-none');
    if (v === 'selected')   document.querySelector('.rp_scope_selected')?.classList.remove('d-none');
  }

  document.addEventListener('DOMContentLoaded', function(){
    const pp = document.getElementById('rp_pay_period').value;
    setDatesForPeriod(pp);
    refreshScope();

    document.getElementById('rp_pay_period').addEventListener('change', function(){
      setDatesForPeriod(this.value);
    });
    document.getElementById('rp_scope').addEventListener('change', refreshScope);
  });
})();
</script>