(function () {
  'use strict';

  var saveBtn   = document.getElementById('pn-customers-manager-settings-save');
  var exportBtn = document.getElementById('pn-customers-manager-settings-export');
  var importBtn = document.getElementById('pn-customers-manager-settings-import');
  var fileInput = document.getElementById('pn-customers-manager-settings-import-file');

  if (!saveBtn) return;

  // Hide footer when WP mobile menu is open
  var menuToggle = document.getElementById('wp-admin-bar-menu-toggle');
  var footer = document.getElementById('pn-customers-manager-settings-footer');
  if (menuToggle && footer) {
    menuToggle.addEventListener('click', function () {
      setTimeout(function () {
        footer.style.display = document.body.classList.contains('wp-responsive-open') ? 'none' : '';
      }, 0);
    });
  }

  // Save — trigger the existing form submit
  saveBtn.addEventListener('click', function () {
    var form = document.getElementById('pn-customers-manager-form-setting');
    if (form) {
      form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }
  });

  // Hide banner image
  var bannerHideBtn = document.getElementById('pn-customers-manager-banner-hide-btn');
  if (bannerHideBtn) {
    bannerHideBtn.addEventListener('click', function () {
      if (!confirm(pnCustomersManagerSettingsFooter.i18n.confirmHideBanner)) return;

      var wrapper = bannerHideBtn.closest('.pn-customers-manager-banner-wrapper');
      if (!wrapper) return;

      var overlay = document.createElement('div');
      overlay.className = 'pn-customers-manager-banner-loader-overlay';
      overlay.innerHTML = '<div class="pn-customers-manager-waiting pn-customers-manager-display-block"><div class="pn-customers-manager-loader-circle-waiting"><div></div><div></div><div></div><div></div></div></div>';
      wrapper.appendChild(overlay);
      bannerHideBtn.style.display = 'none';

      var fd = new FormData();
      fd.append('action', 'pn_customers_manager_ajax');
      fd.append('pn_customers_manager_ajax_type', 'pn_cm_hide_banner');
      fd.append('pn_customers_manager_ajax_nonce', pnCustomersManagerSettingsFooter.nonce);

      fetch(pnCustomersManagerSettingsFooter.ajaxUrl, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.error_key) {
            wrapper.style.transition = 'opacity 0.3s';
            wrapper.style.opacity = '0';
            setTimeout(function () { wrapper.remove(); }, 300);

            var bannerCheckbox = document.getElementById('pn_customers_manager_show_banner');
            if (bannerCheckbox) {
              bannerCheckbox.checked = false;
              bannerCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
          } else {
            overlay.remove();
            bannerHideBtn.style.display = '';
          }
        })
        .catch(function () {
          overlay.remove();
          bannerHideBtn.style.display = '';
        });
    });
  }

  // Export
  exportBtn.addEventListener('click', function () {
    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_settings_export');
    fd.append('pn_customers_manager_ajax_nonce', pnCustomersManagerSettingsFooter.nonce);

    fetch(pnCustomersManagerSettingsFooter.ajaxUrl, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          if (typeof pn_customers_manager_get_main_message === 'function') {
            pn_customers_manager_get_main_message(pnCustomersManagerSettingsFooter.i18n.exportError, 'red');
          }
          return;
        }

        var blob = new Blob([JSON.stringify(res.settings, null, 2)], { type: 'application/json' });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href     = url;
        a.download = 'pn-customers-manager-settings-' + new Date().toISOString().slice(0, 10) + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      })
      .catch(function () {
        if (typeof pn_customers_manager_get_main_message === 'function') {
          pn_customers_manager_get_main_message(pnCustomersManagerSettingsFooter.i18n.exportError, 'red');
        }
      });
  });

  // Import — open file picker
  importBtn.addEventListener('click', function () {
    fileInput.value = '';
    fileInput.click();
  });

  fileInput.addEventListener('change', function () {
    var file = fileInput.files[0];
    if (!file) return;

    var reader = new FileReader();
    reader.onload = function (e) {
      var data;
      try {
        data = JSON.parse(e.target.result);
      } catch (err) {
        if (typeof pn_customers_manager_get_main_message === 'function') {
          pn_customers_manager_get_main_message(pnCustomersManagerSettingsFooter.i18n.invalidFile, 'red');
        }
        return;
      }

      if (!confirm(pnCustomersManagerSettingsFooter.i18n.confirmImport)) return;

      var fd = new FormData();
      fd.append('action', 'pn_customers_manager_ajax');
      fd.append('pn_customers_manager_ajax_type', 'pn_cm_settings_import');
      fd.append('pn_customers_manager_ajax_nonce', pnCustomersManagerSettingsFooter.nonce);
      fd.append('settings', JSON.stringify(data));

      fetch(pnCustomersManagerSettingsFooter.ajaxUrl, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.error_key) {
            if (typeof pn_customers_manager_get_main_message === 'function') {
              pn_customers_manager_get_main_message(res.error_content || pnCustomersManagerSettingsFooter.i18n.importError, 'red');
            }
            return;
          }

          if (typeof pn_customers_manager_get_main_message === 'function') {
            pn_customers_manager_get_main_message(pnCustomersManagerSettingsFooter.i18n.importSuccess, 'green');
          }
          setTimeout(function () { location.reload(); }, 1500);
        })
        .catch(function () {
          if (typeof pn_customers_manager_get_main_message === 'function') {
            pn_customers_manager_get_main_message(pnCustomersManagerSettingsFooter.i18n.importError, 'red');
          }
        });
    };
    reader.readAsText(file);
  });

  // ── Recommended plugins ──────────────────────────────────────

  var rpBtn   = document.getElementById('pn-customers-manager-settings-recommended');
  var rpPopup = document.getElementById('pn-customers-manager-recommended-plugins');

  if (rpBtn && rpPopup) {
    // Open popup
    rpBtn.addEventListener('click', function () {
      if (window.pn_customers_manager_Popups) {
        pn_customers_manager_Popups.open('pn-customers-manager-recommended-plugins');
      }
    });

    // Event delegation for install / activate buttons inside the popup
    rpPopup.addEventListener('click', function (e) {
      var installBtn  = e.target.closest('.pn-cm-rp-install');
      var activateBtn = e.target.closest('.pn-cm-rp-activate');

      if (installBtn)  handleRpInstall(installBtn);
      if (activateBtn) handleRpActivate(activateBtn);
    });
  }

  function handleRpInstall(btn) {
    var slug      = btn.getAttribute('data-slug');
    var card      = btn.closest('.pn-cm-rp-card');
    var actionDiv = card.querySelector('.pn-cm-rp-action');
    var i18n      = pnCustomersManagerSettingsFooter.i18n;

    btn.disabled    = true;
    btn.textContent = i18n.installing;

    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_install_plugin');
    fd.append('pn_customers_manager_ajax_nonce', pnCustomersManagerSettingsFooter.nonce);
    fd.append('slug', slug);

    fetch(pnCustomersManagerSettingsFooter.ajaxUrl, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          btn.disabled    = false;
          btn.textContent = 'Install';
          if (typeof pn_customers_manager_get_main_message === 'function') {
            pn_customers_manager_get_main_message(res.error_content || i18n.installError, 'red');
          }
          return;
        }

        // Replace with Activate button
        actionDiv.innerHTML = '<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent pn-cm-rp-activate" data-slug="' + slug + '">' + i18n.activate + '</button>';

        // Update badge
        updateRpBadge(-1);
      })
      .catch(function () {
        btn.disabled    = false;
        btn.textContent = 'Install';
        if (typeof pn_customers_manager_get_main_message === 'function') {
          pn_customers_manager_get_main_message(i18n.installError, 'red');
        }
      });
  }

  function handleRpActivate(btn) {
    var slug      = btn.getAttribute('data-slug');
    var card      = btn.closest('.pn-cm-rp-card');
    var actionDiv = card.querySelector('.pn-cm-rp-action');
    var i18n      = pnCustomersManagerSettingsFooter.i18n;

    btn.disabled    = true;
    btn.textContent = i18n.activating;

    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_ajax');
    fd.append('pn_customers_manager_ajax_type', 'pn_cm_activate_plugin');
    fd.append('pn_customers_manager_ajax_nonce', pnCustomersManagerSettingsFooter.nonce);
    fd.append('slug', slug);

    fetch(pnCustomersManagerSettingsFooter.ajaxUrl, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          btn.disabled    = false;
          btn.textContent = i18n.activate;
          if (typeof pn_customers_manager_get_main_message === 'function') {
            pn_customers_manager_get_main_message(res.error_content || i18n.activateError, 'red');
          }
          return;
        }

        // Show Active badge
        actionDiv.innerHTML = '<span class="pn-cm-rp-active-badge">' + i18n.active + '</span>';

        // Open settings page in new tab
        var settingsUrl = (pnCustomersManagerSettingsFooter.settingsPages || {})[slug];
        if (settingsUrl) {
          window.open(settingsUrl, '_blank');
        }
      })
      .catch(function () {
        btn.disabled    = false;
        btn.textContent = i18n.activate;
        if (typeof pn_customers_manager_get_main_message === 'function') {
          pn_customers_manager_get_main_message(i18n.activateError, 'red');
        }
      });
  }

  function updateRpBadge(delta) {
    var badge = document.querySelector('.pn-cm-rp-badge');
    if (!badge) return;
    var count = parseInt(badge.textContent, 10) + delta;
    if (count <= 0) {
      badge.remove();
    } else {
      badge.textContent = count;
    }
  }

  // Accounting Summary Test Email
  var accountingTestBtn = document.getElementById('pn-cm-accounting-test-btn');
  if (accountingTestBtn) {
    accountingTestBtn.addEventListener('click', function () {
      var resultDiv = document.getElementById('pn-cm-accounting-test-result');
      accountingTestBtn.disabled = true;
      accountingTestBtn.textContent = 'Enviando...';

      var fd = new FormData();
      fd.append('action', 'pn_customers_manager_ajax');
      fd.append('pn_customers_manager_ajax_type', 'test_accounting_email');
      fd.append('pn_customers_manager_ajax_nonce', pnCustomersManagerSettingsFooter.nonce);

      fetch(pnCustomersManagerSettingsFooter.ajaxUrl, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            resultDiv.innerHTML = '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-top: 10px;">' +
              '<strong>✓ ' + (data.data.message || 'Email enviado correctamente') + '</strong>' +
              '</div>';
          } else {
            resultDiv.innerHTML = '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-top: 10px;">' +
              '❌ ' + (data.data.message || 'Error al enviar el email') +
              '</div>';
          }
          accountingTestBtn.disabled = false;
          accountingTestBtn.textContent = 'Send Test Email';
        })
        .catch(function () {
          resultDiv.innerHTML = '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-top: 10px;">❌ Error de red</div>';
          accountingTestBtn.disabled = false;
          accountingTestBtn.textContent = 'Send Test Email';
        });
    });
  }
})();
