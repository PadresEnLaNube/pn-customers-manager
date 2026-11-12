/**
 * PN Customers Manager - Public Invoice Page
 *
 * Handles print, phase collapse/expand, status change, and sticky totals bar
 * on the public invoice view.
 * Unlike budgets, invoices have NO optional item toggles.
 */
(function ($) {
    $(document).ready(function () {

        // -------------------------------------------------------
        // Helpers
        // -------------------------------------------------------

        /**
         * Format a numeric amount as a currency string.
         *
         * Uses comma as decimal separator and dot as thousands
         * separator, then places the currency symbol according
         * to the position setting ('left' or 'right').
         *
         * @param {number} amount
         * @return {string}
         */
        function formatCurrency(amount) {
            var fixed = Math.abs(amount).toFixed(2);
            // Split integer and decimal parts
            var parts = fixed.split('.');
            var intPart = parts[0];
            var decPart = parts[1];

            // Add dot as thousands separator
            intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            // Join with comma as decimal separator
            var formatted = intPart + ',' + decPart;

            // Negative sign
            if (amount < 0) {
                formatted = '-' + formatted;
            }

            // Position currency symbol
            if (pnCmInvoice.currencyPosition === 'after') {
                return formatted + ' ' + pnCmInvoice.currencySymbol;
            }

            return pnCmInvoice.currencySymbol + formatted;
        }

        /**
         * Recalculate and update subtotal, discount, tax and total.
         * All items are always included (no optional toggle filtering).
         */
        function recalculateTotals() {
            var subtotal = 0;

            $('.pn-cm-invoice-item-row').each(function () {
                var $row = $(this);

                // Skip phase rows — they are section headers with no price.
                if ($row.hasClass('pn-cm-invoice-item-phase')) {
                    return;
                }

                var rowTotal = parseFloat($row.data('item-total')) || 0;
                subtotal += rowTotal;
            });

            var discountRate  = parseFloat(pnCmInvoice.discountRate) || 0;
            var taxRate        = parseFloat(pnCmInvoice.taxRate) || 0;
            var irpfRate       = parseFloat(pnCmInvoice.irpfRate) || 0;

            var discountAmount     = subtotal * discountRate / 100;
            var baseAfterDiscount  = subtotal - discountAmount;
            var taxAmount          = baseAfterDiscount * taxRate / 100;
            var irpfAmount         = baseAfterDiscount * irpfRate / 100;
            var total              = baseAfterDiscount + taxAmount - irpfAmount;

            $('#pn-cm-invoice-subtotal').text(formatCurrency(subtotal));
            $('#pn-cm-invoice-discount').text(formatCurrency(discountAmount));
            $('#pn-cm-invoice-tax').text(formatCurrency(taxAmount));
            if ($('#pn-cm-invoice-irpf').length) {
                $('#pn-cm-invoice-irpf').text('-' + formatCurrency(irpfAmount));
            }
            $('#pn-cm-invoice-total').text(formatCurrency(total));

            // Update sticky bar total.
            $('#pn-cm-invoice-sticky-total').text(formatCurrency(total));
        }

        // -------------------------------------------------------
        // 1. Print
        // -------------------------------------------------------

        $(document).on('click', '#pn-cm-invoice-print', function (e) {
            e.preventDefault();
            window.print();
        });

        // -------------------------------------------------------
        // 2. Phase collapse / expand
        // -------------------------------------------------------

        $(document).on('click', '.pn-cm-invoice-phase-toggle', function () {
            // When admin JS is loaded, it handles this event.
            if (typeof pnCmInvoiceAdmin !== 'undefined') return;

            var $group = $(this).closest('.pn-cm-invoice-phase-group');
            $group.toggleClass('pn-cm-invoice-collapsed');
            // Icon rotation handled via CSS transform when collapsed.
        });

        // -------------------------------------------------------
        // 2b. Payments: toggle collapse
        // -------------------------------------------------------

        $(document).on('click', '.pn-cm-payments-header', function () {
            // When admin JS is loaded, it handles this event.
            if (typeof pnCmInvoiceAdmin !== 'undefined') return;

            $(this).closest('.pn-cm-payments-container, .pn-cm-budget-payments, .pn-cm-invoice-payments').toggleClass('pn-cm-payments-collapsed');
        });

        // -------------------------------------------------------
        // 3. Admin status dropdown
        // -------------------------------------------------------

        $(document).on('change', '.pn-cm-invoice-status-select', function () {
            var $select = $(this);
            var newStatus = $select.val();
            var previousStatus = $select.data('current');

            if (newStatus === previousStatus) return;

            $select.prop('disabled', true);

            $.post(pnCmInvoice.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_update_status',
                pn_customers_manager_ajax_nonce: pnCmInvoice.nonce,
                invoice_id: pnCmInvoice.invoiceId,
                status: newStatus
            }, function (response) {
                $select.prop('disabled', false);
                if (response && response.error_key === '') {
                    $select.data('current', newStatus);
                    var $badge = $select.siblings('.pn-cm-invoice-status');
                    $badge.removeClass('pn-cm-invoice-status-draft pn-cm-invoice-status-sent pn-cm-invoice-status-paid pn-cm-invoice-status-cancelled')
                          .addClass('pn-cm-invoice-status-' + newStatus)
                          .text($select.find('option:selected').text());
                } else {
                    $select.val(previousStatus);
                    alert(pnCmInvoice.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                $select.prop('disabled', false);
                $select.val(previousStatus);
                alert(pnCmInvoice.i18n.error || 'An error occurred.');
            });
        });

        // -------------------------------------------------------
        // 4. Sticky totals bar (IntersectionObserver)
        // -------------------------------------------------------

        var $stickyBar = $('#pn-cm-invoice-sticky-totals');
        var $totalsSection = $('.pn-cm-invoice-totals');

        if ($stickyBar.length && $totalsSection.length && 'IntersectionObserver' in window) {
            // Initially hidden.
            $stickyBar.addClass('pn-cm-invoice-sticky-hidden');

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        $stickyBar.addClass('pn-cm-invoice-sticky-hidden');
                    } else {
                        $stickyBar.removeClass('pn-cm-invoice-sticky-hidden');
                    }
                });
            }, { threshold: 0.1 });

            observer.observe($totalsSection[0]);
        }

        // -------------------------------------------------------
        // 5. Deliverable file download
        // -------------------------------------------------------

        $(document).on('click', '.pn-cm-invoice-download-file', function (e) {
            e.preventDefault();
            var fileId = $(this).data('file-id');
            var invoiceId = pnCmInvoice.invoiceId;
            var invoiceToken = pnCmInvoice.invoiceToken;

            // Use a hidden iframe + form to POST the download request.
            // On success the server streams the file (Content-Disposition: attachment).
            // Using a hidden iframe prevents the page from navigating on error.
            var iframeName = 'pn-cm-download-' + Date.now();
            var $iframe = $('<iframe>', { name: iframeName }).css('display', 'none');
            $('body').append($iframe);

            var $form = $('<form>', {
                method: 'POST',
                action: pnCmInvoice.ajaxUrl,
                target: iframeName
            }).css('display', 'none');

            // Determine the correct action and nonce keys depending on login state.
            var isLoggedIn = pnCmInvoice.isAdmin;
            var actionName = isLoggedIn ? 'pn_customers_manager_ajax' : 'pn_customers_manager_ajax_nopriv';
            var typeKey    = isLoggedIn ? 'pn_customers_manager_ajax_type' : 'pn_customers_manager_ajax_nopriv_type';
            var nonceKey   = isLoggedIn ? 'pn_customers_manager_ajax_nonce' : 'pn_customers_manager_ajax_nopriv_nonce';

            $form.append($('<input>', { type: 'hidden', name: 'action', value: actionName }));
            $form.append($('<input>', { type: 'hidden', name: typeKey, value: 'pn_cm_invoice_download_file' }));
            $form.append($('<input>', { type: 'hidden', name: nonceKey, value: pnCmInvoice.nonce }));
            $form.append($('<input>', { type: 'hidden', name: 'invoice_id', value: invoiceId }));
            $form.append($('<input>', { type: 'hidden', name: 'invoice_token', value: invoiceToken }));
            $form.append($('<input>', { type: 'hidden', name: 'file_id', value: fileId }));

            $('body').append($form);
            $form[0].submit();
            $form.remove();
            // Clean up iframe after a reasonable delay.
            setTimeout(function () { $iframe.remove(); }, 60000);
        });

    });
})(jQuery);
