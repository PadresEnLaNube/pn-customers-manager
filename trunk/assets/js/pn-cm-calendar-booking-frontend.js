(function ($) {
  'use strict';

  if (typeof pnCmCampData === 'undefined') return;

  var data = pnCmCampData;
  var weeks = data.weeks || [];
  var availability = data.availability || {};
  var selectedDates = [];
  var selectedWeeks = [];
  var isMember = false;
  var adminMode = false;

  // Normalizar: asegurar que todas las fechas e IDs son strings únicos
  var usedIds = {};
  weeks.forEach(function (w, idx) {
    w.id = String(w.id || '');
    // Si el ID está vacío o duplicado, generar uno único
    if (!w.id || usedIds[w.id]) {
      w.id = 'week-' + idx;
    }
    usedIds[w.id] = true;
    w.dates = (w.dates || []).map(function (d) { return String(d); });
  });

  var weekMap = {};
  weeks.forEach(function (w) { weekMap[w.id] = w; });

  var allDates = {};
  weeks.forEach(function (w) {
    w.dates.forEach(function (d) { allDates[d] = true; });
  });

  /* ─── Precios activos según toggle ─── */
  function activeDayPrice() {
    return parseFloat(isMember ? data.memberDayPrice : data.dayPrice) || 0;
  }

  function activeWeekPrice(w) {
    return parseFloat(isMember ? w.member_price : w.price) || 0;
  }

  /* ─── Inicializar ─── */
  $(function () {
    renderMemberToggle();
    renderSchedule();
    renderLegend();
    renderCalendar();
    renderWeekButtons();
    renderSummary();
  });

  /* ─── Checkbox socio ─── */
  function renderMemberToggle() {
    var html = '<label class="pn-cm-calendar-member-toggle">';
    html += '<input type="checkbox" id="pn-cm-calendar-member-checkbox"' + (isMember ? ' checked' : '') + ' />';
    html += '<span class="pn-cm-calendar-member-toggle-label">' + data.i18n.memberLabel + '</span>';
    html += '</label>';

    if (data.isAdmin) {
      html += '<button type="button" class="pn-cm-calendar-admin-btn" id="pn-cm-calendar-admin-toggle">';
      html += adminMode ? data.i18n.adminBack : data.i18n.adminPanel;
      html += '</button>';
    }

    $('#pn-cm-calendar-member-toggle').off('.camptoggle').html(html);

    $('#pn-cm-calendar-member-toggle').on('change.camptoggle', '#pn-cm-calendar-member-checkbox', function () {
      isMember = $(this).is(':checked');
      $('#pn-cm-calendar-is-member-input').val(isMember ? '1' : '0');
      renderSchedule();
      renderWeekButtons();
      renderSummary();
    });

    $('#pn-cm-calendar-member-toggle').on('click.camptoggle', '#pn-cm-calendar-admin-toggle', function () {
      adminMode = !adminMode;
      refresh();
      renderMemberToggle();
    });
  }

  /* ─── Horario ─── */
  function renderSchedule() {
    if (!data.schedule) return;
    var html = '<span class="pn-cm-calendar-icon">&#128344;</span> ';
    html += '<strong>' + data.i18n.schedule + ':</strong> ' + escHtml(data.schedule);
    if (isMember) {
      html += '<span class="pn-cm-calendar-member-badge">' + data.i18n.memberBadge + '</span>';
    }
    $('#pn-cm-calendar-schedule').html(html);
  }

  /* ─── Leyenda ─── */
  function renderLegend() {
    $('#pn-cm-calendar-legend').html(
      '<span class="pn-cm-calendar-legend-item"><span class="pn-cm-calendar-dot pn-cm-calendar-dot--available"></span> ' + data.i18n.available + '</span>' +
      '<span class="pn-cm-calendar-legend-item"><span class="pn-cm-calendar-dot pn-cm-calendar-dot--selected"></span> ' + data.i18n.selected + '</span>' +
      '<span class="pn-cm-calendar-legend-item"><span class="pn-cm-calendar-dot pn-cm-calendar-dot--full"></span> ' + data.i18n.full + '</span>'
    );
  }

  /* ─── Calendario por meses ─── */
  function renderCalendar() {
    var months = getActiveMonths();
    var html = '<div class="pn-cm-calendar-months">';
    months.forEach(function (ym) {
      html += renderMonth(ym.year, ym.month);
    });
    html += '</div>';
    $('#pn-cm-calendar-calendar').html(html);
    bindDayClicks();
  }

  function getActiveMonths() {
    // Obtener el rango de meses con fechas de calendario de productos
    var minYear = 9999, minMonth = 13, maxYear = 0, maxMonth = 0;
    Object.keys(allDates).forEach(function (d) {
      var parts = d.split('-');
      var y = parseInt(parts[0]);
      var m = parseInt(parts[1]);
      if (y < minYear || (y === minYear && m < minMonth)) { minYear = y; minMonth = m; }
      if (y > maxYear || (y === maxYear && m > maxMonth)) { maxYear = y; maxMonth = m; }
    });

    // Generar TODOS los meses entre el primero y el último (incluye meses sin fechas)
    var arr = [];
    if (minYear > maxYear) return arr;
    var y = minYear, m = minMonth;
    while (y < maxYear || (y === maxYear && m <= maxMonth)) {
      arr.push({ year: y, month: m });
      m++;
      if (m > 12) { m = 1; y++; }
    }
    return arr;
  }

  function renderMonth(year, month) {
    var monthName = data.months[month - 1];
    var html = '<div class="pn-cm-calendar-month' + (adminMode ? ' pn-cm-calendar-month--admin' : '') + '">';
    html += '<div class="pn-cm-calendar-month-title">' + monthName + ' ' + year + '</div>';
    html += '<div class="pn-cm-calendar-month-grid">';

    data.weekDays.forEach(function (wd) {
      html += '<div class="pn-cm-calendar-weekday">' + wd + '</div>';
    });

    var firstDay = new Date(year, month - 1, 1);
    var startDow = (firstDay.getDay() + 6) % 7;
    var daysInMonth = new Date(year, month, 0).getDate();

    for (var e = 0; e < startDow; e++) {
      html += '<div class="pn-cm-calendar-day pn-cm-calendar-day--empty"></div>';
    }

    for (var d = 1; d <= daysInMonth; d++) {
      var dateStr = year + '-' + pad(month) + '-' + pad(d);
      var isCamp = allDates[dateStr] || false;
      var avail = typeof availability[dateStr] !== 'undefined' ? availability[dateStr] : null;
      var isSelected = selectedDates.indexOf(dateStr) !== -1;
      var isFull = avail !== null && avail <= 0;

      if (adminMode && isCamp) {
        // Admin mode: show occupancy info
        var capacity = getDateCapacity(dateStr);
        var booked = capacity - (avail !== null ? avail : capacity);
        var pct = capacity > 0 ? Math.round((booked / capacity) * 100) : 0;
        var occCls = pct > 80 ? 'high' : (pct >= 50 ? 'mid' : 'low');

        html += '<div class="pn-cm-calendar-day pn-cm-calendar-day--admin pn-cm-calendar-day--admin-' + occCls + '" data-date="' + dateStr + '">';
        html += '<span class="pn-cm-calendar-day-num">' + d + '</span>';
        html += '<span class="pn-cm-calendar-day-occ">' + booked + '/' + capacity + '</span>';
        html += '<span class="pn-cm-calendar-day-bar"><span class="pn-cm-calendar-day-bar-fill pn-cm-calendar-day-bar--' + occCls + '" style="width:' + pct + '%"></span></span>';
        html += '</div>';
      } else {
        // Normal mode
        var cls = 'pn-cm-calendar-day';
        if (!isCamp) {
          cls += ' pn-cm-calendar-day--inactive';
        } else if (isFull) {
          cls += ' pn-cm-calendar-day--full';
        } else if (isSelected) {
          cls += ' pn-cm-calendar-day--selected';
        } else {
          cls += ' pn-cm-calendar-day--available';
        }

        html += '<div class="' + cls + '" data-date="' + dateStr + '">';
        html += '<span class="pn-cm-calendar-day-num">' + d + '</span>';
        if (isCamp && avail !== null && data.showAvailability) {
          html += '<span class="pn-cm-calendar-day-avail">' + avail + '</span>';
        }
        html += '</div>';
      }
    }

    html += '</div></div>';
    return html;
  }

  function getDateCapacity(dateStr) {
    for (var i = 0; i < weeks.length; i++) {
      if (weeks[i].dates.indexOf(dateStr) !== -1) {
        return weeks[i].capacity;
      }
    }
    return 0;
  }

  function bindDayClicks() {
    $('#pn-cm-calendar-calendar').off('click.campday');
    if (adminMode) {
      $('#pn-cm-calendar-calendar').on('click.campday', '.pn-cm-calendar-day--admin', function () {
        var dateStr = String($(this).attr('data-date'));
        openAdminPopup(dateStr);
      });
    } else {
      $('#pn-cm-calendar-calendar').on('click.campday', '.pn-cm-calendar-day--available, .pn-cm-calendar-day--selected', function () {
        var date = String($(this).attr('data-date'));
        toggleDate(date);
      });
    }
  }

  /* ─── Botones de semana ─── */
  function renderWeekButtons() {
    var html = '<div class="pn-cm-calendar-week-btns">';
    weeks.forEach(function (w) {
      var isSelected = selectedWeeks.indexOf(w.id) !== -1;
      var isDisabled = isWeekFull(w);
      var cls = 'pn-cm-calendar-week-btn';
      if (isSelected) cls += ' pn-cm-calendar-week-btn--selected';
      if (isDisabled && !isSelected) cls += ' pn-cm-calendar-week-btn--disabled';

      html += '<button type="button" class="' + cls + '" data-week="' + w.id + '"' +
        (isDisabled && !isSelected ? ' disabled' : '') + '>';
      html += '<span class="pn-cm-calendar-week-btn-label">' + escHtml(w.label || formatWeekRange(w.dates)) + '</span>';
      if (w.label) {
        html += '<span class="pn-cm-calendar-week-btn-range">' + formatWeekRange(w.dates) + '</span>';
      }
      html += '<span class="pn-cm-calendar-week-btn-price">' + formatPrice(activeWeekPrice(w)) + '</span>';
      html += '</button>';
    });
    html += '</div>';
    $('#pn-cm-calendar-weeks-buttons').html(html);
    bindWeekClicks();
  }

  function bindWeekClicks() {
    $('#pn-cm-calendar-weeks-buttons').off('click.campweek');
    $('#pn-cm-calendar-weeks-buttons').on('click.campweek', '.pn-cm-calendar-week-btn:not(.pn-cm-calendar-week-btn--disabled)', function () {
      var weekId = String($(this).attr('data-week'));
      toggleWeek(weekId);
    });
  }

  function isWeekFull(week) {
    for (var i = 0; i < week.dates.length; i++) {
      var avail = availability[week.dates[i]];
      if (typeof avail !== 'undefined' && avail <= 0) return true;
    }
    return false;
  }

  /* ─── Helpers de arrays (siempre string) ─── */
  function addUnique(arr, val) {
    val = String(val);
    if (arr.indexOf(val) === -1) arr.push(val);
  }
  function removeFrom(arr, val) {
    val = String(val);
    var idx = arr.indexOf(val);
    if (idx !== -1) arr.splice(idx, 1);
  }
  function hasVal(arr, val) {
    return arr.indexOf(String(val)) !== -1;
  }

  /* ─── Selección ─── */
  function toggleDate(date) {
    date = String(date);
    if (hasVal(selectedDates, date)) {
      removeFrom(selectedDates, date);
    } else {
      addUnique(selectedDates, date);
    }
    // syncWeeksWithDates() en refresh() recalcula las semanas activas
    refresh();
  }

  function toggleWeek(weekId) {
    weekId = String(weekId);
    var week = weekMap[weekId];
    if (!week) return;

    if (hasVal(selectedWeeks, weekId)) {
      // Deseleccionar semana: quitar todos sus días
      week.dates.forEach(function (d) { removeFrom(selectedDates, d); });
    } else {
      // Seleccionar semana: añadir todos sus días
      week.dates.forEach(function (d) { addUnique(selectedDates, d); });
    }
    // syncWeeksWithDates() en refresh() recalcula las semanas activas
    refresh();
  }

  /* ─── Resumen y precio ─── */
  function renderSummary() {
    var lines = [];
    var total = 0;
    var dp = activeDayPrice();

    var coveredDates = [];
    selectedWeeks.forEach(function (wid) {
      var w = weekMap[wid];
      if (!w) return;
      var wp = activeWeekPrice(w);
      coveredDates = coveredDates.concat(w.dates);
      var weekLabel = w.label ? w.label + ' (' + formatWeekRange(w.dates) + ')' : formatWeekRange(w.dates);
      lines.push({ label: weekLabel, price: wp });
      total += wp;
    });

    var looseDays = selectedDates.filter(function (d) {
      return coveredDates.indexOf(d) === -1;
    });
    looseDays.sort();
    looseDays.forEach(function (d) {
      lines.push({ label: formatDateShort(d), price: dp });
      total += dp;
    });

    var html = '';
    if (lines.length === 0) {
      html = '<div class="pn-cm-calendar-summary-empty">' + data.i18n.noSelection + '</div>';
    } else {
      html = '<div class="pn-cm-calendar-summary-title">' + data.i18n.yourSelection + ':</div>';
      html += '<div class="pn-cm-calendar-summary-lines">';
      lines.forEach(function (l) {
        html += '<div class="pn-cm-calendar-summary-line">';
        html += '<span class="pn-cm-calendar-summary-label">' + escHtml(l.label) + '</span>';
        html += '<span class="pn-cm-calendar-summary-price">' + formatPrice(l.price) + '</span>';
        html += '</div>';
      });
      html += '</div>';
      html += '<div class="pn-cm-calendar-summary-total">';
      html += '<span>' + data.i18n.total + ':</span>';
      html += '<span>' + formatPrice(total) + '</span>';
      html += '</div>';
    }

    $('#pn-cm-calendar-summary').html(html);
    updateHiddenInputs();
  }

  function updateHiddenInputs() {
    $('#pn-cm-calendar-dates-input').val(JSON.stringify(selectedDates.slice().sort()));
    $('#pn-cm-calendar-weeks-input').val(JSON.stringify(selectedWeeks));
  }

  function refresh() {
    syncWeeksWithDates();
    renderCalendar();
    if (adminMode) {
      $('#pn-cm-calendar-weeks-buttons').hide();
      $('#pn-cm-calendar-summary').hide();
      $('#pn-cm-calendar-legend').hide();
      $('form.cart .single_add_to_cart_button').hide();
      $('form.cart .quantity').hide();
      loadAdminSummary();
    } else {
      $('#pn-cm-calendar-weeks-buttons').show();
      $('#pn-cm-calendar-summary').show();
      $('#pn-cm-calendar-legend').show();
      $('form.cart .single_add_to_cart_button').show();
      $('form.cart .quantity').show();
      $('#pn-cm-calendar-admin-summary').empty();
      renderWeekButtons();
      renderSummary();
    }
  }

  /**
   * Recalcular selectedWeeks basándose únicamente en selectedDates.
   * Una semana está activa si y solo si TODOS sus días están seleccionados.
   * Esto garantiza consistencia absoluta entre días y semanas.
   */
  function syncWeeksWithDates() {
    var newWeeks = [];
    weeks.forEach(function (w) {
      if (w.dates.length === 0) return;
      var allSelected = w.dates.every(function (d) { return hasVal(selectedDates, d); });
      if (allSelected) newWeeks.push(w.id);
    });
    selectedWeeks = newWeeks;
  }

  /* ─── Formato ─── */
  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function formatPrice(amount) {
    return parseFloat(amount).toFixed(2).replace('.', ',') + ' ' + data.currency;
  }

  function formatDateShort(dateStr) {
    var parts = dateStr.split('-');
    var monthNames = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return parseInt(parts[2]) + ' ' + monthNames[parseInt(parts[1]) - 1];
  }

  function formatWeekRange(dates) {
    if (!dates || dates.length === 0) return '';
    return formatDateShort(dates[0]) + ' - ' + formatDateShort(dates[dates.length - 1]);
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  /* ─── Admin: popup de reservas ─── */
  function openAdminPopup(dateStr) {
    // Show loading overlay
    var loadingHtml = '<div class="pn-cm-calendar-admin-overlay" id="pn-cm-calendar-admin-overlay">';
    loadingHtml += '<div class="pn-cm-calendar-admin-modal">';
    loadingHtml += '<div class="pn-cm-calendar-admin-modal-loading">' + data.i18n.adminLoading + '</div>';
    loadingHtml += '</div></div>';
    $('body').append(loadingHtml);

    $.post(data.ajaxUrl, {
      action: 'pn_cm_woo_calendar_get_date_bookings',
      nonce: data.nonce,
      product_id: data.productId,
      date: dateStr
    }, function (response) {
      $('#pn-cm-calendar-admin-overlay').remove();
      if (response.success) {
        renderAdminPopup(dateStr, response.data);
      } else {
        alert(data.i18n.adminError);
      }
    }).fail(function () {
      $('#pn-cm-calendar-admin-overlay').remove();
      alert(data.i18n.adminError);
    });
  }

  function renderAdminPopup(dateStr, resData) {
    var capacity = resData.capacity;
    var booked = resData.booked;
    var pct = capacity > 0 ? Math.round((booked / capacity) * 100) : 0;
    var reservations = resData.reservations || [];

    var html = '<div class="pn-cm-calendar-admin-overlay" id="pn-cm-calendar-admin-overlay">';
    html += '<div class="pn-cm-calendar-admin-modal">';

    // Header
    html += '<div class="pn-cm-calendar-admin-modal-header">';
    html += '<div class="pn-cm-calendar-admin-modal-title">' + formatDateLong(dateStr) + '</div>';
    html += '<button type="button" class="pn-cm-calendar-admin-modal-close" id="pn-cm-calendar-admin-close">&times;</button>';
    html += '</div>';

    // Occupancy bar
    var occCls = pct > 80 ? 'high' : (pct >= 50 ? 'mid' : 'low');
    html += '<div class="pn-cm-calendar-admin-modal-occ">';
    html += '<span>' + data.i18n.adminOccupancy + ': <strong>' + booked + ' / ' + capacity + '</strong> (' + pct + '%)</span>';
    html += '<div class="pn-cm-calendar-admin-modal-bar"><span class="pn-cm-calendar-admin-modal-bar-fill pn-cm-calendar-day-bar--' + occCls + '" style="width:' + pct + '%"></span></div>';
    html += '</div>';

    // Reservations table
    if (reservations.length === 0) {
      html += '<div class="pn-cm-calendar-admin-modal-empty">' + data.i18n.adminNoBookings + '</div>';
    } else {
      html += '<div class="pn-cm-calendar-admin-modal-table-wrap">';
      html += '<table class="pn-cm-calendar-admin-modal-table">';
      html += '<thead><tr>';
      html += '<th>' + data.i18n.adminName + '</th>';
      html += '<th>' + data.i18n.adminEmail + '</th>';
      html += '<th>' + data.i18n.adminPhone + '</th>';
      html += '<th>' + data.i18n.adminNotes + '</th>';
      html += '<th>' + data.i18n.adminOrder + '</th>';
      html += '</tr></thead><tbody>';

      reservations.forEach(function (r) {
        html += '<tr>';
        html += '<td data-label="' + data.i18n.adminName + '">' + escHtml(r.first_name + ' ' + r.last_name) + '</td>';
        html += '<td data-label="' + data.i18n.adminEmail + '"><a href="mailto:' + escHtml(r.email) + '">' + escHtml(r.email) + '</a></td>';
        html += '<td data-label="' + data.i18n.adminPhone + '">' + (r.phone ? '<a href="tel:' + escHtml(r.phone) + '">' + escHtml(r.phone) + '</a>' : '-') + '</td>';
        html += '<td data-label="' + data.i18n.adminNotes + '">' + (r.notes ? escHtml(r.notes) : '-') + '</td>';
        html += '<td data-label="' + data.i18n.adminOrder + '"><a href="' + escHtml(r.edit_url) + '" target="_blank">#' + r.order_id + '</a><br><small>' + escHtml(r.order_date) + '</small></td>';
        html += '</tr>';
      });

      html += '</tbody></table>';
      html += '</div>';
    }

    html += '</div></div>';
    $('body').append(html);

    // Bind close
    function closeModal() {
      $('#pn-cm-calendar-admin-overlay').remove();
      $(document).off('keydown.admincampmodal');
    }
    $('#pn-cm-calendar-admin-overlay').on('click', function (e) {
      if ($(e.target).is('#pn-cm-calendar-admin-overlay')) closeModal();
    });
    $('#pn-cm-calendar-admin-close').on('click', closeModal);
    $(document).on('keydown.admincampmodal', function (e) {
      if (e.key === 'Escape') closeModal();
    });
  }

  function formatDateLong(dateStr) {
    var parts = dateStr.split('-');
    var dayNames = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    var monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    var dt = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    return dayNames[dt.getDay()] + ' ' + parseInt(parts[2]) + ' de ' + monthNames[parseInt(parts[1]) - 1] + ' de ' + parts[0];
  }

  /* ─── Admin: resumen de facturación ─── */
  function loadAdminSummary() {
    $('#pn-cm-calendar-admin-summary').html(
      '<div class="pn-cm-calendar-admin-sum-loading">' + data.i18n.adminLoading + '</div>'
    );

    $.post(data.ajaxUrl, {
      action: 'pn_cm_woo_calendar_get_admin_summary',
      nonce: data.nonce,
      product_id: data.productId
    }, function (response) {
      if (!adminMode) { $('#pn-cm-calendar-admin-summary').empty(); return; }
      if (response.success) {
        renderAdminSummary(response.data);
      } else {
        $('#pn-cm-calendar-admin-summary').html(
          '<div class="pn-cm-calendar-admin-sum-loading">' + data.i18n.adminError + '</div>'
        );
      }
    }).fail(function () {
      if (!adminMode) return;
      $('#pn-cm-calendar-admin-summary').html(
        '<div class="pn-cm-calendar-admin-sum-loading">' + data.i18n.adminError + '</div>'
      );
    });
  }

  function renderAdminSummary(sumData) {
    var cur = sumData.currency || data.currency;
    var html = '<div class="pn-cm-calendar-admin-sum">';
    html += '<div class="pn-cm-calendar-admin-sum-title">' + data.i18n.adminSummaryTitle + '</div>';

    // KPI cards
    html += '<div class="pn-cm-calendar-admin-sum-kpis">';
    html += '<div class="pn-cm-calendar-admin-sum-kpi">';
    html += '<span class="pn-cm-calendar-admin-sum-kpi-val">' + sumData.total_orders + '</span>';
    html += '<span class="pn-cm-calendar-admin-sum-kpi-label">' + data.i18n.adminTotalOrders + '</span>';
    html += '</div>';
    html += '<div class="pn-cm-calendar-admin-sum-kpi">';
    html += '<span class="pn-cm-calendar-admin-sum-kpi-val">' + sumData.total_bookings + '</span>';
    html += '<span class="pn-cm-calendar-admin-sum-kpi-label">' + data.i18n.adminTotalBookings + '</span>';
    html += '</div>';
    html += '<div class="pn-cm-calendar-admin-sum-kpi pn-cm-calendar-admin-sum-kpi--highlight">';
    html += '<span class="pn-cm-calendar-admin-sum-kpi-val">' + formatAmount(sumData.total_revenue) + ' ' + cur + '</span>';
    html += '<span class="pn-cm-calendar-admin-sum-kpi-label">' + data.i18n.adminTotalRevenue + '</span>';
    html += '</div>';
    html += '</div>';

    // Week breakdown table
    if (sumData.weeks && sumData.weeks.length > 0) {
      html += '<div class="pn-cm-calendar-admin-sum-weeks-title">' + data.i18n.adminWeekSummary + '</div>';
      html += '<table class="pn-cm-calendar-admin-sum-table">';
      html += '<thead><tr>';
      html += '<th>' + data.i18n.adminWeekLabel + '</th>';
      html += '<th>' + data.i18n.adminWeekBookings + '</th>';
      html += '<th>' + data.i18n.adminWeekRevenue + '</th>';
      html += '</tr></thead><tbody>';

      sumData.weeks.forEach(function (w) {
        html += '<tr>';
        html += '<td>' + escHtml(w.label) + '</td>';
        html += '<td>' + w.bookings + '</td>';
        html += '<td>' + formatAmount(w.revenue) + ' ' + cur + '</td>';
        html += '</tr>';
      });

      html += '</tbody></table>';
    }

    html += '</div>';
    $('#pn-cm-calendar-admin-summary').html(html);
  }

  function formatAmount(n) {
    return parseFloat(n).toFixed(2).replace('.', ',');
  }

  /* ─── AJAX refresh disponibilidad ─── */
  setInterval(function () {
    $.post(data.ajaxUrl, {
      action: 'pn_cm_woo_calendar_get_availability',
      nonce: data.nonce,
      product_id: data.productId
    }, function (response) {
      if (response.success) {
        availability = response.data;
        refresh();
      }
    });
  }, 60000);

})(jQuery);
