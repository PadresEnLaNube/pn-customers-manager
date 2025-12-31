(function ($) {
  'use strict';

  // Helper function to extract content from response HTML
  function extractPopupContent(html) {
    var $temp = $('<div>').html(html);
    var $contentWrapper = $temp.find('.pn-customers-manager-popup-content');
    if ($contentWrapper.length) {
      // If response includes the wrapper, extract only the inner content
      return $contentWrapper.html();
    }
    // Otherwise return the HTML as is
    return html;
  }

  // Helper function to ensure close button is present in popup
  function ensureCloseButton(popupElement) {
    var popupContent = popupElement.find('.pn-customers-manager-popup-content');
    if (!popupContent.length) {
      return;
    }

    // Check if close button already exists
    var existingButton = popupContent.find('.pn-customers-manager-popup-close-wrapper, .pn-customers-manager-popup-close');
    if (existingButton.length) {
      // Make sure it's properly positioned and has the click handler
      if (!existingButton.hasClass('pn-customers-manager-popup-close-wrapper')) {
        existingButton.remove();
      } else {
        // Button exists and is correct, just ensure it's at the top
        var firstChild = popupContent.children().first();
        if (!firstChild.hasClass('pn-customers-manager-popup-close-wrapper')) {
          existingButton.detach();
          popupContent.prepend(existingButton);
        }
        return;
      }
    }

    // Remove any existing close buttons first (in case of duplicates)
    popupContent.find('.pn-customers-manager-popup-close-wrapper, .pn-customers-manager-popup-close').remove();

    // Add the close button
    var closeButton = $('<button class="pn-customers-manager-popup-close-wrapper" type="button"><i class="material-icons-outlined">close</i></button>');
    closeButton.on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      pn_customers_manager_Popups.close();
    });
    popupContent.prepend(closeButton);
  }

  $(document).ready(function () {
    console.log('pn-customers-manager AJAX - Form submit handler registered');

    // Also bind directly to forms that might be added dynamically
    $(document).on('submit', '.pn-customers-manager-form', function (e) {
      console.log('pn-customers-manager AJAX - Form submit event triggered');
      e.preventDefault();
      e.stopPropagation();

      var cm_pn_form = $(this);
      var pn_customers_manager_btn = cm_pn_form.find('input[type="submit"]');

      console.log('pn-customers-manager AJAX - Form found:', cm_pn_form.attr('id'), 'Submit button:', pn_customers_manager_btn.length);

      if (pn_customers_manager_btn.length === 0) {
        console.log('pn-customers-manager AJAX - No submit button found, aborting');
        return false;
      }

      pn_customers_manager_btn.addClass('pn-customers-manager-link-disabled').siblings('.pn-customers-manager-waiting').removeClass('pn-customers-manager-display-none');

      console.log('Form submitted:', cm_pn_form.attr('id'));

      var ajax_url = pn_customers_manager_ajax.ajax_url;
      var data = {
        action: 'pn_customers_manager_ajax_nopriv',
        pn_customers_manager_ajax_nopriv_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
        pn_customers_manager_get_nonce: pn_customers_manager_action.pn_customers_manager_get_nonce,
        pn_customers_manager_ajax_nopriv_type: 'cm_pn_form_save',
        cm_pn_form_id: cm_pn_form.attr('id'),
        cm_pn_form_type: pn_customers_manager_btn.attr('data-pn-customers-manager-type'),
        cm_pn_form_subtype: pn_customers_manager_btn.attr('data-pn-customers-manager-subtype'),
        cm_pn_form_user_id: pn_customers_manager_btn.attr('data-pn-customers-manager-user-id'),
        cm_pn_form_post_id: pn_customers_manager_btn.attr('data-pn-customers-manager-post-id'),
        cm_pn_form_post_type: pn_customers_manager_btn.attr('data-pn-customers-manager-post-type'),
        pn_customers_manager_ajax_keys: [],
      };

      if (!(typeof window['pn_customers_manager_window_vars'] !== 'undefined')) {
        window['pn_customers_manager_window_vars'] = [];
      }

      $(cm_pn_form.find('input:not([type="submit"]), select, textarea')).each(function (index, element) {
        var is_multiple = $(this).attr('multiple');

        if (is_multiple) {
          if (!(typeof window['pn_customers_manager_window_vars']['form_field_' + element.name] !== 'undefined')) {
            window['pn_customers_manager_window_vars']['form_field_' + element.name] = [];
          }

          // Handle checkboxes in multiple fields
          if ($(this).is(':checkbox')) {
            if ($(this).is(':checked')) {
              window['pn_customers_manager_window_vars']['form_field_' + element.name].push($(element).val());
            } else {
              // For unchecked checkboxes in multiple fields, push empty string to maintain array structure
              window['pn_customers_manager_window_vars']['form_field_' + element.name].push('');
            }
          } else {
            // For non-checkbox multiple fields, push the value as before
            window['pn_customers_manager_window_vars']['form_field_' + element.name].push($(element).val());
          }

          data[element.name] = window['pn_customers_manager_window_vars']['form_field_' + element.name];
        } else {
          if ($(this).is(':checkbox')) {
            if ($(this).is(':checked')) {
              data[element.name] = $(element).val();
            } else {
              data[element.name] = '';
            }
          } else if ($(this).is(':radio')) {
            if ($(this).is(':checked')) {
              data[element.name] = $(element).val();
            }
          } else {
            data[element.name] = $(element).val();
          }
        }

        data.pn_customers_manager_ajax_keys.push({
          id: element.name,
          node: element.nodeName,
          type: element.type,
          multiple: (is_multiple == 'multiple' ? true : false),
        });
      });

      $.post(ajax_url, data, function (response) {
        console.log('data'); console.log(data);
        console.log('response'); console.log(response);

        var response_json = JSON.parse(response);

        if (response_json['error_key'] == 'cm_pn_form_save_error_unlogged') {
          pn_customers_manager_get_main_message(pn_customers_manager_i18n.user_unlogged);

          if (!$('.userspn-profile-wrapper .user-unlogged').length) {
            $('.userspn-profile-wrapper').prepend('<div class="userspn-alert userspn-alert-warning user-unlogged">' + pn_customers_manager_i18n.user_unlogged + '</div>');
          }

          pn_customers_manager_Popups.open($('#userspn-profile-popup'));
          $('#userspn-login input#user_login').focus();
        } else if (response_json['error_key'] != '') {
          pn_customers_manager_get_main_message(pn_customers_manager_i18n.an_error_has_occurred);
        } else {
          pn_customers_manager_get_main_message(pn_customers_manager_i18n.saved_successfully);
        }

        if (response_json['update_list'] && response_json['update_html']) {
          // Build selector based on post type
          var list_selector = '.pn-customers-manager-cpt-list-wrapper.pn-customers-manager-' + data.cm_pn_form_post_type + '-list-wrapper';
          if (!$(list_selector).length) {
            // Fallback: try without the cpt-list-wrapper prefix
            list_selector = '.pn-customers-manager-' + data.cm_pn_form_post_type + '-list-wrapper';
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
          pn_customers_manager_Popups.close();
          $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');
        } else {
          console.log('Not closing popup. popup_close is:', response_json['popup_close']);
        }

        if (response_json['check'] == 'post_check') {
          pn_customers_manager_Popups.close();
          $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');
          $('.pn-customers-manager-' + data.cm_pn_form_post_type + '-list-item[data-' + data.cm_pn_form_post_type + '-id="' + data.cm_pn_form_post_id + '"] .pn-customers-manager-check-wrapper i').text('task_alt');
        } else if (response_json['check'] == 'post_uncheck') {
          pn_customers_manager_Popups.close();
          $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');
          $('.pn-customers-manager-' + data.cm_pn_form_post_type + '-list-item[data-' + data.cm_pn_form_post_type + '-id="' + data.cm_pn_form_post_id + '"] .pn-customers-manager-check-wrapper i').text('radio_button_unchecked');
        }

        pn_customers_manager_btn.removeClass('pn-customers-manager-link-disabled').siblings('.pn-customers-manager-waiting').addClass('pn-customers-manager-display-none');
      });

      delete window['pn_customers_manager_window_vars'];
      return false;
    });

    // Backup handler: click on submit button inside .pn-customers-manager-form
    $(document).on('click', '.pn-customers-manager-form input[type="submit"], .pn-customers-manager-form button[type="submit"]', function (e) {
      var $btn = $(this);
      var $form = $btn.closest('.pn-customers-manager-form');

      // Only handle if form validation passes
      if ($form.length && $form[0].checkValidity && !$form[0].checkValidity()) {
        // Let browser handle validation
        return true;
      }

      // If form has novalidate or validation passes, trigger submit
      // The submit handler above will catch it
      console.log('pn-customers-manager AJAX - Submit button clicked, form will submit');
    });

    $(document).on('click', '.pn-customers-manager-popup-open-ajax', function (e) {
      e.preventDefault();

      var pn_customers_manager_btn = $(this);
      var pn_customers_manager_ajax_type = pn_customers_manager_btn.attr('data-pn-customers-manager-ajax-type');
      var cm_pn_funnel_id = pn_customers_manager_btn.closest('.pn-customers-manager-funnel').attr('data-cm_pn_funnel-id') || pn_customers_manager_btn.attr('data-cm_pn_funnel-id') || '';
      var cm_pn_org_id = pn_customers_manager_btn.closest('.pn-customers-manager-organization').attr('data-cm_pn_org-id') || pn_customers_manager_btn.closest('.pn-customers-manager-cm_pn_org-list-item').attr('data-cm_pn_org-id') || pn_customers_manager_btn.attr('data-cm_pn_org-id') || '';
      var pn_customers_manager_popup_element = $('#' + pn_customers_manager_btn.attr('data-pn-customers-manager-popup-id'));

      pn_customers_manager_Popups.open(pn_customers_manager_popup_element, {
        beforeShow: function (instance, popup) {
          var ajax_url = pn_customers_manager_ajax.ajax_url;
          var data = {
            action: 'pn_customers_manager_ajax',
            pn_customers_manager_ajax_type: pn_customers_manager_ajax_type,
            pn_customers_manager_ajax_nonce: pn_customers_manager_ajax.pn_customers_manager_ajax_nonce,
            pn_customers_manager_get_nonce: pn_customers_manager_action.pn_customers_manager_get_nonce,
            cm_pn_funnel_id: cm_pn_funnel_id ? cm_pn_funnel_id : '',
            cm_pn_org_id: cm_pn_org_id ? cm_pn_org_id : '',
          };

          // Log the data being sent
          console.log('pn-customers-manager AJAX - Sending request with data:', data);

          $.ajax({
            url: ajax_url,
            type: 'POST',
            data: data,
            success: function (response) {
              try {
                console.log('pn-customers-manager AJAX - Raw response received:', response);

                // Check if response is already an object (parsed JSON)
                var response_json = typeof response === 'object' ? response : null;

                // If not an object, try to parse as JSON
                if (!response_json) {
                  try {
                    response_json = JSON.parse(response);
                  } catch (parseError) {
                    // If parsing fails, assume it's HTML content
                    console.log('pn-customers-manager AJAX - Response appears to be HTML content');
                    var contentHtml = extractPopupContent(response);
                    pn_customers_manager_popup_element.find('.pn-customers-manager-popup-content').html(contentHtml);

                    // Wait a bit for scripts to execute, then ensure close button is present
                    // Use multiple timeouts to ensure button stays even if scripts modify DOM
                    setTimeout(function () {
                      ensureCloseButton(pn_customers_manager_popup_element);
                    }, 50);
                    setTimeout(function () {
                      ensureCloseButton(pn_customers_manager_popup_element);
                    }, 200);
                    setTimeout(function () {
                      ensureCloseButton(pn_customers_manager_popup_element);
                    }, 500);

                    // Initialize media uploaders if function exists
                    if (typeof initMediaUpload === 'function') {
                      $('.pn-customers-manager-image-upload-wrapper').each(function () {
                        initMediaUpload($(this), 'image');
                      });
                      $('.pn-customers-manager-audio-upload-wrapper').each(function () {
                        initMediaUpload($(this), 'audio');
                      });
                      $('.pn-customers-manager-video-upload-wrapper').each(function () {
                        initMediaUpload($(this), 'video');
                      });
                    }
                    return;
                  }
                }

                // Handle JSON response
                if (response_json.error_key) {
                  console.log('pn-customers-manager AJAX - Server returned error:', response_json.error_key);
                  var errorMessage = response_json.error_content || response_json.error_message || pn_customers_manager_i18n.an_error_has_occurred;
                  pn_customers_manager_get_main_message(errorMessage);
                  return;
                }

                // Handle successful JSON response with HTML content
                if (response_json.html) {
                  console.log('pn-customers-manager AJAX - HTML content received in JSON response');
                  var contentHtml = extractPopupContent(response_json.html);
                  pn_customers_manager_popup_element.find('.pn-customers-manager-popup-content').html(contentHtml);

                  // Wait a bit for scripts to execute, then ensure close button is present
                  // Use multiple timeouts to ensure button stays even if scripts modify DOM
                  setTimeout(function () {
                    ensureCloseButton(pn_customers_manager_popup_element);
                  }, 50);
                  setTimeout(function () {
                    ensureCloseButton(pn_customers_manager_popup_element);
                  }, 200);
                  setTimeout(function () {
                    ensureCloseButton(pn_customers_manager_popup_element);
                  }, 500);

                  // Initialize media uploaders if function exists
                  if (typeof initMediaUpload === 'function') {
                    $('.pn-customers-manager-image-upload-wrapper').each(function () {
                      initMediaUpload($(this), 'image');
                    });
                    $('.pn-customers-manager-audio-upload-wrapper').each(function () {
                      initMediaUpload($(this), 'audio');
                    });
                    $('.pn-customers-manager-video-upload-wrapper').each(function () {
                      initMediaUpload($(this), 'video');
                    });
                  }
                } else {
                  console.log('pn-customers-manager AJAX - Response missing HTML content');
                  pn_customers_manager_get_main_message(pn_customers_manager_i18n.an_error_has_occurred);
                }
              } catch (e) {
                console.log('pn-customers-manager AJAX - Error processing response:', e);
                console.log('Raw response:', response);
                pn_customers_manager_get_main_message(pn_customers_manager_i18n.an_error_has_occurred);
              }
            },
            error: function (xhr, status, error) {
              console.log('pn-customers-manager AJAX - Request failed:', status, error);
              console.log('Response:', xhr.responseText);
              console.log(pn_customers_manager_i18n.an_error_has_occurred);
            }
          });
        },
        afterClose: function () {
          pn_customers_manager_popup_element.find('.pn-customers-manager-popup-content').html('<div class="pn-customers-manager-loader-circle-wrapper"><div class="pn-customers-manager-text-align-center"><div class="pn-customers-manager-loader-circle"><div></div><div></div><div></div><div></div></div></div></div>');
        },
      });
    });

    // Event listener for simple popups (non-AJAX)
    $(document).on('click', '.pn-customers-manager-popup-open', function (e) {
      e.preventDefault();

      var pn_customers_manager_btn = $(this);
      var pn_customers_manager_popup_element = $('#' + pn_customers_manager_btn.attr('data-pn-customers-manager-popup-id'));

      if (pn_customers_manager_popup_element.length) {
        // Try to find and store the ID in the popup if it's a remove popup
        var pn_customers_manager_popup_id = pn_customers_manager_popup_element.attr('id');
        if (pn_customers_manager_popup_id && pn_customers_manager_popup_id.indexOf('-remove') !== -1) {
          // Try to get ID from the clicked element's context
          var pn_customers_manager_cpt_short = pn_customers_manager_popup_id.replace('pn-customers-manager-popup-', '').replace('-remove', '');
          var pn_customers_manager_data_attr = 'data-' + pn_customers_manager_cpt_short + '-id';
          var pn_customers_manager_id = pn_customers_manager_btn.closest('[' + pn_customers_manager_data_attr + ']').attr(pn_customers_manager_data_attr) ||
            pn_customers_manager_btn.closest('.pn-customers-manager-' + pn_customers_manager_cpt_short).attr(pn_customers_manager_data_attr) ||
            pn_customers_manager_btn.closest('.pn-customers-manager-' + pn_customers_manager_cpt_short + '-list-item').attr(pn_customers_manager_data_attr);

          if (pn_customers_manager_id) {
            pn_customers_manager_popup_element.attr(pn_customers_manager_data_attr, pn_customers_manager_id);
          }
        }

        pn_customers_manager_Popups.open(pn_customers_manager_popup_element);
      }
    });

    // Generate event listeners for duplicate and remove functions based on CPTs
    var pn_customers_manager_cpts_mapping = {
      'cm_pn_funnel': 'cm_pn_funnel',
      'cm_pn_org': 'cm_pn_org'
    };

    // Mapping for data attribute names and AJAX parameter names
    var pn_customers_manager_cpts_data_attr = {
      'cm_pn_funnel': 'data-cm_pn_funnel-id',
      'cm_pn_org': 'data-cm_pn_org-id'
    };

    var pn_customers_manager_cpts_ajax_param = {
      'cm_pn_funnel': 'cm_pn_funnel_id',
      'cm_pn_org': 'cm_pn_org_id'
    };

    var pn_customers_manager_cpts_ajax_type = {
      'cm_pn_funnel': {
        'duplicate': 'cm_pn_funnel_duplicate',
        'remove': 'cm_pn_funnel_remove'
      },
      'cm_pn_org': {
        'duplicate': 'cm_pn_org_duplicate',
        'remove': 'cm_pn_org_remove'
      }
    };

    // Loop through CPTs to create duplicate event listeners
    if (typeof pn_customers_manager_cpts !== 'undefined') {
      Object.keys(pn_customers_manager_cpts).forEach(function (cpt) {
        var cpt_short = cpt.replace('pn_customers_manager_', '');
        // If cpt doesn't have the prefix, use it as is
        if (cpt_short === cpt) {
          cpt_short = cpt;
        }

        // Skip if we don't have mapping for this CPT
        if (!pn_customers_manager_cpts_mapping[cpt] || !pn_customers_manager_cpts_data_attr[cpt_short] || !pn_customers_manager_cpts_ajax_type[cpt_short]) {
          return;
        }

        var container_class = '.pn-customers-manager-' + pn_customers_manager_cpts_mapping[cpt] + '-list-wrapper';
        var list_class = '.pn-customers-manager-' + (cpt_short === 'cm_pn_funnel' ? 'funnels' : 'organizations');
        var data_attr = pn_customers_manager_cpts_data_attr[cpt_short];
        var ajax_type = pn_customers_manager_cpts_ajax_type[cpt_short]['duplicate'];
        var ajax_param = pn_customers_manager_cpts_ajax_param[cpt_short];

        // Duplicate event listener
        $(document).on('click', '.pn-customers-manager-' + cpt + '-duplicate-post', function (e) {
          e.preventDefault();
          e.stopPropagation();

          // Close menu more first
          $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');
          $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');
          
          // Find the list container
          var pn_customers_manager_list_container = $(list_class);
          if (!pn_customers_manager_list_container.length) {
            pn_customers_manager_list_container = $(container_class).find('ul');
          }
          
          if (pn_customers_manager_list_container.length) {
            pn_customers_manager_list_container.fadeOut('fast');
          }
          var pn_customers_manager_btn = $(this);

          // Try multiple selectors to find the ID
          var pn_customers_manager_id = pn_customers_manager_btn.closest('.pn-customers-manager-' + cpt_short).attr(data_attr) ||
            pn_customers_manager_btn.closest('.pn-customers-manager-' + cpt_short + '-list-item').attr(data_attr) ||
            pn_customers_manager_btn.closest('.pn-customers-manager-' + pn_customers_manager_cpts_mapping[cpt]).attr(data_attr) ||
            pn_customers_manager_btn.attr(data_attr);

          if (!pn_customers_manager_id) {
            console.error('Could not find organization ID for duplicate action');
            if (pn_customers_manager_list_container.length) {
              pn_customers_manager_list_container.fadeIn('fast');
            }
            pn_customers_manager_get_main_message('Error: Could not find organization ID');
            return;
          }

          var ajax_url = pn_customers_manager_ajax.ajax_url;
          var data = {};
          data.action = 'pn_customers_manager_ajax';
          data.pn_customers_manager_ajax_type = ajax_type;
          data[ajax_param] = pn_customers_manager_id;
          data.pn_customers_manager_ajax_nonce = pn_customers_manager_ajax.pn_customers_manager_ajax_nonce;

          $.post(ajax_url, data, function (response) {
            console.log('data'); console.log(data); console.log('response'); console.log(response);
            var response_json = JSON.parse(response);

            if (response_json['error_key'] != '') {
              pn_customers_manager_get_main_message(response_json['error_content']);
              if (pn_customers_manager_list_container.length) {
                pn_customers_manager_list_container.fadeIn('fast');
              }
            } else {
              // Update the list container
              if (pn_customers_manager_list_container.length) {
                pn_customers_manager_list_container.html(response_json['html']).fadeIn('slow');
              } else if ($(container_class).length) {
                $(container_class).html(response_json['html']).fadeIn('slow');
              } else {
                // Fallback: try to find any list container
                $('.pn-customers-manager-' + cpt_short + '-list-wrapper').html(response_json['html']).fadeIn('slow');
              }
              pn_customers_manager_get_main_message(pn_customers_manager_i18n.duplicated_successfully);
            }

            // Close menu more and overlay
            $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');
            $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');

            // Close all popups
            if (typeof pn_customers_manager_Popups !== 'undefined' && typeof pn_customers_manager_Popups.close === 'function') {
              pn_customers_manager_Popups.close();
            } else {
              // Fallback: manually close popups
              $('.pn-customers-manager-popup').removeClass('pn-customers-manager-popup-active').addClass('pn-customers-manager-display-none-soft').css('display', 'none');
              $('.pn-customers-manager-popup-overlay').removeClass('pn-customers-manager-popup-overlay-active').addClass('pn-customers-manager-display-none-soft').css('display', 'none');
              document.body.classList.remove('pn-customers-manager-popup-open');
            }
          });
        });

        // Remove event listener (for popup button)
        $(document).on('click', '.pn-customers-manager-' + cpt + '-remove', function (e) {
          e.preventDefault();
          e.stopPropagation();

          var pn_customers_manager_btn = $(this);
          var pn_customers_manager_popup = pn_customers_manager_btn.closest('.pn-customers-manager-popup');

          // Try to get ID from multiple sources
          var pn_customers_manager_id = $('.pn-customers-manager-menu-more.pn-customers-manager-active').closest('.pn-customers-manager-' + cpt_short).attr(data_attr) ||
            $('.pn-customers-manager-menu-more.pn-customers-manager-active').closest('.pn-customers-manager-' + cpt_short + '-list-item').attr(data_attr) ||
            $('.pn-customers-manager-menu-more.pn-customers-manager-active').closest('.pn-customers-manager-' + pn_customers_manager_cpts_mapping[cpt]).attr(data_attr) ||
            pn_customers_manager_popup.find('[' + data_attr + ']').attr(data_attr) ||
            pn_customers_manager_popup.attr(data_attr) ||
            $('.pn-customers-manager-popup-active').find('[' + data_attr + ']').first().attr(data_attr);

          if (!pn_customers_manager_id) {
            console.error('Could not find organization ID for remove action');
            pn_customers_manager_get_main_message('Error: Could not find organization ID');
            return;
          }

          // Close menu more first
          $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');
          $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');
          
          // Find the list container
          var pn_customers_manager_list_container = $(list_class);
          if (!pn_customers_manager_list_container.length) {
            pn_customers_manager_list_container = $(container_class).find('ul');
          }
          
          if (pn_customers_manager_list_container.length) {
            pn_customers_manager_list_container.fadeOut('fast');
          }

          var ajax_url = pn_customers_manager_ajax.ajax_url;
          var data = {};
          data.action = 'pn_customers_manager_ajax';
          data.pn_customers_manager_ajax_type = pn_customers_manager_cpts_ajax_type[cpt_short]['remove'];
          data[ajax_param] = pn_customers_manager_id;
          data.pn_customers_manager_ajax_nonce = pn_customers_manager_ajax.pn_customers_manager_ajax_nonce;

          $.post(ajax_url, data, function (response) {
            console.log('data'); console.log(data); console.log('response'); console.log(response);
            var response_json = JSON.parse(response);

            if (response_json['error_key'] != '') {
              pn_customers_manager_get_main_message(response_json['error_content']);
              if (pn_customers_manager_list_container.length) {
                pn_customers_manager_list_container.fadeIn('fast');
              }
            } else {
              // Update the list container
              if (pn_customers_manager_list_container.length) {
                pn_customers_manager_list_container.html(response_json['html']).fadeIn('slow');
              } else if ($(container_class).length) {
                $(container_class).html(response_json['html']).fadeIn('slow');
              } else {
                // Fallback: try to find any list container
                $('.pn-customers-manager-' + cpt_short + '-list-wrapper').html(response_json['html']).fadeIn('slow');
              }
              pn_customers_manager_get_main_message(pn_customers_manager_i18n.removed_successfully);
            }

            // Close menu more and overlay
            $('.pn-customers-manager-menu-more-overlay').fadeOut('fast');
            $('.pn-customers-manager-menu-more.pn-customers-manager-active').fadeOut('slow').removeClass('pn-customers-manager-active');

            // Close all popups
            if (typeof pn_customers_manager_Popups !== 'undefined' && typeof pn_customers_manager_Popups.close === 'function') {
              pn_customers_manager_Popups.close();
            } else {
              // Fallback: manually close popups
              $('.pn-customers-manager-popup').removeClass('pn-customers-manager-popup-active').addClass('pn-customers-manager-display-none-soft').css('display', 'none');
              $('.pn-customers-manager-popup-overlay').removeClass('pn-customers-manager-popup-overlay-active').addClass('pn-customers-manager-display-none-soft').css('display', 'none');
              document.body.classList.remove('pn-customers-manager-popup-open');
            }
          });
        });
      });
    }
  });
})(jQuery);
