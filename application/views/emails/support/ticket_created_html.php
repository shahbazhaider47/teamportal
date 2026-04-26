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
              
              <!-- Main message -->
              <p style="margin: 0 0 25px 0;">Your support ticket has been successfully created and is now in our system. We'll notify you when there are updates.</p>
              
              <!-- Ticket details card -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 25px 0; border-left: 4px solid #667eea;">
                <tr>
                  <td>
                    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 18px;">Ticket Details</h3>
                    
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="30%" style="padding: 5px 0; color: #666; font-weight: bold;">Subject:</td>
                        <td style="padding: 5px 0; color: #222;"><?= html_escape($ticket_subject) ?></td>
                      </tr>
                      <?php if (!empty($ticket_code)): ?>
                      <tr>
                        <td style="padding: 5px 0; color: #666; font-weight: bold;">Ticket ID:</td>
                        <td style="padding: 5px 0; color: #222;"><?= html_escape($ticket_code) ?></td>
                      </tr>
                      <?php endif; ?>
                      <tr>
                        <td style="padding: 5px 0; color: #666; font-weight: bold;">Status:</td>
                        <td style="padding: 5px 0;">
                          <span style="background-color: #e7f3ff; color: #0066cc; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">Open</span>
                        </td>
                      </tr>
                    </table>
                    
                    <!-- Action button -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0 0 0;">
                      <tr>
                        <td align="center">
                          <a href="<?= html_escape($ticket_url) ?>" style="background-color: #667eea; color: white; text-decoration: none; padding: 12px 30px; border-radius: 4px; display: inline-block; font-weight: bold; font-size: 14px;">
                            View Your Ticket
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Additional information -->
              <p style="margin: 25px 0 0 0; color: #666; font-size: 13px;">
                You can check the status of your ticket at any time using the link above. 
                Please allow some time for our team to review and respond to your request.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #f8f9fa; padding: 30px 40px; text-align: center; border-top: 1px solid #e9ecef;">
              <!-- Support info -->
              <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
                <strong><?= html_escape($brand) ?> Support Team</strong><br>
                We're here to help you
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
