(function($) {
  'use strict';

  var cfg = window.pnCmActivityLog || {};
  var state = {
    page: 1,
    search: '',
    actionType: '',
    objectType: '',
    order: 'DESC',
    loading: false
  };
  var searchTimer = null;

  function init() {
    loadLogs(false);
    bindEvents();
  }

  function bindEvents() {
    // Search with 400ms debounce
    $(document).on('input', '#pn-cm-al-search', function() {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function() {
        state.search = $('#pn-cm-al-search').val();
        state.page = 1;
        loadLogs(false);
      }, 400);
    });

    // Filter dropdowns
    $(document).on('change', '#pn-cm-al-action-type', function() {
      state.actionType = this.value;
      state.page = 1;
      loadLogs(false);
    });
    $(document).on('change', '#pn-cm-al-object-type', function() {
      state.objectType = this.value;
      state.page = 1;
      loadLogs(false);
    });

    // Sort toggle
    $(document).on('click', '#pn-cm-al-sort-toggle', function() {
      state.order = state.order === 'DESC' ? 'ASC' : 'DESC';
      state.page = 1;
      loadLogs(false);
    });

    // Load more
    $(document).on('click', '#pn-cm-al-load-more', function() {
      state.page++;
      loadLogs(true);
    });
  }

  function loadLogs(append) {
    if (state.loading) return;
    state.loading = true;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_activity_log_list');
    formData.append('search', state.search);
    formData.append('action_type', state.actionType);
    formData.append('object_type', state.objectType);
    formData.append('order', state.order);
    formData.append('page', state.page);

    fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        var $entries = $('#pn-cm-al-entries');
        if (append) {
          $entries.append(res.html);
        } else {
          $entries.html(res.html);
        }

        if (res.has_more) {
          $('#pn-cm-al-load-more').show();
        } else {
          $('#pn-cm-al-load-more').hide();
        }

        $('#pn-cm-al-total').text(res.total ? '(' + res.total + ')' : '');
      })
      .finally(function() {
        state.loading = false;
      });
  }

  $(document).ready(init);
})(jQuery);
