/**
 * PN Customers Manager - Invoice Admin JS
 *
 * Handles invoice line-item management in the WordPress admin and
 * inline editing on the public invoice page:
 *   1.  Add item (modal popup)
 *   1b. Add phase
 *   2.  Remove item (admin popup)
 *   3.  Drag-sort items (jQuery UI Sortable)
 *   4.  Send invoice
 *   5.  Copy public URL
 *   6.  Auto-recalculate totals
 *   7.  Duplicate invoice
 *   8.  Delete invoice
 *   9.  Public page: popup edit / delete per row
 *  10.  Phase collapse / expand
 *
 * Unlike budgets, invoices have NO optional item toggles.
 */
(function ($) {

    /* ------------------------------------------------------------------
     * Helpers
     * ----------------------------------------------------------------*/

    function escHtml(str) {
        if (!str && str !== 0) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var isPublicPage = $('body').hasClass('pn-customers-manager-invoice-public');

    /**
     * Format a numeric amount as currency.
     * On the public page uses European format (comma decimal, dot thousands).
     */
    function formatCurrency(amount) {
        var value = parseFloat(amount) || 0;
        var symbol = pnCmInvoiceAdmin.currencySymbol || '';
        var position = pnCmInvoiceAdmin.currencyPosition || 'before';
        var formatted;

        if (isPublicPage) {
            var fixed = Math.abs(value).toFixed(2);
            var parts = fixed.split('.');
            var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            formatted = intPart + ',' + parts[1];
            if (value < 0) { formatted = '-' + formatted; }
        } else {
            formatted = value.toFixed(2);
        }

        return position === 'after' ? formatted + ' ' + symbol : symbol + formatted;
    }

    /**
     * Format a number in European style (comma decimal, dot thousands)
     * without currency symbol. Used for the qty column on the public page.
     */
    function formatNumber(val) {
        var v = parseFloat(val) || 0;
        var fixed = v.toFixed(2);
        var parts = fixed.split('.');
        var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return intPart + ',' + parts[1];
    }

    /**
     * Find the invoice ID from the nearest context.
     */
    function getInvoiceId($el) {
        return $el.closest('[data-invoice-id]').data('invoice-id')
            || $el.closest('.pn-customers-manager-invoice-items-editor').data('pn_cm_invoice-id')
            || $el.closest('[data-pn_cm_invoice-id]').data('pn_cm_invoice-id')
            || (typeof pnCmInvoiceAdmin !== 'undefined' ? pnCmInvoiceAdmin.invoiceId : 0)
            || 0;
    }

    /**
     * Get the items tbody element (different selectors for admin vs public).
     */
    function getItemsTbody() {
        if (isPublicPage) {
            return $('#pn-cm-invoice-items-body');
        }
        return $('.pn-customers-manager-invoice-items-body');
    }

    /**
     * Flatten the DOM order into an array of {id, position} for AJAX reorder.
     */
    function flattenOrder() {
        var order = [];
        var pos = 0;
        $('#pn-cm-invoice-items-body').children().each(function () {
            if ($(this).hasClass('pn-cm-invoice-phase-group')) {
                var $phase = $(this).find('.pn-cm-invoice-item-phase');
                order.push({ id: $phase.data('item-id'), position: pos++ });
                $(this).find('.pn-cm-invoice-phase-items > .pn-cm-invoice-item-row').each(function () {
                    order.push({ id: $(this).data('item-id'), position: pos++ });
                });
            } else {
                order.push({ id: $(this).data('item-id'), position: pos++ });
            }
        });
        return order;
    }

    /**
     * Send reorder to server.
     */
    function saveOrder() {
        var order = flattenOrder();
        var invoiceId = $('#pn-cm-invoice-items-body').closest('[data-invoice-id]').data('invoice-id') || 0;
        $.post(pnCmInvoiceAdmin.ajaxUrl, {
            action: 'pn_customers_manager_ajax',
            pn_customers_manager_ajax_type: 'pn_cm_invoice_reorder_items',
            pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
            invoice_id: invoiceId,
            order: JSON.stringify(order)
        });
    }

    /* ------------------------------------------------------------------
     * Build table rows
     * ----------------------------------------------------------------*/

    function buildItemRow(item) {
        if (item.item_type === 'phase') {
            return buildPhaseRow(item);
        }

        var total = (parseFloat(item.quantity) * parseFloat(item.unit_price)) || 0;

        if (isPublicPage) {
            return '<div class="pn-cm-invoice-item-row"'
                + ' data-item-id="' + escHtml(item.id) + '"'
                + ' data-item-type="' + escHtml(item.item_type) + '"'
                + ' data-item-description="' + escHtml(item.description) + '"'
                + ' data-item-quantity="' + parseFloat(item.quantity).toFixed(2) + '"'
                + ' data-item-unit-price="' + parseFloat(item.unit_price).toFixed(2) + '"'
                + ' data-item-total="' + total.toFixed(2) + '">'
                + '<span class="pn-cm-invoice-col-drag"><i class="material-icons-outlined pn-cm-invoice-drag-handle">drag_indicator</i></span>'
                + '<div class="pn-cm-invoice-item-content">'
                + '<div class="pn-cm-invoice-item-title">'
                + '<span class="pn-cm-invoice-col-desc">' + escHtml(item.description) + '</span>'
                + '<span class="pn-cm-invoice-col-actions">'
                + '<a href="#" class="pn-cm-invoice-row-edit" title="Edit"><i class="material-icons-outlined">edit</i></a>'
                + '<a href="#" class="pn-cm-invoice-row-delete" title="Delete"><i class="material-icons-outlined">delete</i></a>'
                + '</span></div>'
                + '<div class="pn-cm-invoice-item-meta">'
                + '<span class="pn-cm-invoice-col-type"><i class="material-icons-outlined">' + (item.item_type === 'hours' ? 'schedule' : 'payments') + '</i>' + '</span>'
                + '<span class="pn-cm-invoice-col-qty">' + formatNumber(item.quantity) + '</span>'
                + '<span class="pn-cm-invoice-col-price">' + formatCurrency(item.unit_price) + '</span>'
                + '<span class="pn-cm-invoice-col-total">' + formatCurrency(total) + '</span>'
                + '</div></div></div>';
        }

        // Admin popup row.
        return '<tr class="pn-customers-manager-invoice-item-row" data-item-id="' + escHtml(item.id) + '">'
            + '<td class="pn-customers-manager-invoice-col-drag">'
            + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
            + '<input type="hidden" name="pn_cm_invoice_item_id[]" value="' + escHtml(item.id) + '" />'
            + '</td>'
            + '<td class="pn-customers-manager-invoice-col-description">'
            + '<input type="text" name="pn_cm_invoice_item_description[]" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="' + escHtml(item.description) + '" />'
            + '</td>'
            + '<td class="pn-customers-manager-invoice-col-type">'
            + '<select name="pn_cm_invoice_item_type[]" class="pn-customers-manager-select">'
            + '<option value="hours"' + (item.item_type === 'hours' ? ' selected' : '') + '>Hours</option>'
            + '<option value="fixed"' + (item.item_type === 'fixed' ? ' selected' : '') + '>Fixed</option>'
            + '</select>'
            + '</td>'
            + '<td class="pn-customers-manager-invoice-col-quantity">'
            + '<input type="number" name="pn_cm_invoice_item_quantity[]" class="pn-customers-manager-input pn-customers-manager-invoice-item-quantity" value="' + parseFloat(item.quantity).toFixed(2) + '" step="0.01" min="0" />'
            + '</td>'
            + '<td class="pn-customers-manager-invoice-col-price">'
            + '<input type="number" name="pn_cm_invoice_item_unit_price[]" class="pn-customers-manager-input pn-customers-manager-invoice-item-unit-price" value="' + parseFloat(item.unit_price).toFixed(2) + '" step="0.01" min="0" />'
            + '</td>'
            + '<td class="pn-customers-manager-invoice-col-total">'
            + '<span class="pn-customers-manager-invoice-item-line-total">' + formatCurrency(total) + '</span>'
            + '</td>'
            + '<td class="pn-customers-manager-invoice-col-actions">'
            + '<a href="#" class="pn-customers-manager-invoice-item-delete pn-customers-manager-text-decoration-none">'
            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i></a>'
            + '</td>'
            + '</tr>';
    }

    function buildPhaseRow(item) {
        if (isPublicPage) {
            return '<div class="pn-cm-invoice-phase-group" data-phase-id="' + escHtml(item.id) + '">'
                + '<div class="pn-cm-invoice-item-row pn-cm-invoice-item-phase"'
                + ' data-item-id="' + escHtml(item.id) + '"'
                + ' data-item-type="phase"'
                + ' data-item-description="' + escHtml(item.description) + '">'
                + '<span class="pn-cm-invoice-col-drag"><i class="material-icons-outlined pn-cm-invoice-drag-handle">drag_indicator</i></span>'
                + '<i class="material-icons-outlined pn-cm-invoice-phase-toggle">expand_more</i>'
                + '<div class="pn-cm-invoice-item-content">'
                + '<span class="pn-cm-invoice-col-phase">' + escHtml(item.description) + '</span>'
                + '<span class="pn-cm-invoice-col-actions">'
                + '<a href="#" class="pn-cm-invoice-row-edit" title="Edit"><i class="material-icons-outlined">edit</i></a>'
                + '<a href="#" class="pn-cm-invoice-row-delete" title="Delete"><i class="material-icons-outlined">delete</i></a>'
                + '</span></div></div>'
                + '<div class="pn-cm-invoice-phase-items"></div>'
                + '</div>';
        }

        return '<tr class="pn-customers-manager-invoice-item-row pn-customers-manager-invoice-item-phase" data-item-id="' + escHtml(item.id) + '">'
            + '<td class="pn-customers-manager-invoice-col-drag">'
            + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
            + '<input type="hidden" name="pn_cm_invoice_item_id[]" value="' + escHtml(item.id) + '" />'
            + '<input type="hidden" name="pn_cm_invoice_item_type[]" value="phase" />'
            + '<input type="hidden" name="pn_cm_invoice_item_quantity[]" value="0" />'
            + '<input type="hidden" name="pn_cm_invoice_item_unit_price[]" value="0" />'
            + '</td>'
            + '<td colspan="5" class="pn-customers-manager-invoice-col-description">'
            + '<input type="text" name="pn_cm_invoice_item_description[]" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-invoice-phase-input" value="' + escHtml(item.description) + '" />'
            + '</td>'
            + '<td class="pn-customers-manager-invoice-col-actions">'
            + '<a href="#" class="pn-customers-manager-invoice-item-delete pn-customers-manager-text-decoration-none">'
            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i></a>'
            + '</td>'
            + '</tr>';
    }

    /* ------------------------------------------------------------------
     * Recalculate totals
     * ----------------------------------------------------------------*/

    /**
     * Check if the current invoice type is expense.
     */
    function isExpenseType() {
        return $('#pn_cm_invoice_type').val() === 'expense';
    }

    function recalculateTotals() {
        if (isPublicPage) {
            recalculatePublicTotals();
            return;
        }

        // Expense: use items if they exist, otherwise use the editable subtotal field.
        if (isExpenseType()) {
            var subtotal = 0;
            var hasItems = false;
            $('.pn-customers-manager-invoice-items-body .pn-customers-manager-invoice-item-row').each(function () {
                var $row = $(this);
                if ($row.hasClass('pn-customers-manager-invoice-item-phase')) return;
                var qty = parseFloat($row.find('.pn-customers-manager-invoice-item-quantity').val()) || 0;
                var price = parseFloat($row.find('.pn-customers-manager-invoice-item-unit-price').val()) || 0;
                var lineTotal = qty * price;
                $row.find('.pn-customers-manager-invoice-item-line-total').text(formatCurrency(lineTotal));
                subtotal += lineTotal;
                hasItems = true;
            });
            if (!hasItems) {
                subtotal = parseFloat($('#pn_cm_invoice_expense_subtotal').val()) || 0;
            } else {
                // Sync expense_subtotal field with computed value.
                $('#pn_cm_invoice_expense_subtotal').val(subtotal.toFixed(2));
            }
            var taxRate = parseFloat($('#pn_cm_invoice_tax_rate').val()) || 0;
            var irpfRate = parseFloat($('#pn_cm_invoice_irpf_rate').val()) || 0;
            var tax = subtotal * (taxRate / 100);
            var irpf = subtotal * (irpfRate / 100);
            var grandTotal = subtotal + tax - irpf;

            $('#pn-cm-invoice-admin-subtotal').text(formatCurrency(subtotal));
            if ($('#pn-cm-invoice-admin-discount').length) {
                $('#pn-cm-invoice-admin-discount').text(formatCurrency(0));
            }
            if ($('#pn-cm-invoice-admin-tax').length) {
                $('#pn-cm-invoice-admin-tax').text(formatCurrency(tax));
            }
            if ($('#pn-cm-invoice-admin-irpf').length) {
                $('#pn-cm-invoice-admin-irpf').text('-' + formatCurrency(irpf));
            }
            $('#pn-cm-invoice-admin-total').text(formatCurrency(grandTotal));
            return;
        }

        var subtotal = 0;

        $('.pn-customers-manager-invoice-items-body .pn-customers-manager-invoice-item-row').each(function () {
            var $row = $(this);
            if ($row.hasClass('pn-customers-manager-invoice-item-phase')) return;

            var qty = parseFloat($row.find('.pn-customers-manager-invoice-item-quantity').val()) || 0;
            var price = parseFloat($row.find('.pn-customers-manager-invoice-item-unit-price').val()) || 0;
            var lineTotal = qty * price;

            $row.find('.pn-customers-manager-invoice-item-line-total').text(formatCurrency(lineTotal));
            subtotal += lineTotal;
        });

        var taxRate = parseFloat($('#pn_cm_invoice_tax_rate').val()) || 0;
        var discountRate = parseFloat($('#pn_cm_invoice_discount_rate').val()) || 0;
        var irpfRate = parseFloat($('#pn_cm_invoice_irpf_rate').val()) || 0;

        var discount = subtotal * (discountRate / 100);
        var afterDiscount = subtotal - discount;
        var tax = afterDiscount * (taxRate / 100);
        var irpf = afterDiscount * (irpfRate / 100);
        var grandTotal = afterDiscount + tax - irpf;

        $('#pn-cm-invoice-admin-subtotal').text(formatCurrency(subtotal));
        if ($('#pn-cm-invoice-admin-discount').length) {
            $('#pn-cm-invoice-admin-discount').text(discountRate > 0 ? '-' + formatCurrency(discount) : formatCurrency(0));
        }
        if ($('#pn-cm-invoice-admin-tax').length) {
            $('#pn-cm-invoice-admin-tax').text(formatCurrency(tax));
        }
        if ($('#pn-cm-invoice-admin-irpf').length) {
            $('#pn-cm-invoice-admin-irpf').text('-' + formatCurrency(irpf));
        }
        $('#pn-cm-invoice-admin-total').text(formatCurrency(grandTotal));
    }

    /**
     * Recalculate totals on the public page reading from data-item-total attributes.
     * All items are summed — no optional/deselected filtering.
     */
    function recalculatePublicTotals() {
        var subtotal = 0;

        $('#pn-cm-invoice-items-body .pn-cm-invoice-item-row').each(function () {
            if ($(this).hasClass('pn-cm-invoice-item-phase')) return;
            subtotal += parseFloat($(this).attr('data-item-total')) || 0;
        });

        var taxRate = parseFloat($('#pn_cm_invoice_tax_rate').val()) || 0;
        var discountRate = parseFloat($('#pn_cm_invoice_discount_rate').val()) || 0;
        var irpfRate = parseFloat($('#pn_cm_invoice_irpf_rate').val()) || 0;
        var discount = subtotal * (discountRate / 100);
        var afterDiscount = subtotal - discount;
        var tax = afterDiscount * (taxRate / 100);
        var irpf = afterDiscount * (irpfRate / 100);
        var total = afterDiscount + tax - irpf;

        $('#pn-cm-invoice-subtotal').text(formatCurrency(subtotal));
        if ($('#pn-cm-invoice-discount').length) {
            $('#pn-cm-invoice-discount').text('-' + formatCurrency(discount));
        }
        if ($('#pn-cm-invoice-tax').length) {
            $('#pn-cm-invoice-tax').text(formatCurrency(tax));
        }
        if ($('#pn-cm-invoice-irpf').length) {
            $('#pn-cm-invoice-irpf').text('-' + formatCurrency(irpf));
        }
        $('#pn-cm-invoice-total').text(formatCurrency(total));
    }

    /**
     * Initialize sortable on phase-items containers.
     */
    function initPhaseItemsSortable($container) {
        if (!$container || !$container.length || !$.fn.sortable) return;
        $container.sortable({
            handle: '.pn-cm-invoice-col-drag',
            items: '> .pn-cm-invoice-item-row',
            connectWith: '.pn-cm-invoice-phase-items, #pn-cm-invoice-items-body',
            placeholder: 'pn-cm-invoice-sortable-placeholder',
            cursor: 'grabbing',
            update: function () {
                saveOrder();
            }
        });
    }

    /* ------------------------------------------------------------------
     * Document ready
     * ----------------------------------------------------------------*/

    $(document).ready(function () {


if (typeof pnCmInvoiceAdmin === 'undefined') return;

        /* ==============================================================
         * 1. Add item – modal popup
         * ============================================================*/

        // Helper: create a popup shell with loading spinner.
        function createPopupShell() {
            var $popup = $('<div class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">'
                + '<div class="pn-customers-manager-popup-overlay"></div>'
                + '<div class="pn-customers-manager-popup-content">'
                + '<div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>'
                + '</div></div>');

            $('body').append($popup);

            // Close on overlay click.
            $popup.find('.pn-customers-manager-popup-overlay').on('click', function () {
                closePopup($popup);
            });

            // Open via plugin Popups system.
            if (typeof pn_customers_manager_Popups !== 'undefined') {
                pn_customers_manager_Popups.open($popup, {
                    afterClose: function () { $popup.remove(); }
                });
            }

            return $popup;
        }

        // Helper: close & remove a popup.
        function closePopup($popup) {
            if (typeof pn_customers_manager_Popups !== 'undefined') {
                pn_customers_manager_Popups.close();
            } else {
                $popup.remove();
            }
        }

        // Helper: inject close button into popup content.
        function addCloseButton($popup) {
            var $content = $popup.find('.pn-customers-manager-popup-content');
            var $closeBtn = $('<button class="pn-customers-manager-popup-close-wrapper" type="button"><i class="material-icons-outlined">close</i></button>');
            $closeBtn.on('click', function (ev) {
                ev.preventDefault();
                closePopup($popup);
            });
            $content.prepend($closeBtn);
        }

        /* --- Add item: open popup, load form via AJAX --- */
        $(document).on('click', '.pn-customers-manager-invoice-add-item', function (e) {
            e.preventDefault();

            var invoiceId = getInvoiceId($(this));
            var $popup = createPopupShell();

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_add_item_form',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId
            }, function (response) {
                if (response.error_key === '' && response.html) {
                    $popup.find('.pn-customers-manager-popup-content').html(response.html);
                    addCloseButton($popup);
                } else {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                    closePopup($popup);
                }
            }).fail(function () {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                closePopup($popup);
            });
        });

        // Save new item from popup.
        $(document).on('click', '.pn-cm-invoice-popup-add-save', function (e) {
            e.preventDefault();

            var $wrapper = $(this).closest('.pn-cm-invoice-edit-item-popup');
            var invoiceId = $wrapper.data('invoice-id');
            var $popup = $(this).closest('.pn-customers-manager-popup');

            var type = $wrapper.find('#pn_cm_item_type').val();
            var description = $.trim($wrapper.find('#pn_cm_item_description').val());
            var quantity = $wrapper.find('#pn_cm_item_quantity').val();
            var unitPrice = $wrapper.find('#pn_cm_item_unit_price').val();

            if (!description) {
                alert(pnCmInvoiceAdmin.i18n.noDescription || 'Please enter a description.');
                return;
            }

            var $btn = $(this);
            $btn.addClass('pn-customers-manager-disabled');

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_add_item',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                item_type: type,
                description: description,
                quantity: quantity,
                unit_price: unitPrice
            }, function (response) {
                $btn.removeClass('pn-customers-manager-disabled');

                if (response.error_key === '' && response.item) {
                    if (isPublicPage) {
                        // Insert into the last phase group if one exists, else append loose.
                        var $lastGroup = $('#pn-cm-invoice-items-body > .pn-cm-invoice-phase-group:last');
                        if ($lastGroup.length) {
                            $lastGroup.find('.pn-cm-invoice-phase-items').append(buildItemRow(response.item));
                        } else {
                            getItemsTbody().append(buildItemRow(response.item));
                        }
                    } else {
                        getItemsTbody().append(buildItemRow(response.item));
                    }
                    recalculateTotals();
                    if (isPublicPage) saveOrder();
                    closePopup($popup);
                } else {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                $btn.removeClass('pn-customers-manager-disabled');
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ==============================================================
         * 1b. Add phase – no modal, creates directly
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-invoice-add-phase', function (e) {
            e.preventDefault();

            var invoiceId = getInvoiceId($(this));
            var $btn = $(this);
            $btn.addClass('pn-customers-manager-disabled');

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_add_item',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                item_type: 'phase',
                description: pnCmInvoiceAdmin.i18n.newPhase || 'New phase',
                quantity: 0,
                unit_price: 0
            }, function (response) {
                $btn.removeClass('pn-customers-manager-disabled');

                if (response.error_key === '' && response.item) {
                    if (isPublicPage) {
                        var $html = $(buildPhaseRow(response.item));
                        getItemsTbody().append($html);
                        initPhaseItemsSortable($html.find('.pn-cm-invoice-phase-items'));
                    } else {
                        getItemsTbody().append(buildPhaseRow(response.item));
                    }
                } else {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                $btn.removeClass('pn-customers-manager-disabled');
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ==============================================================
         * 2. Remove item (admin popup)
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-invoice-item-delete', function (e) {
            e.preventDefault();

            if (!confirm(pnCmInvoiceAdmin.i18n.confirmDelete || 'Are you sure you want to delete this item?')) {
                return;
            }

            var $link = $(this);
            var $row = $link.closest('.pn-cm-invoice-item-row');
            var itemId = $row.data('item-id');
            var invoiceId = getInvoiceId($link);

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_remove_item',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                item_id: itemId
            }, function (response) {
                if (response.error_key === '') {
                    $row.fadeOut(300, function () {
                        $(this).remove();
                        recalculateTotals();
                    });
                } else {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ==============================================================
         * 3. Drag-sort items (jQuery UI Sortable)
         * ============================================================*/

        if ($.fn.sortable) {
            // Admin popup sortable.
            $('.pn-customers-manager-invoice-items-body').sortable({
                handle: '.pn-customers-manager-invoice-col-drag',
                placeholder: 'pn-cm-invoice-sortable-placeholder',
                cursor: 'grabbing',
                update: function () {
                    var invoiceId = $(this).closest('.pn-customers-manager-invoice-items-editor').data('pn_cm_invoice-id') || 0;
                    var order = [];
                    $(this).find('tr').each(function (index) {
                        order.push({
                            id: $(this).data('item-id'),
                            position: index
                        });
                    });

                    $.post(pnCmInvoiceAdmin.ajaxUrl, {
                        action: 'pn_customers_manager_ajax',
                        pn_customers_manager_ajax_type: 'pn_cm_invoice_reorder_items',
                        pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                        invoice_id: invoiceId,
                        order: JSON.stringify(order)
                    });
                }
            });

            // Public page sortable: outer container (phase groups + loose items).
            if (isPublicPage) {
                $('#pn-cm-invoice-items-body').sortable({
                    handle: '> .pn-cm-invoice-item-row > .pn-cm-invoice-col-drag, > .pn-cm-invoice-phase-group > .pn-cm-invoice-item-phase > .pn-cm-invoice-col-drag',
                    items: '> .pn-cm-invoice-item-row, > .pn-cm-invoice-phase-group',
                    placeholder: 'pn-cm-invoice-sortable-placeholder',
                    cursor: 'grabbing',
                    tolerance: 'pointer',
                    update: function () {
                        saveOrder();
                    }
                });

                // Inner sortable on each .pn-cm-invoice-phase-items.
                $('.pn-cm-invoice-phase-items').each(function () {
                    initPhaseItemsSortable($(this));
                });
            }
        }

        /* ==============================================================
         * 4. Send invoice
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-pn_cm_invoice-send-post', function (e) {
            e.preventDefault();

            if (!confirm(pnCmInvoiceAdmin.i18n.confirmSend || 'Are you sure you want to send this invoice?')) {
                return;
            }

            var $btn = $(this);
            var invoiceId = $btn.data('pn_cm_invoice-id') || $btn.closest('[data-pn_cm_invoice-id]').data('pn_cm_invoice-id') || 0;
            $btn.prop('disabled', true);

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_send',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId
            }, function (response) {
                $btn.prop('disabled', false);

                if (response.error_key === '') {
                    alert(pnCmInvoiceAdmin.i18n.invoiceSent || 'Invoice sent successfully.');
                    location.reload();
                } else {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                $btn.prop('disabled', false);
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ==============================================================
         * 5. Copy public URL
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-btn-copy', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var url = $btn.data('pn-customers-manager-copy-text') || $btn.siblings('input').val() || $btn.prev('input').val();

            if (!url) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    showCopiedTooltip($btn);
                });
            } else {
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(url).select();
                document.execCommand('copy');
                $temp.remove();
                showCopiedTooltip($btn);
            }
        });

        function showCopiedTooltip($el) {
            var $tooltip = $('<span class="pn-cm-invoice-copied-tooltip" style="'
                + 'position:absolute;background:#333;color:#fff;padding:4px 10px;'
                + 'border-radius:4px;font-size:12px;white-space:nowrap;z-index:100200;'
                + 'pointer-events:none;">Copied!</span>');

            $('body').append($tooltip);

            var offset = $el.offset();
            $tooltip.css({
                top: offset.top - $tooltip.outerHeight() - 6,
                left: offset.left + ($el.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
            });

            setTimeout(function () {
                $tooltip.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 1200);
        }

        /* ==============================================================
         * 6. Auto-recalculate when inputs change (admin popup)
         * ============================================================*/

        $(document).on('input change', '#pn_cm_invoice_tax_rate, #pn_cm_invoice_discount_rate, #pn_cm_invoice_irpf_rate, #pn_cm_invoice_expense_subtotal, .pn-customers-manager-invoice-item-quantity, .pn-customers-manager-invoice-item-unit-price', function () {
            recalculateTotals();
        });

        /* ==============================================================
         * 7. Duplicate invoice
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-pn_cm_invoice-duplicate-post', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var invoiceId = $('.pn-customers-manager-menu-more.pn-customers-manager-active').closest('[data-pn_cm_invoice-id]').attr('data-pn_cm_invoice-id') ||
                $(this).closest('[data-pn_cm_invoice-id]').attr('data-pn_cm_invoice-id') || 0;
            if (!invoiceId) return;

            $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');
            $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_duplicate',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                pn_cm_invoice_id: invoiceId
            }, function (response) {
                if (response.error_key === '' && response.html) {
                    $('.pn-customers-manager-pn_cm_invoice-list-wrapper').html(response.html);
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmInvoiceAdmin.i18n.invoiceDuplicated || 'Invoice duplicated successfully.');
                    }
                } else {
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                    }
                }
            }, 'json').fail(function () {
                if (typeof pn_customers_manager_get_main_message === 'function') {
                    pn_customers_manager_get_main_message(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            });
        });

        /* ==============================================================
         * 8. Delete invoice
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-pn_cm_invoice-remove', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var invoiceId = $('.pn-customers-manager-menu-more.pn-customers-manager-active').closest('[data-pn_cm_invoice-id]').attr('data-pn_cm_invoice-id') ||
                $('.pn-customers-manager-list-more.pn-customers-manager-active').closest('[data-pn_cm_invoice-id]').attr('data-pn_cm_invoice-id') || 0;
            if (!invoiceId) return;

            $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');
            $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');

            var $listContainer = $('.pn-customers-manager-pn_cm_invoice-list-wrapper');
            $listContainer.fadeOut('fast');

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_remove',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                pn_cm_invoice_id: invoiceId
            }, function (response) {
                if (response.error_key === '' && response.html) {
                    $listContainer.html(response.html).fadeIn('slow');
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmInvoiceAdmin.i18n.invoiceRemoved || 'Invoice removed successfully.');
                    }
                } else {
                    $listContainer.fadeIn('fast');
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                    }
                }

                if (typeof pn_customers_manager_Popups !== 'undefined' && typeof pn_customers_manager_Popups.close === 'function') {
                    pn_customers_manager_Popups.close();
                }
            }, 'json').fail(function () {
                $listContainer.fadeIn('fast');
                if (typeof pn_customers_manager_get_main_message === 'function') {
                    pn_customers_manager_get_main_message(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
                if (typeof pn_customers_manager_Popups !== 'undefined' && typeof pn_customers_manager_Popups.close === 'function') {
                    pn_customers_manager_Popups.close();
                }
            });
        });

        /* ==============================================================
         * 9. Public page: popup editing per row + delete
         * ============================================================*/

        if (isPublicPage) {

            // --- Edit button: open popup with form loaded via AJAX ---
            $(document).on('click', '.pn-cm-invoice-row-edit', function (e) {
                e.preventDefault();
                var $row = $(this).closest('.pn-cm-invoice-item-row');
                var itemId = $row.data('item-id');
                var invoiceId = getInvoiceId($row);

                var $popup = createPopupShell();

                $.post(pnCmInvoiceAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_invoice_edit_item_form',
                    pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                    invoice_id: invoiceId,
                    item_id: itemId
                }, function (response) {
                    if (response.error_key === '' && response.html) {
                        $popup.find('.pn-customers-manager-popup-content').html(response.html);
                        addCloseButton($popup);
                    } else {
                        alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                        closePopup($popup);
                    }
                }).fail(function () {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                    closePopup($popup);
                });
            });

            // --- Save button inside edit popup ---
            $(document).on('click', '.pn-cm-invoice-popup-save', function (e) {
                e.preventDefault();
                var $wrapper = $(this).closest('.pn-cm-invoice-edit-item-popup');
                var itemId = $wrapper.data('item-id');
                var invoiceId = $wrapper.data('invoice-id');
                var isPhase = $wrapper.data('item-type') === 'phase';
                var $popup = $(this).closest('.pn-customers-manager-popup');

                var postData = {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_invoice_update_item',
                    pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                    invoice_id: invoiceId,
                    item_id: itemId
                };

                if (isPhase) {
                    postData.description = $wrapper.find('#pn_cm_item_description').val();
                    postData.item_type = 'phase';
                    postData.quantity = 0;
                    postData.unit_price = 0;
                } else {
                    postData.description = $wrapper.find('#pn_cm_item_description').val();
                    postData.item_type = $wrapper.find('#pn_cm_item_type').val();
                    postData.quantity = $wrapper.find('#pn_cm_item_quantity').val();
                    postData.unit_price = $wrapper.find('#pn_cm_item_unit_price').val();
                }

                var $btn = $(this);
                $btn.addClass('pn-customers-manager-disabled');

                $.post(pnCmInvoiceAdmin.ajaxUrl, postData, function (response) {
                    $btn.removeClass('pn-customers-manager-disabled');

                    if (response.error_key === '' && response.item) {
                        var item = response.item;
                        var total = (parseFloat(item.quantity) * parseFloat(item.unit_price)) || 0;
                        var $row = $('#pn-cm-invoice-items-body [data-item-id="' + itemId + '"]');

                        // Update data attributes.
                        $row.attr('data-item-description', item.description);
                        $row.attr('data-item-type', item.item_type);

                        if (isPhase) {
                            $row.find('.pn-cm-invoice-col-phase').text(item.description);
                        } else {
                            $row.attr('data-item-quantity', parseFloat(item.quantity).toFixed(2));
                            $row.attr('data-item-unit-price', parseFloat(item.unit_price).toFixed(2));
                            $row.attr('data-item-total', total.toFixed(2));

                            $row.find('.pn-cm-invoice-col-desc').text(item.description);
                            $row.find('.pn-cm-invoice-col-type').html('<i class="material-icons-outlined">' + (item.item_type === 'hours' ? 'schedule' : 'payments') + '</i>');
                            $row.find('.pn-cm-invoice-col-qty').text(formatNumber(item.quantity));
                            $row.find('.pn-cm-invoice-col-price').text(formatCurrency(item.unit_price));
                            $row.find('.pn-cm-invoice-col-total').text(formatCurrency(total));
                        }

                        // Update totals from server response.
                        if (response.totals) {
                            $('#pn-cm-invoice-subtotal').text(formatCurrency(response.totals.subtotal));
                            if ($('#pn-cm-invoice-discount').length) {
                                $('#pn-cm-invoice-discount').text('-' + formatCurrency(response.totals.discount_amount));
                            }
                            if ($('#pn-cm-invoice-tax').length) {
                                $('#pn-cm-invoice-tax').text(formatCurrency(response.totals.tax_amount));
                            }
                            if ($('#pn-cm-invoice-irpf').length) {
                                $('#pn-cm-invoice-irpf').text('-' + formatCurrency(response.totals.irpf_amount));
                            }
                            $('#pn-cm-invoice-total').text(formatCurrency(response.totals.total));
                        }

                        closePopup($popup);
                    } else {
                        alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                    }
                }).fail(function () {
                    $btn.removeClass('pn-customers-manager-disabled');
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                });
            });

            // --- Delete button (public page) ---
            $(document).on('click', '.pn-cm-invoice-row-delete', function (e) {
                e.preventDefault();

                if (!confirm(pnCmInvoiceAdmin.i18n.confirmDelete || 'Delete this item?')) return;

                var $row = $(this).closest('.pn-cm-invoice-item-row');
                var itemId = $row.data('item-id');
                var invoiceId = getInvoiceId($row);
                var isPhase = $row.hasClass('pn-cm-invoice-item-phase');
                var $group = isPhase ? $row.closest('.pn-cm-invoice-phase-group') : null;

                $.post(pnCmInvoiceAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_invoice_remove_item',
                    pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                    invoice_id: invoiceId,
                    item_id: itemId
                }, function (response) {
                    if (response.error_key === '') {
                        if (isPhase && $group) {
                            // When deleting a phase, move its items out as loose items.
                            var $phaseItems = $group.find('.pn-cm-invoice-phase-items > .pn-cm-invoice-item-row');
                            $phaseItems.insertBefore($group);
                            $group.fadeOut(300, function () {
                                $(this).remove();
                                recalculatePublicTotals();
                                saveOrder();
                            });
                        } else {
                            $row.fadeOut(300, function () {
                                $(this).remove();
                                recalculatePublicTotals();
                            });
                        }
                    } else {
                        alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                    }
                }).fail(function () {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                });
            });

            /* ==============================================================
             * 10. Phase collapse / expand
             * ============================================================*/

            $(document).on('click', '.pn-cm-invoice-phase-toggle', function () {
                var $group = $(this).closest('.pn-cm-invoice-phase-group');
                $group.toggleClass('pn-cm-invoice-collapsed');
                // Icon rotated via CSS transform when collapsed.
            });

        } // end isPublicPage

        // Initial calculation on page load (admin popup only — public totals are server-rendered).
        if (!isPublicPage) {
            recalculateTotals();
        }

        // ── Payments: add ──
        $(document).on('click', '.pn-cm-payments-container[data-type="invoice"] .pn-cm-payment-add-btn, .pn-cm-invoice-payment-add-btn', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.pn-cm-payments-container, .pn-cm-invoice-payments');
            var invoiceId = $container.data('post-id') || pnCmInvoiceAdmin.invoiceId;
            var $form = $(this).closest('.pn-cm-payments-add-form, .pn-cm-invoice-payments-add-form');
            var date = $form.find('.pn-cm-payment-date').val();
            var concept = $form.find('.pn-cm-payment-concept').val() || '';
            var type = $form.find('.pn-cm-payment-type').val() || 'income';
            var category = $form.find('.pn-cm-payment-category').val() || 'client_payment';
            var recipient = $form.find('.pn-cm-payment-recipient').val() || '';
            var amount = $form.find('.pn-cm-payment-amount').val();
            var method = $form.find('.pn-cm-payment-method').val();
            var notes = $form.find('.pn-cm-payment-notes').val() || '';
            var visible = $form.find('.pn-cm-payment-visible').is(':checked') ? 1 : 0;

            if (!amount || parseFloat(amount) <= 0) {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                return;
            }

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_add_payment',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                payment_date: date,
                payment_concept: concept,
                payment_type: type,
                payment_category: category,
                payment_recipient: recipient,
                payment_amount: amount,
                payment_method: method,
                payment_notes: notes,
                payment_visible: visible
            }, function (response) {
                if (response && response.error_key === '') {
                    if (response.html) {
                        $container.replaceWith(response.html);
                    } else {
                        location.reload();
                    }
                } else {
                    alert((response && response.error_content) || pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        // ── Payments: remove ──
        $(document).on('click', '.pn-cm-payments-container[data-type="invoice"] .pn-cm-payment-remove, .pn-cm-invoice-payment-remove', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.pn-cm-payments-container, .pn-cm-invoice-payments');
            var invoiceId = $container.data('post-id') || pnCmInvoiceAdmin.invoiceId;
            var paymentId = $(this).data('payment-id');

            if (!confirm(pnCmInvoiceAdmin.i18n.confirmDelete || 'Are you sure?')) {
                return;
            }

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_remove_payment',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                payment_id: paymentId
            }, function (response) {
                if (response && response.error_key === '') {
                    if (response.html) {
                        $container.replaceWith(response.html);
                    } else {
                        location.reload();
                    }
                } else {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        // ── Payments: toggle visibility ──
        $(document).on('click', '.pn-cm-payments-container[data-type="invoice"] .pn-cm-payment-toggle-visibility', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.pn-cm-payments-container');
            var invoiceId = $container.data('post-id') || pnCmInvoiceAdmin.invoiceId;
            var paymentId = $(this).data('payment-id');

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_toggle_payment_visibility',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                payment_id: paymentId
            }, function (response) {
                if (response && response.error_key === '') {
                    if (response.html) {
                        $container.replaceWith(response.html);
                    } else {
                        location.reload();
                    }
                } else {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        // ── Payments: edit (inline) ──
        $(document).on('click', '.pn-cm-payments-container[data-type="invoice"] .pn-cm-payment-edit, .pn-cm-invoice-payments .pn-cm-payment-edit', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            if ($row.hasClass('pn-cm-payment-editing')) return;
            $row.addClass('pn-cm-payment-editing');

            var d = $row.data();
            $row.data('original-html', $row.html());

            var editHtml = '<td><input type="date" class="pn-cm-payment-edit-date" value="' + (d.date || '') + '" /></td>' +
                '<td><input type="text" class="pn-cm-payment-edit-concept" value="' + $('<span>').text(d.concept || '').html() + '" /></td>' +
                '<td><select class="pn-cm-payment-edit-type"><option value="income"' + (d.type === 'income' ? ' selected' : '') + '>Income</option><option value="expense"' + (d.type === 'expense' ? ' selected' : '') + '>Expense</option></select></td>' +
                '<td><select class="pn-cm-payment-edit-category">' +
                    '<option value="client_payment"' + (d.category === 'client_payment' ? ' selected' : '') + '>Client payment</option>' +
                    '<option value="worker"' + (d.category === 'worker' ? ' selected' : '') + '>Worker</option>' +
                    '<option value="material"' + (d.category === 'material' ? ' selected' : '') + '>Material</option>' +
                    '<option value="supply"' + (d.category === 'supply' ? ' selected' : '') + '>Supply</option>' +
                    '<option value="other"' + (d.category === 'other' ? ' selected' : '') + '>Other</option>' +
                '</select></td>' +
                '<td><input type="text" class="pn-cm-payment-edit-recipient" value="' + $('<span>').text(d.recipient || '').html() + '" /></td>' +
                '<td><input type="number" class="pn-cm-payment-edit-amount" step="0.01" min="0" value="' + (d.amount || 0) + '" /></td>' +
                '<td><select class="pn-cm-payment-edit-method">' +
                    '<option value="cash"' + (d.method === 'cash' ? ' selected' : '') + '>Cash</option>' +
                    '<option value="bank_transfer"' + (d.method === 'bank_transfer' ? ' selected' : '') + '>Bank transfer</option>' +
                    '<option value="card"' + (d.method === 'card' ? ' selected' : '') + '>Card</option>' +
                    '<option value="paypal"' + (d.method === 'paypal' ? ' selected' : '') + '>PayPal</option>' +
                    '<option value="bizum"' + (d.method === 'bizum' ? ' selected' : '') + '>Bizum</option>' +
                    '<option value="other"' + (d.method === 'other' ? ' selected' : '') + '>Other</option>' +
                '</select></td>' +
                '<td><input type="text" class="pn-cm-payment-edit-notes" value="' + $('<span>').text(d.notes || '').html() + '" /></td>' +
                '<td class="pn-cm-payment-td-actions"><label class="pn-cm-payment-switch"><input type="checkbox" class="pn-cm-payment-edit-visible"' + (parseInt(d.visible) ? ' checked' : '') + ' /><span class="pn-cm-payment-switch-slider"></span></label></td>' +
                '<td class="pn-cm-payment-td-actions"><a href="#" class="pn-cm-payment-edit-save" title="Save"><i class="material-icons-outlined">check</i></a></td>' +
                '<td class="pn-cm-payment-td-actions"><a href="#" class="pn-cm-payment-edit-cancel" title="Cancel"><i class="material-icons-outlined">close</i></a></td>';

            $row.html(editHtml);
        });

        // ── Payments: edit cancel ──
        $(document).on('click', '.pn-cm-payments-container[data-type="invoice"] .pn-cm-payment-edit-cancel, .pn-cm-invoice-payments .pn-cm-payment-edit-cancel', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            $row.html($row.data('original-html'));
            $row.removeClass('pn-cm-payment-editing');
        });

        // ── Payments: edit save ──
        $(document).on('click', '.pn-cm-payments-container[data-type="invoice"] .pn-cm-payment-edit-save, .pn-cm-invoice-payments .pn-cm-payment-edit-save', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            var $container = $row.closest('.pn-cm-payments-container, .pn-cm-invoice-payments');
            var invoiceId = $container.data('post-id') || pnCmInvoiceAdmin.invoiceId;
            var paymentId = $row.data('payment-id');

            var amount = $row.find('.pn-cm-payment-edit-amount').val();
            if (!amount || parseFloat(amount) <= 0) {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                return;
            }

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_edit_payment',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                payment_id: paymentId,
                payment_date: $row.find('.pn-cm-payment-edit-date').val(),
                payment_concept: $row.find('.pn-cm-payment-edit-concept').val() || '',
                payment_type: $row.find('.pn-cm-payment-edit-type').val() || 'income',
                payment_category: $row.find('.pn-cm-payment-edit-category').val() || 'client_payment',
                payment_recipient: $row.find('.pn-cm-payment-edit-recipient').val() || '',
                payment_amount: amount,
                payment_method: $row.find('.pn-cm-payment-edit-method').val(),
                payment_notes: $row.find('.pn-cm-payment-edit-notes').val() || '',
                payment_visible: $row.find('.pn-cm-payment-edit-visible').is(':checked') ? 1 : 0
            }, function (response) {
                if (response && response.error_key === '') {
                    if (response.html) {
                        $container.replaceWith(response.html);
                    } else {
                        location.reload();
                    }
                } else {
                    alert((response && response.error_content) || pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        // ── Payments: toggle collapse ──
        $(document).on('click', '.pn-cm-payments-header', function () {
            $(this).closest('.pn-cm-payments-container, .pn-cm-budget-payments, .pn-cm-invoice-payments').toggleClass('pn-cm-payments-collapsed');
        });

        // ── Payments: init charts ──
        function initPaymentCharts($container) {
            if (typeof Chart === 'undefined') return;
            var income = parseFloat($container.attr('data-total-income')) || 0;
            var expense = parseFloat($container.attr('data-total-expense')) || 0;
            var expCats = {};
            var catLabels = {};
            try { expCats = JSON.parse($container.attr('data-expense-categories') || '{}'); } catch(e) {}
            try { catLabels = JSON.parse($container.attr('data-category-labels') || '{}'); } catch(e) {}

            var balanceCanvas = $container.find('.pn-cm-payments-chart-balance')[0];
            if (balanceCanvas && (income > 0 || expense > 0)) {
                new Chart(balanceCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Income', 'Expenses'],
                        datasets: [{
                            data: [income, expense],
                            backgroundColor: ['#28a745', '#dc3545'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '65%',
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } }
                        }
                    }
                });
            } else if (balanceCanvas) {
                $(balanceCanvas).closest('.pn-cm-payments-chart-box').hide();
            }

            var catCanvas = $container.find('.pn-cm-payments-chart-categories')[0];
            var catKeys = Object.keys(expCats);
            if (catCanvas && catKeys.length > 0) {
                var catColors = {
                    client_payment: '#4e79a7', worker: '#f28e2b', material: '#e15759',
                    supply: '#76b7b2', other: '#999'
                };
                var catData = [];
                var catNames = [];
                var catBg = [];
                var defaultColors = ['#59a14f', '#edc948', '#b07aa1', '#ff9da7', '#9c755f'];
                var ci = 0;
                catKeys.forEach(function (k) {
                    if (expCats[k] > 0) {
                        catData.push(expCats[k]);
                        catNames.push(catLabels[k] || k);
                        catBg.push(catColors[k] || defaultColors[ci++ % defaultColors.length]);
                    }
                });
                if (catData.length > 0) {
                    new Chart(catCanvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: catNames,
                            datasets: [{
                                data: catData,
                                backgroundColor: catBg,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            cutout: '65%',
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } }
                            }
                        }
                    });
                } else {
                    $(catCanvas).closest('.pn-cm-payments-chart-box').hide();
                }
            } else if (catCanvas) {
                $(catCanvas).closest('.pn-cm-payments-chart-box').hide();
            }
        }

        $('.pn-cm-payments-container, .pn-cm-invoice-payments').each(function () {
            initPaymentCharts($(this));
        });

        /* ==============================================================
         * Export items (CSV download)
         * ============================================================*/
        $(document).on('click', '.pn-cm-invoice-export-items', function (e) {
            e.preventDefault();
            var invoiceId = getInvoiceId($(this)) || $(this).data('invoice-id');
            if (!invoiceId) return;

            var $form = $('<form>', { method: 'POST', action: pnCmInvoiceAdmin.ajaxUrl, css: { display: 'none' } });
            $form.append($('<input>', { type: 'hidden', name: 'action', value: 'pn_customers_manager_ajax' }));
            $form.append($('<input>', { type: 'hidden', name: 'pn_customers_manager_ajax_type', value: 'pn_cm_invoice_export_items' }));
            $form.append($('<input>', { type: 'hidden', name: 'pn_customers_manager_ajax_nonce', value: pnCmInvoiceAdmin.nonce }));
            $form.append($('<input>', { type: 'hidden', name: 'invoice_id', value: invoiceId }));
            $('body').append($form);
            $form.submit();
            $form.remove();
        });

        /* ==============================================================
         * Import items (CSV upload with drag-and-drop popup)
         * ============================================================*/
        $(document).on('click', '.pn-cm-invoice-import-items', function (e) {
            e.preventDefault();
            var invoiceId = getInvoiceId($(this)) || $(this).data('invoice-id');
            if (!invoiceId) return;

            var $popup = $('<div class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">'
                + '<div class="pn-customers-manager-popup-overlay"></div>'
                + '<div class="pn-customers-manager-popup-content">'
                + '<div class="pn-cm-budget-import-popup-inner">'
                + '<h3>' + (pnCmInvoiceAdmin.i18n.importItemsTitle || 'Import items from CSV') + '</h3>'
                + '<div class="pn-cm-budget-import-dropzone" id="pn-cm-invoice-import-dropzone">'
                + '<i class="material-icons-outlined" style="font-size:48px;color:#999;">upload_file</i>'
                + '<p>' + (pnCmInvoiceAdmin.i18n.importDropText || 'Drag & drop a CSV file here or click to select') + '</p>'
                + '</div>'
                + '<input type="file" accept=".csv,text/csv" class="pn-cm-invoice-import-file" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);opacity:0;" />'
                + '<div class="pn-cm-invoice-import-status" style="display:none;"></div>'
                + '</div>'
                + '</div></div>');

            $('body').append($popup);

            if (typeof pn_customers_manager_Popups !== 'undefined') {
                pn_customers_manager_Popups.open($popup, {
                    afterClose: function () { $popup.remove(); }
                });
            } else {
                $popup.removeClass('pn-customers-manager-display-none-soft').css('display', 'block');
            }

            var $dropzone = $popup.find('#pn-cm-invoice-import-dropzone');
            var $fileInput = $popup.find('.pn-cm-invoice-import-file');
            var $status = $popup.find('.pn-cm-invoice-import-status');

            $popup.find('.pn-customers-manager-popup-overlay').on('click', function () { closePopup($popup); });

            var $closeBtn = $('<button class="pn-customers-manager-popup-close-wrapper" type="button"><i class="material-icons-outlined">close</i></button>');
            $closeBtn.on('click', function (ev) { ev.preventDefault(); closePopup($popup); });
            $popup.find('.pn-customers-manager-popup-content').prepend($closeBtn);

            $dropzone.on('click', function () { $fileInput[0].click(); });
            $dropzone.on('dragover dragenter', function (ev) {
                ev.preventDefault(); ev.stopPropagation();
                $(this).addClass('pn-cm-budget-import-dropzone-active');
            }).on('dragleave drop', function (ev) {
                ev.preventDefault(); ev.stopPropagation();
                $(this).removeClass('pn-cm-budget-import-dropzone-active');
            }).on('drop', function (ev) {
                var files = ev.originalEvent.dataTransfer.files;
                if (files.length) handleInvoiceImportFile(files[0], invoiceId, $popup, $status);
            });

            $fileInput.on('change', function () {
                if (this.files.length) handleInvoiceImportFile(this.files[0], invoiceId, $popup, $status);
            });
        });

        function handleInvoiceImportFile(file, invoiceId, $popup, $status) {
            if (!file.name.toLowerCase().endsWith('.csv')) {
                $status.show().html('<p style="color:#d32f2f;">' + (pnCmInvoiceAdmin.i18n.importInvalidFile || 'Please select a valid CSV file.') + '</p>');
                return;
            }

            var $dropzone = $popup.find('#pn-cm-invoice-import-dropzone');
            $dropzone.html('<div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>');

            var formData = new FormData();
            formData.append('action', 'pn_customers_manager_ajax');
            formData.append('pn_customers_manager_ajax_type', 'pn_cm_invoice_import_items');
            formData.append('pn_customers_manager_ajax_nonce', pnCmInvoiceAdmin.nonce);
            formData.append('invoice_id', invoiceId);
            formData.append('csv_file', file);

            $.ajax({
                url: pnCmInvoiceAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.error_key === '' && response.items) {
                        var $tbody = getItemsTbody();
                        $.each(response.items, function (i, item) {
                            var rowHtml = buildItemRow(item);
                            if (isPublicPage) {
                                if (item.item_type === 'phase') {
                                    var $phase = $(rowHtml);
                                    $tbody.append($phase);
                                } else {
                                    var $existing = $tbody.children('.pn-cm-invoice-phase-group:last');
                                    if ($existing.length) {
                                        $existing.find('.pn-cm-invoice-phase-items').append(rowHtml);
                                    } else {
                                        $tbody.append(rowHtml);
                                    }
                                }
                            } else {
                                $tbody.append(rowHtml);
                            }
                        });
                        recalculateTotals();
                        if (isPublicPage) saveOrder();

                        $dropzone.html('<i class="material-icons-outlined" style="font-size:48px;color:#4caf50;">check_circle</i>'
                            + '<p>' + (response.count || 0) + ' ' + (pnCmInvoiceAdmin.i18n.importSuccess || 'items imported successfully.') + '</p>');
                        setTimeout(function () { closePopup($popup); }, 1500);
                    } else {
                        $status.show().html('<p style="color:#d32f2f;">' + (response.error_content || pnCmInvoiceAdmin.i18n.error || 'An error occurred.') + '</p>');
                        $dropzone.html('<i class="material-icons-outlined" style="font-size:48px;color:#999;">upload_file</i>'
                            + '<p>' + (pnCmInvoiceAdmin.i18n.importDropText || 'Drag & drop a CSV file here or click to select') + '</p>');
                    }
                },
                error: function () {
                    $status.show().html('<p style="color:#d32f2f;">' + (pnCmInvoiceAdmin.i18n.error || 'An error occurred.') + '</p>');
                    $dropzone.html('<i class="material-icons-outlined" style="font-size:48px;color:#999;">upload_file</i>'
                        + '<p>' + (pnCmInvoiceAdmin.i18n.importDropText || 'Drag & drop a CSV file here or click to select') + '</p>');
                }
            });
        }

        /* ==============================================================
         * Bulk select & delete
         * ============================================================*/
        var lastCheckedIndex = null;

        $(document).on('click', '.pn-cm-invoice-bulk-select-toggle', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var entering = !$btn.hasClass('active');
            var $container = $('#pn-cm-invoice-items-body').closest('.pn-cm-invoice-items-section');
            var $adminBtns = $btn.closest('.pn-cm-invoice-admin-buttons');

            $btn.toggleClass('active', entering);
            $btn.find('i').text(entering ? 'close' : 'checklist');
            $container.toggleClass('pn-cm-invoice-selecting', entering);
            $adminBtns.toggleClass('pn-cm-invoice-selecting', entering);

            if (entering) {
                $('#pn-cm-invoice-items-body .pn-cm-invoice-item-row').each(function () {
                    if (!$(this).find('.pn-cm-invoice-bulk-checkbox').length) {
                        var $drag = $(this).find('.pn-cm-invoice-col-drag');
                        if ($drag.length) {
                            $drag.before('<span class="pn-cm-invoice-bulk-checkbox"><input type="checkbox" class="pn-cm-invoice-bulk-check"></span>');
                        } else {
                            $(this).prepend('<span class="pn-cm-invoice-bulk-checkbox"><input type="checkbox" class="pn-cm-invoice-bulk-check"></span>');
                        }
                    }
                });
            } else {
                $('.pn-cm-invoice-bulk-checkbox').remove();
                $('.pn-cm-invoice-bulk-checked').removeClass('pn-cm-invoice-bulk-checked');
            }
        });

        // Shift+click range selection.
        $(document).on('click', '.pn-cm-invoice-bulk-check', function (e) {
            var $allChecks = $('.pn-cm-invoice-bulk-check');
            var currentIndex = $allChecks.index(this);

            if (e.shiftKey && lastCheckedIndex !== null && lastCheckedIndex !== currentIndex) {
                var start = Math.min(lastCheckedIndex, currentIndex);
                var end = Math.max(lastCheckedIndex, currentIndex);
                var checked = $(this).is(':checked');
                $allChecks.slice(start, end + 1).each(function () {
                    $(this).prop('checked', checked);
                    $(this).closest('.pn-cm-invoice-item-row').toggleClass('pn-cm-invoice-bulk-checked', checked);
                });
            }

            lastCheckedIndex = currentIndex;
        });

        // Toggle highlight on checkbox change.
        $(document).on('change', '.pn-cm-invoice-bulk-check', function () {
            $(this).closest('.pn-cm-invoice-item-row').toggleClass('pn-cm-invoice-bulk-checked', $(this).is(':checked'));
        });

        // Bulk delete.
        $(document).on('click', '.pn-cm-invoice-bulk-delete', function (e) {
            e.preventDefault();

            var $checked = $('.pn-cm-invoice-bulk-check:checked');
            if (!$checked.length) {
                alert(pnCmInvoiceAdmin.i18n.noItemsSelected || 'No items selected.');
                return;
            }
            if (!confirm(pnCmInvoiceAdmin.i18n.confirmBulkDelete || 'Are you sure you want to delete the selected items?')) {
                return;
            }

            var itemIds = [];
            var $rows = $();
            $checked.each(function () {
                var $row = $(this).closest('.pn-cm-invoice-item-row');
                var id = $row.data('item-id');
                if (id) {
                    itemIds.push(id);
                    if ($row.hasClass('pn-cm-invoice-item-phase')) {
                        $rows = $rows.add($row.closest('.pn-cm-invoice-phase-group'));
                    } else {
                        $rows = $rows.add($row);
                    }
                }
            });

            if (!itemIds.length) return;

            var invoiceId = getInvoiceId($rows.first());

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_bulk_remove_items',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                item_ids: JSON.stringify(itemIds)
            }, function (response) {
                if (response.error_key === '') {
                    $rows.fadeOut(300, function () {
                        $(this).remove();
                        recalculateTotals();
                        if (isPublicPage) saveOrder();
                    });
                    // Exit selection mode.
                    $('.pn-cm-invoice-bulk-select-toggle').trigger('click');
                } else {
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ------------------------------------------------------------------
         * 15. Deliverable files — upload & delete (admin metabox + public page)
         * ----------------------------------------------------------------*/

        // Upload button click → trigger hidden file input.
        $(document).on('click', '.pn-cm-invoice-file-upload-btn, .pn-cm-invoice-file-upload-btn-public', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.pn-cm-invoice-files-admin, .pn-cm-invoice-files-section');
            $container.find('.pn-cm-invoice-file-input, .pn-cm-invoice-file-input-public').trigger('click');
        });

        // File input change → AJAX upload each file.
        $(document).on('change', '.pn-cm-invoice-file-input, .pn-cm-invoice-file-input-public', function () {
            var $input = $(this);
            var files = $input[0].files;
            if (!files.length) return;

            var $container = $input.closest('.pn-cm-invoice-files-admin, .pn-cm-invoice-files-section');
            var invoiceId = $container.data('invoice-id') || pnCmInvoiceAdmin.invoiceId || 0;

            for (var i = 0; i < files.length; i++) {
                (function (file) {
                    var formData = new FormData();
                    formData.append('action', 'pn_customers_manager_ajax');
                    formData.append('pn_customers_manager_ajax_type', 'pn_cm_invoice_upload_file');
                    formData.append('pn_customers_manager_ajax_nonce', pnCmInvoiceAdmin.nonce);
                    formData.append('invoice_id', invoiceId);
                    formData.append('file', file);

                    $.ajax({
                        url: pnCmInvoiceAdmin.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function (response) {
                            if (response && response.error_key === '') {
                                var f = response.file;
                                // Append to admin metabox table.
                                var $adminBody = $container.find('.pn-cm-invoice-files-admin-body');
                                if ($adminBody.length) {
                                    $adminBody.find('.pn-cm-invoice-files-empty-row').remove();
                                    var aiBtn = '';
                                    if (isExpenseType()) {
                                        aiBtn = '<a href="#" class="pn-cm-invoice-ai-analyze pn-customers-manager-text-decoration-none" data-file-id="' + f.id + '" title="Analyze with AI" style="margin-right:8px;">'
                                            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer" style="color:#6c5ce7;font-size:18px;">auto_awesome</i>'
                                            + '</a>';
                                    }
                                    $adminBody.append(
                                        '<tr data-file-id="' + f.id + '">'
                                        + '<td><i class="material-icons-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;">description</i>' + escHtml(f.filename) + '</td>'
                                        + '<td>' + escHtml(f.size_formatted) + '</td>'
                                        + '<td>' + escHtml(f.uploaded_at) + '</td>'
                                        + '<td class="pn-customers-manager-text-align-right">'
                                        + aiBtn
                                        + '<a href="#" class="pn-cm-invoice-file-delete pn-customers-manager-text-decoration-none" data-file-id="' + f.id + '">'
                                        + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer" style="color:#dc3545;">delete</i>'
                                        + '</a></td></tr>'
                                    );
                                }
                                // Append to public page list.
                                var $publicList = $container.find('.pn-cm-invoice-files-list');
                                if ($publicList.length) {
                                    $publicList.append(
                                        '<div class="pn-cm-invoice-file-row" data-file-id="' + f.id + '">'
                                        + '<span class="pn-cm-invoice-file-icon"><i class="material-icons-outlined">description</i></span>'
                                        + '<span class="pn-cm-invoice-file-name">' + escHtml(f.filename) + '</span>'
                                        + '<span class="pn-cm-invoice-file-size">' + escHtml(f.size_formatted) + '</span>'
                                        + '<button type="button" class="pn-cm-invoice-btn pn-cm-invoice-btn-secondary pn-cm-invoice-download-file" data-file-id="' + f.id + '" style="padding:6px 14px;font-size:13px;">'
                                        + '<i class="material-icons-outlined" style="font-size:16px;">download</i> Download'
                                        + '</button>'
                                        + '<a href="#" class="pn-cm-invoice-file-delete-public" data-file-id="' + f.id + '">'
                                        + '<i class="material-icons-outlined" style="font-size:18px;color:#dc3545;vertical-align:middle;">delete</i>'
                                        + '</a>'
                                        + '</div>'
                                    );
                                } else if ($container.hasClass('pn-cm-invoice-files-section')) {
                                    // No list yet, create one.
                                    $container.find('p').first().replaceWith(
                                        '<div class="pn-cm-invoice-files-list">'
                                        + '<div class="pn-cm-invoice-file-row" data-file-id="' + f.id + '">'
                                        + '<span class="pn-cm-invoice-file-icon"><i class="material-icons-outlined">description</i></span>'
                                        + '<span class="pn-cm-invoice-file-name">' + escHtml(f.filename) + '</span>'
                                        + '<span class="pn-cm-invoice-file-size">' + escHtml(f.size_formatted) + '</span>'
                                        + '<button type="button" class="pn-cm-invoice-btn pn-cm-invoice-btn-secondary pn-cm-invoice-download-file" data-file-id="' + f.id + '" style="padding:6px 14px;font-size:13px;">'
                                        + '<i class="material-icons-outlined" style="font-size:16px;">download</i> Download'
                                        + '</button>'
                                        + '<a href="#" class="pn-cm-invoice-file-delete-public" data-file-id="' + f.id + '">'
                                        + '<i class="material-icons-outlined" style="font-size:18px;color:#dc3545;vertical-align:middle;">delete</i>'
                                        + '</a>'
                                        + '</div></div>'
                                    );
                                }
                            } else {
                                alert(response.error_content || pnCmInvoiceAdmin.i18n.error);
                            }
                        },
                        error: function () {
                            alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                        }
                    });
                })(files[i]);
            }

            // Reset the input so the same file can be selected again.
            $input.val('');
        });

        // Delete file — admin metabox.
        $(document).on('click', '.pn-cm-invoice-file-delete', function (e) {
            e.preventDefault();
            var $link = $(this);
            var $row = $link.closest('tr');
            var fileId = $link.data('file-id');
            var $container = $link.closest('.pn-cm-invoice-files-admin');
            var invoiceId = $container.data('invoice-id') || pnCmInvoiceAdmin.invoiceId || 0;

            if (!confirm(pnCmInvoiceAdmin.i18n.confirmDelete || 'Are you sure?')) return;

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_delete_file',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                file_id: fileId
            }, function (response) {
                if (response && response.error_key === '') {
                    $row.fadeOut(300, function () { $(this).remove(); });
                } else {
                    alert(response.error_content || pnCmInvoiceAdmin.i18n.error);
                }
            }, 'json');
        });

        // Delete file — public page.
        $(document).on('click', '.pn-cm-invoice-file-delete-public', function (e) {
            e.preventDefault();
            var $link = $(this);
            var $row = $link.closest('.pn-cm-invoice-file-row');
            var fileId = $link.data('file-id');
            var $container = $link.closest('.pn-cm-invoice-files-section');
            var invoiceId = $container.data('invoice-id') || pnCmInvoiceAdmin.invoiceId || 0;

            if (!confirm(pnCmInvoiceAdmin.i18n.confirmDelete || 'Are you sure?')) return;

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_delete_file',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                file_id: fileId
            }, function (response) {
                if (response && response.error_key === '') {
                    $row.fadeOut(300, function () { $(this).remove(); });
                } else {
                    alert(response.error_content || pnCmInvoiceAdmin.i18n.error);
                }
            }, 'json');
        });

        /* ==============================================================
         * Invoice type filters (All / Income / Expense)
         * ============================================================*/

        $(document).on('click', '.pn-cm-invoice-type-filter', function (e) {
            e.preventDefault();
            var type = $(this).data('type');
            $('.pn-cm-invoice-type-filter').removeClass('active');
            $(this).addClass('active');

            if (type === 'all') {
                $('.pn-customers-manager-pn_cm_invoice-list-item').show();
            } else {
                $('.pn-customers-manager-pn_cm_invoice-list-item').each(function () {
                    var rowType = $(this).data('invoice-type') || 'income';
                    $(this).toggle(rowType === type);
                });
            }
        });

        /* ==============================================================
         * AI Upload Zone — drag-and-drop + click to browse
         * Uses delegated events because the zone is loaded via AJAX popup.
         * ============================================================*/

        // Click on dropzone → open file dialog (stop propagation on file input to avoid loop).
        $(document).on('click', '.pn-cm-ai-upload-file-input', function (e) {
            e.stopPropagation();
        });

        $(document).on('click', '.pn-cm-invoice-ai-upload-dropzone', function (e) {
            var $dz = $(this);
            if ($(e.target).closest('.pn-cm-ai-upload-retry').length) return;
            if ($dz.hasClass('pn-cm-ai-upload-loading') || $dz.hasClass('pn-cm-ai-upload-success')) return;
            $dz.find('.pn-cm-ai-upload-file-input')[0].click();
        });

        $(document).on('change', '.pn-cm-ai-upload-file-input', function () {
            if (this.files && this.files[0]) {
                handleAiUploadFile($(this).closest('.pn-cm-invoice-ai-upload-zone'), this.files[0]);
            }
        });

        // Drag events.
        $(document).on('dragenter dragover', '.pn-cm-invoice-ai-upload-dropzone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('pn-cm-dragover');
        });
        $(document).on('dragleave', '.pn-cm-invoice-ai-upload-dropzone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('pn-cm-dragover');
        });
        $(document).on('drop', '.pn-cm-invoice-ai-upload-dropzone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('pn-cm-dragover');
            var files = e.originalEvent.dataTransfer.files;
            if (files && files[0]) {
                handleAiUploadFile($(this).closest('.pn-cm-invoice-ai-upload-zone'), files[0]);
            }
        });

        // Retry / upload another.
        $(document).on('click', '.pn-cm-ai-upload-retry', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $zone = $(this).closest('.pn-cm-invoice-ai-upload-zone');
            resetAiDropzone($zone);
            $zone.find('.pn-cm-ai-upload-file-input')[0].click();
        });

        function resetAiDropzone($zone) {
            $zone.find('.pn-cm-invoice-ai-upload-dropzone').removeClass('pn-cm-ai-upload-loading pn-cm-ai-upload-success pn-cm-ai-upload-error');
            $zone.find('.pn-cm-ai-upload-file-input').val('');
        }

        function handleAiUploadFile($zone, file) {
            var $dropzone = $zone.find('.pn-cm-invoice-ai-upload-dropzone');
            var $tempInput = $zone.find('.pn-cm-ai-upload-temp-file');

            // Validate type.
            var validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (validTypes.indexOf(file.type) === -1) {
                alert(pnCmInvoiceAdmin.i18n.aiUploadInvalidType || 'Please upload a PDF, JPG, or PNG file.');
                return;
            }

            // Validate size (10 MB).
            if (file.size > 10 * 1024 * 1024) {
                alert(pnCmInvoiceAdmin.i18n.aiUploadTooLarge || 'File is too large. Maximum size is 10 MB.');
                return;
            }

            var invoiceId = $zone.data('invoice-id') || 0;

            // Show loading state.
            $dropzone.removeClass('pn-cm-ai-upload-success pn-cm-ai-upload-error');
            $dropzone.addClass('pn-cm-ai-upload-loading');

            var formData = new FormData();
            formData.append('action', 'pn_customers_manager_ajax');
            formData.append('pn_customers_manager_ajax_type', 'pn_cm_invoice_ai_analyze_upload');
            formData.append('pn_customers_manager_ajax_nonce', pnCmInvoiceAdmin.nonce);
            formData.append('invoice_id', invoiceId);
            formData.append('file', file);

            $.ajax({
                url: pnCmInvoiceAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    $dropzone.removeClass('pn-cm-ai-upload-loading');

                    if (response && response.error_key === '' && response.data) {
                        var d = response.data;

                        // Title: use description (concepts) or fall back to organization name.
                        var titleVal = d.description || d.organization || '';
                        if (titleVal) fillFieldAnimated('#title', titleVal);

                        // Supplier invoice number.
                        if (d.supplier_number) fillFieldAnimated('#pn_cm_invoice_supplier_number', d.supplier_number);

                        if (d.date) fillFieldAnimated('#pn_cm_invoice_date', d.date);
                        if (d.due_date) fillFieldAnimated('#pn_cm_invoice_due_date', d.due_date);
                        if (d.subtotal != null && d.subtotal !== '') fillFieldAnimated('#pn_cm_invoice_expense_subtotal', parseFloat(d.subtotal).toFixed(2));
                        if (d.tax_rate != null && d.tax_rate !== '') fillFieldAnimated('#pn_cm_invoice_tax_rate', parseFloat(d.tax_rate).toFixed(2));
                        if (d.irpf_rate != null && d.irpf_rate !== '') fillFieldAnimated('#pn_cm_invoice_irpf_rate', parseFloat(d.irpf_rate).toFixed(2));
                        if (d.category) fillFieldAnimated('#pn_cm_invoice_category', d.category);
                        if (d.payment_method) fillFieldAnimated('#pn_cm_invoice_payment_method', d.payment_method);

                        // Organization: use server-resolved ID (auto-created if needed).
                        if (d.organization_id) {
                            var $orgSelect = $('#pn_cm_invoice_organization_id');
                            // If org was just created, add it to the dropdown.
                            if (d.organization_created && d.organization) {
                                $orgSelect.append('<option value="' + d.organization_id + '">' + escHtml(d.organization) + '</option>');
                            }
                            $orgSelect.val(d.organization_id);
                            fillFieldAnimated('#pn_cm_invoice_organization_id', d.organization_id);
                        }

                        // Items: add as actual invoice item rows.
                        if (d.items && d.items.length) {
                            addAiItemRows(d.items);
                        }

                        recalculateTotals();

                        // Store temp file reference if invoice not yet saved.
                        if (response.temp_file) {
                            $tempInput.val(response.temp_file);
                        }

                        // If file was attached immediately (existing invoice), add row to files table.
                        if (response.file) {
                            var f = response.file;
                            var $tbody = $('.pn-cm-invoice-files-admin-body');
                            $tbody.find('.pn-cm-invoice-files-empty-row').remove();
                            var aiBtn = '<a href="#" class="pn-cm-invoice-ai-analyze pn-customers-manager-text-decoration-none" data-file-id="' + f.id + '" title="Analyze with AI" style="margin-right:8px;">'
                                + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer" style="color:#6c5ce7;font-size:18px;">auto_awesome</i></a>';
                            var sizeStr = f.size > 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : (f.size / 1024).toFixed(0) + ' KB';
                            var row = '<tr data-file-id="' + f.id + '">'
                                + '<td><i class="material-icons-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;">description</i>' + escHtml(f.filename) + '</td>'
                                + '<td>' + sizeStr + '</td>'
                                + '<td>' + escHtml(f.uploaded_at) + '</td>'
                                + '<td class="pn-customers-manager-text-align-right">' + aiBtn
                                + '<a href="#" class="pn-cm-invoice-file-delete pn-customers-manager-text-decoration-none" data-file-id="' + f.id + '">'
                                + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer" style="color:#dc3545;">delete</i></a></td></tr>';
                            $tbody.append(row);
                        }

                        // Show success state with filename.
                        $dropzone.addClass('pn-cm-ai-upload-success');
                        $dropzone.find('.pn-cm-ai-upload-success-filename').text(file.name);

                        if (typeof pn_customers_manager_get_main_message === 'function') {
                            pn_customers_manager_get_main_message(pnCmInvoiceAdmin.i18n.aiAnalyzed || 'AI analysis completed. Fields have been filled.');
                        }
                    } else {
                        $dropzone.addClass('pn-cm-ai-upload-error');
                        alert((response && response.error_content) || pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                        setTimeout(function () { resetAiDropzone($zone); }, 2000);
                    }
                },
                error: function () {
                    $dropzone.removeClass('pn-cm-ai-upload-loading');
                    $dropzone.addClass('pn-cm-ai-upload-error');
                    alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                    setTimeout(function () { resetAiDropzone($zone); }, 2000);
                }
            });
        }

        /**
         * Add AI-extracted items as actual invoice item rows in the items editor.
         */
        function addAiItemRows(items) {
            var $tbody = $('.pn-customers-manager-invoice-items-body');
            if (!$tbody.length) return;

            // Clear existing rows first (AI is replacing the full item list).
            $tbody.find('.pn-customers-manager-invoice-item-row').remove();

            var nextId = 1;
            for (var i = 0; i < items.length; i++) {
                var it = items[i];
                var concept = it.concept || '';
                var qty = it.quantity || 1;
                var price = it.unit_price || 0;
                var lineTotal = (qty * price).toFixed(2);
                var itemId = nextId++;

                var row = '<tr class="pn-customers-manager-invoice-item-row" data-item-id="' + itemId + '">'
                    + '<td class="pn-customers-manager-invoice-col-drag">'
                    + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
                    + '<input type="hidden" name="pn_cm_invoice_item_id[]" value="' + itemId + '" />'
                    + '</td>'
                    + '<td class="pn-customers-manager-invoice-col-description">'
                    + '<input type="text" name="pn_cm_invoice_item_description[]" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="' + escHtml(concept) + '" />'
                    + '</td>'
                    + '<td class="pn-customers-manager-invoice-col-type">'
                    + '<select name="pn_cm_invoice_item_type[]" class="pn-customers-manager-input">'
                    + '<option value="fixed" selected>Fixed</option>'
                    + '<option value="phase">Phase</option>'
                    + '</select>'
                    + '</td>'
                    + '<td class="pn-customers-manager-invoice-col-quantity">'
                    + '<input type="number" name="pn_cm_invoice_item_quantity[]" class="pn-customers-manager-input pn-customers-manager-invoice-item-quantity" value="' + qty + '" step="any" min="0" />'
                    + '</td>'
                    + '<td class="pn-customers-manager-invoice-col-price">'
                    + '<input type="number" name="pn_cm_invoice_item_unit_price[]" class="pn-customers-manager-input pn-customers-manager-invoice-item-unit-price" value="' + parseFloat(price).toFixed(2) + '" step="any" min="0" />'
                    + '</td>'
                    + '<td class="pn-customers-manager-invoice-col-total">'
                    + '<span class="pn-customers-manager-invoice-item-line-total">' + formatCurrency(parseFloat(lineTotal)) + '</span>'
                    + '</td>'
                    + '<td class="pn-customers-manager-invoice-col-actions">'
                    + '<a href="#" class="pn-cm-invoice-row-delete pn-customers-manager-text-decoration-none" title="Delete">'
                    + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i>'
                    + '</a>'
                    + '</td>'
                    + '</tr>';

                $tbody.append(row);
            }

            // Flash highlight on all new rows.
            $tbody.find('.pn-customers-manager-invoice-item-row').addClass('pn-cm-invoice-ai-field-highlight');
            setTimeout(function () {
                $tbody.find('.pn-customers-manager-invoice-item-row').removeClass('pn-cm-invoice-ai-field-highlight');
            }, 2000);
        }

        function fillFieldAnimated(selector, value) {
            var $el = $(selector);
            if (!$el.length) return;
            $el.val(value);
            $el.removeClass('pn-cm-invoice-ai-field-highlight');
            // Force reflow to restart animation.
            $el[0].offsetWidth;
            $el.addClass('pn-cm-invoice-ai-field-highlight');
            setTimeout(function () {
                $el.removeClass('pn-cm-invoice-ai-field-highlight');
            }, 2000);
        }

        /* ==============================================================
         * AI Analyze file button for expense invoices
         * ============================================================*/

        // After file upload success, append AI analyze button for expense invoices.
        $(document).on('click', '.pn-cm-invoice-ai-analyze', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var fileId = $btn.data('file-id');
            var $container = $btn.closest('.pn-cm-invoice-files-admin');
            var invoiceId = $container.data('invoice-id') || pnCmInvoiceAdmin.invoiceId || 0;

            // Show spinner.
            var $origContent = $btn.html();
            $btn.html('<div class="pn-customers-manager-loader-circle" style="width:18px;height:18px;display:inline-block;"><div></div><div></div><div></div><div></div></div>');
            $btn.addClass('pn-customers-manager-disabled');

            $.post(pnCmInvoiceAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_invoice_ai_analyze_file',
                pn_customers_manager_ajax_nonce: pnCmInvoiceAdmin.nonce,
                invoice_id: invoiceId,
                file_id: fileId
            }, function (response) {
                $btn.html($origContent);
                $btn.removeClass('pn-customers-manager-disabled');

                if (response && response.error_key === '' && response.data) {
                    var d = response.data;

                    // Autofill fields.
                    var titleVal = d.description || d.organization || d.title || '';
                    if (titleVal) $('#title').val(titleVal);
                    if (d.supplier_number) $('#pn_cm_invoice_supplier_number').val(d.supplier_number);
                    if (d.date) $('#pn_cm_invoice_date').val(d.date);
                    if (d.due_date) $('#pn_cm_invoice_due_date').val(d.due_date);
                    if (d.subtotal != null && d.subtotal !== '') $('#pn_cm_invoice_expense_subtotal').val(parseFloat(d.subtotal).toFixed(2));
                    if (d.tax_rate != null && d.tax_rate !== '') $('#pn_cm_invoice_tax_rate').val(parseFloat(d.tax_rate).toFixed(2));
                    if (d.irpf_rate != null && d.irpf_rate !== '') $('#pn_cm_invoice_irpf_rate').val(parseFloat(d.irpf_rate).toFixed(2));
                    if (d.category) $('#pn_cm_invoice_category').val(d.category);
                    if (d.payment_method) $('#pn_cm_invoice_payment_method').val(d.payment_method);

                    // Organization: use server-resolved ID if available.
                    if (d.organization_id) {
                        var $orgSelect = $('#pn_cm_invoice_organization_id');
                        if (d.organization_created && d.organization) {
                            $orgSelect.append('<option value="' + d.organization_id + '">' + escHtml(d.organization) + '</option>');
                        }
                        $orgSelect.val(d.organization_id);
                    } else if (d.organization) {
                        var $orgSelect = $('#pn_cm_invoice_organization_id');
                        $orgSelect.find('option').each(function () {
                            if ($(this).text().trim().toLowerCase() === d.organization.toLowerCase()) {
                                $orgSelect.val($(this).val());
                                return false;
                            }
                        });
                    }

                    // Items: add as actual invoice item rows.
                    if (d.items && d.items.length) {
                        addAiItemRows(d.items);
                    }

                    recalculateTotals();

                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmInvoiceAdmin.i18n.aiAnalyzed || 'AI analysis completed. Fields have been filled.');
                    }
                } else {
                    alert((response && response.error_content) || pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                $btn.html($origContent);
                $btn.removeClass('pn-customers-manager-disabled');
                alert(pnCmInvoiceAdmin.i18n.error || 'An error occurred.');
            });
        });

    }); // end document.ready

})(jQuery);
