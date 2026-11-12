/**
 * CRM Clients Dashboard JS.
 *
 * Handles UI interactions and AJAX calls. All HTML rendering is done server-side
 * in PHP; this file only manages state, events, and DOM insertion.
 *
 * @since 1.2.48
 */
(function ($) {
  'use strict';

  var cfg  = window.pnCmClients || {};
  var i18n = cfg.i18n || {};
  var currentView = 'grid';
  var currentStage = '';
  var currentSource = '';
  var currentOrderby = 'date';
  var currentOrder = 'DESC';
  var currentPage = 1;
  var currentPerPage = 20;
  var searchTimer = null;

  function init() {
    var $pp = $('#pn-cm-clients-per-page');
    if ($pp.length) {
      currentPerPage = parseInt($pp.val(), 10) || 20;
    }
    loadClients();
    bindEvents();
    checkDuplicates();
  }

  function bindEvents() {
    // View toggle.
    $(document).on('click', '.pn-cm-clients-view-btn', function () {
      $('.pn-cm-clients-view-btn').removeClass('active');
      $(this).addClass('active');
      currentView = $(this).data('view');
      var $content = $('#pn-cm-clients-content');
      $content.removeClass('pn-cm-clients-grid pn-cm-clients-list');
      $content.addClass(currentView === 'list' ? 'pn-cm-clients-list' : 'pn-cm-clients-grid');
      loadClients();
    });

    // Sort toggle button.
    $(document).on('click', '#pn-cm-clients-sort-toggle', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $panel = $('#pn-cm-clients-sort-panel');
      $('#pn-cm-clients-filter-panel').hide();
      $panel.toggle();
    });

    // Filter toggle button.
    $(document).on('click', '#pn-cm-clients-filter-toggle', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $panel = $('#pn-cm-clients-filter-panel');
      $('#pn-cm-clients-sort-panel').hide();
      $panel.toggle();
    });

    // Close dropdowns when clicking outside.
    $(document).on('click', function (e) {
      if (!$(e.target).closest('#pn-cm-clients-sort-toggle, #pn-cm-clients-sort-panel').length) {
        $('#pn-cm-clients-sort-panel').hide();
      }
      if (!$(e.target).closest('#pn-cm-clients-filter-toggle, #pn-cm-clients-filter-panel').length) {
        $('#pn-cm-clients-filter-panel').hide();
      }
    });

    // Stage tabs.
    $(document).on('click', '.pn-cm-clients-stage-tab', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $('.pn-cm-clients-stage-tab').removeClass('active');
      $(this).addClass('active');
      currentStage = $(this).data('stage');
      currentPage = 1;
      loadClients();
    });

    // Search with debounce.
    $(document).on('input', '#pn-cm-clients-search, .pn-cm-clients-search', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        currentPage = 1;
        loadClients();
      }, 400);
    });

    // Source filter.
    $(document).on('change', '#pn-cm-clients-source-filter', function () {
      currentSource = $(this).val();
      currentPage = 1;
      loadClients();
    });

    // Order by.
    $(document).on('change', '#pn-cm-clients-orderby', function () {
      currentOrderby = $(this).val();
      currentPage = 1;
      loadClients();
    });

    // Order direction toggle.
    $(document).on('click', '#pn-cm-clients-order-toggle', function () {
      var $btn = $(this);
      var $label = $btn.find('.pn-cm-clients-order-label');
      if (currentOrder === 'ASC') {
        currentOrder = 'DESC';
        $btn.data('order', 'DESC');
        $btn.attr('title', i18n.orderDesc || 'Descending');
        $btn.find('.material-icons-outlined').text('arrow_downward');
        if ($label.length) {
          $label.text(i18n.orderDesc || 'Descending');
        }
      } else {
        currentOrder = 'ASC';
        $btn.data('order', 'ASC');
        $btn.attr('title', i18n.orderAsc || 'Ascending');
        $btn.find('.material-icons-outlined').text('arrow_upward');
        if ($label.length) {
          $label.text(i18n.orderAsc || 'Ascending');
        }
      }
      currentPage = 1;
      loadClients();
    });

    // Per page.
    $(document).on('change', '#pn-cm-clients-per-page', function () {
      currentPerPage = parseInt($(this).val(), 10) || 20;
      currentPage = 1;
      loadClients();
    });

    // Pagination buttons.
    $(document).on('click', '.pn-cm-clients-page-btn', function () {
      var page = $(this).data('page');
      if (page && page !== currentPage) {
        currentPage = page;
        loadClients();
      }
    });

    // Stage pipeline dot click.
    $(document).on('click', '.pn-cm-clients-stage-dot', function (e) {
      e.stopPropagation();
      var $dot = $(this);
      var $pipeline = $dot.closest('.pn-cm-clients-stage-pipeline');
      var newStage = $dot.data('stage');
      var clientType = $pipeline.data('client-type');
      var clientId = $pipeline.data('client-id');

      var formData = new FormData();
      formData.append('action', 'pn_customers_manager_ajax');
      formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_update_stage');
      formData.append('client_type', clientType);
      formData.append('client_id', clientId);
      formData.append('new_stage', newStage);

      // Optimistically update pipeline visuals.
      updatePipelineVisuals($pipeline, newStage);

      fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.error_key) {
            alert((i18n.error || 'Error') + ': ' + res.error_content);
          }
        });
    });

    // Add client button - Open popup.
    $(document).on('click', '#pn-cm-clients-add', function () {
      openAddClientPopup();
    });

    // Save new client from popup.
    $(document).on('click', '#pn-cm-add-client-save', function () {
      var $btn = $(this);
      var firstName = $('#pn-cm-add-first-name').val().trim();
      var lastName = $('#pn-cm-add-last-name').val().trim();
      var email = $('#pn-cm-add-email').val().trim();
      var phone = $('#pn-cm-add-phone').val().trim();
      var company = $('#pn-cm-add-company').val().trim();
      var stage = $('#pn-cm-add-stage').val();
      var createWpUser = $('#pn-cm-add-wp-user').is(':checked');
      var username = $('#pn-cm-add-username').val().trim();
      var password = $('#pn-cm-add-password').val();

      // Validation.
      if (!firstName) {
        alert(i18n.errorFirstName || 'First name is required.');
        $('#pn-cm-add-first-name').focus();
        return;
      }
      if (!email) {
        alert(i18n.errorEmail || 'Email is required.');
        $('#pn-cm-add-email').focus();
        return;
      }
      if (createWpUser && !username) {
        alert(i18n.errorUsername || 'Username is required to create a WordPress user.');
        $('#pn-cm-add-username').focus();
        return;
      }
      if (createWpUser && !password) {
        alert(i18n.errorPassword || 'Password is required to create a WordPress user.');
        $('#pn-cm-add-password').focus();
        return;
      }

      $btn.prop('disabled', true).text(i18n.saving || 'Saving...');

      var formData = new FormData();
      formData.append('action', 'pn_customers_manager_ajax');
      formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_add');
      formData.append('first_name', firstName);
      formData.append('last_name', lastName);
      formData.append('email', email);
      formData.append('phone', phone);
      formData.append('company', company);
      formData.append('stage', stage);
      formData.append('create_wp_user', createWpUser ? '1' : '0');
      formData.append('username', username);
      formData.append('password', password);

      fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          $btn.prop('disabled', false).text(i18n.save || 'Save');
          if (res.error_key) {
            alert((i18n.error || 'Error') + ': ' + res.error_content);
          } else {
            closeAddClientPopup();
            loadClients();
          }
        })
        .catch(function () {
          $btn.prop('disabled', false).text(i18n.save || 'Save');
          alert(i18n.errorGeneric || 'An error occurred. Please try again.');
        });
    });

    // Cancel add client popup.
    $(document).on('click', '#pn-cm-add-client-cancel', function () {
      closeAddClientPopup();
    });

    // Toggle WP user fields.
    $(document).on('change', '#pn-cm-add-wp-user', function () {
      var $wpFields = $('#pn-cm-add-wp-fields');
      if ($(this).is(':checked')) {
        $wpFields.show();
        // Auto-fill username from email if empty.
        var $username = $('#pn-cm-add-username');
        if (!$username.val()) {
          var email = $('#pn-cm-add-email').val();
          if (email) {
            $username.val(email.split('@')[0]);
          }
        }
      } else {
        $wpFields.hide();
      }
    });

    // ── Edit popup ───────────────────────────────────────────────────

    $(document).on('click', '.pn-cm-clients-edit-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var clientType = $(this).data('client-type');
      var clientId   = $(this).data('client-id');
      if (clientType && clientId) {
        openEditPopup(clientType, clientId);
      }
    });

    // Save client from edit popup.
    $(document).on('click', '.pn-cm-clients-edit-save', function () {
      var $wrap = $(this).closest('.pn-cm-clients-edit-form-wrap');
      var clientType = $wrap.data('client-type');
      var clientId   = $wrap.data('client-id');
      var $btn = $(this);
      $btn.prop('disabled', true).text(i18n.loading || 'Loading...');

      var formData = new FormData();
      formData.append('action', 'pn_customers_manager_ajax');
      formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_save');
      formData.append('client_type', clientType);
      formData.append('client_id', clientId);

      $wrap.find('input, select, textarea').each(function () {
        var name = $(this).attr('name');
        if (name) {
          formData.append(name, $(this).val());
        }
      });

      fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          $btn.prop('disabled', false).text(i18n.save || 'Save');
          if (res.error_key) {
            alert((i18n.error || 'Error') + ': ' + (res.error_content || ''));
          } else {
            pn_customers_manager_Popups.close('pn-cm-clients-edit-popup');
            loadClients();
          }
        })
        .catch(function () {
          $btn.prop('disabled', false).text(i18n.save || 'Save');
        });
    });

    // Cancel edit.
    $(document).on('click', '.pn-cm-clients-edit-cancel', function () {
      pn_customers_manager_Popups.close('pn-cm-clients-edit-popup');
    });

    // ── Client detail popup ──────────────────────────────────────────

    // Click on card or row to open detail popup.
    $(document).on('click', '.pn-cm-clients-card, .pn-cm-clients-table tbody tr', function (e) {
      if ($(e.target).closest('select, a, button, .pn-cm-clients-edit-btn, .pn-cm-clients-stage-dot, .pn-cm-clients-stage-pipeline').length) {
        return;
      }
      var clientType = $(this).data('client-type');
      var clientId   = $(this).data('client-id');
      if (clientType && clientId) {
        openDetailPopup(clientType, clientId);
      }
    });

    // Tab switching inside popup.
    $(document).on('click', '#pn-cm-clients-detail-popup .pn-customers-manager-tab-links', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $tab = $(this);
      var $wrapper = $tab.closest('.pn-customers-manager-tabs-wrapper');

      $wrapper.find('.pn-customers-manager-tab-links').removeClass('active');
      $wrapper.find('.pn-customers-manager-tab-content').addClass('pn-customers-manager-display-none');

      $tab.addClass('active');
      $wrapper.find('#' + $tab.attr('data-pn-customers-manager-id')).removeClass('pn-customers-manager-display-none');
    });

    // ── Conversation thread toggle ──────────────────────────────────

    $(document).on('click', '.pn-cm-clients-conv-header', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $item = $(this).closest('.pn-cm-clients-conv-item');
      var $thread = $item.find('.pn-cm-clients-conv-thread');
      var $toggle = $item.find('.pn-cm-clients-conv-toggle');

      if ($thread.is(':visible')) {
        $thread.slideUp(200);
        $toggle.text('expand_more');
        return;
      }

      if ($thread.data('loaded')) {
        $thread.slideDown(200);
        $toggle.text('expand_less');
        return;
      }

      // Load messages via AJAX (server returns rendered HTML).
      var convId = $item.data('conv-id');
      var platform = $item.data('conv-platform');

      $thread.html('<div class="pn-cm-csv-spinner"></div>');
      $thread.slideDown(200);
      $toggle.text('expand_less');

      var fd = new FormData();
      fd.append('action', 'pn_customers_manager_ajax');
      fd.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      fd.append('pn_customers_manager_ajax_type', 'pn_cm_client_get_messages');
      fd.append('conv_id', convId);
      fd.append('platform', platform);

      fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.error_key) {
            $thread.html('<div class="pn-cm-clients-conv-error">' + (res.error_content || 'Error') + '</div>');
            return;
          }
          $thread.html(res.html);
          $thread.data('loaded', true);
        });
    });

    // ── Complete task ────────────────────────────────────────────────

    $(document).on('click', '.pn-cm-clients-task-complete-btn', function () {
      var $btn = $(this);
      var taskId = $btn.data('task-id');
      var $item = $btn.closest('.pn-cm-clients-task-item');

      var formData = new FormData();
      formData.append('action', 'pn_customers_manager_ajax');
      formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_complete_task');
      formData.append('task_id', taskId);

      fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.error_key) {
            alert((i18n.error || 'Error') + ': ' + (res.error_content || ''));
          } else {
            var $badge = $item.find('.pn-cm-clients-task-status');
            if (res.status === 'completed') {
              $badge.text(i18n.completed || 'Completed').removeClass('status-active').addClass('status-completed');
              $btn.find('.material-icons-outlined').text('check_circle');
            } else {
              $badge.text(i18n.active || 'Active').removeClass('status-completed').addClass('status-active');
              $btn.find('.material-icons-outlined').text('radio_button_unchecked');
            }
          }
        });
    });

    // ── Install / Activate PN Tasks Manager ──────────────────────────

    $(document).on('click', '.pn-cm-clients-plugin-install', function () {
      var $btn = $(this);
      var slug = $btn.data('slug');
      $btn.prop('disabled', true).text(i18n.installing || 'Installing...');

      var fd = new FormData();
      fd.append('action', 'pn_customers_manager_ajax');
      fd.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      fd.append('pn_customers_manager_ajax_type', 'pn_cm_install_plugin');
      fd.append('slug', slug);

      fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.error_key) {
            $btn.prop('disabled', false).html('<span class="material-icons-outlined">download</span> ' + (i18n.install || 'Install'));
            alert((i18n.installError || 'Install failed') + ': ' + (res.error_content || ''));
            return;
          }
          $btn.replaceWith('<button type="button" class="pn-cm-clients-btn-primary pn-cm-clients-plugin-activate" data-slug="' + slug + '"><span class="material-icons-outlined">power_settings_new</span> ' + (i18n.activate || 'Activate') + '</button>');
        })
        .catch(function () {
          $btn.prop('disabled', false).html('<span class="material-icons-outlined">download</span> ' + (i18n.install || 'Install'));
        });
    });

    $(document).on('click', '.pn-cm-clients-plugin-activate', function () {
      var $btn = $(this);
      var slug = $btn.data('slug');
      $btn.prop('disabled', true).text(i18n.activating || 'Activating...');

      var fd = new FormData();
      fd.append('action', 'pn_customers_manager_ajax');
      fd.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      fd.append('pn_customers_manager_ajax_type', 'pn_cm_activate_plugin');
      fd.append('slug', slug);

      fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.error_key) {
            $btn.prop('disabled', false).html('<span class="material-icons-outlined">power_settings_new</span> ' + (i18n.activate || 'Activate'));
            alert((i18n.activateError || 'Activation failed') + ': ' + (res.error_content || ''));
            return;
          }
          var clientType = $('#pn-cm-clients-detail-popup').data('client-type');
          var clientId = $('#pn-cm-clients-detail-popup').data('client-id');
          if (clientType && clientId) {
            openDetailPopup(clientType, clientId);
          }
        })
        .catch(function () {
          $btn.prop('disabled', false).html('<span class="material-icons-outlined">power_settings_new</span> ' + (i18n.activate || 'Activate'));
        });
    });

    // ── Add task form ────────────────────────────────────────────────

    $(document).on('click', '.pn-cm-clients-add-task-btn', function () {
      var $form = $(this).closest('.pn-customers-manager-tab-content').find('.pn-cm-clients-add-task-form');
      $form.toggleClass('active');
    });

    $(document).on('click', '.pn-cm-clients-save-task', function () {
      var $popup = $('#pn-cm-clients-detail-popup');
      var $form = $popup.find('.pn-cm-clients-add-task-form');
      var clientType = $popup.data('client-type');
      var clientId = $popup.data('client-id');

      var title = $form.find('[name="task_title"]').val();
      if (!title) { $form.find('[name="task_title"]').focus(); return; }

      var formData = new FormData();
      formData.append('action', 'pn_customers_manager_ajax');
      formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_add_task');
      formData.append('client_type', clientType);
      formData.append('client_id', clientId);
      formData.append('task_title', title);
      formData.append('task_date', $form.find('[name="task_date"]').val());
      formData.append('task_time', $form.find('[name="task_time"]').val());
      formData.append('task_description', $form.find('[name="task_description"]').val());

      var $btn = $(this);
      $btn.prop('disabled', true);

      fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          $btn.prop('disabled', false);
          if (res.error_key) {
            alert((i18n.error || 'Error') + ': ' + (res.error_content || ''));
          } else {
            var $list = $popup.find('.pn-cm-clients-tasks-list');
            $list.find('.pn-cm-clients-tab-empty').remove();
            $list.prepend(res.task_html);
            var $count = $popup.find('.pn-customers-manager-tab-links[data-pn-customers-manager-id="pn-cm-client-tab-tasks"] .pn-cm-clients-popup-tab-count');
            if ($count.length) {
              $count.text(parseInt($count.text() || '0') + 1);
            } else {
              $popup.find('.pn-customers-manager-tab-links[data-pn-customers-manager-id="pn-cm-client-tab-tasks"]').append(' <span class="pn-cm-clients-popup-tab-count">1</span>');
            }
            $form.find('input, textarea').val('');
            $form.removeClass('active');
          }
        })
        .catch(function () {
          $btn.prop('disabled', false);
        });
    });

    // ── Add appointment form ─────────────────────────────────────────

    $(document).on('click', '.pn-cm-clients-add-appt-btn', function () {
      var $form = $(this).closest('.pn-customers-manager-tab-content').find('.pn-cm-clients-add-appt-form');
      $form.toggleClass('active');
    });

    $(document).on('click', '.pn-cm-clients-save-appt', function () {
      var $popup = $('#pn-cm-clients-detail-popup');
      var $form = $popup.find('.pn-cm-clients-add-appt-form');
      var clientType = $popup.data('client-type');
      var clientId = $popup.data('client-id');

      var date = $form.find('[name="appointment_date"]').val();
      var startTime = $form.find('[name="appointment_start_time"]').val();
      var endTime = $form.find('[name="appointment_end_time"]').val();

      if (!date || !startTime || !endTime) { return; }

      var formData = new FormData();
      formData.append('action', 'pn_customers_manager_ajax');
      formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_add_appointment');
      formData.append('client_type', clientType);
      formData.append('client_id', clientId);
      formData.append('appointment_date', date);
      formData.append('appointment_start_time', startTime);
      formData.append('appointment_end_time', endTime);

      var $btn = $(this);
      $btn.prop('disabled', true);

      fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          $btn.prop('disabled', false);
          if (res.error_key) {
            alert((i18n.error || 'Error') + ': ' + (res.error_content || ''));
          } else {
            var $list = $popup.find('.pn-cm-clients-appts-list');
            $list.find('.pn-cm-clients-tab-empty').remove();
            $list.prepend(res.appointment_html);
            var $count = $popup.find('.pn-customers-manager-tab-links[data-pn-customers-manager-id="pn-cm-client-tab-appointments"] .pn-cm-clients-popup-tab-count');
            if ($count.length) {
              $count.text(parseInt($count.text() || '0') + 1);
            } else {
              $popup.find('.pn-customers-manager-tab-links[data-pn-customers-manager-id="pn-cm-client-tab-appointments"]').append(' <span class="pn-cm-clients-popup-tab-count">1</span>');
            }
            $form.find('input').val('');
            $form.removeClass('active');
          }
        })
        .catch(function () {
          $btn.prop('disabled', false);
        });
    });
  }

  // ── Detail popup ─────────────────────────────────────────────────

  function openDetailPopup(clientType, clientId) {
    var $body = $('#pn-cm-clients-detail-body');
    $body.html('<div class="pn-cm-clients-popup-loading"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div>');

    pn_customers_manager_Popups.open('pn-cm-clients-detail-popup');

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_get_detail');
    formData.append('client_type', clientType);
    formData.append('client_id', clientId);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          $body.html('<div class="pn-cm-clients-popup-loading"><p>' + (i18n.error || 'Error') + ': ' + res.error_content + '</p></div>');
        } else {
          $body.html(res.html);
          var $popup = $('#pn-cm-clients-detail-popup');
          $popup.attr('data-client-type', res.client_type);
          $popup.attr('data-client-id', res.client_id);

          // Reinitialize tooltips for dynamically added content.
          if (window.PN_CUSTOMERS_MANAGER_Tooltips) {
            PN_CUSTOMERS_MANAGER_Tooltips.init('#pn-cm-clients-detail-popup .pn-customers-manager-tooltip');
          }
        }
      })
      .catch(function () {
        $body.html('<div class="pn-cm-clients-popup-loading"><p>' + (i18n.error || 'Error') + '</p></div>');
      });
  }

  // ── Edit popup ─────────────────────────────────────────────────

  function openEditPopup(clientType, clientId) {
    var $body = $('#pn-cm-clients-edit-body');
    $body.html('<div class="pn-cm-clients-popup-loading"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div>');

    pn_customers_manager_Popups.open('pn-cm-clients-edit-popup');

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_get_edit_form');
    formData.append('client_type', clientType);
    formData.append('client_id', clientId);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          $body.html('<div class="pn-cm-clients-popup-loading"><p>' + (i18n.error || 'Error') + ': ' + res.error_content + '</p></div>');
        } else {
          $body.html(res.html);
        }
      })
      .catch(function () {
        $body.html('<div class="pn-cm-clients-popup-loading"><p>' + (i18n.error || 'Error') + '</p></div>');
      });
  }

  /**
   * Update pipeline dot/connector active states to reflect a new stage.
   */
  function updatePipelineVisuals($pipeline, newStage) {
    var isLarge = $pipeline.hasClass('pn-cm-clients-stage-pipeline-large');

    // Remove previous current markers.
    $pipeline.find('.pn-cm-clients-stage-dot').removeClass('current');
    $pipeline.find('.pn-cm-clients-stage-current-ring').remove();
    $pipeline.find('.pn-cm-clients-stage-current-label').remove();

    var found = false;
    $pipeline.find('.pn-cm-clients-stage-dot').each(function () {
      var $d = $(this);
      var isCurrent = ($d.data('stage') === newStage);
      if (!found) {
        $d.addClass('active');
      } else {
        $d.removeClass('active');
      }
      if (isCurrent) {
        found = true;
        $d.addClass('current');
        if (!isLarge) {
          $d.append('<span class="pn-cm-clients-stage-current-ring"></span>');
        } else {
          var color = $d[0].style.getPropertyValue('--stage-color') || '#555';
          $d.after('<span class="pn-cm-clients-stage-current-label" style="--stage-color:' + color + ';">' + $('<span>').text(newStage).html() + '</span>');
        }
      }
    });
    found = false;
    $pipeline.find('.pn-cm-clients-stage-connector').each(function (idx) {
      var $c = $(this);
      var $nextDot = $pipeline.find('.pn-cm-clients-stage-dot').eq(idx + 1);
      if ($nextDot.hasClass('active')) {
        $c.addClass('active');
      } else {
        $c.removeClass('active');
      }
    });
  }

  // ── Add client popup ──────────────────────────────────────────────

  function openAddClientPopup() {
    var stages = cfg.stages || [];
    var stageOptions = '';
    for (var i = 0; i < stages.length; i++) {
      stageOptions += '<option value="' + stages[i] + '">' + stages[i] + '</option>';
    }

    var html = '<div class="pn-cm-clients-edit-form-wrap">'
      + '<div class="pn-cm-clients-edit-title">'
      + '<span class="material-icons-outlined">person_add</span>'
      + (i18n.addClient || 'Add Client')
      + '</div>'

      + '<div class="pn-cm-clients-edit-section-title">'
      + '<span class="material-icons-outlined">person</span>'
      + (i18n.personalInfo || 'Personal Information')
      + '</div>'
      + '<div class="pn-cm-clients-edit-row">'
      + '<div class="pn-cm-clients-edit-field">'
      + '<label>' + (i18n.firstName || 'First Name') + ' *</label>'
      + '<input type="text" id="pn-cm-add-first-name" />'
      + '</div>'
      + '<div class="pn-cm-clients-edit-field">'
      + '<label>' + (i18n.lastName || 'Last Name') + '</label>'
      + '<input type="text" id="pn-cm-add-last-name" />'
      + '</div>'
      + '</div>'
      + '<div class="pn-cm-clients-edit-row">'
      + '<div class="pn-cm-clients-edit-field">'
      + '<label>' + (i18n.email || 'Email') + ' *</label>'
      + '<input type="email" id="pn-cm-add-email" />'
      + '</div>'
      + '<div class="pn-cm-clients-edit-field">'
      + '<label>' + (i18n.phone || 'Phone') + '</label>'
      + '<input type="text" id="pn-cm-add-phone" />'
      + '</div>'
      + '</div>'

      + '<div class="pn-cm-clients-edit-section-title">'
      + '<span class="material-icons-outlined">business</span>'
      + (i18n.businessInfo || 'Business Information')
      + '</div>'
      + '<div class="pn-cm-clients-edit-row">'
      + '<div class="pn-cm-clients-edit-field">'
      + '<label>' + (i18n.company || 'Company') + '</label>'
      + '<input type="text" id="pn-cm-add-company" />'
      + '</div>'
      + '<div class="pn-cm-clients-edit-field">'
      + '<label>' + (i18n.stage || 'Stage') + '</label>'
      + '<select id="pn-cm-add-stage">' + stageOptions + '</select>'
      + '</div>'
      + '</div>'

      + '<div class="pn-cm-clients-edit-section-title">'
      + '<span class="material-icons-outlined">account_circle</span>'
      + (i18n.wpUserAccount || 'WordPress User Account')
      + '</div>'
      + '<div class="pn-cm-clients-edit-row">'
      + '<div class="pn-cm-clients-edit-field pn-cm-clients-edit-field-full">'
      + '<label>'
      + '<input type="checkbox" id="pn-cm-add-wp-user" style="width:auto;margin-right:6px;" />'
      + (i18n.createWpUser || 'Create WordPress user account')
      + '</label>'
      + '</div>'
      + '</div>'
      + '<div id="pn-cm-add-wp-fields" style="display:none;">'
      + '<div class="pn-cm-clients-edit-row">'
      + '<div class="pn-cm-clients-edit-field">'
      + '<label>' + (i18n.username || 'Username') + ' *</label>'
      + '<input type="text" id="pn-cm-add-username" />'
      + '</div>'
      + '<div class="pn-cm-clients-edit-field">'
      + '<label>' + (i18n.password || 'Password') + ' *</label>'
      + '<input type="password" id="pn-cm-add-password" />'
      + '</div>'
      + '</div>'
      + '</div>'

      + '<div class="pn-cm-clients-edit-actions">'
      + '<button type="button" class="pn-cm-clients-btn-primary" id="pn-cm-add-client-save">' + (i18n.save || 'Save') + '</button>'
      + '<button type="button" class="pn-cm-clients-edit-cancel" id="pn-cm-add-client-cancel">' + (i18n.cancel || 'Cancel') + '</button>'
      + '</div>'
      + '</div>';

    $('#pn-cm-clients-edit-body').html(html);
    $('.pn-customers-manager-popup-overlay').fadeIn(200);
    $('#pn-cm-clients-edit-popup').fadeIn(200);
  }

  function closeAddClientPopup() {
    $('.pn-customers-manager-popup-overlay').fadeOut(200);
    $('#pn-cm-clients-edit-popup').fadeOut(200);
    setTimeout(function () {
      $('#pn-cm-clients-edit-body').html('');
    }, 200);
  }

  // ── Load clients via AJAX ────────────────────────────────────────

  function loadClients() {
    var $content = $('#pn-cm-clients-content');
    var search = $('#pn-cm-clients-search, .pn-cm-clients-search').val() || '';

    $content.html('<div class="pn-cm-clients-empty"><p>' + (i18n.loading || 'Loading...') + '</p></div>');

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_client_get_list');
    formData.append('stage', currentStage);
    formData.append('source', currentSource);
    formData.append('search', search);
    formData.append('view', currentView);
    formData.append('page', currentPage);
    formData.append('per_page', currentPerPage);
    formData.append('orderby', currentOrderby);
    formData.append('order', currentOrder);
    formData.append('show_flags', cfg.showFlags ? '1' : '0');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          $content.html('<div class="pn-cm-clients-empty"><p>' + (i18n.error || 'Error') + '</p></div>');
          $('#pn-cm-clients-pagination').html('');
        } else {
          $content.html(res.html);
          $('#pn-cm-clients-pagination').html(res.pagination_html || '');
          // Reinitialize tooltips for dynamically loaded cards.
          if (window.PN_CUSTOMERS_MANAGER_Tooltips) {
            PN_CUSTOMERS_MANAGER_Tooltips.init('.pn-cm-clients-wrap .pn-customers-manager-tooltip');
          }
          // Flag debug info (only present when WP_DEBUG is on).
          if (res.flag_debug) {
            console.group('[PN CM Clients] Flag debug');
            console.table(res.flag_debug);
            console.groupEnd();
          }
        }
      })
      .catch(function () {
        $content.html('<div class="pn-cm-clients-empty"><p>' + (i18n.error || 'Error') + '</p></div>');
        $('#pn-cm-clients-pagination').html('');
      });
  }

  // ── Duplicates ────────────────────────────────────────────────────

  function checkDuplicates() {
    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_client_get_duplicates');

    fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) return;
        var count = res.count || 0;
        var $toggle = $('#pn-cm-clients-dup-toggle');
        var $badge  = $('#pn-cm-dup-badge');
        if (count > 0) {
          $badge.text(count);
          $toggle.show();
        } else {
          $toggle.hide();
        }
      });
  }

  function openDuplicatesPopup() {
    var $body = $('#pn-cm-clients-dup-body');
    $body.html('<div class="pn-cm-clients-popup-loading"><div class="pn-customers-manager-loader-circle"><div></div></div></div>');
    pn_customers_manager_Popups.open('pn-cm-clients-dup-popup');

    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_client_get_duplicates');

    fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          $body.html('<div class="pn-cm-clients-empty"><p>' + (i18n.error || 'Error') + '</p></div>');
          return;
        }
        $body.html(res.html);
      });
  }

  function mergeDuplicates(groupIndex, $btn) {
    var confirmMsg = groupIndex === 'all' ? (i18n.confirmMergeAll || 'Merge ALL duplicate groups?') : (i18n.confirmMerge || 'Merge this group?');
    if (!confirm(confirmMsg)) return;

    var origText = $btn.html();
    $btn.prop('disabled', true).text(i18n.merging || 'Merging...');

    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_client_merge_duplicates');
    fd.append('group_index', groupIndex);

    fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        $btn.prop('disabled', false).html(origText);
        if (res.error_key) {
          alert((i18n.error || 'Error') + ': ' + (res.error_content || ''));
          return;
        }
        pn_customers_manager_Popups.close('pn-cm-clients-dup-popup');
        loadClients();
        checkDuplicates();
      })
      .catch(function () {
        $btn.prop('disabled', false).html(origText);
      });
  }

  $(document).on('click', '#pn-cm-clients-dup-toggle', function () {
    openDuplicatesPopup();
  });

  $(document).on('click', '.pn-cm-dup-merge-btn', function () {
    var idx = $(this).data('group-index');
    mergeDuplicates(String(idx), $(this));
  });

  $(document).on('click', '#pn-cm-dup-merge-all', function () {
    mergeDuplicates('all', $(this));
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(jQuery);
