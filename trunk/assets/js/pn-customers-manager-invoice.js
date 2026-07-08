/**
 * PN Customers Manager - Public Invoice Page
 *
 * Handles print and phase collapse/expand on the public invoice view.
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

            var discountAmount = subtotal * discountRate / 100;
            var taxAmount      = (subtotal - discountAmount) * taxRate / 100;
            var total          = subtotal - discountAmount + taxAmount;

            $('#pn-cm-invoice-subtotal').text(formatCurrency(subtotal));
            $('#pn-cm-invoice-discount').text(formatCurrency(discountAmount));
            $('#pn-cm-invoice-tax').text(formatCurrency(taxAmount));
            $('#pn-cm-invoice-total').text(formatCurrency(total));
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

    });
})(jQuery);
