<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="card-body">
  <div class="mb-4">
    <p class="text-muted">Configure ticketing rules, restrictions, file policies, and notification behavior.</p>
  </div>

<?php
// Normalize incoming settings array to $S (mirrors payroll example).
$S = [];
if (isset($existing) && is_array($existing)) {
  $S = $existing;
} elseif (isset($existing_data) && is_array($existing_data)) {
  $S = $existing_data;
}

// Read current values (with safe defaults)
$autoCloseDays              = $S['support_auto_close_days']                ?? '';
$publicUrl                  = $S['support_ticket_public_url']              ?? 'no';
$openAllDepts               = $S['support_staff_can_open_all_departments'] ?? 'yes';     // yes|no
$autoAssignFirst            = $S['support_auto_assign_first_replier']      ?? 'yes';     // used in code (yes|no)
$repliesOrder               = $S['support_replies_order']                  ?? 'descending'; // used in code (ascending|descending)
$defaultOnReply             = $S['support_default_status_on_reply']        ?? 'in_progress'; // used in code
$maxAttachments             = $S['support_max_attachments']                ?? '5';
$deptNotifCreate            = $S['support_notify_dept_on_create']          ?? 'yes';
$deptEmailCreate            = $S['support_email_dept_on_create']           ?? 'yes';
$userNotifCreate            = $S['support_notify_user_on_create']          ?? 'yes';
$userEmailCreate            = $S['support_email_user_on_create']           ?? 'yes';
$userNotifReply             = $S['support_notify_user_on_reply']           ?? 'yes';
$userEmailReply             = $S['support_email_user_on_reply']            ?? 'yes';
$userNotifStatus            = $S['support_notify_user_on_status_change']   ?? 'yes';
$userEmailStatus            = $S['support_email_user_on_status_change']    ?? 'yes';
$userNotifAssigned          = $S['support_notify_user_on_assigned']        ?? 'yes';
$userEmailAssigned          = $S['support_email_user_on_assigned']         ?? 'yes';
$userNotifAddWatcher        = $S['support_notify_user_on_added_watcher']   ?? 'yes';
$userEmailAddWatcher        = $S['support_email_user_on_added_watcher']    ?? 'yes';
$userAddWatchers            = $S['support_user_added_watchers']            ?? 'both';
$userNotifRemWatcher        = $S['support_notify_user_on_remove_watcher']  ?? 'yes';
$userEmailRemWatcher        = $S['support_email_user_on_remove_watcher']   ?? 'yes';

// Tiny helper to append a tooltip “?” pill to labels
if (!function_exists('support_tt')) {
  function support_tt(string $label, string $tip): string {
    return $label .
      ' <i class="ti ti-question-circle text-primary align-middle" data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($tip) . '"></i>';
  }
}
?>

  <!-- Core Ticketing Rules -->
  <div class="settings-section mb-4">
    <div class="row app-form">
      <div class="col-md-4 mb-3">
        <label class="form-label">
        Auto-Close After (Days)
        </label>
        <input type="number" min="0" step="1"
               name="settings[support_auto_close_days]"
               class="form-control"
               value="<?= e($autoCloseDays) ?>"
               placeholder="e.g., 5 (0 = disabled)">
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">
          <?= support_tt('Staff Can Open Tickets to All Departments', 'Controls whether staff may create tickets for any department or only their own.') ?>
        </label>
        <select name="settings[support_staff_can_open_all_departments]" class="form-select">
          <option value="yes" <?= $openAllDepts === 'yes' ? 'selected' : '' ?>>Yes (Recommended)</option>
          <option value="no"  <?= $openAllDepts === 'no'  ? 'selected' : '' ?>>No</option>
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">
          <?= support_tt('Auto-Assign First Staff Replier', 'When enabled, the first staff reply automatically assigns the ticket to that staff member.') ?>
        </label>
        <select name="settings[support_auto_assign_first_replier]" class="form-select">
          <option value="yes" <?= $autoAssignFirst === 'yes' ? 'selected' : '' ?>>Yes</option>
          <option value="no"  <?= $autoAssignFirst === 'no'  ? 'selected' : '' ?>>No</option>
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">
        Ticket Replies Order
        </label>
        <select name="settings[support_replies_order]" class="form-select">
          <option value="ascending"  <?= $repliesOrder === 'ascending'  ? 'selected' : '' ?>>Ascending (Oldest First)</option>
          <option value="descending" <?= $repliesOrder === 'descending' ? 'selected' : '' ?>>Descending (Newest First)</option>
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">
        Default Status on Reply
        </label>
        <select name="settings[support_default_status_on_reply]" class="form-select">
          <option value="open"         <?= $defaultOnReply === 'open'         ? 'selected' : '' ?>>Open</option>
          <option value="in_progress"  <?= $defaultOnReply === 'in_progress'  ? 'selected' : '' ?>>In Progress</option>
          <option value="hold"         <?= $defaultOnReply === 'hold'         ? 'selected' : '' ?>>On Hold</option>
          <option value="answered"     <?= $defaultOnReply === 'answered'     ? 'selected' : '' ?>>Answered</option>
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">
        <?= support_tt('Ticket Watchers Can be Added By', 'Watchers can only see the ticket progress with limited access and can not reply or modify the ticket.') ?>
        </label>
        <select name="settings[support_user_added_watchers]" class="form-select">
          <option value="requester"         <?= $userAddWatchers === 'requester'         ? 'selected' : '' ?>>Requester</option>
          <option value="assignee"          <?= $userAddWatchers === 'assignee'          ? 'selected' : '' ?>>Assignee</option>
          <option value="both"              <?= $userAddWatchers === 'both'              ? 'selected' : '' ?>>Both (Requester & Assignee)</option>          
        </select>
      </div>


      <div class="col-md-4 mb-3">
        <label class="form-label">
        Attachments per Ticket 
        </label>
        <input type="number" min="0" step="1"
               name="settings[support_max_attachments]"
               class="form-control"
               value="<?= e($maxAttachments) ?>"
               placeholder="e.g., 5 (0 = no uploads)">
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">
        <?= support_tt('Ticket Public URL', 'Enable this to add public URL (Only assignee can generate the public URL from ticket view)') ?>
        </label>
        <select name="settings[support_ticket_public_url]" class="form-select">
        <option value="yes" <?= $publicUrl === 'yes' ? 'selected' : '' ?>>Enabled</option>
        <option value="no"  <?= $publicUrl === 'no'  ? 'selected' : '' ?>>Disabled</option>
        </select>
      </div>
      
    </div>
  </div>

  <!-- Notification Policy: User -->
  <div class="settings-section mb-2 app-form">
    <h6 class="section-title">
      <?= support_tt('Ticket Notifications', 'Configure how the ticket notifications should work on key events.') ?>
    </h6>
    <div class="table-responsive">
      <table class="table table-sm table-bottom-border small align-middle">
        <thead class="bg-light-primary">
          <tr>
            <th style="width:40%">Event / Action</th>
            <th style="width:20%"><?= support_tt('Scope', 'Notification scope for auto alerts and emails') ?></th>
            <th style="width:20%"><?= support_tt('In-App Notification', 'Send In-App notifications to the related user') ?></th>
            <th style="width:20%"><?= support_tt('Email', 'Sends system configured auto emails to the related user.') ?></th>
          </tr>
        </thead>
        <tbody class="app-form">
          <tr>
            <td>On Ticket Creation</td>
            <td>Department Head / HOD</td>
            <td>
              <select name="settings[support_notify_dept_on_create]" class="form-select form-control-sm">
                <option value="yes" <?= $deptNotifCreate === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $deptNotifCreate === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[support_email_dept_on_create]" class="form-select form-control-sm">
                <option value="yes" <?= $deptEmailCreate === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $deptEmailCreate === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
          </tr>
          <tr>
            <td>On Ticket Creation</td>
            <td>To Requester</td>
            <td>
              <select name="settings[support_notify_user_on_create]" class="form-select form-control-sm">
                <option value="yes" <?= $userNotifCreate === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userNotifCreate === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[support_email_user_on_create]" class="form-select form-control-sm">
                <option value="yes" <?= $userEmailCreate === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userEmailCreate === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
          </tr>
          <tr>
            <td>On Ticket Reply</td>
            <td>To Requester</td>
            <td>
              <select name="settings[support_notify_user_on_reply]" class="form-select form-control-sm">
                <option value="yes" <?= $userNotifReply === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userNotifReply === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[support_email_user_on_reply]" class="form-select form-control-sm">
                <option value="yes" <?= $userEmailReply === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userEmailReply === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
          </tr>
          <tr>
            <td>On Status Change</td>
            <td>To Requester</td>
            <td>
              <select name="settings[support_notify_user_on_status_change]" class="form-select form-control-sm">
                <option value="yes" <?= $userNotifStatus === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userNotifStatus === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[support_email_user_on_status_change]" class="form-select form-control-sm">
                <option value="yes" <?= $userEmailStatus === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userEmailStatus === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
          </tr>
          <tr>
            <td>On Assignment</td>
            <td>To Assignee</td>
            <td>
              <select name="settings[support_notify_user_on_assigned]" class="form-select form-control-sm">
                <option value="yes" <?= $userNotifAssigned === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userNotifAssigned === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[support_email_user_on_assigned]" class="form-select form-control-sm">
                <option value="yes" <?= $userEmailAssigned === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userEmailAssigned === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
          </tr>
          <tr>
            <td>On Adding Watcher</td>
            <td>To Related User</td>
            <td>
              <select name="settings[support_notify_user_on_added_watcher]" class="form-select form-control-sm">
                <option value="yes" <?= $userNotifAddWatcher === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userNotifAddWatcher === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[support_email_user_on_added_watcher]" class="form-select form-control-sm">
                <option value="yes" <?= $userEmailAddWatcher === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userEmailAddWatcher === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
          </tr> 
          <tr>
            <td>On Removing Watcher</td>
            <td>To Related User</td>
            <td>
              <select name="settings[support_notify_user_on_remove_watcher]" class="form-select form-control-sm">
                <option value="yes" <?= $userNotifRemWatcher === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userNotifRemWatcher === 'no'  ? 'selected' : '' ?>>Disabled</option>
              </select>
            </td>
            <td>
              <select name="settings[support_email_user_on_remove_watcher]" class="form-select form-control-sm">
                <option value="yes" <?= $userEmailRemWatcher === 'yes' ? 'selected' : '' ?>>Enabled</option>
                <option value="no"  <?= $userEmailRemWatcher === 'no'  ? 'selected' : '' ?>>Disabled</option>
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
