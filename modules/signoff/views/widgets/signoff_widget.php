<?php
$CI = &get_instance();
$CI->load->model('signoff/Signoff_submissions_model');
$user_id = (int)$CI->session->userdata('user_id');

// Define current month's range
$current_month_start = date('Y-m-01');
$current_month_end   = date('Y-m-t');

// Get counts for current month
$total          = $CI->Signoff_submissions_model->count_user_submissions($user_id, null, $current_month_start, $current_month_end);
$total_approved = $CI->Signoff_submissions_model->count_user_submissions($user_id, 'approved', $current_month_start, $current_month_end);
$total_rejected = $CI->Signoff_submissions_model->count_user_submissions($user_id, 'rejected', $current_month_start, $current_month_end);
$total_pending  = $total - ($total_approved + $total_rejected);

// Percentages
$approved_percent = $total ? round(($total_approved / $total) * 100) : 0;
$rejected_percent = $total ? round(($total_rejected / $total) * 100) : 0;
$pending_percent  = $total ? round(($total_pending  / $total) * 100) : 0;
?>

<div class="card">
    <div class="card-body">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <div class="bg-light-primary bg-opacity-10 p-2 rounded-2 me-2">
                    <i class="ti ti-calendar text-primary fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-semibold text-dark small">Monthly Signoff</h6>
                    <small class="text-muted"><?= date('F Y') ?></small>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="row g-2">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between bg-light-primary px-3 p-2 mt-2 rounded-2">
                    <div>
                        <span class="d-block fs-5 fw-bold"> Total: <?= $total_approved ?></span>
                    </div>
                    <div class="text-end">
                        <span class="d-block small text-danger">Rejected: <?= $total_rejected ?> (<?= $rejected_percent ?>%)</span>
                        <span class="d-block small text-info">Pending: <?= $total_pending ?> (<?= $pending_percent ?>%)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
