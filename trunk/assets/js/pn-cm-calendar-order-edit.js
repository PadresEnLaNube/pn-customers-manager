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
    qty: 1,
    currentDates: [],
    currentWeeks: [],
    currentGroups: {},
    selectedDates: [],
    selectedWeeks: [],
    selectedGroups: {},
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
      modalState.qty = parseInt($btn.data('qty')) || 1;
      modalState.currentDates = JSON.parse($btn.attr('data-current-dates') || '[]');
      modalState.currentWeeks = JSON.parse($btn.attr('data-current-weeks') || '[]');
      modalState.currentGroups = normalizeGroups(JSON.parse($btn.attr('data-current-groups') || '{}'));
      modalState.weeks = JSON.parse($btn.attr('data-weeks') || '[]');
      modalState.dayPrice = parseFloat($btn.data('day-price')) || 0;

      // Simular disponibilidad excluyendo las fechas actuales
      var baseAvailability = JSON.parse($btn.attr('data-availability') || '{}');
      modalState.availability = simulateAvailability(baseAvailability, modalState.currentDates, modalState.currentGroups, modalState.currentWeeks, modalState.weeks, modalState.qty);

      // Crear mapa de todas las fechas disponibles
      modalState.allDates = {};
      modalState.weeks.forEach(function (w) {
        w.dates.forEach(function (d) {
          modalState.allDates[d] = true;
        });
        // Ensure new fields
        if (!w.groups) w.groups = [];
        if (typeof w.discount === 'undefined') w.discount = 0;
        if (!w.discount_type) w.discount_type = 'percent';
      });

      // Inicializar selección con fechas actuales
      modalState.selectedDates = modalState.currentDates.slice();
      modalState.selectedWeeks = modalState.currentWeeks.slice();
      modalState.selectedGroups = $.extend(true, {}, modalState.currentGroups);

      openModal();
    });
  }

  /**
   * Normalize groups from old format { blockId: "groupId" } to new { blockId: { groupId: qty } }
   */
  function normalizeGroups(groups) {
    if (!groups || typeof groups !== 'object') return {};
    var normalized = {};
    for (var blockId in groups) {
      if (!groups.hasOwnProperty(blockId)) continue;
      var val = groups[blockId];
      if (typeof val === 'string') {
        normalized[blockId] = {};
        normalized[blockId][val] = 1;
      } else if (typeof val === 'object' && val !== null) {
        normalized[blockId] = {};
        for (var gid in val) {
          if (val.hasOwnProperty(gid)) {
            normalized[blockId][gid] = parseInt(val[gid]) || 0;
          }
        }
      }
    }
    return normalized;
  }

  /* ─── Simular disponibilidad excluyendo fechas actuales ─── */
  function simulateAvailability(baseAvailability, excludeDates, excludeGroups, excludeWeeks, weeksDef, excludeQty) {
    var simulated = {};
    for (var key in baseAvailability) {
      simulated[key] = parseInt(baseAvailability[key]) || 0;
    }

    excludeQty = excludeQty || 1;

    // Free up spots for excluded dates
    excludeDates.forEach(function (date) {
      // Free day-level spots by qty
      if (typeof simulated[date] !== 'undefined') {
        simulated[date] += excludeQty;
      }
      // Free group-level spots by distribution
      if (excludeWeeks && excludeGroups) {
        excludeWeeks.forEach(function (wid) {
          if (excludeGroups[wid]) {
            for (var i = 0; i < weeksDef.length; i++) {
              if (weeksDef[i].id === wid && weeksDef[i].dates.indexOf(date) !== -1) {
                var distribution = excludeGroups[wid];
                for (var gid in distribution) {
                  if (distribution.hasOwnProperty(gid) && distribution[gid] > 0) {
                    var compositeKey = date + '__' + gid;
                    if (typeof simulated[compositeKey] !== 'undefined') {
                      simulated[compositeKey] += distribution[gid];
                    }
                  }
                }
                break;
              }
            }
          }
        });
      }
    });

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

    // Qty selector
    html += '<div id="pn-cm-calendar-edit-qty"></div>';

    // Leyenda
    html += '<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 16px; font-size: 13px;">';
    html += '<span style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #4caf50;"></span> ' + config.i18n.available + '</span>';
    html += '<span style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #1976d2;"></span> ' + config.i18n.selected + '</span>';
    html += '<span style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 50%; background: #9e9e9e;"></span> ' + config.i18n.full + '</span>';
    html += '</div>';

    // Botones de bloques
    html += '<div id="pn-cm-calendar-edit-weeks" style="margin-bottom: 20px;"></div>';

    // Calendario
    html += '<div id="pn-cm-calendar-edit-calendar"></div>';

    // Group selectors
    html += '<div id="pn-cm-calendar-edit-groups"></div>';

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

    renderQtySelector();
    renderWeekButtons();
    renderCalendar();
    renderGroupSelectors();
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

  /* ─── Renderizar selector de plazas ─── */
  function renderQtySelector() {
    var html = '<div class="pn-cm-calendar-qty-selector" style="margin-bottom: 16px;">';
    html += '<span class="pn-cm-calendar-qty-label">' + config.i18n.plazas + ':</span>';
    html += '<button type="button" class="pn-cm-calendar-qty-btn pn-cm-calendar-qty-minus" id="pn-cm-calendar-edit-qty-minus">\u2212</button>';
    html += '<input type="number" class="pn-cm-calendar-qty-value" id="pn-cm-calendar-edit-qty-value" value="' + modalState.qty + '" min="1" max="100" />';
    html += '<button type="button" class="pn-cm-calendar-qty-btn pn-cm-calendar-qty-plus" id="pn-cm-calendar-edit-qty-plus">+</button>';
    html += '</div>';
    $('#pn-cm-calendar-edit-qty').html(html);

    // Bind qty events
    $('#pn-cm-calendar-edit-qty-plus').off('click').on('click', function () {
      setModalQty(modalState.qty + 1);
    });
    $('#pn-cm-calendar-edit-qty-minus').off('click').on('click', function () {
      setModalQty(modalState.qty - 1);
    });
    $('#pn-cm-calendar-edit-qty-value').off('change').on('change', function () {
      setModalQty(parseInt($(this).val()) || 1);
    });
  }

  function setModalQty(newQty) {
    newQty = Math.max(1, Math.min(100, newQty));
    if (newQty === modalState.qty) return;
    modalState.qty = newQty;
    $('#pn-cm-calendar-edit-qty-value').val(newQty);
    renderGroupSelectors();
    renderSummary();
  }

  /* ─── Renderizar botones de bloques ─── */
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
      var basePrice = parseFloat(week.price) || 0;
      var discountedPrice = applyDiscount(basePrice, week.discount, week.discount_type);
      var hasDiscount = parseFloat(week.discount) > 0 && discountedPrice < basePrice;

      html += '<button type="button" class="' + btnClass + '" data-week-id="' + escAttr(week.id) + '" ' + (isDisabled ? 'disabled' : '') + '>';
      html += '<span class="pn-cm-calendar-week-btn-label">' + escHtml(week.label) + '</span>';
      html += '<span class="pn-cm-calendar-week-btn-range">' + dateRange + '</span>';
      if (hasDiscount) {
        html += '<span class="pn-cm-calendar-week-btn-price">';
        html += '<span class="pn-cm-calendar-price-original">' + basePrice.toFixed(2) + ' ' + config.currency + '</span> ';
        html += '<span class="pn-cm-calendar-price-discounted">' + discountedPrice.toFixed(2) + ' ' + config.currency + '</span>';
        html += '</span>';
      } else {
        html += '<span class="pn-cm-calendar-week-btn-price">' + basePrice.toFixed(2) + ' ' + config.currency + '</span>';
      }
      html += '</button>';
    });

    html += '</div>';
    $('#pn-cm-calendar-edit-weeks').html(html);

    // Bind clicks
    $('#pn-cm-calendar-edit-weeks').off('click.editweek').on('click.editweek', '.pn-cm-calendar-week-btn:not(.pn-cm-calendar-week-btn--disabled)', function () {
      var weekId = $(this).data('week-id');
      toggleWeek(weekId);
    });
  }

  /* ─── Verificar si bloque está disponible ─── */
  function isWeekAvailable(week) {
    if (week.groups && week.groups.length > 0) {
      // With groups: available if at least one group has availability on all dates
      for (var g = 0; g < week.groups.length; g++) {
        var groupAvailable = true;
        for (var i = 0; i < week.dates.length; i++) {
          var compositeKey = week.dates[i] + '__' + week.groups[g].id;
          var avail = modalState.availability[compositeKey] || 0;
          if (avail <= 0) {
            groupAvailable = false;
            break;
          }
        }
        if (groupAvailable) return true;
      }
      return false;
    }
    for (var i = 0; i < week.dates.length; i++) {
      var date = week.dates[i];
      var avail = modalState.availability[date] || 0;
      if (avail <= 0) return false;
    }
    return true;
  }

  /* ─── Toggle selección de bloque ─── */
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
      modalState.selectedWeeks.splice(idx, 1);
      week.dates.forEach(function (d) {
        var dIdx = modalState.selectedDates.indexOf(d);
        if (dIdx !== -1) {
          modalState.selectedDates.splice(dIdx, 1);
        }
      });
      delete modalState.selectedGroups[weekId];
    } else {
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
    renderGroupSelectors();
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
    $('#pn-cm-calendar-edit-calendar').off('click.editday').on('click.editday', '.pn-cm-calendar-day--available, .pn-cm-calendar-day--selected', function () {
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

    config.weekDays.forEach(function (wd) {
      html += '<div style="text-align: center; font-weight: 600; font-size: 12px; color: #888; padding: 4px 0;">' + wd + '</div>';
    });

    var firstDay = new Date(year, month - 1, 1);
    var startDow = (firstDay.getDay() + 6) % 7;
    var daysInMonth = new Date(year, month, 0).getDate();

    for (var e = 0; e < startDow; e++) {
      html += '<div></div>';
    }

    for (var d = 1; d <= daysInMonth; d++) {
      var dateStr = year + '-' + pad(month) + '-' + pad(d);
      var isCamp = modalState.allDates[dateStr] || false;
      var avail = modalState.availability[dateStr] || 0;
      var isSelected = modalState.selectedDates.indexOf(dateStr) !== -1;
      var isFull = avail <= 0;

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
      if (isCamp && avail !== null) {
        html += '<span class="pn-cm-calendar-day-avail">' + avail + '</span>';
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
      modalState.selectedDates.splice(idx, 1);

      // Sync weeks
      modalState.weeks.forEach(function (week) {
        if (modalState.selectedWeeks.indexOf(week.id) !== -1) {
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
              delete modalState.selectedGroups[week.id];
            }
          }
        }
      });
    } else {
      modalState.selectedDates.push(date);

      // Check if any week is now complete
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
    renderGroupSelectors();
    renderSummary();
  }

  /* ─── Renderizar selectores de grupo con distribución ─── */
  function renderGroupSelectors() {
    var html = '';

    // Collect all blocks that need group selection (full blocks + partial blocks with groups)
    var blocksNeedingGroups = [];
    modalState.selectedWeeks.forEach(function (weekId) {
      var week = findWeek(weekId);
      if (week && week.groups && week.groups.length > 0) {
        if (blocksNeedingGroups.indexOf(weekId) === -1) blocksNeedingGroups.push(weekId);
      }
    });

    // Also check for partial blocks with groups (dates selected but not all dates of block)
    modalState.weeks.forEach(function (week) {
      if (!week.groups || week.groups.length === 0) return;
      if (modalState.selectedWeeks.indexOf(week.id) !== -1) return; // already handled
      var anySelected = week.dates.some(function (d) {
        return modalState.selectedDates.indexOf(d) !== -1;
      });
      if (anySelected) {
        if (blocksNeedingGroups.indexOf(week.id) === -1) blocksNeedingGroups.push(week.id);
      }
    });

    blocksNeedingGroups.forEach(function (weekId) {
      var week = findWeek(weekId);
      if (!week) return;

      html += '<div class="pn-cm-calendar-group-distribution" data-week-id="' + escAttr(weekId) + '" style="margin: 8px 0; padding: 12px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px;">';
      html += '<label style="display: block; font-weight: 600; margin-bottom: 8px;">' + escHtml(week.label || formatWeekRange(week.dates)) + ' \u2014 ' + config.i18n.distributeSpots + ':</label>';

      var currentDist = modalState.selectedGroups[weekId] || {};
      var distSum = 0;

      week.groups.forEach(function (g) {
        var groupAvail = getGroupMinAvailability(week, g.id);
        var isGroupFull = groupAvail <= 0;
        var currentVal = parseInt(currentDist[g.id]) || 0;
        distSum += currentVal;

        html += '<div class="pn-cm-calendar-group-dist-row" style="display: flex; align-items: center; gap: 8px; padding: 4px 0;">';
        html += '<span class="pn-cm-calendar-group-dist-name" style="min-width: 120px; font-weight: 600;">' + escHtml(g.label) + '</span>';
        html += '<input type="number" class="pn-cm-calendar-order-group-dist-input" data-group-id="' + escAttr(g.id) + '" ';
        html += 'value="' + currentVal + '" min="0" max="' + groupAvail + '" style="width: 60px;"';
        if (isGroupFull) html += ' disabled';
        html += ' />';
        html += '<span style="color: #888; font-size: 12px;">(' + groupAvail + ' ' + config.i18n.spotsLeft + ')</span>';
        html += '</div>';
      });

      // Distribution status
      var statusColor = distSum === modalState.qty ? '#4caf50' : '#f44336';
      html += '<div class="pn-cm-calendar-order-dist-status" style="font-size: 12px; font-weight: 700; color: ' + statusColor + '; margin-top: 4px;">';
      html += distSum + ' / ' + modalState.qty;
      html += '</div>';

      html += '</div>';
    });

    $('#pn-cm-calendar-edit-groups').html(html);

    // Bind group distribution changes
    $('#pn-cm-calendar-edit-groups').off('input.editgroupdist').on('input.editgroupdist', '.pn-cm-calendar-order-group-dist-input', function () {
      var weekId = $(this).closest('.pn-cm-calendar-group-distribution').attr('data-week-id');
      var groupId = $(this).attr('data-group-id');
      var val = Math.max(0, parseInt($(this).val()) || 0);

      if (!modalState.selectedGroups[weekId]) modalState.selectedGroups[weekId] = {};
      modalState.selectedGroups[weekId][groupId] = val;

      // Update status display
      var sum = getDistributionSum(weekId);
      var $status = $(this).closest('.pn-cm-calendar-group-distribution').find('.pn-cm-calendar-order-dist-status');
      $status.text(sum + ' / ' + modalState.qty);
      $status.css('color', sum === modalState.qty ? '#4caf50' : '#f44336');

      renderSummary();
    });
  }

  function findWeek(weekId) {
    for (var i = 0; i < modalState.weeks.length; i++) {
      if (modalState.weeks[i].id === weekId) return modalState.weeks[i];
    }
    return null;
  }

  function getGroupMinAvailability(week, groupId) {
    var min = Infinity;
    for (var i = 0; i < week.dates.length; i++) {
      var compositeKey = week.dates[i] + '__' + groupId;
      var avail = modalState.availability[compositeKey];
      if (typeof avail !== 'undefined' && avail < min) {
        min = avail;
      }
    }
    if (min === Infinity) {
      // Fallback to group capacity
      for (var g = 0; g < week.groups.length; g++) {
        if (week.groups[g].id === groupId) {
          return parseInt(week.groups[g].capacity) || 0;
        }
      }
    }
    return min === Infinity ? 0 : min;
  }

  function getDistributionSum(weekId) {
    if (!modalState.selectedGroups[weekId]) return 0;
    var sum = 0;
    var dist = modalState.selectedGroups[weekId];
    for (var gid in dist) {
      if (dist.hasOwnProperty(gid)) {
        sum += parseInt(dist[gid]) || 0;
      }
    }
    return sum;
  }

  /* ─── Renderizar resumen ─── */
  function renderSummary() {
    var total = calculateTotal();
    var totalWithQty = total * modalState.qty;
    var html = '';

    if (modalState.selectedDates.length === 0) {
      html += '<p style="text-align: center; color: #999;">' + config.i18n.selectAtLeast + '</p>';
    } else {
      html += '<div style="font-weight: 700; font-size: 15px; margin-bottom: 10px;">' + config.i18n.newDates + '</div>';

      // Bloques
      if (modalState.selectedWeeks.length > 0) {
        html += '<div style="margin-bottom: 8px;">';
        modalState.selectedWeeks.forEach(function (weekId) {
          var week = findWeek(weekId);
          if (week) {
            var basePrice = parseFloat(week.price) || 0;
            var finalPrice = applyDiscount(basePrice, week.discount, week.discount_type);
            var hasDiscount = parseFloat(week.discount) > 0 && finalPrice < basePrice;
            var label = escHtml(week.label);

            // Add group distribution info if applicable
            if (week.groups && week.groups.length > 0 && modalState.selectedGroups[weekId]) {
              var groupParts = [];
              var dist = modalState.selectedGroups[weekId];
              for (var gid in dist) {
                if (!dist.hasOwnProperty(gid) || dist[gid] <= 0) continue;
                for (var gi = 0; gi < week.groups.length; gi++) {
                  if (week.groups[gi].id === gid) {
                    groupParts.push(escHtml(week.groups[gi].label) + ' \u00d7 ' + dist[gid]);
                    break;
                  }
                }
              }
              if (groupParts.length > 0) {
                label += ' \u2014 ' + groupParts.join(', ');
              }
            }

            html += '<div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px;">';
            html += '<span>' + label + '</span>';
            if (hasDiscount) {
              html += '<span style="font-weight: 600;"><span class="pn-cm-calendar-price-original">' + basePrice.toFixed(2) + ' ' + config.currency + '</span> <span class="pn-cm-calendar-price-discounted">' + finalPrice.toFixed(2) + ' ' + config.currency + '</span></span>';
            } else {
              html += '<span style="font-weight: 600;">' + finalPrice.toFixed(2) + ' ' + config.currency + '</span>';
            }
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
        html += '<span>' + looseDays.length + ' ' + config.i18n.looseDays + '</span>';
        html += '<span style="font-weight: 600;">' + (looseDays.length * modalState.dayPrice).toFixed(2) + ' ' + config.currency + '</span>';
        html += '</div>';
        html += '</div>';
      }

      // Qty line
      if (modalState.qty > 1) {
        html += '<div class="pn-cm-calendar-qty-line" style="padding: 6px 0; font-size: 14px; color: #666;">';
        html += modalState.qty + ' ' + config.i18n.plazasLine + ' &times; ' + total.toFixed(2) + ' ' + config.currency + ' = ' + totalWithQty.toFixed(2) + ' ' + config.currency;
        html += '</div>';
      }

      // Total
      html += '<div style="display: flex; justify-content: space-between; border-top: 2px solid #1976d2; padding-top: 10px; font-size: 17px; font-weight: 700; color: #1976d2;">';
      html += '<span>' + config.i18n.total + '</span>';
      html += '<span>' + totalWithQty.toFixed(2) + ' ' + config.currency + '</span>';
      html += '</div>';
    }

    $('#pn-cm-calendar-edit-summary').html(html);
  }

  function applyDiscount(price, discount, discountType) {
    discount = parseFloat(discount) || 0;
    if (discount <= 0) return price;
    if (discountType === 'fixed') return Math.max(0, price - discount);
    return Math.max(0, price * (1 - discount / 100));
  }

  /* ─── Calcular total (per plaza) ─── */
  function calculateTotal() {
    var total = 0;

    var coveredDates = [];
    modalState.selectedWeeks.forEach(function (weekId) {
      var week = findWeek(weekId);
      if (week) {
        var basePrice = parseFloat(week.price) || 0;
        total += applyDiscount(basePrice, week.discount, week.discount_type);
        coveredDates = coveredDates.concat(week.dates);
      }
    });

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
      var week = findWeek(weekId);
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

    // Verify group distribution for blocks with groups
    var blocksNeedingGroups = [];
    modalState.selectedWeeks.forEach(function (wid) {
      var w = findWeek(wid);
      if (w && w.groups && w.groups.length > 0) {
        blocksNeedingGroups.push(wid);
      }
    });
    // Also check partial blocks
    modalState.weeks.forEach(function (week) {
      if (!week.groups || week.groups.length === 0) return;
      if (modalState.selectedWeeks.indexOf(week.id) !== -1) return;
      var anySelected = week.dates.some(function (d) {
        return modalState.selectedDates.indexOf(d) !== -1;
      });
      if (anySelected) blocksNeedingGroups.push(week.id);
    });

    for (var i = 0; i < blocksNeedingGroups.length; i++) {
      var wid = blocksNeedingGroups[i];
      var distSum = getDistributionSum(wid);
      if (distSum !== modalState.qty) {
        alert(config.i18n.invalidDistribution);
        return;
      }
    }

    // Verificar disponibilidad
    for (var i = 0; i < modalState.selectedDates.length; i++) {
      var date = modalState.selectedDates[i];
      var avail = modalState.availability[date] || 0;
      if (avail < modalState.qty) {
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
        weeks: JSON.stringify(modalState.selectedWeeks),
        groups: JSON.stringify(modalState.selectedGroups),
        qty: modalState.qty
      },
      success: function (response) {
        if (response.success) {
          $('#pn-cm-calendar-dates-display-' + modalState.itemId).html(response.data.dates_html);
          alert(config.i18n.success);
          closeModal();
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
    if (first === last || dates.length === 1) {
      return formatDateShort(first);
    }
    return formatDateShort(first) + ' - ' + formatDateShort(last);
  }

  function formatDateShort(dateStr) {
    var months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return parseInt(parts[2]) + ' ' + months[parseInt(parts[1]) - 1];
  }

})(jQuery);
