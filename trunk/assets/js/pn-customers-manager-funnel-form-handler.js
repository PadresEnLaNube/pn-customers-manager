(function() {
  'use strict';

  var i18n = window.pnCmFunnelForm || {};

  /**
   * Replace the loader spinner inside the overlay with a result icon + message,
   * keep it visible for a few seconds, then fade out and remove.
   */
  function showOverlayResult(form, btn, feedback, isSuccess, message) {
    var ov = form.querySelector('.pn-cm-funnel-form-overlay');
    if (ov) {
      var iconSvg = isSuccess
        ? '<svg class="pn-cm-funnel-form-result-icon" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>'
        : '<svg class="pn-cm-funnel-form-result-icon" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
      ov.innerHTML = '<div class="pn-cm-funnel-form-result">' + iconSvg + '<span class="pn-cm-funnel-form-result-text">' + message + '</span></div>';
      ov.classList.add('pn-cm-funnel-form-overlay-result');

      setTimeout(function() {
        ov.classList.add('pn-cm-funnel-form-overlay-fade-out');
        setTimeout(function() {
          if (ov.parentNode) ov.remove();
        }, 400);
      }, 3000);
    }
    btn.disabled = false;
    // Also set the small feedback text below for accessibility
    feedback.className = 'pn-cm-funnel-form-feedback ' + (isSuccess ? 'pn-cm-funnel-form-success' : 'pn-cm-funnel-form-error');
    feedback.textContent = message;
  }

  document.querySelectorAll('.pn-cm-funnel-form').forEach(function(form) {
    var ajaxUrl = form.getAttribute('data-ajax-url');
    var nonce   = form.getAttribute('data-nonce');
    var successMessage = form.getAttribute('data-success-message') || i18n.success || 'Formulario enviado correctamente. Gracias.';

    if (!ajaxUrl || !nonce) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var feedback = form.querySelector('.pn-cm-funnel-form-feedback');
      var btn      = form.querySelector('button[type="submit"]');

      // Client-side required field validation
      var requiredFields = form.querySelectorAll('[required]');
      var allFilled = true;
      for (var ri = 0; ri < requiredFields.length; ri++) {
        if (!requiredFields[ri].value.trim()) {
          allFilled = false;
          break;
        }
      }

      if (!allFilled) {
        feedback.className = 'pn-cm-funnel-form-feedback pn-cm-funnel-form-error';
        feedback.textContent = i18n.requiredFields || 'Please complete all required fields.';
        return;
      }

      // Email format validation
      var emailInputs = form.querySelectorAll('input[type="email"]');
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      for (var ei = 0; ei < emailInputs.length; ei++) {
        var emailVal = emailInputs[ei].value.trim();
        if (emailVal && !emailRegex.test(emailVal)) {
          feedback.className = 'pn-cm-funnel-form-feedback pn-cm-funnel-form-error';
          feedback.textContent = i18n.invalidEmail || 'Please enter a valid email address.';
          return;
        }
      }

      btn.disabled = true;
      feedback.className = 'pn-cm-funnel-form-feedback';
      feedback.textContent = i18n.sending || 'Enviando...';

      // Create loader overlay
      var overlay = document.createElement('div');
      overlay.className = 'pn-cm-funnel-form-overlay';
      overlay.innerHTML = '<div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div>';
      form.style.position = 'relative';
      form.appendChild(overlay);

      var fd = new FormData(form);
      fd.append('action', 'pn_customers_manager_ajax_nopriv');
      fd.append('pn_customers_manager_ajax_nopriv_type', 'pn_cm_funnel_form_submit');
      fd.append('pn_customers_manager_ajax_nopriv_nonce', nonce);
      fd.append('pn_cm_ff_source_url', window.location.href);
      fd.append('pn_cm_ff_source_title', document.title);

      fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var isSuccess = !data.error_key || data.error_key === '';
        var msg = isSuccess
          ? successMessage
          : (data.error_content || i18n.genericError || 'Ha ocurrido un error. Int\u00e9ntalo de nuevo.');
        showOverlayResult(form, btn, feedback, isSuccess, msg);
        if (isSuccess) form.reset();
      })
      .catch(function() {
        showOverlayResult(form, btn, feedback, false, i18n.connectionError || 'Error de conexi\u00f3n. Int\u00e9ntalo de nuevo.');
      });
    });

    /* ===== Display mode: Sequential / Fullscreen ===== */
    var displayMode = form.getAttribute('data-display-mode') || 'basic';

    if (displayMode === 'sequential' || displayMode === 'fullscreen') {
      var steps = form.querySelectorAll('.pn-cm-funnel-form-step');
      if (steps.length > 1) {
        var currentStep = 0;
        var totalSteps = steps.length;

        // Hide original submit button wrapper
        var submitWrap = form.querySelector('.pn-customers-manager-text-align-right');
        if (submitWrap) submitWrap.style.display = 'none';

        // Create progress bar
        var progressWrap = document.createElement('div');
        progressWrap.className = 'pn-cm-funnel-form-progress';
        var progressBar = document.createElement('div');
        progressBar.className = 'pn-cm-funnel-form-progress-bar';
        progressWrap.appendChild(progressBar);

        // Create progress text
        var progressText = document.createElement('div');
        progressText.className = 'pn-cm-funnel-form-progress-text';

        // Insert progress elements before first step
        form.insertBefore(progressText, steps[0]);
        form.insertBefore(progressWrap, progressText);

        // Create navigation bar
        var navBar = document.createElement('div');
        navBar.className = 'pn-cm-funnel-form-nav';

        var backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'pn-cm-funnel-form-nav-btn';
        backBtn.textContent = i18n.back || 'Back';

        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'pn-cm-funnel-form-nav-btn';
        nextBtn.textContent = i18n.next || 'Next';

        // Clone the original submit button for the last step
        var origSubmitBtn = form.querySelector('button[type="submit"]');
        var navSubmitBtn = document.createElement('button');
        navSubmitBtn.type = 'submit';
        navSubmitBtn.className = 'pn-customers-manager-btn pn-cm-funnel-form-nav-submit';
        navSubmitBtn.textContent = origSubmitBtn ? origSubmitBtn.textContent : 'Submit';

        navBar.appendChild(backBtn);
        navBar.appendChild(nextBtn);
        navBar.appendChild(navSubmitBtn);

        // Insert nav bar before feedback element
        var feedbackEl = form.querySelector('.pn-cm-funnel-form-feedback');
        if (feedbackEl) {
          form.insertBefore(navBar, feedbackEl);
        } else {
          form.appendChild(navBar);
        }

        function updateStepDisplay() {
          for (var si = 0; si < steps.length; si++) {
            steps[si].style.display = si === currentStep ? '' : 'none';
            // Re-trigger animation
            if (si === currentStep) {
              steps[si].style.animation = 'none';
              steps[si].offsetHeight; // force reflow
              steps[si].style.animation = '';
            }
          }
          backBtn.disabled = currentStep === 0;
          nextBtn.disabled = currentStep === totalSteps - 1;
          nextBtn.style.display = currentStep === totalSteps - 1 ? 'none' : '';
          navSubmitBtn.style.display = currentStep === totalSteps - 1 ? '' : 'none';
          progressBar.style.width = ((currentStep + 1) / totalSteps * 100) + '%';
          progressText.textContent = (currentStep + 1) + ' ' + (i18n.stepOf || 'of') + ' ' + totalSteps;
        }

        function validateCurrentStep() {
          var currentFields = steps[currentStep].querySelectorAll('[required]');
          for (var vi = 0; vi < currentFields.length; vi++) {
            if (!currentFields[vi].value.trim()) {
              var feedbackMsg = form.querySelector('.pn-cm-funnel-form-feedback');
              if (feedbackMsg) {
                feedbackMsg.className = 'pn-cm-funnel-form-feedback pn-cm-funnel-form-error';
                feedbackMsg.textContent = i18n.requiredFields || 'Please complete all required fields.';
              }
              currentFields[vi].focus();
              return false;
            }
          }
          // Also validate email fields in current step
          var emailFields = steps[currentStep].querySelectorAll('input[type="email"]');
          var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          for (var efi = 0; efi < emailFields.length; efi++) {
            var eVal = emailFields[efi].value.trim();
            if (eVal && !emailRe.test(eVal)) {
              var feedbackMsg2 = form.querySelector('.pn-cm-funnel-form-feedback');
              if (feedbackMsg2) {
                feedbackMsg2.className = 'pn-cm-funnel-form-feedback pn-cm-funnel-form-error';
                feedbackMsg2.textContent = i18n.invalidEmail || 'Please enter a valid email address.';
              }
              emailFields[efi].focus();
              return false;
            }
          }
          // Clear feedback on valid step
          var feedbackClear = form.querySelector('.pn-cm-funnel-form-feedback');
          if (feedbackClear) {
            feedbackClear.className = 'pn-cm-funnel-form-feedback';
            feedbackClear.textContent = '';
          }
          return true;
        }

        backBtn.addEventListener('click', function() {
          if (currentStep > 0) {
            currentStep--;
            updateStepDisplay();
          }
        });

        nextBtn.addEventListener('click', function() {
          if (validateCurrentStep() && currentStep < totalSteps - 1) {
            currentStep++;
            updateStepDisplay();
          }
        });

        updateStepDisplay();

        /* ===== Fullscreen mode ===== */
        if (displayMode === 'fullscreen') {
          var fullscreenBg = form.getAttribute('data-fullscreen-bg') || '#ffffff';

          // Calculate luminance to decide light/dark text
          function hexToLuminance(hex) {
            hex = hex.replace('#', '');
            if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
            var r = parseInt(hex.substr(0, 2), 16) / 255;
            var g = parseInt(hex.substr(2, 2), 16) / 255;
            var b = parseInt(hex.substr(4, 2), 16) / 255;
            r = r <= 0.03928 ? r / 12.92 : Math.pow((r + 0.055) / 1.055, 2.4);
            g = g <= 0.03928 ? g / 12.92 : Math.pow((g + 0.055) / 1.055, 2.4);
            b = b <= 0.03928 ? b / 12.92 : Math.pow((b + 0.055) / 1.055, 2.4);
            return 0.2126 * r + 0.7152 * g + 0.0722 * b;
          }

          var isDark = hexToLuminance(fullscreenBg) < 0.5;

          // Create fullscreen overlay
          var fsOverlay = document.createElement('div');
          fsOverlay.className = 'pn-cm-funnel-form-fullscreen' + (isDark ? ' pn-cm-funnel-form-fullscreen-dark' : '');
          fsOverlay.style.backgroundColor = fullscreenBg;

          // Close button
          var closeBtn = document.createElement('button');
          closeBtn.type = 'button';
          closeBtn.className = 'pn-cm-funnel-form-fullscreen-close';
          closeBtn.innerHTML = '<span style="font-size:28px;line-height:1">&times;</span>';
          closeBtn.setAttribute('title', i18n.closeForm || 'Close');
          fsOverlay.appendChild(closeBtn);

          // Container inside overlay
          var fsContainer = document.createElement('div');
          fsContainer.className = 'pn-cm-funnel-form-fullscreen-container';
          fsOverlay.appendChild(fsContainer);

          // Save placeholder
          var formWrap = form.closest('.pn-cm-funnel-form-wrap');
          var placeholder = document.createElement('div');
          placeholder.className = 'pn-cm-funnel-form-fullscreen-placeholder';

          if (formWrap) {
            formWrap.parentNode.insertBefore(placeholder, formWrap);
            fsContainer.appendChild(formWrap);
          } else {
            form.parentNode.insertBefore(placeholder, form);
            fsContainer.appendChild(form);
          }

          document.body.appendChild(fsOverlay);
          document.body.style.overflow = 'hidden';

          closeBtn.addEventListener('click', function() {
            var movedEl = formWrap || form;
            placeholder.parentNode.insertBefore(movedEl, placeholder);
            placeholder.remove();
            fsOverlay.remove();
            document.body.style.overflow = '';
          });
        }
      }
    }
  });
})();
