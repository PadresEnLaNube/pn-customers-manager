(function($) {
  'use strict';

  // --- Frontend: Application form submit ---
  $(document).on('submit', '#pn-cm-commercial-apply-form', function(e) {
    e.preventDefault();

    var $form = $(this);
    var $btn = $form.find('button[type="submit"]');
    var $message = $form.find('.pn-cm-commercial-message');

    $message.hide().removeClass('pn-cm-commercial-message-success pn-cm-commercial-message-error');

    // Client-side validation
    var firstName = $.trim($form.find('#pn_cm_commercial_first_name').val());
    var lastName = $.trim($form.find('#pn_cm_commercial_last_name').val());
    var email = $.trim($form.find('#pn_cm_commercial_email').val());
    var phone = $.trim($form.find('#pn_cm_commercial_phone').val());

    if (!firstName || !lastName || !email || !phone) {
      showMessage($message, pn_customers_manager_i18n.commercial_missing_fields, 'error');
      return;
    }

    $btn.prop('disabled', true);

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: 'pn_cm_commercial_apply',
        pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        pn_cm_commercial_first_name: firstName,
        pn_cm_commercial_last_name: lastName,
        pn_cm_commercial_email: email,
        pn_cm_commercial_phone: phone,
        pn_cm_commercial_company: $.trim($form.find('#pn_cm_commercial_company').val()),
        pn_cm_commercial_message: $.trim($form.find('#pn_cm_commercial_message').val())
      },
      dataType: 'json',
      success: function(response) {
        $btn.prop('disabled', false);

        if (response.error_key && response.error_key !== '') {
          var errorMsg = response.error_content || pn_customers_manager_i18n.an_error_has_occurred;
          showMessage($message, errorMsg, 'error');
          return;
        }

        showMessage($message, pn_customers_manager_i18n.commercial_application_sent, 'success');

        setTimeout(function() {
          window.location.reload();
        }, 2000);
      },
      error: function() {
        $btn.prop('disabled', false);
        showMessage($message, pn_customers_manager_i18n.an_error_has_occurred, 'error');
      }
    });
  });

  // --- Admin: Approve agent ---
  $(document).on('click', '.pn-cm-commercial-approve-btn', function() {
    var $btn = $(this);
    var userId = $btn.data('user-id');
    var $row = $btn.closest('tr');

    if (!confirm(pn_customers_manager_i18n.commercial_confirm_approve)) {
      return;
    }

    $btn.prop('disabled', true);

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: 'pn_cm_commercial_approve',
        pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        pn_cm_commercial_user_id: userId
      },
      dataType: 'json',
      success: function(response) {
        if (response.error_key === '') {
          $row.find('.pn-cm-commercial-admin-badge')
            .removeClass('pn-cm-commercial-admin-badge-pending')
            .addClass('pn-cm-commercial-admin-badge-approved')
            .text(pn_customers_manager_i18n.commercial_status_approved);
          $row.find('td:last-child').html('—');
        } else {
          $btn.prop('disabled', false);
          alert(response.error_content || pn_customers_manager_i18n.an_error_has_occurred);
        }
      },
      error: function() {
        $btn.prop('disabled', false);
        alert(pn_customers_manager_i18n.an_error_has_occurred);
      }
    });
  });

  // --- Admin: Reject agent ---
  $(document).on('click', '.pn-cm-commercial-reject-btn', function() {
    var $btn = $(this);
    var userId = $btn.data('user-id');
    var $row = $btn.closest('tr');

    if (!confirm(pn_customers_manager_i18n.commercial_confirm_reject)) {
      return;
    }

    $btn.prop('disabled', true);

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax',
        pn_customers_manager_ajax_type: 'pn_cm_commercial_reject',
        pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        pn_cm_commercial_user_id: userId
      },
      dataType: 'json',
      success: function(response) {
        if (response.error_key === '') {
          $row.find('.pn-cm-commercial-admin-badge')
            .removeClass('pn-cm-commercial-admin-badge-pending')
            .addClass('pn-cm-commercial-admin-badge-rejected')
            .text(pn_customers_manager_i18n.commercial_status_rejected);
          $row.find('td:last-child').html('—');
        } else {
          $btn.prop('disabled', false);
          alert(response.error_content || pn_customers_manager_i18n.an_error_has_occurred);
        }
      },
      error: function() {
        $btn.prop('disabled', false);
        alert(pn_customers_manager_i18n.an_error_has_occurred);
      }
    });
  });

  function showMessage($el, text, type) {
    $el.text(text)
       .removeClass('pn-cm-commercial-message-success pn-cm-commercial-message-error')
       .addClass('pn-cm-commercial-message-' + type)
       .show();

    if (type === 'success') {
      setTimeout(function() { $el.fadeOut(); }, 5000);
    }
  }

})(jQuery);
