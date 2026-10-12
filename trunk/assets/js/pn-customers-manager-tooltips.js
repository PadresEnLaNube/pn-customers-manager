(function($) {
    'use strict';

    var $tooltipBox = null;
    var hideTimeout = null;
    var touchTimeout = null;

    function createTooltipBox() {
        if (!$tooltipBox) {
            $tooltipBox = $('<div class="pn-customers-manager-tooltip-box hostpn-tooltip-box"></div>');
            $('body').append($tooltipBox);
        }
        return $tooltipBox;
    }

    function positionTooltip(element) {
        var box = createTooltipBox();
        var rect = element.getBoundingClientRect();
        var boxWidth = box.outerWidth();
        var boxHeight = box.outerHeight();
        var spaceAbove = rect.top;
        var left = rect.left + (rect.width / 2) - (boxWidth / 2);

        if (left < 4) left = 4;
        if (left + boxWidth > window.innerWidth - 4) left = window.innerWidth - boxWidth - 4;

        if (spaceAbove >= boxHeight + 10) {
            box.removeClass('pn-customers-manager-tooltip-box--bottom hostpn-tooltip-box--bottom');
            box.css({ top: rect.top - boxHeight - 8, left: left });
        } else {
            box.addClass('pn-customers-manager-tooltip-box--bottom hostpn-tooltip-box--bottom');
            box.css({ top: rect.bottom + 8, left: left });
        }
    }

    function getTooltipContent(el) {
        var $el = $(el);
        var contentSelector = $el.attr('data-pn-customers-manager-tooltip-content') || $el.attr('data-hostpn-tooltip-content');
        if (contentSelector) {
            var $source = $(contentSelector);
            if ($source.length) return $source.html();
        }
        var text = $el.attr('data-pn-customers-manager-tooltip') || $el.attr('data-hostpn-tooltip');
        if (text) return $('<span>').text(text).html();
        return null;
    }

    function showTooltip(el) {
        clearTimeout(hideTimeout);
        clearTimeout(touchTimeout);
        var content = getTooltipContent(el);
        if (!content) return;
        var box = createTooltipBox();
        box.html(content);
        box.addClass('pn-customers-manager-tooltip-box--visible hostpn-tooltip-box--visible');
        positionTooltip(el);
    }

    function hideTooltip() {
        clearTimeout(touchTimeout);
        if ($tooltipBox) {
            $tooltipBox.removeClass('pn-customers-manager-tooltip-box--visible hostpn-tooltip-box--visible pn-customers-manager-tooltip-box--bottom hostpn-tooltip-box--bottom');
        }
    }

    window.PN_CUSTOMERS_MANAGER_Tooltips = {
        init: function(selector) {
            selector = selector || '.pn-customers-manager-tooltip';
            $(selector).each(function() {
                var $el = $(this);
                if ($el.attr('title') && !$el.attr('data-pn-customers-manager-tooltip')) {
                    $el.attr('data-pn-customers-manager-tooltip', $el.attr('title'));
                    $el.removeAttr('title');
                }
            });
        },
        show: function(element) {
            var el = element instanceof $ ? element[0] : element;
            if (el) showTooltip(el);
        },
        hide: function() { hideTooltip(); }
    };

    $(document).on('mouseenter', '.pn-customers-manager-tooltip', function() {
        var $el = $(this);
        if ($el.attr('title') && !$el.attr('data-pn-customers-manager-tooltip')) {
            $el.attr('data-pn-customers-manager-tooltip', $el.attr('title'));
            $el.removeAttr('title');
        }
        showTooltip(this);
    });
    $(document).on('mouseleave', '.pn-customers-manager-tooltip', function() { hideTooltip(); });
    $(document).on('focusin', '.pn-customers-manager-tooltip', function() {
        var $el = $(this);
        if ($el.attr('title') && !$el.attr('data-pn-customers-manager-tooltip')) {
            $el.attr('data-pn-customers-manager-tooltip', $el.attr('title'));
            $el.removeAttr('title');
        }
        showTooltip(this);
    });
    $(document).on('focusout', '.pn-customers-manager-tooltip', function() { hideTooltip(); });
    $(document).on('touchstart', '.pn-customers-manager-tooltip', function(e) {
        var $el = $(this);
        if ($el.attr('title') && !$el.attr('data-pn-customers-manager-tooltip')) {
            $el.attr('data-pn-customers-manager-tooltip', $el.attr('title'));
            $el.removeAttr('title');
        }
        showTooltip(this);
        touchTimeout = setTimeout(function() { hideTooltip(); }, 4000);
    });

    $(document).ready(function() { PN_CUSTOMERS_MANAGER_Tooltips.init(); });
})(jQuery);
