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
        if (item.item_type === 'image_compare') {
            return buildCompareRow(item);
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

            var recurringBadgeHtml = '';
            if (parseInt(item.is_recurring, 10) && item.recurring_period) {
                var rlabel = (pnCmBudgetAdmin.recurringLabels || {})[item.recurring_period] || item.recurring_period;
                recurringBadgeHtml = '<span class="pn-cm-budget-recurring-badge"><i class="material-icons-outlined">autorenew</i> ' + escHtml(rlabel) + '</span>';
            }

            var altOf = parseInt(item.alt_of, 10) || 0;
            var altActive = item.alt_active || 'original';
            var altClass = altOf ? ' pn-cm-budget-alt-item' : '';
            var altBadge = altOf ? '<span class="pn-cm-budget-alt-badge">ALT</span>' : '';
            var swapBtn = '';
            // Swap button is added dynamically after the alternative is created.

            return '<div class="pn-cm-budget-item-row' + altClass + '"'
                + ' data-item-id="' + escHtml(item.id) + '"'
                + ' data-item-type="' + escHtml(item.item_type) + '"'
                + ' data-item-description="' + escHtml(item.description) + '"'
                + ' data-item-quantity="' + parseFloat(item.quantity).toFixed(2) + '"'
                + ' data-item-unit-price="' + parseFloat(item.unit_price).toFixed(2) + '"'
                + ' data-item-optional="' + (parseInt(item.is_optional, 10) ? '1' : '0') + '"'
                + ' data-item-total="' + total.toFixed(2) + '"'
                + ' data-item-recurring="' + (parseInt(item.is_recurring, 10) ? '1' : '0') + '"'
                + ' data-item-recurring-period="' + escHtml(item.recurring_period || '') + '"'
                + ' data-alt-of="' + altOf + '"'
                + ' data-alt-active="' + escHtml(altActive) + '">'
                + '<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Drag to reorder">drag_indicator</i></span>'
                + '<div class="pn-cm-budget-item-content">'
                + '<div class="pn-cm-budget-item-title">'
                + altBadge
                + '<span class="pn-cm-budget-col-desc">' + item.description + '</span>'
                + toggleHtml
                + '<span class="pn-cm-budget-col-actions">'
                + '<a href="#" class="pn-cm-budget-row-edit pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Edit"><i class="material-icons-outlined">edit</i></a>'
                + '<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Delete"><i class="material-icons-outlined">delete</i></a>'
                + '</span></div>'
                + '<div class="pn-cm-budget-item-meta">'
                + '<span class="pn-cm-budget-col-type"><i class="material-icons-outlined pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + (item.item_type === 'hours' ? 'Hourly rate' : 'Fixed price') + '">' + (item.item_type === 'hours' ? 'schedule' : 'payments') + '</i>' + '</span>'
                + recurringBadgeHtml
                + '<span class="pn-cm-budget-col-qty">' + formatNumber(item.quantity) + '</span>'
                + '<span class="pn-cm-budget-col-price">' + formatCurrency(item.unit_price) + '</span>'
                + '<span class="pn-cm-budget-col-total">' + formatCurrency(total) + '</span>'
                + '</div></div></div>';
        }

        // Admin popup row (read-only display with hidden inputs).
        var optIcon = parseInt(item.is_optional, 10)
            ? '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" style="color:#2271b1;font-size:18px;" data-pn-customers-manager-tooltip="Optional">check_box</i>'
            : '';
        var typeLabel = item.item_type === 'hours' ? 'Hours' : 'Fixed';
        var adminRecurringBadge = '';
        if (parseInt(item.is_recurring, 10) && item.recurring_period) {
            var adminRlabel = (pnCmBudgetAdmin.recurringLabels || {})[item.recurring_period] || item.recurring_period;
            adminRecurringBadge = ' <span class="pn-cm-budget-recurring-badge"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">autorenew</i> ' + escHtml(adminRlabel) + '</span>';
        }

        var adminAltOf = parseInt(item.alt_of, 10) || 0;
        var adminAltActive = item.alt_active || 'original';
        var adminAltClass = adminAltOf ? ' pn-cm-budget-alt-item' : '';
        var adminAltBadge = adminAltOf ? '<span class="pn-cm-budget-alt-badge">ALT</span> ' : '';

        return '<tr class="pn-customers-manager-budget-item-row' + adminAltClass + '" data-item-id="' + escHtml(item.id) + '" data-alt-of="' + adminAltOf + '" data-alt-active="' + escHtml(adminAltActive) + '">'
            + '<td class="pn-customers-manager-budget-col-drag">'
            + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
            + '<input type="hidden" name="pn_cm_budget_item_id[]" value="' + escHtml(item.id) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_description[]" value="' + escHtml(item.description) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_type[]" value="' + escHtml(item.item_type) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_quantity[]" class="pn-customers-manager-budget-item-quantity" value="' + parseFloat(item.quantity).toFixed(2) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_unit_price[]" class="pn-customers-manager-budget-item-unit-price" value="' + parseFloat(item.unit_price).toFixed(2) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_is_optional[]" value="' + (parseInt(item.is_optional, 10) ? '1' : '0') + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_is_recurring[]" value="' + (parseInt(item.is_recurring, 10) ? '1' : '0') + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_recurring_period[]" value="' + escHtml(item.recurring_period || '') + '" />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-description">'
            + adminAltBadge + '<span class="pn-customers-manager-budget-item-desc-display">' + item.description + '</span>'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-type">'
            + '<span class="pn-customers-manager-budget-item-type-display">' + escHtml(typeLabel) + '</span>' + adminRecurringBadge
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-quantity">'
            + '<span class="pn-customers-manager-budget-item-qty-display">' + formatNumber(item.quantity) + '</span>'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-price">'
            + '<span class="pn-customers-manager-budget-item-price-display">' + formatCurrency(item.unit_price) + '</span>'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-total">'
            + '<span class="pn-customers-manager-budget-item-line-total">' + formatCurrency(total) + '</span>'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-optional">'
            + optIcon
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-actions">'
            + '<a href="#" class="pn-cm-budget-row-edit pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Edit">'
            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">edit</i></a>'
            + '<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Delete">'
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
                + '<span class="pn-cm-budget-col-drag"><i class="material-icons-outlined pn-cm-budget-drag-handle pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Drag to reorder">drag_indicator</i></span>'
                + '<i class="material-icons-outlined pn-cm-budget-phase-toggle pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Collapse / Expand">expand_more</i>'
                + '<div class="pn-cm-budget-item-content">'
                + '<span class="pn-cm-budget-col-phase">' + item.description + '</span>'
                + '<span class="pn-cm-budget-phase-total"></span>'
                + '<span class="pn-cm-budget-col-actions">'
                + '<a href="#" class="pn-cm-budget-row-edit pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Edit"><i class="material-icons-outlined">edit</i></a>'
                + '<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Delete"><i class="material-icons-outlined">delete</i></a>'
                + '</span></div></div>'
                + '<div class="pn-cm-budget-phase-items"></div>'
                + '</div>';
        }

        return '<tr class="pn-customers-manager-budget-item-row pn-customers-manager-budget-item-phase" data-item-id="' + escHtml(item.id) + '">'
            + '<td class="pn-customers-manager-budget-col-drag">'
            + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
            + '<input type="hidden" name="pn_cm_budget_item_id[]" value="' + escHtml(item.id) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_type[]" value="phase" />'
            + '<input type="hidden" name="pn_cm_budget_item_description[]" value="' + escHtml(item.description) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_quantity[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_unit_price[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_is_optional[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_is_recurring[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_recurring_period[]" value="" />'
            + '</td>'
            + '<td colspan="6" class="pn-customers-manager-budget-col-description">'
            + '<span class="pn-customers-manager-budget-item-desc-display pn-customers-manager-budget-phase-input">' + item.description + '</span>'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-actions">'
            + '<a href="#" class="pn-cm-budget-row-edit pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Edit">'
            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">edit</i></a>'
            + '<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Delete">'
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
                + '<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Delete"><i class="material-icons-outlined">delete</i></a>'
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
            + '<input type="hidden" name="pn_cm_budget_item_is_recurring[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_recurring_period[]" value="" />'
            + '</td>'
            + '<td colspan="6" class="pn-customers-manager-budget-col-description">'
            + '<input type="hidden" name="pn_cm_budget_item_description[]" value="' + escHtml(item.description) + '" />'
            + '<img src="' + escHtml(item.description) + '" class="pn-customers-manager-budget-image-preview" alt="" />'
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-actions">'
            + '<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Delete">'
            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i></a>'
            + '</td>'
            + '</tr>';
    }

    function buildCompareSliderHtml(beforeUrl, afterUrl) {
        return '<div class="pn-cm-budget-compare-slider">'
            + '<span class="pn-cm-budget-compare-label pn-cm-budget-compare-label-before">Before</span>'
            + '<span class="pn-cm-budget-compare-label pn-cm-budget-compare-label-after">After</span>'
            + '<div class="pn-cm-budget-compare-before"><img src="' + escHtml(beforeUrl) + '" alt="Before" /></div>'
            + '<div class="pn-cm-budget-compare-after"><img src="' + escHtml(afterUrl) + '" alt="After" /></div>'
            + '<div class="pn-cm-budget-compare-handle"></div>'
            + '</div>';
    }

    function buildCompareRow(item) {
        var data = {};
        try { data = JSON.parse(item.description); } catch (e) { data = {}; }
        var beforeUrl = data.before || '';
        var afterUrl = data.after || '';

        if (isPublicPage) {
            return '<div class="pn-cm-budget-item-row pn-cm-budget-item-image-compare pn-cm-budget-gallery-item"'
                + ' data-item-id="' + escHtml(item.id) + '"'
                + ' data-item-type="image_compare"'
                + ' data-item-description="' + escHtml(item.description) + '">'
                + '<div class="pn-cm-budget-item-content">'
                + buildCompareSliderHtml(beforeUrl, afterUrl)
                + '<span class="pn-cm-budget-col-actions">'
                + '<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Delete"><i class="material-icons-outlined">delete</i></a>'
                + '</span></div></div>';
        }

        return '<tr class="pn-customers-manager-budget-item-row pn-customers-manager-budget-item-image-compare" data-item-id="' + escHtml(item.id) + '">'
            + '<td class="pn-customers-manager-budget-col-drag">'
            + '<i class="material-icons-outlined pn-customers-manager-cursor-grab pn-customers-manager-vertical-align-middle">drag_indicator</i>'
            + '<input type="hidden" name="pn_cm_budget_item_id[]" value="' + escHtml(item.id) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_type[]" value="image_compare" />'
            + '<input type="hidden" name="pn_cm_budget_item_description[]" value="' + escHtml(item.description) + '" />'
            + '<input type="hidden" name="pn_cm_budget_item_quantity[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_unit_price[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_is_optional[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_is_recurring[]" value="0" />'
            + '<input type="hidden" name="pn_cm_budget_item_recurring_period[]" value="" />'
            + '</td>'
            + '<td colspan="6" class="pn-customers-manager-budget-col-description">'
            + buildCompareSliderHtml(beforeUrl, afterUrl)
            + '</td>'
            + '<td class="pn-customers-manager-budget-col-actions">'
            + '<a href="#" class="pn-cm-budget-row-delete pn-customers-manager-text-decoration-none pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Delete">'
            + '<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-cursor-pointer">delete</i></a>'
            + '</td>'
            + '</tr>';
    }

    function initCompareSliders() {
        $('.pn-cm-budget-compare-slider').each(function () {
            var $slider = $(this);
            if ($slider.data('pn-compare-init')) return;
            $slider.data('pn-compare-init', true);

            var $after = $slider.find('.pn-cm-budget-compare-after');
            var $handle = $slider.find('.pn-cm-budget-compare-handle');
            var dragging = false;

            function updatePosition(x) {
                var rect = $slider[0].getBoundingClientRect();
                var pct = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
                $after.css('clip-path', 'inset(0 0 0 ' + (pct * 100) + '%)');
                $handle.css('left', (pct * 100) + '%');
            }

            $handle.on('mousedown touchstart', function (e) {
                e.preventDefault();
                dragging = true;
            });

            $(document).on('mousemove touchmove', function (e) {
                if (!dragging) return;
                var x = e.pageX || (e.originalEvent.touches && e.originalEvent.touches[0].pageX);
                if (typeof x !== 'undefined') updatePosition(x);
            });

            $(document).on('mouseup touchend', function () {
                dragging = false;
            });

            $slider.on('click', function (e) {
                if ($(e.target).closest('.pn-cm-budget-col-actions').length) return;
                updatePosition(e.pageX);
            });
        });
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
        var recurringByPeriod = {};

        $('.pn-customers-manager-budget-items-body .pn-customers-manager-budget-item-row').each(function () {
            var $row = $(this);
            if ($row.hasClass('pn-customers-manager-budget-item-phase') || $row.hasClass('pn-customers-manager-budget-item-image') || $row.hasClass('pn-customers-manager-budget-item-image-compare')) return;
            if ($row.hasClass('pn-cm-budget-alt-hidden')) return;

            var qty = parseFloat($row.find('.pn-customers-manager-budget-item-quantity').val()) || 0;
            var price = parseFloat($row.find('.pn-customers-manager-budget-item-unit-price').val()) || 0;
            var lineTotal = qty * price;

            $row.find('.pn-customers-manager-budget-item-line-total').text(formatCurrency(lineTotal));

            var isRecurring = parseInt($row.find('input[name="pn_cm_budget_item_is_recurring[]"]').val(), 10) || 0;
            var period = $row.find('input[name="pn_cm_budget_item_recurring_period[]"]').val() || '';
            if (isRecurring && period) {
                recurringByPeriod[period] = (recurringByPeriod[period] || 0) + lineTotal;
            } else {
                subtotal += lineTotal;
            }
        });

        var taxRate = parseFloat($('#pn_cm_budget_tax_rate').val()) || 0;
        var discountRate = parseFloat($('#pn_cm_budget_discount_rate').val()) || 0;

        var discount = subtotal * (discountRate / 100);
        var afterDiscount = subtotal - discount;
        var tax = afterDiscount * (taxRate / 100);
        var grandTotal = afterDiscount + tax;

        $('#pn-cm-budget-admin-subtotal').text(formatCurrency(subtotal));

        // Remove old recurring rows and add new ones.
        $('.pn-cm-budget-admin-totals-recurring-row').remove();
        var labels = pnCmBudgetAdmin.recurringLabels || {};
        var $subtotalRow = $('#pn-cm-budget-admin-subtotal').closest('.pn-cm-budget-admin-totals-row');
        $.each(recurringByPeriod, function (p, amt) {
            var lbl = labels[p] || p;
            $('<div class="pn-cm-budget-admin-totals-row pn-cm-budget-admin-totals-recurring-row" style="color:#e65100;font-style:italic;">'
                + '<span>Recurring ' + escHtml(lbl) + '</span>'
                + '<span>' + formatCurrency(amt) + '</span>'
                + '</div>').insertAfter($subtotalRow);
        });

        if (discountRate > 0) {
            $('#pn-cm-budget-admin-discount-row').show();
            $('#pn-cm-budget-admin-discount').text('-' + formatCurrency(discount));
        } else {
            $('#pn-cm-budget-admin-discount-row').hide();
        }
        if (taxRate > 0) {
            $('#pn-cm-budget-admin-tax-row').show();
            $('#pn-cm-budget-admin-tax').text(formatCurrency(tax));
        } else {
            $('#pn-cm-budget-admin-tax-row').hide();
        }
        $('#pn-cm-budget-admin-total').html('<strong>' + formatCurrency(grandTotal) + '</strong>');
    }

    /**
     * Recalculate totals on the public page reading from data-item-total attributes.
     * Looks inside both loose items and phase-group items.
     */
    function recalculatePublicTotals() {
        var subtotal = 0;
        var recurringByPeriod = {};

        $('#pn-cm-budget-items-body .pn-cm-budget-item-row').each(function () {
            if ($(this).hasClass('pn-cm-budget-item-phase') || $(this).hasClass('pn-cm-budget-item-image') || $(this).hasClass('pn-cm-budget-item-image-compare')) return;
            // Skip deselected optional items.
            if ($(this).hasClass('pn-cm-budget-item-deselected')) return;
            // Skip hidden alternatives.
            if ($(this).hasClass('pn-cm-budget-alt-hidden')) return;

            var rowTotal = parseFloat($(this).attr('data-item-total')) || 0;
            var isRecurring = parseInt($(this).attr('data-item-recurring'), 10) || 0;
            var period = $(this).attr('data-item-recurring-period') || '';

            if (isRecurring && period) {
                recurringByPeriod[period] = (recurringByPeriod[period] || 0) + rowTotal;
            } else {
                subtotal += rowTotal;
            }
        });

        var taxRate = parseFloat($('#pn_cm_budget_tax_rate').val()) || 0;
        var discountRate = parseFloat($('#pn_cm_budget_discount_rate').val()) || 0;
        var discount = subtotal * (discountRate / 100);
        var afterDiscount = subtotal - discount;
        var tax = afterDiscount * (taxRate / 100);
        var total = afterDiscount + tax;

        $('#pn-cm-budget-subtotal').text(formatCurrency(subtotal));

        // Update recurring totals rows.
        $('.pn-cm-budget-totals-recurring').remove();
        var labels = pnCmBudgetAdmin.recurringLabels || {};
        var $subtotalRow = $('#pn-cm-budget-subtotal').closest('.pn-cm-budget-totals-row');
        $.each(recurringByPeriod, function (p, amt) {
            var lbl = labels[p] || p;
            $('<div class="pn-cm-budget-totals-row pn-cm-budget-totals-recurring" data-recurring-period="' + escHtml(p) + '">'
                + '<span>Recurring ' + escHtml(lbl) + '</span>'
                + '<span>' + formatCurrency(amt) + '</span>'
                + '</div>').insertAfter($subtotalRow);
        });

        if ($('#pn-cm-budget-discount').length) {
            $('#pn-cm-budget-discount').text('-' + formatCurrency(discount));
        }
        if ($('#pn-cm-budget-tax').length) {
            $('#pn-cm-budget-tax').text(formatCurrency(tax));
        }
        $('#pn-cm-budget-total').text(formatCurrency(total));

        // Sync sticky bar recurring items.
        var $stickyRecurring = $('#pn-cm-budget-sticky-recurring');
        var $stickyTotalItem = $('#pn-cm-budget-sticky-total').closest('.pn-cm-budget-sticky-item');
        var hasRecurring = false;
        $.each(recurringByPeriod, function () { hasRecurring = true; return false; });

        if (!hasRecurring) {
            $stickyRecurring.remove();
        } else {
            if (!$stickyRecurring.length) {
                $stickyRecurring = $('<div class="pn-cm-budget-sticky-recurring" id="pn-cm-budget-sticky-recurring"></div>');
                $stickyTotalItem.before($stickyRecurring);
            }
            $stickyRecurring.empty();
            $.each(recurringByPeriod, function (p, amt) {
                var lbl = labels[p] || p;
                $stickyRecurring.append(
                    '<div class="pn-cm-budget-sticky-item pn-cm-budget-sticky-item-recurring" data-recurring-period="' + escHtml(p) + '">'
                    + '<span class="pn-cm-budget-sticky-label">Recurring ' + escHtml(lbl) + '</span>'
                    + '<span class="pn-cm-budget-sticky-value">' + formatCurrency(amt) + '</span>'
                    + '</div>'
                );
            });
        }
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
                if ($(this).hasClass('pn-cm-budget-item-deselected') || $(this).hasClass('pn-cm-budget-item-image') || $(this).hasClass('pn-cm-budget-item-image-compare')) return;
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

        // Initialize any compare sliders present on page load.
        initCompareSliders();

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

        // Helper: initialise Trumbowyg editors inside a container (for AJAX-loaded popups).
        function initPopupEditors($container) {
            if ($.trumbowyg && typeof pn_customers_manager_trumbowyg !== 'undefined') {
                $.trumbowyg.svgPath = pn_customers_manager_trumbowyg.path;
                $container.find('.pn-customers-manager-wysiwyg').each(function () {
                    var opts = {
                        btns: [
                            ['formatting'],
                            ['strong', 'em', 'underline'],
                            ['unorderedList', 'orderedList'],
                            ['link'],
                            ['removeformat'],
                        ],
                        autogrow: true,
                        removeformatPasted: true,
                    };
                    var ph = $(this).attr('placeholder');
                    if (ph) opts.placeholder = ph;
                    $(this).trumbowyg(opts);
                });
            }
            // Initialize recurring period visibility toggle.
            var $recCheck = $container.find('#pn_cm_item_is_recurring');
            var $recPeriodWrapper = $container.find('#pn_cm_item_recurring_period').closest('.pn-customers-manager-input-wrapper');
            if ($recCheck.length && $recPeriodWrapper.length) {
                $recPeriodWrapper.toggle($recCheck.is(':checked'));
                $recCheck.off('change.recurring').on('change.recurring', function () {
                    $recPeriodWrapper.toggle($(this).is(':checked'));
                });
            }
        }

        // Helper: get HTML content from a Trumbowyg editor or fall back to .val().
        function getEditorValue($el) {
            if ($el.data('trumbowyg')) {
                return $el.trumbowyg('html');
            }
            return $el.val();
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
                    initPopupEditors($popup);
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
            var description = $.trim(getEditorValue($wrapper.find('#pn_cm_item_description')));
            var quantity = $wrapper.find('#pn_cm_item_quantity').val();
            var unitPrice = $wrapper.find('#pn_cm_item_unit_price').val();
            var optional = $wrapper.find('#pn_cm_item_is_optional').is(':checked') ? 1 : 0;
            var isRecurring = $wrapper.find('#pn_cm_item_is_recurring').is(':checked') ? 1 : 0;
            var recurringPeriod = isRecurring ? ($wrapper.find('#pn_cm_item_recurring_period').val() || '') : '';

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
                is_optional: optional,
                is_recurring: isRecurring,
                recurring_period: recurringPeriod
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
            e.stopPropagation();

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
                    var phaseHtml = buildPhaseRow(response.item);
                    var $tbody = getItemsTbody();
                    if (isPublicPage) {
                        var $html = $(phaseHtml);
                        $tbody.append($html);
                        initPhaseItemsSortable($html.find('.pn-cm-budget-phase-items'));
                    } else {
                        $tbody.append(phaseHtml);
                    }
                } else {
                    console.error('[budget-admin] Add phase error:', response.error_key);
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                $btn.removeClass('pn-customers-manager-disabled');
                console.error('[budget-admin] Add phase AJAX fail:', textStatus, errorThrown);
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        /* ==============================================================
         * 1c. Add image – opens wp.media, creates item with URL
         * ============================================================*/

        // Helper: force-show the wp.media modal wrapper that the budget page cleanup hides.
        function showMediaModal() {
            setTimeout(function () {
                $('.media-modal').each(function () {
                    // Force the modal itself visible.
                    this.style.setProperty('display', 'block', 'important');
                    this.style.setProperty('visibility', 'visible', 'important');
                    // Force all ancestors visible (the .supports-drag-drop wrapper
                    // is hidden by a CSS rule with !important).
                    $(this).parents().each(function () {
                        var el = $(this);
                        if (el.css('display') === 'none' || el.css('visibility') === 'hidden') {
                            this.style.setProperty('display', 'block', 'important');
                            this.style.setProperty('visibility', 'visible', 'important');
                        }
                    });
                });
                // Also ensure backdrop is visible.
                $('.media-modal-backdrop').each(function () {
                    if (!$(this).is(':visible')) {
                        this.style.setProperty('display', 'block', 'important');
                        this.style.setProperty('visibility', 'visible', 'important');
                    }
                    $(this).parents().each(function () {
                        if ($(this).css('display') === 'none' || $(this).css('visibility') === 'hidden') {
                            this.style.setProperty('display', 'block', 'important');
                            this.style.setProperty('visibility', 'visible', 'important');
                        }
                    });
                });
            }, 50);
        }

        // Helper: open wp.media and add single images.
        function openMediaSingleImage(budgetId) {
            var frame = wp.media({
                title: pnCmBudgetAdmin.i18n.selectImage || 'Select image',
                multiple: true,
                library: { type: 'image' },
                button: { text: pnCmBudgetAdmin.i18n.useImage || 'Use image' }
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection').toArray();
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
                        alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    });
                });
            });

            frame.open();
            showMediaModal();
        }

        // Helper: open wp.media for before/after compare slider (select exactly 2 images).
        function openMediaCompareImage(budgetId) {
            var frame = wp.media({
                title: pnCmBudgetAdmin.i18n.selectBeforeAfter || 'Select 2 images (before / after)',
                multiple: true,
                library: { type: 'image' },
                button: { text: pnCmBudgetAdmin.i18n.useImage || 'Use image' }
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection').toArray();
                if (selection.length !== 2) {
                    alert(pnCmBudgetAdmin.i18n.selectExactlyTwo || 'Please select exactly 2 images.');
                    return;
                }
                var beforeUrl = selection[0].toJSON().url;
                var afterUrl = selection[1].toJSON().url;

                $.post(pnCmBudgetAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_budget_add_item',
                    pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                    budget_id: budgetId,
                    item_type: 'image_compare',
                    description: JSON.stringify({ before: beforeUrl, after: afterUrl }),
                    quantity: 0,
                    unit_price: 0,
                    is_optional: 0
                }, function (response) {
                    if (response.error_key === '' && response.item) {
                        if (isPublicPage) {
                            var $gallery = $('#pn-cm-budget-images-gallery');
                            if (!$gallery.length) {
                                $gallery = $('<div class="pn-cm-budget-images-gallery" id="pn-cm-budget-images-gallery" data-budget-id="' + budgetId + '"></div>');
                                $('.pn-cm-budget-admin-buttons').before($gallery);
                            }
                            $gallery.append(buildCompareRow(response.item));
                        } else {
                            getItemsTbody().append(buildCompareRow(response.item));
                        }
                        initCompareSliders();
                        if (isPublicPage) saveOrder();
                    } else {
                        alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    }
                }).fail(function () {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                });
            });

            frame.open();
            showMediaModal();
        }

        $(document).on('click', '.pn-customers-manager-budget-add-image', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var budgetId = getBudgetId($btn);

            var $popup = createPopupShell();

            var html = '<div class="pn-customers-manager-p-30">'
                + '<h4 class="pn-customers-manager-mb-30">' + (pnCmBudgetAdmin.i18n.addImage || 'Add image') + '</h4>'
                + '<div style="display:flex;gap:16px;">'
                + '<a href="#" class="pn-cm-budget-image-mode-single" style="flex:1;display:flex;flex-direction:column;align-items:center;gap:10px;padding:24px 16px;border:2px solid #e0e0e0;border-radius:8px;text-decoration:none;color:inherit;transition:border-color .2s,background .2s;">'
                + '<i class="material-icons-outlined" style="font-size:40px;color:#666;">image</i>'
                + '<span style="font-weight:600;font-size:14px;">' + (pnCmBudgetAdmin.i18n.singleImage || 'Image') + '</span>'
                + '<span style="font-size:12px;color:#888;text-align:center;">' + (pnCmBudgetAdmin.i18n.singleImageDesc || 'Upload one or more images') + '</span>'
                + '</a>'
                + '<a href="#" class="pn-cm-budget-image-mode-compare" style="flex:1;display:flex;flex-direction:column;align-items:center;gap:10px;padding:24px 16px;border:2px solid #e0e0e0;border-radius:8px;text-decoration:none;color:inherit;transition:border-color .2s,background .2s;">'
                + '<i class="material-icons-outlined" style="font-size:40px;color:#666;">compare</i>'
                + '<span style="font-weight:600;font-size:14px;">' + (pnCmBudgetAdmin.i18n.compareImage || 'Before / After') + '</span>'
                + '<span style="font-size:12px;color:#888;text-align:center;">' + (pnCmBudgetAdmin.i18n.compareImageDesc || 'Select 2 images for a slide comparison') + '</span>'
                + '</a>'
                + '</div></div>';

            $popup.find('.pn-customers-manager-popup-content').html(html);
            addCloseButton($popup);

            var $singleBtn = $popup.find('.pn-cm-budget-image-mode-single');
            var $compareBtn = $popup.find('.pn-cm-budget-image-mode-compare');

            // Hover effect.
            $singleBtn.add($compareBtn).on('mouseenter', function () {
                $(this).css({ 'border-color': '#2271b1', 'background': '#f0f6fc' });
            }).on('mouseleave', function () {
                $(this).css({ 'border-color': '#e0e0e0', 'background': 'transparent' });
            });

            $singleBtn.on('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                closePopup($popup);
                openMediaSingleImage(budgetId);
            });

            $compareBtn.on('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                closePopup($popup);
                openMediaCompareImage(budgetId);
            });
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

        // (Optional toggle removed — editing is done via popup only.)

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
         * 10. Popup editing per row + delete (admin + public)
         * ============================================================*/

        // --- Edit button: open popup with form loaded via AJAX ---
        $(document).on('click', '.pn-cm-budget-row-edit', function (e) {
            e.preventDefault();
            var $row = $(this).closest('.pn-cm-budget-item-row, .pn-customers-manager-budget-item-row');
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
                    initPopupEditors($popup);
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    closePopup($popup);
                }
            }).fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                closePopup($popup);
            });
        });

        // --- Create alternative from edit popup ---
        $(document).on('click', '.pn-cm-budget-popup-add-alt', function (e) {
            e.preventDefault();
            var $wrapper = $(this).closest('.pn-cm-budget-edit-item-popup');
            var itemId = $wrapper.data('item-id');
            var budgetId = $wrapper.data('budget-id');
            var isPhase = $wrapper.data('item-type') === 'phase';
            var $popup = $(this).closest('.pn-customers-manager-popup');
            var $btn = $(this);
            $btn.addClass('pn-customers-manager-disabled');

            var postData = {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_add_item',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                item_type: isPhase ? 'phase' : 'fixed',
                description: (isPhase ? (pnCmBudgetAdmin.i18n.newPhase || 'New phase') : '') + ' (ALT)',
                quantity: 0,
                unit_price: 0,
                is_optional: 0,
                alt_of: itemId
            };

            $.post(pnCmBudgetAdmin.ajaxUrl, postData, function (response) {
                $btn.removeClass('pn-customers-manager-disabled');
                if (response.error_key === '' && response.item) {
                    closePopup($popup);
                    location.reload();
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                $btn.removeClass('pn-customers-manager-disabled');
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        // --- Toggle alternative (swap) for items and phases ---
        $(document).on('click', '.pn-cm-budget-swap-alt, .pn-cm-budget-swap-alt-phase', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var originalId = $btn.data('original-id');
            var isPhase = $btn.hasClass('pn-cm-budget-swap-alt-phase');
            var $row = $btn.closest('.pn-cm-budget-item-row');
            var budgetId = getBudgetId($row);

            if ($btn.hasClass('pn-customers-manager-disabled')) return;
            $btn.addClass('pn-customers-manager-disabled');

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_toggle_alternative',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                item_id: originalId
            }, function (response) {
                $btn.removeClass('pn-customers-manager-disabled');
                if (response.error_key === '') {
                    var newState = response.alt_active;

                    if (isPhase) {
                        // Toggle phase groups: original and its alternative.
                        var $originalGroup = $('#pn-cm-budget-items-body .pn-cm-budget-phase-group[data-phase-id="' + originalId + '"]');
                        var $altGroup = $('#pn-cm-budget-items-body .pn-cm-budget-phase-group[data-alt-of="' + originalId + '"]');

                        if (newState === 'alternative') {
                            $originalGroup.addClass('pn-cm-budget-alt-hidden');
                            $altGroup.removeClass('pn-cm-budget-alt-hidden');
                        } else {
                            $originalGroup.removeClass('pn-cm-budget-alt-hidden');
                            $altGroup.addClass('pn-cm-budget-alt-hidden');
                        }
                        $originalGroup.attr('data-alt-active', newState);
                        $originalGroup.find('> .pn-cm-budget-item-phase').attr('data-alt-active', newState);
                    } else {
                        // Toggle item rows: original and its alternative.
                        var $originalRow = $('#pn-cm-budget-items-body .pn-cm-budget-item-row[data-item-id="' + originalId + '"]');
                        var $altRow = $('#pn-cm-budget-items-body .pn-cm-budget-item-row[data-alt-of="' + originalId + '"]');

                        if (newState === 'alternative') {
                            $originalRow.addClass('pn-cm-budget-alt-hidden');
                            $altRow.removeClass('pn-cm-budget-alt-hidden');
                        } else {
                            $originalRow.removeClass('pn-cm-budget-alt-hidden');
                            $altRow.addClass('pn-cm-budget-alt-hidden');
                        }
                        $originalRow.attr('data-alt-active', newState);
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
                    recalculateTotals();
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                $btn.removeClass('pn-customers-manager-disabled');
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
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
                postData.description = getEditorValue($wrapper.find('#pn_cm_item_description'));
                postData.item_type = 'phase';
                postData.quantity = 0;
                postData.unit_price = 0;
                postData.is_optional = 0;
                postData.is_recurring = 0;
                postData.recurring_period = '';
            } else {
                postData.description = getEditorValue($wrapper.find('#pn_cm_item_description'));
                postData.item_type = $wrapper.find('#pn_cm_item_type').val();
                postData.quantity = $wrapper.find('#pn_cm_item_quantity').val();
                postData.unit_price = $wrapper.find('#pn_cm_item_unit_price').val();
                postData.is_optional = $wrapper.find('#pn_cm_item_is_optional').is(':checked') ? 1 : 0;
                postData.is_recurring = $wrapper.find('#pn_cm_item_is_recurring').is(':checked') ? 1 : 0;
                postData.recurring_period = postData.is_recurring ? ($wrapper.find('#pn_cm_item_recurring_period').val() || '') : '';
            }

            var $btn = $(this);
            $btn.addClass('pn-customers-manager-disabled');

            $.post(pnCmBudgetAdmin.ajaxUrl, postData, function (response) {
                $btn.removeClass('pn-customers-manager-disabled');

                if (response.error_key === '' && response.item) {
                    var item = response.item;
                    var total = (parseFloat(item.quantity) * parseFloat(item.unit_price)) || 0;

                    if (isPublicPage) {
                        var hasOptional = !!pnCmBudgetAdmin.hasOptionalItems;
                        var $row = $('#pn-cm-budget-items-body [data-item-id="' + itemId + '"]');

                        $row.attr('data-item-description', item.description);
                        $row.attr('data-item-type', item.item_type);
                        $row.attr('data-item-recurring', parseInt(item.is_recurring, 10) ? '1' : '0');
                        $row.attr('data-item-recurring-period', item.recurring_period || '');

                        if (isPhase) {
                            $row.find('.pn-cm-budget-col-phase').html(item.description);
                        } else {
                            $row.attr('data-item-quantity', parseFloat(item.quantity).toFixed(2));
                            $row.attr('data-item-unit-price', parseFloat(item.unit_price).toFixed(2));
                            $row.attr('data-item-optional', item.is_optional);
                            $row.attr('data-item-total', total.toFixed(2));

                            $row.find('.pn-cm-budget-col-desc').html(item.description);
                            var typeHtml = '<i class="material-icons-outlined">' + (item.item_type === 'hours' ? 'schedule' : 'payments') + '</i>';
                            $row.find('.pn-cm-budget-col-type').html(typeHtml);
                            // Update recurring badge.
                            $row.find('.pn-cm-budget-recurring-badge').remove();
                            if (parseInt(item.is_recurring, 10) && item.recurring_period) {
                                var editRlabel = (pnCmBudgetAdmin.recurringLabels || {})[item.recurring_period] || item.recurring_period;
                                $row.find('.pn-cm-budget-col-type').after('<span class="pn-cm-budget-recurring-badge"><i class="material-icons-outlined">autorenew</i> ' + escHtml(editRlabel) + '</span>');
                            }
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
                                        '<i class="material-icons-outlined pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="Required">lock</i>'
                                    );
                                }
                            }
                        }

                        recalculatePublicTotals();
                    } else {
                        // Admin dashboard: update the read-only table row.
                        var $row = $('.pn-customers-manager-budget-items-body [data-item-id="' + itemId + '"]');

                        // Update hidden inputs for form submission.
                        $row.find('input[name="pn_cm_budget_item_description[]"]').val(item.description);

                        // Update visible display.
                        $row.find('.pn-customers-manager-budget-item-desc-display').html(item.description);

                        if (!isPhase) {
                            $row.find('input[name="pn_cm_budget_item_type[]"]').val(item.item_type);
                            $row.find('.pn-customers-manager-budget-item-quantity').val(parseFloat(item.quantity).toFixed(2));
                            $row.find('.pn-customers-manager-budget-item-unit-price').val(parseFloat(item.unit_price).toFixed(2));
                            var optVal = parseInt(item.is_optional, 10) ? 1 : 0;
                            $row.find('input[name="pn_cm_budget_item_is_optional[]"]').val(optVal);

                            // Update recurring hidden inputs and badge.
                            var recVal = parseInt(item.is_recurring, 10) ? 1 : 0;
                            $row.find('input[name="pn_cm_budget_item_is_recurring[]"]').val(recVal);
                            $row.find('input[name="pn_cm_budget_item_recurring_period[]"]').val(item.recurring_period || '');

                            $row.find('.pn-customers-manager-budget-item-type-display').text(item.item_type === 'hours' ? 'Hours' : 'Fixed');
                            $row.find('.pn-cm-budget-recurring-badge').remove();
                            if (recVal && item.recurring_period) {
                                var adminEditRlabel = (pnCmBudgetAdmin.recurringLabels || {})[item.recurring_period] || item.recurring_period;
                                $row.find('.pn-customers-manager-budget-item-type-display').after(' <span class="pn-cm-budget-recurring-badge"><i class="material-icons-outlined" style="font-size:14px;vertical-align:middle;">autorenew</i> ' + escHtml(adminEditRlabel) + '</span>');
                            }
                            $row.find('.pn-customers-manager-budget-item-qty-display').text(formatNumber(item.quantity));
                            $row.find('.pn-customers-manager-budget-item-price-display').text(formatCurrency(item.unit_price));
                            $row.find('.pn-customers-manager-budget-item-line-total').text(formatCurrency(total));

                            var $optTd = $row.find('.pn-customers-manager-budget-col-optional');
                            if (optVal) {
                                $optTd.html('<i class="material-icons-outlined pn-customers-manager-vertical-align-middle pn-customers-manager-tooltip" style="color:#2271b1;font-size:18px;" data-pn-customers-manager-tooltip="Optional">check_box</i>');
                            } else {
                                $optTd.html('');
                            }
                        }

                        recalculateTotals();
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

        // --- Delete button (admin + public) ---
        $(document).on('click', '.pn-cm-budget-row-delete', function (e) {
            e.preventDefault();

            if (!confirm(pnCmBudgetAdmin.i18n.confirmDelete || 'Delete this item?')) return;

            var $row = $(this).closest('.pn-cm-budget-item-row, .pn-customers-manager-budget-item-row');
            var itemId = $row.data('item-id');
            var budgetId = getBudgetId($row);
            var isPhase = $row.hasClass('pn-cm-budget-item-phase') || $row.hasClass('pn-customers-manager-budget-item-phase');
            var $group = isPhase ? $row.closest('.pn-cm-budget-phase-group') : null;

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_remove_item',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                item_id: itemId
            }, function (response) {
                if (response.error_key === '') {
                    if (isPublicPage) {
                        if (isPhase && $group) {
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
                        $row.fadeOut(300, function () {
                            $(this).remove();
                            recalculateTotals();
                        });
                    }
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        if (isPublicPage) {

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

                // Only affect optional items (those with a toggle checkbox).
                $group.find('.pn-cm-budget-phase-items .pn-cm-budget-item-row').each(function () {
                    var $toggle = $(this).find('.pn-cm-budget-toggle-item');
                    if ($toggle.length) {
                        itemIds.push($(this).data('item-id'));
                    }
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
                        // Update visual state of optional items only.
                        $group.find('.pn-cm-budget-phase-items .pn-cm-budget-item-row').each(function () {
                            var $toggle = $(this).find('.pn-cm-budget-toggle-item');
                            if ($toggle.length) {
                                $(this).toggleClass('pn-cm-budget-item-deselected', !isChecked);
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

            /* ==============================================================
             * 13. Edit budget text fields (terms, others, client notes)
             * ============================================================*/

            $(document).on('click', '.pn-cm-budget-field-edit', function (e) {
                e.preventDefault();
                var fieldKey = $(this).data('field');
                var fieldLabel = $(this).data('label');
                var $block = $(this).closest('[id]');
                var currentValue = $block.find('.pn-cm-budget-field-content').html() || '';

                // Strip the empty placeholder if present.
                if ($block.find('.pn-cm-budget-field-empty').length) {
                    currentValue = '';
                }

                var $popup = createPopupShell();
                var formHtml = '<div class="pn-cm-budget-edit-item-popup pn-customers-manager-p-30" data-field-key="' + escHtml(fieldKey) + '">'
                    + '<h4 class="pn-customers-manager-mb-30">' + escHtml(fieldLabel) + '</h4>'
                    + '<div class="pn-customers-manager-input-wrapper pn-customers-manager-mb-15">'
                    + '<textarea id="pn_cm_field_editor" class="pn-customers-manager-wysiwyg pn-customers-manager-input pn-customers-manager-width-100-percent">' + currentValue + '</textarea>'
                    + '</div>'
                    + '<div class="pn-customers-manager-text-align-right pn-customers-manager-mt-15">'
                    + '<a href="#" class="pn-customers-manager-btn pn-cm-budget-field-save">' + (pnCmBudgetAdmin.i18n.save || 'Save') + '</a>'
                    + '</div></div>';

                $popup.find('.pn-customers-manager-popup-content').html(formHtml);
                addCloseButton($popup);
                initPopupEditors($popup);
            });

            $(document).on('click', '.pn-cm-budget-field-save', function (e) {
                e.preventDefault();
                var $wrapper = $(this).closest('.pn-cm-budget-edit-item-popup');
                var fieldKey = $wrapper.data('field-key');
                var $popup = $(this).closest('.pn-customers-manager-popup');
                var value = getEditorValue($wrapper.find('#pn_cm_field_editor'));

                var $btn = $(this);
                $btn.addClass('pn-customers-manager-disabled');

                $.post(pnCmBudgetAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_budget_update_field',
                    pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                    budget_id: pnCmBudgetAdmin.budgetId,
                    field_key: fieldKey,
                    field_value: value
                }, function (response) {
                    $btn.removeClass('pn-customers-manager-disabled');
                    if (response.error_key === '') {
                        // Find the block and update content.
                        var $editLink = $('.pn-cm-budget-field-edit[data-field="' + fieldKey + '"]');
                        var $block = $editLink.closest('[id]');
                        var $content = $block.find('.pn-cm-budget-field-content');

                        if ($.trim(response.value)) {
                            $content.html(response.value).removeClass('pn-cm-budget-field-empty');
                        } else {
                            $content.html('<em>—</em>').addClass('pn-cm-budget-field-empty');
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

            /* ==============================================================
             * 14. Change budget status from front-end
             * ============================================================*/

            $(document).on('change', '.pn-cm-budget-status-select', function () {
                var $select = $(this);
                var newStatus = $select.val();
                var previousStatus = $select.data('current');

                if (newStatus === previousStatus) return;

                $select.prop('disabled', true);

                $.post(pnCmBudgetAdmin.ajaxUrl, {
                    action: 'pn_customers_manager_ajax',
                    pn_customers_manager_ajax_type: 'pn_cm_budget_update_field',
                    pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                    budget_id: pnCmBudgetAdmin.budgetId,
                    field_key: 'pn_cm_budget_status',
                    field_value: newStatus
                }, function (response) {
                    $select.prop('disabled', false);
                    if (response.error_key === '') {
                        $select.data('current', newStatus);
                        var $badge = $select.siblings('.pn-cm-budget-status');
                        // Update badge class and text.
                        $badge.removeClass('pn-cm-budget-status-draft pn-cm-budget-status-sent pn-cm-budget-status-accepted pn-cm-budget-status-rejected')
                              .addClass('pn-cm-budget-status-' + newStatus)
                              .text($select.find('option:selected').text());
                    } else {
                        $select.val(previousStatus);
                        alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                    }
                }).fail(function () {
                    $select.prop('disabled', false);
                    $select.val(previousStatus);
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                });
            });

        } // end isPublicPage

        // Initial calculation on page load (admin popup only – public totals are server-rendered).
        if (!isPublicPage) {
            recalculateTotals();
        }

        /* ------------------------------------------------------------------
         * Export items
         * ----------------------------------------------------------------*/
        $(document).on('click', '.pn-cm-budget-export-items', function (e) {
            e.preventDefault();
            var budgetId = getBudgetId($(this)) || $(this).data('budget-id');
            if (!budgetId) return;

            // Build a hidden form and submit it to trigger the CSV download.
            var $form = $('<form>', {
                method: 'POST',
                action: pnCmBudgetAdmin.ajaxUrl,
                css: { display: 'none' }
            });
            $form.append($('<input>', { type: 'hidden', name: 'action', value: 'pn_customers_manager_ajax' }));
            $form.append($('<input>', { type: 'hidden', name: 'pn_customers_manager_ajax_type', value: 'pn_cm_budget_export_items' }));
            $form.append($('<input>', { type: 'hidden', name: 'pn_customers_manager_ajax_nonce', value: pnCmBudgetAdmin.nonce }));
            $form.append($('<input>', { type: 'hidden', name: 'budget_id', value: budgetId }));
            $('body').append($form);
            $form.submit();
            $form.remove();
        });

        /* ------------------------------------------------------------------
         * Import items
         * ----------------------------------------------------------------*/
        $(document).on('click', '.pn-cm-budget-import-items', function (e) {
            e.preventDefault();
            console.log('[budget-import] Import button clicked');
            var budgetId = getBudgetId($(this)) || $(this).data('budget-id');
            console.log('[budget-import] Budget ID resolved:', budgetId, '| from getBudgetId:', getBudgetId($(this)), '| from data-attr:', $(this).data('budget-id'));
            if (!budgetId) {
                console.error('[budget-import] No budget ID found, aborting.');
                return;
            }

            var $popup = $('<div class="pn-customers-manager-popup pn-customers-manager-popup-size-medium pn-customers-manager-display-none-soft">'
                + '<div class="pn-customers-manager-popup-overlay"></div>'
                + '<div class="pn-customers-manager-popup-content">'
                + '<div class="pn-cm-budget-import-popup-inner">'
                + '<h3>' + (pnCmBudgetAdmin.i18n.importItemsTitle || 'Import items from CSV') + '</h3>'
                + '<div class="pn-cm-budget-import-dropzone" id="pn-cm-budget-import-dropzone">'
                + '<i class="material-icons-outlined" style="font-size:48px;color:#999;">upload_file</i>'
                + '<p>' + (pnCmBudgetAdmin.i18n.importDropText || 'Drag & drop a CSV file here or click to select') + '</p>'
                + '</div>'
                + '<input type="file" accept=".csv,text/csv" class="pn-cm-budget-import-file" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);opacity:0;" />'
                + '<div class="pn-cm-budget-import-status" style="display:none;"></div>'
                + '</div>'
                + '</div></div>');

            $('body').append($popup);

            // Open via the plugin Popups system (adds pn-customers-manager-popup-active → display:block).
            if (typeof pn_customers_manager_Popups !== 'undefined') {
                pn_customers_manager_Popups.open($popup, {
                    afterClose: function () { $popup.remove(); }
                });
            } else {
                // Fallback: force display manually.
                $popup.removeClass('pn-customers-manager-display-none-soft').css('display', 'block');
            }

            var $dropzone = $popup.find('#pn-cm-budget-import-dropzone');
            var $fileInput = $popup.find('.pn-cm-budget-import-file');
            var $status = $popup.find('.pn-cm-budget-import-status');

            console.log('[budget-import] Popup opened. Display:', $popup.css('display'), '| Dropzone:', $dropzone.length, '| FileInput:', $fileInput.length);

            // Close on overlay click.
            $popup.find('.pn-customers-manager-popup-overlay').on('click', function () {
                closePopup($popup);
            });

            // Add close button.
            var $closeBtn = $('<button class="pn-customers-manager-popup-close-wrapper" type="button"><i class="material-icons-outlined">close</i></button>');
            $closeBtn.on('click', function (ev) {
                ev.preventDefault();
                closePopup($popup);
            });
            $popup.find('.pn-customers-manager-popup-content').prepend($closeBtn);

            // Click to select file.
            $dropzone.on('click', function () {
                console.log('[budget-import] Dropzone clicked, triggering file input');
                $fileInput[0].click();
            });

            // Drag & drop events.
            $dropzone.on('dragover dragenter', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                $(this).addClass('pn-cm-budget-import-dropzone-active');
            }).on('dragleave drop', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                $(this).removeClass('pn-cm-budget-import-dropzone-active');
            }).on('drop', function (ev) {
                var files = ev.originalEvent.dataTransfer.files;
                if (files.length) {
                    handleImportFile(files[0], budgetId, $popup, $status);
                }
            });

            $fileInput.on('change', function () {
                console.log('[budget-import] File input changed, files:', this.files.length);
                if (this.files.length) {
                    handleImportFile(this.files[0], budgetId, $popup, $status);
                }
            });
        });

        function handleImportFile(file, budgetId, $popup, $status) {
            console.log('[budget-import] handleImportFile called', { fileName: file.name, fileSize: file.size, fileType: file.type, budgetId: budgetId });
            if (!file.name.toLowerCase().endsWith('.csv')) {
                console.error('[budget-import] Invalid file extension:', file.name);
                $status.show().html('<p style="color:#d32f2f;">' + (pnCmBudgetAdmin.i18n.importInvalidFile || 'Please select a valid CSV file.') + '</p>');
                return;
            }

            var $dropzone = $popup.find('#pn-cm-budget-import-dropzone');
            $dropzone.html('<div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>');

            var formData = new FormData();
            formData.append('action', 'pn_customers_manager_ajax');
            formData.append('pn_customers_manager_ajax_type', 'pn_cm_budget_import_items');
            formData.append('pn_customers_manager_ajax_nonce', pnCmBudgetAdmin.nonce);
            formData.append('budget_id', budgetId);
            formData.append('csv_file', file);

            console.log('[budget-import] Sending AJAX request to:', pnCmBudgetAdmin.ajaxUrl);
            $.ajax({
                url: pnCmBudgetAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    console.log('[budget-import] AJAX response:', response);
                    if (response.error_key === '' && response.items) {
                        var $tbody = getItemsTbody();
                        var $lastGroup = null;

                        $.each(response.items, function (i, item) {
                            var rowHtml = buildItemRow(item);

                            if (isPublicPage) {
                                if (item.item_type === 'phase') {
                                    var $phase = $(rowHtml);
                                    $tbody.append($phase);
                                    initPhaseItemsSortable($phase.find('.pn-cm-budget-phase-items'));
                                    $lastGroup = $phase;
                                } else {
                                    if ($lastGroup && $lastGroup.length) {
                                        $lastGroup.find('.pn-cm-budget-phase-items').append(rowHtml);
                                    } else {
                                        // Try existing last phase group.
                                        var $existing = $tbody.children('.pn-cm-budget-phase-group:last');
                                        if ($existing.length) {
                                            $existing.find('.pn-cm-budget-phase-items').append(rowHtml);
                                        } else {
                                            $tbody.append(rowHtml);
                                        }
                                    }
                                }
                            } else {
                                $tbody.append(rowHtml);
                            }
                        });

                        recalculateTotals();
                        initCompareSliders();
                        if (isPublicPage) saveOrder();

                        $dropzone.html('<i class="material-icons-outlined" style="font-size:48px;color:#4caf50;">check_circle</i>'
                            + '<p>' + (response.count || 0) + ' ' + (pnCmBudgetAdmin.i18n.importSuccess || 'items imported successfully.') + '</p>');
                        setTimeout(function () {
                            closePopup($popup);
                        }, 1500);
                    } else {
                        console.error('[budget-import] Server returned error:', response.error_key, response.error_content);
                        $status.show().html('<p style="color:#d32f2f;">' + (response.error_content || pnCmBudgetAdmin.i18n.error || 'An error occurred.') + '</p>');
                        $dropzone.html('<i class="material-icons-outlined" style="font-size:48px;color:#999;">upload_file</i>'
                            + '<p>' + (pnCmBudgetAdmin.i18n.importDropText || 'Drag & drop a CSV file here or click to select') + '</p>');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('[budget-import] AJAX fail:', textStatus, errorThrown, jqXHR.responseText);
                    $status.show().html('<p style="color:#d32f2f;">' + (pnCmBudgetAdmin.i18n.error || 'An error occurred.') + '</p>');
                    $dropzone.html('<i class="material-icons-outlined" style="font-size:48px;color:#999;">upload_file</i>'
                        + '<p>' + (pnCmBudgetAdmin.i18n.importDropText || 'Drag & drop a CSV file here or click to select') + '</p>');
                }
            });
        }

        /* ------------------------------------------------------------------
         * AI prompt toggle & copy
         * ----------------------------------------------------------------*/
        $(document).on('click', '.pn-cm-budget-ai-prompt-toggle', function (e) {
            e.preventDefault();
            var $block = $(this).closest('.pn-customers-manager-budget-items-editor, .pn-cm-budget-items-section').find('.pn-cm-budget-ai-prompt-block');
            if (!$block.length) {
                $block = $(this).parent().siblings('.pn-cm-budget-ai-prompt-block');
            }
            if (!$block.length) {
                $block = $(this).closest('.pn-cm-budget-admin-buttons').siblings('.pn-cm-budget-ai-prompt-block');
            }
            $block.slideToggle(200);
        });

        $(document).on('click', '.pn-cm-budget-ai-prompt-copy', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var text = $btn.closest('.pn-cm-budget-ai-prompt-block').find('.pn-cm-budget-ai-prompt-text').text();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    var original = $btn.text();
                    $btn.text(pnCmBudgetAdmin.i18n.copied || 'Copied!');
                    setTimeout(function () { $btn.text(original); }, 1500);
                });
            } else {
                // Fallback for older browsers.
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
                var original = $btn.text();
                $btn.text(pnCmBudgetAdmin.i18n.copied || 'Copied!');
                setTimeout(function () { $btn.text(original); }, 1500);
            }
        });

        /* ==============================================================
         * Bulk select & delete
         * ============================================================*/

        // Toggle selection mode.
        $(document).on('click', '.pn-cm-budget-bulk-select-toggle', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var entering = !$btn.hasClass('active');
            var $container = $('#pn-cm-budget-items-body').closest('.pn-cm-budget-items');
            var $adminBtns = $btn.closest('.pn-cm-budget-admin-buttons');

            $btn.toggleClass('active', entering);
            $btn.find('i').text(entering ? 'close' : 'checklist');
            $container.toggleClass('pn-cm-budget-selecting', entering);
            $adminBtns.toggleClass('pn-cm-budget-selecting', entering);

            if (entering) {
                // Inject checkboxes replacing the drag handle column.
                $('#pn-cm-budget-items-body .pn-cm-budget-item-row, #pn-cm-budget-images-gallery .pn-cm-budget-item-row').each(function () {
                    if (!$(this).find('.pn-cm-budget-bulk-checkbox').length) {
                        var $drag = $(this).find('.pn-cm-budget-col-drag');
                        if ($drag.length) {
                            $drag.before('<span class="pn-cm-budget-bulk-checkbox"><input type="checkbox" class="pn-cm-budget-bulk-check"></span>');
                        } else {
                            $(this).prepend('<span class="pn-cm-budget-bulk-checkbox"><input type="checkbox" class="pn-cm-budget-bulk-check"></span>');
                        }
                    }
                });
            } else {
                // Remove all checkboxes and selection highlights.
                $('.pn-cm-budget-bulk-checkbox').remove();
                $('.pn-cm-budget-bulk-checked').removeClass('pn-cm-budget-bulk-checked');
            }
        });

        // Shift+click range selection.
        var lastCheckedIndex = null;

        $(document).on('click', '.pn-cm-budget-bulk-check', function (e) {
            var $allChecks = $('.pn-cm-budget-bulk-check');
            var currentIndex = $allChecks.index(this);

            if (e.shiftKey && lastCheckedIndex !== null && lastCheckedIndex !== currentIndex) {
                var start = Math.min(lastCheckedIndex, currentIndex);
                var end = Math.max(lastCheckedIndex, currentIndex);
                var checked = $(this).is(':checked');
                $allChecks.slice(start, end + 1).each(function () {
                    $(this).prop('checked', checked);
                    $(this).closest('.pn-cm-budget-item-row').toggleClass('pn-cm-budget-bulk-checked', checked);
                });
                syncBulkToggles();
            }

            lastCheckedIndex = currentIndex;
        });

        // Sync the bulk optional/recurring slide toggles with current selection state.
        function syncBulkToggles() {
            var $checked = $('.pn-cm-budget-bulk-check:checked');
            if (!$checked.length) {
                $('.pn-cm-budget-bulk-optional-toggle').prop('checked', false);
                $('.pn-cm-budget-bulk-recurring-toggle').prop('checked', false);
                return;
            }
            var allOptional = true, allRecurring = true;
            $checked.each(function () {
                var $row = $(this).closest('.pn-cm-budget-item-row');
                var type = $row.data('item-type');
                // Skip phases and images — they don't have optional/recurring properties.
                if (type === 'phase' || type === 'image' || type === 'image_compare') return;
                if ($row.data('item-optional') != 1) allOptional = false;
                if ($row.data('item-recurring') != 1) allRecurring = false;
            });
            $('.pn-cm-budget-bulk-optional-toggle').prop('checked', allOptional);
            $('.pn-cm-budget-bulk-recurring-toggle').prop('checked', allRecurring);
        }

        // Toggle highlight on checkbox change.
        $(document).on('change', '.pn-cm-budget-bulk-check', function () {
            $(this).closest('.pn-cm-budget-item-row').toggleClass('pn-cm-budget-bulk-checked', $(this).is(':checked'));
            syncBulkToggles();
        });

        // Bulk delete.
        $(document).on('click', '.pn-cm-budget-bulk-delete', function (e) {
            e.preventDefault();

            var $checked = $('.pn-cm-budget-bulk-check:checked');
            if (!$checked.length) {
                alert(pnCmBudgetAdmin.i18n.noItemsSelected || 'No items selected.');
                return;
            }
            if (!confirm(pnCmBudgetAdmin.i18n.confirmBulkDelete || 'Are you sure you want to delete the selected items?')) {
                return;
            }

            var itemIds = [];
            var $rows = $();
            $checked.each(function () {
                var $row = $(this).closest('.pn-cm-budget-item-row');
                var id = $row.data('item-id');
                if (id) {
                    itemIds.push(id);
                    // If it's a phase row, also collect the whole phase group.
                    if ($row.hasClass('pn-cm-budget-item-phase')) {
                        $rows = $rows.add($row.closest('.pn-cm-budget-phase-group'));
                    } else {
                        $rows = $rows.add($row);
                    }
                }
            });

            if (!itemIds.length) return;

            var budgetId = getBudgetId($rows.first());

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_bulk_remove_items',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                item_ids: JSON.stringify(itemIds)
            }, function (response) {
                if (response.error_key === '') {
                    $rows.fadeOut(300, function () {
                        $(this).remove();
                        if (isPublicPage) {
                            recalculatePublicTotals();
                            saveOrder();
                        } else {
                            recalculateTotals();
                        }
                    });
                    // Exit selection mode.
                    $('.pn-cm-budget-bulk-select-toggle').trigger('click');
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        // Bulk toggle optional / recurring.
        function bulkUpdateField(field, value) {
            var $checked = $('.pn-cm-budget-bulk-check:checked');
            if (!$checked.length) {
                alert(pnCmBudgetAdmin.i18n.noItemsSelected || 'No items selected.');
                return;
            }

            var itemIds = [];
            $checked.each(function () {
                var $row = $(this).closest('.pn-cm-budget-item-row');
                var type = $row.data('item-type');
                // Skip phases and images — they don't have optional/recurring properties.
                if (type === 'phase' || type === 'image' || type === 'image_compare') return;
                var id = $row.data('item-id');
                if (id) itemIds.push(id);
            });
            if (!itemIds.length) return;

            var budgetId = getBudgetId($checked.first().closest('.pn-cm-budget-item-row'));

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_bulk_update_items',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                item_ids: JSON.stringify(itemIds),
                field: field,
                value: value ? 1 : 0
            }, function (response) {
                if (response.error_key === '') {
                    // Update the UI on each affected row.
                    $checked.each(function () {
                        var $row = $(this).closest('.pn-cm-budget-item-row');
                        if (field === 'is_optional') {
                            $row.attr('data-optional', value ? '1' : '0');
                            var $toggle = $row.find('.pn-cm-budget-optional-toggle input[type="checkbox"]');
                            if (value && !$toggle.length) {
                                // Optionality was added — reload to reflect changes.
                            }
                        }
                        if (field === 'is_recurring') {
                            $row.attr('data-recurring', value ? '1' : '0');
                        }
                    });
                    if (isPublicPage) {
                        recalculatePublicTotals();
                        saveOrder();
                    } else {
                        recalculateTotals();
                    }
                    // Reload page to reflect UI changes cleanly.
                    location.reload();
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }).fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        }

        $(document).on('change', '.pn-cm-budget-bulk-optional-toggle', function () {
            var value = $(this).is(':checked');
            var msg = value
                ? (pnCmBudgetAdmin.i18n.bulkOptionalOn || 'Mark selected items as optional?')
                : (pnCmBudgetAdmin.i18n.bulkOptionalOff || 'Remove optional from selected items?');
            if (!confirm(msg)) {
                $(this).prop('checked', !value);
                return;
            }
            bulkUpdateField('is_optional', value);
        });

        $(document).on('change', '.pn-cm-budget-bulk-recurring-toggle', function () {
            var value = $(this).is(':checked');
            var msg = value
                ? (pnCmBudgetAdmin.i18n.bulkRecurringOn || 'Mark selected items as recurring?')
                : (pnCmBudgetAdmin.i18n.bulkRecurringOff || 'Remove recurring from selected items?');
            if (!confirm(msg)) {
                $(this).prop('checked', !value);
                return;
            }
            bulkUpdateField('is_recurring', value);
        });

        // ── Helper: update status badge/select after payment changes ──
        function updateBudgetStatus(newStatus) {
            if (!newStatus) return;
            var statusLabels = {
                draft: pnCmBudgetAdmin.i18n.statusDraft || 'Draft',
                sent: pnCmBudgetAdmin.i18n.statusSent || 'Sent',
                accepted: pnCmBudgetAdmin.i18n.statusAccepted || 'Accepted',
                rejected: pnCmBudgetAdmin.i18n.statusRejected || 'Rejected',
                paid: pnCmBudgetAdmin.i18n.statusPaid || 'Paid'
            };
            // Public page status badge.
            var $badge = $('.pn-cm-budget-status');
            if ($badge.length) {
                $badge.removeClass('pn-cm-budget-status-draft pn-cm-budget-status-sent pn-cm-budget-status-accepted pn-cm-budget-status-rejected pn-cm-budget-status-paid')
                      .addClass('pn-cm-budget-status-' + newStatus)
                      .text(statusLabels[newStatus] || newStatus);
            }
            var $select = $('.pn-cm-budget-status-select');
            if ($select.length) {
                $select.val(newStatus).data('current', newStatus);
            }
        }

        // ── Payments: add ──
        $(document).on('click', '.pn-cm-payments-container[data-type="budget"] .pn-cm-payment-add-btn, .pn-cm-budget-payment-add-btn', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.pn-cm-payments-container, .pn-cm-budget-payments');
            var budgetId = $container.data('post-id') || pnCmBudgetAdmin.budgetId;
            var $form = $(this).closest('.pn-cm-payments-add-form, .pn-cm-budget-payments-add-form');
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
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                return;
            }

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_add_payment',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
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
                    if (response.status) {
                        updateBudgetStatus(response.status);
                    }
                } else {
                    alert((response && response.error_content) || pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        // ── Payments: remove ──
        $(document).on('click', '.pn-cm-payments-container[data-type="budget"] .pn-cm-payment-remove, .pn-cm-budget-payment-remove', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.pn-cm-payments-container, .pn-cm-budget-payments');
            var budgetId = $container.data('post-id') || pnCmBudgetAdmin.budgetId;
            var paymentId = $(this).data('payment-id');

            if (!confirm(pnCmBudgetAdmin.i18n.confirmDelete || 'Are you sure?')) {
                return;
            }

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_remove_payment',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                payment_id: paymentId
            }, function (response) {
                if (response && response.error_key === '') {
                    if (response.html) {
                        $container.replaceWith(response.html);
                    } else {
                        location.reload();
                    }
                    if (response.status) {
                        updateBudgetStatus(response.status);
                    }
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        // ── Payments: toggle visibility ──
        $(document).on('click', '.pn-cm-payments-container[data-type="budget"] .pn-cm-payment-toggle-visibility, .pn-cm-budget-payments .pn-cm-payment-toggle-visibility', function (e) {
            e.preventDefault();
            var $container = $(this).closest('.pn-cm-payments-container, .pn-cm-budget-payments');
            var budgetId = $container.data('post-id') || pnCmBudgetAdmin.budgetId;
            var paymentId = $(this).data('payment-id');

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_toggle_payment_visibility',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
                payment_id: paymentId
            }, function (response) {
                if (response && response.error_key === '') {
                    if (response.html) {
                        $container.replaceWith(response.html);
                    } else {
                        location.reload();
                    }
                } else {
                    alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
            });
        });

        // ── Payments: edit (inline) ──
        $(document).on('click', '.pn-cm-payments-container[data-type="budget"] .pn-cm-payment-edit, .pn-cm-budget-payments .pn-cm-payment-edit', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            if ($row.hasClass('pn-cm-payment-editing')) return;
            $row.addClass('pn-cm-payment-editing');

            var d = $row.data();
            var cells = $row.children('td');

            // Store original HTML for cancel
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
        $(document).on('click', '.pn-cm-payments-container[data-type="budget"] .pn-cm-payment-edit-cancel, .pn-cm-budget-payments .pn-cm-payment-edit-cancel', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            $row.html($row.data('original-html'));
            $row.removeClass('pn-cm-payment-editing');
        });

        // ── Payments: edit save ──
        $(document).on('click', '.pn-cm-payments-container[data-type="budget"] .pn-cm-payment-edit-save, .pn-cm-budget-payments .pn-cm-payment-edit-save', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            var $container = $row.closest('.pn-cm-payments-container, .pn-cm-budget-payments');
            var budgetId = $container.data('post-id') || pnCmBudgetAdmin.budgetId;
            var paymentId = $row.data('payment-id');

            var amount = $row.find('.pn-cm-payment-edit-amount').val();
            if (!amount || parseFloat(amount) <= 0) {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                return;
            }

            $.post(pnCmBudgetAdmin.ajaxUrl, {
                action: 'pn_customers_manager_ajax',
                pn_customers_manager_ajax_type: 'pn_cm_budget_edit_payment',
                pn_customers_manager_ajax_nonce: pnCmBudgetAdmin.nonce,
                budget_id: budgetId,
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
                    if (response.status) {
                        updateBudgetStatus(response.status);
                    }
                } else {
                    alert((response && response.error_content) || pnCmBudgetAdmin.i18n.error || 'An error occurred.');
                }
            }, 'json').fail(function () {
                alert(pnCmBudgetAdmin.i18n.error || 'An error occurred.');
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
            var remaining = parseFloat($container.attr('data-remaining')) || 0;
            var expCats = {};
            var catLabels = {};
            try { expCats = JSON.parse($container.attr('data-expense-categories') || '{}'); } catch(e) {}
            try { catLabels = JSON.parse($container.attr('data-category-labels') || '{}'); } catch(e) {}

            // Balance chart
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

            // Categories chart
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

        // Init charts on page load
        $('.pn-cm-payments-container, .pn-cm-budget-payments').each(function () {
            initPaymentCharts($(this));
        });

    }); // end document.ready

})(jQuery);
