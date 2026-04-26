<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$brand           = $brand           ?? 'Our Company';
$user_fullname   = $user_fullname   ?? 'there';
$emp_id          = $emp_id          ?? '';
$contract_type   = $contract_type   ?? 'Employment Contract';
$job_title       = $job_title       ?? '';
$department_name = $department_name ?? '';
$team_name       = $team_name       ?? '';
$start_date      = $start_date      ?? '';
$end_date        = $end_date        ?? '';
$current_salary  = $current_salary  ?? '';
$contract_url    = $contract_url    ?? '#';
?>

<div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.5; color: #222;">
    <p>Dear <?= html_escape($user_fullname); ?>,</p>

    <p>
        We are pleased to inform you that your employment contract with
        <strong><?= html_escape($brand); ?></strong> has been <strong>renewed</strong>.
        Please review the updated details below.
    </p>

    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; margin: 10px 0; font-size: 13px;">
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">Contract Type</td>
            <td><?= html_escape($contract_type); ?></td>
        </tr>
        <?php if ($emp_id): ?>
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">Employee ID</td>
            <td><?= html_escape($emp_id); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">Employee</td>
            <td><?= html_escape($user_fullname); ?></td>
        </tr>
        <?php if ($job_title): ?>
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">Job Title</td>
            <td><?= html_escape($job_title); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($department_name): ?>
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">Department</td>
            <td><?= html_escape($department_name); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($team_name): ?>
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">Team</td>
            <td><?= html_escape($team_name); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($start_date): ?>
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">Start Date</td>
            <td><?= html_escape($start_date); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($end_date): ?>
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">End Date</td>
            <td><?= html_escape($end_date); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($current_salary): ?>
        <tr>
            <td style="font-weight: bold; padding-right: 12px;">Current Salary</td>
            <td><?= html_escape($current_salary); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <p>
        You can view and download your renewed contract using the link below:
    </p>

    <p>
        <a href="<?= html_escape($contract_url); ?>"
           style="display: inline-block; padding: 8px 16px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: 600;">
            View Renewed Contract
        </a>
    </p>

    <p style="margin-top: 16px;">
        If you have any questions regarding the renewal, please contact HR.
    </p>

    <p>Best regards,<br>
       <?= html_escape($brand); ?></p>
</div>
