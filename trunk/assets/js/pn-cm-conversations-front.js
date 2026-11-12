/**
 * PN Customers Manager – Front-end Conversations popup.
 * Handles WhatsApp AI & Instagram AI shortcode interactions.
 * Uses the plugin popup manager (pn_customers_manager_Popups).
 */
(function ($) {
  'use strict';

  $(document).ready(function () {
    $('.pn-cm-conv-front').each(function () {
      initConversationPanel($(this));
    });
  });

  function initConversationPanel($wrap) {
    var platform   = $wrap.attr('data-platform') || 'whatsapp';
    var ajaxUrl    = $wrap.attr('data-ajax-url') || '';
    var nonce      = $wrap.attr('data-nonce') || '';
    var popupId    = 'pn-cm-conv-popup-' + platform;
    var $popup     = $('#' + popupId);
    var $popupBody = $popup.find('.pn-cm-conv-popup-body');
    var detailType = platform === 'instagram' ? 'pn_cm_ig_conversation_detail_html' : 'pn_cm_wa_conversation_detail_html';
    var actionType = platform === 'instagram' ? 'pn_cm_ig_conversation_action' : 'pn_cm_wa_conversation_action';

    /* --- Filters --- */
    $wrap.on('click', '.pn-cm-conv-filter-btn', function (e) {
      e.preventDefault();
      var status = $(this).attr('data-status') || '';
      $wrap.find('.pn-cm-conv-filter-btn').removeClass('active');
      $(this).addClass('active');
      filterCards(status);
    });

    /* --- Card click → open popup --- */
    $wrap.on('click', '.pn-cm-conv-card-view', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var convId = $(this).attr('data-conv-id');
      if (convId) openPopup(convId);
    });

    /* Close action on card list */
    $wrap.on('click', '.pn-cm-conv-close-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var cid = $(this).attr('data-conv-id');
      if (cid) doCardAction('close', cid);
    });

    /* Delete action on card list */
    $wrap.on('click', '.pn-cm-conv-delete-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var did = $(this).attr('data-conv-id');
      if (did && confirm(pnCmConvFrontI18n.confirmDelete || 'Delete this conversation?')) {
        doCardAction('delete', did);
      }
    });

    /* Reset action on card list */
    $wrap.on('click', '.pn-cm-conv-reset-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var rid = $(this).attr('data-conv-id');
      if (rid && confirm(pnCmConvFrontI18n.confirmReset || 'Reset this conversation?')) {
        doCardAction('reset', rid);
      }
    });

    /* --- Popup close button inside AJAX content --- */
    $popup.on('click', '.pn-cm-wa-popup-close', function (e) {
      e.preventDefault();
      pn_customers_manager_Popups.close();
    });

    /* --- Popup action buttons (delegated) --- */
    $popup.on('click', '.pn-cm-wa-action-btn', function () {
      var action = $(this).attr('data-action');
      var convId = $(this).attr('data-conv-id');
      if (action === 'delete' && !confirm(pnCmConvFrontI18n.confirmDelete || 'Delete this conversation?')) {
        return;
      }
      if (action && convId) {
        doPopupAction(action, convId);
      }
    });

    /* ---- helpers ---- */

    function filterCards(status) {
      $wrap.find('.pn-cm-conv-card').each(function () {
        if (!status) {
          $(this).show();
        } else {
          $(this).toggle($(this).attr('data-status') === status);
        }
      });
    }

    function openPopup(convId) {
      if (!$popup.length) return;

      // Show loading
      $popupBody.html('<div class="pn-cm-csv-spinner"></div>');

      // Open via plugin popup manager
      pn_customers_manager_Popups.open(popupId);

      doAjax(detailType, { conv_id: convId }, function (res) {
        if (res.error_key) {
          $popupBody.html('<div style="padding:40px;text-align:center;color:#d63638;">' + res.error_key + '</div>');
          return;
        }
        $popupBody.html(res.html || '');
        // Auto-scroll chat to bottom
        var $chat = $popupBody.find('.pn-cm-wa-chat');
        if ($chat.length) {
          $chat.scrollTop($chat[0].scrollHeight);
        }
      });
    }

    function doPopupAction(action, convId) {
      doAjax(actionType, { conv_id: convId, conv_action: action }, function (res) {
        if (res.error_key) {
          alert(res.error_key);
          return;
        }

        // Close popup via plugin manager
        pn_customers_manager_Popups.close();

        updateCardAfterAction(action, convId);
      });
    }

    function doCardAction(action, convId) {
      doAjax(actionType, { conv_id: convId, conv_action: action }, function (res) {
        if (res.error_key) {
          alert(res.error_key);
          return;
        }
        updateCardAfterAction(action, convId);
      });
    }

    function updateCardAfterAction(action, convId) {
      var $card = $wrap.find('.pn-cm-conv-card[data-conv-id="' + convId + '"]');
      if (!$card.length) return;

      if (action === 'delete') {
        $card.fadeOut(200, function () { $(this).remove(); });
      } else if (action === 'close') {
        $card.attr('data-status', 'closed');
        var $badge = $card.find('.pn-cm-conv-status');
        if ($badge.length) {
          $badge.attr('class', 'pn-cm-conv-status pn-cm-conv-status-closed').text(pnCmConvFrontI18n.closed || 'Closed');
        }
        $card.find('.pn-cm-conv-close-btn').fadeOut(150, function () { $(this).remove(); });

        // Reapply active filter
        var $activeFilter = $wrap.find('.pn-cm-conv-filter-btn.active');
        var st = $activeFilter.attr('data-status') || '';
        if (st) filterCards(st);
      } else if (action === 'reset') {
        // Clear the visible last-message preview and flip the card back to active.
        $card.find('.pn-cm-conv-card-last-msg').remove();

        var wasClosed = $card.attr('data-status') === 'closed';
        $card.attr('data-status', 'active');
        var $resetBadge = $card.find('.pn-cm-conv-status');
        if ($resetBadge.length) {
          $resetBadge.attr('class', 'pn-cm-conv-status pn-cm-conv-status-active').text(pnCmConvFrontI18n.active || 'Active');
        }

        // If the card was previously closed, the close button was removed —
        // re-add it so the user can close the freshly reset conversation again.
        if (wasClosed && !$card.find('.pn-cm-conv-close-btn').length) {
          var $actions = $card.find('.pn-cm-conv-card-actions');
          var $deleteBtn = $actions.find('.pn-cm-conv-delete-btn');
          var $closeBtn = $('<button type="button" class="pn-cm-conv-card-action pn-cm-conv-close-btn"></button>')
            .attr('data-conv-id', convId)
            .html('<span class="material-icons-outlined">close</span>');
          if ($deleteBtn.length) {
            $closeBtn.insertBefore($deleteBtn);
          } else {
            $actions.append($closeBtn);
          }
        }

        // Reapply active filter in case the card moved in/out of view.
        var $activeFilterR = $wrap.find('.pn-cm-conv-filter-btn.active');
        var stR = $activeFilterR.attr('data-status') || '';
        if (stR) filterCards(stR);
      }
    }

    function doAjax(type, extra, callback) {
      var data = {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: type,
        pn_customers_manager_ajax_nonce: nonce
      };
      $.extend(data, extra);

      $.post(ajaxUrl, data, function (raw) {
        var json = typeof raw === 'string' ? JSON.parse(raw) : raw;
        callback(json);
      }).fail(function () {
        callback({ error_key: 'network_error' });
      });
    }
  }

})(jQuery);
