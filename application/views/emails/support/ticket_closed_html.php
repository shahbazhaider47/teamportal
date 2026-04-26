<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #222222; line-height: 1.4; margin: 0; padding: 0; background-color: #f6f6f6;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f6f6f6;">
    <tr>
      <td align="center" style="padding: 20px 0;">
        <!-- Main container -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
          
          <!-- Header with logo -->
          <tr>
            <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 40px; text-align: center;">
              <table role="presentation" width="100%">
                <tr>
                  <td align="center">
                    <!-- Logo placeholder - replace with actual logo -->
                    <div style="background-color: rgba(255,255,255,0.2); border-radius: 8px; padding: 12px 20px; display: inline-block;">
                      <h1 style="color: white; font-size: 24px; margin: 0; font-weight: bold;"><?= html_escape($brand) ?></h1>
                    </div>
                    <!-- Alternative: If you have an actual logo image -->
                    <!-- <img src="<?= base_url('path/to/logo.png') ?>" alt="<?= html_escape($brand) ?>" style="max-height: 50px;"> -->
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Content area -->
          <tr>
            <td style="padding: 40px;">
              <!-- Greeting -->
              <p style="margin: 0 0 20px 0;">Hi <?= html_escape($recipient_name) ?>,</p>
              
              <!-- Status alert -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 6px; padding: 15px; margin: 20px 0; border: 1px solid #e9ecef;">
                <tr>
                  <td align="center">
                    <div style="background-color: #28a745; color: white; padding: 8px 20px; border-radius: 20px; display: inline-block; font-weight: bold; font-size: 14px;">
                      <i style="margin-right: 8px;">✓</i> Ticket Closed
                    </div>
                    <p style="margin: 15px 0 0 0; color: #666; font-size: 14px;">
                      Your support request has been resolved and closed.
                    </p>
                  </td>
                </tr>
              </table>
              
              <!-- Ticket details card -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 25px 0; border-left: 4px solid #28a745;">
                <tr>
                  <td>
                    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 18px;">Ticket Summary</h3>
                    
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="30%" style="padding: 8px 0; color: #666; font-weight: bold; vertical-align: top;">Subject:</td>
                        <td style="padding: 8px 0; color: #222;"><?= html_escape($ticket_subject) ?></td>
                      </tr>
                      <?php if (!empty($ticket_code)): ?>
                      <tr>
                        <td style="padding: 8px 0; color: #666; font-weight: bold; vertical-align: top;">Ticket ID:</td>
                        <td style="padding: 8px 0; color: #222;"><?= html_escape($ticket_code) ?></td>
                      </tr>
                      <?php endif; ?>
                      <tr>
                        <td style="padding: 8px 0; color: #666; font-weight: bold; vertical-align: top;">Status:</td>
                        <td style="padding: 8px 0;">
                          <span style="background-color: #d4edda; color: #155724; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">Closed</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding: 8px 0; color: #666; font-weight: bold; vertical-align: top;">Closed:</td>
                        <td style="padding: 8px 0; color: #222;"><?= date('F j, Y \a\t g:i A') ?></td>
                      </tr>
                    </table>
                    
                    <!-- Action button -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 25px 0 0 0;">
                      <tr>
                        <td align="center">
                          <a href="<?= html_escape($ticket_url) ?>" style="background-color: #6c757d; color: white; text-decoration: none; padding: 12px 30px; border-radius: 4px; display: inline-block; font-weight: bold; font-size: 14px;">
                            Review Ticket
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Important note -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 16px; margin: 20px 0;">
                <tr>
                  <td>
                    <p style="margin: 0; color: #856404; font-size: 13px; text-align: center;">
                      <strong>Note:</strong> If you believe this was closed in error, you can reply directly to this email or 
                      click the button above to reopen the ticket.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Feedback request -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #e7f3ff; border-radius: 6px; padding: 20px; margin: 25px 0 0 0;">
                <tr>
                  <td align="center">
                    <h4 style="color: #0066cc; margin: 0 0 10px 0; font-size: 16px;">How was your support experience?</h4>
                    <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
                      Help us improve by sharing your feedback
                    </p>
                    <a href="#" style="background-color: #0066cc; color: white; text-decoration: none; padding: 10px 25px; border-radius: 4px; display: inline-block; font-size: 13px; font-weight: bold;">
                      Provide Feedback
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #f8f9fa; padding: 30px 40px; text-align: center; border-top: 1px solid #e9ecef;">
              <!-- Support info -->
              <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
                <strong><?= html_escape($brand) ?> Support Team</strong><br>
                Thank you for choosing us
              </p>
              
              <!-- Contact links -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="padding: 10px 0;">
                    <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px; font-size: 13px;">Help Center</a>
                    <span style="color: #ddd;">•</span>
                    <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px; font-size: 13px;">Contact Us</a>
                    <span style="color: #ddd;">•</span>
                    <a href="#" style="color: #667eea; text-decoration: none; margin: 0 10px; font-size: 13px;">Status</a>
                  </td>
                </tr>
              </table>
              
              <!-- Copyright -->
              <p style="margin: 15px 0 0 0; color: #999; font-size: 12px;">
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