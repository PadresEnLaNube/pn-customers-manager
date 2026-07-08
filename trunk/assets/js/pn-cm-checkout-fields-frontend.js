/**
 * PN Customers Manager - Checkout Fields Frontend
 *
 * Handles dynamic html_multi field groups in checkout
 */

(function($) {
	'use strict';

	// Debug helper
	function debug(message, data) {
		if (typeof pnCmCheckoutFields !== 'undefined' && pnCmCheckoutFields.debug) {
			console.log('[PN CM Debug]', message, data || '');
		}
	}

	// Get ajax URL safely
	function getAjaxUrl() {
		if (typeof pnCmCheckoutFields !== 'undefined' && pnCmCheckoutFields.ajax_url) {
			return pnCmCheckoutFields.ajax_url;
		}
		if (typeof pn_customers_manager_ajax !== 'undefined' && pn_customers_manager_ajax.ajax_url) {
			return pn_customers_manager_ajax.ajax_url;
		}
		return '/wp-admin/admin-ajax.php';
	}

	// Get translated string
	function __(key) {
		if (typeof pnCmCheckoutFields !== 'undefined' && pnCmCheckoutFields.i18n && pnCmCheckoutFields.i18n[key]) {
			return pnCmCheckoutFields.i18n[key];
		}
		return key;
	}

	$(document).ready(function() {
		debug('Initializing checkout fields');
		debug('Ajax URL:', getAjaxUrl());
		debug('Popup object available:', typeof pn_customers_manager_Popups !== 'undefined');

		initHtmlMultiFields();
		initCsvExport();
	});

	function initHtmlMultiFields() {
		// Add group
		$('.pn-cm-add-html-multi-group').on('click', function(e) {
			e.preventDefault();
			var $button = $(this);
			var fieldId = $button.data('field-id');
			var fieldName = $button.data('field-name');
			var $wrapper = $button.closest('.pn-cm-html-multi-wrapper');
			var $container = $wrapper.find('.pn-cm-html-multi-groups-container');

			// Check if it's product page (uses field-name) or checkout (uses field-id)
			var templateId = fieldName ? (fieldName + '-product-template') : (fieldId + '-template');
			var $template = $('#' + templateId);

			if (!$template.length) {
				return;
			}

			// Get next index
			var nextIndex = $container.find('.pn-cm-html-multi-group').length;

			// Get template and replace {{INDEX}}
			var html = $template.html().replace(/\{\{INDEX\}\}/g, nextIndex);

			// Append new group
			var $newGroup = $(html);
			$container.append($newGroup);

			// Show remove button on all groups
			$container.find('.pn-cm-remove-html-multi-group').show();

			// Hide remove button if only one group
			if ($container.find('.pn-cm-html-multi-group').length === 1) {
				$container.find('.pn-cm-remove-html-multi-group').hide();
			}

			// Scroll to new group
			$('html, body').animate({
				scrollTop: $newGroup.offset().top - 100
			}, 300);
		});

		// Remove group
		$(document).on('click', '.pn-cm-remove-html-multi-group', function(e) {
			e.preventDefault();
			var $button = $(this);
			var $group = $button.closest('.pn-cm-html-multi-group');
			var $container = $group.closest('.pn-cm-html-multi-groups-container');

			$group.fadeOut(300, function() {
				$(this).remove();

				// Re-index remaining groups
				$container.find('.pn-cm-html-multi-group').each(function(index) {
					$(this).attr('data-index', index);
					// Update field names
					$(this).find('input, select, textarea').each(function() {
						var name = $(this).attr('name');
						if (name) {
							// Replace [old_index] with [new_index]
							var newName = name.replace(/\[\d+\]/, '[' + index + ']');
							$(this).attr('name', newName);
						}
					});
				});

				// Hide remove button if only one group left
				if ($container.find('.pn-cm-html-multi-group').length === 1) {
					$container.find('.pn-cm-remove-html-multi-group').hide();
				}
			});
		});

		// Hide remove button on single groups initially
		$('.pn-cm-html-multi-wrapper').each(function() {
			var $container = $(this).find('.pn-cm-html-multi-groups-container');
			if ($container.find('.pn-cm-html-multi-group').length === 1) {
				$container.find('.pn-cm-remove-html-multi-group').hide();
			}
		});
	}

	function initCsvExport() {
		// Open popup
		$(document).on('click', '.pn-cm-open-csv-popup', function(e) {
			e.preventDefault();
			var productId = $(this).data('product-id');

			openCsvExportPopup(productId);
		});

		// Download CSV
		$(document).on('click', '.pn-cm-export-csv-download', function(e) {
			e.preventDefault();
			var $popup = $(this).closest('.pn-customers-manager-popup');
			var productId = $popup.data('product-id');

			downloadCsv(productId);
		});
	}

	function openCsvExportPopup(productId) {
		debug('Opening CSV export popup for product:', productId);

		var popupId = 'pn-cm-csv-popup-' + productId;

		// Check if overlay exists, if not create it
		if ($('.pn-customers-manager-popup-overlay').length === 0) {
			$('body').append('<div class="pn-customers-manager-popup-overlay pn-customers-manager-display-none-soft" style="z-index: 9998;"></div>');
			debug('Created popup overlay');
		}

		// Remove existing popup if present
		$('#' + popupId).remove();

		// Create popup content
		var content = '<div class="pn-cm-csv-export-popup">' +
			'<h2>' + __('export_orders_csv') + '</h2>' +
			'<div class="pn-cm-csv-loading">' +
				'<div class="pn-customers-manager-loader-circle-wrapper">' +
					'<div class="pn-customers-manager-text-align-center">' +
						'<div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div>' +
					'</div>' +
				'</div>' +
				'<p>' + __('loading_preview') + '</p>' +
			'</div>' +
			'<div class="pn-cm-csv-column-selection">' +
				'<h3>' + __('select_columns') + '</h3>' +
				'<div class="pn-cm-csv-columns-list"></div>' +
			'</div>' +
			'<div class="pn-cm-csv-preview-container">' +
				'<h3>' + __('data_preview') + '</h3>' +
				'<div class="pn-cm-csv-preview"></div>' +
			'</div>' +
			'<div class="pn-cm-csv-footer">' +
				'<button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-btn-transparent" onclick="pn_customers_manager_Popups.close()">' + __('cancel') + '</button>' +
				' <button type="button" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-cm-export-csv-download">' + __('download_csv') + '</button>' +
			'</div>' +
		'</div>';

		// Create popup element with proper structure
		var $popup = $('<div>', {
			id: popupId,
			'class': 'pn-customers-manager-popup pn-customers-manager-popup-size-full-size pn-customers-manager-display-none-soft',
			html: '<div class="pn-customers-manager-popup-content">' + content + '</div>'
		});

		// Append to body
		$('body').append($popup);
		debug('Created popup element:', popupId);

		// Store product ID in popup
		$popup.data('product-id', productId);

		// Open popup using the popup system
		pn_customers_manager_Popups.open(popupId);
		debug('Opened popup');

		// Load preview
		loadCsvPreview(productId, $popup);
	}

	function loadCsvPreview(productId, $popup) {
		debug('Loading CSV preview for product:', productId);

		var $loading = $popup.find('.pn-cm-csv-loading');
		var $columnSelection = $popup.find('.pn-cm-csv-column-selection');
		var $previewContainer = $popup.find('.pn-cm-csv-preview-container');
		var $footer = $popup.find('.pn-cm-csv-footer');

		$loading.show();
		$columnSelection.hide();
		$previewContainer.hide();
		$footer.hide();

		var ajaxData = {
			action: 'pn_cm_get_csv_preview',
			product_id: productId,
			nonce: $('#pn-cm-csv-nonce-' + productId).val()
		};

		debug('Ajax request data:', ajaxData);

		$.ajax({
			url: getAjaxUrl(),
			type: 'POST',
			data: ajaxData,
			success: function(response) {
				debug('CSV preview response:', response);
				$loading.hide();

				if (response.success) {
					renderColumnSelection($popup, response.data.columns);
					renderPreview($popup, response.data.columns, response.data.preview, response.data.total_rows);
					$columnSelection.show();
					$previewContainer.show();
					$footer.show();
				} else {
					alert(response.data.message || __('error_loading_simple'));
				}
			},
			error: function(xhr, status, error) {
				debug('Ajax error:', {xhr: xhr, status: status, error: error});
				$loading.hide();
				alert(__('error_loading'));
			}
		});
	}

	function renderColumnSelection($popup, columns) {
		var $list = $popup.find('.pn-cm-csv-columns-list');
		$list.empty();

		columns.forEach(function(column) {
			var columnId = 'col-' + column.replace(/[^a-zA-Z0-9]/g, '-');
			var $item = $('<div class="pn-cm-csv-column-item">' +
				'<input type="checkbox" id="' + columnId + '" class="pn-cm-csv-column-checkbox" value="' + column + '" checked>' +
				'<label for="' + columnId + '" class="pn-cm-csv-column-label">' + column + '</label>' +
			'</div>');
			$list.append($item);
		});

		// Add event listener to update preview when columns change
		$popup.find('.pn-cm-csv-column-checkbox').on('change', function() {
			updatePreviewTable($popup);
		});
	}

	function renderPreview($popup, columns, preview, totalRows) {
		var $preview = $popup.find('.pn-cm-csv-preview');
		$preview.empty();

		if (preview.length === 0) {
			$preview.html('<p class="pn-cm-csv-preview-empty">' + __('no_orders_found') + '</p>');
			return;
		}

		var html = '<table><thead><tr>';
		columns.forEach(function(column) {
			html += '<th>' + column + '</th>';
		});
		html += '</tr></thead><tbody>';

		preview.forEach(function(row) {
			html += '<tr>';
			columns.forEach(function(column) {
				var value = row[column] || '';
				html += '<td>' + value + '</td>';
			});
			html += '</tr>';
		});

		html += '</tbody></table>';
		var showingText = __('showing_of_total').replace('%1$s', preview.length).replace('%2$s', totalRows);
		html += '<div class="pn-cm-csv-info-message">' + showingText + '</div>';

		$preview.html(html);

		// Store full data for filtering
		$popup.data('fullColumns', columns);
		$popup.data('fullPreview', preview);
		$popup.data('totalRows', totalRows);
	}

	function updatePreviewTable($popup) {
		debug('Updating preview table based on selected columns');

		// Get selected columns
		var selectedColumns = [];
		$popup.find('.pn-cm-csv-column-checkbox:checked').each(function() {
			selectedColumns.push($(this).val());
		});

		debug('Selected columns:', selectedColumns);

		// Get stored full data
		var fullColumns = $popup.data('fullColumns');
		var fullPreview = $popup.data('fullPreview');
		var totalRows = $popup.data('totalRows');

		if (!fullColumns || !fullPreview) {
			debug('No full data stored, skipping update');
			return;
		}

		// Filter columns to only include selected ones (maintain order)
		var filteredColumns = fullColumns.filter(function(col) {
			return selectedColumns.indexOf(col) !== -1;
		});

		debug('Filtered columns:', filteredColumns);

		// Re-render preview with filtered columns
		var $preview = $popup.find('.pn-cm-csv-preview');
		$preview.empty();

		if (filteredColumns.length === 0) {
			$preview.html('<p class="pn-cm-csv-preview-empty">' + __('select_one_column') + '</p>');
			return;
		}

		if (fullPreview.length === 0) {
			$preview.html('<p class="pn-cm-csv-preview-empty">' + __('no_orders_found') + '</p>');
			return;
		}

		var html = '<table><thead><tr>';
		filteredColumns.forEach(function(column) {
			html += '<th>' + column + '</th>';
		});
		html += '</tr></thead><tbody>';

		fullPreview.forEach(function(row) {
			html += '<tr>';
			filteredColumns.forEach(function(column) {
				var value = row[column] || '';
				html += '<td>' + value + '</td>';
			});
			html += '</tr>';
		});

		html += '</tbody></table>';
		var showingText = __('showing_of_total').replace('%1$s', fullPreview.length).replace('%2$s', totalRows);
		html += '<div class="pn-cm-csv-info-message">' + showingText + '</div>';

		$preview.html(html);
	}

	function downloadCsv(productId) {
		debug('Downloading CSV for product:', productId);

		var $popup = $('#pn-cm-csv-popup-' + productId);
		var selectedColumns = [];

		$popup.find('.pn-cm-csv-column-checkbox:checked').each(function() {
			selectedColumns.push($(this).val());
		});

		debug('Selected columns:', selectedColumns);

		if (selectedColumns.length === 0) {
			alert(__('select_one_column'));
			return;
		}

		// Create a form and submit it
		var $form = $('<form>', {
			method: 'POST',
			action: getAjaxUrl()
		});

		$form.append($('<input>', {
			type: 'hidden',
			name: 'action',
			value: 'pn_cm_export_csv'
		}));

		$form.append($('<input>', {
			type: 'hidden',
			name: 'product_id',
			value: productId
		}));

		$form.append($('<input>', {
			type: 'hidden',
			name: 'nonce',
			value: $('#pn-cm-csv-nonce-' + productId).val()
		}));

		selectedColumns.forEach(function(column) {
			$form.append($('<input>', {
				type: 'hidden',
				name: 'columns[]',
				value: column
			}));
		});

		$form.appendTo('body').submit().remove();

		// Close popup after a short delay
		setTimeout(function() {
			pn_customers_manager_Popups.close('pn-cm-csv-popup-' + productId);
		}, 500);
	}

})(jQuery);
