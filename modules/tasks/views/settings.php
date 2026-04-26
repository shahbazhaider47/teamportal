<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="card-body">
  <div class="mb-4">
    <p class="text-muted">Configure task workflows, SLAs, notifications, and policies. These settings are immediately applied across list, Kanban, calendar, and Gantt views.</p>
  </div>

<?php
// Normalize incoming settings array to $S (same pattern you used for Support).
$S = [];
if (isset($existing) && is_array($existing)) {
  $S = $existing;
} elseif (isset($existing_data) && is_array($existing_data)) {
  $S = $existing_data;
}

// Safe reads with defaults (opinionated)
$defaultView          = $S['tasks_default_view']                ?? 'list';         // list|kanban|calendar|gantt
$commentsOrder        = $S['tasks_comments_order']              ?? 'descending';   // ascending|descending
$enforceChecklistDone = $S['tasks_enforce_checklist_before_done'] ?? 'no';
$defaultPriority      = $S['tasks_default_priority']            ?? 'normal';       // low|normal|high|urgent
$dueSoonDays          = $S['tasks_due_soon_days']               ?? '2';
$overdueEscalate      = $S['tasks_overdue_escalate']            ?? 'yes';
$allowRecurring       = $S['tasks_allow_recurring']             ?? 'yes';
$maxAttachments       = $S['tasks_max_attachments']             ?? '10';

$notifyAssigneeCreate = $S['tasks_notify_assignee_on_create']   ?? 'yes';
$emailAssigneeCreate  = $S['tasks_email_assignee_on_create']    ?? 'yes';
$notifyFollowersCreate= $S['tasks_notify_followers_on_create']  ?? 'no';
$emailFollowersCreate = $S['tasks_email_followers_on_create']   ?? 'no';
$notifyAssign         = $S['tasks_notify_on_assignment']        ?? 'yes';
$emailAssign          = $S['tasks_email_on_assignment']         ?? 'yes';
$notifyComment        = $S['tasks_notify_on_comment']           ?? 'yes';
$emailComment         = $S['tasks_email_on_comment']            ?? 'no';
$notifyStatusChange   = $S['tasks_notify_on_status_change']     ?? 'yes';
$emailStatusChange    = $S['tasks_email_on_status_change']      ?? 'no';
$notifyDueSoon        = $S['tasks_notify_on_due_soon']          ?? 'yes';
$emailDueSoon         = $S['tasks_email_on_due_soon']           ?? 'no';
$notifyOverdue        = $S['tasks_notify_on_overdue']           ?? 'yes';
$emailOverdue         = $S['tasks_email_on_overdue']            ?? 'yes';
$notifyChecklistDone  = $S['tasks_notify_on_checklist_complete']?? 'yes';
$emailChecklistDone   = $S['tasks_email_on_checklist_complete'] ?? 'no';
$notifyTaskDone       = $S['tasks_notify_on_task_completed']    ?? 'yes';
$emailTaskDone        = $S['tasks_email_on_task_completed']     ?? 'yes';
$notifyAddFollower    = $S['tasks_notify_user_on_added_follower'] ?? 'yes';
$emailAddFollower     = $S['tasks_email_user_on_added_follower']  ?? 'no';
$notifyRemFollower    = $S['tasks_notify_user_on_remove_follower']?? 'yes';
$emailRemFollower     = $S['tasks_email_user_on_remove_follower'] ?? 'no';


// Tiny helper to append a tooltip “?” to labels
if (!function_exists('tasks_tt')) {
  function tasks_tt(string $label, string $tip): string {
    return $label .
      ' <i class="ti ti-question-circle text-primary align-middle" data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($tip) . '"></i>';
  }
}
?>

  <!-- Core Task Rules -->
  <div class="settings-section mb-4">
    <div class="row app-form">
      <div class="col-md-3 mb-3">
        <label class="form-label"><?= tasks_tt('Default View', 'Initial view for the Tasks module.') ?></label>
        <select name="settings[tasks_default_view]" class="form-select">
          <option value="list"    <?= $defaultView === 'list'    ? 'selected' : '' ?>>List</option>
          <option value="kanban"  <?= $defaultView === 'kanban'  ? 'selected' : '' ?>>Kanban</option>
          <option value="gantt"   <?= $defaultView === 'gantt'   ? 'selected' : '' ?>>Gantt</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Comments Order</label>
        <select name="settings[tasks_comments_order]" class="form-select">
          <option value="ascending"  <?= $commentsOrder === 'ascending'  ? 'selected' : '' ?>>Ascending (Oldest First)</option>
          <option value="descending" <?= $commentsOrder === 'descending' ? 'selected' : '' ?>>Descending (Newest First)</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label"><?= tasks_tt('Enforce Checklist', 'Task cannot be completed until all checklist items are resolved.') ?></label>
        <select name="settings[tasks_enforce_checklist_before_done]" class="form-select">
          <option value="yes" <?= $enforceChecklistDone === 'yes' ? 'selected' : '' ?>>Yes</option>
          <option value="no"  <?= $enforceChecklistDone === 'no'  ? 'selected' : '' ?>>No</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Default Priority</label>
        <select name="settings[tasks_default_priority]" class="form-select">
          <option value="low"     <?= $defaultPriority === 'low'     ? 'selected' : '' ?>>Low</option>
          <option value="normal"  <?= $defaultPriority === 'normal'  ? 'selected' : '' ?>>Normal</option>
          <option value="high"    <?= $defaultPriority === 'high'    ? 'selected' : '' ?>>High</option>
          <option value="urgent"  <?= $defaultPriority === 'urgent'  ? 'selected' : '' ?>>Urgent</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label"><?= tasks_tt('Due Soon Threshold', 'Trigger “due soon” notifications this many days before the due date.') ?></label>
        <input type="number" min="0" step="1"
               name="settings[tasks_due_soon_days]"
               class="form-control"
               value="<?= e($dueSoonDays) ?>"
               placeholder="e.g., 2">
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label"><?= tasks_tt('Escalate Overdue', 'Trigger escalation notifications when a task becomes overdue.') ?></label>
        <select name="settings[tasks_overdue_escalate]" class="form-select">
          <option value="yes" <?= $overdueEscalate === 'yes' ? 'selected' : '' ?>>Yes</option>
          <option value="no"  <?= $overdueEscalate === 'no'  ? 'selected' : '' ?>>No</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label"><?= tasks_tt('Allow Recurring Tasks', 'Enables recurrence options (daily/weekly/monthly) while creating or editing the task.') ?></label>
        <select name="settings[tasks_allow_recurring]" class="form-select">
          <option value="yes" <?= $allowRecurring === 'yes' ? 'selected' : '' ?>>Yes</option>
          <option value="no"  <?= $allowRecurring === 'no'  ? 'selected' : '' ?>>No</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Attachments per Task</label>
        <input type="number" min="0" step="1"
               name="settings[tasks_max_attachments]"
               class="form-control"
               value="<?= e($maxAttachments) ?>"
               placeholder="e.g., 10 (0 = no uploads)">
      </div>
      
    </div>
  </div>




  <!-- Notifications -->
  <div class="settings-section mb-2 app-form">
    <h6 class="section-title">
      <?= tasks_tt('Task Notifications', 'Configure in-app and email notifications for key events.') ?>
    </h6>
    <div class="table-responsive">
      <table class="table table-sm table-bottom-border small align-middle">
        <thead class="bg-light-primary">
          <tr>
            <th style="width:40%">Event / Action</th>
            <th style="width:20%"><?= tasks_tt('Scope', 'Who gets notified') ?></th>
            <th style="width:20%"><?= tasks_tt('In-App Notification', 'Bell icon notifications') ?></th>
            <th style="width:20%"><?= tasks_tt('Email', 'Send system emails on the event') ?></th>
          </tr>
        </thead>
        <tbody class="app-form">
          <tr>
            <td>On Task Creation</td>
            <td>Assignee</td>
            <td>
              <select name="settings[tasks_notify_assignee_on_create]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyAssigneeCreate === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyAssigneeCreate === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_assignee_on_create]" class="form-select form-select-sm">
                <option value="yes" <?= $emailAssigneeCreate === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailAssigneeCreate === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>On Task Creation</td>
            <td>Followers</td>
            <td>
              <select name="settings[tasks_notify_followers_on_create]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyFollowersCreate === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyFollowersCreate === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_followers_on_create]" class="form-select form-select-sm">
                <option value="yes" <?= $emailFollowersCreate === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailFollowersCreate === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>On Assignment</td>
            <td>Assignee</td>
            <td>
              <select name="settings[tasks_notify_on_assignment]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyAssign === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyAssign === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_on_assignment]" class="form-select form-select-sm">
                <option value="yes" <?= $emailAssign === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailAssign === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>On Comment</td>
            <td>Assignee & Followers</td>
            <td>
              <select name="settings[tasks_notify_on_comment]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyComment === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyComment === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_on_comment]" class="form-select form-select-sm">
                <option value="yes" <?= $emailComment === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailComment === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>On Status Change</td>
            <td>Assignee & Followers</td>
            <td>
              <select name="settings[tasks_notify_on_status_change]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyStatusChange === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyStatusChange === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_on_status_change]" class="form-select form-select-sm">
                <option value="yes" <?= $emailStatusChange === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailStatusChange === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>Due Soon</td>
            <td>Assignee</td>
            <td>
              <select name="settings[tasks_notify_on_due_soon]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyDueSoon === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyDueSoon === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_on_due_soon]" class="form-select form-select-sm">
                <option value="yes" <?= $emailDueSoon === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailDueSoon === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>Overdue</td>
            <td>Assignee & Escalation</td>
            <td>
              <select name="settings[tasks_notify_on_overdue]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyOverdue === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyOverdue === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_on_overdue]" class="form-select form-select-sm">
                <option value="yes" <?= $emailOverdue === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailOverdue === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>Checklist Completed</td>
            <td>Assignee & Followers</td>
            <td>
              <select name="settings[tasks_notify_on_checklist_complete]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyChecklistDone === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyChecklistDone === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_on_checklist_complete]" class="form-select form-select-sm">
                <option value="yes" <?= $emailChecklistDone === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailChecklistDone === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>Task Completed</td>
            <td>Assignee & Followers</td>
            <td>
              <select name="settings[tasks_notify_on_task_completed]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyTaskDone === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyTaskDone === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_on_task_completed]" class="form-select form-select-sm">
                <option value="yes" <?= $emailTaskDone === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailTaskDone === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>On Adding Follower</td>
            <td>Related User</td>
            <td>
              <select name="settings[tasks_notify_user_on_added_follower]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyAddFollower === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyAddFollower === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_user_on_added_follower]" class="form-select form-select-sm">
                <option value="yes" <?= $emailAddFollower === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailAddFollower === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>

          <tr>
            <td>On Removing Follower</td>
            <td>Related User</td>
            <td>
              <select name="settings[tasks_notify_user_on_remove_follower]" class="form-select form-select-sm">
                <option value="yes" <?= $notifyRemFollower === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $notifyRemFollower === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[tasks_email_user_on_remove_follower]" class="form-select form-select-sm">
                <option value="yes" <?= $emailRemFollower === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $emailRemFollower === 'no'  ? 'selected' : ''  ?>>Disabled</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <hr class="mt-3">
</div>

<script>
  // Bootstrap tooltip init
  document.addEventListener('DOMContentLoaded', function () {
    var ttEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    ttEls.forEach(function (el) { new bootstrap.Tooltip(el); });
  });
</script>
