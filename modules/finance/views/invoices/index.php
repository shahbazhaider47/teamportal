<?php defined('BASEPATH') or exit('No direct script access allowed'); 
$table_id = $table_id ?? 'dataTable';
?>
<?php $CI =& get_instance(); ?>

<div class="container-fluid">

    <div class="fin-page-header mb-3">
        <div class="fin-page-icon me-3">
            <i class="ti ti-users"></i>
        </div>

        <div class="flex-grow-1">
            <div class="fin-page-title">
                <?= html_escape($page_title ?? 'All Invoices') ?>
            </div>

            <div class="fin-page-sub">
                Manage all invoices for all group and direct clients
            </div>
        </div>

        <div class="ms-auto d-flex gap-2">
            <?php if (staff_can('create', 'finance')): ?>
                <a href="<?= site_url('finance/invoices/create') ?>" class="btn btn-primary btn-header">
                    <i class="fa fa-plus me-1"></i> New Invoice
                </a>
            <?php endif; ?>

            <div class="btn-divider mt-1"></div>

            <?php render_export_buttons([
                'filename' => $page_title ?? 'export'
            ]); ?>
        </div>
    </div>

    <div class="collapse multi-collapse" id="showFilter">
        <div class="card">
            <div class="card-body">    
                <?php if (function_exists('app_table_filter')): ?>
                    <?php app_table_filter($table_id, [
                        'exclude_columns' => ['Actions'],
                    ]); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <div class="fin-card">
        
        <?php echo $CI->load->view('finance/invoices/stats/invoices_top_stats', [], true); ?>
        
        <div class="card-body p-0">
            
        <?php if (empty($invoices)): ?>
            <div class="empty-state text-center py-5">
                <i class="fa fa-file-invoice fa-3x text-muted mb-3"></i>
                <p class="text-muted">No invoices found.</p>

                <?php if (staff_can('create', 'finance')): ?>
                    <a href="<?= site_url('finance/invoices/add') ?>" class="btn btn-primary btn-sm">
                        Create your first invoice
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>

        <div class="fin-table compact">
            <div class="fin-table-scroll">
                <table class="table inv-table mb-2" id="<?= html_escape($table_id); ?>">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Date</th>
                            <th style="width: 130px;">Invoice #</th>
                            <th style="width: 200px;">Client Name</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Paid At</th>
                            <th>Balance Due</th>
                            <th>Status</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($invoices as $inv): 

                        $is_overdue = false;
                        $overdue_days = 0;

                        if (!empty($inv->due_date) && $inv->status !== 'paid') {
                            $due = strtotime($inv->due_date);
                            if ($due < strtotime(date('Y-m-d'))) {
                                $is_overdue = true;
                                $overdue_days = floor((time() - $due) / 86400);
                            }
                        }

                        $status = $inv->status;
                        if ($is_overdue) {
                            $status = 'overdue';
                        }

                    ?>
                        <tr data-invoice-id="<?= (int)$inv->id ?>">

                            <td>
                                <?= html_escape(date('d M Y', strtotime($inv->invoice_date))) ?>
                            </td>

                            <td>
                                <a href="<?= site_url('finance/invoices/view/' . (int)$inv->id) ?>"
                                   class="fw-semibold text-primary">
                                    <?= html_escape($inv->invoice_number) ?>
                                </a>
                            </td>

                            <td>
                                <?= html_escape($inv->client_name) ?>
                            </td>
                            
                            <td>
                                <?php if (!empty($inv->due_date)): ?>
                                    <span class="<?= $is_overdue ? 'text-danger fw-semibold' : '' ?>">
                                        <?= html_escape(date('d M Y', strtotime($inv->due_date))) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= number_format((float)$inv->total_amount, 2) ?>
                            </td>

                            <td>
                                <?php if (!empty($inv->paid_at)): ?>
                                    <span class="<?= $is_overdue ? 'text-danger fw-semibold' : '' ?>">
                                        <?= html_escape(date('d M Y', strtotime($inv->paid_at))) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if ((float)$inv->balance_due > 0): ?>
                                    <span class="text-danger fw-semibold">
                                        <?= number_format((float)$inv->balance_due, 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= render_invoice_status_badge(
                                        $status,
                                        $inv->due_date ?? null  // pass the actual due date string
                                    ); ?>
                            </td>

                            <td class="text-end inv-action-cell">
                                <div class="inv-action-wrap">
                                    <div class="dropdown">
                                        <button class="inv-chevron-btn dropdown-toggle"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false">
                                            <i class="ti ti-chevron-down"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end inv-action-menu">
                            
                                            <?php if (staff_can('view', 'finance')): ?>
                                            <li>
                                                <a class="dropdown-item"
                                                   href="<?= site_url('finance/invoices/view/' . (int)$inv->id) ?>">
                                                    <i class="ti ti-eye"></i> View Invoice
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php if (staff_can('edit', 'finance')): ?>
                                            <li>
                                                <a class="dropdown-item"
                                                   href="<?= site_url('finance/invoices/edit/' . (int)$inv->id) ?>">
                                                    <i class="ti ti-pencil"></i> Edit Invoice
                                                </a>
                                            </li>
                                            <?php endif; ?>
                            
                                            <li>
                                                <a class="dropdown-item"
                                                   href="<?= site_url('finance/invoices/pdf/' . (int)$inv->id) ?>"
                                                   target="_blank">
                                                    <i class="ti ti-download"></i> Download PDF
                                                </a>
                                            </li>
                            
                                            <li>
                                                <a class="dropdown-item"
                                                   href="<?= site_url('finance/invoices/print/' . (int)$inv->id) ?>"
                                                   target="_blank">
                                                    <i class="ti ti-printer"></i> Print Transactions
                                                </a>
                                            </li>
                            
                                            <li>
                                                <button class="dropdown-item"
                                                        onclick="sendInvoiceEmail(<?= (int)$inv->id ?>, '<?= html_escape($inv->invoice_number) ?>')">
                                                    <i class="ti ti-mail"></i> Send Email
                                                </button>
                                            </li>
                            
                                            <li><hr class="dropdown-divider"></li>
                            
                                            <?php if ($inv->status !== 'paid'): ?>
                                            <li>
                                                <button class="dropdown-item inv-action-record-payment"
                                                        data-id="<?= (int)$inv->id ?>"
                                                        data-invoice="<?= html_escape($inv->invoice_number) ?>"
                                                        data-balance="<?= number_format((float)$inv->balance_due, 2) ?>">
                                                    <i class="ti ti-cash"></i> Record Payment
                                                </button>
                                            </li>
                                            <?php endif; ?>
                            
                                            <li>
                                                <button class="dropdown-item"
                                                        onclick="copyInvoiceLink(<?= (int)$inv->id ?>)">
                                                    <i class="ti ti-link"></i> Share Invoice Link
                                                </button>
                                            </li>
                            
                                            <?php if (staff_can('delete', 'finance')): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger"
                                                        data-invoice-id="<?= (int)$inv->id ?>"
                                                        data-invoice-number="<?= html_escape($inv->invoice_number) ?>"
                                                        onclick="confirmDeleteInvoice(this)">
                                                    <i class="ti ti-trash"></i> Delete
                                                </button>
                                            </li>
                                            <?php endif; ?>
                            
                                        </ul>
                                    </div>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

            <?php if (!empty($pagination)): ?>
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <small class="text-muted">
                    Showing <?= (int)$offset + 1 ?>
                    –
                    <?= (int)min($offset + $per_page, $total_rows) ?>
                    of <?= number_format((int)$total_rows) ?>
                </small>

                <div><?= $pagination ?></div>
            </div>
            <?php endif; ?>

        <?php endif; ?>

        </div>
    </div>

</div>



<div class="modal fade" id="modalDeleteInvoice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">

            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger">
                    <i class="fa fa-trash me-2"></i>Delete Invoice
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-1">
                <p class="mb-0">
                    Delete invoice <strong id="deleteInvoiceNumber"></strong>?
                    This cannot be undone.
                </p>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-sm btn-danger" id="btnConfirmDelete">
                    Delete
                </button>
            </div>

        </div>
    </div>
</div>


<script>
function confirmDeleteInvoice(btn) {
    const id     = btn.getAttribute('data-invoice-id');
    const number = btn.getAttribute('data-invoice-number');
    document.getElementById('deleteInvoiceNumber').textContent = number;
    document.getElementById('btnConfirmDelete').setAttribute('data-id', id);
    new bootstrap.Modal(document.getElementById('modalDeleteInvoice')).show();
}

document.getElementById('btnConfirmDelete').addEventListener('click', function () {
    const id = this.getAttribute('data-id');
    if (!id) return;
    window.location.href = '<?= site_url('finance/invoices/delete') ?>/' + id;
});

function sendInvoiceEmail(id, number) {
    console.log('[Invoice] Send email for', number, id);
    // wire up your send email modal here
}

function copyInvoiceLink(id) {
    const url = '<?= site_url('finance/invoices/view') ?>/' + id;
    navigator.clipboard.writeText(url).then(() => {
        alert('Invoice link copied to clipboard.');
    });
}
</script>
