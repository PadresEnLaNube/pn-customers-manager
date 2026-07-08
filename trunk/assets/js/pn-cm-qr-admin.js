(function ($) {
  'use strict';

  var scanner = null;
  var isProcessing = false;
  var currentCameraIndex = 0;
  var cameras = [];
  var searchTimeout = null;

  // Function to show persistent notification in the panel
  function showPanelNotification(message, type) {
    var $notification = $('#pn-cm-qr-notification');
    var $notificationMessage = $('#pn-cm-qr-notification-message');

    // Remove previous type classes
    $notification.removeClass('success error warning');

    // Add new type class
    $notification.addClass(type);

    // Set message
    $notificationMessage.html(message);

    // Show notification
    $notification.removeClass('pn-customers-manager-display-none').show();
  }

  // Function to hide persistent notification
  function hidePanelNotification() {
    var $notification = $('#pn-cm-qr-notification');
    $notification.fadeOut(300, function() {
      $(this).addClass('pn-customers-manager-display-none');
      $('#pn-cm-qr-notification-message').html('');
      $notification.removeClass('success error warning');
    });
  }

  function getSelectedQRCodes() {
    var codes = [];
    $('.pn-cm-qr-item-checkbox:checked').each(function() {
      codes.push($(this).val());
    });
    return codes;
  }

  function updateBulkActionsBar() {
    var selectedCount = $('.pn-cm-qr-item-checkbox:checked').length;

    if (selectedCount > 0) {
      $('.pn-cm-qr-bulk-actions').show();
      $('.pn-cm-qr-bulk-selected').text(selectedCount + ' ' + (selectedCount === 1 ? pnCmQrData.i18n.selected : pnCmQrData.i18n.selectedPlural));
    } else {
      $('.pn-cm-qr-bulk-actions').hide();
    }
  }

  function bulkAction(action, qrCodes) {
    if (isProcessing) return;
    isProcessing = true;

    // Show loader
    $('.pn-cm-qr-loader').removeClass('pn-customers-manager-display-none').addClass('pn-customers-manager-display-block');

    $.ajax({
      url: pnCmQrData.ajaxUrl,
      type: 'POST',
      data: {
        action: action,
        nonce: pnCmQrData.nonce,
        qr_codes: qrCodes
      },
      success: function(response) {
        if (response.success) {
          pn_customers_manager_get_main_message(response.data.message, 'success');
          // Uncheck all checkboxes
          $('#pn-cm-qr-select-all').prop('checked', false);
          $('.pn-cm-qr-item-checkbox').prop('checked', false);
          updateBulkActionsBar();
          // Reload list
          updateOrdersList();
        } else {
          pn_customers_manager_get_main_message(response.data.message || pnCmQrData.i18n.error, 'error');
        }
        isProcessing = false;
      },
      error: function() {
        pn_customers_manager_get_main_message(pnCmQrData.i18n.error, 'error');
        isProcessing = false;
        $('.pn-cm-qr-loader').removeClass('pn-customers-manager-display-block').addClass('pn-customers-manager-display-none');
      }
    });
  }

  function updateOrdersList() {
    var productId = $('#pn-cm-qr-filter-product').val();
    var status = $('#pn-cm-qr-filter-status').val();
    var variationId = $('#pn-cm-qr-filter-variation').val();
    var search = $('#pn-cm-qr-filter-search').val();

    // Show loader
    $('.pn-cm-qr-loader').removeClass('pn-customers-manager-display-none').addClass('pn-customers-manager-display-block');

    $.ajax({
      url: pnCmQrData.ajaxUrl,
      type: 'POST',
      data: {
        action: 'pn_cm_woo_qr_get_orders',
        nonce: pnCmQrData.nonce,
        product_id: productId,
        status: status,
        variation_id: variationId,
        search: search
      },
      success: function (response) {
        if (response.success) {
          $('#pn-cm-qr-orders-list').html(response.data.html);
        }
      },
      complete: function() {
        // Hide loader after request completes
        $('.pn-cm-qr-loader').removeClass('pn-customers-manager-display-block').addClass('pn-customers-manager-display-none');
      }
    });
  }

  $(document).ready(function () {
    // Initialize active stat card based on current filter
    function initializeActiveStatCard() {
      var statusValue = $('#pn-cm-qr-filter-status').val();
      $('.pn-cm-qr-stat-card').removeClass('active');

      if (statusValue === 'pending') {
        $('.pn-cm-qr-stat-pending').addClass('active');
      } else if (statusValue === 'processed') {
        $('.pn-cm-qr-stat-processed').addClass('active');
      } else {
        $('.pn-cm-qr-stat-total').addClass('active');
      }
    }

    // Initialize on page load
    initializeActiveStatCard();

    // Close notification button
    $(document).on('click', '.pn-cm-qr-notification-close', function() {
      hidePanelNotification();
    });

    // Filter by clicking on stat cards
    $(document).on('click', '.pn-cm-qr-stat-card', function(e) {
      e.preventDefault();
      var $card = $(this);

      // Remove active class from all cards
      $('.pn-cm-qr-stat-card').removeClass('active');

      // Add active class to clicked card
      $card.addClass('active');

      // Determine which filter to apply
      var filterValue = '';
      if ($card.hasClass('pn-cm-qr-stat-pending')) {
        filterValue = 'pending';
      } else if ($card.hasClass('pn-cm-qr-stat-processed')) {
        filterValue = 'processed';
      }
      // If it's total, filterValue stays empty (show all)

      // Update the filter dropdown
      $('#pn-cm-qr-filter-status').val(filterValue);

      // Update the list
      updateOrdersList();
    });

    // Make entire row clickable by delegating to the link inside
    $(document).on('click', '.pn-cm-qr-row', function(e) {
      // Don't trigger if clicking on a button, link, or checkbox directly
      if ($(e.target).is('button, a, input[type="checkbox"]') || $(e.target).closest('button, a').length) {
        return;
      }

      // Find the link in this row and trigger its click
      var link = $(this).find('.pn-customers-manager-popup-open-ajax');
      if (link.length) {
        // Use jQuery trigger to ensure all handlers are called
        link.trigger('click');
      }
    });

    // Select all checkboxes
    $(document).on('change', '#pn-cm-qr-select-all', function() {
      var isChecked = $(this).prop('checked');
      $('.pn-cm-qr-item-checkbox').prop('checked', isChecked);
      updateBulkActionsBar();
    });

    // Individual checkbox change
    $(document).on('change', '.pn-cm-qr-item-checkbox', function() {
      var totalCheckboxes = $('.pn-cm-qr-item-checkbox').length;
      var checkedCheckboxes = $('.pn-cm-qr-item-checkbox:checked').length;

      $('#pn-cm-qr-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
      updateBulkActionsBar();
    });

    // Bulk process
    $(document).on('click', '#pn-cm-qr-bulk-process', function() {
      var selectedCodes = getSelectedQRCodes();
      if (selectedCodes.length === 0) return;

      if (confirm(pnCmQrData.i18n.confirmBulkProcess.replace('%d', selectedCodes.length))) {
        bulkAction('pn_cm_woo_qr_bulk_process', selectedCodes);
      }
    });

    // Bulk pending
    $(document).on('click', '#pn-cm-qr-bulk-pending', function() {
      var selectedCodes = getSelectedQRCodes();
      if (selectedCodes.length === 0) return;

      if (confirm(pnCmQrData.i18n.confirmBulkPending.replace('%d', selectedCodes.length))) {
        bulkAction('pn_cm_woo_qr_bulk_pending', selectedCodes);
      }
    });

    // Bulk delete
    $(document).on('click', '#pn-cm-qr-bulk-delete', function() {
      var selectedCodes = getSelectedQRCodes();
      if (selectedCodes.length === 0) return;

      if (confirm(pnCmQrData.i18n.confirmBulkDelete.replace('%d', selectedCodes.length))) {
        bulkAction('pn_cm_woo_qr_bulk_delete', selectedCodes);
      }
    });

    // Load variations when product is selected
    $('#pn-cm-qr-filter-product').on('change', function () {
      var productId = $(this).val();
      var $variationSelect = $('#pn-cm-qr-filter-variation');

      if (productId) {
        // Load variations for the selected product
        $.ajax({
          url: pnCmQrData.ajaxUrl,
          type: 'POST',
          data: {
            action: 'pn_cm_woo_qr_get_variations',
            nonce: pnCmQrData.nonce,
            product_id: productId
          },
          success: function (response) {
            if (response.success && response.data.variations.length > 0) {
              // Clear and populate variation select
              $variationSelect.html('<option value="">All Variations</option>');
              $.each(response.data.variations, function(index, variation) {
                $variationSelect.append(
                  $('<option></option>')
                    .val(variation.id)
                    .text(variation.name)
                );
              });
              $variationSelect.show();
            } else {
              // No variations, hide the select
              $variationSelect.hide().html('<option value="">All Variations</option>');
            }
          }
        });
      } else {
        // No product selected, hide variation select
        $variationSelect.hide().html('<option value="">All Variations</option>');
      }

      // Trigger filter update
      updateOrdersList();
    });

    // Filter orders list
    $('#pn-cm-qr-filter-status, #pn-cm-qr-filter-variation').on('change', function () {
      // If status filter changed, update active stat card
      if ($(this).attr('id') === 'pn-cm-qr-filter-status') {
        var statusValue = $(this).val();
        $('.pn-cm-qr-stat-card').removeClass('active');

        if (statusValue === 'pending') {
          $('.pn-cm-qr-stat-pending').addClass('active');
        } else if (statusValue === 'processed') {
          $('.pn-cm-qr-stat-processed').addClass('active');
        } else {
          $('.pn-cm-qr-stat-total').addClass('active');
        }
      }

      updateOrdersList();
    });

    // Search with debounce
    $('#pn-cm-qr-filter-search').on('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function() {
        updateOrdersList();
      }, 500); // Wait 500ms after user stops typing
    });

    // Open QR scanner
    $('#pn-cm-qr-scan').on('click', function (e) {
      e.preventDefault();
      openScanner();
    });

    // Switch camera button
    $(document).on('click', '#pn-cm-qr-switch-camera', function () {
      switchCamera();
    });

    // Manual process QR button
    $(document).on('click', '.pn-cm-qr-process-btn', function (e) {
      e.stopPropagation();
      var btn = $(this);
      var qrCode = btn.data('qr-code');
      if (confirm(pnCmQrData.i18n.confirmProcess)) {
        processQR(qrCode, btn);
      }
    });

    // Mark as pending button
    $(document).on('click', '.pn-cm-qr-pending-btn', function (e) {
      e.stopPropagation();
      var btn = $(this);
      var qrCode = btn.data('qr-code');
      if (confirm(pnCmQrData.i18n.confirmPending)) {
        markPending(qrCode, btn);
      }
    });

    // Delete QR button
    $(document).on('click', '.pn-cm-qr-delete-btn', function (e) {
      e.stopPropagation();
      var btn = $(this);
      var qrCode = btn.data('qr-code');
      if (confirm(pnCmQrData.i18n.confirmDelete)) {
        deleteQR(qrCode, btn);
      }
    });
  });

  function openScanner() {
    // Check if popup element exists
    var popupElement = document.getElementById('pn-cm-qr-scanner-popup');
    if (!popupElement) {
      return;
    }

    // Open popup using native system
    if (typeof pn_customers_manager_Popups !== 'undefined') {
      pn_customers_manager_Popups.open('pn-cm-qr-scanner-popup', {
        beforeShow: function() {
          // Initialize scanner when popup opens
          scanner = new Html5Qrcode("pn-cm-qr-scanner-container");

          Html5Qrcode.getCameras().then(function (devices) {
            if (devices && devices.length) {
              cameras = devices;

              // Find rear camera (prioritize it) or use first available
              var rearCameraIndex = devices.findIndex(function(device) {
                return device.label.toLowerCase().includes('back') ||
                       device.label.toLowerCase().includes('rear') ||
                       device.label.toLowerCase().includes('trasera') ||
                       device.label.toLowerCase().includes('environment');
              });

              currentCameraIndex = rearCameraIndex !== -1 ? rearCameraIndex : 0;

              // Show switch camera button if more than one camera available
              if (devices.length > 1) {
                $('#pn-cm-qr-switch-camera').show();
              }

              startCamera(cameras[currentCameraIndex].id);
            }
          }).catch(function (err) {
            $('#pn-cm-qr-scanner-result').html(
              '<p class="pn-cm-qr-scan-error">' + pnCmQrData.i18n.scanError + ': ' + err + '</p>'
            );
          });
        },
        afterClose: function() {
          // Clean up scanner when popup closes
          if (scanner) {
            scanner.stop().then(function () {
              scanner.clear();
              scanner = null;
            }).catch(function (err) {
              console.error('Error stopping scanner:', err);
            });
          }
          $('#pn-cm-qr-scanner-result').html('');
          $('#pn-cm-qr-switch-camera').hide();
          cameras = [];
          currentCameraIndex = 0;
        }
      });
    }
  }

  function startCamera(cameraId) {
    scanner.start(
      cameraId,
      {
        fps: 10,
        qrbox: { width: 250, height: 250 }
      },
      function (decodedText, decodedResult) {
        // QR code scanned successfully
        $('#pn-cm-qr-scanner-result').html(
          '<p class="pn-cm-qr-scan-success">' + pnCmQrData.i18n.scanSuccess + '</p>' +
          '<p><strong>' + decodedText + '</strong></p>'
        );

        // Process the QR code
        processQR(decodedText);

        // Stop scanner after successful scan
        setTimeout(function () {
          closeScanner();
        }, 1500);
      },
      function (errorMessage) {
        // Scan error (usually means no QR code in view)
        // We don't show this as it would spam the UI
      }
    ).catch(function (err) {
      $('#pn-cm-qr-scanner-result').html(
        '<p class="pn-cm-qr-scan-error">' + pnCmQrData.i18n.scanError + ': ' + err + '</p>'
      );
    });
  }

  function switchCamera() {
    if (cameras.length <= 1) return;

    // Stop current scanner
    if (scanner) {
      scanner.stop().then(function () {
        // Switch to next camera
        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
        startCamera(cameras[currentCameraIndex].id);
      }).catch(function (err) {
        console.error('Error stopping scanner:', err);
      });
    }
  }

  function closeScanner() {
    // Close popup using native system (cleanup is handled in afterClose callback)
    if (typeof pn_customers_manager_Popups !== 'undefined') {
      pn_customers_manager_Popups.close();
    }
  }

  function processQR(qrCode, btn) {
    if (isProcessing) return;
    isProcessing = true;

    // Clear previous notification before processing
    hidePanelNotification();

    if (btn) {
      btn.prop('disabled', true);
    }

    $.ajax({
      url: pnCmQrData.ajaxUrl,
      type: 'POST',
      data: {
        action: 'pn_cm_woo_qr_process',
        nonce: pnCmQrData.nonce,
        qr_code: qrCode
      },
      success: function (response) {
        if (response.success) {
          // Close popup and show success
          if (typeof pn_customers_manager_Popups !== 'undefined') {
            pn_customers_manager_Popups.close();
          }

          // Show message with appropriate color based on whether it was already processed
          var messageType = response.data.already_processed ? 'warning' : 'success';

          // Show animated message in corner
          pn_customers_manager_get_main_message(response.data.message, messageType);

          // Show persistent notification in panel
          showPanelNotification(response.data.message, messageType);

          // Reload orders list to reflect changes (before alert so user sees the update)
          updateOrdersList();

          // Show JavaScript alert after a short delay to let the list update
          setTimeout(function() {
            alert(response.data.message);
          }, 300);
        } else {
          alert(response.data.message || pnCmQrData.i18n.error);
          pn_customers_manager_get_main_message(response.data.message || pnCmQrData.i18n.error, 'error');
          showPanelNotification(response.data.message || pnCmQrData.i18n.error, 'error');
          if (btn) {
            btn.prop('disabled', false);
          }
        }
        isProcessing = false;
      },
      error: function () {
        alert(pnCmQrData.i18n.error);
        pn_customers_manager_get_main_message(pnCmQrData.i18n.error, 'error');
        showPanelNotification(pnCmQrData.i18n.error, 'error');
        if (btn) {
          btn.prop('disabled', false);
        }
        isProcessing = false;
      }
    });
  }

  function markPending(qrCode, btn) {
    if (isProcessing) return;
    isProcessing = true;

    // Clear previous notification before processing
    hidePanelNotification();

    if (btn) {
      btn.prop('disabled', true);
    }

    $.ajax({
      url: pnCmQrData.ajaxUrl,
      type: 'POST',
      data: {
        action: 'pn_cm_woo_qr_mark_pending',
        nonce: pnCmQrData.nonce,
        qr_code: qrCode
      },
      success: function (response) {
        if (response.success) {
          // Close popup and show success
          if (typeof pn_customers_manager_Popups !== 'undefined') {
            pn_customers_manager_Popups.close();
          }

          // Show animated message in corner
          pn_customers_manager_get_main_message(response.data.message, 'success');

          // Show persistent notification in panel
          showPanelNotification(response.data.message, 'success');

          // Reload orders list to reflect changes (before alert so user sees the update)
          updateOrdersList();

          // Show JavaScript alert after a short delay to let the list update
          setTimeout(function() {
            alert(response.data.message);
          }, 300);
        } else {
          alert(response.data.message || pnCmQrData.i18n.error);
          pn_customers_manager_get_main_message(response.data.message || pnCmQrData.i18n.error, 'error');
          showPanelNotification(response.data.message || pnCmQrData.i18n.error, 'error');
          if (btn) {
            btn.prop('disabled', false);
          }
        }
        isProcessing = false;
      },
      error: function () {
        alert(pnCmQrData.i18n.error);
        pn_customers_manager_get_main_message(pnCmQrData.i18n.error, 'error');
        showPanelNotification(pnCmQrData.i18n.error, 'error');
        if (btn) {
          btn.prop('disabled', false);
        }
        isProcessing = false;
      }
    });
  }

  function deleteQR(qrCode, btn) {
    if (isProcessing) return;
    isProcessing = true;

    // Clear previous notification before processing
    hidePanelNotification();

    if (btn) {
      btn.prop('disabled', true);
    }

    $.ajax({
      url: pnCmQrData.ajaxUrl,
      type: 'POST',
      data: {
        action: 'pn_cm_woo_qr_delete',
        nonce: pnCmQrData.nonce,
        qr_code: qrCode
      },
      success: function (response) {
        if (response.success) {
          // Close popup and show success
          if (typeof pn_customers_manager_Popups !== 'undefined') {
            pn_customers_manager_Popups.close();
          }

          // Show animated message in corner
          pn_customers_manager_get_main_message(response.data.message, 'success');

          // Show persistent notification in panel
          showPanelNotification(response.data.message, 'success');

          // Reload orders list to reflect changes (before alert so user sees the update)
          updateOrdersList();

          // Show JavaScript alert after a short delay to let the list update
          setTimeout(function() {
            alert(response.data.message);
          }, 300);
        } else {
          alert(response.data.message || pnCmQrData.i18n.error);
          pn_customers_manager_get_main_message(response.data.message || pnCmQrData.i18n.error, 'error');
          showPanelNotification(response.data.message || pnCmQrData.i18n.error, 'error');
          if (btn) {
            btn.prop('disabled', false);
          }
        }
        isProcessing = false;
      },
      error: function () {
        alert(pnCmQrData.i18n.error);
        pn_customers_manager_get_main_message(pnCmQrData.i18n.error, 'error');
        showPanelNotification(pnCmQrData.i18n.error, 'error');
        if (btn) {
          btn.prop('disabled', false);
        }
        isProcessing = false;
      }
    });
  }

  // ===== Complete Pending Orders =====

  // Open complete pending orders popup
  $(document).on('click', '#pn-cm-qr-complete-pending', function(e) {
    e.preventDefault();

    var $popup = $('#pn-cm-qr-complete-pending-popup');
    var $content = $('#pn-cm-qr-pending-orders-content');

    // Show loading
    $content.html('<div class="pn-customers-manager-text-align-center" style="padding: 40px;"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div><p>' + pnCmQrData.i18n.loadingOrders + '</p></div>');

    // Show popup using the correct system
    if (typeof pn_customers_manager_Popups !== 'undefined') {
      pn_customers_manager_Popups.open($popup);
    }

    // Load pending orders
    $.ajax({
      url: pnCmQrData.ajaxUrl,
      type: 'POST',
      data: {
        action: 'pn_cm_woo_qr_get_pending_orders',
        nonce: pnCmQrData.nonce
      },
      success: function(response) {
        if (response.success) {
          $content.html(response.data.html);
        } else {
          $content.html('<div class="pn-customers-manager-text-align-center" style="padding: 40px;"><p>' + (response.data.message || pnCmQrData.i18n.error) + '</p></div>');
        }
      },
      error: function() {
        $content.html('<div class="pn-customers-manager-text-align-center" style="padding: 40px;"><p>' + pnCmQrData.i18n.error + '</p></div>');
      }
    });
  });

  // Select all orders
  $(document).on('click', '#pn-cm-qr-select-all', function() {
    $('.pn-cm-qr-order-checkbox').prop('checked', true);
  });

  // Deselect all orders
  $(document).on('click', '#pn-cm-qr-deselect-all', function() {
    $('.pn-cm-qr-order-checkbox').prop('checked', false);
  });

  // Complete selected orders
  $(document).on('click', '#pn-cm-qr-complete-selected-orders', function() {
    var selectedOrders = [];
    $('.pn-cm-qr-order-checkbox:checked').each(function() {
      selectedOrders.push($(this).val());
    });

    if (selectedOrders.length === 0) {
      alert(pnCmQrData.i18n.noPendingOrders);
      return;
    }

    if (!confirm(pnCmQrData.i18n.confirmComplete.replace('%d', selectedOrders.length))) {
      return;
    }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="material-icons-outlined" style="vertical-align: middle; margin-right: 5px;">hourglass_empty</i>' + pnCmQrData.i18n.completingOrders);

    $.ajax({
      url: pnCmQrData.ajaxUrl,
      type: 'POST',
      data: {
        action: 'pn_cm_woo_qr_complete_pending_orders',
        nonce: pnCmQrData.nonce,
        order_ids: selectedOrders
      },
      success: function(response) {
        if (response.success) {
          pn_customers_manager_get_main_message(response.data.message, 'success');
          showPanelNotification(response.data.message, 'success');

          // Close popup using the correct system
          if (typeof pn_customers_manager_Popups !== 'undefined') {
            pn_customers_manager_Popups.close();
          }

          // Reload orders list
          setTimeout(function() {
            loadOrders();
          }, 500);
        } else {
          alert(response.data.message || pnCmQrData.i18n.error);
          $btn.prop('disabled', false).html('<i class="material-icons-outlined" style="vertical-align: middle; margin-right: 5px;">check_circle</i>' + pnCmQrData.i18n.completeSelected);
        }
      },
      error: function() {
        alert(pnCmQrData.i18n.error);
        $btn.prop('disabled', false).html('<i class="material-icons-outlined" style="vertical-align: middle; margin-right: 5px;">check_circle</i>' + pnCmQrData.i18n.completeSelected);
      }
    });
  });

})(jQuery);
