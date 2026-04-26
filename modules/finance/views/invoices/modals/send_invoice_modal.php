<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Send Invoice Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="modalSendInvoice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fa fa-paper-plane me-2"></i>Send Invoice</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sendInvoiceId">
                <div class="mb-3">
                    <label class="form-label">To (Email) <span class="text-danger">*</span></label>
                    <input type="email" id="sendInvoiceEmail" class="form-control" placeholder="client@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" id="sendInvoiceSubject" class="form-control">
                </div>
                <div class="mb-0">
                    <label class="form-label">Message</label>
                    <textarea id="sendInvoiceMessage" class="form-control" rows="4"
                        placeholder="Please find attached your invoice…"></textarea>
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
