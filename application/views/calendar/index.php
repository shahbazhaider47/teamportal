<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>


<script>
    var base_url = "<?= base_url() ?>";
    var lang = {
      add_new_event: "Add New Event",
      edit_event: "Edit Event",
      event_title_start_required: "Title and Start Date required.",
      event_create_error: "Error creating event.",
      event_update_error: "Error updating event.",
      event_delete_error: "Error deleting event.",
      confirm_delete_event: "Delete this event?"
    };

// Inject date/time format from backend
var app_date_format = "<?= isset($date_format) ? addslashes($date_format) : 'Y-m-d' ?>";
var app_time_format = "<?= isset($time_format) ? addslashes($time_format) : 'H:i' ?>";
</script>
<link rel="stylesheet" href="<?= site_url('assets/vendor/slick/slick.css') ?>">
<link rel="stylesheet" href="<?= site_url('assets/vendor/slick/slick-theme.css') ?>">

<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canAdd    = staff_can('add', 'calendar');
          $canExport  = staff_can('export', 'general');
          $canPrint   = staff_can('print', 'general');
        ?>
    
        <div class="btn-divider"></div>
    
        <!-- Add User -->
        <button type="button"
                id="addEventBtn"
                class="btn <?= $canAdd ? 'btn-primary' : 'btn-disabled' ?> btn-header"
                <?= $canAdd ? '' : 'disabled' ?>
                title="Add New User">
            <i class="ti ti-plus"></i> Add New Event
        </button>
        
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

    <!-- Calendar start -->
    <div class="row calendar app-fullcalender">
        <!-- Draggable Events start -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="header-title"><?= _l('draggable_events') ?></h5>
                </div>
                <div class="card-body">
                <div class="events-list" id="events-list">
                    <?php
                    // 1. Render default draggable events if any are stored
                    if (!empty($default_draggable) && is_array($default_draggable)) :
                        foreach ($default_draggable as $draggable) :
                            // Each $draggable should be an array: ['title' => ..., 'class' => ...]
                            $class = !empty($draggable['class']) ? $draggable['class'] : 'event-primary';
                            $title = !empty($draggable['title']) ? $draggable['title'] : 'Untitled Event';
                    ?>
                        <div class="list-event <?= html_escape($class) ?>" data-class="<?= html_escape($class) ?>">
                            <i class="ti ti-arrows-move me-2"></i><?= html_escape($title) ?>
                        </div>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
                </div>
            </div>

            <!-- Upcoming Events (Dynamic) -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="header-title"><?= _l('upcoming_events') ?></h5>
                    <span class="small text-muted">Next 30 Days</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($events)): ?>
                        <div class="list-group">
                        <?php foreach ($events as $event): ?>
                            <?php
                                // If this is a private event, skip it
                                if (!empty($event['is_private'])) continue;
                        
                                // Detect if this is a public holiday (from extendedProps or country fields)
                                $is_public_holiday = false;
                                if (
                                    (isset($event['extendedProps']['is_public_holiday']) && $event['extendedProps']['is_public_holiday']) ||
                                    (isset($event['is_public_holiday']) && $event['is_public_holiday']) ||
                                    (isset($event['country']) && in_array($event['country'], ['US', 'PK']))
                                ) {
                                    $is_public_holiday = true;
                                }
                            ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 small">
                                        <?= html_escape($event['title']) ?>
                                    </h6>
                                    <small><?= date('M d', strtotime($event['start'])) ?></small>
                                </div>
                                <?php if (!$is_public_holiday && !empty($event['description'])): ?>
                                    <p class="mb-1 small text-muted"><?= html_escape($event['description']) ?></p>
                                <?php endif; ?>
                                <small class="text-muted badge bg-light-primary">
                                    <?php
                                        if ($is_public_holiday) {
                                            if (
                                                (isset($event['extendedProps']['country']) && $event['extendedProps']['country'] === 'PK') ||
                                                (isset($event['country']) && $event['country'] === 'PK')
                                            ) {
                                                echo 'PK Holiday';
                                            } elseif (
                                                (isset($event['extendedProps']['country']) && $event['extendedProps']['country'] === 'US') ||
                                                (isset($event['country']) && $event['country'] === 'US')
                                            ) {
                                                echo 'US Holiday';
                                            } else {
                                                echo 'Public Holiday';
                                            }
                                        } else {
                                            echo 'Company Event';
                                        }
                                    ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted"><?= _l('no_upcoming_events') ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Birthdays -->
            <div class="card mt-3">
              <div class="card-header">
                <h6 class="mb-0">🎉 Upcoming Birthdays</h6>
              </div>
              <div class="card-body">
                <?php if (!empty($birthday_upcoming)): ?>
                  <?php foreach ($birthday_upcoming as $b): ?>
                    <div class="d-flex justify-content-between small mb-2">
                      <span><?= user_profile_small($b['name']) ?></span>
                      <span class="text-muted"><?= date('M d', strtotime($b['birthday'])) ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <small class="text-muted">No upcoming birthdays</small>
                <?php endif; ?>
              </div>
            </div>
            
        </div>
        <!-- Draggable Events end -->

        <!-- Calendar Area -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-body" id="mydraggable">
                    <div id='calendar' class="app-calendar"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Calendar end -->
</div>


<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="eventForm" autocomplete="off" class="app-form">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="eventModalLabel"><?= _l('add_new_event') ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= _l('close') ?>"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="eventId">
          <div class="mb-3">
            <label for="eventTitle" class="form-label"><?= _l('event_title') ?></label>
            <input type="text" class="form-control" id="eventTitle" required>
          </div>
          <div class="mb-3">
            <label for="eventDescription" class="form-label"><?= _l('event_description') ?></label>
            <textarea class="form-control" id="eventDescription"></textarea>
          </div>
          <div class="mb-3">
            <label for="eventStart" class="form-label"><?= _l('event_start') ?></label>
            <input type="datetime-local" class="form-control" id="eventStart" required>
          </div>
          <div class="mb-3">
            <label for="eventEnd" class="form-label"><?= _l('event_end') ?></label>
            <input type="datetime-local" class="form-control" id="eventEnd">
          </div>
          <div class="mb-3">
            <label for="eventColor" class="form-label"><?= _l('event_color') ?></label>
            <select class="form-select" id="eventColor">
              <?php foreach ($event_colors as $class => $label): ?>
                <option value="<?= $class ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="eventPrivate">
            <label class="form-check-label" for="eventPrivate">
              <?= _l('private_event') ?>
            </label>
          </div>
        </div>
        <div class="modal-footer">

        <?php
          $canAdd    = staff_can('add', 'calendar');
          $canEdit   = staff_can('edit', 'calendar');
          $canDelete = staff_can('delete', 'calendar');
        ?>
                
        <button type="button"
                id="deleteEvent"
                class="btn <?= $canDelete ? 'btn-danger' : 'btn-disabled' ?> btn-sm me-auto"
                <?= $canDelete ? '' : 'disabled' ?>>
            <?= _l('delete_event') ?>
        </button>
        
        <button type="button"
                id="saveEvent"
                class="btn <?= ($canAdd || $canEdit) ? 'btn-primary' : 'btn-disabled' ?> btn-sm"
                <?= ($canAdd || $canEdit) ? '' : 'disabled' ?>>
            <?= _l('save_event') ?>
        </button>
        
        <button type="button"
                class="btn btn-secondary btn-sm"
                data-bs-dismiss="modal">
            <?= _l('close') ?>
        </button>

        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="viewEventModal" tabindex="-1" aria-labelledby="viewEventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="viewEventModalLabel">Event Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div><b>Title:</b> <span id="viewEventTitle"></span></div>
        <div><b>Description:</b> <span id="viewEventDescription"></span></div>
        <div><b>Start:</b> <span id="viewEventStart"></span></div>
        <div><b>End:</b> <span id="viewEventEnd"></span></div>
        <div><b>Type:</b> <span id="viewEventType"></span></div>
        <div><b>Status:</b> <span id="viewEventStatus"></span></div>
        <div id="viewEventActions" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.10/dayjs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.10/plugin/customParseFormat.min.js"></script>
<script>dayjs.extend(window.dayjs_plugin_customParseFormat);</script>
<script>
  var base_url = "<?= base_url() ?>";
  var lang = {
    add_new_event: "Add New Event",
    edit_event: "Edit Event",
    event_title_start_required: "Title and Start Date required.",
    event_create_error: "Error creating event.",
    event_update_error: "Error updating event.",
    event_delete_error: "Error deleting event.",
    confirm_delete_event: "Delete this event?"
  };

  // Inject date/time format from backend
  var app_date_format = "<?= isset($date_format) ? addslashes($date_format) : 'Y-m-d' ?>";
  var app_time_format = "<?= isset($time_format) ? addslashes($time_format) : 'H:i' ?>";

  // NEW: permissions for View modal buttons
  var canEditEvent   = <?= json_encode(staff_can('edit', 'calendar')) ?>;
  var canDeleteEvent = <?= json_encode(staff_can('delete', 'calendar')) ?>;
</script>
