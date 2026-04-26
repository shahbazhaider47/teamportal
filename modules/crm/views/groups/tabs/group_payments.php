<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (empty($payments)): ?>
    <div class="placeholder-content">
        <i class="ti ti-cash-off"></i>
        <h4>No Payments Yet</h4>
        <p>No payments have been recorded for this group.</p>
    </div>
<?php else: ?>
    <div class="crm-card p-0">
        <div class="table-responsive crm-table">
            <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover">
                <thead class="bg-light-primary">
                    <tr>
                        <th>Reference</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td class="small"><?= html_escape($pay['reference'] ?? '—') ?></td>
                            <td class="small text-primary"><?= html_escape($pay['invoice_number'] ?? '—') ?></td>
                            <td class="small fw-semibold text-success">
                                <?= html_escape($pay['currency'] ?? 'USD') ?>
                                <?= number_format((float)($pay['amount'] ?? 0), 2) ?>
                            </td>
                            <td class="small"><?= html_escape($pay['method'] ?? '—') ?></td>
                            <td class="small"><?= html_escape(crm_date($pay['payment_date'] ?? null)) ?></td>
                            <td class="small"><?= html_escape($pay['received_by'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>