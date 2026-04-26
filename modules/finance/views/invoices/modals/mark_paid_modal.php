<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Mark Paid Confirm Modal ────────────────────────────────────── -->
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
                <p class="mb-0">Mark invoice <strong id="markPaidNumber"></strong> as fully paid?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success" id="btnConfirmMarkPaid">Mark Paid</button>
            </div>
        </div>
    </div>
</div>