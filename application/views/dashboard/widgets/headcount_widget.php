<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI =& get_instance();
$CI->load->model('User_model');

$headcount = null;
try {
    $headcount = $CI->User_model->get_headcount_overview(6);
} catch (Exception $e) {
    log_message('error', 'Headcount widget error: ' . $e->getMessage());
}

if (empty($headcount) || (!isset($headcount['total']) && !isset($headcount['months']))): ?>

<div class="card shadow-sm">
    <div class="card-body text-center py-4">
        <i class="ti ti-users-off text-muted mb-2" style="font-size:28px;"></i>
        <p class="fw-semibold mb-1 small">Headcount data unavailable</p>
        <p class="text-muted x-small mb-2">No staff records found or the widget is not configured.</p>
        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
            <i class="ti ti-refresh me-1"></i>Refresh
        </button>
    </div>
</div>

<?php return; endif;

$total   = (int)($headcount['total']         ?? 0);
$male    = (int)($headcount['male']          ?? 0);
$female  = (int)($headcount['female']        ?? 0);
$months  = array_values($headcount['months'] ?? []);
$series  = array_values($headcount['series'] ?? []);
$growth  = $headcount['growth_percent']      ?? null;
$compare = trim((string)($headcount['compare_label'] ?? ''));

$growthClass = 'hc-growth-flat';
$growthText  = 'No previous period';
$arrowDir    = 'flat';

if ($growth !== null) {
    $abs        = abs((int) $growth);
    $sign       = $growth > 0 ? '+' : ($growth < 0 ? '−' : '');
    $label      = $compare ?: 'prior period';
    $growthText = $sign . $abs . '% vs ' . $label;

    if ($growth > 0)      { $growthClass = 'hc-growth-up';   $arrowDir = 'up'; }
    elseif ($growth < 0)  { $growthClass = 'hc-growth-down'; $arrowDir = 'down'; }
}

$periodLabel = '';
if (!empty($months)) {
    $periodLabel = $months[0] . ' – ' . end($months) . ' ' . date('Y');
}

// Unique ID so multiple widgets on one page never collide
$chartId = 'hcChart_' . uniqid();
?>

<style>
.hc-card {
    background: #fff;
    border: 0.5px solid rgba(148,163,184,0.25);
    border-radius: 12px;
    padding: 1rem 1.25rem 0.75rem;
}
.hc-top-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #94a3b8;
    margin: 0 0 10px;
}
.hc-main-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
}
.hc-total {
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1;
    color: #1e293b;
}
.hc-gender-group { display: flex; gap: 16px; }
.hc-gender-item  { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.hc-gender-num   { font-size: 1rem; font-weight: 600; line-height: 1; color: #1e293b; }
.hc-gender-lbl   { font-size: 11px; color: #94a3b8; }
.hc-divider-v    { width: 0.5px; background: rgba(148,163,184,0.3); align-self: stretch; }
.hc-chart-wrap   { margin: 10px 0 0; height: 58px; position: relative; }
.hc-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 0.5px solid rgba(148,163,184,0.2);
}
.hc-growth-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 100px;
}
.hc-growth-up   { background: #dcfce7; color: #16a34a; }
.hc-growth-down { background: #fee2e2; color: #dc2626; }
.hc-growth-flat { background: #f1f5f9; color: #64748b; }
.hc-period-lbl  { font-size: 11px; color: #94a3b8; }
.hc-gender-icon { width: 14px; height: 14px; display: block; margin: 0 auto 2px; }
</style>

<div class="hc-card">
    <p class="hc-top-label">Total headcount</p>

    <div class="hc-main-row">
        <div class="hc-total"><?= $total ?></div>
        <div class="hc-gender-group">
            <div class="hc-gender-item">
                <span class="hc-gender-num"><?= $male ?></span>
                <span class="hc-gender-lbl"><i class="ti ti-gender-male text-info"></i> Male</span>
            </div>
            <div class="hc-divider-v"></div>
            <div class="hc-gender-item">
                <span class="hc-gender-num"><?= $female ?></span>
                <span class="hc-gender-lbl"><i class="ti ti-gender-male text-danger"></i> Female</span>
            </div>
        </div>
    </div>

    <!-- FIX 1: canvas must be a direct child with explicit width/height attributes -->
<div class="hc-chart-wrap" style="height:120px;margin:12px 0 0;">
    <canvas id="<?= $chartId ?>" width="400" height="120"></canvas>
</div>

    <div class="hc-footer">
        <span class="hc-growth-pill <?= $growthClass ?>">
            <?php if ($arrowDir === 'up'): ?>
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                    <path d="M5 8V2M2 5l3-3 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            <?php elseif ($arrowDir === 'down'): ?>
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                    <path d="M5 2v6M2 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            <?php endif; ?>
            <?= html_escape($growthText) ?>
        </span>
        <span class="hc-period-lbl"><?= html_escape($periodLabel) ?></span>
    </div>
</div>

<script>
(function () {
    var chartId  = <?= json_encode($chartId) ?>;
    var hcMonths = <?= json_encode($months) ?>;
    var hcSeries = <?= json_encode(array_map('floatval', $series)) ?>;

    if (!hcSeries.length) return;

    var minVal = Math.min.apply(null, hcSeries);
    var maxVal = Math.max.apply(null, hcSeries);

function renderChart() {
    var el = document.getElementById(chartId);
    if (!el) return;
    if (el.dataset.init === '1') return;
    el.dataset.init = '1';

    var lineColor  = '#10b981';   // emerald green line
    var dotColor   = '#10b981';
    var fillColor  = 'rgba(16,185,129,0.12)';  // light green fill, no gradient needed
    var tooltipBg  = '#ffffff';
    var tickColor  = 'rgba(0,0,0,0.35)';
    var gridColor  = 'rgba(0,0,0,0.05)';

    new Chart(el, {
        type: 'line',
        data: {
            labels: hcMonths,
            datasets: [{
                data: hcSeries,
                borderColor: lineColor,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: dotColor,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: dotColor,
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
                tension: 0.4,
                fill: true,
                backgroundColor: fillColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 500 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: tooltipBg,
                    titleColor: '#1e293b',
                    bodyColor: '#1e293b',
                    borderColor: 'rgba(0,0,0,0.08)',
                    borderWidth: 0.5,
                    padding: 8,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (item) {
                            return Math.round(item.raw) + ' employees';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: true,
                        color: gridColor,
                        lineWidth: 0.5
                    },
                    border: { display: false },
                    ticks: {
                        font: { size: 11 },
                        color: tickColor,
                        maxRotation: 0
                    }
                },
                y: {
                    display: false,
                    min: minVal === maxVal ? minVal * 0.9 : minVal * 0.85,
                    max: minVal === maxVal ? maxVal * 1.1 : maxVal * 1.08
                }
            }
        }
    });
}
    function loadAndRender() {
        if (typeof Chart !== 'undefined') {
            // Chart.js already on page — render immediately
            renderChart();
            return;
        }

        // Not loaded yet — inject it once, then render on load
        if (document.getElementById('chartjs-cdn')) {
            // Script tag exists but Chart not ready yet — wait for it
            var wait = setInterval(function () {
                if (typeof Chart !== 'undefined') {
                    clearInterval(wait);
                    renderChart();
                }
            }, 100);
            return;
        }

        var script = document.createElement('script');
        script.id  = 'chartjs-cdn';
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
        script.onload = function () { renderChart(); };
        script.onerror = function () {
            // CDN failed — render a plain SVG polyline fallback
            renderFallback();
        };
        document.head.appendChild(script);
    }

    function renderFallback() {
        var el = document.getElementById(chartId);
        if (!el) return;

        var w = el.parentElement.offsetWidth || 300;
        var h = 58;
        var pts = hcSeries;
        var mn  = minVal === maxVal ? minVal * 0.9  : minVal;
        var mx  = minVal === maxVal ? maxVal * 1.1  : maxVal;
        var pad = 4;

        var points = pts.map(function (v, i) {
            var x = pad + (i / (pts.length - 1 || 1)) * (w - pad * 2);
            var y = h - pad - ((v - mn) / ((mx - mn) || 1)) * (h - pad * 2);
            return x.toFixed(1) + ',' + y.toFixed(1);
        }).join(' ');

        var fillPts = points + ' ' + (w - pad) + ',' + (h - pad) + ' ' + pad + ',' + (h - pad);

        el.parentElement.innerHTML =
            '<svg width="100%" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="none">' +
            '<polygon points="' + fillPts + '" fill="rgba(59,130,246,0.12)"/>' +
            '<polyline points="' + points + '" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>' +
            '</svg>';
    }

    // Kick off after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAndRender);
    } else {
        loadAndRender();
    }
})();
</script>