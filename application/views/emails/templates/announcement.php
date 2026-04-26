<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= html_escape($subject ?? ('New Announcement: ' . ($title ?? ''))) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f6f8fb;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fb;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e9eef6;">
          <tr>
            <td style="padding:16px 20px;background:#0d6efd;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
              <a href="<?= html_escape($brand_url ?? base_url()) ?>"
                 style="color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;">
                 <?= html_escape($brand ?? 'System') ?>
              </a>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 20px;font-family:Arial,Helvetica,sans-serif;color:#334155;">
              <p style="margin:0 0 16px 0;">Hi <?= html_escape($recipient_name ?? 'there') ?>, There is a new announcement in your company.</p>
              <h2 style="margin:0 0 12px 0;font-size:18px;color:#111827;">
                <?= html_escape($title ?? '') ?>
              </h2>

              <?php if (!empty($message)): ?>
                <div style="margin:0 0 16px 0;line-height:1.55;color:#374151;white-space:pre-wrap;">
                  <?= nl2br(html_escape($message)) ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($cta_url)): ?>
                <p style="margin:16px 0 0 0;">
                  <a href="<?= html_escape($cta_url) ?>" 
                     style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;
                            padding:10px 16px;border-radius:6px;font-weight:600;">
                    View in app
                  </a>
                </p>
              <?php endif; ?>

              <p style="margin:20px 0 0 0;font-size:12px;color:#6b7280;">
                This is an automated message from <?= html_escape($brand ?? 'System') ?>.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:14px 20px;background:#f3f6fb;color:#64748b;font-size:12px;font-family:Arial,Helvetica,sans-serif;">
              © <?= date('Y') ?> <?= html_escape($brand ?? 'System') ?> — 
              <a href="<?= html_escape($brand_url ?? base_url()) ?>" style="color:#64748b;text-decoration:underline;">Visit site</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
