<?php defined('BASEPATH') or exit('No direct script access allowed');

/** @var string $recipient_name */
/** @var array  $contract */
/** @var string $sign_url */
/** @var string $brand */
/** @var string|null $file_url */
/** @var string|null $file_name */

$fileUrlSafe  = !empty($file_url)  ? htmlspecialchars((string)$file_url,  ENT_QUOTES, 'UTF-8') : null;
$fileNameSafe = !empty($file_name) ? htmlspecialchars((string)$file_name, ENT_QUOTES, 'UTF-8') : null;

$contractType = isset($contract['contract_type']) && $contract['contract_type'] !== ''
    ? $contract['contract_type']
    : 'Employment Contract';

$contractId = (int)($contract['id'] ?? 0);

// Employee display
$employeeName = isset($contract['fullname']) && $contract['fullname'] !== ''
    ? $contract['fullname']
    : trim(($contract['firstname'] ?? '') . ' ' . ($contract['lastname'] ?? ''));

// Employee ID (pretty)
$empIdRaw = $contract['emp_id'] ?? null;
if (function_exists('emp_id_display') && $empIdRaw) {
    $empIdDisplay = emp_id_display($empIdRaw);
} elseif (!empty($empIdRaw)) {
    $empIdDisplay = $empIdRaw;
} else {
    $empIdDisplay = null;
}

// Dates with format_date if available
$startRaw = $contract['start_date'] ?? null;
$endRaw   = $contract['end_date']   ?? null;

if (function_exists('format_date')) {
    $startDate = $startRaw ? format_date($startRaw) : '—';
    $endDate   = $endRaw   ? format_date($endRaw)   : '—';
} else {
    $startDate = $startRaw ?: '—';
    $endDate   = $endRaw   ?: '—';
}

// Salary with c_format if available
$currentSalaryRaw = $contract['current_salary'] ?? null;
if ($currentSalaryRaw !== null && $currentSalaryRaw !== '') {
    if (function_exists('c_format')) {
        $currentSalary = c_format((float)$currentSalaryRaw);
    } else {
        $currentSalary = (string)$currentSalaryRaw;
    }
} else {
    $currentSalary = '—';
}

// Department / Title
$department = $contract['department_name'] ?? ($contract['emp_department'] ?? null);

// At this point controller already resolved emp_title to a readable string
$jobTitleRaw = $contract['emp_title'] ?? null;
$jobTitle    = $jobTitleRaw ? $jobTitleRaw : null;


// Basic HTML escaping
$recipient_name = htmlspecialchars((string)$recipient_name, ENT_QUOTES, 'UTF-8');
$contractType   = htmlspecialchars($contractType, ENT_QUOTES, 'UTF-8');
$employeeName   = htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8');
$brand          = htmlspecialchars((string)$brand, ENT_QUOTES, 'UTF-8');
$sign_url_safe  = htmlspecialchars((string)$sign_url, ENT_QUOTES, 'UTF-8');
$department     = $department ? htmlspecialchars($department, ENT_QUOTES, 'UTF-8') : null;
$jobTitle       = $jobTitle   ? htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8')   : null;
$empIdDisplay   = $empIdDisplay ? htmlspecialchars($empIdDisplay, ENT_QUOTES, 'UTF-8') : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Contract for Signature</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;">
    <div style="max-width:600px;margin:0 auto;padding:24px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#222;">

        <div style="background:#ffffff;border-radius:6px;padding:24px;border:1px solid #e5e5e5;">
            <h2 style="margin-top:0;margin-bottom:16px;font-size:18px;font-weight:600;color:#111;">
                Contract ready for your signature
            </h2>

            <p style="margin:0 0 12px 0;">
                Hi <?= $recipient_name; ?>,
            </p>

            <p style="margin:0 0 16px 0;">
                Your employment contract has been generated and is now ready for your review and signature.
            </p>

            <table cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 16px 0;font-size:13px;">
                <tbody>
                    <?php if ($contractId): ?>
                        <tr>
                            <td style="padding:4px 0;color:#666;width:35%;">Contract #</td>
                            <td style="padding:4px 0;color:#111;">#<?= (int)$contractId; ?></td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td style="padding:4px 0;color:#666;width:35%;">Contract Type</td>
                        <td style="padding:4px 0;color:#111;"><?= $contractType; ?></td>
                    </tr>

                    <?php if ($employeeName !== ''): ?>
                        <tr>
                            <td style="padding:4px 0;color:#666;">Employee</td>
                            <td style="padding:4px 0;color:#111;">
                                <?= $employeeName; ?>
                                <?php if ($empIdDisplay): ?>
                                    <span style="color:#888;">(<?= $empIdDisplay; ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if ($jobTitle): ?>
                        <tr>
                            <td style="padding:4px 0;color:#666;">Job Title</td>
                            <td style="padding:4px 0;color:#111;"><?= $jobTitle; ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php if ($department): ?>
                        <tr>
                            <td style="padding:4px 0;color:#666;">Department</td>
                            <td style="padding:4px 0;color:#111;"><?= $department; ?></td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td style="padding:4px 0;color:#666;">Start Date</td>
                        <td style="padding:4px 0;color:#111;"><?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>

                    <tr>
                        <td style="padding:4px 0;color:#666;">End Date</td>
                        <td style="padding:4px 0;color:#111;"><?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>

                    <tr>
                        <td style="padding:4px 0;color:#666;">Current Salary</td>
                        <td style="padding:4px 0;color:#111;"><?= htmlspecialchars($currentSalary, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>

                <?php if ($fileUrlSafe): ?>
                    <tr>
                        <td style="padding:4px 0;color:#666;">Contract File</td>
                        <td style="padding:4px 0;color:#111;">
                            <a href="<?= $fileUrlSafe; ?>" style="color:#0066cc;text-decoration:none;">
                                <?= $fileNameSafe ?: 'Download contract' ?>
                            </a>
                            <span style="color:#888;font-size:11px;">(also attached to this email)</span>
                        </td>
                    </tr>
                <?php endif; ?>
                    
                </tbody>
            </table>

            <p style="margin:0 0 20px 0;">
                Please review the terms carefully and sign the contract using the link below:
            </p>

            <p style="margin:0 0 18px 0;text-align:center;">
                <a href="<?= $sign_url_safe; ?>"
                   style="display:inline-block;padding:10px 22px;background:#266dd3;color:#ffffff;text-decoration:none;border-radius:4px;font-size:14px;font-weight:600;">
                    Review &amp; Sign Contract
                </a>
            </p>

            <p style="margin:0 0 12px 0;font-size:12px;color:#666;">
                If the button above does not work, copy and paste this link into your browser:
            </p>
            <p style="margin:0 0 16px 0;font-size:12px;color:#0066cc;word-break:break-all;">
                <?= $sign_url_safe; ?>
            </p>

            <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">

            <p style="margin:0 0 4px 0;font-size:12px;color:#666;">
                Thank you,
            </p>
            <p style="margin:0;font-size:12px;color:#666;">
                <?= $brand; ?>
            </p>
        </div>
    </div>
</body>
</html>
