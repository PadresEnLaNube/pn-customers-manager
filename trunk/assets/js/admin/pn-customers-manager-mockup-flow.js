(function ($) {
	'use strict';

	console.log('[pn-cm-mf] Script loaded. typeof pnCmMockupFlow:', typeof pnCmMockupFlow);

	if (typeof pnCmMockupFlow === 'undefined') {
		console.warn('[pn-cm-mf] pnCmMockupFlow is undefined - wp_localize_script did not run. Aborting.');
		return;
	}

	var cfg = pnCmMockupFlow;
	console.log('[pn-cm-mf] Config:', {
		isAdmin: cfg.isAdmin,
		mockupFlowId: cfg.mockupFlowId,
		canvasDataType: typeof cfg.canvasData,
		canvasDataLength: cfg.canvasData ? cfg.canvasData.length : 0,
		imageUrlsKeys: Object.keys(cfg.imageUrls || {}),
		canComment: cfg.canComment,
		ajaxUrl: cfg.ajaxUrl
	});

	var i18n = cfg.i18n || {};
	var isAdmin = cfg.isAdmin;
	var imageUrls = cfg.imageUrls || {};

	var state = {
		mockups: [],
		connections: [],
		labels: [],
		projectName: '',
		zoom: 1,
		panX: 0,
		panY: 0,
		selectedNode: null,
		selectedType: null, // 'mockup' | 'label' | 'connection'
		dirty: false,
		connectingFrom: null,
		isPanning: false,
		panStartX: 0,
		panStartY: 0,
		dragNode: null,
		dragOffsetX: 0,
		dragOffsetY: 0,
		// Touch state
		touchId: null,
		pinchStartDist: 0,
		pinchStartZoom: 1,
		pinchMidX: 0,
		pinchMidY: 0,
		isTouchDrag: false
	};

	// Parse canvas data
	try {
		var parsed = typeof cfg.canvasData === 'string' ? JSON.parse(cfg.canvasData) : cfg.canvasData;
		state.mockups = parsed.mockups || [];
		state.connections = parsed.connections || [];
		state.labels = parsed.labels || [];
		state.projectName = parsed.projectName || '';
		console.log('[pn-cm-mf] Parsed canvas data:', { mockups: state.mockups.length, connections: state.connections.length, labels: state.labels.length });
	} catch (e) {
		console.error('[pn-cm-mf] Error parsing canvasData:', e, 'raw:', cfg.canvasData);
		state.mockups = [];
		state.connections = [];
		state.labels = [];
	}

	var $wrap, $canvasWrap, $zoomLayer, $canvas, $svg, $sidebar;

	$(document).ready(function () {
		console.log('[pn-cm-mf] DOM ready. isAdmin:', isAdmin);
		if (isAdmin) {
			initAdmin();
		} else {
			initPublic();
		}
	});

	// ── Admin Init ──────────────────────────────────────────

	function initAdmin() {
		$wrap = $('.pn-cm-mf-admin-wrap');
		console.log('[pn-cm-mf] initAdmin: .pn-cm-mf-admin-wrap found:', $wrap.length);
		if (!$wrap.length) return;

		$canvasWrap = $wrap.find('.pn-cm-mf-canvas-wrap');
		$zoomLayer = $wrap.find('.pn-cm-mf-zoom-layer');
		$canvas = $wrap.find('.pn-cm-mf-canvas');
		$svg = $wrap.find('.pn-cm-mf-canvas-svg');
		$sidebar = $wrap.find('.pn-cm-mf-sidebar-content');

		console.log('[pn-cm-mf] initAdmin elements:', {
			canvasWrap: $canvasWrap.length,
			zoomLayer: $zoomLayer.length,
			canvas: $canvas.length,
			svg: $svg.length,
			sidebar: $sidebar.length
		});

		state.panX = 40;
		state.panY = 40;
		applyTransform();

		renderAll();
		bindCanvasEvents($canvasWrap);
		bindTouchEvents($canvasWrap);
		bindAdminToolbar();
		console.log('[pn-cm-mf] initAdmin: done');
	}

	function bindAdminToolbar() {
		// Project name
		var $projectInput = $wrap.find('.pn-cm-mf-project-name-input');
		$projectInput.val(state.projectName);
		$projectInput.on('input', function () {
			state.projectName = $(this).val();
			state.dirty = true;
		});

		$wrap.on('click', '.pn-cm-mf-btn-add-mockup', function () {
			var center = getViewportCenter();
			var mockup = {
				id: 'm_' + Date.now(),
				order: state.mockups.length + 1,
				title: i18n.newMockup || 'New Mockup',
				description: '',
				desktop_image_id: 0,
				mobile_image_id: 0,
				x: center.x,
				y: center.y,
				width: 220,
				height: 170
			};
			state.mockups.push(mockup);
			state.dirty = true;
			renderAll();
			selectNode(mockup.id, 'mockup');
		});

		$wrap.on('click', '.pn-cm-mf-btn-add-label', function () {
			var center = getViewportCenter();
			var label = {
				id: 'l_' + Date.now(),
				text: i18n.newLabel || 'New Label',
				x: center.x,
				y: center.y,
				fontSize: 16,
				color: '#333333'
			};
			state.labels.push(label);
			state.dirty = true;
			renderAll();
			selectNode(label.id, 'label');
		});

		$wrap.on('click', '.pn-cm-mf-btn-connect', function () {
			state.connectingFrom = null;
			$wrap.find('.pn-cm-mf-connect-status').text(i18n.clickSource).show();
			$wrap.find('.pn-cm-mf-btn-cancel-connect').show();
			$(this).hide();
		});

		$wrap.on('click', '.pn-cm-mf-btn-cancel-connect', function () {
			cancelConnect();
		});

		$wrap.on('click', '.pn-cm-mf-btn-save', function () {
			saveCanvas();
		});

		// Export canvas as JSON file (includes image URLs for cross-site portability)
		$wrap.on('click', '.pn-cm-mf-btn-export', function () {
			// Build a map of image IDs to their full URLs for export
			var exportImageUrls = {};
			state.mockups.forEach(function (m) {
				if (m.desktop_image_id && imageUrls[m.desktop_image_id]) {
					var u = imageUrls[m.desktop_image_id];
					exportImageUrls[m.desktop_image_id] = u.full || u.medium || u.thumbnail || '';
				}
				if (m.mobile_image_id && imageUrls[m.mobile_image_id]) {
					var u2 = imageUrls[m.mobile_image_id];
					exportImageUrls[m.mobile_image_id] = u2.full || u2.medium || u2.thumbnail || '';
				}
			});

			var data = JSON.stringify({
				mockups: state.mockups,
				connections: state.connections,
				labels: state.labels,
				projectName: state.projectName,
				_imageUrls: exportImageUrls
			}, null, 2);
			var blob = new Blob([data], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = 'mockup-flow-' + cfg.mockupFlowId + '.json';
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		});

		// Import canvas from JSON file
		$wrap.on('click', '.pn-cm-mf-btn-import', function () {
			$wrap.find('.pn-cm-mf-import-file').val('').trigger('click');
		});

		$wrap.on('change', '.pn-cm-mf-import-file', function (e) {
			var file = e.target.files[0];
			if (!file) return;

			var reader = new FileReader();
			reader.onload = function (ev) {
				try {
					var parsed = JSON.parse(ev.target.result);
					if (!parsed || (!Array.isArray(parsed.mockups) && !Array.isArray(parsed.connections) && !Array.isArray(parsed.labels))) {
						showMsg(i18n.importError || 'Invalid JSON file.', 'error');
						return;
					}

					if (!confirm(i18n.importConfirm || 'This will replace all current data. Are you sure?')) return;

					applyImport(parsed);
				} catch (err) {
					console.error('[pn-cm-mf] Import parse error:', err);
					showMsg(i18n.importError || 'Invalid JSON file.', 'error');
				}
			};
			reader.readAsText(file);
		});

		function showMsg(text, type) {
			if (typeof pn_customers_manager_get_main_message === 'function') {
				pn_customers_manager_get_main_message(text, type);
			} else {
				alert(text);
			}
		}

		function applyImport(parsed) {
			var importedImageUrls = parsed._imageUrls || {};
			var hasImages = Object.keys(importedImageUrls).length > 0;

			if (!hasImages) {
				// No images to import — apply directly
				finishImport(parsed, {});
				return;
			}

			// Show importing overlay
			var $overlay = $('<div class="pn-cm-mf-import-overlay"><div class="pn-cm-mf-import-progress"><span class="pn-cm-mf-spinner"></span><p>' + (i18n.importingImages || 'Importing images…') + '</p></div></div>');
			$wrap.append($overlay);

			// Send image URLs to server to download into media library
			$.post(cfg.ajaxUrl, {
				action: 'pn_customers_manager_ajax',
				pn_customers_manager_ajax_type: 'pn_cm_mockup_flow_import_images',
				pn_customers_manager_ajax_nonce: cfg.nonce,
				mockup_flow_id: cfg.mockupFlowId,
				image_map: JSON.stringify(importedImageUrls)
			}, function (response) {
				$overlay.remove();
				try {
					var res = typeof response === 'string' ? JSON.parse(response) : response;
					if (res.error_key !== '') {
						showMsg(res.error_content || (i18n.importError || 'Error importing images.'), 'error');
						return;
					}
					finishImport(parsed, res.id_map || {});
					if (res.warnings && res.warnings.length) {
						console.warn('[pn-cm-mf] Import image warnings:', res.warnings);
					}
				} catch (err) {
					console.error('[pn-cm-mf] Import images parse error:', err);
					// Apply import anyway without remapping
					finishImport(parsed, {});
				}
			}).fail(function () {
				$overlay.remove();
				showMsg(i18n.importError || 'Error importing images.', 'error');
			});
		}

		function finishImport(parsed, idMap) {
			var mockups = parsed.mockups || [];

			// Remap image IDs if we have a mapping from the server
			if (idMap && Object.keys(idMap).length > 0) {
				mockups.forEach(function (m) {
					if (m.desktop_image_id && idMap[m.desktop_image_id]) {
						m.desktop_image_id = idMap[m.desktop_image_id];
					}
					if (m.mobile_image_id && idMap[m.mobile_image_id]) {
						m.mobile_image_id = idMap[m.mobile_image_id];
					}
				});
			}

			state.mockups = mockups;
			state.connections = parsed.connections || [];
			state.labels = parsed.labels || [];
			state.projectName = parsed.projectName || '';
			state.dirty = true;

			// Update project name input
			$wrap.find('.pn-cm-mf-project-name-input').val(state.projectName);

			// Refresh imageUrls cache — fetch thumbnails for all new IDs
			var allIds = [];
			state.mockups.forEach(function (m) {
				if (m.desktop_image_id) allIds.push(m.desktop_image_id);
				if (m.mobile_image_id) allIds.push(m.mobile_image_id);
			});

			if (allIds.length > 0) {
				var postData = {
					action: 'pn_customers_manager_ajax',
					pn_customers_manager_ajax_type: 'pn_cm_mockup_flow_get_image_urls',
					pn_customers_manager_ajax_nonce: cfg.nonce
				};
				allIds.forEach(function (id, i) {
					postData['attachment_ids[' + i + ']'] = id;
				});

				$.post(cfg.ajaxUrl, postData, function (response) {
					try {
						var res = typeof response === 'string' ? JSON.parse(response) : response;
						if (res.error_key === '' && res.urls) {
							for (var aid in res.urls) {
								if (!imageUrls[aid]) imageUrls[aid] = {};
								if (res.urls[aid].thumbnail) imageUrls[aid].thumbnail = res.urls[aid].thumbnail;
								if (res.urls[aid].medium) imageUrls[aid].medium = res.urls[aid].medium;
								if (res.urls[aid].full) imageUrls[aid].full = res.urls[aid].full;
							}
						}
					} catch (e) { /* ignore */ }
					selectNode(null, null);
					renderAll();
				}).fail(function () {
					selectNode(null, null);
					renderAll();
				});
			} else {
				selectNode(null, null);
				renderAll();
			}

			showMsg(i18n.importSuccess || 'Imported successfully. Remember to save.', 'success');
		}

		// Download all mockups as ZIP
		$wrap.on('click', '.pn-cm-mf-btn-download-all', function () {
			var $btn = $(this);
			if ($btn.hasClass('pn-cm-mf-downloading')) return;
			$btn.addClass('pn-cm-mf-downloading');

			// Show plugin loader next to the button
			var $loader = $('<span class="pn-customers-manager-waiting"><span class="pn-customers-manager-loader-circle-waiting"><div></div><div></div><div></div></span></span>');
			$btn.after($loader);

			// Use a hidden iframe as target so the page doesn't navigate away
			var iframeName = 'pn-cm-mf-dl-frame-' + Date.now();
			var $iframe = $('<iframe name="' + iframeName + '" style="display:none;"></iframe>');
			var $form = $('<form method="post" action="' + cfg.ajaxUrl + '" target="' + iframeName + '" style="display:none;"></form>');
			$form.append('<input type="hidden" name="action" value="pn_customers_manager_ajax">');
			$form.append('<input type="hidden" name="pn_customers_manager_ajax_nonce" value="' + cfg.nonce + '">');
			$form.append('<input type="hidden" name="pn_customers_manager_ajax_type" value="pn_cm_mockup_flow_download_all">');
			$form.append('<input type="hidden" name="post_id" value="' + cfg.mockupFlowId + '">');
			$('body').append($iframe).append($form);
			$form.submit();

			// Clean up after download has had time to start
			setTimeout(function () {
				$form.remove();
				$iframe.remove();
				$loader.remove();
				$btn.removeClass('pn-cm-mf-downloading');
			}, 5000);
		});
	}

	function cancelConnect() {
		state.connectingFrom = null;
		$wrap.find('.pn-cm-mf-connect-status').hide();
		$wrap.find('.pn-cm-mf-btn-cancel-connect').hide();
		$wrap.find('.pn-cm-mf-btn-connect').show();
	}

	// ── Public Init ─────────────────────────────────────────

	function initPublic() {
		$wrap = $('.pn-cm-mf-public-wrap');
		console.log('[pn-cm-mf] initPublic: .pn-cm-mf-public-wrap found:', $wrap.length);
		if (!$wrap.length) {
			console.warn('[pn-cm-mf] initPublic: NO .pn-cm-mf-public-wrap element found in the DOM. Check if the shortcode/block rendered the HTML.');
			return;
		}

		$canvasWrap = $wrap.find('.pn-cm-mf-public-canvas-wrap');
		$zoomLayer = $wrap.find('.pn-cm-mf-zoom-layer');
		$canvas = $wrap.find('.pn-cm-mf-canvas');
		$svg = $wrap.find('.pn-cm-mf-canvas-svg');

		console.log('[pn-cm-mf] initPublic elements:', {
			canvasWrap: $canvasWrap.length,
			zoomLayer: $zoomLayer.length,
			canvas: $canvas.length,
			svg: $svg.length,
			mockupsCount: state.mockups.length
		});

		state.panX = 40;
		state.panY = 40;
		applyTransform();

		renderAll();
		bindCanvasEvents($canvasWrap);
		bindTouchEvents($canvasWrap);
		bindPublicEvents();
		console.log('[pn-cm-mf] initPublic: done, rendered', state.mockups.length, 'mockups');
	}

	function bindPublicEvents() {
		// Filter buttons (exclude download-all button)
		$wrap.on('click', '.pn-cm-mf-filter-btn[data-filter]', function () {
			$wrap.find('.pn-cm-mf-filter-btn[data-filter]').removeClass('active');
			$(this).addClass('active');
			var filter = $(this).data('filter');
			renderAll(filter);
		});

		// Download all mockups as ZIP
		$wrap.on('click', '.pn-cm-mf-download-all-btn', function () {
			var $btn = $(this);
			if ($btn.hasClass('pn-cm-mf-downloading')) return;
			$btn.addClass('pn-cm-mf-downloading');

			// Show plugin loader next to the button
			var $loader = $('<span class="pn-customers-manager-waiting"><span class="pn-customers-manager-loader-circle-waiting"><div></div><div></div><div></div></span></span>');
			$btn.after($loader);

			var actionName = cfg.canComment ? 'pn_customers_manager_ajax' : 'pn_customers_manager_ajax_nopriv';
			var nonceKey = cfg.canComment ? 'pn_customers_manager_ajax_nonce' : 'pn_customers_manager_ajax_nopriv_nonce';
			var typeKey = cfg.canComment ? 'pn_customers_manager_ajax_type' : 'pn_customers_manager_ajax_nopriv_type';

			// Use a hidden iframe as target so the page doesn't navigate away
			var iframeName = 'pn-cm-mf-dl-frame-' + Date.now();
			var $iframe = $('<iframe name="' + iframeName + '" style="display:none;"></iframe>');
			var $form = $('<form method="post" action="' + cfg.ajaxUrl + '" target="' + iframeName + '" style="display:none;"></form>');
			$form.append('<input type="hidden" name="action" value="' + actionName + '">');
			$form.append('<input type="hidden" name="' + nonceKey + '" value="' + cfg.nonce + '">');
			$form.append('<input type="hidden" name="' + typeKey + '" value="pn_cm_mockup_flow_download_all">');
			$form.append('<input type="hidden" name="post_id" value="' + cfg.mockupFlowId + '">');
			$('body').append($iframe).append($form);
			$form.submit();

			// Clean up after download has had time to start
			setTimeout(function () {
				$form.remove();
				$iframe.remove();
				$loader.remove();
				$btn.removeClass('pn-cm-mf-downloading');
			}, 5000);
		});

		// Click on mockup to open popup
		$wrap.on('click', '.pn-cm-mf-node', function (e) {
			if (state.isPanning) return;
			var mockupId = $(this).data('mockup-id');
			openMockupPopup(mockupId);
		});
	}

	// ── Canvas Events (shared) ──────────────────────────────

	function bindCanvasEvents($container) {
		// Wheel zoom
		$container[0].addEventListener('wheel', function (e) {
			e.preventDefault();
			var rect = $container[0].getBoundingClientRect();
			var mouseX = e.clientX - rect.left;
			var mouseY = e.clientY - rect.top;

			var oldZoom = state.zoom;
			var delta = e.deltaY > 0 ? -0.1 : 0.1;
			state.zoom = Math.max(0.2, Math.min(3, state.zoom + delta));

			// Zoom toward cursor
			state.panX = mouseX - (mouseX - state.panX) * (state.zoom / oldZoom);
			state.panY = mouseY - (mouseY - state.panY) * (state.zoom / oldZoom);

			applyTransform();
			updateZoomDisplay();
		}, { passive: false });

		// Zoom buttons
		$wrap.on('click', '.pn-cm-mf-btn-zoom-in', function () {
			zoomToCenter(Math.min(3, state.zoom + 0.1));
		});
		$wrap.on('click', '.pn-cm-mf-btn-zoom-out', function () {
			zoomToCenter(Math.max(0.2, state.zoom - 0.1));
		});
		$wrap.on('click', '.pn-cm-mf-btn-zoom-reset', function () {
			state.zoom = 1;
			state.panX = 0;
			state.panY = 0;
			applyTransform();
			updateZoomDisplay();
		});

		// Fullscreen toggle
		$wrap.on('click', '.pn-cm-mf-btn-fullscreen', function () {
			var el = $wrap[0];
			var isFs = document.fullscreenElement || document.webkitFullscreenElement;
			if (!isFs) {
				var rfs = el.requestFullscreen || el.webkitRequestFullscreen;
				if (rfs) {
					rfs.call(el);
				} else {
					// Fallback for browsers without Fullscreen API
					$wrap.addClass('pn-cm-mf-is-fullscreen');
					updateFullscreenIcon();
				}
			} else {
				var efs = document.exitFullscreen || document.webkitExitFullscreen;
				if (efs) {
					efs.call(document);
				} else {
					$wrap.removeClass('pn-cm-mf-is-fullscreen');
					updateFullscreenIcon();
				}
			}
		});

		function onFullscreenChange() {
			var isFs = document.fullscreenElement || document.webkitFullscreenElement;
			if (isFs) {
				$wrap.addClass('pn-cm-mf-is-fullscreen');
			} else {
				$wrap.removeClass('pn-cm-mf-is-fullscreen');
			}
			updateFullscreenIcon();
		}

		document.addEventListener('fullscreenchange', onFullscreenChange);
		document.addEventListener('webkitfullscreenchange', onFullscreenChange);

		function updateFullscreenIcon() {
			var isFs = $wrap.hasClass('pn-cm-mf-is-fullscreen');
			$wrap.find('.pn-cm-mf-btn-fullscreen .dashicons')
				.toggleClass('dashicons-fullscreen-alt', !isFs)
				.toggleClass('dashicons-fullscreen-exit-alt', isFs);
			$wrap.find('.pn-cm-mf-btn-fullscreen').attr('title', isFs ? (i18n.exitFullscreen || 'Exit fullscreen') : (i18n.fullscreen || 'Fullscreen'));
		}

		// Escape key to exit fullscreen fallback
		$(document).on('keydown.pnCmMfFullscreen', function (e) {
			if (e.key === 'Escape' && $wrap.hasClass('pn-cm-mf-is-fullscreen') && !document.fullscreenElement && !document.webkitFullscreenElement) {
				$wrap.removeClass('pn-cm-mf-is-fullscreen');
				updateFullscreenIcon();
			}
		});

		// Pan and drag
		$container.on('mousedown', function (e) {
			// Right-click or middle-click: pan
			if (e.button === 1) {
				e.preventDefault();
				startPan(e);
				return;
			}

			if (e.button !== 0) return;

			var $target = $(e.target);
			var $node = $target.closest('.pn-cm-mf-node');
			var $label = $target.closest('.pn-cm-mf-label');
			var $connLabel = $target.closest('.pn-cm-mf-connection-label');

			if (isAdmin) {
				// Connecting mode
				if (state.connectingFrom !== null || $wrap.find('.pn-cm-mf-connect-status:visible').length) {
					if ($node.length) {
						handleConnectClick($node.data('mockup-id'));
					}
					return;
				}

				// Drag node
				if ($node.length) {
					e.preventDefault();
					var mockupId = $node.data('mockup-id');
					selectNode(mockupId, 'mockup');
					var m = findMockup(mockupId);
					if (m) {
						state.dragNode = { type: 'mockup', id: mockupId };
						var pos = canvasFromClient(e.clientX, e.clientY);
						state.dragOffsetX = pos.x - m.x;
						state.dragOffsetY = pos.y - m.y;
					}
					return;
				}

				// Drag label
				if ($label.length) {
					e.preventDefault();
					var labelId = $label.data('label-id');
					selectNode(labelId, 'label');
					var lbl = findLabel(labelId);
					if (lbl) {
						state.dragNode = { type: 'label', id: labelId };
						var pos = canvasFromClient(e.clientX, e.clientY);
						state.dragOffsetX = pos.x - lbl.x;
						state.dragOffsetY = pos.y - lbl.y;
					}
					return;
				}

				// Select connection
				if ($connLabel.length) {
					var connId = $connLabel.data('connection-id');
					selectNode(connId, 'connection');
					return;
				}
			} else {
				// Public: don't drag, but allow pan on empty canvas
				if ($node.length || $connLabel.length) return;
			}

			// Click on SVG path (connection)
			if ($target.is('path') && isAdmin) {
				var connId = $target.data('connection-id');
				if (connId) {
					selectNode(connId, 'connection');
					return;
				}
			}

			// Deselect
			if (!$node.length && !$label.length && !$connLabel.length && !$target.is('path')) {
				if (isAdmin) {
					selectNode(null, null);
				}
				startPan(e);
			}
		});

		$(document).on('mousemove', function (e) {
			if (state.isPanning) {
				state.panX += e.clientX - state.panStartX;
				state.panY += e.clientY - state.panStartY;
				state.panStartX = e.clientX;
				state.panStartY = e.clientY;
				applyTransform();
				return;
			}

			if (state.dragNode && isAdmin) {
				var pos = canvasFromClient(e.clientX, e.clientY);
				if (state.dragNode.type === 'mockup') {
					var m = findMockup(state.dragNode.id);
					if (m) {
						m.x = snapToGrid(Math.max(0, pos.x - state.dragOffsetX));
						m.y = snapToGrid(Math.max(0, pos.y - state.dragOffsetY));
						positionNode($('.pn-cm-mf-node[data-mockup-id="' + m.id + '"]'), m);
						renderConnections();
					}
				} else if (state.dragNode.type === 'label') {
					var lbl = findLabel(state.dragNode.id);
					if (lbl) {
						lbl.x = snapToGrid(Math.max(0, pos.x - state.dragOffsetX));
						lbl.y = snapToGrid(Math.max(0, pos.y - state.dragOffsetY));
						$('.pn-cm-mf-label[data-label-id="' + lbl.id + '"]').css({ left: lbl.x + 'px', top: lbl.y + 'px' });
					}
				}
				state.dirty = true;
			}
		});

		$(document).on('mouseup', function () {
			state.isPanning = false;
			state.dragNode = null;
		});

		// Double-click to edit label inline (admin)
		if (isAdmin) {
			$container.on('dblclick', '.pn-cm-mf-label', function () {
				var $el = $(this);
				if ($el.attr('contenteditable') === 'true') return;
				$el.attr('contenteditable', 'true').addClass('pn-cm-mf-label-editing').focus();

				$el.one('blur', function () {
					$el.attr('contenteditable', 'false').removeClass('pn-cm-mf-label-editing');
					var labelId = $el.data('label-id');
					var lbl = findLabel(labelId);
					if (lbl) {
						lbl.text = $el.text().trim();
						state.dirty = true;
						renderSidebar();
					}
				});
			});

			// Double-click on mockup to open edit popup
			$container.on('dblclick', '.pn-cm-mf-node', function () {
				var mockupId = $(this).data('mockup-id');
				openEditMockupPopup(mockupId);
			});
		}
	}

	function startPan(e) {
		state.isPanning = true;
		state.panStartX = e.clientX;
		state.panStartY = e.clientY;
	}

	// ── Touch Events ───────────────────────────────────────

	function bindTouchEvents($container) {
		var el = $container[0];

		el.addEventListener('touchstart', function (e) {
			// Let taps on UI controls (zoom, fullscreen, toolbar) pass through
			var touchTarget = e.touches[0] ? document.elementFromPoint(e.touches[0].clientX, e.touches[0].clientY) : null;
			if (touchTarget && $(touchTarget).closest('.pn-cm-mf-zoom-controls, .pn-cm-mf-toolbar, button, a, input, select, textarea').length) {
				return; // Don't intercept — let native click fire
			}

			if (e.touches.length === 2) {
				// Pinch-to-zoom start
				e.preventDefault();
				var t1 = e.touches[0], t2 = e.touches[1];
				state.pinchStartDist = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
				state.pinchStartZoom = state.zoom;
				var rect = el.getBoundingClientRect();
				state.pinchMidX = ((t1.clientX + t2.clientX) / 2) - rect.left;
				state.pinchMidY = ((t1.clientY + t2.clientY) / 2) - rect.top;
				// Cancel any ongoing pan/drag
				state.isPanning = false;
				state.dragNode = null;
				state.isTouchDrag = false;
				return;
			}

			if (e.touches.length !== 1) return;

			var touch = e.touches[0];
			var $target = $(touchTarget || document.elementFromPoint(touch.clientX, touch.clientY));
			var $node = $target.closest('.pn-cm-mf-node');
			var $label = $target.closest('.pn-cm-mf-label');

			if (isAdmin) {
				// Connecting mode
				if (state.connectingFrom !== null || $wrap.find('.pn-cm-mf-connect-status:visible').length) {
					if ($node.length) {
						handleConnectClick($node.data('mockup-id'));
					}
					return;
				}

				// Drag node
				if ($node.length) {
					e.preventDefault();
					var mockupId = $node.data('mockup-id');
					selectNode(mockupId, 'mockup');
					var m = findMockup(mockupId);
					if (m) {
						state.dragNode = { type: 'mockup', id: mockupId };
						state.isTouchDrag = true;
						var pos = canvasFromClient(touch.clientX, touch.clientY);
						state.dragOffsetX = pos.x - m.x;
						state.dragOffsetY = pos.y - m.y;
						state.touchId = touch.identifier;
					}
					return;
				}

				// Drag label
				if ($label.length) {
					e.preventDefault();
					var labelId = $label.data('label-id');
					selectNode(labelId, 'label');
					var lbl = findLabel(labelId);
					if (lbl) {
						state.dragNode = { type: 'label', id: labelId };
						state.isTouchDrag = true;
						var pos = canvasFromClient(touch.clientX, touch.clientY);
						state.dragOffsetX = pos.x - lbl.x;
						state.dragOffsetY = pos.y - lbl.y;
						state.touchId = touch.identifier;
					}
					return;
				}
			} else {
				// Public: tap on node opens popup (handled via click), don't pan from node
				if ($node.length) return;
			}

			// Pan on empty canvas
			e.preventDefault();
			state.isPanning = true;
			state.panStartX = touch.clientX;
			state.panStartY = touch.clientY;
			state.touchId = touch.identifier;
		}, { passive: false });

		el.addEventListener('touchmove', function (e) {
			if (e.touches.length === 2) {
				// Pinch-to-zoom move
				e.preventDefault();
				var t1 = e.touches[0], t2 = e.touches[1];
				var dist = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
				if (state.pinchStartDist > 0) {
					var rect = el.getBoundingClientRect();
					var midX = ((t1.clientX + t2.clientX) / 2) - rect.left;
					var midY = ((t1.clientY + t2.clientY) / 2) - rect.top;
					var oldZoom = state.zoom;
					var newZoom = Math.max(0.2, Math.min(3, state.pinchStartZoom * (dist / state.pinchStartDist)));
					state.zoom = newZoom;
					state.panX = midX - (midX - state.panX) * (newZoom / oldZoom);
					state.panY = midY - (midY - state.panY) * (newZoom / oldZoom);
					applyTransform();
					updateZoomDisplay();
				}
				return;
			}

			if (e.touches.length !== 1) return;
			var touch = e.touches[0];

			if (state.isPanning) {
				e.preventDefault();
				state.panX += touch.clientX - state.panStartX;
				state.panY += touch.clientY - state.panStartY;
				state.panStartX = touch.clientX;
				state.panStartY = touch.clientY;
				applyTransform();
				return;
			}

			if (state.isTouchDrag && state.dragNode && isAdmin) {
				e.preventDefault();
				var pos = canvasFromClient(touch.clientX, touch.clientY);
				if (state.dragNode.type === 'mockup') {
					var m = findMockup(state.dragNode.id);
					if (m) {
						m.x = snapToGrid(Math.max(0, pos.x - state.dragOffsetX));
						m.y = snapToGrid(Math.max(0, pos.y - state.dragOffsetY));
						positionNode($('.pn-cm-mf-node[data-mockup-id="' + m.id + '"]'), m);
						renderConnections();
					}
				} else if (state.dragNode.type === 'label') {
					var lbl = findLabel(state.dragNode.id);
					if (lbl) {
						lbl.x = snapToGrid(Math.max(0, pos.x - state.dragOffsetX));
						lbl.y = snapToGrid(Math.max(0, pos.y - state.dragOffsetY));
						$('.pn-cm-mf-label[data-label-id="' + lbl.id + '"]').css({ left: lbl.x + 'px', top: lbl.y + 'px' });
					}
				}
				state.dirty = true;
			}
		}, { passive: false });

		el.addEventListener('touchend', function (e) {
			if (e.touches.length === 0) {
				state.isPanning = false;
				state.dragNode = null;
				state.isTouchDrag = false;
				state.touchId = null;
				state.pinchStartDist = 0;
			} else if (e.touches.length === 1) {
				// Went from 2 fingers to 1: start pan with remaining finger
				state.pinchStartDist = 0;
				var touch = e.touches[0];
				state.isPanning = true;
				state.panStartX = touch.clientX;
				state.panStartY = touch.clientY;
				state.touchId = touch.identifier;
			}
		}, { passive: false });

		el.addEventListener('touchcancel', function () {
			state.isPanning = false;
			state.dragNode = null;
			state.isTouchDrag = false;
			state.touchId = null;
			state.pinchStartDist = 0;
		});
	}

	// ── Transform helpers ───────────────────────────────────

	function applyTransform() {
		if (!$zoomLayer) return;
		$zoomLayer.css('transform', 'translate(' + state.panX + 'px, ' + state.panY + 'px) scale(' + state.zoom + ')');
	}

	function updateZoomDisplay() {
		var label = Math.round(state.zoom * 100) + '%';
		$wrap.find('.pn-cm-mf-zoom-display').text(label);
		$wrap.find('.pn-cm-mf-zoom-controls-level').text(label);
	}

	function zoomToCenter(newZoom) {
		if (!$canvasWrap || !$canvasWrap.length) return;
		var rect = $canvasWrap[0].getBoundingClientRect();
		var mx = rect.width / 2;
		var my = rect.height / 2;
		var cx = (mx - state.panX) / state.zoom;
		var cy = (my - state.panY) / state.zoom;
		state.zoom = newZoom;
		state.panX = mx - cx * newZoom;
		state.panY = my - cy * newZoom;
		applyTransform();
		updateZoomDisplay();
	}

	var GRID_SIZE = 20;

	function snapToGrid(val) {
		return Math.round(val / GRID_SIZE) * GRID_SIZE;
	}

	function canvasFromClient(cx, cy) {
		var rect = $canvasWrap[0].getBoundingClientRect();
		return {
			x: (cx - rect.left - state.panX) / state.zoom,
			y: (cy - rect.top - state.panY) / state.zoom
		};
	}

	function getViewportCenter() {
		if (!$canvasWrap || !$canvasWrap.length) return { x: 400, y: 300 };
		var rect = $canvasWrap[0].getBoundingClientRect();
		return canvasFromClient(rect.left + rect.width / 2, rect.top + rect.height / 2);
	}

	// ── Rendering ───────────────────────────────────────────

	function renderAll(filter) {
		filter = filter || 'all';
		console.log('[pn-cm-mf] renderAll: filter=' + filter, 'mockups=' + state.mockups.length, 'connections=' + state.connections.length, 'labels=' + state.labels.length);
		renderMockups(filter);
		renderLabels();
		renderConnections(filter);
		if (isAdmin) renderSidebar();
	}

	function renderMockups(filter) {
		filter = filter || 'all';
		$canvas.find('.pn-cm-mf-node').remove();

		state.mockups.forEach(function (m) {
			// Filtering
			if (filter === 'desktop' && !m.desktop_image_id) return;
			if (filter === 'mobile' && !m.mobile_image_id) return;

			var imgId = m.desktop_image_id;
			if (filter === 'mobile') imgId = m.mobile_image_id;
			else if (filter === 'all' && !m.desktop_image_id && m.mobile_image_id) imgId = m.mobile_image_id;

			var thumbUrl = '';
			if (imgId && imageUrls[imgId]) {
				thumbUrl = imageUrls[imgId].thumbnail || imageUrls[imgId].medium || '';
			}

			var selectedClass = (state.selectedNode === m.id && state.selectedType === 'mockup') ? ' pn-cm-mf-node-selected' : '';

			var html = '<div class="pn-cm-mf-node' + selectedClass + '" data-mockup-id="' + m.id + '">';
			if (thumbUrl) {
				html += '<img class="pn-cm-mf-node-thumbnail" src="' + thumbUrl + '" alt="" draggable="false" />';
			} else {
				html += '<div class="pn-cm-mf-node-no-image"><span class="dashicons dashicons-format-image"></span></div>';
			}
			html += '<div class="pn-cm-mf-node-title">' + escHtml(m.title) + '</div>';
			html += '<div class="pn-cm-mf-node-order">' + m.order + '</div>';
			html += '</div>';

			var $node = $(html);
			positionNode($node, m);
			$canvas.append($node);
		});
	}

	function renderLabels() {
		$canvas.find('.pn-cm-mf-label').remove();

		state.labels.forEach(function (lbl) {
			var selectedClass = (state.selectedNode === lbl.id && state.selectedType === 'label') ? ' pn-cm-mf-label-selected' : '';
			var $el = $('<div class="pn-cm-mf-label' + selectedClass + '" data-label-id="' + lbl.id + '">' + escHtml(lbl.text) + '</div>');
			$el.css({
				left: lbl.x + 'px',
				top: lbl.y + 'px',
				fontSize: lbl.fontSize + 'px',
				color: lbl.color
			});
			$canvas.append($el);
		});
	}

	function renderConnections(filter) {
		filter = filter || 'all';
		$svg.find('path').remove();
		$canvas.find('.pn-cm-mf-connection-label').remove();

		state.connections.forEach(function (conn) {
			var fromM = findMockup(conn.from);
			var toM = findMockup(conn.to);
			if (!fromM || !toM) return;

			// Skip connections to filtered-out mockups
			if (filter === 'desktop' && (!fromM.desktop_image_id || !toM.desktop_image_id)) return;
			if (filter === 'mobile' && (!fromM.mobile_image_id || !toM.mobile_image_id)) return;

			var anchors = getBestAnchors(fromM, toM);
			var path = buildBezierPath(anchors.from, anchors.to);

			var selectedClass = (state.selectedNode === conn.id && state.selectedType === 'connection') ? ' selected' : '';
			var markerUrl = isAdmin ? 'url(#pn-cm-mf-arrowhead)' : 'url(#pn-cm-mf-arrowhead-public)';

			var $path = $(document.createElementNS('http://www.w3.org/2000/svg', 'path'));
			$path.attr({
				d: path,
				fill: 'none',
				stroke: selectedClass ? 'var(--pn-customers-manager-color-main, #0073aa)' : '#999',
				'stroke-width': selectedClass ? 3 : 2,
				'marker-end': markerUrl
			});
			$path.attr('data-connection-id', conn.id);
			$svg.append($path);

			// Connection label
			if (conn.label) {
				var mid = getMidpoint(anchors.from, anchors.to);
				var selClass = (state.selectedNode === conn.id && state.selectedType === 'connection') ? ' pn-cm-mf-connection-label-selected' : '';
				var $lbl = $('<div class="pn-cm-mf-connection-label' + selClass + '" data-connection-id="' + conn.id + '">' + escHtml(conn.label) + '</div>');
				$lbl.css({ left: mid.x + 'px', top: mid.y + 'px' });
				$canvas.append($lbl);
			}
		});
	}

	function positionNode($node, m) {
		$node.css({
			left: m.x + 'px',
			top: m.y + 'px',
			width: m.width + 'px',
			height: m.height + 'px'
		});
	}

	// ── Connection geometry ─────────────────────────────────

	function getBestAnchors(from, to) {
		var fCx = from.x + from.width / 2, fCy = from.y + from.height / 2;
		var tCx = to.x + to.width / 2, tCy = to.y + to.height / 2;

		var sides = ['top', 'right', 'bottom', 'left'];
		var fAnchors = getAnchors(from);
		var tAnchors = getAnchors(to);

		var bestDist = Infinity, bestFrom = null, bestTo = null;

		sides.forEach(function (fs) {
			sides.forEach(function (ts) {
				var d = distance(fAnchors[fs], tAnchors[ts]);
				if (d < bestDist) {
					bestDist = d;
					bestFrom = fAnchors[fs];
					bestFrom.side = fs;
					bestTo = tAnchors[ts];
					bestTo.side = ts;
				}
			});
		});

		return { from: bestFrom, to: bestTo };
	}

	function getAnchors(m) {
		return {
			top: { x: m.x + m.width / 2, y: m.y },
			right: { x: m.x + m.width, y: m.y + m.height / 2 },
			bottom: { x: m.x + m.width / 2, y: m.y + m.height },
			left: { x: m.x, y: m.y + m.height / 2 }
		};
	}

	function buildBezierPath(from, to) {
		var offset = 60;
		var cp1 = { x: from.x, y: from.y };
		var cp2 = { x: to.x, y: to.y };

		switch (from.side) {
			case 'top': cp1.y -= offset; break;
			case 'bottom': cp1.y += offset; break;
			case 'left': cp1.x -= offset; break;
			case 'right': cp1.x += offset; break;
		}
		switch (to.side) {
			case 'top': cp2.y -= offset; break;
			case 'bottom': cp2.y += offset; break;
			case 'left': cp2.x -= offset; break;
			case 'right': cp2.x += offset; break;
		}

		return 'M' + from.x + ',' + from.y + ' C' + cp1.x + ',' + cp1.y + ' ' + cp2.x + ',' + cp2.y + ' ' + to.x + ',' + to.y;
	}

	function getMidpoint(a, b) {
		return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
	}

	function distance(a, b) {
		return Math.sqrt((a.x - b.x) * (a.x - b.x) + (a.y - b.y) * (a.y - b.y));
	}

	// ── Selection & Sidebar ─────────────────────────────────

	function selectNode(id, type) {
		state.selectedNode = id;
		state.selectedType = type;
		renderAll(getCurrentFilter());
	}

	function getCurrentFilter() {
		if (!$wrap) return 'all';
		var $active = $wrap.find('.pn-cm-mf-filter-btn.active');
		return $active.length ? $active.data('filter') : 'all';
	}

	function renderSidebar() {
		if (!$sidebar) return;
		$sidebar.empty();

		if (!state.selectedNode) {
			$sidebar.html('<p class="pn-cm-mf-no-selection">' + (i18n.noSelection || '') + '</p>');
			return;
		}

		if (state.selectedType === 'mockup') renderMockupSidebar();
		else if (state.selectedType === 'label') renderLabelSidebar();
		else if (state.selectedType === 'connection') renderConnectionSidebar();
	}

	function renderMockupSidebar() {
		var m = findMockup(state.selectedNode);
		if (!m) return;

		var html = '';
		html += sidebarField(i18n.title, '<input type="text" class="pn-customers-manager-input pn-cm-mf-sb-title" value="' + escAttr(m.title) + '" />');
		html += sidebarField(i18n.order, '<input type="number" class="pn-customers-manager-input pn-cm-mf-sb-order" value="' + m.order + '" min="1" />');
		html += sidebarField(i18n.description, '<textarea class="pn-customers-manager-input pn-cm-mf-sb-desc">' + escHtml(m.description) + '</textarea>');

		// Desktop image
		var dThumb = (m.desktop_image_id && imageUrls[m.desktop_image_id]) ? imageUrls[m.desktop_image_id].thumbnail : '';
		html += sidebarField(i18n.desktopImage,
			(dThumb ? '<img class="pn-cm-mf-sidebar-image-preview" src="' + dThumb + '" />' : '') +
			'<div class="pn-cm-mf-sidebar-image-row"><button type="button" class="button pn-cm-mf-sb-pick-desktop">' + (i18n.selectImage || 'Select') + '</button>' +
			(m.desktop_image_id ? '<button type="button" class="button button-link-delete pn-cm-mf-sb-remove-desktop">' + (i18n.removeImage || 'Remove') + '</button>' : '') + '</div>');

		// Mobile image
		var mThumb = (m.mobile_image_id && imageUrls[m.mobile_image_id]) ? imageUrls[m.mobile_image_id].thumbnail : '';
		html += sidebarField(i18n.mobileImage,
			(mThumb ? '<img class="pn-cm-mf-sidebar-image-preview" src="' + mThumb + '" />' : '') +
			'<div class="pn-cm-mf-sidebar-image-row"><button type="button" class="button pn-cm-mf-sb-pick-mobile">' + (i18n.selectImage || 'Select') + '</button>' +
			(m.mobile_image_id ? '<button type="button" class="button button-link-delete pn-cm-mf-sb-remove-mobile">' + (i18n.removeImage || 'Remove') + '</button>' : '') + '</div>');

		html += '<div class="pn-cm-mf-sidebar-actions">';
		html += '<button type="button" class="button button-link-delete pn-cm-mf-sb-delete">' + (i18n.delete || 'Delete') + '</button>';
		html += '</div>';

		$sidebar.html(html);

		// Bind sidebar events
		$sidebar.find('.pn-cm-mf-sb-title').on('input', function () { m.title = $(this).val(); state.dirty = true; renderMockups(getCurrentFilter()); });
		$sidebar.find('.pn-cm-mf-sb-order').on('input', function () { m.order = parseInt($(this).val()) || 1; state.dirty = true; renderMockups(getCurrentFilter()); });
		$sidebar.find('.pn-cm-mf-sb-desc').on('input', function () { m.description = $(this).val(); state.dirty = true; });

		$sidebar.find('.pn-cm-mf-sb-pick-desktop').on('click', function () { pickImage(m, 'desktop_image_id'); });
		$sidebar.find('.pn-cm-mf-sb-pick-mobile').on('click', function () { pickImage(m, 'mobile_image_id'); });
		$sidebar.find('.pn-cm-mf-sb-remove-desktop').on('click', function () {
			if (!confirm(i18n.confirmRemoveImage || i18n.confirmDelete)) return;
			m.desktop_image_id = 0; state.dirty = true; renderAll(); selectNode(m.id, 'mockup');
		});
		$sidebar.find('.pn-cm-mf-sb-remove-mobile').on('click', function () {
			if (!confirm(i18n.confirmRemoveImage || i18n.confirmDelete)) return;
			m.mobile_image_id = 0; state.dirty = true; renderAll(); selectNode(m.id, 'mockup');
		});

		$sidebar.find('.pn-cm-mf-sb-delete').on('click', function () {
			if (!confirm(i18n.confirmDelete)) return;
			state.mockups = state.mockups.filter(function (x) { return x.id !== m.id; });
			state.connections = state.connections.filter(function (c) { return c.from !== m.id && c.to !== m.id; });
			state.dirty = true;
			selectNode(null, null);
		});
	}

	function renderLabelSidebar() {
		var lbl = findLabel(state.selectedNode);
		if (!lbl) return;

		var html = '';
		html += sidebarField(i18n.text, '<input type="text" class="pn-customers-manager-input pn-cm-mf-sb-label-text" value="' + escAttr(lbl.text) + '" />');
		html += sidebarField(i18n.fontSize, '<input type="number" class="pn-customers-manager-input pn-cm-mf-sb-font-size" value="' + lbl.fontSize + '" min="8" max="72" />');
		html += sidebarField(i18n.color, '<input type="color" class="pn-cm-mf-sb-color" value="' + lbl.color + '" />');
		html += '<div class="pn-cm-mf-sidebar-actions"><button type="button" class="button button-link-delete pn-cm-mf-sb-delete-label">' + (i18n.delete || 'Delete') + '</button></div>';

		$sidebar.html(html);

		$sidebar.find('.pn-cm-mf-sb-label-text').on('input', function () { lbl.text = $(this).val(); state.dirty = true; renderLabels(); });
		$sidebar.find('.pn-cm-mf-sb-font-size').on('input', function () { lbl.fontSize = parseInt($(this).val()) || 16; state.dirty = true; renderLabels(); });
		$sidebar.find('.pn-cm-mf-sb-color').on('input', function () { lbl.color = $(this).val(); state.dirty = true; renderLabels(); });

		$sidebar.find('.pn-cm-mf-sb-delete-label').on('click', function () {
			if (!confirm(i18n.confirmDelete)) return;
			state.labels = state.labels.filter(function (x) { return x.id !== lbl.id; });
			state.dirty = true;
			selectNode(null, null);
		});
	}

	function renderConnectionSidebar() {
		var conn = findConnection(state.selectedNode);
		if (!conn) return;

		var html = '';
		html += sidebarField(i18n.connectionLabel || 'Label', '<input type="text" class="pn-customers-manager-input pn-cm-mf-sb-conn-label" value="' + escAttr(conn.label || '') + '" />');
		html += '<div class="pn-cm-mf-sidebar-actions"><button type="button" class="button button-link-delete pn-cm-mf-sb-delete-conn">' + (i18n.delete || 'Delete') + '</button></div>';

		$sidebar.html(html);

		$sidebar.find('.pn-cm-mf-sb-conn-label').on('input', function () { conn.label = $(this).val(); state.dirty = true; renderConnections(getCurrentFilter()); });

		$sidebar.find('.pn-cm-mf-sb-delete-conn').on('click', function () {
			if (!confirm(i18n.confirmDelete)) return;
			state.connections = state.connections.filter(function (x) { return x.id !== conn.id; });
			state.dirty = true;
			selectNode(null, null);
		});
	}

	function sidebarField(label, content) {
		return '<div class="pn-cm-mf-sidebar-field"><label>' + (label || '') + '</label>' + content + '</div>';
	}

	// ── Connect mode ────────────────────────────────────────

	function handleConnectClick(mockupId) {
		if (state.connectingFrom === null) {
			state.connectingFrom = mockupId;
			$wrap.find('.pn-cm-mf-connect-status').text(i18n.clickTarget);
		} else {
			if (state.connectingFrom !== mockupId) {
				// Check for duplicate connection
				var exists = state.connections.some(function (c) {
					return c.from === state.connectingFrom && c.to === mockupId;
				});
				if (!exists) {
					state.connections.push({
						id: 'c_' + Date.now(),
						from: state.connectingFrom,
						to: mockupId,
						label: ''
					});
					state.dirty = true;
				}
			}
			cancelConnect();
			renderAll();
		}
	}

	// ── Image picker ────────────────────────────────────────

	/**
	 * Return the correct parent element for appending overlays (popups,
	 * lightboxes). When the panel is in native fullscreen the top-layer
	 * element is the fullscreen element; anything appended to <body>
	 * would be hidden behind it.
	 */
	function getOverlayParent() {
		var fsEl = document.fullscreenElement || document.webkitFullscreenElement;
		return fsEl ? $(fsEl) : $('body');
	}


	function pickImage(mockup, field) {
		var frame = wp.media({
			title: i18n.selectImage || 'Select Image',
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			mockup[field] = attachment.id;
			imageUrls[attachment.id] = {
				thumbnail: (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url,
				medium: (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url,
				full: attachment.url
			};
			state.dirty = true;
			renderAll();
			selectNode(mockup.id, 'mockup');
		});

		frame.open();
	}

	// ── Edit mockup popup (admin double-click) ──────────────

	function openEditMockupPopup(mockupId) {
		var m = findMockup(mockupId);
		if (!m) return;

		var dThumb = (m.desktop_image_id && imageUrls[m.desktop_image_id]) ? imageUrls[m.desktop_image_id].thumbnail : '';
		var mThumb = (m.mobile_image_id && imageUrls[m.mobile_image_id]) ? imageUrls[m.mobile_image_id].thumbnail : '';

		var html = '<div class="pn-cm-mf-popup-inner" style="padding:20px; max-width:500px;">';
		html += '<h3>' + (i18n.editMockup || 'Edit Mockup') + '</h3>';
		html += '<div class="pn-cm-mf-sidebar-field"><label>' + (i18n.title || 'Title') + '</label><input type="text" class="pn-cm-mf-popup-title widefat" value="' + escAttr(m.title) + '" /></div>';
		html += '<div class="pn-cm-mf-sidebar-field"><label>' + (i18n.description || 'Description') + '</label><textarea class="pn-cm-mf-popup-desc widefat" rows="3">' + escHtml(m.description) + '</textarea></div>';
		html += '<div class="pn-cm-mf-sidebar-field"><label>' + (i18n.order || 'Order') + '</label><input type="number" class="pn-cm-mf-popup-order" value="' + m.order + '" min="1" /></div>';

		html += '<div class="pn-cm-mf-sidebar-field"><label>' + (i18n.desktopImage || 'Desktop Image') + '</label>';
		html += '<div class="pn-cm-mf-popup-desktop-preview">' + (dThumb ? '<img src="' + dThumb + '" style="max-height:80px;margin-bottom:6px;" />' : '') + '</div>';
		html += '<button type="button" class="button pn-cm-mf-popup-pick-desktop">' + (i18n.selectImage || 'Select') + '</button></div>';

		html += '<div class="pn-cm-mf-sidebar-field"><label>' + (i18n.mobileImage || 'Mobile Image') + '</label>';
		html += '<div class="pn-cm-mf-popup-mobile-preview">' + (mThumb ? '<img src="' + mThumb + '" style="max-height:80px;margin-bottom:6px;" />' : '') + '</div>';
		html += '<button type="button" class="button pn-cm-mf-popup-pick-mobile">' + (i18n.selectImage || 'Select') + '</button></div>';

		html += '<div style="margin-top:16px;display:flex;gap:8px;">';
		html += '<button type="button" class="button button-primary pn-cm-mf-popup-save-btn">' + (i18n.save || 'Save') + '</button>';
		html += '<button type="button" class="button pn-cm-mf-popup-cancel-btn">' + (i18n.cancelConnect || 'Cancel') + '</button>';
		html += '</div></div>';

		var $popup = $('<div class="pn-cm-mf-viewer-popup pn-cm-mf-edit-popup"><div class="pn-cm-mf-viewer-overlay"></div><div class="pn-cm-mf-viewer-inner" style="max-width:500px;">' + html + '</div></div>');
		getOverlayParent().append($popup);

		$popup.find('.pn-cm-mf-popup-pick-desktop').on('click', function () {
			pickImageForPopup(m, 'desktop_image_id', $popup.find('.pn-cm-mf-popup-desktop-preview'));
		});
		$popup.find('.pn-cm-mf-popup-pick-mobile').on('click', function () {
			pickImageForPopup(m, 'mobile_image_id', $popup.find('.pn-cm-mf-popup-mobile-preview'));
		});

		$popup.find('.pn-cm-mf-popup-save-btn').on('click', function () {
			m.title = $popup.find('.pn-cm-mf-popup-title').val();
			m.description = $popup.find('.pn-cm-mf-popup-desc').val();
			m.order = parseInt($popup.find('.pn-cm-mf-popup-order').val()) || 1;
			state.dirty = true;
			$popup.remove();
			renderAll();
			selectNode(m.id, 'mockup');
		});

		$popup.find('.pn-cm-mf-popup-cancel-btn, .pn-cm-mf-viewer-overlay').on('click', function () {
			$popup.remove();
		});
	}

	function pickImageForPopup(mockup, field, $previewContainer) {
		var frame = wp.media({
			title: i18n.selectImage || 'Select Image',
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			mockup[field] = attachment.id;
			imageUrls[attachment.id] = {
				thumbnail: (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url,
				medium: (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url,
				full: attachment.url
			};
			state.dirty = true;
			var thumb = imageUrls[attachment.id].thumbnail;
			$previewContainer.html('<img src="' + thumb + '" style="max-height:80px;margin-bottom:6px;" />');
		});

		frame.open();
	}

	// ── Save canvas ─────────────────────────────────────────

	function saveCanvas() {
		console.log('[pn-cm-mf] saveCanvas: saving', state.mockups.length, 'mockups,', state.connections.length, 'connections,', state.labels.length, 'labels');
		var data = {
			action: 'pn_customers_manager_ajax',
			pn_customers_manager_ajax_type: 'pn_cm_mockup_flow_save_canvas',
			pn_customers_manager_ajax_nonce: cfg.nonce,
			mockup_flow_id: cfg.mockupFlowId,
			canvas_data: JSON.stringify({
				mockups: state.mockups,
				connections: state.connections,
				labels: state.labels,
				projectName: state.projectName
			})
		};

		$.post(cfg.ajaxUrl, data, function (response) {
			console.log('[pn-cm-mf] saveCanvas response:', response);
			try {
				var res = typeof response === 'string' ? JSON.parse(response) : response;
				if (res.error_key === '') {
					state.dirty = false;
					console.log('[pn-cm-mf] saveCanvas: success');
					if (typeof pn_customers_manager_get_main_message === 'function') {
						pn_customers_manager_get_main_message(i18n.saved, 'success');
					} else {
						alert(i18n.saved || 'Saved!');
					}
				} else {
					console.error('[pn-cm-mf] saveCanvas: error:', res);
					if (typeof pn_customers_manager_get_main_message === 'function') {
						pn_customers_manager_get_main_message(res.error_content || i18n.saveError, 'error');
					} else {
						alert(res.error_content || i18n.saveError || 'Error saving');
					}
				}
			} catch (e) {
				console.error('[pn-cm-mf] saveCanvas: parse error:', e, 'raw response:', response);
				if (typeof pn_customers_manager_get_main_message === 'function') {
					pn_customers_manager_get_main_message(i18n.saveError, 'error');
				} else {
					alert(i18n.saveError || 'Error saving');
				}
			}
		}).fail(function(xhr, status, error) {
			console.error('[pn-cm-mf] saveCanvas AJAX failed:', status, error, xhr.responseText);
		});
	}

	// ── Public popup ────────────────────────────────────────

	function openMockupPopup(mockupId) {
		var sorted = state.mockups.slice().sort(function (a, b) { return a.order - b.order; });
		var idx = sorted.findIndex(function (m) { return m.id === mockupId; });
		if (idx === -1) return;

		// Use the active filter to determine the default view mode
		var $activeFilter = $wrap.find('.pn-cm-mf-filter-btn.active');
		var filter = $activeFilter.length ? $activeFilter.data('filter') : 'all';
		var defaultView = (filter === 'mobile') ? 'mobile' : 'desktop';

		showPopupForIndex(sorted, idx, defaultView);
	}

	function showPopupForIndex(sorted, idx, viewMode) {
		$('.pn-cm-mf-viewer-popup').remove();

		var m = sorted[idx];
		var hasDesktop = m.desktop_image_id > 0;
		var hasMobile = m.mobile_image_id > 0;

		if (!viewMode || viewMode === 'desktop') {
			viewMode = hasDesktop ? 'desktop' : (hasMobile ? 'mobile' : 'desktop');
		}

		var imgId = viewMode === 'mobile' ? m.mobile_image_id : m.desktop_image_id;

		var html = '<div class="pn-cm-mf-popup-inner">';

		// Header
		html += '<div class="pn-cm-mf-popup-header"><h3>' + escHtml(m.title) + '</h3>';
		html += '<div class="pn-cm-mf-popup-header-actions">';
		if (hasDesktop && hasMobile) {
			html += '<div class="pn-cm-mf-popup-toggle">';
			html += '<button type="button" data-view="desktop" class="pn-customers-manager-tooltip ' + (viewMode === 'desktop' ? 'active' : '') + '" data-pn-customers-manager-tooltip="' + escAttr(i18n.desktop || 'Desktop') + '"><i class="material-icons-outlined">desktop_windows</i></button>';
			html += '<button type="button" data-view="mobile" class="pn-customers-manager-tooltip ' + (viewMode === 'mobile' ? 'active' : '') + '" data-pn-customers-manager-tooltip="' + escAttr(i18n.mobile || 'Mobile') + '"><i class="material-icons-outlined">smartphone</i></button>';
			html += '</div>';
		}
		html += '<button type="button" class="pn-cm-mf-popup-fullscreen-btn pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + escAttr(i18n.fullscreen || 'Fullscreen') + '"><span class="pn-cm-mf-icon-fullscreen">&#x26F6;</span></button>';
		html += '<button type="button" class="pn-cm-mf-popup-close-btn pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + escAttr(i18n.close || 'Close') + '">&times;</button>';
		html += '</div>';
		html += '</div>';

		// Image (loading spinner, replaced by image via AJAX)
		html += '<div class="pn-cm-mf-popup-image-wrap">';
		html += '<div class="pn-cm-mf-popup-image-loader"><span class="pn-cm-mf-spinner"></span></div>';
		html += '</div>';

		// Description
		if (m.description) {
			html += '<div class="pn-cm-mf-popup-description">' + escHtml(m.description) + '</div>';
		}

		// Navigation
		html += '<div class="pn-cm-mf-popup-nav">';
		html += '<button type="button" class="pn-cm-mf-popup-prev"' + (idx === 0 ? ' disabled' : '') + '>' + (i18n.previous || 'Prev') + '</button>';
		html += '<span class="pn-cm-mf-popup-nav-counter">' + (idx + 1) + ' / ' + sorted.length + '</span>';
		html += '<button type="button" class="pn-cm-mf-popup-next"' + (idx === sorted.length - 1 ? ' disabled' : '') + '>' + (i18n.next || 'Next') + '</button>';
		html += '</div>';

		// Comments
		html += '<div class="pn-cm-mf-comments-section" data-mockup-id="' + m.id + '">';
		html += '<h4 class="pn-cm-mf-comments-title">' + (i18n.comments || 'Comments') + '</h4>';
		html += '<div class="pn-cm-mf-comments-list"><p class="pn-cm-mf-no-comments">' + (i18n.noComments || '') + '</p></div>';

		if (cfg.canComment) {
			html += '<div class="pn-cm-mf-comment-form">';
			html += '<textarea placeholder="' + (i18n.addComment || 'Add comment') + '"></textarea>';
			html += '<button type="button">' + (i18n.send || 'Send') + '</button>';
			html += '</div>';
		} else if (!cfg.isAdmin) {
			html += '<p class="pn-cm-mf-login-notice">' + (i18n.loginToComment || '') + '</p>';
		}

		html += '</div>';
		html += '</div>';

		var $popup = $('<div class="pn-cm-mf-viewer-popup"><div class="pn-cm-mf-viewer-overlay"></div><div class="pn-cm-mf-viewer-inner">' + html + '</div></div>');
		getOverlayParent().append($popup);

		// Load comments
		loadComments(m.id, $popup);

		// Load image via AJAX (or from cache)
		loadPopupImage(imgId, m.title, $popup);

		// Events
		$popup.find('.pn-cm-mf-viewer-overlay, .pn-cm-mf-popup-close-btn').on('click', function () { $popup.remove(); $(document).off('keydown.pnCmMfPopup'); });

		$popup.find('.pn-cm-mf-popup-prev').on('click', function () {
			if (idx > 0) showPopupForIndex(sorted, idx - 1, viewMode);
		});

		$popup.find('.pn-cm-mf-popup-next').on('click', function () {
			if (idx < sorted.length - 1) showPopupForIndex(sorted, idx + 1, viewMode);
		});

		$popup.find('.pn-cm-mf-popup-toggle button').on('click', function () {
			var newView = $(this).data('view');
			showPopupForIndex(sorted, idx, newView);
		});

		// Fullscreen toggle
		$popup.find('.pn-cm-mf-popup-fullscreen-btn').on('click', function () {
			$popup.toggleClass('pn-cm-mf-popup-fullscreen');
			var isFs = $popup.hasClass('pn-cm-mf-popup-fullscreen');
			$(this).find('.pn-cm-mf-icon-fullscreen').html(isFs ? '&#x2716;' : '&#x26F6;');
			$(this).attr('title', isFs ? (i18n.exitFullscreen || 'Exit fullscreen') : (i18n.fullscreen || 'Fullscreen'));
		});

		// Submit comment
		$popup.find('.pn-cm-mf-comment-form button').on('click', function () {
			var $ta = $popup.find('.pn-cm-mf-comment-form textarea');
			var text = $ta.val().trim();
			if (!text) return;
			submitComment(m.id, text, $popup);
			$ta.val('');
		});

		// Keyboard nav
		$(document).off('keydown.pnCmMfPopup').on('keydown.pnCmMfPopup', function (e) {
			if (!$('.pn-cm-mf-viewer-popup').length) return;
			if (e.key === 'Escape') { $popup.remove(); $(document).off('keydown.pnCmMfPopup'); }
			if (e.key === 'ArrowLeft' && idx > 0) showPopupForIndex(sorted, idx - 1, viewMode);
			if (e.key === 'ArrowRight' && idx < sorted.length - 1) showPopupForIndex(sorted, idx + 1, viewMode);
		});
	}

	// ── Popup image AJAX loading ────────────────────────────

	function loadPopupImage(imgId, title, $popup) {
		var $wrap = $popup.find('.pn-cm-mf-popup-image-wrap');

		if (!imgId) {
			$wrap.find('.pn-cm-mf-popup-image-loader').remove();
			return;
		}

		// If we already have full/medium cached, render immediately
		if (imageUrls[imgId] && (imageUrls[imgId].full || imageUrls[imgId].medium)) {
			var url = imageUrls[imgId].full || imageUrls[imgId].medium;
			renderPopupImage(url, title, $wrap, imgId);
			return;
		}

		// Fetch full/medium URLs via AJAX
		var actionName = cfg.canComment ? 'pn_customers_manager_ajax' : 'pn_customers_manager_ajax_nopriv';
		var nonceKey = cfg.canComment ? 'pn_customers_manager_ajax_nonce' : 'pn_customers_manager_ajax_nopriv_nonce';
		var typeKey = cfg.canComment ? 'pn_customers_manager_ajax_type' : 'pn_customers_manager_ajax_nopriv_type';

		var postData = { action: actionName };
		postData[nonceKey] = cfg.nonce;
		postData[typeKey] = 'pn_cm_mockup_flow_get_image_urls';
		postData['attachment_ids[]'] = imgId;

		$.post(cfg.ajaxUrl, postData, function (response) {
			try {
				var res = typeof response === 'string' ? JSON.parse(response) : response;
				if (res.error_key === '' && res.urls && res.urls[imgId]) {
					// Cache for future use
					if (!imageUrls[imgId]) imageUrls[imgId] = {};
					imageUrls[imgId].full = res.urls[imgId].full || '';
					imageUrls[imgId].medium = res.urls[imgId].medium || '';
					imageUrls[imgId].original = res.urls[imgId].original || '';

					var url = imageUrls[imgId].full || imageUrls[imgId].medium;
					if (url) {
						renderPopupImage(url, title, $wrap, imgId);
					} else {
						$wrap.find('.pn-cm-mf-popup-image-loader').remove();
					}
				} else {
					$wrap.find('.pn-cm-mf-popup-image-loader').remove();
				}
			} catch (e) {
				console.error('[pn-cm-mf] loadPopupImage parse error:', e);
				$wrap.find('.pn-cm-mf-popup-image-loader').remove();
			}
		}).fail(function () {
			$wrap.find('.pn-cm-mf-popup-image-loader').remove();
		});
	}

	function renderPopupImage(imgUrl, title, $wrap, imgId) {
		$wrap.find('.pn-cm-mf-popup-image-loader').remove();
		var $img = $('<img src="' + imgUrl + '" alt="' + escAttr(title) + '" class="pn-cm-mf-popup-zoomable pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + escAttr(i18n.clickToZoom || 'Click to enlarge') + '" />');
		$wrap.append($img);
		$wrap.append('<span class="pn-cm-mf-zoom-hint"><i class="material-icons-outlined">search</i></span>');

		// Download button — always downloads the largest available image
		var downloadUrl = (imgId && imageUrls[imgId] && imageUrls[imgId].original) ? imageUrls[imgId].original : imgUrl;
		var $dlBtn = $('<a class="pn-cm-mf-download-hint pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + escAttr(i18n.downloadImage || 'Download') + '" href="' + downloadUrl + '" download><i class="material-icons-outlined">cloud_download</i></a>');
		$wrap.append($dlBtn);

		// Lightbox on click — use original (non-scaled) URL if available
		$img.on('click', function () {
			var originalUrl = (imgId && imageUrls[imgId] && imageUrls[imgId].original) ? imageUrls[imgId].original : '';
			openImageLightbox($(this).attr('src'), $(this).attr('alt') || '', originalUrl);
		});
	}

	// ── Image Lightbox (zoomable & pannable) ─────────────

	function openImageLightbox(src, alt, originalUrl) {
		$('.pn-cm-mf-lightbox').remove();

		var lbZoom = 1, lbPanX = 0, lbPanY = 0;
		var lbPanning = false, lbPanStartX = 0, lbPanStartY = 0;
		var lbPinchDist = 0, lbPinchZoom = 1;
		var lbMinZoom = 1, lbMaxZoom = 8;
		var bestSrc = originalUrl || src;

		var $lightbox = $(
			'<div class="pn-cm-mf-lightbox">' +
				'<div class="pn-cm-mf-lightbox-overlay"></div>' +
				'<div class="pn-cm-mf-lightbox-content">' +
					'<div class="pn-cm-mf-lightbox-img-wrap">' +
						'<img src="' + src + '" alt="' + escAttr(alt) + '" draggable="false" />' +
					'</div>' +
					'<div class="pn-cm-mf-lightbox-toolbar">' +
						'<button type="button" class="pn-cm-mf-lightbox-btn pn-cm-mf-lightbox-zoom-out pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + escAttr(i18n.zoomOut || 'Zoom out') + '">−</button>' +
						'<span class="pn-cm-mf-lightbox-zoom-level">100%</span>' +
						'<button type="button" class="pn-cm-mf-lightbox-btn pn-cm-mf-lightbox-zoom-in pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + escAttr(i18n.zoomIn || 'Zoom in') + '">+</button>' +
						'<button type="button" class="pn-cm-mf-lightbox-btn pn-cm-mf-lightbox-zoom-fit pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + escAttr(i18n.fitToScreen || 'Fit') + '">↺</button>' +
					'</div>' +
					'<a class="pn-cm-mf-lightbox-download pn-customers-manager-tooltip" href="' + bestSrc + '" download data-pn-customers-manager-tooltip="' + escAttr(i18n.downloadImage || 'Download') + '"><i class="material-icons-outlined">cloud_download</i></a>' +
					'<button type="button" class="pn-cm-mf-lightbox-close pn-customers-manager-tooltip" data-pn-customers-manager-tooltip="' + escAttr(i18n.close || 'Close') + '">&times;</button>' +
				'</div>' +
			'</div>'
		);

		// Append above everything: always to body to avoid stacking context issues
		$('body').append($lightbox);
		setTimeout(function () { $lightbox.addClass('pn-cm-mf-lightbox-active'); }, 10);

		var $imgWrap = $lightbox.find('.pn-cm-mf-lightbox-img-wrap');
		var $lbImg = $imgWrap.find('img');
		var imgEl = $imgWrap[0];

		// If an original (non-scaled) URL exists, preload it in the background and swap
		if (originalUrl) {
			var hiRes = new Image();
			hiRes.onload = function () {
				// Only swap if the lightbox is still open
				if ($lightbox.parent().length) {
					$lbImg.attr('src', originalUrl);
				}
			};
			hiRes.src = originalUrl;
		}

		function applyLbTransform() {
			$lbImg.css('transform', 'translate(' + lbPanX + 'px, ' + lbPanY + 'px) scale(' + lbZoom + ')');
			$lightbox.find('.pn-cm-mf-lightbox-zoom-level').text(Math.round(lbZoom * 100) + '%');
			// Show/hide pan cursor
			$imgWrap.toggleClass('pn-cm-mf-lightbox-zoomed', lbZoom > 1.05);
		}

		function lbZoomTo(newZoom) {
			lbZoom = Math.max(lbMinZoom, Math.min(lbMaxZoom, newZoom));
			if (lbZoom <= 1.05) { lbPanX = 0; lbPanY = 0; }
			applyLbTransform();
		}

		function closeLightbox() {
			$lightbox.removeClass('pn-cm-mf-lightbox-active');
			setTimeout(function () { $lightbox.remove(); }, 200);
			$(document).off('keydown.pnCmMfLightbox mousemove.pnCmMfLightbox mouseup.pnCmMfLightbox');
		}

		// Close handlers
		$lightbox.find('.pn-cm-mf-lightbox-overlay, .pn-cm-mf-lightbox-close').on('click', closeLightbox);

		// Zoom buttons
		$lightbox.find('.pn-cm-mf-lightbox-zoom-in').on('click', function (e) { e.stopPropagation(); lbZoomTo(lbZoom + 0.5); });
		$lightbox.find('.pn-cm-mf-lightbox-zoom-out').on('click', function (e) { e.stopPropagation(); lbZoomTo(lbZoom - 0.5); });
		$lightbox.find('.pn-cm-mf-lightbox-zoom-fit').on('click', function (e) { e.stopPropagation(); lbZoomTo(1); });

		// Double-click/tap to zoom
		$imgWrap.on('dblclick', function (e) {
			e.stopPropagation();
			if (lbZoom > 1.05) {
				lbZoomTo(1);
			} else {
				// Zoom to 3× centered on click point
				var rect = imgEl.getBoundingClientRect();
				var cx = e.clientX - rect.left - rect.width / 2;
				var cy = e.clientY - rect.top - rect.height / 2;
				var newZoom = 3;
				lbPanX = -cx * (newZoom - 1);
				lbPanY = -cy * (newZoom - 1);
				lbZoom = newZoom;
				applyLbTransform();
			}
		});

		// Mouse drag to pan when zoomed
		$imgWrap.on('mousedown', function (e) {
			if (lbZoom <= 1.05) return;
			e.preventDefault();
			lbPanning = true;
			lbPanStartX = e.clientX;
			lbPanStartY = e.clientY;
		});

		$(document).on('mousemove.pnCmMfLightbox', function (e) {
			if (!lbPanning) return;
			lbPanX += e.clientX - lbPanStartX;
			lbPanY += e.clientY - lbPanStartY;
			lbPanStartX = e.clientX;
			lbPanStartY = e.clientY;
			applyLbTransform();
		});

		$(document).on('mouseup.pnCmMfLightbox', function () {
			lbPanning = false;
		});

		// Wheel zoom
		imgEl.addEventListener('wheel', function (e) {
			e.preventDefault();
			var delta = e.deltaY > 0 ? -0.3 : 0.3;
			var rect = imgEl.getBoundingClientRect();
			var mx = e.clientX - rect.left - rect.width / 2;
			var my = e.clientY - rect.top - rect.height / 2;
			var oldZoom = lbZoom;
			var newZoom = Math.max(lbMinZoom, Math.min(lbMaxZoom, lbZoom + delta));
			lbPanX = lbPanX * (newZoom / oldZoom) - mx * (newZoom / oldZoom - 1);
			lbPanY = lbPanY * (newZoom / oldZoom) - my * (newZoom / oldZoom - 1);
			lbZoom = newZoom;
			if (lbZoom <= 1.05) { lbPanX = 0; lbPanY = 0; }
			applyLbTransform();
		}, { passive: false });

		// Touch: pinch-to-zoom + pan
		imgEl.addEventListener('touchstart', function (e) {
			e.preventDefault();
			if (e.touches.length === 2) {
				var t1 = e.touches[0], t2 = e.touches[1];
				lbPinchDist = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
				lbPinchZoom = lbZoom;
			} else if (e.touches.length === 1 && lbZoom > 1.05) {
				lbPanning = true;
				lbPanStartX = e.touches[0].clientX;
				lbPanStartY = e.touches[0].clientY;
			}
		}, { passive: false });

		imgEl.addEventListener('touchmove', function (e) {
			e.preventDefault();
			if (e.touches.length === 2 && lbPinchDist > 0) {
				var t1 = e.touches[0], t2 = e.touches[1];
				var dist = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
				var newZoom = Math.max(lbMinZoom, Math.min(lbMaxZoom, lbPinchZoom * (dist / lbPinchDist)));
				lbZoom = newZoom;
				if (lbZoom <= 1.05) { lbPanX = 0; lbPanY = 0; }
				applyLbTransform();
			} else if (e.touches.length === 1 && lbPanning) {
				var touch = e.touches[0];
				lbPanX += touch.clientX - lbPanStartX;
				lbPanY += touch.clientY - lbPanStartY;
				lbPanStartX = touch.clientX;
				lbPanStartY = touch.clientY;
				applyLbTransform();
			}
		}, { passive: false });

		imgEl.addEventListener('touchend', function (e) {
			if (e.touches.length < 2) lbPinchDist = 0;
			if (e.touches.length === 0) lbPanning = false;
		});

		// Keyboard
		$(document).off('keydown.pnCmMfLightbox').on('keydown.pnCmMfLightbox', function (e) {
			if (e.key === 'Escape') closeLightbox();
			if (e.key === '+' || e.key === '=') lbZoomTo(lbZoom + 0.5);
			if (e.key === '-') lbZoomTo(lbZoom - 0.5);
			if (e.key === '0') lbZoomTo(1);
		});
	}

	// ── Comments AJAX ───────────────────────────────────────

	function loadComments(mockupId, $popup) {
		var actionName = cfg.canComment ? 'pn_customers_manager_ajax' : 'pn_customers_manager_ajax_nopriv';
		console.log('[pn-cm-mf] loadComments:', mockupId, 'action:', actionName);
		$.post(cfg.ajaxUrl, {
			action: actionName,
			pn_customers_manager_ajax_type: 'pn_cm_mockup_flow_get_comments',
			pn_customers_manager_ajax_nonce: cfg.nonce,
			mockup_flow_id: cfg.mockupFlowId,
			mockup_id: mockupId
		}, function (response) {
			console.log('[pn-cm-mf] loadComments response:', response);
			try {
				var res = typeof response === 'string' ? JSON.parse(response) : response;
				if (res.error_key === '' && res.comments) {
					renderCommentsList(res.comments, $popup, mockupId);
				} else {
					console.warn('[pn-cm-mf] loadComments error:', res);
				}
			} catch (e) {
				console.error('[pn-cm-mf] loadComments parse error:', e, response);
			}
		}).fail(function(xhr, status, error) {
			console.error('[pn-cm-mf] loadComments AJAX failed:', status, error);
		});
	}

	function renderCommentsList(comments, $popup, mockupId) {
		var $list = $popup.find('.pn-cm-mf-comments-list');
		$list.empty();

		if (!comments.length) {
			$list.html('<p class="pn-cm-mf-no-comments">' + (i18n.noComments || '') + '</p>');
			return;
		}

		comments.forEach(function (c) {
			var resolved = parseInt(c.is_resolved);
			var html = '<div class="pn-cm-mf-comment" data-comment-id="' + c.id + '">';
			html += '<div class="pn-cm-mf-comment-header">';
			html += '<span class="pn-cm-mf-comment-user">' + escHtml(c.user_name) + '</span>';
			html += '<span class="pn-cm-mf-comment-date">' + escHtml(c.created_at) + '</span>';
			if (resolved) html += '<span class="pn-cm-mf-comment-resolved-badge">' + (i18n.resolved || 'Resolved') + '</span>';
			html += '</div>';
			html += '<div class="pn-cm-mf-comment-text">' + escHtml(c.comment_text) + '</div>';

			if (cfg.isAdminUser) {
				html += '<div class="pn-cm-mf-comment-actions">';
				html += '<button type="button" class="pn-cm-mf-resolve-btn">' + (resolved ? (i18n.reopen || 'Reopen') : (i18n.resolve || 'Resolve')) + '</button>';
				html += '<button type="button" class="pn-cm-mf-delete-comment-btn">' + (i18n.deleteComment || 'Delete') + '</button>';
				html += '</div>';
			}

			html += '</div>';
			$list.append(html);
		});

		// Resolve & delete events
		$list.find('.pn-cm-mf-resolve-btn').on('click', function () {
			var commentId = $(this).closest('.pn-cm-mf-comment').data('comment-id');
			resolveComment(commentId, $popup, mockupId);
		});

		$list.find('.pn-cm-mf-delete-comment-btn').on('click', function () {
			var commentId = $(this).closest('.pn-cm-mf-comment').data('comment-id');
			deleteComment(commentId, $popup, mockupId);
		});
	}

	function submitComment(mockupId, text, $popup) {
		var actionName = cfg.canComment ? 'pn_customers_manager_ajax' : 'pn_customers_manager_ajax_nopriv';
		$.post(cfg.ajaxUrl, {
			action: actionName,
			pn_customers_manager_ajax_type: 'pn_cm_mockup_flow_add_comment',
			pn_customers_manager_ajax_nonce: cfg.nonce,
			mockup_flow_id: cfg.mockupFlowId,
			mockup_id: mockupId,
			comment_text: text
		}, function (response) {
			var res = typeof response === 'string' ? JSON.parse(response) : response;
			if (res.error_key === '') {
				loadComments(mockupId, $popup);
			}
		});
	}

	function resolveComment(commentId, $popup, mockupId) {
		$.post(cfg.ajaxUrl, {
			action: 'pn_customers_manager_ajax',
			pn_customers_manager_ajax_type: 'pn_cm_mockup_flow_resolve_comment',
			pn_customers_manager_ajax_nonce: cfg.nonce,
			comment_id: commentId
		}, function (response) {
			var res = typeof response === 'string' ? JSON.parse(response) : response;
			if (res.error_key === '') loadComments(mockupId, $popup);
		});
	}

	function deleteComment(commentId, $popup, mockupId) {
		$.post(cfg.ajaxUrl, {
			action: 'pn_customers_manager_ajax',
			pn_customers_manager_ajax_type: 'pn_cm_mockup_flow_delete_comment',
			pn_customers_manager_ajax_nonce: cfg.nonce,
			comment_id: commentId
		}, function (response) {
			var res = typeof response === 'string' ? JSON.parse(response) : response;
			if (res.error_key === '') loadComments(mockupId, $popup);
		});
	}

	// ── Helpers ──────────────────────────────────────────────

	function findMockup(id) { return state.mockups.find(function (m) { return m.id === id; }); }
	function findLabel(id) { return state.labels.find(function (l) { return l.id === id; }); }
	function findConnection(id) { return state.connections.find(function (c) { return c.id === id; }); }

	function escHtml(str) {
		if (!str) return '';
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	function escAttr(str) {
		return escHtml(str);
	}

})(jQuery);
