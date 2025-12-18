(function($) {
  'use strict';

  // Helper function to extract content from response HTML
  function extractPopupContent(html) {
    var $temp = $('<div>').html(html);
    var $contentWrapper = $temp.find('.crmpn-popup-content');
    if ($contentWrapper.length) {
      // If response includes the wrapper, extract only the inner content
      return $contentWrapper.html();
    }
    // Otherwise return the HTML as is
    return html;
  }

  // Helper function to ensure close button is present in popup
  function ensureCloseButton(popupElement) {
    var popupContent = popupElement.find('.crmpn-popup-content');
    if (!popupContent.length) {
      return;
    }
    
    // Check if close button already exists
    var existingButton = popupContent.find('.crmpn-popup-close-wrapper, .crmpn-popup-close');
    if (existingButton.length) {
      // Make sure it's properly positioned and has the click handler
      if (!existingButton.hasClass('crmpn-popup-close-wrapper')) {
        existingButton.remove();
      } else {
        // Button exists and is correct, just ensure it's at the top
        var firstChild = popupContent.children().first();
        if (!firstChild.hasClass('crmpn-popup-close-wrapper')) {
          existingButton.detach();
          popupContent.prepend(existingButton);
        }
        return;
      }
    }
    
    // Remove any existing close buttons first (in case of duplicates)
    popupContent.find('.crmpn-popup-close-wrapper, .crmpn-popup-close').remove();
    
    // Add the close button
    var closeButton = $('<button class="crmpn-popup-close-wrapper" type="button"><i class="material-icons-outlined">close</i></button>');
    closeButton.on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      CRMPN_Popups.close();
    });
    popupContent.prepend(closeButton);
  }

  $(document).ready(function() {
    console.log('CRMPN AJAX - Form submit handler registered');
    
    // Also bind directly to forms that might be added dynamically
    $(document).on('submit', '.crmpn-form', function(e){
      console.log('CRMPN AJAX - Form submit event triggered');
      e.preventDefault();
      e.stopPropagation();
      
      var crmpn_form = $(this);
      var crmpn_btn = crmpn_form.find('input[type="submit"]');
      
      console.log('CRMPN AJAX - Form found:', crmpn_form.attr('id'), 'Submit button:', crmpn_btn.length);
      
      if (crmpn_btn.length === 0) {
        console.log('CRMPN AJAX - No submit button found, aborting');
        return false;
      }
      
      crmpn_btn.addClass('crmpn-link-disabled').siblings('.crmpn-waiting').removeClass('crmpn-display-none');
      
      console.log('Form submitted:', crmpn_form.attr('id'));

      var ajax_url = crmpn_ajax.ajax_url;
      var data = {
        action: 'crmpn_ajax_nopriv',
        crmpn_ajax_nopriv_nonce: crmpn_ajax.crmpn_ajax_nonce,
        crmpn_get_nonce: crmpn_action.crmpn_get_nonce,
        crmpn_ajax_nopriv_type: 'crmpn_form_save',
        crmpn_form_id: crmpn_form.attr('id'),
        crmpn_form_type: crmpn_btn.attr('data-crmpn-type'),
        crmpn_form_subtype: crmpn_btn.attr('data-crmpn-subtype'),
        crmpn_form_user_id: crmpn_btn.attr('data-crmpn-user-id'),
        crmpn_form_post_id: crmpn_btn.attr('data-crmpn-post-id'),
        crmpn_form_post_type: crmpn_btn.attr('data-crmpn-post-type'),
        crmpn_ajax_keys: [],
      };

      if (!(typeof window['crmpn_window_vars'] !== 'undefined')) {
        window['crmpn_window_vars'] = [];
      }

      $(crmpn_form.find('input:not([type="submit"]), select, textarea')).each(function(index, element) {
        var is_multiple = $(this).attr('multiple');
        
        if (is_multiple) {
          if (!(typeof window['crmpn_window_vars']['form_field_' + element.name] !== 'undefined')) {
            window['crmpn_window_vars']['form_field_' + element.name] = [];
          }

          // Handle checkboxes in multiple fields
          if ($(this).is(':checkbox')) {
            if ($(this).is(':checked')) {
              window['crmpn_window_vars']['form_field_' + element.name].push($(element).val());
            } else {
              // For unchecked checkboxes in multiple fields, push empty string to maintain array structure
              window['crmpn_window_vars']['form_field_' + element.name].push('');
            }
          } else {
            // For non-checkbox multiple fields, push the value as before
            window['crmpn_window_vars']['form_field_' + element.name].push($(element).val());
          }

          data[element.name] = window['crmpn_window_vars']['form_field_' + element.name];
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

        data.crmpn_ajax_keys.push({
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

        if (response_json['error_key'] == 'crmpn_form_save_error_unlogged') {
          crmpn_get_main_message(crmpn_i18n.user_unlogged);

          if (!$('.userspn-profile-wrapper .user-unlogged').length) {
            $('.userspn-profile-wrapper').prepend('<div class="userspn-alert userspn-alert-warning user-unlogged">' + crmpn_i18n.user_unlogged + '</div>');
          }

          CRMPN_Popups.open($('#userspn-profile-popup'));
          $('#userspn-login input#user_login').focus();
        }else if (response_json['error_key'] != '') {
          crmpn_get_main_message(crmpn_i18n.an_error_has_occurred);
        }else {
          crmpn_get_main_message(crmpn_i18n.saved_successfully);
        }

        if (response_json['update_list'] && response_json['update_html']) {
          // Build selector based on post type
          var list_selector = '.crmpn-cpt-list-wrapper.crmpn-' + data.crmpn_form_post_type + '-list-wrapper';
          if (!$(list_selector).length) {
            // Fallback: try without the cpt-list-wrapper prefix
            list_selector = '.crmpn-' + data.crmpn_form_post_type + '-list-wrapper';
          }
          if ($(list_selector).length && response_json['update_html']) {
            $(list_selector).html(response_json['update_html']);
        }


        // Check popup_close with multiple conditions
        console.log('popup_close value:', response_json['popup_close'], 'type:', typeof response_json['popup_close']);
        console.log('Full response:', response_json);
        if (response_json['popup_close'] === true || response_json['popup_close'] === 'true' || response_json['popup_close'] === 1 || response_json['popup_close'] === '1') {
          console.log('Closing popup...');
          CRMPN_Popups.close();
          $('.crmpn-menu-more-overlay').fadeOut('fast');
        } else {
          console.log('Not closing popup. popup_close is:', response_json['popup_close']);
        }

        if (response_json['check'] == 'post_check') {
          CRMPN_Popups.close();
          $('.crmpn-menu-more-overlay').fadeOut('fast');
          $('.crmpn-' + data.crmpn_form_post_type + '-list-item[data-' + data.crmpn_form_post_type + '-id="' + data.crmpn_form_post_id + '"] .crmpn-check-wrapper i').text('task_alt');
        }else if (response_json['check'] == 'post_uncheck') {
          CRMPN_Popups.close();
          $('.crmpn-menu-more-overlay').fadeOut('fast');
          $('.crmpn-' + data.crmpn_form_post_type + '-list-item[data-' + data.crmpn_form_post_type + '-id="' + data.crmpn_form_post_id + '"] .crmpn-check-wrapper i').text('radio_button_unchecked');
        }

        crmpn_btn.removeClass('crmpn-link-disabled').siblings('.crmpn-waiting').addClass('crmpn-display-none')
      });

      delete window['crmpn_window_vars'];
      return false;
    });

    // Backup handler: click on submit button inside .crmpn-form
    $(document).on('click', '.crmpn-form input[type="submit"], .crmpn-form button[type="submit"]', function(e) {
      var $btn = $(this);
      var $form = $btn.closest('.crmpn-form');
      
      // Only handle if form validation passes
      if ($form.length && $form[0].checkValidity && !$form[0].checkValidity()) {
        // Let browser handle validation
        return true;
      }
      
      // If form has novalidate or validation passes, trigger submit
      // The submit handler above will catch it
      console.log('CRMPN AJAX - Submit button clicked, form will submit');
    });

    $(document).on('click', '.crmpn-popup-open-ajax', function(e) {
      e.preventDefault();

      var crmpn_btn = $(this);
      var crmpn_ajax_type = crmpn_btn.attr('data-crmpn-ajax-type');
      var crmpn_funnel_id = crmpn_btn.closest('.crmpn-funnel').attr('data-crmpn_funnel-id') || crmpn_btn.attr('data-crmpn_funnel-id') || '';
      var crmpn_organization_id = crmpn_btn.closest('.crmpn-organization').attr('data-crmpn_organization-id') || crmpn_btn.closest('.crmpn-crmpn_organization-list-item').attr('data-crmpn_organization-id') || crmpn_btn.attr('data-crmpn_organization-id') || '';
      var crmpn_popup_element = $('#' + crmpn_btn.attr('data-crmpn-popup-id'));

      CRMPN_Popups.open(crmpn_popup_element, {
        beforeShow: function(instance, popup) {
          var ajax_url = crmpn_ajax.ajax_url;
          var data = {
            action: 'crmpn_ajax',
            crmpn_ajax_type: crmpn_ajax_type,
            crmpn_ajax_nonce: crmpn_ajax.crmpn_ajax_nonce,
            crmpn_get_nonce: crmpn_action.crmpn_get_nonce,
            crmpn_funnel_id: crmpn_funnel_id ? crmpn_funnel_id : '',
            crmpn_organization_id: crmpn_organization_id ? crmpn_organization_id : '',
          };

          // Log the data being sent
          console.log('CRMPN AJAX - Sending request with data:', data);

          $.ajax({
            url: ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
              try {
                console.log('CRMPN AJAX - Raw response received:', response);
                
                // Check if response is already an object (parsed JSON)
                var response_json = typeof response === 'object' ? response : null;
                
                // If not an object, try to parse as JSON
                if (!response_json) {
                  try {
                    response_json = JSON.parse(response);
                  } catch (parseError) {
                    // If parsing fails, assume it's HTML content
                    console.log('CRMPN AJAX - Response appears to be HTML content');
                    var contentHtml = extractPopupContent(response);
                    crmpn_popup_element.find('.crmpn-popup-content').html(contentHtml);
                    
                    // Wait a bit for scripts to execute, then ensure close button is present
                    // Use multiple timeouts to ensure button stays even if scripts modify DOM
                    setTimeout(function() {
                      ensureCloseButton(crmpn_popup_element);
                    }, 50);
                    setTimeout(function() {
                      ensureCloseButton(crmpn_popup_element);
                    }, 200);
                    setTimeout(function() {
                      ensureCloseButton(crmpn_popup_element);
                    }, 500);
                    
                    // Initialize media uploaders if function exists
                    if (typeof initMediaUpload === 'function') {
                      $('.crmpn-image-upload-wrapper').each(function() {
                        initMediaUpload($(this), 'image');
                      });
                      $('.crmpn-audio-upload-wrapper').each(function() {
                        initMediaUpload($(this), 'audio');
                      });
                      $('.crmpn-video-upload-wrapper').each(function() {
                        initMediaUpload($(this), 'video');
                      });
                    }
                    return;
                  }
                }

                // Handle JSON response
                if (response_json.error_key) {
                  console.log('CRMPN AJAX - Server returned error:', response_json.error_key);
                  var errorMessage = response_json.error_content || response_json.error_message || crmpn_i18n.an_error_has_occurred;
                  crmpn_get_main_message(errorMessage);
                  return;
                }

                // Handle successful JSON response with HTML content
                if (response_json.html) {
                  console.log('CRMPN AJAX - HTML content received in JSON response');
                  var contentHtml = extractPopupContent(response_json.html);
                  crmpn_popup_element.find('.crmpn-popup-content').html(contentHtml);
                  
                  // Wait a bit for scripts to execute, then ensure close button is present
                  // Use multiple timeouts to ensure button stays even if scripts modify DOM
                  setTimeout(function() {
                    ensureCloseButton(crmpn_popup_element);
                  }, 50);
                  setTimeout(function() {
                    ensureCloseButton(crmpn_popup_element);
                  }, 200);
                  setTimeout(function() {
                    ensureCloseButton(crmpn_popup_element);
                  }, 500);
                  
                  // Initialize media uploaders if function exists
                  if (typeof initMediaUpload === 'function') {
                    $('.crmpn-image-upload-wrapper').each(function() {
                      initMediaUpload($(this), 'image');
                    });
                    $('.crmpn-audio-upload-wrapper').each(function() {
                      initMediaUpload($(this), 'audio');
                    });
                    $('.crmpn-video-upload-wrapper').each(function() {
                      initMediaUpload($(this), 'video');
                    });
                  }
                } else {
                  console.log('CRMPN AJAX - Response missing HTML content');
                  crmpn_get_main_message(crmpn_i18n.an_error_has_occurred);
                }
              } catch (e) {
                console.log('CRMPN AJAX - Error processing response:', e);
                console.log('Raw response:', response);
                crmpn_get_main_message(crmpn_i18n.an_error_has_occurred);
              }
            },
            error: function(xhr, status, error) {
              console.log('CRMPN AJAX - Request failed:', status, error);
              console.log('Response:', xhr.responseText);
              console.log(crmpn_i18n.an_error_has_occurred);
            }
          });
        },
        afterClose: function() {
          crmpn_popup_element.find('.crmpn-popup-content').html('<div class="crmpn-loader-circle-wrapper"><div class="crmpn-text-align-center"><div class="crmpn-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>');
        },
      });
    });

    // Event listener for simple popups (non-AJAX)
    $(document).on('click', '.crmpn-popup-open', function(e) {
      e.preventDefault();

      var crmpn_btn = $(this);
      var crmpn_popup_element = $('#' + crmpn_btn.attr('data-crmpn-popup-id'));

      if (crmpn_popup_element.length) {
        CRMPN_Popups.open(crmpn_popup_element);
      }
    });

    // Generate event listeners for duplicate and remove functions based on CPTs
    var crmpn_cpts_mapping = {
      'crmpn_funnel': 'crmpn_funnel',
      'crmpn_organization': 'crmpn_organization'
    };

    // Loop through CPTs to create duplicate event listeners
    Object.keys(crmpn_cpts).forEach(function(cpt) {
      var cpt_short = cpt.replace('crmpn_', '');
      var container_class = '.crmpn-' + crmpn_cpts_mapping[cpt];
      
      // Duplicate event listener
      $(document).on('click', '.crmpn-' + cpt + '-duplicate-post', function(e) {
        e.preventDefault();

        $(container_class).fadeOut('fast');
        var crmpn_btn = $(this);
        var crmpn_id = crmpn_btn.closest('.crmpn-' + cpt_short).attr('data-crmpn_' + cpt_short + '-id');

        var ajax_url = crmpn_ajax.ajax_url;
        var data = {
          action: 'crmpn_ajax',
          crmpn_ajax_type: 'crmpn_' + cpt_short + '_duplicate',
          ['crmpn_' + cpt_short + '_id']: crmpn_id,
          crmpn_ajax_nonce: crmpn_ajax.crmpn_ajax_nonce,
        };

        $.post(ajax_url, data, function(response) {
          console.log('data');console.log(data);console.log('response');console.log(response);
          var response_json = JSON.parse(response);

          if (response_json['error_key'] != '') {
            crmpn_get_main_message(response_json['error_content']);
          }else{
            $(container_class).html(response_json['html']);
          }
          
          $(container_class).fadeIn('slow');
          $('.crmpn-menu-more-overlay').fadeOut('fast');
        });
      });

      // Remove event listener (for popup button)
      $(document).on('click', '.crmpn-' + cpt + '-remove', function(e) {
        e.preventDefault();

        $(container_class).fadeOut('fast');
        var crmpn_id = $('.crmpn-menu-more.crmpn-active').closest('.crmpn-' + cpt_short).attr('data-crmpn_' + cpt_short + '-id');

        var ajax_url = crmpn_ajax.ajax_url;
        var data = {
          action: 'crmpn_ajax',
          crmpn_ajax_type: 'crmpn_' + cpt_short + '_remove',
          ['crmpn_' + cpt_short + '_id']: crmpn_id,
          crmpn_ajax_nonce: crmpn_ajax.crmpn_ajax_nonce,
        };

        $.post(ajax_url, data, function(response) {
          console.log('data');console.log(data);console.log('response');console.log(response);
          var response_json = JSON.parse(response);
         
          if (response_json['error_key'] != '') {
            crmpn_get_main_message(response_json['error_content']);
          }else{
            $(container_class).html(response_json['html']);
            crmpn_get_main_message(crmpn_i18n.removed_successfully);
          }
          
          $(container_class).fadeIn('slow');
          $('.crmpn-menu-more-overlay').fadeOut('fast');

          CRMPN_Popups.close();
        });
      });
    });
  });
})(jQuery);
