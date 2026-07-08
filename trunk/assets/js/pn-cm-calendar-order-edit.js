(function ($) {
  'use strict';

  if (typeof pnCmCalendarOrderEdit === 'undefined') return;

  var config = pnCmCalendarOrderEdit;
  var currentModal = null;

  // State del modal actual
  var modalState = {
    itemId: 0,
    productId: 0,
    orderId: 0,
    currentDates: [],
    currentWeeks: [],
    selectedDates: [],
    selectedWeeks: [],
    weeks: [],
    dayPrice: 0,
    availability: {},
    allDates: {}
  };

  /* ─── Inicializar ─── */
  $(function () {
    bindEditButtons();
  });

  /* ─── Bind botones de editar ─── */
  function bindEditButtons() {
    $(document).on('click', '.pn-cm-calendar-edit-dates', function (e) {
      e.preventDefault();
      var $btn = $(this);

      modalState.itemId = parseInt($btn.data('item-id'));
      modalState.orderId = parseInt($btn.data('order-id'));
      modalState.productId = parseInt($btn.data('product-id'));
      modalState.currentDates = JSON.parse($btn.attr('data-current-dates') || '[]');
      modalState.currentWeeks = JSON.parse($btn.attr('data-current-weeks') || '[]');
      modalState.weeks = JSON.parse($btn.attr('data-weeks') || '[]');
      modalState.dayPrice = parseFloat($btn.data('day-price')) || 0;

      // Simular disponibilidad excluyendo las fechas actuales
      var baseAvailability = JSON.parse($btn.attr('data-availability') || '{}');
      modalState.availability = simulateAvailability(baseAvailability, modalState.currentDates);

      // Crear mapa de todas las fechas disponibles
      modalState.allDates = {};
      modalState.weeks.forEach(function (w) {
        w.dates.forEach(function (d) {
          modalState.allDates[d] = true;
        });
      });

      // Inicializar selección con fechas actuales
      modalState.selectedDates = modalState.currentDates.slice();
      modalState.selectedWeeks = modalState.currentWeeks.slice();

      openModal();
    });
  }

  /* ─── Simular disponibilidad excluyendo fechas actuales ─── */
  function simulateAvailability(baseAvailability, excludeDates) {
    var simulated = {};
    for (var date in baseAvailability) {
      var avail = parseInt(baseAvailability[date]) || 0;
      // Si la fecha está en excludeDates, agregar una plaza
      if (excludeDates.indexOf(date) !== -1) {
        avail++;
      }
      simulated[date] = avail;
    }
    return simulated;
  }

  /* ─── Abrir modal ─── */
  function openModal() {
    var html = '<div class="pn-cm-calendar-admin-overlay" id="pn-cm-calendar-edit-modal">';
    html += '<div class="pn-cm-calendar-admin-modal" style="max-width: 900px;">';
    html += '<div class="pn-cm-calendar-admin-modal-header">';
    html += '<h3 class="pn-cm-calendar-admin-modal-title">' + config.i18n.editDates + '</h3>';
    html += '<button type="button" class="pn-cm-calendar-admin-modal-close">&times;</button>';
    html += '</div>';
    html += '<div style="padding: 20px;">';

    // Leyenda
    html += '<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 16px; font-size: 13px;">';
    html += '<span style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #4caf50;"></span> ' + config.i18n.available + '</span>';
    html += '<span style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #1976d2;"></span> ' + config.i18n.selected + '</span>';
    html += '<span style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #9e9e9e;"></span> ' + config.i18n.full + '</span>';
    html += '</div>';

    // Botones de semanas
    html += '<div id="pn-cm-calendar-edit-weeks" style="margin-bottom: 20px;"></div>';

    // Calendario
    html += '<div id="pn-cm-calendar-edit-calendar"></div>';

    // Resumen
    html += '<div id="pn-cm-calendar-edit-summary" style="background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 8px; padding: 16px; margin-top: 20px;"></div>';

    html += '</div>';
    html += '<div style="padding: 16px 20px; border-top: 1px solid #e0e0e0; display: flex; gap: 10px; justify-content: flex-end;">';
    html += '<button type="button" class="button" id="pn-cm-calendar-edit-cancel">' + config.i18n.cancel + '</button>';
    html += '<button type="button" class="button button-primary" id="pn-cm-calendar-edit-save">' + config.i18n.save + '</button>';
    html += '</div>';
    html += '</div></div>';

    $('body').append(html);
    currentModal = $('#pn-cm-calendar-edit-modal');

    renderWeekButtons();
    renderCalendar();
    renderSummary();

    // Bind eventos
    currentModal.on('click', '.pn-cm-calendar-admin-modal-close, #pn-cm-calendar-edit-cancel', closeModal);
    currentModal.on('click', '#pn-cm-calendar-edit-save', saveChanges);
  }

  /* ─── Cerrar modal ─── */
  function closeModal() {
    if (currentModal) {
      currentModal.remove();
      currentModal = null;
    }
  }

  /* ─── Renderizar botones de semanas ─── */
  function renderWeekButtons() {
    if (modalState.weeks.length === 0) {
      $('#pn-cm-calendar-edit-weeks').html('');
      return;
    }

    var html = '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';

    modalState.weeks.forEach(function (week) {
      var isSelected = modalState.selectedWeeks.indexOf(week.id) !== -1;
      var isDisabled = !isWeekAvailable(week);

      var btnClass = 'pn-cm-calendar-week-btn';
      if (isSelected) btnClass += ' pn-cm-calendar-week-btn--selected';
      if (isDisabled) btnClass += ' pn-cm-calendar-week-btn--disabled';

      var dateRange = formatWeekRange(week.dates);
      var price = parseFloat(week.price) || 0;

      html += '<button type="button" class="' + btnClass + '" data-week-id="' + escAttr(week.id) + '" ' + (isDisabled ? 'disabled' : '') + '>';
      html += '<span class="pn-cm-calendar-week-btn-label">' + escHtml(week.label) + '</span>';
      html += '<span class="pn-cm-calendar-week-btn-range">' + dateRange + '</span>';
      html += '<span class="pn-cm-calendar-week-btn-price">' + price.toFixed(2) + ' ' + config.currency + '</span>';
      html += '</button>';
    });

    html += '</div>';
    $('#pn-cm-calendar-edit-weeks').html(html);

    // Bind clicks
    $('#pn-cm-calendar-edit-weeks').on('click', '.pn-cm-calendar-week-btn:not(.pn-cm-calendar-week-btn--disabled)', function () {
      var weekId = $(this).data('week-id');
      toggleWeek(weekId);
    });
  }

  /* ─── Verificar si semana está disponible ─── */
  function isWeekAvailable(week) {
    for (var i = 0; i < week.dates.length; i++) {
      var date = week.dates[i];
      var avail = modalState.availability[date] || 0;
      if (avail <= 0) return false;
    }
    return true;
  }

  /* ─── Toggle selección de semana ─── */
  function toggleWeek(weekId) {
    var week = null;
    for (var i = 0; i < modalState.weeks.length; i++) {
      if (modalState.weeks[i].id === weekId) {
        week = modalState.weeks[i];
        break;
      }
    }
    if (!week) return;

    var idx = modalState.selectedWeeks.indexOf(weekId);
    if (idx !== -1) {
      // Deseleccionar semana
      modalState.selectedWeeks.splice(idx, 1);
      week.dates.forEach(function (d) {
        var dIdx = modalState.selectedDates.indexOf(d);
        if (dIdx !== -1) {
          modalState.selectedDates.splice(dIdx, 1);
        }
      });
    } else {
      // Seleccionar semana
      if (!isWeekAvailable(week)) return;
      modalState.selectedWeeks.push(weekId);
      week.dates.forEach(function (d) {
        if (modalState.selectedDates.indexOf(d) === -1) {
          modalState.selectedDates.push(d);
        }
      });
    }

    renderWeekButtons();
    renderCalendar();
    renderSummary();
  }

  /* ─── Renderizar calendario ─── */
  function renderCalendar() {
    var months = getActiveMonths();
    var html = '<div style="display: flex; flex-wrap: wrap; gap: 24px;">';
    months.forEach(function (ym) {
      html += renderMonth(ym.year, ym.month);
    });
    html += '</div>';
    $('#pn-cm-calendar-edit-calendar').html(html);

    // Bind clicks en días
    $('#pn-cm-calendar-edit-calendar').on('click', '.pn-cm-calendar-day--available, .pn-cm-calendar-day--selected', function () {
      var date = $(this).data('date');
      toggleDate(date);
    });
  }

  /* ─── Obtener meses activos ─── */
  function getActiveMonths() {
    var dates = Object.keys(modalState.allDates);
    if (dates.length === 0) return [];

    var minYear = 9999, minMonth = 13, maxYear = 0, maxMonth = 0;
    dates.forEach(function (d) {
      var parts = d.split('-');
      var y = parseInt(parts[0]);
      var m = parseInt(parts[1]);
      if (y < minYear || (y === minYear && m < minMonth)) { minYear = y; minMonth = m; }
      if (y > maxYear || (y === maxYear && m > maxMonth)) { maxYear = y; maxMonth = m; }
    });

    var arr = [];
    var y = minYear, m = minMonth;
    while (y < maxYear || (y === maxYear && m <= maxMonth)) {
      arr.push({ year: y, month: m });
      m++;
      if (m > 12) { m = 1; y++; }
    }
    return arr;
  }

  /* ─── Renderizar mes ─── */
  function renderMonth(year, month) {
    var monthName = config.months[month - 1];
    var html = '<div style="flex: 1 1 260px; min-width: 260px; max-width: 340px;">';
    html += '<div style="font-weight: 700; font-size: 16px; text-align: center; margin-bottom: 8px;">' + monthName + ' ' + year + '</div>';
    html += '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;">';

    // Cabecera días
    config.weekDays.forEach(function (wd) {
      html += '<div style="text-align: center; font-weight: 600; font-size: 12px; color: #888; padding: 4px 0;">' + wd + '</div>';
    });

    var firstDay = new Date(year, month - 1, 1);
    var startDow = (firstDay.getDay() + 6) % 7;
    var daysInMonth = new Date(year, month, 0).getDate();

    // Espacios vacíos
    for (var e = 0; e < startDow; e++) {
      html += '<div></div>';
    }

    // Días
    for (var d = 1; d <= daysInMonth; d++) {
      var dateStr = year + '-' + pad(month) + '-' + pad(d);
      var isCamp = modalState.allDates[dateStr] || false;
      var avail = modalState.availability[dateStr] || 0;
      var isSelected = modalState.selectedDates.indexOf(dateStr) !== -1;
      var isFull = avail <= 0;

      var cls = 'pn-cm-calendar-day';
      var styles = 'position: relative; text-align: center; padding: 6px 2px; border-radius: 6px; font-size: 14px; min-height: 44px; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: background 0.2s, color 0.2s, box-shadow 0.2s;';

      if (!isCamp) {
        cls += ' pn-cm-calendar-day--inactive';
        styles += ' color: #ddd;';
      } else if (isFull) {
        cls += ' pn-cm-calendar-day--full';
        styles += ' background: #f5f5f5; color: #bbb; cursor: not-allowed; border: 1px solid #e0e0e0;';
      } else if (isSelected) {
        cls += ' pn-cm-calendar-day--selected';
        styles += ' background: #1976d2; color: #fff; cursor: pointer; border: 1px solid #1565c0;';
      } else {
        cls += ' pn-cm-calendar-day--available';
        styles += ' background: #e8f5e9; color: #2e7d32; cursor: pointer; border: 1px solid #c8e6c9;';
      }

      html += '<div class="' + cls + '" data-date="' + dateStr + '" style="' + styles + '">';
      html += '<span style="font-weight: 600;">' + d + '</span>';
      if (isCamp && avail !== null) {
        html += '<span style="font-size: 10px; opacity: 0.8;">' + avail + '</span>';
      }
      html += '</div>';
    }

    html += '</div></div>';
    return html;
  }

  /* ─── Toggle selección de fecha ─── */
  function toggleDate(date) {
    if (!modalState.allDates[date]) return;

    var avail = modalState.availability[date] || 0;
    if (avail <= 0) return;

    var idx = modalState.selectedDates.indexOf(date);
    if (idx !== -1) {
      // Deseleccionar
      modalState.selectedDates.splice(idx, 1);

      // Verificar si hay que deseleccionar alguna semana
      modalState.weeks.forEach(function (week) {
        if (modalState.selectedWeeks.indexOf(week.id) !== -1) {
          // Verificar si todas las fechas de la semana están seleccionadas
          var allSelected = true;
          for (var i = 0; i < week.dates.length; i++) {
            if (modalState.selectedDates.indexOf(week.dates[i]) === -1) {
              allSelected = false;
              break;
            }
          }
          if (!allSelected) {
            var wIdx = modalState.selectedWeeks.indexOf(week.id);
            if (wIdx !== -1) {
              modalState.selectedWeeks.splice(wIdx, 1);
            }
          }
        }
      });
    } else {
      // Seleccionar
      modalState.selectedDates.push(date);

      // Verificar si se completó alguna semana
      modalState.weeks.forEach(function (week) {
        if (modalState.selectedWeeks.indexOf(week.id) === -1) {
          var allSelected = true;
          for (var i = 0; i < week.dates.length; i++) {
            if (modalState.selectedDates.indexOf(week.dates[i]) === -1) {
              allSelected = false;
              break;
            }
          }
          if (allSelected) {
            modalState.selectedWeeks.push(week.id);
          }
        }
      });
    }

    renderWeekButtons();
    renderCalendar();
    renderSummary();
  }

  /* ─── Renderizar resumen ─── */
  function renderSummary() {
    var total = calculateTotal();
    var html = '';

    if (modalState.selectedDates.length === 0) {
      html += '<p style="text-align: center; color: #999;">' + config.i18n.selectAtLeast + '</p>';
    } else {
      html += '<div style="font-weight: 700; font-size: 15px; margin-bottom: 10px;">' + config.i18n.newDates + '</div>';

      // Semanas
      if (modalState.selectedWeeks.length > 0) {
        html += '<div style="margin-bottom: 8px;">';
        modalState.selectedWeeks.forEach(function (weekId) {
          var week = null;
          for (var i = 0; i < modalState.weeks.length; i++) {
            if (modalState.weeks[i].id === weekId) {
              week = modalState.weeks[i];
              break;
            }
          }
          if (week) {
            var price = parseFloat(week.price) || 0;
            html += '<div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px;">';
            html += '<span>' + escHtml(week.label) + '</span>';
            html += '<span style="font-weight: 600;">' + price.toFixed(2) + ' ' + config.currency + '</span>';
            html += '</div>';
          }
        });
        html += '</div>';
      }

      // Días sueltos
      var looseDays = getLooseDays();
      if (looseDays.length > 0) {
        html += '<div style="margin-bottom: 8px;">';
        html += '<div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px;">';
        html += '<span>' + looseDays.length + ' días sueltos</span>';
        html += '<span style="font-weight: 600;">' + (looseDays.length * modalState.dayPrice).toFixed(2) + ' ' + config.currency + '</span>';
        html += '</div>';
        html += '</div>';
      }

      // Total
      html += '<div style="display: flex; justify-content: space-between; border-top: 2px solid #1976d2; padding-top: 10px; font-size: 17px; font-weight: 700; color: #1976d2;">';
      html += '<span>' + config.i18n.total + '</span>';
      html += '<span>' + total.toFixed(2) + ' ' + config.currency + '</span>';
      html += '</div>';
    }

    $('#pn-cm-calendar-edit-summary').html(html);
  }

  /* ─── Calcular total ─── */
  function calculateTotal() {
    var total = 0;

    // Semanas completas
    var coveredDates = [];
    modalState.selectedWeeks.forEach(function (weekId) {
      var week = null;
      for (var i = 0; i < modalState.weeks.length; i++) {
        if (modalState.weeks[i].id === weekId) {
          week = modalState.weeks[i];
          break;
        }
      }
      if (week) {
        total += parseFloat(week.price) || 0;
        coveredDates = coveredDates.concat(week.dates);
      }
    });

    // Días sueltos
    modalState.selectedDates.forEach(function (date) {
      if (coveredDates.indexOf(date) === -1) {
        total += modalState.dayPrice;
      }
    });

    return total;
  }

  /* ─── Obtener días sueltos ─── */
  function getLooseDays() {
    var coveredDates = [];
    modalState.selectedWeeks.forEach(function (weekId) {
      var week = null;
      for (var i = 0; i < modalState.weeks.length; i++) {
        if (modalState.weeks[i].id === weekId) {
          week = modalState.weeks[i];
          break;
        }
      }
      if (week) {
        coveredDates = coveredDates.concat(week.dates);
      }
    });

    return modalState.selectedDates.filter(function (date) {
      return coveredDates.indexOf(date) === -1;
    });
  }

  /* ─── Guardar cambios ─── */
  function saveChanges() {
    if (modalState.selectedDates.length === 0) {
      alert(config.i18n.selectAtLeast);
      return;
    }

    // Verificar disponibilidad
    for (var i = 0; i < modalState.selectedDates.length; i++) {
      var date = modalState.selectedDates[i];
      var avail = modalState.availability[date] || 0;
      if (avail <= 0) {
        alert(config.i18n.noAvailability);
        return;
      }
    }

    var $saveBtn = $('#pn-cm-calendar-edit-save');
    $saveBtn.prop('disabled', true).text(config.i18n.savingChanges);

    $.ajax({
      url: config.ajaxUrl,
      type: 'POST',
      data: {
        action: 'pn_cm_calendar_update_order_dates',
        nonce: config.nonce,
        order_id: modalState.orderId,
        item_id: modalState.itemId,
        dates: JSON.stringify(modalState.selectedDates),
        weeks: JSON.stringify(modalState.selectedWeeks)
      },
      success: function (response) {
        if (response.success) {
          // Actualizar display de fechas
          $('#pn-cm-calendar-dates-display-' + modalState.itemId).html(response.data.dates_html);

          alert(config.i18n.success);
          closeModal();

          // Recargar página para mostrar nuevo total
          location.reload();
        } else {
          alert(response.data.message || config.i18n.error);
          $saveBtn.prop('disabled', false).text(config.i18n.save);
        }
      },
      error: function () {
        alert(config.i18n.error);
        $saveBtn.prop('disabled', false).text(config.i18n.save);
      }
    });
  }

  /* ─── Helpers ─── */
  function pad(n) {
    return n < 10 ? '0' + n : '' + n;
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function escAttr(str) {
    return String(str).replace(/"/g, '&quot;');
  }

  function formatWeekRange(dates) {
    if (!dates || dates.length === 0) return '';
    var first = dates[0];
    var last = dates[dates.length - 1];
    return formatDateShort(first) + ' - ' + formatDateShort(last);
  }

  function formatDateShort(dateStr) {
    var months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return parseInt(parts[2]) + ' ' + months[parseInt(parts[1]) - 1];
  }

})(jQuery);
