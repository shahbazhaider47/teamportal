<?php defined('BASEPATH') or exit('No direct script access allowed');

$sections = is_array($sections ?? null) ? $sections : [];
$stats    = is_array($stats ?? null) ? $stats : [];

$totalRequests = (int)($stats['total'] ?? 0);

// Fallback: if unified stats are not wired yet, derive total from sections
if ($totalRequests === 0 && !empty($sections)) {
    $totalRequests = array_sum(array_map(static function ($s) {
        return (int)($s['total'] ?? 0);
    }, $sections));
}

?>

<div class="container-fluid">

  <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <h1 class="h6 header-title"><?= html_escape($page_title ?? 'Requests Overview'); ?></h1>

      <span class="badge bg-primary">
        Total Requests: <?= (int)$totalRequests; ?>
      </span>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= site_url('requests/new_request') ?>"
           class="btn btn-primary btn-header"
           title="Add new request for staff">
            <i class="ti ti-plus me-1"></i> New Request
        </a>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0">Requests by Employees</h5>
        <small class="text-muted">
          High-level summary of requests grouped by module (leaves, payroll advances, inventory, loans, etc.).
        </small>
      </div>
    </div>

    <div class="app-divider-v dotted"></div>
    
    <div class="card-body">
      <?php if (empty($sections)): ?>
        <div class="p-4 text-center text-muted">
          <i class="ti ti-inbox mb-2" style="font-size: 2rem;"></i>
          <p class="mb-0">No modules have registered request sections yet.</p>
          <small>Once modules like Attendance, Payroll, Inventory register their request sections, they will appear here.</small>
        </div>
      <?php else: ?>

        <div class="row g-3">
          <?php foreach ($sections as $sec): ?>
            <?php
              $slug     = $sec['slug']        ?? '';
              $label    = $sec['label']       ?? ucfirst($slug);
              $icon     = $sec['icon']        ?? 'ti ti-list';
              $desc     = $sec['description'] ?? '';
              $url      = $sec['url']         ?? site_url('requests/' . $slug);
              $total    = (int)($sec['total']   ?? 0);
              $pending  = (int)($sec['pending'] ?? 0);
              $approved = (int)($sec['approved'] ?? 0);
              $rejected = (int)($sec['rejected'] ?? 0);
              $other    = (int)($sec['other'] ?? 0);
            ?>
            <div class="col-md-4 col-lg-3">
              <div class="card h-100 shadow-sm border-1">
                <div class="card-body d-flex flex-column">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-light-primary text-primary">
                        <i class="<?= html_escape($icon); ?>"></i>
                      </span>
                      <h6 class="mb-0"><?= html_escape($label); ?></h6>
                    </div>
                    <span class="badge bg-light-primary capital">
                    Total: <?= $total; ?>
                    </span>
                  </div>

                  <?php if ($desc): ?>
                    <p class="text-muted small mb-2"><?= html_escape($desc); ?></p>
                  <?php endif; ?>

                    <div class="card-body px-0 pb-0">
                      <ol class="list-group list-group-numbered p-1">
                    
                        <li class="list-group-item d-flex justify-content-between align-items-start text-warning">
                          <div class="ms-2 w-100">
                            <div class="w-100 d-flex justify-content-between align-items-center">
                              <div class="small me-1">Pending</div>
                              <span class="pill pill-warning"><?= (int)$pending; ?></span>
                            </div>
                          </div>
                        </li>
                    
                        <li class="list-group-item d-flex justify-content-between align-items-start text-success">
                          <div class="ms-2 w-100">
                            <div class="w-100 d-flex justify-content-between align-items-center">
                              <div class="small me-1">Approved</div>
                              <span class="pill pill-success"><?= (int)$approved; ?></span>
                            </div>
                          </div>
                        </li>
                    
                        <li class="list-group-item d-flex justify-content-between align-items-start text-danger">
                          <div class="ms-2 w-100">
                            <div class="w-100 d-flex justify-content-between align-items-center">
                              <div class="small me-1">Rejected</div>
                              <span class="pill pill-danger"><?= (int)$rejected; ?></span>
                            </div>
                          </div>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-start text-info">
                          <div class="ms-2 w-100">
                            <div class="w-100 d-flex justify-content-between align-items-center">
                              <div class="small me-1">Other</div>
                              <span class="pill pill-info"><?= (int)$other; ?></span>
                            </div>
                          </div>
                        </li>
                        
                      </ol>
                    </div>

                  <div class="mt-3 d-flex justify-content-end">
                    <a href="<?= html_escape($url); ?>" target="_blank" rel="noopener noreferrer"
                           class="btn btn-light-primary btn-sm">
                        <?= html_escape($label); ?> <i class="ti ti-external-link"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>
  </div>
</div>
