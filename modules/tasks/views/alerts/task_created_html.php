<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #222222; line-height: 1.4; margin: 0; padding: 0; background-color: #f6f6f6;">

<?php
    // Defensive defaults (so template doesn't explode if something is missing)
    $brand          = isset($brand)          ? $brand          : 'Tasks';
    $recipient_name = isset($recipient_name) ? $recipient_name : 'there';
    $task_title     = isset($task_title)     ? $task_title     : '';
    $task_id        = isset($task_id)        ? (int)$task_id   : 0;
    $task_code      = isset($task_code)      ? $task_code      : '';
    $task_status    = isset($task_status)    ? $task_status    : 'not_started';
    $task_priority  = isset($task_priority)  ? $task_priority  : 'normal';
    $task_due_date  = isset($task_due_date)  ? $task_due_date  : null;
    $task_url       = isset($task_url)       ? $task_url       : '#';
    $project_name   = isset($project_name)   ? $project_name   : '';
    $added_by_name  = isset($added_by_name)  ? $added_by_name  : '';

    // Human labels
    $status_label   = ucfirst(str_replace('_', ' ', strtolower($task_status)));
    $priority_label = ucfirst(strtolower($task_priority));

    // Simple status color logic
    $status_color_bg = '#e7f3ff';
    $status_color_fg = '#0066cc';

    switch (strtolower($task_status)) {
        case 'completed':
            $status_color_bg = '#dcfce7';
            $status_color_fg = '#15803d';
            break;
        case 'in_progress':
            $status_color_bg = '#e0f2fe';
            $status_color_fg = '#0369a1';
            break;
        case 'on_hold':
            $status_color_bg = '#fef9c3';
            $status_color_fg = '#92400e';
            break;
        case 'cancelled':
            $status_color_bg = '#fee2e2';
            $status_color_fg = '#b91c1c';
            break;
    }

    // Priority color logic
    $priority_bg = '#f3f4f6';
    $priority_fg = '#374151';
    switch (strtolower($task_priority)) {
        case 'high':
            $priority_bg = '#fee2e2';
            $priority_fg = '#b91c1c';
            break;
        case 'urgent':
            $priority_bg = '#fef3c7';
            $priority_fg = '#92400e';
            break;
        case 'low':
            $priority_bg = '#e0f2fe';
            $priority_fg = '#0369a1';
            break;
    }

    $task_ref = $task_code !== '' ? $task_code : ($task_id ? '#'.$task_id : '');
?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f6f6f6;">
    <tr>
      <td align="center" style="padding: 20px 0;">
        <!-- Main container -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">

          <!-- Header with logo / brand -->
          <tr>
            <td style="background: linear-gradient(135deg, #2563eb 0%, #4f46e5 50%, #7c3aed 100%); padding: 26px 40px; text-align: center;">
              <table role="presentation" width="100%">
                <tr>
                  <td align="center">
                    <div style="background-color: rgba(255,255,255,0.18); border-radius: 999px; padding: 10px 22px; display: inline-block;">
                      <h1 style="color: #ffffff; font-size: 22px; margin: 0; font-weight: 700; letter-spacing: .03em;">
                        <?= html_escape($brand) ?>
                      </h1>
                    </div>
                    <?php if (!empty($task_title)) : ?>
                      <p style="color:#e5e7eb; font-size:12px; margin:12px 0 0 0; letter-spacing:.08em; text-transform:uppercase;">
                        New Task Assigned
                      </p>
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Content area -->
          <tr>
            <td style="padding: 34px 40px 32px 40px;">
              <!-- Greeting -->
              <p style="margin: 0 0 14px 0;">Hi <?= html_escape($recipient_name) ?>,</p>

              <!-- Main message -->
              <p style="margin: 0 0 20px 0;">
                A new task has been created and assigned to you in
                <strong><?= html_escape($brand) ?></strong>.
              </p>

              <?php if ($added_by_name !== ''): ?>
                <p style="margin: 0 0 18px 0; color:#4b5563; font-size:13px;">
                  <strong><?= html_escape($added_by_name) ?></strong> just created this task for you.
                </p>
              <?php endif; ?>

              <!-- Task details card -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; padding: 18px 20px 20px 20px; margin: 18px 0 22px 0; border-left: 4px solid #2563eb;">
                <tr>
                  <td>
                    <h3 style="color: #111827; margin: 0 0 12px 0; font-size: 17px;">
                      Task Details
                    </h3>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;">
                      <?php if ($task_title !== ''): ?>
                      <tr>
                        <td width="30%" style="padding: 4px 0; color: #6b7280; font-weight: 600;">Title:</td>
                        <td style="padding: 4px 0; color: #111827;">
                          <?= html_escape($task_title) ?>
                        </td>
                      </tr>
                      <?php endif; ?>

                      <?php if ($task_ref !== ''): ?>
                      <tr>
                        <td style="padding: 4px 0; color: #6b7280; font-weight: 600;">Task ID:</td>
                        <td style="padding: 4px 0; color: #111827;">
                          <?= html_escape($task_ref) ?>
                        </td>
                      </tr>
                      <?php endif; ?>

                      <tr>
                        <td style="padding: 4px 0; color: #6b7280; font-weight: 600; vertical-align: top;">Status:</td>
                        <td style="padding: 4px 0;">
                          <span style="background-color: <?= $status_color_bg ?>; color: <?= $status_color_fg ?>; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; display: inline-block;">
                            <?= html_escape($status_label) ?>
                          </span>
                        </td>
                      </tr>

                      <tr>
                        <td style="padding: 4px 0; color: #6b7280; font-weight: 600; vertical-align: top;">Priority:</td>
                        <td style="padding: 4px 0;">
                          <span style="background-color: <?= $priority_bg ?>; color: <?= $priority_fg ?>; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing:.05em; display:inline-block;">
                            <?= html_escape($priority_label) ?>
                          </span>
                        </td>
                      </tr>

                      <?php if (!empty($task_due_date)): ?>
                      <tr>
                        <td style="padding: 4px 0; color: #6b7280; font-weight: 600;">Due Date:</td>
                        <td style="padding: 4px 0; color: #111827;">
                          <?= html_escape($task_due_date) ?>
                        </td>
                      </tr>
                      <?php endif; ?>

                      <?php if (!empty($project_name)): ?>
                      <tr>
                        <td style="padding: 4px 0; color: #6b7280; font-weight: 600;">Project:</td>
                        <td style="padding: 4px 0; color: #111827;">
                          <?= html_escape($project_name) ?>
                        </td>
                      </tr>
                      <?php endif; ?>
                    </table>

                    <!-- Action button -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 18px 0 0 0;">
                      <tr>
                        <td align="center">
                          <a href="<?= html_escape($task_url) ?>"
                             style="background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 11px 26px; border-radius: 4px; display: inline-block; font-weight: 600; font-size: 13px;">
                            View Task
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Additional info -->
              <p style="margin: 0 0 6px 0; color: #6b7280; font-size: 13px;">
                You can review the full details, update the status, and add comments directly from the task page.
              </p>
              <p style="margin: 0; color: #6b7280; font-size: 13px;">
                Please align this task with your current priorities and update progress as you work on it.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #f9fafb; padding: 26px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
              <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 12px;">
                <strong><?= html_escape($brand) ?> Tasks</strong><br>
                Staying on top of what matters most.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="padding: 8px 0;">
                    <a href="<?= isset($tasks_home_url) ? html_escape($tasks_home_url) : '#' ?>"
                       style="color: #2563eb; text-decoration: none; margin: 0 8px; font-size: 12px;">My Tasks</a>
                    <span style="color: #d1d5db;">•</span>
                    <a href="<?= isset($projects_url) ? html_escape($projects_url) : '#' ?>"
                       style="color: #2563eb; text-decoration: none; margin: 0 8px; font-size: 12px;">Projects</a>
                    <span style="color: #d1d5db;">•</span>
                    <a href="<?= isset($settings_url) ? html_escape($settings_url) : '#' ?>"
                       style="color: #2563eb; text-decoration: none; margin: 0 8px; font-size: 12px;">Notification Settings</a>
                  </td>
                </tr>
              </table>

              <p style="margin: 10px 0 0 0; color: #9ca3af; font-size: 11px;">
                &copy; <?= date('Y') ?> <?= html_escape($brand) ?>. All rights reserved.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
