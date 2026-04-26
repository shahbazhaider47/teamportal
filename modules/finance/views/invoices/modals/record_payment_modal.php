<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- ── Record Payment Modal ───────────────────────────────────────── -->
<div class="modal fade" id="modalRecordPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fa fa-money-bill me-2"></i>Record Payment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="paymentInvoiceId">
                <div class="alert alert-info py-2 small mb-3">
                    Invoice <strong id="paymentInvoiceNumber"></strong> —
                    Balance due: <strong id="paymentBalanceDue"></strong>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" id="paymentAmount" class="form-control" step="0.01" min="0.01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Date</label>
                        <input type="date" id="paymentDate" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Mode</label>
                        <select id="paymentMode" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="ach">ACH</option>
                            <option value="wire">Wire Transfer</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="digital_wallet">Digital Wallet</option>
                            <option value="other" selected>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference No.</label>
                        <input type="text" id="paymentReference" class="form-control" placeholder="Txn / Cheque #">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea id="paymentNotes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnSavePayment">
                    <i class="fa fa-save me-1"></i> Save Payment
                </button>
            </div>
        </div>
    </div>
</div>