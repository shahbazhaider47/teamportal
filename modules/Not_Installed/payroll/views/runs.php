<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $CUR = html_escape(get_base_currency_symbol()); ?>
<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= e($page_title ?? 'Payroll Runs') ?></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <?php
        $canRun     = staff_can('create', 'payroll');
        $canExport  = staff_can('export', 'general');
        $canPrint   = staff_can('print', 'general');
        $canDelete  = staff_can('delete', 'payroll');
      ?>

      <?php if ($canRun): ?>
      <button type="button"
              class="btn btn-primary btn-header"
              data-bs-toggle="modal"
              data-bs-target="#runPayrollModal">
        <i class="ti ti-report-money me-1"></i> Run New Payroll
      </button>
      <?php endif; ?>

      <div class="btn-divider"></div>

      <!-- Search -->
      <div class="input-group small app-form dynamic-search-container" style="width:200px;">
        <input type="text" class="form-control rounded app-form small dynamic-search-input"
               placeholder="Search..."
               aria-label="Search runs"
               data-table-target="<?= e($table_id ?? '') ?>">
        <button class="btn btn-outline-secondary dynamic-search-clear" type="button" title="Clear" style="display:none;"></button>
      </div>

      <!-- Export -->
      <?php if ($canExport): ?>
        <button type="button"
                class="btn btn-light-primary icon-btn b-r-4 btn-export-table"
                title="Export to Excel"
                data-export-filename="<?= e($page_title ?? 'export') ?>">
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

  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-sm small table-hover app-table" id="<?= e($table_id) ?>">
        <thead class="bg-light-primary">
          <tr>
            <th>Run ID</th>
            <th>Salary Month</th>
            <th>Period</th>
            <th>Run Type</th>
            <th>Pay Date</th>
            <th>Employees</th>
            <th>Gross Payroll</th>
            <th>Net Payroll</th>
            <th>Payroll Status</th>
            <th>Updated</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($runs)): foreach ($runs as $r): ?>
            <tr>
              <td><?= e($r['run_id']) ?></td>
              <?php
                $ps = $r['period_start'] ?? null;
                $pe = $r['period_end'] ?? null;
                $label = '—';
                if ($ps && $pe && strtotime($ps) && strtotime($pe)) {
                    $sm = date('Ym', strtotime($ps));
                    $em = date('Ym', strtotime($pe));
                    $label = ($sm === $em)
                        ? date('M Y', strtotime($ps))
                        : date('M Y', strtotime($ps)) . ' – ' . date('M Y', strtotime($pe));
                }
                ?>
                <td><?= e($label) ?></td>

              <td><?= e(($r['period_start'] ?? '') . ' → ' . ($r['period_end'] ?? '')) ?></td>
              <td><?= e(ucwords(str_replace(['_','-'], ' ', (string)($r['pay_period'] ?? '')))) ?></td>
              <td>
                <?php
                  $pd = !empty($r['pay_date']) && strtotime($r['pay_date']) ? date('M d, Y', strtotime($r['pay_date'])) : '—';
                  echo e($pd);
                ?>
              </td>
              <td><?= (int)($r['employees_count'] ?? 0) ?></td>
              <td><?= $CUR . ' ' . number_format((float)($r['sum_gross'] ?? 0), 2) ?></td>
              <td><?= $CUR . ' ' . number_format((float)($r['sum_net'] ?? 0), 2) ?></td>
              <td>
                <?php $status = trim((string)($r['status_run'] ?? 'Processed')); ?>
                <span class="pill <?= strtolower($status)==='processed' ? 'pill-success' : 'pill-danger' ?>">
                  <?= e($status) ?>
                </span>
              </td>
              <td>
                <?php
                  $upd = !empty($r['updated_at']) && strtotime($r['updated_at']) ? date('Y-m-d H:i', strtotime($r['updated_at'])) : '—';
                  echo e($upd);
                ?>
              </td>
              <td class="text-end">

                    <a href="<?= site_url('payroll/details/'.(int)$r['run_id']) ?>" target="_blank"
                       class="btn btn-outline-primary btn-ssm">
                        <i class="ti ti-eye"></i>
                    </a>
                    
                  <?php if ($canDelete): ?>
                  <form method="post"
                        action="<?= site_url('payroll/delete_run/'.(int)$r['run_id']) ?>"
                        class="d-inline"
                        onsubmit="return confirm('Delete entire run #<?= (int)$r['run_id'] ?>? This cannot be undone.');">
                    <button type="submit" class="btn btn-outline-danger btn-ssm">
                      <i class="ti ti-trash"></i>
                    </button>
                  </form>
                  <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr>
              <td colspan="10" class="text-center text-muted">No payroll runs found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Run Details Modal -->
<div class="modal fade" id="runModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">Run Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="runMeta" class="small"></div>
      </div>
      <div class="modal-footer">
        <a href="#" id="btnOpenRun" class="btn btn-sm btn-primary">
          <i class="ti ti-list-details me-1"></i> Open Payroll Details
        </a>
      </div>
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
        <!-- Payroll Type -->
        <div class="col-md-4">
          <label class="form-label">Payroll Type</label>
          <select class="form-select" name="payroll_type" id="rp_payroll_type" required>
            <option value="regular" selected>Regular</option>
            <option value="off_cycle">Off-Cycle</option>
          </select>
        </div>

          <!-- Pay Period -->
          <div class="col-md-4">
            <label class="form-label">Pay Period</label>
            <select class="form-select" name="pay_period" id="rp_pay_period" required>
              <option value="monthly" selected>Monthly</option>
              <option value="semi-monthly">Semi-Monthly</option>
              <option value="biweekly">Bi-Weekly</option>
              <option value="weekly">Weekly</option>
              <option value="daily">Daily</option>
            </select>
          </div>

          <!-- Period dates -->
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

          <!-- Scope -->
          <div class="col-md-4">
            <label class="form-label">Include</label>
            <select class="form-select" name="scope" id="rp_scope" required>
              <option value="all" selected>All Active Employees</option>
              <option value="department">By Department</option>
              <option value="team">By Team</option>
              <option value="selected">Selected Employees</option>
            </select>
          </div>

          <!-- Department -->
          <div class="col-md-4 rp_scope_field rp_scope_department d-none">
            <label class="form-label">Department</label>
            <select class="form-select" name="department_id">
              <option value="">Select</option>
              <?php if (!empty($departments ?? [])): foreach ($departments as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>

          <!-- Team -->
          <div class="col-md-4 rp_scope_field rp_scope_team d-none">
            <label class="form-label">Team</label>
            <select class="form-select" name="team_id">
              <option value="">Select</option>
              <?php if (!empty($teams ?? [])): foreach ($teams as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>

          <!-- Selected Employees -->
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

          <!-- Rounding -->
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
  const modal = document.getElementById('runModal');
  if (!modal) return;

  modal.addEventListener('show.bs.modal', function (ev) {
    const btn   = ev.relatedTarget;
    const rid   = btn?.getAttribute('data-run-id');
    const meta  = document.getElementById('runMeta');
    const open  = document.getElementById('btnOpenRun');

    meta.innerHTML = 'Loading…';
    open.href = '<?= site_url('payroll/details/') ?>' + rid;

    fetch('<?= site_url('payroll/run_json/') ?>' + rid)
      .then(r => r.json())
      .then(d => {
        if (!d || !d.run_id) { meta.innerHTML = '<div class="text-danger">No data.</div>'; return; }
        meta.innerHTML = `
          <div class="row g-3">
            <div class="col-md-6"><strong>Run ID:</strong> ${d.run_id}</div>
            <div class="col-md-6"><strong>Pay Period:</strong> ${d.pay_period}</div>
            <div class="col-md-6"><strong>Period:</strong> ${d.period_start} → ${d.period_end}</div>
            <div class="col-md-6"><strong>Pay Date:</strong> ${d.pay_date}</div>
            <div class="col-md-6"><strong>Payment Method:</strong> ${d.payment_method}</div>         
            <div class="col-md-6"><strong>Employees:</strong> ${d.employees_count}</div>
            <div class="col-md-6"><strong>Gross:</strong> <?= $CUR ?> ${Number(d.sum_gross ?? 0).toFixed(2)}</div>
            <div class="col-md-6"><strong>Net:</strong> <?= $CUR ?> ${Number(d.sum_net ?? 0).toFixed(2)}</div>
            <div class="col-md-6"><strong>Overtime:</strong> <?= $CUR ?> ${Number(d.sum_overtime ?? 0).toFixed(2)}</div>
            <div class="col-md-6"><strong>Bonus:</strong> <?= $CUR ?> ${Number(d.sum_bonus ?? 0).toFixed(2)}</div>
            <div class="col-md-6"><strong>Arrears:</strong> <?= $CUR ?> ${Number(d.sum_arrears ?? 0).toFixed(2)}</div>
            <div class="col-md-12"><small class="text-muted">Updated: ${d.updated_at}</small></div>
          </div>
        `;
      })
      .catch(()=> meta.innerHTML = '<div class="text-danger">Failed to load.</div>');
  });
})();
</script>

<script>
(function(){
  function pad(n){ return n < 10 ? '0'+n : n; }
  function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
  function monthStart(d){ return new Date(d.getFullYear(), d.getMonth(), 1); }
  function monthEnd(d){ return new Date(d.getFullYear(), d.getMonth()+1, 0); }

  function setDatesForPeriod(period){
    const now = new Date();
    let start=null, end=null;

    switch(period){
      case 'weekly': {
        const day = now.getDay(); // 0 Sun..6 Sat
        const diffToMon = (day === 0 ? 6 : day - 1);
        start = new Date(now); start.setDate(now.getDate() - diffToMon);
        end   = new Date(start); end.setDate(start.getDate() + 6);
        break;
      }
      case 'biweekly': {
        const d2 = now.getDay(); // 0=Sun
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

    const ps = document.getElementById('rp_period_start');
    const pe = document.getElementById('rp_period_end');
    const pd = document.getElementById('rp_pay_date');
    if (start && end) {
      ps.value = ymd(start);
      pe.value = ymd(end);
      const payDate = new Date(end); payDate.setDate(end.getDate() + 3);
      pd.value = ymd(payDate);
    } else {
      ps.value = ''; pe.value = ''; pd.value = '';
    }
  }

  function refreshScope(){
    const v = document.getElementById('rp_scope').value;
    document.querySelectorAll('.rp_scope_field').forEach(el => el.classList.add('d-none'));
    if (v === 'department') document.querySelector('.rp_scope_department')?.classList.remove('d-none');
    if (v === 'team')       document.querySelector('.rp_scope_team')?.classList.remove('d-none');
    if (v === 'selected')   document.querySelector('.rp_scope_selected')?.classList.remove('d-none');
  }

  // NEW: toggle enable/disable for period start/end based on Payroll Type
  function refreshPayrollTypeUI(){
    const type = document.getElementById('rp_payroll_type')?.value || 'regular';
    const ps   = document.getElementById('rp_period_start');
    const pe   = document.getElementById('rp_period_end');

    const isRegular = (type === 'regular');

    // Disable for Regular (system auto-fills based on Pay Period)
    ps.lock = isRegular;
    pe.lock = isRegular;

    // Optional: add a subtle visual cue
    ps.classList.toggle('bg-light', isRegular);
    pe.classList.toggle('bg-light', isRegular);

    // For Regular, keep values synced to Pay Period selection
    if (isRegular) {
      setDatesForPeriod(document.getElementById('rp_pay_period').value);
    }
    // For Off-Cycle, leave existing values (user can edit freely).
  }

  document.addEventListener('DOMContentLoaded', function(){
    // Initial auto-fill for current pay period
    setDatesForPeriod(document.getElementById('rp_pay_period').value);
    refreshScope();
    refreshPayrollTypeUI();

    document.getElementById('rp_pay_period').addEventListener('change', function(){
      // Only auto-adjust dates when Regular; Off-Cycle leaves dates user-editable
      if (document.getElementById('rp_payroll_type').value === 'regular') {
        setDatesForPeriod(this.value);
      }
    });

    document.getElementById('rp_scope').addEventListener('change', refreshScope);
    document.getElementById('rp_payroll_type').addEventListener('change', refreshPayrollTypeUI);
  });
})();
</script>
