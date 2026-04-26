<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (empty($activity_log)): ?>
    <div class="placeholder-content">
        <i class="ti ti-activity-off"></i>
        <h4>No Activity Yet</h4>
        <p>No activity has been recorded for this group.</p>
    </div>
<?php else: ?>
    <div class="audit-section">
        <?php foreach ($activity_log as $log): ?>
            <div class="audit-row">
                <div class="audit-icon"><i class="ti ti-history"></i></div>
                <div style="flex:1;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold"><?= html_escape($log['title'] ?? '') ?></span>
                        <span class="x-small text-muted">
                            <?= html_escape(crm_date($log['created_at'] ?? null, true)) ?>
                        </span>
                    </div>
                    <div class="small text-muted mb-1"><?= html_escape($log['description'] ?? '') ?></div>
                    <div class="x-small text-muted">
                        <i class="ti ti-user"></i> <?= html_escape($log['created_by_name'] ?? 'System') ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>