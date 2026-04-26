<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

    <!-- ── Payment Summary Strip ──────────────────────────── -->
    <div class="mb-3">
        <div class="d-flex align-items-center gap-5 flex-wrap">
    
            <div class="d-flex align-items-center gap-3">
                <div style="width:40px;height:40px;border-radius:50%;background:#f5a623;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ti ti-arrow-down-left" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <div class="kpi-label mb-1">Payment Summary</div>
                    <div class="fin-kpi-value mb-1">
                        $<?= number_format((float)($summary['totals']['total_outstanding'] ?? 0), 2) ?>
                    </div>
                    <div class="small text-light">Total Outstanding Receivables</div>
                </div>
            </div>
    
            <div style="width:1px;height:48px;background:#e2e8f0;flex-shrink:0;"></div>

            <div>
                <div class="kpi-label mb-1">Due Today</div>
                <div class="fin-kpi-value text-warning">
                    $<?= number_format((float)($summary['totals']['due_today'] ?? 0), 2) ?>
                </div>
            </div>
            
            <div style="width:1px;height:48px;background:#e2e8f0;flex-shrink:0;"></div>
    
            <div>
                <div class="kpi-label mb-1">Due Within 30 Days</div>
                <div class="fin-kpi-value">
                    $<?= number_format((float)($summary['totals']['due_30_days'] ?? 0), 2) ?>
                </div>
            </div>
    
            <div style="width:1px;height:48px;background:#e2e8f0;flex-shrink:0;"></div>
    
            <div>
                <div class="kpi-label mb-1">Overdue Invoice</div>
                <div class="fin-kpi-value <?= (float)($summary['totals']['total_overdue'] ?? 0) > 0 ? 'text-danger' : '' ?>">
                    $<?= number_format((float)($summary['totals']['total_overdue'] ?? 0), 2) ?>
                </div>
            </div>
    
            <div style="width:1px;height:48px;background:#e2e8f0;flex-shrink:0;"></div>
    
            <div>
                <div class="kpi-label mb-1">Avg. Days to Get Paid</div>
                <div class="fin-kpi-value">
                    <?= (int)($summary['totals']['avg_days_to_pay'] ?? 0) ?> Days
                </div>
            </div>
    
        </div>
        
            <div class="app-divider-v dashed mt-3 mb-3"></div>
    
            <div class="row g-2">
            <?php
            $cards = [
                'all'       => ['label'=>'Total','icon'=>'database','color'=>'#eef2ff','text'=>'secondary'],
                'draft'     => ['label'=>'Draft','icon'=>'file','color'=>'#f1f5f9','text'=>'secondary'],
                'sent'      => ['label'=>'Sent','icon'=>'send','color'=>'#ecfeff','text'=>'info'],
                'viewed'    => ['label'=>'Viewed','icon'=>'eye','color'=>'#f0fdf4','text'=>'success'],
                'partial'   => ['label'=>'Partial','icon'=>'adjustments','color'=>'#fefce8','text'=>'warning'],
                'paid'      => ['label'=>'Paid','icon'=>'check','color'=>'#ecfdf5','text'=>'success'],
                'overdue'   => ['label'=>'Overdue','icon'=>'alert-circle','color'=>'#fff7ed','text'=>'warning'],
                'cancelled' => ['label'=>'Cancelled','icon'=>'x','color'=>'#fef2f2','text'=>'danger'],
            ];
    
            $active_tab = $filters['status'] ?? 'all';
    
            foreach ($cards as $key => $card):
                $count = (int)($summary['counts'][$key] ?? 0);
            ?>
                <div class="col">
                    <div class="fin-kpi-card <?= $active_tab === $key ? 'active' : '' ?>">
                        <div class="fin-kpi-icon" style="background:<?= html_escape($card['color']); ?>;">
                            <i class="ti ti-<?= html_escape($card['icon']); ?> text-<?= html_escape($card['text']); ?>"></i>
                        </div>
    
                        <div>
                            <div class="fin-kpi-value"><?= $count ?></div>
                            <div class="fin-kpi-label"><?= html_escape($card['label']); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            
    </div>