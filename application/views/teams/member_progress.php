<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/* ═══════════════════════════════════════════════════════════════
 *  PREP — extract variables before any HTML
 * ═══════════════════════════════════════════════════════════════ */
$p           = $progress        ?? [];
$kpis        = $kpis            ?? [];
$user        = $p['user']       ?? [];
$att         = $p['attendance'] ?? [];
$attLogs     = $p['attendance_logs']  ?? [];
$leaves      = $p['leaves']     ?? [];
$signoffs    = $p['signoffs']   ?? [];
$evals       = $p['evaluations']      ?? [];
$latestEval  = $p['latest_evaluation'] ?? null;
$tasks       = $p['tasks']      ?? [];
$movements   = $p['movements']  ?? [];
$contract    = $p['contract']   ?? null;
$assets      = $p['assets']     ?? [];
$documents   = $p['documents']  ?? [];
$exitInfo    = $p['exit_info']  ?? null;
$tickets     = $p['tickets']    ?? [];
$requests    = $p['requests']   ?? [];
$period      = $p['period']     ?? ['start' => '', 'end' => ''];
$isOwn       = $is_own          ?? false;
$viewerRole  = $viewer_role     ?? '';

/* helpers */
$esc  = fn($v)         => html_escape($v ?? '');
$num  = fn($v, $d = 0) => number_format((float)($v ?? 0), $d);
$date = fn($v)         => $v ? date('M j, Y', strtotime($v)) : '—';
$dt   = fn($v)         => $v ? date('M j, Y g:i A', strtotime($v)) : '—';
$pct  = fn($v)         => $v !== null ? $num($v, 1) . '%' : '—';

/* avatar */
$mkAv = function(array $u, int $sz = 38) : string {
    $first = trim($u['firstname'] ?? $u['first_name'] ?? '');
    $last  = trim($u['lastname']  ?? $u['last_name']  ?? '');
    $file  = trim($u['profile_image'] ?? '');
    $init  = strtoupper(substr($first,0,1).substr($last,0,1)) ?: 'U';
    $fs    = max(10, (int)($sz * .36));
    $st    = "width:{$sz}px;height:{$sz}px;font-size:{$fs}px;";
    if ($file) {
        $src = base_url('uploads/users/profile/'.$file);
        $fb  = base_url('assets/images/default-avatar.png');
        return '<img src="'.html_escape($src).'" style="'.$st.'border-radius:50%;object-fit:cover;" loading="lazy"
                     onerror="this.onerror=null;this.src=\''.html_escape($fb).'\'">';
    }
    return '<span style="'.$st.'border-radius:50%;background:#E6F1FB;color:#185FA5;
                display:inline-flex;align-items:center;justify-content:center;font-weight:700;">'.html_escape($init).'</span>';
};

/* badge helpers */
$statusBadge = function(string $s): string {
    $map = [
        'completed'   => 'bg-success-soft text-success',
        'approved'    => 'bg-success-soft text-success',
        'active'      => 'bg-success-soft text-success',
        'signed'      => 'bg-success-soft text-success',
        'resolved'    => 'bg-success-soft text-success',
        'closed'      => 'bg-secondary-soft text-secondary',
        'pending'     => 'bg-warning-soft text-warning',
        'requested'   => 'bg-warning-soft text-warning',
        'in_progress' => 'bg-info-soft text-info',
        'draft'       => 'bg-light text-muted',
        'open'        => 'bg-info-soft text-info',
        'rejected'    => 'bg-danger-soft text-danger',
        'failed'      => 'bg-danger-soft text-danger',
        'overdue'     => 'bg-danger-soft text-danger',
        'cancelled'   => 'bg-secondary-soft text-muted',
        'not_started' => 'bg-light text-muted',
        'review'      => 'bg-primary-soft text-primary',
        'submitted'   => 'bg-info-soft text-info',
        'on_hold'     => 'bg-warning-soft text-warning',
        'expired'     => 'bg-danger-soft text-danger',
    ];
    $cls = $map[strtolower($s)] ?? 'bg-light-secondary text-muted';
    return '<span class="badge '.$cls.'">'.html_escape(ucwords(str_replace('_',' ',$s))).'</span>';
};

$priorityBadge = function(string $p): string {
    $map = [
        'low'    => 'bg-light-secondary text-muted',
        'normal' => 'bg-info-soft text-info',
        'high'   => 'bg-warning-soft text-warning',
        'urgent' => 'bg-danger-soft text-danger',
    ];
    $cls = $map[strtolower($p)] ?? 'bg-light-secondary text-muted';
    return '<span class="badge '.$cls.'">'.html_escape(ucfirst($p)).'</span>';
};

/* tenure display */
$tenureDays = (int)($user['tenure_days'] ?? 0);
$tenureStr  = '';
if ($tenureDays > 0) {
    $y = floor($tenureDays / 365);
    $m = floor(($tenureDays % 365) / 30);
    $tenureStr = ($y > 0 ? $y.'y ' : '') . ($m > 0 ? $m.'m' : ($y === 0 ? $tenureDays.'d' : ''));
}

/* attendance colour helper */
$attColor = function(?float $pct): string {
    if ($pct === null) return 'text-muted';
    if ($pct >= 90)   return 'text-success';
    if ($pct >= 75)   return 'text-warning';
    return 'text-danger';
};

/* section helper */
$sectionOpen  = function(string $id, string $icon, string $title, string $badge = '') : void {
    echo '<div class="solid-card mb-3" id="sec-'.$id.'">';
    echo '<div class="card-header ep-sec-head d-flex align-items-center gap-2 py-2 px-0">';
    echo '<i class="'.$icon.' text-primary ep-sec-icon"></i>';
    echo '<span class="fw-semibold small">'.$title.'</span>';
    if ($badge !== '') echo $badge;
    echo '</div><div class="card-body p-0">';
};
$sectionClose = fn() => print '</div></div>';

$emptyRow = function(string $msg, int $cols = 6): string {
    return '<tr><td colspan="'.$cols.'" class="text-center text-muted py-4 small">'
         . '<i class="fas fa-inbox fa-lg d-block mb-2 text-secondary opacity-50"></i>'.$msg.'</td></tr>';
};
?>

<style>
.ep-kpi{background:#fff;border:0.5px solid rgba(0,0,0,.1);border-radius:10px;padding:14px 16px}
.ep-kpi-label{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#8a8a86;margin-bottom:5px}
.ep-kpi-val{font-size:22px;font-weight:700;line-height:1;color:#1a1a18}
.ep-kpi-sub{font-size:11px;color:#8a8a86;margin-top:4px}
.ep-sec-head{background:#fff;border-bottom:0.5px solid rgba(0,0,0,.08)}
.ep-sec-icon{font-size:13px;width:18px;text-align:center}
.ep-profile-meta dt{font-size:11px;color:#8a8a86;font-weight:400;margin-bottom:1px}
.ep-profile-meta dd{font-size:13px;font-weight:500;color:#1a1a18;margin-bottom:10px}
.ep-att-bar{height:8px;border-radius:4px;background:#eee;overflow:hidden;margin-top:4px}
.ep-att-bar-fill{height:100%;border-radius:4px;background:#185FA5;transition:width .4s}
.ep-timeline-dot{width:10px;height:10px;border-radius:50%;background:#185FA5;flex-shrink:0;margin-top:4px}
.ep-timeline-line{width:2px;background:#e0e0e0;flex:1;min-height:20px;margin:2px auto}
.ep-eval-section{background:#f8f8f6;border:0.5px solid rgba(0,0,0,.08);border-radius:8px;padding:10px 14px;margin-bottom:8px}
.ep-score-bar{height:6px;border-radius:3px;background:#E6F1FB;overflow:hidden;margin-top:3px}
.ep-score-fill{height:100%;border-radius:3px;background:#185FA5}
.ep-log-row{display:flex;align-items:flex-start;gap:10px;padding:7px 16px;border-bottom:0.5px solid rgba(0,0,0,.06)}
.ep-log-row:last-child{border-bottom:none}
.ep-log-dot{width:8px;height:8px;border-radius:50%;background:#B5D4F4;flex-shrink:0;margin-top:5px}
.table-ep td,.table-ep th{font-size:12px;vertical-align:middle;padding:7px 12px}
.table-ep thead th{background:#f8f8f6;font-weight:600;color:#5f5e5a;font-size:10px;text-transform:uppercase;letter-spacing:.04em}
.ep-warn-banner{background:#FAEEDA;border:0.5px solid #FAC775;border-radius:8px;padding:10px 14px;font-size:12px;color:#854F0B}
.ep-exit-banner{background:#FCEBEB;border:0.5px solid #F7C1C1;border-radius:8px;padding:12px 16px;color:#A32D2D}
</style>

<div class="container-fluid">
    
  <div class="view-header mb-3">
    <div class="view-icon me-3"><i class="ti ti-chart-histogram"></i></div>
    <div class="flex-grow-1">
      <div class="view-title me-2"><?= $page_title ?>
      <span class="badge bg-light-info">
        <i class="ti ti-calendar" style="font-size:11px"></i>
        <?= $date($period['start']) ?> — <?= $date($period['end']) ?>
      </span>
      </div>
    </div>
    <div class="ms-auto d-flex gap-2">

    <form method="get" action="<?= site_url('teams/member_progress/'.(int)($user['id'] ?? 0)) ?>"
          class="d-flex align-items-center gap-2 flex-wrap app-form">
      <input type="date" name="from" class="form-control form-control-sm app-form"
             style="width:140px" value="<?= $esc($period['start']) ?>">
      <span class="text-muted small">to</span>
      <input type="date" name="to" class="form-control form-control-sm app-form"
             style="width:140px" value="<?= $esc($period['end']) ?>">
      <div class="btn-divider mt-1"></div>      
      <button type="submit" class="btn btn-primary btn-header">
        <i class="ti ti-refresh me-1"></i>Apply
      </button>
    </form>
        
    </div>
  </div>
  
  <?php if ($exitInfo): ?>
  <div class="ep-exit-banner mb-3 d-flex align-items-center gap-3">
    <i class="fas fa-exclamation-triangle fa-lg flex-shrink-0"></i>
    <div>
      <strong>Employee Exit On Record</strong> —
      <?= $esc($exitInfo['exit_type']) ?> on <?= $date($exitInfo['exit_date']) ?>.
      Status: <strong><?= $esc($exitInfo['exit_status']) ?></strong>.
      <?php if ($exitInfo['last_working_date']): ?>
        Last working day: <strong><?= $date($exitInfo['last_working_date']) ?></strong>.
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-3">
      
    <div class="col-xl-3 col-lg-4">

      <div class="solid-card mb-3">
        <div class="card-body text-center pt-4 pb-3">
          <?= $mkAv($user, 72) ?>
          <div class="mt-3 mb-1 fw-semibold"><?= $esc($user['full_name'] ?? '') ?></div>
          <div class="text-muted small mb-2"><?= $esc($user['position_title'] ?? '') ?></div>
          <div class="d-flex justify-content-center gap-2 flex-wrap mb-3">
            <?php if (!empty($user['user_role'])): ?>
              <span class="badge bg-light-primary"><?= $esc(ucwords($user['user_role'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($user['employment_type'])): ?>
              <span class="badge bg-light-info text-info"><?= $esc($user['employment_type']) ?></span>
            <?php endif; ?>
            <span class="badge <?= $user['is_active'] ? 'bg-light-success text-success' : 'bg-light-danger text-danger' ?>">
              <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
            </span>

            <?php
            $g = strtolower(trim($user['gender'] ?? ''));
            
            $map = [
              'male'   => ['bg-light-primary text-primary', 'Male', 'ti ti-gender-male'],
              'female' => ['bg-light-danger text-danger', 'Female', 'ti ti-gender-female'],
              'other'  => ['bg-light-warning text-warning', 'Other', 'ti ti-gender-bigender'],
            ];
            
            [$class, $label, $icon] = $map[$g] ?? ['bg-light-secondary text-secondary', 'Unknown', 'ti ti-help'];
            ?>
            
            <span class="badge <?= $class ?> d-inline-flex align-items-center gap-1">
              <i class="<?= $icon ?>"></i>
              <?= $label ?>
            </span>

            
          </div>
        </div>
        <div class="card-body border-top pt-3 pb-2">
          <dl class="ep-profile-meta row mb-0">
            <div class="col-6">
              <dt>Emp ID</dt>
              <dd><?= emp_id_display($user['emp_id'] ?? '—') ?></dd>
            </div>
            <div class="col-6">
              <dt>Tenure</dt>
              <dd><?= $tenureStr ?: '—' ?></dd>
            </div>
            <div class="col-6">
              <dt>Department</dt>
              <dd><?= $esc($user['department_name'] ?? '—') ?></dd>
            </div>
            <div class="col-6">
              <dt>Team</dt>
              <dd><?= $esc($user['team_name'] ?? '—') ?></dd>
            </div>
            <div class="col-6">
              <dt>Team Lead</dt>
              <dd><?= user_profile_small($user['lead_name'] ?: '—') ?></dd>
            </div>
            <div class="col-6">
              <dt>Manager</dt>
              <dd><?= user_profile_small($user['manager_name'] ?: '—') ?></dd>
            </div>
            <div class="col-6">
              <dt>Joining Date</dt>
              <dd><?= $date($user['emp_joining']) ?></dd>
            </div>
            <?php if (!empty($user['probation_end_date'])): ?>
            <div class="col-6">
              <dt>Probation Ends</dt>
              <dd><?= $date($user['probation_end_date']) ?></dd>
            </div>
            <?php endif; ?>            
            <div class="col-6">
              <dt>Office</dt>
              <dd><?= $esc($user['office_name'] ?? '—') ?></dd>
            </div>
            <?php if (!empty($user['emp_phone'])): ?>
            <div class="col-6">
              <dt>Phone</dt>
              <dd><?= $esc($user['emp_phone']) ?></dd>
            </div>
            <?php endif; ?>            
            <div class="col-12">
              <dt>Email</dt>
              <dd class="text-truncate"><?= $esc($user['email'] ?? '—') ?></dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- ── Active Contract ───────────────────────────────── -->
      <?php $sectionOpen('contract','ti ti-file-text','Active Contract'); ?>
        <div class="p-3">
          <?php if ($contract): ?>
            <dl class="ep-profile-meta row mb-0">
              <div class="col-6"><dt>Type</dt><dd><?= $esc($contract['contract_type']) ?></dd></div>
              <div class="col-6"><dt>Status</dt><dd><?= $statusBadge($contract['status']) ?></dd></div>
              <div class="col-6"><dt>Start</dt><dd><?= $date($contract['start_date']) ?></dd></div>
              <div class="col-6"><dt>End</dt><dd><?= $date($contract['end_date']) ?></dd></div>
              <div class="col-6"><dt>Notice Period</dt><dd><?= (int)($contract['notice_period_days'] ?? 0) ?>d</dd></div>
              <div class="col-6"><dt>Renewable</dt><dd><?= $contract['is_renewable'] ? 'Yes' : 'No' ?></dd></div>
              <?php if ($contract['signed_at']): ?>
              <div class="col-12"><dt>Signed</dt><dd><?= $date($contract['signed_at']) ?></dd></div>
              <?php endif; ?>
            </dl>
          <?php else: ?>
            <p class="text-muted small text-center py-2 mb-0">No contract on record.</p>
          <?php endif; ?>
        </div>
      <?php $sectionClose(); ?>

      <!-- ── Assigned Assets ───────────────────────────────── -->
      <?php $sectionOpen('assets','ti ti-device-laptop','Assigned Assets',
            '<span class="badge bg-light-primary ms-auto">'.(count($assets)).'</span>'); ?>
        <?php if ($assets): ?>
          <table class="table table-ep table-bottom-border mb-0">
            <thead><tr><th>Asset</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($assets as $a): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= $esc($a['name']) ?></div>
                  <?php if ($a['serial_no']): ?>
                    <div class="text-muted" style="font-size:10px"><?= $esc($a['serial_no']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="text-muted"><?= $esc($a['asset_type'] ?? '—') ?></td>
                <td><?= $statusBadge($a['status'] ?? 'unknown') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-center text-muted small py-3 mb-0">No assets assigned.</p>
        <?php endif; ?>
      <?php $sectionClose(); ?>

      <!-- ── Documents ─────────────────────────────────────── -->
      <?php $sectionOpen('docs','ti ti-paperclip','Documents',
            '<span class="badge bg-light-primary ms-auto">'.count($documents).'</span>'); ?>
        <?php if ($documents): ?>
          <div class="p-2">
          <?php foreach ($documents as $doc): ?>
            <div class="d-flex align-items-start gap-2 py-2 border-bottom">
              <i class="ti ti-file text-primary mt-1 flex-shrink-0"></i>
              <div style="min-width:0">
                <div class="small fw-semibold text-truncate"><?= $esc($doc['title'] ?: $doc['doc_type']) ?></div>
                <div class="text-muted" style="font-size:10px"><?= $esc($doc['doc_type']) ?></div>
                <?php if ($doc['expiry_date']): ?>
                  <div style="font-size:10px" class="<?= (strtotime($doc['expiry_date']) < time()) ? 'text-danger' : 'text-muted' ?>">
                    Expires: <?= $date($doc['expiry_date']) ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="ms-auto text-muted" style="font-size:10px;white-space:nowrap"><?= $date($doc['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-center text-muted small py-3 mb-0">No documents uploaded.</p>
        <?php endif; ?>
      <?php $sectionClose(); ?>

    </div><!-- /left col -->

    <!-- ════════════════════════════════════════════════════════
         RIGHT COLUMN — KPIs + all detail sections
         ════════════════════════════════════════════════════════ -->
    <div class="col-xl-9 col-lg-8">

      <!-- ── KPI Strip ─────────────────────────────────────── -->
      <div class="row g-2 mb-3">
        <?php
        $attPct  = $kpis['attendance_pct']     ?? null;
        $attCls  = $attColor($attPct);
        $taskCmp = (int)($kpis['tasks_completed'] ?? 0);
        $taskTot = (int)($kpis['tasks_total']     ?? 0);
        $taskPct = $taskTot > 0 ? round($taskCmp / $taskTot * 100) : null;
        ?>
        <div class="col-6 col-md-3">
          <div class="ep-kpi">
            <div class="ep-kpi-label">Attendance</div>
            <div class="ep-kpi-val <?= $attCls ?>"><?= $pct($attPct) ?></div>
            <div class="ep-kpi-sub"><?= $kpis['present_days'] ?? 0 ?>P / <?= $kpis['absent_days'] ?? 0 ?>A days</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="ep-kpi">
            <div class="ep-kpi-label">Tasks Completed</div>
            <div class="ep-kpi-val <?= ($kpis['tasks_overdue'] ?? 0) > 0 ? 'text-warning' : '' ?>">
              <?= $taskPct !== null ? $taskPct.'%' : '—' ?>
            </div>
            <div class="ep-kpi-sub"><?= $taskCmp ?>/<?= $taskTot ?> · <?= $kpis['tasks_overdue'] ?? 0 ?> overdue</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="ep-kpi">
            <div class="ep-kpi-label">Latest Eval Rating</div>
            <div class="ep-kpi-val"><?= $kpis['latest_eval_rating'] !== null ? $num($kpis['latest_eval_rating'],1) : '—' ?></div>
            <div class="ep-kpi-sub"><?= $kpis['latest_eval_date'] ? $date($kpis['latest_eval_date']) : 'No approved eval' ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="ep-kpi">
            <div class="ep-kpi-label">Leave Days Taken</div>
            <div class="ep-kpi-val"><?= $num($kpis['leave_days_taken'] ?? 0, 1) ?></div>
            <div class="ep-kpi-sub">Approved in period</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="ep-kpi">
            <div class="ep-kpi-label">Signoff Avg Pts</div>
            <div class="ep-kpi-val"><?= $kpis['signoff_avg_pts'] !== null ? $num($kpis['signoff_avg_pts'],1) : '—' ?></div>
            <div class="ep-kpi-sub"><?= $kpis['signoff_count'] ?? 0 ?> submissions</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="ep-kpi">
            <div class="ep-kpi-label">Loan Balance</div>
            <div class="ep-kpi-val <?= ($kpis['loan_balance'] ?? 0) > 0 ? 'text-warning' : '' ?>">
              <?= $num($kpis['loan_balance'] ?? 0, 0) ?>
            </div>
            <div class="ep-kpi-sub">Active loans outstanding</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="ep-kpi">
            <div class="ep-kpi-label">Late Arrivals</div>
            <div class="ep-kpi-val <?= ($att['late_arrivals'] ?? 0) > 0 ? 'text-warning' : 'text-success' ?>">
              <?= (int)($att['late_arrivals'] ?? 0) ?>
            </div>
            <div class="ep-kpi-sub">Avg check-in: <?= $esc($att['avg_checkin_time'] ?? '—') ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="ep-kpi">
            <div class="ep-kpi-label">Advance Balance</div>
            <div class="ep-kpi-val <?= ($advances['total_balance'] ?? 0) > 0 ? 'text-warning' : '' ?>">
              <?= $num($advances['total_balance'] ?? 0, 0) ?>
            </div>
            <div class="ep-kpi-sub"><?= (int)($advances['pending'] ?? 0) ?> pending requests</div>
          </div>
        </div>
      </div>

      <!-- ── Attendance Detail ──────────────────────────────── -->
      <?php $sectionOpen('attendance','ti ti-calendar-check','Attendance Summary'); ?>
        <div class="p-3">
          <div class="row g-3 mb-3">
            <?php
            $attItems = [
                ['P', 'Present',  'bg-success', $att['P'] ?? 0],
                ['A', 'Absent',   'bg-danger',  $att['A'] ?? 0],
                ['L', 'On Leave', 'bg-warning', $att['L'] ?? 0],
                ['H', 'Holiday',  'bg-info',    $att['H'] ?? 0],
            ];
            $total = max(1, (int)($att['total'] ?? 1));
            foreach ($attItems as [$key, $label, $col, $cnt]):
            ?>
            <div class="col-6 col-md-3">
              <div class="border rounded p-2 text-center">
                <div class="fw-bold fs-4 <?= $col === 'bg-success' ? 'text-success' : ($col === 'bg-danger' ? 'text-danger' : ($col === 'bg-warning' ? 'text-warning' : 'text-info')) ?>">
                  <?= (int)$cnt ?>
                </div>
                <div class="small text-muted"><?= $label ?></div>
                <div class="ep-att-bar mt-1">
                  <div class="ep-att-bar-fill <?= $col ?>" style="width:<?= min(100, round($cnt/$total*100)) ?>%"></div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if ($attLogs): ?>
            <div class="small fw-semibold text-muted mb-2">Recent Check-in / Check-out Log</div>
            <table class="table table-ep table-bottom-border mb-0">
              <thead><tr><th>Date &amp; Time</th><th>Event</th><th>Type</th><th>Status</th><th>IP</th></tr></thead>
              <tbody>
              <?php foreach (array_slice($attLogs, 0, 15) as $log): ?>
                <tr>
                  <td><?= $dt($log['datetime']) ?></td>
                  <td><?= $statusBadge($log['status']) ?></td>
                  <td><span class="badge bg-light-secondary text-muted"><?= $esc($log['log_type']) ?></span></td>
                  <td><?= $statusBadge($log['approval_status']) ?></td>
                  <td class="text-muted"><?= $esc($log['ip_address'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      <?php $sectionClose(); ?>

      <!-- ── Leave Summary ─────────────────────────────────── -->
      <?php $sectionOpen('leaves','ti ti-beach','Leave History',
            '<span class="badge bg-light-primary ms-auto">'.count($leaves['list'] ?? []).' requests</span>'); ?>
        <div class="p-3 pb-0">
          <div class="row g-2 mb-3">
            <?php
            $lt = $leaves['totals'] ?? [];
            foreach ([['approved','Approved','text-success'],['pending','Pending','text-warning'],
                      ['rejected','Rejected','text-danger'],['days_taken','Days Taken','text-primary']] as [$k,$lbl,$cls]):
            ?>
              <div class="col-6 col-md-3">
                <div class="border rounded p-2 text-center">
                  <div class="fw-bold fs-5 <?= $cls ?>"><?= is_float($lt[$k] ?? 0) ? $num($lt[$k],1) : (int)($lt[$k] ?? 0) ?></div>
                  <div class="small text-muted"><?= $lbl ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if (!empty($leaves['list'])): ?>
          <table class="table table-ep table-bottom-border mb-0">
            <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th>Reason</th></tr></thead>
            <tbody>
            <?php foreach ($leaves['list'] as $lv): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= $esc($lv['leave_type'] ?? '—') ?></div>
                  <div class="text-muted" style="font-size:10px"><?= $esc($lv['paid_type'] ?? '') ?></div>
                </td>
                <td><?= $date($lv['start_date']) ?></td>
                <td><?= $date($lv['end_date']) ?></td>
                <td><strong><?= $num($lv['total_days'],1) ?></strong></td>
                <td><?= $statusBadge($lv['status']) ?></td>
                <td class="text-muted"><?= $esc(mb_strimwidth($lv['reason'] ?? '', 0, 40, '…')) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: echo '<p class="text-center text-muted small py-3 mb-0">No leave records in period.</p>'; endif; ?>
      <?php $sectionClose(); ?>

      <!-- ── Signoff Submissions ────────────────────────────── -->
      <?php $sectionOpen('signoffs','ti ti-check','Daily Signoff / Performance',
            '<span class="badge bg-light-primary ms-auto">'.(int)($signoffs['total_submissions'] ?? 0).' submissions</span>'); ?>
        <div class="p-3 pb-0">
          <div class="row g-2 mb-3">
            <?php foreach ([
              ['Avg Points', $num($signoffs['avg_points'] ?? 0,2), ''],
              ['Avg Targets', $num($signoffs['avg_targets'] ?? 0,2), ''],
              ['Reviewed', (int)($signoffs['reviewed_count'] ?? 0), 'text-success'],
              ['Pending Review', (int)($signoffs['pending_review'] ?? 0), ($signoffs['pending_review'] ?? 0) > 0 ? 'text-warning' : ''],
            ] as [$lbl,$val,$cls]): ?>
              <div class="col-6 col-md-3">
                <div class="border rounded p-2 text-center">
                  <div class="fw-bold fs-5 <?= $cls ?>"><?= $val ?></div>
                  <div class="small text-muted"><?= $lbl ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if (!empty($signoffs['list'])): ?>
          <table class="table table-ep table-bottom-border mb-0">
            <thead><tr><th>Date</th><th>Form</th><th>Points</th><th>Targets</th><th>Status</th><th>Reviewed By</th></tr></thead>
            <tbody>
            <?php foreach ($signoffs['list'] as $so): ?>
              <tr>
                <td><?= $date($so['submission_date']) ?></td>
                <td class="text-muted"><?= $esc($so['form_title'] ?? '—') ?></td>
                <td><strong><?= $num($so['total_points'] ?? 0, 2) ?></strong></td>
                <td><?= $num($so['achieved_targets'] ?? 0, 2) ?></td>
                <td><?= $statusBadge($so['status'] ?? 'submitted') ?></td>
                <td class="text-muted"><?= $esc($so['reviewer_name'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: echo '<p class="text-center text-muted small py-3 mb-0">No signoff submissions in period.</p>'; endif; ?>
      <?php $sectionClose(); ?>

      <!-- ── Evaluations ────────────────────────────────────── -->
      <?php $sectionOpen('evals','ti ti-star','Performance Evaluations',
            '<span class="badge bg-light-primary ms-auto">'.(int)($evals['total'] ?? 0).' evals</span>'); ?>
        <div class="p-3 pb-0">
          <div class="row g-2 mb-3">
            <?php foreach ([
              ['Total Evals',  (int)($evals['total'] ?? 0), ''],
              ['Approved',     (int)($evals['approved'] ?? 0), 'text-success'],
              ['Avg Rating',   $evals['avg_rating'] !== null ? $num($evals['avg_rating'],2) : '—', 'text-primary'],
            ] as [$lbl,$val,$cls]): ?>
              <div class="col-4">
                <div class="border rounded p-2 text-center">
                  <div class="fw-bold fs-5 <?= $cls ?>"><?= $val ?></div>
                  <div class="small text-muted"><?= $lbl ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if (!empty($evals['list'])): ?>
          <table class="table table-ep table-bottom-border mb-0">
            <thead><tr><th>Period</th><th>Type</th><th>Template</th><th>Att%</th><th>Rating</th><th>Status</th><th>Reviewer</th></tr></thead>
            <tbody>
            <?php foreach ($evals['list'] as $ev): ?>
              <tr>
                <td><?= $esc($ev['review_period']) ?><br><small class="text-muted"><?= $date($ev['review_date']) ?></small></td>
                <td><span class="badge bg-light-secondary text-muted"><?= $esc(ucwords(str_replace('_',' ',$ev['review_type']))) ?></span></td>
                <td class="text-muted"><?= $esc($ev['template_name'] ?? '—') ?></td>
                <td><?= $ev['att_pct'] !== null ? $num($ev['att_pct'],1).'%' : '—' ?></td>
                <td><strong><?= $ev['score_ratings'] !== null ? $num($ev['score_ratings'],1) : '—' ?></strong></td>
                <td><?= $statusBadge($ev['status']) ?></td>
                <td class="text-muted"><?= $esc($ev['reviewer_name'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: echo '<p class="text-center text-muted small py-3 mb-0">No evaluations on record.</p>'; endif; ?>
      <?php $sectionClose(); ?>

      <!-- ── Latest Evaluation Detail ──────────────────────── -->
      <?php if ($latestEval): ?>
      <?php $sectionOpen('lateval','ti ti-clipboard-list','Latest Evaluation — Detailed Breakdown'); ?>
        <div class="p-3">
          <div class="row g-2 mb-3 small">
            <div class="col-md-6">
              <strong>Template:</strong> <?= $esc($latestEval['template_name'] ?? '—') ?><br>
              <strong>Period:</strong> <?= $esc($latestEval['review_period'] ?? '—') ?><br>
              <strong>Date:</strong> <?= $date($latestEval['review_date']) ?><br>
              <strong>Reviewer:</strong> <?= $esc($latestEval['reviewer_name'] ?? '—') ?>
            </div>
            <div class="col-md-6">
              <strong>Status:</strong> <?= $statusBadge($latestEval['status']) ?><br>
              <strong>Overall Verdict:</strong> <?= $esc(mb_strimwidth($latestEval['overall_verdict'] ?? '—', 0, 80, '…')) ?><br>
              <?php if ($latestEval['supervisor_comments']): ?>
                <strong>Supervisor Notes:</strong> <?= $esc(mb_strimwidth($latestEval['supervisor_comments'], 0, 80, '…')) ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Sections + Criteria -->
          <?php foreach (($latestEval['sections'] ?? []) as $sec): ?>
            <div class="ep-eval-section">
              <div class="fw-semibold small mb-2"><?= $esc($sec['section_label']) ?></div>
              <?php if (!empty($sec['criteria'])): ?>
                <table class="table table-ep mb-0">
                  <thead><tr><th style="width:35%">Criteria</th><th>Type</th><th>Score / Result</th><th>Target</th><th>Actual</th><th>%</th><th>Comments</th></tr></thead>
                  <tbody>
                  <?php foreach ($sec['criteria'] as $cr): ?>
                    <tr>
                      <td><?= $esc($cr['label']) ?></td>
                      <td><span class="badge bg-light-secondary text-muted" style="font-size:9px"><?= $esc($cr['criteria_type']) ?></span></td>
                      <td>
                        <?php if ($cr['criteria_type'] === 'rating' && $cr['score'] !== null): ?>
                          <strong><?= (int)$cr['score'] ?>/5</strong>
                          <div class="ep-score-bar"><div class="ep-score-fill" style="width:<?= min(100,(int)$cr['score']*20) ?>%"></div></div>
                        <?php elseif ($cr['criteria_type'] === 'pass_fail'): ?>
                          <?= $cr['pass_fail'] ? $statusBadge($cr['pass_fail']) : '—' ?>
                        <?php elseif ($cr['criteria_type'] === 'target'): ?>
                          <?= $cr['target_pass_fail'] ? $statusBadge($cr['target_pass_fail']) : '—' ?>
                        <?php else: ?>
                          <?= $esc($cr['selected_option'] ?? '—') ?>
                        <?php endif; ?>
                      </td>
                      <td class="text-muted"><?= $cr['target_month'] !== null ? $num($cr['target_month'],1) : ($cr['target_day'] !== null ? $num($cr['target_day'],1).'/d' : '—') ?></td>
                      <td class="text-muted"><?= $cr['actual_month'] !== null ? $num($cr['actual_month'],1) : '—' ?></td>
                      <td><?= $cr['ach_pct'] !== null ? '<strong>'.$num($cr['ach_pct'],1).'%</strong>' : '—' ?></td>
                      <td class="text-muted"><?= $esc(mb_strimwidth($cr['comments'] ?? '', 0, 40, '…')) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <p class="text-muted small mb-0">No criteria in this section.</p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <!-- Goals -->
          <?php if (!empty($latestEval['goals'])): ?>
            <div class="mt-3">
              <div class="fw-semibold small mb-2">Goals &amp; Training Needs</div>
              <table class="table table-ep table-bottom-border mb-0">
                <thead><tr><th>#</th><th>Goal</th><th>Training Need</th></tr></thead>
                <tbody>
                <?php foreach ($latestEval['goals'] as $gi => $g): ?>
                  <tr>
                    <td class="text-muted"><?= $gi+1 ?></td>
                    <td><?= $esc($g['goal'] ?? '—') ?></td>
                    <td class="text-muted"><?= $esc($g['training_need'] ?? '—') ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      <?php $sectionClose(); ?>
      <?php endif; ?>

      <!-- ── Tasks ──────────────────────────────────────────── -->
      <?php $sectionOpen('tasks','ti ti-subtask','Tasks',
            '<span class="badge bg-light-primary ms-auto">'.(int)($tasks['total'] ?? 0).' total</span>'); ?>
        <div class="p-3 pb-0">
          <div class="row g-2 mb-3">
            <?php foreach ($tasks['by_status'] ?? [] as $ts => $cnt): ?>
              <div class="col-auto">
                <div class="border rounded px-3 py-2 text-center" style="min-width:80px">
                  <div class="fw-bold"><?= (int)$cnt ?></div>
                  <div class="small text-muted"><?= $esc(ucwords(str_replace('_',' ',$ts))) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (($tasks['overdue'] ?? 0) > 0): ?>
              <div class="col-auto">
                <div class="border border-danger rounded px-3 py-2 text-center" style="min-width:80px">
                  <div class="fw-bold text-danger"><?= (int)$tasks['overdue'] ?></div>
                  <div class="small text-danger">Overdue</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php if (!empty($tasks['list'])): ?>
          <table class="table table-ep table-bottom-border mb-0">
            <thead><tr><th>Task</th><th>Priority</th><th>Status</th><th>Due Date</th><th>Finished</th></tr></thead>
            <tbody>
            <?php foreach ($tasks['list'] as $tk): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= $esc(mb_strimwidth($tk['name'] ?? '', 0, 50, '…')) ?></div>
                </td>
                <td><?= $priorityBadge($tk['priority'] ?? 'normal') ?></td>
                <td><?= $statusBadge($tk['status'] ?? 'not_started') ?></td>
                <td class="<?= ($tk['duedate'] && $tk['duedate'] < date('Y-m-d') && !in_array($tk['status'],['completed','cancelled'])) ? 'text-danger' : 'text-muted' ?>">
                  <?= $date($tk['duedate']) ?>
                </td>
                <td class="text-muted"><?= $tk['datefinished'] ? $date($tk['datefinished']) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: echo '<p class="text-center text-muted small py-3 mb-0">No tasks in period.</p>'; endif; ?>
      <?php $sectionClose(); ?>

      <!-- ── Employee Movements ─────────────────────────────── -->
      <?php $sectionOpen('movements','ti ti-arrows-exchange','Career Movements &amp; History',
            '<span class="badge bg-light-primary ms-auto">'.count($movements).'</span>'); ?>
        <?php if ($movements): ?>
          <div class="p-3">
          <?php foreach ($movements as $mi => $mv): ?>
            <div class="d-flex gap-3">
              <div class="d-flex flex-column align-items-center">
                <div class="ep-timeline-dot"></div>
                <?php if ($mi < count($movements)-1): ?><div class="ep-timeline-line"></div><?php endif; ?>
              </div>
              <div class="pb-3" style="min-width:0;flex:1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge bg-light-primary"><?= $esc(ucwords(str_replace('_',' ',$mv['movement_type'] ?? ''))) ?></span>
                  <span class="text-muted small"><?= $date($mv['effective_date']) ?></span>
                </div>
                <div class="small mt-1">
                  <?php if ($mv['from_title'] || $mv['to_title']): ?>
                    <span class="text-muted">Title:</span>
                    <?= $esc($mv['from_title'] ?? '—') ?> → <strong><?= $esc($mv['to_title'] ?? '—') ?></strong><br>
                  <?php endif; ?>
                  <?php if ($mv['from_department'] || $mv['to_department']): ?>
                    <span class="text-muted">Dept:</span>
                    <?= $esc($mv['from_department'] ?? '—') ?> → <strong><?= $esc($mv['to_department'] ?? '—') ?></strong><br>
                  <?php endif; ?>
                  <?php if ($mv['from_team'] || $mv['to_team']): ?>
                    <span class="text-muted">Team:</span>
                    <?= $esc($mv['from_team'] ?? '—') ?> → <strong><?= $esc($mv['to_team'] ?? '—') ?></strong><br>
                  <?php endif; ?>
                  <?php if ($mv['reason']): ?>
                    <span class="text-muted">Reason:</span> <?= $esc($mv['reason']) ?><br>
                  <?php endif; ?>
                  <?php if ($mv['created_by_name']): ?>
                    <span class="text-muted">By:</span> <?= $esc($mv['created_by_name']) ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
        <?php else: echo '<p class="text-center text-muted small py-3 mb-0">No movement history.</p>'; endif; ?>
      <?php $sectionClose(); ?>

      <!-- ── Support Tickets ────────────────────────────────── -->
      <?php $sectionOpen('tickets','ti ti-ticket','Support Tickets Assigned',
            '<span class="badge bg-light-primary ms-auto">'.(int)($tickets['total'] ?? 0).'</span>'); ?>
        <?php if (!empty($tickets['list'])): ?>
          <table class="table table-ep table-bottom-border mb-0">
            <thead><tr><th>Code</th><th>Subject</th><th>Dept</th><th>Priority</th><th>Status</th><th>Created</th><th>Resolved</th></tr></thead>
            <tbody>
            <?php foreach ($tickets['list'] as $tk): ?>
              <tr>
                <td class="text-muted"><?= $esc($tk['code']) ?></td>
                <td><?= $esc(mb_strimwidth($tk['subject'] ?? '', 0, 45, '…')) ?></td>
                <td class="text-muted"><?= $esc($tk['department_name'] ?? '—') ?></td>
                <td><?= $priorityBadge($tk['priority'] ?? 'normal') ?></td>
                <td><?= $statusBadge($tk['status']) ?></td>
                <td class="text-muted"><?= $date($tk['created_at']) ?></td>
                <td class="text-muted"><?= $tk['resolved_at'] ? $date($tk['resolved_at']) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: echo '<p class="text-center text-muted small py-3 mb-0">No tickets assigned in period.</p>'; endif; ?>
      <?php $sectionClose(); ?>

      <!-- ── Requests ───────────────────────────────────────── -->
      <?php $sectionOpen('requests','ti ti-send','Requests Submitted',
            '<span class="badge bg-light-primary ms-auto">'.(int)($requests['total'] ?? 0).'</span>'); ?>
        <?php if (!empty($requests['list'])): ?>
          <table class="table table-ep table-bottom-border mb-0">
            <thead><tr><th>Ref</th><th>Type</th><th>Priority</th><th>Status</th><th>Submitted</th><th>Approved</th><th>Completed</th></tr></thead>
            <tbody>
            <?php foreach ($requests['list'] as $rq): ?>
              <tr>
                <td class="text-muted"><?= $esc($rq['request_no']) ?></td>
                <td><span class="badge bg-light-secondary text-muted"><?= $esc($rq['type'] ?? '—') ?></span></td>
                <td><?= $priorityBadge($rq['priority'] ?? 'normal') ?></td>
                <td><?= $statusBadge($rq['status']) ?></td>
                <td class="text-muted"><?= $date($rq['submitted_at']) ?></td>
                <td class="text-muted"><?= $rq['approved_at']  ? $date($rq['approved_at'])  : '—' ?></td>
                <td class="text-muted"><?= $rq['completed_at'] ? $date($rq['completed_at']) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: echo '<p class="text-center text-muted small py-3 mb-0">No requests in period.</p>'; endif; ?>
      <?php $sectionClose(); ?>

      <!-- ── Exit Information (if applicable) ──────────────── -->
      <?php if ($exitInfo): ?>
      <?php $sectionOpen('exitdetail','ti ti-door-exit','Exit Details'); ?>
        <div class="p-3">
          <div class="row g-3 small">
            <div class="col-md-6">
              <dl class="ep-profile-meta mb-0">
                <div class="row">
                  <div class="col-6"><dt>Exit Type</dt><dd><?= $esc($exitInfo['exit_type']) ?></dd></div>
                  <div class="col-6"><dt>Status</dt><dd><?= $statusBadge($exitInfo['exit_status']) ?></dd></div>
                  <div class="col-6"><dt>Exit Date</dt><dd><?= $date($exitInfo['exit_date']) ?></dd></div>
                  <div class="col-6"><dt>Last Working Day</dt><dd><?= $date($exitInfo['last_working_date']) ?></dd></div>
                  <div class="col-6"><dt>Notice Period Served</dt><dd><?= $exitInfo['notice_period_served'] ? 'Yes' : 'No' ?></dd></div>
                  <div class="col-6"><dt>Interview Date</dt><dd><?= $date($exitInfo['exit_interview_date']) ?></dd></div>
                </div>
              </dl>
            </div>
            <div class="col-md-6">
              <dl class="ep-profile-meta mb-0">
                <div class="row">
                  <div class="col-6"><dt>Checklist Done</dt><dd><?= $exitInfo['checklist_completed'] ? '✓ Yes' : '✗ No' ?></dd></div>
                  <div class="col-6"><dt>Assets Returned</dt><dd><?= $exitInfo['assets_returned'] ? '✓ Yes' : '✗ No' ?></dd></div>
                  <?php if ($exitInfo['final_settlement_amount']): ?>
                  <div class="col-6"><dt>Settlement Amount</dt><dd><?= $num($exitInfo['final_settlement_amount']) ?></dd></div>
                  <div class="col-6"><dt>Settlement Date</dt><dd><?= $date($exitInfo['final_settlement_date']) ?></dd></div>
                  <?php endif; ?>
                  <?php if ($exitInfo['reason']): ?>
                  <div class="col-12"><dt>Reason</dt><dd><?= $esc($exitInfo['reason']) ?></dd></div>
                  <?php endif; ?>
                  <?php if ($exitInfo['remarks']): ?>
                  <div class="col-12"><dt>Remarks</dt><dd><?= $esc(mb_strimwidth($exitInfo['remarks'],0,120,'…')) ?></dd></div>
                  <?php endif; ?>
                </div>
              </dl>
            </div>
          </div>
        </div>
      <?php $sectionClose(); ?>
      <?php endif; ?>

    </div><!-- /right col -->
  </div><!-- /row -->
</div>