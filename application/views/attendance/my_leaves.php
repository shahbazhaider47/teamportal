<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI  =& get_instance();
$uid = (int)($CI->session->userdata('user_id') ?? 0);

$stats    = $stats    ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0, 'total_applied' => 0];
$leaves   = $leaves   ?? [];
$balances = $balances ?? [];

$canApply = !empty($can_apply);

// Status badge helper
function leave_badge(string $status): string {
    $map = [
        'approved'  => 'success',
        'pending'   => 'warning',
        'rejected'  => 'danger',
        'cancelled' => 'secondary',
    ];
    $cls = $map[strtolower($status)] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '-lt text-' . $cls . ' fw-semibold text-uppercase" 
                  style="font-size:.7rem;letter-spacing:.04em">'
           . html_escape(ucfirst($status)) . '</span>';
}
?>

<div class="container-fluid">

<div class="view-header mb-3">
    <div class="view-icon me-3"><i class="ti ti-clipboard-check"></i></div>
        <div class="flex-grow-1">
          <div class="view-title"><?= $page_title ?> — <?= date('Y') ?></div>
        </div>
        
    <div class="ms-auto d-flex gap-2">

    <?php if ($canApply): ?>
      <button class="btn btn-primary btn-header"
              data-bs-toggle="modal"
              data-bs-target="#addLeaveModal">
        <i class="ti ti-plus me-1"></i> Apply for Leave
      </button>
    <?php endif; ?>
          
    </div>
</div>

<!-- ── KPI Strip ───────────────────────────────────────────────── -->
<div class="row g-2 mb-3">
    <?php
    $kpis = [
        ['Total Applied', $stats['total_applied'] ?? 0, 'ti ti-files',        '#6366f118'],
        ['Approved',      $stats['approved'] ?? 0,      'ti ti-circle-check', '#16a34a18'],
        ['Pending',       $stats['pending'] ?? 0,       'ti ti-clock',        '#f59e0b18'],
        ['Rejected',      $stats['rejected'] ?? 0,      'ti ti-circle-x',     '#ef444418'],
    ];
    ?>

    <?php foreach ($kpis as $m): ?>
    <div class="col">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:<?= $m[3] ?>;">
                <i class="<?= $m[2] ?>"></i>
            </div>
            <div>
                <div class="kpi-value"><?= number_format($m[1]) ?></div>
                <div class="kpi-label"><?= $m[0] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
    
  <!-- ── Main body: table + calendar ───────────────────────────────────── -->
  <div class="row g-3">

    <!-- Left: leave history table -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm" style="border-radius:10px">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-2 px-3">
          <h6 class="mb-0 fw-semibold">
            <i class="ti ti-list me-2 text-muted"></i>Leave History
          </h6>
          <span class="badge bg-blue-lt text-blue"><?= count($leaves) ?> records</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="myLeavesTable" style="font-size:.83rem">
              <thead class="table-light">
                <tr>
                  <th class="ps-3">Type</th>
                  <th>Duration</th>
                  <th class="text-center">Days</th>
                  <th class="text-center">Status</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($leaves)): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                      <i class="ti ti-calendar-off d-block mb-2" style="font-size:2rem;opacity:.4"></i>
                      No leave records found.
                      <?php if ($canApply): ?>
                        <br>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#addLeaveModal"
                           class="btn btn-sm btn-primary mt-2">
                          Apply for your first leave
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($leaves as $lv):
                    $status    = strtolower((string)($lv['status'] ?? 'pending'));
                    $canCancel = in_array($status, ['pending'], true);
                    $isSame    = $lv['start_date'] === $lv['end_date'];
                  ?>
                  <tr>
                    <td class="ps-3 py-2">
                      <div class="fw-semibold"><?= html_escape($lv['leave_type_name'] ?? '—') ?></div>
                      <?php if (!empty($lv['reason'])): ?>
                        <div class="text-muted text-truncate" style="max-width:160px;font-size:.75rem"
                             title="<?= html_escape($lv['reason']) ?>">
                          <?= html_escape($lv['reason']) ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="py-2">
                      <?php if ($isSame): ?>
                        <span><?= date('d M Y', strtotime($lv['start_date'])) ?></span>
                        <?php if (!empty($lv['start_time']) && !empty($lv['end_time'])): ?>
                          <br>
                          <small class="text-muted">
                            <?= date('H:i', strtotime($lv['start_time'])) ?> –
                            <?= date('H:i', strtotime($lv['end_time'])) ?>
                          </small>
                        <?php endif; ?>
                      <?php else: ?>
                        <span><?= date('d M', strtotime($lv['start_date'])) ?></span>
                        <i class="ti ti-arrow-right text-muted mx-1" style="font-size:.7rem"></i>
                        <span><?= date('d M Y', strtotime($lv['end_date'])) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center py-2">
                      <span class="fw-semibold"><?= number_format((float)($lv['total_days'] ?? 0), 1) ?></span>
                      <span class="text-muted" style="font-size:.72rem">d</span>
                    </td>
                    <td class="text-center py-2">
                      <?= leave_badge($status) ?>
                    </td>
                    <td class="text-center py-2">
                      <?php if (!empty($lv['attachment_path'])): ?>
                        <a href="<?= base_url(html_escape($lv['attachment_path'])) ?>"
                           target="_blank"
                           class="btn btn-icon btn-sm btn-ghost-secondary"
                           title="View attachment">
                          <i class="ti ti-paperclip"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($canCancel && $canApply): ?>
                        <button type="button"
                                class="btn btn-icon btn-sm btn-ghost-danger js-cancel-leave"
                                data-id="<?= (int)$lv['id'] ?>"
                                title="Cancel leave">
                          <i class="ti ti-x"></i>
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Calendar + Balances -->
    <div class="col-lg-5">

      <!-- Mini Calendar -->
      <div class="card border-0 shadow-sm mb-3" style="border-radius:10px">
        <div class="card-header bg-white border-bottom py-2 px-3">
          <h6 class="mb-0 fw-semibold">
            <i class="ti ti-calendar me-2 text-muted"></i>Leave Calendar
          </h6>
        </div>
        <div class="card-body p-2">
          <div id="myLeaveCalendar" style="min-height:280px"></div>
        </div>
      </div>

      <!-- Leave Balances -->
      <?php if (!empty($balances)): ?>
      <div class="card border-0 shadow-sm" style="border-radius:10px">
        <div class="card-header bg-white border-bottom py-2 px-3">
          <h6 class="mb-0 fw-semibold">
            <i class="ti ti-report me-2 text-muted"></i>Leave Balances
            <small class="text-muted fw-normal ms-1"><?= date('M Y') ?></small>
          </h6>
        </div>
        <div class="card-body py-2 px-3">
          <?php foreach ($balances as $b):
            // Use annual if set, else monthly, else show unlimited
            $allowed = $b['annual_allowed'] ?? $b['monthly_allowed'] ?? null;
            $used    = $b['annual_allowed'] !== null
                         ? $b['annual_used']
                         : ($b['monthly_allowed'] !== null ? $b['monthly_used'] : 0);
            $pct     = ($allowed > 0) ? min(100, round(($used / $allowed) * 100)) : 0;

            $barColor = 'bg-success';
            if ($pct >= 90) $barColor = 'bg-danger';
            elseif ($pct >= 65) $barColor = 'bg-warning';

            $isPaid = strtolower((string)($b['type'] ?? 'paid')) !== 'unpaid';
          ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="fw-semibold" style="font-size:.83rem">
                <?= html_escape($b['name']) ?>
                <?php if (!$isPaid): ?>
                  <span class="badge bg-orange-lt text-orange ms-1" style="font-size:.65rem">Unpaid</span>
                <?php endif; ?>
              </div>
              <div class="text-muted" style="font-size:.78rem">
                <?php if ($allowed !== null): ?>
                  <strong class="text-dark"><?= $used ?></strong> / <?= $allowed ?> days
                <?php else: ?>
                  <strong class="text-dark"><?= $used ?></strong> used
                  <span class="text-muted">(unlimited)</span>
                <?php endif; ?>
              </div>
            </div>
            <?php if ($allowed !== null): ?>
              <div class="progress" style="height:5px;border-radius:3px">
                <div class="progress-bar <?= $barColor ?>"
                     style="width:<?= $pct ?>%"
                     role="progressbar"></div>
              </div>
              <div class="text-muted mt-1" style="font-size:.72rem">
                <?php
                  $rem = $b['annual_allowed'] !== null
                           ? $b['annual_remaining']
                           : $b['monthly_remaining'];
                ?>
                <?= $rem ?> day<?= $rem != 1 ? 's' : '' ?> remaining
                <?= $b['annual_allowed'] !== null ? '(annual)' : '(this month)' ?>
              </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

</div>

<!-- ── Apply Leave Modal (apply-only mode) ───────────────────────────────── -->
<?php $CI =& get_instance(); ?>
<?php if ($canApply): ?>
  <?php $CI->load->view('attendance/modals/add_leave', get_defined_vars()); ?>
<?php endif; ?>

<!-- Cancel confirmation modal -->
<div class="modal fade" id="cancelLeaveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom py-2">
        <h6 class="modal-title fw-semibold">Cancel Leave</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-3">
        <p class="mb-0 text-muted" style="font-size:.88rem">
          Are you sure you want to cancel this leave request?
          This action cannot be undone.
        </p>
      </div>
      <div class="modal-footer py-2 gap-2">
        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Keep it</button>
        <button type="button" class="btn btn-danger btn-sm" id="confirmCancelBtn">
          <i class="ti ti-x me-1"></i> Cancel Leave
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── FullCalendar + page JS ────────────────────────────────────────────── -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
(function () {
  'use strict';

  /* ── Calendar ──────────────────────────────────────────────────────────── */
  const calEl = document.getElementById('myLeaveCalendar');
  if (calEl && window.FullCalendar) {
    const cal = new FullCalendar.Calendar(calEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left:   'prev',
        center: 'title',
        right:  'next',
      },
      height: 'auto',
      eventSources: [{
        url: '<?= site_url('attendance_leaves/ajax_my_leave_events') ?>',
        method: 'GET',
        failure: function () {
          console.warn('Failed to load leave calendar events.');
        },
      }],
      eventDidMount: function (info) {
        const p = info.event.extendedProps;
        info.el.setAttribute('title',
          (p.status ? p.status.charAt(0).toUpperCase() + p.status.slice(1) : '') +
          ' · ' + (p.total_days || '?') + ' day(s)' +
          (p.reason ? '\n' + p.reason : '')
        );
      },
      // Open apply modal on empty date click
      <?php if ($canApply): ?>
      dateClick: function (info) {
        const startEl = document.getElementById('leave_start_date');
        const endEl   = document.getElementById('leave_end_date');
        if (startEl) startEl.value = info.dateStr;
        if (endEl)   endEl.value   = info.dateStr;

        // Trigger recalc in modal
        startEl?.dispatchEvent(new Event('change'));

        const modal = bootstrap.Modal.getOrCreateInstance(
          document.getElementById('addLeaveModal')
        );
        modal.show();
      },
      <?php endif; ?>
    });
    cal.render();
  }

  /* ── Cancel leave ──────────────────────────────────────────────────────── */
  let pendingCancelId = null;
  const cancelModal   = document.getElementById('cancelLeaveModal');
  const confirmBtn    = document.getElementById('confirmCancelBtn');

  document.querySelectorAll('.js-cancel-leave').forEach(function (btn) {
    btn.addEventListener('click', function () {
      pendingCancelId = parseInt(this.dataset.id, 10);
      bootstrap.Modal.getOrCreateInstance(cancelModal).show();
    });
  });

  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      if (!pendingCancelId) return;

      confirmBtn.disabled = true;
      confirmBtn.innerHTML = '<i class="ti ti-loader me-1"></i> Cancelling...';

      fetch('<?= site_url('attendance_leaves/ajax_cancel_leave') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'leave_id=' + pendingCancelId,
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          // Remove row from table
          const btn = document.querySelector('.js-cancel-leave[data-id="' + pendingCancelId + '"]');
          if (btn) {
            const row = btn.closest('tr');
            if (row) row.remove();
          }
          bootstrap.Modal.getOrCreateInstance(cancelModal).hide();

          // Show toast
          showToast('Leave cancelled successfully.', 'success');
        } else {
          showToast(data.message || 'Failed to cancel leave.', 'danger');
          confirmBtn.disabled = false;
          confirmBtn.innerHTML = '<i class="ti ti-x me-1"></i> Cancel Leave';
        }
      })
      .catch(() => {
        showToast('Network error. Please try again.', 'danger');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="ti ti-x me-1"></i> Cancel Leave';
      });
    });
  }

  /* ── Toast helper ──────────────────────────────────────────────────────── */
  function showToast(message, type) {
    type = type || 'info';
    const colorMap = {
      success : { bg: '#2fb344', icon: 'ti-circle-check' },
      danger  : { bg: '#d63939', icon: 'ti-alert-circle' },
      warning : { bg: '#f59f00', icon: 'ti-alert-triangle' },
      info    : { bg: '#4299e1', icon: 'ti-info-circle' },
    };
    const c = colorMap[type] || colorMap.info;

    const wrapper = document.createElement('div');
    wrapper.style.cssText = [
      'position:fixed',
      'bottom:1.5rem',
      'right:1.5rem',
      'z-index:9999',
      'min-width:260px',
    ].join(';');
    wrapper.innerHTML = `
      <div class="alert mb-0 shadow d-flex align-items-center gap-2 py-2 px-3"
           style="background:${c.bg};color:#fff;border-radius:8px;font-size:.85rem">
        <i class="ti ${c.icon}" style="font-size:1.1rem"></i>
        <span>${message}</span>
      </div>`;
    document.body.appendChild(wrapper);
    setTimeout(() => wrapper.remove(), 3500);
  }

})();
</script>