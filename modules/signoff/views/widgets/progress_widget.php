<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI      = &get_instance();
$user_id = (int) $CI->session->userdata('user_id');

if (!$user_id) {
    return; // nothing to show for guests
}

// Read performance indicator from settings (same logic as Signoff)
$perf = 'none';
if (function_exists('get_option')) {
    $perf = strtolower(trim((string) get_option('signoff_perf_indicators')));
} else {
    // Fallback to system_settings if needed
    $row = $CI->db->get_where('system_settings', [
        'group_key' => 'signoff',
        'key'       => 'signoff_perf_indicators',
    ])->row_array();
    if (!empty($row['value'])) {
        $perf = strtolower(trim((string) $row['value']));
    }
}

if (!in_array($perf, ['points', 'targets'], true)) {
    // No indicators selected – show friendly info card instead of hiding the widget
    ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center">
                    <div class="bg-light-primary bg-opacity-10 p-2 rounded-2 me-2">
                        <i class="ti ti-target-arrow fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold text-dark small">
                            Performance Overview
                        </h6>
                        <small class="text-muted"><?= date('F Y') ?></small>
                    </div>
                </div>
            </div>
            <div class="bg-light-primary px-3 py-2 rounded-2 small d-flex align-items-center mt-4">
                <i class="ti ti-alert-circle text-warning me-2"></i>
                <span>
                    Please configure <strong>Points</strong> or <strong>Targets</strong> to see performance stats here.
                </span>
            </div>
        </div>
    </div>
    <?php
    return;
}

$CI->load->model('signoff/Signoff_submissions_model');
$summary    = $CI->Signoff_submissions_model->get_perf_summary($user_id, $perf);
$monthLabel = date('F Y');

if ($perf === 'points') {
    $title      = 'Performance Points';
    $iconClass  = 'ti ti-trophy';
    $current    = number_format((float) ($summary['current'] ?? 0), 0);
    $difference = (float) ($summary['difference'] ?? 0);
    $diffAbs    = number_format(abs($difference), 0);
    $is_positive = $difference >= 0;
    $diff_class  = $is_positive ? 'text-success' : 'text-danger';
    $diff_symbol = $is_positive ? '+' : '-';
} else { // targets
    $title         = 'Performance Targets';
    $iconClass     = 'ti ti-target-arrow';
    $assigned      = (float) ($summary['assigned']   ?? 0);
    $achieved      = (float) ($summary['achieved']   ?? 0);
    $completion    = (float) ($summary['completion'] ?? 0);
    $assignedFmt   = number_format($assigned, 0);
    $achievedFmt   = number_format($achieved, 0);
    $completionFmt = number_format($completion, 0);
}
?>

<div class="card">
    <div class="card-body">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <div class="bg-light-primary bg-opacity-10 p-2 rounded-2 me-2">
                    <i class="<?= $iconClass ?> text-primary fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-semibold text-dark small">
                        <?= html_escape($title) ?>
                    </h6>
                    <small class="text-muted"><?= html_escape($monthLabel) ?></small>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="row g-2">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between bg-light-primary px-3 p-2 mt-1 rounded-2">
                    <?php if ($perf === 'points'): ?>
                        <div>
                            <span class="d-block fs-5 fw-bold">
                                <?= $current ?> pts
                            </span>
                        </div>
                        <div class="text-end">
                            <span class="d-block small">vs last month</span>
                            <span class="d-block <?= $diff_class ?>">
                                <?= $diff_symbol . $diffAbs ?> pts
                            </span>
                        </div>

                    <?php else: ?>
                        <div>
                            <span class="d-block fs-5 fw-bold">
                                <?= $achievedFmt ?>
                            </span>
                            <small class="text-muted">Achieved</small>
                        </div>
                        <div class="text-end small">
                            <span class="d-block">
                                Assigned:
                                <strong><?= $assignedFmt ?></strong>
                            </span>
                            <span class="d-block">
                                Completion:
                                <strong><?= $completionFmt ?>%</strong>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>