<?php
$total          = isset($total) ? (int)$total : 0;
$total_approved = isset($total_approved) ? (int)$total_approved : 0;
$total_rejected = isset($total_rejected) ? (int)$total_rejected : 0;
?>

<div class="card card-widget shadow-sm mb-3 border-start border-4 border-primary">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 text-primary" aria-label="Signoff Submissions Widget Title">
                <i class="ti ti-file-check me-2"></i>Signoff Submissions
            </h5>
            <span class="badge bg-light text-dark" aria-label="Current Month"><?= date('M Y') ?></span>
        </div>
        
        <div class="row text-center">
            <div class="col-4">
                <div class="fs-3 fw-bold" aria-label="Total Submitted"><?= $total ?></div>
                <div class="small text-muted">Submitted</div>
            </div>
            <div class="col-4 border-start border-end">
                <div class="fs-3 fw-bold text-success" aria-label="Total Approved"><?= $total_approved ?></div>
                <div class="small text-success">Approved</div>
            </div>
            <div class="col-4">
                <div class="fs-3 fw-bold text-danger" aria-label="Total Rejected"><?= $total_rejected ?></div>
                <div class="small text-danger">Rejected</div>
            </div>
        </div>
    </div>
</div>