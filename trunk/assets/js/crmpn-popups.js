(function($) {
    'use strict';
  
    window.CRMPN_Popups = {
      open: function(popup, options = {}) {
        var popupElement = typeof popup === 'string' ? $('#' + popup) : popup;
        
        if (!popupElement.length) {
          return;
        }
  
        if (typeof options.beforeShow === 'function') {
          options.beforeShow();
        }
  
        // Show overlay - Remove any inline styles and add active class
        $('.crmpn-popup-overlay').removeClass('crmpn-display-none-soft').addClass('crmpn-popup-overlay-active').css('display', '');
  
        // Show popup - Remove any inline styles and add active class
        popupElement.removeClass('crmpn-display-none-soft').addClass('crmpn-popup-active').css('display', '');
  
        // Add close button if not present
        if (!popupElement.find('.crmpn-popup-close').length) {
          var closeButton = $('<button class="crmpn-popup-close-wrapper"><i class="material-icons-outlined">close</i></button>');
          closeButton.on('click', function() {
            CRMPN_Popups.close();
          });
          popupElement.find('.crmpn-popup-content').append(closeButton);
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
        $('.crmpn-popup').each(function() {
          $(this).removeClass('crmpn-popup-active').addClass('crmpn-display-none-soft').css('display', 'none');
        });
  
        // Hide overlay - Remove classes and set inline display:none
        $('.crmpn-popup-overlay').removeClass('crmpn-popup-overlay-active').addClass('crmpn-display-none-soft').css('display', 'none');
  
        // Call afterClose callback if exists
        $('.crmpn-popup').each(function() {
          const afterClose = $(this).data('afterClose');
          if (typeof afterClose === 'function') {
            afterClose();
            $(this).removeData('afterClose');
          }
        });

        document.body.classList.remove('crmpn-popup-open');
      }
    };
  
    // Initialize popup functionality
    $(document).ready(function() {
      // Close popup when clicking overlay
      $(document).on('click', '.crmpn-popup-overlay', function(e) {
        // Only close if the click was directly on the overlay
        if (e.target === this) {
          CRMPN_Popups.close();
        }
      });
  
      // Prevent clicks inside popup from bubbling up to the overlay
      $(document).on('click', '.crmpn-popup', function(e) {
        e.stopPropagation();
      });
  
      // Close popup when pressing ESC key
      $(document).on('keyup', function(e) {
        if (e.keyCode === 27) { // ESC key
          CRMPN_Popups.close();
        }
      });
  
      // Close popup when clicking close button
      $(document).on('click', '.crmpn-popup-close, .crmpn-popup-close-wrapper', function(e) {
        e.preventDefault();
        CRMPN_Popups.close();
      });
    });
  })(jQuery); 