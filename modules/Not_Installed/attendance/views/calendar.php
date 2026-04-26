<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- FullCalendar.io CSS/JS (CDN) -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">

        <a href="<?= site_url('attendance') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-clock"></i> Attendance
        </a>
        <a href="<?= site_url('attendance/leaves') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-clipboard-list"></i> Leaves
        </a>
        <a href="<?= site_url('attendance/calendar') ?>"
           class="btn btn-primary btn-header">
            <i class="ti ti-calendar-event"></i> Calendar
        </a>
        <a href="<?= site_url('attendance/tracker') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-map-pin"></i> Tracker
        </a>
        
        <div class="btn-divider"></div>

        <a href="<?= site_url('calendar') ?>"
           class="btn btn-outline-primary btn-header">
            <i class="ti ti-calendar"></i> App Calendar
        </a>
        
      </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div id='leave-calendar' class="app-calendar"></div>
        </div>
    </div>
</div>

<!-- Modal for leave details -->
<div class="modal fade" id="leaveDetailModal" tabindex="-1" aria-labelledby="leaveDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="leaveDetailModalLabel">Leave Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="leaveDetailBody">
        <!-- Dynamic content here -->
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('leave-calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 800,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },
        events: <?= json_encode($events) ?>,
        eventClick: function(info) {
            var ep = info.event.extendedProps || {};
            var isLeave = ep.event_type === 'leave';
            var isAttendance = ep.event_type === 'attendance';
            var eventSource = isLeave ? 'Leave Requests' : (isAttendance ? 'Daily Attendance' : 'Calendar Event');

            // Prepare main structure
            var html = '<div class="table-responsive"><table class="table table-bordered mb-0">';
            html += `<tr><th width="32%">Source</th><td>${eventSource}</td></tr>`;

            <?php if ($is_admin): ?>
                html += `<tr><th>User</th><td>${ep.user || '<span class="text-muted">N/A</span>'}</td></tr>`;
            <?php endif; ?>

            html += `<tr><th>Type</th><td>${
                isLeave ? '<span class="badge bg-primary">Leave</span>' :
                isAttendance ? '<span class="badge bg-secondary">Attendance</span>' :
                '<span class="badge bg-info">Announcement</span>'
            }</td></tr>`;

            // Dates
            var start = info.event.start;
            var end = info.event.end;
            html += `<tr><th>Date</th><td>${start ? start.toLocaleDateString() : '-'}`;
            if (isLeave && end) {
                var endDate = new Date(end);
                endDate.setDate(endDate.getDate() - 1); // fullcalendar end is exclusive
                if (start.toDateString() !== endDate.toDateString()) {
                    html += ` to ${endDate.toLocaleDateString()}`;
                }
            }
            html += `</td></tr>`;

            // Status badge
            var status = ep.status || '-';
            var badge = '<span class="badge bg-secondary">' + status + '</span>';
            if (/approved/i.test(status))  badge = '<span class="badge bg-success">' + status + '</span>';
            else if (/pending/i.test(status))  badge = '<span class="badge bg-warning text-dark">' + status + '</span>';
            else if (/rejected|absent/i.test(status)) badge = '<span class="badge bg-danger">' + status + '</span>';
            else if (/holiday/i.test(status)) badge = '<span class="badge bg-info">' + status + '</span>';

            html += `<tr><th>Status</th><td>${badge}</td></tr>`;

            // Notes (for leave or custom events)
            if (isLeave && ep.notes) {
                html += `<tr><th>Notes</th><td>${ep.notes}</td></tr>`;
            } else if (ep.event_type === 'custom' && ep.description) {
                html += `<tr><th>Description</th><td>${ep.description}</td></tr>`;
            }

            html += '</table></div>';
            document.getElementById('leaveDetailBody').innerHTML = html;
            var modal = new bootstrap.Modal(document.getElementById('leaveDetailModal'));
            modal.show();
        }
    });
    calendar.render();
});
</script>

