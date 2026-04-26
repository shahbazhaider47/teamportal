<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?> >
            <?php if (isset($user)): ?>
            <span class="text-muted small"><?= html_escape($user['firstname'] . ' ' . $user['lastname']) ?></span>
            <?php endif; ?>
        </h1>
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">

        <div class="btn-divider"></div>
    
        <!-- Back to Users -->
        <button type="button"
                id="btn-back-users"
                class="btn btn-primary btn-header"
                onclick="window.location.href='<?= site_url('users') ?>'"
                title="Go Back to Users">
          <i class="fas fa-arrow-left me-1"></i> Back to Users
        </button>
        
      </div>
    </div>

<div class="card">
  <div class="card-header">
<p class="small text-muted">Activity logs are often automatically deleted after 30 days from the last synching or data update</p>
  </div>
  <div class="card-body">
    <?php if (!empty($logs)): ?>
      <ul class="app-timeline-box">
        <?php foreach ($logs as $log): ?>
          <li class="timeline-section">
            <div class="timeline-icon">
              <span class="text-light-info h-35 w-35 d-flex-center b-r-50">
                <i class="ti ti-circle-check f-s-20"></i>
              </span>
            </div>
            <div class="timeline-content bg-light-primary b-1-light">
              <div class="d-flex justify-content-between align-items-center">
                <p class="text-dark mt-3 mb-0"><?= e($log['action']) ?></p>
                <p class="text-dark mb-0 small"><?= time_ago($log['created_at']) ?></p>
              </div>
              <p class="text-dark mb-0 mb-3 small"><?= e(date('d M Y, h:i A', strtotime($log['created_at']))) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="text-center text-muted py-4">
        No activity recorded yet.
      </div>
    <?php endif; ?>
  </div>
</div>
</div>
