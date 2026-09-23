/**
 * PN Customers Manager - Public Budget Page
 *
 * Handles optional item toggling, budget accept/reject,
 * print, and live total recalculation.
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
            if (pnCmBudget.currencyPosition === 'after') {
                return formatted + ' ' + pnCmBudget.currencySymbol;
            }

            return pnCmBudget.currencySymbol + formatted;
        }

        /**
         * Recalculate and update subtotal, discount, tax and total
         * based on the currently selected items.
         */
        function recalculateTotals() {
            var subtotal = 0;

            $('.pn-cm-budget-item-row').each(function () {
                var $row = $(this);

                // Skip phase rows — they are section headers with no price.
                if ($row.hasClass('pn-cm-budget-item-phase')) {
                    return;
                }

                var $checkbox = $row.find('.pn-cm-budget-toggle-item');

                // Include the row if it has no checkbox (non-optional)
                // or if its checkbox is checked
                if (!$checkbox.length || $checkbox.is(':checked')) {
                    var rowTotal = parseFloat($row.data('item-total')) || 0;
                    subtotal += rowTotal;
                }
            });

            var discountRate  = parseFloat(pnCmBudget.discountRate) || 0;
            var taxRate        = parseFloat(pnCmBudget.taxRate) || 0;

            var discountAmount = subtotal * discountRate / 100;
            var taxAmount      = (subtotal - discountAmount) * taxRate / 100;
            var total          = subtotal - discountAmount + taxAmount;

            $('#pn-cm-budget-subtotal').text(formatCurrency(subtotal));
            $('#pn-cm-budget-discount').text(formatCurrency(discountAmount));
            $('#pn-cm-budget-tax').text(formatCurrency(taxAmount));
            $('#pn-cm-budget-total').text(formatCurrency(total));
        }

        // -------------------------------------------------------
        // 1. Toggle optional items
        // -------------------------------------------------------

        $(document).on('change', '.pn-cm-budget-toggle-item', function () {
            // When admin JS is loaded, it handles this event via private AJAX.
            if (typeof pnCmBudgetAdmin !== 'undefined') return;

            var $checkbox = $(this);
            var itemId    = $checkbox.data('item-id');
            var checked   = $checkbox.is(':checked');
            var $row      = $checkbox.closest('.pn-cm-budget-item-row');

            // Immediate visual feedback
            $row.toggleClass('pn-cm-budget-item-deselected', !checked);

            // Live client-side recalculation
            recalculateTotals();

            // Persist via AJAX
            $.post(pnCmBudget.ajaxUrl, {
                action: 'pn_customers_manager_ajax_nopriv',
                pn_customers_manager_ajax_nopriv_type: 'pn_cm_budget_toggle_item',
                pn_customers_manager_ajax_nopriv_nonce: pnCmBudget.nonce,
                budget_id: pnCmBudget.budgetId,
                budget_token: pnCmBudget.budgetToken,
                item_id: itemId,
                is_selected: checked ? 1 : 0,
            }, function (response) {
                if (response.success && response.data) {
                    // If the server returns updated totals, apply them
                    if (typeof response.data.subtotal !== 'undefined') {
                        $('#pn-cm-budget-subtotal').text(formatCurrency(parseFloat(response.data.subtotal)));
                    }
                    if (typeof response.data.discount !== 'undefined') {
                        $('#pn-cm-budget-discount').text(formatCurrency(parseFloat(response.data.discount)));
                    }
                    if (typeof response.data.tax !== 'undefined') {
                        $('#pn-cm-budget-tax').text(formatCurrency(parseFloat(response.data.tax)));
                    }
                    if (typeof response.data.total !== 'undefined') {
                        $('#pn-cm-budget-total').text(formatCurrency(parseFloat(response.data.total)));
                    }
                }
            }, 'json').fail(function () {
                // Revert on failure
                $checkbox.prop('checked', !checked);
                $row.toggleClass('pn-cm-budget-item-deselected', checked);
                recalculateTotals();
                alert(pnCmBudget.i18n.error);
            });
        });

        // -------------------------------------------------------
        // 2. Accept budget
        // -------------------------------------------------------

        function sendAcceptRequest(acceptorEmail) {
            var data = {
                action: 'pn_customers_manager_ajax_nopriv',
                pn_customers_manager_ajax_nopriv_type: 'pn_cm_budget_accept',
                pn_customers_manager_ajax_nopriv_nonce: pnCmBudget.nonce,
                budget_id: pnCmBudget.budgetId,
                budget_token: pnCmBudget.budgetToken,
            };
            if (acceptorEmail) {
                data.acceptor_email = acceptorEmail;
            }

            $.post(pnCmBudget.ajaxUrl, data, function (response) {
                if (response.success) {
                    alert(pnCmBudget.i18n.accepted);
                    location.reload();
                } else {
                    alert(response.data && response.data.message ? response.data.message : pnCmBudget.i18n.error);
                }
            }, 'json').fail(function () {
                alert(pnCmBudget.i18n.error);
            });
        }

        $(document).on('click', '#pn-cm-budget-accept', function (e) {
            e.preventDefault();

            if (!confirm(pnCmBudget.i18n.confirmAccept)) {
                return;
            }

            // Logged-in users: accept directly.
            if (pnCmBudget.isLoggedIn) {
                sendAcceptRequest('');
                return;
            }

            // Non-logged-in users: show email prompt first.
            var $prompt = $('#pn-cm-budget-email-prompt');
            if ($prompt.length) {
                $prompt.slideDown(200);
                $('#pn-cm-budget-acceptor-email').focus();
            } else {
                // Fallback if HTML not present.
                sendAcceptRequest('');
            }
        });

        // Confirm accept with email.
        $(document).on('click', '#pn-cm-budget-confirm-accept', function (e) {
            e.preventDefault();
            var $input = $('#pn-cm-budget-acceptor-email');
            var email  = $.trim($input.val());

            if (!email) {
                alert(pnCmBudget.i18n.emailRequired);
                $input.focus();
                return;
            }

            // Basic email validation.
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert(pnCmBudget.i18n.emailInvalid);
                $input.focus();
                return;
            }

            sendAcceptRequest(email);
        });

        // Allow pressing Enter in the email input.
        $(document).on('keydown', '#pn-cm-budget-acceptor-email', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#pn-cm-budget-confirm-accept').trigger('click');
            }
        });

        // -------------------------------------------------------
        // 3. Reject budget
        // -------------------------------------------------------

        $(document).on('click', '#pn-cm-budget-reject', function (e) {
            e.preventDefault();

            if (!confirm(pnCmBudget.i18n.confirmReject)) {
                return;
            }

            $.post(pnCmBudget.ajaxUrl, {
                action: 'pn_customers_manager_ajax_nopriv',
                pn_customers_manager_ajax_nopriv_type: 'pn_cm_budget_reject',
                pn_customers_manager_ajax_nopriv_nonce: pnCmBudget.nonce,
                budget_id: pnCmBudget.budgetId,
                budget_token: pnCmBudget.budgetToken,
            }, function (response) {
                if (response.success) {
                    alert(pnCmBudget.i18n.rejected);
                    location.reload();
                } else {
                    alert(pnCmBudget.i18n.error);
                }
            }, 'json').fail(function () {
                alert(pnCmBudget.i18n.error);
            });
        });

        // -------------------------------------------------------
        // 4. Print
        // -------------------------------------------------------

        $(document).on('click', '#pn-cm-budget-print', function (e) {
            e.preventDefault();
            window.print();
        });

        // -------------------------------------------------------
        // 5. Phase collapse / expand
        // -------------------------------------------------------

        $(document).on('click', '.pn-cm-budget-phase-toggle', function () {
            // When admin JS is loaded, it handles this event.
            if (typeof pnCmBudgetAdmin !== 'undefined') return;

            var $group = $(this).closest('.pn-cm-budget-phase-group');
            $group.toggleClass('pn-cm-budget-collapsed');
            // Icon rotation handled via CSS transform when collapsed.
        });

        // -------------------------------------------------------
        // 6. Phase toggle (activate/deactivate all items in phase)
        // -------------------------------------------------------

        $(document).on('change', '.pn-cm-budget-phase-switch-input', function () {
            // When admin JS is loaded, it handles this event via private AJAX.
            if (typeof pnCmBudgetAdmin !== 'undefined') return;

            var isChecked = $(this).is(':checked');
            var $group    = $(this).closest('.pn-cm-budget-phase-group');
            var itemIds   = [];

            // Only affect optional items (those with a toggle checkbox).
            $group.find('.pn-cm-budget-phase-items .pn-cm-budget-item-row').each(function () {
                var $toggle = $(this).find('.pn-cm-budget-toggle-item');
                if ($toggle.length) {
                    itemIds.push($(this).data('item-id'));
                }
            });

            if (!itemIds.length) return;

            // Update individual toggles immediately for visual feedback.
            $group.find('.pn-cm-budget-phase-items .pn-cm-budget-item-row').each(function () {
                var $toggle = $(this).find('.pn-cm-budget-toggle-item');
                if ($toggle.length) {
                    $(this).toggleClass('pn-cm-budget-item-deselected', !isChecked);
                    $toggle.prop('checked', isChecked);
                }
            });

            recalculateTotals();

            // Persist via AJAX.
            $.post(pnCmBudget.ajaxUrl, {
                action: 'pn_customers_manager_ajax_nopriv',
                pn_customers_manager_ajax_nopriv_type: 'pn_cm_budget_toggle_phase_items',
                pn_customers_manager_ajax_nopriv_nonce: pnCmBudget.nonce,
                budget_id: pnCmBudget.budgetId,
                budget_token: pnCmBudget.budgetToken,
                item_ids: itemIds,
                is_selected: isChecked ? 1 : 0
            }, function (response) {
                if (response.success && response.data) {
                    if (typeof response.data.subtotal !== 'undefined') {
                        $('#pn-cm-budget-subtotal').text(formatCurrency(parseFloat(response.data.subtotal)));
                    }
                    if (typeof response.data.discount !== 'undefined') {
                        $('#pn-cm-budget-discount').text(formatCurrency(parseFloat(response.data.discount)));
                    }
                    if (typeof response.data.tax !== 'undefined') {
                        $('#pn-cm-budget-tax').text(formatCurrency(parseFloat(response.data.tax)));
                    }
                    if (typeof response.data.total !== 'undefined') {
                        $('#pn-cm-budget-total').text(formatCurrency(parseFloat(response.data.total)));
                    }
                }
            }, 'json').fail(function () {
                // Revert on failure — only optional items.
                $group.find('.pn-cm-budget-phase-items .pn-cm-budget-item-row').each(function () {
                    var $toggle = $(this).find('.pn-cm-budget-toggle-item');
                    if ($toggle.length) {
                        $(this).toggleClass('pn-cm-budget-item-deselected', isChecked);
                        $toggle.prop('checked', !isChecked);
                    }
                });
                recalculateTotals();
                alert(pnCmBudget.i18n.error);
            });
        });

        // -------------------------------------------------------
        // 7. Sticky totals bar — mirror values from main totals
        // -------------------------------------------------------

        (function () {
            var mainSub    = document.getElementById('pn-cm-budget-subtotal');
            var mainTotal  = document.getElementById('pn-cm-budget-total');
            var stickySub  = document.getElementById('pn-cm-budget-sticky-subtotal');
            var stickyTotal = document.getElementById('pn-cm-budget-sticky-total');

            if (!mainTotal || !stickyTotal) return;

            function sync() {
                if (stickySub && mainSub) stickySub.textContent = mainSub.textContent;
                stickyTotal.textContent = mainTotal.textContent;
            }

            sync();

            var cfg = { childList: true, characterData: true, subtree: true };
            var obs = new MutationObserver(sync);
            obs.observe(mainTotal, cfg);
            if (mainSub) obs.observe(mainSub, cfg);
        })();

    });
})(jQuery);
