<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $CI =& get_instance(); ?>

<style>
/* ── Proposal number link ─────────────────────────────────────────────── */
.prp-number-link {
    font-size: 12px;
    font-weight: 700;
    color: #056464;
    text-decoration: none;
    font-family: 'Courier New', monospace;
    letter-spacing: 0.3px;
    white-space: nowrap;
}
.prp-number-link:hover { color: #044848; text-decoration: underline; }

/* ── Value cell ───────────────────────────────────────────────────────── */
.prp-value-amount {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    text-align: right;
}
.prp-value-currency {
    font-size: 10.5px;
    font-weight: 600;
    color: #94a3b8;
    margin-right: 2px;
}

/* ── Date cell ────────────────────────────────────────────────────────── */
.prp-date-main { font-size: 12px; color: #475569; white-space: nowrap; }
.prp-date-sub  { font-size: 11px; color: #94a3b8; margin-top: 1px; }

/* ── Action group ─────────────────────────────────────────────────────── */
.prp-action-group {
    display: flex;
    gap: 4px;
    align-items: center;
    justify-content: flex-end;
}
.prp-action-group .btn {
    padding: 3px 9px;
    font-size: 11px;
    border-radius: 6px;
}

</style>

<?php
$proposals = is_array($proposals ?? null) ? $proposals : [];
$counts    = is_array($counts ?? null) ? $counts : [];
$filters   = is_array($filters ?? null) ? $filters : [];
$can       = is_array($can ?? null) ? $can : [];
$status_cfg   = proposal_statuses();
$forecast_cfg = forecast_categories();
$active_status = trim((string)($filters['status'] ?? ''));
$table_id      = $table_id ?? 'crmProposalsTable';

$total_status_count = 0;
foreach (array_keys($status_cfg) as $status_key) {
    $total_status_count += (int)($counts[$status_key] ?? 0);
}


?>

<div class="container-fluid">

    <div class="crm-page-header">
        <div class="crm-page-icon me-3">
            <i class="ti ti-file-certificate"></i>
        </div>

        <div class="flex-grow-1">
            <div class="crm-page-title"><?= html_escape($page_title ?? 'Client Groups') ?></div>
            <div class="crm-page-sub">Manage proposals for clients and leads</div>
        </div>

        <div class="ms-auto d-flex gap-2">

            <?php if (!empty($can['create'])): ?>
                <a href="<?= site_url('crm/proposals/create') ?>"
                   class="btn btn-header btn-primary">
                    <i class="ti ti-plus me-1"></i>New Proposal
                </a>
            <?php endif; ?>
            
            <div class="btn-divider mt-1"></div>

            <?php render_export_buttons([
                'filename' => $page_title ?? 'groups_export'
            ]); ?>
        </div>
    </div>

    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">
                <?php if (function_exists('app_table_filter')): ?>
                    <?php app_table_filter($table_id, [
                        'exclude_columns' => ['Total Value', 'Created', 'Actions'],
                    ]); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="crm-card">
        <div class="row g-2 mb-3">
            <?php foreach (proposal_statuses() as $key => $cfg): ?>
                <div class="col">
                    <div class="crm-kpi-card <?= $active_status === $key ? 'is-active' : '' ?>"
                         onclick="window.location='?status=<?= html_escape($key) ?>'">
                        <div class="crm-kpi-icon" style="background:<?= html_escape($cfg['icon_bg']) ?>;">
                            <i class="ti <?= html_escape($cfg['icon']) ?>"
                               style="color:<?= html_escape($cfg['icon_color']) ?>;"></i>
                        </div>
                        <div>
                            <div class="crm-kpi-value"><?= (int)($counts[$key] ?? 0) ?></div>
                            <div class="crm-kpi-label"><?= html_escape($cfg['label']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="app-divider-v dashed mb-3"></div>
    
        <div class="table-responsive crm-table">
            <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover" id="<?= html_escape($table_id); ?>">
                <thead class="bg-light-primary">
                    <tr>
                        <th>Title</th>
                        <th>Lead</th>
                        <th>Status</th>
                        <th>Forecast</th>
                        <th>Total Value</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proposals)): ?>
                        <?php foreach ($proposals as $proposal): ?>
                            <?php
                            $st_key = (string)($proposal['status'] ?? 'draft');
                            $st     = $status_cfg[$st_key] ?? $status_cfg['draft'];

                            $fc_key = (string)($proposal['forecast_category'] ?? '');
                            $fc     = $forecast_cfg[$fc_key] ?? null;

                            $expires_html = '—';
                            if (!empty($proposal['expires_at'])) {
                                $exp  = new DateTime(date('Y-m-d', strtotime($proposal['expires_at'])));
                                $now  = new DateTime(date('Y-m-d'));
                                $diff = (int)$now->diff($exp)->days;
                                $past = $exp < $now;

                                if ($past) {
                                    $expires_html = '<span style="color:#dc2626;font-weight:600;">' . date('M j, Y', strtotime($proposal['expires_at'])) . '</span>';
                                } elseif ($diff <= 7) {
                                    $expires_html = '<span class="kpi-value-warning" style="font-size:12px;">' . date('M j, Y', strtotime($proposal['expires_at'])) . '<br><small>' . $diff . 'd left</small></span>';
                                } else {
                                    $expires_html = '<span class="prp-date-main">' . date('M j, Y', strtotime($proposal['expires_at'])) . '</span>';
                                }
                            }
                            ?>
                            <tr>

                                <td>
                                    <a href="<?= site_url('crm/proposals/view/' . (int)$proposal['id']) ?>" class="text-primary">
                                        <div class="text-primary fw-bold"><?= html_escape($proposal['title'] ?? 'Untitled Proposal') ?></div>
                                        <div class="text-light small"><?= html_escape($proposal['proposal_number'] ?? '—') ?></div>
                                    </a>
                                </td>

                                <td>
                                    <div class="text-muted"><?= html_escape($proposal['practice_name'] ?? '—') ?></div>
                                    <?php if (!empty($proposal['contact_person'])): ?>
                                        <div class="text-light small"><?= html_escape($proposal['contact_person']) ?></div>
                                    <?php endif; ?>
                                </td>

                                <td><?= proposal_status_badge($proposal['status'] ?? 'draft') ?></td>

                                <td><?= forecast_badge($proposal['forecast_category'] ?? '') ?></td>

                                <td>
                                    <div class="prp-value-amount">
                                        <span class="prp-value-currency"><?= html_escape($proposal['currency'] ?? 'USD') ?></span><?= number_format((float)($proposal['total_value'] ?? 0), 2) ?>
                                    </div>
                                </td>

                                <td>
                                    <?php if (!empty($proposal['created_at'])): ?>
                                        <div class="prp-date-main"><?= date('M j, Y', strtotime($proposal['created_at'])) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($proposal['created_by_name'])): ?>
                                        <div class="prp-date-sub"><?= html_escape($proposal['created_by_name']) ?></div>
                                    <?php endif; ?>
                                </td>

                                <td><?= $expires_html ?></td>

                                <td>
                                    <div class="prp-action-group">
                                        <a href="<?= site_url('crm/proposals/view/' . (int)$proposal['id']) ?>"
                                           class="btn btn-sm btn-outline-secondary"
                                           title="View">
                                            <i class="ti ti-eye"></i>
                                        </a>

                                        <?php if (!empty($can['edit'])): ?>
                                            <a href="<?= site_url('crm/proposals/edit/' . (int)$proposal['id']) ?>"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Edit">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($can['delete'])): ?>
                                            <form method="post"
                                                  action="<?= site_url('crm/proposals/delete/' . (int)$proposal['id']) ?>"
                                                  onsubmit="return confirm('Delete this proposal? This cannot be undone.');"
                                                  class="d-inline">
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Delete">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">
                                <div class="placeholder-content">
                                    <i class="ti ti-file-invoice"></i>
                                    <h4>No Proposals Found</h4>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>