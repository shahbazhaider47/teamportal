<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Prefer passing $company_name and $logo_url from the controller:
 * $html = $this->load->view('emails/password_reset', [
 *   'reset_link'   => $reset_link,
 *   'company_name' => get_system_setting('company_name') ?: 'RCM Centric',
 *   'logo_url'     => base_url('uploads/company/'.($company['light_logo'] ?? '')),
 * ], TRUE);
 *
 * This view still works without them.
 */

$company_name = isset($company_name) && $company_name !== '' ? $company_name : 'RCM Centric';
$logo_url     = isset($logo_url) && $logo_url !== '' ? $logo_url : '';
$now_local    = date('M j, Y H:i');
$ip           = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$safe_link    = htmlspecialchars($reset_link ?? '#', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Password Reset</title>
  <style>
    /* Client-safe resets */
    body, table, td, a { font-family: Arial, Helvetica, sans-serif; }
    img { border: 0; outline: none; text-decoration: none; }
    table { border-collapse: collapse !important; }
    body { margin: 0 !important; padding: 0 !important; background-color: #f4f6f8; color:#1a1a1a; }
    a { color: #0d6efd; text-decoration: none; }
    /* Button fallback if CSS stripped */
    .btn { background:#0d6efd; color:#ffffff !important; padding:12px 24px; border-radius:6px; display:inline-block; }
    /* Dark mode friendly text */
    @media (prefers-color-scheme: dark) {
      body { background-color:#0b0f14; color:#e7e7e7; }
      .card { background:#111820 !important; }
      .muted { color:#9aa4af !important; }
    }
  </style>
</head>
<body>
  <!-- Preheader (hidden preview in inbox) -->
  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    Reset your password for <?= htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?>. Link expires in 1 hour.
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center" style="padding:24px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">
          <tr>
            <td align="center" style="padding:16px;">
              <?php if ($logo_url): ?>
                <img src="<?= htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?> Logo" height="40">
              <?php else: ?>
                <div style="font-weight:700; font-size:18px;"><?= htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php endif; ?>
            </td>
          </tr>

          <tr>
            <td class="card" style="background:#ffffff; border-radius:10px; padding:28px 28px 24px; border:1px solid #e9ecef;">
              <h1 style="margin:0 0 12px; font-size:20px;">Reset your password</h1>
              <p style="margin:0 0 16px; line-height:1.6;">
                You (or someone using your email) requested a password reset for your
                <strong><?= htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></strong> account.
              </p>

              <p style="margin:0 0 20px; line-height:1.6;">
                Click the button below to create a new password. For security reasons, this link expires in <strong>1 hour</strong>.
              </p>

              <p style="margin:0 0 24px;" align="center">
                <a href="<?= $safe_link; ?>" class="btn">Reset Password</a>
              </p>

              <p class="muted" style="margin:0 0 12px; font-size:13px; color:#6c757d; line-height:1.6;">
                If the button doesn’t work, copy and paste this URL into your browser:
              </p>
              <p style="margin:0 0 20px; word-break:break-all;">
                <a href="<?= $safe_link; ?>"><?= $safe_link; ?></a>
              </p>

              <hr style="border:none; border-top:1px solid #e9ecef; margin:18px 0;">

              <p class="muted" style="margin:0 0 6px; font-size:12px; color:#6c757d;">
                Request time: <?= htmlspecialchars($now_local, ENT_QUOTES, 'UTF-8'); ?> &middot; IP: <?= htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?>
              </p>
              <p class="muted" style="margin:0; font-size:12px; color:#6c757d;">
                If you didn’t initiate this request, you can ignore this email. No changes were made.
              </p>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding:16px; font-size:12px; color:#6c757d;">
              &copy; <?= date('Y'); ?> <?= htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
