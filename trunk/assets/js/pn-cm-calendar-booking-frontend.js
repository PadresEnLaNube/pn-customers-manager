(function ($) {
  'use strict';

  if (typeof pnCmCampData === 'undefined') return;

  var data = pnCmCampData;
  var weeks = data.weeks || [];
  var availability = data.availability || {};
  var selectedDates = [];
  var selectedWeeks = [];
  var selectedGroups = {}; // { blockId: { groupId: qty, ... } }
  var isMember = false;
  var adminMode = false;
  var qty = 1;

  // Normalizar: asegurar que todas las fechas e IDs son strings únicos
  var usedIds = {};
  weeks.forEach(function (w, idx) {
    w.id = String(w.id || '');
    if (!w.id || usedIds[w.id]) {
      w.id = 'week-' + idx;
    }
    usedIds[w.id] = true;
    w.dates = (w.dates || []).map(function (d) { return String(d); });
    // Ensure new fields have defaults
    if (typeof w.discount === 'undefined') w.discount = 0;
    if (!w.discount_type) w.discount_type = 'percent';
    if (!w.groups) w.groups = [];
  });

  var weekMap = {};
  weeks.forEach(function (w) { weekMap[w.id] = w; });

  var allDates = {};
  weeks.forEach(function (w) {
    w.dates.forEach(function (d) { allDates[d] = true; });
  });

  /* ─── Precios activos según toggle ─── */
  function activeDayPrice() {
    var mdp = parseFloat(data.memberDayPrice);
    if (isMember && !isNaN(mdp) && mdp > 0) {
      return mdp;
    }
    return parseFloat(data.dayPrice) || 0;
  }

  function activeWeekPrice(w) {
    var mp = parseFloat(w.member_price);
    if (isMember && !isNaN(mp) && mp > 0) {
      return mp;
    }
    return parseFloat(w.price) || 0;
  }

  function applyDiscount(price, discount, discountType) {
    discount = parseFloat(discount) || 0;
    if (discount <= 0) return price;
    if (discountType === 'fixed') {
      return Math.max(0, price - discount);
    }
    return Math.max(0, price * (1 - discount / 100));
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
    if (!data.enableMemberDiscount && !data.isAdmin) {
      $('#pn-cm-calendar-member-toggle').empty().hide();
      return;
    }
    $('#pn-cm-calendar-member-toggle').show();

    var html = '';
    if (data.enableMemberDiscount) {
      html += '<label class="pn-cm-calendar-member-toggle">';
      html += '<input type="checkbox" id="pn-cm-calendar-member-checkbox"' + (isMember ? ' checked' : '') + ' />';
      html += '<span class="pn-cm-calendar-member-toggle-label">' + escHtml(data.i18n.memberLabel) + '</span>';
      html += '</label>';
    }

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
    var minYear = 9999, minMonth = 13, maxYear = 0, maxMonth = 0;
    Object.keys(allDates).forEach(function (d) {
      var parts = d.split('-');
      var y = parseInt(parts[0]);
      var m = parseInt(parts[1]);
      if (y < minYear || (y === minYear && m < minMonth)) { minYear = y; minMonth = m; }
      if (y > maxYear || (y === maxYear && m > maxMonth)) { maxYear = y; maxMonth = m; }
    });

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
        if (weeks[i].groups && weeks[i].groups.length > 0) {
          var total = 0;
          for (var g = 0; g < weeks[i].groups.length; g++) {
            total += parseInt(weeks[i].groups[g].capacity) || 0;
          }
          return total;
        }
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

  /* ─── Botones de bloque ─── */
  function renderWeekButtons() {
    var html = '<div class="pn-cm-calendar-week-btns">';
    weeks.forEach(function (w) {
      var isSelected = selectedWeeks.indexOf(w.id) !== -1;
      var isDisabled = isWeekFull(w);
      var cls = 'pn-cm-calendar-week-btn';
      if (isSelected) cls += ' pn-cm-calendar-week-btn--selected';
      if (isDisabled && !isSelected) cls += ' pn-cm-calendar-week-btn--disabled';

      var basePrice = activeWeekPrice(w);
      var discountedPrice = applyDiscount(basePrice, w.discount, w.discount_type);
      var hasDiscount = parseFloat(w.discount) > 0 && discountedPrice < basePrice;

      html += '<button type="button" class="' + cls + '" data-week="' + w.id + '"' +
        (isDisabled && !isSelected ? ' disabled' : '') + '>';
      html += '<span class="pn-cm-calendar-week-btn-label">' + escHtml(w.label || formatWeekRange(w.dates)) + '</span>';
      if (w.label) {
        html += '<span class="pn-cm-calendar-week-btn-range">' + formatWeekRange(w.dates) + '</span>';
      }
      if (hasDiscount) {
        html += '<span class="pn-cm-calendar-week-btn-price">';
        html += '<span class="pn-cm-calendar-price-original">' + formatPrice(basePrice) + '</span> ';
        html += '<span class="pn-cm-calendar-price-discounted">' + formatPrice(discountedPrice) + '</span>';
        html += '</span>';
      } else {
        html += '<span class="pn-cm-calendar-week-btn-price">' + formatPrice(basePrice) + '</span>';
      }
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
    if (week.groups && week.groups.length > 0) {
      // A block with groups is full only if ALL groups are full on at least one date
      for (var i = 0; i < week.dates.length; i++) {
        var dateStr = week.dates[i];
        var allGroupsFull = true;
        for (var g = 0; g < week.groups.length; g++) {
          var compositeKey = dateStr + '__' + week.groups[g].id;
          var avail = availability[compositeKey];
          if (typeof avail === 'undefined' || avail > 0) {
            allGroupsFull = false;
            break;
          }
        }
        if (allGroupsFull) return true;
      }
      return false;
    }
    // No groups: full if any date has no availability
    for (var i = 0; i < week.dates.length; i++) {
      var avail = availability[week.dates[i]];
      if (typeof avail !== 'undefined' && avail <= 0) return true;
    }
    return false;
  }

  /* ─── Helpers de arrays ─── */
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
    refresh();
  }

  /**
   * Find the block that contains a given date
   */
  function findBlockForDate(date) {
    for (var i = 0; i < weeks.length; i++) {
      if (weeks[i].dates.indexOf(date) !== -1) return weeks[i];
    }
    return null;
  }

  function toggleWeek(weekId) {
    weekId = String(weekId);
    var week = weekMap[weekId];
    if (!week) return;

    if (hasVal(selectedWeeks, weekId)) {
      week.dates.forEach(function (d) { removeFrom(selectedDates, d); });
      delete selectedGroups[weekId];
    } else {
      week.dates.forEach(function (d) { addUnique(selectedDates, d); });
    }
    refresh();
  }

  /* ─── Selector de plazas (qty) ─── */
  function renderQtySelector() {
    var html = '<div class="pn-cm-calendar-qty-selector">';
    html += '<span class="pn-cm-calendar-qty-label">' + data.i18n.plazas + ':</span>';
    html += '<button type="button" class="pn-cm-calendar-qty-btn pn-cm-calendar-qty-minus" id="pn-cm-calendar-qty-minus">−</button>';
    html += '<input type="number" class="pn-cm-calendar-qty-value" id="pn-cm-calendar-qty-value" value="' + qty + '" min="1" max="100" />';
    html += '<button type="button" class="pn-cm-calendar-qty-btn pn-cm-calendar-qty-plus" id="pn-cm-calendar-qty-plus">+</button>';
    html += '</div>';
    return html;
  }

  function bindQtyEvents() {
    $('#pn-cm-calendar-summary').off('click.qtyplus click.qtyminus change.qtyinput');

    $('#pn-cm-calendar-summary').on('click.qtyplus', '#pn-cm-calendar-qty-plus', function () {
      setQty(qty + 1);
    });

    $('#pn-cm-calendar-summary').on('click.qtyminus', '#pn-cm-calendar-qty-minus', function () {
      setQty(qty - 1);
    });

    $('#pn-cm-calendar-summary').on('change.qtyinput', '#pn-cm-calendar-qty-value', function () {
      setQty(parseInt($(this).val()) || 1);
    });
  }

  function setQty(newQty) {
    newQty = Math.max(1, Math.min(100, newQty));
    if (newQty === qty) return;
    qty = newQty;
    $('#pn-cm-calendar-qty-input').val(qty);
    renderSummary();
  }

  /**
   * Get sum of group distribution for a block
   */
  function getDistributionSum(weekId) {
    if (!selectedGroups[weekId]) return 0;
    var sum = 0;
    var dist = selectedGroups[weekId];
    for (var gid in dist) {
      if (dist.hasOwnProperty(gid)) {
        sum += parseInt(dist[gid]) || 0;
      }
    }
    return sum;
  }

  /**
   * Check if group distribution is valid for all blocks
   */
  function isDistributionValid() {
    var blockIds = getBlocksNeedingGroups();
    for (var i = 0; i < blockIds.length; i++) {
      var wid = blockIds[i];
      if (getDistributionSum(wid) !== qty) return false;
    }
    return true;
  }

  /**
   * Get all block IDs that need group selection (blocks with groups that have selected dates)
   */
  function getBlocksNeedingGroups() {
    var result = [];
    var allActiveBlocks = selectedWeeks.concat(partialGroupBlockIds);
    for (var i = 0; i < allActiveBlocks.length; i++) {
      var wid = allActiveBlocks[i];
      var w = weekMap[wid];
      if (w && w.groups && w.groups.length > 0) {
        if (result.indexOf(wid) === -1) result.push(wid);
      }
    }
    return result;
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
      var basePrice = activeWeekPrice(w);
      var discountedPrice = applyDiscount(basePrice, w.discount, w.discount_type);
      var hasDiscount = parseFloat(w.discount) > 0 && discountedPrice < basePrice;
      coveredDates = coveredDates.concat(w.dates);
      var weekLabel = w.label ? w.label + ' (' + formatWeekRange(w.dates) + ')' : formatWeekRange(w.dates);

      var line = { label: weekLabel, price: discountedPrice, originalPrice: hasDiscount ? basePrice : null, weekId: wid, groups: w.groups || [] };
      lines.push(line);
      total += discountedPrice;
    });

    var looseDays = selectedDates.filter(function (d) {
      return coveredDates.indexOf(d) === -1;
    });
    looseDays.sort();

    // Group loose days by block (for blocks with groups, so we can show the group selector)
    var looseDaysByBlock = {}; // blockId -> [dates]
    var trulyLooseDays = [];
    looseDays.forEach(function (d) {
      var block = findBlockForDate(d);
      if (block && block.groups && block.groups.length > 0) {
        if (!looseDaysByBlock[block.id]) looseDaysByBlock[block.id] = [];
        looseDaysByBlock[block.id].push(d);
      } else {
        trulyLooseDays.push(d);
      }
    });

    // Add grouped loose days (with group selector)
    Object.keys(looseDaysByBlock).forEach(function (blockId) {
      var w = weekMap[blockId];
      if (!w) return;
      var dates = looseDaysByBlock[blockId];
      var effectiveDayPrice = dp;
      if (effectiveDayPrice <= 0) {
        var baseWeekPrice = activeWeekPrice(w);
        if (baseWeekPrice > 0 && w.dates.length > 0) {
          effectiveDayPrice = baseWeekPrice / w.dates.length;
        }
      }
      var dayTotal = effectiveDayPrice * dates.length;
      var datesLabel = dates.map(function (d) { return formatDateShort(d); }).join(', ');
      var blockLabel = (w.label ? w.label + ': ' : '') + datesLabel;
      lines.push({ label: blockLabel, price: dayTotal, originalPrice: null, weekId: blockId, groups: w.groups });
      total += dayTotal;
    });

    // Add truly loose days (no groups)
    trulyLooseDays.forEach(function (d) {
      var effectiveDayPrice = dp;
      if (effectiveDayPrice <= 0) {
        var parentBlock = findBlockForDate(d);
        if (parentBlock) {
          var baseWeekPrice = activeWeekPrice(parentBlock);
          if (baseWeekPrice > 0 && parentBlock.dates.length > 0) {
            effectiveDayPrice = baseWeekPrice / parentBlock.dates.length;
          }
        }
      }
      lines.push({ label: formatDateShort(d), price: effectiveDayPrice, originalPrice: null, weekId: null, groups: [] });
      total += effectiveDayPrice;
    });

    // Multiply total by qty
    var totalWithQty = total * qty;

    var html = '';
    if (lines.length === 0) {
      html = '<div class="pn-cm-calendar-summary-empty">' + data.i18n.noSelection + '</div>';
    } else {
      // Qty selector
      html += renderQtySelector();

      html += '<div class="pn-cm-calendar-summary-title">' + data.i18n.yourSelection + ':</div>';
      html += '<div class="pn-cm-calendar-summary-lines">';
      lines.forEach(function (l) {
        html += '<div class="pn-cm-calendar-summary-line">';
        html += '<span class="pn-cm-calendar-summary-label">' + escHtml(l.label) + '</span>';
        if (l.originalPrice !== null) {
          html += '<span class="pn-cm-calendar-summary-price">';
          html += '<span class="pn-cm-calendar-price-original">' + formatPrice(l.originalPrice) + '</span> ';
          html += '<span class="pn-cm-calendar-price-discounted">' + formatPrice(l.price) + '</span>';
          html += '</span>';
        } else {
          html += '<span class="pn-cm-calendar-summary-price">' + formatPrice(l.price) + '</span>';
        }
        html += '</div>';

        // Group distribution for blocks with groups
        if (l.groups.length > 0 && l.weekId) {
          html += renderGroupDistribution(l.weekId, l.groups);
        }
      });
      html += '</div>';

      // Qty line (show "N plazas × price = total" when qty > 1)
      if (qty > 1) {
        html += '<div class="pn-cm-calendar-qty-line">';
        html += qty + ' ' + data.i18n.plazasLine + ' &times; ' + formatPrice(total) + ' = ' + formatPrice(totalWithQty);
        html += '</div>';
      }

      html += '<div class="pn-cm-calendar-summary-total">';
      html += '<span>' + data.i18n.total + ':</span>';
      html += '<span>' + formatPrice(totalWithQty) + '</span>';
      html += '</div>';
    }

    $('#pn-cm-calendar-summary').html(html);

    bindQtyEvents();

    // Bind group distribution input changes
    $('#pn-cm-calendar-summary').off('change.campgroup input.campgroup').on('input.campgroup', '.pn-cm-calendar-group-dist-input', function () {
      var weekId = $(this).closest('.pn-cm-calendar-group-distribution').attr('data-week-id');
      var groupId = $(this).attr('data-group-id');
      var val = Math.max(0, parseInt($(this).val()) || 0);

      if (!selectedGroups[weekId]) selectedGroups[weekId] = {};
      selectedGroups[weekId][groupId] = val;

      // Update the distribution status display
      updateDistributionStatus(weekId);
      updateHiddenInputs();
    });

    updateHiddenInputs();
  }

  /**
   * Render group distribution inputs for a block
   */
  function renderGroupDistribution(weekId, groups) {
    var html = '<div class="pn-cm-calendar-group-distribution" data-week-id="' + weekId + '">';
    html += '<div class="pn-cm-calendar-group-distribution-label">' + data.i18n.distributeSpots + ':</div>';

    var currentDist = selectedGroups[weekId] || {};
    var distSum = 0;

    groups.forEach(function (g) {
      var groupAvail = getGroupMinAvailability(weekId, g.id);
      var isGroupFull = groupAvail <= 0;
      var currentVal = parseInt(currentDist[g.id]) || 0;
      distSum += currentVal;

      var optCls = 'pn-cm-calendar-group-dist-row';
      if (isGroupFull) optCls += ' pn-cm-calendar-group-dist-row--full';

      html += '<div class="' + optCls + '">';
      html += '<span class="pn-cm-calendar-group-dist-name">' + escHtml(g.label) + '</span>';
      html += '<input type="number" class="pn-cm-calendar-group-dist-input" data-group-id="' + g.id + '" ';
      html += 'value="' + currentVal + '" min="0" max="' + groupAvail + '"';
      if (isGroupFull) html += ' disabled';
      html += ' />';
      html += '<span class="pn-cm-calendar-group-dist-avail">(' + groupAvail + ' ' + data.i18n.spotsLeft + ')</span>';
      html += '</div>';
    });

    // Distribution status
    var statusCls = 'pn-cm-calendar-group-dist-status';
    if (distSum === qty) {
      statusCls += ' pn-cm-calendar-group-dist-status--ok';
    } else {
      statusCls += ' pn-cm-calendar-group-dist-status--error';
    }
    html += '<div class="' + statusCls + '" id="pn-cm-calendar-dist-status-' + weekId + '">';
    html += distSum + ' / ' + qty;
    html += '</div>';

    html += '</div>';
    return html;
  }

  /**
   * Update distribution status display after input change
   */
  function updateDistributionStatus(weekId) {
    var sum = getDistributionSum(weekId);
    var $status = $('#pn-cm-calendar-dist-status-' + weekId);
    $status.text(sum + ' / ' + qty);
    $status.removeClass('pn-cm-calendar-group-dist-status--ok pn-cm-calendar-group-dist-status--error');
    if (sum === qty) {
      $status.addClass('pn-cm-calendar-group-dist-status--ok');
    } else {
      $status.addClass('pn-cm-calendar-group-dist-status--error');
    }
  }

  /**
   * Get minimum availability for a group across all dates in a block.
   * Falls back to the group's defined capacity when no availability data exists.
   */
  function getGroupMinAvailability(weekId, groupId) {
    var w = weekMap[weekId];
    if (!w) return 0;

    // Find group capacity as fallback
    var groupCapacity = 0;
    for (var g = 0; g < w.groups.length; g++) {
      if (w.groups[g].id === groupId) {
        groupCapacity = parseInt(w.groups[g].capacity) || 0;
        break;
      }
    }

    var min = Infinity;
    for (var i = 0; i < w.dates.length; i++) {
      var compositeKey = w.dates[i] + '__' + groupId;
      var avail = availability[compositeKey];
      if (typeof avail !== 'undefined') {
        if (avail < min) min = avail;
      }
    }
    // If no composite keys found, fall back to group capacity (no bookings yet)
    return min === Infinity ? groupCapacity : min;
  }

  function updateHiddenInputs() {
    $('#pn-cm-calendar-dates-input').val(JSON.stringify(selectedDates.slice().sort()));
    $('#pn-cm-calendar-weeks-input').val(JSON.stringify(selectedWeeks));
    $('#pn-cm-calendar-groups-input').val(JSON.stringify(selectedGroups));
    $('#pn-cm-calendar-qty-input').val(qty);
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

  function syncWeeksWithDates() {
    var newWeeks = [];
    weeks.forEach(function (w) {
      if (w.dates.length === 0) return;
      var allSelected = w.dates.every(function (d) { return hasVal(selectedDates, d); });
      if (allSelected) newWeeks.push(w.id);
    });
    // Also track blocks with groups that have ANY date selected (for group selector on loose days)
    var partialGroupBlocks = [];
    weeks.forEach(function (w) {
      if (w.dates.length === 0 || !w.groups || w.groups.length === 0) return;
      if (newWeeks.indexOf(w.id) !== -1) return; // already fully selected
      var anySelected = w.dates.some(function (d) { return hasVal(selectedDates, d); });
      if (anySelected) partialGroupBlocks.push(w.id);
    });
    // Clean up groups for deselected weeks (keep groups for both full and partial blocks)
    var activeBlockIds = newWeeks.concat(partialGroupBlocks);
    var newGroups = {};
    activeBlockIds.forEach(function (wid) {
      if (selectedGroups[wid]) {
        newGroups[wid] = selectedGroups[wid];
      }
    });
    selectedWeeks = newWeeks;
    selectedGroups = newGroups;
    partialGroupBlockIds = partialGroupBlocks;
  }

  // Track partially-selected blocks with groups (for group selector on loose days)
  var partialGroupBlockIds = [];

  /* ─── Validación antes de add to cart ─── */
  $(document).on('click', '.single_add_to_cart_button', function (e) {
    var blocksMissingGroup = getBlocksNeedingGroups();
    for (var i = 0; i < blocksMissingGroup.length; i++) {
      var wid = blocksMissingGroup[i];
      var distSum = getDistributionSum(wid);
      if (distSum !== qty) {
        e.preventDefault();
        e.stopImmediatePropagation();
        alert(data.i18n.invalidDistribution);
        var $summary = $('#pn-cm-calendar-summary');
        if ($summary.length) {
          $summary[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
      }
    }
  });

  /* ─── Formato ─── */
  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function formatPrice(amount) {
    return parseFloat(amount).toFixed(2).replace('.', ',') + ' ' + data.currency;
  }

  function formatDateShort(dateStr) {
    var parts = dateStr.split('-');
    var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return parseInt(parts[2]) + ' ' + monthNames[parseInt(parts[1]) - 1];
  }

  function formatWeekRange(dates) {
    if (!dates || dates.length === 0) return '';
    if (dates.length === 1 || dates[0] === dates[dates.length - 1]) {
      return formatDateShort(dates[0]);
    }
    return formatDateShort(dates[0]) + ' - ' + formatDateShort(dates[dates.length - 1]);
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  /* ─── Admin: popup de reservas ─── */
  function openAdminPopup(dateStr) {
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
    var groupsBreakdown = resData.groups_breakdown || [];

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

    // Groups breakdown
    if (groupsBreakdown.length > 0) {
      html += '<div class="pn-cm-calendar-admin-modal-groups">';
      html += '<div class="pn-cm-calendar-admin-modal-groups-title">' + data.i18n.group + ':</div>';
      groupsBreakdown.forEach(function (g) {
        var gPct = g.capacity > 0 ? Math.round((g.booked / g.capacity) * 100) : 0;
        var gCls = gPct > 80 ? 'high' : (gPct >= 50 ? 'mid' : 'low');
        html += '<div class="pn-cm-calendar-admin-modal-group-item">';
        html += '<span class="pn-cm-calendar-admin-modal-group-label">' + escHtml(g.label) + '</span>';
        html += '<span class="pn-cm-calendar-admin-modal-group-occ">' + g.booked + '/' + g.capacity + '</span>';
        html += '<div class="pn-cm-calendar-admin-modal-group-bar"><span class="pn-cm-calendar-day-bar-fill pn-cm-calendar-day-bar--' + gCls + '" style="width:' + gPct + '%"></span></div>';
        html += '</div>';
      });
      html += '</div>';
    }

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
      html += '<th>' + data.i18n.plazas + '</th>';
      if (groupsBreakdown.length > 0) {
        html += '<th>' + data.i18n.group + '</th>';
      }
      html += '<th>' + data.i18n.adminNotes + '</th>';
      html += '<th>' + data.i18n.adminOrder + '</th>';
      html += '</tr></thead><tbody>';

      reservations.forEach(function (r) {
        html += '<tr>';
        html += '<td data-label="' + data.i18n.adminName + '">' + escHtml(r.first_name + ' ' + r.last_name) + '</td>';
        html += '<td data-label="' + data.i18n.adminEmail + '"><a href="mailto:' + escHtml(r.email) + '">' + escHtml(r.email) + '</a></td>';
        html += '<td data-label="' + data.i18n.adminPhone + '">' + (r.phone ? '<a href="tel:' + escHtml(r.phone) + '">' + escHtml(r.phone) + '</a>' : '-') + '</td>';
        html += '<td data-label="' + data.i18n.plazas + '">' + (r.qty || 1) + '</td>';
        if (groupsBreakdown.length > 0) {
          html += '<td data-label="' + data.i18n.group + '">' + (r.group ? escHtml(r.group) : '-') + '</td>';
        }
        html += '<td data-label="' + data.i18n.adminNotes + '">' + (r.notes ? escHtml(r.notes) : '-') + '</td>';
        html += '<td data-label="' + data.i18n.adminOrder + '"><a href="' + escHtml(r.edit_url) + '" target="_blank">#' + r.order_id + '</a><br><small>' + escHtml(r.order_date) + '</small></td>';
        html += '</tr>';
      });

      html += '</tbody></table>';
      html += '</div>';
    }

    html += '</div></div>';
    $('body').append(html);

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
    var dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var dt = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    return dayNames[dt.getDay()] + ' ' + monthNames[parseInt(parts[1]) - 1] + ' ' + parseInt(parts[2]) + ', ' + parts[0];
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
