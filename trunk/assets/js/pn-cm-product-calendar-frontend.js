(function($) {
  'use strict';

  function pad(n) {
    return n < 10 ? '0' + n : n;
  }

  function escHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderMonthGrid($wrap, year, month) {
    var datesMap = $wrap.data('dates') || {};
    var monthsMap = $wrap.data('months') || {};
    var weekdays = $wrap.data('weekdays') || ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];

    var monthTitle = (monthsMap[month] || '') + ' ' + year;
    $wrap.find('.pn-cm-calendar-month-title').text(monthTitle);

    var firstDay = new Date(year, month - 1, 1);
    var startDow = (firstDay.getDay() + 6) % 7; // 0=Mon, 6=Sun
    var daysInMonth = new Date(year, month, 0).getDate();

    var html = '';
    weekdays.forEach(function(wd) {
      html += '<div class="pn-cm-calendar-weekday">' + escHtml(wd) + '</div>';
    });

    for (var e = 0; e < startDow; e++) {
      html += '<div class="pn-cm-calendar-day pn-cm-calendar-day--empty"></div>';
    }

    for (var d = 1; d <= daysInMonth; d++) {
      var dateStr = year + '-' + pad(month) + '-' + pad(d);
      var dateInfo = datesMap[dateStr];

      if (dateInfo) {
        var pids = (dateInfo.pids || []).join(',');
        var tooltip = escHtml(dateInfo.tooltip || '');
        html += '<div class="pn-cm-calendar-day pn-cm-calendar-day--available pn-customers-manager-tooltip" data-date="' + dateStr + '" data-product-ids="' + pids + '" data-pn-customers-manager-tooltip="' + tooltip + '">';
      } else {
        html += '<div class="pn-cm-calendar-day pn-cm-calendar-day--inactive" data-date="' + dateStr + '">';
      }
      html += '<span class="pn-cm-calendar-day-num">' + d + '</span>';
      html += '</div>';
    }

    $wrap.find('.pn-cm-calendar-month-grid').html(html);

    if (window.PN_CUSTOMERS_MANAGER_Tooltips && typeof window.PN_CUSTOMERS_MANAGER_Tooltips.init === 'function') {
      window.PN_CUSTOMERS_MANAGER_Tooltips.init('.pn-cm-calendar-day.pn-customers-manager-tooltip');
    }
  }

  $(function() {
    // Mes anterior
    $(document).on('click', '.pn-cm-calendar-prev-month', function(e) {
      e.preventDefault();
      var $wrap = $(this).closest('.pn-cm-product-calendar-block-wrap');
      var year = parseInt($wrap.attr('data-year'), 10);
      var month = parseInt($wrap.attr('data-month'), 10);

      month--;
      if (month < 1) {
        month = 12;
        year--;
      }

      $wrap.attr('data-year', year);
      $wrap.attr('data-month', month);
      renderMonthGrid($wrap, year, month);
    });

    // Mes siguiente
    $(document).on('click', '.pn-cm-calendar-next-month', function(e) {
      e.preventDefault();
      var $wrap = $(this).closest('.pn-cm-product-calendar-block-wrap');
      var year = parseInt($wrap.attr('data-year'), 10);
      var month = parseInt($wrap.attr('data-month'), 10);

      month++;
      if (month > 12) {
        month = 1;
        year++;
      }

      $wrap.attr('data-year', year);
      $wrap.attr('data-month', month);
      renderMonthGrid($wrap, year, month);
    });

    // Clic en fecha con evento -> Scroll down al extracto del producto correspondiente
    $(document).on('click', '.pn-cm-calendar-day--available', function(e) {
      e.preventDefault();
      var pidsAttr = $(this).attr('data-product-ids') || '';
      if (!pidsAttr) return;

      var pids = pidsAttr.split(',');
      var targetPid = pids[0];
      if (!targetPid) return;

      var $card = $('.pn-cm-product-excerpt-card[data-product-id="' + targetPid + '"]');
      if ($card.length) {
        $('html, body').stop().animate({
          scrollTop: $card.offset().top - 90
        }, 500);

        $('.pn-cm-product-excerpt-card').removeClass('pn-cm-excerpt-card-highlighted');
        $card.addClass('pn-cm-excerpt-card-highlighted');

        setTimeout(function() {
          $card.removeClass('pn-cm-excerpt-card-highlighted');
        }, 2000);
      }
    });

    // Inicializar tooltips en fechas activas existentes
    if (window.PN_CUSTOMERS_MANAGER_Tooltips && typeof window.PN_CUSTOMERS_MANAGER_Tooltips.init === 'function') {
      window.PN_CUSTOMERS_MANAGER_Tooltips.init('.pn-cm-calendar-day.pn-customers-manager-tooltip');
    }
  });

})(jQuery);
