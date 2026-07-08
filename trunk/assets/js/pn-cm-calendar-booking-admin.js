(function ($) {
  'use strict';

  var weekIndex = $('#pn-cm-calendar-weeks-repeater .pn-cm-calendar-week-row').length;

  /* ─── Generar HTML de una nueva semana ─── */
  function getWeekRowHtml(i) {
    var n = i + 1;
    return '' +
      '<div class="pn-cm-calendar-week-row" data-index="' + i + '">' +
        '<div class="pn-cm-calendar-week-header">' +
          '<span class="pn-cm-calendar-week-handle dashicons dashicons-move"></span>' +
          '<span class="pn-cm-calendar-week-number">#' + n + '</span>' +
          '<span class="pn-cm-calendar-week-header-label">Nueva semana</span>' +
          '<button type="button" class="pn-cm-calendar-remove-week" title="Eliminar">' +
            '<span class="dashicons dashicons-trash"></span>' +
          '</button>' +
        '</div>' +
        '<div class="pn-cm-calendar-week-body">' +
          '<div class="pn-cm-calendar-row">' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Identificador</label>' +
              '<input type="text" name="_pn_cm_woo_calendar_weeks[' + i + '][id]" value="" placeholder="semana-' + n + '" />' +
              '<span class="pn-cm-calendar-field-hint">Uso interno, sin espacios</span>' +
            '</div>' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Nombre visible</label>' +
              '<input type="text" name="_pn_cm_woo_calendar_weeks[' + i + '][label]" value="" placeholder="Semana ' + n + '" class="pn-cm-calendar-label-input" />' +
            '</div>' +
          '</div>' +
          '<div class="pn-cm-calendar-row">' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Fecha inicio</label>' +
              '<input type="date" name="_pn_cm_woo_calendar_weeks[' + i + '][date_start]" value="" class="pn-cm-calendar-date-input" />' +
            '</div>' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Fecha fin</label>' +
              '<input type="date" name="_pn_cm_woo_calendar_weeks[' + i + '][date_end]" value="" class="pn-cm-calendar-date-input" />' +
            '</div>' +
            '<div class="pn-cm-calendar-field pn-cm-calendar-field--narrow">' +
              '<label>Aforo/d\u00eda</label>' +
              '<input type="number" name="_pn_cm_woo_calendar_weeks[' + i + '][capacity]" value="25" placeholder="25" min="1" />' +
            '</div>' +
          '</div>' +
          '<div class="pn-cm-calendar-row">' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Precio semana - No socios</label>' +
              '<div class="pn-cm-calendar-input-with-suffix">' +
                '<input type="number" name="_pn_cm_woo_calendar_weeks[' + i + '][price]" value="" placeholder="100.00" step="0.01" min="0" />' +
                '<span class="pn-cm-calendar-suffix">&euro;</span>' +
              '</div>' +
            '</div>' +
            '<div class="pn-cm-calendar-field">' +
              '<label>Precio semana - Socios</label>' +
              '<div class="pn-cm-calendar-input-with-suffix">' +
                '<input type="number" name="_pn_cm_woo_calendar_weeks[' + i + '][member_price]" value="" placeholder="80.00" step="0.01" min="0" />' +
                '<span class="pn-cm-calendar-suffix">&euro;</span>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="pn-cm-calendar-dates-preview"></div>' +
        '</div>' +
      '</div>';
  }

  /* ─── Añadir semana ─── */
  $(document).on('click', '#pn-cm-calendar-add-week', function () {
    var $row = $(getWeekRowHtml(weekIndex));
    $('#pn-cm-calendar-weeks-repeater').append($row);
    weekIndex++;
    // Scroll suave hasta la nueva tarjeta
    $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  /* ─── Eliminar semana ─── */
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
    var val = $(this).val() || 'Nueva semana';
    $(this).closest('.pn-cm-calendar-week-row').find('.pn-cm-calendar-week-header-label').text(val);
  });

  /* ─── Preview de días laborables al cambiar fechas ─── */
  $(document).on('change', '.pn-cm-calendar-date-input', function () {
    var $row = $(this).closest('.pn-cm-calendar-week-row');
    updateDatesPreview($row);
  });

  function updateDatesPreview($row) {
    var startVal = $row.find('input[name*="[date_start]"]').val();
    var endVal   = $row.find('input[name*="[date_end]"]').val();
    var $preview = $row.find('.pn-cm-calendar-dates-preview');

    if (!startVal || !endVal || endVal < startVal) {
      $preview.html('');
      return;
    }

    var dates = getWeekdaysBetween(startVal, endVal);
    if (dates.length === 0) {
      $preview.html('<span class="pn-cm-calendar-preview-warning">No hay d\u00edas laborables (L-V) en este rango</span>');
      return;
    }

    var html = '<span class="pn-cm-calendar-preview-label">' + dates.length + ' d\u00edas laborables:</span> ';
    var pills = dates.map(function (d) {
      return '<span class="pn-cm-calendar-preview-pill">' + formatDateShort(d) + '</span>';
    });
    html += pills.join(' ');
    $preview.html(html);
  }

  function getWeekdaysBetween(start, end) {
    var dates = [];
    var parts = start.split('-');
    var cur = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    var eParts = end.split('-');
    var last = new Date(parseInt(eParts[0]), parseInt(eParts[1]) - 1, parseInt(eParts[2]));
    while (cur <= last) {
      var dow = cur.getDay(); // 0=dom, 6=sab
      if (dow >= 1 && dow <= 5) {
        var y = cur.getFullYear();
        var m = cur.getMonth() + 1;
        var d = cur.getDate();
        dates.push(y + '-' + (m < 10 ? '0' + m : m) + '-' + (d < 10 ? '0' + d : d));
      }
      cur.setDate(cur.getDate() + 1);
    }
    return dates;
  }

  function formatDateShort(dateStr) {
    var months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var parts = dateStr.split('-');
    return parseInt(parts[2]) + ' ' + months[parseInt(parts[1]) - 1];
  }

  /* ─── Reindexar filas al eliminar o reordenar ─── */
  function reindexRows() {
    $('#pn-cm-calendar-weeks-repeater .pn-cm-calendar-week-row').each(function (idx) {
      $(this).attr('data-index', idx);
      $(this).find('.pn-cm-calendar-week-number').text('#' + (idx + 1));
      $(this).find('input, select, textarea').each(function () {
        var name = $(this).attr('name');
        if (name) {
          $(this).attr('name', name.replace(/_pn_cm_woo_calendar_weeks\[\d+\]/, '_pn_cm_woo_calendar_weeks[' + idx + ']'));
        }
      });
    });
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

    // Preview inicial para filas existentes
    $('#pn-cm-calendar-weeks-repeater .pn-cm-calendar-week-row').each(function () {
      updateDatesPreview($(this));
    });
  });

})(jQuery);
