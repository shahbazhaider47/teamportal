<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$proposals = is_array($proposals ?? null) ? $proposals : [];
$lead      = is_array($lead ?? null) ? $lead : [];
$can       = is_array($can ?? null) ? $can : [];

$canEdit   = !empty($can['edit']);
$canView   = !empty($can['view']);

$fmt_money = static function ($v, $currency = 'USD'): string {
    $amount   = is_numeric($v) ? (float)$v : 0.00;
    $currency = strtoupper(trim((string)$currency));

    $symbols = [
        'USD' => '$',
        'PKR' => 'Rs ',
        'GBP' => '£',
        'EUR' => '€',
        'AED' => 'AED ',
        'SAR' => 'SAR ',
    ];

    $prefix = $symbols[$currency] ?? ($currency !== '' ? $currency . ' ' : '$');

    return $prefix . number_format($amount, 2);
};

$fmt_date = static function (?string $d): string {
    if (empty($d) || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') {
        return '<span class="text-muted">—</span>';
    }

    $ts = strtotime($d);
    if (!$ts) {
        return '<span class="text-muted">—</span>';
    }

    return date('M j, Y', $ts);
};

$days_until = static function (?string $d): ?int {
    if (empty($d) || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') {
        return null;
    }

    try {
        $target = new DateTime(date('Y-m-d', strtotime($d)));
        $today  = new DateTime(date('Y-m-d'));
        $diff   = $today->diff($target);

        return $target >= $today ? (int)$diff->days : -(int)$diff->days;
    } catch (Throwable $e) {
        return null;
    }
};

$total_value = 0.00;
$approved_count = 0;
$approved_value = 0.00;
$pending_count = 0;

foreach ($proposals as $proposal) {
    $rowValue = isset($proposal['total_value']) && is_numeric($proposal['total_value'])
        ? (float)$proposal['total_value']
        : (isset($proposal['subtotal']) && is_numeric($proposal['subtotal']) ? (float)$proposal['subtotal'] : 0.00);

    $total_value += $rowValue;

    if (($proposal['status'] ?? '') === 'approved') {
        $approved_count++;
        $approved_value += $rowValue;
    }

    if (in_array(($proposal['status'] ?? ''), ['draft', 'pending_review', 'sent', 'viewed'], true)) {
        $pending_count++;
    }
}
?>

<style>
/* expiry chip */
.expiry-ok   { color: #16a34a; font-weight: 600; }
.expiry-warn { color: #d97706; font-weight: 600; }
.expiry-over { color: #b91c1c; font-weight: 600; }

/* title */
.prp-title {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.4;
}
.prp-note {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 2px;
    line-height: 1.4;
}

/* action btn group */
.prp-actions {
    display: flex;
    gap: 4px;
    align-items: center;
}
.prp-actions .btn {
    padding: 3px 9px;
    font-size: 11px;
    border-radius: 6px;
    white-space: nowrap;
}

/* value */
.prp-value {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
}

/* creator avatar-inline */
.creator-inline {
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.creator-chip {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: #056464;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

</style>

    <div class="card-body">

        <div class="kpi-strip mb-4">
            <div class="kpi">
                <div class="kpi-label">Total Proposals</div>
                <div class="kpi-value"><?= count($proposals) ?></div>
                <div class="kpi-sub">For this lead</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Total Value</div>
                <div class="kpi-value" style="color:#056464;">
                    <?= $fmt_money($total_value, !empty($proposals) ? ($proposals[0]['currency'] ?? 'USD') : 'USD') ?>
                </div>
                <div class="kpi-sub">Across all proposals</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Approved Value</div>
                <div class="kpi-value" style="color:#16a34a;">
                    <?= $fmt_money($approved_value, !empty($proposals) ? ($proposals[0]['currency'] ?? 'USD') : 'USD') ?>
                </div>
                <div class="kpi-sub"><?= $approved_count ?> proposal<?= $approved_count !== 1 ? 's' : '' ?> approved</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Awaiting Response</div>
                <div class="kpi-value" style="color:#d97706;"><?= $pending_count ?></div>
                <div class="kpi-sub">Draft, review, sent or viewed</div>
            </div>
        </div>

    <div class="crm-table">
        <div class="table-responsive">
            <table class="crm-table-light">
                <thead>
                    <tr>
                        <th>Proposal Title</th>
                        <th>Status</th>
                        <th>Value</th>
                        <th>Sent</th>
                        <th>Expires</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proposals)): ?>
                        <?php foreach ($proposals as $p): ?>
                            <?php
                            $days = $days_until($p['expires_at'] ?? null);

                            if ($days === null) {
                                $expiry_html = '<span class="text-muted">—</span>';
                            } elseif ($days < 0) {
                                $expiry_html = '<span class="expiry-over">' . $fmt_date($p['expires_at']) . '<br><small>' . abs($days) . 'd ago</small></span>';
                            } elseif ($days <= 7) {
                                $expiry_html = '<span class="expiry-warn">' . $fmt_date($p['expires_at']) . '<br><small>' . $days . 'd left</small></span>';
                            } else {
                                $expiry_html = '<span class="expiry-ok">' . $fmt_date($p['expires_at']) . '</span>';
                            }

                            $createdBy = trim((string)($p['created_by_name'] ?? 'Unknown'));
                            $initialsParts = preg_split('/\s+/', $createdBy) ?: [];
                            $initials = '';

                            foreach ($initialsParts as $part) {
                                if ($part !== '') {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }
                            }

                            $initials = $initials !== '' ? substr($initials, 0, 2) : 'NA';

                            $proposalNumber = trim((string)($p['proposal_number'] ?? ''));
                            if ($proposalNumber === '') {
                                $proposalNumber = 'PRP-' . str_pad((string)((int)($p['id'] ?? 0)), 4, '0', STR_PAD_LEFT);
                            }

                            $displayNote = trim((string)($p['summary'] ?? ''));
                            if ($displayNote === '') {
                                $displayNote = trim((string)($p['client_notes'] ?? ''));
                            }
                            if ($displayNote === '') {
                                $displayNote = trim((string)($p['internal_notes'] ?? ''));
                            }

                            $displayValue = isset($p['total_value']) && is_numeric($p['total_value'])
                                ? (float)$p['total_value']
                                : (isset($p['subtotal']) && is_numeric($p['subtotal']) ? (float)$p['subtotal'] : 0.00);

                            $displayVersion = trim((string)($p['version'] ?? ($p['revision_no'] ?? '—')));
                            ?>
                            <tr>
                                <td>
                                    <?php if ($canView && !empty($p['id'])): ?>
                                        <a href="<?= site_url('crm/proposals/view/' . (int)$p['id']) ?>" class="">
                                            <?= html_escape($p['title'] ?? 'Untitled Proposal') ?></div><br>
                                            <span class="text-light x-small"><?= html_escape($proposalNumber) ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-light x-small"><?= html_escape($proposalNumber) ?></span>
                                    <?php endif; ?>
                                </td>
                                
                                <td><?= proposal_status_badge($p['status']) ?></td>                                
                                
                                <td>
                                    <span class="prp-value"><?= $fmt_money($displayValue, $p['currency'] ?? 'USD') ?></span>
                                </td>

                                <td style="font-size:12px;color:#475569;"><?= $fmt_date($p['sent_at'] ?? null) ?></td>

                                <td style="font-size:12px;"><?= $expiry_html ?></td>

                                <td>
                                    <div class="creator-inline">
                                        <div class="creator-chip"><?= html_escape($initials) ?></div>
                                        <span style="font-size:12px;"><?= html_escape($createdBy) ?></span>
                                    </div>
                                </td>
                                
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No proposals found for this lead.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                
            </table>
        </div>
    </div>
</div>