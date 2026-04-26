<?php
$stats = $stats ?? [];
$totalApplied = (int)($stats['total_applied'] ?? 0);
$approved     = (int)($stats['approved'] ?? 0);
$rejected     = (int)($stats['rejected'] ?? 0);
$pending      = (int)($stats['pending'] ?? 0);
$cancelled    = (int)($stats['cancelled'] ?? 0);
?>


<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Total Applied</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-primary f-w-600"><?= $totalApplied ?></h4>
        </div>
      </div>
      <div class="project-card-icon bg-light-primary h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-list-check f-s-16 mb-1 text-primary"></i>
      </div>
    </div>
  </div>
</div>

<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Approved</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-success f-w-600"><?= $approved ?></h4>
        </div>
      </div>
      <div class="project-card-icon bg-light-success h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-circle-check f-s-16 mb-1 text-success"></i>
      </div>
    </div>
  </div>
</div>

<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Rejected</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-danger f-w-600"><?= $rejected ?></h4>
        </div>
      </div>
      <div class="project-card-icon bg-light-danger h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-circle-x f-s-16 mb-1 text-danger"></i>
      </div>
    </div>
  </div>
</div>

<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Pending</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-warning f-w-600"><?= $pending ?></h4>
        </div>
      </div>
      <div class="project-card-icon bg-light-warning h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-alert-circle f-s-16 mb-1 text-warning"></i>
      </div>
    </div>
  </div>
</div>

<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Cancelled</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-secondary f-w-600"><?= $cancelled ?></h4>
        </div>
      </div>
      <div class="project-card-icon bg-light-secondary h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-ban f-s-16 mb-1 text-secondary"></i>
      </div>
    </div>
  </div>
</div>

<div class="col-md-3 col-lg-2">
  <div class="card project-cards">
    <div class="card-body d-flex justify-content-between">
      <div>
        <h6 class="small">Other</h6>
        <div class="d-flex align-items-center gap-2 mt-2">
          <h4 class="text-secondary f-w-600"><?= $cancelled ?></h4>
        </div>
      </div>
      <div class="project-card-icon bg-light-secondary h-35 w-35 d-flex-center b-r-100">
        <i class="ti ti-ban f-s-16 mb-1 text-secondary"></i>
      </div>
    </div>
  </div>
</div>