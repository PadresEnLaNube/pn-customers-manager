(function($) {
    'use strict';
  
    window.CUSTOMERS_MANAGER_PN_Popups = {
      open: function(popup, options = {}) {
        var popupElement = typeof popup === 'string' ? $('#' + popup) : popup;
        
        if (!popupElement.length) {
          return;
        }
  
        if (typeof options.beforeShow === 'function') {
          options.beforeShow();
        }
  
        // Show overlay - Remove any inline styles and add active class
        $('.customers-manager-pn-popup-overlay').removeClass('customers-manager-pn-display-none-soft').addClass('customers-manager-pn-popup-overlay-active').css('display', '');
  
        // Show popup - Remove any inline styles and add active class
        popupElement.removeClass('customers-manager-pn-display-none-soft').addClass('customers-manager-pn-popup-active').css('display', '');
  
        // Ensure close button is present (unless ESC is disabled)
        var popupContent = popupElement.find('.customers-manager-pn-popup-content');
        if (popupContent.length) {
          // Check if close button should be hidden (when ESC is disabled)
          var escDisabled = popupElement.attr('data-customers-manager-pn-popup-disable-esc') === 'true';
          
          // Remove any existing close buttons first
          popupContent.find('.customers-manager-pn-popup-close-wrapper, .customers-manager-pn-popup-close').remove();
          
          // Add the close button only if ESC is not disabled
          if (!escDisabled) {
            var closeButton = $('<button class="customers-manager-pn-popup-close-wrapper" type="button"><i class="material-icons-outlined">close</i></button>');
            closeButton.on('click', function(e) {
              e.preventDefault();
              CUSTOMERS_MANAGER_PN_Popups.close();
            });
            popupContent.prepend(closeButton);
          }
        }
  
        // Store and call callbacks if provided
        if (options.beforeShow) {
          popupElement.data('beforeShow', options.beforeShow);
        }
        if (options.afterClose) {
          popupElement.data('afterClose', options.afterClose);
        }
      },
  
      close: function() {
        // Hide all popups - Remove classes and set inline display:none
        $('.customers-manager-pn-popup').each(function() {
          $(this).removeClass('customers-manager-pn-popup-active').addClass('customers-manager-pn-display-none-soft').css('display', 'none');
        });
  
        // Hide overlay - Remove classes and set inline display:none
        $('.customers-manager-pn-popup-overlay').removeClass('customers-manager-pn-popup-overlay-active').addClass('customers-manager-pn-display-none-soft').css('display', 'none');
  
        // Call afterClose callback if exists
        $('.customers-manager-pn-popup').each(function() {
          const afterClose = $(this).data('afterClose');
          if (typeof afterClose === 'function') {
            afterClose();
            $(this).removeData('afterClose');
          }
        });

        document.body.classList.remove('customers-manager-pn-popup-open');
      }
    };
  
    // Initialize popup functionality
    $(document).ready(function() {
      // Close popup when clicking overlay (unless disabled)
      $(document).on('click', '.customers-manager-pn-popup-overlay', function(e) {
        // Only close if the click was directly on the overlay
        if (e.target === this) {
          // Check if overlay close is disabled for the active popup
          var overlayCloseDisabled = $('.customers-manager-pn-popup.customers-manager-pn-popup-active[data-customers-manager-pn-popup-disable-overlay-close="true"]').length > 0;
          if (!overlayCloseDisabled) {
            CUSTOMERS_MANAGER_PN_Popups.close();
          }
        }
      });
  
      // Prevent clicks inside popup from bubbling up to the overlay
      $(document).on('click', '.customers-manager-pn-popup', function(e) {
        e.stopPropagation();
      });
  
      // Close popup when pressing ESC key unless disabled
      $(document).on('keyup', function(e) {
        if (e.keyCode === 27) { // ESC key
          var escDisabled = $('.customers-manager-pn-popup.customers-manager-pn-popup-active[data-customers-manager-pn-popup-disable-esc="true"]').length > 0;
          if (!escDisabled) {
            CUSTOMERS_MANAGER_PN_Popups.close();
          }
        }
      });
  
      // Close popup when clicking close button
      $(document).on('click', '.customers-manager-pn-popup-close, .customers-manager-pn-popup-close-wrapper', function(e) {
        e.preventDefault();
        CUSTOMERS_MANAGER_PN_Popups.close();
      });
    });
  })(jQuery); 