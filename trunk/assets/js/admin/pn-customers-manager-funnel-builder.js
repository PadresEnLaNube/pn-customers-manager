/**
 * Funnel Builder – visual drag-and-drop canvas.
 *
 * Reads config from window.pnCmFunnelBuilder (ajaxUrl, nonce, funnelId, canvasData, i18n).
 *
 * @since 1.0.41
 */
(function () {
  'use strict';

  /* -------- Config -------- */
  var cfg = window.pnCmFunnelBuilder || {};
  var i18n = cfg.i18n || {};
  var apiStatus = cfg.apiStatus || {};
  var apiLinks = cfg.apiLinks || {};

  /* -------- API requirements per subtype -------- */
  var API_REQUIREMENTS = {
    whatsapp:      ['whatsapp'],
    whatsapp_ai:   ['whatsapp', 'openai'],
    instagram_ai:  ['instagram', 'openai'],
    chatbot:       ['openai'],
  };

  /* -------- State -------- */
  var state = {
    funnelId: parseInt(cfg.funnelId, 10) || 0,
    nodes: [],
    connections: [],
    nextId: 1,
    selectedNode: null,
    dirty: false,
  };

  /* -------- Zoom / pan state -------- */
  var zoom = 1;
  var panX = 0;
  var panY = 0;
  var isPanning = false;
  var panStartX = 0;
  var panStartY = 0;
  var panOrigX = 0;
  var panOrigY = 0;
  var MIN_ZOOM = 0.2;
  var MAX_ZOOM = 3;
  var ZOOM_STEP = 0.1;

  /* -------- DOM refs -------- */
  var canvas, canvasWrap, svg, propsPanel, propsEmpty;
  var funnelSelect, btnSave, btnDeleteNode, btnDeleteFunnel, btnCreate, inputNewFunnelName;
  var propLabel;
  var waChatBtnWrap, waChatBtn;
  var igChatBtnWrap, igChatBtn;
  var btnSettings;
  var zoomLayer, zoomLevelEl, sidebarLeft, sidebarToggle;

  /* -------- Icon map -------- */
  var ICONS = {
    facebook_ads: 'campaign',
    google_ads: 'ads_click',
    email: 'email',
    social_organic: 'share',
    seo: 'search',
    opt_in: 'web',
    webinar: 'videocam',
    sales_page: 'storefront',
    squeeze: 'description',
    order_form: 'shopping_cart',
    upsell: 'trending_up',
    downsell: 'trending_down',
    thank_you: 'celebration',
    email_sequence: 'forward_to_inbox',
    retargeting: 'replay',
    automation: 'smart_toy',
    market: 'public',
    conversion: 'flag',
    whatsapp: 'chat',
    whatsapp_ai: 'psychology',
    instagram_ai: 'photo_camera',
    chatbot: 'forum',
    live_chat: 'support_agent',
    sms: 'sms',
    call: 'call',
    blog_post: 'article',
    video_content: 'play_circle',
    lead_magnet: 'card_giftcard',
    form: 'assignment',
    button: 'smart_button',
    cta: 'touch_app',
  };

  var TYPE_LABELS = {
    traffic_source: 'Tráfico',
    landing_page: 'Landing',
    checkout: 'Checkout',
    follow_up: 'Seguimiento',
    communication: 'Comunicación',
    content: 'Contenido',
    market: 'Mercado',
    conversion: 'Conversión',
  };

  /* ======== Init ======== */
  function init() {
    canvas = document.getElementById('pn-cm-fb-canvas');
    canvasWrap = document.getElementById('pn-cm-fb-canvas-wrap');
    svg = document.getElementById('pn-cm-fb-canvas-svg');
    propsPanel = document.getElementById('pn-cm-fb-props-panel');
    propsEmpty = document.querySelector('.pn-cm-fb-props-empty');
    funnelSelect = document.getElementById('pn-cm-fb-funnel-select');
    btnSave = document.getElementById('pn-cm-fb-btn-save');
    btnDeleteNode = document.getElementById('pn-cm-fb-btn-delete-node');
    btnDeleteFunnel = document.getElementById('pn-cm-fb-btn-delete');
    btnCreate = document.getElementById('pn-cm-fb-btn-create');
    inputNewFunnelName = document.getElementById('pn-cm-fb-new-funnel-name');
    propLabel = document.getElementById('pn-cm-fb-prop-label');
    waChatBtnWrap = document.getElementById('pn-cm-fb-wa-chat-btn-wrap');
    waChatBtn = document.getElementById('pn-cm-fb-wa-chat-btn');
    igChatBtnWrap = document.getElementById('pn-cm-fb-ig-chat-btn-wrap');
    igChatBtn = document.getElementById('pn-cm-fb-ig-chat-btn');
    btnSettings = document.getElementById('pn-cm-fb-btn-settings');

    zoomLayer = document.getElementById('pn-cm-fb-zoom-layer');
    zoomLevelEl = document.getElementById('pn-cm-fb-zoom-level');
    sidebarLeft = document.getElementById('pn-cm-fb-sidebar-left');
    sidebarToggle = document.getElementById('pn-cm-fb-sidebar-toggle');

    if (!canvas || !canvasWrap || !svg || !zoomLayer || !funnelSelect || !btnSave || !btnDeleteNode || !propLabel) return;

    setupSvgDefs();
    bindEvents();
    bindPopupEvents();
    bindIgPopupEvents();

    if (state.funnelId && cfg.canvasData) {
      loadCanvasFromJSON(cfg.canvasData);
    } else if (state.funnelId) {
      initDefaultNodes();
    }
  }

  /* ======== SVG arrow markers ======== */
  function setupSvgDefs() {
    var ns = 'http://www.w3.org/2000/svg';
    var defs = document.createElementNS(ns, 'defs');

    // Normal arrowhead
    var marker = document.createElementNS(ns, 'marker');
    marker.setAttribute('id', 'pn-cm-fb-arrowhead');
    marker.setAttribute('markerWidth', '10');
    marker.setAttribute('markerHeight', '7');
    marker.setAttribute('refX', '10');
    marker.setAttribute('refY', '3.5');
    marker.setAttribute('orient', 'auto');
    var poly = document.createElementNS(ns, 'polygon');
    poly.setAttribute('points', '0 0, 10 3.5, 0 7');
    poly.setAttribute('fill', '#b0b0b0');
    marker.appendChild(poly);
    defs.appendChild(marker);

    // Active arrowhead
    var marker2 = document.createElementNS(ns, 'marker');
    marker2.setAttribute('id', 'pn-cm-fb-arrowhead-active');
    marker2.setAttribute('markerWidth', '10');
    marker2.setAttribute('markerHeight', '7');
    marker2.setAttribute('refX', '10');
    marker2.setAttribute('refY', '3.5');
    marker2.setAttribute('orient', 'auto');
    var poly2 = document.createElementNS(ns, 'polygon');
    poly2.setAttribute('points', '0 0, 10 3.5, 0 7');
    poly2.setAttribute('fill', '#4f46e5');
    marker2.appendChild(poly2);
    defs.appendChild(marker2);

    svg.appendChild(defs);
  }

  /* ======== Events ======== */
  function bindEvents() {
    // Funnel selector
    funnelSelect.addEventListener('change', onFunnelChange);

    // Save button
    btnSave.addEventListener('click', saveCanvas);

    // Delete funnel button
    if (btnDeleteFunnel) {
      btnDeleteFunnel.addEventListener('click', deleteFunnel);
    }

    // Create funnel button
    if (btnCreate) {
      btnCreate.addEventListener('click', createNewFunnel);
    }

    // Allow Enter key in funnel name input
    if (inputNewFunnelName) {
      inputNewFunnelName.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
          createNewFunnel();
        }
      });
    }

    // Delete node
    btnDeleteNode.addEventListener('click', deleteSelectedNode);

    // Property inputs
    propLabel.addEventListener('input', onPropChange);

    // Settings popup
    if (btnSettings) btnSettings.addEventListener('click', openSettingsPopup);
    var settingsCloseBtn = document.getElementById('pn-cm-fb-settings-popup-close');
    if (settingsCloseBtn) {
      settingsCloseBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (typeof window.pn_customers_manager_Popups !== 'undefined') {
          window.pn_customers_manager_Popups.close();
        }
      });
    }

    // Drag-and-drop from sidebar
    canvasWrap.addEventListener('dragover', onCanvasDragOver);
    canvasWrap.addEventListener('dragleave', onCanvasDragLeave);
    canvasWrap.addEventListener('drop', onCanvasDrop);

    // ── Pan: mousedown on empty canvas area ──
    canvasWrap.addEventListener('mousedown', function (e) {
      // Ignore clicks on UI controls inside canvasWrap
      if (e.target.closest('.pn-cm-fb-zoom-controls') || e.target.closest('.pn-cm-fb-sidebar-toggle')) return;
      // Only start pan if clicking on empty canvas / zoom layer / svg
      var onEmpty = (e.target === canvas || e.target === canvasWrap || e.target === zoomLayer || e.target === svg);
      if (!onEmpty) return;
      if (e.button !== 0) return;

      isPanning = true;
      panStartX = e.clientX;
      panStartY = e.clientY;
      panOrigX = panX;
      panOrigY = panY;
      canvasWrap.classList.add('pn-cm-fb-panning');
      e.preventDefault();
    });

    document.addEventListener('mousemove', function (e) {
      if (!isPanning) return;
      panX = panOrigX + (e.clientX - panStartX);
      panY = panOrigY + (e.clientY - panStartY);
      applyTransform();
    });

    document.addEventListener('mouseup', function (e) {
      if (!isPanning) return;
      var moved = Math.abs(e.clientX - panStartX) + Math.abs(e.clientY - panStartY);
      isPanning = false;
      canvasWrap.classList.remove('pn-cm-fb-panning');
      // If barely moved, treat as click → deselect
      if (moved < 5) {
        selectNode(null);
      }
    });

    // ── Touch pan support ──
    var touchId = null;
    var touchStartX = 0;
    var touchStartY = 0;
    var touchOrigPanX = 0;
    var touchOrigPanY = 0;

    canvasWrap.addEventListener('touchstart', function (e) {
      if (e.target.closest('.pn-cm-fb-zoom-controls') || e.target.closest('.pn-cm-fb-sidebar-toggle')) return;
      var onEmpty = (e.target === canvas || e.target === canvasWrap || e.target === zoomLayer || e.target === svg);
      if (!onEmpty || e.touches.length !== 1) return;
      var t = e.touches[0];
      touchId = t.identifier;
      touchStartX = t.clientX;
      touchStartY = t.clientY;
      touchOrigPanX = panX;
      touchOrigPanY = panY;
    }, { passive: true });

    canvasWrap.addEventListener('touchmove', function (e) {
      if (touchId === null) return;
      for (var i = 0; i < e.touches.length; i++) {
        if (e.touches[i].identifier === touchId) {
          panX = touchOrigPanX + (e.touches[i].clientX - touchStartX);
          panY = touchOrigPanY + (e.touches[i].clientY - touchStartY);
          applyTransform();
          e.preventDefault();
          return;
        }
      }
    }, { passive: false });

    canvasWrap.addEventListener('touchend', function (e) {
      for (var i = 0; i < e.changedTouches.length; i++) {
        if (e.changedTouches[i].identifier === touchId) {
          touchId = null;
          return;
        }
      }
    }, { passive: true });

    // ── Zoom: mouse wheel ──
    canvasWrap.addEventListener('wheel', function (e) {
      e.preventDefault();
      var delta = e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP;
      var newZoom = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, zoom + delta));
      if (newZoom === zoom) return;

      // Zoom centered on mouse position
      var rect = canvasWrap.getBoundingClientRect();
      var mx = e.clientX - rect.left;
      var my = e.clientY - rect.top;

      // Canvas point under mouse
      var cx = (mx - panX) / zoom;
      var cy = (my - panY) / zoom;

      zoom = newZoom;

      // Keep same canvas point under mouse
      panX = mx - cx * zoom;
      panY = my - cy * zoom;

      applyTransform();
      updateZoomDisplay();
    }, { passive: false });

    // ── Zoom buttons ──
    var btnZoomIn = document.getElementById('pn-cm-fb-zoom-in');
    var btnZoomOut = document.getElementById('pn-cm-fb-zoom-out');
    var btnZoomReset = document.getElementById('pn-cm-fb-zoom-reset');

    if (btnZoomIn) btnZoomIn.addEventListener('click', function () {
      zoomToCenter(Math.min(MAX_ZOOM, zoom + ZOOM_STEP));
    });
    if (btnZoomOut) btnZoomOut.addEventListener('click', function () {
      zoomToCenter(Math.max(MIN_ZOOM, zoom - ZOOM_STEP));
    });
    if (btnZoomReset) btnZoomReset.addEventListener('click', function () {
      zoom = 1;
      panX = 0;
      panY = 0;
      applyTransform();
      updateZoomDisplay();
    });

    // SVG click to delete connections
    svg.addEventListener('click', onSvgClick);

    // ── Sidebar toggle ──
    if (sidebarToggle && sidebarLeft) {
      sidebarToggle.addEventListener('click', toggleSidebar);
    }

    // ── Mobile: click sidebar element to add node ──
    if (sidebarLeft) {
      sidebarLeft.addEventListener('click', function (e) {
        var el = e.target.closest('.pn-cm-fb-element');
        if (!el) return;
        // Only use click-to-add on touch devices or when drag isn't practical
        if (!('ontouchstart' in window) && window.innerWidth > 768) return;

        if (!state.funnelId) {
          pn_customers_manager_get_main_message(i18n.noFunnel);
          return;
        }

        var emptyMsg = canvasWrap.querySelector('.pn-cm-fb-canvas-empty');
        if (emptyMsg) emptyMsg.remove();

        var type = el.getAttribute('data-type');
        var subtype = el.getAttribute('data-subtype');
        var label = el.querySelector('span:last-child').textContent.trim();

        // Place at center of visible viewport in canvas coordinates
        var wrapRect = canvasWrap.getBoundingClientRect();
        var cx = (wrapRect.width / 2 - panX) / zoom;
        var cy = (wrapRect.height / 2 - panY) / zoom;

        addNode({
          type: type,
          subtype: subtype,
          label: label,
          x: Math.max(0, snapToGrid(cx - 55)),
          y: Math.max(0, snapToGrid(cy - 30)),
          config: {},
        });
      });
    }

    // Unsaved changes warning
    window.addEventListener('beforeunload', function (e) {
      if (state.dirty) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
  }

  /* ======== Zoom / Pan helpers ======== */
  function applyTransform() {
    zoomLayer.style.transform = 'translate(' + panX + 'px,' + panY + 'px) scale(' + zoom + ')';
    // Sync background dots with zoom/pan
    var dotSize = GRID_SIZE * zoom;
    canvasWrap.style.backgroundSize = dotSize + 'px ' + dotSize + 'px';
    canvasWrap.style.backgroundPosition = panX + 'px ' + panY + 'px';
    redrawConnections();
  }

  function updateZoomDisplay() {
    if (zoomLevelEl) {
      zoomLevelEl.textContent = Math.round(zoom * 100) + '%';
    }
  }

  function zoomToCenter(newZoom) {
    var rect = canvasWrap.getBoundingClientRect();
    var mx = rect.width / 2;
    var my = rect.height / 2;
    var cx = (mx - panX) / zoom;
    var cy = (my - panY) / zoom;
    zoom = newZoom;
    panX = mx - cx * zoom;
    panY = my - cy * zoom;
    applyTransform();
    updateZoomDisplay();
  }

  /** Convert screen coordinates (clientX/Y) to canvas coordinates */
  function screenToCanvas(clientX, clientY) {
    var rect = canvasWrap.getBoundingClientRect();
    return {
      x: (clientX - rect.left - panX) / zoom,
      y: (clientY - rect.top - panY) / zoom,
    };
  }

  function toggleSidebar() {
    if (!sidebarLeft) return;
    var isSmall = window.innerWidth <= 600;
    if (isSmall) {
      sidebarLeft.classList.toggle('pn-cm-fb-sidebar-expanded');
    } else {
      sidebarLeft.classList.toggle('pn-cm-fb-sidebar-collapsed');
    }
  }

  /* ======== Funnel selection ======== */
  function onFunnelChange() {
    var newId = parseInt(funnelSelect.value, 10) || 0;

    if (state.dirty) {
      if (!confirm(i18n.unsavedChanges)) {
        funnelSelect.value = state.funnelId || '';
        return;
      }
    }

    state.funnelId = newId;
    state.dirty = false;
    btnSave.disabled = !newId;

    if (newId) {
      // Navigate to the URL with funnel_id
      window.location.href = cfg.adminUrl + '?page=pn_customers_manager_funnel_builder&funnel_id=' + newId;
    } else {
      clearCanvas();
      showEmptyCanvas();
    }
  }

  /* ======== Load/Save ======== */
  function loadCanvasFromJSON(jsonStr) {
    var data;
    try {
      data = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
    } catch (e) {
      initDefaultNodes();
      return;
    }

    if (!data || !data.nodes) {
      initDefaultNodes();
      return;
    }

    clearCanvas();

    state.nodes = data.nodes || [];
    state.connections = data.connections || [];
    state.nextId = data.nextId || 1;

    // Render all nodes
    state.nodes.forEach(function (node) {
      renderNode(node);
    });

    // Restore viewport state
    if (data.viewport) {
      zoom = data.viewport.zoom || 1;
      panX = data.viewport.panX || 0;
      panY = data.viewport.panY || 0;
      applyTransform();
      updateZoomDisplay();
    }

    // Render all connections
    redrawConnections();
  }

  function initDefaultNodes() {
    clearCanvas();

    var wrapRect = canvasWrap.getBoundingClientRect();
    var cx = wrapRect.width / 2;
    var cy = wrapRect.height / 2;

    // Market node
    addNode({
      id: 'market',
      type: 'market',
      subtype: 'market',
      label: i18n.market || 'Mercado',
      x: snapToGrid(80),
      y: snapToGrid(cy - 40),
      config: {},
    });

    // Conversion node
    addNode({
      id: 'conversion',
      type: 'conversion',
      subtype: 'conversion',
      label: i18n.conversion || 'Conversión',
      x: snapToGrid(wrapRect.width - 200),
      y: snapToGrid(cy - 40),
      config: {},
    });
  }

  function clearCanvas() {
    // Remove all node DOM elements
    var nodeEls = canvas.querySelectorAll('.pn-cm-fb-node');
    nodeEls.forEach(function (el) { el.remove(); });

    // Clear SVG lines
    var lines = svg.querySelectorAll('.pn-cm-fb-arrow');
    lines.forEach(function (el) { el.remove(); });

    // Remove empty message (lives in canvasWrap, not canvas)
    var emptyMsg = canvasWrap.querySelector('.pn-cm-fb-canvas-empty');
    if (emptyMsg) emptyMsg.remove();

    state.nodes = [];
    state.connections = [];
    state.nextId = 1;
    state.selectedNode = null;
    showPropsEmpty();
  }

  function showEmptyCanvas() {
    var div = document.createElement('div');
    div.className = 'pn-cm-fb-canvas-empty';
    div.innerHTML = '<span class="material-icons-outlined">account_tree</span><p>' +
      (i18n.noFunnel || 'Selecciona un funnel para empezar a diseñar') + '</p>';
    canvasWrap.appendChild(div);
  }

  function createNewFunnel() {
    if (!inputNewFunnelName) return;

    var funnelName = inputNewFunnelName.value.trim();
    if (!funnelName) {
      pn_customers_manager_get_main_message(i18n.funnelNameRequired || 'El nombre del funnel es obligatorio', 'error');
      inputNewFunnelName.focus();
      return;
    }

    // Disable button during creation
    if (btnCreate) btnCreate.disabled = true;
    if (inputNewFunnelName) inputNewFunnelName.disabled = true;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_funnel_builder_create');
    formData.append('funnel_name', funnelName);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (response) { return response.json(); })
      .then(function (res) {
        if (btnCreate) btnCreate.disabled = false;
        if (inputNewFunnelName) inputNewFunnelName.disabled = false;

        if (res.error_key) {
          pn_customers_manager_get_main_message(res.error_content || i18n.createError || 'Error al crear el funnel', 'error');
          return;
        }

        // Success - redirect to the new funnel
        if (res.funnel_url) {
          window.location.href = res.funnel_url;
        }
      })
      .catch(function (error) {
        if (btnCreate) btnCreate.disabled = false;
        if (inputNewFunnelName) inputNewFunnelName.disabled = false;
        pn_customers_manager_get_main_message(i18n.createError || 'Error al crear el funnel', 'error');
      });
  }

  function deleteFunnel() {
    if (!state.funnelId) return;

    // Confirm deletion
    if (!confirm(i18n.deleteFunnelConfirm || '¿Estás seguro de que quieres eliminar este funnel? Esta acción no se puede deshacer.')) {
      return;
    }

    // Disable button during deletion
    if (btnDeleteFunnel) btnDeleteFunnel.disabled = true;
    if (btnSave) btnSave.disabled = true;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_funnel_builder_delete');
    formData.append('funnel_id', state.funnelId);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (response) { return response.json(); })
      .then(function (res) {
        if (res.error_key) {
          if (btnDeleteFunnel) btnDeleteFunnel.disabled = false;
          if (btnSave) btnSave.disabled = false;
          pn_customers_manager_get_main_message(res.error_content || i18n.deleteError || 'Error al eliminar el funnel', 'error');
          return;
        }

        // Success - redirect to main funnel builder page
        pn_customers_manager_get_main_message(i18n.deleteSuccess || 'Funnel eliminado correctamente', 'success');
        state.dirty = false; // Prevent unsaved changes warning
        setTimeout(function () {
          window.location.href = window.location.pathname + '?page=pn_customers_manager_funnel_builder';
        }, 1000);
      })
      .catch(function (error) {
        if (btnDeleteFunnel) btnDeleteFunnel.disabled = false;
        if (btnSave) btnSave.disabled = false;
        pn_customers_manager_get_main_message(i18n.deleteError || 'Error al eliminar el funnel', 'error');
      });
  }

  function saveCanvas() {
    if (!state.funnelId) return;

    var data = {
      nodes: state.nodes,
      connections: state.connections,
      nextId: state.nextId,
      viewport: { zoom: zoom, panX: panX, panY: panY },
    };

    btnSave.disabled = true;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_funnel_builder_save');
    formData.append('pn_cm_funnel_id', state.funnelId);
    formData.append('canvas_data', JSON.stringify(data));

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btnSave.disabled = false;
        if (res.error_key) {
          pn_customers_manager_get_main_message(res.error_content || i18n.saveError);
        } else {
          state.dirty = false;
          pn_customers_manager_get_main_message(i18n.saved);
        }
      })
      .catch(function () {
        btnSave.disabled = false;
        pn_customers_manager_get_main_message(i18n.saveError);
      });
  }

  /* ======== Node CRUD ======== */
  function addNode(nodeData) {
    if (!nodeData.id) {
      nodeData.id = 'node_' + state.nextId++;
    }
    if (!nodeData.config) {
      nodeData.config = {};
    }

    state.nodes.push(nodeData);
    renderNode(nodeData);
    state.dirty = true;
  }

  function renderNode(node) {
    var el = document.createElement('div');
    el.className = 'pn-cm-fb-node';
    el.setAttribute('data-node-id', node.id);

    if (node.type === 'market') el.classList.add('pn-cm-fb-node-market');
    if (node.type === 'conversion') el.classList.add('pn-cm-fb-node-conversion');

    var icon = ICONS[node.subtype] || ICONS[node.type] || 'circle';
    var typeLabel = TYPE_LABELS[node.type] || node.type;

    var missing = getMissingApis(node.subtype);
    var warningHtml = '';
    if (missing.length) {
      warningHtml = '<span class="material-icons-outlined pn-cm-fb-node-warning" title="' +
        escapeHtml(i18n.apiNotConfigured || 'API no configurada') + '">warning</span>';
    }

    el.innerHTML =
      warningHtml +
      '<span class="material-icons-outlined pn-cm-fb-node-icon">' + icon + '</span>' +
      '<span class="pn-cm-fb-node-label">' + escapeHtml(node.label) + '</span>' +
      '<span class="pn-cm-fb-node-type">' + escapeHtml(typeLabel) + '</span>' +
      '<div class="pn-cm-fb-port pn-cm-fb-port-in" data-port="in"></div>' +
      '<div class="pn-cm-fb-port pn-cm-fb-port-out" data-port="out"></div>';

    el.style.left = node.x + 'px';
    el.style.top = node.y + 'px';

    // Drag to move
    setupNodeDrag(el, node);

    // Click to select
    el.addEventListener('mousedown', function (e) {
      if (e.target.classList.contains('pn-cm-fb-port')) return;
      selectNode(node.id);
    });

    // Port drag for connections
    setupPortDrag(el, node);

    canvas.appendChild(el);
  }

  function deleteSelectedNode() {
    if (!state.selectedNode) return;

    var nodeId = state.selectedNode;

    // Don't allow deleting if it's market/conversion? Optional – allow it.
    if (!confirm(i18n.confirmDelete)) return;

    // Remove connections involving this node
    state.connections = state.connections.filter(function (c) {
      return c.from !== nodeId && c.to !== nodeId;
    });

    // Remove from state
    state.nodes = state.nodes.filter(function (n) { return n.id !== nodeId; });

    // Remove DOM
    var el = canvas.querySelector('[data-node-id="' + nodeId + '"]');
    if (el) el.remove();

    selectNode(null);
    redrawConnections();
    state.dirty = true;
  }

  /* ======== Grid snapping ======== */
  var GRID_SIZE = 24; // matches CSS background-size

  function snapToGrid(val) {
    return Math.round(val / GRID_SIZE) * GRID_SIZE;
  }

  /* ======== Node dragging ======== */
  function setupNodeDrag(el, node) {
    var startX, startY, origX, origY, dragging = false;

    el.addEventListener('mousedown', function (e) {
      if (e.target.classList.contains('pn-cm-fb-port')) return;
      if (e.button !== 0) return;

      dragging = true;
      startX = e.clientX;
      startY = e.clientY;
      origX = node.x;
      origY = node.y;

      e.preventDefault();

      function onMove(ev) {
        if (!dragging) return;
        var dx = (ev.clientX - startX) / zoom;
        var dy = (ev.clientY - startY) / zoom;
        node.x = Math.max(0, snapToGrid(origX + dx));
        node.y = Math.max(0, snapToGrid(origY + dy));
        el.style.left = node.x + 'px';
        el.style.top = node.y + 'px';
        redrawConnections();
        state.dirty = true;
      }

      function onUp() {
        dragging = false;
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
      }

      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
    });
  }

  /* ======== Port dragging (connections) ======== */
  var tempLine = null;
  var connectingFrom = null;

  function setupPortDrag(el, node) {
    var ports = el.querySelectorAll('.pn-cm-fb-port');
    ports.forEach(function (port) {
      port.addEventListener('mousedown', function (e) {
        e.stopPropagation();
        e.preventDefault();

        var portType = port.getAttribute('data-port');
        if (portType !== 'out') return; // Only connect from output

        connectingFrom = node.id;

        var ns = 'http://www.w3.org/2000/svg';
        tempLine = document.createElementNS(ns, 'path');
        tempLine.setAttribute('class', 'pn-cm-fb-arrow-temp');
        svg.appendChild(tempLine);

        var startPos = getPortCenter(el, 'out');

        function onMove(ev) {
          var pos = screenToCanvas(ev.clientX, ev.clientY);
          var d = buildCurvePath(startPos.x, startPos.y, pos.x, pos.y);
          tempLine.setAttribute('d', d);
        }

        function onUp(ev) {
          document.removeEventListener('mousemove', onMove);
          document.removeEventListener('mouseup', onUp);

          if (tempLine) {
            tempLine.remove();
            tempLine = null;
          }

          // Find target node
          var targetEl = document.elementFromPoint(ev.clientX, ev.clientY);
          if (targetEl) {
            var nodeEl = targetEl.closest('.pn-cm-fb-node');
            if (nodeEl) {
              var targetId = nodeEl.getAttribute('data-node-id');
              if (targetId && targetId !== connectingFrom) {
                // Check if connection already exists
                var exists = state.connections.some(function (c) {
                  return c.from === connectingFrom && c.to === targetId;
                });
                if (!exists) {
                  state.connections.push({ from: connectingFrom, to: targetId });
                  redrawConnections();
                  state.dirty = true;
                }
              }
            }
          }

          connectingFrom = null;
        }

        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
      });
    });
  }

  /* ======== Connection rendering ======== */
  function redrawConnections() {
    // Remove existing arrows
    var existing = svg.querySelectorAll('.pn-cm-fb-arrow');
    existing.forEach(function (el) { el.remove(); });

    state.connections.forEach(function (conn) {
      var fromEl = canvas.querySelector('[data-node-id="' + conn.from + '"]');
      var toEl = canvas.querySelector('[data-node-id="' + conn.to + '"]');
      if (!fromEl || !toEl) return;

      var start = getPortCenter(fromEl, 'out');
      var end = getPortCenter(toEl, 'in');

      var ns = 'http://www.w3.org/2000/svg';
      var path = document.createElementNS(ns, 'path');
      path.setAttribute('class', 'pn-cm-fb-arrow');
      path.setAttribute('d', buildCurvePath(start.x, start.y, end.x, end.y));
      path.setAttribute('data-from', conn.from);
      path.setAttribute('data-to', conn.to);
      svg.appendChild(path);
    });
  }

  function getPortCenter(nodeEl, portType) {
    var port = nodeEl.querySelector('.pn-cm-fb-port-' + portType);
    if (!port) return { x: 0, y: 0 };

    var layerRect = zoomLayer.getBoundingClientRect();
    var portRect = port.getBoundingClientRect();

    return {
      x: (portRect.left + portRect.width / 2 - layerRect.left) / zoom,
      y: (portRect.top + portRect.height / 2 - layerRect.top) / zoom,
    };
  }

  function buildCurvePath(x1, y1, x2, y2) {
    var dx = Math.abs(x2 - x1) * 0.5;
    var cp1x = x1 + dx;
    var cp2x = x2 - dx;
    return 'M' + x1 + ',' + y1 + ' C' + cp1x + ',' + y1 + ' ' + cp2x + ',' + y2 + ' ' + x2 + ',' + y2;
  }

  /* ======== SVG click (delete connection) ======== */
  function onSvgClick(e) {
    var path = e.target.closest('.pn-cm-fb-arrow');
    if (!path) return;

    var from = path.getAttribute('data-from');
    var to = path.getAttribute('data-to');

    state.connections = state.connections.filter(function (c) {
      return !(c.from === from && c.to === to);
    });

    path.remove();
    state.dirty = true;
  }

  /* ======== Drop from sidebar ======== */
  function onCanvasDragOver(e) {
    e.preventDefault();
    canvasWrap.classList.add('pn-cm-fb-drag-over');
  }

  function onCanvasDragLeave() {
    canvasWrap.classList.remove('pn-cm-fb-drag-over');
  }

  function onCanvasDrop(e) {
    e.preventDefault();
    canvasWrap.classList.remove('pn-cm-fb-drag-over');

    if (!state.funnelId) {
      pn_customers_manager_get_main_message(i18n.noFunnel);
      return;
    }

    // Remove empty canvas message if present
    var emptyMsg = canvasWrap.querySelector('.pn-cm-fb-canvas-empty');
    if (emptyMsg) emptyMsg.remove();

    var type = e.dataTransfer.getData('text/pn-cm-fb-type');
    var subtype = e.dataTransfer.getData('text/pn-cm-fb-subtype');
    var label = e.dataTransfer.getData('text/pn-cm-fb-label');

    if (!type) return;

    var pos = screenToCanvas(e.clientX, e.clientY);
    var x = pos.x - 55; // center offset
    var y = pos.y - 30;

    addNode({
      type: type,
      subtype: subtype,
      label: label,
      x: Math.max(0, snapToGrid(x)),
      y: Math.max(0, snapToGrid(y)),
      config: {},
    });
  }

  /* ======== Sidebar drag start ======== */
  document.addEventListener('dragstart', function (e) {
    var el = e.target.closest('.pn-cm-fb-element');
    if (!el) return;

    e.dataTransfer.setData('text/pn-cm-fb-type', el.getAttribute('data-type'));
    e.dataTransfer.setData('text/pn-cm-fb-subtype', el.getAttribute('data-subtype'));
    e.dataTransfer.setData('text/pn-cm-fb-label', el.querySelector('span:last-child').textContent.trim());
    e.dataTransfer.effectAllowed = 'copy';
  });

  /* ======== Node selection & properties ======== */
  function selectNode(nodeId) {
    // Deselect previous
    var prev = canvas.querySelector('.pn-cm-fb-node-selected');
    if (prev) prev.classList.remove('pn-cm-fb-node-selected');

    state.selectedNode = nodeId;

    if (!nodeId) {
      showPropsEmpty();
      return;
    }

    var nodeEl = canvas.querySelector('[data-node-id="' + nodeId + '"]');
    if (nodeEl) nodeEl.classList.add('pn-cm-fb-node-selected');

    var node = findNode(nodeId);
    if (!node) {
      showPropsEmpty();
      return;
    }

    // Show props panel
    propsEmpty.style.display = 'none';
    propsPanel.style.display = 'block';

    propLabel.value = node.label || '';

    // Show/hide WhatsApp chat button
    var isWaNode = node.subtype === 'whatsapp' || node.subtype === 'whatsapp_ai';
    if (waChatBtnWrap) {
      waChatBtnWrap.style.display = isWaNode ? 'block' : 'none';
    }

    // Show/hide Instagram chat button
    var isIgNode = node.subtype === 'instagram_ai';
    if (igChatBtnWrap) {
      igChatBtnWrap.style.display = isIgNode ? 'block' : 'none';
    }

    // Show/hide API warning banner in properties panel
    var existingBanner = document.getElementById('pn-cm-fb-api-banner');
    if (existingBanner) existingBanner.remove();

    var missing = getMissingApis(node.subtype);
    if (missing.length) {
      var bannerDiv = document.createElement('div');
      bannerDiv.id = 'pn-cm-fb-api-banner';
      bannerDiv.className = 'pn-cm-fb-api-banner';

      var bannerHtml = '<span class="material-icons-outlined pn-cm-fb-api-banner-icon">warning</span>' +
        '<span class="pn-cm-fb-api-banner-text">' + escapeHtml(i18n.apiNotConfigured || 'API no configurada') + '</span>';

      for (var m = 0; m < missing.length; m++) {
        bannerHtml += '<a href="' + escapeHtml(missing[m].link) + '" class="pn-cm-fb-api-banner-btn" target="_blank">' +
          '<span class="material-icons-outlined">settings</span> ' +
          escapeHtml((i18n.configureApi || 'Configurar') + ' ' + missing[m].label) +
          '</a>';
      }

      bannerDiv.innerHTML = bannerHtml;
      propsPanel.insertBefore(bannerDiv, propsPanel.firstChild);
    }
  }

  function showPropsEmpty() {
    propsPanel.style.display = 'none';
    propsEmpty.style.display = 'flex';
  }

  function onPropChange() {
    if (!state.selectedNode) return;
    var node = findNode(state.selectedNode);
    if (!node) return;

    node.label = propLabel.value;

    // Update label in DOM
    var nodeEl = canvas.querySelector('[data-node-id="' + node.id + '"]');
    if (nodeEl) {
      var labelEl = nodeEl.querySelector('.pn-cm-fb-node-label');
      if (labelEl) labelEl.textContent = node.label;
    }

    state.dirty = true;
  }

  /* ======== Helpers ======== */
  function findNode(id) {
    for (var i = 0; i < state.nodes.length; i++) {
      if (state.nodes[i].id === id) return state.nodes[i];
    }
    return null;
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
  }

  /**
   * Convert WhatsApp-style formatting to HTML (call AFTER escapeHtml).
   * *bold* → <strong>, _italic_ → <em>, URLs → clickable links.
   */
  function formatWhatsAppText(text) {
    // URLs → clickable links
    text = text.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:#039be5">$1</a>');
    // *bold* → <strong> (non-greedy, single line)
    text = text.replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');
    // _italic_ → <em> (non-greedy, single line)
    text = text.replace(/\b_([^_\n]+)_\b/g, '<em>$1</em>');
    return text;
  }

  /**
   * Returns an array of missing API keys for a given node subtype.
   * Each entry: { key: 'whatsapp'|'openai', label: string, link: string }
   */
  function getMissingApis(subtype) {
    var required = API_REQUIREMENTS[subtype];
    if (!required) return [];

    var labels = { whatsapp: 'WhatsApp', openai: 'OpenAI', instagram: 'Instagram' };
    var missing = [];

    for (var i = 0; i < required.length; i++) {
      var key = required[i];
      if (!apiStatus[key]) {
        missing.push({ key: key, label: labels[key] || key, link: apiLinks[key] || '' });
      }
    }

    return missing;
  }

  /* ======== Standard loader HTML ======== */
  var LOADER_HTML = '<div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>';

  /* ======== WhatsApp Chat Popup ======== */
  var WA_POPUP_ID = 'pn-cm-fb-wa-popup';
  var popup, popupBody, popupTitle, popupBack, popupClose;
  var popupView = 'list'; // 'list' or 'chat'

  function bindPopupEvents() {
    popup      = document.getElementById(WA_POPUP_ID);
    popupBody  = document.getElementById('pn-cm-fb-wa-popup-body');
    popupTitle = document.getElementById('pn-cm-fb-wa-popup-title');
    popupBack  = document.getElementById('pn-cm-fb-wa-popup-back');
    popupClose = document.getElementById('pn-cm-fb-wa-popup-close');

    if (!popup) return;

    if (waChatBtn) {
      waChatBtn.addEventListener('click', openPopup);
    }
    popupClose.addEventListener('click', closePopup);
    popupBack.addEventListener('click', function () {
      loadConversationsList();
    });
  }

  function openPopup() {
    window.pn_customers_manager_Popups.open(WA_POPUP_ID);
    loadConversationsList();
  }

  function closePopup() {
    window.pn_customers_manager_Popups.close();
  }

  function loadConversationsList() {
    popupView = 'list';
    popupBack.style.display = 'none';
    popupTitle.textContent = i18n.conversations || 'Conversaciones WhatsApp';
    popupBody.innerHTML = LOADER_HTML;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_wa_conversations_list');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.conversations || res.conversations.length === 0) {
          popupBody.innerHTML =
            '<div class="pn-cm-fb-wa-popup-empty">' +
              '<span class="material-icons-outlined">chat_bubble_outline</span>' +
              '<p>' + escapeHtml(i18n.noConversations || 'No hay conversaciones todavia.') + '</p>' +
            '</div>';
          return;
        }

        var html = '<div class="pn-cm-fb-wa-conv-list">';
        for (var c = 0; c < res.conversations.length; c++) {
          var conv = res.conversations[c];
          var name = conv.name || conv.phone;
          var statusClass = conv.status === 'active' ? 'pn-cm-fb-wa-status-active' : 'pn-cm-fb-wa-status-closed';
          var statusLabel = conv.status === 'active' ? (i18n.active || 'Activa') : (i18n.closed || 'Cerrada');

          html += '<div class="pn-cm-fb-wa-conv-item" data-conv-id="' + conv.id + '" data-status="' + escapeHtml(conv.status) + '">' +
            '<div class="pn-cm-fb-wa-conv-avatar"><span class="material-icons-outlined">person</span></div>' +
            '<div class="pn-cm-fb-wa-conv-info">' +
              '<div class="pn-cm-fb-wa-conv-name">' + escapeHtml(name) + '</div>' +
              '<div class="pn-cm-fb-wa-conv-last">' + escapeHtml(conv.last_msg) + '</div>' +
            '</div>' +
            '<div class="pn-cm-fb-wa-conv-meta-col">' +
              '<span class="pn-cm-fb-wa-conv-badge ' + statusClass + '">' + escapeHtml(statusLabel) + '</span>' +
              '<span class="pn-cm-fb-wa-conv-count">' + conv.msg_count + ' ' + escapeHtml(i18n.messages || 'msgs') + '</span>' +
            '</div>' +
            '<div class="pn-cm-fb-wa-conv-item-actions">' +
              (conv.status === 'active' ? '<button type="button" class="pn-cm-fb-conv-action-btn" data-action="close" data-conv-id="' + conv.id + '" data-platform="wa" title="' + escapeHtml(i18n.close || 'Close') + '"><span class="material-icons-outlined">close</span></button>' : '') +
              '<button type="button" class="pn-cm-fb-conv-action-btn" data-action="reset" data-conv-id="' + conv.id + '" data-platform="wa" title="' + escapeHtml(i18n.reset || 'Reset') + '"><span class="material-icons-outlined">restart_alt</span></button>' +
              '<button type="button" class="pn-cm-fb-conv-action-btn pn-cm-fb-conv-action-delete" data-action="delete" data-conv-id="' + conv.id + '" data-platform="wa" title="' + escapeHtml(i18n.deleteLabel || 'Delete') + '"><span class="material-icons-outlined">delete</span></button>' +
            '</div>' +
          '</div>';
        }
        html += '</div>';
        popupBody.innerHTML = html;

        // Bind click on items (open chat view)
        var items = popupBody.querySelectorAll('.pn-cm-fb-wa-conv-item');
        items.forEach(function (item) {
          item.addEventListener('click', function (e) {
            if (e.target.closest('.pn-cm-fb-conv-action-btn')) return;
            var convId = parseInt(item.getAttribute('data-conv-id'), 10);
            loadConversationChat(convId);
          });
        });

        // Bind action buttons on list items
        popupBody.querySelectorAll('.pn-cm-fb-conv-action-btn').forEach(function (btn) {
          btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var action = btn.getAttribute('data-action');
            var convId = parseInt(btn.getAttribute('data-conv-id'), 10);
            if (!action || !convId) return;
            if (action === 'delete' && !confirm(i18n.confirmDeleteConv || 'Delete this conversation?')) return;
            if (action === 'reset' && !confirm(i18n.confirmReset || 'Reset this conversation? All messages will be cleared.')) return;
            doConvAction(action, convId, 'wa', function () { loadConversationsList(); });
          });
        });
      })
      .catch(function () {
        popupBody.innerHTML = '<div class="pn-cm-fb-wa-popup-empty"><p>' +
          escapeHtml(i18n.loadError || 'Error al cargar.') + '</p></div>';
      });
  }

  function loadConversationChat(convId) {
    popupView = 'chat';
    popupBack.style.display = 'inline-flex';
    popupBody.innerHTML = LOADER_HTML;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_wa_conversation_messages');
    formData.append('conv_id', convId);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          popupBody.innerHTML = '<div class="pn-cm-fb-wa-popup-empty"><p>' + escapeHtml(res.error_content) + '</p></div>';
          return;
        }

        popupTitle.textContent = res.name || res.phone;

        if (!res.messages || res.messages.length === 0) {
          popupBody.innerHTML = '<div class="pn-cm-fb-wa-popup-empty"><p>' +
            escapeHtml(i18n.noConversations || 'Sin mensajes.') + '</p></div>';
          return;
        }

        var convStatus = res.status || 'active';

        var html = '<div class="pn-cm-fb-wa-chat-wrap">';
        for (var m = 0; m < res.messages.length; m++) {
          var msg = res.messages[m];
          var bubbleClass = msg.role === 'user' ? 'pn-cm-fb-wa-msg-user' : 'pn-cm-fb-wa-msg-bot';
          var time = msg.timestamp ? msg.timestamp.substring(11, 16) : '';

          html += '<div class="pn-cm-fb-wa-msg ' + bubbleClass + '">' +
            '<div class="pn-cm-fb-wa-msg-bubble">' +
              '<div class="pn-cm-fb-wa-msg-text">' + formatWhatsAppText(escapeHtml(msg.content).replace(/\n/g, '<br>')) + '</div>' +
              '<span class="pn-cm-fb-wa-msg-time">' + escapeHtml(time) + '</span>' +
            '</div>' +
          '</div>';
        }
        html += '</div>';

        // Action buttons bar
        html += '<div class="pn-cm-fb-wa-chat-actions">';
        if (convStatus === 'active') {
          html += '<button type="button" class="pn-cm-fb-conv-action-btn" data-action="close" data-conv-id="' + convId + '" data-platform="wa">' +
            '<span class="material-icons-outlined">close</span> ' + escapeHtml(i18n.close || 'Close') + '</button>';
        }
        html += '<button type="button" class="pn-cm-fb-conv-action-btn" data-action="reset" data-conv-id="' + convId + '" data-platform="wa">' +
          '<span class="material-icons-outlined">restart_alt</span> ' + escapeHtml(i18n.reset || 'Reset') + '</button>';
        html += '<button type="button" class="pn-cm-fb-conv-action-btn pn-cm-fb-conv-action-delete" data-action="delete" data-conv-id="' + convId + '" data-platform="wa">' +
          '<span class="material-icons-outlined">delete</span> ' + escapeHtml(i18n.deleteLabel || 'Delete') + '</button>';
        html += '</div>';

        popupBody.innerHTML = html;

        // Bind action buttons in chat view
        popupBody.querySelectorAll('.pn-cm-fb-wa-chat-actions .pn-cm-fb-conv-action-btn').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var action = btn.getAttribute('data-action');
            var cid = parseInt(btn.getAttribute('data-conv-id'), 10);
            if (!action || !cid) return;
            if (action === 'delete' && !confirm(i18n.confirmDeleteConv || 'Delete this conversation?')) return;
            if (action === 'reset' && !confirm(i18n.confirmReset || 'Reset this conversation? All messages will be cleared.')) return;
            doConvAction(action, cid, 'wa', function () { loadConversationsList(); });
          });
        });

        // Scroll to bottom
        var chatWrap = popupBody.querySelector('.pn-cm-fb-wa-chat-wrap');
        if (chatWrap) {
          chatWrap.scrollTop = chatWrap.scrollHeight;
        }
      })
      .catch(function () {
        popupBody.innerHTML = '<div class="pn-cm-fb-wa-popup-empty"><p>' +
          escapeHtml(i18n.loadError || 'Error al cargar.') + '</p></div>';
      });
  }

  /* ======== Shared conversation action helper ======== */
  function doConvAction(action, convId, platform, callback) {
    var ajaxType = platform === 'ig' ? 'pn_cm_ig_conversation_action' : 'pn_cm_wa_conversation_action';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', ajaxType);
    formData.append('conv_id', convId);
    formData.append('conv_action', action);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          alert(res.error_content || 'Error');
          return;
        }
        if (typeof callback === 'function') callback();
      })
      .catch(function () {
        alert(i18n.loadError || 'Error');
      });
  }

  /* ======== Instagram Chat Popup ======== */
  var IG_POPUP_ID = 'pn-cm-fb-ig-popup';
  var igPopup, igPopupBody, igPopupTitle, igPopupBack, igPopupClose;
  var igPopupView = 'list';

  function bindIgPopupEvents() {
    igPopup      = document.getElementById(IG_POPUP_ID);
    igPopupBody  = document.getElementById('pn-cm-fb-ig-popup-body');
    igPopupTitle = document.getElementById('pn-cm-fb-ig-popup-title');
    igPopupBack  = document.getElementById('pn-cm-fb-ig-popup-back');
    igPopupClose = document.getElementById('pn-cm-fb-ig-popup-close');

    if (!igPopup) return;

    if (igChatBtn) {
      igChatBtn.addEventListener('click', openIgPopup);
    }
    igPopupClose.addEventListener('click', closeIgPopup);
    igPopupBack.addEventListener('click', function () {
      loadIgConversationsList();
    });
  }

  function openIgPopup() {
    window.pn_customers_manager_Popups.open(IG_POPUP_ID);
    loadIgConversationsList();
  }

  function closeIgPopup() {
    window.pn_customers_manager_Popups.close();
  }

  function loadIgConversationsList() {
    igPopupView = 'list';
    igPopupBack.style.display = 'none';
    igPopupTitle.textContent = i18n.igConversations || 'Instagram Conversations';
    igPopupBody.innerHTML = LOADER_HTML;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_ig_conversations_list');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.conversations || res.conversations.length === 0) {
          igPopupBody.innerHTML =
            '<div class="pn-cm-fb-ig-popup-empty">' +
              '<span class="material-icons-outlined">photo_camera</span>' +
              '<p>' + escapeHtml(i18n.igNoConversations || 'No Instagram conversations yet.') + '</p>' +
            '</div>';
          return;
        }

        var html = '<div class="pn-cm-fb-ig-conv-list">';
        for (var c = 0; c < res.conversations.length; c++) {
          var conv = res.conversations[c];
          var name = conv.name || conv.ig_user;
          var statusClass = conv.status === 'active' ? 'pn-cm-fb-ig-status-active' : 'pn-cm-fb-ig-status-closed';
          var statusLabel = conv.status === 'active' ? (i18n.active || 'Active') : (i18n.closed || 'Closed');

          html += '<div class="pn-cm-fb-ig-conv-item" data-conv-id="' + conv.id + '" data-status="' + escapeHtml(conv.status) + '">' +
            '<div class="pn-cm-fb-ig-conv-avatar"><span class="material-icons-outlined">person</span></div>' +
            '<div class="pn-cm-fb-ig-conv-info">' +
              '<div class="pn-cm-fb-ig-conv-name">' + escapeHtml(name) + '</div>' +
              '<div class="pn-cm-fb-ig-conv-last">' + escapeHtml(conv.last_msg) + '</div>' +
            '</div>' +
            '<div class="pn-cm-fb-ig-conv-meta-col">' +
              '<span class="pn-cm-fb-ig-conv-badge ' + statusClass + '">' + escapeHtml(statusLabel) + '</span>' +
              '<span class="pn-cm-fb-ig-conv-count">' + conv.msg_count + ' ' + escapeHtml(i18n.messages || 'msgs') + '</span>' +
            '</div>' +
            '<div class="pn-cm-fb-wa-conv-item-actions">' +
              (conv.status === 'active' ? '<button type="button" class="pn-cm-fb-conv-action-btn" data-action="close" data-conv-id="' + conv.id + '" data-platform="ig" title="' + escapeHtml(i18n.close || 'Close') + '"><span class="material-icons-outlined">close</span></button>' : '') +
              '<button type="button" class="pn-cm-fb-conv-action-btn" data-action="reset" data-conv-id="' + conv.id + '" data-platform="ig" title="' + escapeHtml(i18n.reset || 'Reset') + '"><span class="material-icons-outlined">restart_alt</span></button>' +
              '<button type="button" class="pn-cm-fb-conv-action-btn pn-cm-fb-conv-action-delete" data-action="delete" data-conv-id="' + conv.id + '" data-platform="ig" title="' + escapeHtml(i18n.deleteLabel || 'Delete') + '"><span class="material-icons-outlined">delete</span></button>' +
            '</div>' +
          '</div>';
        }
        html += '</div>';
        igPopupBody.innerHTML = html;

        var items = igPopupBody.querySelectorAll('.pn-cm-fb-ig-conv-item');
        items.forEach(function (item) {
          item.addEventListener('click', function (e) {
            if (e.target.closest('.pn-cm-fb-conv-action-btn')) return;
            var convId = parseInt(item.getAttribute('data-conv-id'), 10);
            loadIgConversationChat(convId);
          });
        });

        // Bind action buttons on list items
        igPopupBody.querySelectorAll('.pn-cm-fb-conv-action-btn').forEach(function (btn) {
          btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var action = btn.getAttribute('data-action');
            var convId = parseInt(btn.getAttribute('data-conv-id'), 10);
            if (!action || !convId) return;
            if (action === 'delete' && !confirm(i18n.confirmDeleteConv || 'Delete this conversation?')) return;
            if (action === 'reset' && !confirm(i18n.confirmReset || 'Reset this conversation? All messages will be cleared.')) return;
            doConvAction(action, convId, 'ig', function () { loadIgConversationsList(); });
          });
        });
      })
      .catch(function () {
        igPopupBody.innerHTML = '<div class="pn-cm-fb-ig-popup-empty"><p>' +
          escapeHtml(i18n.loadError || 'Error loading.') + '</p></div>';
      });
  }

  function loadIgConversationChat(convId) {
    igPopupView = 'chat';
    igPopupBack.style.display = 'inline-flex';
    igPopupBody.innerHTML = LOADER_HTML;

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_ig_conversation_messages');
    formData.append('conv_id', convId);

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.error_key) {
          igPopupBody.innerHTML = '<div class="pn-cm-fb-ig-popup-empty"><p>' + escapeHtml(res.error_content) + '</p></div>';
          return;
        }

        igPopupTitle.textContent = res.name || res.ig_user;

        if (!res.messages || res.messages.length === 0) {
          igPopupBody.innerHTML = '<div class="pn-cm-fb-ig-popup-empty"><p>' +
            escapeHtml(i18n.igNoConversations || 'No messages.') + '</p></div>';
          return;
        }

        var convStatus = res.status || 'active';

        var html = '<div class="pn-cm-fb-ig-chat-wrap">';
        for (var m = 0; m < res.messages.length; m++) {
          var msg = res.messages[m];
          var bubbleClass = msg.role === 'user' ? 'pn-cm-fb-ig-msg-user' : 'pn-cm-fb-ig-msg-bot';
          var time = msg.timestamp ? msg.timestamp.substring(11, 16) : '';

          html += '<div class="pn-cm-fb-ig-msg ' + bubbleClass + '">' +
            '<div class="pn-cm-fb-ig-msg-bubble">' +
              '<div class="pn-cm-fb-ig-msg-text">' + formatInstagramText(escapeHtml(msg.content).replace(/\n/g, '<br>')) + '</div>' +
              '<span class="pn-cm-fb-ig-msg-time">' + escapeHtml(time) + '</span>' +
            '</div>' +
          '</div>';
        }
        html += '</div>';

        // Action buttons bar
        html += '<div class="pn-cm-fb-wa-chat-actions">';
        if (convStatus === 'active') {
          html += '<button type="button" class="pn-cm-fb-conv-action-btn" data-action="close" data-conv-id="' + convId + '" data-platform="ig">' +
            '<span class="material-icons-outlined">close</span> ' + escapeHtml(i18n.close || 'Close') + '</button>';
        }
        html += '<button type="button" class="pn-cm-fb-conv-action-btn" data-action="reset" data-conv-id="' + convId + '" data-platform="ig">' +
          '<span class="material-icons-outlined">restart_alt</span> ' + escapeHtml(i18n.reset || 'Reset') + '</button>';
        html += '<button type="button" class="pn-cm-fb-conv-action-btn pn-cm-fb-conv-action-delete" data-action="delete" data-conv-id="' + convId + '" data-platform="ig">' +
          '<span class="material-icons-outlined">delete</span> ' + escapeHtml(i18n.deleteLabel || 'Delete') + '</button>';
        html += '</div>';

        igPopupBody.innerHTML = html;

        // Bind action buttons in chat view
        igPopupBody.querySelectorAll('.pn-cm-fb-wa-chat-actions .pn-cm-fb-conv-action-btn').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var action = btn.getAttribute('data-action');
            var cid = parseInt(btn.getAttribute('data-conv-id'), 10);
            if (!action || !cid) return;
            if (action === 'delete' && !confirm(i18n.confirmDeleteConv || 'Delete this conversation?')) return;
            if (action === 'reset' && !confirm(i18n.confirmReset || 'Reset this conversation? All messages will be cleared.')) return;
            doConvAction(action, cid, 'ig', function () { loadIgConversationsList(); });
          });
        });

        var chatWrap = igPopupBody.querySelector('.pn-cm-fb-ig-chat-wrap');
        if (chatWrap) {
          chatWrap.scrollTop = chatWrap.scrollHeight;
        }
      })
      .catch(function () {
        igPopupBody.innerHTML = '<div class="pn-cm-fb-ig-popup-empty"><p>' +
          escapeHtml(i18n.loadError || 'Error loading.') + '</p></div>';
      });
  }

  /**
   * Convert Instagram text to HTML: only linkify URLs (plain text, no bold/italic).
   */
  function formatInstagramText(text) {
    text = text.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:#c13584">$1</a>');
    return text;
  }

  /* ======== Settings Popup (uses pn_customers_manager_Popups) ======== */
  var settingsFieldsCache = {};
  var SETTINGS_POPUP_ID = 'pn-cm-fb-settings-popup';

  function getSettingsPopupContent() {
    return document.querySelector('#' + SETTINGS_POPUP_ID + ' .pn-customers-manager-popup-content');
  }

  function openSettingsPopup() {
    if (!state.selectedNode) return;
    var node = findNode(state.selectedNode);
    if (!node) return;

    if (typeof window.pn_customers_manager_Popups === 'undefined') return;

    window.pn_customers_manager_Popups.open(SETTINGS_POPUP_ID);

    var popupContent = getSettingsPopupContent();
    if (!popupContent) return;

    var cacheKey = node.subtype || '_default';

    // If already cached, reuse HTML
    if (settingsFieldsCache[cacheKey]) {
      popupContent.innerHTML = settingsFieldsCache[cacheKey];
      bindPopupSaveButton(popupContent);
      populateSettingsFields(node, popupContent);
      initWysiwygEditors(node, popupContent);
      bindSettingsFieldEvents(node, popupContent);
      initImageUploads(popupContent, node);
      restoreImagePreviews(popupContent);
      return;
    }

    // Show loading spinner
    popupContent.innerHTML = '<div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>';

    var formData = new FormData();
    formData.append('action', 'pn_customers_manager_ajax');
    formData.append('pn_customers_manager_ajax_nonce', cfg.nonce);
    formData.append('pn_customers_manager_ajax_type', 'pn_cm_funnel_node_settings');
    formData.append('node_subtype', node.subtype || '');

    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        settingsFieldsCache[cacheKey] = html;
        var pc = getSettingsPopupContent();
        if (!pc) return;
        pc.innerHTML = html;
        bindPopupSaveButton(pc);
        populateSettingsFields(node, pc);
        initWysiwygEditors(node, pc);
        bindSettingsFieldEvents(node, pc);
        initImageUploads(pc, node);
        restoreImagePreviews(pc);
      })
      .catch(function () {
        var pc = getSettingsPopupContent();
        if (pc) {
          pc.innerHTML = '<div class="pn-customers-manager-p-30 pn-customers-manager-text-align-center"><p>' +
            escapeHtml(i18n.settingsError || 'Error loading settings.') + '</p></div>';
        }
      });
  }

  function initWysiwygEditors(node, container) {
    if (typeof jQuery === 'undefined' || !jQuery.fn.trumbowyg) return;
    var $editors = jQuery(container).find('.pn-customers-manager-wysiwyg');
    if (!$editors.length) return;

    // Destroy any previous Trumbowyg instances (e.g. from cached HTML)
    $editors.each(function () {
      var $el = jQuery(this);
      if ($el.data('trumbowyg')) {
        $el.trumbowyg('destroy');
      }
    });

    // Set SVG path
    if (typeof pn_customers_manager_trumbowyg !== 'undefined') {
      jQuery.trumbowyg.svgPath = pn_customers_manager_trumbowyg.path;
    }

    $editors.each(function () {
      var $el = jQuery(this);
      var key = $el.attr('name');

      $el.trumbowyg({
        btns: [
          ['formatting'],
          ['strong', 'em', 'underline'],
          ['unorderedList', 'orderedList'],
          ['link'],
          ['removeformat'],
        ],
        autogrow: true,
        removeformatPasted: true,
      });

      // Sync Trumbowyg changes back to node config
      $el.on('tbwchange', function () {
        if (!node.config) node.config = {};
        node.config[key] = $el.trumbowyg('html');
        state.dirty = true;
      });
    });
  }

  function bindPopupSaveButton(popupContent) {
    var saveBtn = popupContent.querySelector('#pn-cm-fb-settings-popup-save');
    if (!saveBtn) return;
    saveBtn.addEventListener('click', function (e) {
      e.preventDefault();
      if (typeof window.pn_customers_manager_Popups !== 'undefined') {
        window.pn_customers_manager_Popups.close();
      }
    });
  }

  /**
   * Normalise a field name attribute to a config key.
   * Multi-select / multi-input fields have a trailing "[]" in their name.
   */
  function normaliseFieldKey(name) {
    if (!name) return '';
    return name.replace(/\[\]$/, '');
  }

  /**
   * Rebuild an html_multi wrapper so it shows exactly `count` rows.
   * Uses the existing forms.js add/remove click handlers when available,
   * falling back to manual DOM clone/removal otherwise.
   */
  function setHtmlMultiRowCount(wrapper, count) {
    var groups = wrapper.querySelectorAll('.pn-customers-manager-html-multi-group');
    var current = groups.length;
    if (count < 1) count = 1;

    // Remove extra rows
    while (current > count) {
      var last = wrapper.querySelectorAll('.pn-customers-manager-html-multi-group');
      if (last.length <= 1) break;
      last[last.length - 1].parentNode.removeChild(last[last.length - 1]);
      current--;
    }

    // Add missing rows by cloning the first one
    while (current < count) {
      var all = wrapper.querySelectorAll('.pn-customers-manager-html-multi-group');
      if (!all.length) break;
      var clone = all[0].cloneNode(true);
      // Clear values and drop any stale id attributes on cloned inputs
      var clonedInputs = clone.querySelectorAll('input, select, textarea');
      for (var ci = 0; ci < clonedInputs.length; ci++) {
        var cin = clonedInputs[ci];
        if (cin.type === 'checkbox' || cin.type === 'radio') {
          cin.checked = false;
        } else if (cin.tagName === 'SELECT') {
          for (var oi = 0; oi < cin.options.length; oi++) {
            cin.options[oi].selected = false;
          }
        } else {
          cin.value = '';
        }
        cin.removeAttribute('id');
      }
      all[all.length - 1].parentNode.insertBefore(clone, all[all.length - 1].nextSibling);
      current++;
    }
  }

  /**
   * Read the values of an html_multi wrapper as an array (by row).
   * Assumes a single field per row (one input name shared across rows).
   */
  function readHtmlMultiValues(wrapper) {
    var values = [];
    var groups = wrapper.querySelectorAll('.pn-customers-manager-html-multi-group');
    for (var g = 0; g < groups.length; g++) {
      var input = groups[g].querySelector('input[name], select[name], textarea[name]');
      if (!input) { values.push(''); continue; }
      values.push(input.value || '');
    }
    return values;
  }

  /**
   * Get the config key (field id) stored by an html_multi wrapper.
   */
  function getHtmlMultiKey(wrapper) {
    var probe = wrapper.querySelector('input[name], select[name], textarea[name]');
    if (!probe) return '';
    return normaliseFieldKey(probe.getAttribute('name'));
  }

  function populateSettingsFields(node, container) {
    // Populate html_multi wrappers first (row count must match saved array)
    var wrappers = container.querySelectorAll('.pn-customers-manager-html-multi-wrapper');
    for (var w = 0; w < wrappers.length; w++) {
      var wrapper = wrappers[w];
      var key = getHtmlMultiKey(wrapper);
      if (!key) continue;
      var stored = (node.config && node.config[key] !== undefined) ? node.config[key] : [];
      if (!Array.isArray(stored)) {
        stored = (stored === '' || stored === null || stored === undefined) ? [] : [stored];
      }
      setHtmlMultiRowCount(wrapper, stored.length || 1);
      var groups = wrapper.querySelectorAll('.pn-customers-manager-html-multi-group');
      for (var gi = 0; gi < groups.length; gi++) {
        var input = groups[gi].querySelector('input[name], select[name], textarea[name]');
        if (!input) continue;
        input.value = (stored[gi] !== undefined && stored[gi] !== null) ? stored[gi] : '';
      }
    }

    var fields = container.querySelectorAll('input[name], select[name], textarea[name]');
    for (var i = 0; i < fields.length; i++) {
      var el = fields[i];
      // Skip fields inside html_multi wrappers — already handled above
      if (el.closest && el.closest('.pn-customers-manager-html-multi-wrapper')) continue;

      var rawName = el.getAttribute('name');
      if (!rawName) continue;
      var cfgKey = normaliseFieldKey(rawName);
      var val = (node.config && node.config[cfgKey] !== undefined) ? node.config[cfgKey] : '';

      if (el.type === 'checkbox') {
        el.checked = !!val;
      } else if (el.tagName === 'SELECT' && el.multiple) {
        var selectedValues = Array.isArray(val) ? val.map(String) : (val === '' || val === null || val === undefined ? [] : [String(val)]);
        for (var oi = 0; oi < el.options.length; oi++) {
          el.options[oi].selected = selectedValues.indexOf(String(el.options[oi].value)) !== -1;
        }
      } else {
        el.value = val;
      }
    }

    // Initialise (or refresh) the custom pn_customers_manager_Selector on any
    // <select class="pn-customers-manager-select"> inside the popup. The
    // global bootstrap in pn-customers-manager-aux.js only runs on document
    // ready, so dynamically injected selects would otherwise render as raw
    // native <select> elements.
    if (window.jQuery && jQuery.fn && jQuery.fn.pn_customers_manager_Selector) {
      jQuery(container).find('select.pn-customers-manager-select').each(function () {
        var $sel = jQuery(this);
        var existing = $sel.data('-selector');
        if (existing && typeof existing.updateDisplay === 'function') {
          // Already initialised — just refresh visual state from current <option selected>
          existing.updateDisplay();
          return;
        }
        var opts = { searchable: true };
        var ph = $sel.attr('placeholder') || $sel.attr('data-placeholder');
        if (ph) opts.placeholder = ph;
        $sel.pn_customers_manager_Selector(opts);
      });
    }

    if (typeof window.cm_pn_form_update === 'function') {
      window.cm_pn_form_update();
    }
  }

  function bindSettingsFieldEvents(node, container) {
    // Helper: sync an html_multi wrapper's values back into node.config
    function syncHtmlMulti(wrapper) {
      var key = getHtmlMultiKey(wrapper);
      if (!key) return;
      if (!node.config) node.config = {};
      node.config[key] = readHtmlMultiValues(wrapper);
      state.dirty = true;
    }

    // Bind inputs inside html_multi wrappers (delegated so dynamically cloned rows also fire)
    var wrappers = container.querySelectorAll('.pn-customers-manager-html-multi-wrapper');
    for (var w = 0; w < wrappers.length; w++) {
      (function (wrapper) {
        wrapper.addEventListener('input', function (e) {
          if (e.target && e.target.matches && e.target.matches('input[name], textarea[name]')) {
            syncHtmlMulti(wrapper);
          }
        });
        wrapper.addEventListener('change', function (e) {
          if (e.target && e.target.matches && e.target.matches('input[name], select[name], textarea[name]')) {
            syncHtmlMulti(wrapper);
          }
        });

        // Local click handlers for add/remove row buttons (do not rely on the
        // global forms.js delegated handlers, which only bind if an html_multi
        // group existed at page load — the funnel builder injects them later).
        wrapper.addEventListener('click', function (e) {
          var removeBtn = e.target.closest ? e.target.closest('.pn-customers-manager-html-multi-remove-btn') : null;
          if (removeBtn && wrapper.contains(removeBtn)) {
            e.preventDefault();
            e.stopPropagation();
            var group = removeBtn.closest('.pn-customers-manager-html-multi-group');
            if (!group) return;
            var groupsNow = wrapper.querySelectorAll('.pn-customers-manager-html-multi-group');
            if (groupsNow.length > 1) {
              group.parentNode.removeChild(group);
            } else {
              // Keep at least one row, just clear its values
              var inputs = group.querySelectorAll('input, select, textarea');
              for (var ii = 0; ii < inputs.length; ii++) {
                if (inputs[ii].type === 'checkbox' || inputs[ii].type === 'radio') {
                  inputs[ii].checked = false;
                } else {
                  inputs[ii].value = '';
                }
              }
            }
            syncHtmlMulti(wrapper);
            return;
          }

          var addBtn = e.target.closest ? e.target.closest('.pn-customers-manager-html-multi-add-btn') : null;
          if (addBtn && wrapper.contains(addBtn)) {
            e.preventDefault();
            e.stopPropagation();
            var groupsAll = wrapper.querySelectorAll('.pn-customers-manager-html-multi-group');
            if (!groupsAll.length) return;
            var newGroup = groupsAll[0].cloneNode(true);
            var newInputs = newGroup.querySelectorAll('input, select, textarea');
            for (var ni = 0; ni < newInputs.length; ni++) {
              if (newInputs[ni].type === 'checkbox' || newInputs[ni].type === 'radio') {
                newInputs[ni].checked = false;
              } else if (newInputs[ni].tagName === 'SELECT') {
                for (var noi = 0; noi < newInputs[ni].options.length; noi++) {
                  newInputs[ni].options[noi].selected = false;
                }
              } else {
                newInputs[ni].value = '';
              }
              newInputs[ni].removeAttribute('id');
            }
            var lastGroup = groupsAll[groupsAll.length - 1];
            lastGroup.parentNode.insertBefore(newGroup, lastGroup.nextSibling);
            syncHtmlMulti(wrapper);
            return;
          }
        });
      })(wrappers[w]);
    }

    var fields = container.querySelectorAll('input[name], select[name], textarea[name]');
    for (var i = 0; i < fields.length; i++) {
      (function (el) {
        // Skip fields inside html_multi wrappers — handled above
        if (el.closest && el.closest('.pn-customers-manager-html-multi-wrapper')) return;

        var rawName = el.getAttribute('name');
        if (!rawName) return;
        var cfgKey = normaliseFieldKey(rawName);
        var eventName = (el.type === 'checkbox' || el.tagName === 'SELECT') ? 'change' : 'input';

        el.addEventListener(eventName, function () {
          if (!node.config) node.config = {};
          if (el.type === 'checkbox') {
            node.config[cfgKey] = el.checked;
          } else if (el.tagName === 'SELECT' && el.multiple) {
            var out = [];
            for (var oi = 0; oi < el.options.length; oi++) {
              if (el.options[oi].selected) out.push(el.options[oi].value);
            }
            node.config[cfgKey] = out;
          } else {
            node.config[cfgKey] = el.value;
          }
          state.dirty = true;
          if (typeof window.cm_pn_form_update === 'function') {
            window.cm_pn_form_update();
          }
          // Update button shortcode preview on any btn_ field change
          if (node.subtype === 'button' && cfgKey.indexOf('btn_') === 0) {
            updateButtonShortcode(node, container);
          }
        });
      })(fields[i]);
    }

    // Initial shortcode build for button nodes
    if (node.subtype === 'button') {
      updateButtonShortcode(node, container);
    }
  }

  /* ======== Button shortcode preview ======== */
  var BTN_SHORTCODE_DEFAULTS = {
    btn_text: '',
    btn_url: '#',
    btn_target_blank: false,
    btn_nofollow: false,
    btn_style: 'solid',
    btn_color: '',
    btn_text_color: '',
    btn_size: 'medium',
    btn_align: 'center',
    btn_icon: '',
    btn_icon_position: 'left',
    btn_use_image: false,
    btn_image: '',
  };

  // Map config key → shortcode attribute name
  var BTN_ATTR_MAP = {
    btn_text: 'text',
    btn_url: 'url',
    btn_target_blank: 'target_blank',
    btn_nofollow: 'nofollow',
    btn_style: 'style',
    btn_color: 'color',
    btn_text_color: 'text_color',
    btn_size: 'size',
    btn_align: 'align',
    btn_icon: 'icon',
    btn_icon_position: 'icon_position',
    btn_use_image: 'use_image',
    btn_image: 'image',
  };

  function updateButtonShortcode(node, container) {
    var input = container.querySelector('#pn-cm-fb-btn-shortcode');
    if (!input) return;

    var conf = node.config || {};
    var parts = [];

    for (var key in BTN_ATTR_MAP) {
      if (!BTN_ATTR_MAP.hasOwnProperty(key)) continue;
      var val = conf[key] !== undefined ? conf[key] : BTN_SHORTCODE_DEFAULTS[key];
      var def = BTN_SHORTCODE_DEFAULTS[key];

      // Normalise booleans / checkbox values
      if (typeof def === 'boolean') {
        var boolVal = !!val && val !== '0' && val !== 'off' && val !== '';
        if (boolVal === def) continue; // skip default
        val = boolVal ? '1' : '0';
      } else {
        if (String(val) === String(def) || val === '' || val === undefined) continue;
      }

      parts.push(BTN_ATTR_MAP[key] + '="' + String(val).replace(/"/g, '&quot;') + '"');
    }

    var shortcode = '[pn-customers-manager-button' + (parts.length ? ' ' + parts.join(' ') : '') + ']';
    input.value = shortcode;

    // Sync copy-text attribute so the global .pn-customers-manager-btn-copy handler works
    var copyBtn = container.querySelector('#pn-cm-fb-btn-shortcode-copy');
    if (copyBtn) {
      copyBtn.setAttribute('data-pn-customers-manager-copy-text', shortcode);
    }
  }

  /**
   * Initialise wp.media image upload buttons inside a dynamically loaded container.
   * The global forms JS skips this when no .pn-customers-manager-image-btn exists at page load.
   */
  function initImageUploads(container, node) {
    if (typeof wp === 'undefined' || !wp.media) return;

    var btns = container.querySelectorAll('.pn-customers-manager-image-btn');
    if (!btns.length) return;

    btns.forEach(function (btn) {
      if (btn._pnCmMediaBound) return; // prevent double-bind
      btn._pnCmMediaBound = true;

      btn.addEventListener('click', function (e) {
        e.preventDefault();

        var block = btn.closest('.pn-customers-manager-images-block');
        if (!block) return;

        var imagesDiv   = block.querySelector('.pn-customers-manager-images');
        var hiddenInput = block.querySelector('.pn-customers-manager-image-input');
        var isMultiple  = block.getAttribute('data-pn-customers-manager-multiple') === 'true';

        var frame = wp.media({
          title: isMultiple ? 'Select images' : 'Select image',
          library: { type: 'image' },
          multiple: isMultiple,
        });

        frame.on('select', function () {
          var selection = frame.state().get('selection').toJSON();
          var ids = [];
          if (imagesDiv) imagesDiv.innerHTML = '';

          for (var s = 0; s < selection.length; s++) {
            ids.push(selection[s].id);
            if (imagesDiv) {
              var img = document.createElement('img');
              img.src = selection[s].sizes && selection[s].sizes.thumbnail
                ? selection[s].sizes.thumbnail.url
                : selection[s].url;
              imagesDiv.appendChild(img);
            }
          }

          if (hiddenInput) {
            var key = hiddenInput.getAttribute('name');
            var val = ids.join(',');
            hiddenInput.value = val;

            // Write directly to node config
            if (node && key) {
              if (!node.config) node.config = {};
              node.config[key] = val;
              state.dirty = true;

              // Update shortcode preview for button nodes
              if (node.subtype === 'button') {
                updateButtonShortcode(node, container);
              }
            }
          }
        });

        frame.open();
      });
    });
  }

  /**
   * Restore image thumbnails for image fields that already have a saved value.
   * Called after populateSettingsFields so hidden inputs have their IDs set.
   */
  function restoreImagePreviews(container) {
    if (typeof wp === 'undefined' || !wp.media) return;

    var imageInputs = container.querySelectorAll('.pn-customers-manager-image-input');
    for (var j = 0; j < imageInputs.length; j++) {
      (function (input) {
        if (!input.value) return;
        var block = input.closest('.pn-customers-manager-images-block');
        if (!block) return;
        var imagesDiv = block.querySelector('.pn-customers-manager-images');
        if (!imagesDiv || imagesDiv.children.length) return;

        var ids = input.value.split(',');
        for (var k = 0; k < ids.length; k++) {
          var attId = parseInt(ids[k], 10);
          if (!attId) continue;
          var attachment = wp.media.attachment(attId);
          attachment.fetch().then(function () {
            var url = (attachment.get('sizes') && attachment.get('sizes').thumbnail)
              ? attachment.get('sizes').thumbnail.url
              : attachment.get('url');
            if (url && imagesDiv) {
              var img = document.createElement('img');
              img.src = url;
              imagesDiv.appendChild(img);
            }
          });
        }
      })(imageInputs[j]);
    }
  }

  /* ======== Boot ======== */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
