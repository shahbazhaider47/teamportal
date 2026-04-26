<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between bg-light-secondary page-header gap-3 px-3 py-1 mb-3 rounded-3 shadow-sm">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <h1 class="h6 header-title"><i class="ti ti-report-search"></i> Report Type: <span class="text-muted small"><?= $page_title ?></span></h1>
        <div class="d-flex align-items-center small gap-1">
        </div>        
      </div>
    
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php
          $table_id     = $table_id ?? 'dataTable';
        ?>

        <!-- Back to Reports -->
        <a href="<?= site_url('reports') ?>"
           id="btn-inactive-users"
           class="btn btn-primary btn-header"
           title="Go Back to Reports">
            <i class="ti ti-arrow-left me-1"></i> Back to Reports
        </a>
        
        <div class="btn-divider"></div>

            <!-- Filter & Export Buttons-->
            <?php render_export_buttons([
                'filename' => $page_title ?? 'export'
            ]); ?>

      </div>
    </div>
    
    <!-- Universal table filter (global search + per-column filters) -->
    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
            <?php if (function_exists('app_table_filter')): ?>
                <?php app_table_filter($table_id, [
                    'exclude_columns' => ['ID'],
                ]);
                ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            
            <?php if (!empty($rows)): ?>
                <div class="table-responsive">
                    <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
                        <thead class="bg-light-primary">
                            <tr>
                                <?php foreach ($columns as $col): ?>
                                    <th><?= html_escape($col); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?= html_escape($value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No data available for this report.</p>
            <?php endif; ?>

        </div>
    </div>

</div>
