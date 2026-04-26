<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ================================================================
     finance/invoices/_modals.php
     Shared modals included by index.php and view.php
     ================================================================ -->

<!-- ── Delete Confirm ──────────────────────────────────────────── -->
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
                    <span class="d-block text-muted small mt-1">This action cannot be undone.</span>
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="btnConfirmDelete">
                    <i class="fa fa-trash me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Send Invoice ─────────────────────────────────────────────── -->
<div class="modal fade" id="modalSendInvoice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="fa fa-paper-plane me-2"></i>Send Invoice
                    <small class="text-muted ms-2" id="sendInvoiceNumberLabel"></small>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sendInvoiceId">
                <div class="mb-3">
                    <label class="form-label">To (Email) <span class="text-danger">*</span></label>
                    <input type="email" id="sendInvoiceEmail" class="form-control" placeholder="client@example.com">
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" id="sendInvoiceSubject" class="form-control">
                </div>
                <div class="mb-0">
                    <label class="form-label">Message</label>
                    <textarea id="sendInvoiceMessage" class="form-control" rows="4"
                              placeholder="Please find your invoice attached…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnSendInvoice">
                    <i class="fa fa-paper-plane me-1"></i> Send
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Mark Paid ────────────────────────────────────────────────── -->
<div class="modal fade" id="modalMarkPaid" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-success">
                    <i class="fa fa-check-circle me-2"></i>Mark as Paid
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1">
                <p class="mb-0">
                    Mark invoice <strong id="markPaidNumber"></strong> as fully paid?
                    <span class="d-block text-muted small mt-1">
                        This will set the status to <em>Paid</em> and clear the balance due.
                    </span>
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success" id="btnConfirmMarkPaid">
                    <i class="fa fa-check me-1"></i> Mark Paid
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Record Payment ───────────────────────────────────────────── -->
<div class="modal fade" id="modalRecordPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="fa fa-money-bill me-2"></i>Record Payment
                    <small class="text-muted ms-2" id="paymentInvoiceNumberLabel"></small>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="paymentInvoiceId">

                <div class="alert alert-info py-2 small mb-3">
                    Outstanding balance:
                    <strong id="paymentBalanceDisplay"></strong>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" id="paymentCurrencySymbol">$</span>
                            <input type="number" id="paymentAmount" class="form-control"
                                   step="0.01" min="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Date</label>
                        <input type="date" id="paymentDate" class="form-control"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Mode</label>
                        <select id="paymentMode" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="ach">ACH / Bank Transfer</option>
                            <option value="wire">Wire Transfer</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="digital_wallet">Digital Wallet</option>
                            <option value="other" selected>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference No.</label>
                        <input type="text" id="paymentReference" class="form-control"
                               placeholder="Txn ID / Cheque #">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea id="paymentNotes" class="form-control" rows="2"
                                  placeholder="Optional notes…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success" id="btnSavePayment">
                    <i class="fa fa-save me-1"></i> Save Payment
                </button>
            </div>
        </div>
    </div>
</div>