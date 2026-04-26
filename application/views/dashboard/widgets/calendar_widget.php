<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="card dashboard-widget shadow-sm rounded mb-4" style="min-height:380px;">
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold text-primary">
            <i class="ti ti-calendar-event me-1"></i>
            My Calendar
        </span>
        <a href="<?= site_url('calendar') ?>" class="btn btn-header btn-light-primary py-1 px-2">
            Open Full Calendar
        </a>
    </div>
    <div class="card-body p-3 pt-2" style="font-size: 14px;">
        <!-- The full calendar loads here -->
        <div id="dashboard-calendar" class="app-calendar" style="min-height:650px;"></div>
    </div>
</div>
<!-- Modern FullCalendar CSS and JS (v6.x) -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<div id="dashboard-calendar"></div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('dashboard-calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      initialView: 'dayGridMonth',
      events: {
        url: "<?= site_url('calendar/get_events') ?>",
        method: 'GET'
      },
      height: 320,
      eventLimit: true,
      editable: true,
      droppable: true
      // ...rest of your config
    });
    calendar.render();
  });
</script>
