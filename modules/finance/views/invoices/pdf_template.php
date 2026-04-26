<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= htmlspecialchars($invoice->invoice_number) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 13px; color: #333; }
        .page { padding: 40px; }
        .header { display: table; width: 100%; margin-bottom: 40px; }
        .header-left  { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: top; }
        .company-name  { font-size: 22px; font-weight: bold; color: #1a1a2e; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #4361ee; text-transform: uppercase; }
        .invoice-meta  { margin-top: 6px; font-size: 12px; color: #666; }
        .invoice-meta td { padding: 2px 8px 2px 0; }
        .section-label { font-size: 10px; text-transform: uppercase; color: #999; letter-spacing: .5px; margin-bottom: 4px; }
        .from-to { display: table; width: 100%; margin-bottom: 30px; }
        .from-col { display: table-cell; width: 50%; }
        .to-col   { display: table-cell; width: 50%; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items thead th { background: #f4f6f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: #666; border-bottom: 2px solid #e0e0e0; }
        table.items tbody td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        table.items tbody tr:last-child td { border-bottom: none; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .totals-wrap { display: table; width: 100%; }
        .totals-spacer { display: table-cell; width: 55%; }
        .totals-table  { display: table-cell; width: 45%; }
        table.totals { width: 100%; border-collapse: collapse; }
        table.totals td { padding: 5px 10px; font-size: 13px; }
        table.totals .total-row td { font-size: 16px; font-weight: bold; border-top: 2px solid #4361ee; padding-top: 8px; color: #4361ee; }
        .badge-status { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-paid     { background: #d4edda; color: #155724; }
        .badge-draft    { background: #e2e3e5; color: #383d41; }
        .badge-sent     { background: #cce5ff; color: #004085; }
        .badge-partial  { background: #fff3cd; color: #856404; }
        .badge-overdue  { background: #f8d7da; color: #721c24; }
        .notes-section { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .notes-section h5 { font-size: 11px; text-transform: uppercase; color: #999; margin-bottom: 6px; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="company-name"><?= htmlspecialchars(company_setting('company_name') ?: 'Your Company') ?></div>
            <div style="color:#666; margin-top:4px; font-size:12px;">
                <?= nl2br(htmlspecialchars(company_setting('company_address') ?: '')) ?>
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">Invoice</div>
            <div style="margin-top:8px;">
                <span class="badge-status badge-<?= $invoice->status ?>">
                    <?= ucfirst($invoice->status) ?>
                </span>
            </div>
            <table class="invoice-meta" style="margin-left:auto; margin-top:10px;">
                <tr>
                    <td style="color:#999">Invoice #</td>
                    <td><strong><?= htmlspecialchars($invoice->invoice_number) ?></strong></td>
                </tr>
                <tr>
                    <td style="color:#999">Date</td>
                    <td><?= date('d M Y', strtotime($invoice->invoice_date)) ?></td>
                </tr>
                <?php if ($invoice->due_date): ?>
                <tr>
                    <td style="color:#999">Due</td>
                    <td><?= date('d M Y', strtotime($invoice->due_date)) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($invoice->po_number): ?>
                <tr>
                    <td style="color:#999">PO #</td>
                    <td><?= htmlspecialchars($invoice->po_number) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Bill To -->
    <div class="from-to">
        <div class="from-col">
            <div class="section-label">Billed To</div>
            <strong><?= htmlspecialchars($client->company ?? $invoice->client_name) ?></strong><br>
            <?php if (!empty($client->email)): ?>
            <span style="color:#666"><?= htmlspecialchars($client->email) ?></span><br>
            <?php endif; ?>
            <?php if (!empty($client->address)): ?>
            <span style="color:#666; font-size:12px;"><?= nl2br(htmlspecialchars($client->address)) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Line Items -->
    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Item / Description</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Unit</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $idx => $item): ?>
        <tr>
            <td style="color:#999"><?= $idx + 1 ?></td>
            <td>
                <strong><?= htmlspecialchars($item->item_name) ?></strong>
                <?php if ($item->description): ?>
                <br><span style="font-size:11px; color:#888"><?= htmlspecialchars($item->description) ?></span>
                <?php endif; ?>
            </td>
            <td class="text-center"><?= number_format($item->quantity, 2) ?></td>
            <td class="text-center"><?= htmlspecialchars($item->unit ?? '') ?></td>
            <td class="text-right"><?= $invoice->currency ?> <?= number_format($item->unit_price, 2) ?></td>
            <td class="text-right">
                <?php if ($item->discount_amount > 0): ?>
                    <?= number_format($item->discount_amount, 2) ?><?= $item->discount_type === 'percent' ? '%' : '' ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td class="text-right">
                <?= $item->tax_rate > 0 ? $item->tax_rate . '%' : '—' ?>
            </td>
            <td class="text-right"><strong><?= $invoice->currency ?> <?= number_format($item->line_total, 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-wrap">
        <div class="totals-spacer"></div>
        <div class="totals-table">
            <table class="totals">
                <tr>
                    <td style="color:#666">Subtotal</td>
                    <td class="text-right"><?= $invoice->currency ?> <?= number_format($invoice->subtotal, 2) ?></td>
                </tr>
                <?php if ($invoice->discount_amount > 0): ?>
                <tr>
                    <td style="color:#666">Discount</td>
                    <td class="text-right" style="color:#e63946">− <?= $invoice->currency ?> <?= number_format($invoice->discount_amount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($invoice->tax_amount > 0): ?>
                <tr>
                    <td style="color:#666">Tax</td>
                    <td class="text-right"><?= $invoice->currency ?> <?= number_format($invoice->tax_amount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td>Total</td>
                    <td class="text-right"><?= $invoice->currency ?> <?= number_format($invoice->total_amount, 2) ?></td>
                </tr>
                <?php if ($invoice->paid_amount > 0): ?>
                <tr>
                    <td style="color:#28a745">Amount Paid</td>
                    <td class="text-right" style="color:#28a745">− <?= $invoice->currency ?> <?= number_format($invoice->paid_amount, 2) ?></td>
                </tr>
                <tr>
                    <td style="color:#dc3545; font-weight:bold">Balance Due</td>
                    <td class="text-right" style="color:#dc3545; font-weight:bold"><?= $invoice->currency ?> <?= number_format($invoice->balance_due, 2) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Notes / Terms -->
    <?php if ($invoice->notes || $invoice->terms): ?>
    <div class="notes-section">
        <?php if ($invoice->notes): ?>
        <div style="margin-bottom:16px">
            <h5>Notes</h5>
            <p style="color:#555; font-size:12px"><?= nl2br(htmlspecialchars($invoice->notes)) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($invoice->terms): ?>
        <div>
            <h5>Payment Terms</h5>
            <p style="color:#555; font-size:12px"><?= nl2br(htmlspecialchars($invoice->terms)) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /.page -->
</body>
</html>