<?php
$CI = &get_instance();
$CI->load->model('signoff/Signoff_submissions_model');
$user_id = (int)$CI->session->userdata('user_id');

// Use the correct method for claims_submitted
$summary = $CI->Signoff_submissions_model->get_claims_submitted_summary($user_id);

$current_total  = number_format($summary['current'], 0);
$previous_total = number_format($summary['previous'], 0);
$difference     = number_format(abs($summary['difference']), 0);
$is_positive    = $summary['difference'] >= 0;
$diff_class     = $is_positive ? 'text-success' : 'text-danger';
$diff_symbol    = $is_positive ? '+' : '-';
?>
<div class="card">
    <div class="card-body">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <div class="bg-light-primary bg-opacity-10 p-2 rounded-2 me-2">
                    <i class="ti ti-file-invoice text-primary fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-semibold text-dark small">Claims Submitted</h6>
                    <small class="text-muted"><?= date('F Y') ?></small>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="row g-2">
            <div class="col-12">
                <!-- Total Amount -->
                <div class="d-flex align-items-center justify-content-between bg-light-primary px-3 p-2 mt-1 rounded-2">
                    <div>
                        <span class="d-block fs-5 fw-bold"><?= $current_total ?> Claims</span>
                    </div>
                    <div class="text-end">
                        <span class="d-block small">vs last month</span>
                        <span class="d-block <?= $diff_class ?>"><?= $diff_symbol ?><?= $difference ?> claims</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
