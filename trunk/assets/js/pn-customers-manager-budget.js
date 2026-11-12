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
            var recurringByPeriod = {};

            $('.pn-cm-budget-item-row').each(function () {
                var $row = $(this);

                // Skip phase, image and image_compare rows — they have no price.
                if ($row.hasClass('pn-cm-budget-item-phase') || $row.hasClass('pn-cm-budget-item-image') || $row.hasClass('pn-cm-budget-item-image-compare')) {
                    return;
                }

                // Skip inactive alternative items or items inside inactive alternative phases.
                if ($row.hasClass('pn-cm-budget-alt-hidden') || $row.closest('.pn-cm-budget-phase-group').hasClass('pn-cm-budget-alt-hidden')) {
                    return;
                }

                var $checkbox = $row.find('.pn-cm-budget-toggle-item');

                // Include the row if it has no checkbox (non-optional)
                // or if its checkbox is checked
                if (!$checkbox.length || $checkbox.is(':checked')) {
                    var rowTotal = parseFloat($row.data('item-total')) || 0;
                    var isRecurring = parseInt($row.attr('data-item-recurring'), 10) || 0;
                    var period = $row.attr('data-item-recurring-period') || '';

                    if (isRecurring && period) {
                        recurringByPeriod[period] = (recurringByPeriod[period] || 0) + rowTotal;
                    } else {
                        subtotal += rowTotal;
                    }
                }
            });

            var discountRate  = parseFloat(pnCmBudget.discountRate) || 0;
            var taxRate        = parseFloat(pnCmBudget.taxRate) || 0;

            var discountAmount = subtotal * discountRate / 100;
            var taxAmount      = (subtotal - discountAmount) * taxRate / 100;
            var total          = subtotal - discountAmount + taxAmount;

            $('#pn-cm-budget-subtotal').text(formatCurrency(subtotal));

            // Update recurring totals rows.
            $('.pn-cm-budget-totals-recurring').remove();
            var labels = (typeof pnCmBudget !== 'undefined' && pnCmBudget.recurringLabels) ? pnCmBudget.recurringLabels : {};
            var $subtotalRow = $('#pn-cm-budget-subtotal').closest('.pn-cm-budget-totals-row');
            $.each(recurringByPeriod, function (p, amt) {
                var lbl = labels[p] || p;
                $('<div class="pn-cm-budget-totals-row pn-cm-budget-totals-recurring" data-recurring-period="' + p + '">'
                    + '<span>Recurring ' + lbl + '</span>'
                    + '<span>' + formatCurrency(amt) + '</span>'
                    + '</div>').insertAfter($subtotalRow);
            });

            $('#pn-cm-budget-discount').text(formatCurrency(discountAmount));
            $('#pn-cm-budget-tax').text(formatCurrency(taxAmount));
            $('#pn-cm-budget-total').text(formatCurrency(total));

            // Sync sticky bar recurring items.
            syncStickyRecurring(recurringByPeriod, labels);

            // Update phase subtotals.
            updatePhaseTotals();
        }

        /**
         * Recalculate and display the total for each phase group header.
         */
        function updatePhaseTotals() {
            $('.pn-cm-budget-phase-group').each(function () {
                var $group = $(this);
                if ($group.hasClass('pn-cm-budget-alt-hidden')) return;

                var phaseTotal = 0;
                $group.find('.pn-cm-budget-phase-items > .pn-cm-budget-item-row').each(function () {
                    var $row = $(this);
                    if ($row.hasClass('pn-cm-budget-item-deselected') || $row.hasClass('pn-cm-budget-item-image') || $row.hasClass('pn-cm-budget-item-image-compare')) return;
                    if ($row.hasClass('pn-cm-budget-alt-hidden')) return;
                    
                    var $checkbox = $row.find('.pn-cm-budget-toggle-item');
                    if (!$checkbox.length || $checkbox.is(':checked')) {
                        phaseTotal += parseFloat($row.attr('data-item-total')) || 0;
                    }
                });
                var $label = $group.find('.pn-cm-budget-phase-total');
                if ($label.length) {
                    $label.text(formatCurrency(phaseTotal));
                }
            });
        }

        /**
         * Rebuild the sticky bar recurring section from the computed map.
         */
        function syncStickyRecurring(recurringByPeriod, labels) {
            var $stickyRecurring = $('#pn-cm-budget-sticky-recurring');
            var $stickyTotalItem = $('#pn-cm-budget-sticky-total').closest('.pn-cm-budget-sticky-item');

            var hasItems = false;
            for (var k in recurringByPeriod) { if (recurringByPeriod.hasOwnProperty(k)) { hasItems = true; break; } }

            if (!hasItems) {
                $stickyRecurring.remove();
                return;
            }

            if (!$stickyRecurring.length) {
                $stickyRecurring = $('<div class="pn-cm-budget-sticky-recurring" id="pn-cm-budget-sticky-recurring"></div>');
                $stickyTotalItem.before($stickyRecurring);
            }
            $stickyRecurring.empty();
            $.each(recurringByPeriod, function (p, amt) {
                var lbl = labels[p] || p;
                $stickyRecurring.append(
                    '<div class="pn-cm-budget-sticky-item pn-cm-budget-sticky-item-recurring" data-recurring-period="' + p + '">'
                    + '<span class="pn-cm-budget-sticky-label">Recurring ' + lbl + '</span>'
                    + '<span class="pn-cm-budget-sticky-value">' + formatCurrency(amt) + '</span>'
                    + '</div>'
                );
            });
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
        // 2. Signature canvas
        // -------------------------------------------------------

        var signatureCanvas = document.getElementById('pn-cm-budget-signature-canvas');
        var signatureCtx = signatureCanvas ? signatureCanvas.getContext('2d') : null;
        var isDrawing = false;
        var hasDrawn = false;

        if (signatureCanvas && signatureCtx) {
            // Set up high-DPI canvas.
            function resizeCanvas() {
                var rect = signatureCanvas.getBoundingClientRect();
                var dpr = window.devicePixelRatio || 1;
                signatureCanvas.width = rect.width * dpr;
                signatureCanvas.height = rect.height * dpr;
                signatureCtx.scale(dpr, dpr);
                signatureCtx.lineCap = 'round';
                signatureCtx.lineJoin = 'round';
                signatureCtx.lineWidth = 2;
                signatureCtx.strokeStyle = '#000';
                hasDrawn = false;
            }
            resizeCanvas();

            function getPos(e) {
                var rect = signatureCanvas.getBoundingClientRect();
                var clientX, clientY;
                if (e.touches && e.touches.length) {
                    clientX = e.touches[0].clientX;
                    clientY = e.touches[0].clientY;
                } else {
                    clientX = e.clientX;
                    clientY = e.clientY;
                }
                return { x: clientX - rect.left, y: clientY - rect.top };
            }

            function startDrawing(e) {
                e.preventDefault();
                isDrawing = true;
                var pos = getPos(e);
                signatureCtx.beginPath();
                signatureCtx.moveTo(pos.x, pos.y);
            }

            function draw(e) {
                if (!isDrawing) return;
                e.preventDefault();
                var pos = getPos(e);
                signatureCtx.lineTo(pos.x, pos.y);
                signatureCtx.stroke();
                hasDrawn = true;
            }

            function stopDrawing(e) {
                if (isDrawing) {
                    e.preventDefault();
                    isDrawing = false;
                }
            }

            // Mouse events.
            signatureCanvas.addEventListener('mousedown', startDrawing);
            signatureCanvas.addEventListener('mousemove', draw);
            signatureCanvas.addEventListener('mouseup', stopDrawing);
            signatureCanvas.addEventListener('mouseleave', stopDrawing);

            // Touch events.
            signatureCanvas.addEventListener('touchstart', startDrawing, { passive: false });
            signatureCanvas.addEventListener('touchmove', draw, { passive: false });
            signatureCanvas.addEventListener('touchend', stopDrawing);
            signatureCanvas.addEventListener('touchcancel', stopDrawing);

            // Clear button.
            $(document).on('click', '#pn-cm-budget-signature-clear', function (e) {
                e.preventDefault();
                var rect = signatureCanvas.getBoundingClientRect();
                signatureCtx.clearRect(0, 0, rect.width, rect.height);
                hasDrawn = false;
            });
        }

        function isSignatureEmpty() {
            return !hasDrawn;
        }

        function getSignatureData() {
            if (!signatureCanvas) return '';
            return signatureCanvas.toDataURL('image/png');
        }

        // -------------------------------------------------------
        // 3. Accept budget (with signature)
        // -------------------------------------------------------

        function sendAcceptRequest(email, name, signatureData) {
            var data = {
                action: 'pn_customers_manager_ajax_nopriv',
                pn_customers_manager_ajax_nopriv_type: 'pn_cm_budget_accept',
                pn_customers_manager_ajax_nopriv_nonce: pnCmBudget.nonce,
                budget_id: pnCmBudget.budgetId,
                budget_token: pnCmBudget.budgetToken,
                acceptor_email: email,
                acceptor_name: name,
                signature_data: signatureData,
            };

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

            var $nameInput  = $('#pn-cm-budget-acceptor-name');
            var $emailInput = $('#pn-cm-budget-acceptor-email');
            var name  = $.trim($nameInput.val());
            var email = $.trim($emailInput.val());

            // Validate name.
            if (!name) {
                alert(pnCmBudget.i18n.nameRequired);
                $nameInput.focus();
                return;
            }

            // Validate email.
            if (!email) {
                alert(pnCmBudget.i18n.emailRequired);
                $emailInput.focus();
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert(pnCmBudget.i18n.emailInvalid);
                $emailInput.focus();
                return;
            }

            // Validate signature.
            if (isSignatureEmpty()) {
                alert(pnCmBudget.i18n.signatureRequired);
                return;
            }

            if (!confirm(pnCmBudget.i18n.confirmAccept)) {
                return;
            }

            sendAcceptRequest(email, name, getSignatureData());
        });

        // -------------------------------------------------------
        // 4. Reject budget
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
        // 5b. Payments: toggle collapse
        // -------------------------------------------------------

        $(document).on('click', '.pn-cm-payments-header', function () {
            // When admin JS is loaded, it handles this event.
            if (typeof pnCmBudgetAdmin !== 'undefined') return;

            $(this).closest('.pn-cm-payments-container, .pn-cm-budget-payments, .pn-cm-invoice-payments').toggleClass('pn-cm-payments-collapsed');
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
        // 7. Before/After image comparison sliders
        // -------------------------------------------------------

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

        initCompareSliders();

        // -------------------------------------------------------
        // 8. Sticky totals bar — mirror values from main totals
        // -------------------------------------------------------

        (function () {
            var mainTotal  = document.getElementById('pn-cm-budget-total');
            var stickyTotal = document.getElementById('pn-cm-budget-sticky-total');

            if (!mainTotal || !stickyTotal) return;

            function sync() {
                stickyTotal.textContent = mainTotal.textContent;
            }

            sync();

            var cfg = { childList: true, characterData: true, subtree: true };
            var obs = new MutationObserver(sync);
            obs.observe(mainTotal, cfg);
        })();

    });
})(jQuery);
