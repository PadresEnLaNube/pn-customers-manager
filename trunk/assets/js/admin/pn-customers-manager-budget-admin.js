/**
 * PN Customers Manager - Budget Admin JS
 *
 * Handles budget line-item management in the WordPress admin and
 * inline editing on the public budget page:
 *   1.  Add item (modal popup)
 *   1b. Add phase
 *   2.  Remove item (admin popup)
 *   3.  Drag-sort items (jQuery UI Sortable)
 *   4.  Send budget
 *   5.  Copy public URL
 *   6.  Auto-recalculate totals
 *   7.  Toggle optional checkbox
 *   8.  Duplicate budget
 *   9.  Delete budget
 *  10.  Public page: popup edit / delete per row
 *  11.  Phase collapse / expand
 *  12.  Phase toggle (activate/deactivate all items)
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

    var isPublicPage = $('body').hasClass('pn-customers-manager-budget-public');

    /**
     * Format a numeric amount as currency.
     * On the public page uses European format (comma decimal, dot thousands).
     */
    function formatCurrency(amount) {
        var value = parseFloat(amount) || 0;
        var symbol = pnCmBudgetAdmin.currencySymbol || '';
        var position = pnCmBudgetAdmin.currencyPosition || 'before';
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
     * Find the budget ID from the nearest context.
     */
    function getBudgetId($el) {
        return $el.closest('[data-budget-id]').data('budget-id')
            || $el.closest('.pn-customers-manager-budget-items-editor').data('pn_cm_budget-id')
            || $el.closest('[data-pn_cm_budget-id]').data('pn_cm_budget-id')
            || (typeof pnCmBudgetAdmin !== 'undefined' ? pnCmBudgetAdmin.budgetId : 0)
            || 0;
    }

    /**
     * Get the items tbody element (different selectors for admin vs public).
     */
    function getItemsTbody() {
        if (isPublicPage) {
            return $('#pn-cm-budget-items-body');
        }
        return $('.pn-customers-manager-budget-items-body');
    }

    /**
     * Flatten the DOM order into an array of {id, position} for AJAX reorder.
     */
    function flattenOrder() {
        var order = [];
        var pos = 0;
        $('#pn-cm-budget-items-body').children().each(function () {
            if ($(this).hasClass('pn-cm-budget-phase-group')) {
                var $phase = $(this).find('.pn-cm-budget-item-phase');
                order.push({ id: $phase.data('item-id'), position: pos++ });
                $(this).find('.pn-cm-budget-phase-items > .pn-cm-budget-item-row').each(function () {
                    order.push({ id: $(this).data('item-id'), position: pos++ });
                });
            } else {
                order.push({ id: $(this).data('item-id'), position: pos++ });
            }
        });
        // Include gallery images.
        $('#pn-cm-budget-images-gallery > .pn-cm-budget-item-row').each(function () {
            order.push({ id: $(this).data('item-id'), position: pos++ });
        });
        return order;
    }

    /**
     * Send reorder to server.
     */
    function saveOrder() {
        var order = flattenOrder();
        var budgetId = $('#pn-cm-budget-items-body').closest('[data-budget-id]').data('budget-id') || 0;
        $.post(pnCmBudgetAdmin.ajaxUrl, {
            action: 'pn_customers_manager_ajax',
            pn_customers_manager_ajax_type: 'pn_cm_budget_reorder_items',
            pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
            budget_id: budgetId,
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
        if (item.item_type === 'image') {
            return buildImageRow(item);
        }

        var total = (parseFloat(item.quantity) * parseFloat(item.unit_price)) || 0;

        if (isPublicPage) {
            var hasOptional = !!pnCmBudgetAdmin.hasOptionalItems;
            var toggleHtml = '';

            if (hasOptional && parseInt(item.is_optional, 10)) {
                toggleHtml = '<span class="pn-cm-budget-col-toggle">'
                    + '<label class="pn-cm-budget-toggle">'
                    + '<input type="checkbox" class="pn-cm-budget-toggle-item" data-item-id="' + escHtml(item.id) + '" checked>'
                    + '<span class="pn-cm-budget-toggle-slider"></span>'
                    + '</label></span>';
            } else if (hasOptional) {
                toggleHtml = '<span class="pn-cm-budget-col-toggle"></span>';
            }

            return '<div class="pn-cm-budget-item-row"'
                + ' data-item-id="' + escHtml(item.id) + '"'
                + ' data-item-type="' + escHtml(item.item_type) + '"'
                + ' data-item-description="' + escHtml(item.description) + '"'
                + ' data-item-quantity="' + parseFloat(item.quantity).toFixed(2) + '"'
                + ' data-item-unit-price="' + parseFloat(item.unit_price).toFixed(2) + '"'
                + ' data-item-optional="' + (parseInt(item.is_optional, 10) ? '1' : '0') + '"'
                + ' data-item-total="' + total.toFixed(2) + '">'
                + '<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle" title="Drag to reorder">drag_indicator</i></span>'
                + '<div class="pn-cm-budget-item-content">'
                + '<div class="pn-cm-budget-item-title">'
                + '<span class="pn-cm-budget-col-desc">' + escHtml(item.description) + '</span>'
                + toggleHtml
                + '<span class="pn-cm-budget-col-actions">'
                + '<a href="#" class="pn-cm-budget-row-edit" title="Edit"><i class="material-icons-outlined">edit</i></a>'
                + '<a href="#" class="pn-cm-budget-row-delete" title="Delete"><i class="material-icons-outlined">delete</i></a>'
                + '</span></div>'
                + '<div class="pn-cm-budget-item-meta">'
                + '<span class="pn-cm-budget-col-type"><i class="material-icons-outlined" title="' + (item.item_type === 'hours' ? 'Hourly rate' : 'Fixed price') + '">' + (item.item_type === 'hours' ? 'schedule' : 'payments') + '</i>' + '</span>'
                + '<span class="pn-cm-budget-col-qty">' + formatNumber(item.quantity) + '</span>'
                + '<span class="pn-cm-budget-col-price">' + formatCurrency(item.unit_price) + '</span>'
                + '<span class="pn-cm-budget-col-total">' + formatCurrency(total) + '</span>'
                + '</div></div></div>';
        }

        // Admin popup row.
        return '<tr class="pn-customers-manager-budget-item-row" data-item-id="' + escHtml(item.id) + '">'
            + '<td class="pn-customers-manager-budget-col-drag">'
            + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
            + '<input type="hidden" name="pn_cm_budget_item_id[]" value="' + escHtml(item.id) + '" />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-description">'
            + '<input type="text" name="pn_cm_budget_item_description[]" class="pn-customers-manager-input pn-customers-manager-width-100-percent" value="' + escHtml(item.description) + '" />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-type">'
            + '<select name="pn_cm_budget_item_type[]" class="pn-customers-manager-select">'
            + '<option value="hours"' + (item.item_type === 'hours' ? ' selected' : '') + '>Hours</option>'
            + '<option value="fixed"' + (item.item_type === 'fixed' ? ' selected' : '') + '>Fixed</option>'
            + '</select>'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-quantity">'
            + '<input type="number" name="pn_cm_budget_item_quantity[]" class="pn-customers-manager-input pn-customers-manager-budget-item-quantity" value="' + parseFloat(item.quantity).toFixed(2) + '" step="0.01" min="0" />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-price">'
            + '<input type="number" name="pn_cm_budget_item_unit_price[]" class="pn-customers-manager-input pn-customers-manager-budget-item-unit-price" value="' + parseFloat(item.unit_price).toFixed(2) + '" step="0.01" min="0" />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-total">'
            + '<span class="pn-customers-manager-budget-item-line-total">' + formatCurrency(total) + '</span>'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-optional">'
            + '<input type="hidden" name="pn_cm_budget_item_is_optional[]" value="' + (parseInt(item.is_optional, 10) ? '1' : '0') + '" />'
            + '<input type="checkbox" class="pn-customers-manager-budget-item-optional-toggle"' + (parseInt(item.is_optional, 10) ? ' checked' : '') + ' />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-actions">'
            + '<a href="#" class="pn-customers-manager-budget-item-delete pn-customers-manager-text-decoration-none">'
            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i></a>'
            + '</td>'
            + '</tr>';
    }

    function buildPhaseRow(item) {
        if (isPublicPage) {
            return '<div class="pn-cm-budget-phase-group" data-phase-id="' + escHtml(item.id) + '">'
                + '<div class="pn-cm-budget-item-row pn-cm-budget-item-phase"'
                + ' data-item-id="' + escHtml(item.id) + '"'
                + ' data-item-type="phase"'
                + ' data-item-description="' + escHtml(item.description) + '">'
                + '<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle" title="Drag to reorder">drag_indicator</i></span>'
                + '<i class="material-icons-outlined pn-cm-budget-phase-toggle" title="Collapse / Expand">expand_more</i>'
                + '<div class="pn-cm-budget-item-content">'
                + '<span class="pn-cm-budget-col-phase">' + escHtml(item.description) + '</span>'
                + '<span class="pn-cm-budget-phase-total"></span>'
                + '<span class="pn-cm-budget-col-actions">'
                + '<a href="#" class="pn-cm-budget-row-edit" title="Edit"><i class="material-icons-outlined">edit</i></a>'
                + '<a href="#" class="pn-cm-budget-row-delete" title="Delete"><i class="material-icons-outlined">delete</i></a>'
                + '</span></div></div>'
                + '<div class="pn-cm-budget-phase-items"></div>'
                + '</div>';
        }

        return '<tr class="pn-customers-manager-budget-item-row pn-customers-manager-budget-item-phase" data-item-id="' + escHtml(item.id) + '">'
            + '<td class="pn-customers-manager-budget-col-drag">'
            + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
            + '<input type="hidden" name="pn_cm_budget_item_id[]" value="' + escHtml(item.id) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_type[]" value="phase" />'
            + '<input type="hidden" name="pn_cm_budget_item_quantity[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_unit_price[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_is_optional[]" value="0" />'
            + '</td>'
            + '<td colspan="6" class="pn-customers-manager-budget-col-description">'
            + '<input type="text" name="pn_cm_budget_item_description[]" class="pn-customers-manager-input pn-customers-manager-width-100-percent pn-customers-manager-budget-phase-input" value="' + escHtml(item.description) + '" />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-actions">'
            + '<a href="#" class="pn-customers-manager-budget-item-delete pn-customers-manager-text-decoration-none">'
            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i></a>'
            + '</td>'
            + '</tr>';
    }

    function buildImageRow(item) {
        if (isPublicPage) {
            return '<div class="pn-cm-budget-item-row pn-cm-budget-item-image pn-cm-budget-gallery-item"'
                + ' data-item-id="' + escHtml(item.id) + '"'
                + ' data-item-type="image"'
                + ' data-item-description="' + escHtml(item.description) + '">'
                + '<div class="pn-cm-budget-item-content">'
                + '<img src="' + escHtml(item.description) + '" class="pn-cm-budget-image-preview" alt="" />'
                + '<span class="pn-cm-budget-col-actions">'
                + '<a href="#" class="pn-cm-budget-row-delete" title="Delete"><i class="material-icons-outlined">delete</i></a>'
                + '</span></div></div>';
        }

        return '<tr class="pn-customers-manager-budget-item-row pn-customers-manager-budget-item-image" data-item-id="' + escHtml(item.id) + '">'
            + '<td class="pn-customers-manager-budget-col-drag">'
            + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
            + '<input type="hidden" name="pn_cm_budget_item_id[]" value="' + escHtml(item.id) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_type[]" value="image" />'
            + '<input type="hidden" name="pn_cm_budget_item_quantity[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_unit_price[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_is_optional[]" value="0" />'
            + '</td>'
            + '<td colspan="6" class="pn-customers-manager-budget-col-description">'
            + '<input type="hidden" name="pn_cm_budget_item_description[]" value="' + escHtml(item.description) + '" />'
            + '<img src="' + escHtml(item.description) + '" class="pn-customers-manager-budget-image-preview" alt="" />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-actions">'
            + '<a href="#" class="pn-customers-manager-budget-item-delete pn-customers-manager-text-decoration-none">'
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

        $('.pn-customers-manager-budget-items-body .pn-customers-manager-budget-item-row').each(function () {
            var $row = $(this);
            if ($row.hasClass('pn-customers-manager-budget-item-phase') || $row.hasClass('pn-customers-manager-budget-item-image')) return;

            var qty = parseFloat($row.find('.pn-customers-manager-budget-item-quantity').val()) || 0;
            var price = parseFloat($row.find('.pn-customers-manager-budget-item-unit-price').val()) || 0;
            var lineTotal = qty * price;

            $row.find('.pn-customers-manager-budget-item-line-total').text(formatCurrency(lineTotal));
            subtotal += lineTotal;
        });

        var taxRate = parseFloat($('#pn_cm_budget_tax_rate').val()) || 0;
        var discountRate = parseFloat($('#pn_cm_budget_discount_rate').val()) || 0;

        var discount = subtotal * (discountRate / 100);
        var afterDiscount = subtotal - discount;
        var tax = afterDiscount * (taxRate / 100);
        var grandTotal = afterDiscount + tax;

        $('.pn-customers-manager-budget-totals .pn-customers-manager-budget-totals-value').each(function (index) {
            switch (index) {
                case 0: $(this).text(formatCurrency(subtotal)); break;
                case 1: $(this).text(discountRate > 0 ? '-' + formatCurrency(discount) : formatCurrency(0)); break;
                case 2: $(this).text(formatCurrency(tax)); break;
            }
        });
        $('.pn-customers-manager-budget-total-row .pn-customers-manager-budget-totals-value').text(formatCurrency(grandTotal));
    }

    /**
     * Recalculate totals on the public page reading from data-item-total attributes.
     * Looks inside both loose items and phase-group items.
     */
    function recalculatePublicTotals() {
        var subtotal = 0;

        $('#pn-cm-budget-items-body .pn-cm-budget-item-row').each(function () {
            if ($(this).hasClass('pn-cm-budget-item-phase') || $(this).hasClass('pn-cm-budget-item-image')) return;
            // Skip deselected optional items.
            if ($(this).hasClass('pn-cm-budget-item-deselected')) return;
            subtotal += parseFloat($(this).attr('data-item-total')) || 0;
        });

        var taxRate = parseFloat($('#pn_cm_budget_tax_rate').val()) || 0;
        var discountRate = parseFloat($('#pn_cm_budget_discount_rate').val()) || 0;
        var discount = subtotal * (discountRate / 100);
        var afterDiscount = subtotal - discount;
        var tax = afterDiscount * (taxRate / 100);
        var total = afterDiscount + tax;

        $('#pn-cm-budget-subtotal').text(formatCurrency(subtotal));
        if ($('#pn-cm-budget-discount').length) {
            $('#pn-cm-budget-discount').text('-' + formatCurrency(discount));
        }
        if ($('#pn-cm-budget-tax').length) {
            $('#pn-cm-budget-tax').text(formatCurrency(tax));
        }
        $('#pn-cm-budget-total').text(formatCurrency(total));
    }

    /**
     * Initialize sortable on phase-items containers.
     */
    function initPhaseItemsSortable($container) {
        if (!$container || !$container.length || !$.fn.sortable) return;
        $container.sortable({
            handle: '.pn-cm-budget-col-drag',
            items: '> .pn-cm-budget-item-row',
            connectWith: '.pn-cm-budget-phase-items, #pn-cm-budget-items-body',
            placeholder: 'pn-cm-budget-sortable-placeholder',
            cursor: 'grabbing',
            update: function () {
                scheduleRebuild();
            }
        });
    }

    /* ------------------------------------------------------------------
     * Rebuild phase groups after drag-drop (public page only)
     * ----------------------------------------------------------------*/

    var _rebuildTimer = null;
    function scheduleRebuild() {
        if (_rebuildTimer) clearTimeout(_rebuildTimer);
        _rebuildTimer = setTimeout(function () {
            rebuildPhaseGroups();
            saveOrder();
        }, 60);
    }

    /**
     * Reconstruct the phase-group DOM structure from flat item order.
     * After a drag-drop, items may have moved outside their phase wrapper,
     * losing the left-border visual indicator.
     */
    function rebuildPhaseGroups() {
        var $body = $('#pn-cm-budget-items-body');
        if (!$body.length) return;

        // 1. Destroy all sortable instances.
        try { $body.sortable('destroy'); } catch (e) {}
        $('.pn-cm-budget-phase-items').each(function () {
            try { $(this).sortable('destroy'); } catch (e) {}
        });

        // 2. Flatten: collect every element in DOM order.
        var flat = [];
        $body.children().each(function () {
            var $el = $(this);
            if ($el.hasClass('pn-cm-budget-phase-group')) {
                // Extract phase header.
                var $phaseRow = $el.children('.pn-cm-budget-item-phase').detach();
                flat.push($phaseRow);
                // Extract children.
                $el.children('.pn-cm-budget-phase-items').children('.pn-cm-budget-item-row').each(function () {
                    flat.push($(this).detach());
                });
                $el.remove();
            } else {
                flat.push($el.detach());
            }
        });

        // 3. Rebuild: walk flat array, grouping items under phases.
        var currentGroup = null;
        var currentItems = null;
        for (var i = 0; i < flat.length; i++) {
            var $el = flat[i];
            if ($el.hasClass('pn-cm-budget-item-phase') || $el.attr('data-item-type') === 'phase') {
                currentGroup = $('<div class="pn-cm-budget-phase-group" data-phase-id="' + $el.attr('data-item-id') + '"></div>');
                currentItems = $('<div class="pn-cm-budget-phase-items"></div>');
                currentGroup.append($el);
                currentGroup.append(currentItems);
                $body.append(currentGroup);
            } else {
                if (currentItems) {
                    currentItems.append($el);
                } else {
                    $body.append($el);
                }
            }
        }

        // 4. Re-initialize sortable on outer container.
        $body.sortable({
            handle: '> .pn-cm-budget-item-row > .pn-cm-budget-col-drag, > .pn-cm-budget-phase-group > .pn-cm-budget-item-phase > .pn-cm-budget-col-drag',
            items: '> .pn-cm-budget-item-row, > .pn-cm-budget-phase-group',
            placeholder: 'pn-cm-budget-sortable-placeholder',
            cursor: 'grabbing',
            tolerance: 'pointer',
            update: function () {
                scheduleRebuild();
            }
        });

        // 5. Re-initialize sortable on each phase-items container.
        $('.pn-cm-budget-phase-items').each(function () {
            initPhaseItemsSortable($(this));
        });

        // 6. Update phase totals.
        updatePhaseTotals();
    }

    /**
     * Recalculate and display the total for each phase group header.
     */
    function updatePhaseTotals() {
        $('.pn-cm-budget-phase-group').each(function () {
            var phaseTotal = 0;
            $(this).find('.pn-cm-budget-phase-items > .pn-cm-budget-item-row').each(function () {
                if ($(this).hasClass('pn-cm-budget-item-deselected') || $(this).hasClass('pn-cm-budget-item-image')) return;
                phaseTotal += parseFloat($(this).attr('data-item-total')) || 0;
            });
            var $label = $(this).find('.pn-cm-budget-phase-total');
            if (!$label.length) {
                $label = $('<span class="pn-cm-budget-phase-total"></span>');
                $(this).find('.pn-cm-budget-col-phase').after($label);
            }
            $label.text(formatCurrency(phaseTotal));
        });
    }

    /* ------------------------------------------------------------------
     * Document ready
     * ----------------------------------------------------------------*/

    $(document).ready(function () {

        
if (typeof pnCmBudgetAdmin === 'undefined') return;

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
        $(document).on('click', '.pn-customers-manager-budget-add-item', function (e) {
            e.preventDefault();

            var budgetId = getBudgetId($(this));
            var $popup = createPopupShell();

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_add_item_form',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId
            }, function (response) {
                if (response.error_key === '' && response.html) {
                    $popup.find('.pn-customers-manager-popup-content').html(response.html);
                    addCloseButton($popup);
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    closePopup($popup);
                }
            }).fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                closePopup($popup);
            });
        });

        // Save new item from popup.
        $(document).on('click', '.pn-cm-budget-popup-add-save', function (e) {
            e.preventDefault();

            var $wrapper = $(this).closest('.pn-cm-budget-edit-item-popup');
            var budgetId = $wrapper.data('budget-id');
            var $popup = $(this).closest('.pn-customers-manager-popup');

            var type = $wrapper.find('#pn_cm_item_type').val();
            var description = $.trim($wrapper.find('#pn_cm_item_description').val());
            var quantity = $wrapper.find('#pn_cm_item_quantity').val();
            var unitPrice = $wrapper.find('#pn_cm_item_unit_price').val();
            var optional = $wrapper.find('#pn_cm_item_is_optional').is(':checked') ? 1 : 0;

            if (!description) {
                alert(pnCmBudgetAdmin.i18n.noDescription || 'Please enter a description.');
                return;
            }

            var $btn = $(this);
            $btn.addClass('pn-customers-manager-disabled');

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_add_item',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                item_type: type,
                description: description,
                quantity: quantity,
                unit_price: unitPrice,
                is_optional: optional
            }, function (response) {
                $btn.removeClass('pn-customers-manager-disabled');

                if (response.error_key === '' && response.item) {
                    if (isPublicPage) {
                        // Insert into the last phase group if one exists, else append loose.
                        var $lastGroup = $('#pn-cm-budget-items-body > .pn-cm-budget-phase-group:last');
                        if ($lastGroup.length) {
                            $lastGroup.find('.pn-cm-budget-phase-items').append(buildItemRow(response.item));
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
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                $btn.removeClass('pn-customers-manager-disabled');
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ==============================================================
         * 1b. Add phase – no modal, creates directly
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-budget-add-phase', function (e) {
            e.preventDefault();

            var budgetId = getBudgetId($(this));
            var $btn = $(this);
            $btn.addClass('pn-customers-manager-disabled');

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_add_item',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                item_type: 'phase',
                description: pnCmBudgetAdmin.i18n.newPhase || 'New phase',
                quantity: 0,
                unit_price: 0,
                is_optional: 0
            }, function (response) {
                $btn.removeClass('pn-customers-manager-disabled');

                if (response.error_key === '' && response.item) {
                    if (isPublicPage) {
                        var $html = $(buildPhaseRow(response.item));
                        getItemsTbody().append($html);
                        initPhaseItemsSortable($html.find('.pn-cm-budget-phase-items'));
                    } else {
                        getItemsTbody().append(buildPhaseRow(response.item));
                    }
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                $btn.removeClass('pn-customers-manager-disabled');
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ==============================================================
         * 1c. Add image – opens wp.media, creates item with URL
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-budget-add-image', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var budgetId = getBudgetId($btn);

            var frame = wp.media({
                title: pnCmBudgetAdmin.i18n.selectImage || 'Select image',
                multiple: true,
                library: { type: 'image' },
                button: { text: pnCmBudgetAdmin.i18n.useImage || 'Use image' }
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection').toArray();
                $btn.addClass('pn-customers-manager-disabled');

                var pending = selection.length;
                selection.forEach(function (attachment) {
                    var imageUrl = attachment.toJSON().url;

                    $.post(pnCmBudgetAdmin.ajaxUrl, {
                        action: 'pn_customers_manager_ajax',
                        pn_customers_manager_ajax_type: 'pn_cm_budget_add_item',
                        pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                        budget_id: budgetId,
                        item_type: 'image',
                        description: imageUrl,
                        quantity: 0,
                        unit_price: 0,
                        is_optional: 0
                    }, function (response) {
                        pending--;
                        if (pending <= 0) {
                            $btn.removeClass('pn-customers-manager-disabled');
                        }

                        if (response.error_key === '' && response.item) {
                            if (isPublicPage) {
                                var $gallery = $('#pn-cm-budget-images-gallery');
                                if (!$gallery.length) {
                                    $gallery = $('<div class="pn-cm-budget-images-gallery" id="pn-cm-budget-images-gallery" data-budget-id="' + budgetId + '"></div>');
                                    $('.pn-cm-budget-admin-buttons').before($gallery);
                                }
                                $gallery.append(buildImageRow(response.item));
                            } else {
                                getItemsTbody().append(buildImageRow(response.item));
                            }
                            if (isPublicPage && pending <= 0) saveOrder();
                        } else {
                            alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                        }
                    }).fail(function () {
                        pending--;
                        if (pending <= 0) {
                            $btn.removeClass('pn-customers-manager-disabled');
                        }
                        alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    });
                });
            });

            frame.open();
        });

        /* ==============================================================
         * 2. Remove item (admin popup)
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-budget-item-delete', function (e) {
            e.preventDefault();

            if (!confirm(pnCmBudgetAdmin.i18n.confirmDelete || 'Are you sure you want to delete this item?')) {
                return;
            }

            var $link = $(this);
            var $row = $link.closest('.pn-cm-budget-item-row');
            var itemId = $row.data('item-id');
            var budgetId = getBudgetId($link);

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_remove_item',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                item_id: itemId
            }, function (response) {
                if (response.error_key === '') {
                    $row.fadeOut(300, function () {
                        $(this).remove();
                        recalculateTotals();
                    });
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ==============================================================
         * 3. Drag-sort items (jQuery UI Sortable)
         * ============================================================*/

        if ($.fn.sortable) {
            // Admin popup sortable.
            $('.pn-customers-manager-budget-items-body').sortable({
                handle: '.pn-customers-manager-budget-col-drag',
                placeholder: 'pn-cm-budget-sortable-placeholder',
                cursor: 'grabbing',
                update: function () {
                    var budgetId = $(this).closest('.pn-customers-manager-budget-items-editor').data('pn_cm_budget-id') || 0;
                    var order = [];
                    $(this).find('tr').each(function (index) {
                        order.push({
                            id: $(this).data('item-id'),
                            position: index
                        });
                    });

                    $.post(pnCmBudgetAdmin.ajaxUrl, {
                        action: 'pn_customers_manager_ajax',
                        pn_customers_manager_ajax_type: 'pn_cm_budget_reorder_items',
                        pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                        budget_id: budgetId,
                        order: JSON.stringify(order)
                    });
                }
            });

            // Public page sortable: outer container (phase groups + loose items).
            if (isPublicPage) {
                $('#pn-cm-budget-items-body').sortable({
                    handle: '> .pn-cm-budget-item-row > .pn-cm-budget-col-drag, > .pn-cm-budget-phase-group > .pn-cm-budget-item-phase > .pn-cm-budget-col-drag',
                    items: '> .pn-cm-budget-item-row, > .pn-cm-budget-phase-group',
                    placeholder: 'pn-cm-budget-sortable-placeholder',
                    cursor: 'grabbing',
                    tolerance: 'pointer',
                    update: function () {
                        scheduleRebuild();
                    }
                });

                // Inner sortable on each .pn-cm-budget-phase-items.
                $('.pn-cm-budget-phase-items').each(function () {
                    initPhaseItemsSortable($(this));
                });

                // Initial phase totals.
                updatePhaseTotals();
            }
        }

        /* ==============================================================
         * 4. Send budget
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-pn_cm_budget-send-post', function (e) {
            e.preventDefault();

            if (!confirm(pnCmBudgetAdmin.i18n.confirmSend || 'Are you sure you want to send this budget?')) {
                return;
            }

            var $btn = $(this);
            var budgetId = $btn.data('pn_cm_budget-id') || $btn.closest('[data-pn_cm_budget-id]').data('pn_cm_budget-id') || 0;
            $btn.prop('disabled', true);

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_send',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId
            }, function (response) {
                $btn.prop('disabled', false);

                if (response.error_key === '') {
                    alert(pnCmBudgetAdmin.i18n.budgetSent || 'Budget sent successfully.');
                    location.reload();
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                $btn.prop('disabled', false);
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
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
            var $tooltip = $('<span class="pn-cm-budget-copied-tooltip" style="'
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

        $(document).on('input change', '#pn_cm_budget_tax_rate, #pn_cm_budget_discount_rate, .pn-customers-manager-budget-item-quantity, .pn-customers-manager-budget-item-unit-price', function () {
            recalculateTotals();
        });

        /* ==============================================================
         * 7. Toggle optional checkbox – sync hidden input
         * ============================================================*/

        $(document).on('change', '.pn-customers-manager-budget-item-optional-toggle', function () {
            var $hidden = $(this).siblings('input[name="pn_cm_budget_item_is_optional[]"]');
            $hidden.val($(this).is(':checked') ? 1 : 0);
        });

        /* ==============================================================
         * 8. Duplicate budget
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-pn_cm_budget-duplicate-post', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var budgetId = $('.pn-customers-manager-menu-more.pn-customers-manager-active').closest('[data-pn_cm_budget-id]').attr('data-pn_cm_budget-id') ||
                $(this).closest('[data-pn_cm_budget-id]').attr('data-pn_cm_budget-id') || 0;
            if (!budgetId) return;

            $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');
            $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_duplicate',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                pn_cm_budget_id: budgetId
            }, function (response) {
                if (response.error_key === '' && response.html) {
                    $('.pn-customers-manager-pn_cm_budget-list-wrapper').html(response.html);
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmBudgetAdmin.i18n.budgetDuplicated || 'Budget duplicated successfully.');
                    }
                } else {
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    }
                }
            }).fail(function () {
                if (typeof pn_customers_manager_get_main_message === 'function') {
                    pn_customers_manager_get_main_message(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            });
        });

        /* ==============================================================
         * 8b. Generate Invoice from Budget
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-pn_cm_budget-generate-invoice', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var budgetId = $('.pn-customers-manager-menu-more.pn-customers-manager-active').closest('[data-pn_cm_budget-id]').attr('data-pn_cm_budget-id') ||
                $(this).closest('[data-pn_cm_budget-id]').attr('data-pn_cm_budget-id') || $(this).data('pn_cm_budget-id') || 0;
            if (!budgetId) return;

            if (!confirm(pnCmBudgetAdmin.i18n.confirmGenerateInvoice || 'Are you sure you want to generate an invoice from this budget?')) {
                return;
            }

            $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');
            $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_generate_invoice',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId
            }, function (response) {
                if (response.error_key === '' && response.invoice_id) {
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(
                            (pnCmBudgetAdmin.i18n.invoiceGenerated || 'Invoice generated successfully.') +
                            ' #' + (response.invoice_number || response.invoice_id)
                        );
                    }
                } else {
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    }
                }
            }).fail(function () {
                if (typeof pn_customers_manager_get_main_message === 'function') {
                    pn_customers_manager_get_main_message(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            });
        });

        /* ==============================================================
         * 9. Delete budget
         * ============================================================*/

        $(document).on('click', '.pn-customers-manager-pn_cm_budget-remove', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var budgetId = $('.pn-customers-manager-menu-more.pn-customers-manager-active').closest('[data-pn_cm_budget-id]').attr('data-pn_cm_budget-id') ||
                $('.pn-customers-manager-list-more.pn-customers-manager-active').closest('[data-pn_cm_budget-id]').attr('data-pn_cm_budget-id') || 0;
            if (!budgetId) return;

            $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');
            $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');

            var $listContainer = $('.pn-customers-manager-pn_cm_budget-list-wrapper');
            $listContainer.fadeOut('fast');

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_remove',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                pn_cm_budget_id: budgetId
            }, function (response) {
                if (response.error_key === '' && response.html) {
                    $listContainer.html(response.html).fadeIn('slow');
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmBudgetAdmin.i18n.budgetRemoved || 'Budget removed successfully.');
                    }
                } else {
                    $listContainer.fadeIn('fast');
                    if (typeof pn_customers_manager_get_main_message === 'function') {
                        pn_customers_manager_get_main_message(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    }
                }

                if (typeof pn_customers_manager_Popups !== 'undefined' && typeof pn_customers_manager_Popups.close === 'function') {
                    pn_customers_manager_Popups.close();
                }
            }).fail(function () {
                $listContainer.fadeIn('fast');
                if (typeof pn_customers_manager_get_main_message === 'function') {
                    pn_customers_manager_get_main_message(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
                if (typeof pn_customers_manager_Popups !== 'undefined' && typeof pn_customers_manager_Popups.close === 'function') {
                    pn_customers_manager_Popups.close();
                }
            });
        });

        /* ==============================================================
         * 10. Public page: popup editing per row + delete
         * ============================================================*/

        if (isPublicPage) {

            var hasOptional = !!pnCmBudgetAdmin.hasOptionalItems;

            // --- Edit button: open popup with form loaded via AJAX ---
            $(document).on('click', '.pn-cm-budget-row-edit', function (e) {
                e.preventDefault();
                var $row = $(this).closest('.pn-cm-budget-item-row');
                var itemId = $row.data('item-id');
                var budgetId = getBudgetId($row);

                var $popup = createPopupShell();

                $.post(pnCmBudgetAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_budget_edit_item_form',
                    pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                    budget_id: budgetId,
                    item_id: itemId
                }, function (response) {
                    if (response.error_key === '' && response.html) {
                        $popup.find('.pn-customers-manager-popup-content').html(response.html);
                        addCloseButton($popup);
                    } else {
                        alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                        closePopup($popup);
                    }
                }).fail(function () {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    closePopup($popup);
                });
            });

            // --- Save button inside edit popup ---
            $(document).on('click', '.pn-cm-budget-popup-save', function (e) {
                e.preventDefault();
                var $wrapper = $(this).closest('.pn-cm-budget-edit-item-popup');
                var itemId = $wrapper.data('item-id');
                var budgetId = $wrapper.data('budget-id');
                var isPhase = $wrapper.data('item-type') === 'phase';
                var $popup = $(this).closest('.pn-customers-manager-popup');

                var postData = {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_budget_update_item',
                    pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                    budget_id: budgetId,
                    item_id: itemId
                };

                if (isPhase) {
                    postData.description = $wrapper.find('#pn_cm_item_description').val();
                    postData.item_type = 'phase';
                    postData.quantity = 0;
                    postData.unit_price = 0;
                    postData.is_optional = 0;
                } else {
                    postData.description = $wrapper.find('#pn_cm_item_description').val();
                    postData.item_type = $wrapper.find('#pn_cm_item_type').val();
                    postData.quantity = $wrapper.find('#pn_cm_item_quantity').val();
                    postData.unit_price = $wrapper.find('#pn_cm_item_unit_price').val();
                    postData.is_optional = $wrapper.find('#pn_cm_item_is_optional').is(':checked') ? 1 : 0;
                }

                var $btn = $(this);
                $btn.addClass('pn-customers-manager-disabled');

                $.post(pnCmBudgetAdmin.ajaxUrl, postData, function (response) {
                    $btn.removeClass('pn-customers-manager-disabled');

                    if (response.error_key === '' && response.item) {
                        var item = response.item;
                        var total = (parseFloat(item.quantity) * parseFloat(item.unit_price)) || 0;
                        var $row = $('#pn-cm-budget-items-body [data-item-id="' + itemId + '"]');

                        // Update data attributes.
                        $row.attr('data-item-description', item.description);
                        $row.attr('data-item-type', item.item_type);

                        if (isPhase) {
                            $row.find('.pn-cm-budget-col-phase').text(item.description);
                        } else {
                            $row.attr('data-item-quantity', parseFloat(item.quantity).toFixed(2));
                            $row.attr('data-item-unit-price', parseFloat(item.unit_price).toFixed(2));
                            $row.attr('data-item-optional', item.is_optional);
                            $row.attr('data-item-total', total.toFixed(2));

                            $row.find('.pn-cm-budget-col-desc').text(item.description);
                            $row.find('.pn-cm-budget-col-type').html('<i class="material-icons-outlined">' + (item.item_type === 'hours' ? 'schedule' : 'payments') + '</i>');
                            $row.find('.pn-cm-budget-col-qty').text(formatNumber(item.quantity));
                            $row.find('.pn-cm-budget-col-price').text(formatCurrency(item.unit_price));
                            $row.find('.pn-cm-budget-col-total').text(formatCurrency(total));

                            if (hasOptional) {
                                if (parseInt(item.is_optional, 10)) {
                                    $row.find('.pn-cm-budget-col-toggle').html(
                                        '<label class="pn-cm-budget-toggle">'
                                        + '<input type="checkbox" class="pn-cm-budget-toggle-item" data-item-id="' + escHtml(item.id) + '" checked>'
                                        + '<span class="pn-cm-budget-toggle-slider"></span></label>'
                                    );
                                } else {
                                    $row.find('.pn-cm-budget-col-toggle').html(
                                        '<i class="material-icons-outlined" title="Required">lock</i>'
                                    );
                                }
                            }
                        }

                        // Update totals from server response.
                        if (response.totals) {
                            $('#pn-cm-budget-subtotal').text(formatCurrency(response.totals.subtotal));
                            if ($('#pn-cm-budget-discount').length) {
                                $('#pn-cm-budget-discount').text('-' + formatCurrency(response.totals.discount_amount));
                            }
                            if ($('#pn-cm-budget-tax').length) {
                                $('#pn-cm-budget-tax').text(formatCurrency(response.totals.tax_amount));
                            }
                            $('#pn-cm-budget-total').text(formatCurrency(response.totals.total));
                        }

                        closePopup($popup);
                    } else {
                        alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    }
                }).fail(function () {
                    $btn.removeClass('pn-customers-manager-disabled');
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                });
            });

            // --- Delete button (public page) ---
            $(document).on('click', '.pn-cm-budget-row-delete', function (e) {
                e.preventDefault();

                if (!confirm(pnCmBudgetAdmin.i18n.confirmDelete || 'Delete this item?')) return;

                var $row = $(this).closest('.pn-cm-budget-item-row');
                var itemId = $row.data('item-id');
                var budgetId = getBudgetId($row);
                var isPhase = $row.hasClass('pn-cm-budget-item-phase');
                var $group = isPhase ? $row.closest('.pn-cm-budget-phase-group') : null;

                $.post(pnCmBudgetAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_budget_remove_item',
                    pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                    budget_id: budgetId,
                    item_id: itemId
                }, function (response) {
                    if (response.error_key === '') {
                        if (isPhase && $group) {
                            // When deleting a phase, move its items out as loose items.
                            var $phaseItems = $group.find('.pn-cm-budget-phase-items > .pn-cm-budget-item-row');
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
                        alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    }
                }).fail(function () {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                });
            });

            /* ==============================================================
             * 11a. Toggle individual optional item (admin – private AJAX)
             * ============================================================*/

            $(document).on('change', '.pn-cm-budget-toggle-item', function () {
                var $checkbox = $(this);
                var itemId    = $checkbox.data('item-id');
                var checked   = $checkbox.is(':checked');
                var $row      = $checkbox.closest('.pn-cm-budget-item-row');
                var budgetId  = getBudgetId($row);

                // Immediate visual feedback.
                $row.toggleClass('pn-cm-budget-item-deselected', !checked);
                recalculatePublicTotals();
                updatePhaseTotals();

                // Update phase switch state.
                var $group = $row.closest('.pn-cm-budget-phase-group');
                if ($group.length) {
                    var allChecked = true;
                    $group.find('.pn-cm-budget-phase-items .pn-cm-budget-toggle-item').each(function () {
                        if (!$(this).is(':checked')) allChecked = false;
                    });
                    $group.find('.pn-cm-budget-phase-switch-input').prop('checked', allChecked);
                }

                $.post(pnCmBudgetAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_budget_toggle_item',
                    pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                    budget_id: budgetId,
                    item_id: itemId,
                    is_selected: checked ? 1 : 0
                }, function (response) {
                    if (response.error_key === '' && response.totals) {
                        $('#pn-cm-budget-subtotal').text(formatCurrency(response.totals.subtotal));
                        if ($('#pn-cm-budget-discount').length) {
                            $('#pn-cm-budget-discount').text('-' + formatCurrency(response.totals.discount_amount));
                        }
                        if ($('#pn-cm-budget-tax').length) {
                            $('#pn-cm-budget-tax').text(formatCurrency(response.totals.tax_amount));
                        }
                        $('#pn-cm-budget-total').text(formatCurrency(response.totals.total));
                    }
                }).fail(function () {
                    // Revert on failure.
                    $checkbox.prop('checked', !checked);
                    $row.toggleClass('pn-cm-budget-item-deselected', checked);
                    recalculatePublicTotals();
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                });
            });

            /* ==============================================================
             * 11b. Phase collapse / expand
             * ============================================================*/

            $(document).on('click', '.pn-cm-budget-phase-toggle', function () {
                var $group = $(this).closest('.pn-cm-budget-phase-group');
                $group.toggleClass('pn-cm-budget-collapsed');
                // Icon rotated via CSS transform when collapsed.
            });

            /* ==============================================================
             * 12. Phase toggle (activate/deactivate all items in phase)
             * ============================================================*/

            $(document).on('change', '.pn-cm-budget-phase-switch-input', function () {
                var isChecked = $(this).is(':checked');
                var $group = $(this).closest('.pn-cm-budget-phase-group');
                var budgetId = getBudgetId($group);
                var itemIds = [];

                $group.find('.pn-cm-budget-phase-items .pn-cm-budget-item-row').each(function () {
                    itemIds.push($(this).data('item-id'));
                });

                if (!itemIds.length) return;

                $.post(pnCmBudgetAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_budget_toggle_phase_items',
                    pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                    budget_id: budgetId,
                    item_ids: itemIds,
                    is_selected: isChecked ? 1 : 0
                }, function (response) {
                    if (response.error_key === '') {
                        // Update visual state of items.
                        $group.find('.pn-cm-budget-phase-items .pn-cm-budget-item-row').each(function () {
                            if (isChecked) {
                                $(this).removeClass('pn-cm-budget-item-deselected');
                            } else {
                                $(this).addClass('pn-cm-budget-item-deselected');
                            }
                            // Update individual toggle switches if they exist.
                            var $toggle = $(this).find('.pn-cm-budget-toggle-item');
                            if ($toggle.length) {
                                $toggle.prop('checked', isChecked);
                            }
                        });

                        // Update totals from server response.
                        if (response.totals) {
                            $('#pn-cm-budget-subtotal').text(formatCurrency(response.totals.subtotal));
                            if ($('#pn-cm-budget-discount').length) {
                                $('#pn-cm-budget-discount').text('-' + formatCurrency(response.totals.discount_amount));
                            }
                            if ($('#pn-cm-budget-tax').length) {
                                $('#pn-cm-budget-tax').text(formatCurrency(response.totals.tax_amount));
                            }
                            $('#pn-cm-budget-total').text(formatCurrency(response.totals.total));
                        }
                        updatePhaseTotals();
                    } else {
                        // Revert switch on error.
                        $(this).prop('checked', !isChecked);
                        alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    }
                }).fail(function () {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                });
            });

        } // end isPublicPage

        // Initial calculation on page load (admin popup only – public totals are server-rendered).
        if (!isPublicPage) {
            recalculateTotals();
        }

    }); // end document.ready

})(jQuery);
