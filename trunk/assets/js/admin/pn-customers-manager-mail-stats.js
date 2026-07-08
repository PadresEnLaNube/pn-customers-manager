/**
 * PN Customers Manager — MailPn Statistics Dashboard JS
 *
 * Handles Chart.js rendering, AJAX period switching,
 * stat-card popup modals, and chart updates.
 */
(function ($) {
  'use strict';

  var cfg = window.pnCmMailStats || {};
  var i18n = cfg.i18n || {};

  // Chart instances
  var chartCombined = null;
  var chartSent = null;
  var chartOpened = null;
  var chartClicked = null;

  /* ──────────────────────────────────
     Initialisation
     ────────────────────────────────── */
  $(document).ready(function () {
    if (!cfg.chartsData) return;

    renderAllCharts(cfg.chartsData);
    bindEvents();
  });

  /* ──────────────────────────────────
     Event bindings
     ────────────────────────────────── */
  function bindEvents() {
    // Period change
    $(document).on('change', '#pn-cm-ms-period-select', function () {
      loadPeriod($(this).val());
    });

    // Widget click → open popup
    $(document).on('click', '.pn-cm-ms-widget[data-popup]', function () {
      var popupId = $(this).data('popup');
      if (popupId) {
        openPopup($('#' + popupId));
      }
    });

    // Close popup
    $(document).on('click', '.pn-cm-ms-popup-close', function () {
      closePopup();
    });

    // Overlay click closes popup
    $(document).on('click', '.pn-cm-ms-overlay', function () {
      closePopup();
    });

    // ESC key closes popup
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape' || e.keyCode === 27) {
        closePopup();
      }
    });
  }

  /* ──────────────────────────────────
     Popup management
     ────────────────────────────────── */
  function openPopup($popup) {
    if (!$popup.length) return;
    $('.pn-cm-ms-overlay').show();
    $popup.show();
  }

  function closePopup() {
    $('.pn-cm-ms-overlay').hide();
    $('.pn-cm-ms-popup').hide();
  }

  /* ──────────────────────────────────
     AJAX period loading
     ────────────────────────────────── */
  function loadPeriod(period) {
    var $wrap = $('.pn-cm-ms-wrap');
    $wrap.addClass('pn-cm-ms-loading');

    $.post(cfg.ajaxUrl, {
      action: 'pn_customers_manager_ajax',
      pn_customers_manager_ajax_type: 'pn_cm_mail_stats_period',
      pn_customers_manager_ajax_nonce: cfg.nonce,
      period: period,
    })
      .done(function (raw) {
        var res = typeof raw === 'string' ? JSON.parse(raw) : raw;

        if (res.error_key !== '') return;

        // Update widget values
        if (res.widgets) {
          $.each(res.widgets, function (key, data) {
            var $widget = $('.pn-cm-ms-widget[data-widget="' + key + '"]');
            $widget.find('.pn-cm-ms-widget-value').text(data.count);
          });
        }

        // Update widget titles
        if (res.labels && res.labels.widget_period && cfg.widgetLabels) {
          $.each(cfg.widgetLabels, function (key, tpl) {
            var $widget = $('.pn-cm-ms-widget[data-widget="' + key + '"]');
            $widget
              .find('.pn-cm-ms-widget-title')
              .text(tpl.replace('%s', res.labels.widget_period));
          });
        }

        // Update popup contents
        if (res.popups) {
          $.each(res.popups, function (key, data) {
            var $popup = $('#pn-cm-ms-popup-' + key);
            if ($popup.length) {
              $popup.find('h2').text(data.title);
              $popup.find('.pn-cm-ms-popup-body').html(data.html);
            }
          });
        }

        // Update chart title
        if (res.labels && res.labels.chart_title) {
          $('#pn-cm-ms-chart-combined-title span').text(res.labels.chart_title);
        }

        // Update charts
        if (res.charts) {
          renderAllCharts(res.charts);
        }
      })
      .fail(function () {
        // silent fail
      })
      .always(function () {
        $wrap.removeClass('pn-cm-ms-loading');
      });
  }

  /* ──────────────────────────────────
     Chart rendering
     ────────────────────────────────── */
  function renderAllCharts(data) {
    if (!data || !data.labels) return;

    renderCombinedChart(data);
    renderBarChart('pn-cm-ms-chart-sent', data.labels, data.sent, 'rgba(63, 81, 181, 0.7)', 'rgba(63, 81, 181, 0.15)', 'sent');
    renderBarChart('pn-cm-ms-chart-opened', data.labels, data.opened, 'rgba(0, 150, 136, 0.7)', 'rgba(0, 150, 136, 0.15)', 'opened');
    renderBarChart('pn-cm-ms-chart-clicked', data.labels, data.clicked, 'rgba(255, 152, 0, 0.7)', 'rgba(255, 152, 0, 0.15)', 'clicked');
  }

  /* Combined line chart (sent + opened + clicked) */
  function renderCombinedChart(data) {
    var ctx = document.getElementById('pn-cm-ms-chart-combined');
    if (!ctx) return;

    if (chartCombined) {
      chartCombined.destroy();
    }

    chartCombined = new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels: data.labels,
        datasets: [
          {
            label: i18n.sent || 'Sent',
            data: data.sent,
            borderColor: '#3f51b5',
            backgroundColor: 'rgba(63, 81, 181, 0.08)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
          },
          {
            label: i18n.opened || 'Opened',
            data: data.opened,
            borderColor: '#009688',
            backgroundColor: 'rgba(0, 150, 136, 0.08)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
          },
          {
            label: i18n.clicked || 'Clicks',
            data: data.clicked,
            borderColor: '#ff9800',
            backgroundColor: 'rgba(255, 152, 0, 0.08)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          intersect: false,
          mode: 'index',
        },
        plugins: {
          legend: {
            position: 'top',
          },
        },
        scales: {
          x: {
            ticks: {
              maxTicksLimit: 12,
              maxRotation: 45,
            },
          },
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
            },
          },
        },
      },
    });
  }

  /* Individual bar charts */
  function renderBarChart(canvasId, labels, data, bgColor, bgColorLight, type) {
    var ctx = document.getElementById(canvasId);
    if (!ctx) return;

    // Destroy previous instance
    if (type === 'sent' && chartSent) { chartSent.destroy(); }
    if (type === 'opened' && chartOpened) { chartOpened.destroy(); }
    if (type === 'clicked' && chartClicked) { chartClicked.destroy(); }

    var chart = new Chart(ctx.getContext('2d'), {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            data: data,
            backgroundColor: bgColor,
            hoverBackgroundColor: bgColor,
            borderRadius: 4,
            borderSkipped: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          x: {
            ticks: {
              maxTicksLimit: 12,
              maxRotation: 45,
            },
          },
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
            },
          },
        },
      },
    });

    // Store reference
    if (type === 'sent') { chartSent = chart; }
    if (type === 'opened') { chartOpened = chart; }
    if (type === 'clicked') { chartClicked = chart; }
  }
})(jQuery);
