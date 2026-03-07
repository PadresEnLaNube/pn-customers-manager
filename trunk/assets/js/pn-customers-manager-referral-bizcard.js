(function($) {
  'use strict';

  var bgImage = null;
  var currentFormat = 'standard';
  var includeQr = true;
  var includeQrBack = true;
  var currentFace = 'front';
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

  $(document).on('click', '#pn-cm-bizcard-user-selected', function(e) {
    e.stopPropagation();
    var $dropdown = $(this).siblings('.pn-cm-bizcard-user-dropdown');
    var wasOpen = $dropdown.hasClass('pn-cm-bizcard-user-dropdown-open');
    $('.pn-cm-bizcard-user-dropdown').removeClass('pn-cm-bizcard-user-dropdown-open');
    if (!wasOpen) {
      $dropdown.addClass('pn-cm-bizcard-user-dropdown-open');
      $dropdown.find('.pn-cm-bizcard-user-search').val('').trigger('input').focus();
    }
  });

  $(document).on('input', '.pn-cm-bizcard-user-search', function() {
    var query = $(this).val().toLowerCase();
    $(this).siblings('.pn-cm-bizcard-user-list').find('.pn-cm-bizcard-user-option').each(function() {
      var text = $(this).text().toLowerCase();
      $(this).toggle(text.indexOf(query) !== -1);
    });
  });

  $(document).on('click', '.pn-cm-bizcard-user-option', function() {
    var userId = $(this).data('value');
    var label = $(this).text();
    var $select = $(this).closest('.pn-cm-bizcard-user-select');
    $select.find('.pn-cm-bizcard-user-option').removeClass('pn-cm-bizcard-user-option-active');
    $(this).addClass('pn-cm-bizcard-user-option-active');
    $select.find('#pn-cm-bizcard-user-selected').text(label).data('value', userId);
    $select.find('.pn-cm-bizcard-user-dropdown').removeClass('pn-cm-bizcard-user-dropdown-open');
    selectBizcardUser(userId);
  });

  $(document).on('click', function() {
    $('.pn-cm-bizcard-user-dropdown').removeClass('pn-cm-bizcard-user-dropdown-open');
  });

  $(document).on('click', '.pn-cm-bizcard-user-dropdown', function(e) {
    e.stopPropagation();
  });

  function selectBizcardUser(userId) {
    if (!userId && userId !== 0) {
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
  }

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

  $(document).on('change', '#pn-cm-bizcard-qr-back', function() {
    includeQrBack = $(this).is(':checked');
    renderCard();
  });

  /* ---- tabs ---- */

  $(document).on('click', '.pn-cm-bizcard-tab', function() {
    var face = $(this).data('face');
    if (face === currentFace) return;
    currentFace = face;
    $('.pn-cm-bizcard-tab').removeClass('pn-cm-bizcard-tab-active');
    $(this).addClass('pn-cm-bizcard-tab-active');
    if (face === 'front') {
      $('.pn-cm-bizcard-face-front').removeClass('pn-cm-bizcard-face-hidden');
      $('.pn-cm-bizcard-face-back').addClass('pn-cm-bizcard-face-hidden');
    } else {
      $('.pn-cm-bizcard-face-front').addClass('pn-cm-bizcard-face-hidden');
      $('.pn-cm-bizcard-face-back').removeClass('pn-cm-bizcard-face-hidden');
    }
    renderCard();
  });

  /* ---- phrase selector ---- */

  $(document).on('change', '#pn-cm-bizcard-phrase-select', function() {
    var phrase = $(this).val();
    if (phrase) {
      $('#pn-cm-bizcard-message').val(phrase);
      debounceRender();
    }
  });

  /* ---- text inputs ---- */

  $(document).on('input', '.pn-cm-bizcard-field', debounceRender);

  /* ---- render card ---- */

  function drawBackground(ctx, w, h) {
    if (bgImage) {
      var imgW = bgImage.width;
      var imgH = bgImage.height;
      var scale = Math.max(w / imgW, h / imgH) * bgZoom;
      var sw = imgW * scale;
      var sh = imgH * scale;
      var sx = (w - sw) / 2 + bgOffsetX;
      var sy = (h - sh) / 2 + bgOffsetY;
      ctx.drawImage(bgImage, sx, sy, sw, sh);

      ctx.fillStyle = 'rgba(0, 0, 0, 0.45)';
      ctx.fillRect(0, 0, w, h);
    } else {
      var grad = ctx.createLinearGradient(0, 0, w, h);
      grad.addColorStop(0, '#1a1a2e');
      grad.addColorStop(0.5, '#16213e');
      grad.addColorStop(1, '#0f3460');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, w, h);
    }
  }

  function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
    var paragraphs = text.split('\n');
    var lines = [];
    for (var p = 0; p < paragraphs.length; p++) {
      var words = paragraphs[p].split(' ');
      var line = '';
      for (var i = 0; i < words.length; i++) {
        var testLine = line ? line + ' ' + words[i] : words[i];
        if (ctx.measureText(testLine).width > maxWidth && line) {
          lines.push(line);
          line = words[i];
        } else {
          line = testLine;
        }
      }
      lines.push(line);
    }
    var totalHeight = lines.length * lineHeight;
    var startY = y - totalHeight / 2;
    for (var j = 0; j < lines.length; j++) {
      ctx.fillText(lines[j], x, startY + j * lineHeight + lineHeight / 2);
    }
    return startY + totalHeight;
  }

  function renderCard() {
    var canvas = document.getElementById('pn-cm-bizcard-canvas');
    if (!canvas || !canvas.getContext) return;

    var fmt = formats[currentFormat] || formats.standard;
    canvas.width = fmt.w;
    canvas.height = fmt.h;

    var ctx = canvas.getContext('2d');
    var w = fmt.w;
    var h = fmt.h;
    var s = w / 1012;

    drawBackground(ctx, w, h);

    if (currentFace === 'back') {
      renderBack(ctx, w, h, s);
    } else {
      renderFront(ctx, w, h, s);
    }
  }

  function renderFront(ctx, w, h, s) {
    var name  = getField('name');
    var title = getField('title');
    var phone = getField('phone');
    var email = getField('email');
    var web   = getField('web');

    var textX = 120 * s;
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

  function renderBack(ctx, w, h, s) {
    var message = $.trim($('#pn-cm-bizcard-message').val());
    var data = window.pnCmReferralQrData;
    var showQr = includeQrBack && data && data.url && typeof qrcode !== 'undefined';

    var margin = Math.round(60 * s);
    var gap = Math.round(14 * s);
    var radius = Math.round(10 * s);

    // Right column: logo (top) + QR (bottom)
    if (showQr) {
      // Smaller QR for the back
      var qrSize = Math.round(130 * s);
      var padding = Math.round(14 * s);
      var boxSize = qrSize + padding * 2;
      // Logo same size as QR box
      var logoSize = boxSize;
      var colX = w - boxSize - margin;

      // Logo top, QR bottom — same margin as front face
      var logoY = margin;
      var qrBoxY = h - boxSize - margin;

      // --- Logo ---
      if (data.brandingUrl) {
        var brandImg = new Image();
        brandImg.crossOrigin = 'anonymous';
        brandImg.onload = function() {
          ctx.drawImage(brandImg, colX, logoY, logoSize, logoSize);
        };
        brandImg.src = data.brandingUrl;
      }

      // White rounded background
      ctx.fillStyle = '#ffffff';
      ctx.beginPath();
      ctx.moveTo(colX + radius, qrBoxY);
      ctx.lineTo(colX + boxSize - radius, qrBoxY);
      ctx.quadraticCurveTo(colX + boxSize, qrBoxY, colX + boxSize, qrBoxY + radius);
      ctx.lineTo(colX + boxSize, qrBoxY + boxSize - radius);
      ctx.quadraticCurveTo(colX + boxSize, qrBoxY + boxSize, colX + boxSize - radius, qrBoxY + boxSize);
      ctx.lineTo(colX + radius, qrBoxY + boxSize);
      ctx.quadraticCurveTo(colX, qrBoxY + boxSize, colX, qrBoxY + boxSize - radius);
      ctx.lineTo(colX, qrBoxY + radius);
      ctx.quadraticCurveTo(colX, qrBoxY, colX + radius, qrBoxY);
      ctx.closePath();
      ctx.fill();

      // Generate and draw QR
      var qr = qrcode(0, 'H');
      qr.addData(data.url);
      qr.make();

      var moduleCount = qr.getModuleCount();
      var moduleSize = qrSize / moduleCount;
      var qrDrawX = colX + padding;
      var qrDrawY = qrBoxY + padding;

      for (var row = 0; row < moduleCount; row++) {
        for (var col = 0; col < moduleCount; col++) {
          ctx.fillStyle = qr.isDark(row, col) ? '#000000' : '#ffffff';
          ctx.fillRect(
            qrDrawX + col * moduleSize,
            qrDrawY + row * moduleSize,
            moduleSize + 0.5,
            moduleSize + 0.5
          );
        }
      }

      // Branding in QR center
      if (data.brandingUrl) {
        var clearPct = 0.25;
        var clearSize = qrSize * clearPct;
        var clearX = qrDrawX + (qrSize - clearSize) / 2;
        var clearY = qrDrawY + (qrSize - clearSize) / 2;

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(clearX, clearY, clearSize, clearSize);

        var brandImgQr = new Image();
        brandImgQr.crossOrigin = 'anonymous';
        brandImgQr.onload = function() {
          var imgS = clearSize * 0.85;
          var imgX = clearX + (clearSize - imgS) / 2;
          var imgY = clearY + (clearSize - imgS) / 2;
          ctx.drawImage(brandImgQr, imgX, imgY, imgS, imgS);
        };
        brandImgQr.src = data.brandingUrl;
      }

      // Referral code below QR
      if (data.code) {
        ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
        ctx.font = Math.round(14 * s) + 'px Arial, Helvetica, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText(data.code, colX + boxSize / 2, qrBoxY + boxSize + Math.round(6 * s));
      }

      // --- Message on the left side ---
      if (message) {
        var textMargin = Math.round(80 * s);
        var msgMaxW = colX - textMargin - margin;
        var fontSize = Math.round(56 * s);
        var lineHeight = Math.round(76 * s);
        ctx.fillStyle = '#ffffff';
        ctx.font = fontSize + 'px Arial, Helvetica, sans-serif';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        wrapText(ctx, message, textMargin, h / 2, msgMaxW, lineHeight);
      }
    } else if (message) {
      // Only message, centered, double size
      var fontSize2 = Math.round(56 * s);
      var lineHeight2 = Math.round(76 * s);
      var msgMaxW2 = w - Math.round(120 * s);
      ctx.fillStyle = '#ffffff';
      ctx.font = fontSize2 + 'px Arial, Helvetica, sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      wrapText(ctx, message, w / 2, h / 2, msgMaxW2, lineHeight2);
    }
  }

  function drawQrOnCard(ctx, w, h, s) {
    var data = window.pnCmReferralQrData;
    var qrSize = Math.round(195 * s);
    var padding = Math.round(18 * s);
    var margin = Math.round(60 * s);
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

      // Large logo top-right, aligned with QR column
      var logoSize = Math.round(80 * s);
      var logoX = boxX + boxSize - logoSize;
      var logoY = margin;

      var brandImg = new Image();
      brandImg.crossOrigin = 'anonymous';
      brandImg.onload = function() {
        // Small branding in QR center
        var imgS = clearSize * 0.85;
        var imgX = clearX + (clearSize - imgS) / 2;
        var imgY = clearY + (clearSize - imgS) / 2;
        ctx.drawImage(brandImg, imgX, imgY, imgS, imgS);
        // Large logo top-right
        ctx.drawImage(brandImg, logoX, logoY, logoSize, logoSize);
      };
      brandImg.src = data.brandingUrl;
    }
  }

  /* ---- download ---- */

  function downloadFace(face) {
    var canvas = document.getElementById('pn-cm-bizcard-canvas');
    if (!canvas) return;

    var savedFace = currentFace;
    currentFace = face;
    renderCard();

    var name = getField('name') || 'tarjeta';
    var safeName = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    var suffix = face === 'back' ? '-reverso' : '-anverso';
    var filename = 'tarjeta-' + safeName + suffix + '.png';

    var link = document.createElement('a');
    link.download = filename;
    link.href = canvas.toDataURL('image/png');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    currentFace = savedFace;
    renderCard();
  }

  $(document).on('click', '.pn-cm-bizcard-download-front', function() {
    downloadFace('front');
  });

  $(document).on('click', '.pn-cm-bizcard-download-back', function() {
    downloadFace('back');
  });

})(jQuery);
