<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ================================================================
     finance/invoices/view.php
     ================================================================ -->

<div class="fin-invoice-view">

    <!-- ── Page header ──────────────────────────────────────── -->
    <div class="page-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-1">
                Invoice <span class="text-muted">#<?= htmlspecialchars($invoice->invoice_number) ?></span>
                <span class="badge inv-status-badge badge-<?= $invoice->status ?> ms-2" style="font-size:.75rem">
                    <?= ucfirst($invoice->status) ?>
                </span>
            </h4>

        </div>

        <!-- Action bar -->
        <div class="d-flex gap-2 flex-wrap">

            <a href="<?= site_url('finance/invoices/pdf/' . $invoice->id) ?>"
               class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="fa fa-file-pdf me-1"></i> PDF
            </a>

            <?php if (staff_can('edit', 'finance') && !in_array($invoice->status, ['paid','cancelled'])): ?>
            <a href="<?= site_url('finance/invoices/edit/' . $invoice->id) ?>"
               class="btn btn-sm btn-outline-primary">
                <i class="fa fa-pencil me-1"></i> Edit
            </a>
            <?php endif; ?>

            <?php if (in_array($invoice->status, ['draft','sent','partial']) && staff_can('edit', 'finance')): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-send-invoice"
                    data-id="<?= $invoice->id ?>"
                    data-number="<?= htmlspecialchars($invoice->invoice_number) ?>"
                    data-email="<?= htmlspecialchars($invoice->client_email ?? '') ?>">
                <i class="fa fa-paper-plane me-1"></i> Send
            </button>
            <?php endif; ?>

            <?php if ($invoice->status !== 'paid' && $invoice->balance_due > 0 && staff_can('create', 'finance')): ?>
            <button type="button" class="btn btn-sm btn-success btn-record-payment"
                    data-id="<?= $invoice->id ?>"
                    data-number="<?= htmlspecialchars($invoice->invoice_number) ?>"
                    data-balance="<?= $invoice->balance_due ?>"
                    data-currency="<?= $invoice->currency ?>">
                <i class="fa fa-money-bill me-1"></i> Record Payment
            </button>
            <?php endif; ?>

            <?php if ($invoice->status !== 'paid' && staff_can('edit', 'finance')): ?>
            <button type="button" class="btn btn-sm btn-outline-success btn-mark-paid"
                    data-id="<?= $invoice->id ?>"
                    data-number="<?= htmlspecialchars($invoice->invoice_number) ?>">
                <i class="fa fa-check-circle me-1"></i> Mark Paid
            </button>
            <?php endif; ?>

            <?php if (staff_can('create', 'finance')): ?>
            <a href="<?= site_url('finance/invoices/duplicate/' . $invoice->id) ?>"
               class="btn btn-sm btn-outline-secondary"
               onclick="return confirm('Duplicate this invoice?')">
                <i class="fa fa-copy me-1"></i> Duplicate
            </a>
            <?php endif; ?>

            <?php if (staff_can('delete', 'finance') && $invoice->status !== 'paid'): ?>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-invoice"
                    data-id="<?= $invoice->id ?>"
                    data-number="<?= htmlspecialchars($invoice->invoice_number) ?>">
                <i class="fa fa-trash"></i>
            </button>
            <?php endif; ?>

        </div>
    </div>

    <div class="row g-4">

        <!-- ── LEFT: Invoice preview ──────────────────────── -->
        <div class="col-lg-8">

            <div class="card">
                <div class="card-body p-4" id="invoicePreview">

                    <!-- From / To -->
                    <div class="row mb-4">
                        <div class="col-6">
                            <p class="text-muted small mb-1">FROM</p>
                            <strong><?= htmlspecialchars(company_setting('company_name') ?: 'Your Company') ?></strong>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-muted small mb-1">BILLED TO</p>
                            <strong><?= htmlspecialchars($client->company ?? $invoice->client_name) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($client->email ?? '') ?></small>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr>
                                    <td class="text-muted pe-3">Invoice #</td>
                                    <td><strong><?= htmlspecialchars($invoice->invoice_number) ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted pe-3">Date</td>
                                    <td><?= date('d M Y', strtotime($invoice->invoice_date)) ?></td>
                                </tr>
                                <?php if ($invoice->due_date): ?>
                                <tr>
                                    <td class="text-muted pe-3">Due Date</td>
                                    <td class="<?= strtotime($invoice->due_date) < time() && $invoice->status !== 'paid' ? 'text-danger fw-semibold' : '' ?>">
                                        <?= date('d M Y', strtotime($invoice->due_date)) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($invoice->po_number): ?>
                                <tr>
                                    <td class="text-muted pe-3">PO #</td>
                                    <td><?= htmlspecialchars($invoice->po_number) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-6 text-end">
                            <div class="display-6 text-primary fw-bold">
                                <?= $invoice->currency ?> <?= number_format($invoice->total_amount, 2) ?>
                            </div>
                            <?php if ($invoice->balance_due > 0): ?>
                            <div class="text-danger small mt-1">
                                Balance due: <?= $invoice->currency ?> <?= number_format($invoice->balance_due, 2) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Line items -->
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <td class="text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($item->item_name) ?></strong>
                                    <?php if ($item->description): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($item->description) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= number_format($item->quantity, 2) ?>
                                    <?php if ($item->unit): ?>
                                    <small class="text-muted"><?= htmlspecialchars($item->unit) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= number_format($item->unit_price, 2) ?></td>
                                <td class="text-end">
                                    <?php if ($item->discount_amount > 0): ?>
                                        <?= number_format($item->discount_amount, 2) ?>
                                        <?= $item->discount_type === 'percent' ? '%' : '' ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?= $item->tax_rate > 0 ? $item->tax_rate . '%' : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?= number_format($item->line_total, 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals block -->
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted">Subtotal</td>
                                    <td class="text-end"><?= $invoice->currency ?> <?= number_format($invoice->subtotal, 2) ?></td>
                                </tr>
                                <?php if ($invoice->discount_amount > 0): ?>
                                <tr>
                                    <td class="text-muted">Discount</td>
                                    <td class="text-end text-danger">− <?= $invoice->currency ?> <?= number_format($invoice->discount_amount, 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($invoice->tax_amount > 0): ?>
                                <tr>
                                    <td class="text-muted">Tax</td>
                                    <td class="text-end"><?= $invoice->currency ?> <?= number_format($invoice->tax_amount, 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="border-top fw-bold fs-5">
                                    <td>Total</td>
                                    <td class="text-end"><?= $invoice->currency ?> <?= number_format($invoice->total_amount, 2) ?></td>
                                </tr>
                                <?php if ($invoice->paid_amount > 0): ?>
                                <tr class="text-success">
                                    <td>Paid</td>
                                    <td class="text-end">− <?= $invoice->currency ?> <?= number_format($invoice->paid_amount, 2) ?></td>
                                </tr>
                                <tr class="text-danger fw-semibold">
                                    <td>Balance Due</td>
                                    <td class="text-end"><?= $invoice->currency ?> <?= number_format($invoice->balance_due, 2) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Notes / Terms -->
                    <?php if ($invoice->notes || $invoice->terms): ?>
                    <hr>
                    <div class="row g-3 mt-1">
                        <?php if ($invoice->notes): ?>
                        <div class="col-md-6">
                            <p class="small text-muted mb-1 fw-semibold">NOTES</p>
                            <p class="small"><?= nl2br(htmlspecialchars($invoice->notes)) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($invoice->terms): ?>
                        <div class="col-md-6">
                            <p class="small text-muted mb-1 fw-semibold">PAYMENT TERMS</p>
                            <p class="small"><?= nl2br(htmlspecialchars($invoice->terms)) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div><!-- /#invoicePreview -->
            </div>

        </div><!-- /col-lg-8 -->


        <!-- ── RIGHT: Payments + Activity ─────────────────── -->
        <div class="col-lg-4">

            <!-- Payments -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
                    <span>Payments</span>
                    <?php if ($invoice->status !== 'paid' && $invoice->balance_due > 0 && staff_can('create', 'finance')): ?>
                    <button type="button" class="btn btn-xs btn-outline-success btn-record-payment"
                            data-id="<?= $invoice->id ?>"
                            data-number="<?= htmlspecialchars($invoice->invoice_number) ?>"
                            data-balance="<?= $invoice->balance_due ?>"
                            data-currency="<?= $invoice->currency ?>">
                        <i class="fa fa-plus me-1"></i> Add
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($payments)): ?>
                    <p class="text-muted small text-center py-3 mb-0">No payments recorded.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($payments as $p): ?>
                        <li class="list-group-item px-3 py-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold"><?= $invoice->currency ?> <?= number_format($p->allocated_amount, 2) ?></span>
                                <span class="text-muted"><?= date('d M Y', strtotime($p->payment_date)) ?></span>
                            </div>
                            <div class="text-muted">
                                <?= ucwords(str_replace('_',' ',$p->payment_mode)) ?>
                                <?= $p->reference_no ? ' · ' . htmlspecialchars($p->reference_no) : '' ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity / Timeline -->
            <div class="card">
                <div class="card-header fw-semibold">Activity</div>
                <div class="card-body p-0">
                    <?php if (empty($activity)): ?>
                    <p class="text-muted small text-center py-3 mb-0">No activity recorded.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($activity as $log): ?>
                        <li class="list-group-item px-3 py-2 small">
                            <div><?= htmlspecialchars($log->description) ?></div>
                            <div class="text-muted"><?= time_ago($log->created_at) ?> · <?= htmlspecialchars($log->staff_name ?? '') ?></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /col-lg-4 -->

    </div><!-- /row -->

</div><!-- /.fin-invoice-view -->

<?php $CI =& get_instance(); ?>
<!-- ================================================================
     Shared modals (reused from index, included once per layout)
     ================================================================ -->
<?php $CI->load->view('finance/invoices/_modals') ?>