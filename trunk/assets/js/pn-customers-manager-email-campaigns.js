(function($) {
  'use strict';

  var pollingInterval = null;

  // --- Send campaign ---
  $(document).on('click', '.pn-cm-email-campaigns-send-btn', function() {
    var $panel = $(this).closest('.pn-cm-email-campaigns-panel');
    var $btn = $(this);
    var $message = $panel.find('.pn-cm-email-campaigns-message');
    var $select = $panel.find('.pn-cm-email-campaigns-users-select');
    var mailId = $panel.data('mail-id');
    var userIds = $select.val();
    var externalEmails = [];
    $panel.find('input[type="email"]').each(function() {
      var val = $.trim($(this).val());
      if (val) {
        externalEmails.push(val);
      }
    });

    $message.hide().removeClass('pn-cm-email-campaigns-message-success pn-cm-email-campaigns-message-error');

    var hasUsers = userIds && userIds.length > 0;
    var hasEmails = externalEmails.length > 0;
    var externalEmailsStr = externalEmails.join("\n");

    if (!hasUsers && !hasEmails) {
      showMessage($message, pn_customers_manager_i18n.email_campaigns_no_recipients || 'Select at least one user or enter an email.', 'error');
      return;
    }

    if (!confirm(pn_customers_manager_i18n.email_campaigns_confirm_send || 'Are you sure you want to send this campaign to the selected recipients?')) {
      return;
    }

    $btn.prop('disabled', true);

    var ajaxData = {
      action: 'pn_customers_manager_ajax',
      pn_customers_manager_ajax_type: 'pn_cm_email_campaign_send',
      pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
      mail_id: mailId
    };

    if (hasUsers) {
      ajaxData.user_ids = userIds;
    }
    if (hasEmails) {
      ajaxData.emails = externalEmailsStr;
    }

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: ajaxData,
      dataType: 'json',
      success: function(response) {
        $btn.prop('disabled', false);

        if (response.error_key && response.error_key !== '') {
          var errorMsg = response.error_content || pn_customers_manager_i18n.an_error_has_occurred;
          showMessage($message, errorMsg, 'error');
          return;
        }

        showMessage($message, pn_customers_manager_i18n.email_campaigns_sent || 'Campaign sent successfully. Emails are being processed.', 'success');

        // Show progress bar and start polling
        $panel.find('.pn-cm-email-campaigns-progress').show();
        startProgressPolling($panel, mailId);
      },
      error: function() {
        $btn.prop('disabled', false);
        showMessage($message, pn_customers_manager_i18n.an_error_has_occurred, 'error');
      }
    });
  });

  function startProgressPolling($panel, mailId) {
    if (pollingInterval) {
      clearInterval(pollingInterval);
    }

    pollingInterval = setInterval(function() {
      $.ajax({
        url: pn_customers_manager_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'pn_customers_manager_ajax',
          pn_customers_manager_ajax_type: 'pn_cm_email_campaign_progress',
          pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
          mail_id: mailId
        },
        dataType: 'json',
        success: function(response) {
          if (response.error_key && response.error_key !== '') {
            return;
          }

          // Update stats
          $panel.find('[data-stat="sent"]').text(response.sent);
          $panel.find('[data-stat="opened"]').text(response.opened);
          $panel.find('[data-stat="clicks"]').text(response.clicks);
          $panel.find('[data-stat="queued"]').text(response.queued);

          // Update progress bar
          var pct = response.percentage || 0;
          $panel.find('.pn-cm-email-campaigns-progress-fill').css('width', pct + '%');
          $panel.find('.pn-cm-email-campaigns-progress-text').text(pct + '%');

          // Update records table
          if (response.table_html) {
            $panel.find('.pn-cm-email-campaigns-records-wrapper').html(response.table_html);
          }

          // Stop polling when queue is empty
          if (parseInt(response.queued, 10) === 0) {
            clearInterval(pollingInterval);
            pollingInterval = null;

            setTimeout(function() {
              $panel.find('.pn-cm-email-campaigns-progress').fadeOut();
            }, 2000);
          }
        }
      });
    }, 5000);
  }

  function showMessage($el, text, type) {
    $el.text(text)
       .removeClass('pn-cm-email-campaigns-message-success pn-cm-email-campaigns-message-error')
       .addClass('pn-cm-email-campaigns-message-' + type)
       .show();

    if (type === 'success') {
      setTimeout(function() { $el.fadeOut(); }, 5000);
    }
  }

})(jQuery);
