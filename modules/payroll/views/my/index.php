<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    $labels = $chart_labels_json ?? '[]';
    $gross  = $chart_gross_json  ?? '[]';
    $deduct = $chart_deduct_json ?? '[]';
    $net    = $chart_net_json    ?? '[]';

    /* Convenience: coerce all snapshot values to float */
    $loan_taken   = (float)($loanAgg['loan_taken_total']  ?? 0);
    $loan_paid    = (float)($loanAgg['loan_total_paid']   ?? 0);
    $loan_balance = (float)($loanAgg['loan_balance_total']?? 0);
    $adv_taken    = (float)($advAgg['advance_total']      ?? 0);
    $adv_paid     = (float)($advAgg['advance_paid']       ?? 0);
    $adv_balance  = (float)($advAgg['advance_balance']    ?? 0);

    /* Progress percentages (capped at 100) */
    $loan_pct = $loan_taken > 0 ? min(100, round($loan_paid / $loan_taken * 100)) : 0;
    $adv_pct  = $adv_taken  > 0 ? min(100, round($adv_paid  / $adv_taken  * 100)) : 0;
?>

<style>
.prd-wrap        { padding-bottom: 24px; }

/* ── KPI cards ───────────────────────────────────── */
.prd-kpi-grid {
    display               : grid;
    grid-template-columns : repeat(3, 1fr);
    gap                   : 12px;
    margin-bottom         : 16px;
}
.prd-kpi {
    background    : #ffffff;
    border        : 1px solid #e2e8f0;
    border-radius : 10px;
    padding       : 14px 16px;
    display       : flex;
    align-items   : flex-start;
    gap           : 12px;
}
.prd-kpi-icon {
    width           : 36px;
    height          : 36px;
    border-radius   : 8px;
    display         : flex;
    align-items     : center;
    justify-content : center;
    font-size       : 16px;
    flex-shrink     : 0;
}
.prd-kpi-icon.green  { background: #dcfce7; color: #15803d; }
.prd-kpi-icon.red    { background: #fee2e2; color: #b91c1c; }
.prd-kpi-icon.blue   { background: #dbeafe; color: #1d4ed8; }
.prd-kpi-icon.amber  { background: #fef3c7; color: #b45309; }
.prd-kpi-icon.purple { background: #ede9fe; color: #6d28d9; }
.prd-kpi-icon.teal   { background: #ccfbf1; color: #0f766e; }

.prd-kpi-lbl  { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; margin-bottom: 3px; }
.prd-kpi-val  { font-size: 20px; font-weight: 700; color: #0d1b2a; line-height: 1.2; }
.prd-kpi-sub  { font-size: 11px; color: #94a3b8; margin-top: 2px; }

/* ── Section cards ───────────────────────────────── */
.prd-card {
    background    : #ffffff;
    border        : 1px solid #e2e8f0;
    border-radius : 10px;
    overflow      : hidden;
    margin-bottom : 16px;
}
.prd-card-head {
    display       : flex;
    align-items   : center;
    justify-content: space-between;
    padding       : 11px 16px;
    background    : #f8fafc;
    border-bottom : 1px solid #e2e8f0;
    font-size     : 13px;
    font-weight   : 700;
    color         : #0d1b2a;
}
.prd-card-head i { color: #056464; margin-right: 6px; }
.prd-card-body   { padding: 16px; }

/* ── Snapshot rows ───────────────────────────────── */
.prd-snap-row {
    display         : flex;
    align-items     : center;
    justify-content : space-between;
    padding         : 7px 0;
    font-size       : 13px;
    border-bottom   : 1px solid #f1f5f9;
}
.prd-snap-row:last-child { border-bottom: none; }
.prd-snap-lbl { color: #64748b; font-weight: 500; }
.prd-snap-val { font-weight: 700; color: #0d1b2a; }
.prd-snap-val.danger  { color: #dc2626; }
.prd-snap-val.success { color: #16a34a; }

/* ── Progress bar ────────────────────────────────── */
.prd-progress-wrap { margin-top: 10px; }
.prd-progress-lbl  { display: flex; justify-content: space-between; font-size: 11px; color: #94a3b8; margin-bottom: 5px; }
.prd-progress-track {
    height        : 6px;
    background    : #f1f5f9;
    border-radius : 99px;
    overflow      : hidden;
}
.prd-progress-fill {
    height        : 6px;
    border-radius : 99px;
    background    : #056464;
    transition    : width .4s ease;
}
.prd-progress-fill.warning { background: #d97706; }
.prd-progress-fill.danger  { background: #dc2626; }

/* ── Shortcut nav ────────────────────────────────── */
.prd-nav-grid {
    display               : grid;
    grid-template-columns : repeat(4, 1fr);
    gap                   : 10px;
    margin-bottom         : 16px;
}
.prd-nav-btn {
    display         : flex;
    flex-direction  : column;
    align-items     : center;
    justify-content : center;
    gap             : 6px;
    padding         : 14px 8px;
    background      : #ffffff;
    border          : 1px solid #e2e8f0;
    border-radius   : 10px;
    text-decoration : none;
    color           : #334155;
    font-size       : 12px;
    font-weight     : 600;
    transition      : background .13s, border-color .13s, color .13s;
}
.prd-nav-btn i { font-size: 20px; color: #056464; }
.prd-nav-btn:hover { background: #e6f4f1; border-color: #056464; color: #056464; text-decoration: none; }

/* ── Chart card ──────────────────────────────────── */
.prd-chart-canvas { max-height: 200px; }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 991px) {
    .prd-kpi-grid  { grid-template-columns: repeat(2, 1fr); }
    .prd-nav-grid  { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
    .prd-kpi-grid  { grid-template-columns: 1fr; }
    .prd-nav-grid  { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="container-fluid prd-wrap">

    <!-- ── Page header ──────────────────────────── -->
    <div class="fin-page-header mb-3">
        <div class="fin-page-icon me-3">
            <i class="ti ti-wallet"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fin-page-title"><?= e($page_title ?? 'My Payroll') ?></div>
            <div class="fin-page-sub">Your salary, loans, and advance summary at a glance.</div>
        </div>
    </div>

    <!-- ── Quick nav ─────────────────────────────── -->
    <div class="prd-nav-grid">
        <a href="<?= site_url('Payroll/my') ?>" class="prd-nav-btn">
            <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>
        <a href="<?= site_url('payroll/my/pay_slip') ?>" class="prd-nav-btn">
            <i class="ti ti-file-invoice"></i> Pay Slips
        </a>
        <a href="<?= site_url('payroll/my/my_advances') ?>" class="prd-nav-btn">
            <i class="ti ti-cash"></i> Advances
        </a>
        <a href="<?= site_url('payroll/my/my_loans') ?>" class="prd-nav-btn">
            <i class="ti ti-building-bank"></i> Loans
        </a>
    </div>

    <!-- ── KPI row ───────────────────────────────── -->
    <div class="prd-kpi-grid">

        <div class="prd-kpi">
            <div class="prd-kpi-icon green"><i class="ti ti-building-bank"></i></div>
            <div>
                <div class="prd-kpi-lbl">Loan Taken</div>
                <div class="prd-kpi-val"><?= number_format($loan_taken, 2) ?></div>
                <div class="prd-kpi-sub">Total disbursed</div>
            </div>
        </div>

        <div class="prd-kpi">
            <div class="prd-kpi-icon teal"><i class="ti ti-circle-check"></i></div>
            <div>
                <div class="prd-kpi-lbl">Loan Paid</div>
                <div class="prd-kpi-val"><?= number_format($loan_paid, 2) ?></div>
                <div class="prd-kpi-sub"><?= $loan_pct ?>% repaid</div>
            </div>
        </div>

        <div class="prd-kpi">
            <div class="prd-kpi-icon <?= $loan_balance > 0 ? 'red' : 'green' ?>">
                <i class="ti ti-<?= $loan_balance > 0 ? 'alert-circle' : 'check' ?>"></i>
            </div>
            <div>
                <div class="prd-kpi-lbl">Loan Balance</div>
                <div class="prd-kpi-val"><?= number_format($loan_balance, 2) ?></div>
                <div class="prd-kpi-sub"><?= $loan_balance > 0 ? 'Outstanding' : 'Fully repaid' ?></div>
            </div>
        </div>

        <div class="prd-kpi">
            <div class="prd-kpi-icon amber"><i class="ti ti-cash"></i></div>
            <div>
                <div class="prd-kpi-lbl">Advance Taken</div>
                <div class="prd-kpi-val"><?= number_format($adv_taken, 2) ?></div>
                <div class="prd-kpi-sub">Total advanced</div>
            </div>
        </div>

        <div class="prd-kpi">
            <div class="prd-kpi-icon blue"><i class="ti ti-receipt"></i></div>
            <div>
                <div class="prd-kpi-lbl">Advance Paid</div>
                <div class="prd-kpi-val"><?= number_format($adv_paid, 2) ?></div>
                <div class="prd-kpi-sub"><?= $adv_pct ?>% recovered</div>
            </div>
        </div>

        <div class="prd-kpi">
            <div class="prd-kpi-icon <?= $adv_balance > 0 ? 'red' : 'green' ?>">
                <i class="ti ti-<?= $adv_balance > 0 ? 'alert-circle' : 'check' ?>"></i>
            </div>
            <div>
                <div class="prd-kpi-lbl">Advance Balance</div>
                <div class="prd-kpi-val"><?= number_format($adv_balance, 2) ?></div>
                <div class="prd-kpi-sub"><?= $adv_balance > 0 ? 'Outstanding' : 'Fully recovered' ?></div>
            </div>
        </div>

    </div>

    <div class="row g-3">

        <!-- ── Salary trend chart ─────────────────── -->
        <div class="col-lg-8">
            <div class="prd-card">
                <div class="prd-card-head">
                    <span><i class="ti ti-chart-bar"></i>Salary Trend — Last 12 Months</span>
                </div>
                <div class="prd-card-body">
                    <canvas id="salaryTrend" class="prd-chart-canvas"></canvas>
                </div>
            </div>
        </div>

        <!-- ── Snapshots ─────────────────────────── -->
        <div class="col-lg-4">

            <!-- Loan snapshot -->
            <div class="prd-card">
                <div class="prd-card-head">
                    <span><i class="ti ti-building-bank"></i>Loan Snapshot</span>
                    <a href="<?= site_url('payroll/my/my_loans') ?>" class="btn btn-light-primary btn-header">
                        View <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="prd-card-body">
                    <div class="prd-snap-row">
                        <span class="prd-snap-lbl">Total Taken</span>
                        <span class="prd-snap-val"><?= number_format($loan_taken, 2) ?></span>
                    </div>
                    <div class="prd-snap-row">
                        <span class="prd-snap-lbl">Amount Paid</span>
                        <span class="prd-snap-val success"><?= number_format($loan_paid, 2) ?></span>
                    </div>
                    <div class="prd-snap-row">
                        <span class="prd-snap-lbl">Balance Due</span>
                        <span class="prd-snap-val <?= $loan_balance > 0 ? 'danger' : 'success' ?>">
                            <?= number_format($loan_balance, 2) ?>
                        </span>
                    </div>
                    <!-- Repayment progress -->
                    <div class="prd-progress-wrap">
                        <div class="prd-progress-lbl">
                            <span>Repayment progress</span>
                            <span><?= $loan_pct ?>%</span>
                        </div>
                        <div class="prd-progress-track">
                            <div class="prd-progress-fill <?= $loan_pct < 30 ? 'danger' : ($loan_pct < 70 ? 'warning' : '') ?>"
                                 style="width:<?= $loan_pct ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advance snapshot -->
            <div class="prd-card">
                <div class="prd-card-head">
                    <span><i class="ti ti-cash"></i>Advance Snapshot</span>
                    <a href="<?= site_url('payroll/my/my_advances') ?>" class="btn btn-light-primary btn-header">
                        View <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="prd-card-body">
                    <div class="prd-snap-row">
                        <span class="prd-snap-lbl">Total Taken</span>
                        <span class="prd-snap-val"><?= number_format($adv_taken, 2) ?></span>
                    </div>
                    <div class="prd-snap-row">
                        <span class="prd-snap-lbl">Amount Paid</span>
                        <span class="prd-snap-val success"><?= number_format($adv_paid, 2) ?></span>
                    </div>
                    <div class="prd-snap-row">
                        <span class="prd-snap-lbl">Balance Due</span>
                        <span class="prd-snap-val <?= $adv_balance > 0 ? 'danger' : 'success' ?>">
                            <?= number_format($adv_balance, 2) ?>
                        </span>
                    </div>
                    <!-- Recovery progress -->
                    <div class="prd-progress-wrap">
                        <div class="prd-progress-lbl">
                            <span>Recovery progress</span>
                            <span><?= $adv_pct ?>%</span>
                        </div>
                        <div class="prd-progress-track">
                            <div class="prd-progress-fill <?= $adv_pct < 30 ? 'danger' : ($adv_pct < 70 ? 'warning' : '') ?>"
                                 style="width:<?= $adv_pct ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div><!-- /.prd-wrap -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var labels = <?= $labels ?>;
    var gross  = <?= $gross  ?>;
    var deduct = <?= $deduct ?>;
    var net    = <?= $net    ?>;

    var ctx = document.getElementById('salaryTrend');
    if (!ctx || !labels.length) return;

    new Chart(ctx.getContext('2d'), {
        type : 'bar',
        data : {
            labels   : labels,
            datasets : [
                {
                    label           : 'Gross',
                    data            : gross,
                    backgroundColor : '#bbf7d0',
                    borderColor     : '#16a34a',
                    borderWidth     : 1.5,
                    borderRadius    : 4,
                },
                {
                    label           : 'Deduction',
                    data            : deduct,
                    backgroundColor : '#fecaca',
                    borderColor     : '#dc2626',
                    borderWidth     : 1.5,
                    borderRadius    : 4,
                },
                {
                    label           : 'Net',
                    data            : net,
                    backgroundColor : '#bfdbfe',
                    borderColor     : '#1d4ed8',
                    borderWidth     : 1.5,
                    borderRadius    : 4,
                },
            ]
        },
        options : {
            responsive         : true,
            maintainAspectRatio: true,
            interaction        : { mode: 'index', intersect: false },
            plugins            : {
                legend : {
                    position : 'bottom',
                    labels   : { boxWidth: 12, font: { size: 12 } }
                },
                tooltip : {
                    callbacks : {
                        label : function (ctx) {
                            return ' ' + ctx.dataset.label + ': ' +
                                parseFloat(ctx.raw || 0).toLocaleString('en-US', {
                                    minimumFractionDigits : 2,
                                    maximumFractionDigits : 2
                                });
                        }
                    }
                }
            },
            scales : {
                x : {
                    grid : { display: false },
                    ticks: { font: { size: 11 } }
                },
                y : {
                    beginAtZero : true,
                    grid        : { color: '#f1f5f9' },
                    ticks       : {
                        font    : { size: 11 },
                        callback: function (v) {
                            return v.toLocaleString();
                        }
                    }
                }
            }
        }
    });
})();
</script>