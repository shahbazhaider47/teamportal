<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (empty($invoices)): ?>
    <div class="placeholder-content">
        <i class="ti ti-file-invoice-off"></i>
        <h4>No Invoices Yet</h4>
        <p>No invoices have been generated for this group.</p>
    </div>
<?php else: ?>
    <div class="crm-card p-0">
        <div class="table-responsive crm-table">
            <table class="table small table-sm table-bottom-border align-middle mb-2 table-box-hover">
                <thead class="bg-light-primary">
                    <tr>
                        <th>Invoice #</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td class="small fw-semibold"><?= html_escape($inv['invoice_number'] ?? '—') ?></td>
                            <td class="small">
                                <div class="fw-semibold"><?= html_escape($inv['description'] ?? '—') ?></div>
                            </td>
                            <td class="small fw-semibold">
                                <?= html_escape($inv['currency'] ?? 'USD') ?>
                                <?= number_format((float)($inv['amount'] ?? 0), 2) ?>
                            </td>
                            <td><?= html_escape($inv['status'] ?? '—') ?></td>
                            <td class="small"><?= html_escape(crm_date($inv['due_date'] ?? null)) ?></td>
                            <td class="text-end">
                                <a href="<?= site_url('crm/invoices/view/' . (int)($inv['id'] ?? 0)) ?>"
                                   class="btn btn-light-primary btn-header">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>