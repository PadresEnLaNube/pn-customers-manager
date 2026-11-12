/**
 * API test buttons for Settings page.
 *
 * Handles OpenAI and WhatsApp test button clicks.
 *
 * @since 1.0.42
 */
(function () {
  'use strict';

  var cfg  = window.pnCmApiTests || {};
  var i18n = cfg.i18n || {};

  function init() {
    var openaiBtn  = document.getElementById('pn-cm-openai-test-btn');
    var waBtn      = document.getElementById('pn-cm-whatsapp-test-btn');
    var waRecvBtn  = document.getElementById('pn-cm-whatsapp-receive-btn');
    var waRecvStop = document.getElementById('pn-cm-whatsapp-receive-stop-btn');
    var igBtn      = document.getElementById('pn-cm-instagram-test-btn');
    var igRecvBtn  = document.getElementById('pn-cm-instagram-receive-btn');
    var igRecvStop = document.getElementById('pn-cm-instagram-receive-stop-btn');
    var socialBtn  = document.getElementById('pn-cm-social-media-test-btn');

    if (openaiBtn) {
      openaiBtn.addEventListener('click', testOpenAI);
    }
    if (waBtn) {
      waBtn.addEventListener('click', testWhatsApp);
    }
    if (waRecvBtn) {
      waRecvBtn.addEventListener('click', startWebhookListen);
    }
    if (waRecvStop) {
      waRecvStop.addEventListener('click', stopWebhookListen);
    }
    if (igBtn) {
      igBtn.addEventListener('click', testInstagram);
    }
    if (igRecvBtn) {
      igRecvBtn.addEventListener('click', startIgWebhookListen);
    }
    if (igRecvStop) {
      igRecvStop.addEventListener('click', stopIgWebhookListen);
    }
    if (socialBtn) {
      socialBtn.addEventListener('click', testSocialMedia);
    }

    var ghlTestBtn = document.getElementById('pn-cm-ghl-test-btn');
    var ghlPushBtn = document.getElementById('pn-cm-ghl-push-btn');
    var ghlPullBtn = document.getElementById('pn-cm-ghl-pull-btn');

    if (ghlTestBtn) {
      ghlTestBtn.addEventListener('click', testGHL);
    }
    if (ghlPushBtn) {
      ghlPushBtn.addEventListener('click', pushGHL);
    }
    if (ghlPullBtn) {
      ghlPullBtn.addEventListener('click', pullGHL);
    }

    var zohoTestBtn       = document.getElementById('pn-cm-zoho-test-btn');
    var zohoPushBtn       = document.getElementById('pn-cm-zoho-push-btn');
    var zohoPullBtn       = document.getElementById('pn-cm-zoho-pull-btn');
    var zohoDisconnectBtn = document.getElementById('pn-cm-zoho-disconnect-btn');

    if (zohoTestBtn) {
      zohoTestBtn.addEventListener('click', testZoho);
    }
    if (zohoPushBtn) {
      zohoPushBtn.addEventListener('click', pushZoho);
    }
    if (zohoPullBtn) {
      zohoPullBtn.addEventListener('click', pullZoho);
    }
    if (zohoDisconnectBtn) {
      zohoDisconnectBtn.addEventListener('click', disconnectZoho);
    }
  }

  function testOpenAI() {
    var btn    = document.getElementById('pn-cm-openai-test-btn');
    var result = document.getElementById('pn-cm-openai-test-result');

    btn.disabled = true;
    result.textContent = i18n.testing || 'Probando...';
    result.style.color = '#666';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_openai_test');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        if (res.error_key) {
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
        } else {
          result.textContent = res.message;
          result.style.color = '#00a32a';
        }
      })
      .catch(function () {
        btn.disabled = false;
        result.textContent = i18n.error || 'Error';
        result.style.color = '#d63638';
      });
  }

  function testWhatsApp() {
    var btn       = document.getElementById('pn-cm-whatsapp-test-btn');
    var result    = document.getElementById('pn-cm-whatsapp-test-result');
    var phoneInput = document.getElementById('pn_customers_manager_whatsapp_test_phone');

    var phone = phoneInput ? phoneInput.value.trim() : '';

    if (!phone) {
      result.textContent = i18n.noPhone || 'Introduce un numero de telefono de prueba.';
      result.style.color = '#d63638';
      return;
    }

    btn.disabled = true;
    result.textContent = i18n.testing || 'Probando...';
    result.style.color = '#666';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_whatsapp_test');
    formData.append('test_phone', phone);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        if (res.error_key) {
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
        } else {
          result.textContent = res.message;
          result.style.color = '#00a32a';
        }
      })
      .catch(function () {
        btn.disabled = false;
        result.textContent = i18n.waError || 'Error al enviar el mensaje.';
        result.style.color = '#d63638';
      });
  }

  /* ======== Webhook reception test (polling) ======== */
  var listenTimer = null;
  var listenSince = '';
  var listenCount = 0;
  var LISTEN_INTERVAL = 3000; // 3 seconds
  var LISTEN_MAX_POLLS = 40;  // ~2 minutes

  function startWebhookListen() {
    var btn     = document.getElementById('pn-cm-whatsapp-receive-btn');
    var stopBtn = document.getElementById('pn-cm-whatsapp-receive-stop-btn');
    var result  = document.getElementById('pn-cm-whatsapp-receive-result');

    btn.disabled = true;
    btn.style.display = 'none';
    stopBtn.style.display = 'inline-block';
    result.textContent = i18n.listening || 'Escuchando... Envia un mensaje de WhatsApp a tu numero de empresa.';
    result.style.color = '#2271b1';
    listenCount = 0;

    // Get current server time to use as "since" baseline
    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_whatsapp_test_receive');
    formData.append('since', 'start');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        listenSince = res.server_time;
        listenTimer = setInterval(pollWebhookMessage, LISTEN_INTERVAL);
      })
      .catch(function () {
        stopWebhookListen();
        result.textContent = i18n.error || 'Error';
        result.style.color = '#d63638';
      });
  }

  function stopWebhookListen() {
    var btn     = document.getElementById('pn-cm-whatsapp-receive-btn');
    var stopBtn = document.getElementById('pn-cm-whatsapp-receive-stop-btn');
    var result  = document.getElementById('pn-cm-whatsapp-receive-result');

    if (listenTimer) {
      clearInterval(listenTimer);
      listenTimer = null;
    }

    btn.disabled = false;
    btn.style.display = 'inline-block';
    stopBtn.style.display = 'none';

    if (result.style.color !== 'rgb(0, 163, 42)') { // not already success (green)
      result.textContent = i18n.listenStopped || 'Escucha detenida.';
      result.style.color = '#666';
    }
  }

  function pollWebhookMessage() {
    var result = document.getElementById('pn-cm-whatsapp-receive-result');

    listenCount++;
    if (listenCount > LISTEN_MAX_POLLS) {
      stopWebhookListen();
      result.textContent = i18n.listenTimeout || 'Tiempo de espera agotado. No se recibio ningun mensaje.';
      result.style.color = '#d63638';
      return;
    }

    // Show animated dots
    var dots = '.'.repeat((listenCount % 3) + 1);
    result.textContent = (i18n.listening || 'Escuchando') + dots + ' (' + listenCount * 3 + 's)';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_whatsapp_test_receive');
    formData.append('since', listenSince);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          stopWebhookListen();
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
          return;
        }

        if (res.found) {
          stopWebhookListen();
          var info = (res.name ? res.name + ' (' + res.phone + ')' : res.phone);
          result.textContent = (i18n.messageReceived || 'Mensaje recibido de') +
            ' ' + info + ': "' + res.message + '"';
          result.style.color = '#00a32a';
        }
      })
      .catch(function () {
        // Ignore individual poll errors, keep trying
      });
  }

  /* ======== Instagram test send ======== */
  function testInstagram() {
    var btn    = document.getElementById('pn-cm-instagram-test-btn');
    var result = document.getElementById('pn-cm-instagram-test-result');
    var igIdInput = document.getElementById('pn_customers_manager_instagram_test_ig_id');

    var igId = igIdInput ? igIdInput.value.trim() : '';

    if (!igId) {
      result.textContent = i18n.noIgId || 'Enter a test Instagram User ID.';
      result.style.color = '#d63638';
      return;
    }

    btn.disabled = true;
    result.textContent = i18n.testing || 'Testing...';
    result.style.color = '#666';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_instagram_test');
    formData.append('test_ig_id', igId);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        if (res.error_key) {
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
        } else {
          result.textContent = res.message;
          result.style.color = '#00a32a';
        }
      })
      .catch(function () {
        btn.disabled = false;
        result.textContent = i18n.igError || 'Error sending Instagram message.';
        result.style.color = '#d63638';
      });
  }

  /* ======== Social Media APIs test ======== */
  function testSocialMedia() {
    var btn    = document.getElementById('pn-cm-social-media-test-btn');
    var result = document.getElementById('pn-cm-social-media-test-result');

    btn.disabled = true;
    result.textContent = i18n.testing || 'Testing...';
    result.style.color = '#666';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_social_media_test');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        if (res.error_key === '' && res.results) {
          var parts = [];
          for (var platform in res.results) {
            if (res.results.hasOwnProperty(platform)) {
              var info = res.results[platform];
              var icon = info.status === 'ok' ? '\u2713' : '\u2717';
              parts.push(icon + ' ' + platform + ': ' + info.message);
            }
          }
          result.innerHTML = parts.join('<br>');
          result.style.color = '#333';
        } else {
          result.textContent = i18n.error || 'Error';
          result.style.color = '#d63638';
        }
      })
      .catch(function () {
        btn.disabled = false;
        result.textContent = i18n.error || 'Error';
        result.style.color = '#d63638';
      });
  }

  /* ======== Instagram webhook reception test (polling) ======== */
  var igListenTimer = null;
  var igListenSince = '';
  var igListenCount = 0;

  function startIgWebhookListen() {
    var btn     = document.getElementById('pn-cm-instagram-receive-btn');
    var stopBtn = document.getElementById('pn-cm-instagram-receive-stop-btn');
    var result  = document.getElementById('pn-cm-instagram-receive-result');

    btn.disabled = true;
    btn.style.display = 'none';
    stopBtn.style.display = 'inline-block';
    result.textContent = i18n.igListening || 'Listening for Instagram DMs... Send a DM to your Instagram account.';
    result.style.color = '#2271b1';
    igListenCount = 0;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_instagram_test_receive');
    formData.append('since', 'start');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        igListenSince = res.server_time;
        igListenTimer = setInterval(pollIgWebhookMessage, LISTEN_INTERVAL);
      })
      .catch(function () {
        stopIgWebhookListen();
        result.textContent = i18n.error || 'Error';
        result.style.color = '#d63638';
      });
  }

  function stopIgWebhookListen() {
    var btn     = document.getElementById('pn-cm-instagram-receive-btn');
    var stopBtn = document.getElementById('pn-cm-instagram-receive-stop-btn');
    var result  = document.getElementById('pn-cm-instagram-receive-result');

    if (igListenTimer) {
      clearInterval(igListenTimer);
      igListenTimer = null;
    }

    btn.disabled = false;
    btn.style.display = 'inline-block';
    stopBtn.style.display = 'none';

    if (result.style.color !== 'rgb(0, 163, 42)') {
      result.textContent = i18n.listenStopped || 'Listening stopped.';
      result.style.color = '#666';
    }
  }

  function pollIgWebhookMessage() {
    var result = document.getElementById('pn-cm-instagram-receive-result');

    igListenCount++;
    if (igListenCount > LISTEN_MAX_POLLS) {
      stopIgWebhookListen();
      result.textContent = i18n.listenTimeout || 'Timeout. No messages received.';
      result.style.color = '#d63638';
      return;
    }

    var dots = '.'.repeat((igListenCount % 3) + 1);
    result.textContent = (i18n.igListening || 'Listening for Instagram DMs') + dots + ' (' + igListenCount * 3 + 's)';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_instagram_test_receive');
    formData.append('since', igListenSince);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          stopIgWebhookListen();
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
          return;
        }

        if (res.found) {
          stopIgWebhookListen();
          var info = (res.name ? res.name + ' (' + res.ig_user + ')' : res.ig_user);
          result.textContent = (i18n.igMessageReceived || 'Instagram DM received from') +
            ' ' + info + ': "' + res.message + '"';
          result.style.color = '#00a32a';
        }
      })
      .catch(function () {
        // Ignore individual poll errors
      });
  }

  /* ======== Go High Level ======== */
  function testGHL() {
    var btn    = document.getElementById('pn-cm-ghl-test-btn');
    var result = document.getElementById('pn-cm-ghl-test-result');

    btn.disabled = true;
    result.textContent = i18n.testing || 'Testing...';
    result.style.color = '#666';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_ghl_test');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) {
        if (!r.ok) {
          return r.text().then(function (txt) {
            throw new Error('HTTP ' + r.status + ': ' + txt.substring(0, 300));
          });
        }
        return r.json();
      })
      .then(function (res) {
        btn.disabled = false;
        if (res.error_key) {
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
        } else {
          result.textContent = res.message;
          result.style.color = '#00a32a';
        }
      })
      .catch(function (err) {
        btn.disabled = false;
        result.textContent = (i18n.error || 'Error') + ': ' + err.message;
        result.style.color = '#d63638';
      });
  }

  function pushGHL() {
    var btn    = document.getElementById('pn-cm-ghl-push-btn');
    var result = document.getElementById('pn-cm-ghl-push-result');

    btn.disabled = true;
    result.textContent = i18n.syncing || 'Syncing...';
    result.style.color = '#666';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_ghl_push_all');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) {
        if (!r.ok) {
          return r.text().then(function (txt) {
            throw new Error('HTTP ' + r.status + ': ' + txt.substring(0, 300));
          });
        }
        return r.json();
      })
      .then(function (res) {
        btn.disabled = false;
        if (res.error_key) {
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
        } else {
          result.textContent = res.message;
          result.style.color = '#00a32a';
        }
      })
      .catch(function (err) {
        btn.disabled = false;
        result.textContent = (i18n.error || 'Error') + ': ' + err.message;
        result.style.color = '#d63638';
      });
  }

  function pullGHL() {
    var btn    = document.getElementById('pn-cm-ghl-pull-btn');
    var result = document.getElementById('pn-cm-ghl-pull-result');

    btn.disabled = true;

    var totals = { orgs_created: 0, orgs_updated: 0, contacts_created: 0, contacts_updated: 0, errors: 0 };
    var batchNum = 0;

    function fetchPage(cursor) {
      batchNum++;
      result.textContent = (i18n.syncing || 'Syncing...') + ' (' + (i18n.batch || 'batch') + ' ' + batchNum + ')';
      result.style.color = '#666';

      var formData = new FormData();
      formData.append('action', 'pn_customers_manager_ajax');
      formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      formData.append('pn_customers_manager_ajax_type', 'pn_cm_ghl_pull_all');
      if (cursor) {
        formData.append('pull_cursor', cursor);
      }

      fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (r) {
          if (!r.ok) {
            return r.text().then(function (txt) {
              throw new Error('HTTP ' + r.status + ': ' + txt.substring(0, 300));
            });
          }
          return r.json();
        })
        .then(function (res) {
          if (res.error_key) {
            btn.disabled = false;
            result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
            result.style.color = '#d63638';
            return;
          }

          var s = res.summary || {};
          totals.orgs_created     += s.orgs_created || 0;
          totals.orgs_updated     += s.orgs_updated || 0;
          totals.contacts_created += s.contacts_created || 0;
          totals.contacts_updated += s.contacts_updated || 0;
          totals.errors           += s.errors || 0;

          if (res.has_more && res.cursor) {
            fetchPage(res.cursor);
          } else {
            btn.disabled = false;
            result.textContent = (i18n.pullComplete || 'Pull complete.') +
              ' Orgs: ' + totals.orgs_created + ' created, ' + totals.orgs_updated + ' updated.' +
              ' Contacts: ' + totals.contacts_created + ' created, ' + totals.contacts_updated + ' updated.' +
              ' Errors: ' + totals.errors + '.';
            result.style.color = totals.errors ? '#dba617' : '#00a32a';
          }
        })
        .catch(function (err) {
          btn.disabled = false;
          result.textContent = (i18n.error || 'Error') + ': ' + err.message;
          result.style.color = '#d63638';
        });
    }

    fetchPage('');
  }

  /* ======== Zoho CRM ======== */
  function testZoho() {
    var btn    = document.getElementById('pn-cm-zoho-test-btn');
    var result = document.getElementById('pn-cm-zoho-test-result');

    btn.disabled = true;
    result.textContent = i18n.testing || 'Testing...';
    result.style.color = '#666';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_zoho_test');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) {
        if (!r.ok) {
          return r.text().then(function (txt) {
            throw new Error('HTTP ' + r.status + ': ' + txt.substring(0, 300));
          });
        }
        return r.json();
      })
      .then(function (res) {
        btn.disabled = false;
        if (res.error_key) {
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
        } else {
          result.textContent = res.message;
          result.style.color = '#00a32a';
        }
      })
      .catch(function (err) {
        btn.disabled = false;
        result.textContent = (i18n.error || 'Error') + ': ' + err.message;
        result.style.color = '#d63638';
      });
  }

  function pushZoho() {
    var btn    = document.getElementById('pn-cm-zoho-push-btn');
    var result = document.getElementById('pn-cm-zoho-push-result');

    btn.disabled = true;
    result.textContent = i18n.syncing || 'Syncing...';
    result.style.color = '#666';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_zoho_push_all');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) {
        if (!r.ok) {
          return r.text().then(function (txt) {
            throw new Error('HTTP ' + r.status + ': ' + txt.substring(0, 300));
          });
        }
        return r.json();
      })
      .then(function (res) {
        btn.disabled = false;
        if (res.error_key) {
          result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
          result.style.color = '#d63638';
        } else {
          result.textContent = res.message;
          result.style.color = '#00a32a';
        }
      })
      .catch(function (err) {
        btn.disabled = false;
        result.textContent = (i18n.error || 'Error') + ': ' + err.message;
        result.style.color = '#d63638';
      });
  }

  function pullZoho() {
    var btn    = document.getElementById('pn-cm-zoho-pull-btn');
    var result = document.getElementById('pn-cm-zoho-pull-result');

    btn.disabled = true;

    var totals = { orgs_created: 0, orgs_updated: 0, contacts_created: 0, contacts_updated: 0, errors: 0 };
    var batchNum = 0;

    function fetchPage(page) {
      batchNum++;
      result.textContent = (i18n.syncing || 'Syncing...') + ' (' + (i18n.batch || 'batch') + ' ' + batchNum + ')';
      result.style.color = '#666';

      var formData = new FormData();
      formData.append('action', 'pn_customers_manager_ajax');
      formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
      formData.append('pn_customers_manager_ajax_type', 'pn_cm_zoho_pull_all');
      formData.append('pull_page', page);

      fetch(cfg.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (r) {
          if (!r.ok) {
            return r.text().then(function (txt) {
              throw new Error('HTTP ' + r.status + ': ' + txt.substring(0, 300));
            });
          }
          return r.json();
        })
        .then(function (res) {
          if (res.error_key) {
            btn.disabled = false;
            result.textContent = (i18n.error || 'Error:') + ' ' + res.error_content;
            result.style.color = '#d63638';
            return;
          }

          var s = res.summary || {};
          totals.orgs_created     += s.orgs_created || 0;
          totals.orgs_updated     += s.orgs_updated || 0;
          totals.contacts_created += s.contacts_created || 0;
          totals.contacts_updated += s.contacts_updated || 0;
          totals.errors           += s.errors || 0;

          if (res.has_more) {
            fetchPage(page + 1);
          } else {
            btn.disabled = false;
            result.textContent = (i18n.pullComplete || 'Pull complete.') +
              ' Orgs: ' + totals.orgs_created + ' created, ' + totals.orgs_updated + ' updated.' +
              ' Contacts: ' + totals.contacts_created + ' created, ' + totals.contacts_updated + ' updated.' +
              ' Errors: ' + totals.errors + '.';
            result.style.color = totals.errors ? '#dba617' : '#00a32a';
          }
        })
        .catch(function (err) {
          btn.disabled = false;
          result.textContent = (i18n.error || 'Error') + ': ' + err.message;
          result.style.color = '#d63638';
        });
    }

    fetchPage(1);
  }

  function disconnectZoho() {
    if (!confirm(i18n.confirmDisconnect || 'Are you sure you want to disconnect Zoho CRM?')) {
      return;
    }

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_zoho_disconnect');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function () {
        location.reload();
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
