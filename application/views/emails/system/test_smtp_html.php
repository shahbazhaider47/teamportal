<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>SMTP Test — <?= html_escape($brand ?? 'System') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#222;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:32px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="580" cellpadding="0" cellspacing="0"
               style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);max-width:580px;">

          <!-- Header -->
          <tr>
            <td style="background:#3b82f6;padding:20px 28px;">
              <p style="margin:0;font-size:18px;font-weight:700;color:#ffffff;line-height:1.3;">
                <?= html_escape($brand ?? 'System') ?>
              </p>
            </td>
          </tr>

          <!-- Icon + Title -->
          <tr>
            <td style="padding:32px 28px 8px;text-align:center;">
              <div style="display:inline-block;background:#eff6ff;border-radius:50%;width:56px;height:56px;line-height:56px;font-size:26px;margin-bottom:16px;">
                ✅
              </div>
              <h1 style="margin:0 0 8px;font-size:22px;color:#111827;">SMTP is Working</h1>
              <p style="margin:0;font-size:15px;color:#6b7280;">
                Your email configuration is set up correctly.
              </p>
            </td>
          </tr>

          <!-- Divider -->
          <tr>
            <td style="padding:20px 28px 0;">
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:0;">
            </td>
          </tr>

          <!-- Details -->
          <tr>
            <td style="padding:20px 28px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                     style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                <tr>
                  <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;width:38%;color:#6b7280;font-size:13px;">
                    Sent at
                  </td>
                  <td style="padding:10px 16px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#111827;">
                    <strong><?= html_escape($time ?? date('Y-m-d H:i:s')) ?></strong>
                  </td>
                </tr>
                <tr>
                  <td style="padding:10px 16px;color:#6b7280;font-size:13px;">
                    Sender
                  </td>
                  <td style="padding:10px 16px;font-size:13px;color:#111827;">
                    <strong><?= html_escape($brand ?? 'System') ?></strong>
                  </td>
                </tr>
              </table>

              <p style="margin:20px 0 0;font-size:13px;color:#9ca3af;line-height:1.6;">
                This is an automated test message sent from your application's SMTP configuration panel.
                No action is required. If you did not request this test, you can safely ignore this email.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:14px 28px;">
              <p style="margin:0;font-size:12px;color:#9ca3af;">
                Sent by <?= html_escape($brand ?? 'System') ?> &bull; SMTP Configuration Test
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>