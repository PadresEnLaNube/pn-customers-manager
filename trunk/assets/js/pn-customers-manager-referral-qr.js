(function($) {
  'use strict';

  /* ========================================
   * Part 1 — QR Canvas Rendering
   * (referral panel, logged-in users)
   * ======================================== */

  function renderQrCanvas() {
    var data = window.pnCmReferralQrData;
    if (!data || !data.url || !data.code) {
      return;
    }

    var canvas = document.getElementById('pn-cm-referral-qr-canvas');
    if (!canvas || !canvas.getContext) {
      return;
    }

    var ctx = canvas.getContext('2d');
    var canvasW = 300;
    var canvasH = 340;
    var qrAreaSize = 280;
    var qrOffsetX = (canvasW - qrAreaSize) / 2;
    var qrOffsetY = 10;

    // White background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvasW, canvasH);

    // Generate QR
    var qr = qrcode(0, 'H');
    qr.addData(data.url);
    qr.make();

    var moduleCount = qr.getModuleCount();
    var moduleSize = qrAreaSize / moduleCount;

    // Draw modules
    for (var row = 0; row < moduleCount; row++) {
      for (var col = 0; col < moduleCount; col++) {
        if (qr.isDark(row, col)) {
          ctx.fillStyle = '#000000';
        } else {
          ctx.fillStyle = '#ffffff';
        }
        ctx.fillRect(
          qrOffsetX + col * moduleSize,
          qrOffsetY + row * moduleSize,
          moduleSize + 0.5,
          moduleSize + 0.5
        );
      }
    }

    // Clear center 25% for branding
    var clearSize = qrAreaSize * 0.25;
    var clearX = qrOffsetX + (qrAreaSize - clearSize) / 2;
    var clearY = qrOffsetY + (qrAreaSize - clearSize) / 2;

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(clearX, clearY, clearSize, clearSize);

    // Draw branding image if available
    if (data.brandingUrl) {
      var img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = function() {
        var imgSize = clearSize * 0.85;
        var imgX = clearX + (clearSize - imgSize) / 2;
        var imgY = clearY + (clearSize - imgSize) / 2;
        ctx.drawImage(img, imgX, imgY, imgSize, imgSize);
        drawCodeText(ctx, data.code, canvasW, qrOffsetY + qrAreaSize);
      };
      img.onerror = function() {
        drawCodeText(ctx, data.code, canvasW, qrOffsetY + qrAreaSize);
      };
      img.src = data.brandingUrl;
    } else {
      drawCodeText(ctx, data.code, canvasW, qrOffsetY + qrAreaSize);
    }
  }

  function drawCodeText(ctx, code, canvasW, qrBottom) {
    ctx.fillStyle = '#000000';
    ctx.font = 'bold 16px Courier New, Courier, monospace';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    ctx.fillText(code, canvasW / 2, qrBottom + 10);
  }

  // Download handler
  $(document).on('click', '.pn-cm-referral-qr-download', function() {
    var canvas = document.getElementById('pn-cm-referral-qr-canvas');
    if (!canvas) {
      return;
    }

    var data = window.pnCmReferralQrData;
    var filename = 'qr-referral-' + (data && data.code ? data.code : 'code') + '.png';

    var link = document.createElement('a');
    link.download = filename;
    link.href = canvas.toDataURL('image/png');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });

  // Render QR on DOM ready
  $(document).ready(function() {
    renderQrCanvas();
  });

  /* ========================================
   * Part 2 — Landing Popup
   * (any page with ?pn_cm_qr_ref)
   * ======================================== */

  $(document).on('click', '#pn-cm-qr-referral-submit', function() {
    var $btn = $(this);
    var $form = $btn.closest('.pn-cm-qr-referral-landing-form');
    var $emailInput = $form.find('#pn-cm-qr-referral-email');
    var $codeInput = $form.find('#pn-cm-qr-referral-code');
    var $message = $form.find('.pn-cm-qr-referral-landing-message');
    var email = $.trim($emailInput.val());
    var code = $codeInput.val();

    $message.hide().removeClass('pn-cm-qr-referral-landing-message-success pn-cm-qr-referral-landing-message-error');

    if (!email) {
      showLandingMessage($message, pn_customers_manager_i18n.qr_referral_email_required, 'error');
      return;
    }

    $btn.prop('disabled', true);

    $.ajax({
      url: pn_customers_manager_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'pn_customers_manager_ajax_nopriv',
        pn_customers_manager_ajax_nopriv_type: 'pn_cm_qr_referral_create',
        pn_customers_manager_ajax_nopriv_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        referral_email: email,
        referral_code: code
      },
      dataType: 'json',
      success: function(response) {
        $btn.prop('disabled', false);

        if (response.error_key && response.error_key !== '') {
          var errorMsg = response.error_content || pn_customers_manager_i18n.qr_referral_error;
          showLandingMessage($message, errorMsg, 'error');
          return;
        }

        var successMsg = response.error_content || pn_customers_manager_i18n.qr_referral_success;
        showLandingMessage($message, successMsg, 'success');
        $emailInput.hide();
        $btn.hide();

        setTimeout(function() {
          if (typeof pn_customers_manager_Popups !== 'undefined') {
            pn_customers_manager_Popups.close();
          }
        }, 4000);
      },
      error: function() {
        $btn.prop('disabled', false);
        showLandingMessage($message, pn_customers_manager_i18n.qr_referral_error, 'error');
      }
    });
  });

  // Enter key support on email input
  $(document).on('keydown', '#pn-cm-qr-referral-email', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      $('#pn-cm-qr-referral-submit').trigger('click');
    }
  });

  function showLandingMessage($el, text, type) {
    $el.text(text)
       .removeClass('pn-cm-qr-referral-landing-message-success pn-cm-qr-referral-landing-message-error')
       .addClass('pn-cm-qr-referral-landing-message-' + type)
       .show();
  }

})(jQuery);
