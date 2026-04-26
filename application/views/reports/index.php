<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><?= $page_title ?></h1>
        <div class="d-flex align-items-center small gap-1">
        </div>        
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $canExport    = staff_can('export', 'general');
          $canPrint     = staff_can('print', 'general');
        ?>
                    
        <div class="btn-divider"></div>

        <!-- Search -->
        <?php foreach (get_reports_menu() as $group): ?>
            <div class="btn-group">
                <button type="button"
                    class="btn btn-light-primary btn-header dropdown-toggle"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="<?= html_escape($group['icon'] ?? 'ti ti-report'); ?>"></i>
                    <?= html_escape($group['label']); ?>
                </button>

            <ul class="dropdown-menu small">
            <?php foreach ($group['items'] as $item): ?>
                <li>
                    <a class="dropdown-item small <?= $item['class'] ?? ''; ?>"
                        href="<?= $item['href']; ?>">
                        <i class="<?= $item['icon'] ?? 'ti ti-arrow-badge-right'; ?>"></i>
                        <?= html_escape($item['label']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

        <!-- Help start -->
        <div class="faq-header text-center">
        
            <h4 class="text-muted mb-4">
                Please select a report type from above to view the report details.
            </h4>
        
            <div class="search-div d-flex justify-content-center flex-wrap gap-2">
                <p class="text-muted small">Common Reports: </p>
                <!-- Common Reports Tags -->
                <a href="<?= site_url('reports/invoice_report'); ?>"
                   class="pill pill-info">
                    Invoice Summary
                </a>
        
                <a href="<?= site_url('reports/financial_report'); ?>"
                   class="pill pill-success">
                    Profit & Loss
                </a>
        
                <a href="<?= site_url('reports/timesheet_report'); ?>"
                   class="pill pill-warning">
                    Timesheet Overview
                </a>
        
                <a href="<?= site_url('reports/project_report'); ?>"
                   class="pill pill-danger">
                    Project Status
                </a>
        
                <a href="<?= site_url('reports/client_report'); ?>"
                   class="pill pill-success">
                    Client Activity
                </a>
        
            </div>
        
        </div>
        <!-- Help end -->

</div>
