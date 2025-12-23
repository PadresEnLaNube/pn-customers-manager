(function($) {
  'use strict';

  // Helper function to extract content from response HTML
  function extractPopupContent(html) {
    var $temp = $('<div>').html(html);
    var $contentWrapper = $temp.find('.customers-manager-pn-popup-content');
    if ($contentWrapper.length) {
      // If response includes the wrapper, extract only the inner content
      return $contentWrapper.html();
    }
    // Otherwise return the HTML as is
    return html;
  }

  // Helper function to ensure close button is present in popup
  function ensureCloseButton(popupElement) {
    var popupContent = popupElement.find('.customers-manager-pn-popup-content');
    if (!popupContent.length) {
      return;
    }
    
    // Check if close button already exists
    var existingButton = popupContent.find('.customers-manager-pn-popup-close-wrapper, .customers-manager-pn-popup-close');
    if (existingButton.length) {
      // Make sure it's properly positioned and has the click handler
      if (!existingButton.hasClass('customers-manager-pn-popup-close-wrapper')) {
        existingButton.remove();
      } else {
        // Button exists and is correct, just ensure it's at the top
        var firstChild = popupContent.children().first();
        if (!firstChild.hasClass('customers-manager-pn-popup-close-wrapper')) {
          existingButton.detach();
          popupContent.prepend(existingButton);
        }
        return;
      }
    }
    
    // Remove any existing close buttons first (in case of duplicates)
    popupContent.find('.customers-manager-pn-popup-close-wrapper, .customers-manager-pn-popup-close').remove();
    
    // Add the close button
    var closeButton = $('<button class="customers-manager-pn-popup-close-wrapper" type="button"><i class="material-icons-outlined">close</i></button>');
    closeButton.on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      CUSTOMERS_MANAGER_PN_Popups.close();
    });
    popupContent.prepend(closeButton);
  }

  $(document).ready(function() {
    console.log('customers-manager-pn AJAX - Form submit handler registered');
    
    // Also bind directly to forms that might be added dynamically
    $(document).on('submit', '.customers-manager-pn-form', function(e){
      console.log('customers-manager-pn AJAX - Form submit event triggered');
      e.preventDefault();
      e.stopPropagation();
      
      var cm_pn_form = $(this);
      var customers_manager_pn_btn = cm_pn_form.find('input[type="submit"]');
      
      console.log('customers-manager-pn AJAX - Form found:', cm_pn_form.attr('id'), 'Submit button:', customers_manager_pn_btn.length);
      
      if (customers_manager_pn_btn.length === 0) {
        console.log('customers-manager-pn AJAX - No submit button found, aborting');
        return false;
      }
      
      customers_manager_pn_btn.addClass('customers-manager-pn-link-disabled').siblings('.customers-manager-pn-waiting').removeClass('customers-manager-pn-display-none');
      
      console.log('Form submitted:', cm_pn_form.attr('id'));

      var ajax_url = customers_manager_pn_ajax.ajax_url;
      var data = {
        action: 'customers_manager_pn_ajax_nopriv',
        customers_manager_pn_ajax_nopriv_nonce: customers_manager_pn_ajax.customers_manager_pn_ajax_nonce,
        customers_manager_pn_get_nonce: customers_manager_pn_action.customers_manager_pn_get_nonce,
        customers_manager_pn_ajax_nopriv_type: 'cm_pn_form_save',
        cm_pn_form_id: cm_pn_form.attr('id'),
        cm_pn_form_type: customers_manager_pn_btn.attr('data-customers-manager-pn-type'),
        cm_pn_form_subtype: customers_manager_pn_btn.attr('data-customers-manager-pn-subtype'),
        cm_pn_form_user_id: customers_manager_pn_btn.attr('data-customers-manager-pn-user-id'),
        cm_pn_form_post_id: customers_manager_pn_btn.attr('data-customers-manager-pn-post-id'),
        cm_pn_form_post_type: customers_manager_pn_btn.attr('data-customers-manager-pn-post-type'),
        customers_manager_pn_ajax_keys: [],
      };

      if (!(typeof window['customers_manager_pn_window_vars'] !== 'undefined')) {
        window['customers_manager_pn_window_vars'] = [];
      }

      $(cm_pn_form.find('input:not([type="submit"]), select, textarea')).each(function(index, element) {
        var is_multiple = $(this).attr('multiple');
        
        if (is_multiple) {
          if (!(typeof window['customers_manager_pn_window_vars']['form_field_' + element.name] !== 'undefined')) {
            window['customers_manager_pn_window_vars']['form_field_' + element.name] = [];
          }

          // Handle checkboxes in multiple fields
          if ($(this).is(':checkbox')) {
            if ($(this).is(':checked')) {
              window['customers_manager_pn_window_vars']['form_field_' + element.name].push($(element).val());
            } else {
              // For unchecked checkboxes in multiple fields, push empty string to maintain array structure
              window['customers_manager_pn_window_vars']['form_field_' + element.name].push('');
            }
          } else {
            // For non-checkbox multiple fields, push the value as before
            window['customers_manager_pn_window_vars']['form_field_' + element.name].push($(element).val());
          }

          data[element.name] = window['customers_manager_pn_window_vars']['form_field_' + element.name];
        }else{
          if ($(this).is(':checkbox')) {
            if ($(this).is(':checked')) {
              data[element.name] = $(element).val();
            }else{
              data[element.name] = '';
            }
          }else if ($(this).is(':radio')) {
            if ($(this).is(':checked')) {
              data[element.name] = $(element).val();
            }
          }else{
            data[element.name] = $(element).val();
          }
        }

        data.customers_manager_pn_ajax_keys.push({
          id: element.name,
          node: element.nodeName,
          type: element.type,
          multiple: (is_multiple == 'multiple' ? true : false),
        });
      });

      $.post(ajax_url, data, function(response) {
        console.log('data');console.log(data);
        console.log('response');console.log(response);

        var response_json = JSON.parse(response);

        if (response_json['error_key'] == 'cm_pn_form_save_error_unlogged') {
          customers_manager_pn_get_main_message(customers_manager_pn_i18n.user_unlogged);

          if (!$('.userspn-profile-wrapper .user-unlogged').length) {
            $('.userspn-profile-wrapper').prepend('<div class="userspn-alert userspn-alert-warning user-unlogged">' + customers_manager_pn_i18n.user_unlogged + '</div>');
          }

          CUSTOMERS_MANAGER_PN_Popups.open($('#userspn-profile-popup'));
          $('#userspn-login input#user_login').focus();
        }else if (response_json['error_key'] != '') {
          customers_manager_pn_get_main_message(customers_manager_pn_i18n.an_error_has_occurred);
        }else {
          customers_manager_pn_get_main_message(customers_manager_pn_i18n.saved_successfully);
        }

        if (response_json['update_list'] && response_json['update_html']) {
          // Build selector based on post type
          var list_selector = '.customers-manager-pn-cpt-list-wrapper.customers-manager-pn-' + data.cm_pn_form_post_type + '-list-wrapper';
          if (!$(list_selector).length) {
            // Fallback: try without the cpt-list-wrapper prefix
            list_selector = '.customers-manager-pn-' + data.cm_pn_form_post_type + '-list-wrapper';
          }
          if ($(list_selector).length && response_json['update_html']) {
            $(list_selector).html(response_json['update_html']);
          }
        }

        // Check popup_close with multiple conditions
        console.log('popup_close value:', response_json['popup_close'], 'type:', typeof response_json['popup_close']);
        console.log('Full response:', response_json);
        if (response_json['popup_close'] === true || response_json['popup_close'] === 'true' || response_json['popup_close'] === 1 || response_json['popup_close'] === '1') {
          console.log('Closing popup...');
          CUSTOMERS_MANAGER_PN_Popups.close();
          $('.customers-manager-pn-menu-more-overlay').fadeOut('fast');
        } else {
          console.log('Not closing popup. popup_close is:', response_json['popup_close']);
        }

        if (response_json['check'] == 'post_check') {
          CUSTOMERS_MANAGER_PN_Popups.close();
          $('.customers-manager-pn-menu-more-overlay').fadeOut('fast');
          $('.customers-manager-pn-' + data.cm_pn_form_post_type + '-list-item[data-' + data.cm_pn_form_post_type + '-id="' + data.cm_pn_form_post_id + '"] .customers-manager-pn-check-wrapper i').text('task_alt');
        }else if (response_json['check'] == 'post_uncheck') {
          CUSTOMERS_MANAGER_PN_Popups.close();
          $('.customers-manager-pn-menu-more-overlay').fadeOut('fast');
          $('.customers-manager-pn-' + data.cm_pn_form_post_type + '-list-item[data-' + data.cm_pn_form_post_type + '-id="' + data.cm_pn_form_post_id + '"] .customers-manager-pn-check-wrapper i').text('radio_button_unchecked');
        }

        customers_manager_pn_btn.removeClass('customers-manager-pn-link-disabled').siblings('.customers-manager-pn-waiting').addClass('customers-manager-pn-display-none');
      });

      delete window['customers_manager_pn_window_vars'];
      return false;
    });

    // Backup handler: click on submit button inside .customers-manager-pn-form
    $(document).on('click', '.customers-manager-pn-form input[type="submit"], .customers-manager-pn-form button[type="submit"]', function(e) {
      var $btn = $(this);
      var $form = $btn.closest('.customers-manager-pn-form');
      
      // Only handle if form validation passes
      if ($form.length && $form[0].checkValidity && !$form[0].checkValidity()) {
        // Let browser handle validation
        return true;
      }
      
      // If form has novalidate or validation passes, trigger submit
      // The submit handler above will catch it
      console.log('customers-manager-pn AJAX - Submit button clicked, form will submit');
    });

    $(document).on('click', '.customers-manager-pn-popup-open-ajax', function(e) {
      e.preventDefault();

      var customers_manager_pn_btn = $(this);
      var customers_manager_pn_ajax_type = customers_manager_pn_btn.attr('data-customers-manager-pn-ajax-type');
      var cm_pn_funnel_id = customers_manager_pn_btn.closest('.customers-manager-pn-funnel').attr('data-cm_pn_funnel-id') || customers_manager_pn_btn.attr('data-cm_pn_funnel-id') || '';
      var cm_pn_org_id = customers_manager_pn_btn.closest('.customers-manager-pn-organization').attr('data-cm_pn_org-id') || customers_manager_pn_btn.closest('.customers-manager-pn-cm_pn_org-list-item').attr('data-cm_pn_org-id') || customers_manager_pn_btn.attr('data-cm_pn_org-id') || '';
      var customers_manager_pn_popup_element = $('#' + customers_manager_pn_btn.attr('data-customers-manager-pn-popup-id'));

      CUSTOMERS_MANAGER_PN_Popups.open(customers_manager_pn_popup_element, {
        beforeShow: function(instance, popup) {
          var ajax_url = customers_manager_pn_ajax.ajax_url;
          var data = {
            action: 'customers_manager_pn_ajax',
            customers_manager_pn_ajax_type: customers_manager_pn_ajax_type,
            customers_manager_pn_ajax_nonce: customers_manager_pn_ajax.customers_manager_pn_ajax_nonce,
            customers_manager_pn_get_nonce: customers_manager_pn_action.customers_manager_pn_get_nonce,
            cm_pn_funnel_id: cm_pn_funnel_id ? cm_pn_funnel_id : '',
            cm_pn_org_id: cm_pn_org_id ? cm_pn_org_id : '',
          };

          // Log the data being sent
          console.log('customers-manager-pn AJAX - Sending request with data:', data);

          $.ajax({
            url: ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
              try {
                console.log('customers-manager-pn AJAX - Raw response received:', response);
                
                // Check if response is already an object (parsed JSON)
                var response_json = typeof response === 'object' ? response : null;
                
                // If not an object, try to parse as JSON
                if (!response_json) {
                  try {
                    response_json = JSON.parse(response);
                  } catch (parseError) {
                    // If parsing fails, assume it's HTML content
                    console.log('customers-manager-pn AJAX - Response appears to be HTML content');
                    var contentHtml = extractPopupContent(response);
                    customers_manager_pn_popup_element.find('.customers-manager-pn-popup-content').html(contentHtml);
                    
                    // Wait a bit for scripts to execute, then ensure close button is present
                    // Use multiple timeouts to ensure button stays even if scripts modify DOM
                    setTimeout(function() {
                      ensureCloseButton(customers_manager_pn_popup_element);
                    }, 50);
                    setTimeout(function() {
                      ensureCloseButton(customers_manager_pn_popup_element);
                    }, 200);
                    setTimeout(function() {
                      ensureCloseButton(customers_manager_pn_popup_element);
                    }, 500);
                    
                    // Initialize media uploaders if function exists
                    if (typeof initMediaUpload === 'function') {
                      $('.customers-manager-pn-image-upload-wrapper').each(function() {
                        initMediaUpload($(this), 'image');
                      });
                      $('.customers-manager-pn-audio-upload-wrapper').each(function() {
                        initMediaUpload($(this), 'audio');
                      });
                      $('.customers-manager-pn-video-upload-wrapper').each(function() {
                        initMediaUpload($(this), 'video');
                      });
                    }
                    return;
                  }
                }

                // Handle JSON response
                if (response_json.error_key) {
                  console.log('customers-manager-pn AJAX - Server returned error:', response_json.error_key);
                  var errorMessage = response_json.error_content || response_json.error_message || customers_manager_pn_i18n.an_error_has_occurred;
                  customers_manager_pn_get_main_message(errorMessage);
                  return;
                }

                // Handle successful JSON response with HTML content
                if (response_json.html) {
                  console.log('customers-manager-pn AJAX - HTML content received in JSON response');
                  var contentHtml = extractPopupContent(response_json.html);
                  customers_manager_pn_popup_element.find('.customers-manager-pn-popup-content').html(contentHtml);
                  
                  // Wait a bit for scripts to execute, then ensure close button is present
                  // Use multiple timeouts to ensure button stays even if scripts modify DOM
                  setTimeout(function() {
                    ensureCloseButton(customers_manager_pn_popup_element);
                  }, 50);
                  setTimeout(function() {
                    ensureCloseButton(customers_manager_pn_popup_element);
                  }, 200);
                  setTimeout(function() {
                    ensureCloseButton(customers_manager_pn_popup_element);
                  }, 500);
                  
                  // Initialize media uploaders if function exists
                  if (typeof initMediaUpload === 'function') {
                    $('.customers-manager-pn-image-upload-wrapper').each(function() {
                      initMediaUpload($(this), 'image');
                    });
                    $('.customers-manager-pn-audio-upload-wrapper').each(function() {
                      initMediaUpload($(this), 'audio');
                    });
                    $('.customers-manager-pn-video-upload-wrapper').each(function() {
                      initMediaUpload($(this), 'video');
                    });
                  }
                } else {
                  console.log('customers-manager-pn AJAX - Response missing HTML content');
                  customers_manager_pn_get_main_message(customers_manager_pn_i18n.an_error_has_occurred);
                }
              } catch (e) {
                console.log('customers-manager-pn AJAX - Error processing response:', e);
                console.log('Raw response:', response);
                customers_manager_pn_get_main_message(customers_manager_pn_i18n.an_error_has_occurred);
              }
            },
            error: function(xhr, status, error) {
              console.log('customers-manager-pn AJAX - Request failed:', status, error);
              console.log('Response:', xhr.responseText);
              console.log(customers_manager_pn_i18n.an_error_has_occurred);
            }
          });
        },
        afterClose: function() {
          customers_manager_pn_popup_element.find('.customers-manager-pn-popup-content').html('<div class="customers-manager-pn-loader-circle-wrapper"><div class="customers-manager-pn-text-align-center"><div class="customers-manager-pn-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>');
        },
      });
    });

    // Event listener for simple popups (non-AJAX)
    $(document).on('click', '.customers-manager-pn-popup-open', function(e) {
      e.preventDefault();

      var customers_manager_pn_btn = $(this);
      var customers_manager_pn_popup_element = $('#' + customers_manager_pn_btn.attr('data-customers-manager-pn-popup-id'));

      if (customers_manager_pn_popup_element.length) {
        CUSTOMERS_MANAGER_PN_Popups.open(customers_manager_pn_popup_element);
      }
    });

    // Generate event listeners for duplicate and remove functions based on CPTs
    var customers_manager_pn_cpts_mapping = {
      'cm_pn_funnel': 'cm_pn_funnel',
      'cm_pn_org': 'cm_pn_org'
    };

    // Loop through CPTs to create duplicate event listeners
    Object.keys(customers_manager_pn_cpts).forEach(function(cpt) {
      var cpt_short = cpt.replace('customers_manager_pn_', '');
      var container_class = '.customers-manager-pn-' + customers_manager_pn_cpts_mapping[cpt];
      
      // Duplicate event listener
      $(document).on('click', '.customers-manager-pn-' + cpt + '-duplicate-post', function(e) {
        e.preventDefault();

        $(container_class).fadeOut('fast');
        var customers_manager_pn_btn = $(this);
        var customers_manager_pn_id = customers_manager_pn_btn.closest('.customers-manager-pn-' + cpt_short).attr('data-customers_manager_pn_' + cpt_short + '-id');

        var ajax_url = customers_manager_pn_ajax.ajax_url;
        var data = {
          action: 'customers_manager_pn_ajax',
          customers_manager_pn_ajax_type: 'customers_manager_pn_' + cpt_short + '_duplicate',
          ['customers_manager_pn_' + cpt_short + '_id']: customers_manager_pn_id,
          customers_manager_pn_ajax_nonce: customers_manager_pn_ajax.customers_manager_pn_ajax_nonce,
        };

        $.post(ajax_url, data, function(response) {
          console.log('data');console.log(data);console.log('response');console.log(response);
          var response_json = JSON.parse(response);

          if (response_json['error_key'] != '') {
            customers_manager_pn_get_main_message(response_json['error_content']);
          }else{
            $(container_class).html(response_json['html']);
          }
          
          $(container_class).fadeIn('slow');
          $('.customers-manager-pn-menu-more-overlay').fadeOut('fast');
        });
      });

      // Remove event listener (for popup button)
      $(document).on('click', '.customers-manager-pn-' + cpt + '-remove', function(e) {
        e.preventDefault();

        $(container_class).fadeOut('fast');
        var customers_manager_pn_id = $('.customers-manager-pn-menu-more.customers-manager-pn-active').closest('.customers-manager-pn-' + cpt_short).attr('data-customers_manager_pn_' + cpt_short + '-id');

        var ajax_url = customers_manager_pn_ajax.ajax_url;
        var data = {
          action: 'customers_manager_pn_ajax',
          customers_manager_pn_ajax_type: 'customers_manager_pn_' + cpt_short + '_remove',
          ['customers_manager_pn_' + cpt_short + '_id']: customers_manager_pn_id,
          customers_manager_pn_ajax_nonce: customers_manager_pn_ajax.customers_manager_pn_ajax_nonce,
        };

        $.post(ajax_url, data, function(response) {
          console.log('data');console.log(data);console.log('response');console.log(response);
          var response_json = JSON.parse(response);
         
          if (response_json['error_key'] != '') {
            customers_manager_pn_get_main_message(response_json['error_content']);
          }else{
            $(container_class).html(response_json['html']);
            customers_manager_pn_get_main_message(customers_manager_pn_i18n.removed_successfully);
          }
          
          $(container_class).fadeIn('slow');
          $('.customers-manager-pn-menu-more-overlay').fadeOut('fast');

          CUSTOMERS_MANAGER_PN_Popups.close();
        });
      });
    });
  });
})(jQuery);
