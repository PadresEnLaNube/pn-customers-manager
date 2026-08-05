(function() {
  'use strict';

  var config  = window.pnCmContactMessages || {};
  var ajaxUrl = config.ajaxUrl || '';
  var nonce   = config.nonce || '';
  var i18n    = config.i18n || {};

  // Persistent wrappers — the inner HTML of these gets swapped when the user
  // switches between the inbox and spam tabs, so we rely on event delegation
  // from these parents rather than binding on individual elements.
  var listWrap = document.getElementById('pn-cm-contact-messages-list');
  var tabsWrap = document.getElementById('pn-cm-contact-messages-tabs');

  // Current view + page. Kept in sync with the server response so the list
  // can be refetched on popstate without re-parsing the URL.
  var currentView  = (listWrap && listWrap.getAttribute('data-view')) || 'inbox';
  var currentPaged = 1;

  /**
   * Toggle the expand / collapse icon when opening a message detail row.
   */
  function setToggleIcon(toggleEl, expanded) {
    if (!toggleEl) return;
    var iconSpan = toggleEl.querySelector('.material-icons-outlined');
    if (iconSpan) {
      iconSpan.textContent = expanded ? 'expand_less' : 'expand_more';
    }
  }

  /**
   * Refresh the header badge after an action returns updated counts.
   * The tabs themselves are re-rendered server-side via tabs_html, so we
   * only need to touch the header badge here.
   */
  function updateHeaderBadge(data) {
    if (!data || data.unread_count === undefined) return;
    var headerBadge = document.querySelector('.pn-customers-manager-contact-messages-wrap > h1 .pn-customers-manager-badge');
    if (headerBadge) {
      if (data.unread_count > 0) {
        headerBadge.textContent = data.unread_count;
      } else {
        headerBadge.remove();
      }
    } else if (data.unread_count > 0) {
      var h1 = document.querySelector('.pn-customers-manager-contact-messages-wrap > h1');
      if (h1) {
        var badge = document.createElement('span');
        badge.className = 'pn-customers-manager-badge';
        badge.textContent = data.unread_count;
        h1.appendChild(document.createTextNode(' '));
        h1.appendChild(badge);
      }
    }
  }

  /**
   * Update in-tab badges using counts returned by an action endpoint.
   * Used by action handlers (mark-read, mark-spam, delete) that don't
   * re-render the tabs HTML themselves.
   */
  function updateTabBadges(data) {
    if (!data) return;

    var inboxTab = document.querySelector('.pn-customers-manager-messages-tab[data-view="inbox"]');
    if (inboxTab && data.unread_count !== undefined) {
      var inboxBadge = inboxTab.querySelector('.pn-customers-manager-badge');
      if (data.unread_count > 0) {
        if (inboxBadge) {
          inboxBadge.textContent = data.unread_count;
        } else {
          inboxBadge = document.createElement('span');
          inboxBadge.className = 'pn-customers-manager-badge';
          inboxBadge.textContent = data.unread_count;
          inboxTab.appendChild(inboxBadge);
        }
      } else if (inboxBadge) {
        inboxBadge.remove();
      }
    }

    var spamTab = document.querySelector('.pn-customers-manager-messages-tab[data-view="spam"]');
    if (spamTab && data.spam_count !== undefined) {
      var spamBadge = spamTab.querySelector('.pn-customers-manager-badge');
      if (data.spam_count > 0) {
        if (spamBadge) {
          spamBadge.textContent = data.spam_count;
        } else {
          spamBadge = document.createElement('span');
          spamBadge.className = 'pn-customers-manager-badge';
          spamBadge.textContent = data.spam_count;
          spamTab.appendChild(spamBadge);
        }
      } else if (spamBadge) {
        spamBadge.remove();
      }
    }

    var formsTab = document.querySelector('.pn-customers-manager-messages-tab[data-view="forms"]');
    if (formsTab && data.forms_unread_count !== undefined) {
      var formsBadge = formsTab.querySelector('.pn-customers-manager-badge');
      if (data.forms_unread_count > 0) {
        if (formsBadge) {
          formsBadge.textContent = data.forms_unread_count;
        } else {
          formsBadge = document.createElement('span');
          formsBadge.className = 'pn-customers-manager-badge';
          formsBadge.textContent = data.forms_unread_count;
          formsTab.appendChild(formsBadge);
        }
      } else if (formsBadge) {
        formsBadge.remove();
      }
    }
  }

  /**
   * Build the page URL for a given view/page so we can keep the browser
   * URL in sync with the state shown on screen.
   */
  function buildUrl(view, paged) {
    var base = window.location.pathname + '?page=pn_customers_manager_contact_messages&view=' + encodeURIComponent(view);
    if (paged && paged > 1) base += '&paged=' + paged;
    return base;
  }

  /**
   * Fetch a rendered list (table + pagination) and optionally updated tabs
   * for the given view/page and swap it into the DOM without a full reload.
   */
  function loadList(view, paged, pushState) {
    if (!listWrap) return;

    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_contact_list');
    fd.append('pn_customers_manager_ajax_nonce', nonce);
    fd.append('view', view);
    fd.append('paged', paged || 1);

    listWrap.classList.add('pn-customers-manager-loading');

    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        listWrap.classList.remove('pn-customers-manager-loading');
        if (!data || data.error_key) return;

        currentView  = data.view || view;
        currentPaged = data.paged || paged || 1;

        listWrap.innerHTML = data.html || '';
        listWrap.setAttribute('data-view', currentView);

        if (tabsWrap && data.tabs_html) {
          tabsWrap.outerHTML = data.tabs_html;
          tabsWrap = document.getElementById('pn-cm-contact-messages-tabs');
        }

        updateHeaderBadge(data);

        if (pushState && window.history && window.history.pushState) {
          window.history.pushState(
            { pnCmView: currentView, pnCmPaged: currentPaged },
            '',
            buildUrl(currentView, currentPaged)
          );
        }
      })
      .catch(function() {
        listWrap.classList.remove('pn-customers-manager-loading');
      });
  }

  /**
   * Mark a message as read.
   */
  function markRead(id, row) {
    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_contact_mark_read');
    fd.append('pn_customers_manager_ajax_nonce', nonce);
    fd.append('message_id', id);

    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.error_key || data.error_key === '') {
          if (row) {
            row.classList.remove('pn-customers-manager-message-unread');
            row.classList.add('pn-customers-manager-message-read');
            var dot = row.querySelector('.pn-customers-manager-status-dot');
            if (dot) {
              dot.classList.remove('pn-customers-manager-status-unread');
              dot.classList.add('pn-customers-manager-status-read');
            }
            var markBtn = row.querySelector('.pn-customers-manager-msg-mark-read');
            if (markBtn) markBtn.remove();
          }
          updateHeaderBadge(data);
          updateTabBadges(data);
        }
      });
  }

  /**
   * Mark a form submission as read.
   */
  function markFormRead(id, row) {
    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_form_mark_read');
    fd.append('pn_customers_manager_ajax_nonce', nonce);
    fd.append('message_id', id);

    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.error_key || data.error_key === '') {
          if (row) {
            row.classList.remove('pn-customers-manager-message-unread');
            row.classList.add('pn-customers-manager-message-read');
            var dot = row.querySelector('.pn-customers-manager-status-dot');
            if (dot) {
              dot.classList.remove('pn-customers-manager-status-unread');
              dot.classList.add('pn-customers-manager-status-read');
            }
            var markBtn = row.querySelector('.pn-customers-manager-form-mark-read');
            if (markBtn) markBtn.remove();
          }
          updateHeaderBadge(data);
          updateTabBadges(data);
        }
      });
  }

  // ---------------------------------------------------------------------
  // Event delegation
  // ---------------------------------------------------------------------

  // Tab switching (delegated on the tabs wrapper because it gets swapped).
  document.addEventListener('click', function(e) {
    var tab = e.target.closest('.pn-customers-manager-messages-tab');
    if (!tab) return;
    if (!tabsWrap || !tabsWrap.contains(tab)) return;

    e.preventDefault();
    var view = tab.getAttribute('data-view') || 'inbox';
    if (view === currentView) return;
    loadList(view, 1, true);
  });

  // Actions inside the list (delegated on the list wrapper).
  document.addEventListener('click', function(e) {
    if (!listWrap || !listWrap.contains(e.target)) return;

    var toggleBtn = e.target.closest('.pn-customers-manager-msg-toggle');
    if (toggleBtn) {
      e.preventDefault();
      var trow = toggleBtn.closest('tr');
      var tid  = trow.getAttribute('data-id');
      var detail = listWrap.querySelector('tr.pn-customers-manager-message-detail[data-id="' + tid + '"]');
      if (detail) {
        var expanded = detail.style.display === 'none';
        detail.style.display = expanded ? '' : 'none';
        setToggleIcon(toggleBtn, expanded);
      }
      if (trow.classList.contains('pn-customers-manager-message-unread') && !trow.classList.contains('pn-customers-manager-message-spam')) {
        markRead(tid, trow);
      }
      return;
    }

    var readBtn = e.target.closest('.pn-customers-manager-msg-mark-read');
    if (readBtn) {
      e.preventDefault();
      var rid = readBtn.getAttribute('data-id');
      var rrow = listWrap.querySelector('tr.pn-customers-manager-message-row[data-id="' + rid + '"]');
      markRead(rid, rrow);
      return;
    }

    var spamBtn = e.target.closest('.pn-customers-manager-msg-mark-spam');
    if (spamBtn) {
      e.preventDefault();
      var unmark = spamBtn.getAttribute('data-unmark') === '1';
      if (!unmark && !confirm(i18n.confirmMarkSpam || '\u00bfMarcar este mensaje como spam?')) return;
      var sid = spamBtn.getAttribute('data-id');

      var sfd = new FormData();
      sfd.append('action', 'pn_customers_manager_ajax');
      sfd.append('pn_customers_manager_ajax_type', 'pn_cm_contact_mark_spam');
      sfd.append('pn_customers_manager_ajax_nonce', nonce);
      sfd.append('message_id', sid);
      sfd.append('unmark', unmark ? '1' : '0');

      fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: sfd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.error_key || data.error_key === '') {
            var srow = listWrap.querySelector('tr.pn-customers-manager-message-row[data-id="' + sid + '"]');
            var sdetail = listWrap.querySelector('tr.pn-customers-manager-message-detail[data-id="' + sid + '"]');
            if (srow) srow.remove();
            if (sdetail) sdetail.remove();
            updateHeaderBadge(data);
            updateTabBadges(data);
          }
        });
      return;
    }

    var delBtn = e.target.closest('.pn-customers-manager-msg-delete');
    if (delBtn) {
      e.preventDefault();
      if (!confirm(i18n.confirmDelete || '\u00bfEst\u00e1s seguro de que quieres eliminar este mensaje?')) return;
      var did = delBtn.getAttribute('data-id');

      var dfd = new FormData();
      dfd.append('action', 'pn_customers_manager_ajax');
      dfd.append('pn_customers_manager_ajax_type', 'pn_cm_contact_delete');
      dfd.append('pn_customers_manager_ajax_nonce', nonce);
      dfd.append('message_id', did);

      fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: dfd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.error_key || data.error_key === '') {
            var drow = listWrap.querySelector('tr.pn-customers-manager-message-row[data-id="' + did + '"]');
            var ddetail = listWrap.querySelector('tr.pn-customers-manager-message-detail[data-id="' + did + '"]');
            if (drow) drow.remove();
            if (ddetail) ddetail.remove();
            updateHeaderBadge(data);
            updateTabBadges(data);
          }
        });
      return;
    }

    // Form submission toggle
    var formToggleBtn = e.target.closest('.pn-customers-manager-form-toggle');
    if (formToggleBtn) {
      e.preventDefault();
      var ftrow = formToggleBtn.closest('tr');
      var ftid  = ftrow.getAttribute('data-id');
      var ftdetail = listWrap.querySelector('tr.pn-customers-manager-message-detail[data-id="' + ftid + '"]');
      if (ftdetail) {
        var ftexpanded = ftdetail.style.display === 'none';
        ftdetail.style.display = ftexpanded ? '' : 'none';
        setToggleIcon(formToggleBtn, ftexpanded);
      }
      if (ftrow.classList.contains('pn-customers-manager-message-unread')) {
        markFormRead(ftid, ftrow);
      }
      return;
    }

    // Form submission mark as read
    var formReadBtn = e.target.closest('.pn-customers-manager-form-mark-read');
    if (formReadBtn) {
      e.preventDefault();
      var frid = formReadBtn.getAttribute('data-id');
      var frrow = listWrap.querySelector('tr.pn-customers-manager-message-row[data-id="' + frid + '"]');
      markFormRead(frid, frrow);
      return;
    }

    // Form submission delete
    var formDelBtn = e.target.closest('.pn-customers-manager-form-delete');
    if (formDelBtn) {
      e.preventDefault();
      if (!confirm(i18n.confirmDeleteForm || '\u00bfEst\u00e1s seguro de que quieres eliminar este formulario?')) return;
      var fdid = formDelBtn.getAttribute('data-id');

      var fffd = new FormData();
      fffd.append('action', 'pn_customers_manager_ajax');
      fffd.append('pn_customers_manager_ajax_type', 'pn_cm_form_delete');
      fffd.append('pn_customers_manager_ajax_nonce', nonce);
      fffd.append('message_id', fdid);

      fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fffd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.error_key || data.error_key === '') {
            var fdrow = listWrap.querySelector('tr.pn-customers-manager-message-row[data-id="' + fdid + '"]');
            var fddetail = listWrap.querySelector('tr.pn-customers-manager-message-detail[data-id="' + fdid + '"]');
            if (fdrow) fdrow.remove();
            if (fddetail) fddetail.remove();
            updateHeaderBadge(data);
            updateTabBadges(data);
          }
        });
      return;
    }

    // Pagination links — reuse the AJAX loader instead of a full reload.
    var pageLink = e.target.closest('.pn-customers-manager-pagination-link');
    if (pageLink) {
      e.preventDefault();
      var href = pageLink.getAttribute('href') || '';
      var m = href.match(/[?&]paged=(\d+)/);
      var p = m ? parseInt(m[1], 10) : 1;
      loadList(currentView, p, true);
      return;
    }
  });

  // Browser back / forward — restore the previous view/page.
  window.addEventListener('popstate', function(e) {
    var state = e.state || {};
    var view = state.pnCmView;
    var paged = state.pnCmPaged;

    if (!view) {
      var url = new URL(window.location.href);
      view  = url.searchParams.get('view') || 'inbox';
      paged = parseInt(url.searchParams.get('paged') || '1', 10);
    }

    if (view !== currentView || paged !== currentPaged) {
      loadList(view, paged || 1, false);
    }
  });

  // Seed the initial history entry so the first popstate has context to
  // restore when the user navigates back to the original server-rendered
  // state after performing AJAX tab switches.
  if (window.history && window.history.replaceState) {
    var initialUrl = new URL(window.location.href);
    currentPaged = parseInt(initialUrl.searchParams.get('paged') || '1', 10);
    window.history.replaceState(
      { pnCmView: currentView, pnCmPaged: currentPaged },
      '',
      window.location.href
    );
  }
})();
