<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= html_escape($subject ?? 'Leave Rejected') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body style="margin:0;padding:0;background:#f6f8fb;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fb;padding:24px 0;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0"
             style="width:600px;max-width:100%;background:#ffffff;border-radius:8px;
                    overflow:hidden;border:1px solid #e9eef6;">

        <!-- Header -->
        <tr>
          <td style="padding:16px 20px;background:#dc2626;color:#ffffff;
                     font-family:Arial,Helvetica,sans-serif;">
            <a href="<?= html_escape($brand_url ?? base_url()) ?>"
               style="color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;">
              <?= html_escape($brand ?? 'HR System') ?>
            </a>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:24px 20px;font-family:Arial,Helvetica,sans-serif;color:#334155;">
            <p style="margin:0 0 16px 0;">
              Hi <?= html_escape($recipient_name ?? 'there') ?>,
            </p>

            <h2 style="margin:0 0 12px 0;font-size:18px;color:#7f1d1d;">
              Your leave request was not approved
            </h2>

            <p style="margin:0 0 16px 0;line-height:1.55;color:#374151;">
              After careful review, your leave request has been <strong>rejected</strong>.
            </p>

            <?php if (!empty($leave)): ?>
              <table cellpadding="0" cellspacing="0" width="100%"
                     style="margin:16px 0;border-collapse:collapse;font-size:14px;">
                <tr>
                  <td style="padding:6px 0;color:#475569;"><strong>Leave Type:</strong></td>
                  <td style="padding:6px 0;color:#111827;">
                    <?= html_escape($leave['leave_type'] ?? '-') ?>
                  </td>
                </tr>
                <tr>
                  <td style="padding:6px 0;color:#475569;"><strong>From:</strong></td>
                  <td style="padding:6px 0;color:#111827;">
                    <?= html_escape($leave['start_date'] ?? '-') ?>
                  </td>
                </tr>
                <tr>
                  <td style="padding:6px 0;color:#475569;"><strong>To:</strong></td>
                  <td style="padding:6px 0;color:#111827;">
                    <?= html_escape($leave['end_date'] ?? '-') ?>
                  </td>
                </tr>
              </table>
            <?php endif; ?>

            <?php if (!empty($cta_url)): ?>
              <p style="margin:20px 0 0 0;">
                <a href="<?= html_escape($cta_url) ?>"
                   style="display:inline-block;background:#dc2626;color:#ffffff;
                          text-decoration:none;padding:10px 16px;border-radius:6px;
                          font-weight:600;">
                  View Leave Request
                </a>
              </p>
            <?php endif; ?>

            <p style="margin:20px 0 0 0;font-size:12px;color:#6b7280;">
              This is an automated message from <?= html_escape($brand ?? 'HR System') ?>.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:14px 20px;background:#f3f6fb;color:#64748b;
                     font-size:12px;font-family:Arial,Helvetica,sans-serif;">
            © <?= date('Y') ?> <?= html_escape($brand ?? 'HR System') ?> —
            <a href="<?= html_escape($brand_url ?? base_url()) ?>"
               style="color:#64748b;text-decoration:underline;">
              Visit site
            </a>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
