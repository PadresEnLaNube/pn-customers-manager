(function($) {
  'use strict';

  var bgImage = null;
  var currentFormat = 'standard';
  var includeQr = true;
  var debounceTimer = null;
  var bgOffsetX = 0, bgOffsetY = 0;
  var bgZoom = 1;
  var isDragging = false, dragStartX, dragStartY;

  var formats = {
    standard: { w: 1012, h: 654 },
    square:   { w: 774,  h: 774 },
    mini:     { w: 833,  h: 333 }
  };

  /* ---- helpers ---- */

  function getField(id) {
    return $.trim($('#pn-cm-bizcard-' + id).val());
  }

  function debounceRender() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(renderCard, 150);
  }

  /* ---- open popup ---- */

  $(document).on('click', '.pn-cm-referral-bizcard-btn', function() {
    if (typeof pn_customers_manager_Popups !== 'undefined') {
      pn_customers_manager_Popups.open('pn-cm-referral-bizcard-popup');
      renderCard();
    }
  });

  /* ---- admin: generate as another user ---- */

  $(document).on('change', '#pn-cm-bizcard-user', function() {
    var userId = $(this).val();
    if (!userId) {
      // Back to current user — restore original data
      if (window.pnCmReferralQrDataOriginal) {
        window.pnCmReferralQrData = $.extend({}, window.pnCmReferralQrDataOriginal);
      }
      var cu = window.pnCmBizcardCurrentUser || {};
      $('#pn-cm-bizcard-name').val(cu.name || '').trigger('input');
      $('#pn-cm-bizcard-email').val(cu.email || '').trigger('input');
      renderCard();
      return;
    }
    // Save originals on first switch
    if (!window.pnCmReferralQrDataOriginal && window.pnCmReferralQrData) {
      window.pnCmReferralQrDataOriginal = $.extend({}, window.pnCmReferralQrData);
      window.pnCmBizcardCurrentUser = {
        name: $('#pn-cm-bizcard-name').data('original') || $('#pn-cm-bizcard-name').val(),
        email: $('#pn-cm-bizcard-email').data('original') || $('#pn-cm-bizcard-email').val()
      };
      $('#pn-cm-bizcard-name').data('original', window.pnCmBizcardCurrentUser.name);
      $('#pn-cm-bizcard-email').data('original', window.pnCmBizcardCurrentUser.email);
    }
    $.post(pn_customers_manager_ajax.ajax_url, {
      action: 'pn_customers_manager_ajax',
      pn_customers_manager_ajax_type: 'pn_cm_bizcard_user_data',
      pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
      target_user_id: userId
    }, function(resp) {
      var data = typeof resp === 'string' ? JSON.parse(resp) : resp;
      if (data.error_key) return;
      window.pnCmReferralQrData = {
        url: data.qr_url,
        code: data.qr_code,
        brandingUrl: data.branding_url
      };
      $('#pn-cm-bizcard-name').val(data.name);
      $('#pn-cm-bizcard-email').val(data.email);
      renderCard();
    });
  });

  /* ---- background image ---- */

  $(document).on('click', '.pn-cm-bizcard-bg-label', function() {
    $('#pn-cm-bizcard-bg-input').trigger('click');
  });

  $(document).on('click', '.pn-cm-bizcard-media-thumb', function() {
    var fullUrl = $(this).data('full');
    if (!fullUrl) return;
    var img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function() {
      bgImage = img;
      bgOffsetX = 0;
      bgOffsetY = 0;
      bgZoom = 1;
      updateZoomLabel();
      $('#pn-cm-bizcard-canvas').addClass('pn-cm-bizcard-canvas-draggable');
      $('.pn-cm-bizcard-zoom-controls').addClass('pn-cm-bizcard-zoom-visible');
      $('.pn-cm-bizcard-bg-thumb').attr('src', fullUrl).show();
      $('.pn-cm-bizcard-bg-remove').show();
      $('.pn-cm-bizcard-media-thumb').removeClass('pn-cm-bizcard-media-thumb-active');
      $(this).addClass('pn-cm-bizcard-media-thumb-active');
      renderCard();
    }.bind(this);
    img.src = fullUrl;
  });

  $(document).on('change', '#pn-cm-bizcard-bg-input', function() {
    var file = this.files && this.files[0];
    if (!file) return;

    var reader = new FileReader();
    reader.onload = function(e) {
      var img = new Image();
      img.onload = function() {
        bgImage = img;
        bgOffsetX = 0;
        bgOffsetY = 0;
        bgZoom = 1;
        updateZoomLabel();
        $('#pn-cm-bizcard-canvas').addClass('pn-cm-bizcard-canvas-draggable');
        $('.pn-cm-bizcard-zoom-controls').addClass('pn-cm-bizcard-zoom-visible');
        $('.pn-cm-bizcard-bg-thumb').attr('src', e.target.result).show();
        $('.pn-cm-bizcard-bg-remove').show();
        renderCard();
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });

  $(document).on('click', '.pn-cm-bizcard-bg-remove', function() {
    bgImage = null;
    bgOffsetX = 0;
    bgOffsetY = 0;
    bgZoom = 1;
    updateZoomLabel();
    $('#pn-cm-bizcard-canvas').removeClass('pn-cm-bizcard-canvas-draggable pn-cm-bizcard-canvas-dragging');
    $('.pn-cm-bizcard-zoom-controls').removeClass('pn-cm-bizcard-zoom-visible');
    $('#pn-cm-bizcard-bg-input').val('');
    $('.pn-cm-bizcard-bg-thumb').hide();
    $('.pn-cm-bizcard-media-thumb').removeClass('pn-cm-bizcard-media-thumb-active');
    $(this).hide();
    renderCard();
  });

  /* ---- zoom background image ---- */

  function updateZoomLabel() {
    $('.pn-cm-bizcard-zoom-level').text(Math.round(bgZoom * 100) + '%');
  }

  $(document).on('click', '.pn-cm-bizcard-zoom-btn', function() {
    if (!bgImage) return;
    var action = $(this).data('zoom');
    if (action === 'in') {
      bgZoom = Math.min(bgZoom + 0.1, 3);
    } else if (action === 'out') {
      bgZoom = Math.max(bgZoom - 0.1, 0.5);
    } else if (action === 'reset') {
      bgZoom = 1;
      bgOffsetX = 0;
      bgOffsetY = 0;
    }
    updateZoomLabel();
    renderCard();
  });

  /* ---- drag background image ---- */

  $(document).on('mousedown', '#pn-cm-bizcard-canvas', function(e) {
    if (!bgImage) return;
    isDragging = true;
    var canvas = this;
    var ratio = canvas.width / canvas.offsetWidth;
    dragStartX = e.clientX * ratio - bgOffsetX;
    dragStartY = e.clientY * ratio - bgOffsetY;
    $(canvas).addClass('pn-cm-bizcard-canvas-dragging');
  });

  $(document).on('touchstart', '#pn-cm-bizcard-canvas', function(e) {
    if (!bgImage) return;
    e.preventDefault();
    isDragging = true;
    var canvas = this;
    var touch = e.originalEvent.touches[0];
    var ratio = canvas.width / canvas.offsetWidth;
    dragStartX = touch.clientX * ratio - bgOffsetX;
    dragStartY = touch.clientY * ratio - bgOffsetY;
    $(canvas).addClass('pn-cm-bizcard-canvas-dragging');
  });

  $(document).on('mousemove', function(e) {
    if (!isDragging) return;
    var canvas = document.getElementById('pn-cm-bizcard-canvas');
    if (!canvas) return;
    var ratio = canvas.width / canvas.offsetWidth;
    bgOffsetX = e.clientX * ratio - dragStartX;
    bgOffsetY = e.clientY * ratio - dragStartY;
    renderCard();
  });

  $(document).on('touchmove', function(e) {
    if (!isDragging) return;
    e.preventDefault();
    var canvas = document.getElementById('pn-cm-bizcard-canvas');
    if (!canvas) return;
    var touch = e.originalEvent.touches[0];
    var ratio = canvas.width / canvas.offsetWidth;
    bgOffsetX = touch.clientX * ratio - dragStartX;
    bgOffsetY = touch.clientY * ratio - dragStartY;
    renderCard();
  });

  $(document).on('mouseup touchend', function() {
    if (!isDragging) return;
    isDragging = false;
    $('#pn-cm-bizcard-canvas').removeClass('pn-cm-bizcard-canvas-dragging');
  });

  /* ---- format change ---- */

  $(document).on('change', '#pn-cm-bizcard-format', function() {
    currentFormat = $(this).val();
    renderCard();
  });

  /* ---- QR checkbox ---- */

  $(document).on('change', '#pn-cm-bizcard-qr', function() {
    includeQr = $(this).is(':checked');
    renderCard();
  });

  /* ---- text inputs ---- */

  $(document).on('input', '.pn-cm-bizcard-field', debounceRender);

  /* ---- render card ---- */

  function renderCard() {
    var canvas = document.getElementById('pn-cm-bizcard-canvas');
    if (!canvas || !canvas.getContext) return;

    var fmt = formats[currentFormat] || formats.standard;
    canvas.width = fmt.w;
    canvas.height = fmt.h;

    var ctx = canvas.getContext('2d');
    var w = fmt.w;
    var h = fmt.h;

    // Background
    if (bgImage) {
      // Cover the canvas with the image
      var imgW = bgImage.width;
      var imgH = bgImage.height;
      var scale = Math.max(w / imgW, h / imgH) * bgZoom;
      var sw = imgW * scale;
      var sh = imgH * scale;
      var sx = (w - sw) / 2 + bgOffsetX;
      var sy = (h - sh) / 2 + bgOffsetY;
      ctx.drawImage(bgImage, sx, sy, sw, sh);

      // Semi-transparent overlay
      ctx.fillStyle = 'rgba(0, 0, 0, 0.45)';
      ctx.fillRect(0, 0, w, h);
    } else {
      // Default dark gradient
      var grad = ctx.createLinearGradient(0, 0, w, h);
      grad.addColorStop(0, '#1a1a2e');
      grad.addColorStop(0.5, '#16213e');
      grad.addColorStop(1, '#0f3460');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, w, h);
    }

    // Scale factor based on standard width
    var s = w / 1012;

    // Texts
    var name  = getField('name');
    var title = getField('title');
    var phone = getField('phone');
    var email = getField('email');
    var web   = getField('web');

    var textX = 60 * s;
    var textY = h * 0.38;

    // Name
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold ' + Math.round(84 * s) + 'px Arial, Helvetica, sans-serif';
    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';
    ctx.fillText(name, textX, textY);

    // Title
    if (title) {
      ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
      ctx.font = Math.round(44 * s) + 'px Arial, Helvetica, sans-serif';
      ctx.fillText(title, textX, textY + 88 * s);
    }

    // Separator line
    var sepY = textY + 160 * s;
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(textX, sepY);
    ctx.lineTo(textX + 400 * s, sepY);
    ctx.stroke();

    // Contact details
    ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
    ctx.font = Math.round(36 * s) + 'px Arial, Helvetica, sans-serif';
    var detailY = sepY + 60 * s;
    var lineH = 64 * s;

    if (phone) {
      ctx.fillText(phone, textX, detailY);
      detailY += lineH;
    }
    if (email) {
      ctx.fillText(email, textX, detailY);
      detailY += lineH;
    }
    if (web) {
      ctx.fillText(web, textX, detailY);
    }

    // QR code
    if (includeQr && window.pnCmReferralQrData && window.pnCmReferralQrData.url && typeof qrcode !== 'undefined') {
      drawQrOnCard(ctx, w, h, s);
    }
  }

  function drawQrOnCard(ctx, w, h, s) {
    var data = window.pnCmReferralQrData;
    var qrSize = Math.round(195 * s);
    var padding = Math.round(18 * s);
    var margin = Math.round(30 * s);
    var boxSize = qrSize + padding * 2;
    var boxX = w - boxSize - margin;
    var boxY = h - boxSize - margin;
    var radius = Math.round(10 * s);

    // White rounded background
    ctx.fillStyle = '#ffffff';
    ctx.beginPath();
    ctx.moveTo(boxX + radius, boxY);
    ctx.lineTo(boxX + boxSize - radius, boxY);
    ctx.quadraticCurveTo(boxX + boxSize, boxY, boxX + boxSize, boxY + radius);
    ctx.lineTo(boxX + boxSize, boxY + boxSize - radius);
    ctx.quadraticCurveTo(boxX + boxSize, boxY + boxSize, boxX + boxSize - radius, boxY + boxSize);
    ctx.lineTo(boxX + radius, boxY + boxSize);
    ctx.quadraticCurveTo(boxX, boxY + boxSize, boxX, boxY + boxSize - radius);
    ctx.lineTo(boxX, boxY + radius);
    ctx.quadraticCurveTo(boxX, boxY, boxX + radius, boxY);
    ctx.closePath();
    ctx.fill();

    // Generate and draw QR
    var qr = qrcode(0, 'H');
    qr.addData(data.url);
    qr.make();

    var moduleCount = qr.getModuleCount();
    var moduleSize = qrSize / moduleCount;
    var qrX = boxX + padding;
    var qrY = boxY + padding;

    for (var row = 0; row < moduleCount; row++) {
      for (var col = 0; col < moduleCount; col++) {
        ctx.fillStyle = qr.isDark(row, col) ? '#000000' : '#ffffff';
        ctx.fillRect(
          qrX + col * moduleSize,
          qrY + row * moduleSize,
          moduleSize + 0.5,
          moduleSize + 0.5
        );
      }
    }

    // Referral code below QR
    if (data.code) {
      ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
      ctx.font = Math.round(16 * s) + 'px Arial, Helvetica, sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'top';
      ctx.fillText(data.code, boxX + boxSize / 2, boxY + boxSize + Math.round(6 * s));
    }

    // Branding in QR center
    if (data.brandingUrl) {
      var clearPct = 0.25;
      var clearSize = qrSize * clearPct;
      var clearX = qrX + (qrSize - clearSize) / 2;
      var clearY = qrY + (qrSize - clearSize) / 2;

      ctx.fillStyle = '#ffffff';
      ctx.fillRect(clearX, clearY, clearSize, clearSize);

      var brandImg = new Image();
      brandImg.crossOrigin = 'anonymous';
      brandImg.onload = function() {
        var imgS = clearSize * 0.85;
        var imgX = clearX + (clearSize - imgS) / 2;
        var imgY = clearY + (clearSize - imgS) / 2;
        ctx.drawImage(brandImg, imgX, imgY, imgS, imgS);
      };
      brandImg.src = data.brandingUrl;
    }
  }

  /* ---- download ---- */

  $(document).on('click', '.pn-cm-bizcard-download', function() {
    var canvas = document.getElementById('pn-cm-bizcard-canvas');
    if (!canvas) return;

    var name = getField('name') || 'tarjeta';
    var safeName = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    var filename = 'tarjeta-' + safeName + '.png';

    var link = document.createElement('a');
    link.download = filename;
    link.href = canvas.toDataURL('image/png');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });

})(jQuery);
