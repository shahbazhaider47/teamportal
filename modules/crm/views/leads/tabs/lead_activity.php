<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
.crm-timeline { position: relative; padding: 8px 0; }
.crm-timeline::before {
    content: '';
    position: absolute;
    left: 18px;
    top: 40px; bottom: 25px;
    width: 2px;
    background: linear-gradient(to bottom, #e0e7ff, #c7d2fe, #e0e7ff);
    border-radius: 2px;
}
.crm-timeline-item { position: relative; display: flex; gap: 16px; margin-bottom: 24px; }
.crm-timeline-item:last-child { margin-bottom: 0; }
.crm-timeline-icon {
    position: relative; z-index: 1;
    width: 38px; height: 38px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
}
.crm-timeline-icon.crm-call    { background: #dbeafe; color: #2563eb; }
.crm-timeline-icon.crm-email   { background: #d1fae5; color: #059669; }
.crm-timeline-icon.crm-meeting { background: #ede9fe; color: #7c3aed; }
.crm-timeline-icon.crm-note    { background: #fef9c3; color: #b45309; }
.crm-timeline-icon.crm-status  { background: #fee2e2; color: #dc2626; }
.crm-timeline-icon.crm-task    { background: #fce7f3; color: #db2777; }

.crm-timeline-body { flex: 1; min-width: 0; }
.crm-activity-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 10px;
    padding: 12px 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    transition: box-shadow 0.2s;
}
.crm-activity-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.10); }

.crm-activity-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
.crm-activity-title { font-weight: 600; font-size: 0.775rem; color: #1e293b; margin: 0; }
.crm-activity-time { font-size: 0.75rem; color: #94a3b8; white-space: nowrap; flex-shrink: 0; }
.crm-activity-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.crm-activity-text { font-size: 0.8125rem; color: #64748b; margin: 4px 0 0; line-height: 1.5; }
.crm-activity-user { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; color: #64748b; }
.crm-activity-avatar {
    width: 20px; height: 20px; border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.crm-badge {
    display: inline-flex; align-items: center;
    padding: 2px 8px; border-radius: 20px;
    font-size: 0.7rem; font-weight: 600; letter-spacing: 0.02em;
}
.crm-badge-success  { background: #d1fae5; color: #065f46; }
.crm-badge-warning  { background: #fef3c7; color: #92400e; }
.crm-badge-info     { background: #dbeafe; color: #1e40af; }
.crm-badge-purple   { background: #ede9fe; color: #5b21b6; }
.crm-badge-danger   { background: #fee2e2; color: #991b1b; }
.crm-badge-pink     { background: #fce7f3; color: #9d174d; }
.crm-badge-gray     { background: #f1f5f9; color: #475569; }

.crm-outcome-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px;
    font-size: 0.7rem; font-weight: 500;
}
.crm-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

.crm-divider-date {
    display: flex; align-items: center; gap: 10px;
    margin: 20px 10px 20px 10px;
    padding-left: 50px;
}
.crm-divider-date span {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; color: #94a3b8;
    background: #f8fafc; padding: 2px 10px; border-radius: 20px;
    border: 1px solid #e2e8f0; white-space: nowrap;
}
.crm-divider-date::before, .crm-divider-date::after {
    content: ''; flex: 1; height: 1px; background: #e2e8f0;
}

.crm-activity-attachments { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.crm-attachment-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 6px;
    background: #f1f5f9; border: 1px solid #e2e8f0;
    font-size: 0.72rem; color: #475569; cursor: pointer;
    transition: background 0.15s;
}
.crm-attachment-chip:hover { background: #e2e8f0; }

.crm-status-change {
    display: flex; align-items: center; gap: 6px;
    margin-top: 6px; flex-wrap: wrap;
}
.crm-status-arrow { color: #94a3b8; font-size: 12px; }

.crm-load-more {
    text-align: center; padding: 8px 0 4px;
    border-top: 1px dashed #e2e8f0; margin-top: 8px;
}
.crm-load-more a { font-size: 0.8rem; color: #6366f1; text-decoration: none; font-weight: 500; }
.crm-load-more a:hover { text-decoration: underline; }
</style>

<?php
$activities = $activities ?? [];
$groupedActivities = [];
foreach ($activities as $activity) {
    $dateKey = !empty($activity['created_at']) ? date('Y-m-d', strtotime($activity['created_at'])) : date('Y-m-d');
    $groupedActivities[$dateKey][] = $activity;
}

?>

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="fw-semibold">
            <i class="ti ti-activity me-2 text-primary"></i>Activity Timeline
        </div>
    </div>

    <div class="card-body">
        <div class="crm-timeline">

            <?php if (empty($groupedActivities)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="ti ti-activity fs-1 text-muted"></i>
                    </div>
                    <h6 class="mb-1">No activity found</h6>
                    <p class="text-muted mb-0">No timeline records are available for this lead yet.</p>
                </div>
            <?php else: ?>

                <?php foreach ($groupedActivities as $dateKey => $dateActivities): ?>
                    <?php
                        $dateTimestamp = strtotime($dateKey);
                        $today         = date('Y-m-d');
                        $yesterday     = date('Y-m-d', strtotime('-1 day'));

                        if ($dateKey === $today) {
                            $dateLabel = 'Today — ' . date('M d, Y', $dateTimestamp);
                        } elseif ($dateKey === $yesterday) {
                            $dateLabel = 'Yesterday — ' . date('M d, Y', $dateTimestamp);
                        } else {
                            $dateLabel = date('M d, Y', $dateTimestamp);
                        }
                    ?>

                    <div class="crm-divider-date">
                        <span><?= html_escape($dateLabel); ?></span>
                    </div>

                    <?php foreach ($dateActivities as $activity): ?>
                        <?php
                            $map        = crm_activity_icon_class((string)($activity['action'] ?? ''));
                            $title      = crm_activity_title((string)($activity['action'] ?? ''));
                            $badges     = crm_activity_badges($activity);
                            $timeLabel  = !empty($activity['created_at']) ? date('h:i A', strtotime($activity['created_at'])) : '—';
                            $userName   = trim((string)($activity['user_name'] ?? 'System'));
                            $userText   = crm_activity_user_text($activity);
                            $desc       = trim((string)($activity['description'] ?? ''));
                            $meta       = [];

                            if (!empty($activity['metadata'])) {
                                $decoded = json_decode((string)$activity['metadata'], true);
                                if (is_array($decoded)) {
                                    $meta = $decoded;
                                }
                            }

                            $avatarText = '';
                            $nameParts  = preg_split('/\s+/', $userName);
                            if (!empty($nameParts[0])) {
                                $avatarText .= strtoupper(substr($nameParts[0], 0, 1));
                            }
                            if (!empty($nameParts[1])) {
                                $avatarText .= strtoupper(substr($nameParts[1], 0, 1));
                            }
                            if ($avatarText === '') {
                                $avatarText = 'SY';
                            }
                        ?>

                        <div class="crm-timeline-item">
                            <div class="crm-timeline-icon <?= html_escape($map['wrap']); ?>">
                                <i class="<?= html_escape($map['icon']); ?>"></i>
                            </div>

                            <div class="crm-timeline-body">
                                <div class="crm-activity-card">
                                    <div class="crm-activity-header">
                                        <p class="crm-activity-title"><?= html_escape($title); ?></p>
                                        <span class="crm-activity-time">
                                            <i class="ti ti-clock me-1"></i><?= html_escape($timeLabel); ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($badges)): ?>
                                        <div class="crm-activity-meta">
                                            <?php foreach ($badges as $badge): ?>
                                                <span class="crm-badge <?= html_escape($badge['class']); ?>">
                                                    <?php if (!empty($badge['icon'])): ?>
                                                        <i class="<?= html_escape($badge['icon']); ?> me-1"></i>
                                                    <?php endif; ?>
                                                    <?= html_escape($badge['text']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($desc !== ''): ?>
                                        <p class="crm-activity-text"><?= nl2br(html_escape($desc)); ?></p>
                                    <?php endif; ?>

                                    <?php if (($activity['action'] ?? '') === 'status_changed' && (!empty($meta['old_status']) || !empty($meta['new_status']))): ?>
                                        <div class="crm-status-change">
                                            <span class="crm-badge crm-badge-gray">
                                                <?= html_escape(ucwords(str_replace('_', ' ', (string)($meta['old_status'] ?? 'N/A')))); ?>
                                            </span>
                                            <span class="crm-status-arrow"><i class="ti ti-arrow-right"></i></span>
                                            <span class="crm-badge crm-badge-info">
                                                <?= html_escape(ucwords(str_replace('_', ' ', (string)($meta['new_status'] ?? 'N/A')))); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (($activity['action'] ?? '') === 'forecast_updated'): ?>
                                        <div class="crm-activity-meta mt-2">
                                            <?php if (array_key_exists('old_estimated_monthly_revenue', $meta) || array_key_exists('new_estimated_monthly_revenue', $meta)): ?>
                                                <span class="crm-badge crm-badge-gray">
                                                    Revenue:
                                                    <?= html_escape((string)($meta['old_estimated_monthly_revenue'] ?? '0')); ?>
                                                    →
                                                    <?= html_escape((string)($meta['new_estimated_monthly_revenue'] ?? '0')); ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($meta['new_expected_close_date'])): ?>
                                                <span class="crm-badge crm-badge-warning">
                                                    <i class="ti ti-calendar-event me-1"></i>
                                                    Close: <?= html_escape((string)$meta['new_expected_close_date']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (($activity['action'] ?? '') === 'imported' && !empty($meta['file_name'])): ?>
                                        <div class="crm-activity-attachments">
                                            <span class="crm-attachment-chip">
                                                <i class="ti ti-file me-1"></i><?= html_escape((string)$meta['file_name']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (($activity['action'] ?? '') === 'status_changed' && !empty($meta['loss_reason'])): ?>
                                        <div class="mt-2">
                                            <span class="crm-badge crm-badge-warning">
                                                <i class="ti ti-alert-circle me-1"></i>
                                                Loss Reason: <?= html_escape((string)$meta['loss_reason']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="crm-activity-user mt-2">
                                        <div class="crm-activity-avatar"><?= html_escape($avatarText); ?></div>
                                        <span><?= html_escape($userText); ?> <strong><?= user_profile_small($userName); ?></strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <?php if (!empty($groupedActivities) && count($activities) >= 100): ?>
            <div class="crm-load-more">
                <a href="javascript:void(0);"><i class="ti ti-dots me-1"></i>Older activities available</a>
            </div>
        <?php endif; ?>
    </div>
</div>