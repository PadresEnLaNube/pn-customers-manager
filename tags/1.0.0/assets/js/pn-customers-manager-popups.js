(function($) {
    'use strict';
  
    window.pn_customers_manager_Popups = {
      open: function(popup, options = {}) {
        var popupElement = typeof popup === 'string' ? $('#' + popup) : popup;
        
        if (!popupElement.length) {
          return;
        }
  
        if (typeof options.beforeShow === 'function') {
          options.beforeShow();
        }
  
        // Show overlay - Remove any inline styles and add active class
        $('.pn-customers-manager-popup-overlay').removeClass('pn-customers-manager-display-none-soft').addClass('pn-customers-manager-popup-overlay-active').css('display', '');
  
        // Show popup - Remove any inline styles and add active class
        popupElement.removeClass('pn-customers-manager-display-none-soft').addClass('pn-customers-manager-popup-active').css('display', '');
  
        // Ensure close button is present (unless ESC is disabled)
        var popupContent = popupElement.find('.pn-customers-manager-popup-content');
        if (popupContent.length) {
          // Check if close button should be hidden (when ESC is disabled)
          var escDisabled = popupElement.attr('data-pn-customers-manager-popup-disable-esc') === 'true';
          
          // Remove any existing close buttons first
          popupContent.find('.pn-customers-manager-popup-close-wrapper, .pn-customers-manager-popup-close').remove();
          
          // Add the close button only if ESC is not disabled
          if (!escDisabled) {
            var closeButton = $('<button class="pn-customers-manager-popup-close-wrapper" type="button"><i class="material-icons-outlined">close</i></button>');
            closeButton.on('click', function(e) {
              e.preventDefault();
              pn_customers_manager_Popups.close();
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
        $('.pn-customers-manager-popup').each(function() {
          $(this).removeClass('pn-customers-manager-popup-active').addClass('pn-customers-manager-display-none-soft').css('display', 'none');
        });
  
        // Hide overlay - Remove classes and set inline display:none
        $('.pn-customers-manager-popup-overlay').removeClass('pn-customers-manager-popup-overlay-active').addClass('pn-customers-manager-display-none-soft').css('display', 'none');
  
        // Call afterClose callback if exists
        $('.pn-customers-manager-popup').each(function() {
          const afterClose = $(this).data('afterClose');
          if (typeof afterClose === 'function') {
            afterClose();
            $(this).removeData('afterClose');
          }
        });

        document.body.classList.remove('pn-customers-manager-popup-open');
      }
    };
  
    // Initialize popup functionality
    $(document).ready(function() {
      // Close popup when clicking overlay (unless disabled)
      $(document).on('click', '.pn-customers-manager-popup-overlay', function(e) {
        // Only close if the click was directly on the overlay
        if (e.target === this) {
          // Check if overlay close is disabled for the active popup
          var overlayCloseDisabled = $('.pn-customers-manager-popup.pn-customers-manager-popup-active[data-pn-customers-manager-popup-disable-overlay-close="true"]').length > 0;
          if (!overlayCloseDisabled) {
            pn_customers_manager_Popups.close();
          }
        }
      });
  
      // Prevent clicks inside popup from bubbling up to the overlay
      $(document).on('click', '.pn-customers-manager-popup', function(e) {
        e.stopPropagation();
      });
  
      // Close popup when pressing ESC key unless disabled
      $(document).on('keyup', function(e) {
        if (e.keyCode === 27) { // ESC key
          var escDisabled = $('.pn-customers-manager-popup.pn-customers-manager-popup-active[data-pn-customers-manager-popup-disable-esc="true"]').length > 0;
          if (!escDisabled) {
            pn_customers_manager_Popups.close();
          }
        }
      });
  
      // Close popup when clicking close button
      $(document).on('click', '.pn-customers-manager-popup-close, .pn-customers-manager-popup-close-wrapper, .pn-customers-manager-popup-cancel', function(e) {
        e.preventDefault();
        pn_customers_manager_Popups.close();
      });
    });
  })(jQuery); 