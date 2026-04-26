/**
 * invoices.js
 * Module : finance / Invoices
 * Handles: line-item calculations, modal wiring, AJAX actions
 */

;(function ($) {
    'use strict';

    /* ================================================================
     * CONFIG
     * ============================================================== */
    var BASE_URL = window.appBaseUrl || '';
    var CSRF_KEY = window.csrfKey   || 'csrf_token';
    var CSRF_VAL = window.csrfVal   || '';

    function csrfData() {
        var d = {};
        d[CSRF_KEY] = CSRF_VAL;
        return d;
    }

    /* ================================================================
     * UTILITIES
     * ============================================================== */

    function showAlert(type, msg, container) {
        var $c = container ? $(container) : $('body');
        var html = '<div class="alert alert-' + type + ' alert-dismissible fade show mt-2" role="alert">'
            + msg
            + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        var $alert = $(html);
        $c.prepend($alert);
        setTimeout(function () { $alert.alert('close'); }, 5000);
    }

    function btnLoading($btn, loading) {
        if (loading) {
            $btn.data('original-html', $btn.html())
                .prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1"></span> Processing…');
        } else {
            $btn.prop('disabled', false)
                .html($btn.data('original-html'));
        }
    }

    function refreshPage() {
        window.location.reload();
    }

    /* ================================================================
     * LINE ITEMS  (form page only)
     * ============================================================== */

    function calcLineTotal($row) {
        var qty       = parseFloat($row.find('.item-qty').val())          || 0;
        var price     = parseFloat($row.find('.item-price').val())        || 0;
        var discVal   = parseFloat($row.find('.item-discount-val').val()) || 0;
        var discType  = $row.find('.item-discount-type').val();
        var taxRate   = parseFloat($row.find('.item-tax').val())          || 0;

        var gross    = qty * price;
        var discAmt  = discType === 'percent' ? gross * (discVal / 100)
                     : discType === 'fixed'   ? discVal
                     : 0;
        var lineTotal = Math.max(0, gross - discAmt);
        var taxAmt    = lineTotal * (taxRate / 100);

        $row.find('.item-line-total').text(lineTotal.toFixed(2));

        return { lineTotal: lineTotal, taxAmt: taxAmt };
    }

    function recalcSummary() {
        var subtotal   = 0;
        var itemsTax   = 0;
        var invDisc    = parseFloat($('#invoiceDiscount').val())  || 0;
        var invTaxRate = parseFloat($('#invoiceTaxRate').val())   || 0;

        $('#lineItemsBody .line-item-row').each(function () {
            var r = calcLineTotal($(this));
            subtotal += r.lineTotal;
            itemsTax += r.taxAmt;
        });

        var afterDisc  = Math.max(0, subtotal - invDisc);
        var invTaxAmt  = afterDisc * (invTaxRate / 100);
        var totalTax   = itemsTax + invTaxAmt;
        var total      = afterDisc + invTaxAmt;

        $('#summarySubtotal').text(subtotal.toFixed(2));
        $('#summaryDiscount').text(invDisc.toFixed(2));
        $('#summaryTax').text(totalTax.toFixed(2));
        $('#summaryTotal').text(total.toFixed(2));
    }

    // Add a new row
    function addItemRow() {
        var $tbody  = $('#lineItemsBody');
        var newIdx  = $tbody.find('.line-item-row').length;
        var $tmpl   = $('#lineItemRowTemplate');

        if (!$tmpl.length) { return; }

        // Clone template content and replace placeholder index
        var html = $tmpl.html().replace(/__IDX__/g, newIdx);
        $tbody.append(html);
        $tbody.find('.line-item-row:last').find('input:first').focus();
        recalcSummary();
    }

    // Reindex all rows so name arrays stay sequential
    function reindexRows() {
        $('#lineItemsBody .line-item-row').each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
            });
        });
    }

    /* ================================================================
     * MODAL HELPERS
     * ============================================================== */

    // ── Delete ──────────────────────────────────────────────────────
    var _deleteId = null;

    $(document).on('click', '.btn-delete-invoice', function (e) {
        e.preventDefault();
        _deleteId = $(this).data('id');
        $('#deleteInvoiceNumber').text('#' + $(this).data('number'));
        $('#modalDeleteInvoice').modal('show');
    });

    $(document).on('click', '#btnConfirmDelete', function () {
        if (!_deleteId) { return; }
        var $btn = $(this);
        btnLoading($btn, true);

        $.ajax({
            url  : BASE_URL + 'finance/invoices/delete/' + _deleteId,
            type : 'POST',
            data : csrfData(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#modalDeleteInvoice').modal('hide');
                    if (res.redirect) {
                        window.location.href = res.redirect;
                    } else {
                        var $row = $('tr[data-invoice-id="' + _deleteId + '"]');
                        $row.fadeOut(300, function () { $row.remove(); });
                    }
                } else {
                    showAlert('danger', res.message || 'Delete failed.');
                }
            },
            error: function () { showAlert('danger', 'Server error. Please try again.'); },
            complete: function () { btnLoading($btn, false); }
        });
    });

    // ── Send Invoice ────────────────────────────────────────────────
    $(document).on('click', '.btn-send-invoice', function (e) {
        e.preventDefault();
        var id     = $(this).data('id');
        var num    = $(this).data('number');
        var email  = $(this).data('email') || '';
        $('#sendInvoiceId').val(id);
        $('#sendInvoiceNumberLabel').text('#' + num);
        $('#sendInvoiceEmail').val(email).removeClass('is-invalid');
        $('#sendInvoiceSubject').val('Invoice #' + num + ' from ' + (window.companyName || ''));
        $('#sendInvoiceMessage').val('');
        $('#modalSendInvoice').modal('show');
    });

    $(document).on('click', '#btnSendInvoice', function () {
        var $email = $('#sendInvoiceEmail');
        if (!$email.val() || !/\S+@\S+\.\S+/.test($email.val())) {
            $email.addClass('is-invalid');
            return;
        }
        $email.removeClass('is-invalid');

        var id   = $('#sendInvoiceId').val();
        var $btn = $(this);
        btnLoading($btn, true);

        $.ajax({
            url  : BASE_URL + 'finance/invoices/send/' + id,
            type : 'POST',
            data : $.extend(csrfData(), {
                email  : $email.val(),
                subject: $('#sendInvoiceSubject').val(),
                message: $('#sendInvoiceMessage').val(),
            }),
            dataType: 'json',
            success: function (res) {
                $('#modalSendInvoice').modal('hide');
                if (res.success) {
                    showAlert('success', res.message);
                    // Update status badge inline
                    $('tr[data-invoice-id="' + id + '"] .inv-status-badge')
                        .removeClass().addClass('badge inv-status-badge badge-sent').text('Sent');
                } else {
                    showAlert('danger', res.message || 'Send failed.');
                }
            },
            error: function () { showAlert('danger', 'Server error. Please try again.'); },
            complete: function () { btnLoading($btn, false); }
        });
    });

    // ── Mark Paid ───────────────────────────────────────────────────
    var _markPaidId = null;

    $(document).on('click', '.btn-mark-paid', function (e) {
        e.preventDefault();
        _markPaidId = $(this).data('id');
        $('#markPaidNumber').text('#' + $(this).data('number'));
        $('#modalMarkPaid').modal('show');
    });

    $(document).on('click', '#btnConfirmMarkPaid', function () {
        if (!_markPaidId) { return; }
        var $btn = $(this);
        btnLoading($btn, true);

        $.ajax({
            url  : BASE_URL + 'finance/invoices/mark_paid/' + _markPaidId,
            type : 'POST',
            data : csrfData(),
            dataType: 'json',
            success: function (res) {
                $('#modalMarkPaid').modal('hide');
                if (res.success) {
                    showAlert('success', res.message);
                    refreshPage();
                } else {
                    showAlert('danger', res.message || 'Update failed.');
                }
            },
            error: function () { showAlert('danger', 'Server error. Please try again.'); },
            complete: function () { btnLoading($btn, false); }
        });
    });

    // ── Record Payment ──────────────────────────────────────────────
    $(document).on('click', '.btn-record-payment', function (e) {
        e.preventDefault();
        var id       = $(this).data('id');
        var num      = $(this).data('number');
        var balance  = parseFloat($(this).data('balance')) || 0;
        var currency = $(this).data('currency') || '';

        $('#paymentInvoiceId').val(id);
        $('#paymentInvoiceNumberLabel').text('#' + num);
        $('#paymentBalanceDisplay').text(currency + ' ' + balance.toFixed(2));
        $('#paymentCurrencySymbol').text(currency || '$');
        $('#paymentAmount').val(balance.toFixed(2)).attr('max', balance);
        $('#paymentDate').val(new Date().toISOString().split('T')[0]);
        $('#paymentMode').val('other');
        $('#paymentReference').val('');
        $('#paymentNotes').val('');
        $('#modalRecordPayment').modal('show');
    });

    $(document).on('click', '#btnSavePayment', function () {
        var amount = parseFloat($('#paymentAmount').val());
        if (!amount || amount <= 0) {
            showAlert('warning', 'Please enter a valid amount.', '#modalRecordPayment .modal-body');
            return;
        }

        var invoiceId = $('#paymentInvoiceId').val();
        var $btn      = $(this);
        btnLoading($btn, true);

        $.ajax({
            url  : BASE_URL + 'finance/invoices/record_payment',
            type : 'POST',
            data : $.extend(csrfData(), {
                invoice_id  : invoiceId,
                amount      : amount,
                payment_date: $('#paymentDate').val(),
                payment_mode: $('#paymentMode').val(),
                reference_no: $('#paymentReference').val(),
                notes       : $('#paymentNotes').val(),
            }),
            dataType: 'json',
            success: function (res) {
                $('#modalRecordPayment').modal('hide');
                if (res.success) {
                    showAlert('success', res.message);
                    refreshPage();
                } else {
                    showAlert('danger', res.message || 'Payment failed.');
                }
            },
            error: function () { showAlert('danger', 'Server error. Please try again.'); },
            complete: function () { btnLoading($btn, false); }
        });
    });

    // ── Quick status change (badge click on index) ──────────────────
    $(document).on('click', '.inv-status-badge[data-id]', function () {
        // Build a tiny inline dropdown
        var $badge  = $(this);
        var id      = $badge.data('id');
        var options = ['draft','sent','viewed','partial','paid','overdue','cancelled'];
        var current = $badge.text().trim().toLowerCase();

        if ($('.status-quick-picker').length) {
            $('.status-quick-picker').remove();
            return;
        }

        var html = '<div class="status-quick-picker dropdown-menu show p-1" style="min-width:130px; font-size:12px">';
        options.forEach(function (s) {
            html += '<a class="dropdown-item px-2 py-1 ' + (s === current ? 'active' : '') + '"'
                 + ' data-id="' + id + '" data-status="' + s + '" href="#">'
                 + s.charAt(0).toUpperCase() + s.slice(1) + '</a>';
        });
        html += '</div>';

        var $picker = $(html).insertAfter($badge);
        $picker.css({ position: 'absolute', zIndex: 1060 });

        $(document).one('click.statuspicker', function (e) {
            if (!$(e.target).closest('.status-quick-picker').length) {
                $picker.remove();
            }
        });
    });

    $(document).on('click', '.status-quick-picker a', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var id     = $(this).data('id');
        var status = $(this).data('status');
        $(this).closest('.status-quick-picker').remove();

        $.ajax({
            url  : BASE_URL + 'finance/invoices/update_status',
            type : 'POST',
            data : $.extend(csrfData(), { id: id, status: status }),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    var $badge = $('tr[data-invoice-id="' + id + '"] .inv-status-badge');
                    $badge.removeClass(function (i, cls) {
                        return (cls.match(/(^|\s)badge-\S+/g) || []).join(' ');
                    }).addClass('badge-' + status).text(status.charAt(0).toUpperCase() + status.slice(1));
                } else {
                    showAlert('danger', res.message || 'Status update failed.');
                }
            }
        });
    });

    /* ================================================================
     * FORM PAGE INIT
     * ============================================================== */

    if ($('#invoiceForm').length) {

        // Add row button
        $(document).on('click', '#btnAddItem', function () { addItemRow(); });

        // Remove row
        $(document).on('click', '.btn-remove-item', function () {
            var $rows = $('#lineItemsBody .line-item-row');
            if ($rows.length <= 1) {
                showAlert('warning', 'At least one line item is required.');
                return;
            }
            $(this).closest('.line-item-row').remove();
            reindexRows();
            recalcSummary();
        });

        // Live recalc on any input change
        $(document).on('input change', '.item-qty, .item-price, .item-discount-val, .item-discount-type, .item-tax', function () {
            recalcSummary();
        });

        $(document).on('input change', '#invoiceDiscount, #invoiceTaxRate', function () {
            recalcSummary();
        });

        // Initial calc on page load (edit mode)
        recalcSummary();

        // Drag-to-reorder via SortableJS (if available)
        if (typeof Sortable !== 'undefined') {
            Sortable.create(document.getElementById('lineItemsBody'), {
                handle   : '.drag-handle',
                animation: 150,
                onEnd    : function () { reindexRows(); recalcSummary(); }
            });
        }
    }

})(jQuery);