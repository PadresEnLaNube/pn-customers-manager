(function($) {
  'use strict';

  $(document).on('click', '.pn-cm-referral-submit', function() {
    var $btn = $(this);
    var $panel = $btn.closest('.pn-cm-referral-panel');
    var $form = $panel.find('.pn-cm-referral-create');
    var $emailInput = $form.find('.pn-cm-referral-email');
    var $message = $form.find('.pn-cm-referral-message');
    var $linkDisplay = $form.find('.pn-cm-referral-link-display');
    var email = $.trim($emailInput.val());

    $message.hide().removeClass('pn-cm-referral-message-success pn-cm-referral-message-error');

    if (!email) {
      showMessage($message, pn_customers_manager_i18n.referral_invalid_email, 'error');
      return;
    }

    $btn.prop('disabled', true);

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: 'pn_cm_referral_create',
        pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        referral_email: email
      },
      dataType: 'json',
      success: function(response) {
        $btn.prop('disabled', false);

        if (response.error_key && response.error_key !== '') {
          var errorKey = 'referral_' + response.error_key;
          var errorMsg = pn_customers_manager_i18n[errorKey] || pn_customers_manager_i18n.an_error_has_occurred;
          showMessage($message, errorMsg, 'error');
          return;
        }

        showMessage($message, pn_customers_manager_i18n.referral_sent, 'success');
        $emailInput.val('');

        if (response.referral_link) {
          $linkDisplay.find('.pn-cm-referral-link-input').val(response.referral_link);
          $linkDisplay.show();
        }

        if (response.referral) {
          var ref = response.referral;
          var dateStr = ref.created_at ? ref.created_at.substring(0, 10) : '';
          var $item = $(
            '<div class="pn-cm-referral-item pn-cm-referral-item-new" data-status="' + escAttr(ref.status) + '">' +
              '<span class="pn-cm-referral-col-email">' + escHtml(ref.email) + '</span>' +
              '<span class="pn-cm-referral-col-status">' +
                '<span class="pn-cm-referral-badge pn-cm-referral-badge-' + escAttr(ref.status) + '">' +
                  escHtml(ref.status.charAt(0).toUpperCase() + ref.status.slice(1)) +
                '</span>' +
              '</span>' +
              '<span class="pn-cm-referral-col-date">' + escHtml(dateStr) + '</span>' +
            '</div>'
          );

          var $listBody = $panel.find('.pn-cm-referral-list-body');
          $listBody.find('.pn-cm-referral-empty').remove();
          $listBody.prepend($item);

          setTimeout(function() { $item.removeClass('pn-cm-referral-item-new'); }, 600);
        }

        updateStats($panel, 1, 0);
      },
      error: function() {
        $btn.prop('disabled', false);
        showMessage($message, pn_customers_manager_i18n.an_error_has_occurred, 'error');
      }
    });
  });

  $(document).on('click', '.pn-cm-referral-copy-link', function() {
    var $row = $(this).closest('.pn-cm-referral-link-row');
    var link = $row.find('.pn-cm-referral-link-input').val();
    var $message = $(this).closest('.pn-cm-referral-create').find('.pn-cm-referral-message');

    if (navigator.clipboard && link) {
      navigator.clipboard.writeText(link).then(function() {
        showMessage($message, pn_customers_manager_i18n.referral_link_copied, 'success');
      });
    }
  });

  $(document).on('keydown', '.pn-cm-referral-email', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      $(this).closest('.pn-cm-referral-create').find('.pn-cm-referral-submit').trigger('click');
    }
  });

  function updateStats($panel, totalDelta, completedDelta) {
    var $total = $panel.find('.pn-cm-referral-stat-total');
    var $completed = $panel.find('.pn-cm-referral-stat-completed');
    var $pending = $panel.find('.pn-cm-referral-stat-pending');

    var total = parseInt($total.text(), 10) + totalDelta;
    var completed = parseInt($completed.text(), 10) + completedDelta;
    var pending = total - completed;

    $total.text(total);
    $completed.text(completed);
    $pending.text(pending);
  }

  function showMessage($el, text, type) {
    $el.text(text)
       .removeClass('pn-cm-referral-message-success pn-cm-referral-message-error')
       .addClass('pn-cm-referral-message-' + type)
       .show();

    setTimeout(function() { $el.fadeOut(); }, 5000);
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function escAttr(str) {
    return String(str).replace(/[&"'<>]/g, function(m) {
      return {'&':'&amp;','"':'&quot;',"'":'&#39;','<':'&lt;','>':'&gt;'}[m];
    });
  }

})(jQuery);
