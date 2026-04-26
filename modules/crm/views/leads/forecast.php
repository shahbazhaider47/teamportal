<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<?php
$table_id     = $table_id ?? 'dataTable';
$summary           = $summary ?? [];
$forecastByStage   = $forecast_by_stage ?? [];
$forecastByOwner   = $forecast_by_owner ?? [];
$forecastLeads     = $forecast_leads ?? [];
$filters           = $filters ?? [];

$canView = staff_can('lead_view', 'crm') || staff_can('client_view', 'crm') || staff_can('view', 'crm');

$fmtMoney = static function ($value): string {
    $value = is_numeric($value) ? (float)$value : 0;
    return '$' . number_format($value, 2);
};

$fmtInt = static function ($value): string {
    return number_format((int)$value);
};

$fmtPercent = static function ($value): string {
    $value = is_numeric($value) ? (float)$value : 0;
    return rtrim(rtrim(number_format($value, 2), '0'), '.') . '%';
};

$fmtDate = static function ($value, $format = 'M d, Y'): string {
    return !empty($value) && $value !== '0000-00-00' && $value !== '0000-00-00 00:00:00'
        ? date($format, strtotime($value))
        : '—';
};

$getVal = static function (array $source, array $keys, $default = 0) {
    foreach ($keys as $key) {
        if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
            return $source[$key];
        }
    }
    return $default;
};

$summaryOpenLeads      = (int)$getVal($summary, ['open_leads', 'total_open_leads', 'total_leads'], 0);
$summaryPipelineValue  = (float)$getVal($summary, ['pipeline_value', 'total_pipeline_value', 'open_pipeline_value'], 0);
$summaryWeightedValue  = (float)$getVal($summary, ['weighted_value', 'weighted_forecast_value', 'forecast_value'], 0);
$summaryCommitValue    = (float)$getVal($summary, ['commit_value', 'commit_forecast_value'], 0);
$summaryBestCaseValue  = (float)$getVal($summary, ['best_case_value', 'best_case_forecast_value'], 0);
$summaryWonValue       = (float)$getVal($summary, ['won_value', 'closed_won_value'], 0);

$forecastCategoryColors = [
    'commit'    => ['bg' => '#dcfce7', 'color' => '#166534'],
    'best_case' => ['bg' => '#fef3c7', 'color' => '#92400e'],
    'pipeline'  => ['bg' => '#e0f2fe', 'color' => '#075985'],
    'omitted'   => ['bg' => '#f1f5f9', 'color' => '#475569'],
];

$qualityColors = [
    'hot'  => ['bg' => '#fee2e2', 'color' => '#b91c1c'],
    'warm' => ['bg' => '#fef3c7', 'color' => '#d97706'],
    'cold' => ['bg' => '#e0f2fe', 'color' => '#0369a1'],
];

// BUG FIX: Use correct filter keys for each field
$currentDateFrom = html_escape($filters['date_from'] ?? '');
$currentDateTo   = html_escape($filters['date_to'] ?? '');
$assignedTo      = html_escape($filters['assigned_to'] ?? '');
$currentCategory = html_escape($filters['forecast_category'] ?? '');
?>

<div class="container-fluid">

    <div class="crm-page-header mb-3">
        <div class="crm-page-icon me-3">
            <i class="ti ti-chart-histogram"></i>
        </div>

        <div class="flex-grow-1">
            <div class="crm-page-title"><?= html_escape($page_title ?? 'Client Groups') ?></div>
            <div class="crm-page-sub">Sales forecasting focuses on predicting future sales.</div>
        </div>

        <div class="ms-auto d-flex gap-2">

            <div class="btn-divider mt-1"></div>

            <?php render_export_buttons([
                'filename' => $page_title ?? 'groups_export'
            ]); ?>
        </div>
    </div>


    <!-- Universal table filter (global search + per-column filters) -->
    <div class="collapse multi-collapse" id="showFilter">
        <div class="card mb-4">
            <div class="card-body py-3">
                <form method="get" action="<?= site_url('crm/leads/forecast'); ?>" class="app-form">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-2 col-md-4 col-6">
                            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $currentDateFrom; ?>">
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $currentDateTo; ?>">
                        </div>

                        <div class="col-lg-2 col-md-4 col-6">
                            <input type="text" name="assigned_to" class="form-control form-control-sm" value="<?= $assignedTo; ?>" placeholder="Person name">
                        </div>
                        <div class="col-lg-2 col-md-4 col-6">
                            <select name="forecast_category" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <option value="commit"    <?= ($currentCategory === 'commit'    ? 'selected' : ''); ?>>Commit</option>
                                <option value="best_case" <?= ($currentCategory === 'best_case' ? 'selected' : ''); ?>>Best Case</option>
                                <option value="pipeline"  <?= ($currentCategory === 'pipeline'  ? 'selected' : ''); ?>>Pipeline</option>
                                <option value="omitted"   <?= ($currentCategory === 'omitted'   ? 'selected' : ''); ?>>Omitted</option>
                            </select>
                        </div>
                        <div class="col-lg-1 col-md-4 col-6 d-grid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ti ti-filter me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Summary KPI Cards -->
        <div class="kpi-strip mb-3">
            <?php
            $kpis = [
                ['label' => 'Open Leads',        'value' => $fmtInt($summaryOpenLeads),       'sub' => 'Active pipeline count',    'icon' => 'ti-users',        'accent' => '#6366f1'],
                ['label' => 'Pipeline Value',    'value' => $fmtMoney($summaryPipelineValue), 'sub' => 'Gross open value',         'icon' => 'ti-database',     'accent' => '#0ea5e9'],
                ['label' => 'Weighted Forecast', 'value' => $fmtMoney($summaryWeightedValue), 'sub' => 'Probability adjusted',     'icon' => 'ti-chart-line',   'accent' => '#8b5cf6'],
                ['label' => 'Commit',            'value' => $fmtMoney($summaryCommitValue),   'sub' => 'High-confidence pipeline', 'icon' => 'ti-circle-check', 'accent' => '#10b981'],
                ['label' => 'Best Case',         'value' => $fmtMoney($summaryBestCaseValue), 'sub' => 'Stretch opportunities',    'icon' => 'ti-trending-up',  'accent' => '#f59e0b'],
                ['label' => 'Closed Won',        'value' => $fmtMoney($summaryWonValue),      'sub' => 'Signed business',          'icon' => 'ti-rosette',      'accent' => '#22c55e'],
            ];
            ?>
        
            <?php foreach ($kpis as $kpi): ?>
                <div class="kpi" style="--accent: <?= html_escape($kpi['accent']); ?>;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="fc-icon">
                            <i class="ti <?= html_escape($kpi['icon']); ?>"></i>
                        </div>
                        <span class="fc-label text-light"><?= html_escape($kpi['label']); ?></span>
                    </div>
        
                    <div class="kpi-value mt-2 text-muted"><?= $kpi['value']; ?></div>
                    <div class="kpi-sub text-light mt-1"><?= html_escape($kpi['sub']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>      

<div class="row g-3 mb-4" id="forecast-charts-section">

    <!-- Forecast by Stage -->
    <div class="col-lg-6">
        <div class="fc-chart-card">
            <div class="fc-chart-header">
                <div class="fc-chart-title">
                    <span class="fc-chart-dot" style="background: linear-gradient(135deg,#6366f1,#818cf8)"></span>
                    Forecast by Stage
                </div>
                <a href="<?= site_url('crm/leads'); ?>" class="fc-view-btn">
                    View All <span>→</span>
                </a>
            </div>

            <!-- Horizontal bar chart -->
            <div class="fc-chart-area" style="position:relative; height:260px; padding: 0 4px;">
                <canvas id="stageBarChart"></canvas>
            </div>

            <!-- Clickable legend / table -->
            <div class="fc-stage-list" id="stage-list">
                <?php if (!empty($forecastByStage)):
                    $stageTotal = array_sum(array_map(fn($r) => (float)$getVal($r, ['pipeline_value','total_value','gross_value'],0), $forecastByStage));
                    foreach ($forecastByStage as $idx => $row):
                        $stageKey   = strtolower(trim((string)($row['lead_status'] ?? $row['stage'] ?? 'unknown')));
                        $stageLabel = (string)($row['stage_label'] ?? ucwords(str_replace('_', ' ', $stageKey)));
                        $count      = (int)$getVal($row, ['total_leads','leads_count','total'], 0);
                        $pipeline   = (float)$getVal($row, ['pipeline_value','total_value','gross_value'], 0);
                        $weighted   = (float)$getVal($row, ['weighted_value','forecast_value'], 0);
                        $avgProb    = (float)$getVal($row, ['avg_probability','average_probability'], 0);
                        $pct        = $stageTotal > 0 ? round(($pipeline / $stageTotal) * 100, 1) : 0;
                        $stageMeta  = $statusColors[$stageKey] ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
                ?>
                <a href="<?= site_url('crm/leads?lead_status=' . urlencode($stageKey)); ?>" class="fc-stage-row" data-index="<?= $idx; ?>">
                    <span class="fc-stage-dot" style="background:<?= $stageMeta['color']; ?>"></span>
                    <span class="fc-stage-name"><?= html_escape($stageLabel); ?></span>
                    <span class="fc-stage-count"><?= $count; ?></span>
                    <div class="fc-stage-bar-wrap">
                        <div class="fc-stage-bar-fill" style="width:<?= $pct; ?>%; background:<?= $stageMeta['color']; ?>"></div>
                    </div>
                    <span class="fc-stage-value">$<?= number_format($pipeline/1000,1); ?>k</span>
                    <span class="fc-stage-prob"><?= round($avgProb); ?>%</span>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Forecast by Owner -->
    <div class="col-lg-6">
        <div class="fc-chart-card">
            <div class="fc-chart-header">
                <div class="fc-chart-title">
                    <span class="fc-chart-dot" style="background: linear-gradient(135deg,#06b6d4,#22d3ee)"></span>
                    Forecast by Owner
                </div>
                <a href="<?= site_url('crm/leads'); ?>" class="fc-view-btn">
                    View All <span>→</span>
                </a>
            </div>

            <!-- Donut + bar combo -->
            <div class="fc-owner-layout">
                <div class="fc-donut-wrap">
                    <canvas id="ownerDonutChart" width="160" height="160"></canvas>
                    <div class="fc-donut-center">
                        <div class="fc-donut-total-label">Total</div>
                        <div class="fc-donut-total-val" id="donut-total-val">—</div>
                    </div>
                </div>

                <div class="fc-owner-list" id="owner-list">
                    <?php if (!empty($forecastByOwner)):
                        $ownerTotalPipe = array_sum(array_map(fn($r) => (float)$getVal($r,['pipeline_value','total_value','gross_value'],0), $forecastByOwner));
                        $ownerPalette   = ['#6366f1','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];
                        foreach ($forecastByOwner as $idx => $row):
                            $ownerId   = (int)$getVal($row,['assigned_to','owner_id'],0);
                            $ownerName = (string)$getVal($row,['assigned_to_name','owner_name','fullname'],'Unassigned');
                            $count     = (int)$getVal($row,['total_leads','leads_count','total'],0);
                            $pipeline  = (float)$getVal($row,['pipeline_value','total_value','gross_value'],0);
                            $weighted  = (float)$getVal($row,['weighted_value','forecast_value'],0);
                            $pct       = $ownerTotalPipe > 0 ? round(($pipeline/$ownerTotalPipe)*100,1) : 0;
                            $ownerLink = $ownerId > 0 ? site_url('crm/leads?assigned_to='.$ownerId) : site_url('crm/leads');
                            $clr       = $ownerPalette[$idx % count($ownerPalette)];
                    ?>
                    <a href="<?= $ownerLink; ?>" class="fc-owner-row" data-index="<?= $idx; ?>">
                        <span class="fc-owner-dot" style="background:<?= $clr; ?>"></span>
                        <div class="fc-owner-info">
                            <span class="fc-owner-name"><?= html_escape($ownerName); ?></span>
                        </div>
                        <div class="fc-owner-stats">
                            <span class="fc-owner-pct"><?= $pct; ?>%</span>
                            <span class="fc-owner-val">$<?= number_format($pipeline/1000,1); ?>k</span>
                        </div>
                    </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Grouped bar for weighted vs pipeline per owner -->
            <div style="position:relative; height:100px; margin-top:12px; padding:0 4px;">
                <canvas id="ownerBarChart"></canvas>
            </div>
        </div>
    </div>
</div>

    <!-- Forecast Lead Drilldown -->
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-semibold fs-6"><i class="ti ti-list-details me-2 text-primary"></i>Forecast Leads</span>
            <span class="badge bg-light-primary">Weighted Drilldown</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover">
                    <thead class="bg-light-primary">
                        <tr>
                            <th class="ps-3">Practice</th>
                            <th>Status</th>
                            <th>Quality</th>
                            <th>Category</th>
                            <th class="text-end">Value</th>
                            <th class="text-end">Probability</th>
                            <th class="text-end">Weighted</th>
                            <th>Expected Close</th>
                            <th>Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($forecastLeads)): ?>
                            <?php foreach ($forecastLeads as $row): ?>
                                <?php
                                $leadId        = (int)($row['id'] ?? 0);
                                $practiceName  = (string)($row['practice_name'] ?? '—');
                                $statusKey     = strtolower(trim((string)($row['lead_status'] ?? '')));
                                $statusMeta    = crm_lead_status_meta($statusKey);
                                $qualityKey    = strtolower(trim((string)($row['lead_quality'] ?? '')));
                                $qualityLabel  = ucwords(str_replace('_', ' ', $qualityKey ?: 'n/a'));
                                $categoryKey   = strtolower(trim((string)($row['forecast_category'] ?? 'pipeline')));
                                $categoryLabel = ucwords(str_replace('_', ' ', $categoryKey));
                                $baseValue     = (float)$getVal($row, ['base_value', 'estimated_monthly_revenue', 'monthly_collections'], 0);
                                $probability   = (float)$getVal($row, ['resolved_probability', 'forecast_probability'], 0);
                                // BUG NOTE: fallback weighted calc assumes probability is 0–100 scale; adjust if 0–1 scale is used
                                $weightedValue = (float)$getVal($row, ['weighted_value', 'forecast_value'], ($baseValue * $probability) / 100);
                                $expectedClose = $row['expected_close_date'] ?? null;
                                $ownerName     = (string)$getVal($row, ['assigned_to_name', 'owner_name'], 'Unassigned');

                                $statusMeta    = crm_lead_status_meta($statusKey);
                                $qualityColor  = $qualityColors[$qualityKey]   ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
                                $categoryColor = $forecastCategoryColors[$categoryKey] ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <?php if ($canView && $leadId > 0): ?>
                                            <a href="<?= site_url('crm/leads/view/' . $leadId); ?>" class="text-decoration-none fw-semibold text-primary">
                                                <?= html_escape($practiceName); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="fw-semibold"><?= html_escape($practiceName); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('crm/leads?lead_status=' . urlencode($statusKey)); ?>" class="text-decoration-none">
                                            <span class="stage-badge" style="background:<?= $statusMeta['bg']; ?>;color:<?= $statusMeta['color']; ?>">
                                                <?= html_escape($statusMeta['label']); ?>
                                            </span>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="stage-badge" style="background:<?= $qualityColor['bg']; ?>;color:<?= $qualityColor['color']; ?>">
                                            <?= html_escape($qualityLabel); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="stage-badge" style="background:<?= $categoryColor['bg']; ?>;color:<?= $categoryColor['color']; ?>">
                                            <?= html_escape($categoryLabel); ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?= $fmtMoney($baseValue); ?></td>
                                    <td class="text-end">
                                        <span class="prob-pill"><?= $fmtPercent($probability); ?></span>
                                    </td>
                                    <td class="text-end fw-semibold text-primary"><?= $fmtMoney($weightedValue); ?></td>
                                    <td>
                                        <?php $dateStr = $fmtDate($expectedClose); ?>
                                        <?php if ($dateStr !== '—'): ?>
                                            <span class="date-chip"><?= $dateStr; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= html_escape($ownerName); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center text-muted py-5">No forecast leads found matching your filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>


<script>
(function () {
    /* ── helpers ── */
    const fmt = v => '$' + (v >= 1e6 ? (v/1e6).toFixed(1)+'M' : v >= 1e3 ? (v/1e3).toFixed(1)+'k' : v.toFixed(0));

    /* ── Stage data from PHP ── */
    const stageData = <?php
        $jsStages = [];
        if (!empty($forecastByStage)) {
            foreach ($forecastByStage as $row) {
                $stageKey  = strtolower(trim((string)($row['lead_status'] ?? $row['stage'] ?? 'unknown')));
                $stageMeta = $statusColors[$stageKey] ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
                $stageLabel = (string)($row['stage_label'] ?? ucwords(str_replace('_', ' ', $stageKey)));
                $jsStages[] = [
                    'label'    => $stageLabel,
                    'pipeline' => (float)$getVal($row, ['pipeline_value','total_value','gross_value'], 0),
                    'weighted' => (float)$getVal($row, ['weighted_value','forecast_value'], 0),
                    'prob'     => (float)$getVal($row, ['avg_probability','average_probability'], 0),
                    'color'    => $stageMeta['color'],
                    'bg'       => $stageMeta['bg'],
                    'link'     => site_url('crm/leads?lead_status=' . urlencode($stageKey)),
                ];
            }
        }
        echo json_encode($jsStages);
    ?>;

    /* ── Owner data from PHP ── */
    const ownerData = <?php
        $jsOwners = [];
        $palette  = ['#6366f1','#06b6d4','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];
        if (!empty($forecastByOwner)) {
            foreach ($forecastByOwner as $idx => $row) {
                $ownerId  = (int)$getVal($row,['assigned_to','owner_id'],0);
                $jsOwners[] = [
                    'label'    => (string)$getVal($row,['assigned_to_name','owner_name','fullname'],'Unassigned'),
                    'pipeline' => (float)$getVal($row,['pipeline_value','total_value','gross_value'],0),
                    'weighted' => (float)$getVal($row,['weighted_value','forecast_value'],0),
                    'color'    => $palette[$idx % count($palette)],
                    'link'     => $ownerId > 0 ? site_url('crm/leads?assigned_to='.$ownerId) : site_url('crm/leads'),
                ];
            }
        }
        echo json_encode($jsOwners);
    ?>;

    /* ════════════════════════════════════════
       STAGE HORIZONTAL BAR CHART
    ════════════════════════════════════════ */
    if (stageData.length) {
        const stageCtx = document.getElementById('stageBarChart').getContext('2d');
        const stageChart = new Chart(stageCtx, {
            type: 'bar',
            data: {
                labels: stageData.map(d => d.label),
                datasets: [
                    {
                        label: 'Pipeline',
                        data: stageData.map(d => d.pipeline),
                        backgroundColor: stageData.map(d => d.color + 'cc'),
                        borderColor: stageData.map(d => d.color),
                        borderWidth: 1.5,
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 14,
                    },
                    {
                        label: 'Weighted',
                        data: stageData.map(d => d.weighted),
                        backgroundColor: stageData.map(d => d.color + '44'),
                        borderColor: stageData.map(d => d.color + '88'),
                        borderWidth: 1,
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 14,
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeOutQuart' },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'DM Sans', size: 11, weight: '600' },
                            color: '#64748b',
                            boxWidth: 10, boxHeight: 10,
                            padding: 12,
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'DM Sans', size: 12, weight: '700' },
                        bodyFont: { family: 'DM Mono', size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.parsed.x)
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: {
                            font: { family: 'DM Mono', size: 10 },
                            color: '#94a3b8',
                            callback: v => fmt(v)
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'DM Sans', size: 11, weight: '600' },
                            color: '#475569'
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length) {
                        const idx = elements[0].index;
                        if (stageData[idx]?.link) window.location.href = stageData[idx].link;
                    }
                },
                onHover: (e, elements) => {
                    e.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                    document.querySelectorAll('.fc-stage-row').forEach((el, i) => {
                        el.classList.toggle('is-highlighted', elements.length && elements[0].index === i);
                    });
                }
            }
        });

        /* sync row hover → chart highlight */
        document.querySelectorAll('.fc-stage-row').forEach((el, i) => {
            el.addEventListener('mouseenter', () => {
                stageChart.setDatasetVisibility(0, true);
                stageChart.tooltip.setActiveElements(
                    [{datasetIndex: 0, index: i},{datasetIndex: 1, index: i}],
                    {x: 0, y: 0}
                );
                stageChart.update('none');
            });
            el.addEventListener('mouseleave', () => {
                stageChart.tooltip.setActiveElements([], {x: 0, y: 0});
                stageChart.update('none');
            });
        });
    }

    /* ════════════════════════════════════════
       OWNER DONUT CHART
    ════════════════════════════════════════ */
    if (ownerData.length) {
        const totalPipeline = ownerData.reduce((s,d) => s + d.pipeline, 0);
        document.getElementById('donut-total-val').textContent = fmt(totalPipeline);

        const donutCtx = document.getElementById('ownerDonutChart').getContext('2d');
        const donutChart = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ownerData.map(d => d.label),
                datasets: [{
                    data: ownerData.map(d => d.pipeline),
                    backgroundColor: ownerData.map(d => d.color + 'dd'),
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 8,
                    hoverBorderWidth: 3,
                }]
            },
            options: {
                cutout: '68%',
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'DM Sans', size: 12, weight: '700' },
                        bodyFont: { family: 'DM Mono', size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: ctx => ' Pipeline: ' + fmt(ctx.parsed) + ' (' + ((ctx.parsed/totalPipeline)*100).toFixed(1) + '%)'
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length) {
                        const idx = elements[0].index;
                        if (ownerData[idx]?.link) window.location.href = ownerData[idx].link;
                    }
                },
                onHover: (e, elements) => {
                    e.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                    document.querySelectorAll('.fc-owner-row').forEach((el, i) => {
                        el.classList.toggle('is-highlighted', elements.length && elements[0].index === i);
                    });
                }
            }
        });

        document.querySelectorAll('.fc-owner-row').forEach((el, i) => {
            el.addEventListener('mouseenter', () => {
                donutChart.tooltip.setActiveElements([{datasetIndex:0,index:i}],{x:0,y:0});
                donutChart.update('none');
            });
            el.addEventListener('mouseleave', () => {
                donutChart.tooltip.setActiveElements([],{x:0,y:0});
                donutChart.update('none');
            });
        });

        /* ── Owner grouped bar (Pipeline vs Weighted) ── */
        const ownerBarCtx = document.getElementById('ownerBarChart').getContext('2d');
        new Chart(ownerBarCtx, {
            type: 'bar',
            data: {
                labels: ownerData.map(d => d.label.split(' ')[0]),
                datasets: [
                    {
                        label: 'Pipeline',
                        data: ownerData.map(d => d.pipeline),
                        backgroundColor: ownerData.map(d => d.color + 'bb'),
                        borderRadius: 5,
                        borderSkipped: false,
                        barThickness: 12,
                    },
                    {
                        label: 'Weighted',
                        data: ownerData.map(d => d.weighted),
                        backgroundColor: ownerData.map(d => d.color + '44'),
                        borderRadius: 5,
                        borderSkipped: false,
                        barThickness: 12,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 800, delay: ctx => ctx.dataIndex * 60 },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'DM Sans', size: 10, weight: '600' },
                            color: '#94a3b8',
                            boxWidth: 8, boxHeight: 8, padding: 10
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'DM Sans', size: 11, weight: '700' },
                        bodyFont: { family: 'DM Mono', size: 10 },
                        padding: 8, cornerRadius: 8,
                        callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.parsed.y) }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'DM Sans', size: 10 }, color: '#94a3b8' }
                    },
                    y: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: {
                            font: { family: 'DM Mono', size: 9 },
                            color: '#94a3b8',
                            callback: v => fmt(v)
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length) {
                        const idx = elements[0].index;
                        if (ownerData[idx]?.link) window.location.href = ownerData[idx].link;
                    }
                },
                onHover: (e, elements) => {
                    e.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                }
            }
        });
    }
})();
</script>