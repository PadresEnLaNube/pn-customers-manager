(function ($) {
  'use strict';

  var weekIndex = $('#pn-cm-calendar-weeks-repeater .pn-cm-calendar-week-row').length;

  function escHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ─── Generar HTML de una nueva fila de bloque ─── */
  function getWeekRowHtml(i) {
    var n = i + 1;
    var memberLabel = $('#_pn_cm_woo_calendar_member_label').val() || 'We are a member family';
    var enableMember = $('#_pn_cm_woo_calendar_enable_member_discount').is(':checked');
    return '' +
      '<div class="pn-cm-calendar-week-row" data-index="' + i + '">' +
        '<div class="pn-cm-calendar-week-header">' +
          '<span class="pn-cm-calendar-week-handle dashicons dashicons-move"></span>' +
          '<span class="pn-cm-calendar-week-number">#' + n + '</span>' +
          '<span class="pn-cm-calendar-week-header-label">New block</span>' +
          '<button type="button" class="pn-cm-calendar-remove-week" title="Delete">' +
            '<span class="dashicons dashicons-trash"></span>' +
          '</button>' +
        '</div>' +
        '<div class="pn-cm-calendar-week-body">' +
          /* Fila 1: ID + Etiqueta */
          '<div class="pn-cm-calendar-row">' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Identifier</label>' +
              '<input type="text" name="_pn_cm_woo_calendar_weeks[' + i + '][id]" value="" placeholder="bloque-' + n + '" />' +
              '<span class="pn-cm-calendar-field-hint">Internal use, no spaces</span>' +
            '</div>' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Display name</label>' +
              '<input type="text" name="_pn_cm_woo_calendar_weeks[' + i + '][label]" value="" placeholder="Block ' + n + '" class="pn-cm-calendar-label-input" />' +
            '</div>' +
          '</div>' +
          /* Calendario interactivo */
          '<div class="pn-cm-calendar-picker-section">' +
            '<label>Dates</label>' +
            '<span class="pn-cm-calendar-picker-hint">Click = select/deselect \u00b7 Shift+click = range \u00b7 Ctrl+click = add individual</span>' +
            '<div class="pn-cm-calendar-block-picker" data-dates="[]"></div>' +
            '<input type="hidden" name="_pn_cm_woo_calendar_weeks[' + i + '][dates_json]" class="pn-cm-calendar-dates-json" value="[]" />' +
          '</div>' +
          '/* Configuración: Aforo + Precios + Descuento */' +
          '<div class="pn-cm-calendar-config-grid">' +
            '<div class="pn-cm-calendar-field pn-cm-calendar-capacity-field">' +
              '<label>Capacity/day</label>' +
              '<div class="pn-cm-calendar-input-with-suffix">' +
                '<input type="number" name="_pn_cm_woo_calendar_weeks[' + i + '][capacity]" value="25" placeholder="25" min="1" />' +
                '<span class="pn-cm-calendar-suffix">spots</span>' +
              '</div>' +
            '</div>' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Standard price</label>' +
              '<div class="pn-cm-calendar-input-with-suffix">' +
                '<input type="number" name="_pn_cm_woo_calendar_weeks[' + i + '][price]" value="" placeholder="100.00" step="0.01" min="0" />' +
                '<span class="pn-cm-calendar-suffix">&euro;</span>' +
              '</div>' +
            '</div>' +
            '<div class="pn-cm-calendar-field pn-cm-member-only-field"' + (enableMember ? '' : ' style="display:none;"') + '>' +
              '<label class="pn-cm-member-price-title">Precio (<span class="pn-cm-member-label-text">' + escHtml(memberLabel) + '</span>)</label>' +
              '<div class="pn-cm-calendar-input-with-suffix">' +
                '<input type="number" name="_pn_cm_woo_calendar_weeks[' + i + '][member_price]" value="" placeholder="80.00" step="0.01" min="0" />' +
                '<span class="pn-cm-calendar-suffix">&euro;</span>' +
              '</div>' +
            '</div>' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Discount</label>' +
              '<div class="pn-cm-calendar-discount-inputs">' +
                '<input type="number" name="_pn_cm_woo_calendar_weeks[' + i + '][discount]" value="0" placeholder="0" step="0.01" min="0" class="pn-cm-calendar-discount-amount" />' +
                '<select name="_pn_cm_woo_calendar_weeks[' + i + '][discount_type]" class="pn-cm-calendar-discount-type">' +
                  '<option value="percent">%</option>' +
                  '<option value="fixed">&euro;</option>' +
                '</select>' +
              '</div>' +
            '</div>' +
          '</div>' +
          /* Grupos */
          '<div class="pn-cm-calendar-groups-section">' +
            '<label class="pn-cm-calendar-groups-title">Activity groups</label>' +
            '<div class="pn-cm-calendar-groups-repeater"></div>' +
            '<button type="button" class="button pn-cm-calendar-add-group">' +
              '<span class="dashicons dashicons-plus-alt2"></span> Add group' +
            '</button>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  /* ─── Añadir bloque ─── */
  $(document).on('click', '#pn-cm-calendar-add-week', function () {
    var $row = $(getWeekRowHtml(weekIndex));
    $('#pn-cm-calendar-weeks-repeater').append($row);
    weekIndex++;
    initCalendarForRow($row);
    $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  /* ─── Eliminar bloque ─── */
  $(document).on('click', '.pn-cm-calendar-remove-week', function () {
    var $row = $(this).closest('.pn-cm-calendar-week-row');
    $row.css({ opacity: 0, transform: 'scale(0.95)' });
    setTimeout(function () {
      $row.remove();
      reindexRows();
    }, 200);
  });

  /* ─── Actualizar label en el header al escribir ─── */
  $(document).on('input', '.pn-cm-calendar-label-input', function () {
    var val = $(this).val() || 'New block';
    $(this).closest('.pn-cm-calendar-week-row').find('.pn-cm-calendar-week-header-label').text(val);
  });

  /* ═══════════════════════════════════════════
   *  Mini-calendario interactivo (block picker)
   *  – Siempre visible con navegación por meses
   *  – Clic = toggle, Shift+clic = rango, Ctrl/Cmd+clic = individual
   * ═══════════════════════════════════════════ */

  var calendarState = {}; // keyed by row data-index: { viewYear, viewMonth, lastClickedDate }

  function getCalState($row) {
    var idx = $row.attr('data-index');
    if (!calendarState[idx]) {
      var now = new Date();
      // If there are selected dates, start from the first one
      var dates = getSelectedDatesFromRow($row);
      var vy = now.getFullYear(), vm = now.getMonth() + 1;
      if (dates.length > 0) {
        var fp = dates[0].split('-');
        vy = parseInt(fp[0]);
        vm = parseInt(fp[1]);
      }
      calendarState[idx] = { viewYear: vy, viewMonth: vm, lastClickedDate: null };
    }
    return calendarState[idx];
  }

  function renderBlockCalendar($row) {
    var $picker = $row.find('.pn-cm-calendar-block-picker');
    var selectedDates = getSelectedDatesFromRow($row);
    var state = getCalState($row);

    var selectedSet = {};
    selectedDates.forEach(function (d) { selectedSet[d] = true; });

    var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                      'July', 'August', 'September', 'October', 'November', 'December'];
    var dayNames = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

    // Show 3 months: previous, current, next
    var months = [];
    for (var offset = -1; offset <= 1; offset++) {
      var m = state.viewMonth + offset;
      var y = state.viewYear;
      if (m < 1) { m = 12; y--; }
      if (m > 12) { m = 1; y++; }
      months.push({ year: y, month: m });
    }

    var html = '';
    // Navigation header
    html += '<div class="pn-cm-calendar-picker-nav">';
    html += '<button type="button" class="pn-cm-calendar-picker-prev" title="Previous month">&lsaquo;</button>';
    html += '<span class="pn-cm-calendar-picker-nav-title">' + monthNames[state.viewMonth - 1] + ' ' + state.viewYear + '</span>';
    html += '<button type="button" class="pn-cm-calendar-picker-next" title="Next month">&rsaquo;</button>';
    html += '</div>';

    html += '<div class="pn-cm-calendar-block-picker-months">';
    months.forEach(function (ym) {
      var isCurrent = (ym.year === state.viewYear && ym.month === state.viewMonth);
      html += '<div class="pn-cm-calendar-block-picker-month' + (isCurrent ? ' pn-cm-calendar-block-picker-month--current' : '') + '">';
      html += '<div class="pn-cm-calendar-block-picker-month-title">' + monthNames[ym.month - 1] + ' ' + ym.year + '</div>';
      html += '<div class="pn-cm-calendar-block-picker-grid">';

      dayNames.forEach(function (dn) {
        html += '<div class="pn-cm-calendar-block-picker-weekday">' + dn + '</div>';
      });

      var firstDay = new Date(ym.year, ym.month - 1, 1);
      var startDow = (firstDay.getDay() + 6) % 7;
      var daysInMonth = new Date(ym.year, ym.month, 0).getDate();

      for (var e = 0; e < startDow; e++) {
        html += '<div class="pn-cm-calendar-block-picker-day pn-cm-calendar-block-picker-day--empty"></div>';
      }

      for (var d = 1; d <= daysInMonth; d++) {
        var mm = ym.month < 10 ? '0' + ym.month : '' + ym.month;
        var dd = d < 10 ? '0' + d : '' + d;
        var dateStr = ym.year + '-' + mm + '-' + dd;
        var isSelected = selectedSet[dateStr] || false;
        var cls = 'pn-cm-calendar-block-picker-day';
        if (isSelected) cls += ' pn-cm-calendar-block-picker-day--selected';
        html += '<div class="' + cls + '" data-date="' + dateStr + '">' + d + '</div>';
      }

      html += '</div></div>';
    });
    html += '</div>';

    $picker.html(html);
  }

  function getSelectedDatesFromRow($row) {
    var jsonVal = $row.find('.pn-cm-calendar-dates-json').val();
    try {
      var dates = JSON.parse(jsonVal);
      if (Array.isArray(dates)) return dates;
    } catch (e) {}
    return [];
  }

  function setSelectedDatesForRow($row, dates) {
    dates.sort();
    $row.find('.pn-cm-calendar-dates-json').val(JSON.stringify(dates));
    renderBlockCalendar($row);
    updateDatesPreview($row, dates);
  }

  /* ─── Navigation: prev/next month ─── */
  $(document).on('click', '.pn-cm-calendar-picker-prev', function (e) {
    e.preventDefault();
    var $row = $(this).closest('.pn-cm-calendar-week-row');
    var state = getCalState($row);
    state.viewMonth--;
    if (state.viewMonth < 1) { state.viewMonth = 12; state.viewYear--; }
    renderBlockCalendar($row);
  });

  $(document).on('click', '.pn-cm-calendar-picker-next', function (e) {
    e.preventDefault();
    var $row = $(this).closest('.pn-cm-calendar-week-row');
    var state = getCalState($row);
    state.viewMonth++;
    if (state.viewMonth > 12) { state.viewMonth = 1; state.viewYear++; }
    renderBlockCalendar($row);
  });

  /* ─── Click on mini-calendar day ─── */
  $(document).on('click', '.pn-cm-calendar-block-picker-day:not(.pn-cm-calendar-block-picker-day--empty)', function (e) {
    var $row = $(this).closest('.pn-cm-calendar-week-row');
    var date = $(this).attr('data-date');
    var dates = getSelectedDatesFromRow($row);
    var state = getCalState($row);

    if (e.shiftKey && state.lastClickedDate) {
      // Shift+click: select range between lastClickedDate and this date
      var rangeStart = state.lastClickedDate < date ? state.lastClickedDate : date;
      var rangeEnd = state.lastClickedDate < date ? date : state.lastClickedDate;
      var rangeDates = getDatesBetween(rangeStart, rangeEnd);
      rangeDates.forEach(function (d) {
        if (dates.indexOf(d) === -1) dates.push(d);
      });
    } else if (e.ctrlKey || e.metaKey) {
      // Ctrl/Cmd+click: toggle individual date (add if not present)
      var idx = dates.indexOf(date);
      if (idx !== -1) {
        dates.splice(idx, 1);
      } else {
        dates.push(date);
      }
    } else {
      // Normal click: toggle individual date
      var idx2 = dates.indexOf(date);
      if (idx2 !== -1) {
        dates.splice(idx2, 1);
      } else {
        dates.push(date);
      }
    }

    state.lastClickedDate = date;
    setSelectedDatesForRow($row, dates);
  });

  /* ─── Helper: get all dates between two date strings (inclusive) ─── */
  function getDatesBetween(start, end) {
    var dates = [];
    var parts = start.split('-');
    var cur = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    var eParts = end.split('-');
    var last = new Date(parseInt(eParts[0]), parseInt(eParts[1]) - 1, parseInt(eParts[2]));
    while (cur <= last) {
      var y = cur.getFullYear();
      var m = cur.getMonth() + 1;
      var d = cur.getDate();
      dates.push(y + '-' + (m < 10 ? '0' + m : m) + '-' + (d < 10 ? '0' + d : d));
      cur.setDate(cur.getDate() + 1);
    }
    return dates;
  }

  /* ─── Preview de días seleccionados ─── */
  function updateDatesPreview($row, dates) {
    var $preview = $row.find('.pn-cm-calendar-dates-preview');
    if (!dates) dates = getSelectedDatesFromRow($row);

    if (dates.length === 0) {
      $preview.html('');
      return;
    }

    var html = '<span class="pn-cm-calendar-preview-label">' + dates.length + ' days selected:</span> ';
    var pills = dates.map(function (d) {
      return '<span class="pn-cm-calendar-preview-pill">' + formatDateShort(d) + '<button type="button" class="pn-cm-calendar-preview-pill-remove" data-date="' + d + '" title="Remove">&times;</button></span>';
    });
    html += pills.join(' ');
    $preview.html(html);
  }

  /* ─── Remove date from preview pill ─── */
  $(document).on('click', '.pn-cm-calendar-preview-pill-remove', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $row = $(this).closest('.pn-cm-calendar-week-row');
    var date = $(this).attr('data-date');
    var dates = getSelectedDatesFromRow($row);
    var idx = dates.indexOf(date);
    if (idx !== -1) dates.splice(idx, 1);
    setSelectedDatesForRow($row, dates);
  });

  function formatDateShort(dateStr) {
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var parts = dateStr.split('-');
    return parseInt(parts[2]) + ' ' + months[parseInt(parts[1]) - 1];
  }

  /* ═══════════════════════════════════════════
   *  Groups sub-repeater
   * ═══════════════════════════════════════════ */

  $(document).on('click', '.pn-cm-calendar-add-group', function () {
    var $row = $(this).closest('.pn-cm-calendar-week-row');
    var rowIndex = $row.attr('data-index');
    var $repeater = $row.find('.pn-cm-calendar-groups-repeater');
    var groupIndex = $repeater.find('.pn-cm-calendar-group-row').length;

    var html = '<div class="pn-cm-calendar-group-row" data-group-index="' + groupIndex + '">' +
      '<input type="hidden" name="_pn_cm_woo_calendar_weeks[' + rowIndex + '][groups][' + groupIndex + '][id]" value="" class="pn-cm-calendar-group-id" />' +
      '<div class="pn-cm-calendar-field">' +
        '<input type="text" name="_pn_cm_woo_calendar_weeks[' + rowIndex + '][groups][' + groupIndex + '][label]" value="" placeholder="Group name" class="pn-cm-calendar-group-label" />' +
      '</div>' +
      '<div class="pn-cm-calendar-field pn-cm-calendar-field--narrow">' +
        '<input type="number" name="_pn_cm_woo_calendar_weeks[' + rowIndex + '][groups][' + groupIndex + '][capacity]" value="15" placeholder="15" min="1" class="pn-cm-calendar-group-capacity" />' +
      '</div>' +
      '<button type="button" class="pn-cm-calendar-remove-group" title="Delete group">' +
        '<span class="dashicons dashicons-no-alt"></span>' +
      '</button>' +
    '</div>';

    $repeater.append(html);
    updateCapacityVisibility($row);
  });

  $(document).on('click', '.pn-cm-calendar-remove-group', function () {
    var $row = $(this).closest('.pn-cm-calendar-week-row');
    $(this).closest('.pn-cm-calendar-group-row').remove();
    reindexGroupRows($row);
    updateCapacityVisibility($row);
  });

  function updateCapacityVisibility($row) {
    var hasGroups = $row.find('.pn-cm-calendar-group-row').length > 0;
    var $capacity = $row.find('.pn-cm-calendar-capacity-field');
    if (hasGroups) {
      $capacity.hide();
      if ($row.find('.pn-cm-calendar-capacity-note').length === 0) {
        $capacity.after('<div class="pn-cm-calendar-capacity-note"><span class="pn-cm-calendar-field-hint">Capacity is defined in each group</span></div>');
      }
    } else {
      $capacity.show();
      $row.find('.pn-cm-calendar-capacity-note').remove();
    }
  }

  function reindexGroupRows($weekRow) {
    var rowIndex = $weekRow.attr('data-index');
    $weekRow.find('.pn-cm-calendar-group-row').each(function (gi) {
      $(this).attr('data-group-index', gi);
      $(this).find('input, select').each(function () {
        var name = $(this).attr('name');
        if (name) {
          $(this).attr('name', name.replace(/\[groups\]\[\d+\]/, '[groups][' + gi + ']'));
        }
      });
    });
  }

  /* ─── Reindexar filas al eliminar o reordenar ─── */
  function reindexRows() {
    $('#pn-cm-calendar-weeks-repeater .pn-cm-calendar-week-row').each(function (idx) {
      var oldIdx = $(this).attr('data-index');
      $(this).attr('data-index', idx);
      $(this).find('.pn-cm-calendar-week-number').text('#' + (idx + 1));
      $(this).find('input, select, textarea').each(function () {
        var name = $(this).attr('name');
        if (name) {
          $(this).attr('name', name.replace(/_pn_cm_woo_calendar_weeks\[\d+\]/, '_pn_cm_woo_calendar_weeks[' + idx + ']'));
        }
      });
      // Update calendar state key
      if (oldIdx !== String(idx) && calendarState[oldIdx]) {
        calendarState[idx] = calendarState[oldIdx];
        delete calendarState[oldIdx];
      }
    });
  }

  /* ─── Init calendar for a row ─── */
  function initCalendarForRow($row) {
    renderBlockCalendar($row);
    updateDatesPreview($row);
    updateCapacityVisibility($row);
  }

  /* ─── Sortable ─── */
  $(function () {
    if ($.fn.sortable) {
      $('#pn-cm-calendar-weeks-repeater').sortable({
        handle: '.pn-cm-calendar-week-handle',
        axis: 'y',
        placeholder: 'pn-cm-calendar-week-placeholder',
        tolerance: 'pointer',
        update: function () {
          reindexRows();
        }
      });
    }

    // Init calendars for existing rows
    $('#pn-cm-calendar-weeks-repeater .pn-cm-calendar-week-row').each(function () {
      initCalendarForRow($(this));
    });

    // Toggle member-only fields in admin panel
    $(document).on('change', '#_pn_cm_woo_calendar_enable_member_discount', function () {
      var isChecked = $(this).is(':checked');
      $('.pn-cm-member-only-field').toggle(isChecked);
    });

    // Dynamic update of member label text across admin panel
    $(document).on('input', '#_pn_cm_woo_calendar_member_label', function () {
      var val = $(this).val() || 'We are a member family';
      $('.pn-cm-member-label-text').text(val);
    });
  });

})(jQuery);
