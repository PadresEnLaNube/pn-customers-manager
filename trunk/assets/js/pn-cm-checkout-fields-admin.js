/**
 * PN Customers Manager - Checkout Fields Admin
 *
 * Manages dynamic field addition, removal, and sorting in product edit screen
 */

(function($) {
	'use strict';

	var fieldIndex = 0;

	$(document).ready(function() {
		initCheckoutFields();
		initCsvExport();
	});

	function initCheckoutFields() {
		var $container = $('#pn-cm-checkout-fields-list');

		// Initialize index based on existing fields
		fieldIndex = $container.find('.pn-cm-checkout-field-row').length;

		// Sortable (drag and drop) for fields
		$container.sortable({
			handle: '.pn-cm-field-handle',
			placeholder: 'pn-cm-field-placeholder',
			start: function(e, ui) {
				ui.placeholder.height(ui.item.height());
			}
		});

		// Initialize sortable for existing options lists
		initOptionsSortable();

		// Add field
		$('.pn-cm-add-checkout-field').on('click', function(e) {
			e.preventDefault();
			addField();
		});

		// Toggle field (expand/collapse) - on button click
		$container.on('click', '.pn-cm-toggle-field', function(e) {
			e.preventDefault();
			e.stopPropagation();
			toggleField($(this).closest('.pn-cm-checkout-field-row'));
		});

		// Toggle field (expand/collapse) - on title click
		$container.on('click', '.pn-cm-field-title', function(e) {
			e.preventDefault();
			e.stopPropagation();
			toggleField($(this).closest('.pn-cm-checkout-field-row'));
		});

		// Toggle field (expand/collapse) - on header click (except handle and remove)
		$container.on('click', '.pn-cm-field-header', function(e) {
			// Don't toggle if clicking on handle or remove button
			if ($(e.target).closest('.pn-cm-field-handle, .pn-cm-remove-field').length) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			toggleField($(this).closest('.pn-cm-checkout-field-row'));
		});

		// Remove field
		$container.on('click', '.pn-cm-remove-field', function(e) {
			e.preventDefault();

			if (confirm(pnCmCheckoutFields.i18n.confirmRemove)) {
				$(this).closest('.pn-cm-checkout-field-row').fadeOut(300, function() {
					$(this).remove();
				});
			}
		});

		// Update field title when label changes
		$container.on('input', '.pn-cm-field-label-input', function() {
			var label = $(this).val() || pnCmCheckoutFields.i18n.newField;
			$(this).closest('.pn-cm-checkout-field-row').find('.pn-cm-field-title').text(label);
		});

		// Show/hide options based on field type
		$container.on('change', '.pn-cm-field-type', function() {
			var $row = $(this).closest('.pn-cm-checkout-field-row');
			updateFieldVisibility($row);
		});

		// Add option to list
		$container.on('click', '.pn-cm-add-option', function(e) {
			e.preventDefault();
			var $list = $(this).siblings('.pn-cm-options-list');
			var $newOption = $('<div class="pn-cm-option-item">' +
				'<span class="pn-cm-option-handle dashicons dashicons-menu"></span>' +
				'<input type="text" name="_pn_cm_checkout_fields[' + $list.data('field-index') + '][options_array][]" ' +
				'class="pn-cm-option-input" placeholder="Option value" />' +
				'<button type="button" class="button pn-cm-remove-option" title="Remove option">' +
				'<span class="dashicons dashicons-no"></span>' +
				'</button>' +
				'</div>');

			$list.append($newOption);
			$newOption.find('.pn-cm-option-input').focus();
			updateOptionsHiddenField($list);
		});

		// Remove option from list
		$container.on('click', '.pn-cm-remove-option', function(e) {
			e.preventDefault();
			var $item = $(this).closest('.pn-cm-option-item');
			var $list = $item.closest('.pn-cm-options-list');
			$item.fadeOut(200, function() {
				$(this).remove();
				updateOptionsHiddenField($list);
			});
		});

		// Update hidden field when option value changes
		$container.on('input', '.pn-cm-option-input', function() {
			var $list = $(this).closest('.pn-cm-options-list');
			updateOptionsHiddenField($list);
		});

		// Add html_multi field
		$container.on('click', '.pn-cm-add-html-multi-field', function(e) {
			e.preventDefault();
			var $list = $(this).siblings('.pn-cm-html-multi-fields-list');
			var fieldIndex = $list.data('field-index');
			var hmfIndex = $list.find('.pn-cm-html-multi-field-item').length;

			var $newField = $('<div class="pn-cm-html-multi-field-item">' +
				'<div style="display: flex; gap: 10px; margin-bottom: 5px; align-items: center;">' +
				'<input type="text" ' +
				'name="_pn_cm_checkout_fields[' + fieldIndex + '][html_multi_fields][' + hmfIndex + '][label]" ' +
				'placeholder="Field Label" style="flex: 2;" />' +
				'<select name="_pn_cm_checkout_fields[' + fieldIndex + '][html_multi_fields][' + hmfIndex + '][type]" ' +
				'style="flex: 1;">' +
				'<option value="text">Text</option>' +
				'<option value="email">Email</option>' +
				'<option value="tel">Phone</option>' +
				'<option value="number">Number</option>' +
				'<option value="date">Date</option>' +
				'<option value="textarea">Textarea</option>' +
				'</select>' +
				'<label style="margin: 0; display: flex; align-items: center; gap: 5px; white-space: nowrap;">' +
				'<input type="checkbox" ' +
				'name="_pn_cm_checkout_fields[' + fieldIndex + '][html_multi_fields][' + hmfIndex + '][required]" ' +
				'value="1" />' +
				'Required' +
				'</label>' +
				'<button type="button" class="button pn-cm-remove-html-multi-field" title="Remove field">' +
				'<span class="dashicons dashicons-no"></span>' +
				'</button>' +
				'</div>' +
				'</div>');

			$list.append($newField);
			$newField.find('input[type="text"]').focus();
		});

		// Remove html_multi field
		$container.on('click', '.pn-cm-remove-html-multi-field', function(e) {
			e.preventDefault();
			$(this).closest('.pn-cm-html-multi-field-item').fadeOut(200, function() {
				$(this).remove();
			});
		});

		// Open first field if exists
		if ($container.find('.pn-cm-checkout-field-row').length > 0) {
			toggleField($container.find('.pn-cm-checkout-field-row:first'));
		}
	}

	function initOptionsSortable() {
		$('.pn-cm-options-list').sortable({
			handle: '.pn-cm-option-handle',
			placeholder: 'pn-cm-option-placeholder',
			update: function(e, ui) {
				updateOptionsHiddenField($(this));
			}
		});
	}

	function toggleField($row) {
		var $body = $row.find('.pn-cm-field-body');
		var $icon = $row.find('.pn-cm-toggle-field .dashicons');

		$body.slideToggle(200, function() {
			if ($body.is(':visible')) {
				$icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
				// Update field visibility when opening
				updateFieldVisibility($row);
			} else {
				$icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
			}
		});
	}

	function updateFieldVisibility($row) {
		var type = $row.find('.pn-cm-field-type').val();

		console.log('Updating field visibility for type:', type);

		// Options field (select, radio)
		var $optionsField = $row.find('.pn-cm-field-options');
		if (['select', 'radio'].indexOf(type) !== -1) {
			$optionsField.removeAttr('style').show();
			// Initialize sortable if not already initialized
			var $optionsList = $optionsField.find('.pn-cm-options-list');
			if (!$optionsList.hasClass('ui-sortable')) {
				$optionsList.sortable({
					handle: '.pn-cm-option-handle',
					placeholder: 'pn-cm-option-placeholder',
					update: function(e, ui) {
						updateOptionsHiddenField($(this));
					}
				});
			}
		} else {
			$optionsField.attr('style', 'display:none;');
		}

		// HTML Multi configuration
		var $htmlMultiConfig = $row.find('.pn-cm-field-html-multi-config');
		if (type === 'html_multi') {
			$htmlMultiConfig.removeAttr('style').show();
		} else {
			$htmlMultiConfig.attr('style', 'display:none;');
		}

		// Range settings (number, range)
		var $rangeSettings = $row.find('.pn-cm-field-range-settings');
		if (['number', 'range'].indexOf(type) !== -1) {
			$rangeSettings.removeAttr('style').show();
		} else {
			$rangeSettings.attr('style', 'display:none;');
		}

		// Stars settings
		var $starsSettings = $row.find('.pn-cm-field-stars-settings');
		if (type === 'stars') {
			$starsSettings.removeAttr('style').show();
		} else {
			$starsSettings.attr('style', 'display:none;');
		}

		// Multiple files option
		var $multipleField = $row.find('.pn-cm-field-multiple');
		if (['image', 'video', 'audio', 'file'].indexOf(type) !== -1) {
			$multipleField.removeAttr('style').show();
		} else {
			$multipleField.attr('style', 'display:none;');
		}

		console.log('Options field:', $optionsField.attr('style'));
		console.log('HTML Multi config:', $htmlMultiConfig.attr('style'));
		console.log('Range settings:', $rangeSettings.attr('style'));
		console.log('Stars settings:', $starsSettings.attr('style'));
		console.log('Multiple field:', $multipleField.attr('style'));
	}

	function updateOptionsHiddenField($list) {
		var options = [];
		$list.find('.pn-cm-option-input').each(function() {
			var val = $(this).val().trim();
			if (val) {
				options.push(val);
			}
		});
		$list.siblings('.pn-cm-options-hidden').val(options.join("\n"));
	}

	function addField() {
		var template = $('#pn-cm-checkout-field-template').html();
		var html = template.replace(/\{\{INDEX\}\}/g, fieldIndex);

		var $newField = $(html);
		$('#pn-cm-checkout-fields-list').append($newField);

		// Initialize sortable for options in new field
		$newField.find('.pn-cm-options-list').sortable({
			handle: '.pn-cm-option-handle',
			placeholder: 'pn-cm-option-placeholder',
			update: function(e, ui) {
				updateOptionsHiddenField($(this));
			}
		});

		// Auto-expand new field
		setTimeout(function() {
			toggleField($newField);
			$newField.find('input[type="text"]').first().focus();
		}, 100);

		fieldIndex++;
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
		// Create popup content
		var popupId = 'pn-cm-csv-popup-' + productId;
		var content = '<div class="pn-cm-csv-export-popup" style="padding: 20px;">' +
			'<h2 style="margin-top: 0;">' + 'Export Orders to CSV' + '</h2>' +
			'<div class="pn-cm-csv-loading" style="text-align: center; padding: 40px;">' +
				'<div class="pn-customers-manager-loader-circle-wrapper">' +
					'<div class="pn-customers-manager-text-align-center">' +
						'<div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div>' +
					'</div>' +
				'</div>' +
				'<p>Loading preview...</p>' +
			'</div>' +
			'<div class="pn-cm-csv-column-selection" style="display: none;">' +
				'<h3 style="font-size: 16px; margin-top: 20px; margin-bottom: 15px;">Select Columns to Export</h3>' +
				'<div class="pn-cm-csv-columns-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; padding: 15px; background: #f9f9f9; border: 1px solid #e1e1e1; border-radius: 4px; max-height: 200px; overflow-y: auto;"></div>' +
			'</div>' +
			'<div class="pn-cm-csv-preview-container" style="display: none; margin-top: 30px;">' +
				'<h3 style="font-size: 16px; margin-bottom: 15px;">Data Preview</h3>' +
				'<div class="pn-cm-csv-preview" style="overflow-x: auto; border: 1px solid #e1e1e1; border-radius: 4px;"></div>' +
			'</div>' +
			'<div class="pn-cm-csv-footer" style="display: none; margin-top: 20px; text-align: right;">' +
				'<a href="#" onclick="pn_customers_manager_Popups.close(\'' + popupId + '\')">Cancel</button>' +
				' <a href="#" class="pn-customers-manager-btn pn-customers-manager-btn-mini pn-customers-manager-mr-20 pn-customers-manager-ml-20 pn-cm-export-csv-download">Download CSV</a>' +
			'</div>' +
		'</div>';

		// Open popup
		pn_customers_manager_Popups.open(popupId, content, {
			size: 'large'
		});

		// Store product ID in popup
		$('#' + popupId).data('product-id', productId);

		// Load preview
		loadCsvPreview(productId, $('#' + popupId));
	}

	function loadCsvPreview(productId, $popup) {
		var $loading = $popup.find('.pn-cm-csv-loading');
		var $columnSelection = $popup.find('.pn-cm-csv-column-selection');
		var $previewContainer = $popup.find('.pn-cm-csv-preview-container');
		var $footer = $popup.find('.pn-cm-csv-footer');

		$loading.show();
		$columnSelection.hide();
		$previewContainer.hide();
		$footer.hide();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pn_cm_get_csv_preview',
				product_id: productId,
				nonce: $('#pn-cm-csv-nonce-' + productId).val()
			},
			success: function(response) {
				$loading.hide();

				if (response.success) {
					renderColumnSelection($popup, response.data.columns);
					renderPreview($popup, response.data.columns, response.data.preview, response.data.total_rows);
					$columnSelection.show();
					$previewContainer.show();
					$footer.show();
				} else {
					alert(response.data.message || 'Error loading preview');
				}
			},
			error: function() {
				$loading.hide();
				alert('Error loading preview');
			}
		});
	}

	function renderColumnSelection($popup, columns) {
		var $list = $popup.find('.pn-cm-csv-columns-list');
		$list.empty();

		columns.forEach(function(column) {
			var $item = $('<div style="display: flex; align-items: center; gap: 8px;">' +
				'<input type="checkbox" id="col-' + column + '" class="pn-cm-csv-column-checkbox" value="' + column + '" checked>' +
				'<label for="col-' + column + '" style="margin: 0; cursor: pointer; font-size: 14px;">' + column + '</label>' +
			'</div>');
			$list.append($item);
		});
	}

	function renderPreview($popup, columns, preview, totalRows) {
		var $preview = $popup.find('.pn-cm-csv-preview');
		$preview.empty();

		if (preview.length === 0) {
			$preview.html('<p style="padding: 20px; text-align: center; color: #999;">No orders found for this product.</p>');
			return;
		}

		var html = '<table style="width: 100%; border-collapse: collapse; font-size: 13px;"><thead><tr>';
		columns.forEach(function(column) {
			html += '<th style="padding: 10px 12px; text-align: left; border-bottom: 1px solid #e1e1e1; background: #f5f5f5; font-weight: 600; white-space: nowrap;">' + column + '</th>';
		});
		html += '</tr></thead><tbody>';

		preview.forEach(function(row) {
			html += '<tr>';
			columns.forEach(function(column) {
				var value = row[column] || '';
				html += '<td style="padding: 10px 12px; border-bottom: 1px solid #e1e1e1; color: #666;">' + value + '</td>';
			});
			html += '</tr>';
		});

		html += '</tbody></table>';
		html += '<div style="margin-top: 10px; padding: 10px; background: #e8f5e9; border-left: 3px solid #4caf50; font-size: 13px; color: #2e7d32;">Showing ' + preview.length + ' of ' + totalRows + ' total orders</div>';

		$preview.html(html);

		// Store full data for filtering
		$popup.data('fullColumns', columns);
		$popup.data('fullPreview', preview);
		$popup.data('totalRows', totalRows);
	}

	function downloadCsv(productId) {
		var $popup = $('#pn-cm-csv-popup-' + productId);
		var selectedColumns = [];

		$popup.find('.pn-cm-csv-column-checkbox:checked').each(function() {
			selectedColumns.push($(this).val());
		});

		if (selectedColumns.length === 0) {
			alert('Please select at least one column');
			return;
		}

		// Create a form and submit it
		var $form = $('<form>', {
			method: 'POST',
			action: ajaxurl
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
