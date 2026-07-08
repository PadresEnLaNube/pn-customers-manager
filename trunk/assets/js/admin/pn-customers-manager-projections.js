/**
 * PN Customers Manager — Projections Dashboard JS
 *
 * Handles Chart.js rendering, AJAX data loading, tab switching,
 * manual projection creation/deletion and social media tests.
 */
(function ($) {
  'use strict';

  var cfg = window.pnCmProjections || {};
  var i18n = cfg.i18n || {};
  var lineChart = null;

  var state = {
    source: 'crm',
    metric: '',
    currentMetrics: [],
    projections: [],
  };

  /* ──────────────────────────────────
     Initialisation
     ────────────────────────────────── */
  $(document).ready(function () {
    var $activeTab = $('.pn-cm-proj-tab.active');
    if ($activeTab.length) {
      state.source = $activeTab.data('source') || 'crm';
    }

    bindEvents();
    loadData();
  });

  /* ──────────────────────────────────
     Event bindings
     ────────────────────────────────── */
  function bindEvents() {
    // Tab switch
    $(document).on('click', '.pn-cm-proj-tab', function () {
      var src = $(this).data('source');
      $('.pn-cm-proj-tab').removeClass('active');
      $(this).addClass('active');

      if (src === 'social') {
        $('.pn-cm-proj-social-subtabs').show();
        var $firstSub = $('.pn-cm-proj-social-subtab').first();
        if ($firstSub.length) {
          $('.pn-cm-proj-social-subtab').removeClass('active');
          $firstSub.addClass('active');
          state.source = $firstSub.data('source');
        }
      } else {
        $('.pn-cm-proj-social-subtabs').hide();
        state.source = src;
      }

      state.metric = '';
      loadData();
    });

    // Social media sub-tab
    $(document).on('click', '.pn-cm-proj-social-subtab', function () {
      $('.pn-cm-proj-social-subtab').removeClass('active');
      $(this).addClass('active');
      state.source = $(this).data('source');
      state.metric = '';
      loadData();
    });

    // Metric selector
    $(document).on('change', '#pn-cm-proj-metric', function () {
      state.metric = $(this).val();
      loadData();
    });

    // Open create projection modal
    $(document).on('click', '#pn-cm-proj-open-modal', function () {
      openModal();
    });

    // Close modal
    $(document).on('click', '.pn-cm-proj-modal-close, .pn-cm-proj-modal-cancel, .pn-cm-proj-modal-backdrop', function () {
      closeModal();
    });

    // Form: source changes update metric list
    $(document).on('change', '#pn-cm-proj-form-source', function () {
      loadMetricsForForm($(this).val());
    });

    // Form submit
    $(document).on('submit', '#pn-cm-proj-form', function (e) {
      e.preventDefault();
      createProjection();
    });

    // Delete projection
    $(document).on('click', '.pn-cm-proj-delete', function () {
      var id = $(this).data('id');
      if (confirm(i18n.confirmDelete || 'Delete this projection?')) {
        deleteProjection(id);
      }
    });
  }

  /* ──────────────────────────────────
     AJAX: Load data
     ────────────────────────────────── */
  function loadData() {
    $.post(cfg.ajaxUrl, {
      action: 'pn_customers_manager_ajax',
      pn_customers_manager_ajax_type: 'pn_cm_projections_data',
      pn_customers_manager_ajax_nonce: cfg.nonce,
      source: state.source,
      metric: state.metric,
    }, function (response) {
      var data;
      try {
        data = typeof response === 'string' ? JSON.parse(response) : response;
      } catch (err) {
        console.error('Projections: invalid JSON', response);
        return;
      }

      if (data.error_key) {
        console.error('Projections error:', data.error_key);
        return;
      }

      state.currentMetrics = data.available_metrics || [];
      state.metric = data.current_metric || '';
      state.projections = data.projections || [];

      populateMetricSelector(state.currentMetrics, state.metric);
      renderLineChart(data.historical || [], state.projections);
      renderProjectionsList(state.projections);
    });
  }

  /* ──────────────────────────────────
     Populate metric selector
     ────────────────────────────────── */
  function populateMetricSelector(metrics, current) {
    var $sel = $('#pn-cm-proj-metric');
    $sel.empty();
    if (!metrics.length) {
      $sel.append('<option value="">' + (i18n.noData || 'No data') + '</option>');
      return;
    }
    metrics.forEach(function (m) {
      var selected = m === current ? ' selected' : '';
      $sel.append('<option value="' + escapeHtml(m) + '"' + selected + '>' + escapeHtml(m) + '</option>');
    });
  }

  /* ──────────────────────────────────
     Render line chart: historical + projections overlay
     ────────────────────────────────── */
  function renderLineChart(historical, projections) {
    var ctx = document.getElementById('pn-cm-proj-line-chart');
    if (!ctx) return;

    if (!historical.length && !projections.length) {
      $('#pn-cm-proj-no-data').show();
      if (lineChart) {
        lineChart.destroy();
        lineChart = null;
      }
      return;
    }
    $('#pn-cm-proj-no-data').hide();

    // Build a unified sorted list of dates
    var datesSet = {};
    historical.forEach(function (h) { datesSet[h.snapshot_date] = true; });
    projections.forEach(function (p) { datesSet[p.target_date] = true; });

    var labels = Object.keys(datesSet).sort();

    // Build dataset by matching each label
    var historicalMap = {};
    historical.forEach(function (h) { historicalMap[h.snapshot_date] = parseFloat(h.metric_value); });

    var projectionMap = {};
    projections.forEach(function (p) { projectionMap[p.target_date] = parseFloat(p.projected_value); });

    var histData = labels.map(function (d) {
      return historicalMap.hasOwnProperty(d) ? historicalMap[d] : null;
    });
    var projData = labels.map(function (d) {
      return projectionMap.hasOwnProperty(d) ? projectionMap[d] : null;
    });

    if (lineChart) lineChart.destroy();

    lineChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: i18n.historical || 'Historical',
            data: histData,
            borderColor: '#0073aa',
            backgroundColor: 'rgba(0,115,170,0.1)',
            fill: true,
            tension: 0.25,
            spanGaps: true,
          },
          {
            label: i18n.projected || 'Manual projection',
            data: projData,
            borderColor: '#ff9800',
            backgroundColor: 'rgba(255,152,0,0.15)',
            borderDash: [6, 4],
            pointStyle: 'rectRot',
            pointRadius: 6,
            pointHoverRadius: 8,
            fill: false,
            tension: 0,
            spanGaps: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
        },
        scales: {
          y: { beginAtZero: true },
        },
      },
    });
  }

  /* ──────────────────────────────────
     Render manual projections list
     ────────────────────────────────── */
  function renderProjectionsList(projections) {
    var $tbody = $('#pn-cm-proj-list-table tbody');
    $tbody.empty();

    if (!projections.length) {
      $('#pn-cm-proj-list-table').hide();
      $('#pn-cm-proj-list-empty').show();
      return;
    }

    $('#pn-cm-proj-list-empty').hide();
    $('#pn-cm-proj-list-table').show();

    projections.forEach(function (p) {
      var actual = '—';
      var deviation = '<span class="pn-cm-proj-pending">' + (i18n.pending || 'Pending') + '</span>';
      if (p.deviation) {
        actual = formatNumber(p.deviation.actual);
        var devPct = p.deviation.deviation_pct;
        var devClass = devPct >= 0 ? 'pn-cm-proj-dev-positive' : 'pn-cm-proj-dev-negative';
        deviation = '<span class="' + devClass + '">' + (devPct >= 0 ? '+' : '') + devPct + '%</span>';
      }

      var row = '<tr>'
        + '<td>' + escapeHtml(p.target_date) + '</td>'
        + '<td>' + formatNumber(p.projected_value) + '</td>'
        + '<td>' + actual + '</td>'
        + '<td>' + deviation + '</td>'
        + '<td>' + escapeHtml(p.notes || '') + '</td>'
        + '<td><button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-cm-proj-delete" data-id="' + p.id + '">'
        + '<span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">delete</span> '
        + escapeHtml(i18n.delete || 'Delete')
        + '</button></td>'
        + '</tr>';
      $tbody.append(row);
    });
  }

  /* ──────────────────────────────────
     Modal: open / close
     ────────────────────────────────── */
  function openModal() {
    // Prefill the form with the current source/metric
    $('#pn-cm-proj-form-source').val(state.source);
    loadMetricsForForm(state.source, state.metric);

    // Default target date = today + 30 days
    var d = new Date();
    d.setDate(d.getDate() + 30);
    var yyyy = d.getFullYear();
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var dd = String(d.getDate()).padStart(2, '0');
    $('#pn-cm-proj-form-date').val(yyyy + '-' + mm + '-' + dd);

    $('#pn-cm-proj-form-value').val('');
    $('#pn-cm-proj-form-notes').val('');
    $('#pn-cm-proj-form-status').text('');

    $('#pn-cm-proj-modal').css('display', 'flex');
  }

  function closeModal() {
    $('#pn-cm-proj-modal').hide();
  }

  /* ──────────────────────────────────
     Form: load metric options for the given source
     ────────────────────────────────── */
  function loadMetricsForForm(source, preferredMetric) {
    $.post(cfg.ajaxUrl, {
      action: 'pn_customers_manager_ajax',
      pn_customers_manager_ajax_type: 'pn_cm_projections_data',
      pn_customers_manager_ajax_nonce: cfg.nonce,
      source: source,
      metric: '',
    }, function (response) {
      var data;
      try {
        data = typeof response === 'string' ? JSON.parse(response) : response;
      } catch (err) {
        return;
      }
      var metrics = data.available_metrics || [];
      var $sel = $('#pn-cm-proj-form-metric');
      $sel.empty();
      if (!metrics.length) {
        $sel.append('<option value="">' + (i18n.noData || 'No data') + '</option>');
        return;
      }
      metrics.forEach(function (m) {
        var selected = (preferredMetric && m === preferredMetric) ? ' selected' : '';
        $sel.append('<option value="' + escapeHtml(m) + '"' + selected + '>' + escapeHtml(m) + '</option>');
      });
    });
  }

  /* ──────────────────────────────────
     Create projection
     ────────────────────────────────── */
  function createProjection() {
    var source = $('#pn-cm-proj-form-source').val();
    var metric = $('#pn-cm-proj-form-metric').val();
    var targetDate = $('#pn-cm-proj-form-date').val();
    var projectedValue = $('#pn-cm-proj-form-value').val();
    var notes = $('#pn-cm-proj-form-notes').val();

    $('#pn-cm-proj-form-status').text(i18n.saving || 'Saving...');

    $.post(cfg.ajaxUrl, {
      action: 'pn_customers_manager_ajax',
      pn_customers_manager_ajax_type: 'pn_cm_projection_create',
      pn_customers_manager_ajax_nonce: cfg.nonce,
      source: source,
      metric: metric,
      target_date: targetDate,
      projected_value: projectedValue,
      notes: notes,
    }, function (response) {
      var data;
      try {
        data = typeof response === 'string' ? JSON.parse(response) : response;
      } catch (err) {
        $('#pn-cm-proj-form-status').text(i18n.saveError || 'Error');
        return;
      }

      if (data.error_key) {
        $('#pn-cm-proj-form-status').text(data.error_content || i18n.saveError || 'Error');
        return;
      }

      $('#pn-cm-proj-form-status').text(i18n.saved || 'Saved');

      // Switch view to the new projection's source/metric
      state.source = source;
      state.metric = metric;
      updateActiveTab(source);

      setTimeout(function () {
        closeModal();
        loadData();
      }, 400);
    });
  }

  /* ──────────────────────────────────
     Delete projection
     ────────────────────────────────── */
  function deleteProjection(id) {
    $.post(cfg.ajaxUrl, {
      action: 'pn_customers_manager_ajax',
      pn_customers_manager_ajax_type: 'pn_cm_projection_delete',
      pn_customers_manager_ajax_nonce: cfg.nonce,
      id: id,
    }, function (response) {
      var data;
      try {
        data = typeof response === 'string' ? JSON.parse(response) : response;
      } catch (err) {
        return;
      }
      if (data.error_key) {
        alert(data.error_content || i18n.deleteError || 'Error');
        return;
      }
      loadData();
    });
  }

  /* ──────────────────────────────────
     Helpers
     ────────────────────────────────── */
  function updateActiveTab(source) {
    var socialSources = ['instagram', 'facebook', 'twitter'];
    $('.pn-cm-proj-tab').removeClass('active');
    if (socialSources.indexOf(source) !== -1) {
      $('.pn-cm-proj-tab[data-source="social"]').addClass('active');
      $('.pn-cm-proj-social-subtabs').show();
      $('.pn-cm-proj-social-subtab').removeClass('active');
      $('.pn-cm-proj-social-subtab[data-source="' + source + '"]').addClass('active');
    } else {
      $('.pn-cm-proj-tab[data-source="' + source + '"]').addClass('active');
      $('.pn-cm-proj-social-subtabs').hide();
    }
  }

  function formatNumber(n) {
    if (n === null || n === undefined || n === '') return '—';
    var num = parseFloat(n);
    if (isNaN(num)) return '—';
    return num.toLocaleString(undefined, { maximumFractionDigits: 2 });
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
})(jQuery);
