<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= html_escape($subject ?? 'Reminder Alert') ?></title>
  <meta name="viewport" content="width=device-width">
  <style>
    /* Basic, safe inliners */
    .btn { display:inline-block;padding:10px 16px;text-decoration:none;border-radius:6px }
  </style>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#222;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04)">
          <tr>
            <td style="background:#3b82f6;color:#fff;padding:16px 20px;">
              <h2 style="margin:0;font-size:18px;line-height:1.3;">
                <?= html_escape($brand_name ?? 'Reminder System') ?>
              </h2>
            </td>
          </tr>

          <tr>
            <td style="padding:20px;">
              <h3 style="margin:0 0 6px;font-size:18px;"><?= html_escape($title ?? 'Upcoming Reminder') ?></h3>
              <p style="margin:0 0 14px;color:#444;">
                This is a friendly heads-up that you have a reminder due in about <strong>30 minutes</strong>.
              </p>

              <?php if (!empty($description)): ?>
                <div style="margin:10px 0 14px;padding:12px;border:1px solid #eef2ff;border-radius:8px;background:#f9fbff;color:#333;">
                  <?= nl2br(html_escape($description)) ?>
                </div>
              <?php endif; ?>

              <table role="presentation" cellpadding="0" cellspacing="0" style="margin:10px 0 16px;">
                <tr>
                  <td style="padding:4px 8px;color:#666;">When:</td>
                  <td style="padding:4px 8px;"><strong><?= html_escape($when_human ?? '') ?></strong></td>
                </tr>
                <?php if (!empty($priority)): ?>
                <tr>
                  <td style="padding:4px 8px;color:#666;">Priority:</td>
                  <td style="padding:4px 8px;"><strong style="text-transform:capitalize;"><?= html_escape($priority) ?></strong></td>
                </tr>
                <?php endif; ?>
              </table>

              <p style="margin:18px 0;">
                <a class="btn" href="<?= html_escape($cta_url ?? '#') ?>"
                   style="background:#3b82f6;color:#fff;" target="_blank" rel="noopener">
                  Open Reminders
                </a>
              </p>

              <p style="margin:12px 0 0;color:#666;font-size:12px;">
                If the button doesn’t work, copy & paste this URL into your browser:<br>
                <span style="word-break:break-all;color:#3949ab;"><?= html_escape($cta_url ?? '') ?></span>
              </p>
            </td>
          </tr>

          <tr>
            <td style="background:#fafafa;border-top:1px solid #eee;padding:14px 20px;color:#888;font-size:12px;">
              Sent by <?= html_escape($brand_name ?? 'Reminder System') ?> • <?= html_escape($brand_url ?? '') ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
