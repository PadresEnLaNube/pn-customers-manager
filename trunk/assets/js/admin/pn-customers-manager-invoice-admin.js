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

    function recalculateTotals() {
        if (isPublicPage) {
            recalculatePublicTotals();
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

        var discount = subtotal * (discountRate / 100);
        var afterDiscount = subtotal - discount;
        var tax = afterDiscount * (taxRate / 100);
        var grandTotal = afterDiscount + tax;

        $('.pn-customers-manager-invoice-totals .pn-customers-manager-invoice-totals-value').each(function (index) {
            switch (index) {
                case 0: $(this).text(formatCurrency(subtotal)); break;
                case 1: $(this).text(discountRate > 0 ? '-' + formatCurrency(discount) : formatCurrency(0)); break;
                case 2: $(this).text(formatCurrency(tax)); break;
            }
        });
        $('.pn-customers-manager-invoice-total-row .pn-customers-manager-invoice-totals-value').text(formatCurrency(grandTotal));
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
        var discount = subtotal * (discountRate / 100);
        var afterDiscount = subtotal - discount;
        var tax = afterDiscount * (taxRate / 100);
        var total = afterDiscount + tax;

        $('#pn-cm-invoice-subtotal').text(formatCurrency(subtotal));
        if ($('#pn-cm-invoice-discount').length) {
            $('#pn-cm-invoice-discount').text('-' + formatCurrency(discount));
        }
        if ($('#pn-cm-invoice-tax').length) {
            $('#pn-cm-invoice-tax').text(formatCurrency(tax));
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
                + '<div class="pn-customers-manager-p-30 pn-customers-manager-text-align-center">'
                + '<i class="material-icons-outlined" style="font-size:30px;color:#999;">hourglass_empty</i>'
                + '</div></div></div>');

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

        $(document).on('input change', '#pn_cm_invoice_tax_rate, #pn_cm_invoice_discount_rate, .pn-customers-manager-invoice-item-quantity, .pn-customers-manager-invoice-item-unit-price', function () {
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
            }).fail(function () {
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
            }).fail(function () {
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

    }); // end document.ready

})(jQuery);
