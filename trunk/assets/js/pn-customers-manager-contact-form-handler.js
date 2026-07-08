(function() {
  'use strict';

  var i18n = window.pnCmContactForm || {};

  document.querySelectorAll('.pn-customers-manager-contact-form').forEach(function(form) {
    var ajaxUrl = form.getAttribute('data-ajax-url');
    var nonce   = form.getAttribute('data-nonce');

    if (!ajaxUrl || !nonce) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var feedback = form.querySelector('.pn-customers-manager-cf-feedback');
      var btn      = form.querySelector('button[type="submit"]');

      var name    = form.querySelector('[name="contact_name"]').value.trim();
      var email   = form.querySelector('[name="contact_email"]').value.trim();
      var message = form.querySelector('[name="contact_message"]').value.trim();

      if (!name || !email || !message) {
        feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-error';
        feedback.textContent = i18n.requiredFields || 'Por favor, completa todos los campos obligatorios.';
        return;
      }

      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-error';
        feedback.textContent = i18n.invalidEmail || 'Introduce un correo electr\u00f3nico v\u00e1lido.';
        return;
      }

      btn.disabled = true;
      feedback.className = 'pn-customers-manager-cf-feedback';
      feedback.textContent = i18n.sending || 'Enviando...';

      var fd = new FormData(form);
      fd.append('action', 'pn_customers_manager_ajax_nopriv');
      fd.append('pn_customers_manager_ajax_nopriv_type', 'pn_cm_contact_send');
      fd.append('pn_customers_manager_ajax_nopriv_nonce', nonce);
      fd.append('contact_source_url', window.location.href);
      fd.append('contact_source_title', document.title);

      fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        btn.disabled = false;
        if (!data.error_key || data.error_key === '') {
          feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-success';
          feedback.textContent = i18n.success || 'Mensaje enviado correctamente. Gracias por contactarnos.';
          form.reset();
        } else {
          feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-error';
          feedback.textContent = data.error_content || i18n.genericError || 'Ha ocurrido un error. Int\u00e9ntalo de nuevo.';
        }
      })
      .catch(function() {
        btn.disabled = false;
        feedback.className = 'pn-customers-manager-cf-feedback pn-customers-manager-cf-error';
        feedback.textContent = i18n.connectionError || 'Error de conexi\u00f3n. Int\u00e9ntalo de nuevo.';
      });
    });
  });
})();
